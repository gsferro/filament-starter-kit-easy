# Casos de Teste de Browser — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Derivado do `00-requisito.md` (RQ-03, RQ-07, RQ-08, RQ-25, P-02, P-04, P-07, P-11), como o `04`. Costura: `browser`, a linha "G4 — site construído" de `## Costuras de Teste` do `04` (regras R13, R14, R21, R24, R31 e R32).
> *(alterado em 2026-11-20: rodada de revisão do step 7 — os CT-B saem de `site/verifica-acessibilidade.mjs` para um conferidor próprio; CT-B04, CT-B05 e CT-B06 novos, da P-11; CT-B01 ganha a condição das peças condicionais e o M5)*
> *(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial e fatos de plataforma medidos pela sessão:*
> - *CT-B07 novo (ADV-06);*
> - *CT-B02 e CT-B03 reescritos: o card de prova é texto no painel lateral, e o passaporte mostra as provas da própria peça;*
> - *o CT-B01 perde a metade de condição e o M5, estacionados na Q11 (ADV-31), e ganha o rótulo de cada peça, a escuta de exceção e a conferência de recurso de fora (ADV-28, ADV-29, ADV-14);*
> - *o CT-B04 lê a interface também com a busca e o passaporte abertos (ADV-30);*
> - *o R13-M3 passa a morrer no CT-09 do `04` (ADV-19))*
>
> **Runtime: um conferidor Node + Playwright próprio, `site/verifica-mapa.mjs`**, sobre o site construído e servido pelo `astro preview`. Os dois workflows (`pages.yml` e o job `site` do `ci.yml`) o rodam logo depois do conferidor de acessibilidade, com a mesma prévia de pé. No `pages.yml`, ele roda antes do envio do artefato.
> - **Divergência do template, declarada:** o `pest-plugin-browser` sobe a aplicação Laravel em processo e não serve site estático (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`). O arranjo é o da wiki irmã `diagramas-da-arquitetura`.
> - **Arquivo próprio, e não o `site/verifica-acessibilidade.mjs`:** aquele já carrega `[CT-B01]` e `[CT-B02]` da wiki `diagramas-da-arquitetura`, e os IDs desta wiki também começam em 01. O CT-25 do `04` trava a ordem dos dois conferidores nos workflows, e o CT-42, que a falha deste reprova o job.
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

- A RQ-07 pede visualização interativa, e o visualizador do archify desenha no navegador.
- A P-04 pede a prova à vista no próprio diagrama. O HTML do mapa só existe depois do build, o `site/dist` é ignorado pelo git, e a suíte Pest nunca o vê. O archify só tem prova por nó (`sources`, de 1 a 3), sem prova por aresta. Por isso a prova de cada ligação no artefato depende do gerador, que a põe em cards do painel lateral, e só o HTML construído a mostra.
- A P-07 pede o link para o commit que publica, e só o build sabe qual é.
- A P-11 pede a interface do visualizador no idioma da página e a conferência de acessibilidade por amostra. As duas só o navegador mede. O catálogo en do archify, que o CT-B07 compara com o pt, só existe depois do build, que baixa o archify.
- A RQ-25 proíbe dependência nova de visualização. A biblioteca que entra por CDN ou por arquivo copiado só aparece quando o HTML abre (CT-B01).
- Nada disso cabe num teste de componente: não há componente Livewire.

## Teto e estouro justificado

O perfil `padrão` dá 1 CT-B de caminho feliz, e aqui há 7. O gate vence o teto, porque cada CT-B mata um conjunto de mutantes que nenhum outro mata:

- o **CT-B01** é o caminho feliz, e mata também os rótulos trocados entre peças, a exceção não capturada e a biblioteca carregada de fora;
- o **CT-B02** mata o que só o artefato construído revela: o gerador perde a prova da ligação desenhada por agrupamento ou por porta, o card sai sem o trecho, o card fica fora do painel lateral;
- o **CT-B03** mata o "não interativo" da RQ-07 e o link de evidência que vai para outro repositório, para um ramo ou para `vendor/`;
- o **CT-B04** mata a interface que cai no inglês, inclusive na busca e no passaporte, e o `lang` errado (P-11);
- o **CT-B05** mata a amostra vazia, o tipo fora da amostra e o comparativo não auditado (P-11);
- o **CT-B06** mata a tolerância larga demais: pelo texto do seletor, pela regra sem o elemento, ou de uma terceira violação (P-11);
- o **CT-B07** mata o catálogo pt com o texto en copiado e a lista de textos iguais sem o motivo (P-11). Ele não precisa do navegador para comparar, mas precisa do build, e por isso mora no conferidor.

Os mutantes que vieram da revisão adversarial não contam para o teto de mutantes da regra.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| rota entre nós, lente, exportação, modo apresentação | são recursos do archify; testá-los é testar o archify. O "não interativo" já morre no CT-B03 |
| troca de tema com o diagrama já aberto | a P-11 pede a amostra por tema, e o CT-B05 abre cada tema pelo mecanismo do visualizador. A troca com o diagrama aberto não tem cláusula |
| o comparativo no tema claro, na amostra de acessibilidade | a P-11 pede o comparativo só no tema escuro |
| o comparativo do `/app` mostra `DefinirTenantDePermissoes` como acrescentado | o delta é cálculo do archify, e o par certo de antes e depois já morre no CT-22 do `04` |
| voltar do diagrama para a página do mapa | o `00` não pede |
| a página "Mapa interativo" em navegador | é Markdown sem JS (Superfície de UI). O CT-15, o CT-34 do `04` e as guardas herdadas a cobrem |
| a contagem "N de saída · M de entrada" e o alcance no passaporte | é cálculo do archify, a partir das arestas do diagrama; a R36 do `04` confere as arestas |
| a marca de condição da peça condicional no desenho | estacionada na Q11 (ADV-31): o passo 4 do CT-B01 e o R24-M5 voltam com a resposta |

## Pré-requisitos

- [ ] `npm ci` e `npm run build` em `site/`. O build baixa o archify e gera `dist/mapa/{pt,en}/{slug}.html`.
- [ ] `astro preview` de pé na porta 4321, com `wait-on` antes dos conferidores. Com `DOCS_BASE`, o conferidor prefixa a base, como o de acessibilidade já faz.
- [ ] O commit do build: `git rev-parse HEAD` no checkout. No CI, é o `GITHUB_SHA`.
- [ ] O repositório do kit: o `support.source` do `composer.json` (`https://github.com/gsferro/filament-starter-kit-easy`).
- [ ] O esperado vem da fonte, nunca do `dist/`: o modelo de ligações, o modelo de peças e os arquivos de diagrama de `site/mapa/`. Assim o oráculo não sai do mesmo artefato que ele confere.
- [ ] O catálogo de interface en do archify do commit fixado, que o build baixa (`locales/en.json`, 486 chaves). É a lista das chaves que o CT-B04 percorre e a base do CT-B07.
- [ ] As rotas do mapa vêm dos arquivos de diagrama, e não de uma varredura do `dist/` por `index.html`, como a do conferidor herdado (`site/verifica-acessibilidade.mjs:'index.html':78`, `site/verifica-acessibilidade.mjs:todas:82`).
- [ ] As escutas ficam ligadas antes do `goto`: o `console` do tipo error, o `pageerror` (exceção não capturada) e o `request` (toda requisição da página).
- [ ] Nunca esperar por segundos fixos. A espera é por estado: o nó desenhado, o passaporte aberto, o tema aplicado.
- [ ] O axe vem de `node_modules/axe-core`, como no conferidor herdado. Os controles sintéticos do CT-B06 e as cópias do CT-B07 passam pela **mesma** função que decide o real, como os controles de `site/verifica-acessibilidade.mjs`.

## Seletores

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão num HTML do archify, no commit fixado, no Chromium)*

| Elemento | Seletor | Já existe? |
|---|---|---|
| nó desenhado de uma peça | `[data-node-id]`, com o rótulo em `data-node-label` | sim, medido num HTML de arquitetura. Nos outros tipos, a descobrir: o código do visualizador, o mesmo nos exemplos do archify de todos os tipos, usa os dois atributos, mas o elemento de cada tipo só o HTML gerado mostra |
| aresta desenhada | `[data-edge-from][data-edge-to]`, com `data-edge-id` | sim, medido num HTML de arquitetura; nos outros tipos, a descobrir |
| card de prova das ligações | `.reader-rail .card`, no painel lateral, sempre presente, sem depender de foco | sim, medido num HTML de arquitetura. Todo esquema do archify aceita `cards` |
| campo de busca de nó | atalho `/` do visualizador (README do archify) | a descobrir |
| passaporte da peça em foco | o painel que aparece quando o leitor foca a peça | a descobrir |
| link de evidência do passaporte | `a[href*="/blob/"]` dentro do passaporte, no formato `https://github.com/{dono}/{repo}/blob/{commit}/{arquivo}#L{linha}` | o formato, sim (medido); o seletor, a descobrir |
| título do diagrama | `document.title` ou o título que o visualizador mostra | a descobrir |
| idioma do documento | `document.documentElement.lang`, que segue o `meta.locale` (medido: `pt-BR`) | sim (medido) |
| aviso de idioma reserva | o aviso que o archify mostra quando falta a tradução de uma chave | a descobrir |
| alvo tolerado 1 | o `<svg>` raiz do diagrama, resolvido como **elemento** no DOM, nunca pelo texto do seletor do axe | a descobrir |
| alvo tolerado 2 | `.node-outline-heading > small`, a contagem de grupo do índice de nós, resolvido como **elemento** | a descobrir |

---

## CT-B01: cada diagrama do mapa abre desenhado, cada peça com o seu rótulo, sem erro nem exceção, sem recurso de fora, no idioma da página

**Por que browser**: o visualizador desenha as peças com JavaScript. O HTML cru não prova que o diagrama apareceu, e o recurso que ele carrega só aparece quando ele abre.

```gherkin
# language: pt
  Cenário: [CT-B01] cada diagrama do mapa abre desenhado, cada peça com o seu rótulo, sem erro nem exceção, sem recurso de fora e no idioma da página, nos dois idiomas
    Dado o site construído pelo build do commit, servido pela prévia
    E os arquivos de diagrama do mapa, com os textos de cada um em pt e em en
    Quando o conferidor abre cada diagrama publicado, em pt e em en
    Então o visualizador desenha, em cada um, as peças que o arquivo daquele diagrama desenha, e cada peça desenhada tem, pelo id, o rótulo que o arquivo dá a ela no idioma da página
    E nenhum erro de console e nenhuma exceção não capturada são registrados
    E nenhuma requisição da página sai da origem da prévia, fora `data:` e `blob:`, e o documento não tem `<script src>` nem `<link rel="stylesheet">`
    E o título mostrado é o do arquivo no idioma da página, com os acentos intactos
    E o total de diagramas abertos é o número de arquivos de diagrama vezes dois
```

*(alterado em 2026-11-20: ADV-31, ADV-28, ADV-29 e ADV-14)*
- **Saiu a condição.** A metade de condição (o passo 4) e o M5 estão estacionados na Q11, com os IDs reservados.
- **O rótulo de cada peça.** O par id e rótulo pega a troca de rótulos entre peças, que a contagem e um conjunto de nomes deixam passar.
- **Requisições e elementos.** O HTML do archify, no commit fixado, é autocontido: o template e os 15 exemplos não trazem `<script src>` nem `<link>` de folha de estilo, e a sessão mediu zero requisição externa num HTML gerado. O renderizador do comparativo (`delta/architecture-delta.mjs` do archify) também não escreve nenhum dos dois; o HTML do comparativo não foi aberto, e o primeiro build o confirma.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | listar os diagramas pela fonte | ler `site/mapa/diagramas/`; N = quantidade de arquivos | N > 0 |
| 2 | abrir cada um, com as escutas ligadas | `pagina.on('console', …)` filtrando `type() === 'error'`, `pagina.on('pageerror', …)` e `pagina.on('request', …)`; depois `pagina.goto(\`http://localhost:${PORTA}${BASE}/mapa/${idioma}/${slug}.html\`)` e esperar o primeiro `[data-node-id]` | o diagrama aparece |
| 3 | ler as peças desenhadas | `pagina.evaluate(...)` sobre o elemento de cada peça (`[data-node-id]`, com o rótulo em `data-node-label`, no tipo em que isso se confirmar; ver Seletores): o par id e rótulo de cada uma | os pares do arquivo, no idioma da página |
| 4 | ~~conferir a condição~~ | estacionado na Q11 (ADV-31) | — |
| 5 | conferir os recursos | as requisições escutadas e `document.querySelectorAll('script[src], link[rel="stylesheet"]')` | nenhuma requisição fora da origem da prévia (fora `data:` e `blob:`); nenhum dos dois elementos |
| 6 | ler o título | o título mostrado | o título do arquivo naquele idioma, com acento |
| 7 | totalizar | contador por idioma | N em pt e N em en |

**Assertions**: o oráculo é o par id e rótulo de cada peça, os recursos da página e o título. O console e a exceção são asserção de apoio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o conferidor do mapa lista os diagramas varrendo o `dist/` por `index.html`, como o de acessibilidade (`site/verifica-acessibilidade.mjs:'index.html':78`), e não acha nenhum HTML do mapa | CT-B01 | "o total de diagramas abertos é o número de arquivos vezes dois": dá zero e reprova; o mutante aceita |
| M2 | o gerador grava o HTML sem declarar UTF-8, e os acentos se trocam | CT-B01 | "o título … com os acentos intactos": reprova; o mutante aceita |
| M3 | o HTML en sai com os textos pt | CT-B01 | idioma en, "o título é o do arquivo no idioma da página": reprova; o mutante aceita |
| M4 | o visualizador não carrega (o script do archify não entrou no HTML), e a página fica em branco | CT-B01 | "desenha as peças que o arquivo daquele diagrama desenha": dá zero peça e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-28) | o desenho tem o mesmo número de peças, com os rótulos trocados entre elas | CT-B01 | "cada peça desenhada tem, pelo id, o rótulo que o arquivo dá a ela, no idioma da página": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-29) | o script do visualizador lança exceção não capturada na busca ou no detalhe, e o conferidor só escuta o console | CT-B01 | "nenhuma exceção não capturada": reprova; o mutante aceita |

(Numeração da R24 do `04`. O M5, a marca de condição, está estacionado na Q11.)

Mutante da R21 que este CT-B também mata:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M5 — revisão adversarial (ADV-14) | o HTML do diagrama carrega a biblioteca por CDN, ou de um arquivo copiado para `site/public/` | CT-B01 | "nenhuma requisição sai da origem da prévia, e o documento não tem `<script src>` nem `<link rel="stylesheet">`": reprova; o mutante aceita |

(Numeração da R21 do `04`. Os M1, M2 e M4 dela morrem no CT-23; o M3 é lacuna declarada.)

---

## CT-B02: cada ligação que o diagrama desenha traz a prova num card do painel lateral do próprio diagrama, à vista

**Por que browser**: o card de prova está no HTML que o build gera, no painel lateral que o visualizador monta. Ele não existe na fonte versionada, e a prova por aresta não é recurso do archify.

```gherkin
# language: pt
  Cenário: [CT-B02] cada ligação que o diagrama desenha traz no próprio diagrama, num card do painel lateral, o arquivo e o trecho da prova
    Dado o site construído pelo build do commit, servido pela prévia
    E o modelo de ligações, com a prova de cada uma
    Quando o conferidor abre cada diagrama publicado, sem focar peça nenhuma
    Então cada ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros, uma por ligação — tem a prova, com o arquivo e o trecho do modelo, num card do painel lateral do diagrama, à vista
    E o total de provas conferidas é o de ligações desenhadas, maior que zero em todo diagrama que desenha ligação
```

*(reescrito em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)*
- **Onde fica o card.** Os cards ficam no painel lateral, sempre presentes, sem depender de foco. A prova de cada ligação fica num card do próprio diagrama, agrupado pela peça de origem; o agrupamento é desenho, e o oráculo é "num card do painel lateral, à vista".
- **O card é texto.** O archify escreve o item do card escapado, sem link. O link de evidência que o leitor clica é o do passaporte (CT-B03).
- **O número de linha.** Se o modelo guardar o número de linha (R13-M3), ele morre no CT-09 do `04`, na suíte do kit, que roda em todo PR (ADV-19).

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar o esperado pela fonte | ler o modelo de ligações e, de cada arquivo de diagrama, as ligações que ele desenha, pela definição da R12 do `04` (as de agrupamento e as de porta que nomeiam os membros, expandidas em uma por ligação; a porta que só nomeia o mapa não desenha a ligação) | lista de (diagrama, ligação, arquivo, trecho) |
| 2 | abrir cada diagrama | `pagina.goto(…)` e esperar o desenho, sem clicar em peça | o diagrama aparece |
| 3 | ler os cards | `pagina.evaluate(...)` sobre `.reader-rail .card`: o título e os itens de cada card, e se ele está à vista (caixa com altura, sem `display: none` nem `visibility: hidden`) | arquivo e trecho por ligação, à vista |
| 4 | conferir as provas | cada ligação esperada tem, num card à vista, o arquivo e o trecho iguais aos do modelo | nenhuma faltando, nenhuma trocada |
| 5 | totalizar | provas conferidas por diagrama | igual às ligações desenhadas, maior que zero |

**Assertions**: o oráculo é a igualdade com o modelo (arquivo, trecho), o card à vista sem foco e a contagem. Um "há um card" sozinho não serve.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o gerador perde a prova das ligações desenhadas por agrupamento ou por porta que nomeia os membros *(alterado em 2026-11-20: a porta entrou com os fatos de plataforma)* | CT-B02 | "uma por ligação": a ligação do membro sem prova reprova; o mutante aceita |
| M2 | o card traz só o arquivo, sem o trecho *(reescrito em 2026-11-20: era "link de prova para `blob/main`", que passou à R14 como M5, porque o card do archify é texto, sem link)* | CT-B02 | "a prova, com o arquivo e o trecho do modelo": reprova; o mutante aceita |
| M4 | o card mostra a prova de outra ligação (índice trocado) | CT-B02 | "o arquivo e o trecho do modelo": reprova; o mutante aceita |
| M5 | a prova só fica num documento à parte, nunca no diagrama | CT-B02 | "o total de provas conferidas é o de ligações desenhadas, maior que zero": dá zero e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-25) | o card da prova fica fora do painel lateral: oculto, ou só aparece quando o leitor foca a peça | CT-B02 | "num card do painel lateral do diagrama, à vista", com o diagrama aberto sem focar peça nenhuma: reprova; o mutante aceita |

(Numeração da R13 do `04`. O M3 morre no CT-09 do `04`.)

---

## CT-B03: o leitor acha a peça pela busca, e o passaporte dela mostra as provas da própria peça, com o link para o repositório do kit no commit do build

**Por que browser**: a busca e o passaporte existem só com o JavaScript do visualizador rodando. É a interação que a RQ-07 pede.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B03] o leitor acha a peça pela busca, e o passaporte dela mostra as provas da própria peça, com o link para o repositório do kit no commit do build
    Dado o primeiro diagrama `architecture` publicado, aberto em "<idioma>", e a primeira peça do modelo que o arquivo dele desenha
    E o repositório do kit, o `support.source` do `composer.json`
    Quando o leitor busca a peça pelo nome e a foca
    Então o passaporte aparece, com o rótulo da peça
    E diz a origem verificada no repositório do kit, no commit do build
    E mostra de uma a três provas da própria peça, cada uma com o link para o repositório do `support.source`, no commit do build, para um arquivo que existe nesse commit

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

Ele não lista cada ligação com a prova dela: essa prova está no card (CT-B02). Por isso o caso deixou de pedir a prova da ligação no detalhe.

A prova da peça é só caminho e linha, porque o `sources` do archify não guarda trecho. O oráculo do link é o da P-07: o repositório do `support.source`, o commit do build e um arquivo que existe nesse commit.

O diagrama e a peça saem dos arquivos de diagrama, na ordem em que estão, e não de uma lista escrita no teste. O tipo é `architecture` porque a peça dele é peça do modelo, com arquivo no kit; o marco da linha do tempo, por exemplo, não tem prova de código.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | escolher o diagrama e a peça | o primeiro arquivo de diagrama `architecture` e a primeira peça do modelo nele (não uma caixa de agrupamento nem de porta) | diagrama e peça |
| 2 | abrir o diagrama | `pagina.goto(\`…/mapa/${idioma}/${slug}.html\`)` e esperar o desenho | o diagrama aparece |
| 3 | buscar a peça | `pagina.keyboard.press('/')`, digitar o nome da peça e confirmar | a peça fica em foco |
| 4 | ler o passaporte | `pagina.evaluate(...)` sobre o passaporte | o rótulo da peça; a origem verificada com o repositório e o commit |
| 5 | conferir os links | do `href` de cada prova, extrair dono, repositório, commit e caminho; conferir o arquivo no commit (`git cat-file -e {commit}:{caminho}`) | dono e repositório iguais aos do `support.source`; commit igual a `git rev-parse HEAD`; o arquivo existe; de 1 a 3 links |

**Assertions**: o oráculo é o passaporte da peça escolhida: o rótulo, a origem verificada e os links. O foco da peça sozinho não basta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama é exportado como imagem estática, sem busca nem passaporte | CT-B03 | "o leitor busca a peça e a foca": não há busca e reprova; o mutante aceita |
| M2 | o nó sai sem provas, e o passaporte não mostra a origem verificada *(reescrito em 2026-11-20: era "o detalhe não traz a prova da ligação", que o passaporte do archify não mostra por desenho)* | CT-B03 | "mostra de uma a três provas da própria peça": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-25) | o link do passaporte leva a outro repositório, como um fork ou um nome errado, com o mesmo commit e caminho *(reescrito em 2026-11-20: era "o detalhe do agrupamento não nomeia os membros", que passou à R12 como M5)* | CT-B03 | "leva ao repositório do `support.source`": reprova; o mutante aceita |
| M4 | a prova da peça de pacote aponta para `vendor/`, fora do commit, e o link dá 404 | CT-B03 | "a um arquivo que existe nesse commit": reprova; o mutante aceita |
| M5 | link de evidência para `blob/main`, um ramo que anda *(veio da R13, onde era o M2)* | CT-B03 | "no commit do build": o `href` não traz o commit e reprova; o mutante aceita |

---

## CT-B04: a interface do visualizador fala o idioma da página, e só a casca do comparativo fica em inglês

**Por que browser**: a interface do visualizador (botões, busca, legenda, passaporte, aviso de idioma reserva) é montada pelo JavaScript do archify a partir do `meta.locale` e das traduções. Só o documento desenhado mostra o que o leitor lê.

```gherkin
# language: pt
  Cenário: [CT-B04] a interface do visualizador fala o idioma da página, e só a casca do comparativo fica em inglês
    Dado o site construído pelo build do commit, servido pela prévia
    E o catálogo de interface en do archify do commit fixado
    Quando o conferidor abre cada diagrama publicado, em pt e em en
    Então, na versão pt de todo diagrama fora o comparativo, toda chave da interface do visualizador mostra o texto pt — nenhuma cai no inglês, e nenhum aviso de idioma reserva aparece —, e o `lang` do documento começa por `pt`
    E, na versão pt de todo diagrama fora o comparativo, com a busca aberta e o passaporte de uma peça aberto, toda chave visível mostra o texto pt
    E, na versão pt de todo diagrama fora o comparativo, a legenda dos tipos de peça usa os nomes da RQ-24
    E, na versão pt do comparativo, o texto que o kit escreveu está em pt, e só a casca do archify fica em inglês
    E, na versão en, a interface está em inglês, e o `lang` do documento começa por `en`
```

O `lang` do comparativo pt não é afirmado. A P-11 isenta a casca dele e não decide o idioma do documento.

*(alterado em 2026-11-20: ADV-30 e fatos de plataforma)*
- **O `lang`.** O `lang` do documento segue o `meta.locale` (medido: `pt-BR`).
- **A busca e o passaporte.** Eles montam os textos na interação, e por isso o caso lê a interface também com os dois abertos.
- **O filtro do passo 3.** Ele isenta só a chave cujo texto pt é igual ao en. Depois do CT-B07, que reprova a chave copiada fora da lista fechada, essa isenção vale só para as chaves da lista.

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | ler o catálogo de interface en do archify | o arquivo de locale en do archify baixado pelo build | as chaves e o texto en de cada uma |
| 2 | abrir cada diagrama pt | `pagina.goto(\`…/mapa/pt/${slug}.html\`)` e esperar o desenho | o diagrama aparece |
| 3 | ler o idioma e a interface | `document.documentElement.lang`; os textos da interface do visualizador; a presença do aviso de idioma reserva | `lang` começa por `pt`; nenhum texto de interface igual ao en de uma chave cujo texto pt é outro; nenhum aviso |
| 4 | ler a legenda dos tipos de peça | os rótulos da legenda | os nomes da RQ-24 (painel, recurso, página, widget, plugin de pacote, provider, middleware, serviço próprio, model…) |
| 5 | abrir a busca e o passaporte | `/` e o foco da primeira peça, como no CT-B03 | os textos visíveis da busca e do passaporte: nenhum igual ao en de uma chave cujo texto pt é outro; nenhum aviso |
| 6 | repetir em en | `pagina.goto(\`…/mapa/en/${slug}.html\`)` | `lang` começa por `en`; interface em inglês |
| 7 | separar a exceção | o comparativo pt | o texto escrito pelo kit nele em pt; a casca isenta, inclusive o `lang` |

**Assertions**: o oráculo é o texto da interface e o `lang` lidos no documento desenhado, no carregamento e com a busca e o passaporte abertos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | uma chave da interface do archify sem tradução pt cai no inglês, com o aviso de idioma reserva | CT-B04 | "nenhuma chave da interface cai no inglês, e nenhum aviso de idioma reserva aparece": reprova; o mutante aceita |
| M2 | o documento pt se declara `lang="en"` (o idioma não chega ao HTML) | CT-B04 | "o `lang` do documento começa por `pt`": reprova; o mutante aceita |
| M3 | a legenda dos tipos de peça fica com os rótulos do archify em inglês | CT-B04 | "a legenda dos tipos de peça usa os nomes da RQ-24": reprova; o mutante aceita |
| M4 | a exceção do comparativo vale para todo diagrama, e a interface inteira fica em inglês sem reprovar | CT-B04 | "na versão pt de todo diagrama fora o comparativo, … nenhuma cai no inglês": reprova; o mutante aceita |
| M5 | a versão en recebe a tradução pt (catálogo de tradução trocado de idioma) | CT-B04 | versão en, "a interface está em inglês": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-30) | a busca e o passaporte montam os textos na interação, e uma chave deles cai no inglês | CT-B04 | "com a busca aberta e o passaporte de uma peça aberto, toda chave visível mostra o texto pt": reprova; o mutante aceita |

(Numeração da R31 do `04`. O M7 e o M8 estão no CT-B07.)

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

(Numeração da R32 do `04`. M1 a M3 estão no CT-B06.)

---

## CT-B06: a tolerância reconhece os dois defeitos do archify pela regra e pelo elemento, e reprova qualquer outra violação

**Por que browser**: a decisão depende do elemento que o axe aponta, resolvido no DOM, e não do texto do seletor. Os controles são páginas sintéticas abertas no navegador, decididas pela mesma função que decide a amostra real, no molde dos controles de `site/verifica-acessibilidade.mjs`.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B06] a tolerância reconhece os dois defeitos do archify pela regra e pelo elemento, e reprova qualquer outra violação
    Dado uma página de controle com "<violação>"
    Quando o conferidor decide sobre ela pela mesma função que decide a amostra real
    Então o resultado é "<resultado>"

    Exemplos:
      | violação                                                                    | resultado | # partição                     |
      | `nested-interactive` no `<svg>` raiz do diagrama                            | tolera    | defeito tolerado 1             |
      | `color-contrast` em `.node-outline-heading > small`                         | tolera    | defeito tolerado 2             |
      | os dois defeitos tolerados juntos, nos elementos certos                     | tolera    | os dois                        |
      | `nested-interactive` num `<svg>` que não é o raiz do diagrama               | reprova   | regra certa, elemento errado   |
      | `color-contrast` num `<small>` fora de `.node-outline-heading`              | reprova   | regra certa, elemento errado   |
      | `color-contrast` num parágrafo do corpo                                     | reprova   | regra sem o elemento           |
      | `button-name` (serious) num botão sem nome                                  | reprova   | terceira violação              |
```

**Roteiro executável**

| # | Ação | Código (Playwright, no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | montar cada página de controle | `pagina.setContent(…)` com a marcação mínima que produz a violação da linha | a página de controle |
| 2 | rodar o axe | o mesmo `axe.run` da amostra | a violação da linha |
| 3 | decidir | a função de decisão do CT-B05, que resolve o alvo do axe para o **elemento** e compara com o `<svg>` raiz e com `.node-outline-heading > small` | tolera ou reprova, como na linha |

**Assertions**: o oráculo é a decisão de cada linha. A amostra real só vale se todos os controles decidirem certo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a tolerância casa pelo texto do seletor do axe: qualquer alvo cujo seletor contém `svg` ou `small` passa | CT-B06 | linhas "`nested-interactive` num `<svg>` que não é o raiz" e "`color-contrast` num `<small>` fora de `.node-outline-heading`": reprova; o mutante tolera |
| M2 | a tolerância casa pela regra sem o elemento: todo `color-contrast` passa | CT-B06 | linha "`color-contrast` num parágrafo do corpo": reprova; o mutante tolera |
| M3 | uma terceira violação entra na tolerância | CT-B06 | linha "`button-name` num botão sem nome": reprova; o mutante tolera |

(Numeração da R32 do `04`. M4 a M6 estão no CT-B05.)

---

## CT-B07: o catálogo pt que o kit entrega ao visualizador não copia o texto en, fora a lista fechada de textos iguais, com o motivo

**Por que no conferidor**: o catálogo en (`locales/en.json` do archify) só existe depois do build, que baixa o archify no commit fixado. A comparação não precisa do navegador, mas precisa do site construído, e por isso mora no conferidor do mapa. As cópias são em memória, decididas pela mesma função que decide o catálogo real.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B07] o catálogo pt que o kit entrega ao visualizador não copia o texto en, fora a lista fechada de textos iguais nos dois idiomas, com o motivo
    Dado o catálogo en embutido no archify do commit fixado, `locales/en.json`, com 486 chaves
    E o catálogo pt que o kit entrega ao visualizador "<mundo>"
    Quando o conferidor compara os dois, chave a chave, pela mesma função que decide o catálogo real
    Então o resultado é "<resultado>"

    Exemplos:
      | mundo                                                                    | resultado                                                                                                                          | # partição        |
      | o de cada HTML pt publicado, fora o comparativo                          | aceita: o pt tem as 486 chaves do en, e toda chave cujo texto pt é igual ao en está na lista fechada do conferidor, com o motivo     | válida            |
      | cópia em que `viewer.finder.placeholder`, a da busca, traz o texto en    | reprova, nomeando a chave                                                                                                          | texto en copiado  |
      | cópia sem a chave `viewer.passport.evidence`                             | reprova, nomeando a chave                                                                                                          | chave ausente     |
      | cópia da lista com `viewer.guide.facts`, só de marcadores, sem o motivo  | reprova, nomeando a chave                                                                                                          | motivo ausente    |
      | cópia da lista com `viewer.guide.facts`, com o motivo "—"                | reprova, nomeando a chave                                                                                                          | motivo de enfeite |
```

*(cenário novo em 2026-11-20: ADV-06)* O catálogo pt é o que cada HTML pt carrega, e não o arquivo do kit: é o que o leitor recebe. O comparativo fica fora, porque a P-11 isenta a casca dele, que o archify só tem em inglês. "Motivo de enfeite" tem a definição do Setup Global do `04`.

Fato da base, no commit fixado do archify:
- o `examples/locales/pt.json` tem 461 chaves, todas também no `locales/en.json`, e as 25 que faltam o kit acrescenta;
- das 461, 14 têm o mesmo texto do en:
  - três só de marcadores: `viewer.guide.facts`, `viewer.finder.status.all` e `viewer.passport.relationship.row`;
  - onze com palavras: Frontend e Backend (quatro chaves), Upstream e Downstream (quatro chaves), Editorial, AUTO e Interface.

Isso é fato, e não a lista fechada. As sobrescritas dos tipos de peça trocam parte dessas chaves, e a lista é decisão da entrega, cada chave com o motivo.

**Roteiro executável**

| # | Ação | Código (no conferidor) | Resultado visível |
|---|---|---|---|
| 1 | ler o catálogo en | o `locales/en.json` do archify baixado pelo build | 486 chaves e o texto de cada uma |
| 2 | ler o catálogo pt de cada HTML pt | as traduções que o HTML pt entrega ao visualizador | as chaves e o texto pt |
| 3 | decidir | a mesma função para o real e para as cópias: chave ausente reprova; texto igual ao en reprova, salvo a chave da lista fechada com motivo que não é de enfeite | aceita ou reprova, nomeando a chave |
| 4 | rodar as cópias | as cópias da tabela, em memória | cada uma decide como a linha |

**Assertions**: o oráculo é a decisão sobre o catálogo real e sobre cada cópia. O real só vale se todas as cópias decidirem certo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 — revisão adversarial (ADV-06) | para calar o aviso de idioma reserva, o catálogo pt do kit recebe o texto en nas chaves não traduzidas | CT-B07 | linha "a chave da busca traz o texto en": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-06) | a lista fechada de textos iguais aceita a chave sem o motivo | CT-B07 | linha "na lista, sem o motivo": reprova; o mutante aceita |

(Numeração da R31 do `04`. M1 a M6 estão no CT-B04.)

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Página "Mapa interativo": página do site (Starlight, Markdown), rota `/{pt,en}/referencia/mapa-interativo/`, sem JS. O leitor lê a grade painel × modo e abre cada diagrama | | | |
| 2 | Diagramas do mapa: HTML do archify (visualizador), rota `/mapa/{pt,en}/{slug}.html`, com JS. O leitor tem busca de nó, painel de detalhes com a evidência, rota entre nós, lente, tema e exportação, e lê os cards de prova | | | |
