# Casos de Teste — w3b: registro aberto no /app e aprovação de cadastro

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando implementação — ela não existe.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — porta pública de registro (`/app/register` sem token) | 3 | 3 | **9** | completo |
| A2 — papel e acesso do recém-registrado | 2 | 3 | **6** | padrão (técnica escalada, ver abaixo) |
| A3 — pendência de aprovação e a aprovação em si | 3 | 3 | **9** | completo |
| A4 — verificação de e-mail | 3 | 2 | **6** | padrão |
| A5 — organização por tenant | 2 | 3 | **6** | padrão |
| A6 — configuração e ponto único de leitura | 1 | 2 | **2** | mínimo |

**Técnica escalada acima do perfil da área**: A2 é `padrão`, mas a regra R3 ("nenhum outro
perfil ou acesso") usa **matriz papel × painel completa** (3 painéis × 2 personas), porque
RQ-05 é uma cláusula de autorização negativa e amostrar painel deixaria justamente o painel
não amostrado sem barreira.

- Técnicas aplicadas: EP, BVA (contagem de tentativas do throttle), **tabela de decisão**
  (token × registro habilitado × organização), **tabela estado × operação** (pendente ×
  entrar/aprovar/editar), **matriz papel × painel**, rastreio de efeito (log, notificação de
  verificação), normalização não se aplica (nenhum identificador novo).
- Cenários: **26** (CT-01…CT-26) · Regras: **9** · Mutantes previstos: **31** · Sem
  matador: **2** (declarados).

## Divergência com a skill (Project Rule vence)

- A skill sugere `pest --parallel --tia` como padrão. Neste projeto o `tests/Pest.php` já liga
  `pest()->tia()->locally()`, e `.ai/rules/testes-browser.md` + o `composer.json` proíbem
  `--parallel` na suíte de browser. O comando desta feature é
  `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` + `composer test:browser`.
- A skill sugere `pest --mutate`. `pestphp/pest-plugin-mutate` **está** declarado em
  `require-dev` do `composer.json` (não é transitivo). Roda, mas exige driver de cobertura.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | `App\Support\RegistroAberto`; 2 migrations (`users.aprovacao_pendente`, `tenants.registro_habilitado`); `App\Models\User` (contrato, cast, guarda, `aprovar()`); `TelaRegistro` (renomeada); `TelaLogin`; `AppPanelProvider`; 2 `UserResource`; `TenantForm`; bloco `kit.registro` | CT-01, CT-02, CT-25, CT-26 |
| **F** | ligar/desligar registro; resolver organização; criar usuário; atribuir papel; marcar pendência; aprovar; exigir verificação de e-mail; recusar; oferecer o link no login | CT-03…CT-24 |
| **D** | dado que ENTRA: nome, e-mail, senha, `?token`, `?org`. Dado que JÁ EXISTE: usuário com o mesmo e-mail, convite pendente para o mesmo e-mail, organização inativa, organização com registro desligado, usuário legado sem `email_verified_at`. Dado de OUTRO tenant: organização cujo registro está desligado | CT-05…CT-09, CT-14…CT-18, CT-22 |
| **I** | uma rota HTTP pública (`GET /app/register`); um componente Livewire (`register`); duas Actions de tabela; **e o chamador direto** — `RegistroAberto::registrar()` e `User::aprovar()` chamados fora da tela (job, comando, seeder) | CT-10, CT-11, CT-19, CT-21 |
| **P** | SQLite em memória na suíte; `CACHE_STORE=array` (o `RateLimiter` do limite por e-mail é por processo — é o que torna CT-13 escrevível); `MAIL_MAILER=array`; o channel `autenticacao` com `LOG_KIT_DRIVER=monolog` | CT-13, CT-20 |
| **O** | visitante anônimo; visitante anônimo em laço (abuso); pessoa que já tem conta; administrador da instalação (`master_global`/`admin`); administrador da organização (`admin_app`, só em `tests/Tenancy`); usuário comum (`panel_user`) tentando aprovar | CT-12, CT-13, CT-19, CT-21, CT-23 |
| **T** | **não se aplica além do já coberto**: a feature não tem expiração, agendamento nem janela de tempo próprios. A única grandeza temporal é `email_verified_at`, e o que importa dela é **nulo vs. preenchido**, não o instante — nenhuma comparação de data é feita pelo kit (quem compara é `hasVerifiedEmail()`, do framework, que testa nulidade). Concorrência: duas aprovações simultâneas do mesmo usuário são idempotentes por construção (CT-21), e dois registros simultâneos do mesmo e-mail são barrados pelo `unique` de `users.email`, que já existe e já tem cobertura no convite | CT-21 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — sem token, o registro só existe quando a opção está ligada | A1 (completo) | RQ-01, RQ-11, RQ-12 | tabela de decisão | CT-03, CT-04, CT-05 |
| **R2** — o caminho do convite não muda, com a opção ligada ou desligada | A1 (completo) | RQ-01, RQ-12 | tabela de decisão + regressão | CT-06, CT-07 |
| **R3** — o registrado recebe `panel_user` e **nada além** | A2 (padrão↑) | RQ-04, RQ-05 | matriz papel × painel | CT-08, CT-09, CT-10 |
| **R4** — com aprovação manual, o cadastro nasce pendente e não entra em painel nenhum | A3 (completo) | RQ-07, RQ-12 | tabela estado × operação | CT-11, CT-12, CT-15, CT-16 |
| **R5** — a aprovação libera o acesso, é idempotente e exige quem pode | A3 (completo) | RQ-06, RQ-07 | estado × operação + matriz papel × ação | CT-19, CT-20, CT-21, CT-23 |
| **R6** — a verificação de e-mail é opcional e nunca alcança quem veio de convite | A4 (padrão) | RQ-09, RQ-12 | rastreio de efeito | CT-17, CT-18, CT-22 |
| **R7** — com tenancy, só organização ativa e com registro ligado aceita registro | A5 (padrão) | RQ-03 | tabela de decisão | CT-14, CT-24 |
| **R8** — a porta pública é limitada e registrada | A1 (completo) | RQ-12 (superfície nova) | BVA na contagem + rastreio de efeito | CT-13, CT-20 |
| **R9** — a configuração é lida por um ponto único e o default é `false` | A6 (mínimo) | RQ-02, RQ-11 | EP + varredura de arquivo | CT-01, CT-02, CT-25, CT-26 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `RegistroAberto::habilitado()`, `registrar()`, `User::aprovar()` | escolha de implementação | detalhe do cenário (o cenário chama o método, mas o `Então` afirma sobre estado/papel/log) |
| nome da coluna `aprovacao_pendente` | escolha de implementação (ADR-06) | detalhe. O `Então` de CT-11 afirma **"não entra em painel nenhum"**, não "a coluna é `true`" |
| `?org={slug}` como forma de passar a organização | escolha de implementação (ADR-07) | detalhe. O `Então` de CT-14 afirma o **vínculo com a organização**, não o formato da URL |
| rótulos "Pendente"/"Ativo" e o texto da notificação de aprovação pendente | comportamento visível que o requisito **não** determina | **pergunta ao usuário** (abaixo). Os cenários afirmam a **existência** da mensagem e o estado, nunca a string exata |
| classe renomeada para `TelaRegistro` e prefixo de log `[TelaRegistro@…]` | escolha de implementação (ADR-04) | detalhe. CT-20 afirma o **channel, o nível e os campos do context**, não o prefixo |
| `panel_user` como "o perfil de acesso ao /app" | o requisito **também** o determina ("somente o perfil de acesso ao /app") — o PRD só deu o nome | **pode** virar `Então` |
| default `false` | o requisito o determina literalmente ("o default é false") | **pode** virar `Então`, e CT-26 usa o valor de fábrica |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- O texto exato da mensagem ao pendente e os rótulos da coluna de situação não estão no
  requisito. **Premissa**: existe mensagem que diz que o cadastro foi recebido e aguarda
  aprovação, e existe um rótulo distinguível para pendente. Cenários afetados: CT-12 e CT-19,
  marcados `@premissa`, afirmam **que há notificação/rótulo distinguível**, não a string.
- Quem pode aprovar: o requisito diz "o administrado". **Premissa**: quem tem
  `Update:User` no painel — isto é, `admin`/`master_global` no `/admin` e `admin_app` no
  `/app`; `panel_user` não. CT-23 é o cenário `@premissa` da negativa.

## Setup Global

### Personas

- `usuarioDoKit('master_global')` — administrador da instalação (helper de `tests/Pest.php`).
- `usuarioDoKit('admin')` — administrador do `/admin`.
- `usuarioCom(null)` — **usuário sem papel nenhum**; a persona que prova RQ-05 por
  construção.
- `usuarioComPapel('admin_app', $tenant)` — só em `tests/Tenancy` (`.ai/rules/testes.md`: o
  papel `admin_app` **não existe** em `tests/Kit`; usá-lo lá morre em `RoleDoesNotExist`).
- `usuarioComPapel('panel_user', $tenant)` — usuário comum da organização.

### Fixtures

- `Tenant::factory()` / o helper `tenant('Acme', 'acme')`.
- `Convite::factory()` + o helper local `conviteCom($papel, $tenant, $email)` de
  `tests/Kit/ConviteTest.php` — **não reutilizar entre arquivos** sem mover para
  `tests/Pest.php` (`.ai/rules/testes.md`); os arquivos novos declaram o próprio helper ou
  criam o convite inline.
- `beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` — sem os
  dois, `panel_user` não existe e o arranjo morre.

### Fakes

- `Notification::fake()` nos cenários de verificação de e-mail (R6).
- `espiarAutenticacao()` (helper de `tests/Pest.php`) nos cenários de log (R8).
- Sem `Http::fake()` — a feature não faz request externo.

### Estratégia de DB

`RefreshDatabase` global, aplicado em `tests/Pest.php` por suíte. `tests/Kit` roda
single-tenant; `tests/Tenancy` roda com `permission.teams` ligado desde
`TenancyTestCase::createApplication()`.

### A armadilha de arnês desta feature — e o que foi tentado

`config(['kit.registro.habilitado' => true])` **funciona** para tudo que é lido em tempo de
execução: `TelaRegistro::mount()`, `TelaLogin::getSubheading()`, `RegistroAberto::registrar()`,
`TenantForm`. Não funciona para o **registro de rotas** da verificação de e-mail, que o
`AppPanelProvider` decide durante o boot (`vendor/filament/filament/routes/web.php:75-84`, sob
`if ($panel->hasEmailVerification())`, e `hasEmailVerification()` é
`filled($this->getEmailVerificationPromptRouteAction())` — `HasAuth.php:620-623`).

Tentado e descartado:

1. **Closure em `->emailVerification(fn () => …)`** — a assinatura aceita
   (`HasAuth.php:110`), mas `filled(Closure)` é sempre `true`, então a rota nasceria **sempre**.
   Não serve.
2. **Um `TestCase` novo com `KIT_REGISTRO_VERIFICAR_EMAIL` em `createApplication()`** —
   funcionaria (é o padrão de `Tests\TenancyTestCase`), mas o Pest não permite dois `TestCase`
   na mesma pasta: exigiria `tests/RegistroVerificado/` e uma quarta suíte no `phpunit.xml`,
   para um único cenário.
3. **Adotado**: CT-22 exercita a decisão **onde ela é tomada**, construindo o painel pelo
   próprio provider (`(new AppPanelProvider($this->app))->panel(Panel::make())`) e afirmando
   `hasEmailVerification()` e `isEmailVerificationRequired()` nas duas partições da config.
   Mede a mesma condição que a rota consome, sem quarta suíte.

---

## Regra R1 — sem token, o registro só existe quando a opção está ligada

> `RQ-01`, `RQ-11`, `RQ-12` · perfil **completo** · técnica: **tabela de decisão**

### Tabela de decisão

| # | `?token` | registro aberto | tenancy | `?org` válido | Resultado |
|---|---|---|---|---|---|
| D1 | ausente | **desligado** | off | — | recusa → login (o comportamento de hoje) |
| D2 | ausente | **ligado** | off | — | formulário de registro aberto |
| D3 | **inválido** | ligado | off | — | **recusa** (não cai no modo aberto) |
| D4 | válido | ligado | off | — | fluxo de convite |
| D5 | ausente | ligado | **on** | sim | formulário, vinculado à organização |
| D6 | ausente | ligado | **on** | não/ausente | recusa |

D1, D2, D3 → R1. D4 → R2. D5, D6 → R7.

```gherkin
# language: pt

Funcionalidade: registro aberto no painel de negócio

  Regra: sem token na URL, o cadastro só é oferecido quando a instalação o liberou

    Cenário: [CT-03] com o registro desligado, a visita sem token termina no login
      Dado que a instalação está com o registro aberto desligado
      E que o visitante não está autenticado
      Quando o visitante abre o endereço de registro sem token
      Então ele é redirecionado para a tela de login
      E nenhum usuário é criado

    Cenário: [CT-04] com o registro ligado, o formulário de cadastro é oferecido
      Dado que a instalação está com o registro aberto ligado
      Quando o visitante abre o endereço de registro sem token
      Então o formulário de cadastro é exibido
      E o campo de e-mail está habilitado e vazio

    Cenário: [CT-05] token inválido continua recusando, mesmo com o registro ligado
      Dado que a instalação está com o registro aberto ligado
      Quando o visitante abre o endereço de registro com um token que não existe
      Então ele é redirecionado para a tela de login
      E nenhum usuário é criado
```

> **Por que CT-04 assere "habilitado e vazio"**: no modo convite o campo de e-mail é exibido
> **desabilitado** e preenchido com o e-mail do convite. É a diferença observável entre os dois
> modos, e é ela que falsifica o mutante "o ramo do convite vazou para o modo aberto".

> **Por que CT-05 é a espinha desta regra**: ele é o único cenário que distingue "garfo por
> ausência de token" de "garfo por convite inválido". Sem ele, `?token=lixo` viraria uma
> segunda porta para o cadastro aberto — e é a porta que **não** passa pelo throttle da recusa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o garfo é `if (! $convite) { modo aberto }` — token inválido cai no modo aberto | **CT-05** |
| M2 | a consulta à opção é negada (`if (habilitado) recusar()`) | CT-03 **e** CT-04 (um dos dois falharia em qualquer inversão) |
| M3 | a opção é ignorada e o modo aberto vale sempre | CT-03 |
| M4 | o campo de e-mail continua desabilitado no modo aberto (ramo do convite não condicionado) | CT-04 |

---

## Regra R2 — o caminho do convite não muda

> `RQ-01`, `RQ-12` · perfil **completo** · técnica: **tabela de decisão** (linha D4) + regressão

```gherkin
  Regra: o aceite de convite funciona igual, com o registro aberto ligado ou desligado

    Esquema do Cenário: [CT-06] o convite é aceito nas duas configurações
      Dado um convite pendente para "convidado@example.com" com o papel "panel_user"
      E que a instalação está com o registro aberto <registro>
      Quando a pessoa aceita o convite pelo link recebido
      Então existe um usuário com o e-mail "convidado@example.com"
      E esse usuário tem o papel "panel_user"
      E o convite está marcado como aceito

      Exemplos:
        | registro   | # partição       |
        | desligado  | o default        |
        | ligado     | a coexistência   |

    Cenário: [CT-07] o convite vence o formulário na escolha do e-mail
      Dado um convite pendente para "convidado@example.com"
      E que a instalação está com o registro aberto ligado
      Quando a pessoa envia o cadastro digitando "outro@example.com" no e-mail
      Então o usuário criado tem o e-mail "convidado@example.com"
      E nenhum usuário com o e-mail "outro@example.com" existe
```

> CT-07 é regressão dirigida ao mutante que a coexistência introduz: tornar
> `mutateFormDataBeforeRegister()` condicional é exatamente onde alguém pode inverter a
> condição e deixar o formulário escolher o e-mail de um convite.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | `mutateFormDataBeforeRegister()` deixa de forçar o e-mail (condição invertida) | **CT-07** |
| M6 | `handleRegistration()` chama `RegistroAberto::registrar()` também no modo convite | CT-06 (o convite não ficaria aceito) |
| M7 | com o registro aberto ligado, o `mount()` deixa de resolver o convite | CT-06 (linha "ligado") |

---

## Regra R3 — o registrado recebe `panel_user` e nada além

> `RQ-04`, `RQ-05` · perfil **padrão**, técnica **escalada** · técnica: **matriz papel × painel**

### Matriz papel × painel

| Persona | `/app` | `/admin` | `/infra` |
|---|---|---|---|
| recém-registrado, aprovado (`panel_user`) | **entra** (CT-08) | **403** (CT-09) | **403** (CT-09) |
| recém-registrado, pendente (sem papel) | **403** (CT-11) | **403** (CT-11) | **403** (CT-11) |

```gherkin
  Regra: quem se cadastra pela porta aberta recebe apenas o perfil de acesso ao painel de negócio

    Cenário: [CT-08] o cadastro aprovado automaticamente entra no painel de negócio
      Dado que a instalação está com o registro aberto ligado e a aprovação automática
      Quando o visitante envia o cadastro
      Então o usuário criado tem exatamente um papel
      E esse papel é o de acesso ao painel de negócio
      E ele consegue abrir o painel de negócio

    Esquema do Cenário: [CT-09] o cadastro não alcança os painéis de administração
      Dado um usuário criado pelo registro aberto, com aprovação automática
      Quando ele tenta abrir <painel>
      Então a resposta é 403

      Exemplos:
        | painel   | # partição              |
        | /admin   | administração da instalação |
        | /infra   | infraestrutura          |

    Cenário: [CT-10] chamado fora da tela, o registro continua dando um papel só
      Dado que a instalação está com o registro aberto ligado e a aprovação automática
      Quando o registro é executado direto, sem passar pela tela
      Então o usuário criado tem exatamente um papel
      E esse papel é o de acesso ao painel de negócio
```

> **CT-09 usa `Esquema do Cenário` para não amostrar painel** — é a técnica escalada declarada
> no perfil. E CT-10 é o cenário de **interface** da varredura SFDIPOT: a barreira tem de valer
> para o chamador direto (job, comando, seeder), não só para a tela — a lição de
> `.ai/rules/filament.md` § *"Asserção de identidade vive no model"*.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | o papel atribuído é `admin_app` (o outro papel do painel `app`) | CT-08 ("é o de acesso ao painel de negócio") |
| M9 | além de `panel_user`, um segundo papel é atribuído | CT-08 ("exatamente um papel") |
| M10 | o papel é atribuído no contexto global em vez do da organização (com tenancy) | CT-24 |
| M11 | nenhum papel é atribuído no caminho de aprovação automática | CT-08 (não abriria o painel) |
| M12 | a atribuição de papel só existe dentro da página, não em `registrar()` | **CT-10** |

---

## Regra R4 — com aprovação manual, o cadastro nasce pendente e não entra em painel nenhum

> `RQ-07`, `RQ-12` · perfil **completo** · técnica: **tabela estado × operação**

### Tabela estado × operação

| Estado | entrar no `/app` | entrar no `/admin` | entrar no `/infra` | ser aprovado | ser editado |
|---|---|---|---|---|---|
| **pendente** | 403 (CT-11) | 403 (CT-11) | 403 (CT-11) | **válido** (CT-19) | válido (CT-16) |
| **aprovado** | entra (CT-08) | 403 (CT-09) | 403 (CT-09) | no-op (CT-21) | válido (CT-16) |

Cada coluna tem **ao menos uma célula válida exercitada** — a metade que a skill cobra e que
"toda célula vazia vira cenário negativo" não cobre sozinha. A coluna "ser editado" tem CT-16
justamente para exercitar a armadilha da unicidade contra o próprio registro.

```gherkin
  Regra: enquanto o cadastro aguarda aprovação, ele não entra em painel nenhum

    Esquema do Cenário: [CT-11] o cadastro pendente é recusado nos três painéis
      Dado um usuário criado pelo registro aberto com aprovação manual
      Quando ele tenta abrir <painel>
      Então a resposta é 403

      Exemplos:
        | painel   |
        | /app     |
        | /admin   |
        | /infra   |

    Cenário: [CT-12] @premissa quem acabou de se cadastrar sai da tela sabendo que aguarda aprovação
      Dado que a instalação está com o registro aberto ligado e a aprovação manual
      Quando o visitante envia o cadastro
      Então ele não fica autenticado
      E recebe uma mensagem informando que o cadastro aguarda aprovação

    Cenário: [CT-15] o cadastro pendente aparece para quem administra
      Dado um usuário pendente criado pelo registro aberto
      Quando quem administra abre a listagem de usuários filtrando os pendentes
      Então o usuário pendente aparece na listagem
      E um usuário já aprovado não aparece

    Cenário: [CT-16] o cadastro pendente pode ser editado sem colidir com o próprio e-mail
      Dado um usuário pendente com o e-mail "pendente@example.com"
      Quando quem administra salva o cadastro trocando apenas o nome
      Então o nome gravado é o novo
      E o e-mail gravado continua "pendente@example.com"
```

> **CT-11 é a cláusula RQ-05 na sua forma mais forte.** Ele é o cenário que reprova se a guarda
> de pendência for posta **depois** do atalho do `master_global` em `canAccessPanel()`, ou se
> ela for esquecida em um painel.
>
> **Por que CT-12 assere "não fica autenticado"**: a página de registro do Filament termina em
> `Filament::auth()->login($user)` (`Register.php:105`). Sem desfazer isso, a sessão fica
> autenticada para alguém que não pode nada — e o próximo request vira 403 sem explicação.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | a guarda de pendência fica **depois** do atalho do `master_global` | CT-11 (na linha em que o pendente for master — coberto porque o pendente nasce **sem papel**, e o cenário afirma os três painéis) |
| M14 | a guarda de pendência existe só no painel `app` | CT-11 (linhas `/admin` e `/infra`) |
| M15 | a pendência é gravada mas o registro segue autenticado | **CT-12** |
| M16 | a pendência é ignorada quando a opção está ligada (condição invertida) | CT-11 |
| M17 | o filtro de pendentes devolve todo mundo | CT-15 ("um usuário já aprovado não aparece") |
| M18 | a validação de unicidade do e-mail não ignora o próprio registro na edição | **CT-16** |

---

## Regra R5 — a aprovação libera o acesso, é idempotente e exige quem pode

> `RQ-06`, `RQ-07` · perfil **completo** · técnica: **estado × operação** + **matriz papel × ação**

```gherkin
  Regra: aprovar um cadastro pendente dá a ele o perfil do painel de negócio, e só quem administra pode

    Cenário: [CT-19] @premissa a aprovação libera o painel de negócio
      Dado um usuário pendente criado pelo registro aberto
      E que quem administra tem permissão de editar usuários
      Quando quem administra dispara a aprovação na listagem
      Então o usuário passa a ter o papel de acesso ao painel de negócio
      E ele consegue abrir o painel de negócio
      E a listagem deixa de exibi-lo como pendente

    Cenário: [CT-20] a aprovação deixa rastro de quem aprovou
      Dado um usuário pendente criado pelo registro aberto
      Quando quem administra aprova o cadastro
      Então uma linha de registro do canal de autenticação identifica o usuário aprovado e quem aprovou
      E essa linha não contém a senha nem o e-mail em claro

    Cenário: [CT-21] aprovar duas vezes não muda nada além da primeira
      Dado um usuário pendente criado pelo registro aberto
      Quando a aprovação é executada duas vezes sobre o mesmo cadastro
      Então o usuário tem exatamente um papel
      E ele continua aprovado

    Cenário: [CT-23] @premissa o usuário comum não aprova cadastro
      Dado um usuário pendente criado pelo registro aberto
      E um usuário comum do painel de negócio
      Quando o usuário comum tenta disparar a aprovação
      Então a ação é recusada
      E o cadastro continua pendente
      E ele continua sem papel nenhum
```

> **CT-21 ancora no agregado persistido**, não no retorno: "tem exatamente um papel" depois de
> duas execuções sobre **o mesmo registro** é o que falsifica o mutante "aprovar atribui o
> papel sem verificar se já tem".
>
> **CT-23 é o cenário que a `.ai/rules/filament.md` cobra**: Action do Filament não consulta
> policy sozinha — o default de `$authorization` é `null`, liberado para todo mundo
> (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:15-22`). Sem `->authorize()`, ele
> reprova. E ele afirma o **não-efeito** (continua pendente, continua sem papel), não só "foi
> recusado".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M19 | a aprovação limpa a pendência mas não atribui o papel | CT-19 (não abriria o painel) |
| M20 | a aprovação atribui o papel mas não limpa a pendência | CT-19 (não abriria o painel — a guarda barra) |
| M21 | a Action nasce sem `->authorize()` | **CT-23** |
| M22 | a aprovação atribui o papel sempre, sem checar se já tem | CT-21 ("exatamente um papel") |
| M23 | a chamada de log é removida | **CT-20** |
| M24 | o log sai com o e-mail em claro | CT-20 |
| M25 | a aprovação grava no contexto global de papéis em vez do da organização | CT-24 |

---

## Regra R6 — a verificação de e-mail é opcional e nunca alcança quem veio de convite

> `RQ-09`, `RQ-12` · perfil **padrão** · técnica: **rastreio de efeito** (o QUE, e as três direções)

O efeito rastreado é a **notificação de verificação de e-mail** do Filament
(`Filament\Auth\Notifications\VerifyEmail`), entregue ao próprio usuário.

```gherkin
  Regra: a exigência de validar o e-mail é opcional, e o convite nunca a dispara

    Cenário: [CT-17] com a opção ligada, o cadastro aberto recebe o pedido de validação
      Dado que a instalação está com o registro aberto e a validação de e-mail ligadas
      Quando o visitante envia o cadastro
      Então o usuário criado está com o e-mail não validado
      E ele recebe a notificação de validação de e-mail

    Cenário: [CT-18] com a opção desligada, ninguém recebe pedido de validação
      Dado que a instalação está com o registro aberto ligado e a validação de e-mail desligada
      Quando o visitante envia o cadastro
      Então o usuário criado está com o e-mail já validado
      E nenhuma notificação de validação de e-mail é enviada

    Cenário: [CT-22] o aceite de convite nunca dispara pedido de validação
      Dado que a instalação está com a validação de e-mail ligada
      E um convite pendente para "convidado@example.com"
      Quando a pessoa aceita o convite pelo link recebido
      Então o usuário criado está com o e-mail já validado
      E nenhuma notificação de validação de e-mail é enviada
```

**CT-22 tem uma segunda metade, na mesma regra**: afirmar que o painel de negócio **passa a
exigir** a validação quando a opção está ligada, e **não exige** quando desligada — construindo
o painel pelo próprio provider, pelo motivo explicado em *"A armadilha de arnês"*:

```gherkin
    Esquema do Cenário: [CT-22b] o painel de negócio só exige validação quando a opção está ligada
      Dado que a instalação está com a validação de e-mail <opcao>
      Quando o painel de negócio é montado
      Então a exigência de validação de e-mail está <estado>

      Exemplos:
        | opcao      | estado    |
        | ligada     | presente  |
        | desligada  | ausente   |
```

> **A terceira direção do rastreio de efeito** ("aconteceu uma só vez") não gera cenário
> próprio: o envio está dentro de `Register::register()` do vendor, chamado uma vez por submit,
> e a assertion de CT-17 usa a contagem do fake (`assertSentTo(..., 1)`).
>
> **A quarta direção (atomicidade)** — o e-mail não sai se a gravação falhar — é **lacuna
> declarada**: o envio acontece **fora** da transação, depois dela
> (`Register.php:84-107`: `wrapInDatabaseTransaction()` fecha, e só então
> `sendEmailVerificationNotification()`). É comportamento do vendor, não do kit, e alterá-lo
> exigiria sobrescrever `register()` inteiro. Tentado: falhar por `unique` de e-mail duplicado
> — o `handleRegistration()` estoura dentro da transação e o envio nunca é alcançado, então o
> cenário passaria por construção e não distinguiria nada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | `email_verified_at` é gravado **sempre**, ignorando a opção | **CT-17** (usuário nasceria validado e nada seria enviado) |
| M27 | `email_verified_at` **nunca** é gravado no registro aberto | CT-18 ("já validado" + nenhuma notificação) |
| M28 | `Convite::aceitar()` deixa de gravar `email_verified_at` | **CT-22** |
| M29 | a exigência do painel é ligada sempre (independe da opção) | CT-22b (linha "desligada") |
| M30 | `User` não implementa o contrato de validação de e-mail | CT-17 (o vendor pula o envio quando o modelo não implementa — `Register.php:157-161`) |

---

## Regra R7 — com tenancy, só organização ativa e com registro ligado aceita registro

> `RQ-03` · perfil **padrão** · técnica: **tabela de decisão** · **suíte `tests/Tenancy`**

### Tabela de decisão

| # | organização existe | `ativo` | `registro_habilitado` | Resultado |
|---|---|---|---|---|
| E1 | sim | sim | **sim** | registro, vinculado a ela |
| E2 | sim | sim | **não** | recusa |
| E3 | sim | **não** | sim | recusa |
| E4 | **não** (slug desconhecido) | — | — | recusa |
| E5 | **parâmetro ausente** | — | — | recusa |

```gherkin
  Regra: com multi-tenancy, o cadastro aberto só entra em organização que o habilitou

    Cenário: [CT-24] o cadastro entra na organização que habilitou o registro
      Dado que a instalação está com o registro aberto ligado e a aprovação automática
      E uma organização ativa que habilitou o registro
      Quando o visitante envia o cadastro apontando para essa organização
      Então o usuário criado pertence a essa organização
      E ele tem o papel de acesso ao painel de negócio dentro dela
      E ele consegue abrir o painel de negócio dessa organização

    Esquema do Cenário: [CT-14] organização que não habilitou o registro recusa o cadastro
      Dado que a instalação está com o registro aberto ligado
      E uma organização <situacao>
      Quando o visitante abre o endereço de registro apontando para ela
      Então ele é redirecionado para a tela de login
      E nenhum usuário é criado

      Exemplos:
        | situacao                             | # linha da tabela |
        | ativa, com o registro desligado      | E2                |
        | inativa, com o registro ligado       | E3                |
        | inexistente (slug desconhecido)      | E4                |
        | não informada (sem o parâmetro)      | E5                |
```

> **CT-24 é o cenário discriminante da persona/contexto**: ele afirma o papel **dentro da
> organização**, não só "tem o papel". Com `permission.teams` ligado, papel gravado no contexto
> global fica invisível dentro do `/app` (`.ai/rules/testes.md`, ADR-10 da wiki
> `admin-da-organizacao`) — é o mutante M10/M25, e só este cenário o mata.
>
> **CT-14 cobre as quatro linhas negativas da tabela num `Esquema`** — e as quatro devolvem a
> **mesma** recusa, de propósito: um visitante não descobre qual das condições falhou.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M31 | a resolução da organização ignora `registro_habilitado` | CT-14 (linha E2) |
| M32 | a resolução ignora `ativo` | CT-14 (linha E3) |
| M33 | sem organização, o registro acontece de qualquer forma (falha **aberta**) | CT-14 (linha E5) |
| M34 | o vínculo com a organização não é gravado | CT-24 (não abriria o painel da organização) |

---

## Regra R8 — a porta pública é limitada e registrada

> `RQ-12` (consequência da superfície nova) · perfil **completo** · técnica: **BVA na contagem** + **rastreio de efeito**

```gherkin
  Regra: a porta pública de cadastro tem limite de tentativas e deixa rastro

    Esquema do Cenário: [CT-13] o envio do cadastro é limitado por tentativa
      Dado que a instalação está com o registro aberto ligado
      Quando o visitante envia o formulário pela <n>ª vez na mesma janela
      Então o resultado é "<resultado>"

      Exemplos:
        | n | resultado            | # borda   |
        | 1 | cadastro criado      | dentro    |
        | 2 | cadastro criado      | borda     |
        | 3 | recusado pelo limite | borda+1   |

    Cenário: [CT-20b] a recusa por falta de convite continua sendo registrada
      Dado que a instalação está com o registro aberto desligado
      Quando o visitante abre o endereço de registro sem token
      Então uma linha de aviso do canal de autenticação registra a recusa
      E essa linha não contém token nenhum
```

> **A borda é 2, não 10**: o limite é o do vendor — `rateLimit(2)` por IP mais 2 por e-mail
> (`Register.php:71-79` e `:126-148`). O BVA usa o **valor efetivo lido**, e o incremento é 1
> tentativa (o tipo do campo é contagem inteira).
>
> **CT-20b é regressão dirigida** ao caminho que o rename e o garfo novo podem quebrar: o
> `recusar()` de hoje já loga e já tem throttle próprio (5/600 s), auditados em QA-01 da wiki
> `convite-de-usuario`. O que este cenário protege é que a reescrita do `mount()` não tirou o
> log do caminho da recusa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M35 | o `register()` sobrescrito deixa de chamar o pai e perde o throttle | **CT-13** (linha borda+1) |
| M36 | o `register()` sobrescrito não trata o retorno `null` do throttle e estoura | CT-13 (linha borda+1) |
| M37 | o garfo novo no `mount()` deixa a recusa sem log | **CT-20b** |

---

## Regra R9 — a configuração é lida por um ponto único e o default é `false`

> `RQ-02`, `RQ-11` · perfil **mínimo** · técnica: **EP** + varredura de arquivo

```gherkin
  Regra: as três opções são lidas por um ponto único, e nascem desligadas

    Cenário: [CT-01] nenhum outro arquivo lê as opções de registro direto
      Dado o código da aplicação
      Quando se procura por leitura direta das opções de registro fora do ponto único
      Então nenhuma ocorrência é encontrada

    Cenário: [CT-02] o estado da aprovação não é gravável por formulário
      Dado o modelo de usuário
      Quando se consulta os atributos preenchíveis por atribuição em massa
      Então o campo de pendência de aprovação não está entre eles

    Esquema do Cenário: [CT-26] as três opções nascem desligadas
      Dado a configuração de fábrica, com <chave> ausente do ambiente
      Quando a opção é consultada pelo ponto único
      Então o valor é falso

      Exemplos:
        | chave                          |
        | registro aberto                |
        | aprovação manual               |
        | validação de e-mail            |

    Cenário: [CT-25] a organização decide o registro pela tela de organizações
      Dado que a instalação está com o registro aberto ligado
      E uma organização que não habilitou o registro
      Quando quem administra salva a organização habilitando o registro
      Então a organização passa a aceitar registro
```

> **CT-26 usa o valor de fábrica.** O `phpunit.xml` **não** fixa `KIT_REGISTRO*` — foi
> conferido —, então o cenário mede o default do `config/kit.php`, que é o que o requisito
> determina literalmente ("o default é false"). Se alguém acrescentar as chaves ao
> `phpunit.xml`, este cenário passa a medir o ambiente e vira vácuo: o CT declara essa
> dependência para que a próxima pessoa saiba.
>
> **CT-01 é o guardião de ADR-02.** Ele é a única coisa que impede a leitura de config de se
> espalhar e transformar o rebase com `feat/settings-do-kit` de um arquivo em cinco. Segue o
> padrão de `tests/Kit/QualidadeDeCodigoTest.php`, **com o filtro de comentário** que
> `.ai/rules/testes.md` cobra em asserção de ausência: os arquivos do kit **citam** o que
> proíbem.
>
> **CT-02 segue o padrão de `tests/Kit/ConviteUsuarioExistenteTest.php:120-122`**, que faz o
> mesmo para `email_verified_at`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M38 | `aprovacao_pendente` entra no `$fillable` | **CT-02** |
| M39 | o default de uma das chaves é `true` | CT-26 |
| M40 | algum consumidor lê `config('kit.registro.*')` direto | **CT-01** |
| M41 | `registro_habilitado` fica fora do `$fillable` do tenant e o formulário não grava | **CT-25** |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma rota nova recebe `{id}` de recurso. A aprovação recebe o registro pelo route binding do Resource, cujo escopo por organização já é coberto por `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| Autorização exercida na **ação** (não só `can()`) | **CT-23** (o usuário comum dispara a Action e é recusado) |
| Idempotência (ancorada no agregado persistido) | **CT-21** |
| Concorrência | **não se aplica além do existente**: dois registros simultâneos do mesmo e-mail são barrados pelo `unique` de `users.email`; duas aprovações simultâneas são idempotentes (CT-21). Não há contador, saldo nem limite de uso nesta feature |
| Fronteira no ponto de entrada (**gravação**) | CT-13 (limite de tentativas), CT-16 (edição) |
| Domínio condicionado (um campo depende de outro) | **CT-14** (`registro_habilitado` só importa com tenancy e com o registro global ligado) e CT-22b |
| Estado × operação de escrita (pendente ainda funciona?) | **CT-11** (pendente não entra), **CT-16** (pendente é editável) |
| Ausente ≠ `null` ≠ `""` | **CT-14** (linha E5, `?org` ausente) e CT-03/CT-05 (`?token` ausente × inválido) |
| Paginação / ordenação | **não se aplica**: nenhuma listagem nova. O filtro de pendentes entra numa tabela existente, com paginação já coberta |
| Timezone / DST | **não se aplica**: a feature não compara datas. O único campo temporal é `email_verified_at`, e o que decide é nulidade (`hasVerifiedEmail()`), não instante — ver a dimensão **T** do SFDIPOT |
| Unicode / limite de varchar | **não se aplica**: nenhum campo de texto novo. Nome, e-mail e senha usam os componentes e as regras do Filament, já cobertos pelo convite |
| Unicidade + soft delete | **não se aplica**: `User` não usa `SoftDeletes` (a lista do `RevivePlugin` no `InfraPanelProvider` o exclui de propósito) |
| CRUD combinado | CT-16 (editar sem alterar o campo único), CT-21 (operar duas vezes) |
| **Mass assignment** | **CT-02** (`aprovacao_pendente` fora do `$fillable`) e **CT-07** (o e-mail do formulário é descartado no modo convite) |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: nenhum valor numérico de domínio |
| **Efeito colateral pelo canal certo** | **CT-17** (a notificação de validação de e-mail, do tipo que o Filament nomeia) e **CT-20** (o channel `autenticacao`, não o default) |
| **Segredo em log** | **CT-20** (sem senha, e-mail mascarado) e **CT-20b** (sem token) |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | nenhum outro arquivo lê a config direto | R9 | varredura | Kit | `tests/Kit/RegistroAbertoTest.php` | M40 |
| CT-02 | pendência fora do `$fillable` | R9 | EP | Kit | idem | M38 |
| CT-03 | registro desligado → login | R1 | tabela de decisão | Kit (Livewire) | idem | M2, M3 |
| CT-04 | registro ligado → formulário | R1 | tabela de decisão | Kit (Livewire) | idem | M2, M4 |
| CT-05 | token inválido continua recusando | R1 | tabela de decisão | Kit (Livewire) | idem | **M1** |
| CT-06 | convite aceito nas duas configurações | R2 | `Esquema` + regressão | Kit (Livewire) | idem | M6, M7 |
| CT-07 | o convite vence o formulário no e-mail | R2 | mass assignment | Kit (Livewire) | idem | M5 |
| CT-08 | aprovado automaticamente entra no `/app` | R3 | matriz papel × painel | Kit (Livewire+HTTP) | idem | M8, M9, M11 |
| CT-09 | não alcança `/admin` nem `/infra` | R3 | matriz papel × painel | Kit (HTTP) | idem | M8 |
| CT-10 | chamado direto, um papel só | R3 | interface (SFDIPOT) | Kit (Unit-like) | idem | **M12** |
| CT-11 | pendente recusado nos três painéis | R4 | estado × operação | Kit (HTTP) | idem | M13, M14, M16 |
| CT-12 | pendente não fica autenticado | R4 | estado × operação | Kit (Livewire) | idem | **M15** |
| CT-13 | limite de tentativas | R8 | BVA (contagem) | Kit (Livewire) | idem | M35, M36 |
| CT-14 | organização sem registro recusa | R7 | tabela de decisão | Tenancy (Livewire) | `tests/Tenancy/RegistroAbertoTenancyTest.php` | M31, M32, M33 |
| CT-15 | pendente aparece no filtro | R4 | estado × operação | Kit (Livewire) | `tests/Kit/RegistroAbertoTest.php` | M17 |
| CT-16 | pendente editável sem colidir consigo | R4 | CRUD/unicidade | Kit (Livewire) | idem | **M18** |
| CT-17 | validação ligada → notificação | R6 | rastreio de efeito | Kit (Livewire) | idem | M26, M30 |
| CT-18 | validação desligada → nada enviado | R6 | rastreio de efeito | Kit (Livewire) | idem | M27 |
| CT-19 | aprovação libera o painel | R5 | estado × operação | Kit (Livewire+HTTP) | idem | M19, M20 |
| CT-20 | a aprovação deixa rastro | R5 | rastreio de efeito | Kit (Livewire) | idem | M23, M24 |
| CT-20b | a recusa continua registrada | R8 | rastreio de efeito | Kit (Livewire) | idem | **M37** |
| CT-21 | aprovar duas vezes é idempotente | R5 | idempotência | Kit (Unit-like) | idem | M22 |
| CT-22 | convite nunca dispara validação | R6 | rastreio de efeito | Kit (Livewire) | idem | **M28** |
| CT-22b | o painel só exige quando ligado | R6 | `Esquema` + wiring | Kit | idem | M29 |
| CT-23 | usuário comum não aprova | R5 | matriz papel × ação | Tenancy (Livewire) | `tests/Tenancy/RegistroAbertoTenancyTest.php` | **M21** |
| CT-24 | o cadastro entra na organização certa | R7 / R3 | tabela de decisão + contexto | Tenancy (Livewire+HTTP) | idem | M10, M25, M34 |
| CT-25 | a organização habilita pela tela | R9 | gravação por componente | Tenancy (Livewire) | idem | **M41** |
| CT-26 | as três opções nascem desligadas | R9 | EP | Kit | `tests/Kit/RegistroAbertoTest.php` | M39 |

**Mutantes sem matador — lacunas declaradas** (2):

1. **Atomicidade da notificação de verificação** — o envio está fora da transação, no vendor
   (`Register.php:84-107`). Tentado: falhar por `unique` de e-mail; o cenário passaria por
   construção. Fechar exigiria sobrescrever `register()` inteiro, o que ADR-10 recusa.
2. **`master_global` pendente** — a guarda de pendência vence o atalho do `master_global`, mas
   não há cenário que crie um `master_global` pendente: a via aberta nunca atribui esse papel, e
   forjar o estado testaria uma combinação que o sistema não produz. CT-11 cobre a ordem da
   guarda pelo caminho real (o pendente nasce **sem papel**, então qualquer ordem o barra) —
   o que fica descoberto é só a hipótese de alguém marcar um `master_global` como pendente à
   mão no banco.

## Gate de tela de escrita — conferência

| Rota de escrita da `## Superfície de UI` | Cenário de gravação por componente |
|---|---|
| `/app/register` (modo aberto) | CT-08, CT-12, CT-17, CT-18, CT-24 |
| `/app/register` (modo convite) | CT-06, CT-07, CT-22 |
| `/admin/organizacoes/{id}/edit` | **CT-25** |
| `/app/{tenant}/users/{id}/edit` | **CT-16** |
| ação Aprovar (`/admin` e `/app`) | CT-19 (positiva), CT-23 (negativa) |
