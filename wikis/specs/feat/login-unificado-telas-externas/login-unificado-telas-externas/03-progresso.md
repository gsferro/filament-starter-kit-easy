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

- [x] Tabela do passo 7 do `01` fechada — a linha da redefinição de senha virou ❌→✅ (o e-mail não saía para quem não acessa o painel da rota; ADR-05), 2026-09-07
- [x] Conta indisponível conferida — `resources/views/auth/conta-indisponivel.blade.php:38` tem "Voltar ao login"; CT-63 assere o texto e a cadeia até `/login`, 2026-09-07

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
- [x] `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` → CT-50; 20 URLs literais + inventário — 34/34 (Tela + Documentacao + Inventario), 2026-09-07
- [ ] Lacunas declaradas do `04`: M-A6 (ícone renderizado — tentar `svg()->contents()`), M-A7 (estrutural, só `arch()`)

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — 454 inserções em 19 arquivos, a maior parte docblock;
      nada a cortar que se defendesse: `rotulo()`/`icone()` têm dois consumidores e são o lugar do
      fallback (CT-44 mede neles), `urlDoCadastro()` é a fonte única que a doc aponta, e
      `cadastroTemDestino()` nomeia a regra que CT-58 mata. Três cortes já haviam sido aplicados
      na auditoria do plano (método só para um redirect, dois logs por request), 2026-09-07
- [x] `vendor/bin/pint --dirty --format agent` — passed, 2026-09-07
- [ ] `vendor/bin/filacheck --fix`
- [ ] Testes da feature (lista do `01`)
- [ ] Regressão em série com a chave desligada
- [ ] CT-B de regressão (`LoginUnificadoTest`, `BoasVindasTest`, `ConfiguracoesDoKitTest`)
- [x] `vendor/bin/phpstan analyse app --memory-limit=1G` — 0 erros, 2026-09-07
- [x] Desvios propagados ao `01`/`02`/`04` e às docs, marcados `*(alterado em …)*` — três desvios: reset (ADR-05), guarda de autenticado, arranjo de CT-52, 2026-09-07
- [x] Citações `arquivo:símbolo:linha` reverificadas — **28/28 ok** (12 tinham deslocado com a implementação), 2026-09-07
- [x] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa — CT-43…CT-65 nos dois lados; CT-17/CT-28/CT-40 saíram do teste e ficaram no `04` só como referência de "atualiza", 2026-09-07
- [x] Docs pt/en, CHANGELOG e README reconciliados — inclui a correção do painel do link de reset depois de ADR-05, 2026-09-07
- [ ] `git commit` (individualizados, lista do `01`)

<!-- Cada [x] acima leva " — {evidência}, {data}". -->

## Conformidade com Rules

<!-- Preenchida no step 7. Rules cujos globs casam o diff previsto: -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `auth.md` — redeclarar `$layout` | `app/Filament/Pages/Auth/**` | aplicada | `CadastroUnificado.php:25` e `TelaRecuperarSenhaUnificada.php:26` redeclaram; CT-62 mede o par nas duas |
| `auth.md` — guarda de laço por método sobrescrevível | `app/Filament/Pages/Auth/**` | aplicada | `ehAPaginaUnica()` em `RegistroPorConvite` e `TelaRecuperarSenha`, sobrescrito nas duas subclasses; CT-51/52 e CT-59/60 cobrem os dois sentidos |
| `auth.md` — par `fi-auth-layout` | `app/Filament/Pages/Auth/**` | aplicada | CT-62: `/cadastro` e `/esqueci-minha-senha` com o layout, `/admin` sem ele depois |
| `filament.md` — cartão de painel via `Paineis::cartoes()` filtrado | `app/Filament/**` | aplicada | `EscolhaDePainel::getCards():91-107` continua filtrando por `paineisDe()`; CT-45 mede o filtro com painel novo |
| `providers.md` — rota do kit no provider com `web` | `app/Providers/**` | aplicada | `KitServiceProvider::configureLoginUnificado():527-546` — `/cadastro`, `/esqueci-minha-senha` e o redirect do slug, todos com `web` |
| `pages.md` — segredo em formulário | `app/Filament/Admin/Pages/**` | n.a. | só o `$slug` mudou; nenhum campo tocado, `SegredosDoSettingsTest` verde |
| `app.md` — papel via `ContextoDePapeis` | `app/**` | n.a. | nenhuma atribuição de papel na entrega; o teste de tenancy usa `ContextoDePapeis::em()` para LER |
| `config.md` — env fail-closed | `config/**` | n.a. | só texto de comentário em `config/kit.php`; nenhuma chave nova |
| `testes.md` — helper cruzado em `tests/Pest.php` | `tests/**` | aplicada | `ligarLoginUnificado()`, `organizacaoComRegistro()`, `painelRegistradoEmTeste()` e `corpoDepoisDoTitulo()` movidos; `HelpersDeTesteTest` 1/1 |
| `testes.md` — uma tela aberta não é uma tela que grava | `tests/**` | aplicada | CT-55 grava por componente nas três partições (aberto, convite, organização) |
| `testes.md` — boote o painel `app` com um GET real | `tests/**` | aplicada | CT-61 faz `get('/esqueci-minha-senha')` antes do `Livewire::test()`; sem isso o broker não resolvia |
| `testes-browser.md` — grupo `browser`, `assertPathIs` primeiro | `tests/Browser/**` | aplicada | CT-B01 inalterado na ordem; só a asserção de ausência trocou de texto para `href` |
| `specs.md` — vendor citado com `file:line` | `wikis/specs/**` | aplicada | 28/28 citações ok, 2026-09-07 |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: 1 · **Veredito**: **APROVADO COM DÉBITO** · **Data**: 2026-09-07
- **Relatório**: `06-relatorio-qa.md` — 0 blocker, 0 major em aberto; QA-01 e QA-02 (defeitos
  reais encontrados pelos CT antes do PR) quitados no ciclo e propagados às fontes; QA-03 e
  QA-04 quitados; QA-05 é lacuna já declarada na derivação (M-A6, M-A7).
- **Matriz de rastreabilidade**: 7 `RQ`, todas com passo, CT e código. Nenhuma omissão silenciosa.

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

1. **A recuperação de senha não enviava e-mail para quem não acessa o painel da rota** (medido por
   CT-61). O `request()` do Filament condiciona o envio a
   `canAccessPanel(Filament::getCurrentOrDefaultPanel())`, e sob `panel:app` o corrente é o `app`.
   Corrigido com `TelaRecuperarSenhaUnificada::request()` reposicionando o painel corrente no
   primeiro painel da conta. Propagado: **ADR-05** nova, passo 6 do `01`, linha da redefinição na
   tabela do passo 7, mutante **M-D8** e a linha de CT-61 no `04`, docs pt/en.
2. **Quem já estava autenticado ia para o `/app`** em `/cadastro` e `/esqueci-minha-senha` (o
   `mount()` do vendor manda para `Filament::getUrl()`), onde um `admin` toma 403 — medido por
   CT-54. As duas páginas passaram a seguir a regra de destino do login. Propagado: passo 5 e 6 do
   `01`, consequências da ADR-02.
3. **CT-52 estava mal especificado**: na linha sem convite, `/app/register` recusa (sem token e sem
   cadastro aberto) e o 302 da recusa não distingue "não voltou" de "voltou". A linha ganhou o
   cadastro aberto ligado. Corrigido no `04` (causa (a): CT errado, não implementação).
4. **`tests/Kit/KitUpdateTest.php` corrigido, fora do escopo previsto**: a asserção do CHANGELOG
   recortava só a seção do topo, então qualquer `[Unreleased]` novo reprovava um caso alheio. Passou
   a olhar o arquivo inteiro, sem relaxar nada. Mesmo defeito e mesma correção que a branch
   `feat/entidades-widgets-ordem-e-titulo` aplicou em paralelo.

## Notas de Implementação

- **`Filament::registerPanel()` pela facade não registra dentro do teste.** O `PanelRegistry` é
  singleton e é o mesmo objeto antes e depois, mas `Filament::getPanels()` não vê o painel; pelo
  registry direto (`app(PanelRegistry::class)->register()`) funciona. `painelRegistradoEmTeste()`
  em `tests/Pest.php` usa o registry, com o motivo escrito ao lado. A facade continua sendo o
  caminho de produção.
- **`Login::getPasswordFormComponent()` devolve `Component`, e `hint()` é de `Field`.** O
  `TelaLogin` faz narrow para `TextInput` antes de chamar `hint()` — sem isso o PHPStan level 7
  reprova, e o motivo é real (a assinatura do pai não garante o método).
- **`getRawState()` devolve `array|Arrayable`**: acesso por índice não passa no PHPStan; `collect()`
  resolve em uma linha.
- **`projeto-3` (RQ-05)**: a correção do kit chega por `kit:update`; a instalação ainda precisa (1) ligar "Aceita cadastro público" na organização `padrao` e (2) divulgar `/login?org=padrao` ou `/cadastro?org=padrao`. `Paineis.php` está customizado lá — o `kit:update` vai acusar conflito; com esta entrega a customização deixa de ser necessária.

## Retrospectiva

- (após a implementação)
