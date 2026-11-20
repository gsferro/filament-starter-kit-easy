/**
 * Confere o mapa interativo do kit no site construído, num navegador real — ADR-06 e D17 da wiki
 * `mapa-interativo-do-kit`.
 *
 * Mora fora do `verifica-acessibilidade.mjs` (D17): aquele conferidor já carrega os `[CT-B01]` e
 * `[CT-B02]` da wiki `diagramas-da-arquitetura`, e os CT-B desta wiki também começam em 01 — o
 * mesmo ID no mesmo arquivo nomearia dois cenários.
 *
 * AMOSTRA, e não varredura: um HTML do mapa por tipo de diagrama, por idioma e por tema, mais o
 * comparativo no tema em que ele abre (escuro), que ignora o esquema de cor. Os HTML de um mesmo
 * tipo têm o mesmo runtime e os mesmos estilos do archify; o que muda entre eles já está nos dois
 * defeitos tolerados abaixo.
 *
 * A amostra sai dos ARQUIVOS DE DIAGRAMA (`mapa/diagramas/`), e não de uma varredura do `dist/`:
 * um diagrama que o build deixou de gerar reprova aqui, em vez de sumir da amostra.
 *
 * Reprova qualquer violação `serious`/`critical` de WCAG 2.1 AA, qualquer `console.error` e
 * qualquer erro de página, EXCETO exatamente dois defeitos do archify, reconhecidos pela regra E
 * pelo elemento — nunca pelo texto do seletor que o axe devolve, que muda com o tipo:
 *  1. `nested-interactive` no `<svg>` raiz do diagrama (`role="img"` com filhos focáveis);
 *  2. `color-contrast` na contagem de grupo do índice de nós (`.node-outline-heading > small`),
 *     2,5:1 nos dois temas.
 * Uma versão nova do archify que mude o elemento de um deles reprova aqui — e é o desejado: a
 * troca de versão (ADR-02) passa a exigir rever esta tolerância.
 */
import { chromium } from 'playwright';
import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { join, resolve } from 'node:path';

const PORTA = process.argv[2] || '4321';
const BASE = (process.env.DOCS_BASE || '').replace(/\/+$/, '');
const DIST = resolve('dist');
const DIAGRAMAS = resolve('mapa/diagramas');
const AXE = readFileSync(resolve('node_modules/axe-core/axe.min.js'), 'utf8');
const RENDER_TIMEOUT_MS = 15_000;

const diagramas = readdirSync(DIAGRAMAS)
  .filter((nome) => nome.endsWith('.json'))
  .sort()
  .map((nome) => JSON.parse(readFileSync(join(DIAGRAMAS, nome), 'utf8')));

/** O primeiro diagrama de cada tipo, em ordem de nome de arquivo. */
const representantes = new Map();
for (const { tipo, slug } of diagramas) {
  if (!representantes.has(tipo)) representantes.set(tipo, slug);
}

const amostra = [...representantes].flatMap(([tipo, slug]) =>
  ['pt', 'en'].flatMap((idioma) =>
    (tipo === 'compare' ? ['dark'] : ['light', 'dark']).map((tema) => ({ tipo, slug, idioma, tema })),
  ),
);

const violacoes = [];
const toleradas = { 'nested-interactive': 0, 'color-contrast': 0 };
let conferidos = 0;

const navegador = await chromium.launch();

for (const { tipo, slug, idioma, tema } of amostra) {
  const rotulo = `${tipo} ${idioma}/${slug} ${tema}`;
  if (!existsSync(join(DIST, 'mapa', idioma, `${slug}.html`))) {
    violacoes.push(`${rotulo} — o build não gerou dist/mapa/${idioma}/${slug}.html`);
    continue;
  }

  const contexto = await navegador.newContext({ colorScheme: tema });
  const pagina = await contexto.newPage();
  pagina.on('console', (mensagem) => {
    if (mensagem.type() === 'error') violacoes.push(`${rotulo} — console error: ${mensagem.text().slice(0, 200)}`);
  });
  pagina.on('pageerror', (erro) => violacoes.push(`${rotulo} — erro de página: ${erro.message.slice(0, 200)}`));

  await pagina.goto(`http://localhost:${PORTA}${BASE}/mapa/${idioma}/${slug}.html`, { waitUntil: 'load' });
  try {
    await pagina.waitForFunction(() => document.querySelector('svg[role="img"]'), null, { timeout: RENDER_TIMEOUT_MS });
  } catch {
    violacoes.push(`${rotulo} — nenhum <svg role="img"> em ${RENDER_TIMEOUT_MS} ms`);
    await contexto.close();
    continue;
  }
  // Duas pinturas: o visualizador termina de montar o painel lateral depois do primeiro quadro.
  await pagina.evaluate(() => new Promise((pronto) => requestAnimationFrame(() => requestAnimationFrame(pronto))));

  // O comparativo abre no escuro, de propósito, e ignora o esquema de cor (D8).
  const temaAplicado = await pagina.evaluate(() => document.documentElement.dataset.theme);
  if (tipo !== 'compare' && temaAplicado !== tema) {
    violacoes.push(`${rotulo} — o visualizador abriu no tema ${temaAplicado}, e o pedido foi ${tema}`);
  }

  await pagina.addScriptTag({ content: AXE });
  const resultado = await pagina.evaluate(async () => {
    const raiz = document.getElementById('archify-diagram-title')?.closest('svg') ?? null;
    const tolerada = (regra, elemento) =>
      (regra === 'nested-interactive' && elemento === raiz) ||
      (regra === 'color-contrast' && elemento?.matches?.('.node-outline-heading > small') === true);

    const r = await window.axe.run(document, {
      resultTypes: ['violations'],
      elementRef: true,
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
    });

    return r.violations
      .filter((v) => v.impact === 'serious' || v.impact === 'critical')
      .map((v) => {
        const restantes = v.nodes.filter((no) => !tolerada(v.id, no.element));
        return {
          regra: v.id,
          impacto: v.impact,
          toleradas: v.nodes.length - restantes.length,
          restantes: restantes.map((no) => (no.html || '').slice(0, 120)),
        };
      });
  });

  for (const v of resultado) {
    if (v.toleradas > 0) toleradas[v.regra] += v.toleradas;
    if (v.restantes.length > 0) {
      violacoes.push(`${rotulo} — ${v.regra} (${v.impacto}, ${v.restantes.length}×): ${v.restantes[0]}`);
    }
  }

  conferidos++;
  await contexto.close();
}

await navegador.close();

console.log(`mapa: ${conferidos} HTML conferidos de ${amostra.length} na amostra (${representantes.size} tipos)`);
console.log(
  `tolerados (ADR-06): nested-interactive no <svg> raiz ${toleradas['nested-interactive']}×, ` +
    `color-contrast na contagem do índice ${toleradas['color-contrast']}×`,
);

/*
 * Piso de população, como nos outros conferidores do site: sem diagrama nenhum, a amostra seria
 * vazia e o conferidor diria que está tudo bem sobre o nada.
 */
let reprovado = false;
if (amostra.length === 0) {
  console.error('REPROVADO — mapa/diagramas/ não tem diagrama nenhum');
  reprovado = true;
}
if (violacoes.length > 0) {
  console.error(`REPROVADO — ${violacoes.length} problema(s) no mapa:`);
  for (const v of [...new Set(violacoes)]) console.error(`  - ${v}`);
  reprovado = true;
}
if (!reprovado) console.log('OK — mapa sem violação serious/critical fora das duas toleradas, e sem erro no console.');

process.exitCode = reprovado ? 1 : 0;
