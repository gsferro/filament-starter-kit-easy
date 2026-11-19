# Plano de Ação — Permitir ou bloquear o "+" no e-mail do cadastro

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: nenhuma (toca as vias de `wikis/specs/feat/registro-e-aprovacao/`, dos convites e do login social, sem mudar o comportamento delas com a opção permitindo)
- **Motivo**: uma proteção opcional contra muitos cadastros com uma caixa de e-mail só
- **Toca infra compartilhada?**: sim — `config/kit.php`, `ConfiguracoesDoKit` (settings e página), `RegistroAberto`, `Convite`, os dois `UserResource`, os dois formulários de convite, o login social e o componente de perfil do Breezy

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Opção que permite ou bloqueia o "+" | 1, 2 | — |
| RQ-02 | Permitido: cadastro com "+" nas três vias | 3, 4, 5, 6, 7 | é o comportamento de hoje; os passos só não podem quebrá-lo |
| RQ-03 | Bloqueado: regra de validação recusa nas três vias | 1, 3, 4, 5, 6, 7 | — |
| RQ-04 | Sem "+", aceito nos dois estados | 1 | — |
| P-01 | Nasce permitindo | 1, 2 | — |
| P-02 | Opção da instalação, leitor único | 1, 2 | — |
| P-03 | Todas as vias que dão e-mail novo, menos os comandos | 3, 4, 5, 6, 7 | — |
| P-04 | Só a parte local; conta existente intocada | 1, 5, 7 | — |
| P-05 | Erro no campo, motivo no convite em massa, recusa explicada no login social | 3, 4, 6 | — |
| P-06 | Desligada, o convite com "+" para conta nova não se aceita (nem o emitido antes); sem reenvio nem lembrete | 3, 4, 6 | recusa ao abrir o link, no login social e no model; Reenviar oculto; lembrete pula *(alterado em 2026-11-19: Q4 respondida "não" — o convite com "+" para conta nova é recusado no aceite)* |

## Objetivo

Uma opção da instalação, "Permitir "+" no e-mail", ligada por padrão. Desligada, toda via que dá um
e-mail novo a uma conta recusa o endereço com "+" na parte local, com uma mensagem que diz o motivo.

## Análise dos Arquivos Existentes

- `app/Support/RegistroAberto.php`: leitor único das opções de registro (`habilitado()`, `exigirAprovacao()`), e `registrar()` com a guarda `exigirPortaAberta()` para quem não passou pela tela (o login social)
- `app/Settings/ConfiguracoesDoKit.php`: propriedade, linha no `mapaDeConfiguracao()` e migration em `database/settings/` — os três lugares (`.ai/rules/settings.md`)
- `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaRegistro()`: seções `cadastro` e `email-verificado`
- `app/Filament/Pages/Auth/RegistroPorConvite.php:getEmailFormComponent()`: o campo do registro aberto e do aceite de convite (o `CadastroUnificado` herda)
- `app/Models/Convite.php`: `aceitar()` cria a conta; `convidarEmMassa()` valida endereço por endereço com motivos
- `app/Filament/Admin/Resources/Convites/Schemas/ConviteForm.php` e `app/Filament/App/Resources/Convites/ConviteResource.php`: o e-mail do convite
- `app/Filament/{Admin,App}/Resources/Users/UserResource.php:form()`: o e-mail do CRUD
- `app/Http/Controllers/Auth/LoginSocialController.php:retorno()`: cria conta por `criarConta()` ou `criarContaPorConvite()`
- Breezy `PersonalInfo::getEmailComponent()`: a troca de e-mail no perfil, nos três painéis

## Estrutura de Implementação

### 1. O leitor único e a regra

- **Path**: `app/Support/EmailComMais.php` (novo), `config/kit.php`, `.env.example`
- **Atende**: RQ-01, RQ-03, RQ-04, P-01, P-02, P-04
- **Logs**: nenhum (a classe só responde; quem recusa registra)
- **O quê**: `config('kit.email.permitir_mais')`: ausente ou vazia → `true`; senão `filter_var(..., FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true` (o `filter_var` sozinho devolve `false` para a string vazia; irreconhecível permite; P-01) *(alterado em 2026-11-19: QA-02 do gate — o texto anterior não batia com o código)*. A classe é a única que lê a chave, e trata o `null` da chave ausente (projeto com o próprio `config/kit.php`) como permitido: `permitido(): bool`, `temMais(string $email): bool` (o "+" na parte local, antes do último "@"), `aceita(string $email): bool`, `aceitaNoConvite(string $email): bool` (também passa o e-mail de conta existente; P-04), `mensagem(): string`, `mensagemDoConvite(): string` (a `mensagem()` mais o pedido de um convite sem o "+", fonte única da tela do aceite e do login social por convite; *alterado em 2026-11-19 — QA-15*), `regra(?string $atual = null): Closure` e `regraDeConvite(): Closure` — a closure de validação que recusa o e-mail com "+" quando a opção está desligada e ignora o valor igual ao `$atual` (P-04)

### 2. A opção na tela de configurações

- **Path**: `app/Settings/ConfiguracoesDoKit.php`, `database/settings/` (migration nova), `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- **Atende**: RQ-01, P-01, P-02
- **Logs**: o salvamento da página já registra
- **O quê**: propriedade `email_permitir_mais`, a linha no mapa (`kit.email.permitir_mais`) e a migration com `true`. Na aba Registro, uma seção colapsável nova, "E-mail com +", com o toggle e o texto do porquê

### 3. Registro aberto

- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php`, `app/Support/RegistroAberto.php`
- **Atende**: RQ-02, RQ-03, P-03, P-05
- **Logs**: `warning` no `autenticacao`, motivo `email_com_mais`, quando a guarda do `registrar()` recusa; e `warning` `[RegistroPorConvite@mount]` no `autenticacao`, motivo `email_com_mais`, com o id do convite e o e-mail por `Str::mask`, quando o convite com "+" para conta nova é recusado ao abrir o link *(alterado em 2026-11-19 — QA-10)*
- **O quê**: o campo de e-mail ganha a regra só no modo aberto; no modo convite, o convite com "+" para conta nova é recusado no `mount()` (`recusarPorMais()`: aviso com o motivo e login), porque o campo do convite é desabilitado *(alterado em 2026-11-19: Q4 respondida "não" — o convite com "+" para conta nova é recusado no aceite)*; `exigirPortaAberta()` ganha o motivo `email_com_mais` (o chamador que não passou pela tela)

### 4. Convite

- **Path**: `app/Models/Convite.php`, `app/Filament/Admin/Resources/Convites/Schemas/ConviteForm.php`, `app/Filament/App/Resources/Convites/ConviteResource.php`, `app/Filament/Concerns/ConvidaEmMassa.php`, `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php`, `app/Console/Commands/KitConvitesLembrar.php`
- **Atende**: RQ-02, RQ-03, P-03, P-05, P-06
- **Logs**: `warning` `[Convite@aceitar]` no `autenticacao`, motivo `email_com_mais`, quando o `aceitar()` recusa a conta nova com "+"; o convite em massa já registrava os recusados; o lembrete pula sem log *(alterado em 2026-11-19 — QA-10)*
- **O quê**: os dois formulários com a regra, que deixa passar o e-mail de uma conta existente (P-04); `convidarEmMassa()` com o motivo `email_com_mais` e o texto em `motivoLegivel()`, com a mesma exceção; `aceitar()` recusa a conta nova com "+" (`RuntimeException` com a mensagem), o Reenviar some para esse convite (`ConvitesTable`) e o lembrete o pula (`KitConvitesLembrar`); o Revogar, que já existia, é a saída do admin (P-06) *(alterado em 2026-11-19: Q4 respondida "não" — o convite com "+" para conta nova é recusado no aceite)*

### 5. CRUD de usuários

- **Path**: `app/Filament/Admin/Resources/Users/UserResource.php`, `app/Filament/App/Resources/Users/UserResource.php`
- **Atende**: RQ-02, RQ-03, P-03, P-04
- **Logs**: nenhum (erro de validação de formulário)
- **O quê**: o campo de e-mail com a regra, ignorando o e-mail atual do registro na edição

### 6. Login social

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php`
- **Atende**: RQ-03, P-03, P-05
- **Logs**: `warning` no `autenticacao`, motivo `email_com_mais`, e-mail mascarado
- **O quê**: antes de criar conta pelo registro aberto, o e-mail com "+" e a opção desligada recusam com a mensagem do motivo — conferido no controller, antes do `criarConta()`, porque o `catch (RuntimeException)` do `retorno()` trocaria a mensagem pela do convite. Conta existente entra como hoje (P-04); o convite com "+" para conta nova recusa com a mensagem do convite, que pede um convite sem o "+", a mesma da tela do aceite (P-06) *(alterado em 2026-11-19: Q4 respondida "não" — o convite com "+" para conta nova é recusado no aceite; a mensagem do convite pelo QA-15 do gate)*

### 7. Perfil

- **Path**: `app/Livewire/InformacoesPessoais.php` (novo, estende o `PersonalInfo` do Breezy), os três `PanelProvider`. Feito por último, num commit próprio, depois de confirmar que a chave `personal_info` do `myProfileComponents()` substitui o componente padrão
- **Atende**: RQ-03, P-03, P-04
- **Logs**: nenhum
- **O quê**: `getEmailComponent()` do pai com a regra, ignorando o e-mail atual; registrado como `personal_info` no `myProfileComponents()` dos três painéis. A Q2 foi respondida em 2026-11-19: o perfil segue a opção *(alterado em 2026-11-19 — QA-10)*

### 8. Documentação

- **Path**: `docs/{pt,en}/autenticacao/registro-aberto.md`, `CHANGELOG.md`, README (contagens)
- **Atende**: RQ-01
- **Logs**: —
- **O quê**: a opção, o padrão, as vias cobertas e o que fica de fora

## Impacto em Features Existentes

| Feature | O que muda com a opção permitindo (padrão) | O que muda com ela desligada | Prova |
|---|---|---|---|
| Registro aberto (`registro-e-aprovacao`) | nada | o "+" é recusado no campo e no `registrar()` | `RegistroAbertoTest`, `RegistroAbertoTenancyTest`, CT-06, CT-08..CT-10 |
| Convites, um a um e em massa | nada | o "+" para conta nova é recusado; conta existente passa | `ConviteTest`, `ConviteEmMassaTest`, CT-11..CT-15, CT-28 |
| Aceite de convite (tela, social e model) | nada | o convite com "+" para conta nova é RECUSADO, mesmo o emitido antes: a tela leva ao login com o aviso (`RegistroPorConvite::recusarPorMais()`), o login social recusa antes de criar a conta (`LoginSocialController::retorno()`) e o model lança (`Convite::aceitar()`); o convite para conta existente com "+" segue aceito (P-04) *(alterado em 2026-11-19 — QA-10)* | `ConviteTest`, `CadastroSocialPorConviteTest`, CT-16, CT-17, CT-29 |
| Lista de convites do `/admin` (Reenviar oculto) | nada | a ação Reenviar some para o convite com "+" para conta nova; Revogar continua *(alterado em 2026-11-19 — QA-10)* | `ConviteTest`, CT-30 |
| Lembrete automático (`kit:convites-lembrar`) | nada | o convite com "+" para conta nova é pulado, sem lembrete *(alterado em 2026-11-19 — QA-10)* | `ConviteTest`, CT-31 |
| CRUD de usuários (`/admin`, `/app`) | nada | o e-mail novo com "+" é recusado; o atual passa | `PaineisTest`, `AdminDaOrganizacaoTest`, CT-18..CT-20 |
| Login social | nada | a conta nova com "+" não nasce, com aviso próprio | `LoginSocialGoogleTest`, CT-21..CT-23, CT-27 |
| Perfil (Breezy) | o bloco "Informações pessoais" passa a ser a classe do kit, com o mesmo formulário | a troca para um e-mail com "+" é recusada | `PreferenciasDeAparenciaTest` CT-35, CT-24..CT-26 |
| Tela de configurações e `kit:info` | uma seção a mais na aba Registro; uma propriedade a mais (77) | — | `ConfiguracoesDoKitTest`, `KitInfoTest` CT-06 |
| Testes de quem deriva o kit | os helpers do registro e do convite moram no `tests/Pest.php` | — | `HelpersDeTesteTest` |

## Decisões de Desenho

- **Uma chave nova, `kit.email.permitir_mais`, e não `kit.registro.*`**: o docblock de `config/kit.php` reserva `kit.registro.*` ao `RegistroAberto`, e esta opção vale também para convite, CRUD e perfil. Dona única: `EmailComMais` (`.ai/rules/config.md`)
- **Ausente ou vazia → `true` antes do `filter_var(..., FILTER_NULL_ON_FAILURE) ?? true`, e não `(bool) env(..., true)`**: o segundo argumento do `env()` só cobre a chave ausente (`.ai/rules/config.md`), e o `filter_var` devolve `false` para a string vazia; `KIT_EMAIL_PERMITIR_MAIS=` vazio bloquearia por acidente. A direção segura aqui é permitir (P-01) *(alterado em 2026-11-19: QA-02 do gate — o texto anterior não batia com o código)*
- **"Desligado explícito" é o falso que o `FILTER_VALIDATE_BOOLEAN` reconhece: `false`, `0`, `off` e `no`, em qualquer caixa e com espaço em volta; qualquer outro valor (`nao`, `não`, `talvez`) permite** (Q6, decidida aqui como desenho): é a P-01 levada ao fim — só um desligado inequívoco bloqueia, e o valor que o PHP não reconhece cai na ausência de valor, que permite. Ler `nao`/`não` como desligado exigiria uma lista própria de palavras numa chave que as outras do `config/kit.php` não têm, e o erro nessa direção é o barato (uma conta a mais, desfeita pelo admin; ver o Impacto 2 do `04`). Medido em 2026-11-19 com `filter_var(…, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true`: `off`, `OFF`, ` No `, `no`, `false`, `0` → `false`; `nao`, `não`, `talvez` → `true`. O CT-01 testa `false`, `0`, `off`, `no` (minúsculas), vazio, ausente, `true` e `talvez`; a caixa, os espaços e `nao`/`não` estão só medidos, sem caso *(alterado em 2026-11-19 — QA-11)*
- **A regra do Filament é ligada como `->rule(fn (): Closure => EmailComMais::regra(...))`**: o Filament avalia toda closure passada a `rule()`
- **A regra é closure de método estático**, o padrão do projeto (`AdministradorDaInstalacao::regraDeNomeDePapel()`), e não classe em `app/Rules`, que não existe
- **Guarda no servidor além da regra do campo** (`registrar()`): o chamador fora da tela não passa pelo formulário — o mesmo argumento de `exigirPortaAberta()`. O `aceitar()` também ganha guarda, para a conta nova com "+" (P-06, Q4); o login social confere o "+" no controller, antes de criar conta por registro ou por convite *(alterado em 2026-11-19: QA-02 do gate — o texto anterior não batia com o código)*

## Superfície de UI

- Configurações → aba Registro → seção nova "E-mail com +", um toggle
- Erro no campo de e-mail: registro (`/app/register`, `/cadastro`), criação de convite, criação e edição de usuário (`/admin`, `/app`), perfil
- Aceite de convite com "+" para conta nova: ao abrir o link, a pessoa vai ao login com o aviso "Este convite não pode ser aceito" e o motivo *(alterado em 2026-11-19: Q4 respondida "não" — o convite com "+" para conta nova é recusado no aceite)*
- Lista de convites do `/admin`: a ação Reenviar some para esse convite; Revogar continua
- Convite em massa: o endereço com "+" na lista de recusados, com o motivo
- Login social: a tela de recusa com o motivo
