# Relatório de QA — feat/estudo-de-pacotes-rodada-3: Estudo e adoção de pacotes Filament, rodada 3 (tabelas e PHPStan)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: rolagem com `x-intersect`; domínio sensível: autorização por permissão nova e fronteira por organização)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `PapeisSeeder`, `config/filament-shield.php`, `ConfiguraFilamentGlobal` (toda tabela), settings, `tests/Pest.php`, bump do Filament 5.8.2 → 5.10.2 (P-12), `kit.css` global (P-31) · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 8 · Cosmético: 1
- Não verificadas: H (só em parte: teclado e foco não exercitados) e K (K2: o score de mutação não é certificável, ver QA-01). Causas em *Não Verificado*.
- `RQ` abertas: nenhuma. Pergunta aberta: Q11 (P-07, autor inativo ou sem vínculo). O pacote mantém hoje a visão compartilhada desse autor visível aos colegas, e o recorte de R10 está bloqueado no `04`. Isso conta como item de teto.
- Ambiente: app em `http://localhost:8000` · Pest 5.0.5 · MCP: laravel-boost indisponível (CONNECT_TIMEOUT), Playwright MCP ausente. O confronto dinâmico foi feito com Playwright Node do `node_modules`, contra o app servido.

## Achados

### QA-01 — Score de mutação "100 %, 0 sobreviventes" não é certificável no arnês Windows · Major · destino 1

- **Dimensão**: L6 / K2
- **Relacionado a**: `03` Verificação Final (linha 96), `CHANGELOG.md:49-51`
- **Esperado**: um score de mutação só pode ser declarado quando um mutante morto significa que um teste cobridor falhou de fato (SKILL, K2: plausibilidade; Proibição 11).
- **Observado**: o `pest-plugin-mutate` lança cada mutante com `--filter="<um nome por teste cobridor>"` por Symfony Process. No Windows, o Symfony leva o argumento para uma variável de ambiente que o `cmd /V:ON` expande. Acima de cerca de 8 191 caracteres, a variável chega vazia e o argumento vira `--filter=`. O Pest então responde "No tests executed!" com exit 1, e o plugin conta todo exit diferente de zero como morto. O resumo do arnês mostra "406 tested" e nenhum timeout. Só que, nas linhas cobertas por muitos testes (`mapaDeConfiguracao()` de `app/Settings/ConfiguracoesDoKit.php`, `configuraTable()`), o filtro passa desse limite. Um sinal concreto: os dois `RemoveIntegerCast` em `app/Providers/Concerns/ConfiguraFilamentGlobal.php:defaultPaginationPageOption:291` e `:infiniteScroll:330` aparecem como mortos, embora sejam aparentemente equivalentes, porque todos os testes cobridores passam inteiros.
- **Repro**:
  1. `php -r 'require "vendor/autoload.php"; $f=implode("|",array_fill(0,692,"Teste::(.*)x")); $p=new Symfony\Component\Process\Process(["php","-r","echo strlen(\$argv[1]);","--","--filter=\"$f\""]); $p->run(); echo $p->getOutput();'` → `9` (só `--filter=` chega). Com 7 994 caracteres chegam 8 005.
  2. `php vendor/bin/pest tests/Kit/CarregamentoDaTabelaTest.php --bail --filter=` → "No tests executed!", exit 1.
  3. `vendor/pestphp/pest-plugin-mutate/src/MutationTest.php:start():48`, `:95` monta o `--filter` com um nome por teste cobridor, e `:hasFinished():120`, `:isSuccessful:138` trata como morto qualquer resultado que não seja sucesso.
- **Evidência**: as saídas acima. `scratchpad/mutate2.txt` traz `Mutations: 406 tested · Score: 100.00% · Duration: 33,965.63s`, sem duração por mutante.
- **Destino**: 1 (alegação do `03`/CHANGELOG). Também 4 para o arnês (`pestw.cmd` não protege contra isso).
- **Ação exigida**: tirar o "Score 100 % / 0 sobreviventes" como resultado certificado do `03` (linha 96) e do `CHANGELOG.md` (linhas 49-51), conferido por `grep -rn "406\|100 %" wikis/specs/feat/estudo-de-pacotes-rodada-3/ CHANGELOG.md`. Depois, remedir num ambiente em que o filtro não é truncado (Linux/WSL/CI, ou por arquivo de teste com filtro abaixo de 8 KB), com a lista de sobreviventes.

### QA-02 — `01` contradiz o código entregue sem marca de alteração · Major · destino 1

- **Dimensão**: L3
- **Relacionado a**: passos 8 e 9 do PRD, `## Mapeamentos`, P-29, P-30, D6
- **Esperado**: o PRD diz o que o código faz, ou traz `*(alterado em …)*` (SKILL, L3).
- **Observado**: `01-plano-acao.md:338` traz a chave `'usar:visoesdetabela'`, mas o código usa `config/filament-shield.php` `'usar:visoes_de_tabela'` (o `03`, linha 40, registra que a grafia antiga gerava `Usar:Visoesdetabela`). O trecho do passo 9 (`:363-373`) mostra `PERMISSAO`, `habilitadas()`, aliases públicos e só `->authorize()`. O código tem `PERMISSAO_DAS_VISOES`, `visoesHabilitadas()`, `as protected …DoPacote` (P-30) e `->hidden(! visoesHabilitadas())` (P-29) (`app/Filament/Concerns/VisoesDeTabela.php:VisoesDeTabela:80`, `:82-157`). Os `## Mapeamentos` (`:458`) ainda citam `VisoesDeTabela::habilitadas()`. Esses desvios só existem no `03` e no `00`.
- **Repro**: `grep -n "visoesdetabela\|habilitadas()\|PERMISSAO\b" wikis/specs/feat/estudo-de-pacotes-rodada-3/estudo-de-pacotes-rodada-3/01-plano-acao.md` × `grep -n "visoes_de_tabela\|visoesHabilitadas\|as protected\|hidden(" config/filament-shield.php app/Filament/Concerns/VisoesDeTabela.php`
- **Evidência**: as linhas `01:338, 363-373, 458` contra `app/Filament/Concerns/VisoesDeTabela.php:VisoesDeTabela:80`, `:83-157`. O `04:118` e o `04:720` (M33) citam `habilitadas()`/`PERMISSAO` e a grafia antiga como nome de implementação ou mutante, o que é aceitável lá.
- **Destino**: 1
- **Ação exigida**: reescrever o passo 8, o passo 9 e os `## Mapeamentos` do `01` com os nomes reais, a chave real, os aliases protegidos e o `hidden()`, marcando `*(alterado em …)*`.

### QA-03 — Evidências vencidas em `## Conformidade com Rules` · Minor · destino 1

- **Dimensão**: L4
- **Observado**: a linha `filament.md` (`03:136`) cita "`->authorize(…)` + `->visible()`", mas o código usa `hidden()`. A linha `specs.md` (`:143`) diz "`citacoes.sh` … (pendente do bump 1.0.1)". A linha `testes.md` (`:146`) diz "`ids-ct.sh` silencioso salvo CT-B01, pendente". Hoje o código cumpre as rules: `conformidade-rules.sh` sai com exit 0, e `citacoes.sh`, `ids-ct.sh` também saem com 0.
- **Repro**: `sed -n 136p;143p;146p` do `03` × `app/Filament/Concerns/VisoesDeTabela.php:hidden:134`.
- **Ação exigida**: atualizar a evidência das três linhas.

### QA-04 — ADR-01 com o custo velho · Minor · destino 1

- **Dimensão**: L3
- **Observado**: `02-decisoes-arquiteturais.md:61` diz que "o `booted` do pacote faz uma consulta por carga de listagem quando a feature está ligada". O custo medido (`01` Modelo de Execução, alterado em 2026-10-15) é de 4 consultas na montagem e 3 por render, e eu reproduzi esses números (ver E).
- **Repro**: `grep -rn "uma consulta" wikis/specs/feat/estudo-de-pacotes-rodada-3/`
- **Ação exigida**: alinhar a consequência negativa da ADR-01 ao número medido.

### QA-05 — Captura da aba *Tabelas* só no tema claro, e o `03` diz o contrário · Minor · destino 1

- **Dimensão**: A / L5 (RQ-07)
- **Observado**: `art/admin-configuracoes-tabelas.png` não tem par `-escuro`, e a página pt/en (`:156`) publica só a versão clara. O Desvio CT-51 do `03` afirma que "toda captura da feature tem o par escuro". O oráculo do CT-51 (`04:1538`) só cobre visões e carregamento.
- **Repro**: `ls art | grep configuracoes-tabelas`
- **Ação exigida**: decidir se a aba entra em RQ-07 ("quando tem o que mostrar"). Se entrar, criar a captura escura; se não, corrigir a frase do `03`.

### QA-06 — Rodapé em tema escuro sem CT-B, embora o plano o declare só-navegador · Minor · destino 3

- **Dimensão**: G (nível 2)
- **Observado**: o *Gate de CT-B* do `01` (`:166`) nomeia "o do tema escuro do rodapé" como cenário que só o navegador prova. O `05` tem só o CT-B01 (rolagem, `inLightMode()`). O tema escuro fica provado apenas por captura (o CT-51 confere a existência do arquivo, não a cor). No nível 3 eu conferi visualmente, e está correto: texto `oklch(0.705…)` sobre `oklch(0.21…)`.
- **Ação exigida**: levar a lacuna à `feature-test-design`, para um CT-B com `inDarkMode()` e asserção de cor via `getComputedStyle`, ou registrar o corte no `05` com o motivo.

### QA-07 — CT-28 não afirma que o id inexistente deixa de criar visão · Minor · destino 3

- **Dimensão**: K1
- **Observado**: `tests/Kit/VisoesDeTabelaTest.php:assertModelExists:374` (K1: `assertModelExists` só pela chave). As linhas "id 999999 renomeado para Fantasma" afirmam só "Minha continua". Um mutante que cria uma visão nova para um id desconhecido sobrevive.
- **Repro**: `bash .claude/skills/feature-quality-gate/scripts/k1-oraculo-fraco.sh tests/Kit/VisoesDeTabelaTest.php` e a leitura de `04:811-829`.
- **Ação exigida**: no Gherkin e no teste, acrescentar que a contagem de `remember_table_views` não muda e que não existe nenhuma visão "Fantasma".

### QA-08 — N+1 por linha na listagem de usuários, ampliado pelo carregamento contínuo · Minor · destino 2

- **Dimensão**: E
- **Observado**: um render de `ListUsers` com 10 linhas faz 34 consultas, 27 delas `select exists(… roles … model_has_roles …)`, uma por linha (o N+1 já existia). No modo contínuo, cada bloco redesenha todas as linhas carregadas, até 500, e a conta cresce com o total na tela. As docs registram isso como consequência.
- **Repro**: tinker só-leitura com `Livewire::test(ListUsers::class)`, `$refresh` sob `DB::enableQueryLog()` e agrupamento por template.
- **Ação exigida**: carregar os papéis de antemão na query da listagem de usuários, ou aceitar como débito declarado.

### QA-09 — `## Impacto em Features Existentes` subestimou o bump do Filament · Minor · destino 1

- **Dimensão**: J
- **Observado**: o `01` (`:200-207`) não cita o bump 5.8 → 5.10. O medido está nas Notas do `03` (`:250`): notificações foram para `NotificationMailbox`, widgets passaram a dar 403 antes do `mount()`, rótulos agora são `string|Htmlable`, 11 citações se deslocaram, houve o flake dos gráficos, e o `z-index` do dropdown passou a valer para todo cabeçalho (P-31).
- **Ação exigida**: levar os impactos medidos à seção do `01`.

### QA-10 — Termos decididos na feature fora do glossário · Minor · destino 1

- **Dimensão**: L7
- **Observado**: "visão salva", "visão padrão", "visão compartilhada", "carregamento contínuo" e "bloco" foram decididos (P-07…P-16) e aparecem na UI e nas docs, mas não estão em `wikis/glossario.md`.
- **Ação exigida**: registrar os termos, com "Não confundir com" (por exemplo, visão salva × persistência de filtros na sessão).

### QA-11 — Docs omitem agrupamento e aba na condição da visão padrão · Cosmético · destino 1

- **Dimensão**: L5
- **Observado**: na linha "Padrão"/"Default" de `docs/{pt,en}/recursos/visoes-de-tabela-e-carregamento.md:55` falta "agrupamento" e "aba", que estão em P-14 e em `requestHasTableState()`.

## Matriz de Rastreabilidade

`rastreabilidade.sh` saiu com exit 0. Não há `07-tickets/` (não fatiado). As lacunas aparecem só no julgamento:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-07 | capturas claro e escuro | 14 | CT-51 | — | `KitArte`, `CapturaDeArteTest` | aba *Tabelas* sem par escuro | ⚠️ QA-05 |
| P-29/P-30 | `hidden()` / aliases protegidos | 9 | CT-79, CT-80 | — | `VisoesDeTabela` | ✅ no app; o `01` contradiz | ⚠️ QA-02 |
| P-07 (Q11) | autor inativo ou sem vínculo | — (bloqueado) | — | — | default do pacote | ⏳ pergunta aberta | teto |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 1 achado (QA-05). `rastreabilidade.sh` exit 0; `indice.sh --check`: sem `07-tickets/`. Conferi o diff por passo (passos 1–14) |
| B | Fronteiras e dados | ✅ | Validação lida: vendor `name` `required`/`maxLength(100)`, sync grava só `name`/`is_default`/`is_public`/`sort_order` com dono do servidor. Coberta por R11/R15/R22 (CT-53, CT-45…). Fallback inline: sem `--agent` instalado, e o hook nega arquivo de sonda |
| C | Matriz de permissão | ✅ | admin com e sem a permissão × 4 verbos (CT-20/21), panel_user (CT-23), admin_app por organização aberta (CT-66), master_global (CT-77, P-20). Com as visões desligadas, o forjado em `/admin/users` dá `applyTableView`→false, `mountAction`→null e alias→MethodNotFound |
| D | Observabilidade | ✅ | Nenhum log novo, por decisão do `01`. Trilha `configuracoes-2026-10-15.log` ao salvar a aba: `[AuditarConfiguracoesDoKit@handle]` com nomes das chaves e `user_id`, sem PII |
| E | Performance | ⚠️ | Reproduzido: desligadas, 0 consultas a `remember_table_views`; ligadas, 4 na montagem e 3 por render. QA-08 |
| F | UX de erro | ✅ | Modais e aba em pt_BR, coerção de valor ilegível (CT-12/76). A notificação "Salvo" foi observada |
| G | Tema e cor | ⚠️ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 sobre o diff: exit 0. Nível 3 com Playwright em `colorScheme: dark`: rodapé, dropdown, modal e aba legíveis. Nível 2: QA-06 |
| H | Acessibilidade | ⏭️ parcial | axe em `/admin/users` com os recursos ligados: 2 violações anteriores à feature (`button-name` em `.fi-ta-col`, `empty-table-header`), nenhuma no botão *Visões* ou no rodapé. Teclado e foco não exercitados |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (`## Revisão do Diff`, 9 linhas, CR-03 aceito como limitação). Além dele: nenhuma rota nova; mass assignment conferido no sync; `$wire` forjado com as visões desligadas, reproduzido no app |
| J | Regressão adjacente | ⚠️ | Kit 4 141/4 144 (3 skipped), Tenancy 616/616 (4 processos). CT-B ancestrais (`Browser/ConfiguracoesDoKitTest`, `DockDeAtalhosTest`, `TemaEscuroTest`) 11/11. RCRCRC: risco no bump. QA-09 |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, 18 candidatas: 10 capturas de arte (rejeitadas: o oráculo é o CT-51), 6 em linhas anteriores ao diff, 1 em CT-28 confirmada (QA-07). K2 não verificada (QA-01). Revisão adversarial do `04` feita, com débito para CT-75…CT-81: revisei-os de leve aqui, sem achado além do QA-07 |
| L | Consistência documental | ❌ | L1 `ids-ct.sh` exit 0 (contagem 81/26/164 confere). L2 `citacoes.sh` exit 0. L4 `conformidade-rules.sh` exit 0, com QA-03 no julgamento. L6 `checkbox-sem-evidencia.sh` exit 0, com QA-01 no julgamento. L3: QA-02, QA-04. L5: QA-11. L7: QA-10. Números do `03` reproduzidos: 7 `KIT_TABELA_`, 61/21/64/82, badge e contagens por arquivo (147, 7, 46, 55, 15, 26, 34) |

## Débitos Aceitos

- nenhum aceito ainda (QA-03…QA-11 aguardam decisão no step 10)

## Suspeitas Não Confirmadas

- Não consegui quantificar quantos dos 406 mutantes caíram no filtro truncado: o mapa linha → testes não foi persistido. O mecanismo está provado (QA-01).

## Não Verificado

- K2 (mutação) — motivo: prova indispensável à K pendente. O filtro do mutante é truncado acima de cerca de 8 KB no Windows, e o "morto" pode ser "No tests executed".
- H, teclado e foco — motivo: não exercitados; só o axe rodou.
- Sondas dinâmicas de B com gravação (nome de 101 caracteres, HTML, emoji) — motivo: o hook nega arquivo de sonda e gravar visão no banco local está fora do estado permitido. Coberto por leitura e pelos CTs.
- Suíte de navegador fora dos ancestrais (22 arquivos) depois do bump do Filament — motivo: exige `npm run build`. Os assets do Filament mudaram nesta branch.

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 0 abertos (QA-08 aceito como débito) · Cosmético: 0
- Não verificadas: K (K2, score de mutação, não certificável no Windows) e H em parte (teclado e foco). Causas em *Não Verificado — Ciclo 2*.
- `RQ` abertas: nenhuma. Pergunta aberta: Q11 (P-07), registrada como débito de requisito.
- Ambiente: app em `http://localhost:8000`, encontrado e deixado com os três interruptores desligados (`paginacao`/`false`/`false`) · Pest 5.0.5 · MCP: laravel-boost e Playwright indisponíveis.
- Escopo do que mudou depois do ciclo 1 (`aee4a5ac..HEAD`, 3 commits): no código de aplicação, só uma linha em `app/Console/Commands/KitArte.php`. O resto são testes (CT-B02, CT-28, CT-51, `KitArteTest`), captura escura da aba, docs, README, CHANGELOG, glossário e `01`/`02`/`03`/`04`/`05`/`06`. Como nenhum código de app de runtime mudou, as dimensões dinâmicas do ciclo 1 (B–I) continuam válidas e não foram refeitas.

### Revalidação item a item

| Achado | Ciclo 1 | Estado no ciclo 2 | Evidência |
|---|---|---|---|
| QA-01 score de mutação não certificável | Major · 1+4 | **fechado (alegação retirada)**; K2 segue como débito | O `03` (Verificação Final, item `pest --mutate`) e o `CHANGELOG.md:49-54` declaram o 406/406 inválido e a remedição por chunks também não certificável. `scratchpad/mutate4.txt` confere com o que o `03` diz: rodada 4 com "No tests executed!" e exit 1; rodadas 7 e 8 com 61 e 17 mutantes em 2,73 s e 0,80 s; soma de 570 testados. A evidência substituta (amostragem manual de 5 mutantes do trait) não foi reproduzida por este gate (exige editar o código). O arnês foi registrado nas *Notas de Implementação* (destino 4) |
| QA-02 `01` × código | Major · 1 | **fechado** | O `01` passo 8 traz `usar:visoes_de_tabela`. O passo 9 traz `as protected`, `PERMISSAO_DAS_VISOES`, `visoesHabilitadas()` e `->hidden()`. Os `## Mapeamentos` trazem `visoesHabilitadas()`. Tudo com `*(alterado em 2026-10-15, QA-02)*` e conferido contra `app/Filament/Concerns/VisoesDeTabela.php` |
| QA-03 evidências vencidas em *Conformidade* | Minor · 1 | **fechado** | `filament.md` cita `hidden()` (RD-01). Nenhum "pendente" restante nas linhas `specs.md`/`testes.md`. `conformidade-rules.sh` exit 0 |
| QA-04 ADR-01 com custo velho | Minor · 1 | **fechado** | `02:61-65` traz o custo medido (4 na montagem, 3 por render, zero desligada), com marca de alteração |
| QA-05 aba *Tabelas* sem par escuro | Minor · 1 | **fechado** | `art/admin-configuracoes-tabelas-escuro.png` e o thumb publicados; conferi a imagem e ela está legível. Docs pt/en com a tabela claro/escuro. O CT-51 passou a cobrir a aba. `PacotesRodada3Test` + `KitArteTest` 69/69 |
| QA-06 rodapé escuro sem CT-B | Minor · 3 | **fechado** | CT-B02 em `tests/Browser/CarregamentoContinuoTest.php`, com oráculo de luminância (texto > 120, fundo < 80) que cai no tema claro. Arquivo 2/2, 18 asserções. `ids-ct.sh` exit 0 |
| QA-07 CT-28 sem afirmar "não cria" | Minor · 3 | **fechado** | O teste compara `TableView::count()` antes e depois e verifica que não existe "Roubada" nem "Fantasma". `--filter=CT-28` 7/7. A linha do K1 em `:374` (`assertModelExists`) continua, mas agora acompanhada do oráculo de contagem: rejeitada |
| QA-08 N+1 por linha em `ListUsers` | Minor · 2 | **débito aceito** | Já existia na `main`, fora do diff, e está documentado nas docs como consequência do `LIMIT` crescente. A correção fica no roadmap |
| QA-09 *Impacto* sem o bump do Filament | Minor · 1 | **fechado** | Bullet novo no `01` com os impactos medidos (P-12) |
| QA-10 termos fora do glossário | Minor · 1 | **fechado** | `wikis/glossario.md` +5 linhas (visão salva, visão padrão, visão compartilhada, carregamento contínuo, bloco), cada uma com "Não confundir com" coerente com P-07…P-16 |
| QA-11 condição da visão padrão nas docs | Cosmético · 1 | **fechado** | Docs pt e en completas: "agrupamento ou aba" / "grouping or tab" |

### Achados novos ou reabertos

Nenhum. A busca de regressão no que mudou depois do ciclo 1:

- **Scripts**: `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `conformidade-rules.sh {wiki} main` e `ids-ct.sh` (10 arquivos da feature, incluindo `Browser/CarregamentoContinuoTest.php`) saem todos com exit 0. `dark-mode.sh` sobre `app`/`resources` alterados sai com exit 0.
- **K1** (`k1-oraculo-fraco.sh` sobre os testes alterados): exit 1. As candidatas são as capturas de arte, incluindo os dois cenários novos da aba; foram rejeitadas porque o oráculo dessas capturas é o CT-51. A linha restante é CT-28 `:374`, também rejeitada (ver QA-07).
- **Testes rodados**: `SiteDeDocumentacaoTest` + `CitacoesDeCodigoTest` + `DiagramasDaArquiteturaTest` 599/599, o que cobre o badge 2.179 e as citações deslocadas, como `app/Console/Commands/KitArte.php:$label:384`. Também `Browser/CarregamentoContinuoTest` 2/2, `PacotesRodada3Test` + `KitArteTest` 69/69 e CT-28 7/7.

### Débitos Aceitos — Ciclo 2

- QA-08 (Minor): N+1 de papéis por linha em `ListUsers`, ampliado pelo carregamento contínuo. Já existia na `main`; vai para o roadmap.
- K2: score de mutação completo das 5 classes a medir em Linux/CI. No Windows, o filtro de mais de 8 KB é truncado e o plugin conta "No tests executed!" como morto.
- Q11 (P-07): visão compartilhada de autor inativo ou sem vínculo continua visível (comportamento do pacote). A pergunta está aberta ao solicitante.

### Não Verificado — Ciclo 2

- K2 — motivo: prova indispensável pendente. Nenhuma rodada de `--mutate` neste host é certificável. A amostragem manual alegada no `03` não foi reproduzida por este gate, que não pode aplicar mutante na árvore.
- H, teclado e foco — motivo: não exercitados. Só rodou o axe, no ciclo 1.
- Suíte de navegador fora de `CarregamentoContinuoTest` e dos três ancestrais do ciclo 1 — motivo: exige `npm run build` (continua como no ciclo 1).
