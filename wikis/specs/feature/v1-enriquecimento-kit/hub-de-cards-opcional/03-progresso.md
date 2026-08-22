# Progresso — Hub de cards fora do padrão da instalação

> Wiki: `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/`
> Branch: `feature/v1-enriquecimento-kit` · Ancestral: `wikis/specs/main/hub-de-navegacao-em-cards/`

## 1. A chave de config

- [x] `config/kit.php` — bloco `hub` depois de `demo`, com o comentário que registra por que o
      `/infra` não depende dela

## 2. O `.env.example`

- [x] `KIT_HUB=false` com o comentário de três linhas, abaixo do bloco `KIT_DEMO`

## 3. O `phpunit.xml`

- [x] `<env name="KIT_HUB" value="false" force="true"/>` ao lado de `KIT_DEMO`

## 4. O hub de `/admin` atrás da flag

- [x] `HubDeAdministracao::canAccess()` — só `canAccess()`, sem `shouldRegisterNavigation()`
- [x] docblock do método explicando por que um método basta em Page (e dois em Resource)
- [x] comentário de `AdminPanelProvider.php:197` menciona a flag

## 5. O hub de `/app` atrás da flag

- [x] `HubDoNegocio::canAccess()`
- [x] a seção "Esta página NÃO entra na lista de subtração do `panel_user`" **preservada**, com a
      frase nova sobre a flag não mexer na permissão
- [x] comentário de `AppPanelProvider.php:279` menciona a flag

## 6. O hub de `/infra`: a exceção declarada, e as descrições

- [x] **nenhum** `canAccess()` acrescentado — a ausência é a decisão
- [x] parágrafo no docblock da classe: por que esta é a única fora da flag
- [x] `descricoesDosDestinos()` com os 16 FQCN e as 16 frases
- [x] `getCards()` passa `descricoes:`
- [x] cada frase conferida contra o destino real (não escrita de memória)

## 7. O trait aceita descrição

- [x] `cardsDoPainel(array $excluir = [], array $descricoes = [])`
- [x] `cardDe(string $componente, ?string $descricao = null)` → `->description($descricao)`
- [x] PHPDoc dos dois parâmetros, com `array<class-string, string>`
- [x] parâmetro **opcional** — as outras duas Pages não passam nada

## 8. A captura de tela do hub

Reescrito na implementação: a captura **não** é um cenário da suíte de arte, é o próprio CT-B02.
Ver ADR-05 (reescrita) e "Notas de Implementação".

- [x] ~~cenário em `tests/BrowserTenancy/CapturaDeArteTest.php`~~ — **abandonado**: a suíte de arte
      atravessa painéis e a captura saía com a barra lateral errada
- [x] CT-B02 em `tests/Browser/HubDeCardsTest.php` ganha `resize(1400, 875)` e grava `infra-hub`
- [x] o `->screenshot()` do CT-B01 removido — fotografava a mesma tela com outro nome
- [x] `beforeEach` do arquivo aquece **só** o `/infra` pelo kernel (o `composer art` roda o arquivo
      isolado, e sem isso o primeiro cenário estoura os 45 s)
- [x] `composer.json` → script `art` invoca também `tests/Browser/HubDeCardsTest.php`
- [x] `composer art` executado — duas invocações, 4 + 2 cenários, todas verdes
- [x] `art/infra-hub.png` e `art/thumbs/infra-hub.png` publicados — 1400x875 e 760x475, batendo com a galeria
- [x] **a imagem foi aberta e olhada** — cartões com frase legível, painel certo na barra lateral — ✅ barra lateral do `/infra`, uma topbar, frases legíveis em 3 colunas, sem transbordo

## 9. A receita

- [x] `wikis/receitas.md` §"Página hub de cards": estado atual (o `/infra` nasce ligado, os outros
      dois são `KIT_HUB=true`) no topo da seção
- [x] quinto caso de uso: **página de fluxo**
- [x] subseção "Descrição nos cartões": o mapa por FQCN, por que não é método na classe do destino,
      e que a frase entra na busca
- [x] a imagem (thumb com link para o cheio) no topo da seção
- [x] em "O que NÃO fazer": ligar o hub em painel com poucos destinos

## 10. O `pacotes.md` e o CHANGELOG

- [x] `wikis/pacotes.md:36` — a linha do pacote ganha o estado (ligado no `/infra`, opt-in nos outros)
- [x] `CHANGELOG.md` — entrada de mudança de comportamento para quem vai rodar `kit:update`
- [x] **`README.md` e `README.en.md` fora do escopo** — corte da auditoria Ponytail; o README nunca
      mencionou o hub, e RQ-05 pede a imagem "como opções de uso", que é papel do `receitas.md`

## 11. Regressão obrigatória

- [x] CT-01 da ancestral (`tests/Kit/HubDeCardsTest.php:78`) — arranjo ganha a flag
- [x] CT-02 da ancestral (`tests/Tenancy/HubDoNegocioTest.php:44`) — arranjo ganha a flag
- [x] CT-03 da ancestral (`tests/Kit/HubDeCardsTest.php:32`) — só as linhas de `/admin`
- [x] CT-04 da ancestral (`tests/Tenancy/HubDoNegocioTest.php:79`) — **nada muda**, confirmar verde
- [x] CT-05 da ancestral (`tests/Kit/HubDeCardsTest.php:53`) — nada muda, roda no `/infra`
- [x] CT-B01 (`tests/Browser/HubDeCardsTest.php`) — nada no arranjo, confirmado verde na suíte completa
- [x] nenhuma assertion afrouxada; ajuste **só** de arranjo

## Testes

- [x] `tests/Kit/HubDeCardsTest.php` — CT-01 (linhas `/admin`), CT-02, CT-05, CT-06, CT-07,
      CT-08, CT-11, CT-12
- [x] `tests/Tenancy/HubDoNegocioTest.php` — CT-01 (linha `panel_user` / `/app`)
- [x] `tests/Browser/HubDeCardsTest.php` — CT-B02 (segundo `it()`, ao lado do CT-B01 existente)

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — nada a cortar; ver abaixo
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --compact --filter=Hub`
- [x] `php artisan test --compact --filter=PainelAppVazio` — 4 verdes
- [x] `composer test:browser` (embute `npm run build` e `view:cache`; em série)
- [x] `vendor/bin/pest --parallel --group=kit` — nada mais no suite quebrou
      (**não** `--parallel --tia`: ver a divergência declarada no `04`)
- [x] `php artisan config:show kit.hub` devolve `false` num `.env` limpo
- [x] roteiro "Desenhado × Implementado" do `05-casos-de-teste-browser.md` preenchido — 7 linhas, 6 ✅ e 1 ⚠️ (tema escuro, LB1)
- [x] `git commit` — cinco commits, na ordem do passo 12 do PRD; commitado e mergeado em `main` (`git branch --no-merged main` vazio)

## Auditoria Pré-Implementação

<!-- Saída dos steps 5 e 6 da feature-wiki, ANTES de escrever código. -->

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa inicial | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "gate por `canAccess()` **e** `shouldRegisterNavigation()`, como `ProjetoResource`" | `Page::registerNavigationItems()` já retorna cedo com `canAccess()` falso — `vendor/filament/filament/src/Pages/Page.php:133-135`. Em **Page** um método basta; em **Resource** são dois | PRD, passos 4 e 5: um método só; a diferença Page × Resource ficou escrita no docblock |
| "a descrição vai exigir CSS novo no `cards.css`" | as três classes que a blade emite já estão cobertas — `cards.css:114`, `:120`, `:141`. A única ausente é `text-start`, inócua em LTR | PRD, `## Análise dos Arquivos Existentes`: o arquivo **não** é tocado; e o corte foi registrado na `## Filosofia de Implementação` |
| "gatear o `Css::make('kit-cards')` do `KitServiceProvider` pela flag" | o `/infra` continua com hub, então o `if` nunca ficaria falso | PRD: o provider **não** é tocado |
| "existem prints/thumbs do hub para reaproveitar" | `art/` e `art/thumbs/` não têm nenhum. As capturas de painel são **anteriores** ao hub — conferido abrindo `art/thumbs/panel-admin.png`: a barra lateral não tem o item | `00-requisito.md`, RQ-05: a condicional "se houver" nasce falsa; o usuário converteu a cláusula em imperativa |
| "o screenshot do CT-B01 serve para a galeria" | ele grava sem `resize()`, e o `kit:arte` publica **todo** PNG do diretório (`KitArte.php:79-100`) — colisão de nome tornaria a imagem publicada dependente de qual suíte rodou por último | ADR-05 + PRD, passo 8: nome `infra-hub` |
| "descrição só nos destinos de rótulo obscuro" | dos 16 destinos, 8 têm rótulo de vendor não traduzido e 8 são autoexplicativos; descrever metade produz cartões de duas alturas | `00-requisito.md`, premissa de RQ-07: todos os 16 |
| "os FQCN do painel `/infra` podem ser lidos do provider" | o provider registra **plugins**, não classes; as Pages e Resources vêm de `discoverPages()` e dos plugins. A lista real só sai do painel em runtime | os 16 FQCN foram levantados por `php artisan tinker` sobre `Filament::getPanel('infra')` |

### Auditoria Ponytail (step 6)

<!-- Preencher após rodar /ponytail:ponytail-review sobre os arquivos desta wiki. -->

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | **CT-04 é tautológico** — `HubDeAdministracao` não é destino do painel `/infra`, logo o `assertDontSee` fica verde com a flag inteira removida | **sim** | `04`, cenário removido; motivo em `## Cogitado e cortado` |
| 2 | CT-03 (barra lateral) não mata mutante próprio — `Page.php:133-135` garante os dois efeitos juntos | **sim** | `04`, virou a última assertion do CT-01 |
| 3 | CT-09 assere o `data-search-text`, que a blade do **vendor** preenche | **sim** | `04`, cenário removido |
| 4 | CT-10 duplica o CT-02 (mesma tela, mesma persona, mesma flag) | **sim** | `04`, virou a última assertion do CT-02 |
| 5 | edições em `README.md` e `README.en.md` — dois arquivos espelhados para uma feature desligada por default, num README que nunca mencionou o hub | **sim** | `01`, passo 10 |
| 6 | `descricoesDosDestinos()` tem um chamador só — inlinar no `getCards()` | **recusada**: o docblock é a razão de ADR-04 no ponto de uso, e inlinar leva `getCards()` de 4 para 25 linhas | `01`, passo 6 |

**Resultado**: 12 cenários → **8**; 13 arquivos a tocar → **11**. Nenhum mutante ficou sem matador
por causa dos cortes — os quatro cenários cortados ou não matavam nada, ou matavam o que outro
cenário já mata.

### Derivação de testes (step 4, `feature-test-design`)

| O que a derivação devolveu | Destino |
|---|---|
| o **texto** das descrições não está no requisito — só no PRD | pergunta em `00-requisito.md`; CT-07 marcado `@premissa` com uma string literal como canário |
| "cada card" obriga a acusar cartão futuro sem frase? **CT-08 conflita com ADR-04** | pergunta em `00-requisito.md`, **decisão pendente do usuário**; CT-08 marcado `@premissa` |
| RQ-02 e RQ-06 não são integralmente verificáveis | lacuna declarada em `00-requisito.md` e no `04`; CT-12 cobre a parte verificável |
| RQ-01 não gera código — precisa de caso que **proíba** a remoção | CT-11 (R6) |
| ADR-03 precisa de caso que fique vermelho se a flag chegar ao `/infra` | CT-05, linha `desligada` (R2) |
| ADR-02 precisa de caso que proteja a matriz de permissões | CT-06 (R3) |
| duas lacunas de derivação declaradas | **L1** (segunda chave de config para o `/infra`) e **L2** (chave órfã no mapa) |
| uma lacuna de browser declarada | **LB1** (posição e pintura da frase — só screenshot e olhar) |
| divergência skill × rule do projeto | `--parallel --tia` recusado; a rule `.ai/rules/testes-browser.md` venceu, e a divergência está escrita no `04` |

## Blockers

- [x] **Decisão do usuário sobre o CT-08 — resolvida em 2026-08-21: MANTER.** "Sim, nenhum card
      fica sem descrição, logo todos devem ter." O caso está implementado como
      `it('não deixa nenhum cartão do hub de infraestrutura sem descrição')`, com mensagem de falha
      que nomeia a classe faltante. A tensão com ADR-04 fica registrada: a cláusula do requisito
      venceu a ADR, e o vermelho a cada plugin novo é comportamento pedido, não falso positivo.

## Desvios do Plano

- **`CHANGELOG.md`: convenção do projeto ≠ passo do PRD.** O passo 10 mandava acrescentar a entrada.
  Ao executar, o `git log -- CHANGELOG.md` mostrou que o arquivo **só muda em commits
  `:bookmark: bump version`** — nenhuma das features recentes deste branch (import-export, anexos
  privados, arte) o tocou. Registrei o desvio e **o usuário pediu para atualizar de todo modo**, na
  mesma conversa: a entrada foi escrita numa seção `## [Não lançado]`, que é o que o próprio
  cabeçalho do arquivo (Keep a Changelog) prevê e que o projeto ainda não usava.

- **A assertion "nenhum cartão da grade tem descrição" saiu do CT-02.** O `04` a previa como o que
  restou do CT-10 cortado. Ao implementar, ela se mostrou inexpressável de forma honesta por HTTP: o
  hub de `/admin` tem `$searchable = true`, então todo cartão traz `data-search-text`, e distinguir
  "sem descrição" de "com descrição" exigiria fatiar o atributo de cada cartão. Os dois mutantes que
  ela mataria (parâmetro obrigatório no trait; `$descricoes[$componente]` sem `??`) morrem **do mesmo
  jeito** no CT-02 como está: o primeiro é `TypeError` e o segundo é `ErrorException` — a tela nem
  responde 200. Assertion que não distingue nada foi cortada em vez de mantida como enfeite.

- **`ExceptionResource` e `DependencyGraphPage` ficam no mapa sem aparecer como cartão hoje.**
  Levantei a lista com o painel corrente definido e sem, e os dois só entram na navegação num dos
  casos: o `DependencyGraphPage` tem `shouldRegisterNavigation()` falso, e o `ExceptionResource`
  resolve o plugin pelo painel corrente. As duas chaves ficaram no mapa de propósito — chave que não
  casa nunca é lida, e removê-las deixaria o cartão sem frase no dia em que a navegação deles voltar.

## Notas de Implementação

- **A suíte de arte publicava captura com a barra lateral do painel ERRADO — defeito
  pré-existente, encontrado ao olhar a imagem.** O `beforeEach` aquece `/app` e `/admin` por
  `$this->get()`, e o servidor do `pest-plugin-browser` roda **in-process**: o `FilamentManager` e o
  `AssetManager` guardam estado de painel e o do último aquecimento é o que a tela seguinte
  renderiza. A primeira `art/infra-hub.png` saiu com título "Central de infraestrutura" e navegação
  "Projetos / Convites / Usuários", e os ícones da topbar todos iguais.

  **Não é defeito desta feature**: `art/admin-papeis-import-export.png`, publicada no commit
  `04642b0`, tem o mesmo sintoma. Nenhum teste ficou vermelho porque `assertSee` acha o texto que o
  cenário pede e a barra lateral não é afirmada por ninguém — é o `assertSee` como oráculo único que
  `.ai/rules/testes-browser.md` já avisa não bastar.

  **Isto foi encontrado por conferência visual, não por assertion** — exatamente o item
  "a imagem foi aberta e olhada" da `## Verificação Final`. Sem ele, a wiki teria fechado verde com
  a imagem errada na documentação.

- **Duas tentativas erradas de corrigir o vazamento da captura, e o que elas ensinaram.**

  **Tentativa 1** — `fronteiraDeRequest()` no fim do `beforeEach`. Derrubou os três cenários que
  criam `Projeto`: `SQLSTATE[23000] NOT NULL constraint failed: projetos.tenant_id`. O helper esquece
  o `FilamentManager` e com ele `Filament::getTenant()`, que é de onde o `BelongsToTenant` tira a
  coluna. 3 vermelhos, 2 verdes.

  **Tentativa 2** — a mesma chamada movida para antes de cada `visit()`, depois da criação. Os
  cenários voltaram, e a captura saiu **pior**: topbar duplicada (dois campos de busca, dois badges)
  e a barra lateral ainda do painel errado. Ou seja: a hipótese sobre o mecanismo estava errada, não
  só o lugar da chamada.

  **As duas foram revertidas** (`git checkout -- tests/BrowserTenancy/CapturaDeArteTest.php` e
  `art/`), junto com o cenário de captura do hub. O `art/` voltou ao estado do commit — imagem errada
  na árvore de trabalho é pior que imagem ausente.

  **A medição que devia ter vindo primeiro**: o CT-B02 vive em `tests/Browser`, que **não** aquece
  outros painéis. A captura dele sai limpa — 14 cartões com descrição, quatro grupos na ordem, barra
  lateral do `/infra`, uma topbar. Isso prova duas coisas de uma vez: **a feature está correta**, e o
  defeito de barra lateral é **inteiramente** do aquecimento cruzado da suíte de arte, que é
  pré-existente (`art/admin-papeis-import-export.png` está assim desde `04642b0`).

  A lição, e ela é a mesma dos dois erros: eu apliquei a correção antes de ter o experimento que
  isolava a causa. O experimento custava um arquivo de teste que eu **já tinha escrito**.

- **O `ResizeObserver loop completed with undelivered notifications` não é da feature.** O CT-B02
  falhou com ele ao rodar o arquivo isolado, e a suspeita era plausível: cartão de uma linha virou
  cartão de três, e layout thrash produz exatamente esse aviso. **Na suíte completa não reproduz** —
  35 testes, 28 verdes, CT-B01 e CT-B02 entre eles. Não virou rule nem débito: é ruído de execução
  isolada.

- **`vendor/bin/pest --parallel --group=kit`: 517 testes, 516 verdes.** O único vermelho no
  repositório inteiro é o CT-12, pela imagem ausente. A seção `## Impacto em Features Existentes` do
  PRD previu quatro pontos de risco — Spotlight, cartões dos outros hubs, os casos da ancestral e o
  `PainelAppVazioTest` — e nenhum deles quebrou. Previsão confirmada, sem divergência a registrar.

- **Suíte de browser: 35 testes, 28 verdes, 6 skipped (a arte, sem `KIT_ART`), 1 vermelho.** O
  vermelho é `CabecalhoDoMenuDoUsuarioTest` com `Timeout 45000ms exceeded` — o caso que
  `.ai/rules/testes-browser.md` documenta como medidor de cache frio, sem relação com esta entrega.

## Auditoria Ponytail do diff (pós-implementação)

520 linhas inseridas, e o que é **código executável** são ~30:

| Arquivo | Código | Resto |
|---|---|---|
| `config/kit.php` | 1 linha | bloco de comentário no formato das outras 9 chaves do arquivo |
| as duas Pages com flag | 4 linhas (2 métodos) | docblock |
| `DescobreCardsDoPainel` | 2 linhas (parâmetro + repasse) | PHPDoc |
| `HubDeInfraestrutura` | 16 do mapa + 5 do `getCards()` + imports | docblock |
| providers (3) | **0** | só comentário |
| `composer.json`, `phpunit.xml`, `.env.example` | 1 linha cada | — |

Nenhuma abstração especulativa: sem interface de uma implementação, sem factory, sem config que
ninguém mexe, sem camada de um chamador. O único parâmetro novo (`descricoes`) tem chamador real e é
opcional. Os quatro cortes que a auditoria da wiki já havia feito (step 6) seguem valendo, e mais
três apareceram durante a implementação e foram aceitos: nenhum CSS novo, nenhum gate no
`KitServiceProvider`, e a assertion do CT-02 que não distinguia nada.

**Um ponto de duplicação assumido**: o docblock de `HubDeAdministracao::canAccess()` tem 11 linhas
para um método de uma, e o mesmo conteúdo agora vive em `.ai/rules/filament.md`. Mantido porque as
audiências são diferentes — a rule alcança o agente que edita `app/Filament/**`, o docblock alcança
quem abre a classe — e porque encurtá-lo faria dele o único docblock magro de um arquivo onde todos
são densos. O `HubDoNegocio`, que teria o mesmo texto, **aponta para o irmão em vez de repetir**.

## Débito declarado

- **RQ-05 resolvida pela opção 1**, escolhida pelo mantenedor: a captura passou para
  `tests/Browser`, que não atravessa painéis e renderiza limpo, e o `composer art` ganhou a
  invocação daquele arquivo. Ver ADR-05, reescrita.

- **Fica em aberto, e não é desta entrega: o vazamento de painel da suíte de arte.** O `beforeEach`
  de `tests/BrowserTenancy/CapturaDeArteTest.php` aquece `/app` e `/admin`, e o estado de painel
  atravessa o servidor in-process — a tela sai com o cabeçalho de um painel e a barra lateral de
  outro. **`art/admin-papeis-import-export.png` está publicada assim desde o commit `04642b0`.**

  Não foi corrigido aqui porque: atinge quatro cenários alheios a esta feature, as duas correções
  óbvias falharam (ver "Notas de Implementação"), e o diagnóstico de verdade é trabalho próprio.
  Registrado em `.ai/rules/testes-browser.md` para quem for mexer naquele arquivo.

## Retrospectiva

<!-- Preencher ao final. -->

- **Funcionou bem**:
- **Faltou no plano**:

## Candidatos a Rule

Todos aprovados pelo mantenedor em 2026-08-21 e **gravados** via a tool `record-rule` do Boost.

| # | Rule | Arquivo | Origem |
|---|---|---|---|
| 1 | Em Page, `canAccess()` sozinho basta; em Resource são dois métodos | `.ai/rules/filament.md` | ADR-02 + o corte 1 da auditoria Ponytail |
| 2 | Nome de screenshot de CT-B nunca colide com nome de imagem de `art/` | `.ai/rules/testes-browser.md` | ADR-05 |
| 3 | Utilitária que blade de vendor emite precisa existir no CSS do kit | `.ai/rules/css-filament.md` (arquivo novo) | ADR-02 da wiki ancestral + ADR-04 desta |
| 4 | `fronteiraDeRequest()` entre o aquecimento e o `visit()` — e o lugar dela não é livre | `.ai/rules/testes-browser.md` | descoberta durante a implementação: o vazamento de painel na captura, e a correção posta no lugar errado |

O Boost regenerou o `.ai/rules/index.md`, que ganhou a linha
`resources/css/filament/** → .ai/rules/css-filament.md`.

**A rule 4 nasceu como duas** — uma sobre o vazamento, outra sobre o lugar da chamada — e foram
**fundidas numa seção só**. Duas seções sobre a mesma armadilha, no mesmo arquivo, é inflação de
contexto: toda edição em `tests/BrowserTenancy/**` pagaria o imposto duas vezes.
