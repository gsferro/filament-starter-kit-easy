# Plano de Ação — W7: validação de e-mail editável na tela

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção (paga a dívida declarada — Blocker QA-01)
- **Wiki ancestral**: `wikis/specs/feat/registro-e-aprovacao/registro-e-aprovacao/`
- **Motivo**: o quality gate daquela wiki reprovou o toggle de `registro_verificar_email` porque
  ele gravava sem fazer efeito. A saída aplicada foi tirar a chave do Settings e documentar que ela
  mora no `.env`. Esta wiki ataca a **causa** — a decisão fixada no array da rota — e devolve o
  toggle com efeito real.
- **Toca infra compartilhada?**: **sim** → `app/Settings/ConfiguracoesDoKit.php` (mapa consumido
  por todo o kit), `AppPanelProvider` (middleware de todas as rotas de página do `/app`) e uma
  migration nova de settings. Regressão obrigatória contra os CT da wiki ancestral
  (`tests/Kit/RegistroAbertoTest.php`, `tests/Tenancy/RegistroAbertoTenancyTest.php`,
  `tests/Kit/ConviteTest.php`) e contra `tests/Kit/ConfiguracoesDoKitTest.php`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | editável na aba Registro | 3, 4, 5 | propriedade + mapa + migration + toggle |
| RQ-02 | decide por request | 1, 2 | o middleware é o único ponto de decisão |
| RQ-03 | middleware próprio, não Closure | 1, 2 | `emailVerifiedMiddlewareName()` troca a classe |
| RQ-04 | ligada barra quem não validou | 1 | delega ao `EnsureEmailIsVerified` do Laravel |
| RQ-05 | desligada não barra e não envia | 1, 2 | guarda no `handle()`; envio segue governado por `email_verified_at` |
| RQ-06 | vale no request seguinte | 3 | linha do `mapaDeConfiguracao()` |
| RQ-07 | convite não regride | — (não-regressão) | `Convite::aceitar()` intocado; coberto por CT-05 e regressão |
| RQ-08 | `/admin` e `/infra` inalterados | — (não-regressão) | nenhum dos dois chama `emailVerification()`; coberto por CT-08 |
| RQ-09 | rota de destino existe sempre | 2 | `emailVerification()` passa a receber a classe **sempre** |

## Objetivo

Tornar `registro_verificar_email` editável na tela de Configurações do Kit **com efeito no request
seguinte**, resolvendo a causa que reprovou a primeira tentativa: hoje a decisão é tomada no boot
do `AppPanelProvider` e materializada no array de middleware da rota, no momento do registro
(`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`).

A inversão é simples e é toda a feature: **o middleware passa a ser aplicado sempre, e quem decide
é ele**. Sai do array da rota a condição `if (opção ligada) então acrescente o middleware`; entra
`sempre acrescente o middleware, que pergunta a opção a cada request`.

## Contexto

Estado atual (v0.19.3), medido:

- `AppPanelProvider.php:410-413` chama
  `->emailVerification(RegistroAberto::exigirVerificacaoDeEmail() ? EmailVerification::class : null, isRequired: RegistroAberto::exigirVerificacaoDeEmail())`.
  Com `null` no primeiro parâmetro, `hasEmailVerification()` é `filled($action)` → `false`
  (`HasAuth.php:620-622`) e **nenhuma rota de verificação nasce**
  (`vendor/filament/filament/routes/web.php:75-84`). Com `isRequired: false`,
  `HasRoutes.php:91` não acrescenta middleware nenhum.
- O settings sobrepõe a config no boot do `KitServiceProvider`, e o painel é montado **antes**.
  Resultado medido no QA-01: `config('kit.registro.verificar_email') === true` e
  `hasEmailVerification() === false` no mesmo processo.
- A tela hoje exibe um `TextEntry` de aviso dizendo que a chave mora no `.env`
  (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:410-413`).

O ponto de extensão que resolve isso está no vendor e é público:
`Panel::emailVerifiedMiddlewareName(string|Closure $name)` (`HasAuth.php:174-178`). O nome
declarado ali é o que `getEmailVerifiedMiddleware()` concatena com a rota de destino
(`HasAuth.php:367-370`), e é ESSA string que entra no array da rota. Trocando o nome pela nossa
classe, o array da rota continua fixo — e passa a apontar para código nosso, que decide por
request.

## Análise dos Arquivos Existentes

### `app/Support/RegistroAberto.php`

Ponto único de leitura de `config('kit.registro.*')`, enforçado por CT-01 de
`tests/Kit/RegistroAbertoTest.php` (varre `app/` com filtro de comentário). O middleware novo
**não** lê `config()`: ele chama `RegistroAberto::exigirVerificacaoDeEmail()`. Nenhum método desta
classe muda — só o docblock, que hoje declara a dívida como não resolvida.

### `app/Providers/Filament/AppPanelProvider.php`

Duas alterações, ambas em `panel()`:
1. `->emailVerification(...)` passa a receber a classe da tela e `isRequired: true` sem condição;
2. `->emailVerifiedMiddlewareName(ExigirEmailVerificado::class)` entra logo depois.

O bloco de comentário longo (linhas 360-409) descreve o mecanismo antigo e precisa ser reescrito —
ele afirma coisas que deixam de ser verdade ("a chave decide se a rota existe").

### `app/Settings/ConfiguracoesDoKit.php`

Ganha a propriedade `registro_verificar_email` e a linha do mapa. O comentário de 12 linhas que
justifica a **ausência** dela no mapa (linhas 203-211) é substituído pela justificativa da
presença, apontando para o middleware.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

`abaRegistro()`: o `TextEntry::make('aviso_verificacao_email')` sai e um `Toggle` entra, com a
mesma condição de visibilidade das outras duas (`$aberto`).

### `database/settings/`

Existe **uma** migration (`2026_08_24_000000_create_kit_settings.php`) e o docblock dela é
explícito: *"Não edite esta migration depois de ela ter rodado em algum lugar — crie outra"*. Logo:
migration nova, com um `add()` e um `deleteIfExists()`.

### `app/Models/User.php`

Já implementa `MustVerifyEmail` (contrato global, exigido pela tela de prompt do Filament). Isso
**não** muda: o que protege `/admin` e `/infra` é aqueles painéis não pedirem verificação, e é
exatamente isso que continua valendo.

## Autorização

- **Policies / Gates**: nenhum novo. A tela de Configurações do Kit já tem a sua permissão.
- **Middleware**: `App\Http\Middleware\ExigirEmailVerificado` — **novo**. Aplicado pelo Filament a
  toda rota de página do painel `app`, via `emailVerifiedMiddlewareName()`. Não vai em
  `bootstrap/app.php`: não é alias e não é global; é o Filament que o injeta.
- **Guards**: nenhum.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/app/email-verification/prompt` | `filament.app.auth.email-verification.prompt` | authMiddleware do painel (passa a nascer **sempre**) |
| GET | `/app/email-verification/{id}/{hash}` | `filament.app.auth.email-verification.verify` | `signed`, `throttle:6,1` (passa a nascer **sempre**) |
| — | todas as rotas de página do `/app` | — | ganham `ExigirEmailVerificado:filament.app.auth.email-verification.prompt` **sempre** |

> RQ-09: as duas rotas passarem a nascer sempre não é efeito colateral, é requisito. Middleware
> que decide por request pode redirecionar em qualquer request; se a rota de destino só existisse
> com a opção ligada no boot, ligar pela tela produziria `RouteNotFoundException` em vez de tela.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ConfiguracoesDoKit` → aba Registro | Filament (Page + Settings form) | `/admin/configuracoes-do-kit` | marca/desmarca "Exigir e-mail validado" e salva | Sim (o toggle só aparece com `registro_habilitado` ligado, via `->live()`) |
| `EmailVerification` (prompt) | Filament (SimplePage, Auth Designer) | `/app/email-verification/prompt` | lê o aviso, pede reenvio, sai | Não |

**Gate de CT-B**: a visibilidade condicional do toggle depende de `->live()`, que é reatividade
Livewire — e isso é **teste de componente**, não de browser (o `04` cobre com `fillForm` +
`assertFormFieldIsVisible`). O barramento e o redirecionamento são HTTP puro. **Não há cenário que
só o navegador prove**, então não haverá `05-casos-de-teste-browser.md`; o motivo fica registrado
no `04`.

**Gate de tela de escrita**: `/admin/configuracoes-do-kit` é tela de escrita e o `04` tem cenário
de gravação por componente (CT-02), que é o que prova que o toggle governa algo.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_REGISTRO_VERIFICAR_EMAIL` | `false` | **mantida.** Passa a ser semeadora e plano B, como as outras 24 propriedades do Settings |

## Eventos / Listeners / Observers

Nenhum novo. `AuditarConfiguracoesDoKit` (ouvinte de `SavingSettings`) já audita a propriedade nova
sem alteração — ele percorre o payload.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Registro aberto** (`registro-e-aprovacao`): é a feature-mãe. `RegistroAberto::registrar()` não
  muda; a opção ligada continua deixando `email_verified_at` nulo e o vendor continua enviando o
  e-mail (`Register.php:167-169`).
- **Convite**: `Convite::aceitar()` grava `email_verified_at` → convidado passa pelo middleware sem
  ser barrado e sem receber e-mail. **Risco medido: nenhum.** Coberto por CT-05.
- **`/admin` e `/infra`**: nenhum dos dois providers chama `emailVerification()`, e o default do
  painel é `isEmailVerificationRequired = false` (`HasAuth.php:56`). O middleware não entra no
  array daquelas rotas. Coberto por CT-08.
- **Base legada**: com a opção ligada, quem não tem `email_verified_at` é barrado — comportamento
  **idêntico** ao que o `.env` já produzia. O comando de reparo do README continua válido e
  continua sendo o caminho. O que muda é o risco de **acionamento acidental**: antes exigia editar
  o `.env` e reiniciar; agora um clique basta. Mitigação: o `helperText` do toggle avisa, e o
  README ganha a advertência ao lado do comando.
- **Tela de Configurações do Kit**: 25 propriedades em vez de 24. `ConfiguracoesDoKitTest` conta
  propriedades? — verificar na revisão profunda (step 5).

## Rollback

- **Migration down**: `deleteIfExists('kit.registro_verificar_email')` na migration nova. Sem a
  linha no banco, `aplicarNaConfig()` não sobrepõe nada dessa chave e o `.env` volta a mandar.
- **Reversão de comportamento**: `git revert` do commit do provider devolve o `if` no array da
  rota. Não há dado migrado a desfazer.
- **Desligamento de emergência sem deploy**: gravar `false` na tela. É justamente o que a feature
  passa a permitir.

## Dependências

Nenhuma nova. `emailVerifiedMiddlewareName()` é API pública do Filament 5.7.6 já instalado, e
`Illuminate\Auth\Middleware\EnsureEmailIsVerified` é do framework.

## Riscos

1. **A string do middleware no array da rota deixa de ser um alias e passa a ser um FQCN com
   parâmetro** (`App\Http\...\ExigirEmailVerificado:filament.app.auth.email-verification.prompt`).
   Mitigação: é a forma que o Laravel resolve há muitas versões (`MiddlewareNameResolver` separa
   `nome:parâmetros` e usa o nome como classe quando não é alias nem grupo). Provado por CT-04 e
   CT-06, que exercitam o barramento e a passagem por HTTP real.
2. **Ligar por acidente numa base com gente dentro.** Mitigação: `helperText` explícito no toggle +
   advertência no README ao lado do comando de reparo. Não se resolve com código: o requisito pede
   que a opção seja editável.
3. **A tela de prompt fica alcançável com a opção desligada.** Aceito e registrado em
   `## Ambiguidades` do `00`. Quem tem `email_verified_at` é redirecionado no `mount()`; quem não
   tem vê uma tela que funciona. Nada é barrado.
4. **Conflito com as três branches paralelas.** `feat/upload-limite-e-tipos` toca `arquivo()` e
   `config/kit.php`; `feat/mais-provedores-sociais` toca `abaLogin()` e `config/services.php`. Esta
   toca `abaRegistro()` e o `AppPanelProvider`. Mitigação: alteração mínima no Settings (uma
   propriedade, uma linha de mapa, um toggle) e **migration nova em vez de editar a existente**.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` já tem o channel `autenticacao` (`daily`), usado por
`RegistroAberto::registrar()`, pelo convite e pelo bloqueio de sessão.

### Decisão

**Reutilizar `autenticacao`.** Um channel novo para um middleware de 15 linhas fragmentaria a
trilha do mesmo assunto em dois arquivos: quem investiga "por que esta pessoa não entra no /app"
quer o registro, o convite e o barramento no mesmo lugar. Ver ADR-04.

## Estrutura de Implementação

### 1. O middleware que decide por request

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Http/Middleware/ExigirEmailVerificado.php` (arquivo novo)
- **Assinatura**:
  ```php
  final class ExigirEmailVerificado extends EnsureEmailIsVerified
  {
      public function handle($request, Closure $next, $redirectToRoute = null);
  }
  ```
- **Lógica**: uma guarda e uma delegação.
  1. `if (! RegistroAberto::exigirVerificacaoDeEmail()) { return $next($request); }`
  2. `return parent::handle($request, $next, $redirectToRoute);`
- **Por que estender em vez de reimplementar**: o `handle()` do Laravel já trata os três casos que
  importam e que ninguém quer reescrever — usuário ausente, usuário que não implementa
  `MustVerifyEmail`, e `expectsJson()` respondendo 403 em vez de redirecionar
  (`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:31-42`).
  Escada do Ponytail: a dependência já está instalada e resolve; o que falta é **uma guarda**.
- **Logs**:
  - barramento: `Log::channel('autenticacao')->warning('[ExigirEmailVerificado@handle] Acesso ao /app barrado por e-mail nao validado | user: {id}', ['user_id' => ..., 'email' => Str::mask(...), 'rota' => $request->route()?->getName(), 'ip' => $request->ip()])`
  - **sem log no caminho liberado**: é todo request de todo usuário do `/app`. Log ali é ruído que
    esconde o sinal e enche o disco.

### 2. O painel aplica sempre e delega a decisão

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AppPanelProvider.php`
- Trocar as linhas 410-413 por:
  ```php
  ->emailVerification(EmailVerification::class)
  ->emailVerifiedMiddlewareName(ExigirEmailVerificado::class)
  ```
  `emailVerification()` com um argumento já entra com `isRequired: true` de tabela
  (`HasAuth.php:110`) — que é o que se quer: o middleware **sempre** no array.
- Import novo: `App\Http\Middleware\ExigirEmailVerificado`.
- Reescrever o bloco de comentário: ele hoje explica um mecanismo que deixa de existir.
- **Sem logs**: é boot de provider, roda em todo request e comando artisan.

### 3. A propriedade e a linha do mapa

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php`
- `public bool $registro_verificar_email;` na seção "Registro aberto".
- `'registro_verificar_email' => 'kit.registro.verificar_email',` em `mapaDeConfiguracao()`.
- Substituir o comentário que justifica a ausência pela justificativa da presença, citando
  `ExigirEmailVerificado`.
- **Sem logs**: `aplicarNaConfig()` já loga as chaves alinhadas.

### 4. A migration de settings

> Skills: `laravel-best-practices`

- **Path**: `database/settings/2026_08_25_000000_add_registro_verificar_email_to_kit_settings.php`
  (arquivo novo — a existente já rodou; ver docblock dela)
- `up()`: `$kit->add('registro_verificar_email', (bool) config('kit.registro.verificar_email', false));`
- `down()`: `$this->migrator->deleteIfExists('kit.registro_verificar_email');`
- Semeia do `.env`, como as outras: quem já tinha `KIT_REGISTRO_VERIFICAR_EMAIL=true` continua com
  a opção ligada depois do `migrate`.

### 5. O toggle na tela

> Skills: `laravel-best-practices`, `tailwindcss-development` (não aplicável — componente padrão)

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`, método `abaRegistro()`
- Remover `TextEntry::make('aviso_verificacao_email')`.
- Acrescentar:
  ```php
  Toggle::make('registro_verificar_email')
      ->label('Exigir e-mail validado no /app')
      ->helperText('...')
      ->visible($aberto),
  ```
- O `helperText` tem de dizer as duas coisas que o README diz: vale para **todo** usuário do
  `/app` (não só os novos), e quem vem de convite nunca é afetado.
- Se `TextEntry` deixar de ser usado no arquivo, remover o import.
- **Sem logs**: `AuditarConfiguracoesDoKit` já registra a gravação.

### 6. Os docblocks que passam a mentir

> Skills: nenhuma — documentação de código

- `app/Support/RegistroAberto.php` — o bloco "**`exigirVerificacaoDeEmail()` ficou FORA do
  Settings, de propósito**" descreve o estado anterior. Reescrever contando como a decisão saiu do
  array da rota.
- `app/Support/ConfiguracaoDoLogin.php:19-20` — cita a chave como o contraexemplo de "chave que não
  pôde virar Settings". Ajustar a citação.
- `config/kit.php` — o bloco de `'verificar_email'` diz "Liga a tela de confirmação"; a tela passa
  a existir sempre.

### 7. Testes

> Skills: `pest-testing`

Ver `04-casos-de-teste.md`. Dois casos existentes **invertem de sinal** e a inversão precisa de
justificativa escrita no `03-progresso.md`:

- `tests/Kit/RegistroAbertoTest.php` — *"exige validacao de email no painel de negocio somente com
  a opcao ligada"* (CT-22b da wiki ancestral): media `hasEmailVerification()` e
  `isEmailVerificationRequired()` pelo boot do provider, esperando `false` com a opção desligada.
  Passam a ser **sempre `true`** — é o que tira a decisão do boot. O caso é reescrito para afirmar
  sobre o que agora é verdade: o middleware declarado é o nosso, e a rota de destino nasce nos dois
  estados.
- `tests/Kit/RegistroAbertoTest.php` — *"mantem as tres chaves de registro no mapa de
  configuracao"*: a asserção `->and($mapa)->not->toHaveKey('registro_verificar_email')` era o
  guardião da dívida. Vira asserção **positiva**.

### 8. README (pt e en) e proposta de rule

> Skills: nenhuma — documentação

- `README.md` §"Validação de e-mail (opcional)" e a linha `F-03c` da matriz de features.
- `README.en.md`, as duas seções equivalentes (linhas ~433 e ~1145).
- O que muda: a chave deixa de ser "só pelo `.env`"; a rota deixa de "só existir com a chave
  ligada"; o comando de reparo continua e ganha a advertência de que agora um clique liga.
- `.ai/rules/settings.md` — a rule afirma *"Não foi feito; é dívida conhecida"*. Vira falsa com
  esta entrega. **Proposta de atualização escrita no `03-progresso.md`, não gravada.**

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.**
> A escada, aplicada a esta feature:
> 1. **Reusar**: `EnsureEmailIsVerified` do Laravel já faz o trabalho — herdar e guardar.
> 2. **Feature nativa antes de código próprio**: `emailVerifiedMiddlewareName()` é ponto de
>    extensão público do Filament. Nada de reescrever `HasRoutes`, nada de macro, nada de
>    `Route::matched`.
> 3. **Uma linha onde cabe**: a decisão da feature é um `if` de duas linhas.
> 4. **Nada especulativo**: sem interface, sem contrato, sem enum de "modos de verificação".
>
> Atalhos deliberados marcados com `ponytail:`.
>
> **Caveman** na conversa com o usuário; arquivos da wiki, código e commits em prosa normal.

## Mapeamentos

| Propriedade do Settings | Chave de `config()` | Consumidor |
|---|---|---|
| `registro_verificar_email` | `kit.registro.verificar_email` | `RegistroAberto::exigirVerificacaoDeEmail()` → `ExigirEmailVerificado::handle()` e `RegistroAberto::registrar()` |

## Testes

> Ver `04-casos-de-teste.md`. Sem `05-casos-de-teste-browser.md` — motivo registrado no `04`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --testsuite=Kit --filter=RegistroAberto --compact`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — base **1016**, não cair
- [ ] `vendor/bin/phpstan analyse` — 0 erros
- [ ] `composer test:browser`

## Commits

- `:sparkles: feat(registro): middleware proprio decide a verificacao de e-mail por request`
- `:sparkles: feat(settings): o toggle de e-mail validado volta a tela, agora com efeito`
- `:white_check_mark: test(registro): a inversao dos dois casos que guardavam a divida`
- `:memo: docs: README pt/en e os docblocks que descreviam a divida`
- `:memo: docs(wiki): wiki da feature verificacao-de-email-editavel`
