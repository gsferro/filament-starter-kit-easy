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
