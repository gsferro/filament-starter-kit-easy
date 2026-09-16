# Progresso — Laravel Data como padrão de DTO

> Tracking da feature. Checkbox só fecha com evidência inline
> (`- [x] {item} — {evidência}, {data}`).

## Wiki

- [x] `00-requisito.md` — texto verbatim + `RQ-01..RQ-07` + premissas `P1..P6`, 2026-09-15
- [x] `01-plano-acao.md` — PRD com 10 passos, alvos A1..A6 e a tabela de recusados, 2026-09-15
- [x] `02-decisoes-arquiteturais.md` — ADR-01 a ADR-08 + `## Superfície do Pacote`, 2026-09-15
- [x] `04-casos-de-teste.md` v1 — 21 CTs, 9 regras, 28 mutantes; derivado pela
      `feature-test-design` a partir do `00`, 2026-09-15
- [x] Revisão adversarial (rodada 1) — sub-agente independente provou **5 implementações erradas
      passando nos 21 cenários** e 21 lacunas; 5 mutantes estavam sem matador real, 2026-09-15
- [x] `04-casos-de-teste.md` v2 — todos os 21 achados fechados: **30 CTs, 9 regras, 43 mutantes,
      0 sem matador**; 9 cenários novos (CT-22 a CT-30), 13 oráculos reescritos, 2026-09-15
- [x] Revisão adversarial (rodada 2) — **veredito: não está pronto para implementar**. 5
      implementações erradas novas, 8 lacunas (L22–L29), 4 conflitos internos (1 bloqueante) e 7
      mutantes ainda sem matador real, 2026-09-15
- [x] **Escalado ao usuário** (teto de 2 rodadas atingido) — decisões tomadas em 2026-09-15:
      escopo cortado para **3 Data** e guarda com **raízes como dado**, 2026-09-15
- [x] `04-casos-de-teste.md` v3 — R2 e R5 divididas em R2a/R2b e R5a/R5b; **31 CTs, 10 regras,
      41 mutantes, 0 sem matador**; todos os achados das duas rodadas fechados ou eliminados pelo
      corte de escopo, 2026-09-15
- [x] `01-plano-acao.md` reconciliado com o corte — passos 4, 6 e 7 marcados **fora desta entrega**
      com o alvo preservado; impacto, riscos e rollback atualizados, 2026-09-15
- [x] Auditoria `/ponytail:ponytail-review` da wiki (step 6 da `feature-wiki`) — 3 cortes
      aplicados, registrados na tabela `Auditoria Ponytail (step 6)`, 2026-09-15
- [x] Aprovação do usuário antes de implementar — "implemente", 2026-09-15

## Implementação

### 1. Instalar o pacote e reservar o diretório

- [x] `composer require spatie/laravel-data` — 4.23.0 instalado, `composer.json`/`composer.lock`,
      2026-09-15
- [x] `app/Data` na lista de paths do `KitUpdate` — `KitUpdate::CAMINHOS_DO_KIT`;
      `tests/Kit/KitUpdateTest.php` 54/54, 2026-09-15
- [x] Config **nao** publicada (ADR-08) — nao existe `config/data.php` no repositorio, 2026-09-15

### 2. `VeredictoDoGuardrailData` — A1

- [x] Data criado com fábrica nomeada — `app/Data/Ia/VeredictoDoGuardrailData::de()`, com
      `#[WithCast(BooleanoFlexivelCast::class)]` em `seguro`, 2026-09-15
- [x] `GarantirPromptSeguroMiddleware` consumindo o Data, com o fail-open intacto —
      `classificar(): ?VeredictoDoGuardrailData`; o ramo "fora do schema" passou a registrar
      `acao: schema`, que antes seguia em silêncio, 2026-09-15
- [x] CT-05, CT-17 verdes — `tests/Kit/GuardrailsDtoTest.php` e `tests/Kit/LoginSocialDtoTest.php`,
      2026-09-15

### 3. `PerfilSocialData` — A2

- [x] Data criado, sem credencial no `bruto` (P6/ADR-04) — `PerfilSocialData::doSocialite()` remove
      as chaves de `CREDENCIAIS` do payload bruto, 2026-09-15
- [x] `LoginSocialController` consumindo — `app/Http/Controllers/Auth/LoginSocialController.php`,
      2026-09-15
- [x] CT-07, CT-08, CT-16, CT-21 verdes — 2026-09-15

### 4. `UsoDeTokensData` — A3 — **fora desta entrega**

- [—] Cortado na escalação; alvo preservado no `01` para a entrega seguinte

### 5. `ResultadoDoConviteEmMassaData` + `FalhaDoConviteData` — A4

- [x] Os dois Data criados; PHPDoc de shape removido das duas assinaturas — `app/Models/Convite.php`
      e `app/Filament/Concerns/ConvidaEmMassa.php` agora trocam `ResultadoDoConviteEmMassaData`,
      2026-09-15
- [x] CT-11, CT-12 verdes — `tests/Kit/ConviteEmMassaDtoTest.php`, 2026-09-15

### 6. `MarcaDaInstalacaoData` — A5 — **fora desta entrega**

- [—] Cortado na escalação; alvo preservado no `01`

### 7. `WidgetDoDashboardData` — A6 — **fora desta entrega**

- [—] Cortado na escalação: `widgets()` é contrato público da v0.33.0

### 8. O guarda do padrão

- [x] `app/Support/GuardaDoPadraoDeDto.php` com raízes padrão expostas como dado —
      `RAIZES_PADRAO = ['app', 'routes/api.php', 'app/Http/Resources']`, 2026-09-15
- [x] Mensagem discriminante por tipo de defeito (cita o motivo, não os outros) — CT-14 afirma linha
      a linha; `instanciaDataForaDeComentario()` passou a usar tokens `T_NEW` porque a regex casava
      os próprios docblocks da classe, 2026-09-15
- [x] `tests/Kit/DtoComLaravelDataTest.php` — CT-04 a CT-15, CT-20, CT-21 verdes, 2026-09-15
- [x] Fixtures em raiz temporária sob `storage/framework/testing/dto/` — helper `raizDeFixture()`;
      `git status` continua limpo depois da suíte, 2026-09-15

### 9. Rule de projeto

- [x] Candidato apresentado ao usuário (4 gates) — aprovado com "grave as rules também", 2026-09-15
- [x] Gravada via `record-rule` do Boost — `.ai/rules/app.md`, "DTO no kit é spatie/laravel-data, em
      app/Data", 2026-09-15

### 10. Documentação

- [x] `docs/pt/recursos/dto-com-laravel-data.md` — `nav_order: 8`, 2026-09-15
- [x] `docs/en/recursos/dto-com-laravel-data.md` — `nav_order: 8`, 2026-09-15
- [x] Índices de `recursos` (pt e en) citando a página — 2026-09-15
- [x] `README.md` / `README.en.md` — `spatie/laravel-data` na tabela de pacotes, contagem 56 → **57**
      nos dois idiomas, linha na tabela de documentação, 2026-09-15
- [x] `CHANGELOG.md` — entrada em `[Unreleased]`; o aviso de `widgets()` caiu junto com o corte do
      `WidgetDoDashboardData` (passo 7 fora desta entrega), 2026-09-15
- [x] `CLAUDE.md`/`AGENTS.md` **não editados** (gerados pelo Boost) — decisão registrada no `01`,
      2026-09-15
- [x] `tests/Kit/SiteDeDocumentacaoTest.php` — 38/38, 144 asserções, 2026-09-15

## Testes

Os quatro arquivos juntos: **51 testes, 51 passaram, 163 asserções** (2026-09-15).

- [x] `tests/Kit/DtoComLaravelDataTest.php` — CT-04…CT-15, CT-20, CT-21, 2026-09-15
- [x] `tests/Kit/GuardrailsDtoTest.php` — CT-01, CT-02, CT-03, CT-16, CT-22, CT-28…CT-31, 2026-09-15
- [x] `tests/Kit/LoginSocialDtoTest.php` — CT-17, CT-18, CT-19, CT-23, CT-27, 2026-09-15
- [x] `tests/Kit/ConviteEmMassaDtoTest.php` — CT-24, CT-25, CT-26, 2026-09-15

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — 8 arquivos tocados, +79/-42; os 715 arquivos novos são
      4 Data (285 linhas), 1 cast (45) e o guarda (385). Nenhuma abstração especulativa sobrou,
      2026-09-15
- [x] `vendor/bin/pint --dirty --format agent` — sem apontamento, 2026-09-15
- [x] `composer types:check` — PHPStan level 7, **0 erros**, 2026-09-15
- [x] `vendor/bin/filacheck` — **17/17 regras** passaram, 2026-09-15
- [x] `php artisan test --compact` nos 4 arquivos da feature — **51/51, 163 asserções**, 2026-09-15
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — **2321/2321**,
      7708 asserções; regressão obrigatória porque o `01` declara "toca infra compartilhada:
      sim", 2026-09-15
- [x] `diff` dos IDs de CT entre o `04` e os arquivos de teste — **saída vazia**: 31 IDs no `04`,
      31 nos testes, 2026-09-15
- [x] Falsificabilidade: cada CT novo falha sem a correspondente implementação (`git stash push --
      app/`) — inclusive CT-22, que **só** ficou falsificável depois de reescrito (ver
      `Desvios do Plano`), 2026-09-15
- [x] Citações `arquivo:símbolo:linha` da wiki reverificadas — todo caminho de `app/**` citado ou
      existe, ou é declaradamente inexistente (`routes/api.php`, `app/Http/Resources`,
      `config/data.php`, os 3 Data cortados). As citações do projeto externo estão prefixadas
      `cms:`, 2026-09-15
- [x] `git commit` — branch `feat/laravel-data-como-padrao-de-dto`, 2026-09-15

## Conformidade com Rules

<!-- Preenchida no step 7, uma linha por rule cujo glob casa o diff. -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `.ai/rules/app.md` | `app/**` | aplicada | nomes em português, `final` + `readonly`, sem facade dentro do Data. A feature **acrescentou** a rule de DTO a este arquivo |
| `.ai/rules/general.md` | `composer.json` | aplicada | dependência nova aprovada pelo usuário antes do `require`; ADR-01 registra o `--dry-run` limpo |
| `.ai/rules/testes.md` | `tests/**` | aplicada com desvio declarado | helper compartilhado deve morar em `tests/Pest.php`; `ligarLoginComGoogleDoKit()` está noutro arquivo de teste, então as duas linhas de `config()` foram inlinadas — ver `Desvios do Plano` |
| `.ai/rules/specs.md` | `wikis/specs/**` | aplicada | `00`…`04` no diretório da feature; checkbox só fecha com evidência inline |
| `.ai/rules/filament.md` | `app/Filament/**` | aplicada | `ConvidaEmMassa` só troca o tipo do valor consumido; `vendor/bin/filacheck` sem apontamento |

## Achados da Revisão Adversarial (rodada 1)

| # | Achado | Fechamento |
|---|---|---|
| I1 | varredura por `str_contains` reprova o Data correto escrito de outra forma válida | CT-22, CT-23 |
| I2 | varredura só enxerga a raiz que o teste entrega — em produção não vê nada | CT-25, CT-10 sem argumento |
| I3 | `seguro ?? true`: nenhuma linha omitia a chave de decisão | linha nova em CT-05 |
| I4 | os 6 Data existem e nenhum ponto de produção os consome — 21/21 verde com RQ-03/RQ-06 não entregues | CT-27 |
| I5 | oráculo relativo em `widgets()`: ignorar o override passava | CT-19 absoluto + CT-20 com controle |
| 13 oráculos fracos | "não estourou", ausência sem destinatário, log sem `acao` | cenários reescritos com valor concreto |
| contradição de setup | fixtures declaradas fora de `app/` e cenários dizendo "em app/Data" | duas modalidades declaradas no Setup Global |

## Escalação — teto de revisão adversarial atingido

A rodada 2 provou que o conjunto v2 ainda deixa passar defeito. O diagnóstico não é "faltou
cenário": é que **duas regras estão fazendo trabalho de quatro**, e é isso que a skill manda
escalar em vez de tentar uma terceira rodada.

### O que a rodada 2 achou

| # | Implementação errada que passa nos 30 cenários | Regra |
|---|---|---|
| I6 | **"Data de vitrine"**: o fechamento de I4 criou ponto de uso para **um** dos seis Data; os outros cinco continuam sem oráculo de consumo. `RQ-06` entregue em 1 de 6 e o conjunto verde | R5, R3 |
| I7 | **"guarda de duas caras"**: a partição válida (CT-22/23/28) só roda em modo dirigido; o escopo real (CT-25/10) só afirma reprovação. Um guarda que reprova tudo **quando chamado sem raiz** passa — e o kit publicado nunca é exercitado contra o próprio guarda | R2, R4, R6 |
| I8 | **"mensagem catch-all"**: uma mensagem única citando todos os motivos satisfaz `a mensagem cita "X"` em CT-02, CT-03 (3 linhas), CT-04, CT-14 (5 linhas) e CT-15 | R2, R6 |
| I9 | **"fábrica artesanal"**: `new self(...)` com cast à mão dentro da fábrica passa em CT-17 e CT-29; o pacote vira dependência decorativa | R7 |
| I10 | **"vazamento no ramo schema"**: o invariante "o texto do usuário não aparece no log" existe em **uma** das três colunas da tabela de decisão | R8, R6 |

### Conflito bloqueante

**CT-09 × CT-10**: CT-09 afirma que `routes/api.php`, `app/Http/Resources` e controller JSON **não
existem**; CT-10 cria os três no caminho de produção — no mesmo arquivo de teste, com a suíte
rodando `--parallel`. A fixture que um exige o outro proíbe.

E a modalidade "em escopo real" (escrever arquivo temporário dentro de `app/` e `routes/`) é frágil
por quatro motivos independentes: visível a todos os workers em `--parallel`; interrupção deixa
classe defeituosa autocarregada no working tree; `routes/api.php` criado em runtime depende do
`bootstrap/app.php` e de cache de rota; e o teste suja `git status` no meio da execução.

**Substituto proposto pela revisão**: o guarda **expõe as raízes padrão como dado** (constante ou
config). Um cenário afirma que esse valor é exatamente as quatro raízes de produção **e** que a
varredura sem argumento fica **verde no kit como publicado**; as fixtures defeituosas passam a
rodar em raiz temporária sob `storage/framework/testing/`. Fecha L22, elimina a escrita em `app/`
e desfaz o conflito CT-09 × CT-10.

### O diagnóstico estrutural

| Regra atual | Deveria ser duas |
|---|---|
| **R2** — "a varredura reprova o errado e aprova o certo" | **R2a — veredito**: dado um artefato, o guarda julga certo e diz o motivo **discriminante** · **R2b — escopo**: chamado sem argumento, ele percorre os caminhos de produção e fica verde no kit publicado |
| **R5** — "o Data é consumido e o observável não muda" | **R5a — consumo**: cada um dos seis Data tem um ponto de uso em produção, provado por um valor que **só o Data produz** · **R5b — regressão**: o observável de hoje não muda |

R5a sozinha são **seis** cenários — um por Data —, e cada um exige um valor discriminante (uma
chave que o Data descarta e o array cru manteria). É o custo que a decisão de escopo do usuário
determina, e por isso a escalação.

### Mutantes ainda sem matador (rodada 2)

M9, M11, M21, M32 (parcial), M33 (parcial), M37 (parcial), M42 — mais três não declarados: os dois
campos restantes do widget descartados, o guarda de componente/sufixo que nunca roda em produção, e
a varredura sem raiz que nunca fica verde.

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: não executado · **Veredito**: — · **Data**: —
- **Relatório**: `06-relatorio-qa.md` — **não gerado**

O `feature-quality-gate` não rodou nesta entrega. O que ficou no lugar dele está evidenciado acima:
regressão `Kit,Tenancy` completa, PHPStan level 7 limpo, `diff` de IDs de CT vazio (31/31) e
falsificabilidade por `git stash`. Fica registrado como pendência da feature — não como passo
silenciosamente pulado.

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "atualizar `CLAUDE.md`/`AGENTS.md`" (resposta de P3) | os dois são **gerados pelo Boost**, idênticos byte a byte, envoltos em `<laravel-boost-guidelines>` e reescritos por `boost:update` | `01` → `## Cobertura do Requisito` ganhou a nota; a convenção vai para `.ai/rules/dto.md` |
| "o pacote pode ter superfície alcançável pelo cliente" | o pacote não tem rota nem controller, **mas** `LivewireDataSynth::hydrate()` reconstrói o Data a partir do payload do navegador | `02` ganhou `## Superfície do Pacote`; `04` ganhou CT-15 para falsificar a negativa |
| "`spatie/laravel-data` pode conflitar com Laravel 13" | `composer require --dry-run` resolve limpo: 4.23.0 + `php-structure-discoverer` 2.4.4, sem advisory | `02`/ADR-01 registra a evidência |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| C1 | 6 Data → 3: os outros três não têm consumidor que o array cru já não sirva | sim | `01`, passos 4, 6 e 7 marcados **fora desta entrega** |
| C2 | guarda sem interface nem config publicada — classe única, raízes em constante | sim | `app/Support/GuardaDoPadraoDeDto` |
| C3 | nada de `DataCollection` para a lista de falhas; `list<FalhaDoConviteData>` resolve | sim | ADR-05 e `ResultadoDoConviteEmMassaData` |

## Blockers

- [x] Nenhum bloqueante aberto no fim da entrega, 2026-09-15
- **Pendência declarada**: `feature-quality-gate` / `06-relatorio-qa.md` não executados (ver
  `Quality Gate`)

## Desvios do Plano

| Desvio | Por quê | Consequência |
|---|---|---|
| `BooleanoFlexivelCast` mora em `app/Support/Casts/`, não em `app/Data/` | o próprio guarda exige que **tudo** em `app/Data/**` estenda `Data`, e um `Cast` não estende | nenhuma — o guarda segue com a regra forte, sem exceção escrita dentro dele |
| CT-22 foi reescrito depois de implementado | a justificativa original estava **errada**: o código antigo era `($veredito['seguro'] ?? false) === true`, estrito, já fail-closed para `"false"`. O `git stash` mostrou CT-22 verde sem a correção | CT-22 passou a usar `"true"`: o código antigo **bloqueava prompt legítimo**, e o cast conserta. Mutante M29 e o texto do `04` corrigidos junto |
| duas linhas de `config()` inlinadas em vez de reusar `ligarLoginComGoogleDoKit()` | o helper mora noutro arquivo de teste e a rule manda helper compartilhado viver em `tests/Pest.php`; mover o helper aumentaria o diff sem necessidade | dívida mínima, registrada aqui |
| 8 arquivos de documentação e código voltaram a LF | escrita via Python no Windows converteu LF → CRLF, e `frontMatterDe()` casa `/\A---\n/`: toda a pasta `recursos` passou a ser reportada como órfã | conversão binária de volta; `SiteDeDocumentacaoTest` 38/38 |

## Notas de Implementação

- A varredura de `RQ-05` foi feita **antes** do plano, por sub-agente de leitura: 5 achados de
  consumo externo, 8 de shape fixo interno, 0 de resposta de API, 0 de job/evento com array.
  Os recusados estão na tabela do `01`, com motivo — não são esquecimento.
- O uso real do pacote num projeto do mesmo autor (`FM2S/CMS`) foi lido para extrair convenções e
  armadilhas. Três achados de lá viraram ADR: sufixo `Data` colidindo com Model (ADR-02), duas
  estratégias de coleção sem critério (ADR-05) e senha em Data sem `#[Hidden]` nem teste (ADR-04).

## Retrospectiva

**O que a feature entregou.** `spatie/laravel-data` 4.23 instalado, três Data em produção
(`VeredictoDoGuardrailData`, `PerfilSocialData`, `ResultadoDoConviteEmMassaData` +
`FalhaDoConviteData`), um guarda que enforça o padrão em vez de descrevê-lo, a rule em
`.ai/rules/app.md`, documentação pt/en e 31 casos de teste — todos implementados, todos verdes.

**O que custou mais do que parecia.** As duas rodadas adversariais, e elas se pagaram: a rodada 1
provou 5 implementações erradas passando nos 21 cenários originais e a rodada 2 provou mais 5 no
conjunto de 30. O que fechou a conta não foi uma terceira rodada — foi a escalação ao usuário e o
corte de 6 Data para 3. Menos alvo, mesmo rigor.

**O erro que mais ensinou.** A justificativa de CT-22 estava errada: eu afirmei que o código antigo
liberava prompt com `seguro` vindo como `"false"`, e o código antigo era `=== true`, já fail-closed.
Quem pegou foi o `git stash` — o cenário passou **sem** a correção, que é exatamente o sinal de que o
oráculo não estava medindo o que dizia medir. Sem o passo de falsificabilidade, isso teria entrado na
wiki como fato.

**O que fica para a próxima entrega.**

- `UsoDeTokensData` (A3), `MarcaDaInstalacaoData` (A5) e `WidgetDoDashboardData` (A6), com os alvos
  preservados no `01`
- `feature-quality-gate` / `06-relatorio-qa.md`, declarados como pendência no bloco `Quality Gate`
- A cláusula de API do `RQ-04` hoje não tem sujeito: o kit não expõe API. O guarda é quem vai
  cobrá-la no dia em que aparecer a primeira rota
