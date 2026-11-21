# Casos de Teste — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md` (RQ-01 a RQ-26, P-01 a P-12, Adendo 1, perguntas Q6, Q7, Q9, Q11, Q12, Q13 e Q16) · Plano: `01-plano-acao.md` (só paths, rotas, stack e `## Superfície de UI`, recebidos por trecho no despacho) · `02`: só `## Superfície Livewire` ("não se aplica")
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação: ela ainda não existe.
> Base congelada: commit `d9608c5a` (2026-11-19). Todo "da base" deste arquivo foi medido nele, nesta derivação.
> **Revisão adversarial: feita por decisão da sessão, em duas rodadas, as duas fechadas.** O perfil não a exigia: nenhuma área é `completo` e nenhuma tem Impacto 3 (o motivo está abaixo da tabela). A primeira rodada deu 34 achados (ADV-01 a ADV-34), fechados na quarta rodada; a segunda deu 32 (ADV2-01 a ADV2-32), fechados na quinta. É o teto de duas rodadas da skill: não há terceira, e todo oráculo novo ou reescrito no fechamento da segunda foi conferido por comando contra dados reais (`## Revisão adversarial — segunda rodada`).
> *(alterado em 2026-11-20: rodada de revisão do step 7 — perguntas renumeradas para Q6 a Q15 e respondidas pela sessão; regras R27 a R33, CT-28 a CT-34 e CT-B04 a CT-B06 novos; os CT-B saem do conferidor de acessibilidade para um conferidor próprio, `site/verifica-mapa.mjs`)*
> *(alterado em 2026-11-20: terceira rodada — as duas perguntas novas da rodada anterior viraram Q16 e Q17 e foram fechadas; a R27 e o CT-28 partem dos manifestos e conferem as duas direções, com a referência de pacotes)*
> *(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial e P-12; regras R34 a R36, CT-35 a CT-43 e CT-B07 novos; R30, CT-32, CT-33 e R24-M5 estacionados na Q11; CT-B02 e CT-B03 reescritos pelos fatos de plataforma medidos pela sessão)*
> *(alterado em 2026-11-20: ajuste da quarta rodada — as duas perguntas novas da rodada viraram Q18 e Q19, decididas pela sessão no `01` (D25 e D26); a R10 ganha a quinta forma; a R5 casa o termo por palavra inteira; a R6 troca a regra da rota (40 linhas nos dois idiomas); a leitura da porta fica fechada pela P-10)*
> *(alterado em 2026-11-20: quinta rodada — fechamento da segunda revisão adversarial, a última; regra R37 e CT-44 e CT-45 novos; oráculos conferidos por comando contra o código, o catálogo, o README e o esqueleto do mapa já construído; os do site construído, conferidos de novo no commit `4adaf9a`, que a sessão publicou durante a rodada com os construtores do mapa)*

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — catálogo de features: base, numeração, fontes (README, páginas, pacotes), origem, cobertura pelo mapa e piso do registro | 2 | 2 | 4 | padrão |
| B — modelo de peças e ligações, provas e registro das peças dos painéis (a disponibilidade está estacionada na Q11) | 3 | 2 | 6 | padrão |
| C — página do mapa, grade painel × modo, tipos de visualização, diagramas de dado e dependências | 3 | 2 | 6 | padrão |
| D — publicação e site construído: build, fluxos de CI, visualizador, idioma da interface e acessibilidade | 3 | 2 | 6 | padrão |
| E — README e os diagramas Mermaid da base | 2 | 1 | 2 | mínimo |
| F — rule do catálogo | 1 | 2 | 2 | mínimo |

- **Por que Impacto 2 e não 3.** O mapa é documentação pública de um kit de código aberto. Um defeito desenha errado, engana o leitor e custa retrabalho, e o build seguinte o desfaz. Nenhuma área toca dinheiro, autorização, dado de terceiro ou perda irreversível.
  - Só um caminho publicaria conteúdo de fora do repositório: uma prova citando arquivo fora do commit. Ele não alcança segredo, porque o artefato publicado sai do CI, de um checkout limpo (`pages.yml`). O CT-09 o reprova mesmo assim, porque o link dessa prova daria 404 (P-07).
  - A tolerância da P-11 a dois defeitos do archify afrouxa o falha fechado. O risco dela é de acessibilidade do site público, e é reversível. Fica no Impacto 2, mas a técnica da R32 é escalada (abaixo).
- **Por que P é 3 em B, C e D.**
  - B confronta o modelo com o registro vivo dos três painéis nos dois modos. São centenas de peças (o spike da P-10 mediu 243 peças e 496 ligações só no `/admin`), as provas são por trecho, e há peça que o plugin registra e peça que só abre com uma condição.
  - C cruza dez tipos e o comparativo com três painéis e dois modos, com célula compartilhada, e confere diagrama de dado contra o CHANGELOG.
  - D baixa o archify no build (integração externa), depende de JavaScript no navegador e tolera dois defeitos de terceiro na acessibilidade.
- **Técnica escalada.**
  - A R4 usa BVA de 3 valores na borda 71/72, e não de 2. "Começa na F-72" e "pula a F-72" só se distinguem com o terceiro valor. A borda−1 (71) morre no CT-03.
  - A R32 usa o teto de mutantes do perfil completo, que é 6. A tolerância afrouxa o falha fechado (P-11), e cada um dos seis mutantes é um jeito diferente de ela vazar.
- **Técnicas aplicadas.**
  - EP.
  - BVA de 3 valores na numeração.
  - Tabela de decisão e produto cartesiano fechado. No registro, 3 painéis × 2 modos × 5 tipos de registro. No piso da varredura, 3 × 2 × 4, mais as 135 classes de `app/` fora de `app/Filament` (P-12). Na grade e na arquitetura por modo, 3 × 2. Nos tipos, 63 células por idioma. Na origem, F-nn × pacote nomeado × pacote da peça. No tipo de peça, classe × tipo declarado.
  - Estado × operação no número do catálogo: 15 células.
  - Controle negativo em cópia adulterada em memória, no molde de `conferirDg10()` de `tests/Kit/GuardasDosDiagramasTest.php`.
  - Controle sintético no conferidor do site, decidido pela mesma função que decide o real (o molde dos controles de `site/verifica-acessibilidade.mjs`).
- Cenários: 43 · Regras: 36 · Mutantes previstos: 219 · Sem matador: 7 (mais 7 CT-B no `05`, nas regras R6, R12, R13, R14, R21, R23, R24, R31 e R32; os mutantes delas estão contados aqui)
<!-- derivado por grep -c (template-04.md §Contagem do cabeçalho) -->

**Divergências declaradas.**
- **A costura `Pest feature HTTP` mora em `tests/Kit` e `tests/Tenancy`, não em `tests/Feature`.** No kit, o `tests/Pest.php` liga o `TestCase`, o `RefreshDatabase` e o grupo `kit` a essas duas pastas. O `tests/Feature` é do projeto que nasce do kit. O `tests/Unit` não liga o `TestCase`, então uma guarda que precisa do `base_path()` ou do painel não cabe nele. Vence a convenção do projeto.
- **Os CT-B rodam num conferidor Node + Playwright próprio, `site/verifica-mapa.mjs`, e não no `pest-plugin-browser`.**
  - O plugin sobe a aplicação Laravel e não serve site estático (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`).
  - O arranjo é o da wiki irmã `diagramas-da-arquitetura`, mas num arquivo à parte: o `site/verifica-acessibilidade.mjs` já carrega o `[CT-B01]` e o `[CT-B02]` daquela wiki, e os IDs desta também começam em 01.
  - O `ids-ct.sh` só lê o `it()` e o `test()` do Pest, então acusa os CT-B desta wiki como sem teste. Isso é esperado, por desenho.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | <ul><li>Em `site/mapa/`: o modelo de peças, o modelo de ligações e um arquivo por diagrama; a tradução da interface do visualizador; o gerador e o download do archify.</li><li>A página "Mapa interativo" em pt e en, com navegação, redirecionamento e stub.</li><li>O catálogo (a página Roteiro de features em pt e en), a referência de pacotes instalados e o README em pt e en.</li><li>A rule do catálogo e a linha dela no índice.</li><li>`site/package.json`, o conferidor novo `site/verifica-mapa.mjs` e o conferidor de acessibilidade.</li><li>`pages.yml` e o job `site` do `ci.yml`.</li><li>Nenhum arquivo de `app/` muda (RQ-15 e a Superfície Livewire do `02`).</li></ul> | CT-01 a CT-31, CT-34 a CT-45 (CT-32 e CT-33 estacionados na Q11) |
| F | <ul><li>Desenhar peças e ligações por painel e por modo.</li><li>Provar cada ligação com o arquivo e o trecho.</li><li>Cobrir toda feature do catálogo e toda peça registrada.</li><li>Mostrar a grade painel × modo e os tipos. A condição de disponibilidade está estacionada na Q11.</li><li>Ancorar no código cada transição de fluxo.</li><li>Unificar e numerar o catálogo.</li><li>Falhar quando o código diverge (P-03).</li><li>Publicar no build do commit.</li><li>Deixar o leitor buscar a peça e ler a prova, na língua da página.</li><li>Passar pela conferência de acessibilidade por amostra.</li></ul> | CT-03, CT-06, CT-08 a CT-14, CT-17, CT-20, CT-25, CT-29 a CT-31, CT-36 a CT-41, CT-44, CT-45, CT-B01 a CT-B07 |
| D | <ul><li>Peças e ligações: 243 e 496 só no `/admin`.</li><li>Os diagramas.</li><li>Na base: 74 F-nn por idioma (F-01 a F-71, mais F-03a, F-03b e F-03c), 46 itens de feature do README por idioma, 39 páginas do site por idioma, 91 pacotes nos manifestos e 87 na referência por idioma (os quatro que faltam a entrega acrescenta, Q16), 141 versões com data no CHANGELOG, mais o `[Unreleased]`, sem data, e 135 classes de `app/` fora de `app/Filament` (P-12). Das 74 linhas do catálogo, 40 trazem no "onde fica" um caminho de rota sem "→", nos dois idiomas.</li><li>Provas: arquivo do kit versionado (válida), e as inválidas — ausente, vazia, só de espaços, só em comentário, fora do commit (`vendor/` ou fora do git).</li><li>Peças de disponibilidade condicional: pelo modo (`TenantResource`) e pela chave (`AgenteIaResource`). A marca delas está estacionada na Q11.</li><li>Os modos (sem e com tenant), os 10 tipos e o comparativo, os idiomas pt e en, os temas claro e escuro.</li><li>Cardinalidade 0/1/N: painel sem diagrama, feature sem peça, peça registrada ou classe de `app/` sem F-nn, ligação sem diagrama, transição sem âncora, célula sem diagrama nem motivo, amostra vazia.</li><li>Dado de outro tenant não se aplica: o modo é configuração, não dado.</li></ul> | CT-01, CT-05, CT-06, CT-09, CT-14, CT-17, CT-21, CT-28, CT-29, CT-31, CT-35, CT-39, CT-41, CT-B05 |
| I | <ul><li>A página estática `/{pt,en}/referencia/mapa-interativo/` e os HTML `/mapa/{pt,en}/{slug}.html`.</li><li>O README no GitHub, o catálogo, a referência de pacotes e a rule, que os agentes leem pelo índice.</li><li>O registro vivo dos painéis: `Filament::getPanels()` e os getters de cada painel; o roteador da aplicação, para o "onde fica" do catálogo.</li><li>Os arquivos citados pelas provas e pelas âncoras de fluxo, e os links de evidência para o repositório do `support.source` do `composer.json`.</li><li>O `CHANGELOG.md`.</li><li>`npm run build` e os dois workflows.</li></ul> | CT-07, CT-11, CT-12, CT-15, CT-25, CT-26, CT-28, CT-31, CT-36, CT-39, CT-42, CT-B03 |
| P | <ul><li>PHP 8.4, Laravel 13.42, Filament 5.10.2 (getters conferidos no vendor) e Pest 5.3.7.</li><li>Node 24, Astro 7.3.3 + Starlight 0.42.2, Playwright 1.63 e axe-core 4.13.</li><li>archify 3.2.0 no commit `4478129`, segundo o README e o `schemas/README.md` do archify nesse commit: não tem catálogo pt (tem en, zh-CN, zh-TW, es, ko e ar); o `meta.locale` escolhe a interface e o `lang`, mas não traduz o texto escrito; a prova é por nó (`sources`, de 1 a 3), sem prova por aresta; a casca do comparativo existe só em inglês (P-11); e há dois defeitos de acessibilidade tolerados (P-11).</li><li>Medido pela sessão num HTML do archify, no commit fixado, no Chromium:<ul><li>o passaporte de uma peça em foco mostra o rótulo, o tipo, "origem verificada {repositório} @ {commit}", as provas da própria peça (até três) como links para o GitHub no commit, e só as contagens das ligações;</li><li>os cards ficam no painel lateral (`.reader-rail .card`), sempre presentes;</li><li>o DOM traz `data-node-id`, `data-node-label`, `data-edge-from`, `data-edge-to` e `data-edge-id`, e o `lang` do documento segue o `meta.locale`;</li><li>nenhuma requisição externa e nenhum erro.</li></ul></li><li>O `examples/locales/pt.json` do archify (461 chaves) fica fora do manifesto dos catálogos embutidos. O kit entrega o pt pelas traduções do diagrama, e o en é o `locales/en.json` (486 chaves).</li><li>Nos mapas de arquitetura, a aresta é direta, de agrupamento ou de porta.</li><li>GitHub Pages sob a base `/filament-starter-kit-easy`.</li><li>Desenvolvimento no Windows (caminho com `\`, fim de linha CRLF) e CI no Linux.</li></ul> | CT-09, CT-14, CT-16, CT-40, CT-B01 a CT-B07 |
| O | <ul><li>O leitor do site avalia o kit.</li><li>O mantenedor cria feature: número e rule.</li><li>O agente de IA lê a rule pelo índice.</li><li>O CI roda em todo PR e publica no Pages.</li><li>Volume: nenhum painel cabe num desenho só (P-10).</li><li>Uso indevido: prova copiada de outra ligação ou tirada de outro arquivo, prova que só casa em comentário, número reaproveitado, aresta inventada ou invertida, catálogo pt com o texto en, conferidor que não reprova o job, tolerância de acessibilidade larga demais.</li></ul> | CT-03, CT-07, CT-10, CT-14, CT-40, CT-42, CT-B06, CT-B07 |
| T | <ul><li>O mapa reflete o commit que publica (P-07), e a linha do link de prova é a desse commit.</li><li>A suíte do kit roda em todo PR, e o job `site` só roda quando o PR toca `docs/` ou `site/`. Por isso as guardas de prova, de registro e de catálogo moram na suíte do kit.</li><li>A numeração só cresce (P-08), e o número removido continua removido nas entregas seguintes.</li><li>Envelhecem: o trecho que some do código, a âncora de fluxo, o recurso novo no painel, a classe nova em `app/` (P-12), o pacote atualizado, a versão nova do CHANGELOG e o tempo medido.</li></ul> | CT-03, CT-04, CT-09, CT-11, CT-25, CT-29, CT-31, CT-39, CT-41, CT-B02 |

## Mapa de Regras

*(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial; R34 a R36 novas; R30 estacionada na Q11. Quinta rodada — segunda revisão adversarial; R37 nova)*

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O catálogo é um só, com as mesmas F-nn em pt e em en, a mesma feature e o mesmo estado em cada número, uma linha por F-nn e uma origem do glossário em cada | A (padrão) | RQ-21, RQ-22, RQ-04, RQ-05, P-02 | EP + controles | CT-01 |
| R2 — README, páginas do site e mapa só citam F-nn que o catálogo tem, e nenhum deles lista feature por fora dele | A (padrão) | RQ-22, RQ-21, P-08 | EP + controles | CT-02 |
| R3 — Toda F-nn da base continua com a sua feature, ou fica marcada como removida com o motivo, e a removida não volta | A (padrão) | P-08, RQ-22 | estado × operação + controles | CT-03 |
| R4 — A feature nova recebe o próximo número livre: a F-72, e as seguintes em sequência, sem buraco e sem sufixo novo | A (padrão) | P-08 | BVA 3-valores (escalada) | CT-04 |
| R5 — Todo item de feature do README da base e toda página do site correspondem a ao menos uma F-nn — os seis itens que a base não cobre, pelo termo fixado —, ou a página está na lista das que não descrevem feature, com o motivo | A (padrão) | RQ-19, RQ-04, RQ-05, P-02 | EP por fonte + controles | CT-05, CT-35 |
| R6 — Toda F-nn vigente é entregue por peça desenhada da origem que ela declara — do pacote que a linha nomeia, ou, no código próprio, da rota do "onde fica" —, com a F-nn no desenho da peça; todo caminho do "onde fica" resolve; e nenhuma peça entrega F-nn removida | A/B (padrão) | RQ-04, RQ-05, RQ-21, P-03, P-10, P-08 | EP por origem + tabela de decisão + controles | CT-06, CT-36, CT-44, CT-B01 (no `05`) |
| R7 — Manter o catálogo único é uma rule indexada, que alcança os lugares onde a feature nasce e manda o número só crescer | F (mínimo) | RQ-23, P-08 | EP | CT-07 |
| R8 — Toda peça do modelo é peça com nome que existe, do tipo que a classe dela é, dentro do conjunto fechado, e aparece uma vez | B (padrão) | RQ-24, P-03 | EP + tabela de decisão (classe × tipo) + controles | CT-08 |
| R9 — Toda prova resolve no commit que publica: o arquivo é versionado no kit, o trecho está nele, fora de comentário pela sintaxe do tipo de arquivo, e a prova não guarda número de linha, nem no modelo nem nos arquivos de diagrama | B (padrão) | RQ-24, P-03, P-04, P-07 | EP (uma partição inválida por linha) + controles | CT-09 |
| R10 — A prova é a da ligação: está no arquivo da origem, num trait ou concern que a origem usa, no provider do painel (descoberta, plugin ou configuração), no método de contrato do alvo ou, quando a origem é peça de pacote, num arquivo do kit que nomeia a origem; o trecho é o dessa ligação; e as duas pontas são peças | B (padrão) | RQ-24, P-03, P-07 | EP (forma da prova × arquivo) + controles | CT-10 |
| R11 — Tudo que o painel registra tem ligação de registro no modelo, e o modelo não dá a um modo o que o painel não registra nele | B (padrão) | P-05, RQ-08, RQ-10, RQ-11, P-03 | tabela de decisão (3 × 2 × 5, nas duas direções) + controles | CT-11, CT-12, CT-13 |
| R12 — Toda ligação do modelo está desenhada em diagrama publicado, direto ou por agrupamento que nomeia os membros num card do diagrama ou no rótulo dele; menção em texto não conta | B (padrão) | P-10, RQ-08 | EP (forma da aresta) + controles | CT-14, CT-B02 (no `05`) |
| R13 — Toda ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros — traz a prova, com o arquivo e o trecho, num item próprio de card do painel lateral do próprio diagrama, inteiro e à vista sem foco; o comparativo fica fora | B (padrão) | P-04, P-07, RQ-08 | rastreio exaustivo no site construído | CT-B02 (no `05`) |
| R14 — O leitor acha a peça pela busca, e o passaporte dela mostra as provas da própria peça, com o link para o repositório do kit no commit do build | B (padrão) | RQ-07, P-04, P-07 | EP (idioma) + amostra por `architecture` | CT-B03 (no `05`) |
| R15 — A página de cada idioma leva, por `<a href>` relativo, a todo diagrama do seu idioma, e só a diagrama que existe | C (padrão) | RQ-03, P-02 | EP | CT-15 |
| R16 — Todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido; o título só fica igual quando é o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código | C (padrão) | P-02, RQ-03 | EP (tipo de texto) + controles | CT-16 |
| R17 — A grade painel × modo é inteira: cada painel do registro tem diagrama próprio em cada modo, o compartilhado vem marcado e só ele, e a célula não leva a diagrama do outro modo nem de outro painel | C (padrão) | RQ-10, RQ-11, P-06, P-09 | tabela de decisão (3 × 2) + controles | CT-17, CT-18, CT-43 |
| R18 — Só se compartilha o que é igual nos dois modos, o que é igual não se duplica, e toda ligação desenhada vale no modo do diagrama | C (padrão) | P-09, RQ-11 | tabela de decisão + controles | CT-19 |
| R19 — Cada um dos dez tipos e o comparativo têm uso ou motivo, cada combinação tipo × painel × modo tem diagrama ou motivo traduzido, e só é do kit inteiro o diagrama que não desenha peça registrada por painel | C (padrão) | RQ-06, RQ-25, P-06, P-02 | produto cartesiano fechado (63 células por idioma) + controles | CT-20, CT-21 |
| R20 — Todo comparativo publicado vai do modo sem tenant (antes) para o com tenant (depois) do mesmo painel | C (padrão) | RQ-25, RQ-11, P-04 | EP + controles | CT-22 |
| R21 — Nenhuma dependência nova de visualização no site: o `site/package.json` fica com as dependências da base nos quatro campos, `site/mapa/` só tem os arquivos do mapa, e o HTML de cada diagrama não carrega recurso de fora dele | C (padrão) | RQ-25 | EP (lista fechada) + controles + rastreio no site construído | CT-23, CT-B01 (no `05`) |
| R22 — Há fluxo publicado, e, nos tipos `workflow`, `sequence` e `dataflow`, todo passo percorre peça do modelo | C (padrão) | RQ-09, P-03 | EP (tipo de fluxo) + controles | CT-24 |
| R23 — O mapa nasce no build do commit que publica, os dois fluxos de CI o conferem com o conferidor do mapa antes de enviar, e a falha do conferidor reprova o job | D (padrão) | P-07, RQ-03, RQ-17, P-11 | EP por workflow + controles | CT-25, CT-42, CT-B06 (no `05`) |
| R24 — Todo diagrama publicado abre desenhado, com as peças que a fonte dá a ele e a interface do visualizador montada, sem erro de console nem exceção, no idioma da página | D (padrão) | RQ-07, RQ-03, P-02 | rastreio exaustivo no site construído | CT-B01, CT-B03, CT-B04 (no `05`) |
| R25 — O README de cada idioma leva ao mapa do mesmo idioma | E (mínimo) | RQ-02, P-02 | EP | CT-26 |
| R26 — Os diagramas Mermaid da base continuam onde estavam | E (mínimo) | P-01, RQ-25 | EP | CT-27 |
| R27 — Todo pacote instalado está na referência de pacotes e numa F-nn de origem pacote, ou na lista dos sem feature própria, com o motivo; e todo pacote que a referência lista está instalado | A (padrão) | RQ-19, RQ-04, P-03 | EP por partição de pacote + controles | CT-28 |
| R28 — Toda peça que um painel registra — recurso, página, widget e plugin —, nos dois modos, e toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está na lista da guarda das peças e classes sem feature própria, com o motivo | A/B (padrão) | RQ-20, RQ-04, RQ-05, P-05, P-12 | tabela de decisão (3 × 2 × 4) + EP (classe de `app/`) + controles | CT-29, CT-30, CT-41 |
| R29 — Cada marco da linha do tempo cita uma versão do CHANGELOG com a data dela, e o título visível do marco aparece na seção dessa versão; e cada tempo medido traz a data e a fonte da medição | C (padrão) | P-03, P-06 | EP (uma partição inválida por linha) + controles | CT-31 |
| ~~R30 — Toda peça de disponibilidade condicional guarda no modelo a condição, com a âncora do código que decide, e mostra a condição em todo diagrama que a desenha~~ | estacionada — Q11 (ADV-17, ADV-31) | RQ-04, RQ-05, RQ-11, P-03, P-09 | — | ~~CT-32, CT-33~~ |
| R31 — A interface do visualizador está no idioma da página, e só a casca do comparativo fica em inglês; o catálogo pt que o kit entrega não copia o texto en, fora a lista fechada com o motivo | D (padrão) | P-11, P-02, RQ-03 | rastreio exaustivo no site construído + controles | CT-B04, CT-B07 (no `05`) |
| R32 — A conferência de acessibilidade audita a amostra inteira e tolera só os dois defeitos do archify, pela regra e pelo elemento | D (padrão; técnica escalada) | P-11, RQ-03, RQ-07 | amostra estratificada + controle sintético | CT-B05, CT-B06 (no `05`) |
| R33 — A página do mapa declara, nos dois idiomas, as duas exceções da P-11, a do comparativo na P-04 e o limite da varredura da P-12 | D (padrão) | P-11, P-12, P-04, P-02 | EP (idioma) | CT-34 |
| R34 — Os diagramas de arquitetura e a árvore de registro de um painel que valem num modo desenham, juntos, todo o registro do painel nesse modo, e nada que ele não registra nesse modo | C (padrão) | P-09, RQ-11, RQ-10, P-05 | tabela de decisão (3 × 2) + controles | CT-37, CT-38 |
| R35 — Toda transição de fluxo publicado tem âncora que resolve; no `lifecycle`, todo estado também; e duas transições seguidas ancoradas no mesmo método estão na ordem do código | C (padrão) | P-03, RQ-09 | EP + controles | CT-39 |
| R36 — O que o mapa desenha está no modelo: no mapa de arquitetura e na árvore de registro, toda aresta é ligação do modelo no mesmo sentido — direta, de agrupamento ou de porta —, e todo diagrama de painel só desenha, como peça, peça do painel ou do kit inteiro, além da caixa de porta | B (padrão) | P-03, RQ-24, P-10, RQ-10 | EP (forma da aresta) + controles | CT-40 |
| R37 — Toda marca de disponibilidade que o mapa publica é a do código: a chave que decide o acesso da peça, com o valor de fábrica dela, ou o modo | B (padrão) | P-03, RQ-04, RQ-05, RQ-11 | EP + controles | CT-45 |

### Cláusulas sem regra

- RQ-01 — restrição de processo, sem regra e sem cenário. A análise do archify, registrada antes do uso, confere-se pela wiki e pela ordem dos commits, no quality gate (dimensão A).
- RQ-12 — restrição de processo, sem regra e sem cenário. O Codex com `--yolo` como auxiliar confere-se pelos despachos do `03`.
- RQ-13 — restrição de processo, sem regra e sem cenário. A própria wiki a cumpre.
- RQ-14 — restrição de processo, sem regra e sem cenário. A RQ-26 substitui em parte o "até o fim". O resto se cumpre pelo conjunto implementado e verde.
- RQ-15 — restrição sobre o diff, sem regra e sem cenário. Um teste "`app/` não mudou" não sobrevive ao merge. O gate confere o `git diff --stat` sem `app/`, e a Superfície Livewire do `02` já declara que nenhum arquivo de `app/` muda.
- RQ-16 — condicional à RQ-15, sem regra e sem cenário. Se nenhum código de aplicação mudar, ela fica vazia. Se algum mudar, o gate confere no diff a documentação de cada mudança.
- RQ-17 — não-funcional, citada pela R23. O CT-25 prova que os dois fluxos de CI rodam os conferidores do site. O resto se cumpre pelo próprio conjunto: o Índice aponta arquivos das suítes padrão (`tests/Kit`, `tests/Tenancy`) e o conferidor do mapa. Um cenário "os testes existem" não falsificaria nada.
- RQ-18 — restrição de processo, sem regra e sem cenário. Os subagentes em paralelo conferem-se pelos despachos do `03`.
- RQ-26 — restrição de processo, sem regra e sem cenário. O PR abre com o CI verde, e merge e release só acontecem com o OK do mantenedor. Confere-se no PR. *(alterado em 2026-11-20)* O ADV-34 pedia um cenário para o executor que acumula o papel de aprovador, e foi rejeitado como cenário. É controle de processo desta cláusula: a sessão e o Codex nunca fazem merge, push na `main` nem tag, e a sessão confere HEAD, tags e o estado do PR antes e depois de cada despacho do Codex. Confere-se no quality gate e no PR.

> **Aviso à sessão.** O `rastreabilidade.sh` acusa "RQ sem CT" para RQ-01, RQ-12, RQ-13, RQ-14, RQ-15, RQ-16, RQ-18 e RQ-26 se o `## Cobertura do Requisito` do `01` não as marcar com passo "—" e justificativa na Observação. Não li o `01`, então não posso conferir.

### Cláusulas abertas e coberturas parciais

A parte de cada cláusula que já está decidida tem cenário. O recorte espera a resposta. Nenhuma RQ está inteira aberta: a RQ-20 deixou de estar com a Q8 decidida, e a P-12 deu a ela o piso das classes de `app/` (R28).

- RQ-09 — cobertura parcial: CT-24, CT-39; recorte {quais fluxos, e se todos} aberto (Q6); recorte {o trecho da transição do `lifecycle` faz a transição} lacuna declarada (R35-M5, D25)
- RQ-11 — cobertura parcial: CT-11 a CT-13, CT-17 a CT-19, CT-22, CT-37, CT-38, CT-43, CT-45; recorte {a marca da peça de disponibilidade condicional, e em que diagrama de modo ela entra, inclusive no "nem mais nem menos" do CT-37 (ADV2-29)} aberto (Q11); recorte {o modo de toda ligação de uso confere com o código, e o nó de fluxo com rótulo abreviado} lacuna declarada (R18-M5 e R18-M6)
- P-09 — cobertura parcial: CT-17 a CT-19, CT-37, CT-38, CT-43; recorte {se a disponibilidade por modo conta como diferença de estrutura para compartilhar o diagrama} aberto (Q11)
- RQ-04 — cobertura parcial: CT-01, CT-05, CT-06, CT-28 a CT-30, CT-35, CT-36, CT-41, CT-44, CT-45, CT-B01; recorte {a marca da peça de feature opcional, e em que diagrama de modo ela entra} aberto (Q11)
- RQ-05 — cobertura parcial: CT-01, CT-05, CT-06, CT-29, CT-30, CT-35, CT-36, CT-41, CT-44, CT-45, CT-B01; recorte {a marca da peça de feature opcional, e em que diagrama de modo ela entra} aberto (Q11)
- P-03 — cobertura parcial: CT-06, CT-08 a CT-13, CT-24, CT-28, CT-31, CT-36, CT-39, CT-40, CT-44, CT-45, CT-B01; recorte {em que diagrama de modo entra a peça condicional, e se o mapa a marca} aberto (Q11); recorte {o trecho da transição do `lifecycle` faz a transição} lacuna declarada (R35-M5, D25)

*(alterado em 2026-11-20: ADV-32)* A ordem dos passos saiu do recorte da Q6. A P-03 vale para todo fluxo publicado, qualquer que seja a lista. Por isso a âncora de cada transição e a ordem no mesmo método são da R35, e só a ordem entre arquivos ou métodos diferentes ficou como lacuna declarada (R35-M4).

**Estacionado na Q11 (ADV-17 e ADV-31).**
- **Por que estaciona.** A Q11 tem três leituras: a peça condicional entra marcada e desenhada só onde está disponível; entra marcada e desenhada onde está registrada; ou entra desenhada sem marca. Nenhuma afirmação sobre a marca vale nas três.
- **O que saiu do conjunto vigente, com os IDs reservados:**
  - a R30, com o CT-32 (o modelo guarda a condição e a âncora do código que decide) e o CT-33 (todo diagrama que desenha a peça mostra a condição);
  - a metade de condição do CT-B01 (o passo 4 do `05`);
  - o R24-M5.
- **Onde não aparecem.** Nenhum Gherkin nem linha vigente do Índice os define; a linha da R30 no Mapa e as do CT-32 e do CT-33 no Índice estão riscadas.
- **Quando voltam.** Com a resposta da Q11, junto com o ADV-17, que pede a detecção também no modo com tenant, cada chave com o valor oposto ao de fábrica e a linha da âncora que não resolve.
- **O que a segunda rodada mexeu aqui** *(alterado em 2026-11-20: ADV2-29 e ADV2-30)*. A Q11 segura também o "nem mais nem menos" do diagrama de arquitetura sem tenant do `/admin` para a peça condicional (CT-37). E a parte que vale nas três leituras — a marca publicada é a do código — saiu do estacionamento como a R37 (CT-45), vácua enquanto o mapa não marcar nada.

A RQ-19 tem cobertura completa desde a terceira rodada (CT-05, CT-28 e, na quarta, CT-35), com a Q16 fechada. A RQ-24 tem cobertura completa desde o ajuste da quarta rodada (CT-08 a CT-10, CT-40), com a Q19 decidida (D26).

Fechadas ou decididas nas rodadas de revisão, e com cenário:

| Pergunta | Cenários |
|---|---|
| Q7 | CT-05, CT-28 |
| Q8 | CT-29, CT-30, CT-41 |
| Q9 | CT-09, CT-10 |
| Q10 | CT-31 |
| Q12 | CT-34, CT-B04, CT-B05, CT-B06, CT-B07 |
| Q13 | CT-04 |
| Q14 | CT-03, CT-05 |
| Q15 | CT-15 |
| Q16 | CT-28 |
| Q17 | CT-28 |
| Q18 | CT-39 |
| Q19 | CT-10 |

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — arquivos versionados do catálogo, do README, da referência de pacotes, da rule, da página do mapa e dos workflows (Kit) | R1, R2, R3, R4, R5, R7, R21 (CT-23), R23, R25, R26, R27, R33 | Pest feature HTTP | **nova**: `tests/Kit/MapaInterativoTest.php`. Moldes: a base congelada e a leitura de `docs/` de `tests/Kit/SiteDeDocumentacaoTest.php` (`[CT-24]`, `[CT-40]`, `[CT-42]`); a rule e a linha do índice de `tests/Kit/OnboardingGuiadoInstalacaoTest.php`; o inventário Mermaid de `blocosMermaidDaArvore()` | o `Então` afirma o conteúdo de arquivos versionados — catálogo, README, páginas, referência de pacotes, `composer.json`, `package.json`, rule, índice, `site/package.json` e workflows — contra a base congelada e contra a árvore. A aplicação fica de pé pelo arnês do kit, e por isso a linha do CT-28 que lê o registro dos painéis (ADV-04) cabe aqui | sessão, 2026-11-20 — convenção do projeto: guarda de documentação em `tests/Kit`, no molde do `SiteDeDocumentacaoTest` e do `GuardasDosDiagramasTest` |
| G2 — modelo e diagramas do mapa × aplicação de pé, sem tenant (Kit) | R6, R8, R9, R10, R11 (CT-11, CT-13), R12, R15, R16, R17, R18, R19, R20, R22, R28 (CT-29, CT-41), R29, R34 (CT-37), R35, R36, R37 | Pest feature HTTP | **nova**: `tests/Kit/MapaInterativoTest.php`. Moldes: o `[CT-25]` de `tests/Kit/SiteDeDocumentacaoTest.php`, que confronta o README com `Filament::getPanel()`; o `conferirDg10()` de `tests/Kit/GuardasDosDiagramasTest.php`, para o controle em cópia | o `Então` afirma o modelo e os arquivos de diagrama contra o que os painéis registram, contra o roteador da aplicação, contra os arquivos de código citados e as classes de `app/`, contra o CHANGELOG e contra a página. Roda em todo PR, e o job `site` não (T) | sessão, 2026-11-20 — convenção do projeto: guarda que confronta documentação com o painel vivo em `tests/Kit` (molde do `[CT-25]` do `SiteDeDocumentacaoTest`) |
| G3 — registro dos painéis com tenant (Tenancy) | R6 (CT-44), R11 (CT-12), R28 (CT-30), R34 (CT-38) | Pest feature HTTP | **nova**: `tests/Tenancy/MapaInterativoTenancyTest.php`. Molde: `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` | o modo com tenant só existe com o `Tests\TenancyTestCase`, que fixa `kit.tenancy.enabled` antes do boot | sessão, 2026-11-20 — convenção do projeto: o modo com tenant só existe na suíte `Tenancy` (molde do `DiagramasDaArquiteturaTenancyTest`) |
| G4 — site construído (conferidor do mapa, Node + Playwright) | R6 (CT-B01), R12 (CT-B02), R13, R14, R21 (CT-B01), R23 (CT-B06), R24, R31, R32 | browser | **nova** *(alterado em 2026-11-20: era "existente — `site/verifica-acessibilidade.mjs`")*: `site/verifica-mapa.mjs`, sobre o `astro preview`. Os dois workflows o rodam logo depois do conferidor de acessibilidade, com a mesma prévia de pé; no `pages.yml`, antes do envio do artefato. Molde: `site/verifica-acessibilidade.mjs`, inclusive os controles sintéticos | o HTML do mapa só existe depois do build (o `site/dist` é ignorado pelo git), o visualizador desenha no navegador, e acessibilidade e idioma da interface só o navegador mede. O catálogo en do archify só existe depois do build, que baixa o archify, e por isso o CT-B07 também mora aqui. O `pest-plugin-browser` não serve site estático (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`). O arquivo é próprio porque o conferidor de acessibilidade já tem `[CT-B01]` e `[CT-B02]` de outra wiki. O `ids-ct.sh` acusa estes CT-B como sem teste, por desenho: ele só lê `it()` e `test()` | sessão, 2026-11-20 — convenção do projeto: o site construído é conferido por Node + Playwright no CI (molde do `site/verifica-acessibilidade.mjs`); arquivo próprio pela D17 do `01` |

## Fronteira com o Plano

| Item do plano | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes e formato de `site/mapa/pecas.json`, `ligacoes.json` e `diagramas/{slug}.json` ("a pasta é o manifesto") | escolha de implementação | detalhe: os cenários falam de "modelo de peças", "modelo de ligações" e "arquivos de diagrama". O oráculo vem da RQ-24 e de P-03 a P-11 |
| `site/mapa/archify.mjs` baixa o archify no commit fixo e o confere | o `00` não fixa versão nem integridade do download | sem CT. A integridade do download fica para a dimensão de segurança do quality gate |
| `site/mapa/pt-BR.json`: as chaves que faltam no catálogo pt do archify e as sobrescritas dos rótulos de tipo de peça | mecanismo do idioma | o oráculo é a P-11 (a interface pt, CT-B04; o catálogo pt sem o texto en, CT-B07) e a P-02 (o texto do kit, CT-16 e CT-B01) |
| `site/mapa/gerar.mjs` grava em `site/dist/mapa/{pt,en}/{slug}.html` depois do `astro build`; `"build": "astro build && node mapa/gerar.mjs"` | mecanismo. Quem dá o oráculo é a P-07: o mapa nasce no build do commit que publica | o CT-25 afirma que o build dos dois workflows gera o mapa e que nenhum HTML do mapa é versionado. O caminho do HTML é detalhe dos CT-B |
| `site/mapa/posicionar.mjs`, script de desenvolvimento que nunca roda no build | o `00` não fala de posição | sem CT (ver Cogitado e cortado) |
| página em `docs/{pt,en}/referencia/mapa-interativo.md`, na seção Referência, em Markdown sem JS | superfície. O `00` pede "um lugar escolhido" no site | detalhe: CT-15 a CT-21 e CT-34 leem a página nesse caminho. A seção não é oráculo |
| o catálogo é a página Roteiro de features (`docs/{pt,en}/operacao/roteiro-de-features.md`) | o glossário também o determina (Catálogo de features, decidido nesta feature) | **aceito como oráculo**, pelo glossário e pela RQ-22 |
| rota `{base}/mapa/{pt,en}/{slug}.html` | superfície | detalhe dos CT-B |
| na Superfície de UI: "rota entre nós, lente, tema, exportação" | são recursos do archify, e testá-los é testar o archify. A RQ-07 pede interação; a P-04 pede a prova à vista; a P-11 pede o tema na acessibilidade | "busca de nó" e o passaporte com as provas da peça viram oráculo no CT-B03; os cards de prova, no CT-B02; e "tema", no CT-B05 *(alterado em 2026-11-20: fatos de plataforma — o passaporte mostra as provas da peça e só as contagens das ligações)*. O resto vai para Cogitado e cortado |
| `site/verifica-acessibilidade.mjs` alterado | o mapa não entra nele *(alterado em 2026-11-20)*: os CT-B desta wiki moram em `site/verifica-mapa.mjs`, por decisão da sessão nesta rodada, porque aquele arquivo já carrega `[CT-B01]` e `[CT-B02]` de outra wiki | detalhe. O CT-25 confere a ordem dos dois conferidores no workflow |
| a P-11 cita como origem o `01` (D2, D8, D12) e o `02` (ADR-06) | o oráculo vem da P-11, vigente no `00`, e não desses documentos | **aceito como oráculo pela P-11** (R31, R32, R33) |
| os dois defeitos tolerados: `nested-interactive` no `<svg>` raiz do diagrama e `color-contrast` em `.node-outline-heading > small` | a P-11 diz "dois defeitos do próprio archify, que a conferência tolera nomeados pela regra e pelo elemento", sem nomeá-los. Os nomes vieram da sessão, medidos no archify do commit fixado | aceitos como fato da plataforma que preenche a P-11. Se o archify mudar de commit, muda a tabela de controles do CT-B06, não a regra |
| `site/sidebar.json`, `site/redirects.json` e os stubs `site/public/{pt,en}/referencia/mapa-interativo.html` | superfície que guardas herdadas já vigiam: `[CT-20]`, `[CT-23]`, `[CT-36]` e `[CT-38]` de `tests/Kit/SiteDeDocumentacaoTest.php` | sem CT novo: se faltar algo, os herdados ficam vermelhos |
| link da página para os HTML do mapa | **decidido (Q15)** *(alterado em 2026-11-20: era achado)*. A página usa `<a href>` relativo cru dentro do Markdown, e não link Markdown. O `[CT-22]` (`tests/Kit/SiteDeDocumentacaoTest.php:'[CT-22]':613`) e o `[CT-45]` (`tests/Kit/SiteDeDocumentacaoTest.php:'[CT-45]':1753`) herdados só leem link Markdown e não mudam. O `site/verifica-links.mjs` confere o alvo no `dist/` | o CT-15 lê os `<a href>` e confere cada um contra os arquivos de diagrama |
| edição de `docs/{pt,en}/referencia/index.md`, `arquitetura-em-diagramas.md`, `operacao/index.md`, `depois-de-criar-resources.md`, `desenvolvendo-o-kit.md` e `roteiro-de-features.md` | superfície. Três desses arquivos carregam blocos Mermaid da base: DG-01, DG-02, DG-03 e DG-13 na página de diagramas, DG-11 no catálogo e DG-19 em "Desenvolvendo o kit" | P-01: o CT-27 confere que todo bloco da base continua onde estava |
| `tests/Browser/RoteiroDoKitTest.php`, só o docblock | documentação de teste | sem CT. Os números que ele cita (F-03, F-13, F-45, F-51, F-52) ficam protegidos pelo CT-03 |
| `.ai/rules/catalogo-de-features.md` e a linha em `.ai/rules/index.md` | o `00` pede "uma rule"; o nome do arquivo é detalhe | o CT-07 acha a rule pela linha do índice |
| `CHANGELOG.md` | registro da entrega. Asserção sobre o topo do CHANGELOG expira (`.ai/rules/testes.md`) | sem CT sobre o registro da entrega; o gate confere. O CT-31 lê o CHANGELOG só como fonte das versões, das datas e do texto de cada seção |
| "nenhuma rota do app muda", "nenhum arquivo de `app/` muda" | é a RQ-15 aplicada pelo plano: uma restrição sobre o diff | sem CT (ver RQ-15) |
| archify 3.2.0, commit `4478129…` | versão (entrada 6 do despacho) | os identificadores dos dez tipos, tirados do `schemas/README.md` do archify nesse commit, entram no CT-20 como fato da plataforma. O número dez e o comparativo vêm do `00` (RQ-25) |

### Perguntas da derivação: estado depois da rodada de revisão

As de raia requisito estão no `00`, em `## Perguntas ao Solicitante`, com o estado de cada uma. As de raia desenho foram decididas pela sessão.

| ID | Raia | Afeta | Estado | No `04` |
|---|---|---|---|---|
| Q6 | requisito | RQ-09 | aberta | recorte bloqueado: quais fluxos, e se todos. O passo que nomeia peça (R22), a âncora de cada transição e a ordem no mesmo método (R35) não dependem dela (ADV-32) |
| Q7 | requisito | RQ-19, RQ-04, RQ-05 | fechada pelo `00` | R5 (todas as páginas) e R27 (pacotes instalados) |
| Q8 | desenho (reclassificada) | RQ-20 | decidida pela sessão | R28 (CT-29, CT-30) |
| Q9 | requisito | RQ-24, P-04, P-05, P-07 | fechada pelo `00` | R9 (prova fora do commit reprova) e R10 (registro pelo plugin que traz a peça) |
| Q10 | desenho | P-03, P-06 | decidida pela sessão | R29 (CT-31) |
| Q11 | requisito | RQ-04, RQ-05, RQ-11, P-03, P-09 | aberta | recorte bloqueado e estacionado (ADV-17, ADV-31): a R30 (CT-32, CT-33), a metade de condição do CT-B01 e o R24-M5 saíram do conjunto vigente, porque nenhuma afirmação sobre a marca vale nas três leituras. Na quinta rodada, a Q11 segura também o recorte do CT-37 (ADV2-29), e a parte que vale nas três leituras virou a R37 (ADV2-30) |
| Q12 | requisito | P-02, RQ-03, RQ-07 | fechada pela P-11 | R31, R32 e R33 |
| Q13 | requisito | P-08, RQ-23 | fechada pelo `00` | R4 (linha F-03d) |
| Q14 | desenho | P-08, RQ-19 | decidida (a recomendação) | R3 e R5 (`@premissa`) |
| Q15 | desenho | RQ-03, P-02 | decidida antes da derivação | R15 (`<a href>` relativo cru) |
| Q16 | requisito | RQ-19, RQ-04, P-03 | fechada pelo `00` | R27: o CT-28 parte dos manifestos, e a linha "instalado e não listado" reprova |
| Q17 | desenho | RQ-19, P-03 | decidida (a recomendação) | R27 (`@premissa` do CT-28) |
| Q18 | desenho | P-03, RQ-09 | decidida pela sessão (D25: não) | R35-M5 continua sem matador, com o motivo do D25; o CT-39 não muda |
| Q19 | desenho | RQ-24, P-03, P-07 | decidida pela sessão (D26) | R10: a quinta forma (CT-10; R10-M8 e M9) |

Nenhuma pergunta nova na terceira rodada. Ficam abertas só a Q6 e a Q11, que já estão no `00`. A Q18 e a Q19, de raia desenho, nasceram na quarta rodada e foram decididas pela sessão (D25 e D26). A P-12, que veio do ADV-33, é premissa vigente do `00`, e não pergunta.

Perguntas da quarta rodada, renumeradas e decididas pela sessão no `01`:

**Q18** · raia: desenho · afeta: P-03, RQ-09 · depende de: —
No diagrama `lifecycle`, a âncora de cada transição tem de nomear, no trecho, o estado de destino, como o código o chama? A decisão do ADV-11 pede só que a âncora resolva. Uma transição inventada, como Recusado → Pendente, passa se receber a âncora de um trecho que existe e não faz essa transição (R35-M5).
**decidida — D25:** não. Os estados do kit são calculados, e não gravados:
- o `Convite::situacao()` deriva o estado de `aceito_em`, `recusado_em` e `expira_em` (`app/Models/Convite.php:situacao:619`);
- o `User::rotuloDaSituacao()` deriva de `aprovacao_pendente` e `ativo` (`app/Models/User.php:rotuloDaSituacao:558`);
- a transição grava a coluna e nunca escreve o nome do estado (`app/Models/Convite.php:'aceito_em':690`), e "prazo vence" não tem linha que a faça.

Exigir o nome do destino reprovaria transição real. O R35-M5 continua sem matador, com esse motivo, e o CT-39 não muda.

**Q19** · raia: desenho · afeta: RQ-24, P-03, P-07 · depende de: —
A sessão mediu que parte das ligações tem a prova em `vendor/`, e que o modelo a troca pelo trecho do kit que liga ou configura a peça. Quando a origem é peça de pacote e esse trecho fica em `config/` ou num provider que não é de painel, qual forma da R10 o aceita? As formas decididas no ADV-07 e no ADV-08 aceitam o provider só nas ligações do painel, e qualquer outro arquivo reprova. Uma entrega correta pode ficar vermelha no CT-10.
**decidida — D26:** a quinta forma da R10, mais larga que a recomendação. Na ligação cuja origem é peça de pacote, a prova é o trecho do kit que liga a origem ao destino, num arquivo versionado fora de `vendor/`, em qualquer pasta:
- o trecho nomeia a peça de destino (classe, alias ou chave);
- o arquivo nomeia a peça de origem (classe, plugin ou a chave de config do pacote);
- `vendor/` e saída de comando (`artisan route:list`) nunca valem.

A sessão mediu as 288 ligações da varredura cuja origem não é classe do kit nem painel. Só `config/` e provider deixariam vermelhas as ligações de `routes/` e de `app/Support` (a distribuição está na R10).

## Perguntas para o 00-requisito.md

*(alterado em 2026-11-20: virou apontador)* As perguntas de raia requisito estão no `00`, em `## Perguntas ao Solicitante` (Q6, Q7, Q9, Q11, Q12, Q13 e Q16), com o estado de cada uma. As de raia desenho (Q8, Q10, Q14, Q15, Q17, Q18 e Q19) foram decididas pela sessão; a Q18 e a Q19, no `01` (D25 e D26).

## Revisão adversarial

*(seção nova em 2026-11-20, quarta rodada)* A sessão despachou a revisão por decisão própria (o perfil não a exigia). O revisor recebeu só o `00`, o `04`, o `05` e o glossário, e devolveu 34 achados: 18 implementações erradas que passavam, 12 oráculos fracos, 3 de cobertura de RQ e 1 da sonda 6. A sessão decidiu cada um. O fechamento criou cenário novo, então cabe uma re-revisão, uma vez só.

Resultado:
- 32 aceitos. Um deles, o ADV-31, foi aceito com um sub-ponto rejeitado, e o que ele aponta ficou estacionado na Q11.
- 1 estacionado na Q11 (ADV-17).
- 1 rejeitado como cenário (ADV-34).

Os mutantes que vieram da revisão levam a marca `revisão adversarial` e não contam para o teto do perfil.

| Achado | O que o revisor achou | Decisão | Destino ou motivo |
|---|---|---|---|
| ADV-01 | os seis itens do README sem F-nn na base podiam casar com a F-nn de outra feature | aceito | R5: o termo de cada item fica fixado no `04`; CT-05 (a linha casada traz o termo) e CT-35 (controle); R5-M6 |
| ADV-02 | F-nn marcada como removida e ainda entregue por peça do modelo | aceito | R6: CT-36, linha da F-05 removida e entregue; R6-M5 |
| ADV-03 | a associação peça → F-nn era livre: o pacote da peça e o lugar da feature não eram conferidos | aceito | R6: CT-06 (o pacote que a linha nomeia; a rota do "onde fica" no código próprio) e CT-36; R6-M6 e M7; R6-M8 sem matador (código próprio cujo "onde fica" não é rota) |
| ADV-04 | pacote cujo plugin um painel registra, jogado na lista dos sem feature própria | aceito | R27: CT-28, linha do Health na lista; R27-M5 |
| ADV-05 | o `/app` só com diagrama de arquitetura compartilhado, sem as peças do modo com tenant | aceito | R34, nova: CT-37 (sem tenant, G2) e CT-38 (com tenant, G3) |
| ADV-06 | o catálogo pt do kit recebe o texto en, e o filtro do CT-B04 isentava essas chaves | aceito | R31: CT-B07, novo, no `05`; R31-M7 e M8 |
| ADV-07 | prova tirada de outro arquivo cujo trecho nomeia a peça usada | aceito, com as formas válidas medidas pela sessão | R10: arquivo da origem, trait ou concern que a origem usa, provider do painel, ou método de contrato do alvo; CT-10, linha do `UserExporter`; R10-M6. A quinta forma, para a ligação cuja origem é peça de pacote, veio da Q19 (decidida — D26), com R10-M8 e M9 |
| ADV-08 | a peça que o framework acrescenta pela configuração do painel não tinha forma de prova | aceito | R10: quarta forma, o trecho que configura o painel; CT-10, linhas de `->id()` e `->tenant()`; R10-M7 |
| ADV-09 | o número novo é uma feature no pt e outra no en | aceito | R1: CT-01, linha da F-72; R1-M5 |
| ADV-10 | descrição e passo en copiados do pt; marca de nome de código de fachada; motivo da célula en não conferido | aceito; a marca só vale para título que é o nome de uma peça do modelo ou de um comando `kit:*` | R16: CT-16, duas linhas; R16-M4 e M5. R19: CT-21, linha do motivo en; R19-M6 |
| ADV-11 | o `lifecycle` não nomeia peça, e a transição inventada passava | aceito | R22 separa os tipos de fluxo; R35, nova (CT-39): estado e transição com âncora que resolve; R35-M1 e M3; R35-M5 sem matador (Q18, decidida — D25) |
| ADV-12 | marco com versão e data certas e a feature de outra versão | aceito | R29: o marco traz um termo da seção dessa versão; CT-31, linha "Passkeys" na 0.56.0; R29-M6 |
| ADV-13 | o passo do conferidor do mapa entra no workflow sem poder reprovar | aceito | R23: CT-42, novo; R23-M5 |
| ADV-14 | biblioteca nova por CDN, por arquivo copiado ou como devDependency fora da lista de recusa | aceito | R21: as devDependencies viram lista fechada (CT-23), e o HTML não carrega recurso de fora (CT-B01); R21-M4 e M5 |
| ADV-15 | agrupamento que nomeia os membros só no texto da peça agrupadora | aceito | R12: CT-14, linha do texto da peça agrupadora; R12-M5 |
| ADV-16 | tipo de peça errado dentro do conjunto fechado | aceito | R8: o tipo confere com a classe; CT-08, linha do `User` como recurso; R8-M6 |
| ADV-17 | a detecção do CT-32 rodava numa configuração só, e a âncora que não resolve não tinha linha | estacionado — Q11 | só vale se a Q11 confirmar a marca; volta com a R30 |
| ADV-18 | a linha da rule no índice casava só a página do catálogo | aceito | R7: CT-07 (casa também `app/**`, `composer.json`, `package.json` e `routes/**`); R7-M4 |
| ADV-19 | o R13-M3, número de linha guardado no modelo, não divergia no PR que o cria | aceito | R9: CT-09, linha da prova com número de linha, na suíte do kit, que roda em todo PR; o R13-M3 passa a morrer no CT-09 |
| ADV-20 | lista à parte de features no mapa passava no CT-02 | aceito | R2: CT-02, linhas da tabela de F-nn na página do mapa e da lista num arquivo do mapa; R2-M1 com matador novo; R2-M4 |
| ADV-21 | CT-05 e CT-06 só tinham o mundo publicado | aceito | CT-35 (R5) e CT-36 (R6), com as cópias sugeridas |
| ADV-22 | motivo de enfeite na remoção, na página e no pacote | aceito | CT-03, CT-35 e CT-28, linhas "—" e "não se aplica" sem porquê; R3-M6, R5-M7 e R27-M6 |
| ADV-23 | o piso do CT-09 não separava todas as ligações das de registro | aceito | R9: o CT-09 confere tantas provas quantas ligações o modelo tem; R9-M6 |
| ADV-24 | aresta inventada ou invertida no desenho | aceito, com as três formas de aresta medidas pela sessão | R36, nova (CT-40); R36-M1 a M4 |
| ADV-25 | link de prova para outro repositório; card que não aparece ao leitor | aceito | R13: o card no painel lateral, à vista sem foco (CT-B02); R14: o link do passaporte vai ao `support.source` no commit do build (CT-B03); R13-M6 e R14-M3 |
| ADV-26 | célula que leva a diagrama do outro modo; a metade "outro painel" do R19-M4 sem linha | aceito | R17: CT-17 e CT-43, novo; R17-M6. R19: CT-21, linha do `sequence` do app |
| ADV-27 | diagrama declarado de um painel, copiado de outro | aceito, com as três formas de aresta | R36: CT-40, linha do `UserResource` no `/infra`; R36-M5 |
| ADV-28 | o CT-B01 reduzia o oráculo à contagem de peças | aceito | R24: CT-B01 confere o conjunto de rótulos; R24-M6 |
| ADV-29 | a exceção não capturada ficava fora da escuta | aceito | R24 e R32: CT-B01 e CT-B05 escutam também a exceção; R24-M7 |
| ADV-30 | a busca e o detalhe montam os textos só na interação | aceito | R31: CT-B04 lê a interface também com a busca e o passaporte abertos; R31-M6 |
| ADV-31 | o `04` afirmava a marca da peça condicional, que a Q11 deixa aberta | aceito por inteiro, e a marca fica estacionada — Q11; o sub-ponto do Estado das RQ no `00`, rejeitado | a R30 (CT-32, CT-33), a metade de condição do CT-B01 e o R24-M5 saem do conjunto vigente, com os IDs reservados (Cláusulas abertas). O sub-ponto foi rejeitado porque, pela regra da skill, cláusula que depende só em parte da resposta continua "fechada", e o recorte fica listado no `04` |
| ADV-32 | a ordem dos passos estava bloqueada na Q6 sem precisar | aceito | R35 (CT-39): toda transição de fluxo tem âncora que resolve; a ordem é conferida quando duas transições seguidas têm âncora no mesmo arquivo e método; R35-M2; R22-M3 ganha matador; R35-M4 fica sem matador, com o motivo |
| ADV-33 | o piso da varredura parava no registro dos painéis | aceito (virou a P-12) | R28: a lista com motivo vale também para peça registrada; CT-41, novo (classes de `app/` fora de `app/Filament`); R33: a página declara o limite (CT-34); R28-M4 a M6 e R33-M4 |
| ADV-34 | o executor acumula o papel de aprovador (merge, tag) | rejeitado como cenário | é controle de processo da RQ-26: a sessão e o Codex nunca fazem merge, push na `main` nem tag, e a sessão confere HEAD, tags e o estado do PR antes e depois de cada despacho do Codex. Confere-se no quality gate e no PR |

## Revisão adversarial — segunda rodada

*(seção nova em 2026-11-20, quinta rodada)* É a segunda e última rodada: a skill tem teto de duas, e não há terceira. O revisor recebeu só o `00`, o `04`, o `05` e o glossário, e devolveu 32 achados. A sessão aceitou todos, alguns com o oráculo ajustado pelo que mediu. Esta derivação conferiu por comando cada oráculo novo ou reescrito, e cada fato de linha de controle, contra dados reais: o código, o vendor, o catálogo, o README, o CHANGELOG e o esqueleto do mapa construído (os 13 arquivos de diagrama de `site/mapa/diagramas/` e o HTML de `site/dist/mapa/`, na revisão `ae5d20f`). Durante a rodada, a sessão publicou os construtores do mapa (commit `4adaf9a`), e os oráculos do site construído foram conferidos de novo nesse commit: os 17 arquivos de diagrama dele, gerados pelo `site/mapa/gerar.mjs --saida` numa pasta de rascunho, sem tocar no `dist/`. O gerador aceita 10 dos 17; os 7 escritos à mão no esqueleto, sem prova por ligação, ele recusa, e os tipos `lifecycle`, `erd`, `class`, `sequence`, `workflow` e `dataflow` ficam sem medição nesse commit (o `erd`, o `sequence`, o `workflow` e o `dataflow` foram medidos no trabalho em andamento da sessão, fora de commit, só para os seletores do `05`). A sessão publicou também o catálogo da entrega (commit `0f38e2f`, com a F-72 a F-121 e a coluna Origem), e os oráculos do catálogo que dependem das linhas novas — a identidade por tokens (ADV2-01) e a resolução dos caminhos (ADV2-04) — foram conferidos nele. Por último, a sessão publicou a página do mapa (commit `e7d6eb5`) e os fluxos escritos a partir do código (commit `4cc9517`, que tira os diagramas de arquitetura escritos à mão do esqueleto). **A base da conferência fica congelada no `4cc9517`**: os 43 arquivos de diagrama dele, de todos os tipos e todos aceitos pelo gerador, foram gerados de novo na pasta de rascunho e conferidos no Chromium, e a página, o README e o modelo foram lidos nele; commit posterior não foi conferido. Medido no `4cc9517`: o `<h1>` e o rótulo de cada peça iguais aos da fonte nos 84 HTML fora o comparativo; a busca com um resultado por peça em 42 de 42 diagramas, e nenhum sem JavaScript; nenhum erro, nenhuma requisição para fora e nenhum `<script src>` nos 86; os pares de arestas dos 4 mapas de arquitetura iguais aos da fonte; 1.172 itens de prova, todos achados, inteiros e sem corte; 685 links de prova de peça, todos com o trecho na linha; 1.416 textos en de duas palavras ou mais sem palavra funcional do pt; nenhuma marca de disponibilidade; nenhum item que é nome de F-nn nas 13 listas e tabelas da página nem nos 328 cards; e o registro do `/admin` (57 peças) e o do `/infra` (84) iguais nos dois modos, e o do `/app` com 46 sem tenant e 48 com. Onde a conferência reprovaria dado correto, o oráculo foi ajustado, e a última coluna diz o que se mediu.

Placar: 32 aceitos. Os achados estruturais — ADV2-14, ADV2-20, ADV2-29 e ADV2-30 — a sessão escalou ao mantenedor. Os mutantes que vieram desta rodada levam a marca `revisão adversarial (ADV2-nn)` e, pela regra da skill, não contam para o teto da regra.

| Achado | O que o revisor achou | Decisão | Destino | Conferido contra dados reais |
|---|---|---|---|---|
| ADV2-01 | duas F-nn novas, sem página, pacote nem rota, trocam de feature entre pt e en | aceito, com o oráculo ajustado | R1: nas linhas novas, os tokens entre crases do pt são os do en; CT-01; R1-M6, e a troca entre linhas sem token, lacuna declarada (R1-M8) | 4 das 74 linhas da base diferem nos tokens (F-02 e F-04 por `{painel}`/`{panel}`, F-07 com o en errado, F-51 com a palavra traduzida) e 10 não têm token: a regra vale só para as linhas novas. No commit `0f38e2f`, as 50 linhas novas (F-72 a F-121) têm os mesmos tokens nos dois idiomas, e a F-118 ("Voltar ao topo", código próprio, sem página nem rota) não tem token nenhum: "e não vazios" reprovaria dado correto, e saiu |
| ADV2-02 | F-nn removida num idioma só | aceito | R1: o estado entra na identidade; CT-01; R1-M7 | a base não tem F-nn removida |
| ADV2-03 | o README publicado ganha item fora do catálogo | aceito | R5: o CT-05 lê também a lista publicada; CT-35; R5-M9 | 46 itens e 46 títulos diferentes por idioma: 26 em negrito, 17 pelo separador e 3 pelo item inteiro. A lista não muda no commit `4cc9517`: a seção "Mapa interativo" que o `e7d6eb5` pôs no README fica fora dela |
| ADV2-04 | caminho velho no "onde fica" passava com "basta um caminho" | aceito | R6: todo caminho resolve; CT-06, CT-36 (F-07 en) e CT-44, novo, com tenant; R6-M9 | na base, pt: 51 caminhos, 3 só com tenant, e todos resolvem; en: só a F-07 (`/{panel}/my-profile`) não resolve. No catálogo da entrega (commit `0f38e2f`): 92 caminhos por idioma, 6 só com tenant, e todos resolvem nos dois modos; a F-07 en foi corrigida |
| ADV2-05 | a F-nn não chega ao leitor | aceito (D29), com o oráculo ajustado | R6: o CT-B01 confere a F-nn no desenho da peça — o `tag` no nível de leitura cheio, no mapa de arquitetura, e o sublabel, na árvore de registro; R6-M10 | no commit `4adaf9a`: o `tag` das 4 peças com F-nn do `app-acesso` fica com opacidade 0 no carregamento (nível "read") e com 1 no nível "full", pelo botão "+", e com a peça em foco pela busca; o passaporte mostra o `tag`, e a busca por "F-02" acha as duas telas que a entregam. O esquema `tree` do archify não tem `tag`, e a árvore leva a F-nn no sublabel, à vista no carregamento (5 peças em cada árvore do `/app`). "À vista" no carregamento reprovaria o mapa de arquitetura, e o ponto vai ao coordenador |
| ADV2-06 | "fora de comentário" só definido para PHP | aceito | R9: comentário por tipo de arquivo; CT-09, linha Blade; R9-M7 | a linha 1 do `convites-recebidos.blade.php` é um `{{-- --}}` com `ConvitesRecebidos::table()`, que o arquivo não traz fora dele |
| ADV2-07 | o número de linha guardado fora do caminho | aceito | R9: o modelo não tem campo de linha, e as provas dos arquivos de diagrama são âncora; CT-09, duas linhas; R9-M8 | 405 provas nos 13 arquivos do esqueleto: 3 com `arquivo` e `trecho` e 402 também com `label`; nenhuma com linha. No commit `4adaf9a`: 977 âncoras no modelo e nos 17 arquivos de diagrama, 652 com `arquivo` e `trecho` e 325 também com `label`, nenhuma com linha |
| ADV2-08 | trait que a origem não usa, e método qualquer do alvo | aceito | R10: o trait que a origem usa e a lista fechada de métodos de contrato; CT-10, duas linhas; R10-M10 e M11 | 6 interfaces e 7 métodos de contrato no vendor; o `Projeto` usa o `BelongsToTenant`, e o `UserResource`, não |
| ADV2-09 | par de mapeamento com as pontas trocadas | aceito (D27) | R10: o trecho é a linha inteira do par; CT-10, linha do `MailLog`; R10-M12 | a linha 67 do `PoliciesDeVendor` tem 22 espaços de alinhamento, e a 68 mapeia o `MailLog`. No modelo do commit `4adaf9a`, as 10 ligações que saem do `PoliciesDeVendor` levam a linha inteira do par como trecho, com os espaços |
| ADV2-10 | as páginas que o painel liga pela configuração ficam fora do registro | aceito (D30) | R11: o registro vivo lê os getters da configuração; CT-11 e CT-13; R11-M6 | `TelaLogin`, `TelaRecuperarSenha` e `TelaRedefinirSenha` (três painéis), `RegistroPorConvite` e `TelaVerificacaoDeEmail` (`/app`) fora do `getPages()`; com tenant, os getters da organização devolvem nulo. No modelo do commit `4adaf9a`, o painel app e o admin registram a `TelaLogin` nos dois modos, com o trecho `->usingPage(TelaLogin::class)` |
| ADV2-11 | rótulo de nó que não é peça sai igual ao pt | aceito, com o oráculo ajustado | R16: termo do glossário com o en dele, frase pelo critério do ADV2-28, código isento; CT-16; R16-M6 | 373 pares iguais nas traduções do esqueleto, todos código ou texto em inglês; dos 4 rótulos que são termo do glossário, 2 não batem (Inativa → Off; Telas de acesso → Access screens) |
| ADV2-12 | diagrama de um modo marcado como compartilhado | aceito | R17: nas duas direções; CT-17 e CT-43; R17-M7 | no esqueleto, os diagramas de um modo só têm um modo em `modos` |
| ADV2-13 | ligação de um modo desenhada no diagrama do outro | aceito | R18: CT-19; R18-M4, e o modo da ligação de uso, lacuna declarada (R18-M5) | no esqueleto, o `app-resumo-sem` não desenha peça nem ligação do modo com tenant, e o `app-resumo-com` desenha 5. No modelo do commit `4adaf9a`, "o painel app usa o model `Tenant`" está nos dois modos, embora o trecho dela, o `->tenant()`, só rode com tenant, e o "registra `IdentifyTenant`", com o mesmo trecho, só no modo com tenant: é defeito do modelo, que a linha de controle do CT-19 acusa; nenhuma ligação do modelo vale só com tenant entre peças dos dois modos no `/app` |
| ADV2-14 | fluxo de painel declarado do kit inteiro | aceito (D28); estrutural, escalado | R19: CT-21; R19-M7 | os 6 diagramas do kit inteiro do esqueleto não desenham peça registrada; o `conta` cita o `UserResource` só como âncora. No commit `4cc9517`: 22 diagramas do kit inteiro, nenhum nó com o nome exato de uma das 112 peças que painel registra (o marco 0.33.0 cita `Dashboard` numa frase, e conferir por palavra reprovaria dado correto); a pilha da requisição saiu em dois diagramas de painel, o comum, dos três painéis e dos dois modos, e o do `/app` com tenant |
| ADV2-15 | biblioteca embutida, ou em `peerDependencies` | aceito (D32), com a lista ajustada | R21: o CT-23 vira Esquema, com os quatro campos e a lista fechada de `site/mapa/`; R21-M6 e M7 | sem `optionalDependencies` nem `peerDependencies`. No commit `4adaf9a`, `site/mapa/` tem `archify.mjs`, `diagramas/`, `gerar.mjs`, `interface.json`, `ligacoes.json`, `pecas.json` e `posicionar.mjs`. A lista da D32 não nomeia os dois arquivos do modelo, e com ela a linha "publicados" reprovaria dado correto; a lista fechada os inclui, e a divergência vai ao coordenador |
| ADV2-16 | o conferidor reprova e termina com código 0 | aceito | R23: controle de processo no CT-B06; R23-M6 | o `site/verifica-mapa.mjs` do commit `4adaf9a`, rodado numa pasta de rascunho sobre uma cópia em que o HTML do `app-resumo-sem` não desenha nada, saiu com código 1 ("nenhum `<svg role="img">` em 15000 ms", nas quatro entradas da amostra); sobre a cópia com o HTML gerado, saiu com 0 |
| ADV2-17 | o gerador perde as arestas | aceito, com o oráculo ajustado | R12: o CT-B02 confere os pares do DOM contra a fonte — o arquivo, no diagrama escrito à mão; o modelo, no construído; R12-M7 | no esqueleto, 76 e 76, 14 e 14, 43 e 43 pares nos três `architecture`. No commit `4adaf9a`, o arquivo construído não tem `connections`, e "o do arquivo" reprovaria dado correto; contra o modelo, 17 e 17 (`app-acesso`), 14 e 14 (`app-organizacoes`) e 16 e 16 (`app-resumo-sem` e `app-resumo-com`) |
| ADV2-18 | comando `kit:*` na lista dos sem feature | aceito | R28: CT-41; R28-M7 | 9 comandos `kit:*`. No modelo do commit `4adaf9a`, nenhum dos 9 tem F-nn, e o catálogo da entrega (`0f38e2f`) dá a F-102 ao `kit:install` e a F-105 ao `kit:update`: o CT-41 e o CT-06 reprovam esse modelo até a sessão ligar as F-nn, e o oráculo fica |
| ADV2-19 | violação com alvos mistos | aceito | R32: CT-B06; R32-M7 | o conferidor já filtra os alvos (`v.nodes.filter`) |
| ADV2-20 | comparativo sem cards | aceito (P-04 emendada); estrutural, escalado | R20: CT-22; R13 tira o comparativo dos cards; R33: CT-34; R24: o CT-B01 abre o comparativo; R20-M3 e R33-M5 | comparativo nos dois idiomas: `lang="en"`, nenhum card, nenhuma requisição para fora; a `base` e a `cabeca` estão publicadas. No commit `4adaf9a`, o mesmo; a casca não tem `<h1>` com o título do arquivo: mostra o subtítulo "base → cabeça", com os títulos dos dois diagramas no idioma da página |
| ADV2-21 | lista só de nomes de feature | aceito, com o oráculo ajustado | R2: limiar de 3 nomes de F-nn em item de lista, linha de tabela ou item de card; CT-02; R2-M5 | os nós do `infra-registro` trazem 3 nomes de F-nn (são peças), então nó não conta; cards do esqueleto: nenhum; a página prevista no passo 6 do `01` só tem link de diagrama e motivo. No commit `4cc9517`, com a página publicada e os cards dos 43 diagramas: nenhum item que é nome de F-nn nas 13 listas e tabelas da página nem nos 328 cards, nos dois idiomas |
| ADV2-22 | a prova do card não amarrada à ligação | aceito, com o oráculo ajustado | R13: CT-B02; R13-M4 reescrito e R13-M7 | o gerador escreve um item por ligação, "origem → destino (verbo): arquivo:linha — trecho". No commit `4adaf9a`, o painel lateral do mapa construído tem também os cards "Como ler as setas" e "Quem está em …", e o item de prova se reconhece pela forma; nos 6 diagramas construídos, 281 itens de prova por idioma, igual às ligações desenhadas (75 no `app-resumo-sem`, sobre 16 pares de arestas), todos inteiros, e nenhum diagrama repete nome de peça |
| ADV2-23 | amostra de uma peça só, e a linha nunca conferida | aceito, com o oráculo ajustado | R14: CT-B03; R14-M6 | no esqueleto, 226 links de prova de peça, todos com o trecho na linha. No commit `4adaf9a`, todos também: 21, 9, 49, 47, 1 e 1 nos construídos, e 73, 31 e 26 no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`; o `app-acesso` tem 5 peças de pacote em 21, e os outros três `architecture`, nenhuma |
| ADV2-24 | a exceção da interação não é pega | aceito | R14 e R31: CT-B03 e CT-B04; o R24-M7 passa a eles | nenhum erro no esqueleto com a busca aberta e uma peça em foco; no commit `4adaf9a`, nenhum no `app-acesso` e no `app-organizacoes`, nos dois idiomas, com a busca e o passaporte abertos |
| ADV2-25 | o termo do marco à escolha dele | aceito | R29: o título visível; CT-31; R29-M7 | "login" está na seção da 0.56.0, e "Passkeys", não |
| ADV2-26 | a lista de textos iguais sem teto | aceito, com o oráculo ajustado | R31: CT-B07 fechado às 14 chaves medidas e às só de marcadores; R31-M9 | 14 das 461 chaves do pt do archify iguais ao en. No commit `4adaf9a`, cada HTML embute só as 390 chaves de interface (as mesmas no pt e no en), e "o pt tem as 486" sobre o HTML reprovaria dado correto; as outras 96 o archify aplica no render. Iguais ao en: 9 a 11 por HTML entre as 390 e 14 na fonte do kit, todas entre as 14 |
| ADV2-27 | o en do CT-B04 sem critério | aceito | R31: CT-B04; R31-M5 reescrito | no esqueleto, a legenda en do `admin-acesso` traz os nomes en dos tipos. No commit `4adaf9a`, a do `app-acesso` traz Screen (panel, page, resource, widget, Livewire), Kit code (provider, service, command), Data (model, settings), Third-party package or plugin e Access (middleware, policy); nenhum texto de interface en igual ao pt de uma chave cujo en é outro |
| ADV2-28 | "traduzido" conferido como "diferente do pt" | aceito | R16 e R19: palavra funcional do pt; CT-16 e CT-21; R16-M7 e R19-M8 | nenhum dos 482 valores en de duas palavras ou mais traz uma; o detector acha 199 dos 827 textos pt. No commit `4adaf9a`: nenhum dos 403 textos en dos 17 arquivos de diagrama, nem dos 14 textos en do gerador, nem dos 9 das sobrescritas de tipo. "Do" também é palavra inglesa: nenhum texto en do commit `4adaf9a` a traz, e, se um vier a trazer, a lista é decisão da sessão |
| ADV2-29 | o CT-37 afirmava o recorte da Q11 | aceito; estrutural, escalado | R34: a linha admin tira a peça condicional; a Q11 segura o recorte | — |
| ADV2-30 | a marca publicada não era conferida | aceito; estrutural, escalado | R37, nova: CT-45; R37-M1 a M3 | o esqueleto não publica marca (a regra é vácua hoje); o `canAccess()` das duas peças condicionais lido no código. No commit `4adaf9a`, nenhum dos 17 arquivos de diagrama publica marca, e o modelo registra o `TenantResource` e o `AgenteIaResource` nos dois modos |
| ADV2-31 | publicação a partir de outro ramo | aceito (D31) | R23: CT-25 e CT-42; R23-M7 | `if: github.ref == 'refs/heads/main'` no job `build` do `pages.yml` |
| ADV2-32 | texto cortado à vista | aceito, com o oráculo ajustado | R13: o item de card inteiro (CT-B02); R24: o rótulo igual ao da fonte (CT-B01); R13-M8 | o item de card do archify quebra a linha (`overflow-wrap: anywhere`); um item de 160 caracteres sem espaço ficou com 346 px nas duas medidas; o esqueleto escreve "…" à mão em 33 rótulos, e por isso o rótulo é conferido por igualdade, e não por "sem reticências". No commit `4adaf9a`, nenhum dos 562 itens de prova dos construídos é cortado |

## Setup Global

### Sentinela, grupo e suíte
- Todo caso lê `site/`, `docs/`, `README*.md`, `CHANGELOG.md` ou `.ai/rules/`, e nada disso viaja para o projeto instalado (`.gitattributes`: `/site`, `/docs` e `CHANGELOG.md` com `export-ignore`). Os dois arquivos levam a sentinela `naArvoreDoKit()` no `beforeEach`, com `markTestSkipped`, no molde de `tests/Kit/GuardasDosDiagramasTest.php`. Fontes: `.ai/rules/testes.md` (path interpolado) e `[CT-10]`/`[CT-11]` de `tests/Kit/RedeDeDocumentacaoTest.php`.
- Grupo `kit`, pelo `tests/Pest.php`. Nenhum caso Pest chama a rede.

### Modo de tenancy
- `tests/Kit/MapaInterativoTest.php` usa `Tests\TestCase`, o modo sem tenant. `tests/Tenancy/MapaInterativoTenancyTest.php` usa `Tests\TenancyTestCase`, o modo com tenant.
- O `Dado` de cada caso afirma o **valor lido** — `config('kit.tenancy.enabled')` e, por painel, `hasTenancy()` — e não presume a suíte.

### Registro vivo: getters conferidos no vendor (Filament 5.10.2)
- Painéis: `Filament::getPanels()` (`vendor/filament/filament/src/FilamentManager.php:getPanels:384`). É a lista da grade e do registro, nunca escrita à mão.
- Recursos, páginas e widgets: `getResources()`, `getPages()` e `getWidgets()` (`vendor/filament/filament/src/Panel/Concerns/HasComponents.php:getResources:466`, `vendor/filament/filament/src/Panel/Concerns/HasComponents.php:getPages:442`, `vendor/filament/filament/src/Panel/Concerns/HasComponents.php:getWidgets:490`).
- *(alterado em 2026-11-20: ADV2-10, D30)* Páginas que o painel liga pela configuração, e não pelo `->pages()`: `getLoginRouteAction()` (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:getLoginRouteAction:561`), `getRegistrationRouteAction()`, `getRequestPasswordResetRouteAction()`, `getResetPasswordRouteAction()`, `getEmailVerificationPromptRouteAction()` e `getProfilePage()`, todos em `HasAuth`; e, com tenant, `getTenantRegistrationPage()` e `getTenantProfilePage()` (`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:getTenantProfilePage:262`). Medido no kit, nos dois modos: `TelaLogin`, `TelaRecuperarSenha` e `TelaRedefinirSenha` nos três painéis, e `RegistroPorConvite` e `TelaVerificacaoDeEmail` no `/app`, fora do `getPages()`; `getProfilePage()` e os dois getters da organização devolvem nulo.
- Plugins: `getPlugins()` (`vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:getPlugins:38`).
- Middlewares:
  - `getMiddleware()` devolve também o alias `panel:{id}` (`vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getMiddleware:97`, `vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getId:100`).
  - `getAuthMiddleware()` (`vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getAuthMiddleware:108`).
  - `getTenantMiddleware()` devolve `IdentifyTenant` **sempre** (`vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:IdentifyTenant:119`). Por isso o middleware de organização só conta com `hasTenancy()` do painel (`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:hasTenancy:207`), nunca com a chave da instalação.
- **A peça que um plugin traz.** O plugin registra a peça no `register()` dele. O `FilamentSpatieLaravelHealthPlugin` faz `$panel->pages([$this->getPage()])` com a página `HealthCheckResults` (`vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php:pages:36`). No código versionado do kit, essa peça só aparece como o registro do plugin no provider do painel (`app/Providers/Filament/InfraPanelProvider.php:FilamentSpatieLaravelHealthPlugin:345`). A guarda confere que o plugin traz a peça pelo próprio plugin: o `register()` dele, num painel de teste, devolve a peça. Não confere pelo nome.
- **O que muda de registro entre os modos, medido nesta derivação.**
  - **No `/app`**, a organização é decidida no boot (`app/Providers/Filament/AppPanelProvider.php:'kit.tenancy.enabled':658`).
  - **No `/admin` e no `/infra`**, o registro é o mesmo nos dois modos, e o que muda é a disponibilidade. A tela de organizações é descoberta sempre e aberta só com tenant (`app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`). A tela opcional segue a mesma convenção (`app/Providers/Filament/AdminPanelProvider.php:Par sempre registrado:151`, `app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php:canAccess:70`). Fora do `/app`, `grep -n "if (config(" app/Providers/Filament/*.php` só acha `lockscreen.enabled`, que acrescenta item de menu, e não peça.
  - Por isso os CT-11 e CT-12 afirmam a completude do registro sem amarrá-la ao modo, e só afirmam o modo na direção "o modelo não dá a um modo o que o painel não registra nele". A disponibilidade (a marca da peça condicional e o diagrama de modo em que ela entra) está estacionada na Q11 *(alterado em 2026-11-20: ADV-31; era "é da R30")*.
  - O `/app` é o único painel cujo registro muda entre os modos. Por isso é o único que precisa de um diagrama de arquitetura por modo (R34).

### Base congelada (commit `d9608c5a`, medida nesta derivação)
- **Catálogo:** 74 F-nn por idioma (F-01 a F-71, mais F-03a, F-03b e F-03c), e 71 é o maior inteiro. A guarda congela o par F-nn → termo-chave da feature, por exemplo F-05 → "Passkeys", F-10 → "403", F-11 → "master_global" e F-15 → "Convite individual". Nenhuma F-nn está marcada como removida na base.
- **README:** 46 itens por idioma na lista "O que já vem pronto" / "What's already there", até o `### A densidade…`. A guarda congela cada item com a F-nn que o cobre (`@premissa`, Q14c). A base do catálogo está defasada em relação a essa lista: o dock de atalhos, o tutorial guiado, as visões de tabela, a página única de login, a proteção anti-robô e o "+" no e-mail não têm F-nn nela. O termo que a linha casada de cada um traz está na R5 (ADV-01).
- **"Onde fica" do catálogo:** das 74 linhas por idioma, 40 trazem na célula um caminho entre crases que começa por `/`, sem "→"; 4 têm caminho e "→"; e 30 não têm caminho (menu, ação, "os três", "—"). A conta é a mesma em pt e em en. O oráculo da rota (R6) vale nas 40.
- **Classes de `app/` fora de `app/Filament` (P-12):** 135 arquivos PHP, que declaram 123 classes, 7 enums e 5 traits. A P-12 mediu que 103 já eram peça na varredura do step 3.
- **Repositório do kit:** o `support.source` do `composer.json`, `https://github.com/gsferro/filament-starter-kit-easy`. É o repositório que os links de evidência têm de levar (R14).
- **Pacotes instalados (Q16):** instalado é o que os manifestos declaram, o `composer.json` (`require` e `require-dev`) e o `package.json` da aplicação (`dependencies`, `devDependencies` e `optionalDependencies`). O `php` é plataforma e fica fora da conta.
  - Na base são 91: 61 no `require` (fora o `php`), 23 no `require-dev`, nenhum nas `dependencies`, 6 nas `devDependencies` e 1 nas `optionalDependencies`, o `@laravel/multiplex`.
- **Referência de pacotes instalados:** 87 pacotes por idioma, os mesmos em pt e em en, nas seções fora de "Avaliados e não adotados". A seção de front-end vem **depois** da dos avaliados.
  - O nome do pacote vem do endereço do link: o texto `pest-plugin-laravel` leva a `pestphp/pest-plugin-laravel`.
  - Todos os 87 estão instalados.
  - **Fato da base que a entrega corrige (Q16):** quatro pacotes instalados ficam fora da referência nos dois idiomas: `hoceineel/filament-quick-action-dock` (o do dock de atalhos), `brianium/paratest`, `icanhazstring/composer-unused` e `maglnet/composer-require-checker`. Por isso o CT-28 reprova a base. Ele passa quando a referência listar os quatro, cada um numa F-nn de origem pacote ou na lista dos sem feature própria, com o motivo.
- **Inventário Mermaid:** DG-01 a DG-20 por idioma nos arquivos de `docs/`, mais o DG-01 no README de cada idioma, com o arquivo de cada bloco congelado. A lista vem de `blocosMermaidDaArvore()` (`tests/Pest.php:blocosMermaidDaArvore:1478`) rodado na base.
- **`site/package.json`:** as `dependencies` são `@astrojs/starlight`, `astro`, `astro-mermaid`, `mermaid` e `sharp`; as `devDependencies` são `axe-core`, `playwright` e `wait-on`.
- **CHANGELOG:** 141 cabeçalhos `## [x.y.z] - AAAA-MM-DD` e o `## [Unreleased]`, sem data. Exemplo: `## [0.56.0] - 2026-11-19`. Só cinco versões citam F-nn, e por isso o marco da linha do tempo casa por termo, e não por F-nn (R29).
- **Lido da árvore atual, não congelado:** as páginas do site (39 por idioma na base), os diagramas e o modelo.

### Os tipos de visualização e os tipos de peça
- **Os dez tipos nativos (archify 3.2.0, commit `4478129…`):** `diagram_type` ∈ {`architecture`, `workflow`, `sequence`, `dataflow`, `lifecycle`, `erd`, `class`, `tree`, `timeline`, `waterfall`}, conforme o `schemas/README.md` do archify nesse commit.
- **O comparativo de arquitetura:** soma-se aos dez, mas sai de `compare architecture <antes> <depois>` e não é um `diagram_type`.
- **Os de fluxo, para a R22 e a R35:** `workflow`, `sequence` e `dataflow`, cujo passo nomeia peça; e `lifecycle`, cujo estado e cuja transição têm âncora no código *(alterado em 2026-11-20: ADV-11)*.
- **As formas de aresta nos mapas de arquitetura** (medidas pela sessão): direta, de agrupamento e de porta. A R12 e a R36 as definem.
- **Os tipos de peça (RQ-24 e glossário), 13 ao todo:** painel, recurso, página, widget, plugin de pacote, provider, middleware, serviço próprio e model, e também policy, setting, comando e componente Livewire quando a ligação passa por eles. O tipo declarado confere com a classe pela tabela da R8 (ADV-16).

### Guarda e controles
- Toda guarda é uma função aplicada ao modelo publicado e a cópias adulteradas **em memória**, nunca ao arquivo real. Ela devolve "aceita" ou "reprova", com um motivo que nomeia o item. O molde é `conferirDg10()` (`tests/Kit/GuardasDosDiagramasTest.php:conferirDg10:117`).
- A guarda do catálogo recebe a base como parâmetro. Assim o controle monta uma base em que um número já está removido, sem tocar na base congelada.
- O piso de não vácuo vem de uma fonte que o modelo não controla: o registro do painel, a árvore de `docs/`, os manifestos de pacotes ou o catálogo da base. Assim um modelo vazio não passa em "toda peça…".
- Trecho fora de comentário, por tipo de arquivo *(alterado em 2026-11-20: ADV2-06)*: em PHP, `codigoSemComentario()` (`tests/Pest.php:codigoSemComentario:2188`); em Blade, fora de `{{-- --}}` e de `<!-- -->`; em JS e stub, fora de `//` e de `/* */`.
- "No commit que publica" quer dizer que o arquivo está no índice do git. Invocar o `git` exige a sentinela (`[CT-26]` de `tests/Kit/RedeDeDocumentacaoTest.php`).
- Arquivos que existem no disco e estão fora do commit:
  - `bootstrap/cache/packages.php`, que o Composer gera e o `bootstrap/cache/.gitignore` ignora;
  - todo arquivo de `vendor/`.
- Quarto painel: `painelRegistradoEmTeste('financeiro')` (`tests/Pest.php:painelRegistradoEmTeste:812`). O registro morre com o container do caso.
- A guarda das classes de `app/` recebe a lista de classes como parâmetro. Assim o CT-41 acrescenta uma classe que não existe, sem tocar na árvore.
- *(alterado em 2026-11-20: segunda revisão adversarial)* Listas fechadas e critérios que as guardas compartilham, cada um conferido na regra dele: os métodos de contrato do alvo (R10, ADV2-08); os comandos `kit:*`, que nunca entram na lista das sem feature (R28, ADV2-18); os 39 termos do glossário e as palavras funcionais do pt na tradução (R16, ADV2-11 e ADV2-28); o título do item do README (R5, ADV2-03); a normalização dos caminhos do "onde fica" (R6, ADV2-04); os arquivos de `site/mapa/` (R21, D32).
- **Motivo de enfeite** *(alterado em 2026-11-20: ADV-22)*: o motivo vazio, só de travessão ou pontuação, ou "não se aplica" sem dizer por quê. A definição é uma só, nos CT-03, CT-21, CT-28, CT-29, CT-35 e CT-41, e no CT-B07 do `05`.
- A detecção da peça condicional (o `master_global`, o `canAccess()` e o `canView()`) saiu com a R30, estacionada na Q11 *(alterado em 2026-11-20: ADV-31)*.

### Helpers
- O helper usado pelos dois arquivos mora em `tests/Pest.php` (`.ai/rules/testes.md`, com a prova em `tests/Kit/HelpersDeTesteTest.php`). É o caso de ler o modelo, de montar o conjunto de registro de um painel num modo e de achar a F-nn de uma peça.
- O helper de um arquivo só fica nele.
- Reusar estes, sem clone com outro nome: `naArvoreDoKit()`, `paginasDoSite()`, `blocosMermaidDaArvore()`, `codigoSemComentario()` e `painelRegistradoEmTeste()`. O `usuarioDoKit()` saiu da lista com o CT-32, estacionado.

### Guardas herdadas que alcançam a entrega (não re-derivadas)
- **`tests/Kit/SiteDeDocumentacaoTest.php`:**
  - `[CT-04]` e `[CT-34]`: espelho pt/en.
  - `[CT-20]` e `[CT-23]`: navegação.
  - `[CT-36]` e `[CT-38]`: redirecionamento e stub.
  - `[CT-41]`: ordem.
  - `[CT-29]`, `[CT-30]` e `[CT-44]`: formato da página.
  - `[CT-14]`: o link do README resolve.
  - `[CT-12]`: as dependências do `package.json` da raiz congeladas.
  - `[CT-22]` e `[CT-45]`: links internos. Só leem link Markdown. Como a página do mapa usa `<a href>` relativo cru (Q15), eles não mudam.
- **`tests/Kit/DiagramasDaArquiteturaTest.php`:** `[CT-01]`, o DG onde o catálogo daquela wiki diz. Ele também mata o M1 da R26.
- **`site/verifica-links.mjs`:** confere todo `href` de todo `.html` do `dist/`, inclusive os `<a href>` da página para o mapa.

### Fakes
- Nenhum. A entrega não tem efeito colateral (e-mail, fila, evento, HTTP).

### Estratégia de DB
- O `RefreshDatabase` do `tests/Pest.php`, nas suítes Kit e Tenancy. Nenhum caso grava nem semeia: o único que semeava, o CT-32, está estacionado na Q11. O registro dos painéis e o roteador não dependem do banco.

---

## Regra R1 — O catálogo é um só, com as mesmas F-nn em pt e em en, a mesma feature e o mesmo estado em cada número, uma linha por F-nn e uma origem do glossário em cada

> `RQ-21`, `RQ-22`, `RQ-04`, `RQ-05`, `P-02` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia

```gherkin
# language: pt
Funcionalidade: Mapa interativo do kit com a skill archify

  Regra: O catálogo de features é um só, em paridade pt e en, e cada F-nn declara uma origem do glossário

    Esquema do Cenário: [CT-01] o catálogo tem as mesmas F-nn nos dois idiomas, a mesma feature em cada número, uma linha por F-nn e uma origem em cada
      Dado o catálogo de features em pt e em en "<mundo>"
      Quando a guarda do mapa confere o catálogo
      Então o resultado é "<resultado>"
      E a guarda conferiu, em cada idioma, ao menos as 74 F-nn da base

      Exemplos:
        | mundo                                                                                   | resultado                                | # partição                   |
        | publicado                                                                               | aceita                                   | válida                       |
        | cópia em que o en não tem a F-69, que o pt tem                                          | reprova, nomeando a F-69 e o idioma en   | paridade quebrada            |
        | cópia em que a F-72 do pt leva à página do dock de atalhos, e a do en, à do tutorial guiado | reprova, nomeando a F-72             | número com outra feature     |
        | cópia em que a F-30 declara a origem "biblioteca"                                       | reprova, nomeando a F-30 e a origem      | origem fora do conjunto      |
        | cópia em que a F-30 não declara origem                                                  | reprova, nomeando a F-30                 | origem ausente               |
        | cópia em que a F-30 está em duas linhas do pt                                           | reprova, nomeando a F-30                 | número repetido              |
        | cópia em que a F-118 e a F-121, novas, de código próprio e sem página nem rota, trocam de descrição entre pt e en | reprova, nomeando a F-118 e a F-121 | número novo com outra feature |
        | cópia com a F-05 marcada como removida, com o motivo, no pt e vigente no en | reprova, nomeando a F-05 e o idioma en | estado diferente entre os idiomas |
```

As origens válidas são as três do glossário (Catálogo de features): pacote, código próprio e pacote com customização própria. A linha "publicado" afirma que cada F-nn declara **exatamente uma** delas.

*(alterado em 2026-11-20: ADV-09, ADV2-01 e ADV2-02)* A mesma F-nn é a mesma feature, no mesmo estado, nos dois idiomas:
- **Nas linhas da base** (F-01 a F-71, F-03a, F-03b e F-03c): as duas linhas declaram a mesma origem, nomeiam o mesmo pacote, levam à mesma página (a do outro idioma, no espelho de `docs/`) e dão os mesmos caminhos de rota no "onde fica", com `{painel}` e `{panel}` tidos como o mesmo curinga.
- **Nas linhas novas** (F-72 em diante), que a entrega escreve: além disso, o conjunto de tokens entre crases da linha pt é o da linha en. Assim a F-nn nova sem página, pacote ou rota, como a de um comando `kit:*`, também fica amarrada pelo que só ela tem. A linha nova sem token em nenhum dos dois idiomas não tem essa âncora, e a troca entre duas linhas assim é lacuna declarada (R1-M8). *(alterado em 2026-11-20: "e não é vazio" saiu, medido no catálogo da entrega)*
- **O estado:** a F-nn removida está marcada como removida no pt e no en.

Medido na base: das 74 linhas, 4 têm tokens diferentes entre pt e en — F-02 e F-04 (`{painel}`/`{panel}`), F-07 (`/{painel}/meu-perfil`/`/{panel}/my-profile`, e o en está errado) e F-51 (`homologação`/`staging`, palavra traduzida) — e 10 não têm token nenhum. Por isso a identidade por tokens vale só para as linhas novas; nas da base fica a identidade acima, que reprova a F-07 en até a entrega a corrigir (R6, ADV2-04).

Medido no catálogo da entrega (commit `0f38e2f`, F-72 a F-121): as 50 linhas novas têm os mesmos tokens nos dois idiomas; só a F-118 ("Voltar ao topo") não tem token nenhum, e exigir token reprovaria dado correto. As linhas novas de código próprio sem página nem rota são duas, a F-118 e a F-121 ("Senha forte e comandos destrutivos bloqueados em produção", com `php artisan`, `APP_ENV=production` e `migrate:fresh`), e a linha de controle da troca usa as duas. As linhas da F-72, da F-118, da F-121 e da F-05 são de controle, em cópia: no commit, a F-72 é o login social por painel, com página, e a F-05, as passkeys, desligadas no kit.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a unificação é feita só no pt, e o en fica sem F-nn que o pt tem | CT-01 | linha "en sem a F-69": reprova nomeando F-69 e en; o mutante aceita |
| M2 | origem como texto livre ("biblioteca", "pacote + código") | CT-01 | linha "origem biblioteca": reprova nomeando a F-30; o mutante aceita |
| M3 | a coluna de origem é opcional, e a F-nn sem origem passa | CT-01 | linha "não declara origem": reprova; o mutante aceita |
| M4 | duas linhas com o mesmo número (a feature nova copiada de uma linha antiga sem trocar o número) | CT-01 | linha "F-30 em duas linhas": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-09) | com pt e en editados em paralelo, os números novos saem em ordem diferente: a F-72 do pt é o dock, e a do en, o tutorial | CT-01 | linha da F-72: reprova; o mutante aceita, porque confere só o conjunto de números |
| M6 — revisão adversarial (ADV2-01) | duas F-nn novas sem página, pacote nem rota trocam de feature entre pt e en, e os atributos de identidade vazios e iguais deixam passar | CT-01 | linha da F-118 e da F-121: os tokens entre crases diferem (nenhum na F-118, três na F-121), e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-02) | a F-nn é marcada como removida num idioma só | CT-01 | linha da F-05 removida no pt e vigente no en: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-01) | duas F-nn novas sem token nenhum, sem página e sem rota trocam de descrição entre pt e en | ⚠️ **sem matador**: no catálogo da entrega (commit `0f38e2f`), a F-118 é a única linha nova sem token, e exigir token reprovaria dado correto; sem token, página nem rota, não há âncora mecânica da identidade entre os idiomas. Fica para o quality gate | — (lacuna declarada) |

---

## Regra R2 — README, páginas do site e mapa só citam F-nn que o catálogo tem, e nenhum deles lista feature por fora dele

> `RQ-22`, `RQ-21`, `P-08` · perfil **padrão** · técnica: **EP** + controles em cópia

```gherkin
  Regra: Nenhum segundo catálogo nasce: quem cita feature cita a F-nn do catálogo do mesmo idioma

    Esquema do Cenário: [CT-02] o README, as páginas do site e o mapa só citam F-nn que o catálogo tem, e nenhum deles traz lista ou tabela de features
      Dado o README, as páginas de `docs/` fora do catálogo e os arquivos do mapa "<mundo>"
      Quando a guarda do mapa confere cada F-nn citada neles contra o catálogo do mesmo idioma
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu ao menos a F-03 e a F-04, que `docs/` já cita na base, e a F-nn de cada feature que o mapa cobre

      Exemplos:
        | mundo                                                                                       | resultado                                   |
        | publicados                                                                                  | aceita                                      |
        | cópia de uma página de `docs/pt/` que cita a F-99                                           | reprova, nomeando a página e a F-99         |
        | cópia do modelo em que uma peça cita a sua feature pelo nome, sem F-nn                      | reprova, nomeando a peça                    |
        | cópia do `README.en.md` que cita uma F-nn que só o catálogo pt tem                          | reprova, nomeando a F-nn e o `README.en.md` |
        | cópia da página do mapa com uma tabela de três F-nn                                         | reprova, nomeando a página                  |
        | cópia de um arquivo do mapa com uma lista de features, cada uma com a F-nn e o nome          | reprova, nomeando o arquivo                 |
        | cópia da página do mapa com uma lista só de nomes, "Passkeys", "Impersonate" e "Lixeira", sem número | reprova, nomeando a página |
        | cópia de um arquivo do mapa com um card de três itens que são nomes de F-nn do catálogo | reprova, nomeando o arquivo |
```

A varredura cobre o README, `docs/` e `site/mapa/`. Ficam de fora `tests/` e `app/`, onde "F-nn" também nomeia achado de revisão, como "F-01 da conformidade Blueprint" (`.ai/rules/kit.md`). Ali a regex daria falso positivo.

*(alterado em 2026-11-20: ADV-20)* "Lista ou tabela de features" tem dois sinais:
- **Nas páginas fora do catálogo:** linha de tabela ou item de lista que cita F-nn. Na base não há nenhuma, e as duas citações de `docs/`, em `autenticacao/login-social.md`, estão em prosa.
- **Nos arquivos do mapa:** a F-nn junto do nome que o catálogo dá a ela. A peça aponta a F-nn só pelo número.

*(alterado em 2026-11-20: ADV2-21)* A lista de features também pode vir só com os nomes, sem número. O terceiro sinal é este: um item de lista, uma linha de tabela ou um item de card que é o nome que o catálogo dá a uma F-nn (o texto da coluna "Feature", sem a marcação), com ou sem o número. Três ou mais itens assim na mesma lista, tabela ou card da página do mapa e dos arquivos do mapa reprovam. Ficam fora da conta:
- o nó de diagrama, porque nomeia a peça ou o marco. Medido no esqueleto: o `infra-registro` (tree) tem 3 nós cujo rótulo é nome de F-nn — Execuções de IA, Grafo de dependências e Lixeira —, e são peças;
- o item que é link para um diagrama do mapa, que é navegação.

Medido: nos cards dos 13 arquivos do esqueleto, nenhum item é nome de F-nn. A página do mapa ainda não existe; pelo passo 6 do `01`, as tabelas e listas dela são a grade painel × modo, com `<a href>` em cada célula, os fluxos, a matriz de tipos com o motivo de cada célula vazia e os limites, e nenhuma é lista de nomes de feature.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o mapa guarda uma lista própria de features, pelo nome, ao lado do catálogo *(alterado em 2026-11-20: ADV-20, a asserção antiga não divergia)* | CT-02 | linhas "tabela de três F-nn na página do mapa" e "lista de features num arquivo do mapa": reprova nomeando a página ou o arquivo; o mutante aceita |
| M2 | uma página do site cita número que o catálogo não tem (inventado, ou de antes da unificação) | CT-02 | linha "F-99": reprova nomeando a página; o mutante aceita |
| M3 | o README en cita F-nn que só o catálogo pt ganhou | CT-02 | linha `README.en.md`: reprova; o mutante aceita |
| M4 | a peça aponta a feature pelo nome, e não pela F-nn | CT-02 | linha "peça cita a feature pelo nome": reprova nomeando a peça; o mutante aceita |
| M5 — revisão adversarial (ADV2-21) | a lista própria de features vem só com os nomes, sem número, e não acende nenhum dos dois sinais | CT-02 | linhas da lista de três nomes na página do mapa e do card de três nomes: reprova; o mutante aceita |

---

## Regra R3 — Toda F-nn da base continua com a sua feature, ou fica marcada como removida com o motivo, e a removida não volta

> `P-08`, `RQ-22` · perfil **padrão** · técnica: **estado × operação** (matriz abaixo) + controles em cópia

Matriz do número do catálogo. São 3 estados (da base; removida com registro; nova) × 5 operações (manter; dar o número a outra feature; remover sem registro; remover com registro; acrescentar) = **15 células**. Delas, 9 têm cenário: 4 válidas, 4 inválidas e a célula mista "nova × acrescentar", que tem a borda válida e as inválidas. As outras 6 não se aplicam, com motivo.

O estado "removida" não existe na base, porque nenhum número está removido nela. As células dele rodam com uma base de controle em que a F-05 já está marcada como removida (linhas com a base "com a F-05 removida").

| Estado \ operação | manter | dar o número a outra feature | remover sem registro | remover com registro | acrescentar |
|---|---|---|---|---|---|
| da base | ✅ CT-03 (publicado) | ❌ CT-03 (F-10 e F-11 trocadas) | ❌ CT-03 (sem a F-05; removida sem motivo ou com motivo de enfeite) | ✅ CT-03 (F-05 removida com motivo) | não se aplica: acrescentar cria número novo |
| removida com registro | ✅ CT-03 (base com a F-05 removida: o catálogo mantém a marca) | ❌ CT-03 (base com a F-05 removida: o número é dado a outra feature) | ❌ CT-03 (base com a F-05 removida: a linha dela some) | não se aplica: já está removida | não se aplica: acrescentar cria número novo |
| nova | ✅ CT-04 (publicado) | não se aplica nesta entrega: a nova nasce agora, e na entrega seguinte ela é base | não se aplica nesta entrega (idem) | não se aplica nesta entrega (idem) | ✅ CT-04 (F-72) / ❌ CT-04 (F-73, buraco, F-03d) |

Legenda: ❌ = a guarda reprova, nomeando o número. Não há efeito colateral a afirmar, porque a operação é edição de arquivo e a guarda só lê.

```gherkin
  Regra: O número da base não troca de feature, não some sem registro, e o removido não volta

    @premissa
    Esquema do Cenário: [CT-03] o número da base fica com a sua feature, ou fica marcado como removido com o motivo, e não volta
      Dado a base "<base>", cada F-nn com o termo que identifica a sua feature
      E o catálogo "<mundo>"
      Quando a guarda do mapa confere o catálogo contra a base
      Então o resultado é "<resultado>"

      Exemplos:
        | base                              | mundo                                                       | resultado                          | # célula                                 |
        | `d9608c5a`                        | publicado, pt e en                                          | aceita                             | da base × manter                         |
        | `d9608c5a`                        | cópia com a F-10 e a F-11 trocadas de feature               | reprova, nomeando a F-10 e a F-11  | da base × dar o número a outra feature   |
        | `d9608c5a`                        | cópia sem a linha da F-05                                   | reprova, nomeando a F-05           | da base × remover sem registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, com o motivo        | aceita                             | da base × remover com registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, sem o motivo        | reprova, nomeando a F-05           | da base × remover sem registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, com o motivo "—"    | reprova, nomeando a F-05           | da base × remover sem registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, com o motivo "não se aplica", sem porquê | reprova, nomeando a F-05 | da base × remover sem registro  |
        | `d9608c5a` com a F-05 removida    | cópia que mantém a F-05 marcada como removida, com o motivo | aceita                             | removida × manter                        |
        | `d9608c5a` com a F-05 removida    | cópia em que a F-05 descreve outra feature                  | reprova, nomeando a F-05           | removida × dar o número a outra feature  |
        | `d9608c5a` com a F-05 removida    | cópia sem a linha da F-05                                   | reprova, nomeando a F-05           | removida × remover sem registro          |
```

`@premissa` de mecanismo, decidida pela sessão (Q14a): a removida fica na tabela do catálogo, marcada e com o motivo. A F-05 ("Passkeys", que o catálogo dá como desligada) é o exemplo discriminante porque é a candidata natural a sair na unificação.

*(alterado em 2026-11-20: ADV-02 e ADV-22)*
- O "aceita" da remoção com motivo é veredito só do catálogo. A F-nn removida que uma peça do modelo ainda entrega reprova no CT-36 (R6), que lê o modelo.
- "Motivo de enfeite" tem a definição do Setup Global, a mesma do CT-21.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a unificação renumera por ordem de seção (as F-62 a F-68 de Autorização passam a vir logo depois da F-14) | CT-03 | linha "publicado": a F-15 da base deixa de ter o termo "Convite individual" e a guarda reprova; o mutante aceita |
| M2 | a guarda confere só que o número existe, e não a feature dele | CT-03 | linha "F-10 e F-11 trocadas": reprova; o mutante aceita |
| M3 | um número some sem registro: a F-05 apagada na unificação, ou a linha de uma removida apagada depois | CT-03 | as duas linhas "sem a linha da F-05": reprova; o mutante aceita |
| M4 | número removido é reaproveitado para uma feature nova | CT-03 | linha "base com a F-05 removida", "a F-05 descreve outra feature": reprova; o mutante aceita |
| M5 | a marca de removida vem sem o motivo | CT-03 | linha "removida sem o motivo": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-22) | a marca de removida vem com motivo de enfeite ("—", ou "não se aplica" sem porquê) | CT-03 | linhas do motivo "—" e do "não se aplica" sem porquê: reprova; o mutante aceita |

---

## Regra R4 — A feature nova recebe o próximo número livre: a F-72, e as seguintes em sequência, sem buraco e sem sufixo novo

> `P-08` · perfil **padrão** · técnica: **BVA 3-valores** (escalada; fronteira: o maior inteiro da base, 71; incremento: 1 número)

A borda−1 (71) é número da base. Dar a F-71 a uma feature nova troca a feature dela, e isso morre no CT-03. O CT-04 cobre a borda (72), a borda+1 (73), o buraco e o sufixo de letra. O sufixo foi fechado pelo `00` (Q13): a P-08 dá à feature nova "o próximo número livre", e depois da F-71 o próximo é a F-72. Um sufixo não é o próximo número.

```gherkin
  Regra: O primeiro número novo é a F-72, os seguintes vêm em sequência, e nenhum número novo tem sufixo de letra

    Esquema do Cenário: [CT-04] os números que a base não tem começam na F-72, seguem em sequência e não têm sufixo
      Dado o maior inteiro da base, 71
      E o catálogo "<mundo>"
      Quando a guarda do mapa confere os números que a base não tem
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                    | resultado                                  | # borda          |
        | publicado, pt e en                       | aceita                                     | sequência real   |
        | cópia com um único número novo, a F-72   | aceita                                     | borda            |
        | cópia com um único número novo, a F-73   | reprova, nomeando a F-73 (pula a F-72)     | borda+1          |
        | cópia com os números novos F-72 e F-74   | reprova, nomeando a F-74 (pula a F-73)     | buraco           |
        | cópia com um único número novo, a F-03d  | reprova, nomeando a F-03d                  | sufixo novo      |
```

A linha "publicado" não é vazia: a base está defasada em relação ao README (ver Setup Global), então a unificação acrescenta F-nn.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | os novos começam depois de um salto (F-73, ou F-100 "para separar as novas") | CT-04 | linha "F-73": reprova; o mutante aceita |
| M2 | a nova ocupa um número qualquer, sem sequência | CT-04 | linha "F-72 e F-74": reprova nomeando a F-74; o mutante aceita |
| M3 | a variante de uma feature existente ganha sufixo de letra (o "+" no e-mail como F-03d, ao lado de F-03a, F-03b e F-03c) | CT-04 | linha "F-03d": reprova; o mutante aceita |

---

## Regra R5 — Todo item de feature do README da base e toda página do site correspondem a ao menos uma F-nn — os seis itens que a base não cobre, pelo termo fixado —, ou a página está na lista das que não descrevem feature, com o motivo

> `RQ-19`, `RQ-04`, `RQ-05`, `P-02` · perfil **padrão** · técnica: **EP por fonte** + controles em cópia

A Q7 foi fechada pelo `00`: a RQ-19 diz "páginas do site" sem recorte. Então todas as páginas de `docs/{pt,en}` contam. As de `recursos/` e de `autenticacao/`, fora os índices, nunca vão para a lista das que não descrevem feature, porque cada uma descreve uma. Esse é o invariante que já valia antes da resposta. Os índices de seção, como `recursos/index.md`, são justamente páginas que não descrevem feature.

```gherkin
  Regra: Toda fonte da RQ-19 chega ao catálogo: o README da base e todas as páginas do site

    @premissa
    Esquema do Cenário: [CT-05] todo item de feature do README da base e toda página do site chegam ao catálogo, ou a página está na lista das que não descrevem feature
      Dado a fonte "<fonte>" em "<idioma>", com <itens> itens
      Quando a guarda do mapa casa cada item com o catálogo do mesmo idioma, por "<correspondência>"
      Então cada item corresponde a ao menos uma F-nn que existe nesse catálogo — ou, se é página que não descreve feature, está na lista da guarda com o motivo
      E nenhum item fica sem uma das duas coisas, e nenhuma página de `recursos/` ou de `autenticacao/`, fora os índices, está nessa lista
      E <casamento>
      E a guarda conferiu exatamente <itens> itens

      Exemplos:
        | fonte                                  | idioma | itens                       | correspondência                                                    | casamento                                                                                                            |
        | lista de features do README da base    | pt     | 46                          | a tabela da base congelada na guarda                               | cada um dos seis itens que a base não cobre casa com uma F-nn cuja linha traz o termo pt do item, como palavra inteira (tabela abaixo)      |
        | lista de features do README da base    | en     | 46                          | a tabela da base congelada na guarda                               | cada um dos seis itens que a base não cobre casa com uma F-nn cuja linha traz o termo en do item, como palavra inteira (tabela abaixo)      |
        | todas as páginas de `docs/pt/`         | pt     | as da árvore (39 na base)   | o link da linha do catálogo para a página, ou a lista da guarda    | a linha de cada F-nn casada leva por link à própria página                                                           |
        | todas as páginas de `docs/en/`         | en     | as da árvore (39 na base)   | o link da linha do catálogo para a página, ou a lista da guarda    | a linha de cada F-nn casada leva por link à própria página                                                           |
        | lista de features do README publicado  | pt     | as da lista publicada (46 na base) | o título do item: o da base, pela tabela congelada; o novo, pela tabela da guarda, com a F-nn e o termo | todo item cujo título não é de um dos 46 da base casa com uma F-nn cuja linha traz o termo dele, como palavra inteira |
        | lista de features do README publicado  | en     | as da lista publicada (46 na base) | o título do item: o da base, pela tabela congelada; o novo, pela tabela da guarda, com a F-nn e o termo | todo item cujo título não é de um dos 46 da base casa com uma F-nn cuja linha traz o termo dele, como palavra inteira |

    @premissa
    Esquema do Cenário: [CT-35] a guarda das fontes reprova o item casado com a feature errada, a página escondida na lista e o motivo de enfeite
      Dado a tabela e a lista da guarda das fontes "<mundo>"
      Quando a guarda do mapa casa as fontes com o catálogo do mesmo idioma
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                           | resultado                                 | # partição                        |
        | cópia da tabela que casa o item "Dock de atalhos" do README pt com a F-06 (Bloqueio de sessão), cuja linha não traz `dock` | reprova, nomeando o item     | termo ausente da linha casada     |
        | cópia da tabela que casa o item "Dock de atalhos" do README pt com a F-56 (Inferência local), cuja linha traz "docker", e não a palavra `dock` | reprova, nomeando o item | termo só como pedaço de palavra |
        | cópia da tabela que casa o item "Tutorial guiado" do README pt com a F-99, que o catálogo não tem                | reprova, nomeando o item e a F-99         | F-nn inexistente                  |
        | cópia da lista com a página `recursos/dock-de-atalhos.md`, com motivo                                            | reprova, nomeando a página                | página de recurso na lista        |
        | cópia da lista com a página `comecar/index.md`, sem o motivo                                                     | reprova, nomeando a página                | motivo ausente                    |
        | cópia da lista com a página `comecar/index.md`, com o motivo "—"                                                 | reprova, nomeando a página                | motivo de enfeite                 |
        | cópia da lista com a página `comecar/index.md`, com o motivo "não se aplica", sem porquê                         | reprova, nomeando a página                | motivo de enfeite                 |
        | cópia do README pt com um item a mais na lista, "**Mapa interativo**: …", fora da tabela da guarda | reprova, nomeando o item | item novo sem F-nn |
```

`@premissa` de mecanismo, decidida pela sessão (Q14b e Q14c):
- a linha da F-nn leva por link à página que a descreve;
- a página sem feature fica listada na guarda, com o motivo;
- os itens do README da base casam pela tabela congelada na guarda.

*(alterado em 2026-11-20: ADV-01)* Os seis itens do README que a base do catálogo não cobre, com o termo que a linha da F-nn casada tem de trazer. O termo casa por palavra inteira, sem diferença de maiúscula e com acento *(alterado em 2026-11-20: conferido pela sessão)*. Como pedaço de palavra, `dock` casaria "docker" na linha da F-56 (`docs/pt/operacao/roteiro-de-features.md:F-56:133`, `docs/en/operacao/roteiro-de-features.md:F-56:131`), e `views` casaria "reviews". O `"+"` discrimina: nenhuma linha da base o traz.

| Item do README da base (pt / en) | Termo pt | Termo en |
|---|---|---|
| Dock de atalhos / Shortcut dock | `dock` | `dock` |
| Tutorial guiado / Guided tutorial | `tutorial` | `tutorial` |
| Visões salvas de tabela / Saved table views | `visões` | `views` |
| Página única de login / Single login page | `página única` | `single login` |
| Proteção anti-robô opcional / Optional anti-robot protection | `anti-robô` | `anti-robot` |
| "+" no e-mail do cadastro / "+" in the sign-up e-mail | `"+"` | `"+"` |

*(alterado em 2026-11-20: ADV2-03)* A R5 lê também a lista publicada do README, e não só a da base. O item é reconhecido pelo título: o texto em negrito do começo; sem negrito, o texto antes do primeiro ":", "—" ou "("; e, sem nenhum dos dois, o item inteiro. Medido nos dois READMEs: 46 itens e 46 títulos diferentes por idioma, 26 em negrito, 17 pelo separador e 3 pelo item inteiro. O item cujo título não é de um dos 46 da base fica na tabela da guarda, com a F-nn e o termo, como os seis do ADV-01.

*(alterado em 2026-11-20: ADV-21 e ADV-22)* O CT-35 dá a cada mutante da guarda das fontes uma cópia que o mata, sem depender de a árvore real estar errada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a unificação esquece um item do README que a base do catálogo não tinha (dock de atalhos, tutorial guiado, "+" no e-mail) | CT-05 | linha README pt: o item fica sem F-nn e a contagem de casados fica abaixo de 46; o mutante aceita |
| M2 | a lista unificada só entra no catálogo pt | CT-05 | linha README en: reprova; o mutante aceita |
| M3 | a tabela da guarda aponta F-nn que o catálogo não tem | CT-35 | linha da F-99: reprova; o mutante aceita |
| M4 | uma página fica sem F-nn e fora da lista, ou entra na lista sem o motivo (a página nova de `comecar/`, criada depois da base) | CT-05, CT-35 | CT-05, linhas de páginas, lidas da árvore: a página sem nenhuma das duas coisas reprova; CT-35, linha "sem o motivo": reprova; o mutante aceita |
| M5 | página de recurso jogada na lista das que não descrevem feature, para fugir do catálogo | CT-35 | linha `recursos/dock-de-atalhos.md`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-01) | a tabela da guarda, escrita pela mesma mão que escreve o catálogo, casa um item novo do README com a F-nn de outra feature | CT-05, CT-35 | CT-35, linha do dock casado com a F-06: reprova; e, no publicado, o CT-05 exige o termo na linha casada; o mutante aceita |
| M7 — revisão adversarial (ADV-22) | página na lista com motivo de enfeite | CT-35 | linhas do motivo "—" e do "não se aplica" sem porquê: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-01; conferido pela sessão) | o termo casa como pedaço de palavra, e `dock` acha "docker" na linha da F-56 | CT-35 | linha do dock casado com a F-56: reprova; o mutante aceita |
| M9 — revisão adversarial (ADV2-03) | a entrega acrescenta à lista do README um item sem F-nn, como o próprio mapa, e a guarda só lê o README da base | CT-05, CT-35 | CT-35, linha do item "Mapa interativo": reprova; e, no publicado, o CT-05 lê a lista publicada; o mutante aceita |

---

## Regra R6 — Toda F-nn vigente é entregue por peça desenhada da origem que ela declara — do pacote que a linha nomeia, ou, no código próprio, da rota do "onde fica" —, com a F-nn no desenho da peça; todo caminho do "onde fica" resolve; e nenhuma peça entrega F-nn removida

> `RQ-04`, `RQ-05`, `RQ-21`, `P-03`, `P-10`, `P-08` · perfil **padrão** · técnica: **EP por origem** (as três partições do glossário) + tabela de decisão (F-nn × pacote nomeado × pacote da peça; removida × entregue) + controles em cópia

```gherkin
  Regra: A demonstração cobre todas as features, as de pacote e as de código próprio

    @premissa
    Esquema do Cenário: [CT-06] toda F-nn é entregue por peça desenhada, e ao menos uma dessas peças é da origem que ela declara
      Dado as F-nn do catálogo com a origem "<origem>", fora as marcadas como removidas
      Quando a guarda do mapa procura as peças que entregam cada uma
      Então cada F-nn é entregue por ao menos uma peça do modelo que está desenhada em diagrama publicado
      E ao menos uma dessas peças é <peça da origem>
      E todo caminho do "onde fica" dessas F-nn resolve no roteador, no modo sem tenant (o caminho com `{org}` fica para o CT-44)

      Exemplos:
        | origem                          | peça da origem                                                                                                                                                      |
        | pacote                          | do pacote que a linha nomeia, pelo nome do Composer ou do npm                                                                                                       |
        | código próprio                  | do código do kit (fora de `vendor/` e de `node_modules/`); e, quando a célula "onde fica" traz um caminho entre crases que começa por `/`, em qualquer ponto, sem "→", a página que responde num desses caminhos, ou o recurso dono dela |
        | pacote com customização própria | uma do pacote que a linha nomeia e outra do código do kit                                                                                                           |

    @premissa
    Esquema do Cenário: [CT-36] a guarda da origem reprova a F-nn entregue pela peça errada e a removida que ainda é entregue
      Dado o catálogo e o modelo "<mundo>"
      Quando a guarda do mapa confere a origem e a entrega de cada F-nn
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                                         | resultado                                  | # partição                          |
        | publicados                                                                                                                    | aceita: nenhuma peça do modelo entrega F-nn marcada como removida | válida                    |
        | cópia em que a F-33 (Health checks), de origem pacote, nomeia `shuvroroy/filament-spatie-laravel-health` e é entregue só pelo `FilamentJobsMonitorPlugin` | reprova, nomeando a F-33 e o pacote | peça de outro pacote |
        | cópia em que a F-29 (Usuários, `/admin/users`), de código próprio, é entregue só pelo `ConviteResource`                        | reprova, nomeando a F-29 e a rota          | peça que não responde na rota       |
        | cópia em que uma F-nn de código próprio é entregue só por peça de pacote                                                      | reprova, nomeando a F-nn                   | origem errada                       |
        | cópia em que uma F-nn de pacote com customização própria é entregue por uma peça só                                           | reprova, nomeando a F-nn                   | customização com uma peça só        |
        | cópia com a F-05 marcada como removida, com o motivo, e uma peça do modelo que ainda a entrega                                | reprova, nomeando a F-05 e a peça          | removida e entregue                 |
        | cópia em que o "onde fica" da F-07 en é `/{panel}/my-profile`, que o roteador não resolve | reprova, nomeando a F-07 e o caminho | caminho que não resolve |

    Esquema do Cenário: [CT-44] com tenant, todo caminho do "onde fica" que leva a organização resolve no roteador
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o catálogo em "<idioma>"
      Quando a guarda do mapa resolve no roteador cada caminho do "onde fica" que traz `{org}` ou `{/org}`, com uma organização no lugar do curinga
      Então todo caminho resolve
      E a guarda conferiu ao menos os caminhos da F-15, da F-22 e da F-25

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

O piso de não vácuo é o do CT-01: cada F-nn tem exatamente uma origem, e as três linhas somadas percorrem o catálogo inteiro.

`@premissa` de mecanismo, decidida pela sessão (Q17): a linha do catálogo nomeia o pacote pelo nome do Composer ou do npm.

*(alterado em 2026-11-20: ADV-02, ADV-03 e ADV-21)*
- **O pacote da peça.** A peça é do pacote quando a classe dela está em `vendor/{pacote}/`.
- **A rota do "onde fica".**
  - O caminho de rota é o texto entre crases que começa com `/`.
  - A guarda troca `{painel}` por cada painel do registro, tira o `{org}` (a suíte do kit roda sem tenant) e a query, e resolve o caminho no roteador da aplicação.
  - Todo caminho da célula resolve no roteador, em toda linha, de qualquer origem *(alterado em 2026-11-20: ADV2-04; era "basta um caminho")*. `{painel}` e `{panel}` valem para cada um dos três painéis, e todos têm de resolver; `{/org}` vale sem a organização no modo sem tenant e com ela no modo com tenant; `{org}` só existe com tenant e é conferido no CT-44, na suíte Tenancy; outro parâmetro aceita qualquer valor.
  - No código próprio, ao menos um caminho resolve para uma peça que entrega a F-nn: a página que responde nele, ou o recurso dono dela. Medido com `php artisan route:list`: `admin/users` resolve para `ListUsers`, página do `UserResource`; `app/login`, para `TelaLogin`; `infra/health-check-results`, para `HealthCheckResults`.
  - Medido com `php artisan route:list --json` nos dois modos: no pt, 51 caminhos, 3 só do modo com tenant (F-15, F-22 e F-25, `/app/{org}…`), e todos resolvem; no en, os mesmos, menos a F-07, `/{panel}/my-profile`, que não resolve sem tenant em painel nenhum (a rota é `/meu-perfil` nos três). É defeito da base, que a entrega corrige no passo 5. No catálogo da entrega (commit `0f38e2f`, a coluna "Onde" achada pelo cabeçalho, depois da Origem): 92 caminhos por idioma, 6 só do modo com tenant, e todos resolvem nos dois modos (136 rotas sem tenant e 137 com).
- **Até onde o oráculo vai.** A regra é a célula "onde fica" trazer um caminho entre crases que começa por `/`, em qualquer ponto, sem "→". Na base, dá 40 das 74 linhas, nos dois idiomas. Outras 4 têm caminho e "→", e 30 não têm caminho (menu, ação, "os três", "—"). *(alterado em 2026-11-20: a regra antiga, "começa por caminho", dava 37 em pt e 38 em en, porque a F-44 é "topbar do `/infra`" em pt e "`/infra` topbar" em en)*
  - Nessas 40, a rota fecha o oráculo do código próprio.
  - Nas outras, ele é só "do código do kit", e trocar uma peça do kit por outra fica como lacuna (M8).
- **A F-nn no desenho da peça** *(alterado em 2026-11-20: ADV2-05, D29; oráculo ajustado pela medição no commit `4adaf9a`)*. Nos diagramas que os construtores geram do modelo — o mapa de arquitetura e a árvore de registro —, a F-nn de cada peça com nó próprio que entrega feature está no desenho dela, e o CT-B01 confere as duas formas. A peça que só aparece como membro de um agrupamento não tem nó próprio: o card "Quem está em …" a nomeia, e a F-nn dela fica no diagrama em que ela tem nó (medido: o `app-resumo-sem` desenha 1 peça e 7 agrupamentos).
  - **No mapa de arquitetura, no `tag`** do componente ("F-29 · F-30"). O archify o desenha como detalhe fino (`data-detail="fine"`): o visualizador abre no nível de leitura "read", e o `tag` fica com opacidade 0; a partir de 175 % de zoom (nível "full", pelo botão "+") e com a peça em foco pela busca, opacidade 1. O CT-B01 leva o visualizador ao nível cheio e confere a opacidade. Medido: 4 peças com F-nn no `app-acesso`, em pt e em en, com 0 no carregamento e 1 no nível cheio; o passaporte mostra o `tag`, e a busca por "F-02" acha as duas telas que a entregam.
  - **Na árvore de registro, no sublabel.** O esquema `tree` do archify não tem `tag` — só `architecture`, `workflow`, `dataflow`, `lifecycle` e `erd` o têm, e no `erd` ele é a faixa de domínio, e não um texto da entidade. A F-nn vai no sublabel, à vista no carregamento. Medido: 5 peças com F-nn em cada árvore do `/app`, com opacidade 1.
  - **No carregamento, o `tag` não aparece.** A P-10 não conta como desenho a etiqueta que o zoom esconde, e o ADV2-05 era "a F-nn não chega ao leitor". O oráculo segue a D29 e pede o nível cheio; se a F-nn tiver de aparecer sem zoom, a D29 muda (por exemplo, a F-nn no sublabel, como na árvore), e o ponto foi ao coordenador.
- **O CT-36 controla com cópias.** Cada mutante da guarda da origem morre sem depender de a árvore real estar errada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o modelo nasce só da varredura de `app/`, e a feature de pacote fica sem peça | CT-06 | linha "pacote": uma F-nn de pacote sem peça do pacote nomeado reprova; o mutante aceita |
| M2 | a peça da feature está no modelo, mas não está desenhada em diagrama publicado | CT-06 | "desenhada em diagrama publicado": reprova; o mutante aceita |
| M3 | origem declarada errada: "código próprio" numa feature entregue só por peça de pacote | CT-36 | linha "código próprio entregue só por peça de pacote": reprova; o mutante aceita |
| M4 | a guarda ou o gerador tratam só duas origens, e a "pacote com customização própria" passa sem conferência | CT-36 | linha "customização com uma peça só": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-02) | feature que o kit ainda implementa sai como removida (a F-05, "desligada por padrão"), e a peça continua a entregá-la | CT-36 | linha da F-05 removida e entregue: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-03) | a associação peça → F-nn é livre: a F-nn do Health é entregue só por outro plugin | CT-36 | linha da F-33 entregue pelo `FilamentJobsMonitorPlugin`: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-03) | a F-nn de código próprio é entregue por uma peça do kit que não responde na rota do "onde fica" | CT-36 | linha da F-29 entregue pelo `ConviteResource`: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-03) | a F-nn de código próprio cujo "onde fica" não é rota (menu, ação, painel inteiro) é entregue por outra peça do kit | ⚠️ **sem matador**: nas 34 linhas da base cujo "onde fica" não traz caminho sem "→" (4 com "→" e 30 sem caminho), não há peça que a guarda resolva sem julgamento. Fica para o quality gate | — (lacuna declarada) |
| M9 — revisão adversarial (ADV2-04) | o "onde fica" guarda um segundo caminho velho, que não resolve mais, e "basta um caminho" aceita a célula | CT-36, CT-44 | CT-36, linha da F-07 en: reprova; com tenant, o CT-44 resolve os caminhos com `{org}`; o mutante aceita |
| M10 — revisão adversarial (ADV2-05) | o modelo liga cada peça à F-nn dela, mas nenhum diagrama mostra a F-nn ao leitor | CT-B01 | "a F-nn de cada peça com nó próprio que entrega feature está no desenho dela: no `tag`, com opacidade 1 no nível de leitura cheio, ou no sublabel da árvore, à vista": reprova; o mutante aceita |

---

## Regra R7 — Manter o catálogo único é uma rule indexada, que alcança os lugares onde a feature nasce e manda o número só crescer

> `RQ-23`, `P-08` · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: A rule do catálogo existe, está no índice e manda o que mantém o catálogo único

    Cenário: [CT-07] a rule do catálogo está no índice das rules, alcança o catálogo e os lugares onde a feature nasce, e manda o número só crescer
      Dado o índice das rules do projeto
      Quando a guarda do mapa procura a linha que aponta para a rule do catálogo de features
      Então a linha existe, e cada caminho dela casa ao menos um arquivo existente
      E os caminhos da linha casam a página do catálogo em pt e em en, e também `app/**`, `composer.json`, `package.json` e `routes/**`
      E a rule nomeia a página do catálogo como o lugar único das features
      E a rule manda a feature nova receber o próximo número livre e proíbe trocar, reaproveitar ou remover número sem registro
```

*(alterado em 2026-11-20: ADV-18)* A feature nasce num recurso, num plugin, num comando ou numa rota, e o pacote, nos manifestos. A rule que só casa a página do catálogo nunca é aberta por quem cria a feature, e o catálogo volta a defasar (RQ-22).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a rule existe, mas o índice não aponta para ela, e o agente nunca a abre | CT-07 | "a linha existe": reprova; o mutante aceita |
| M2 | o caminho da linha do índice não casa arquivo nenhum (erro de digitação no glob) | CT-07 | "cada caminho casa ao menos um arquivo existente": reprova; o mutante aceita |
| M3 | a rule fala do catálogo único e esquece a numeração | CT-07 | "manda o próximo número livre e proíbe…": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-18) | a linha da rule casa só a página do catálogo, e quem cria recurso, plugin ou comando nunca a abre | CT-07 | "casam também `app/**`, `composer.json`, `package.json` e `routes/**`": reprova; o mutante aceita |

---

## Regra R8 — Toda peça do modelo é peça com nome que existe, do tipo que a classe dela é, dentro do conjunto fechado, e aparece uma vez

> `RQ-24`, `P-03` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + tabela de decisão (classe × tipo declarado) + controles em cópia

```gherkin
  Regra: Módulo é peça com nome que existe no código, e o modelo não inventa nem repete peça

    Esquema do Cenário: [CT-08] toda peça do modelo existe no código, é de um tipo do conjunto fechado e aparece uma vez
      Dado o modelo de peças "<mundo>"
      Quando a guarda do mapa confere cada peça contra o código, contra os pacotes instalados e contra o registro de painéis
      Então o resultado é "<resultado>"
      E, no publicado, cada painel do registro de painéis é uma peça do tipo painel

      Exemplos:
        | mundo                                                                                         | resultado                   |
        | publicado                                                                                     | aceita                      |
        | cópia com a peça `App\Filament\Admin\Resources\Inexistente\InexistenteResource`               | reprova, nomeando a classe  |
        | cópia com uma peça do tipo "rota"                                                             | reprova, nomeando o tipo    |
        | cópia com `App\Models\User` do tipo recurso                                                   | reprova, nomeando a classe e o tipo |
        | cópia com `App\Models\User` em duas peças                                                     | reprova, nomeando a classe  |
        | cópia com uma peça do pacote `vendor-inexistente/plugin`, que não está instalado              | reprova, nomeando o pacote  |
        | cópia com a peça painel "financeiro", que o registro de painéis não tem                       | reprova, nomeando o painel  |
```

Os tipos válidos são os 13 do Setup Global.

*(alterado em 2026-11-20: ADV-16)* O tipo declarado confere com a classe. A guarda testa as linhas abaixo, na ordem, e a primeira que a classe satisfaz dá o tipo esperado. As classes-base do Filament foram conferidas no vendor (`vendor/filament/filament/src/Resources/Resource.php`, `vendor/filament/filament/src/Pages/BasePage.php`, `vendor/filament/widgets/src/Widget.php` e `vendor/filament/filament/src/Contracts/Plugin.php`).

| Tipo | A classe… |
|---|---|
| painel | não é classe: é um id do registro de painéis |
| recurso | estende `Filament\Resources\Resource` |
| página | estende `Filament\Pages\BasePage`: o `Page` dos painéis e o `SimplePage` das telas de autenticação (o `TelaLogin` estende o `SimplePage`) |
| widget | estende `Filament\Widgets\Widget` |
| plugin de pacote | implementa `Filament\Contracts\Plugin` |
| model | estende `Illuminate\Database\Eloquent\Model` |
| provider | estende `Illuminate\Support\ServiceProvider` |
| comando | estende `Illuminate\Console\Command` |
| setting | estende `Spatie\LaravelSettings\Settings` |
| componente Livewire | estende `Livewire\Component` |
| policy | é a que o `Gate` devolve para um model |
| middleware | é registrada como middleware por um painel ou pela aplicação, ou é o alias `panel:{id}` |
| serviço próprio | é do código do kit e não cai em nenhuma linha acima |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a peça de uma classe apagada continua no modelo | CT-08 | linha `InexistenteResource`: reprova; o mutante aceita |
| M2 | tipo de peça fora do conjunto da RQ-24 e do glossário ("rota", "tabela") | CT-08 | linha "rota": reprova; o mutante aceita |
| M3 | a mesma classe vira duas peças, e as ligações dela se dividem entre as duas | CT-08 | linha `User` em duas peças: reprova; o mutante aceita |
| M4 | a peça de um pacote desinstalado continua no modelo | CT-08 | linha `vendor-inexistente/plugin`: reprova; o mutante aceita |
| M5 | o painel é escrito à mão no modelo, sem conferir o registro | CT-08 | linha "financeiro": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-16) | a varredura erra o tipo dentro do conjunto fechado: o model `User` como recurso, um widget como página | CT-08 | linha do `User` do tipo recurso: reprova; o mutante aceita |

---

## Regra R9 — Toda prova resolve no commit que publica: o arquivo é versionado no kit, o trecho está nele, fora de comentário pela sintaxe do tipo de arquivo, e a prova não guarda número de linha, nem no modelo nem nos arquivos de diagrama

> `RQ-24`, `P-03`, `P-04`, `P-07` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha, nunca duas juntas) + controles em cópia

A Q9 foi fechada pelo `00`. Pela P-07, o link de prova aponta para o commit que publica, e o `vendor/` não está nele. Pela RQ-24, a prova de uma peça que um plugin traz é, no código versionado do kit, o registro do plugin no painel (R10). Então toda prova que cita arquivo fora do commit reprova, inclusive a de `vendor/`.

```gherkin
  Regra: A ligação vem com o trecho de código que a prova, no commit que publica, e a guarda falha quando o código deixa de bater

    Esquema do Cenário: [CT-09] a prova de toda ligação resolve no commit que publica: o arquivo é versionado, o trecho está nele, fora de comentário, e a prova não guarda número de linha
      Dado uma ligação do modelo, com a prova "<prova>"
      Quando a guarda do mapa confere a prova
      Então o resultado é "<resultado>"
      E, no modelo publicado, a guarda conferiu a prova de tantas ligações quantas o modelo tem, e de ao menos uma de cada painel do registro

      Exemplos:
        | prova                                                                                                                                                      | resultado                    | # partição                        |
        | "o painel admin registra o plugin do Shield": `app/Providers/Filament/AdminPanelProvider.php`, trecho `FilamentShieldPlugin::make()`                       | aceita                       | válida: arquivo versionado        |
        | a mesma ligação, sem trecho                                                                                                                                | reprova, nomeando a ligação  | trecho ausente                    |
        | a mesma ligação, trecho vazio                                                                                                                              | reprova, nomeando a ligação  | trecho vazio                      |
        | a mesma ligação, trecho só de espaços                                                                                                                      | reprova, nomeando a ligação  | trecho só de espaços              |
        | a mesma ligação, contra uma cópia em memória do arquivo sem a linha do trecho                                                                              | reprova, nomeando a ligação  | trecho que saiu do código         |
        | a mesma ligação, trecho `ver a nota longa no AppPanelProvider`, que só existe num comentário do arquivo                                                    | reprova, nomeando a ligação  | trecho só em comentário           |
        | a mesma ligação, em `app/Providers/Filament/AdminPainelProvider.php`, que não existe                                                                       | reprova, nomeando o arquivo  | arquivo inexistente               |
        | a mesma ligação, em `bootstrap/cache/packages.php`, que está no disco e fora do git, com um trecho que está nele                                          | reprova, nomeando o arquivo  | arquivo fora do git               |
        | "o painel infra registra a página `HealthCheckResults`": `vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php`, trecho `$panel->pages([$this->getPage()]);` | reprova, nomeando o arquivo  | arquivo de pacote, fora do commit |
        | a primeira ligação, com a prova `app/Providers/Filament/AdminPanelProvider.php:240`, que guarda o número da linha, e o mesmo trecho                         | reprova, nomeando a ligação  | prova com número de linha         |
        | a mesma ligação, em `resources/views/filament/pages/convites-recebidos.blade.php`, trecho `ConvitesRecebidos::table()`, que só existe dentro do `{{-- --}}` da linha 1 | reprova, nomeando a ligação | trecho só em comentário Blade |
        | cópia do modelo com a prova de uma ligação num campo `linha: 240`, ao lado do arquivo e do trecho | reprova, nomeando a ligação | linha num campo do modelo |
        | cópia de um arquivo de diagrama em que a prova de uma peça, no `sources`, traz `line: 240` | reprova, nomeando o diagrama e a peça | linha nas provas do diagrama |
```

*(alterado em 2026-11-20: ADV-19 e ADV-23)*
- A prova é só o arquivo e o trecho. O número da linha envelhece a cada edição do arquivo (P-07), e quem o calcula é o build. A guarda reprova a prova que guarda linha, num campo próprio ou colada no caminho (`:240`, `#L240`).
- Essa linha está aqui, na suíte do kit, porque ela roda em todo PR. O job `site` não roda no PR que só toca `app/`, e é nesse PR que a linha guardada envelhece.

*(alterado em 2026-11-20: ADV2-06 e ADV2-07)*
- **Fora de comentário, por tipo de arquivo.** PHP: `codigoSemComentario()`. Blade (`.blade.php`): fora de `{{-- --}}` e de `<!-- -->`. JS e stub: fora de `//` e de `/* */`. Medido: a linha 1 de `resources/views/filament/pages/convites-recebidos.blade.php` é um `{{-- --}}` com `ConvitesRecebidos::table()`, e o arquivo, de 4 linhas, não traz o trecho fora dele.
- **A linha nunca é guardada.** O modelo não tem campo `linha` nem `line`, e a prova de peça nos arquivos de diagrama (`sources`) é sempre âncora `{arquivo, trecho}`, com `label` opcional. Medido nos 13 arquivos de diagrama do esqueleto: 405 provas, 3 com `arquivo` e `trecho`, 402 também com `label`, e nenhuma com linha nem com `:N` ou `#LN` no caminho. O gerador projeta a âncora e acha a linha na revisão publicada (`site/mapa/gerar.mjs`, função `linhaDaAncora`), e não aceita outra forma como prova.
- O piso conta todas as ligações do modelo, e não só as de registro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a guarda confere só que o arquivo existe | CT-09 | linha "cópia sem a linha do trecho": reprova; o mutante aceita |
| M2 | `str_contains()` com trecho vazio ou ausente devolve verdadeiro | CT-09 | linhas "sem trecho" e "trecho vazio": reprova; o mutante aceita |
| M3 | o trecho casa dentro de comentário (os arquivos do kit citam o que não fazem, `.ai/rules/testes.md`) | CT-09 | linha "só num comentário": reprova; o mutante aceita |
| M4 | prova em arquivo fora do git, cujo link para o commit daria 404 | CT-09 | linha `bootstrap/cache/packages.php`: reprova; o mutante aceita |
| M5 | a prova da peça de plugin cita o `register()` do plugin em `vendor/`, que existe no disco e não está no commit | CT-09 | linha `vendor/shuvroroy/…`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-23) | a guarda confere a prova só das ligações de registro, e a prova velha de uma ligação de uso (`UserResource` → `User`) passa | CT-09 | "conferiu a prova de tantas ligações quantas o modelo tem": a conta fica abaixo do total e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-06) | a prova cita uma view Blade, um stub ou um JS, e o trecho só existe dentro de `{{-- --}}`, `<!-- -->` ou `//`, que a guarda de PHP não reconhece | CT-09 | linha do `convites-recebidos.blade.php`: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-07) | o número de linha fica guardado fora do caminho: num campo do modelo, ou no `sources` do arquivo de diagrama | CT-09 | linhas do campo `linha` e do `sources` com `line`: reprova; o mutante aceita |

---

## Regra R10 — A prova é a da ligação: está no arquivo da origem, num trait ou concern que a origem usa, no provider do painel (descoberta, plugin ou configuração), no método de contrato do alvo ou, quando a origem é peça de pacote, num arquivo do kit que nomeia a origem; o trecho é o dessa ligação; e as duas pontas são peças

> `RQ-24`, `P-03`, `P-07` · perfil **padrão** · técnica: **EP** (forma da prova × arquivo) + controles em cópia

*(alterado em 2026-11-20: ADV-07 e ADV-08)* Fato medido pela sessão nos grafos da varredura do step 3: 687 de 748 ligações (92 %) têm a prova no arquivo da peça de origem. As demais ficam:
- num trait ou concern que a origem usa;
- no arquivo do alvo, quando o trecho é o método de contrato que o framework chama nele;
- ou em `vendor/`, que a P-07 proíbe; o modelo troca por um trecho do kit que liga ou configura a peça.

As formas válidas de a prova ser a da ligação são estas. Qualquer outro arquivo reprova, mesmo com um trecho que nomeia a peça usada.
- **Arquivo da origem, com o nome da peça usada.** O trecho nomeia a peça (a primeira linha dos Exemplos).
- **Trait ou concern que a origem usa.** O `Projeto` usa o `BelongsToTenant` (`app/Models/Projeto.php:BelongsToTenant:38`), e o trait liga ao `Tenant` (`app/Traits/BelongsToTenant.php:belongsTo:84`).
- **Provider do painel, nas ligações do painel.** São três formas.
  - *Descoberta por pasta.* É a regra nos três painéis (`app/Providers/Filament/AdminPanelProvider.php:discoverResources:147`). A prova de "o painel admin registra `UserResource`" não nomeia o recurso: é a linha de descoberta cuja pasta o contém.
  - *Registro do plugin que traz a peça* (Q9). A peça que um plugin traz só aparece no código versionado como o registro do plugin no provider. A prova de "o painel infra registra `HealthCheckResults`" é o `FilamentSpatieLaravelHealthPlugin::make()` (`app/Providers/Filament/InfraPanelProvider.php:FilamentSpatieLaravelHealthPlugin:345`), porque o `register()` desse plugin registra a página (`vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php:pages:36`).
  - *Configuração do painel* (ADV-08). O framework acrescenta peças a partir da configuração do painel: o alias `panel:{id}` sai do `->id()` (`app/Providers/Filament/AdminPanelProvider.php:id:77`), e o `IdentifyTenant` sai do `->tenant()` (`app/Providers/Filament/AppPanelProvider.php:tenant:660`). A prova é o trecho que configura, no provider desse painel.
- **Método de contrato do alvo.** Quando o framework chama um método no alvo, o trecho é esse método, no arquivo do alvo: o `canAccessPanel()` do `User` (`app/Models/User.php:canAccessPanel:174`). O método é um da lista fechada, abaixo dos Exemplos.
- **Arquivo do kit que liga a peça de pacote** (Q19, decidida — D26). Quando a origem é peça de pacote, a prova é o trecho do kit que liga a origem ao destino, num arquivo versionado fora de `vendor/`, em qualquer pasta.
  - O trecho nomeia a peça de destino, pela classe, pelo alias ou pela chave.
  - O arquivo nomeia a peça de origem, pela classe, pelo plugin ou pela chave de config do pacote.
  - `vendor/` e saída de comando (`artisan route:list`) nunca valem.
  - Exemplos do código de hoje: o `bind` do `LockerScreen` do pacote para a `TelaBloqueio` (`app/Providers/AppServiceProvider.php:bind:23`); o mapa das policies dos models de pacote (`app/Support/PoliciesDeVendor.php:ExceptionPolicy:67`); e o `Event::listen` dos eventos do `laravel/ai` para o `RegistrarAiRun` (`app/Providers/KitServiceProvider.php:listen:529`). O terceiro fica fora dos Exemplos do CT-10: as pontas dele são eventos, e evento não está na tabela de tipos da R8.
  - Medido pela sessão nas 288 ligações da varredura cuja origem não é classe do kit nem painel: provider de painel 116, sem prova 63, `vendor/` 57, outro provider 11, `database/` 11, `config/` 9, `routes/` 6, `app/Filament` 4, saída de `artisan` 4, `app/Support` 3, model 2, view 1 e stub 1. Aceitar só `config/` e provider deixaria vermelhas as ligações de `routes/` e de `app/Support`.

Sem a partição do plugin e a da configuração, toda ligação de registro que a R11 exige para peça de plugin, para `panel:{id}` e para `IdentifyTenant` ficaria vermelha. Sem a quinta forma, ficariam vermelhas as ligações cuja origem é peça de pacote.

```gherkin
  Regra: O trecho de cada prova é o da ligação que ela prova

    Esquema do Cenário: [CT-10] a prova é a da ligação: está num arquivo que a ligação permite, e o trecho é o dela
      Dado a ligação "<ligação>", com a prova em "<arquivo>" e o trecho "<trecho>"
      Quando a guarda do mapa confere se a prova é a dessa ligação
      Então o resultado é "<resultado>"

      Exemplos:
        | ligação                                                  | arquivo                                                | trecho                                                         | resultado                    | # partição                              |
        | `UserResource` usa o model `User`                        | `app/Filament/Admin/Resources/Users/UserResource.php`  | `protected static ?string $model = User::class;`               | aceita                       | arquivo da origem, nomeia a peça usada  |
        | `Projeto` usa o model `Tenant`                           | `app/Traits/BelongsToTenant.php`                       | `return $this->belongsTo(Tenant::class);`                      | aceita                       | trait que a origem usa                  |
        | o painel admin registra `UserResource`                   | `app/Providers/Filament/AdminPanelProvider.php`        | `->discoverResources(in: app_path('Filament/Admin/Resources')` | aceita                       | descoberta que alcança a pasta          |
        | o painel infra registra a página `HealthCheckResults`    | `app/Providers/Filament/InfraPanelProvider.php`        | `FilamentSpatieLaravelHealthPlugin::make()`                    | aceita                       | registro pelo plugin que traz a peça    |
        | o painel admin registra `panel:admin`                    | `app/Providers/Filament/AdminPanelProvider.php`        | `->id('admin')`                                                | aceita                       | configuração do painel                  |
        | o painel app registra `IdentifyTenant`                   | `app/Providers/Filament/AppPanelProvider.php`          | `->tenant(Tenant::class, slugAttribute: 'slug')`               | aceita                       | configuração do painel                  |
        | o painel admin usa o model `User`                        | `app/Models/User.php`                                  | `public function canAccessPanel(Panel $panel): bool`           | aceita                       | método de contrato do alvo              |
        | a página `LockerScreen` do pacote do lockscreen resolve para a `TelaBloqueio` | `app/Providers/AppServiceProvider.php` | `$this->app->bind(LockerScreen::class, TelaBloqueio::class);` | aceita | arquivo do kit que liga a peça de pacote |
        | o model `Exception` do `filament-exceptions` usa a `ExceptionPolicy` | `app/Support/PoliciesDeVendor.php` | `Exception::class                      => ExceptionPolicy::class,` | aceita | par de mapeamento: a linha inteira |
        | `UserResource` usa o model `User`                        | `app/Filament/Exports/UserExporter.php`                | `protected static ?string $model = User::class;`               | reprova, nomeando a ligação  | outro arquivo, que nomeia a peça        |
        | o painel infra registra o `UserResource` do `/admin`     | `app/Providers/Filament/InfraPanelProvider.php`        | `->discoverResources(in: app_path('Filament/Infra/Resources')` | reprova, nomeando a ligação  | descoberta que não alcança              |
        | o painel infra registra a página `HealthCheckResults`    | `app/Providers/Filament/InfraPanelProvider.php`        | `FilamentJobsMonitorPlugin::make()`                            | reprova, nomeando a ligação  | registro de plugin que não traz a peça  |
        | o painel admin registra `panel:admin`                    | `app/Providers/Filament/AppPanelProvider.php`          | `->id('app')`                                                  | reprova, nomeando a ligação  | configuração de outro painel            |
        | o painel admin registra `IdentifyTenant`                 | `app/Providers/Filament/AppPanelProvider.php`          | `->tenant(Tenant::class, slugAttribute: 'slug')`               | reprova, nomeando a ligação  | configuração de outro painel            |
        | `UserResource` usa o model `Tenant`                      | `app/Traits/BelongsToTenant.php`                       | `return $this->belongsTo(Tenant::class);`                      | reprova, nomeando a ligação  | trait que a origem não usa              |
        | o painel admin usa o model `Convite`                     | `app/Models/Convite.php`                               | `public function situacao(): string`                           | reprova, nomeando a ligação  | método fora da lista de contrato        |
        | o model `MailLog` do pacote usa a `ExceptionPolicy`      | `app/Support/PoliciesDeVendor.php`                     | `Exception::class                      => ExceptionPolicy::class,` | reprova, nomeando a ligação | par que não nomeia a origem |
        | o painel admin registra o plugin do Shield               | `app/Providers/Filament/AdminPanelProvider.php`        | `AuthDesignerPlugin::make()`                                   | reprova, nomeando a ligação  | prova de outra ligação                  |
        | a página `HealthCheckResults` do pacote do Health resolve para a `TelaBloqueio` | `app/Providers/AppServiceProvider.php` | `$this->app->bind(LockerScreen::class, TelaBloqueio::class);` | reprova, nomeando a ligação | arquivo que não nomeia a origem |
        | o plugin `FilamentSpatieLaravelHealthPlugin` traz a página `HealthCheckResults` | `vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php` | `protected string $page = HealthCheckResults::class;` | reprova, nomeando o arquivo | arquivo de `vendor/` |
        | a página `LockerScreen` do pacote do lockscreen resolve para a `TelaBloqueio` | a saída de `php artisan route:list` | a linha da rota da tela de bloqueio | reprova, nomeando a ligação | saída de comando |
        | `UserResource` usa o model `User`                        | `app/Filament/Admin/Resources/Users/UserResource.php`  | `class UserResource`                                           | reprova, nomeando a ligação  | nome só dentro de outro nome            |
        | `UserResource` usa `User`, numa cópia do modelo sem a peça `User` | `app/Filament/Admin/Resources/Users/UserResource.php` | `protected static ?string $model = User::class;`      | reprova, nomeando `User`     | ponta que não é peça                    |
```

Cada linha inválida tem um único defeito. Os trechos dela estão no arquivo, e a R9 os aceitaria, fora as linhas de `vendor/` e da saída de comando, que a R9 também reprova; a quinta forma as reprova por conta própria (D26). O `UserExporter` traz o mesmo trecho do `UserResource` (`app/Filament/Exports/UserExporter.php:$model:24`), e por isso a única diferença da linha dele é o arquivo.

*(alterado em 2026-11-20: ADV2-08 e ADV2-09)*
- **O trait vale só quando a origem o usa.** Medido: o `Projeto` usa o `BelongsToTenant`, e o `UserResource`, não.
- **O método de contrato é um da lista fechada,** a das interfaces de `vendor/filament/filament/src/Models/Contracts` no Filament instalado: `canAccessPanel` (`FilamentUser`), `canAccessTenant` e `getTenants` (`HasTenants`), `getDefaultTenant` (`HasDefaultTenant`), `getCurrentTenantLabel` (`HasCurrentTenantLabel`), `getFilamentName` (`HasName`) e `getFilamentAvatarUrl` (`HasAvatar`). Medido: 6 interfaces e 7 métodos; o `User` implementa `FilamentUser`, `HasAvatar` e `HasTenants`. O `situacao()` do `Convite` (`app/Models/Convite.php:situacao:619`) não está na lista.
- **Par de mapeamento** (D27). Quando o trecho é um par `X::class => Y::class`, como no `app/Support/PoliciesDeVendor.php`, ele é a linha inteira, com os espaços de alinhamento, e nomeia as duas pontas. Fora desse caso vale a D26. Conferido o literal: a linha 67, sem o recuo, é `Exception::class`, 22 espaços e `=> ExceptionPolicy::class,`, e a 68 mapeia o `MailLog`. Medido pela sessão nas 116 provas em provider de painel: a linha nomeia as duas pontas em só 3, e por isso a regra das duas pontas vale só para o par de mapeamento.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | prova copiada de outra ligação do mesmo arquivo | CT-10 | linha `AuthDesignerPlugin::make()`: reprova; o mutante aceita |
| M2 | qualquer linha de descoberta vale para qualquer recurso | CT-10 | linha da descoberta do `/infra`: reprova; o mutante aceita |
| M3 | "nomeia a peça" conferido por substring do nome curto | CT-10 | linha `class UserResource` para a peça `User`: reprova; o mutante aceita |
| M4 | ligação com ponta solta: a peça saiu do modelo, e a ligação ficou | CT-10 | linha da cópia sem a peça `User`: reprova nomeando `User`; o mutante aceita |
| M5 | qualquer `::make()` de plugin do mesmo provider vale como prova de peça de plugin | CT-10 | linha `FilamentJobsMonitorPlugin::make()` para `HealthCheckResults`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-07) | a prova sai de outro arquivo versionado cujo trecho nomeia a peça usada (o exportador, uma policy) | CT-10 | linha do `UserExporter`: reprova; o mutante aceita, porque só olha o destino |
| M7 — revisão adversarial (ADV-08) | a peça que o framework acrescenta pela configuração do painel fica dispensada de prova, ou aceita a configuração de outro painel | CT-10 | linhas do `->id('app')` e do `->tenant()` do `/app` como prova do `/admin`: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-07; D26) | na ligação cuja origem é peça de pacote, vale qualquer arquivo do kit cujo trecho nomeia o destino, mesmo sem nomear a origem | CT-10 | linha do `HealthCheckResults` com o `bind` do `AppServiceProvider`: reprova; o mutante aceita |
| M9 — revisão adversarial (ADV-07; D26) | a prova da ligação cuja origem é peça de pacote fica no `vendor/` dela, ou na saída de `artisan route:list` | CT-10 | linhas de `vendor/` e da saída de comando: reprova; o mutante aceita |
| M10 — revisão adversarial (ADV2-08) | a guarda aceita qualquer trait como prova, sem conferir se a origem o usa | CT-10 | linha "`UserResource` usa o model `Tenant`", com o `BelongsToTenant`: reprova; o mutante aceita |
| M11 — revisão adversarial (ADV2-08) | a guarda aceita qualquer método no arquivo do alvo, sem conferir se é de contrato | CT-10 | linha do `situacao()` do `Convite`: reprova; o mutante aceita |
| M12 — revisão adversarial (ADV2-09) | num arquivo de mapeamento, o trecho nomeia só o destino, e toda origem que o arquivo mapeia passa com qualquer policy dele | CT-10 | linha do `MailLog` com a linha do `Exception`: reprova; o mutante aceita |

---

## Regra R11 — Tudo que o painel registra tem ligação de registro no modelo, e o modelo não dá a um modo o que o painel não registra nele

> `P-05`, `RQ-08`, `RQ-10`, `RQ-11`, `P-03` · perfil **padrão** · técnica: **tabela de decisão** — 3 painéis × 2 modos × 5 tipos de registro (recurso, página, widget, plugin, middleware) = 30 células, cada uma nas duas direções. O CT-11 cobre as 15 do modo sem tenant, o CT-12 as 15 do modo com tenant, e o CT-13 controla cada direção.

A completude (painel → modelo) não se amarra ao modo. A peça registrada nos dois modos e aberta só num deles — como a tela de organizações do `/admin` — tem a ligação de registro no modelo. A disponibilidade dela (a marca e o diagrama de modo) está estacionada na Q11 *(alterado em 2026-11-20: ADV-31; era "é da R30")*. O modo só é afirmado na direção modelo → painel.

```gherkin
  Regra: O que o painel registra está no modelo, e o modelo não dá a um modo o que o painel não registra nele

    Esquema do Cenário: [CT-11] sem tenant, tudo que o painel registra está no modelo, e o modelo não dá a esse modo nada além disso
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o painel "<painel>" do registro de painéis, sem tenancy
      Quando a guarda do mapa compara o que o painel registra com as ligações de registro do modelo
      Então todo recurso, página, widget, plugin e middleware que o painel registra é destino de uma ligação de registro dele no modelo
      E toda peça que o modelo dá a ele no modo sem tenant é algo que ele registra
      E <registrado> está nos dois lados
      E <fora> não está no registro dele, e o modelo não o dá a ele no modo sem tenant

      Exemplos:
        | painel | registrado                                                                                                  | fora                                                                    |
        | admin  | o plugin `FilamentShieldPlugin`, o middleware `panel:admin` e o recurso `TenantResource`, registrado mesmo sem tenancy | `IdentifyTenant`, que `getTenantMiddleware()` devolve mesmo sem tenancy |
        | app    | o plugin `FilamentSearchSpotlightPlugin` e a página `TelaLogin`, que o painel liga pela configuração de login | `DefinirTenantDePermissoes` e `IdentifyTenant` |
        | infra  | o plugin `FilamentSpatieLaravelHealthPlugin` e a página `HealthCheckResults`, que ele registra               | `IdentifyTenant`                                                        |

    Esquema do Cenário: [CT-12] com tenant, tudo que o painel registra está no modelo, e o modelo não dá a esse modo nada além disso
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o painel "<painel>" do registro de painéis, com tenancy "<tenancy>"
      Quando a guarda do mapa compara o que o painel registra com as ligações de registro do modelo
      Então todo recurso, página, widget, plugin e middleware que o painel registra é destino de uma ligação de registro dele no modelo
      E toda peça que o modelo dá a ele no modo com tenant é algo que ele registra
      E <registrado> está nos dois lados
      E <fora> não está no registro dele, e o modelo não o dá a ele no modo com tenant

      Exemplos:
        | painel | tenancy | registrado                                                                    | fora                                                                                    |
        | app    | sim     | os middlewares de organização `IdentifyTenant` e `DefinirTenantDePermissoes` | o `UserResource` do `/admin`                                                            |
        | admin  | não     | o plugin `FilamentShieldPlugin` e o recurso `TenantResource`                  | `IdentifyTenant`: o modo está ligado, mas o painel não tem tenancy                      |
        | infra  | não     | o plugin `FilamentSpatieLaravelHealthPlugin` e a página `HealthCheckResults`  | `IdentifyTenant`: o modo está ligado, mas o painel não tem tenancy                      |

    Esquema do Cenário: [CT-13] a guarda do registro reprova o modelo que esquece peça, que sobra peça ou que troca o modo
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o modelo "<mundo>"
      Quando a guarda do mapa compara o registro dos três painéis com o modelo
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                         | resultado                                     | # direção          |
        | publicado                                                                                                     | aceita                                        | —                  |
        | cópia sem a ligação "o painel admin registra `FilamentShieldPlugin`"                                          | reprova, nomeando o plugin e o painel admin   | painel ⊄ modelo    |
        | cópia com a ligação "o painel admin registra `FilamentSpatieLaravelHealthPlugin`", que só o `/infra` registra  | reprova, nomeando o plugin e o painel admin   | modelo ⊄ painel    |
        | cópia que dá `DefinirTenantDePermissoes` ao painel app no modo sem tenant                                     | reprova, nomeando o middleware                | modo trocado       |
        | cópia sem a ligação "o painel app registra `TelaLogin`"                                                     | reprova, nomeando a `TelaLogin` e o painel app | painel ⊄ modelo    |
```

*(alterado em 2026-11-20: ADV2-10, D30)* O registro vivo inclui as páginas que o painel liga pela configuração, e não pelo `->pages()` (Setup Global). Medido com os getters do Filament, nos dois modos: `TelaLogin`, `TelaRecuperarSenha` e `TelaRedefinirSenha` nos três painéis, e `RegistroPorConvite` e `TelaVerificacaoDeEmail` no `/app`, estão fora do `getPages()`; com tenant, o kit não liga página de cadastro nem de perfil da organização (os dois getters devolvem nulo).

As âncoras das colunas "registrado" e "fora" vêm do código de hoje:
- `FilamentShieldPlugin::make()` em `app/Providers/Filament/AdminPanelProvider.php:FilamentShieldPlugin:240`;
- `FilamentSearchSpotlightPlugin::make()` em `app/Providers/Filament/AppPanelProvider.php:FilamentSearchSpotlightPlugin:264`;
- `FilamentSpatieLaravelHealthPlugin::make()` em `app/Providers/Filament/InfraPanelProvider.php:FilamentSpatieLaravelHealthPlugin:345`, que nenhum outro provider registra, e a página `HealthCheckResults`, que o plugin registra;
- o `tenantMiddleware` de `DefinirTenantDePermissoes` dentro de `kit.tenancy.enabled`, em `app/Providers/Filament/AppPanelProvider.php:DefinirTenantDePermissoes:670`;
- o `TenantResource`, descoberto nos dois modos e aberto só com tenant (`app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`).

Elas provam que cada suíte roda no seu modo e que cada guarda leu o registro, e não um conjunto vazio nem a disponibilidade.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o registro do modelo é uma lista escrita à mão que esquece um plugin, ou a página que um plugin registra | CT-13, CT-11 | CT-13, linha "sem a ligação do Shield": reprova nomeando o plugin. No publicado, o CT-11, linha "infra", reprova a falta de `HealthCheckResults`. O mutante aceita |
| M2 | peça a mais: um registro de outro painel, ou de peça que saiu | CT-13 | linha "admin registra o plugin do Health": reprova; o mutante aceita |
| M3 | um modo só: o modelo não tem o que só o modo com tenant registra | CT-12 | linha "app": os dois middlewares de organização estão no modelo; o mutante não os tem, e a guarda reprova |
| M4 | o middleware de organização é contado pela chave da instalação, ou sem `hasTenancy()`, e `IdentifyTenant` entra no registro de painel sem tenancy | CT-11, CT-12 | CT-11, linhas admin, app e infra, e CT-12, linhas admin e infra: `IdentifyTenant` fora dos dois lados. O mutante o põe no modelo, e a guarda mutante o conta no painel |
| M5 | a guarda filtra o lado do painel: deixa fora o alias `panel:{id}`, que não é classe, ou a peça que só abre com tenant (compara a disponibilidade, não o registro) | CT-11 | linha "admin": `panel:admin` e `TenantResource` nos dois lados; o modelo do mutante pode omiti-los, e a guarda mutante não reclama |
| M6 — revisão adversarial (ADV2-10) | o registro vivo usa só o `getPages()`, e as páginas que o painel liga pela configuração (login, cadastro, recuperação de senha, verificação de e-mail) ficam fora do modelo | CT-13, CT-11 | CT-13, linha "sem a ligação do `TelaLogin`": reprova; e, no publicado, o CT-11, linha "app", exige a `TelaLogin`; o mutante aceita |

---

## Regra R12 — Toda ligação do modelo está desenhada em diagrama publicado, direto ou por agrupamento que nomeia os membros num card do diagrama ou no rótulo dele; menção em texto não conta

> `P-10`, `RQ-08` · perfil **padrão** · técnica: **EP** (forma da aresta) + controles em cópia

*(alterado em 2026-11-20: ADV-15 e fatos de plataforma medidos pela sessão)* Nos mapas de arquitetura, a aresta tem três formas:
- **direta**, uma ligação do modelo;
- **de agrupamento**, uma caixa que nomeia os membros, e a aresta representa as ligações entre membros;
- **de porta**, uma caixa "→ outro mapa", que representa as ligações cujo outro lado está no mapa que a porta nomeia.

A P-10 conta como desenho a aresta direta e o agrupamento que nomeia os membros. Por isso:
- **O agrupamento nomeia os membros num card do diagrama ou no rótulo da caixa.** O card fica no painel lateral, sempre à vista; o rótulo aparece no zoom padrão. Nomear só no texto secundário da peça agrupadora não conta: é a etiqueta que o zoom esconde (M3).
- **A porta é um agrupamento cujos membros são as peças do outro lado.** Ela conta como desenho da ligação quando nomeia essas peças, num card ou no rótulo. A porta que só nomeia o mapa não desenha a ligação, e a ligação tem de estar desenhada noutro diagrama. Leitura fechada pela P-10 (texto literal): "direto, ou por um agrupamento que nomeia os seus membros".

```gherkin
  Regra: "Todas as ligações" vale para o conjunto dos diagramas publicados, e só aresta conta como desenho

    Esquema do Cenário: [CT-14] toda ligação do modelo aparece desenhada em diagrama publicado, e só aresta conta
      Dado o modelo de ligações e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa procura cada ligação nos diagramas que a página leva
      Então o resultado é "<resultado>"
      E, nos publicados, o total de ligações achadas é o total do modelo

      Exemplos:
        | mundo                                                                                                      | resultado                          | # partição                        |
        | publicados                                                                                                 | aceita                             | direta, ou por agrupamento ou porta que nomeia os membros |
        | cópia em que "o painel admin registra `FilamentShieldPlugin`" só aparece no texto da peça painel admin      | reprova, nomeando a ligação        | menção em texto                   |
        | cópia com um agrupamento dos recursos do `/admin` que não nomeia os membros                                 | reprova, nomeando o agrupamento    | agrupamento sem membros           |
        | cópia com um agrupamento dos recursos do `/admin` que nomeia os membros só no texto secundário da caixa      | reprova, nomeando o agrupamento    | membros só no texto que o zoom esconde |
        | cópia em que uma ligação só é representada por uma porta que nomeia o mapa, e não a peça do outro lado       | reprova, nomeando a ligação        | porta sem os membros              |
        | cópia sem essa ligação em diagrama nenhum                                                                   | reprova, nomeando a ligação        | ausente                           |
        | cópia em que essa ligação só está num diagrama que a página não leva                                        | reprova, nomeando a ligação        | diagrama órfão                    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | os diagramas são gerados de um subconjunto (só as ligações de registro) | CT-14 | linha "sem essa ligação" reprova; e, nos publicados, o total achado fica abaixo do total do modelo; o mutante aceita |
| M2 | agrupamento que não nomeia os membros conta como desenho | CT-14 | linha do agrupamento: reprova; o mutante aceita |
| M3 | a menção da ligação no texto da peça conta como desenho (o que some com o zoom) | CT-14 | linha "só no texto": reprova; o mutante aceita |
| M4 | a ligação só aparece num diagrama que a página não publica | CT-14 | linha "diagrama órfão": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-15) | o agrupamento nomeia os membros só no texto secundário da caixa, que o zoom esconde | CT-14 | linha "membros só no texto secundário": reprova; o mutante aceita |
| M6 | a porta conta como desenho das ligações do outro lado sem nomear as peças de lá | CT-14 | linha "porta sem os membros": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-17) | na conversão para o esquema do archify, o gerador perde as arestas, e o diagrama desenha só os nós | CT-B02 | "em cada `architecture`, o conjunto de pares origem → destino das arestas desenhadas é o da fonte": reprova; o mutante aceita |

*(alterado em 2026-11-20: ADV2-17; oráculo ajustado pela medição no commit `4adaf9a`)* O CT-B02 confere que as arestas que o DOM desenha são as da fonte. No diagrama escrito à mão, a fonte é a lista de ligações do arquivo. No construído (o campo `construtor`), o arquivo só diz o recorte e o layout, e a fonte é o modelo: cada ligação do modelo no painel e no modo do diagrama vira o par das pontas desenhadas — a peça, ou o agrupamento que a nomeia —, e a ligação cuja ponta não está desenhada, ou cujas duas pontas caem na mesma caixa, não vira aresta. Medido no esqueleto (`ae5d20f`): `admin-acesso`, 76 pares no arquivo e 76 no DOM; `app-resumo-sem`, 14 e 14; `app-resumo-com`, 43 e 43. No commit `4adaf9a`, contra o modelo: `app-acesso`, 17 e 17; `app-organizacoes`, 14 e 14; `app-resumo-sem` e `app-resumo-com`, 16 e 16. O DOM tem mais elementos de aresta que pares (62 elementos e 43 pares no `app-resumo-com` do esqueleto), e por isso a conta é de pares únicos, lidos em `svg [data-edge-from][data-edge-to]`.

---

## Regra R13 — Toda ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros — traz a prova, com o arquivo e o trecho, num item próprio de card do painel lateral do próprio diagrama, inteiro e à vista sem foco; o comparativo fica fora

> `P-04`, `P-07`, `RQ-08` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído · cenário: **CT-B02, no `05`** (costura browser, G4)

O archify só tem prova por nó (`sources`, de 1 a 3) e nenhuma por aresta. Por isso a prova de cada ligação no artefato publicado é responsabilidade do gerador, e só o HTML construído a mostra. O Gherkin e o roteiro estão no `05`. A tabela abaixo é a mesma do CT-B02, mais o M3, que morre na suíte do kit.

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)*
- **Onde fica a prova.** Os cards ficam no painel lateral do diagrama, sempre presentes, sem depender de foco. A prova de cada ligação fica num card do próprio diagrama, agrupado pela peça de origem; esse arranjo é decisão de desenho, e o oráculo é só "num card do painel lateral, à vista".
- **O card não tem link.** O archify escreve o item do card como texto escapado, num `<li>` (a função `renderCards`, em `renderers/shared/utils.mjs` do archify, no commit fixado). O link de evidência que o leitor clica é o do passaporte da peça (R14). Por isso a P-07 aqui é a do M3: a prova não guarda número de linha.

*(alterado em 2026-11-20: ADV2-20, ADV2-22 e ADV2-32)*
- **O comparativo fica fora** da conferência de cards: pela P-04 emendada, a casca do archify não leva cards nem passaporte. A `base` e a `cabeca` dele são diagramas do mapa, publicados ao lado com os cards (R20). Medido no HTML do comparativo, nos dois idiomas: 43 pares de arestas (62 elementos de aresta no DOM) e nenhum card.
- **Cada item amarrado à ligação.** O gerador escreve um item por ligação, "origem → destino (rótulo): arquivo:linha — trecho", no card da peça de origem (`site/mapa/gerar.mjs`, função `cardsDeProva`). O CT-B02 lê o item que nomeia a ligação e confere o arquivo e o trecho dela; e o total de itens de prova lidos no DOM, por diagrama, é o de ligações desenhadas, contado do lado do DOM.
- **Como se reconhece o item de prova** *(alterado em 2026-11-20: medido no commit `4adaf9a`)*. O painel lateral tem outros cards — no mapa construído, "Como ler as setas" e "Quem está em …", com os membros de cada agrupamento —, e eles ficam fora da conta. O item de prova é o que tem a forma "origem → destino (verbo): arquivo:linha — trecho". O total é por ligação do modelo, e não por par de arestas: o agrupamento desenha várias ligações numa aresta só. Medido: nos 6 diagramas construídos, 281 itens de prova por idioma, igual às ligações desenhadas (75 no `app-resumo-sem`, sobre 16 pares; 77 no `app-resumo-com`, sobre 16), e nenhum diagrama repete nome de peça, que é o que o item nomeia.
- **O item aparece inteiro.** Medido no Chromium: o item de card do archify quebra a linha (`overflow-wrap: anywhere`), e um item sintético de 160 caracteres sem espaço ficou com `scrollWidth` e `clientWidth` iguais, de 346 px; os 25 itens de card do esqueleto também não transbordam, nem os 562 itens de prova dos construídos no commit `4adaf9a`. O CT-B02 afirma `scrollWidth <= clientWidth + 1` e o trecho inteiro do modelo, sem reticências que o modelo não tenha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o gerador perde a prova das ligações desenhadas por agrupamento ou por porta que nomeia os membros *(alterado em 2026-11-20: a porta entrou com os fatos de plataforma)* | CT-B02 | "uma por ligação": a ligação do membro sem prova reprova; o mutante aceita |
| M2 | o card traz só o arquivo, sem o trecho *(reescrito em 2026-11-20: era "link de prova para `blob/main`", que passou à R14 como M5, porque o card do archify é texto, sem link)* | CT-B02 | "um item de prova que a nomeia, … com o arquivo e o trecho do modelo": o item sem o trecho reprova; o mutante aceita |
| M3 | número de linha guardado no modelo, que envelhece a cada edição do arquivo *(alterado em 2026-11-20: ADV-19, o matador passou do CT-B02 para a suíte do kit)* | CT-09 | linha da prova com número de linha, na suíte do kit, que roda em todo PR: reprova; o mutante aceita |
| M4 | o card mostra a prova de outra ligação (índice trocado, ou provas trocadas entre os cards de duas origens) *(alterado em 2026-11-20: ADV2-22, a asserção antiga contava o conjunto de provas em qualquer card)* | CT-B02 | "um item de prova que a nomeia, 'origem → destino', com o arquivo e o trecho do modelo": o item que nomeia a ligação traz a prova de outra, e reprova; o mutante aceita |
| M5 | a prova só fica num documento à parte, nunca no diagrama | CT-B02 | "o total de itens de prova lidos no DOM é o de ligações desenhadas, maior que zero em todo diagrama que desenha ligação": dá zero e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-25) | o card da prova fica fora do painel lateral: oculto, ou só aparece quando o leitor foca a peça | CT-B02 | "num card do painel lateral, à vista", com o diagrama aberto sem focar peça nenhuma: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-22) | o conferidor conta as provas do lado esperado, e a ligação de membro sem item próprio passa, porque outra ligação desenhada tem a mesma prova | CT-B02 | "o total de itens de prova lidos no DOM é o de ligações desenhadas": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-32) | o item de card corta o trecho longo, com reticências que o modelo não tem | CT-B02 | "o item mostra o trecho inteiro do modelo, e não é cortado (`scrollWidth <= clientWidth + 1`)": reprova; o mutante aceita |

---

## Regra R14 — O leitor acha a peça pela busca, e o passaporte dela mostra as provas da própria peça, com o link para o repositório do kit no commit do build

> `RQ-07`, `P-04`, `P-07` · perfil **padrão** · técnica: **EP** (idioma) · cenário: **CT-B03, no `05`** (costura browser, G4)

A interação que o `00` pede (RQ-07) é provada pela busca e pelo passaporte com as provas (P-04, P-07). Rota entre nós, lente e exportação são recursos do archify e ficam em Cogitado e cortado. A tabela abaixo é a mesma do CT-B03.

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)* Ao focar uma peça, o passaporte do archify mostra:
- o rótulo e o tipo da peça;
- "origem verificada {repositório} @ {commit}";
- as provas da própria peça (`sources`, até três), como links `https://github.com/{dono}/{repo}/blob/{commit}/{arquivo}#L{linha}`;
- só as contagens de ligações, "N de saída · M de entrada", e o alcance.

Ele não lista cada ligação com a prova dela: essa prova está no card (R13). Por isso o CT-B03 deixou de afirmar a prova da ligação no detalhe.

O oráculo do link é o da P-07: o repositório do `support.source` do `composer.json`, o commit do build e um arquivo que existe nesse commit.

*(alterado em 2026-11-20: ADV2-23 e ADV2-24)*
- **A amostra.** Em cada `architecture`, a primeira peça de pacote que o arquivo desenha, quando houver, e a primeira do kit; e, no conjunto dos `architecture`, ao menos uma de pacote. Medido no esqueleto: o `admin-acesso` tem 4 peças de pacote em 41, e o `app-resumo-sem` e o `app-resumo-com` não têm nenhuma; exigir uma de pacote em cada `architecture` reprovaria esses dois. No commit `4adaf9a`: o `app-acesso` tem 5 em 21, e o `app-organizacoes`, o `app-resumo-sem` e o `app-resumo-com`, nenhuma. A peça de pacote é a que o modelo dá como de pacote (a origem `pacote`); no desenho, é a do tipo `cloud`, "Pacote ou plugin de terceiros" na legenda.
- **A linha.** A prova de peça é âncora `{arquivo, trecho}` (R9), e o gerador acha a linha na revisão publicada. A linha do `#L` contém, no commit do build, o trecho da âncora. Medido no HTML construído do esqueleto (revisão `ae5d20f`): 226 links de prova de peça em 4 diagramas, e a linha de todos contém o trecho. No commit `4adaf9a`, também todos: 21, 9, 49, 47, 1 e 1 nos 6 construídos, e 73, 31 e 26 no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`.
- **Sem erro na interação.** Com a busca aberta e o passaporte aberto, nenhuma exceção não capturada e nenhum erro de console. Medido no Chromium: nenhum erro no esqueleto com a busca aberta e uma peça em foco; no commit `4adaf9a`, nenhum no `app-acesso` (a peça de pacote `AuthDesignerPlugin`, achada pela busca, com o passaporte e um link para `AdminPanelProvider.php#L200` no commit) e no `app-organizacoes`, nos dois idiomas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama é exportado como imagem estática, sem busca nem passaporte | CT-B03 | "o leitor busca a peça e a foca": não há busca e reprova; o mutante aceita |
| M2 | o nó sai sem provas, e o passaporte não mostra a origem verificada *(reescrito em 2026-11-20: era "o detalhe não traz a prova da ligação", que o passaporte do archify não mostra por desenho; ADV2-23 estendeu a amostra)* | CT-B03 | "cada peça da amostra mostra de uma a três provas": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-25) | o link do passaporte leva a outro repositório, como um fork ou um nome errado, com o mesmo commit e caminho *(reescrito em 2026-11-20: era "o detalhe do agrupamento não nomeia os membros", que passou à R12 como M5)* | CT-B03 | "leva ao repositório do `support.source`": reprova; o mutante aceita |
| M4 | a prova da peça de pacote aponta para `vendor/`, fora do commit, e o link dá 404 | CT-B03 | "a um arquivo que existe nesse commit": reprova; o mutante aceita |
| M5 | link de evidência para `blob/main`, um ramo que anda *(veio da R13, onde era o M2)* | CT-B03 | "no commit do build": o `href` não traz o commit e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV2-23) | a linha do `#L` sai fixa em 1, ou envelhece, e o link leva a outra linha | CT-B03 | "a linha do `#L` contém o trecho da âncora, no commit do build": reprova; o mutante aceita |

---

## Regra R15 — A página de cada idioma leva, por `<a href>` relativo, a todo diagrama do seu idioma, e só a diagrama que existe

> `RQ-03`, `P-02` · perfil **padrão** · técnica: **EP** (idioma)

A Q15 foi decidida antes da derivação. A página linka cada diagrama com `<a href>` relativo cru dentro do Markdown, e não com link Markdown. O `[CT-22]` e o `[CT-45]` herdados só leem link Markdown e não mudam. O `site/verifica-links.mjs` confere o alvo no `dist/`.

```gherkin
  Regra: A página do mapa e os arquivos de diagrama se espelham, idioma a idioma

    Esquema do Cenário: [CT-15] a página de cada idioma leva, por `<a href>` relativo, a todo diagrama do seu idioma, e só a diagrama que existe
      Dado os arquivos de diagrama do mapa
      E a página do mapa em "<idioma>"
      Quando a guarda do mapa lê os `<a href>` da página que apontam para o mapa
      Então o conjunto de diagramas que eles levam é o conjunto de arquivos de diagrama, nenhum a mais e nenhum a menos
      E todo `<a href>` é relativo e leva ao HTML do diagrama em `/mapa/<idioma>/`, e nenhum ao do outro idioma

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | diagrama órfão: o arquivo existe e a página não o leva | CT-15 | "nenhum a menos": reprova; o mutante aceita |
| M2 | link morto: a página leva a um diagrama que não existe | CT-15 | "nenhum a mais": reprova; o mutante aceita |
| M3 | a página en leva aos HTML pt (link copiado da página pt) | CT-15 | linha "en", "nenhum ao do outro idioma": reprova; o mutante aceita |
| M4 | `href` absoluto (`/mapa/pt/x.html`), que no Pages perde a base `/filament-starter-kit-easy` | CT-15 | "todo `<a href>` é relativo": reprova; o mutante aceita |
| M5 | link Markdown no lugar do `<a href>` (o `[CT-22]` herdado o acusaria como morto) | CT-15 | a guarda lê zero `<a href>`, e "nenhum a menos" reprova; o mutante aceita |

---

## Regra R16 — Todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido; o título só fica igual quando é o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código

> `P-02`, `RQ-03` · perfil **padrão** · técnica: **EP** + controles em cópia

*(alterado em 2026-11-20: ADV-10, ADV2-11 e ADV2-28)* O texto en é traduzido por quatro critérios, e nunca por "diferente do pt":
- **Termo do glossário ou nome de estado.** Quando o texto pt é um termo do glossário — os 39 pares "termo (en: …)" de `wikis/glossario.md` —, o en é o termo en do glossário, sem diferença de maiúscula. Vale para o rótulo de nó que não é peça: o estado do `lifecycle`, a caixa de agrupamento e a de porta, o marco.
- **Frase.** O texto en de duas palavras ou mais não traz palavra funcional do pt — de, do, da, para, com, não ou uma — como palavra: não colada a letra, dígito, `_`, `.`, `/` ou `-`. Vale para título, descrição, passo, rótulo de ligação e rótulo de nó que não é peça.
- **Nome de código e rótulo de peça.** Ficam isentos: o rótulo de peça é o nome dela, igual nos dois idiomas de propósito, e o token único (classe, método, caminho, comando, chave) é código.
- **O título igual nos dois idiomas.** Só passa quando é exatamente o nome de uma peça do modelo ou de um comando `kit:*` (`kit:install`), e o arquivo o marca como nome de código. Antes, o exemplo dava também `2FA`, que essa restrição recusa. A marca num título que não é nome de peça nem de comando reprova.

Medido nas traduções do esqueleto (827 pares pt → en):
- 373 pares têm o en igual ao pt, 77 deles com espaço, e todos são código ou texto já em inglês (`22 migrations`, `CHANGELOG v0.56.0`, `/infra/logs · LogsExplorer`). "Diferente do pt" reprovaria todos.
- Dos 482 valores en de duas palavras ou mais, nenhum traz palavra funcional do pt; o mesmo detector acha alguma em 199 dos 827 textos pt. No commit `4adaf9a`, também nenhum: 403 textos en de duas palavras ou mais nos 17 arquivos de diagrama, 14 no gerador e 9 nas sobrescritas de tipo. "Do" também é palavra inglesa ("do not"); nenhum texto en do commit `4adaf9a` a traz, e, se um texto en legítimo vier a trazê-la, a lista da ADV2-28 é decisão da sessão.
- Dos 4 rótulos do esqueleto que são termo do glossário, 2 batem com o en dele (Pendente → Pending; Excluída → Deleted) e 2 não: Inativa → Off (o glossário dá Inactive) e Telas de acesso → Access screens (o glossário dá sign-in screens). São defeitos do esqueleto, que a entrega corrige.

```gherkin
  Regra: O site vale nos dois idiomas, e o que o kit escreve em cada diagrama também

    Esquema do Cenário: [CT-16] todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido, salvo o título que é nome de peça ou de comando, marcado
      Dado os arquivos de diagrama do mapa "<mundo>"
      Quando a guarda do mapa confere os textos que o kit escreveu em cada diagrama
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                              | resultado                                                                                                                              | # partição               |
        | publicados                                                                         | aceita: todo título, rótulo, descrição e passo tem pt e en não vazios; o termo do glossário usa o en dele; o texto en de duas palavras ou mais não traz palavra funcional do pt; e o título igual nos dois idiomas é exatamente o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código | válida |
        | cópia em que um rótulo não tem o texto en                                          | reprova, nomeando o diagrama e o rótulo                                                                                                | texto en ausente         |
        | cópia em que o título en é igual ao pt, sem a marca de nome de código              | reprova, nomeando o diagrama                                                                                                           | título copiado           |
        | cópia em que o título `kit:install` é igual nos dois idiomas, marcado como nome de código | aceita                                                                                                                          | nome de comando          |
        | cópia em que o título "Painel admin — sem organização" é igual nos dois idiomas, marcado como nome de código | reprova, nomeando o diagrama e o título                                                                        | marca de fachada         |
        | cópia em que a descrição en de um passo é a frase pt "Gerando senha do admin"         | reprova, nomeando o diagrama e o passo | descrição copiada |
        | cópia do `lifecycle` do convite em que o estado Pendente tem o rótulo en "Pendente" | reprova, nomeando o diagrama e o estado | termo do glossário sem o en dele |
        | cópia em que o passo en é a frase pt com uma palavra trocada, "Generating senha do admin" | reprova, nomeando o diagrama e o passo | frase com palavra do pt |
        | cópia em que um diagrama de fluxo só tem os textos pt                              | reprova, nomeando o diagrama                                                                                                           | diagrama de um idioma só |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o texto en que falta cai no pt, sem aviso | CT-16 | linha "rótulo sem en": reprova; o mutante aceita |
| M2 | título en copiado do pt | CT-16 | linha "título igual, sem a marca": reprova; o mutante aceita |
| M3 | diagrama de fluxo escrito só em pt | CT-16 | linha "só pt": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-10) | o título copiado do pt ganha a marca de nome de código para passar | CT-16 | linha "Painel admin — sem organização", marcado: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-10) | a descrição ou o passo en é a frase pt copiada | CT-16 | linha "descrição en é a frase pt": a frase traz "do", e reprova; o mutante aceita, porque só exige en não vazio |
| M6 — revisão adversarial (ADV2-11) | o rótulo en do estado ou da caixa que não é peça sai igual ao pt ("Pendente") | CT-16 | linha do estado Pendente: o glossário dá Pending, e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-28) | a frase en é a pt com uma palavra trocada, e passa no "diferente do pt" | CT-16 | linha "Generating senha do admin": traz "do", e reprova; o mutante aceita |

---

## Regra R17 — A grade painel × modo é inteira: cada painel do registro tem diagrama próprio em cada modo, o compartilhado vem marcado e só ele, e a célula não leva a diagrama do outro modo nem de outro painel

> `RQ-10`, `RQ-11`, `P-06`, `P-09` · perfil **padrão** · técnica: **tabela de decisão** (3 painéis × 2 modos = 6 células por idioma, 12 nos dois) + controles em cópia

```gherkin
  Regra: Cada painel do kit tem a sua visualização, sem e com tenant, e a página mostra a grade inteira

    Esquema do Cenário: [CT-17] cada painel do registro tem diagrama próprio em cada modo, e o compartilhado vem marcado
      Dado os painéis do registro de painéis
      E a grade painel × modo da página do mapa em "<idioma>"
      Quando a guarda do mapa confere a célula do painel "<painel>" no modo "<modo>"
      Então a célula leva a ao menos um diagrama desse painel que vale nesse modo
      E todo diagrama da célula que vale nos dois modos está também na outra célula do painel, marcado como compartilhado
      E nenhum diagrama da célula é de outro painel
      E nenhum diagrama da célula vale só no outro modo
      E todo diagrama marcado como compartilhado vale nos dois modos

      Exemplos:
        | idioma | painel | modo       |
        | pt     | admin  | sem tenant |
        | pt     | admin  | com tenant |
        | pt     | app    | sem tenant |
        | pt     | app    | com tenant |
        | pt     | infra  | sem tenant |
        | pt     | infra  | com tenant |
        | en     | admin  | sem tenant |
        | en     | admin  | com tenant |
        | en     | app    | sem tenant |
        | en     | app    | com tenant |
        | en     | infra  | sem tenant |
        | en     | infra  | com tenant |

    Cenário: [CT-18] um painel novo no registro, sem diagrama, deixa a grade incompleta
      Dado um quarto painel, "financeiro", registrado no caso
      Quando a guarda do mapa confere a grade da página contra o registro de painéis
      Então ela reprova, nomeando o painel "financeiro" nos dois modos
      E os três painéis do kit continuam conferidos

    Esquema do Cenário: [CT-43] a guarda da grade reprova a célula que leva a diagrama do outro modo ou de outro painel
      Dado a grade painel × modo da página do mapa "<mundo>"
      Quando a guarda do mapa confere cada célula
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                          | resultado                                   | # partição                    |
        | publicada, pt e en                                                                                             | aceita                                      | válida                        |
        | cópia em que a célula app × com tenant leva também ao diagrama de arquitetura do app que só vale sem tenant      | reprova, nomeando a célula e o diagrama     | diagrama do outro modo        |
        | cópia em que a célula infra × sem tenant leva ao diagrama de arquitetura do admin                              | reprova, nomeando a célula e o diagrama     | diagrama de outro painel      |
        | cópia em que a célula app × com tenant marca como compartilhado o diagrama de arquitetura que só vale com tenant | reprova, nomeando a célula e o diagrama | compartilhado de um modo só |
```

*(alterado em 2026-11-20: ADV-26)* O CT-17 pede "ao menos um" diagrama válido na célula. Sem a linha "vale só no outro modo", a célula com tenant podia levar também ao diagrama que só vale sem tenant. O CT-43 dá a cópia que mata esse defeito, e também a do diagrama de outro painel, que antes só tinha o mundo publicado.

*(alterado em 2026-11-20: ADV2-12)* A marca de compartilhado vale nas duas direções: o diagrama que vale nos dois modos está marcado nas duas células, e o diagrama marcado vale nos dois. O modo de cada diagrama é o do campo `modos` do arquivo. Medido no esqueleto: os diagramas de um modo só, `app-resumo-sem` e `app-resumo-com`, têm um modo em `modos`, e os demais, os dois.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um painel é esquecido na grade (o `/infra`) | CT-17 | linhas "infra": "a célula leva a ao menos um diagrama" reprova; o mutante aceita |
| M2 | a lista de painéis da grade é escrita à mão | CT-18 | "reprova, nomeando o painel financeiro": o mutante aceita, porque não lê o registro |
| M3 | a célula leva a diagrama de outro painel | CT-17, CT-43 | CT-17, "nenhum diagrama da célula é de outro painel", e CT-43, linha "infra leva ao admin": reprova; o mutante aceita |
| M4 | o diagrama dos dois modos aparece numa célula só, ou sem a marca | CT-17 | "está também na outra célula, marcado como compartilhado": reprova; o mutante aceita |
| M5 | só o modo sem tenant tem diagramas | CT-17 | linhas "com tenant": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-26) | a célula com tenant leva também ao diagrama que só vale sem tenant | CT-43 | linha "app × com tenant leva ao diagrama que só vale sem tenant": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-12) | um diagrama que só vale num modo vem marcado como compartilhado na célula | CT-43, CT-17 | CT-43, linha "marca como compartilhado o diagrama que só vale com tenant": reprova; e, no publicado, o CT-17 exige que todo marcado valha nos dois modos; o mutante aceita |

---

## Regra R18 — Só se compartilha o que é igual nos dois modos, o que é igual não se duplica, e toda ligação desenhada vale no modo do diagrama

> `P-09`, `RQ-11` · perfil **padrão** · técnica: **tabela de decisão** (igual nos dois modos × publicado uma vez) + controles em cópia

As linhas usam `DefinirTenantDePermissoes`, que o `/app` só registra com tenant. Assim o oráculo vem do registro (CT-11 e CT-12), e não da disponibilidade. Se a disponibilidade por modo conta como diferença de estrutura é recorte aberto (Q11).

*(alterado em 2026-11-20: ADV2-13)* Toda ligação desenhada tem, nos modos dela, o modo do diagrama. A ligação "o painel app usa o model `Tenant`" vem do `->tenant()` dentro do bloco `kit.tenancy.enabled` (`app/Providers/Filament/AppPanelProvider.php:tenant:660`), e só vale no modo com tenant. Medido no esqueleto: o `app-resumo-sem` não desenha peça nem ligação do modo com tenant, e o `app-resumo-com` desenha 5 ligações do `IdentifyTenant` e do `DefinirTenantDePermissoes`.

*(alterado em 2026-11-20: medido no modelo do commit `4adaf9a`)* O modelo dá "o painel app usa o model `Tenant`" aos dois modos, e o "o painel app registra `IdentifyTenant`", com o mesmo trecho, só ao modo com tenant. O trecho só roda com tenant, e por isso o defeito é do modelo, e não do oráculo: a linha de controle do CT-19 parte do código e reprova enquanto o modelo der a ligação ao modo sem tenant. Nenhuma ligação do modelo do commit `4adaf9a` vale só com tenant entre peças dos dois modos no `/app`. O modo das demais ligações de uso não tem guarda: o registro vivo (R11) dá o modo só das ligações de registro, e a P-05 não promete a varredura de uso inteira. É lacuna declarada (R18-M5), e o ponto foi ao coordenador.

*(alterado em 2026-11-20: fato da implementação, dado pelo coordenador, e medição no commit `4cc9517`)* A pilha da requisição saiu em dois diagramas `sequence`: o `requisicao-autenticada`, comum, com os três painéis e os dois modos, e o `requisicao-autenticada-com`, só do `/app` e só com tenant, com a camada da organização. O comum não desenha o `IdentifyTenant` nem o `DefinirTenantDePermissoes`: só os cita num card que leva ao `-com`, e menção em texto não é desenho (R12). O `-com` os desenha, o segundo com o rótulo abreviado "DefinirTenant". A guarda reconhece a peça do fluxo pelo nome exato, e o rótulo abreviado de uma peça de um modo só, desenhado no fluxo comum, passaria: é lacuna declarada (R18-M6).

```gherkin
  Regra: Ganha um diagrama por modo só o que muda de estrutura

    Esquema do Cenário: [CT-19] compartilha-se só o que é igual nos dois modos, e o igual não se duplica
      Dado o modelo, com o modo de cada peça e de cada ligação, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere o modo de cada diagrama
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                                  | resultado                               | # partição                         |
        | publicados                                                                                                             | aceita                                  | válida                             |
        | cópia em que o diagrama compartilhado do painel app desenha `DefinirTenantDePermissoes`, que só existe com tenant       | reprova, nomeando o diagrama e a peça   | compartilhado esconde a diferença  |
        | cópia com dois diagramas do painel app, do mesmo tipo, um por modo, que desenham as mesmas peças e ligações             | reprova, nomeando os dois diagramas     | igual duplicado                    |
        | cópia em que o diagrama do painel app sem tenant desenha `DefinirTenantDePermissoes`                                   | reprova, nomeando o diagrama e a peça   | peça do modo errado                |
        | cópia em que o diagrama de arquitetura do painel app sem tenant desenha "o painel app usa o model `Tenant`", que o código só faz com tenant (o `->tenant()` no bloco `kit.tenancy.enabled`) | reprova, nomeando o diagrama e a ligação | ligação do modo errado |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama compartilhado esconde a diferença de modo | CT-19 | linha "compartilhado desenha `DefinirTenantDePermissoes`": reprova; o mutante aceita |
| M2 | o que é igual é publicado duas vezes, uma por modo | CT-19 | linha "dois iguais": reprova; o mutante aceita |
| M3 | uma peça de um modo é desenhada no diagrama do outro | CT-19 | linha "sem tenant desenha `DefinirTenantDePermissoes`": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV2-13) | o diagrama sem tenant desenha uma ligação que o modelo dá só ao modo com tenant, entre peças que existem nos dois modos | CT-19 | linha "o painel app usa o model `Tenant`" no diagrama sem tenant: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV2-13) | o modelo dá aos dois modos uma ligação de uso cujo trecho só roda com tenant | ⚠️ **sem matador**: nenhuma guarda deriva do código o modo da ligação de uso; o registro vivo (R11) dá o modo só das ligações de registro, e a P-05 não promete a varredura de uso inteira. Medido no modelo do commit `4adaf9a`: "o painel app usa o model `Tenant`", com o trecho `->tenant(Tenant::class, slugAttribute: 'slug')` no bloco `kit.tenancy.enabled`, está nos dois modos. A linha de controle do CT-19 acusa esse caso, e só ele; o resto fica para o quality gate | — (lacuna declarada) |
| M6 — revisão adversarial (ADV2-13) | o fluxo comum aos dois modos desenha, com o rótulo abreviado, uma peça que só existe com tenant ("DefinirTenant") | ⚠️ **sem matador**: no fluxo escrito à mão, a guarda só reconhece a peça do modelo pelo nome exato, e o rótulo abreviado não é peça para ela. Medido no commit `4cc9517`: o `requisicao-autenticada-com` desenha o `DefinirTenantDePermissoes` como "DefinirTenant", e 61 dos 99 nós dos 14 fluxos de painel não têm nome de peça. Fica para o quality gate | — (lacuna declarada) |

---

## Regra R19 — Cada um dos dez tipos e o comparativo têm uso ou motivo, cada combinação tipo × painel × modo tem diagrama ou motivo traduzido, e só é do kit inteiro o diagrama que não desenha peça registrada por painel

> `RQ-06`, `RQ-25`, `P-06`, `P-02` · perfil **padrão** · técnica: **produto cartesiano fechado**: (10 tipos × 3 painéis × 2 modos) + (comparativo × 3 painéis) = **63 células por idioma**, 126 nos dois. Cada célula é resolvida pelo CT-21. No nível do tipo, os 11 tipos são resolvidos pelo CT-20

```gherkin
  Regra: Nenhum tipo disponível fica sem uso sem um motivo escrito

    Esquema do Cenário: [CT-20] cada um dos dez tipos nativos e o comparativo de arquitetura têm diagrama publicado ou o motivo escrito
      Dado os arquivos de diagrama do mapa e a página do mapa em pt e em en
      Quando a guarda do mapa procura o tipo "<tipo>"
      Então há diagrama publicado desse tipo, ou a página traz, nos dois idiomas, o motivo de não haver

      Exemplos:
        | tipo                                           |
        | architecture                                   |
        | workflow                                       |
        | sequence                                       |
        | dataflow                                       |
        | lifecycle                                      |
        | erd                                            |
        | class                                          |
        | tree                                           |
        | timeline                                       |
        | waterfall                                      |
        | comparativo de arquitetura (sem e com tenant)  |

    Esquema do Cenário: [CT-21] toda combinação tipo × painel × modo tem diagrama ou motivo ao lado, e só do conjunto fechado de tipos
      Dado as 63 células de cada idioma na página "<mundo>"
      Quando a guarda do mapa confere cada célula
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                          | resultado                                                                                                                  | # partição             |
        | publicada, pt e en | aceita: todo diagrama a que a célula leva é daquele tipo e daquele painel e vale naquele modo, ou a célula traz o motivo; o motivo da página en é traduzido (critério da R16); e o diagrama do kit inteiro (`paineis` vazio) não desenha peça que painel registra | válida |
        | cópia com a célula `waterfall` × admin × com tenant sem diagrama e sem motivo                  | reprova, nomeando a célula                                                                                                 | célula vazia           |
        | cópia com o motivo "—" na célula comparativo × infra                                           | reprova, nomeando a célula                                                                                                 | motivo de enfeite      |
        | cópia com o motivo "não se aplica", sem porquê, na célula `erd` × app × com tenant             | reprova, nomeando a célula                                                                                                 | motivo de enfeite      |
        | cópia da página en em que o motivo da célula comparativo × infra é o texto pt "o resumo do painel é igual nos dois modos" | reprova, nomeando a célula | motivo en copiado do pt |
        | cópia da página en em que o motivo de uma célula é a frase pt com uma palavra trocada, "no flow de dados in this panel" | reprova, nomeando a célula | motivo en com palavra do pt |
        | cópia em que um `workflow` que desenha peças que o `/app` registra tem `paineis` vazio e resolve as células `workflow` × app com o motivo "do kit inteiro" | reprova, nomeando o diagrama | fluxo de painel declarado do kit inteiro |
        | cópia em que a célula `sequence` × admin × sem tenant leva a um diagrama `workflow`            | reprova, nomeando a célula e o tipo                                                                                        | link na célula errada  |
        | cópia em que a célula `sequence` × admin × sem tenant leva ao `sequence` do app                | reprova, nomeando a célula                                                                                                 | link de outro painel   |
        | cópia com o tipo "mindmap" na página                                                           | reprova, nomeando o tipo                                                                                                   | tipo fora do conjunto  |
```

Um tipo do kit inteiro (P-06: linha do tempo das versões, tempos, modelo de dados e de classes) resolve as células de painel com o motivo "do kit inteiro" e o link para o diagrama dele.

*(alterado em 2026-11-20: ADV-10 e ADV-26)* O motivo da célula na página en é diferente do da mesma célula na página pt. A linha "leva ao `sequence` do app" dá à metade "outro painel" do M4 a cópia que faltava.

*(alterado em 2026-11-20: ADV2-14, D28, e ADV2-28)*
- **Do kit inteiro.** Vale só para o diagrama que não desenha peça registrada por painel: versões, tempos, modelo de dados e de classes, e os comandos `kit:*` (P-06, D28). O fluxo transversal — login, 2FA, login social, recuperação de senha, bloqueio de sessão, pilha de middleware — desenha peça que todo painel registra e é diagrama dos painéis que ele desenha: `paineis[]` substitui `painel?`, e o diagrama aparece, compartilhado, na célula de cada um. Medido no esqueleto: os 6 diagramas com `paineis` vazio (lifecycle, erd, waterfall, workflow do `kit:install`, timeline e class) não desenham peça registrada; o `conta` cita o `UserResource` só como âncora de um estado; e o `requisicao-autenticada` (sequence) tem os três painéis em `paineis`.
- *(alterado em 2026-11-20: medido no commit `4cc9517`)* **Desenhar a peça** é ter um nó com o id do modelo dela ou com o nome dela, exato. O marco da linha do tempo que cita a feature numa frase não desenha a peça: o título do marco 0.33.0, "Dashboard dinâmico nos três painéis", traz `Dashboard`, nome de peça registrada, e conferir por palavra reprovaria dado correto. Medido: 22 diagramas com `paineis` vazio, e nenhum nó com o nome exato de uma das 112 peças que painel registra. A pilha da requisição é de painel: o `requisicao-autenticada` tem os três painéis e os dois modos, e o `requisicao-autenticada-com`, só o `/app` e só com tenant.
- *(alterado em 2026-11-20: medido na página do commit `e7d6eb5`)* **As células do comparativo.** A página repete o comparativo nas duas colunas de modo de cada painel (66 células por idioma, e não 63); as duas valem pela mesma combinação comparativo × painel, e a guarda as lê assim. O motivo real das células do `/admin` e do `/infra` é "o resumo do painel é igual nos dois modos" ("the panel summary is the same in both modes"), e as linhas de controle do motivo usam essas células. Na mesma página, as células da árvore de registro e da arquitetura do `/admin` e do `/infra` trazem "—", e o CT-21 as reprova até a entrega pôr a árvore compartilhada de cada um e o motivo da arquitetura.
- **O motivo en é traduzido** pelo critério da R16: não traz palavra funcional do pt, e nunca "diferente do pt".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | tipo sem uso e sem motivo (o `waterfall` esquecido) | CT-20 | linha `waterfall`: reprova; o mutante aceita |
| M2 | célula sem diagrama e sem motivo | CT-21 | linha "célula vazia": reprova; o mutante aceita |
| M3 | motivo de enfeite ("—", "não se aplica") | CT-21 | linhas "—" e "não se aplica": reprova; o mutante aceita |
| M4 | link na célula de outro tipo ou de outro painel | CT-21 | linhas "`sequence` leva a `workflow`" e "`sequence` leva ao `sequence` do app": reprova; o mutante aceita |
| M5 | a página traz um tipo que o archify não tem | CT-21 | linha "mindmap": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-10) | o motivo da célula na página en é o texto pt copiado | CT-21 | linha "motivo en copiado do pt": o texto pt traz "do", e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-14) | os fluxos do `/app` são declarados do kit inteiro, e as células de fluxo × painel se resolvem com o motivo "do kit inteiro" | CT-21 | linha do `workflow` com `paineis` vazio: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-28) | o motivo en é a frase pt com uma palavra trocada, e passa no "diferente do pt" | CT-21 | linha "no flow de dados in this panel": traz "de", e reprova; o mutante aceita |

---

## Regra R20 — Todo comparativo publicado vai do modo sem tenant (antes) para o com tenant (depois) do mesmo painel

> `RQ-25`, `RQ-11`, `P-04` · perfil **padrão** · técnica: **EP** + controles em cópia

```gherkin
  Regra: O comparativo de arquitetura compara o mesmo painel, de sem tenant para com tenant

    Esquema do Cenário: [CT-22] todo comparativo publicado vai do modo sem tenant para o com tenant do mesmo painel
      Dado os comparativos de arquitetura do mapa "<mundo>"
      Quando a guarda do mapa confere o par de cada comparativo
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                     | resultado                                                                                                             |
        | publicados | aceita: o "antes" é o diagrama de arquitetura do painel sem tenant, e o "depois" é o do mesmo painel com tenant, os dois arquivos de diagrama do mapa, publicados ao lado com a prova de cada ligação (P-04) |
        | cópia com o comparativo do painel app indo de "com tenant" para "sem"     | reprova, nomeando o comparativo                                                                                       |
        | cópia com o comparativo indo de "admin sem tenant" para "app com tenant"  | reprova, nomeando o comparativo                                                                                       |
        | cópia com o comparativo cuja `base` não é arquivo de diagrama do mapa | reprova, nomeando o comparativo |
```

O comparativo é opcional pela R19: ele pode ser trocado por um motivo. Esta regra vale para todo comparativo que for publicado.

*(alterado em 2026-11-20: ADV2-20, P-04 emendada)* O comparativo é a exceção da P-04: a casca do archify não leva cards nem passaporte. Ele só é publicado quando a `base` e a `cabeca` são diagramas do mapa, publicados ao lado com a prova de cada ligação. Medido no esqueleto: o `app-resumo-sem-com` tem `base` `app-resumo-sem` e `cabeca` `app-resumo-com`, os dois publicados; o HTML dele, nos dois idiomas, tem `lang="en"`, nenhum card e nenhuma requisição para fora.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | antes e depois trocados: o delta mostra a organização como removida | CT-22 | linha "de com para sem": reprova; o mutante aceita |
| M2 | painéis misturados no par | CT-22 | linha "admin sem → app com": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV2-20) | o comparativo compara arquivos que não são diagramas do mapa publicados, e o leitor fica sem a prova das ligações dele | CT-22 | linha "a `base` não é arquivo de diagrama do mapa": reprova; o mutante aceita |

---

## Regra R21 — Nenhuma dependência nova de visualização no site: o `site/package.json` fica com as dependências da base nos quatro campos, `site/mapa/` só tem os arquivos do mapa, e o HTML de cada diagrama não carrega recurso de fora dele

> `RQ-25` · perfil **padrão** · técnica: **EP** (lista fechada: os quatro campos de dependência e os arquivos de `site/mapa/`) + controles em cópia + rastreio no site construído (CT-B01, no `05`)

```gherkin
  Regra: Nenhuma dependência nova de visualização além do archify

    Esquema do Cenário: [CT-23] o site não ganha dependência de visualização além do archify, nem pelo manifesto nem por arquivo em `site/mapa/`
      Dado as dependências de `site/package.json` da base: `@astrojs/starlight`, `astro`, `astro-mermaid`, `mermaid` e `sharp` para execução; `axe-core`, `playwright` e `wait-on` para desenvolvimento
      E o `site/package.json` e a pasta `site/mapa/` "<mundo>"
      Quando a guarda do mapa lê os quatro campos de dependência e a lista de arquivos de `site/mapa/`
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo | resultado | # partição |
        | publicados | aceita: as de execução e as de desenvolvimento são exatamente as da base; `optionalDependencies` e `peerDependencies` não existem ou estão vazias; e `site/mapa/` só tem o download do archify, o gerador, a interface, o posicionador, os dois arquivos do modelo (o de peças e o de ligações) e a pasta de diagramas | válida |
        | cópia com `d3` nas de execução | reprova, nomeando a dependência | fora da lista fechada |
        | cópia com `@antv/g6` nas de desenvolvimento | reprova, nomeando a dependência | fora da lista fechada |
        | cópia com `chart.js` em `peerDependencies` | reprova, nomeando a dependência | campo que a guarda não lia |
        | cópia de `site/mapa/` com `chart.min.js` | reprova, nomeando o arquivo | arquivo fora da lista |
```

O `package.json` da raiz já está congelado pelo `[CT-12]` herdado de `tests/Kit/SiteDeDocumentacaoTest.php`.

*(alterado em 2026-11-20: ADV-14)*
- **Lista fechada.** As dependências de desenvolvimento eram conferidas contra uma lista de recusa, e passava qualquer nome fora dela (`@antv/g6`, `sigma`, `vega`). Agora são lista fechada, como as de execução.
- **O canal que o manifesto não vê.** A biblioteca pode entrar por CDN ou por arquivo copiado para `site/public/`. Isso só aparece no HTML construído, então quem confere é o CT-B01, no `05`.
  - Nenhuma requisição sai da origem da prévia, fora `data:` e `blob:`.
  - O documento não tem `<script src>` nem `<link rel="stylesheet">`.
  - O HTML do archify no commit fixado é autocontido: o `assets/template.html` e os exemplos não trazem nenhum dos dois. A sessão mediu zero requisição externa num HTML gerado.

*(alterado em 2026-11-20: ADV2-15, D32)* A biblioteca de visualização também entra copiada para `site/mapa/` e embutida no HTML como `<script>` inline, sem requisição e sem `<script src>`, ou num campo de dependência que a guarda não lia. Por isso a guarda lê os quatro campos (`dependencies`, `devDependencies`, `optionalDependencies` e `peerDependencies`) e fecha a lista de `site/mapa/`: o download (`archify.mjs`), o gerador (`gerar.mjs`), a interface (`interface.json`), o posicionador (`posicionar.mjs`), os dois arquivos do modelo (`pecas.json` e `ligacoes.json`) e a pasta `diagramas/`. Medido: o `site/package.json` não tem `optionalDependencies` nem `peerDependencies`, e as outras duas são as da base. No esqueleto, `site/mapa/` tinha `archify.mjs`, `diagramas/`, `gerar.mjs` e `interface.json`; no commit `4adaf9a`, tem também `ligacoes.json`, `pecas.json` e `posicionar.mjs`. A D32 lista o download, o gerador, a interface, o posicionador e a pasta de diagramas, e não nomeia o modelo, que o plano põe na mesma pasta; sem os dois arquivos do modelo na lista, a linha "publicados" reprovaria o commit `4adaf9a`, e a divergência foi ao coordenador. O `archify check` não serve de oráculo: medido pela sessão, ele só acusa alteração do HTML com o recibo `.delivery.json`, que o `render` não grava.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `d3` ou `cytoscape` nas dependências de execução, para desenhar o que o archify não desenha | CT-23 | linha `d3`: reprova; o mutante aceita |
| M2 | `@mermaid-js/mermaid-cli` nas de desenvolvimento, para pré-renderizar | CT-23 | linha "publicados": as de desenvolvimento são exatamente as da base, e o mutante não é; reprova; o mutante aceita |
| M3 | uma biblioteca PHP de grafo entra no `composer.json` para gerar o modelo | ⚠️ **sem matador**: os pacotes PHP do kit mudam toda semana, e uma lista congelada ficaria vermelha em toda adição legítima (é o precedente do `[CT-12]` de `tests/Kit/SiteDeDocumentacaoTest.php`). O gate confere no diff | — (lacuna declarada) |
| M4 — revisão adversarial (ADV-14) | a biblioteca nova entra nas de desenvolvimento com um nome fora da lista de recusa antiga (`@antv/g6`) | CT-23 | linha `@antv/g6`: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-14) | o HTML do diagrama carrega a biblioteca por CDN, ou de um arquivo copiado para `site/public/` | CT-B01 | "nenhuma requisição sai da origem da prévia, e o documento não tem `<script src>` nem `<link rel="stylesheet">`": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV2-15) | a biblioteca entra em `optionalDependencies` ou em `peerDependencies`, que a guarda não lia | CT-23 | linha `chart.js` em `peerDependencies`: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-15) | o gerador copia a biblioteca para `site/mapa/` e a embute no HTML como `<script>` inline | CT-23 | linha `chart.min.js` em `site/mapa/`: reprova; o mutante aceita |

---

## Regra R22 — Há fluxo publicado, e, nos tipos `workflow`, `sequence` e `dataflow`, todo passo percorre peça do modelo

> `RQ-09` (parcial; recorte aberto em Q6), `P-03` · perfil **padrão** · técnica: **EP** (tipo de fluxo) + controles em cópia

```gherkin
  Regra: A demonstração exibe fluxos do kit, e cada passo deles é peça que existe

    Esquema do Cenário: [CT-24] o mapa publica fluxos, e todo passo deles percorre peça do modelo
      Dado os arquivos de diagrama do mapa "<mundo>"
      Quando a guarda do mapa lê os diagramas de fluxo dos tipos `workflow`, `sequence` e `dataflow`
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                       | resultado                                                                                                   |
        | publicados                                                  | aceita: há ao menos um diagrama de fluxo, de um dos quatro tipos de fluxo, e todo passo desses três tipos nomeia peça do modelo |
        | cópia em que um passo de fluxo nomeia `ConviteService`      | reprova, nomeando o passo e o diagrama                                                                      |
        | cópia sem nenhum diagrama de fluxo                          | reprova: nenhum fluxo publicado                                                                             |
```

*(alterado em 2026-11-20: ADV-11 e ADV-32)*
- **Os tipos se separam.** O `lifecycle` não nomeia peça: os estados não estão entre os 13 tipos da R8. Por isso o CT-24 confere os passos só dos outros três tipos de fluxo. O estado e a transição do `lifecycle` têm âncora no código (R35).
- **A âncora vem da P-03.** Pelo glossário, fluxo não é ligação, e a RQ-24 não pede prova em cada seta. Mas a P-03 vale para todo fluxo publicado: cada transição afirma algo sobre o código e precisa de uma guarda que falhe quando o código deixar de bater. Essa guarda é a âncora da R35, e não depende da lista de fluxos (Q6).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o mapa publica só arquitetura, sem fluxo | CT-24 | linha "sem nenhum diagrama de fluxo" reprova; a linha "publicados" exige ao menos um; o mutante aceita |
| M2 | passo de fluxo escrito de memória, nomeando peça que não existe | CT-24 | linha `ConviteService`: reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-32) | dois passos seguidos, ancorados no mesmo método, saem na ordem trocada *(alterado em 2026-11-20: era "a ordem diverge do código", que não tinha matador; a ordem entre arquivos ou métodos diferentes ficou na R35, como M4)* | CT-39 | linha "duas transições seguidas, ancoradas no mesmo método, trocadas de ordem": reprova; o mutante aceita |

---

## Regra R23 — O mapa nasce no build do commit que publica, os dois fluxos de CI o conferem com o conferidor do mapa antes de enviar, e a falha do conferidor reprova o job

> `P-07`, `RQ-03`, `RQ-17`, `P-11` · perfil **padrão** · técnica: **EP por workflow** + controles em cópia

```gherkin
  Regra: O que o mapa afirma corresponde à revisão publicada, e é conferido antes de sair

    Esquema do Cenário: [CT-25] o mapa nasce no build do workflow, e o conferidor do mapa roda depois do de acessibilidade, com a mesma prévia, antes de o artefato sair
      Dado o workflow "<workflow>"
      Quando a guarda do mapa lê os passos dele e o `site/package.json`
      Então o passo de build roda o build do site, e o build do site gera os diagramas do mapa
      E o conferidor do mapa, `site/verifica-mapa.mjs`, roda depois do conferidor de acessibilidade, com a mesma prévia de pé, <e antes>
      E nenhum HTML de diagrama do mapa está versionado no repositório

      Exemplos:
        | workflow                                    | e antes                         |
        | `.github/workflows/pages.yml`               | e antes do envio do artefato, e o job `build` só roda na `main` (`if: github.ref == 'refs/heads/main'`) |
        | o job `site` de `.github/workflows/ci.yml`  | (o job não envia artefato)      |

    Esquema do Cenário: [CT-42] a falha do conferidor do mapa reprova o job, e o build do Pages só roda na `main`
      Dado o workflow "<workflow>" "<mundo>"
      Quando a guarda do mapa lê o passo que roda o conferidor do mapa
      Então o resultado é "<resultado>"

      Exemplos:
        | workflow                                    | mundo                                                                  | resultado                                                    |
        | `.github/workflows/pages.yml`               | publicado                                                              | aceita: o passo não tem `continue-on-error`, e o comando não engole a falha |
        | o job `site` de `.github/workflows/ci.yml`  | publicado                                                              | aceita (idem)                                                |
        | `.github/workflows/pages.yml`               | cópia em que o passo do conferidor do mapa tem `continue-on-error: true` | reprova, nomeando o passo                                  |
        | o job `site` de `.github/workflows/ci.yml`  | cópia em que o passo roda `node verifica-mapa.mjs \|\| true`             | reprova, nomeando o passo                                    |
        | `.github/workflows/pages.yml`               | cópia em que o job `build` não tem a condição `github.ref == 'refs/heads/main'` | reprova, nomeando o job |
```

Molde: `[CT-40]` e `[CT-42]` de `tests/Kit/SiteDeDocumentacaoTest.php` (passos do workflow por posição).

*(alterado em 2026-11-20: ADV-13)* Sem o CT-42, o conferidor rodava no lugar certo e não reprovava nada, e o artefato saía com o mapa reprovado. "Engolir a falha" é o `|| true` ou qualquer `||` depois do comando do conferidor.

*(alterado em 2026-11-20: ADV2-16 e ADV2-31, D31)* O CT-42 lê o YAML; quem prova que a reprovação do conferidor vira código de saída é o CT-B06, no `05`, com o conferidor rodado sobre um HTML do mapa quebrado. Medido no commit `4adaf9a`: o `site/verifica-mapa.mjs` grava `process.exitCode = reprovado ? 1 : 0`; rodado numa pasta de rascunho sobre uma cópia em que o HTML do `app-resumo-sem` não desenha nada, ele saiu com código 1 ("nenhum `<svg role="img">` em 15000 ms", nas quatro entradas da amostra), e sobre a cópia com o HTML gerado, com 0. O job `build` do `pages.yml` só roda na `main` (medido: `if: github.ref == 'refs/heads/main'`): um dispatch de outro ramo publicaria links para um commit que some com o ramo (Q9).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o HTML do mapa é gerado na máquina do mantenedor e versionado: envelhece e não é o do commit | CT-25 | "nenhum HTML de diagrama do mapa está versionado": reprova; o mutante aceita |
| M2 | o workflow chama `astro build` direto e pula a geração do mapa | CT-25 | "o build do site gera os diagramas do mapa": reprova; o mutante aceita |
| M3 | o conferidor do mapa roda depois do envio do artefato, ou antes de a prévia subir | CT-25 | linha `pages.yml`: "depois do conferidor de acessibilidade, com a mesma prévia, e antes do envio" reprova; o mutante aceita |
| M4 | o workflow roda só o conferidor de acessibilidade, e o do mapa nunca roda | CT-25 | "o conferidor do mapa roda": reprova nos dois workflows; o mutante aceita |
| M5 — revisão adversarial (ADV-13) | o passo do conferidor do mapa entra sem poder reprovar (`continue-on-error: true`, ou `\|\| true`) | CT-42 | linhas do `continue-on-error` e do `\|\| true`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV2-16) | o conferidor registra a reprovação no log e termina com código 0, e o job passa | CT-B06 | linha do controle de processo: o conferidor, sobre um HTML do mapa que não desenha peça nenhuma, termina com código diferente de zero; o mutante termina com 0 |
| M7 — revisão adversarial (ADV2-31) | o build do Pages roda a partir de outro ramo, por dispatch, e os links de prova apontam para um commit que some com o ramo | CT-42 | linha "o job `build` sem a condição da `main`": reprova; o mutante aceita |

---

## Regra R24 — Todo diagrama publicado abre desenhado, com as peças que a fonte dá a ele e a interface do visualizador montada, sem erro de console nem exceção, no idioma da página

> `RQ-07`, `RQ-03`, `P-02` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído · cenário: **CT-B01, no `05`** (costura browser, G4)

*(alterado em 2026-11-20: ADV-31, ADV-28 e ADV-29)*
- **A condição saiu.** A metade de condição do CT-B01 (o passo 4) e o M5 estão estacionados na Q11, com o ID reservado. A RQ-11 saiu da origem desta regra: o que restou não afirma modo.
- **O oráculo é o rótulo de cada peça.** O CT-B01 confere, pelo id, que cada peça desenhada tem o rótulo que a fonte dá a ela, e não só a contagem. Os pares id e rótulo pegam também a troca de rótulos entre peças, que um conjunto de nomes não pega.
- **A escuta.** Ela pega também a exceção não capturada.
- **O CT-B01 serve também à R21.** Nenhum recurso de fora entra no HTML; o mutante dela, R21-M5, está na tabela da R21.
- *(alterado em 2026-11-20: ADV2-20, ADV2-24 e ADV2-32)* **O comparativo também é aberto,** para a escuta de erro e a de recurso de fora (P-04 emendada); os cards e o passaporte dele ficam fora (CT-B02 e CT-B03).
- **A exceção da interação** — a busca e o passaporte — passa a ser pega no CT-B03 e no CT-B04, que abrem os dois; o CT-B01 só abre o diagrama.
- **O rótulo mostrado é o da fonte, inteiro.** O oráculo é a igualdade, pelo id, com o rótulo da fonte no idioma da página, e não "sem reticências": medido, o esqueleto escreve "…" à mão em 33 rótulos e 29 sublabels para caber na caixa, e o archify recusa no render o rótulo mais largo que a caixa nos tipos `architecture`, `dataflow`, `sequence` e `workflow` ("Label … is wider than component"). Medido no Chromium: o `[data-node-id]` sem escopo pega também o resultado da busca, um botão por peça (82 elementos para 41 peças no `admin-acesso` do esqueleto); dentro do SVG, há um elemento por peça (21 no `app-acesso` e 52 no `app-registro-sem` do commit `4adaf9a`). Por isso o seletor é `svg [data-node-id]`, e a conta é pelo conjunto de ids. *(corrigido em 2026-11-20: a leitura antiga, "dois elementos por peça no DOM", contava o resultado da busca)*
- **A F-nn no desenho da peça** (D29) e o mutante dela, R6-M10, estão na R6.
- *(alterado em 2026-11-20: medido no commit `4adaf9a`)* **O título mostrado é o `<h1>`.** Ele é igual ao título do arquivo no idioma da página. O `document.title` é a moldura do archify, a chave `page.title`: "Diagrama {título}" em pt e "{title} Diagram" em en. O comparativo não tem `<h1>` com o título dele: a casca mostra o subtítulo "base → cabeça", com os títulos dos dois diagramas, e fica fora do título, das peças e dos rótulos.
- **O rótulo de cada peça é o da fonte.** No diagrama escrito à mão, é o campo de rótulo do tipo no esquema do archify: `label` em `architecture`, `workflow`, `sequence`, `dataflow`, `lifecycle`, `erd`, `tree` e `class`; `title` em `timeline`; `name` em `waterfall`. No construído, é o nome da peça no modelo, e o das caixas de agrupamento e de porta, o do arquivo. Medido: rótulo igual, pelo id, em todas as peças dos 9 diagramas aceitos no commit `4adaf9a` fora o comparativo, nos dois idiomas.
- **O desenho já vem no HTML.** O archify grava o SVG pronto: sem JavaScript, o `app-acesso` tem as 21 peças e as 17 arestas. O que só existe com o script rodando é a interface do visualizador — a busca, o nível de leitura, os textos de interface, o passaporte. O CT-B01 confere que ela montou: a busca tem um resultado por peça desenhada, pelo id. Medido com JavaScript no `app-acesso`, no `app-registro-sem`, no `app-resumo-sem`, no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`: 21 de 21, 52 de 52, 9 de 9, 39 de 39, 12 de 12 e 26 de 26; sem JavaScript, nenhum resultado. Os seletores são `svg [data-node-id]`, porque o resultado da busca também traz `data-node-id`.

A tabela abaixo é a mesma do CT-B01.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o conferidor do mapa lista os diagramas varrendo o `dist/` por `index.html`, como o de acessibilidade (`site/verifica-acessibilidade.mjs:'index.html':78`), e não acha nenhum HTML do mapa | CT-B01 | "o total de diagramas abertos é o número de arquivos vezes dois": dá zero e reprova; o mutante aceita |
| M2 | o gerador grava o HTML sem declarar UTF-8, e os acentos se trocam | CT-B01 | "o `<h1>` é o título do arquivo no idioma da página, com os acentos intactos": reprova; o mutante aceita |
| M3 | o HTML en sai com os textos pt | CT-B01 | idioma en, "o `<h1>` é o título do arquivo no idioma da página": reprova; o mutante aceita |
| M4 | o script do visualizador não entra no HTML, ou quebra antes de montar a interface: o SVG estático aparece, sem busca nem nível de leitura *(reescrito em 2026-11-20: medido no commit `4adaf9a`, o SVG vem pronto no HTML, e "dá zero peça" não matava o mutante)* | CT-B01 | "a busca tem um resultado por peça desenhada, pelo id": dá zero resultado e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-28) | o desenho tem o mesmo número de peças, com os rótulos trocados entre elas | CT-B01 | "toda peça que a fonte dá a ele está desenhada e tem, pelo id, o rótulo que a fonte dá a ela no idioma da página": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-29) | o script do visualizador lança exceção não capturada na busca ou no detalhe, e o conferidor só escuta o console *(alterado em 2026-11-20: ADV2-24, o matador passou do CT-B01, que não abre a busca, ao CT-B03 e ao CT-B04)* | CT-B03, CT-B04 | "com a busca aberta e o passaporte aberto, nenhuma exceção não capturada e nenhum erro de console": reprova; o mutante aceita |

---

## Regra R25 — O README de cada idioma leva ao mapa do mesmo idioma

> `RQ-02`, `P-02` · perfil **mínimo** · técnica: **EP** (idioma)

```gherkin
  Regra: O README ganha o lugar que leva à demonstração, nos dois idiomas

    Esquema do Cenário: [CT-26] o README de cada idioma leva ao mapa do mesmo idioma
      Dado o "<readme>"
      Quando a guarda do mapa procura nele o link para o mapa interativo
      Então há um link para a página do mapa em "<idioma>" no site do kit
      E nenhum link para a página do mapa do outro idioma

      Exemplos:
        | readme         | idioma |
        | `README.md`    | pt     |
        | `README.en.md` | en     |
```

O link vale com a URL do site (`…/<idioma>/referencia/mapa-interativo/`) ou com o stub `.html`, que os links atuais do README usam. A resolução do link fica com o `[CT-14]` herdado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | só o README pt ganha o lugar | CT-26 | linha `README.en.md`: não há link, reprova; o mutante aceita |
| M2 | o README en leva ao mapa pt | CT-26 | linha `README.en.md`, "nenhum link para a página do outro idioma": reprova; o mutante aceita |

---

## Regra R26 — Os diagramas Mermaid da base continuam onde estavam

> `P-01`, `RQ-25` · perfil **mínimo** · técnica: **EP** (inventário da base)

```gherkin
  Regra: A demonstração nova se soma aos diagramas Mermaid, sem substituí-los

    Cenário: [CT-27] todo bloco Mermaid da base continua no arquivo em que estava, nos dois idiomas
      Dado o inventário Mermaid da base: DG-01 a DG-20 por idioma, cada um no seu arquivo de `docs/`, e o DG-01 também no README de cada idioma
      Quando a guarda do mapa extrai os blocos Mermaid do README e das páginas de cada idioma
      Então cada DG da base está no mesmo arquivo da base, fora de comentário HTML
      E entre eles estão o DG-11, no catálogo de features, e o DG-19, em "Desenvolvendo o kit": duas páginas que esta entrega reescreve
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o DG-11 some na reescrita do catálogo | CT-27 | "o DG-11 no catálogo de features": reprova; o mutante aceita (o `[CT-01]` herdado de `DiagramasDaArquiteturaTest` também mata) |
| M2 | o bloco do README é trocado por uma imagem ou por um link do mapa | CT-27 | o DG-01 sai do README: reprova; o mutante aceita |
| M3 | DG-01, DG-02, DG-03 e DG-13 vão da página de diagramas para a página do mapa | CT-27 | "no mesmo arquivo da base": reprova; o mutante aceita |

---

## Regra R27 — Todo pacote instalado está na referência de pacotes e numa F-nn de origem pacote, ou na lista dos sem feature própria, com o motivo; e todo pacote que a referência lista está instalado

> `RQ-19`, `RQ-04`, `P-03` · perfil **padrão** · técnica: **EP** por partição de pacote + controles em cópia *(regra nova em 2026-11-20: Q7; alterada na terceira rodada: Q16 e Q17)*

A Q7 e a Q16 foram fechadas pelo `00`: a RQ-19 diz "pacotes instalados", e instalado é o que os manifestos declaram. São eles:
- o `composer.json`, em `require` e `require-dev`;
- o `package.json` da aplicação, em `dependencies`, `devDependencies` e `optionalDependencies`.

A referência de pacotes (`docs/{pt,en}/referencia/pacotes-instalados.md`) é o meio de mostrá-los. Ela conta nas seções de produção, na `## Desenvolvimento (require-dev)` e na `## Front-end (package.json)`, mas não na `## Avaliados e não adotados`. Na base ela está defasada em quatro pacotes, e a entrega a corrige (Setup Global).

```gherkin
  Regra: Os pacotes instalados chegam à referência e ao catálogo, e a referência só lista pacote instalado

    @premissa
    Esquema do Cenário: [CT-28] todo pacote instalado está na referência e chega ao catálogo, ou à lista dos sem feature própria; e a referência só lista pacote instalado
      Dado os pacotes que os manifestos declaram e a referência de pacotes em "<idioma>" "<mundo>", fora a seção dos avaliados e não adotados
      Quando a guarda do mapa confere os dois conjuntos
      Então o resultado é "<resultado>"
      E, na publicada, a guarda conferiu ao menos os 91 pacotes dos manifestos da base, inclusive o `@laravel/multiplex`, de `optionalDependencies`

      Exemplos:
        | idioma | mundo                                                                                                  | resultado                                                                                                                                                                                                                                                             | # partição                  |
        | pt     | publicada                                                                                              | aceita: todo pacote dos manifestos está na referência e é nomeado por ao menos uma F-nn de origem pacote, ou pacote com customização própria, ou está na lista da guarda dos pacotes sem feature própria, com o motivo; e todo pacote da referência está nos manifestos | válida                      |
        | en     | publicada                                                                                              | aceita (idem)                                                                                                                                                                                                                                                         | válida                      |
        | pt     | cópia da referência sem a linha de `hoceineel/filament-quick-action-dock`, que os manifestos declaram  | reprova, nomeando o pacote                                                                                                                                                                                                                                            | instalado e não listado     |
        | pt     | cópia que lista `vendor-inexistente/plugin`, que não está instalado                                    | reprova, nomeando o pacote                                                                                                                                                                                                                                            | listado e não instalado     |
        | pt     | cópia do catálogo sem a F-nn que nomeia `shuvroroy/filament-spatie-laravel-health`                     | reprova, nomeando o pacote                                                                                                                                                                                                                                            | pacote sem F-nn             |
        | pt     | cópia da lista da guarda com `laravel/tinker`, sem o motivo                                            | reprova, nomeando o pacote                                                                                                                                                                                                                                            | motivo ausente              |
        | pt     | cópia da lista da guarda com `laravel/tinker`, com o motivo "—"                                        | reprova, nomeando o pacote                                                                                                                                                                                                                                            | motivo de enfeite           |
        | pt     | cópia da lista da guarda com `laravel/tinker`, com o motivo "não se aplica", sem porquê                | reprova, nomeando o pacote                                                                                                                                                                                                                                            | motivo de enfeite           |
        | pt     | cópia da lista da guarda com `shuvroroy/filament-spatie-laravel-health`, com motivo, cujo plugin o `/infra` registra | reprova, nomeando o pacote                                                                                                                                                                                                                                | pacote com peça registrada na lista |
```

`@premissa` de mecanismo, decidida pela sessão (Q17):
- a linha do catálogo nomeia o pacote pelo nome do Composer ou do npm, o mesmo do link da referência;
- a lista dos pacotes sem feature própria mora na guarda, com o motivo de cada um, como a lista das páginas (Q14b).

O pacote vem do endereço do link: o texto `pest-plugin-laravel` leva a `pestphp/pest-plugin-laravel`. O `php` é requisito de plataforma, não pacote, e fica fora da conta.

*(alterado em 2026-11-20: ADV-04 e ADV-22)*
- **Pacote com peça registrada não vai para a lista.** O pacote cuja classe um painel registra nunca entra na lista dos sem feature própria: a classe do plugin, do recurso, da página ou do widget está em `vendor/{pacote}/`, e o registro dos painéis está de pé. Essa linha lê o registro, o que a aplicação de pé do arnês do kit permite.
- **Motivo de enfeite** tem a definição do Setup Global.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um pacote sai do `composer.json`, e a referência e a F-nn que o cita ficam | CT-28 | linha `vendor-inexistente/plugin`: reprova; o mutante aceita |
| M2 | pacote instalado fora da referência (o do dock, na base): a guarda parte da referência e não dos manifestos | CT-28 | linha "sem a linha de `hoceineel/filament-quick-action-dock`": reprova; o mutante aceita |
| M3 | a unificação esquece um pacote, que fica sem F-nn e fora da lista | CT-28 | linha "sem a F-nn do Health": reprova; o mutante aceita |
| M4 | pacote jogado na lista dos sem feature, sem o motivo | CT-28 | linha `laravel/tinker`: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-04) | o pacote cujo plugin um painel registra vai para a lista dos sem feature, com um motivo qualquer ("dependência do painel") | CT-28 | linha do Health na lista: reprova; o mutante aceita, porque o controle antigo usava um pacote sem peça |
| M6 — revisão adversarial (ADV-22) | pacote na lista com motivo de enfeite | CT-28 | linhas do motivo "—" e do "não se aplica" sem porquê: reprova; o mutante aceita |

Saiu o mutante "a guarda para de ler a referência na seção dos avaliados e perde a de front-end". Com as duas direções conferidas, esse erro da guarda não deixa defeito passar. Os sete pacotes do front-end (seis de `devDependencies` e o opcional) viram "instalado e não listado", e a guarda reprova a implementação correta, o que denuncia a si mesma. O mesmo vale para a guarda que ignora o `optionalDependencies` ou lê a seção dos avaliados como instalados.

---

## Regra R28 — Toda peça que um painel registra — recurso, página, widget e plugin —, nos dois modos, e toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está na lista da guarda das peças e classes sem feature própria, com o motivo

> `RQ-20`, `RQ-04`, `RQ-05`, `P-05`, `P-12` · perfil **padrão** · técnica: **tabela de decisão** (3 painéis × 2 modos × 4 tipos de peça) + EP (classe de `app/`) + controles em cópia *(regra nova em 2026-11-20: Q8; alterada na quarta rodada: P-12, que veio do ADV-33)*

A Q8 foi reclassificada para a raia desenho e decidida pela sessão: é o piso mecânico da varredura da RQ-20. O middleware fica fora do piso do registro.

*(alterado em 2026-11-20: P-12)* A P-12 estendeu o piso e deu a ele a lista com o motivo:
- **O piso.** Vale para a peça registrada e também para toda classe de `app/` fora de `app/Filament`.
  - "Classe" é o tipo que cada arquivo PHP dali declara. São 135 na base: 123 classes, 7 enums e 5 traits, a conta da própria P-12.
  - A P-12 mediu que 103 delas já eram peça na varredura do step 3.
- **A guarda.** Reprova a peça ou a classe que não entrega F-nn e não está na lista com o motivo.
- **O limite.** Além desse piso, a completude da varredura não tem garantia, e a página do mapa diz isso (R33, CT-34).

```gherkin
  Regra: A varredura não deixa peça registrada nem classe de `app/` fora do catálogo sem dizer por quê

    Esquema do Cenário: [CT-29] sem tenant, toda peça que o painel registra entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o painel "<painel>" do registro de painéis, e o modelo e a lista da guarda "<mundo>"
      Quando a guarda do mapa procura a F-nn de cada recurso, página, widget e plugin que o painel registra
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                          | resultado                                                                                              |
        | admin  | publicado                                                      | aceita: cada peça registrada é, no modelo, peça de ao menos uma F-nn que existe no catálogo, ou está na lista da guarda com o motivo |
        | app    | publicado                                                      | aceita (idem)                                                                                          |
        | infra  | publicado                                                      | aceita (idem), inclusive a página `HealthCheckResults`, que o plugin registra                           |
        | admin  | cópia em que `TenantResource` não entrega F-nn nenhuma e está fora da lista | reprova, nomeando `TenantResource`                                                        |
        | infra  | cópia em que a página `HealthCheckResults` não entrega F-nn nenhuma e está fora da lista | reprova, nomeando a página                                                     |
        | admin  | cópia em que o widget `AccountWidget` aponta a F-99, que o catálogo não tem | reprova, nomeando o widget                                                                  |
        | admin  | cópia em que o widget `AccountWidget` não entrega F-nn e está na lista sem o motivo | reprova, nomeando o widget                                                          |

    Esquema do Cenário: [CT-30] com tenant, toda peça que o painel registra entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o painel "<painel>" do registro de painéis
      Quando a guarda do mapa procura a F-nn de cada recurso, página, widget e plugin que o painel registra
      Então cada peça registrada é, no modelo, peça de ao menos uma F-nn que existe no catálogo, ou está na lista da guarda com o motivo
      E a guarda conferiu ao menos <âncora>

      Exemplos:
        | painel | âncora                                               |
        | app    | o plugin `FilamentSearchSpotlightPlugin`             |
        | admin  | o recurso `TenantResource`                           |
        | infra  | a página `HealthCheckResults`, que o plugin registra |

    Esquema do Cenário: [CT-41] toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo
      Dado as classes de `app/` fora de `app/Filament`, lidas da árvore, e o modelo e a lista da guarda "<mundo>"
      Quando a guarda do mapa procura a F-nn de cada classe
      Então o resultado é "<resultado>"
      E, no publicado, a guarda conferiu ao menos as 135 classes da base

      Exemplos:
        | mundo                                                                                                     | resultado                                                                                                     | # partição                  |
        | publicado                                                                                                 | aceita: cada classe é peça do modelo que entrega ao menos uma F-nn que existe no catálogo, ou está na lista da guarda com o motivo | válida |
        | cópia em que `App\Console\Commands\KitUpdate` não entrega F-nn e está fora da lista                         | reprova, nomeando a classe                                                                                    | classe sem F-nn nem motivo  |
        | lista de classes com uma a mais, `App\Support\RelatorioNovo`, que o modelo não tem                          | reprova, nomeando a classe                                                                                    | classe nova esquecida       |
        | cópia da lista com `App\Support\TelaDeAutenticacao`, sem o motivo                                          | reprova, nomeando a classe                                                                                    | motivo ausente              |
        | cópia da lista com `App\Traits\TemUuid`, com o motivo "—"                                                  | reprova, nomeando a classe                                                                                    | motivo de enfeite           |
        | cópia da lista com `App\Console\Commands\KitUpdate`, com o motivo "classe de apoio"                       | reprova, nomeando a classe                                                                                    | comando `kit:*` na lista    |
```

O widget `AccountWidget` é registrado no `/admin` em `app/Providers/Filament/AdminPanelProvider.php:AccountWidget:158`.

A guarda das classes recebe a lista de classes como parâmetro. Assim o controle acrescenta uma classe que não existe, sem tocar na árvore. O comando `kit:update` e o serviço `TelaDeAutenticacao` são os exemplos que a revisão deu de feature fora do registro (ADV-33): `app/Console/Commands/KitUpdate.php` e `app/Support/TelaDeAutenticacao.php`.

*(alterado em 2026-11-20: ADV2-18)* Comando `kit:*` nunca entra na lista das sem feature própria, como a página de `recursos/` não entra na da R5 e o pacote com peça registrada não entra na da R27. Medido com `Artisan::all()`: 9 comandos `kit:*` — `kit:admin`, `kit:arte`, `kit:cobertura`, `kit:convites-lembrar`, `kit:info`, `kit:install`, `kit:midia-privada`, `kit:tenancy` e `kit:update`. No modelo do commit `4adaf9a`, nenhum dos 9 tem F-nn, e o catálogo da entrega (`0f38e2f`) dá a F-102 ao `kit:install` e a F-105 ao `kit:update`: o CT-41 e o CT-06 reprovam esse modelo até a sessão ligar as F-nn.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um recurso novo entra no painel sem F-nn e fora da lista (a varredura o esqueceu) *(alterado em 2026-11-20: P-12, a lista com motivo vale para peça registrada)* | CT-29 | linha "`TenantResource` sem F-nn e fora da lista": reprova; o mutante aceita |
| M2 | a guarda confere só `getResources()` e esquece a página que o plugin registra | CT-29, CT-30 | CT-29, linha "`HealthCheckResults` sem F-nn", e CT-30, linha "infra": reprova; o mutante aceita |
| M3 | a peça aponta uma F-nn que o catálogo não tem | CT-29 | linha "`AccountWidget` aponta a F-99": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-33) | a varredura para no registro dos painéis, e o comando `kit:update` fica sem F-nn e fora da lista | CT-41 | linha do `KitUpdate`: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-33) | uma classe nova entra em `app/`, e ninguém a cataloga | CT-41 | linha da classe a mais, `RelatorioNovo`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-33) | peça ou classe na lista sem o motivo, ou com motivo de enfeite | CT-29, CT-41 | CT-29, linha do `AccountWidget` na lista sem o motivo, e CT-41, linhas do `TelaDeAutenticacao` e do `TemUuid`: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-18) | a feature que a varredura achou vai para a lista das sem feature própria com um motivo crível ("classe de apoio") | CT-41 | linha do `KitUpdate` na lista: reprova; o mutante aceita |

---

## Regra R29 — Cada marco da linha do tempo cita uma versão do CHANGELOG com a data dela, e o título visível do marco aparece na seção dessa versão; e cada tempo medido traz a data e a fonte da medição

> `P-03`, `P-06` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia *(regra nova em 2026-11-20: Q10)*

A Q10 é de raia desenho e foi decidida pela sessão. Cada marco da linha do tempo afirma features de uma versão, e cada barra da cascata é um passo de um fluxo, então a P-03 vale para os dois. Os marcos são escolhidos e não precisam ser todas as versões. Se não houver diagrama `timeline` nem `waterfall` publicado (a R19 aceita motivo), a regra não tem o que conferir.

```gherkin
  Regra: O que a linha do tempo e a cascata afirmam bate com a fonte delas

    Esquema do Cenário: [CT-31] cada marco da linha do tempo cita versão do CHANGELOG com a data dela e um termo da seção dela, e cada tempo medido traz data e fonte
      Dado os diagramas `timeline` e `waterfall` do mapa "<mundo>"
      E o `CHANGELOG.md`
      Quando a guarda do mapa confere cada marco e cada tempo
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                         | resultado                                                                                                                                                         | # partição          |
        | publicados | aceita: cada marco cita uma versão que tem cabeçalho no CHANGELOG, com a data desse cabeçalho, e o título visível do marco aparece na seção dessa versão; cada tempo traz a data e a fonte da medição (o comando ou o run do CI) | válida |
        | cópia com um marco da versão 0.99.0, que o CHANGELOG não tem                  | reprova, nomeando o marco                                                                                                                                         | versão inexistente  |
        | cópia com o marco da 0.56.0 datado de 2026-11-18 (o CHANGELOG diz 2026-11-19) | reprova, nomeando o marco                                                                                                                                         | data trocada        |
        | cópia com o marco "Passkeys" da 0.56.0, datado de 2026-11-19, que guarda o termo "login" | reprova, nomeando o marco | título que a seção não traz |
        | cópia com um marco do `[Unreleased]`                                          | reprova, nomeando o marco                                                                                                                                         | versão sem data     |
        | cópia com um tempo sem a fonte da medição                                     | reprova, nomeando o passo                                                                                                                                         | tempo sem fonte     |
        | cópia com um tempo sem a data da medição                                      | reprova, nomeando o passo                                                                                                                                         | tempo sem data      |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | marco de versão escrito de memória, que o CHANGELOG não tem | CT-31 | linha "0.99.0": reprova; o mutante aceita |
| M2 | marco com a data do commit, e não a do cabeçalho do CHANGELOG | CT-31 | linha "0.56.0 datado de 2026-11-18": reprova; o mutante aceita |
| M3 | o `[Unreleased]` vira marco da linha do tempo | CT-31 | linha `[Unreleased]`: reprova; o mutante aceita |
| M4 | tempo medido sem a fonte (o comando ou o run do CI) | CT-31 | linha "sem a fonte": reprova; o mutante aceita |
| M5 | tempo medido sem a data da medição | CT-31 | linha "sem a data": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-12) | o marco traz a versão e a data certas e atribui a ela a feature de outra versão | CT-31 | linha do marco "Passkeys": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-25) | o marco guarda um termo da seção diferente do título que mostra ao leitor | CT-31 | linha do marco "Passkeys" com o termo "login": o título não está na seção, e reprova; o mutante aceita |

*(alterado em 2026-11-20: ADV-12)*
- **O termo vale como oráculo, e a F-nn não.** O marco afirma features de uma versão, e o termo é o que liga as duas. A F-nn não serve: o CHANGELOG cita F-nn em só cinco versões (medido na base).
- **O termo do marco é o título visível dele** *(alterado em 2026-11-20: ADV2-25)*, e não um campo à escolha do marco. Ele aparece no texto da seção da versão, entre o cabeçalho dela e o próximo. Medido na seção da 0.56.0: "login" está nela, e "Passkeys", não; com o termo à escolha, o marco "Passkeys" passaria guardando "login".

---

## Regra R31 — A interface do visualizador está no idioma da página, e só a casca do comparativo fica em inglês; o catálogo pt que o kit entrega não copia o texto en, fora a lista fechada com o motivo

> `P-11`, `P-02`, `RQ-03` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído + controles em cópia · cenários: **CT-B04 e CT-B07, no `05`** (costura browser, G4) *(regra nova em 2026-11-20: Q12, fechada pela P-11; alterada na quarta rodada: ADV-06 e ADV-30)*

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão, ADV-06 e ADV-30)*
- **O catálogo pt que o kit entrega.** O archify, no commit fixado, tem o `examples/locales/pt.json`, com 461 chaves, mas ele não está no manifesto dos catálogos embutidos. O kit entrega a interface pt pelas traduções do próprio diagrama: essas 461, mais 25 chaves do kit e as sobrescritas dos tipos de peça. O en é o catálogo embutido, `locales/en.json`, com 486 chaves.
- *(alterado em 2026-11-20: medido no commit `4adaf9a`)* **O catálogo chega em duas partes.** O archify embute em cada HTML só as 390 chaves de interface (`<script id="archify-i18n-data">`, as mesmas 390 no pt e no en), e aplica as outras 96 no render — o título da página, a descrição, o contexto do nó, as legendas e os eixos de `timeline` e `waterfall` —, sem embuti-las. Por isso o CT-B07 lê as 390 no HTML pt e as 96 na fonte do kit (o `examples/locales/pt.json` do archify fixado e o `site/mapa/interface.json`, que o build junta), e compara as 486 com o `locales/en.json`. Medido: nenhuma das 486 sem texto pt; iguais ao en, 9 a 11 por HTML entre as 390 (os construtores de arquitetura sobrescrevem as dos tipos) e 14 na fonte, todas entre as 14 do archify. O mesmo vale para o CT-B04: o texto pt de cada chave é o embutido ou, nas 96, o da fonte.
- **Por que o CT-B07.** Como todo texto pt sai do que o kit entrega, uma chave com o texto en copiado não mostra o aviso de idioma reserva. Por isso o CT-B07 compara os dois catálogos, chave a chave.
- **Por que a busca e o passaporte abertos.** A busca e o passaporte montam os textos deles na interação. Por isso o CT-B04 lê a interface também com os dois abertos.
- *(alterado em 2026-11-20: ADV2-26 e ADV2-27)* **A lista fechada do CT-B07 tem teto:** só as 14 chaves cujo pt, no `examples/locales/pt.json` do archify fixado, é igual ao en, e as chaves só de marcadores. Medido: 14 das 461 chaves.
- **Os critérios da legenda no CT-B04.** Valem para o mapa de arquitetura, o único tipo cuja legenda nomeia os tipos de peça: medido no commit `4adaf9a`, a árvore, a `timeline`, o `waterfall`, o `erd` e o `sequence` não têm legenda de tipo, e a do `workflow` e a do `dataflow` falam de passos e de dados. Em pt, a legenda traz o nome pt de cada tipo de peça desenhado, como pedaço do rótulo do grupo dele: os da RQ-24 — painel, recurso, página, widget, plugin, provider, middleware, serviço e model — e, fora dela, policy, comando, Livewire e settings. Em en, nenhum texto de interface igual ao pt de uma chave cujo en é outro; nenhum aviso de idioma reserva; e, no mapa de arquitetura, a legenda com o nome en de cada tipo de peça desenhado — panel, resource, page, widget, plugin, provider, middleware, service, model, policy, setting, command, Livewire —, como pedaço do rótulo. Medido no esqueleto: a legenda en do `admin-acesso` traz panel, page, resource, widget, Livewire, provider, service, model, settings, policy, middleware e plugin. No commit `4adaf9a`, no `app-acesso`: em pt, "Tela (painel, página, recurso, widget, Livewire)", "Código do kit (provider, serviço, comando)", "Dados (model, settings)", "Pacote ou plugin de terceiros" e "Acesso (middleware, policy)"; em en, os mesmos grupos com os nomes en; e nenhum dos tipos desenhados (página, serviço, plugin, middleware, Livewire, model) falta. "Plugin de pacote" e "serviço próprio", por extenso, reprovariam dado correto: a legenda diz "Pacote ou plugin de terceiros" e "serviço".

A tabela abaixo é a mesma do CT-B04 e do CT-B07.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | uma chave da interface do archify sem tradução pt cai no inglês, com o aviso de idioma reserva | CT-B04 | "nenhum texto de interface é igual ao en de uma chave cujo texto pt é outro, nenhum aviso de idioma reserva aparece": reprova; o mutante aceita |
| M2 | o documento pt se declara `lang="en"` (o idioma não chega ao HTML) | CT-B04 | "o `lang` do documento começa por `pt`": reprova; o mutante aceita |
| M3 | a legenda dos tipos de peça fica com os rótulos do archify em inglês | CT-B04 | "a legenda traz o nome pt de cada tipo de peça desenhado, como pedaço do rótulo": reprova; o mutante aceita |
| M4 | a exceção do comparativo vale para todo diagrama, e a interface inteira fica em inglês sem reprovar | CT-B04 | "na versão pt de todo diagrama fora o comparativo, nenhum texto de interface é igual ao en de uma chave cujo texto pt é outro": reprova; o mutante aceita |
| M5 | a versão en recebe a tradução pt (catálogo de tradução trocado de idioma) *(alterado em 2026-11-20: ADV2-27, a asserção antiga não tinha critério)* | CT-B04 | versão en, "nenhum texto de interface igual ao pt de uma chave cujo en é outro, e a legenda com os nomes en dos tipos": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-30) | a busca e o passaporte montam os textos na interação, e uma chave deles cai no inglês | CT-B04 | "com a busca aberta e o passaporte de uma peça aberto, nenhum texto visível é igual ao en de uma chave cujo texto pt é outro": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-06) | para calar o aviso de idioma reserva, o catálogo pt do kit recebe o texto en nas chaves não traduzidas | CT-B07 | linha "a chave da busca traz o texto en": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-06) | a lista fechada de textos iguais aceita a chave sem o motivo | CT-B07 | linha "na lista, sem o motivo": reprova; o mutante aceita |
| M9 — revisão adversarial (ADV2-26) | quem copia o texto en para calar o aviso acrescenta a chave à lista fechada, com um motivo que não é de enfeite | CT-B07 | linha `viewer.finder.placeholder` na lista, com motivo: reprova; o mutante aceita |

---

## Regra R32 — A conferência de acessibilidade audita a amostra inteira e tolera só os dois defeitos do archify, pela regra e pelo elemento

> `P-11`, `RQ-03`, `RQ-07` · perfil **padrão**, técnica escalada (6 mutantes, o teto do completo) · técnica: **amostra estratificada** (tipo × idioma × tema) + **controle sintético** · cenários: **CT-B05 e CT-B06, no `05`** (costura browser, G4) *(regra nova em 2026-11-20: Q12, fechada pela P-11)*

Os dois defeitos tolerados são estes:
- `nested-interactive` cujo alvo é o `<svg>` raiz do diagrama;
- `color-contrast` cujo alvo é a contagem de grupo do índice de nós, `.node-outline-heading > small`.

Qualquer outra violação serious ou critical, qualquer `console.error` e qualquer exceção não capturada reprovam *(alterado em 2026-11-20: ADV-29; o mutante da exceção está na R24, como M7, e o teto de seis desta regra fica)*. A tabela abaixo é a mesma do `05`.

*(alterado em 2026-11-20: ADV2-19)* A tolerância decide por alvo, e não pela violação inteira: a violação com alvos mistos reprova pelo alvo que não é tolerado. Medido: o `site/verifica-mapa.mjs` já filtra os alvos da violação (`v.nodes.filter`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a tolerância casa pelo texto do seletor do axe: qualquer alvo cujo seletor contém `svg` ou `small` passa | CT-B06 | linhas "`nested-interactive` num `<svg>` que não é o raiz" e "`color-contrast` num `<small>` fora de `.node-outline-heading`": reprova; o mutante tolera |
| M2 | a tolerância casa pela regra sem o elemento: todo `color-contrast` passa | CT-B06 | linha "`color-contrast` num parágrafo do corpo": reprova; o mutante tolera |
| M3 | uma terceira violação entra na tolerância | CT-B06 | linha "`button-name` num botão sem nome": reprova; o mutante tolera |
| M4 | amostra vazia: o conferidor não acha diagrama e não audita nada | CT-B05 | "a amostra tem exatamente uma entrada por tipo publicado, idioma e tema, e nenhuma é vazia": reprova; o mutante aceita |
| M5 | um tipo publicado fica fora da amostra | CT-B05 | idem, pelo tipo que falta: reprova; o mutante aceita |
| M6 | o comparativo não é auditado | CT-B05 | "mais o comparativo no tema escuro": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-19) | a função de decisão tolera a violação inteira quando algum alvo, ou o primeiro, é tolerado | CT-B06 | linha "`color-contrast` de dois alvos: `.node-outline-heading > small` e um parágrafo do corpo": reprova pelo parágrafo; o mutante tolera |

---

## Regra R33 — A página do mapa declara, nos dois idiomas, as duas exceções da P-11, a do comparativo na P-04 e o limite da varredura da P-12

> `P-11`, `P-12`, `P-04`, `P-02` · perfil **padrão** · técnica: **EP** (idioma) *(regra nova em 2026-11-20: Q12, fechada pela P-11; alterada na quarta rodada: P-12)*

```gherkin
  Regra: O leitor sabe o que o mapa não cumpre, e por quê

    Esquema do Cenário: [CT-34] a página do mapa de cada idioma declara a casca do comparativo em inglês, os dois defeitos tolerados do archify e o limite da varredura
      Dado a página do mapa em "<idioma>"
      Quando a guarda do mapa lê a declaração de exceções da página
      Então a página diz que a casca do comparativo fica em inglês
      E a página nomeia os dois defeitos tolerados pela regra e pelo elemento: `nested-interactive` no `<svg>` raiz do diagrama, e `color-contrast` na contagem de grupo do índice de nós (`.node-outline-heading > small`)
      E a página diz qual é o piso da varredura — as peças que os painéis registram e as classes de `app/` fora de `app/Filament` — e que, além dele, a completude não tem garantia
      E a página diz que o comparativo não leva cards nem passaporte, e que a base e a cabeça dele são diagramas do mapa com a prova de cada ligação (P-04)

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a página não declara as exceções | CT-34 | "a página diz…" e "a página nomeia…": reprova; o mutante aceita |
| M2 | a declaração está só na página pt | CT-34 | linha "en": reprova; o mutante aceita |
| M3 | a declaração é genérica ("o archify tem defeitos de acessibilidade"), sem a regra e o elemento | CT-34 | "nomeia os dois defeitos pela regra e pelo elemento": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-33) | a página apresenta o mapa como completo e não diz o piso da varredura nem o limite dela | CT-34 | "diz qual é o piso da varredura e que, além dele, a completude não tem garantia": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV2-20) | a página não declara a exceção do comparativo, e o leitor procura nele a prova que ele não tem | CT-34 | "diz que o comparativo não leva cards nem passaporte": reprova; o mutante aceita |

---

## Regra R34 — Os diagramas de arquitetura e a árvore de registro de um painel que valem num modo desenham, juntos, todo o registro do painel nesse modo, e nada que ele não registra nesse modo

> `P-09`, `RQ-11`, `RQ-10`, `P-05` · perfil **padrão** · técnica: **tabela de decisão** (o registro muda entre os modos × há arquitetura por modo; 3 painéis × 2 modos) + controles em cópia *(regra nova em 2026-11-20: ADV-05)*

O `/app` muda de registro entre os modos: `IdentifyTenant` e `DefinirTenantDePermissoes` só existem com tenant (CT-11 e CT-12). O `/admin` e o `/infra`, não (Setup Global).

A regra não manda o `/app` ter dois diagramas. Ela pede, em cada modo, que os diagramas `architecture` e a árvore de registro do painel que valem nesse modo — os do modo ou os compartilhados — desenhem juntos o registro do painel nesse modo, nem mais nem menos. O compartilhado só passa nos dois modos se o registro for o mesmo, e por isso o `/app` precisa de um por modo.

*(alterado em 2026-11-20: fato da implementação, dado pelo coordenador, e medição no commit `4cc9517`)* O registro do painel sai na árvore de registro, que o construtor gera do modelo: o `/app` tem uma árvore por modo (`app-registro-sem` e `app-registro-com`), porque com tenant registra o `IdentifyTenant` e o `DefinirTenantDePermissoes`; o `/admin` e o `/infra` têm uma árvore só cada um, compartilhada pelos dois modos, porque o registro deles não muda entre os modos. Por isso a árvore de registro conta aqui, ao lado da arquitetura, e só contar a arquitetura reprovaria o `/admin` e o `/infra`, que não têm mapa de arquitetura. Medido no modelo do commit `4cc9517`: o registro do `/admin` tem 57 peças nos dois modos, e o do `/infra`, 84, iguais; o do `/app` tem 46 sem tenant e 48 com, e a diferença é o `IdentifyTenant` e o `DefinirTenantDePermissoes`. As duas árvores do `/app` estão nos arquivos desse commit; a do `/admin` e a do `/infra` ainda não, e o CT-37 e o CT-38 as cobram: sem elas, nada desenha o registro do `/admin` (falta 57) nem o do `/infra` (falta 84). No `/app`, os mapas e a árvore que valem em cada modo desenham juntos o registro dele, nem mais nem menos: 46 de 46 sem tenant e 48 de 48 com; os dois mapas compartilhados (`app-acesso` e `app-organizacoes`) não desenham peça nem ligação de um modo só, e nenhum dos 4 mapas desenha peça que só outro painel registra.

A ligação de registro que só aparece num diagrama de outro tipo — fora a árvore de registro — não conta aqui, embora conte no CT-14.

O modo com tenant só existe na suíte `Tenancy`. Sem tenant, o `/app` não registra os dois middlewares, e a cópia "só o compartilhado" passaria no CT-37. Quem a mata é o CT-38.

*(alterado em 2026-11-20: ADV2-29)* O `TenantResource` está no registro do `/admin` nos dois modos e só abre com tenant. Afirmar que o diagrama sem tenant o desenha responderia à Q11 ("em que diagrama de modo ela entra"), e ao contrário da recomendação dela. Até a resposta, a linha admin do CT-37 tira do "nem mais nem menos" as peças de disponibilidade condicional, e a Q11 segura esse recorte.

```gherkin
  Regra: O que muda de registro entre os modos ganha um diagrama de arquitetura por modo

    Esquema do Cenário: [CT-37] sem tenant, os diagramas de arquitetura e a árvore de registro de cada painel que valem nesse modo desenham o registro dele nesse modo, e nada além
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o painel "<painel>" do registro de painéis, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa junta as ligações de registro que os diagramas `architecture` e a árvore de registro do painel que valem no modo sem tenant desenham
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                                                    | resultado                                                                                         |
        | admin  | publicados                                                                               | aceita: o conjunto é o registro do painel sem tenant, nem mais nem menos, fora a peça de disponibilidade condicional (o `TenantResource`), que fica com a Q11 |
        | app    | publicados                                                                               | aceita (idem), sem `IdentifyTenant` nem `DefinirTenantDePermissoes`                              |
        | infra  | publicados                                                                               | aceita (idem), com a página `HealthCheckResults`, que o plugin registra                          |
        | admin  | cópia em que nem a árvore de registro nem um diagrama de arquitetura do admin desenha o `FilamentShieldPlugin` | reprova, nomeando o painel admin e o plugin                                     |

    Esquema do Cenário: [CT-38] com tenant, os diagramas de arquitetura e a árvore de registro de cada painel que valem nesse modo desenham o registro dele nesse modo, e nada além
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o painel "<painel>" do registro de painéis, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa junta as ligações de registro que os diagramas `architecture` e a árvore de registro do painel que valem no modo com tenant desenham
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                                                                                    | resultado                                                                                   |
        | app    | publicados                                                                                                               | aceita: o conjunto é o registro do painel com tenant, inclusive `IdentifyTenant` e `DefinirTenantDePermissoes` |
        | admin  | publicados                                                                                                               | aceita: o conjunto é o registro do painel com tenant                                        |
        | infra  | publicados                                                                                                               | aceita (idem)                                                                               |
        | app    | cópia em que o painel app só tem diagramas compartilhados — a arquitetura e a árvore —, sem `IdentifyTenant` nem `DefinirTenantDePermissoes` | reprova, nomeando o painel app e as duas peças                     |
        | app    | cópia em que o `IdentifyTenant` só aparece no `sequence` `requisicao-autenticada-com`, e nem a árvore nem a arquitetura do `/app` com tenant o desenham | reprova, nomeando o painel app e `IdentifyTenant` |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV-05) | o `/app` sai só com diagramas compartilhados — a arquitetura e a árvore —, que omitem as peças do modo com tenant | CT-38 | linha "só diagramas compartilhados": reprova; o mutante aceita, porque o compartilhado não desenha peça de um modo só (CT-19) |
| M2 — revisão adversarial (ADV-05) | as ligações de registro do modo com tenant ficam num diagrama de outro tipo, marcado "com tenant" | CT-38 | linha "só no `requisicao-autenticada-com`": reprova; o mutante aceita, porque o CT-14 acha a ligação desenhada *(alterado em 2026-11-20: a linha de controle era um `workflow` hipotético; todo `workflow` do commit `4cc9517` é do kit inteiro, e o caso real é a pilha da requisição com tenant, que desenha o `IdentifyTenant`)* |
| M3 | a árvore de registro de um painel esquece uma peça registrada, como a de plugin, e nenhum diagrama de arquitetura dele a desenha | CT-37 | linha "nem a árvore nem a arquitetura desenham o `FilamentShieldPlugin`": reprova; o mutante aceita, se a ligação estiver noutro tipo de diagrama |

---

## Regra R35 — Toda transição de fluxo publicado tem âncora que resolve; no `lifecycle`, todo estado também; e duas transições seguidas ancoradas no mesmo método estão na ordem do código

> `P-03`, `RQ-09` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia *(regra nova em 2026-11-20: ADV-11 e ADV-32)*

- **A âncora.** É o arquivo e o trecho, e resolve como uma prova (R9): o arquivo é versionado no kit, e o trecho está nele, fora de comentário.
- **A transição** é a seta entre dois passos de `workflow`, `sequence` e `dataflow`, ou entre dois estados de `lifecycle`.
- **A ordem.** Só é conferível quando duas transições seguidas do mesmo fluxo (`workflow`, `sequence` ou `dataflow`) têm âncora no mesmo arquivo e no mesmo método: o trecho da primeira vem antes do da segunda nesse método. Entre arquivos ou métodos diferentes, a ordem depende da chamada em tempo de execução, que uma guarda estática não reconstrói. Essa parte é lacuna declarada (M4).
- **Qual fluxo entra no mapa** é recorte da Q6. Esta regra vale para todo fluxo publicado, qualquer que seja a lista.

```gherkin
  Regra: O que um fluxo afirma tem âncora no código, e a guarda falha quando a âncora deixa de bater

    Esquema do Cenário: [CT-39] toda transição de fluxo publicado tem âncora que resolve, todo estado de `lifecycle` também, e as transições seguidas no mesmo método estão na ordem do código
      Dado os diagramas de fluxo do mapa "<mundo>"
      Quando a guarda do mapa confere a âncora de cada transição e de cada estado
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu a âncora de todas as transições de todos os diagramas de fluxo

      Exemplos:
        | mundo                                                                                                                         | resultado                                     | # partição                       |
        | publicados                                                                                                                    | aceita                                        | válida                           |
        | cópia com um diagrama `lifecycle` do convite em que a transição Recusado → Pendente não tem âncora                             | reprova, nomeando a transição                 | transição sem âncora             |
        | cópia com a transição Recusado → Pendente ancorada num trecho que não está no arquivo                                          | reprova, nomeando a transição                 | âncora que não resolve           |
        | cópia com um estado do `lifecycle` sem âncora                                                                                  | reprova, nomeando o estado                    | estado sem âncora                |
        | cópia de um fluxo cuja transição tem a âncora num arquivo copiado em memória sem a linha do trecho                             | reprova, nomeando a transição                 | chamada que saiu do código       |
        | cópia de um fluxo com duas transições seguidas, ancoradas no mesmo método, trocadas de ordem                                   | reprova, nomeando as duas transições          | ordem trocada no mesmo método    |
```

O exemplo do convite vem do glossário: "Expirado … é o único que volta a Pendente". A cópia põe o diagrama em memória, então a linha vale mesmo que a Q6 não leve esse fluxo ao mapa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV-11) | o `lifecycle` é dispensado da guarda do passo, e uma transição inventada (Recusado → Pendente) passa | CT-39 | linha "transição sem âncora": reprova; o mutante aceita |
| M2 — revisão adversarial (ADV-32) | a chamada sai do código, e a transição continua desenhada, porque as classes continuam existindo | CT-39 | linha "chamada que saiu do código": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-11) | estado do `lifecycle` escrito de memória, sem nada no código que o sustente | CT-39 | linha "estado sem âncora": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-32) | a ordem diverge do código entre transições ancoradas em arquivos ou métodos diferentes | ⚠️ **sem matador**: a ordem entre arquivos depende da chamada em tempo de execução, que a guarda estática não reconstrói. O quality gate a confere na leitura do fluxo | — (lacuna declarada) |
| M5 — revisão adversarial (ADV-11) | a transição inventada ganha uma âncora que resolve, num trecho que existe e não faz essa transição | ⚠️ **sem matador**: a âncora prova que o trecho existe, e não que ele faz a transição. Exigir no trecho o nome do estado de destino reprovaria transição real (Q18, decidida — D25): os estados do kit são calculados, e não gravados (`app/Models/Convite.php:situacao:619`, `app/Models/User.php:rotuloDaSituacao:558`); a transição grava a coluna e nunca escreve o nome do estado (`app/Models/Convite.php:'aceito_em':690`); e "prazo vence" não tem linha que a faça. Nos outros tipos de fluxo, o nome do destino no trecho falharia na chamada por injeção de dependência | — (lacuna declarada) |

---

## Regra R36 — O que o mapa desenha está no modelo: no mapa de arquitetura e na árvore de registro, toda aresta é ligação do modelo no mesmo sentido — direta, de agrupamento ou de porta —, e todo diagrama de painel só desenha, como peça, peça do painel ou do kit inteiro, além da caixa de porta

> `P-03`, `RQ-24`, `P-10`, `RQ-10` · perfil **padrão** · técnica: **EP** (forma da aresta) + controles em cópia *(regra nova em 2026-11-20: ADV-24 e ADV-27)*

A R12 vai do modelo ao desenho. Esta regra vai do desenho ao modelo, com as três formas de aresta que a sessão mediu nos mapas de arquitetura:
- **Direta.** É uma ligação do modelo, no mesmo sentido.
- **De agrupamento.** Toda ligação que ela representa existe no modelo, e liga peças que o agrupamento nomeia.
- **De porta.** O outro lado de cada ligação que ela representa é peça desenhada no mapa que a porta nomeia.

O diagrama de um painel só desenha três coisas:
- peça do painel: o que ele registra (R11) e as pontas das ligações que partem dele ou das peças dele;
- peça do kit inteiro: peça que nenhum painel registra, como model, serviço próprio, comando ou setting;
- caixa de porta.

*(alterado em 2026-11-20: medido no commit `4cc9517`, com os fluxos escritos a partir do código)* A regra da aresta vale para o mapa de arquitetura e para a árvore de registro, que desenham ligações do modelo. O fluxo (`sequence`, `workflow`, `dataflow`, `lifecycle`) desenha a ordem de execução: a mensagem e a transição dele não são ligação do modelo, e quem as confere é a âncora (R35). No fluxo de painel, a regra da peça vale para o nó que é peça do modelo, pelo nome exato; o nó que não é peça — o navegador, o provedor de IA, um formulário — fica fora dela. Medido nos 14 fluxos de painel escritos à mão do commit: 99 nós, 38 com o nome exato de uma peça do modelo, todos peça de um dos painéis do diagrama ou do kit inteiro, e 61 que não são peça; das 157 arestas, só 4 são ligação do modelo entre as duas pontas. A regra antiga, "toda aresta de todo diagrama", reprovaria esses fluxos.

```gherkin
  Regra: O desenho não inventa nem inverte ligação, e o diagrama de um painel não desenha o que é de outro

    Esquema do Cenário: [CT-40] toda aresta de mapa é ligação do modelo no mesmo sentido, e todo diagrama de painel só desenha, como peça, o que é dele, do kit inteiro ou porta
      Dado o modelo e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere cada aresta e cada peça desenhada
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu todas as arestas dos mapas de arquitetura e das árvores de registro, e todo nó que é peça do modelo nos diagramas de painel

      Exemplos:
        | mundo                                                                                                         | resultado                                     | # partição                    |
        | publicados                                                                                                    | aceita                                        | válida                        |
        | cópia com a aresta `User` → `UserResource`, invertida                                                         | reprova, nomeando o diagrama e a aresta       | direta invertida              |
        | cópia com uma aresta entre duas peças do diagrama que o modelo não liga                                       | reprova, nomeando o diagrama e a aresta       | direta inventada              |
        | cópia com uma aresta de agrupamento que representa a ligação de uma peça que o agrupamento não nomeia          | reprova, nomeando o agrupamento e a peça      | membro não nomeado            |
        | cópia com uma aresta de porta para o mapa do `/infra`, cuja outra ponta o mapa do `/infra` não desenha          | reprova, nomeando a porta                     | porta para o mapa errado      |
        | cópia de um diagrama declarado do `/infra`, copiado do `/admin`, que desenha o `UserResource`                   | reprova, nomeando o diagrama e a peça         | peça de outro painel          |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV-24) | o gerador inverte o sentido da aresta (`User` → `UserResource`) | CT-40 | linha "invertida": reprova; o mutante aceita, porque o CT-14 só vai do modelo ao desenho |
| M2 — revisão adversarial (ADV-24) | um mapa de arquitetura escrito à mão traz uma aresta que o modelo não tem | CT-40 | linha "inventada": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-24) | a aresta de agrupamento representa a ligação de uma peça que não é membro nomeado | CT-40 | linha "membro não nomeado": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-24) | a porta aponta para um mapa que não desenha o outro lado da ligação | CT-40 | linha "porta para o mapa errado": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-27) | o diagrama de um painel é copiado de outro e declarado com o painel novo | CT-40 | linha do `UserResource` no `/infra`: reprova; o mutante aceita, porque o CT-17 decide pelo painel que o arquivo declara |

---

## Regra R37 — Toda marca de disponibilidade que o mapa publica é a do código: a chave que decide o acesso da peça, com o valor de fábrica dela, ou o modo

> `P-03`, `RQ-04`, `RQ-05`, `RQ-11` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia *(regra nova em 2026-11-20: ADV2-30)*

A Q11 não diz se o mapa marca a peça condicional (R30, estacionada). Mas, se marcar, a marca tem de ser a do código, e isso vale nas três leituras da Q11. No publicado sem marca nenhuma, a regra é vácua.
- **A chave e o valor de fábrica.** A chave da marca é a que o `canAccess()` da peça lê, direto ou pelo apoio que ele chama, e o valor de fábrica é o default dela no `config/kit.php`. A chave vale pela variável do `.env` ou pela chave de config.
- **O modo.** A marca de modo diz em que modo a peça abre, pela leitura de `kit.tenancy.enabled` no `canAccess()`.

Medido no código:
- o `canAccess()` do `AgenteIaResource` lê `AgentesDeIa::habilitado()` (`app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php:canAccess:70`), que lê `kit.agentes_ia.habilitado` (`app/Support/AgentesDeIa.php:habilitado:29`), cuja variável é `KIT_AGENTES_IA`, desligada de fábrica (`config/kit.php:'habilitado':682`);
- o `canAccess()` do `TenantResource` lê `kit.tenancy.enabled` (`app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`), cuja variável é `KIT_TENANCY`, desligada de fábrica (`config/kit.php:'enabled':452`);
- o esqueleto publicado não tem marca de disponibilidade, e a regra hoje é vácua.

```gherkin
  Regra: O que o mapa diz sobre a disponibilidade de uma peça é o que o código decide

    Esquema do Cenário: [CT-45] toda marca de disponibilidade publicada é a do código, com a chave que decide e o valor de fábrica, ou o modo
      Dado o modelo e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere cada marca de disponibilidade publicada contra o `canAccess()` da peça
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                         | resultado                                                        | # partição               |
        | publicados                                                                                    | aceita: cada marca é a do código; sem marca nenhuma, aceita por vácuo | válida              |
        | cópia que marca o `AgenteIaResource` com a chave `KIT_REGISTRO`, que não decide o acesso dele | reprova, nomeando a peça                                         | chave que não decide     |
        | cópia que marca o `AgenteIaResource` com `KIT_AGENTES_IA` ligada de fábrica                   | reprova, nomeando a peça                                         | valor de fábrica trocado |
        | cópia que marca o `TenantResource` como disponível só sem tenant                              | reprova, nomeando a peça                                         | modo invertido           |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV2-30) | o mapa marca a peça com uma chave vizinha, que não decide o acesso dela | CT-45 | linha `KIT_REGISTRO`: reprova; o mutante aceita |
| M2 — revisão adversarial (ADV2-30) | a marca traz o valor de fábrica trocado | CT-45 | linha "ligada de fábrica": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV2-30) | a marca de modo sai invertida, e a tela de organizações aparece como disponível só sem tenant | CT-45 | linha "só sem tenant": reprova; o mutante aceita |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-09 (linhas `bootstrap/cache/packages.php` e `vendor/`). O site é público e estático, e nenhuma rota recebe `{id}`. A única saída de conteúdo para fora do repositório público seria a prova citar arquivo fora do commit, e essas linhas a matam | G2 |
| Autorização exercida na ação | não se aplica: nenhuma ação, policy ou permissão nova. A Superfície Livewire do `02` não cria nada em `app/`, e o gate confere o diff (RQ-15) | — |
| Idempotência (ancorada no agregado) | não se aplica: o build regenera o mapa do zero a partir do commit (P-07), e nada acumula entre uma execução e outra | — |
| Concorrência | não se aplica: não há contador nem limite | — |
| **Fronteira no ponto de entrada** (o modelo, o catálogo e as referências, conferidos a cada PR) | CT-01, CT-03, CT-04, CT-08, CT-09, CT-10, CT-21, CT-28, CT-29, CT-31, CT-35, CT-36, CT-39, CT-40, CT-41, CT-44, CT-45 | G1, G2, G3 |
| **Domínio condicionado** | CT-06, CT-36 e CT-44 (origem × peça; F-nn × pacote nomeado × pacote da peça; rota do "onde fica" × peça; caminho × modo), CT-08 (classe × tipo), CT-10 (forma da prova × arquivo; trait × origem que o usa; método × lista de contrato; par de mapeamento × pontas), CT-21 (tipo × painel × modo; escopo do diagrama × peça registrada), CT-37 e CT-38 (registro × modo × arquitetura), CT-45 (marca × chave × valor de fábrica × modo) | G2, G3 |
| **Estado × operação de escrita** (número removido × reaproveitar; removido × ainda entregue; removido × idioma) | CT-03, CT-04 (matriz da R3, 15 células), CT-36 (a removida que uma peça ainda entrega), CT-01 (removida num idioma só) | G1, G2 |
| Ausente ≠ null ≠ vazio | CT-09 (trecho ausente, vazio, só de espaços), CT-03, CT-21, CT-28, CT-29, CT-35 e CT-41 (motivo ausente; motivo "—"; "não se aplica" sem porquê), CT-31 (tempo sem fonte; sem data), CT-39 (transição e estado sem âncora) | G1, G2 |
| Paginação / ordenação | não se aplica: não há listagem paginada, e a ordem das linhas do catálogo não é cláusula. A ordem dos números é, e o CT-04 a cobre | — |
| Timezone / DST | não se aplica: nenhuma data é calculada. As datas da linha do tempo são comparadas como texto com o cabeçalho do CHANGELOG (CT-31) | — |
| Unicode / texto livre | CT-B01 (acentos intactos no `<h1>`; rótulo igual ao da fonte), CT-B02 (item de card inteiro), CT-B04 (interface pt e en, inclusive com a busca e o passaporte abertos), CT-B07 (catálogo pt sem o texto en), CT-16 e CT-21 (termo do glossário; palavra funcional do pt em frase en) | G4, G2 |
| Unicidade (+ soft delete) | CT-01 (F-nn em duas linhas), CT-08 (classe em duas peças). Soft delete não se aplica: não há banco | G1, G2 |
| CRUD combinado | não se aplica: não há CRUD | — |
| Mass assignment | não se aplica: não há formulário nem payload | — |
| Upload | não se aplica: não há upload | — |
| Precisão monetária | não se aplica: não há valor monetário | — |
| **Superfície Livewire** | não se aplica: o `02` declara que nenhuma página, widget ou componente é criado ou alterado, e o plano não tem path em `app/` | — |
| **Estado do framework usado sem validar** | lacuna declarada: o hash da URL do visualizador (`#focus=`, `#route=`) é entrada do leitor, mas quem o lê é o código do archify, não a entrega. Nenhum cenário da entrega o falsifica. Fica registrado para a dimensão de segurança do quality gate | G4 |
| **IDOR e mass assignment por entidade** | não se aplica: a entrega não persiste entidade | — |
| **Escopo com discriminante nulo** (o modo da peça, da ligação e do diagrama) | CT-11, CT-12, CT-19, CT-37, CT-38, CT-43 e CT-44: o modelo não dá a um modo o que o painel não registra nele, a ligação desenhada vale no modo do diagrama, os diagramas de arquitetura de cada modo desenham o registro desse modo, a célula não leva a diagrama do outro modo, e o caminho com organização resolve com tenant. A condição da peça está estacionada na Q11 (ADV-31); a marca publicada é conferida pela R37. Nas ligações de uso, o modo vem da varredura: lacuna declarada, vinculada à P-05 ("sem promessa de que a varredura achou todas") | G2, G3 |
| **Saída do estado de erro** | não se aplica: nenhum cenário termina em 4xx nem em redirect. Os links da página ficam com `site/verifica-links.mjs` e com o CT-15 | — |
| Cardinalidade 0/1/N | CT-06 (feature sem peça), CT-14 (ligação sem diagrama), CT-17 (célula sem diagrama), CT-29 (peça registrada sem F-nn), CT-39 (transição sem âncora), CT-41 (classe de `app/` sem F-nn), CT-B02 (diagrama de ligações sem prova), CT-B05 (amostra vazia) | G2, G4 |
| Leitura de `docs/`, README, `CHANGELOG.md` ou `site/` fora da árvore do kit (item vivo do projeto, `.ai/rules/testes.md`) | sentinela `naArvoreDoKit()` no `beforeEach` dos dois arquivos (Setup Global), vigiada pelo `[CT-10]`/`[CT-11]` herdados de `RedeDeDocumentacaoTest` | G1, G2, G3 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| rota entre nós, lente e exportação no visualizador | são recursos do archify; testá-los é testar o archify. O "não interativo" já morre no CT-B03 |
| troca de tema com o diagrama já aberto | a P-11 pede a amostra por tema, e o CT-B05 abre cada tema pelo mecanismo do visualizador. A troca com o diagrama aberto não tem cláusula |
| o comparativo no tema claro, na amostra de acessibilidade | a P-11 pede o comparativo só no tema escuro |
| volta do diagrama para a página do mapa | o `00` não pede, e nenhum mutante de requisito morre aí |
| "o build não roda o script de posicionamento" | posição não é oráculo, e nenhum mutante de requisito morre aí |
| guarda de F-nn em `tests/` e `app/` | ali "F-nn" é nome sobrecarregado (`.ai/rules/kit.md`: "F-01 da conformidade Blueprint"), e o falso positivo é certo |
| "`app/` não mudou" (RQ-15) | não sobrevive ao merge; o gate confere o diff |
| o delta do comparativo no HTML (o `/app` mostra `DefinirTenantDePermissoes` como acrescentado) | seria mais um CT-B. O par certo de antes e depois (CT-22) já mata a inversão e a mistura, e o delta é cálculo do archify |
| busca sem acento acha peça com acento | recurso do archify |
| o README mostra uma imagem do mapa | o `00` só pede o lugar, e o link basta (CT-26) |
| todo F-nn citado em `tests/Browser/RoteiroDoKitTest.php` existe no catálogo | mata o mesmo mutante que o CT-03: a F-03, a F-13, a F-45, a F-51 e a F-52 da base não podem trocar de feature |
| a marca da peça condicional e o diagrama de modo em que ela entra (a R30, o CT-32, o CT-33, a metade de condição do CT-B01 e o R24-M5) | estacionado na Q11 (ADV-17, ADV-31): nenhuma afirmação sobre a marca vale nas três leituras. Volta com a resposta |
| peça que só some do menu (`shouldRegisterNavigation()`) tratada como condicional | a peça que só some do menu continua aberta pela URL, e por isso não é de disponibilidade condicional. Vale quando a R30 voltar da Q11 |
| a ordem entre transições ancoradas em arquivos ou métodos diferentes | a guarda estática não reconstrói a chamada em tempo de execução (R35-M4, lacuna declarada). A ordem no mesmo método tem guarda (CT-39) |
| o oráculo do "onde fica" que não traz caminho sem "→" (34 linhas na base: 4 com "→" e 30 sem caminho) | não há peça que a guarda resolva sem julgamento (R6-M8, lacuna declarada); fica para o quality gate |
| a contagem "N de saída · M de entrada" do passaporte | é cálculo do archify, a partir das arestas que o diagrama já tem; a R36 confere as arestas |
| um diagrama de arquitetura por modo para todo painel | a P-09 manda publicar uma vez o que é igual nos dois modos; a R34 cobra o registro de cada modo, e só o `/app` muda |
| o executor não faz merge nem tag sem o OK do mantenedor (ADV-34) | controle de processo da RQ-26, conferido no quality gate e no PR (Cláusulas sem regra) |
| o `archify check` como oráculo da biblioteca embutida | medido pela sessão: ele só acusa alteração do HTML com o recibo `.delivery.json`, que o `render` não grava (D32) |
| "sem reticências" no rótulo de peça | o esqueleto escreve "…" à mão em 33 rótulos para caber na caixa; o rótulo é conferido por igualdade com o do arquivo (CT-B01), e o "…" do item de card, contra o modelo (CT-B02) |
| uma peça de pacote em cada `architecture` da amostra do CT-B03 | o `app-resumo-sem` e o `app-resumo-com` do esqueleto não têm peça de pacote, nem, no commit `4adaf9a`, o `app-organizacoes`, o `app-resumo-sem` e o `app-resumo-com`; a amostra pede uma de pacote no conjunto dos `architecture` |
| contar como "lista de features" os nós de diagrama cujo rótulo é nome de F-nn | o `infra-registro` tem 3 nós assim, e são peças; o ADV2-21 conta item de lista, linha de tabela e item de card |
| a F-nn à vista no carregamento do mapa de arquitetura, sem zoom | o archify desenha o `tag` como detalhe fino, com opacidade 0 no nível de leitura do carregamento (medido no commit `4adaf9a`). O CT-B01 confere no nível cheio, como a D29 decidiu; se a F-nn tiver de aparecer sem zoom, muda a D29, e o ponto foi ao coordenador |
| o título no `document.title` | é a moldura do archify ("Diagrama {título}", "{title} Diagram"); o título que o leitor vê é o `<h1>`, e é ele que o CT-B01 confere |
| a forma do desenho sem JavaScript | o archify grava o SVG pronto no HTML; o que depende do script é a interface do visualizador, e o CT-B01 confere que ela montou |
| token obrigatório na linha nova do catálogo | medido no commit `0f38e2f`: a F-118 ("Voltar ao topo") não tem token e está certa; a troca entre linhas novas sem token é a lacuna R1-M8 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | catálogo em paridade, a mesma feature e o mesmo estado em cada número (tokens nas linhas novas), uma linha por F-nn, origem do glossário | R1 | EP + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R1-M1 a M7 |
| CT-02 | só se cita F-nn que o catálogo tem, e ninguém lista feature por fora dele, nem só pelos nomes | R2 | EP + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R2-M1 a M5 |
| CT-03 | número da base fica com a feature, ou removido com motivo, e não volta | R3 | estado × operação | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R3-M1 a M6 |
| CT-04 | os novos começam na F-72, em sequência, sem sufixo | R4 | BVA 3-valores | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R4-M1, M2, M3 |
| CT-05 | README da base e o publicado, e todas as páginas do site, chegam ao catálogo; os seis itens novos e os itens novos do README, pelo termo | R5 | EP por fonte | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R5-M1, M2, M4, M6, M9 |
| CT-06 | toda F-nn é entregue por peça desenhada da sua origem, e todo caminho do "onde fica" resolve sem tenant | R6 | EP por origem | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R6-M1, M2 |
| CT-07 | a rule está no índice, alcança onde a feature nasce e manda o número só crescer | R7 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R7-M1 a M4 |
| CT-08 | toda peça existe, tem o tipo da sua classe e aparece uma vez | R8 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R8-M1 a M6 |
| CT-09 | toda prova resolve no commit que publica, fora de comentário pelo tipo de arquivo, sem número de linha; todas as ligações conferidas | R9 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R9-M1 a M8, R13-M3 |
| CT-10 | a prova é a da ligação: arquivo da origem, trait que ela usa, provider, método da lista de contrato, par de mapeamento ou arquivo do kit que liga a peça de pacote | R10 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R10-M1 a M12 |
| CT-11 | sem tenant: registro (com as páginas da configuração) → modelo, e o modo do modelo → registro | R11 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R11-M1, M4, M5, M6 |
| CT-12 | com tenant: registro → modelo, e o modo do modelo → registro | R11 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R11-M3, M4 |
| CT-13 | a guarda do registro reprova esquecer (inclusive a página da configuração), sobrar e trocar de modo | R11 | controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R11-M1, M2, M6 |
| CT-14 | toda ligação desenhada em diagrama publicado; só aresta conta, e o agrupamento e a porta nomeiam os membros | R12 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R12-M1 a M6 |
| CT-15 | a página de cada idioma leva, por `<a href>` relativo, a todo diagrama, e só aos que existem | R15 | EP | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R15-M1 a M5 |
| CT-16 | textos de diagrama em pt e en, traduzidos: termo do glossário com o en dele, frase sem palavra do pt; título igual só se é nome de peça ou de comando, marcado | R16 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R16-M1 a M7 |
| CT-17 | grade painel × modo inteira, compartilhado marcado e só ele, sem diagrama do outro modo nem de outro painel | R17 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R17-M1, M3, M4, M5, M7 |
| CT-18 | painel novo sem diagrama reprova a grade | R17 | controle | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R17-M2 |
| CT-19 | só se compartilha o que é igual, o igual não se duplica, e a ligação desenhada vale no modo do diagrama | R18 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R18-M1 a M4 |
| CT-20 | cada tipo e o comparativo têm uso ou motivo | R19 | produto cartesiano | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R19-M1 |
| CT-21 | toda célula tipo × painel × modo tem diagrama ou motivo traduzido, e só o diagrama sem peça registrada é do kit inteiro | R19 | produto cartesiano + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R19-M2 a M8 |
| CT-22 | comparativo do mesmo painel, de sem para com tenant, entre diagramas do mapa publicados | R20 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R20-M1, M2, M3 |
| CT-23 | nenhuma dependência nova de visualização nos quatro campos do `site/package.json`, e `site/mapa/` só com os arquivos do mapa | R21 | EP (lista fechada) + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R21-M1, M2, M4, M6, M7 |
| CT-24 | há fluxo publicado, e todo passo de `workflow`, `sequence` e `dataflow` é peça do modelo | R22 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R22-M1, M2 |
| CT-25 | o mapa nasce no build, o conferidor do mapa roda depois do de acessibilidade e antes do envio, e o build do Pages só roda na `main` | R23 | EP por workflow | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R23-M1 a M4 |
| CT-26 | o README de cada idioma leva ao mapa do mesmo idioma | R25 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R25-M1, M2 |
| CT-27 | os blocos Mermaid da base ficam onde estavam | R26 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R26-M1, M2, M3 |
| CT-28 | todo pacote instalado está na referência e chega ao catálogo, ou à lista dos sem feature; a referência só lista pacote instalado | R27 | EP por partição de pacote + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R27-M1 a M6 |
| CT-29 | sem tenant, toda peça registrada entrega F-nn ou está na lista com o motivo | R28 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R28-M1, M2, M3, M6 |
| CT-30 | com tenant, toda peça registrada entrega F-nn ou está na lista com o motivo | R28 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R28-M2 |
| CT-31 | marcos da linha do tempo batem com o CHANGELOG, com o título visível na seção da versão, e todo tempo tem data e fonte | R29 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R29-M1 a M7 |
| ~~CT-32~~ | ~~o modelo guarda a condição e a âncora de toda peça que não abre nesta configuração~~ — estacionado — Q11 (ADV-17, ADV-31) | ~~R30~~ | — | — | — | — | — |
| ~~CT-33~~ | ~~todo diagrama mostra a condição da peça condicional e não a dá como disponível no modo errado~~ — estacionado — Q11 (ADV-31) | ~~R30~~ | — | — | — | — | — |
| CT-34 | a página do mapa declara as duas exceções da P-11, a do comparativo e o limite da varredura, nos dois idiomas | R33 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R33-M1 a M5 |
| CT-35 | a guarda das fontes reprova o item casado com a feature errada, o item novo sem F-nn, a página escondida na lista e o motivo de enfeite | R5 | controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R5-M3 a M9 |
| CT-36 | a guarda da origem reprova a F-nn entregue pela peça errada, a removida que ainda é entregue e o caminho que não resolve | R6 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R6-M3 a M7, M9 |
| CT-37 | sem tenant, os diagramas de arquitetura e a árvore de registro de cada painel desenham o registro dele nesse modo, fora a peça condicional | R34 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R34-M3 |
| CT-38 | com tenant, os diagramas de arquitetura e a árvore de registro de cada painel desenham o registro dele nesse modo | R34 | tabela de decisão + controles | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R34-M1, M2 |
| CT-39 | toda transição de fluxo tem âncora que resolve, todo estado de `lifecycle` também, e a ordem no mesmo método é a do código | R35 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R35-M1, M2, M3, R22-M3 |
| CT-40 | toda aresta de mapa é ligação do modelo no mesmo sentido, e o diagrama de painel só desenha, como peça, o que é dele, do kit inteiro ou porta | R36 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R36-M1 a M5 |
| CT-41 | toda classe de `app/` fora de `app/Filament` entrega F-nn ou está na lista com o motivo, e comando `kit:*` nunca está na lista | R28 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R28-M4 a M7 |
| CT-42 | a falha do conferidor do mapa reprova o job, e o build do Pages só roda na `main` | R23 | controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R23-M5, M7 |
| CT-43 | a guarda da grade reprova a célula com diagrama do outro modo ou de outro painel, e a marca de compartilhado num diagrama de um modo só | R17 | controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R17-M3, M6, M7 |
| CT-44 | com tenant, todo caminho do "onde fica" com organização resolve no roteador | R6 | EP (idioma) | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R6-M9 |
| CT-45 | toda marca de disponibilidade publicada é a do código: chave, valor de fábrica ou modo | R37 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R37-M1, M2, M3 |
| CT-B01 | cada diagrama, inclusive o comparativo, abre com as peças e os rótulos da fonte, a interface do visualizador montada e a F-nn no desenho da peça, sem erro nem exceção, sem recurso de fora (no `05`) | R24, R21, R6 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R24-M1 a M4, M6, R21-M5, R6-M10 |
| CT-B02 | cada ligação desenhada tem o seu item de prova inteiro num card do painel lateral, contado no DOM, e as arestas do DOM são as da fonte; fora o comparativo (no `05`) | R13, R12 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R13-M1, M2, M4 a M8, R12-M7 |
| CT-B03 | o leitor busca a peça, e o passaporte mostra as provas dela, com a linha do trecho no commit do build, numa amostra por `architecture`; sem erro na interação (no `05`) | R14, R24 | EP + amostra | G4 | browser | `site/verifica-mapa.mjs` | R14-M1 a M6, R24-M7 |
| CT-B04 | a interface do visualizador fala o idioma da página, com critério no pt e no en, também com a busca e o passaporte abertos e sem erro; só a casca do comparativo fica em inglês (no `05`) | R31, R24 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R31-M1 a M6, R24-M7 |
| CT-B05 | a amostra de acessibilidade cobre cada tipo, idioma e tema, e o comparativo no escuro (no `05`) | R32 | amostra estratificada | G4 | browser | `site/verifica-mapa.mjs` | R32-M4, M5, M6 |
| CT-B06 | a tolerância reconhece os dois defeitos pelo alvo, reprova o resto e a violação de alvos mistos, e a reprovação vira código de saída (no `05`) | R32, R23 | controle sintético | G4 | browser | `site/verifica-mapa.mjs` | R32-M1, M2, M3, M7, R23-M6 |
| CT-B07 | o catálogo pt que o kit entrega — as 390 chaves embutidas em cada HTML pt e as 96 de render, na fonte — não copia o texto en, fora a lista fechada das 14 chaves medidas e das só de marcadores (no `05`) | R31 | controles | G4 | browser | `site/verifica-mapa.mjs` | R31-M7, M8, M9 |
