# Plano de Ação — Vínculo de provedor social

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/login-social-google/login-social-google/` (e
  `mais-provedores-sociais`, que trouxe os outros três provedores)
- **Motivo**: a validação real dos provedores (arquivo `07` da ancestral) levantou a pergunta "e
  quando o e-mail já tem conta?". A resposta é este vínculo.
- **Toca infra compartilhada?**: sim — `users` ganha relação nova (`vinculosSociais`), a tabela de
  settings ganha uma propriedade, e o `LoginSocialController::retorno()` (porta de entrada dos
  quatro provedores) muda de forma. Regressão obrigatória contra os CT de `LoginSocialGoogleTest`,
  `LoginSocialProvedoresTest`, `LoginSocialGoogleTenancyTest` e `BloqueioDeSessaoTest`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | e-mail verificado com conta → entra, modo padrão | 4 | comportamento que já existia; o passo 4 o preserva no ramo "sem vínculo, conta existe, modo padrão" |
| RQ-02 | decisão registrada | 8, `02-decisoes-arquiteturais.md` ADR-01 | — |
| RQ-03 | vínculo (`provedor`, `sub`) gravado na primeira entrada | 1, 2, 4 | — |
| RQ-04 | aviso por e-mail na primeira entrada em conta existente | 3, 4 | — |
| RQ-05 | entradas seguintes casam pelo vínculo | 4 | é a primeira consulta do `retorno()` |
| RQ-06 | modo opcional: confirmar por link antes de entrar | 3, 4, 5 | link assinado, 30 minutos |
| RQ-07 | configurável no `.env` e na tela | 6 | booleano — ver ambiguidade do `00` |
| RQ-08 | README com prints | 8, 9 | capturas via `CapturaDeArteTest` + `kit:arte` |
| RQ-09 | tenancy: existente entra, nova sem organização recusa | — | **já atendida** pelo fix `8c92658` da rodada de validação; o caso está em `LoginSocialGoogleTenancyTest`. Nada a fazer aqui além de não regredir |

## Objetivo

Dar ao login social uma identidade própria dentro do kit — o vínculo entre a conta e o `sub` do
provedor — de modo que (a) quem já entrou por um provedor seja reconhecido por ele, e não pelo
e-mail; (b) a primeira entrada de um provedor numa conta que já existia deixe rastro e avise a
pessoa; e (c) quem administra possa exigir, por configuração, que essa primeira entrada seja
confirmada pelo e-mail antes de virar sessão.

## Contexto

Hoje o `retorno()` casa a conta pelo e-mail verificado que o provedor devolve, e só. Isso é
seguro na medida em que o provedor só marca verificado quem provou a caixa postal — a mesma prova
que o "Esqueceu a senha?" aceita. O que falta não é barreira, é **memória**: o kit não sabe que
"esta conta já entrou pelo Google", então (1) não pode avisar quando um provedor novo aparece, (2)
não pode oferecer um modo mais rígido, e (3) fica refém do e-mail em toda entrada — um endereço
reciclado no provedor de correio leva à conta do dono anterior. Ver ADR-01.

## Análise dos Arquivos Existentes

### `app/Http/Controllers/Auth/LoginSocialController.php`

- `retorno()`: obtém o usuário no provedor, valida e-mail presente e verificado, casa por
  `contaCom($email)`, cria pela porta do registro aberto quando não há conta, trata pendência,
  `Auth::login()`, destrava lockscreen, redireciona. **O trecho entre `contaCom()` e
  `Auth::login()` é reescrito** (passo 4).
- `recusar()`, `aguardarAprovacao()`, `criarConta()`, `urlDoPainel()`, `urlDoPerfil()`: mantidos.

### `app/Support/ConfiguracaoDoLogin.php`

- Ponto único de leitura da config de login. Ganha `vinculoExigeConfirmacao()`.

### `app/Settings/ConfiguracoesDoKit.php` e `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

- Três lugares para a propriedade nova (classe, `mapaDeConfiguracao()`, migration de settings) —
  `.ai/rules/settings.md`. A chave é lida por request (dentro do `retorno()`), então pode ir para a
  tela.

### `routes/web.php`

- Grupo `auth/{provedor}` com `throttle:10,1`. Ganha a rota `confirmar`, sob `signed`.

### `tests/Pest.php`

- `ligarProvedor()`, `usuarioSocialFalso()` (aceita `id` em `$mapeados` — é o `sub`), `usuario()`.

## Autorização

- **Policies/Gates**: nenhum novo. O vínculo não é recurso de painel.
- **Middleware**: `signed` na rota de confirmação (assinatura temporária, 30 min) + o `throttle`
  do grupo.
- **Guards**: `web`, o mesmo do login.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/auth/{provedor}/confirmar?user=&sub=&expires=&signature=` | `auth.social.confirmar` | `throttle:10,1`, `signed` |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação | Depende de JS? |
|---|---|---|---|---|
| Tela de login (aviso após pedir confirmação) | Filament | `/{painel}/login` | lê a notificação persistente | Não |
| `/admin/configuracoes-do-kit` → aba Login → seção "Login social" | Filament (Settings) | `/admin/configuracoes-do-kit` | liga/desliga "Exigir confirmação por e-mail…" | Não |
| E-mails `PrimeiroAcessoSocial` e `ConfirmarVinculoSocial` | MailMessage | — | abre o link | Não |

**Gate de CT-B**: nada aqui só o navegador prova. Sem `05`. As capturas de tela do passo 9 são
**arte para o README**, não evidência de teste (`.ai/rules/testes-browser.md`).

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_SOCIALITE_VINCULO_CONFIRMAR` | `false` | `true`: a primeira entrada social numa conta existente exige confirmação pelo link no e-mail. `false`: entra e avisa por e-mail. `FILTER_VALIDATE_BOOLEAN` — falha fechado (no modo padrão) |

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum novo. As duas notificações são `ShouldQueue` como `ConviteDeAcesso`.

## Jobs / Queues

- As notificações vão pela fila padrão (`SendQueuedNotifications`). **Sem worker nada sai** —
  `composer dev` sobe um; a matriz de validação registrou esse tropeço.

## Impacto em Features Existentes

- Login social (quatro provedores): a ordem das decisões no `retorno()` muda; os oráculos dos CT
  existentes (recusa sem e-mail, sem verificação, sem conta com registro fechado, criação, 2FA,
  lockscreen) **não** mudam. Regressão obrigatória.
- Registro aberto: nada muda; a conta nova criada por provedor passa a nascer **com** vínculo.
- Exclusão de usuário: `cascadeOnDelete` na FK — apagar a conta apaga os vínculos.

## Rollback

- **Migration down**: `dropIfExists('vinculos_sociais')`; a de settings faz `deleteIfExists`.
- **Feature flag**: `KIT_SOCIALITE_VINCULO_CONFIRMAR=false` devolve o comportamento anterior
  (entra pelo e-mail) — com o acréscimo do aviso e do vínculo, que são inócuos.

## Dependências

- Nenhuma nova. `Str::mask`, `URL::temporarySignedRoute`, middleware `signed` e notificações são
  do framework.

## Riscos

- **Provedor sem `sub` estável** (`getId()` vazio): não se cria vínculo e o fluxo cai no e-mail,
  como hoje. Registrado em log. Os quatro drivers do kit devolvem id.
- **`sub` já vinculado a OUTRA conta** na confirmação (corrida entre dois links): a confirmação
  recusa em vez de re-vincular. Caso CT-V08.
- **Notificação na fila** parece "não enviou" em instalação sem worker: documentado no README.

## Channel de Log da Feature

Existente: `autenticacao` (`config/logging.php`), o mesmo de todo o login social. Todo log desta
feature segue `[Classe@método] mensagem | chave: valor` com `array $context`, e o e-mail sempre
mascarado (`Str::mask($email, '*', 3)`).

## Estrutura de Implementação

### 1. Tabela e model do vínculo

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_26_172455_create_vinculos_sociais_table.php`,
  `app/Models/VinculoSocial.php`, `app/Models/User.php` (relação `vinculosSociais()`).
- Colunas: `id`, `user_id` (FK `users`, `cascadeOnDelete`), `provedor` (string 32), `sub` (string
  191), `confirmado_em` (timestamp), `ultimo_acesso_em` (timestamp nullable), timestamps.
  `unique(provedor, sub)`; `index(user_id, provedor)`.
- Model: `$table = 'vinculos_sociais'`, `belongsTo(User)`, estáticos `de(ProvedorSocial, string
  $sub): ?self` e `vincular(User, ProvedorSocial, string $sub): self` (`firstOrCreate` pela chave
  única), `registrarAcesso()` (`ultimo_acesso_em = now()`).
- **Logs**: nenhum no model; quem loga é o controller.

### 2. Config, settings e ponto único de leitura

> Skills: `laravel-best-practices`; rule `.ai/rules/settings.md`

- `config/kit.php` → `login.vinculo_confirmar` (`FILTER_VALIDATE_BOOLEAN` de
  `KIT_SOCIALITE_VINCULO_CONFIRMAR`).
- `app/Settings/ConfiguracoesDoKit.php` → `public bool $login_vinculo_confirmar;` + linha no
  `mapaDeConfiguracao()`.
- `database/settings/2026_08_26_000000_add_login_vinculo_confirmar_to_kit_settings.php` →
  `add('login_vinculo_confirmar', (bool) config('kit.login.vinculo_confirmar', false))`.
- `app/Support/ConfiguracaoDoLogin::vinculoExigeConfirmacao(): bool`.
- `.env.example`: a chave com o comentário do bloco de login social.

### 3. As duas notificações

> Skills: `laravel-best-practices`

- `app/Notifications/PrimeiroAcessoSocial.php` (`ProvedorSocial $provedor, string $ip`):
  assunto "Sua conta foi acessada pelo {rótulo} pela primeira vez"; corpo com quando e IP;
  orientação: se não foi você, troque (ou defina) a senha e avise quem administra; ação
  "Abrir o painel" → `Filament::getPanel('app')->getUrl()`.
- `app/Notifications/ConfirmarVinculoSocial.php` (`ProvedorSocial $provedor, string $url`):
  assunto "Confirme a entrada pelo {rótulo}"; corpo: alguém tentou entrar na sua conta com a conta
  {rótulo}; se foi você, confirme (vale 30 minutos); se não, ignore — nada muda. Ação
  "Confirmar e entrar" → a URL assinada.
- Ambas `ShouldQueue` + `Queueable`, `via = ['mail']`, como `ConviteDeAcesso`.

### 4. O `retorno()` decide pelo vínculo antes do e-mail

> Skills: `laravel-best-practices`, `socialite-development`

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php`.
- Depois da verificação de e-mail, a ordem passa a ser:
  1. `$sub = trim((string) $doProvedor->getId())`; `$vinculo = VinculoSocial::de($provedor, $sub)`.
  2. **Há vínculo** → `$user = $vinculo->user`, `registrarAcesso()`, log
     `info "[LoginSocialController@retorno] Conta reconhecida pelo vínculo | provedor: x - user: n"`.
  3. **Não há** → `contaCom($email)`:
     - sem conta e registro fechado → recusa (como hoje);
     - sem conta e registro aberto → `criarConta()` (como hoje), `$novo = true`;
     - com conta e `vinculoExigeConfirmacao()` → `pedirConfirmacaoDoVinculo()` e **retorna**;
     - com conta, modo padrão → `notify(new PrimeiroAcessoSocial)`, log
       `info "[…@retorno] Primeiro acesso por este provedor — vínculo criado e aviso enviado | provedor: x - user: n - email: mascarado"`.
     - em todos os ramos que seguem: `VinculoSocial::vincular($user, $provedor, $sub)` se `$sub !== ''`.
  4. `aprovacao_pendente` → `aguardarAprovacao()` (agora para conta nova **ou** existente).
  5. `Auth::login()`, destravar lockscreen, log, redirect — como hoje.
- `pedirConfirmacaoDoVinculo(ProvedorSocial, User, string $sub, string $mascarado)`:
  `URL::temporarySignedRoute('auth.social.confirmar', now()->addMinutes(30), [provedor, user, sub])`,
  `notify(new ConfirmarVinculoSocial)`, log
  `warning "[…@retorno] Primeira entrada por este provedor aguarda confirmação por e-mail | provedor: x - user: n"`
  (`motivo: vinculo_aguardando_confirmacao`), `Notification` persistente `info` "Confirme pelo
  e-mail", redirect para o login do `/app`.

### 5. A rota e a ação de confirmação

> Skills: `laravel-best-practices`

- `routes/web.php`: `Route::get('confirmar', [LoginSocialController::class, 'confirmarVinculo'])->middleware('signed')->name('confirmar')`
  dentro do grupo existente.
- `confirmarVinculo(ProvedorSocial $provedor, Request $request)`: `abort_unless(disponivel, 404)`
  como as outras ações; `$user = User::find($request->integer('user'))`, `$sub = trim(query sub)`;
  sem usuário ou sem `sub` → `recusar('Este link não é válido.')`; `sub` já vinculado a **outra**
  conta → `recusar('Esta conta do {rótulo} já está vinculada a outro usuário.')` +
  log `warning` (`motivo: sub_ja_vinculado`); senão `vincular()`, pendência → `aguardarAprovacao()`,
  `Auth::login()`, destravar lockscreen, log
  `info "[…@confirmarVinculo] Vínculo confirmado pelo e-mail | provedor: x - user: n"`, redirect
  para o painel.
- Assinatura inválida/expirada: o `signed` responde 403 pela página de erro do kit.

### 6. A tela de Settings

> Skills: `filament`

- Aba Login → seção "Login social": `Toggle::make('login_vinculo_confirmar')` com rótulo "Exigir
  confirmação por e-mail na primeira entrada social em conta existente" e `helperText` dizendo o
  que muda (entra e avisa × recebe o link e só entra depois de confirmar).

### 7. Testes

> Skills: `pest-testing`

Ver `04-casos-de-teste.md`. Arquivo: `tests/Kit/VinculoDeProvedorSocialTest.php`, com os helpers
de `tests/Pest.php`. Regressão: os quatro arquivos citados em *Natureza da Wiki*.

### 8. README (PT e EN) e ADR

- Seção nova dentro de "Login social": **"Vínculo com o provedor: a primeira vez, e as
  seguintes"** — a tabela dos quatro casos (vínculo existe / não existe × conta existe / não
  existe), o argumento de segurança (a mesma prova do "Esqueceu a senha?"), os riscos residuais
  (endereço reciclado, bug do provedor) e o que cada modo faz com eles, a chave, o `sub`, a fila.
- A tabela "O que o login social faz" ganha a linha "Guarda a identidade no provedor (`sub`)".
- `02-decisoes-arquiteturais.md` ADR-01..04.

### 9. Capturas para o README

> Rule: `.ai/rules/testes-browser.md` — captura nova = `->screenshot(filename)` **e** a linha em
> `KitArte::IMAGENS`.

- `tests/BrowserTenancy/CapturaDeArteTest.php`: `login-social` (login do `/app` com dois
  provedores ligados), `admin-configuracoes-login` (aba Login), `app-perfil-definir-senha`,
  `app-bloqueio-social`, `admin-users-origem`.
- `KitArte::IMAGENS` ganha os cinco nomes; `composer art` publica em `art/` e gera thumbs; o README
  referencia `raw.githubusercontent.com/.../art/<nome>.png`.

## Filosofia de Implementação

> Ponytail `full`: a tabela tem só o que os três fluxos leem; nenhuma tela de gerenciamento de
> vínculo (fora de escopo declarado); o link de confirmação é o mecanismo nativo de URL assinada;
> as notificações copiam a forma da `ConviteDeAcesso`. Caveman na conversa; prosa normal aqui.

## Verificação Final

- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse` nos arquivos tocados
- [ ] `php artisan test --compact tests/Kit/VinculoDeProvedorSocialTest.php` + regressão dos quatro arquivos
- [ ] `KIT_ART=1 php artisan test --filter=CapturaDeArte` + `php artisan kit:arte`
- [ ] Mutação: sem `VinculoSocial::de()` no `retorno()`, CT-V03 (e-mail trocado) reprova; sem o `signed`, CT-V05 reprova
- [ ] commit, PR #45

## Commits

- `:sparkles: feat(login-social): vínculo com o provedor — reconhece pelo sub, avisa na primeira vez, e confirma por e-mail se configurado`
- `:memo: docs(readme): vínculo de provedor social, com as capturas`
