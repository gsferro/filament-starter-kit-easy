# Casos de Teste — feat/estudo-de-pacotes-rodada-3: Estudo e adoção de pacotes Filament, rodada 3 (tabelas e PHPStan)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, rotas, env, mapeamentos, stack e `## Superfície de UI`) · ADR: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação. A cópia dos pacotes em vendor foi lida só para **nomes** (Actions, métodos, chaves de config e de tradução, tabela `remember_table_views`).
> **Revisão adversarial: rodadas 1 e 2 feitas** (`fw-adversario-ct`, 2026-10-14) — rodada 1: 41 achados; rodada 2: 36 fechados, 5 parciais e 9 novos (ADV-42…ADV-50). Tudo fechado, nada rejeitado; ver `## Revisão adversarial — rodada 1` e `## Revisão adversarial — rodada 2`. Teto de 2 rodadas atingido: não há rodada 3. Os cenários criados no fechamento da rodada 2 (CT-75…CT-78) não passaram por revisão independente — débito declarado para o `feature-quality-gate`.
> Desvio declarado: o `00` é somente leitura para esta derivação (sub-agente). As perguntas novas voltam na resposta da derivação e em `## Fronteira com o Plano` → *Perguntas geradas pela derivação*, numeradas `Q?n`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Interruptores e defaults (Settings, enum `CarregamentoDaTabela`, aba *Tabelas*, semente) | 2 | 2 | 4 | padrão |
| Visões salvas (permissão, posse, organização, compartilhamento, desligar/religar, aplicação) | 3 | 3 | 9 | completo |
| Carregamento contínuo (toda tabela dos três painéis, bloco, teto, entrada forjada) | 3 | 2 | 6 | padrão |
| PHPStan (include da extensão, sem exceção nova) | 1 | 1 | 1 | mínimo |
| Entrega da rodada (pacotes adotados/recusados, documentação, capturas, traduções) | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: EP, BVA 3-valores (teto de 500, escalado na área padrão), tabela de decisão (visibilidade), matriz papel × ação, matriz estado de configuração × operação (uma só, 24 células), 2-switch (desligar → religar), rastreio de efeito (consulta ao banco), normalização (vocabulário do carregamento)
- Cenários: 78 · Regras: 26 · Mutantes previstos: 154 · Sem matador: 0
- Rodada 2 da derivação (decisões da sessão sobre Q6–Q12 → P-13…P-17, Q11 aberta, D6): CT alterados CT-15, CT-24, CT-39; CT novos CT-53…CT-60; regras novas R22…R25; nenhum CT obsoleto. Rodada 3 (Q13 → P-18; P-12 incorporada): CT-61 e CT-62 novos, regra nova R26. Rodada 4 — fechamento da revisão adversarial 1 (P-19…P-25): CT-63…CT-74 novos; CT-02, CT-03, CT-08, CT-12, CT-16, CT-20, CT-22…CT-25, CT-28, CT-31…CT-36, CT-38, CT-39, CT-41, CT-42, CT-44, CT-45, CT-47, CT-50, CT-51, CT-53, CT-54, CT-57, CT-62 alterados; M111…M147 novos (origem ADV, fora do teto de mutantes por regra); nenhum CT obsoleto. Rodada 5 — fechamento da revisão adversarial 2 (P-26 substitui P-19; P-27): CT-75…CT-78 novos; CT-12, CT-24, CT-25, CT-35, CT-47, CT-51, CT-57, CT-64, CT-70, CT-73 alterados; M148…M154 novos; nenhum CT obsoleto.
<!-- derivado do arquivo por grep -c (comandos em references/template-04.md, "Contagem do cabeçalho"); CT-B01 vive no 05 e não entra na contagem de cenários -->
- Técnica escalada: R15 (teto de 500 linhas) usa BVA 3-valores numa área padrão — o número é literal de P-10, e BVA 2-valores não separa "para em 500" de "passa uma vez de 500".
- Precedência (rule do projeto vence a skill), três divergências declaradas:
  1. `.ai/rules/testes-browser.md` — comando do CT-B é `composer test:browser` (embute `npm run build` e `view:cache`), em série, nunca `--parallel`; a skill sugere `vendor/bin/pest tests/Browser --filter=…`.
  2. `tests/Pest.php` não liga `tests/Unit` a TestCase nenhum; a costura `unit de regra` mora em `tests/Kit` (molde: `DensidadeDoLayoutTest` CT-10), como o projeto já faz.
  3. `.ai/rules/testes.md` — no `/app`, o painel boota por um `GET` real antes do primeiro `Livewire::test()`; `noPainelBootado('app')` não serve.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | 3 propriedades do Settings + mapa + migration de settings; enum `CarregamentoDaTabela`; trait `VisoesDeTabela` em 10 listagens; `ConfiguraFilamentGlobal::aplicaCarregamento()`; permissão custom `Usar:VisoesDeTabela` (`config/filament-shield.php` + `PapeisSeeder`); tabela `remember_table_views` (model de vendor `TableView`); `config/remember-table.php`; `lang/vendor/filament-remember-table/pt_BR`; `phpstan.neon`; `composer.json`; docs pt/en; `KitArte` | CT-01, CT-02, CT-16, CT-17, CT-48, CT-49, CT-50, CT-51, CT-52 |
| F | ligar/desligar (3 interruptores); salvar, aplicar, atualizar, gerenciar (renomear, reordenar, apagar), limpar, compartilhar visão; carregar o próximo bloco (rolagem ou botão); teto; reinício do bloco; coerção de valor ilegível; função administrativa escondida: os métodos públicos do trait do pacote (`applyTableView`, `resetTableToDefaultState`) e o método interceptado `infiniteScrollLoadMore` em **qualquer** tabela | CT-03…CT-47, CT-B01 |
| D | `state` JSON gravado por um usuário e relido (filtros, busca, ordenação, agrupamento, colunas, aba, tamanho de página); `user_type`/`user_id`, `tenant_id` (**string** na tabela), `table_key`, `is_public`, `is_default`; **dado de outra organização** e **de outro usuário**; discriminante nulo (`tenant_id` nulo); nome livre exibido a colegas; volume: 501 registros para o teto | CT-24…CT-40, CT-44 |
| I | aba *Tabelas* (`/admin/configuracoes-da-aplicacao`); botão *Visões* e modais nas 10 listagens; `?view={id}` na URL; `$wire.*` (métodos públicos, `mountAction` por nome, `set('activeTableView')`, `set('tableRecordsPerPage')`); `.env`/`.env.example`; seeders; `composer.json`; `phpstan dump-parameters` | CT-01, CT-11, CT-13…CT-16, CT-20, CT-32…CT-34, CT-45, CT-46, CT-48 |
| P | SQLite em memória nos testes; `tenant_id` string comparado com a chave da organização (afinidade de tipo do SQLite × MySQL — exercitado em CT-31 com a organização real); Alpine `x-intersect` no navegador (CT-B01); `app.locale` pt_BR (CT-52); Windows para `--mutate` (rule de `testes.md`: sem `--filter`, pelo `pestw.cmd`) | CT-31, CT-52, CT-B01 |
| O | personas `admin`, `infra`, `panel_user`, `admin_app`, `master_global` (controle), admin com a permissão revogada; uso indevido: payload forjado, id alheio, tamanho de página forjado; volume acima do teto; plugins de terceiro com tabela | CT-18…CT-23, CT-25, CT-26, CT-28, CT-41, CT-45 |
| T | ordem desligar → religar (2-switch); leitura por request (gravar e ver o efeito no mesmo processo); visão gravada antes de a tabela mudar (filtro/coluna removidos depois); registro criado depois da visão. Timezone/DST: não se aplica — nenhuma regra temporal (visão não expira, sem data no requisito) | CT-16, CT-37, CT-38, CT-39, CT-40 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Tudo nasce desligado na fábrica: `paginacao`, visões desligadas, compartilhamento desligado | Interruptores (padrão) | RQ-05, P-02, P-10 | EP (fonte × chave) | CT-01, CT-02 |
| R2 — Em `paginacao` a tabela é a de hoje | Carregamento (padrão) | RQ-05, P-10 | EP + controle | CT-03, CT-71 |
| R3 — Com as visões desligadas nada é oferecido, consultado, aplicado nem executado | Visões (completo) | RQ-05, P-03 | matriz estado × operação (linha E1) + rastreio de efeito | CT-04…CT-08 |
| R4 — Vocabulário fechado do carregamento; fora dele, `paginacao` sem erro | Interruptores (padrão) | P-11, P-10, P-24 | EP + estado exibido exaustivo + normalização | CT-09…CT-12, CT-76 |
| R5 — A aba *Tabelas* grava os três interruptores e o gravado governa por request | Interruptores (padrão) | RQ-04, RQ-05 | EP + criação ≠ edição ≠ uso | CT-13, CT-14, CT-16 |
| R6 — `Usar:VisoesDeTabela` nasce em `admin`, `infra`, `admin_app`, não em `panel_user`, e é concedível | Visões (completo) | P-01, RQ-04 | matriz papel × permissão + rastreio de efeito (tela → ação) | CT-17…CT-19, CT-67 |
| R7 — Salvar, atualizar, gerenciar e limpar exigem a permissão; aplicar não | Visões (completo) | P-08, P-01 | matriz papel × ação (+ papel × organização) | CT-20…CT-23, CT-66 |
| R8 — Uma visão gravada pertence a quem gravou, à página e à organização aberta; o cliente não escolhe | Visões (completo) | P-07, P-02 | EP + mass assignment | CT-24…CT-26, CT-78 |
| R9 — Só o dono atualiza, renomeia, reordena ou apaga a própria visão | Visões (completo) | P-08, P-07, P-20 | matriz papel × ação (dono × colega × master × inexistente) + mass assignment | CT-27…CT-29, CT-64, CT-72 |
| R10 — Visibilidade: dono, página e organização; compartilhada só na mesma organização/painel e só com o compartilhamento ligado | Visões (completo) | P-07, P-02, P-22, P-27, P-28 | tabela de decisão + IDOR | CT-30…CT-34, CT-65, CT-73, CT-74, CT-77 |
| R11 — "Compartilhar com todos" existe só com o interruptor ligado; o nome compartilhado chega ao colega como texto | Visões (completo) | P-02, P-26 | EP + 2-switch sobre `is_public` + taxonomia (texto livre exibido a terceiro) | CT-35, CT-36 |
| R12 — Desligar esconde sem apagar; religar devolve | Visões (completo) | P-03 | 2-switch | CT-37, CT-38 |
| R13 — Aplicar restaura só o que ainda existe; a visão guarda recorte, não dados nem tamanho de página | Visões (completo) | P-09, P-16 | EP (parte do estado: válida e inexistente) | CT-39, CT-40, CT-63, CT-75 (e CT-24, `state` sem tamanho de página) |
| R14 — Contínuo em toda tabela, no modo escolhido, em blocos de "Linhas por página", sem seletor de registros por página | Carregamento (padrão) | P-04, P-10, RQ-04 | EP (modo × tabela) | CT-41…CT-43 (+ CT-B01 no `05`) |
| R15 — Nunca mais de 500 linhas na tela; entrada forjada não fura o bloco | Carregamento (padrão) | P-10, P-23 | BVA 3-valores (escalado, linhas e total) + EP de tipo + estado terminal × modo | CT-44…CT-46, CT-69, CT-70 |
| R16 — Busca, filtro, ordenação, agrupamento ou aba reiniciam no primeiro bloco | Carregamento (padrão) | P-10 | EP (gatilho) | CT-47 |
| R17 — A extensão de PHPStan entra pelo include, sem exceção nova e só em dev | PHPStan (mínimo) | P-05, RQ-02 | EP | CT-48 |
| R18 — Entram os pacotes adotados; os dois recusados ficam de fora | Entrega (mínimo) | RQ-01, RQ-02, P-06 | EP | CT-49 |
| R19 — A documentação registra o veredito dos cinco e explica como ligar cada opção, em pt e en | Entrega (mínimo) | RQ-01, RQ-06, P-25 | EP | CT-50 |
| R20 — Cada funcionalidade visível tem captura nos temas claro e escuro | Entrega (mínimo) | RQ-07 | EP | CT-51 |
| R21 — As telas novas falam pt_BR: toda chave do pacote tem tradução, e a aba *Tabelas* usa os rótulos do plano | Entrega (mínimo) | P-17, RQ-04, RQ-06 | EP + estado exibido | CT-52, CT-60 |
| R22 — Nome obrigatório e até 100 caracteres, ao salvar e ao gerenciar; nome repetido é permitido | Visões (completo) | P-13, P-21 | BVA 3-valores (100 caracteres) + EP (vazio após trim) + criação ≠ edição | CT-53, CT-54, CT-56 |
| R23 — No máximo 50 visões por usuário por listagem, contadas por organização | Visões (completo) | P-13, P-18 | BVA 3-valores (50) + EP (organização) | CT-55, CT-61 |
| R24 — Só a visão padrão do próprio usuário é aplicada sozinha ao abrir; a de colega, nunca | Visões (completo) | P-14, P-22, P-07 | tabela de decisão (de quem × listagem × organização × recorte na URL) | CT-57, CT-68 |
| R26 — O Filament resolvido tem o render hook de que o carregamento contínuo depende | Entrega (mínimo) | P-12, RQ-02 | EP | CT-62 |
| R25 — O compartilhamento só existe com as visões ligadas: o campo some, desligar as visões grava o compartilhamento desligado, e religá-las não o religa | Visões (completo) | P-15, P-02 | EP + 2-switch | CT-15, CT-58, CT-59 |

- RQ-03 — sem cenário: restrição de **processo** (uso do Codex CLI). A evidência é o `03-progresso.md` e o histórico da sessão, conferidos pelo `feature-quality-gate`; não há comportamento do sistema a falsificar.
- RQ-08 — sem cenário: restrição de **processo** (branch nova, sem interferir no CI da outra sessão). A evidência é o `git`; não há comportamento do sistema a falsificar.
- P-05 — "a extensão de PHPStan não tem interruptor" é escopo (apaga RQ-04/RQ-05 para ela); o que sobra dela é R17.
- P-08 — coberta: CT-20…CT-23, CT-27…CT-29 (o recorte de nome e quantidade virou P-13 → R22, R23).
- P-09 — coberta: CT-39, CT-40; o tamanho de página foi decidido em P-16 (não é guardado) → CT-24, CT-39.
- P-02 — coberta: CT-01, CT-25, CT-30, CT-33, CT-35; o recorte do compartilhamento ao desligar/religar e a visibilidade do campo foi decidido em P-15 → R25.
- P-07 / R10 — cobertura parcial: CT-24…CT-34; recorte *visão compartilhada de autor que perdeu o vínculo com a organização ou teve a conta Inativa/Excluída* **bloqueado — Q11** (aberta, fora desta entrega; hoje vale o comportamento do pacote). O resto de R10 continua coberto.
- P-12 — o Filament sobe de 5.8.2 para 5.10.x (dentro da série 5) por causa de `TablesRenderHook::CONTENT_AFTER`: R26.
- P-18 (Q13) — o teto de 50 é contado por organização: R23 (CT-61).
- P-26 (Q21) substitui P-19 — com o compartilhamento desligado, toda visão **enviada** pelo formulário de gerenciar (o repeater reenvia todas) é regravada privada: R11 (CT-35). P-27 (Q22) — o `master_global` não é exceção na visibilidade: R10 (CT-77).
- P-19…P-25 (Q14…Q20, decididas pela sessão sobre as perguntas da revisão adversarial 1): P-19 (substituída por P-26) → R11 (CT-35); P-20 → R9 (CT-72); P-21 → R22 (CT-53, CT-54); P-22 → R24 (CT-57); P-23 → R15 (CT-44); P-24 → R4 (CT-12); P-25 → R19 (CT-50).
- P-13, P-14, P-15, P-16, P-17 — premissas novas (decisões da sessão sobre Q6, Q7, Q8, Q9, Q10): R22/R23, R24, R25, R13, R21.

### Matriz estado de configuração × operação (uma só)

Estados (produto cartesiano dos dois interruptores; o compartilhamento não tem efeito sem as visões — P-02/P-03): **E1** visões desligadas (compartilhamento qualquer — a linha `desligadas/ligado` de CT-04 e as de CT-07 exercitam o compartilhamento ligado) · **E2** visões ligadas, compartilhamento desligado · **E3** visões ligadas, compartilhamento ligado.
Operações: **O1** oferecer o botão *Visões* e as ações · **O2** salvar · **O3** aplicar a própria · **O4** aplicar a compartilhada de colega · **O5** atualizar · **O6** gerenciar (renomear/apagar) · **O7** limpar tabela · **O8** `?view={id}` na URL.
**3 × 8 = 24 células** — 9 inválidas (❌), 13 válidas (✅), 2 `não se aplica`. Legenda (auditada coluna a coluna): ✅ executa e o efeito é afirmado; ❌ = recusa **e** não-efeito do efeito que aquela operação dispara no caminho feliz — O1: ação não oferecida; O2: nenhuma linha nova em `remember_table_views`; O3/O4/O8: recorte não aplicado (busca vazia) e nome não exibido; O5: `state` inalterado; O6: nome e linha inalterados; O7: recorte não limpo. Persona da célula: dono **com** a permissão (a persona sem permissão é a dimensão da matriz papel × ação de R7).

| | O1 | O2 | O3 | O4 | O5 | O6 | O7 | O8 |
|---|---|---|---|---|---|---|---|---|
| E1 | ❌ CT-04, CT-05 | ❌ CT-08 | ❌ CT-07 | ❌ CT-07 | ❌ CT-08 | ❌ CT-08 | ❌ CT-08 | ❌ CT-07 |
| E2 | ✅ CT-21 | ✅ CT-24 (`is_public` falso: CT-25) | ✅ CT-30 (linha a) | ❌ CT-30 (linha f), CT-33 | ✅ CT-21, CT-29 | ✅ CT-27 | ✅ CT-21 | ✅ CT-33 (controle) |
| E3 | ✅ CT-35 | ✅ CT-35 (marca compartilhada) | não se aplica: aplicar a própria não depende do compartilhamento (P-02 só governa a exposição a terceiros); a célula E2 executa a operação, e CT-30 linha a (ligado) a repete em E3 | ✅ CT-30 (linha e), CT-22, CT-23 | ✅ CT-29 (a de colega é ❌ na mesma tabela de Exemplos) | ✅ CT-35 (gerenciar marca compartilhada) | não se aplica: limpar só altera o estado da própria tabela (`vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:resetTableToDefaultState():511-547`); a célula E2 executa a operação | ✅ CT-33 (compartilhada de colega) |

## Costuras de Teste

<!-- Proposta da derivação em sub-agente: coluna Confirmada VAZIA — a sessão confirma com o desenvolvedor (raia desenho) antes de materializar; costura trocada re-deriva o grupo. -->

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 Vocabulário do carregamento | R4 | unit de regra | nova — `tests/Kit/CarregamentoDaTabelaTest.php`, irmã de `tests/Kit/DensidadeDoLayoutTest.php` (CT-10/CT-13 de lá são o molde de `coagir()`); nenhum arquivo existente testa este enum | o `Então` é valor calculado (`coagir`, `opcoes`, `modoDoPacote`) | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G2 Fábrica e regressão de tabela | R1, R2, R4 | Pest feature HTTP | existente — `tests/Kit/DefaultsDeTabelaTest.php` (observa o `Table` depois do `configureUsing` global; config de fábrica por `kitConfigCom()`) | o `Então` é config de arquivo, linha semeada, `.env.example`, `Table` resultante, página servida | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G3 Aba *Tabelas* | R4, R5, R21 (CT-60), R25 (CT-15, CT-58) | componente Livewire/Filament | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` (gravação por componente da tela de configurações) | gate de tela de escrita: `fillForm` → `call('save')` → `configuracaoGravada()` | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G4 Carregamento contínuo | R14, R15, R16 | componente Livewire/Filament | nova — `tests/Kit/CarregamentoContinuoTest.php`; nenhum teste existente monta uma listagem e chama `infiniteScrollLoadMore` | o `Então` é a quantidade de linhas e o rodapé renderizados pelo componente da listagem (com a definição de tabela da própria página, que `Table::make()` não aplica) | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G5 Visões no painel single-tenant | R3, R7, R8, R9, R10, R11, R12, R13, R22, R23, R24, R25 (CT-59) | componente Livewire/Filament | nova — `tests/Kit/VisoesDeTabelaTest.php`; molde de persona e permissão: `tests/Kit/PermissoesDeAcoesTest.php` e `tests/Kit/DockDeAtalhosTest.php` | ações de página, formulário de modal, visibilidade de ação, estado da tabela | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G6 Visões na organização | R3, R7, R8, R10, R14, R23 (CT-61) | componente Livewire/Filament | nova — `tests/Tenancy/VisoesDeTabelaTenancyTest.php`; molde: `tests/Tenancy/DockDeAtalhosTenancyTest.php` | `admin_app` e a organização aberta só existem em `tests/Tenancy` (`.ai/rules/testes.md`) | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G7 Visões por fora do componente | R3, R10 | Pest feature HTTP | existente nos arquivos de G5/G6 (casos HTTP no mesmo arquivo, como `DockDeAtalhosTenancyTest` CT-72) | gate de camada da regra: o `?view={id}` pela rota real e a contagem de consultas do request | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G8 Permissão semeada | R6 | Pest feature HTTP | existente — `tests/Kit/PermissoesDeAcoesTest.php` (CT-37/CT-41 do dock são o molde) e `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | o `Então` é a matriz de papéis depois dos seeders e o conjunto oferecido na tela de papéis | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G9 PHPStan | R17 | Pest feature HTTP | existente — `tests/Kit/QualidadeDeCodigoTest.php` (CT-04/CT-06 sobre `phpstan.neon`, helper `configuracaoEfetivaDoPhpstan()`) | o `Então` é a configuração efetiva do analisador | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G10 Entrega da rodada | R18, R19, R20, R21, R26 | Pest feature HTTP | nova — `tests/Kit/PacotesRodada3Test.php`, irmã de `tests/Kit/PacotesRodada2Test.php` | o `Então` é arquivo do repositório (`composer.json`, docs, `KitArte::IMAGENS`, `lang/`) | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |
| G11 Rolagem no navegador | R14 | browser | nova — `tests/Browser/CarregamentoContinuoTest.php` | a interseção (`x-intersect` do Alpine) que dispara o próximo bloco é JavaScript executado; nenhum teste de componente a prova | sim — convenção existente (molde citado na linha), sessão, 2026-10-14 |

## Fronteira com o Plano

| Item do PRD/ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| Nomes `habilitadas()`, `coagir()`, `opcoes()`, `deConfig()`, `padrao()`, `modoDoPacote()`, `aplicaCarregamento()`, `VisoesDeTabela::PERMISSAO` | escolha de implementação | detalhe do cenário |
| Nome da permissão `Usar:VisoesDeTabela` | **o requisito determina** (P-01) | oráculo (R6, R7) |
| Valores `paginacao`/`rolagem`/`botao` e o 500 | **o requisito determina** (P-10, P-11) | oráculo (R4, R15) |
| Chaves `kit.tabelas.carregamento`, `kit.tabelas.visoes_salvas`, `remember-table.allow_public_views` e as variáveis `KIT_TABELA_*` | escolha de implementação; o oráculo é o **efeito** (desligado na fábrica, o gravado governa) | detalhe do cenário (Dado/Quando); CT-01 lê cada fonte para o valor de fábrica |
| A migration semeia "o valor da config vigente" (`.env` como semente) | escolha de implementação; o requisito só fixa o valor de fábrica (desligado) | só o valor de fábrica é afirmado (CT-02) |
| Rótulos das três opções do Select e helpers da aba *Tabelas* | **o requisito passou a determinar** (P-17: "os rótulos/ajudas do plano valem") | oráculo: rótulos das opções e o trecho do helper das visões que afirma P-03 (CT-60); texto integral dos demais helpers não é afirmado (frágil, sem mutante que só ele mate) |
| Textos do botão, dos modais e das notificações | P-17 fixa o idioma (pt_BR completo), não cada frase | oráculo: completude e marcadores (CT-52) |
| Toggle "compartilhamento" visível só com as visões ligadas; gravado `false` ao salvar com as visões desligadas | **o requisito passou a determinar** (P-15) | oráculo (CT-15, CT-58, CT-59). O mecanismo (`mutateFormDataBeforeSave()` da Page) é detalhe: o cenário afirma o valor gravado |
| Nome `required`/`maxLength(100)` e `max_views_per_table = 50`; nome repetido permitido | **o requisito passou a determinar** (P-13); "validação do pacote"/"config do pacote" é mecanismo | oráculo com os números literais 100 e 50 (CT-53…CT-56) |
| `remember.per_page = false` do pacote | P-16 determina o comportamento (não guarda); a chave é mecanismo | oráculo: o `state` gravado não tem tamanho de página e aplicar não o muda (CT-24, CT-39) |
| `getDefaultTableViewKey()` só sobre `ownedTableViewsQuery` | P-14 determina o comportamento; o método é mecanismo | oráculo: a padrão própria se aplica, a de colega não (CT-57) |
| Tamanho emitido "sempre múltiplo do bloco" e "nunca acima do tamanho que o servidor emitiu" (ADR, `InfiniteScrollHook::sync()`) | mecanismo do pacote; o requisito fixa "bloco do tamanho de Linhas por página" e "nunca mais de 500" | oráculo de CT-45 é "o bloco exibido não muda"; o de CT-44 linha `99` é "no máximo 500"; o múltiplo não é afirmado |
| `applyTableView()` devolve `false` com as visões desligadas (ADR) | mecanismo | oráculo de CT-07 é "o recorte não é aplicado" |
| Texto do rodapé `Mostrando N de M` | texto do pacote; o requisito só diz que, no teto, o rodapé "pede busca ou filtro" | CT-44 afirma o pedido de busca/filtro; a contagem é afirmada pela quantidade de linhas, não pelo texto |
| Barreira só na Action (D6, confirmada na Q12), sem barreira no model `TableView` | afirmação negativa que dispensa controle de fronteira | evidência `vendor/sehsah/filament-remember-table/src/Models/TableView.php:class TableView:22` e o grep do 02 (`grep -rn "::find(\|findOrFail(" src` = 0) **e** cenário escrito como se ela fosse falsa: CT-20 (ação acionada pelo nome, sem o botão) e CT-33/CT-34 (`?view` pela rota) |

**Perguntas da rodada 1, já decididas pela sessão** (renumeradas na sequência da feature): Q6 → P-13 (nome obrigatório até 100 caracteres, até 50 visões por usuário por listagem, **nome repetido permitido** — a sessão divergiu da recomendação, que pedia unicidade) · Q7 → P-14 · Q8 → P-15 · Q9 → P-16 · Q10 → P-17 · **Q11 aberta, fora desta entrega** (recorte bloqueado em R10) · Q12, raia desenho → D6 (barreira só nas quatro Actions).

**Pergunta da rodada 2, decidida pela sessão**: Q13 → P-18 — o teto de 50 é contado **por organização** (a sessão divergiu da recomendação, que era somar: somar contradiria o isolamento por organização de P-07). Coberta por CT-61.

Nenhuma pergunta nova na rodada 3.

**Perguntas da revisão adversarial 1, decididas pela sessão**: Q14 → P-19 (gerenciar com o compartilhamento desligado grava `is_public` false em toda visão tocada) · Q15 → P-20 (o `master_global` não é exceção à posse) · Q16 → P-21 (nome só com espaços é recusado) · Q17 → P-22 (`?view` na URL vence a padrão) · Q18 → P-23 (com exatamente 500 registros tudo carrega e o rodapé não pede busca nem filtro) · Q19 → P-24 (valor ilegível gravado é coagido a `paginacao` ao preencher a tela e regravado assim) · Q20 → P-25 (documentação pt e en). Nenhuma pergunta nova nesta rodada.

**Perguntas da revisão adversarial 2, decididas pela sessão**: Q21 → P-26 (substitui P-19: "visão tocada" = toda visão enviada pelo formulário de gerenciar) · Q22 → P-27 (o `master_global` não vê nem aplica visão privada de outro usuário). Nenhuma pergunta nova no fechamento da rodada 2.

## Setup Global

### Personas
- Suíte `tests/Kit` (single-tenant): `usuarioDoKit('admin', 'ana@example.com')` (Ana), `usuarioCom('admin')` (Bia, colega de painel), `usuarioDoKit('infra', 'ivo@example.com')` (Ivo, painel /infra), `usuarioCom('master_global')` (só **controle**: vence toda permissão pelo `Gate::before`).
- Persona sem a permissão: `semAPermissao('admin', 'Usar:VisoesDeTabela')` — revoga do papel REAL, para a ocultação não vir da porta do painel (`tests/Pest.php`). A visão de partida dela é gravada por `visaoGravada()`, que não depende da permissão.
- Suíte `tests/Tenancy` (multi-organização): `tenant('Acme','acme')`, `tenant('Globex','globex')`; `usuarioComPapel('admin_app', $acme, …)` (Ana e Bia na Acme; Carla na Globex); Dani `admin_app` nas duas (`papelNaOrganizacao()` + `tenants()->attach()`) — a persona que separa organização de usuário; Edu `panel_user` na Acme; Fábio `admin_app` na Globex e `panel_user` na Acme, com a Globex como organização da sessão (a persona que separa organização aberta de organização da sessão — ADV-07); Gil `admin` global (sem papel em organização), para as listagens do `/admin` na suíte Tenancy.
- Antes do primeiro `Livewire::test()` do `/app`: `$this->actingAs($x)->get('/app/acme/projetos')->assertOk(); noPainelDa($acme);` (`.ai/rules/testes.md`).

### Fixtures
- **Seeders**: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` no `beforeEach` dos arquivos de G5, G6, G7, G8.
- **Visão gravada**: helper novo em `tests/Pest.php`, `visaoGravada(User $dono, string $pagina, array $estado, array $atributos = [])` — grava `TableView` direto pelo model com `table_key` igual à **chave que a própria página usa**, obtida por `chaveDaTabela(string $pagina)`, que monta a página e chama o método público `getTableViewsKey()` dela (nome do vendor, não oráculo — ADV-50 substitui a decisão de ADV-29). O oráculo de "a visão pertence à página" é a **distinção** entre as chaves (CT-78) e a visão gravada pela tela levar a chave da página aberta (CT-24), nunca o formato da string. `tenant_id` vem dos atributos. A visão **não tem ciclo de vida** (sem status, sem máquina de estados): a partida por gravação direta é autorizada, como os favoritos do dock.
- **Interruptores**: helpers novos em `tests/Pest.php` (usados por G4/G5/G6/G7 em mais de um arquivo — `.ai/rules/testes.md`): `ligarVisoesDeTabela(bool $visoes = true, bool $compartilhadas = false)` (config `kit.tabelas.visoes_salvas` e `remember-table.allow_public_views`), `ligarCarregamento(string $modo, int $linhas = 7)` (config `kit.tabelas.carregamento` e `kit.tabelas.paginacao`), `concederVisoesDeTabela(string ...$papeis)` (molde `concederDock()`, com `Permission::findOrCreate`). Cenário que prova a **cadeia do Settings** grava na tabela (`gravarConfiguracao()` + `alinharConfiguracoesDoKit()`) ou pela tela, nunca só `config()`.
- **Linhas por página = 7** nos cenários de carregamento: diferente do default do kit (10) e do default do pacote (25) — o valor que discrimina "bloco = Linhas por página".
- **Teto**: o `Dado` de CT-44 afirma o valor efetivo lido (`config('filament-infinite-scroll.max_records')` = 500, sem ajuste do teste e sem chave no `phpunit.xml`); o `Então` usa o 500 literal de P-10.
- Registros em volume (CT-44: 501 usuários; CT-43/CT-45/CT-47: 30): inserção em lote pelo query builder; nenhum `Event::fake()` antes das factories.
- `composerDoKit()` vive hoje em `tests/Kit/PacotesRodada2Test.php`; G10 o usa num segundo arquivo → **mover para `tests/Pest.php`** (`.ai/rules/testes.md`, sem clone com outro nome).
- Abas (CT-39, CT-47): `ListUsers` e `ListConvites` (do `/admin` e do `/app`) já declaram `getTabs()` — as linhas de aba usam `/admin/users`. Agrupamento: nenhuma das 10 listagens o declara — as linhas de agrupamento usam uma página de listagem de teste em `tests/Kit/fixtures` com uma tabela agrupável (molde `tests/Browser/Fixtures/WidgetDeTeste.php`); se o fixture não for viável, a linha vira lacuna declarada com o que foi tentado.
- Teto de visões (CT-55): o `Dado` afirma o valor efetivo lido (`config('remember-table.max_views_per_table')` = 50, sem ajuste do teste); o `Então` usa o 50 literal de P-13. As 49/50 visões de partida são gravadas por `visaoGravada()` em lote.
- Tabelas do kit carregam adiadas (`deferLoading` global): todo caso de componente que afirma linhas chama `->loadTable()` antes.

### Fakes
- Nenhum `Mail`/`Queue`/`Http` fake: a feature não envia nada. Notificação exibida: `assertNotified()` (apoio, nunca oráculo único).
- `DB::listen` para a contagem de consultas (CT-06), com controle positivo no mesmo cenário (molde `DockDeAtalhosTenancyTest` CT-72).

### Estratégia de DB
- `RefreshDatabase` global por pasta (`tests/Pest.php`): `Kit`, `Tenancy`, `Browser`. A migration de `remember_table_views` publicada em `database/migrations` roda com as demais.

### Testes existentes que precisam continuar verdes (regressão, não CT novo)
- `tests/Kit/DefaultsDeTabelaTest.php` CT-17…CT-20 (os quatro defaults de tabela de hoje).
- `tests/Kit/PermissoesDeAcoesTest.php` — o caso de painel declarado para toda custom permission. O inventário de Actions **não** ganha linha: ele varre `Action::make(` em `app/Filament/**`, e as quatro Actions são construídas no vendor (`HasRememberTable`); linha sem ocorrência vira órfã e reprova. A prova da permissão é CT-20/CT-21.
- `tests/Kit/QualidadeDeCodigoTest.php` CT-01, CT-04, CT-05, CT-06 (as exceções de `ignoreErrors` continuam 3).
- `tests/Kit/HelpersDeTesteTest.php`, `tests/Kit/AderenciaAoBlueprintTest.php`, `tests/Kit/KitArteTest.php` (todo quadro declarado tem quem o capture).

---

## Regra R1 — Tudo nasce desligado na fábrica

> `RQ-05`, `P-02`, `P-10` · perfil **padrão** · técnica: **EP** (fonte do valor × chave). O `Dado` afirma o valor efetivo lido sem a variável no ambiente — não depende do `phpunit.xml`.

```gherkin
# language: pt
Funcionalidade: Interruptores das tabelas nascem desligados

  Regra: numa instalação nova o carregamento é paginacao, as visões estão desligadas e o compartilhamento também

    Esquema do Cenário: [CT-01] o valor de fábrica de cada interruptor é o desligado, em cada fonte
      Dado o ambiente do processo sem a variável <variavel>
      Quando a configuração é lida de <fonte>
      Então o valor efetivo de <chave> é <valor>

      Exemplos:
        | fonte                       | variavel                         | chave                             | valor     |
        | config/kit.php              | KIT_TABELA_CARREGAMENTO          | tabelas.carregamento              | paginacao |
        | config/kit.php              | KIT_TABELA_VISOES_SALVAS         | tabelas.visoes_salvas             | false     |
        | config/remember-table.php   | KIT_TABELA_VISOES_COMPARTILHADAS | allow_public_views                | false     |
        | .env.example (linha comentada) | —                             | KIT_TABELA_CARREGAMENTO           | paginacao |
        | .env.example (linha comentada) | —                             | KIT_TABELA_VISOES_SALVAS          | false     |
        | .env.example (linha comentada) | —                             | KIT_TABELA_VISOES_COMPARTILHADAS  | false     |

      <!-- *(alterado em 2026-10-14: o `.env.example` do kit traz TODA chave de tabela COMENTADA com o default — `# KIT_TABELA_PAGINACAO=10` — porque o banco vence e o arquivo é semente; "linha ativa" era sobre-especificação da derivação, não requisito. O oráculo passa a ser a linha comentada `# {chave}={valor}`, uma só.)* -->

    Esquema do Cenário: [CT-02] a migration de settings semeia os três interruptores desligados e o rollback os remove
      Dado a configuração de fábrica, sem ajuste do teste
      E a migration de settings dos interruptores já aplicada pelo RefreshDatabase
      Quando a migration roda <operacao>
      Então <resultado>

      Exemplos:
        | operacao                  | resultado                                                                                                   |
        | o down()                  | tabela_carregamento, visoes_de_tabela e visoes_de_tabela_compartilhadas não existem no grupo kit            |
        | o down() e depois o up()  | tabela_carregamento está gravada como a string "paginacao", e as outras duas como false                     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M01 | `visoes_salvas` com default `true` em `config/kit.php` | CT-01 | linha `config/kit.php · tabelas.visoes_salvas`: esperado `false`; o mutante lê `true` |
| M02 | o kit não sobrepõe `allow_public_views` do pacote (default `true` no vendor) | CT-01 | linha `config/remember-table.php`: esperado `false`; o mutante devolve `true` |
| M03 | `.env.example` com `KIT_TABELA_VISOES_SALVAS=true` (o `create-project` o copia para o `.env`) | CT-01 | linha `.env.example · KIT_TABELA_VISOES_SALVAS`: esperado `false`; o mutante tem `true` |
| M04 | migration semeia `rolagem` (o modo default do pacote é `scroll`) | CT-02 | `tabela_carregamento` gravado `"paginacao"`; o mutante grava `"rolagem"` |
| M05 | migration esquece `visoes_de_tabela_compartilhadas` | CT-02 | a propriedade precisa existir gravada `false`; o mutante não a cria |
| M139 | ADV-32 — o `down()` não remove as propriedades (rollback incompleto) | CT-02 | linha `o down()`: as três não existem; o mutante as mantém |

---

## Regra R2 — Em `paginacao` a tabela é a de hoje

> `RQ-05`, `P-10` · perfil **padrão** · técnica: **EP** + controle. Um cenário: as três asserções são o mesmo comportamento ("com paginacao nada muda em relação a hoje").

```gherkin
# language: pt
Funcionalidade: Carregamento da tabela no modo de hoje

  Regra: com paginacao nenhum carregamento contínuo é registrado e o seletor de registros por página continua

    Esquema do Cenário: [CT-03] em paginacao a tabela continua paginada como hoje
      Dado o carregamento da tabela em "paginacao" e Linhas por página igual a 25
      Quando a tabela de <tabela> é montada
      Então nenhuma configuração de carregamento contínuo está registrada para ela
      E as opções de registros por página são exatamente as que a mesma tabela oferece na `main` (lista literal fixada no teste)
      E o tamanho de página inicial é 25

      <!-- *(alterado em 2026-10-14: 7 não está entre as opções de página do Filament (5, 10, 25, 50), e aí a tabela começa pela primeira opção — comportamento de HOJE, que "nada muda" preserva; o cenário usa um valor da lista para o tamanho inicial ser falsificável. O caso do valor fora da lista é o CT-17 de `settings-do-kit`.)* -->

      Exemplos:
        | tabela                          | # origem            |
        | usuários do /admin (ListUsers)  | do kit              |
        | auditoria do /infra             | plugin de terceiro  |

    Cenário: [CT-71] em paginacao a listagem carregada não tem rodapé de carregamento contínuo e mostra o seletor
      Dado o carregamento "paginacao", Linhas por página igual a 5 e 10 usuários
      Quando o master_global abre /admin/users e a tabela carrega
      Então a tabela exibe 5 usuários com a paginação numerada

      <!-- *(alterado em 2026-10-14, onda 2: mesmo motivo do CT-03 — 7 não está nas opções de página e a tabela começaria pela primeira.)* -->
      E o HTML da tabela não tem o rodapé do carregamento contínuo nem o botão "Carregar mais"
      E o seletor de registros por página aparece
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M06 | `aplicaCarregamento()` registra o contínuo mesmo em `paginacao` (cai no modo default do pacote) | CT-03 | registro de carregamento contínuo esperado nulo; o mutante registra `Scroll` |
| M07 | `paginated([$linhas])` aplicado em todo modo | CT-03 | opções exatamente as da `main`; o mutante deixa só `[7]` |
| M08 | em `paginacao` o tamanho inicial passa a vir do pacote (25) | CT-03 | tamanho inicial 7; o mutante devolve 25 |
| M136 | ADV-27 — o rodapé do pacote renderizado em toda tabela, mesmo sem carregamento contínuo | CT-71 | o HTML não tem o rodapé nem "Carregar mais"; o mutante os renderiza |

---

## Regra R3 — Com as visões desligadas nada é oferecido, consultado, aplicado nem executado

> `RQ-05`, `P-03` · perfil **completo** · técnica: **matriz estado × operação** (linha E1) + **rastreio de efeito** (consulta ao banco: não aconteceu quando não devia, com controle positivo). No teto (5).

```gherkin
# language: pt
Funcionalidade: Visões salvas desligadas

  Regra: desligadas, as visões não aparecem, não são consultadas, não se aplicam e as ações não executam

    Esquema do Cenário: [CT-04] desligadas, as listagens dos painéis não oferecem o botão Visões nem as ações
      Dado as visões <visoes> e o compartilhamento <compartilhamento>
      E <persona> com a permissão Usar:VisoesDeTabela
      Quando <persona> abre a listagem <listagem>
      Então o botão Visões <oferecido> oferecido
      E as ações saveTableView, updateTableView, manageTableViews e resetTableView <oferecido> oferecidas

      Exemplos:
        | listagem                          | persona     | visoes     | compartilhamento | oferecido |
        | /admin/users                      | Ana (admin) | desligadas | desligado        | não é     |
        | /admin/users                      | Ana (admin) | desligadas | ligado           | não é     |
        | /admin/convites                   | Ana (admin) | desligadas | desligado        | não é     |
        | /admin/agentes-ia                 | Ana (admin) | desligadas | desligado        | não é     |
        | /admin/shield/roles               | Ana (admin) | desligadas | desligado        | não é     |
        | /infra/ai-runs                    | Ivo (infra) | desligadas | desligado        | não é     |
        | /infra/composer-release-packages  | Ivo (infra) | desligadas | desligado        | não é     |
        | /admin/users                      | Ana (admin) | ligadas    | desligado        | é         |
        | /admin/convites                   | Ana (admin) | ligadas    | desligado        | é         |
        | /admin/agentes-ia                 | Ana (admin) | ligadas    | desligado        | é         |
        | /admin/shield/roles               | Ana (admin) | ligadas    | desligado        | é         |
        | /infra/ai-runs                    | Ivo (infra) | ligadas    | desligado        | é         |
        | /infra/composer-release-packages  | Ivo (infra) | ligadas    | desligado        | é         |

    Esquema do Cenário: [CT-05] desligadas, as listagens da organização não oferecem o botão Visões
      Dado as visões <visoes>
      E <persona> com a permissão Usar:VisoesDeTabela
      Quando <persona> abre a listagem <listagem>
      Então o botão Visões <oferecido> oferecido

      Exemplos:
        | listagem              | persona                  | visoes     | oferecido |
        | /app/acme/projetos    | Ana (admin_app da Acme)  | desligadas | não é     |
        | /app/acme/users       | Ana (admin_app da Acme)  | desligadas | não é     |
        | /app/acme/convites    | Ana (admin_app da Acme)  | desligadas | não é     |
        | /admin/organizacoes   | admin global             | desligadas | não é     |
        | /app/acme/projetos    | Ana (admin_app da Acme)  | ligadas    | é         |
        | /app/acme/users       | Ana (admin_app da Acme)  | ligadas    | é         |
        | /app/acme/convites    | Ana (admin_app da Acme)  | ligadas    | é         |
        | /admin/organizacoes   | admin global             | ligadas    | é         |

    Esquema do Cenário: [CT-06] desligadas, abrir a listagem não consulta as visões gravadas
      Dado as visões <visoes>
      E a Ana com uma visão gravada em /admin/users
      Quando a Ana abre /admin/users por HTTP
      Então o número de consultas à tabela remember_table_views no request é <consultas>

      Exemplos:
        | visoes     | consultas   | # papel   |
        | desligadas | 0           | prova     |
        | ligadas    | ao menos 1  | controle  |

    Esquema do Cenário: [CT-07] desligadas, nenhuma visão gravada é aplicada
      Dado as visões desligadas e o compartilhamento ligado
      E <visao>, gravada com a busca "zz"
      Quando a Ana <acao>
      Então a busca da tabela da Ana está vazia
      E o nome da visão não aparece na listagem

      Exemplos:
        | visao                                          | acao                                              |
        | a visão padrão da Ana em /admin/users          | abre /admin/users                                 |
        | a visão da Ana em /admin/users                 | abre /admin/users com ?view={id da visão}         |
        | a visão da Ana em /admin/users                 | chama applyTableView com o id da visão            |
        | a visão compartilhada da Bia em /admin/users   | chama applyTableView com o id da visão da Bia     |

    Esquema do Cenário: [CT-08] desligadas, as ações acionadas pelo nome não executam
      Dado as visões desligadas
      E a Ana com a permissão Usar:VisoesDeTabela e a visão "Ativos" gravada em /admin/users com a busca "zz"
      E a tabela de /admin/users com a busca "ab"
      Quando a Ana aciona pelo nome a ação <acao> com <dados>
      Então <nao_efeito>
      E a listagem continua exibindo os usuários com a busca "ab"

      Exemplos:
        | acao              | dados                                            | nao_efeito                                       |
        | saveTableView     | nome "Nova"                                      | remember_table_views continua com 1 linha        |
        | updateTableView   | — (com "Ativos" ativa)                           | o state de "Ativos" continua com a busca "zz"    |
        | manageTableViews  | "Ativos" renomeada para "X"                      | "Ativos" continua gravada com o nome "Ativos"    |
        | manageTableViews  | a lista vazia (pedido de apagar "Ativos")        | "Ativos" continua gravada                        |
        | resetTableView    | —                                                | a busca da tabela continua "ab"                  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M09 | a guarda vive só em `getTableViewActions()` (botão); as Actions continuam montáveis pelo nome | CT-08 | linha `saveTableView`: 1 linha em `remember_table_views`; o mutante grava a segunda |
| M10 | a guarda lê `remember-table.allow_public_views` em vez de `kit.tabelas.visoes_salvas` | CT-04 | linha `/admin/users · desligadas · ligado`: "não é oferecido"; o mutante oferece |
| M11 | `bootedHasRememberTable()` não curto-circuitado: a visão padrão se aplica com as visões desligadas | CT-07 | linha "visão padrão": busca vazia; o mutante põe "zz" |
| M12 | o render esconde o botão mas `getTableViews()` ainda consulta | CT-06 | linha `desligadas`: 0 consultas; o mutante faz ≥ 1 |
| M13 | o trait entra em parte das 10 listagens (esquece as do `/app` ou a de organizações) | CT-05, CT-04 | linhas `ligadas`: "é oferecido"; o mutante não oferece em `/app/acme/projetos` |
| M14 | `findTableView()` sem a guarda: `?view={id}` aplica com as visões desligadas | CT-07 | linha `?view`: busca vazia; o mutante aplica "zz" |

---

## Regra R4 — Vocabulário fechado do carregamento; fora dele, `paginacao` sem erro

> `P-11`, `P-10` · perfil **padrão** · técnica: **EP** (cada inválida isolada) + **estado exibido exaustivo** + **normalização**. Estouro do teto (5 > 3; CT-76 nasce de ADV-44, que separou a exibição coagida da regravação): criação ≠ edição ≠ uso — o valor ilegível é exercitado na função (CT-09), no uso pela listagem (CT-11) e na edição pela tela (CT-12), três pontos distintos; CT-10 é a partição exaustiva do enum.

```gherkin
# language: pt
Funcionalidade: Vocabulário do carregamento da tabela

  Regra: só paginacao, rolagem e botao são valores; o resto vira paginacao

    Esquema do Cenário: [CT-09] o carregamento aceita só os três valores e o resto cai em paginacao
      Dado o valor bruto <bruto> para o carregamento da tabela
      Quando o valor é coagido ao vocabulário do carregamento
      Então o carregamento resultante é <resultado>

      Exemplos:
        | bruto        | resultado | # partição                     |
        | "paginacao"  | paginacao | válido                         |
        | "rolagem"    | rolagem   | válido                         |
        | "botao"      | botao     | válido                         |
        | "scroll"     | paginacao | valor do pacote, não do kit    |
        | "button"     | paginacao | valor do pacote, não do kit    |
        | "ROLAGEM"    | paginacao | caixa                          |
        | " rolagem"   | paginacao | espaço na borda                |
        | "botão"      | paginacao | acento                         |
        | ""           | paginacao | vazio                          |
        | null         | paginacao | ausente                        |
        | 1            | paginacao | tipo errado (inteiro)          |
        | ["rolagem"]  | paginacao | tipo errado (lista)            |

    Esquema do Cenário: [CT-10] cada um dos três valores tem o seu modo, e são exatamente três
      Dado o carregamento <valor>
      Quando o modo do carregamento contínuo correspondente é resolvido
      Então o modo é <modo>
      E as opções oferecidas do carregamento são exatamente paginacao, rolagem e botao

      Exemplos:
        | valor     | modo                     |
        | paginacao | nenhum (não é contínuo)  |
        | rolagem   | Scroll                   |
        | botao     | Button                   |

    Esquema do Cenário: [CT-11] um carregamento ilegível gravado não derruba a listagem e ela fica paginada
      Dado o carregamento "xpto" vindo de <fonte>
      E o master_global autenticado
      Quando ele abre /admin/users
      Então a página responde 200 com a listagem de usuários
      E a tabela de /admin/users não tem carregamento contínuo registrado

      Exemplos:
        | fonte                                                        |
        | o banco (gravarConfiguracao + alinhamento do boot)            |
        | a variável KIT_TABELA_CARREGAMENTO relida em config/kit.php   |

    Cenário: [CT-12] um carregamento ilegível gravado aparece coagido a paginacao ao abrir a tela
      Dado o carregamento "xpto" gravado no banco
      E o admin autenticado
      Quando o admin abre a tela de configurações
      Então o campo de carregamento da aba Tabelas mostra "paginacao", antes de qualquer preenchimento pelo teste

    Cenário: [CT-76] salvar a tela com um carregamento ilegível gravado regrava paginacao
      Dado o carregamento "xpto" gravado no banco
      E o admin com a tela de configurações aberta, sem tocar no campo de carregamento
      Quando o admin muda o nome da aplicação para "Projeto Novo" e salva
      Então a tela não tem erro de formulário
      E nome_da_aplicacao está gravado como "Projeto Novo"
      E tabela_carregamento está gravado como a string "paginacao"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M15 | coerção por `InfiniteScrollMode::tryFrom()` (aceita o valor do pacote) | CT-09 | linhas `"scroll"`/`"button"`: `paginacao`; o mutante devolve rolagem/botao |
| M16 | coerção com `strtolower(trim())` | CT-09 | linhas `"ROLAGEM"`/`" rolagem"`: `paginacao`; o mutante devolve `rolagem` |
| M17 | `CarregamentoDaTabela::from()` (lança `ValueError`) no consumidor ou no `fill()` da tela | CT-11, CT-12 | CT-11 linha "banco": 200; o mutante responde 500. CT-12: sem erro; o mutante estoura |
| M18 | modo trocado (`rolagem` → `Button`) | CT-10 | linha `rolagem`: `Scroll`; o mutante devolve `Button` |
| M19 | enum com dois casos (sem `botao`) | CT-10, CT-09 | opções "exatamente três"; linha `"botao"` de CT-09 devolve `botao` |
| M137 | ADV-28/ADV-44 — a tela não coage o valor ilegível ao preencher o formulário (o Select abre vazio ou com `"xpto"`) | CT-12 | o campo mostra `paginacao` ao abrir, sem `fillForm` do teste; o mutante mostra vazio ou `"xpto"` |
| M149 | ADV-44 — a tela salva regrava o valor bruto (`"xpto"`) ou `null` em `tabela_carregamento` | CT-76 | `tabela_carregamento` gravado `"paginacao"`; o mutante grava `"xpto"`/`null` |

---

## Regra R5 — A aba *Tabelas* grava os três interruptores e o gravado governa por request

> `RQ-04`, `RQ-05` · perfil **padrão** · técnica: **EP** + **criação ≠ edição ≠ uso**. No teto (3): gate de tela de escrita (CT-13), recusa (CT-14) e os três lugares do Settings (`.ai/rules/settings.md`, CT-16). CT-15 (rodada 1 aqui) foi para R25 com o oráculo de P-15; M25 continua morto por ele.

```gherkin
# language: pt
Funcionalidade: Aba Tabelas das configurações

  Regra: o que o administrador grava na aba Tabelas chega a quem consome, no mesmo processo

    Cenário: [CT-13] a aba Tabelas grava o carregamento como texto e os dois interruptores
      Dado o carregamento "paginacao", as visões desligadas e o compartilhamento desligado gravados
      E o admin na tela de configurações
      Quando o admin escolhe o carregamento "botao", liga as visões e liga o compartilhamento, e salva
      Então a tela não tem erro de formulário
      E tabela_carregamento está gravado como a string "botao"
      E visoes_de_tabela e visoes_de_tabela_compartilhadas estão gravados como true
      E depois do alinhamento do boot o carregamento efetivo é "botao", as visões estão ligadas e o compartilhamento do pacote está ligado

    Esquema do Cenário: [CT-14] carregamento fora do vocabulário é recusado no campo e nada é gravado
      Dado o carregamento "rolagem" e as visões desligadas gravados
      E o admin na tela de configurações
      Quando o admin envia o carregamento <valor> junto com as visões ligadas e salva
      Então a tela tem erro no campo tabela_carregamento
      E tabela_carregamento continua "rolagem" e visoes_de_tabela continua false

      Exemplos:
        | valor        | # partição          |
        | "xpto"       | fora do vocabulário |
        | "scroll"     | valor do pacote     |
        | ["rolagem"]  | tipo errado         |

    Esquema do Cenário: [CT-16] cada interruptor gravado governa o consumidor no mesmo processo
      Dado <propriedade> gravada como <antes> e o alinhamento do boot feito
      E <consumidor> observado com <efeito_antes>, no mesmo processo de teste e sem request HTTP
      Quando <propriedade> é gravada como <depois> e o alinhamento do boot é refeito, sem novo request e sem remontar o painel
      Então <consumidor> passa a mostrar <efeito_depois>

      Exemplos:
        | propriedade                     | antes     | depois  | consumidor                                      | efeito_antes                        | efeito_depois                     |
        | tabela_carregamento             | paginacao | rolagem | a tabela de /admin/users                        | sem carregamento contínuo           | carregamento contínuo em Scroll   |
        | tabela_carregamento             | rolagem   | paginacao | a tabela de /admin/users                      | carregamento contínuo em Scroll     | sem carregamento contínuo         |
        | tabela_carregamento             | botao     | rolagem   | a tabela de /admin/users                      | carregamento contínuo em Button     | carregamento contínuo em Scroll   |
        | visoes_de_tabela                | false     | true    | a listagem /admin/users da Ana (com permissão)  | sem o botão Visões                  | o botão Visões                    |
        | visoes_de_tabela_compartilhadas | false     | true    | o formulário de saveTableView (visões ligadas)  | sem o campo Compartilhar com todos  | o campo Compartilhar com todos    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M20 | `Select::options(CarregamentoDaTabela::class)`: o estado volta como enum e grava serializado (ou estoura `TypeError` no `fill()`) | CT-13 | `tabela_carregamento` gravado `"botao"` (string crua) e sem erro; o mutante grava `{"value":"botao"}` ou a tela tem erro |
| M21 | linha de `visoes_de_tabela` ausente no `mapaDeConfiguracao()` | CT-16 | linha `visoes_de_tabela`: o botão aparece depois do alinhamento; o mutante continua sem botão |
| M22 | o compartilhamento mapeado para `kit.tabelas.visoes_compartilhadas` em vez de `remember-table.allow_public_views` | CT-13, CT-16 | CT-13: "compartilhamento do pacote ligado"; o mutante deixa `allow_public_views` desligado. CT-16 linha 3: o campo não aparece |
| M23 | Select sem validação de opção (aceita `"xpto"`) | CT-14 | linha `"xpto"`: erro no campo e `"rolagem"` mantido; o mutante grava `"xpto"` |
| M24 | o consumidor lê o interruptor uma vez (no `register`/`boot` do provider), não a cada montagem da tabela | CT-16 | linha `paginacao → rolagem`, observada no mesmo processo e sem request novo (nada reboota — ADV-31): Scroll; o mutante continua sem contínuo |
| M25 | o campo oculto do compartilhamento falta no estado e o `save()` estoura | CT-15 (R25) | "sem erro de formulário" e `visoes_de_tabela` false; o mutante lança |
| M128 | ADV-16 — o `configureUsing` do contínuo registrado uma vez, com o modo capturado no closure: desligar ou trocar o modo no mesmo processo não vale | CT-16 | linhas `rolagem → paginacao` (sem contínuo) e `botao → rolagem` (Scroll); o mutante continua no modo antigo |

---

## Regra R6 — `Usar:VisoesDeTabela` nasce em `admin`, `infra`, `admin_app`, não em `panel_user`, e é concedível

> `P-01`, `RQ-04` · perfil **completo** · técnica: **matriz papel × permissão** (as duas direções; semeadura repetida)

```gherkin
# language: pt
Funcionalidade: Permissão das visões de tabela

  Regra: a permissão existe, é oferecida na tela de papéis e nasce só nos papéis de administração

    Cenário: [CT-17] a permissão existe depois dos seeders e é oferecida na tela de papéis
      Dado os dois seeders rodados
      E o master_global na edição do papel panel_user
      Quando a tela de edição do papel é aberta
      Então a tela abre
      E a permissão Usar:VisoesDeTabela existe no guard web
      E ela está entre as permissões customizadas oferecidas para marcar

    Esquema do Cenário: [CT-18] na instalação single-tenant a permissão nasce no admin e no infra, e não no panel_user
      Dado os dois seeders rodados duas vezes
      Quando a permissão do papel <papel> é consultada
      Então o papel <tem> Usar:VisoesDeTabela
      E existe no máximo uma linha de Usar:VisoesDeTabela para o papel na pivot

      Exemplos:
        | papel      | tem     |
        | admin      | tem     |
        | infra      | tem     |
        | panel_user | não tem |

    Esquema do Cenário: [CT-19] na instalação multi-organização a permissão nasce também no admin_app, e não no panel_user
      Dado os dois seeders rodados com permission.teams ligado
      Quando a permissão do papel <papel> é consultada
      Então o papel <tem> Usar:VisoesDeTabela

      Exemplos:
        | papel      | tem     |
        | admin_app  | tem     |
        | admin      | tem     |
        | infra      | tem     |
        | panel_user | não tem |

    Cenário: [CT-67] a permissão marcada na tela de papéis libera as ações de escrita para o papel
      Dado as visões ligadas e os dois seeders rodados com permission.teams ligado
      E o Edu, panel_user da Acme, sem Usar:VisoesDeTabela, em /app/acme/projetos
      E o master_global marcou Usar:VisoesDeTabela no papel panel_user pela tela de papéis e salvou
      Quando o Edu aciona a ação saveTableView com o nome "Do Edu"
      Então a ação saveTableView é oferecida ao Edu
      E remember_table_views tem a visão "Do Edu" do Edu
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M26 | a permissão entra em todo papel (inclusive `panel_user`) | CT-18, CT-19 | linha `panel_user`: "não tem"; o mutante tem |
| M27 | painel declarado só `admin` em `paineisDasPermissoesCustomizadas()` | CT-18, CT-19 | linha `infra` (CT-18) e `admin_app` (CT-19): "tem"; o mutante não tem |
| M28 | criada pelo seeder sem entrar em `custom_permissions` (não oferecida na tela) | CT-17 | "entre as permissões customizadas oferecidas"; o mutante não está em `getCustomPermissions()` |
| M29 | `givePermissionTo` cumulativo duplica a linha da pivot na segunda semeadura | CT-18 | "no máximo uma linha"; o mutante deixa 2 |
| M120 | ADV-09 — a tela de papéis oferece a permissão com um nome diferente do que o `authorize` consulta (marcar grava outra permissão) | CT-67 | saveTableView oferecida e a visão gravada; o mutante continua ocultando a ação |

---

## Regra R7 — Salvar, atualizar, gerenciar e limpar exigem a permissão; aplicar não

> `P-08`, `P-01` · perfil **completo** · técnica: **matriz papel × ação** (persona com/sem permissão × 5 verbos; verbo irmão não herda evidência — cada verbo executado). Gate de camada: CT-20 aciona cada ação **pelo nome**, sem o botão — o chamador "por fora" da UI que o cliente alcança (`$wire.mountAction`); decisão D6, confirmada na Q12.

```gherkin
# language: pt
Funcionalidade: Autorização das ações de visão

  Regra: só quem tem Usar:VisoesDeTabela salva, atualiza, gerencia e limpa; quem abre a listagem aplica

    Esquema do Cenário: [CT-20] sem a permissão, cada ação de escrita fica oculta e, acionada pelo nome, não executa
      Dado as visões e o compartilhamento ligados
      E a Ana, admin com a permissão Usar:VisoesDeTabela revogada, dona da visão "Ativos" gravada em /admin/users com a busca "zz"
      E a tabela de /admin/users da Ana com a visão "Ativos" ativa e a busca alterada para "ab"
      Quando a Ana aciona pelo nome a ação <acao> com <dados>
      Então a ação <acao> não é oferecida à Ana
      E <nao_efeito>
      E a listagem continua exibindo os usuários com a busca "ab"

      Exemplos:
        | acao             | dados                                        | nao_efeito                                     |
        | saveTableView    | nome "Nova"                                  | remember_table_views continua com 1 linha      |
        | updateTableView  | —                                            | o state de "Ativos" continua com a busca "zz"  |
        | manageTableViews | "Ativos" renomeada para "X"                  | "Ativos" continua gravada com o nome "Ativos"  |
        | manageTableViews | a lista vazia (pedido de apagar "Ativos")    | "Ativos" continua gravada                      |
        | resetTableView   | —                                            | a busca da tabela continua "ab"                |

    Esquema do Cenário: [CT-21] com a permissão, cada ação de escrita é oferecida e executa
      Dado as visões ligadas
      E a Ana, admin com Usar:VisoesDeTabela, dona da visão "Ativos" gravada em /admin/users com a busca "zz"
      E a tabela de /admin/users da Ana com a visão "Ativos" ativa e a busca alterada para "ab"
      Quando a Ana aciona a ação <acao> com <dados>
      Então a ação <acao> é oferecida
      E <efeito>

      Exemplos:
        | acao             | dados                      | efeito                                          |
        | saveTableView    | nome "Nova"                | remember_table_views tem 2 linhas, uma "Nova"   |
        | updateTableView  | —                          | o state de "Ativos" passa a ter a busca "ab"    |
        | manageTableViews | "Ativos" renomeada "Meus"  | a visão gravada passa a se chamar "Meus"        |
        | resetTableView   | —                          | a busca da tabela fica vazia                    |

    Cenário: [CT-22] o admin sem a permissão aplica a visão compartilhada de um colega
      Dado as visões e o compartilhamento ligados
      E a Bia com a visão "Pendentes" compartilhada em /admin/users com a busca "zz"
      E a Ana, admin com a permissão Usar:VisoesDeTabela revogada
      Quando a Ana aplica a visão "Pendentes"
      Então a busca da tabela da Ana é "zz"
      E as ações saveTableView, updateTableView, manageTableViews e resetTableView não são oferecidas à Ana
      E o botão Visões e a opção de aplicar "Pendentes" são oferecidos à Ana

    Cenário: [CT-23] o usuário comum da organização aplica a visão compartilhada do administrador dela
      Dado as visões e o compartilhamento ligados
      E a Ana, admin_app da Acme, com a visão "Atrasados" compartilhada em /app/acme/projetos com a busca "zz"
      E o Edu, panel_user da Acme, sem Usar:VisoesDeTabela
      Quando o Edu aplica a visão "Atrasados" em /app/acme/projetos
      Então a busca da tabela do Edu é "zz"
      E a ação saveTableView não é oferecida ao Edu
      E o botão Visões e a opção de aplicar "Atrasados" são oferecidos ao Edu

    Esquema do Cenário: [CT-66] a permissão vale no papel da organização aberta, não no da organização da sessão
      Dado as visões ligadas
      E o Fábio, admin_app na Globex e panel_user na Acme, com a Globex como organização da sessão
      E o Fábio em /app/<org>/projetos
      Quando o Fábio aciona pelo nome a ação saveTableView com o nome "Do Fábio"
      Então a ação saveTableView <oferecida> oferecida ao Fábio
      E remember_table_views tem <linhas> visão(ões) do Fábio

      Exemplos:
        | org    | oferecida | linhas | # papel na organização aberta |
        | acme   | não é     | 0      | panel_user                    |
        | globex | é         | 1      | admin_app                     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M30 | `->authorize()` só no `saveTableView` (verbo irmão esquecido) | CT-20 | linhas `updateTableView`/`manageTableViews`/`resetTableView`: estado mantido; o mutante altera |
| M31 | permissão consultada só na visibilidade do `ActionGroup` (botão), não em cada Action | CT-20 | linha `saveTableView`: 1 linha; acionada pelo nome, o mutante grava a segunda |
| M32 | aplicar uma visão também exige a permissão | CT-22, CT-23 | busca "zz" aplicada; o mutante deixa a busca vazia |
| M33 | `authorize` com a chave da config (`usar:visoesdetabela`) em vez do nome semeado | CT-21 | "a ação é oferecida" à Ana com a permissão; o mutante esconde de todos |
| M34 | condição invertida (`! can`) | CT-20, CT-21 | CT-21: oferecida e executa; o mutante oculta. CT-20: não executa; o mutante executa |
| M118 | ADV-07 — a permissão resolvida no time da organização da sessão (`tenant_corrente`), não da organização aberta | CT-66 | linha `acme`: não oferecida e 0 visões; o mutante oferece e grava (Globex na sessão) |
| M119 | ADV-08 — o grupo *Visões* inteiro oculto para quem não tem a permissão (aplicar só pelo nome) | CT-22, CT-23 | "o botão Visões e a opção de aplicar são oferecidos"; o mutante os oculta |

---

## Regra R8 — Uma visão gravada pertence a quem gravou, à página e à organização aberta

> `P-07`, `P-02`, `P-16` · perfil **completo** · técnica: **EP** + **mass assignment** (por tabela: `remember_table_views`). Gate de tela de escrita: CT-24 é a gravação por componente do modal *Salvar como nova visão*. Rodada 2: CT-24 ganhou a asserção de P-16 (o `state` não guarda tamanho de página; 25 ≠ o default 10 para discriminar).

```gherkin
# language: pt
Funcionalidade: Gravação de uma visão

  Regra: dono, página, organização e compartilhamento vêm do contexto, nunca do formulário

    Cenário: [CT-24] salvar grava o recorte da tabela com a dona, a página e sem compartilhar
      Dado as visões ligadas e o compartilhamento desligado
      E a Ana, admin com a permissão, em /admin/users com a busca "ana", a ordenação por e-mail e 25 registros por página
      Quando a Ana salva a visão com o nome "Minha visão"
      Então existe uma visão "Minha visão" com o usuário Ana, table_key igual à chave que a página /admin/users usa e tenant_id nulo
      E o state dela tem a busca "ana" e a ordenação por e-mail
      E o state dela não tem tamanho de página
      E is_public está gravado como false

    Esquema do Cenário: [CT-25] campos forjados no formulário de salvar são ignorados
      Dado as visões ligadas e o compartilhamento desligado
      E a Ana, admin com a permissão, em /admin/users, e a Bia, outra admin
      Quando a Ana salva a visão "Forjada" com o campo <campo> forjado como <valor>
      Então a visão "Forjada" tem <campo> igual a <gravado>

      Exemplos:
        | campo      | valor                       | gravado                    |
        | user_id    | o id da Bia                 | o id da Ana                |
        | table_key  | a chave que a página /admin/convites usa | a chave que a página /admin/users usa |
        | is_public  | true                        | false                      |
        | is_public  | ausente do payload          | false                      |

    Esquema do Cenário: [CT-26] a organização da visão é a organização aberta, não a do payload nem a da sessão
      Dado as visões ligadas
      E a Dani, admin_app na Acme e na Globex, com a Globex como organização da sessão
      Quando a Dani salva a visão "Da Acme" em /app/acme/projetos com <forjado>
      Então a visão "Da Acme" tem tenant_id igual ao id da Acme

      Exemplos:
        | forjado                          |
        | nenhum campo forjado             |
        | tenant_id forjado como a Globex  |

    Cenário: [CT-78] cada página de listagem tem a sua chave de visões, e a visão gravada pela tela leva a da página aberta
      Dado as visões ligadas
      E a Ana, admin com a permissão, em /admin/users
      Quando a Ana salva a visão "Desta página"
      Então a chave de visões da página /admin/users, a da página do /app de usuários e a da página /admin/convites são três valores distintos
      E "Desta página" está gravada com a chave de /admin/users
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M35 | `$data` do formulário mesclado no `create()` (dono vindo do payload) | CT-25 | linha `user_id`: id da Ana; o mutante grava o da Bia |
| M36 | `is_public` gravado do payload mesmo com o compartilhamento desligado | CT-25 | linha `is_public · true`: false; o mutante grava true |
| M37 | `tenant_id` da sessão (`tenant_corrente`) ou do payload em vez da organização aberta | CT-26 | id da Acme; o mutante grava a Globex |
| M38 | `table_key` do Resource ou do slug (páginas homônimas compartilham a chave) | CT-78, CT-24, CT-25, CT-65 | CT-78: três chaves distintas; o mutante dá a mesma a `/admin/users` e ao `/app` de usuários (slug `users`). CT-65: a visão do `/app` não vale no `/admin` |
| M39 | salvar sem capturar o recorte (`state` vazio) | CT-24 | `state` com a busca "ana"; o mutante grava `[]` |

---

## Regra R9 — Só o dono atualiza, renomeia, reordena ou apaga a própria visão

> `P-08`, `P-07` · perfil **completo** · técnica: **matriz papel × ação** (dono × colega × id inexistente). Gate de tela de escrita: CT-27 é a gravação por componente do modal *Gerenciar visões*.

```gherkin
# language: pt
Funcionalidade: Gestão das visões

  Regra: o gerenciamento e a atualização só alcançam as visões do próprio usuário

    Cenário: [CT-27] gerenciar renomeia, reordena e apaga as visões da própria Ana
      Dado as visões ligadas
      E a Ana com as visões "A" (ordem 1), "B" (ordem 2) e "C" (ordem 3) em /admin/users
      Quando a Ana gerencia as visões enviando "C" renomeada para "Primeira" na primeira posição, "A" na segunda e "B" fora da lista
      Então a visão que era "C" se chama "Primeira" e tem a menor ordem
      E "A" vem depois dela
      E "B" não existe mais

    Esquema do Cenário: [CT-28] gerenciar com ids alheios ou inexistentes não alcança visão de outro usuário
      Dado as visões ligadas e o compartilhamento <compartilhamento>
      E a Ana com a visão "Minha" e a Bia com a visão "Da Bia" marcada compartilhada, as duas em /admin/users
      Quando a Ana gerencia as visões enviando <payload>
      Então a gestão termina sem erro
      E "Da Bia" continua gravada com o nome "Da Bia" e is_public true
      E <proprio>

      Exemplos:
        | compartilhamento | payload                                              | proprio                               | # por quê                                    |
        | desligado        | "Minha" mais o id de "Da Bia" renomeada para "Roubada" | "Minha" continua gravada              | "Da Bia" invisível à Ana                     |
        | ligado           | "Minha" mais o id de "Da Bia" renomeada para "Roubada" | "Minha" continua gravada              | "Da Bia" visível à Ana, mas não é dela       |
        | desligado        | só "Minha" na lista (pedido de apagar as demais)     | "Minha" continua gravada              | invisível                                    |
        | ligado           | só "Minha" na lista (pedido de apagar as demais)     | "Minha" continua gravada              | visível, mas não é dela                      |
        | desligado        | "Minha" mais o id 999999 (inexistente) renomeado para "Fantasma" | "Minha" continua gravada     | inexistente                                  |
        | ligado           | "Minha" mais o id 999999 (inexistente) renomeado para "Fantasma" | "Minha" continua gravada     | inexistente                                  |

      <!-- *(alterado em 2026-10-14, onda 2 do executor: o formulário de gerenciar SEMPRE reenvia todas as visões do usuário; um payload só com o id alheio/inexistente é, para o pacote, "apague as minhas que faltam" — e apagar o próprio dado por payload forjado não fere P-07/P-08. As linhas passam a enviar "Minha" junto do intruso, que é o que distingue "ignorar o alheio" de "apagar o próprio".)* -->
        | desligado        | "Minha" com is_public true                           | "Minha" continua com is_public false  | forjado com o compartilhamento desligado     |

    Esquema do Cenário: [CT-29] atualizar a visão ativa só vale para a visão do próprio usuário
      Dado as visões e o compartilhamento ligados
      E <ativa> ativa na tabela da Ana em /admin/users, gravada com a busca "zz"
      E a busca da tabela alterada para "ab"
      Quando a Ana aciona pelo nome a ação updateTableView
      Então o state de <ativa> tem a busca <busca>

      Exemplos:
        | ativa                                  | busca |
        | a visão "Minha" da Ana                 | "ab"  |
        | a visão "Da Bia" compartilhada da Bia  | "zz"  |

    Esquema do Cenário: [CT-64] campos forjados no gerenciamento são ignorados
      Dado as visões ligadas e o compartilhamento desligado
      E a Ana, admin com a permissão, com a visão "Minha" (busca "zz") em /admin/users, e a Bia, outra admin, sem visão padrão
      Quando a Ana gerencia as visões enviando "Minha" com o campo <campo> forjado como <valor>
      Então "Minha" tem <campo> igual a <gravado>
      E <efeito>

      Exemplos:
        | campo                        | valor                                                        | gravado                                                 | efeito                                                          |
        | user_id (com is_default true) | o id da Bia                                                 | o id da Ana                                             | a Bia, ao abrir /admin/users, fica com a busca vazia           |
        | table_key                    | a chave que a página /admin/convites usa                     | a chave que a página /admin/users usa                     | a ação de aplicar "Minha" existe para a Ana em /admin/users     |
        | tenant_id                    | "999"                                                        | nulo                                                    | a ação de aplicar "Minha" existe para a Ana em /admin/users     |

    Esquema do Cenário: [CT-72] o master_global também não altera a visão de outro usuário
      Dado as visões e o compartilhamento ligados
      E a Bia com a visão "Da Bia" compartilhada em /admin/users, com a busca "zz"
      E o master_global com "Da Bia" ativa na tabela e a busca alterada para "ab"
      Quando o master_global <acao>
      Então "Da Bia" continua gravada com o nome "Da Bia" e o state com a busca "zz"

      Exemplos:
        | acao                                                             |
        | gerencia as visões enviando "Da Bia" renomeada para "Do master"  |
        | gerencia as visões com a lista vazia (pedido de apagar)          |
        | aciona pelo nome a ação updateTableView                          |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M40 | o gerenciamento percorre os ids do payload sem filtrar pelo dono | CT-28 | linha "Roubada": "Da Bia" continua com o nome; o mutante renomeia |
| M41 | exclusão por `whereKeyNot($enviados)` sem `ownedBy` | CT-28 | linha "só Minha na lista": "Da Bia" continua gravada; o mutante a apaga |
| M42 | `updateTableView` permitido na visão compartilhada ativa de colega | CT-29 | linha "Da Bia": busca continua "zz"; o mutante grava "ab" |
| M43 | id inexistente resolvido por `findOrFail` (estoura a gestão inteira) | CT-28 | linha `999999`: "termina sem erro"; o mutante lança `ModelNotFoundException` |
| M44 | a gestão só apaga, não grava renomeação nem ordem | CT-27 | "Primeira" com a menor ordem; o mutante mantém "C" na ordem 3 |
| M112 | ADV-02 — o gerenciamento filtra por `visibleTo` (dono ou compartilhada visível) em vez de `ownedBy` | CT-28 | linhas `ligado`: "Da Bia" mantém o nome e continua gravada; o mutante a renomeia ou apaga |
| M113 | ADV-03 — o gerenciamento faz `update($data)` com `user_id`, `table_key`, `tenant_id` e `is_default` do payload | CT-64 | linha `user_id`: continua o id da Ana e a Bia não ganha padrão; o mutante transfere a visão e a aplica à Bia |
| M144 | ADV-39 — a posse decidida por policy, que o `Gate::before` do master atravessa | CT-72 | "Da Bia" mantém nome e busca "zz"; o mutante deixa o master renomear, apagar ou atualizar |

---

## Regra R10 — Visibilidade: dono, página e organização; compartilhada só na mesma organização/painel e só com o compartilhamento ligado

> `P-07`, `P-02` · perfil **completo** · técnica: **tabela de decisão** (condições: mesmo usuário? · mesma página? · mesma organização? · marcada compartilhada? · compartilhamento ligado?) + **IDOR** + **discriminante nulo** (fecha). Gate de camada: CT-33/CT-34 entram pela rota real (`?view={id}`), por fora do componente. Estouro do teto (9 > 5): CT-65, CT-73, CT-74 e CT-77 nascem dos achados ADV-04, ADV-40, ADV-41 e ADV-42 da revisão adversarial — cada um mata mutante que nenhum dos cinco anteriores mata. **Recorte bloqueado — Q11** (aberta, fora desta entrega): a visão compartilhada de autor que perdeu o vínculo com a organização ou teve a conta Inativa/Excluída — nenhum cenário afirma que ela aparece nem que some; o resto da regra segue coberto.
>
> Tabela de decisão colapsada (para o dono, o compartilhamento não muda nada; uma das três primeiras condições "não" já decide): **a** dono/mesma página/mesma organização → visível · **b** outro usuário/privada → invisível · **c** dono/outra página → invisível · **d** dono/outra organização → invisível · **e** outro usuário/compartilhada/ligado/mesma organização → visível · **f** outro usuário/compartilhada/desligado → invisível · **g** outro usuário/compartilhada/ligado/outra organização → invisível · **h** `tenant_id` nulo numa página do `/app` → invisível. 8 regras sobreviventes, todas com linha.

```gherkin
# language: pt
Funcionalidade: Visibilidade das visões

  Regra: uma visão só aparece para quem a gravou, na página e na organização em que foi gravada, salvo compartilhada na mesma organização com o compartilhamento ligado

    Esquema do Cenário: [CT-30] quem vê cada visão no painel single-tenant
      Dado as visões ligadas e o compartilhamento <compartilhamento>
      E a visão "Alvo" da <dona>, <marcada>, gravada em <pagina_gravada>
      Quando a Ana abre /admin/users
      Então a visão "Alvo" <aparece> na lista de visões da Ana
      E a ação de aplicar "Alvo" <existe> para a Ana

      Exemplos:
        | regra | dona | marcada        | pagina_gravada   | compartilhamento | aparece      | existe      |
        | a     | Ana  | privada        | /admin/users     | desligado        | aparece      | existe      |
        | a     | Ana  | privada        | /admin/users     | ligado           | aparece      | existe      |
        | b     | Bia  | privada        | /admin/users     | ligado           | não aparece  | não existe  |
        | c     | Ana  | privada        | /admin/convites  | desligado        | não aparece  | não existe  |
        | e     | Bia  | compartilhada  | /admin/users     | ligado           | aparece      | existe      |
        | f     | Bia  | compartilhada  | /admin/users     | desligado        | não aparece  | não existe  |

    Esquema do Cenário: [CT-31] quem vê cada visão na organização
      Dado as visões e o compartilhamento ligados
      E a visão "Alvo" <origem>, gravada com a busca "zz"
      E <ator> em /app/<org>/projetos
      Quando <ator> aplica o id de "Alvo"
      Então a busca da tabela é <busca>
      E a visão "Alvo" <aparece> na lista de visões

      Exemplos:
        | regra | origem                                                                    | ator                    | org    | aparece      | busca |
        | d     | da Dani, privada, gravada em /app/acme/projetos                           | a Dani (Acme e Globex)  | globex | não aparece  | ""    |
        | a     | da Dani, privada, gravada em /app/acme/projetos                           | a Dani (Acme e Globex)  | acme   | aparece      | "zz"  |
        | g     | da Ana (Acme), compartilhada, em /app/acme/projetos                       | a Carla (Globex)        | globex | não aparece  | ""    |
        | e     | da Ana (Acme), compartilhada, em /app/acme/projetos                       | a Bia (Acme)            | acme   | aparece      | "zz"  |
        | h     | da Bia, compartilhada, com tenant_id nulo e a chave da listagem de projetos | a Ana (Acme)           | acme   | não aparece  | ""    |

    Esquema do Cenário: [CT-32] a visão ativa forjada pelo cliente não aplica visão invisível nem quebra a tela
      Dado as visões ligadas e o compartilhamento desligado
      E a Bia com a visão privada "Da Bia" (busca "zz") e a Ana com a visão "Minha" (busca "ok"), em /admin/users, e a Ana com a visão "Convites" (busca "zz") em /admin/convites
      Quando a Ana <entrada> <valor>
      Então a busca da tabela da Ana é <busca>
      E a resposta não tem exceção

      Exemplos:
        | entrada                           | valor                       | busca | # partição                   |
        | chama applyTableView com          | o id de "Minha"             | "ok"  | controle                     |
        | chama applyTableView com          | o id de "Da Bia"            | ""    | outro usuário                |
        | chama applyTableView com          | o id de "Convites" da Ana   | ""    | dona, outra listagem         |
        | define activeTableView como       | o id de "Da Bia"            | ""    | outro usuário (propriedade)  |
        | chama applyTableView com          | o id de uma visão apagada   | ""    | registro removido            |
        | chama applyTableView com          | "preset-xpto"               | ""    | preset inexistente           |
        | define activeTableView como       | "abc"                       | ""    | fora do domínio              |
        | define activeTableView como       | "-1"                        | ""    | fora do domínio              |
        | define activeTableView como       | ["1"]                       | ""    | tipo errado — a resposta é RECUSADA com erro de tipo do framework (P-28), e a busca continua "" |

      <!-- *(alterado em 2026-10-14, onda 2: `activeTableView` é propriedade `string` do trait do vendor; o Livewire recusa o array com `TypeError` antes de qualquer lógica — nada é aplicado nem gravado. A linha afirma a recusa (exceção) e a busca inalterada em vez de "sem exceção". Premissa P-28 no `00`.)* -->

    Esquema do Cenário: [CT-33] a visão pedida pela URL só se aplica se for visível a quem abre
      Dado as visões ligadas e o compartilhamento <compartilhamento>
      E a Bia com a visão "Recorte da Bia" <marcada> e a Ana com a visão "Recorte da Ana", em /admin/users, ambas com a busca "zz"
      E a Ana com a visão "Convites da Ana" (busca "zz") em /admin/convites
      Quando a Ana abre por HTTP /admin/users?view={id de <visao>}
      Então a página responde 200 e a busca da tabela montada com o mesmo parâmetro é <busca>
      E o HTML <contem> o nome <visao>

      Exemplos:
        | visao              | marcada        | compartilhamento | busca | contem      |
        | "Recorte da Ana"   | privada        | desligado        | "zz"  | contém      |
        | "Recorte da Bia"   | privada        | desligado        | ""    | não contém  |
        | "Recorte da Bia"   | compartilhada  | desligado        | ""    | não contém  |
        | "Recorte da Bia"   | compartilhada  | ligado           | "zz"  | contém      |
        | "Convites da Ana"  | privada        | desligado        | ""    | não contém  |

    Esquema do Cenário: [CT-34] a visão de outra organização pedida pela URL não vaza nem o nome
      Dado as visões e o compartilhamento ligados
      E a Ana (Acme) com a visão "Plano da Acme" compartilhada em /app/acme/projetos, com a busca "zz"
      E a Carla, admin_app da Globex, com a visão "Plano da Globex" em /app/globex/projetos, com a busca "zz"
      Quando a Carla abre por HTTP /app/globex/projetos?view={id de <visao>}
      Então a página responde 200 e a busca da tabela montada com o mesmo parâmetro é <busca>
      E o HTML <contem> o nome <visao>

      Exemplos:
        | visao              | busca | contem      |
        | "Plano da Globex"  | "zz"  | contém      |
        | "Plano da Acme"    | ""    | não contém  |

    Esquema do Cenário: [CT-65] a visão de uma listagem do /app não vale na listagem homônima do /admin
      Dado as visões e o compartilhamento ligados
      E a Ana (Acme) com a visão "Da Acme" compartilhada em /app/acme/users, com a busca "zz"
      E o Gil, admin global, em /admin/users
      Quando o Gil <entrada>
      Então a busca da tabela do Gil é ""
      E a visão "Da Acme" não aparece na lista de visões do Gil

      Exemplos:
        | entrada                                     |
        | abre /admin/users                           |
        | abre /admin/users?view={id de "Da Acme"}    |
        | chama applyTableView com o id de "Da Acme"  |

    Esquema do Cenário: [CT-73] quem já aplicou uma visão compartilhada a perde quando ela deixa de ser visível
      Dado as visões e o compartilhamento ligados
      E a Bia com a visão "Pendentes" compartilhada em /admin/users, com a busca "zz"
      E a Ana com "Pendentes" aplicada e ativa na tabela de /admin/users
      E a Bia mudou a busca gravada em "Pendentes" para "yy"
      E <evento>
      Quando a Ana reabre /admin/users
      Então "Pendentes" não aparece como visão ativa nem na lista de visões da Ana
      E a ação de aplicar "Pendentes" não existe para a Ana
      E a busca da tabela da Ana não é "yy"

      Exemplos:
        | evento                                               |
        | a Bia desmarcou o compartilhamento de "Pendentes"    |
        | a Bia apagou "Pendentes"                             |
        | o compartilhamento foi desligado na aba Tabelas      |

    Esquema do Cenário: [CT-74] o link ?view com valor inválido abre a listagem sem visão
      Dado as visões ligadas
      E a Ana com a visão "Minha" (busca "zz") em /admin/users e o id de uma visão "Apagada" que já foi apagada
      Quando a Ana abre por HTTP /admin/users?<consulta>
      Então a página responde 200
      E a busca da tabela montada com a mesma consulta é ""

      Exemplos:
        | consulta                  | # partição        |
        | view={id de "Apagada"}    | registro removido |
        | view=abc                  | fora do domínio   |
        | view[]=1                  | tipo errado       |

    Esquema do Cenário: [CT-77] o master_global não vê nem aplica a visão privada de outro usuário
      Dado as visões ligadas e o compartilhamento desligado
      E a Bia com a visão privada "Da Bia" em /admin/users, com a busca "zz"
      Quando o master_global <entrada>
      Então a busca da tabela do master_global é ""
      E "Da Bia" não aparece na lista de visões do master_global

      Exemplos:
        | entrada                                      |
        | abre /admin/users                            |
        | abre /admin/users?view={id de "Da Bia"}      |
        | chama applyTableView com o id de "Da Bia"    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M45 | `visibleTo` inclui `is_public` sem conferir `allow_public_views` | CT-30, CT-33 | CT-30 linha f: "não aparece"; o mutante mostra. CT-33 linha 3: "não contém"; o mutante exibe |
| M46 | consulta sem `forTable` (visões de todas as páginas) | CT-30 | linha c: "não aparece"; o mutante mostra a de /admin/convites |
| M47 | `scope_to_tenant` desligado ou `forTenant` ausente | CT-31 | linha d (Globex): "não aparece" e busca ""; o mutante mostra e aplica a visão da Acme para a mesma Dani |
| M48 | `forTenant(null)` que abre para visão sem organização | CT-31 | linha h: "não aparece"; o mutante mostra a de `tenant_id` nulo |
| M49 | `?view`/`activeTableView` resolvido por `TableView::find($id)` sem escopo | CT-32, CT-33, CT-34 | CT-33 linha 2: busca "" e "não contém"; o mutante aplica "zz" (ADV-23: o recorte, não só o nome). CT-34 linha 2: idem com "Plano da Acme" |
| M50 | `activeTableView` com id não numérico estoura (`ValueError`) ou um array é convertido em id | CT-32 | linha `"abc"`: sem exceção e busca ""; linha `["1"]`: o framework recusa com `TypeError` antes de qualquer lógica (P-28) e a busca continua "" — o mutante que converte o array em id aplica "zz" *(reescrito em 2026-10-14, onda 2)* |
| M114 | ADV-04 — `table_key` pelo slug/Resource: `/app/{org}/users` e `/admin/users` compartilham a chave, e o `/admin` não filtra organização | CT-65 | busca "" e "Da Acme" fora da lista do Gil; o mutante mostra e aplica |
| M124 | ADV-12 — `applyTableView`/`?view` resolvem "dono ou visível" sem a chave da listagem | CT-32, CT-33 | CT-32 linha "dona, outra listagem": ""; CT-33 linha "Convites da Ana": "" — o mutante aplica "zz" |
| M145 | ADV-40 — a visão ativa persistida na sessão é reaplicada sem revalidar a visibilidade | CT-73 | "Pendentes" não é ativa nem listada; o mutante a mantém ativa com o rótulo |
| M146 | ADV-41 — `?view[]=1` (array em propriedade string) estoura 500 pela rota | CT-74 | linha `view[]=1`: 200; o mutante responde 500 |
| M151 | ADV-42 — a visibilidade decidida por policy (`can('view')`), que o `Gate::before` do master atravessa | CT-77 | busca "" e "Da Bia" fora da lista do master; o mutante mostra e aplica |
| M153 | ADV-46 — a visão ativa revalidada só no rótulo; o `state` atual da visão alheia é reaplicado por id ao reabrir | CT-73 | a busca da Ana não é "yy"; o mutante aplica "yy" |

---

## Regra R11 — "Compartilhar com todos" existe só com o interruptor ligado; o nome compartilhado chega ao colega como texto

> `P-02`, `P-26` (substitui P-19) · perfil **completo** · técnica: **EP** + **2-switch sobre `is_public`** (marcar e desmarcar) + taxonomia (texto livre exibido a terceiro). **N1** = `<img src=x onerror=alert(1)>`; escapado = `&lt;img src=x onerror=alert(1)&gt;` (fora do Gherkin para não ser lido como parâmetro do Esquema). As linhas `desligado` de CT-35 **submetem** o formulário com o valor forjado (ADV-26). O `is_public` forjado com o compartilhamento desligado é morto em R8 (CT-25) e R9 (CT-28); a compartilhada antiga escondida com o interruptor desligado, em R10 (CT-30 linha f, CT-33 linha 3).

```gherkin
# language: pt
Funcionalidade: Compartilhar com todos

  Regra: a opção de compartilhar só existe com o compartilhamento ligado, e marcá-la expõe a visão aos colegas

    Esquema do Cenário: [CT-35] a opção Compartilhar com todos acompanha o interruptor, ao salvar e ao gerenciar
      Dado as visões ligadas e o compartilhamento <compartilhamento>
      E a Ana, admin com a permissão, com as visões "Minha" (privada) e "Antiga" (gravada antes com is_public true) em /admin/users, e a Bia, outra admin
      Quando a Ana <acao>
      Então o campo Compartilhar com todos <campo> no formulário
      E <gravado>

      Exemplos:
        | compartilhamento | acao                                                                     | campo      | gravado                                                       |
        | desligado        | salva a visão "Forjada" com Compartilhar com todos forjado como true     | não existe | "Forjada" está gravada com is_public false                    |
        | desligado        | gerencia enviando "Minha" com Compartilhar com todos forjado como true   | não existe | "Minha" está gravada com is_public false                      |
        | desligado        | gerencia renomeando "Antiga" para "Antiga 2"                             | não existe | "Antiga 2" está gravada com is_public false (P-26)            |
        | desligado        | gerencia renomeando só "Minha", com "Antiga" reenviada sem alteração      | não existe | "Antiga" está gravada com is_public false (P-26: enviada)     |
        | ligado           | salva a visão "Pública" marcando Compartilhar com todos                  | existe     | "Pública" está gravada com is_public true                     |
        | ligado           | salva a visão "Só minha" sem marcar Compartilhar com todos               | existe     | "Só minha" está gravada com is_public false e a Bia não a vê  |
        | ligado           | gerencia marcando "Minha" como Compartilhar com todos                    | existe     | "Minha" está gravada com is_public true                       |
        | ligado           | gerencia desmarcando o compartilhamento de "Antiga"                      | existe     | "Antiga" está gravada com is_public false e a Bia não a vê    |

    Esquema do Cenário: [CT-36] o nome de uma visão compartilhada chega ao colega como texto, em todo ponto em que aparece
      Dado as visões e o compartilhamento ligados
      E a Bia com a visão compartilhada cujo nome é a marcação N1 em /admin/users
      Quando a Ana <acao>
      Então o HTML devolvido contém o nome N1 escapado
      E o HTML devolvido não contém a marcação N1 crua

      Exemplos:
        | acao                                       | # ponto de exibição               |
        | abre /admin/users com a lista de visões    | item da lista                     |
        | aplica a visão compartilhada da Bia        | rótulo da visão ativa no botão    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M51 | o campo `is_public` sempre presente e desidratado no formulário de salvar | CT-35 | linha 1: "não existe" e "Forjada" com is_public false; o mutante grava true |
| M52 | o gerenciamento não grava `is_public` mesmo com o compartilhamento ligado | CT-35 | linha "gerencia marcando Minha": is_public true; o mutante mantém false |
| M53 | o rótulo da visão montado com `HtmlString`/`{!! !!}` | CT-36 | "não contém a marcação crua"; o mutante entrega `<img …>` |
| M115 | ADV-05 — o toggle Compartilhar com todos nasce marcado com o compartilhamento ligado | CT-35 | linha "Só minha": is_public false; o mutante grava true |
| M116 | ADV-06 — o gerenciamento só grava `is_public` quando vem true (não dá para descompartilhar) | CT-35 | linha "desmarcando Antiga": false; o mutante mantém true |
| M117 | P-26 — gerenciar com o compartilhamento desligado mantém o `is_public` gravado da visão enviada e alterada | CT-35 | linha "renomeando Antiga": "Antiga 2" com false; o mutante mantém true |
| M152 | ADV-43 — com o compartilhamento desligado, só as visões alteradas no gerenciamento são regravadas privadas; a reenviada sem alteração mantém o `is_public` | CT-35 | linha "renomeando só Minha": "Antiga" com false; o mutante mantém true |
| M132 | ADV-19 — o rótulo da visão ativa montado com `HtmlString` | CT-36 | linha "rótulo da visão ativa": sem marcação crua; o mutante a entrega |

---

## Regra R12 — Desligar esconde sem apagar; religar devolve

> `P-03` · perfil **completo** · técnica: **2-switch** (ligado → desligar → religar; oráculo sobre o segundo evento e sobre os registros do ciclo anterior). O desligar e o religar passam **pela tela**: um apagamento escrito como reação ao salvamento do Settings não seria exercitado por `gravarConfiguracao()`.

```gherkin
# language: pt
Funcionalidade: Desligar e religar as visões

  Regra: desligar as visões esconde o botão e as ações e mantém tudo gravado; religar devolve

    Cenário: [CT-37] desligar as visões pela tela esconde o botão e não apaga nada
      Dado as visões ligadas
      E a Ana com a visão "Padrão" marcada como padrão e a Bia com a visão "Compartilhada" marcada compartilhada, ambas em /admin/users
      Quando o admin desliga as visões na aba Tabelas e salva
      Então a listagem /admin/users da Ana não oferece o botão Visões
      E remember_table_views continua com 2 linhas
      E "Padrão" continua com is_default true e "Compartilhada" continua com is_public true

    Cenário: [CT-38] religar as visões pela tela devolve as visões gravadas antes de desligar
      Dado as visões ligadas e a Ana com as visões "Uma" (busca "zz") e "Outra" em /admin/users
      E o admin desligou as visões na aba Tabelas
      Quando o admin religa as visões na aba Tabelas e salva
      Então a listagem /admin/users da Ana oferece o botão Visões com "Uma" e "Outra"
      E a ação de aplicar "Uma" existe para a Ana
      E "Uma" continua gravada com o state com a busca "zz"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M54 | desligar pela tela apaga as visões (reação ao salvamento do Settings / truncate) | CT-37 | "continua com 2 linhas"; o mutante deixa 0 |
| M55 | desligar zera `is_default`/`is_public` "para não vazar" | CT-37 | `is_default` e `is_public` true; o mutante grava false |
| M56 | religar não reexibe (lista de visões memoizada vazia) | CT-38 | botão com "Uma" e "Outra" e a ação de aplicar "Uma" existe; o mutante mostra lista vazia |

---

## Regra R13 — Aplicar restaura só o que ainda existe; a visão guarda recorte, não dados

> `P-09`, `P-16` · perfil **completo** · CT-75 usa a listagem de teste agrupável de `tests/Kit/fixtures` (nenhuma das 10 listagens agrupa); fixture inviável → lacuna declarada pelo mesmo critério do CT-39 (ADV-47) · técnica: **EP** (uma partição por parte do estado) — o `state` é entrada do cliente de um request anterior (Superfície Livewire do 02). Rodada 2: P-16 (a visão não guarda tamanho de página) acrescentou a linha "tamanho de página 25, uma das opções", que só discrimina com a decisão — um tamanho válido gravado no `state` (visão antiga ou forjada) também é ignorado; as abas usam as de `/admin/users`, o agrupamento usa o fixture.

```gherkin
# language: pt
Funcionalidade: Aplicação de uma visão

  Regra: a visão aplicada restaura o que ainda existe na tabela e ignora o resto

    Esquema do Cenário: [CT-39] partes do estado que não existem mais na tabela são ignoradas, e as que existem se aplicam
      Dado as visões ligadas
      E a Ana com a visão "Antiga" em /admin/users, com a busca "zz" e <parte> igual a <valor>
      Quando a Ana aplica a visão "Antiga"
      Então a busca da tabela é "zz"
      E <efeito>
      E a resposta não tem exceção

      Exemplos:
        | parte                 | valor                          | efeito                                                |
        | um filtro             | "xpto_removido" => "1"         | nenhum filtro "xpto_removido" na tabela               |
        | a ordenação           | a coluna "xpto_removida"       | a ordenação da tabela é a ordenação padrão da listagem |
        | o agrupamento         | o grupo "xpto_removido"        | a tabela fica sem agrupamento                         |
        | a aba                 | "xpto_removida"                | a aba ativa é a aba padrão da listagem                |
        | o tamanho de página   | 9999 (fora das opções)         | o tamanho de página continua 10, o de antes de aplicar |
        | o tamanho de página   | 25 (uma das opções)            | o tamanho de página continua 10, o de antes de aplicar |
        | as colunas visíveis   | a lista sem a coluna "email"   | a coluna "email", que a visão não conhecia, aparece   |

    Cenário: [CT-40] a visão guarda o recorte, e os registros vêm do momento em que ela é aplicada
      Dado as visões ligadas
      E a Ana com a visão "Com zz" em /admin/users, com a busca "zz", gravada quando só existia o usuário "zz-antigo"
      E depois da gravação, o usuário "zz-novo" criado e o "zz-antigo" renomeado para "outro"
      Quando a Ana aplica a visão "Com zz"
      Então a tabela exibe "zz-novo"
      E a tabela não exibe "outro"

    Cenário: [CT-63] aplicar restaura cada parte do recorte que ainda existe na tabela
      Dado as visões ligadas
      E a Ana com a visão "Completa" em /admin/users com um filtro que a tabela declara (valor que recorta a lista), a ordenação por e-mail decrescente, a coluna ocultável "origem" oculta e uma aba que não é a padrão
      E a tabela da Ana sem filtro, na ordenação padrão, com todas as colunas e na aba padrão
      Quando a Ana aplica a visão "Completa"
      Então o filtro gravado está ativo com o valor gravado
      E a ordenação é por e-mail decrescente
      E a coluna "origem" está oculta

      <!-- *(alterado em 2026-10-14, onda 2: "email" não é coluna ocultável da listagem de usuários — só `origem` tem `toggleable()`; uma visão só esconde o que o gerenciador de colunas deixa esconder.)* -->
      E a aba ativa é a gravada

    Cenário: [CT-75] aplicar restaura o agrupamento gravado
      Dado as visões ligadas
      E a Ana com a visão "Agrupada" na listagem de teste agrupável, gravada com o agrupamento por uma coluna da tabela
      E a tabela da Ana nessa listagem sem agrupamento
      Quando a Ana aplica a visão "Agrupada"
      Então a tabela está agrupada pela coluna gravada
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M57 | filtros reaplicados sem interseção com os filtros da tabela | CT-39 | linha "um filtro": sem exceção e sem "xpto_removido"; o mutante lança ou mantém a chave |
| M58 | ordenação reaplicada sem conferir a coluna | CT-39 | linha "a ordenação": a ordenação padrão (ADV-25); o mutante fixa "xpto_removida" |
| M59 | a visão guarda os ids dos registros exibidos | CT-40 | exibe "zz-novo"; o mutante mostra só o registro antigo |
| M60 | colunas reaplicadas como lista de visíveis (o que não está nela fica oculto) | CT-39 | linha "as colunas visíveis": "email" aparece; o mutante a oculta |
| M61 | tamanho de página reaplicado sem conferir as opções (ou com clamp para o maior valor) | CT-39 | linha "9999": continua 10 (ADV-25); o mutante fixa 9999 ou o maior valor |
| M92 | a visão lembra o tamanho de página (`remember.per_page` ligado) | CT-24, CT-39 | CT-24: `state` sem tamanho de página; o mutante grava 25. CT-39 linha "25": continua 10; o mutante aplica 25 |
| M111 | ADV-01 — `applyTableView` restaura só a busca e ignora filtro, ordenação, colunas e aba que ainda existem | CT-63 | filtro, ordenação e-mail desc, "email" oculta e a aba gravada; o mutante deixa a tabela como estava |
| M148 | ADV-47 — aplicar restaura tudo menos o agrupamento | CT-75 | a tabela agrupada pela coluna gravada; o mutante deixa sem agrupamento |

---

## Regra R14 — Contínuo em toda tabela, no modo escolhido, em blocos de "Linhas por página", sem seletor

> `P-04`, `P-10`, `RQ-04` · perfil **padrão** · técnica: **EP** (modo × tabela, inclusive de plugin de terceiro). A interseção que dispara o bloco na rolagem é **CT-B01** (`05`).

```gherkin
# language: pt
Funcionalidade: Carregamento contínuo nas tabelas

  Regra: com rolagem ou botao, toda tabela dos três painéis carrega em blocos do tamanho de Linhas por página e perde o seletor de registros por página

    Esquema do Cenário: [CT-41] toda tabela recebe o carregamento contínuo no modo escolhido
      Dado o carregamento <modo> e Linhas por página igual a 7
      E <persona> autenticado
      Quando a listagem <listagem> é montada e a tabela carrega
      Então a tabela tem carregamento contínuo no modo <modo_pacote>
      E as opções de registros por página são exatamente [7]

      Exemplos:
        | listagem                          | persona                  | modo    | modo_pacote | # origem   |
        | /admin/users                      | master_global            | rolagem | Scroll      | kit        |
        | /admin/convites                   | master_global            | rolagem | Scroll      | kit        |
        | /admin/agentes-ia                 | master_global            | rolagem | Scroll      | kit        |
        | /admin/shield/roles               | master_global            | rolagem | Scroll      | kit        |
        | /infra/ai-runs                    | master_global            | rolagem | Scroll      | kit        |
        | /infra/composer-release-packages  | master_global            | rolagem | Scroll      | kit        |
        | /infra/audits                     | master_global            | rolagem | Scroll      | plugin     |
        | /infra/exceptions                 | master_global            | rolagem | Scroll      | plugin     |
        | /admin/users                      | master_global            | botao   | Button      | kit        |
        | /infra/audits                     | master_global            | botao   | Button      | plugin     |
        | /app/acme/projetos                | admin_app (Tenancy)      | rolagem | Scroll      | kit, /app  |
        | /app/acme/users                   | admin_app (Tenancy)      | botao   | Button      | kit, /app  |
        | /app/acme/convites                | admin_app (Tenancy)      | rolagem | Scroll      | kit, /app  |
        | /admin/organizacoes               | master_global (Tenancy)  | rolagem | Scroll      | kit        |
        | relation manager de usuários em /admin/organizacoes/{acme}/edit | master_global (Tenancy) | rolagem | Scroll | kit, relation manager |

    Esquema do Cenário: [CT-42] o rodapé da tabela mostra o modo escolhido e o primeiro bloco
      Dado o carregamento <modo>, Linhas por página igual a 7 e 10 usuários
      Quando o master_global abre /admin/users e a tabela carrega
      Então a tabela exibe 7 usuários
      E o rodapé do carregamento contínuo está no modo <modo_pacote> com 7 carregados
      E <gatilho>
      E o seletor de registros por página não aparece na tabela renderizada

      Exemplos:
        | modo    | modo_pacote | gatilho                                                                   |
        | rolagem | scroll      | o rodapé tem o sentinela de interseção e não tem o botão "Carregar mais"  |
        | botao   | button      | o rodapé tem o botão "Carregar mais" ligado ao pedido do próximo bloco    |

    Esquema do Cenário: [CT-43] cada carregamento acrescenta um bloco de Linhas por página
      Dado o carregamento "botao", Linhas por página igual a 7 e 30 usuários
      E o master_global com /admin/users aberta e a tabela carregada com 7 usuários
      Quando o próximo bloco é pedido <vezes> vez(es)
      Então a tabela exibe <linhas> usuários

      Exemplos:
        | vezes | linhas | # partição                                       |
        | 1     | 14     | segundo bloco                                    |
        | 2     | 21     | terceiro bloco (dobrar daria 28)                 |
        | 4     | 30     | último bloco incompleto, e pedido além do total  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M62 | carregamento aplicado por Resource do kit, não pelo `configureUsing` global | CT-41 | linhas `/infra/audits`/`/infra/exceptions`: contínuo registrado; o mutante não registra |
| M63 | bloco do config do pacote (25) em vez de Linhas por página | CT-41, CT-42, CT-43 | CT-42: 7 usuários; o mutante exibe 10. CT-43 linha 1: 14; o mutante exibe 30 |
| M64 | listagem que declara `paginated([...])` próprio sobrepõe o global e mantém o seletor | CT-41 | opções exatamente `[7]` em cada uma das 10 listagens; o mutante deixa várias opções na que sobrepõe |
| M65 | o modo ignorado (sempre `Scroll`) | CT-41, CT-42 | linhas `botao`: `Button` e o botão "Carregar mais"; o mutante mostra o sentinela |
| M66 | cada pedido dobra o tamanho em vez de somar um bloco | CT-43 | linha 2: 21; o mutante exibe 28 |
| M67 | a interseção não dispara no navegador (Alpine `intersect` ou asset do pacote ausente) | CT-B01 | `data-loaded` passa de 25 para 50 depois de rolar ao fim; o mutante fica em 25 |
| M131 | ADV-18 — o contínuo aplicado só a páginas de listagem (`ListRecords`) | CT-41 | linha "relation manager": Scroll; o mutante deixa paginado |
| M138 | ADV-30 — opções restritas a `[7]` mas o seletor continua renderizado | CT-42 | o seletor não aparece; o mutante o mostra com uma opção |

---

## Regra R15 — Nunca mais de 500 linhas na tela; entrada forjada não fura o bloco

> `P-10`, `P-23` · perfil **padrão**, técnica escalada (estouro do teto, 5 > 3: CT-69 e CT-70 nascem de ADV-14 e ADV-17; CT-44 ganhou a BVA do total, ADV-22): **BVA 3-valores** no teto literal (500) + **EP de tipo** na entrada forjada (Superfície Livewire do 02: `tableRecordsPerPage` e `infiniteScrollLoadMore`). O incremento natural do teto é 1 linha; com "Linhas por página" no máximo 100, as bordas são exercitadas por bloco (400/500/pedido além) e pela linha `99`, cujo pedido além cruzaria 500 com 594.

```gherkin
# language: pt
Funcionalidade: Teto e entrada forjada do carregamento contínuo

  Regra: a lista nunca passa de 500 linhas, e o cliente não muda o tamanho do bloco

    Esquema do Cenário: [CT-44] a lista para em 500 linhas e só pede busca ou filtro quando há mais registros que isso
      Dado o teto efetivo do carregamento contínuo é 500, sem ajuste do teste
      E o carregamento "botao", Linhas por página igual a <bloco> e <total> usuários
      E o master_global com /admin/users aberta e a tabela carregada
      Quando o próximo bloco é pedido <vezes> vezes
      Então a tabela exibe <linhas> usuários
      E o rodapé <rodape>

      Exemplos:
        | total | bloco | vezes | linhas         | rodape                                                             | # borda                                |
        | 501   | 100   | 3     | 400            | oferece o próximo bloco e não pede busca nem filtro                | linhas: borda−1 (um bloco antes)       |
        | 501   | 100   | 4     | 500            | pede busca ou filtro para restringir a lista                       | linhas: borda; total: borda+1          |
        | 501   | 100   | 5     | 500            | pede busca ou filtro para restringir a lista                       | linhas: borda+1 (pedido além do teto)  |
        | 501   | 99    | 6     | no máximo 500  | pede busca ou filtro para restringir a lista                       | bloco que não divide o teto            |
        | 499   | 100   | 4     | 499            | diz que todos os 499 foram carregados e não pede busca nem filtro  | total: borda−1                         |
        | 500   | 100   | 4     | 500            | diz que todos os 500 foram carregados e não pede busca nem filtro  | total: borda (P-23)                    |

    Esquema do Cenário: [CT-45] o tamanho de página forjado pelo cliente não muda o bloco exibido
      Dado o carregamento "rolagem", Linhas por página igual a 7 e 30 usuários
      E o master_global com /admin/users aberta e a tabela carregada com 7 usuários
      Quando o cliente define <propriedade> como <valor>
      Então a tabela exibe os 7 primeiros usuários da ordenação padrão
      E a resposta não tem exceção

      Exemplos:
        | propriedade          | valor  | # partição                       |
        | tableRecordsPerPage  | 50000  | acima do teto                    |
        | tableRecordsPerPage  | 501    | teto+1                           |
        | tableRecordsPerPage  | 21     | vários blocos de uma vez         |
        | tableRecordsPerPage  | 15     | não múltiplo do bloco            |
        | tableRecordsPerPage  | 0      | zero                             |
        | tableRecordsPerPage  | -7     | negativo                         |
        | tableRecordsPerPage  | "abc"  | tipo errado (texto)              |
        | tableRecordsPerPage  | [7]    | tipo errado (lista)              |
        | tableRecordsPerPage  | "all"  | valor especial de "todos" do Filament |
        | paginators.page      | 3      | página forjada no modo contínuo  |

    Esquema do Cenário: [CT-46] pedir o próximo bloco numa tabela paginada não muda nada
      Dado o carregamento "paginacao", Linhas por página igual a 5 e 30 usuários
      E o master_global com /admin/users aberta e a tabela carregada
      Quando o cliente chama infiniteScrollLoadMore <argumento>
      Então a chamada é recusada pelo componente (o método só existe no modo contínuo) e nenhum bloco é somado
      E a tabela continua exibindo 5 usuários
      E as opções de registros por página continuam com mais de uma opção

      <!-- *(alterado em 2026-10-14, onda 2: fora do modo contínuo o hook do pacote não intercepta e o Livewire responde "method not found" — recusa, não 200; e 7 não está nas opções de página (ver CT-03), então o bloco de controle passa a 5.)* -->

      Exemplos:
        | argumento            |
        | sem argumento        |
        | com o argumento 999  |

    Cenário: [CT-69] no modo contínuo o argumento do pedido do próximo bloco é ignorado
      Dado o carregamento "rolagem", Linhas por página igual a 7 e 30 usuários
      E o master_global com /admin/users aberta e a tabela carregada com 7 usuários
      Quando o cliente chama infiniteScrollLoadMore com o argumento 999
      Então a tabela exibe 14 usuários

    Esquema do Cenário: [CT-70] no teto e no fim da lista o rodapé não oferece mais nenhum pedido
      Dado o carregamento <modo>, Linhas por página igual a <bloco> e <total> usuários
      E o master_global com /admin/users aberta e a tabela carregada
      Quando o próximo bloco é pedido <vezes> vezes
      Então o rodapé tem data-has-more falso
      E o rodapé não tem o sentinela de interseção nem o botão "Carregar mais"
      E o rodapé <texto>

      Exemplos:
        | modo    | bloco | total | vezes | texto                                              | # estado terminal |
        | rolagem | 100   | 501   | 4     | pede busca ou filtro para restringir a lista       | teto              |
        | botao   | 100   | 501   | 4     | pede busca ou filtro para restringir a lista       | teto              |
        | rolagem | 7     | 30    | 4     | diz que todos os 30 foram carregados               | fim da lista      |
        | botao   | 7     | 30    | 4     | diz que todos os 30 foram carregados               | fim da lista      |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M68 | o kit passa `maxRecords: false` (sem teto) ao configurar | CT-44 | linha borda+1: 500; o mutante exibe 501 |
| M69 | teto conferido antes de crescer (cresce uma vez além) | CT-44 | linha `99 · 6`: no máximo 500; o mutante exibe 501 (todos) |
| M70 | no teto o rodapé continua oferecendo o próximo bloco (sem o pedido de busca/filtro) | CT-44 | linha borda: o rodapé pede busca ou filtro; o mutante mostra "Carregar mais" |
| M71 | `tableRecordsPerPage` aceito como veio do cliente | CT-45 | linha `50000`: 7 usuários; o mutante exibe 30 |
| M72 | `tableRecordsPerPage` com tipo errado estoura no `render` | CT-45 | linha `"abc"`: sem exceção; o mutante lança |
| M73 | o método interceptado cresce a página também nas tabelas sem contínuo | CT-46 | 7 usuários; o mutante exibe 14 |
| M125 | ADV-13 — `tableRecordsPerPage = "all"` aceito (desliga a paginação) | CT-45 | linha `"all"`: os 7 primeiros; o mutante exibe 30 |
| M126 | ADV-14 — no modo contínuo o argumento de `infiniteScrollLoadMore` usado como tamanho do bloco | CT-69 | 14 usuários; o mutante exibe 30 |
| M129 | ADV-17/ADV-48 — no teto ou no fim, o sentinela continua com `data-has-more` verdadeiro (pedidos em laço) | CT-70 | `data-has-more` falso e sem sentinela, inclusive nas linhas de teto; o mutante mantém o sentinela |
| M130 | ADV-17 — no teto ou no fim, o botão "Carregar mais" continua oferecido | CT-70 | linhas `botao`: sem botão; o mutante o mostra junto do aviso |
| M135 | ADV-22 — o aviso de busca/filtro disparado ao chegar a 500 linhas, mesmo com tudo carregado | CT-44 | linha total 500: não pede busca nem filtro; o mutante pede |
| M147 | ADV-24 — a página forjada (`paginators.page = 3`) honrada no modo contínuo | CT-45 | linha `paginators.page`: os 7 primeiros da ordenação padrão; o mutante mostra os registros 15 a 21 |

---

## Regra R16 — Busca, filtro, ordenação, agrupamento ou aba reiniciam no primeiro bloco

> `P-10` · perfil **padrão** · técnica: **EP** (um gatilho por linha; cada gatilho casa com mais de 7 registros, senão "7" não discrimina)

```gherkin
# language: pt
Funcionalidade: Reinício do carregamento contínuo

  Regra: quando o recorte muda, a lista volta ao primeiro bloco

    Esquema do Cenário: [CT-47] mudar o recorte depois de três blocos volta a um bloco
      Dado o carregamento "botao", Linhas por página igual a 7 e 30 registros que casam com a mudança
      E as visões ligadas, com a visão "Todos" da Ana gravada com uma busca que casa com os 30
      E a tabela com 21 registros exibidos depois de dois pedidos do próximo bloco
      Quando o usuário <mudanca>
      Então a tabela exibe 7 registros

      Exemplos:
        | mudanca                                        |
        | busca por um termo que casa com os 30          |
        | aplica um filtro que casa com os 30            |
        | ordena por outra coluna                        |
        | agrupa por uma coluna                          |
        | troca para uma aba que lista os 30             |
        | aplica a visão "Todos"                         |
        | limpa a tabela, que estava com um filtro que casa com os 30, aplicado antes dos dois pedidos |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M74 | reinício só na busca | CT-47 | linhas filtro/ordenação: 7; o mutante mantém 21 |
| M75 | a assinatura do recorte ignora agrupamento e aba | CT-47 | linhas agrupamento/aba: 7; o mutante mantém 21 |
| M127 | ADV-15 — o reinício ligado só aos hooks `updated*` (mudança vinda do usuário); aplicar visão ou limpar a tabela pelo servidor mantém o tamanho | CT-47 | linhas "aplica a visão" e "limpa a tabela": 7; o mutante mantém 21 |

---

## Regra R17 — A extensão de PHPStan entra pelo include, sem exceção nova e só em dev

> `P-05`, `RQ-02` · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt
Funcionalidade: PHPStan entende os macros

  Regra: o phpstan.neon inclui a extensão de macros do Filament e nada mais muda

    Cenário: [CT-48] o phpstan.neon inclui a extensão e as exceções continuam as três de antes
      Dado o phpstan.neon e o composer.json do kit
      Quando a configuração efetiva do PHPStan é lida pelo dump-parameters
      Então o bloco includes cita vendor/happenv-com/filament-phpstan-macros/extension.neon
      E o dump-parameters termina com código 0
      E ignoreErrors tem exatamente 3 entradas e nenhuma cita infiniteScroll
      E happenv-com/filament-phpstan-macros está em require-dev e não em require
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M76 | include ausente (pacote instalado e não usado) | CT-48 | o bloco `includes` cita a extensão; o mutante não cita |
| M77 | exceção nova em `ignoreErrors` para o macro `infiniteScroll()` | CT-48 | exatamente 3 entradas; o mutante tem 4 |
| M78 | pacote em `require` (chega à produção) | CT-48 | em `require-dev` e não em `require`; o mutante inverte |

---

## Regra R18 — Entram os pacotes adotados; os dois recusados ficam de fora

> `RQ-01`, `RQ-02`, `P-06` · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt
Funcionalidade: Pacotes da rodada 3

  Regra: só os pacotes que agregam valor são instalados

    Cenário: [CT-49] o composer declara os adotados e não traz os recusados
      Dado o composer.json e o composer.lock do kit
      Quando as dependências são lidas
      Então sehsah/filament-remember-table e asignua/filament-infinite-scroll estão em require
      E nem spiggle/list-view nem happenv-com/filament-saved-views aparecem em require, require-dev ou no composer.lock
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M79 | `happenv-com/filament-saved-views` instalado junto (cobre o mesmo requisito) | CT-49 | ausente do `composer.lock`; o mutante o traz |
| M80 | `sehsah/filament-remember-table` em `require-dev` (a funcionalidade não chega à produção) | CT-49 | em `require`; o mutante põe em `require-dev` |

---

## Regra R19 — A documentação registra o veredito dos cinco e explica como ligar cada opção, em pt e en

> `RQ-01`, `RQ-06`, `P-25` · perfil **mínimo** · técnica: **EP** (idioma). Cada afirmação se ancora numa seção ou num marcador da página (o título da seção de critério, a linha "reabrir quando…" de cada recusado, a frase de efeito de cada opção), nunca só no nome do pacote ou da variável (ADV-36, ADV-37). Lê `docs/` → `->skip(fn (): bool => ! naArvoreDoKit(), …)` (`.ai/rules/testes.md`).

```gherkin
# language: pt
Funcionalidade: Documentação da rodada 3

  Regra: a documentação diz o que entrou, o que ficou de fora e por quê, como ligar e o que cada opção faz

    Esquema do Cenário: [CT-50] a página da funcionalidade cita os cinco pacotes e as três opções
      Dado a árvore do kit com a documentação em <idioma>
      Quando a página de visões de tabela e carregamento é lida
      Então ela escreve o critério de "agregar valor ao kit" usado na análise
      E ela cita pelo nome composer os cinco pacotes analisados, com o veredito de cada um
      E, para cada um dos dois recusados, o motivo e a condição de reabertura
      E ela cita KIT_TABELA_CARREGAMENTO, KIT_TABELA_VISOES_SALVAS e KIT_TABELA_VISOES_COMPARTILHADAS e a aba Tabelas, com o que cada opção faz
      E ela diz que as três nascem desligadas

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M81 | documentação só em pt | CT-50 | linha `en`: a página existe e cita os cinco; o mutante não tem página en |
| M82 | a documentação omite os pacotes recusados | CT-50 | cita `spiggle/list-view` e `happenv-com/filament-saved-views`; o mutante cita só os adotados |
| M140 | ADV-36 — a página não escreve o critério de "agregar valor" | CT-50 | a seção de critério existe; o mutante só lista vereditos |
| M141 | ADV-37 — a página lista nomes e variáveis sem o motivo, a condição de reabertura nem o efeito de cada opção | CT-50 | motivo e reabertura de cada recusado e o efeito de cada opção; o mutante tem só nomes |

---

## Regra R20 — Cada funcionalidade visível tem captura nos temas claro e escuro

> `RQ-07` · perfil **mínimo** · técnica: **EP** (tema). Convenção do kit para o tema escuro: sufixo `-escuro` (`KitArte`, clipes do dock).

```gherkin
# language: pt
Funcionalidade: Capturas da rodada 3

  Regra: o botão Visões e o rodapé do carregamento contínuo têm captura no tema claro e no escuro

    Cenário: [CT-51] toda captura desta feature tem o par no tema escuro
      Dado a lista de imagens declarada no KitArte
      Quando as capturas das visões de tabela e do carregamento contínuo são procuradas
      Então existe ao menos uma captura das visões e uma do carregamento contínuo
      E cada uma delas tem a captura de mesmo nome com o sufixo "-escuro"
      E cada imagem declarada existe publicada no diretório de arte do kit
      E o carregamento contínuo tem um clipe animado declarado, também com o par "-escuro"
      E o arquivo .gif de cada um desses clipes existe em art/
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M83 | capturas só no tema claro | CT-51 | par `-escuro` para cada captura; o mutante não tem |
| M84 | o rodapé do carregamento contínuo sem captura | CT-51 | "uma do carregamento contínuo"; o mutante só captura as visões |
| M142 | ADV-38 — captura declarada no `KitArte` sem o arquivo publicado | CT-51 | cada imagem declarada existe; o mutante só declara |
| M143 | ADV-38 — o carregamento contínuo sem clipe animado (só imagem parada) | CT-51 | clipe declarado com o par `-escuro`; o mutante não tem |
| M150 | ADV-38 — o clipe animado declarado no `KitArte` sem o `.gif` publicado em `art/` | CT-51 | o `.gif` claro e o escuro existem; o mutante só declara |

---

## Regra R21 — As telas novas falam pt_BR: toda chave do pacote tem tradução, e a aba *Tabelas* usa os rótulos do plano

> `P-17`, `RQ-04`, `RQ-06` · perfil **mínimo** · técnica: **EP** + **estado exibido** (os três rótulos do enum, exaustivos). O rodapé do carregamento contínuo usa o pt_BR que o próprio pacote embarca; o `remember-table` só embarca `en` e `ar`. Estouro do teto (2 > 1): CT-60 nasce de P-17 ("os rótulos/ajudas do plano valem") e mata mutantes que CT-52 não alcança (texto do kit, não do pacote).

```gherkin
# language: pt
Funcionalidade: Traduções das visões de tabela

  Regra: toda chave do arquivo en do remember-table tem tradução pt_BR, com os mesmos marcadores

    Cenário: [CT-52] o pt_BR do remember-table cobre todas as chaves do en e preserva os marcadores
      Dado o arquivo en do pacote e o arquivo pt_BR do kit em lang/vendor/filament-remember-table
      E a aplicação no idioma pt_BR
      Quando as chaves dos dois arquivos são comparadas
      Então toda chave do en existe no pt_BR com texto não vazio e diferente do en
      E cada texto pt_BR tem os mesmos marcadores (":name", ":limit") do texto en
      E o rótulo do botão traduzido pelo namespace do pacote é o texto pt_BR, não "Views"

    Esquema do Cenário: [CT-60] a aba Tabelas mostra cada opção de carregamento com o rótulo combinado
      Dado o admin na tela de configurações
      Quando a aba Tabelas é exibida
      Então a opção <valor> do carregamento tem o rótulo "<rotulo>"
      E o texto de ajuda das visões diz "Desligar esconde as visões sem apagá-las"

      Exemplos:
        | valor     | rotulo                                                          |
        | paginacao | Paginação — as páginas numeradas de hoje                        |
        | rolagem   | Rolagem infinita — o próximo bloco carrega ao chegar ao fim     |
        | botao     | Botão "Carregar mais" — o próximo bloco carrega no clique       |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M85 | chave faltando (ex.: `notifications.limit_reached`) | CT-52 | "toda chave do en existe no pt_BR"; o mutante deixa a chave cair no en |
| M86 | marcador traduzido (`:nome` no lugar de `:name`) | CT-52 | mesmos marcadores; o mutante perde `:name` e a notificação sai sem o nome |
| M87 | arquivo na pasta errada (fora do namespace do pacote) | CT-52 | o rótulo traduzido é o pt_BR; o mutante devolve "Views" |
| M106 | rótulos de `rolagem` e `botao` trocados no `opcoes()` | CT-60 | linha `rolagem`: "Rolagem infinita…"; o mutante mostra "Botão \"Carregar mais\"…" |
| M107 | o texto de ajuda das visões não avisa que desligar não apaga | CT-60 | contém "Desligar esconde as visões sem apagá-las"; o mutante não contém |

---

## Regra R22 — Nome obrigatório e até 100 caracteres, ao salvar e ao gerenciar; nome repetido é permitido

> `P-13`, `P-21` · perfil **completo** · técnica: **BVA 3-valores** no limite literal de 100 caracteres, mais a partição vazio-depois-de-trim (P-21) (incremento: 1 caractere, contado em caractere e não em byte — a linha `100 × "ã"` discrimina) + **criação ≠ edição** (salvar e gerenciar). A partida tem uma visão gravada, para "nada gravado" ter alvo. "Nome repetido permitido" é o lado afirmativo da decisão: CT-56 prova que repetir não quebra nada.

```gherkin
# language: pt
Funcionalidade: Nome das visões

  Regra: o nome é obrigatório e tem no máximo 100 caracteres, na criação e na edição; dois nomes iguais convivem

    Esquema do Cenário: [CT-53] o nome da visão nova é obrigatório e tem no máximo 100 caracteres
      Dado as visões ligadas
      E a Ana, admin com a permissão, com a visão "Minha" em /admin/users
      Quando a Ana salva uma visão com o nome <nome>
      Então o salvamento é <resultado>
      E remember_table_views tem <linhas> linha(s) da Ana

      Exemplos:
        | nome                    | resultado                   | linhas | # borda                              |
        | ""                      | recusado com erro no nome   | 1      | vazio                                |
        | "   " (só espaços)      | recusado com erro no nome   | 1      | vazio depois de trim (P-21)          |
        | 99 vezes "a"            | aceito                      | 2      | borda−1                              |
        | 100 vezes "ã"           | aceito                      | 2      | borda, em caractere multibyte        |
        | 101 vezes "a"           | recusado com erro no nome   | 1      | borda+1                              |

    Esquema do Cenário: [CT-54] renomear pelo gerenciamento segue as mesmas regras do nome
      Dado as visões ligadas
      E a Ana, admin com a permissão, com a visão "Minha" em /admin/users
      Quando a Ana gerencia as visões renomeando "Minha" para <nome>
      Então o gerenciamento é <resultado>
      E a visão da Ana se chama <gravado>

      Exemplos:
        | nome           | resultado                  | gravado         |
        | ""             | recusado com erro no nome  | "Minha"         |
        | "   " (só espaços) | recusado com erro no nome | "Minha"       |
        | 100 vezes "ã"  | aceito                     | 100 vezes "ã"   |
        | 101 vezes "a"  | recusado com erro no nome  | "Minha"         |

    Cenário: [CT-56] duas visões com o mesmo nome convivem e cada uma guarda o seu recorte
      Dado as visões ligadas
      E a Ana, admin com a permissão, com a visão "Ativos" gravada em /admin/users com a busca "aa"
      E a tabela de /admin/users da Ana com a busca "bb"
      Quando a Ana salva uma nova visão com o nome "Ativos"
      Então remember_table_views tem 2 visões "Ativos" da Ana
      E a primeira continua com a busca "aa" e a segunda tem a busca "bb"
      E a lista de visões oferece duas ações de aplicar distintas, uma para cada "Ativos"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M93 | nome sem `required` | CT-53, CT-54 | linha `""`: recusado e 1 linha; o mutante grava a visão sem nome |
| M94 | limite contado em bytes (`strlen`) | CT-53, CT-54 | linha `100 × "ã"` (200 bytes): aceito; o mutante recusa |
| M95 | limite maior que 100 (o 255 da coluna) | CT-53, CT-54 | linha `101 × "a"`: recusado; o mutante aceita |
| M96 | validação só no salvar; o gerenciamento renomeia sem validar | CT-54 | linha `""`: "Minha" mantido; o mutante grava `""` |
| M97 | salvar com nome existente sobrescreve a visão (`updateOrCreate` por nome) | CT-56 | 2 visões e a primeira com "aa"; o mutante deixa 1, com "bb" |
| M98 | a ação de aplicar identificada pelo nome (`md5` do nome): duas iguais colidem | CT-56 | duas ações de aplicar distintas; o mutante oferece uma |
| M134 | ADV-21 — obrigatoriedade sem `trim` (nome só com espaços aceito) | CT-53, CT-54 | linha `"   "`: recusado; o mutante grava |

---

## Regra R23 — No máximo 50 visões por usuário por listagem

> `P-13` · perfil **completo** · técnica: **BVA 3-valores** no limite literal de 50 (incremento: 1 visão). O `Dado` afirma o valor efetivo lido (50, sem ajuste do teste). CT-55 roda no single-tenant; a dimensão organização (P-18) é CT-61, em `tests/Tenancy`.

```gherkin
# language: pt
Funcionalidade: Teto de visões

  Regra: cada usuário guarda no máximo 50 visões em cada listagem, e as dos outros e das outras listagens não contam

    Esquema do Cenário: [CT-55] a 51ª visão da Ana na mesma listagem é recusada
      Dado o teto efetivo de visões por usuário por listagem é 50, sem ajuste do teste
      E as visões ligadas
      E a Ana, admin com a permissão, com <da_ana> visões em /admin/users
      E <outras>
      Quando a Ana salva a visão "Mais uma" em /admin/users
      Então o salvamento é <resultado>
      E a Ana tem <total> visões em /admin/users

      Exemplos:
        | da_ana | outras                                                             | resultado                         | total | # borda                       |
        | 48     | nenhuma outra visão                                                | aceito                            | 49    | borda−1                       |
        | 49     | nenhuma outra visão                                                | aceito                            | 50    | borda                         |
        | 50     | nenhuma outra visão                                                | recusado, com o aviso de limite   | 50    | borda+1                       |
        | 49     | 5 visões da Ana em /admin/convites e 5 da Bia em /admin/users      | aceito                            | 50    | as outras não contam          |

    Esquema do Cenário: [CT-61] o teto de 50 é contado em cada organização
      Dado o teto efetivo de visões por usuário por listagem é 50, sem ajuste do teste
      E as visões ligadas
      E a Dani, admin_app na Acme e na Globex, com 50 visões em /app/acme/projetos
      Quando a Dani salva a visão "Mais uma" em /app/<org>/projetos
      Então o salvamento é <resultado>
      E a Dani tem <total> visões em /app/<org>/projetos

      Exemplos:
        | org    | resultado                        | total | # partição                      |
        | acme   | recusado, com o aviso de limite  | 50    | mesma organização, borda+1      |
        | globex | aceito                           | 1     | outra organização não conta     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M99 | teto com `>` (aceita a 51ª) | CT-55 | linha `50`: recusado e total 50; o mutante grava a 51ª |
| M100 | teto contando todas as visões do usuário, ou da listagem de todos os usuários | CT-55 | linha "as outras não contam": aceito; o mutante recusa (conta 59) |
| M101 | teto ausente (`max_views_per_table` nulo na config do kit) | CT-55 | linha `50`: recusado; o mutante aceita |
| M108 | teto somando as visões da mesma listagem em todas as organizações do usuário | CT-61 | linha "Globex": aceito e 1 visão na Globex; o mutante recusa (conta 50 da Acme) |

---

## Regra R24 — Só a visão padrão do próprio usuário é aplicada sozinha ao abrir; a de colega, nunca

> `P-14`, `P-22`, `P-07` · perfil **completo** · técnica: **tabela de decisão** (de quem é a padrão × de que listagem × de que organização × há recorte na URL). A linha do `?view` vem de P-22 (a URL vence a padrão); as de busca, filtro, ordenação e aba na URL, de P-14 ("sem recorte na URL" — ADV-11). Agrupamento na URL: nenhuma das 10 listagens agrupa; a linha só existiria na listagem de teste agrupável, e fica como lacuna declarada (o mutante de "agrupamento" é o mesmo do de filtro: a padrão que só inspeciona a busca, M154). Com as visões desligadas a padrão não se aplica: CT-07 (R3).

```gherkin
# language: pt
Funcionalidade: Visão padrão

  Regra: ao abrir a listagem sem recorte na URL, aplica-se a visão padrão do próprio usuário nessa listagem, e nenhuma outra

    Esquema do Cenário: [CT-57] só a visão padrão da própria Ana nesta listagem é aplicada ao abrir
      Dado as visões e o compartilhamento ligados
      E <padrao>
      Quando a Ana abre <abertura>
      Então a busca da tabela da Ana é <busca>
      E <recorte_da_url>

      Exemplos:
        | padrao                                                                         | abertura                                                    | busca | recorte_da_url                                       | # condição                       |
        | a visão padrão da Ana em /admin/users com a busca "zz"                         | /admin/users                                                | "zz"  | nada vem da URL                                      | a própria, nesta listagem        |
        | a visão padrão compartilhada da Bia em /admin/users com a busca "zz"           | /admin/users                                                | ""    | nada vem da URL                                      | de colega                        |
        | a visão padrão da Ana em /admin/convites com a busca "zz"                      | /admin/users                                                | ""    | nada vem da URL                                      | a própria, de outra listagem     |
        | a visão padrão da Ana com a busca "zz" e a visão "Outra" com a busca "ok", em /admin/users | /admin/users?view={id de "Outra"}               | "ok"  | "Outra" é a visão ativa                              | visão pedida na URL (P-22)       |
        | a visão padrão da Ana em /admin/users com a busca "zz"                         | /admin/users com a busca "ab" na URL                        | "ab"  | a busca da URL vale                                  | busca na URL (P-14)              |
        | a visão padrão da Ana em /admin/users com a busca "zz"                         | /admin/users com um filtro da tabela na URL                 | ""    | o filtro da URL está ativo                           | filtro na URL (P-14)             |
        | a visão padrão da Ana em /admin/users com a busca "zz"                         | /admin/users com a ordenação por e-mail na URL              | ""    | a ordenação é por e-mail                             | ordenação na URL (P-14)          |
        | a visão padrão da Ana em /admin/users com a busca "zz"                         | /admin/users com uma aba que não é a padrão na URL          | ""    | a aba ativa é a da URL                               | aba na URL (P-14)                |

    Esquema do Cenário: [CT-68] a visão padrão e o ?view da própria visão valem só na organização em que foram gravados
      Dado as visões ligadas
      E a Dani, admin_app na Acme e na Globex, com a visão padrão "Da Acme" em /app/acme/projetos, com a busca "zz"
      Quando a Dani abre <abertura>
      Então a busca da tabela da Dani é <busca>

      Exemplos:
        | abertura                                      | busca |
        | /app/acme/projetos                            | "zz"  |
        | /app/globex/projetos                          | ""    |
        | /app/globex/projetos?view={id de "Da Acme"}   | ""    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M102 | a padrão procurada entre as visíveis (`visibleTo`), não entre as próprias | CT-57 | linha "de colega": busca ""; o mutante aplica "zz" |
| M103 | a padrão não é aplicada ao abrir (`apply_default_view_on_load` desligado) | CT-57 | linha "a própria": "zz"; o mutante deixa "" |
| M104 | a padrão procurada sem a chave da listagem | CT-57 | linha "de outra listagem": ""; o mutante aplica a de /admin/convites |
| M105 | a padrão vence o `?view` da URL | CT-57 | linha "URL": "ok"; o mutante aplica "zz" |
| M121 | ADV-10 — a padrão procurada sem a organização (`ownedBy` sem `forTenant`) | CT-68 | linha `/app/globex/projetos`: ""; o mutante aplica "zz" da Acme |
| M122 | ADV-10 — o `?view` da própria visão resolvido sem a organização | CT-68 | linha `?view` na Globex: ""; o mutante aplica "zz" |
| M123 | ADV-11 — a padrão aplicada mesmo com recorte na URL | CT-57 | linha "busca na URL": "ab"; o mutante aplica "zz" |
| M154 | ADV-11 (parcial na rodada 2) — a padrão só verifica a busca da URL (`tableSearch`) e se aplica quando a URL traz filtro, ordenação ou aba | CT-57 | linhas filtro/ordenação/aba: busca "" e o recorte da URL ativo; o mutante aplica "zz" |

---

## Regra R26 — O Filament resolvido tem o render hook de que o carregamento contínuo depende

> `P-12`, `RQ-02` · perfil **mínimo** · técnica: **EP** (série resolvida) + **rastreio de efeito** ("os assets publicados do Filament acompanham" — ADV-20; comparação por md5 do conteúdo; se a correspondência de nomes `dist/index.js` ↔ `app.js` e `dist/theme.css` ↔ `app.css` não existir no vendor, a asserção vira lacuna declarada com o que foi tentado). Mesma fonte de verdade que `PacotesRodada2Test` CT-32 (constraint na série 5), sem repeti-lo: aqui o que se afirma é a versão **resolvida** no lock e o hook.

```gherkin
# language: pt
Funcionalidade: Versão do Filament da rodada 3

  Regra: o Filament instalado é 5.10 ou posterior, ainda na série 5, e tem o render hook do conteúdo depois da tabela

    Cenário: [CT-62] o lock resolve o Filament 5.10 ou posterior e o hook existe
      Dado o composer.lock do kit
      Quando a versão resolvida de filament/filament é lida
      Então ela é maior ou igual a 5.10.0 e menor que 6.0.0
      E a constante Filament\Tables\View\TablesRenderHook::CONTENT_AFTER existe
      E o conteúdo de public/js/filament/filament/app.js é idêntico ao de vendor/filament/filament/dist/index.js
      E o conteúdo de public/css/filament/filament/app.css é idêntico ao de vendor/filament/filament/dist/theme.css
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M109 | o lock fica no 5.8.x (pacote instalado sem subir o Filament; o rodapé nunca renderiza) | CT-62 | versão ≥ 5.10.0; o mutante resolve 5.8.2 e a constante não existe |
| M110 | o update sai da série 5 (constraint afrouxada para aceitar 6.x) | CT-62 | versão < 6.0.0; o mutante resolve 6.x |
| M133 | ADV-20 — o lock sobe para 5.10.x e os assets publicados do Filament ficam no 5.8 | CT-62 | md5 de `app.js`/`app.css` igual ao do `dist` do vendor; o mutante diverge |

---

## Regra R25 — O compartilhamento só existe com as visões ligadas

> `P-15`, `P-02` · perfil **completo** · técnica: **EP** (visões ligadas × desligadas no formulário) + **2-switch** (desligar → religar, com o oráculo sobre o compartilhamento depois do segundo evento). CT-15 vem de R5 (rodada 1) com o oráculo invertido por P-15: antes ele afirmava só que salvar não dava erro; agora afirma o compartilhamento gravado desligado.

```gherkin
# language: pt
Funcionalidade: Compartilhamento depende das visões

  Regra: o campo de compartilhamento só aparece com as visões ligadas; desligar as visões grava o compartilhamento desligado, e religá-las não o religa

    Cenário: [CT-15] desligar as visões pela tela grava o compartilhamento desligado
      Dado as visões ligadas, o compartilhamento ligado e o carregamento "rolagem" gravados
      E o admin na tela de configurações
      Quando o admin desliga as visões e salva
      Então a tela não tem erro de formulário
      E visoes_de_tabela está gravado como false
      E visoes_de_tabela_compartilhadas está gravado como false
      E tabela_carregamento continua "rolagem"

    Esquema do Cenário: [CT-58] o campo de compartilhamento acompanha o interruptor das visões no formulário
      Dado o admin na tela de configurações com as visões <antes> no formulário
      Quando o admin <acao> as visões no formulário, sem salvar
      Então o campo de compartilhamento está <campo>

      Exemplos:
        | antes      | acao     | campo   |
        | desligadas | liga     | visível |
        | ligadas    | desliga  | oculto  |

    Cenário: [CT-59] religar as visões não religa o compartilhamento nem apaga a marca das visões
      Dado as visões e o compartilhamento ligados gravados
      E a Bia com a visão "Do time" marcada compartilhada em /admin/users
      E o admin desligou as visões na aba Tabelas
      Quando o admin religa as visões na aba Tabelas, sem tocar no compartilhamento, e salva
      Então visoes_de_tabela_compartilhadas continua gravado como false
      E a visão "Do time" não aparece na lista de visões da Ana em /admin/users
      E "Do time" continua gravada com is_public true
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M88 | salvar com as visões desligadas não força o compartilhamento a `false` (campo oculto não desidratado mantém o valor antigo) | CT-15 | `visoes_de_tabela_compartilhadas` gravado false; o mutante mantém true |
| M89 | o campo de compartilhamento sempre visível (sem `visible` dependente das visões) | CT-58 | linha "desliga": oculto; o mutante mantém visível |
| M90 | o compartilhamento forçado a `false` em todo salvamento, mesmo com as visões ligadas | CT-13 (R5) | "visoes_de_tabela_compartilhadas gravado true"; o mutante grava false |
| M91 | religar as visões restaura o compartilhamento anterior (guardado em outra chave ou no estado do formulário) | CT-59 | `visoes_de_tabela_compartilhadas` false e "Do time" fora da lista da Ana; o mutante religa e mostra |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-28, CT-29, CT-64, CT-65, CT-72, CT-73, CT-74, CT-77, CT-30, CT-31, CT-32, CT-33, CT-34 | G5, G6, G7 |
| Autorização exercida na ação (não só `can()`) | CT-20 (ação pelo nome, sem o botão), CT-08, CT-66 (papel da organização aberta), CT-67 (tela de papéis → ação) | G5 |
| Idempotência (ancorada no agregado) | semeadura duas vezes: CT-18 · salvar a mesma visão duas vezes (mesmo nome): CT-56 — P-13 decide que convivem; o agregado afirmado são as duas linhas persistidas e o `state` intacto da primeira | G8 · G5 |
| Concorrência | lacuna declarada: duas gravações simultâneas da 50ª visão (teto de P-13) — o arnês é SQLite `:memory:` numa conexão só, sem duas transações reais; tentado: nenhum caso do kit abre conexões concorrentes. A borda sequencial está em CT-55 (e por organização em CT-61). Duas visões "padrão": a exclusividade não é decidida no `00` | G5 |
| **Fronteira no ponto de entrada** (gravação) | aba *Tabelas*: CT-14 · nome da visão, ao salvar e ao gerenciar: CT-53, CT-54 · teto de visões: CT-55 | G3 · G5 |
| **Domínio condicionado** (tipo × valor) | visões × compartilhamento: CT-04 (linha desligadas/ligado), CT-15, CT-30, CT-35, CT-58, CT-59 · modo × bloco: CT-41, CT-43 | G5, G4, G3 |
| **Estado × operação de escrita** | matriz de 24 células (linha E1: CT-04, CT-05, CT-07, CT-08) | G5, G6 |
| Ausente ≠ null ≠ vazio | carregamento `null`/`""`: CT-09 · `is_public` ausente do payload: CT-25 | G1, G5 |
| Paginação / ordenação | CT-03, CT-43, CT-44, CT-45, CT-46, CT-47, CT-69, CT-70, CT-71 · ordenação por coluna inexistente no estado: CT-39 | G2, G4, G5 |
| Timezone / DST | não se aplica: nenhuma regra temporal (visão não expira; nenhuma data no requisito) | — |
| Unicode / limite de varchar (nome) | 100 caracteres multibyte aceitos e 101 recusados: CT-53, CT-54 · HTML no nome exibido ao colega: CT-36 | G5 |
| Unicidade + soft delete | não se aplica: `TableView` sem `SoftDeletes` (`vendor/sehsah/filament-remember-table/src/Models/TableView.php:class TableView:22`, só `extends Model`) | — |
| Entidade removível (removido ainda funciona?) | visão apagada pedida por id: CT-32 · visões desligadas ainda executam?: CT-08 · autor que perdeu o vínculo, Inativo ou Excluído: lacuna declarada: recorte bloqueado — Q11 (aberta, fora desta entrega) | G5 |
| CRUD combinado | id inexistente na gestão: CT-28 · aplicar id inexistente/apagado: CT-32 · editar sem alterar nada: lacuna declarada — nenhum mutante plausível distingue regravar os mesmos valores | G5 |
| Mass assignment | `remember_table_views`: CT-25, CT-26, CT-28 (linha `is_public`), CT-35 (linhas `desligado`), CT-64 (gerenciar) · `settings`: CT-14 | G5, G6, G3 |
| Upload | não se aplica: nenhum campo de arquivo na Superfície de UI (aba *Tabelas*: 1 Select + 2 Toggles; modais: nome, padrão, compartilhar) | — |
| Precisão monetária | não se aplica: nenhum valor monetário | — |
| **Superfície Livewire** (método público, prop pública, estado do framework) | `activeTableView`: CT-32, CT-33, CT-73 (persistida), CT-74 (pela rota, `view[]`) · `applyTableView()`: CT-32, CT-07 · `resetTableToDefaultState()`: não se aplica: só altera o estado da própria tabela (`vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:resetTableToDefaultState():511-547`); a Action `resetTableView` é coberta em CT-08/CT-20 · `getTableViews()`/`getTableViewActions()`: CT-04, CT-06 · `saveTableView`: CT-20, CT-25 · `updateTableView`: CT-29 · `manageTableViews`: CT-28 · `applyTableView_{md5}`: CT-30 · `infiniteScrollLoadMore`: CT-43, CT-46 · `tableRecordsPerPage`: CT-45 (inclusive `"all"`) · argumento de `infiniteScrollLoadMore` no contínuo: CT-69 · render hook do rodapé: CT-42 · arrays de reinício: CT-47 | G4, G5, G6, G7 |
| **Estado do framework usado sem validar** | `state` reaplicado (filtro, ordenação, agrupamento, aba, tamanho, colunas): CT-39 · `tableRecordsPerPage`: CT-45 | G5, G4 |
| **IDOR por entidade** | `remember_table_views`: CT-28, CT-30, CT-31, CT-32, CT-33, CT-34 · `settings` (grupo `kit`): não se aplica: a autorização da tela de configurações é anterior a esta feature e já tem caso em `tests/Kit/ConfiguracoesDoKitTelaTest.php` ("recusa a tela de configuracoes a quem nao tem a permissao") | G5, G6, G7 · — |
| **Escopo com discriminante nulo** | `tenant_id` nulo numa página do `/app`: fecha — CT-31 (linha h); `/admin` (sem organização) contra visão de organização: CT-65 | G6 |
| **Saída do estado de erro** | nenhum CT termina em 4xx/5xx/redirect; as recusas de CT-08/CT-20 afirmam que a listagem continua operável (a busca responde) | G5 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | valor de fábrica desligado em cada fonte | R1 | EP | G2 | Pest feature HTTP | `tests/Kit/DefaultsDeTabelaTest.php` | M01, M02, M03 |
| CT-02 | migration semeia desligado; rollback remove | R1 | EP | G2 | Pest feature HTTP | `tests/Kit/DefaultsDeTabelaTest.php` | M04, M05, M139 |
| CT-03 | em paginacao a tabela continua como hoje | R2 | EP | G2 | Pest feature HTTP | `tests/Kit/DefaultsDeTabelaTest.php` | M06, M07, M08 |
| CT-04 | desligadas, sem botão nem ações (admin/infra) | R3 | matriz E×O | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M10, M13 |
| CT-05 | desligadas, sem botão (organização) | R3 | matriz E×O | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M13 |
| CT-06 | desligadas, zero consulta às visões | R3 | rastreio de efeito | G7 | Pest feature HTTP | `tests/Kit/VisoesDeTabelaTest.php` | M12 |
| CT-07 | desligadas, nenhuma visão se aplica | R3 | matriz E×O | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M11, M14 |
| CT-08 | desligadas, ações pelo nome não executam | R3 | matriz E×O | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M09 |
| CT-09 | vocabulário fechado do carregamento | R4 | EP + normalização | G1 | unit de regra | `tests/Kit/CarregamentoDaTabelaTest.php` | M15, M16, M19 |
| CT-10 | modo de cada valor; exatamente três | R4 | estado exibido | G1 | unit de regra | `tests/Kit/CarregamentoDaTabelaTest.php` | M18, M19 |
| CT-11 | ilegível gravado não derruba a listagem | R4 | EP | G2 | Pest feature HTTP | `tests/Kit/DefaultsDeTabelaTest.php` | M17 |
| CT-12 | ilegível gravado aparece coagido a paginacao ao abrir a tela (alterado na rodada adversarial 2) | R4 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M17, M137 |
| CT-13 | aba Tabelas grava os três interruptores | R5 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M20, M22, M90 |
| CT-14 | carregamento fora do vocabulário recusado na tela | R5 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M23 |
| CT-15 | desligar as visões grava o compartilhamento desligado (alterado na rodada 2, P-15) | R25 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M25, M88 |
| CT-16 | cada interruptor governa por request | R5 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M21, M22, M24, M128 |
| CT-17 | permissão existe e é oferecida na tela de papéis | R6 | matriz papel×permissão | G8 | Pest feature HTTP | `tests/Kit/PermissoesDeAcoesTest.php` | M28 |
| CT-18 | semeadura single-tenant (duas vezes) | R6 | matriz papel×permissão | G8 | Pest feature HTTP | `tests/Kit/PermissoesDeAcoesTest.php` | M26, M27, M29 |
| CT-19 | semeadura multi-organização | R6 | matriz papel×permissão | G8 | Pest feature HTTP | `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | M26, M27 |
| CT-20 | sem permissão, ações ocultas e não executam | R7 | matriz papel×ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M30, M31, M34 |
| CT-21 | com permissão, cada ação executa | R7 | matriz papel×ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M33, M34 |
| CT-22 | admin sem permissão aplica compartilhada | R7 | matriz papel×ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M32, M119 |
| CT-23 | panel_user aplica compartilhada na organização | R7 | matriz papel×ação | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M32, M119 |
| CT-24 | salvar grava recorte, dona, página, sem compartilhar nem tamanho de página (alterado na rodada 2, P-16) | R8 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M38, M39, M92, M38 |
| CT-25 | campos forjados ao salvar são ignorados | R8 | mass assignment | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M35, M36, M38 |
| CT-26 | organização da visão é a aberta | R8 | mass assignment | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M37 |
| CT-27 | gerenciar renomeia, reordena e apaga as próprias | R9 | matriz papel×ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M44 |
| CT-28 | gerenciar com ids alheios/inexistentes | R9 | matriz papel×ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M40, M41, M43, M112 |
| CT-29 | atualizar só a própria | R9 | matriz papel×ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M42 |
| CT-30 | quem vê cada visão (single-tenant) | R10 | tabela de decisão | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M45, M46 |
| CT-31 | quem vê cada visão (organização) | R10 | tabela de decisão | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M47, M48 |
| CT-32 | visão ativa forjada pelo cliente | R10 | EP de tipo | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M49, M50, M124 |
| CT-33 | `?view` pela URL (single-tenant) | R10 | IDOR | G7 | Pest feature HTTP | `tests/Kit/VisoesDeTabelaTest.php` | M45, M49, M124 |
| CT-34 | `?view` de outra organização pela URL | R10 | IDOR | G7 | Pest feature HTTP | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M49 |
| CT-35 | Compartilhar com todos acompanha o interruptor | R11 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M51, M52, M115, M116, M117, M152 |
| CT-36 | nome compartilhado chega como texto | R11 | taxonomia | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M53, M132 |
| CT-37 | desligar pela tela esconde sem apagar | R12 | 2-switch | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M54, M55 |
| CT-38 | religar devolve as visões | R12 | 2-switch | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M56 |
| CT-39 | partes inexistentes do estado ignoradas; tamanho de página nunca reaplicado (linha nova na rodada 2, P-16) | R13 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M57, M58, M60, M61, M92 |
| CT-40 | recorte, não dados | R13 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M59 |
| CT-41 | toda tabela recebe o contínuo no modo | R14 | EP | G4 (linhas `/app` e organizações: G6) | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` + linhas Tenancy em `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M62, M63, M64, M65, M131 |
| CT-42 | rodapé mostra o modo e o primeiro bloco | R14 | EP | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M63, M65, M138 |
| CT-43 | cada carregamento soma um bloco | R14 | EP | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M63, M66 |
| CT-44 | teto de 500 linhas | R15 | BVA 3-valores | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M68, M69, M70, M135 |
| CT-45 | tamanho de página forjado | R15 | EP de tipo | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M71, M72, M125, M147 |
| CT-46 | próximo bloco numa tabela paginada | R15 | EP | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M73 |
| CT-47 | mudar o recorte volta a um bloco | R16 | EP | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M74, M75, M127 |
| CT-48 | phpstan.neon com o include, 3 exceções | R17 | EP | G9 | Pest feature HTTP | `tests/Kit/QualidadeDeCodigoTest.php` | M76, M77, M78 |
| CT-49 | composer com adotados, sem recusados | R18 | EP | G10 | Pest feature HTTP | `tests/Kit/PacotesRodada3Test.php` | M79, M80 |
| CT-50 | documentação pt e en | R19 | EP | G10 | Pest feature HTTP | `tests/Kit/PacotesRodada3Test.php` | M81, M82, M140, M141 |
| CT-51 | capturas claro e escuro | R20 | EP | G10 | Pest feature HTTP | `tests/Kit/PacotesRodada3Test.php` | M83, M84, M142, M143, M150 |
| CT-52 | traduções pt_BR completas | R21 | EP | G10 | Pest feature HTTP | `tests/Kit/PacotesRodada3Test.php` | M85, M86, M87 |
| CT-53 | nome da visão nova: obrigatório, até 100 caracteres | R22 | BVA 3-valores | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M93, M94, M95, M134 |
| CT-54 | renomear no gerenciamento segue as regras do nome | R22 | BVA (criação ≠ edição) | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M93, M94, M95, M96, M134 |
| CT-55 | a 51ª visão da mesma listagem é recusada | R23 | BVA 3-valores | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M99, M100, M101 |
| CT-56 | duas visões de mesmo nome convivem | R22 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M97, M98 |
| CT-57 | só a padrão da própria Ana nesta listagem se aplica ao abrir | R24 | tabela de decisão | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M102, M103, M104, M105, M123, M154 |
| CT-58 | campo de compartilhamento acompanha as visões no formulário | R25 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M89 |
| CT-59 | religar as visões não religa o compartilhamento | R25 | 2-switch | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M91 |
| CT-60 | rótulos das opções e ajuda das visões na aba Tabelas | R21 | estado exibido | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M106, M107 |
| CT-61 | o teto de 50 é contado em cada organização | R23 | EP (organização) | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M108 |
| CT-62 | o lock resolve Filament ≥ 5.10 e o hook existe | R26 | EP | G10 | Pest feature HTTP | `tests/Kit/PacotesRodada3Test.php` | M109, M110, M133 |
| CT-63 | aplicar restaura as partes do recorte que existem | R13 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M111 |
| CT-64 | campos forjados no gerenciamento são ignorados | R9 | mass assignment | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M113 |
| CT-65 | visão do /app não vale na listagem homônima do /admin | R10 | IDOR (partição entre painéis) | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M38, M114 |
| CT-66 | permissão no papel da organização aberta | R7 | matriz papel × organização | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M118 |
| CT-67 | permissão marcada na tela de papéis libera as ações | R6 | rastreio de efeito | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M120 |
| CT-68 | padrão e ?view próprios só na organização deles | R24 | tabela de decisão | G6 | componente Livewire/Filament | `tests/Tenancy/VisoesDeTabelaTenancyTest.php` | M121, M122 |
| CT-69 | argumento do próximo bloco ignorado no contínuo | R15 | EP de tipo | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M126 |
| CT-70 | sem pedido no teto e no fim, nos dois modos | R15 | estado terminal × modo | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M129, M130 |
| CT-71 | em paginacao, sem rodapé contínuo e com seletor | R2 | EP | G4 | componente Livewire/Filament | `tests/Kit/CarregamentoContinuoTest.php` | M136 |
| CT-72 | master_global não altera visão alheia | R9 | matriz papel × ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M144 |
| CT-73 | quem aplicou a compartilhada a perde quando ela some | R10 | 2-switch | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M145, M153 |
| CT-74 | ?view inválido pela rota abre sem visão | R10 | EP de tipo | G7 | Pest feature HTTP | `tests/Kit/VisoesDeTabelaTest.php` | M146 |
| CT-75 | aplicar restaura o agrupamento gravado | R13 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` (listagem de `tests/Kit/fixtures`) | M148 |
| CT-76 | salvar com ilegível gravado regrava paginacao | R4 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M17, M149 |
| CT-77 | master_global não vê nem aplica visão privada alheia | R10 | matriz papel × ação | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M151 |
| CT-78 | chaves de visões distintas por página; a gravada é a da página aberta | R8 | EP | G5 | componente Livewire/Filament | `tests/Kit/VisoesDeTabelaTest.php` | M38 |
| CT-B01 | rolar ao fim carrega o próximo bloco (`05`) | R14 | EP | G11 | browser | `tests/Browser/CarregamentoContinuoTest.php` | M67 |

> CT-41 atravessa duas suítes (as linhas `/app` e `/admin/organizacoes` exigem `tests/Tenancy`): o executor implementa as linhas Kit em `CarregamentoContinuoTest.php` e as Tenancy em `VisoesDeTabelaTenancyTest.php`, com o mesmo ID e o dataset partido — o teste de sincronia de IDs compara a união dos arquivos.

## Rastreabilidade (RQ, P-nn e perguntas → regra → CT)

| Origem | Regras | Cenários | Estado |
|---|---|---|---|
| RQ-01 | R18, R19 | CT-49, CT-50 | coberta |
| RQ-02 | R17, R18, R26 | CT-48, CT-49, CT-62 | coberta |
| RQ-03 | — | — | sem cenário: restrição de processo (Codex CLI) |
| RQ-04 | R5, R6, R14, R21 | CT-13…CT-17, CT-41…CT-43, CT-52, CT-60, CT-67 | coberta |
| RQ-05 | R1, R2, R3, R5 | CT-01…CT-08, CT-13, CT-14, CT-16 | coberta |
| RQ-06 | R19, R21 | CT-50, CT-52, CT-60 | coberta |
| RQ-07 | R20 | CT-51 | coberta |
| RQ-08 | — | — | sem cenário: restrição de processo (branch) |
| P-01 | R6, R7 | CT-17…CT-23 | coberta |
| P-02 | R1, R8, R10, R11, R25 | CT-01, CT-25, CT-30, CT-33, CT-35, CT-36, CT-15, CT-58, CT-59 | coberta |
| P-03 | R3, R12 | CT-04…CT-08, CT-37, CT-38 | coberta |
| P-04 | R14 | CT-41…CT-43, CT-B01 | coberta |
| P-05 | R17 | CT-48 | coberta (o interruptor é escopo apagado) |
| P-06 | R18, R19 | CT-49, CT-50 | coberta |
| P-07 | R8, R9, R10, R24 | CT-24…CT-34, CT-57 | cobertura parcial: recorte do autor sem vínculo/Inativo/Excluído **bloqueado — Q11** |
| P-08 | R7, R9 | CT-20…CT-23, CT-27…CT-29 | coberta |
| P-09 | R13 | CT-39, CT-40 | coberta |
| P-10 | R2, R14, R15, R16 | CT-03, CT-41…CT-47, CT-B01 | coberta |
| P-11 | R4 | CT-09…CT-12 | coberta |
| P-12 | R26 | CT-62 | coberta |
| P-13 (Q6) | R22, R23 | CT-53…CT-56 | coberta |
| P-18 (Q13) | R23 | CT-61 | coberta |
| P-19 (Q14) | R11 | — | substituída por P-26 |
| P-20 (Q15) | R9 | CT-72 | coberta |
| P-21 (Q16) | R22 | CT-53, CT-54 | coberta |
| P-22 (Q17) | R24 | CT-57 | coberta |
| P-23 (Q18) | R15 | CT-44 | coberta |
| P-24 (Q19) | R4 | CT-12 | coberta |
| P-25 (Q20) | R19 | CT-50 | coberta |
| P-26 (Q21) | R11 | CT-35 (e CT-59 para as visões quando só a aba das configurações é salva) | coberta |
| P-27 (Q22) | R10 | CT-77 | coberta |
| P-14 (Q7) | R24 | CT-57 (e CT-07, desligadas) | coberta |
| P-15 (Q8) | R25 | CT-15, CT-58, CT-59 | coberta |
| P-16 (Q9) | R13, R8 | CT-24, CT-39 | coberta |
| P-17 (Q10) | R21 | CT-52, CT-60 | coberta |
| Q11 | — | — | aberta, fora desta entrega: sem cenário |
| Q13 → P-18 | R23 | CT-61 | decidida |
| Q12 → D6 | R7 | CT-20 (ação acionada pelo nome, como se a barreira só na Action fosse insuficiente) | decisão de desenho |

## Revisão adversarial — rodada 1

`fw-adversario-ct`, 2026-10-14: 41 achados, todos fechados, **nenhum rejeitado**; as 7 perguntas viraram Q14…Q20 → P-19…P-25 (decididas pela sessão). Como o fechamento criou cenário novo, a rodada 2 é obrigatória (teto de 2).

| ADV | O que mudou | CT | Mutante |
|---|---|---|---|
| ADV-01 | cenário novo: aplicar restaura filtro, ordenação, colunas e aba que existem | CT-63 | M111 |
| ADV-02 | CT-28 repete as linhas 1 a 3 com o compartilhamento ligado | CT-28 | M112 |
| ADV-03 | cenário novo: campos forjados no gerenciamento (`user_id` + `is_default`, `table_key`, `tenant_id`) | CT-64 | M113 |
| ADV-04 | cenário novo: visão do `/app/acme/users` não vale em `/admin/users` (lista, `?view`, `applyTableView`) | CT-65 | M114, M38 |
| ADV-05 | linha nova no CT-35: ligado, salvar sem marcar → false e a Bia não vê | CT-35 | M115 |
| ADV-06 | linha nova no CT-35: ligado, desmarcar no gerenciamento → false e a Bia deixa de ver | CT-35 | M116 |
| ADV-07 | cenário novo com o Fábio (admin_app na Globex, panel_user na Acme, Globex na sessão) | CT-66 | M118 |
| ADV-08 | CT-22 e CT-23 afirmam que o botão Visões e a opção de aplicar são oferecidos sem a permissão | CT-22, CT-23 | M119 |
| ADV-09 | cenário novo: marcar a permissão na tela de papéis libera saveTableView ao panel_user | CT-67 | M120 |
| ADV-10 | cenário novo: padrão e `?view` próprios da Dani na Acme não valem na Globex | CT-68 | M121, M122 |
| ADV-11 | linha nova no CT-57: busca na URL vence a padrão (P-14) | CT-57 | M123 |
| ADV-12 | linha nova no CT-32 e no CT-33: id da própria visão de outra listagem | CT-32, CT-33 | M124 |
| ADV-13 | linha `"all"` no CT-45 | CT-45 | M125 |
| ADV-14 | cenário novo: `infiniteScrollLoadMore(999)` no contínuo cresce um bloco | CT-69 | M126 |
| ADV-15 | linhas novas no CT-47: aplicar visão e limpar a tabela reiniciam o bloco | CT-47 | M127 |
| ADV-16 | linhas novas no CT-16: `rolagem → paginacao` e `botao → rolagem` | CT-16 | M128 |
| ADV-17 | cenário novo: teto e fim × rolagem e botão, sem sentinela nem botão; CT-44 borda−1 afirma que não pede busca/filtro | CT-70, CT-44 | M129, M130 |
| ADV-18 | linha nova no CT-41: relation manager de usuários das organizações. Widget de tabela: o kit não tem um conhecido nesta derivação; se o executor achar algum, vira linha do CT-41, senão lacuna declarada | CT-41 | M131 |
| ADV-19 | CT-36 vira Esquema com o ponto de exibição "rótulo da visão ativa" | CT-36 | M132 |
| ADV-20 | CT-62 compara o md5 de `app.js`/`app.css` publicados com o `dist` do vendor (lacuna declarada se os nomes não corresponderem) | CT-62 | M133 |
| ADV-21 | linha "só espaços" no CT-53 e no CT-54 (P-21) | CT-53, CT-54 | M134 |
| ADV-22 | BVA do total no CT-44: 499, 500 (P-23), 501 | CT-44 | M135 |
| ADV-23 | CT-33 e CT-34 afirmam a busca da tabela montada (recorte), além do nome | CT-33, CT-34 | M49 |
| ADV-24 | CT-45 afirma "os 7 primeiros da ordenação padrão" (identidade) | CT-45 | M147 |
| ADV-25 | CT-39: ordenação e aba = padrão da listagem; tamanho "continua 10" | CT-39 | M58, M61 |
| ADV-26 | CT-35 linhas `desligado` submetem o formulário com o valor forjado | CT-35 | M51 |
| ADV-27 | CT-03 com as opções exatas da `main`; cenário novo com o HTML da tabela em paginação (sem rodapé, com seletor) | CT-03, CT-71 | M07, M136 |
| ADV-28 | CT-12 reescrito por P-24 (vocabulário fechado, precedente da densidade): campo preenchido com `paginacao` e regravado `"paginacao"` | CT-12 | M137 |
| ADV-29 | `table_key` como FQCN literal da página no CT-24, CT-25 e CT-64 — **revisto na rodada 2 por ADV-50** (o oráculo passou a ser a distinção das chaves, CT-78) | CT-24, CT-25, CT-64 | M38 |
| ADV-30 | CT-42 afirma que o seletor não aparece na tabela renderizada | CT-42 | M138 |
| ADV-31 | CT-16 fixa o canal (mesmo processo, sem request) e M24 reescrito | CT-16 | M24 |
| ADV-32 | CT-02 vira Esquema: `down()` remove; `down()`+`up()` semeia | CT-02 | M139 |
| ADV-33 | CT-31 com um só `Quando` (aplicar); a navegação foi para o `Dado` | CT-31 | — |
| ADV-34 | CT-38 sem ação no `Então`: a ação de aplicar "Uma" existe e o `state` continua com "zz" | CT-38 | M56 |
| ADV-35 | CT-08 e CT-20: linha de gerenciar separada em renomear e apagar | CT-08, CT-20 | — |
| ADV-36 | CT-50 afirma o critério de "agregar valor" | CT-50 | M140 |
| ADV-37 | CT-50 afirma motivo e reabertura de cada recusado e o efeito de cada opção | CT-50 | M141 |
| ADV-38 | CT-51 afirma os arquivos publicados e o clipe animado do contínuo nos dois temas | CT-51 | M142, M143 |
| ADV-39 | cenário novo: master_global não renomeia, não apaga nem atualiza a visão da Bia (P-20) | CT-72 | M144 |
| ADV-40 | cenário novo: quem aplicou a compartilhada a perde quando ela é descompartilhada, apagada ou o compartilhamento é desligado | CT-73 | M145 |
| ADV-41 | cenário novo pela rota: `?view` de visão apagada, `abc` e `view[]=1` → 200 e sem visão; se o Livewire estourar com array, o executor devolve o vermelho (b) | CT-74 | M146 |

## Revisão adversarial — rodada 2

`fw-adversario-ct`, 2026-10-14 (última rodada; teto de 2). Conferência dos 41: 36 fechados e 5 parciais (ADV-01, ADV-11, ADV-15, ADV-28, ADV-38), além de 9 achados novos (ADV-42…ADV-50). Perguntas: Q21 → P-26 (substitui P-19) e Q22 → P-27. Fechamento: tudo fechado, **nada rejeitado**, nenhuma pergunta nova. Os cenários novos deste fechamento (CT-75…CT-78) **não passaram por revisão independente** (o teto foi atingido) — débito para o `feature-quality-gate`.

| ADV | Situação na rodada 2 | O que mudou | CT | Mutante |
|---|---|---|---|---|
| ADV-01 | parcial (sem agrupamento) | cenário novo do agrupamento na listagem de teste agrupável (com ADV-47) | CT-75 | M148 |
| ADV-11 | parcial (só busca na URL) | CT-57 ganhou as linhas de filtro, ordenação e aba na URL e a coluna `recorte_da_url`; agrupamento na URL fica como lacuna declarada (nenhuma listagem agrupa; o mutante plausível, "só olha a busca", morre nas outras linhas) | CT-57 | M154, M123 |
| ADV-15 | parcial (linha "limpa a tabela" vazia) | a linha começa com um filtro que casa com os 30, aplicado antes dos dois pedidos (com ADV-45) | CT-47 | M127 |
| ADV-28 | parcial (verificação no `Dado`) | CT-12 dividido: CT-12 abre a tela e afirma no `Então` que o campo mostra `paginacao`; CT-76 salva e afirma `"paginacao"` gravado (com ADV-44) | CT-12, CT-76 | M137, M149 |
| ADV-38 | parcial (clipe só declarado) | CT-51 afirma que o `.gif` de cada clipe existe em `art/` | CT-51 | M150 |
| ADV-42 | novo | cenário novo: o master_global não vê nem aplica a visão privada da Bia (lista, `?view`, `applyTableView`) — P-27 | CT-77 | M151 |
| ADV-43 | novo | linha nova no CT-35: com o compartilhamento desligado, "Antiga" reenviada sem alteração é regravada privada — P-26 | CT-35 | M152, M117 |
| ADV-44 | novo | ver ADV-28 | CT-12, CT-76 | M137, M149 |
| ADV-45 | novo | ver ADV-15 | CT-47 | M127 |
| ADV-46 | novo | CT-73: a Bia muda a busca gravada para "yy" antes do evento; o `Então` afirma que a busca da Ana não é "yy" | CT-73 | M153 |
| ADV-47 | novo | ver ADV-01 | CT-75 | M148 |
| ADV-48 | novo | CT-70: `data-has-more` falso, sem sentinela e sem botão em todas as linhas; o texto vira coluna — teto pede busca/filtro (coerente com CT-44), fim diz que todos foram carregados | CT-70 | M129, M130 |
| ADV-49 | novo | CT-64: o segundo `Então` vira coluna — a linha `user_id` afirma que a Bia não ganha padrão; `table_key` e `tenant_id` afirmam que "Minha" continua aplicável pela Ana em /admin/users | CT-64 | M113 |
| ADV-50 | novo | o oráculo deixa de ser o FQCN literal: CT-78 afirma as três chaves distintas (Admin…ListUsers ≠ App…ListUsers ≠ Admin…ListConvites) e a visão gravada pela tela com a chave da página aberta; CT-24, CT-25 e CT-64 citam "a chave que a página usa"; `chaveDaTabela()` passa a chamar `getTableViewsKey()` da página montada (Setup Global) | CT-78, CT-24, CT-25, CT-64 | M38 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Coerção booleana de `KIT_TABELA_VISOES_SALVAS` (`"0"`, `"off"`, `""`) | `BooleanoDoEnv::comPadrao()` já é coberto por casos de outras wikis; o default de fábrica (CT-01) mata M01/M03 |
| A migration semeia "o valor da config vigente" (não o literal) | mecanismo do plano, sem cláusula; CT-02 mata o default errado |
| CT-B do tema escuro do rodapé | o requisito não fixa cor; `assertSee` não prova legibilidade e o primeiro screenshot cria a baseline com o defeito (`pest-plugin-browser.md` fato 10). A captura nos dois temas (RQ-07) é CT-51 |
| CT-B do botão "Carregar mais" | o clique é `wire:click` padrão do Livewire; CT-42 prova o atributo e CT-43 o efeito do método |
| Visões em `/infra` com persona `infra` sem a permissão | mata o mesmo M30/M31 que CT-20 (admin revogado) |
| Smoke HTTP 200 de todas as telas em `rolagem` | `assertOk()` sozinho é oráculo proibido; CT-41 monta as tabelas pelo componente e afirma o modo |
| Visão de outra organização apagada pela mesma Dani | a dona é a mesma pessoa (P-08 permite ao dono); o `00` não decide o contexto da exclusão e não há fronteira entre pessoas a matar |
| `Table::make(new Pagina)` para o modo de cada tabela | não aplica a definição de tabela da própria página — uma listagem com `paginated()` próprio passaria (M64); CT-41 monta o componente |
