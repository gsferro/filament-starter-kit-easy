# Casos de Teste de Browser — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Derivado do `00-requisito.md` (RQ-03, RQ-04, RQ-05, RQ-07, RQ-08, RQ-17, RQ-25, P-02, P-03, P-04, P-07, P-10, P-11, desde o step 9, P-14, e, desde o Adendo 2, RQ-27, RQ-28 e P-15, e, desde o Adendo 3, RQ-30 a RQ-32, e, desde o Adendo 4, RQ-33), como o `04`. *(alterado em 2026-11-23 — Adendo 3)* *(alterado em 2026-11-23 — Adendo 4)* Costura: `browser`, a linha "G4 — site construído" de `## Costuras de Teste` do `04` (regras R6, R12, R13, R14, R21, R23, R24, R31, R32 e R40).
> *(alterado em 2026-11-23 — Adendo 2: a Q11 foi respondida pela recomendação. Voltam, com os IDs reservados, o passo 4 do CT-B01 — a marca da peça condicional à vista no carregamento — e o R24-M5. O esperado do CT-B01 e do CT-B02 passa a seguir a definição do Setup Global do `04`: a peça entra no diagrama do modo só quando o painel a registra nele e ela fica disponível nele, com a chave ligada; a ligação dela, idem. Nenhum CT-B novo)*
> *(alterado em 2026-11-23 — Adendo 3: a Q27, a Q28 e a Q29 foram respondidas pela recomendação — RQ-30 a RQ-32 —, e a sessão decidiu o lugar e a redação da marca. O passo 4 do CT-B01 confere a marca na redação decidida, inclusive no `CommandRecordResource` (configuração de pacote) e no `AcessosPorPainel` (herdada), e o card "Condições de disponibilidade" à vista — o R44-M4 do `04`. Nenhum CT-B novo)*
> *(alterado em 2026-11-23 — Adendo 4)*: a Q30 voltou pela recomendação — RQ-33. O passo 4 do CT-B01 confere também a marca do chat do assistente, com as duas chaves, e do checklist do tutorial, inclusive nos dois diagramas escritos à mão que desenham o chat, em que o nó é reconhecido pela prova da classe. *(alterado em 2026-11-23 — step 11)* Neles, a marca fica só na linha do card "Condições de disponibilidade" (P-17 do `00`), e o passo confere essa linha. A redação da configuração de pacote foi confirmada pela sessão. Nenhum CT-B novo
> *(alterado em 2026-11-23 — step 9 do Adendo, achados A2, A3, A5 e A6: a revisão do diff do ciclo dos Adendos 2 a 4. O passo 4 do CT-B01 reconhece o nó do diagrama escrito à mão pela D43 do `04` (Setup Global) e espera, na marca da chave que é lista, o "vazia" / "empty"; a árvore de registro compartilhada só espera a ligação de registro que vale nos dois modos. Nenhum CT-B novo e nenhum mutante novo aqui: os mutantes desses achados morrem no `04`, no CT-14 e no CT-57)*
> *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achados AD2-01 e AD2-02: o CT-B08 vira esquema e ganha quatro controles sintéticos — a condição do modelo que o conferidor não pode ler, decidida pela mesma função que lê o modelo real (`condicaoDaPeca`): o modo em lista, a `ConfigSource` de outro namespace, a `ConfigSource::class` e a chave `APP_DEBUG`. Os mutantes novos, R40-M30 a M32, estão contados no `04`. O esperado do mapa compartilhado só pede a ligação que vale em cada modo dele, como o da árvore (nota dos Pré-requisitos). Nenhum CT-B novo)*
> *(alterado em 2026-11-24 — step 11, achado QA-10: os três `Dashboard` do dashboard dinâmico passam a peças condicionais pela chave `KIT_DASHBOARD_DINAMICO`, e a população vai de 18 a 21. O passo 4 do CT-B01 não muda: o esperado sai da condição do modelo, e a nota dele traz os 14 lugares que passam a pedir a marca. Nenhum CT-B novo, nenhuma linha nova e nenhum mutante novo aqui)*
> *(alterado em 2026-11-25 — step 11, Q32: o `DynamicDashboard` do pacote herda a condição dos três `Dashboard`, e a população vai a 22. O passo 4 não muda; a nota dele ganha o nó dele no `admin-produtividade-e-ui`: são 15 lugares)*
> *(alterado em 2026-11-21: step 9 — achados da revisão do diff. CT-B08 novo (A11, R40); linhas novas no CT-B01 e no CT-B02 (A14: o rótulo da raiz da árvore vem do modelo, não do HTML), no CT-B04 (A16: o tipo do nó no passaporte pt), no CT-B06 (A12: o controle de caminho) e no CT-B07 (A13: a tabela do motivo de enfeite). Os oráculos novos foram conferidos no commit `168d1827`; onde o dado de hoje os reprova, a nota diz)*
> *(alterado em 2026-11-20: rodada de revisão do step 7 — os CT-B saem de `site/verifica-acessibilidade.mjs` para um conferidor próprio; CT-B04, CT-B05 e CT-B06 novos, da P-11; CT-B01 ganha a condição das peças condicionais e o M5)*
> *(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial e fatos de plataforma medidos pela sessão:*
> - *CT-B07 novo (ADV-06);*
> - *CT-B02 e CT-B03 reescritos: o card de prova é texto no painel lateral, e o passaporte mostra as provas da própria peça;*
> - *o CT-B01 perde a metade de condição e o M5, estacionados na Q11 (ADV-31), e ganha o rótulo de cada peça, a escuta de exceção e a conferência de recurso de fora (ADV-28, ADV-29, ADV-14);*
> - *o CT-B04 lê a interface também com a busca e o passaporte abertos (ADV-30);*
> - *o R13-M3 passa a morrer no CT-09 do `04` (ADV-19))*
>
> *(alterado em 2026-11-20: quinta rodada — fechamento da segunda revisão adversarial, a última. Cada oráculo novo ou reescrito foi conferido por comando contra o commit `4adaf9a`, que a sessão publicou durante a rodada com os construtores do mapa, e de novo contra o `4cc9517`, com os 43 diagramas e a página, base congelada desta rodada; os HTML foram gerados pelo `site/mapa/gerar.mjs --saida` numa pasta de rascunho:*
> - *CT-B01 abre também o comparativo, para a escuta de erro e a de recurso de fora (ADV2-20); confere a F-nn no desenho da peça (ADV2-05, D29), o `<h1>` e a interface do visualizador montada; os rótulos vêm da fonte, inclusive do modelo, nos diagramas construídos;*
> - *CT-B02 fica fora do comparativo (ADV2-20), reconhece o item de prova pela forma, conta por ligação, exige o item inteiro (ADV2-22, ADV2-32) e confere os pares de arestas contra a fonte (ADV2-17);*
> - *CT-B03 vira amostra por `architecture`, com peça de pacote no conjunto e a linha do `#L` (ADV2-23), e escuta erro com a busca e o passaporte abertos (ADV2-24);*
> - *CT-B04 ganha critério en e a legenda pt pelos nomes medidos (ADV2-27), lê o texto pt das 486 chaves e escuta erro na interação (ADV2-24);*
> - *CT-B06 ganha a linha de alvos mistos (ADV2-19) e a de controle de processo (ADV2-16);*
> - *CT-B07 lê o catálogo em duas partes e fecha a lista às 14 chaves medidas e às só de marcadores (ADV2-26))*
>
> **Runtime: um conferidor Node + Playwright próprio, `site/verifica-mapa.mjs`**, sobre o site construído e servido pelo `astro preview`. Os dois workflows (`pages.yml` e o job `site` do `ci.yml`) o rodam logo depois do conferidor de acessibilidade, com a mesma prévia de pé. No `pages.yml`, ele roda antes do envio do artefato.
> - **Divergência do template, declarada:** o `pest-plugin-browser` sobe a aplicação Laravel em processo e não serve site estático (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`). O arranjo é o da wiki irmã `diagramas-da-arquitetura`.
> - **Arquivo próprio, e não o `site/verifica-acessibilidade.mjs`:** aquele já carrega `[CT-B01]` e `[CT-B02]` da wiki `diagramas-da-arquitetura`, e os IDs desta wiki também começam em 01.
> - **Quem prova o quê no CI.** O CT-25 do `04` trava a ordem dos dois conferidores nos workflows, e o CT-42, que o passo deste não engole a falha. Que a reprovação vira código de saída diferente de zero, quem prova é o CT-B06, com o próprio conferidor rodado como processo.
> - **`ids-ct.sh`:** ele só lê o `it()` e o `test()` do Pest, então acusa estes CT-B como sem teste. É por desenho.
> - **Mutantes:** cada CT-B traz a sua tabela, igual à da regra correspondente do `04`, porque quem implementa os CT-B recebe só este arquivo.
>
> Comando, em série e nunca em paralelo:
>
> ```bash
> cd site && npm ci && npm run build          # o build gera o mapa em dist/mapa/{pt,en}/
> npx astro preview & npx wait-on "http://localhost:4321/pt/" -t 60000
> node verifica-acessibilidade.mjs            # o conferidor herdado, como hoje
> node verifica-mapa.mjs                      # este conferidor, com a mesma prévia de pé
> ```

## Gate — por que este arquivo existe

- A RQ-07 pede visualização interativa. O archify grava o desenho pronto no HTML, como SVG (medido: sem JavaScript, o `app-acesso` do commit `4adaf9a` tem as 21 peças e as 17 arestas), mas a interação — a busca, o nível de leitura, o passaporte, os textos de interface — só existe com o script do visualizador rodando.
- A P-04 pede a prova à vista no próprio diagrama. O HTML do mapa só existe depois do build, o `site/dist` é ignorado pelo git, e a suíte Pest nunca o vê. O archify só tem prova por nó (`sources`, de 1 a 3), sem prova por aresta. Por isso a prova de cada ligação no artefato depende do gerador, que a põe em cards do painel lateral, e só o HTML construído a mostra.
- A P-07 pede o link para o commit que publica, e só o build sabe qual é.
- A P-11 pede a interface do visualizador no idioma da página e a conferência de acessibilidade por amostra. As duas só o navegador mede. O catálogo en do archify, que o CT-B07 compara com o pt, só existe depois do build, que baixa o archify.
- A RQ-25 proíbe dependência nova de visualização. A biblioteca que entra por CDN ou por arquivo copiado só aparece quando o HTML abre (CT-B01).
- A F-nn de cada peça (D29) aparece no nível de leitura que o visualizador controla, e só o navegador o leva ao nível cheio.
- Nada disso cabe num teste de componente: não há componente Livewire.

## Teto e estouro justificado

O perfil `padrão` dá 1 CT-B de caminho feliz, e aqui há 8 *(7 até o step 9)*. O gate vence o teto, porque cada CT-B mata um conjunto de mutantes que nenhum outro mata:

- o **CT-B01** é o caminho feliz, e mata também os rótulos trocados entre peças, o visualizador que não monta a interface, a exceção não capturada, a biblioteca carregada de fora, a F-nn que não chega ao desenho e *(alterado em 2026-11-23 — Adendo 2)* a marca da peça condicional que o desenho perde ou esconde no carregamento, e *(alterado em 2026-11-23 — Adendo 3)* o card "Condições de disponibilidade" escondido ou cortado;
- o **CT-B02** mata o que só o artefato construído revela: o gerador perde a prova da ligação desenhada por agrupamento ou por porta, ou perde as arestas; o card sai sem o trecho, ou com o trecho cortado; o item não fica amarrado à ligação; o card fica fora do painel lateral;
- o **CT-B03** mata o "não interativo" da RQ-07, o link de evidência que vai para outro repositório, para um ramo, para `vendor/` ou para a linha errada, e a exceção na interação;
- o **CT-B04** mata a interface que cai no inglês, inclusive na busca e no passaporte, o en com o texto pt, a legenda sem os nomes dos tipos e o `lang` errado (P-11);
- o **CT-B05** mata a amostra vazia, o tipo fora da amostra e o comparativo não auditado (P-11);
- o **CT-B06** mata a tolerância larga demais — pelo texto do seletor, pela regra sem o elemento, de uma terceira violação ou da violação de alvos mistos (P-11) — e o conferidor que reprova sem mudar o código de saída;
- o **CT-B07** mata o catálogo pt com o texto en copiado e a lista de textos iguais sem o motivo ou fora do teto (P-11). Ele não precisa do navegador para comparar, mas precisa do build, e por isso mora no conferidor;
- o **CT-B08** *(step 9, achado A11)* mata o conferidor que reprova um JSON quebrado sem nomear o arquivo (P-14). Ele também não precisa do navegador, mas o conferidor importa o Playwright e lê o `axe-core` de `site/node_modules` antes de ler os diagramas, e por isso só roda no job `site`; o gerador e o posicionador têm o mesmo caso na suíte do kit (CT-50). *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* E mata o conferidor que aceita a condição do modelo que o gerador recusa — a fonte "nova" que só muda o namespace ou ganha `::class`, e a chave fora de `KIT_*` —, ou que deixa de recusar o modo em lista: controles sintéticos decididos pela função que lê o modelo real.

Os mutantes que vieram das revisões adversariais não contam para o teto de mutantes da regra.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| rota entre nós, lente, exportação, modo apresentação | são recursos do archify; testá-los é testar o archify. O "não interativo" já morre no CT-B03 |
| troca de tema com o diagrama já aberto | a P-11 pede a amostra por tema, e o CT-B05 abre cada tema pelo mecanismo do visualizador. A troca com o diagrama aberto não tem cláusula |
| o comparativo no tema claro, na amostra de acessibilidade | a P-11 pede o comparativo só no tema escuro |
| o comparativo do `/app` mostra `DefinirTenantDePermissoes` como acrescentado | o delta é cálculo do archify, e o par certo de antes e depois já morre no CT-22 do `04` |
| os cards, o passaporte e o título do comparativo | pela P-04 emendada, a casca do archify não leva cards nem passaporte, e ela não mostra o título do arquivo (medido: sem `<h1>`; o subtítulo é "base → cabeça"). O CT-B01 abre o comparativo para a escuta de erro e a de recurso de fora, e o CT-B04 lê o subtítulo |
| voltar do diagrama para a página do mapa | o `00` não pede |
| a página "Mapa interativo" em navegador | é Markdown sem JS (Superfície de UI). O CT-15, o CT-34 do `04` e as guardas herdadas a cobrem |
| a contagem "N de saída · M de entrada" e o alcance no passaporte | é cálculo do archify, a partir das arestas do diagrama; a R36 do `04` confere as arestas |
| a marca de condição da peça condicional no desenho | estacionada na Q11 (ADV-31): o passo 4 do CT-B01 e o R24-M5 voltam com a resposta. *(alterado em 2026-11-23 — Adendo 2: voltaram; já não é corte)* |
| *(alterado em 2026-11-23 — Adendo 2)* o lugar da peça condicional — que ela só entre nos diagramas do modo em que abre — conferido no navegador | o arquivo de diagrama e o modelo já dizem onde ela entra, e o CT-33 do `04` o confere em todo PR; no navegador provaria o mesmo, mais caro e só no job `site`. O CT-B01 confere o que só o desenho mostra: a marca à vista |
| *(alterado em 2026-11-23 — Adendo 2)* a marca no comparativo | a casca do archify não tem card, e desenha as peças condicionais do resumo do `/app` só dentro das caixas de agrupamento; a marca está nos dois diagramas que ele compara, publicados ao lado |
| a F-nn à vista no carregamento do mapa de arquitetura, sem zoom | o archify desenha o `tag` como detalhe fino, com opacidade 0 no nível de leitura do carregamento (medido). O CT-B01 confere no nível cheio, como a D29 decidiu; se a F-nn tiver de aparecer sem zoom, muda a D29, e o ponto foi ao coordenador |
| a busca pela F-nn, e o `tag` no passaporte | medido: a busca por "F-02" acha as duas telas que a entregam, e o passaporte mostra o `tag`. São caminhos do archify sobre o mesmo `tag`; o oráculo é o `tag` no desenho |
| o título no `document.title` | é a moldura do archify, a chave `page.title` ("Diagrama {título}", "{title} Diagram"); o título que o leitor vê é o `<h1>` |

## Pré-requisitos

- [ ] `npm ci` e `npm run build` em `site/`. O build baixa o archify e gera `dist/mapa/{pt,en}/{slug}.html`.
- [ ] `astro preview` de pé na porta 4321, com `wait-on` antes dos conferidores. Com `DOCS_BASE`, o conferidor prefixa a base, como o de acessibilidade já faz.
- [ ] O commit do build: `git rev-parse HEAD` no checkout. No CI, é o `GITHUB_SHA`.
- [ ] O repositório do kit: o `support.source` do `composer.json` (`https://github.com/gsferro/filament-starter-kit-easy`).
- [ ] O esperado vem da fonte, nunca do `dist/`, para o oráculo não sair do mesmo artefato que ele confere:
  - **no diagrama escrito à mão,** o próprio arquivo de `site/mapa/diagramas/`: as peças da coleção do tipo, com o rótulo no campo do esquema (`label`; `title` na `timeline`; `name` no `waterfall`), e as ligações, com a prova de cada uma;
  - **a coleção de ligações de cada tipo,** no diagrama escrito à mão: `connections` no `architecture`, `edges` no `workflow`, `messages` no `sequence`, `flows` no `dataflow`, `transitions` no `lifecycle`, e `relationships` no `erd` e no `class`. A `timeline` e o `waterfall` não têm ligação. A árvore não tem coleção: na árvore de registro construída, cada registro do painel no modo dela — a relação do painel com a peça, que a hierarquia desenha — é uma ligação desenhada, com o item de prova. O `/app` tem uma árvore por modo; o `/admin` e o `/infra`, uma só cada, compartilhada pelos dois modos *(alterado em 2026-11-23 — Adendo 2: pela RQ-28 e pela RQ-29, o desenho do `/admin` sem tenant não tem o `TenantResource`, e a forma recomendada é uma árvore por modo, como no `/app` — a colisão a do `04`; o registro que a árvore de cada modo desenha é o que fica disponível nele, com a chave ligada)*;
  - **no diagrama construído** (o arquivo com o campo `construtor`), o arquivo diz só o recorte e o layout, e o resto vem do modelo (`site/mapa/pecas.json` e `site/mapa/ligacoes.json`). *(alterado em 2026-11-23 — Adendo 2)* O "modo do diagrama" segue a definição do Setup Global do `04`: a peça entra nele só quando o painel a registra nesse modo e ela fica disponível nele, pela condição do modelo, com a chave ligada; a peça que a condição deixa fora do modo não é esperada no desenho, nem as ligações dela, como o gerador faz — senão o CT-B01 e o CT-B02 reprovam o desenho certo. A marca esperada de cada peça condicional sai da condição do modelo, nunca do HTML. *(alterado em 2026-11-23 — Adendo 3: vale também para a peça que painel nenhum registra e tem condição herdada (RQ-31): o `AcessosPorPainel` não é esperado no `kit-resumo-sem` nem no `kit-multi-tenancy-opt-in`, compartilhado. E o card "Condições de disponibilidade" esperado tem uma linha por peça condicional desenhada, com a condição e a âncora do modelo)* No mapa de arquitetura, as peças são as do recorte que o modelo dá ao modo do diagrama, com o nome do modelo; as caixas de agrupamento e de porta têm o rótulo do arquivo; e cada ligação do modelo no painel e no modo do diagrama vira o par das pontas desenhadas — a peça, ou o agrupamento que a nomeia —, e não vira aresta quando uma ponta não está desenhada ou quando as duas caem na mesma caixa. Na árvore de registro, as peças são as que o painel registra no modo, e cada registro é uma ligação. *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)* Na árvore que vale nos dois modos, "no modo" é em cada um: ela só espera a ligação de registro que vale nos dois, como o conferidor já faz (`site/verifica-mapa.mjs`, `esperadoDaArvore`); o gerador de hoje guarda a ligação pelo primeiro modo do diagrama, e o CT-14 do `04` o reprova. No dado publicado, a única árvore compartilhada, o `infra-registro`, não tem ligação de um modo só. *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* O mesmo vale para o mapa de arquitetura que vale nos dois modos: ele só espera a ligação que vale em cada modo dele, entre pontas que o modelo registra em cada um. **Hoje** o conferidor espera no mapa compartilhado a ligação que vale em algum modo dele (`site/verifica-mapa.mjs`, `esperadoDoMapa`, `l.modos.some`, no commit `b130c686`): com o gerador certo, o CT-B01 e o CT-B02 reprovariam o desenho certo na primeira ligação de um modo só num mapa compartilhado. No dado publicado não há nenhuma (0 das 281 ligações com as duas pontas num mapa compartilhado), e o gerador que a desenhasse morre no CT-14 do `04` (R12-M11); a sessão alinhou o esperado no `68a1f789` (`modos.every`), sem linha nova (Cogitado e cortado do `04`), e o conferidor passou no build real depois. *(alterado em 2026-11-21 — step 9, achado A14)* A raiz da árvore é a peça painel do arquivo (`painel:{painel}`), e o rótulo esperado dela vem do modelo, como o de toda peça construída — o nome dela no modelo (`/app`) —, nunca do `data-node-label` lido no HTML. Decidido pela sessão (Q23 do `04`): a raiz leva exatamente o nome da peça no modelo, como toda peça construída.
- [ ] O catálogo de interface en do archify do commit fixado, que o build baixa (`locales/en.json`, 486 chaves). O texto pt de cada chave vem de duas partes: as 390 chaves de interface que o archify embute em cada HTML (`<script id="archify-i18n-data">`), e as 96 que ele aplica no render e não embute, lidas na fonte do kit (o `examples/locales/pt.json` do archify e o `site/mapa/interface.json`, que o build junta).
- [ ] As rotas do mapa vêm dos arquivos de diagrama, e não de uma varredura do `dist/` por `index.html`, como a do conferidor herdado (`site/verifica-acessibilidade.mjs:'index.html':78`, `site/verifica-acessibilidade.mjs:todas:82`).
- [ ] As escutas ficam ligadas antes do `goto`: o `console` do tipo error, o `pageerror` (exceção não capturada) e o `request` (toda requisição da página).
- [ ] Nunca esperar por segundos fixos. A espera é por estado: o nó desenhado, o resultado da busca montado, o nível de leitura cheio, o passaporte aberto, o tema aplicado.
- [ ] O axe vem de `node_modules/axe-core`, como no conferidor herdado. Os controles sintéticos do CT-B06 e as cópias do CT-B07 passam pela **mesma** função que decide o real, como os controles de `site/verifica-acessibilidade.mjs`. *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* Os controles da condição do CT-B08 também: a mesma `condicaoDaPeca` que lê o modelo real.

## Seletores

*(alterado em 2026-11-20: quinta rodada — medidos no commit `4adaf9a`, no Chromium, em `architecture`, `tree`, `timeline`, `waterfall` e no comparativo; `erd`, `sequence`, `workflow` e `dataflow` medidos no trabalho em andamento da sessão, fora de commit; `lifecycle` e `class` medidos no commit `4cc9517`, que a sessão publicou depois, com os três ciclos de vida, os três diagramas de classes e a pilha da requisição)*

| Elemento | Seletor | Já existe? |
|---|---|---|
| nó desenhado de uma peça | `svg [data-node-id]`, com o rótulo em `data-node-label` | sim, medido: um elemento por peça no SVG. Sem o escopo `svg`, o seletor pega também o resultado da busca, que traz `data-node-id` (por isso a leitura antiga contava 82 elementos para 41 peças). Em `lifecycle` e `class`, medido no commit `4cc9517` |
| aresta desenhada | `svg [data-edge-from][data-edge-to]`, com `data-edge-id` | sim, medido em `architecture`. Há mais elementos que pares (62 elementos e 43 pares no `app-resumo-com` do esqueleto), e por isso a conta é de pares únicos |
| card do painel lateral | `.reader-rail .card`, sempre presente, sem depender de foco; o item, `.reader-rail .card li` | sim, medido |
| item de prova | o item de card com a forma "origem → destino (verbo): arquivo:linha — trecho" | sim, medido: os outros cards do mapa construído ("Como ler as setas", "Quem está em …") não têm a forma |
| F-nn no mapa de arquitetura | o texto do `tag`, um `[data-detail="fine"]` dentro do nó da peça | sim, medido: opacidade 0 no nível "read" do carregamento e 1 no nível "full" |
| F-nn na árvore de registro | o sublabel do nó da peça | sim, medido: opacidade 1 no carregamento |
| nível de leitura | `.diagram-container[data-detail-level]` ("map", "read" ou "full"); o botão `[data-view="in"]` aproxima 25 % por clique, e o nível "full" começa em 175 % de zoom | sim, medido |
| campo de busca | o atalho `/` foca `input.node-finder-input` | sim, medido: o texto de exemplo é "Pesquisar rótulos ou IDs" em pt e "Search labels or IDs" em en |
| resultado da busca | `.node-finder-result`, com `data-node-id`, um por peça desenhada | sim, medido; sem JavaScript, nenhum |
| passaporte da peça em foco | `.semantic-passport-detail` (o sublabel, `#focus-detail`), dentro de `.relationship-lens-copy`; o tipo, em `#focus-kind` (`[data-passport="kind"]`), dentro de `.semantic-passport-meta` | sim, medido: o rótulo, o `tag`, "origem verificada {dono}/{repo} @ {commit}" e os links; o tipo, em `#focus-kind` *(alterado em 2026-11-21 — step 9, correção devolvida pelo executor de browser: o tipo fica em `#focus-kind`)* |
| link de evidência do passaporte | `a[href*="/blob/"]` dentro do passaporte, no formato `https://github.com/{dono}/{repo}/blob/{commit}/{arquivo}#L{linha}` | sim, medido |
| título do diagrama | o `<h1>`; o `document.title` é a moldura do archify | sim, medido |
| subtítulo do comparativo | `p.subtitle`, "base → cabeça" | sim, medido |
| legenda dos tipos de peça | `svg g[data-legend-kind]`, com o rótulo em `data-legend-label` | sim, medido no mapa de arquitetura, o único tipo cuja legenda nomeia os tipos de peça |
| idioma do documento | `document.documentElement.lang`, que segue o `meta.locale` | sim, medido: `pt-BR` e `en`; o comparativo, `en` nos dois |
| catálogo de interface embutido | `<script id="archify-i18n-data" type="application/json">`, com `locale` e `messages` | sim, medido: 390 chaves no pt e no en |
| aviso de idioma reserva | o aviso que o archify mostra quando falta a tradução de uma chave | medido: não existe no DOM; é do build do archify (código `i18n/locale-fallback`). A conferência é a ausência de elemento de aviso e de texto de fallback, mais o CT-B07 sobre o catálogo *(alterado em 2026-11-20 — guardas do passo 8, correção devolvida pelo executor de browser)* |
| alvo tolerado 1 | o `<svg>` raiz do diagrama, resolvido como **elemento** no DOM, nunca pelo texto do seletor do axe | lido no `site/verifica-mapa.mjs`: o `closest('svg')` de `#archify-diagram-title` |
| alvo tolerado 2 | `.node-outline-heading > small`, a contagem de grupo do índice de nós, resolvido como **elemento** | lido no `site/verifica-mapa.mjs` (`elemento.matches`); no DOM, a descobrir |
| tipo do nó de diagrama de classes *(step 9, achado A16)* | `svg [data-node-id]`, com o `kind` do arquivo em `data-class-kind` e o nome do tipo, traduzido no render, em `data-node-context`; no passaporte aberto, o tipo em `#focus-kind` *(alterado em 2026-11-21 — step 9, correção devolvida pelo executor de browser: o tipo fica em `#focus-kind`)* | sim, medido no commit `168d1827`, no `classes-heranca-e-contratos`: em pt, "Interface", "Classe" e "Classe abstrata" nos dois lugares |
| traço da aresta *(step 9, achado A1)* | a classe `a-{variante}` do `<path>` de `svg [data-edge-from][data-edge-to]` (`a-emphasis`, `a-default`, `a-dashed`, `a-security`) | sim, medido no commit `168d1827`, no `app-acesso-e-autenticacao`; o CT-46 do `04` confere a variante na entrada do gerador, e não aqui |
| marca da peça condicional *(alterado em 2026-11-23 — Adendo 2)* | no nó da peça, `svg [data-node-id="{id archify da peça}"]` — *(alterado em 2026-11-23 — Adendo 4)* no diagrama escrito à mão, o id do nó do arquivo, achado pelo nome exato ou pela prova da classe (o `widget` do `app-chat-de-ia`) *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)*, pela D43 do `04`: a prova primeiro, e o nome só sem homônima —, o texto do rótulo secundário, com a opacidade computada no nível de leitura do carregamento; na peça que é membro de agrupamento, o item `.reader-rail .card li` do card "Quem está em …" que a nomeia | não: o mapa de hoje não marca nada. O rótulo secundário é o lugar da recomendação da Q?3 do `04` (`@premissa`); na árvore, o sublabel já foi medido à vista no carregamento (linha da F-nn acima) *(alterado em 2026-11-23 — Adendo 3: o lugar foi decidido pela sessão; no mapa de arquitetura, a opacidade do rótulo secundário no carregamento continua a medir)* *(alterado em 2026-11-23 — Adendo 4)* E no participante de `sequence` (o chat no `app-chat-de-ia`) e no nó de `dataflow` (o chat no `ia-do-prompt-ao-ledger`), também a medir *(alterado em 2026-11-23 — step 11, Adendo 4: medido — a marca não cabe neles: medido no archify (commit `4478129`), a caixa do participante do sequence chega no máximo a 190 px (`renderers/sequence/render-sequence.mjs`, `participantSpreadGeometry`), e o nó do dataflow, sem largura explícita, à largura do estágio, 168 px (`renderers/dataflow/dataflow-compiler.mjs`, `stageW`); a marca do chat pede cerca de 345 px e 381 px, e a largura explícita que a comportaria quebra o roteamento das setas *(alterado em 2026-11-23 — step 9 do Adendo, RD-05: dizia "96 px fixos" e "144 px", a medida daqueles diagramas, e não o limite do archify)*. No diagrama escrito à mão, a marca fica só na linha da peça no card "Condições de disponibilidade" (R42 do `04`))* |
| card "Condições de disponibilidade" *(alterado em 2026-11-23 — Adendo 3)* | o `.reader-rail .card` cujo título é "Condições de disponibilidade" ("Availability conditions" em en); a linha, `.reader-rail .card li` | não: o gerador ainda não o monta. Decisão de desenho da sessão (R44 do `04`) |

---

## CT-B01: cada diagrama do mapa abre com as peças e os rótulos da fonte, a interface do visualizador montada e a F-nn no desenho da peça, sem erro nem exceção, sem recurso de fora

**Por que browser**: o desenho vem pronto no HTML, mas a interface do visualizador — a busca, o nível de leitura que mostra o `tag` — só existe com o script rodando. A exceção, a requisição para fora e o idioma da interface também só aparecem quando a página abre.

```gherkin
# language: pt
  Cenário: [CT-B01] cada diagrama do mapa abre com as peças e os rótulos da fonte, a interface do visualizador montada e a F-nn no desenho da peça, sem erro nem exceção e sem recurso de fora, nos dois idiomas
    Dado o site construído pelo build do commit, servido pela prévia
    E os arquivos de diagrama do mapa e o modelo de peças e de ligações, com os textos em pt e em en
    Quando o conferidor abre cada diagrama publicado, em pt e em en, inclusive o comparativo
    Então, em cada diagrama fora o comparativo, toda peça que a fonte dá a ele está desenhada e tem, pelo id, o rótulo que a fonte dá a ela no idioma da página
    E, em cada diagrama fora o comparativo, a interface do visualizador montou: a busca tem um resultado por peça desenhada, pelo id
    E, nos diagramas construídos do modelo, a F-nn de cada peça com nó próprio que entrega feature está no desenho dela — todas, até três; acima de três, as três primeiras na ordem do modelo e "+N", com N o resto —: no mapa de arquitetura, no `tag`, com opacidade 1 depois de o visualizador chegar ao nível de leitura cheio; na árvore de registro, no sublabel, à vista no carregamento
    E, nesses diagramas, o card "F-nn de cada peça" ("F-nn of each piece" em en) traz, à vista sem foco e inteira, a linha "{nome da peça}: {todas as F-nn dela, separadas por vírgula}" de cada uma dessas peças
    E, em cada diagrama fora o comparativo, toda peça condicional que ele desenha mostra, à vista no carregamento — sem zoom, sem busca e sem foco —, a marca da condição que o modelo dá a ela, no idioma da página: no nó dela, com opacidade 1 no nível de leitura do carregamento; ou, se ela é membro de agrupamento, na linha dela no card que a nomeia; ou, no diagrama escrito à mão, só na linha dela no card "Condições de disponibilidade" *(alterado em 2026-11-23 — step 11, Adendo 4)*
    E, nesses diagramas, o card "Condições de disponibilidade" ("Availability conditions" em en) traz, à vista sem foco e inteira, uma linha por peça condicional que o diagrama desenha, com a condição e a âncora que o modelo dá a ela
    E, em todo diagrama, inclusive o comparativo, nenhum erro de console e nenhuma exceção não capturada são registrados
    E, em todo diagrama, inclusive o comparativo, nenhuma requisição da página sai da origem da prévia, fora `data:` e `blob:`, e o documento não tem `<script src>` nem `<link rel="stylesheet">`
    E, em cada diagrama fora o comparativo, o `<h1>` é o título do arquivo no idioma da página, com os acentos intactos
    E, em cada árvore de registro, a raiz é a peça painel do arquivo e tem, pelo id, o rótulo que a fonte dá a ela: o nome da peça painel no modelo
    E o total de diagramas abertos é o número de arquivos de diagrama vezes dois
```

*(alterado em 2026-11-21 — step 9, achado A14)* **A raiz da árvore.** O conferidor dava à raiz o esperado `null`, e o CT-B01 não conferia o rótulo dela; o CT-B02 montava o prefixo dos itens de prova com o rótulo lido da raiz no próprio HTML. Uma raiz com o rótulo de outro painel ficava verde nos dois. O passo novo traz a raiz para a regra do rótulo de cada peça construída (R24): o nome da peça no modelo. **Hoje o dado reprova o passo novo:** medido no commit `168d1827`, a raiz desenha "Painel /app" em pt e "Panel /app" em en, e o nome da peça `painel:app` no modelo é `/app`; o gerador monta assim a raiz das quatro árvores (`site/mapa/gerar.mjs`, `arvoreDeRegistro`, no commit `168d1827`), e as 8 raízes (4 árvores × 2 idiomas) reprovam. *(alterado em 2026-11-21 — step 9: Q23 decidida pela sessão)* A raiz leva o nome do modelo (`/app`): as 8 raízes de hoje são vermelho de implementação, e o esperado sai do modelo, nunca do HTML.

*(alterado em 2026-11-21 — step 9, re-revisão: achado do passe 1)* **A caixa que o filtro de modo esvazia.** O esperado das caixas de agrupamento do conferidor exclui a caixa que o filtro de modo esvazia, como o gerador faz (P-13, R38): o gerador tira do desenho o agrupamento sem membro no modo do diagrama (`site/mapa/gerar.mjs:agrupamentos:501`), e o conferidor, no HEAD `bc27379f`, só filtra os membros (`site/verifica-mapa.mjs`, `esperadoDoMapa`, no commit `bc27379f`) e ainda espera a caixa. Medido nesse commit: nenhuma das 56 caixas de agrupamento dos 46 mapas construídos fica vazia no modo do diagrama, e por isso a divergência não aparece no dado de hoje; ela apareceria no primeiro mapa de um modo só com um agrupamento só de peças do outro modo, com o CT-B01 reprovando um desenho certo.

*(alterado em 2026-11-23 — Adendo 2)* **A marca da peça condicional (o passo 4).**
- **O que confere.** Em cada diagrama fora o comparativo, nos dois idiomas, toda peça condicional que o diagrama desenha mostra a marca à vista no carregamento: no nó, a opacidade computada do texto da marca é 1 no nível de leitura com que o visualizador abre, sem clicar em `[data-view="in"]`, sem busca e sem foco; na peça que é membro de agrupamento, a linha dela no card "Quem está em …", à vista e inteira, traz a marca. *(alterado em 2026-11-23 — step 11, Adendo 4)* No diagrama escrito à mão, o nó não leva a marca — a caixa do participante do sequence e a do nó do dataflow não a cabem (R42 do `04`) —, e o passo confere a linha da peça no card "Condições de disponibilidade".
- **De onde vem.** "À vista no carregamento" vem da RQ-28: a peça desenhada sem a marca à vista é mostrada como sempre disponível. Por isso o `tag`, que fica com opacidade 0 no carregamento (a linha da F-nn, acima), não serve. O conteúdo vem da RQ-27 — o modo, ou a chave e o valor de fábrica, ou os dois —, e o esperado sai da condição do modelo, nunca do HTML, pelo id da peça: o `UserResource` e o `ConviteResource` do `/admin` não têm condição, e os do `/app` têm.
- **`@premissa` (Q?3 do `04`).** O lugar — o rótulo secundário do nó — e a redação — o modo pelo termo da coluna da grade da página do mesmo idioma ("com tenant ativo", "tenancy on"), a chave como `KIT_AGENTES_IA=false` — são a recomendação da Q?3, de raia desenho. Se a sessão decidir outro lugar ou outra redação, muda o seletor e o texto esperado; o oráculo "à vista no carregamento, com a condição do modelo" não muda. *(alterado em 2026-11-23 — Adendo 3: decididos pela sessão — o rótulo secundário da caixa e o item do agrupamento que nomeia a peça; o modo, "só com tenant ativo" / "tenancy on only"; a chave, "KIT_HUB=false de fábrica" / "KIT_HUB=false by default"; a configuração de pacote, pelo caminho, `command-center.sources`, com a fonte que falta de fábrica (`DatabaseSource`) — redação confirmada pela sessão *(alterado em 2026-11-23 — Adendo 4)* —; a peça de modo e chave, as duas. E o card "Condições de disponibilidade" / "Availability conditions", com a linha de cada peça condicional desenhada e a prova da condição — a R44 do `04`)*
- **Fora.** O comparativo, cuja casca não tem card e desenha as peças condicionais do resumo do `/app` só dentro das caixas de agrupamento; a peça da Q27 (o `CommandRecordResource`) e a da Q28 (o `AcessosPorPainel`), até as respostas. *(alterado em 2026-11-23 — Adendo 3: as duas entram, pela RQ-30 e pela RQ-31; nenhum plugin nem middleware tem marca (RQ-32); fica fora o componente flutuante ligado a chave, até a Q30 do `04`)* *(alterado em 2026-11-23 — Adendo 4)* O componente flutuante entra, pela RQ-33: o chat, com as duas chaves — `KIT_AGENTES_IA=false` e `KIT_AGENTES_IA_PAINEIS` vazia, "de fábrica" / "by default" —, e o checklist. No diagrama escrito à mão, o nó é a peça pelo nome exato ou pela prova que declara a classe: o participante "AssistenteChat" do `app-chat-de-ia` é o chat pela prova `class AssistenteChatWidget extends Component`, e a marca dele, escrita no arquivo, aparece no rótulo secundário. *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* A última frase caiu com a P-17 do `00` (step 11): a marca do chat, escrita no arquivo, fica só na linha dele no card "Condições de disponibilidade", e não no rótulo secundário — como diz "O que confere", acima.
- *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* **O nó do diagrama escrito à mão é o da D43 do `04`** (Setup Global), a mesma regra do CT-33, do CT-57 e do CT-45:
  - o nó é a peça cuja classe uma fonte (`sources`) do nó declara — o arquivo da fonte é um arquivo de evidência da peça no modelo, e o trecho declara a classe (`class`, `interface`, `trait` ou `enum` seguido do nome), sem precisar ser igual ao trecho da evidência;
  - sem fonte que declare classe de peça, o nó é a peça do nome exato do rótulo, só se uma peça só tem esse nome; com a prova, a homônima do rótulo não entra;
  - com homônimas e sem prova, o nó não designa peça, e o passo não espera linha de card para ele (a linha que vier mesmo assim é a Q?6 do `04`).
  - **Hoje** o conferidor soma o nome à prova e toma toda peça do nome do rótulo (`site/verifica-mapa.mjs`, `pecasDoNo`, no commit `1dd48acd`): com a homônima, ele esperaria a linha da condicional para um nó que a prova faz ser a outra. Medido no HEAD `1dd48acd`, nos 339 nós dos diagramas escritos à mão: um só tem rótulo de peça homônima — o `pagina` do `configuracoes-config`, a página do `/admin` pela prova —, e nenhuma das duas homônimas tem condição: no dado publicado, o esperado do passo 4 não muda.
- *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)* **A marca da chave que é lista leva o "vazia" / "empty"**, o valor de fábrica dela: a do chat é "KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS vazia de fábrica" / "KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS empty by default", no nó ou na linha do membro do construído e na linha do card dos escritos à mão. O esperado da marca traz a palavra do idioma da página. **Hoje** o conferidor espera só o nome da variável (`site/verifica-mapa.mjs`, `partesDaMarca`, no commit `1dd48acd`), e a marca sem a palavra passaria; o gerador e os dois cards escritos à mão a escrevem certa (varrido no HEAD `1dd48acd`, nas 186 entradas que o gerador monta: os 16 lugares do chat — o nó do `kit-ia`, a linha de membro dos dois `kit-resumo` e a linha do card "Condições de disponibilidade" do `kit-ia`, dos dois `kit-resumo`, do `app-chat-de-ia` e do `ia-do-prompt-ao-ledger` —, nos dois idiomas). O gerador que perde a palavra morre no CT-57 do `04` (R42-M7 e M8), na entrada do archify; o passo confere que o desenho a mostra.
- **Medido no HEAD `c642c23c`.** O passo reprova: o mapa não marca nada, e as 14 peças condicionais do discriminante estão desenhadas em 70 lugares dos arquivos de hoje — 20 nós de mapa, 22 nós de árvore e 28 linhas de membro —, nos dois idiomas. *(alterado em 2026-11-23 — Adendo 3: com o `CommandRecordResource` e o `AcessosPorPainel`, 16 peças em 76 lugares — 22 nós de mapa, 23 nós de árvore e 31 linhas de membro —, em 23 arquivos de diagrama; nenhum tem o card)* *(alterado em 2026-11-23 — Adendo 4)* Com o chat e o checklist, 18 peças em 85 lugares de 28 arquivos — 25 nós de mapa, 23 nós de árvore, 35 linhas de membro e 2 nós de diagrama escrito à mão —, nenhum com marca.
- *(alterado em 2026-11-24 — step 11, achado QA-10 do `06`)* **Os três `Dashboard` do dashboard dinâmico.** Eles só abrem com `KIT_DASHBOARD_DINAMICO`, desligada de fábrica, porque o `mount()` os devolve ao `DashboardClassico`; a sessão decidiu que são peças condicionais pela chave (Setup Global do `04`, o discriminante que monta a página). A população vai a 21. Quando o modelo tiver a condição deles, o esperado do passo 4 os inclui sem mudar o passo, porque o conferidor tira a marca e a linha do card da condição do modelo. Medido no HEAD `2a4df758`, são 14 lugares por idioma, em 14 arquivos construídos:
  - 4 nós de mapa: o `admin-produtividade-e-ui`, o `app-produtividade-e-ui-sem` e o `-com`, e o `infra-produtividade-e-ui`;
  - 5 nós de árvore: o `admin-registro-sem` e o `-com`, o `app-registro-sem` e o `-com`, e o `infra-registro`;
  - 5 linhas de membro: os resumos `admin-resumo-sem` e `-com`, `app-resumo-sem` e `-com`, e `infra-resumo`.

  Nenhum diagrama escrito à mão os designa, e a exceção da P-17 não os alcança. Hoje o passo passa sem eles: o modelo não tem a condição, e o conferidor não pede marca. O passo 4 não acusa essa omissão, e quem a mata é o CT-32 e o CT-56 do `04`, que conferem a condição contra o código. Nenhuma linha nova e nenhum mutante novo aqui. *(alterado em 2026-11-25 — step 11, Q32: com o `DynamicDashboard` do pacote, que herda deles (Q32 do `00`), a população vai a 22, e os lugares a 15. O nó dele no `admin-produtividade-e-ui` é o único lugar dele: não é membro nem nó de árvore, e nenhum escrito à mão o designa)*

*(alterado em 2026-11-20: ADV-31, ADV-28, ADV-29 e ADV-14)*
- **Saiu a condição.** A metade de condição (o passo 4) e o M5 estão estacionados na Q11, com os IDs reservados. *(alterado em 2026-11-23 — Adendo 2: voltaram — o passo 4, acima, e o M5, na tabela)*
- **O rótulo de cada peça.** O par id e rótulo pega a troca de rótulos entre peças, que a contagem e um conjunto de nomes deixam passar.
- **Requisições e elementos.** O HTML do archify, no commit fixado, é autocontido: o template e os 15 exemplos não trazem `<script src>` nem `<link>` de folha de estilo.

*(alterado em 2026-11-20: quinta rodada — ADV2-05, ADV2-20, ADV2-32 e medição no commit `4adaf9a`)*
- **O comparativo também abre,** pela P-04 emendada, só para a escuta de erro e a de recurso de fora. O título, as peças e a interface dele são da casca do archify: não há `<h1>` com o título do arquivo, e o subtítulo é "base → cabeça". Medido nos dois idiomas: `lang="en"`, nenhum card, nenhuma requisição para fora, nenhum erro.
- **A F-nn no desenho** (D29). Nos diagramas construídos, o conferidor sabe pelo modelo quais peças entregam feature. Conta a peça com nó próprio: a que só aparece como membro de um agrupamento é nomeada no card "Quem está em …", e a F-nn dela fica no diagrama em que ela tem nó (medido: o `app-resumo-sem` desenha 1 peça e 7 agrupamentos). No mapa de arquitetura, a F-nn é o `tag` do componente, que o archify desenha como detalhe fino: opacidade 0 no nível "read" do carregamento, 1 no nível "full". O conferidor clica no `[data-view="in"]` até o `.diagram-container` ter `data-detail-level="full"` e lê a opacidade computada. Na árvore de registro, cujo esquema não tem `tag`, a F-nn está no sublabel, com opacidade 1 no carregamento. O total de peças conferidas, somado, é maior que zero. Medido: 4 peças com F-nn no `app-acesso` (0 no carregamento e 1 no nível cheio) e 5 em cada árvore do `/app` (1 no carregamento), nos dois idiomas.
- *(alterado em 2026-11-20 — guardas do passo 8, CT-B01 vermelho classificado como cenário a corrigir pela D29 emendada)* **A F-nn abreviada no desenho, inteira no card.** O archify recusa o `tag` mais largo que a caixa, e uma peça entrega até 25 F-nn (a página de configurações do kit); a caixa larga o bastante para todas levaria a grade inteira à largura dela e derrubaria o texto dos mapas abaixo do piso de leitura da ADR-07. Por isso o desenho mostra até três F-nn e "+N", e o card "F-nn de cada peça", no painel lateral do mesmo diagrama, lista todas, à vista sem foco (D29 emendada; desvio registrado no `03`). O oráculo segue falsificável: o tag com a F-nn errada, fora da ordem do modelo ou com o N errado reprova, e a peça sem a linha inteira no card também. Medido no run do passo 8: 26 diagramas por idioma têm peça com mais de três F-nn.
- **A interface montada.** O SVG vem pronto no HTML, e por isso "desenhou zero peça" não pega o visualizador que não carregou. O que só existe com o script rodando é a busca: um resultado por peça desenhada. Medido com JavaScript no `app-acesso`, no `app-registro-sem`, no `app-resumo-sem`, no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`: 21 de 21, 52 de 52, 9 de 9, 39 de 39, 12 de 12 e 26 de 26; sem JavaScript, nenhum.
- **O título é o `<h1>`,** igual ao título do arquivo no idioma da página. Medido em todos os diagramas aceitos do commit `4adaf9a`, fora o comparativo. O `document.title` é a moldura do archify, "Diagrama {título}" em pt e "{title} Diagram" em en.
- **A pilha da requisição e os tipos novos** *(medido no commit `4cc9517`)*. O `requisicao-autenticada`, comum aos três painéis e aos dois modos, e o `requisicao-autenticada-com`, do `/app` com tenant, com a camada da organização, abrem com o `<h1>` do arquivo, os rótulos da fonte e a busca montada (8 de 8 e 5 de 5 resultados), sem erro nem recurso de fora; o mesmo nos três `lifecycle` (`conta`, `convite`, `sessao`) e nos três `class`.
- **No commit `4cc9517`, a base congelada:** o `<h1>` e o rótulo de cada peça iguais aos da fonte nos 84 HTML fora o comparativo, a busca com um resultado por peça em 42 de 42 diagramas, e nenhum erro, requisição para fora ou `<script src>` nos 86 HTML.
- **O rótulo é o da fonte, por igualdade,** e não "sem reticências": o esqueleto escreve "…" à mão em 33 rótulos, e o archify recusa no render o rótulo mais largo que a caixa. Medido: rótulo igual, pelo id, em todas as peças dos 9 diagramas aceitos do commit `4adaf9a` fora o comparativo, nos dois idiomas.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar o esperado pela fonte | ler `site/mapa/diagramas/` (N = quantidade de arquivos) e, nos construídos, o modelo; por diagrama, as peças com o rótulo de cada uma, como nos Pré-requisitos, e as peças que entregam feature, com as F-nn | N > 0 |
| 2 | abrir cada um, com as escutas ligadas | `pagina.on('console', …)` filtrando `type() === 'error'`, `pagina.on('pageerror', …)` e `pagina.on('request', …)`; depois `pagina.goto(\`http://localhost:${PORTA}${BASE}/mapa/${idioma}/${slug}.html\`)` e esperar o primeiro `svg [data-node-id]` e, fora o comparativo, o primeiro `.node-finder-result` | o diagrama aparece |
| 3 | ler as peças desenhadas | `pagina.evaluate(...)` sobre `svg [data-node-id]`: o par id e `data-node-label` de cada uma | toda peça do esperado, com o rótulo da fonte no idioma da página |
| 4 | conferir a marca da peça condicional *(alterado em 2026-11-23 — Adendo 2: destravado; estava estacionado na Q11, ADV-31)* | no carregamento, sem clicar em `[data-view="in"]`, sem busca e sem foco: para cada peça condicional do esperado com nó próprio, o texto da marca no rótulo secundário de `svg [data-node-id="{id}"]` e a opacidade computada dele — *(alterado em 2026-11-23 — Adendo 4)* no diagrama construído, `{id}` é o id archify da peça; no escrito à mão, é o id do nó do arquivo cuja peça é a do nome exato do rótulo ou a da prova que declara a classe (no `app-chat-de-ia`, o participante `widget`, rótulo "AssistenteChat", é o chat pela prova `class AssistenteChatWidget extends Component`) *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)*, pela D43 do `04`: a prova primeiro, mesmo com o trecho diferente do da evidência, e o nome só sem homônima — *(alterado em 2026-11-23 — step 11, Adendo 4: no escrito à mão, o nó não leva a marca, e o passo confere só a linha da peça, achada pelo nó do arquivo, no card "Condições de disponibilidade")*; para cada uma que é membro de agrupamento, o `textContent` do item `.reader-rail .card li` do card "Quem está em …" que a nomeia, à vista e com `scrollWidth <= clientWidth + 1` | cada peça condicional mostra a marca que a condição do modelo dá a ela, no idioma da página, com opacidade 1 no nó; o total conferido é maior que zero. *(alterado em 2026-11-23 — Adendo 3)* E, nos itens `.reader-rail .card li` do card "Condições de disponibilidade" / "Availability conditions", sem foco: uma linha por peça condicional desenhada, com a condição e a âncora do modelo, à vista e com `scrollWidth <= clientWidth + 1` |
| 5 | conferir a interface montada | o conjunto de `data-node-id` de `.node-finder-result` | igual ao conjunto de ids de `svg [data-node-id]` |
| 6 | conferir a F-nn | no mapa de arquitetura construído: clicar em `[data-view="in"]` até `.diagram-container[data-detail-level="full"]` e ler a opacidade computada do `[data-detail="fine"]` com as F-nn, dentro do nó de cada peça com nó próprio; na árvore construída, no carregamento, o sublabel do nó de cada peça; nos dois, os itens `.reader-rail .card li` | cada peça mostra as F-nn dela (todas, até três; as três primeiras e "+N", acima disso), com opacidade 1; o card "F-nn de cada peça" tem a linha inteira de cada uma, à vista e sem corte; o total conferido é maior que zero |
| 7 | conferir os recursos | as requisições escutadas e `document.querySelectorAll('script[src], link[rel="stylesheet"]')`, inclusive no comparativo | nenhuma requisição fora da origem da prévia (fora `data:` e `blob:`); nenhum dos dois elementos |
| 8 | ler o título | o texto do `<h1>`, fora o comparativo | o título do arquivo naquele idioma, com acento |
| 8a | conferir a raiz da árvore *(step 9, achado A14)* | na árvore de registro, o `data-node-label` de `svg [data-node-id="{id da peça painel:{painel do arquivo}}"]`, contra o nome dessa peça no modelo de peças | o rótulo que a fonte dá à raiz; o esperado nunca sai do HTML |
| 9 | totalizar | contador por idioma | N em pt e N em en |

**Assertions**: o oráculo é o par id e rótulo de cada peça, a busca montada, a F-nn no desenho, os recursos da página e o título. O console e a exceção são asserção de apoio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o conferidor do mapa lista os diagramas varrendo o `dist/` por `index.html`, como o de acessibilidade (`site/verifica-acessibilidade.mjs:'index.html':78`), e não acha nenhum HTML do mapa | CT-B01 | "o total de diagramas abertos é o número de arquivos vezes dois": dá zero e reprova; o mutante aceita |
| M2 | o gerador grava o HTML sem declarar UTF-8, e os acentos se trocam | CT-B01 | "o `<h1>` é o título do arquivo no idioma da página, com os acentos intactos": reprova; o mutante aceita |
| M3 | o HTML en sai com os textos pt | CT-B01 | idioma en, "o `<h1>` é o título do arquivo no idioma da página": reprova; o mutante aceita |
| M4 | o script do visualizador não entra no HTML, ou quebra antes de montar a interface: o SVG estático aparece, sem busca nem nível de leitura *(reescrito em 2026-11-20: medido no commit `4adaf9a`, o SVG vem pronto no HTML, e "dá zero peça" não matava o mutante)* | CT-B01 | "a busca tem um resultado por peça desenhada, pelo id": dá zero resultado e reprova; o mutante aceita |
| M5 *(alterado em 2026-11-23 — Adendo 2: destravado; estava estacionado na Q11)* | a marca chega à entrada do archify, mas o desenho a perde, ou a deixa só no detalhe fino que o nível de leitura do carregamento esconde | CT-B01 (passo 4) | "toda peça condicional que ele desenha mostra, à vista no carregamento, a marca da condição que o modelo dá a ela": a opacidade computada é 0, ou o texto não está no nó; reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-28) | o desenho tem o mesmo número de peças, com os rótulos trocados entre elas | CT-B01 | "toda peça que a fonte dá a ele está desenhada e tem, pelo id, o rótulo que a fonte dá a ela no idioma da página": reprova; o mutante aceita |
| M8 — step 9 (A14) | a raiz da árvore de registro sai com o rótulo de outro painel, ou sem o nome do painel, e o conferidor, que dá à raiz o esperado `null`, não a confere | CT-B01 | "em cada árvore de registro, a raiz … tem, pelo id, o rótulo que a fonte dá a ela": reprova; o mutante aceita |

(Numeração da R24 do `04`. O M5, a marca de condição, está estacionado na Q11; o M7 morre no CT-B03 e no CT-B04. *(alterado em 2026-11-23 — Adendo 2: o M5 voltou, e a ausência da marca na entrada do archify — antes do desenho — é o R42-M1, que morre no CT-57 do `04`)*)

Mutante da R21 que este CT-B também mata:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M5 — revisão adversarial (ADV-14) | o HTML do diagrama carrega a biblioteca por CDN, ou de um arquivo copiado para `site/public/` | CT-B01 | "nenhuma requisição sai da origem da prévia, e o documento não tem `<script src>` nem `<link rel="stylesheet">`": reprova; o mutante aceita |

(Numeração da R21 do `04`. Os M1, M2, M4, M6 e M7 dela morrem no CT-23; o M3 é lacuna declarada.)

Mutante da R44 que este CT-B também mata *(alterado em 2026-11-23 — Adendo 3)*:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M4 | o card "Condições de disponibilidade" só aparece com a peça em foco, ou sai cortado | CT-B01 (passo 4) | "o card 'Condições de disponibilidade' traz, à vista sem foco e inteira, uma linha por peça condicional que o diagrama desenha": reprova; o mutante aceita |

(Numeração da R44 do `04`. Os M1 a M3 morrem no CT-57 do `04`.)

Mutante da R6 que este CT-B também mata:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M10 — revisão adversarial (ADV2-05) | o modelo liga cada peça à F-nn dela, mas nenhum diagrama mostra a F-nn ao leitor | CT-B01 | "a F-nn de cada peça com nó próprio que entrega feature está no desenho dela: no `tag`, com opacidade 1 no nível de leitura cheio, ou no sublabel da árvore, à vista": reprova; o mutante aceita |

(Numeração da R6 do `04`. Os M1 a M7 e o M9 morrem na suíte do kit; o M8 é lacuna declarada.)

---

## CT-B02: cada ligação que o diagrama desenha traz a prova num item próprio de card do painel lateral, inteiro e à vista, e as arestas desenhadas são as da fonte

**Por que browser**: o card de prova está no HTML que o build gera, no painel lateral que o visualizador monta. Ele não existe na fonte versionada, e a prova por aresta não é recurso do archify.

```gherkin
# language: pt
  Cenário: [CT-B02] cada ligação que o diagrama desenha traz, num item próprio de card do painel lateral, o arquivo e o trecho da prova, inteiros e à vista, e as arestas desenhadas são as da fonte
    Dado o site construído pelo build do commit, servido pela prévia
    E o modelo de ligações, com a prova de cada uma, e os arquivos de diagrama
    Quando o conferidor abre cada diagrama publicado fora o comparativo, sem focar peça nenhuma
    Então cada ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros, uma por ligação — tem, num card do painel lateral, à vista, um item de prova que a nomeia, "origem → destino", com o arquivo e o trecho do modelo
    E cada item de prova mostra o trecho inteiro do modelo, sem reticências que o modelo não tenha, e não é cortado (`scrollWidth <= clientWidth + 1`)
    E o total de itens de prova lidos no DOM é o de ligações desenhadas, maior que zero em todo diagrama que desenha ligação
    E, em cada `architecture`, o conjunto de pares origem → destino das arestas desenhadas é o da fonte
    E, na árvore de registro, a origem de cada item de prova é o rótulo que a fonte dá à peça painel do arquivo, e não o que o HTML dá à raiz
```

*(alterado em 2026-11-21 — step 9, achado A14)* O passo novo tira do HTML o último esperado que saía dele: a origem dos itens de prova da árvore. Ela é o rótulo da raiz que o CT-B01 confere contra o modelo. Hoje o dado o reprova junto com o do CT-B01: os itens começam por "Painel /app" e "Panel /app", e o nome da peça no modelo é `/app`. Pela Q23 do `04`, decidida pela sessão, é vermelho de implementação: os itens passam a começar por `/app`.

*(reescrito em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)*
- **Onde fica o card.** Os cards ficam no painel lateral, sempre presentes, sem depender de foco. A prova de cada ligação fica num card do próprio diagrama, agrupado pela peça de origem; o agrupamento é desenho, e o oráculo é "num card do painel lateral, à vista".
- **O card é texto.** O archify escreve o item do card escapado, sem link. O link de evidência que o leitor clica é o do passaporte (CT-B03).
- **O número de linha.** Se o modelo guardar o número de linha (R13-M3), ele morre no CT-09 do `04`, na suíte do kit, que roda em todo PR (ADV-19).

*(alterado em 2026-11-20: quinta rodada — ADV2-17, ADV2-20, ADV2-22, ADV2-32 e medição no commit `4adaf9a`)*
- **O comparativo fica fora,** pela P-04 emendada: a casca do archify não leva cards. A `base` e a `cabeca` dele são diagramas do mapa, publicados ao lado com os cards.
- **O item de prova se reconhece pela forma** "origem → destino (verbo): arquivo:linha — trecho". O painel lateral do mapa construído tem outros cards — "Como ler as setas" e "Quem está em …" —, e eles ficam fora da conta.
- **A conta é por ligação, do lado do DOM.** O agrupamento desenha várias ligações numa aresta só, e cada uma tem o seu item. Medido: nos 6 diagramas construídos, 281 itens de prova por idioma, igual às ligações desenhadas (75 no `app-resumo-sem`, sobre 16 pares de arestas), todos inteiros, e nenhum diagrama repete nome de peça, que é o que o item nomeia.
- **O item inteiro.** O item de card do archify quebra a linha (`overflow-wrap: anywhere`): um item sintético de 160 caracteres sem espaço ficou com `scrollWidth` e `clientWidth` iguais, de 346 px, e nenhum dos 562 itens de prova dos construídos é cortado.
- **A árvore.** A árvore de registro construída dá um item de prova a cada registro do painel (medido nos commits `4adaf9a` e `4cc9517`: 46 no `app-registro-sem` e 48 no `app-registro-com`, todos achados). O `/app` tem uma árvore por modo; o `/admin` e o `/infra`, uma só cada, compartilhada pelos dois modos, porque o registro deles não muda (fato da implementação; medido no modelo do commit `4cc9517`: 57 e 84 peças, iguais nos dois modos). *(alterado em 2026-11-23 — Adendo 2: o desenho do `/admin` sem tenant não tem o `TenantResource` (RQ-28, RQ-29), e por isso não dá item de prova ao registro dele; a forma recomendada é uma árvore por modo, como no `/app` — a colisão a do `04`)* A árvore escrita à mão do esqueleto, o `infra-registro`, desenhava 38 relações pai → filho sem item de prova, com nós que não eram peças do modelo; o commit `4cc9517` a tirou, e a do `/infra` passa a sair do construtor.
- *(alterado em 2026-11-23 — Adendo 2, colisão b do `04`)* **A ligação cuja ponta não entra no modo.** A ligação do modelo vale nos modos em que o código a faz (o `modos` dela), mas só é desenhada num diagrama quando as duas pontas entram nele — e a peça condicional só entra nos diagramas do modo em que fica disponível. O esperado do conferidor aplica a mesma regra que o gerador: "o painel admin registra `TenantResource`" vale nos dois modos e só tem item de prova nos diagramas do `/admin` com tenant. Esperar o item no diagrama sem tenant reprovaria o desenho certo.
- **Os pares contra a fonte.** No diagrama escrito à mão, a fonte é a lista de ligações do arquivo; no construído, o modelo, como nos Pré-requisitos. Medido no commit `4adaf9a`, contra o modelo: `app-acesso`, 17 pares na fonte e 17 no DOM; `app-organizacoes`, 14 e 14; `app-resumo-sem` e `app-resumo-com`, 16 e 16. No esqueleto, contra o arquivo: 76 e 76, 14 e 14, 43 e 43.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar o esperado pela fonte | de cada arquivo de diagrama e do modelo, as ligações que ele desenha, pela definição da R12 do `04` (as de agrupamento e as de porta que nomeiam os membros, expandidas em uma por ligação; a porta que só nomeia o mapa não desenha a ligação), com o arquivo e o trecho de cada uma; e, em cada `architecture`, os pares de pontas desenhadas | lista de (diagrama, origem, destino, arquivo, trecho) e conjunto de pares |
| 2 | abrir cada diagrama, fora o comparativo | `pagina.goto(…)` e esperar o desenho, sem clicar em peça | o diagrama aparece |
| 3 | ler os itens de prova | `pagina.evaluate(...)` sobre `.reader-rail .card li`: o `textContent` de cada item com a forma de prova, se ele está à vista (caixa com altura, sem `display: none` nem `visibility: hidden`) e se `scrollWidth <= clientWidth + 1` | itens de prova, à vista e inteiros |
| 4 | conferir as provas | cada ligação esperada tem um item que começa por "origem → destino", traz o arquivo e termina com o trecho inteiro do modelo; na árvore de registro, a origem é o rótulo da raiz ("Painel /app"), que não está em fonte nenhuma do diagrama: o conferidor o lê do `data-node-label` do nó da raiz *(alterado em 2026-11-20 — guardas do passo 8, correção devolvida pelo executor de browser)*. *(alterado em 2026-11-21 — step 9, achado A14: a raiz é a peça painel do arquivo, que está no modelo; a origem esperada é o rótulo que a fonte dá a ela — o nome da peça no modelo, decidido na Q23 do `04` —, e o `data-node-label` lido do HTML deixa de ser o esperado)* | nenhuma faltando, nenhuma trocada, nenhuma cortada |
| 5 | totalizar | itens de prova lidos no DOM, por diagrama | igual às ligações desenhadas, maior que zero |
| 6 | conferir as arestas | em cada `architecture`, o conjunto de pares `data-edge-from` → `data-edge-to` de `svg [data-edge-from][data-edge-to]` | igual ao conjunto de pares da fonte |

**Assertions**: o oráculo é a igualdade com o modelo (arquivo, trecho), o item à vista e inteiro sem foco, a contagem do lado do DOM e os pares de arestas. Um "há um card" sozinho não serve.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o gerador perde a prova das ligações desenhadas por agrupamento ou por porta que nomeia os membros *(alterado em 2026-11-20: a porta entrou com os fatos de plataforma)* | CT-B02 | "uma por ligação": a ligação do membro sem prova reprova; o mutante aceita |
| M2 | o card traz só o arquivo, sem o trecho *(reescrito em 2026-11-20: era "link de prova para `blob/main`", que passou à R14 como M5, porque o card do archify é texto, sem link)* | CT-B02 | "um item de prova que a nomeia, … com o arquivo e o trecho do modelo": o item sem o trecho reprova; o mutante aceita |
| M4 | o card mostra a prova de outra ligação (índice trocado, ou provas trocadas entre os cards de duas origens) *(alterado em 2026-11-20: ADV2-22, a asserção antiga contava o conjunto de provas em qualquer card)* | CT-B02 | "um item de prova que a nomeia, 'origem → destino', com o arquivo e o trecho do modelo": o item que nomeia a ligação traz a prova de outra, e reprova; o mutante aceita |
| M5 | a prova só fica num documento à parte, nunca no diagrama | CT-B02 | "o total de itens de prova lidos no DOM é o de ligações desenhadas, maior que zero em todo diagrama que desenha ligação": dá zero e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-25) | o card da prova fica fora do painel lateral: oculto, ou só aparece quando o leitor foca a peça | CT-B02 | "num card do painel lateral, à vista", com o diagrama aberto sem focar peça nenhuma: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-22) | o conferidor conta as provas do lado esperado, e a ligação de membro sem item próprio passa, porque outra ligação desenhada tem a mesma prova | CT-B02 | "o total de itens de prova lidos no DOM é o de ligações desenhadas": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-32) | o item de card corta o trecho longo, com reticências que o modelo não tem | CT-B02 | "o item mostra o trecho inteiro do modelo, e não é cortado (`scrollWidth <= clientWidth + 1`)": reprova; o mutante aceita |
| M9 — step 9 (A14) | o item de prova da árvore de registro nomeia a origem com um rótulo que não é o da peça painel no modelo, e o conferidor, que monta o prefixo esperado com o rótulo que lê da raiz no próprio HTML, aceita | CT-B02 | "na árvore de registro, a origem de cada item de prova é o rótulo que a fonte dá à peça painel do arquivo": reprova; o mutante aceita |

(Numeração da R13 do `04`. O M3 morre no CT-09 do `04`.)

Mutante da R12 que este CT-B também mata:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 — revisão adversarial (ADV2-17) | na conversão para o esquema do archify, o gerador perde as arestas, e o diagrama desenha só os nós | CT-B02 | "em cada `architecture`, o conjunto de pares origem → destino das arestas desenhadas é o da fonte": reprova; o mutante aceita |

(Numeração da R12 do `04`. Os M1 a M6 morrem no CT-14.)

---

## CT-B03: o leitor acha cada peça da amostra pela busca, e o passaporte dela mostra as provas da própria peça, com o link para a linha do trecho no repositório do kit, no commit do build

**Por que browser**: a busca e o passaporte existem só com o JavaScript do visualizador rodando. É a interação que a RQ-07 pede.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B03] o leitor acha cada peça da amostra pela busca, e o passaporte dela mostra as provas da própria peça, com o link para a linha do trecho no repositório do kit, no commit do build, sem erro na interação
    Dado a amostra dos diagramas `architecture` publicados, aberta em "<idioma>": em cada um, a primeira peça de pacote que ele desenha, quando houver, e a primeira peça do kit, quando houver; e, no conjunto, ao menos uma peça de pacote e ao menos uma do kit
    E o repositório do kit, o `support.source` do `composer.json`
    Quando o leitor busca cada peça da amostra pelo nome e a foca
    Então o passaporte aparece, com o rótulo da peça
    E diz a origem verificada no repositório do kit, no commit do build
    E mostra de uma a três provas da própria peça, cada uma com o link para o repositório do `support.source`, no commit do build, para um arquivo que existe nesse commit
    E a linha do `#L` de cada link contém, nesse commit, o trecho da âncora da prova
    E, com a busca aberta e o passaporte aberto, nenhuma exceção não capturada e nenhum erro de console são registrados

    Exemplos:
      | idioma |
      | pt     |
      | en     |
```

*(reescrito em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)* O passaporte do archify mostra estas coisas:
- o rótulo e o tipo da peça;
- "origem verificada {repositório} @ {commit}";
- as provas da própria peça (`sources`, até três), como links;
- só as contagens das ligações.

Ele não lista cada ligação com a prova dela: essa prova está no card (CT-B02). Por isso o caso deixou de pedir a prova da ligação no detalhe. O oráculo do link é o da P-07: o repositório do `support.source`, o commit do build e um arquivo que existe nesse commit.

O diagrama e as peças saem dos arquivos de diagrama e do modelo, na ordem em que estão, e não de uma lista escrita no teste. O tipo é `architecture` porque a peça dele é peça do modelo, com arquivo no kit; o marco da linha do tempo, por exemplo, não tem prova de código. O comparativo fica fora: a casca dele não tem passaporte (P-04 emendada).

*(alterado em 2026-11-20: quinta rodada — ADV2-23, ADV2-24 e medição no commit `4adaf9a`)*
- **A amostra.** A peça de pacote é a que o modelo dá como de pacote (no desenho, o tipo `cloud`, "Pacote ou plugin de terceiros" na legenda). Exigir uma em cada `architecture` reprovaria dado correto: no commit `4adaf9a`, o `app-acesso` tem 5 peças de pacote em 21, e o `app-organizacoes`, o `app-resumo-sem` e o `app-resumo-com`, nenhuma (no esqueleto, o `app-resumo-sem` e o `app-resumo-com` também não tinham).
- **A linha.** A prova de peça é âncora `{arquivo, trecho}`, e o gerador acha a linha na revisão publicada. Medido: no esqueleto (`ae5d20f`), 226 links de prova de peça, e a linha de todos contém o trecho; no commit `4adaf9a`, todos também — 21, 9, 49, 47, 1 e 1 nos construídos, e 73, 31 e 26 no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`.
- **Sem erro na interação.** Medido no commit `4adaf9a`: a peça de pacote `AuthDesignerPlugin`, achada pela busca no `app-acesso`, abre o passaporte com "origem verificada gsferro/filament-starter-kit-easy @ 4adaf9a" e um link para `app/Providers/Filament/AdminPanelProvider.php#L200` no commit; nenhum erro, nos dois idiomas.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar a amostra | em cada arquivo de diagrama `architecture`, na ordem, a primeira peça de pacote (origem `pacote` no modelo) e a primeira do kit (origem `proprio` ou `pacote+proprio`), quando houver, pelo modelo (não uma caixa de agrupamento nem de porta); no conjunto, ao menos uma de cada. *(alterado em 2026-11-20 — guardas do passo 8, correção devolvida pelo executor de browser)*: cinco `architecture` não têm peça do kit — as três pilhas da requisição desenham só middlewares de pacote, e os dois resumos do kit, só agrupamentos e portas | peças por diagrama, e uma de cada classe no conjunto |
| 2 | abrir o diagrama, com as escutas ligadas | `pagina.on('console', …)` e `pagina.on('pageerror', …)`; `pagina.goto(\`…/mapa/${idioma}/${slug}.html\`)` e esperar o desenho | o diagrama aparece |
| 3 | buscar cada peça | `pagina.keyboard.press('/')`, digitar o nome da peça em `input.node-finder-input` e confirmar | a peça fica em foco |
| 4 | ler o passaporte | `pagina.evaluate(...)` sobre `.semantic-passport-detail` e o pai dele | o rótulo da peça; a origem verificada com o repositório e o commit |
| 5 | conferir os links | do `href` de cada `a[href*="/blob/"]`, extrair dono, repositório, commit, caminho e linha; conferir o arquivo no commit (`git show {commit}:{caminho}`) e a linha | dono e repositório iguais aos do `support.source`; commit igual a `git rev-parse HEAD`; o arquivo existe; de 1 a 3 links; a linha contém o trecho da âncora |
| 6 | conferir as escutas | o que o console e o `pageerror` registraram com a busca e o passaporte abertos | nenhum erro, nenhuma exceção |

**Assertions**: o oráculo é o passaporte de cada peça da amostra: o rótulo, a origem verificada, os links e a linha. O foco da peça sozinho não basta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama é exportado como imagem estática, sem busca nem passaporte | CT-B03 | "o leitor busca a peça e a foca": não há busca e reprova; o mutante aceita |
| M2 | o nó sai sem provas, e o passaporte não mostra a origem verificada *(reescrito em 2026-11-20: era "o detalhe não traz a prova da ligação", que o passaporte do archify não mostra por desenho; ADV2-23 estendeu a amostra)* | CT-B03 | "cada peça da amostra mostra de uma a três provas": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-25) | o link do passaporte leva a outro repositório, como um fork ou um nome errado, com o mesmo commit e caminho *(reescrito em 2026-11-20: era "o detalhe do agrupamento não nomeia os membros", que passou à R12 como M5)* | CT-B03 | "leva ao repositório do `support.source`": reprova; o mutante aceita |
| M4 | a prova da peça de pacote aponta para `vendor/`, fora do commit, e o link dá 404 | CT-B03 | "a um arquivo que existe nesse commit": reprova; o mutante aceita |
| M5 | link de evidência para `blob/main`, um ramo que anda *(veio da R13, onde era o M2)* | CT-B03 | "no commit do build": o `href` não traz o commit e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV2-23) | a linha do `#L` sai fixa em 1, ou envelhece, e o link leva a outra linha | CT-B03 | "a linha do `#L` contém o trecho da âncora, no commit do build": reprova; o mutante aceita |

Mutante da R24 que este CT-B também mata:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 — revisão adversarial (ADV-29) | o script do visualizador lança exceção não capturada na busca ou no detalhe, e o conferidor só escuta o console *(alterado em 2026-11-20: ADV2-24, o matador passou do CT-B01, que não abre a busca, ao CT-B03 e ao CT-B04)* | CT-B03, CT-B04 | "com a busca aberta e o passaporte aberto, nenhuma exceção não capturada e nenhum erro de console": reprova; o mutante aceita |

(Numeração da R24 do `04`. O CT-B04 também o mata.)

---

## CT-B04: a interface do visualizador fala o idioma da página, com critério em pt e em en, também com a busca e o passaporte abertos, e só a casca do comparativo fica em inglês

**Por que browser**: a interface do visualizador (botões, busca, legenda, passaporte, aviso de idioma reserva) é montada pelo JavaScript do archify a partir do `meta.locale` e das traduções. Só o documento desenhado mostra o que o leitor lê.

```gherkin
# language: pt
  Cenário: [CT-B04] a interface do visualizador fala o idioma da página, com critério em pt e em en, também com a busca e o passaporte abertos, e só a casca do comparativo fica em inglês
    Dado o site construído pelo build do commit, servido pela prévia
    E o catálogo de interface en do archify do commit fixado, com 486 chaves, e o texto pt de cada chave: o embutido no HTML pt, nas 390 chaves de interface, e o da fonte do kit, nas 96 que o archify aplica no render
    Quando o conferidor abre cada diagrama publicado, em pt e em en
    Então, na versão pt de todo diagrama fora o comparativo, nenhum texto de interface é igual ao en de uma chave cujo texto pt é outro, nenhum aviso de idioma reserva aparece, e o `lang` do documento começa por `pt`
    E, na versão pt de todo diagrama fora o comparativo, com a busca aberta e o passaporte de uma peça aberto, nenhum texto visível é igual ao en de uma chave cujo texto pt é outro, e nenhuma exceção não capturada e nenhum erro de console são registrados
    E, na versão pt de todo mapa de arquitetura, a legenda traz o nome pt de cada tipo de peça desenhado, como pedaço do rótulo
    E, na versão pt do comparativo, o subtítulo da casca traz os títulos pt da base e da cabeça, e só a casca do archify fica em inglês
    E, na versão en, nenhum texto de interface é igual ao pt de uma chave cujo texto en é outro, a legenda de todo mapa de arquitetura traz o nome en de cada tipo de peça desenhado, e o `lang` do documento começa por `en`
    E, na versão pt de todo diagrama de classes, com o passaporte aberto num nó de cada tipo que o arquivo desenha (`class`, `abstract`, `interface`), o tipo que o passaporte mostra é o nome pt que o desenho dá ao nó, e nunca o identificador cru do tipo
```

*(alterado em 2026-11-21 — step 9, achado A16)* **O tipo do nó no passaporte pt.** O archify do commit fixado não tem `viewer.kind.class` nem `viewer.kind.abstract`, e o kit as acrescenta ao catálogo pt embutido depois do render ("Classe" e "Classe abstrata"). O critério acima — nenhum texto igual ao en de uma chave cujo pt é outro — só pegava `Class` por coincidência, e `Abstract` não é texto de chave nenhuma do en: sem as duas chaves, o passaporte pt mostraria o tipo cru, e o caso ficava verde. O passo novo compara o tipo do passaporte com o contexto que o desenho dá ao mesmo nó (`data-node-context`, que o archify traduz no render, pelo catálogo pt que o kit entrega). Medido no commit `168d1827`, no `classes-heranca-e-contratos` gerado numa pasta de rascunho: o desenho dá "Interface", "Classe" e "Classe abstrata" em pt, e o passaporte mostra os mesmos três; o passo passa hoje. A amostra é o primeiro nó de cada `kind` dos `types` do arquivo, porque o `abstract` aparece uma vez só; o `interface` não discrimina — é a mesma palavra nos dois idiomas. Em en, o passaporte mostra `class` e `abstract` crus, que são inglês: o passo vale só para o pt.

O `lang` do comparativo pt não é afirmado. A P-11 isenta a casca dele e não decide o idioma do documento (medido: `en` nos dois idiomas).

*(alterado em 2026-11-20: ADV-30 e fatos de plataforma)*
- **O `lang`.** O `lang` do documento segue o `meta.locale` (medido: `pt-BR`).
- **A busca e o passaporte.** Eles montam os textos na interação, e por isso o caso lê a interface também com os dois abertos.
- **O filtro.** Ele isenta só a chave cujo texto pt é igual ao en. Depois do CT-B07, que reprova a chave copiada fora da lista fechada, essa isenção vale só para as chaves da lista.

*(alterado em 2026-11-20: quinta rodada — ADV2-24, ADV2-27 e medição no commit `4adaf9a`)*
- **O texto pt de cada chave.** O archify embute no HTML só as 390 chaves de interface e aplica as outras 96 no render (o título da página, a descrição, o contexto do nó, as legendas, os eixos de `timeline` e `waterfall`). O texto pt das 96 vem da fonte do kit, como no CT-B07. Medido no `app-acesso`, no `app-registro-sem`, no `marcos-do-changelog` e no `kit-install-ci`: nenhuma das 486 chaves sem texto pt, e nenhum dos textos lidos — inclusive o `document.title`, os `aria-label`, os `title`, o texto de exemplo da busca e os rótulos da legenda — igual ao en de uma chave cujo pt é outro. Em en, no `app-acesso`, no `app-organizacoes` e no `app-registro-sem`, nenhum texto igual ao pt de uma chave cujo en é outro, entre as 390 embutidas.
- **A legenda.** Só o mapa de arquitetura tem legenda de tipo de peça: medido, a árvore, a `timeline`, o `waterfall`, o `erd` e o `sequence` não têm, e a do `workflow` e a do `dataflow` falam de passos e de dados. Os nomes pt são os da RQ-24 — painel, recurso, página, widget, plugin, provider, middleware, serviço e model — e, fora dela, policy, comando, Livewire e settings; os en, panel, resource, page, widget, plugin, provider, middleware, service, model, policy, command, Livewire e setting. "Plugin de pacote" e "serviço próprio", por extenso, reprovariam dado correto. Medido no `app-acesso` pt: "Tela (painel, página, recurso, widget, Livewire)", "Código do kit (provider, serviço, comando)", "Dados (model, settings)", "Pacote ou plugin de terceiros" e "Acesso (middleware, policy)"; em en, "Screen (panel, page, resource, widget, Livewire)", "Kit code (provider, service, command)", "Data (model, settings)", "Third-party package or plugin" e "Access (middleware, policy)".
- **O texto do kit no comparativo** é o subtítulo "base → cabeça". Medido: em pt, "/app — resumo por domínio, sem tenant → /app — resumo por domínio, com tenant ativo"; em en, "/app — summary by domain, tenancy off → /app — summary by domain, tenancy on".

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar os catálogos | o `locales/en.json` do archify baixado pelo build; o pt de cada chave: o `archify-i18n-data` do HTML pt e, nas 96 de render, a fonte do kit | as 486 chaves, com o texto en e o pt de cada uma |
| 2 | abrir cada diagrama pt, com as escutas ligadas | `pagina.goto(\`…/mapa/pt/${slug}.html\`)` e esperar o desenho | o diagrama aparece |
| 3 | ler o idioma e a interface | `document.documentElement.lang`; os textos visíveis fora do desenho das peças e dos cards, e os atributos `aria-label`, `title`, `placeholder` e `data-legend-label`; a presença do aviso de idioma reserva | `lang` começa por `pt`; nenhum texto igual ao en de uma chave cujo texto pt é outro; nenhum aviso |
| 4 | ler a legenda dos tipos de peça | no mapa de arquitetura, `data-legend-label` de `svg g[data-legend-kind]`; os tipos desenhados, pelo modelo | o nome pt de cada tipo desenhado, como pedaço de um rótulo |
| 5 | abrir a busca e o passaporte | `/` e o foco da primeira peça, como no CT-B03 | os textos visíveis da busca e do passaporte: nenhum igual ao en de uma chave cujo texto pt é outro; nenhum aviso; nenhum erro nem exceção |
| 6 | repetir em en | `pagina.goto(\`…/mapa/en/${slug}.html\`)` | `lang` começa por `en`; nenhum texto igual ao pt de uma chave cujo texto en é outro; a legenda com os nomes en |
| 7 | separar a exceção | o comparativo pt | o subtítulo `p.subtitle` com os títulos pt da base e da cabeça; a casca isenta, inclusive o `lang` |
| 8 | conferir o tipo do nó no passaporte pt *(step 9, achado A16)* | em cada diagrama de classes pt, o primeiro nó de cada `kind` dos `types` do arquivo: o `data-node-context` do nó no desenho e, com o passaporte dele aberto, o tipo que `#focus-kind` mostra *(alterado em 2026-11-21 — step 9, correção devolvida pelo executor de browser: o tipo fica em `#focus-kind`)* | os dois iguais, sem diferença de caixa, e nenhum igual ao `kind` cru quando o nome pt é outro; e o tipo do passaporte é o nome pt do tipo no catálogo do kit — a chave `viewer.kind.{kind}` de `foraDoCatalogo` do `site/mapa/interface.json`, ou a do catálogo pt do archify, ou `node.context.class.{kind}` —, tirado da fonte e não do HTML *(alterado em 2026-11-21 — step 9, lacuna devolvida pelo executor de browser: comparar só o desenho com o passaporte deixava passar os dois caindo juntos no kind cru)* |

**Assertions**: o oráculo é o texto da interface, a legenda e o `lang` lidos no documento desenhado, no carregamento e com a busca e o passaporte abertos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | uma chave da interface do archify sem tradução pt cai no inglês, com o aviso de idioma reserva | CT-B04 | "nenhum texto de interface é igual ao en de uma chave cujo texto pt é outro, nenhum aviso de idioma reserva aparece": reprova; o mutante aceita |
| M2 | o documento pt se declara `lang="en"` (o idioma não chega ao HTML) | CT-B04 | "o `lang` do documento começa por `pt`": reprova; o mutante aceita |
| M3 | a legenda dos tipos de peça fica com os rótulos do archify em inglês | CT-B04 | "a legenda traz o nome pt de cada tipo de peça desenhado, como pedaço do rótulo": reprova; o mutante aceita |
| M4 | a exceção do comparativo vale para todo diagrama, e a interface inteira fica em inglês sem reprovar | CT-B04 | "na versão pt de todo diagrama fora o comparativo, nenhum texto de interface é igual ao en de uma chave cujo texto pt é outro": reprova; o mutante aceita |
| M5 | a versão en recebe a tradução pt (catálogo de tradução trocado de idioma) *(alterado em 2026-11-20: ADV2-27, a asserção antiga não tinha critério)* | CT-B04 | versão en, "nenhum texto de interface igual ao pt de uma chave cujo en é outro, e a legenda com os nomes en dos tipos": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-30) | a busca e o passaporte montam os textos na interação, e uma chave deles cai no inglês | CT-B04 | "com a busca aberta e o passaporte de uma peça aberto, nenhum texto visível é igual ao en de uma chave cujo texto pt é outro": reprova; o mutante aceita |
| M11 — step 9 (A16) | o catálogo pt perde as chaves que o kit acrescenta fora do catálogo do archify (`viewer.kind.class`, `viewer.kind.abstract`), e o passaporte pt mostra o tipo do nó cru, em inglês | CT-B04 | "na versão pt de todo diagrama de classes, o tipo que o passaporte mostra é o nome pt que o desenho dá ao nó": o passaporte diz `class` e o desenho, "Classe", e reprova; o mutante aceita |

(Numeração da R31 do `04`. O M7, o M8, o M9 e o M10 estão no CT-B07.)

Mutante da R24 que este CT-B também mata:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 — revisão adversarial (ADV-29) | o script do visualizador lança exceção não capturada na busca ou no detalhe, e o conferidor só escuta o console *(alterado em 2026-11-20: ADV2-24, o matador passou do CT-B01, que não abre a busca, ao CT-B03 e ao CT-B04)* | CT-B03, CT-B04 | "com a busca aberta e o passaporte aberto, nenhuma exceção não capturada e nenhum erro de console": reprova; o mutante aceita |

(Numeração da R24 do `04`. O CT-B03 também o mata.)

---

## CT-B05: a amostra de acessibilidade cobre cada tipo, idioma e tema, e o comparativo no escuro, sem violação fora das duas toleradas

**Por que browser**: contraste e estrutura acessível só existem no documento desenhado e no tema aplicado. É a conferência que a P-11 pede, a mesma das páginas do site, por amostra.

```gherkin
# language: pt
  Cenário: [CT-B05] a amostra de acessibilidade cobre cada tipo publicado em cada idioma e tema, e o comparativo no escuro, sem violação fora das duas toleradas
    Dado o site construído, servido pela prévia, e os arquivos de diagrama do mapa
    Quando o conferidor audita com o axe a amostra: um diagrama de cada tipo publicado, em cada idioma e em cada tema, e o comparativo no tema escuro
    Então a amostra tem exatamente uma entrada por tipo publicado, idioma e tema, mais o comparativo no tema escuro, e nenhuma é vazia
    E cada diagrama auditado estava no tema pedido, aplicado pelo mecanismo do próprio visualizador e conferido antes do axe
    E nenhuma violação serious ou critical aparece, fora as duas toleradas
    E nenhum `console.error` e nenhuma exceção não capturada são registrados
```

*(alterado em 2026-11-20: ADV-29)* A escuta pega também a exceção não capturada. O mutante dela está na R24, como M7, e o teto de seis da R32 fica.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar a amostra pela fonte | dos arquivos de diagrama, os tipos publicados; para cada tipo, o primeiro diagrama dele; e o primeiro comparativo | tipo × idioma × tema, mais o comparativo no escuro |
| 2 | abrir e aplicar o tema | `pagina.goto(…)`, aplicar o tema pelo visualizador e esperar o tema aplicado (estado, nunca segundos) | o tema pedido, conferido |
| 3 | rodar o axe | `axe.run` com as tags `wcag2a`, `wcag2aa`, `wcag21a` e `wcag21aa`, como o conferidor herdado | as violações |
| 4 | decidir | a mesma função de decisão do CT-B06 | só as duas toleradas passam |
| 5 | escutar o console e as exceções | `pagina.on('console', …)`, filtrando `type() === 'error'`, e `pagina.on('pageerror', …)` | nenhum erro, nenhuma exceção |
| 6 | conferir a composição | contagem por tipo, idioma e tema | igual à da fonte, e maior que zero |

**Assertions**: o oráculo é a composição da amostra contra a fonte e a lista de violações decidida.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M4 | amostra vazia: o conferidor não acha diagrama e não audita nada | CT-B05 | "a amostra tem exatamente uma entrada por tipo publicado, idioma e tema, e nenhuma é vazia": reprova; o mutante aceita |
| M5 | um tipo publicado fica fora da amostra | CT-B05 | idem, pelo tipo que falta: reprova; o mutante aceita |
| M6 | o comparativo não é auditado | CT-B05 | "mais o comparativo no tema escuro": reprova; o mutante aceita |

(Numeração da R32 do `04`. O M1, o M2, o M3 e o M7 estão no CT-B06.)

---

## CT-B06: a tolerância reconhece os dois defeitos do archify pela regra e pelo elemento, alvo por alvo, reprova qualquer outra violação, e a reprovação vira código de saída

**Por que browser**: a decisão depende do elemento que o axe aponta, resolvido no DOM, e não do texto do seletor. Os controles são páginas sintéticas abertas no navegador, decididas pela mesma função que decide a amostra real, no molde dos controles de `site/verifica-acessibilidade.mjs`. O controle de processo roda o próprio conferidor.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B06] a tolerância reconhece os dois defeitos do archify pela regra e pelo elemento, alvo por alvo, reprova qualquer outra violação, e a reprovação vira código de saída
    Dado o controle "<controle>"
    Quando o conferidor o decide pelo mesmo caminho que decide a amostra real
    Então o resultado é "<resultado>"

    Exemplos:
      | controle                                                                                            | resultado                                        | # partição                   |
      | página com `nested-interactive` no `<svg>` raiz do diagrama                                         | tolera                                           | defeito tolerado 1           |
      | página com `color-contrast` em `.node-outline-heading > small`                                      | tolera                                           | defeito tolerado 2           |
      | página com os dois defeitos tolerados juntos, nos elementos certos                                  | tolera                                           | os dois                      |
      | página com `nested-interactive` num `<svg>` que não é o raiz do diagrama                            | reprova                                          | regra certa, elemento errado |
      | página com `color-contrast` num `<small>` fora de `.node-outline-heading`                           | reprova                                          | regra certa, elemento errado |
      | página com `color-contrast` num parágrafo do corpo                                                  | reprova                                          | regra sem o elemento         |
      | página com `button-name` (serious) num botão sem nome                                               | reprova                                          | terceira violação            |
      | página com um `color-contrast` de dois alvos: `.node-outline-heading > small` e um parágrafo do corpo | reprova pelo parágrafo                         | alvos mistos                 |
      | o conferidor rodado como processo sobre uma prévia em que o HTML de um diagrama não desenha nada    | reprova, e o processo termina com código diferente de zero | controle de processo |
      | o caminho com que o conferidor se relançaria, derivado de uma URL de módulo dele numa pasta "meus projetos/João" (`file:///…/meus%20projetos/Jo%C3%A3o/verifica-mapa.mjs`), pela mesma função que o controle de processo usa | o caminho do arquivo no disco, `…/meus projetos/João/verifica-mapa.mjs`, sem escape de URL | controle de caminho |
```

*(alterado em 2026-11-21 — step 9, achado A12)* **O controle de caminho.** O controle de processo relança o conferidor por `new URL(import.meta.url).pathname` (`site/verifica-mapa.mjs`, `conferirControleDeProcesso`, no commit `168d1827`), que guarda o escape da URL: num clone com espaço ou acento no caminho, o filho não acha o arquivo, morre, e o pai reprova o CT-B06 porque o log não nomeia o diagrama — vermelho falso. Medido no commit `168d1827`: um módulo em `…/meus projetos/João/`, com a mesma expressão, deriva `…/meus%20projetos/Jo%C3%A3o/…`, que não existe no disco; o `fileURLToPath` deriva o certo. A linha nova confere a derivação pela mesma função que relança, sem precisar que o CI rode num caminho com espaço e acento; uma junção não serve, porque o Node resolve o link na URL do módulo. *(alterado em 2026-11-21 — step 9: Q25 decidida pela sessão)* Fica o controle sobre a derivação do caminho, pela mesma função; sem prova de ponta a ponta, que rodaria o conferidor duas vezes no job `site`.

*(alterado em 2026-11-20: quinta rodada — ADV2-16, ADV2-19 e medição no commit `4adaf9a`)*
- **Alvo por alvo.** A tolerância decide por alvo, e não pela violação inteira: a violação com alvos mistos reprova pelo alvo que não é tolerado. O `site/verifica-mapa.mjs` de hoje já filtra os alvos da violação (`v.nodes.filter`).
- **O controle de processo.** O CT-42 do `04` lê o YAML; que a reprovação vira código de saída, só o processo prova. Medido: o `site/verifica-mapa.mjs` do commit `4adaf9a`, rodado numa pasta de rascunho sobre uma cópia em que o HTML do `app-resumo-sem` não desenha nada, saiu com código 1 ("nenhum `<svg role="img">` em 15000 ms", nas quatro entradas da amostra); sobre a cópia com o HTML gerado, saiu com 0. O controle roda o conferidor como processo filho, com a pasta de diagramas e a prévia de controle, e lê o código de saída.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar cada página de controle | `pagina.setContent(…)` com a marcação mínima que produz a violação da linha | a página de controle |
| 2 | rodar o axe | o mesmo `axe.run` da amostra | a violação da linha |
| 3 | decidir | a função de decisão do CT-B05, que resolve cada alvo do axe para o **elemento** e compara com o `<svg>` raiz e com `.node-outline-heading > small` | tolera ou reprova, como na linha |
| 4 | rodar o controle de processo | o conferidor como processo filho (`node verifica-mapa.mjs {porta}`), com uma pasta de diagramas de um arquivo e uma prévia de controle em que o HTML dele não desenha nada, passadas por ambiente: `VERIFICA_MAPA_DIAGRAMAS`, `VERIFICA_MAPA_DIST`, `VERIFICA_MAPA_TIMEOUT_MS` e `VERIFICA_MAPA_CONTROLE` *(alterado em 2026-11-20 — guardas do passo 8, correção devolvida pelo executor de browser)* | código de saída diferente de zero, e o log nomeia o diagrama |
| 5 | rodar o controle de caminho *(step 9, achado A12)* | a função com que o controle de processo deriva o caminho de relançamento, aplicada à URL `file:///…/meus%20projetos/Jo%C3%A3o/verifica-mapa.mjs` | `…/meus projetos/João/verifica-mapa.mjs`, sem escape de URL |

**Assertions**: o oráculo é a decisão de cada linha. A amostra real só vale se todos os controles decidirem certo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a tolerância casa pelo texto do seletor do axe: qualquer alvo cujo seletor contém `svg` ou `small` passa | CT-B06 | linhas "`nested-interactive` num `<svg>` que não é o raiz" e "`color-contrast` num `<small>` fora de `.node-outline-heading`": reprova; o mutante tolera |
| M2 | a tolerância casa pela regra sem o elemento: todo `color-contrast` passa | CT-B06 | linha "`color-contrast` num parágrafo do corpo": reprova; o mutante tolera |
| M3 | uma terceira violação entra na tolerância | CT-B06 | linha "`button-name` num botão sem nome": reprova; o mutante tolera |
| M7 — revisão adversarial (ADV2-19) | a função de decisão tolera a violação inteira quando algum alvo, ou o primeiro, é tolerado | CT-B06 | linha "`color-contrast` de dois alvos: `.node-outline-heading > small` e um parágrafo do corpo": reprova pelo parágrafo; o mutante tolera |

(Numeração da R32 do `04`. O M4, o M5 e o M6 estão no CT-B05.)

Mutante da R23 que este CT-B também mata:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 — revisão adversarial (ADV2-16) | o conferidor registra a reprovação no log e termina com código 0, e o job passa | CT-B06 | linha do controle de processo: o conferidor, sobre um HTML do mapa que não desenha peça nenhuma, termina com código diferente de zero; o mutante termina com 0 |
| M8 — step 9 (A12) | o conferidor se relança pelo caminho da URL do módulo com o escape dela, e, num clone com espaço ou acento no caminho, o controle de processo reprova porque o filho não acha o arquivo — vermelho falso | CT-B06 | linha do controle de caminho: o caminho derivado é o do disco, sem escape; o mutante devolve `meus%20projetos/Jo%C3%A3o` e reprova |

(Numeração da R23 do `04`. Os M1 a M4 morrem no CT-25; o M5 e o M7, no CT-42.)

---

## CT-B07: o catálogo pt que o kit entrega ao visualizador não copia o texto en, fora a lista fechada de textos iguais, com o motivo

**Por que no conferidor**: o catálogo en (`locales/en.json` do archify) só existe depois do build, que baixa o archify no commit fixado. A comparação não precisa do navegador, mas precisa do site construído, e por isso mora no conferidor do mapa. As cópias são em memória, decididas pela mesma função que decide o catálogo real.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B07] o catálogo pt que o kit entrega ao visualizador não copia o texto en, fora a lista fechada de textos iguais nos dois idiomas, com o motivo
    Dado o catálogo en embutido no archify do commit fixado, `locales/en.json`, com 486 chaves
    E o catálogo pt que o kit entrega ao visualizador "<mundo>": as 390 chaves de interface embutidas no HTML pt e as 96 que o archify aplica no render, lidas na fonte do kit
    Quando o conferidor compara os dois, chave a chave, pela mesma função que decide o catálogo real
    Então o resultado é "<resultado>"

    Exemplos:
      | mundo                                                                              | resultado                                                                                                                                                                                                 | # partição                     |
      | o de cada HTML pt publicado, fora o comparativo                                    | aceita: o pt tem as 486 chaves do en; toda chave cujo texto pt é igual ao en está na lista fechada do conferidor, com o motivo; e a lista só tem chaves entre as 14 medidas e chaves só de marcadores | válida                         |
      | cópia em que `viewer.finder.placeholder`, a da busca, traz o texto en              | reprova, nomeando a chave                                                                                                                                                                                 | texto en copiado               |
      | cópia sem a chave `viewer.passport.evidence`                                       | reprova, nomeando a chave                                                                                                                                                                                 | chave ausente                  |
      | cópia da lista com `viewer.guide.facts`, só de marcadores, sem o motivo            | reprova, nomeando a chave                                                                                                                                                                                 | motivo ausente                 |
      | cópia da lista com `viewer.guide.facts`, com o motivo "—"                          | reprova, nomeando a chave                                                                                                                                                                                 | motivo de enfeite              |
      | cópia com o texto en em `viewer.finder.placeholder` e a chave na lista, com motivo | reprova, nomeando a chave                                                                                                                                                                                 | fora das 14 e com palavras     |
      | cópia da lista com `viewer.guide.facts`, com o motivo "N/A"                        | reprova, nomeando a chave                                                                                                                                                                                 | motivo de enfeite (tabela do Setup Global do `04`) |
      | cópia da lista com `viewer.guide.facts`, com o motivo "not applicable"             | reprova, nomeando a chave                                                                                                                                                                                 | motivo de enfeite (tabela do Setup Global do `04`) |
      | cópia da lista com `viewer.guide.facts`, com o motivo "Does not apply"             | reprova, nomeando a chave                                                                                                                                                                                 | motivo de enfeite (tabela do Setup Global do `04`) |
      | cópia da lista com `viewer.guide.facts`, com o motivo 0, que não é texto           | reprova, nomeando a chave                                                                                                                                                                                 | motivo de enfeite (tabela do Setup Global do `04`) |
```

*(alterado em 2026-11-21 — step 9, achado A13)* As quatro linhas novas são o lado do conferidor da tabela do motivo de enfeite (Setup Global do `04`), a mesma que o CT-35 cita do lado da guarda do kit: "a definição é uma só" quer dizer que os dois lados decidem igual a mesma tabela. **Hoje o dado reprova as quatro:** medido no commit `168d1827`, a definição do conferidor (`site/verifica-mapa.mjs`, `motivoDeEnfeite`, no commit `168d1827`) aceita como motivo "N/A", "n/a", "not applicable", "Not applicable.", "does not apply", "Does not apply" e o número 0 — 7 das 17 entradas da tabela —, que a guarda do kit (`tests/Pest.php:motivoDeEnfeite:4221`) trata como enfeite.

*(cenário novo em 2026-11-20: ADV-06)* O catálogo pt é o que o leitor recebe, e não o arquivo do kit. O comparativo fica fora, porque a P-11 isenta a casca dele, que o archify só tem em inglês. "Motivo de enfeite" tem a definição do Setup Global do `04`.

Fato da base, no commit fixado do archify:
- o `examples/locales/pt.json` tem 461 chaves, todas também no `locales/en.json`, e as 25 que faltam o kit acrescenta;
- das 461, 14 têm o mesmo texto do en:
  - três só de marcadores: `viewer.guide.facts`, `viewer.finder.status.all` e `viewer.passport.relationship.row`;
  - onze com palavras: Frontend e Backend (quatro chaves), Upstream e Downstream (quatro chaves), Editorial, AUTO e Interface.

Isso é fato, e não a lista fechada. As sobrescritas dos tipos de peça trocam parte dessas chaves, e a lista é decisão da entrega, cada chave com o motivo.

*(alterado em 2026-11-20: quinta rodada — ADV2-26 e medição no commit `4adaf9a`)*
- **O teto da lista.** A lista fechada só pode ter chaves entre as 14 medidas e chaves só de marcadores. Quem copia o texto en para calar o aviso e acrescenta a chave à lista, com um motivo que não é de enfeite, reprova pela linha nova.
- **O catálogo em duas partes.** O archify embute em cada HTML só as 390 chaves de interface (`<script id="archify-i18n-data">`, as mesmas no pt e no en) e aplica as outras 96 no render, sem embuti-las. "O pt tem as 486 chaves" sobre o que o HTML carrega reprovaria dado correto; por isso as 96 vêm da fonte do kit (o `examples/locales/pt.json` do archify e o `site/mapa/interface.json`, que o build junta). As chaves das linhas de controle estão entre as 390. Medido: nenhuma das 486 sem texto pt; iguais ao en, 9 a 11 por HTML entre as 390 (os construtores de arquitetura sobrescrevem as dos tipos) e 14 na fonte, todas entre as 14 medidas.

**Roteiro executável**

| # | Ação | Código (no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | ler o catálogo en | o `locales/en.json` do archify baixado pelo build | 486 chaves e o texto de cada uma |
| 2 | ler o catálogo pt de cada HTML pt | o `messages` de `archify-i18n-data` do HTML pt (390 chaves) e, nas 96 que faltam nele, a fonte do kit | as 486 chaves e o texto pt |
| 3 | decidir | a mesma função para o real e para as cópias: chave ausente reprova; texto igual ao en reprova, salvo a chave da lista fechada com motivo que não é de enfeite; chave da lista fora das 14 medidas e que não é só de marcadores reprova | aceita ou reprova, nomeando a chave |
| 4 | rodar as cópias | as cópias da tabela, em memória | cada uma decide como a linha |

**Assertions**: o oráculo é a decisão sobre o catálogo real e sobre cada cópia. O real só vale se todas as cópias decidirem certo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 — revisão adversarial (ADV-06) | para calar o aviso de idioma reserva, o catálogo pt do kit recebe o texto en nas chaves não traduzidas | CT-B07 | linha "a chave da busca traz o texto en": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-06) | a lista fechada de textos iguais aceita a chave sem o motivo | CT-B07 | linha "na lista, sem o motivo": reprova; o mutante aceita |
| M9 — revisão adversarial (ADV2-26) | quem copia o texto en para calar o aviso acrescenta a chave à lista fechada, com um motivo que não é de enfeite | CT-B07 | linha `viewer.finder.placeholder` na lista, com motivo: reprova; o mutante aceita |
| M10 — step 9 (A13) | o conferidor do mapa tem a própria definição de motivo de enfeite, e aceita como motivo "N/A", "not applicable", "Does not apply" ou um número, que a guarda do kit trata como enfeite | CT-B07 | linhas da lista com `viewer.guide.facts` e o motivo "N/A", "not applicable", "Does not apply" e 0: reprova; o mutante aceita |

(Numeração da R31 do `04`. Os M1 a M6 e o M11 estão no CT-B04.)

---

## CT-B08: o conferidor, rodado sobre uma pasta de diagramas com um JSON quebrado, reprova com a mensagem `[mapa]` que nomeia o arquivo, e termina com código diferente de zero; e recusa a condição do modelo que não pode ler, nomeando a peça

*(cenário novo em 2026-11-21: step 9, achado A11 — R40, P-14)*

**Por que no conferidor**: a asserção não depende do navegador, mas o conferidor importa o Playwright e lê o `axe-core` de `site/node_modules` antes de ler os arquivos de diagrama, e só o job `site` tem essa pasta; a suíte do kit não o consegue rodar. O gerador e o posicionador têm o mesmo caso no CT-50 do `04`. A pasta de diagramas de controle entra pelo mesmo caminho do controle de processo do CT-B06: a variável de ambiente `VERIFICA_MAPA_DIAGRAMAS`.

*(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01: o cenário virou esquema. A primeira linha é o cenário de antes, sem mudança; as quatro seguintes são controles sintéticos da condição do modelo, no molde dos do CT-B06)*

```gherkin
# language: pt
  Esquema do Cenário: [CT-B08] o conferidor recusa a entrada que não pode usar e nomeia o culpado: o JSON quebrado, rodado como processo, antes de abrir qualquer diagrama, e a condição do modelo que não se lê, pela função com que lê o modelo real
    Dado o controle "<controle>"
    Quando o conferidor o decide <como>
    Então o resultado é "<resultado>"

    Exemplos:
      | controle | como | resultado | # partição |
      | uma pasta de diagramas de controle com o `tempos-ci.json` publicado e um `zz-quebrado.json` com JSON quebrado | rodado como processo sobre ela, com a pasta passada por ambiente, como no controle de processo do CT-B06 | o processo termina com código diferente de zero, e a saída traz a mensagem `[mapa]` que nomeia o arquivo `zz-quebrado.json`, e não só a mensagem do `JSON.parse` | JSON quebrado |
      | uma peça sintética com a condição do `TenantResource` do `/admin` e o modo escrito como lista, `["com"]` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | pela mesma função com que lê a condição de cada peça do modelo real | recusa, nomeando a peça e o modo `["com"]` | modo que não é texto |
      | uma peça sintética com a condição do `CommandRecordResource` e a `ligada` de `command-center.sources` trocada por `ConfigSource`, a de fábrica, e `Outro\Pacote\ConfigSource` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | pela mesma função | recusa, nomeando a peça e a configuração `command-center.sources`: pelo nome curto, a `ligada` não traz fonte além da de fábrica | fonte "nova" que só muda o namespace |
      | uma peça sintética com a condição do `CommandRecordResource` e a `ligada` trocada por `ConfigSource` e `Bityukov\CommandCenter\Sources\ConfigSource::class` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | pela mesma função | recusa, nomeando a peça e a configuração `command-center.sources` | fonte "nova" que só ganha `::class` |
      | uma peça sintética com a condição do `AgenteIaResource` e a chave trocada por `APP_DEBUG` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | pela mesma função | recusa, nomeando a peça e a chave `APP_DEBUG` | variável em maiúsculas fora de `KIT_*` |
```

- **Medido no commit `168d1827`:** `VERIFICA_MAPA_DIAGRAMAS` apontando para uma pasta com um `zz-quebrado.json` quebrado, o conferidor, rodado de `site/`, terminou com o stack do `SyntaxError` do `JSON.parse` ("Expected double-quoted property name in JSON at position 45 (line 1 column 46)"), que aponta o `lerJson` do próprio conferidor, e não o arquivo do diagrama, nem o prefixo `[mapa]`. O caso reprova hoje.
- **O código de saída** já é diferente de zero hoje (a exceção derruba o processo); o que o caso acrescenta é a mensagem que diz qual arquivo quebrou.
- *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* **A condição que o conferidor não pode ler.** O conferidor tira da condição do modelo o lugar e a marca esperados (passo 4 do CT-B01), e falha fechado na que não sabe ler: reprova uma vez, nomeando a peça, antes de abrir diagrama (`site/verifica-mapa.mjs`, `condicaoDaPeca`). Ele tem de recusar o que o gerador recusa (CT-50 do `04`, R40-M26 a M29): senão espera, para a mesma peça, uma marca que o gerador certo não publica. Os controles são peças sintéticas, decididas pela mesma função que lê o modelo real, como os do CT-B06: o modelo do conferidor não tem caminho por ambiente, e um controle de processo pediria um. A conferência real só vale se os quatro controles recusarem; o controle que aceita reprova o CT-B08, e o processo termina com código diferente de zero. A recusa a mais — a condição publicada recusada — já reprova o CT-B01 no modelo real, pela mesma função: as 18 condições de hoje usam só `KIT_*` e a `command-center.sources` com a `DatabaseSource` ligada além da `ConfigSource` de fábrica. *(alterado em 2026-11-24 — step 11, QA-10: serão 21; as três dos `Dashboard` usam a `KIT_DASHBOARD_DINAMICO`, booleana, que a `condicaoDaPeca` lê)* *(alterado em 2026-11-25 — step 11, Q32: 22, e a do `DynamicDashboard` usa a mesma chave)*
- **Medido no HEAD `b130c686`**, com a função copiada do arquivo e executada fora do repositório: o modo `["com"]` é recusado ("o modo ["com"] não tem redação decidida") — a linha passa hoje e guarda o alinhamento com o gerador (R40-M32); a `ConfigSource` de outro namespace, a `ConfigSource::class` e o `APP_DEBUG` são aceitos — as três linhas reprovam hoje (causa b); as condições publicadas do `CommandRecordResource` e do chat são aceitas.

**Roteiro executável**

| # | Ação | Código (no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar a pasta de controle | numa pasta temporária, copiar `tempos-ci.json` de `mapa/diagramas/` e gravar `zz-quebrado.json` com `{ "slug": "zz-quebrado", "tipo": "workflow", }` | a pasta de controle, com um arquivo quebrado |
| 2 | rodar o conferidor como processo | `spawn(process.execPath, [caminho do conferidor, porta])`, com `VERIFICA_MAPA_DIAGRAMAS` apontando para a pasta e `VERIFICA_MAPA_CONTROLE=1`, como no controle de processo do CT-B06 | o processo termina |
| 3 | conferir | o código de saída e a saída do processo | código diferente de zero; a saída traz `[mapa]` e `zz-quebrado.json` |
| 4 | montar os controles da condição *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | uma peça sintética por linha, com o `id`, o `nome` e a `disponibilidade` da linha | quatro peças de controle |
| 5 | decidir cada uma | `condicaoDaPeca(peca)`, a função com que o conferidor lê a condição do modelo real | cada uma devolve `{ erro }`, com o nome da peça e o culpado da linha; a que não devolve reprova o CT-B08 |

**Assertions**: o oráculo é a mensagem que nomeia o arquivo, e, nos controles, a recusa que nomeia a peça e o culpado. O código de saída é asserção de apoio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M3 — step 9 (A11) | o JSON quebrado reprova com a mensagem do `JSON.parse`, sem o arquivo nem o `[mapa]` | CT-50, CT-B08 | CT-B08: a saída não nomeia `zz-quebrado.json`, e reprova; o mutante aceita |
| M30 — step 9 do Adendo, rodada 2 (AD2-01) | o conferidor aceita a configuração de pacote com a `ligada` não vazia, sem conferir que ela traz fonte nova pelo nome curto — o caso de hoje | CT-B08 | linhas da `ConfigSource` de outro namespace e da `ConfigSource::class`: a função as aceita; reprova; o mutante aceita |
| M31 — step 9 do Adendo, rodada 2 (AD2-01) | o conferidor toma por variável do kit qualquer nome em maiúsculas — o caso de hoje | CT-B08 | linha do `APP_DEBUG`: a função a aceita; reprova; o mutante aceita |
| M32 — step 9 do Adendo, rodada 2 (AD2-01) | o conferidor, alinhado ao gerador, confere o modo pela chave da tabela da marca e passa a aceitar a lista `["com"]` | CT-B08 | linha do modo `["com"]`: a função a aceita; reprova; o mutante aceita |

(Numeração da R40 do `04`. Os M1, M2, M4 e M5 morrem no CT-49 e no CT-50, na suíte do kit. *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2)* Os M26 a M29 e M33 a M35, no CT-50)

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Página "Mapa interativo": página do site (Starlight, Markdown), rota `/{pt,en}/referencia/mapa-interativo/`, sem JS. O leitor lê a grade painel × modo e abre cada diagrama | | | |
| 2 | Diagramas do mapa: HTML do archify (visualizador), rota `/mapa/{pt,en}/{slug}.html`, com JS. O leitor tem busca de nó, painel de detalhes com a evidência, rota entre nós, lente, tema e exportação, e lê os cards de prova | | | |
