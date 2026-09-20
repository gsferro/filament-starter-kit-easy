/**
 * Capturas do spike para avaliação visual.
 *
 * Existe porque cor não se confere por árvore de acessibilidade: texto de baixo contraste está
 * no DOM e na árvore, e só está ilegível. O oráculo é olhar — e olhar nos dois temas, porque o
 * modo de falha clássico é o valor que funciona num e desaparece no outro.
 *
 * Uso: node capturas.mjs [porta] [prefixo]
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const PORTA = process.argv[2] || '4321';
const PREFIXO = process.argv[3] || 'starlight';
const SAIDA = process.env.SAIDA || './capturas';

const PAGINAS = [
  ['landing', '/pt/'],
  ['densa', '/pt/recursos/configuracoes-do-kit/'],
  ['secao', '/pt/autenticacao/'],
];

mkdirSync(SAIDA, { recursive: true });

const navegador = await chromium.launch();

for (const tema of ['dark', 'light']) {
  const contexto = await navegador.newContext({
    colorScheme: tema,
    viewport: { width: 1440, height: 1000 },
    deviceScaleFactor: 1,
  });

  const pagina = await contexto.newPage();

  for (const [nome, rota] of PAGINAS) {
    await pagina.goto(`http://localhost:${PORTA}${rota}`, { waitUntil: 'networkidle' });
    // O tema do Starlight vive no localStorage; `colorScheme` do contexto só cobre o auto.
    await pagina.evaluate((t) => document.documentElement.setAttribute('data-theme', t), tema);
    await pagina.waitForTimeout(250);
    await pagina.screenshot({ path: `${SAIDA}/${PREFIXO}-${nome}-${tema}.png`, fullPage: false });
    console.log(`${PREFIXO}-${nome}-${tema}.png`);
  }

  await contexto.close();
}

// Celular, só no escuro: é onde a barra lateral vira gaveta e o contraste costuma escapar.
const celular = await navegador.newContext({
  colorScheme: 'dark',
  viewport: { width: 390, height: 844 },
});
const p = await celular.newPage();
await p.goto(`http://localhost:${PORTA}/pt/recursos/configuracoes-do-kit/`, {
  waitUntil: 'networkidle',
});
await p.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));
await p.screenshot({ path: `${SAIDA}/${PREFIXO}-celular-dark.png` });
console.log(`${PREFIXO}-celular-dark.png`);

await navegador.close();
