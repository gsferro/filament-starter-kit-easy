# Plano de Ação — Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/` (os 20 diagramas
  Mermaid e as guardas de fato contra o código vivo) e `wikis/specs/feat/site-de-documentacao/site-de-documentacao/`
  (o site Starlight, os stubs, o conversor e as guardas de documentação)
- **Motivo**: a ancestral desenha o kit em diagramas estáticos, sem nenhum que mostre o que cada painel
  registra; esta acrescenta a visualização interativa por painel e por modo, ligada a um catálogo único
  de features, sem tirar nenhum Mermaid do lugar (P-01)
- **Toca infra compartilhada?**: sim → o `npm run build` do site (`site/package.json`), o conferidor de
  acessibilidade do site (`site/verifica-acessibilidade.mjs`, ADR-06), a página do catálogo
  (`docs/{pt,en}/operacao/roteiro-de-features.md`, citada por testes e pela página de diagramas),
  `.ai/rules/index.md` e os contadores do README. A regressão roda contra os CT da ancestral
  `diagramas-da-arquitetura` e as guardas do site

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Análise do archify registrada antes do uso | — | ⚠️ fora de passo — processo: a análise está em `## Análise dos Arquivos Existentes` → *archify*, registrada antes do uso; o passo 1 fixa a versão analisada; conferida no quality gate (dimensão A) *(alterado em 2026-11-20 — step 7: restrição de processo, sem regra nem CT no `04` — `## Cláusulas sem regra`; o `rastreabilidade.sh` só aceita passo "—" com justificativa)* |
| RQ-02 | Lugar no README | 7 | — |
| RQ-03 | Lugar no site | 6 | — |
| RQ-04 | Todas as features de pacote | 2, 5 | parcialmente substituída por RQ-19/RQ-20 |
| RQ-05 | Todas as features de código próprio | 2, 5 | parcialmente substituída por RQ-19/RQ-20 |
| RQ-06 | Explorar ao máximo os tipos | 1, 4, 6 | a matriz tipo × painel × modo está em `## Matriz de visualizações`; parcialmente substituída por RQ-25 |
| RQ-07 | Visualização interativa | 1, 4 | o visualizador do archify (busca de nó, passaporte, rota, lente, exportação) |
| RQ-08 | Todas as ligações entre módulos | 2, 4, 8 | parcialmente substituída por RQ-24; completude na camada de registro (P-05); toda ligação do modelo desenhada em algum diagrama (P-10) |
| RQ-09 | Os fluxos do kit | 3, 4 | a lista fechada de fluxos na página depende da Q6 (aberta); o resto segue |
| RQ-10 | Separado por painel | 2, 4, 6 | — |
| RQ-11 | Com e sem tenant ativo | 2, 4, 6, 8 | o que é igual nos dois modos sai uma vez, marcado (P-09) |
| RQ-12 | Codex `--yolo` como auxiliar | — | ⚠️ fora de passo — processo: o Codex já fez a varredura independente do step 3; nos passos 3 e 5 ele traduz os fluxos e confere o catálogo. Registro em `03` → `## Despachos` *(alterado em 2026-11-20 — step 7: restrição de processo, sem regra nem CT no `04` — `## Cláusulas sem regra`; o `rastreabilidade.sh` só aceita passo "—" com justificativa)* |
| RQ-13 | Conduzido por feature-wiki | — | ⚠️ fora de passo — é esta wiki |
| RQ-14 | Implementar até o fim | — | ⚠️ fora de passo — processo, parcialmente substituída por RQ-26: cumprida pelo conjunto implementado e verde até o passo 11 *(alterado em 2026-11-20 — step 7: restrição de processo, sem regra nem CT no `04` — `## Cláusulas sem regra`; o `rastreabilidade.sh` só aceita passo "—" com justificativa)* |
| RQ-15 | Código de aplicação só muda se necessário | — | ⚠️ fora de passo — nenhum passo toca `app/`, `config/`, `routes/` ou `database/`; os defeitos achados na pesquisa vão para o usuário (D9) |
| RQ-16 | Mudança de código bem documentada | — | ⚠️ fora de passo — condicional à RQ-15: sem mudança de código de aplicação ela fica vazia; se alguma mudar, o quality gate confere a documentação no diff. O gerador e as guardas são documentados na página do mapa e em `docs/{pt,en}/operacao/desenvolvendo-o-kit.md` (passo 10) *(alterado em 2026-11-20 — step 7: restrição de processo, sem regra nem CT no `04` — `## Cláusulas sem regra`; o `rastreabilidade.sh` só aceita passo "—" com justificativa)* |
| RQ-17 | Testes padrão | 1, 8 | — |
| RQ-18 | Subagentes em paralelo | — | ⚠️ fora de passo — processo; registro em `03` → `## Despachos` |
| RQ-19 | Lista = README + site + pacotes | 5 | — |
| RQ-20 | Varredura do código inteiro completa a lista | 2, 5, 8 | a varredura está feita (step 3); o passo 5 a incorpora ao catálogo; o piso mecânico é a D13 (toda peça registrada entrega uma F-nn) |
| RQ-21 | Catálogo único | 5 | — |
| RQ-22 | O catálogo previsto é a base; sem segundo catálogo | 5 | ADR-05 |
| RQ-23 | Manter o catálogo vira rule | 9 | — |
| RQ-24 | Módulo = peça com nome; ligação = uso com o trecho que prova | 2, 4 | P-04 (prova à vista) |
| RQ-25 | Só archify (10 tipos + compare) e os Mermaid existentes | 1, 4 | nenhuma dependência nova além do archify |
| RQ-26 | Fim = PR com CI verde; merge e release só com OK | — | ⚠️ fora de passo — processo: o passo 11 abre o PR e deixa o CI verde; merge e release esperam o OK do mantenedor, conferido no PR *(alterado em 2026-11-20 — step 7: restrição de processo, sem regra nem CT no `04` — `## Cláusulas sem regra`; o `rastreabilidade.sh` só aceita passo "—" com justificativa)* |
| P-01 | Mermaid existentes ficam | 6, 7 | nenhum bloco Mermaid novo nem removido |
| P-02 | README e site em pt e en | 1, 3, 4, 5, 6, 7 | — |
| P-03 | Toda afirmação tem guarda contra o código | 8 | — |
| P-04 | A prova de cada ligação fica à vista no artefato | 4 | cards de prova no próprio HTML |
| P-05 | Completude garantida na camada de registro | 8 | — |
| P-06 | Matriz com motivo para toda célula vazia | 4, 6 | — |
| P-07 | Gerado da revisão publicada | 1, 4 | ADR-01, ADR-03 |
| P-08 | Numeração do catálogo só por acréscimo | 5, 8, 9 | — |
| P-09 | O que é igual nos dois modos sai uma vez | 2, 6 | a grade painel × modo da página marca a célula compartilhada |
| P-10 | Toda ligação do modelo desenhada em algum diagrama | 2, 8 | conta feita pela guarda no JSON do modelo (ADR-07) |
| P-11 | Diagramas como páginas do site: interface no idioma e acessibilidade por amostra | 1, 4, 6 | D2, D8, D12, ADR-06 e D17; as duas exceções vão declaradas na página e sinalizadas no PR |
| P-12 | Piso mecânico da varredura: peça registrada e classe de `app/` fora de `app/Filament` entregam F-nn ou têm motivo | 2, 5, 8 | D13; obrigação permanente do CI sobre toda classe nova, sinalizada no PR |

## Objetivo

Dar ao kit um **mapa interativo** que mostre, painel por painel e com e sem o modo multi-organização,
todas as peças que cada painel registra, as ligações entre elas com a prova no código, os fluxos de ponta
a ponta e as visões que não pertencem a um painel (modelo de dados, classes, histórico de versões, tempos
de instalação e de CI), usando os dez tipos nativos do archify e o comparativo de arquitetura.

Junto com o mapa, o roteiro de features vira o **catálogo único** do kit: ganha as features que o README,
o site, os pacotes e o código entregam e ele ainda não numera, ganha a origem de cada uma (pacote ou código
próprio), e manter o catálogo passa a ser rule do projeto. O mapa cita os números do catálogo, e as guardas
amarram os dois ao código.

## Contexto

Hoje o kit se explica por 20 diagramas Mermaid (a página "Arquitetura em diagramas" e o DG-01 do README),
cada um guardado por um teste de fato. Nenhum deles mostra o que um painel registra — recursos, páginas,
widgets, plugins de pacote, middlewares —, e nenhum separa os três painéis nos dois modos de tenancy. O
roteiro de features, que numera "tudo que o kit entrega" desde agosto de 2026, ficou para trás: cerca de
cinquenta features entregues não têm número, catorze trechos dizem o que o código já não faz, e ele não diz
o que vem de pacote e o que é código do kit.

A pesquisa do step 3 (varreduras por painel, transversais, de dados, de tenancy e a varredura independente
do Codex) levantou 243 peças e 496 ligações no `/admin`, 203 e 430 no `/app`, 207 e 367 no `/infra`, 58 e
110 no interior da IA, 55 tabelas, 27 fluxos de autenticação, 9 de IA e 15 do `/infra`, todos com evidência
conferida por script contra o código da tag `v0.56.0`.

## Análise dos Arquivos Existentes

### archify (a skill analisada — RQ-01)

- Versão 3.2.0, canal estável, licença Apache-2.0; repositório público `github.com/tt-a1i/archify`, commit
  analisado `44781293f38728baf04dbad33ce1a0cef8c39cca`. CLI em Node, sem dependência de runtime: `render`,
  `deliver`, `validate`, `check`, `finalize` (que soma o `browser-check` com Chrome), `compare architecture`
- Dez tipos nativos (architecture, workflow, sequence, dataflow, lifecycle, erd, tree, class, timeline,
  waterfall), mais `compare architecture` (antes, delta e depois) e `explainer` (fora: RQ-25)
- Cada HTML é autocontido, sem nenhuma requisição de rede ao abrir, com 0,83–0,88 MB; o `compare`, com
  ~2,46 MB *(alterado em 2026-11-21 — step 11, QA-03: medido no build do `a624bff1`, 2.457.513 B em pt e 2.451.364 B em en; era ~2,35 MB no spike)*. `render` é determinístico (mesmos bytes para o mesmo JSON)
- Um idioma por artefato: `meta.locale` muda só a interface do visualizador; não há catálogo pt embutido, e
  `meta.translations` com as 486 chaves da interface a deixa inteira em pt-BR
- Evidência: `meta.repository {url, revision}` e `sources` com no máximo três `{path, line, end_line,
  label}` por elemento primário; ligações não têm `sources` — a própria skill manda "manter a evidência
  longa num card". `--repo-root` confere origem, commit, arquivo e faixa de linhas, não o conteúdo
- Acessibilidade medida com o axe 4.13 na configuração do CI do site: todo HTML tem `nested-interactive`
  (serious) no SVG raiz; dois exemplos de tree e timeline têm `color-contrast` (serious) no índice
  lateral agrupado; o `compare` tem cinco `color-contrast` no tema claro
- Tema: `?theme=`, `localStorage` próprio ou o tema do sistema — não lê o tema do Starlight
- Limitações: a casca do `compare` é só em inglês e escura; sem marca para Laravel, PHP, Filament,
  Livewire ou Tailwind no catálogo de marcas
- Estudo completo e medições: `mapa-pesquisa/archify.md` (fora do repositório, pasta de pesquisa da sessão)

### Site (`site/`)

- Astro 7.3.3 + Starlight 0.42.2; o conteúdo é lido de `docs/` no lugar (`site/src/content.config.ts`); a
  barra lateral é `site/sidebar.json`, os redirecionamentos são `site/redirects.json` e os 66 stubs de
  `site/public/{pt,en}/`, todos gerados e commitados pelo `site/converter.mjs`, que não roda no CI
- `site/package.json`: `build` = `astro build`. `site/.gitignore`: `node_modules/`, `dist/`, `.astro/`
- `site/verifica-links.mjs` lê todo `.html` do `dist/` e confere todo `href` relativo, inclusive âncora
- `site/verifica-acessibilidade.mjs` visita só `index.html` sob `/pt` e `/en`, injeta o axe só no frame
  principal e reprova `serious`/`critical` e qualquer `console.error`
- Publicação: `.github/workflows/pages.yml` (push em `main` com `docs/**` ou `site/**`); no PR, o job `site`
  do `.github/workflows/ci.yml` constrói e confere quando o diff toca `docs/` ou `site/`

### Guardas que limitam onde e como o mapa entra

- `tests/Kit/SiteDeDocumentacaoTest.php`: `[CT-21]` (`docs/` só `.md`/`.yml`, sem `<iframe>`), `[CT-36]` e
  `[CT-38]` (todo `.html` de `site/public/` é stub, na quantidade das folhas), `[CT-22]` (link Markdown
  interno resolve para página), `[CT-45]` (sem link absoluto), `[CT-14]` (o README linka só página de
  docs, folha na forma `.html`), `[CT-13]` (teto de linhas do README), `[CT-19]` (marcador de idioma:
  `the` proibido em pt; `não|que|é|são|sobre` proibidos em en), `[CT-01]`/`[CT-03]` (títulos do baseline),
  `[CT-04]`/`[CT-05]` (paridade pt/en), `[CT-20]`/`[CT-29]`/`[CT-41]` (barra lateral, front-matter, ordem)
- `tests/Kit/DiagramasDaArquiteturaTest.php`: catálogo Mermaid fechado em 20 + 1 (`[CT-01]`, `[CT-02]`); o
  DG-11 mora na página do roteiro de features; imagens só de `art/` (`[CT-69]`, `[CT-55]`)
- `tests/Kit/CitacoesDeCodigoTest.php` não varre `site/` — o modelo do mapa precisa de guarda própria
- `tests/Kit/RedeDeDocumentacaoTest.php`: toda suíte que lê `docs/`, `site/` ou o README leva a sentinela
  `naArvoreDoKit()`, sem `is_dir(`/`file_exists(` com `docs` antes do `;`
- `tests/Kit/KitUpdateTest.php`: arquivo novo em `tests/Kit` já está coberto pelo `kit:update`

### Painéis vivos (fonte da completude — P-05)

- `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php` registram recursos, páginas, widgets, plugins e
  middlewares; o painel montado expõe `getResources()`, `getPages()`, `getWidgets()`, `getPlugins()`,
  `getMiddleware()`, `getAuthMiddleware()` e `getTenantMiddleware()` (Filament 5,
  `vendor/filament/filament/src/Panel/Concerns/`)
- Suíte `Kit`: modo sem tenant. Suíte `Tenancy`: `Tests\TenancyTestCase` fixa a config em
  `createApplication()`, antes do boot dos painéis — o modo com tenant
- Precedente de inventário de telas por painel com teste: `tests/Kit/InventarioDeOnboardingTest.php`

### Catálogo (`docs/{pt,en}/operacao/roteiro-de-features.md`)

- 74 entradas (F-01…F-71 mais F-03a/b/c) em oito seções, seis colunas `# | Feature | Onde | Quem alcança |
  Como conferir | Teste`; ordem temática, não numérica; pt e en com os mesmos ids
- Dependem dos números: `tests/Browser/RoteiroDoKitTest.php` (F-03, F-13, F-45, F-51, F-52), o `[CT-06]` de
  `SiteDeDocumentacaoTest` (a linha en do F-06 cita "social login"), wikis, comentários e o CHANGELOG
- Nenhum teste lê a forma da tabela; o DG-11 está na página e tem de continuar nela
- Defasagens a corrigir (RQ-22): F-07 en (`/{panel}/my-profile` → `meu-perfil`), F-31 (tutorial nos três
  painéis), F-32 (9 widgets no `/admin`), F-33 (checks de saúde), F-44/F-45 (marca), F-53 e F-30 (chat e
  catálogo dependem do interruptor de IA), F-68 (hub com `KIT_HUB=false` responde 403), F-06
  (inatividade), `docs/{pt,en}/operacao/index.md` ("68 features"), `docs/pt/operacao/depois-de-criar-resources.md`
  (telas de pacote do `/infra` consultam a permissão), cabeçalho de `tests/Browser/RoteiroDoKitTest.php`
- Features sem número: as 50 da reconciliação da pesquisa (`mapa-pesquisa/catalogo.md` §3.6 e
  `mapa-pesquisa/lacuna-catalogo-codigo.md`): login social, `/login` único, anti-robô, aparência das telas de
  acesso, "+" no e-mail, estados da conta, cadastro por organização, configurações da aplicação,
  identidade, rodapé, aviso de alterações não salvas, densidade e aparência pessoal, interruptor de IA,
  dashboard do `/infra`, dashboard dinâmico, link do painel da organização, seletor oculto, login por
  organização, insights, rótulo da organização, badge do papel, dock, tutorial (consumo), visões de tabela,
  carregamento contínuo, import/export CSV, defaults de tabela, UI pt-BR, mídia privada, teto de upload,
  `kit:install`, `kit:admin`, `kit:info`, `kit:update`, domínio local, Docker por profile, deploy local,
  multiambiente, model caching, DTO, gates de qualidade, capturas por teste, preparo para agentes,
  convenções, senha por e-mail no perfil, URL sem `/public`, voltar ao topo, SMTP na tela, travas de
  escalada de papéis, senha forte e comandos destrutivos bloqueados em produção

### README

- `README.md` e `README.en.md`, 512 e 513 linhas; teto de 756 e 767 (`[CT-13]`); a seção "Os três painéis"
  termina com o link "Veja todos os diagramas…" — o ponto do mapa. Contadores travados por teste: features
  especificadas (97 com esta wiki), project rules (28 com a rule nova), arquivos de teste e o badge de casos
  de teste

### `search-docs`

- O MCP `laravel-boost` não conectou nesta sessão (timeout); as APIs de painel do Filament foram lidas no
  código instalado em `vendor/filament/filament`. Limitação registrada no `03`

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | O README ganha uma seção curta com uma tabela painel × modo que leva à página do mapa, e nenhuma imagem nova: o GitHub não executa o visualizador, e uma imagem exportada seria um retrato sem guarda, fora do `kit:arte` | autorização existente ("veja onde podemos colocar no readme") | difícil de reverter | sessão, 2026-11-20 |
| D2 | A interface do visualizador em pt usa as 486 chaves do catálogo do archify: as 461 do `examples/locales/pt.json` do commit fixado, lidas no build, mais as 25 que faltam, mantidas em `site/mapa/interface.json` *(alterado em 2026-11-21 — step 11, QA-03: era `pt-BR.json`; virou `interface.json` no passo 1, porque guarda as sobrescritas da D12 nos dois idiomas)* com as sobrescritas da D12 *(corte do ponytail, step 6)* | autorização existente (P-02) | difícil de reverter | sessão, 2026-11-20 |
| D3 | O rótulo de cada peça é o nome dela no código (neutro entre idiomas); tipo, verbo da ligação, títulos e textos dos fluxos são traduzidos | autorização existente | surpreendente | sessão, 2026-11-20 |
| D4 | A prova das ligações vai em cards do próprio HTML, agrupados pela peça de origem: "origem → destino (verbo) — arquivo, linha" (P-04) | P-04 | difícil de reverter | sessão, 2026-11-20 |
| D5 | Fluxos escolhidos: os de ponta a ponta de cada painel e os transversais da pesquisa — a lista está em `## Matriz de visualizações`; o critério é cobrir cada painel nos dois modos, não converter todos os 80 levantados. *(alterado em 2026-11-20 — spike)* Cada sequência é escrita à mão a partir do código, não convertida da pesquisa: no máximo 8 participantes, nome de participante de até 15 caracteres, rótulo de mensagem de até 30 e o detalhe numa nota; a conversão automática dos 27 fluxos da pesquisa gerou rótulos que diziam o contrário do código | autorização existente | trade-off | sessão, 2026-11-20 |
| D6 | Links profundos do visualizador (`#focus=`, `#route=`) ficam fora da página do site até o `verifica-links` reconhecê-los | — | difícil de reverter | sessão, 2026-11-20 |
| D7 | O tema do mapa segue o do sistema; a página do site avisa que o seletor de tema do site não chega ao visualizador | — | difícil de reverter | sessão, 2026-11-20 |
| D8 | O `compare` mantém a casca em inglês (limitação do archify); a página do site avisa. *(alterado em 2026-11-20 — spike)* Só existe para o resumo dos painéis em que a estrutura muda (P-09); abre no tema escuro e ignora `?theme` | RQ-25 | trade-off | sessão, 2026-11-20 |
| D9 | Os defeitos do kit achados na pesquisa (configuração de fábrica da IA que falha antes dos guardrails, três caminhos que levam texto sem redação de PII ao provider, rótulo do interruptor de colunas, docblocks de tenancy defasados) **não** são corrigidos aqui (RQ-15): são relatados ao usuário | RQ-15 | difícil de reverter | sessão, 2026-11-20 |
| D10 | Números e datas que o archify escreve em formato en-US não podem enganar o leitor em pt: o `waterfall` usa segundos inteiros abaixo de 1.000 (o renderizador escreve `toLocaleString('en-US')`, e "5,866 s" seria lido como cinco segundos), e o trecho que passaria disso (a cobertura do CI, ~5.855 s) vai num card com a duração escrita em pt; a `timeline` traz a data `dd/mm/aaaa` no texto de cada marco, porque o eixo é fixo em `MM-DD`; a página declara as duas limitações | RQ-25, instrução da organização (SI do Brasil) | surpreendente | sessão, 2026-11-20 |
| D11 | Os fluxos de IA mostram o caminho que o código faz hoje, inclusive os três que levam texto sem redação ao provider (D9); o card do fluxo declara isso. Mostrar só o caminho com redação seria afirmar o que o código não faz (P-03) | P-03 | trade-off | sessão, 2026-11-20 |
| D12 | A interface do visualizador em en também recebe traduções: os rótulos dos tipos de peça (`viewer.kind.*`) e da legenda são sobrescritos nos dois idiomas, porque os 33 tipos do kit cabem nos 7 tipos de componente do archify e, sem isso, o painel de detalhes chama um interruptor de "message bus" | autorização existente | surpreendente | sessão, 2026-11-20 |
| D13 | O piso mecânico da varredura (RQ-20): toda peça que um painel registra — recurso, página, widget e plugin —, nos dois modos, entrega ao menos uma F-nn do catálogo, e a guarda reprova a peça registrada sem F-nn; além do registro, a página declara que a completude não tem garantia (P-05). *Reclassificada de requisito para desenho: escolhe o oráculo de uma cláusula já fechada.* *(alterado em 2026-11-20 — revisão adversarial, ADV-33: o piso cresce para toda classe de `app/` fora de `app/Filament` e passa a ser a P-12 do `00`; a lista das peças e classes sem feature própria, com o motivo, mora na guarda)* | Q8, P-12 | — | sessão, 2026-11-20 (step 7) |
| D14 | A linha do tempo e a cascata de tempos têm guarda, como o resto do mapa: cada marco cita uma versão que existe no `CHANGELOG.md`, com a data dela (não precisa ter todas as versões: os marcos são escolhidos); cada tempo medido traz a data e a fonte da medição (o comando ou o run do CI); a guarda reprova a versão inexistente, a data trocada e o tempo sem fonte. *Reclassificada de requisito para desenho: escolhe o oráculo* | Q10 | — | sessão, 2026-11-20 (step 7) |
| D15 | Três mecanismos do catálogo: (a) a F-nn removida continua na tabela, marcada como removida e com o motivo; (b) a linha da F-nn leva à página do site que a descreve por link, como a F-68 já faz, e a página que não descreve feature (índice de seção, página de processo) é listada na guarda com o motivo; (c) a guarda casa a lista de features do README da base com as F-nn por uma tabela congelada nela | Q14 | — | sessão, 2026-11-20 (step 7) |
| D16 | A página do mapa linka cada diagrama com `<a href>` relativo cru, como a ADR-04 já decidiu: o `[CT-22]` e o `[CT-45]` herdados só leem link Markdown e ficam como estão; o `verifica-links` confere o alvo no `dist/`, e a guarda do mapa confere cada link contra os arquivos de diagrama. Nenhuma guarda herdada ganha isenção | Q15 | — | sessão, 2026-11-20 (step 7) |
| D17 | O que o navegador confere no mapa (os CT-B e a amostra de acessibilidade da ADR-06) mora num conferidor próprio, `site/verifica-mapa.mjs`, e não no `site/verifica-acessibilidade.mjs`, que já carrega os `[CT-B01]` e `[CT-B02]` da wiki `diagramas-da-arquitetura`: as duas wikis numeram CT-B a partir de 01, e o mesmo ID no mesmo arquivo nomearia dois cenários. Os dois workflows o rodam logo depois do conferidor de acessibilidade, com a mesma prévia de pé. O `ids-ct.sh` só lê `it()`/`test()` do Pest e acusa os CT-B do mapa como sem teste, por desenho; a conferência deles é o próprio conferidor, no CI | advisor do step 7 | — | sessão, 2026-11-20 (step 7) |
| D18 | Critério único de modo (P-09): o modelo guarda, por peça, os modos em que o painel a **registra** e a condição de **disponibilidade** (o modo ou a chave e o valor de fábrica, com a âncora do código que decide, como o `canAccess()` do `TenantResource`); por ligação, os modos em que ela existe. Um diagrama ganha versão por modo exatamente quando o conjunto que ele desenha muda entre os modos; senão é um só, marcado como compartilhado. Qual conjunto se desenha para a peça registrada e indisponível depende da Q11 (recorte bloqueado): a renderização muda, o modelo não. A exceção da matriz para o `/infra` ("vai em card") sai: o `/infra` segue o mesmo critério | Q11 (aberta) e advisor do step 7 | — | sessão, 2026-11-20 (step 7) |
| D19 | A linha do catálogo de uma F-nn de origem pacote nomeia o pacote pelo nome do Composer ou do npm (o mesmo do link da referência de pacotes); a lista dos pacotes instalados sem feature própria mora na guarda, com o motivo de cada um, como a das páginas que não descrevem feature (D15b). A referência de pacotes passa a listar os quatro instalados que ela não tinha (Q16) | Q17 | — | sessão, 2026-11-20 (step 7) |
| D20 | Formas válidas do arquivo da prova (ADV-07, ADV-08): o arquivo da peça de origem; um trait ou concern que o arquivo da origem usa; o provider do painel, nas ligações do painel; o arquivo do alvo, quando o trecho é o método de contrato que o framework chama nele (`User::canAccessPanel`); e, para o que o framework acrescenta a partir da configuração do painel (`panel:{id}`, `IdentifyTenant`), o trecho que o configura (`->id('admin')`, `->tenant(...)`). Prova em `vendor/` é trocada pelo trecho do kit que liga ou configura a peça (P-07). Medido nos grafos do step 3: 687 de 748 ligações (92 %) já têm a prova no arquivo da origem | revisão adversarial | — | sessão, 2026-11-20 (step 7) |
| D21 | Cada marco da linha do tempo traz, além da versão e da data, um termo que está na seção dessa versão no `CHANGELOG.md`: o CHANGELOG cita F-nn em só 5 versões, então a F-nn não serve de oráculo para o marco (ADV-12) | revisão adversarial | — | sessão, 2026-11-20 (step 7) |
| D22 | O link de prova é `{support.source do composer.json}/blob/{commit}/{arquivo}#L{linha}`, e o `meta.repository` de cada diagrama aponta o mesmo repositório (ADV-25); o archify mostra no passaporte "origem verificada" com esse repositório e o commit | revisão adversarial | — | sessão, 2026-11-20 (step 7) |
| D23 | A linha da rule do catálogo no `.ai/rules/index.md` casa também os caminhos onde a feature nasce — `app/**`, `composer.json`, `package.json` e `routes/**` —, além da página do catálogo: quem cria recurso, plugin ou comando abre a rule (ADV-18) | revisão adversarial | — | sessão, 2026-11-20 (step 7) |
| D24 | Controle de processo da RQ-26 (ADV-34): a sessão e o Codex nunca fazem merge, push na `main` nem tag; antes e depois de cada despacho do Codex a sessão confere `git rev-parse HEAD`, `git tag --points-at HEAD`, o branch e o estado do PR, e para no primeiro desvio. O PR fica aberto até o OK escrito do mantenedor | revisão adversarial | — | sessão, 2026-11-20 (step 7) |
| D25 | No `lifecycle`, a âncora da transição não precisa nomear o estado de destino (Q18 — não). Os estados do kit são calculados: o `Convite::situacao()` os deriva de `aceito_em`, `recusado_em` e `expira_em` (`app/Models/Convite.php:situacao:619`), e o `User::rotuloDaSituacao()`, de `aprovacao_pendente` e `ativo` (`app/Models/User.php:rotuloDaSituacao:558`). A transição grava a coluna e nunca escreve o nome do estado (`app/Models/Convite.php:'aceito_em':690`), e "prazo vence" não tem linha que a faça. Exigir o nome reprovaria transição real; o R35-M5 fica lacuna declarada, com este motivo | Q18 | — | sessão, 2026-11-20 (step 7, medido no código) |
| D26 | Quinta forma da prova (Q19): ligação cuja origem é peça de pacote. A prova é o trecho do kit que liga a peça de origem à de destino, num arquivo versionado fora de `vendor/`, em qualquer pasta; o trecho nomeia a peça de destino (classe, alias ou chave), e o arquivo nomeia a peça de origem (classe, plugin ou a chave de config do pacote). Prova em `vendor/` e saída de comando (`artisan route:list`) nunca valem: o modelo troca pela linha que registra a peça. Medido nas 288 ligações da varredura do step 3 cuja origem não é classe do kit nem painel: provider de painel 116, sem prova 63, `vendor/` 57, outro provider 11, `database/` 11, `config/` 9, `routes/` 6, `app/Filament` 4, saída de `artisan` 4, `app/Support` 3, model 2, view 1 e stub 1. Só `config/` e provider, como a recomendação pedia, deixariam vermelhas as ligações de `routes/` e de `app/Support`. Exemplo: `Event::listen([AgentPrompted::class, AgentStreamed::class], RegistrarAiRun::class)` (`app/Providers/KitServiceProvider.php:RegistrarAiRun:529`) | Q19 | — | sessão, 2026-11-20 (step 7, medido nos grafos da varredura) |
| D27 | Na quinta forma da prova (D26), quando o trecho é um par de um arquivo de mapeamento (`X::class => Y::class`, como o `app/Support/PoliciesDeVendor.php`), ele nomeia as duas pontas: o trecho é a linha inteira, com os espaços de alinhamento. Fora desse caso a regra da D26 fica: o trecho nomeia o destino e o arquivo nomeia a origem. Medido nas 116 ligações da varredura com origem de pacote e prova em provider de painel: a linha nomeia as duas pontas em 3, só o destino em 59, só a origem em 12 e nenhuma em 42 — exigir as duas pontas em todo trecho reprovaria entrega correta (ADV2-09) | segunda revisão adversarial | — | sessão, 2026-11-20 (step 7, medido nos grafos da varredura) |
| D28 | Escopo "do kit inteiro" pelo critério da P-06 ("o que não pertence a um painel"): só o diagrama que não desenha peça registrada por painel — versões, tempos, modelo de dados e de classes, e os comandos `kit:*`. Fluxo transversal (login, 2FA, login social, recuperação de senha, bloqueio de sessão, pilha de middleware) desenha peça que todo painel registra e é diagrama dos painéis que ele desenha: o arquivo de diagrama troca `painel?` por `paineis[]`, e o diagrama aparece, compartilhado, na célula de cada um. A coluna "Kit / transversal" da matriz fica só com o que é do kit inteiro (ADV2-14) | segunda revisão adversarial | — | sessão, 2026-11-20 (step 7) |
| D29 | A F-nn de cada peça fica à vista no diagrama: os construtores de architecture e da tree de registro põem as F-nn da peça no `tag` do componente ("F-29 · F-30"). Medido no pinado: o archify desenha o `tag`, e o `admin-acesso` do esqueleto valida com ele em todas as 41 peças. É o caminho do leitor da peça à feature (ADV2-05). *(alterado em 2026-11-20 — medido na quinta rodada: o archify esconde o `tag` no carregamento e o mostra no nível de detalhe completo, pelo botão "+"; o passaporte mostra a F-nn e a busca acha a peça por ela. Na árvore de registro, cujo esquema não tem `tag`, a F-nn vai no `sublabel`)* *(alterado em 2026-11-20 — guardas do passo 8: o `tag` e o `sublabel` mostram até três F-nn e "+N", porque o archify recusa o `tag` mais largo que a caixa e uma peça entrega até 25 F-nn; o card "F-nn de cada peça" do mesmo diagrama lista todas, à vista sem foco; o CT-B01 do `05` confere os dois)* | segunda revisão adversarial | — | sessão, 2026-11-20 (step 7, medido) |
| D30 | O registro vivo de cada painel inclui as páginas que o painel liga pela configuração de autenticação, e não pelo `->pages()`: `getLoginRouteAction()`, `getRegistrationRouteAction()`, `getRequestPasswordResetRouteAction()`, `getResetPasswordRouteAction()`, `getEmailVerificationPromptRouteAction()`, `getProfilePage()` e, com tenant, as páginas de cadastro e de perfil da organização. Medido no kit: `TelaLogin`, `TelaRecuperarSenha` e `TelaRedefinirSenha` (nos três painéis), `RegistroPorConvite` e `TelaVerificacaoDeEmail` (no `/app`) não estão no `getPages()` (ADV2-10) | segunda revisão adversarial | — | sessão, 2026-11-20 (step 7, medido) |
| D31 | O job `build` do `pages.yml` só roda na `main` (`if: github.ref == 'refs/heads/main'`): um `workflow_dispatch` de outro ramo publicaria links de prova para um commit que some com o ramo (Q9: nunca um link que dê 404). O dispatch a partir da `main` segue valendo. Sinalizado no PR (ADV2-31) | segunda revisão adversarial | difícil de reverter | sessão, 2026-11-20 (step 7) |
| D32 | Nenhuma biblioteca de visualização entra fora do archify (RQ-25) por dois controles: a lista de arquivos de `site/mapa/` é fechada (o download, o gerador, a interface, o posicionador, os dois arquivos do modelo — `pecas.json` e `ligacoes.json` — e a pasta de diagramas) *(alterado em 2026-11-20: o modelo entrou na lista)*, e a guarda lê os quatro campos de dependência do `site/package.json` (`dependencies`, `devDependencies`, `optionalDependencies` e `peerDependencies`). O `archify check` não serve de oráculo: medido, ele só acusa alteração do HTML com o recibo `.delivery.json`, que o `render` não grava (ADV2-15) | segunda revisão adversarial | — | sessão, 2026-11-20 (step 7, medido) |
| D33 | F-nn sem peça possível no modelo: a feature que não é entregue por classe — arquivo de infraestrutura (F-107, o `docker-compose.yml`; F-108, o `deploy_docker_local.sh`), guarda de teste (F-64), arquivos de tradução (F-99) ou parte de pacote que não é tipo de peça da R8 (F-100, o componente de formulário da media library; F-113, o plugin de browser do Pest e o Playwright) — fica numa lista fechada da guarda da R6, com o motivo de cada uma, e a página do mapa declara a lista; o catálogo e a página do site seguem demonstrando essas features. As outras 21 F-nn sem peça ganharam a peça verdadeira que faltava no modelo (providers, widgets e models de pacote, com a linha do kit que os liga ou usa). Medido no mapeamento peça → F-nn: 238 peças com F-nn, 3 na lista das sem feature própria, 27 F-nn sem peça (21 com peça proposta, 6 impossíveis) | implementação (passo 2) | — | sessão, 2026-11-20 (medido) |
| D34 | "Fora de comentário" (R9, CT-52 e CT-53) vale também para YAML e `.env` (`#`) e para Markdown (`<!-- -->`), além de PHP, Blade, JS e stub: a âncora do `ci.yml`, do `.env.example` e do CHANGELOG fica sob a mesma regra (Q20) | step 9, achado A7 | — | sessão, 2026-11-21 (step 9) |
| D35 | Os jobs do CI que rodam a suíte Kit (`qualidade` e `cobertura`) ganham `actions/setup-node` com a versão do site: as guardas da P-14 rodam o gerador e o posicionador como processo filho (costura G5), e o Node do runner não é contrato (Q21) | step 9, achados A2, A4, A6 e A11 | — | sessão, 2026-11-21 (step 9) |
| D36 | Uma seta por verbo: o mapa de arquitetura desenha, para cada par de pontas, uma seta por verbo das ligações que caem nele, cada uma com o traço do seu verbo (o archify só tem os quatro traços, todos já de verbo); os mapas com par de mais de um verbo são reposicionados (Q22, P-13) | step 9, achado A1 | — | sessão, 2026-11-21 (step 9, medido: duas conexões no mesmo par renderizam, e a validação de layout pede reposicionar) |
| D37 | O controle do CT-B06 sobre o caminho do conferidor é de derivação (a URL do módulo vira caminho de arquivo sem escape), sem prova de ponta a ponta num clone com espaço ou acento no caminho (Q25) | step 9, achado A12 | — | sessão, 2026-11-21 (step 9) |
| D38 | A suíte Tenancy não ganha controle no CT-30 nem no CT-44: o docblock dela passa a dizer o que os datasets têm, e o CT-55 e a linha nova do CT-38 cobrem a assimetria do registro (Q26) | step 9, achado A15 | — | sessão, 2026-11-21 (step 9) |
| D39 | A raiz da árvore de registro de um painel leva o nome que o modelo dá à peça painel (`/app`), como toda peça desenhada, e não um formato próprio ("Painel /app") (R24 do `04`) | Q23 (step 9, achado A14) *(alterado em 2026-11-21 — step 11, QA-06: a Q23 saiu da raia de requisito do `00`)* | — | sessão, 2026-11-21 (step 9) |
| D40 | `--tentativas` do posicionador aceita só um inteiro positivo: `0` é recusado, porque zero tentativas não é busca (P-14) | Q24 (step 9, achado A6) *(alterado em 2026-11-21 — step 11, QA-06: a Q24 saiu da raia de requisito do `00`)* | — | sessão, 2026-11-21 (step 9) |

## Autorização

- **Policies / Gates / Guards**: nenhum — a feature não cria tela no app; o site é estático e público
- **Middleware**: nenhum

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET (site estático) | `{base}/pt/referencia/mapa-interativo/` e `{base}/en/referencia/mapa-interativo/` | — | — (GitHub Pages) |
| GET (site estático) | `{base}/mapa/{pt,en}/{slug}.html` (um por diagrama) | — | — (GitHub Pages) |

Nenhuma rota do app muda.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Página "Mapa interativo" | Página do site (Starlight, Markdown) | `/{pt,en}/referencia/mapa-interativo/` | lê a matriz e abre cada diagrama | Não |
| Diagramas do mapa | HTML do archify (visualizador) | `/mapa/{pt,en}/{slug}.html` | busca de nó, passaporte com a evidência, rota entre nós, lente, tema, exportação; lê os cards de prova | Sim |

Nenhuma tela Filament, Livewire ou Blade é criada ou alterada. A verificação em navegador dos diagramas é a
do conferidor de acessibilidade do site (Node + Playwright), como os CT-B01/CT-B02 da ancestral.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `ARCHIFY_UPDATE_CHECK_DISABLED` | `1` (o gerador define) | desliga a checagem de atualização do archify no build |

Nenhuma chave nova no `.env` do kit.

## Eventos / Listeners / Observers

- Nenhum.

## Jobs / Queues

- Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | um por página estática; cada diagrama é um HTML autocontido, sem requisição de rede depois de aberto |
| O que é adiado, e por qual gatilho? | nada no app; a geração roda no `npm run build` do site |
| O que é memoizado **por request**? | nada |
| O que é cacheado **entre** requests? | o archify baixado fica em `site/.archify/` entre builds locais |
| Custo do caminho principal | no build: um `render` por diagrama e idioma (~0,4 s cada, medido) e um `compare` por painel; no app: zero |

## Impacto em Features Existentes

- Site: o `npm run build` passa a gerar o mapa e a depender do download do archify; o `dist/` cresce de 100 a
  180 MB (limite do Pages: 1 GB)
- Roteiro de features: ganha coluna e linhas; o DG-11 continua na página; a linha en do F-06 continua citando
  "social login" (`[CT-06]`)
- README: seção nova e contadores (features especificadas, rules, arquivos de teste, casos de teste)
- Nenhuma mudança de comportamento no app

## Rollback

- **Migration down**: não há migration
- **Feature flag**: não há; reverter o PR remove a página, o modelo, o gerador e as guardas, e o site volta ao
  build anterior no próximo deploy
- **Reversão de dados**: não há dado

## Dependências

- **Composer**: nenhuma
- **NPM**: nenhuma
- **Ferramenta de build**: archify, commit `44781293f38728baf04dbad33ce1a0cef8c39cca` do repositório público, baixado
  pelo gerador (ADR-02)

## Riscos

- **Tamanho dos mapas por painel**: decidido pelo spike de escala — nenhum painel cabe num diagrama (ADR-07)
- **Layout não monotônico**: uma peça a mais pode derrubar um mapa que passava. Mitigação: orçamento a 80 % do
  teto e o `posicionar.mjs` para refazer o layout (ADR-07)
- **Fontes do Linux**: o piso de 6 px do archify foi medido no Windows com folga de 0,02 a 0,15 px. Mitigação: piso
  do projeto em 6,5 px e o esqueleto (passo 1) no Linux
- **Saída en nunca gerada no spike**: textos traduzidos mudam larguras. Mitigação: o esqueleto e o build geram e
  conferem os dois idiomas
- **Tempo de build**: de 0,3 a 1,4 s de `render` por HTML; o esqueleto mede no Linux
- **Repositório do archify indisponível**: o build falha fechado com a mensagem (ADR-02)
- **Manutenção do modelo**: código que muda uma ligação reprova a guarda até o modelo acompanhar (ADR-03) — é
  o comportamento desejado (P-03)
- **Acessibilidade do visualizador de terceiros**: ADR-06

## Channel de Log da Feature

### Verificação de Channel Existente

- A feature não executa código de aplicação: o gerador é um script de build do site, e as guardas são testes.

### Decisão

- **Sem channel**: o gerador escreve uma linha por diagrama no stdout do build, no formato
  `[mapa] {slug} {idioma} — {tipo}, {n} peças, {bytes} B` e, na falha, `[mapa] ERRO {slug} {idioma} — {diagnóstico}`.

## Matriz de visualizações

> Decomposição dos mapas por painel: ADR-07, decidida pelo spike de escala. Toda célula vazia tem o motivo
> (P-06); célula que vale para os dois modos diz "os dois modos" (P-09). "kit" = visão do kit inteiro, sem eixo
> de painel. *(alterado em 2026-11-20 — spike de escala; a versão anterior previa um mapa por painel e modo,
> reprovado pelo spike)* As quantidades de mapas de domínio são estimativa do spike; a contagem fechada sai no
> manifesto depois do passo 1 (esqueleto) e do passo 2 (modelo).
> *(alterado em 2026-11-20 — step 7, D18)* No `/admin` o registro é o mesmo nos dois modos (42 rotas e o mesmo
> middleware, medidos na pesquisa de tenancy); o que muda é disponibilidade — a tela de organizações e as peças
> dela — e o modelo de papéis. Por isso as células do `/admin` que dividem por modo dependem da **Q11** (recorte
> bloqueado): com "desenhar só onde está disponível", organizações e pessoas dividem e o resumo tem comparativo,
> como está abaixo; com "desenhar onde está registrada, marcada com a condição", o `/admin` só divide onde uma
> ligação muda. O `/app` divide em qualquer resposta: o registro dele muda.

| Tipo | `/admin` | `/app` | `/infra` | Kit / transversal |
|---|---|---|---|---|
| tree — registro do painel | sem · com | sem · com | os dois modos (nada do registro muda) | — (cada painel tem o seu) |
| architecture — resumo | sem · com | sem · com | os dois modos | — |
| architecture — domínios | ~12 domínios nos dois modos; organizações só com; pessoas sem · com | ~8 domínios nos dois modos; ~3 com variante por modo | ~9 domínios nos dois modos | — |
| compare architecture | resumo sem → com | resumo sem → com | — (o resumo é igual nos dois modos) *(alterado em 2026-11-20 — step 7, D18: as duas peças que o spike viu só com tenant seguem o critério único — o mapa de domínio que as desenhar ganha versão por modo, sem a exceção do card)* | — |
| sequence | convite | troca de organização (só com) · chat de IA | ledger de IA · tratamento de exceções | — *(alterado em 2026-11-20 — D28: login, 2FA, login social, recuperação de senha, bloqueio de sessão e pilha de middleware da requisição desenham peças que os três painéis registram; são diagramas dos três painéis, compartilhados na célula de cada um, e não do kit inteiro)* |
| workflow | — (os fluxos do painel são sequências) | — | — | `kit:install` · `kit:update` · `kit:tenancy` |
| lifecycle | — (os estados são do kit) | — | — | conta · convite · sessão |
| dataflow | configurações da aplicação → `config()` | do prompt ao ledger da IA (D11) | (mesmo fluxo da IA, visto do ledger) | — |
| erd | — (o esquema é do kit) | — | — | 9 domínios, os dois modos · identidade e permissões com tenant |
| class | — | — | — | models e relações · models com superclasses e contratos · traits |
| timeline | — | — | — | marcos do CHANGELOG (D10) |
| waterfall | — | — | — | `kit:install` medido · um run de CI medido, a cobertura em card (D10) |

## Estrutura de Implementação

### 1. Esqueleto andante — um diagrama de cada tipo, do modelo ao `dist/`, num clone raso no Linux

> Skills: `ponytail`
> *(passo novo em 2026-11-20 — spike de escala; absorve o antigo passo "archify fixado e interface em pt-BR")*

- **Path**: `site/mapa/archify.mjs`, `site/mapa/interface.json` *(alterado em 2026-11-21 — step 11, QA-03: era `pt-BR.json`)*, `site/.gitignore`, `site/mapa/gerar.mjs` (mínimo),
  `site/package.json`, `site/verifica-mapa.mjs` (ADR-06, D17), `.github/workflows/pages.yml` e o job `site` do
  `.github/workflows/ci.yml` (o passo do conferidor do mapa) e um diagrama de cada tipo já no formato do
  modelo, partindo das entradas do spike *(alterado em 2026-11-20 — step 7, D17: o conferidor do mapa é um
  arquivo próprio)*
- `archify.mjs` exporta `obterArchify(): string` *(alterado em 2026-11-21 — step 11, QA-03: síncrona: o gerador é um script de build, e o `execFileSync` basta)* (caminho da CLI): se `site/.archify/` não tem o
  commit declarado (`ARCHIFY_COMMIT`, 40 hex), faz `git init`, `git fetch --depth 1` do commit e `checkout`;
  confere `git rev-parse HEAD` igual ao declarado; falha fechado com a mensagem de como resolver; roda o archify
  com `ARCHIFY_UPDATE_CHECK_DISABLED=1`
- `interface.json` *(alterado em 2026-11-21 — step 11, QA-03: era `pt-BR.json`)*: só as 25 chaves que o `examples/locales/pt.json` do archify não tem e as sobrescritas de
  `viewer.kind.*` e da legenda, que valem nos dois idiomas (D2, D12); o gerador mescla com o catálogo do archify *(corte do ponytail, step 6)*
- `verifica-mapa.mjs`: a amostra da ADR-06 (um HTML do mapa por tipo, idioma e tema, e o comparativo no
  tema escuro) é escolhida sozinha a partir do que o build gerou, então o passo não volta a mexer no conferidor *(corte do ponytail, step 6)*; os CT-B do `05` moram nele (D17)
- Um diagrama de cada um dos dez tipos e um comparativo, em pt e en, passam por `npm run build` (só `render`,
  ADR-01), `verifica-links` e o conferidor de acessibilidade com a tolerância da ADR-06, num
  `git clone --depth 1` do branch dentro de um contêiner Linux (Docker local) — o caminho do `pages.yml`
- Confere as cinco coisas que nunca rodaram: a saída en (os tipos de peça não podem cair em "Frontend" ou
  "Cloud"); o piso de 6,5 px no Chromium do Linux; o `verifica-links` sobre HTML do archify; o `--repo-root`
  num clone raso; o download do archify pelo commit
- Mede o `render` e o `visual-check` por HTML no Linux, para decidir se o CI confere a leitura de todos os
  HTML do mapa ou de uma amostra
- Saída: as correções que o esqueleto impõe às ADRs e ao plano, em `## Desvios do Plano` do `03`
- **Atende**: RQ-01, RQ-06, RQ-07, RQ-25, P-07
- **Logs**: `[mapa] archify {commit curto} pronto em {pasta}` · `[mapa] ERRO archify — {motivo}` e o formato de
  `## Channel de Log da Feature`

### 2. Modelo do mapa — peças, ligações e diagramas

> Skills: `ponytail`

- **Path**: `site/mapa/pecas.json`, `site/mapa/ligacoes.json`, `site/mapa/diagramas/{slug}.json`,
  `site/mapa/posicionar.mjs` *(corte do ponytail, step 6)*
- Normaliza num formato só as varreduras do step 3 (os três grafos de painel, as transversais, a IA e o
  esquema), com ids estáveis entre os modos (o `compare` depende disso):
  - **peça**: `{id, nome, tipo, paineis[], registrada: ["sem"|"com"], condicao?: {modo?: "sem"|"com",
    chave?, fabrica?, evidencia: {arquivo, trecho}}, origem: "pacote"|"proprio"|"pacote+proprio", pacote?,
    features: ["F-nn"], evidencia: [{arquivo, trecho}]}` — `registrada` são os modos em que o painel a registra, e
    `condicao` é o que a deixa indisponível apesar de registrada (D18);
    `tipo` ∈ painel, recurso, pagina, widget, plugin, provider, middleware, servico, model, policy, setting,
    comando, livewire *(alterado em 2026-11-20 — step 7: `pacote` e `rota` saíram da lista; pelo glossário o pacote
    é a origem da peça, não a peça, e rota não é peça com nome da RQ-24; a classe de pacote usada pelo kit entra com
    o tipo do que ela é e `origem: "pacote"`)*
  - **ligação**: `{de, para, verbo, paineis[], modos: ["sem"|"com"], evidencia: {arquivo, trecho}}` *(alterado em
    2026-11-20 — step 7, D18)*
  - **evidência**: âncora de trecho (ADR-03); o trecho tem no mínimo 12 caracteres e não pode estar só em
    comentário
- Peças de tipo recurso, página, widget e plugin que um painel registra citam pelo menos um F-id (é o elo com o
  catálogo, e o piso da varredura — D13) *(alterado em 2026-11-20 — step 7: o plugin entra no piso)*
- `diagramas/{slug}.json`, um arquivo por diagrama — a pasta é o manifesto *(corte do ponytail, step 6)*. Todo arquivo traz `slug`, `tipo`,
  `titulo {pt, en}`, `paineis[]` *(alterado em 2026-11-20 — D28: era `painel?`; vazio quer dizer do kit inteiro)* e
  `modos` (o diagrama que vale para os dois modos lista os dois — P-09) e uma de
  três formas:
  - **architecture**: as peças, as ligações e os agrupamentos que ele desenha, por id (o agrupamento nomeia os
    membros), as portas para outros mapas, as fronteiras e o layout — célula ou posição e tamanho de cada peça,
    iguais nos dois idiomas (ADR-01, ADR-07)
  - **tree de registro**: só o painel e o modo; as folhas saem de `pecas.json`
  - **curado** (sequence, workflow, lifecycle, dataflow, erd, class, timeline, waterfall): o próprio JSON de
    entrada do archify, com cada texto como `{pt, en}` e cada evidência como âncora `{arquivo, trecho}`; o ERD
    é conferido contra o esquema vivo, e a timeline cita versões que a guarda confere no `CHANGELOG.md`
- Orçamento da ADR-07 em todo mapa de domínio: até 30 peças e 6 portas, até 40 ligações desenhadas, no máximo um
  hub de 12; o autor mira 80 %
- `posicionar.mjs {slug}` (desenvolvimento, nunca no build): busca um layout que passe no `validate` do archify
  com a qualidade do diagrama, usando só a CLI, e grava o layout no arquivo do diagrama
- A conversão das varreduras usa um script descartável na pasta de pesquisa; só o modelo é commitado
- **Atende**: RQ-04, RQ-05, RQ-08, RQ-10, RQ-11, RQ-19, RQ-20, RQ-24, P-05, P-09, P-10
- **Logs**: `posicionar.mjs` escreve `[mapa] layout {slug} — tentativa {n}: {diagnósticos}` e o layout escolhido

### 3. Fluxos bilíngues

> Skills: `ponytail`

- **Path**: `site/mapa/diagramas/{slug}.json` (forma **curado** do passo 2) *(corte do ponytail, step 6)*
- Cada fluxo da matriz é o JSON de entrada do archify com os textos em `{pt, en}` e as evidências em âncora; o
  gerador projeta no idioma
- Partem dos fluxos estruturados da pesquisa (transversal-auth, IA, `/infra`, `/admin`, `/app`, tenancy e os
  comandos `kit:*`); o texto en é traduzido pelo Codex (`codex exec --yolo`, RQ-12) e revisado pela sessão
- Escritos à mão a partir do código (D5); a sequência tem no máximo 8 participantes e nenhuma auto-mensagem (o
  archify não as aceita: a ação interna vai numa nota); o `lifecycle` e o `dataflow` saem do código, não da prosa
  da pesquisa, que errou duas vezes no spike
- **Atende**: RQ-09, RQ-10, RQ-11, RQ-12, P-02, P-04
- **Logs**: não se aplica (dado)

### 4. Gerador

> Skills: `ponytail`

- **Path**: `site/mapa/gerar.mjs`, `site/package.json` (`"build": "astro build && node mapa/gerar.mjs"`)
- `gerar.mjs`: lê as peças, as ligações e a pasta `diagramas/`; só architecture e a tree de registro têm
  construtor (função pura `(diagrama, modelo) → IR do archify`); os tipos curados já são IR. Um passe genérico
  projeta cada `{pt, en}` no idioma *(corte do ponytail, step 6)*, resolve as âncoras de evidência na revisão `HEAD`
  (`git show HEAD:{arquivo}`), põe `meta.repository {url, revision}`, `meta.locale` (`pt-BR` com o catálogo,
  ou `en`) e os cards de prova (D4), grava o JSON numa pasta temporária fora do `dist/` e roda
  `archify render {tipo} … --repo-root {raiz}` *(alterado em 2026-11-21 — step 11, QA-03: sem `--quality` na linha de comando: o perfil fica em cada arquivo de diagrama, `meta.quality_profile`, que o archify respeita quando a opção não vem)* para `dist/mapa/{pt,en}/{slug}.html`;
  depois roda o `compare architecture` do resumo do `/admin` e do `/app` (sem → com, D8). Nenhum sidecar do
  archify vai para o `dist/`. *(alterado em 2026-11-20 — spike)* O build só renderiza: nada de `finalize`, que
  exige navegador (ADR-01)
- Nos mapas de architecture, as setas saem sem rótulo e com a variante de traço do verbo; um card de legenda
  explica a convenção e os cards de prova listam cada ligação desenhada (ADR-07, D4)
- Para no primeiro diagrama que o archify recusar, com o diagnóstico; sai com código diferente de zero
- **Atende**: RQ-06, RQ-07, RQ-08, RQ-09, RQ-10, RQ-11, RQ-24, RQ-25, P-04, P-06, P-07
- **Logs**: o formato de `## Channel de Log da Feature`

### 5. Catálogo único

> Skills: `ponytail`

- **Path**: `docs/pt/operacao/roteiro-de-features.md`, `docs/en/operacao/roteiro-de-features.md`,
  `docs/{pt,en}/operacao/index.md`, `docs/pt/operacao/depois-de-criar-resources.md` (e o espelho en),
  `tests/Browser/RoteiroDoKitTest.php` (só o docblock do cabeçalho)
- Coluna nova **Origem** (`pacote` + nome do pacote, `próprio`, `pacote + próprio`; en: `package`, `own`,
  `package + own`) em todas as seções
- As features sem número entram a partir de F-72, na seção do tema, na ordem de entrada (P-08); "Instalação e
  operação" e "Desenvolvimento" entram como seções novas, no fim
- As defasagens listadas em `## Análise dos Arquivos Existentes` são corrigidas sem mudar número
- O Codex confere, de forma independente, cada linha nova contra o código (RQ-12)
- **Atende**: RQ-04, RQ-05, RQ-12, RQ-19, RQ-20, RQ-21, RQ-22, P-02, P-08
- **Logs**: não se aplica

### 6. Página do mapa no site

> Skills: `ponytail`

- **Path**: `docs/pt/referencia/mapa-interativo.md`, `docs/en/referencia/mapa-interativo.md`, `site/sidebar.json`,
  `site/redirects.json`, `site/public/{pt,en}/referencia/mapa-interativo.html`, `docs/{pt,en}/referencia/index.md`,
  `docs/{pt,en}/referencia/arquitetura-em-diagramas.md` (uma linha de prosa levando ao mapa)
- Abre com um parágrafo de prosa (vira a `description`); seções: como usar o visualizador; por painel (tabela
  painel × sem/com organização, com `<a href="../../../mapa/{idioma}/{slug}.html">` cru em cada célula); fluxos;
  dados, classes e tempos; tipos usados (a matriz, com o motivo de cada célula vazia — P-06); como o mapa é
  conferido e os limites (P-05, ADR-06, D7, D8)
- Títulos fora do baseline congelado e dos títulos de landing (`[CT-01]`, `[CT-03]`); sem `the` em pt e sem
  `não|que|é|são|sobre` em en (`[CT-19]`)
- Stub, slug e redirect pelo `site/converter.mjs`, com o diff conferido: só a página nova pode mudar
- **Atende**: RQ-03, RQ-06, RQ-07, RQ-10, RQ-11, P-01, P-02, P-06
- **Logs**: não se aplica

### 7. README

> Skills: `ponytail`

- **Path**: `README.md`, `README.en.md`
- Logo depois da linha "Veja todos os diagramas…", um `###` novo com uma frase e a tabela painel × modo levando
  à página do mapa (forma `.html`, `[CT-14]`) e ao catálogo; nenhuma imagem nova (D1); nenhum Mermaid novo (P-01)
- Contadores: features especificadas, project rules, arquivos de teste e o badge de casos de teste, no fim
- **Atende**: RQ-02, P-01, P-02
- **Logs**: não se aplica

### 8. Guardas

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Kit/MapaInterativo{,Catalogo,Publicacao,Diagramas}Test.php` e `tests/Tenancy/MapaInterativoTenancyTest.php` *(alterado em 2026-11-21 — step 11, QA-03: cinco arquivos, um dono por arquivo, desde o passo 8)* (helpers cruzados,
  se houver, em `tests/Pest.php`)
- Os CT nascem do `04` (step 7). Escopo previsto: integridade do modelo; âncoras de evidência; completude da
  camada de registro contra o painel vivo nos dois modos; catálogo (unicidade, paridade pt/en, só acréscimo,
  todo F-id citado existe, toda feature do catálogo aparece em alguma visualização); manifesto × página do site
  × matriz; controles negativos ("mundos alterados")
- Sentinela `naArvoreDoKit()` em todo caso que lê `docs/`, `site/` ou o README
- **Atende**: RQ-17, RQ-21, P-03, P-05, P-08
- **Logs**: não se aplica

### 9. Rule do catálogo

> Skills: `requirement-to-rule`

- **Path**: `.ai/rules/catalogo-de-features.md`, `.ai/rules/index.md`
- A rule manda: feature nova entregue pelo kit ganha o próximo F-id no roteiro (pt e en), com a origem; nenhum
  número é trocado ou reaproveitado; a peça que a implementa entra no modelo do mapa com o F-id. Escopada para
  não valer em projeto derivado (onde `docs/` não chega)
- **Atende**: RQ-23, P-08
- **Logs**: não se aplica

### 10. Documentação de manutenção e CHANGELOG

- **Path**: `docs/{pt,en}/operacao/desenvolvendo-o-kit.md` (como gerar e conferir o mapa localmente), `CHANGELOG.md`
  (`[Unreleased]`)
- **Atende**: RQ-16
- **Logs**: não se aplica

### 11. PR com CI verde

- Abrir o PR para `main`, acompanhar o CI até verde; **sem** merge, **sem** release, **sem** disparar o `pages.yml`
  pelo branch
- **Atende**: RQ-14, RQ-26
- **Logs**: não se aplica

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo aplica a escada de simplicidade: reutilizar o que existe; stdlib do Node e do PHP antes de código
> próprio; nenhuma dependência além do archify; o mínimo que funciona.
>
> **Caveman ativo em modo `ultra`** na comunicação com o usuário. Wiki, código, commits e PR em prosa normal.
>
> **Baseline antes do primeiro commit**: a suíte `Kit` e a `Tenancy` em `main`, com as falhas pré-existentes por nome.

## Mapeamentos

| Varredura (step 3) | Campo da pesquisa | Campo do modelo |
|---|---|---|
| `/admin` | `nodes[].{id,nome,tipo,origem,pacote,tenant,features,evidencia}`, `links[].{from,to,rel,evidencia}` | peça e ligação |
| `/app` | `nodes[].{id,nome,cat,tipo,origem,tenant,ev}`, `links[].{de,para,v,cond,ev}` | peça e ligação (`cond: com_tenant` → `tenancy: "com"`) |
| `/infra` | `nodes[].{id,nome,tipo,subtipo,origem,painel,tenant,evidencia}`, `links[].{de,para,tipo,evidencia}` | peça e ligação (tenancy da ligação derivada dos nós e das condicionais de tenancy) |
| IA | `nodes[]`, `links[].{from,to,rel,evidencia,tenant}` | peça e ligação |
| Ids de pacote | `pkg:`, `composer:`, `vendor/nome` | `vendor/nome` |
| Evidência | `caminho:Simbolo:linha` | `{arquivo, trecho}` com o trecho tirado da linha citada na tag `v0.56.0` |

## Testes

> Ver `04-casos-de-teste.md` para a especificação dos cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários em navegador, se a `feature-test-design` decidir que existem.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest tests/Kit/MapaInterativoTest.php tests/Tenancy/MapaInterativoTenancyTest.php --compact`
- [ ] Suítes `Kit` e `Tenancy` comparadas à baseline de `main`
- [ ] `npm run build` em `site/`, `node verifica-links.mjs` e o conferidor de acessibilidade, num clone raso no
  contêiner Linux do passo 1 (o antigo passo 12) *(corte do ponytail, step 6)*
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9), antes da reconciliação
- [ ] Reconciliação do step 10 (scripts da feature-wiki)
- [ ] CI verde no PR

## Commits

- `:memo: docs(specs): wiki da feature mapa-interativo-do-kit`
- `:card_file_box: feat(mapa): modelo do mapa e fluxos bilíngues`
- `:sparkles: feat(mapa): gerador do mapa interativo com o archify`
- `:memo: docs(roteiro): catálogo único de features`
- `:memo: docs(site): página do mapa interativo e seção no README`
- `:white_check_mark: test(mapa): guardas do mapa e do catálogo`
- `:memo: docs(rules): rule do catálogo de features`
