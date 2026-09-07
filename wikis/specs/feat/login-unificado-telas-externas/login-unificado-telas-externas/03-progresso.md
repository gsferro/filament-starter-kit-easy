# Progresso — Telas externas com o login unificado

> Branch: `feat/login-unificado-telas-externas` · Wiki criada em 2026-09-06 · Plano aprovado pelo solicitante em 2026-09-07 · Implementação: **em andamento**

## 1. `Paineis` vira a fonte única de rótulo, ícone e cartão por painel

- [x] `Paineis::rotulos()`, `icones()`, `rotulo(Panel)`, `icone(Panel)` com os fallbacks do Panel Switch — `app/Support/Paineis.php`, PHPStan level 7 verde, 2026-09-07
- [x] `Paineis::cartoes()` itera `Filament::getPanels()`; mapa local de cor/descrição dos três do kit — CT-46 verde (BoasVindas 39/39), 2026-09-07
- [x] Import `Heroicon` removido; `Str` importado — Pint e PHPStan verdes, 2026-09-07

## 2. Panel Switch e boas-vindas leem de `Paineis`

- [x] `configuraPanelSwitch()` usa `Paineis::rotulos()`/`icones()` — `ConfiguraFilamentGlobal.php`, 2026-09-07
- [x] `BoasVindas::getSubheading()` sem "Três" — CT-46 assere a ausência, 39/39, 2026-09-07
- [x] Docblocks de `EscolhaDePainel` e `Paineis::cartoes()` sem "três" — 2026-09-07

## 3. Slug `configuracoes-da-aplicacao`

- [x] `$slug` na Page + docblocks — CT-48 e CT-49 escritos; ConfiguracoesDoKitTela+Documentacao+Inventario 34/34, 2026-09-07
- [x] `Route::redirect` 301 do slug antigo no `KitServiceProvider` — CT-49 (anônimo e autenticado), 2026-09-07
- [x] Textos em `app/`, `config/kit.php`, `resources/views`, `.ai/rules/pages.md` — 40 arquivos, zero ocorrências restantes fora de CHANGELOG e wikis, 2026-09-07
- [x] `README.md`, `README.en.md`, `docs/{pt,en}` — URL nova; nome do arquivo de doc mantido, 2026-09-07
- [x] Testes com URL literal + inventário de telas + `ConfiguracoesDoKitDocumentacaoTest` (CT-50 com a ausência da URL antiga) — 2026-09-07

## 4. `RegistroAberto::urlDoCadastro()` e o link de registro da tela de login

- [x] `RegistroAberto::urlDoCadastro(?string $org)` — `app/Support/RegistroAberto.php`, 2026-09-07
- [x] `TelaLogin::getSubheading()` exige organização resolvível com tenancy; `cadastroTemDestino()`, `orgDoPedido()` — CT-57 e CT-58 escritos, 2026-09-07
- [x] `TelaLogin::registerAction()` com a URL do kit e `?org=` — 2026-09-07

## 5. `/cadastro` — a página única de registro

- [x] `CadastroUnificado` (`$layout`, `ehAPaginaUnica(): true`, `mount()` com chave desligada → rota do painel) — 2026-09-07
- [x] `RegistroPorConvite::mount()` com a guarda (query preservada) e `ehAPaginaUnica(): false` — 2026-09-07
- [x] Rota `cadastro` no `KitServiceProvider` — 2026-09-07

## 6. `/esqueci-minha-senha` — a página única do pedido de reset

- [x] `TelaRecuperarSenhaUnificada` — 2026-09-07
- [x] `TelaRecuperarSenha::mount()` com a guarda e `ehAPaginaUnica(): false`; docblock atualizado — 2026-09-07
- [x] `TelaLogin::getPasswordFormComponent()` com `urlDeRecuperacaoDeSenha()` — narrow para `TextInput` (o pai devolve `Component`), PHPStan verde, 2026-09-07
- [x] Rota `esqueci-minha-senha` no `KitServiceProvider` — 2026-09-07

## 7. Revisão das telas externas com a chave ligada

- [ ] Tabela do passo 7 do `01` fechada com o resultado dos CT (cada linha ✅/⚠️ com evidência)
- [ ] Conta indisponível conferida (link de volta para `/login`)

## 8. Docs, CHANGELOG, `kit:update` e wiki ancestral

- [x] `docs/{pt,en}/autenticacao/login-unificado.md` — "Painel novo depois da instalação", "As outras telas sem prefixo de painel" e "O que continua por painel" reescritas, 2026-09-07
- [x] `docs/{pt,en}/autenticacao/registro-aberto.md` — link do login com `?org=`; caso medido, 2026-09-07
- [x] `CHANGELOG.md` `[Unreleased]` — Adicionado, Corrigido e Alterado, 2026-09-07
- [x] `KitUpdate::CAMINHOS_DO_KIT` conferido — `app/Filament`, `app/Support` e `app/Providers` já cobrem os arquivos novos; nada a acrescentar, 2026-09-07
- [x] Nota "refinada por" na ADR-01 da ancestral, e `*(alterado em …)*` em CT-17, CT-28 e CT-40 do `04` dela — 2026-09-07

## Testes

- [ ] `tests/Kit/LoginUnificadoTest.php` — CT-43…CT-65 sem tenancy (23 cenários, 10 regras, 49 mutantes; CT-17→CT-43, CT-05/CT-28→CT-51, CT-40→CT-59)
- [ ] `tests/Tenancy/LoginUnificadoTenancyTest.php` (novo) — células com tenancy da tabela de decisão (CT-52/53/55/56/58); helpers `ligarLoginUnificado()` e `organizacaoComRegistro()` migram para `tests/Pest.php` (`.ai/rules/testes.md`)
- [ ] `tests/Kit/BoasVindasTest.php:56` → CT-46; `tests/Browser/BoasVindasTest.php:54-56` e `tests/Browser/LoginUnificadoTest.php:33-38` — rótulo do `app` e ausência por `href`
- [ ] `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` → CT-50; 20 URLs literais + `tests/Pest.php:270` (forçados por `InventarioDeTelasTest`)
- [ ] Lacunas declaradas do `04`: M-A6 (ícone renderizado — tentar `svg()->contents()`), M-A7 (estrutural, só `arch()`)

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] Testes da feature (lista do `01`)
- [ ] Regressão em série com a chave desligada
- [ ] CT-B de regressão (`LoginUnificadoTest`, `BoasVindasTest`, `ConfiguracoesDoKitTest`)
- [ ] `vendor/bin/phpstan analyse --memory-limit=1G`
- [ ] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*`
- [ ] Citações `arquivo:símbolo:linha` reverificadas — {n}/{n} ok
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit` (individualizados, lista do `01`)

<!-- Cada [x] acima leva " — {evidência}, {data}". -->

## Conformidade com Rules

<!-- Preenchida no step 7. Rules cujos globs casam o diff previsto: -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `auth.md` — redeclarar `$layout` | `app/Filament/Pages/Auth/**` | | |
| `auth.md` — guarda de laço por método sobrescrevível | `app/Filament/Pages/Auth/**` | | |
| `auth.md` — par `fi-auth-layout` | `app/Filament/Pages/Auth/**` | | |
| `filament.md` — cartão de painel via `Paineis::cartoes()` filtrado | `app/Filament/**` | | |
| `providers.md` — rota do kit no provider com `web` | `app/Providers/**` | | |
| `pages.md` — segredo em formulário | `app/Filament/Admin/Pages/**` | | (só `$slug`; n.a. previsto) |
| `app.md` — papel via `ContextoDePapeis` | `app/**` | | n.a. previsto |
| `config.md` — env fail-closed | `config/**` | | só texto de comentário; n.a. previsto |
| `testes.md` — helper cruzado em `tests/Pest.php` | `tests/**` | | |
| `testes-browser.md` — grupo `browser`, `assertPathIs` primeiro | `tests/Browser/**` | | |
| `specs.md` — vendor citado com `file:line` | `wikis/specs/**` | aplicada (pré) | 21/21 citações ok, 2026-09-06 |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: — · **Veredito**: — · **Data**: —
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação

### Pesquisa (step 3) — sub-agentes em paralelo

| Agente | Escopo | Achado que mudou o plano |
|---|---|---|
| Panel Switch × escolha | vendor 3.1.0, `Paineis`, providers, testes de rótulo | não há `excludes()`/`visible()`; fallbacks `ucfirst(id)` / `heroicon-o-square-2-stack`; rótulo/ícone dos cartões divergem do Panel Switch → ADR-01 |
| Registro, reset, Auth Designer | 7 telas; vendor `Login.php`, `Register.php`, `RequestPasswordReset.php`; testes | os links vêm de `filament()->get*Url()` do painel corrente; nenhuma sobrescrita no kit → ADR-02; CT-40 da ancestral consagra `/app/password-reset/request` (inverte) |
| `projeto-3` (somente leitura) | `.env`, `settings`, painéis, diff com o kit, log, rotas | 5 painéis; `KIT_TENANCY=true`; `login_unificado=true`, `registro_habilitado=true` no Settings; tenant `padrao` sem cadastro público; 5 recusas no log; nenhum arquivo do kit divergente → ADR-04; RQ-05 fechada |
| Slug `configuracoes-do-kit` | 92 arquivos | slug vem do nome da classe; 20 URLs literais em testes; tag de auditoria e nome do arquivo de doc são outra coisa → ADR-03 |

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| citações `configuraPanelSwitch():332-345`, `cartoes():269-273`, `recusar():404-431` | métodos começam em `:324`, `:256`, `:375` | 01/02 corrigidos; conferência mecânica 21/21 ok |
| `CardItem::icon()` aceita string | `HasIcon::icon(string \| BackedEnum \| Htmlable \| Closure \| null)` | confirmado (01, Contexto) |
| `HasAuth::getRegistrationUrl(array $parameters = [])` aceita query | `vendor/filament/filament/src/Panel/Concerns/HasAuth.php:getRegistrationUrl():392` e `getRequestPasswordResetUrl():404` aceitam `array $parameters` e devolvem `?string` (null sem a feature no painel) | passo 4 e 6 do `01`: cast `(string)` e fallback para `route()` quando o painel não tem a feature |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `configureRedirectsLegados()` novo só para um `Route::redirect` | sim — uma linha no grupo `web` existente | `01`, passo 3 |
| 2 | `info` no canal `autenticacao` quando o link de cadastro é ocultado (por render da tela) | sim — cortado; a doc explica | `01`, passo 4 |
| 3 | `info` no redirect `/app/register` → `/cadastro` (por acesso) | sim — cortado; redirect determinístico | `01`, passo 5 |
| 4 | Quatro métodos em `Paineis` (`rotulos/icones/rotulo/icone`) | recusada: o Panel Switch consome arrays, os cartões consomem por painel com fallback; `rotulo()`/`icone()` são one-liners e CT-44 mede a paridade neles | `01`, passo 1 |
| 5 | 23 CT para a entrega (`04`) | recusada: 5 são atualizações de teste existente; as tabelas de decisão (CT-51/53/58) cobrem 24 células com 3 `it()` via dataset; a revisão adversarial já fundiu o que sobrava | `04` |

## Blockers

- nenhum

## Desvios do Plano

- nenhum ainda

## Notas de Implementação

- **`projeto-3` (RQ-05)**: a correção do kit chega por `kit:update`; a instalação ainda precisa (1) ligar "Aceita cadastro público" na organização `padrao` e (2) divulgar `/login?org=padrao` ou `/cadastro?org=padrao`. `Paineis.php` está customizado lá — o `kit:update` vai acusar conflito; com esta entrega a customização deixa de ser necessária.

## Retrospectiva

- (após a implementação)
