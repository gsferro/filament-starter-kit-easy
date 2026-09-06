# Progresso — Página única de login (`/login`)

> Branch: `feat/login-unificado` · Wiki criada em 2026-09-05 · Implementação: **concluída em 2026-09-05** (PR aberto; quality gate e candidatos a rule ficam para o passo seguinte)

## 1. A chave: `config/kit.php`, `.env.example`, `ConfiguracaoDoLogin::unificado()`

- [x] `config/kit.php` → `login.unificado` com `filter_var(FILTER_VALIDATE_BOOLEAN)` e o bloco de comentário
- [x] `.env.example` → `KIT_LOGIN_UNIFICADO=false` junto de `KIT_LOGIN_RODAPE`
- [x] `ConfiguracaoDoLogin::unificado(): bool`

## 2. Settings: propriedade, mapa, migration, toggle

- [x] `ConfiguracoesDoKit::$login_unificado` (bool)
- [x] `'login_unificado' => 'kit.login.unificado'` no `mapaDeConfiguracao()`
- [x] `database/settings/2026_09_05_100000_add_login_unificado_to_kit_settings.php` (`add` / `delete`)
- [x] `Toggle::make('login_unificado')` na seção "Página única de login", primeira da aba Login

## 3. `Paineis::cartoes()` — os cartões por painel saem da boas-vindas

- [x] `Paineis::cartoes(): array<string, CardItem>` com os três cartões, textos idênticos
- [x] `BoasVindas::getCards()` → `array_values(Paineis::cartoes())`; `cardDoPainel()` removido
- [x] `BoasVindasTest` continua verde

## 4. `DestinoAposLogin`

- [x] `paineisDe(User): list<Panel>` — `canAccessPanel()` por painel
- [x] `urlPara(User): string` — pretendida (prefixo exato de painel acessível) → 1 → escolha; `session()->pull('url.intended')`
- [x] `info`/`warning` no canal `autenticacao` com contexto (`user_id`, `paineis`, `pretendida`, `destino`)

## 5. `RespostaDeLogin` + bind

- [x] `app/Http/Responses/RespostaDeLogin.php` estende `Filament\Auth\Http\Responses\LoginResponse`
- [x] bind do contrato `Filament\Auth\Http\Responses\Contracts\LoginResponse` em `KitServiceProvider`

## 6. `TelaLogin::mount()` e `TelaLoginUnificada`

- [x] **Medição primeiro**: submeter o formulário de `/login` com a chave ligada — o `/livewire/update` tem painel corrente? Resultado registrado em "Notas de Implementação"
- [x] `TelaLogin::mount()` — redireciona para `route('login')` quando unificado e `! routeIs('login')`, via `HttpResponseException`
- [x] `TelaLoginUnificada` — `$layout` redeclarado; `mount()` (desligado → login do painel default; autenticado → destino); `isUserAllowedToAccessPanel()` → algum painel
- [x] `warning` na recusa "nenhum painel acessível"

## 7. `EscolhaDePainel`

- [x] `CardsPage`, layout `simple`, `kit-cards-page`, título "Em qual painel você quer entrar?"
- [x] `mount()`: 1 painel → redireciona; 0 → logout + notificação + `route('login')`; ≥2 → renderiza
- [x] `getCards()` filtra `Paineis::cartoes()` pelos ids de `DestinoAposLogin::paineisDe()`
- [x] `info`/`warning` no canal `autenticacao`

## 8. Rotas em `KitServiceProvider::configureLoginUnificado()`

- [x] `Route::middleware(['web', 'panel:app'])` → `/login` (`login`) e `/login/painel` (`login.painel`, `+ auth`)
- [x] `php artisan route:list --name=login` confirma middleware e ordem (alias `panel` disponível no boot do provider; senão `$this->app->booted()`)

## 9. Login social no modo unificado

- [x] `botoes-sociais.blade.php`: `$painelCorrente = unificado ? null : painel corrente` + frase no comentário (sem `@`)
- [x] `LoginSocialController::urlDoPainel(User)` — unificado → `DestinoAposLogin::urlPara()`; três chamadores atualizados (`retorno`, `confirmarVinculo`, `urlDoPerfil`)

## 10. `kit:update`: `app/Http/Responses` e `database/settings`

- [x] `CAMINHOS_DO_KIT` += `app/Http/Responses`, `database/settings`
- [x] `DIRETORIOS_DE_CODIGO` (teste) += `database/settings`
- [x] `KitUpdateTest` verde (varredura e "só lista caminhos que existem")

## 11. CHANGELOG e documentação

- [x] `CHANGELOG.md` → `[Unreleased]` → Adicionado (página única) e Corrigido (`database/settings` entregue)
- [x] `docs/pt/autenticacao/login-unificado.md` e `docs/en/autenticacao/login-unificado.md` + frase no `index.md` (pt/en)
- [x] `docs/pt|en/recursos/configuracoes-do-kit.md` — linha na aba Login, se a página listar
- [x] `README.md` / `README.en.md` — bullet, se houver lista equivalente
- [x] Testes de documentação verdes

## 12. Testes

- [x] `tests/Kit/LoginUnificadoTest.php` — CT-01…CT-21 + CT-23…CT-30, CT-32 (revisão adversarial); CT-22 em `tests/Kit/KitUpdateTest.php`
- [x] `tests/Browser/LoginUnificadoTest.php` — CT-B01 do `05`

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse --no-progress`
- [x] `php artisan route:list --name=login`
- [x] `php artisan test tests/Kit/LoginUnificadoTest.php --compact`
- [x] Regressão com a chave desligada (lista do PRD)
- [x] `composer test:kit`
- [x] `composer test:browser`
- [x] Docs verdes
- [x] `git commit` (seis commits do plano)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `redirect()` solto não serve em `mount()`; padrão é `HttpResponseException(new RedirectResponse(...))` | `RegistroPorConvite.php:20-21,276,427` — confirmado, com os imports | nenhuma |
| `KitServiceProvider` tem `register()` para o bind | **não tem**; só `boot()` com os `configure*()` (`:55-74`) | passo 5 e 8: bind no `boot()`, dentro de `configureLoginUnificado()` |
| `/livewire/update` não teria painel corrente (risco 4) | `SetUpPanel` é **middleware persistente** do Livewire (`FilamentServiceProvider.php:106-116`) — reaplicado a partir da rota original | risco reescrito; a medição do passo 6 continua |
| `urlDoPainel()` tem dois chamadores | **três**: `:346`, `:562` e `urlDoPerfil()` `:751` | passo 9 corrigido |
| Rota de provider recebe o grupo `web` sozinha | `bootstrap/app.php:9-13` — `withRouting(web: routes/web.php)`; só o arquivo ganha `web` | passo 8 já tinha `middleware(['web', 'panel:app'])` — confirmado necessário |
| `Panel::getUrl()` pode devolver `null` | `HasRoutes.php:170` — `?string`, com tenancy | `?? url(path)` em todos os usos — confirmado |
| `make:settings-migration` existe | `php artisan list`: `make:settings-migration` — confirmado | nenhuma |
| `configuracoes-do-kit.md` tem lista da aba Login | `:18`, tabela por aba | passo 11 aponta a linha |
| README tem lista de recursos de autenticação | `README.md:186-191` ("Registro aberto opcional", "Login social por painel") | passo 11 aponta a linha |
| Docs de autenticação: front matter | `parent: Autenticação`, `grand_parent: Português`, `nav_order` — confirmado em `login-social.md` | nenhuma |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `01` passo 4: `$painel->hasLogin() &&` em `paineisDe()` — os três painéis têm login, e painel sem login com `canAccessPanel()` verdadeiro ainda é destino válido (guard compartilhado) | sim | `01`, passo 4: condição removida |
| 2 | `01` passo 7: `mount()` com dois `if` e uma ternária aninhada para três casos | sim (`shrink`) | `01`, passo 7: `match(count)` + `encerrarSemPainel()` |
| 3 | `01` passo 2: `Section` só para um `Toggle` — cogitado toggle solto no topo da aba | **recusada** | a aba Login é uma lista de `Section`s; um toggle solto quebra o padrão visual da tela por quatro linhas |
| 4 | `01` passo 11: página nova de docs em dois idiomas + índice + configurações + README (6 arquivos) | **recusada** | é a convenção do kit para feature de autenticação (`login-social.md` existe nos dois idiomas); cortar deixaria a chave só no `.env.example` |
| 5 | `04` CT-10 (senha errada) não mata mutante próprio | **recusada** | é o controle de CT-08 — sem ele, `isUserAllowedToAccessPanel()` devolvendo `true` antes da senha passaria; declarado no índice |

## Blockers

- nenhum

## Desvios do Plano

- **Passo 6 — a guarda do laço não é a rota corrente.** O plano previa `! request()->routeIs('login')` em `TelaLogin::mount()`. Em `Livewire::test()` a rota é nula (e em `livewire.update` no navegador também), o que dispararia o redirect na própria página única. Virou um método sobrescrevível: `TelaLogin::ehAPaginaUnica()` devolve `false`; `TelaLoginUnificada` devolve `true`. Independente de rota, testável por componente.
- **Passo 4 — zero painéis não vai mais ao login.** `DestinoAposLogin::urlPara()` com zero painéis devolvia o login do painel default. Com sessão viva (o login social autentica antes de perguntar por painel), a página única redirecionaria de volta para a decisão — **laço**. Achado da revisão adversarial (#21). Agora zero painéis vai para `/login/painel`, e é a `EscolhaDePainel` quem encerra a sessão e volta ao login. O `match` ficou com dois ramos.
- **Passo 12 — nove cenários a mais** (CT-23…CT-30, CT-32), todos da revisão adversarial; CT-31 fundido em CT-03 (o toggle liga **e** desliga).
- **`tests/Kit/KitInfoTest.php` CT-06**: a âncora escrita à mão subiu de 48 para 49 propriedades — é o comportamento desenhado daquele teste (propriedade nova obriga a decisão).

## Notas de Implementação

- **Medição do passo 6 (risco 4 do PRD)**: o formulário de `/login` submete e navega no navegador — CT-B01 verde (9 assertions, 9 s). `SetUpPanel` é middleware persistente do Livewire e o `panel:app` da rota original vale no `/livewire/update`. Sem `Filament::setCurrentPanel()` extra.
- **`route:list --name=login`**: `GET login` → `App\Filament\Pages\Auth\TelaLoginUnificada` (nome `login`), `GET login/painel` → `EscolhaDePainel` (nome `login.painel`); os três `filament.{painel}.auth.login` continuam apontando para `TelaLogin`. O alias `panel` já existe no `boot()` do `KitServiceProvider` — não precisou de `$this->app->booted()`.
- **Auth Designer**: a página única herda a configuração do plugin do painel default (arte à esquerda, 70%, toggle de tema). Screenshot do CT-B confirma. A tela de escolha usa o layout `simple` dos cartões, não o do Auth Designer — o layout do plugin exige página de auth registrada nele (`getAuthDesignerConfig`, regra `.ai/rules/auth.md`).
- **2FA (Breezy) é por painel** (`scopeToPanel`): a sessão de 2FA nasce no painel corrente. CT-27 cria a sessão sob `noPainelBootado('admin')` e prova que `GET /admin` exige o desafio depois da escolha. A escolha em si aparece **antes** do desafio (só lista painéis) — aceito e documentado.
- **CT-17**: `fi-simple-layout-header` aparece na escolha (é o cabeçalho do layout simples com o título), então a asserção de "sem topbar" é `id="fi-main-sidebar"` ausente + `fi-simple-main-ctn` presente, como em `BoasVindasTest`.
- **Regressão com a chave desligada** (14 arquivos, em série): 676/677 — a única falha foi a âncora do `KitInfoTest` (acima). Rodada em série porque a primeira tentativa foi morta por falta de memória: outras sessões rodavam `pest --parallel` na máquina ao mesmo tempo.
- **CT-B01 na primeira rodada**: seletor errado (`fill('email')`; o padrão do kit é `#form\.email`) e depois timeout de 45 s no `assertPathIs('/admin')` com a máquina carregada — o screenshot da falha mostrava o dashboard já carregado. Passou na rodada seguinte em 9 s.
- **Docs**: `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 46/46 com a página nova (`nav_order: 7`).

## Retrospectiva

- **Funcionou**: medir o Panel Switch e o `LoginResponse` no vendor antes de decidir — as duas decisões que dão forma à feature (cartões; resposta no container em vez de middleware) nasceram de `file:line`. A revisão adversarial por sub-agente pagou-se: achou um laço real (#21) que nenhum dos 22 cenários originais pegaria.
- **Faltou no plano**: a guarda do laço por rota corrente — bastava lembrar que `Livewire::test()` não tem rota. E a checagem de que `fi-simple-layout-header` é do layout simples, não do painel.
- **Faltou no plano**: prever a carga da máquina (outras sessões rodando suítes) ao escolher `--parallel` para a regressão; em série foi mais lento, mas terminou.
