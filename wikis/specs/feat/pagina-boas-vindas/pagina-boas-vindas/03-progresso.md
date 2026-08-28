# Progresso — página de boas-vindas na rota `/`

Branch: `feat/pagina-boas-vindas` · base: `main` (`eb9a589`)

## 0. Desenho da tela (RQ-08)

- [x] Skill `design` invocada
- [x] `design/Main.dc.html` + `design/canvas.json` escritos com tokens lidos do projeto
- [x] Artboard publicado: https://claude.ai/code/artifact/cd1677da-a5f4-44f0-9995-70baf64e0552

## 1. A página com os cartões dos painéis (RQ-01, RQ-03, RQ-04, RQ-09, RQ-10, RQ-11)

- [x] `app/Filament/Pages/BoasVindas.php` criada
- [x] `$layout` apontando para `filament-panels::components.layout.simple`
- [x] `getPageClasses()` devolvendo `['kit-cards-page']`
- [x] `getCards()` com os três `CardItem`, URL por `Filament::getPanel($id)->getUrl()`
- [x] PHPDoc da `getCards()` explicando a ausência de filtro por autorização (ADR-03)

## 2. A infolist com as informações do kit (RQ-05, RQ-06, RQ-07, RQ-09, RQ-12)

- [x] `informacoesDoKit(Schema $schema): Schema` com as duas seções
- [x] `resources/views/filament/pages/boas-vindas.blade.php` (uma linha)
- [x] `getFooter()` apontando para a view
- [x] Formatação de prazo desligado ("Sem poda") e de lembretes vazios ("Desligados")
- [x] Nenhuma chave da lista negra do ADR-04 lida

## 3. A rota (RQ-01, RQ-02, RQ-10, RQ-11)

- [x] `routes/web.php` com `Route::get('/', BoasVindas::class)->middleware('panel:app')`

## 4. Apagar a welcome padrão (RQ-02)

- [x] `resources/views/welcome.blade.php` removida
- [x] Confirmado que nada mais a referencia

## 5. Casos de teste de backend

- [x] `tests/Kit/BoasVindasTest.php` — CT-01, CT-02, CT-03, CT-05, CT-06, CT-07, CT-08, CT-10, CT-11, CT-12, CT-13, CT-14, CT-15, CT-16, CT-17 → **38 casos, 38 verdes, 93 asserções**
- [x] `tests/Tenancy/BoasVindasTest.php` — CT-04 → **1 caso, 1 verde, 4 asserções**

## 6. Casos de teste de navegador

- [x] `tests/Browser/BoasVindasTest.php` — CT-B01 e **CT-B02** → **3 casos, 3 verdes, 10 asserções**
      (CT-B02 é um `Esquema do Cenário` com as linhas `claro` e `escuro`, e nasceu do achado QA-01
      do quality gate)

## 7. Quality gate (step 8 da `feature-wiki`)

- [x] `feature-quality-gate` invocado — perfil **completo** (UI com JS + domínio sensível)
- [x] Matriz de rastreabilidade montada: **12 de 12 `RQ` com rastro**, nenhuma omissão silenciosa,
      nenhum passo/CT/código sem `RQ` de origem
- [x] Onze dimensões percorridas; as duas fora de alcance declaradas com motivo
- [x] `06-relatorio-qa.md` escrito
- [x] **Veredito: APROVADO COM DÉBITO** — 0 Blocker, 0 Major, 2 Minor, 1 ciclo
- [x] QA-01 (destino 3 — teste) **fechado no mesmo ciclo**: CT-B02
- [x] QA-02 (destino 4 — não é defeito desta feature) aceito como débito, abaixo

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — a auditoria do step 6 foi refeita sobre o código escrito
- [x] `vendor/bin/pint --dirty --format agent` → `passed`
- [x] `vendor/bin/phpstan analyse --no-progress` → `0 errors` (level 7)
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel` → **632 casos, 632 verdes,
      1675 asserções**, 397 s. Nenhuma regressão nas telas dos três painéis.
      Rodado com `--parallel` e **sem** `--tia`: `.ai/rules/testes-browser.md` mediu que sem PCOV o
      `--tia` em série não termina (abortado após 35 min), e a primeira tentativa desta feature em
      série sem paralelismo foi abortada aos 25 min sem sair do lugar
- [x] `composer test:browser` (embute `npm run build` + `view:cache`) → **40 casos, 35 verdes,
      5 pulados, 171 asserções**, 410 s, exit 0. Os 5 pulados são pré-existentes e esperados: são
      as capturas de arte de `tests/BrowserTenancy/CapturaDeArteTest.php`, com
      `markTestSkipped()` sob `if (! env('KIT_ART'))` (linha 56) — só `composer art` liga a flag.
      Nenhuma regressão nas 52 telas dos painéis
- [x] `php artisan route:list --name=boas-vindas` → `GET|HEAD / … boas-vindas › App\Filament\Pages\BoasVindas`
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido
- [x] `git commit` + `git push -u origin feat/pagina-boas-vindas` — mergeado via PR #22

## Degradações de ferramenta declaradas

A skill `feature-wiki` exige `search-docs` (Documentation API do Boost) para cada stack que o PRD
toca. Registro honesto do que aconteceu nesta sessão:

1. A instrução inicial deste agente dizia que o MCP do Boost estava indisponível.
2. Uma correção posterior informou que ele havia sido reconectado.
3. **As tools do Boost não chegaram ao toolset deste sub-agente.** Medido: `ToolSearch` com as
   consultas `select:mcp__laravel-boost__search-docs`, `+boost` e `+laravel` devolveu
   "No matching deferred tools found" nas três. `search-docs`, `database-query`,
   `database-schema`, `get-absolute-url` e `record-rule` **não** foram invocáveis em nenhum
   momento.

**Fallback usado**, na ordem de autoridade que a skill define para as lacunas do `search-docs`:

| Pergunta | Como foi respondida |
|---|---|
| infolist do Filament 5 sem registro Eloquent | `WebFetch` em https://filamentphp.com/docs/5.x/infolists/overview — confirmou `infolist(Schema $schema): Schema` e `TextEntry::make(...)->state(...)` para valor estático |
| página Filament fora de painel; dark mode e assets | `WebFetch` em https://filamentphp.com/docs/5.x/styling/overview — a doc **não** cobre o assunto. Por isso o ADR-01 foi construído sobre leitura de `vendor/` com `file:line` e sobre **medição** (`FilamentAsset::renderStyles()` com e sem painel), como `.ai/rules/specs.md` exige |
| comportamento do `harvirsidhu/filament-cards` | leitura de `vendor/harvirsidhu/filament-cards/**` com `file:line` nas ADRs |
| Pest 5 / `pest-plugin-browser` | fora da cobertura do `search-docs` por design; `.ai/rules/testes-browser.md` do projeto é a fonte, e ela vence a skill onde divergem (declarado no `04`) |

Consequência para quem auditar: **nenhuma afirmação sobre comportamento de vendor nesta wiki veio
de memória.** Todas têm `file:line` ou um comando medido ao lado. As duas exceções estão marcadas
como tais: o mutante M6 do `04` (lacuna declarada) e o alerta do SFDIPOT → P sobre o grep de
`@vite` não cobrir blades de vendor.

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa inicial | O que o código real diz | Correção aplicada |
|---|---|---|
| "a welcome atual usa `Route::has('login')` com `route('login')`, que hoje resolve para o painel default" (contexto recebido) | **Não existe rota nomeada `login`.** `php artisan route:list` só tem `filament.app.auth.login`, `filament.admin.auth.login` e `filament.infra.auth.login`. Os blocos `@if (Route::has('login'))` da welcome **nunca renderizaram** neste projeto | `01`, seção "Contexto", reescrita com a medição |
| "Filament 5.7.6, e o `CLAUDE.md` está desatualizado falando de 4" (contexto recebido) | confirmado — mas o `CLAUDE.md` está certo no que importa aqui: os namespaces que ele lista (`Filament\Infolists\Components\TextEntry`, `Filament\Schemas\Components\Section`, `Filament\Support\Icons\Heroicon`) são os da 5.x | nenhuma; o alerta não se materializou |
| `DescobreCardsDoPainel` seria herdado, como nos hubs | o trait filtra por `canAccess()`, e o visitante da `/` é anônimo — o resultado seria **zero cartão** | ADR-03 escrito para registrar a rejeição deliberada e o motivo |
| `@filamentStyles` bastaria para "herdar o css" | **medido**: não traz a folha do Filament e ignora `KIT_COR_PRIMARIA` (âmbar com `Violet` na env) | ADR-01 inteiro nasceu dessa medição; CT-15 é o cenário que a guarda |
| `Filament::bootCurrentPanel()` poderia ser medido por tinker | **falha no console** com `Error: Call to a member function parameter() on null` | registrado em ADR-01 → Riscos, para a próxima pessoa não repetir a tentativa |
| a página poderia usar `livewire()` de `Pest\Livewire` | `pestphp/pest-plugin-livewire` **não** está no `composer.json`. O projeto usa `Livewire\Livewire::test()` (ver `tests/Kit/HubDeCardsTest.php`) | irrelevante no fim: o `04` cortou o cenário de componente por não haver interação a exercer |
| CT-02 poderia viver em `tests/Unit` | `tests/Pest.php` **não** liga `Tests\TestCase` a `Unit` — só a `Feature`, `Kit`, `Tenancy`, `Browser` e `BrowserTenancy`. Um caso ali rodaria sem container | CT-02 alocado em `tests/Kit`, com o motivo escrito no `04` |

### Auditoria Ponytail (step 6)

Executada como leitura crítica da própria wiki, com a escada aplicada aos passos do PRD.

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | painel Filament novo só para a rota `/` | **cortado antes de entrar** | ADR-01, alternativa 2 |
| 2 | copiar/publicar as 397 linhas da blade do pacote de cartões | **cortado** | ADR-01, alternativa 3 |
| 3 | `config/boas-vindas.php` para parametrizar os três cartões | **cortado** — três itens escritos à mão não pagam um arquivo de config | `01`, "Filosofia de Implementação" |
| 4 | channel de log próprio + `[Classe@Método]` por request | **cortado**, com as três condições que dispensam o log nomeadas | ADR-05 |
| 5 | feature flag para manter a welcome viva ao lado da nova | **cortado** — contraria RQ-02 | `01`, "Rollback" |
| 6 | duas views de rodapé (uma por seção) e dois métodos de schema | **cortado** — um schema com duas `Section` faz o mesmo | `01`, passo 2 |
| 7 | `assertNoAccessibilityIssues()` no CT-B | **cortado**, com motivo e condição de retorno | `05`, "cogitados e cortados" |
| 8 | cenário de componente Livewire para a infolist | **cortado** — sem interação a exercer | `04`, "cogitados e cortados" |

Nenhuma sugestão foi recusada: as oito viraram corte.

### Auditoria Ponytail sobre o código escrito (pós-implementação)

| # | Achado no diff | O que foi feito |
|---|---|---|
| 9 | `$columns = 3` repetia o default do `CardsPage` sem dizer por quê | **mantido com docblock**, não removido: é a única barreira contra alguém subir para 5 e produzir uma grade sem CSS, com todo teste verde. O comentário nomeia o teto e aponta CT-05 |
| 10 | o comentário de `routes/web.php` repetia o docblock da classe | **encurtado** — passou a apontar para a classe e para o ADR-01 em vez de repetir a medição |
| 11 | quatro helpers estáticos de formatação (`emDias`, `retencao`, `lembretes`, `corPrimaria`) | **mantidos.** Os quatro são o único código com ramo na página, os quatro estão cobertos (CT-10, CT-11, CT-16) e cada um carrega no docblock o defeito que evita. Inline seria uma expressão ternária dentro de `->state()` sem lugar para o porquê |
| 12 | `cardDoPainel()` com cinco parâmetros nomeados | **mantido** — centraliza o `getUrl() ?? url(getPath())` num lugar só, em vez de repetir a mesma armadilha de tenancy três vezes |
| 13 | `->columnSpanFull()` nas duas `Section` | **mantido.** Duas chamadas contra uma armadilha que o `CLAUDE.md` do projeto nomeia por escrito ("Never assume full-width layout"), e cujo modo de falhar é visual |

## Blockers

Nenhum.

## Débitos Aceitos

### QA-02 — `config/kit.php` lê rótulo de organização com `env()` direto (Minor, destino 4)

`KIT_TENANCY_LABEL=` (presente, valor vazio) faz `env()` devolver string vazia, e o default
`'Organização'` **nunca** entra. **Medido**:

```
$ php artisan config:show kit.tenancy.label
kit.tenancy.label .. Organização              # chave AUSENTE: o default entra

$ KIT_TENANCY_LABEL= php artisan tinker --execute \
    'var_dump(env("KIT_TENANCY_LABEL"), config("kit.tenancy.label"));'
string(0) ""
string(0) ""                                   # chave VAZIA: o default NAO entra
```

É o padrão que `.ai/rules/config.md` documenta, e a varredura mostra que ele **não é** desta
feature nem o pior caso da família: as chaves numéricas já estão guardadas por `NumeroDoEnv` e
`ValidadeDoConvite`; as de **string** não têm equivalente no kit. Os três casos graves são
`KIT_TENANCY_SLUG` (o segmento do CRUD vira vazio: `/admin/organizacoes` → `/admin/`),
`KIT_ADMIN_EMAIL` e `KIT_ADMIN_PASSWORD` (o `UsuarioAdminSeeder` nasce sem credencial de login).

**Não corrigido aqui, deliberadamente.** A rule do projeto manda varrer o padrão inteiro antes de
tocar num ponto, e a varredura devolveu seis chaves em `config/kit.php` — o que é uma entrega
própria (um `TextoDoEnv` irmão do `NumeroDoEnv`), não um remendo dentro de uma feature de
boas-vindas. A tabela completa está no `06-relatorio-qa.md` → QA-02.

## Desvios do Plano

### Três casos de teste corrigidos por especificação errada (causa "a")

Nenhum dos três apontava defeito de código. Os três estavam **errados como especificação**, e a
classificação é a do loop de CT-B da `feature-wiki`: causa **(a)**, CT especificado errado — não
**(b)**, implementação divergente. Corrigir o CT foi o certo; corrigir a aplicação teria sido
destruir o instrumento.

| CT | O que a especificação dizia | Por que estava errado | O que passou a dizer |
|---|---|---|---|
| **CT-06** | `assertDontSee('fi-sidebar')` | `fi-sidebar` aparece **11 vezes** na resposta, todas dentro de blocos `<style>`: o CSS do `gsferro/filament-odometer-easy` e o `resources/css/filament/kit.css` escrevem seletores `.fi-main-sidebar`, que existem mesmo numa página sem barra lateral. Nome de classe em folha de estilo não é elemento renderizado | `assertDontSee('id="fi-main-sidebar"')` (só `livewire/sidebar.blade.php:20` emite) + `assertDontSee('fi-simple-layout-header')` + `assertSee('fi-simple-main-ctn')` |
| **CT-15** | `assertSee(Violet[500])` **e** `assertDontSee(Amber[500])` | o âmbar **é** a paleta padrão de `--warning-*` no Filament: `oklch(0.769 0.188 70.08)` aparece na resposta de qualquer jeito | `assertSee('--primary-500:'.Color::Violet[500])` — o **par**, que prende a cor à variável certa. Asserção mais forte que a anterior |
| **CT-13** | usuário com papel `master_global`, e `assertDontSee` do e-mail dele | duas coisas erradas. (1) o papel exigia `PapeisSeeder` + `ShieldPermissionsSeeder`, e sem eles o caso morria no arranjo com `RoleDoesNotExist` — defeito de suíte, não de código. (2) com sessão ativa o `layout.simple` renderiza a topbar (`simple.blade.php:30`) e o kit pendura ali o cabeçalho de identidade: **o e-mail aparece, para o dono da sessão, no menu de usuário padrão do Filament**. Não é vazamento, é a tela funcionando | `usuario()` sem papel; asserção sobre 200 + título + link do `/admin`. O oráculo de "nenhuma identidade para quem NÃO está autenticado" migrou para CT-06, onde não há sessão |

O terceiro é o mais instrutivo: a especificação tinha confundido "não vazar identidade de terceiro"
com "não mostrar identidade nenhuma". O `00-requisito.md` fala de anonimato da rota, não de
esconder do usuário o próprio nome.

### Um item da lista negra do ADR-04 ficou sem cenário sentinela

O `04` previa **nove** linhas em CT-12, uma por item da lista negra. A implementada tem **oito**:
`config('app.url')` ficou fora.

Motivo: `app.url` alimenta o gerador de URL do Laravel, e os `href` dos três cartões vêm de
`url()`. Plantar um sentinela ali arrisca uma falha por um caminho que não é o do caso — e
mediria o gerador de URL, não a página. `app.url` continua na lista negra do ADR-04 (a página não
o lê em lugar nenhum); o que não existe é o cenário sentinela dele.

### Duas divergências entre o desenho e a tela

Detalhadas no roteiro "Desenhado × Implementado" do `05`. Nenhuma é defeito:

1. **O badge de versão do cabeçalho não foi implementado.** O desenho o punha ao lado do `<h1>`;
   a versão aparece uma vez, na entrada "Versão do kit" da infolist. Pôr a mesma informação duas
   vezes na mesma tela é ruído, e o cabeçalho nativo do Filament (`$title` + `getSubheading()`)
   não tem encaixe para um badge sem sobrescrever `getHeader()` inteiro.
2. **A composição da segunda seção mudou.** O desenho tinha as três retenções como três entradas;
   a implementação as fundiu numa só ("exceções 14 dias · e-mails 14 dias · importações e
   exportações 30 dias") e usou as duas vagas liberadas para "Versão do kit" e "Idiomas do painel",
   que o desenho não tinha. Continuam 6 entradas; o conteúdo ficou melhor distribuído entre as
   duas seções.

## Notas de Implementação

### Descoberta durante a pesquisa: a `/` era o último consumidor de `@vite` do projeto

`grep -rn '@vite' resources/ app/` devolve **uma** ocorrência: `resources/views/welcome.blade.php:13`.
Nenhum painel do kit chama `viteTheme()` (declarado por escrito no cabeçalho de
`resources/css/filament/cards.css`), e a folha do Filament vem de `FilamentAsset`, não do Vite.

Apagando a welcome, **o repositório fica sem nenhum `@vite` próprio.** Isso tem uma consequência
para `.ai/rules/testes-browser.md`, que afirma como pré-requisito duro que "sem
`public/build/manifest.json` **toda** tela responde `ViteException`". Se a afirmação vinha do
`@vite` da welcome, ela deixa de valer; se vinha de uma blade de vendor, continua valendo.

**Não medido, e por isso não corrigido.** O grep cobriu `resources/` e `app/`, não `vendor/`. Fica
como pergunta aberta para o dono do kit, e o `composer test:browser` continua embutindo o
`npm run build` — que não custa nada e cobre os dois casos.

## Candidatos a Project Rule (decisão do usuário)

A gravação é decisão do usuário e o agente principal a executa. Aqui ficam as **propostas**,
avaliadas nos quatro gates.

### Candidato 1 — rota anônima que exibe `config()` exige cenário de ausência por chave sensível

- **Glob**: `routes/web.php`, `app/Filament/Pages/**`
- **Evidência**: ADR-04 desta wiki + CT-12 do `04` (nove partições, uma por item da lista negra)
- **Gates**: durável ✅ (vale para qualquer tela pública futura) | escopável ✅ | não-inferível ✅
  (um agente competente exibiria o e-mail do admin achando que é "informação útil da instalação") |
  não-redundante ✅ (nenhuma rule atual fala de superfície pública)
- **Nota curta proposta**: *"Tela sem autenticação que exibe `config()` só exibe um subconjunto
  curado, e o teste dela assere a **ausência** de cada chave sensível com valor sentinela plantado.
  Guardar por `! app()->isProduction()` não serve: a proteção passaria a depender de `APP_ENV`
  estar certo, e `.env` mal copiado é o modo de falha mais comum de um skeleton recém-instalado."*

### Candidato 2 — página Filament fora de painel precisa do middleware `panel:{id}`

- **Glob**: `routes/web.php`, `app/Filament/Pages/**`
- **Evidência**: ADR-01, com as três medições (`renderStyles()` sem painel não traz a folha do
  Filament e ignora `KIT_COR_PRIMARIA`) e `file:line` do `SetUpPanel` e do `layout.base`
- **Gates**: durável ✅ | escopável ✅ | não-inferível ✅ (o caminho intuitivo é `@filamentStyles`,
  que falha **em silêncio**: a página renderiza, só sem a folha e com a paleta errada) |
  não-redundante ✅
- **Nota curta proposta**: *"Tela que usa componente do Filament fora de uma rota de painel precisa
  do middleware `panel:{id}` (alias de `SetUpPanel`): é `Panel::boot()` que registra a paleta
  (`FilamentColor::register`) e é o `layout.base` do painel que emite `getTheme()->getHtml()`,
  as fontes e o script de dark mode. `@filamentStyles` sozinho **não** traz a folha nem a cor de
  `KIT_COR_PRIMARIA` — medido. E não tente medir isso por `tinker`: `bootCurrentPanel()` fora de um
  request morre com `Call to a member function parameter() on null`."*

### Candidato 3 — `env()` de STRING sem guarda: vazio engole o default (atualiza `.ai/rules/config.md`)

- **Glob**: `config/**` (rule **existente**, a estender — a skill diz que atualizar é sempre
  preferível a criar outra)
- **Evidência**: QA-02 do `06-relatorio-qa.md`, com a medição e a tabela das seis chaves
- **Gates**: durável ✅ | escopável ✅ | não-inferível ✅ (a rule atual só fala de número, e a leitura
  natural dela é "resolvido pelo `NumeroDoEnv`") | não-redundante ✅ (é o **complemento** do que já
  está lá, não uma repetição)
- **Nota curta proposta**: *"A regra do valor vazio vale para STRING também, e o kit não tem
  equivalente do `NumeroDoEnv` para ela. `env('CHAVE', 'padrão')` com `CHAVE=` no `.env` devolve
  string vazia e o default nunca entra — medido em `KIT_TENANCY_LABEL`. Seis chaves de
  `config/kit.php` estão nessa situação, e três com consequência real:
  `KIT_TENANCY_SLUG` (o segmento do CRUD vira vazio, `/admin/organizacoes` → `/admin/`),
  `KIT_ADMIN_EMAIL` e `KIT_ADMIN_PASSWORD` (o seeder nasce sem credencial de login). Ao ler string
  de env, guarde: `filled($v = env('X')) ? $v : 'padrão'`."*
- **Observação para o dono do kit**: esta rule só vale a pena junto com a correção. Rule que
  descreve um defeito vivo em seis lugares é lembrete, não barreira.

### Observação que NÃO virou candidato

A nota sobre `@vite` (acima) **não** foi proposta como rule. A afirmação de
`.ai/rules/testes-browser.md` pode continuar verdadeira por causa de uma blade de vendor, e isso não
foi medido — o grep cobriu `resources/` e `app/`, não `vendor/`. Corrigir uma rule com base em
inferência é pior que deixá-la desatualizada. O que fica é a pergunta, no lugar certo.

## Retrospectiva

- **Funcionou bem**: medir antes de decidir. As três medições de `FilamentAsset::renderStyles()`
  (com/sem painel, com `KIT_COR_PRIMARIA=Violet`) transformaram o que seria uma escolha de gosto
  entre "Blade solta" e "painel bootado" numa decisão com número — e produziram CT-15, o único
  cenário do conjunto que distingue as duas implementações.
- **Funcionou bem**: ler o cabeçalho de `resources/css/filament/cards.css` **antes** de escolher as
  opções do pacote de cartões. Ele lista por escrito as combinações sem CSS, e o modo de falhar
  delas é silencioso.
- **Faltou no plano**: a distinção entre "não vazar identidade de terceiro" e "não mostrar
  identidade nenhuma". O `04` derivou CT-13 afirmando a ausência do e-mail do próprio usuário
  autenticado, e isso não vem de nenhuma cláusula do `00-requisito.md` — vem de uma leitura apressada
  da premissa de anonimato. O erro só apareceu na execução, e a lição é a mesma do princípio 1 da
  `feature-test-design`: cláusula sobre a **rota** ser anônima não é cláusula sobre a **tela**
  esconder o dono da sessão.
- **Faltou no plano**: prever que asserção de ausência sobre nome de classe CSS é frágil. O
  `.ai/rules/testes.md` já registra a versão irmã disso ("asserção de ausência sobre arquivo
  documentado precisa filtrar comentário"), e o mecanismo é idêntico: o texto que se quer negar
  aparece num lugar que não é o que interessa — lá em comentário, aqui em bloco `<style>`. Custou
  uma rodada de teste vermelho sem defeito.
- **Funcionou bem**: escolher a camada pelo que a asserção afirma. Dezesseis dos dezessete casos
  couberam em HTTP, e o único CT-B carrega o único eixo que só o navegador prova. A tabela de
  "cogitados e cortados" do `05` foi o que impediu o arquivo de crescer para quatro cenários de
  navegador que nada acrescentariam.
