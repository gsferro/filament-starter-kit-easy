# Decisões Arquiteturais — Diagramas da arquitetura do kit

> Requisito: `00-requisito.md`. Plano: `01-plano-acao.md`.

## ADR-01: O GitDiagram entra só como rascunho de topologia, nunca como fonte publicada

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

O pedido original (RQ-01, RQ-02, RQ-03) manda estudar o diagrama que o `gitdiagram.com` gerou para
este repositório, suas funcionalidades e as duas saídas de export (PNG e Mermaid), e avaliar se
esse material entra no README e no site. A sessão auditou o Mermaid exportado (6.021 bytes, 23
nós, 27 arestas, 20 `click`) contra o código em `origin/main` (`be8a0af`).

O resultado da auditoria, célula a célula:

| O quê | Total | Corretos | Imprecisos | Errados | Inventados |
|---|---|---|---|---|---|
| Nós | 23 | 17 | 6 | 0 | 0 |
| Arestas | 27 | 26 | 1 | 0 | 0 |
| Links `click` | 20 | 18 | 2 | 0 | 0 |

Nenhum nó é inventado — o problema não é fabricação, é **distorção**: 4 dos 6 nós imprecisos
apresentam como sempre-presente algo que é **opt-in e vem desligado** (organizações/`Tenant`,
vínculos de organização, resources do `/app`, `Projeto`), e faltam 7 arestas e 10 omissões
estruturais, a mais grave sendo que o diagrama **não mostra o controle de acesso por papel**
(`roles.painel`), que é a regra central do kit — mesmo o README que o gerador leu já trazendo essa
tabela dentro do trecho de 8.500 caracteres que ele analisou
(`app/Models/User.php:canAccessPanel:156`, `:206`, `:219`).

Três razões estruturais tornam o GitDiagram inadequado como **fonte** publicada:

1. **Não é determinístico e não é versionado no commit.** O cache do serviço é indexado só pelo
   nome do repositório (`gsferro/filament-starter-kit-easy`), sem SHA — o diagrama de hoje é da
   `v0.39.0`, já 18 commits atrás, e **qualquer visitante** pode clicar em "Regenerar" e trocar o
   conteúdo do link a qualquer momento, sem PR e sem review.
2. **O LLM parte do README, não do código.** O prompt do serviço manda preservar capacidades
   documentadas mesmo sem ver o código-fonte, e o serviço lê no máximo 12 arquivos e até 8.500
   caracteres do README — no kit isso é ~23% do arquivo. Isso viola RQ-10 por construção: o
   diagrama reflete o que o README *diz*, não o que o código *faz*.
3. **Nenhuma guarda de teste alcança um link externo.** RQ-26 exige que cada diagrama tenha uma
   guarda que falhe quando o código deixar de bater com ele — impossível para um artefato que mora
   num servidor de terceiro e muda por clique de qualquer visitante.

### Decisão

O GitDiagram **não é publicado, embutido nem linkado como fonte** de nenhum diagrama. Ele serviu
de **rascunho de topologia**: dos 23 nós e 26 arestas corretos/corrigíveis, o catálogo DG-01..DG-20
reaproveita a ideia de agrupamento (painéis, gestão de organização, IA, infraestrutura) com as
correções da auditoria — ator único vira os cinco papéis reais, `Tenant`/`Projeto`/resources do
`/app` marcados como opcionais, e as arestas e omissões da seção 4 da auditoria (papel, tenancy
opcional, 2FA/bloqueio, ciclo do convite, catálogo de comandos `kit:*`) incorporadas nos diagramas
correspondentes do catálogo. Todo Mermaid publicado nesta entrega é escrito e mantido pela sessão,
guardado por teste (ADR-06), e cita o código que o sustenta.

O único vestígio do GitDiagram no material publicado é um **link de crédito**, tratado no ADR-11.

### Alternativas Consideradas

1. **Publicar o Mermaid exportado, corrigido linha a linha.** Descartada: mesmo corrigido hoje, o
   texto do GitDiagram não tem dono nem guarda — a próxima pessoa que editar o kit não sabe que
   aquele bloco "era" um export de terceiro e pode reintroduzir a distorção ao copiar o padrão.
   Mais barato (e mais claro para quem lê o histórico) é escrever o Mermaid do zero, no catálogo.
2. **Só linkar o site do GitDiagram, sem embutir nada.** Descartada como fonte (não como crédito):
   o conteúdo do lado de lá muda sem aviso e sem PR — um link "fonte" que pode virar outra coisa a
   qualquer momento não é documentação, é aposta.

### Consequências

- **Positivas**: todo diagrama publicado é auditável no `git blame`, tem guarda de teste e cita o
  código; o crédito ao GitDiagram fica honesto (RQ-30: "visão gerada por IA, não verificada").
- **Negativas**: mais trabalho de escrita manual do que copiar um export pronto; a sessão assume a
  manutenção de 20 diagramas em vez de delegar a atualização a uma ferramenta externa.
- **Riscos**: nenhum novo — o risco que esta decisão elimina é justamente o de um diagrama que
  muda sem controle do kit.

### Referências

- Auditoria completa: pesquisa da sessão (`gitdiagram.md`, seção "AUDITORIA DE FIDELIDADE"), não
  commitada — o resumo acima é o registro permanente.
- `app/Models/User.php:canAccessPanel:156`
- Refina: ADR-05 (tipo por propósito), ADR-11 (crédito)

---

## ADR-02: `astro-mermaid` + `mermaid@11.17.2`, fixados em `site/package.json`

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

RQ-18 (Adendo 2) já aprova esta dependência explicitamente, então esta ADR registra o *porquê*
dela e não das alternativas, para a próxima pessoa que revisitar o assunto não reabrir a busca do
zero.

O site (`site/`) hoje não tem nenhum caminho para Mermaid virar diagrama: `site/package.json`
declara só `@astrojs/starlight`, `astro` e `sharp` como dependências de produção. Sem plugin, um
bloco ` ```mermaid ` publicado em `docs/**/*.md` renderiza como bloco de código com realce de
sintaxe, não como diagrama.

O GitHub renderiza Mermaid nativamente na versão **11.17.2** (confirmado lendo o script de
renderização do próprio GitHub, que chama `b.renderer.draw(e,t,"11.17.2",b)`). Fixar a mesma versão
no site evita que o mesmo bloco `.md` desenhe diferente nos dois lugares — o Mermaid 12 (lançado
10/09/2026) trocou o layout padrão de `stateDiagram`/`erDiagram` para ELK, que o GitHub ainda não
usa.

### Decisão

Adicionar em `site/package.json` (nunca na raiz — `[CT-12]` trava isso):

```json
"dependencies": {
  "astro-mermaid": "^2.1.0",
  "mermaid": "11.17.2"
}
```

`astro-mermaid` entra **antes** de `starlight()` no array `integrations` de `site/astro.config.mjs`
(exigência do próprio plugin), e o `site/package-lock.json` regenerado vai no mesmo commit do
`site/package.json`: o workflow do site roda `npm ci` (`.github/workflows/pages.yml:npm:55`), que
reprova lock divergente, e o `[CT-15]` exige o lock
(`tests/Kit/SiteDeDocumentacaoTest.php:'package-lock':673`). A integração entra com `autoTheme: true` (segue o atributo `data-theme` que o Starlight
já usa — `site/src/styles/kit.css:'data-theme':46` já declara `:root[data-theme='light']`). O plugin renderiza
**no cliente** (JS no navegador troca `<pre class="mermaid">` pelo SVG), sem passo de build via
Playwright/Puppeteer — por isso não conflita com a recusa já registrada de trocar o processador de
markdown do Astro 7 (`site/astro.config.mjs:rehypePlugins:85`, comentário sobre `rehypePlugins` exigir
`@astrojs/markdown-remark`).

`site/verifica-acessibilidade.mjs` muda em três pontos, todos necessários porque o SVG do Mermaid
nasce **depois** do carregamento inicial da página (o plugin roda no cliente):

1. esperar o `<svg>` existir antes de rodar o axe na(s) página(s) com diagrama (a chamada atual usa
   `waitUntil: 'domcontentloaded'`, que roda antes do JS do plugin desenhar o SVG), com o seletor
   `.mermaid svg`; *(alterado em 2026-09-29: a espera é por `data-processed` em todo `pre.mermaid` — ver "Alterações depois da implementação")*
2. reprovar a página em que algum `.mermaid` não virou `<svg>` ou virou o diagrama de erro do
   Mermaid (`Syntax error in text`) — é a única validação de sintaxe que os blocos têm; *(alterado em 2026-09-29: o `astro-mermaid` 2.1.0 põe um `<div>` de erro no lugar do bloco, e a reprovação é por essa estrutura)*
3. incluir a página nova de diagramas na `AMOSTRA_CLARA` (hoje só o modo escuro é auditado em
   todas as rotas).

### Alternativas Consideradas

1. **`rehype-mermaid`** — gera SVG no build via Playwright. Descartada: exige trocar o processador
   de markdown do Astro (`@astrojs/markdown-remark`), que `site/astro.config.mjs:rehypePlugins:85` já registra
   como recusado; e o `.github/workflows/pages.yml` instala o Chromium **depois** do build
   (`npm run build` antes de `playwright install`), então precisaria reordenar o pipeline de CI.
2. **Imagem pré-renderizada em `art/`, servida por URL absoluta.** Descartada: não segue o tema
   claro/escuro, vira binário que diverge do texto assim que o código muda, e ainda seria preciso
   alguma ferramenta para gerá-la — nenhum Mermaid está instalado hoje em `node_modules`, `vendor/`
   ou no lock, então gerar a imagem já exigiria a mesma dependência. Também recusada por `[CT-21]`,
   que rejeita imagem relativa em `docs/` (`tests/Kit/SiteDeDocumentacaoTest.php:getExtension:967`).
3. **`mermaid.ink` (serviço externo que desenha a partir de uma URL compactada).** Descartada:
   dependência de rede em tempo de leitura da página (sem SLA declarado), sem confirmação de tema
   escuro, e a fonte fica ilegível na URL (compactada em pako), dificultando a guarda de RQ-26 —
   ela precisa ler o texto Mermaid, não decodificar uma URL.

### Consequências

- **Positivas**: um único texto-fonte (o bloco ```` ```mermaid ````) renderiza nos três lugares que
  importam — GitHub, Packagist (como código cru) e site —, sem build extra.
- **Negativas**: primeira dependência de runtime que o site carrega no navegador além do Starlight
  em si; o próprio mantenedor do Starlight já registrou, em discussão pública, que o Mermaid é
  "grande e lento" — o peso não foi medido nesta entrega e vira item do Riscos do `01`.

### Alterações depois da implementação

*(alterado em 2026-09-29: o que a implementação e a revisão do diff mudaram nesta decisão — a escolha do
`astro-mermaid` + `mermaid` 11.17.2 continua de pé)*

- **Peso medido**: cerca de 700 KB de JS a mais, só nas páginas com diagrama (commit `92857dc`); o
  Vite avisa de chunk acima de 500 kB. Visível, não proibitivo.
- **Espera e erro, como ficaram no conferidor**: não há espera por seletor de SVG — o conferidor espera
  todo `pre.mermaid` ganhar `data-processed`, que o `astro-mermaid` grava ao terminar, e só então roda
  o axe. O `astro-mermaid` 2.1.0 não desenha o SVG de erro do Mermaid: no `catch` ele troca o bloco
  por um `<div>` com `<strong>Error rendering diagram:</strong>`, e a reprovação é por essa estrutura,
  nunca por regex no texto (os itens 1 e 2 da decisão acima). O conferidor ganhou também os CT-B01 e
  CT-B02 do `05`: contagem exata de SVG por página contra a fonte, nos dois temas, e a troca de tema
  com a página aberta.
- **Contraste no tema escuro** (Adendo 3, RQ-33): o tema `dark` do Mermaid deixa o fundo do rótulo de
  aresta a 4,43:1 sob o texto, abaixo do AA. O Adendo 3 falava em ajustar "na config do
  astro-mermaid", mas ela não tem configuração por tema — o `themeMap` só troca o nome do tema, e o
  `mermaidConfig` é o mesmo objeto para os dois. O ajuste foi para `site/src/styles/kit.css`, escopado
  a `data-theme='dark'` e a `pre.mermaid`, com `!important` porque o Mermaid prefixa cada regra com o
  ID do diagrama; nenhuma cor entra no bloco (ADR-04, item 4). Desvio do texto da opção, não do
  objetivo; registrado no `03`.
- **Onde a renderização é conferida**: o `pages.yml` roda o conferidor antes do upload do artefato,
  como decidido; e, pelo Adendo 3 (RQ-36), o CI de pull request ganhou um job `site` com os mesmos
  passos, sem publicar, só quando o PR toca `docs/` ou `site/` — o `pages.yml` só roda depois do
  merge, e o DG-11 quebrado só apareceu porque o build foi rodado à mão.
- **Legibilidade: tamanho natural, com rolagem dentro do bloco** *(alterado em 2026-09-29: Adendo 6,
  RQ-47 a RQ-49, depois do QA-06 do quality gate)*: com o `useMaxWidth` padrão, o Mermaid entrega o SVG
  com `width="100%"` e o `astro-mermaid` o limita à coluna de cerca de 600 px, e 13 dos 20 diagramas saíam
  com fonte efetiva abaixo de 10 px (o DG-11 a 2,2 px). O mantenedor escolheu "tamanho natural com
  rolagem": o `site/src/styles/kit.css` dá ao SVG uma largura que nunca é o limite e deixa o `max-width`
  que o próprio Mermaid escreve no SVG cortar de volta ao tamanho natural, sem encolher; o que passa da
  coluna rola dentro do `pre.mermaid`, que já tem `overflow: auto`, alinhado ao início para a rolagem
  alcançar as duas bordas. Nenhum bloco muda e o GitHub, que não lê este CSS, fica como estava (RQ-48).
  O conferidor ganhou o CT-B05 (piso de 12 px de fonte efetiva, a 1280×900 e a 390×844, nos dois temas e
  idiomas) e o CT-B06 (o diagrama rola dentro do bloco e a página não); o CT-B05 foi escrito antes do CSS
  (RQ-49). Medido em 2026-09-29: fonte efetiva mínima de 14,0 px (DG-13) em todas as variantes.

### Referências

- `site/astro.config.mjs:rehypePlugins:85` (recusa do rehype), `site/astro.config.mjs:integrations:52`
- `site/package.json:starlight:11-13`
- `site/src/styles/kit.css:'data-theme':46`
- `site/verifica-acessibilidade.mjs:waitUntil:230` (ponto de espera),
  `site/verifica-acessibilidade.mjs:AMOSTRA_CLARA:89`
- Refina: ADR-01 (por que não veio do GitDiagram), ADR-06 (guarda por diagrama)

---

## ADR-03 — removida no step 6 (fundida em ADR-02)

O conteúdo desta ADR (Mermaid inline no `.md`, versionado, como única fonte — `[CT-21]` recusando
imagem em `docs/`) repetia RQ-19 e as alternativas 2 e 3 do ADR-02. O único fato que não estava lá
virou uma linha na alternativa 2 do ADR-02. Referências a "ADR-03" no `01` continuam válidas: leem
esta nota.

---

## ADR-04: Regras comuns dos blocos Mermaid (sem `{{`, sem tema/cor fixa, `accTitle`/`accDescr`, IDs estáveis pt=en)

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

Vinte diagramas escritos por mãos diferentes ao longo do tempo divergem em estilo se não houver
uma convenção única e guardada por teste — e cada uma das regras abaixo já reprovaria (ou já
reprova) algum teste existente do kit se for quebrada.

### Decisão

Toda entrada do catálogo DG-01..DG-20 segue, sem exceção:

1. **Compatível com Mermaid 11.17.2** — nada que só existe na v12 (`usecase-beta` completo,
   `layout: elk` como padrão) nem sintaxe experimental sem suporte igual nos dois lugares
   (`zenuml`, `C4*`, `architecture-beta`, todos proibidos inteiros).
2. **`accTitle` e `accDescr` obrigatórios em todo diagrama** — geram `<title>`/`<desc>` e
   `aria-labelledby`/`aria-describedby` no SVG, que é o que um leitor de tela lê. **Quem cobra é a
   guarda do kit (ADR-06, camada 1), não o axe**: a regra `svg-img-alt` do axe seleciona
   `svg[role='graphics-document']` por igualdade, e o Mermaid grava `role="graphics-document
   document"` — a pesquisa deixou em aberto se a regra chega a se aplicar (`site-e-readme.md`,
   pendência 2), e contar com ela seria contar com um gate que talvez não olhe.
3. **Nenhum `{{` nem `{%`** — `[CT-18]` (`tests/Kit/SiteDeDocumentacaoTest.php:acusaLiquidSolto:227`)
   trata `{{` como delimitador de template solto e reprova a página inteira; isso **elimina o nó
   hexágono do Mermaid** (`id{{texto}}`) de qualquer diagrama nesta entrega.
4. **Nenhum tema ou cor fixos** — sem `%%{init: {"theme": ...}}%%`, sem front matter de tema, sem
   `classDef`/`style` com `fill`/`color`. O GitHub aplica `theme:"default"` ou `"dark"` conforme o
   modo do usuário, e o Starlight segue `data-theme` via `autoTheme` do `astro-mermaid` (ADR-02);
   cor fixa quebra o modo escuro nos dois lugares, e foi exatamente o erro do GitDiagram
   (`classDef` fixo e claro, ver ADR-01).
5. **IDs de nó/participante idênticos em pt e en; só o rótulo traduz.** O ID é o contrato que a
   guarda de RQ-26 lê (comparação estrutural entre as duas árvores de idioma); o rótulo é texto
   livre e entra na checagem de idioma do `[CT-19]` (`né/que/é/são/sobre` em pt, `\bthe\b` em en —
   os rótulos do diagrama caem nessa regex como qualquer outro texto da página).
6. **O que é opcional e vem desligado é marcado como opcional no rótulo ou na nota do diagrama**
   (tenancy, login unificado, registro aberto, demo, 2FA, Docker, IA SaaS) — o erro mais caro do
   GitDiagram (ADR-01) foi justamente apresentar o opcional como sempre-presente.
7. **Compactos.** O GitHub reduz a escala de um `flowchart LR` largo e o `[CT-13]`
   (`tests/Kit/SiteDeDocumentacaoTest.php:'[CT-13]':558`) limita o tamanho do README — teto de
   756/767 linhas (pt/en), hoje em 445/446 — por isso só o DG-01 (19 nós, ~40 linhas estimadas) vai
   ao README, e os diagramas grandes de `docs/` (o DG-02 tem 26 nós; o DG-12, 12 telas com fonte e
   quem grava; o DG-17, os conjuntos inteiros das duas rotas) preferem `TD` a `LR` largo.

### Alternativas Consideradas

1. **Deixar cada diagrama livre para escolher tema/cor** — descartada porque o modo escuro é
   metade do público (o Starlight tem alternador, e o GitHub também) e cor fixa falha em silêncio:
   a página abre, só fica ilegível.

### Consequências

- **Positivas**: um único checklist serve para revisar qualquer um dos 20 diagramas; a guarda
  comum (ADR-06) aplica as sete regras de uma vez, sem repetir lógica por diagrama.
- **Negativas**: nenhuma — são restrições que o kit já teria que respeitar de qualquer forma
  (CT-18, CT-19, acessibilidade).

### Referências

- `tests/Kit/SiteDeDocumentacaoTest.php:acusaLiquidSolto:227`, `:CT-19:430-455`
- `site/verifica-acessibilidade.mjs:serious:286` (nível *serious*)
- Refina: ADR-06 (a guarda que aplica estas regras)

---

## ADR-05: O tipo Mermaid é escolhido pelo que ele preserva do propósito, não pela novidade da sintaxe

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

A pesquisa (`tipos-de-diagrama.md`) mapeou o que cada tipo Mermaid renderiza no GitHub (11.17.2) e
o que o `astro-mermaid` aceitaria. Caso de uso é o caso mais discutido: o Mermaid **tem** sintaxe
nativa de caso de uso desde a v12 (`usecase-beta`), mas o GitHub ainda está na v11.17.2 e não a
reconhece (`grep -c usecase` no script de renderização do GitHub devolve 0).

### Decisão

| Propósito | Tipo escolhido | Por quê |
|---|---|---|
| Caso de uso por papel (DG-02) | `flowchart LR` com atores fora de um `subgraph` que faz a fronteira do sistema | É o único formato com semântica de ator+fronteira+relação que renderiza **hoje**, nos dois lugares |
| Arquitetura em camadas (DG-01), regra de acesso ao painel (DG-03), containers/Docker (DG-18), infra (DG-12), config (DG-14), fluxo do `kit:update` (DG-16), rotas de entrega (DG-17), segundo plano (DG-19), ~~tenancy (DG-20, o fluxo do comando)~~ | `flowchart TD`/`LR` com `subgraph` | Estável nos dois renderizadores, segue o tema, e é o mesmo tipo que `tests/Kit/SiteDeDocumentacaoTest.php:[CT-25]` já usa como precedente de guarda estrutural |
| Sequências de autenticação, convite, instalação, assistente de IA e o request em `/app/{tenant}` (DG-04 a DG-07, DG-11, DG-15 e ~~a sequência curta do~~ o DG-20) | `sequenceDiagram` | Cobre `alt`/`opt`/`loop`, ativação, notas e `autonumber` — confirmado no próprio script do GitHub — sem sintaxe experimental |

*(alterado em 2026-09-29: step 10 do ciclo 2, QA-01 — o DG-20 publicado é um bloco só, o `sequenceDiagram` da
requisição em `/app/{tenant}`; o flowchart do `kit:tenancy` não foi desenhado — decisão da sessão no step 10, lacuna
L-08 do `04` —, e a ordem do comando ficou em prosa, acima do bloco. A linha do flowchart deixa de listar o DG-20)*
| Estados de conta e de convite, sessão autenticada (DG-08, DG-09, DG-10) | `stateDiagram-v2` | Suporta estado composto e escolha; é o vocabulário certo para "de onde vem, para onde vai" com guarda |
| ER do núcleo (DG-13) | `erDiagram` | Suporte confirmado no GitHub 11.17.2 (regex de detecção do script inclui `erDiagram`) e no `astro-mermaid`; notação pé-de-galinha padrão |

### Alternativas Consideradas

1. **`usecase-beta` para DG-02, apostando que o GitHub vai subir de versão em breve.** Descartada:
   publicaria um diagrama que não renderiza HOJE no README nem no arquivo `.md` de `docs/` visto
   pelo GitHub — viola RQ-06 (os diagramas existem para mostrar graficamente como o kit funciona,
   não para ficar em branco).
2. **PlantUML via `astro-plantuml`.** Descartada: dependência de servidor externo no build do site
   (o pacote por padrão chama um servidor PlantUML remoto), e o README ficaria **sem** o diagrama
   correspondente, quebrando RQ-19 (fonte única, mesmo bloco nos dois lugares).
3. **C4 para a arquitetura em camadas (DG-01).** Descartada: sem posição automática, o diagrama
   exigiria manutenção manual de coordenadas a cada nó novo — o oposto de "compacto e fácil de
   manter" que ADR-04 pede.

### Consequências

- **Positivas**: cada diagrama renderiza de fato, hoje, nos dois lugares, sem esperar upgrade de
  ferramenta de terceiro.
- **Negativas**: caso de uso perde o vocabulário formal de `include`/`extend`/generalização do UML
  — vira aresta tracejada rotulada, com a nota explícita de que é aproximação.

### Referências

- `tipos-de-diagrama.md` (pesquisa da sessão), seção "Tabela tipo x renderização"
- Refina: ADR-04 (regras comuns), ADR-01 (por que não copiar o layout do GitDiagram)

---

## ADR-06: Uma guarda por diagrama, não só uma guarda para o total

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

RQ-26 exige que cada diagrama tenha uma guarda que falhe quando o código deixar de bater com ele.
O precedente mais próximo no kit é `[CT-25]` (`tests/Kit/SiteDeDocumentacaoTest.php:'[CT-25]':1046`), que
mantém a tabela por painel do README sincronizada com os painéis registrados — mas essa guarda
confere **uma** tabela contra **uma** fonte. Vinte diagramas, cada um com um fato diferente do
código, não cabem numa única asserção genérica sem perder precisão sobre QUAL diagrama quebrou.

### Decisão

`tests/Kit/DiagramasDaArquiteturaTest.php` (arquivo novo) tem duas camadas:

1. **Regras comuns, uma vez** — extrai todo bloco ```` ```mermaid ```` de `README.md`,
   `README.en.md` e `docs/**/*.md` (reaproveitando `paginasDoSite($idioma)`, `tests/Pest.php:paginasDoSite:1008`,
   já usada pelo `[CT-18]`/`[CT-19]`, somada aos dois READMEs — sem glob novo); para cada bloco
   confere `accTitle`+`accDescr` presentes, ausência de `%%{init`/`classDef` com cor, tipo fora da
   lista proibida (ADR-04/05). Roda uma vez, sobre a coleção inteira de blocos — não por diagrama.
2. **Uma asserção por `DG-xx`, com o fato do código dele** — roda com `->with(['pt', 'en'])`: cada
   bloco de cada idioma é conferido contra o fato do código que a tabela do `01` (coluna "Fato do
   código") descreve — se é `Filament::getPanels()`, uma contagem de `Schema::getForeignKeys`, um
   `array_map('get_class', ...)` sobre middleware, ou uma comparação de opções de uma signature de
   comando. A paridade de IDs entre pt e en, que vêm do mesmo fato do código, sai de brinde — não
   precisa de comparação pt=en separada. Cada `it()` cita o `DG-xx` no nome do teste. *(alterado em 2026-09-29: cita o `[CT-nn]` do `04`; o `DG-xx` está no marcador do bloco)*

Os guardas de DG-17 e DG-18 não entram neste arquivo: `it('[DG-18]')` vive em
`tests/Kit/MysqlNoDockerTest.php`, reaproveitando o `blocoDoServico` que já mora lá, e
`it('[DG-17]')` vive em `tests/Kit/DuasRotasDeEntregaTest.php`, reaproveitando a constante
`FORA_DA_ENTREGA_POR_DECISAO` que já mora lá — nada sobe para `tests/Pest.php`. *(alterado em 2026-09-29: não foi assim — ver "Alterações depois da implementação", abaixo)* Cada um carrega seu
próprio `->skip(fn () => ! naArvoreDoKit(), …)`, padrão de `tests/Kit/AcoesPinadasPorShaTest.php:skip:72`.

Três condições tornam as duas camadas executáveis, e estavam implícitas:

- **Sentinela no arquivo inteiro** (`beforeEach` com `naArvoreDoKit()`): o `tests/Kit` viaja pelo
  `kit:update` e o README/`docs/` não; num projeto instalado a camada 2 compararia o código do
  usuário com um diagrama que não existe ali, e reprovaria por customização legítima.
- **Marcador `%% DG-xx`** na linha seguinte à declaração do tipo de cada bloco: é o que liga o
  bloco ao seu DG — cada `it('[DG-xx]')` da camada 2 o usa para achar seu próprio bloco, e falha se
  ele sumir.
- **IDs extraídos por tipo** (nó de `flowchart`, `participant` de `sequenceDiagram`, estado de
  `stateDiagram-v2`, entidade de `erDiagram`): uma regex única de nó de `flowchart` deixaria passar
  participante trocado entre pt e en. O extrator mora local em `DiagramasDaArquiteturaTest.php`
  (a camada 2 é a única que precisa dele). *(alterado em 2026-09-29: o extrator mora em `tests/Pest.php` — ver abaixo)*

**Restrição de bootstrap**: o arquivo roda com `Tests\TestCase`, sem `permission.teams` e com o
painel `app` sem tenancy; a metade "com tenancy" das guardas de DG-02, DG-03, DG-04 e DG-20 não
cabe nele (o Pest não aceita dois `TestCase`s na mesma pasta). Ela vai para
`tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` (`TenancyTestCase`, `tests/Pest.php:TenancyTestCase:82-85`),
que só afirma o código — o lado do diagrama dessas metades é lido no arquivo `Kit`. *(alterado em 2026-09-29: o irmão também lê o DG-02, o DG-03 e o DG-09 — ver abaixo)*

### Alterações depois da implementação

*(alterado em 2026-09-29: a decisão — uma guarda por diagrama, em duas camadas — ficou; a forma mudou)*

- **Os testes levam os IDs do `04` (`[CT-nn]`), não `[DG-xx]`**: o `04` foi derivado do requisito e é
  o contrato que o mantenedor confirmou; cada cenário acha o próprio bloco pelo marcador `%% DG-xx`.
  O nome do teste que falha diz a regra; a mensagem, o DG e o arquivo.
- **O extrator e o bloco do catálogo moram em `tests/Pest.php`**, não no arquivo Kit: o irmão com
  tenancy também lê bloco (o DG-02, o DG-03 e o DG-09), e `.ai/rules/testes.md` manda helper usado por
  mais de um arquivo para lá. O mesmo valeu para o recorte `blocoDoServico` do `docker-compose.yml`.
  As rodadas 3 e 4 da revisão do diff acrescentaram o extrator de arestas normalizado (Adendos 3 e 4,
  RQ-34), que reconhece toda forma de seta do Mermaid 11.17.2.
- **DG-17 e DG-18 não foram para `MysqlNoDockerTest` nem para `DuasRotasDeEntregaTest`**: as guardas
  deles são cenários do `04` e moram no arquivo Kit, com a sentinela do arquivo inteiro.
- **A camada 2 lê o conteúdo do bloco**: a rodada 1 da revisão do diff provou que 16 guardas não liam
  o bloco real (com quatro diagramas mentindo, a suíte passava), e a rodada 3 estendeu a leitura aos 21
  cenários que só afirmavam a existência do bloco (RQ-35).
- ~~**O que ficou sem guarda do fato**: os três extras (DG-10, DG-19, DG-20) têm as regras genéricas e
  uma relação declarada, com a lacuna L-01 do `04` — os números do DG-10 (1800 s, 5 tentativas), as
  metades do agendador e do Compose do DG-19 e a ordem do `kit:tenancy` (que ficou em prosa, sem
  flowchart) não são conferidos contra o código. Achado do step 10, no `03`.~~
  *(alterado em 2026-09-29: step 10 do ciclo 2, QA-01 — os extras ganharam guarda do fato na segunda
  passada do step 10, num terceiro arquivo)* **Os extras têm guarda própria**:
  `tests/Kit/GuardasDosDiagramasTest.php` confere o DG-10 contra o plugin de bloqueio de cada painel
  (ociosidade, tentativas e o desfecho, CT-106/CT-107), o agendador do DG-19 contra `Schedule::events()`
  (CT-108/CT-109), cada processo do DG-19 contra o comando que o `composer dev` e o `docker-compose.yml`
  lhe dão (CT-110/CT-111), os controles do DG-20 (CT-115) e o fato declarado de cada um dos 20 DGs contra o
  bloco publicado e uma cópia adulterada dele, em pt e en (CT-116); `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`
  confere a ordem da pilha e o desfecho de `GET /app/{tenant}` (CT-112..CT-114). Sem guarda fica só o que o
  DG-20 não desenha: a ordem do `kit:tenancy`, que ficou em prosa (lacuna L-08 do `04`).
- **As guardas de paridade e de opcional leem o publicado, com um extrator só** *(alterado em
  2026-09-29: step 10 do ciclo 2, QA-04, QA-05 e QA-07)*: o CT-03 e o CT-85 comparam os blocos
  publicados pt × en com os extratores de `tests/Pest.php` (`arestasDeFluxo()`, `relacoesDeEr()`,
  `mensagensDeSequencia()`, que passou a ler toda seta de sequência do Mermaid 11.17.2); os clones locais
  (`mensagensDaSequencia()`, `transicoesDoEstado()`, `ordemDoDG20EstaCorreta()`) saíram, e cada helper
  existe uma vez em `tests/Pest.php`. O opcional é conferido por elemento, no escopo dele, e pela chave
  exata (CT-129/CT-130), não mais por bloco e por substring; o título do índice da página é o `accTitle` do
  bloco (CT-131); e o texto visível de cada bloco é do idioma dele (CT-132).

### Consequências

- **Positivas**: quando `DG-11` (a sequência do assistente) quebrar por causa de uma mudança na
  ordem dos guardrails, o nome do teste que falha diz exatamente qual diagrama está errado —
  ninguém precisa reler os outros 19 para saber o que investigar.
- **Negativas**: o arquivo cresce (20 casos + regras comuns); mitigado por manter cada `it()` curto
  e apontando para o `01` para o racional completo.

### Referências

- `tests/Kit/SiteDeDocumentacaoTest.php:'[CT-25]':1046` (precedente de guarda estrutural)
- `tests/Pest.php:paginasDoSite:1008`, `tests/Pest.php:TenancyTestCase:82-85`
- `tests/Kit/AcoesPinadasPorShaTest.php:skip:72` (padrão de `->skip`)
- `.ai/rules/testes.md` (helper cruzado em `tests/Pest.php`)
- Refina: ADR-04 (o que a camada comum confere)

---

## ADR-07 — removida no step 6 (fundida em ADR-04)

RQ-20 (Adendo 2) já decide sozinho que o README leva "1 diagrama + link" — esta ADR só repetia
esse fato. O dado útil que não estava em outro lugar (o teto de `[CT-13]`: 756/767 linhas, hoje
445/446) foi para o item "Compactos" da ADR-04. Referências a "ADR-07" no `01` continuam válidas:
leem esta nota.

---

## ADR-08: DG-10, DG-19 e DG-20 entram como diagramas pertinentes (RQ-25), além do catálogo pedido

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

RQ-25 autoriza a sessão a acrescentar diagramas que julgar pertinentes e que agreguem valor, cada
um justificado aqui.

### Decisão

Três diagramas além dos quatro grupos explicitamente pedidos (visão geral, autenticação, IA/infra,
ciclo do kit):

- **DG-10 — Sessão autenticada** (`stateDiagram-v2`): desafio de 2FA só para quem já confirmou,
  bloqueio por ociosidade (1800s) ou manual, desbloqueio, 5 tentativas → logout forçado. **Valor**:
  o grupo de Autenticação (RQ-22) já cobre login e conta/convite, mas a sessão **depois** do login
  — que é onde o Lockscreen e o 2FA realmente agem — não tinha nenhum diagrama; sem ele, alguém lê
  a documentação e não sabe que existe bloqueio automático por inatividade.
- **DG-19 — Segundo plano: `composer dev` × Docker Compose × agendador** (`flowchart LR`):
  **Valor duplo** — mostra o que roda em background (um dos ganchos gráficos que RQ-09 pede) **e**
  torna verificável por teste as duas correções de RQ-29 (D1: `schedule:work` não vem no
  `composer dev`; D2: o `composer dev` omite o `reverb`) — sem este diagrama, a correção textual
  do README fica sem guarda que impeça a regressão.
- ~~**DG-20 — `kit:tenancy`: de single para multi-organização** (`flowchart TD` + `sequenceDiagram`
  curto): **Valor**: multi-tenancy é a feature opcional de maior impacto estrutural do kit (muda
  rotas, middleware e seeds inteiros), citada no README como recurso de destaque, e não tinha
  nenhuma representação gráfica de "o que acontece quando eu ligo isso".~~
  *(alterado em 2026-09-29: step 10 do ciclo 2, QA-01 — o DG-20 publicado é só a sequência da requisição;
  a justificativa passa a ser a dele)* **DG-20 — Requisição em `/app/{tenant}`** (`sequenceDiagram`):
  **Valor**: multi-tenancy é a feature opcional de maior impacto estrutural do kit, citada no README como
  recurso de destaque, e o que ela muda em **cada requisição** não tinha representação gráfica: o
  `IdentifyTenant` resolve a organização da rota e consulta `canAccessTenant()`, a organização inativa
  responde 404 para todos, inclusive o `master_global`, quem não tem vínculo e não é `master_global`
  também, e só no ramo que permite o `DefinirTenantDePermissoes` fixa o contexto de papéis da organização.
  É o "o que acontece quando eu ligo isso" do ponto de vista de quem usa o painel `/app`, e cada afirmação
  tem guarda (CT-112..CT-115). O que o `kit:tenancy` faz ao ligar o modo (a ordem do comando) ficou em
  prosa, logo acima do bloco, porque o flowchart não entrou nesta entrega (lacuna L-08 do `04`).

### Consequências

- **Negativas**: mais três diagramas para manter (impacto absorvido pela guarda comum do ADR-06).

### Referências

- `critico.md` (pesquisa da sessão), seção 6, veredito "ia 1" e o candidato "Precedência de
  configuração"
- Refina: ADR-06 (a guarda que também protege D1/D2 via DG-19)

---

## ADR-09: GIFs pelo `kit:arte`, generalizado — vídeo fica de fora, e o `install.gif` é refeito a partir de captura real

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

RQ-16/RQ-17 (Adendo 1) pedem GIFs ou vídeos de partes do sistema, condicionado a caber no pipeline
existente sem dependência nova. RQ-27 (Adendo 2) concretiza isso: generalizar
`KitArte::QUADROS_DO_GIF` (hoje uma lista fixa de 3 quadros) para um mapa `clipe → quadros`.

`[CT-21]` (`tests/Kit/SiteDeDocumentacaoTest.php:video:975`) proíbe `<video>`, YouTube e Vimeo
dentro de `docs/` — vídeo fora, RQ-27.

Separadamente, `art/install.gif` (145 quadros, 12 fps, commit `0ce965c` de 2026-08-12) mostra
"Login inicial: `admin@example.com` / `password`" no último quadro — mas desde a correção do #100,
`SenhaDoAdministrador::ehUtilizavel()` recusa exatamente esse valor como senha
(`app/Support/SenhaDoAdministrador.php:PADRAO_PUBLICADO:40`, `:ehUtilizavel:118`) e a instalação
GERA uma senha de 24 caracteres. O GIF também não mostra as etapas reais de geração de senha nem o
banner que a imprime.

### Decisão

1. **Vídeo fica fora desta entrega**, por CT-21 e pela ausência de API de gravação sem gambiarra —
   RQ-16 é atendida só por GIF (slideshow de quadros estáticos, mesmo mecanismo que já existe).
2. **`KitArte::QUADROS_DO_GIF` (lista) vira `KitArte::CLIPES` (mapa `clipe => quadros`)** *(alterado em 2026-09-29: a lista ficou, dentro do mapa — ver "Alterações depois da implementação")*, cada
   clipe gerando seu próprio arquivo em `art/{clipe}.gif`, sem exceção. Quadro de clipe **não**
   entra em `KitArte::IMAGENS` nem vira PNG em `art/` — é o contrato que o `QUADROS_DO_GIF` já tem
   (o docblock de `QUADROS_DO_GIF`, na linha 41 de `app/Console/Commands/KitArte.php` antes da entrega; hoje o de `app/Console/Commands/KitArte.php:CLIPES:70`); o `publicar()` confere `IMAGENS`
   **antes** de pular o quadro de clipe (antes da entrega, o `continue` da linha 134; hoje `app/Console/Commands/KitArte.php:IMAGENS:187` vem antes de `app/Console/Commands/KitArte.php:$quadrosDeClipe:206`), o
   que deixa o `densidade` (cujos 3 quadros já são PNG publicados em `IMAGENS`) participar do mesmo
   mapa sem precisar de origem alternativa. Os clipes desta entrega:
   - `fluxo-import-export` — os 3 quadros que já existem, sem captura nova; já referenciado em
     `docs/pt/recursos/import-export-csv.md:fluxo-import-export:13` (e o par em `docs/en/`);
   - `densidade` — reaproveita os 3 PNG **já publicados** (`densidade-confortavel`,
     `densidade-compacto`, `densidade-denso`), capturados em `tests/Browser/Screenshots`
     (`tests/BrowserTenancy/CapturaDeArteTest.php:it:362`) na mesma execução do `composer art`
     (`composer.json:'art':180-187`), sem navegar de novo;
   - `busca-spotlight` — 2 quadros novos (fechada → aberta), capturados em
     `tests/BrowserTenancy/CapturaDeArteTest.php`, no mesmo padrão de arranjo de painel que os
     outros cenários do arquivo (nunca no `beforeEach` — ver `.ai/rules/testes-browser.md`, "o
     `beforeEach` não arranja painel");
   - `login-unificado` — 2 quadros novos (formulário único → cartões de escolha de painel), reuso
     do fluxo já provado por `LoginUnificadoTest.php` CT-B01, mas capturado como tela estática
     (screenshot, não o teste funcional em si).
3. **`art/install.gif` é refeito a partir de uma transcrição REAL** de uma execução de
   `kit:install`, não de um roteiro inventado — ver o passo dedicado no `01` (Estrutura de
   Implementação). A transcrição e a view que a desenha moram em `tests/Browser/Fixtures/` — não
   em `resources/views/arte/`, que o `kit:update` não entregaria (o `KitUpdate::CAMINHOS_DO_KIT`
   lista `resources/views` subdiretório a subdiretório), deixando o cenário de captura sem a view.
   A senha exibida é **mascarada** com um padrão fixo (nunca o valor gerado de
   verdade, que muda a cada execução), e o roteiro mostra as etapas reais na ordem do
   `KitInstall::handle()` (`app/Console/Commands/KitInstall.php:handle:81-148`): geração de senha,
   formatação do código gerado, banner com a senha mascarada, resumo da customização.

### Alternativas Consideradas

1. **Script Node com Playwright + `recordVideo`, fora da suíte de teste** (caminho C da pesquisa).
   Descartada para esta entrega: perde o oráculo da suíte (precisa de app "de pé" com dados fixos,
   sem o `RefreshDatabase` que os testes garantem) e produziria vídeo — que RQ-16/CT-21 não pedem
   como obrigatório.

### Consequências

- **Positivas**: nenhuma dependência nova (ffmpeg já é usado pelo `KitArte::montarGif` existente,
  Playwright já é dependência tanto na raiz quanto em `site/`).
- **Riscos**: o roteiro do `install.gif` precisa ser fiel ao texto real capturado — um roteiro
  "quase certo" reintroduziria o mesmo tipo de defeito que está sendo corrigido. Ver Riscos no
  `01`.

### Alterações depois da implementação

*(alterado em 2026-09-29: o que a implementação e as rodadas da revisão do diff mudaram)*

- **`QUADROS_DO_GIF` ficou**, como a lista do clipe `fluxo-import-export`, e o `CLIPES` a reaproveita
  — os testes a leem por Reflection pelo nome. São cinco clipes: `fluxo-import-export`,
  `busca-spotlight`, `login-unificado`, `densidade` e `install`.
- **A publicação do GIF é atômica de verdade só no mesmo volume**: a premissa de que `rename()` falha
  entre volumes era falsa (no PHP ele copia e devolve `true`). O ffmpeg escreve num temporário ao lado
  do GIF publicado, em `art/`, e a publicação é um `rename()` no mesmo diretório; como o temporário não
  termina em `.gif`, a chamada leva `-f gif` (a regressão RD4-01, Adendo 5). Uma falha de publicação é
  capturada e avisa "não consegui publicar", uma exceção qualquer num clipe não aborta os outros, e o
  diretório de montagem é limpo antes da cópia e no `finally`. O timeout real de 60 s do `Process` e a
  atomicidade entre volumes não são falsificáveis na suíte — dívida declarada no `03`.
- **O `install.gif`**: nasceu da saída real do `kit:install` de um `create-project` da v0.41.1, com a
  senha gerada mascarada; quatro quadros, fotografados com viewport 1400x2100 (no corte de 875 px o
  resumo, onde fica a senha, não aparecia). A view é renderizada com `view()->file()` e servida por uma
  rota registrada só dentro do teste de captura.
- **Tamanho medido de cada GIF** (P-10, sem teto): no `CHANGELOG.md` e no `01`, passo 19.

### Referências

- `app/Console/Commands/KitArte.php:IMAGENS:112-142`, `:montarGif:256`, `:QUADROS_DO_GIF:45-49`, `:CLIPES:70`
- `app/Support/SenhaDoAdministrador.php:PADRAO_PUBLICADO:40`, `:ehUtilizavel:118`
- `.ai/rules/testes-browser.md` (regra do par captura ↔ `IMAGENS`, e "o `beforeEach` não arranja
  painel")
- Refina: ADR-03 (por que GIF de tela é diferente de diagrama — é captura, não fonte-de-verdade)

---

## ADR-10: Toda afirmação corrigida segue o código, nunca o docblock ou o comentário

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

RQ-29 lista quatro correções encontradas na pesquisa, e o padrão comum entre elas é: o **código**
já faz a coisa certa, mas o **texto ao lado** (README, docblock, comentário) ainda descreve um
comportamento antigo ou nunca existiu. `.ai/rules/specs.md` já registra a regra geral ("justificar
comportamento de pacote só depois de ler o vendor"); esta ADR aplica o mesmo princípio às
afirmações do PRÓPRIO kit sobre si mesmo.

### Decisão

Cada afirmação falsa é corrigida na fonte que ela contradiz, nunca "suavizada":

| Afirmação | Onde | Por que é falsa | Correção |
|---|---|---|---|
| "passkeys" como recurso ativo | `README.md:passkeys:201`, `README.en.md:passkeys:201`, `docs/pt/referencia/pacotes-instalados.md:passkeys:23` (e o par em en); irmãs achadas na revisão: a linha `F-05` de `docs/pt/operacao/roteiro-de-features.md:Passkeys:32` (e `docs/en/operacao/roteiro-de-features.md:Passkeys:30`) e `wikis/pacotes.md:passkeys:10` | `vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasPasskeys.php:passkeys:25` tem `$passkeys = false` por padrão, e `enablePasskeys()` nunca é chamado no kit | Remover "passkeys" das três linhas (e da equivalente em inglês); Breezy continua descrito só por perfil/avatar/2FA |
| "`composer dev` = servidor + fila + vite" | `README.md:vite:365`, `README.en.md:vite:366`; irmãs: `docs/pt/comecar/instalacao-avancada.md:vite:168` (e `docs/en/comecar/instalacao-avancada.md:vite:166`), `docs/pt/referencia/pacotes-instalados.md:concurrently:141` (e `docs/en/referencia/pacotes-instalados.md:concurrently:140`) | O `artisan dev` também sobe o `reverb` — quem o registra é o próprio Reverb (`vendor/laravel/reverb/src/ReverbServiceProvider.php:register:30` → `vendor/laravel/reverb/src/Reverb.php:registerDevCommands:12`), não o `vendor/laravel/framework/src/Illuminate/Foundation/DevCommands.php:registerDefaults:106`, que registra `serve`, `queue:listen`, `pail` (só com `pcntl`) e o `vite` | Reescrever para citar server, fila, vite **e** reverb (mencionar pail como "logs" condicional ao SO, sem prometer no Windows) |
| Resumo do `kit:install`: "password (padrão do kit)" | `app/Support/CustomizadorDaInstalacao.php` (linha 295 antes da entrega) | É o ramo da resposta vazia — exatamente o caso em que a instalação GERA a senha (D4); `password` é recusada por `app/Support/SenhaDoAdministrador.php:ehUtilizavel:118` | Dizer que a senha é gerada e aparece no banner final, sem valor; caso novo no bloco `R9` de `tests/Kit/CustomizadorDaInstalacaoTest.php` |
| O comentário de `routes/console.php` (linha 20 antes da entrega) diz que o `schedule:work` "já incluso no `composer dev`" | `routes/console.php:composer:19`; irmãs: `wikis/arquitetura.md:composer:375` (a mesma frase) e `docs/pt/operacao/roteiro-de-features.md:composer:150` (e `docs/en/operacao/roteiro-de-features.md:composer:148`: "os três primeiros o `composer dev` já resolve", dois deles dependentes do agendador) | O `artisan dev` não registra `schedule:work` — só server/queue/vite/reverb/pail | Reescrever o comentário para não afirmar isso; manter a orientação de rodar `schedule:work` à parte em dev |
| Docblocks contradizendo o código | `app/Providers/Filament/AppPanelProvider.php:canAccessPanel:67` ("qualquer usuário autenticado"), `app/Models/AgenteIa.php:'temperatura':78` ("a temperatura vai direto para o provider como número"), `app/Ai/Agents/GuardaPrompt.php:max_tokens:19` ("temperatura baixa e `max_tokens` curto"), `app/Providers/Filament/InfraPanelProvider.php:'ver-logs':343` (citava `KitServiceProvider.php`, linha 172, para `ver-logs`; o `Gate::define('ver-logs', ...)` real está em `app/Providers/KitServiceProvider.php:'ver-logs':429`), `app/Providers/Filament/AdminPanelProvider.php:launcher:268` (promete "consumo… pertence ao painel de negócio", que o `/app` não tem), `app/Console/Commands/KitInstall.php:idempotentes:27` ("Nenhum passo aborta a instalação") | `AppPanelProvider.php:canAccessPanel:67` contradizia `User::canAccessPanel()` (exige papel, não "qualquer autenticado"); `AgenteIa.php:'temperatura':78` e `GuardaPrompt.php:max_tokens:19` prometiam que a temperatura do catálogo chega ao provider, mas `vendor/laravel/ai/src/Gateway/TextGenerationOptions.php:forAgent:69-76` só lê `temperature()`/`maxTokens()` por método ou atributo do próprio agente, e `AgenteBase` não implementa nenhum dos dois (`grep -rnE "temperature|maxTokens" app/Ai/` não acha nada) — o valor do catálogo nunca sai do banco; `InfraPanelProvider.php:'ver-logs':343` citava uma linha (`KitServiceProvider.php`, linha 172) que era um comentário vazio (só `*`); `AdminPanelProvider.php:onboarding:262-269` prometia um recurso inexistente no `/app`; `KitInstall.php:idempotentes:27` era falso com `--force` + SQLite bloqueado (`BancoSqlite::recriar` lança `RuntimeException` sem captura) | Reescrever cada docblock para descrever o comportamento REAL (acesso por papel; `AgenteIa.php:'temperatura':78` e `GuardaPrompt.php:max_tokens:19` passam a dizer que o cast é `float`, mas o valor do catálogo ainda não chega ao SDK porque `AgenteBase` não implementa `temperature()`/`maxTokens()` — mudar esse comportamento fica fora desta feature, e vira achado para o mantenedor; citação corrigida para `:429`; deixar claro que o consumo de onboarding não existe hoje no `/app`; `KitInstall.php:idempotentes:27` passa a dizer que passos idempotentes não abortam, mas falhas de infraestrutura, como banco preso, podem interromper) |

### Alternativas Consideradas

1. **Apagar os docblocks errados em vez de corrigi-los.** Descartada: o contexto que o docblock
   tenta explicar (por que aquele código existe) continua válido; só a afirmação factual está
   errada. Apagar perde informação útil.

### Consequências

- **Positivas**: README, docs e código passam a contar a mesma história; a citação de código
  errada em `app/Providers/Filament/InfraPanelProvider.php:'ver-logs':343` some, o que também limpa uma citação que
  `tests/Kit/CitacoesDeCodigoTest.php` poderia (e não pôde até agora) verificar como enganosa.
- **Negativas**: nenhuma — são correções de texto, sem risco de regressão funcional.
  *(alterado em 2026-09-29: deixou de ser só texto no instalador. A revisão do diff mostrou que o texto
  novo do resumo também mentia em casos que ninguém tinha listado, e a correção mudou comportamento: a
  linha do resumo tem três valores pela regra de `SenhaDoAdministrador` (que passou a ler a senha do
  `.env` de destino), o `KitInstall` a corrige com o desfecho real da semeadura, banner e resumo dão a
  mesma instrução quando o banco não foi populado, e `SubstituicaoEmArquivo` passou a gravar a barra
  invertida escapada. O risco de regressão funcional existiu e está guardado: `CustomizadorDaInstalacaoTest`
  e `ResumoDoKitInstallTest`, provados vermelhos sem cada correção. O que ficou aberto — banco
  inacessível (RD4-02) e reinstalação sobre admin com `password` (RD4-10) — é dívida declarada no
  `03`)*

### Referências

- `app/Providers/Filament/AppPanelProvider.php:canAccessPanel:67`
- `app/Models/AgenteIa.php:'temperatura':78`, `app/Ai/Agents/GuardaPrompt.php:max_tokens:19`
- `vendor/laravel/ai/src/Gateway/TextGenerationOptions.php:forAgent:69-76`
- `app/Providers/Filament/InfraPanelProvider.php:'ver-logs':343`, `app/Providers/KitServiceProvider.php:'ver-logs':429`
  (`Gate::define('ver-logs', ...)`)
- `app/Providers/Filament/AdminPanelProvider.php:launcher:268`
- `app/Console/Commands/KitInstall.php:idempotentes:27`, `app/Support/BancoSqlite.php:recriar:39`
- `.ai/rules/specs.md`

---

## ADR-11: Crédito ao GitDiagram na página de diagramas, sem embutir

**Status**: Aceita
**Data**: 2026-09-27

### Decisão

A página nova de diagramas do site (`docs/{pt,en}/referencia/arquitetura-em-diagramas.md`) traz,
perto do rodapé, uma linha de texto — não um botão, não um `<iframe>`, não uma imagem — com um link
para `https://gitdiagram.com/gsferro/filament-starter-kit-easy` e o texto (traduzido em cada
idioma): *"O GitDiagram gerou uma primeira leitura deste repositório por IA — não verificada, e os
diagramas desta página a corrigem e ampliam. O diagrama oficial é este."* Nenhuma menção ao
GitDiagram entra no README (RQ-30 combinado com ADR-04: o README já está no limite do que cabe).

### Consequências

- **Positivas**: transparência sobre a origem do rascunho, sem os riscos de embutir conteúdo de
  terceiro que muda sem aviso (ADR-01).
- **Negativas**: nenhuma.

### Referências

- Refina: ADR-01 (a auditoria que fundamenta "não verificada")

---

## ADR-12: `sent.dm` não se aplica — não gera vídeo, é API de mensagens

**Status**: Aceita
**Data**: 2026-09-27

### Contexto

RQ-14 e RQ-15 (Adendo 1) pedem avaliar o `sent.dm` como gerador de vídeo para os diagramas e como
funcionalidade do próprio kit. O link veio com `utm_source=gitdiagram&utm_medium=sponsorship`.

### Decisão

**Não se aplica a nenhuma das duas cláusulas.** A pesquisa (`sent-dm.md`) confirmou, direto na
página do produto: `<title>` "Sent - Unified Messaging API for SMS, WhatsApp & RCS"; nenhuma das 7
ocorrências da palavra "video" no site fala em gerar vídeo (são anexos de mídia em mensagens e a
tag `max-video-preview` do Googlebot). O link é o **anúncio pago** que o próprio README do
GitDiagram exibe (confirmado no clone raso do repositório `ahmedkhaleel2004/gitdiagram`, commit
`abe0620f` — arquivo `src/lib/sponsor-campaign.ts`, constante `sentCampaign`, linhas 21-28; é
código de terceiro, **não** existe neste repositório). Quem gera vídeo é o **próprio GitDiagram**
(`/video` na URL do diagrama) — recurso não solicitado no Adendo 2, então fica fora desta entrega.

Integrar o `sent.dm` como funcionalidade (RQ-15) exigiria: dependência nova pré-1.0
(`sentdm/sent-dm-php`); credencial paga e rota de webhook com HMAC; e dado pessoal (telefone)
tratado fora do país sem LGPD na política de privacidade (hoje só GDPR/SCC) — o kit já redige
telefone como dado sensível (`app/Ai/Guardrails/PiiRedactor.php:PiiRedactor:14`,
`:'telefone':23`). Fora do escopo do Adendo 2.

### Consequências

- **Positivas**: fecha as duas cláusulas do Adendo 1 sem gastar orçamento em algo que o mantenedor
  não pediu (o Adendo 2 já não retomou a integração).

### Referências

- `sent-dm.md` (pesquisa da sessão) — página do produto, código-fonte do GitDiagram
  (`ahmedkhaleel2004/gitdiagram@abe0620f`, arquivo `src/lib/sponsor-campaign.ts`)
- `app/Ai/Guardrails/PiiRedactor.php:PiiRedactor:14`

---

## Superfície Livewire

Não se aplica: nenhuma página/widget/componente; `git diff --stat -- app/Filament app/Livewire`
vazio; `KitArte` é Command, sem `$wire`.
