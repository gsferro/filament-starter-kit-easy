# Progresso — w3b: registro aberto e aprovação

## 1. `config/kit.php` — o bloco `registro`

- [x] Bloco `registro` com as três chaves, depois de `tenancy`
- [x] Comentário explicando por que `(bool) env()` é seguro e `(int) env()` não é
- [x] Comentário dizendo que ninguém lê estas chaves direto
- [x] `.env.example` com as três chaves comentadas

## 2. Migrations

- [x] `users.aprovacao_pendente` boolean default `false`, depois de `email_verified_at`
- [x] Docblock: por que boolean e não `aprovado_em` nullable (direção do default)
- [x] `tenants.registro_habilitado` boolean default `false`, depois de `ativo`
- [x] `down()` das duas, com a nota de que pendente vira aprovado ao reverter

## 3. `App\Support\RegistroAberto` — o ponto único

- [x] `habilitado()`, `exigirAprovacao()`, `exigirVerificacaoDeEmail()` — marcados como o
      ponto de ligação com o Settings
- [x] `papel()` devolvendo o papel do painel `app`
- [x] `organizacao(?string $slug)` exigindo existe + ativo + registro habilitado
- [x] `registrar(array $dados, ?Tenant $organizacao)` reafirmando as fronteiras
- [x] `email_verified_at` gravado quando a verificação está desligada
- [x] `aprovacao_pendente` gravado quando a aprovação é manual
- [x] vínculo com a organização **antes** da aprovação
- [x] papel atribuído **só** quando não está pendente, no contexto da organização
- [x] `info` de sucesso e `warning` de recusa no channel `autenticacao`, e-mail mascarado

## 4. `App\Models\User`

- [x] `implements MustVerifyEmail`
- [x] `'aprovacao_pendente' => 'boolean'` nos casts
- [x] `aprovacao_pendente` **fora** do `$fillable`
- [x] guarda de pendência como **primeira** instrução de `canAccessPanel()`, com `warning`
- [x] `aprovar()` idempotente, com papel e `info`

## 5. `App\Filament\Pages\Auth\RegistroPorConvite` — os dois modos

- [x] `$layout` redeclarado (regra do kit); NENHUM rename — ver ADR-04
- [x] `mount()` com o garfo por **ausência** de token
- [x] token presente e inválido continua recusando
- [x] tenancy ligada exige organização resolvida
- [x] `getEmailFormComponent()` condicional (desabilitado só no convite)
- [x] `getHeading()` nos dois modos
- [x] `mutateFormDataBeforeRegister()` força o e-mail só no convite
- [x] `handleRegistration()` nos dois modos
- [x] `register()` sobrescrito só para o pendente (trata `null` do throttle)
- [x] docblock da classe abre com os dois modos e a tabela do garfo (substitui o rename)

## 6. `TelaLogin` — o link "Cadastre-se"

- [x] `getSubheading()` devolve o do pai quando o registro está ligado
- [x] docblock atualizado

## 7. `AppPanelProvider` — verificação de e-mail condicional

- [x] `->emailVerification()` condicional pelo ponto único
- [x] bloco de comentário de `:341-377` reescrito
- [x] **afirmação falsa corrigida**: "NENHUM usuário semeado tem `email_verified_at`"
- [x] comentário de `:249-262` atualizado

## 8. `TenantForm` — toggle por organização

- [x] `Toggle` na `Section` de Identificação já existente (corte 2 da auditoria), visível só com o registro global ligado
- [x] `helperText` com o endereço `/app/register?org={slug}`
- [x] `registro_habilitado` no `$fillable` e nos `casts()` do `Tenant`

## 9. Os dois `UserResource`

- [x] coluna de situação (`/app` e `/admin`)
- [x] filtro de pendentes (`/app` e `/admin`)
- [x] `Action::make('aprovar')` com `->authorize('update')` e `->requiresConfirmation()`
- [x] `->visible()` só para pendente

## 10. Testes

- [x] `tests/Kit/RegistroAbertoTest.php` — CT-01…CT-13, CT-15…CT-22b, CT-26
- [x] `tests/Tenancy/RegistroAbertoTenancyTest.php` — CT-14, CT-23, CT-24, CT-25
- [x] `tests/Browser/RegistroAbertoTest.php` — CT-B01, CT-B02
- [x] nenhum helper novo em `tests/Pest.php` sem uso cruzado

## 11. README

- [x] `README.md` → `## Registro aberto e aprovação`, depois de `## Convite de usuário`
- [x] `README.en.md` → `## Open registration and approval`, depois de `## User invitation`
- [x] tabela "o que ligar cada chave faz refletir" (RQ-12) nos dois
- [x] consequência de ligar a verificação de e-mail em base legada, com o reparo
- [x] `## Roteiro de features` → `### Acesso e autenticação`, nos dois idiomas

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse --no-progress` — 0 erros
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — 735 na base, nenhuma queda
- [ ] `composer test:browser`
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] `git push -u origin feat/registro-e-aprovacao`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `Register.php:157-176` é `sendEmailVerificationNotification()` | o método vai de `:161` a `:180`; as duas saídas antecipadas são `:163-165` e `:167-169` | citações corrigidas em `01`, `02` e `04` |
| throttle do registro em `Register.php:71-79` e `:126-148` | `rateLimit(2)` em `:73`, dentro do `try` de `:72-78`; o limitador por e-mail é `:129-148`, chamado em `:80-82` | corrigido em `02` (ADR-09) e `04` (CT-13) |
| transação `:84-107`, login `:105` | transação `:84-102`, evento `:104`, envio `:106`, login `:108` | corrigido em `02` (ADR-10) e `04` |
| **CT-22b é escrevível?** — o plano supôs que dava para montar o painel pelo provider | **confirmado**: `(new AppPanelProvider(app()))->panel(Panel::make())` devolve um painel utilizável fora do boot — medido, `hasEmailVerification() === false`, `hasRegistration() === true`, `isEmailVerificationRequired() === false` | premissa mantida; o `04` já registra as duas alternativas descartadas |
| `pest --agent` disponível para sondagem | **não instalado** (`pestphp/pest-plugin-agent` não está no `composer.json`) | sondagens feitas por `php artisan tinker --execute`; nenhuma dependência nova foi adicionada |
| `phpunit.xml` não fixa `KIT_REGISTRO*` | confirmado — nenhuma das três chaves aparece no arquivo | premissa de CT-26 mantida, e a dependência está declarada no `04` |

### Auditoria Ponytail (step 6) — sub-agente independente

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | **CORTE** o rename `RegistroPorConvite → TelaRegistro`: ~10 arquivos, nenhum RQ pede, e 2 dos arquivos são asserções de prefixo de log de testes do convite | **sim** | ADR-04 reescrita (decisão invertida, registrada como substituição em vez de apagada); `01` passos 5 e Riscos; `03` seção 5 |
| 2 | **SIMPLIFIQUE** o `TenantForm`: `Section::make('Registro')` só para um `Toggle` contraria o arquivo, onde `ativo` (mesma natureza) já vive na `Section` de Identificação | **sim** | `01` passo 8 |
| 3 | **CORTE** CT-16: provava `->unique(ignoreRecord: true)` que já existe e não tem relação com esta feature | **parcialmente** — o cenário ficou, com o oráculo trocado para o risco que **esta** feature cria ("editar pendente não o aprova em silêncio"), que é o par comportamental do CT-02 estrutural. Cortar por inteiro deixaria M18 sem matador comportamental | `04` R4, CT-16, M18, taxonomia |
| 4 | **SIMPLIFIQUE** CT-06: a linha "desligado" do `Esquema` repete `tests/Kit/ConviteTest.php:199`, que já roda sob o default | **sim** — virou cenário simples, só a partição "ligado" (a coexistência, que é o que a feature introduz) | `04` R2 |

Nada apontado em `00-requisito.md` nem em `05-casos-de-teste-browser.md` (o segundo já se
autopoda pela tabela *Cogitado e cortado*).

**Saldo**: −10 arquivos tocados, −1 `Section`, −1 linha de `Esquema`, 0 cenário perdido.

### Auditoria Ponytail do DIFF (pós-implementação) — sub-agente independente

Segunda rodada, sobre o código em vez do plano. Escopo: `app/`, `config/`, `database/`, `tests/`.

| # | Achado | Aplicado? | Onde |
|---|---|---|---|
| 1 | **SIMPLIFIQUE** `RegistroAberto::atribuirPapel()` é a **quinta** cópia do padrão de contexto de papéis (as outras em `Convite`, `UsersRelationManager`, `DemoTenancySeeder`) | **sim, e além** — extraído `App\Support\ContextoDePapeis::em()` e as **quatro** chamadas convertidas, não só a minha. Meia conversão reintroduziria o problema que o achado nomeia (o guard divergindo num dos lugares) | `app/Support/ContextoDePapeis.php`; Notas, achado 7 |
| 2 | **SIMPLIFIQUE** `RecursiveIteratorIterator`/`RecursiveDirectoryIterator` montados à mão para listar `.php` de `app/` | **sim** — `File::allFiles()` | `tests/Kit/RegistroAbertoTest.php` (CT-01) |
| 3 | **SIMPLIFIQUE** `helperText()` concatenando três literais sem motivo | **sim** | `TenantForm.php` |

Nada apontado em `AprovacaoDeCadastro`, `RegistroPorConvite`, `TelaLogin`, `User`,
`AppPanelProvider`, `config/kit.php`, nas duas migrations nem no resto da suíte. O revisor
registrou explicitamente que a **trait** é reuso legítimo (mesma regra de negócio nos dois
painéis) e que os casos de teste, mesmo numerosos, não repetem oráculo entre si — as duas coisas
que eu mais esperava ver questionadas.

**Saldo**: −40 linhas líquidas, −4 cópias de um guard de segurança, +1 primitiva compartilhada.

## Blockers

Abertos pelo **quality gate, ciclo 1** — ver `06-relatorio-qa.md` para repro e evidência.

- **QA-01 (Blocker)** — o toggle *"Exigir e-mail validado"* das `ConfiguracoesDoKit` é **inerte**:
  gravado no Settings, `config()` e `RegistroAberto::exigirVerificacaoDeEmail()` viram `true`, mas o
  painel `/app` já foi montado antes do alinhamento (`KitServiceProvider::boot()`) e nasce sem as
  rotas de verificação. Pelo `.env` funciona. Atinge RQ-02, RQ-09 e RQ-12.
- **QA-02 (Blocker)** — aprovar pelo `/admin` com tenancy ligada grava `panel_user` em
  `Tenant::CONTEXTO_GLOBAL`: a pessoa entra no `/app` (200) e **não vê nada**, e a ação `aprovar`
  se esconde depois, então a mesma tela não conserta. `User::aprovar()` é o único caminho novo que
  atribui papel de `/app` sem `App\Support\ContextoDePapeis`. Atinge RQ-04 e RQ-07.
- **QA-03 (Major)** — `RegistroAberto::registrar()` não reconfere `ativo`/`registro_habilitado` do
  `$organizacao` recebido: chamador direto (job, comando, seeder) cadastra em organização que não
  optou. Atinge RQ-03.

## Débitos Aceitos (quality gate, ciclo 1)

- **QA-04 (Minor)** — a guarda de pendência em `canAccessPanel()` está **correta** (medido: 403 nos
  três painéis, inclusive para `master_global` pendente) e **sem oráculo**: toda persona pendente da
  suíte tem zero papéis, então apagar o bloco mantém a suíte verde. Faltam dois casos: pendente
  **com** papel do painel, e `master_global` pendente.
- **QA-05 (Minor)** — o throttle `5/600s` de `recusar()` funciona (8 GETs ⇒ 5 linhas) e **nenhum CT
  reprova se ele for removido**.
- **QA-06 (Cosmético)** — `RegistroPorConvite::recusar()` e ADR-09 citavam *"QA-01 do relatório desta
  wiki"* quando o `06` não existia. O achado herdado ficou registrado como **QA-00**; ao encostar
  nesses pontos, corrigir a citação.

## Veredito do Quality Gate

- **Ciclo 1: REPROVADO → teste** (2 Blocker, 1 Major, 2 Minor, 1 Cosmético). Cada Blocker começa
  pelo CT que falha; o roteamento completo, as 11 dimensões, as 11 hipóteses rejeitadas e o que
  **não** foi verificado estão em `06-relatorio-qa.md`.

## Desvios do Plano

- **Passo 5 — `getHeading()` do modo aberto mudou de texto.** O plano dizia "Criar conta", que é
  **exatamente** o rótulo do botão de envio
  (`vendor/filament/filament/resources/lang/pt_BR/auth/pages/register.php`,
  `form.actions.register.label`). A tela sairia com o mesmo texto duas vezes e qualquer asserção
  por texto ficaria ambígua. Virou "Criar sua conta" / "Criar sua conta em {organização}".
  Descoberto ao escrever CT-B01.

- **Passo 9 — a coluna, o filtro e a ação viraram uma trait**, `App\Filament\Concerns\AprovacaoDeCadastro`,
  em vez de código repetido nos dois `UserResource`. Não contradiz a ADR-04 de
  `admin-da-organizacao` (que recusou **classe-base** compartilhada porque o que difere entre os
  dois resources é regra de segurança): o que está na trait é UI cuja regra é idêntica nos dois
  painéis por definição. `app/Filament/Concerns/` já era o lugar estabelecido para isso
  (`BadgeContagemNavegacao`, `ConvidaEmMassa`, `DescobreCardsDoPainel`).

- **Passo 9 — entrou um método que o plano não previa**: `papelObrigatorioNaEdicao()`. Ver
  *Notas de Implementação*, achado 1 — é correção de defeito, não escopo novo.

- **CT-23 virou dois casos** (CT-23a e CT-23b) e **CT-17 perdeu uma asserção**. Os dois motivos
  estão nas notas abaixo, achados 4 e 5.

## Notas de Implementação

### 1. Defeito de produto: cadastro pendente era impossível de editar

O campo `roles` é `->required()` nos dois `UserResource`, com uma razão boa ("usuário sem papel é
conta morta"). Mas o cadastro pendente **não tem papel por desenho** — quem o dá é
`User::aprovar()`. Abrir a edição de um pendente e trocar só o nome devolvia *"É obrigatória a
indicação de um valor para o campo papéis"*, e a única saída era atribuir um papel à mão — o que
**dá acesso sem passar pela aprovação** e deixa o registro incoerente (com papel, ainda
pendente).

Corrigido em `AprovacaoDeCadastro::papelObrigatorioNaEdicao()`: obrigatório continua obrigatório,
menos quando o registro está pendente. Na página de **criação** (`$record === null`) segue
obrigatório — criar usuário sem papel pela tela é criar conta morta, que é o que a regra original
protege.

**Achado por CT-16, que existia para outra coisa** (provar que salvar não aprova em silêncio). É
o argumento a favor de escrever o caso mesmo quando ele parece redundante.

### 2. `EditUser`/`EditTenant` em teste recebem `getRouteKey()`, não `getKey()`

`App\Traits\TemUuid` faz o uuid ser a chave de rota. Passar o id numérico devolve
`No query results for model [App\Models\User] 1`, que parece defeito de dado e é defeito de
arranjo.

### 3. Dois cadastros no mesmo caso exigem logout entre eles

`Register::mount()` do Filament começa com `if (Filament::auth()->check()) { redirect() }`
(`Register.php:57-63`), e um cadastro bem-sucedido termina autenticado (`:108`). Sem logout, a
segunda chamada monta um componente que já redirecionou, `$this->form` é nulo, e o `fillForm()`
morre em `getDefaultTestingSchemaName() on null` — mensagem que não tem relação com a causa. O
helper `registrarAberto()` desloga antes, o que também é o que o caso do throttle quer dizer
(cada tentativa é um visitante novo).

### 4. `panel_user` não chega à listagem do `/app`, então CT-23 virou dois

A primeira versão de CT-23 tentava `assertActionHidden()` com um `panel_user` e morria em
*"Invalid Livewire snapshot structure"*: `UserResource::canAccess()` do /app exige `ViewAny:User`,
que o `PapeisSeeder` subtrai do papel comum — o componente **nem monta**.

Isso significa que o `panel_user` **não** falsifica o mutante "a Action nasceu sem
`->authorize()`": ele não abre a tela, então a ação sem autorização passaria igual. CT-23b
acrescentou a persona que falsifica — um papel de leitura (`ViewAny:User` + `View:User`, sem
`Update:User`), criado no próprio caso. Não é cenário artificial: a tela de Papéis do /admin
existe para que quem administra recorte perfis assim.

Sem essa divisão, o checklist leria "coberto" com o mutante intacto.

### 5. Asserção de papel com tenancy usa `papeisEmQualquerContexto()`

`expect($novo->roles)->toHaveCount(1)` devolvia **0** com a pivot correta no banco: a `roles()` do
spatie acrescenta `wherePivot(team_id, getPermissionsTeamId())`, e o contexto corrente no teste é
o global. É a mesma razão pela qual `canAccessPanel()` usa a outra relação
(`.ai/rules/models.md`).

### 6. A lacuna de CT-17 é de arnês, e está declarada

`sendEmailVerificationNotification()` monta a URL com `Filament::getVerifyEmailUrl()`, que resolve
uma rota registrada no **boot** do painel. `config()` ajustado dentro do caso chega tarde, e o
cenário morre em `Route [filament.app.auth.email-verification.verify] not defined`. Tentado e
recusado: declarar a rota à mão (fabricar encanamento de framework para "provar" efeito do
framework) e `refreshApplication()` (derruba o SQLite `:memory:` no meio do caso).

CT-17 passou a afirmar as **duas condições** que o vendor consulta (`Register.php:163-169`):
`email_verified_at` nulo e `User instanceof MustVerifyEmail`. CT-22b prova o encanamento onde ele
é decidido; CT-18 e CT-22 provam a direção que importa para segurança — que nada é enviado quando
não deve.

### 7. Eu escrevi a QUINTA cópia do contexto de papéis — e a auditoria do diff pegou

A auditoria independente do diff (`ponytail-review`) achou que
`RegistroAberto::atribuirPapel()` reimplementava, pela quinta vez no projeto, o mesmo padrão:
`getPermissionsTeamId()` → `setPermissionsTeamId()` → `try/finally` → `unsetRelation('roles')` nas
duas pontas. As outras quatro: `Convite::atribuirPapel()`,
`UsersRelationManager::noContextoDe()`, `DemoTenancySeeder::papelDoApp()` e a minha.

É exatamente a slop que a escada nomeia — reimplementar o que mora alguns arquivos ao lado —, e o
agravante é o assunto: errar o contexto **não dá erro**. Dá alguém que autentica e leva 403, ou um
papel invisível dentro do `/app`. Quatro cópias de um guard cuja divergência não quebra teste
nenhum dos quatro, porque cada um testa o seu.

Extraído para `App\Support\ContextoDePapeis::em()`, com as quatro chamadas convertidas. Cada
chamador mantém a única coisa que é decisão dele: **qual** contexto.

Efeito colateral bom: o registro aberto perdeu um `if (! config('permission.teams'))` que o
`Convite` já havia concluído ser desnecessário — com teams desligado o spatie ignora o team
fixado, então é um caminho para os dois modos. Menos uma ramificação sem efeito para testar.

Verificado com 130 casos nos sete arquivos que cobrem os quatro caminhos, a suíte inteira do
convite incluída.

### 8. PR #24 (`v0.18.10`) mergeou durante a implementação — e não pede nada aqui

A regra nova é: Page e Widget do kit consultam permissão, e Action customizada exige
`->authorize(...)`. Esta feature **não cria Page nem Widget**, e a única Action customizada dela
já nasceu com `->authorize('update')` — permissão que já está na matriz do `PapeisSeeder`. Nada a
acrescentar no rebase. A base de testes passou de 662 para 735.

## Candidatos a Rule (PROPOSTA — decisão do usuário)

> A instrução desta rodada é **propor**, nunca gravar. **Nada foi passado para `record-rule`.**
> Teto de 3 candidatos; cada um foi checado contra os 4 gates e contra o `.ai/rules/index.md`
> existente.

### Candidato 1 — contexto de papel se fixa por `ContextoDePapeis`, nunca à mão

- **Glob**: `app/**`, `database/seeders/**`
- **Nota proposta**: para atribuir ou sincronizar papel fora de um request de painel, use
  `App\Support\ContextoDePapeis::em()`. Não escreva o par
  `setPermissionsTeamId()` / `try-finally` à mão: errar o contexto **não dá erro** — dá alguém que
  autentica e leva 403, ou um papel invisível dentro do `/app`, porque a `roles()` do spatie
  filtra por `wherePivot(team_id, …)`. O `unsetRelation('roles')` vai nas **duas** pontas (o cache
  do Eloquent contamina leitura e escrita). As únicas chamadas legítimas de
  `setPermissionsTeamId()` fora dessa classe são as de **mão única**, que fixam o contexto do
  request inteiro: `DefinirTenantDePermissoes`, `KitServiceProvider` e `AtivadorDeTenancy`.
- **Evidência**: `app/Support/ContextoDePapeis.php`; `03-progresso.md` → Notas, achado 7. O padrão
  existia **cinco** vezes antes desta feature (quatro delas anteriores a ela)
- **Gates**: durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅
- **Enforço automático — este é o candidato que ganha um teste**, e por isso é a
  recomendação: um caso varrendo `app/` e `database/` por `setPermissionsTeamId` fora da
  allowlist de três arquivos + `ContextoDePapeis` transforma a rule em reprovação, e a prosa fica
  só apontando para ela. É a escada do Ponytail aplicada a rule: máquina onde a máquina alcança.

### Candidato 2 — direção do default numa coluna de fronteira de acesso

> Não como rule nova: **como segunda seção dentro de `.ai/rules/config.md`**, que já trata
> exatamente desta família — default silenciosamente errado.

- **Glob** (o da `config.md`, mais): `database/migrations/**`, `app/Models/**`
- **Nota proposta**: coluna que decide acesso (pendência, bloqueio, aprovação) nasce **boolean
  com default `false`**, não timestamp nullable. Com o nullable, "estado ruim" passa a ser o
  default e **todo** caminho existente de criação tem de lembrar de preencher — no kit são seis
  (`UsuarioAdminSeeder`, `UserFactory`, `DemoTenancySeeder`, `Convite::aceitar()`, `kit:admin`, a
  tela de usuários) —, e esquecer não dá erro: dá uma pessoa trancada fora dos painéis, com 403 e
  sem explicação. A coluna fica **fora do `$fillable`**, com teste asserindo isso.
- **Evidência**: `02-decisoes-arquiteturais.md` ADR-06;
  `database/migrations/2026_08_24_000001_add_aprovacao_pendente_to_users_table.php`;
  `tests/Kit/RegistroAbertoTest.php` (CT-02)
- **Gates**: durável ✅ | escopável ✅ | não-inferível ✅ (o padrão do kit hoje é o oposto —
  `aceito_em`, `recusado_em`, `email_verified_at`) | não-redundante ✅
- **Parentesco**: é a mesma família de `.ai/rules/config.md` (*"valor vazio no .env vira 0"*) —
  default silenciosamente errado. Se aprovado, talvez caiba **na** `config.md` como segunda
  seção, em vez de rule nova.

### Candidato 3 — campo obrigatório × estado que não pode tê-lo

- **Glob**: `app/Filament/**`
- **Nota proposta**: ao introduzir um estado em que um campo `->required()` **não pode** estar
  preenchido, o `required` vira condicional no mesmo commit. Senão o formulário fica impossível
  de salvar para exatamente os registros que o estado novo cria, e a saída que a pessoa encontra
  costuma ser pior que o bug — preencher o campo à mão, contornando a transição que o estado
  existia para proteger. Medido: cadastro pendente não tem papel por desenho, e o `roles`
  obrigatório dos dois `UserResource` forçava atribuir papel à mão, dando acesso sem passar pela
  aprovação.
- **Evidência**: `03-progresso.md` → Notas de Implementação, achado 1;
  `app/Filament/Concerns/AprovacaoDeCadastro.php::papelObrigatorioNaEdicao()`
- **Gates**: durável ✅ | escopável ✅ | não-inferível ✅ (o defeito só aparece com o registro
  novo em mão) | não-redundante ✅
- **Enforço automático**: não há — nem PHPStan nem `pest --arch` alcançam. É prosa, com o caso
  concreto ao lado.

### Candidato 4 — persona que não abre a tela não falsifica a autorização da ação

- **Glob**: `tests/**`
- **Nota proposta**: caso que prova *"X não pode executar a ação Y"* precisa de uma persona que
  **abra a tela** e não possa executar. Persona barrada antes, no `canAccess()` do Resource, não
  falsifica o mutante "a Action nasceu sem `->authorize()`" — ela passaria igual com a ação
  liberada para todos. Sintoma no Filament 5: `assertActionHidden()` morre em
  *"Invalid Livewire snapshot structure"*, mensagem que não aponta a causa. Cubra em par: a
  barreira do Resource (`assertForbidden`) e a barreira da Action (persona com leitura, sem
  escrita).
- **Evidência**: `03-progresso.md` → Notas de Implementação, achado 4;
  `tests/Tenancy/RegistroAbertoTenancyTest.php` (CT-23a e CT-23b)
- **Gates**: durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ⚠️ — a
  `.ai/rules/filament.md` já diz que `->authorize()` não é opcional; **isto é o lado do teste**
  dessa regra, e talvez caiba como parágrafo dentro dela ou em `.ai/rules/testes.md`, ao lado de
  *"uma tela aberta não é uma tela que grava"*, que é o parente direto.

**Recomendação**: são **4** propostos e o teto da skill é 3, então a escolha é explícita. Se for um só, o **candidato 1** — é o único que ganha enforço automático (um caso de teste substitui a prosa) e o único cujo defeito já existia no projeto **cinco** vezes. Se forem três, o 1, o 3 e o 4; o **candidato 2** cabe melhor como seção nova dentro da `.ai/rules/config.md` que já existe, e não como rule própria.
único que descreve um defeito que já produziu um caminho de escalada de privilégio, e o único
que nenhuma rule atual insinua.

## Retrospectiva

**Funcionou bem no planejamento**

- **Ler o vendor antes de escrever a decisão.** O briefing desta feature dizia que implementar
  `MustVerifyEmail` faria todo aceite de convite disparar e-mail de verificação, e oferecia a
  saída de entregar a opção desligada com o motivo escrito. Abrir
  `Register.php:161-180` e `Convite.php:591` mostrou que a premissa era **falsa**: o vendor pula
  quem já tem `email_verified_at`, e o convite grava a coluna de propósito. A feature entregou o
  que o requisito pediu em vez de uma justificativa. É literalmente a regra de
  `.ai/rules/specs.md`, e ela pagou o maior dividendo da wiki.
- **O ponto único de leitura antes de saber a API do Settings.** Bloqueio real (a classe vem de
  outra branch) resolvido sem adivinhar nada, e com um teste de varredura protegendo a
  invariante — sem ele, "um lugar só" é intenção, não fato.
- **A auditoria do plano por sub-agente independente.** Cortou o rename da classe, que eu havia
  defendido com um argumento razoável. Era ~10 arquivos e dois deles asserções de log de testes
  do convite — a superfície que a entrega manda não quebrar. Auditar o **plano** (e não só o
  código) foi o que evitou pagar isso.
- **Escrever caso de teste que parecia redundante.** CT-16 existia para provar que salvar não
  aprova em silêncio, e achou um defeito diferente e pior: a edição de um pendente era
  impossível.

**Faltou no plano**

- **O plano não previu colisão de texto com rótulo de vendor.** "Criar conta" era o rótulo do
  botão de envio, em `lang/pt_BR`. Nenhum passo do PRD mandava conferir os rótulos traduzidos
  contra os textos novos, e só o CT-B pegou. Vale como item de checagem em qualquer feature que
  escreva `getHeading()`/`getTitle()`.
- **O plano tratou "editar o cadastro" (RQ-06) como já coberto pela tela existente**, e não
  perguntou se o formulário existente **aceita** o registro que a feature cria. A pergunta que
  faltou é curta: *"o estado novo passa por todas as validações das telas que já existem?"*
- **Três armadilhas de arnês custaram três iterações** (`getRouteKey()` vs `getKey()`, logout
  entre cadastros, `papeisEmQualquerContexto()` vs `roles`). As duas primeiras são conhecimento
  do projeto que não estava em rule nenhuma; a terceira **estava** em `.ai/rules/models.md` e eu
  não a apliquei ao escrever a asserção. Ler a rule não é o mesmo que usá-la.
- **A lacuna de CT-17 era previsível no step 3** e só apareceu na execução. O plano já havia
  identificado que a rota de verificação nasce no boot (é o motivo de CT-22b existir) e não
  ligou os pontos: se a rota não existe, a notificação que precisa dela também não sai.
