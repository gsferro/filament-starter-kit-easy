# Decisões Arquiteturais — Página única de login (`/login`)

## ADR-01: A escolha de painel é uma página de cartões, não o Panel Switch

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

O requisito pede o Panel Switch "em modo full da página", com a alternativa explícita: "se o pacote não atender, use o card que é usado na tela de boas-vindas". Medido no vendor (`bezhansalleh/filament-panel-switch/src/PanelSwitch.php:83-160,238-270` e `resources/views/panel-switch-menu.blade.php`):

- o componente é um **gatilho** na topbar (`renderHook()` default `GLOBAL_SEARCH_BEFORE` / `USER_MENU_BEFORE`) que abre um **dropdown** (`simple()`) ou um **modal** (`slideOver()`, `modalWidth()`); não existe modo página;
- `getPanels()` e `isAbleToSwitchPanels()` exigem `auth()->user()` e resolvem o painel **corrente** — o componente pressupõe estar dentro de um painel aberto;
- os rótulos e ícones vêm do `configureUsing()` global (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:332-345`) e são os da topbar (`config('app.name')`, "Administração", "Infraestrutura").

"Página inteira" com ele seria copiar a blade do pacote para fora do contexto para o qual foi escrita, ou abrir o modal por JavaScript numa página vazia.

### Decisão

`EscolhaDePainel` é uma `CardsPage` (`harvirsidhu/filament-cards`), layout `simple`, com os **mesmos cartões da boas-vindas** (`Paineis::cartoes()`), filtrados por `User::canAccessPanel()`. O Panel Switch da topbar continua como está, dentro dos painéis.

### Alternativas Consideradas

1. **Embutir a blade `panel-switch-menu` numa página simples** — descartada: reproduz um componente de topbar fora da topbar, com `$currentPanel` sem sentido (a pessoa ainda não está em painel nenhum) e rótulos de outro contexto.
2. **Abrir o modal do Panel Switch automaticamente numa página em branco** — descartada: depende de JavaScript para uma decisão que é do servidor, e a página em branco por trás é a "tela vazia" que o requisito quer evitar.
3. **Listar os painéis como links simples (sem cartões)** — descartada: os cartões já existem, já têm CSS publicado (`public/css/kit/kit-cards.css`) e já dizem o que cada painel é. Reusar é a escada.

### Consequências

- **Positivas**: zero componente novo; a escolha tem a mesma cara da boas-vindas; um cartão por painel **acessível**, filtrado antes de montar — o que a regra `.ai/rules/filament.md` ("CardItem do hub…") exige na essência: cartão só para destino que a pessoa pode abrir.
- **Negativas**: os textos da escolha são os da boas-vindas ("Painel do negócio"…), não os do Panel Switch (`config('app.name')`…). Duas fontes de rótulo para os painéis já existiam; esta ADR não cria a terceira.
- **Riscos**: `CardItem` não checa acesso; a filtragem é responsabilidade de `EscolhaDePainel::getCards()`. Coberto pelo `04`.

### Referências

- `app/Filament/Pages/BoasVindas.php` (molde); `app/Support/Paineis.php` (`cartoes()`)
- `vendor/bezhansalleh/filament-panel-switch/src/PanelSwitch.php:83-160,203-270`

---

## ADR-02: A chave é `kit.login.unificado`, lida por request, e por isso vai para o Settings

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

`.ai/rules/settings.md`: chave lida no **boot** (para montar painel, registrar rota, decidir middleware) não pode ir para a tela; chave lida **por request** pode. `.ai/rules/config.md`: uma pergunta, uma dona; interruptor que muda fluxo de acesso falha fechado.

### Decisão

- `config/kit.php` → `login.unificado`, `filter_var(env('KIT_LOGIN_UNIFICADO', false), FILTER_VALIDATE_BOOLEAN)`.
- Dona: `ConfiguracaoDoLogin::unificado()`. Todo consumidor pergunta a ela.
- Settings: propriedade `login_unificado` + linha no `mapaDeConfiguracao()` + migration + toggle na aba Login.
- **Nada** desta feature lê a chave no boot: as rotas são registradas sempre; o `LoginResponse` é bound sempre e decide dentro do `toResponse()`; o `mount()` das telas decide por request.

### Alternativas Consideradas

1. **Só `.env`** — descartada: a tela de configurações é onde as outras chaves de login moram, e a regra permite porque a leitura é por request. Toggle que **faz efeito na hora** é o que a regra pede.
2. **Registrar as rotas `/login` só com a chave ligada** — descartada: reintroduz leitura no boot e quebra `route('login')`/`route:cache` (o `routes/web.php` documenta o mesmo para o login social).

### Consequências

- **Positivas**: liga e desliga sem deploy; `kit:info` e a tela mostram a chave sozinhos (derivam do mapa).
- **Negativas**: mais uma linha em três lugares (propriedade, mapa, migration) — o custo padrão do kit, guardado por `ConfiguracoesDoKitTest`.

---

## ADR-03: Sem middleware novo — a tela de login redireciona, o `LoginResponse` decide o destino

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

O requisito sugere "provavelmente será necessário uma middleware". Medido:

- **todos** os caminhos que levam à tela de login de um painel passam pela **mesma classe** `TelaLogin` (é a `usingPage()` do Auth Designer nos três painéis): `Authenticate` do Filament (`redirectTo()` → `Filament::getLoginUrl()`), `TelaBloqueio::sairPara($panel->getLoginUrl())`, `RegistroPorConvite` (3 pontos), `LoginSocialController::urlDeLoginDoPainel()`, `LogoutResponse`, `PasswordResetResponse`;
- o destino após login é `app(LoginResponse::class)` (`vendor/filament/filament/src/Auth/Pages/Login.php:169`), um contrato do container;
- a tela de escolha precisa de sessão autenticada — o middleware `auth` do Laravel já é isso.

### Decisão

1. `TelaLogin::mount()` redireciona para `route('login')` quando a chave está ligada e `$this->ehAPaginaUnica()` é falso (a página única sobrescreve para `true`). Um ponto, todos os fluxos. **Alterado em 2026-09-05**: a primeira versão usava `request()->routeIs('login')`, que é nulo em `Livewire::test()` e no `livewire.update` — dispararia o redirect na própria página única.
2. `RespostaDeLogin` (estende a `LoginResponse` do Filament) decide o destino pela chave; desligada, `parent::toResponse()`.
3. `/login/painel` leva `auth`. Anônimo é mandado ao login pelo Laravel.
4. `EscolhaDePainel::mount()` cobre os dois casos que não devem ver a tela: um painel (redireciona) e nenhum (encerra a sessão e volta ao login).

### Alternativas Consideradas

1. **Middleware global interceptando `filament.*.auth.login`** — descartada: faz o mesmo que o `mount()` com um arquivo a mais e sem o contexto da tela (a página única **é** uma `TelaLogin`; a guarda contra laço fica natural na própria classe).
2. **Sobrescrever `redirectTo()` do `Authenticate`** (uma subclasse nos três `authMiddleware`) — descartada como **obrigatória**; fica como melhoria opcional. Cobre só o anônimo; lock screen, registro, logout e reset continuariam passando pela tela do painel. O hop extra (`/admin/login` → `/login`) é um redirect 302 de custo zero.
3. **Middleware "escolha pendente" que força a tela de escolha em toda request até a pessoa escolher** — descartada: a escolha é uma decisão **no momento do login**, não um estado da sessão. Quem entrou num painel e digita a URL de outro que também acessa deve entrar — é o que o Panel Switch da topbar faz. Um estado "pendente" na sessão criaria o caso "escolheu `admin`, abriu `/app`, foi mandado de volta para escolher".

### Consequências

- **Positivas**: nenhum arquivo em `app/Http/Middleware`; o fluxo é legível em duas classes; RQ-07 sai de graça (tudo que chama `getLoginUrl()` cai no `mount()`).
- **Negativas**: com a chave ligada, o anônimo faz dois redirects (`/admin/x` → `/admin/login` → `/login`). Aceito.
- **Riscos**: laço `TelaLogin ↔ TelaLoginUnificada`. A condição `! $this->ehAPaginaUnica()` e a página única desligada redirecionando para o login do painel default (que desligado não redireciona) fecham os dois sentidos; o `04` tem os dois casos.

---

## ADR-04: O login social segue a mesma regra de destino, e os botões deixam de carregar `painel`

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

A wiki `login-social-por-painel` fez o painel de origem viajar na sessão e decidir o destino (ADR-03 e ADR-06 de lá). Na página única não há painel de origem: o painel corrente é `app` só porque o `panel:app` bootou o layout.

### Decisão

- `botoes-sociais.blade.php`: `$painelCorrente` é `null` quando unificado. `null` já significa "provedor habilitado, em qualquer painel" em `ConfiguracaoDoLogin::disponiveis()` e "sem painel na sessão" no controller.
- `LoginSocialController::urlDoPainel(User)`: unificado → `DestinoAposLogin::urlPara($user)`; senão, o de hoje (ADR-06 de `login-social-por-painel` continua valendo no modo por painel).

### Alternativas Consideradas

1. **Manter `painel=app` no botão** — descartada: filtraria os provedores pela lista do `/app` e mandaria todo mundo para o `/app` depois do login social, contradizendo RQ-03/RQ-04 para quem entra pelo provedor.
2. **Unificar só o login por senha** — descartada: "todas as telas de login serão unificadas"; o botão social está na mesma tela.

### Consequências

- **Positivas**: uma regra de destino para senha e provedor; o `LoginSocialPorPainelTest` continua verde com a chave desligada.
- **Negativas**: com a chave ligada, a lista "provedor X só no painel Y" perde efeito na página única (não há painel). Documentado nos docs.

---

## ADR-05: As rotas novas ficam no `KitServiceProvider`, não em `routes/web.php`

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

`routes/web.php` tem as rotas públicas do kit (boas-vindas, login social) e **não está** em `KitUpdate::CAMINHOS_DO_KIT` — é arquivo do usuário, como `composer.json`: sobrescrevê-lo apagaria as rotas do projeto. Consequência já existente: as rotas do kit nele nunca chegam a quem atualiza (lacuna registrada no `00`, fora desta entrega). `app/Providers` **está** na lista.

### Decisão

`KitServiceProvider::configureLoginUnificado()` registra `/login` e `/login/painel` com `Route::middleware(['web', 'panel:app'])`, e faz o bind do `LoginResponse`.

### Alternativas Consideradas

1. **`routes/web.php`, como as irmãs** — descartada: a feature não chegaria a quem atualiza; a convenção local perde para a entrega.
2. **Um arquivo `routes/kit.php` carregado pelo provider** — descartada por ora: dois arquivos onde um método basta; se as rotas do kit em `web.php` migrarem um dia, aí sim um arquivo próprio faz sentido (wiki própria).
3. **Rota dentro do painel default (`$panel->routes()`)** — descartada: ficaria sob `/app/login`, que já existe e é exatamente o que se quer substituir.

### Consequências

- **Positivas**: entrega garantida; `route:cache` funciona (rota de provider é cacheável).
- **Negativas**: `web` explícito no grupo — rota de provider não ganha o grupo `web` sozinha (Laravel 11+, `withRouting(web: …)` só cobre `routes/web.php`). E o alias `panel` precisa existir quando o provider registrar — medir com `route:list`; se a ordem falhar, `$this->app->booted()`.

---

## ADR-06: A URL pretendida vence a escolha — quando é de um painel acessível

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

`redirect()->intended()` é o comportamento do Filament e o que a pessoa pediu ao colar `/admin/users`. Mas a pretendida pode ser de um painel que ela **não** acessa.

### Decisão

`DestinoAposLogin::urlPara()` honra `url.intended` apenas se o **path** dela começar com `/{path do painel}` (igualdade ou `/{path}/…`) de algum painel acessível. Fora disso, a pretendida é descartada e vale a regra 1 → direto, ≥2 → escolha.

### Alternativas Consideradas

1. **Sempre a escolha, ignorando a pretendida** — descartada: faz a pessoa que pediu `/admin/users` clicar num cartão para chegar onde já tinha pedido.
2. **Sempre a pretendida** — descartada: mandaria para um painel inacessível, onde o `Authenticate` responderia 403 — a tela em branco que o requisito quer evitar.

### Consequências

- **Positivas**: o caso mais comum (link direto para uma tela) continua com um passo só.
- **Negativas**: `/administracao` (um path hipotético) não pode ser confundido com `/admin` — a comparação exige a barra ou a igualdade exata. O `04` tem o caso.

---

## ADR-07: `isUserAllowedToAccessPanel()` sobrescrito na página única — "algum painel", não "o corrente"

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

`Login::authenticate()` do Filament recusa quem não acessa `Filament::getCurrentOrDefaultPanel()` — duas vezes: dentro do `Timebox` e no `attemptWhen()` (`Login.php:107-114,163-166`). Na página única o painel corrente é `app`. Um `admin` sem papel no `/app` seria recusado com "credenciais inválidas".

### Decisão

`TelaLoginUnificada::isUserAllowedToAccessPanel(Authenticatable $user)` devolve `DestinoAposLogin::paineisDe($user) !== []`. O método é `protected` no Filament, feito para isso; a recusa explicada de `TelaLogin::authenticate()` (conta indisponível) continua por cima, porque ela embrulha o `parent::authenticate()`.

### Alternativas Consideradas

1. **`Filament::setCurrentPanel()` para o primeiro painel acessível antes de autenticar** — descartada: exige achar o usuário pelo e-mail **antes** da validação de senha (um lookup a mais fora do `Timebox`) e troca o painel corrente no meio do request.
2. **Reescrever `authenticate()` inteiro** — descartada: ~60 linhas copiadas do Filament, com MFA e `Timebox`, para mudar uma condição.

### Consequências

- **Positivas**: uma linha muda o critério; MFA, rate limit, `Timebox` e eventos continuam os do Filament.
- **Negativas**: `paineisDe()` chama `canAccessPanel()` para os três painéis, e o model loga `warning` por painel negado — até dois avisos por login de quem tem um painel só. O Panel Switch já faz isso a cada request (`getPanels()`); o ruído não é novo. Registrado; não filtrado.

---

## ADR-08: A página única roda no contexto do painel default (`panel:app`)

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

Uma `Page` do Filament fora de rota de painel precisa de um painel corrente para tema, cores, layout do Auth Designer e `Filament::auth()`. A boas-vindas resolveu com o middleware `panel:app` (alias de `SetUpPanel`), e o docblock dela mede por que `@filamentStyles` sozinho não basta.

### Decisão

`/login` e `/login/painel` usam `panel:app`. É um atalho deliberado (registrado no docblock da página): o painel default empresta o contexto; nenhum "painel de autenticação" é criado.

### Alternativas Consideradas

1. **Um quarto painel `auth` só com login** — descartada: um `PanelProvider` inteiro, permissões do Shield, mais um lugar para registrar plugins (`.ai/rules/providers-filament.md` — plugin que resolve o painel corrente precisa estar em **todos**), para servir uma tela.
2. **Blade solta com `@filamentStyles`** — descartada pela medição da boas-vindas: sem o boot do painel não vem a folha, a paleta nem o script de tema.

### Consequências

- **Positivas**: molde já provado; zero configuração.
- **Negativas**: o `authentication_log` nasceria carimbado com `app` (o painel corrente) para todo login pela página única. **Resolvido pela ADR-09** (adendo 1): o registro nasce sem painel e recebe o painel de entrada.
- **Riscos**: a hidratação do Livewire no `/livewire/update` fora da rota original (risco 4 do PRD). Medir no primeiro item do passo 6.

---

## ADR-09: O painel do log de acesso é o de entrada — marca de sessão na página única, carimbo no destino

**Status**: Aceita
**Data**: 2026-09-05 (adendo 1 do `00`)

### Contexto

`KitServiceProvider::registrarPainelNoLogDeAcesso()` carimba `authentication_log.painel` no `creating` com `Filament::getCurrentPanel()`. Na página única o painel corrente é o default emprestado pelo `panel:app` — todo login por senha viraria `app` no breakdown de acessos por painel (insights, stat de logins). O painel de entrada só é conhecido **depois** do `Login` event: no destino direto, na pretendida ou no clique do cartão.

### Decisão

1. `TelaLoginUnificada::mount()` grava a marca `login_unificado.em_curso` na sessão; o `creating` devolve cedo (painel nulo) quando ela existe; `TelaLogin::mount()` das telas de painel a apaga.
2. `DestinoAposLogin::carimbarAcesso()` apaga a marca e atualiza o último registro sem painel do usuário. Chamado em `urlPara()` (destino direto ou pretendida) e em `entrarEm()` (cartão).
3. O cartão da escolha aponta para `GET /login/painel/{painel}` (`EntrarNoPainelController`), que valida acesso, carimba e redireciona; painel inacessível volta à escolha sem carimbo.

### Alternativas Consideradas

1. **Listener do `Login` event decidindo o painel** — descartada: no momento do evento o destino não existe (pode ser a escolha).
2. **Middleware persistente nos três painéis carimbando na primeira request** — descartada: mais um middleware em três `authMiddleware`, rodando em toda request para agir uma vez; e o clique do cartão já é um ponto natural de servidor.
3. **Cartão como link direto para o painel e carimbo pela pretendida** — descartada: o clique não passa pelo servidor do kit; o registro ficaria nulo para quem escolhe.
4. **Carimbar o primeiro painel acessível no login** — descartada: mente sobre o que a pessoa fez quando ela tem mais de um.

### Emendas da auditoria Blueprint (2026-09-05)

- **Medium** — o login social pela página única não enviava `painel=`, e a barreira `painelAutorizado()` do `LoginSocialController` só valia na ida com `?painel=`: GitHub restrito ao `/infra` entregava a pessoa no `/admin`. `DestinoAposLogin::restringirAosPaineisAutorizados(ProvedorSocial)` grava na sessão os painéis em que o provedor vale (lista vazia = todos, nada gravado); `paineisDe()` intersecta; o carimbo apaga a marca. Sem painel autorizado acessível → escolha → sessão encerrada (CT-41).
- **Low** — `painelDe()` comparava só o host do `parse_url()`, que aceita `https://evil\@host/admin`, `javascript://host/…` e `//evil`. Agora aceita só URL absoluta que começa por `url('/')` mais barra, ou path relativo que começa por `/` e não por `//` nem barra invertida (CT-15, 4 linhas novas).
- **Low** — `{painel}` da rota do cartão sem constraint: `->where('painel', '[a-z0-9_-]{1,32}')` (CT-42).
- **Low** — `/login/painel` e `/login/painel/{painel}` funcionavam com a chave desligada: redirecionam ao painel default (CT-26; a premissa do `00` foi revertida).
- **Low** — `EscolhaDePainel` com um painel só devolvia a URL sem carimbar: passou a usar `entrarEm()`.
- **Low** — a marca `SESSAO_EM_CURSO` era honrada no `creating` mesmo com a chave desligada: agora exige `unificado()`.
- **Info aceitos**: `paineisDe()` chamado mais de uma vez por login (até 4 `warning` de `canAccessPanel` — o Panel Switch já faz o mesmo a cada request); `GET` com efeito colateral no carimbo (afeta só o registro da própria pessoa; um `<form>` com CSRF é a saída se incomodar); pretendida `/app/{tenant alheio}` cai no 404 do tenant (só a própria pessoa).

### Consequências

- **Positivas**: os widgets de acessos por painel continuam corretos com a chave ligada; quem entra pela tela do painel não muda nada.
- **Negativas**: um registro pode ficar sem painel (fechou a aba na escolha) — é um login que não entrou em painel nenhum; os widgets já toleram nulo. Uma rota a mais (`login.painel.entrar`).
- **Riscos**: `UPDATE … LIMIT 1` ordenado por `login_at` pega o último acesso sem painel do usuário — dois logins simultâneos do mesmo usuário em navegadores diferentes poderiam trocar o carimbo entre si. Aceito: mesma pessoa, mesma contagem por painel no agregado.

### Referências

- `app/Support/DestinoAposLogin.php` (`SESSAO_EM_CURSO`, `entrarEm()`, `carimbarAcesso()`)
- `app/Http/Controllers/Auth/EntrarNoPainelController.php`; `app/Providers/KitServiceProvider.php` (`registrarPainelNoLogDeAcesso()`)
- `tests/Kit/LoginUnificadoTest.php` CT-33…CT-37; `tests/Kit/CarimboDePainelNoAcessoTest.php`

