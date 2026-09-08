# Plano de Ação — fix: destino depois do cadastro

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/login-unificado/login-unificado/` (ADR-06 — a validação da
  URL pretendida no login), refinada por
  `wikis/specs/feat/login-unificado-telas-externas/login-unificado-telas-externas/` (ADR-02 — o
  cadastro sem prefixo de painel)
- **Motivo**: a ADR-06 endureceu a URL pretendida **só para o login**. O cadastro continuou com a
  resposta crua do Filament, e a conta recém-criada é entregue na pretendida sem nenhuma
  verificação — 403 quando ela é de painel que a conta não acessa. Medido no navegador na
  instalação real de teste na v0.32.2, com a chave `kit.login.unificado` ligada **e** desligada
- **Toca infra compartilhada?**: **sim** → o bind de `RegistrationResponse` no container vale para
  os três painéis e para os dois modos de cadastro (convite e registro aberto), com a chave ligada
  ou desligada. Por isso a **regressão é obrigatória**: os CT/CT-B de `login-unificado`,
  `login-unificado-telas-externas`, `convite-de-usuario`, `convite-para-usuario-existente`,
  `registro-e-aprovacao` e `cadastro-social-por-convite-e-organizacao` atravessam esta resposta

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | conta nova nunca entregue em painel inacessível | 2, 3 | |
| RQ-02 | mesma decisão do login, via `DestinoAposLogin` | 1, 2 | reuso, não regra nova |
| RQ-03 | vale com a chave desligada | 1, 2 | atendida sob a premissa do `00` (descartar a pretendida, não inventar tela) |
| RQ-04 | pretendida acessível continua honrada | 2 | é o que `DestinoAposLogin::painelDe()` já decide |
| RQ-05 | documentação | 5, 6 | wiki + docs pt/en + CHANGELOG |
| RQ-06 | main + tag | 8 | depois do quality gate e da CI verde |
| RQ-07 | validar por `kit:update` numa instalação de teste | 9 | `TESTES KIT/login-unificado-sem-tenancy` |

## Objetivo

Fazer o destino depois de um cadastro bem-sucedido passar pelo mesmo decisor do destino depois do
login, para que a conta recém-criada nunca seja entregue na URL de um painel que ela não acessa.

Hoje o Filament termina o cadastro em `redirect()->intended(Filament::getUrl())`, sem olhar quem é
a conta. O kit já resolveu exatamente esse problema no login, com `DestinoAposLogin::urlPara()`, e
a correção é ligar o cadastro nesse mesmo decisor — uma classe de resposta e um bind, nada de
lógica nova.

## Contexto

A URL pretendida entra na sessão sem ninguém pedir: o middleware `Authenticate` do Filament a
grava quando um visitante tenta abrir um painel e é mandado ao login. Ela sobrevive ao
`session()->regenerate()` do cadastro, porque `regenerate()` troca o id da sessão e preserva os
dados.

A sequência que produz o 403 é banal e não exige nenhuma sessão de administrador:

1. visitante abre `/admin` → o `Authenticate` grava `url.intended = /admin` e manda ao login
2. o mesmo visitante abre o link do convite e se cadastra
3. o cadastro faz login da conta nova e devolve `redirect()->intended('/app')`
4. a pretendida existe, então o Filament ignora o fallback e entrega `/admin`
5. a conta nova só tem `panel_user` → **403**

A pessoa não fez nada errado, o cadastro funcionou, e a primeira tela do sistema é um código de
erro. É o mesmo desenho de falha que `RegistroPorConvite::register():216` já fecha para o cadastro
pendente de aprovação — só que ali o gatilho é o estado da conta, e aqui é a sessão.

## Análise dos Arquivos Existentes

### `app/Http/Responses/RespostaDeLogin.php`

O irmão exato desta correção, e o molde a seguir. Estende `Filament\Auth\Http\Responses\LoginResponse`,
lê `Filament::auth()->user()`, delega ao `parent::` quando a chave está desligada ou o usuário não
é `App\Models\User`, e devolve `redirect()->to(DestinoAposLogin::urlPara($user))` no resto.
Vinculada em `app/Providers/KitServiceProvider.php:configureLoginUnificado():535-538`.

A correção **não** mexe nesta classe.

### `app/Support/DestinoAposLogin.php`

O decisor. `urlPara()` (`:85`) puxa `url.intended`, pergunta a `painelDe()` (`:234`) se ela é de um
painel acessível, e escolhe entre pretendida, painel único e tela de escolha. `painelDe()` é
privada e já resolve as duas armadilhas de URL que a auditoria Blueprint achou (`//`, `/\`, host
falsificado).

Ganha **um método público novo**, `descartarPretendidaInacessivel()`, para o caso da chave
desligada — onde a tela de escolha não existe e a única coisa correta a fazer é esquecer a
pretendida e deixar o Filament decidir. É a menor superfície possível: não duplica `painelDe()`,
não muda `urlPara()`, e o `RespostaDeCadastro` não passa a conhecer as regras de parsing de URL.

### `app/Filament/Pages/Auth/RegistroPorConvite.php`

Já sobrescreve `register()`, para o cadastro pendente de aprovação. Termina em
`return $resposta;`, e é esse `$resposta` — o `app(RegistrationResponse::class)` do vendor — que
esta correção troca. **Nenhuma linha desta classe muda**: a troca é no container, o que faz a
correção valer igual para `CadastroUnificado` (que a estende) e para o registro aberto.

### `app/Providers/KitServiceProvider.php`

`configureLoginUnificado()` (`:535`) é onde o bind de `LoginResponse` já vive. O bind novo entra
ao lado, e o docblock do método é corrigido: hoje ele afirma que com a chave desligada a resposta
é idêntica à do Filament, o que passa a ser falso para o cadastro (a pretendida inacessível é
descartada nas duas configurações).

## Autorização

- **Policies**: nenhuma
- **Gates**: nenhum
- **Middleware**: nenhum novo. A correção existe justamente para não depender do `Authenticate`
  do painel devolver 403 — ele continua igual
- **Guards**: nenhum. `Filament::auth()` é o guard `web`, único do kit
- **A pergunta de autorização usada** é `User::canAccessPanel()`, via
  `DestinoAposLogin::paineisDe()` — exatamente a mesma do middleware de cada painel, e a mesma
  do login (ADR-07 da ancestral)

## Rotas

Nenhuma rota nova, nenhuma alterada.

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| — | — | — | — |

## Superfície de UI

**Sem superfície de UI nova.** A correção troca o destino de um redirect; não há tela, campo,
botão nem texto novo. As telas que a atravessam já existem e não mudam:

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `RegistroPorConvite` | Filament (Livewire) | `/app/register` | envia o formulário de cadastro | Não (o envio é Livewire) |
| `CadastroUnificado` | Filament (Livewire) | `/cadastro` | idem, com a chave ligada | Não |

**Gate de CT-B**: nenhum cenário desta correção afirma sobre JavaScript executado, console,
acessibilidade, cor ou layout. O oráculo é sempre *para onde o redirect aponta* — asserção de
componente Livewire. Portanto **sem CT-B**; ver a seção correspondente no `04`.

**Gate de tela de escrita**: `/app/register` e `/cadastro` são telas de escrita, e a gravação por
componente já é coberta pelas wikis de convite e de registro aberto. Os CTs desta correção
exercitam a mesma gravação, agora afirmando sobre o destino.

## Variáveis de Ambiente

Nenhuma nova. A correção lê `kit.login.unificado`, que já existe (`KIT_LOGIN_UNIFICADO` no
`.env.example`, e a Setting equivalente depois da instalação).

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_LOGIN_UNIFICADO` | `false` | já existente — decide qual dos dois ramos da resposta roda |

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum novo. O `Registered` continua sendo emitido pelo vendor, antes da
  resposta
- **Listeners**: nenhum
- **Observers**: nenhum
- **Efeito colateral relevante que a correção herda**: `DestinoAposLogin::urlPara()` **carimba o
  painel de entrada** no `authentication_log` quando o painel é decidido ali
  (`carimbarAcesso()`). Isso passa a valer também para o cadastro com a chave ligada, e é
  desejável — hoje o acesso do cadastro fica sem painel. Ver ADR-03

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

O bind vale para todo cadastro do sistema, então a lista é a de tudo que termina em
`app(RegistrationResponse::class)`:

- **convite de usuário** (`wikis/specs/main/convite-de-usuario/`): o aceite por convite passa pela
  resposta nova. É o caminho onde o defeito foi medido
- **convite para usuário existente**: **não** atravessa — sai antes por `HttpResponseException`
  em `desviarParaAceite()`, sem chegar a `register()`
- **registro aberto** (`wikis/specs/feat/registro-e-aprovacao/`): o cadastro aprovado atravessa; o
  pendente sai antes, por `redirect()` próprio com `return null`
- **cadastro social por convite e organização**: o fluxo social não passa por `Register::register()`;
  o destino dele é `LoginSocialController::urlDoPainel()`, que já usa `DestinoAposLogin`
- **login unificado / telas externas**: `CadastroUnificado` estende `RegistroPorConvite` e herda a
  resposta nova sem uma linha de código
- **carimbo de painel no acesso** (`tests/Kit/CarimboDePainelNoAcessoTest.php`): com a chave
  ligada, o cadastro passa a carimbar o painel. Verificar se algum CT afirma o contrário

**Risco de regressão concreto**: nenhum CT do kit deve depender de o cadastro honrar uma
pretendida inacessível. Se algum depender, ele estava assertando o defeito, e é o caso da
Proibição 11 — o CT é corrigido no `04` da wiki de origem, com a marca `*(alterado em …)*`.

## Rollback

- **Migration down**: não há migration
- **Feature flag**: não há chave nova. O rollback é remover uma linha —
  `$this->app->bind(RegistrationResponse::class, RespostaDeCadastro::class);` — e o Filament volta
  a decidir. Reversível sem estado
- **Reversão de dados**: nada é gravado além do carimbo de painel no `authentication_log`, que é
  uma coluna já existente e tolerante a nulo

## Dependências

Nenhuma nova, nem Composer nem NPM.

## Riscos

- **A pretendida vem de qualquer lugar, não só de painel.** Pode ser `/boas-vindas`, `/health`, um
  link do site. `painelDe()` devolve `null` para essas, e com a chave ligada `urlPara()` cai no
  painel único ou na escolha — ou seja, uma pretendida legítima fora de painel é **descartada**.
  Mitigação: é exatamente o comportamento que o login já tem desde a ADR-06, e mudá-lo aqui
  criaria a assimetria que esta correção existe para fechar. Registrado em ADR-02
- **O carimbo de painel passa a acontecer no cadastro.** Mitigação: rodar
  `tests/Kit/CarimboDePainelNoAcessoTest.php` e conferir se alguma asserção contava com o acesso
  do cadastro sem painel

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` já tem o channel **`autenticacao`** (`driver` `daily`, 14 dias), e é o channel
de todo o fluxo de auth do kit: `DestinoAposLogin`, `EscolhaDePainel`, `RegistroPorConvite`,
`TelaLoginUnificada`. Conferido por `grep -n "autenticacao" config/logging.php`.

### Decisão

**Reusar `autenticacao`.** Channel novo para uma classe de dez linhas seria ruído: o leitor que
investiga "por que a conta nova foi para lá" precisa das linhas do cadastro **no mesmo arquivo**
das linhas do login, que é onde `DestinoAposLogin` já escreve.

E há um segundo motivo, mais forte: com a chave ligada a correção delega a `urlPara()`, que **já
loga** `[DestinoAposLogin@urlPara] Destino após login decidido` com painéis, pretendida e destino.
Logar de novo na resposta duplicaria a linha. Portanto:

| Ramo | Log |
|---|---|
| chave ligada | nenhum log próprio — `DestinoAposLogin::urlPara()` já registra |
| chave desligada, pretendida descartada | **um** `warning` em `descartarPretendidaInacessivel()`, porque é a única decisão que ninguém mais registra |
| chave desligada, pretendida vazia ou acessível | nenhum log — não houve decisão a registrar |

## Estrutura de Implementação

### 1. Método público no decisor: descartar a pretendida inacessível

> Skills: `laravel-best-practices`, `ponytail`
> Atende: RQ-02, RQ-03

- **Path**: `app/Support/DestinoAposLogin.php` (editar)
- Acrescentar:

```php
/**
 * Esquece a URL pretendida quando ela não é de um painel que a pessoa acessa.
 *
 * Serve ao cadastro com a página única DESLIGADA, onde não existe tela de escolha e o destino
 * continua sendo o do Filament (`redirect()->intended(Filament::getUrl())`): a única coisa
 * errada ali é a pretendida, então é ela que sai.
 */
public static function descartarPretendidaInacessivel(User $user): void
```

*(alterado em 2026-09-08: assinatura `void`, não `bool` — a auditoria Ponytail do step 6 cortou
um retorno que nenhum chamador leria; o observável do descarte é o `warning` no channel.)*

- Corpo: ler `session()->get('url.intended')`; sair se estiver vazia; sair se
  `painelDe($pretendida, paineisDe($user))` devolver um `Panel`; senão
  `session()->forget('url.intended')` e logar
- **Por que aqui e não na resposta**: `painelDe()` é privada e carrega as duas defesas de URL da
  auditoria Blueprint. Reimplementar a checagem na resposta duplicaria essa superfície de
  segurança em dois lugares — o oposto do que a escada do Ponytail pede
- **Logs**:
  - `Log::channel('autenticacao')->warning("[DestinoAposLogin@descartarPretendidaInacessivel] URL pretendida descartada — não é painel acessível | user: {$user->getKey()}", ['user_id' => ..., 'paineis' => [...ids...], 'pretendida' => parse_url($pretendida, PHP_URL_PATH), 'motivo' => 'pretendida_inacessivel'])`
  - só nesse caso; os dois `false` são silenciosos de propósito

### 2. A resposta de cadastro do kit

> Skills: `laravel-best-practices`, `ponytail`
> Atende: RQ-01, RQ-02, RQ-03, RQ-04

- **Path**: `app/Http/Responses/RespostaDeCadastro.php` (criar)
- Molde exato de `RespostaDeLogin`: `final class RespostaDeCadastro extends RegistrationResponse`,
  com `public function toResponse($request): RedirectResponse|Redirector`
- Lógica, na ordem:
  1. `$user = Filament::auth()->user();` — não é `User`? `return parent::toResponse($request);`
     (o cadastro pendente já saiu antes; isto é a guarda de tipo, não zelo)
  2. chave ligada (`ConfiguracaoDoLogin::unificado()`) →
     `return redirect()->to(DestinoAposLogin::urlPara($user));` — idêntico ao login
  3. chave desligada → `DestinoAposLogin::descartarPretendidaInacessivel($user);` e depois
     `return parent::toResponse($request);`
- **Logs**: nenhum próprio. Ver a tabela da seção de channel — os dois ramos já logam no lugar
  onde a decisão acontece, e uma terceira linha aqui só repetiria

### 3. O bind no container

> Skills: `laravel-best-practices`
> Atende: RQ-01, RQ-03

- **Path**: `app/Providers/KitServiceProvider.php` (editar `configureLoginUnificado()`, `:527`)
- Acrescentar, ao lado do bind de login:
  `$this->app->bind(RegistrationResponse::class, RespostaDeCadastro::class);`
- Import de `Filament\Auth\Http\Responses\Contracts\RegistrationResponse` e de
  `App\Http\Responses\RespostaDeCadastro`
- **Corrigir o docblock do método**: a frase "com a chave desligada ela é idêntica à do Filament"
  vale para o login e **não** vale para o cadastro — a pretendida inacessível é descartada nas
  duas configurações. Marcar a razão em uma linha
- **Logs**: nenhum. Bind de container não é execução

### 4. Testes

> Skills: `pest-testing`
> Atende: RQ-01, RQ-03, RQ-04

- Escrever os CTs do `04-casos-de-teste.md`, na numeração contínua a partir de **CT-67**
- Arquivo previsto: `tests/Kit/DestinoAposCadastroTest.php` (`group('kit')`), mais a célula de
  tenancy em `tests/Tenancy/` se o `04` a exigir
- Reusar os helpers de `tests/Pest.php`: `ligarLoginUnificado()`, `painelRegistradoEmTeste()`,
  `organizacaoComRegistro()`
- Rodar também `tests/Kit/CarimboDePainelNoAcessoTest.php`, `ConviteTest.php`,
  `ConviteUsuarioExistenteTest.php`, `RegistroAbertoTest.php` e `LoginUnificadoTest.php` — é a
  regressão que o "toca infra compartilhada" obriga

### 5. Docs de usuário

> Atende: RQ-05

- `docs/pt/autenticacao/login-unificado.md` e `docs/en/...`: a seção que explica a URL pretendida
  passa a dizer que a regra vale para o **login e para o cadastro**
- `docs/pt/autenticacao/registro-aberto.md` e `docs/en/...`: uma frase sobre para onde a conta
  nova vai depois de se cadastrar
- **Restrição do CT-50**: as docs não podem conter a string do slug antigo de configurações. Não
  toca esta correção, mas vale ao editar qualquer doc

### 6. CHANGELOG e version

> Atende: RQ-05, RQ-06

- `CHANGELOG.md`: seção nova com o defeito, a causa e a correção
- `config/kit.php` → `version` — só no commit de release, depois do merge

### 7. Reconciliação (step 7 da skill)

- Fechar cada caixa do `03` com evidência inline
- Reverificar as citações `arquivo:símbolo:linha` desta wiki pelo grep da skill
- Preencher `## Conformidade com Rules` para `app/**`, `app/Providers/**`, `tests/**` e
  `wikis/specs/**`

### 8. Quality gate, PR, merge, tag

> Atende: RQ-06

- `feature-quality-gate` → `06-relatorio-qa.md`
- PR, CI verde nos quatro jobs, merge, `config/kit.php` + CHANGELOG, tag, release `--latest`,
  conferir o site publicado

### 9. Validação por `kit:update` em instalação real

> Atende: RQ-07

- Instalação: `D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES KIT\login-unificado-sem-tenancy`
  (hoje na v0.32.2, com repositório git próprio, `login_unificado = true` e as fixtures de convite
  `@validacao.test` já semeadas)
- `php artisan kit:update --all --no-interaction` e conferir que
  `app/Http/Responses/RespostaDeCadastro.php`, `app/Support/DestinoAposLogin.php` e
  `app/Providers/KitServiceProvider.php` foram entregues
- Reproduzir a sequência do laudo no navegador: visitante em `/admin` → login → link do convite →
  cadastro → **conferir que cai no `/app`, não em 403**
- **Toda chamada `git` nessa pasta leva `-C` ou vem depois de `cd ... || exit 1`** — nunca rodar
  git do kit a partir de script que trabalha na pasta de testes

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**. A escada aqui é curta e já foi subida: o degrau que vale é o
> **2 — reusar o que já existe neste código**. `DestinoAposLogin` já decide destino, já valida URL
> pretendida contra painel acessível, já loga no channel certo e já é a fonte do login. A correção
> é uma classe de resposta de ~12 linhas e um bind.
>
> O que foi recusado, e por quê:
> - regra de destino nova na resposta de cadastro → duplicaria `painelDe()`, que é superfície de
>   segurança
> - tela de escolha de painel com a chave desligada → feature nova disfarçada de correção
> - channel de log próprio → ver a seção de channel
>
> Arquivos wiki, código, commits e PRs são boundary do Caveman — prosa normal.

## Mapeamentos

| Chave | Ramo da resposta | Destino |
|---|---|---|
| `kit.login.unificado = true` | `DestinoAposLogin::urlPara()` | pretendida acessível → ela; painel único → ele; vários → `/login/painel`; nenhum → `/login/painel`, que encerra a sessão |
| `kit.login.unificado = false`, pretendida de painel acessível | `parent::toResponse()` | a pretendida (`redirect()->intended()`) |
| `kit.login.unificado = false`, pretendida inacessível | descarta + `parent::toResponse()` | `Filament::getUrl()` do painel corrente |
| `kit.login.unificado = false`, sem pretendida | `parent::toResponse()` | `Filament::getUrl()` — comportamento de hoje, intacto |

## Testes

> Ver `04-casos-de-teste.md`. Sem `05-casos-de-teste-browser.md`: nenhum cenário exige navegador
> (ver o gate na seção `## Superfície de UI`); o motivo fica registrado no `04`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --memory-limit=2G`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest --filter=DestinoAposCadastro --compact`
- [ ] Regressão: `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [ ] `vendor/bin/pest --parallel --tia` — confere o impacto real contra a seção `## Impacto`
- [ ] Citações `arquivo:símbolo:linha` reverificadas
- [ ] `kit:update` na instalação de teste + reprodução no navegador

## Commits

- `:bug: fix(auth): destino depois do cadastro passa pelo decisor do login`
- `:white_check_mark: test: CT-67.. destino depois do cadastro`
- `:memo: docs: destino depois do cadastro nas docs pt/en e no CHANGELOG`
- `:memo: docs(wiki): fix/destino-apos-cadastro`
