# Decisões Arquiteturais — w3b: registro aberto e aprovação

## ADR-01: Aprovação pendente é implementada no kit — nada nativo nem pacote serve

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-08 é explícito: *"pesquise na documentação do filament se já tem algo nativo ou se existe
algum pacote pronto para isso, caso não tenha, implementamos"*. O `CLAUDE.md` proíbe mexer em
dependência sem aprovação, então a pesquisa não é formalidade — ela decide se há diff.

### Decisão

**Implementar no kit**, com uma coluna booleana e uma guarda em `canAccessPanel()`. Nenhuma
dependência nova.

### Alternativas Consideradas

1. **Filament 5, algo nativo** — não existe. `search-docs` sobre `filament/filament` para
   *"registration approval pending user"*, *"registration panel enable registration custom
   register page"* e *"email verification MustVerifyEmail panel"* devolve: página de registro,
   verificação de e-mail, verificação de troca de e-mail, MFA por e-mail e registro de
   **tenant**. Nada sobre moderar cadastro. O que o Filament oferece de mais próximo é
   `->emailVerification()`, que prova posse do endereço — não é aprovação humana, e as duas
   coisas são pedidas separadamente pelo requisito (RQ-07 e RQ-09).
2. **`anselmokossa/filament-sentinel` (instalado)** — recusado: são páginas de erro
   500/403/404/419/503 vestidas com a UI do Filament
   (`vendor/anselmokossa/filament-sentinel/README.md`, e o `src/` tem 4 arquivos: plugin,
   provider, middleware de request-id e um helper). Nada a ver com registro.
3. **`wallacemartinss/filament-onboarding` (instalado)** — recusado: é checklist de
   onboarding **depois** do login, com tours e condições
   (`vendor/wallacemartinss/filament-onboarding/README.md`). Pressupõe usuário que já entra
   no painel; o pendente, por definição, não entra. Usá-lo como portão seria inverter a
   ferramenta.
4. **`jeffgreco13/filament-breezy` 3.2.x (instalado)** — recusado: o `BreezyCore` registra
   perfil, 2FA e middleware de 2FA; `grep -n "registration|register"` em `BreezyCore.php`
   devolve só `register(Panel $panel)` (o hook do plugin) e o registro do middleware de 2FA.
   Não há moderação de cadastro.
5. **Pacote novo de "user approval"** — recusado sem instalar. O que a feature precisa é
   *uma coluna booleana, uma guarda de 6 linhas em `canAccessPanel()`, uma table action e um
   filtro*. Qualquer pacote traz migration própria, model própria, provider, config, tela e
   uma matriz de permissões nova — que no kit significa **entrar na lista de subtração do
   `PapeisSeeder`** ou promover todo `panel_user` a administrador em silêncio
   (`.ai/rules/filament.md`). Custo de manutenção acima do custo do código evitado.

### Consequências

- **Positivas**: zero dependência nova; a fronteira de acesso continua num lugar só
  (`canAccessPanel()`), que é onde o kit já loga negativa; nenhuma permissão nova, logo
  nenhum risco de omissão na subtração do `panel_user`.
- **Negativas**: o kit passa a manter mais uma coluna de fronteira em `users`.
- **Riscos**: se um dia o Filament ganhar moderação nativa, esta implementação vira dívida.
  Mitigação: o estado é **um** boolean e **um** método (`User::aprovar()`) — migrar é trocar
  o armazenamento, não redesenhar o fluxo.

### Referências

- `vendor/anselmokossa/filament-sentinel/src/` (4 arquivos)
- `vendor/wallacemartinss/filament-onboarding/README.md`
- `vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:56-99`
- `.ai/rules/filament.md` § *"Resource, Page ou Widget de administração no painel `app` entra
  na lista de subtração"*

---

## ADR-02: A configuração é lida por UM ponto único — `App\Support\RegistroAberto`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-02 diz que a opção é *"um settings"*. A classe de Settings do kit está sendo criada em
paralelo na branch `feat/settings-do-kit`, que mergeia **antes** desta. Esta branch precisa
entregar o comportamento inteiro sem inventar a API da outra.

### Decisão

Toda leitura de configuração desta feature passa por `App\Support\RegistroAberto`, com três
métodos:

```php
RegistroAberto::habilitado()
RegistroAberto::exigirAprovacao()
RegistroAberto::exigirVerificacaoDeEmail()
```

Hoje os três leem `config('kit.registro.*')`. Quando `feat/settings-do-kit` mergear, **só o
corpo destes três métodos muda** — nenhum outro arquivo. Nenhum outro lugar do código chama
`config('kit.registro.*')`, e essa é a invariante que o CT-01 protege por varredura de
arquivo.

**O ponto de ligação, para quem for costurar**: `app/Support/RegistroAberto.php`, os três
métodos no topo da classe, marcados com o comentário
`// ponytail: ponto único de ligação com o Settings do kit`.

### Alternativas Consideradas

1. **Ler `config()` direto em cada consumidor** (`RegistroPorConvite`, `TelaLogin`,
   `AppPanelProvider`, `TenantForm`, `RegistroAberto::registrar()`) — recusado: cinco arquivos
   a trocar no rebase, e a chance de sobrar um é exatamente a chance de a feature ficar
   metade no Settings e metade no `.env`, sem erro nenhum.
2. **Adivinhar a API do Settings da outra branch** e programar contra ela — recusado: se o
   palpite errar, o rebase quebra em cinco arquivos em vez de um, e o teste que "prova" a
   integração estaria provando um contrato inventado.
3. **Um contrato/interface com duas implementações** (config e settings) — recusado por
   YAGNI: interface com uma implementação por vez, para trocar o corpo de três métodos.

### Consequências

- **Positivas**: o rebase é um arquivo; a leitura defensiva (a base pode não ter a tabela de
  settings durante `migrate`) fica num lugar só.
- **Negativas**: uma indireção a mais para quem lê o código pela primeira vez.
- **Riscos**: alguém acrescentar um `config('kit.registro.*')` fora da classe. Mitigação:
  CT-01 varre `app/` e reprova.

### Referências

- `app/Support/RegistroAberto.php`
- `config/kit.php` (bloco `registro`)
- `.ai/rules/config.md`

---

## ADR-03: Uma tela, dois modos — e o garfo é por AUSÊNCIA de token

**Status**: Aceita
**Data**: 2026-08-24
**Refina**: ADR-01 de `wikis/specs/main/convite-de-usuario/` (*"registro e convite passam a
ser a mesma coisa"*)

### Contexto

Hoje `/app/register` **é** a tela de aceite de convite: `mount()` exige token válido e, sem
ele, recusa. Registro aberto precisa coexistir com isso sem quebrar o convite, que é o fluxo
padrão do kit e tem cobertura em `tests/Kit/ConviteTest.php`,
`tests/Kit/ConviteUsuarioExistenteTest.php` e `tests/Tenancy/ConviteTenancyTest.php`.

A frase que descreve o estado anterior é *"sem token = recusa"*. Com registro aberto,
*"sem token"* passa a ser um caminho legítimo — e essa é a única mudança semântica da
feature.

### Decisão

**Uma tela** (`RegistroPorConvite`), com o garfo no `mount()` decidido pela **presença** do
parâmetro `token`:

| Query string | Caminho |
|---|---|
| `?token=válido` | convite — idêntico ao de hoje, sem consultar o registro aberto |
| `?token=inválido/expirado/usado` | `recusar()` — idêntico ao de hoje, **mesmo com registro aberto ligado** |
| sem `token`, registro aberto **desligado** | `recusar()` — idêntico ao de hoje (o default) |
| sem `token`, registro aberto **ligado** | registro aberto |

O ponto fino: **token presente e inválido continua recusando**. Se o garfo fosse "token
inválido ⇒ cai no modo aberto", `?token=lixo` seria uma segunda porta para o cadastro aberto —
e a recusa é justamente onde vive o throttle de log e a mensagem genérica que não revela se o
token não existe, expirou ou já foi usado (ADR-02 da wiki `convite-de-usuario`).

### Alternativas Consideradas

1. **Duas páginas, duas rotas** — recusado por três motivos concretos: (a) o painel do
   Filament tem **uma** chave `registration`
   (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php`), então a segunda página exigiria
   rota escrita à mão; (b) o layout do Auth Designer é gravado por chave no
   `AuthDesignerConfigRepository` (`AuthDesignerPlugin.php:92-94`) — a segunda tela nasceria
   sem mídia e sem alternador de tema, **sem erro nenhum**, que é exatamente a armadilha
   registrada em ADR-06 da wiki `convite-de-usuario`; (c) duas superfícies públicas para
   auditar em vez de uma.
2. **Uma tela com um seletor de modo visível** ("tenho convite" / "quero me cadastrar") —
   recusado: expõe a existência do mecanismo de convite a visitante anônimo, e o token já
   chega pela URL do e-mail. Zero ganho, superfície de informação a mais.
3. **Manter a decisão em config em vez de na URL** (`if (habilitado) modo aberto; else
   convite`) — recusado: quebraria o convite no dia em que alguém ligasse o registro aberto.
   O convite tem de continuar funcionando com o registro aberto **ligado**, e é a URL que
   distingue os dois.

### Consequências

- **Positivas**: o caminho do convite não muda; o default (`false`) reproduz o comportamento
  de hoje instrução por instrução; uma rota pública, um layout, um throttle.
- **Negativas**: quatro métodos da página passam a ter dois ramos
  (`mutateFormDataBeforeRegister`, `handleRegistration`, `getEmailFormComponent`,
  `getHeading`). Mitigado por CT que exercitam os dois ramos de cada um.
- **Riscos**: alguém "simplificar" o garfo para `if (! $convite) modo aberto`, reabrindo a
  porta por token inválido. Mitigação: CT-05 reprova exatamente isso, e o comentário no
  `mount()` diz por quê.

### Referências

- `app/Filament/Pages/Auth/RegistroPorConvite.php` (`mount()`)
- `app/Providers/Filament/AppPanelProvider.php:212-224`
- ADR-02 e ADR-06 de `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md`

---

## ADR-04: A classe NÃO muda de nome — `RegistroPorConvite` fica

**Status**: Aceita
**Data**: 2026-08-24
**Substitui**: a primeira versão desta ADR, que decidia renomear para `RegistroPorConvite`

### Contexto

A classe passa a atender registro aberto **e** convite, então `RegistroPorConvite` deixa de
descrever metade do que ela faz. A primeira versão desta ADR decidiu renomear para
`RegistroPorConvite`, seguindo a convenção do kit (`TelaLogin`, `TelaBloqueio`).

A auditoria de over-engineering do plano (step 6 da wiki, sub-agente independente) reprovou a
decisão, e com razão.

### Decisão

**Manter `RegistroPorConvite`**, com o docblock da classe reescrito descrevendo os dois modos.

### Por que a decisão virou

Três argumentos, e o terceiro é o que decide:

1. **Nenhuma cláusula do requisito pede o rename.** Ele é diff sem comportamento: ~10 arquivos
   tocados — a página, 3 pontos no `AppPanelProvider`, 4 arquivos de teste e
   `wikis/arquitetura.md:128` — para zero mudança observável.
2. **Dois casos de teste do convite asseram o PREFIXO DE LOG** (`ConviteTest.php:190` e
   `:1017`, ambos `str_starts_with($mensagem, '[RegistroPorConvite@mount]')`). Renomear obriga
   a editar asserção de teste de uma feature que esta wiki não deveria estar tocando.
3. **O rename aumenta o risco exatamente onde ele não pode aumentar.** A instrução desta
   entrega é *não quebrar o caminho do convite*, que é o fluxo padrão do kit. Um rename numa
   superfície de autenticação compartilhada, com edição em 4 arquivos de teste, é risco de
   regressão pago em troca de estética de nome. Renomear é ao mesmo tempo a opção **maior** e
   a **mais arriscada** — é raro as duas coincidirem, e quando coincidem a discussão acaba.

### O que substitui o rename

O argumento a favor dele era real, e continua: *nome que mente sobre metade do comportamento
convida o próximo agente a implementar registro aberto de novo, em outro arquivo*. O que fecha
esse risco sem tocar em nada é o **docblock da classe**, que passa a abrir com os dois modos e
com a tabela do garfo (ADR-03) — quem abre o arquivo lê nas primeiras linhas que a classe
atende as duas portas. Documentação no lugar onde o leitor já vai olhar custa zero arquivo.

### Alternativas Consideradas

1. **Renomear para `RegistroPorConvite`** — recusado pelos três motivos acima. Era a decisão original
   desta wiki, e está registrada como revertida em vez de apagada: quem tiver a mesma ideia
   encontra o custo já medido.
2. **Renomear só o prefixo de log**, mantendo a classe — recusado: o padrão do kit é
   `[Classe@Método]`, e prefixo que não casa com a classe quebra o `grep` que o padrão existe
   para permitir.

### Consequências

- **Positivas**: o diff da feature encolhe ~10 arquivos; nenhum teste do convite é editado; o
  risco de regressão na porta do convite cai a zero por construção.
- **Negativas**: o nome da classe descreve um dos dois modos. Mitigado pelo docblock, e o custo
  real é um leitor surpreso, não um defeito.
- **Riscos**: o próximo agente pode não ler o docblock. Risco aceito, e ele já existia — o
  arquivo tem ~150 linhas de comentário que só funcionam se alguém as ler.

### Referências

- `app/Filament/Pages/Auth/RegistroPorConvite.php` (docblock da classe)
- `tests/Kit/ConviteTest.php:190,1017`

---

## ADR-05: Verificação de e-mail é LIGADA nesta feature — e o convite não é afetado

**Status**: Aceita
**Data**: 2026-08-24
**Refina**: ADR-03 de `wikis/specs/fix/auth-designer-telas/` (que deixou a tela vestida e a
rota desligada)

### Contexto

RQ-09 pede a opção de exigir validação de e-mail ao liberar o registro. O kit não tem
verificação de e-mail: os três painéis fazem `->emailVerification(null, isRequired: false)`,
e o comentário de `AppPanelProvider.php:341-377` explica que ligar dá **500**, porque
`EmailVerificationPrompt::getVerifiable()` declara retorno `MustVerifyEmail`
(`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`)
e `App\Models\User` não implementa a interface.

O risco declarado no briefing desta feature era: *implementar `MustVerifyEmail` faria todo
aceite de convite disparar e-mail de verificação*. **Medido no vendor, esse risco não
existe.**

### Decisão

1. `App\Models\User implements Illuminate\Contracts\Auth\MustVerifyEmail`.
2. No `/app`:
   `->emailVerification(EmailVerification::class, isRequired: true)` **quando**
   `RegistroAberto::exigirVerificacaoDeEmail()`; `->emailVerification(null, isRequired: false)`
   quando não. `/admin` e `/infra` ficam como estão.
3. No registro aberto com a opção **desligada**, `RegistroAberto::registrar()` grava
   `email_verified_at = now()`.

### Por que o convite não dispara e-mail — o fato do vendor

`Register::sendEmailVerificationNotification()`
(`vendor/filament/filament/src/Auth/Pages/Register.php:161-180`) retorna cedo em dois casos:
`! $user instanceof MustVerifyEmail` **e** `$user->hasVerifiedEmail()`.

`Convite::aceitar()` já grava `email_verified_at` antes de devolver o usuário
(`app/Models/Convite.php:591`), com a justificativa escrita em `:583-590`: *o token PROVA
posse do endereço, e pedir verificação depois disso é pedir a mesma prova duas vezes*. Logo o
convidado nasce verificado e o vendor pula o envio.

**A condição não precisa de flag: ela já está no dado.** O mesmo mecanismo condiciona o
registro aberto — verificação desligada ⇒ `forceFill(email_verified_at)` ⇒ vendor pula;
verificação ligada ⇒ coluna nula ⇒ vendor envia. Um `if` no lugar certo, zero override de
método do vendor.

### O middleware, e o que ele barra de verdade

`isRequired: true` faz o Filament acrescentar o middleware de verificação a **cada rota de
página do painel** (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`), e o
`EnsureEmailIsVerified` do Laravel barra qualquer usuário `MustVerifyEmail` sem
`email_verified_at` (`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40`).

Isso é maior que "os recém-registrados": é **todo** usuário do `/app`. Consequência aceita e
documentada no README, com a mitigação medida:

| Caminho que cria usuário no kit | Grava `email_verified_at`? |
|---|---|
| `UsuarioAdminSeeder.php:45` | sim |
| `UserFactory.php:30` | sim (e `unverified()` para o oposto) |
| `DemoTenancySeeder.php:103` | sim |
| `Convite::aceitar()` — `Convite.php:591` | sim |
| `php artisan kit:admin` — `KitAdmin.php:204` | sim |
| `UserResource` (criação pela tela) | **não** |
| `RegistroAberto::registrar()` com verificação desligada | sim |

Numa instalação limpa, ligar a opção não tranca ninguém. Quem cria usuário pela tela do
`/admin` com a opção ligada precisa saber que aquela pessoa recebe o prompt de verificação —
o que é o comportamento correto, não um defeito.

**Correção de fato**: o comentário em `AppPanelProvider.php:372` afirma *"NENHUM usuário
semeado tem `email_verified_at`"*. A tabela acima mostra que isso é falso hoje (5 dos 7
caminhos gravam). A afirmação sustentava a decisão de não ligar. Corrigida junto — é
exatamente o padrão que `.ai/rules/specs.md` manda vigiar.

### Alternativas Consideradas

1. **Entregar a opção desligada com justificativa** ("não dá para condicionar sem quebrar o
   convite") — recusado porque a premissa é falsa. Foi verificada lendo
   `Register.php:161-180` e `Convite.php:591`, não presumida.
2. **Sobrescrever `sendEmailVerificationNotification()` na `RegistroPorConvite`** para não enviar no
   modo convite — recusado: resolve com override o que o dado já resolve, e valeria só para
   quem passa pela tela. `Convite::aceitar()` chamado por job ou comando ficaria de fora.
3. **`isRequired: false` sempre** (tela no ar, sem middleware) — recusado: a opção viraria
   decoração. Verificação que não barra não verifica nada, e RQ-12 pede que o `true` reflita
   em tudo que vem.
4. **Implementar `MustVerifyEmail` somente com a opção ligada** (interface condicional) —
   impossível em PHP, e mesmo que fosse, o `hasVerifiedEmail()` já dá o condicionamento no
   lugar certo.

### Consequências

- **Positivas**: RQ-09 atendido de verdade; a tela de verificação que o PR #21 vestiu passa a
  ter uso; nenhum override de método do vendor; o convite não muda.
- **Negativas**: `User` ganha um contrato que vale nos três painéis. Inerte em `/admin` e
  `/infra`, que não registram o middleware.
- **Riscos**: base legada com usuários sem `email_verified_at` + opção ligada = gente barrada.
  Mitigação: documentado no README com o reparo em uma linha.

### Referências

- `vendor/filament/filament/src/Auth/Pages/Register.php:161-180`
- `vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`
- `vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91,116-118`
- `vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40`
- `app/Models/Convite.php:583-591`
- ADR-03 de `wikis/specs/fix/auth-designer-telas/auth-designer-telas/02-decisoes-arquiteturais.md`

---

## ADR-06: Pendência é um boolean com default `false`, não um `aprovado_em` nullable

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O estado "aguardando aprovação" precisa de armazenamento. A forma idiomática no kit para
"aconteceu em" é timestamp nullable (`convites.aceito_em`, `convites.recusado_em`,
`users.email_verified_at`).

### Decisão

`users.aprovacao_pendente boolean not null default false`, **fora do `$fillable`**.

### Alternativas Consideradas

1. **`users.aprovado_em` timestamp nullable** — recusado, e o motivo é a **direção do
   default**. Com `aprovado_em` nulo significando pendente, **todo** caminho existente de
   criação de usuário passa a nascer pendente e tem de lembrar de preencher a coluna. São
   cinco hoje (`UsuarioAdminSeeder`, `UserFactory`, `DemoTenancySeeder`, `Convite::aceitar()`,
   `KitAdmin`) e mais o `UserResource` — e esquecer não dá erro: dá um usuário trancado para
   fora de todos os painéis, com 403 e sem explicação. É a mesma classe de defeito que
   `.ai/rules/config.md` documenta para `(int) env()`: o default silenciosamente errado.
   Com o boolean, **só quem se registra pela via aberta grava `true`**; todo o resto nasce
   aprovado por omissão, sem uma linha a mais em lugar nenhum. Nenhuma migration de backfill.
2. **Enum de status (`ativo`/`pendente`/`bloqueado`)** — recusado por YAGNI: o requisito pede
   dois estados. Enum com dois valores é um boolean com cerimônia, e o terceiro estado
   (bloqueio) já existe no kit por outra via (remover o papel).
3. **Nenhuma coluna — "pendente" = usuário sem papel** — recusado: usuário sem papel é
   indistinguível de usuário cujo papel foi removido pela tela de papéis, e a tela de
   aprovação não teria como listar quem espera. Estado implícito não se consulta.
4. **Guardar quem aprovou e quando** (`aprovado_por`, `aprovado_em`) — recusado por YAGNI: o
   requisito não pede, e `App\Models\User` já usa `AuditsFillables` com
   `owen-it/laravel-auditing`. Se a auditoria não cobrir (a coluna não é fillable), os logs de
   `User::aprovar()` no channel `autenticacao` guardam `alvo_id` e `executor_id`. **Gatilho
   para reavaliar**: no dia em que alguém precisar de um relatório de aprovações por
   administrador, aí duas colunas se pagam.

### Consequências

- **Positivas**: inerte por construção — a feature pode ser mergeada com o registro aberto
  desligado e nada no kit muda de comportamento; `down()` da migration não perde dado de
  negócio.
- **Negativas**: "quem aprovou" só existe no log e na auditoria, não numa coluna consultável.
- **Riscos**: `aprovacao_pendente` no `$fillable` por descuido abriria mass assignment do
  estado da fronteira. Mitigação: CT-02 assere que a coluna **não** está no `$fillable` — o
  mesmo padrão do CT de `email_verified_at` em
  `tests/Kit/ConviteUsuarioExistenteTest.php:120-122`.

### Referências

- `database/migrations/2026_08_24_000001_add_aprovacao_pendente_to_users_table.php`
- `app/Models/User.php` (`casts()`, `canAccessPanel()`, `aprovar()`)

---

## ADR-07: Com tenancy, a organização vem por query string — e sem ela a tela recusa

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-03: com tenancy ligada e registro liberado, *"também pode optar por habilitar ou não o uso
de register no seu tenant"*. Mas a rota de registro do Filament é do **painel**, não do
tenant: `/app/register` fica fora do grupo de rotas de tenant, então não existe organização no
caminho da URL.

E o `/app` **não** tem `->tenantRegistration()`, ausência deliberada
(`AppPanelProvider.php:360`: *"quem cria tenant é o..."*). Registrar-se NUMA organização não é
criar organização — são coisas diferentes, e só a primeira está no escopo.

### Decisão

- Coluna `tenants.registro_habilitado boolean default false` (opt-in por organização).
- A organização de destino vem de `?org={slug}` — **o mesmo formato que o convite já usa para
  o token**, que é o precedente do kit para "parâmetro que chega por link".
- `RegistroAberto::organizacao($slug)` resolve o tenant exigindo três condições: existe,
  `ativo = true`, `registro_habilitado = true`.
- Com tenancy ligada e sem organização resolvida, a tela **recusa** — o mesmo `recusar()`
  genérico do convite inválido, com throttle e mensagem que não revela qual das três
  condições falhou.
- O `Toggle` no `TenantForm` só aparece quando o registro global está ligado
  (`RegistroAberto::habilitado()`): RQ-03 amarra as duas ("**e** o register estiver
  liberado"), e um toggle inerte é pior que nenhum toggle.

### Alternativas Consideradas

1. **Rota nova dentro do grupo de tenant** (`/app/{tenant}/register`) — recusado: rota de auth
   escrita à mão, fora da configuração do painel, perdendo o layout do Auth Designer
   (ADR-03) e o throttle da página do Filament. Muito diff para trocar `?org=` por `/{slug}/`.
2. **Sem tenant: registrar e deixar a pessoa sem organização** — recusado, e não por estética:
   com tenancy ligada, usuário sem nenhum tenant não tem `/app` para entrar — o Filament
   procura o tenant de destino e não acha. Registrar alguém num estado inalcançável é pior que
   recusar com uma mensagem.
3. **Default `true` em `registro_habilitado`** — recusado: abriria registro em toda
   organização existente no instante em que alguém ligasse a chave global. Opt-in é a única
   leitura de RQ-03 que não decide pelo cliente.
4. **Uma única organização "padrão" configurável** — recusado por YAGNI e por acoplar o
   registro a uma escolha que o requisito não menciona.

### Consequências

- **Positivas**: fail closed em todos os caminhos; uma rota; o toggle é honesto (só aparece
  quando pode funcionar).
- **Negativas**: o link de registro de cada organização precisa carregar o `?org=` — quem
  divulgar `/app/register` cru numa instalação multi-tenant leva as pessoas à recusa.
  Documentado no README e no `helperText` do toggle.
- **Riscos**: `?org=` de organização inativa ou com registro desligado é um oráculo de
  existência? Não: as três condições devolvem a **mesma** recusa, como os três motivos de
  convite inválido (ADR-02 da wiki `convite-de-usuario`).

### Referências

- `app/Providers/Filament/AppPanelProvider.php:360-366`
- `app/Support/RegistroAberto.php` (`organizacao()`)
- `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`

---

## ADR-08: A aprovação mora nos `UserResource` que já existem — nenhum Resource novo

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Alguém precisa ver quem está pendente e aprovar. RQ-06 diz que *"o administrado... ele mesmo
mexe no cadastro e altera a permissão futuramente"* — descrevendo exatamente a tela de
usuários que o kit já tem, nos dois painéis.

### Decisão

Coluna de situação + filtro de pendentes + `Action::make('aprovar')` nos **dois**
`UserResource` (`/admin` e `/app`). Nenhuma Page, Resource ou Widget novo.

A transição de estado é `User::aprovar()`, no **model** — não no corpo da Action.

### Alternativas Consideradas

1. **Um Resource ou Page "Aprovações"** — recusado, e o motivo é caro no kit: entidade nova em
   painel gera permissões novas na matriz do Shield, e **entidade de administração no painel
   `app` precisa entrar em `PapeisSeeder::permissoesDeAdministracaoDoApp()`**. Esquecer não dá
   erro: os dois seeders rodam, tudo fica verde, e todo `panel_user` vira administrador da
   organização (`.ai/rules/filament.md`). Reusar os `UserResource` custa zero permissão nova e
   zero risco de omissão na subtração.
2. **Lógica de aprovação dentro do `->action()` da Action** — recusado por
   `.ai/rules/filament.md` § *"Asserção de identidade vive no model, não na query da tela"*:
   enquanto a página for o único chamador funciona, e o primeiro job, comando ou seeder passa
   por cima. `User::aprovar()` é chamável direto e testado direto.
3. **Só no `/admin`** — recusado: com tenancy ligada, quem aprova é quem administra **a
   organização** (`admin_app`), e essa pessoa não entra no `/admin`.
4. **Aprovação em massa (bulk)** — recusado por YAGNI. Aprovar é decisão individual sobre
   quem entra; a versão em lote é a que se usa sem ler.

### Consequências

- **Positivas**: nenhuma permissão nova, nenhuma alteração no `PapeisSeeder`; a autorização
  cai na `UserPolicy::update` que já existe, e `panel_user` já não a tem.
- **Negativas**: a coluna de situação aparece para quem nunca vai ver um pendente (instalação
  com registro desligado). Aceito: o badge diz "Ativo", que é informação honesta.
- **Riscos**: `Action` do Filament **não** consulta policy sozinha — o default de
  `$authorization` é `null`, ou seja, liberada para todo mundo
  (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:15-22`, que diz isso em comentário
  no próprio vendor). Sem `->authorize('update')`, qualquer um que abra a listagem aprova.
  Mitigação: a linha está no plano como obrigatória, e CT-17 reprova sem ela.

### Referências

- `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:15-22`
- `.ai/rules/filament.md`
- ADR-04 e ADR-08 de `wikis/specs/main/admin-da-organizacao/02-decisoes-arquiteturais.md`

---

## ADR-09: O throttle do registro é o do vendor; o da recusa continua sendo o do kit

**Status**: Aceita
**Data**: 2026-08-24
**Refina**: QA-01 de `wikis/specs/main/convite-de-usuario/06-relatorio-qa.md`

### Contexto

Registro aberto é superfície **anônima que cria conta**. Precisa de limite, e o kit já tem
dois mecanismos na mesma tela — que protegem coisas diferentes e são fáceis de confundir.

### Decisão

Nada novo. Os dois que já existem cobrem os dois caminhos:

| Caminho | Limite | Origem |
|---|---|---|
| envio do formulário (`register()`) | `rateLimit(2)` por IP **+** 2 por e-mail (`filament-register:{sha1(email)}`) | vendor, `Register.php:72-78` e `:129-148` |
| recusa no `mount()` (`recusar()`) | 5 por 600 s por IP | kit, `RegistroPorConvite::recusar()` |

O throttle da recusa protege o **log**, não a resposta — a justificativa completa está no
comentário de `recusar()`, medida em QA-01: 12 GETs anônimos escreviam 12 linhas de `warning`
no channel `autenticacao` (driver `daily`, 14 dias, o mesmo arquivo que o Logs Explorer do
`/infra` abre) sem nenhum 429.

O throttle do formulário protege a **criação de conta**, e é o do vendor porque é o mesmo que
o kit já herda para o aceite de convite: 2 tentativas por IP e por e-mail é agressivo para
cadastro e é o default do Filament.

### Alternativas Consideradas

1. **Middleware `throttle` na rota de registro** — recusado: a rota é registrada pelo painel,
   não pelo kit; e barraria por IP quem tem convite **válido** por causa do vizinho de NAT — o
   argumento já registrado em `recusar()`.
2. **Um throttle próprio no `register()`** — recusado: duplicaria o do vendor, com outra chave
   e outro número, e a próxima pessoa teria de descobrir qual dos dois disparou.
3. **CAPTCHA** — recusado: dependência nova (proibida sem aprovação), e a superfície já tem
   dois limites, papel único e — opcionalmente — verificação de e-mail e aprovação humana.
   **Gatilho para reavaliar**: registro aberto ligado em produção com abuso medido.

### Consequências

- **Positivas**: zero código de throttle novo; o comportamento é o mesmo do aceite de convite,
  que já foi auditado.
- **Negativas**: 2 tentativas por janela é apertado — quem errar a confirmação de senha duas
  vezes espera. É o default do Filament, e vale igual para o convite hoje.
- **Riscos**: o limite por e-mail usa `RateLimiter` no store de cache; com `CACHE_STORE=array`
  (o do `phpunit.xml`) ele é por processo. Afeta teste, não produção — e é o que torna CT-20
  possível de escrever.

### Referências

- `vendor/filament/filament/src/Auth/Pages/Register.php:72-78,129-148`
- `app/Filament/Pages/Auth/RegistroPorConvite.php` (`recusar()`)
- QA-01 de `wikis/specs/main/convite-de-usuario/06-relatorio-qa.md`

---

## ADR-10: O pendente é deslogado depois do registro, não impedido de logar

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

`Register::register()` do vendor termina com `Filament::auth()->login($user)` +
`session()->regenerate()` e devolve um `RegistrationResponse`, que redireciona ao painel
(`Register.php:106-112`). Um usuário pendente não pode entrar em painel nenhum.

### Decisão

Sobrescrever `register()` na `RegistroPorConvite` **só** para o caso pendente: chamar
`parent::register()` (que faz throttle, transação, evento e login) e, se o usuário autenticado
estiver pendente, desfazer — `logout()`, `session()->invalidate()`, `regenerateToken()` —,
notificar *"Cadastro recebido, aguarde a aprovação"* e redirecionar ao login.

Aqui `redirect()` é seguro: estamos numa **ação** Livewire. A armadilha documentada em
`recusar()` (redirect solto devolve o Redirector do Livewire onde o Laravel espera código
HTTP, e o request morre em 500) é específica do `mount()`.

### Alternativas Consideradas

1. **Deixar o vendor logar e o painel barrar** — recusado por UX: `canAccessPanel()` falso
   produz um **403** para quem acabou de se cadastrar com sucesso. A pessoa não fez nada
   errado; ela precisa de uma frase, não de um código de erro.
2. **Reescrever `register()` do zero** sem chamar o pai — recusado: jogaria fora throttle,
   transação, `saveRelationships()`, o evento `Registered` e a regeneração de sessão, para
   mudar o último passo.
3. **Não logar (sobrescrever o login)** — o vendor não expõe gancho entre `handleRegistration`
   e `login`; `afterRegister` roda **dentro** da transação, antes do login. Desfazer depois é o
   único ponto de extensão que não reescreve o método.
4. **Barrar no login em vez de no registro** — necessário de qualquer forma (a pessoa pode
   tentar entrar depois), e é o que a guarda de `canAccessPanel()` faz. As duas coisas
   coexistem: `canAccessPanel()` é a barreira; o `register()` é a **mensagem**.

### Consequências

- **Positivas**: a pessoa sai da tela sabendo o que aconteceu; a sessão não fica autenticada
  para um usuário que não pode nada.
- **Negativas**: um método do vendor sobrescrito, com a fragilidade normal de acoplamento a
  versão. Mitigado por CT-11 (pendente não fica autenticado) e CT-12 (a notificação).
- **Riscos**: `parent::register()` devolve `null` quando o throttle dispara — o ramo pendente
  precisa checar `null` antes de olhar o usuário, senão o throttle vira 500.

### Referências

- `vendor/filament/filament/src/Auth/Pages/Register.php:70-113`
- `app/Filament/Pages/Auth/RegistroPorConvite.php` (`register()`, `recusar()`)
