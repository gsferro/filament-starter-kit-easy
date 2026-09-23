# Plano de Ação — Rodapé coerente entre os painéis e a tela de login

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: **evolução**
- **Wiki ancestral**: a do rodapé dos painéis e a do rodapé do login (ADR-09 de `login-social-google`)
- **Motivo**: os dois rodapés nasceram em features diferentes e nunca foram reconciliados
- **Toca infra compartilhada?**: **sim** — o hook `FOOTER` é registrado **sem `scopes:`** e vale
  para os três painéis e para qualquer painel que o projeto criar depois. **Regressão obrigatória.**

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | nome da aplicação ao lado da versão | 1, 2 | composto em `AssinaturaDoRodape` |
| RQ-02 | `©` antes do nome | 1 | **com o ano corrente** — ver ADR-04, revertida |
| RQ-03 | prefixo `v` no campo | 4 | `->prefix('v')`, só exibição |
| RQ-04 | usar o afixo nativo do Filament | 4 | `HasAffixes::prefix()`, confirmado na fonte instalada |
| RQ-05 | gerir melhor os dados do rodapé | 1 | ponto único de composição, testável sem render |
| RQ-06 | coerência entre painéis e login | 2, 3, **5** | mesma linha nos dois, visibilidade diferente |
| RQ-07 | avaliar a melhor solução | — | três opções levadas ao usuário; ver `00` e ADR-01 |
| RQ-08 | Filament conforme o Blueprint | 1–4 | APIs verificadas na fonte instalada; ver abaixo |
| RQ-09 | sem tag própria | — | **fora de escopo declarado**; acumula com outras evoluções |

## Objetivo

Transformar dois rodapés que nasceram separados numa **assinatura única do sistema**, composta em
um só lugar, exibida nos painéis e nas telas de autenticação — com a **versão** continuando
invisível para quem não autenticou.

## Verificações do Blueprint (RQ-08)

Feitas contra a **fonte instalada**, não contra a memória. Cada uma é premissa do plano.

| # | Premissa | Como foi verificada | Resultado |
|---|---|---|---|
| V1 | `TextInput` aceita `prefix()` | `vendor/filament/forms/src/Components/TextInput.php:26` e `vendor/filament/forms/src/Components/Concerns/HasAffixes.php:prefix:56` | **confirmado** |
| V2 | `prefix()` é **só exibição** | a assinatura recebe rótulo e não muta estado; `inlinePrefix()` (`:119`) é método à parte | **confirmado** — o valor gravado segue sem `v` |
| V3 | quais layouts emitem `FOOTER` | ~~grep em `vendor/filament/filament/resources/views/`~~ → **grep em `vendor/` inteiro** | **CORRIGIDA (QA-02)**: são **três**, não dois — `filament/.../layout/index.blade.php:126`, `filament/.../layout/simple.blade.php:61` **e `caresome/filament-auth-designer/.../layouts/auth.blade.php:63`**. A primeira medição varreu **um só vendor**, e o terceiro é justamente o que estas telas usam |
| V4 | ordem entre hooks no mesmo ponto | `vendor/filament/support/src/View/ViewManager.php:renderHook:74` | **sem escopo renderiza ANTES de com escopo**, independente da ordem de registro; dedupe por `spl_object_id` |
| V5 | onde o `FOOTER` cai na tela de login | ~~`simple.blade.php`~~ → **`auth-designer/.../auth.blade.php`**: `.fi-auth-layout` abre na 28, fecha na **61**, `FOOTER` na **63** | **CORRIGIDA (QA-02)**: **8 de 9 páginas de `app/Filament/Pages/Auth/` redeclaram `$layout` para o do Auth Designer**, que **não tem `<main>`**. A medição original foi feita no layout errado — e foi ela que produziu o **Blocker de geometria** do step 6.5 |
| V6 | o que `getRenderHookScopes()` devolve | `vendor/filament/filament/src/Pages/BasePage.php:getRenderHookScopes:200` | `[static::class]` — a classe **concreta** |
| V7 | classes de página de login em uso | `usingPage(TelaLogin::class)` nos três providers; rota `/login` para `TelaLoginUnificada` em `app/Providers/KitServiceProvider.php:configureLoginUnificado:782` | **duas**, e a segunda **estende** a primeira — por V6, escopar só na mãe **não pega** a filha |

> **V5 inverteu o plano ingênuo — e estava medindo o layout errado.**
>
> A conclusão **sobreviveu**: o `AUTH_LOGIN_FORM_AFTER` sai dentro do cartão do formulário e o
> `FOOTER` fora e abaixo dele, então mover o recado continua sendo necessário para a ordem pedida.
>
> O que não sobreviveu foi a **premissa**. Eu medi o `simple.blade.php` do Filament, e estas telas
> usam o layout do `filament-auth-designer` — que não tem `<main>` e, pior, fixa `.fi-auth-layout`
> em `min-height: 100vh` emitindo o `FOOTER` **depois** de fechá-lo. Foi isso que pôs a assinatura
> **abaixo da dobra** e virou o Blocker do step 6.5, fechado por regra de CSS.
>
> **A lição**: `grep` num vendor só responde sobre aquele vendor. Achado QA-02 do quality gate.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação | Depende de JS? |
|---|---|---|---|---|
| Rodapé de toda tela dos painéis | render hook `FOOTER` | todas | leitura | Não |
| Rodapé das telas de autenticação | render hook `FOOTER` | `/admin/login`, `/app/login`, `/infra/login`, `/login` | leitura | Não |
| Recado do rodapé, na tela de login | `FOOTER` **escopado** | idem | leitura | Não |
| Campo "Versão do sistema" | `TextInput` com afixo | `/admin/configuracoes-da-aplicacao?tab=identidade` | digitar a versão | Não |

~~**Gate de CT-B**: a entrega é composição de texto e ordem de render — nada que **só o navegador
prove**.~~ *(alterado em 2026-09-23: **a própria entrega falseou esta declaração** — QA-15.)*

**Gate de CT-B — corrigido.** O step 6.5 achou um **Blocker que nenhum dos 113 casos de HTML
via**: a assinatura e o recado **abaixo da dobra** em toda tela de autenticação. Geometria só o
navegador prova — `assertVisible` fica verde com o elemento fora do viewport, porque exige
bounding box não-vazio, não estar na tela.

Os cenários de composição vão para o `04`; os de **geometria e estilo computado** vão para
`tests/Browser/RodapeNaDobraTest.php` (`[CT-B01]`, `[CT-B02]`).

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | **1** — o hook roda no render |
| O que é adiado? | nada |
| O que é cacheado? | nada |
| Custo | **zero query** — a assinatura lê só `config()` |

## Autorização

**Nenhuma policy nova.** A única regra de acesso é a que já existe e **se estreita**:
`filament()->auth()->check()` deixa de guardar o bloco inteiro e passa a guardar **só a versão**.

> `filament()->auth()->check()` e **não** `@auth`: o `@auth` consulta o guard **default**, e o kit
> nasce sem `->authGuard()` nos painéis — mas o projeto que nascer dele pode declarar um. O modo
> de falhar seria **a versão aparecendo para visitante, com o diff parecendo correto**.

## Rotas, Variáveis de Ambiente, Eventos, Jobs

Nada novo em nenhum dos quatro. A assinatura lê `app.name`, `app.version`, `kit.version` e
`kit.exibir_versao`, todos existentes.

## Channel de Log da Feature

**Nenhum.** A entrega compõe string a partir de `config()` e renderiza. Log aqui seria ruído por
request.

## Impacto em Features Existentes

- **`FOOTER` sem `scopes:`** — a assinatura passa a aparecer em **toda tela de autenticação**
  (login, registro, recuperação, dois fatores, bloqueio), onde hoje não aparece nada. É o
  comportamento pedido, e é mudança visível
- **Tela de login** — o recado sai de dentro do cartão e passa para baixo dele. **Mudança
  visual**, com captura de arte a conferir
- **`tests/Kit/VersaoNoRodapeTest.php`** — é a regressão desta superfície e ganha os casos novos
- **Registro e recuperação** — continuam **sem** recado: o escopo é das páginas de login. Só a
  assinatura chega nelas

## Rollback

Reverter os commits. Nada de schema, migration ou dado.

## Dependências

Nenhuma nova em runtime. `filament/blueprint` foi instalado como **dev** (`composer bp:on`) para
atender RQ-08 e sai com `composer bp:off`.

## Riscos

- **A versão vazar para visitante** — é o risco que a feature toca de frente, porque a guarda
  deixa de ser do bloco e passa a ser da parte. Mitigação: caso que afirma sobre a tela de login
  **anônima**, não sobre o helper
- **O escopo não pegar o login unificado** (V6/V7) — mitigação: caso que percorre as duas classes
- **Utilitária Tailwind sem `viteTheme()`** — classe emitida na blade não existe na folha
  compilada e o estilo sai vazio **com o teste verde**. Mitigação: reusar `.kit-versao`
  (`resources/css/filament/kit.css:188`)

## Estrutura de Implementação

### 1. `App\Support\AssinaturaDoRodape` — o ponto único (RQ-01, RQ-02, RQ-05)

> Skills: `laravel-best-practices`

- **Path**: `app/Support/AssinaturaDoRodape.php` (`php artisan make:class`)
- **Vizinhas de referência**: `ConfiguracaoDoLogin`, `DensidadeDoLayout`, `CorPrimaria` — mesma
  família, mesmo papel de ponto único de leitura
- **API**: `public static function partes(bool $comVersao): array`
- **Composição**, nesta ordem, cada parte entrando só quando `filled()`:
  1. `© {ano corrente} {config('app.name')}` — ano de `now()->year`, no render (ADR-04)
  2. `v{config('app.version')}` — **só quando** `$comVersao`
  3. `kit {config('kit.version')}` — só quando `$comVersao` **e** `config('kit.exibir_versao')`
- **A classe NÃO decide a audiência.** Recebe `$comVersao` e compõe. A regra de quem vê o quê fica
  na blade, onde a audiência é conhecida — ver ADR-02

### 2. A blade da assinatura (RQ-01, RQ-02, RQ-06)

> Skills: `filament-development`

- **Path**: `resources/views/filament/versao-do-kit.blade.php` → **`assinatura-do-rodape.blade.php`**
  (`git mv`); atualizar a referência em
  `app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraVersaoNoRodape:129`
- **A guarda se estreita**: o `@if` deixa de envolver o bloco e passa a decidir só o argumento —
  `AssinaturaDoRodape::partes(comVersao: filament()->auth()->check())`
- **Saída escapada** — nunca HTML cru. `app.name` vem de campo editável e esta blade passa a
  renderizar em tela **pública**. Ver ADR-05
- Separador ` · `, como hoje. Classe `.kit-versao`, que já existe

### 3. O recado do login muda de hook (RQ-06)

> Skills: `filament-development`

- **Path**: `app/Providers/KitServiceProvider.php:configureTelaDeLogin:706`
- Sai de `AUTH_LOGIN_FORM_AFTER` e vai para `FOOTER` **com `scopes:`**, listando **as duas**
  classes (V6/V7): `TelaLogin::class` e `TelaLoginUnificada::class`
- **Por que isso resolve a ordem**: por V4 o hook **sem escopo** renderiza antes do **com escopo**,
  independentemente da ordem de registro. E por V5 os dois saem fora do cartão, empilhados
- **A decisão original é preservada**: o recado continua sem aparecer nas outras telas. O que muda
  é o mecanismo — de "outro hook" para "mesmo hook, escopado"
- Os **botões sociais continuam** em `AUTH_LOGIN_FORM_AFTER`: pertencem ao formulário, não ao rodapé

### 4. O prefixo `v` no campo (RQ-03, RQ-04)

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`, `TextInput::make('versao_do_sistema')`
- `->prefix('v')` (V1, V2). **Não** `prefixIcon()` — é ícone, não texto
- O valor gravado continua sem `v` e a blade continua compondo o `v`. É isso que evita a duplicação
- **Dado sujo preexistente**: quem já digitou `v1.2.3` passa a ver `vv1.2.3`. O prefixo **torna o
  erro visível**; não o corrige. Sem migração — ver ADR-06

### 5. A regra de CSS que faz o rodapé caber na dobra (RQ-06)

> Skills: `tailwindcss-development` · **Passo acrescentado em 2026-09-23** — ele **não estava no
> plano**: nasceu do Blocker RD-01 do step 6.5, e o quality gate cobrou o registro (QA-12).

- **Path**: `resources/css/filament/kit.css` **e** a cópia publicada `public/css/kit/kit-correcoes.css`
- **O problema**: o layout do Auth Designer fixa `.fi-auth-layout` em `min-height: 100vh` e emite o
  `FOOTER` **depois** de fechá-lo. O contêiner sozinho consome a dobra, e o rodapé sobra para fora
- **A regra**: `body.fi-body:has(> .fi-auth-layout)` vira coluna e o layout cede a altura
- **Escopada por `:has()`** para valer só nas telas de autenticação — ver ADR-07
- **Guardada por** `[CT-B01]` e `[CT-B02]`

### 6. Documentação e CHANGELOG

- `docs/pt/` e `docs/en/` — a página de configurações descreve o campo e o rodapé
- `CHANGELOG.md`, seção `[Unreleased]` (RQ-09: **não** sai tag)

## Testes

> Ver `04-casos-de-teste.md`.

## Verificação Final

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix` — o diff toca `app/Filament`
- [ ] `vendor/bin/pest tests/Kit/VersaoNoRodapeTest.php --compact`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel` — regressão obrigatória
- [ ] `vendor/bin/phpstan analyse`
- [ ] Captura de arte reconferida — o recado mudou de lugar na tela de login
- [ ] `/code-review high main...HEAD` + passe de eixos (step 6.5)
- [ ] `feature-quality-gate` (step 8)
- [ ] `composer bp:off` — o Blueprint é dev e não fica
