# Casos de Teste — Login social com Google

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando implementação — ela não existe
> ainda. O `01` entrou só para paths, rotas e a tabela `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Callback de OAuth (vínculo, criação, e-mail verificado, 2FA, segredo) | 3 — integração externa + regra com muitas condições | 3 — autorização, dado de terceiro, irreversível (conta criada) | **9** | **completo** |
| Disponibilidade (interruptor + credenciais → botão e rotas) | 2 — integra com três painéis e dois arquivos de config | 3 — autorização: rota pública viva com a feature desligada | **6** | **padrão** |
| Rodapé da tela de login | 1 — blade novo isolado | 3 — XSS armazenado em página **não autenticada** | **3** | **mínimo**, técnica escalada |
| Documentação e `.env.example` | 1 | 1 | **1** | **mínimo** |

- **Técnicas aplicadas**: EP (partição), EP exaustiva de enum, BVA (não se aplica — sem faixa
  ordenável), tabela de decisão, matriz estado × operação, rastreio de efeito, normalização,
  varredura de padrão de fronteira.
- **Técnica escalada acima do perfil da área**: o rodapé é `mínimo` (1 cenário por regra) e
  recebe **dois** — o cenário de escape (CT-15) existe porque a implementação defeituosa plausível
  (`{!! !!}`) é XSS numa página pública, e EP sobre "tem texto / não tem texto" não a distingue.
- **Cenários**: 24 CT (`04`) + 1 CT-B (`05`) · **Regras**: 15 · **Mutantes previstos**: 63 ·
  **Sem matador**: 2 (declarados em R4 e R7) · **Retirados por serem falsos**: 1 (M59 — ver R14).

### Divergência declarada: rule do projeto vence a skill

A skill sugere `pest --parallel --tia` como padrão. `.ai/rules/testes-browser.md` **mediu** que
`--parallel` derruba 4 dos 11 cenários de browser e que, sem PCOV, o `--tia` não termina (abortado
após 35 min). **A rule vence.** Os comandos desta feature são:

```bash
php artisan test --testsuite=Kit --filter=LoginSocialGoogle --compact
php artisan test --testsuite=Unit,Feature,Kit,Tenancy
composer test:browser
```

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | `App\Support\ConfiguracaoDoLogin`; `LoginComGoogleController`; duas rotas; dois blades; um método no `KitServiceProvider`; blocos em `config/services.php` e `config/kit.php`; quatro chaves no `.env.example`; dependência `laravel/socialite`. **Nenhuma migration, nenhuma model, nenhuma coluna** | CT-17, CT-18 |
| **F**unction | redirecionar ao provedor; receber o callback; verificar o e-mail no provedor; casar a conta; criar conta (condicionado); autenticar; escolher o destino; exibir/ocultar o botão; exibir/ocultar o rodapé; derrubar a rota quando indisponível. **Função administrativa escondida**: nenhuma | CT-01…CT-16, CT-19…CT-23 |
| **D**ata | e-mail (exato / caixa alta / com espaços nas bordas / ausente); `email_verified` (`true` / `false` / ausente / string `"false"`); nome (presente / vazio); as três credenciais (preenchida / vazia / ausente); texto do rodapé (vazio / texto / HTML); estado da conta (existe / não existe / com 2FA confirmado / sem papel). **Dado de outro tenant**: não se aplica — a autenticação é anterior ao tenant, e `users` não é escopada por organização | CT-01, CT-06, CT-07, CT-11, CT-14, CT-15, CT-22 |
| **I**nterfaces | duas rotas HTTP GET e a tela de login (três painéis). Nenhum comando artisan, nenhum job, nenhum webhook, nenhum import | CT-02, CT-03, CT-04 |
| **P**latform | **sessão** — o `state` de CSRF do Socialite depende dela (`AbstractProvider.php:166,288`); SQLite em memória na suíte contra MySQL/Postgres em produção, o que importa para o `lower(email)` da busca normalizada (SQLite `lower()` é ASCII-only; MySQL costuma ter collation `_ci`, então a comparação é redundante lá e necessária aqui — a redundância é de propósito); nenhuma dependência de Redis, fila ou storage | CT-05, CT-07 |
| **O**perations | usuário real é **qualquer visitante** da tela de login, sem sessão. Uso indevido previsto: chamar `/auth/google/callback` direto sem passar pelo redirect (state ausente) e chamar as duas rotas com a feature desligada. Volume: throttle de 10/min por IP | CT-02, CST-05 → CT-05 |
| **T**ime | o `state` vive na sessão e morre com ela; nenhum agendamento; nenhuma expiração própria; nenhuma comparação de data. **Concorrência**: dois callbacks simultâneos do mesmo e-mail com registro aberto poderiam criar duas contas — mas `users.email` é único (constraint do schema), então a segunda falha no banco. Declarado, não coberto: o ramo de criação só roda com o registro aberto, que não é desta entrega | declarado |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o botão aparece **se e somente se** o interruptor está ligado **e** as três credenciais estão preenchidas | Disponibilidade (padrão) | RQ-02, RQ-06, RQ-13 | tabela de decisão | CT-01 |
| R2 — indisponível, as duas rotas de OAuth respondem 404 | Disponibilidade (padrão) | RQ-06, RQ-15 | matriz estado × operação | CT-02, CT-03 |
| R3 — quando aparece, o botão fica **depois** do formulário e traz o ícone do Google | Disponibilidade (padrão) | RQ-03, RQ-09 | EP + ordem no DOM | CT-04 |
| R4 — o `state` de CSRF permanece ligado; callback sem state válido não autentica | Callback (completo) | RQ-01 | EP, partição inválida isolada + rastreio de não-efeito | CT-05 |
| R5 — o login autentica quem já tem conta com aquele e-mail, comparado de forma normalizada | Callback (completo) | RQ-02, RQ-07 `@premissa` | EP + normalização | CT-06, CT-07 |
| R6 — sem conta e com o registro fechado, o callback **recusa**: não cria e não autentica | Callback (completo) | RQ-07, RQ-14 `@premissa` | tabela de decisão | CT-08 |
| R7 — quem se **registra** por login social vai para a tela do próprio perfil; quem só autentica vai para o painel | Callback (completo) | RQ-07 | tabela de decisão | CT-09, CT-10 |
| R8 — e-mail não verificado no provedor é recusado | Callback (completo) | RQ-01 | EP exaustiva do valor de `email_verified` | CT-11 |
| R9 — o `client_secret` não aparece em nenhuma saída | Callback (completo) | RQ-04 | rastreio de não-efeito | CT-12, CT-13 |
| R10 — o rodapé aparece na tela de login quando há texto, e não aparece quando não há | Rodapé (mínimo) | RQ-10, RQ-11 | EP | CT-14 |
| R11 — o texto do rodapé é **escapado** | Rodapé (escalado) | RQ-11 | EP com valor discriminante | CT-15 |
| R12 — o login por Google **não** contorna o 2FA | Callback (completo) | RQ-01 | estado × operação | CT-16 |
| R13 — as chaves novas estão declaradas no `.env.example` e documentadas nos dois READMEs | Doc (mínimo) | RQ-04, RQ-12 | EP | CT-17 |
| R14 — a coerção do interruptor não usa `(bool) env()` | Disponibilidade (fronteira) | RQ-13 | varredura de padrão | CT-18 |
| R15 — o login por Google deixa rastro: log no channel `autenticacao` e linha na trilha de acesso | Callback (completo) | RQ-01 | rastreio de efeito (3 direções) | CT-19, CT-20, CT-21 |

Cláusulas sem regra própria, com justificativa:

- **RQ-05** (`redirect` é `/auth/google/callback`) — não é regra de comportamento, é um valor
  literal. Fica coberta como `Então` dentro de CT-03 (a rota existe nesse path) e CT-17
  (o valor está no `config/services.php`).
- **RQ-08** (só Google agora) — é restrição de escopo, não comportamento observável. Um cenário
  "não existe rota do GitHub" seria teste de ausência de feature não pedida.
- **RQ-12** (bem documentado) — R13 cobre a parte verificável (as chaves existem nos dois
  READMEs). "Bem" não é falsificável e não vira cenário.
- **RQ-15** (reflete em tudo que vem) — não é regra própria: é o que R1 + R2 + R7 medem juntas.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `ConfiguracaoDoLogin::googleDisponivel()` (nome do método) | escolha de implementação | detalhe do cenário; os `Então` afirmam sobre o **botão** e sobre o **404**, não sobre o retorno do método |
| `App\Support\ConfiguracaoDoLogin` como classe | escolha de implementação (ADR-02) | usado nos cenários de fronteira (CT-18) só como caminho de arquivo |
| nomes das rotas `auth.google.redirect` / `auth.google.callback` | escolha de implementação | detalhe; o path `/auth/google/callback` **é** oráculo, porque o requisito o escreve literalmente (RQ-05) |
| `throttle:10,1` | só o PRD determina; o requisito não fala de limite | **não vira cenário.** Nenhum `Então` sobre 429 |
| textos das mensagens de recusa | comportamento visível que o requisito não determina | **pergunta** (abaixo). Os cenários afirmam "não autenticado" e "não criado", nunca o texto |
| motivos de log (`email_nao_verificado`, …) | só o PRD determina, e são chaves de log | usados como âncora em CT-20, porque log é **efeito** que o requisito pede rastrear via RQ-01; o texto exato não é oráculo, o `motivo` é |
| a lista `Str::mask($email, '*', 3)` | convenção do projeto, não do requisito | é oráculo em CT-13/CT-19 porque a **ausência do e-mail em claro** é decisão de segurança do ADR-09, e a régua do kit é a forma dela |

**Perguntas em aberto** (já replicadas em `00-requisito.md` → `## Ambiguidades`):

- **Criar conta ou só autenticar?** — bloqueia R6 e R7. Premissa: só autentica, salvo registro
  aberto. Cenários `@premissa`: CT-08, CT-09, CT-10.
- **Qual papel recebe quem se registra por social?** — bloqueia a metade "e consegue abrir o
  perfil" de R7. Premissa: nenhum papel; o destino é a URL do perfil e o 403 de lá é correto.
  Cenário `@premissa`: CT-09, cujo `Então` é sobre o **destino do redirecionamento**, não sobre
  conseguir abrir a tela.
- **Texto das mensagens de recusa** — não bloqueia nada; nenhum cenário afirma texto de mensagem.

## Setup Global

### Personas

Três, e a distinção entre elas é discriminante — persona colapsada (a mesma pessoa em todos os
papéis) deixa a barreira de identidade sem cenário:

- `pessoa com conta` — `usuario('ja.tem@example.com')` (helper de `tests/Pest.php:312`)
- `pessoa sem conta` — nenhum registro em `users` com o e-mail que o provedor devolve
- `pessoa com conta e 2FA confirmado` — `usuario()` + `enableTwoFactorAuthentication()` + confirmação,
  o mesmo arranjo de `tests/Kit/TelasDeAutenticacaoTest.php:191-197`
- `operador do painel` — `usuarioDoKit('master_global')` (`tests/Pest.php:387`), só onde é preciso
  abrir uma página autenticada

### Fixtures

Nenhuma factory nova. `App\Models\User` tem factory (`Database\Factories\UserFactory`), mas os
cenários usam os helpers `usuario()`/`usuarioCom()` do `tests/Pest.php`, que é o que os arquivos
vizinhos de `tests/Kit` fazem.

O usuário do provedor é `Laravel\Socialite\Two\User::fake([...])`
(`vendor/laravel/socialite/src/Two/User.php:43-62`) — ele faz `setRaw($attributes)`, e é por isso
que passar `email_verified => false` chega ao `getRaw()` e torna R8 testável sem rede.

### Fakes

- `Socialite::fake('google', User::fake([...]))` — **em todos** os cenários de callback, exceto
  CT-05, que precisa do provedor **real** para exercer o `state` (ver a nota de R4).
- `espiarAutenticacao()` (`tests/Pest.php:397`) nos cenários de log — espia **só** o channel
  `autenticacao` e deixa os outros reais.
- `Notification::fake()` **não** é usado: as notificações do Filament nesta feature são flash de
  sessão, não `Illuminate\Notifications`.
- **Nenhum `Http::fake()` nem `Http::preventStrayRequests()`**, e é preciso dizer por quê: o
  Socialite usa o Guzzle **dele** (`AbstractProvider::getHttpClient()`), não o cliente do
  Laravel, então a facade `Http` não intercepta nem impede nada que ele faça. Uma versão
  anterior deste arquivo prometia `preventStrayRequests()` como rede de segurança do CT-05 —
  era falso. Quem garante que nenhum caso sai para a rede são duas coisas verificadas no
  vendor: o `FakeProvider` nunca chama HTTP (`Testing/FakeProvider.php:61-78`), e no CT-05 a
  `InvalidStateException` é lançada **antes** da primeira chamada
  (`Two/AbstractProvider.php:230-241`).

### Configuração por cenário

`config()->set()` para ligar/desligar o interruptor, escrever as credenciais e o rodapé. **Uma
exceção deliberada**: CT-01 tem uma linha que afirma o **valor efetivo de fábrica**, e o `Dado`
declara que `KIT_SOCIALITE_GOOGLE` **não** está fixado no `phpunit.xml` (ao contrário de
`KIT_COR_PRIMARIA`, `KIT_DEMO`, `KIT_HUB` e os três de tenancy, que estão com `force="true"`).
Sem essa declaração o cenário mediria o `phpunit.xml`, não o `config/kit.php`.

### Estratégia de DB

`RefreshDatabase` global, aplicado em `tests/Pest.php:45-48` para `->in('Kit')`. Sem seeder de
papéis, salvo CT-16 e os cenários que abrem página autenticada — papel é o que dá acesso a painel
(`.ai/rules/testes.md`, tabela de papéis por suíte).

Arquivo: `tests/Kit/LoginSocialGoogleTest.php`, grupo `kit`, suíte **Kit** (single-tenant). Nada
aqui depende de organização, e `admin_app` — o único papel que só existe em `tests/Tenancy` — não
é usado.

---

## Regra R1 — o botão aparece se e somente se o interruptor está ligado E as três credenciais estão preenchidas

> `RQ-02`, `RQ-06`, `RQ-13` · área **Disponibilidade** · perfil **padrão** ·
> técnica: **tabela de decisão** (2 condições × 4 combinações; nenhuma colapsada, porque a ação
> difere em três das quatro linhas apenas pelo motivo, e a quarta é a única que exibe)

```gherkin
# language: pt

Funcionalidade: Botão de entrar com Google na tela de login

  Regra: o botão só aparece com o interruptor ligado e as três credenciais preenchidas

    Esquema do Cenário: [CT-01] a exibição do botão pela tabela de decisão
      Dado que o interruptor do login com Google está "<interruptor>"
      E que as credenciais do Google estão "<credenciais>"
      Quando um visitante abre a tela de login do painel /app
      Então o botão "Entrar com Google" "<visibilidade>" na página

      Exemplos:
        | interruptor          | credenciais           | visibilidade | # linha da tabela        |
        | de fábrica (ausente) | ausentes              | não aparece  | default do kit           |
        | desligado            | as três preenchidas   | não aparece  | interruptor vence        |
        | ligado               | client_secret vazio   | não aparece  | credencial incompleta    |
        | ligado               | as três preenchidas   | aparece      | a única que exibe        |
```

Notas de execução:

- A linha "de fábrica" é a que exige o `Dado` declarando que a chave **não** está no
  `phpunit.xml`. Ela é o único cenário desta feature que mede o default real.
- A linha "client_secret vazio" usa **`''`, não ausência**: valor vazio é o caso real do kit
  (`.ai/rules/config.md`), e `isset()` passaria por ele.
- Camada: `Feature` — `$this->get('/app/login')` + `assertSee` / `assertDontSee`. Não é browser:
  presença e ausência de texto no HTML não precisam de navegador.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | default do interruptor é `true` | CT-01, linha "de fábrica" |
| M2 | `\|\|` no lugar de `&&` entre interruptor e credenciais | CT-01, linhas 2 e 3 |
| M3 | só o interruptor é conferido; credenciais não | CT-01, linha 3 |
| M4 | só as credenciais são conferidas; interruptor não | CT-01, linha 2 |
| M5 | `isset()` no lugar de `filled()` nas credenciais | CT-01, linha 3 (valor `''`) |
| M6 | confere `client_id` e `redirect`, esquece `client_secret` | CT-01, linha 3 (é o `client_secret` que está vazio) |

---

## Regra R2 — indisponível, as duas rotas de OAuth respondem 404

> `RQ-06`, `RQ-15` · área **Disponibilidade** · perfil **padrão** ·
> técnica: **matriz estado × operação**. Estados: {indisponível, disponível}. Operações: {redirect,
> callback} — as duas metades do fluxo. Quatro células, todas exercitadas: duas inválidas (404) e
> **duas válidas**, porque coluna sem célula válida deixa a operação sem prova de que ela funciona.

```gherkin
# language: pt

Funcionalidade: Rotas de OAuth do Google

  Regra: com o login social indisponível, as rotas de OAuth não existem funcionalmente

    Esquema do Cenário: [CT-02] as duas rotas caem quando o login social está indisponível
      Dado que o interruptor do login com Google está "<interruptor>"
      E que as credenciais do Google estão "<credenciais>"
      Quando um visitante acessa "<rota>"
      Então a resposta é 404
      E o visitante continua não autenticado

      Exemplos:
        | interruptor | credenciais         | rota                    | # célula          |
        | desligado   | as três preenchidas | /auth/google/redirect   | inválida, redirect |
        | desligado   | as três preenchidas | /auth/google/callback   | inválida, callback |
        | ligado      | client_id vazio     | /auth/google/redirect   | inválida, redirect |
        | ligado      | client_id vazio     | /auth/google/callback   | inválida, callback |

    Esquema do Cenário: [CT-03] as duas rotas funcionam quando o login social está disponível
      Dado que o login com Google está disponível
      Quando um visitante acessa "<rota>"
      Então a resposta não é 404
      E a resposta é "<resultado>"

      Exemplos:
        | rota                  | resultado                                        | # célula        |
        | /auth/google/redirect | um redirecionamento para fora da aplicação       | válida, redirect |
        | /auth/google/callback | um redirecionamento de volta para a tela de login | válida, callback |
```

Notas:

- CT-03 na linha do `callback` chega sem `state` e sem `code`, então o resultado esperado é a
  recusa **tratada** — o que também prova que o `catch` existe. É a célula válida da coluna
  `callback` no sentido de "a rota está no ar e responde", que é o que R2 afirma; o comportamento
  de recusa em si é de R4.
- CT-03 na linha do `redirect` usa `Socialite::fake('google')`, cujo `redirect()` devolve
  `https://socialite.fake/google/authorize`
  (`vendor/laravel/socialite/src/Testing/FakeProvider.php:61-64`) — nenhuma chamada de rede.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | só o botão é escondido; as rotas ficam vivas | CT-02, todas as linhas |
| M8 | o guarda está no `redirect` e falta no `callback` (o clássico: quem escreve pensa no botão) | CT-02, linhas 2 e 4 |
| M9 | o guarda está no `callback` e falta no `redirect` | CT-02, linhas 1 e 3 |
| M10 | o guarda confere só o interruptor, e a rota vive com credencial incompleta | CT-02, linhas 3 e 4 |
| M11 | o guarda derruba a rota **sempre** (condição negada) | CT-03, as duas linhas |

---

## Regra R3 — quando aparece, o botão fica depois do formulário e traz o ícone do Google

> `RQ-03`, `RQ-09` · área **Disponibilidade** · perfil **padrão** · técnica: **EP + ordem no DOM**

```gherkin
# language: pt

Funcionalidade: Posição e ícone do botão de entrar com Google

  Regra: o botão fica abaixo do formulário e traz o ícone do Google

    Esquema do Cenário: [CT-04] o botão aparece depois do formulário, com o ícone, nos três painéis
      Dado que o login com Google está disponível
      Quando um visitante abre a tela de login do painel "<painel>"
      Então o campo de senha aparece antes do botão "Entrar com Google" no HTML
      E o botão traz um ícone com as quatro cores da marca do Google
      E o botão aponta para a rota de redirecionamento do Google

      Exemplos:
        | painel |
        | app    |
        | admin  |
        | infra  |
```

Notas:

- **Os três painéis, exaustivo e não amostrado**, pela mesma razão que
  `tests/Kit/TelasDeAutenticacaoTest.php:76-78` dá: o defeito histórico do kit nessa área é
  configurar um painel e esquecer os outros dois. Aqui a registração é única (ADR-05), então o
  cenário é o que prova que a registração única de fato alcança os três.
- "antes no HTML" com `assertSeeInOrder([...])` — é a asserção que distingue "abaixo do
  formulário" de "acima". `assertSee` do botão sozinho ficaria verde com o botão no topo, e
  RQ-03 é explícito sobre a posição.
- "as quatro cores da marca" — a âncora é o conjunto dos quatro valores hexadecimais do SVG
  oficial. É o exemplo **discriminante**: `assertSee('svg')` ficaria verde com qualquer ícone,
  inclusive um Heroicon genérico, que é o mutante M14.
- Camada: `Feature`. Se o botão está clicável e visível é outra pergunta, e é CT-B01.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | o botão é registrado em `AUTH_LOGIN_FORM_BEFORE` (acima do formulário) | CT-04 (ordem) |
| M13 | o hook é registrado só no painel `app` | CT-04, linhas `admin` e `infra` |
| M14 | um ícone genérico de Heroicon no lugar do logo do Google | CT-04 (as quatro cores) |
| M15 | o `href` aponta para o callback em vez do redirect | CT-04 (`href`) |

---

## Regra R4 — o `state` de CSRF permanece ligado; callback sem state válido não autentica

> `RQ-01` · área **Callback** · perfil **completo** ·
> técnica: **EP com a partição inválida isolada** + **rastreio de não-efeito**

```gherkin
# language: pt

Funcionalidade: Proteção de CSRF no retorno do Google

  Regra: o retorno do Google sem o state da sessão não autentica ninguém

    Cenário: [CT-05] o callback chamado direto, sem state na sessão, recusa sem autenticar
      Dado que o login com Google está disponível
      E que existe uma conta com o e-mail "ja.tem@example.com"
      E que a sessão não guarda nenhum state
      Quando alguém acessa o callback do Google com um code e um state inventados
      Então o visitante continua não autenticado
      E nenhuma conta nova foi criada
      E o visitante é devolvido para a tela de login
```

Notas de execução — **este é o único cenário de callback sem `Socialite::fake()`**, e por um
motivo estrutural: `FakeProvider::user()` devolve o usuário falso **sem passar pela verificação de
state** (`vendor/laravel/socialite/src/Testing/FakeProvider.php:71-78`). Um cenário faked não pode
falsificar esta regra.

Com o provedor **real** também não há rede: `AbstractProvider::user()` chama `hasInvalidState()`
**antes** de `getAccessTokenResponse()` (`vendor/laravel/socialite/src/Two/AbstractProvider.php:230-241`),
e `hasInvalidState()` só lê a sessão e o input (`:282-290`). A `InvalidStateException` é lançada
sem um único byte de rede. **Não há rede de segurança de `Http`** para isso: o Socialite usa o
Guzzle dele e a facade `Http` não o intercepta. A garantia é a ordem lida no vendor, e é por isso
que ela está citada com `arquivo:linha` em vez de assumida.

- "nenhuma conta nova" é a metade do não-efeito que separa "recusa" de "recusa **depois** de
  gravar", que é o anti-padrão que a skill nomeia.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | `->stateless()` acrescentado "para simplificar o teste" | CT-05 — com `stateless()`, `hasInvalidState()` devolve `false` (`:284-286`) e o fluxo segue para a rede, estourando no `preventStrayRequests` ou autenticando |
| M17 | o `catch` engole a exceção e segue autenticando o primeiro usuário que achar | CT-05 (não autenticado) |
| M18 | o `catch` grava a conta antes de recusar | CT-05 (nenhuma conta nova) |
| M19 | não há `catch`; a exceção vira 500 | CT-05 (devolvido para o login) |
| M20 | o `catch` autentica um usuário nulo e a sessão fica meio aberta | CT-05 (não autenticado) |
| M21 | ⚠️ o `state` é comparado com `==` no lugar de `hash_equals` (timing attack) | **sem matador** — a comparação é do vendor (`:290`), não do kit. Lacuna declarada: tentado alcançar por CT com dois states de mesmo tamanho; nenhum oráculo observável distingue as duas comparações num teste funcional. Fica coberto por leitura do vendor no ADR-01 |

---

## Regra R5 — o login autentica quem já tem conta com aquele e-mail, comparado de forma normalizada

> `RQ-02`, `RQ-07` `@premissa` · área **Callback** · perfil **completo** ·
> técnica: **EP + normalização**

```gherkin
# language: pt

Funcionalidade: Autenticação pelo Google de quem já tem conta

  Regra: o e-mail devolvido pelo Google casa com a conta existente, ignorando caixa e espaços

    Cenário: [CT-06] quem já tem conta entra pelo Google
      Dado que o login com Google está disponível
      E que existe uma conta com o e-mail "ja.tem@example.com"
      E que o Google devolve o e-mail "ja.tem@example.com" verificado
      Quando o visitante volta do Google para o callback
      Então o visitante está autenticado como a conta de "ja.tem@example.com"
      E o total de contas continua sendo 1

    Esquema do Cenário: [CT-07] o casamento por e-mail ignora caixa e espaços nas bordas
      Dado que o login com Google está disponível
      E que existe uma conta com o e-mail "ja.tem@example.com"
      E que o Google devolve o e-mail "<devolvido>" verificado
      Quando o visitante volta do Google para o callback
      Então o visitante está autenticado como a conta de "ja.tem@example.com"
      E o total de contas continua sendo 1

      Exemplos:
        | devolvido               | # partição            |
        | JA.TEM@EXAMPLE.COM      | caixa alta            |
        | Ja.Tem@Example.com      | caixa mista           |
        | "  ja.tem@example.com " | espaços nas bordas    |
```

Notas:

- **"o total de contas continua sendo 1" é o oráculo que separa autenticar de criar.** Sem ele,
  uma implementação `updateOrCreate` (o exemplo da própria doc do Laravel) passa em CT-06 e CT-07
  criando uma segunda conta com o e-mail em caixa alta.
- Os valores de CT-07 são **discriminantes**: `JA.TEM@EXAMPLE.COM` distingue `where('email', $x)`
  de comparação normalizada, e `"  ja.tem@example.com "` distingue `mb_strtolower()` sozinho de
  `mb_strtolower(trim())`. Nenhum dos dois é redondo.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | `where('email', $email)` cru, sem normalização | CT-07, linhas 1 e 2 |
| M23 | normaliza a caixa mas não o `trim` | CT-07, linha 3 |
| M24 | normaliza só o lado do provedor, não o do banco | CT-07, linhas 1 e 2 (a conta está em minúsculas, então este mutante **passa** nelas — mata-se com uma conta gravada em caixa mista; ver nota) |
| M25 | `updateOrCreate` no lugar de buscar-e-autenticar | CT-06 e CT-07 ("total de contas continua sendo 1") |
| M26 | autentica o primeiro usuário da tabela em vez do que casa | CT-06 (autenticado **como** aquela conta) |

> **Nota sobre M24**: para matá-lo, a linha de CT-07 com caixa alta grava a **conta** com
> `Ja.Tem@Example.com` e o provedor devolve `ja.tem@example.com` — invertendo os lados. Fica como
> quinta linha do `Exemplos`, com o rótulo "caixa na conta, não no provedor". Sem ela, uma
> implementação que baixa só o lado do provedor passa em tudo.

---

## Regra R6 — sem conta e com o registro fechado, o callback recusa: não cria e não autentica

> `RQ-07`, `RQ-14` `@premissa` · área **Callback** · perfil **completo** ·
> técnica: **tabela de decisão** (conta existe? × registro aberto?)

```gherkin
# language: pt

Funcionalidade: A porta do convite continua fechada para o login social

  Regra: sem conta e com o registro fechado, o retorno do Google não cria nem autentica

    Cenário: [CT-08] o Google de quem não tem conta é recusado com o registro fechado
      Dado que o login com Google está disponível
      E que o registro aberto está desligado
      E que não existe conta com o e-mail "de.fora@example.com"
      E que o Google devolve o e-mail "de.fora@example.com" verificado
      Quando o visitante volta do Google para o callback
      Então o visitante continua não autenticado
      E não existe conta com o e-mail "de.fora@example.com"
      E o visitante é devolvido para a tela de login
```

Notas:

- É a barreira mais cara da feature. Os três `Então` são a tríade que a skill exige do cenário de
  recusa: recusado, estado **não** mudou, nenhum registro criado.
- O `Dado` "o registro aberto está desligado" é o **default**, não um ajuste — mas é declarado
  porque a chave que o representa ainda não existe (ADR-02) e o cenário precisa dizer qual valor
  efetivo ele mediu.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | `User::updateOrCreate()` como no exemplo da doc do Socialite | CT-08 (não existe conta) |
| M28 | cria a conta e só então confere o registro aberto | CT-08 (não existe conta) |
| M29 | recusa mas autentica um usuário recém-instanciado, sem gravar | CT-08 (não autenticado) |
| M30 | a condição do registro aberto é lida invertida | CT-08 + CT-09 |
| M31 | o default do registro aberto é `true` | CT-08 |

---

## Regra R7 — quem se registra por login social vai para o perfil; quem só autentica vai para o painel

> `RQ-07` · área **Callback** · perfil **completo** · técnica: **tabela de decisão**

```gherkin
# language: pt

Funcionalidade: Destino depois do login social

  Regra: conta recém-criada por login social é levada para a tela do próprio perfil

    Cenário: [CT-09] com o registro aberto ligado, a conta nova vai para o perfil
      Dado que o login com Google está disponível
      E que o registro aberto está ligado
      E que não existe conta com o e-mail "novo@example.com"
      E que o Google devolve o e-mail "novo@example.com" verificado, com o nome "Pessoa Nova"
      Quando o visitante volta do Google para o callback
      Então existe uma conta com o e-mail "novo@example.com" e o nome "Pessoa Nova"
      E o visitante está autenticado como essa conta
      E o visitante é levado para a tela do próprio perfil

    Cenário: [CT-10] quem já tinha conta vai para o painel, não para o perfil
      Dado que o login com Google está disponível
      E que existe uma conta com o e-mail "ja.tem@example.com"
      E que o Google devolve o e-mail "ja.tem@example.com" verificado
      Quando o visitante volta do Google para o callback
      Então o visitante é levado para o painel /app
      E não é levado para a tela do próprio perfil
```

Notas:

- **CT-10 é o contrapeso obrigatório de CT-09.** Sem ele, uma implementação que manda **todo
  mundo** para o perfil passa em CT-09 — e o requisito diz "se a pessoa se registrar", não "sempre".
- CT-09 é `@premissa`: ele afirma sobre o **destino do redirecionamento**, não sobre a pessoa
  conseguir abrir a tela. Conta sem papel recebe 403 no painel, e isso é o comportamento correto
  do kit (`app/Models/User.php:76-105`); ver `## Ambiguidades` do `00`.
- "o nome Pessoa Nova" é `Então` de valor concreto: sem ele, uma implementação que grava o e-mail
  no lugar do nome passa.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | todo login social vai para o perfil | CT-10 |
| M33 | todo login social vai para o painel | CT-09 |
| M34 | a conta nova nasce com o e-mail no campo do nome | CT-09 (nome concreto) |
| M35 | a conta nova é criada mas não autenticada | CT-09 (autenticado) |
| M36 | ⚠️ a conta nova recebe um papel de painel | **sem matador** — asserção sobre papel exigiria decidir **qual** papel é o certo, e essa decisão é da branch `feat/registro-e-aprovacao`. Lacuna declarada: tentado escrever "e a conta nova não tem nenhum papel"; recusado porque congelaria no teste uma decisão que não é desta feature e faria o teste desta wiki reprovar o merge da outra. Registrado como débito no `03-progresso.md`, a conferir no rebase |

---

## Regra R8 — e-mail não verificado no provedor é recusado

> `RQ-01` · área **Callback** · perfil **completo** ·
> técnica: **EP exaustiva** sobre o valor de `email_verified`. Não se amostra: cada valor é uma
> partição, e é a partição `false` que carrega a tomada de conta.

```gherkin
# language: pt

Funcionalidade: E-mail verificado no provedor como pré-condição

  Regra: o kit só aceita e-mail que o Google declara verificado

    Esquema do Cenário: [CT-11] o veredito por valor de email_verified
      Dado que o login com Google está disponível
      E que existe uma conta com o e-mail "vitima@example.com"
      E que o Google devolve o e-mail "vitima@example.com" com email_verified "<valor>"
      Quando o visitante volta do Google para o callback
      Então o resultado é "<resultado>"

      Exemplos:
        | valor           | resultado         | # partição                  |
        | true            | autenticado       | verificado                  |
        | false           | não autenticado   | não verificado — a tomada de conta |
        | ausente         | não autenticado   | provedor omitiu o campo     |
        | a string "false"| não autenticado   | valor textual falso         |
        | a string "0"    | não autenticado   | valor textual falso         |
```

Notas:

- A partição "a string `false`" é o exemplo **discriminante** desta regra: uma implementação com
  `if ($raw['email_verified'] ?? false)` fica verde nas quatro primeiras linhas e **falha** aqui,
  porque `(bool) "false"` é `true`. É o mesmo defeito de ADR-08, do outro lado da fronteira.
- A regra é testável sem rede porque `User::fake()` faz `setRaw($attributes)`
  (`vendor/laravel/socialite/src/Two/User.php:57`).
- A linha "ausente" precisa que a implementação leia as **duas** chaves (`email_verified` e o alias
  legado `verified_email`, ambas populadas por
  `vendor/laravel/socialite/src/Two/GoogleProvider.php:90-92`) e falhe fechado quando nenhuma
  existe. Uma sexta linha cobre o alias: `verified_email = true` e `email_verified` ausente ⇒
  autenticado.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M37 | a verificação de e-mail não é feita (o caso base: ninguém pensou nela) | CT-11, linha `false` |
| M38 | `(bool)` no lugar de `filter_var(..., FILTER_VALIDATE_BOOLEAN)` | CT-11, linhas `"false"` e `"0"` |
| M39 | falha **aberta** quando o campo está ausente (`?? true`) | CT-11, linha `ausente` |
| M40 | lê só `verified_email` e ignora `email_verified` | CT-11, linha `true` (o `email_verified` é a chave do userinfo v3) |
| M41 | recusa **sempre**, mesmo verificado | CT-11, linha `true` |

---

## Regra R9 — o `client_secret` não aparece em nenhuma saída

> `RQ-04` · área **Callback** · perfil **completo** · técnica: **rastreio de não-efeito**

```gherkin
# language: pt

Funcionalidade: O segredo do OAuth não sai

  Regra: o client_secret não aparece em tela nem em log

    Esquema do Cenário: [CT-12] o segredo não aparece no HTML de nenhuma tela de login
      Dado que o login com Google está disponível, com o client_secret "segredo-irreconhecivel-42"
      Quando um visitante abre a tela de login do painel "<painel>"
      Então a página não contém "segredo-irreconhecivel-42"

      Exemplos:
        | painel |
        | app    |
        | admin  |
        | infra  |

    Cenário: [CT-13] o segredo e o e-mail em claro não aparecem no que o log grava
      Dado que o login com Google está disponível, com o client_secret "segredo-irreconhecivel-42"
      E que existe uma conta com o e-mail "ja.tem@example.com"
      E que o Google devolve o e-mail "ja.tem@example.com" verificado
      Quando o visitante volta do Google para o callback
      Então nenhuma mensagem gravada no channel de autenticação contém "segredo-irreconhecivel-42"
      E nenhuma mensagem gravada contém "ja.tem@example.com" em claro
      E ao menos uma mensagem gravada contém o e-mail mascarado
```

Notas:

- O valor `segredo-irreconhecivel-42` é escolhido para ser **discriminante**: uma string que não
  aparece por acidente em nenhum lugar do HTML. Usar `secret` ou `password` produziria falso
  vermelho pelo próprio formulário de senha.
- CT-13 usa `espiarAutenticacao()` (`tests/Pest.php:397`) e assere sobre **as três direções**: o
  segredo não está, o e-mail em claro não está, e o mascarado está. A terceira é o que separa
  "mascarou" de "não logou nada" — sem ela, remover todos os logs deixa o cenário verde.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M42 | o segredo é logado "para depurar a configuração" | CT-13 |
| M43 | o e-mail vai em claro para o log | CT-13 (segunda asserção) |
| M44 | nenhum log é emitido | CT-13 (terceira asserção) |
| M45 | a config é despejada na tela num bloco de depuração | CT-12 |

---

## Regra R10 — o rodapé aparece quando há texto, e não aparece quando não há

> `RQ-10`, `RQ-11` · área **Rodapé** · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Rodapé da tela de login

  Regra: o rodapé aparece na tela de login quando há texto configurado

    Esquema do Cenário: [CT-14] a exibição do rodapé por estado do texto
      Dado que o rodapé da tela de login está "<estado>"
      Quando um visitante abre a tela de login do painel /app
      Então o rodapé "<visibilidade>" na página

      Exemplos:
        | estado                      | visibilidade | # partição       |
        | com o texto "Fiotec 2026"   | aparece      | preenchido       |
        | vazio                       | não aparece  | vazio            |
        | com apenas espaços          | não aparece  | só espaço em branco |
```

Notas:

- A partição "só espaço em branco" é a que distingue `filled()` de `!== null` — e `filled()` já
  trata string de espaços como vazia, então este é o exemplo discriminante do `trim`/`filled`.
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M46 | o rodapé é sempre renderizado, com uma faixa vazia quando não há texto | CT-14, linhas 2 e 3 |
| M47 | `!== null` no lugar de `filled()` | CT-14, linhas 2 e 3 |
| M48 | o rodapé nunca é renderizado | CT-14, linha 1 |

---

## Regra R11 — o texto do rodapé é escapado

> `RQ-11` · área **Rodapé** · técnica **escalada** acima do perfil `mínimo` ·
> técnica: **EP com valor discriminante**

```gherkin
# language: pt

Funcionalidade: Rodapé da tela de login

  Regra: o texto do rodapé é tratado como texto, nunca como HTML

    Cenário: [CT-15] HTML no rodapé sai escapado na tela de login
      Dado que o rodapé da tela de login está com o texto "<script>alert(1)</script>Fiotec"
      Quando um visitante abre a tela de login do painel /app
      Então a página contém o texto escapado, com as entidades de menor e maior
      E a página não contém a tag de script executável
```

Notas:

- Justificativa do escalonamento: o perfil da área é `mínimo` (1 cenário por regra), e R10 já
  consumiu esse cenário. Este existe porque a implementação defeituosa plausível — usar a sintaxe
  de saída não escapada do Blade — é **XSS armazenado numa página não autenticada**, e nenhum
  exemplo de R10 a distingue. Ver ADR-09.
- A âncora é o par de asserções: o escapado **presente** e o executável **ausente**. Só a segunda
  ficaria verde com o rodapé não renderizado (que é M48, de outra regra).
- Camada: `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M49 | a saída não escapada do Blade, "para permitir link no rodapé" | CT-15 |
| M50 | o valor passa por uma sanitização caseira que deixa `<script>` | CT-15 (segunda asserção) |

---

## Regra R12 — o login por Google não contorna o 2FA

> `RQ-01` · área **Callback** · perfil **completo** · técnica: **estado × operação**
> (estado da conta: {sem 2FA, com 2FA confirmado} × operação: {entrar pelo Google})

```gherkin
# language: pt

Funcionalidade: Segundo fator continua obrigatório no login social

  Regra: conta com 2FA confirmado não chega ao painel pelo Google sem o segundo fator

    Cenário: [CT-16] quem tem 2FA confirmado entra pelo Google e cai no desafio
      Dado que o login com Google está disponível
      E que existe uma conta com papel de acesso ao painel /admin e 2FA confirmado
      E que o Google devolve o e-mail dessa conta, verificado
      Quando o visitante volta do Google para o callback
      E em seguida abre uma página do painel /admin
      Então o visitante é levado para o desafio de dois fatores
      E não vê o conteúdo da página do painel
```

Notas:

- É o cenário de bypass. A barreira não é do kit: é o middleware `MustTwoFactor`
  (`vendor/jeffgreco13/filament-breezy/src/Middleware/MustTwoFactor.php:42-43`), que o kit recebe
  por default ao chamar `enableTwoFactorAuthentication()` sem tocar no 4º parâmetro
  (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:29`).
  O cenário existe porque **a barreira ser de terceiro não a torna garantida**: um mutante que
  passe `authMiddleware: false` ou que autentique por outro guard a desliga sem erro nenhum.
- Painel `/admin` e não `/app`: `/admin` não tem tenancy, e o `MustTwoFactor` tem um `return`
  antecipado quando há tenancy e a rota não traz o parâmetro `tenant` (`:31-33`). Escolher o
  painel errado faria o cenário passar por acidente.
- Precisa dos seeders de papéis (`ShieldPermissionsSeeder`, `PapeisSeeder`) e do papel `admin`,
  que existe nas duas suítes (`.ai/rules/testes.md`).
- Camada: `Feature`, com dois requests — o callback e a página. O `E em seguida` é continuação do
  mesmo `Quando`, não um segundo `Quando`: a ação sob teste é "entrar pelo Google", e abrir a
  página é a observação.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M51 | o callback marca a sessão de 2FA como válida "porque o Google já autenticou" | CT-16 |
| M52 | o callback autentica por um guard sem o middleware do painel | CT-16 |
| M53 | o callback redireciona direto para dentro do painel, ignorando o middleware | CT-16 |

---

## Regra R13 — as chaves novas estão no `.env.example` e nos dois READMEs

> `RQ-04`, `RQ-12` · área **Doc** · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Documentação das chaves do login social

  Regra: quem instala o kit encontra as chaves no .env.example e nos dois READMEs

    Esquema do Cenário: [CT-17] as chaves e o caminho do callback estão declarados
      Dado o arquivo "<arquivo>"
      Quando ele é lido
      Então ele menciona "<termo>"

      Exemplos:
        | arquivo       | termo                    |
        | .env.example  | KIT_SOCIALITE_GOOGLE     |
        | .env.example  | GOOGLE_CLIENT_ID         |
        | .env.example  | GOOGLE_CLIENT_SECRET     |
        | .env.example  | KIT_LOGIN_RODAPE         |
        | README.md     | KIT_SOCIALITE_GOOGLE     |
        | README.md     | /auth/google/callback    |
        | README.en.md  | KIT_SOCIALITE_GOOGLE     |
        | README.en.md  | /auth/google/callback    |
```

Notas:

- É asserção de **presença** sobre o texto cru, então não precisa do filtro de comentário que
  `.ai/rules/testes.md` exige — o filtro é obrigatório apenas na asserção de **ausência**, e é
  CT-18 que tem uma.
- `/auth/google/callback` nos READMEs cobre RQ-05 do lado da documentação: quem cadastra a URI no
  console do Google precisa achar o caminho escrito.
- Camada: `Unit` seria mais barata, mas `tests/Pest.php:45-48` liga o `TestCase` da aplicação a
  `->in('Kit')` e não há ligação equivalente para `Unit` neste projeto — a escada real começa na
  camada que o arnês sustenta. Fica em `tests/Kit`, junto dos vizinhos que fazem o mesmo
  (`CacheDeViewsNoDockerTest`, `QualidadeDeCodigoTest`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M54 | as chaves entram no `.env.example` e ninguém documenta nos READMEs | CT-17, linhas de README |
| M55 | só o `README.md` é atualizado; o `README.en.md` fica atrás | CT-17, linhas de `README.en.md` |
| M56 | as chaves são documentadas mas não entram no `.env.example` | CT-17, linhas de `.env.example` |

---

## Regra R14 — o interruptor falha FECHADO diante de valor irreconhecível

> `RQ-13` · área **Disponibilidade** · técnica: **EP sobre o valor da env** (a técnica original
> era "varredura de padrão de fronteira", e ela foi **abandonada** — o motivo está abaixo, e é o
> achado mais útil deste arquivo)

### Por que esta regra foi reescrita durante a implementação

A versão original afirmava que `(bool) env('KIT_SOCIALITE_GOOGLE', false)` era antipadrão, com a
justificativa de que a string `"false"` viraria `true`, e mandava **varrer o `config/kit.php`
inteiro** afirmando a ausência do padrão — aplicando o método de `.ai/rules/specs.md` ("ao achar
defeito numa fronteira, varra o padrão no repo antes de consertar o ponto").

**O caso reprovou, e estava certo em reprovar**: três chaves irmãs do mesmo arquivo usam
`(bool) env(...)` — `kit.tenancy.enabled` (`:83`), `kit.demo` (`:115`) e `kit.hub` (`:144`) — e
**nenhuma delas está errada**. O `Env::getOption()` do Laravel já converte `"true"`, `"false"`,
`"(false)"`, `"null"` e `"empty"` em valor PHP antes de devolver
(`vendor/laravel/framework/src/Illuminate/Support/Env.php:252-262`).

É a armadilha que a própria `.ai/rules/specs.md` descreve, vivida aqui: a **conclusão** (usar
`filter_var`) continua certa, e o **motivo** escrito estava factualmente errado. Se a varredura
tivesse sido "corrigida" no sentido oposto — trocando as três irmãs — a feature teria mexido em
`kit.tenancy.enabled`, a chave mais consequente do kit, para consertar o que não estava quebrado.

O que a medição mostrou, e que é a regra de verdade: a diferença é de **direção**, não de
correção. `off`, `no` e qualquer lixo dão `true` no cast (falha **aberta**) e `false` no
`filter_var` (falha **fechada**).

```gherkin
# language: pt

Funcionalidade: Coerção do interruptor do login social

  Regra: valor irreconhecível mantém o interruptor DESLIGADO

    Cenário: [CT-18] a coerção declarada no config é a que falha fechado
      Dado o arquivo config/kit.php
      Quando a chave do interruptor do login social é procurada
      Então a expressão usa FILTER_VALIDATE_BOOLEAN

    Esquema do Cenário: [CT-18b] valor irreconhecível não liga o interruptor
      Dado o valor "<valor>" vindo do ambiente
      Quando ele é coagido pela regra do config
      Então o resultado é falso
      E um cast de bool sobre o mesmo valor daria verdadeiro

      Exemplos:
        | valor | # partição              |
        | off   | negação que o Laravel não reconhece |
        | no    | negação que o Laravel não reconhece |
        | lixo  | valor arbitrário        |
```

Notas:

- **CT-18 é asserção de PRESENÇA**, sobre o texto cru — e por isso não precisa mais do filtro de
  comentário que `.ai/rules/testes.md` exige. O filtro era necessário para a asserção de
  **ausência** que foi removida; o comentário do bloco `login` cita o antipadrão para explicar a
  divergência, e teria reprovado o caso pela própria documentação.
- **CT-18b é o contrapeso comportamental**: sem ele, CT-18 afirma apenas sobre texto de arquivo, e
  uma implementação que escrevesse a linha certa e lesse outra chave passaria. A segunda asserção
  (`(bool) $valor` seria `true`) é o que torna o caso **discriminante** — ela prova que os dois
  jeitos divergem naquele valor, que é a única razão de a divergência com as três irmãs existir.
- **Nenhum `putenv()`**: teste que mexe em ambiente passa local e falha no CI, e o `phpunit.xml`
  do kit fixa env com `force="true"` justamente para não depender disso.
- Camada: `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M57 | `(bool) env('KIT_SOCIALITE_GOOGLE', false)` | CT-18 (presença) + CT-18b (a divergência medida) |
| M58 | `env('KIT_SOCIALITE_GOOGLE', false)` cru, e a comparação `== true` em outro lugar | CT-18 |
| M59 | ~~a coerção certa na chave nova e um `(bool) env(` acrescentado depois em outra chave~~ | **retirado**: não é mutante, é o padrão legítimo de três chaves do arquivo. Mantê-lo transformaria o gate em pressão para uma "correção" que pioraria o kit |

## Regra R15 — o login por Google deixa rastro

> `RQ-01` · área **Callback** · perfil **completo** ·
> técnica: **rastreio de efeito**, nas três direções: aconteceu / não aconteceu quando não devia /
> aconteceu pelo canal certo. Regra de efeito **consome o teto inteiro** — é o custo declarado da
> técnica.

```gherkin
# language: pt

Funcionalidade: Rastro do login social

  Regra: entrar pelo Google grava rastro no channel de autenticação e na trilha de acesso

    Cenário: [CT-19] o sucesso grava um registro no channel de autenticação
      Dado que o login com Google está disponível
      E que existe uma conta com o e-mail "ja.tem@example.com"
      E que o Google devolve o e-mail "ja.tem@example.com" verificado
      Quando o visitante volta do Google para o callback
      Então o channel de autenticação recebe uma mensagem de nível informativo
      E a mensagem traz o prefixo da classe e do método que a emitiu
      E a mensagem traz o identificador da conta autenticada

    Cenário: [CT-20] a recusa grava um alerta com o motivo, e não um registro de sucesso
      Dado que o login com Google está disponível
      E que o registro aberto está desligado
      E que não existe conta com o e-mail "de.fora@example.com"
      E que o Google devolve o e-mail "de.fora@example.com" verificado
      Quando o visitante volta do Google para o callback
      Então o channel de autenticação recebe uma mensagem de nível de alerta
      E o contexto da mensagem traz o motivo da recusa
      E o channel não recebe nenhuma mensagem de nível informativo de autenticação

    Cenário: [CT-21] o login pelo Google entra na trilha de acesso da instalação
      Dado que o login com Google está disponível
      E que existe uma conta com o e-mail "ja.tem@example.com"
      E que o Google devolve o e-mail "ja.tem@example.com" verificado
      Quando o visitante volta do Google para o callback
      Então existe um registro na trilha de acesso apontando para essa conta
```

Notas:

- **CT-20 é a direção "não aconteceu quando não devia"**: a terceira asserção é o que separa
  "logou o alerta" de "logou o alerta **e também** o sucesso", que é o mutante de um `return`
  esquecido.
- **CT-21 é a direção "pelo canal certo"**, e ela prova algo que nenhuma linha de código desta
  feature escreve: `Auth::login()` dispara `Illuminate\Auth\Events\Login`, que o
  `rappasoft/laravel-authentication-log` escuta
  (`vendor/rappasoft/laravel-authentication-log/src/LaravelAuthenticationLogServiceProvider.php:35`)
  e grava em `authentication_log`
  (`vendor/rappasoft/laravel-authentication-log/src/Models/AuthenticationLog.php:33`). Uma
  implementação que abra a sessão por outro caminho — escrevendo na sessão à mão, ou por um guard
  próprio — **passa em todos os outros cenários** e desaparece da trilha de acesso do `/infra`.
  Este é o único cenário que a mata.
- CT-19 e CT-21 têm o mesmo `Dado`/`Quando`, e não são redundantes: matam mutantes disjuntos, em
  canais diferentes (arquivo de log × tabela).
- Camada: `Feature`, com `espiarAutenticacao()` e `assertDatabaseHas`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M60 | nenhum log de sucesso (só o de erro foi escrito) | CT-19 |
| M61 | o log vai para o channel default em vez do de autenticação | CT-19 (o espião é do channel nomeado) |
| M62 | a recusa é logada em nível informativo, como se fosse normal | CT-20 |
| M63 | falta o `return` na recusa: ela loga o alerta e segue para o log de sucesso | CT-20 (terceira asserção) |
| M64 | a sessão é aberta sem `Auth::login()` (à mão, ou por guard próprio) | CT-21 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma rota desta feature recebe id de recurso. O callback recebe `code` e `state`, e o `state` é a barreira — CT-05 |
| Autorização exercida na ação (não só `can()`) | CT-02 (o `abort_unless` é exercido pela requisição, não afirmado por predicado) |
| Idempotência (ancorada no agregado) | CT-06 e CT-07, cuja âncora é **o total de contas**, não o retorno da chamada. Duas voltas do Google com o mesmo e-mail não podem produzir duas contas nem duas sessões distintas |
| Concorrência | **lacuna declarada**: dois callbacks simultâneos com o registro aberto. Tentado: o ramo de criação só roda com o registro aberto, que não é desta entrega, e `users.email` já é único no schema — a segunda gravação falha no banco. Cobrir exigiria um cenário sobre o tratamento dessa colisão, que é comportamento que o requisito não determina. Registrado como débito |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: nenhuma faixa ordenável, nenhum limite numérico no requisito. A única "gravação" é a criação de conta, coberta em CT-09 |
| Domínio condicionado (um campo depende de outro) | CT-01 — a visibilidade do botão depende do **par** (interruptor, credenciais), e a tabela de decisão cruza os dois em vez de tratar cada um isolado |
| Estado × operação de escrita | CT-02 e CT-03 (as duas rotas × os dois estados de disponibilidade) e CT-16 (conta com 2FA × entrar) |
| Ausente ≠ `null` ≠ vazio | CT-01 (interruptor **ausente** vs. credencial **vazia** — duas linhas diferentes), CT-11 (`email_verified` ausente vs. `false` vs. `"false"`), CT-14 (rodapé vazio vs. só espaços) |
| Paginação / ordenação | **não se aplica**: nenhuma listagem |
| Timezone / DST | **não se aplica**: nenhuma comparação de data. O `state` vive na sessão e não tem prazo próprio |
| Unicode / limite de varchar | **lacuna declarada**: nome do Google com emoji ou acima do `varchar` de `users.name`. Tentado: o campo é `string` (255) e o Google limita o nome bem abaixo disso; o cenário provaria a constraint do banco, não a regra. Coberto de lado por CT-09, que afirma o nome gravado |
| Unicidade + soft delete | **não se aplica**: `App\Models\User` não usa `SoftDeletes` (verificado nas traits, `app/Models/User.php:31-41`) |
| CRUD combinado | **não se aplica**: a feature não tem CRUD |
| Mass assignment | CT-09 — a conta nova é criada com `name`, `email` e senha aleatória; o `Então` afirma **o nome concreto**, o que denuncia gravar qualquer outra coisa vinda do provedor. O `$fillable` de `User` (`:43-48`) não tem campo de autorização, então não há `is_admin` a injetar |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| **XSS / saída não escapada** (linha nova desta feature) | CT-15 |
| **Segredo em log ou em tela** (linha nova desta feature) | CT-12, CT-13 |
| **Bypass de segundo fator** (linha nova desta feature) | CT-16 |

> As três últimas linhas são candidatas a entrar na taxonomia do projeto — ver
> `03-progresso.md` → candidatos a rule.

## Índice de Cenários

Todos em `tests/Kit/LoginSocialGoogleTest.php`, grupo `kit`.

| ID | Cenário | Regra | Técnica | Camada | Mata |
|----|---------|-------|---------|--------|------|
| CT-01 | exibição do botão pela tabela de decisão (4 linhas) | R1 | tabela de decisão | Feature | M1–M6 |
| CT-02 | as duas rotas caem quando indisponível (4 linhas) | R2 | estado × operação | Feature | M7–M10 |
| CT-03 | as duas rotas funcionam quando disponível (2 linhas) | R2 | estado × operação | Feature | M11 |
| CT-04 | botão depois do form, com o ícone, nos três painéis | R3 | EP + ordem no DOM | Feature | M12–M15 |
| CT-05 | callback sem state não autentica nem cria | R4 | EP inválida isolada | Feature | M16–M20 |
| CT-06 | quem já tem conta entra pelo Google | R5 | EP | Feature | M25, M26 |
| CT-07 | casamento por e-mail ignora caixa e espaços (5 linhas) | R5 | normalização | Feature | M22–M25 |
| CT-08 | sem conta e registro fechado ⇒ recusa | R6 | tabela de decisão | Feature | M27–M31 |
| CT-09 | registro aberto ⇒ cria e vai para o perfil | R7 | tabela de decisão | Feature | M33–M35 |
| CT-10 | quem já tinha conta vai para o painel | R7 | tabela de decisão | Feature | M32 |
| CT-11 | veredito por valor de `email_verified` (6 linhas) | R8 | EP exaustiva | Feature | M37–M41 |
| CT-12 | segredo ausente do HTML das três telas | R9 | não-efeito | Feature | M45 |
| CT-13 | segredo e e-mail em claro ausentes do log | R9 | não-efeito | Feature | M42–M44 |
| CT-14 | exibição do rodapé por estado do texto (3 linhas) | R10 | EP | Feature | M46–M48 |
| CT-15 | HTML no rodapé sai escapado | R11 | EP discriminante | Feature | M49, M50 |
| CT-16 | 2FA confirmado ⇒ desafio mesmo entrando pelo Google | R12 | estado × operação | Feature | M51–M53 |
| CT-17 | chaves no `.env.example` e nos dois READMEs (8 linhas) | R13 | EP | Feature (leitura de arquivo) | M54–M56 |
| CT-18 | a coerção declarada no config é a que falha fechado | R14 | EP | Feature (leitura de arquivo) | M57, M58 |
| CT-18b | valor irreconhecível não liga o interruptor (3 linhas) | R14 | EP discriminante | Unit-em-Kit | M57 |
| CT-19 | log informativo no sucesso | R15 | rastreio de efeito | Feature | M60, M61 |
| CT-20 | alerta com motivo na recusa, sem log de sucesso | R15 | rastreio de efeito | Feature | M62, M63 |
| CT-21 | login pelo Google entra na trilha de acesso | R15 | rastreio de efeito | Feature | M64 |
| CT-22 | provedor sem e-mail ⇒ recusa sem autenticar nem criar | R8 | EP inválida isolada | Feature | M39 (variante) |
| CT-23 | o mesmo e-mail voltando duas vezes não cria segunda sessão nem segunda conta | R5 | idempotência (âncora no total de contas) | Feature | M25 |

> **CT-22** e **CT-23** são as duas células que a poda do passo 7 **manteve**: CT-22 porque
> "provedor não devolveu e-mail" é partição inválida distinta de "e-mail não verificado" e uma
> implementação que só confira a verificação estoura com `null` no `where`; CT-23 porque
> idempotência ancorada no agregado é item obrigatório da taxonomia e nenhum outro cenário aplica
> a ação **duas vezes**.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| `throttle` de 10/min devolve 429 na 11ª chamada | o limite é escolha do PRD, não cláusula do requisito. Cenário do PRD, não do requisito |
| a conta nova recebe senha aleatória e não consegue entrar por senha | mata o mesmo mutante que CT-09, e afirmaria sobre o **hash**, que é detalhe de implementação |
| `Socialite::driver('google')->usesState()` é verdadeiro | mede o default do vendor, não o código do kit; o mutante real (`->stateless()` no controller) é morto por CT-05 |
| o botão não aparece na tela de recuperação de senha | o requisito não fala de outras telas; afirmaria ausência de feature não pedida |
| nenhuma rota `/auth/github/*` existe | ausência de feature não pedida (RQ-08) |
| o avatar do Google é gravado no `avatar_url` | comportamento **não** implementado por decisão (ADR-07); o cenário testaria a decisão de não fazer, o que é ruído |
