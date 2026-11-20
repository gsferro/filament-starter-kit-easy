# Decisões Arquiteturais — Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`

## ADR-01: O mapa é gerado no build do site a partir de um modelo versionado; o HTML não entra no repositório

**Status**: Aceita
**Data**: 2026-11-20
**Portões**: difícil de reverter ✅ (o formato do modelo, o gerador e as guardas nascem em torno desta escolha; voltar a versionar o HTML desfaz os três) · surpreendente ✅ (quem procura o mapa no repositório não acha nenhum HTML, só JSON e um script) · trade-off ✅ (repositório leve e evidência sempre da revisão publicada, contra um build que depende de baixar a ferramenta)

### Contexto

O archify entrega cada diagrama como um HTML autocontido de 0,83 a 0,97 MB (1,25 a 1,51 MB nos
grandes, 2,56 MB no comparativo), dos quais cerca de 796 kB são o runtime do visualizador, repetido em
todo arquivo. A demonstração pedida (três painéis, dois modos de tenancy, os dez tipos nativos e o
comparativo, em dois idiomas) passa de cem arquivos depois da decomposição da ADR-07, na casa de
100 a 180 MB por geração. *(alterado em 2026-11-20 — números medidos no spike de escala)*

A evidência que o archify embute (`meta.repository.revision` e `sources`) é presa a **um** commit e
conferida só por arquivo e faixa de linhas. Um HTML versionado não consegue citar o commit que o
contém, e os números de linha guardados envelhecem a cada edição do arquivo citado (P-07).

### Decisão

O repositório guarda o **modelo** do mapa (peças, ligações e um arquivo por diagrama) em JSON, dentro
de `site/` (que já é `export-ignore`). O build do site roda o gerador, que
transforma o modelo no JSON de entrada de cada diagrama, por idioma, resolve as linhas da evidência na
revisão sendo publicada e manda o archify escrever os HTML. Nenhum HTML do mapa é commitado.

*(alterado em 2026-11-20 — spike de escala)* O **layout** de cada diagrama (posição e tamanho de cada
peça) faz parte do modelo versionado e é o mesmo nos dois idiomas. Buscar layout é tarefa de
desenvolvimento, feita por um script versionado e documentado, e nunca roda no build: o spike mediu de
50 a 70 s por mapa e um resultado não monotônico (uma peça a mais derruba um mapa que passava). O build
só **renderiza** (`archify render`, sem navegador): no `pages.yml` e no job `site` do CI o build roda
antes de o Chromium ser instalado. As conferências que precisam de navegador ficam no passo de
acessibilidade (ADR-06) e no desenvolvimento.

### Alternativas Consideradas

1. **Commitar os HTML gerados** — cada regeneração acrescenta dezenas de MB ao histórico do git, a
   evidência aponta para um commit anterior ao que publica, e o diff de qualquer mudança vira ruído
   ilegível na revisão.
2. **Commitar o JSON de entrada do archify por idioma** — duplica o conteúdo em pt e en e repete o
   layout em cada arquivo; cada edição de uma peça vira N edições. O modelo bilíngue com o gerador
   tem uma fonte só.
3. **Gerar o modelo a partir do código, por introspecção, num comando artisan** — põe código de
   aplicação novo no kit sem necessidade (RQ-15), viaja para os projetos derivados pelo `kit:update`, e
   ainda assim não descobre as ligações de uso; a introspecção fica nas guardas (ADR-03, P-05).

### Consequências

- **Positivas**: o repositório cresce só pelo JSON; a evidência de cada HTML aponta para o commit que
  o publica; regenerar não muda bytes se o modelo e o commit não mudam (o archify é determinístico).
- **Negativas**: o `npm run build` do site passa a depender do archify e a demorar mais; o
  `npm run dev` não serve o mapa (a prévia é `npm run build` seguido de `npm run preview`).
- **Riscos**: um build que não consiga baixar o archify falha fechado, com a mensagem de como
  resolver; nunca publica a página do mapa sem os diagramas, porque o `verifica-links` reprova o link
  para HTML ausente.

### Referências

- Wiki ancestral `diagramas-da-arquitetura` (guardas de fato contra o código vivo).
- ADR-02, ADR-03, ADR-04.

---

## ADR-02: O archify chega ao build por um commit fixo do repositório público, sem npm e sem cópia no kit

**Status**: Aceita
**Data**: 2026-11-20
**Portões**: difícil de reverter ✅ (o caminho da ferramenta entra no build local e no de CI, e a guarda de versão depende dele) · surpreendente ✅ (uma dependência do site que não aparece no `package.json` nem no lock) · trade-off ✅ (nenhum peso no repositório e versão auditável, contra acesso de rede ao GitHub no build)

### Contexto

O archify não é publicado no registro npm (o `package.json` dele é `private`) e não tem dependência
de runtime: a CLI roda direto com o Node já instalado. As dependências npm da raiz do kit são
congeladas por teste, e o Adendo 1 só autoriza o archify como dependência nova (RQ-25).

### Decisão

O gerador baixa o archify do repositório público num commit fixo de 40 hex, declarado num único
lugar do gerador, para uma pasta ignorada pelo git dentro de `site/`. Antes de usar, confere que o
commit baixado é o declarado; se não for, ou se o download falhar, o build para. A checagem de
atualização do archify é desligada no build, que não depende de outra rede além desse download.

### Alternativas Consideradas

1. **Vendorizar o archify em `site/`** — dezoito MB no repositório (treze só de exemplos), atualização
   manual por cópia e um diff ilegível a cada versão.
2. **Instalar pelo npm a partir do GitHub (`github:` no `package.json` do site)** — o npm resolve por
   tag ou branch e grava o commit no lock, mas executa os scripts de instalação do pacote; o commit fixo
   no gerador é o mesmo controle sem executar nada do terceiro na instalação.
3. **Clonar o repositório inteiro, com histórico** — mais lento e maior; o download raso do commit fixo
   basta, porque o commit identifica o conteúdo.

### Consequências

- **Positivas**: a versão do archify fica auditável (o commit é o conteúdo); trocar de versão é mudar
  uma linha e regenerar.
- **Negativas**: o primeiro build local precisa de rede e de `git`; o CI também.
- **Riscos**: o repositório do terceiro sumir ou reescrever o histórico. Mitigação: o build falha
  fechado, com a mensagem; a licença (Apache-2.0) permite vendorizar como plano B.

### Referências

- ADR-01. Rule `general.md` (dependência privada não viaja no Composer).

---

## ADR-03: A evidência do modelo é âncora de trecho — arquivo e trecho literal —, e a linha é resolvida no build

**Status**: Aceita
**Data**: 2026-11-20
**Portões**: difícil de reverter ✅ (todas as peças, ligações e fluxos do modelo usam o formato) · surpreendente ✅ (o projeto cita `arquivo:símbolo:linha` em toda a documentação; o modelo do mapa não guarda linha) · trade-off ✅ (o modelo não envelhece a cada edição de um arquivo citado, contra a ambiguidade de um trecho que aparece mais de uma vez)

### Contexto

O modelo tem perto de mil e quinhentas ligações com prova. Se cada uma guardar um número de linha, a
guarda de citações reprova o PR seguinte que mexer em qualquer arquivo citado, mesmo sem mudar o que
a ligação afirma; o modelo vira manutenção de números. O archify, por sua vez, precisa de linha em
cada fonte que embute.

### Decisão

Cada evidência do modelo é `{arquivo, trecho}`: o trecho é um pedaço literal do código que prova a
afirmação. O gerador lê o arquivo **na revisão sendo publicada**, acha a linha da primeira ocorrência
do trecho e entrega `path`, `line` e `end_line` ao archify. A guarda Pest confere, em todo PR, que cada
trecho ainda aparece no arquivo e fora de comentário; quando o código muda o que a ligação afirma, o
trecho some e a guarda reprova.

### Alternativas Consideradas

1. **`arquivo:símbolo:linha`, como no resto do projeto** — certo para wikis e docs, que são lidos
   pontualmente; num modelo de mil e quinhentas provas, cada edição de código vira uma correção de
   números sem mudança de fato.
2. **Só o símbolo (classe ou método)** — prova a existência da peça, não a ligação: o mesmo método
   pode ou não registrar o plugin; o trecho prova a chamada.

### Consequências

- **Positivas**: a guarda reprova só quando o fato muda; a linha publicada é sempre a da revisão
  publicada.
- **Negativas**: um trecho repetido no arquivo resolve para a primeira ocorrência; o autor escolhe um
  trecho que identifique a chamada.
- **Riscos**: trecho curto demais casa com outra coisa. Mitigação: a guarda exige um tamanho mínimo de
  trecho e recusa o que só aparece em comentário.

*(alterado em 2026-11-20 — spike de escala)* Só conta como evidência arquivo **versionado** no
repositório: `vendor/`, `artisan …`, `db:` e linha de log não existem no commit, e o archify recusa a
fonte (no spike, de 18 a 97 referências caíram por isso). Peça que vem de pacote cita a linha em que o
kit a registra ou configura (o provider, o `config/`) ou o `composer.json`. Todo diagrama sai com
`meta.repository` (url e revisão): o conversor do spike o omitia em silêncio quando nenhuma fonte
sobrava, o que deixaria o diagrama sem prova à vista (P-04); a guarda reprova esse caso.

### Referências

- ADR-01. Rule `specs.md` (citação mecanicamente conferível), que segue valendo para a wiki.

---

## ADR-04: O HTML do mapa é escrito no `dist/` depois do build do Astro e linkado por âncora HTML crua

**Status**: Aceita
**Data**: 2026-11-20
**Portões**: difícil de reverter ✅ (a forma do link e o lugar dos arquivos entram na página do site, no README e nas guardas) · surpreendente ✅ (os HTML não estão em `site/public/` nem em `docs/`, e o link da página é HTML cru dentro do Markdown) · trade-off ✅ (nenhuma guarda existente precisa ser afrouxada, contra uma prévia de desenvolvimento que não mostra o mapa)

### Contexto

`docs/` só aceita `.md` e `.yml` e proíbe `<iframe>`; todo `.html` de `site/public/` tem de ser stub de
redirecionamento, na mesma quantidade das páginas. Um link Markdown relativo para um asset é
reprovado pela guarda de links internos, e um link absoluto, pela guarda de base. O único link para
asset que alguma guarda confere é o `<a href>` relativo cru, que o `verifica-links` resolve contra o
`dist/`.

### Decisão

O gerador escreve os HTML em `site/dist/mapa/{pt,en}/` depois do `astro build`, no mesmo
`npm run build`. A página do mapa no site linka cada diagrama com `<a href>` relativo cru; o
`verifica-links` passa a ser a guarda de que todo diagrama linkado foi gerado. O link do README vai
para a página do site, nunca para o HTML. Links profundos do visualizador (`#focus=`, `#route=`) ficam
fora até o `verifica-links` saber distingui-los de âncora.

### Alternativas Consideradas

1. **`site/public/mapa/`** — exige estreitar as duas guardas dos stubs para excluir a pasta, e os HTML
   gerados localmente apareceriam nelas antes de qualquer commit.
2. **Página Astro em `site/src/pages` com `<iframe>`** — fica fora das guardas de paridade e de barra
   lateral, e o `src` do iframe não é conferido por nenhuma guarda.
3. **`art/` ou diretório novo na raiz** — viaja no `composer create-project` e infla todo projeto
   derivado.

### Consequências

- **Positivas**: nenhuma guarda existente muda; um diagrama não gerado reprova o build pelo link
  quebrado.
- **Negativas**: `npm run dev` não serve o mapa; os HTML não passam pela busca do site.
- **Riscos**: o tema escolhido no site não chega ao visualizador, que segue o tema do sistema.
  Declarado na página.

### Referências

- ADR-01. Wiki ancestral `site-de-documentacao` (stubs, conversor e guardas do site).

---

## ADR-05: O catálogo único é o roteiro de features ampliado, com numeração só por acréscimo

**Status**: Aceita
**Data**: 2026-11-20
**Portões**: difícil de reverter ✅ (os números passam a nomear peças do mapa, além de testes, wikis e o CHANGELOG) · surpreendente ✅ (o catálogo continua numa página de operação chamada "Roteiro de features", e não num arquivo de dados) · trade-off ✅ (uma fonte que o leitor já conhece e os testes já nomeiam, contra uma tabela mais larga e um parser de Markdown no gerador e nas guardas)

### Contexto

O roteiro de features nasceu no README em agosto de 2026 com o propósito declarado de numerar "tudo
que o kit entrega" e foi migrado para o site em setembro. Hoje ele não cobre cerca de cinquenta
features que o README, as páginas do site, os pacotes e o código entregam, e tem trechos que o código
desmente. Os números já nomeiam testes de navegador, comentários, wikis e entradas do CHANGELOG, e um
número reaproveitado já causou colisão.

### Decisão

O roteiro, em pt e en, é o catálogo único (RQ-22): ganha as features que faltam, cada uma com o
próximo número livre a partir de F-72, e uma coluna de **origem** (pacote, código próprio ou pacote com
customização própria). Os trechos defasados são corrigidos sem mudar número (P-08). As peças e os
fluxos do mapa citam os números do roteiro; a guarda confere que todo número citado existe e que toda
feature do roteiro aparece em pelo menos uma visualização. A obrigação de manter o catálogo vira rule
do projeto (RQ-23), escopada para não valer em projeto derivado, onde o roteiro não chega.

### Alternativas Consideradas

1. **Catálogo como arquivo de dados (YAML ou JSON) renderizado na página** — `docs/` não tem
   componente para renderizar dados (MDX não resolve dentro de `docs/`), e a página viraria um segundo
   lugar a manter.
2. **O modelo do mapa como catálogo** — é dado de máquina, sem "onde fica", "quem alcança" e "como
   conferir"; seria o segundo catálogo que o RQ-22 proíbe.
3. **Ids provisórios separados (`P-nn`) ao lado dos `F-nn`** — também um segundo catálogo, e colide com
   as premissas `P-nn` das wikis e com comentários do código.

### Consequências

- **Positivas**: uma lista só, que o leitor, os testes e o mapa usam.
- **Negativas**: a tabela ganha uma coluna; a ordem no arquivo continua temática, não numérica.
- **Riscos**: feature nova entrar no código sem número. Mitigação: a rule do catálogo e a guarda de
  cobertura do mapa (a peça nova no painel vivo exige peça no modelo, que exige número).

### Referências

- Página "Roteiro de features" (pt e en). ADR-01, ADR-03.

---

## ADR-06: As páginas do mapa entram no conferidor de acessibilidade por amostra, com dois defeitos do archify tolerados por regra e elemento, e a lacuna declarada

**Status**: Aceita
**Data**: 2026-11-20
**Portões**: difícil de reverter ✅ (o conferidor do site passa a tolerar duas violações `serious` nomeadas; tirar ou ampliar a tolerância muda o que o CI deixa publicar) · surpreendente ✅ (o site reprova qualquer `serious` e o mapa publica duas, de propósito) · trade-off ✅ (publicar os diagramas interativos que o pedido exige, contra duas violações conhecidas de um terceiro que o kit não pode corrigir sem quebrar a proveniência)

### Contexto

O `site/verifica-acessibilidade.mjs` roda o axe (WCAG 2.1 AA) nos dois temas e derruba o job em
qualquer violação `serious` ou `critical` e em qualquer `console.error`. O spike de escala rodou o axe
nos HTML do archify 3.2 de todos os tipos, nos dois temas:

- **`nested-interactive`** (`serious`) em **todo** HTML: o `<svg>` raiz do diagrama tem `role="img"` e
  contém elementos focáveis. O seletor que o axe devolve muda com o tipo (`svg[role="img"]`,
  `svg[data-animation="trace"]`, `svg[data-reader-fit=…]`, `svg[data-sequence-column-fit="spread"]`).
- **`color-contrast`** (`serious`) na contagem de cada grupo do índice de nós
  (`.node-outline-heading > small`): 2,54:1 no claro e 2,51:1 no escuro, em 11 px. O índice se agrupa
  sempre que o diagrama tem fronteiras, raias, estágios ou grupos; o axe só acusa quando a contagem tem
  dois dígitos — com um dígito marca "incompleto", mas o contraste é o mesmo.
- **Comparativo**: a casca abre no tema escuro, ignora `?theme` e não tem violação nesse tema; no claro,
  alcançado pelo botão da própria página, tem seis `color-contrast`.
- O axe não avalia o contraste do texto dentro do SVG (204 textos ficaram "incompletos" num ERD).

Corrigir pós-processando o HTML quebra a proveniência que o archify grava e mexe em código de
terceiro a cada versão.

### Decisão

O site passa a auditar, além das páginas, **um HTML do mapa por tipo, por idioma e por tema**, e o
comparativo no tema em que abre (escuro). *(alterado em 2026-11-20 — step 7, `01` D17)* Quem audita o mapa é um
conferidor próprio, `site/verifica-mapa.mjs`, com as mesmas regras do `site/verifica-acessibilidade.mjs` (axe,
WCAG 2.1 AA): o conferidor do site já carrega os `[CT-B01]` e `[CT-B02]` da wiki `diagramas-da-arquitetura`, e os
CT-B desta wiki, que também começam em 01, moram com a amostra do mapa. Reprova qualquer violação
`serious` ou `critical` e qualquer `console.error`, **exceto** exatamente dois defeitos do archify,
reconhecidos pela regra **e** pelo elemento — nunca pelo texto do seletor:

1. `nested-interactive` cujo alvo é o `<svg>` raiz do diagrama;
2. `color-contrast` cujo alvo é a contagem de grupo do índice de nós (`.node-outline-heading > small`).

A página do mapa no site declara a lacuna: os dois defeitos, o tema claro do comparativo e o
contraste do texto no SVG, que o axe não confere. O catálogo de features e a página de diagramas
Mermaid seguem como a alternativa em texto, já auditada. Reportar os defeitos ao archify é decisão do
mantenedor.

### Alternativas Consideradas

1. **Só declarar a lacuna, sem auditar o mapa** — uma versão nova do archify poderia trazer violação
   nova sem que nada acusasse.
2. **Pós-processar o HTML para corrigir** — quebra a proveniência e vira manutenção de código de
   terceiro a cada versão.
3. **Manter todo grupo do índice abaixo de dez nós** — esconde o defeito do axe sem mudar o
   contraste de 2,5:1.
4. **Auditar todos os HTML** — os HTML de um mesmo tipo têm o mesmo runtime e os mesmos estilos; o que
   muda entre eles já está coberto pelos dois casos tolerados. A amostra por tipo cobre cada renderizador
   em segundos, não em minutos.

### Consequências

- **Positivas**: violação nova em qualquer renderizador derruba o CI; os dois defeitos tolerados têm nome,
  regra e elemento, e não escondem outra coisa.
- **Negativas**: o site publica duas violações `serious` conhecidas.
- **Riscos**: uma versão nova do archify mudar o elemento de um dos defeitos. O CI reprova, e a troca de
  versão (ADR-02) passa a exigir rever a tolerância — é o comportamento desejado.

### Referências

- Spike de escala (`mapa-pesquisa/archify-lab/spike/`, veredito do juiz, item 4). ADR-02, ADR-04.
- Wiki ancestral `site-de-documentacao` (o conferidor e os dois temas).

---

## ADR-07: O mapa de cada painel é decomposto em árvore de registro, resumo e mapas de domínio com orçamento fixo

**Status**: Aceita
**Data**: 2026-11-20
**Portões**: difícil de reverter ✅ (o manifesto, o layout versionado, a página do site e as guardas nascem da decomposição) · surpreendente ✅ (o pedido diz "cada painel" e cada painel vira vários diagramas, sem nenhum que mostre o painel inteiro) · trade-off ✅ (mapas legíveis sem abrir o painel de detalhes, contra mais arquivos e o trabalho de posicionar cada peça)

### Contexto

O spike de escala renderizou o `/admin` (243 peças, 496 ligações) com o archify 3.2:

- **Inteiro**: o `validate` reprova com 7.825 a 14.681 diagnósticos, leva de 25 a 69 s e não gera HTML.
  O roteador aceita no máximo 80 obstáculos e 64 buscas por diagrama, e ligação que atravessa peça é erro
  em qualquer perfil de qualidade.
- **243 peças com 64 ligações**: passa, mas mente: o painel de detalhes diz "4 de saída" num painel que
  tem 68.
- **Só as peças**: passa, mas a busca e o `#focus=` não rolam a página até a peça, que fica fora da tela.
- **Legível sem o painel de detalhes**: até cerca de 30 peças e 40 ligações desenhadas, com no máximo
  um hub de 12 ligações e setas sem rótulo (o rótulo vira obstáculo e corta a capacidade pela metade).
- **Recorte por domínio de funcionalidade** teve coesão 0,57; por tipo de peça, 0,39 (o grupo "recursos e
  models" teria 75 peças).
- O architecture não tem layout automático, e buscar layout custa de 50 a 70 s por mapa, com resultado não
  monotônico.

### Decisão

Para cada painel e cada modo de tenancy em que a estrutura muda (P-09):

1. **Árvore de registro** (tipo `tree`, para a direita): tudo o que o painel registra — recursos, páginas,
   widgets, plugins e middlewares —, conferido contra o painel vivo (P-05). O leque "registra" sai dos
   mapas e fica nela.
2. **Resumo** (`architecture`, até 15 caixas e 25 ligações), com uma porta para cada mapa de domínio.
3. **Mapas de domínio** (`architecture`), recortados por domínio de funcionalidade, com orçamento fixo:
   até 30 peças mais até 6 portas "→ outro mapa", até 40 ligações desenhadas, no máximo um hub com até 12
   ligações, setas sem rótulo — o verbo sai no traço e na cor, explicados num card de legenda. O autor
   mira 80 % de cada teto (24 peças, 32 ligações, hub de 10), para uma peça nova não derrubar o mapa.
4. **Estouro do orçamento**: divide o domínio, ou junta um hub bipartido num agrupamento declarado que
   nomeia os membros (os widgets do dashboard numa caixa). Nenhuma ligação fica só em etiqueta; porta e
   card contam só o que está desenhado.
5. **Toda ligação do modelo é desenhada** em pelo menos um diagrama, direto ou por agrupamento (P-10), e
   toda ligação desenhada aparece no card de prova do próprio diagrama (P-04). A guarda confere as duas
   coisas no JSON do modelo, sem precisar do archify.
6. **Comparativo** (sem → com) só no resumo dos painéis em que a estrutura muda, com a largura do lado
   "com" de até 1.216 px para o delta não cair abaixo do piso de leitura.

O piso de leitura do projeto é 6,5 px projetados, acima dos 6 px do archify: o spike mediu de 6,00 a
6,15 px no Chromium do Windows, e as fontes do Linux do CI podem virar o resultado.

### Alternativas Consideradas

1. **O painel inteiro num diagrama** — reprovado pelo spike (acima).
2. **Recorte por tipo de peça** — coesão 0,39 contra 0,57, e grupos de 70 a 75 peças.
3. **Setas rotuladas** — o teto cai para 13 a 17 ligações por mapa.
4. **Buscar o layout no build** — de 50 a 70 s por mapa, e uma mudança qualquer no kit poderia derrubar o
   build do site sem relação com o que mudou (ADR-01).
5. **Só as peças, como índice navegável** — a busca do visualizador não leva até a peça abaixo da dobra.

### Consequências

- **Positivas**: cada mapa se lê sem abrir o painel de detalhes; o build é determinístico e rápido; "todas
  as ligações" vira uma conta que a guarda faz.
- **Negativas**: muitos arquivos (o manifesto declara a contagem); pôr uma peça nova num mapa de domínio
  exige posicioná-la, com o script de layout.
- **Riscos**: o resumo nunca foi testado no spike — o esqueleto da implementação o testa antes da
  produção em série.

### Referências

- Spike de escala (veredito do juiz, itens 1 e 2). ADR-01, ADR-03, P-05, P-09, P-10.

---

## Superfície Livewire

Não se aplica: a feature não cria nem altera página, widget ou componente Livewire. Nenhum arquivo de
`app/` muda (RQ-15); as telas novas são uma página estática do site e os HTML do archify.
