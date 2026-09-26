# Plano de Ação — PHPStan no level 8, e as pendências das últimas rodadas

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: ajuste
- **Wiki ancestral**: `wikis/specs/feat/cobertura-de-testes/cobertura-de-testes/` — foi ela que
  mediu os níveis de qualidade adiados (ADR-05 dela, roadmap §8) e adiou o level 8
- **Motivo**: o item 8.1 do roadmap sai do adiamento
- **Toca infra compartilhada?**: **sim** — `phpstan.neon` é gate de `composer test` e do job
  `qualidade` do CI; e o passo 3 muda o comportamento de `DescobreCardsDoPainel`, consumido pelos
  três hubs. A regressão roda contra a suíte inteira (Unit, Feature, Kit, Tenancy)

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | pendência de código | 1, 2, 3, 4 | os 48 erros do level 8 são a pendência de código medida; o ternário morto de `DescobreCardsDoPainel` é achado colateral (passo 3) |
| RQ-02 | pendência de teste | 6 | caixas desatualizadas em duas wikis; nenhum teste vermelho ou pulado sem motivo na suíte |
| RQ-03 | melhor degrau de qualidade | 5 | level 7 → 8 |
| RQ-04 | regra travada | 5 | caso novo em `QualidadeDeCodigoTest` |
| RQ-05 | merge + tag | 8 | não há PR aberto; é o PR desta entrega |
| RQ-06 | causa, não silêncio | 1–4 | 47 de 48 corrigidos no código; 1 é anotação errada do vendor (ADR-03) |

## Objetivo

Subir o gate de análise estática de level 7 para level 8 corrigindo os 48 erros pela causa, travar
o nível por teste, fechar as caixas de verificação que as duas últimas wikis deixaram abertas com
a evidência já existente, e liberar tudo numa release junto com o `[Unreleased]` pendente.

## Contexto

O level 8 do PHPStan acrescenta ao 7 uma única família: **nulidade** — chamar método, ler
propriedade ou passar argumento sobre um valor que o tipo declara poder ser `null`. Os 48 erros
caem em quatro classes, e a classe decide a correção:

| Classe | Erros | O que é | Correção |
|---|---:|---|---|
| **A — nulo impossível, tipo mal dito** | 27 | o valor nunca é nulo no fluxo, mas a expressão usada é tipada `?T` | escrever a mesma coisa com uma expressão que carrega o tipo real. **Sem mudança de comportamento** |
| **B — URL de painel nula** | 7 | `Panel::getLoginUrl()` é `null` em painel sem `->login()`; `Panel::getUrl()` é `null` com tenant por domínio | fallback **que o kit já usa**: `?? url($painel->getPath())` (`app/Support/Paineis.php:url():240`) |
| **C — invariante sem guarda** | 13 | o nulo é impossível por **outra** camada (FK, request de painel), mas nada no código diz isso | exceção de invariante com mensagem, no molde de `CreateRole::afterCreate` |
| **D — anotação errada do vendor** | 1 | o `@return` do `EnsureEmailIsVerified::handle()` do Laravel inclui `null`, que o corpo nunca devolve | guarda de invariante no middleware (ADR-05; era `ignoreErrors`, ADR-03, *alterado em 2026-09-26*) |

A contagem por arquivo é de `phpstan analyse --level=8 --error-format=raw | cut -d: -f1 | sort | uniq -c`
(saída no `03`); a soma confere com `grep -c identifier=` → `48`.

## Análise dos Arquivos Existentes

| Arquivo | Erros | Classe |
|---|---:|---|
| `app/Livewire/AssistenteChatWidget.php` | 10 | A |
| `app/Filament/Concerns/DescobreCardsDoPainel.php` | 9 (3 × 3 hubs) | C |
| `app/Filament/Admin/Resources/Roles/Pages/CreateRole.php` | 3 | A |
| `app/Filament/Admin/Resources/Roles/Pages/EditRole.php` | 3 | A |
| `app/Filament/Pages/Auth/RegistroPorConvite.php` | 5 | 4 B + 1 A |
| `app/Filament/Pages/Auth/TelaBloqueio.php` | 3 | 2 A + 1 B |
| `app/Filament/Pages/Auth/TelaLogin.php` | 1 | A |
| `app/Livewire/DefinirSenhaPorEmail.php` | 2 | A |
| `app/Http/Controllers/Auth/LoginSocialController.php` | 1 | B |
| `app/Notifications/PrimeiroAcessoSocial.php` | 1 | B |
| `app/Models/Convite.php` | 5 | 4 A + 1 C |
| `app/Support/ImportExport/ImportadorDoKit.php` | 3 | C |
| `app/Http/Middleware/ExigirEmailVerificado.php` | 1 | D |
| `database/migrations/2026_08_12_164953_harden_onboarding_progress_scope.php` | 1 | A |
| **total** | **48** | 27 A · 7 B · 13 C · 1 D |


## Autorização, Rotas, Superfície de UI, Ambiente, Eventos, Jobs

Nada novo. Nenhuma policy, gate ou middleware muda de decisão — o `AssistenteChatWidget` mantém o
`abort_unless(auth()->check(), 403)` e só passa a **devolver** o usuário que ele já garantia.
**Sem superfície de UI nova**: login, bloqueio, registro por convite, hubs, papéis e o widget do
assistente não mudam no caminho real.

## Modelo de Execução

Um request, sem trabalho adiado. Nenhuma query nova: as correções da classe A trocam a origem de
um valor já carregado (o `$record` da página de papel em vez do `$data` do formulário).

## Impacto em Features Existentes

- **Hubs** (`DescobreCardsDoPainel`): chamada fora de um request de painel passa a lançar
  `LogicException` em vez de `Error: Call to a member function on null`. Nenhum caminho do kit faz
  essa chamada; teste que monte o hub sem painel corrente passaria a ver a mensagem nova
- **Aceite de convite**: convite cujo papel não carrega passa a lançar `LogicException` com
  mensagem, antes do `assignRole()`. Hoje a FK `convites.role_id` sem cascade (migration
  `2026_08_13_000002`) impede o estado
- **Log dos papéis**: `CreateRole`/`EditRole` passam a logar o nome do papel **gravado** (`$papel`)
  em vez do digitado (`$this->data`) — idênticos, porque `mutateFormDataBeforeCreate` repassa o
  `name` sem transformar

## Rollback

Reverter o commit. Nenhuma migration, nenhum dado.

## Dependências

Nenhuma.

## Riscos

- **Fallback de URL mascarar configuração errada**: o `url($painel->getPath())` leva à raiz do
  painel, cujo middleware de autenticação redireciona ao login que existir. É o mesmo desenho de
  `Paineis::url()` e não inventa destino fora do painel
- **Docs com o nível espalhado**: o "level 7" aparece em 10+ arquivos (lista no passo 7). O teste
  do passo 5 trava o `phpstan.neon`, não a prosa — a prosa é reconciliada no step 7 com `grep`

## Channel de Log da Feature

Não se aplica: nenhum log novo. Os logs existentes de `CreateRole`/`EditRole` (channel
`autenticacao`) mudam só a **origem** do valor, não o formato.

## Estrutura de Implementação

### 1. Classe A — o tipo passa a dizer o que o fluxo já garante

> Skills: `laravel-best-practices`, `ponytail`

| Arquivo:linha | Hoje | Correção |
|---|---|---|
| `TelaLogin.php:207`, `DefinirSenhaPorEmail.php:105,108`, `TelaBloqueio.php:212` | `Filament::getCurrentOrDefaultPanel()` → `?Panel` | `Filament::getCurrentPanel() ?? Filament::getDefaultPanel()` — é o **corpo** do método do vendor (`vendor/filament/filament/src/FilamentManager.php:getCurrentOrDefaultPanel:117-120`), e `getDefaultPanel(): Panel` não é nulo |
| `CreateRole.php:46,67,70`, `EditRole.php:195,245,248` | `$this->data['guard_name']`, `$this->data['name']` com `$data` `?array` | ler do `$papel` já narrado a `SpatieRole`: `$papel->guard_name`, `$papel->name`. O `instanceof` sobe para antes do `firstOrCreate` |
| `AssistenteChatWidget.php` (10) | `auth()->user()` → `?User` depois do `assertContexto()` | `assertContexto()` passa a **devolver** o `User` que ele autorizou (`: User`); `historico()` idem via guarda local. `$this->mensagemPendente` é lido **uma vez** para uma local no início de `responder()` — a chamada de método entre a guarda e o uso desfaz o estreitamento da propriedade |
| `RegistroPorConvite.php:290` | `$this->convite->aceitar…` (`?Convite`) | `$this->convite()->aceitar…` — o accessor da própria classe (`:convite():394`) |
| `Convite.php:320,345,351,356` | `[$validos, $tortos] = $emails->partition(...)` → elementos `?Collection` | `$validos = $emails->filter(...)` e `$tortos = $emails->diff($validos)` — mesmo predicado avaliado uma vez, mesmas chaves |
| migration `…harden_onboarding_progress_scope.php:111` | `$rows->first()` → `?stdClass` | `if ($survivor === null) { continue; }` — o grupo vem de `having count(*) > 1`, nunca é vazio; a guarda não muda o `up()` |

### 2. Classe B — URL de painel nula cai na raiz do painel

| Arquivo:linha | Correção |
|---|---|
| `RegistroPorConvite.php:273,466` | `$painel->getLoginUrl() ?? url($painel->getPath())` |
| `RegistroPorConvite.php:284,305` (`urlDaOrganizacao`) | `$painel->getUrl(...) ?? url($painel->getPath())` |
| `TelaBloqueio.php:215` | `$panel->getLoginUrl() ?? url($panel->getPath())` |
| `LoginSocialController.php:752` (`urlDeLoginDoPainel`) | `?? url($painel->getPath())` |
| `PrimeiroAcessoSocial.php:49` | `?? url($painel->getPath())` |

### 3. Classe C — o invariante ganha guarda com mensagem

- `DescobreCardsDoPainel::cardsDoPainel()` e `::agrupar()`: um método privado
  `painelCorrente(): Panel` que lança `LogicException('Hub de cards fora de um request de painel.')`
  quando `Filament::getCurrentPanel()` é nulo. **Fail loud**: um hub vazio em silêncio seria pior
  que o erro
- **Achado colateral**, mesmo arquivo: `cardDe()` tem
  `->url(is_a($componente, Resource::class, true) ? $componente::getUrl() : $componente::getUrl())`
  — os dois ramos são idênticos. Vira `->url($componente::getUrl())`, e o `use Resource` sai se
  ficar sem uso
- `Convite::atribuirPapel()`: `$papel = $this->papel;` e `LogicException` se não for `Model`,
  antes do `assignRole()`
- `ImportadorDoKit::beforeSave()` e `::exigirPermissaoDoOperador()`: o `$this->record` do
  `Importer` é `?Model` (`vendor/filament/actions/src/Imports/Importer.php:44`); `resolveRecord()`
  roda antes e nunca devolve nulo neste importador (`:96-97`, `?? new $model`). Guarda com
  `LogicException` numa só leitura

### 4. Classe D — a anotação errada do vendor *(alterado em 2026-09-26: RD-06 do step 6.5, Adendo 1 / RQ-09 — ADR-05 substitui a ADR-03)*

- ~~`phpstan.neon` → `ignoreErrors`, escopo só `app/Http/Middleware/ExigirEmailVerificado.php`~~
- `ExigirEmailVerificado::handle()` guarda o retorno de `parent::handle()` e lança
  `LogicException` se não for `Response`. O inventário de `ignoreErrors` fica nas três de antes
- Cobertura: `tests/Kit/VerificacaoDeEmailTest.php` (regressão) e o CT-06 (inventário = 3)

### 4b. Achados do step 6.5 que mudaram código *(alterado em 2026-09-26: Adendo 1)*

- `Paineis::correnteOuPadrao(): Panel` — um lugar só para a expressão que estava colada em quatro
  telas; `TelaBloqueio::getAuthDesignerConfig()` deixa o nullsafe
- `DefinirSenhaPorEmail::enviar()` e `RegistroPorConvite::register()` deixam de passar `null` ao
  `redirect()` (RQ-08, CT-23)
- `PrimeiroAcessoSocial` reaproveita `Paineis::url()` em vez de reimplementá-lo

### 5. O nível sobe e fica travado

- `phpstan.neon`: `level: 8`
- `tests/Kit/QualidadeDeCodigoTest.php`: caso novo — o `phpstan.neon` declara `level` ≥ 8, não
  inclui baseline (`phpstan-baseline`), e toda entrada de `ignoreErrors` tem `path`/`paths`
  (nenhuma exceção global)

### 6. Pendências documentais das wikis anteriores

- `cobertura-de-testes/03-progresso.md`: fechar com evidência as três caixas feitas; a do ponytail
  fica aberta com a recusa já escrita
- `plumb-e-dividas-tecnicas/03-progresso.md`: fechar as duas caixas da Verificação Final com a
  evidência (a suíte de 2026-09-26 e o #108)

### 6b. A reconciliação de testes que a wiki de cobertura mandou fazer e ninguém fez *(alterado em 2026-09-26: achado da varredura, RQ-02)*

A caixa `tests/Kit/CoberturaDeTestesTest.php — CTs conforme o 04` **não** estava só desatualizada.
O `diff` de IDs entre o `04` da cobertura e os testes volta com 22 CTs do `04` sem teste
(`CT-13…32`, `CT-49`, `CT-50` na sombra de IDs coincidentes) e 3 IDs de teste fora do `04`
(`CT-51…53`). A própria seção `## Reconciliação` daquele `04` mandava renumerar o
`KitCoberturaTest` e listava 18 cenários sem teste — e a `## Verificação Final` do `03` fechou
*"IDs ⊆ 04 e vice-versa"* mesmo assim.

- Correções de **especificação** no `04` da cobertura, feitas pela sessão antes do executor:
  CT-07 reescrito (não existe meta padrão), CT-10 linhas `" 78 "` e `1e2` passam a `aceito`
  (normalizam, não abrem a guarda), CT-23 perde a cláusula de gatilho em PR (decisão registrada no
  `ci.yml`)
- Renumeração, movimentação e escrita dos cenários automatizáveis: `fw-executor-ct`, que não toca
  `app/`
- Não automatizáveis na suíte `Kit` (dependem do driver de cobertura ou levam ~52 min): CT-01,
  CT-02, CT-03, CT-25, CT-28 — declarados no `04` da cobertura

### 7. Docs e CHANGELOG

Onde o nível **corrente** é afirmado, passa a 8: `README.md`, `README.en.md`,
`.github/CONTRIBUTING.md`, `docs/{pt,en}/referencia/qualidade-de-codigo.md`,
`site-vitepress/{pt,en}/referencia/qualidade-de-codigo.md`, `wikis/qualidade-de-codigo.md`,
`wikis/README.md`, `rector.php`, docblock de `QualidadeDeCodigoTest`, `wikis/roadmap.md` §8.1.
Onde a frase é **histórica** ("o level 7 já reportou esse erro quando…"), fica. O título h3
congelado em `tests/Kit/fixtures/baseline-readme.php` é tratado pela ADR-04.

### 8. Release

Versão **0.41.0** (minor: gate de qualidade mais estrito é mudança perceptível para quem estende o
kit — código novo que passava no 7 pode reprovar no 8). `config/kit.php`, CHANGELOG, PR, merge,
tag.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** Nenhum helper novo compartilhado entre arquivos: cada correção
> é local. A exceção é `painelCorrente()`, porque o mesmo arquivo lê o painel duas vezes.

## Testes

> Ver `04-casos-de-teste.md`.

## Verificação Final

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` (level 8) — 0 erros
- [ ] `vendor/bin/filacheck --fix`
- [ ] suíte Unit, Feature, Kit, Tenancy `--parallel` contra a baseline (2.993 / 2.990 / 3 pulados)
- [ ] `/code-review high main...HEAD` + passe de eixos (step 6.5)
- [ ] quality gate (step 8)

## Commits

- `:rotating_light: fix(tipos): PHPStan no level 8 — os 48 nulos tratados pela causa`
- `:memo: docs(qualidade): o level 8 nas docs, e as caixas que as wikis deixaram abertas`
- `:bookmark: chore(release): v0.41.0`
