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
| P-06 | O convite já emitido vale | 3, 4, 6 | o modo convite e o login social por convite não ganham a regra |

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
- **O quê**: `config('kit.email.permitir_mais')`: ausente ou vazia → `true`; senão `filter_var(..., FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true` (o `filter_var` sozinho devolve `false` para a string vazia; irreconhecível permite; P-01) *(alterado em 2026-11-19: QA-02 do gate — o texto anterior não batia com o código)*. A classe é a única que lê a chave, e trata o `null` da chave ausente (projeto com o próprio `config/kit.php`) como permitido: `permitido(): bool`, `temMais(string $email): bool` (o "+" na parte local, antes do último "@"), `aceita(string $email): bool`, `aceitaNoConvite(string $email): bool` (também passa o e-mail de conta existente; P-04), `mensagem(): string`, `regra(?string $atual = null): Closure` e `regraDeConvite(): Closure` — a closure de validação que recusa o e-mail com "+" quando a opção está desligada e ignora o valor igual ao `$atual` (P-04)

### 2. A opção na tela de configurações

- **Path**: `app/Settings/ConfiguracoesDoKit.php`, `database/settings/` (migration nova), `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- **Atende**: RQ-01, P-01, P-02
- **Logs**: o salvamento da página já registra
- **O quê**: propriedade `email_permitir_mais`, a linha no mapa (`kit.email.permitir_mais`) e a migration com `true`. Na aba Registro, uma seção colapsável nova, "E-mail com +", com o toggle e o texto do porquê

### 3. Registro aberto

- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php`, `app/Support/RegistroAberto.php`
- **Atende**: RQ-02, RQ-03, P-03, P-05
- **Logs**: `warning` no `autenticacao`, motivo `email_com_mais`, quando a guarda do `registrar()` recusa
- **O quê**: o campo de e-mail ganha a regra só no modo aberto (no modo convite vale a P-06); `exigirPortaAberta()` ganha o motivo `email_com_mais` (o chamador que não passou pela tela)

### 4. Convite

- **Path**: `app/Models/Convite.php`, `app/Filament/Admin/Resources/Convites/Schemas/ConviteForm.php`, `app/Filament/App/Resources/Convites/ConviteResource.php`, `app/Filament/Concerns/ConvidaEmMassa.php`
- **Atende**: RQ-02, RQ-03, P-03, P-05, P-06
- **Logs**: nenhum novo (o convite em massa já registra os recusados)
- **O quê**: os dois formulários com a regra, que deixa passar o e-mail de uma conta existente (P-04); `convidarEmMassa()` com o motivo `email_com_mais` e o texto em `motivoLegivel()`, com a mesma exceção; o `aceitar()` não muda (P-06)

### 5. CRUD de usuários

- **Path**: `app/Filament/Admin/Resources/Users/UserResource.php`, `app/Filament/App/Resources/Users/UserResource.php`
- **Atende**: RQ-02, RQ-03, P-03, P-04
- **Logs**: nenhum (erro de validação de formulário)
- **O quê**: o campo de e-mail com a regra, ignorando o e-mail atual do registro na edição

### 6. Login social

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php`
- **Atende**: RQ-03, P-03, P-05
- **Logs**: `warning` no `autenticacao`, motivo `email_com_mais`, e-mail mascarado
- **O quê**: antes de criar conta pelo registro aberto, o e-mail com "+" e a opção desligada recusam com a mensagem do motivo — conferido no controller, antes do `criarConta()`, porque o `catch (RuntimeException)` do `retorno()` trocaria a mensagem pela do convite. Conta existente entra como hoje (P-04); o convite já emitido também (P-06)

### 7. Perfil

- **Path**: `app/Livewire/InformacoesPessoais.php` (novo, estende o `PersonalInfo` do Breezy), os três `PanelProvider`. Feito por último, num commit próprio (Q2 aberta), depois de confirmar que a chave `personal_info` do `myProfileComponents()` substitui o componente padrão
- **Atende**: RQ-03, P-03, P-04
- **Logs**: nenhum
- **O quê**: `getEmailComponent()` do pai com a regra, ignorando o e-mail atual; registrado como `personal_info` no `myProfileComponents()` dos três painéis

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
| Aceite de convite (tela e social) | nada | nada: o convite emitido vale | `ConviteTest`, `CadastroSocialPorConviteTest`, CT-16, CT-17 |
| CRUD de usuários (`/admin`, `/app`) | nada | o e-mail novo com "+" é recusado; o atual passa | `PaineisTest`, `AdminDaOrganizacaoTest`, CT-18..CT-20 |
| Login social | nada | a conta nova com "+" não nasce, com aviso próprio | `LoginSocialGoogleTest`, CT-21..CT-23, CT-27 |
| Perfil (Breezy) | o bloco "Informações pessoais" passa a ser a classe do kit, com o mesmo formulário | a troca para um e-mail com "+" é recusada | `PreferenciasDeAparenciaTest` CT-35, CT-24..CT-26 |
| Tela de configurações e `kit:info` | uma seção a mais na aba Registro; uma propriedade a mais (77) | — | `ConfiguracoesDoKitTest`, `KitInfoTest` CT-06 |
| Testes de quem deriva o kit | os helpers do registro e do convite moram no `tests/Pest.php` | — | `HelpersDeTesteTest` |

## Decisões de Desenho

- **Uma chave nova, `kit.email.permitir_mais`, e não `kit.registro.*`**: o docblock de `config/kit.php` reserva `kit.registro.*` ao `RegistroAberto`, e esta opção vale também para convite, CRUD e perfil. Dona única: `EmailComMais` (`.ai/rules/config.md`)
- **Ausente ou vazia → `true` antes do `filter_var(..., FILTER_NULL_ON_FAILURE) ?? true`, e não `(bool) env(..., true)`**: o segundo argumento do `env()` só cobre a chave ausente (`.ai/rules/config.md`), e o `filter_var` devolve `false` para a string vazia; `KIT_EMAIL_PERMITIR_MAIS=` vazio bloquearia por acidente. A direção segura aqui é permitir (P-01) *(alterado em 2026-11-19: QA-02 do gate — o texto anterior não batia com o código)*
- **A regra do Filament é ligada como `->rule(fn (): Closure => EmailComMais::regra(...))`**: o Filament avalia toda closure passada a `rule()`
- **A regra é closure de método estático**, o padrão do projeto (`AdministradorDaInstalacao::regraDeNomeDePapel()`), e não classe em `app/Rules`, que não existe
- **Guarda no servidor além da regra do campo** (`registrar()`): o chamador fora da tela não passa pelo formulário — o mesmo argumento de `exigirPortaAberta()`. O `aceitar()` não ganha guarda: o convite já emitido vale (P-06); o login social confere o "+" no controller, antes do `criarConta()` *(alterado em 2026-11-19: QA-02 do gate — o texto anterior não batia com o código)*

## Superfície de UI

- Configurações → aba Registro → seção nova "E-mail com +", um toggle
- Erro no campo de e-mail: registro (`/app/register`, `/cadastro`), aceite de convite, criação de convite, criação e edição de usuário (`/admin`, `/app`), perfil
- Convite em massa: o endereço com "+" na lista de recusados, com o motivo
- Login social: a tela de recusa com o motivo
