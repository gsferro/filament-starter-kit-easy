# Casos de Teste de Browser — Page header nas telas de registro

> Requisito: [`00-requisito.md`](00-requisito.md) · Casos de backend: [`04-casos-de-teste.md`](04-casos-de-teste.md)
> Runtime: `pest-plugin-browser` (Playwright). **O plugin sobe o próprio servidor** — nenhum Herd,
> `artisan serve`, Sail ou Vite dev server, e nada de `APP_URL` a configurar.
> Comando: `composer test:browser` (embute `npm run build` + `view:cache`). **Em série — nunca `--parallel`.**

## Gate — por que este arquivo existe

O `01` declara superfície de UI **presente, com JS**: o pacote registra um `AlpineComponent` por `x-load`
(`header.blade.php:17-18`) e um comportamento de compactação ligado ao scroll. E declara dependência de **tema**:
a CSS do pacote define tokens em `:root .fph-root` e os redefine em **`.dark .fph-root`**
(`page-header.css:13-18`), a partir dos `--gray-*` do Filament.

Quatro eixos passam pelo gate. Cada um afirma sobre algo que **só o navegador prova**:

| Eixo | Por que o `04` não alcança | CT-B |
|---|---|---|
| **cor / tema escuro** | `assertSee` devolve o **mesmo HTML** nos dois temas — passa com texto branco em fundo branco. Tokens CSS só existem depois de o navegador resolver a cascata | CT-B01 |
| **JavaScript executado** | o modo compacto é Alpine + scroll. No servidor o HTML é idêntico com e sem o componente carregado | CT-B02 |
| **console limpo** | a entrega põe um componente Alpine de **terceiro** em seis telas. Erro de JS não muda status nem corpo | CT-B03 |
| **acessibilidade** | o header traz um `<h1>` novo, um `<img>` com `alt` e um bloco de iniciais `aria-hidden`. Árvore de acessibilidade não existe fora do navegador | CT-B04 |

Tudo o mais desta feature — presença do `fph-root`, região `fph-avatar`, permissão, tenancy, superfície Livewire,
escape — é **mais barato e mais preciso** por HTTP ou por componente, e está no `04`. Ver
[`## Cogitado e cortado`](#cogitado-e-cortado).

**Teto do perfil**: as áreas B, C, D e E1 são `padrão` (1 happy path) e A e F são `completo`
(1 happy path + 1 erro visível). Quatro CT-B cabem sem estouro: CT-B01 e CT-B02 são os happy paths de tema e de
JS, CT-B03 é o erro visível (console) e CT-B04 é o erro visível de acessibilidade.

---

## Pré-requisitos

- [ ] `npm run build` executado — sem `public/build/manifest.json` **toda** tela responde `ViteException` e todo
      cenário falha por um motivo que não é o dele
- [ ] `php artisan view:cache` executado — com cache frio o **primeiro** cenário que renderiza um painel paga a
      compilação das ~590 views **dentro** do timeout de 45 s e estoura. Determinístico, e com cara de flake
- [ ] `php artisan filament:assets` executado — a CSS e o JS do pacote precisam estar em
      `public/css/mortalkiller/filament-page-header/` e `public/js/mortalkiller/filament-page-header/`.
      **Sem isto CT-B01 mede o Filament, não o pacote**, e CT-B02 nunca carrega o componente Alpine
- [ ] **Aquecimento pelo kernel no `beforeEach`**: `$this->get($rota)` antes de qualquer `visit()`. O `view:cache`
      não adianta a compilação dos componentes Livewire do Filament (~25 s), e rodando o arquivo isolado ela cai
      dentro do cronômetro do Playwright
- [ ] `tests/Browser/Screenshots` no `.gitignore` — e toda captura que precise ser publicada exige a linha
      correspondente em `KitArte::IMAGENS`, senão é reportada como ignorada
- [ ] Autenticação por `$this->actingAs($user)` **antes** do `visit()` — login pela tela custa ~20 s por cenário
- [ ] **O `beforeEach` não arranja painel.** Cada cenário arranja o seu, imediatamente antes de visitar: o
      servidor roda in-process e o `visit()` renderiza com a barra lateral do painel em que o processo foi
      deixado. `fronteiraDeRequest()` **não** resolve — foi tentado duas vezes

---

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| raiz do header | `.fph-root` / `[data-fph-root]` | **sim**, `header.blade.php:13-14` |
| caixa do header | `.fph-header` (`[data-fph-header]`) | sim, `:21` |
| título | `h1.fph-heading` | sim, `components/heading.blade.php:3` |
| avatar | `.fph-avatar` (e `.fph-avatar img`) | sim, `layout.blade.php:25` |
| badges | `.fph-badges` | sim, `layout.blade.php:46` |
| metadata | `.fph-metadata` | sim, `layout.blade.php:60` |
| ações do header | `.fph-actions` | sim, `actions.blade.php:10` |
| alternador de tema | `[aria-label="Mudar para tema escuro"]` | sim (kit) |

**Modo estrito do Playwright**: seletor que casa mais de um elemento é **erro**, não "o primeiro". `.fph-root` é
único por página (uma página, um header) — mas `.fph-avatar img` casaria o avatar da barra superior se o seletor
fosse `img` solto. Ancore sempre no `.fph-root`.

**O kit não tem `data-testid`** (dívida conhecida). Esta entrega **não** a fecha: as classes `fph-*` são API
pública do pacote, versionada, e R8 do `04` já as guarda.

---

## CT-B01 — o header obedece ao tema escuro

**Por que browser e não Livewire**: a asserção é sobre **cor resolvida**. O `04` prova que o `fph-root` está no
HTML (CT-03); nada no servidor distingue um header que lê `.dark .fph-root` de um que ignora o bloco inteiro. O
HTML é **byte a byte o mesmo** nos dois temas. E `->inDarkMode()->assertSee('Painel')` não testa tema coisa
nenhuma — passa com texto branco em fundo branco.

**Por que medir E fotografar.** A regra do projeto é explícita: para defeito de **cor** não há saída barata, é
screenshot e olhar. Mas `getComputedStyle` de uma **custom property** discrimina com mensagem, e é o que separa
"a cascata aplicou o bloco `.dark`" de "não aplicou". Os dois, com papéis distintos: a medição é o oráculo que
reprova sozinho; a captura é a evidência que um humano confere no PR.

```gherkin
# language: pt
  Regra: o header segue o tema do painel

    Cenário: [CT-B01] os tokens do header mudam entre o tema claro e o escuro
      Dado um administrador autenticado no painel "/admin"
      E uma conta alvo com nome conhecido e sem foto de perfil
      Quando ele abre a ficha dessa conta com o navegador anunciando tema escuro
      Então o elemento raiz do header existe e está visível
      E o valor calculado do token de texto do header é diferente do valor do mesmo token no tema claro
      E o valor calculado do token de borda do header é diferente do valor do mesmo token no tema claro
      E a cor de fundo calculada do avatar de iniciais é diferente da do tema claro
      E o título do header continua exibindo o nome da conta
      E não há erro de JavaScript no console
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `$this->actingAs(usuarioDoKit('admin'))` + alvo `User::factory()` sem `avatar_url` | — |
| 2 | aquecimento | `$this->get($urlDaFicha)` — fora do cronômetro do Playwright | — |
| 3 | linha de base | `visit($urlDaFicha)` → `script()` lendo `getComputedStyle(document.querySelector('.fph-root'))` e devolvendo `getPropertyValue('--fph-text' \| '--fph-border' \| '--fph-avatar-background')` | três strings de cor |
| 4 | tema escuro | `visit($urlDaFicha)->inDarkMode()` → mesma leitura | três strings de cor |
| 5 | oráculo | `expect($escuro['texto'])->not->toBe($claro['texto'])` — e o mesmo para borda e fundo do avatar, **cada `expect` com um valor só** (`toBe` é variádico? não — mas `toContain` é, e é o vizinho de erro) | reprova com os dois valores na mensagem |
| 6 | evidência | `->screenshot(filename: 'page-header-tema-escuro')` | imagem para o revisor |

**Por que três tokens e não um.** Um só mata "o bloco `.dark` não é aplicado". Três matam também "o bloco existe
mas perdeu uma linha no merge" — que é o modo de falhar de uma CSS de vendor republicada por `filament:assets`.

**Por que a comparação claro × escuro e não um valor literal.** Fixar `rgb(250, 250, 250)` amarra o caso ao
número exato de `--gray-*` do Filament, que muda entre versões menores; o caso reprovaria num upgrade legítimo. A
**diferença** entre os dois temas é o que o requisito de "seguir os tokens do Filament" significa, e ela
sobrevive ao upgrade.

**A captura só vale se a linha existir em `KitArte::IMAGENS`** — arquivo não declarado é reportado, nunca
publicado. Se a intenção for apenas evidência de PR, e não arte publicada, **não declare a linha** e diga isso no
cenário: o `tests/Browser/Screenshots` é limpo a cada run.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | a CSS do pacote não é publicada (`filament:assets` esquecido no fluxo de atualização) — o header sai sem estilo nos dois temas | CT-B01, passo 5: os três tokens vêm vazios e **iguais** nos dois temas |
| MB2 | alguém publica a CSS do pacote em `resources/css/` e a customiza, perdendo o bloco `.dark` | CT-B01 |
| MB3 | o header é colocado fora do contêiner que recebe a classe `.dark` do Filament | CT-B01 |
| MB4 | o caso lê o token do `<html>` em vez do `.fph-root` e mede o Filament, não o pacote | ⚠️ **sem matador** — **lacuna declarada**: é erro do teste, não do produto. Mitigação escrita: o seletor do `script()` é `.fph-root`, e CT-B02 confirma que esse elemento existe |

---

## CT-B02 — o componente Alpine do pacote carrega e o modo compacto funciona

**Por que browser e não Livewire**: o `fph-root` traz `x-load`, `x-load-src` e `x-data="pageHeader(...)"`. No
servidor esses são **atributos de texto** — o HTML é idêntico com o JS do pacote publicado e com ele ausente. O
`04` (CT-25) prova que `data-fph-options` está no DOM e não carrega PII; nada prova que **algo leu** esse
atributo.

```gherkin
# language: pt
  Regra: o comportamento compacto do header roda no navegador

    Cenário: [CT-B02] o header reage à rolagem da página
      Dado um administrador autenticado no painel "/admin"
      E a ficha de uma organização que tem conteúdo suficiente para rolar
      Quando ele abre a ficha e rola a página para baixo
      Então o elemento raiz do header ganha o estado compacto
      E o título do header continua visível
      E, ao voltar ao topo, o estado compacto sai
      E não há erro de JavaScript no console
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `actingAs` + organização com relacionamentos, para a página ter altura | — |
| 2 | aquecimento | `$this->get($url)` | — |
| 3 | linha de base | `visit($url)` → ler o estado do `.fph-root` por `script()` (atributo/classe que o componente alterna) | estado "expandido" |
| 4 | ação única | `script('window.scrollTo(0, document.body.scrollHeight)')` | — |
| 5 | oráculo | esperar pelo **estado final visível** (`assertAttributeContains` no `.fph-root`), nunca `wait(n)` | estado "compacto" |
| 6 | volta | rolar ao topo, e o estado sai | estado "expandido" |
| 7 | apoio | `assertNoJavaScriptErrors()` | — |

**A linha de base do passo 3 é o que separa os dois mundos.** Sem ela, um header que nascesse **sempre** compacto
— ou um `script()` que lesse um atributo inexistente e devolvesse a mesma string nas duas leituras — passaria.
É o mesmo par linha-de-base × oráculo que `AlertaDeAlteracoesNaoSalvasTest` usa nesta base.

**Nunca `wait($segundos)` e nunca `waitForEvent('networkidle')`.** O painel do Filament fica consultando
notificações, a rede nunca fica ociosa e o cenário morre no teto. Espere pelo estado visível, que o plugin
reexecuta com retry.

**Se o pacote não expuser estado observável no DOM** (classe ou atributo alternado pelo Alpine), o oráculo passa
a ser **geometria**: `getBoundingClientRect().height` do `.fph-header` antes e depois da rolagem, com a
expectativa de que a altura **diminua**. Número discrimina e reprova com mensagem —
`.ai/rules/testes-browser.md` §*"`assertVisible` não prova posição"*. Decidir na implementação, e registrar
qual dos dois foi usado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB5 | o JS do pacote não é publicado — `x-load-src` aponta para 404 e o componente nunca monta | CT-B02, passo 5 |
| MB6 | o `x-data` é registrado sem `x-load`, ou o `FilamentAsset::register` do pacote não roda no painel | CT-B02 |
| MB7 | o header nasce compacto e nunca expande | CT-B02, passo 3 (linha de base) |
| MB8 | trocar de aba de relacionamento reinicia o estado compacto | ⚠️ **sem matador** — **lacuna declarada**: o vendor afirma que não reinicia (`docs/configuration.md:232`), e o cenário exigiria uma ficha com abas **e** rolagem **e** clique de aba, três ações num cenário só. Cogitado e cortado abaixo |

---

## CT-B03 — console limpo nas telas que ganharam o header

**Por que browser**: erro de JavaScript não muda status, não muda corpo e não deixa rastro no log do servidor.
Nenhum caso do `04` fica vermelho com o componente Alpine do pacote quebrando na montagem.

**`assertNoJavaScriptErrors()` e NÃO `assertNoSmoke()`**: as seis telas carregam um componente Alpine de
**terceiro**. `assertNoSmoke()` reprova em qualquer `console.log` alheio, e a suíte ficaria vermelha por dívida
que ninguém vai corrigir — o que ela ensina é a ignorar o vermelho.

```gherkin
# language: pt
  Regra: o header não quebra o JavaScript das telas em que entra

    Cenário: [CT-B03] as telas do painel /admin com header abrem sem erro de console
      Dado um administrador autenticado no painel "/admin"
      E uma conta alvo e uma organização alvo
      Quando ele percorre a ficha e a edição das duas
      Então cada tela exibe o título do registro correspondente no header
      E não há erro de JavaScript no console em nenhuma delas
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `actingAs(usuarioDoKit('admin'))` + os dois alvos | — |
| 2 | aquecimento | um `$this->get()` por rota | — |
| 3 | lote | `visit([$fichaUser, $editUser, $fichaTenant, $editTenant])` | — |
| 4 | âncora | `->assertSee($nomeComumAosQuatro)` — **console limpo sozinho passa em página em branco, em 403 renderizado e em tela sem conteúdo** | — |
| 5 | oráculo | `->assertNoJavaScriptErrors()` | — |

**Um cenário por painel, nunca um lote com os dois.** `visit([...])` **aborta na primeira falha** e as rotas
seguintes não são verificadas naquele run. O `/app` fica em `tests/BrowserTenancy`, em cenário próprio — e, por
tenancy, o arranjo dele é outro.

**Não use este cenário para provar que o header existe**: isso é CT-03 do `04`, mais barato e mais preciso. A
âncora do passo 4 existe só para o console não ser o oráculo único.

**Atenção ao ruído de CI conhecido, verificado**: `ColorPicker` dentro de `Tabs` emite
`ResizeObserver loop completed…` no Chrome headless do Linux e **não** no do Windows. A edição de organização
**tem** `ColorPicker` (`Schemas/TenantForm.php:126`) — mas dentro de `Section`, **não** de `Tabs`
(`:34`, `:107`: nenhum `Tabs` no arquivo). Logo `assertNoJavaScriptErrors()` é seguro nas quatro telas hoje.
Se alguém mover o formulário de organização para `Tabs`, **tire a asserção daquela tela** e escreva por quê — os
oráculos que ficam são os de conteúdo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB9 | o componente Alpine do pacote colide com um plugin já registrado no painel | CT-B03 |
| MB10 | o `json_encode` das opções do header emite JSON inválido no atributo e o Alpine estoura na montagem | CT-B03 |
| MB11 | a tela renderiza em branco sob o header novo | CT-B03, passo 4 (âncora de conteúdo) |

---

## CT-B04 — a árvore de acessibilidade da tela nova

**Por que browser**: `assertNoAccessibilityIssues()` roda sobre a árvore que o navegador constrói. A `ViewUser` é
tela **nova**, e o header acrescenta um `<h1>`, um `<img>` com `alt` e um bloco de iniciais `aria-hidden="true"`.
Nenhuma dessas três coisas é verificável no HTML cru sem reimplementar o axe.

```gherkin
# language: pt
  Regra: a tela nova não regride a acessibilidade do painel

    Cenário: [CT-B04] a ficha de conta não introduz problema de acessibilidade
      Dado um administrador autenticado no painel "/admin"
      E uma conta alvo **com** foto de perfil
      Quando ele abre a ficha dessa conta
      Então o título do header é o único elemento de nível 1 da página
      E a imagem do avatar tem texto alternativo não vazio
      E a página não acusa problema de acessibilidade
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | conta **com** `avatar_url` — é o estado que produz o `<img>`, e sem ele a asserção de `alt` é vácua | — |
| 2 | aquecimento | `$this->get($url)` | — |
| 3 | visita | `visit($url)` | — |
| 4 | oráculo 1 | contar `h1` por `script()` — `document.querySelectorAll('h1').length === 1` | número |
| 5 | oráculo 2 | ler `alt` de `.fph-root .fph-avatar img` e afirmar que não é vazio e contém o nome | string |
| 6 | oráculo 3 | `assertNoAccessibilityIssues()` | — |

**O passo 1 é o que impede o falso ✅.** Com uma conta **sem** foto, o `<img>` não existe (o `@elseif` cai nas
iniciais) e o oráculo 2 afirmaria sobre o vazio — exatamente a asserção de ausência em mundo sem destinatário
que o `04` combate em R1/CT-02.

**O passo 4 pega um defeito real e barato**: o pacote emite `<h1 class="fi-header-heading fph-heading">`, e se a
página também renderizar o cabeçalho nativo (trait aplicado **junto** com um `getHeader()` que não o substitui)
a página fica com dois `<h1>`. R12/CT-41 pega a causa no fonte; este pega o sintoma no navegador.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB12 | o header nativo e o do pacote renderizam juntos → dois `<h1>` | CT-B04, passo 4 |
| MB13 | o avatar passa `alt=""` ou um `alt` genérico | CT-B04, passo 5 |
| MB14 | o bloco de iniciais perde o `aria-hidden` e o leitor de tela anuncia "AP" | CT-B04, passo 6 (o axe o pega como conteúdo sem rótulo) — **ou não**: se o axe não o classificar como problema, vira **lacuna declarada** |

---

## Cogitado e cortado

O gate de browser é generoso e o teto é apertado. O que foi pensado e **não** virou CT-B:

| Cenário cogitado | Por que foi cortado |
|---|---|
| "o `fph-root` está presente nas seis telas, no navegador" | já provado por CT-03 do `04`, por HTTP, em milissegundos e nas seis telas — o navegador não acrescenta nada |
| "o `data:` URI não chega ao `<img>` do header" | CT-07 do `04` o prova mais barato **e melhor**: o navegador não distingue `data:` de `http:` no DOM. Um CT-B sobre isso mediria **rede** ("nada foi buscado"), que é outro oráculo, e o `AvatarDeIniciaisTest` já o exerce para o avatar do painel |
| "o `<img>` do avatar carrega de fato" (`naturalWidth > 0`) | tentador e **quase** entra. Cortado porque o URL é `/storage/…` servido pelo próprio app e o `04`/CT-08 já afirma o esquema; o mutante que ele mataria (arquivo ausente no disco) é de upload, fora do escopo desta entrega |
| "a permissão negada mostra 403 no navegador" | CT-11 e CT-12 do `04` provam status, corpo e saída. O navegador não acrescenta oráculo — só custo |
| "o header aparece acima das abas de relacionamento" | CT-46 do `04` afirma a ordem no DOM. **Posição** no navegador exigiria medir geometria, e a ordem no DOM já é o contrato (regiões disjuntas). Se um dia o layout quebrar visualmente sem quebrar a ordem, o caso passa a valer — registrado |
| "trocar de aba não reinicia o modo compacto" | três ações num cenário só (rolar, clicar na aba, verificar), contra a regra de **um `Quando`**. E é comportamento do vendor, declarado em `docs/configuration.md:232`. Vira **MB8, lacuna declarada** |
| "a ficha no `/app` em tema escuro" | mata o mesmo mutante que CT-B01 e custa o arranjo de tenancy inteiro. Se o `/app` ganhar tema próprio, reabre |
| "o alternador de tema muda o header ao clique" | o `tests/Browser/TemaEscuroTest.php` já cobre o alternador do kit (cenário de outra wiki — ID deliberadamente não citado aqui, para não poluir a sincronia de IDs deste `05`); CT-B01 cobre o header sob `prefers-color-scheme`. A combinação não traz mutante novo |
| "a tela nova não regride o tempo de carregamento" | não há orçamento de desempenho declarado no requisito. Sem oráculo, não é caso |

---

## Roteiro de Validação: Desenhado × Implementado

> Preencher **depois** de implementar. É o gancho do `feature-quality-gate`.

| # | O que o requisito pediu | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | header nas telas de View/Edit de `User` (dois painéis) | | | |
| 2 | header nas telas de View/Edit de `Tenant` | | | |
| 3 | permissão para a ficha de conta (RQ-13) | | | |
| 4 | o header segue o tema do painel | | | CT-B01 + captura |
| 5 | o comportamento compacto funciona no navegador | | | CT-B02 |
| 6 | nenhuma tela regride console ou acessibilidade | | | CT-B03, CT-B04 |

---

## Armadilhas que invalidariam estes CT-B

| Armadilha | Consequência aqui |
|---|---|
| `--parallel` com browser | medido nesta base: derruba 4 de 11 cenários. E `--parallel --tia` não convive com CT-B numa invocação só — são dois comandos |
| `wait($segundos)` / `waitForEvent('networkidle')` | o painel consulta notificações sem parar; a rede nunca fica ociosa e o cenário morre no teto. Espere pelo **estado visível** |
| `assertPathIs` depois do `assertSee` | nenhum destes CT-B navega por `press`/`click` — se algum passar a navegar, `assertPathIs` vem **primeiro**, é ela que espera a navegação |
| `beforeEach` que arranja painel | o `visit()` renderiza com a barra lateral do painel em que o processo foi deixado. Cada cenário arranja o seu, imediatamente antes de visitar |
| `assertSee` como oráculo de tema | passa com texto branco em fundo branco. CT-B01 mede token calculado, não texto |
| `assertVisible` como oráculo de layout | passa para qualquer caixa não-vazia, **inclusive fora da viewport**. Para posição, geometria por `script()` |
| `assertNoSmoke()` em tela com plugin de terceiro | vermelho por `console.log` alheio. Use `assertNoJavaScriptErrors()` |
| `visit([...])` em lote atravessando painéis | aborta na primeira falha; o `/app` vai para `tests/BrowserTenancy`, em cenário próprio |
| `.fph-avatar img` sem âncora no `.fph-root` | modo estrito do Playwright: casaria o avatar da barra superior também, e o erro é `strict mode violation`, não "o primeiro" |
| screenshot sem linha em `KitArte::IMAGENS` | reportado como ignorado e nunca publicado. Se for só evidência de PR, **não** declare a linha e diga isso no cenário |

---

## Índice de CT-B

| ID | Cenário | Só o navegador prova | Suíte | Arquivo sugerido | Mata |
|---|---|---|---|---|---|
| CT-B01 | tokens do header no tema escuro | cor resolvida pela cascata | `Browser` | `tests/Browser/PageHeaderTemaTest.php` | MB1, MB2, MB3 |
| CT-B02 | modo compacto na rolagem | JavaScript executado | `Browser` | `tests/Browser/PageHeaderCompactoTest.php` | MB5, MB6, MB7 |
| CT-B03 | console limpo nas quatro telas do `/admin` | erro de JS | `Browser` | `tests/Browser/PageHeaderTelasTest.php` | MB9, MB10, MB11 |
| CT-B04 | acessibilidade da ficha nova | árvore de acessibilidade | `Browser` | `tests/Browser/PageHeaderAcessibilidadeTest.php` | MB12, MB13 |

**Mutantes sem matador — 3, declarados**: MB4 (o caso ler o token do elemento errado), MB8 (aba reinicia o modo
compacto), MB14 (o axe pode não classificar as iniciais sem `aria-hidden` como problema).
