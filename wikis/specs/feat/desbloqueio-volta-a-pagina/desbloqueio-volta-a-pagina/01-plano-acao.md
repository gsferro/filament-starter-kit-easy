# Plano de Ação — Desbloqueio da sessão volta para a página em que a pessoa estava

## Natureza da Wiki

**correção** — de um comportamento do pacote `marjose123/filament-lockscreen` que o kit herdou.
Wiki ancestral do bloqueio: nenhuma no `wikis/specs/` (a tela foi vestida pela `identidade-visual-da-organizacao`,
que não toca o destino). Infra compartilhada tocada: o middleware de bloqueio, que roda em toda requisição
autenticada dos três painéis — por isso o quality gate roda regressão sobre `tests/Kit/BloqueioDeSessaoTest.php`
e `tests/Kit/LoginSocialTest.php`-família.

## Cobertura do Requisito

| RQ | Passo | Observação |
|---|---|---|
| RQ-01 | 1, 2 | o ramo de inatividade passa a guardar a página; sem ela, o fallback é o painel (P-02) |
| RQ-02 | 1, 2, 3 | senha (passo 1+2) e provedor social (passo 3, P-01) |
| RQ-03 | 1 | a URL guardada é a da requisição interceptada (`fullUrl`), não `url()->previous()` |
| P-01 | 3 | |
| P-02 | 2 | |
| P-03 | 1 | o ramo "já travada" do pacote é sobrescrito pela mesma regra |
| P-04 | 1 | GET sem `expectsJson()`; Livewire nem chega ao ramo (o pacote passa adiante) |
| P-06 | 3 | `Auth::id()` comparado ao usuário do provedor antes do `Auth::login()` |
| P-07 | 3 | `confirmarVinculo()` descarta `url.intended` numa sessão travada *(alterado em 2026-10-20: step 9)* |
| P-08 | 2 | fallback pela organização da sessão (`raizDoPainel()`) *(alterado em 2026-10-20: step 9)* |
| P-09 | 2 | `forget` de `url.intended` quando o envio termina deslogado *(alterado em 2026-10-20: step 9)* |
| P-05 | 2 | a guarda do `mount()` passa a ler o valor da marca, não a existência da chave |

## Objetivo

Depois do bloqueio por inatividade, desbloquear a sessão (por senha ou pelo provedor social) leva a pessoa
à página que ela pediu quando a barreira a interceptou — o efeito *intended* do Laravel. Hoje ela cai em
`/`, a página pública de boas-vindas.

## Contexto

Leitura do vendor (pedido "analise o pacote base"): a funcionalidade é **parcialmente nativa**.

| Gatilho da trava | O que o pacote grava em `url.intended` | Destino do desbloqueio hoje |
|---|---|---|
| Botão "Bloquear sessão" (POST) | `url()->previous()` — a página do clique (`vendor/marjose123/filament-lockscreen/src/Http/LockscreenSessionController.php:setIntendedUrl:32`) | a página do clique ✅ |
| Inatividade (ramo *Idle*) | **nada** (`vendor/marjose123/filament-lockscreen/src/Http/Middleware/Locker.php:session_last_activity:48-51`) | `/` ❌ |
| Já travada, abre outra URL (ramo *Normal*) | `url()->previous()` (`vendor/marjose123/filament-lockscreen/src/Http/Middleware/Locker.php:setIntendedUrl:32`) — o `Referer` ou a última GET servida, que é a própria tela de bloqueio | tela de bloqueio → raiz do painel ❌ |

O desbloqueio por senha termina em `redirect()->intended()` sem default
(`vendor/marjose123/filament-lockscreen/src/Http/Livewire/LockerScreen.php:intended:115`), e o
default do Laravel é `/` (`vendor/laravel/framework/src/Illuminate/Routing/Redirector.php:intended():95-100`).
`url()->previous()` prefere o header `Referer` e nunca devolve nulo — cai em `/`
(`vendor/laravel/framework/src/Illuminate/Routing/UrlGenerator.php:previous():162-175`), então o `?? ...` do
pacote é código morto. A régua do *intended* que o pedido cita é a de `Redirector::guest()`: GET de rota,
sem `expectsJson()`, grava `fullUrl()` (`vendor/laravel/framework/src/Illuminate/Routing/Redirector.php:guest():71-83`).

## Análise dos Arquivos Existentes

### `vendor/marjose123/filament-lockscreen/src/Lockscreen.php`
O plugin registra `Locker::class` como `authMiddleware` persistente (`vendor/marjose123/filament-lockscreen/src/Lockscreen.php:authMiddleware:42`).
O Laravel resolve middleware pelo container — o mesmo caminho que o kit já usa para trocar `LockerScreen`
por `TelaBloqueio` (`app/Providers/AppServiceProvider.php:bind():23`).

### `app/Filament/Pages/Auth/TelaBloqueio.php`
`mount()` já sobrescreve as duas guardas do pacote (`app/Filament/Pages/Auth/TelaBloqueio.php:mount():286`). Não
sobrescreve `authenticate()` — e não precisa: basta que `url.intended` exista quando ele roda.

### `app/Http/Controllers/Auth/LoginSocialController.php`
`retorno()` destrava a sessão (`app/Http/Controllers/Auth/LoginSocialController.php:lockscreen:361`) mas
redireciona para `urlDoPainel()` (`app/Http/Controllers/Auth/LoginSocialController.php:urlDoPainel:382`). No
login unificado, `urlDoPainel()` já consome `url.intended` validando o painel
(`app/Support/DestinoAposLogin.php:'url.intended':88`); no não unificado, ignora.

## Decisões de Desenho

- **Subclasse do `Locker` ligada por bind**, que chama `parent::handle()` e só acrescenta a página de retorno quando o pacote
  **não** passou a requisição adiante (os dois únicos caminhos assim são os dois redirects para a tela de bloqueio).
  Alternativas descartadas: reescrever o `handle()` inteiro (copia a lógica do vendor e envelhece a cada bump);
  middleware próprio antes do `Locker` (não consegue sobrescrever o `previous()` que o ramo *Normal* grava depois dele).
  Mesma técnica e mesmo provider do `TelaBloqueio`.
- **Detectar a trava por "o `$next` não foi chamado"**, não comparando a URL do redirect: independe do path configurado
  em `Lockscreen::setUrl()` e do painel.
- **Fallback do P-02 no `mount()` e antes do `authenticate()`** (grava `url.intended` = raiz do painel quando vazia), sem reescrever o
  `authenticate()`: o método do pacote tem rate limit e logout forçado; a sobrescrita prepara o destino (a página da sessão vence a lida no
  `mount()`; a raiz marcada em `lockscreen_raiz_gravada` é recalculada), grava `url.intended`, chama `parent::` e limpa as chaves ao
  destravar ou ao deslogar *(alterado em 2026-10-20: QA-02 do quality gate)*
- **Desbloqueio social só honra a página quando quem volta é a mesma conta da sessão travada** — o "Trocar de conta" e
  o botão de outro provedor podem trazer outra pessoa; para ela o destino é o de um login comum (falha fechado).

## Consistência do Ciclo de Vida (Blueprint)

Aplicação do `filament/blueprint` (`planning/overview.md`, *Cross-Requirement Consistency*; `planning/checklist.md`):
a feature não tem model, resource, campo nem widget — as seções de Commands/Models/Resources do Blueprint
não se aplicam; o que se aplica é o caminho de estado compartilhado *travar → guardar → destravar → redirecionar*.

- **Regra canônica**: o destino é `url.intended`, gravado **uma vez por requisição interceptada** com o `fullUrl()`
  de um GET que não espera JSON. Os outros escritores só preservam ou descartam: o ramo JSON do middleware repõe o valor
  anterior; o `mount()` grava a raiz do painel quando a chave está vazia (e a marca); o `authenticate()` regrava o destino
  antes de cada envio; o `retorno()` e o `confirmarVinculo()` a apagam para outra conta ou link. Quem consome (`pull`) é o
  desbloqueio bem-sucedido — senha ou provedor da mesma conta *(alterado em 2026-10-20: QA-02 do quality gate)*
- **Estágios**:

  | # | Transição | `url.intended` depois | Visível |
  |---|---|---|---|
  | 1 | GET X com a sessão ociosa | X | redirect à tela de bloqueio |
  | 2 | GET Y com a sessão já travada | Y (sobrescreve) | redirect à tela de bloqueio |
  | 3 | GET da tela de bloqueio | mantém; se vazia, raiz do painel | formulário |
  | 4 | Livewire / GET JSON com a sessão travada ou ociosa | mantém | — |
  | 5 | senha errada | mantém | erro no campo |
  | 6 | senha certa | consumida | redirect a X/Y |
  | 7 | provedor, mesma conta | consumida | redirect a X/Y (unificado: validado por `DestinoAposLogin`) |
  | 8 | provedor, outra conta | apagada antes do `Auth::login()` (P-06) | destino de login comum *(alterado em 2026-10-20: QA-02 do quality gate)* |

- **Checagem de consistência**: ida e volta — depois do estágio 6, um segundo ciclo sem página guardada termina na raiz
  do painel, não no X do primeiro (a chave foi consumida); 5 seguido de 6 ainda leva a X
- **Verificação**: os estágios 1–8 têm CT no `04` (R1–R6, R9); o discriminante é query string e `Referer` ≠ pedido
- **APIs conferidas**: `search-docs` (laravel/framework 13.x — *intended*; livewire 4.x — `assertRedirect` de ação).
  O pacote do bloqueio não está na Documentation API: fallback no código-fonte instalado (citações do `## Contexto`)

## Autorização

Nenhuma policy, gate ou permissão nova. A página de retorno passa pelo middleware de autenticação e autorização do painel
como qualquer requisição; no login unificado, `DestinoAposLogin::urlPara()` continua descartando URL de painel não acessível.

## Rotas

Nenhuma rota nova nem alterada.

## Superfície de UI

Sem superfície de UI nova. A tela de bloqueio (`/{painel}/screen/lock`) não muda; muda só para onde o sucesso leva.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

Um request, sem trabalho adiado. O custo novo é um `session()->put()` na requisição que trava — nenhum nas outras.

## Impacto em Features Existentes

- Todo GET autenticado dos três painéis passa pelo middleware novo (mesma lógica, um closure a mais)
- Bloqueio manual: o destino continua o do pacote (a página do clique)
- Login social sem sessão travada: inalterado
- Testes: `tests/Kit/BloqueioDeSessaoTest.php` (13 casos) e os de login social

## Rollback

Remover o bind do `Locker` em `AppServiceProvider::register()` — o pacote volta a valer inteiro. Os passos 2 e 3 são
inertes sem o passo 1 (sem `url.intended`, caem no destino de hoje, exceto o fallback do painel).

## Dependências

Nenhuma nova. `marjose123/filament-lockscreen` já instalado.

## Riscos

- Bump do pacote que mude o `handle()` do `Locker` (ex.: passar a chamar `$next` antes de travar) desligaria a detecção em
  silêncio — coberto pelo CT de inatividade, que falha nesse caso
- O ramo *Normal* do pacote grava `previous()` **antes** do nosso `put()`; a ordem (nosso por último) é o que garante P-03

## Channel de Log da Feature

### Verificação de Channel Existente
`autenticacao` existe (`config/logging.php:'autenticacao':132`) e é o canal do login e do desbloqueio social.

### Decisão
Reusar `autenticacao`. O log novo dispara só na requisição que trava (não por request), então não soma ao ruído medido
na nota do canal.

## Estrutura de Implementação

### 1. Middleware que guarda a página ao travar
- **Criar** `app/Http/Middleware/BloquearSessaoComRetorno.php` (`php artisan make:class` não serve a middleware: `php artisan make:middleware BloquearSessaoComRetorno --no-interaction`, depois `extends Locker`)
- Assinatura: `public function handle(Request $request, Closure $next): mixed`
- Lógica: chama `parent::handle($request, $closure)` com um closure que marca `$seguiu = true` e delega a `$next`. Se não seguiu
  **e** `! $request->expectsJson()`, grava
  `redirect()->setIntendedUrl($request->fullUrl())` e loga
- **Log** `Log::channel('autenticacao')->info('[BloquearSessaoComRetorno@handle] Página de retorno guardada ao travar a sessão | painel: {id} - caminho: {path}', ['painel' => …, 'caminho' => $request->path(), 'user_id' => …])` — só o path, nunca a query
- **Bind** em `app/Providers/AppServiceProvider.php:register()`: `$this->app->bind(Locker::class, BloquearSessaoComRetorno::class);`
- Se não seguiu **e** espera JSON: repõe o `url.intended` lido antes do `parent::handle()` (ou o remove) — o ramo da sessão já travada do pacote grava `previous()` antes de devolver *(alterado em 2026-10-20: RD-01/CX-01 do step 9; o `isMethod('GET')` saiu, CR-08: `! $seguiu` já implica GET)*
- RQ-01, RQ-02, RQ-03, P-03, P-04

### 2. Fallback do desbloqueio para a raiz do painel e guarda da sessão destravada
- **Editar** `TelaBloqueio::mount()`: depois das duas guardas, `if (! session()->has('url.intended')) { redirect()->setIntendedUrl(Paineis::url($panel)); }`
- Sem log (caminho comum ao abrir a tela por favorito; não é decisão de fluxo relevante)
- **Editar** a segunda guarda do `mount()` (era `session()->has('lockscreen')` em `main`, hoje `app/Filament/Pages/Auth/TelaBloqueio.php:lockscreen:296`): `session()->has('lockscreen')` → `session('lockscreen') !== true`. O pacote destrava gravando `false` (`vendor/marjose123/filament-lockscreen/src/Http/Livewire/LockerScreen.php:lockscreen:121`), então a chave existe depois do desbloqueio e a guarda de hoje nunca dispara para quem já destravou
- **Propriedade** `#[Locked] public ?string $destinoDoDesbloqueio`, lida uma vez no `mount()` (depois do fallback); o `authenticate()` a repõe em `url.intended` antes de `parent::authenticate()` e remove a chave se o envio terminar deslogado (logout forçado do rate limit, P-09). Fallback = `raizDoPainel()`: no `/app`, `Panel::getUrl($organizacaoDaSessao)` (P-08) *(alterado em 2026-10-20: CR-02, CR-04, CR-05 do step 9)*
- Re-revisão do step 9: no envio, a página da sessão vence a propriedade (outra aba pode ter guardado uma mais nova — RD-03); a raiz que o `mount()` grava é marcada na sessão e recalculada no envio, e só usa a organização da sessão se `canAccessTenant` (RD-04) *(alterado em 2026-10-20)*
- Histórico: a 1ª correção do CT-11 só aplicava o mesmo fallback antes de `parent::authenticate()`: o `intended()` do pacote consome a página, e um segundo envio no mesmo componente cairia em `/` *(alterado em 2026-10-20: CT-11 vermelho por (b) — o fallback só no `mount()` não cobria o segundo envio)*
- RQ-01, P-02, P-05

### 3. Desbloqueio pelo provedor social volta à página
- **Editar** `LoginSocialController::retorno()`: antes de `Auth::login($user)`, `$sessaoTravada = session('lockscreen') === true; $desbloqueio = $sessaoTravada && Auth::id() === $user->getKey();`
- No retorno final: `$novo` continua indo ao perfil; senão, `$desbloqueio ? redirect()->intended($this->urlDoPainel($user)) : redirect()->to($this->urlDoPainel($user))`.
  No unificado, `urlDoPainel()` já consome e valida `url.intended`; no não unificado, `intended()` o consome
- Outra conta numa sessão travada (P-06): `session()->forget('url.intended')` antes do `Auth::login()` — no unificado `urlDoPainel()` consome a chave de qualquer um *(alterado em 2026-10-20: P-06)*
- **Editar** `confirmarVinculo()`: numa sessão travada, `session()->forget('url.intended')` antes do `Auth::login()` — o link do e-mail não é desbloqueio (P-07) *(alterado em 2026-10-20: CR-03/RD-02 do step 9)*
- **Log**: acrescentar `'desbloqueio' => $desbloqueio` ao contexto do `info` de `retorno()` que já existe
- RQ-02, P-01

## Filosofia de Implementação

Ponytail ativo: uma subclasse de ~30 linhas, duas edições cirúrgicas, nenhuma reimplementação do vendor.

## Testes

Ver `04-casos-de-teste.md`. Arquivo: `tests/Kit/BloqueioDeSessaoTest.php` (casos novos ao final).

## Verificação Final

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --compact tests/Kit/BloqueioDeSessaoTest.php` e os de login social
- `composer types:check` (PHPStan nível 8)
- `vendor/bin/filacheck --fix` (o passo 2 toca `app/Filament`)

## Commits

- `:bug: bloqueio: desbloqueio volta à página interceptada pela trava por inatividade`
- `:memo: wiki(desbloqueio-volta-a-pagina): …`
