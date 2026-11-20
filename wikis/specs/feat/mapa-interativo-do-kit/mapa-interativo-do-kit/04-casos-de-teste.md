# Casos de Teste — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md` (RQ-01 a RQ-26, P-01 a P-12, Adendo 1, perguntas Q6, Q7, Q9, Q11, Q12, Q13 e Q16) · Plano: `01-plano-acao.md` (só paths, rotas, stack e `## Superfície de UI`, recebidos por trecho no despacho) · `02`: só `## Superfície Livewire` ("não se aplica")
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação: ela ainda não existe.
> Base congelada: commit `d9608c5a` (2026-11-19). Todo "da base" deste arquivo foi medido nele, nesta derivação.
> **Revisão adversarial: feita por decisão da sessão, e fechada na quarta rodada.** O perfil não a exigia: nenhuma área é `completo` e nenhuma tem Impacto 3 (o motivo está abaixo da tabela). Foram 34 achados, ADV-01 a ADV-34, e o que virou cada um está em `## Revisão adversarial`. O fechamento criou cenário novo, então cabe uma re-revisão, uma vez só.
> *(alterado em 2026-11-20: rodada de revisão do step 7 — perguntas renumeradas para Q6 a Q15 e respondidas pela sessão; regras R27 a R33, CT-28 a CT-34 e CT-B04 a CT-B06 novos; os CT-B saem do conferidor de acessibilidade para um conferidor próprio, `site/verifica-mapa.mjs`)*
> *(alterado em 2026-11-20: terceira rodada — as duas perguntas novas da rodada anterior viraram Q16 e Q17 e foram fechadas; a R27 e o CT-28 partem dos manifestos e conferem as duas direções, com a referência de pacotes)*
> *(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial e P-12; regras R34 a R36, CT-35 a CT-43 e CT-B07 novos; R30, CT-32, CT-33 e R24-M5 estacionados na Q11; CT-B02 e CT-B03 reescritos pelos fatos de plataforma medidos pela sessão)*
> *(alterado em 2026-11-20: ajuste da quarta rodada — as duas perguntas novas da rodada viraram Q18 e Q19, decididas pela sessão no `01` (D25 e D26); a R10 ganha a quinta forma; a R5 casa o termo por palavra inteira; a R6 troca a regra da rota (40 linhas nos dois idiomas); a leitura da porta fica fechada pela P-10)*

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
- Cenários: 41 · Regras: 35 · Mutantes previstos: 181 · Sem matador: 4 (mais 7 CT-B no `05`, nas regras R13, R14, R21, R24, R31 e R32; os mutantes delas estão contados aqui)
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
| S | <ul><li>Em `site/mapa/`: o modelo de peças, o modelo de ligações e um arquivo por diagrama; a tradução da interface do visualizador; o gerador e o download do archify.</li><li>A página "Mapa interativo" em pt e en, com navegação, redirecionamento e stub.</li><li>O catálogo (a página Roteiro de features em pt e en), a referência de pacotes instalados e o README em pt e en.</li><li>A rule do catálogo e a linha dela no índice.</li><li>`site/package.json`, o conferidor novo `site/verifica-mapa.mjs` e o conferidor de acessibilidade.</li><li>`pages.yml` e o job `site` do `ci.yml`.</li><li>Nenhum arquivo de `app/` muda (RQ-15 e a Superfície Livewire do `02`).</li></ul> | CT-01 a CT-31, CT-34 a CT-43 (CT-32 e CT-33 estacionados na Q11) |
| F | <ul><li>Desenhar peças e ligações por painel e por modo.</li><li>Provar cada ligação com o arquivo e o trecho.</li><li>Cobrir toda feature do catálogo e toda peça registrada.</li><li>Mostrar a grade painel × modo e os tipos. A condição de disponibilidade está estacionada na Q11.</li><li>Ancorar no código cada transição de fluxo.</li><li>Unificar e numerar o catálogo.</li><li>Falhar quando o código diverge (P-03).</li><li>Publicar no build do commit.</li><li>Deixar o leitor buscar a peça e ler a prova, na língua da página.</li><li>Passar pela conferência de acessibilidade por amostra.</li></ul> | CT-03, CT-06, CT-08 a CT-14, CT-17, CT-20, CT-25, CT-29 a CT-31, CT-36 a CT-41, CT-B01 a CT-B07 |
| D | <ul><li>Peças e ligações: 243 e 496 só no `/admin`.</li><li>Os diagramas.</li><li>Na base: 74 F-nn por idioma (F-01 a F-71, mais F-03a, F-03b e F-03c), 46 itens de feature do README por idioma, 39 páginas do site por idioma, 91 pacotes nos manifestos e 87 na referência por idioma (os quatro que faltam a entrega acrescenta, Q16), 141 versões com data no CHANGELOG, mais o `[Unreleased]`, sem data, e 135 classes de `app/` fora de `app/Filament` (P-12). Das 74 linhas do catálogo, 40 trazem no "onde fica" um caminho de rota sem "→", nos dois idiomas.</li><li>Provas: arquivo do kit versionado (válida), e as inválidas — ausente, vazia, só de espaços, só em comentário, fora do commit (`vendor/` ou fora do git).</li><li>Peças de disponibilidade condicional: pelo modo (`TenantResource`) e pela chave (`AgenteIaResource`). A marca delas está estacionada na Q11.</li><li>Os modos (sem e com tenant), os 10 tipos e o comparativo, os idiomas pt e en, os temas claro e escuro.</li><li>Cardinalidade 0/1/N: painel sem diagrama, feature sem peça, peça registrada ou classe de `app/` sem F-nn, ligação sem diagrama, transição sem âncora, célula sem diagrama nem motivo, amostra vazia.</li><li>Dado de outro tenant não se aplica: o modo é configuração, não dado.</li></ul> | CT-01, CT-05, CT-06, CT-09, CT-14, CT-17, CT-21, CT-28, CT-29, CT-31, CT-35, CT-39, CT-41, CT-B05 |
| I | <ul><li>A página estática `/{pt,en}/referencia/mapa-interativo/` e os HTML `/mapa/{pt,en}/{slug}.html`.</li><li>O README no GitHub, o catálogo, a referência de pacotes e a rule, que os agentes leem pelo índice.</li><li>O registro vivo dos painéis: `Filament::getPanels()` e os getters de cada painel; o roteador da aplicação, para o "onde fica" do catálogo.</li><li>Os arquivos citados pelas provas e pelas âncoras de fluxo, e os links de evidência para o repositório do `support.source` do `composer.json`.</li><li>O `CHANGELOG.md`.</li><li>`npm run build` e os dois workflows.</li></ul> | CT-07, CT-11, CT-12, CT-15, CT-25, CT-26, CT-28, CT-31, CT-36, CT-39, CT-42, CT-B03 |
| P | <ul><li>PHP 8.4, Laravel 13.42, Filament 5.10.2 (getters conferidos no vendor) e Pest 5.3.7.</li><li>Node 24, Astro 7.3.3 + Starlight 0.42.2, Playwright 1.63 e axe-core 4.13.</li><li>archify 3.2.0 no commit `4478129`, segundo o README e o `schemas/README.md` do archify nesse commit: não tem catálogo pt (tem en, zh-CN, zh-TW, es, ko e ar); o `meta.locale` escolhe a interface e o `lang`, mas não traduz o texto escrito; a prova é por nó (`sources`, de 1 a 3), sem prova por aresta; a casca do comparativo existe só em inglês (P-11); e há dois defeitos de acessibilidade tolerados (P-11).</li><li>Medido pela sessão num HTML do archify, no commit fixado, no Chromium:<ul><li>o passaporte de uma peça em foco mostra o rótulo, o tipo, "origem verificada {repositório} @ {commit}", as provas da própria peça (até três) como links para o GitHub no commit, e só as contagens das ligações;</li><li>os cards ficam no painel lateral (`.reader-rail .card`), sempre presentes;</li><li>o DOM traz `data-node-id`, `data-node-label`, `data-edge-from`, `data-edge-to` e `data-edge-id`, e o `lang` do documento segue o `meta.locale`;</li><li>nenhuma requisição externa e nenhum erro.</li></ul></li><li>O `examples/locales/pt.json` do archify (461 chaves) fica fora do manifesto dos catálogos embutidos. O kit entrega o pt pelas traduções do diagrama, e o en é o `locales/en.json` (486 chaves).</li><li>Nos mapas de arquitetura, a aresta é direta, de agrupamento ou de porta.</li><li>GitHub Pages sob a base `/filament-starter-kit-easy`.</li><li>Desenvolvimento no Windows (caminho com `\`, fim de linha CRLF) e CI no Linux.</li></ul> | CT-09, CT-14, CT-16, CT-40, CT-B01 a CT-B07 |
| O | <ul><li>O leitor do site avalia o kit.</li><li>O mantenedor cria feature: número e rule.</li><li>O agente de IA lê a rule pelo índice.</li><li>O CI roda em todo PR e publica no Pages.</li><li>Volume: nenhum painel cabe num desenho só (P-10).</li><li>Uso indevido: prova copiada de outra ligação ou tirada de outro arquivo, prova que só casa em comentário, número reaproveitado, aresta inventada ou invertida, catálogo pt com o texto en, conferidor que não reprova o job, tolerância de acessibilidade larga demais.</li></ul> | CT-03, CT-07, CT-10, CT-14, CT-40, CT-42, CT-B06, CT-B07 |
| T | <ul><li>O mapa reflete o commit que publica (P-07), e a linha do link de prova é a desse commit.</li><li>A suíte do kit roda em todo PR, e o job `site` só roda quando o PR toca `docs/` ou `site/`. Por isso as guardas de prova, de registro e de catálogo moram na suíte do kit.</li><li>A numeração só cresce (P-08), e o número removido continua removido nas entregas seguintes.</li><li>Envelhecem: o trecho que some do código, a âncora de fluxo, o recurso novo no painel, a classe nova em `app/` (P-12), o pacote atualizado, a versão nova do CHANGELOG e o tempo medido.</li></ul> | CT-03, CT-04, CT-09, CT-11, CT-25, CT-29, CT-31, CT-39, CT-41, CT-B02 |

## Mapa de Regras

*(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial; R34 a R36 novas; R30 estacionada na Q11)*

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O catálogo é um só, com as mesmas F-nn em pt e em en, a mesma feature em cada número, uma linha por F-nn e uma origem do glossário em cada | A (padrão) | RQ-21, RQ-22, RQ-04, RQ-05, P-02 | EP + controles | CT-01 |
| R2 — README, páginas do site e mapa só citam F-nn que o catálogo tem, e nenhum deles lista feature por fora dele | A (padrão) | RQ-22, RQ-21, P-08 | EP + controles | CT-02 |
| R3 — Toda F-nn da base continua com a sua feature, ou fica marcada como removida com o motivo, e a removida não volta | A (padrão) | P-08, RQ-22 | estado × operação + controles | CT-03 |
| R4 — A feature nova recebe o próximo número livre: a F-72, e as seguintes em sequência, sem buraco e sem sufixo novo | A (padrão) | P-08 | BVA 3-valores (escalada) | CT-04 |
| R5 — Todo item de feature do README da base e toda página do site correspondem a ao menos uma F-nn — os seis itens que a base não cobre, pelo termo fixado —, ou a página está na lista das que não descrevem feature, com o motivo | A (padrão) | RQ-19, RQ-04, RQ-05, P-02 | EP por fonte + controles | CT-05, CT-35 |
| R6 — Toda F-nn vigente é entregue por peça desenhada da origem que ela declara — do pacote que a linha nomeia, ou, no código próprio, da rota do "onde fica" —, e nenhuma peça entrega F-nn removida | A/B (padrão) | RQ-04, RQ-05, RQ-21, P-03, P-10, P-08 | EP por origem + tabela de decisão + controles | CT-06, CT-36 |
| R7 — Manter o catálogo único é uma rule indexada, que alcança os lugares onde a feature nasce e manda o número só crescer | F (mínimo) | RQ-23, P-08 | EP | CT-07 |
| R8 — Toda peça do modelo é peça com nome que existe, do tipo que a classe dela é, dentro do conjunto fechado, e aparece uma vez | B (padrão) | RQ-24, P-03 | EP + tabela de decisão (classe × tipo) + controles | CT-08 |
| R9 — Toda prova resolve no commit que publica: o arquivo é versionado no kit, o trecho está nele, fora de comentário, e a prova não guarda número de linha | B (padrão) | RQ-24, P-03, P-04, P-07 | EP (uma partição inválida por linha) + controles | CT-09 |
| R10 — A prova é a da ligação: está no arquivo da origem, num trait ou concern que a origem usa, no provider do painel (descoberta, plugin ou configuração), no método de contrato do alvo ou, quando a origem é peça de pacote, num arquivo do kit que nomeia a origem; o trecho é o dessa ligação; e as duas pontas são peças | B (padrão) | RQ-24, P-03, P-07 | EP (forma da prova × arquivo) + controles | CT-10 |
| R11 — Tudo que o painel registra tem ligação de registro no modelo, e o modelo não dá a um modo o que o painel não registra nele | B (padrão) | P-05, RQ-08, RQ-10, RQ-11, P-03 | tabela de decisão (3 × 2 × 5, nas duas direções) + controles | CT-11, CT-12, CT-13 |
| R12 — Toda ligação do modelo está desenhada em diagrama publicado, direto ou por agrupamento que nomeia os membros num card do diagrama ou no rótulo dele; menção em texto não conta | B (padrão) | P-10, RQ-08 | EP (forma da aresta) + controles | CT-14 |
| R13 — Toda ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros — traz a prova, com o arquivo e o trecho, num card do painel lateral do próprio diagrama, à vista sem foco | B (padrão) | P-04, P-07, RQ-08 | rastreio exaustivo no site construído | CT-B02 (no `05`) |
| R14 — O leitor acha a peça pela busca, e o passaporte dela mostra as provas da própria peça, com o link para o repositório do kit no commit do build | B (padrão) | RQ-07, P-04, P-07 | EP (idioma) | CT-B03 (no `05`) |
| R15 — A página de cada idioma leva, por `<a href>` relativo, a todo diagrama do seu idioma, e só a diagrama que existe | C (padrão) | RQ-03, P-02 | EP | CT-15 |
| R16 — Todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido; o título só fica igual quando é o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código | C (padrão) | P-02, RQ-03 | EP + controles | CT-16 |
| R17 — A grade painel × modo é inteira: cada painel do registro tem diagrama próprio em cada modo, o compartilhado vem marcado, e a célula não leva a diagrama do outro modo nem de outro painel | C (padrão) | RQ-10, RQ-11, P-06, P-09 | tabela de decisão (3 × 2) + controles | CT-17, CT-18, CT-43 |
| R18 — Só se compartilha o que é igual nos dois modos, e o que é igual não se duplica | C (padrão) | P-09, RQ-11 | tabela de decisão + controles | CT-19 |
| R19 — Cada um dos dez tipos e o comparativo têm uso ou motivo, e cada combinação tipo × painel × modo tem diagrama ou motivo ao lado | C (padrão) | RQ-06, RQ-25, P-06, P-02 | produto cartesiano fechado (63 células por idioma) + controles | CT-20, CT-21 |
| R20 — Todo comparativo publicado vai do modo sem tenant (antes) para o com tenant (depois) do mesmo painel | C (padrão) | RQ-25, RQ-11 | EP + controles | CT-22 |
| R21 — Nenhuma dependência nova de visualização no site: o `site/package.json` fica com as dependências da base, e o HTML de cada diagrama não carrega recurso de fora dele | C (padrão) | RQ-25 | EP (lista fechada) + rastreio no site construído | CT-23, CT-B01 (no `05`) |
| R22 — Há fluxo publicado, e, nos tipos `workflow`, `sequence` e `dataflow`, todo passo percorre peça do modelo | C (padrão) | RQ-09, P-03 | EP (tipo de fluxo) + controles | CT-24 |
| R23 — O mapa nasce no build do commit que publica, os dois fluxos de CI o conferem com o conferidor do mapa antes de enviar, e a falha do conferidor reprova o job | D (padrão) | P-07, RQ-03, RQ-17, P-11 | EP por workflow + controles | CT-25, CT-42 |
| R24 — Todo diagrama publicado abre desenhado, com as peças do arquivo, sem erro de console nem exceção, no idioma da página | D (padrão) | RQ-07, RQ-03, P-02 | rastreio exaustivo no site construído | CT-B01 (no `05`) |
| R25 — O README de cada idioma leva ao mapa do mesmo idioma | E (mínimo) | RQ-02, P-02 | EP | CT-26 |
| R26 — Os diagramas Mermaid da base continuam onde estavam | E (mínimo) | P-01, RQ-25 | EP | CT-27 |
| R27 — Todo pacote instalado está na referência de pacotes e numa F-nn de origem pacote, ou na lista dos sem feature própria, com o motivo; e todo pacote que a referência lista está instalado | A (padrão) | RQ-19, RQ-04, P-03 | EP por partição de pacote + controles | CT-28 |
| R28 — Toda peça que um painel registra — recurso, página, widget e plugin —, nos dois modos, e toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está na lista da guarda das peças e classes sem feature própria, com o motivo | A/B (padrão) | RQ-20, RQ-04, RQ-05, P-05, P-12 | tabela de decisão (3 × 2 × 4) + EP (classe de `app/`) + controles | CT-29, CT-30, CT-41 |
| R29 — Cada marco da linha do tempo cita uma versão do CHANGELOG com a data dela e um termo da seção dessa versão, e cada tempo medido traz a data e a fonte da medição | C (padrão) | P-03, P-06 | EP (uma partição inválida por linha) + controles | CT-31 |
| ~~R30 — Toda peça de disponibilidade condicional guarda no modelo a condição, com a âncora do código que decide, e mostra a condição em todo diagrama que a desenha~~ | estacionada — Q11 (ADV-17, ADV-31) | RQ-04, RQ-05, RQ-11, P-03, P-09 | — | ~~CT-32, CT-33~~ |
| R31 — A interface do visualizador está no idioma da página, e só a casca do comparativo fica em inglês; o catálogo pt que o kit entrega não copia o texto en, fora a lista fechada com o motivo | D (padrão) | P-11, P-02, RQ-03 | rastreio exaustivo no site construído + controles | CT-B04, CT-B07 (no `05`) |
| R32 — A conferência de acessibilidade audita a amostra inteira e tolera só os dois defeitos do archify, pela regra e pelo elemento | D (padrão; técnica escalada) | P-11, RQ-03, RQ-07 | amostra estratificada + controle sintético | CT-B05, CT-B06 (no `05`) |
| R33 — A página do mapa declara, nos dois idiomas, as duas exceções da P-11 e o limite da varredura da P-12 | D (padrão) | P-11, P-12, P-02 | EP (idioma) | CT-34 |
| R34 — Os diagramas de arquitetura de um painel que valem num modo desenham, juntos, todo o registro do painel nesse modo, e nada que ele não registra nesse modo | C (padrão) | P-09, RQ-11, RQ-10, P-05 | tabela de decisão (3 × 2) + controles | CT-37, CT-38 |
| R35 — Toda transição de fluxo publicado tem âncora que resolve; no `lifecycle`, todo estado também; e duas transições seguidas ancoradas no mesmo método estão na ordem do código | C (padrão) | P-03, RQ-09 | EP + controles | CT-39 |
| R36 — O que um diagrama desenha está no modelo: toda aresta é ligação do modelo no mesmo sentido — direta, de agrupamento ou de porta —, e o diagrama de painel só desenha peça do painel, peça do kit inteiro ou caixa de porta | B (padrão) | P-03, RQ-24, P-10, RQ-10 | EP (forma da aresta) + controles | CT-40 |

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
- RQ-11 — cobertura parcial: CT-11 a CT-13, CT-17 a CT-19, CT-22, CT-37, CT-38, CT-43; recorte {a marca da peça de disponibilidade condicional, e em que diagrama de modo ela entra} aberto (Q11)
- P-09 — cobertura parcial: CT-17 a CT-19, CT-37, CT-38, CT-43; recorte {se a disponibilidade por modo conta como diferença de estrutura para compartilhar o diagrama} aberto (Q11)
- RQ-04 — cobertura parcial: CT-01, CT-05, CT-06, CT-28 a CT-30, CT-35, CT-36, CT-41; recorte {a marca da peça de feature opcional, e em que diagrama de modo ela entra} aberto (Q11)
- RQ-05 — cobertura parcial: CT-01, CT-05, CT-06, CT-29, CT-30, CT-35, CT-36, CT-41; recorte {a marca da peça de feature opcional, e em que diagrama de modo ela entra} aberto (Q11)
- P-03 — cobertura parcial: CT-06, CT-08 a CT-13, CT-24, CT-28, CT-31, CT-36, CT-39, CT-40; recorte {o que o mapa afirma sobre a peça condicional} aberto (Q11); recorte {o trecho da transição do `lifecycle` faz a transição} lacuna declarada (R35-M5, D25)

*(alterado em 2026-11-20: ADV-32)* A ordem dos passos saiu do recorte da Q6. A P-03 vale para todo fluxo publicado, qualquer que seja a lista. Por isso a âncora de cada transição e a ordem no mesmo método são da R35, e só a ordem entre arquivos ou métodos diferentes ficou como lacuna declarada (R35-M4).

**Estacionado na Q11 (ADV-17 e ADV-31).**
- **Por que estaciona.** A Q11 tem três leituras: a peça condicional entra marcada e desenhada só onde está disponível; entra marcada e desenhada onde está registrada; ou entra desenhada sem marca. Nenhuma afirmação sobre a marca vale nas três.
- **O que saiu do conjunto vigente, com os IDs reservados:**
  - a R30, com o CT-32 (o modelo guarda a condição e a âncora do código que decide) e o CT-33 (todo diagrama que desenha a peça mostra a condição);
  - a metade de condição do CT-B01 (o passo 4 do `05`);
  - o R24-M5.
- **Onde não aparecem.** Nenhum Gherkin nem linha vigente do Índice os define; a linha da R30 no Mapa e as do CT-32 e do CT-33 no Índice estão riscadas.
- **Quando voltam.** Com a resposta da Q11, junto com o ADV-17, que pede a detecção também no modo com tenant, cada chave com o valor oposto ao de fábrica e a linha da âncora que não resolve.

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
| G2 — modelo e diagramas do mapa × aplicação de pé, sem tenant (Kit) | R6, R8, R9, R10, R11 (CT-11, CT-13), R12, R15, R16, R17, R18, R19, R20, R22, R28 (CT-29, CT-41), R29, R34 (CT-37), R35, R36 | Pest feature HTTP | **nova**: `tests/Kit/MapaInterativoTest.php`. Moldes: o `[CT-25]` de `tests/Kit/SiteDeDocumentacaoTest.php`, que confronta o README com `Filament::getPanel()`; o `conferirDg10()` de `tests/Kit/GuardasDosDiagramasTest.php`, para o controle em cópia | o `Então` afirma o modelo e os arquivos de diagrama contra o que os painéis registram, contra o roteador da aplicação, contra os arquivos de código citados e as classes de `app/`, contra o CHANGELOG e contra a página. Roda em todo PR, e o job `site` não (T) | sessão, 2026-11-20 — convenção do projeto: guarda que confronta documentação com o painel vivo em `tests/Kit` (molde do `[CT-25]` do `SiteDeDocumentacaoTest`) |
| G3 — registro dos painéis com tenant (Tenancy) | R11 (CT-12), R28 (CT-30), R34 (CT-38) | Pest feature HTTP | **nova**: `tests/Tenancy/MapaInterativoTenancyTest.php`. Molde: `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` | o modo com tenant só existe com o `Tests\TenancyTestCase`, que fixa `kit.tenancy.enabled` antes do boot | sessão, 2026-11-20 — convenção do projeto: o modo com tenant só existe na suíte `Tenancy` (molde do `DiagramasDaArquiteturaTenancyTest`) |
| G4 — site construído (conferidor do mapa, Node + Playwright) | R13, R14, R21 (CT-B01), R24, R31, R32 | browser | **nova** *(alterado em 2026-11-20: era "existente — `site/verifica-acessibilidade.mjs`")*: `site/verifica-mapa.mjs`, sobre o `astro preview`. Os dois workflows o rodam logo depois do conferidor de acessibilidade, com a mesma prévia de pé; no `pages.yml`, antes do envio do artefato. Molde: `site/verifica-acessibilidade.mjs`, inclusive os controles sintéticos | o HTML do mapa só existe depois do build (o `site/dist` é ignorado pelo git), o visualizador desenha no navegador, e acessibilidade e idioma da interface só o navegador mede. O catálogo en do archify só existe depois do build, que baixa o archify, e por isso o CT-B07 também mora aqui. O `pest-plugin-browser` não serve site estático (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`). O arquivo é próprio porque o conferidor de acessibilidade já tem `[CT-B01]` e `[CT-B02]` de outra wiki. O `ids-ct.sh` acusa estes CT-B como sem teste, por desenho: ele só lê `it()` e `test()` | sessão, 2026-11-20 — convenção do projeto: o site construído é conferido por Node + Playwright no CI (molde do `site/verifica-acessibilidade.mjs`); arquivo próprio pela D17 do `01` |

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
| Q11 | requisito | RQ-04, RQ-05, RQ-11, P-03, P-09 | aberta | recorte bloqueado e estacionado (ADV-17, ADV-31): a R30 (CT-32, CT-33), a metade de condição do CT-B01 e o R24-M5 saíram do conjunto vigente, porque nenhuma afirmação sobre a marca vale nas três leituras |
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
- Trecho fora de comentário: em arquivo PHP, usar `codigoSemComentario()` (`tests/Pest.php:codigoSemComentario:2188`).
- "No commit que publica" quer dizer que o arquivo está no índice do git. Invocar o `git` exige a sentinela (`[CT-26]` de `tests/Kit/RedeDeDocumentacaoTest.php`).
- Arquivos que existem no disco e estão fora do commit:
  - `bootstrap/cache/packages.php`, que o Composer gera e o `bootstrap/cache/.gitignore` ignora;
  - todo arquivo de `vendor/`.
- Quarto painel: `painelRegistradoEmTeste('financeiro')` (`tests/Pest.php:painelRegistradoEmTeste:812`). O registro morre com o container do caso.
- A guarda das classes de `app/` recebe a lista de classes como parâmetro. Assim o CT-41 acrescenta uma classe que não existe, sem tocar na árvore.
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

## Regra R1 — O catálogo é um só, com as mesmas F-nn em pt e em en, a mesma feature em cada número, uma linha por F-nn e uma origem do glossário em cada

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
```

As origens válidas são as três do glossário (Catálogo de features): pacote, código próprio e pacote com customização própria. A linha "publicado" afirma que cada F-nn declara **exatamente uma** delas.

*(alterado em 2026-11-20: ADV-09)* A mesma F-nn é a mesma feature nos dois idiomas quando as duas linhas declaram a mesma origem, nomeiam o mesmo pacote, levam à mesma página (a do outro idioma, no espelho de `docs/`) e dão os mesmos caminhos de rota no "onde fica". A linha da F-72 é de controle: na cópia, o link da F-72 en é trocado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a unificação é feita só no pt, e o en fica sem F-nn que o pt tem | CT-01 | linha "en sem a F-69": reprova nomeando F-69 e en; o mutante aceita |
| M2 | origem como texto livre ("biblioteca", "pacote + código") | CT-01 | linha "origem biblioteca": reprova nomeando a F-30; o mutante aceita |
| M3 | a coluna de origem é opcional, e a F-nn sem origem passa | CT-01 | linha "não declara origem": reprova; o mutante aceita |
| M4 | duas linhas com o mesmo número (a feature nova copiada de uma linha antiga sem trocar o número) | CT-01 | linha "F-30 em duas linhas": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-09) | com pt e en editados em paralelo, os números novos saem em ordem diferente: a F-72 do pt é o dock, e a do en, o tutorial | CT-01 | linha da F-72: reprova; o mutante aceita, porque confere só o conjunto de números |

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
```

A varredura cobre o README, `docs/` e `site/mapa/`. Ficam de fora `tests/` e `app/`, onde "F-nn" também nomeia achado de revisão, como "F-01 da conformidade Blueprint" (`.ai/rules/kit.md`). Ali a regex daria falso positivo.

*(alterado em 2026-11-20: ADV-20)* "Lista ou tabela de features" tem dois sinais:
- **Nas páginas fora do catálogo:** linha de tabela ou item de lista que cita F-nn. Na base não há nenhuma, e as duas citações de `docs/`, em `autenticacao/login-social.md`, estão em prosa.
- **Nos arquivos do mapa:** a F-nn junto do nome que o catálogo dá a ela. A peça aponta a F-nn só pelo número.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o mapa guarda uma lista própria de features, pelo nome, ao lado do catálogo *(alterado em 2026-11-20: ADV-20, a asserção antiga não divergia)* | CT-02 | linhas "tabela de três F-nn na página do mapa" e "lista de features num arquivo do mapa": reprova nomeando a página ou o arquivo; o mutante aceita |
| M2 | uma página do site cita número que o catálogo não tem (inventado, ou de antes da unificação) | CT-02 | linha "F-99": reprova nomeando a página; o mutante aceita |
| M3 | o README en cita F-nn que só o catálogo pt ganhou | CT-02 | linha `README.en.md`: reprova; o mutante aceita |
| M4 | a peça aponta a feature pelo nome, e não pela F-nn | CT-02 | linha "peça cita a feature pelo nome": reprova nomeando a peça; o mutante aceita |

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

---

## Regra R6 — Toda F-nn vigente é entregue por peça desenhada da origem que ela declara — do pacote que a linha nomeia, ou, no código próprio, da rota do "onde fica" —, e nenhuma peça entrega F-nn removida

> `RQ-04`, `RQ-05`, `RQ-21`, `P-03`, `P-10`, `P-08` · perfil **padrão** · técnica: **EP por origem** (as três partições do glossário) + tabela de decisão (F-nn × pacote nomeado × pacote da peça; removida × entregue) + controles em cópia

```gherkin
  Regra: A demonstração cobre todas as features, as de pacote e as de código próprio

    @premissa
    Esquema do Cenário: [CT-06] toda F-nn é entregue por peça desenhada, e ao menos uma dessas peças é da origem que ela declara
      Dado as F-nn do catálogo com a origem "<origem>", fora as marcadas como removidas
      Quando a guarda do mapa procura as peças que entregam cada uma
      Então cada F-nn é entregue por ao menos uma peça do modelo que está desenhada em diagrama publicado
      E ao menos uma dessas peças é <peça da origem>

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
```

O piso de não vácuo é o do CT-01: cada F-nn tem exatamente uma origem, e as três linhas somadas percorrem o catálogo inteiro.

`@premissa` de mecanismo, decidida pela sessão (Q17): a linha do catálogo nomeia o pacote pelo nome do Composer ou do npm.

*(alterado em 2026-11-20: ADV-02, ADV-03 e ADV-21)*
- **O pacote da peça.** A peça é do pacote quando a classe dela está em `vendor/{pacote}/`.
- **A rota do "onde fica".**
  - O caminho de rota é o texto entre crases que começa com `/`.
  - A guarda troca `{painel}` por cada painel do registro, tira o `{org}` (a suíte do kit roda sem tenant) e a query, e resolve o caminho no roteador da aplicação.
  - Basta um caminho resolver para uma peça que entrega a F-nn. Medido com `php artisan route:list`: `admin/users` resolve para `ListUsers`, página do `UserResource`; `app/login`, para `TelaLogin`; `infra/health-check-results`, para `HealthCheckResults`.
- **Até onde o oráculo vai.** A regra é a célula "onde fica" trazer um caminho entre crases que começa por `/`, em qualquer ponto, sem "→". Na base, dá 40 das 74 linhas, nos dois idiomas. Outras 4 têm caminho e "→", e 30 não têm caminho (menu, ação, "os três", "—"). *(alterado em 2026-11-20: a regra antiga, "começa por caminho", dava 37 em pt e 38 em en, porque a F-44 é "topbar do `/infra`" em pt e "`/infra` topbar" em en)*
  - Nessas 40, a rota fecha o oráculo do código próprio.
  - Nas outras, ele é só "do código do kit", e trocar uma peça do kit por outra fica como lacuna (M8).
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

## Regra R9 — Toda prova resolve no commit que publica: o arquivo é versionado no kit, o trecho está nele, fora de comentário, e a prova não guarda número de linha

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
```

*(alterado em 2026-11-20: ADV-19 e ADV-23)*
- A prova é só o arquivo e o trecho. O número da linha envelhece a cada edição do arquivo (P-07), e quem o calcula é o build. A guarda reprova a prova que guarda linha, num campo próprio ou colada no caminho (`:240`, `#L240`).
- Essa linha está aqui, na suíte do kit, porque ela roda em todo PR. O job `site` não roda no PR que só toca `app/`, e é nesse PR que a linha guardada envelhece.
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
- **Método de contrato do alvo.** Quando o framework chama um método no alvo, o trecho é esse método, no arquivo do alvo: o `canAccessPanel()` do `User` (`app/Models/User.php:canAccessPanel:174`).
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
        | o model `Exception` do `filament-exceptions` usa a `ExceptionPolicy` | `app/Support/PoliciesDeVendor.php`      | `=> ExceptionPolicy::class,`                                   | aceita                       | arquivo do kit que liga a peça de pacote |
        | `UserResource` usa o model `User`                        | `app/Filament/Exports/UserExporter.php`                | `protected static ?string $model = User::class;`               | reprova, nomeando a ligação  | outro arquivo, que nomeia a peça        |
        | o painel infra registra o `UserResource` do `/admin`     | `app/Providers/Filament/InfraPanelProvider.php`        | `->discoverResources(in: app_path('Filament/Infra/Resources')` | reprova, nomeando a ligação  | descoberta que não alcança              |
        | o painel infra registra a página `HealthCheckResults`    | `app/Providers/Filament/InfraPanelProvider.php`        | `FilamentJobsMonitorPlugin::make()`                            | reprova, nomeando a ligação  | registro de plugin que não traz a peça  |
        | o painel admin registra `panel:admin`                    | `app/Providers/Filament/AppPanelProvider.php`          | `->id('app')`                                                  | reprova, nomeando a ligação  | configuração de outro painel            |
        | o painel admin registra `IdentifyTenant`                 | `app/Providers/Filament/AppPanelProvider.php`          | `->tenant(Tenant::class, slugAttribute: 'slug')`               | reprova, nomeando a ligação  | configuração de outro painel            |
        | o painel admin registra o plugin do Shield               | `app/Providers/Filament/AdminPanelProvider.php`        | `AuthDesignerPlugin::make()`                                   | reprova, nomeando a ligação  | prova de outra ligação                  |
        | a página `HealthCheckResults` do pacote do Health resolve para a `TelaBloqueio` | `app/Providers/AppServiceProvider.php` | `$this->app->bind(LockerScreen::class, TelaBloqueio::class);` | reprova, nomeando a ligação | arquivo que não nomeia a origem |
        | o plugin `FilamentSpatieLaravelHealthPlugin` traz a página `HealthCheckResults` | `vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php` | `protected string $page = HealthCheckResults::class;` | reprova, nomeando o arquivo | arquivo de `vendor/` |
        | a página `LockerScreen` do pacote do lockscreen resolve para a `TelaBloqueio` | a saída de `php artisan route:list` | a linha da rota da tela de bloqueio | reprova, nomeando a ligação | saída de comando |
        | `UserResource` usa o model `User`                        | `app/Filament/Admin/Resources/Users/UserResource.php`  | `class UserResource`                                           | reprova, nomeando a ligação  | nome só dentro de outro nome            |
        | `UserResource` usa `User`, numa cópia do modelo sem a peça `User` | `app/Filament/Admin/Resources/Users/UserResource.php` | `protected static ?string $model = User::class;`      | reprova, nomeando `User`     | ponta que não é peça                    |
```

Cada linha inválida tem um único defeito. Os trechos dela estão no arquivo, e a R9 os aceitaria, fora as linhas de `vendor/` e da saída de comando, que a R9 também reprova; a quinta forma as reprova por conta própria (D26). O `UserExporter` traz o mesmo trecho do `UserResource` (`app/Filament/Exports/UserExporter.php:$model:24`), e por isso a única diferença da linha dele é o arquivo. O trecho do `PoliciesDeVendor` é só o lado direito da linha 67, porque ela alinha a seta com espaços, e o trecho tem de estar no arquivo, letra a letra (R9).

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
        | app    | o plugin `FilamentSearchSpotlightPlugin`                                                                    | `DefinirTenantDePermissoes` e `IdentifyTenant`                          |
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
```

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

---

## Regra R13 — Toda ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros — traz a prova, com o arquivo e o trecho, num card do painel lateral do próprio diagrama, à vista sem foco

> `P-04`, `P-07`, `RQ-08` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído · cenário: **CT-B02, no `05`** (costura browser, G4)

O archify só tem prova por nó (`sources`, de 1 a 3) e nenhuma por aresta. Por isso a prova de cada ligação no artefato publicado é responsabilidade do gerador, e só o HTML construído a mostra. O Gherkin e o roteiro estão no `05`. A tabela abaixo é a mesma do CT-B02, mais o M3, que morre na suíte do kit.

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)*
- **Onde fica a prova.** Os cards ficam no painel lateral do diagrama, sempre presentes, sem depender de foco. A prova de cada ligação fica num card do próprio diagrama, agrupado pela peça de origem; esse arranjo é decisão de desenho, e o oráculo é só "num card do painel lateral, à vista".
- **O card não tem link.** O archify escreve o item do card como texto escapado, num `<li>` (a função `renderCards`, em `renderers/shared/utils.mjs` do archify, no commit fixado). O link de evidência que o leitor clica é o do passaporte da peça (R14). Por isso a P-07 aqui é a do M3: a prova não guarda número de linha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o gerador perde a prova das ligações desenhadas por agrupamento ou por porta que nomeia os membros *(alterado em 2026-11-20: a porta entrou com os fatos de plataforma)* | CT-B02 | "uma por ligação": a ligação do membro sem prova reprova; o mutante aceita |
| M2 | o card traz só o arquivo, sem o trecho *(reescrito em 2026-11-20: era "link de prova para `blob/main`", que passou à R14 como M5, porque o card do archify é texto, sem link)* | CT-B02 | "a prova, com o arquivo e o trecho do modelo": reprova; o mutante aceita |
| M3 | número de linha guardado no modelo, que envelhece a cada edição do arquivo *(alterado em 2026-11-20: ADV-19, o matador passou do CT-B02 para a suíte do kit)* | CT-09 | linha da prova com número de linha, na suíte do kit, que roda em todo PR: reprova; o mutante aceita |
| M4 | o card mostra a prova de outra ligação (índice trocado) | CT-B02 | "o arquivo e o trecho do modelo": reprova; o mutante aceita |
| M5 | a prova só fica num documento à parte, nunca no diagrama | CT-B02 | "o total de provas conferidas é o de ligações desenhadas, maior que zero": dá zero e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-25) | o card da prova fica fora do painel lateral: oculto, ou só aparece quando o leitor foca a peça | CT-B02 | "num card do painel lateral do diagrama, à vista", com o diagrama aberto sem focar peça nenhuma: reprova; o mutante aceita |

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

O oráculo do link é o da P-07: o repositório do `support.source` do `composer.json`, o commit do build e um arquivo que existe nesse commit. A linha da prova da peça não tem trecho a conferir: o `sources` do archify guarda só o caminho e a linha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama é exportado como imagem estática, sem busca nem passaporte | CT-B03 | "o leitor busca a peça e a foca": não há busca e reprova; o mutante aceita |
| M2 | o nó sai sem provas, e o passaporte não mostra a origem verificada *(reescrito em 2026-11-20: era "o detalhe não traz a prova da ligação", que o passaporte do archify não mostra por desenho)* | CT-B03 | "mostra de uma a três provas da própria peça": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-25) | o link do passaporte leva a outro repositório, como um fork ou um nome errado, com o mesmo commit e caminho *(reescrito em 2026-11-20: era "o detalhe do agrupamento não nomeia os membros", que passou à R12 como M5)* | CT-B03 | "leva ao repositório do `support.source`": reprova; o mutante aceita |
| M4 | a prova da peça de pacote aponta para `vendor/`, fora do commit, e o link dá 404 | CT-B03 | "a um arquivo que existe nesse commit": reprova; o mutante aceita |
| M5 | link de evidência para `blob/main`, um ramo que anda *(veio da R13, onde era o M2)* | CT-B03 | "no commit do build": o `href` não traz o commit e reprova; o mutante aceita |

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

*(alterado em 2026-11-20: ADV-10)*
- **O que tem de ser traduzido.** O título, o rótulo de ligação, a descrição e o passo en são diferentes dos pt.
- **O rótulo de peça.** É o nome dela, igual nos dois idiomas de propósito.
- **O título igual nos dois idiomas.** Só passa quando é exatamente o nome de uma peça do modelo ou de um comando `kit:*` (`kit:install`), e o arquivo o marca como nome de código. Antes, o exemplo dava também `2FA`, que essa restrição recusa. A marca num título que não é nome de peça nem de comando reprova.

```gherkin
  Regra: O site vale nos dois idiomas, e o que o kit escreve em cada diagrama também

    Esquema do Cenário: [CT-16] todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido, salvo o título que é nome de peça ou de comando, marcado
      Dado os arquivos de diagrama do mapa "<mundo>"
      Quando a guarda do mapa confere os textos que o kit escreveu em cada diagrama
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                              | resultado                                                                                                                              | # partição               |
        | publicados                                                                         | aceita: todo título, rótulo, descrição e passo tem pt e en não vazios; o título, o rótulo de ligação, a descrição e o passo en são diferentes dos pt, salvo o título que é exatamente o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código | válida |
        | cópia em que um rótulo não tem o texto en                                          | reprova, nomeando o diagrama e o rótulo                                                                                                | texto en ausente         |
        | cópia em que o título en é igual ao pt, sem a marca de nome de código              | reprova, nomeando o diagrama                                                                                                           | título copiado           |
        | cópia em que o título `kit:install` é igual nos dois idiomas, marcado como nome de código | aceita                                                                                                                          | nome de comando          |
        | cópia em que o título "Painel admin — sem organização" é igual nos dois idiomas, marcado como nome de código | reprova, nomeando o diagrama e o título                                                                        | marca de fachada         |
        | cópia em que a descrição en de um passo é a frase pt                               | reprova, nomeando o diagrama e o passo                                                                                                 | descrição copiada        |
        | cópia em que um diagrama de fluxo só tem os textos pt                              | reprova, nomeando o diagrama                                                                                                           | diagrama de um idioma só |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o texto en que falta cai no pt, sem aviso | CT-16 | linha "rótulo sem en": reprova; o mutante aceita |
| M2 | título en copiado do pt | CT-16 | linha "título igual, sem a marca": reprova; o mutante aceita |
| M3 | diagrama de fluxo escrito só em pt | CT-16 | linha "só pt": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-10) | o título copiado do pt ganha a marca de nome de código para passar | CT-16 | linha "Painel admin — sem organização", marcado: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-10) | a descrição ou o passo en é a frase pt copiada | CT-16 | linha "descrição en é a frase pt": reprova; o mutante aceita, porque só exige en não vazio |

---

## Regra R17 — A grade painel × modo é inteira: cada painel do registro tem diagrama próprio em cada modo, o compartilhado vem marcado, e a célula não leva a diagrama do outro modo nem de outro painel

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
```

*(alterado em 2026-11-20: ADV-26)* O CT-17 pede "ao menos um" diagrama válido na célula. Sem a linha "vale só no outro modo", a célula com tenant podia levar também ao diagrama que só vale sem tenant. O CT-43 dá a cópia que mata esse defeito, e também a do diagrama de outro painel, que antes só tinha o mundo publicado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um painel é esquecido na grade (o `/infra`) | CT-17 | linhas "infra": "a célula leva a ao menos um diagrama" reprova; o mutante aceita |
| M2 | a lista de painéis da grade é escrita à mão | CT-18 | "reprova, nomeando o painel financeiro": o mutante aceita, porque não lê o registro |
| M3 | a célula leva a diagrama de outro painel | CT-17, CT-43 | CT-17, "nenhum diagrama da célula é de outro painel", e CT-43, linha "infra leva ao admin": reprova; o mutante aceita |
| M4 | o diagrama dos dois modos aparece numa célula só, ou sem a marca | CT-17 | "está também na outra célula, marcado como compartilhado": reprova; o mutante aceita |
| M5 | só o modo sem tenant tem diagramas | CT-17 | linhas "com tenant": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-26) | a célula com tenant leva também ao diagrama que só vale sem tenant | CT-43 | linha "app × com tenant leva ao diagrama que só vale sem tenant": reprova; o mutante aceita |

---

## Regra R18 — Só se compartilha o que é igual nos dois modos, e o que é igual não se duplica

> `P-09`, `RQ-11` · perfil **padrão** · técnica: **tabela de decisão** (igual nos dois modos × publicado uma vez) + controles em cópia

As linhas usam `DefinirTenantDePermissoes`, que o `/app` só registra com tenant. Assim o oráculo vem do registro (CT-11 e CT-12), e não da disponibilidade. Se a disponibilidade por modo conta como diferença de estrutura é recorte aberto (Q11).

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
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama compartilhado esconde a diferença de modo | CT-19 | linha "compartilhado desenha `DefinirTenantDePermissoes`": reprova; o mutante aceita |
| M2 | o que é igual é publicado duas vezes, uma por modo | CT-19 | linha "dois iguais": reprova; o mutante aceita |
| M3 | uma peça de um modo é desenhada no diagrama do outro | CT-19 | linha "sem tenant desenha `DefinirTenantDePermissoes`": reprova; o mutante aceita |

---

## Regra R19 — Cada um dos dez tipos e o comparativo têm uso ou motivo, e cada combinação tipo × painel × modo tem diagrama ou motivo ao lado

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
        | publicada, pt e en                                                                             | aceita: todo diagrama a que a célula leva é daquele tipo e daquele painel e vale naquele modo, ou a célula traz o motivo; e o motivo da página en é traduzido | válida |
        | cópia com a célula `waterfall` × admin × com tenant sem diagrama e sem motivo                  | reprova, nomeando a célula                                                                                                 | célula vazia           |
        | cópia com o motivo "—" na célula `tree` × infra × sem tenant                                   | reprova, nomeando a célula                                                                                                 | motivo de enfeite      |
        | cópia com o motivo "não se aplica", sem porquê, na célula `erd` × app × com tenant             | reprova, nomeando a célula                                                                                                 | motivo de enfeite      |
        | cópia da página en em que o motivo da célula `tree` × infra × sem tenant é o texto da página pt | reprova, nomeando a célula                                                                                                | motivo en copiado do pt |
        | cópia em que a célula `sequence` × admin × sem tenant leva a um diagrama `workflow`            | reprova, nomeando a célula e o tipo                                                                                        | link na célula errada  |
        | cópia em que a célula `sequence` × admin × sem tenant leva ao `sequence` do app                | reprova, nomeando a célula                                                                                                 | link de outro painel   |
        | cópia com o tipo "mindmap" na página                                                           | reprova, nomeando o tipo                                                                                                   | tipo fora do conjunto  |
```

Um tipo do kit inteiro (P-06: linha do tempo das versões, tempos, modelo de dados e de classes) resolve as células de painel com o motivo "do kit inteiro" e o link para o diagrama dele.

*(alterado em 2026-11-20: ADV-10 e ADV-26)* O motivo da célula na página en é diferente do da mesma célula na página pt. A linha "leva ao `sequence` do app" dá à metade "outro painel" do M4 a cópia que faltava.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | tipo sem uso e sem motivo (o `waterfall` esquecido) | CT-20 | linha `waterfall`: reprova; o mutante aceita |
| M2 | célula sem diagrama e sem motivo | CT-21 | linha "célula vazia": reprova; o mutante aceita |
| M3 | motivo de enfeite ("—", "não se aplica") | CT-21 | linhas "—" e "não se aplica": reprova; o mutante aceita |
| M4 | link na célula de outro tipo ou de outro painel | CT-21 | linhas "`sequence` leva a `workflow`" e "`sequence` leva ao `sequence` do app": reprova; o mutante aceita |
| M5 | a página traz um tipo que o archify não tem | CT-21 | linha "mindmap": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-10) | o motivo da célula na página en é o texto pt copiado | CT-21 | linha "motivo en copiado do pt": reprova; o mutante aceita |

---

## Regra R20 — Todo comparativo publicado vai do modo sem tenant (antes) para o com tenant (depois) do mesmo painel

> `RQ-25`, `RQ-11` · perfil **padrão** · técnica: **EP** + controles em cópia

```gherkin
  Regra: O comparativo de arquitetura compara o mesmo painel, de sem tenant para com tenant

    Esquema do Cenário: [CT-22] todo comparativo publicado vai do modo sem tenant para o com tenant do mesmo painel
      Dado os comparativos de arquitetura do mapa "<mundo>"
      Quando a guarda do mapa confere o par de cada comparativo
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                     | resultado                                                                                                             |
        | publicados                                                                | aceita: o "antes" é o diagrama de arquitetura do painel sem tenant, e o "depois" é o do mesmo painel com tenant        |
        | cópia com o comparativo do painel app indo de "com tenant" para "sem"     | reprova, nomeando o comparativo                                                                                       |
        | cópia com o comparativo indo de "admin sem tenant" para "app com tenant"  | reprova, nomeando o comparativo                                                                                       |
```

O comparativo é opcional pela R19: ele pode ser trocado por um motivo. Esta regra vale para todo comparativo que for publicado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | antes e depois trocados: o delta mostra a organização como removida | CT-22 | linha "de com para sem": reprova; o mutante aceita |
| M2 | painéis misturados no par | CT-22 | linha "admin sem → app com": reprova; o mutante aceita |

---

## Regra R21 — Nenhuma dependência nova de visualização no site: o `site/package.json` fica com as dependências da base, e o HTML de cada diagrama não carrega recurso de fora dele

> `RQ-25` · perfil **padrão** · técnica: **EP** (lista fechada, nas de execução e nas de desenvolvimento) + rastreio no site construído (CT-B01, no `05`)

```gherkin
  Regra: Nenhuma dependência nova de visualização além do archify

    Cenário: [CT-23] o site não ganha dependência de visualização além do archify
      Dado as dependências de `site/package.json` da base: `@astrojs/starlight`, `astro`, `astro-mermaid`, `mermaid` e `sharp` para execução; `axe-core`, `playwright` e `wait-on` para desenvolvimento
      Quando a guarda do mapa lê `site/package.json`
      Então as dependências de execução são exatamente as da base
      E as dependências de desenvolvimento são exatamente as da base
```

O `package.json` da raiz já está congelado pelo `[CT-12]` herdado de `tests/Kit/SiteDeDocumentacaoTest.php`.

*(alterado em 2026-11-20: ADV-14)*
- **Lista fechada.** As dependências de desenvolvimento eram conferidas contra uma lista de recusa, e passava qualquer nome fora dela (`@antv/g6`, `sigma`, `vega`). Agora são lista fechada, como as de execução.
- **O canal que o manifesto não vê.** A biblioteca pode entrar por CDN ou por arquivo copiado para `site/public/`. Isso só aparece no HTML construído, então quem confere é o CT-B01, no `05`.
  - Nenhuma requisição sai da origem da prévia, fora `data:` e `blob:`.
  - O documento não tem `<script src>` nem `<link rel="stylesheet">`.
  - O HTML do archify no commit fixado é autocontido: o `assets/template.html` e os exemplos não trazem nenhum dos dois. A sessão mediu zero requisição externa num HTML gerado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `d3` ou `cytoscape` nas dependências de execução, para desenhar o que o archify não desenha | CT-23 | "as de execução são exatamente as da base": reprova; o mutante aceita |
| M2 | `@mermaid-js/mermaid-cli` nas de desenvolvimento, para pré-renderizar | CT-23 | "as de desenvolvimento são exatamente as da base": reprova; o mutante aceita |
| M3 | uma biblioteca PHP de grafo entra no `composer.json` para gerar o modelo | ⚠️ **sem matador**: os pacotes PHP do kit mudam toda semana, e uma lista congelada ficaria vermelha em toda adição legítima (é o precedente do `[CT-12]` de `tests/Kit/SiteDeDocumentacaoTest.php`). O gate confere no diff | — (lacuna declarada) |
| M4 — revisão adversarial (ADV-14) | a biblioteca nova entra nas de desenvolvimento com um nome fora da lista de recusa antiga (`@antv/g6`) | CT-23 | "as de desenvolvimento são exatamente as da base": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-14) | o HTML do diagrama carrega a biblioteca por CDN, ou de um arquivo copiado para `site/public/` | CT-B01 | "nenhuma requisição sai da origem da prévia, e o documento não tem `<script src>` nem `<link rel="stylesheet">`": reprova; o mutante aceita |

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
        | `.github/workflows/pages.yml`               | e antes do envio do artefato    |
        | o job `site` de `.github/workflows/ci.yml`  | (o job não envia artefato)      |

    Esquema do Cenário: [CT-42] a falha do conferidor do mapa reprova o job
      Dado o workflow "<workflow>" "<mundo>"
      Quando a guarda do mapa lê o passo que roda o conferidor do mapa
      Então o resultado é "<resultado>"

      Exemplos:
        | workflow                                    | mundo                                                                  | resultado                                                    |
        | `.github/workflows/pages.yml`               | publicado                                                              | aceita: o passo não tem `continue-on-error`, e o comando não engole a falha |
        | o job `site` de `.github/workflows/ci.yml`  | publicado                                                              | aceita (idem)                                                |
        | `.github/workflows/pages.yml`               | cópia em que o passo do conferidor do mapa tem `continue-on-error: true` | reprova, nomeando o passo                                  |
        | o job `site` de `.github/workflows/ci.yml`  | cópia em que o passo roda `node verifica-mapa.mjs \|\| true`             | reprova, nomeando o passo                                    |
```

Molde: `[CT-40]` e `[CT-42]` de `tests/Kit/SiteDeDocumentacaoTest.php` (passos do workflow por posição).

*(alterado em 2026-11-20: ADV-13)* Sem o CT-42, o conferidor rodava no lugar certo e não reprovava nada, e o artefato saía com o mapa reprovado. "Engolir a falha" é o `|| true` ou qualquer `||` depois do comando do conferidor.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o HTML do mapa é gerado na máquina do mantenedor e versionado: envelhece e não é o do commit | CT-25 | "nenhum HTML de diagrama do mapa está versionado": reprova; o mutante aceita |
| M2 | o workflow chama `astro build` direto e pula a geração do mapa | CT-25 | "o build do site gera os diagramas do mapa": reprova; o mutante aceita |
| M3 | o conferidor do mapa roda depois do envio do artefato, ou antes de a prévia subir | CT-25 | linha `pages.yml`: "depois do conferidor de acessibilidade, com a mesma prévia, e antes do envio" reprova; o mutante aceita |
| M4 | o workflow roda só o conferidor de acessibilidade, e o do mapa nunca roda | CT-25 | "o conferidor do mapa roda": reprova nos dois workflows; o mutante aceita |
| M5 — revisão adversarial (ADV-13) | o passo do conferidor do mapa entra sem poder reprovar (`continue-on-error: true`, ou `\|\| true`) | CT-42 | linhas do `continue-on-error` e do `\|\| true`: reprova; o mutante aceita |

---

## Regra R24 — Todo diagrama publicado abre desenhado, com as peças do arquivo, sem erro de console nem exceção, no idioma da página

> `RQ-07`, `RQ-03`, `P-02` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído · cenário: **CT-B01, no `05`** (costura browser, G4)

*(alterado em 2026-11-20: ADV-31, ADV-28 e ADV-29)*
- **A condição saiu.** A metade de condição do CT-B01 (o passo 4) e o M5 estão estacionados na Q11, com o ID reservado. A RQ-11 saiu da origem desta regra: o que restou não afirma modo.
- **O oráculo é o rótulo de cada peça.** O CT-B01 confere, pelo id, que cada peça desenhada tem o rótulo que o arquivo dá a ela, e não só a contagem. Os pares id e rótulo pegam também a troca de rótulos entre peças, que um conjunto de nomes não pega.
- **A escuta.** Ela pega também a exceção não capturada.
- **O CT-B01 serve também à R21.** Nenhum recurso de fora entra no HTML; o mutante dela, R21-M5, está na tabela da R21.

A tabela abaixo é a mesma do CT-B01.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o conferidor do mapa lista os diagramas varrendo o `dist/` por `index.html`, como o de acessibilidade (`site/verifica-acessibilidade.mjs:'index.html':78`), e não acha nenhum HTML do mapa | CT-B01 | "o total de diagramas abertos é o número de arquivos vezes dois": dá zero e reprova; o mutante aceita |
| M2 | o gerador grava o HTML sem declarar UTF-8, e os acentos se trocam | CT-B01 | "o título … com os acentos intactos": reprova; o mutante aceita |
| M3 | o HTML en sai com os textos pt | CT-B01 | idioma en, "o título é o do arquivo no idioma da página": reprova; o mutante aceita |
| M4 | o visualizador não carrega (o script do archify não entrou no HTML), e a página fica em branco | CT-B01 | "desenha as peças que o arquivo daquele diagrama desenha": dá zero peça e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-28) | o desenho tem o mesmo número de peças, com os rótulos trocados entre elas | CT-B01 | "cada peça desenhada tem, pelo id, o rótulo que o arquivo dá a ela, no idioma da página": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-29) | o script do visualizador lança exceção não capturada na busca ou no detalhe, e o conferidor só escuta o console | CT-B01 | "nenhuma exceção não capturada": reprova; o mutante aceita |

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
```

O widget `AccountWidget` é registrado no `/admin` em `app/Providers/Filament/AdminPanelProvider.php:AccountWidget:158`.

A guarda das classes recebe a lista de classes como parâmetro. Assim o controle acrescenta uma classe que não existe, sem tocar na árvore. O comando `kit:update` e o serviço `TelaDeAutenticacao` são os exemplos que a revisão deu de feature fora do registro (ADV-33): `app/Console/Commands/KitUpdate.php` e `app/Support/TelaDeAutenticacao.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um recurso novo entra no painel sem F-nn e fora da lista (a varredura o esqueceu) *(alterado em 2026-11-20: P-12, a lista com motivo vale para peça registrada)* | CT-29 | linha "`TenantResource` sem F-nn e fora da lista": reprova; o mutante aceita |
| M2 | a guarda confere só `getResources()` e esquece a página que o plugin registra | CT-29, CT-30 | CT-29, linha "`HealthCheckResults` sem F-nn", e CT-30, linha "infra": reprova; o mutante aceita |
| M3 | a peça aponta uma F-nn que o catálogo não tem | CT-29 | linha "`AccountWidget` aponta a F-99": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-33) | a varredura para no registro dos painéis, e o comando `kit:update` fica sem F-nn e fora da lista | CT-41 | linha do `KitUpdate`: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-33) | uma classe nova entra em `app/`, e ninguém a cataloga | CT-41 | linha da classe a mais, `RelatorioNovo`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-33) | peça ou classe na lista sem o motivo, ou com motivo de enfeite | CT-29, CT-41 | CT-29, linha do `AccountWidget` na lista sem o motivo, e CT-41, linhas do `TelaDeAutenticacao` e do `TemUuid`: reprova; o mutante aceita |

---

## Regra R29 — Cada marco da linha do tempo cita uma versão do CHANGELOG com a data dela e um termo da seção dessa versão, e cada tempo medido traz a data e a fonte da medição

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
        | publicados                                                                    | aceita: cada marco cita uma versão que tem cabeçalho no CHANGELOG, com a data desse cabeçalho, e traz um termo que aparece na seção dessa versão; cada tempo traz a data e a fonte da medição (o comando ou o run do CI) | válida |
        | cópia com um marco da versão 0.99.0, que o CHANGELOG não tem                  | reprova, nomeando o marco                                                                                                                                         | versão inexistente  |
        | cópia com o marco da 0.56.0 datado de 2026-11-18 (o CHANGELOG diz 2026-11-19) | reprova, nomeando o marco                                                                                                                                         | data trocada        |
        | cópia com o marco da 0.56.0, datado de 2026-11-19, com o termo "Passkeys", que a seção da 0.56.0 não traz | reprova, nomeando o marco                                                                                             | feature de outra versão |
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
| M6 — revisão adversarial (ADV-12) | o marco traz a versão e a data certas e atribui a ela a feature de outra versão | CT-31 | linha "Passkeys" na 0.56.0: reprova; o mutante aceita |

*(alterado em 2026-11-20: ADV-12)*
- **O termo vale como oráculo, e a F-nn não.** O marco afirma features de uma versão, e o termo é o que liga as duas. A F-nn não serve: o CHANGELOG cita F-nn em só cinco versões (medido na base).
- **O termo do marco.** Aparece no texto da seção da versão, entre o cabeçalho dela e o próximo. A seção da 0.56.0 não traz "Passkeys" (medido na base).

---

## Regra R31 — A interface do visualizador está no idioma da página, e só a casca do comparativo fica em inglês; o catálogo pt que o kit entrega não copia o texto en, fora a lista fechada com o motivo

> `P-11`, `P-02`, `RQ-03` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído + controles em cópia · cenários: **CT-B04 e CT-B07, no `05`** (costura browser, G4) *(regra nova em 2026-11-20: Q12, fechada pela P-11; alterada na quarta rodada: ADV-06 e ADV-30)*

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão, ADV-06 e ADV-30)*
- **O catálogo pt que o kit entrega.** O archify, no commit fixado, tem o `examples/locales/pt.json`, com 461 chaves, mas ele não está no manifesto dos catálogos embutidos. O kit entrega a interface pt pelas traduções do próprio diagrama: essas 461, mais 25 chaves do kit e as sobrescritas dos tipos de peça. O en é o catálogo embutido, `locales/en.json`, com 486 chaves.
- **Por que o CT-B07.** Como todo texto pt sai do que o kit entrega, uma chave com o texto en copiado não mostra o aviso de idioma reserva. Por isso o CT-B07 compara os dois catálogos, chave a chave.
- **Por que a busca e o passaporte abertos.** A busca e o passaporte montam os textos deles na interação. Por isso o CT-B04 lê a interface também com os dois abertos.

A tabela abaixo é a mesma do CT-B04 e do CT-B07.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | uma chave da interface do archify sem tradução pt cai no inglês, com o aviso de idioma reserva | CT-B04 | "nenhuma chave da interface cai no inglês, e nenhum aviso de idioma reserva aparece": reprova; o mutante aceita |
| M2 | o documento pt se declara `lang="en"` (o idioma não chega ao HTML) | CT-B04 | "o `lang` do documento começa por `pt`": reprova; o mutante aceita |
| M3 | a legenda dos tipos de peça fica com os rótulos do archify em inglês | CT-B04 | "a legenda dos tipos de peça usa os nomes da RQ-24": reprova; o mutante aceita |
| M4 | a exceção do comparativo vale para todo diagrama, e a interface inteira fica em inglês sem reprovar | CT-B04 | "na versão pt de todo diagrama fora o comparativo, … nenhuma cai no inglês": reprova; o mutante aceita |
| M5 | a versão en recebe a tradução pt (catálogo de tradução trocado de idioma) | CT-B04 | versão en, "a interface está em inglês": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-30) | a busca e o passaporte montam os textos na interação, e uma chave deles cai no inglês | CT-B04 | "com a busca aberta e o passaporte de uma peça aberto, toda chave visível mostra o texto pt": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-06) | para calar o aviso de idioma reserva, o catálogo pt do kit recebe o texto en nas chaves não traduzidas | CT-B07 | linha "a chave da busca traz o texto en": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-06) | a lista fechada de textos iguais aceita a chave sem o motivo | CT-B07 | linha "na lista, sem o motivo": reprova; o mutante aceita |

---

## Regra R32 — A conferência de acessibilidade audita a amostra inteira e tolera só os dois defeitos do archify, pela regra e pelo elemento

> `P-11`, `RQ-03`, `RQ-07` · perfil **padrão**, técnica escalada (6 mutantes, o teto do completo) · técnica: **amostra estratificada** (tipo × idioma × tema) + **controle sintético** · cenários: **CT-B05 e CT-B06, no `05`** (costura browser, G4) *(regra nova em 2026-11-20: Q12, fechada pela P-11)*

Os dois defeitos tolerados são estes:
- `nested-interactive` cujo alvo é o `<svg>` raiz do diagrama;
- `color-contrast` cujo alvo é a contagem de grupo do índice de nós, `.node-outline-heading > small`.

Qualquer outra violação serious ou critical, qualquer `console.error` e qualquer exceção não capturada reprovam *(alterado em 2026-11-20: ADV-29; o mutante da exceção está na R24, como M7, e o teto de seis desta regra fica)*. A tabela abaixo é a mesma do `05`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a tolerância casa pelo texto do seletor do axe: qualquer alvo cujo seletor contém `svg` ou `small` passa | CT-B06 | linhas "`nested-interactive` num `<svg>` que não é o raiz" e "`color-contrast` num `<small>` fora de `.node-outline-heading`": reprova; o mutante tolera |
| M2 | a tolerância casa pela regra sem o elemento: todo `color-contrast` passa | CT-B06 | linha "`color-contrast` num parágrafo do corpo": reprova; o mutante tolera |
| M3 | uma terceira violação entra na tolerância | CT-B06 | linha "`button-name` num botão sem nome": reprova; o mutante tolera |
| M4 | amostra vazia: o conferidor não acha diagrama e não audita nada | CT-B05 | "a amostra tem exatamente uma entrada por tipo publicado, idioma e tema, e nenhuma é vazia": reprova; o mutante aceita |
| M5 | um tipo publicado fica fora da amostra | CT-B05 | idem, pelo tipo que falta: reprova; o mutante aceita |
| M6 | o comparativo não é auditado | CT-B05 | "mais o comparativo no tema escuro": reprova; o mutante aceita |

---

## Regra R33 — A página do mapa declara, nos dois idiomas, as duas exceções da P-11 e o limite da varredura da P-12

> `P-11`, `P-12`, `P-02` · perfil **padrão** · técnica: **EP** (idioma) *(regra nova em 2026-11-20: Q12, fechada pela P-11; alterada na quarta rodada: P-12)*

```gherkin
  Regra: O leitor sabe o que o mapa não cumpre, e por quê

    Esquema do Cenário: [CT-34] a página do mapa de cada idioma declara a casca do comparativo em inglês, os dois defeitos tolerados do archify e o limite da varredura
      Dado a página do mapa em "<idioma>"
      Quando a guarda do mapa lê a declaração de exceções da página
      Então a página diz que a casca do comparativo fica em inglês
      E a página nomeia os dois defeitos tolerados pela regra e pelo elemento: `nested-interactive` no `<svg>` raiz do diagrama, e `color-contrast` na contagem de grupo do índice de nós (`.node-outline-heading > small`)
      E a página diz qual é o piso da varredura — as peças que os painéis registram e as classes de `app/` fora de `app/Filament` — e que, além dele, a completude não tem garantia

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

---

## Regra R34 — Os diagramas de arquitetura de um painel que valem num modo desenham, juntos, todo o registro do painel nesse modo, e nada que ele não registra nesse modo

> `P-09`, `RQ-11`, `RQ-10`, `P-05` · perfil **padrão** · técnica: **tabela de decisão** (o registro muda entre os modos × há arquitetura por modo; 3 painéis × 2 modos) + controles em cópia *(regra nova em 2026-11-20: ADV-05)*

O `/app` muda de registro entre os modos: `IdentifyTenant` e `DefinirTenantDePermissoes` só existem com tenant (CT-11 e CT-12). O `/admin` e o `/infra`, não (Setup Global).

A regra não manda o `/app` ter dois diagramas. Ela pede, em cada modo, que os diagramas `architecture` do painel que valem nesse modo — o do modo ou o compartilhado — desenhem juntos o registro do painel nesse modo, nem mais nem menos. O compartilhado só passa nos dois modos se o registro for o mesmo, e por isso o `/app` precisa de um por modo.

A ligação de registro que só aparece num diagrama de outro tipo não conta aqui, embora conte no CT-14.

O modo com tenant só existe na suíte `Tenancy`. Sem tenant, o `/app` não registra os dois middlewares, e a cópia "só o compartilhado" passaria no CT-37. Quem a mata é o CT-38.

```gherkin
  Regra: O que muda de registro entre os modos ganha um diagrama de arquitetura por modo

    Esquema do Cenário: [CT-37] sem tenant, os diagramas de arquitetura de cada painel que valem nesse modo desenham o registro dele nesse modo, e nada além
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o painel "<painel>" do registro de painéis, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa junta as ligações de registro que os diagramas `architecture` do painel que valem no modo sem tenant desenham
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                                                    | resultado                                                                                         |
        | admin  | publicados                                                                               | aceita: o conjunto é o registro do painel sem tenant, nem mais nem menos                         |
        | app    | publicados                                                                               | aceita (idem), sem `IdentifyTenant` nem `DefinirTenantDePermissoes`                              |
        | infra  | publicados                                                                               | aceita (idem), com a página `HealthCheckResults`, que o plugin registra                          |
        | admin  | cópia em que o diagrama de arquitetura do admin não desenha o `FilamentShieldPlugin`      | reprova, nomeando o painel admin e o plugin                                                      |

    Esquema do Cenário: [CT-38] com tenant, os diagramas de arquitetura de cada painel que valem nesse modo desenham o registro dele nesse modo, e nada além
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o painel "<painel>" do registro de painéis, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa junta as ligações de registro que os diagramas `architecture` do painel que valem no modo com tenant desenham
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                                                                                    | resultado                                                                                   |
        | app    | publicados                                                                                                               | aceita: o conjunto é o registro do painel com tenant, inclusive `IdentifyTenant` e `DefinirTenantDePermissoes` |
        | admin  | publicados                                                                                                               | aceita: o conjunto é o registro do painel com tenant                                        |
        | infra  | publicados                                                                                                               | aceita (idem)                                                                               |
        | app    | cópia em que o painel app só tem o diagrama de arquitetura compartilhado, sem `IdentifyTenant` nem `DefinirTenantDePermissoes` | reprova, nomeando o painel app e as duas peças                                    |
        | app    | cópia em que a ligação "o painel app registra `IdentifyTenant`" só está num diagrama `workflow` marcado com tenant         | reprova, nomeando o painel app e `IdentifyTenant`                                           |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV-05) | o `/app` sai com um só diagrama de arquitetura, compartilhado, que omite as peças do modo com tenant | CT-38 | linha "só o compartilhado": reprova; o mutante aceita, porque o compartilhado não desenha peça de um modo só (CT-19) |
| M2 — revisão adversarial (ADV-05) | as ligações de registro do modo com tenant ficam num diagrama de outro tipo, marcado "com tenant" | CT-38 | linha "só num `workflow`": reprova; o mutante aceita, porque o CT-14 acha a ligação desenhada |
| M3 | o diagrama de arquitetura de um painel esquece uma peça registrada, como a de plugin | CT-37 | linha "sem o `FilamentShieldPlugin`": reprova; o mutante aceita, se a ligação estiver noutro tipo de diagrama |

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

## Regra R36 — O que um diagrama desenha está no modelo: toda aresta é ligação do modelo no mesmo sentido — direta, de agrupamento ou de porta —, e o diagrama de painel só desenha peça do painel, peça do kit inteiro ou caixa de porta

> `P-03`, `RQ-24`, `P-10`, `RQ-10` · perfil **padrão** · técnica: **EP** (forma da aresta) + controles em cópia *(regra nova em 2026-11-20: ADV-24 e ADV-27)*

A R12 vai do modelo ao desenho. Esta regra vai do desenho ao modelo, com as três formas de aresta que a sessão mediu nos mapas de arquitetura:
- **Direta.** É uma ligação do modelo, no mesmo sentido.
- **De agrupamento.** Toda ligação que ela representa existe no modelo, e liga peças que o agrupamento nomeia.
- **De porta.** O outro lado de cada ligação que ela representa é peça desenhada no mapa que a porta nomeia.

O diagrama de um painel só desenha três coisas:
- peça do painel: o que ele registra (R11) e as pontas das ligações que partem dele ou das peças dele;
- peça do kit inteiro: peça que nenhum painel registra, como model, serviço próprio, comando ou setting;
- caixa de porta.

```gherkin
  Regra: O desenho não inventa nem inverte ligação, e o diagrama de um painel não desenha o que é de outro

    Esquema do Cenário: [CT-40] toda aresta desenhada é ligação do modelo no mesmo sentido, e todo diagrama de painel só desenha o que é dele, do kit inteiro ou porta
      Dado o modelo e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere cada aresta e cada peça desenhada
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu todas as arestas de todos os diagramas

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
| M2 — revisão adversarial (ADV-24) | um diagrama escrito à mão traz uma aresta que o modelo não tem | CT-40 | linha "inventada": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-24) | a aresta de agrupamento representa a ligação de uma peça que não é membro nomeado | CT-40 | linha "membro não nomeado": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-24) | a porta aponta para um mapa que não desenha o outro lado da ligação | CT-40 | linha "porta para o mapa errado": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-27) | o diagrama de um painel é copiado de outro e declarado com o painel novo | CT-40 | linha do `UserResource` no `/infra`: reprova; o mutante aceita, porque o CT-17 decide pelo painel que o arquivo declara |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-09 (linhas `bootstrap/cache/packages.php` e `vendor/`). O site é público e estático, e nenhuma rota recebe `{id}`. A única saída de conteúdo para fora do repositório público seria a prova citar arquivo fora do commit, e essas linhas a matam | G2 |
| Autorização exercida na ação | não se aplica: nenhuma ação, policy ou permissão nova. A Superfície Livewire do `02` não cria nada em `app/`, e o gate confere o diff (RQ-15) | — |
| Idempotência (ancorada no agregado) | não se aplica: o build regenera o mapa do zero a partir do commit (P-07), e nada acumula entre uma execução e outra | — |
| Concorrência | não se aplica: não há contador nem limite | — |
| **Fronteira no ponto de entrada** (o modelo, o catálogo e as referências, conferidos a cada PR) | CT-01, CT-03, CT-04, CT-08, CT-09, CT-10, CT-21, CT-28, CT-29, CT-31, CT-35, CT-36, CT-39, CT-40, CT-41 | G1, G2 |
| **Domínio condicionado** | CT-06 e CT-36 (origem × peça; F-nn × pacote nomeado × pacote da peça; rota do "onde fica" × peça), CT-08 (classe × tipo), CT-10 (forma da prova × arquivo; descoberta × pasta; plugin × peça; configuração × painel), CT-21 (tipo × painel × modo), CT-37 e CT-38 (registro × modo × arquitetura) | G2, G3 |
| **Estado × operação de escrita** (número removido × reaproveitar; removido × ainda entregue) | CT-03, CT-04 (matriz da R3, 15 células), CT-36 (a removida que uma peça ainda entrega) | G1, G2 |
| Ausente ≠ null ≠ vazio | CT-09 (trecho ausente, vazio, só de espaços), CT-03, CT-21, CT-28, CT-29, CT-35 e CT-41 (motivo ausente; motivo "—"; "não se aplica" sem porquê), CT-31 (tempo sem fonte; sem data), CT-39 (transição e estado sem âncora) | G1, G2 |
| Paginação / ordenação | não se aplica: não há listagem paginada, e a ordem das linhas do catálogo não é cláusula. A ordem dos números é, e o CT-04 a cobre | — |
| Timezone / DST | não se aplica: nenhuma data é calculada. As datas da linha do tempo são comparadas como texto com o cabeçalho do CHANGELOG (CT-31) | — |
| Unicode / texto livre | CT-B01 (acentos intactos no HTML gerado), CT-B04 (interface pt, inclusive com a busca e o passaporte abertos), CT-B07 (catálogo pt sem o texto en), CT-16 e CT-21 (texto en traduzido; título igual só se é nome de peça ou de comando, marcado) | G4, G2 |
| Unicidade (+ soft delete) | CT-01 (F-nn em duas linhas), CT-08 (classe em duas peças). Soft delete não se aplica: não há banco | G1, G2 |
| CRUD combinado | não se aplica: não há CRUD | — |
| Mass assignment | não se aplica: não há formulário nem payload | — |
| Upload | não se aplica: não há upload | — |
| Precisão monetária | não se aplica: não há valor monetário | — |
| **Superfície Livewire** | não se aplica: o `02` declara que nenhuma página, widget ou componente é criado ou alterado, e o plano não tem path em `app/` | — |
| **Estado do framework usado sem validar** | lacuna declarada: o hash da URL do visualizador (`#focus=`, `#route=`) é entrada do leitor, mas quem o lê é o código do archify, não a entrega. Nenhum cenário da entrega o falsifica. Fica registrado para a dimensão de segurança do quality gate | G4 |
| **IDOR e mass assignment por entidade** | não se aplica: a entrega não persiste entidade | — |
| **Escopo com discriminante nulo** (o modo da peça e do diagrama) | CT-11, CT-12, CT-19, CT-37, CT-38 e CT-43: o modelo não dá a um modo o que o painel não registra nele, os diagramas de arquitetura de cada modo desenham o registro desse modo, e a célula não leva a diagrama do outro modo. A condição da peça está estacionada na Q11 (ADV-31). Nas ligações de uso, o modo vem da varredura: lacuna declarada, vinculada à P-05 ("sem promessa de que a varredura achou todas") | G2, G3 |
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

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | catálogo em paridade, a mesma feature em cada número, uma linha por F-nn, origem do glossário | R1 | EP + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R1-M1 a M5 |
| CT-02 | só se cita F-nn que o catálogo tem, e ninguém lista feature por fora dele | R2 | EP + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R2-M1 a M4 |
| CT-03 | número da base fica com a feature, ou removido com motivo, e não volta | R3 | estado × operação | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R3-M1 a M6 |
| CT-04 | os novos começam na F-72, em sequência, sem sufixo | R4 | BVA 3-valores | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R4-M1, M2, M3 |
| CT-05 | README da base e todas as páginas do site chegam ao catálogo, ou à lista das sem feature; os seis itens novos, pelo termo | R5 | EP por fonte | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R5-M1, M2, M4, M6 |
| CT-06 | toda F-nn é entregue por peça desenhada da sua origem: do pacote que a linha nomeia, ou da rota do "onde fica" | R6 | EP por origem | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R6-M1, M2 |
| CT-07 | a rule está no índice, alcança onde a feature nasce e manda o número só crescer | R7 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R7-M1 a M4 |
| CT-08 | toda peça existe, tem o tipo da sua classe e aparece uma vez | R8 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R8-M1 a M6 |
| CT-09 | toda prova resolve no commit que publica, fora de comentário, sem número de linha; todas as ligações conferidas | R9 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R9-M1 a M6, R13-M3 |
| CT-10 | a prova é a da ligação: arquivo da origem, trait ou concern, provider (descoberta, plugin ou configuração), contrato do alvo ou arquivo do kit que liga a peça de pacote | R10 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R10-M1 a M9 |
| CT-11 | sem tenant: registro → modelo, e o modo do modelo → registro | R11 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R11-M1, M4, M5 |
| CT-12 | com tenant: registro → modelo, e o modo do modelo → registro | R11 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R11-M3, M4 |
| CT-13 | a guarda do registro reprova esquecer, sobrar e trocar de modo | R11 | controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R11-M1, M2 |
| CT-14 | toda ligação desenhada em diagrama publicado; só aresta conta, e o agrupamento e a porta nomeiam os membros | R12 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R12-M1 a M6 |
| CT-15 | a página de cada idioma leva, por `<a href>` relativo, a todo diagrama, e só aos que existem | R15 | EP | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R15-M1 a M5 |
| CT-16 | textos de diagrama em pt e en, traduzidos; título igual só se é nome de peça ou de comando, marcado | R16 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R16-M1 a M5 |
| CT-17 | grade painel × modo inteira, compartilhado marcado, sem diagrama do outro modo nem de outro painel | R17 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R17-M1, M3, M4, M5 |
| CT-18 | painel novo sem diagrama reprova a grade | R17 | controle | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R17-M2 |
| CT-19 | só se compartilha o que é igual, e o igual não se duplica | R18 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R18-M1, M2, M3 |
| CT-20 | cada tipo e o comparativo têm uso ou motivo | R19 | produto cartesiano | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R19-M1 |
| CT-21 | toda célula tipo × painel × modo tem diagrama ou motivo, e o motivo en é traduzido | R19 | produto cartesiano + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R19-M2 a M6 |
| CT-22 | comparativo do mesmo painel, de sem para com tenant | R20 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R20-M1, M2 |
| CT-23 | as dependências do site são as da base, nas de execução e nas de desenvolvimento | R21 | EP (lista fechada) | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R21-M1, M2, M4 |
| CT-24 | há fluxo publicado, e todo passo de `workflow`, `sequence` e `dataflow` é peça do modelo | R22 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R22-M1, M2 |
| CT-25 | o mapa nasce no build, e o conferidor do mapa roda depois do de acessibilidade, antes do envio | R23 | EP por workflow | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R23-M1 a M4 |
| CT-26 | o README de cada idioma leva ao mapa do mesmo idioma | R25 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R25-M1, M2 |
| CT-27 | os blocos Mermaid da base ficam onde estavam | R26 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R26-M1, M2, M3 |
| CT-28 | todo pacote instalado está na referência e chega ao catálogo, ou à lista dos sem feature; a referência só lista pacote instalado | R27 | EP por partição de pacote + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R27-M1 a M6 |
| CT-29 | sem tenant, toda peça registrada entrega F-nn ou está na lista com o motivo | R28 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R28-M1, M2, M3, M6 |
| CT-30 | com tenant, toda peça registrada entrega F-nn ou está na lista com o motivo | R28 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R28-M2 |
| CT-31 | marcos da linha do tempo batem com o CHANGELOG, com um termo da seção da versão, e todo tempo tem data e fonte | R29 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R29-M1 a M6 |
| ~~CT-32~~ | ~~o modelo guarda a condição e a âncora de toda peça que não abre nesta configuração~~ — estacionado — Q11 (ADV-17, ADV-31) | ~~R30~~ | — | — | — | — | — |
| ~~CT-33~~ | ~~todo diagrama mostra a condição da peça condicional e não a dá como disponível no modo errado~~ — estacionado — Q11 (ADV-31) | ~~R30~~ | — | — | — | — | — |
| CT-34 | a página do mapa declara as duas exceções da P-11 e o limite da varredura da P-12, nos dois idiomas | R33 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R33-M1 a M4 |
| CT-35 | a guarda das fontes reprova o item casado com a feature errada, a página escondida na lista e o motivo de enfeite | R5 | controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R5-M3 a M8 |
| CT-36 | a guarda da origem reprova a F-nn entregue pela peça errada e a removida que ainda é entregue | R6 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R6-M3 a M7 |
| CT-37 | sem tenant, os diagramas de arquitetura de cada painel desenham o registro dele nesse modo | R34 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R34-M3 |
| CT-38 | com tenant, os diagramas de arquitetura de cada painel desenham o registro dele nesse modo | R34 | tabela de decisão + controles | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R34-M1, M2 |
| CT-39 | toda transição de fluxo tem âncora que resolve, todo estado de `lifecycle` também, e a ordem no mesmo método é a do código | R35 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R35-M1, M2, M3, R22-M3 |
| CT-40 | toda aresta desenhada é ligação do modelo no mesmo sentido, e o diagrama de painel só desenha o que é dele, do kit inteiro ou porta | R36 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R36-M1 a M5 |
| CT-41 | toda classe de `app/` fora de `app/Filament` entrega F-nn ou está na lista com o motivo | R28 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R28-M4, M5, M6 |
| CT-42 | a falha do conferidor do mapa reprova o job | R23 | controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R23-M5 |
| CT-43 | a guarda da grade reprova a célula que leva a diagrama do outro modo ou de outro painel | R17 | controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R17-M3, M6 |
| CT-B01 | cada diagrama abre desenhado, cada peça com o seu rótulo, sem erro nem exceção, sem recurso de fora, no idioma da página (no `05`) | R24, R21 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R24-M1 a M4, M6, M7, R21-M5 |
| CT-B02 | cada ligação desenhada, direta ou por agrupamento ou porta que nomeia os membros, traz a prova num card do painel lateral, à vista (no `05`) | R13 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R13-M1, M2, M4, M5, M6 |
| CT-B03 | o leitor busca a peça, e o passaporte mostra as provas dela, com o link para o repositório do kit no commit do build (no `05`) | R14 | EP | G4 | browser | `site/verifica-mapa.mjs` | R14-M1 a M5 |
| CT-B04 | a interface do visualizador fala o idioma da página, também com a busca e o passaporte abertos; só a casca do comparativo fica em inglês (no `05`) | R31 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R31-M1 a M6 |
| CT-B05 | a amostra de acessibilidade cobre cada tipo, idioma e tema, e o comparativo no escuro (no `05`) | R32 | amostra estratificada | G4 | browser | `site/verifica-mapa.mjs` | R32-M4, M5, M6 |
| CT-B06 | a tolerância reconhece os dois defeitos pela regra e pelo elemento, e reprova o resto (no `05`) | R32 | controle sintético | G4 | browser | `site/verifica-mapa.mjs` | R32-M1, M2, M3 |
| CT-B07 | o catálogo pt que o kit entrega não copia o texto en, fora a lista fechada com o motivo (no `05`) | R31 | controles | G4 | browser | `site/verifica-mapa.mjs` | R31-M7, M8 |
