---
paths:
  - 'tests/Browser/**'
  - 'tests/BrowserTenancy/**'
---

# Testes de browser (`pest-plugin-browser`)

## O plugin sobe o próprio servidor — não configure nenhum

Servidor HTTP in-process (amphp), em porta aleatória. **Nenhum Herd, `artisan serve`, Sail ou Vite dev server**, e nada de `APP_URL` a configurar.

Como é o **mesmo processo** do teste, três coisas continuam valendo dentro do navegador, ao contrário do que um teste de browser costuma exigir:

- `DB_DATABASE=:memory:` e `RefreshDatabase`
- `$this->actingAs($user)` antes do `visit()` — **use isto**, não login pela UI. Logar pela tela custa ~20 s por cenário; reserve um cenário só para o formulário de login, que é o único caminho real do usuário
- `$this->assertAuthenticated()` e o resto das asserções do Laravel

## `npm run build` é pré-requisito duro

Sem `public/build/manifest.json` **toda** tela responde `ViteException` e todo cenário falha por um motivo que não é o dele. Rode com `composer test:browser`, que já embute o build.

## `view:cache` é o segundo pré-requisito duro

Compilar as ~590 views do kit custa dezenas de segundos, e o **primeiro** cenário que renderiza
um painel paga a conta inteira **dentro do próprio timeout**. Com cache frio ele estoura o teto
de 45s e falha por um motivo que não é o dele — o mesmo estrago do `ViteException` acima, com
outra cara.

Medido em `tests/Browser/CabecalhoDoMenuDoUsuarioTest`:

| Cache de view | Resultado |
|---|---|
| frio (`view:clear` antes) | **falha**, `Timeout 45000ms exceeded`, 50s |
| quente | passa, 6 asserções, 10,6s |

É determinístico, e o disfarce é cruel: numa máquina que acabou de rodar `composer test:kit` as
views estão quentes e tudo passa; num clone novo ou no CI a suíte nasce vermelha, e o sintoma
tem exatamente o formato de teste instável — foi preciso rodar a suíte duas vezes para separar
uma coisa da outra.

`composer test:browser` embute o `view:cache` por isso. **Não remova a linha**, e não "conserte"
o sintoma subindo o `pest()->browser()->timeout()`: isso troca a falha por uma suíte lenta e
mantém a compilação dentro do cronômetro do cenário.

Mesma causa raiz do `view:cache` no boot do container — ver
`wikis/specs/feature/v1-enriquecimento-kit/cache-de-views-no-docker/`.
## `view:cache` não basta para arquivo isolado — aqueça pelo kernel

O `view:cache` cobre as Blade do repositório. **O primeiro render de um painel ainda paga a
compilação dos componentes Livewire do Filament**, e isso são ~25 s que o `view:cache` não
adianta. Rodando a suíte inteira o problema não aparece: os arquivos anteriores pagam a conta.
Rodando **um arquivo só** de `tests/BrowserTenancy`, o primeiro cenário estoura os 45 s.

Medido em `tests/BrowserTenancy/CapturaDeArteTest` depois de um `view:clear`, com o
`view:cache` já executado:

| Aquecimento | Resultado |
|---|---|
| nenhum | **3 de 4 verdes**, o primeiro cenário em `Timeout 45000ms exceeded` |
| um `$this->get()` da mesma tela no `beforeEach` | 4 de 4 verdes, 53 s |

A correção é pagar a conta **fora do cronômetro do Playwright**, num request pelo kernel, no
`beforeEach`:

```php
$this->actingAs($usuario);

// Compila os componentes do painel em PHP, onde ninguém está cronometrando.
// Os arquivos compilados ficam em disco e o servidor do navegador os reusa.
$this->get(ProjetoResource::getUrl('index', tenant: $organizacao));
```

Funciona porque o servidor do plugin roda **no mesmo processo** e lê o mesmo
`storage/framework/views`.

Não troque isto por `pest()->browser()->timeout()` maior: o `tests/Pest.php` registra que 40 s e
60 s reproduzem a falha igual — o problema é a conta estar dentro do cronômetro, não o teto ser
baixo.

## `waitForEvent('networkidle')` não serve em painel do Filament

Ele nunca resolve: o painel fica consultando as notificações, a rede não fica ociosa e o cenário
morre no teto. Espere pelo **estado visível** (`assertSee`, `assertAttributeContains`), que é o
que o plugin reexecuta com retry.

E cuidado com o modo estrito do Playwright: seletor que casa mais de um elemento é **erro**, não
"o primeiro". `.fi-ta-image img` numa listagem com duas linhas de anexo estoura
`strict mode violation`. Prefira seletor por atributo único — `[id="form.painel-app::data::section-heading"]`
em vez de `text=Painel /app`, que também casa o select "Acesso ao painel".

## `assertPathIs` antes das asserções de conteúdo

`assertPathIs` é a asserção que **espera a navegação**. Depois de qualquer ação que navegue (`press`, `click`), ela vem primeiro:

```php
->press('Login')->assertPathIs('/app')->assertSee('Painel de Controle')
```

Invertido, o `assertSee` é avaliado contra o snapshot da página anterior e falha dizendo que não achou o texto — **com a ação tendo funcionado**. O screenshot da falha mostra a tela nova, e o erro aponta a URL velha.

Nunca use `wait(n)` com segundos fixos. O plugin reexecuta cada asserção até o teto de `pest()->browser()->timeout()`; espere pelo estado final visível.

## Nunca `--parallel` com browser

Multiplica processos de navegador e produz timeout. Medido: `pest --parallel --tia` no run completo derruba 4 dos 11 cenários. Rode `pest --testsuite=Browser` em série.

Consequência: o `--tia` exige run **completo** (`--group` e `--exclude-group` o desligam), então `--parallel --tia` e os CT-B não convivem numa invocação só. Use dois comandos:

```bash
vendor/bin/pest --parallel --group=kit   # backend, 196 s
vendor/bin/pest --testsuite=Browser      # telas, em série, 120 s
```

E enquanto o ambiente não tiver **PCOV**, o `--tia` é inviável de qualquer jeito: em série, com Xdebug, não termina (medido: abortado após 35 min).

## `visit([...])` aborta na primeira falha

Lote é o jeito certo de cobrir muitas telas — 52 em 2 cenários. Mas a primeira exceção encerra o laço, e as rotas seguintes **não são verificadas naquele run**. Se o que você quer é *colher todos* os problemas (auditoria de acessibilidade, por exemplo), separe em um cenário por painel.

## Seletores: `aria-label` e texto, não classe de CSS

O kit não tem `data-testid` (dívida conhecida). O disponível hoje:

- campos do Filament: `#form\.email`, `#form\.password` — `id` gerado, e o `.` precisa de escape em CSS
- alternador de tema: `[aria-label="Mudar para tema escuro"]`
- rótulos: texto visível — mas confira o texto **traduzido**. O `<h1>` do dashboard é `Painel de Controle`, não `Dashboard`

## `assertSee` não valida tema

`assertSee('Salvar')` **passa** com texto branco em fundo branco: o texto está no DOM, só está invisível. `->inDarkMode()->assertSee(...)` prova que a tela abre sob `prefers-color-scheme: dark`, e nada sobre legibilidade. Para defeito de cor não há saída barata — é screenshot e olhar.

Use `assertNoSmoke()` só em tela de autoria própria; nas de plugin de terceiro, `assertNoJavaScriptErrors()`, senão a suíte fica vermelha por `console.log` que ninguém vai corrigir.

## `assertVisible` não prova posição — para layout, meça geometria via `script()`

`assertVisible` do Playwright passa para qualquer elemento com caixa não-vazia que não esteja `display:none`/`visibility:hidden`. **Posição fora da viewport não conta.** O F-45 (busca ⌘K) ficou verde por um mês com o overlay a 1.833 px do topo numa viewport de 1.117 px: o HTML era correto, faltava o CSS inteiro, e o usuário via "nada acontece".

Quando o cenário afirma sobre **onde** ou **como** algo aparece — ancorado, sobreposto, centralizado, com fundo —, o oráculo é número, não presença. `script()` devolve o que o Chromium calculou:

```php
$medida = json_decode((string) $pagina->script(<<<'JS'
    (() => {
        const el = document.querySelector('[x-on\\:open-spotlight\\.window]');
        const r = el.getBoundingClientRect(), cs = getComputedStyle(el);
        return JSON.stringify({ position: cs.position, zIndex: cs.zIndex, fundo: cs.backgroundColor,
                                top: r.top, altura: r.height, viewportH: innerHeight });
    })()
JS), true, flags: JSON_THROW_ON_ERROR);

expect($medida['position'])->toBe('fixed')->and($medida['top'])->toEqual(0);
```

Diferente de cor (onde continua sendo screenshot e olhar), geometria discrimina: `top: 1833`, `z-index: auto` e `rgba(0, 0, 0, 0)` reprovam com mensagem. Rode o cenário novo contra o código **sem** a correção antes de aceitá-lo — se não ficar vermelho, ele não mede o defeito. Padrão em `tests/Browser/RoteiroDoKitTest.php` (F-45); origem em `wikis/specs/fix/spotlight-sem-estilo/`.

## `kit:arte` publica de uma lista declarada — captura nova precisa da linha

`tests/Browser/Screenshots` é caminho fixo do `pest-plugin-browser` e recebe TUDO: as capturas de
arte, os `->screenshot()` de evidência de qualquer CT-B, e os screenshots que o Pest grava sozinho
quando um cenário de navegador FALHA. O plugin limpa o diretório no início de cada run, então
sobra do run anterior não existe — mas screenshot de falha DO MESMO run existe, e já quase entrou
no `art/`.

Por isso `KitArte::IMAGENS` é uma lista de nomes: arquivo não declarado é **reportado**, nunca
publicado e nunca silenciado. Os dois erros ficam visíveis — o intruso aparece como ignorado, e a
captura nova que esqueceu a linha aparece como ignorada também, com o nome dela.

Ao acrescentar uma captura: o `->screenshot(filename: 'x')` no cenário **e** a linha `'x'` em
`KitArte::IMAGENS`.

E o `composer art` roda os arquivos de captura numa **única** invocação do `artisan test`. Duas
invocações não funcionam: a segunda limpa o diretório e apaga o que a primeira escreveu, e o
`kit:arte` publica só o resto — sem erro nenhum, com quatro imagens silenciosamente não
atualizadas.
## Cenário de navegador visita o painel em que o processo foi deixado
**Substitui a rule anterior sobre `fronteiraDeRequest()` na captura de arte** — aquela descrevia o sintoma e prescrevia uma correção que não funciona.

O servidor do `pest-plugin-browser` roda in-process. Cenário arranjado num painel e que visita OUTRO renderiza a tela com **a barra lateral do painel do arranjo**: cabeçalho e conteúdo saem certos, a navegação ao lado é de outro painel, e os ícones da topbar saem repetidos.

Medido com o mesmo cenário (`/admin/shield/roles/{id}/edit`):

| Arranjo antes do `visit()` | Barra lateral |
|---|---|
| `noPainelDa($org)` + `get()` de URL do /app | **/app** — Projetos, Convites, Usuários |
| nenhum arranjo de /app | /admin — Usuários, Onboarding, Organizações, Funções |

Por isso `art/admin-papeis-import-export.png` ficou errada do commit `04642b0` até a correção: o `beforeEach` arranjava o /app e aquele cenário visita o /admin. As outras capturas do arquivo nunca estiveram erradas — arranjam e visitam o /app.

**Não reproduz em `$this->get()`**: em HTTP puro o painel troca certo em qualquer ordem, e o painel de destino não acumula item de navegação alheio (`getNavigationItems()` devolve só o declarado no provider). É específico do `visit()`.

**`fronteiraDeRequest()` não resolve**, e foi tentado duas vezes: no `beforeEach` derruba os cenários que criam model com `BelongsToTenant` (esquece `Filament::getTenant()`), e antes do `visit()` produz topbar duplicada com a barra lateral ainda errada.

Regra: **o `beforeEach` não arranja painel**. Cada cenário arranja o seu, imediatamente antes de visitar. Ver `arranjarPainelApp()` em `tests/BrowserTenancy/CapturaDeArteTest.php`.

E ao revisar captura de tela, confira a **barra lateral** — nenhum `assertSee` afirma sobre ela, então o defeito passa verde.

## O ColorPicker dentro de Tabs emite ResizeObserver no headless do CI, e não é defeito seu

`assertNoJavaScriptErrors()` numa tela com `ColorPicker` dentro de `Tabs` reprova **só no CI**:
o Chrome headless do Linux emite `ResizeObserver loop completed with undelivered notifications`
duas vezes na montagem, e o Chrome do Windows não. É ruído do navegador — observers reagindo em
cascata —, não erro da aplicação.

O plugin não oferece filtro: `assertNoJavaScriptErrors()` compara com array vazio
(`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:78-89`).

Então, em tela com esse par de componentes, **não use a asserção** e escreva por que ela não
está ali. Os oráculos que provam o comportamento são os de visibilidade do campo, e esses ficam.
Mesmo espírito da nota sobre `assertNoSmoke()` em tela de plugin: suíte vermelha por dívida
alheia ninguém conserta, e o que ela ensina é a ignorar o vermelho.

Custou um CI vermelho na feature `settings-do-kit`, com a suíte passando local.
