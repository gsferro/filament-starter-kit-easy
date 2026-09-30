# Progresso — Diagramas da arquitetura do kit no README e no site

**Estado**: em revisão
<!-- Step 9 (revisão do diff, 4 rodadas) concluído; step 10 (esta reconciliação) em 2026-09-29. Passa a
     "concluída — {data}" no step 11, depois do veredito do quality gate. -->

> **Numeração dos steps.** A wiki nasceu na feature-wiki 3.x e foi reconciliada na 4.0.0. Onde este
> `03` (ou o `01`/`04`) diz "step 6.5", leia step 9 (revisão do diff); "step 7" é o 10 (reconciliação);
> "step 8" é o 11 (quality gate); "step 9" é o 12 (candidatos a rule) — tabela no README da
> feature-wiki, *Numeração dos steps — 3.x → 4.0.0*. A numeração antiga fica onde foi escrita.
>
> **Reconciliação do step 10 (2026-09-29).** Os passos 1 a 21 abaixo espelham o `01`; os 22 a 25 são
> as rodadas da revisão do diff e os Adendos 3 a 5. Cada checkbox fecha com a evidência inline; o que
> não tem evidência fica aberto e diz por quê. O que divergiu do plano está corrigido na fonte (`01`,
> `02`, `04`, `05`), marcado *(alterado em 2026-09-29: …)*, e listado em `## Desvios do Plano`.
>
> **Reconciliação do ciclo 2 do quality gate (2026-09-29).** O ciclo 1 reprovou com 14 achados
> (`06-relatorio-qa.md`), e o Adendo 6 mandou corrigir todos. Esta passada reconcilia o que os lotes do ciclo 2
> mudaram: o `## 26.` (acima de `## Testes`) tem um checkbox por achado; `## Testes` e `## Verificação Final`
> trazem a saída de cada comando rodado agora; `## Quality Gate` registra o ciclo 1 e o destino de cada achado.
>
> **Fechamento do ciclo 2 (2026-09-30).** O Adendo 7 (RQ-50..RQ-52) e as duas rodadas da revisão adversarial da adição do
> step 11 (39 + 24 achados) entraram no `04`/`05` e no código; o `## 27.` tem um checkbox por frente, com a prova vermelha
> antes da correção onde o destino era teste (RQ-45); os parciais do `## 26.` fecharam; `## Testes`, `## Verificação Final`,
> `## Blockers` e `## Despachos` (#66–#82) trazem a saída de cada comando desta passada.
>
> **Ciclo 3 (2026-09-30).** O ciclo 2 do gate reprovou (→ especificação, 9 achados); o Adendo 8 decidiu a Q?9 e o `## 28.`
> registra a correção dos nove, com a prova vermelha onde o destino era teste; `## Despachos` #84–#89.

## 1. Correções de texto e docblocks (RQ-28, RQ-29)
- [x] `README.md`/`README.en.md`: a senha do administrador deixa de ser `password` (tabela de credenciais e passo 3 da instalação) — ``grep -n '`password`' README.md README.en.md`` vazio (exit 1); a linha 32 diz "senha aleatória, gerada e impressa no fim (ou a de `KIT_ADMIN_PASSWORD`)"; commit `3342845`, 2026-09-29
- [x] Breezy sem passkeys como recurso, nos READMEs *(alterado em 2026-09-29: a palavra ficou, dizendo que estão desligadas; desvio D-01)* — `README.md:246` e `README.en.md:246` dizem "passkeys desligadas"/"passkeys disabled"; CT-42 verde em `tests/Kit/DiagramasDaArquiteturaTest.php` (no ciclo 2, 439/441 — as 2 falhas são do CT-132, `## Testes`), 2026-09-29
- [x] "`composer dev` = servidor + fila + vite" corrigido para incluir o Reverb, no README e nas irmãs de `docs/` — `grep -rni "vite juntos\|vite together\|fila e vite$\|incluso no .composer dev" README.md README.en.md docs wikis/*.md routes/console.php` vazio (exit 1), 2026-09-29
- [x] Irmãs de "passkeys" reescritas, sem apagar o `F-05` — `docs/{pt,en}/referencia/pacotes-instalados.md:23` dizem "passkeys desligadas/disabled"; `F-05` em `docs/pt/operacao/roteiro-de-features.md:32` e `docs/en/...:30` diz "desligado no kit"; `wikis/pacotes.md:10` diz "o kit as mantém desligadas", 2026-09-29
- [x] Irmãs de "`schedule:work` vem no `composer dev`" e o comentário de `routes/console.php` — `sed -n 19,22p routes/console.php`: "o `composer dev` NÃO registra o `schedule:work`"; CT-44 verde, 2026-09-29
- [x] Resumo do `kit:install` sem `password` *(alterado em 2026-09-29: a correção cresceu nas rodadas da revisão do diff; passos 22 e 24, desvio D-02)* — `app/Support/CustomizadorDaInstalacao.php:$senhaDigitadaUtilizavel:331-333` tem os três valores (digitada, já definida em `KIT_ADMIN_PASSWORD`, `RESUMO_SENHA_GERADA`); `tests/Kit/CustomizadorDaInstalacaoTest.php` 75/75 no ciclo 2 (73 antes do CT-118), `tests/Kit/ResumoDoKitInstallTest.php` 2/2, 2026-09-29
- [x] Os seis docblocks (AppPanelProvider, InfraPanelProvider, AdminPanelProvider, KitInstall, AgenteIa, GuardaPrompt) dizem o que o código faz — `app/Providers/Filament/AppPanelProvider.php:canAccessPanel:67` "exige `roles.painel = 'app'`"; `app/Providers/Filament/InfraPanelProvider.php:'ver-logs':343` aponta para `app/Providers/KitServiceProvider.php:'ver-logs':429`; `app/Providers/Filament/AdminPanelProvider.php:onboarding:262` "AUTORIA … hoje nenhum outro painel registra"; `app/Console/Commands/KitInstall.php:idempotentes:27-31` "Uma falha de INFRAESTRUTURA interrompe"; `app/Models/AgenteIa.php:SDK:76` e `app/Ai/Agents/GuardaPrompt.php:SDK:20` "ainda NÃO chega ao SDK"; CT-45, CT-66, CT-67, CT-102 verdes e `tests/Kit/CitacoesDeCodigoTest.php` 3/3, 2026-09-29

## 2. Dependência do site: `astro-mermaid` + `mermaid@11.17.2` (RQ-05, RQ-12, RQ-18)
- [x] `site/package.json` com `astro-mermaid` `^2.1.0` e `mermaid` `11.17.2` em `dependencies` — linhas 13-14; `git diff --name-only origin/main...HEAD -- composer.json composer.lock package.json package-lock.json` vazio (nenhuma dependência fora de `site/`, CT-39); CT-38 verde, 2026-09-29
- [x] `site/package-lock.json` regenerado e commitado junto — `node_modules/mermaid` resolve `11.17.2` no lock (linhas 5893-5894); `npm ci` limpo em 2026-09-29
- [x] `astro-mermaid` no array `integrations`, antes de `starlight(...)`, com `autoTheme: true` — `site/astro.config.mjs:mermaid:62`, com o `starlight(` na linha seguinte, 2026-09-29
- [x] O conferidor espera o diagrama renderizar antes do axe *(alterado em 2026-09-29: por `data-processed`, não por seletor de SVG; desvio D-03)* — `site/verifica-acessibilidade.mjs:waitForFunction:238`; CT-B01 verde, 2026-09-29
- [x] O conferidor reprova bloco que não vira SVG *(alterado em 2026-09-29: pela estrutura de erro do `astro-mermaid` 2.1.0, não pelo texto "Syntax error"; desvio D-03)* — `site/verifica-acessibilidade.mjs:temElementoDeErro:256`; CT-B01 verde, 2026-09-29
- [x] Página nova na `AMOSTRA_CLARA`, em pt e en — `site/verifica-acessibilidade.mjs:AMOSTRA_CLARA:89`, nas linhas 94 e 95 da lista, 2026-09-29
- [x] Build e sentinela do `dist/` — `npm ci && npm run build`: 69 páginas, 40 blocos transformados pelo `astro-mermaid` (20 pt + 20 en, contados no log); `grep -o 'class="mermaid"' site/dist/pt/referencia/arquitetura-em-diagramas/index.html | wc -l` = 4; `node verifica-acessibilidade.mjs` exit 0, 2026-09-29

## 3. README (pt/en): DG-01 + link para a página de diagramas (RQ-11, RQ-20, RQ-21)
- [x] DG-01 dentro de "## Os três painéis", antes de "### Como cada um se parece" — `README.md`: título na linha 106, bloco nas 126-167 (marcador `%% DG-01` na 128), "### Como cada um se parece" na 171; idem `README.en.md`, 2026-09-29
- [x] Link para a página de diagramas logo abaixo, no idioma do README *(alterado em 2026-09-29: aponta para o stub `.html`)* — `README.md:169`; CT-53 e `[CT-14]` herdado verdes (`tests/Kit/SiteDeDocumentacaoTest.php` 68/68), 2026-09-29
- [x] README dentro do teto de `[CT-13]` — `wc -l README.md README.en.md` = 490/491 (teto 756/767); `[CT-13]` verde, 2026-09-29

## 4. Página nova de diagramas: `docs/{pt,en}/referencia/arquitetura-em-diagramas.md` (RQ-05, RQ-12, RQ-21, RQ-23, RQ-30)
- [x] Página pt e en com `title`/`description`/`sidebar.order: 5` — `head -6` das duas; CT-04, CT-29, CT-30, CT-41 herdados verdes, 2026-09-29
- [x] DG-01, DG-02, DG-03 e DG-13, cada um com parágrafo de contexto — marcadores `%% DG-01/02/03/13` nas duas páginas (`grep -n "%% DG-" docs/{pt,en}/referencia/arquitetura-em-diagramas.md`); CT-36 (o DG-01 é o mesmo bloco do README) verde, 2026-09-29
- [x] Índice dos diagramas com links relativos *(alterado em 2026-09-29: uma tabela com os 20 DG, não uma lista das 12 páginas)* — `grep -c "^| DG-" docs/{pt,en}/referencia/arquitetura-em-diagramas.md` = 20 e 20; `[CT-22]`/`[CT-45]` herdados verdes, 2026-09-29
- [x] Crédito ao GitDiagram, por link, como visão gerada por IA e não verificada — `docs/pt/referencia/arquitetura-em-diagramas.md:190` e o par en; CT-54 e CT-55 verdes, 2026-09-29
- [x] `node converter.mjs` rodado: sidebar, stubs e redirects só com adições — `git diff --stat origin/main...HEAD -- site/sidebar.json site/public/ site/redirects.json`: 4 arquivos, 31 inserções, 0 remoções; `node verifica-links.mjs`: 2.584 links, 0 quebrados, 56 redirects, 2026-09-29

## 5. `docs/{pt,en}/autenticacao/index.md`: DG-04, DG-10 + link ao DG-03 (RQ-21, RQ-22, RQ-25)
- [x] Link para o DG-03 na página de diagramas, sem copiar o bloco — `docs/pt/autenticacao/index.md:13`, 2026-09-29
- [x] DG-04, login por senha e 2FA (`sequenceDiagram`) — marcador na linha 20 das duas páginas; CT-15, CT-16 verdes, 2026-09-29
- [x] DG-10, sessão autenticada (`stateDiagram-v2`) *(alterado em 2026-09-29: sem a guarda prevista no `01`; achado A-01 do step 10)* — marcador na linha 64; as regras genéricas do `04` (CT-01..CT-08, CT-77) verdes; os números do bloco (1800 s, 5 tentativas) não têm guarda, 2026-09-29

## 6. `docs/{pt,en}/autenticacao/login-unificado.md`: DG-05 (RQ-22)
- [x] DG-05, login unificado para 0/1/N painéis *(alterado em 2026-09-29: antes de "O que muda para quem entra", que hoje está na linha 78)* — marcador na linha 43; CT-17, CT-59 verdes, 2026-09-29

## 7. `docs/{pt,en}/autenticacao/login-social.md`: DG-06 (RQ-22)
- [x] DG-06, retorno do login social — marcador na linha 141, antes de "Vínculo com o provedor" (linha 188); CT-18, CT-70, CT-87 verdes, 2026-09-29

## 8. `docs/{pt,en}/autenticacao/convites.md`: DG-07 (RQ-22)
- [x] DG-07, convite do envio ao aceite (`sequenceDiagram`) *(alterado em 2026-09-29: o título "8 e 9" do `01` virou dois passos)* — marcador na linha 72; CT-19, CT-88, CT-89, CT-90 verdes, 2026-09-29

## 9. `docs/{pt,en}/autenticacao/convites.md`: DG-09 (RQ-22)
- [x] DG-09, estados do convite (`stateDiagram-v2`) — marcador na linha 111; CT-21, CT-22, CT-61, CT-80, CT-81, CT-86 verdes (no ciclo 2: Kit 439/441, com as 2 falhas no CT-132; Tenancy 45/45), 2026-09-29

## 10. `docs/{pt,en}/autenticacao/estados-de-usuario.md`: DG-08 (RQ-22)
- [x] DG-08, estados da conta (`stateDiagram-v2`) — marcador na linha 21; CT-20, CT-60, CT-78, CT-79, CT-86 verdes, 2026-09-29

## 11. `docs/{pt,en}/operacao/roteiro-de-features.md`: DG-11 (RQ-23)
- [x] Subseção "### Sequência do assistente, do prompt ao ledger" dentro de "## IA" — `docs/pt/operacao/roteiro-de-features.md`: "## IA" na 126, subseção na 135, 2026-09-29
- [x] DG-11 na ordem real do pipeline, renderizando *(alterado em 2026-09-29: sem `;` na nota; passo 22)* — marcador na linha 150; CT-23, CT-24, CT-85, CT-91 verdes; SVG sem erro no CT-B01, 2026-09-29

## 12. `docs/{pt,en}/recursos/trilhas-de-infraestrutura.md`: DG-12 (RQ-23)
- [x] DG-12, mapa do `/infra`: tela, fonte e quem grava *(alterado em 2026-09-29: com as telas de cadastro de comandos e de pacotes)* — marcador na linha 29; CT-25, CT-26, CT-62, CT-99, CT-100 verdes, 2026-09-29

## 13. `docs/{pt,en}/recursos/configuracoes-do-kit.md`: DG-14 (RQ-23)
- [x] DG-14, de onde vem a configuração, em "## Quem manda: o banco ou o `.env`?" — seção na linha 184, marcador na 192; CT-29, CT-92 verdes, 2026-09-29

## 14. Teste-guarda: `tests/Kit/DiagramasDaArquiteturaTest.php` (RQ-10, RQ-26)
- [x] Arquivo com sentinela no arquivo inteiro (`naArvoreDoKit()`) — CT-05 verde; `vendor/bin/pest tests/Kit/DiagramasDaArquiteturaTest.php --compact --no-tia` no ciclo 2: 441 casos, 439 verdes, 3.157 asserções (as 2 falhas são do CT-132, `## Testes`), 2026-09-29
- [x] Marcador `%% DG-xx` em todo bloco do catálogo — `grep -rn "%% DG-" README.md README.en.md docs/` acha 42 marcadores (DG-01 a DG-20 nas duas árvores e o DG-01 nos dois READMEs), 2026-09-29
- [x] Camada 1: `accTitle`/`accDescr`, sem tema nem cor fixos, lista branca de tipo, DG-01 idêntico nos dois lugares — CT-01, CT-02, CT-34, CT-35, CT-36, CT-95, CT-103 verdes, 2026-09-29
- [x] Camada 2: o fato do código por diagrama, em pt e en *(alterado em 2026-09-29: os testes levam os IDs do `04`, não `[DG-xx]`; desvio D-04)* — `bash .ai/skills/feature-wiki/scripts/ids-ct.sh {wiki} …` sobre os seis arquivos de teste da feature e o conferidor do site (os sete padrões estão, escritos, na linha do `ids-ct.sh` da `## Verificação Final`): só a linha do CT-39, que não tem arquivo de teste por desenho, 2026-09-29 *(e, desde a R47 do step 10, a do CT-105, cujo teste falta — DV-09)* *(alterado em 2026-09-29: o teste do CT-105 existe; no ciclo 2, com o `tests/Kit/GuardasDosDiagramasTest.php` entre os arquivos, a saída é só a linha do CT-39)*
- [x] Guardas do DG-17, DG-18 e DG-19 *(alterado em 2026-09-29: moram em `tests/Kit/DiagramasDaArquiteturaTest.php`, não em `DuasRotasDeEntregaTest`/`MysqlNoDockerTest`; desvio D-04)* — CT-31, CT-32, CT-72, CT-93 (DG-17), CT-33, CT-75, CT-84 (DG-18), CT-43, CT-76 (DG-19) verdes; `git diff --stat origin/main...HEAD -- tests/Kit/DuasRotasDeEntregaTest.php` vazio, 2026-09-29
- [x] Irmão com tenancy: `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` *(alterado em 2026-09-29: também lê bloco, do DG-02, do DG-03 e do DG-09)* — no ciclo 2, 45/45, 320 asserções (CT-112..CT-114 entraram na segunda passada do step 10), 2026-09-29

## 15. `docs/{pt,en}/comecar/instalacao-avancada.md`: DG-15, DG-18 (RQ-24)
- [x] DG-15, a instalação por dentro, como seção final "## Como a instalação acontece por dentro" — seção na linha 276, marcador na 294; CT-30, CT-96 verdes, 2026-09-29
- [x] DG-18, containers por profile *(alterado em 2026-09-29: numa seção própria, "## Os containers por profile", entre "A aplicação containerizada e o banco" e "## Comandos")* — seção na 165, marcador na 178; CT-33, CT-75, CT-84 verdes e `tests/Kit/MysqlNoDockerTest.php` 28/28 (o CT-05 dele pegou o texto do DG-18 na rodada 2, commit `821d357`), 2026-09-29

## 16. `docs/{pt,en}/comecar/atualizando-o-projeto.md`: DG-16, DG-17 (RQ-24)
- [x] DG-16, fluxo do `kit:update`, dentro de "## O jeito fácil" — seção na linha 18, marcador na 44; CT-32 verde, 2026-09-29
- [x] DG-17, as duas rotas de entrega, antes de "## O jeito manual" — marcador na 169, "## O jeito manual" na 197; CT-31, CT-72, CT-93 verdes; `tests/Kit/DuasRotasDeEntregaTest.php` 3/3, 2026-09-29

## 17. `docs/{pt,en}/recursos/multi-tenancy.md`: DG-20 (RQ-25)
- [x] DG-20, a requisição em `/app/{tenant}` (`sequenceDiagram`) *(alterado em 2026-09-29: o flowchart do `kit:tenancy` não foi desenhado, e a ordem dele ficou em prosa; achado A-02, desvio D-05)* — marcador na linha 144; `hasTenancy()` conferido no irmão com tenancy; citações da prosa conferidas pelo `tests/Kit/CitacoesDeCodigoTest.php` 3/3, 2026-09-29

## 18. `docs/{pt,en}/operacao/desenvolvendo-o-kit.md`: DG-19 (RQ-25)
- [x] DG-19, segundo plano, como seção final "## O que roda em segundo plano" *(alterado em 2026-09-29: só a metade do `composer dev` tem guarda; achado A-03, desvio D-05)* — seção na linha 95, marcador na 112; CT-43, CT-76 verdes, 2026-09-29

## 19. `KitArte.php`: um GIF por clipe (`CLIPES`) (RQ-27)
- [x] `CLIPES` como mapa `clipe => quadros` com `fluxo-import-export`, `densidade`, `busca-spotlight`, `login-unificado` e `install` *(alterado em 2026-09-29: `QUADROS_DO_GIF` ficou, dentro do mapa; desvio D-06)* — `app/Console/Commands/KitArte.php:CLIPES:70`; CT-46, CT-50, CT-64 verdes em `tests/Kit/KitArteTest.php` (28/28), 2026-09-29
- [x] `montarGif()` itera os clipes e produz `art/{clipe}.gif` — `ls -l art/*.gif`: `busca-spotlight.gif` 60.692, `densidade.gif` 132.112, `fluxo-import-export.gif` 108.568, `install.gif` 116.347, `login-unificado.gif` 159.179 bytes, 2026-09-29
- [x] `IMAGENS` não muda; os dois órfãos seguem ignorados (dívida DV-08) — `git diff origin/main...HEAD -- app/Console/Commands/KitArte.php` não acrescenta nome a `IMAGENS` (as linhas de `densidade-*` somadas são do `CLIPES`), 2026-09-29
- [x] `publicar()` confere `IMAGENS` primeiro e só depois pula o quadro de clipe — `app/Console/Commands/KitArte.php:IMAGENS:187` antes de `app/Console/Commands/KitArte.php:$quadrosDeClipe:206`; CT-48 verde, 2026-09-29
- [x] A montagem não trunca o GIF publicado *(alterado em 2026-09-29: temporário ao lado do publicado, `rename()` no mesmo diretório, `-f gif`, `Throwable` por clipe; passos 22, 24 e 25)* — CT-49, CT-65 e os testes `[RD2-02/RD2-03]`, `[RD3-09]` verdes; o timeout real e a atomicidade entre volumes não são falsificáveis aqui (dívida DV-07), 2026-09-29

## 20. Capturas novas para `busca-spotlight` e `login-unificado` (RQ-27)
- [x] Cenário `busca-spotlight` *(alterado em 2026-09-29: em `/app/{organização}/projetos`, com o resultado afirmado dentro do overlay)* — `KIT_ART=1 vendor/bin/pest tests/BrowserTenancy/CapturaDeArteTest.php --filter="CT-B03" --no-tia`: 2/2, 10 asserções, 37 s; `busca-spotlight-2-aberta.png` aberto e conferido no olho, 2026-09-29
- [x] Cenário `login-unificado`, com `ligarLoginUnificado()` e uma conta com dois painéis — mesma execução; `login-unificado-2-escolha.png` mostra os cartões Administração e Infraestrutura, 2026-09-29

## 21. Correção do `art/install.gif` (RQ-28)
- [x] Fixture `tests/Browser/Fixtures/terminal-instalacao.blade.php` com a transcrição real e a senha mascarada *(alterado em 2026-09-29: saída de um `create-project` da v0.41.1; máscara de 24 `X`)* — `grep -n -i "password" tests/Browser/Fixtures/terminal-instalacao.blade.php` só acha o comentário que proíbe a palavra; CT-52 verde, 2026-09-29
- [x] Cenário de captura com os quadros da instalação *(alterado em 2026-09-29: quatro quadros, viewport 1400x2100, rota registrada só no teste)* — `tests/BrowserTenancy/CapturaDeArteTest.php:'instalacao-1-inicio':805-827`, com `assertDontSee('/ password')` e `assertDontSee('password (padrão do kit)')` antes de cada quadro, 2026-09-29
- [x] `CLIPES` com `install`, e o `art/install.gif` refeito — `art/install.gif` 116.347 bytes (era 347.561 na `origin/main`, `git diff --stat`), commit `2ab9bfa`; `[CT-11]` herdado verde, 2026-09-29

## 22. Terceira rodada da revisão do diff (Adendo 3: RQ-31 a RQ-35)
- [x] O DG-11 renderiza (RQ-32) — commit `f39dd18`; CT-B01: `pt: claro=20 escuro=20 (fonte=20); en: claro=20 escuro=20 (fonte=20)`, exit 0, 2026-09-29
- [x] Contraste AA do rótulo de aresta no tema escuro, na CSS do site (RQ-33) *(alterado em 2026-09-29: em `site/src/styles/kit.css`, não na config do `astro-mermaid`; desvio D-07)* — commit `b2991c9`; CT-B01 sem `color-contrast` serious; CT-B02 com as cores medidas na troca de tema, 2026-09-29
- [x] Extrator de arestas normalizado e guardas lendo o conteúdo do bloco (RQ-34, RQ-35) — commit `8a3974e`; `tests/Kit/DiagramasDaArquiteturaTest.php` verde na rodada 3 (no ciclo 2, 439/441, com o CT-132 fora — `## Testes`), 2026-09-29
- [x] Correções do instalador e do `kit:arte` desta rodada — commits `db345fb` e `12af492`; `tests/Kit/CustomizadorDaInstalacaoTest.php` 73/73, `tests/Kit/KitArteTest.php` 28/28; provado vermelho com o `app/` de antes (7 falhas no Customizador, 1 no KitArte, conferente #35), 2026-09-29

## 23. Job `site` no CI de pull request (RQ-36)
- [x] Job `site` no `.github/workflows/ci.yml`, só em PR que toca `docs/` ou `site/`, com `permissions: contents: read` — commits `a86a6eb` e `5ff5227`; `tests/Kit/AcoesPinadasPorShaTest.php` 2/2, 2026-09-29
- [x] CT para o job `site`: nenhum teste lê o `ci.yml` atrás dele (`grep -rln "ci.yml" tests/` acha seis arquivos, nenhum sobre o job); ~~lacuna declarada no `04` e dívida DV-08~~ o cenário existe desde a reconferência do step 10 — CT-105, R47 do `04`, derivado do `00` —, e falta o teste: dívida DV-09 (a DV-08 é a das capturas órfãs; a referência estava trocada) — fechado pelo CT-105 (`tests/Kit/DiagramasDaArquiteturaTest.php:'[CT-105] o PR que toca docs/ ou site/':5035`), 1/1 verde, 14 asserções; os mutantes M1..M6 da R47 vermelhos sobre cópias em memória do `ci.yml`, 2026-09-29

## 24. Quarta rodada da revisão do diff (Adendo 4: RQ-37 a RQ-40)
- [x] RD3-01..RD3-12 fechados ou reduzidos, e o que a revisão cega achou virou dívida — commits `697a75b`, `83219ff`, `8a6e9e1`, `5ff5227`; situação de cada RD3 em `## Revisão do Diff (step 9)`; os 5 CTs novos do instalador 0 verdes com o `app/` de antes e 5/5 com ele (conferente #43), 2026-09-29

## 25. As exceções do Adendo 5 (RQ-41 a RQ-44)
- [x] RD4-01, o `kit:arte` volta a montar o GIF com o ffmpeg real — commit `8a6e9e1`; 8 falhas com o ffmpeg de teste novo contra o `KitArte` sem `-f gif`, 28/28 com ele; ffmpeg 8.1 real com os argumentos exatos: exit 0 e `GIF89a` (sessão, sem despacho), 2026-09-29
- [x] RD4-03, o fato do DG-03 lê o rótulo, em pt e em en — commit `697a75b`; vermelho com a troca de rótulos em cada idioma separadamente (sessão), 2026-09-29
- [x] RD4-04 (Pint) e RD4-06 (citações) — `vendor/bin/pint --test --format agent`: `{"result":"passed"}`; `tests/Kit/CitacoesDeCodigoTest.php` 3/3, 2026-09-29
- [x] RD4-02, 05, 07, 08, 09 e 10 declarados como dívida, com RD4-02 e RD4-10 em destaque — `## Dívidas declaradas`, abaixo, 2026-09-29

## 26. Ciclo 2 do quality gate (Adendo 6: RQ-45 a RQ-49)

*(alterado em 2026-09-29: seção nova, do step 10 do ciclo 2 — um checkbox por achado do `06`; o que o código
mudou está no passo 26 do `01`, e o destino de cada achado, em `## Quality Gate`)*

- [x] QA-01 — o `01`, o `02`, o `04`, o CHANGELOG e a frase da página dizem o que o código faz hoje — `01`: passos 5, 14, 17, 18 e 23 com a marca desta passada, passo 26 novo, Cobertura com RQ-45..RQ-49, `## Testes` e `## Commits`; `02`: ADR-02, ADR-05, ADR-06 e ADR-08; `04`: cabeçalho, costura dos extras em `tests/Kit/GuardasDosDiagramasTest.php`, `## Índice de Cenários` de CT-105..CT-132, Q?2 e a linha da RQ-36; CHANGELOG com 441, 47 e 45 casos e o `install.gif` de 116.347 bytes; `sed -n 7p docs/pt/referencia/arquitetura-em-diagramas.md` (e o par en) nomeia os três arquivos de guarda (lote C1), 2026-09-29
- [x] QA-02 — cada número deste `03` saiu de comando — `## Testes` e `## Verificação Final` com a saída de `XDEBUG_MODE=off vendor/bin/pest {arquivo} --compact --no-tia` de cada arquivo, em série; o A-04 saiu de `## Blockers` e da `## Verificação Final` (está fechado em `## Achados do step 10`); `grep -rn "411\|35/35" {wiki}/` só acha o `06`, que relata o ciclo 1, e esta linha, que cita o comando, 2026-09-29
- [x] QA-03 — ~~**parcial**~~ **fechado em 2026-09-30**: os seis testes que só tinham o ID ganharam as asserções do cenário — CT-117 (8 linhas: barra no fim, barra seguida de `n`, `${APP_ENV}`, quebra LF/CRLF/CR), CT-119 (3 `it()`, com a linha ¬G ∧ ¬S restrita a senha não utilizável, ADV2-19), CT-120 (dataset G ∧ S, ¬G ∧ S, ¬G ∧ ¬S ×2), CT-121 e CT-122 (banner e linha, sem acento e sem caixa), CT-127 ("a saída nomeia o clipe") — e o `it('[CT-B04][CT-B10] captura os quadros do instalador')` faz os passos 5 e 7 (`assertScript` da `.janela` e md5 de cada PNG); `tests/Kit/CustomizadorDaInstalacaoTest.php` 115/115, `tests/Kit/ResumoDoKitInstallTest.php` 10/10, `tests/Kit/KitArteTest.php` 29/29, CT-B04/CT-B10 1/1 com 27 asserções (`## Testes`). Feito na passada anterior: a P-44 no `00` (`10bf1ba`), os 14 `[RD…][CT-nn]`, o `ids-ct.sh` só com o CT-39
- [x] QA-04 — o opcional leva a chave no próprio elemento, em pt e en — DG-02 (`cu_aceitar_convite` com "recusar exige KIT_TENANCY"), DG-07 (o vínculo "quando ele tem tenant_id" e o `else recusa (exige KIT_TENANCY)`) e DG-08 (`note right of Pendente` com `KIT_REGISTRO_APROVACAO_MANUAL`), lote C1; CT-129 e CT-130 verdes no `DiagramasDaArquiteturaTest` (439/441, falhas só no CT-132), 2026-09-29
- [x] QA-05 — a paridade lê o publicado com o extrator de `tests/Pest.php` — CT-03, CT-85 e CT-126 verdes no mesmo arquivo; `tests/Pest.php:mensagensDeSequencia:1511` lê as doze formas de seta do CT-126, 2026-09-29
- [x] QA-06 — nenhum diagrama do site abaixo de 12 px — `npm run build` (69 páginas) e `node verifica-acessibilidade.mjs 4399` contra o `astro preview`, exit 0: `[CT-B05] fonte efetiva minima` 14,0 px (DG-13), 20 SVGs, em pt 1280×900 claro e escuro, en 1280×900 claro, pt 390×844 claro e en 390×844 escuro; `[CT-B06] rolagem no bloco - celular (390x844): 40 diagramas maiores que a coluna, pagina sem rolagem: true`; o CSS está em `site/src/styles/kit.css` (lote C2), e nenhum bloco mudou, 2026-09-29 *(alterado em 2026-09-30: na execução de 2026-09-30 a mesma linha diz **60** diagramas — o conferidor passou a contar por combinação de janela e tema depois de CT-B07..CT-B11; QA-16)*
- [x] QA-07 — cada helper uma vez, em `tests/Pest.php` — `grep -rn "function transicoesDoEstado\|function mensagensDaSequencia\|function ordemDoDG20EstaCorreta" tests/` vazio (exit 1); os três usos passam por `tests/Pest.php:mensagensDeSequencia:1511`, `tests/Pest.php:ordemDg20EhCorreta:1552` e `tests/Pest.php:transicoesDeEstado:1619` (lote T1), 2026-09-29
- [x] QA-08 — as frases erradas das docs — `grep -n "Assistente de IA na sessão\|AI assistant in the session\|kit:update — relatório\|kit:update — the report" docs/{pt,en}/referencia/arquitetura-em-diagramas.md` vazio, e o índice usa o `accTitle` (CT-131 verde); `grep -n "restaurar()" docs/{pt,en}/autenticacao/estados-de-usuario.md` vazio (a prosa diz `restore()`, do `SoftDeletes`); `docs/pt/recursos/trilhas-de-infraestrutura.md` diz "o health não é o único agendado" e lista as podas (lote C1), 2026-09-29
- [x] QA-09 — ~~**parcial**~~ **fechado**: as duas linhas de cópia do CT-132 aplicam a alteração ao bloco publicado desde `252826c`; `XDEBUG_MODE=off vendor/bin/pest tests/Kit/DiagramasDaArquiteturaTest.php --filter="CT-132" --compact --no-tia`: 7/7, 17 asserções, 2026-09-30; e `grep -n "conta nova\|conta existente\|escolha de painel" docs/en/autenticacao/convites.md docs/en/autenticacao/login-unificado.md` vazio (lote C1)
- [x] QA-10 — ~~só a primeira ocorrência da chave é trocada~~ *(alterado em 2026-09-30: desde o Adendo 7 o CT-118 afirma toda linha ativa e nenhuma comentada, e passa 115/115 com o `definirLinhaNoEnv()` — `## 27.`; o mutante do limite é o R54.M4, QA-16)* — `[CT-118]` (`tests/Kit/CustomizadorDaInstalacaoTest.php:'[CT-118]':658`), 2 linhas de Exemplo, verde no `CustomizadorDaInstalacaoTest` 75/75, 2026-09-29
- [x] QA-11 — ~~**parcial**~~ **fechado**: a decisão da entrega foi "o glossário viaja pelo `kit:update`" — `wikis/glossario.md` em `KitUpdate::CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:'wikis/glossario.md':315`, commit `ef68f6d`), e o README diz 12 documentos de referência; `tests/Kit/KitUpdateTest.php` e o `[CT-48]` de `tests/Kit/SiteDeDocumentacaoTest.php` verdes na suíte desta passada (`## Verificação Final`), 2026-09-30
- [x] QA-12 — nota para a skill — `## Notas de Implementação` e a DV-11 em `## Dívidas declaradas`, com destino na `feature-wiki` (`pestw.cmd`), 2026-09-29
- [x] QA-13, acentos do DG-19 pt — `grep -n "retencao\|papeis" docs/pt/operacao/desenvolvendo-o-kit.md` vazio (exit 1), lote C1, 2026-09-29
- [ ] QA-13, quadros do `install.gif` — **parcial, cosmético**. Feito: a altura da janela é medida no quadro final (`tests/BrowserTenancy/CapturaDeArteTest.php:it:794`, lote C3), o GIF foi remontado (1000 × 1484, 4 quadros, 6,67 s, 116.347 bytes) e cada quadro é conferido pelo CT-B10 (o marcador dele à vista, o do seguinte fora). **Fica**: os quadros 1 a 3 continuam com área vazia — os quatro saem com a mesma altura, porque o `palettegen`/`paletteuse` do `kit:arte` descarta quadros de tamanho diferente —, e a linha `WARN` do terminal do Windows segue no último quadro, porque é da transcrição real da fixture. Não reprova (Adendo 6: cosmético); destino: decisão do mantenedor, no PR
- [x] QA-14 — coluna `Custo` em toda tabela de `## Despachos` — #1–#28 ganharam a coluna, com o workflow de cada linha e "não reportado" onde o host não registrou; #45–#57 trocaram "Onde" por "Custo (workflow)"; o quadro deste ciclo nasce com ela, 2026-09-29
- [ ] RQ-46 — o ciclo 2 do quality gate, cego como o 1: é o step 11, depois deste

## 27. Adendo 7 e a revisão adversarial da adição (RQ-50 a RQ-52, RQ-45; passo 27 do `01`)

*(alterado em 2026-09-30: seção nova — o que o Adendo 7 e as duas rodadas da revisão adversarial da adição do step 11
mudaram no código, nos testes e nas docs; um checkbox por frente, com a prova vermelha antes da correção onde o destino
era teste)*

- [x] Q?9, Q?10 e Q?11 levadas ao `00` como P-45, P-46 e P-47 (`## Ambiguidades e Perguntas Abertas`); RQ-50 e RQ-28 ficam **abertas em parte** — a direção sem cenário, o invariante como CT-139, CT-134 e CT-136 — `grep -n "P-4[567]" {wiki}/00-requisito.md` = 3 linhas, 2026-09-30
- [x] Revisão adversarial da adição, rodada 1 (`fw-adversario-ct`, cega): 39 achados, fechados pela `feature-test-design` no `04`/`05` — CT-133..CT-148, CT-B07..CT-B11, R63..R65 (workflow da passada anterior, `1305e50` + árvore de 2026-09-30 01:03); rodada 2 (despacho #72): 24 achados, 23 aceitos e 1 rejeitado (ADV2-23), fechados pelo analista (#73) — CT-149, CT-150, linhas novas em CT-133, CT-134, CT-136, CT-137, CT-139..CT-146, CT-148, CT-71, CT-98 e CT-B05; teto de 2 rodadas atingido, sem terceira. Cabeçalho do `04`, pelos comandos do comentário: **150 cenários · 65 regras · 427 mutantes · 5 sem matador**; `05`: **11 CT-B · 36 mutantes**, 2026-09-30
- [x] **Teste primeiro** (RQ-45), com a saída literal do executor contra o código de antes — CT-118 (linhas "comentada antes" e "2 ativas": `o .env deveria ter 1 linha(s) ativa(s) de APP_NAME - achei: ["APP_NAME=\"Novo\"","APP_NAME=\"Antigo\"]`; `-'APP_NAME="Novo"' +'APP_NAME="Repetida"'`), CT-137 (5 das 8 linhas: `export`, espaço no `=`, `#APP_NAME=` antes da ativa, linha em branco, 2 ativas), CT-138 (7 linhas, todos os gravadores: `-'Novo' +'Repetida'`, `-'pgsql' +'mysql'`, `-'http://novo.test' +'http://antigo.test'`, `-'true' +'false'`…): **14 vermelhos por causa (b)**, `tests/Kit/CustomizadorDaInstalacaoTest.php` 92/106 (despacho #66); CT-144 (DG-06 pt e en sem chave de provedor, DG-08 com uma das duas chaves) e CT-146 (DG-06 en com "(provedor social)"): `DiagramasDaArquiteturaTest` 491/496 (#69); CT-129 (DG-07 pt e en, sem `KIT_TENANCY` no escopo e o `tenant_id` no meio da mensagem): 523/525 (#78). CT-117, CT-139, CT-140..CT-143, CT-145, CT-147, CT-148 e os CT-B novos nasceram verdes contra o código de hoje, como o `04` previa
- [x] **RQ-50 e RQ-51 no código** — `app/Support/SubstituicaoEmArquivo.php:definirLinhaNoEnv` troca toda linha que o Dotenv lê como a chave e nenhuma comentada, descomenta no lugar sem linha ativa (P-45) e anexa sem nenhuma, para todo chamador (`definirNoEnv()`, `aplicarBanco()`, `AtivadorDeTenancy::escreverEnv()`, `KitTenancy::semearDemo()`; `aplicar()` fica com o limite 1 para config PHP) — sessão, em linha (exceção: correção de um método com os testes já vermelhos); depois dela `CustomizadorDaInstalacaoTest` 106/106 → 115/115 com a segunda leva, `MysqlNoDockerTest` 28/28, `HostLocalTest`, `TenancyNaInstalacaoTest`, `SenhaDoAdministrador*` verdes (206/207 no lote, a falha era citação — abaixo), `SchemaDaTenancyTest` + `TenancyTest` + `KitInfoTest` + `BoasVindasTest` + `CacheDeViewsNoDockerTest` 113/113; `composer types:check` `{"tool":"phpstan","result":"passed","errors":0}`, 2026-09-30
- [x] **RQ-52** — a saída já nomeava a publicação (RD2-04/RD3-09); CT-128 e CT-147 afirmam que nenhuma linha da saída culpa a montagem ou o ffmpeg (descontadas as de outro clipe declarado) — `tests/Kit/KitArteTest.php` 29/29, 78 asserções (#77), 2026-09-30
- [x] **Docs, pt e en** — DG-06: `note over provedor` com as quatro chaves de provedor e o en sem "(provedor social)"; DG-08: a nota do Pendente com `KIT_REGISTRO` e `KIT_REGISTRO_APROVACAO_MANUAL`; DG-07: `, se ele tem tenant_id (KIT_TENANCY)` nas duas mensagens do vínculo (construtores #74 e #80, depois do vermelho); `DiagramasDaArquiteturaTest` 525/525 e o CT-143 realinhado ao publicado (#82), 2026-09-30
- [x] **Guardas e conferidor** — extrator (`tests/Pest.php`: cadeia, `&`, bidirecional, oito meias-setas, `%%` e seta dentro de rótulo ignorados), detector de opcional por escopo estruturado e palavra inteira (`[CT-08]` apertado), baseline de tipo e `direction` (CT-148), pares de papéis em CT-71 e CT-98; `site/verifica-acessibilidade.mjs` com CT-B07..CT-B09, CT-B11 e as duas linhas "escuro desde o carregamento" do CT-B05: `node verifica-acessibilidade.mjs 4399` exit 0, `[CT-B05] fonte efetiva minima` 14,0 px (DG-13) nas 7 combinações, `[CT-B08] textos medidos - … foreignObject: 91, com <text>/<tspan>: 49, sem texto medido …: 0`, `[CT-B09] … 0 violacao(oes)`, `[CT-B11] … 0 SVG(s) abaixo de 12 px` ×2 (#70, #79), 2026-09-30
- [x] **L-16, a prova vermelha do CT-B05** — com `site/src/styles/kit.css` de `e597896^`, `npm run build` e o conferidor: **exit 1**, `[CT-B05] fonte efetiva minima - pt 1280x900 claro: 2.2 px (DG-11), 20 SVGs medidos`, 125 violações (88 `[CT-B05] abaixo do piso`, 36 `[CT-B11]`, 1 `[CT-B06] ancora de nao vacuo`), 16 dos 20 SVGs abaixo do piso em pt 1280×900; `git checkout -- site/src/styles/kit.css`, build, conferidor: exit 0 (#70), 2026-09-30
- [x] Citações e rastreabilidade — `citacoes.sh`: 139 na entrada (01: 21, 03: 11, 04: 102, 05: 1, 06: 4), 118 deslocadas corrigidas por script (símbolo mais próximo da linha citada), 23 fora do formato reescritas à mão, mais 32 após a última leva de testes: saída vazia, exit 0; `rastreabilidade.sh`: RQ-50..RQ-52 sem passo na entrada → passo 27 e três linhas na Cobertura do `01`: saída vazia, exit 0; o `CitacoesDeCodigoTest` `[CT-26]` acusou 7 citações das docs deslocadas pelo `ef68f6d` (`KitUpdate.php`) e por esta passada (`AtivadorDeTenancy.php`), corrigidas em `docs/{pt,en}/comecar/atualizando-o-projeto.md`, `docs/{pt,en}/recursos/multi-tenancy.md` e `tests/Kit/ChecklistDeReleaseTest.php`: 23/23 + 3 pulados *(alterado em 2026-09-30: eram 26 testes, 23 passaram e 3 pulados; QA-16)*, 2026-09-30
- [x] README e CHANGELOG — o badge passa a 1.835 casos (`[CT-50]`, `tests/Kit/SiteDeDocumentacaoTest.php` 68/68); o CHANGELOG ganha a entrada da RQ-50/RQ-51 em *Corrigido* e os números novos das guardas, 2026-09-30
- [ ] RQ-46 — o ciclo 2 do quality gate, cego: é o step 11, logo depois desta reconciliação (#83)

## 28. Ciclo 3 — os nove achados do ciclo 2 do gate (Adendo 8; QA-15 a QA-23)

*(alterado em 2026-09-30: seção nova — um checkbox por achado, com o comando ou o despacho que o fechou)*

- [x] QA-15 (especificação) — a Q?9 foi ao mantenedor com as duas opções e a recomendação; a resposta "continue" virou o **Adendo 8** (RQ-53: sem linha ativa, descomenta a primeira comentada no lugar; fidelidade baixa, por omissão), a P-45 e a L-13 fecharam, a RQ-50 deixou de estar aberta; RQ-28 segue aberta em parte (Q?10/Q?11), marcada na Cobertura do `01`; `## Tickets` diz 2 perguntas abertas; a coluna `Estado` não entrou no `00` (as tabelas dos Adendos não a têm — a marca fica nas premissas e na Cobertura) — `grep -c "Adendo 8" {wiki}/00-requisito.md` = 1; `rastreabilidade.sh` vazio, exit 0 (na entrada do ciclo 3 acusava "RQ-53 sem CT", fechado pela Origem de R64), 2026-09-30
- [x] QA-16 (especificação) — `115.928` → `116.347` no `01` e no `03` (`grep -rn "115.928" {wiki}/` vazio); o passo 26 do `01` e a linha do QA-10 do `## 26.` dizem "toda linha ativa" com a marca; `ChecklistDeRelease 23/23 + 3 pulados`; a linha do custo diz que o fechamento do ciclo 2 toca `app/` (4 arquivos, 96 linhas); os `grep -c "alterado em 2026-09-29"` refeitos (04: 181) e os de 2026-09-30 (04: 131, 05: 24); o "40 diagramas" do CT-B06 anotado com os 60 de hoje; o cabeçalho do `04` diz P-01..P-47, 2026-09-30
- [x] QA-17 (implementação) — `semAcentoESemCaixa()` uma vez em `tests/Pest.php`; `textoSemAcentoESemCaixa()` e `normalizadoSemAcentoESemCaixa()` apagadas (`grep -rn "mb_strtolower(Str::ascii" tests/` só acha `tests/Pest.php`); `ResumoDoKitInstallTest` + `CustomizadorDaInstalacaoTest` + `HelpersDeTesteTest` 126/126 logo depois da troca; a linha da `testes.md` em `## Conformidade com Rules` ganhou a nota, 2026-09-30
- [x] QA-18 (teste → implementação) — R58/CT-129 com as linhas das `accDescr` do DG-08 (as duas chaves) e do DG-09 (`KIT_TENANCY`), R58.M9/M10 (despacho #84); **nasceram vermelhas** em pt e en (#86: `DG-08 (pt, docs/pt/autenticacao/estados-de-usuario.md:19): … sem KIT_REGISTRO e KIT_REGISTRO_APROVACAO_MANUAL no escopo "accDescr: Pendente aprova para Ativo…"`, e a do DG-09 idem); corrigidas pelo construtor #87 ("Pendente (só existe com KIT_REGISTRO e KIT_REGISTRO_APROVACAO_MANUAL) aprova…", "Recusado (só com KIT_TENANCY)", pt e en); `DiagramasDaArquiteturaTest` 527/527, 2026-09-30
- [x] QA-19 (teste) — premissa, não cenário: **P-48** no `00` (valor multilinha entre aspas não é suportado pela gravação do kit; o kit nunca o produz — CT-117), lacuna **L-21** no `04` com a sonda do gate como reprodução, Q?14 registrada; dívida **DV-12** abaixo, 2026-09-30
- [x] QA-20 (teste) — **CT-151** (R64, tabela de decisão arquivo × ativa × comentada com o retorno; R64.M8..M12) em `tests/Kit/CustomizadorDaInstalacaoTest.php:'[CT-151]':924`, 5 linhas verdes; prova pelo arnês do gate (`php -d auto_prepend_file=…/prepend.php vendor/bin/pest tests/Kit/CustomizadorDaInstalacaoTest.php --mutate --covered-only --everything --path=app/Support/SubstituicaoEmArquivo.php`): antes 11 sobreviventes, **86,25 %**; depois 6, **92,68 %** — morreram `:133 IncrementInteger`, `:135 TrueToFalse`, `:140 TrueToFalse`, `:138 ConcatRemoveRight`, `:138 ConcatSwitchSides`; ficam os dois `RemoveStringCast` (equivalentes, declarados) e os quatro de `aplicar()` (`:60`, `:62`), fora do fechamento (#85). A Q?15 (o retorno é "a gravação aconteceu") decidida pela sessão; docblock alinhado sem mudar código, 2026-09-30
- [x] QA-21 (especificação) — 64 citações `arquivo:it:N` que caíam em linha sem `it(` recitadas pelo ID (`arquivo:'[CT-nnn]':linha`) por script (o `it()` do CT nomeado na mesma linha da wiki, o mais próximo), mais as 5 de forma do `06` (só citações); o `cita.py` da sessão passou a buscar o símbolo sem as aspas; `citacoes.sh` vazio, exit 0. Nota para a skill: o `citacoes.sh` aceita `it` por substring (fica na `## Notas de Implementação`), 2026-09-30
- [x] QA-22 (especificação) — as 11 citações curtas de `docs/{pt,en}/comecar/atualizando-o-projeto.md` realinhadas ao `KitUpdate.php` de hoje por script (função mais próxima da linha citada); `CitacoesDeCodigoTest` 3/3, 2026-09-30
- [x] QA-23 (especificação) — `wikis/glossario.md` com "Linha ativa (do `.env`)", "Linha comentada (do `.env`)" e "Pendente" (conta × convite), com *Não confundir com*; `SiteDeDocumentacaoTest` verde (68/68, `[CT-48]` com 12 documentos de referência), 2026-09-30
- [x] Badge do README — 1.836 (o CT-151), `[CT-50]` verde; `git diff --stat` do ciclo 3: só testes, docs, wiki e o docblock de `SubstituicaoEmArquivo`, 2026-09-30
- [ ] O ciclo 3 do quality gate, cego e último (#89): **pendente** — o app servido foi encerrado pelo host por falta de memória antes do despacho; roda quando o mantenedor liberar memória e pedir

## Testes
<!-- Um arquivo de teste por linha, com os IDs que ele cobre (step 7, reconciliado no step 10). -->
*(alterado em 2026-09-29: step 10 do ciclo 2 — os IDs saem de `grep -E "^\s*(it|test)\(\x27\[" {arquivo} | grep -oE "\[CT-[0-9]+\]" | sort -V -u`, e as contagens, de `XDEBUG_MODE=off vendor/bin/pest {arquivo} --compact --no-tia`, um arquivo por vez, em série; os IDs de outras wikis que colidem no mesmo arquivo ficam de fora da lista)*
- [x] `tests/Kit/DiagramasDaArquiteturaTest.php` (109 IDs: CT-01 a CT-11, CT-13, CT-15 a CT-38, CT-40, CT-42 a CT-45, CT-51 a CT-63, CT-66 a CT-79, CT-81 a CT-88, CT-90 a CT-97, CT-99 a CT-105, CT-124 a CT-126, CT-129 a CT-132, CT-140 a CT-146, CT-148) — **525/525**, 3.831 asserções, 108,5 s (era 439/441 na entrada: o CT-132 fechou em `252826c`; +84 casos desta passada) *(alterado em 2026-09-30)* *(ciclo 3: 527/527, 3.841 asserções, com as duas linhas das `accDescr` no CT-129)*
- [x] `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` (CT-12, CT-14, CT-80, CT-89, CT-98, CT-112 a CT-114) — 50/50 (era 45/45; +5 linhas de CT-98, ADV2-24), com `HelpersDeTesteTest` 1/1 e `GuardasDosDiagramasTest` 47/47: 98/98, 723 asserções, 36,1 s *(alterado em 2026-09-30)*
- [x] `tests/Kit/GuardasDosDiagramasTest.php` (CT-106 a CT-111, CT-115, CT-116) — 47/47, 387 asserções, 4,7 s, 2026-09-29
- [x] `tests/Kit/KitArteTest.php` (CT-46 a CT-50, CT-64, CT-65, CT-127, CT-128, CT-147) — 29/29, 78 asserções, 8,5 s *(alterado em 2026-09-30)*
- [x] `tests/Kit/CustomizadorDaInstalacaoTest.php` (desta wiki: CT-41 em três `it()`, CT-117 a CT-120, CT-123, CT-137 a CT-139, CT-150, CT-151) — 120/120, 519 asserções (era 75/75; 115/115 antes do CT-151) *(alterado em 2026-09-30)*
- [x] `tests/Kit/ResumoDoKitInstallTest.php` (CT-121, CT-122, CT-133 a CT-136, CT-149) — 10/10, 127 asserções, 15,2 s (era 2/2) *(alterado em 2026-09-30)*
- [x] `tests/BrowserTenancy/CapturaDeArteTest.php` (CT-B03 em dois `it()`, CT-B04 e CT-B10 no mesmo `it()`) — `KIT_ART=1 XDEBUG_MODE=off vendor/bin/pest tests/BrowserTenancy/CapturaDeArteTest.php --filter="CT-B04|CT-B10" --no-tia --compact`: 1/1, 27 asserções, 10,9 s; os quatro PNG com md5 distintos (`85d326…`, `a24639…`, `faeaef…`, `ad456d…`), 2026-09-30 *(alterado em 2026-09-30)*
- [x] `site/verifica-acessibilidade.mjs` (CT-B01, CT-B02, CT-B05 a CT-B09, CT-B11) — `cd site && npm run build` (69 páginas) e `node verifica-acessibilidade.mjs 4399` contra o `npx astro preview --port 4399`: exit 0, 96 páginas, `[CT-B01] SVGs conferidos — pt: claro=20 escuro=20 (fonte=20); en: claro=20 escuro=20 (fonte=20)`, 2026-09-30 *(alterado em 2026-09-30)*
- [ ] CT-39: sem arquivo de teste por desenho (costura `diff`, conferida no quality gate); o `ids-ct.sh` o acusa. Conferência por comando neste step: `git diff --name-only origin/main...HEAD -- composer.json composer.lock package.json package-lock.json` vazio, e `git diff --name-only -- composer.json composer.lock package.json package-lock.json site/package.json site/package-lock.json` (a árvore do ciclo 2) também, 2026-09-29
- [x] CT-105 (R47, RQ-36): ~~cenário derivado no step 10, depois da reconferência mecânica; **o teste não existe**~~ *(alterado em 2026-09-29: existe)* — `tests/Kit/DiagramasDaArquiteturaTest.php:it:5597`, verde no arquivo acima; os mutantes M1..M6 da R47 vermelhos sobre cópias do `ci.yml` em memória (lote T1 do step 10), 2026-09-29

**`05` existe** e tem ~~três~~ ~~seis~~ onze CT-B *(alterado em 2026-09-30: CT-B07..CT-B11 da revisão adversarial da adição; `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-B[0-9]+\]' 05-casos-de-teste-browser.md` = 11)* *(alterado em 2026-09-29: CT-B04..CT-B06 do step 11; `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-B[0-9]+\]' 05-casos-de-teste-browser.md` = 6)* (o gate do `05` passou na derivação do `04`, contra o "N/A" do `01`).

## Tickets
Não fatiado — 2026-09-29: registrado no step 10 (a wiki é da 3.x, que não tinha o step 8 de fatiamento); ~~44 RQ no `00`, 105 CT *(104 da derivação e o CT-105 da reconferência do step 10)* e 3 CT-B~~ ~~49 RQ e 44 `P-nn` no `00`, 132 CT e 6 CT-B~~ ~~52 RQ e 47 `P-nn` no `00`, 150 CT e 11 CT-B~~ 53 RQ e 48 `P-nn` no `00`, 151 CT e 11 CT-B *(alterado em 2026-09-30: ciclo 3 — Adendo 8, P-48, CT-151; `grep -cE` = 151)* *(alterado em 2026-09-29: ciclo 2 do gate — `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md` = 132)* *(alterado em 2026-09-30: Adendo 7 e a revisão adversarial da adição — o mesmo `grep -cE` = 150; no `05`, 11)*, compactação: não, ~~0 perguntas de requisito abertas~~ 2 perguntas de requisito abertas (Q?10 e Q?11 → P-46, P-47; a Q?9 fechou pelo Adendo 8) *(alterado em 2026-09-30, QA-15)* — sem sinal; a implementação terminou numa sessão só

## Verificação Final
*(alterado em 2026-09-29: refeita no step 10 do ciclo 2 do gate — cada linha fecha com a saída do comando rodado nesta passada; o que não rodou agora diz por quê. A linha do A-04 saiu: ele está fechado em `## Achados do step 10`, e a suíte do ciclo 1 do gate passou sem ele)*
- [ ] `/ponytail:ponytail-review` no diff: não há registro de que tenha rodado sobre o diff final, e não rodou neste step
- [x] `vendor/bin/pint --test` (o `lint:check` do CI) — nesta passada `vendor/bin/pint --dirty --format agent`: `{"result":"passed"}` sobre todos os PHP alterados (os executores formataram cada arquivo de teste; a sessão, os de `app/`), 2026-09-30
- [x] `composer types:check` (PHPStan level 8) — `{"tool":"phpstan","result":"passed","errors":0}` com o `app/` desta passada (`SubstituicaoEmArquivo`, `CustomizadorDaInstalacao`, `AtivadorDeTenancy`, `KitTenancy`), 2026-09-30
- [x] Cada arquivo da feature rodado sozinho, `XDEBUG_MODE=off vendor/bin/pest {arquivo} --compact --no-tia`, em série — 2026-09-30: DiagramasDaArquitetura **525/525**; DiagramasDaArquiteturaTenancy 50/50; GuardasDosDiagramas 47/47; HelpersDeTeste 1/1; KitArte 29/29; CustomizadorDaInstalacao 115/115; ResumoDoKitInstall 10/10; MysqlNoDocker 28/28; SiteDeDocumentacao 68/68; KitUpdate verde; CitacoesDeCodigo 3/3; ChecklistDeRelease 23/23 + 3 pulados; HostLocal, TenancyNaInstalacao, SenhaDoAdministrador, SchemaDaTenancy, Tenancy, KitInfo, BoasVindas, CacheDeViewsNoDocker verdes (lotes de 207 e 113); `KIT_ART=1 … CapturaDeArteTest.php --filter="CT-B04|CT-B10"` 1/1. Reproduzido pela sessão em série, um arquivo por vez, depois de o mantenedor liberar memória (2026-09-30): DiagramasDaArquitetura 525/525 (3.831 asserções, 123,0 s); DiagramasDaArquiteturaTenancy 50/50 (335); GuardasDosDiagramas 47/47 (387); HelpersDeTeste 1/1; KitArte 29/29 (78); CustomizadorDaInstalacao 115/115 (500); ResumoDoKitInstall 10/10 (127); MysqlNoDocker 28/28; SiteDeDocumentacao 68/68 (254); KitUpdate 54/54; CitacoesDeCodigo 3/3; ChecklistDeRelease 23/23 + 3 pulados; HostLocal 77/77; TenancyNaInstalacao 3/3; CoberturaDeTestes 20/20; RedeDeDocumentacao 20/20; DuasRotasDeEntrega 3/3; AcoesPinadasPorSha 2/2 — 18 arquivos, 0 falhas (a primeira tentativa desta passada em série foi encerrada pelo host por falta de memória, junto com a suíte completa). **Ciclo 3**, os arquivos tocados de novo em série: CustomizadorDaInstalacao 120/120 (519 asserções); ResumoDoKitInstall 10/10 (127); DiagramasDaArquitetura 527/527 (3.841, 107,4 s); DiagramasDaArquiteturaTenancy 50/50 (335); GuardasDosDiagramas 47/47; HelpersDeTeste 1/1; SiteDeDocumentacao 68/68; KitArte 29/29; KitUpdate 54/54; CitacoesDeCodigo **vermelho na primeira passada** — o `pint` reordenou os `use` de `tests/Pest.php` e deslocou em +1 duas citações de comentário (`tests/Kit/KitArteTest.php` → `tests/Pest.php:kitConfigCom:583`, `tests/Kit/LinkDoPainelSemTenancyTest.php` → `tests/Pest.php:telasDoKit:227`), corrigidas: `{"tool":"pest","result":"passed","tests":3,"passed":3,"assertions":10,"duration_ms":1223}`
- [x] Suíte completa sem browser — `XDEBUG_MODE=off vendor/bin/pest --testsuite=Unit,Feature,Kit,Tenancy --compact --no-tia`, em série (nunca `--parallel`), 2026-09-30: **verde** — 3.796 testes, 3.793 passaram, 3 pulados, 0 falhas, 18.257 asserções, 1.686,8 s (28,1 min); `git status --porcelain` igual antes e depois (33 entradas). A primeira tentativa foi encerrada pelo host por falta de memória na máquina, e a segunda caiu com a sessão; esta é a terceira, com o log em arquivo. **Ciclo 3**, depois das correções: **não concluída** — o host encerrou a suíte (e o `php artisan serve` do gate) por falta de memória na máquina pela segunda vez em 2026-09-30; a suíte verde de antes do ciclo 3 vale para o `app/` (o ciclo 3 só muda um docblock nele), e os arquivos de teste tocados passaram em série acima; a reexecução completa espera o mantenedor (na entrada desta passada: 3.658 testes, 5 falhas — CT-132, `KitUpdateTest`, `[CT-48]`, `[CT-50]` — todas fechadas acima)
- [ ] `vendor/bin/pest --parallel --tia`: não se aplica como escrito. Com `--testsuite` o Pest avisa "TIA does not apply to partial runs"; sem filtro a execução inclui a suíte Browser, que não roda em `--parallel` (`.ai/rules/testes-browser.md`); e `vendor/bin/pest --tia` em série achou o grafo velho e começou a regravar frio. O impacto real ficou medido pela suíte sem browser acima, pelo CT-B03/CT-B04 e pelo conferidor do site
- [ ] `pest --mutate` nas classes do diff: não rodado nesta passada. O quality gate mediu o K2 no ciclo 1 pelo arnês próprio (`06`, dimensão K: `KitArte`, `CustomizadorDaInstalacao`, `SenhaDoAdministrador`, `SubstituicaoEmArquivo`, `KitInstall`); pelo `pestw.cmd` o score é falso no Windows (QA-12, DV-11). O mutante sobrevivente do diff (QA-10) ganhou o CT-118
- [x] Custo medido: n/a, a entrega não tem caminho de request novo — `git diff -U0 origin/main...HEAD -- app/ routes/ database/ | grep '^+' | grep -cE "DB::|->where\(|::query\(|->get\(\)|::find\(|->first\(\)"` fora de comentário = 0, e o ciclo 2 não toca `app/`, `routes/` nem `database/` (`git diff --stat` vazio) *(alterado em 2026-09-30: o fechamento do ciclo 2 toca `app/` — `SubstituicaoEmArquivo`, `CustomizadorDaInstalacao`, `AtivadorDeTenancy`, `KitTenancy`: 4 arquivos, 96 linhas, `git diff --stat 1305e50..HEAD -- app/`; nenhum request nem query nova, o mesmo `grep` acima dá 0; QA-16)*; o custo é de build do site, ~700 KB de JS só nas páginas com diagrama (`92857dc`), 2026-09-29
- [x] `/code-review high origin/main...HEAD` + passe de eixos (step 6.5 da 3.x, step 9 da 4.0.0), antes da reconciliação — quatro rodadas; cada achado fechado, rejeitado com motivo ou declarado como dívida em `## Revisão do Diff (step 9)`, 2026-09-29. O delta do ciclo 2 (testes, docs, CSS do site, arte) não passou pelo step 9: o Adendo 6 manda corrigir e rodar o gate de novo, e é o ciclo 2 do gate que o lê
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido — 5 ✅ e 3 ⚠️ (as lacunas L-03 e L-04, e o CT-B04 sem os passos 5 e 7 do roteiro dele), 2026-09-29
- [x] Desvios propagados ao `01`/`02`/`04`/`05`, marcados *(alterado em 2026-09-29: …)* — `grep -c "alterado em 2026-09-29" 01-plano-acao.md 02-decisoes-arquiteturais.md 04-casos-de-teste.md 05-casos-de-teste-browser.md`: 01-plano-acao.md:56, 02-decisoes-arquiteturais.md:15, 04-casos-de-teste.md:165, 05-casos-de-teste-browser.md:14 (eram 45, 11, 140 e 11 na entrada desta passada, já com a passada da `feature-test-design` do step 11), 2026-09-29 *(alterado em 2026-09-30: o mesmo `grep -c "alterado em 2026-09-29"` dá hoje 01: 56, 02: 15, 04: 181, 05: 14 — o Adendo 7 e o fechamento da revisão adversarial acrescentaram marcas datadas de 2026-09-29 ao `04`; e `grep -c "alterado em 2026-09-30"` dá 01: 6, 02: 1, 04: 131, 05: 24; QA-16)*
- [x] `rastreabilidade.sh {wiki}` silencioso — 2026-09-30: três linhas na entrada (RQ-50..RQ-52 sem passo), corrigidas na fonte (passo 27 e Cobertura do `01`); saída vazia, exit 0
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — saída vazia, exit 0, 2026-09-30 (na entrada desta passada, três linhas — os blockers fechados sem " — evidência" —, corrigidas)
- [x] Citações `arquivo:símbolo:linha` reverificadas — `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki}`: saída vazia, exit 0, 2026-09-30, depois de corrigir 139 + 32 (`## 27.`); na passada anterior, 51
- [x] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa — o mesmo `ids-ct.sh` sobre os oito arquivos: exit 1, uma linha só, `04-casos-de-teste.md:3015: CT-39 sem teste` (costura `diff`, por desenho), 2026-09-30; Dataset × Exemplos fecha: o `## Índice de Cenários` cita o `it()` de todo CT, e nenhum cenário pede mais do que o teste afirma (QA-03 fechado)
- [x] Rules casadas pelo diff com linha em `## Conformidade com Rules` — `conformidade-rules.sh {wiki} origin/main`: saída vazia, exit 0, 2026-09-30 (o `app.md` casa o `SubstituicaoEmArquivo`; nenhuma regra dele — papel, DTO, painel — é tocada)
- [x] Alocação a ticket: n/a, sem `07-tickets/` — `ls {wiki}/07-tickets` → "No such file or directory", 2026-09-29
- [x] Falsificabilidade dos CTs novos e reforçados — rodadas 1 a 4 e Adendo 5 como antes; ciclo 2 do gate, agora reproduzido nesta passada: CT-118, CT-137, CT-138 vermelhos (14 linhas) contra o `SubstituicaoEmArquivo` de antes, CT-144 e CT-146 contra as docs de antes, CT-129 contra o DG-07 de antes, CT-B05 vermelho com o `kit.css` de `e597896^` (L-16) — saídas literais em `## 27.`. **Não falsificável nesta pilha, guarda mantida, dívida declarada (DV-07)**: o timeout real de 60 s do `Process` e a atomicidade do `rename()` entre volumes, 2026-09-30
- [x] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final — docs: DG-06, DG-07 e DG-08 nos dois idiomas (`## 27.`), e as citações de `atualizando-o-projeto.md` e `multi-tenancy.md` (`[CT-26]` verde); CHANGELOG: a entrada da RQ-50/RQ-51 em *Corrigido* e as guardas com 525, 47 e 50 casos; README: o badge 1.835 (`[CT-50]`) e 12 documentos de referência (`[CT-48]`), `tests/Kit/SiteDeDocumentacaoTest.php` 68/68, 2026-09-30
- [x] Site conferido localmente, como o `pages.yml` e o job `site` fazem — `cd site && npm run build`: 69 páginas; `node verifica-acessibilidade.mjs 4399` com `npx astro preview --port 4399`: 96 páginas (68 no escuro, 28 no claro), 13 com diagrama por idioma, CT-B01, CT-B02, CT-B05..CT-B09 e CT-B11 verdes, exit 0; o preview encerrado (#79), 2026-09-30
- [ ] `git commit`: fica para o orquestrador (este step não commita)

## Revisão do Diff (step 9)

<!-- Um achado por linha, dos dois passes, confirmados e rejeitados, das quatro rodadas (step 6.5 da 3.x
     nas três primeiras; a rodada 3 começou na 3.5.1 e o rebase levou a 4.0.0, e a 4ª já rodou com os
     agentes fw-* da 4.0.0). A dimensão I do feature-quality-gate lê daqui o que o step 9 já cobriu.
     Fontes: os relatórios de cada rodada no scratchpad da sessão (impl/passo65-*.md, final-revisao2.md,
     rodada3-r3.md, rodada4-r4.md e os dos conferentes) e as mensagens dos commits. -->

**Roteamento.** As rodadas 1 a 3 rodaram no roteamento da 3.x: o achado virou CT (ou reforço de CT) no
teste e correção, com a prova vermelha antes — mas **o CT não nasceu no `04`** e nenhuma `P-nn` foi
escrita no `00`. Os achados sem `RQ` que os cubra e que mudam o que o kit promete — só o **RD2-08**
(valor com `\` no `.env`) — pediriam `P-nn` no roteamento da 4.0.0; o `00` não foi tocado neste step.
Os 14 testes com o ID do achado estão listados no `04` ("Testes nascidos na revisão do diff, sem CT").
*(alterado em 2026-09-29: no step 11 (QA-03) a P-44 entrou no `00`, a `feature-test-design` derivou um cenário para
cada um dos 14, e os `it()` levam hoje o `[CT-nn]` ao lado do ID do achado — o mapa está no `04`)*

| ID | Passe | Achado | Destino | `P-nn` / CT | Desfecho |
|---|---|---|---|---|---|
| RD-01 | eixos (rodada 1) | `KitArteTest` troca o `PATH` do processo e não restaura (Blocker) | teste (arnês) | — / CT-46..CT-49 (`afterEach`) | fechado em `dc833b6`: 31/31 com `DeployDockerLocalTest`, nenhum `kit_arte_*` sobra |
| RD-02 | eixos (rodada 1) | `DiagramasDaArquiteturaTenancyTest` sem sentinela, vai para o projeto instalado (Blocker) | teste + helper | — / CT-05 | fechado em `dc833b6`: árvore de `git archive` com os 35 casos da Tenancy de então pulados; `RedeDeDocumentacaoTest` varre `tests/Tenancy` |
| RD-03 | eixos (rodada 1) | guardas "o desenhado é o executado" não leem o desenhado (Major) | teste | — / 16 CTs reescritos | parcial: resíduos RD2-09..RD2-16 |
| RD-04 | eixos (rodada 1) | DG-02 liga `admin` a "Personificar usuário"; só o `master_global` pode (Major) | especificação (diagrama), guarda vermelha antes | — / CT-83 (RQ-21) | fechado em `a48c9b2` |
| RD-05 | eixos (rodada 1) | DG-01 com quatro arestas que o código não tem | idem | — / CT-10 (RQ-20) | fechado em `a48c9b2`; resíduo RD2-10 |
| RD-06 | eixos (rodada 1) | DG-13 com ponta obrigatória onde a chave é nula | idem | — / CT-63 (RQ-23) | fechado em `a48c9b2`; resíduo RD2-12 |
| RD-07 | eixos (rodada 1) | o resumo promete "gerada pelo instalador e impressa no fim" quando nada é gerado | teste → correção | — / CT-41 (RQ-28) | Repro A fechado em `0da4f78`; Repro B virou RD2-05 |
| RD-08 | eixos (rodada 1) | `CLIPES` declara três clipes que nenhum cenário produz | teste | — / CT-46, CT-50 (RQ-27) | fechado em `dc833b6`; resíduo RD2-14 |
| RD-09 | eixos (rodada 1) | `montarClipe`/`resolverFfmpeg` sem caminho de exceção | código | — / R32, R33 (RQ-27) | parcial em `0da4f78`: RD2-02..RD2-04 |
| RD-10 | eixos (rodada 1) | comentários e citações velhos no código novo | higiene | — | parcial: RD2-17 |
| RD-11 | eixos (rodada 1) | helper clonado com outro nome (`blocoDoCatalogo` × `blocoDoCatalogoNaArvore`) | teste (move) | — | fechado: um helper só, em `tests/Pest.php` |
| RD-12 | eixos (rodada 1) | "master_global sempre entra" na organização | especificação (diagrama) | — / CT-06 (RQ-21) | repro original fechado em `a48c9b2`; resíduo RD2-09 |
| CR-1 | `/code-review` (rodada 1) | = RD-01 | — | — | fechado com RD-01 |
| CR-2, CR-3 | `/code-review` (rodada 1) | = RD-08 (CT-50 só na lista velha; CT-46 passava pelo aviso) | — | — | fechado com RD-08 |
| CR-4 | `/code-review` (rodada 1) | = RD-09 (`resolverFfmpeg` muta o `PATH` global) | — | — | fechado: sem `putenv` |
| CR-5 | `/code-review` (rodada 1) | CT-96 lê o `.env` real de quem roda | teste | — / CT-96 | fechado: `$atual` explícito |
| CR-6 | `/code-review` (rodada 1) | docblock órfão em `tests/Pest.php` | higiene | — | fechado |
| CR-7 | `/code-review` (rodada 1) | o extrator liga "dentro de comentário" até o fim do arquivo com `<!--` numa cerca alheia | teste | — / CT-103 | fechado para info string de um token; resíduo RD2-18 |
| CR-8 | `/code-review` (rodada 1) | = RD-07 | — | — | com RD-07 |
| CR-9 | `/code-review` (rodada 1) | o conferidor esconde as violações quando o piso falha, e acusa erro por regex no texto | código (site) | — / CT-B01 | fechado em `0da4f78`; conferido com o vendor na rodada 2 e rodado no CT-B01 |
| CR-10 | `/code-review` (rodada 1) | consertar `AgenteBase` e registrar `schedule:work` no `DevCommands` em vez de documentar | — | — | **rejeitado**: muda o comportamento do kit para caber no texto, fora do escopo declarado no `00`; o defeito da temperatura foi entregue ao mantenedor |
| RD2-01 | eixos (rodada 2) | o CT-64 deixou de guardar o quadro sobrado | teste | — / CT-64 | fechado em `12af492` |
| RD2-02 | eixos (rodada 2) | as correções de RD-09 não têm teste que as reprove | teste | — / `[RD2-02/RD2-03]` | parcial: o `finally` guardado; timeout e publicação seguem sem guarda (DV-07) |
| RD2-03 | eixos (rodada 2) | "qualquer outra exceção segue para o próximo clipe" é falso | código + teste | — / `[RD2-02/RD2-03]` | fechado em `12af492` (`catch (Throwable)`) |
| RD2-04 | eixos (rodada 2) | `publicarGif` parte da premissa falsa de que `rename()` falha entre volumes | código | — | aberto → RD3-09 |
| RD2-05 | eixos (rodada 2) | RD-07 Repro B: `--no-seed` ou banco inacessível | código + teste | — / `[RD2-05]` ×4 | parcial em `db345fb` → RD3-01, RD3-03, RD3-04 |
| RD2-06 | eixos (rodada 2) | senha digitada não utilizável anunciada como "a que você digitou" | teste → correção | — / CT-41 | fechado em `db345fb` |
| RD2-07 | eixos (rodada 2) | duas fontes para "a senha é utilizável?" (arquivo × `config()`) | código | — | aberto → RD3-03 |
| RD2-08 | eixos (rodada 2) | `Dotenv::parse` sem tratamento derruba `aplicar()` no meio (barra invertida) | código + teste | ~~**P-nn não criada**~~ P-44 *(alterado em 2026-09-29: step 11, QA-03)* / `[RD2-08][CT-117]`, `[CT-118]` | fechado em `db345fb`; ~~sem `RQ` que o cubra~~ coberto pela P-44 |
| RD2-09 | eixos (rodada 2) | o fato do DG-03 aceita o defeito do RD-12 com as linhas reordenadas | teste | — / CT-06 (RQ-21) | fechado em `8a3974e` → RD3-06 → RD4-03 |
| RD2-10 | eixos (rodada 2) | CT-10: ausência de uma grafia só, e só em pt | teste | — / CT-10 (RQ-34) | fechado em `8a3974e` |
| RD2-11 | eixos (rodada 2) | a regex `\s*-->\s*` não reconhece `--->`, `-.->`, `==>` | teste | — / CT-11, CT-73, CT-83 (RQ-34) | fechado em `8a3974e` → RD3-05 |
| RD2-12 | eixos (rodada 2) | as leituras reais do DG-13 no CT-63 e no CT-94 não casam com o diagrama | teste | — / CT-63, CT-94 | fechado em `8a3974e` |
| RD2-13 | eixos (rodada 2) | o CT-28 não afirma a solidez dos atributos desenhados | teste | — / CT-28 | fechado em `8a3974e` |
| RD2-14 | eixos (rodada 2) | o oráculo do CT-50 aceita o nome do quadro em qualquer lugar, até em comentário | teste | — / CT-50 | aberto → RD3-07 |
| RD2-15 | eixos (rodada 2) | CT-11 e CT-73 continuam vazios | teste | — / CT-11, CT-73 | aberto → RD3-08 |
| RD2-16 | eixos (rodada 2) | 21 CTs ainda só afirmam que o bloco existe (Major) | teste | — / os 21 (RQ-35) | parcial em `8a3974e` → RD3-06 |
| RD2-17 | eixos (rodada 2) | comentários e citações velhos no delta | higiene | — | parcial → RD3-02, RD3-10 |
| RD2-18 | eixos (rodada 2) | o CR-7 só trata info string de um token | teste | — / CT-103 | fechado em `8a3974e` |
| HR2-01 | eixos (rodada 2) | hipótese: o `afterEach` apaga `kit_arte_*` de outro worker | — | — | **rejeitada**: só o `KitArteTest` cria esses diretórios, e o `--parallel` distribui por arquivo |
| HR2-02 | eixos (rodada 2) | hipótese: `rename()` sobre destino existente falha no Windows | — | — | **rejeitada**: medido, substitui |
| HR2-03 | eixos (rodada 2) | hipótese: timeout deixa `isSuccessful()` verdadeiro | — | — | **rejeitada**: `stop()` encerra com código ≠ 0 |
| RD3-01 | eixos (rodada 3) | os conselhos para "banco não populado" levam ao admin com `password` ou a um beco sem saída (Major) | código + teste | — / `[RD3-01][RD3-04]` (RQ-28) | parcial em `83219ff`: fechado para `--no-seed`; banco inacessível virou RD4-02 (DV-01) |
| RD3-02 | eixos (rodada 3) | a suíte Kit ficou vermelha: CT-26 do `CitacoesDeCodigoTest` | sessão (mecânico) | — | fechado: citação do `garantirNoEnv` na linha certa |
| RD3-03 | eixos (rodada 3) | `corrigirResumoDaSenha()` conclui "não semeou" de `senhaGerada === null` | código | — / `[RD3-03]` | fechado em `83219ff` (`$semeado` vem de `handle()`); resíduo RD4-05 |
| RD3-04 | eixos (rodada 3) | nenhum teste liga a correção de RD2-05 ao `handle()` | teste | — / `ResumoDoKitInstallTest` | parcial → RD4-05 (DV-03) |
| RD3-05 | eixos (rodada 3) | o extrator "normalizado" não reconhece formas válidas de seta | teste | — / `[RD3-05]` ×3 (RQ-34) | fechado em `697a75b` |
| RD3-06 | eixos (rodada 3) | fatos que passam no vazio em DG-12/13/14/16/17, e o DG-03 só em pt (Major) | teste | — / CT-06 (RQ-34, RQ-35) | parcial em `697a75b`: DG-12 e DG-13 fechados; DG-03 regrediu (RD4-03); DG-17 ficou fraco (RD4-07) |
| RD3-07 | eixos (rodada 3) | RD2-14 não foi tocado | teste | — / CT-50 | fechado em `8a6e9e1` (`token_get_all`) |
| RD3-08 | eixos (rodada 3) | RD2-15 não foi tocado | teste | — / CT-73 | parcial em `697a75b`: o `continue` virou asserção; o fallback segue vazio (RD4-07, DV-04) |
| RD3-09 | eixos (rodada 3) | RD2-04 segue aberto, e a mensagem de falha de cópia não é alcançada | código | — / `[RD3-09]` | fechado no papel em `8a6e9e1`, causou RD4-01 |
| RD3-10 | eixos (rodada 3) | o delta deixou citações velhas que o CT-26 não confere | sessão (mecânico) | — | reaberto → RD4-06 |
| RD3-11 | eixos (rodada 3) | o job `site` não restringe o token | CI | — | fechado em `5ff5227` |
| RD3-12 | eixos (rodada 3) | a correção depende de um literal duplicado | código | — / `[RD3-12]` ×2 | fechado em `83219ff` (`RESUMO_SENHA_GERADA`) |
| HR3-01 | eixos (rodada 3) | hipótese: o `preg_replace_callback` quebra algum chamador de `SubstituicaoEmArquivo` | — | — | **rejeitada**: os 6 chamadores passam substituições literais |
| HR3-02 | eixos (rodada 3) | hipótese: a regra de CSS do contraste altera o tema claro | — | — | **rejeitada**: valores idênticos com e sem a regra |
| RD4-01 | eixos (rodada 4) | o `kit:arte` não monta mais GIF com o ffmpeg real (Blocker, regressão da rodada) | Adendo 5 (RQ-41) | — / CT-46, CT-49, CT-64, CT-65 | corrigido em `8a6e9e1`: `-f gif` e ffmpeg de teste que escolhe o formato como o real |
| RD4-02 | eixos (rodada 4) | banco inacessível: nota e aviso do mesmo banner se contradizem, e nenhum funciona (Major) | dívida (RQ-44) | — | **DV-01, destaque** |
| RD4-03 | eixos (rodada 4) | o fato do DG-03 ignora o rótulo, e a troca de rótulos passa nos dois idiomas (Major, regressão) | Adendo 5 (RQ-41) | — / CT-06 | corrigido em `697a75b` |
| RD4-04 | eixos (rodada 4) | Pint vermelho em dois arquivos de teste deixa o CI vermelho (Major) | Adendo 5 (RQ-42) | — | corrigido; `pint --test` passou em 2026-09-29 |
| RD4-05 | eixos (rodada 4) | cada metade do repro de RD3-04 sobrevive sozinha | dívida (RQ-44) | — | DV-03 |
| RD4-06 | eixos (rodada 4) | citações e mensagens que o delta deixou erradas | Adendo 5 (RQ-42) | — | corrigido; `CitacoesDeCodigoTest` 3/3 |
| RD4-07 | eixos (rodada 4) | resíduos de guarda no vazio: o fato do DG-17 e o fallback do CT-73 | dívida (RQ-44) | — | DV-04 |
| RD4-08 | eixos (rodada 4) | o hint da senha ainda promete gerar com `--no-seed` | dívida (RQ-44) | — | DV-05 |
| RD4-09 | eixos (rodada 4) | dentro de um container já criado, seguir a instrução semeia `password` | dívida (RQ-44) | — | DV-06 |
| RD4-10 | eixos (rodada 4) | pré-existente: reinstalar sobre admin com `password` imprime uma senha que não vale (Major) | dívida (RQ-44) | — | **DV-02, destaque** |
| HR4-01 | eixos (rodada 4) | hipótese: `--no-seed` com senha digitada dá instruções contraditórias | — | — | **rejeitada**: redundante, não contraditório; o `db:seed` usa a senha digitada |
| HR4-02 | eixos (rodada 4) | hipótese: a seta nova do extrator dá falso positivo nos blocos reais | — | — | **rejeitada**: `DiagramasDaArquiteturaTest` todo verde na rodada 4 |

**Achados do CT-B, fora da revisão do diff** (`53cacab`): o DG-11 não renderizava (o `;` da nota) e o
tema escuro do Mermaid deixava o rótulo de aresta a 4,43:1 em 16 páginas — os dois entraram na rodada 3
pelo Adendo 3 (RQ-32, RQ-33) e estão corrigidos (`f39dd18`, `b2991c9`). E a suíte completa da rodada 2
pegou o CT-05 do `MysqlNoDockerTest` contra o texto do DG-18 (`821d357`).

## Dívidas declaradas

<!-- Adendo 5 (RQ-39, RQ-44): o que a revisão da 4ª rodada achou e não entrou pelas exceções vira
     dívida aqui e no PR, com RD4-02 e RD4-10 em destaque. Mais as dívidas já declaradas antes. Nenhuma
     issue foi aberta neste step: o destino diz onde cada uma deve ir, e quem abre é o step 11 (PR). -->

| # | Origem | O quê | Motivo de não corrigir agora | Destino |
|---|---|---|---|---|
| **DV-01** | **RD4-02** (Major) | Com o banco **inacessível**, a nota nova do banner ("defina `KIT_ADMIN_PASSWORD` e rode `db:seed`") e o aviso do `conferirConexao()` ("`docker compose up -d && php artisan migrate --seed`") se contradizem, e nenhum funciona sozinho: `db:seed` sem migrations dá `no such table: roles`, e `migrate --seed` com a variável vazia semeia `password`. O RD3-01 só fechou para `--no-seed` | Adendo 5: fora das exceções (regressões e mecânico) | **issue** no PR: uma instrução só para banco não populado, que funcione nos dois casos |
| **DV-02** | **RD4-10** (Major, pré-existente, fora do delta) | Reinstalar sobre um administrador que já existe com `password` (instalação de antes da v0.39.1): o `kit:install` gera e imprime uma senha, mas o `UsuarioAdminSeeder` não toca admin existente — a senha impressa não vale e o admin segue com a credencial publicada | Adendo 5; defeito de antes deste trabalho | **issue** no PR |
| DV-03 | RD4-05 (Minor) | Cada metade do repro de RD3-04 (remover `corrigirResumoDaSenha()`; fixar `mensagemDoBanner(true)`) sobrevive sozinha: o teste confere que as palavras aparecem, não que banner e resumo dizem o mesmo | Adendo 5 | issue (teste) |
| DV-04 | RD4-07 (Minor) | O fato do DG-17 só olha os nós de destino (acrescentar `docs/` ao rótulo de `caminhos_do_kit` passa em pt e en); o fallback do CT-73 procura `panel_user --> importar`, ID que não existe no bloco real | Adendo 5 | issue (teste) |
| DV-05 | RD4-08 (Minor) | O hint do prompt de senha ainda promete "gerar … e imprimi-la" com `--no-seed` | Adendo 5 | issue |
| DV-06 | RD4-09 (Minor) | Num container já criado (`env_file: .env`), seguir a instrução semeia `password`: a variável exportada vazia vence o `.env` novo; a instrução não diz para recriar o container | Adendo 5 | issue (texto da instrução e docs do Docker) |
| DV-07 | RD2-02, RD3-09; conferente da rodada 1 | **Não falsificável nesta pilha**: o timeout real de 60 s do `Process` (nenhum teste força `ProcessTimedOutException`) e a atomicidade do `rename()` entre volumes (o PHP copia e devolve `true`); guarda mantida | a suíte não exibe o defeito no Windows nem sem container | roadmap: CI Linux com `storage/` em volume próprio, como o container `app` do kit |
| DV-08 | passo 19 do `01` (step 6 da 3.x) | Capturas órfãs: `admin-user-ficha-header` e `admin-organizacao-header` seguem capturadas e fora de `IMAGENS`, sem uso em `docs/` nem no README (0 ocorrências) — o `kit:arte` as relata como ignoradas | nenhuma `RQ` desta feature pede a correção | roadmap / fix próprio |
| DV-09 | step 10 (A-05) | ~~RQ-36 sem CT~~ CT-105 (RQ-36) sem teste: nenhum teste confere o job `site` do `ci.yml`. O cenário foi derivado na reconferência do step 10 (R47 do `04`, 6 mutantes com matador e R47.M7 na lacuna L-07) | cenário não derivado na rodada 3; na reconferência, o cenário nasceu no `04`, e o teste está fora do alcance deste step | ~~`feature-test-design` (entrada: `00` com o Adendo 3 e o `04`)~~ executor do CT: `it('[CT-105] …')` em `tests/Kit/DiagramasDaArquiteturaTest.php`, provado vermelho por R47.M1..M6, antes ou no step 11 | **Fechada em 2026-09-29**: `[CT-105]` escrito e verde, M1..M6 vermelhos.
| DV-10 | step 10 | 14 testes com o ID do achado, sem cenário no `04` | idem | `feature-test-design`; lista no `04` | **Fechada em 2026-09-29** *(alterado: step 11, QA-03)*: os 14 têm cenário e levam o `[CT-nn]`; ~~seis cenários ainda pedem mais do que o teste afirma~~ *(alterado em 2026-09-30: os seis testes ganharam todo `Então` do cenário — `## 26.`, QA-03)* |
| DV-12 | QA-19 do quality gate, ciclo 2 | Um `.env` editado à mão com valor **multilinha entre aspas** (`APP_NAME="linha1` + `linha2"`), que o Dotenv lê, fica ilegível depois de o kit gravar a chave: `.*$` troca só a primeira linha física e o resto vira `linha2"`, que o `Dotenv::parse()` recusa | P-48 (a confirmar pelo mantenedor): o kit nunca produz valor multilinha (RQ-51, CT-117), e a entrada só nasce de edição à mão; defeito anterior a esta feature | issue no PR; se a P-48 for negada, a L-21 do `04` vira linha de R64 (trocar até a aspa que fecha, ou recusar com mensagem) |
| DV-11 | QA-12 do quality gate, ciclo 1 | O `pest --mutate` pelo `pestw.cmd` da `feature-wiki` dá score falso no Windows quando o mutante tem dois ou mais testes cobridores: o plugin relança com `--filter="A\|B"`, e o `%*` do `cmd` lê a barra vertical como pipe — exit 255 em 0,22 s, contado como mutante morto (98,58 % contra 57,09 % pelo arnês do gate, no `CustomizadorDaInstalacao`) | é defeito do arnês da skill, não da feature; não reprova a entrega (Adendo 6: "O QA-12 vira nota para a skill") | a skill `feature-wiki` (`scripts/pestw.cmd`); e, se o step 12 aceitar, uma linha na `.ai/rules/testes.md` |

## Achados do step 10 (para o quality gate)

<!-- A reconciliação leu o código atrás das afirmações do 01/02; o que não é texto a corrigir virou
     achado. Nada de app/ nem de tests/ foi editado neste step. -->

| # | Achado | Evidência | Destino sugerido |
|---|---|---|---|
| A-01 | Os números do DG-10 ("ociosidade 1800s", "5 tentativas erradas") não têm guarda; RQ-26 pede guarda que falhe quando o código deixar de bater | `grep -rn "1800\|idle_timeout" tests/Kit/DiagramasDaArquiteturaTest.php tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php tests/Pest.php` vazio; o `04` trata o DG-10 pela lacuna L-01 | teste | **Fechado em 2026-09-29**: CT-106/CT-107 (R48) em `tests/Kit/GuardasDosDiagramasTest.php`.
| A-02 | O DG-20 saiu sem o flowchart do `kit:tenancy`; a ordem de `KitTenancy::handle()` e de `recriarBanco()` ficou em prosa, sem guarda de ordem | `grep -c '^```mermaid' docs/pt/recursos/multi-tenancy.md` = 1; `grep -n "KitTenancy\|recriarBanco" tests/Kit/DiagramasDaArquiteturaTest.php` vazio | especificação (decidir se o flowchart entra) ou teste | **Fechado em 2026-09-29**: o flowchart do `kit:tenancy` não entra (decisão da sessão, lacuna L-08 do `04`); o que o DG-20 desenha é guardado por CT-112..CT-115 (R51, R52) — e o CT-114 achou o `alt` errado, corrigido.
| A-03 | As metades do agendador e do Compose do DG-19 não têm guarda, e o bloco não traz a nota "`schedule:work` NÃO roda dentro do `composer dev`" que o `01` previa | `grep -n "Schedule::class\|->events()\|pulse:check\|ai,ai-post" tests/Kit/DiagramasDaArquiteturaTest.php` vazio | teste | **Fechado em 2026-09-29**: CT-108..CT-111 (R49, R50) em `tests/Kit/GuardasDosDiagramasTest.php`.
| A-04 | **Suíte vermelha**: `tests/Kit/CoberturaDeTestesTest.php` `[CT-27]` (de outra wiki) falha desde a rodada 3, que acrescentou o símbolo à citação de `docs/{pt,en}/referencia/qualidade-de-codigo.md` (`…CustomizadorDaInstalacao.php:label_plural:524`); a regex do CT-27 exige `app/\S+\.php:\d+`. O CT-26 do `CitacoesDeCodigoTest` e o CT-27 pedem formas que se excluem | `vendor/bin/pest tests/Kit/CoberturaDeTestesTest.php --compact --no-tia`: 19/20; na suíte sem browser, 3.565 de 3.569 com 1 falha; na `origin/main` a linha era `:470` e passava | implementação/teste: decidir a forma da citação (ou a regex), **blocker do CI** (`composer test` roda a suíte Kit) | **Fechado em 2026-09-29 pela sessão**: a citação da doc voltou a ser só a linha, a forma que o CT-27 exige (o default está em `app/Support/CustomizadorDaInstalacao.php:label_plural:524`) (a forma com símbolo foi introduzida pela própria sessão na rodada 3); CT-26 e CT-27 verdes.
| A-05 | RQ-36 sem CT (é a DV-09) — *corrigido na fonte na reconferência: R47 e CT-105 no `04`; sobra o teste do CT-105* | `rastreabilidade.sh`: `00-requisito.md:324: RQ-36 sem CT` na primeira passada; saída vazia depois da R47; `ids-ct.sh`: `CT-105 sem teste` | teste | **Fechado em 2026-09-29**: `[CT-105]` escrito.
| A-06 | A página de diagramas promete que cada um é "guardado por um teste automatizado que falha quando o código deixar de bater com o que o diagrama descreve" (`docs/pt/referencia/arquitetura-em-diagramas.md`, primeiro parágrafo, e o par en): vale para o fato declarado de cada DG, não para tudo que DG-10, DG-19 e DG-20 desenham (A-01..A-03) | idem A-01..A-03 | teste (preferível a enfraquecer a frase) | **Fechado em 2026-09-29**: CT-116 (R53) — o fato declarado de cada um dos 20 DGs aceita os 40 blocos publicados e reprova 40 cópias adulteradas.
| A-07 | O `rastreabilidade.sh` trata como substituída toda `RQ` citada na coluna `Substitui` dos Adendos sem "(parcial)", e no `00` a coluna foi usada para "resolve", "concretiza", "reforça", "exceção a" e "aplica": 13 `RQ` saem da cobrança mecânica (RQ-02, 04, 05, 07, 08, 09, 10, 11, 12, 16, 17, 26, 39), RQ-26 inclusive | a coluna `Substitui` das tabelas dos Adendos, extraída com `awk` e `grep -oE 'RQ-[0-9]+'`; a Cobertura do `01` lista todas, por leitura | nota para a skill (o `00` é imutável) |

## Conformidade com Rules

<!-- Uma linha por rule de .ai/rules/ cujo paths: casa com um arquivo do diff (conformidade-rules.sh).
     "violada" = blocker do PR. -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — papel por `ContextoDePapeis`; DTO em `app/Data`; `Paineis::correnteOuPadrao()` e `Paineis::url()` | `app/**` (10 arquivos) | n.a. | o diff não atribui papel, não cria DTO nem resolve painel: `git diff -U0 origin/main...HEAD -- app/ \| grep '^+' \| grep -cE "assignRole\|syncRoles\|getCurrentOrDefaultPanel\|extends Data\|getUrl\(\)\|getLoginUrl\(\)"` = 0 |
| `css-filament.md` — utilitária de blade de vendor no CSS do kit; `@layer` de plugin | `app/Providers/**` (3 arquivos) | n.a. | nos providers só mudaram comentários: `git diff -U0 origin/main...HEAD -- app/Providers app/Models app/Ai \| grep -E '^[+-]' \| grep -vE '^[+-]\s*(\*\|//\|/\*)'` sem linha de código; nenhum CSS de painel tocado (o `site/src/styles/kit.css` é do site Starlight, fora do glob) |
| `models.md` — `ModeloCacheavel`; `papelDoPainel()`; `SoftDeletes` e mídia | `app/Models/**` (`AgenteIa.php`) | n.a. | só o comentário do cast de `temperatura` (mesma conferência) |
| `providers-filament.md` — plugin que resolve o painel corrente nos três painéis | `app/Providers/Filament/**` (3 arquivos) | n.a. | só comentários; nenhum plugin registrado ou removido |
| `providers.md` — rota do kit no `KitServiceProvider`, com `web` explícito | `app/Providers/**` | n.a. | nenhuma rota nova: `git diff -U0 origin/main...HEAD -- app/Providers routes/ \| grep '^+' \| grep -c "Route::"` = 0 |
| `specs.md` — justificativa de vendor com arquivo:linha; varrer o padrão; citação conferida por símbolo | `wikis/specs/**` (6 arquivos da wiki) | aplicada | `citacoes.sh {wiki}`: saída vazia, exit 0, depois de corrigir as 276 da entrada; afirmações de vendor com citação (ex.: `site/node_modules/astro-mermaid/astro-mermaid-integration.js:catch:554-567` no `05`, `vendor/laravel/ai/src/Gateway/TextGenerationOptions.php:forAgent:69-76` no `01`); a varredura do padrão rendeu A-04 e A-07 |
| `testes-browser.md` — `kit:arte` de lista declarada; o `beforeEach` não arranja painel; aquecer pelo kernel; `assertPathIs` antes; nunca `--parallel` | `tests/Browser/**` (a fixture), `tests/BrowserTenancy/**` | aplicada | os quadros novos são declarados em `KitArte::CLIPES` e o CT-50 exige a captura real de cada um (quadro de clipe não vira PNG, então não vai para `IMAGENS`); os dois `it('[CT-B03]')` arranjam o painel dentro do cenário e aquecem pelo kernel (`$this->get('/login')`); `assertPathIs('/login/painel')` antes do `assertSee`; CT-B03 rodado sem `--parallel`. Ressalva pré-existente: DV-08 |
| `testes.md` — helper cruzado em `tests/Pest.php`; `toContain()` sem mensagem; ausência filtra comentário; ID histórico de CT sem colchetes | `tests/**` (13 arquivos) | aplicada | `blocoDoServico`, `blocosMermaidDe`, `blocoDoCatalogoNaArvore` e o extrator de arestas em `tests/Pest.php`, `tests/Kit/HelpersDeTesteTest.php` 1/1; `toContain` com mensagem trocado em `58f0d67`; o CT-42 filtra comentário com `codigoSemComentario()`. Ressalva: 10 comentários de teste citam `[CT-nn]` **desta** wiki com colchetes (ex. `tests/Kit/KitArteTest.php`, `tests/Pest.php`) — a rule fala de ID antigo, movido ou de outra wiki, e o `ids-ct.sh` sobre os arquivos da feature não ficou mascarado. *(alterado em 2026-09-29: o ciclo 1 do gate achou a rule **violada** — três helpers clonados com outro nome (QA-07). Corrigido no ciclo 2 — e **violada de novo** pelos executores do fechamento (QA-17 do ciclo 2: dois helpers com o mesmo corpo), corrigida no ciclo 3 com `semAcentoESemCaixa()` uma vez em `tests/Pest.php` (`grep -rn "mb_strtolower(Str::ascii" tests/` só acha `tests/Pest.php`): `grep -rn "function transicoesDoEstado\|function mensagensDaSequencia\|function ordemDoDG20EstaCorreta" tests/` vazio, e cada um existe uma vez em `tests/Pest.php` — `tests/Pest.php:transicoesDeEstado:1619`)* |

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: 1 · **Veredito**: REPROVADO → especificação · **Data**: 2026-09-29
- **Relatório**: `06-relatorio-qa.md` — 14 achados (Blocker 0, Major 7, Minor 5, Cosmético 2); J e K verificadas só em parte; sub-agente `fw-qa-gate`, cego
- **Débito**: n/a (reprovado)
- **Decisão do mantenedor**: Adendo 6 — corrigir os 14, com o teste primeiro onde o destino é teste, e rodar o ciclo 2 cego (RQ-45, RQ-46); no QA-06, "tamanho natural com rolagem" (RQ-47..RQ-49)

| Achado | Severidade | Destino no gate | Quem corrigiu no ciclo 2 | Situação (2026-09-29, `## 26.`) |
|---|---|---|---|---|
| QA-01 | Major | 1 — especificação | reconciliação (R) e a frase da página (C1) | fechado |
| QA-02 | Major | 1 | R | fechado |
| QA-03 | Major | 1 → 3 | P-44 no `00`; cenários (CEN); `[CT-nn]` nos testes (T1, T2); as asserções e o CT-B04 completos (#66, #67, #68, #71) | ~~**parcial**~~ fechado em 2026-09-30 |
| QA-04 | Major | 3 → 2 | R58, R59 (CEN); CT-129, CT-130 (T1); DG-02, DG-07, DG-08 (C1) | fechado |
| QA-05 | Major | 3 | CT-03, CT-85 reescritos e CT-126 (CEN, T1) | fechado |
| QA-06 | Major | 3 → 2 | CT-B05, CT-B06 (CEN, T3); CSS (C2) | fechado |
| QA-07 | Major | 2 — implementação | T1 | fechado |
| QA-08 | Minor | 3 → 2 | R60 e CT-131 (CEN, T1); docs (C1) | fechado |
| QA-09 | Minor | 3 → 2 | R61, CT-89, CT-132 (CEN, T1); docs en (C1) | ~~**parcial**~~ fechado: CT-132 7/7 desde `252826c` |
| QA-10 | Minor | 3 | R54 e CT-118 (CEN, T2) | fechado |
| QA-11 | Minor | 1 | `wikis/glossario.md` (R); DG-20 en (C1); o glossário em `CAMINHOS_DO_KIT` e o README com 12 (`ef68f6d`) | ~~**parcial**~~ fechado |
| QA-12 | Minor | 4 — infra | nota para a skill (R) | fechado (DV-11) |
| QA-13 | Cosmético | 2 | altura medida e GIF remontado (C3); acentos (C1); cada quadro conferido pelo CT-B10 (#71) | **parcial, cosmético**: quadros 1 a 3 com área vazia e o `WARN` no último — decisão do mantenedor |
| QA-14 | Cosmético | 1 | R | fechado |

- **Ciclo**: 2 · **Veredito**: REPROVADO → especificação · **Data**: 2026-09-30
- **Relatório**: `06-relatorio-qa.md` — 9 achados novos (Blocker 0, Major 3, Minor 6, Cosmético 0; o QA-13 do ciclo 1 segue como débito cosmético); G (nível 3), J e K verificadas só em parte; sub-agente `fw-qa-gate`, cego (despacho #83); nenhum achado repete um do ciclo 1 já fechado
- **Débito**: n/a (reprovado)
- **Decisão do mantenedor**: Adendo 8 (2026-09-30) — à pergunta da Q?9 com as duas opções e a recomendação, a resposta foi "continue": a sessão aplicou a recomendação (descomentar no lugar, o código de hoje; RQ-53, fidelidade baixa, decisão por omissão registrada no `00`); Q?10 e Q?11 seguem abertas (só limitam o teto). Os 9 achados são corrigidos no ciclo 3, o último pelo teto da skill: QA-15 pelo Adendo 8; QA-16, QA-21, QA-22 e QA-23 pela sessão; QA-17 pela sessão (helper único em `tests/Pest.php`); QA-18, QA-19 e QA-20 pela `feature-test-design` (destino 3: CT-129 com as `accDescr`, L-21/P-48, CT-151) e pelos executores

| Achado | Severidade | Destino no gate | Situação (2026-09-30) |
|---|---|---|---|
| QA-15 | Major | 1 — especificação | ~~espera a resposta do solicitante~~ fechado em 2026-09-30 pelo Adendo 8 (RQ-53), `## 28.` |
| QA-16 | Major | 1 | ~~números e afirmações velhas~~ fechado em 2026-09-30, `## 28.` |
| QA-17 | Major | 2 — implementação | ~~clonados~~ fechado: `semAcentoESemCaixa()` uma vez em `tests/Pest.php` |
| QA-18 | Minor | 3 → 2 | fechado: CT-129 com as `accDescr` (vermelho antes), docs pt/en corrigidas |
| QA-19 | Minor | 3 | fechado como premissa P-48 + L-21 + DV-12 (sem cenário que afirme a direção) |
| QA-20 | Minor | 3 | fechado: CT-151, 86,25 % → 92,68 %; sobram os equivalentes e os de `aplicar()` |
| QA-21 | Minor | 1 (+ nota para a skill) | fechado: 64 recitadas pelo ID; a nota para a skill em `## Notas de Implementação` |
| QA-22 | Minor | 1 | fechado: 11 citações curtas realinhadas, pt e en |
| QA-23 | Minor | 1 | fechado: três termos no `wikis/glossario.md` |

- **Ciclo**: 3 · **Veredito**: pendente — despacho #89 aguarda memória na máquina (o `php artisan serve` e a suíte foram encerrados pelo host em 2026-09-30)

## Candidatos a Rule

<!-- Step 12, depois do veredito (requirement-to-rule). -->

- {YYYY-MM-DD} — rota: {sessão principal | sub-agente com MCP herdado} — apresentados N · gravados N · recusados N · descartados no gate N · poda N
## Auditoria Pré-Implementação
<!-- Saída dos steps 5 e 6, ANTES de escrever código. Não confundir com "Desvios do Plano",
     que é pós-implementação. -->

*(alterado em 2026-09-29: registro histórico, sem mudança de conteúdo. As citações destas tabelas descrevem o código **de antes da entrega**; as que a implementação moveu ou apagou foram reescritas como referência histórica — arquivo, símbolo e "linha N" — ou ganharam o path completo e o símbolo que o `citacoes.sh` pede)*

### Revisão profunda (step 5) — premissas do plano contra o código real

| # | Premissa do plano | O código real diz | Correção aplicada (arquivo, seção) |
|---|---|---|---|
| 1 | Citações com caminho curto e símbolo (7) | Símbolo certo na linha citada; o script dá ERRO por causa do caminho | Caminho completo. 01: passos 4, 17. 02: ADR-03, 04, 07, 09, 10 |
| 2 | `src/lib/sponsor-campaign.ts` (constante `sentCampaign`, linha 21) e `explainer-share.tsx` (linha 130) como citação local | Arquivos do GitDiagram (`abe0620f`), fora deste repositório | Reescritas como citação de repositório externo, sem apagar. 02: ADR-11, ADR-12 |
| 3 | Citações sem símbolo (README, `site/*`, `composer.json`, `import-export-csv.md`) | A skill reprova citação sem símbolo | Símbolo acrescentado. 01: passos 1, 15, 19. 02: ADR-02, ADR-09, ADR-10 |
| 4 | DG-20: `KIT_TENANCY=true` em `AtivadorDeTenancy.php`, linha 169 | O arquivo tem 116 linhas; a escrita é `app/Console/Commands/KitTenancy.php:ligarFlagNoEnv:70` → `app/Support/AtivadorDeTenancy.php:escreverEnv:35` | Citação corrigida. 01: passo 17 |
| 5 | DG-18: `mysql:55` | O cabeçalho do serviço é a `:52`; a 55 é o `profiles:` | `:52`. 01: passo 15 |
| 6 | DG-15, DG-16 e DG-20: a Reflection compara a ordem das chamadas por igualdade | `handle()` chama métodos fora do diagrama, retorna cedo e tem `try/finally`; nomes de método ≠ nós | Subsequência em ordem relativa, mapa nó → método com linha, controle positivo; DG-20 também confere o corpo de `recriarBanco()`. 01: passos 15, 16, 17 |
| 7 | Cobertura do Requisito com os números de passo errados (16 linhas) | Os passos 15 a 21 foram deslocados | 16 linhas corrigidas. 01: Cobertura. Também "passo 18" → 21 em Autorização e Riscos |
| 8 | ADR-05: DG-16 é sequência; faltam DG-03 e a sequência do DG-20 | DG-16 é `flowchart TD`, DG-03 é `flowchart`, DG-20 tem sequência curta | Tabela corrigida. 02: ADR-05. Também RQ-08 no 01 |
| 9 | RQ-28 (resumo do `kit:install`) aponta para o passo 1, que não o corrigia | `CustomizadorDaInstalacao.php`, linha 295 (antes da entrega), imprime `password (padrão do kit)` quando a senha é gerada | Novo item no passo 1, com caso no bloco `R9` do `CustomizadorDaInstalacaoTest`. 01: Natureza, Análise, passo 1. 02: linha nova no ADR-10 |
| 10 | Verificação `grep -n "password" README*` "só acha o texto novo" | O `README.en.md` usa "password" como palavra comum em 10 linhas | Grep com crase. 01: passo 1 |
| 11 | "`DevCommands::registerDefaults` registra server/queue/vite/reverb" | O `DevCommands` registra serve/queue/pail/vite; o reverb vem de `vendor/laravel/reverb/src/Reverb.php:registerDevCommands:12` | Texto e citação corrigidos. 01: passo 1. 02: ADR-10 |
| 12 | Esperar `svg[role="graphics-document"]` | O Mermaid grava `role="graphics-document document"`; o seletor de igualdade nunca casa | `svg[role~="graphics-document"]`. 01: Riscos, passo 2. 02: ADR-02 |
| 13 | Sentinela `grep graphics-document site/dist/...` | O `astro-mermaid` desenha no cliente; o `dist/` só tem `<pre class="mermaid">` | Sentinela em duas metades: `pre.mermaid` no `dist/`, um `<svg>` por bloco no navegador. 01: passo 2. 02: ADR-02 |
| 14 | ADR-02: "a guarda RQ-26 roda o Mermaid num navegador" | A guarda Pest só lê texto | `verifica-acessibilidade.mjs` reprova bloco sem `<svg>` e `Syntax error in text`. 01: passo 2. 02: ADR-02, ADR-03 |
| 15 | ADR-04: "sem `accTitle`, o axe reprova `svg-img-alt`" | O seletor do axe é por igualdade; se ele chega a se aplicar, não está confirmado | Quem cobra é a camada 1 da guarda. 02: ADR-04. 01: linha nova em Riscos |
| 16 | Lock do site não citado | O workflow do site roda `npm ci` (`.github/workflows/pages.yml:npm:55`); o `[CT-15]` exige o lock (`:package-lock:673`) | `site/package-lock.json` no mesmo commit. 01: Impacto, passo 2, Verificação. 02: ADR-02 |
| 17 | README "após a seção de arquitetura já existente" | Essa seção não existe | Dentro de `## Os três painéis` (`README.md:106`). 01: passo 3 |
| 18 | DG-01 sem o nó `pulse` | O catálogo põe pulse no segundo plano | Nó acrescentado. 01: passo 3 |
| 19 | "~15 nós/linhas", "Nove páginas", "as 9 páginas dos passos 5 a 13" | DG-01 tem 19 nós, DG-02 tem 26; são 12 páginas, nos passos 5-13 e 15-18 | 01: Análise, Impacto, passo 4. 02: ADR-04 item 8, ADR-07 |
| 20 | DG-02: atores iguais aos papéis, "com e sem `permission.teams`" | Visitante, agendador e CLI não são papel; o `admin_app` depende de `kit.tenancy.enabled` (`database/seeders/PapeisSeeder.php:tenancy:79`) | Só os atores de papel entram na comparação; condição corrigida. 01: passo 4 |
| 21 | DG-03 sem "contexto" nem `canAccessTenant`; dataset de 4 contas | O catálogo exige os dois (`app/Models/User.php:contexto:217`, `:canAccessTenant:789`); são 5 ramos | Nós e dataset (5 + 4) acrescentados. 01: passo 4 |
| 22 | DG-04: "LoginResponse ou equivalente", `gatherMiddleware()` | Contrato em `app/Providers/KitServiceProvider.php:LoginResponse:31`; lista resolvida em `Router::gatherRouteMiddleware` | Guarda exata, por subsequência. 01: passo 5 |
| 23 | DG-09: "Reenviar só em Pendente/Expirado", sem citação nem guarda | `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:visible:89` | Citação e guarda de visibilidade. 01: passos 8 e 9 |
| 24 | DG-11: `remember` no fim, filtro de saída antes da auditoria; guarda sobre `middleware()` crua | O SDK põe `RememberConversation` primeiro (`vendor/laravel/ai/src/Providers/Concerns/GeneratesText.php:RemembersConversations:148`); `middleware()` lança exceção sem o `AssistenteSeeder` | Ordem real; seed obrigatório; guarda própria para o `remember`; `getRawListeners` no lugar de `hasListeners`. 01: passo 11 |
| 25 | DG-12: plugins de `getPlugins()` "batem" com as telas | São 21 plugins; 3 telas são Page/Resource; o total de checks do Health varia | Subconjunto + páginas/resource + 7 checks incondicionais; coluna "quem grava" do catálogo acrescentada; "substitui" o teste do `FundacaoTest` → "complementa". 01: passo 12 |
| 26 | Guarda sem marcador por DG; IDs por uma regex só; sentinela por caso | `tests/Kit` viaja pelo `kit:update` sem `docs/` | Sentinela no arquivo inteiro, marcador `%% DG-xx`, IDs extraídos por tipo. 01: passo 14. 02: ADR-06 |
| 27 | DG-18 "reusar `($this->blocoDoServico)`" | É uma closure no `beforeEach` de outro arquivo, inalcançável daqui | Move para `tests/Pest.php` (mover, não copiar). 01: Natureza, regressão, passo 15. 02: ADR-06 |
| 28 | DG-17 com 4 + 4 nós, guarda de igualdade | São 7 alvos `export-ignore` e 9 chaves; `README.md` não é chave; a constante só existe quando o arquivo dela é carregado | Conjuntos inteiros; asserção própria para o `README.md`; constante para `tests/Pest.php`. 01: passo 16 |
| 29 | DG-16: nós sem os passos intermediários | `<tag>` some no Mermaid; o catálogo cita `CAMINHOS_SO_RELATORIO` e exige o rótulo "não interativo" | `#lt;tag#gt;`; nós `resumo`, `nao_interativo` e `so_relatorio`. 01: passo 16 |
| 30 | DG-20 sem o nó `permission.teams` | Faz parte do catálogo (`ligarPapeisPorTenant:71`) | Nó acrescentado. 01: passo 17 |
| 31 | DG-19: "não contém `schedule:work`" sem mais nada | O `ArtisanServiceProvider` é deferível (`:120`, `:274`) e a lista pode vir vazia; o catálogo pede também os eventos agendados e os comandos do Compose | Controle positivo (medido com tinker) e as duas outras metades. 01: passo 18 |
| 32 | `IMAGENS` recebe os quadros dos clipes; verificação usa `art/import-export.gif` | Quadro de GIF não vira PNG (`KitArte.php`, linhas 41 e 134 antes da entrega); o arquivo se chama `fluxo-import-export.gif` | `IMAGENS` só recebe os 2 órfãos; `publicar()` generalizado, com exceção para `densidade`; nome do GIF corrigido. 01: Análise, passos 19 e 21. 02: ADR-09 |
| 33 | View nova em `resources/views/arte/` | O `CAMINHOS_DO_KIT` lista `resources/views` por subdiretório; o `kit:update` não entregaria o `arte/` | A view vai para `tests/Browser/Fixtures/`. 01: Superfície de UI, passo 21. 02: ADR-09 |
| 34 | `install.gif` conferido "visualmente" | O texto vem da fixture | `grep` na fixture e `assertDontSee` antes do screenshot. 01: passo 21 |
| 35 | `tests/Tenancy/LoginUnificadoTest.php` | Não existe; o CT-B01 está em `tests/Browser/LoginUnificadoTest.php` | Caminho corrigido. 01: regressão, passo 20 |
| 36 | Erros de digitação e incoerências | "Mermoid", "published", "iIegível", `architecture-beta` "além do básico" contra o item 9, "filament-shield/Packagist", "20 (mais os 3)" | 02: ADR-02, 04, 07. 01: Riscos, pergunta 6 |

### Varredura da classe irmã

| irmã | ocorrências | entradas acrescentadas ao 01 |
|---|---|---|
| `tests/Kit/DiagramasDaArquiteturaTest.php` | `RedeDeDocumentacaoTest [CT-10]`; `CAMINHOS_DO_KIT` → `tests/Kit` viaja; `MysqlNoDockerTest.php`, linha 50 (antes da entrega); `tests/Kit/DuasRotasDeEntregaTest.php:FORA_DA_ENTREGA_POR_DECISAO:132`; `[CT-25]` e `[CT-50]` (números do README) | Sentinela no `beforeEach`; os 2 ajudantes movidos para `tests/Pest.php`; cada arquivo rodado sozinho na Verificação Final |
| `docs/*/referencia/arquitetura-em-diagramas.md` | `sidebar.json`, `redirects.json`, stubs, `AMOSTRA_CLARA:63`, `[CT-41]` (já previstos); `[CT-22]` com `caminhoResolvido:190` | Estilo dos links no índice (`../../x/`) |
| `site/package.json` | `site/package-lock.json`; `.github/workflows/pages.yml:npm:55` (`npm ci`); `[CT-15]:673` | Lock no passo 2, no Impacto e na Verificação |
| `terminal-instalacao.blade.php` | `CAMINHOS_DO_KIT` (`resources/views/{auth,errors,filament,livewire,svg}`); o `DuasRotas` só desce um nível abaixo do topo | View movida para `tests/Browser/Fixtures/` |
| `KitArte::CLIPES` | `IMAGENS`; `publicar():134`; `montarGif` `:206`/`:228`; `import-export-csv.md:13` | `IMAGENS` só com os órfãos; exceção do `densidade`; nome do GIF mantido |
| "`composer dev` = servidor + fila + vite" | `README.md:365`, `README.en.md:366`, `instalacao-avancada.md:168`/`:166`, `pacotes-instalados.md:141`/`:140` | 4 linhas nos `docs/` |
| "`schedule:work` vem no `composer dev`" | `routes/console.php` (linha 20 antes da entrega), `roteiro-de-features.md:150`/`:148`, `wikis/arquitetura.md:375` | 3 linhas |
| "passkeys" | `README.md:201`, `README.en.md:201`, `pacotes-instalados.md:23` ×2, `roteiro F-05 :32`/`:30`, `wikis/pacotes.md:10` | 3 linhas (reescritas, nunca apagadas) |
| "password" | `README.md:32` e `:97` (pt e en), `CustomizadorDaInstalacao.php` (linha 295 antes da entrega), `install.gif` | `CustomizadorDaInstalacao` e o caso no teste `R9` |

### Auditoria Ponytail (step 6)

| # | Achado | Aplicada? | Onde |
|---|---|---|---|
| 1 | A:L485-493 (defaults de config no DG-01) | aceito, com regra | A guarda do DG-01 confere tudo que o diagrama desenha, e só isso. Se o implementador rotular "SQLite (padrão)" ou "llama.cpp (padrão)", a guarda passa a ler o default no fonte do `config/*.php`; sem rótulo de default, sai. Escrever essa regra no passo 3 e no ADR-06 |
| 2 | A:L655-657, B:7 (DG-03 copiado em `autenticacao/index`) | aceito | `autenticacao/index` ganha só um link para o DG-03 da página de diagramas. Sai a checagem de texto idêntico do DG-03 (a do DG-01 README × página fica) |
| 3 | A:L759-761, L798-801, L851-852, L884-885, L1034-1035 (datasets que repetem testes de comportamento existentes) | aceito, com o limite declarado | A guarda do DG confere a estrutura que o diagrama desenha (rotas, enums/valores, métodos, chaves, ordem por Reflection) contra o código. O comportamento dos ramos já é provado pelos testes de feature citados pelo revisor — o `it()` do DG cita esses testes num comentário `// comportamento: tests/Kit/…Test.php [CT-nn]`. O ADR-06 ganha o limite: mudar o comportamento de um ramo e atualizar só o teste de feature não reprova a guarda do diagrama; o comentário é o ponteiro para quem mexer |
| 4 | B:3, ADR:13 (`{{` na camada 1) | aceito | O `[CT-18]` já cobre `docs/`; o README não passa pelo Liquid |
| 5 | B:4 (lista branca de 4 tipos) | aceito | Substitui a lista negra: primeira linha significativa do bloco ∈ {`flowchart`, `sequenceDiagram`, `stateDiagram-v2`, `erDiagram`}; barra também front matter e `%%{init}%%` no topo |
| 6 | ADR:14 (tirar a checagem de tipo do Pest) | recusada: o gate do site só roda em push na `main` (não há gate de PR para o site) e ninguém confere o render do GitHub | A lista branca do B:4 é uma linha e pega antes do merge |
| 7 | B:5 (página en irmã) | aceito | `[CT-04]`/`[CT-34]` já exigem |
| 8 | B:6, ADR:16 (constante com os 20 IDs / completude) | aceito | Cada `it('[DG-xx]')` acha o próprio bloco pelo marcador `%% DG-xx` e falha se ele sumir |
| 9 | ADR:12, B:9, B:10 (comparação pt=en separada; marcadores no irmão; extrator em `tests/Pest.php`) | aceito | A camada 2 roda `->with(['pt', 'en'])` e confere o bloco de cada idioma contra o fato do código — a paridade sai de brinde. O extrator de IDs por tipo continua (a camada 2 precisa dele), mas mora local em `DiagramasDaArquiteturaTest.php`. O irmão `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` só afirma código (não lê diagrama); o lado do diagrama dessas metades é lido no arquivo Kit |
| 10 | B:11 (metade de middleware do DG-20) | aceito | O DG-20 no irmão fica só com `hasTenancy()` = `kit.tenancy.enabled` |
| 11 | B:13 (precedente de Reflection) | aceito | `ReflectionMethod` + `getStartLine`/`getEndLine` (`tests/Kit/RaizDeUrlRegistradaTest.php:ReflectionMethod:162`) + `codigoSemComentario()` (`tests/Pest.php`, linha 1264 na data; hoje `tests/Pest.php:codigoSemComentario:1914`) + `preg_match_all('~\$this->(\w+)\(~')`, função local com os 4 usos. Conferir as duas citações com `sed -n` antes de gravar |
| 12 | B:14 (controle positivo "12 métodos") | aceito | Mantido só no DG-19, onde a asserção é negativa |
| 13 | B:17 (reuse `caminhosDoKit()` e o laço de `caminhosDeTopoQueViajam()`) | aceito | Conferir as citações (`tests/Pest.php`, linha 1242 na data, hoje `tests/Pest.php:caminhosDoKit:1855`; `tests/Kit/DuasRotasDeEntregaTest.php:caminhosDeTopoQueViajam:181`) |
| 14 | ADR:17 (guardas do DG-17 e DG-18 nos arquivos onde os helpers já moram) | aceito | `it('[DG-18]')` em `tests/Kit/MysqlNoDockerTest.php` e `it('[DG-17]')` em `tests/Kit/DuasRotasDeEntregaTest.php`, cada um com `->skip(fn () => ! naArvoreDoKit(), …)` no próprio caso (padrão `tests/Kit/AcoesPinadasPorShaTest.php:skip:72`, e o `[CT-10]` de `RedeDeDocumentacaoTest` exige sentinela no caso quando o caminho literal aparece). Nada se move para `tests/Pest.php`. Registrar no passo 14 onde cada `DG` mora |
| 15 | B:20, ADR:24 (chave do clipe = nome do arquivo) | aceito | `fluxo-import-export`, `install`, `busca-spotlight`, `login-unificado`, `densidade` → `art/{clipe}.gif` sem exceção |
| 16 | B:21, B:23, ADR:25, A:L138-141 (origem alternativa `art/` para `densidade`) | aceito | Uma origem só (`tests/Browser/Screenshots`); `publicar()` confere `IMAGENS` antes de pular quadro de clipe |
| 17 | B:22, ADR:27 (publicar os 2 órfãos) | aceito | Ficam como estão (o `kit:arte` os relata como ignorados). Viram dívida declarada no 01 (seção Riscos ou Dívidas): violam o par de `.ai/rules/testes-browser.md`, e nenhum RQ pede a correção |
| 18 | B:25 (uma fixture só para o install.gif) | aceito | Uma view Blade em `tests/Browser/Fixtures/` com a transcrição inline e um `$ate` para recortar cada quadro |
| 19 | ADR:26 (cortar os últimos quadros do GIF atual com ffmpeg) | recusada: a opção que o mantenedor escolheu diz "Refazer o GIF e corrigir o texto e o resumo" (Adendo 2); o GIF atual também omite etapas que o código executa hoje ("Gerando senha do administrador", "Formatando o código gerado"); cortar o último quadro tira a URL e o login do fim e deixa o resto defasado | Mantém o refazer, com a fixture única do B:25 |
| 20 | B:26 (fixar o cenário em `CapturaDeArteTest.php`) | aceito | — |
| 21 | B:27 (grep na fixture × `assertDontSee`) | aceito | Fica o `assertDontSee` (as duas formas), que roda em todo `composer art` |
| 22 | B:29 (5 contagens à mão) | aceito | A Verificação Final roda os testes que guardam os números; o número certo sai da falha |
| 23 | B:30 (perguntas respondidas) | aceito | Fica a tabela de decisões da sessão + os riscos 2, 3, 5 (medição na implementação) na seção Riscos |
| 24 | ADR:1, A:L427-432 (sentinela manual do `dist/`) | aceito, com substituto | Sai o grep manual. Entra na Verificação Final: `npm ci && npm run build && node verifica-links.mjs && node verifica-acessibilidade.mjs` rodados localmente em `site/` antes do PR — o `pages.yml` só roda em push na `main`, então sem isso a primeira vez que o plugin é exercido é depois do merge |
| 25 | ADR:2 (seletor `.mermaid svg`) | aceito | — |
| 26 | ADR:5 (ADR-03 inteira) | aceito | Fundir em ADR-02 (o fato do `[CT-21]` vira uma linha nas alternativas) |
| 27 | ADR:20 (ADR-07 inteira) | aceito | O dado útil (teto do `[CT-13]`: 756/767, hoje 445/446) vai para o passo 3 do 01 |
| 28 | ADR:30 (entrada do `AgenteIa.php` na tabela de correções) | recusada: e a retratação do construtor está ERRADA | O docblock de `app/Models/AgenteIa.php`, linhas 75-76 na data ("a temperatura vai direto para o provider como número"), é falso: `vendor/laravel/ai/src/Gateway/TextGenerationOptions.php:forAgent:69-76` só lê `temperature()`/`maxTokens()` por método ou atributo do agente, e `grep -rnE "temperature|maxTokens" app/Ai/` não acha nenhum. A entrada fica na tabela de correções do ADR-10 e do passo 1; o parágrafo que a retira sai. O docblock passa a dizer a verdade: o cast é `float`, e o valor do catálogo ainda não chega ao SDK (`AgenteBase` não implementa `temperature()`). Irmã: `app/Ai/Agents/GuardaPrompt.php:max_tokens:19` — mesma correção. Não mudar o comportamento de `AgenteBase` (fora do escopo; vira achado para o mantenedor) |
| 29 | ADR:34 (custo de integrar o sent.dm) | parcialmente rejeitado | O RQ-15 pediu avaliar o sent.dm como funcionalidade do kit — a avaliação É o custo. Encolher para 3 linhas (dependência pré-1.0, credencial + HMAC, dado pessoal fora do país sem LGPD na política), sem tabela de preços |
| 30 | ADR:37 (Superfície Livewire) | aceito | Uma linha com a evidência: nenhuma página/widget/componente; `KitArte` é Command |
| 31 | todos os demais | aceitos | Aplicar como o revisor descreveu |

**Linhas resultantes** (`wc -l`): `01-plano-acao.md` 1.584 → 1.426; `02-decisoes-arquiteturais.md` 842 → 625.

## Despachos

<!-- Claude Code: uma linha por disparo de sub-agente, com o modelo, o que ele NÃO recebeu e a
     auditoria do retorno. Host sem sub-agente: uma linha "Sem despacho — host sem sub-agente".
     Tarefa que rodou em linha por exceção: "Sem despacho — {motivo}".
     Auditoria REPROVADA também é linha (com o redespacho ao lado); fallback para general-purpose
     por agente fw-* indisponível vai na coluna Modelo. -->

Modelo da sessão orquestradora: Opus 5.5 (`claude-opus-5-5`). "herdado" = sem `model` explícito no `agent()` do Workflow, que herda o modelo da sessão (o Workflow não é `general-purpose`, e sim o sub-agente padrão de workflow).

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `gitdiagram` — estudo do GitDiagram + Mermaid verbatim (workflow `pesquisa-diagramas-do-kit`) | herdado (Opus 5.5) | 01, 02 (não existiam) | funcionalidades, geração, Mermaid 6.021 B (sha256 conferido em duas leituras GET), riscos | não reportado — workflow `pesquisa-diagramas-do-kit` (o host reporta por workflow; o custo dele não foi registrado) | sessão conferiu o Mermaid e as provas negativas; nenhum POST de geração |
| 2 | 3 | `fidelidade-gitdiagram` — auditoria nó a nó do Mermaid (pipeline após #1) | herdado | — | 23 nós (17 corretos, 6 imprecisos), 27 arestas (26/1), 7 ausentes, 10 omissões | idem #1 | sessão conferiu amostras (`DestinoAposLogin`, `AgenteIaResource`) |
| 3 | 3 | `site-e-readme` — superfícies de publicação e testes-guarda | herdado | — | matriz de impacto de 20+ CT; achou a contagem de specs 71→72 já reprovando | idem #1 | sessão reconferiu `tests/Kit/SiteDeDocumentacaoTest.php:it:990` e o `find` |
| 4 | 3 | `tipos-de-diagrama` — catálogo web de tipos Mermaid | herdado | — | GitHub em Mermaid 11.17.2; `usecase-beta` não renderiza no GitHub | idem #1 | URLs citadas; aceitas como fonte externa |
| 5 | 3 | `inv-acesso` — inventário de acesso/auth/tenancy | herdado | — | 431 citações (script do agente: 0 falhas) | idem #1 | cruzado pelo crítico (#8) |
| 6 | 3 | `inv-ciclo-de-vida` — inventário do ciclo de vida | herdado | — | D1–D6 (divergências doc × código) | idem #1 | cruzado pelo crítico (#8) |
| 7 | 3 | `inv-ia-infra-dados` — inventário IA/infra/dados | herdado | — | pipeline da IA, N1–N10 | idem #1 | sessão reconferiu N1 (`TextGenerationOptions::forAgent`) |
| 8 | 3 | `critico-inventarios` — completude e exatidão (barreira após #5–7) | herdado | — | ~250 citações, 4 erros de caminho, F1–F5, 13 lacunas, 6 possíveis invenções | idem #1 | aceito; suas correções vencem os inventários |
| 9 | 3 | `sent-dm` (workflow `pesquisa-adendo-videos`) | herdado | — | sent.dm = API de SMS/WhatsApp/RCS; vídeo é do GitDiagram `/video` | não reportado — workflow `pesquisa-adendo-videos` | sessão conferiu o 302 do link de patrocínio no texto do retorno |
| 10 | 3 | `gifs-e-videos` | herdado | — | pipeline `kit:arte`; `install.gif` com `password` (RQ-10) | idem #9 | sessão reconferiu `SenhaDoAdministrador::ehUtilizavel` e `README.md:32/97` |
| 11 | 4 | `construtor-01-02` (workflow `wiki-diagramas-01-02-e-revisao`) | sonnet | a conversa | 01 (1.274 l) e 02 (785 l), 12 ADRs, 5 perguntas | não reportado — workflow `wiki-diagramas-01-02-e-revisao` | reprovado em parte: afirmou "75/75 ok" e o script da skill deu 69 ok / 6 ERRO; retratou por engano a correção do docblock de `AgenteIa` (a sessão reverteu no step 6) |
| 12 | 5 | `citacoes` — script de citações | haiku | — | "3 OK / 10 ERRO" | não reportado — workflows do step 5, sem nome registrado | reprovado em parte pelo analista (#16): 4 "linhas erradas" estavam certas; o total contou só as linhas impressas |
| 13 | 5 | `restricoes-site-testes` | haiku | — | tabela premissa × código | não reportado — workflows do step 5, sem nome registrado | reprovado em parte: CT-20 citado na linha errada; 6 "diverge" eram estado futuro; não viu o `package-lock` |
| 14 | 5 | `fatos-das-guardas` | sonnet | — | viabilidade por DG | não reportado — workflows do step 5, sem nome registrado | aceito com ressalva: não pegou DG-11 e DG-12 inviáveis como escritos |
| 15 | 5 | `classe-irma` | haiku | — | listas paralelas | não reportado — workflows do step 5, sem nome registrado | reprovado em parte: `phpunit.xml`, linha 41, errado (é a 39); conclusão "tudo previsto" falsa |
| 16 | 5 | `analista-step5` — julga e corrige a wiki | herdado (Opus 5.5) | a conversa | 36 correções; citações 102/102 (script da skill) e 149/149 (estendido) | não reportado — workflows do step 5, sem nome registrado | sessão conferiu `git status` (só a wiki) e releu as perguntas |
| 17–19 | 6 | `ponytail-01-a`, `ponytail-01-b`, `ponytail-02` (workflow `ponytail-review-da-wiki-de-diagramas`) | herdado | a conversa | −105, −145 e −270 linhas possíveis | não reportado — workflow `ponytail-review-da-wiki-de-diagramas` | sessão julgou cada achado (`ponytail-julgamento-da-sessao.md`): maioria aceita, 3 rejeitados (install.gif por corte de quadro; tirar a lista branca de tipos; retirar a correção do `AgenteIa`) e 1 parcial (custo do sent.dm) |
| 20–21 | 6 | `aplica-01`, `aplica-02` — aplicam o julgamento (arquivos disjuntos, em paralelo) | sonnet | a conversa | 01: 1.584→1.426 l; 02: 842→625 l | idem #17–19 | conferido por #22 |
| 22 | 6 | `conferencia` — citações e referências cruzadas pós-edição | sonnet | — | 139 ok / 1 ERRO; 4 resíduos | idem #17–19 | aceito; a sessão corrigiu os 4 resíduos em linha (citação `forAgent:69-76`, símbolo em `GuardaPrompt.php:max_tokens:19`, `[DG-03]` no passo certo, `npm ci` antes do PR) |
| 23 | 4 | `deriva-04` (`feature-test-design`) — deriva o `04` e o `05` a partir do `00` + recorte de 85 linhas do `01` (paths, rotas, Superfície de UI, dependências) | herdado (Opus 5.5) | `01`/`02` inteiros, a conversa | `04`: 38 regras, 55 cenários, 134 mutantes; `05`: 3 CT-B, 11 mutantes; 18 perguntas | não reportado — workflows da derivação do `04` e da revisão adversarial, sem nome registrado | citações do `04`/`05` conferidas pelo script estendido da sessão (125 ok; 2 "ERRO" são a citação envelhecida `KitServiceProvider.php`, linha 172, reproduzida de propósito); `git status` só a wiki |
| 24 | 4 | `monta-03` — monta este `03` espelhando os 21 passos do `01` | **sonnet** | a conversa, o `04` | 308 linhas, 20 seções de passo ("8 e 9" unidas, como no `01`), 91 checkboxes abertos | não reportado — workflows da derivação do `04` e da revisão adversarial, sem nome registrado | `grep -n '^- \[x\]'` → só o exemplo dentro do comentário do template |
| 25 | 4 | `adversario-c1` — revisão adversarial cega (`fw-adversario-ct`) | herdado (Opus 5.5) | `01`, `02`, `03`, o código da feature | 18 achados (3 alta, 13 média, 2 baixa) | não reportado — workflows da derivação do `04` e da revisão adversarial, sem nome registrado | aceito: todos com implementação errada concreta |
| 26 | 4 | `revisa-04-c1` — fecha os achados do ciclo 1 | herdado | `01`/`02` inteiros | CT-56..CT-77; 186 mutantes | não reportado — workflows da derivação do `04` e da revisão adversarial, sem nome registrado | revisto pelo ciclo 2 |
| 27 | 4 | `adversario-c2` — re-revisão cega (teto da skill: 2 rodadas) | herdado | idem #25 | 22 achados (3 alta, 16 média, 3 baixa) — **não convergiu** (18 → 22) | não reportado — workflows da derivação do `04` e da revisão adversarial, sem nome registrado | aceito; os 3 estruturais (A2-01..A2-03) escalados ao mantenedor como premissas P-29..P-31 |
| 28 | 4 | `revisa-04-c2` — fecha os achados do ciclo 2 | herdado | `01`/`02` inteiros | 46 regras, 104 cenários (CT-01..CT-104), 259 mutantes no `04`; 43 perguntas | não reportado — workflows da derivação do `04` e da revisão adversarial, sem nome registrado | **sem 3ª revisão** (teto); a sessão incorporou as 43 perguntas ao `00` e decidiu P-03 e P-06 |

**Sem despacho — em linha, por exceção**: captura verbatim do `00` e dos Adendos 1 e 2 (a skill proíbe passar a fonte por resumo de sub-agente); as quatro perguntas de escopo ao mantenedor; as decisões 1/7/8 da sessão sobre as perguntas do 01; o julgamento do ponytail; a conferência do docblock de `AgenteIa` (`grep -rnE "temperature|maxTokens" app/Ai/` → vazio).

### Rodadas 3 e 4 da revisão do diff (step 6.5 da 3.x → step 9 da 4.0.0)

*(alterado em 2026-09-29: transcrito no step 10, do quadro de despachos do orquestrador)* A numeração
continua a de cima. **Custo**: o host reporta tokens por **workflow**, não por agente — rodada 3,
workflow `wf_3a43ceb4-461` (8 agentes, 1.847.231 tokens, 127 min); rodada 4, 1ª tentativa,
`wf_8ce52eb7-c0e` (parado pela sessão, custo não reportado); rodada 4, retomada, `wf_c060de7c-439`
(6 agentes, 1.008.471 tokens, 70 min). A rodada 3 começou na feature-wiki 3.5.1 (step 6.5); o rebase
sobre a `main` levou a 4.0.0 (step 9), e a 4ª rodada já rodou com os agentes `fw-*` da 4.0.0.

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 29 | 6.5 → 9 | E3 — guardas normalizadas e de conteúdo (`fw-executor-ct`, rota executor-ct) | sonnet (definição do agente) | — | extrator normalizado, 21 CTs lendo o bloco real, 65 falhas nas mutações | no workflow | amostragem: 395/395 verdes conferidos pela sessão depois do rebase |
| 30 | 6.5 → 9 | E4 — KitArte e instalação (`fw-executor-ct`) | sonnet | — | CTs de RD2-01..08 | no workflow | falsificabilidade confirmada pelo conferente (#35) |
| 31 | 6.5 → 9 | F3 — docs (DG-11), construtor | sonnet | — | `;` da nota do DG-11 | no workflow | CT-B01 verde no #35 |
| 32 | 6.5 → 9 | F5 — código, construtor | sonnet | — | RD2-03..08 | no workflow | 7 falhas com o `app/` de HEAD (#35) |
| 33 | 6.5 → 9 | F4 — contraste do site, construtor | sonnet | — | `kit.css` escopado ao tema escuro | no workflow | CT-B01/CT-B02 verdes (#35) |
| 34 | 6.5 → 9 | F6 — job `site` no CI, construtor | sonnet | — | job novo, actions pinadas | no workflow | `AcoesPinadasPorShaTest` verde |
| 35 | 6.5 → 9 | V — conferente, mecânico | sonnet | — | tudo verde **exceto o CT-26** (citação velha), com prova de falsificabilidade | no workflow | achado confirmado pela sessão |
| 36 | 6.5 → 9 | R3 — revisão cega do delta (`fw-revisor-diff`) | opus (definição do agente) | o briefing da rodada (recusado pelo próprio revisor), `01`, `03` | 12 achados, RD3-01..12 (3 Major) | no workflow | amostragem: RD3-01 e RD3-11 conferidos; a correção sugerida para uma citação (`:469`) estava errada — a certa era `:506`; `git status` idêntico no início e no fim |
| 37 | 9 | E5 — guardas (`fw-executor-ct`), 1ª tentativa da rodada 4 | sonnet | — | interrompido quando a sessão parou o workflow | não reportado | trabalho parcial no disco, retomado pelo #39 |
| 38 | 9 | E6 — instalação e arte (`fw-executor-ct`), 1ª tentativa | sonnet | — | **interrompido — erro da sessão**: um `SendMessage` da sessão para o agente em execução criou uma 2ª instância paralela dele (mesmo id, transcrição bifurcada), que escreveu linhas de depuração no arquivo do lote; o `TaskStop` parou as duas, e o workflow foi parado para não seguir com os testes incompletos | não reportado | **auditoria reprovada**; incidente registrado como memória da sessão |
| 39 | 9 | E5 — continuação (`fw-executor-ct`) | sonnet | — | **retorno vazio**: cortado pelo `maxTurns: 40` da definição 4.0.0 do agente, depois de restaurar os próprios arquivos e antes da verificação final | no workflow | **reprovado como retorno**; a revisão cega (#44) conferiu o trabalho: RD3-05 fechado, RD3-06 parcial, e uma regressão no DG-03 (RD4-03) |
| 40 | 9 | E6 — continuação (`fw-executor-ct`) | sonnet | — | **retorno vazio**: cortado pelo `maxTurns: 40` **entre** mutar `tests/BrowserTenancy/CapturaDeArteTest.php`, linha 718, para a prova do RD3-07 e restaurá-lo — a mutação ficou na árvore | no workflow | **reprovado como retorno**; o F7 (#41) reportou; a sessão restaurou o arquivo (diff = só a linha da mutação) e confirmou o CT-50 verde |
| 41 | 9 | F7 — código e docs das mensagens, construtor | sonnet | — | RD3-01/03/09/12, hint da senha, README 1.798 casos e 170/197 arquivos | no workflow | 5 CTs novos, 0 verdes com o `app/` de HEAD (#43) |
| 42 | 9 | F9 — `permissions` no job `site`, construtor | sonnet | — | RD3-11 | no workflow | testes do CI verdes |
| 43 | 9 | V — conferente, mecânico | sonnet | — | tudo verde **exceto** o `lint:check` (Pint em 2 arquivos de teste) | no workflow | achado confirmado pela sessão |
| 44 | 9 | R4 — revisão cega do delta (`fw-revisor-diff`) | opus | o briefing da rodada (recusado pelo revisor), `01`, `03`; o relatório do conferente veio colado e não foi usado como prova | 10 achados, RD4-01..10: 1 Blocker (RD4-01, regressão), 4 Major (RD4-02, RD4-03 regressão, RD4-04 Pint, RD4-10 pré-existente) | no workflow | RD4-01 reproduzido pela sessão com o ffmpeg 8.1 real; `git status --porcelain` igual antes e depois |

**Sem despacho (sessão, em linha), rodada 3** — RD3-02 (CT-26) e RD3-10 (citações velhas): edição
cirúrgica mecânica, conferida linha a linha, `CitacoesDeCodigoTest` verde. Rebase sobre a `main`
(`9c81fb1`): conflito só nos contadores do README, recalculados; 9 citações do `KitUpdate.php`
deslocadas pelo PR #125 e 4 que já estavam erradas na `main`.

**Sem despacho (sessão, em linha), pelo Adendo 5** — RD4-01 (`-f gif` + ffmpeg falso que escolhe o
formato como o real: 8 falhas antes, 28/28 depois, ffmpeg real exit 0 e `GIF89a`), RD4-03 (fato do
DG-03 lendo o rótulo: vermelho com a troca de rótulos em pt e em en, separadamente), RD4-04 (Pint),
RD4-06 (citações). Motivo: correções pequenas decididas pelo mantenedor como exceção ao RQ-39, sem nova
revisão cega (RQ-43); cada uma provada vermelha antes.

### Step 10 e o fechamento antes do quality gate

| # | Step | Agente / tarefa | Modelo efetivo | Não recebe | Resultado | Custo (workflow) | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 45 | 10 | mecânico-1 — os 5 scripts de conferência | sonnet | — | rastreabilidade 29 linhas, citações 276, ids-ct, conformidade | `wf_620bf4f5-046` (5 agentes, 1.313.673 tokens, 133 min) | saída literal usada como insumo do #46 |
| 46 | 10 | reconciliação (itens 1-8 e 11) | opus (herdado) | — | 01..05 e CHANGELOG reconciliados; DV-01..DV-10; A-01..A-07 | idem | amostragem: A-01..A-03 conferidos por `grep`; A-04 era defeito da sessão |
| 47 | 10 | mecânico-2 — reconferência | sonnet | — | sobrou RQ-36 sem CT e o CT-B03 com padrão incompleto | idem | — |
| 48 | 10 | correção da reconferência | opus (herdado) | — | R47 e CT-105 no `04`; CT-B03 era padrão faltando | idem | aceito |
| 49 | 10 | suíte | sonnet | — | Unit/Feature/Kit/Tenancy 3.565/3.569 (1 falha: CT-27, causa da sessão; 3 skipped); Browser não concluiu por memória (~340 MB livres, ~37 processos Chrome alheios), um arquivo isolado verde; Pint, PHPStan e FilaCheck verdes | idem | a sessão corrigiu o CT-27 e o conferiu |
| 50 | 10 | deriva-guardas (`feature-test-design`) | opus (herdado) | o `01`/`02` | CT-106..CT-116 (R48..R53); o CT-114 aponta o `alt` errado do DG-20; Q?1/Q?2 de desenho | `wf_a7dc1003-d34` (4 agentes, 817.282 tokens, 45 min) | Q?1/Q?2 decididas pela sessão |
| 51 | 10 | T1 — CT-105 (`fw-executor-ct`) | sonnet | — | verde; M1..M6 vermelhos em memória; o hook negou um `git diff` sem `':(exclude)wikis'` | idem | aceito |
| 52 | 10 | T2 — CT-106..116 (`fw-executor-ct`) | sonnet | — | **retorno vazio**: cortado pelo `maxTurns: 40` ainda lendo o contexto (49 chamadas, nenhuma escrita) | idem | refeito pelo #54/#55 |
| 53 | 10 | conferência | sonnet | — | 412 + 35 verdes; README 1.799 | idem | — |
| 54 | 10 | L-kit — CT-106..111, 115, 116 | sonnet, **fallback `general-purpose`** (o `fw-executor-ct` não cabe em 40 turnos): contrato por prompt, sem hook nem restrição de ferramenta | `01`/`02` | `tests/Kit/GuardasDosDiagramasTest.php`, 47/47, 387 asserções; nota: o `bezhansalleh/filament-exceptions` agenda um `model:prune` próprio às 00:00 (8 eventos, não 7) | `wf_3342ddbb-ba0` (4 agentes, 765.171 tokens, 43 min) | prova por desligar o check na guarda e por linhas de dataset com mundo alterado |
| 55 | 10 | L-tenancy — CT-112..114 | sonnet, fallback `general-purpose` | `01`/`02` | CT-112/113 verdes; **CT-114 linha 5 vermelha, causa (b)**: o `alt` do DG-20 dizia que sem vínculo dá 404, e o `master_global` entra | idem | defeito real, roteado ao #56 |
| 56 | 10 | DG-20, construtor | sonnet | — | `alt` e `accDescr` corrigidos em pt e en; 45 + 412 + 47 verdes | idem | aceito; o en usa "no link", a forma do oráculo |
| 57 | 10 | conferência | sonnet | — | 504/504; README pede 1.810 e 171/198; ids-ct só com o CT-39 | idem | a sessão atualizou o README e conferiu o `SiteDeDocumentacaoTest` (68/68) |

**Sem despacho (sessão, em linha), step 10** — A-04 (a citação do sobrevivente voltou à forma que o CT-27 exige), os contadores do README (1.810 casos, 171/198 arquivos), as decisões de desenho Q?1 e Q?2 e este registro.

**Lacuna de registro, declarada** — os despachos da **implementação** (fases A, B e C1: construtores
dos passos 1 e 2, executores do `04`, construtores dos grupos de diagramas) e das **rodadas 1 e 2** da
revisão do diff (`fw-revisor-diff` + `/code-review` da rodada 1, lotes E1/E2/F1/F2, conferentes, o
executor dos CT-B e a revisão cega da rodada 2) não foram registrados aqui na hora. Os retornos estão
no scratchpad da sessão (`impl/faseA-*.md`, `faseB-*.md`, `faseC1-*.md`, `passo65-*.md`,
`passo65c-*.md`, `final-ctb.md`, `final-revisao2.md`, `suite-completa.log`), sem modelo nem custo por
agente. **Step 10**: as conferências mecânicas e esta reconciliação rodaram em sub-agentes do workflow
do orquestrador, que registra a linha delas.

**Notas das rodadas 3 e 4 para a skill**:

- O **`maxTurns: 40`** dos executores da feature-wiki 4.0.0 cortou os dois lotes de teste da rodada 4
  retomada sem retorno — a skill declara esse número como "hipótese a calibrar". Um deles foi cortado no
  meio de uma prova de mutação, e a mutação ficou na árvore real: prova de mutação feita no arquivo
  real, com restauração depois, é frágil a corte por teto de turnos; a cópia fora do repositório não é.
- O **hook da 4.0.0** (`guarda-subagente.sh`) negou `mkdir`, `cp` e `git stash list` ao
  `fw-revisor-diff` até fora do repositório; o revisor contornou com `php -d auto_prepend_file` e sondas
  no scratchpad.

### Ciclo 2 do quality gate (Adendo 6) — o quadro deste ciclo

*(alterado em 2026-09-29: subseção nova, do step 10 do ciclo 2)* Os lotes rodaram no workflow do orquestrador do
ciclo 2. **Custo**: o host reporta tokens por workflow, e o total deste só sai quando ele termina — nenhum
sub-agente o vê; a linha fica para o orquestrador. **Modelo**: os relatórios dos lotes não o declaram, e o
reconciliador não recebeu o quadro de despacho; só o da própria linha (#65) é conhecido.

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 58 | 11 (destino 3) | CEN — `feature-test-design` sobre o `06`: QA-03..QA-06, QA-08..QA-10 | não declarado no retorno | o `01` e o `02` | `04`: R54..R62, CT-117..CT-132, CT-03/CT-85/CT-89 reescritos, linhas novas em CT-95 e CT-103, mapa `[RD…]` → CT; `05`: CT-B04..CT-B06 | no workflow do ciclo 2 (total só no fim dele) | aceito: os números do cabeçalho do `04` batem com os `grep -c` (132 · 62 · 353 · 3; no `05`, 6 e 24); a revisão adversarial que o gatilho pede (Impacto 3, área H) não foi feita — é da sessão |
| 59 | 11 (destino 3 → 2) | T1 — executor de CT, diagramas: `tests/Pest.php`, `DiagramasDaArquiteturaTest`, `DiagramasDaArquiteturaTenancyTest`, `GuardasDosDiagramasTest` | não declarado | o `01` e o `02` | CT-03 e CT-85 sobre o publicado, CT-124..CT-126, CT-129..CT-132, o CT-89 por idioma, os clones fora (QA-07), a linha nova do CT-103; Pint nos quatro arquivos | idem | **reprovado em parte**: `DiagramasDaArquiteturaTest` 439/441 — as duas linhas de cópia do CT-132 leem o bloco publicado sem aplicar a alteração e ficam vermelhas depois que o C1 tirou o português do en; Tenancy 45/45, Guardas 47/47 |
| 60 | 11 (destino 3) | T2 — executor de CT, instalação e arte | não declarado | não declarado | CT-118 novo (QA-10), o `[CT-nn]` nos `[RD…]` do instalador e do `kit:arte`; prova de falsificabilidade fora do repositório | idem | **reprovado em parte**: CT-117, CT-119, CT-120, CT-121, CT-122 e CT-127 só ganharam o ID no nome (`git diff` desses `it()` = só o nome); os Exemplos e as asserções do `04` não entraram |
| 61 | 11 (destino 3) | T3 — conferidor do site, CT-B05 e CT-B06 (QA-06) | não declarado | não declarado | `site/verifica-acessibilidade.mjs`: fonte efetiva por SVG e rolagem dentro do bloco, com controles | idem | aceito: verde no build do ciclo 2 contra o CSS do C2 |
| 62 | 11 (destino 2) | C1 — docs pt e en: a frase da página (QA-01), QA-04, QA-08, QA-09, QA-11, os acentos do DG-19 | não declarado | não declarado | 11 arquivos de `docs/`, 66 inserções e 53 remoções | idem | aceito: CT-129, CT-130 e CT-131 verdes; nenhum teste editado |
| 63 | 11 (destino 2) | C2 — CSS do site (QA-06) | não declarado | não declarado | `site/src/styles/kit.css`, 85 linhas: o SVG no tamanho natural e a rolagem no bloco, alinhada ao início | idem | aceito: CT-B01, CT-B02, CT-B05 e CT-B06 verdes; axe sem serious/critical em 96 páginas |
| 64 | 11 (destino 2) | C3 — arte (QA-13) | não declarado | não declarado | a altura da captura medida no quadro final; o `art/install.gif` remontado (116.347 bytes) | idem | aceito em parte: CT-B03 e CT-B04 3/3; o GIF sai 1000 × 1484, os quadros 1 a 3 seguem com área vazia e o `WARN` do terminal segue no último |
| 65 | 10 (ciclo 2) | R — esta reconciliação: QA-01, QA-02, QA-11, QA-12, QA-14 e este quadro | Opus 5.5 (`claude-opus-5-5`) | a conversa | `01`, `02`, `03`, `04`, `05`, `06` (só citações), CHANGELOG e `wikis/glossario.md`; os cinco scripts da reconciliação | idem | do orquestrador |

### Fechamento do ciclo 2 (Adendo 7 e a revisão adversarial da adição) — 2026-09-30

*(alterado em 2026-09-30: subseção nova)* Modelo da sessão orquestradora: Fable 5.1 (`claude-fable-5-1`). **Fallback declarado**: os lotes de teste de backend rodaram como `general-purpose`/sonnet com `.claude/agents/fw-executor-ct.md` como contrato por prompt — o `fw-executor-ct` tem `maxTurns: 40` e não cabe em lote de 5 a 8 cenários (os despachos #39, #40 e #52 já tinham sido cortados por ele); perde-se o hook, não a cegueira (o `01`/`02`/`03`/`06` ficaram fora do prompt e nenhum lote os leu), e a integridade foi conferida por `git status --porcelain` e `md5sum .env` antes e depois. **Custo**: tokens e duração que o host reportou por agente. A passada anterior (a revisão adversarial da adição, rodada 1, e o fechamento dela no `04`/`05`) rodou no workflow `Fecha o ciclo 2 da wiki de diagramas`, sem custo reportado.

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 66 | impl. (destino 3) | T-A — CT-117, CT-118, CT-119, CT-120, CT-137, CT-138, CT-139 em `CustomizadorDaInstalacaoTest` | sonnet, fallback `general-purpose` (contrato do `fw-executor-ct` por prompt) | `01`, `02`, `03`, `06`, a conversa | 106 testes: 92 verdes, **14 vermelhos por causa (b)** — CT-118 (2), CT-137 (5), CT-138 (7), todos no limite 1 e no `#?` de `aplicar()`; KIT_DEMO fora (isolamento) | 190.490 tokens · 7,0 min | aceito: a saída literal de cada vermelho no retorno; `md5sum .env` igual; só o arquivo dele no diff |
| 67 | impl. (destino 3) | T-B — CT-121, CT-122, CT-133..CT-136 em `ResumoDoKitInstallTest` | idem | idem | 9/9, 105 asserções; arnês da senha digitada por `OutputStyle::askQuestion` e do `db:seed` que falha por `Artisan::registerCommand` | 132.799 · 6,1 min | aceito: amostragem de `chaveVemAntesDoSeed()` e do `Hash::check()` |
| 68 | impl. (destino 3) | T-C — CT-127, CT-128, CT-147 em `KitArteTest` | idem | idem | 29/29, 83 asserções | 90.589 · 1,6 min | aceito; a sessão tirou o prefixo `[RD3-09]` do nome do CT-147 |
| 69 | impl. (destino 3) | T-D — CT-140..CT-146, CT-148 em `DiagramasDaArquiteturaTest` + extrator em `tests/Pest.php`; `[CT-08]` por palavra inteira | idem | idem | 496 testes: 491 verdes, **5 vermelhos por (b)** — CT-144 (DG-06 pt/en, DG-08), CT-146 (DG-06 en), `[CT-08]`; Helpers + Tenancy 46/46 | 263.637 · 15,3 min | aceito; incidente declarado (dois trechos do `tests/Pest.php` apagados por script e reconstruídos de `git show HEAD:`), diff final só o extrator |
| 70 | 10 (CT-B) | T-E1 — CT-B07, CT-B08, CT-B09, CT-B11 e a prova vermelha da L-16 em `site/verifica-acessibilidade.mjs` | sonnet, `general-purpose` (arnês Node, não Pest) | `01`, `03`, `06` | conferidor exit 0 nas 4 linhas novas; L-16: exit 1 com o `kit.css` de `e597896^` (125 violações), exit 0 de volta | 118.026 · 7,5 min | aceito: as duas saídas literais; `kit.css` byte a byte igual a HEAD |
| 71 | 10 (CT-B) | T-E2 — CT-B04 (passos 5 e 7) e CT-B10 em `CapturaDeArteTest` | `fw-executor-ctb` (sonnet, hook) | `03`, a conversa | 1/1, 27 asserções; md5 dos 4 PNG distintos | 68.782 · 1,1 min | aceito: `assertScript` e `md5_file` conferidos no diff |
| 72 | 7 (re-revisão) | ADV-2 — re-revisão adversarial cega da adição (rodada 2 de 2) | `fw-adversario-ct` (opus, hook) | `01`, `02`, código, a conversa | 24 achados (ADV2-01..24): 15 implementações que passam, 3 oráculos fracos, 5 RQ/lacunas, 1 sonda | 226.638 · 7,5 min | aceito: cada achado com linha do `04`/`05`; a sessão decidiu os 24 (23 aceitos, ADV2-23 rejeitado) |
| 73 | 7 (fechamento) | CEN-2 — fecha os 24 no `04`/`05` pela `feature-test-design` | opus, `general-purpose` (analista) | `01`, `02`, `03`, `06` | CT-149, CT-150, linhas novas em 14 cenários e no CT-B05, R64 sem o "sem fallback", Q?12/Q?13 ✅, L-16 fechada, L-20; 150 · 65 · 427 · 5 | 409.782 · 25,7 min | aceito com duas correções da sessão: o controle do publicado do CT-143 (nasceu "recusa", virou "aceita" depois do DG-07) e a nota do CT-147 sobre outro clipe declarado; ADV2-24 foi para CT-71/CT-98 em vez de cenário novo (motivo aceito) |
| 74 | impl. (destino 2) | C-docs — DG-06 (chaves de provedor, sem "(provedor social)") e DG-08 (as duas chaves), pt e en | sonnet (construtor) | wiki, `03` | 4 arquivos; CT-08/CT-144/CT-146 verdes; achou o CT-143 com a nota antiga e o `[CT-50]` com 1.832 | 54.987 · 4,4 min | aceito; os dois achados roteados (#82 e a sessão) |
| 75 | impl. (destino 3) | T-A2 — segunda leva: CT-119/CT-120 (ADV2-19), CT-139 (3 ausentes), CT-150, CT-138 KIT_DEMO | sonnet, fallback `general-purpose` | `01`, `02`, `03`, `06` | 115/115, 500 asserções; `semearDemo()` isolado por `setBasePath()` com o `call()` neutralizado | 119.771 · 2,5 min | aceito; `md5sum .env` igual |
| 76 | impl. (destino 3) | T-B2 — CT-133 (lista e "ou"), CT-134 (banner sem administrador; forma fechada), CT-136 (senha impressa), CT-149 | idem | idem | 10/10, 127 asserções; `migrate:fresh` trocado por `forceDelete()` dos administradores entre as duas instalações (transação do sqlite) | 96.180 · 2,9 min | aceito |
| 77 | impl. (destino 3) | T-C2 — CT-128/CT-147 sobre a saída inteira | idem | idem | 29/29, 78 asserções; ambiguidade: as linhas dos outros quatro clipes declarados | 73.611 · 1,0 min | aceito; a sessão gravou a ressalva no `04` (R33) |
| 78 | impl. (destino 3) | T-D2 — CT-129, CT-130, CT-140..CT-146, CT-148, CT-71, CT-98 (+ `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`) | idem | idem | 525 testes: 523 verdes, **CT-129 DG-07 pt/en vermelho por (b)**; Tenancy 50/50 (+5 do CT-98); `grafoDoFluxo` e irmãs movidas para `tests/Pest.php` | 241.437 · 12,9 min | aceito; a sessão registrou a ambiguidade do controle do publicado do CT-143 |
| 79 | 10 (CT-B) | T-E1b — CT-B05 "escuro desde o carregamento" (ADV2-15) | sonnet, `general-purpose` | `01`, `03`, `06` | 7 combinações a 14,0 px; guarda de não vácuo do `prefers-color-scheme` | 73.362 · 3,3 min | aceito |
| 80 | impl. (destino 2) | C-docs-2 — DG-07 com `, se ele tem tenant_id (KIT_TENANCY)` nas duas mensagens, pt e en | sonnet (construtor) | wiki, `03` | CT-129 verde; 4 linhas de controle do CT-143 vermelhas (os `str_replace` da mutação viraram no-op) | 54.477 · 3,0 min | aceito; o vermelho roteado como causa (a) ao #82 |
| 81 | 10 | MEC-1 — `## Índice de Cenários` do `04` com o `it()` real de CT-117..CT-150, CT-71, CT-98; roteiro do `05` (linhas 6–14) | sonnet (mecânico) | `03`, a conversa | Índice e roteiro preenchidos; `ids-ct.sh` só com o CT-39 | 94.379 · 2,5 min | aceito; as citações que ele deixou velhas (o T-D3 ainda editava o arquivo) foram movidas pela sessão |
| 82 | impl. (destino 3) | T-D3 — controles do CT-143 sobre o DG-07 novo; `[CT-144]` `'%%'` em aspas duplas | sonnet, fallback `general-purpose` | `01`, `02`, `03`, `06` | 525/525, 3.831 asserções | 77.908 · 5,2 min | aceito; a sessão espelhou a troca do controle do publicado no `04` |
| 83 | 11 | `fw-qa-gate` — ciclo 2 do quality gate, cego | opus (hook) | a conversa; só o path da wiki, a URL do app (`http://127.0.0.1:8000`), o `git diff --stat` e a base `origin/main` | **REPROVADO → especificação**: 9 achados (3 Major, 6 Minor), 9 de 12 dimensões; K2 medido por arnês próprio (80 mutantes, 86,25 % em 230 s) | 339.549 tokens · 17,4 min | aceito: `06` entre `<<<06` e `>>>06`, gravado sem edição; `git status --porcelain` idêntico antes e depois (33 entradas); md5 de `.env` e `config/*.php` iguais; a amostragem confirmou QA-16 (`03:212` e `03:215` estavam velhas) e QA-17 (`grep -rn "mb_strtolower(Str::ascii" tests/` acha os dois) |

**Sem despacho (sessão, em linha), 2026-09-30** — as três perguntas ao `00` (P-45..P-47, captura de decisão); a correção de `SubstituicaoEmArquivo` e dos três chamadores (um método, com os 14 vermelhos já em mãos — exceção de "edição cirúrgica em arquivo com forte interdependência"); a decisão dos 24 achados; as citações (script + 23 à mão); o passo 27 e a Cobertura do `01`; o ADR-10; o CHANGELOG; o badge do README; o nome do CT-147; a troca do controle do publicado do CT-143 e a nota do CT-147 no `04`; a nota do roteiro do `05`; este `03`.

### Ciclo 3 — os nove achados do ciclo 2 (2026-09-30)

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 84 | 7 (destino 3) | CEN-3 — QA-18, QA-19 e QA-20 no `04` pela `feature-test-design`; Adendo 8 aplicado | opus, `general-purpose` (analista) | `01`, `02`, `03` | CT-129 com as duas `accDescr` (R58.M9/M10); L-21, Q?14/P-48; CT-151 (R64.M8..M12), Q?15; 151 · 65 · 434 · 5 | 218.840 tokens · 14,6 min | aceito; a sessão decidiu Q?15 e levou a P-48 ao `00` |
| 85 | impl. (destino 3) | T-A4 — CT-151 em `CustomizadorDaInstalacaoTest`, com a prova de mutação pelo arnês do gate | sonnet, fallback `general-purpose` | `01`, `02`, `03`, `06` | 5/5 verdes; `--mutate` 86,25 % → 92,68 %, os cinco mutantes alvo mortos | 83.509 · 11,5 min | aceito: as duas saídas literais do `--mutate`; `md5sum .env` igual |
| 86 | impl. (destino 3) | T-D5 — as duas `accDescr` no CT-129 em `DiagramasDaArquiteturaTest` | idem | idem | **4 linhas vermelhas por (b)** (DG-08 e DG-09, pt e en) e o CT-143 por efeito colateral; 524/527 | 56.911 · 5,8 min | aceito: saída literal com o texto das `accDescr` |
| 87 | impl. (destino 2) | C-docs-3 — as quatro `accDescr` com as chaves, pt e en | sonnet (construtor) | wiki, `03` | 527/527; achou o `[CT-50]` em 1.836 | 49.773 · 3,8 min | aceito; o badge atualizado pela sessão |
| 88 | — | (sessão, em linha) | — | — | Adendo 8; P-48; QA-16, QA-17, QA-21, QA-22, QA-23; Q?15 e o docblock; badge; este `03` | — | — |
| 89 | 11 | `fw-qa-gate` — ciclo 3 do quality gate, cego e último | opus (hook) | a conversa | **não despachado** (host sem memória; pendente) | — | — |

## Blockers
<!-- Impedimentos encontrados durante implementação -->
*(alterado em 2026-09-29: step 10 do ciclo 2 — o A-04 saiu desta seção: está fechado em `## Achados do step 10`, com o CT-26 e o CT-27 verdes, `tests/Kit/CoberturaDeTestesTest.php` 20/20 nesta passada. Os três abaixo deixam o `composer test` do CI vermelho)* *(alterado em 2026-09-30: os três fecharam — o CT-132 em `252826c`, o glossário em `ef68f6d`, o badge nesta passada (1.835); nenhum blocker aberto)*
- [x] ~~**CT-132 vermelho em duas linhas**~~ fechado em `252826c` — as linhas de cópia aplicam a alteração ao bloco publicado; `--filter="CT-132"` 7/7, 17 asserções, 2026-09-30
- [x] ~~**O glossário reprova duas guardas de entrega**~~ fechado em `ef68f6d` — `wikis/glossario.md` em `KitUpdate::CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:'wikis/glossario.md':315`) e o README com 12 documentos de referência; `KitUpdateTest` e `[CT-48]` verdes na suíte de 2026-09-30
- [x] ~~**O badge de casos de teste do README está velho**~~ fechado em 2026-09-30 — 1.835 nos dois READMEs; `tests/Kit/SiteDeDocumentacaoTest.php` 68/68 (`[CT-50]`)

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê. A correção está na fonte; aqui só o ponteiro. -->
- **D-01, passo 1** (passkeys): a palavra ficou nos READMEs e em `pacotes-instalados`, dizendo que estão desligadas, em vez de sair; o CT-42 aceita a menção com "desligadas"/"disabled". Fonte: `01`, passo 1 (item e Verificação)
- **D-02, passos 1, 22 e 24** (resumo do `kit:install`): de "muda o texto dessa linha, nada mais" para três valores, a correção pelo desfecho da semeadura, banner e resumo com a mesma instrução, a barra invertida no `.env` e a leitura do `.env` de destino em `SenhaDoAdministrador`. Fonte: `01` (Análise, passo 1, passos 22 e 24); `02`, ADR-10 (Consequências)
- **D-03, passo 2** (conferidor do site): espera por `data-processed`, e não por `svg[role~=…]`; erro reconhecido pela estrutura do `astro-mermaid` 2.1.0, e não pelo texto "Syntax error"; mais o CT-B01 exato e o CT-B02. Fonte: `01`, passo 2; `02`, ADR-02 (Alterações); `05`, CT-B01
- **D-04, passo 14** (guardas): testes com os IDs do `04`, não `[DG-xx]`; DG-17/18/19 no arquivo Kit; extratores e `blocoDoServico` em `tests/Pest.php`; o irmão com tenancy lê bloco. Fonte: `01`, Natureza e passos 14, 15, 16, 18; `02`, ADR-06
- **D-05, passos 5, 17 e 18** (os extras): ~~as guardas previstas para DG-10, DG-19 (metades 2 e 3) e DG-20 não foram implementadas, porque o `04` é o contrato e trata os extras pela lacuna L-01~~; e o DG-20 saiu sem o flowchart do `kit:tenancy`. Achados A-01 a A-03. Fonte: `01`, passos 5, 17, 18; `02`, ADR-06 *(alterado em 2026-09-29: as guardas existem desde a segunda passada do step 10, em `tests/Kit/GuardasDosDiagramasTest.php` e no irmão com tenancy, pelas R48..R53; o desvio que fica é o flowchart do `kit:tenancy`, fora pela decisão da sessão — lacuna L-08 do `04`; `02`, ADR-05 e ADR-08)*
- **D-06, passo 19** (`KitArte`): `QUADROS_DO_GIF` ficou, dentro do `CLIPES`; a publicação por temporário ao lado + `rename()` + `-f gif`; `Throwable` por clipe; o ffmpeg procurado diretório a diretório do `PATH`. Fonte: `01`, Análise e passo 19; `02`, ADR-09
- **D-07, passo 22** (contraste, RQ-33): ajustado em `site/src/styles/kit.css`, e não "na config do astro-mermaid" como dizia a opção do Adendo 3 — o pacote não tem configuração por tema. Desvio do texto da opção, não do objetivo; a cláusula RQ-33 diz "configuração do site". Fonte: `01`, Cobertura (RQ-33) e passo 22; `02`, ADR-02
- **D-08, passos 20 e 21** (capturas): a busca em `/app/{organização}/projetos`, com o resultado afirmado dentro do overlay; quatro quadros do `install.gif` com viewport 1400x2100, a fixture servida por rota registrada só no teste, e a máscara de 24 `X`. Fonte: `01`, passos 20 e 21; `05`, roteiro do CT-B03
- **D-09, Superfície de UI**: o gate de CT-B passou na derivação do `04`, contra o "N/A" do `01` — há `05` com três CT-B. Fonte: `01`, Superfície de UI
- **D-10, passo 3** (DG-01): quatro arestas corrigidas pela rodada 1 (RD-05); o link aponta para o stub `.html`; 490/491 linhas. Fonte: `01`, passo 3
- **D-11, passo 4** (DG-02, DG-03, DG-13): corrigidos pela rodada 1 (RD-04, RD-12, RD-06) e pelo RD4-03; o índice é uma tabela com os 20 DG. Fonte: `01`, passo 4
- **D-12, Cobertura e numeração**: faixas "a" viraram listas, os DG saíram da coluna de passos, o "8 e 9" virou dois passos e entraram os passos 22 a 25 e as linhas RQ-31..RQ-44. Fonte: `01`, Cobertura e Estrutura de Implementação
- **D-13, passo 26** (ciclo 2 do gate): o passo nasceu depois do plano, pelo Adendo 6; o `04` ganhou R54..R62 e o `05`, CT-B04..CT-B06. Desviam do cenário, e estão em `## 26.`: seis testes que só ganharam o ID (CT-117, CT-119..CT-122, CT-127), o CT-B04 sem os passos 5 e 7, e o CT-132 com duas linhas que não aplicam a alteração. Fonte: `01`, passo 26; `04`, `## Índice de Cenários`; `05`, roteiro do CT-B04 *(alterado em 2026-09-29)* *(alterado em 2026-09-30: os três desvios fecharam — `## 26.`, QA-03 e QA-09)*
- **D-14, passo 27** (Adendo 7 e a revisão adversarial da adição): o passo nasceu depois do plano; três decisões da sessão desviam do texto do `04`/`05` de antes: (1) `DB_CONNECTION` ausente passa a ser anexado como toda chave (o `04` dizia "sem fallback"; ADV2-06); (2) o `KIT_DEMO` do `kit:tenancy --demo` entrou no CT-138 e no CT-139 com o `semearDemo()` isolado por `setBasePath()` e o `db:seed` neutralizado (o `04` o dava como possível dívida); (3) o controle do publicado do CT-143 nasceu "recusa" e virou o controle positivo depois da correção do DG-07. E, no CT-147/CT-128, "nenhuma linha da saída" desconta as linhas sobre outro clipe declarado (o `kit:arte` reporta os quatro sem quadros). Fonte: `01`, passo 27; `02`, ADR-10; `04`, R33, R54, R59, R64
- **TIA** (item 10 do step 10): o `## Impacto em Features Existentes` do `01` não pôde ser confrontado com o TIA (ver a linha do TIA em `## Verificação Final`). Confrontado com a suíte sem browser: a única quebra fora dos arquivos previstos é o `CoberturaDeTestesTest` (A-04), que o `01` não listava — é o efeito colateral da correção de citação em `docs/{pt,en}/referencia/qualidade-de-codigo.md`

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->
- Os campos `temperatura` e `max_tokens` do catálogo de agentes no `/admin` não chegam ao provider — `TextGenerationOptions::forAgent` lê `temperature()`/`maxTokens()` do agente e `AgenteBase` não implementa; fora do escopo, corrigido só o docblock (passo 1); achado entregue ao mantenedor.
- O `astro-mermaid` 2.1.0 renderiza no cliente e, quando o bloco falha, **não** desenha o SVG de erro do Mermaid: troca o `<pre>` por um `<div>` com `<strong>Error rendering diagram:</strong>`. Toda detecção de erro precisa ser pela estrutura. Documentado no `02` (ADR-02) e no `05` (Seletores).
- O `;` dentro de uma nota de `sequenceDiagram` encerra o statement — um bloco que o GitHub e a guarda Pest aceitam quebra no site. Só o navegador (CT-B01) pegou; o job `site` do CI (passo 23) existe por isso.
- O tema `dark` do Mermaid 11.17.2 deixa o rótulo de aresta a 4,43:1, e o `astro-mermaid` não tem configuração por tema; o `<style>` do Mermaid prefixa cada regra com o ID do diagrama, então a correção de CSS precisa de `!important`. Documentado no `02` (ADR-02) e em `site/src/styles/kit.css`.
- No PHP, `rename()` entre volumes copia e devolve `true` — não é atômico e não falha. A publicação do GIF só é atômica com o temporário no mesmo diretório; e o temporário sem extensão `.gif` exige `-f gif`, senão o ffmpeg real recusa a saída. O ffmpeg falso da suíte precisou passar a escolher o formato como o real, porque era por aceitar o que o real recusa que a suíte ficou verde com o RD4-01. Documentado no `02` (ADR-09).
- `SubstituicaoEmArquivo::definirNoEnv` gravava a barra invertida sem escapar (o `preg_replace` consome o `\\`) — defeito de antes desta feature, que o resumo novo transformou em crash no meio da customização (RD2-08). Documentado no `01` (passo 22).
- A suíte completa pegou o que os arquivos isolados não pegavam duas vezes: o CT-05 do `MysqlNoDockerTest` contra o texto do DG-18 (rodada 2, `821d357`) e o CT-27 do `CoberturaDeTestesTest` contra a citação de `qualidade-de-codigo.md` (este step, A-04). Os conferentes das rodadas 3 e 4 rodaram listas de arquivos, não a suíte.
- O `rastreabilidade.sh` lê a coluna `Substitui` dos Adendos como substituição, e o `00` desta wiki a usou para "resolve/concretiza/reforça" — 13 `RQ` ficam fora da cobrança mecânica (A-07). A Cobertura do `01` as cobre por leitura.
- *(ciclo 2 do gate, QA-12)* **O `pestw.cmd` da `feature-wiki` falseia o `pest --mutate` no Windows**: o plugin relança os testes cobridores com `--filter="A|B"`, o `%*` do `cmd` lê a barra vertical como pipe, o processo sai com 255 em 0,22 s e o plugin conta o mutante como morto — 98,58 % no `CustomizadorDaInstalacao` pelo `pestw.cmd` contra 57,09 % pelo arnês do gate, que relança por `proc_open` com array. Com um teste cobridor só, funciona. Destino: a skill (DV-11); até lá, o K2 no Windows roda pelo arnês do scratchpad do gate, nunca pelo `pestw.cmd`.
- *(ciclo 2, QA-06)* O `useMaxWidth` padrão do Mermaid entrega o SVG com `width="100%"` e um `max-width` inline com a largura natural; o SVG só de `viewBox` sempre ocupa a largura do contêiner, e centralizar num contêiner flex com `overflow: auto` deixa o transbordo da esquerda fora da rolagem. Por isso o CSS dá ao SVG uma largura que nunca é o limite, deixa o `max-width` do próprio Mermaid cortar, e alinha ao início (`site/src/styles/kit.css`; ADR-02).
- *(ciclo 2, QA-11)* O leitor do `.gitattributes` de `tests/Kit/DuasRotasDeEntregaTest.php` reduz `/wikis/specs export-ignore` ao topo `wikis`, e trata `wikis` inteiro como fora do `create-project` — mas os `wikis/*.md` viajam. É o `KitUpdateTest` ("cobre as regras de IA e as wikis de referência") que governa esses arquivos, e ele não tem lista de exceção: todo `wikis/*.md` novo tem de estar em `KitUpdate::CAMINHOS_DO_KIT`. Registrado em `## 26.`, QA-11.
- *(2026-09-30, RQ-50)* O Dotenv e a carga do Laravel ficam com a **última** definição de uma chave repetida no `.env` — o `ImmutableWriter` só recusa a chave definida antes da carga —, então trocar só a primeira linha deixava os dois leitores com o valor velho; e o padrão `^#?\s*CHAVE=` com `/m` casava o comentário antes da ativa e consumia a linha em branco anterior. `definirLinhaNoEnv()` usa `^[ \t]*(?:export[ \t]+)?CHAVE[ \t]*=` para a ativa e `^[ \t]*#[ \t]*…` para a comentada. Documentado no `02` (ADR-10) e no `04` (R54, R64).
- *(2026-09-30)* O `fw-executor-ct` (`maxTurns: 40`) não cabe em lote de 5 a 8 cenários: os sete lotes de teste desta passada rodaram como `general-purpose`/sonnet com o arquivo do agente como contrato por prompt (sem hook), e nenhum tocou arquivo fora do seu — conferido por `git status --porcelain` e `md5sum .env` antes e depois de cada um. Um deles reconstruiu o `tests/Pest.php` a partir de `git show HEAD:` depois de um script apagar dois trechos; o diff final é só o extrator.
- *(2026-09-30)* Os controles de mutação que partem do bloco publicado por `str_replace` de um trecho literal viram no-op quando a doc muda (CT-143 depois da correção do DG-07): o executor precisa partir do publicado de hoje e registrar o trecho que remove. O mesmo vale para o `'%%'` em literal com quebra de linha (CT-144), que só casou em aspas duplas.
- *(2026-09-30, ciclo 3)* O `citacoes.sh` aceita `it` como símbolo por substring (`with`, `str_starts_with`) e o `sed -n` mecânico da sessão escolheu "a linha mais próxima que contém `it`": 64 citações `arquivo:it:N` apontavam linha sem `it(` com o script silencioso (QA-21). A forma que envelhece bem é `arquivo:'[CT-nnn]':linha` — o ID entre aspas é chave, e a conferência acha a linha do `it('[CT-nnn]`. Nota para a `feature-wiki`: o script deveria recusar símbolo que não seja identificador ou exigir que a linha de `it` comece por `it(`.
- *(2026-09-30, ciclo 3)* Dois executores em paralelo criaram o mesmo helper com nomes diferentes (QA-17), o mesmo padrão do QA-07 do ciclo 1: helper que dois lotes vão precisar (a comparação sem acento e sem caixa do Setup Global) tem de nascer no lote `D0` de `tests/Pest.php` antes dos lotes, não em cada arquivo.
- *(2026-09-30, ciclo 3)* O `pest --mutate` no Windows funciona com o arnês do gate: `php -d auto_prepend_file=prepend.php`, que carrega uma cópia da `Pest\Mutate\MutationTest` relançando com `PHP_BINARY` na frente (sem `cmd`/`.bat`); 80–82 mutantes de `SubstituicaoEmArquivo` em ~230 s. É o substituto do `pestw.cmd` (DV-11) até a skill absorvê-lo.
- O `--tia` desta máquina não liga em execução parcial (`--testsuite`) e, na completa, não roda em paralelo por causa da suíte Browser; o grafo gravado estava velho para o lock atual e foi descartado ao começar a regravação.

## Referências Abertas
<!-- Uma linha por arquivo de references/ das skills aberto nesta feature, com o step. -->
- `feature-wiki/SKILL.md`, seção "### 10." e *Adendo ao requisito* — step 10 — 2026-09-29
- `feature-wiki/references/casos-medidos.md`, seções *Step 9* e *Step 10* — step 10 — 2026-09-29
- `feature-wiki/references/citacoes-de-codigo.md` — step 10 (antes do item 3) — 2026-09-29
- `feature-wiki/references/template-03-progresso.md` — step 10 — 2026-09-29
- `feature-wiki/references/pest-5.md`, *TIA* — step 10 (item 10) — 2026-09-29
- `feature-wiki/README.md`, *Numeração dos steps — 3.x → 4.0.0* — step 10 — 2026-09-29
- Os scripts `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh` e `conformidade-rules.sh` (cabeçalho e lógica) — step 10 — 2026-09-29
- `feature-wiki/SKILL.md`, seção "### 10.", e `feature-wiki/references/glossario.md` — step 10 do ciclo 2 do gate — 2026-09-29
- `feature-wiki/SKILL.md`, *Execução e Delegação* e *Execução dos testes*; `references/roteamento-e-despacho.md`; `references/delegacao-casos-de-teste.md`; `agents/fw-executor-ct.md`, `fw-executor-ctb.md`, `fw-adversario-ct.md` — fechamento do ciclo 2 — 2026-09-30
- `feature-test-design/SKILL.md` e `references/revisao-adversarial.md` (pelo analista #73) — 2026-09-30
- As referências dos steps 3 a 7 não foram registradas na época (a seção não existia no template da 3.x)

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
- **Funcionou bem**: derivar o `04` do requisito, e não do plano, deu à implementação um contrato que o mantenedor confirmou inteiro ("Os 104 cenários"). A revisão do diff por quem não implementou foi o gate mais produtivo, como a skill prevê: a rodada 1 achou quatro diagramas afirmando o que o código não faz com a suíte verde (385/385), e cada rodada seguinte achou o que a anterior deixou passar no vazio. O CT-B01 no navegador achou os dois defeitos que nenhuma leitura de arquivo via (o `;` do DG-11 e o contraste do tema escuro), e o job `site` no CI nasceu daí. A prova vermelha de cada correção pegou o que a própria correção quebrava (RD4-01).
- **Faltou no plano**: (1) o `01` escreveu guardas por DG com um fato próprio para os três extras (DG-10, DG-19, DG-20) que o `04` não derivou — o conflito ficou calado até esta reconciliação (A-01 a A-03), e a página promete mais do que a guarda confere (A-06); (2) o `01` tratou a correção do resumo do `kit:install` como "muda o texto dessa linha, nada mais", e a revisão do diff precisou de quatro rodadas para achar os casos em que o texto novo também mentia — um mapa dos desfechos do `kit:install` (semeou? gerou? banco acessível? container?) no planejamento teria encurtado isso, e as dívidas DV-01, DV-02, DV-05 e DV-06 são desse mesmo mapa; (3) o `03` não acompanhou a implementação — nenhum checkbox fechou durante ela, as rodadas 1 e 2 e a implementação ficaram sem linha em `## Despachos`, e os testes das rodadas nasceram no código sem passar pelo `04`; (4) os conferentes rodaram listas de arquivos em vez da suíte, e a regressão do CT-27 (A-04) só apareceu aqui; (5) os números da wiki (linhas de README, citações, contagens) envelheceram dentro do próprio ciclo: 276 citações a reconferir no step 10.
- **Ciclo 2 do gate** *(alterado em 2026-09-29)*: o gate cego achou o que a autolimpeza do step 10 deixou — a wiki ainda descrevendo as guardas dos extras como ausentes (QA-01), números do `03` que não se reproduziam (QA-02) e três diagramas desenhando opcional como sempre ligado, com a suíte verde (QA-04). No ciclo 2, os lotes de teste e os de correção rodaram em paralelo sobre arquivos disjuntos, e isso custou duas coisas que só a reconciliação pegou: o CT-132, escrito contra o bloco ainda errado, ficou vermelho quando a doc foi corrigida; e o renomear de um teste (`[RD…]` → `[RD…][CT-nn]`) passou por "teste escrito" sem os Exemplos que o cenário acrescentou. A correção de um teste que nasce vermelho deveria vir no mesmo lote da correção que o faz passar, ou ser rodada de novo depois dela.
- **Fechamento do ciclo 2** *(alterado em 2026-09-30)*: a revisão adversarial da adição, que o gatilho pedia desde o step 11 e ninguém tinha despachado, rendeu 63 achados em duas rodadas e três regras novas — e o mais caro deles não era de teste: a gravação no `.env` trocava só a primeira linha da chave, podia trocar a comentada, e nenhum dos seis chamadores estava coberto. O que funcionou: os executores contra o código de antes produziram a prova vermelha de graça (14 linhas no `.env`, 5 nas docs), e a correção em um método só ficou verde nos seis gravadores porque o cenário (R64) já exigia cada um. O que custou: cada rodada de fechamento desloca linhas de teste e envelhece as citações da wiki (139 + 32 nesta passada) — o script que as move pelo símbolo mais próximo resolveu 150 em segundos; e a sessão que deriva o "nasce vermelho" de um controle sobre o publicado tem de escrever também o que ele vira depois da correção, senão o executor seguinte o encontra vermelho pelo motivo errado.
