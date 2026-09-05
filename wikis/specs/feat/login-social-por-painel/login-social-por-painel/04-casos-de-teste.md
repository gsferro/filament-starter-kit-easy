# Casos de Teste — Login social por painel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · ADRs: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação da
> feature (ela não existe). Os arquivos de teste vizinhos foram lidos apenas para herdar
> convenção — helpers, arranjo, forma do oráculo —, nunca para inferir comportamento esperado.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A régua de disponibilidade por provedor e painel (`ConfiguracaoDoLogin`) | 3 | 3 | 9 | **completo** |
| A barreira da rota de ida contra a query forjada | 3 | 3 | 9 | **completo** |
| O botão nas telas de autenticação dos três painéis | 3 | 3 | 9 | **completo** |
| O destino do fluxo (seis pontos de redirecionamento + sessão) | 3 | 2 | 6 | padrão |
| A tela de settings (campo novo, gravação, ligação com a decisão) | 2 | 2 | 4 | padrão |
| A chave de config, a semeadura e o default de fábrica (`vazio = todos`) | 2 | 3 | 6 | padrão |

Justificativa das notas altas: as três primeiras áreas **são** a decisão de autorização
(`I=3`), integram três componentes já existentes e a entrada chega por query string, que é
entrada do usuário (`P=3`). O destino não concede acesso — `canAccessPanel()` continua
decidindo —, então o impacto dele é retrabalho e UX, não autorização (`I=2`). A área de config
leva `I=3` por um motivo só: um default errado ali **desliga o login social de toda instalação
existente** num update.

- Técnicas aplicadas: partição de equivalência, tabela de decisão (3 condições conjuntivas),
  matriz provedor × painel, matriz provedor × superfície, matriz provedor × propriedade,
  tabela desfecho × painel de origem, matriz papel × painel, partição exaustiva de parâmetro de
  entrada, rastreio de ligação (`.env` → config → decisão e settings → config → decisão),
  criação × edição × uso.
- **BVA não se aplica**: nenhuma faixa ordenável nesta feature. Nenhum número, nenhuma data,
  nenhum dinheiro. O domínio é um conjunto de ids de painel, e a técnica dele é partição.
- Cenários: **31** · Regras: **8** · Mutantes previstos: **53** · Sem matador: **0**
- Lacunas declaradas: **2** · Perguntas em aberto: **6** (uma delas, A6, é uma **contradição
  interna do `01`** e bloqueia CT-10)
- Revisão adversarial: **2 entregas do mesmo revisor independente**, **11 lacunas de cobertura**
  + **3 matadores declarados que não matavam** + **10 oráculos fracos ou passos compostos** —
  todos fechados neste arquivo. Ver `## Revisão Adversarial`.

### Camada: por que nenhum cenário é `Unit`

`tests/Pest.php` liga `Tests\TestCase` a `Feature`, `Kit`, `Tenancy`, `Browser` e
`BrowserTenancy` — e **não** a `Unit`. `tests/Unit` existe (só com o `ExampleTest.php` de
partida) e tem testsuite no `phpunit.xml:8-10`, mas roda com o `TestCase` do PHPUnit puro: sem
container, sem `config()`, sem `Filament::getPanels()`. Como toda regra desta feature lê
`config()` — e `Paineis::opcoes()` lê os painéis registrados —, a camada mais barata **que o
arnês deste projeto sustenta** é `tests/Kit`. É o mesmo caminho que os vizinhos tomaram para
`ConfiguracaoDoLogin::disponiveis()` (CT-02 de `LoginSocialProvedoresTest`).

Registrado porque a escada teórica (`Unit < Feature`) daria a resposta errada aqui.

### Divergência entre esta skill e as rules do projeto

Nenhuma. `--parallel --tia` (que a skill sugere e o `01` já prevê) é o padrão deste repositório
— `pest()->tia()->defaultBranch('main')->locally()` está no `tests/Pest.php:143-145`,
condicionado a `.git` existir. Sem CT-B, a proibição de `--parallel` com browser não é alcançada
por este conjunto.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | `config/kit.php` (4 chaves `paineis`); 4 propriedades + 4 linhas de mapa + 1 migration de settings; `ConfiguracaoDoLogin::disponivel/disponiveis/painelAutorizado`; `LoginSocialController` (barreira, painel na sessão, **6** pontos de destino); o blade dos botões; a Page de settings; `.env.example`; docs | CT-01, CT-02, CT-18, CT-21…CT-25, CT-26…CT-29 |
| **F** | decidir disponibilidade por provedor **e** painel; filtrar os botões por painel; barrar a rota de ida; propagar o painel pela sessão; resolver o painel de destino nos **seis** pontos; gravar a escolha pela tela | CT-01…CT-25 |
| **D** | a lista de ids de painel nas partições **chave ausente / vazia / contém / não contém / só id inexistente**; o `$painel` nulo; a query `painel` **ausente / vazia / autorizada / não autorizada / inexistente / não-string**; 4 provedores × 3 painéis; o `login_social.contexto` já carregando `org` e `token` | CT-01, CT-02, CT-03, CT-05, CT-09, CT-10, CT-16, CT-26 |
| **I** | 3 telas de login **e** a tela de registro do `/app` (o render hook é registrado nos dois pontos); as 3 rotas globais fora do painel; `/admin/configuracoes-do-kit`; o `.env`; a tabela de settings | CT-05, CT-07, CT-09, CT-21…CT-25, CT-28 |
| **P** | os ids de painel saem de `Filament::getPanels()` via `Paineis::opcoes()` — painel novo entra sozinho, e id forjado nunca casa; sessão por cookie; `in_array` estrito num PHP 8.4 em que `0 == 'admin'` já é **falso** (muda o valor discriminante — ver CT-02) | CT-02, CT-10, CT-25 |
| **O** | o administrador configurando na tela; a pessoa clicando no botão; **o atacante forjando `?painel=`**; **a instalação existente que só roda `migrate` e nunca escolheu nada**; a instalação cujo `config/kit.php` é anterior ao update | CT-09, CT-12, CT-01 (chave ausente), CT-29 |
| **T** | a configuração alterada **entre** a ida e a volta (ADR-05, janela de segundos aceita); a sessão perdida entre a ida e a volta (ADR-06); a propriedade lida **por request** e não no boot (ADR-07) | CT-19, CT-20, CT-22 |

Nenhuma dimensão vazia. `T` não tem componente de fuso, DST ou expiração: nada nesta feature é
datado.

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — um provedor só vale num painel quando o interruptor está ligado, as três credenciais estão preenchidas **e** o painel consta da lista (ou a lista está vazia/ausente) | régua (completo) | RQ-01, RQ-03, RQ-06, A2 | tabela de decisão (3 condições) + partição da lista | CT-01, CT-02 |
| **R2** — a escolha é por provedor: a lista de um provedor não decide sobre nenhum outro | régua (completo) | RQ-02 | matriz provedor × painel | CT-03, CT-04 |
| **R3** — o botão do provedor aparece na tela de autenticação de um painel se e somente se o provedor vale naquele painel, e o link que ele monta carrega o painel corrente | tela (completo) | RQ-01, RQ-04, RQ-06 | matriz provedor × painel + matriz provedor × superfície | CT-05, CT-06, CT-07, CT-08 |
| **R4** — a rota de ida recusa quando o painel pedido não autoriza o provedor, e segue quando autoriza | barreira (completo) | RQ-05 | partição exaustiva do parâmetro de query + matriz provedor × painel | CT-09, CT-10, CT-11, CT-12, CT-13 |
| **R5** — quem entra por um painel autorizado termina naquele painel, em **todos** os desfechos da volta, e o painel de origem não concede acesso a ele | destino (padrão) | A1 (leitura ampla, respondida) | tabela desfecho × painel de origem + matriz papel × painel | CT-14, CT-15, CT-16, CT-17, CT-18 |
| **R6** — sem painel válido na sessão, o destino é o painel default do Filament, e o fluxo nunca lança | destino (padrão) | A1 + ADR-06 | partição do estado da sessão | CT-19, CT-20 |
| **R7** — a tela de settings grava a escolha de **cada** provedor, e o valor gravado governa a decisão | settings (padrão) | RQ-01, RQ-02, A3, A4 | gravação por componente + matriz provedor × propriedade + rastreio de ligação; criação × edição | CT-21, CT-22, CT-23, CT-24, CT-25 |
| **R8** — a chave de config nasce vazia, e vazia significa **todos** os painéis — inclusive no valor que a migration semeia | config (padrão) | A2 (respondida) | partição do valor do `.env` medida no próprio arquivo + rastreio semeadura → decisão | CT-26, CT-27, CT-28, CT-29 |

**Técnica escalada acima do perfil da área**: nenhuma. **Teto de cenários estourado em duas
regras** (R5 e R7), com justificativa escrita em cada uma — e nos dois casos o estouro vem de
achado da revisão adversarial, não de zelo.

**Teto de mutantes por regra**: os mutantes marcados `— revisão adversarial` **não contam para o
teto**, por regra explícita da skill (mutante trazido por revisão é achado medido, não
enchimento). Contando só os derivados na primeira passagem, nenhuma regra passa do teto do seu
perfil: R1 6/6, R2 4/6, R3 6/6, R4 6/6, R5 4/5, R6 3/5, R7 5/5, R8 5/5. Desdobrar as regras para
acomodar os 11 mutantes de revisão significaria renumerar a rastreabilidade inteira por um motivo
cosmético.

**Cobertura das cláusulas** — toda `RQ` gerou regra:

| RQ | Regra(s) | Onde é falsificável |
|---|---|---|
| RQ-01 | R1, R3, R7 | CT-01; CT-05; **CT-22, nos quatro provedores** |
| RQ-02 | R2, R7 | CT-03; CT-21, CT-22 |
| RQ-03 | R1 | CT-01 linha 3 |
| RQ-04 | R3 | CT-05, CT-07 (login **e** registro) |
| RQ-05 | R4 | CT-09, CT-12 |
| RQ-06 | R1, R3 | CT-01 linhas do interruptor, **das três credenciais** e da "chave ausente"; CT-07 célula válida; CT-08b |
| A1 (permissão **e** destino) | R5, R6 | CT-14 (sucesso), **CT-18 (os outros desfechos)**, CT-19 |
| A2 (default: todos) | R1, R7, R8 | CT-01, CT-23, CT-26, CT-28, **CT-29 (o valor semeado)** |
| A4 (os três painéis) | R3, R5, R7, R8 | CT-06, CT-08, CT-14, **CT-25 (as opções do campo × os painéis registrados)** |
| A5 (sem papel do painel) | R5 | CT-15 |

## Fronteira com o Plano

O que veio do `01-plano-acao.md` e foi **recusado como oráculo**, para o cenário não virar teste
do PRD:

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| os nomes `painelAutorizado()`, `painelDaRequisicao()`, `painel(?string $id)` | escolha de implementação | detalhe do cenário; nenhum `Então` os cita |
| `Paineis::opcoes()` como lista branca e como `options()` do campo | escolha de implementação (ADR-02) | detalhe do arranjo — mas ver CT-25, cujo oráculo é a **lista de painéis registrados**, que o requisito determina via A4 |
| `Select::make(...)->multiple()` como forma do campo | escolha de implementação | detalhe do arranjo |
| `->visible()` do campo casado com o toggle do provedor | UX que só o PRD determina | **recusado**; nenhum cenário o afirma |
| `helperText('Vazio = todos os painéis.')` — o texto na tela | comportamento **visível ao usuário** que o requisito não determina | **pergunta A10** |
| o `warning` de recusa e o painel no contexto do log de sucesso | efeito colateral que só o PRD pede | CT-13, marcado `@do-plano`, + **pergunta A9** |
| painel **inexistente** na query → 404 (ADR-03) × segue no default (código do passo 5b) | o `01` **se contradiz**, e o `00` não determina | CT-10, marcado `@premissa`, + **pergunta A6** |
| a volta **não** reconferir a autorização por painel (ADR-05) | decisão do PRD; o `00` não determina | CT-20, marcado `@premissa`, + **pergunta A8** |

### Dois itens do PRD que **foram** adotados, e por quê

- **`?painel=` como nome do parâmetro de query.** Sem um nome, nenhum cenário de RQ-05 é
  expressável — a cláusula fala do fluxo, e o fluxo precisa de um canal. Adotado como **detalhe
  do cenário**: se o nome mudar, muda este `04`, não o requisito. O que é oráculo é *"forjar o
  painel na requisição não abre o provedor"*, não a grafia da chave.
- **404 como status da recusa.** Não sai do PRD: sai de **RQ-06** ("as premissas atuais se
  mantêm"). A recusa da rota de OAuth **já é** 404 hoje, e há caso de teste ancestral que a
  fixa (`LoginSocialProvedoresTest` CT-03). Trocar por 403 ou 302 seria mudança de
  comportamento que o requisito não pede.

## Perguntas para o `00-requisito.md`

<!-- Bloco pronto para colagem em `## Ambiguidades e Perguntas Abertas` do `00-requisito.md`.
     DESVIO DECLARADO: o `00` não foi editado por esta skill — a instrução da tarefa restringe a
     escrita a este arquivo. As perguntas continuam BLOQUEANDO o que dependem delas. -->

- **A6 — painel inexistente na query: 404 ou segue no painel default?** O `01` responde as duas
  coisas: a **ADR-03** diz *"o valor é um painel que existe (lista branca) … as duas falhas
  respondem 404"*, e o código do **passo 5b** faz `painelDaRequisicao()` devolver `null` para
  painel inexistente, com o `abort` do 5a condicionado a `$painel !== null` — ou seja,
  `?painel=marketing` **segue** e cai no painel default. O `00` não determina.
  - **Assumido**: o código do passo 5b (segue no default), porque é a leitura que preserva
    qualquer link antigo sem `painel` e não transforma um erro de digitação em 404.
  - **Se negado** (404): CT-10 inverte, e vira o par de CT-09.
  - **Bloqueia**: R4 / CT-10.

- **A7 — lista gravada só com painel inexistente: o provedor vale em nenhum painel, ou o valor
  inválido é ignorado?** Com `paineis = ['marketing']` nenhum painel real casa, e o provedor
  desaparece dos três — em silêncio, sem erro. Alcançável por `.env` com um painel renomeado ou
  removido.
  - **Assumido**: falha **fechada** — o provedor não vale em nenhum painel. É coerente com
    "vazio = todos, e quem não quer o provedor em painel nenhum desliga o interruptor".
  - **Se negado** (ignorar o inválido = todos): CT-02 muda de linha, e o kit precisa dizer isso
    na tela.
  - **Bloqueia**: R1 / CT-02 (linha `@premissa`).

- **A8 — a volta reconfere a autorização por painel?** A ADR-05 decide que não. O `00` não diz.
  - **Assumido**: não reconfere (ADR-05) — a autorização é decidida na ida.
  - **Se negado**: a configuração alterada entre o clique e a volta produz 404 depois de a
    pessoa já ter autenticado no provedor, e CT-20 inverte.
  - **Bloqueia**: R6 / CT-20 (`@premissa`).

- **A9 — o registro da recusa é requisito?** O `01` prevê um `warning` no canal `autenticacao`
  com `motivo: painel_nao_autorizado`; o `00` não pede rastro nenhum.
  - **Assumido**: é entrega do plano, não cláusula. CT-13 existe e está marcado `@do-plano`.
  - **Se negado** (é requisito): CT-13 deixa de ser opcional e ganha o par "não loga sucesso na
    recusa", como os CT-37/CT-38 das wikis ancestrais.

- **A10 — o rótulo e o texto de ajuda do campo são requisito?** O `01` fixa `label('Painéis')` e
  `helperText('Vazio = todos os painéis.')`. Como o default "vazio = todos" **não é
  adivinhável**, o texto é parte do contrato com quem configura — e nenhum cenário o afirma.
  - **Assumido**: não é cláusula. Nenhum `Então` sobre texto de tela.
  - **Se negado**: entra um cenário de componente afirmando o texto de ajuda.

- **A11 — RQ-04 alcança a tela de registro (`/app/register`)?** O requisito diz "tela de login".
  O render hook é registrado em `AUTH_LOGIN_FORM_AFTER` **e** em `AUTH_REGISTER_FORM_AFTER`
  (`00-requisito.md`, levantamento), e a `## Superfície de UI` do `01` lista as duas.
  - **Assumido**: sim — "em quais painéis poderão usar" é sobre o provedor no painel, e o
    registro é uma tela do painel `app`. CT-07 está marcado `@premissa`.
  - **Se negado**: CT-07 sai por inteiro — **as duas metades**, inclusive a célula válida. Note
    que remover só a metade negativa deixaria a feature abrindo pelo cadastro o que ela fecha
    pelo login.

## Setup Global

### Personas

- `usuarioDoKit('admin')` — quem opera `/admin/configuracoes-do-kit` (R7)
- `usuarioDoKit('admin' | 'panel_user' | 'infra', 'ja.tem@example.com')` — **um papel por painel
  de destino**. Sem o papel do painel, `canAccessPanel()` recusa e o cenário mede o barramento
  em vez do destino; é a mesma razão registrada em `LoginSocialProvedoresTest` CT-48
- `usuarioDoKit('panel_user', 'ja.tem@example.com')` entrando pelo `/admin` — a persona
  **discriminante** de CT-15: tem conta, não tem papel do painel de origem
- `usuarioDoKit('master_global')` — **não** serve para nada aqui: vence toda permissão pelo
  `Gate::before`. Linha de controle, nunca de prova

### Fixtures e arranjo

- `ligarProvedor(ProvedorSocial::X, ['client_secret' => ''])` (`tests/Pest.php:520-529`) — liga o
  interruptor e as três credenciais; o segundo argumento é o que permite esvaziar **uma** delas
- `config()->set("kit.login.{$provedor->value}.paineis", [...])` — a lista por provedor no
  arranjo. É a chave que o `01` cria; nenhum `Então` a cita
- `usuarioSocialFalso($provedor, $bruto, $mapeados)` (`tests/Pest.php:564-582`) — o usuário do
  provedor com o campo de verificação **só no bruto**, que é o lado em que o driver real o
  entrega. Usar `Two\User::fake()` cru esconderia a barreira de e-mail verificado
- `gravarConfiguracao()` / `configuracaoGravada()` / `alinharConfiguracoesDoKit()`
  (`tests/Pest.php`) — settings sem passar por `save()`, leitura do `payload` cru e o
  alinhamento que o boot faria
- `kitConfigCom('KIT_SOCIALITE_GOOGLE_PAINEIS', ' admin, app ,')` (`tests/Pest.php:478-503`) —
  relê o `config/kit.php` com a variável forçada e restaura o ambiente no `finally`. É a única
  forma de medir o **default do arquivo** em vez do valor que o teste escreveu
- `noPainelBootado('admin')` — só onde a tela depende de algo registrado no `boot()` de plugin.
  Nenhum cenário deste conjunto precisa
- Seeders: `ShieldPermissionsSeeder` + `PapeisSeeder` no `beforeEach` — os papéis por painel só
  existem depois deles, e `assignRole()` estoura `RoleDoesNotExist` sem eles

### Fakes

- `Socialite::fake($provedor->value, usuarioSocialFalso(...))` — a API oficial do pacote.
  Obrigatória em todo cenário que chame a rota de volta
- `Http::preventStrayRequests()` no `beforeEach` — o kit não chama API de provedor nenhuma.
  **Não** protege o Socialite (que usa o Guzzle dele, não a facade `Http`); quem impede o
  Socialite de sair para a rede é o `Socialite::fake()`
- `espiarAutenticacao()` (`tests/Pest.php:605-612`) — espia só o canal `autenticacao`; os outros
  continuam reais. Usado só em CT-13
- Nunca `Http::fake()` sozinho: sem stub ele devolve 200 vazio e o cenário passa sem provar nada

### Estratégia de DB

`RefreshDatabase` global, aplicado a `tests/Kit` e `tests/Tenancy` pelo `tests/Pest.php`.

### Armadilhas de arnês que invalidariam estes cenários

Verificadas neste repositório, não presumidas:

| Armadilha | Consequência | O que fazer |
|---|---|---|
| **`throttle:10,1` no grupo das rotas de OAuth** (`routes/web.php:61-70`) — e o limite é do grupo, somando os quatro provedores na mesma URI de rota | um caso que faça **mais de 10 requisições à rota de ida** dentro do MESMO teste recebe 429 e falha por um motivo que não é o dele | manter cada partição numa linha de `Exemplos:` — cada linha é um teste próprio, com aplicação nova. Nenhum cenário deste conjunto passa de 5 requisições por caso |
| `CACHE_STORE=array` (`phpunit.xml:121`) | o contador do throttle vive na instância da aplicação, então **reinicia a cada caso** — é o que torna a coluna acima administrável | nada a fazer; registrado para que ninguém "conserte" com um `RateLimiter::clear()` desnecessário |
| `SESSION_DRIVER=array` (`phpunit.xml:138`) | a sessão sobrevive entre os `$this->get()` do MESMO caso e morre entre casos — é exatamente o que CT-14 e CT-20 precisam (ida e volta no mesmo caso) e o que CT-19 linha 1 precisa (volta sem ida) | nada; mas **não** escrever cenário que dependa de sessão atravessando dois casos |
| `Http::fake()` sem stub | devolve 200 vazio e o cenário passa sem provar nada | sempre com `Http::preventStrayRequests()`, que já está no `beforeEach` |
| `Event::fake()` antes das factories | eventos de model não rodam e a fixture nasce quebrada | nenhum cenário deste conjunto usa `Event::fake()` |
| `assertFormSet`, `callTableAction`, `assertTableActionExists` | `@deprecated` nos `.stubs.php` do Filament; passam hoje e quebram no upgrade, e `tests/Kit/AderenciaAoBlueprintTest.php` reprova | usar `assertSchemaStateSet`, `callAction`, `assertActionExists` |

### Suítes e arquivos

| Regras | Arquivo | Suíte |
|---|---|---|
| R1, R2, R3, R4, R5 (CT-14…CT-16, CT-18), R6, R8 | `tests/Kit/LoginSocialPorPainelTest.php` (novo) | `Kit` |
| R7 | `tests/Kit/ConfiguracoesDoKitTelaTest.php` (acréscimo) | `Kit` |
| R5 / CT-17 | `tests/Tenancy/LoginSocialPorPainelTenancyTest.php` (novo) | `Tenancy` |

`tests/Tenancy` e não `tests/Kit` para CT-17 por razão de bootstrap, não de organização:
`Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, **antes** das
migrations, e ligar a flag num `beforeEach` é tarde demais.

---

## Regra R1 — a régua conjuntiva por provedor e painel

> `RQ-01`, `RQ-03`, `RQ-06`, `A2` · perfil **completo** · técnicas: **tabela de decisão** (três
> condições conjuntivas, nenhuma linha colapsada) + **partição da lista de painéis**
> (chave ausente / vazia / contém / não contém / só id inexistente) + **partição do parâmetro de
> painel** (informado / nulo)

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: um provedor vale num painel quando o interruptor está ligado, as três credenciais
         estão preenchidas e o painel consta da lista — e a lista vazia significa todos

    Esquema do Cenário: [CT-01] a decisão conjuga o interruptor, as credenciais e a lista de painéis
      Dado o provedor Google com o interruptor <interruptor> e o client_secret <secret>
      E a lista de painéis do Google igual a <lista>
      Quando o kit é perguntado se o Google vale no painel <painel>
      Então a resposta é <resposta>

      Exemplos:
        | interruptor | secret     | lista           | painel  | resposta | # partição                                    |
        | desligado   | preenchido | ["admin"]       | "admin" | false    | condição 1 isolada (RQ-06)                    |
        | ligado      | vazio      | ["admin"]       | "admin" | false    | condição 2, pelo client_secret (RQ-06)        |
        | ligado      | (client_id vazio) | ["admin"] | "admin" | false    | condição 2, pelo client_id (RQ-06)            |
        | ligado      | (redirect vazio)  | ["admin"] | "admin" | false    | condição 2, pelo redirect (RQ-06)             |
        | ligado      | preenchido | ["app"]         | "admin" | false    | condição 3 — a desta wiki (RQ-03)             |
        | ligado      | preenchido | ["admin"]       | "admin" | true     | célula VÁLIDA do painel autorizado            |
        | ligado      | preenchido | ["admin","app"] | "app"   | true     | célula VÁLIDA, lista com dois itens           |
        | ligado      | preenchido | []              | "admin" | true     | lista vazia = todos (ADR-04)                  |
        | ligado      | preenchido | []              | "infra" | true     | lista vazia = todos, outro painel             |
        | ligado      | preenchido | chave AUSENTE   | "admin" | true     | a config anterior ao update não conhece a chave |
        | ligado      | preenchido | chave AUSENTE   | "app"   | true     | idem, no painel default                       |
        | ligado      | preenchido | ["admin"]       | nulo    | true     | painel não informado dispensa a condição 3    |
        | ligado      | preenchido | ["infra"]       | nulo    | true     | idem, com lista que NÃO contém o painel default |

    Esquema do Cenário: [CT-02] a lista casa o id do painel por identidade, não por conveniência
      Dado o provedor Google ligado e com as três credenciais preenchidas
      E a lista de painéis do Google igual a <lista>
      Quando o kit é perguntado se o Google vale no painel "admin"
      Então a resposta é <resposta>

      Exemplos:
        | lista         | resposta | # o que a linha discrimina                                        |
        | ["admin"]     | true     | a célula válida — mata a comparação por CHAVE em vez de por valor |
        | [true]        | false    | comparação frouxa: `in_array("admin", [true])` é VERDADEIRO        |
        | ["marketing"] | false    | `@premissa` A7: id inexistente na lista falha FECHADA              |
        | ["Admin"]     | false    | a caixa importa: o id de painel vem de `getPanels()`, não é texto livre |
```

**Discriminância dos valores, item por item** — nenhum é redondo:

- A linha `[true]` é a **única** que separa `in_array` estrito de frouxo neste PHP, e isso foi
  **medido**, não presumido — no PHP 8.4.10 desta instalação:

  | Expressão | Resultado |
  |---|---|
  | `0 == 'admin'` | `false` — mudou na 8.0 |
  | `in_array('admin', [0])` | `false` — a linha que a intuição escolheria **não discrimina nada** |
  | `in_array('admin', [true])` | `true` |
  | `in_array('admin', [true], true)` | `false` |
  | `in_array('admin', ['Admin'])` | `false` |
  | `in_array('app', ['admin', ' app'], true)` | `false` — o mutante do `trim`, em R8 |

  Por isso `[0]` ficou de fora (lacuna L1) e `[true]` entrou. O estado é alcançável: o `payload`
  do settings é JSON, e nada além do `Select` garante que ele guarde só strings.

- **As duas últimas linhas de CT-01 usam listas que NÃO contêm o painel default**, e isso é
  correção de um erro desta própria derivação. A versão anterior usava `["app"]` com `painel`
  nulo — e o painel default **é** o `app`, então o mutante "`$painel === null` resolve o painel
  default" devolvia `true` e **sobrevivia** com a linha marcada de verde. É o mesmo erro que a
  lacuna L1 documenta do outro lado: valor escolhido sem conferir se discrimina. Achado da
  revisão adversarial (RA-09).
- As duas linhas de **chave ausente** cobrem a instalação real de um *starter kit*, onde
  `config/` pertence a quem instalou: quem atualiza o pacote e mantém o próprio `config/kit.php`
  não tem a chave nova. `config()` devolve `null` ali, e uma leitura sem `(array)`/default
  estoura `TypeError` — ou, pior, trata como "nenhum painel" e desliga o login social de todo
  mundo. É a partição que faz a promessa de A2 ("a feature nasce inerte") ser falsificável.
- **As três credenciais têm linha própria**, e não só o `client_secret` (achado RA-22). O `01`
  **reescreve** a condição das credenciais de `filled()` para `blank()` para poder acrescentar a
  terceira condição; uma reescrita que preserve só o `client_secret` e perca o `client_id` ou o
  `redirect` é o mutante mais plausível de toda a R1, e uma tabela de decisão que declara "nenhuma
  linha colapsada" não pode colapsar a condição 2 em uma das três chaves. O arranjo já permite
  esvaziar qualquer uma delas (`ligarProvedor($p, ['client_id' => ''])`).
- A linha `["admin"]` mata o mutante que confunde as duas listas da feature —
  `array_key_exists($painel, $paineis)` funciona na lista branca de `Paineis::opcoes()` (que é
  um mapa `id => path`) e **falha** na lista de painéis escolhidos (que é `[0 => 'admin']`).
- A ordem das três primeiras linhas de CT-01 é deliberada: cada condição inválida **isolada em
  sua própria linha**, porque a primeira validação a disparar mascara as demais.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a terceira condição **substitui** uma das duas antigas (`return painelAutorizado(...)` sozinho) | CT-01 linhas 1 e 2 |
| M2 | `\|\|` no lugar de `&&`: painel autorizado basta, mesmo com o interruptor desligado | CT-01 linha 1 |
| M3 | lista vazia = **nenhum** painel (o `$paineis === []` esquecido) | CT-01 linhas `[]` |
| M4 | `$painel === null` passa a resolver o painel **default** em vez de dispensar a condição | CT-01 linhas `["admin"]`/nulo e `["infra"]`/nulo |
| M5 | `in_array` **frouxo** | CT-02 linha `[true]` |
| M6 | `array_key_exists($painel, $paineis)` no lugar de `in_array` | CT-01 e CT-02 linhas válidas |
| M7 | a chave é lida sem `(array)` nem default: ausente estoura `TypeError`, ou é tratada como "nenhum painel" — *revisão adversarial* | CT-01 linhas "chave AUSENTE" |
| M52 | a reescrita de `filled()` para `blank()` preserva só o `client_secret` e perde o `client_id` ou o `redirect` — *revisão adversarial* | CT-01 linhas "client_id vazio" e "redirect vazio" |

---

## Regra R2 — a escolha é por provedor

> `RQ-02` · perfil **completo** · técnica: **matriz provedor × painel**, com a cruz negativa

O requisito é explícito: *"o Google pode valer no `/admin` e não valer no `/app`"*. Um conjunto
que fixasse um provedor deixaria viva a implementação que lê a lista de **um** provedor e a
aplica a todos — e essa implementação passa em toda a R1.

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: a lista de painéis de um provedor não decide sobre nenhum outro provedor

    Esquema do Cenário: [CT-03] cada painel oferece exatamente os provedores que valem nele
      Dado o Google ligado e completo, valendo só no painel "admin"
      E o GitHub ligado e completo, valendo só no painel "app"
      E o LinkedIn ligado e completo, com a lista de painéis vazia
      E o X ligado e completo, valendo só no painel "infra"
      Quando o kit lista os provedores disponíveis no painel <painel>
      Então a lista é exatamente <lista>, na ordem do enum

      Exemplos:
        | painel  | lista              | # o que a linha prova                        |
        | "admin" | [Google, LinkedIn] | o Google entra, o GitHub e o X ficam fora    |
        | "app"   | [GitHub, LinkedIn] | o GitHub entra e o Google — o mesmo provedor da linha acima — fica fora |
        | "infra" | [LinkedIn, X]      | o X entra; o provedor de lista vazia entra nos três |

    Cenário: [CT-04] mudar a lista de um provedor não mexe no botão de outro
      Dado o Google e o GitHub ligados e completos, os dois valendo no painel "admin"
      E que a tela de login do "/admin" já foi renderizada com os dois botões
      Quando o Google passa a valer só no painel "app"
      Então a tela de login do "/admin" traz o botão "Entrar com GitHub"
      E a tela de login do "/admin" não traz o botão "Entrar com Google"
```

**Por que CT-03 compara a lista inteira e não usa "contém"**: `toContain` ficaria verde com
`ProvedorSocial::cases()` devolvido cru. E a **ordem** é oráculo porque distingue um
`array_filter` que preserva as chaves originais — que quebra o laço do blade — de um que chama
`array_values()`; é a mesma razão registrada em `LoginSocialProvedoresTest` CT-02.

**Por que CT-04 renderiza a tela ANTES de mudar a lista**: é o que dá ao mutante que memoiza a
lista entre chamadas a chance de memoizar. Sem o primeiro render, um cache nasceria já com o
valor novo e o cenário ficaria verde. Herdado de `LoginSocialProvedoresTest` CT-06.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | a lista é lida de um provedor **fixo** (`kit.login.google.paineis`) e aplicada a todos | CT-03 (as três linhas divergem) |
| M9 | `disponiveis($painel)` recebe o painel e **não o repassa** a `disponivel()` | CT-03 (as três linhas dariam o mesmo conjunto) |
| M10 | `disponiveis()` devolve o array sem `array_values()` | CT-03 (comparação com a lista inteira) |
| M11 | a lista ou a decisão é memoizada estaticamente entre chamadas | CT-04 |

---

## Regra R3 — o botão só na tela do painel autorizado, e o link carrega o painel

> `RQ-01`, `RQ-04`, `RQ-06` · perfil **completo** · técnicas: **matriz provedor × painel** e
> **matriz provedor × superfície** (login e registro), as duas com as células válidas e as
> inválidas, e a cruz negativa por provedor

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: o botão do provedor aparece na tela de autenticação de um painel se e somente se o
         provedor vale naquele painel

    Esquema do Cenário: [CT-05] o botão aparece no painel autorizado e desaparece nos outros
      Dado o Google ligado e completo, valendo só no painel "admin"
      E o GitHub ligado e completo, valendo nos painéis "app" e "infra"
      Quando alguém abre a tela de login do painel <painel>
      Então a tela <google> o botão "Entrar com Google"
      E a tela <github> o botão "Entrar com GitHub"

      Exemplos:
        | painel  | google   | github   | # célula                                        |
        | "admin" | traz     | não traz | válida do Google, inválida do GitHub            |
        | "app"   | não traz | traz     | inválida do Google, válida do GitHub            |
        | "infra" | não traz | traz     | exaustividade de A4 — apoio, não discriminante  |

    Esquema do Cenário: [CT-06] provedor com a lista vazia continua oferecido nos três painéis
      Dado o LinkedIn ligado e completo, com a lista de painéis vazia
      Quando alguém abre a tela de login do painel <painel>
      Então a tela traz o botão "Entrar com LinkedIn"

      Exemplos:
        | painel  |
        | "admin" |
        | "app"   |
        | "infra" |

    Cenário: [CT-07] @premissa (A11) a tela de registro obedece à mesma escolha de painéis
      Dado o registro aberto ligado
      E o Google ligado e completo, valendo só no painel "admin"
      E o GitHub ligado e completo, com a lista de painéis vazia
      Quando alguém abre a tela de registro do painel "app"
      Então a tela de registro não traz o botão "Entrar com Google"
      E a tela de registro traz o botão "Entrar com GitHub"
      E o link do botão do GitHub na tela de registro é "/auth/github/redirect?painel=app"

    Esquema do Cenário: [CT-08] o link do botão declara o painel de onde a pessoa saiu
      Dado o GitHub ligado e completo, com a lista de painéis <lista>
      Quando alguém abre a tela de login do painel <painel>
      Então o link do botão do GitHub é "/auth/github/redirect?painel=<painel>"

      Exemplos:
        | painel  | lista               | # o que a linha acrescenta                          |
        | "admin" | vazia               | o painel corrente não é o default                   |
        | "app"   | vazia               | o painel corrente é o default                       |
        | "infra" | vazia               | o terceiro painel (A4)                              |
        | "admin" | ["admin"]           | lista RESTRITA: o link continua carregando o painel |
        | "infra" | ["infra","admin"]   | lista restrita com dois itens                       |

    Cenário: [CT-08b] o painel entra no link SEM derrubar a organização e o token do convite
      Dado o registro aberto ligado
      E o GitHub ligado e completo, com a lista de painéis vazia
      Quando alguém abre a tela de registro do painel "app" com a organização "acme" e o token "abc123" na URL
      Então o link do botão do GitHub carrega o painel "app"
      E o link do botão do GitHub carrega a organização "acme"
      E o link do botão do GitHub carrega o token "abc123"
```

**Por que a cruz negativa em CT-05 é obrigatória**: com um provedor só no arranjo, *"não traz o
botão do outro"* é verdadeiro por construção. Duas listas **diferentes** no mesmo arranjo é o
que torna a afirmação falsificável, e é o que reprova o filtro que usa o painel default para
todos. A terceira linha está declarada como **apoio**: ela cobre a exaustividade de A4 e não
discrimina nada que a linha `"app"` já não discrimine — declarar isso é mais honesto que fingir
que ela mata um mutante.

**CT-07 tem as DUAS metades, e é por isso que ele existe.** A versão anterior deste cenário
afirmava só *"não traz o botão do Google"* — e uma tela de registro que não oferece **provedor
nenhum** (o dev que conclui "registro não tem painel de origem para propagar" e devolve lista
vazia no hook de registro) ficava verde. Isso quebraria o cadastro social por convite, que é
feature ancestral viva, sem nenhum cenário vermelho. Achado da revisão adversarial (RA-01). O
`/app/register` sem token exige o registro aberto ligado — conferido em
`app/Filament/Pages/Auth/RegistroPorConvite.php:112-119` —, e é por isso que o `Dado` o liga.

**Por que CT-08 não é redundante com CT-05**: CT-05 prova que o botão certo aparece; CT-08 prova
que o link **leva o painel corrente**, e não o default nem nada. Sem CT-08 a barreira de R4
ficaria inalcançável pelo caminho real (a rota nunca receberia painel) e o destino de R5 cairia
sempre no default — com CT-05, CT-09 e CT-14 todos verdes. É a junta entre a tela e a rota, e
ela precisa de oráculo próprio.

**As duas últimas linhas de CT-08 são o achado RA-05**: com a lista sempre vazia, o mutante "o
link só carrega `painel` quando a lista do provedor está vazia" — que é o atalho de quem pensa
"se vale em todos, não preciso restringir; se é restrito, o painel já está decidido" —
sobreviveria a tudo.

**CT-08b é achado RA-23, e é a lacuna mais escorregadia das duas rodadas.** CT-16 prova que o
painel **na sessão** não apaga `org` e `token` — mas ele **forja a query à mão**, então nunca vê o
link que a tela produz. CT-08 mede o link com o contexto vazio. Entre os dois sobra exatamente o
mutante que importa: o dev que, ao acrescentar `painel` ao `route()`, **substitui** o array em vez
de somá-lo ao `array_filter([...'org'..., ...'token'...], 'is_string')` que já está lá. O convite
por organização quebra em silêncio, com CT-08, CT-16 e a suíte ancestral todos verdes — porque a
suíte ancestral testa o controller a partir de uma query que ela mesma monta, não a partir do link
da tela. A junta tela → rota **com o contexto irmão presente** não tinha nenhum oráculo.

**Nenhum cenário afirma posição, ícone ou cor** — isso é R3/R5 das wikis ancestrais e continua
coberto lá (`LoginSocialProvedoresTest` CT-08). Repetir aqui seria cenário que não mata mutante
novo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | o blade continua chamando `disponiveis()` **sem painel** | CT-05 (as células inválidas) |
| M13 | o blade passa `getDefaultPanel()` em vez do painel corrente | CT-05 linhas "admin" e "infra"; CT-08 |
| M14 | o filtro entra só no hook de login e não no de registro | CT-07 (`@premissa`) |
| M15 | o hook de registro deixa de oferecer **qualquer** provedor — *revisão adversarial* | CT-07, célula válida |
| M16 | o link não carrega `painel` na query | CT-08 |
| M17 | o link carrega um painel **fixo** (o default) | CT-08 linhas "admin" e "infra" |
| M18 | o link só carrega `painel` quando a lista do provedor está vazia — *revisão adversarial* | CT-08 linhas de lista restrita |
| M19 | a condição do botão é **negada**: aparece só onde não vale | CT-05 células válidas; CT-06 |
| M51 | o link é montado **substituindo** a query em vez de somar: `org` e `token` do convite desaparecem — *revisão adversarial* | CT-08b |

---

## Regra R4 — a rota de ida é a barreira, e ela é no servidor

> `RQ-05` · perfil **completo** · técnicas: **partição exaustiva do parâmetro de query** +
> **matriz provedor × painel** na superfície da rota

Esta é a regra número 1 do conjunto, e o motivo está em `.ai/rules/filament.md:19-29`: *"a query
é filtro de UI; a barreira é uma asserção no método"*. Um conjunto que só provasse "o botão não
aparece" (R3) deixaria a barreira inteira sem oráculo — a URL da rota é fixa, pública e
conhecida, e forjar `?painel=admin` é a coisa mais óbvia a se fazer com ela.

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: a rota de ida do provedor recusa quando o painel pedido na requisição não autoriza
         aquele provedor, e segue quando autoriza

    Esquema do Cenário: [CT-09] o painel pedido na requisição decide se a ida segue
      Dado o Google ligado e completo, valendo só no painel "app"
      Quando alguém pede a rota de ida do Google com <query>
      Então a resposta é <resposta>
      E ninguém está autenticado
      E o contexto de login social na sessão <contexto>

      Exemplos:
        | query           | resposta                      | contexto            | # partição                                       |
        | "?painel=app"   | um redirecionamento ao Google | traz o painel "app" | célula VÁLIDA — a ida segue onde o provedor vale |
        | "?painel=admin" | 404                           | não existe          | a query FORJADA: o painel não autoriza (RQ-05)   |
        | "?painel=infra" | 404                           | não existe          | segunda partição não autorizada                  |
        | "?painel="      | um redirecionamento ao Google | não traz painel     | valor vazio = painel não informado               |
        | sem query       | um redirecionamento ao Google | não traz painel     | ausente ≠ vazio ≠ não autorizado                 |

    Esquema do Cenário: [CT-10] @premissa (A6) painel que não existe não é tratado como recusa
      Dado o Google ligado e completo, valendo só no painel "app"
      Quando alguém pede a rota de ida do Google com <query>
      Então a resposta é um redirecionamento ao Google
      E o contexto de login social na sessão não traz painel nenhum

      Exemplos:
        | query               | # partição                                 |
        | "?painel=marketing" | id que não é painel registrado             |
        | "?painel[]=admin"   | valor não-string: a query aceita array      |
        | "?painel=%20app"    | id com espaço à esquerda, sem normalização  |

    Cenário: [CT-11] a recusa não apaga nem grava o contexto de cadastro
      Dado o Google ligado e completo, valendo só no painel "app"
      Quando alguém pede a rota de ida do Google com "?painel=admin&org=acme&token=abc123"
      Então a resposta é 404
      E não existe contexto de login social na sessão

    Esquema do Cenário: [CT-12] a barreira consulta a lista DAQUELE provedor
      Dado o Google ligado e completo, valendo só no painel "admin"
      E o GitHub ligado e completo, valendo só no painel "app"
      Quando alguém pede a rota de ida do provedor <provedor> com "?painel=<painel>"
      Então a resposta é <resposta>

      Exemplos:
        | provedor | painel  | resposta                      | # célula |
        | Google   | "admin" | um redirecionamento ao Google | válida   |
        | Google   | "app"   | 404                           | inválida |
        | GitHub   | "app"   | um redirecionamento ao GitHub | válida   |
        | GitHub   | "admin" | 404                           | inválida |

    Cenário: [CT-13] @do-plano a recusa por painel é registrada com o provedor e o painel
      Dado o Google ligado e completo, valendo só no painel "app"
      E o GitHub ligado e completo, com a lista de painéis vazia
      Quando alguém pede a rota de ida do Google com "?painel=admin"
      Então o canal "autenticacao" recebe um aviso com o motivo "painel_nao_autorizado"
      E esse aviso nomeia o provedor "google" e o painel "admin"
      E o canal "autenticacao" não recebe o registro informativo de redirecionamento ao provedor
```

**O par obrigatório está em CT-09 e CT-12**: sem a célula válida em cada coluna, uma barreira com
a condição **negada** — que derruba a rota sempre — ficaria verde em todas as linhas de recusa.
E CT-12 é o que impede a barreira de nascer com um provedor fixo: cada provedor é aceito num
painel e recusado no outro, **cruzado**, então nenhuma implementação com lista única passa.

**A afirmação do não-efeito está em TODAS as linhas de CT-09, não isolada em CT-11.** "404"
sozinho não distingue *recusar* de *gravar o contexto e depois recusar*, e a versão anterior deste
conjunto tinha o não-efeito só em CT-11, que roda uma query só — as linhas `?painel=infra`
ficavam sem ele (achado RA-11). A coluna `contexto` de CT-09 é a correção, e a linha válida dela
carrega a metade positiva: com o painel autorizado, o contexto **é** gravado, o que mata "nunca
grava o painel".

**CT-11 sobrevive a CT-09 por um motivo próprio**: o `org=acme&token=abc123` na query é o que
torna observável a diferença entre "não gravou nada" e "gravou o contexto de cadastro sem o
painel" — e é o contexto da wiki ancestral que a recusa não pode corromper.

**Sobre a rota de volta**: ela **não** entra nesta regra. A ADR-05 decide que o callback não
reconfere a autorização por painel, e o `00` não determina. O comportamento da volta com o
provedor desligado ou sem credencial continua sendo RQ-06 e continua coberto pelo caso ancestral
(`LoginSocialProvedoresTest` CT-03). O que a decisão da ADR-05 produz de observável está em
CT-20, marcado `@premissa`.

**CT-13 testa o `01`, não o `00`** — está marcado, e a pergunta A9 foi devolvida. Se A9 vier
negada como "não é requisito", CT-13 pode ser cortado sem perda de cobertura de cláusula; o
único mutante que ele mata (M25) é do plano. O segundo `E` — o aviso nomeia o provedor e o painel
— é achado RA-12: sem ele, um `warning` genérico disparado em qualquer recusa passa, e o
registro não serve para o incidente que ele existe para auditar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | a barreira existe só no botão; a rota não valida painel nenhum | CT-09 linha "?painel=admin"; CT-12 |
| M21 | a validação usa a lista de um provedor **fixo** | CT-12 (as quatro linhas, cruzadas) |
| M22 | a barreira é negada: 404 **também** no painel autorizado | CT-09 linha "?painel=app"; CT-12 células válidas |
| M23 | painel **ausente** na query passa a ser 404 (quebraria todo link antigo e a compatibilidade de A2) | CT-09 linhas "sem query" e "?painel=" |
| M24 | grava o contexto na sessão **antes** de barrar | CT-09 linhas de recusa (coluna `contexto`); CT-11 |
| M25 | o aviso da recusa não é escrito, é genérico, ou vai para o `stack` em vez do canal `autenticacao` | CT-13 (`@do-plano`) |

---

## Regra R5 — quem entra por um painel autorizado termina naquele painel

> `A1` (leitura ampla, respondida pelo mantenedor) · perfil **padrão** · técnicas: **tabela
> desfecho × painel de origem** + **matriz papel × painel**, com o oráculo no `Location`

O oráculo desta regra é o **`Location`**, e não a sessão: "o painel está na sessão" é afirmação
sobre o mecanismo, e passa com o destino ainda fixo no `/app`. O par admin + app é obrigatório:
sem a linha do `/app`, uma implementação com o destino fixo no `admin` fica verde.

E o **desfecho** é dimensão, não detalhe: o `00` enumera **seis** `getPanel('app')`, dos quais
**um** é o sucesso, quatro são "volta ao login" e um é o perfil. Um conjunto que percorra os três
painéis sempre pelo caminho de sucesso deixa cinco dos seis pontos sem oráculo.

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: quem entra pelo botão da tela de um painel autorizado termina naquele painel, em todos
         os desfechos da volta, e o painel de origem não concede acesso a ele

    Esquema do Cenário: [CT-14] o destino do sucesso é o painel de onde a pessoa saiu
      Dado o GitHub ligado e completo, valendo exatamente no painel <painel>
      E uma pessoa com conta e com o papel <papel> do painel <painel>
      Quando ela completa a volta do GitHub tendo saído da tela de login do painel <painel>
      Então o caminho do redirecionamento é "/<painel>"
      E ela está autenticada
      E o caminho do redirecionamento responde 200 quando seguido

      Exemplos:
        | painel  | papel        |
        | "admin" | "admin"      |
        | "app"   | "panel_user" |
        | "infra" | "infra"      |

    Cenário: [CT-15] o painel de origem não vira permissão de acesso
      Dado o GitHub ligado e completo, com a lista de painéis vazia
      E uma pessoa com conta e com o papel "panel_user", que não abre o "/admin"
      Quando ela completa a volta do GitHub tendo saído da tela de login do painel "admin"
      Então o caminho do redirecionamento é "/admin"
      E o caminho do redirecionamento responde 403 quando seguido

    Cenário: [CT-16] o painel na sessão não apaga a organização nem o token do convite
      Dado o GitHub ligado e completo, com a lista de painéis vazia
      Quando alguém pede a rota de ida do GitHub com "?painel=admin&org=acme&token=abc123"
      Então o contexto de login social na sessão traz o painel "admin"
      E o contexto de login social na sessão traz a organização "acme"
      E o contexto de login social na sessão traz o token "abc123"

    Esquema do Cenário: [CT-17] com tenancy ligada, entrar pelo "/admin" não tenta resolver organização
      Dado o kit com multi-tenancy ligada e uma organização "acme"
      E o GitHub ligado e completo, com a lista de painéis vazia
      E uma pessoa com o papel "admin" no contexto global e a conta no estado <estado>
      Quando ela completa a volta do GitHub tendo saído da tela de login do painel "admin"
      Então o caminho do redirecionamento começa por "/admin"
      E o caminho do redirecionamento não começa por "/app"
      E ela está autenticada
      E o caminho do redirecionamento responde 200 quando seguido
      E nenhuma organização está fixada como corrente

      Exemplos:
        | estado                                     | # o ponto de destino exercitado                    |
        | existente                                  | o destino de sucesso, no painel SEM tenancy         |
        | inexistente, com o registro aberto ligado  | o **perfil**, que é o ponto que consulta `hasTenancy()` |

    Esquema do Cenário: [CT-18] a volta que NÃO termina em sucesso também volta ao painel de origem
      Dado o GitHub ligado e completo, com a lista de painéis vazia
      E que a pessoa saiu da tela de login do painel <painel>
      E a conta dela no estado <estado>
      Quando ela completa a volta do GitHub
      Então o caminho do redirecionamento começa por "/<painel>"
      E o caminho do redirecionamento não começa por "/app/"
      E ela <sessao>

      Exemplos:
        | painel  | estado                                  | sessao                | # o ponto de destino exercitado          |
        | "admin" | inexistente, com o registro fechado     | não está autenticada  | a volta ao login na recusa               |
        | "admin" | desativada ou excluída                  | não está autenticada  | o aviso de conta indisponível            |
        | "infra" | inexistente, com o registro fechado     | não está autenticada  | a mesma recusa, em outro painel de origem |
        | "admin" | inexistente, com o registro aberto ligado | está autenticada    | o perfil de quem acabou de se cadastrar  |
```

**Estouro do teto declarado**: o perfil da área é `padrão` (teto 3) e esta regra tem 5 cenários.
Motivo — os seis `Filament::getPanel('app')` do controller **não são o mesmo comportamento**, e o
`00` os enumera um por um como a razão de a feature existir. Os cinco cenários cobrem
comportamentos distintos (o destino do sucesso, a não-concessão de acesso, a preservação do
contexto irmão, o painel sem tenancy e **os cinco pontos que não são o sucesso**), e nenhum
deles mata os mutantes de outro. O gate do passo 6 vence o teto: deixar M27 vivo para economizar
cenário inverteria a razão de existir do conjunto.

**CT-18 é o achado mais caro da revisão adversarial (RA-02).** Sem ele, a implementação que
parametriza **só** `urlDoPainel()` e deixa `:434`, `:466`, `:590`, `:666` e `:694` no `app` fica
verde em tudo — e quem entra pelo `/admin` e cai numa recusa é jogado em `/app/login`, que é
**literalmente o incidente** que a wiki do Google já pagou uma vez. O `E o caminho não começa por
"/app/"` é a metade que importa: sem ela, um destino que sempre volta ao `/app/login` passaria na
linha de origem `"app"` e a asserção positiva de `"/admin"` não seria suficiente para as outras.

**Por que CT-14 usa lista RESTRITA ao painel da linha, e não lista vazia**: com a lista sempre
vazia, o mutante "a resolução do destino consulta a lista de painéis e cai no default quando ela
é restrita" nunca é exercitado (achado RA-06). Restringir ao painel da própria linha é
estritamente mais forte e não custa nada.

**Por que CT-14 e CT-17 seguem o `Location`**: um redirecionamento não é uma tela que abre. Foi
assim que a instalação real da wiki do Google mandou a pessoa para `/app/meu-perfil` e ela
recebeu 403 — o oráculo era o destino, e o destino não abria.

**Por que CT-15 é o contrapeso obrigatório de CT-14**: sem ele, "o destino é o painel de origem"
convive com uma implementação que **concede** acesso ao painel de origem — e a A5 diz o
contrário (o comportamento atual do painel decide). CT-15 fixa o 403 como o resultado esperado,
e é a única persona do conjunto sem o papel do painel de origem.

**Por que CT-16 afirma sobre a sessão** (contra a regra geral desta seção): o efeito observável
da propagação é o conteúdo do contexto, e a alternativa de caixa-preta — o fluxo inteiro de
cadastro por convite dentro de uma organização — pertence à suíte `tests/Tenancy` da wiki
ancestral e é regressão dela, não cobertura desta. O mutante que este cenário mata (`+` trocado
por atribuição, que descarta `org` e `token`) é silencioso e quebra a feature do vizinho.

**Por que CT-17 vive em `tests/Tenancy`**: `urlDoPerfil()` consulta `$painel->hasTenancy()`, e
`tests/Kit` roda single-tenant — o ramo com tenancy nunca é exercitado lá. O painel de destino
`/admin` **não** tem tenancy, e o `/app` tem: um destino parametrizado que assuma tenancy
estoura, e o cenário é 500 em vez de 302. O último `E` é achado RA-13: o título prometia "não
tenta resolver organização" e nenhum `Então` afirmava o não-efeito.

**A segunda linha de CT-17 é achado RA-24, e ela fecha o que era a lacuna declarada L3.** A
primeira linha exercita o destino de **sucesso** com tenancy; só a segunda alcança
`urlDoPerfil()` — que é justamente o ponto que consulta `hasTenancy()`, e o único dos seis cuja
parametrização errada produz 500 em vez de um destino errado. Fechar a lacuna aqui **discrimina**:
a implementação que resolve o perfil pelo painel `app` fixo devolve um caminho sob `/app` (que a
segunda asserção reprova) ou tenta resolver a organização de um painel que não tem tenancy (que a
quarta reprova, com 500). Sem esta linha, a lacuna L3 continuaria valendo — e a régua da skill é
explícita: fechar lacuna sem discriminar é pior que declará-la.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | o destino do sucesso continua fixo em `getPanel('app')` | CT-14 linhas "admin" e "infra"; CT-17 |
| M27 | **só** o destino do sucesso é parametrizado; os cinco pontos de recusa e de perfil ficam no `app` — *revisão adversarial* | CT-18 (as quatro linhas) |
| M28 | o destino é parametrizado mas fixado no `admin` | CT-14 linha "app"; CT-18 linha de origem "infra" |
| M29 | o painel **substitui** o contexto da sessão em vez de somar (`org` e `token` perdidos) | CT-16 |
| M30 | o painel de origem passa a conceder acesso, contornando `canAccessPanel()` | CT-15 |
| M31 | a resolução do destino consulta a lista de painéis e cai no default quando ela é restrita — *revisão adversarial* | CT-14 (lista restrita ao painel da linha) |
| M53 | com tenancy ligada, o destino do **perfil** assume o painel `app` e tenta resolver organização num painel que não tem — *revisão adversarial* | CT-17 linha "registro aberto ligado" |

---

## Regra R6 — sem painel válido na sessão, o destino é o default e nada lança

> `A1` + `ADR-06` · perfil **padrão** · técnica: **partição do estado da sessão**
> (ausente / descartada / inexistente / a autorização mudou no meio)

Sessão se perde: cookie bloqueado, expiração entre a ida e a volta, link do callback aberto em
outro navegador. É o caminho real, não a exceção teórica.

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: quando a sessão não traz um painel válido, a volta termina no painel default do
         Filament, sem lançar

    Esquema do Cenário: [CT-19] sessão sem painel válido cai no painel default
      Dado o GitHub ligado e completo, com a lista de painéis vazia
      E uma pessoa com conta e com o papel do painel default
      E a sessão no estado <estado>
      Quando ela completa a volta do GitHub
      Então o caminho do redirecionamento é o do painel default do Filament
      E ela está autenticada

      Exemplos:
        | estado                                                       | # partição                            |
        | sem contexto de login social nenhum                          | a volta chamada direto, sem ida        |
        | com o contexto gravado pela ida do "/admin", descartado depois | cookie perdido entre a ida e a volta  |
        | com um painel inexistente no contexto                        | sessão manipulada: o destino revalida  |

    Cenário: [CT-20] @premissa (A8, ADR-05) a autorização retirada no meio do fluxo não vira 404 na volta
      Dado o GitHub ligado e completo, valendo no painel "admin"
      E uma pessoa com conta e com o papel "admin"
      E que ela já pediu a rota de ida do GitHub a partir do painel "admin"
      E que o GitHub passou a valer só no painel "app"
      Quando ela completa a volta do GitHub
      Então o caminho do redirecionamento é "/admin"
      E ela está autenticada
```

**A terceira linha de CT-19 é a que fecha uma lacuna que parecia impossível**: o painel viaja
pela sessão, e um teste de Feature pode escrever nela (`session([...])`), então o mutante "confia
na sessão sem revalidar" é observável. A hipótese de "o arnês não permite" foi testada e é falsa.

> **Correção de um fato do `01`, lida no `vendor/` antes de escrever** (`.ai/rules/specs.md`): a
> ADR-06 diz que *"`getPanel()` com id inexistente lança"*. **Nesta versão do Filament ele não
> lança.** `FilamentManager::getPanel()` (`vendor/filament/filament/src/FilamentManager.php:372-375`)
> delega a `PanelRegistry::get()` (`vendor/filament/filament/src/PanelRegistry.php:36-44`), que
> devolve `null` para `$id === null` **e** para id desconhecido em modo estrito. O que estoura é o
> `->getUrl()` sobre `null`, um `Error` — o observável continua sendo **500 em vez de 302**, e
> CT-19 continua sendo o matador. A **decisão** da ADR-06 (cair no default, nunca lançar) segue
> correta; só a justificativa estava errada. É exatamente o padrão que a rule descreve: conclusão
> certa pelo motivo errado, que fica invisível até alguém tentar consertar o cenário pelo motivo
> escrito.

**O `E ela está autenticada` é achado RA-10**, e não é enfeite: sem ele, a implementação que
manda a pessoa ao painel default **sem abrir sessão** fica verde — ela cai na tela de login do
`/app` e o fluxo falha em silêncio, com o `Location` "correto". A asserção anterior ("a resposta
não é um erro de servidor") foi **removida** por ser redundante: num 500 não há `Location`, então
a primeira linha do `Então` já a exclui.

**O oráculo é "o painel default do Filament", não a string `/app`.** Escrever `/app` mediria a
configuração do ambiente de teste: o `app` **é** o default hoje, e afirmar o literal deixaria o
cenário verde no dia em que o default mudasse e o fallback continuasse escrito à mão. A segunda
linha carrega a metade que importa: o contexto veio do `/admin` e foi descartado, e o destino
**não** é `/admin`.

**Em CT-20 a mudança de configuração é `Dado`, não `Quando`** — um único `Quando` por cenário. A
alteração da lista é rearranjo do mundo; o evento é a volta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | o id da sessão vai direto para `Filament::getPanel()` sem revalidar; ele devolve `null` e o `->getUrl()` estoura | CT-19 linha 3 |
| M33 | sem painel na sessão, aborta 404 em vez de cair no default | CT-19 linhas 1 e 2 |
| M34 | sem painel na sessão, redireciona ao default **sem autenticar** — *revisão adversarial* | CT-19 (o `E ela está autenticada`) |
| M35 | a volta reconfere a autorização por painel e responde 404 | CT-20 (`@premissa`) |

---

## Regra R7 — a tela de settings grava a escolha de cada provedor, e o gravado governa

> `RQ-01`, `RQ-02`, `A3`, `A4` · perfil **padrão** · técnicas: **gravação por componente** +
> **matriz provedor × propriedade, exaustiva nos quatro** + **rastreio de ligação** settings →
> config → decisão + **criação × edição**

A Page de settings é **tela de escrita**, e o gate é explícito: *uma tela aberta não é uma tela
que grava*. `GET /admin/configuracoes-do-kit` já é coberto pelo inventário de telas; o que esta
regra cobra é `fillForm` → `->call('save')` → asserção sobre **o valor gravado**.

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: a tela de configurações grava a lista de painéis de cada provedor, sem tocar a dos
         outros, e o valor gravado é o que decide

    Cenário: [CT-21] um único salvamento grava a escolha de dois provedores, sem mexer nos outros
      Dado um administrador na tela de configurações do kit
      E a propriedade de painéis do LinkedIn já gravada com o painel "infra"
      Quando ele escolhe o painel "admin" para o Google e os painéis "app" e "infra" para o X, e salva
      Então o formulário não acusa erro e notifica
      E o valor gravado da propriedade de painéis do Google é exatamente ["admin"]
      E o valor gravado da propriedade de painéis do X é exatamente ["app","infra"]
      E o valor gravado da propriedade de painéis do LinkedIn continua sendo exatamente ["infra"]
      E o valor gravado da propriedade de painéis do GitHub continua sendo uma lista vazia

    Esquema do Cenário: [CT-22] cada um dos quatro provedores tem escolha que governa
      Dado o provedor <provedor> ligado e completo, oferecido nos três painéis
      E um administrador que escolheu só o painel "admin" para <provedor> e salvou
      Quando as configurações gravadas são aplicadas na configuração do processo
      Então a tela de login do "/admin" traz o botão de <provedor>
      E a tela de login do "/app" não traz o botão de <provedor>

      Exemplos:
        | provedor |
        | Google   |
        | GitHub   |
        | LinkedIn |
        | X        |

    Cenário: [CT-23] esvaziar a escolha pela tela devolve o provedor a todos os painéis
      Dado o Google ligado e completo, com o painel "admin" gravado como única escolha
      E que a configuração do processo é alinhada com o gravado a cada requisição, como o boot faz
      Quando o administrador esvazia a escolha de painéis do Google e salva
      Então o valor gravado da propriedade de painéis do Google é uma lista vazia
      E a tela de login do "/app" traz o botão "Entrar com Google"
      E a tela de login do "/admin" traz o botão "Entrar com Google"
      E a tela de login do "/infra" traz o botão "Entrar com Google"

    Cenário: [CT-24] painel gravado que não existe mais não trava a tela inteira
      Dado a propriedade de painéis do Google gravada com o painel "marketing"
      Quando o administrador altera o nome da aplicação e salva
      Então o formulário não acusa erro
      E o valor gravado do nome da aplicação é o novo
      E o valor gravado da propriedade de painéis do Google continua sendo exatamente ["marketing"]

    Esquema do Cenário: [CT-25] o campo aceita exatamente os painéis registrados no kit
      Dado um administrador na tela de configurações do kit
      E a propriedade de painéis do Google já gravada com o painel "app"
      Quando ele escolhe <escolha> para o Google e salva
      Então o formulário <veredicto>
      E o valor gravado da propriedade de painéis do Google é exatamente <gravado>

      Exemplos:
        | escolha       | veredicto           | gravado   | # o que a linha prova                                   |
        | ["admin"]     | não acusa erro      | ["admin"] | um id registrado é aceito                               |
        | ["infra"]     | não acusa erro      | ["infra"] | o terceiro painel também (A4)                           |
        | ["marketing"] | acusa erro no campo | ["app"]   | id que não é painel registrado é recusado, e nada muda  |

    Cenário: [CT-25b] as opções oferecidas são os painéis que o kit registra, e não uma lista escrita à mão
      Dado um administrador na tela de configurações do kit
      Quando ele abre a seção de login social
      Então as opções do campo de painéis de cada provedor são exatamente os ids dos painéis registrados no Filament
```

**Estouro do teto declarado**: perfil `padrão` (teto 3), seis cenários. Os três excedentes são
todos achado da revisão adversarial, e cada um mata mutante que nenhum outro mata:

- **CT-22 é `Esquema` com os quatro provedores** (achado RA-03). A versão anterior tinha o
  rastreio settings → decisão só para o **Google**, e amostrava três provedores na gravação. Uma
  implementação que declarasse propriedade (ou linha do `mapaDeConfiguracao()`) para três dos
  quatro passava em tudo — os cenários que usam GitHub nesta wiki arranjam por `config()->set`,
  **desviando da camada de settings**. E o caso ancestral que assere que *toda propriedade
  declarada é semeada* não reprova uma propriedade que nunca foi declarada. RQ-01 diz "**cada**
  provedor".
- **CT-21 afirma o não-efeito nas propriedades irmãs** (achado RA-04): um `statePath`
  compartilhado, ou um `save()` que reescreve o bloco de login social inteiro, apaga a escolha do
  vizinho sem nada ficar vermelho. E ele salva **dois provedores num único `save()`**, que é o uso
  real da tela.
- **CT-25b** fecha A4 do lado do campo (achado RA-08). Um campo com a lista `['admin','app','infra']`
  **escrita à mão** passa em CT-25 e nos outros 28 cenários, e para de reconhecer um painel novo —
  exatamente a propriedade que a varredura SFDIPOT reivindica em **P**. O oráculo compara as opções
  com o registro do Filament, que é a **outra** fonte da verdade; não é tautologia por isso, e é o
  mesmo mecanismo de âncora que `tests/Kit/InventarioDeTelasTest.php` usa.

**CT-24 é regressão de defeito medido neste kit**, não cobertura de fronteira: achado QA-01 de
`settings-do-kit` (Major), e entra pela linha da taxonomia que o histórico do próprio projeto
alimentou. O mecanismo, lido no `vendor/` antes de escrever (`.ai/rules/specs.md`): num `Select`
com `->multiple()`, `getInValidationRuleValues()` compara o estado corrente com as opções e
devolve `[]` quando sobra qualquer valor
(`vendor/filament/forms/src/Components/Select.php:1771-1772`, o `array_diff` → `return []`); esse
`[]` vira `Rule::in([])`
(`vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:808-814`) e é aplicado ao
campo (`:854` e `:916`). Ou seja: um valor **gravado** que a tela não oferece reprova o campo — e,
com ele, o `save()` inteiro. Nem o nome da aplicação grava. O estado é alcançável: `.env` com um
painel removido, ou um painel renomeado depois da escolha.

**O par de oráculos de CT-24 é deliberado**: o outro campo gravou **e** o valor de fora
sobreviveu — sem a segunda metade, "normalizar em silêncio para o default" passaria, e normalizar
em silêncio é perda de dado, não validação. É a mesma forma do caso que já existe no arquivo para
`mail_mailer` e `cor_primaria`.

**A tensão entre CT-25 (linha 3) e CT-24 é deliberada, e é o ponto da regra.** A linha 3 exige que
um id que **chega pelo formulário** e não é painel seja recusado; CT-24 exige que um id que **já
estava gravado** e não é mais painel não trave a tela. As duas convivem — é o que o kit já faz com
`mail_mailer` e `cor_primaria`, oferecendo o valor gravado como opção marcada. O que os dois
cenários juntos impedem é o conserto errado: desligar a regra de domínio do campo para fazer
CT-24 passar deixaria CT-25 linha 3 vermelho, e vice-versa.

**O valor anterior de CT-25 é `["app"]`, não vazio** (achado RA-14): numa linha de criação "o
valor anterior" é a lista vazia, e o oráculo ficaria indistinguível de "o formulário acusou erro
**e** gravou vazio".

**Por que CT-21 leva mais de um provedor e por que a linha do LinkedIn importa**: um campo que
grave sempre na propriedade do Google passa num cenário de um provedor. O nome do LinkedIn é a
discriminante: o valor do enum é `linkedin-openid` e a propriedade é
`login_linkedin_openid_paineis` — montar a string à mão em vez de usar
`propriedadeDeSettings('paineis')` grava numa propriedade que não existe.

**Por que CT-22 é o cenário mais valioso desta regra**: ele é o único que mata o defeito
silencioso que `.ai/rules/settings.md:30` nomeia — a **linha do `mapaDeConfiguracao()`
esquecida**. Com ela ausente o campo aparece, grava, e não governa nada: CT-21 fica verde e a
produção segue oferecendo o botão em todos os painéis. E ele também mata o registro **no boot**:
o alinhamento acontece depois da gravação, num arnês em que os provedores já bootaram, então uma
decisão congelada no boot devolve o estado anterior.

**Os três painéis em CT-23** são achado RA-07: com o oráculo num painel só, a implementação que
traduz "vazio" para o **painel default** (em vez de para "todos") fica verde — M33 cobre "vazio →
nenhum", não "vazio → default", e o `/infra` é justamente o painel que A4 admitiu por decisão e
não por pedido.

**Recusado como oráculo**: o `->visible()` do campo casado com o toggle do provedor, o rótulo
`Painéis` e o `helperText`. São escolha do `01`; ver A10.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | falta a linha no `mapaDeConfiguracao()`: o campo grava e não governa nada | CT-22 |
| M37 | o campo grava sempre na propriedade do Google, ou monta o nome do LinkedIn à mão | CT-21; CT-22 linhas LinkedIn e X |
| M38 | o campo grava string (`"admin"`) em vez de lista | CT-21, CT-25 (comparação estrita com a lista) |
| M39 | o vazio gravado pela tela é traduzido para "nenhum painel" | CT-23 |
| M40 | o `Rule::in()` do `Select` reprova o formulário inteiro por um valor **gravado** fora das opções | CT-24 |
| M41 | só um (ou três) provedor recebe propriedade de settings ou linha do mapa — *revisão adversarial* | CT-22 (as quatro linhas); CT-21 (a asserção do GitHub) |
| M42 | o `save()` de um provedor sobrescreve a propriedade de painéis dos irmãos — *revisão adversarial* | CT-21 |
| M43 | o vazio gravado é traduzido para o painel **default** — *revisão adversarial* | CT-23 (os três painéis) |
| M44 | as opções do campo são uma lista de painéis escrita à mão — *revisão adversarial* | CT-25b |

---

## Regra R8 — a chave de config nasce vazia, e vazia significa todos

> `A2` (respondida: default = todos os painéis) · perfil **padrão** · técnicas: **partição do
> valor do `.env`, medida no próprio `config/kit.php`** + **rastreio semeadura → alinhamento →
> decisão**

Esta é a regra que decide se o update quebra ou não toda instalação que hoje usa login social. Se
CT-26, CT-28 ou CT-29 falharem, a feature nasce desligando o acesso de quem já o tinha — que é
exatamente o oposto do que o `00` assumiu em A2.

```gherkin
# language: pt

Funcionalidade: Login social por painel

  Regra: a chave de painéis de cada provedor traduz o valor do ambiente para uma lista de ids, e
         o valor ausente produz lista vazia

    Esquema do Cenário: [CT-26] a coerção da lista, medida no próprio arquivo de configuração
      Dado a variável de ambiente de painéis do Google com o valor <valor>
      Quando o arquivo de configuração do kit é lido
      Então a chave de painéis do Google é exatamente <lista>

      Exemplos:
        | valor          | lista           | # o que a linha discrimina                                   |
        | ausente        | []              | o DEFAULT de fábrica — mata o default ["app"] ou os três      |
        | ""             | []              | vazio ≠ ausente, mesmo resultado declarado                    |
        | "   "          | []              | só espaços: separa `array_filter` de uma comparação com ""     |
        | "admin"        | ["admin"]       | um item                                                        |
        | "admin,app"    | ["admin","app"] | dois itens                                                     |
        | "admin, app"   | ["admin","app"] | sem `trim`, o "/app" perde o provedor em silêncio               |
        | ",admin,,app," | ["admin","app"] | sem `array_filter` e `array_values`, a lista fica com vazios e chaves furadas |

    Esquema do Cenário: [CT-27] cada provedor tem a sua chave, com a sua coerção, e a do LinkedIn casa o hífen
      Dado a variável de ambiente de painéis de <provedor> com o valor " admin, app ,"
      Quando o arquivo de configuração do kit é lido
      Então a chave de painéis de <provedor> é exatamente ["admin","app"]
      E as chaves de painéis dos outros três provedores são listas vazias

      Exemplos:
        | provedor |
        | Google   |
        | GitHub   |
        | LinkedIn |
        | X        |

    Esquema do Cenário: [CT-28] de fábrica, sem nenhuma escolha, o provedor vale nos três painéis
      Dado o Google ligado e completo, sem nenhuma escolha de painéis em lugar nenhum
      E que a tela de login do painel <painel> foi aberta e traz o botão "Entrar com Google"
      Quando alguém pede a rota de ida do Google com "?painel=<painel>"
      Então a resposta é um redirecionamento ao Google

      Exemplos:
        | painel  |
        | "admin" |
        | "app"   |
        | "infra" |

    Esquema do Cenário: [CT-29] a instalação que só roda migrate não perde o provedor de painel nenhum
      Dado o Google ligado e completo
      E as configurações do kit semeadas pela migration, sem ninguém ter escolhido nada
      E que as configurações gravadas foram aplicadas na configuração do processo
      Quando alguém abre a tela de login do painel <painel>
      Então o valor gravado da propriedade de painéis do Google é uma lista vazia
      E a chave de config de painéis do Google é uma lista vazia
      E a tela traz o botão "Entrar com Google"

      Exemplos:
        | painel  |
        | "admin" |
        | "app"   |
        | "infra" |
```

**Por que CT-26 mede o arquivo e não o valor que o teste escreveu**: `config()->set()` aceita
qualquer chave e qualquer valor, então um cenário que arranje a lista em memória mede o arranjo.
`kitConfigCom()` relê o `config/kit.php` com a variável forçada e restaura o ambiente no
`finally` — é o método que os vizinhos aplicam ao interruptor (`LoginSocialProvedoresTest`
CT-40) e ao bloco de credenciais (CT-46), e é a única forma de matar um default errado.

**A linha `"admin, app"` é a mais valiosa do conjunto inteiro depois de CT-09.** Um `.env`
escrito por gente tem espaço depois da vírgula. Sem `trim`, a lista fica `['admin', ' app']`, o
`in_array` estrito falha para `app`, e o painel `/app` **perde o provedor sem nenhum erro em
lugar nenhum**.

**CT-27 usa `" admin, app ,"` e não `"admin"`** (achado RA-06): com o valor `"admin"` — sem
espaço, sem vírgula dupla e sem vazio — a linha não discrimina **nada**, e os mutantes de coerção
sobreviviam em três dos quatro provedores (bloco copiado do Google com `explode()` cru). CT-26 e
CT-27 cruzam duas dimensões pela metade cada; com o valor certo em CT-27 o cruzamento fecha.

**Por que CT-28 não é redundante com CT-06**: CT-06 arranja a lista vazia explicitamente; CT-28
não arranja **nada** — nem `.env`, nem config, nem settings. É a diferença entre "vazio se
comporta como todos" e "o estado de fábrica **é** vazio". Em CT-28 a abertura da tela é `Dado`,
não `Quando`: o evento é a rota de ida, e a tela é a precondição que prova que o botão existe
para ser clicado.

**CT-29 é achado RA-15, e é a persona que a varredura SFDIPOT declara em O e que CT-28 não
encarna.** CT-28 é a instalação **nova**; CT-29 é a instalação **existente** que atualiza o kit e
só roda `migrate`. Uma migration que semeie a propriedade com `['app']` — o cenário "Se negado" de
A2, e a tentativa honesta de "não mudar comportamento", já que o `app` é o destino efetivo de hoje
— passa em CT-26 (que mede o arquivo) e em CT-28 (que não aplica as configurações gravadas), e
some com o Google do `/admin` e do `/infra` em toda instalação existente. O caso ancestral
(`ConfiguracoesDoKitTest.php:305`) prova que a propriedade **é** semeada, não **com quê**.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M45 | o default da chave é `["app"]`, ou os três painéis explícitos, no `config/kit.php` | CT-26 linha "ausente"; CT-28 |
| M46 | sem `trim`: `"admin, app"` deixa `" app"` na lista e o `/app` perde o provedor | CT-26 linha "admin, app"; CT-27 (nos quatro provedores) |
| M47 | sem `array_filter`/`array_values`: vazios na lista e chaves não reindexadas | CT-26 linhas "   " e ",admin,,app,"; CT-27 |
| M48 | as quatro chaves leem a **mesma** variável de ambiente (bloco copiado do vizinho) | CT-27 (a asserção das outras três vazias) |
| M49 | o caminho de config do LinkedIn usa `linkedin` em vez de `linkedin-openid` | CT-27 linha LinkedIn |
| M50 | **a migration semeia a propriedade com `["app"]`** — *revisão adversarial* | CT-29 |

---

## Checklist de Taxonomia

<!-- Resposta válida: um ID de cenário, "não se aplica: {motivo}", ou
     "lacuna declarada: {o que foi tentado}". NUNCA "sim". -->

| Item | Cenário que mata |
|---|---|
| **IDOR / autorização horizontal** | CT-09, CT-12 — o `?painel=` **é** o identificador de recurso desta feature, e a forja dele é o cenário nº 1 |
| **Autorização exercida na ação, não só no filtro de UI** | CT-09, CT-11, CT-12 — a rota chamada direto, sem passar pela tela |
| **Autorização em cada verbo irmão** | CT-09 (a ida) · a volta é fora de escopo por ADR-05, com a consequência observável em CT-20 |
| **Todo desfecho da operação, não só o feliz** | CT-18 — as quatro linhas cobrem os cinco pontos de destino que não são o sucesso |
| **Domínio condicionado** (a lista depende do provedor) | CT-01, CT-03, CT-12, CT-22 |
| **Fronteira no ponto de entrada (gravação)** | CT-25 (a linha `["marketing"]`), CT-26 |
| **Criação ≠ edição ≠ uso** | criação: CT-21, CT-25 · edição: CT-23 (altera para vazio) e CT-24 (salva sem tocar a lista) · uso: CT-01, CT-05, CT-09, CT-22 |
| **Ausente ≠ null ≠ vazio** | CT-01 (**chave ausente** × lista vazia × `$painel` nulo), CT-09 (query ausente × `?painel=` vazio × não autorizado), CT-26 (env ausente × `""` × `"   "`) |
| **Estado × operação de escrita** | não se aplica: a feature não tem ciclo de vida nem status; a entidade é uma lista de configuração |
| **Idempotência** | não se aplica: a ida grava um contexto de sessão de uso único, cujo valor correto **é** o do último clique; e o agregado que sofre efeito (a conta) não é tocado por esta feature. O par de idempotência do callback é CT-14 de `LoginSocialProvedoresTest`, que continua verde |
| **Concorrência** | não se aplica: não há contador, saldo nem limite; o contexto é da sessão do próprio usuário |
| **Paginação** | não se aplica: nenhuma listagem |
| **Ordenação** | CT-03 — a ordem do enum é oráculo de `disponiveis()`; ordenação por coluna não existe |
| **Timezone / DST / virada de dia** | não se aplica: nada nesta feature é datado. A única dimensão temporal é a janela entre a ida e a volta, coberta por CT-20 |
| **Unicode / limite de varchar / espaços nas bordas** | CT-26 e CT-27 (espaços na lista do `.env`), CT-10 (`%20app` na query), CT-02 (caixa no id) |
| **Unicidade + soft delete** | não se aplica: nada é criado nem excluído |
| **CRUD combinado** (ler/editar/excluir inexistente) | CT-10 (painel inexistente na requisição), CT-19 linha 3 (na sessão), CT-24 (gravado), CT-25 linha 3 (submetido) |
| **Mass assignment** | CT-25 linha `["marketing"]` — id que a tela não oferece é recusado pelo campo |
| **Migração / update de instalação existente** | CT-01 (chave ausente da config), CT-29 (a propriedade semeada) |
| **Upload** | não se aplica |
| **Precisão monetária** | não se aplica |
| **Efeito colateral / log** | CT-13, marcado `@do-plano` e com a pergunta A9 devolvida — o `00` não pede rastro |
| **Regressão em feature adjacente** | CT-07 (o cadastro social na tela de registro), CT-16 (o `org`/`token` do convite), CT-17 (tenancy), e a suíte ancestral inteira, listada na `## Verificação Final` do `01` |

### Lacunas declaradas

| # | O que não tem cenário | O que foi tentado | Por que ficou de fora |
|---|---|---|---|
| L1 | o mutante `in_array` frouxo com um **inteiro** na lista (`[0]`, `[1]`) | montar a linha `[0]` com o painel `"admin"` e medir | em PHP 8.4 `0 == "admin"` é **falso** (medido), então a implementação certa e a errada concordam e a linha seria decorativa. Substituída pela linha `[true]`, que discrimina de verdade |
| L2 | a leitura da propriedade **no boot do painel** por um caminho diferente do render hook | CT-22, que grava, alinha e abre as telas depois de os provedores já terem bootado | CT-22 mata a variante que congela a decisão no boot do provider. O que **não** tem cenário é um registro condicional feito no `Panel::boot()` de cada painel: teste de componente não atravessa o `SetUpPanel`, e o arnês HTTP boota os painéis uma vez por caso. Se o `01` mudar de ideia e registrar o hook por painel, esta lacuna vira bloqueio |
**L3 foi FECHADA, não declarada.** Ela era "os pontos de destino que não são o sucesso, no ramo
com tenancy", e a segunda entrega da revisão mostrou que o ponto que importa ali —
`urlDoPerfil()`, o único cujo erro produz 500 — é alcançável com uma linha a mais em CT-17. O que
segue **não** coberto, e por decisão de escopo declarada, é replicar as quatro linhas de CT-18 em
`tests/Tenancy`: elas provariam o mesmo `hasTenancy()` quatro vezes, e o requisito não determina
o comportamento das telas de recusa por painel com tenancy.

**Nenhum mutante previsto está sem matador.** Os três matadores que a revisão adversarial provou
serem falsos (M4, M45 e o antigo "M23") foram corrigidos: M4 ganhou linhas discriminantes em
CT-01, M45 teve o alcance reduzido ao `config/kit.php` e a variante da semeadura virou M50 com
CT-29, e o antigo M23 foi **desdobrado** em M26 (o sucesso) e M27 (os outros cinco pontos), com
CT-18 nascendo para o segundo.

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | a decisão conjuga interruptor, as três credenciais e a lista | R1 | tabela de decisão | Feature (`Kit`) | `tests/Kit/LoginSocialPorPainelTest.php` | M1–M4, M6, M7, M52 |
| CT-02 | a lista casa o id por identidade | R1 | partição do tipo do elemento | Feature (`Kit`) | idem | M5, M6 |
| CT-03 | cada painel oferece exatamente os provedores que valem nele | R2 | matriz provedor × painel | Feature (`Kit`) | idem | M8, M9, M10 |
| CT-04 | mudar a lista de um provedor não mexe no botão de outro | R2 | rastreio temporal | Feature (`Kit`) | idem | M11 |
| CT-05 | o botão aparece no painel autorizado e desaparece nos outros | R3 | matriz provedor × painel | Feature (`Kit`) | idem | M12, M13, M19 |
| CT-06 | lista vazia continua oferecida nos três painéis | R3 | partição da lista | Feature (`Kit`) | idem | M19, M3 |
| CT-07 | `@premissa` a tela de registro obedece à escolha, nas duas metades | R3 | matriz provedor × superfície | Feature (`Kit`) | idem | M14, M15 |
| CT-08 | o link declara o painel de origem, com lista vazia e restrita | R3 | partição do painel corrente × lista | Feature (`Kit`) | idem | M16, M17, M18, M13 |
| CT-08b | o painel no link não derruba `org` nem `token` do convite | R3 | rastreio de efeito na junta tela → rota | Feature (`Kit`) | idem | M51 |
| CT-09 | o painel pedido na requisição decide se a ida segue | R4 | partição exaustiva da query | Feature (`Kit`) | idem | M20, M22, M23, M24 |
| CT-10 | `@premissa` painel inexistente não é recusa | R4 | partição inválida da query | Feature (`Kit`) | idem | — (bloqueado por A6) |
| CT-11 | a recusa não grava o contexto de cadastro | R4 | não-efeito | Feature (`Kit`) | idem | M24 |
| CT-12 | a barreira consulta a lista daquele provedor | R4 | matriz provedor × painel | Feature (`Kit`) | idem | M21, M20, M22 |
| CT-13 | `@do-plano` a recusa é registrada com provedor e painel | R4 | rastreio de efeito | Feature (`Kit`) | idem | M25 |
| CT-14 | o destino do sucesso é o painel de origem | R5 | partição do painel de origem × lista restrita | Feature (`Kit`) | idem | M26, M28, M31 |
| CT-15 | o painel de origem não vira permissão | R5 | matriz papel × painel | Feature (`Kit`) | idem | M30 |
| CT-16 | o painel na sessão não apaga `org` nem `token` | R5 | rastreio de efeito | Feature (`Kit`) | idem | M29 |
| CT-17 | com tenancy, entrar pelo `/admin` não resolve organização — no sucesso e no perfil | R5 | partição de plataforma × desfecho | Feature (`Tenancy`) | `tests/Tenancy/LoginSocialPorPainelTenancyTest.php` | M26 (ramo com tenancy), M53 |
| CT-18 | a volta que não é sucesso também volta ao painel de origem | R5 | tabela desfecho × painel de origem | Feature (`Kit`) | `tests/Kit/LoginSocialPorPainelTest.php` | M27, M28 |
| CT-19 | sessão sem painel válido cai no default, autenticando | R6 | partição do estado da sessão | Feature (`Kit`) | idem | M32, M33, M34 |
| CT-20 | `@premissa` a autorização retirada no meio não vira 404 | R6 | janela temporal | Feature (`Kit`) | idem | M35 |
| CT-21 | um save grava dois provedores sem mexer nos irmãos | R7 | gravação por componente + não-efeito | Livewire | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M37, M38, M41, M42 |
| CT-22 | cada um dos quatro provedores tem escolha que governa | R7 | matriz provedor × propriedade + rastreio de ligação | Livewire + Feature | idem | M36, M37, M41 |
| CT-23 | esvaziar devolve o provedor aos três painéis | R7 | edição + partição | Livewire + Feature | idem | M39, M43 |
| CT-24 | painel gravado inexistente não trava a tela | R7 | valor fora do domínio do campo | Livewire | idem | M40 |
| CT-25 | o campo aceita exatamente os painéis registrados | R7 | partição do domínio do campo | Livewire | idem | M38 |
| CT-25b | as opções são os painéis registrados, não uma lista à mão | R7 | âncora contra a outra fonte da verdade | Livewire | idem | M44 |
| CT-26 | a coerção da lista no próprio arquivo de config | R8 | partição do valor do `.env` | Feature (`Kit`) | `tests/Kit/LoginSocialPorPainelTest.php` | M45, M46, M47 |
| CT-27 | cada provedor tem a sua chave e a sua coerção | R8 | matriz provedor × coerção | Feature (`Kit`) | idem | M46, M47, M48, M49 |
| CT-28 | de fábrica o provedor vale nos três painéis | R8 | default sem arranjo | Feature (`Kit`) | idem | M45, M3 |
| CT-29 | a instalação que só roda migrate não perde o provedor | R8 | rastreio semeadura → alinhamento → decisão | Feature (`Kit`) | idem | M50 |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| a posição do botão abaixo do formulário, o ícone e as cores da marca, por painel | já provado por `LoginSocialProvedoresTest` CT-08; nenhum mutante desta wiki morre ali |
| a rota de **volta** respondendo 404 por painel não autorizado | contraria a ADR-05; o observável da decisão está em CT-20, marcado `@premissa` |
| o divisor "ou" desaparecer quando o painel não autoriza nenhum provedor | mata o mesmo mutante que CT-05 (o laço vazio), e `LoginSocialProvedoresTest` CT-09 já cobre o divisor |
| `disponiveis(null)` continuar devolvendo os quatro provedores | é linha de CT-01, na camada mais barata; um cenário próprio não acrescenta mutante |
| forjar `?painel=` na rota de **confirmação de vínculo** | o `01` não passa painel por ali, e a autorização daquele fluxo é o token assinado; sem cláusula no `00`, seria cenário inventado |
| dois cliques na ida com painéis diferentes (idempotência do contexto) | não mata mutante: o valor correto do contexto **é** o do último clique, então o cenário é tautológico. Registrado no checklist |
| o `helperText` e o rótulo do campo na tela | recusado como oráculo (ver A10); afirmar texto do PRD é testar o PRD |
| as quatro linhas de CT-18 replicadas em `tests/Tenancy` | provariam o mesmo `hasTenancy()` quatro vezes. O ponto que importa ali — o perfil, o único cujo erro dá 500 — passou a ser a segunda linha de CT-17, e o resto é decisão de escopo declarada |
| um cenário de browser para o campo de seleção múltipla | o gate do `05` não passa — ver `## Sem CT-B` |

## Sem CT-B

**O gate do `05-casos-de-teste-browser.md` não passa, e o arquivo não foi criado.**

Motivo: toda afirmação deste conjunto é sobre presença ou ausência de texto no HTML, status HTTP,
o `Location` de um redirecionamento, o conteúdo do contexto na sessão, as opções de um campo ou o
valor gravado no settings. Nenhuma delas depende de **JavaScript executado, console limpo,
acessibilidade, cor ou layout** — as únicas coisas que só o navegador prova. O campo novo é um
`Select` múltiplo do Filament, e validação, gravação e notificação de formulário são teste de
componente Livewire, em milissegundos.

É a mesma conclusão que o `01-plano-acao.md` registra na `## Superfície de UI` ("**Gate de CT-B**:
não passa"), alcançada de forma independente aqui.

## Pós-implementação

- [ ] `php artisan test --compact tests/Kit/LoginSocialPorPainelTest.php`
- [ ] `php artisan test --compact tests/Kit/LoginSocial*Test.php tests/Kit/VinculoDeProvedorSocialTest.php tests/Kit/CadastroSocialPorConviteTest.php` — a regressão das três wikis ancestrais
- [ ] `php artisan test --compact tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/ConfiguracoesDoKitTelaTest.php`
- [ ] `php artisan test --compact --testsuite=Tenancy`
- [ ] `vendor/bin/pest tests/Kit/LoginSocialPorPainelTest.php --mutate --path=app/Support/ConfiguracaoDoLogin.php`
- [ ] `vendor/bin/pest tests/Kit/LoginSocialPorPainelTest.php --mutate --path=app/Http/Controllers/Auth/LoginSocialController.php`
- [ ] cada mutante sobrevivente traduzido de volta em **lacuna de derivação** e convertido em
      cenário novo — e não em `covers()` ajustado para o score subir
- [ ] o `## Índice de Cenários` atualizado com o nome real de cada caso

`pestphp/pest-plugin-mutate` está declarado direto no `composer.json` (`^5.0`, linha 94), não como
dependência transitiva — conferido. `--mutate` exige driver de cobertura, e `--path=` é o filtro
confiável; `--class=` pode não casar.

> **O mutation score é piso de qualidade de assertion, não medida de cobertura de requisito.**
> Ele é estruturalmente cego à omissão: se a terceira condição de `disponivel()` nunca for
> escrita, não há nada para mutar e o score não cai. Quem responde por omissão aqui é a tabela
> `RQ → regra` do `## Mapa de Regras` e os mutantes de **especificação** deste arquivo, que
> nasceram do `00` e não do código. Esta rodada é a prova do argumento: os oito achados da revisão
> adversarial são todos **comportamento ausente** — a tela de registro sem célula válida, a chave
> de config ausente, três dos quatro provedores sem propriedade, cinco dos seis pontos de destino,
> a semeadura da migration. Nenhum deles geraria mutante.

## Revisão Adversarial

Executada por sub-agente independente, que recebeu **apenas** o `00-requisito.md` e este arquivo
— sem o `01`, sem as ADRs, sem o código e sem o raciocínio de quem derivou. Contrato: *provar que
este conjunto deixa passar um defeito*, com proibição explícita de elogiar, de reescrever cenário
e de sugerir implementação. **Duas entregas**, 24 achados, todos fechados abaixo.

### Lacunas de cobertura (9 na primeira entrega) — cada uma virou cenário

| # | Achado | O que virou |
|---|---|---|
| RA-01 | CT-07 afirmava só *"a tela de registro não traz o botão"*. Uma tela de registro que não oferece **provedor nenhum** — o dev que conclui "registro não tem painel de origem para propagar" — ficava verde, quebrando o cadastro social ancestral | CT-07 ganhou a **célula válida**: o provedor autorizado no `app` continua oferecido, com o link correto. Mutante novo M15 |
| RA-02 | R5 exercitava só o **desfecho de sucesso**. A implementação que parametriza apenas `urlDoPainel()` e deixa os outros cinco `getPanel('app')` passava em tudo — e é o incidente que a wiki do Google já pagou | **CT-18** (novo), tabela desfecho × painel de origem, com `E o caminho não começa por "/app/"`. O antigo M23 foi desdobrado em **M26** e **M27** |
| RA-03 | o rastreio settings → decisão existia só para o **Google**, e a gravação amostrava três provedores. Uma propriedade (ou linha do mapa) faltando para o GitHub passava em tudo, porque os cenários com GitHub arranjam por `config()->set` | **CT-22** virou `Esquema` com os **quatro** provedores. Mutante novo M41 |
| RA-04 | nenhum cenário afirmava o **não-efeito nas propriedades irmãs**; um `save()` que reescreve o bloco inteiro apagava a escolha do vizinho em silêncio | **CT-21** (novo): dois provedores num único `save()` + as duas propriedades irmãs intactas. Mutante novo M42 |
| RA-05 | CT-08 media o link só com a lista **vazia** | duas linhas de **lista restrita**. Mutante novo M18 |
| RA-06 | CT-27 usava o valor `"admin"` — sem espaço, sem vírgula dupla, sem vazio: a única linha que **não discrimina nada**. M46/M47 sobreviviam em três dos quatro provedores | o valor de CT-27 passou a ser `" admin, app ,"` |
| RA-07 | `esvaziar = todos` era provado num painel só; "vazio → painel **default**" ficava verde | CT-23 ganhou os três painéis no `Então`. Mutante novo M43 |
| RA-08 | A4 não era falsificável do lado do **campo**: uma lista de painéis escrita à mão passava em tudo e pararia de reconhecer painel novo | **CT-25b** (novo), comparando as opções com o registro do Filament. Mutante novo M44 |
| RA-15 | o default era medido no arquivo de config e na instalação **nova**; o valor que a **migration semeia** não era medido em lugar nenhum. `['app']` na semeadura é a tentativa honesta de "não mudar comportamento" e apagaria o Google do `/admin` em toda instalação existente | **CT-29** (novo). Mutante novo M50, e M45 teve o alcance reduzido ao `config/kit.php` |

*(O revisor também produziu o achado da chave de config **ausente** — a instalação cujo
`config/kit.php` é anterior ao update, caso normal num starter kit onde `config/` pertence a quem
instalou. Duas linhas novas em CT-01 e o mutante M7.)*

### Matadores declarados que NÃO matavam (3) — o achado mais valioso

| # | Achado | Correção |
|---|---|---|
| RA-09 | **M4** (`$painel === null` resolve o painel default) estava declarado morto pela última linha de CT-01, que era `lista=["app"]` / `painel=nulo` / `true`. O painel default **é** o `app`: o mutante devolvia `true` e sobrevivia. Erro idêntico ao que a lacuna L1 documenta — valor escolhido sem conferir se discrimina | as duas últimas linhas de CT-01 passaram a usar `["admin"]` e `["infra"]`, listas que **não** contêm o painel default |
| RA-16 | **M45** (default `["app"]`) estava declarado morto por CT-26 e CT-28, que medem só a camada de config; a variante alojada na semeadura sobrevivia aos dois | alcance de M45 reduzido, variante da semeadura promovida a **M50** com CT-29 |
| RA-17 | **M23** ("o destino continua fixo") estava declarado morto por CT-14 e CT-17, que o matam **em 1 dos 6 pontos** | desdobrado em **M26** (sucesso) e **M27** (os outros cinco), com CT-18 |

### Oráculos fracos e passos compostos (9) — reforçados no lugar

- **RA-10** — CT-19 não afirmava `E ela está autenticada`: a implementação que manda a pessoa ao
  painel default **sem abrir sessão** ficava verde. Acrescentado, e a asserção redundante ("a
  resposta não é erro de servidor") foi removida — num 500 não há `Location`.
- **RA-11** — o não-efeito de contexto estava isolado em CT-11, que roda uma query só; as linhas
  `?painel=infra` de CT-09 ficavam sem ele. CT-09 ganhou a coluna `contexto`, com a metade
  positiva na linha válida.
- **RA-12** — CT-13 afirmava só o `motivo`: um `warning` genérico de qualquer recusa passava.
  Acrescentados o provedor e o painel.
- **RA-13** — CT-17 prometia no título "não tenta resolver organização" sem nenhum `Então` de
  não-efeito, e sem o 200 que CT-14 tem. Os dois acrescentados.
- **RA-14** — a linha inválida de CT-25 tinha `gravado = o valor anterior`, que numa criação é a
  lista vazia — indistinguível de "acusou erro **e** gravou vazio". O `Dado` passou a gravar
  `["app"]` antes.
- **RA-18** — CT-28 tinha `Quando` composto (abrir a tela **e** pedir a rota). A tela virou
  `Dado`; o evento é a rota.
- **RA-19** — CT-20 tinha `Quando` composto (mudar a configuração **e** completar a volta). A
  mudança virou `Dado` — rearranjo não é evento.
- **RA-20** — a terceira linha de CT-05 (`"infra"`) não discrimina nada que a linha `"app"` já
  não discrimine. Mantida e **declarada como apoio** (exaustividade de A4), em vez de contada
  como matadora.
- **RA-21** — R5/R6 fixavam a lista em "vazia". CT-14 passou a usar lista **restrita ao painel da
  linha**, que é estritamente mais forte. Mutante novo M31.

### A segunda entrega, e o que ela mudou

O revisor entregou **duas vezes** — a segunda quando lhe foi pedida a lista final no formato
combinado. As duas entregas convergiram nos 20 achados acima, o que é evidência de que eles não
são ruído. E a segunda trouxe **três achados novos**, todos fechados:

| # | Achado | O que virou |
|---|---|---|
| RA-22 | a tabela de decisão de R1 declarava "nenhuma linha colapsada" e **colapsava a condição 2 numa das três credenciais**: só o `client_secret` tinha linha. O `01` **reescreve** essa condição de `filled()` para `blank()`, então a reescrita que preserva só o `secret` é o mutante mais plausível da regra | duas linhas novas em CT-01 (`client_id` vazio e `redirect` vazio). Mutante novo **M52** |
| RA-23 | CT-16 prova que o painel **na sessão** não apaga `org`/`token`, mas forja a query à mão — nunca vê o link que a tela monta; CT-08 mede o link com contexto vazio. O mutante "o link substitui a query em vez de somar" sobrevivia aos dois, e quebra o convite por organização em silêncio | **CT-08b** (novo). Mutante novo **M51** |
| RA-24 | CT-17 exercitava com tenancy só o destino de **sucesso**; `urlDoPerfil()` — o único dos seis pontos cujo erro produz **500** e não um destino errado — não era alcançado, e estava registrado como lacuna **L3** | CT-17 virou `Esquema` com a linha de perfil incompleto. **L3 fechada**, com a prova de discriminância escrita. Mutante novo **M53** |

O teto da skill é 2 rodadas e ele foi respeitado. A segunda entrega não trouxe achado
**estrutural** — nenhuma regra precisou ser desdobrada em duas, e nenhum dos três achados novos
nasceu de superfície criada pelo fechamento da primeira. Encerrado aqui.

**Contrapartida honesta, e é a lição desta rodada**: os três matadores falsos (RA-09, RA-16,
RA-17) e o colapso da condição 2 (RA-22) foram achados por um revisor externo, e **nenhum deles
seria pego por `pest --mutate`** — os quatro são erros de *declaração* e de *omissão*, não de
código. A revisão adversarial pagou-se quatro vezes só aí. Se o `feature-quality-gate` achar um
quinto, a conclusão não é "faltou rodada": é que a tabela de mutantes desta skill precisa de
revisão por par como etapa própria, e isso é candidato a linha no `.ai/rules/` do projeto.
