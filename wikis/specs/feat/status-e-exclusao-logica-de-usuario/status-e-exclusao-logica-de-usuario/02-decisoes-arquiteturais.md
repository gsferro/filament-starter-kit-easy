# Decisões Arquiteturais — Status de ativo/inativo e exclusão lógica de usuário

## ADR-01: A negação mora em `User::canAccessPanel()`, antes de tudo — inclusive do cadastro pendente

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

Há três lugares onde "este usuário pode entrar?" é perguntado: a tela de login por senha
(`Filament\Auth\Pages\Login::authenticate()` chama `isUserAllowedToAccessPanel()`,
`vendor/filament/filament/src/Auth/Pages/Login.php:105,178`), o middleware do painel a cada
request (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:35-39`) e o login social
(`LoginSocialController`, que faz `Auth::login()` por fora do guard). Os três já convergem, hoje,
em `canAccessPanel()` para o cadastro pendente — menos o social, que checa `aprovacao_pendente`
por conta própria.

### Decisão

`ativo = false` e `deleted_at` preenchido negam em `canAccessPanel()` como **primeira** instrução,
antes da guarda de `aprovacao_pendente`, pelo mesmo argumento que pôs aquela guarda antes do
atalho do `master_global`: posta depois, teria um furo invisível. A pergunta "qual é o motivo da
indisponibilidade" ganha um método (`motivoDeIndisponibilidade()`) que os três chamadores usam —
inclusive o login social, que passa a perguntar ao model em vez de repetir a condição.

### Alternativas Consideradas

1. **Filtrar `ativo = true` nas credenciais** (`getCredentialsFromFormData()` acrescentando a
   coluna) — descartada. Só cobre a tela de login; a sessão já aberta de quem é desativado
   continuaria válida até expirar, e o login social não passa por credenciais.
2. **Global scope `ativo = true` no model** — descartada. Sumiria da tela de usuários justamente
   quem precisa ser reativado, e um escopo global no model do guard é o que ADR-03 de
   `admin-da-organizacao` já recusou por outro motivo.
3. **Middleware próprio** — descartada. O `Authenticate` do Filament já chama `canAccessPanel()` a
   cada request; um middleware a mais seria a mesma pergunta em outro lugar.

### Consequências

- **Positivas**: uma decisão, três consumidores. O excluído nem chega a `canAccessPanel()` pela
  senha (o `EloquentUserProvider` respeita o escopo do `SoftDeletes`), mas a guarda vale para
  qualquer caminho que reconstrua um `User` trashed — `withTrashed()` numa tela, um job.
- **Negativas**: mais um `warning` por request de quem está desativado e ainda tem sessão (o
  middleware pergunta a cada request). É o mesmo ruído que o cadastro pendente já produz.
- **Riscos**: nenhum novo — a guarda é `return false` com log.

### Referências

- `app/Models/User.php:107-161` (a guarda de `aprovacao_pendente` e o comentário sobre ordem)
- `vendor/filament/filament/src/Auth/Pages/Login.php:98-110` (o `fireFailedEvent` na negativa)
- `vendor/filament/filament/src/Http/Middleware/Authenticate.php:35-39`

---

## ADR-02: A tela de aviso é uma view própria no layout do Sentinel, alcançada por interceptar `authenticate()`

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

O requisito pede "uma tela de aviso (talvez uma do pacote sentinel)". O Sentinel do kit tem um
layout publicado e autossuficiente (`resources/views/errors/sentinel-layout.blade.php`) e cinco
páginas de erro. O 403 mostra o `Motivo` da exceção **só fora de produção**
(`403.blade.php`, `$mostrarDiagnostico = ! app()->isProduction()`): um `abort(403, 'Sua conta…')`
entregaria a frase em dev e a esconderia em produção, que é onde importa.

Do lado do login, a falha do Filament é uma `ValidationException` lançada dentro de um `Timebox`
por `throwFailureValidationException()`, que é `never` — não há como devolver um redirect dali.

### Decisão

- **View** `auth/conta-indisponivel.blade.php` que **estende** `errors.sentinel-layout` com
  `code 403`, tom `warning`, título e corpo escolhidos pelo motivo. Mesma cara, mesmo número de
  mensagem `SNT-403-…`, texto visível em qualquer ambiente. Servida por rota pública que só
  renderiza quando há um aviso **flash na sessão** (posto pelo interceptor um request antes); sem
  aviso, redireciona para `/`.
- **Interceptação** em `TelaLogin::authenticate()`: `try { return parent::authenticate(); }
  catch (ValidationException $e)`. No `catch`, procura a conta por e-mail **com** trashed, e só
  se ela estiver indisponível **e** a senha conferir (ADR-03) faz `$this->redirect()` para a rota
  do aviso. Caso contrário relança a exceção — o comportamento de hoje.
- `TelaLogin` passa a ser a tela de login dos **três** painéis (`->usingPage()` no Auth Designer
  do `/admin` e do `/infra`), senão só o `/app` explicaria.

### Alternativas Consideradas

1. **`abort(403, $mensagem)` reaproveitando o 403 do Sentinel** — descartada: a mensagem some em
   produção (ver contexto).
2. **`Notification` de perigo na própria tela de login** (o que `LoginSocialController::recusar()`
   faz hoje) — descartada. Cumpre "avisar", não cumpre "cair numa tela de aviso"; e o requisito
   nomeou o Sentinel.
3. **Sobrescrever `throwFailureValidationException()`** — descartada: é `never`, roda dentro do
   `Timebox`, e um redirect de Livewire precisa que o método retorne.
4. **Reescrever `authenticate()` inteiro** — descartada: copiaria ~90 linhas do vendor (rate limit,
   Timebox, MFA) para acrescentar um `if`. O `try/catch` preserva tudo e custa oito linhas.
5. **Page Filament em vez de Blade** — descartada: a página é vista por quem **não** tem sessão, e
   o layout do Sentinel já existe exatamente para isso.

### Consequências

- **Positivas**: zero dependência nova; a página herda tema claro/escuro e marca do Sentinel; o
  rate limit, o `Failed` e o `Timebox` do Filament continuam intactos.
- **Negativas**: a rota `/conta-indisponivel` existe sempre e responde `302 /` quando visitada
  "solta" — comportamento deliberado, coberto por caso de teste.
- **Riscos**: o flash de sessão depende de a sessão persistir entre o request do Livewire e o GET
  seguinte — é o mesmo mecanismo que `Notification::send()` fora do Livewire já usa no kit
  (`LoginSocialController::recusar()`).

### Referências

- `resources/views/errors/403.blade.php:15-16,80-85`
- `vendor/filament/filament/src/Auth/Pages/Login.php:73-170,231-236`
- `app/Providers/Filament/AppPanelProvider.php:208-217`

---

## ADR-03: O aviso só aparece com a senha certa — e dentro do mesmo `Timebox` do Filament

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

"Sua conta foi excluída em 12/08/2026" confirma que **existe** (ou existiu) uma conta com aquele
e-mail. Hoje o kit devolve a mesma mensagem genérica para "e-mail não existe" e "senha errada",
e o `LoginSocialController` tem por escrito que "detalhar o motivo na tela é dizer a um atacante
em qual [barreira] ele encostou". O aviso pedido pelo requisito quebra essa uniformidade — para
quem **é** o dono da conta, que é o público dele.

### Decisão

O interceptor mostra o aviso apenas quando `Hash::check($senhaDigitada, $user->password)` é
verdadeiro. Senha errada em conta inativa ou excluída devolve exatamente a mensagem genérica de
hoje. A checagem inteira roda em `app(Timebox::class)->call(…, config('auth.timebox_duration'))`
— a mesma duração mínima que o Filament impõe à falha normal — para que o tempo de resposta não
distinga "não existe" (sem `Hash::check`) de "existe, está excluído, senha errada" (com
`Hash::check`).

No login social a prova equivalente já existe: o provedor **verificou** o e-mail antes de a
pessoa chegar ao controller (barreira `emailVerificado()`). Quem chega ali é o dono do endereço.

### Alternativas Consideradas

1. **Mostrar o aviso por e-mail sozinho** — descartada: enumeração de contas de graça.
2. **Mostrar o aviso só após rate limit estourar** — descartada: não resolve enumeração, e piora a
   experiência do dono legítimo.
3. **Enviar o aviso por e-mail em vez de exibir** — descartada: o requisito pede tela; e-mail fica
   como evolução (fora de escopo declarado).

### Consequências

- **Positivas**: nenhuma informação nova para quem não tem a senha. Um caso de teste específico
  (senha errada em conta excluída → genérico) trava a decisão.
- **Negativas**: um `bcrypt` a mais por tentativa **em conta indisponível** — só nessas; conta
  inexistente ou ativa não paga nada além do que já pagava.
- **Riscos**: `bcrypt` de custo 12 pode passar dos 200 ms do `Timebox` e abrir um oráculo de
  tempo fraco. É a mesma exposição que o próprio Filament aceita para a falha normal — não é
  introduzida aqui.

### Referências

- `app/Http/Controllers/Auth/LoginSocialController.php:45-48,138-158`
- `vendor/filament/filament/src/Auth/Pages/Login.php:89-113` (o `Timebox` e a duração)

---

## ADR-04: Desativar/reativar só no `/admin`, pela mesma régua da exclusão

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

`travas-de-exclusao-e-upload-anonimo` (ADR-01) decidiu que excluir usuário é **ato global** — tira
a pessoa de todas as organizações — e por isso é negado no `/app`, onde quem opera administra
**uma** organização. Desativar tem exatamente o mesmo alcance: `canAccessPanel()` nega nos três
painéis, em todas as organizações.

### Decisão

As ações Desativar e Reativar vivem só no `UserResource` do `/admin`, com permissões próprias
(`Desativar:User`, `Reativar:User`) geradas por `filament-shield.resources.manage` e consultadas
por `->authorize()` na Action e por `UserPolicy::desativar()/reativar()`. O `/app` mostra o estado
(coluna Situação com três valores e o filtro "Somente inativos") e não oferece a ação.

A guarda contra desativar a **própria conta** e o **último `master_global` ativo** mora em
`User::desativar()` — lança para qualquer chamador — e a tela só a espelha em `->visible()`.

### Alternativas Consideradas

1. **Ações nos dois painéis** — descartada pela assimetria já decidida para a exclusão. Se um dia
   o `/app` precisar de "tirar acesso à minha organização", a ferramenta é desvincular
   (`DetachAction` do `UsersRelationManager`), não desativar.
2. **Uma permissão só (`AlterarSituacao:User`)** — descartada: reativar é conceder acesso,
   desativar é retirar; papéis podem legitimamente ter só uma.
3. **Guarda só na tela (`->visible()`)** — descartada por `.ai/rules/filament.md`: barreira que só
   existe na tela não é barreira.

### Consequências

- **Positivas**: o `panel_user` já perde as duas permissões sem mudança de seeder (a subtração é
  por FQCN do `UserResource`); o `admin` as ganha pela matriz do painel.
- **Negativas**: `admin_app` não consegue desativar um membro da própria organização. É a mesma
  limitação que ele já tem para excluir.
- **Riscos**: nenhum novo.

### Referências

- `wikis/specs/feat/auditoria-de-seguranca/travas-de-exclusao-e-upload-anonimo/02-decisoes-arquiteturais.md` ADR-01
- `config/filament-shield.php:226-300` (`resources.manage`)

---

## ADR-05: O e-mail de conta na lixeira fica reservado

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

`users.email` é único. Com exclusão lógica a linha continua na tabela, então criar outra conta com
o mesmo e-mail estoura a constraint. Três portas criam usuário: o formulário de `/admin/users`,
o registro aberto e o aceite de convite — as três validam o e-mail com o `->unique()` do Filament.

### Decisão

Nada muda no código: o `unique` do Laravel **inclui** registros soft-deleted por default
(`Rule::unique()->withoutTrashed()` é opt-in, doc do Laravel 13). O e-mail de uma conta na lixeira
é recusado como "já em uso" nas três portas. Quem quiser o endereço de volta **restaura** a conta
(a intenção mais provável) ou a exclui definitivamente (fora do escopo desta entrega; a permissão
`ForceDelete:User` existe, a ação não é oferecida).

### Alternativas Consideradas

1. **`->unique()` ignorando trashed + `forceDelete()` automático da conta antiga ao recriar** —
   descartada: apagar silenciosamente uma conta da lixeira para dar lugar a outra é o oposto do
   que a lixeira promete.
2. **Mensagem específica "há uma conta excluída com este e-mail"** — descartada nesta entrega: o
   `unique` do Filament devolve a mensagem padrão de validação, e distinguir exigiria trocar a
   regra nas três telas. Documentado no README; evolução barata se incomodar.

### Consequências

- **Positivas**: zero código, zero risco de recriar conta duplicada.
- **Negativas**: `UsuarioAdminSeeder::firstOrCreate()` e `KitAdmin::emailEmUso()` usam
  `User::where()` sem trashed — se o e-mail do administrador estiver na lixeira, o seeder estoura
  na constraint. Cenário de instalação sobre base usada, não de operação; anotado no README.
- **Riscos**: convite enviado para e-mail que está na lixeira só é recusado no **aceite**
  (o `unique` da tela de registro), não no envio. Aceito: o convite não cria nada.

### Referências

- Doc do Laravel 13, validação, `unique` → "Ignoring Soft Deleted Records in Unique Checks"
- `app/Filament/Pages/Auth/RegistroPorConvite.php:276-278,301-303`

---

## ADR-06: A Lixeira exige `Recyclable` — `User` entra, e a dívida do `Projeto` é paga aqui

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

O requisito manda restaurar pela lixeira. Ao ler o vendor para confirmar que `->models([User::class])`
bastaria, o achado: a página do Revive lista linhas de `recycle_bin_items`
(`vendor/promethys/revive/src/Tables/RecycleBin.php:120-124`), e quem grava essas linhas é o
evento `deleted` da trait `Promethys\Revive\Concerns\Recyclable`
(`vendor/promethys/revive/src/Concerns/Recyclable.php:29-45`). `App\Models\Projeto` tem
`SoftDeletes` e **não** tem a trait — a Lixeira do kit lista vazio desde que nasceu, e a rule de
`.ai/rules/models.md` ("model com `SoftDeletes` entra em `models()`") descreve metade do que é
preciso.

O comentário do `InfraPanelProvider` recusava `User` na lista porque "usuário volta com papel numa
organização que pode não existir mais". A premissa era exclusão **física** com cascata. Com
exclusão lógica, `tenant_user`, `model_has_roles` e `vinculos_sociais` **ficam** — restaurar
devolve o que havia; e `Tenant` nunca é apagado (tem `ativo`).

### Decisão

- `User` e `Projeto` ganham `Recyclable`; `User::class` entra em `->models()` da Lixeira do
  `/infra`. O comentário do provider é reescrito com a premissa nova.
- A metade que faltava à rule vira **teste** (`tests/Kit/LixeiraTest.php`): toda model de
  `app/Models` com `SoftDeletes` usa `Recyclable` e está na lista. Rule em prosa continua como
  candidata (step 9), mas o enforço é a máquina.
- Backfill de registros excluídos antes da trait: `php artisan revive:discover`, documentado.

### Alternativas Consideradas

1. **Só `User`, deixar `Projeto` como está** — descartada. A dívida invalida a promessa da tela
   (e do README) para o model de exemplo; a correção é uma linha, e a memória do projeto manda
   pagar dívida bloqueante quando exposta.
2. **Rodar `revive:discover` no `kit:install`** — descartada: instalação nova não tem nada a
   descobrir; quem atualiza roda uma vez.

### Consequências

- **Positivas**: a Lixeira passa a funcionar para os dois models; a asserção "RQ-11 pela lixeira"
  fica verdadeira de fato, não por leitura de config.
- **Negativas**: `Recyclable::booted()` sobrescreve `booted()` — uma futura `booted()` na classe
  precisa chamar a da trait. Nenhuma trait atual declara `booted()` (verificado por grep).
  `Log::info()` do vendor no channel default a cada delete/restore.
- **Riscos**: `state` do `RecycleBinItem` guarda `toArray()` do model — `password` e
  `remember_token` estão em `$hidden` e ficam de fora.

### Referências

- `vendor/promethys/revive/src/Concerns/Recyclable.php:14-19,29-45`
- `vendor/promethys/revive/src/Tables/RecycleBin.php:117-137,371-374`
- `vendor/promethys/revive/src/Models/RecycleBinItem.php:31` (`morphTo()->withTrashed()`)
- `app/Providers/Filament/InfraPanelProvider.php:509-548`

---

## ADR-07: Dashboards pessoais continuam sendo apagados na exclusão lógica

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

`mddev31/filament-dynamic-dashboard` pendura `User::deleting` no boot e apaga os dashboards
pessoais de quem sai (`FilamentDynamicDashboardServiceProvider.php:93-98`). O evento `deleting`
dispara também na exclusão **lógica**. Restaurar o usuário não os devolve.

### Decisão

Aceitar. O dashboard pessoal é conveniência de layout, não dado de negócio; guardá-lo para uma
restauração eventual exigiria interceptar um listener de vendor (ou desregistrá-lo) para
condicionar a `isForceDeleting()`. `ExclusaoDeUsuarioTest` continua verde e continua sendo o
contrato. Documentado no README como o que restaurar **não** devolve.

### Alternativas Consideradas

1. **Desregistrar o listener e reimplementar condicionado a `forceDelete`** — descartada:
   código sobre comportamento de vendor para preservar um layout.

### Consequências

- **Negativas**: quem for excluído e restaurado recomeça com o dashboard padrão.

### Referências

- `vendor/mddev31/filament-dynamic-dashboard/src/FilamentDynamicDashboardServiceProvider.php:78-98`
- `tests/Kit/ExclusaoDeUsuarioTest.php`
