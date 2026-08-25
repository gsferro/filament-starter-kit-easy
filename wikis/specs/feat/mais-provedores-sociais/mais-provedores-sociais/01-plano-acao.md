# Plano de Ação — W8: mais provedores de login social

> Requisito: `00-requisito.md`. Decisões: `02-decisoes-arquiteturais.md`.

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/login-social-google/login-social-google/`
- **Motivo**: aquela entrega atendeu a primeira metade do mesmo requisito (o Google) e registrou,
  no ADR-10, o critério para reabrir a decisão de abstrair. Esta entrega atende a segunda metade e
  faz a extração.
- **Toca infra compartilhada?**: **sim** — `app/Settings/ConfiguracoesDoKit.php` (aba Login),
  `config/services.php`, `config/kit.php`, `routes/web.php` e o render hook da tela de login em
  `KitServiceProvider`. Três outras branches tocam o mesmo arquivo de Settings em métodos
  diferentes.

> Tipo `evolução` + infra compartilhada = **regressão obrigatória** contra os CT/CT-B da wiki
> ancestral (`tests/Kit/LoginSocialGoogleTest.php`, `tests/Tenancy/LoginSocialGoogleTenancyTest.php`,
> `tests/Browser/LoginSocialGoogleTest.php`) e contra os da wiki `settings-do-kit`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | GitHub, Facebook, LinkedIn, X, Discord | 1, 2, 3, 4, 5, 6, 7 | **parcial**: GitHub, LinkedIn e X entram. **Facebook fora** (ADR-05, sem sinal de verificação). **Discord fora** (ADR-04, sem driver no Socialite; exigiria dependência). Justificado, não omitido |
| RQ-02 | lista aberta, sem reescrever os anteriores | 1, 3, 4, 6, 7 | o enum é o ponto de extensão; nenhum arquivo de lógica muda ao acrescentar caso |
| RQ-03 | ícone da marca em cada botão | 6 | SVG inline, uma partial por provedor (ADR-08) |
| RQ-04 | botão só com todos os dados preenchidos | 2, 4 | `ConfiguracaoDoLogin::disponivel()` confere interruptor + 3 chaves |
| RQ-05 | ligar a opção abre os campos | 7 | `Section` por provedor + `visible()` no toggle `->live()` (ADR-07) |
| RQ-06 | campos para todos os provedores, não só o Google | 5, 7 | 9 propriedades novas de Settings + migration |
| RQ-07 | default `false` no registro aberto | — | já é o estado do kit; coberto por regressão em `tests/Kit/RegistroAbertoTest.php:140` (`it('nasce com as tres opcoes de registro desligadas')`). A redação original apontava para um `CT-R1` que não existe em wiki nenhuma — achado da derivação dos casos |
| RQ-08 | default `false` em **cada** provedor | 3, 5 | `filter_var(env(...), false)` por provedor; migration semeia do `.env` |
| RQ-09 | ligado reflete em tudo que vem | 2, 4, 6 | o predicado governa a **rota** (404), não só o botão; e liga um provedor sem ligar os outros |
| RQ-10 | muito bem documentado nos READMEs | 10 | `README.md` + `README.en.md` + `.env.example` |

## Objetivo

Transformar o login social do kit — hoje um provedor com código próprio — em quatro provedores que
compartilham um único caminho, sem perder nenhuma das barreiras que a entrega do Google levantou:
o interruptor derruba a **rota** e não só o botão; o botão exige interruptor **e** credenciais
completas; o login **autentica** quem já tem conta e só **cria** conta com o registro aberto
ligado; e o e-mail precisa estar **provadamente verificado no provedor**.

O eixo do desenho é a última barreira, porque é a única que muda de provedor para provedor — e
muda tanto que dois dos cinco pedidos não conseguem atendê-la e ficam fora, com a recusa
registrada em ADR.

## Contexto

A v0.19.2/0.19.3 entregou o Google deliberadamente sem abstração, e escreveu o critério de
reabertura no ADR-10: extrair com dois casos na mão, não com um. Ao investigar os quatro
candidatos no `vendor/laravel/socialite/`, o que apareceu foi (a) o eixo real de variação, que não
era o que se adivinharia; (b) que Discord não é driver do Socialite, ao contrário da premissa de
escopo; (c) que Facebook não tem como provar verificação de e-mail; e (d) um defeito **anterior**
no Settings, em que o `client_secret` do Google é cifrado na gravação e não é decifrado na leitura
(ADR-06).

O (d) bloqueia a entrega: o requisito manda imitar o campo do Google, e imitá-lo literalmente
replicaria o defeito em três segredos novos.

## Análise dos Arquivos Existentes

### `app/Support/ConfiguracaoDoLogin.php`

Ponto único de leitura. `PROVEDOR_GOOGLE` (constante) e `googleDisponivel()` viram
`disponivel(ProvedorSocial)` + `disponiveis()`. `rodapeDoLogin()` e `registroAberto()` ficam
intactos.

### `app/Http/Controllers/Auth/LoginComGoogleController.php`

As seis barreiras do `retorno()` estão certas e ficam. O que muda: o provedor entra por parâmetro
de rota, a verificação de e-mail delega ao enum, e as mensagens de log e de tela passam a nomear o
provedor. **Renomeado** para `LoginSocialController` — o nome antigo mentiria servindo quatro.

> Consequência conferida: dois casos existentes assertam o prefixo literal
> `[LoginComGoogleController@retorno]` e o texto "Autenticado pelo Google"
> (`tests/Kit/LoginSocialGoogleTest.php:731,758`). Serão atualizados para o novo formato — é
> mudança de texto de log, não remoção de caso.

### `routes/web.php`

O grupo `auth/google` vira `auth/{provedor}` com nome `auth.social.*` (ADR-02). Conferido: nenhum
teste usa o nome da rota; todos usam a URL literal, que **não muda**.

### `resources/views/filament/auth/botao-google.blade.php`

Substituído por `botoes-sociais.blade.php` + quatro partials de ícone (ADR-08). É o **único**
consumidor de `route('auth.google.redirect')` no repositório.

### `app/Providers/KitServiceProvider.php` → `configureTelaDeLogin()`

Uma linha: o nome da view do primeiro render hook. A registração global (sem escopo de painel)
está correta e fica — é ela que cobre os três painéis com uma chamada.

### `app/Settings/ConfiguracoesDoKit.php`

9 propriedades novas, 9 linhas novas no `mapaDeConfiguracao()`, e `encrypted()` corrigido de 1 para
4 segredos (ADR-06). **Alteração mínima e localizada em métodos diferentes dos que as outras três
branches tocam** — `encrypted()` e o bloco de login social do mapa.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

`abaLogin()` reescrita com um `foreach` sobre o enum (ADR-07). `mutateFormDataBeforeFill()` zera os
quatro segredos (era 2). `segredoDoGoogleGuardado()` generaliza para
`segredoGuardadoDe(ProvedorSocial)`.

### `config/services.php` e `config/kit.php`

Três blocos novos em cada. O `redirect` continua **caminho relativo** de propósito: o Socialite o
resolve para URL absoluta, então acompanha o `APP_URL` de cada ambiente.

## Autorização

- **Policies / Gates**: nenhum novo. A tela de Settings continua sob a permission
  `View:ConfiguracoesDoKit` via `ExigePermissaoDaTela`.
- **Middleware**: `throttle:10,1` no grupo de rotas (compartilhado, ADR-02).
- **A barreira de autorização desta feature não é middleware, é o `abort_unless()` do
  controller** — por provedor, nas duas pontas.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/auth/{provedor}/redirect` | `auth.social.redirect` | `throttle:10,1` |
| GET | `/auth/{provedor}/callback` | `auth.social.callback` | `throttle:10,1` |

`{provedor}` é `ProvedorSocial` (implicit enum binding) → **404 automático** fora do enum. As URIs
resultantes: `/auth/google/*`, `/auth/github/*`, `/auth/linkedin-openid/*`, `/auth/x/*`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação | Depende de JS? |
|---|---|---|---|---|
| Botões de login social | Blade via render hook `AUTH_LOGIN_FORM_AFTER` | `/app/login`, `/admin/login`, `/infra/login` | clica no botão do provedor | Não |
| Aba "Login" das Configurações do Kit | Filament `SettingsPage` | `/admin/configuracoes-do-kit` | liga o toggle e **os campos aparecem**; preenche credenciais; salva | **Sim** — o `->live()` do toggle e o `visible()` dos campos são Livewire |

**Gate de CT-B**: o que só o navegador prova aqui é (a) o botão renderizado com o SVG da marca e o
`href` correto na tela de login real, e (b) que ligar o toggle **de fato abre** os campos — o
`->live()` é um round-trip de Livewire. O resto (predicado, 404, callback, gravação, segredo fora
do HTML) é teste de componente/HTTP e pertence ao `04`.

**Gate de tela de escrita**: `/admin/configuracoes-do-kit` é tela de escrita → o `04` precisa de
cenário de **gravação por componente Livewire**, incluindo a sobrevivência de cada segredo em
branco.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_SOCIALITE_GOOGLE` | `false` | interruptor do Google (já existe) |
| `KIT_SOCIALITE_GITHUB` | `false` | interruptor do GitHub |
| `KIT_SOCIALITE_LINKEDIN` | `false` | interruptor do LinkedIn (driver `linkedin-openid`) |
| `KIT_SOCIALITE_X` | `false` | interruptor do X |
| `GITHUB_CLIENT_ID` / `GITHUB_CLIENT_SECRET` | vazio | credenciais do OAuth App do GitHub |
| `LINKEDIN_CLIENT_ID` / `LINKEDIN_CLIENT_SECRET` | vazio | credenciais do app da LinkedIn |
| `X_CLIENT_ID` / `X_CLIENT_SECRET` | vazio | credenciais do app do X |

Todos os interruptores com `filter_var(..., FILTER_VALIDATE_BOOLEAN)` — falha **FECHADA**, porque
abrem superfície pública de OAuth (`.ai/rules/config.md`).

## Eventos / Listeners / Observers

Nenhum novo. `Auth::login()` continua disparando `Illuminate\Auth\Events\Login`, que o
`rappasoft/laravel-authentication-log` escuta para gravar a trilha de acesso — comportamento que
esta feature não escreve e que um caso de teste guarda.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Login social com Google**: caminho reescrito. Regressão obrigatória nos três arquivos de teste
  da wiki ancestral. As URLs não mudam; o nome da rota e o prefixo do log mudam.
- **Settings do kit**: `encrypted()` muda de comportamento para `login_google_client_secret`
  (ADR-06). Instalação que já salvou o segredo pela tela tem o valor **normalizado** pela
  migration. Regressão nos casos de `settings-do-kit`.
- **`feat/verificacao-de-email-editavel`** (branch paralela): toca a **aba Registro** do mesmo
  arquivo de Settings e o `AppPanelProvider`. Conflito localizado em métodos diferentes.
- **`feat/upload-limite-e-tipos`** (branch paralela): toca `config/kit.php` e um helper de upload
  no mesmo Settings. Conflito localizado.
- **Telas de login dos três painéis**: o render hook passa a renderizar outra view. Se a view
  quebrar, **as três telas de login caem** — é o risco de maior alcance da entrega.

## Rollback

- **Migration down**: `deleteIfExists` das 9 propriedades novas. A normalização do segredo do
  Google **não** é revertida (o valor cifrado continua legível enquanto `encrypted()` o listar);
  reverter `encrypted()` sem reverter o dado voltaria ao defeito, então o `down()` documenta que a
  ordem correta é reverter o código e depois o dado.
- **Feature flag**: os quatro interruptores nascem `false`; desligar todos volta a tela de login ao
  estado sem botão nenhum.
- **Reversão de dados**: nenhuma tabela de negócio é tocada.

## Dependências

**Nenhuma.** `laravel/socialite ^5.30` já instalado cobre os três provedores novos. Discord ficaria
fora justamente por exigir dependência (ADR-04).

## Riscos

- **O render hook derruba as três telas de login se o blade quebrar** — mitigação: o caso de teste
  que visita as três telas já existe na wiki ancestral e roda na regressão; e `.ai/rules/views.md`
  (diretiva dentro de comentário) é a armadilha exata deste tipo de blade.
- **A normalização do segredo no ADR-06 mexe em dado gravado** — mitigação: `null` passa reto, e o
  caminho de decisão é `decrypt` com `try/catch`, nunca heurística sobre o formato da string.
- **Conflito de merge com as três branches paralelas** — mitigação: alteração mínima, em métodos
  distintos, seguindo o contrato dos três lugares do docblock de `ConfiguracoesDoKit`.
- **A chamada extra do GitHub sai para a rede em produção** — mitigação: `Http::fake()` em teste,
  falha fechada com o motivo no log.

## Channel de Log da Feature

**Channel existente: `autenticacao`.** Conferido em `config/logging.php` — é o channel que a wiki
ancestral criou e que o `LoginComGoogleController` já usa. Nenhum channel novo: a feature é a
mesma superfície, com mais provedores. Um channel por provedor fragmentaria a auditoria de
autenticação em quatro arquivos, que é o oposto do que o channel serve.

> Nota do `config/logging.php` respeitada: nada é logado na **abertura** de tela (um `info` por
> request custou 1,1 MB/dia medido). Os logs desta feature são só nas rotas de OAuth.

## Estrutura de Implementação

### 1. O enum `ProvedorSocial`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/ProvedorSocial.php` (novo)
- `enum ProvedorSocial: string` com `Google = 'google'`, `Github = 'github'`,
  `LinkedIn = 'linkedin-openid'`, `X = 'x'`.
- `rotulo(): string` — `match($this)` → `'Google'`, `'GitHub'`, `'LinkedIn'`, `'X'`.
- `icone(): string` — `match($this)` → `'google'`, `'github'`, `'linkedin'`, `'x'` (nome da partial;
  sem hífen de propósito, para o nome de arquivo ser simples).
- `propriedadeDeSettings(string $sufixo): string` — `'login_'.str_replace('-', '_', $this->value).'_'.$sufixo`.
- `emailVerificado(AbstractUser $doProvedor): bool` — o `match` do ADR-03, falha fechada em todos
  os ramos. O ramo do GitHub chama o método privado abaixo.
- `private function naoDesmentidoNoBruto(AbstractUser $doProvedor, array $chaves): bool` — a
  guarda dos ramos de presença: um `email_verified` **falso** no bruto vence a presença do e-mail.
  Só chave booleana entra na lista (`confirmed_email` do X guarda o ENDEREÇO, e `filter_var` de um
  e-mail é `false`).
- **Sem chamada HTTP nenhuma.** O plano original previa que o ramo do GitHub refizesse a consulta
  a `/user/emails`; a revisão do `vendor/` mostrou que era redundante — ver ADR-03, item 2, e a
  seção "Desvios do Plano" do `03-progresso.md`.
- **Logs**: nenhum no enum. Quem loga a recusa por e-mail não verificado é o controller, uma vez,
  com o `motivo` e o e-mail mascarado.

### 2. `ConfiguracaoDoLogin` generalizado

> Skills: `laravel-best-practices`

- **Path**: `app/Support/ConfiguracaoDoLogin.php`
- Remove `PROVEDOR_GOOGLE` e `googleDisponivel()`.
- `public static function disponivel(ProvedorSocial $provedor): bool` — as mesmas duas condições,
  agora por provedor: `config("kit.login.{$provedor->value}.habilitado")` **e** as três chaves de
  `config("services.{$provedor->value}")` com `filled()`. As três, `client_secret` incluído.
- `public static function disponiveis(): array` — `array_values(array_filter(ProvedorSocial::cases(), self::disponivel(...)))`.
- `rodapeDoLogin()` e `registroAberto()` **inalterados**.
- Atualizar o docblock: ele fala de "um provedor" e do ADR-10; passa a apontar para os ADRs desta
  wiki.
- **Logs**: nenhum. É predicado lido por request; log aqui seria um por render de tela de login.

### 3. `config/services.php` e `config/kit.php`

> Skills: nenhuma — ler `.ai/rules/config.md` antes

- **`config/services.php`**: três blocos novos, no formato que o Socialite exige.
  - `'github' => ['client_id' => env('GITHUB_CLIENT_ID'), 'client_secret' => env('GITHUB_CLIENT_SECRET'), 'redirect' => '/auth/github/callback']`
  - `'linkedin-openid' => [... env('LINKEDIN_CLIENT_ID'), env('LINKEDIN_CLIENT_SECRET'), '/auth/linkedin-openid/callback']`
  - `'x' => [... env('X_CLIENT_ID'), env('X_CLIENT_SECRET'), '/auth/x/callback']`
  - Comentário: nenhuma chave `scopes` — os defaults do provider bastam, e um `setScopes` errado
    tiraria `user:email` do GitHub em silêncio (ADR-09, item 3).
- **`config/kit.php`** → bloco `login`: três chaves `habilitado` novas, cada uma com
  `filter_var(env('KIT_SOCIALITE_...', false), FILTER_VALIDATE_BOOLEAN)`. Comentário explicando que
  a falha é **fechada** e que o default é `false` por provedor (RQ-08).
- **Logs**: n/a.

### 4. O controller `LoginSocialController`

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php` (renomeado de
  `LoginComGoogleController.php`)
- `redirecionar(ProvedorSocial $provedor): RedirecionamentoDoProvedor`
  - `abort_unless(ConfiguracaoDoLogin::disponivel($provedor), 404);`
  - `Log::channel('autenticacao')->info('[LoginSocialController@redirecionar] Redirecionando para o provedor | provedor: '.$provedor->value.' - ip: '.request()->ip(), ['ip' => ..., 'provedor' => $provedor->value])`
  - `return Socialite::driver($provedor->value)->redirect();` — **sem** `->stateless()`.
- `retorno(ProvedorSocial $provedor): RedirectResponse` — as **seis barreiras**, na mesma ordem:
  1. `abort_unless(ConfiguracaoDoLogin::disponivel($provedor), 404);`
  2. `try { Socialite::driver($provedor->value)->user(); } catch (Throwable $e)` — uma cláusula para
     os três casos (state inválido, rede, credencial recusada), porque a resposta ao usuário é a
     mesma nos três.
     - `warning` com `'motivo' => 'falha_no_provedor'`, `'exception' => $e`, `'provedor' => ...`
     - recusa: `"Não foi possível concluir a entrada com o {$provedor->rotulo()}. Tente novamente."`
  3. e-mail ausente → `warning` `'motivo' => 'email_ausente'`; recusa
     `"O {$provedor->rotulo()} não informou um e-mail para esta conta."`
  4. `$provedor->emailVerificado($doProvedor)` falso → `warning` `'motivo' => 'email_nao_verificado'`;
     recusa `"A sua conta do {$provedor->rotulo()} não tem o e-mail verificado."`
  5. conta inexistente **e** registro fechado → `warning`
     `'motivo' => 'conta_inexistente_registro_fechado'`; recusa "Não há conta com este e-mail. O
     acesso a este sistema é por convite."
  6. senão, `criarConta()` (só com registro aberto) e `Auth::login($user)`.
  - Sucesso: `Log::channel('autenticacao')->info("[LoginSocialController@retorno] Autenticado pelo provedor | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado, ['user_id' => ..., 'email' => $mascarado, 'conta_nova' => $novo, 'provedor' => $provedor->value])`
- `emailVerificadoNoProvedor()` **sai** do controller — a lógica é do enum agora. O guard
  `! $doProvedor instanceof AbstractUser` (que o PHPStan cobrou, porque `getRaw()` não está no
  contrato `Socialite\Contracts\User`) vai para dentro do enum, com a mesma falha fechada.
- `criarConta()` passa a receber o provedor, só para o log dizer de onde veio.
- `contaCom()`, `urlDoPainel()`, `urlDoPerfil()`, `recusar()` — **inalterados**.
- Nenhuma mensagem devolvida ao usuário diz **qual** barreira reprovou além do necessário; o motivo
  vai para o log, com o e-mail mascarado (`Str::mask($email, '*', 3)`) e sem o segredo do OAuth.

### 5. `routes/web.php`

- **Path**: `routes/web.php`
- Substitui o grupo `auth/google` pelo grupo `auth/{provedor}` (ADR-02).
- Comentário: as rotas nascem **sempre** de propósito; quem recusa é o `abort_unless` do
  controller; o 404 de provedor inexistente é do implicit enum binding.
- **Logs**: n/a.

### 6. Os blades

> Skills: `tailwindcss-development` — e **ler `.ai/rules/views.md` antes**

- **Novo**: `resources/views/filament/auth/botoes-sociais.blade.php`
  - `@php $provedores = \App\Support\ConfiguracaoDoLogin::disponiveis(); @endphp`
  - `@if ($provedores !== [])` — divisor "ou" uma vez, depois o laço.
  - Por provedor: `<x-filament::button tag="a" :href="route('auth.social.redirect', $provedor)" color="gray" size="lg" outlined :aria-label="'Entrar com '.$provedor->rotulo()" style="width:100%">` + o ícone por inclusão + `Entrar com {{ $provedor->rotulo() }}`.
  - Espaçamento entre botões quando há mais de um.
- **Novos**: `resources/views/filament/auth/icones/{google,github,linkedin,x}.blade.php` — só o
  `<svg>`, com `aria-hidden="true" focusable="false"`, 18×18. Google reaproveita o SVG que já
  existe; GitHub/LinkedIn/X em `currentColor` (marcas monocromáticas), o que faz o ícone seguir
  tema claro/escuro sozinho.
- **Removido**: `resources/views/filament/auth/botao-google.blade.php`.
- **Atenção**: nenhum comentário de blade pode escrever diretiva com arroba — vira código
  (`.ai/rules/views.md`).

### 7. Settings: classe, mapa, `encrypted()`, migration e a tela

> Skills: `laravel-best-practices` — e **ler `.ai/rules/settings.md` e `.ai/rules/pages.md` antes**

Os **três lugares** do contrato, para cada propriedade nova:

- **7a. `app/Settings/ConfiguracoesDoKit.php`**
  - 9 propriedades novas: `login_github_habilitado`/`_client_id`/`_client_secret`,
    `login_linkedin_openid_*`, `login_x_*` (tipos: `bool`, `?string`, `?string`).
  - `encrypted()` → `['mail_password', 'login_google_client_secret', 'login_github_client_secret', 'login_linkedin_openid_client_secret', 'login_x_client_secret']` (ADR-06).
  - `mapaDeConfiguracao()` → 9 linhas novas, no bloco de login social:
    `login_github_habilitado => kit.login.github.habilitado`,
    `login_github_client_id => services.github.client_id`, etc. Para o LinkedIn a chave de config é
    `kit.login.linkedin-openid.habilitado` / `services.linkedin-openid.client_id`.
  - Comentário explicando por que estas **podem** ser Settings (lidas por request) enquanto
    `registro_verificar_email` não pode (lida no boot).
- **7b. `database/settings/2026_08_25_000000_add_provedores_sociais_to_kit_settings.php`** (nova)
  - `add`/`addEncrypted` das 9, semeando de `config(...)` — **nunca** literais, senão a resposta do
    `.env` de quem instalou nunca chega à tela.
  - A **normalização** do `login_google_client_secret` (ADR-06): `update(...)` com `$encrypted =
    false`, e dentro da closure `try { decrypt($v); return $v; } catch (DecryptException) { return
    encrypt($v); }`, com `null` passando reto.
  - `down()`: `deleteIfExists` das 9. Documenta que reverter o dado do Google exige reverter o
    código primeiro.
- **7c. `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`**
  - `abaLogin()` → `foreach (ProvedorSocial::cases() as $p) { $secoes[] = $this->secaoDoProvedor($p); }`
    + o `Textarea` do rodapé fora das seções.
  - `secaoDoProvedor(ProvedorSocial $p): Section` — `Section::make($p->rotulo())->collapsible()->columnSpanFull()`
    com o toggle `->live()`, o `TextInput` do client_id e o do client_secret
    (`->password()->revealable()->dehydrated(fn (?string $e) => filled($e))` + `->placeholder()`
    dizendo "em branco mantém"), os dois com `->visible(fn (Get $get) => (bool) $get($p->propriedadeDeSettings('habilitado')))`.
  - `helperText` do client_id cita **onde** criar o app OAuth e **qual** URI de redirecionamento
    cadastrar, por provedor.
  - `mutateFormDataBeforeFill()` → zera `mail_password` **e os quatro** `*_client_secret`, por laço
    sobre o enum.
  - `segredoDoGoogleGuardado()` → `segredoGuardadoDe(ProvedorSocial $p): ?string`.
  - **Logs**: `afterSave()` inalterado (registra quem salvou; o que mudou fica na trilha de
    `audits`).

### 8. Testes

> Skills: `pest-testing`. Ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.

- **Atualizar** `tests/Kit/LoginSocialGoogleTest.php`: só o que o rename de controller e de
  mensagem exige (2 asserções de log) — nenhum caso removido.
- **Novo** `tests/Kit/LoginSocialProvedoresTest.php`: o par por provedor (ligado+completo mostra o
  botão / incompleto não mostra **e** a rota dá 404), a verificação de e-mail por provedor, o 404 de
  provedor fora do enum, e o isolamento entre provedores.
- **Novo** `tests/Kit/SegredosDoSettingsTest.php`: o ADR-06 — o que está gravado é ciphertext, e a
  normalização.
- **Atualizar** `tests/Browser/LoginSocialGoogleTest.php` e criar o CT-B da aba Login.
- **Nenhum caso sai para a rede**: `Socialite::fake($driver, User::fake([...]))` e `Http::fake()`.

### 9. Verificação

- `vendor/bin/pint --dirty --format agent`
- `vendor/bin/phpstan analyse` — 0 erros
- `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — base **1016**, não pode cair
- `composer test:browser`

### 10. Documentação

> RQ-10, e é cláusula de requisito, não cortesia.

- **`README.md`** e **`README.en.md`**, seção "Login social": tabela dos provedores suportados;
  para **cada** um, onde criar o app OAuth, qual URI de redirecionamento cadastrar, quais escopos o
  kit pede e **o que acontece se o provedor não devolver e-mail verificado**; a explicação de por
  que Facebook e Discord ficaram fora, e o que faltaria para incluí-los; o roteiro do próximo
  provedor.
- **`.env.example`**: as 4 chaves novas de interruptor e as 6 de credencial, **vazias**, com o
  comentário de falha fechada.
- **`CHANGELOG.md`**: a entrada, incluindo o defeito do ADR-06 como correção.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada, aplicada nesta feature: reusar
> `ConfiguracaoDoLogin`/`RegistroAberto`/o channel `autenticacao` antes de criar; `Socialite::driver()`
> como fábrica em vez de uma nossa; enum nativo do PHP em vez de interface + classes; `filter_var`
> da stdlib em vez de coerção própria; implicit enum binding do Laravel em vez de lista branca
> escrita à mão. Atalhos deliberados marcados com comentário `ponytail:`.

## Testes

Ver `04-casos-de-teste.md` (backend) e `05-casos-de-teste-browser.md` (UI).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` — 0 erros
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — >= 1016
- [ ] `composer test:browser`
- [ ] `php artisan route:list --path=auth` confere as duas rotas e o parâmetro

## Commits

- `:sparkles: feat(login-social): enum de provedor, rota unica e a barreira de e-mail por provedor`
- `:lock: fix(settings): client_secret cifrado na ida E na volta`
- `:memo: docs(readme): login social com quatro provedores, e por que dois ficaram fora`
- `:memo: docs(specs): wiki da feature mais-provedores-sociais`
