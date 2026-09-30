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
 * [CT-B05]/[CT-B06] Também devolve, por rota, o ID `DG-nn` de cada bloco NA ORDEM da fonte — o
 * `%% DG-nn` é um comentário Mermaid (1–2 linhas depois da cerca: a linha do tipo do diagrama,
 * `flowchart`/`sequenceDiagram`/`stateDiagram-v2`, e então o comentário), invisível no SVG
 * renderizado. É a ÚNICA forma de nomear qual diagrama caiu abaixo do piso de fonte — o DOM não
 * carrega esse identificador, só a ORDEM dos `pre.mermaid` na página, que é a mesma ordem da fonte
 * (o Starlight não reordena bloco).
 *
 * @returns {{ porRota: Record<string, number>, dgsPorRota: Record<string, Array<string|null>> }}
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
  /** @type {Record<string, Array<string|null>>} */
  const dgsPorRota = {};

  for (const idioma of ['pt', 'en']) {
    for (const arquivo of coletarMarkdown(join(DOCS, idioma))) {
      const linhas = readFileSync(arquivo, 'utf8').split('\n');

      // Só a cerca ```` ```mermaid ```` sozinha na linha — a mesma forma que o `astro-mermaid`
      // exige para reconhecer o bloco (nunca com atributos colados, ex. ```` ```mermaid title=x ````).
      const dgs = [];
      for (let i = 0; i < linhas.length; i++) {
        if (!/^```mermaid\s*$/.test(linhas[i])) {
          continue;
        }

        const dg = linhas
          .slice(i + 1, i + 3)
          .map((l) => l.match(/^%%\s*(DG-\d+)/)?.[1])
          .find(Boolean);

        dgs.push(dg ?? null);
      }

      if (dgs.length === 0) {
        continue;
      }

      // O caminho em disco vira rota: tira o prefixo de `docs/`, troca `\` por `/` (Windows) e
      // `.md` pela barra final; `index.md` é a própria pasta (mesma convenção do Starlight).
      let relativo = arquivo.slice(DOCS.length).replace(/\\/g, '/').replace(/\.md$/, '');

      if (relativo.endsWith('/index')) {
        relativo = relativo.slice(0, -'/index'.length);
      }

      const rota = `${relativo}/`;
      porRota[rota] = dgs.length;
      dgsPorRota[rota] = dgs;
    }
  }

  return { porRota, dgsPorRota };
}

const { porRota: blocosNaFonte, dgsPorRota } = blocosMermaidNaFonte();
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

/**
 * [CT-B05]/[CT-B06] Mede, para cada `pre.mermaid` da página aberta: a fonte efetiva mínima (fonte
 * computada × escala do `viewBox`, o menor texto do SVG — não o primeiro) e os dados de layout que
 * decidem se ele passa da coluna e, se passar, se rola DENTRO do próprio bloco. Roda dentro do
 * browser (o Playwright serializa a função para `page.evaluate`), por isso não pode fechar sobre
 * nada deste módulo.
 *
 * @returns {Array<{semSvg: true} | {semSvg: false, fonteEfetivaMinima: number|null, larguraSvg: number, blocoClientWidth: number, containerRolavel: {clientWidth: number, scrollWidth: number}|null}>}
 */
function medirDiagramasDaPagina() {
  return [...document.querySelectorAll('pre.mermaid')].map((bloco) => {
    const svg = bloco.querySelector('svg');

    if (!svg) {
      return { semSvg: true };
    }

    const retangulo = svg.getBoundingClientRect();
    const vb = svg.viewBox && svg.viewBox.baseVal;
    const escala = vb && vb.width ? retangulo.width / vb.width : 1;

    // O Mermaid 11 desenha rótulo de fluxo em HTML dentro de `foreignObject` e mensagem de
    // sequência/nó em `<text>`/`<tspan>` (05, Seletores) — os três entram na conta, e é o MENOR
    // texto que decide (mutante M5 do CT-B05: ler só o primeiro deixaria passar o rótulo menor).
    let menorFonte = Infinity;
    for (const el of svg.querySelectorAll('text, tspan, foreignObject *')) {
      if (!el.textContent || el.textContent.trim() === '') {
        continue;
      }

      const px = parseFloat(getComputedStyle(el).fontSize);

      if (Number.isFinite(px) && px < menorFonte) {
        menorFonte = px;
      }
    }

    // [CT-B06] o contêiner rolável fica DENTRO do bloco: a busca sobe do próprio `pre.mermaid`
    // até quem o envolve (05, passo 1) e para aí — nunca até um ancestral que embrulhe outros
    // diagramas (M3: "rolagem posta num ancestral de todos os blocos").
    let candidato = bloco;
    let containerRolavel = null;

    for (let profundidade = 0; candidato && profundidade <= 1; profundidade++) {
      const overflowX = getComputedStyle(candidato).overflowX;

      if (overflowX === 'auto' || overflowX === 'scroll') {
        containerRolavel = { clientWidth: candidato.clientWidth, scrollWidth: candidato.scrollWidth };
        break;
      }

      candidato = candidato.parentElement;
    }

    return {
      semSvg: false,
      fonteEfetivaMinima: Number.isFinite(menorFonte) ? menorFonte * escala : null,
      larguraSvg: retangulo.width,
      blocoClientWidth: bloco.clientWidth,
      containerRolavel,
    };
  });
}

/** [CT-B05] piso de fonte efetiva — RQ-47/RQ-49, sem tolerância (Q?8 do `04`). */
const PISO_FONTE_EFETIVA_PX = 12;

/** Folga de arredondamento de sub-pixel do layout do navegador — nunca de tolerância de negócio. */
const EPSILON_PX = 1;

/**
 * [CT-B05]/[CT-B06] — QA-06/Adendo 6 (RQ-47..RQ-49). Mede, na MESMA passada, as 5 combinações
 * reais do `05` (janela × idioma, com troca de tema onde o Gherkin pede) e soma as duas regras:
 * nenhum texto de diagrama abaixo do piso de 12px, e o diagrama mais largo que a coluna rola
 * DENTRO do próprio bloco (nunca a página). Hoje nasce vermelho nas duas frentes: o Mermaid encolhe
 * o SVG para caber (13/20 diagramas abaixo de 10px a 1280×900, QA-06) e por isso NENHUM passa da
 * coluna — a âncora de não vácuo do CT-B06 falha sozinha.
 */
async function conferirLegibilidadeERolagem() {
  const COMBOS = [
    { janela: '1280x900', largura: 1280, altura: 900, idioma: 'pt', tema: 'claro', trocarTema: false },
    { janela: '1280x900', largura: 1280, altura: 900, idioma: 'pt', tema: 'escuro (trocado)', trocarTema: true },
    { janela: '1280x900', largura: 1280, altura: 900, idioma: 'en', tema: 'claro', trocarTema: false },
    { janela: '390x844', largura: 390, altura: 844, idioma: 'pt', tema: 'claro', trocarTema: false },
    { janela: '390x844', largura: 390, altura: 844, idioma: 'en', tema: 'escuro (trocado)', trocarTema: true },
  ];

  let maisLargoNoCelular = 0;
  const detalhesAbaixoDoPiso = [];
  const detalhesDeRolagem = [];
  const detalhesDePagina = [];

  for (const combo of COMBOS) {
    const rotasDoIdioma = rotasComDiagrama.filter((r) => (r.startsWith('/en/') ? 'en' : 'pt') === combo.idioma);
    const contexto = await navegador.newContext({
      viewport: { width: combo.largura, height: combo.altura },
      colorScheme: 'light',
    });
    const pagina = await contexto.newPage();

    let medidosNestaCombinacao = 0;
    let menorFonteDaCombinacao = Infinity;
    let dgDaMenorFonte = null;

    for (const rota of rotasDoIdioma) {
      await pagina.goto(`http://localhost:${PORTA}${BASE}${rota}`, { waitUntil: 'domcontentloaded' });
      await pagina.evaluate(() => document.documentElement.setAttribute('data-theme', 'light'));
      await pagina.waitForFunction(
        () => document.querySelectorAll('pre.mermaid:not([data-processed])').length === 0,
        null,
        { timeout: MERMAID_TIMEOUT_MS },
      );

      if (combo.trocarTema) {
        // [P-18, como o CT-B02] espera o SVG NOVO — o `astro-mermaid` redesenha do zero na troca.
        const idsAntes = await pagina.evaluate(() =>
          [...document.querySelectorAll('pre.mermaid svg')].map((svg) => svg.id),
        );

        await pagina.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));

        try {
          await pagina.waitForFunction(
            (antes) => {
              const agora = [...document.querySelectorAll('pre.mermaid svg')].map((svg) => svg.id);
              return agora.length === antes.length && agora.every((id, i) => id && id !== antes[i]);
            },
            idsAntes,
            { timeout: MERMAID_TIMEOUT_MS },
          );
        } catch {
          violacoes.push(`[CT-B05] ${combo.idioma} ${combo.janela} ${rota} — nao redesenhou apos a troca de tema (M2)`);
        }
      }

      const [medidas, layoutDaPagina] = await Promise.all([
        pagina.evaluate(medirDiagramasDaPagina),
        pagina.evaluate(() => ({
          scrollWidth: document.documentElement.scrollWidth,
          innerWidth: window.innerWidth,
        })),
      ]);

      if (layoutDaPagina.scrollWidth > layoutDaPagina.innerWidth + EPSILON_PX) {
        detalhesDePagina.push(
          `${combo.idioma} ${combo.janela} ${rota} — pagina rola na horizontal (${layoutDaPagina.scrollWidth} > ${layoutDaPagina.innerWidth})`,
        );
      }

      const dgs = dgsPorRota[rota] ?? [];

      medidas.forEach((m, indice) => {
        if (m.semSvg) {
          return;
        }

        medidosNestaCombinacao++;
        const dg = dgs[indice] ?? `bloco #${indice}`;

        if (m.fonteEfetivaMinima !== null && m.fonteEfetivaMinima < menorFonteDaCombinacao) {
          menorFonteDaCombinacao = m.fonteEfetivaMinima;
          dgDaMenorFonte = dg;
        }

        if (m.fonteEfetivaMinima === null || m.fonteEfetivaMinima < PISO_FONTE_EFETIVA_PX) {
          detalhesAbaixoDoPiso.push(
            `${dg} (${rota}, ${combo.idioma} ${combo.janela} ${combo.tema}): ${m.fonteEfetivaMinima?.toFixed(1) ?? 'sem texto medido'} px`,
          );
        }

        const maisLargoQueColuna = m.larguraSvg > m.blocoClientWidth + EPSILON_PX;

        if (maisLargoQueColuna) {
          if (combo.janela === '390x844') {
            maisLargoNoCelular++;
          }

          if (!m.containerRolavel) {
            detalhesDeRolagem.push(`${dg} (${rota}, ${combo.idioma} ${combo.janela}) — sem conteiner rolavel dentro do bloco`);
          } else if (m.containerRolavel.scrollWidth < m.larguraSvg - EPSILON_PX) {
            detalhesDeRolagem.push(`${dg} (${rota}, ${combo.idioma} ${combo.janela}) — conteiner nao alcanca a borda direita do SVG`);
          }
        }
      });
    }

    console.log(
      `[CT-B05] fonte efetiva minima - ${combo.idioma} ${combo.janela} ${combo.tema}: ` +
        `${Number.isFinite(menorFonteDaCombinacao) ? menorFonteDaCombinacao.toFixed(1) : 'n/a'} px (${dgDaMenorFonte}), ` +
        `${medidosNestaCombinacao} SVGs medidos`,
    );

    const esperado = blocosNaFontePorIdioma[combo.idioma];
    if (medidosNestaCombinacao !== esperado) {
      violacoes.push(
        `[CT-B05] ${combo.idioma} ${combo.janela} ${combo.tema} — ${medidosNestaCombinacao} SVG(s) medido(s), a fonte tem ${esperado}`,
      );
    }

    await contexto.close();
  }

  for (const msg of detalhesAbaixoDoPiso) {
    violacoes.push(`[CT-B05] abaixo do piso de ${PISO_FONTE_EFETIVA_PX}px — ${msg}`);
  }

  for (const msg of detalhesDeRolagem) {
    violacoes.push(`[CT-B06] ${msg}`);
  }

  for (const msg of detalhesDePagina) {
    violacoes.push(`[CT-B06] ${msg}`);
  }

  // [CT-B06] a âncora de não vácuo (05: "sem ela, 'todo SVG mais largo que a coluna rola' vale no
  // vazio, e vale hoje — o Mermaid encolhe todos"): sem isto, zero diagramas rolando passaria como
  // "tudo rola", porque a implicação acima nunca tem antecedente verdadeiro.
  if (maisLargoNoCelular === 0) {
    violacoes.push(
      '[CT-B06] ancora de nao vacuo — nenhum diagrama ficou mais largo que a coluna em 390x844 (o Mermaid encolhe todos; nada prova a rolagem dentro do bloco)',
    );
  }

  console.log(
    `[CT-B06] rolagem no bloco - celular (390x844): ${maisLargoNoCelular} diagramas maiores que a coluna, ` +
      `pagina sem rolagem: ${detalhesDePagina.length === 0}`,
  );
}

/**
 * [CT-B05]/[CT-B06] Controles sintéticos (05, tabelas de Exemplos "de controle") — provam que o
 * MEDIDOR está certo antes de confiar nele sobre os 20 diagramas reais: a escala do `viewBox` entra
 * na conta, vale o MENOR texto do SVG (não o primeiro), a borda de 12px exata é aceita sem
 * tolerância, e as duas formas de vazamento de rolagem (sem contêiner, e com contêiner que corta)
 * reprovam.
 */
async function conferirControlesDeLegibilidadeERolagem() {
  const html = (corpo) => `<!doctype html><html><body>${corpo}</body></html>`;

  const contextoDesktop = await navegador.newContext({ viewport: { width: 1280, height: 900 } });
  const paginaDesktop = await contextoDesktop.newPage();

  // Controle 1: viewBox 5x mais largo que o desenhado (500 -> renderiza a 100px) com texto de 16px
  // => 16 * 0.2 = 3.2px. "a escala entra na conta" (05).
  await paginaDesktop.setContent(html(
    '<pre class="mermaid" data-processed="true"><svg viewBox="0 0 500 100" style="width:100px;height:20px" xmlns="http://www.w3.org/2000/svg"><text style="font-size:16px" x="0" y="20">controle</text></svg></pre>',
  ));
  const [controleEscala] = await paginaDesktop.evaluate(medirDiagramasDaPagina);

  // Controle 2: escala 1, com um texto de 16px e outro de 8px — o mínimo tem de vencer o primeiro.
  await paginaDesktop.setContent(html(
    '<pre class="mermaid" data-processed="true"><svg viewBox="0 0 200 60" style="width:200px;height:60px" xmlns="http://www.w3.org/2000/svg">'
      + '<text style="font-size:16px" x="0" y="20">grande</text><text style="font-size:8px" x="0" y="40">pequeno</text></svg></pre>',
  ));
  const [controleMenorTexto] = await paginaDesktop.evaluate(medirDiagramasDaPagina);

  // Controle 3: escala 1, exatamente no piso (12px) — aceita, sem tolerância.
  await paginaDesktop.setContent(html(
    '<pre class="mermaid" data-processed="true"><svg viewBox="0 0 200 30" style="width:200px;height:30px" xmlns="http://www.w3.org/2000/svg"><text style="font-size:12px" x="0" y="20">piso</text></svg></pre>',
  ));
  const [controleBordaDoPiso] = await paginaDesktop.evaluate(medirDiagramasDaPagina);

  await contextoDesktop.close();

  console.log(
    `[CT-B05] controles - viewBox 5x: ${controleEscala.fonteEfetivaMinima?.toFixed(1)} px; ` +
      `menor de dois textos: ${controleMenorTexto.fonteEfetivaMinima?.toFixed(1)} px; ` +
      `borda do piso: ${controleBordaDoPiso.fonteEfetivaMinima?.toFixed(1)} px`,
  );

  if (Math.abs((controleEscala.fonteEfetivaMinima ?? 0) - 3.2) > 0.1) {
    violacoes.push(`[CT-B05] controle (viewBox 5x mais largo) — esperava reprovar nomeando 3.2px, mediu ${controleEscala.fonteEfetivaMinima}`);
  }

  if (Math.abs((controleMenorTexto.fonteEfetivaMinima ?? 0) - 8) > 0.1) {
    violacoes.push(`[CT-B05] controle (menor dos dois textos) — esperava reprovar nomeando 8px, mediu ${controleMenorTexto.fonteEfetivaMinima}`);
  }

  if (!(controleBordaDoPiso.fonteEfetivaMinima !== null && controleBordaDoPiso.fonteEfetivaMinima >= 12 && controleBordaDoPiso.fonteEfetivaMinima < 12.5)) {
    violacoes.push(`[CT-B05] controle (borda do piso, 12px) — esperava aceitar, mediu ${controleBordaDoPiso.fonteEfetivaMinima}`);
  }

  const contextoCelular = await navegador.newContext({ viewport: { width: 390, height: 844 } });
  const paginaCelular = await contextoCelular.newPage();

  // Controle 4: SVG de 2000px sem contêiner rolável — a página inteira deve vazar (rolar na
  // horizontal), porque nada limita a largura.
  await paginaCelular.setContent(html(
    '<pre class="mermaid" data-processed="true"><svg viewBox="0 0 2000 100" style="width:2000px;height:100px" xmlns="http://www.w3.org/2000/svg"><text style="font-size:16px" x="0" y="20">largo</text></svg></pre>',
  ));
  const paginaVazaScroll = await paginaCelular.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);

  if (!paginaVazaScroll) {
    violacoes.push('[CT-B06] controle (sem conteiner) — esperava a pagina rolar na horizontal, e nao rolou');
  }

  // Controle 5: SVG de 2000px num contêiner com `overflow: hidden` — não é "auto"/"scroll", então
  // o medidor não deve achá-lo rolável, e a borda direita do SVG conta como inalcançável.
  await paginaCelular.setContent(html(
    '<div style="overflow-x:hidden;width:300px"><pre class="mermaid" data-processed="true"><svg viewBox="0 0 2000 100" style="width:2000px;height:100px" xmlns="http://www.w3.org/2000/svg"><text style="font-size:16px" x="0" y="20">largo</text></svg></pre></div>',
  ));
  const [controleOverflowHidden] = await paginaCelular.evaluate(medirDiagramasDaPagina);

  if (controleOverflowHidden.containerRolavel !== null) {
    violacoes.push('[CT-B06] controle (overflow:hidden) — o medidor achou conteiner rolavel onde nao deveria');
  }

  await contextoCelular.close();

  console.log(
    `[CT-B06] controles - sem conteiner rola a pagina: ${paginaVazaScroll}; ` +
      `overflow:hidden sem conteiner rolavel: ${controleOverflowHidden.containerRolavel === null}`,
  );
}

await conferirControlesDeLegibilidadeERolagem();
await conferirLegibilidadeERolagem();

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
