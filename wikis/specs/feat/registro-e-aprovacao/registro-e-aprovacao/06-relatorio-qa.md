# Relatório de QA — w3b: registro aberto no /app e aprovação de cadastro

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · ADRs: `02-decisoes-arquiteturais.md`
> Perfil de esforço: **completo** (UI com JS **e** domínio sensível — autorização, porta pública anônima que cria conta)
> Natureza da wiki: **evolução** · Regressão: **sim** (obrigatória — convite, três painéis, matriz de papéis)
> QA por agente independente: não implementou a feature.

## Veredito — Ciclo 1

**REPROVADO → teste** (dois Blocker; a correção de cada um começa pelo CT que falha)

- Blocker: **2** · Major: **1** · Minor: **2** · Cosmético: **1**
- Ambiente: suíte em `sqlite :memory:` (`phpunit.xml`) + boot real de artisan contra banco de
  rascunho migrado do zero · Pest 5.0.5 · Filament v5.7.6 · pcov e xdebug presentes
- Playwright MCP: **não usado** (proibido nesta execução — instância única). Dimensões G e H
  ficaram no nível estático.
- Baseline herdado e conferido: backend 947/947 (2480 asserções), browser 49 casos (44 verdes,
  5 pulados pré-existentes), Pint verde, PHPStan 0.

## Veredito — Ciclo 2: APROVADO

QA-01, QA-02 e QA-03 corrigidos no commit `81d7112` — `app/Providers/Filament/AppPanelProvider.php` (toggle de verificação lido por middleware), `app/Models/User.php::aprovar()` (papel gravado no contexto da organização) e `app/Support/RegistroAberto.php::organizacaoAceitaRegistro()` (reconfere `ativo`/`registro_habilitado` do `$organizacao` recebido).

## Achados

### QA-00 — throttle do log da recusa · herdado do ciclo anterior · já corrigido

Registrado aqui porque **duas referências no repositório citam "QA-01 do relatório desta wiki"**
para um relatório que nunca foi escrito: `RegistroPorConvite::recusar()` (o comentário longo do
throttle) e `02-decisoes-arquiteturais.md:574` (ADR-09). O achado é real e a correção está no
código: `recusar()` era chamável por `curl` anônimo em laço e escrevia uma linha de `warning` por
request no channel `autenticacao` (`daily`, 14 dias) — medido então em 12 GETs / 12 linhas / nenhum
429. Hoje há `rateLimit(maxAttempts: 5, decaySeconds: 600, method: 'recusar')`.
**Reconferido nesta execução**: 8 GETs anônimos em `/app/register` com o registro desligado ⇒ 302
para o login nas 8 e **5** linhas de `warning` gravadas. Corrigido; falta o oráculo (ver QA-05).

### QA-01 — o toggle "Exigir e-mail validado" do Settings é inerte · **Blocker** · destino 3 → 2

- **Dimensão**: A (cobertura do requisito) e J (regressão adjacente)
- **Relacionado a**: RQ-02, RQ-09, RQ-12; ADR-02; `mapaDeConfiguracao()`; CT-22b, CT-38
- **Esperado**: RQ-02 (*"como um settings"*) + RQ-09 + RQ-12 (*"se tiver true, precisa refletir em
  tudo que vem"*). O docblock de `ConfiguracoesDoKit::mapaDeConfiguracao()` afirma que o mapa **é**
  a ligação e que o toggle governa `RegistroAberto`; o do próprio arquivo nomeia o defeito que
  quer evitar: *"a pessoa marca o toggle, salva, e nada acontece. Sem erro nenhum."*
- **Observado**: é exatamente isso que acontece. Gravado `registro_verificar_email = true` no
  Settings, num boot real:
  - `config('kit.registro.verificar_email')` ⇒ `true`
  - `RegistroAberto::exigirVerificacaoDeEmail()` ⇒ `true`
  - `Filament::getPanel('app')->hasEmailVerification()` ⇒ **`false`**
  - `isEmailVerificationRequired()` ⇒ **`false`**
  - `route:list --path=email-verification` ⇒ **nenhuma rota**

  Pelo `.env` (`KIT_REGISTRO_VERIFICAR_EMAIL=true`) as **duas** rotas nascem. Ou seja: das três
  chaves que entraram no mapa, **duas funcionam pelo Settings** (`registro_habilitado` e
  `registro_aprovacao_manual`, lidas a cada request) e **uma não** — justamente a que RQ-09 pede.
- **Repro** (banco de rascunho, sem tocar o do projeto):
  1. `DB_DATABASE=/tmp/qa.sqlite php artisan migrate --force`
  2. `DB_DATABASE=/tmp/qa.sqlite php artisan tinker --execute '$s = app(App\Settings\ConfiguracoesDoKit::class); $s->registro_habilitado = true; $s->registro_verificar_email = true; $s->save();'`
  3. `DB_DATABASE=/tmp/qa.sqlite php artisan tinker --execute 'dump(config("kit.registro"), Filament\Facades\Filament::getPanel("app")->hasEmailVerification());'`
     ⇒ `verificar_email => true` e `hasEmailVerification() => false`
  4. `DB_DATABASE=/tmp/qa.sqlite php artisan route:list --path=email-verification` ⇒ erro "no routes"
  5. o mesmo comando com `KIT_REGISTRO_VERIFICAR_EMAIL=true` ⇒ 2 rotas
- **Causa** (ordem de boot, não de código): `Filament::registerPanel()` adia a construção do painel
  para a primeira **resolução** do `PanelRegistry`
  (`vendor/filament/filament/src/Facades/Filament.php:189-195`), e essa resolução acontece no boot
  do provider de pacote do Filament — **antes** do `KitServiceProvider::boot()`, que é quem sobrepõe
  a config com o banco (`app/Providers/KitServiceProvider.php:132-149`, `configureSettingsDoKit()`).
  Laravel registra todos os providers e só então boota todos, e provider de pacote boota antes de
  provider da aplicação: quando o painel é montado, o valor do banco ainda não chegou. Prova
  cruzada de que o alinhamento roda (e roda tarde): no passo 3 a `config()` já está `true` **e** o
  painel já está `false`.
- **Por que a suíte não pega**: CT-22b monta o painel à mão (`new AppPanelProvider($this->app))->panel(...)`)
  — o que mede a condição sem medir o *momento* em que ela é lida; e CT-38 afirma que a chave está
  no mapa, não que ela governa o painel. Com `RefreshDatabase`, o `boot()` roda antes das
  migrations e o alinhamento é inerte na suíte inteira (é intencional, e aqui esconde o defeito).
- **Ação exigida**: escrever primeiro o CT que falha — um caso que exercite o alinhamento **e**
  pergunte ao painel (ou uma varredura que proíba chave consumida em tempo de boot de painel entrar
  no `mapaDeConfiguracao()`). Depois decidir entre (a) alinhar a config na fase de **register**, e
  não na de boot, aceitando tocar o banco mais cedo (a armadilha que o docblock de `RegistroAberto`
  já previa), ou (b) tirar `registro_verificar_email` do mapa, esconder o toggle e documentar a
  chave como exclusiva do `.env`. **Não** aplicar closure no `isRequired` sem antes ler o vendor: o
  próprio comentário do `AppPanelProvider` registra que o middleware entra por rota
  (`Pages/Concerns/HasRoutes.php:91`), o que é também tempo de boot — a prescrição óbvia pode ser
  no-op (`.ai/rules/specs.md`).

### QA-02 — aprovar pelo `/admin` com tenancy ligada grava o papel no contexto errado · **Blocker** · destino 3 → 2

- **Dimensão**: A, C (matriz de permissão) e I (segurança da superfície nova)
- **Relacionado a**: RQ-04, RQ-05, RQ-07; ADR-08 (alternativa 3); `User::aprovar()`; CT-19b
- **Esperado**: *"a pessoa recebe somente o perfil de acesso ao /app"*. O docblock de
  `User::aprovar()` afirma: *"No /admin (sem tenancy) o contexto é o global. Nos dois casos o
  `assignRole()` grava no lugar certo sem esta função precisar saber onde está."*
- **Observado**: com `permission.teams` ligado a afirmação é **falsa**. `aprovar()` não usa
  `App\Support\ContextoDePapeis` — é o único caminho novo que atribui papel de `/app` sem fixar o
  contexto —, e no `/admin` o contexto corrente é `Tenant::CONTEXTO_GLOBAL`
  (`KitServiceProvider.php:99`). Medido pela **tela real** (`Admin\...\ListUsers`, ação `aprovar`,
  ator `master_global`):
  - `aprovacao_pendente` ⇒ `false` (a aprovação "funcionou", com notificação de sucesso)
  - `model_has_roles` ⇒ `team_id = 0` (global), sendo a organização `id = 1`
  - dentro do `/app`: `roles` ⇒ `[]`, `hasRole('panel_user')` ⇒ `false`, `can('ViewAny:Convite')` ⇒ `false`
  - `GET /app/acme` ⇒ **200** — a pessoa **entra e não vê nada**, que é a falha mais silenciosa
    da área de acesso do kit, nomeada em `UsersRelationManager.php:107-111` e em
    `ContextoDePapeis`
  - e a ação `aprovar` fica **escondida** depois (`->visible(fn ($r) => $r->aprovacao_pendente)`):
    a mesma tela não conserta o que acabou de estragar. O reparo só existe em
    *"Papéis nesta organização"*, no relation manager de organizações.
- **Repro**:
  1. tenancy ligada (`tests/Tenancy`), `kit.registro.habilitado = true`, `aprovacao_manual = true`
  2. `RegistroAberto::registrar([...], $acme)` com `$acme->registro_habilitado = true`
  3. `noPainelBootado('admin')`; `setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL)`; `actingAs($master_global)`
  4. `Livewire::test(Admin\...\ListUsers::class)->loadTable()->callAction(TestAction::make('aprovar')->table($pendente))`
  5. conferir `model_has_roles.team_id` e, com `noPainelDa($acme)`, `hasRole('panel_user')`
- **Por que a suíte não pega**: CT-19b (`tests/Tenancy/RegistroAbertoTenancyTest.php:264`) cobre a
  aprovação **pelo `/app`**, onde o `DefinirTenantDePermissoes` já fixou a organização — e ali a
  asserção de `team_id` passa. Nenhum caso aprova pelo `/admin` com tenancy. ADR-08 discutiu
  *"só no /admin"* e **não** perguntou o que a ação do `/admin` faz quando a tenancy está ligada.
- **Ação exigida**: CT de tenancy que aprove pelo `/admin` e afirme `team_id` da organização (e o
  acesso efetivo, não só o 200). Depois, a transição precisa resolver o contexto a partir do
  **dado**, como o convite já faz — `Convite::contextoDoPapel()` (`Convite.php:779-789`) via
  `ContextoDePapeis::em()` — e não a partir do request. Decidir e registrar o que fazer quando o
  pendente pertence a mais de uma organização.

### QA-03 — `registrar()` reafirma uma guarda e meia: o opt-in da organização não é reconferido · **Major** · destino 3 → 2

- **Dimensão**: A e I
- **Relacionado a**: RQ-03; ADR-07; docblock de `RegistroAberto`
- **Esperado**: o docblock da classe diz que `registrar()` existe para valer também para o
  chamador que não passou pela tela, e nomeia *"as duas guardas (a opção ligada, e a organização
  exigida com tenancy)"*. RQ-03 dá à organização o **direito de optar**
  (`tenants.registro_habilitado`, default `false` por decisão explícita da migration).
- **Observado**: `exigirPortaAberta()` confere apenas `habilitado()` e `$organizacao instanceof
  Tenant`. Um job, comando, seeder ou action que passe um `Tenant` com
  `registro_habilitado = false` — ou com `ativo = false` — **cria a conta e atribui o papel**.
  Quem filtra as três condições é `organizacao(?string $slug)`, que só a tela chama.
- **Repro** (tenancy ligada, registro aberto ligado):
  ```php
  $fechada = Tenant::factory()->create(['ativo' => true,  'registro_habilitado' => false]);
  $inativa = Tenant::factory()->create(['ativo' => false, 'registro_habilitado' => true]);
  RegistroAberto::registrar([...], $fechada); // criou usuário, papel panel_user
  RegistroAberto::registrar([...], $inativa); // criou usuário, papel panel_user
  ```
- **Por que a suíte não pega**: CT-14 mede as quatro linhas negativas **pela URL** (`?org=`), e
  CT-25 mede a recusa do chamador direto **sem** organização. A célula "chamador direto **com**
  organização que não optou" não existe na tabela de decisão.
- **Ação exigida**: CT que chame `registrar()` direto com organização fechada e com organização
  inativa; depois fazer `exigirPortaAberta()` reconferir `ativo`/`registro_habilitado` do
  `$organizacao` recebido (ou aceitar apenas slug e resolver dentro).

### QA-04 — a segunda barreira de pendência não tem oráculo · **Minor** · destino 3

- **Dimensão**: K (adequação da suíte)
- **Relacionado a**: ADR-06/ADR-10; `User::canAccessPanel()`
- **Esperado**: o comentário da guarda diz que ela é *"deliberadamente redundante: ela vale para
  qualquer caminho futuro que marque alguém como pendente, inclusive um que já tenha papel"*.
- **Observado**: o comportamento **está correto** — medido: usuário com `panel_user` marcado
  pendente à mão leva 403 em `/app`, `/admin` e `/infra`, e `master_global` pendente também leva
  403 nos três (a ordem antes do atalho do `master_global` está certa). Mas **nenhum caso de teste
  falsifica a remoção da guarda**: em todos os cenários de pendência do repositório a persona nasce
  do registro aberto e tem **zero** papéis, então o 403 viria do `temPapelDoPainel()` de qualquer
  forma. Apagar o bloco inteiro (e o log dele) mantém a suíte verde.
- **Repro**: `usuarioDoKit('panel_user')->forceFill(['aprovacao_pendente' => true])->save()` e
  `GET /app|/admin|/infra` ⇒ 403 nos três; idem com `master_global`.
- **Ação exigida**: dois casos — pendente **com** papel do painel, e `master_global` pendente. São
  os únicos que matam o mutante "guarda removida" e o mutante "guarda posta depois do atalho".

### QA-05 — o throttle da recusa não tem oráculo · **Minor** · destino 3

- **Dimensão**: K
- **Relacionado a**: QA-00, ADR-09, `RegistroPorConvite::recusar()`
- **Observado**: o throttle funciona (8 GETs ⇒ 5 linhas), e **nada reprova se ele for removido**.
  CT-07 espia o log e afirma que a linha existe — asserção que continua verdadeira sem o limite.
  Um `rateLimit()` apagado, ou `maxAttempts` trocado de 5 para 5000, passa em 947/947.
- **Repro**: com o registro desligado, 8 × `GET /app/register` espiando o channel `autenticacao`
  ⇒ 5 linhas de `warning`, 8 redirecionamentos para o login.
- **Ação exigida**: CT contando as linhas em N > 5 requests (é o caso que o próprio comentário de
  `recusar()` descreve como medido, e que não foi versionado).

### QA-06 — referência a relatório inexistente · **Cosmético** · destino 1

`RegistroPorConvite::recusar()` e ADR-09 (`02-decisoes-arquiteturais.md:574`) citam *"QA-01 do
relatório desta wiki"*, que não existia até este arquivo. Resolvido aqui registrando o achado
herdado como **QA-00** — a numeração deste ciclo começa em QA-01 e as duas citações passariam a
apontar para o achado errado se eu reusasse o número. **Ação**: ao encostar nesses dois pontos,
trocar a citação por `06-relatorio-qa.md` → QA-00.

## Matriz de Rastreabilidade

<!-- Só as linhas com lacuna, mais as três que os achados invalidam. -->

| RQ | Cláusula | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|----|----------|-----------|----|------|--------|-----------|----------|
| RQ-02 | a opção vem de um Settings | 3 | CT-37, CT-38 | — | `mapaDeConfiguracao()` | ⚠️ | **2 de 3 chaves** — `verificar_email` inerte (QA-01) |
| RQ-03 | organização opta | 2,3,5,9 | CT-14, CT-24, CT-25 | — | `organizacao()`, `TenantForm` | ⚠️ | opt-in furado pelo chamador direto (QA-03) |
| RQ-04 | recebe só o perfil do `/app` | 3,4 | CT-08, CT-24, CT-19b | CT-B01 | `RegistroAberto::papel()`, `aprovar()` | ❌ | papel no contexto errado ao aprovar pelo `/admin` (QA-02) |
| RQ-07 | automático ou pendente | 2,3,4,6,7,8 | CT-11, CT-15..CT-19b | CT-B02 | `aprovacao_pendente`, `AprovacaoDeCadastro` | ⚠️ | pendência OK; a liberação falha num dos dois painéis (QA-02) |
| RQ-09 | opção de validação de e-mail | 3,4,5,10 | CT-20..CT-22b | — | `MustVerifyEmail`, `AppPanelProvider` | ⚠️ | funciona pelo `.env`, não pelo Settings (QA-01) |
| RQ-12 | `true` reflete em tudo que vem | 5,6,7,8,10,11 | vários | CT-B01/B02 | — | ⚠️ | "tudo" vale para a origem `.env`; pelo Settings, não (QA-01) |

As demais (RQ-01, RQ-05, RQ-06, RQ-08, RQ-10, RQ-11) fecham com passo, CT e código
correspondentes, conferidos caso a caso contra o código e não contra o mapa declarado. Nenhuma
cláusula ficou **sem** passo — não há omissão silenciosa de requisito nesta wiki; as três falhas
são de fronteira e de oráculo.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 12/12 cláusulas com passo e CT; 3 entregues pela metade (QA-01, QA-02, QA-03) |
| B | Fronteiras e dados | ✅ | `?org[]=`/`?token[]=` (array) caem nos guards `is_string()`; slug vazio/inexistente/inativo/sem opt-in ⇒ mesma recusa; e-mail duplicado barrado pelo `->unique()` do vendor |
| C | Matriz de permissão | ⚠️ | `panel_user` não aprova (CT-23a/b, tenancy); `admin_app` aprova (CT-19b); célula **`/admin` com tenancy** era a não testada — e é QA-02 |
| D | Observabilidade real | ✅ | channel `autenticacao` reusado com justificativa; formato `[Classe@Método]` em todos os pontos; níveis coerentes (`warning` na recusa/negativa, `info` no sucesso); **sem PII**: `Str::mask($email,'*',3)` ⇒ `pen*****************`, senha e token nunca; CT-14 e CT-19 asseguram a máscara |
| E | Performance | ✅ | coluna de situação lê boolean do próprio registro (sem relação, sem N+1); filtro é `where` simples; sem índice **por decisão escrita** na migration (baixa cardinalidade) |
| F | UX de erro | ✅ | recusa genérica e única para os motivos de convite/organização (não revela estado); pendente recebe frase explicativa persistente em vez de 403; `papelObrigatorioNaEdicao()` conserta o formulário impossível de salvar |
| G | Tema e cor | ⏭️ parcial | **nenhum Blade nem CSS no diff**; a única cor nova é `warning`/`success` em `badge()` do Filament, que é token temático. Screenshot nos dois temas não foi feito — Playwright MCP proibido nesta execução |
| H | Acessibilidade | ⏭️ | os dois CT-B usam `assertNoJavaScriptErrors()` (correto para tela de vendor, por `.ai/rules/testes-browser.md`) e não `assertNoAccessibilityIssues()`; não verificado nesta execução |
| I | Segurança da superfície nova | ⚠️ | throttle real na porta pública (2/IP + 2/e-mail, vendor) e na recusa (5/600s, kit); `aprovacao_pendente` e `email_verified_at` fora do `$fillable`; `->authorize('update')` presente na ação; sem IDOR novo. Furos: QA-02 (papel no contexto errado) e QA-03 (opt-in não reafirmado) |
| J | Regressão adjacente | ✅ | ver "hipóteses rejeitadas": convite ponta a ponta verde **com as três chaves ligadas**; `/admin` e `/infra` intocados pelo `MustVerifyEmail`; único achado de regressão é QA-01, e ele é da ligação com o Settings, não do convite |
| K | Adequação da suíte | ⚠️ | **Estático**: nenhum CT sem assertion, nenhum `assertOk()`/`assertNoJavaScriptErrors()` solitário, CT-B com par (path, conteúdo) — oráculo forte. **Medido**: `pest tests/Kit/RegistroAbertoTest.php --mutate --path=app/Support/RegistroAberto.php --covered-only` ⇒ **44 mutantes, score 100%**, nenhum sobrevivente (bem acima do piso de 70%). E é o exemplo perfeito do limite da métrica: os 100% convivem com QA-03, porque a checagem que **falta** (`ativo`/`registro_habilitado` do `$organizacao` recebido) não gera mutante nenhum. Três lacunas de falsificação, todas achadas fora da métrica: QA-04, QA-05 e a que sustenta QA-01 (CT-22b monta o painel à mão e por isso não mede o *momento* da leitura) |

## Débitos Aceitos

- QA-04 (Minor): guarda de pendência sem oráculo — replicar no `03-progresso.md`.
- QA-05 (Minor): throttle da recusa sem oráculo — replicar no `03-progresso.md`.
- QA-06 (Cosmético): citação a QA-01 inexistente — resolvida por QA-00 neste arquivo.
- **Pré-existente, fora do escopo desta wiki** (não conta como achado): o `Select` de papéis do
  `UserResource` do `/admin` grava em `Tenant::CONTEXTO_GLOBAL` por desenho
  (`UsersRelationManager.php:107-111`), então RQ-06 (*"altera a permissão futuramente"*) só entrega
  papel de `/app` utilizável pelo relation manager de organizações. QA-02 é o caso **novo** dessa
  família; a família em si é dívida herdada e é a razão de o padrão merecer varredura.

## Suspeitas Não Confirmadas

- **Deadlock entre pendência e verificação de e-mail com as duas opções ligadas** (o pendente é
  deslogado, e a rota `verify` do Filament é assinada mas o `prompt` exige sessão): o cenário é
  plausível pela leitura de `vendor/filament/filament/routes/web.php:75-84`, mas **não reproduzi**
  o clique no link de verificação por um usuário deslogado. Fica aqui, sem severidade, porque
  suspeita sem repro não é achado.

## Hipóteses Rejeitadas

Todas medidas, não deduzidas. Custaram o mesmo que os achados.

1. **Ligar `MustVerifyEmail` quebrou `/admin` e `/infra`.** Não. Os dois mantêm
   `->emailVerification(null, isRequired: false)` (`AdminPanelProvider.php:269`,
   `InfraPanelProvider.php:409`) e o diff não os toca. Medido com a chave **de fato** ligada por
   env: usuário sem `email_verified_at` ⇒ `/admin` 200, `/infra` inalterado, e só o `/app`
   redireciona para `…/email-verification/prompt`.
2. **Ligar `MustVerifyEmail` quebrou o convite.** Não. Medido com `KIT_REGISTRO=true` **e**
   `KIT_REGISTRO_VERIFICAR_EMAIL=true`: o convidado nasce `hasVerifiedEmail() === true`, com **um**
   papel, sem `VerifyEmail` enviada (`Notification::assertNotSentTo`), e `GET /app` ⇒ 200.
   Mecanismo conferido no vendor: `Register::sendEmailVerificationNotification()` retorna cedo para
   quem já validou (`vendor/filament/filament/src/Auth/Pages/Register.php:163-169`) e
   `Convite::aceitar()` grava `email_verified_at` (`Convite.php:591`).
3. **O contrato ligou a verificação de troca de e-mail do perfil.** Não.
   `hasEmailChangeVerification` nasce `false` (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:40`)
   e seu único consumidor é o `EditProfile` do Filament (`EditProfile.php:238`), que este kit não
   usa (o perfil é o do Breezy).
4. **O contrato mexeu no 2FA forçado do Breezy.** Não. A condição lê `hasVerifiedEmail()`
   (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:121`), e
   esse método já existia pela **trait** de `Illuminate\Foundation\Auth\User` — implementar a
   **interface** não altera nada ali.
5. **Um listener do framework passou a enviar e-mail de verificação em todo registro.** Não. O
   evento disparado é `Filament\Auth\Events\Registered` (`Register.php:104`), não o do Illuminate,
   e não há `SendEmailVerificationNotification` registrado no projeto.
6. **Usuários que já existem numa instalação foram trancados fora.** Não, para os cinco caminhos do
   kit que gravam `email_verified_at` (`UsuarioAdminSeeder.php:45`, `UserFactory.php:30`,
   `DemoTenancySeeder.php:103`, `Convite.php:591`, `KitAdmin.php:204`) — medido: o admin semeado sai
   verificado. **Sim**, para quem foi criado pelas telas de usuários do `/admin` e do `/app`, que é
   comportamento declarado no provider e no README, com reparo documentado.
7. **A porta pública não tem throttle.** Não: `rateLimit(2)` por IP (`Register.php:72-78`) mais 2
   por e-mail (`:129-148`), e CT-13 mede a terceira tentativa. A recusa tem o seu, do kit.
8. **O registrado recebe mais que `panel_user`.** Não: um papel só, medido nas duas suítes, e 403
   em `/admin` e `/infra` (CT-09, esquema de cenário por painel — sem amostragem).
9. **O caminho do convite regrediu.** Não: 947/947 verdes, e reconferido à mão o garfo por ausência
   de token — `?token=lixo` cai em `recusar()` mesmo com o registro aberto ligado (ADR-03
   preservada), e sem token com a opção desligada o comportamento é byte por byte o de antes.
10. **Tabela `settings` ausente derruba comando artisan.** Não: `php artisan migrate --force` contra
    um sqlite **vazio** completou (é o cenário do primeiro `migrate`), porque
    `configureSettingsDoKit()` embrulha o próprio `Schema::hasTable()` em `try/catch (Throwable)`.
    E `AppPanelProvider` só lê `config()` no boot — não passou a tocar o banco.
11. **`?org=` como array derruba a tela.** Não: `is_string()` no `mount()` transforma em `null` e o
    caminho é o da recusa genérica.

## Não Verificado

- **Visual nos dois temas e árvore de acessibilidade** (dimensões G e H): Playwright MCP proibido
  nesta execução. Atenuação: não há Blade nem CSS no diff.
- **Mutation score de `app/Models/User.php`**: rodada disparada e não aguardada até o fim nesta
  sessão. O de `RegistroAberto` está na dimensão K.
- **Confirmação de QA-01 em request HTTP** (foi medido em boot de console: `route:list` e `tinker`).
  A sequência de boot de providers é a mesma nos dois modos, e a prova cruzada (config `true`,
  painel `false`, no mesmo processo) não depende do canal — mas a medição em request real não foi
  feita.
- **Arquivo de log real**: o `phpunit.xml` força `LOG_KIT_DRIVER=monolog` de propósito, então a
  suíte não escreve em `storage/logs`. A dimensão D foi verificada por espião de channel (CT-14,
  CT-19), leitura do código e cálculo da máscara — não pela leitura do arquivo.
- **`/infra` responde 302 para o próprio login** para usuário com papel `infra` em teste HTTP puro.
  É pré-existente, não tocado por esta feature, e não foi investigado.
