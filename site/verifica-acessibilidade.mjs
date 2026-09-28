/**
 * Acessibilidade do site construído, com axe-core num navegador real.
 *
 * ## Por que isto existe fora da suíte do Pest
 *
 * O `pest-plugin-browser` sobe **a aplicação Laravel** em processo — ele não tem como servir um
 * site estático gerado por outro toolchain. Não é questão de custo: é impossível. Por isso a
 * afirmação sobre acessibilidade mora aqui, ao lado do conferidor de links, e o `[CT-40]` garante
 * que o fluxo de publicação roda os dois antes de enviar o artefato.
 *
 * ## Por que os DOIS temas
 *
 * Contraste é a única classe de defeito em que a árvore de acessibilidade é cega ao olhar e o
 * olhar é cego à árvore: `assertSee('Salvar')` passa com texto branco em fundo branco, e um
 * screenshot não diz qual é a razão de contraste. O axe calcula.
 *
 * E este site já teve o defeito: a primeira versão do tema redefinia `--sl-color-gray-6/7` no
 * seletor global, e o chip de código inline virou bloco quase preto com texto escuro **só no tema
 * claro**. No escuro estava impecável. Rodar um tema só teria passado.
 *
 * ## O que ele NÃO faz
 *
 * Não navega por teclado, não confere ordem de foco e não julga texto alternativo — o axe reporta
 * ausência de `alt`, não se o `alt` diz algo útil. São lacunas declaradas, não cobertas.
 *
 * ## Diagramas Mermaid (ADR-02)
 *
 * O `astro-mermaid` desenha o SVG **no cliente**, depois do `domcontentloaded` — rodar o axe no
 * `<pre class="mermaid">` cru não prova nada. Antes do axe, cada página espera todo `pre.mermaid`
 * terminar de processar (`data-processed`, marcado tanto no sucesso quanto no erro) e reprova se
 * algum bloco não virou `<svg>` ou virou o elemento de erro que a integração instalada (v2.1.0)
 * põe no lugar do bloco quando `mermaid.render()` rejeita
 * (`node_modules/astro-mermaid/astro-mermaid-integration.js`, bloco `catch` de `initMermaid()`:
 * um `<div>` com um `<strong>Error rendering diagram:</strong>` filho direto). A detecção é
 * ESTRUTURAL — a presença desse elemento, nunca um regex sobre o texto visível do bloco: um
 * diagrama válido cujo próprio rótulo contivesse a mesma frase (ex.: um fluxo que documenta
 * tratamento de erro) seria acusado de quebrado por um regex, e não é o caso aqui. É a única
 * validação de SINTAXE que estes blocos têm: a guarda Pest lê o texto e não roda o Mermaid; o
 * GitHub não reprova nada.
 *
 * ## [CT-B01]/[CT-B02] — o que este arquivo ganhou para a wiki `diagramas-da-arquitetura`
 *
 * O que estava aqui ANTES media só duas coisas: um PISO de páginas com diagrama (>= 10 por
 * idioma, contando página como 1 tenha ela 1 ou 5 blocos) e uma AMOSTRA de 6 páginas no tema
 * claro (pensada para defeito de CSS global, não para Mermaid). O `[CT-B01]` pede mais forte —
 * contagem EXATA de SVG por página, contra a FONTE (`docs/{pt,en}/**\/*.md`), nos DOIS temas — e
 * o `[CT-B02]` pede uma coisa que nada aqui fazia: abrir com um tema, TROCAR com a página já
 * aberta, e prometer que o mesmo nó muda de cor. As duas seções novas (`blocosMermaidNaFonte()` e
 * `conferirCtB02()`) SOMAM-SE ao que já existia — nenhum piso ou amostra anterior foi removido.
 *
 * Uso: `node verifica-acessibilidade.mjs [porta]`
 */
import { chromium } from 'playwright';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, resolve } from 'node:path';

const PORTA = process.argv[2] || '4321';
const DIST = resolve('dist');
const DOCS = resolve('../docs');
const AXE = readFileSync(resolve('node_modules/axe-core/axe.min.js'), 'utf8');

/*
 * O prefixo base, pelo mesmo motivo do `verifica-links.mjs`: o `astro preview` serve o site sob
 * `/filament-starter-kit-easy/` quando o build recebeu esse `base`. Navegar para `/pt/` ali dá
 * 404, e o axe reportaria zero violação numa página de erro — verde sobre o nada.
 */
const BASE = (process.env.DOCS_BASE || '').replace(/\/+$/, '');

/** As rotas do site construído, derivadas do `dist/` — nunca uma lista escrita à mão. */
function rotas(dir = DIST, prefixo = '') {
  return readdirSync(dir).flatMap((nome) => {
    const caminho = join(dir, nome);

    if (statSync(caminho).isDirectory()) {
      return rotas(caminho, `${prefixo}/${nome}`);
    }

    return nome === 'index.html' && prefixo !== '' ? [`${prefixo}/`] : [];
  });
}

const todas = rotas().filter((r) => /^\/(pt|en)\//.test(r) || r === '/pt/' || r === '/en/');

/*
 * Amostra do tema claro: o tema é uniforme, então um defeito de cor aparece em qualquer página
 * que tenha o elemento. O que varia por página é ESTRUTURA — cabeçalho, imagem, tabela —, e essa
 * é coberta varrendo todas no tema padrão.
 */
const AMOSTRA_CLARA = [
  '/pt/',
  '/en/',
  '/pt/recursos/configuracoes-do-kit/',
  '/pt/autenticacao/',
  '/pt/referencia/arquitetura-em-diagramas/',
  '/en/referencia/arquitetura-em-diagramas/',
];

/** Quanto esperar pelo render client-side do Mermaid antes de desistir (ADR-02). */
const MERMAID_TIMEOUT_MS = 15_000;

/*
 * Piso de páginas com diagrama por idioma (R23/CT-37, mutante M4): sem isto, uma varredura que
 * não achasse `pre.mermaid` em página nenhuma (regressão que apaga os blocos, ou o catálogo de
 * `docs/` movido sem o site acompanhar) ficaria verde do mesmo jeito — zero é um resultado
 * silencioso demais para passar batido. Contado só no tema escuro, que varre TODAS as rotas
 * (`todas`); o claro varre a amostra e contaria de menos.
 */
const PISO_DIAGRAMAS_POR_IDIOMA = 10;

/**
 * [CT-B01] Blocos ```` ```mermaid ```` por página, contados na FONTE
 * (`docs/{pt,en}/**\/*.md`) — nunca no `dist/` construído. O `dist/` não tem NENHUM `<svg>` (o
 * astro-mermaid desenha no cliente, ADR-02), e contar `<pre class="mermaid">` ali só provaria que
 * o Starlight concordou consigo mesmo — inclusive quando concorda ERRADO: o mutante M1 (integração
 * fora de ordem) faz a cerca virar bloco de código comum, que TAMBÉM não aparece como `.mermaid` no
 * `dist/`. O oráculo de M1 só existe comparando com um número que nenhum dos dois lados do defeito
 * pode inventar sozinho — por isso a fonte, e não o build.
 *
 * @returns {Record<string, number>} rota do site → número de blocos na fonte daquela página
 */
function blocosMermaidNaFonte() {
  const coletarMarkdown = (dir) =>
    readdirSync(dir).flatMap((nome) => {
      const caminho = join(dir, nome);

      return statSync(caminho).isDirectory()
        ? coletarMarkdown(caminho)
        : nome.endsWith('.md') ? [caminho] : [];
    });

  /** @type {Record<string, number>} */
  const porRota = {};

  for (const idioma of ['pt', 'en']) {
    for (const arquivo of coletarMarkdown(join(DOCS, idioma))) {
      // Só a cerca ```` ```mermaid ```` sozinha na linha — a mesma forma que o `astro-mermaid`
      // exige para reconhecer o bloco (nunca com atributos colados, ex. ```` ```mermaid title=x ````).
      const n = (readFileSync(arquivo, 'utf8').match(/^```mermaid\s*$/gm) ?? []).length;

      if (n === 0) {
        continue;
      }

      // O caminho em disco vira rota: tira o prefixo de `docs/`, troca `\` por `/` (Windows) e
      // `.md` pela barra final; `index.md` é a própria pasta (mesma convenção do Starlight).
      let relativo = arquivo.slice(DOCS.length).replace(/\\/g, '/').replace(/\.md$/, '');

      if (relativo.endsWith('/index')) {
        relativo = relativo.slice(0, -'/index'.length);
      }

      porRota[`${relativo}/`] = n;
    }
  }

  return porRota;
}

const blocosNaFonte = blocosMermaidNaFonte();
const rotasComDiagrama = Object.keys(blocosNaFonte);

/** [CT-B01] soma de blocos na fonte, por idioma — o "20 por idioma" do cenário Gherkin. */
const blocosNaFontePorIdioma = { pt: 0, en: 0 };
for (const [rota, n] of Object.entries(blocosNaFonte)) {
  blocosNaFontePorIdioma[rota.startsWith('/en/') ? 'en' : 'pt'] += n;
}

/*
 * [CT-B01] Amostra clara AMPLIADA: toda página com diagrama entra também no tema claro, além das 6
 * da amostra estrutural original. Sem isto (mutante M5) uma página com diagrama fora da amostra
 * podia perder o tema claro inteiro (CSS fixo por cima do `data-theme`) sem que nada aqui notasse
 * — a amostra original é sobre ESTRUTURA (cabeçalho, tabela...), nunca foi pensada para os blocos
 * Mermaid especificamente, e continua existindo do jeito que estava.
 */
const rotasClaras = [...new Set([...AMOSTRA_CLARA, ...rotasComDiagrama])];

const navegador = await chromium.launch();
const violacoes = [];
const diagramasPorIdioma = { pt: 0, en: 0 };
/** [CT-B01] SVGs REALMENTE conferidos por idioma e tema — contra `blocosNaFontePorIdioma`. */
const svgsConferidos = {
  pt: { light: 0, dark: 0 },
  en: { light: 0, dark: 0 },
};
let conferidas = 0;

for (const [tema, rotasDoTema] of [
  ['dark', todas],
  ['light', rotasClaras],
]) {
  const contexto = await navegador.newContext({ colorScheme: tema });
  const pagina = await contexto.newPage();

  // [CT-B01] "o console não registrou erro" — nada aqui escutava o console antes disto.
  let rotaAtual = null;
  pagina.on('console', (msg) => {
    if (msg.type() === 'error') {
      violacoes.push(`${tema} ${rotaAtual} — console error: ${msg.text().slice(0, 200)}`);
    }
  });

  for (const rota of rotasDoTema) {
    rotaAtual = rota;

    await pagina.goto(`http://localhost:${PORTA}${BASE}${rota}`, { waitUntil: 'domcontentloaded' });
    await pagina.evaluate((t) => document.documentElement.setAttribute('data-theme', t), tema);

    /*
     * Espera o Mermaid terminar de processar TODO bloco da página antes do axe (ADR-02). Página
     * sem `.mermaid` nasce com a condição já satisfeita (zero pendente de zero) e não espera nada.
     */
    try {
      await pagina.waitForFunction(
        () => document.querySelectorAll('pre.mermaid:not([data-processed])').length === 0,
        null,
        { timeout: MERMAID_TIMEOUT_MS },
      );
    } catch {
      violacoes.push(`${tema} ${rota} — mermaid: bloco(s) não terminaram de renderizar em ${MERMAID_TIMEOUT_MS}ms`);
    }

    // A única validação de sintaxe destes blocos: bloco sem <svg>, ou com o elemento de erro
    // ESTRUTURAL que o astro-mermaid 2.1.0 põe no lugar (ver ADR-02, acima) — nunca regex no texto.
    const blocosMermaid = await pagina.evaluate(() =>
      [...document.querySelectorAll('pre.mermaid')].map((bloco, indice) => ({
        indice,
        temSvg: !!bloco.querySelector('svg'),
        // `initMermaid()` do vendor, no catch: cria um <div> e um <strong>Error rendering
        // diagram:</strong> como filho dele — a mesma estrutura, sempre, não importa a
        // mensagem do erro. `:scope > div > strong` casa só essa forma exata.
        temElementoDeErro: !!bloco.querySelector(':scope > div > strong'),
      })),
    );

    for (const erro of blocosMermaid.filter((b) => !b.temSvg || b.temElementoDeErro)) {
      violacoes.push(
        `${tema} ${rota} — mermaid bloco #${erro.indice}: ${erro.temElementoDeErro ? 'elemento de erro do astro-mermaid' : 'sem SVG'}`,
      );
    }

    // [CT-B01] contagem EXATA contra a fonte — "cada página tem exatamente N SVGs", não "≥ 1".
    const nEsperado = blocosNaFonte[rota];
    if (nEsperado !== undefined) {
      if (blocosMermaid.length !== nEsperado) {
        violacoes.push(
          `${tema} ${rota} — ${blocosMermaid.length} SVG(s) renderizado(s), a fonte tem ${nEsperado}`,
        );
      }

      svgsConferidos[rota.startsWith('/en/') ? 'en' : 'pt'][tema] += blocosMermaid.length;
    }

    // Só no escuro (varre TODAS as rotas) — ver o comentário do piso, acima.
    if (tema === 'dark' && blocosMermaid.length > 0) {
      diagramasPorIdioma[rota.startsWith('/en/') ? 'en' : 'pt']++;
    }

    await pagina.addScriptTag({ content: AXE });

    const resultado = await pagina.evaluate(async () => {
      // `serious` e `critical` são o que quebra uso real; `minor` vira ruído numa varredura de 70.
      const r = await window.axe.run(document, {
        resultTypes: ['violations'],
        runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
      });

      return r.violations.map((v) => ({
        id: v.id,
        impacto: v.impact,
        quantos: v.nodes.length,
        exemplo: v.nodes[0]?.html?.slice(0, 120) ?? '',
      }));
    });

    conferidas++;

    for (const v of resultado.filter((v) => v.impacto === 'serious' || v.impacto === 'critical')) {
      violacoes.push(`${tema} ${rota} — ${v.id} (${v.impacto}, ${v.quantos}×): ${v.exemplo}`);
    }
  }

  await contexto.close();
}

/**
 * [CT-B02] O mesmo nó do mesmo diagrama muda de cor quando o LEITOR troca o tema com a página já
 * aberta — não só no carregamento (`@premissa` P-18/Adendo 2, confirmada nesta sessão).
 *
 * O `astro-mermaid` reobserva `data-theme` com um `MutationObserver` e RE-renderiza do zero
 * (`node_modules/astro-mermaid/astro-mermaid-integration.js`, bloco `if (${autoTheme})`: remove
 * `data-processed` de todo `pre.mermaid` e chama `initMermaid()` de novo). O `svg.id` sai TROCADO
 * depois — é o sinal que a espera usa; o mesmo `id` de antes seria "o CSS mudou por cima", não
 * "re-renderizou", e não provaria nada sobre o mutante M2 (tema lido só no carregamento).
 *
 * Medido nesta sessão, na página real: claro `fill: rgb(236, 236, 255)` / texto `rgb(51, 51, 51)`;
 * escuro `fill: rgb(31, 32, 32)` / texto `rgb(204, 204, 204)` — os três pares diferem, como o
 * cenário exige.
 */
async function conferirCtB02() {
  const ROTA_DG01 = '/pt/referencia/arquitetura-em-diagramas/';

  const contexto = await navegador.newContext({ colorScheme: 'light' });
  const pagina = await contexto.newPage();

  await pagina.goto(`http://localhost:${PORTA}${BASE}${ROTA_DG01}`, { waitUntil: 'domcontentloaded' });
  await pagina.evaluate(() => document.documentElement.setAttribute('data-theme', 'light'));

  await pagina.waitForFunction(
    () => document.querySelectorAll('pre.mermaid:not([data-processed])').length === 0,
    null,
    { timeout: MERMAID_TIMEOUT_MS },
  );

  // O PRIMEIRO nó do PRIMEIRO diagrama da página (DG-01) — a forma (`fill`) e o rótulo (`color`).
  const medirPrimeiroNo = () =>
    pagina.evaluate(() => {
      const svg = document.querySelector('pre.mermaid svg');
      const no = svg?.querySelector('.node') ?? null;
      const forma = no?.querySelector('rect, polygon, circle, ellipse, path') ?? null;
      const rotulo = no?.querySelector('.nodeLabel, .label, foreignObject, text') ?? null;

      return {
        svgId: svg?.id ?? null,
        fill: forma ? getComputedStyle(forma).fill : null,
        texto: rotulo ? getComputedStyle(rotulo).color : null,
      };
    });

  const claro = await medirPrimeiroNo();

  await pagina.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));

  try {
    await pagina.waitForFunction(
      (idAntigo) => {
        const svg = document.querySelector('pre.mermaid svg');
        return !!svg && svg.id !== idAntigo;
      },
      claro.svgId,
      { timeout: MERMAID_TIMEOUT_MS },
    );
  } catch {
    violacoes.push('[CT-B02] dark — o DG-01 não re-renderizou depois da troca de tema (M2: tema lido só no carregamento)');
  }

  const escuro = await medirPrimeiroNo();

  await contexto.close();

  console.log(`[CT-B02] DG-01 claro: fill=${claro.fill} texto=${claro.texto}`);
  console.log(`[CT-B02] DG-01 escuro: fill=${escuro.fill} texto=${escuro.texto}`);

  if (claro.fill === null || escuro.fill === null) {
    violacoes.push(
      '[CT-B02] não achou nó/preenchimento no primeiro diagrama da página — seletor de nó desatualizado ou DG-01 ausente',
    );
    return;
  }

  if (claro.fill === escuro.fill) {
    violacoes.push(`[CT-B02] a cor de preenchimento do nó não mudou entre os temas (${claro.fill}) — M1: tema fixo na integração`);
  }

  if (claro.texto === claro.fill) {
    violacoes.push(`[CT-B02] tema claro — texto e preenchimento do nó saem na MESMA cor (${claro.fill}) — M3`);
  }

  if (escuro.texto === escuro.fill) {
    violacoes.push(`[CT-B02] tema escuro — texto e preenchimento do nó saem na MESMA cor (${escuro.fill}) — M3`);
  }
}

await conferirCtB02();

await navegador.close();

/*
 * Piso de população, pelo mesmo motivo do conferidor de links: uma varredura que não achasse rota
 * nenhuma produziria zero violações e diria que está tudo bem sobre o nada.
 */
const PISO = 60;

const idiomasAbaixoDoPiso = ['pt', 'en'].filter((idioma) => diagramasPorIdioma[idioma] < PISO_DIAGRAMAS_POR_IDIOMA);

console.log(`paginas conferidas: ${conferidas} (${todas.length} no escuro, ${rotasClaras.length} no claro)`);
console.log(`paginas com diagrama: pt=${diagramasPorIdioma.pt}, en=${diagramasPorIdioma.en} (piso ${PISO_DIAGRAMAS_POR_IDIOMA} por idioma)`);
console.log(
  `[CT-B01] SVGs conferidos — pt: claro=${svgsConferidos.pt.light} escuro=${svgsConferidos.pt.dark} (fonte=${blocosNaFontePorIdioma.pt}); ` +
    `en: claro=${svgsConferidos.en.light} escuro=${svgsConferidos.en.dark} (fonte=${blocosNaFontePorIdioma.en})`,
);

/*
 * Os três motivos de reprovação são INDEPENDENTES (CR-9): um `else if` esconderia as violações
 * sempre que o piso também falhasse, e o piso de diagrama sempre que o de população falhasse —
 * nenhum vira ruído do outro. Todo motivo que se aplica é impresso, e a saída reprova se
 * QUALQUER um deles falhar.
 */
let reprovado = false;

if (conferidas < PISO) {
  console.error(`REPROVADO — so ${conferidas} paginas conferidas, e o piso e ${PISO}`);
  reprovado = true;
}

if (idiomasAbaixoDoPiso.length > 0) {
  console.error(
    `REPROVADO — piso de paginas com diagrama nao atingido: ${idiomasAbaixoDoPiso
      .map((idioma) => `${idioma}=${diagramasPorIdioma[idioma]}/${PISO_DIAGRAMAS_POR_IDIOMA}`)
      .join(', ')}`,
  );
  reprovado = true;
}

/*
 * [CT-B01] "a soma de SVGs conferidos é 20 por idioma e por tema" — IGUALDADE, não piso. Isto
 * some-se ao `idiomasAbaixoDoPiso` acima (que conta PÁGINAS, não blocos, e só no escuro): uma
 * página que perdesse 1 de 4 blocos ainda contaria como "tem diagrama" ali, e só aqui é pega.
 */
for (const idioma of ['pt', 'en']) {
  for (const tema of ['light', 'dark']) {
    const conferido = svgsConferidos[idioma][tema];
    const esperado = blocosNaFontePorIdioma[idioma];

    if (conferido !== esperado) {
      console.error(`REPROVADO — [CT-B01] SVGs de ${idioma}/${tema}: ${conferido} conferidos, a fonte tem ${esperado}`);
      reprovado = true;
    }
  }
}

if (violacoes.length > 0) {
  console.error(`REPROVADO — ${violacoes.length} violacao(oes) serious/critical:`);
  for (const v of [...new Set(violacoes)]) {
    console.error(`  - ${v}`);
  }
  reprovado = true;
}

if (!reprovado) {
  console.log('OK — nenhuma violacao serious/critical de WCAG 2.1 AA, piso de diagramas atingido.');
}

process.exitCode = reprovado ? 1 : 0;
