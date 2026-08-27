# Plano de Ação — Cadastro pelo provedor social: registro, organização e convite

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/login-social-google/login-social-google/` (+ `vinculo-de-provedor-social`, `registro-e-aprovacao`, `convites`)
- **Motivo**: o login social só **autenticava**; criava conta só sem organização e sem convite. A validação real com tenancy expôs que "conta nova pelo provedor" era recusada por desenho — e o solicitante pediu o caminho completo.
- **Toca infra compartilhada?**: sim — `LoginSocialController::retorno()` (porta dos quatro provedores), a view dos botões sociais (usada no login e no bloqueio), `KitServiceProvider` (hooks). Regressão obrigatória contra `LoginSocialGoogleTest`, `LoginSocialProvedoresTest`, `VinculoDeProvedorSocialTest`, `BloqueioDeSessaoTest`, `LoginSocialGoogleTenancyTest`, `ConviteTest`, `RegistroAbertoTest`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | botões sociais no `/app/register` | 1, 2 | o mesmo blade do login, pelo hook `AUTH_REGISTER_FORM_AFTER` |
| RQ-02 | conta sem senha, e-mail verificado | 4 | já era assim (`Str::password(32)`, `email_verified_at`); preservado |
| RQ-03 | `?org=` cria na organização | 2, 3, 4 | `RegistroAberto::registrar($dados, $organizacao)` — a porta do formulário já sabe |
| RQ-04 | `?token=` aceita o convite | 2, 3, 4 | `Convite::aceitar()` / `aceitarComoUsuarioExistente()` — a porta do formulário já sabe |
| RQ-05 | validações | 3, 4 | org inexistente/fechada → `RegistroAberto` recusa; convite inválido → `Convite::valido()` devolve `null`; e-mail diferente → recusa explícita |
| RQ-06 | medido na instalação real com tenancy | 6 | `TESTES KIT/social-tenancy`, Google e GitHub |

## Objetivo

Fechar o caminho "cadastrar-se pelo provedor" onde o formulário já sabe cadastrar: na organização
certa (multi-organização) e a partir do convite. Sem lógica nova de cadastro — o controller passa a
**carregar o contexto** que a tela de registro recebe (`org`, `token`) até a volta do OAuth e a
entregar às portas que já existem (`RegistroAberto::registrar()`, `Convite::aceitar()`).

## Contexto

`/app/register` lê `?org=` e `?token=` no `mount()` (`RegistroPorConvite.php:95-135`). O botão
social não os carregava, e o OAuth não tem onde levá-los: o `state` é do Socialite (anti-CSRF).
Então `criarConta()` chamava `RegistroAberto::registrar($dados)` sem organização — e com a tenancy
ligada a porta recusa (`sem_organizacao`), como deve. O convite nem entrava na conta.

## Análise dos Arquivos Existentes

- `resources/views/filament/auth/botoes-sociais.blade.php` — percorre `ConfiguracaoDoLogin::disponiveis()` e monta `route('auth.social.redirect', $provedor)`. Ganha os parâmetros `org`/`token` da query corrente, quando presentes.
- `app/Providers/KitServiceProvider.php::configureTelaDeLogin()` — dois hooks `AUTH_LOGIN_FORM_AFTER`. Ganha `AUTH_REGISTER_FORM_AFTER` com a view dos botões (sem o rodapé).
- `app/Http/Controllers/Auth/LoginSocialController.php` — `redirecionar()` (guarda o contexto na sessão), `retorno()` (consome o contexto no ramo "sem conta" e no "conta existe + convite"), `criarConta()` (ganha `?Tenant $organizacao`).
- `app/Models/Convite.php` — `valido(?string $token)`, `aceitar(array $dados)` (já trata conta existente), `usuarioExistente()`, `email`. Sem mudança.
- `app/Support/RegistroAberto.php` — `organizacao(?string $slug)`, `registrar(array, ?Tenant)`. Sem mudança.

## Autorização

- **Policies/Gates**: nenhum novo. A autorização de "quem entra em qual organização" continua nas duas portas existentes.
- **Middleware**: os do grupo `auth/{provedor}` (`throttle:10,1`).
- **Segredo**: o `token` do convite **não** vai para log (só `com_token: true/false`); na sessão fica até a volta e é consumido com `pull()` em qualquer desfecho.

## Rotas

Nenhuma nova. `auth.social.redirect` passa a aceitar `?org=` e `?token=` (query).

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação | Depende de JS? |
|---|---|---|---|---|
| `/app/register` (registro aberto e aceite de convite) | Filament (página do kit) | `/app/register?org=…` ou `?token=…` | clica "Entrar com X" abaixo do formulário | Não |

**Gate de CT-B**: nada só o navegador prova — o botão é um `<a href>`. Sem `05`. A captura de
tela do registro com os botões pode entrar em `CapturaDeArteTest` numa próxima leva de arte.

## Variáveis de Ambiente

Nenhuma nova.

## Eventos / Listeners / Observers · Jobs / Queues

Nenhum novo. O aceite de convite já envia o que envia.

## Impacto em Features Existentes

- Login social: `redirecionar()` grava uma chave na sessão em todo clique; `retorno()` a consome sempre. Oráculos dos CT existentes não mudam.
- Convite: um convite passa a poder ser aceito pelo provedor — consumido do mesmo jeito (`aceito_em`, `token_lembrete = null`).
- Tela de bloqueio: usa a mesma view dos botões; não há `org`/`token` na query dela, então nada muda.

## Rollback

Reverter o commit. Sem migration. Sessões com `login_social.contexto` pendente expiram sozinhas.

## Dependências

Nenhuma.

## Riscos

- **Contexto de sessão vazado para outro clique**: quem abre `/app/register?org=acme`, clica em Google, desiste, e depois entra pelo login comum com Google → o `callback` consumiria `org=acme`. Mitigação: só o ramo **sem conta** usa `org`; conta existente ignora. E o `pull()` limpa em qualquer desfecho. Registrado na ADR-02.
- **Convite para outro e-mail**: recusa explícita, convite intacto (CT-C05).

## Channel de Log da Feature

`autenticacao`, o mesmo do login social. Padrão `[Classe@metodo] mensagem | chave: valor` + context; e-mail mascarado; `token` nunca.

## Estrutura de Implementação

### 1. A view dos botões carrega `org` e `token`

- `botoes-sociais.blade.php`: `route('auth.social.redirect', array_filter(['provedor' => $provedor->value, 'org' => request()->query('org'), 'token' => request()->query('token')], is_string(...)))`. Comentário no cabeçalho explicando que a query da tela de registro precisa chegar ao `redirect`.

### 2. O hook da tela de registro

- `KitServiceProvider::configureTelaDeLogin()`: `FilamentView::registerRenderHook(PanelsRenderHook::AUTH_REGISTER_FORM_AFTER, fn () => view('filament.auth.botoes-sociais')->render())`. O rodapé não entra (é do login).

### 3. `redirecionar()` guarda o contexto

- `session()->put('login_social.contexto', ['org' => $org, 'token' => $token])` com os dois filtrados a `string` não vazia (ou ausentes).
- **Log**: `info "[LoginSocialController@redirecionar] Redirecionando para o provedor | provedor: x - ip: y"` ganha `'contexto' => ['org' => $org, 'com_token' => $token !== null]`.

### 4. `retorno()` consome o contexto

- Logo depois de obter `$doProvedor`: `$contexto = session()->pull('login_social.contexto', [])` (array).
- Ramo **sem conta**:
  1. `$convite = Convite::valido($contexto['token'] ?? null)`. Se há convite e `mb_strtolower(trim($convite->email)) !== $email` → `warning` (`motivo: convite_para_outro_email`, `convite_id`) e `recusar('Este convite é para outro e-mail. Entre com a conta do provedor que usa o e-mail convidado.')`. Se há convite e bate → `criarContaPorConvite($provedor, $convite, $email, $mascarado, $nome)`: `$convite->aceitar(['name' => …, 'email' => $email, 'password' => Str::password(32)])`, `forceFill(['email_verified_at' => now(), 'origem' => $provedor->value])`, `info "[…@criarContaPorConvite] Conta criada por login social a partir de convite | provedor - user - convite"`. `RuntimeException` → recusa como hoje.
  2. Sem convite: se registro fechado → recusa (como hoje). Senão `$organizacao = RegistroAberto::organizacao($contexto['org'] ?? null)` (pode ser `null`) e `criarConta($provedor, $email, $mascarado, $nome, $organizacao)` → `RegistroAberto::registrar($dados, $organizacao)` — com tenancy e sem organização a porta recusa (comportamento atual).
- Ramo **conta existe** (sem vínculo): se `Convite::valido($token)` existe e o e-mail bate → `$convite->aceitarComoUsuarioExistente($user)` (`RuntimeException` → só log `warning`, segue); `info "[…@retorno] Convite aceito na volta do provedor por conta existente | convite - user"`. Depois, o fluxo do vínculo como está (aviso ou confirmação).
- Ramo **vínculo existe**: idem ao anterior para o convite (quem já é reconhecido pode aceitar um convite pelo botão).

### 5. Testes

Ver `04-casos-de-teste.md`. Suíte Kit: `tests/Kit/CadastroSocialPorConviteTest.php`. Suíte Tenancy: `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php`.

### 6. Validação real

`TESTES KIT/social-tenancy` (tenancy ligada, demo, registro aberto na `acme`, Google e GitHub pela camada de aplicação): (a) conta vinculada à `acme` entra pelo Google → `/app/acme`; (b) `/app/register?org=acme` → GitHub (yahoo) → conta nova na `acme`; (c) `/app/register` sem org → recusa; (d) convite para o yahoo em `globex` → link → GitHub → membro de `globex`. Registrar em `07-validacao-real-dos-provedores.md` da wiki do login social.

### 7. README

PT/EN: na seção "Vínculo com o provedor", trocar o parágrafo da multi-organização ("conta nova sem organização é recusada… evolução declarada") pelo comportamento novo; acrescentar em "O que o login social faz" a linha do cadastro pelo `/app/register` (org e convite).

## Filosofia de Implementação

> Ponytail `full`: zero lógica nova de cadastro — as duas portas existentes recebem o que o formulário já lhes dá. Sessão em vez de `state` customizado. Sem tela intermediária.

## Verificação Final

- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse` nos arquivos tocados
- [ ] testes novos (Kit + Tenancy) + regressão dos sete arquivos
- [ ] mutação: sem o `pull()` do contexto no ramo sem conta, CT-C03 (org) e CT-C04 (convite) reprovam
- [ ] validação real (passo 6) registrada
- [ ] commit, PR #45

## Commits

- `:sparkles: feat(login-social): cadastro pelo provedor a partir do /app/register — por organização e por convite`
- `:memo: docs(readme): cadastro social por organização e convite`
