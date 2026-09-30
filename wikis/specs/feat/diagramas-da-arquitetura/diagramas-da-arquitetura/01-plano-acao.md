# Plano de Ação — Diagramas da arquitetura do kit no README e no site

> Requisito: `00-requisito.md`. Decisões: `02-decisoes-arquiteturais.md`.
>
> **Reconciliado com o código no step 10 da feature-wiki 4.0.0 (2026-09-29).** Onde a implementação
> divergiu do plano, o texto foi corrigido aqui e marcado *(alterado em 2026-09-29: …)*; o `03` só
> aponta. Os passos 22 a 25 são novos e registram o que as rodadas da revisão do diff e os Adendos 3
> a 5 acrescentaram. **Numeração dos steps**: a wiki nasceu na 3.x; "step 6.5" é o step 9 da 4.0.0
> (revisão do diff), "step 7" é o 10 (reconciliação), "step 8" é o 11 (quality gate) e "step 9" é o
> 12 (candidatos a rule) — tabela no README da feature-wiki, *Numeração dos steps — 3.x → 4.0.0*. A
> numeração antiga fica onde foi escrita.
>
> **Reconciliado de novo no ciclo 2 do quality gate (2026-09-29)** *(alterado em 2026-09-29: o ciclo 1 do
> gate reprovou com 14 achados, `06-relatorio-qa.md`, e o Adendo 6 decidiu corrigir todos)*. Os passos 5, 14,
> 17, 18 e 23 diziam que as guardas dos extras e o teste do CT-105 não existiam, e existem; cada um ganhou a
> marca desta passada. O passo 26 é novo e registra o que o ciclo 2 mudou no código (QA-01..QA-14).

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: n/a
- **Motivo**: n/a (primeira entrega deste assunto)
- **Toca infra compartilhada?**: **sim** — README (`README.md`/`README.en.md`), `docs/pt/**` e
  `docs/en/**`, `site/` (dependência nova, config, script de acessibilidade), `app/Console/Commands/KitArte.php`
  (pipeline de captura de arte, usado por qualquer feature futura que precise de GIF/PNG na
  galeria), `app/Support/CustomizadorDaInstalacao.php` (resumo do `kit:install`, RQ-28),
  `tests/Kit/MysqlNoDockerTest.php` e `tests/Kit/DuasRotasDeEntregaTest.php` (ganham `it()` novos
  ao lado dos ajudantes que já têm — `blocoDoServico`/`FORA_DA_ENTREGA_POR_DECISAO` — nenhum dos
  dois sobe para `tests/Pest.php`; passo 14).
  *(alterado em 2026-09-29: nenhum dos dois ganhou `it()` — as guardas do DG-17, DG-18 e DG-19
  moram em `tests/Kit/DiagramasDaArquiteturaTest.php`, com os IDs do `04` (CT-31, CT-33, CT-72,
  CT-75, CT-84, CT-93, CT-43, CT-76) —, e o `blocoDoServico` **subiu** para
  `tests/Pest.php:blocoDoServico:1874`, porque ganhou um segundo consumidor. A infra compartilhada
  tocada também inclui `app/Console/Commands/KitInstall.php`, `app/Support/SenhaDoAdministrador.php`
  e `app/Support/SubstituicaoEmArquivo.php` (as correções do instalador das rodadas da revisão do
  diff, passos 1 e 22), os extratores Mermaid de `tests/Pest.php` (passo 14) e
  `.github/workflows/ci.yml` (job `site`, passo 23))*

  Isso **força regressão obrigatória** contra os testes-guarda que já protegem essas superfícies,
  mesmo a feature sendo "nova" — regressão: ver Impacto em Features Existentes.

## Cobertura do Requisito

*(alterado em 2026-09-29: as faixas "4 a 13" viraram listas explícitas e os IDs de diagrama saíram da
coluna de passos para a Observação — o `rastreabilidade.sh` lê todo número da coluna como passo, e
"5 a 10" deixava os passos 6 a 9 sem `RQ`; entram as linhas RQ-31..RQ-44 dos Adendos 3 a 5. `—` com
Observação é cláusula de estudo ou de processo, que não vira passo nem CT — o julgamento é do quality
gate, dimensão A)*

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Analisar por inteiro o diagrama do GitDiagram contra o que o kit implementa | — | Estudo **concluído** na pesquisa da sessão; resultado registrado em `02-decisoes-arquiteturais.md`, ADR-01 (auditoria célula a célula: 17/23 nós corretos, 26/27 arestas corretas, 7 arestas faltantes, 10 omissões estruturais) |
| RQ-02 | Levantar e registrar as funcionalidades do GitDiagram | — | Estudo **concluído**; resultado registrado em ADR-01 (rascunho de topologia, nunca fonte) |
| RQ-03 | Avaliar as duas saídas de export (imagem e Mermaid) como insumo | — | Estudo **concluído**; ADR-03 decide que nenhuma exportação (imagem OU Mermaid de terceiro) vira fonte — o Mermaid é reescrito pela sessão |
| RQ-04 | Viabilidade de levar o GitDiagram ao README, com decisão registrada | — | Resolvida em dois pedaços: o **diagrama** entra corrigido como DG-01 (passo 3); o **site do GitDiagram** entra só como crédito na página nova (passo 4), nunca no README — ADR-01, ADR-11 |
| RQ-05 | Viabilidade de exibir diagramas em docs e site, com decisão registrada | 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18 | `docs/` (pt e en), renderizado por `astro-mermaid` — ADR-02 |
| RQ-06 | Os diagramas mostram como o kit funciona, não são decoração | 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18 | Toda linha "Fato do código" da seção de diagramas abaixo é uma checagem executável, não estética |
| RQ-07 | Diagramas de casos de uso são estudados | 4 | DG-02. Concretizado por RQ-21 |
| RQ-08 | Diagramas de sequência são estudados | 5, 6, 7, 8, 11, 15, 17 | DG-04 a DG-07, DG-11, DG-15 e a sequência do DG-20 (o DG-16 é `flowchart`). Concretizado por RQ-22/23/24 |
| RQ-09 | Outras representações gráficas são estudadas | 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18 | Concretizado por RQ-21/23/24/25 (`flowchart`, `stateDiagram-v2`, `erDiagram`) |
| RQ-10 | Todo diagrama publicado reflete o que o kit já implementa, com guarda que vale depois da publicação | 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18, 14 | Conteúdo nos passos 3 a 13 e 15 a 18; a guarda no 14. Nenhum diagrama descreve funcionalidade planejada; o teste-guarda (passo 14) é o mecanismo que torna isso válido também DEPOIS de publicado |
| RQ-11 | Diagramas disponíveis no README | 3 | DG-01 + link, nos dois idiomas |
| RQ-12 | Diagramas disponíveis no site do kit | 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18 | Página nova (passo 4) + diagramas nas páginas existentes (passos 5 a 13 e 15 a 18) |
| RQ-13 | O repositório `ahmedkhaleel2004/gitdiagram` é a fonte do estudo do RQ-02 | — | Usado na pesquisa (clone raso, commit `abe0620f`); registrado em ADR-01 |
| RQ-14 | Avaliar o site indicado como gerador de vídeo para os diagramas | — | **Não se aplica** — `sent.dm` é API de mensagens (SMS/WhatsApp/RCS), não gera vídeo; quem gera vídeo é o próprio GitDiagram (`/video`), recurso não pedido no Adendo 2. Decisão registrada em `02-decisoes-arquiteturais.md`, ADR-12 |
| RQ-15 | Avaliar o mesmo site como funcionalidade do próprio kit | — | **Não se aplica**, mesmo motivo — ADR-12 |
| RQ-16 | Avaliar gerar GIFs/vídeos de partes do sistema | 19, 20, 21 | Concretizado por RQ-27: `kit:arte` generalizado, vídeo fora (CT-21) |
| RQ-17 | Vídeos/imagens tornam o kit mais visível a quem for usá-lo | 19, 20, 21 | Os 4 clipes novos + o `install.gif` corrigido |
| RQ-18 | `astro-mermaid` + `mermaid` 11.17.2 em `site/package.json`, aprovação explícita | 2 | `site/package.json` (não a raiz) — ADR-02 |
| RQ-19 | Fonte de todo diagrama é Mermaid em texto, versionado | 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18 | ADR-03 |
| RQ-20 | README ganha 1 diagrama de arquitetura + link para a página de diagramas | 3 | DG-01 — ADR-07 |
| RQ-21 | Diagramas da visão geral: arquitetura, casos de uso por papel, regra de acesso ao painel | 3, 4, 5 | DG-01, DG-02, DG-03 |
| RQ-22 | Diagramas de autenticação: login por senha+2FA, login unificado, retorno social, convite, estados de conta/convite | 5, 6, 7, 8, 9, 10 | DG-04 (passo 5), DG-05 (6), DG-06 (7), DG-07 (8), DG-09 (9), DG-08 (10) |
| RQ-23 | Diagramas de IA/infra/dados: assistente, mapa `/infra`, ER, precedência de configuração | 4, 11, 12, 13 | DG-13 (passo 4), DG-11 (11), DG-12 (12), DG-14 (13) |
| RQ-24 | Diagramas do ciclo do kit: instalação, `kit:update` com as duas rotas, containers por profile | 15, 16 | DG-15, DG-16, DG-17, DG-18 |
| RQ-25 | Diagramas pertinentes adicionais, justificados no `02` | 5, 17, 18 | DG-10 (passo 5), DG-20 (17), DG-19 (18) — ADR-08 |
| RQ-26 | Todo diagrama em pt e en, com guarda de teste | 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18, 14 | Conteúdo nos passos 3 a 13 e 15 a 18; a guarda no 14. ADR-04 (IDs), ADR-06 (guarda por diagrama) |
| RQ-27 | `kit:arte` monta GIFs de mais partes do sistema, sem dependência nova; vídeo fora | 19, 20 | ADR-09 |
| RQ-28 | README, resumo do `kit:install` e `art/install.gif` deixam de afirmar `password` | 1, 21, 22 | ADR-09, ADR-10. *(alterado em 2026-09-29: o passo 22 registra as correções do instalador que a revisão do diff trouxe — o resumo e o banner nos casos em que nada é gerado)* *(alterado em 2026-09-30: **aberta em parte** — Q?10 e Q?11, P-46 e P-47 do `00`; a direção não é implementada, e os invariantes são CT-134 e CT-136)* |
| RQ-29 | Outras afirmações falsas corrigidas (passkeys, `composer dev`, `schedule:work`, docblocks) | 1 | ADR-10 |
| RQ-30 | Página de diagramas credita o GitDiagram, sem embutir | 4 | ADR-11 |
| RQ-31 | Uma 3ª rodada da revisão do diff é autorizada, acima do teto da skill | — | Cláusula de **processo** (Adendo 3): não é comportamento do kit. Cumprida pela rodada 3 da revisão do diff (step 6.5 da 3.x, step 9 da 4.0.0) — o que ela mudou no código está no passo 22; os achados RD3-01..RD3-12 da revisão cega do delta, no `03`, `## Revisão do Diff (step 9)` |
| RQ-32 | Todo diagrama renderiza no site e no GitHub, sem erro de sintaxe | 22 | O DG-11 não renderizava: o `;` da nota era separador de statement. Prova no site: CT-B01 (`05`); no GitHub, conferência manual no PR (L-03 do `04`) |
| RQ-33 | Contraste WCAG AA nos dois temas, ajustado na configuração do site, nunca com cor fixa no bloco | 22 | Rótulo de aresta no tema escuro, em `site/src/styles/kit.css` — desvio do texto da opção ("na config do astro-mermaid"): o `astro-mermaid` não tem configuração por tema (ADR-02). CT-B01; CT-34, CT-35, CT-95 |
| RQ-34 | As guardas leem as arestas de forma normalizada, nos dois idiomas | 22, 24 | Extrator de arestas em `tests/Pest.php` (rodada 3) e as formas de seta que ele ainda perdia (rodada 4, RD3-05) |
| RQ-35 | Os 21 cenários que só conferiam a existência do bloco passam a conferir o conteúdo | 22, 24 | Rodada 3 (RD2-16) e os fatos de DG-12/13/14/16/17 com os IDs reais, em pt e en (rodada 4, RD3-06) |
| RQ-36 | O CI de pull request constrói e confere o site quando o PR toca `docs/` ou `site/` | 23 | Job `site` no `.github/workflows/ci.yml`. ~~**Sem CT** — lacuna declarada no `04` e dívida no `03`~~ CT-105 (R47 do `04`) *(alterado em 2026-09-29: o cenário foi derivado no step 10, depois da reconferência mecânica; ~~o teste ainda não existe — dívida DV-09 no `03`~~ o teste existe, `tests/Kit/DiagramasDaArquiteturaTest.php:'[CT-105]':6223`, e a DV-09 está fechada)* |
| RQ-37 | Uma 4ª rodada fecha RD3-01..RD3-12, Major e Minor | — | Cláusula de **processo** (Adendo 4). Cumprida pela rodada 4 — o código dela está no passo 24; a situação de cada RD3 está no `03`, `## Revisão do Diff (step 9)` |
| RQ-38 | A 4ª rodada tem revisão cega só do delta novo | — | Cláusula de **processo**: `fw-revisor-diff` sobre o delta, despacho #44 do `03` |
| RQ-39 | É a última rodada: o que a revisão da 4ª achar vira dívida declarada no `03` | — | Cláusula de **processo**, com as exceções do Adendo 5 (RQ-41, RQ-42). As dívidas estão no `03`, `## Dívidas declaradas` |
| RQ-40 | Depois da 4ª rodada, seguem a reconciliação, o quality gate e o PR | — | Cláusula de **processo**: este step 10, depois o 11 e o PR |
| RQ-41 | As duas regressões da 4ª rodada são corrigidas | 25 | RD4-01 (`-f gif`, com um ffmpeg de teste que escolhe o formato como o real) e RD4-03 (o fato do DG-03 lê o rótulo, em pt e en) |
| RQ-42 | Os dois consertos mecânicos entram: Pint verde e citações corretas | — | Conserto **mecânico** (RD4-04, RD4-06), sem comportamento do kit a derivar em CT: feito no passo 25 e provado por comando — `vendor/bin/pint --test` e `tests/Kit/CitacoesDeCodigoTest.php` verdes (`03`, `## Verificação Final`) |
| RQ-43 | Cada correção é provada vermelha antes; sem nova revisão cega | — | Cláusula de **processo**: a prova de cada correção está no `03`, `## Revisão do Diff (step 9)` |
| RQ-44 | RD4-02, 05, 07, 08, 09 e 10 viram dívida declarada no `03` e no PR, com RD4-02 e RD4-10 em destaque | — | Cláusula de **processo**: `03`, `## Dívidas declaradas`; no PR, no step 11 |
| RQ-45 | Todos os achados do ciclo 1 do quality gate (QA-01..QA-14) são corrigidos, com o teste primeiro onde o destino é teste; o QA-12 vira nota para a skill | — | Cláusula de **processo** (Adendo 6) *(alterado em 2026-09-29: linha nova, do ciclo 2 do gate)*: o código que ela trouxe está no passo 26; o destino de cada achado, no `03`, `## Quality Gate`; o "teste primeiro" são os cenários que nasceram vermelhos no `04` (CT-126, CT-129, CT-131, CT-132) e no `05` (CT-B05, CT-B06) |
| RQ-46 | O quality gate roda o ciclo 2, cego como no 1, antes do PR | — | Cláusula de **processo** (Adendo 6): é o step 11, depois deste passo 26; o veredito vai para o `03`, `## Quality Gate` |
| RQ-47 | No site, o diagrama nunca encolhe abaixo de um piso de fonte efetiva (cerca de 12 px); o que não cabe rola na horizontal dentro do próprio bloco | 26 | `site/src/styles/kit.css` — ADR-02, *Alterações depois da implementação*; CT-B05 e CT-B06 do `05` |
| RQ-48 | A legibilidade se resolve no CSS do site, sem mudar bloco nem o que o GitHub mostra | 26 | Só o `site/src/styles/kit.css` mudou para isto; nenhum bloco Mermaid foi tocado pela correção |
| RQ-49 | Um CT-B mede o piso de fonte antes da correção | 26 | CT-B05, escrito no `site/verifica-acessibilidade.mjs` antes do CSS (lote T3 do ciclo 2, no `03`) |
| RQ-50 | A gravação no `.env` troca toda linha ATIVA da chave e nenhuma comentada: qualquer leitor fica com o valor gravado | 27 | `app/Support/SubstituicaoEmArquivo.php:definirLinhaNoEnv` (todo chamador); CT-118, CT-137, CT-138, CT-150; aberta em parte (Q?9 → P-45: sem linha ativa, descomenta no lugar; CT-139 é o invariante) *(alterado em 2026-09-30: linha nova, Adendo 7)* *(alterado em 2026-09-30: a Q?9 fechou pelo Adendo 8 — RQ-53, descomentar no lugar; RQ-50 deixa de estar aberta)* |
| RQ-51 | Valor com quebra de linha vai ao `.env` com a quebra trocada por espaço, e o arquivo não ganha chave nova | 27 | `app/Support/SubstituicaoEmArquivo.php:escaparValorDeEnv` (LF, CRLF e CR → um espaço); CT-117, linhas LF/CRLF/CR *(alterado em 2026-09-30: linha nova, Adendo 7)* |
| RQ-52 | Quando a montagem do GIF dá certo e a publicação em `art/` falha, a saída do `kit:arte` nomeia a publicação, e não a montagem | 27 | `app/Console/Commands/KitArte.php` ("Não consegui publicar", RD2-04/RD3-09); CT-128, CT-147 *(alterado em 2026-09-30: linha nova, Adendo 7)* |
| RQ-53 | Sem nenhuma linha ativa da chave, a gravação descomenta a primeira linha comentada no lugar | 27 | `app/Support/SubstituicaoEmArquivo.php:definirLinhaNoEnv` (o ramo `$comentada`); CT-139 (linhas "só comentada") *(alterado em 2026-09-30: linha nova, Adendo 8)* |

## Objetivo

Publicar, dentro do README e do site de documentação do kit, um conjunto de diagramas Mermaid que
mostrem graficamente como o Starter Kit Easy funciona hoje — arquitetura, acesso por papel,
autenticação, IA, infraestrutura, modelo de dados e o ciclo de vida do próprio kit (instalação,
atualização, multi-tenancy) — cada um guardado por um teste automatizado que falha quando o código
deixar de bater com o que o diagrama descreve. Ao lado disso, a entrega corrige um pequeno conjunto
de afirmações do README e de comentários do código que já estavam desatualizadas antes desta
feature (a senha `password`, `passkeys`, o que o `composer dev` sobe, um docblock com citação
errada) e estende o pipeline de captura de tela que o kit já tem (`kit:arte`) para produzir GIFs
novos de partes do sistema, incluindo um `install.gif` refeito a partir de uma execução real.

## Contexto

O mantenedor rodou o `gitdiagram.com` contra este repositório e pediu para avaliar se aquele
diagrama (ou a ideia por trás dele) merece entrar no README e no site. A auditoria da sessão achou
o diagrama gerado **estruturalmente enganoso** — não por invenção, mas por apresentar como
sempre-presente o que é opcional (organizações, tenancy) e por **omitir a regra central do kit**
(acesso por papel). Ele também não é reproduzível: o serviço externo pode trocar o conteúdo do link
a qualquer momento, sem PR. A decisão (ADR-01) foi tratar o GitDiagram como rascunho de ideia, não
como fonte, e escrever os diagramas do zero, com guarda de teste — a mesma disciplina que o kit já
aplica aos números do README (`[CT-25]`, `[CT-13]`, etc.).

O site (Starlight, em `site/`) hoje não tem nenhum mecanismo para renderizar Mermaid — nem
dependência instalada, nem plugin. O README está perto do teto de linhas que `[CT-13]` impõe. Os
dois fatos moldam a entrega: 1 diagrama compacto no README, o resto em `docs/`, com uma dependência
nova e isolada (`site/package.json`) para o site renderizar o Mermaid.

Paralelamente, a mesma pesquisa achou (e o mantenedor já aprovou corrigir) um pequeno conjunto de
afirmações do kit que o código contradiz — a mais visível sendo que o README e o `install.gif`
ainda dizem que a senha do administrador é `password`, quando desde uma correção anterior (#100) a
instalação gera uma senha aleatória.

## Análise dos Arquivos Existentes

### `README.md` / `README.en.md`
Hoje com 445/446 linhas (teto de `[CT-13]`: 756/767). Ganham um bloco Mermaid (DG-01) + um link
para a página nova do site, e três correções de texto (senha, passkeys, `composer dev`).
*(alterado em 2026-09-29: depois da entrega, 490/491 linhas — `wc -l README.md README.en.md` —, com
folga para o teto do `[CT-13]`)*

### `docs/pt/**` e `docs/en/**`
Doze páginas existentes recebem um ou mais diagramas cada — uma por passo, nos passos 5 a 13 e 15
a 18 (os passos 8 e 9 dividem a mesma página). Uma página
nova é criada (`referencia/arquitetura-em-diagramas.md`), dentro da seção **existente**
`referencia/` (a seção tem hoje 5 páginas, `order:` de 0 a 4 — a nova entra com `order: 5`; criar
seção nova reprovaria `[CT-14]`/`[CT-31]`/`[CT-37]` e exigiria editar `site/converter.mjs:SECOES`,
o que está fora do escopo aprovado). Todas as páginas tocadas mudam **igualmente** em pt e en, pelo
mesmo motivo que `[CT-04]`/`[CT-05]`/`[CT-19]`/`[CT-34]` existem.

**Divergência achada entre o catálogo (Adendo 2) e o conteúdo real**: DG-11 vai em
`docs/{pt,en}/operacao/roteiro-de-features.md`, seção `## IA` (existente), e não em
`agentes-de-ia.md` — essa página é sobre agentes de codificação, não sobre o assistente do kit
(decisão 1, ver Decisões da sessão).

### `site/package.json`, `site/astro.config.mjs`, `site/verifica-acessibilidade.mjs`
Ganham a dependência nova (ADR-02), a integração no array `integrations` e os dois ajustes de
acessibilidade (espera pelo SVG, página nova na `AMOSTRA_CLARA`).

### `app/Console/Commands/KitArte.php`
`QUADROS_DO_GIF` (lista) vira `CLIPES` (mapa `clipe => quadros`); `montarGif()` passa a aceitar
mais de um clipe, sempre a partir de `tests/Browser/Screenshots` (uma origem só — ver passo 19).
Os quadros dos clipes novos **não** entram em `IMAGENS`: o padrão existente é o oposto — quadro de
GIF não vira PNG em `art/` (antes da entrega: o docblock de `QUADROS_DO_GIF`, na linha 41 de
`app/Console/Commands/KitArte.php`, e o `continue` da linha 134, que pulava o quadro antes da
checagem de `IMAGENS`). O par captura ↔ declaração de `.ai/rules/testes-browser.md` fica satisfeito
pelo `CLIPES`, como já é hoje pelo `QUADROS_DO_GIF`. Os dois órfãos existentes
(`admin-user-ficha-header`, `admin-organizacao-header`) ficam como estão — ver passo 19.
*(alterado em 2026-09-29: `QUADROS_DO_GIF` não sumiu — continua sendo a lista do clipe
`fluxo-import-export` (`app/Console/Commands/KitArte.php:QUADROS_DO_GIF:45`), que o `CLIPES` reaproveita
(`app/Console/Commands/KitArte.php:CLIPES:70`), porque os testes o leem por Reflection pelo nome; hoje o
`publicar()` confere `IMAGENS` primeiro (`app/Console/Commands/KitArte.php:IMAGENS:187`) e só depois
pula o quadro de clipe (`app/Console/Commands/KitArte.php:$quadrosDeClipe:206`))*

### `tests/BrowserTenancy/CapturaDeArteTest.php`
Ganha os cenários que capturam os quadros dos clipes `busca-spotlight` e `login-unificado`, e os
quadros do roteiro do `install.gif`.

### `app/Support/CustomizadorDaInstalacao.php`
O resumo do `kit:install` (antes da entrega: `app/Support/CustomizadorDaInstalacao.php`, linha 295)
imprime `password (padrão do kit)` quando a resposta da senha vem vazia — que é justamente o caso em
que a instalação **gera** a senha (D4, RQ-28). Muda o texto dessa linha, nada mais.
*(alterado em 2026-09-29: mudou mais que a linha — a revisão do diff achou os casos em que o novo
texto também mentia. Hoje a linha tem três valores, pela regra de `SenhaDoAdministrador`
(`app/Support/CustomizadorDaInstalacao.php:$senhaDigitadaUtilizavel:331`: digitada e utilizável, já
definida em `KIT_ADMIN_PASSWORD`, ou `RESUMO_SENHA_GERADA`), e o `KitInstall` a corrige com o desfecho
real da semeadura (`app/Console/Commands/KitInstall.php:corrigirResumoDaSenha:612`); passos 1, 22 e 24)*

## Autorização

**N/A.** Esta feature não introduz nem modifica nenhuma Policy, Gate, middleware ou guard — ela
escreve documentação, configura uma dependência de build do site (puramente estático, sem
autenticação própria) e estende um Artisan command de bastidor sem UI. Nenhuma tela nova é
alcançável pelo navegador; nenhum dado sensível novo é exposto (a senha do `install.gif` é
mascarada — ver passo 21).

## Rotas

**Nenhuma rota Laravel nova.**

## Superfície de UI

**Sem superfície de UI nova no app.** O que existe:

| Superfície | Tipo | Muda o quê |
|---|---|---|
| README (pt/en) | Markdown estático | +1 diagrama, +1 link, 3 correções de texto |
| Site (Starlight) | Markdown estático + 1 página nova | +20 diagramas distribuídos, +1 página, +1 dependência de renderização client-side |
| Telas do app (`/admin`, `/app`, `/infra`) | **Nenhuma mudança de UI** — só **fotografadas** pelo pipeline `kit:arte` para produzir GIFs (busca ⌘K, escolha de painel do login unificado) | Zero: são as mesmas telas que já existem, capturadas com Playwright, sem alteração de Blade/Livewire/Filament |
| Roteiro de instalação (terminal) | Renderização estática de uma transcrição real de `kit:install`, para o `install.gif` | Nova view Blade **de fixture**, sem rota pública — motivo do caminho (`tests/Browser/Fixtures/`) no passo 21 |

**Gate de CT-B**: N/A, o comportamento já é provado em `RoteiroDoKitTest` (F-45) e
`LoginUnificadoTest` (CT-B01).
*(alterado em 2026-09-29: a derivação do `04` refez o gate e ele **passa** — a renderização do site é
JavaScript no cliente, que só o navegador prova. Há `05-casos-de-teste-browser.md` com três CT-B:
CT-B01 e CT-B02 no conferidor Node + Playwright de `site/` (`site/verifica-acessibilidade.mjs`) e
CT-B03 em `tests/BrowserTenancy/CapturaDeArteTest.php`. E a fixture do terminal não tem rota pública,
mas é servida por uma rota registrada só dentro do teste de captura, depois de renderizada com
`view()->file()` (`tests/BrowserTenancy/CapturaDeArteTest.php:Route:814`))*

## Variáveis de Ambiente

**Nenhuma.** Esta feature não introduz nenhuma chave `.env` nova. As chaves citadas nos diagramas
(`KIT_TENANCY`, `KIT_LOGIN_UNIFICADO`, `KIT_DEMO`, `KIT_SOCIALITE_VINCULO_CONFIRMAR`, etc.) já
existem e são apenas **referenciadas** como fato do código, nunca criadas ou alteradas.

## Eventos / Listeners / Observers

**Nenhum novo.** `AgentPrompted`/`AgentStreamed` e o listener `RegistrarAiRun` (citados no DG-11)
já existem e não mudam.

## Jobs / Queues

**Nenhum novo.** O agendamento `kit:convites-lembrar` (citado no DG-07) já existe
(`routes/console.php:'kit:convites-lembrar':40`) e não muda.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | Não se aplica a request Laravel — README e `docs/` são arquivos estáticos; o site é pré-renderizado no build (`npm run build`), sem custo de request por página além de servir HTML/JS estáticos |
| O que é adiado, e por qual gatilho? | Render do SVG no navegador, ver passo 2 |
| O que é memoizado por request? | N/A |
| O que é cacheado entre requests? | O GitHub Pages serve os arquivos estáticos do build; nenhum cache de aplicação novo |
| Custo do caminho principal | Nenhuma query nova de banco; o custo é só de build do site (tempo de `npm run build` cresce com o JS do `mermaid`, não medido nesta entrega — ver Riscos) |

## Impacto em Features Existentes

| Teste-guarda (matriz de `site-e-readme.md`) | Muda como |
|---|---|
| `[CT-01]`/`[CT-03]` (todo título do baseline aparece, nada migrado sobra no README) | Não afetado pelos títulos NOVOS (a página `arquitetura-em-diagramas` e as novas subseções H2/H3 não fazem parte do baseline congelado); confirmar que nenhum título novo colide com um título do baseline |
| `[CT-04]`/`[CT-34]` (paridade de conjunto e de caminho pt/en) | A página nova existe nos dois idiomas, no mesmo caminho |
| `[CT-05]` (pt não é resumo de en) | Cada bloco Mermaid entra IGUAL (mesmos nós) nas duas páginas — garante paridade de tamanho automaticamente |
| `[CT-13]` (teto de linhas do README, diferença pt×en ≤ 5%) | +1 diagrama nos dois idiomas (DG-01: 19 nós, os `subgraph` das camadas, arestas e `accTitle`/`accDescr`, medido com `wc -l` no passo 3) — folga de ~310 linhas hoje (445/446 de 756/767); o mesmo bloco nos dois idiomas não mexe na diferença pt×en |
| `[CT-14]`/`[CT-22]` (todo link do README/site resolve) | O link novo do README para a página de diagramas precisa resolver; os links internos da página nova (índice para cada diagrama) idem |
| `[CT-18]` (nenhum `{{`/`{%` solto) | Nenhum diagrama usa nó hexágono `{{ }}` — regra dura do catálogo |
| `[CT-19]` (idioma correto por árvore) | Os rótulos dos diagramas entram na regex de idioma — cuidado extra ao traduzir |
| `[CT-20]` (toda página na navegação do idioma) | A página nova precisa aparecer em `site/sidebar.json`, regenerado por `node converter.mjs` |
| `[CT-21]` (nada de vídeo/binário/caminho relativo em `docs/`) | Todos os blocos são texto Mermaid — nenhuma imagem nova em `docs/` |
| `[CT-25]` (números do README sincronizados com a árvore) | A contagem de `00-requisito.md` sobe (esta própria wiki); derivar por comando, nunca escrever à mão |
| `[CT-29]`/`[CT-30]` (formato Starlight, sem H1 duplicado) | A página nova segue o formato: `title`/`description`/`sidebar.order`, corpo sem H1 |
| `[CT-31]` (índice de seção com rótulo "Visão geral"/"Overview") | Não afetado — a página nova é uma FOLHA de `referencia/`, não um índice de seção |
| `[CT-34]`/`[CT-36]`/`[CT-37]`/`[CT-38]`/`[CT-41]` (espelho pt/en, redirects, ordem) | A página nova precisa de `order:`, de stub em `site/public/{pt,en}/...` e de entrada em `redirects.json` — gerados rodando `node converter.mjs` dentro de `site/` |
| `[CT-44]`/`[CT-45]` (título sem marcação, link relativo) | O índice de diagramas na página nova usa links relativos, sem marcação Markdown no `title:` |
| `tests/Kit/CitacoesDeCodigoTest.php` | Todas as citações novas (nos diagramas e nas correções de docblock) precisam apontar para o símbolo certo |
| `tests/Kit/DuasRotasDeEntregaTest.php` | Nenhum diretório de topo novo é criado — a página fica dentro de `docs/referencia/`, já coberto |
| `tests/Kit/MysqlNoDockerTest.php` | ganha `it('[DG-18]')` e `it('[DG-19]')` (passo 14); `blocoDoServico` sai de closure em `$this` para função de topo do próprio arquivo (`blocoDoServicoNoCompose`), sem subir para `tests/Pest.php` |
| `tests/Kit/HelpersDeTesteTest.php` | se um helper novo for compartilhado entre `DiagramasDaArquiteturaTest.php` e outro arquivo, precisa estar em `tests/Pest.php`, nunca duplicado |
| `tests/Browser/HubDeCardsTest.php` | `KitArte.php` muda de lista para mapa (`CLIPES`); os testes que alimentam `tests/Browser/Screenshots` continuam produzindo exatamente os nomes que `KitArte` espera |
| `site/verifica-links.mjs`, `site/verifica-acessibilidade.mjs` (fora do Pest, gate de CI) | a página nova e a dependência nova (`astro-mermaid`) só se provam corretas depois de `npm run build` — estes dois scripts são a regressão do lado do site |
| `.ai/rules/testes-browser.md` (par captura ↔ `KitArte::IMAGENS`) | Garante que os clipes novos também tenham o par captura ↔ declaração |

## Rollback

- **Sem migration** — nenhuma tabela ou coluna é criada ou alterada.
- **Reversão de conteúdo**: `git revert` do(s) commit(s) desta feature restaura README, `docs/`,
  `site/` (incluindo `package.json`/`package-lock.json`, `sidebar.json`, `redirects.json` e os
  stubs), `KitArte.php` e os `art/*.gif` novos ao estado anterior — tudo versionado, nada gerado em
  runtime.

## Dependências

- **NPM, em `site/package.json`** (nunca na raiz — `[CT-12]` trava isso):
  - `astro-mermaid` `^2.1.0`
  - `mermaid` `11.17.2` (fixado, mesma versão que o GitHub usa em produção)

  **Aprovação**: já concedida explicitamente no Adendo 2 do `00-requisito.md` (RQ-18). Nenhuma
  outra dependência (Composer ou NPM) é adicionada nesta feature, em nenhum `package.json` ou
  `composer.json`.

## Riscos

| Risco | Mitigação |
|---|---|
| O peso do JS do `mermaid` no bundle do site não foi medido (o mantenedor do Starlight já registrou publicamente que Mermaid é "grande e lento") | Medir com `npm run build` no passo 2 e registrar o tamanho do chunk na Verificação Final; se for proibitivo, é achado para a sessão decidir — não é motivo para reverter esta entrega sozinho, mas deve ser visível |
| `astro-mermaid` pode não funcionar corretamente com o loader `glob` que lê `docs/` de fora da raiz do projeto Astro (a mesma classe de problema que a ADR-02 da wiki `site-starlight` já documentou para o `autogenerate`) | Sentinela: ver passo 2 (Verificação) |
| O texto do `install.gif` novo, se digitado à mão em vez de transcrito de uma execução real, reintroduziria o mesmo tipo de defeito que está sendo corrigido (afirmação que não corresponde ao código) | O passo 21 exige transcrição de uma execução REAL de `kit:install` (mascarando a senha), nunca um roteiro inventado |
| O axe pode não auditar o SVG do Mermaid pela regra `svg-img-alt`: o seletor dela é `svg[role='graphics-document']`, de igualdade, e o Mermaid grava `graphics-document document` (não confirmado na pesquisa — `site-e-readme.md`, pendência 2) | A obrigação de `accTitle`/`accDescr` não depende do axe: a camada 1 da guarda (passo 14) a confere em todo bloco, no texto |
| **Dívida declarada**: `admin-user-ficha-header` e `admin-organizacao-header` continuam publicados sem uso (0 ocorrências em `docs/`/`README*`), violando o par de `.ai/rules/testes-browser.md` — nenhum RQ desta feature pede a correção | Fica para um fix próprio; o `kit:arte` os relata como ignorados (passo 19) |

*(alterado em 2026-09-29: desfecho de cada risco, medido na implementação)*

- **Peso do `mermaid`**: medido — cerca de 700 KB de JS a mais, carregado só nas páginas com
  diagrama (`92857dc`); o maior chunk do build é `site/dist/_astro/chunk-*.js` com 662.109 bytes
  (`ls -l site/dist/_astro/*.js`, 2026-09-29), e o Vite avisa de chunk acima de 500 kB. Visível, não
  proibitivo: nenhuma ação.
- **Loader `glob` fora da raiz do Astro**: não se confirmou — o build transforma os blocos de `docs/`
  (`[astro-mermaid] … transformed mermaid block in …/docs/pt/…` no log do `npm run build`) e o
  `site/dist/pt/referencia/arquitetura-em-diagramas/index.html` tem 4 `class="mermaid"`.
- **`install.gif`**: a transcrição é a saída real do `kit:install` de um `create-project` da v0.41.1,
  com a senha gerada mascarada (`3c79ff0`, `2ab9bfa`).
- **`svg-img-alt` do axe**: continua não confirmado se a regra chega a se aplicar; a camada 1 da guarda
  cobra `accTitle`/`accDescr` em todo bloco, como previsto.
- **Riscos que as rodadas da revisão do diff deixaram abertos**: as dívidas declaradas do `03`
  (`## Dívidas declaradas`), com RD4-02 e RD4-10 em destaque.

## Channel de Log da Feature

### Verificação de Channel Existente

`grep -rn "Log::channel(" config/logging.php app/` mostra os channels do kit: `ai`, `tenancy`,
`autenticacao`, `configuracoes` (`config/logging.php:'ai':114`, `:'tenancy':123`,
`:'autenticacao':132`, `:'configuracoes':153`) — todos ligados a lógica de **negócio** em runtime.

### Decisão

**Nenhum channel novo, e nenhum `Log::channel()` novo em nenhum passo desta feature.** Esta feature
não cria lógica de runtime de negócio — ela escreve Markdown, configura uma dependência de build
estático e estende um Artisan command de bastidor. O único "log" que a feature toca é a **saída de
console** de dois comandos que já existem e já usam esse padrão (`$this->components`), não `Log::`.

## Estrutura de Implementação

### 1. Correções de texto e docblocks (RQ-28, RQ-29)

> Skills: nenhuma (edição de texto)

Sem lógica nova — só correção de afirmações que o código já contradiz (ADR-10).

- **Path**: `README.md`, `README.en.md`
  - `README.md:password:97`/`README.en.md:password:97` (tabela de credenciais) e a linha 3 do
    passo a passo de instalação (`README.md:password:32`/`README.en.md:password:32`): trocar
    `password` pela indicação de que a senha é **gerada** (ex.: "gerada na instalação — impressa no
    terminal"), sem inventar um valor fixo.
  - `README.md:passkeys:201`/`README.en.md:passkeys:201`: remover "passkeys" da lista de recursos
    do Breezy (fica "perfil do usuário, avatar, 2FA"). *(alterado em 2026-09-29: a palavra ficou, dizendo o contrário — "perfil do usuário, avatar e 2FA (passkeys desligadas: o Breezy nasce com `$passkeys = false` e `enablePasskeys()` não é chamado em nenhum painel)", hoje na linha 246 dos dois READMEs; o mesmo em `pacotes-instalados.md`. O CT-42 recusa passkey afirmada como recurso e aceita a menção que diz que estão desligadas)*
  - `README.md:vite:365`/`README.en.md:vite:366`: trocar "servidor + fila + vite juntos"/"server +
    queue + vite together" por uma frase que inclua o Reverb (ex.: "servidor + fila + vite + Reverb").
- **Path**: `app/Support/CustomizadorDaInstalacao.php` (resumo do `kit:install`,
  antes da entrega na linha 295 de `app/Support/CustomizadorDaInstalacao.php`)
  - Trocar `'password (padrão do kit)'` (o ramo da resposta vazia, em que a instalação GERA a senha)
    por um texto que diga que a senha é gerada e aparece no banner final, sem valor nenhum.
  - Teste: um caso em `tests/Kit/CustomizadorDaInstalacaoTest.php`, no bloco `R9` (resumo e
    segredo, que já existe), com `senha` vazia: a linha `Senha do administrador` do resumo não
    contém `password` e diz que é gerada. É comportamento de texto guardado — sem o caso, o texto
    antigo volta num merge sem ninguém ver. *(alterado em 2026-09-29: a revisão do diff achou os casos em que o texto novo também mentia, e a correção cresceu além da linha — rodada 1 (RD-07): o `.env` que já tem `KIT_ADMIN_PASSWORD` utilizável; rodada 3 (passo 22): senha digitada não utilizável, `--no-seed` ou banco inacessível, e a barra invertida no `.env`; rodada 4 (passo 24): banner e resumo com a mesma instrução, o desfecho lido de `handle()` e a constante `RESUMO_SENHA_GERADA`. Os casos de teste passaram de um para três `it('[CT-41]')` e mais nove testes com o ID do achado — lista no `04`, "Testes nascidos na revisão do diff, sem CT")*
- **Ocorrências irmãs** das mesmas afirmações falsas, fora das linhas que a pesquisa listou (achadas
  por `grep -rn -i "passkey\|composer dev" README.md README.en.md docs wikis/*.md`) — mesma
  correção, na fonte:
  - "passkeys": `docs/pt/referencia/pacotes-instalados.md:passkeys:23` e
    `docs/en/referencia/pacotes-instalados.md:passkeys:23` (linha do `jeffgreco13/filament-breezy`
    — remover a palavra); `docs/pt/operacao/roteiro-de-features.md:Passkeys:32` e
    `docs/en/operacao/roteiro-de-features.md:Passkeys:30` (a linha `F-05` apresenta passkeys como
    feature entregue — reescrever para "desligado no kit: o Breezy nasce com `$passkeys = false` e
    `enablePasskeys()` não é chamado", **sem apagar o `F-05`**, porque os IDs `F-xx` são referência
    de teste, ex. `tests/Browser/RoteiroDoKitTest.php:'F-03':36`); `wikis/pacotes.md:passkeys:10`
    (tabela "já existe — não escreva de novo": o pacote traz passkeys, mas desligado — acrescentar
    "desligado; liga com `enablePasskeys()`", sem remover, porque a linha diz de onde vem, não o que
    está ligado).
  - "`composer dev` = servidor + fila + vite": `docs/pt/comecar/instalacao-avancada.md:vite:168` e
    `docs/en/comecar/instalacao-avancada.md:vite:166` (o mesmo comentário do README);
    `docs/pt/referencia/pacotes-instalados.md:concurrently:141` e
    `docs/en/referencia/pacotes-instalados.md:concurrently:140` (acrescentar o Reverb).
  - "`schedule:work` vem no `composer dev`" (D1): `docs/pt/operacao/roteiro-de-features.md:composer:150`
    e `docs/en/operacao/roteiro-de-features.md:composer:148` ("os três primeiros o `composer dev` já
    resolve" — dois dos três dependem do agendador, que o `composer dev` não sobe); e
    `wikis/arquitetura.md:composer:375` ("já incluso no `composer dev`", a mesma frase do
    `routes/console.php`). As `wikis/` ficam fora como **destino de diagrama** (00, RQ-05), não como
    fonte de afirmação falsa — mantidas aqui (decisão 8, ver Decisões da sessão).
- **Path**: `routes/console.php` (comentário nas linhas 19 a 22, `routes/console.php:composer:19`)
  - Reescrever o comentário para não afirmar que `schedule:work` "já incluso no `composer dev`" —
    ele não está: o `artisan dev` registra `serve`, `queue:listen`, `pail` (só com `pcntl`) e o
    `vite` (`vendor/laravel/framework/src/Illuminate/Foundation/DevCommands.php:registerDefaults:106`,
    linhas 112-121), e o Reverb acrescenta o `reverb:start` por conta própria
    (`vendor/laravel/reverb/src/Reverb.php:registerDevCommands:12`) — nenhum dos dois registra
    `schedule:work`.
- **Path**: `app/Providers/Filament/AppPanelProvider.php` (docblock na linha 67,
  `app/Providers/Filament/AppPanelProvider.php:canAccessPanel:67`)
  - Trocar "Acesso: qualquer usuário autenticado — ajuste em `User::canAccessPanel()`" por uma
    frase que descreva a regra real (papel com `roles.painel = 'app'`, ver `app/Models/User.php:canAccessPanel:156-219`).
- **Path**: `app/Providers/Filament/InfraPanelProvider.php` (docblock na linha 343,
  `app/Providers/Filament/InfraPanelProvider.php:'ver-logs':343`)
  - Corrigir a citação que o docblock trazia, "`KitServiceProvider.php`, linha 172" (essa linha é um
    comentário vazio, só `*` — confirmado com `sed -n '172p' app/Providers/KitServiceProvider.php`),
    para `app/Providers/KitServiceProvider.php:'ver-logs':429`, onde `Gate::define('ver-logs', ...)`
    de fato está.
- **Path**: `app/Providers/Filament/AdminPanelProvider.php` (docblock nas linhas 262-269,
  `app/Providers/Filament/AdminPanelProvider.php:launcher:268`)
  - Reescrever o docblock do `FilamentOnboardingPlugin` para não prometer consumo (`launcher`/
    `tours`) no painel de negócio — hoje só existe autoria no `/admin` (confirmado: `grep -n -i
    onboarding app/Providers/Filament/AppPanelProvider.php` não acha nada).
- **Path**: `app/Console/Commands/KitInstall.php` (docblock na linha 27,
  `app/Console/Commands/KitInstall.php:idempotentes:27`)
  - Reescrever "Nenhum passo aborta a instalação" para refletir que passos idempotentes não
    abortam, mas uma falha de infraestrutura (ex.: SQLite preso por outro processo, com `--force`)
    pode interromper (`app/Support/BancoSqlite.php:recriar:39`, lança `RuntimeException`).
- **Path**: `app/Models/AgenteIa.php` (comentário do cast nas linhas 75 a 77, `app/Models/AgenteIa.php:'temperatura':78`)
  e irmã `app/Ai/Agents/GuardaPrompt.php:max_tokens:19`
  - As duas dizem que a temperatura do catálogo "vai direto para o provider" — falso:
    `vendor/laravel/ai/src/Gateway/TextGenerationOptions.php:forAgent:69-76` só lê
    `temperature()`/`maxTokens()` por método ou atributo do próprio agente, e
    `grep -rnE "temperature|maxTokens" app/Ai/` não acha nenhum — o valor do catálogo nunca sai do
    banco (`AgenteBase` não implementa `temperature()`). Reescrever as duas para dizer a verdade: o
    cast é `float`, e o valor ainda não chega ao SDK. **Não** mudar o comportamento de `AgenteBase`
    — fora do escopo desta feature; vira achado para o mantenedor.
- **Logs**: n/a — passo é edição de texto, sem lógica de execução.
- **Verificação**:
  - `grep -rn -i "passkeys" README.md README.en.md docs/pt/referencia/pacotes-instalados.md docs/en/referencia/pacotes-instalados.md` volta vazio; a linha `F-05` do roteiro e a de `wikis/pacotes.md` dizem "desligado"; *(alterado em 2026-09-29: não volta vazio — acha as quatro linhas que dizem "passkeys desligadas"/"passkeys disabled", ver acima; o critério que vale é o do CT-42)*
  - ``grep -n '`password`' README.md README.en.md`` volta vazio — com crase: o `README.en.md` usa
    "password" como palavra comum em dez linhas, e o `grep -n "password"` sem crase nunca voltaria
    vazio;
  - `grep -rni "vite juntos\|vite together\|fila e vite$\|incluso no .composer dev" README.md README.en.md docs wikis/*.md routes/console.php` volta vazio (hoje acha as 10 linhas listadas acima — `-i` porque o roteiro escreve "Vite", e `fila e vite$` porque lá a frase quebra antes de "juntos");
  - `vendor/bin/pest tests/Kit/CustomizadorDaInstalacaoTest.php --compact` verde.

### 2. Dependência do site: `astro-mermaid` + `mermaid@11.17.2` (RQ-05, RQ-12, RQ-18)

> Skills: nenhuma

- **Path**: `site/package.json`
  - Adicionar em `dependencies`: `"astro-mermaid": "^2.1.0"`, `"mermaid": "11.17.2"`.
- **Path**: `site/package-lock.json` — regenerado pelo `npm install` e commitado **junto** com o
  `site/package.json`: o workflow do site roda `npm ci` (`.github/workflows/pages.yml:npm:55`), que
  reprova lock divergente, e o `[CT-15]` exige o lock
  (`tests/Kit/SiteDeDocumentacaoTest.php:'package-lock':673`).
- **Path**: `site/astro.config.mjs`
  - Importar o export default do `astro-mermaid` (o README do pacote mostra
    `import mermaid from 'astro-mermaid'`; conferir o nome no `node_modules` instalado) e inseri-lo
    no array `integrations`, **antes** de `starlight(...)` (`site/astro.config.mjs:integrations:52`),
    com `autoTheme: true`.
- **Path**: `site/verifica-acessibilidade.mjs`
  - No ponto onde a página carrega (antes da entrega, a linha 77 de `site/verifica-acessibilidade.mjs`, com
    `waitUntil: 'domcontentloaded'`), nas páginas que têm bloco `.mermaid`, esperar
    `svg[role~="graphics-document"]` antes de rodar o axe — **`~=`** (casa uma palavra do atributo):
    o Mermaid grava `role="graphics-document document"`, e `[role="graphics-document"]` (igualdade)
    não casaria nunca. *(alterado em 2026-09-29: a espera não é por seletor de SVG — o conferidor espera todo `pre.mermaid` ganhar `data-processed` (`site/verifica-acessibilidade.mjs:waitForFunction:238`), que o `astro-mermaid` grava ao terminar, e só então confere cada bloco; o `goto` com `domcontentloaded` continua em `site/verifica-acessibilidade.mjs:domcontentloaded:230`)*
  - Na mesma página, **reprovar** (entrar em `violacoes`) quando algum `.mermaid` não virou `<svg>`
    ou virou o diagrama de erro do Mermaid (texto `Syntax error in text`). É esta a validação de
    sintaxe dos blocos: a guarda Pest do passo 14 lê texto e não roda o Mermaid, e o GitHub não
    reprova nada — sem este item, um bloco quebrado publica em silêncio. *(alterado em 2026-09-29: o `astro-mermaid` 2.1.0 não desenha o SVG de erro do Mermaid — no `catch` ele troca o bloco por um `<div>` com `<strong>Error rendering diagram:</strong>`; a reprovação é por essa estrutura (`site/verifica-acessibilidade.mjs:temElementoDeErro:256`), nunca por regex no texto (CR-9 da rodada 1). E o conferidor ganhou os CT-B01 e CT-B02 do `05`: contagem EXATA de SVG por página contra os blocos da fonte, nos dois temas, console sem erro, piso de 10 páginas com diagrama por idioma, e a troca de tema com a página aberta)*
  - Acrescentar o caminho da página nova (`/pt/referencia/arquitetura-em-diagramas/`) à lista
    `AMOSTRA_CLARA` (`site/verifica-acessibilidade.mjs:AMOSTRA_CLARA:89`). *(alterado em 2026-09-29: e a página en também, `/en/referencia/arquitetura-em-diagramas/`)*
- **Logs**: n/a — mudança de configuração de build, sem lógica de runtime.
- **Verificação**:
  - `cd site && npm install && npm run build` — build verde; `git status --porcelain site/` mostra
    `package.json` **e** `package-lock.json`.
  - Sentinela do Risco 2: no HTML construído, o bloco saiu como `<pre class="mermaid">` e não como
    bloco de código realçado — `grep -c 'class="mermaid"' site/dist/pt/referencia/arquitetura-em-diagramas/index.html`
    ≥ 4 (DG-01, DG-02, DG-03, DG-13); o `<svg>` **não** está no `dist/`, e procurá-lo ali
    (`grep graphics-document`) reprovaria sempre.
  - `node site/verifica-acessibilidade.mjs` verde.
  - *(alterado em 2026-09-29: o CT-B01 achou o contraste do rótulo de aresta no tema escuro, 4,43:1,
    corrigido no passo 22 em `site/src/styles/kit.css` — RQ-33 —, e o `pages.yml` passou a rodar o
    mesmo conferidor com o nome "Conferir diagramas e acessibilidade" antes do upload do artefato; o
    CI de pull request ganhou um job próprio no passo 23)*

### 3. README (pt/en): DG-01 + link para a página de diagramas (RQ-11, RQ-20, RQ-21)

> Skills: `pest-testing` (para a guarda no passo 14)

- **Path**: `README.md`, `README.en.md`
- Inserir, numa posição que não estoure `[CT-13]`, o diagrama **DG-01 — Arquitetura em camadas**.
  O README **não tem** seção de arquitetura (`grep -n '^## ' README.md`): o lugar natural é dentro de
  `## Os três painéis` (`README.md:painéis:106`, `README.en.md:panels:106`), antes de
  `### Como cada um se parece`, porque é ali que o leitor encontra os painéis que o diagrama liga:

  **Tipo**: `flowchart TD` com `subgraph`.

  **Nós (ID estável · rótulo pt · rótulo en)**:

  | ID | pt | en |
  |---|---|---|
  | `navegador` | Navegador | Browser |
  | `rota_publica` | `/` pública | Public `/` |
  | `painel_app` | `/app` (padrão) | `/app` (default) |
  | `painel_admin` | `/admin` | `/admin` |
  | `painel_infra` | `/infra` | `/infra` |
  | `acesso_por_papel` | Acesso por papel | Role-based access |
  | `configuracoes` | Configurações | Settings |
  | `agentes_ia` | Agentes de IA | AI agents |
  | `banco` | Banco de dados | Database |
  | `fila` | Fila | Queue |
  | `cache` | Cache | Cache |
  | `worker` | Worker (opcional) | Worker (optional) |
  | `agendador` | Agendador (opcional) | Scheduler (optional) |
  | `reverb` | Reverb (opcional) | Reverb (optional) |
  | `pulse` | Pulse (`pulse:check`) | Pulse (`pulse:check`) |
  | `ia_externa` | IA local ou SaaS | Local or SaaS AI |
  | `oauth` | OAuth | OAuth |
  | `email_externo` | E-mail | Mail |
  | `packagist` | Packagist | Packagist |

  **Estrutura**: `navegador` liga a `rota_publica` e aos três painéis; os três painéis ligam a
  `acesso_por_papel`; `acesso_por_papel` liga a `configuracoes` e a `agentes_ia` (só a partir de
  `painel_app`, ver DG-11); `configuracoes`/`agentes_ia` ligam ao bloco `banco`/`fila`/`cache`;
  `fila` liga a `worker` (nota "opcional — precisa do serviço rodando"); `agendador`/`reverb` como
  nós conectados a `fila`/`painel_admin` respectivamente, marcados opcionais; `pulse` liga a
  `painel_infra` (a tela Pulse do `/infra`) e a `banco` (storage em banco); `agentes_ia` liga
  (tracejado) a `ia_externa`; `painel_admin`/pilha de auth ligam (tracejado) a `oauth`; `configuracoes`
  liga (tracejado) a `email_externo`; `painel_admin` liga (tracejado) a `packagist` (Release
  Notifier).
  *(alterado em 2026-09-29: a rodada 1 da revisão do diff (RD-05) mostrou quatro arestas que o código
  não tem, e o bloco publicado as corrigiu — `reverb --> camada_paineis` (o Reverb serve os três
  painéis, não só o `/admin`), `painel_infra -.-> packagist` (o Release Notifier é plugin do `/infra`),
  `camada_paineis -.-> oauth` (o login social entra por um hook global, não pelo `/admin`) e
  `acesso_por_papel --> agentes_ia` sem o "só a partir do `painel_app`" (o catálogo de agentes é
  administrado no `/admin`). O CT-10 do `04` recusa cada uma das quatro, nas quatro cópias do bloco)*

  **Fato do código**: ids `app`/`admin`/`infra` de `Filament::getPanels()`
  (`app/Providers/Filament/AppPanelProvider.php:id:76`,
  `app/Providers/Filament/AdminPanelProvider.php:id:67`,
  `app/Providers/Filament/InfraPanelProvider.php:id:88`).

  **Guarda**: `Filament::getPanels()` produz exatamente os 3 ids do diagrama. Regra do catálogo
  (ADR-06): a guarda confere **tudo que o diagrama desenha, e só isso** — nenhum nó tem rótulo de
  "(padrão)", então não há checagem de `config()`; se um nó desses ganhar esse rótulo, a guarda
  passa a ler o default correspondente no `config/*.php`.

  **Verificação**: teste do passo 14 (`DiagramasDaArquiteturaTest.php`, `[DG-01]`).

- Logo abaixo do diagrama, o link: *"Veja todos os diagramas da arquitetura →
  [gsferro.github.io/filament-starter-kit-easy/pt/referencia/arquitetura-em-diagramas/](https://gsferro.github.io/filament-starter-kit-easy/pt/referencia/arquitetura-em-diagramas/)"*
  (e o equivalente `/en/` no README.en.md).
  *(alterado em 2026-09-29: o link aponta para `…/arquitetura-em-diagramas.html`, o stub de redirect
  que o `converter.mjs` gera, e não para o diretório)*
- **Logs**: n/a.
- **Verificação**: `wc -l README.md README.en.md` continua ≤ 756/767 (hoje 445/446 — folga de
  ~310; anotar o acréscimo real do bloco); `[CT-13]`/`[CT-14]` verdes.
  *(alterado em 2026-09-29: medido — 490/491 linhas, +45 líquidas no `README.md` (`git diff --stat
  origin/main...HEAD -- README.md`: 52 inserções, 7 remoções): o bloco de 42 linhas com as cercas, o
  link e as linhas em branco; as correções de texto trocam linhas sem somar)*

### 4. Página nova de diagramas: `docs/{pt,en}/referencia/arquitetura-em-diagramas.md` (RQ-05, RQ-12, RQ-21, RQ-23, RQ-30)

> Skills: `pest-testing`

- **Path**: `docs/pt/referencia/arquitetura-em-diagramas.md` (e o par em
  `docs/en/referencia/`)
- Front matter: `title: "Arquitetura em diagramas"` / `"Architecture diagrams"`,
  `description:` (1 frase, ver regra de `[CT-29]` — não pode ser igual ao título nem começar por
  `![`), `sidebar:\n  order: 5` (próximo livre na seção `referencia/`, que hoje vai de 0 a 4).
- Conteúdo: os diagramas **DG-01** (mesmo bloco do README — texto idêntico, para que a guarda
  compare os dois README/`docs` como a mesma fonte), **DG-02**, **DG-03** e **DG-13**, cada um com
  um parágrafo curto de contexto; um **índice** ao final linkando (relativo) para cada página que
  tem diagrama (as 12 páginas dos passos 5 a 13 e 15 a 18), no estilo de URL das páginas irmãs
  (`../../autenticacao/convites/` — o `[CT-22]` resolve em espaço de URL,
  `tests/Kit/SiteDeDocumentacaoTest.php:caminhoResolvido:190`, e a folha nova é publicada como
  diretório), nunca `.md` nem caminho absoluto (`[CT-45]`); e o crédito ao GitDiagram (ADR-11), texto:
  *"O GitDiagram gerou uma primeira leitura deste repositório por IA — não verificada, e os
  diagramas desta página a corrigem e ampliam. [O diagrama oficial é este](https://gitdiagram.com/gsferro/filament-starter-kit-easy)."*
  (tradução equivalente em en).

  **DG-02 — Casos de uso por papel**

  **Tipo**: `flowchart LR`, atores fora de um `subgraph fronteira["Starter Kit Easy"]`.

  **Nós (ID · pt · en)**:

  | ID | pt | en |
  |---|---|---|
  | `ator_visitante` | Visitante | Visitor |
  | `ator_panel_user` | Usuário do painel (`panel_user`) | Panel user (`panel_user`) |
  | `ator_admin_app` | Admin da organização (`admin_app`, opcional) | Organization admin (`admin_app`, optional) |
  | `ator_admin` | Administrador (`admin`) | Administrator (`admin`) |
  | `ator_infra` | Infraestrutura (`infra`) | Infrastructure (`infra`) |
  | `ator_master_global` | Super admin (`master_global`) | Super admin (`master_global`) |
  | `ator_agendador` | Agendador | Scheduler |
  | `ator_cli` | Operador da CLI | CLI operator |
  | `cu_login` | Entrar | Log in |
  | `cu_login_social` | Entrar com provedor social | Log in with social provider |
  | `cu_recuperar_senha` | Recuperar senha | Reset password |
  | `cu_cadastro` | Cadastrar-se (opcional) | Sign up (optional) |
  | `cu_aceitar_convite` | Aceitar/recusar convite | Accept/decline invite |
  | `cu_trocar_painel` | Trocar de painel | Switch panel |
  | `cu_2fa` | Confirmar 2FA | Confirm 2FA |
  | `cu_bloqueio` | Bloquear/desbloquear sessão | Lock/unlock session |
  | `cu_convidar` | Convidar usuário | Invite user |
  | `cu_gerir_usuarios_org` | Gerir usuários da organização (opcional) | Manage organization users (optional) |
  | `cu_aprovar_cadastro` | Aprovar cadastro | Approve sign-up |
  | `cu_gerir_papeis` | Gerir papéis | Manage roles |
  | `cu_configuracoes` | Configurar a aplicação | Configure the application |
  | `cu_ver_log_acesso` | Ver log de acesso | View access log |
  | `cu_lixeira` | Usar a Lixeira | Use the Recycle Bin |
  | `cu_personificar` | Personificar usuário | Impersonate user |
  | `cu_lembrar_convites` | Lembrar convites pendentes | Remind pending invites |
  | `cu_tenancy` | Ligar multi-organização | Enable multi-tenancy |

  **Estrutura**: cada ator liga aos casos de uso da tabela `8. Candidatos a diagrama` de
  `inv-acesso.md`, seção (a); `ator_admin_app` e `cu_gerir_usuarios_org` marcados "(opcional)" —
  só existem com tenancy ligada.

  **Fato do código**: `Role::pluck('painel', 'name')` depois do `PapeisSeeder`, nos dois modos
  (`database/seeders/PapeisSeeder.php:papel:55`, `:papel:58`, `:papel:61`, `:papel:80`, `:papel:101`);
  o `admin_app` só nasce com `config('kit.tenancy.enabled')`
  (`database/seeders/PapeisSeeder.php:tenancy:79`), não com `permission.teams`.

  **Guarda**: os atores **de papel** do diagrama (`ator_panel_user`, `ator_admin_app`, `ator_admin`,
  `ator_infra`, `ator_master_global` — cada um com o nome do papel entre crases no rótulo) são
  exatamente as chaves de `Role::pluck('painel', 'name')` depois do `PapeisSeeder`; os três atores
  que não são papel (`ator_visitante`, `ator_agendador`, `ator_cli`) ficam fora da comparação, senão
  a igualdade reprovaria sempre. Sem tenancy: 4 papéis, `admin_app` ausente; com tenancy: 5 — o
  modo com tenancy esbarra na restrição de bootstrap do passo 14.

  **DG-03 — Regra de acesso ao painel**

  **Tipo**: `flowchart TD`.

  **Nós**: `inicio` (Início/Start) → `checa_indisponivel{Conta indisponível?/Account
  unavailable?}` → (sim) `nega_403` (Nega — 403/Deny — 403); (não) → `checa_pendente{Aprovação
  pendente?/Approval pending?}` → (sim) `nega_403`; (não) → `checa_master{É master_global?/Is
  master_global?}` → (sim) `permite` (Permite/Allow); (não) → `checa_contexto{Painel com
  organizações?/Panel with tenants?}` → (sim, só com tenancy) `papel_em_alguma` (papel em qualquer
  organização/role in any tenant) · (não) `papel_global` (papel no contexto global/role in the
  global context) → `checa_papel{Tem papel do painel?/Has panel role?}` → (sim) `permite`; (não)
  → `nega_403`. Depois de `permite` num painel com tenancy, `checa_tenant{canAccessTenant}` →
  organização inativa → `nega_404`; `master_global` → `permite_tenant`; vínculo com a organização
  → `permite_tenant`; sem vínculo → `nega_404`.

  **Fato do código**: ordem real de `User::canAccessPanel()` —
  `app/Models/User.php:canAccessPanel:156`, indisponibilidade `:166`, pendência `:193`,
  master global `:206`, contexto `app/Models/User.php:contexto:217` (painel com tenancy → `null`,
  sem → `contextoGlobal()`), papel do painel `:219`; `User::canAccessTenant()`
  (`app/Models/User.php:canAccessTenant:789`: inativa `:813`, `master_global` `:826`, vínculo
  `app/Models/User.php:tenants:830`).

  **Guarda**: dataset com uma conta por ramo exercitando `canAccessPanel()` — indisponível,
  pendente, `master_global`, com papel, sem papel (5 linhas; cada linha muda o veredito) — e
  conferindo que o veredito bate com o nó `permite`/`nega_403` correspondente; `canAccessTenant()`
  com organização inativa, `master_global`, vínculo e sem vínculo (4 linhas). O ramo
  `papel_em_alguma` × `papel_global` só existe com `permission.teams` ligado — restrição de
  bootstrap do passo 14.

  **DG-13 — ER do núcleo**

  **Tipo**: `erDiagram`.

  **Entidades (ID · pt · en — mesmo nome nas duas, ER não tem rótulo textual traduzido além do
  nome da entidade, que já é técnico)**: `users`, `roles`, `model_has_roles`, `tenants`,
  `tenant_user`, `convites`, `vinculos_sociais`, `agentes_ia`; relações lógicas (sem FK, tracejadas
  na nota) para `ai_runs` e `agent_conversations`; `projetos` marcado "(demo)"; `passkeys`
  deliberadamente **fora** do diagrama (existe a tabela, mas nenhuma feature do kit a usa —
  ver `02`, ADR-10, nota sobre não desenhar o que não é usado).

  **Fato do código**: `User N:N Tenant` via `tenant_user`
  (`app/Models/User.php:tenants:748`, `app/Models/Tenant.php:users:112`); `User 1:N VinculoSocial`
  (`app/Models/User.php:vinculosSociais:758`); `User N:N Role` via `model_has_roles`
  (`app/Models/Role.php` — `roles.painel`); `Convite` pertence a `Role`/`Tenant`/`User`
  (`app/Models/Convite.php:papel:116`, `:tenant:122`, `:convidadoPor:128`); `Projeto` pertence a `Tenant`
  (`app/Traits/BelongsToTenant.php:tenant:82`); `ai_runs.tenant_id` e `.task` são relação lógica
  (sem FK) — `app/Ai/Listeners/RegistrarAiRun.php` grava `tenant_id`/`task` como string.

  **Guarda**: para cada aresta com FK declarada, `Schema::getForeignKeys('{tabela}')` contém a
  chave esperada; para as arestas marcadas lógicas, a ausência de FK é esperada (não reprova);
  todas as tabelas citadas ⊆ `Schema::getTableListing()`.

  *(alterado em 2026-09-29: a rodada 1 da revisão do diff achou três destes diagramas afirmando o que
  o código não faz, e os blocos publicados foram corrigidos (`a48c9b2`) — **DG-02**: o `admin` não liga
  a "Personificar usuário", porque `User::canImpersonate` só aceita o `master_global` (RD-04);
  **DG-03**: a organização inativa barra **antes** do `master_global`, e o ramo de tenant tem dois nós,
  `checa_tenant` (organização inativa?) e `checa_vinculo` (`master_global` ou vínculo?), o que o fato da
  guarda confere pelo ID e pelo rótulo (RD-12, RD4-03); **DG-13**: `convidado_por_id` é nulo (ponta
  opcional), `ai_runs` e `agent_conversations` não têm FK de usuário, e o bloco `convites` ganhou os
  atributos com as colunas reais, que o CT-28 confere contra o schema (RD-06, RD2-13). As guardas de
  cada um estão no `04`: CT-11, CT-73, CT-83 (DG-02); CT-06, CT-13, CT-71 (DG-03); CT-27, CT-28, CT-63,
  CT-94 (DG-13))*

- **Comando que regenera sidebar/stubs/redirects**: `node converter.mjs` (dentro de `site/`, depois
  de escrever as duas páginas `.md`) — conferir com
  `git diff --stat site/sidebar.json site/public/ site/redirects.json` mostrando só adições.
- **Logs**: n/a.
- **Verificação**: teste do passo 14, casos `[DG-01]`, `[DG-02]`, `[DG-03]` e `[DG-13]`; `[CT-01,03,04,05,19,20,22,29,30,34,36,37,38,41,44,45]` verdes; `node
  verifica-links.mjs` verde.

### 5. `docs/{pt,en}/autenticacao/index.md`: DG-04, DG-10 + link ao DG-03 (RQ-21, RQ-22, RQ-25)

> Skills: `pest-testing`

- **Path**: `docs/pt/autenticacao/index.md` (10 linhas hoje — cresce; o par em `docs/en/` cresce
  igual)
- Acrescenta um link para o DG-03 na página de diagramas (passo 4) — referência rápida de
  autenticação, sem copiar o bloco Mermaid.

  **DG-04 — Sequência: login por senha num painel**

  **Tipo**: `sequenceDiagram`.

  **Participantes (ID · pt · en)**: `visitante`/Visitante/Visitor, `tela_login`/Tela de
  login/Login screen, `vendor_login`/Login (Filament)/Login (Filament), `authenticate`/`Authenticate`/`Authenticate`,
  `authenticate_session`/`AuthenticateSession`/`AuthenticateSession`,
  `must_two_factor`/`MustTwoFactor`/`MustTwoFactor`, `locker`/`Locker`/`Locker`,
  `identify_tenant`/`IdentifyTenant` (opcional)/`IdentifyTenant` (optional),
  `definir_tenant`/`DefinirTenantDePermissoes` (opcional)/`DefinirTenantDePermissoes` (optional),
  `exigir_email`/`ExigirEmailVerificado`/`ExigirEmailVerificado`,
  `resposta_login`/`RespostaDeLogin`/`RespostaDeLogin`.

  **Estrutura**: `visitante` → `tela_login`: credenciais; `tela_login` → `vendor_login`:
  `authenticate()`; nota "rate limit 5 tentativas"; `alt` conta indisponível: recusa (403) via
  `ContaIndisponivelController`; `else`: `attemptWhen` → sessão regenerada → `resposta_login`
  decide o destino; a seguir, a pilha de middleware do **próximo request** ao painel, na ordem
  real: `authenticate` → `authenticate_session` → `must_two_factor` → `locker` → (opcional, só com
  tenancy) `identify_tenant` → `definir_tenant` → `exigir_email` (só obrigatório no `/app`, com a
  chave ligada).

  **Fato do código**: `app(LoginResponse::class)` é `RespostaDeLogin`
  (`app/Providers/KitServiceProvider.php:LoginResponse:829` — vincula a interface;
  `app/Http/Responses/RespostaDeLogin.php:unificado:29` e `:urlPara:33`);
  ordem real de middleware conferida via `php artisan route:list --json` na rota
  `app/{tenant:slug}/convites` (autenticação → sessão → 2FA → lockscreen → tenant → e-mail).

  **Guarda**: `app(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class)` — o contrato
  que o kit vincula (`app/Providers/KitServiceProvider.php:LoginResponse:31`, o `use`) — é instância
  de `RespostaDeLogin`; e a lista **resolvida** de middleware de uma rota do painel `app`
  (`app('router')->gatherRouteMiddleware($rota)`, que expande grupo e alias em classe — é o que o
  `route:list` imprime; o `gatherMiddleware()` da rota pode devolver nome de grupo ou alias) contém,
  nesta ordem relativa (subsequência, não igualdade — a lista tem o `web` inteiro antes), as classes
  do diagrama. Sem tenancy, a subsequência é `Authenticate` → `AuthenticateSession` →
  `MustTwoFactor` → `Locker` → `ExigirEmailVerificado`; os dois nós opcionais
  (`IdentifyTenant` → `DefinirTenantDePermissoes`) só aparecem com o painel `app` montado com
  tenancy, no boot (`app/Providers/Filament/AppPanelProvider.php:tenantMiddleware:595`) — restrição
  de bootstrap do passo 14.

  **DG-10 — Sessão autenticada (pertinente, RQ-25)**

  **Tipo**: `stateDiagram-v2`.

  **Estados (ID · pt · en)**: `autenticada`/Autenticada/Authenticated,
  `aguardando_2fa`/Aguardando 2FA/Awaiting 2FA, `bloqueada`/Bloqueada/Locked,
  `encerrada`/Encerrada/Ended.

  **Estrutura**: `[*] --> autenticada`; `autenticada --> aguardando_2fa` (nota: só quem já
  confirmou 2FA passa por aqui — `MustTwoFactor`); `aguardando_2fa --> autenticada` (confirmação
  ok); `autenticada --> bloqueada` (ociosidade 1800s OU bloqueio manual); `bloqueada -->
  autenticada` (senha correta); `bloqueada --> encerrada` (5 tentativas erradas — force logout);
  `autenticada --> encerrada` (logout manual).

  **Fato do código**: `config('lockscreen.idle_timeout')` = 1800
  (`config/lockscreen.php:'idle_timeout':16`); `enableRateLimit(limit: 5, decayMinutes: 5,
  forceLogout: true)` nos três painéis (`app/Providers/Filament/AdminPanelProvider.php:enableRateLimit:259`,
  `app/Providers/Filament/AppPanelProvider.php:enableRateLimit:373`,
  `app/Providers/Filament/InfraPanelProvider.php:enableRateLimit:282`);
  `HasRateLimit::isForceLogout()`/`getRateLimitLimit()`
  (`vendor/marjose123/filament-lockscreen/src/Concerns/HasRateLimit.php:isForceLogout:48`,
  `:getRateLimitLimit:58`).

  **Guarda**: `config('lockscreen.idle_timeout') === 1800`; a instância do plugin de lockscreen de
  cada um dos três painéis (`Filament::getPanel($id)->getPlugin(...)`) devolve
  `getRateLimitLimit() === 5` e `isForceLogout() === true` — os dois são públicos no trait, sem
  Reflection.

  *(alterado em 2026-09-29: ~~**esta guarda do DG-10 não foi implementada**~~ — valeu até a segunda passada do step 10; ver a nota seguinte. As guardas vieram do `04`,
  que é o contrato confirmado pelo mantenedor ("Os 104 cenários"), e ele trata os três extras (DG-10,
  DG-19, DG-20) pelas regras genéricas R1–R4, R21, R40 e por uma relação declarada em `fatosPorDg`,
  com a lacuna L-01. Os números do bloco — "ociosidade 1800s" e "5 tentativas erradas" — não têm quem
  os confira: `grep -rn "1800\|idle_timeout" tests/Kit/DiagramasDaArquiteturaTest.php
  tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php tests/Pest.php` volta vazio. Achado do step 10,
  registrado no `03` para o quality gate: RQ-26 pede guarda que falhe quando o código deixar de bater)*

  *(alterado em 2026-09-29: ciclo 2 do gate, QA-01 — a nota acima deixou de valer na segunda passada do
  step 10. **A guarda do DG-10 existe**, pela R48 do `04`, num arquivo próprio: `[CT-106]`
  (`tests/Kit/GuardasDosDiagramasTest.php:'[CT-106]':165`) lê a ociosidade, o número de tentativas e o desfecho
  que o bloco desenha e os compara, painel a painel, com o plugin de bloqueio registrado e com o fonte de
  `config/lockscreen.php`; `[CT-107]` (`:197`) prova que ela fica vermelha quando o número, o desfecho ou
  a marca de opcional mudam no mundo. Os dois verdes em 2026-09-29, no `GuardasDosDiagramasTest` 47/47)*

- **Logs**: n/a.
- **Verificação**: teste do passo 14, casos `[DG-04]` e `[DG-10]` (o `[DG-03]` é provado no passo 4, onde o bloco mora). *(alterado em 2026-09-29: não há `[DG-xx]` — o DG-04 é CT-15/CT-16 de `tests/Kit/DiagramasDaArquiteturaTest.php`, e o DG-10, CT-106/CT-107 de `tests/Kit/GuardasDosDiagramasTest.php`)*

### 6. `docs/{pt,en}/autenticacao/login-unificado.md`: DG-05 (RQ-22)

> Skills: `pest-testing`

- **Path**: `docs/pt/autenticacao/login-unificado.md` (131 linhas hoje)
- Inserir perto de "## O que muda para quem entra" (linha 37).

  **DG-05 — Sequência: login unificado → destino por 0/1/N painéis**

  **Tipo**: `sequenceDiagram`.

  **Participantes**: `visitante`/Visitante/Visitor, `tela_unificada`/Tela de login
  única/Unified login screen, `destino`/`DestinoAposLogin`/`DestinoAposLogin`,
  `escolha`/Escolha de painel/Panel choice, `controller`/`EntrarNoPainelController`/`EntrarNoPainelController`.

  **Estrutura**: `visitante` → `tela_unificada`: credenciais; `tela_unificada` →
  `destino`: `paineisDe(user)`; `alt` 0 painéis: `escolha` → encerra sessão, volta ao login; `alt`
  1 painel: `destino` → entra direto (`entrarEm`); `alt` N painéis: `escolha` mostra cartões →
  visitante escolhe → `controller` → `destino.entrarEm(painel)`.

  **Fato do código**: `TelaLoginUnificada::mount()` — redireciona se desligado, se já autenticado
  vai para `DestinoAposLogin::urlPara` (`app/Filament/Pages/Auth/TelaLoginUnificada.php:mount:35`);
  `DestinoAposLogin::paineisDe()` filtra `Filament::getPanels()` por `canAccessPanel`
  (`app/Support/DestinoAposLogin.php:paineisDe:52`); `urlPara()` decide pretendida → único painel →
  `route('login.painel')` (`:85`); `EscolhaDePainel::mount()` trata 0/1/N
  (`app/Filament/Pages/Auth/EscolhaDePainel.php:mount:62`, ramo 0 em `:80`,
  `encerrarSemPainel:109`); `EntrarNoPainelController::__invoke()` chama
  `DestinoAposLogin::entrarEm` (`app/Http/Controllers/Auth/EntrarNoPainelController.php:__invoke:27`,
  `:entrarEm:39`).

  **Guarda**: `Route::has('login')`, `Route::has('login.painel')`,
  `Route::has('login.painel.entrar')`; `config('kit.login.unificado')` default `false`
  (`config/kit.php:'unificado':719`). Comportamento por ramo (0/1/2 painéis) já provado por
  `// comportamento: tests/Kit/LoginUnificadoTest.php [CT-12] [CT-13] [CT-19]`.

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-05]`.

### 7. `docs/{pt,en}/autenticacao/login-social.md`: DG-06 (RQ-22)

> Skills: `pest-testing`

- **Path**: `docs/pt/autenticacao/login-social.md` (401 linhas hoje)
- Inserir perto de "## Vínculo com o provedor: a primeira vez, e as seguintes" (linha 137).

  **DG-06 — Sequência: retorno do login social**

  **Tipo**: `sequenceDiagram`.

  **Participantes**: `visitante`/Visitante/Visitor, `provedor`/Provedor OAuth/OAuth provider,
  `controller`/`LoginSocialController`/`LoginSocialController`,
  `convite`/`Convite` (se houver)/`Convite` (if any), `vinculo`/`VinculoSocial`/`VinculoSocial`,
  `email_fila`/E-mail (fila)/E-mail (queue).

  **Estrutura**: `visitante` → `provedor` (redirect); `provedor` → `controller`: `retorno()`; `alt`
  sem e-mail ou não verificado: recusa; `alt` com vínculo existente: entra (aviso se conta
  indisponível); `alt` sem vínculo, conta existente por e-mail: cria vínculo, envia
  `PrimeiroAcessoSocial` (fila), **se** `vinculo_confirmar` ligado → envia link de confirmação e
  **não entra ainda**; `alt` sem vínculo, sem conta, com convite válido: `Convite::aceitar()`,
  depois vincula; `alt` sem vínculo, sem conta, registro aberto: `RegistroAberto::registrar()`,
  depois vincula; nota: o vínculo (`VinculoSocial::vincular`) roda **depois** de toda a cadeia,
  inclusive para conta recém-criada.

  **Fato do código**: `LoginSocialController::redirecionar()` (`:77`), `::retorno()` (`:133`),
  `VinculoSocial::vincular()` chamado em `:315` (branch de conta existente) e `:560`
  (`confirmarVinculo`), com log `sub_ausente` quando `$sub === ''` (`:319`); `ProvedorSocial::cases()`
  = 4 (`app/Support/ProvedorSocial.php:Google:78`, mais Github/LinkedIn/X nas linhas seguintes);
  `config('kit.login.vinculo_confirmar')` default
  `false` (`config/kit.php:'vinculo_confirmar':728`).

  **Guarda**: `ProvedorSocial::cases()` tem exatamente os 4 provedores do diagrama; rotas
  `auth.social.redirect`/`auth.social.callback`/`auth.social.confirmar` existem. Comportamento por
  ramo (vínculo existente / conta por e-mail / registro aberto / sem e-mail) já provado por
  `// comportamento: tests/Kit/LoginSocialGoogleTest.php:it:280, :it:360, :it:393, :it:527` e
  `tests/Kit/LoginSocialProvedoresTest.php:it:1014` (ramo de convite).

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-06]`.

### 8. `docs/{pt,en}/autenticacao/convites.md`: DG-07 (RQ-22)

> Skills: `pest-testing`

*(alterado em 2026-09-29: o título "8 e 9" virou dois passos, 8 (DG-07) e 9 (DG-09), na mesma página —
o `rastreabilidade.sh` só reconhece `### N.`, e a Cobertura apontava para os dois números)*

- **Path**: `docs/pt/autenticacao/convites.md` (67 linhas hoje)

  **DG-07 — Sequência: convite do envio ao aceite**

  **Tipo**: `sequenceDiagram`.

  **Participantes**: `quem_convida`/Quem convida (admin ou admin_app)/Inviter (admin or admin_app),
  `convite`/`Convite`/`Convite`, `fila`/Fila/Queue, `agendador`/Agendador/Scheduler,
  `convidado_novo`/Convidado novo/New invitee, `convidado_existente`/Convidado com conta/Invitee
  with account.

  **Estrutura**: `quem_convida` → `convite`: `enviar()` — gera token, `expira_em`, e-mail
  `ConviteDeAcesso` na fila; loop diário: `agendador` → `convite`: `lembrar()` (cron `0 8 * * *`),
  se dentro de `lembretes_dias`; `alt` convidado novo: `Convite::aceitar()` cria conta; `alt`
  convidado com conta: `aceitarComoUsuarioExistente()`; `alt` recusa: `recusar()`.

  **Fato do código**: `Convite::enviar()` (`:143`), `::lembrar()` (`:207`), `::valido()` (`:464`),
  `::aceitar()` (`:606`), `::aceitarComoUsuarioExistente()` (`:674`), `::recusar()` (`:739`);
  evento agendado `Schedule::command('kit:convites-lembrar')->dailyAt('08:00')`
  (`routes/console.php:'kit:convites-lembrar':40`); `ConviteDeAcesso implements ShouldQueue`
  (`app/Notifications/ConviteDeAcesso.php:ShouldQueue:27`).

  **Guarda**: `method_exists(Convite::class, 'enviar'|'lembrar'|'aceitar'|
  'aceitarComoUsuarioExistente'|'recusar')`; `app(Schedule::class)->events()` contém um evento
  com `command` igual a `kit:convites-lembrar` e expressão cron `0 8 * * *`.

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-07]`.

### 9. `docs/{pt,en}/autenticacao/convites.md`: DG-09 (RQ-22)

> Skills: `pest-testing`

- **Path**: a mesma página do passo 8.

  **DG-09 — Estados do convite**

  **Tipo**: `stateDiagram-v2`.

  **Estados**: `pendente`/Pendente/Pending, `aceito`/Aceito/Accepted,
  `recusado`/Recusado/Declined, `expirado`/Expirado/Expired.

  **Estrutura**: `[*] --> pendente`; `pendente --> aceito`; `pendente --> recusado`; `pendente -->
  expirado` (pelo tempo); `expirado --> pendente` (reenvio); `pendente --> pendente` (reenvio,
  self-loop opcional na nota).

  **Fato do código**: `Convite::situacao()` — precedência Aceito > Recusado > Expirado > Pendente
  (`app/Models/Convite.php:situacao:587-593`); a ação "Reenviar" só é visível em Pendente ou
  Expirado (`app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:visible:89`).

  **Guarda**: os 4 valores possíveis de `situacao()` batem com os 4 estados do diagrama (comportamento
  já provado por `// comportamento: tests/Kit/ConviteUsuarioExistenteTest.php:it:125` [CT-14]); a
  aresta de reenvio é nova — a ação `reenviar` da tabela de convites do `/admin` é visível nos
  convites Pendente e Expirado e oculta nos Aceito e Recusado
  (`assertTableActionVisible`/`assertTableActionHidden` sobre a página de listagem).

- **Logs**: n/a.
- **Verificação**: teste do passo 14, casos `[DG-07]`, `[DG-09]`.

### 10. `docs/{pt,en}/autenticacao/estados-de-usuario.md`: DG-08 (RQ-22)

> Skills: `pest-testing`

- **Path**: `docs/pt/autenticacao/estados-de-usuario.md` (16 linhas hoje)

  **DG-08 — Estados da conta do usuário**

  **Tipo**: `stateDiagram-v2`.

  **Estados**: `pendente`/Pendente/Pending, `ativo`/Ativo/Active, `inativo`/Inativo/Inactive,
  `excluido`/Excluído/Deleted.

  **Estrutura**: `[*] --> pendente` (registro aberto com aprovação manual) ou `[*] --> ativo`
  (convite, registro sem aprovação, login social, criação interna); `pendente --> ativo`
  (`User::aprovar()`); `ativo --> inativo` (`desativar()`); `inativo --> ativo` (`reativar()`);
  `ativo --> excluido` / `inativo --> excluido` (soft delete); `excluido --> ativo` (restore);
  nota: só `ativo` tem `canAccessPanel() = true`.

  **Fato do código**: união de `rotuloDaSituacao()` (`app/Models/User.php:rotuloDaSituacao:501`,
  Pendente > Inativo > Ativo) com o estado `Excluído` (via `deleted_at`/`SoftDeletes`);
  `canAccessPanel()` só
  verdadeiro fora de `motivoDeIndisponibilidade()` e sem `aprovacao_pendente` (`:166,193`).

  **Guarda**: `motivoDeIndisponibilidade()` só devolve `conta_excluida`, `conta_inativa` ou `null`.
  Comportamento por estado (`canAccessPanel()` só verdadeiro em `ativo`) já provado por
  `// comportamento: tests/Kit/SituacaoDaContaTest.php:'[CT-01]':75-99` [CT-01..03].

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-08]`.

### 11. `docs/{pt,en}/operacao/roteiro-de-features.md`: DG-11 (RQ-23) — página corrigida (ver Análise dos Arquivos Existentes)

> Skills: `pest-testing`

- **Path**: `docs/pt/operacao/roteiro-de-features.md`, seção `## IA` (linha 126) — nova subseção
  `### Sequência do assistente, do prompt ao ledger`

  **DG-11 — Sequência: assistente de IA**

  **Tipo**: `sequenceDiagram`.

  **Participantes**: `usuario`/Usuário/User, `widget`/`AssistenteChatWidget`/`AssistenteChatWidget`,
  `budget`/`BudgetGuardMiddleware`/`BudgetGuardMiddleware`,
  `prompt_injection`/`PromptInjectionGuardMiddleware`/`PromptInjectionGuardMiddleware`,
  `prompt_guard_local`/`GarantirPromptSeguroMiddleware` (classificador)/`GarantirPromptSeguroMiddleware`
  (classifier), `pii_redactor`/`PiiRedactorMiddleware`/`PiiRedactorMiddleware`,
  `filtro_saida`/`FiltroSaidaSensivelMiddleware`/`FiltroSaidaSensivelMiddleware`,
  `provider`/Provider de IA/AI provider, `auditoria`/`AiAuditMiddleware`/`AiAuditMiddleware`,
  `remember`/`RememberConversation` (vendor)/`RememberConversation` (vendor),
  `ledger`/`RegistrarAiRun` → `ai_runs`/`RegistrarAiRun` → `ai_runs`.

  **Estrutura**: `usuario` → `widget`: pergunta (fase 1, `enviar()`, ≤2000 caracteres); `widget` →
  pipeline (fase 2, `responder()`), **na ordem real** em que o SDK a monta: `remember` é o mais
  externo — o SDK o põe **antes** de `middleware()` porque o `Assistente` usa
  `RemembersConversations`, e ele só age no fim — → `budget` → `prompt_injection` →
  `prompt_guard_local` (nota: vê o texto ANTES da redação) → `pii_redactor` → `auditoria` (log, por
  último, já com o prompt redigido) → `provider` (stream). No fim do stream, os `then` rodam na ordem
  em que foram registrados: `filtro_saida` (nota: detecta mas NÃO impede — os deltas já foram
  enviados) → `remember` (grava título + mensagem do usuário ORIGINAL + resposta JÁ REDIGIDA) → o
  SDK dispara `AgentStreamed` → `ledger` grava em `ai_runs`. Nota honesta: normalmente 2 linhas no
  ledger por turno (guarda-prompt + assistente); 1 quando o classificador não responde (fail-open).
  *(alterado em 2026-09-29: no bloco, a nota não pode ter `;` — no Mermaid ele encerra o statement, e
  foi exatamente isso que deixou o DG-11 sem renderizar até o CT-B01 pegar, passo 22)*

  **Fato do código**: ordem via `AgenteBase::middleware()`
  (`app/Ai/Agents/AgenteBase.php:middleware:66`) + `Assistente::middleware()`
  (`app/Ai/Agents/Assistente.php:middleware:61`, `RemembersConversations` na linha `28`);
  `GuardrailRegistry::MAPA` (`app/Ai/Guardrails/GuardrailRegistry.php:MAPA:23-27`); listener de
  `AgentStreamed` é `RegistrarAiRun` (`app/Ai/Listeners/RegistrarAiRun.php:handle:30`); o render
  hook do widget só existe no painel `app`
  (`app/Providers/Filament/AppPanelProvider.php:BODY_END:155`); limite de 2000 caracteres
  (`app/Livewire/AssistenteChatWidget.php:Validate:43`, `:pendente:96`).

  **Guarda**: depois de `$this->seed(AssistenteSeeder::class)` — o `middleware()` lê os guardrails
  do catálogo `agentes_ia` e lança `AgenteSemGuardrailsException` com a lista vazia
  (`app/Ai/Agents/AgenteBase.php:middleware:66`) —, `array_map('get_class', (new
  Assistente($usuario))->middleware())` é igual, na ordem, às seis classes de middleware do
  diagrama (`BudgetGuardMiddleware`, os quatro de `GuardrailRegistry::MAPA` na ordem semeada em
  `database/seeders/AssistenteSeeder.php:guardrails:39`, `AiAuditMiddleware`). O `remember` **não**
  está nessa lista — quem o prefixa é o SDK
  (`vendor/laravel/ai/src/Providers/Concerns/GeneratesText.php:RemembersConversations:148`) — e
  a guarda dele é `in_array(RemembersConversations::class, class_uses_recursive(Assistente::class))`.
  `Event::getRawListeners()[AgentStreamed::class]` contém `RegistrarAiRun::class`
  (`app/Providers/KitServiceProvider.php:AgentStreamed:455` — `hasListeners()` devolve só `bool`);
  grep confirma que `AssistenteChatWidget`/render hook só aparece em
  `app/Providers/Filament/AppPanelProvider.php` (não em `Admin`/`InfraPanelProvider.php`).

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-11]`.

### 12. `docs/{pt,en}/recursos/trilhas-de-infraestrutura.md`: DG-12 (RQ-23)

> Skills: `pest-testing`

- **Path**: `docs/pt/recursos/trilhas-de-infraestrutura.md` (82 linhas hoje)

  **DG-12 — Mapa do `/infra`: tela → fonte → quem grava**

  **Tipo**: `flowchart LR`.

  **Nós**: `tela_health`/Health, `tela_backups`/Backups, `tela_jobs`/Jobs (filas)/Jobs (queues),
  `tela_logs`/Logs, `tela_excecoes`/Exceções agrupadas/Grouped exceptions,
  `tela_email`/Trilha de e-mails/Mail trail, `tela_lixeira`/Lixeira/Recycle bin,
  `tela_auditoria`/Auditoria/Audit trail, `tela_log_acesso`/Log de autenticação/Authentication log,
  `tela_comandos`/Central de comandos/Command Center, `tela_pulse`/Pulse, `tela_ia`/Execuções de
  IA/AI runs — cada um ligando à tabela ou fonte que mostra (`health_check_result_history_items`,
  `backup_runs`, `queue_monitors`, `storage/logs`, `filament_exceptions_table`, `mail_logs`,
  `recycle_bin_items`, `audits`, `authentication_log`, `command_center_runs`, `pulse_*`, `ai_runs`
  — *(alterado em 2026-09-29: o bloco publicado também desenha a tela de cadastro de comandos, que
  grava `command_center_commands`, e a de pacotes, que grava `composer_release_package_snapshots` no
  sync disparado no login; o backup aparece como manual, porque o agendamento está comentado — rodada
  1 da revisão do diff e `a48c9b2`)*)
  e, dela, a **quem grava**: o `health:check` agendado a cada 15 min
  (`routes/console.php:'health:check':29`); os eventos do `spatie/laravel-backup` (o `backup:run`
  **não** está agendado — só roda à mão, pelo Command Center); o monitor de filas; o `reportable`
  do handler de exceções; o evento `MessageSending` (e-mail); a trait `Recyclable` de `User` e
  `Projeto` (lixeira); os models auditáveis e o listener `AuditarConfiguracoesDoKit` (auditoria); os
  eventos de login (log de autenticação); a própria execução pelo Command Center; os recorders do
  Pulse e o daemon `pulse:check`; `RegistrarAiRun` (`ai_runs`). Fonte do mapeamento: a seção 2 de
  `inv-ia-infra-dados.md` da pesquisa, com as citações de cada linha — o implementador reconfere
  cada uma com `sed -n` antes de desenhar, como as deste plano.

  **Fato do código**: plugins de `Filament::getPanel('infra')->getPlugins()`
  (`app/Providers/Filament/InfraPanelProvider.php:FilamentSpatieLaravelHealthPlugin:303` — health,
  `:FilamentJobsMonitorPlugin:324` — jobs, `:FilamentLogsExplorerPlugin:337` — logs,
  `:FilamentExceptionsPlugin:502` — exceções, `:FilamentMailLogPlugin:547` — e-mail,
  `:RevivePlugin:580` — lixeira, `:FilamentAuditingPlugin:330` — auditoria,
  `:FilamentAuthenticationLogPlugin:327` — log de acesso, `:CommandCenterPlugin:440` — command
  center);
  `Health::registeredChecks()` (`app/Providers/KitServiceProvider.php:configureHealthChecks:849-866`,
  entre 7 e 10 checks conforme SO).

  **Guarda** (subconjunto, nunca igualdade — o painel registra 21 plugins e a maioria não é tela do
  mapa): (a) cada uma das 9 telas-plugin do diagrama tem
  a sua classe em `array_map('get_class', Filament::getPanel('infra')->getPlugins())` (subconjunto);
  (b) as outras três vêm de página ou resource do próprio painel — `BackupRunsPage` e `Pulse` em
  `getPages()` (`app/Filament/Infra/Pages/BackupRunsPage.php:BackupRunsPage:43`,
  `app/Filament/Infra/Pages/Pulse.php:Pulse:40`), `AiRunResource` em `getResources()`
  (`app/Filament/Infra/Resources/AiRuns/AiRunResource.php:AiRunResource:31`); (c) os nomes de
  `Health::registeredChecks()` contêm os 7 checks incondicionais (Database, Cache, Queue,
  Schedule, DebugMode, Environment, OptimizedApp — `app/Providers/KitServiceProvider.php:DatabaseCheck:852`
  a `:OptimizedAppCheck:858`); disco (fora do Windows, `:UsedDiskSpaceCheck:860`) e IA local
  (conforme o provider, `:LocalAiCheck:865`) entram no diagrama marcados como condicionais e ficam
  fora da igualdade. Complementa — não apaga — o `expect($checks)->not->toBeEmpty()` de
  `tests/Kit/FundacaoTest.php:registeredChecks:83`.

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-12]`.

### 13. `docs/{pt,en}/recursos/configuracoes-do-kit.md`: DG-14 (RQ-23)

> Skills: `pest-testing`

- **Path**: `docs/pt/recursos/configuracoes-do-kit.md`, seção "## Quem manda: o banco ou o
  `.env`?" (linha 182)

  **DG-14 — De onde vem a configuração**

  **Tipo**: `flowchart LR`.

  **Nós**: `env_file`/`.env`/`.env`, `config_php`/`config/*.php`/`config/*.php`,
  `settings_banco`/Settings no banco/Settings in DB, `tela_config`/Tela de configurações/Settings
  screen.

  **Estrutura**: `env_file` → `config_php` (boot); `settings_banco` → `config_php` (sobrepõe no
  boot, SE a tabela existir); `tela_config` → `settings_banco` (grava); nota: banco inacessível →
  vale só o `.env`/`config_php`.

  **Fato do código**: `KitServiceProvider::configureSettingsDoKit()` chama
  `ConfiguracoesDoKit::aplicarNaConfig()` (`app/Providers/KitServiceProvider.php:configureSettingsDoKit:394`, delegando
  para `app/Settings/ConfiguracoesDoKit.php:aplicarNaConfig:504`); `mapaDeConfiguracao()`
  (`:367`) declara as chaves sobrepostas.

  **Guarda**: cada chave citada no diagrama existe em `mapaDeConfiguracao()`. O comportamento inerte
  sem a tabela `settings` já é provado por
  `// comportamento: tests/Kit/ConfiguracoesDoKitTest.php:it:135`.

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-14]`.

### 14. Teste-guarda novo: `tests/Kit/DiagramasDaArquiteturaTest.php` (RQ-10, RQ-26)

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Kit/DiagramasDaArquiteturaTest.php`
- Segue `.ai/rules/testes.md` (helper cruzado em `tests/Pest.php`, nunca duplicado).
- **Sentinela no arquivo inteiro, não por caso** (`beforeEach` com `naArvoreDoKit()` —
  `tests/Kit/RedeDeDocumentacaoTest.php`, nota de uso do `[CT-10]`): `tests/Kit` viaja pelo
  `kit:update`, README e `docs/` não.
- **Marcador do bloco**: todo bloco do catálogo leva, na linha seguinte à declaração do tipo, um
  comentário Mermaid `%% DG-xx` (comentário `%%` simples é permitido; o proibido é `%%{init}%%`).
  Sem ele, nem a camada 1 sabe qual bloco é qual DG, nem a camada 2 acha o bloco que compara com o
  código.
- **Camada 1 — regras comuns** (uma vez, sobre todo bloco ```` ```mermaid ```` extraído de
  `README.md`, `README.en.md` e `docs/**/*.md` via `Symfony\Component\Finder`, mesmo padrão de
  `SiteDeDocumentacaoTest.php`):
  - todo bloco tem `accTitle` e `accDescr`;
  - nenhum bloco tem `%%{init`, `classDef ... fill|color`;
  - a primeira linha significativa do bloco (depois de front matter e `%%{init}%%`, se houver)
    começa por `flowchart`, `sequenceDiagram`, `stateDiagram-v2` ou `erDiagram` (lista branca —
    barra qualquer outro tipo, incluindo `usecase-beta`, `zenuml`, `C4Context`, `architecture-beta`);
  - cada `it('[DG-xx]')` da Camada 2 acha o próprio bloco pelo marcador `%% DG-xx` e falha se ele
    sumir — é isso que garante que o catálogo DG-01..DG-20 inteiro continua publicado, sem precisar
    de uma constante à parte com os 20 IDs;
  - bloco que aparece em dois lugares é o mesmo texto nos dois — só o DG-01, no README e na página
    nova (passo 4).
- **Camada 2 — um `it()` por `DG-xx`** (os que moram aqui — ver bullet seguinte), cada um rodando
  `->with(['pt', 'en'])` e comparando o bloco de **cada idioma** contra o "Fato do código" e a
  "Guarda" descritos nos passos 3 a 13, 15, 16 e 17 acima — a paridade pt/en sai de brinde. O
  extrator de blocos e de IDs por tipo (`flowchart`: token antes de
  `[`/`(`/`{`/`-->`/`---`; `sequenceDiagram`: `participant|actor ID`; `stateDiagram-v2`: token de
  `A --> B` e de `state ... as ID`; `erDiagram`: nome da entidade) tem 1 usuário só (o irmão abaixo
  não lê bloco), então fica como função local deste arquivo, nunca em `tests/Pest.php`.
- **Onde mora cada `DG-xx`** (RQ-26, registrado aqui): DG-01 a DG-16 e DG-20 ficam **aqui**, neste
  arquivo. DG-17 mora em `tests/Kit/DuasRotasDeEntregaTest.php` (junto de `caminhosDoKit()`/
  `caminhosDeTopoQueViajam()`, que o arquivo já tem — passo 16). DG-18 e DG-19 moram em
  `tests/Kit/MysqlNoDockerTest.php` (os dois precisam do recorte `blocoDoServicoNoCompose` do
  `docker-compose.yml`, que o arquivo já tem — passos 15 e 18) — cada `it()` com
  `->skip(fn () => ! naArvoreDoKit(), …)` no próprio caso (padrão
  `tests/Kit/AcoesPinadasPorShaTest.php:skip:72`), já que esses dois arquivos não têm sentinela de
  arquivo inteiro. Nenhum helper muda de arquivo por causa disto.
- **Restrição de bootstrap (modo tenancy)**: este arquivo roda sem `permission.teams` e com o
  painel `app` montado sem tenancy (o Pest não aceita dois `TestCase` na mesma pasta). Quatro
  guardas têm uma metade que só existe com tenancy ligada — o `admin_app` do DG-02, o ramo de
  contexto e o `canAccessTenant` do DG-03, o `IdentifyTenant` → `DefinirTenantDePermissoes` do
  DG-04 e o `hasTenancy()` do DG-20 — e vão para o irmão abaixo.
- **Irmão com tenancy**: `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`, suíte `Tenancy`
  (permission.teams + painel `app` com tenancy), com a mesma sentinela `naArvoreDoKit()`. Só
  **afirma código** — não lê bloco nem marcador `%% DG-xx`, porque o lado do diagrama de cada `DG`
  já é lido no `it()` do arquivo Kit. Leva as quatro metades com tenancy: `admin_app` com
  `painel = app` (DG-02); o ramo de contexto e o `canAccessTenant` (DG-03); `IdentifyTenant` →
  `DefinirTenantDePermissoes` na pilha da rota `/app/{tenant}` (DG-04); e só `hasTenancy()`
  (DG-20 — o middleware de tenant já é conferido pelo DG-04 acima, na mesma pilha resolvida).
- *(alterado em 2026-09-29: o que o código fez diferente deste passo, e por quê)*
  - **Os testes levam os IDs do `04`, não `[DG-xx]`.** Os executores escreveram os cenários do `04`
    (`[CT-01]`..`[CT-104]`), e cada `DG` é achado pelo marcador `%% DG-xx` dentro do cenário; o mapa
    DG → página mora no próprio arquivo de teste. Não existe `it('[DG-xx]')`.
  - **DG-17, DG-18 e DG-19 moram aqui**, em `tests/Kit/DiagramasDaArquiteturaTest.php` (DG-17: CT-31,
    CT-32, CT-72, CT-93; DG-18: CT-33, CT-75, CT-84; DG-19: CT-43, CT-76) — nenhum `it()` novo em
    `tests/Kit/DuasRotasDeEntregaTest.php` nem em `tests/Kit/MysqlNoDockerTest.php`.
  - **O extrator e os helpers cruzados subiram para `tests/Pest.php`**, porque o arquivo de Tenancy
    também lê bloco (`.ai/rules/testes.md`): `tests/Pest.php:blocosMermaidDe:1089`,
    `tests/Pest.php:blocosMermaidDaArvore:1204`, `tests/Pest.php:blocoDoCatalogoNaArvore:1235`, o
    extrator de arestas normalizado das rodadas 3 e 4 (passos 22 e 24) e
    `tests/Pest.php:blocoDoServico:1874`, que saiu do `MysqlNoDockerTest`.
  - **O irmão com tenancy lê bloco**: o DG-02, o DG-03 e o DG-09 são lidos em
    `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` por `blocoDoCatalogoNaArvore()`, com a
    sentinela `naArvoreDoKit()` no arquivo inteiro (RD-02, rodada 1) — sem ela, toda instalação nova
    nasceria vermelha; `tests/Kit/RedeDeDocumentacaoTest.php` passou a varrer `tests/Tenancy` também.
  - **As guardas passam a ler o conteúdo** do bloco (RD-03, rodada 1; RQ-35, rodada 3) e a reconhecer
    toda forma de seta (RQ-34, rodadas 3 e 4).
- *(alterado em 2026-09-29: ciclo 2 do gate, QA-01, QA-05 e QA-07 — o que mudou depois da nota acima)*
  - **São três arquivos, não dois.** Os extras ganharam guarda do fato num arquivo próprio,
    `tests/Kit/GuardasDosDiagramasTest.php` (CT-106..CT-111, CT-115, CT-116; segunda passada do step 10),
    com a mesma sentinela de arquivo inteiro; o irmão com tenancy ganhou CT-112..CT-114.
  - **Um extrator só.** O CT-03 e o CT-85 leem os blocos publicados pelos extratores de `tests/Pest.php`
    (`tests/Pest.php:arestasDeFluxo:1368`, `tests/Pest.php:relacoesDeEr:1449`,
    `tests/Pest.php:mensagensDeSequencia:1511`), e não mais por um extrator local que só lia `-->`; os
    clones com outro nome saíram (`mensagensDaSequencia()` do arquivo Kit, `transicoesDoEstado()` e
    `ordemDoDG20EstaCorreta()` do irmão com tenancy), e cada um existe uma vez em `tests/Pest.php`
    (`tests/Pest.php:transicoesDeEstado:1619`, `tests/Pest.php:ordemDg20EhCorreta:1552`).
  - **Guardas novas no arquivo Kit**: o opcional por elemento e pela chave exata (CT-129, CT-130), o
    título do índice igual ao `accTitle` (CT-131), o texto visível de cada bloco no idioma dele (CT-132) e
    toda seta de sequência como mensagem (CT-126).
- **Logs**: n/a — arquivo de teste.
- **Verificação**: `vendor/bin/pest tests/Kit/DiagramasDaArquiteturaTest.php --compact` e
  `vendor/bin/pest tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php --compact` verdes;
  `vendor/bin/pest --parallel --tia` sem quebrar mais nada.

### 15. `docs/{pt,en}/comecar/instalacao-avancada.md`: DG-15, DG-18 (RQ-24)

> Skills: `pest-testing`

- **Path**: `docs/pt/comecar/instalacao-avancada.md` (224 linhas hoje)
- DG-15 entra como nova seção final "## Como a instalação acontece por dentro"; DG-18 entra logo
  após "## A aplicação containerizada e o banco" (linha 146), antes de "## Comandos" (linha 165).

  **DG-15 — Sequência da instalação**

  **Tipo**: `sequenceDiagram`.

  **Participantes**: `composer`/`composer create-project`/`composer create-project`,
  `env_hook`/`post-root-package-install`/`post-root-package-install`,
  `create_hook`/`post-create-project-cmd`/`post-create-project-cmd`,
  `kit_install`/`kit:install`/`kit:install`, `banco`/Banco/Database, `seeders`/Seeders/Seeders.

  **Estrutura**: `composer` → `env_hook`: copia `.env.example` → `.env` (só se faltar); `composer`
  → `create_hook` → `kit_install`; dentro de `kit_install`, na ordem real do `handle()`:
  `prepararEnv` → (opcional) `customizar` (pergunta nome/banco/credenciais/cor/tenancy) →
  `gerarAppKey` → `prepararBancoSqlite` → `conferirConexao` → `migrar` → (se banco acessível)
  `semear` (gera senha do admin, roda `seeders` na ordem Shield → Papéis → UsuarioAdmin →
  Assistente → GuardaPrompt) → `formatarCodigoGerado` → `publicarAssets` → `construirFrontend` →
  `banner` (mostra a senha só se gerada nesta execução) → `resumoDaCustomizacao`.

  **Fato do código**: ordem de `$this->x()` dentro de `KitInstall::handle()`
  (`app/Console/Commands/KitInstall.php:handle:81-148` — `prepararEnv:85`, `customizar:108`,
  `gerarAppKey:109`, `prepararBancoSqlite:110`, `conferirConexao:111`, `migrar:114`, `semear:119`,
  `formatarCodigoGerado:120`, `corrigirResumoDaSenha:128`, `publicarAssets:130`, `construirFrontend:133`, `banner:142`,
  `resumoDaCustomizacao:143` *(alterado em 2026-09-29: linhas depois das correções do instalador; `corrigirResumoDaSenha` é novo, passos 22 e 24)*); scripts `post-root-package-install`/`post-create-project-cmd`
  (`composer.json:'post-root-package-install':204-208`); ordem de `DatabaseSeeder::run` (`database/seeders/DatabaseSeeder.php:run:16`).

  **Guarda**: Reflection sobre `KitInstall::handle()` extrai a sequência de chamadas
  `$this->metodo()` (`ReflectionMethod::getStartLine()`/`getEndLine()`, precedente
  `tests/Kit/RaizDeUrlRegistradaTest.php:ReflectionMethod:162`, mais `codigoSemComentario()`
  `tests/Pest.php:codigoSemComentario:1914` e `preg_match_all('~\$this->(\w+)\(~')` — função local, reaproveitada por
  DG-16, DG-20 e `recriarBanco`) e confere que os métodos do diagrama aparecem nela **nesta ordem
  relativa** — subsequência, não igualdade: o `handle()` também chama o que o diagrama condensa ou
  omite (`customizarSemBanco` no ramo `--custom`, que retorna cedo, `desvincularDoSnyk`,
  `oferecerHostLocal`, `oferecerTestes`, `oferecerEstrela`, além de
  `$this->option()`/`$this->components`), e a igualdade reprovaria sempre. `composer.json` tem os
  dois scripts citados; `DatabaseSeeder::run` chama os cinco seeders na ordem do diagrama.

  **DG-18 — Containers por profile do Docker**

  **Tipo**: `flowchart TD` com `subgraph` por profile.

  **Nós/subgraphs**: `subgraph base` (`pgsql`, `redis`); `subgraph profile_mysql` (`mysql`);
  `subgraph profile_app` (`nginx`, `app`, `queue`, `scheduler`); `subgraph profile_realtime`
  (`reverb`, `pulse` — nota: também em `profile_app`); `subgraph profile_ai` (`llamacpp`,
  `llamacpp_embeddings`); `subgraph profile_mail` (`mailpit`). Nota explícita: "redis (cache) e
  reverb só valem com `.env.docker`; o `.env.example` padrão usa `CACHE_STORE=database` e
  `BROADCAST_CONNECTION=log`".

  **Fato do código**: pares serviço/profile do `docker-compose.yml` (`pgsql:29`, `redis:85`,
  `mysql:52,profiles:[mysql]` — o cabeçalho do serviço, como as outras 11 citações; a `:55` que
  estava aqui é a linha do `profiles:` —, `nginx:238,profiles:[app]`, `app:174,profiles:[app]`,
  `queue:266,profiles:[app]`, `scheduler:296,profiles:[app]`, `reverb:329,profiles:[app,realtime]`,
  `pulse:359,profiles:[app,realtime]`, `llamacpp:103,profiles:[ai,full]`,
  `llamacpp-embeddings:131,profiles:[ai,full]`, `mailpit:161,profiles:[mail,full]`); a divergência
  `.env.example` × `.env.docker` (`CACHE_STORE`: `database` vs `redis`; `BROADCAST_CONNECTION`:
  `log` vs `reverb`).

  **Guarda**: `it('[DG-18]')` mora em `tests/Kit/MysqlNoDockerTest.php` (passo 14), não no arquivo
  de teste-guarda geral — é lá que o recorte `blocoDoServico`
  (antes da entrega, na linha 50 de `tests/Kit/MysqlNoDockerTest.php`) já existe. *(alterado em 2026-09-29: o `it('[DG-18]')` não foi criado aqui — as guardas do DG-18 são CT-33, CT-75 e CT-84 em `tests/Kit/DiagramasDaArquiteturaTest.php` —, e o recorte, com dois consumidores, subiu para `tests/Pest.php:blocoDoServico:1874`; o `MysqlNoDockerTest` o chama por uma closure de uma linha)* Ele sai de **closure em `$this`**
  (montada no `beforeEach`) para função de topo do próprio arquivo (`blocoDoServicoNoCompose(string
  $compose, string $servico): string`, mesmo corpo, chamada pelos dois casos) — sem subir para
  `tests/Pest.php`, porque os dois usos ficam no mesmo arquivo. Extrai o bloco de cada serviço
  citado e compara `profiles:` com o diagrama (sem `symfony/yaml` direto — aprovação exigiria
  dependência nova, fora do escopo).

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-15]`;
  `vendor/bin/pest tests/Kit/MysqlNoDockerTest.php --compact`, caso `[DG-18]`.

### 16. `docs/{pt,en}/comecar/atualizando-o-projeto.md`: DG-16, DG-17 (RQ-24)

> Skills: `pest-testing`

- **Path**: `docs/pt/comecar/atualizando-o-projeto.md` (156 linhas hoje)
- DG-16 entra dentro de "## O jeito fácil: `php artisan kit:update`" (linha 18); DG-17 entra como
  nova seção antes de "## O jeito manual" (linha 117).

  **DG-16 — Fluxo do `kit:update`**

  **Tipo**: `flowchart TD`.

  **Nós**: `pre_voo`/Pré-voo (git limpo?)/Pre-flight (clean git?), `remote_kit`/Remote
  `kit`/`kit` remote, `escolher_tags`/Escolher origem/destino/Pick source/target,
  `diff_filtrado`/Diff filtrado por `CAMINHOS_DO_KIT`/Diff filtered by `CAMINHOS_DO_KIT`,
  `resumo`/Resumo do que mudou/Summary of changes, `nao_interativo`/Não interativo sem `--all` nem
  `--only-new`?/Non-interactive without `--all` or `--only-new`?,
  `branch_update`/Branch `kit-update/#lt;tag#gt;`/`kit-update/#lt;tag#gt;` branch (no Mermaid, `<tag>`
  cru é tratado como tag HTML e some do rótulo — usar as entidades `#lt;`/`#gt;`),
  `revisar`/Revisar e aplicar/Review and apply, `so_relatorio`/`composer.json`: só relatório/`composer.json`:
  report only, `marcar_versao`/`marcarVersao()`/`marcarVersao()`,
  `finally`/Limpa remote e tags/Clean up remote and tags. Nota: `resumo` é o ponto em que o
  `--dry-run` sai; `nao_interativo` carrega o rótulo "não interativo" (catálogo, F3); `so_relatorio`
  é o `CAMINHOS_SO_RELATORIO` que a guarda cita.

  **Estrutura**: `pre_voo` → (falha se sujo) `remote_kit` → `escolher_tags` → `diff_filtrado` →
  (nada mudou → sucesso) `resumo` → (`--dry-run` → sai) `nao_interativo` → (sim → sai sem aplicar)
  `branch_update` → `revisar` → `so_relatorio` → `marcar_versao` → `finally` (sempre roda,
  `try/finally`, salvo `--keep-remote`). Rótulo explícito: **"não interativo"**, nunca "sem TTY".

  **Fato do código**: ordem em `KitUpdate::handle()` (`app/Console/Commands/KitUpdate.php:handle:376`);
  guarda de interatividade usa `! $this->input->isInteractive()`
  (`app/Console/Commands/KitUpdate.php:isInteractive:428`).

  **Guarda**: os nós não têm o nome do método (o diagrama é abstração), então a guarda leva o
  **mapa nó → método** e confere, na sequência de `$this->metodo()` extraída do corpo de
  `KitUpdate::handle()` por Reflection, a subsequência em ordem relativa — não a igualdade: o corpo
  tem retornos antecipados (tags vazias, nada mudou, `--dry-run`, não interativo) e um
  `try/finally`. O mapa: `pre_voo` → `preVoo` (`:379`), `remote_kit` →
  `vincularKit` (`:386`), `escolher_tags` → `tagsDoKit`/`escolherDestino`/`resolverOrigem`
  (`:389`, `:397`, `:398`), `diff_filtrado` → `arquivosAlterados` (`:400`), `resumo` →
  `mostrarResumo` (`:414`), `branch_update` → `prepararBranch` (`:437`), `revisar` →
  `revisarEAplicar` (`:441`), `so_relatorio` → `relatarComposerJson` (`:444`), `marcar_versao` →
  `encerrar` (`:446`, que chama `marcarVersao` em
  `app/Console/Commands/KitUpdate.php:marcarVersao:1051`), `finally` → `desvincularKit` (`:449`). *(alterado em 2026-09-29: linhas deslocadas em uma pelo comentário que o PR #125 pôs no `KitUpdate` antes da linha 283, trazido pelo rebase sobre a `main`)*
  E: `(new ReflectionClassConstant(KitUpdate::class, 'CAMINHOS_SO_RELATORIO'))->getValue() ===
  ['composer.json']` (`app/Console/Commands/KitUpdate.php:CAMINHOS_SO_RELATORIO:367`); as opções
  que o diagrama cita (`--dry-run`, `--all`, `--only-new`, `--keep-remote`) existem em
  `Artisan::all()['kit:update']->getDefinition()` (subconjunto — a signature tem nove).

  **DG-17 — As duas rotas de entrega**

  **Tipo**: `flowchart LR` com `subgraph` por rota.

  **Nós**: `subgraph rota_create["composer create-project"]` (`gitattributes`/`.gitattributes`
  (exclusão)/`.gitattributes` (exclusion)); `subgraph rota_update["kit:update"]`
  (`caminhos_do_kit`/`CAMINHOS_DO_KIT` (inclusão)/`CAMINHOS_DO_KIT` (inclusion)); nós de "o que NÃO
  viaja" em cada rota — **os conjuntos inteiros**, porque a guarda é de igualdade: fora do
  create-project, os 7 alvos `export-ignore` (`.github`, `CHANGELOG.md`, `.styleci.yml`,
  `wikis/specs`, `docs`, `site`, `site-vitepress`); fora do kit:update, as 9 chaves de
  `FORA_DA_ENTREGA_POR_DECISAO` (`art`, `stubs`, `bootstrap`, `lang/pt_BR`, `public/fonts`,
  `public/js`, `resources/js`, `tests/Feature`, `tests/Unit`) e o `README.md` à parte (arquivo de
  topo: a varredura do `DuasRotasDeEntregaTest` só olha diretório, então ele não é chave). A lista
  anterior (4 + 4) reprovaria a própria guarda: faltavam 3 alvos e 6 chaves, e o `README.md` não é
  chave.

  **Fato do código**: `export-ignore` em `.gitattributes:20-22,32,40,46-47`;
  `KitUpdate::CAMINHOS_DO_KIT` (constante da classe) e
  `FORA_DA_ENTREGA_POR_DECISAO` em `tests/Kit/DuasRotasDeEntregaTest.php:FORA_DA_ENTREGA_POR_DECISAO:132`.

  **Guarda**: `it('[DG-17]')` mora em `tests/Kit/DuasRotasDeEntregaTest.php` (passo 14), ao lado da
  própria `FORA_DA_ENTREGA_POR_DECISAO` — nada sobe para `tests/Pest.php`. Reaproveita o que o
  arquivo já tem: `caminhosDoKit()` (`tests/Pest.php:caminhosDoKit:1855`) para `! in_array('README.md',
  CAMINHOS_DO_KIT)`, e o laço de `caminhosDeTopoQueViajam()` (`tests/Kit/DuasRotasDeEntregaTest.php:caminhosDeTopoQueViajam:181`)
  para os alvos `export-ignore`, sem o `explode('/')[0]` (aqui o alvo completo é comparado, não só o
  diretório de topo) — em vez de escrever um segundo parser de `.gitattributes`. Os alvos
  `export-ignore` batem exatamente com os nós de "fora do create-project"; as chaves de
  `FORA_DA_ENTREGA_POR_DECISAO` batem exatamente com os nós de "fora do kit:update", menos o
  `README.md`.
  *(alterado em 2026-09-29: a guarda do DG-17 mora em `tests/Kit/DiagramasDaArquiteturaTest.php` —
  CT-31, CT-72 e CT-93 do `04` —, e não em `tests/Kit/DuasRotasDeEntregaTest.php`, que não mudou.
  Dívida da rodada 4 (RD4-07): o fato do DG-17 em `fatosPorDg` só olha os nós de destino, e acrescentar
  `docs/` ao rótulo de `caminhos_do_kit` passa em pt e en)*

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-16]`;
  `vendor/bin/pest tests/Kit/DuasRotasDeEntregaTest.php --compact`, caso `[DG-17]`.

### 17. `docs/{pt,en}/recursos/multi-tenancy.md`: DG-20 (RQ-25)

> Skills: `pest-testing`

- **Path**: `docs/pt/recursos/multi-tenancy.md` (118 linhas hoje)

  **DG-20 — `kit:tenancy`: de single para multi-organização**

  **Tipo**: `flowchart TD` + `sequenceDiagram` curto (dois blocos na mesma seção).

  **Flowchart — nós**: `pre_voo`/Pré-voo (git limpo)/Pre-flight (clean git),
  `confirmar`/Confirmar (--force ou prompt)/Confirm (--force or prompt),
  `env_flag`/`KIT_TENANCY=true`/`KIT_TENANCY=true`,
  `papeis_por_tenant`/Papéis por organização (`permission.teams`)/Roles per tenant
  (`permission.teams`),
  `migrate_fresh`/`migrate:fresh --seed` (destrutivo!)/`migrate:fresh --seed` (destructive!),
  `conferir_schema`/Conferir schema/Verify schema, `demo_opcional`/`--demo` (opcional)/`--demo`
  (optional).

  **Sequência — participantes**: `visitante`/Visitante/Visitor,
  `identify_tenant`/`IdentifyTenant`/`IdentifyTenant`,
  `can_access_tenant`/`canAccessTenant()`/`canAccessTenant()`,
  `definir_tenant`/`DefinirTenantDePermissoes`/`DefinirTenantDePermissoes`.
  **Estrutura da sequência**: `visitante` → `/app/{tenant}`; `identify_tenant` → `can_access_tenant`
  → (404 se negar) `definir_tenant` fixa o team do spatie.

  **Fato do código**: ordem em `KitTenancy::handle()`
  (`app/Console/Commands/KitTenancy.php:handle:50`): `preVoo` (`:61`) → `confirmarDestruicao`
  (`:65`) → `ligarFlagNoEnv` (`app/Console/Commands/KitTenancy.php:ligarFlagNoEnv:70`, que escreve
  `KIT_TENANCY=true` por `app/Support/AtivadorDeTenancy.php:escreverEnv:35`) →
  `ligarPapeisPorTenant` (`app/Console/Commands/KitTenancy.php:ligarPapeisPorTenant:71`, delegando a
  `app/Support/AtivadorDeTenancy.php:ligarPapeisPorTenant:66`) → `recriarBanco`
  (`app/Console/Commands/KitTenancy.php:recriarBanco:72`, que roda `migrate:fresh --seed --force`
  em `:187` e `conferirSchema()` em `:189`, definido em `:198`) → `semearDemo` só com `--demo`
  (`app/Console/Commands/KitTenancy.php:semearDemo:75`: `DemoTenancySeeder` + `KIT_DEMO=true`,
  `:227,234`); tenant middleware do painel `app` contém `DefinirTenantDePermissoes`
  (`app/Providers/Filament/AppPanelProvider.php:tenantMiddleware:595`); `hasTenancy()` reflete
  `config('kit.tenancy.enabled')` (`config/kit.php:'enabled':351`).

  **Guarda**: subsequência em ordem relativa de `preVoo` → `confirmarDestruicao` →
  `ligarFlagNoEnv` → `ligarPapeisPorTenant` → `recriarBanco` → `semearDemo` no corpo de
  `KitTenancy::handle()` (Reflection, mesmo método do DG-15), e de `migrate:fresh` → `conferirSchema`
  no corpo de `recriarBanco()` — o `migrate:fresh` e o `conferirSchema` **não** estão no `handle()`,
  e uma guarda só sobre ele não os acharia; opções `--demo`/`--force` na signature;
  `Filament::getPanel('app')->hasTenancy() === config('kit.tenancy.enabled')` (vale nos dois modos
  — é só isto que o DG-20 confere sobre tenant; o middleware de tenant já é conferido pelo DG-04,
  na mesma pilha resolvida).

  *(alterado em 2026-09-29: **o flowchart do `kit:tenancy` não foi desenhado**. O DG-20 publicado é um
  bloco só, o `sequenceDiagram` da requisição em `/app/{tenant}`; a ordem de `KitTenancy::handle()`
  ficou em prosa, logo acima do bloco, com as citações de cada método
  (`docs/pt/recursos/multi-tenancy.md`, seção "Por dentro do `kit:tenancy`…", e o par en), que o
  `tests/Kit/CitacoesDeCodigoTest.php` confere. Por isso a subsequência de `KitTenancy::handle()` e de
  `recriarBanco()` não foi implementada — `grep -n "KitTenancy\|recriarBanco" tests/Kit/DiagramasDaArquiteturaTest.php`
  volta vazio. O que o DG-20 tem de guarda: `hasTenancy()` no irmão com tenancy
  (`tests/Pest.php:hasTenancy:2382`), as regras genéricas do `04` e a
  relação declarada em `fatosPorDg` (lacuna L-01). Achado do step 10, registrado no `03`)*

  *(alterado em 2026-09-29: ciclo 2 do gate, QA-01 — a última frase da nota acima deixou de valer na
  segunda passada do step 10. O flowchart do `kit:tenancy` continua fora — decisão da sessão, lacuna L-08
  do `04` —, e a ordem do comando continua sem guarda. **A sequência publicada tem guarda**, pelas R51 e
  R52: `[CT-112]` (`tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php:'[CT-112]':668`) compara a ordem que o
  bloco desenha com a pilha de middlewares de uma rota do `/app/{tenant}`; `[CT-113]` (`:856`) confere que
  o contexto de papéis é fixado com o id da organização da rota, e só no pedido permitido; `[CT-114]`
  (`:925`) executa o `GET` em cada situação e confere que o ramo desenhado leva ao status que o código dá —
  foi ele que achou o `alt` que negava a entrada do `master_global` sem vínculo, corrigido em pt e en; e
  `[CT-115]` (`tests/Kit/GuardasDosDiagramasTest.php:'[CT-115]':799`) são os controles do DG-20 contra a tabela
  literal do CT-114)*

- **Logs**: n/a.
- **Verificação**: teste do passo 14, caso `[DG-20]`. *(alterado em 2026-09-29: não há `[DG-20]` — são
  CT-112..CT-114 no irmão com tenancy e CT-115 em `tests/Kit/GuardasDosDiagramasTest.php`)*

### 18. `docs/{pt,en}/operacao/desenvolvendo-o-kit.md`: DG-19 (RQ-25)

> Skills: `pest-testing`

- **Path**: `docs/pt/operacao/desenvolvendo-o-kit.md` (93 linhas hoje) — nova seção final "## O que
  roda em segundo plano"

  **DG-19 — Segundo plano: `composer dev` × Compose × agendador**

  **Tipo**: `flowchart LR`.

  **Nós**: `subgraph dev["composer dev"]` (`server`, `queue_listen` (`--queue=default` só),
  `vite`, `reverb`, `pail` (nota: só com `pcntl`, não no Windows)); `subgraph compose["Docker
  Compose"]` (`queue_worker` (`--queue=ai,ai-post,default`), `scheduler`, `reverb_compose`,
  `pulse_compose`); `subgraph agendador["Agendador (routes/console.php)"]`
  (`health_check`/a cada 15 min, `convites_lembrar`/08:00, `purge_*`/madrugada).
  Nota explícita: "`schedule:work` NÃO roda dentro do `composer dev`" (a correção de D1).

  **Fato do código**: processos registrados pelo `artisan dev`
  (`vendor/laravel/framework/src/Illuminate/Foundation/DevCommands.php:registerDefaults:106`,
  mais `vendor/laravel/reverb/src/ReverbServiceProvider.php:register:30`); `queue:listen` sem
  `--queue` escuta só `default` (`vendor/laravel/framework/src/Illuminate/Queue/Console/ListenCommand.php:getQueue:85`,
  `config/queue.php:'default':42`); comandos do Compose (`docker-compose.yml:queue:274`,
  `scheduler:304`, `reverb:337`, `pulse:367`); eventos do agendador
  (`routes/console.php:'health:check':29` e `:'kit:convites-lembrar':40`, e as podas de retenção).

  **Guarda**: este diagrama É a guarda viva de D1/D2 (ADR-08). Três metades, as três no catálogo:
  (1) `array_column(DevCommands::commands(), 'command')` **não** contém `schedule:work` — com
  **controle positivo** antes: a lista contém `php artisan serve`, `php artisan queue:listen …`,
  `php artisan reverb:start` e `npm run dev`. O `ArtisanServiceProvider` é deferível; sem o
  controle, a lista vazia passa;
  (2) os eventos de `app(Schedule::class)->events()` contêm `health:check` a cada 15 min e
  `kit:convites-lembrar` às 08:00 (`routes/console.php:'health:check':29`,
  `routes/console.php:'kit:convites-lembrar':40`), os horários que o diagrama mostra; (3) o `command:`
  dos serviços do Compose (pelo mesmo recorte de `blocoDoServicoNoCompose` do DG-18): `queue` inclui
  `--queue=ai,ai-post,default` (superconjunto do que o `composer dev` escuta), `scheduler` roda
  `schedule:work`, `reverb` roda `reverb:start`, `pulse` roda `pulse:check`
  (`docker-compose.yml:queue:274`, `:schedule:304`, `:reverb:337`, `:pulse:367`).

  *(alterado em 2026-09-29: só a metade (1) foi implementada, e fora do `MysqlNoDockerTest` — o
  CT-76 de `tests/Kit/DiagramasDaArquiteturaTest.php` exige que todo bloco com "composer dev" nomeie
  `serve`, `queue:listen`, `vite` e `reverb` e não nomeie `schedule:work`, e o CT-43 faz o mesmo na
  prosa. ~~As metades (2) e (3) — os horários do agendador e o `command:` de cada serviço do Compose —
  não têm guarda~~ (valeu até a segunda passada do step 10; ver a nota seguinte): `grep -n "Schedule::class\|->events()\|pulse:check\|ai,ai-post" tests/Kit/DiagramasDaArquiteturaTest.php`
  não acha nada delas; o `04` trata o DG-19 como extra, pela lacuna L-01. O bloco publicado também não
  traz a nota "`schedule:work` NÃO roda dentro do `composer dev`": diz, no subgrafo do Compose, que o
  container `scheduler` é quem roda o agendador. Achado do step 10, registrado no `03`)*

  *(alterado em 2026-09-29: ciclo 2 do gate, QA-01 — as metades (2) e (3) **têm guarda** desde a segunda
  passada do step 10, pelas R49 e R50, em `tests/Kit/GuardasDosDiagramasTest.php`: `[CT-108]`
  (`tests/Kit/GuardasDosDiagramasTest.php:'[CT-108]':437`) confere que cada evento de `Schedule::events()` cai numa
  das linhas do agendador do bloco com a frequência que declara (8 eventos hoje, com o `model:prune` que o
  `bezhansalleh/filament-exceptions` agenda às 00:00, na mesma linha das podas), e `[CT-109]` (`:473`) prova que ele fica vermelho com evento novo,
  horário mudado ou aresta errada; `[CT-110]` (`:624`) confere o comando, as filas e a condição de cada
  processo do `composer dev` e do Compose, e `[CT-111]` (`:656`) o vermelho com comando ou rótulo mudado. A
  nota "`schedule:work` NÃO roda dentro do `composer dev`" continua fora do bloco; o que ele diz é que o
  container `scheduler` roda o agendador, e o CT-76 continua exigindo que o bloco com "composer dev" não nomeie `schedule:work`)*

- **Logs**: n/a.
- **Verificação**: `vendor/bin/pest tests/Kit/MysqlNoDockerTest.php --compact`, caso `[DG-19]`
  (mora ali — passo 14). *(alterado em 2026-09-29: não há caso `[DG-19]` ali; a verificação que vale é
  `vendor/bin/pest tests/Kit/DiagramasDaArquiteturaTest.php --compact`, CT-43 e CT-76, e — ciclo 2 do
  gate — `vendor/bin/pest tests/Kit/GuardasDosDiagramasTest.php --compact`, CT-108..CT-111)*

### 19. `KitArte.php`: generalizar `QUADROS_DO_GIF` → `CLIPES` (RQ-27)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Console/Commands/KitArte.php`
- Trocar `private const QUADROS_DO_GIF = [...]` (lista) por
  `private const CLIPES = ['fluxo-import-export' => [...3 quadros atuais...], 'densidade' =>
  ['densidade-confortavel','densidade-compacto','densidade-denso'], 'busca-spotlight' =>
  ['busca-spotlight-1-fechada','busca-spotlight-2-aberta'], 'login-unificado' =>
  ['login-unificado-1-formulario','login-unificado-2-escolha']]` (mapa `clipe => quadros`) — a
  chave é sempre o nome do arquivo final, sem exceção: `montarGif()` passa a iterar `self::CLIPES`
  produzindo `art/{clipe}.gif`, e o `fluxo-import-export.gif` de sempre continua saindo com o mesmo
  nome (já referenciado em `docs/pt/recursos/import-export-csv.md:fluxo-import-export:13` e
  `docs/en/recursos/import-export-csv.md:fluxo-import-export:13`) porque a chave já é ele.
- Uma origem só para todo clipe: `tests/Browser/Screenshots` — sem parâmetro de origem alternativa.
- `IMAGENS` não muda nesta feature (os dois órfãos existentes ficam como estão — dívida declarada
  em Riscos). Os quadros de `busca-spotlight`, `login-unificado` e `instalacao` (passo 21) também
  **não** entram em `IMAGENS`: quadro de GIF não vira PNG em `art/` (docblock de
  `QUADROS_DO_GIF`, na linha 41 de `app/Console/Commands/KitArte.php` antes da entrega; hoje o de `app/Console/Commands/KitArte.php:CLIPES:70`: "publicá-los dobraria o peso do repositório
  sem uso no README"). Os quadros do `densidade` já estão em `IMAGENS` hoje e continuam.
- `publicar()` confere `IMAGENS` **primeiro** (publica o que estiver lá — os quadros do `densidade`
  saem por aqui); só então generaliza o `continue` de
  `QUADROS_DO_GIF` (linha 134 antes da entrega; hoje `app/Console/Commands/KitArte.php:$quadrosDeClipe:206`) para pular o que sobrar e for quadro de um
  clipe (`busca-spotlight`, `login-unificado`, `instalacao`).
- **Logs**: n/a — este comando não usa `Log::`, usa `$this->components` (console), padrão mantido.
- **Verificação**: `php artisan kit:arte --sem-gif` não cria `art/busca-spotlight-*.png` nem
  `art/login-unificado-*.png`; reporta os dois órfãos existentes como ignorados (dívida declarada,
  inalterado); com GIF, `art/fluxo-import-export.gif`, `art/densidade.gif`,
  `art/busca-spotlight.gif`, `art/login-unificado.gif` existem.
- *(alterado em 2026-09-29: o que o código fez diferente deste passo)*
  - **`QUADROS_DO_GIF` ficou**, como a lista do clipe `fluxo-import-export`
    (`app/Console/Commands/KitArte.php:QUADROS_DO_GIF:45`), e o `CLIPES` a reaproveita — os testes a leem
    por Reflection pelo nome. O `CLIPES` tem cinco chaves, com o `install` do passo 21.
  - **A montagem mudou de forma** nas rodadas da revisão do diff: um GIF por clipe, com o diretório de
    montagem limpo antes da cópia e no `finally` (rodada 1, RD-01/CR-1, e `3c79ff0`); o ffmpeg escrevia
    no próprio GIF publicado com `-y` e truncava o arquivo numa falha — agora escreve num temporário
    ao lado do publicado, e a publicação é um `rename()` no mesmo diretório
    (`app/Console/Commands/KitArte.php:publicarGif:430`), com `-f gif` porque o temporário não termina
    em `.gif` (passos 24 e 25); `Throwable` num clipe não aborta os outros (passo 22); o ffmpeg é
    procurado diretório a diretório do `PATH`, com o `PATHEXT` no Windows, sem mutar o ambiente do
    processo (`app/Console/Commands/KitArte.php:resolverFfmpeg:460`).
  - **Medido** (`ls -l art/*.gif`, 2026-09-29): `busca-spotlight.gif` 60.692 bytes,
    `densidade.gif` 132.112, `fluxo-import-export.gif` 108.568 (era 105.452), `login-unificado.gif`
    159.179 e `install.gif` 116.347 (era 347.561); `art/` inteiro: 9.343.775 bytes (era 9.209.455 na
    `origin/main`, `git ls-tree -r -l`), sem teto de peso — P-10 confirmada pelo mantenedor.

### 20. Capturas novas para os clipes `busca-spotlight` e `login-unificado` (RQ-27)

> Skills: `pest-testing`

- **Path**: `tests/BrowserTenancy/CapturaDeArteTest.php`
- Dois cenários novos, seguindo o padrão existente do arquivo (arranjo de painel imediatamente
  antes do `visit()`, nunca no `beforeEach` — `.ai/rules/testes-browser.md`, "o `beforeEach` não
  arranja painel"):
  - `busca-spotlight`: visita uma tela do `/app`, screenshot fechada
    (`busca-spotlight-1-fechada`), abre o Spotlight (mesmo seletor de
    `tests/Browser/RoteiroDoKitTest.php:'F-45':100-154`), screenshot aberta (`busca-spotlight-2-aberta`).
  - `login-unificado`: com `ligarLoginUnificado()` (`tests/Pest.php:ligarLoginUnificado:508`), visita `/login`,
    screenshot do formulário único (`login-unificado-1-formulario`); autentica um usuário com 2+ painéis
    acessíveis, chega em `/login/painel`, screenshot dos cartões (`login-unificado-2-escolha`) —
    reaproveita o arranjo de `tests/Browser/LoginUnificadoTest.php` (CT-B01 — o arquivo mora em `tests/Browser/`, não em `tests/Tenancy/`) sem duplicar as
    asserções funcionais (aqui é só captura, a prova de comportamento já existe naquele arquivo).
- **Logs**: n/a.
- **Verificação**: `KIT_ART=1 php artisan test tests/BrowserTenancy/CapturaDeArteTest.php` produz
  os 4 PNGs novos em `tests/Browser/Screenshots/`.
- *(alterado em 2026-09-29: a busca é capturada em `/app/{organização}/projetos`, com um projeto criado
  no cenário e o termo "Contrato" digitado, e o quadro aberto só sai depois de o resultado aparecer
  dentro do overlay (`assertSeeIn`); os dois cenários levam o ID `[CT-B03]` do `05`)*

### 21. Correção do `art/install.gif` (RQ-28)

> Skills: `laravel-best-practices`, `pest-testing`, `ponytail`

Caminho **sem dependência nova** (ffmpeg já usado pelo `KitArte::montarGif`, Playwright já
dependência do `pest-plugin-browser`): renderizar como HTML uma transcrição REAL de terminal e
fotografá-la, exatamente como o `gifs-e-videos.md` da pesquisa recomenda (caminho "D sem
dependência nova").

- **Path**: `tests/Browser/Fixtures/terminal-instalacao.blade.php` (fixture única, sem rota
  pública — só renderizada em teste, com `view()->file(...)`). **Não** em `resources/views/arte/`:
  o `KitUpdate::CAMINHOS_DO_KIT` lista `resources/views` subdiretório a subdiretório e não traria
  um `arte/` novo, então o `kit:update` entregaria o cenário de captura sem a view que ele
  renderiza — e o `DuasRotasDeEntregaTest` não pegaria, porque só desce um nível abaixo do topo
  (`resources/views` conta como coberto). `tests/Browser` já viaja pelas duas rotas.
  - A transcrição real de uma execução de `php artisan kit:install --ansi` (feita pelo implementador
    num diretório descartável, por exemplo a partir de um `create-project` de validação, na ordem
    real do `KitInstall::handle()` — passo 15/DG-15) entra **inline** na view, como um array de
    "linhas" (texto + classe CSS para cor — verde/amarelo/cinza, imitando `Laravel\Prompts`), desenhada
    na mesma moldura estilo macOS do GIF atual (título "meu-projeto — instalação"). Um parâmetro
    `$ate` (índice da última linha) recorta cada quadro progressivo da mesma transcrição — sem uma
    segunda fixture. **A senha é substituída por um placeholder fixo** (ex.:
    `••••••••••••••••••••••••` com uma nota "(gerada — 24 caracteres)"), nunca por um valor real
    nem por um valor inventado que pareça real.
- **Path**: `tests/BrowserTenancy/CapturaDeArteTest.php`
  - Cenário novo: renderiza a view acima com 4 a 6 valores de `$ate` (quadros progressivos), tira um
    `screenshot()` por quadro, nomeados `instalacao-1-inicio` .. `instalacao-N-resumo`.
- **Path**: `app/Console/Commands/KitArte.php`
  - `CLIPES` ganha `'install' => ['instalacao-1-inicio', ..., 'instalacao-N-resumo']` — a chave já é
    o nome do arquivo (`art/install.gif`, o mesmo que `[CT-11]` exige existir no archive).
  - `IMAGENS` **não** muda por causa deste clipe (quadro de GIF não vira PNG — passo 19).
- **Logs**: n/a.
- **Verificação**: o cenário de captura faz `assertDontSee('/ password')` e
  `assertDontSee('password (padrão do kit)')` em cada quadro antes do `screenshot()` (as duas
  formas em que a senha antiga aparecia) — o texto que vira imagem é o da fixture, então conferir o
  texto confere o GIF; depois, conferir visualmente o `art/install.gif`; `[CT-11]` continua verde
  (arquivo existe).
- *(alterado em 2026-09-29: a transcrição é a saída real do `kit:install` de um `create-project` da
  v0.41.1, com a senha gerada mascarada por 24 `X` (o mesmo comprimento da senha real, nunca o valor); saíram **quatro** quadros (`instalacao-1-inicio`,
  `instalacao-2-senha`, `instalacao-3-progresso`, `instalacao-4-resumo`), fotografados com viewport
  1400x2100 — no corte padrão de 875 px o resumo, onde fica a senha, nunca aparecia. A view é
  renderizada com `view()->file()` e servida por uma rota registrada só dentro do teste
  (`tests/BrowserTenancy/CapturaDeArteTest.php:Route:814`), não pelo navegador abrindo um arquivo)*

### 22. Terceira rodada da revisão do diff: diagramas, site, guardas e instalador (RQ-28, RQ-32 a RQ-35 — Adendo 3)

*(alterado em 2026-09-29: passo novo, da reconciliação — registra o que a rodada 3 da revisão do diff
mudou no código, autorizada pelo Adendo 3; as rodadas 1 e 2 estão marcadas nos passos que alteraram,
1, 2, 14, 19 e 21)*

> Skills: `pest-testing`, `laravel-best-practices`

- **Diagramas** (`f39dd18`): o `;` dentro da nota do DG-11 encerrava o statement e o bloco virava
  erro no site (CT-B01). Nenhum outro `;` ou `#` em nota ou mensagem nos 42 blocos (RQ-32).
- **Site** (`b2991c9`): no tema escuro do Mermaid o rótulo de aresta saía 4,43:1; o fundo dele passa a
  `#404040` (6,46:1) em `site/src/styles/kit.css`, só com `data-theme='dark'` e só dentro de
  `pre.mermaid` — nenhuma cor no bloco, e o tema claro intocado (RQ-33; decisão no ADR-02).
- **Guardas** (`8a3974e`): o extrator normalizado mora em `tests/Pest.php`
  (`tests/Pest.php:existeArestaDeFluxo:1302`, `tests/Pest.php:arestasDeFluxo:1368`,
  `tests/Pest.php:relacaoDeEr:1439`, `tests/Pest.php:mensagensDeSequencia:1511`,
  `tests/Pest.php:transicoesDeEstado:1619`), usado em pt e en (RQ-34); o CT-10 lê as quatro cópias
  do DG-01, o CT-63/CT-94 rodam nos dois idiomas com os nomes reais das entidades, o DG-03 exige a
  ordem "inativa antes de master_global" como caminho no grafo, o CT-28 confere que todo atributo
  desenhado no DG-13 existe no schema, e os 21 CTs que só afirmavam o bloco aplicam o fato do código
  ao bloco real (RQ-35).
- **Instalador** (`db345fb`): `SubstituicaoEmArquivo::definirNoEnv` grava a barra invertida escapada
  (callback no lugar do `preg_replace`), e a leitura tolera o arquivo; senha digitada não utilizável
  não aparece como "a que você digitou"; a leitura da senha do `.env` de destino mora em
  `SenhaDoAdministrador` (`app/Support/SenhaDoAdministrador.php:doArquivo:92`); com `--no-seed` ou banco
  inacessível, o `KitInstall` reescreve a linha do resumo e o banner sem prometer senha (RQ-28).
- **`kit:arte`** (`12af492`): `montarClipe()` captura `Throwable` por clipe, avisa nomeando o clipe e
  segue; o diretório de montagem é limpo no `finally`; o aviso distingue "o ffmpeg falhou" de "não
  consegui publicar".
- **Logs**: n/a — `$this->components` no console, como o resto do `kit:arte` e do `kit:install`.
- **Verificação**: cada correção provada vermelha antes (`03`, `## Revisão do Diff (step 9)`,
  rodada 3); CT-B01 e CT-B02 verdes no conferidor do site.

### 23. Job `site` no CI de pull request (RQ-36 — Adendo 3)

*(alterado em 2026-09-29: passo novo, da reconciliação)*

> Skills: nenhuma

- **Path**: `.github/workflows/ci.yml`, job `site` (`a86a6eb`): espelha os passos do `pages.yml`, com
  as mesmas actions pinadas por SHA e sem publicar — `npm ci`, build, `verifica-links.mjs` e
  `verifica-acessibilidade.mjs` com o Chromium do Playwright. Roda só em `pull_request` e só quando o
  `git diff` entre as pontas do PR toca `docs/` ou `site/`.
- Na rodada 4 (`5ff5227`, RD3-11): `permissions: contents: read` no nível do job — ele roda `npm ci` e
  `playwright install --with-deps` com o token do PR e nunca publica.
- **Logs**: n/a.
- **Verificação**: `tests/Kit/AcoesPinadasPorShaTest.php` verde (as actions novas são pinadas). ~~**Não há
  CT** que confira o job — lacuna declarada no `04` (RQ sem regra própria) e dívida no `03`.~~
  *(alterado em 2026-09-29: o `04` ganhou a R47 e o CT-105 no step 10 — o job roda em `pull_request`,
  cada passo depende da condição sobre os caminhos do PR, a condição aceita `docs/` e `site/` e recusa o
  resto, e os quatro passos rodam com os conferidores depois do build, sem `continue-on-error`. ~~O teste
  do CT-105 ainda não existe: dívida DV-09 no `03`~~)* *(alterado em 2026-09-29: ciclo 2 do gate, QA-01 —
  o teste existe, `[CT-105]` em `tests/Kit/DiagramasDaArquiteturaTest.php:'[CT-105]':6223`, verde e provado
  vermelho pelos mutantes R47.M1..M6 sobre cópias do `ci.yml` em memória; a DV-09 está fechada)*

### 24. Quarta rodada da revisão do diff (RQ-34, RQ-35, RQ-28 — Adendo 4)

*(alterado em 2026-09-29: passo novo, da reconciliação)*

> Skills: `pest-testing`, `laravel-best-practices`

- **Guardas** (`697a75b`): `tests/Pest.php:SETA_DE_FLUXO:1278` cobre as formas de seta do Mermaid
  11.17.2 que o extrator perdia (`[xo<]?--+[-xo>]`, `==+`, `-.->`, `-...->`), o rótulo por travessão
  (`A -- "ws" --> B`) e a relação ER não identificadora (`..`); a linha que abre e fecha código inline de
  três crases não é cerca (RD3-05). Os fatos de DG-12/13/14/16/17 em `fatosPorDg` usam os IDs reais
  dos blocos, com controle positivo, em pt e en (RD3-06). O CT-73 afirma a ausência de dashboard e
  import no DG-02, em vez de pular com `continue` (RD3-08).
- **Instalador** (`83219ff`): com `--no-seed`, banner e resumo dão a mesma instrução — definir
  `KIT_ADMIN_PASSWORD` no `.env` e só então rodar `php artisan db:seed` (RD3-01); o desfecho vem de
  `handle()`, que grava `$semeado` onde `semear()` roda (RD3-03); uma fonte só para a linha do resumo,
  `app/Support/CustomizadorDaInstalacao.php:RESUMO_SENHA_GERADA:73` (RD3-12); o hint do prompt de senha
  não promete geração incondicional; `tests/Kit/ResumoDoKitInstallTest.php` roda o `kit:install` pelo
  ponto de entrada (RD3-04).
- **`kit:arte`** (`8a6e9e1`): o temporário do ffmpeg nasce em `art/`, ao lado do publicado
  (`app/Console/Commands/KitArte.php:$temporario:320`), e a publicação é um `rename()` no mesmo
  diretório (`app/Console/Commands/KitArte.php:rename:437`); a falha de publicação é capturada dentro de
  `publicarGif()`, avisa "não consegui publicar" e não deixa `.tmp` em `art/`; um temporário que virou
  diretório não substitui o GIF publicado (RD3-09). O CT-50 reconhece só a chamada real de captura
  (`token_get_all`), não o nome num docblock (RD3-07).
- **Logs**: n/a.
- **Verificação**: cada correção provada vermelha antes (`03`, rodada 4). O que a revisão cega desta
  rodada achou está no `03`: duas regressões e dois consertos mecânicos entraram pelo passo 25, e os
  outros seis viraram dívida declarada.

### 25. As exceções do Adendo 5: duas regressões da rodada 4 e dois consertos mecânicos (RQ-41, RQ-42)

*(alterado em 2026-09-29: passo novo, da reconciliação)*

> Skills: `pest-testing`

- **RD4-01** (Blocker, `8a6e9e1`): `'-f', 'gif'` antes da saída
  (`app/Console/Commands/KitArte.php:'-f':340`) — o temporário não termina em `.gif`, o ffmpeg escolhe
  o muxer pela extensão e, sem o formato, recusava a saída: o `kit:arte` não montava GIF nenhum. O
  ffmpeg falso da suíte passa a escolher o formato como o real (sem `-f gif` e sem extensão `.gif`,
  falha) — era por aceitar o que o real recusa que a suíte ficou verde com o defeito.
- **RD4-03** (Major, `697a75b`): o fato do DG-03 confere a estrutura pelo ID e o que cada nó pergunta
  pelo rótulo — `checa_tenant` fala de organização inativa, `checa_vinculo` traz o `master_global`.
- **RD4-04** (Pint) e **RD4-06** (citações deslocadas pelo próprio delta), em `697a75b` e `83219ff`.
- **Logs**: n/a.
- **Verificação**: RD4-01 com 8 falhas contra o `KitArte` sem `-f gif` e 28/28 depois, e o ffmpeg 8.1
  real, com os argumentos exatos, exit 0 e `GIF89a`; RD4-03 vermelho com a troca de rótulos em pt e
  em en, separadamente; `vendor/bin/pint --test` e `tests/Kit/CitacoesDeCodigoTest.php` verdes. Sem
  nova revisão cega (RQ-43).

### 26. Ciclo 2 do quality gate: os 14 achados do ciclo 1 (RQ-45, RQ-47 a RQ-49 — Adendo 6)

*(alterado em 2026-09-29: passo novo, da reconciliação do ciclo 2 — registra o que os lotes do ciclo 2 mudaram
no código; o destino de cada achado está no `03`, `## Quality Gate`)*

**Atende**: RQ-47, RQ-48, RQ-49, P-44 (e, pelos achados, RQ-10, RQ-26, RQ-28, RQ-34)

> Skills: `pest-testing`, `testing-best-practices`

- **Teste primeiro** (RQ-45): os cenários e os mutantes vieram da `feature-test-design` sobre o `06` (R54..R62,
  CT-117..CT-132, CT-B04..CT-B06), e os testes foram escritos antes das correções de docs e de CSS — CT-126,
  CT-129, CT-131, CT-132, CT-B05 e CT-B06 nasceram vermelhos contra o publicado.
- **Docs, pt e en** (QA-04, QA-08, QA-09, QA-11, QA-13): no DG-02, o caso de uso do convite diz que recusar
  exige `KIT_TENANCY`; no DG-07, o vínculo à organização só vale quando o convite tem `tenant_id`, e o ramo
  da recusa leva a chave; no DG-08, o estado Pendente leva a nota de `KIT_REGISTRO_APROVACAO_MANUAL`; o índice
  da página de diagramas usa o `accTitle` de cada bloco como título; o DG-08 diz `restore()` (do
  `SoftDeletes`), e não um `restaurar()` que não existe; o texto do `/infra` deixa de dizer que o health é o
  único agendado; os blocos en perdem o português ("(conta nova)", "conta existente", "(escolha de painel)");
  o DG-20 en diz "inactive organization"; o DG-19 pt ganha os acentos. A primeira frase da página nomeia os
  três arquivos de guarda (QA-01).
- **Guardas** (QA-03, QA-05, QA-07, QA-10): os 14 testes `[RD…]` e o da captura do instalador ganharam o
  `[CT-nn]`; o CT-03 e o CT-85 leem os blocos publicados pelos extratores de `tests/Pest.php`, e
  `tests/Pest.php:mensagensDeSequencia:1511` lê toda seta de sequência; os três clones saíram; o CT-118 prova
  que `SubstituicaoEmArquivo::definirNoEnv` ~~troca só a primeira ocorrência da chave~~ *(alterado em 2026-09-30:
  desde o Adendo 7, troca toda linha ativa e nenhuma comentada — passo 27; o mutante do limite do
  `preg_replace_callback` virou o R54.M4)*.
- **Site** (QA-06): `site/src/styles/kit.css` deixa o SVG no tamanho natural e a rolagem dentro do bloco
  (ADR-02); o conferidor ganhou o CT-B05 e o CT-B06, em `site/verifica-acessibilidade.mjs`.
- **Arte** (QA-13): `tests/BrowserTenancy/CapturaDeArteTest.php` mede a altura da janela no quadro final, em vez
  do 2100 fixo, e o `art/install.gif` foi remontado (1000 × 1484, 116.347 bytes).
- **Glossário** (QA-11): `wikis/glossario.md`, com os termos decididos nesta feature.
- **Logs**: n/a.
- **Verificação**: cada arquivo da feature rodado sozinho, o conferidor do site e os scripts da reconciliação,
  com a saída no `03`, `## 26.` e `## Verificação Final`.

### 27. Adendo 7 e a revisão adversarial da adição: a gravação no `.env` e as guardas que faltavam (RQ-50 a RQ-52; RQ-28, RQ-45)

*(alterado em 2026-09-30: passo novo — registra o que o Adendo 7 e o fechamento das duas rodadas da revisão
adversarial da adição do step 11 mudaram no código, nos testes e nas docs; os cenários estão no `04` (R54, R63..R65,
CT-133..CT-150) e no `05` (CT-B07..CT-B11))*

**Atende**: RQ-50, RQ-51, RQ-52, P-44, P-45 (e, pelos cenários, RQ-28, RQ-34, RQ-47..RQ-49)

> Skills: `pest-testing`, `testing-best-practices`

- **Teste primeiro** (RQ-45): os executores escreveram os testes contra o código de antes — CT-118 (2 linhas), CT-137
  (5 linhas) e CT-138 (7 linhas) nasceram vermelhos em `tests/Kit/CustomizadorDaInstalacaoTest.php`, e CT-144
  (DG-06 pt/en, DG-08) e CT-146 (DG-06 en) nasceram vermelhos contra as docs publicadas; a saída literal está no `03`.
- **`.env`** (RQ-50, RQ-51, P-45): `app/Support/SubstituicaoEmArquivo.php:definirLinhaNoEnv` troca **toda** linha que o
  Dotenv lê como a chave (`export`, espaço no `=`, indentação) e nenhuma comentada; sem linha ativa, descomenta a
  primeira comentada no lugar (P-45; a Q?9 fechou pelo Adendo 8, RQ-53) *(alterado em 2026-09-30: a Q?9 fechou pelo Adendo 8, RQ-53; QA-25 do ciclo 3)*; sem nenhuma, anexa — para todo chamador, `DB_CONNECTION` incluído.
  `definirNoEnv()` a chama, e `aplicarBanco()`, `AtivadorDeTenancy::escreverEnv()` e `KitTenancy::semearDemo()`
  deixaram de chamar `aplicar()` direto; `aplicar()` fica com o limite 1 para padrão arbitrário (config PHP). A
  quebra de linha já virava um espaço (RQ-51); o CT-117 passou a afirmar LF, CRLF e CR.
- **Saída do `kit:arte`** (RQ-52): a mensagem "Não consegui publicar" já existia (RD2-04/RD3-09); o CT-128 e o CT-147
  passaram a afirmar que nenhuma linha da saída culpa a montagem ou o ffmpeg.
- **Docs, pt e en**: o DG-06 nomeia as quatro chaves de provedor social numa `note over provedor` e o en perdeu o
  "(provedor social)"; a nota de Pendente do DG-08 leva `KIT_REGISTRO` e `KIT_REGISTRO_APROVACAO_MANUAL`; o DG-07 leva
  `KIT_TENANCY` no escopo do vínculo, com o `tenant_id` em posição de condição (Q?13).
- **Guardas**: o extrator de `tests/Pest.php` lê cadeia, `&`, bidirecional, as oito meias-setas de sequência, e ignora
  comentário `%%` e seta dentro de rótulo (R65, R57); o detector de opcional lê o escopo estruturado (`alt`/`else`/`opt`
  e nota) e a chave como palavra inteira (R59, `[CT-08]`); baseline de tipo e direção dos blocos (R62, CT-148); o
  conferidor do site mede a escala pela matriz da tela, inclui o HTML de `foreignObject`, confere o bloco dentro da
  coluna e a troca de tema como evento (CT-B07..CT-B11); a captura do `install.gif` confere cada quadro (CT-B04, CT-B10).
- **Logs**: n/a.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo aplica a escada de simplicidade: reutilizar o que já existe (o pipeline `kit:arte`, o
> padrão de guarda `[CT-25]`, o conversor de site já reexecutável) antes de criar mecanismo novo;
> nenhuma dependência além da já aprovada em RQ-18; nenhum diretório de topo novo.
>
> **Caveman ativo em modo `ultra`** na comunicação agente ↔ usuário. Arquivos wiki (00-06) são
> boundary do Caveman — prosa normal, como este documento.
>
> **Baseline antes do primeiro commit**: rodar `composer test` (ou `vendor/bin/pest --testsuite=Kit,Tenancy`)
> em `main` e listar por nome as falhas pré-existentes, se houver. A Verificação Final compara
> contra essa baseline, não contra zero.

## Testes

> Ver `04-casos-de-teste.md` (a ser derivado pela skill `feature-test-design`, a partir do
> `00-requisito.md`) para a especificação formal dos cenários. Este `01` já embute, em cada passo,
> o "Fato do código" e a "Guarda" que o `04` precisa cobrir — nenhum cenário é duplicado aqui.
>
> *(alterado em 2026-09-29: o `04` foi derivado — 46 regras, 104 cenários, 259 mutantes, e 3 CT-B no
> `05` — e é o contrato que o mantenedor confirmou; onde a "Guarda" de um passo aqui diverge dele, vale o
> `04`, e o passo diz o que ficou de fora. As rodadas da revisão do diff deixaram 14 testes com o ID do
> achado, sem CT — lista no `04`)*
>
> *(alterado em 2026-09-29: depois da reconferência mecânica do step 10, o `04` ganhou a R47 e o CT-105,
> para a RQ-36, que era a única `RQ` fechada sem cenário: 47 regras, 105 cenários, 266 mutantes. O teste
> do CT-105 ainda não existe — `03`, DV-09)*
>
> *(alterado em 2026-09-29: ciclo 2 do gate — o `04` tem hoje 62 regras, 132 cenários e 353 mutantes, 3 sem
> matador, e o `05`, 6 CT-B e 24 mutantes, pelos comandos do cabeçalho do `04`. O teste do CT-105 existe, e os
> 14 testes das rodadas levam o `[CT-nn]` do cenário que o `04` derivou para eles)*

## Verificação Final

*(alterado em 2026-09-29: esta é a lista planejada; a executada, com a evidência inline de cada item, está no `03`, `## Verificação Final`. "step 6.5" abaixo é o step 9 da 4.0.0, e a citação é conferida hoje pelo `citacoes.sh` da skill, que já casa md/yml/mjs/json, símbolo entre aspas e path curto)*

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] Cada arquivo tocado rodado sozinho (é o modo em que uma constante ou função presa a outro
      arquivo quebra): `tests/Kit/DiagramasDaArquiteturaTest.php`,
      `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`, `tests/Kit/MysqlNoDockerTest.php`,
      `tests/Kit/DuasRotasDeEntregaTest.php`, `tests/Kit/HelpersDeTesteTest.php`,
      `tests/Kit/CustomizadorDaInstalacaoTest.php`, `tests/Kit/RedeDeDocumentacaoTest.php`,
      `tests/Kit/SiteDeDocumentacaoTest.php` (`--filter=SiteDeDocumentacao`),
      `tests/Browser`/`tests/BrowserTenancy` (`--filter=CapturaDeArte`) — cada um `--compact`
- [ ] `vendor/bin/pest --parallel --tia` (nada mais quebrou, contra a baseline de `main`; os números
      do README/`docs/` que dependem da árvore — `[CT-25]` e os badges — saem certos ou a falha
      já aponta o valor)
- [ ] **Localmente, antes do PR** (o `pages.yml` só roda em push na `main`): `cd site && npm ci && npm run build && node verifica-links.mjs && node verifica-acessibilidade.mjs` — `npm ci`, e não `npm install`, para reproduzir o que o `pages.yml` roda sobre o lock commitado; `git status --porcelain site/` mostra o `package-lock.json` junto do `package.json`
- [ ] `/code-review high {base}...HEAD` + passe de eixos (step 6.5)
- [ ] Citações `arquivo:símbolo:linha` reverificadas com o script de
  `.claude/skills/feature-wiki/SKILL.md` (seção "Citações de código") — {n}/{n} ok. O script da skill
  só casa `.php` e símbolo sem `-`/`:`; rodar também a versão estendida (md/yml/mjs/json/ts,
  símbolo entre aspas como `'ver-logs'`/`'health:check'`, `[CT-nn]`) usada na revisão do step 5, e
  path sempre completo — path curto (só o nome do arquivo, sem o diretório) é `ERRO` no script mesmo com a linha
  certa

## Commits

*(alterado em 2026-09-29: a entrega saiu em ~~33~~ 37 commits até o ciclo 1 do gate — `git log --oneline origin/main..HEAD | wc -l` = 37 em 2026-09-29, sem os do ciclo 2, que o orquestrador faz —, com as rodadas da revisão do diff e os Adendos 3 a 6; a lista abaixo é o plano)*

- `:memo: docs(diagramas): wiki da feature diagramas-da-arquitetura`
- `:bug: fix(docs): corrige senha, passkeys e composer dev no README e nos docblocks`
- `:sparkles: feat(site): astro-mermaid + mermaid 11.17.2 para renderizar diagramas`
- `:sparkles: feat(docs): diagramas de arquitetura, acesso e ciclo de vida do kit`
- `:white_check_mark: test(kit): guarda por diagrama em DiagramasDaArquiteturaTest`
- `:camera: feat(arte): generaliza kit:arte para clipes e refaz o install.gif`

---

## Decisões da sessão (2026-09-27)

| # | Decisão | Motivo |
|---|---|---|
| 1 | **Mantida** a página do DG-11 em `docs/{pt,en}/operacao/roteiro-de-features.md`, seção `## IA` | O catálogo do Adendo 2 fixa **quais** diagramas; "onde" é da sessão. A `agentes-de-ia.md` é sobre agentes de codificação, e uma página nova só para um diagrama não se paga — a seção `## IA` do roteiro já descreve o assistente, e o diagrama fica ao lado do texto que ele explica. A página de diagramas (passo 4) lista o DG-11 no índice com o link |
| 2, 3, 5 | **Abertas até a implementação**, como riscos (ver Riscos) — cada uma tem a medição prevista no passo indicado | Não dá para decidir sem instalar o pacote, construir o site ou rodar a captura real |
| 7 | **(a)**: arquivo irmão `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`, com a mesma sentinela `naArvoreDoKit()` | É o padrão do repositório para o modo com tenancy (`tests/Tenancy/*TenancyTest.php`). Uma opção de arquivo único deixaria o RQ-26 sem cumprir em quatro diagramas, ou cobriria só metade deles |
| 8 | **Mantidas** no RQ-29 as ocorrências irmãs, inclusive as duas em `wikis/` | É a mesma afirmação falsa que o RQ-29 manda corrigir. As `wikis/` viajam no `create-project`, e deixar a cópia viva faria o DG-19 contradizer o roteiro no dia em que for publicado |
