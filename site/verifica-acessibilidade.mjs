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
 * Uso: `node verifica-acessibilidade.mjs [porta]`
 */
import { chromium } from 'playwright';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, resolve } from 'node:path';

const PORTA = process.argv[2] || '4321';
const DIST = resolve('dist');
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
const AMOSTRA_CLARA = ['/pt/', '/en/', '/pt/recursos/configuracoes-do-kit/', '/pt/autenticacao/'];

const navegador = await chromium.launch();
const violacoes = [];
let conferidas = 0;

for (const [tema, rotasDoTema] of [
  ['dark', todas],
  ['light', AMOSTRA_CLARA],
]) {
  const contexto = await navegador.newContext({ colorScheme: tema });
  const pagina = await contexto.newPage();

  for (const rota of rotasDoTema) {
    await pagina.goto(`http://localhost:${PORTA}${BASE}${rota}`, { waitUntil: 'domcontentloaded' });
    await pagina.evaluate((t) => document.documentElement.setAttribute('data-theme', t), tema);
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

await navegador.close();

/*
 * Piso de população, pelo mesmo motivo do conferidor de links: uma varredura que não achasse rota
 * nenhuma produziria zero violações e diria que está tudo bem sobre o nada.
 */
const PISO = 60;

console.log(`paginas conferidas: ${conferidas} (${todas.length} no escuro, ${AMOSTRA_CLARA.length} no claro)`);

if (conferidas < PISO) {
  console.error(`REPROVADO — so ${conferidas} paginas conferidas, e o piso e ${PISO}`);
  process.exitCode = 1;
} else if (violacoes.length > 0) {
  console.error(`REPROVADO — ${violacoes.length} violacao(oes) serious/critical:`);
  for (const v of [...new Set(violacoes)]) {
    console.error(`  - ${v}`);
  }
  process.exitCode = 1;
} else {
  console.log('OK — nenhuma violacao serious/critical de WCAG 2.1 AA.');
}
