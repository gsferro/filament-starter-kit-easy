/**
 * Confere os links internos do site construído: a página existe, e a âncora existe dentro dela.
 *
 * Existe porque a migração muda a FORMA da URL — o Jekyll publica `convites.html` e o Starlight
 * publica `convites/` —, e link relativo que sobreviveu à conversão por acidente aponta para
 * página que em alguns casos existe. Conferir no HTML construído é o único oráculo que não
 * depende de eu ter convertido certo.
 *
 * Âncora entra junto porque a auditoria de 2026-09-19 achou 25 âncoras mortas nos readmes — o
 * modo de falha conhecido deste repositório não é o link para lugar nenhum, é o link para a
 * página certa e o pedaço errado dela.
 */
import { readdirSync, statSync, readFileSync, existsSync } from 'node:fs';
import { join, resolve, posix } from 'node:path';

const DIST = resolve('dist');

/*
 * O PREFIXO BASE, e por que ele precisa ser descontado aqui.
 *
 * O site é de projeto, não de usuário: ele mora em `gsferro.github.io/filament-starter-kit-easy/`,
 * e o build de produção recebe `DOCS_BASE=/filament-starter-kit-easy`. Com isso, todo link interno
 * do HTML sai como `href="/filament-starter-kit-easy/pt/..."` — mas o `dist/` **não** tem um
 * diretório `filament-starter-kit-easy/`: o base é prefixo de URL, não de caminho em disco.
 *
 * Sem descontar, este conferidor acusa TODOS os links internos como inexistentes. Foi o que
 * aconteceu no primeiro deploy real: o job reprovou com centenas de falsos positivos, e o site não
 * subiu. O guarda falhou FECHADO, que é a direção certa — mas por um defeito dele, não do site.
 *
 * A prévia local não passa `DOCS_BASE` e serve na raiz, então o bug era invisível fora da CI.
 */
const BASE = (process.env.DOCS_BASE || '').replace(/\/+$/, '');

/** O caminho absoluto carrega o prefixo base? */
const temBase = (caminho) =>
  BASE === '' || caminho === BASE || caminho.startsWith(BASE + '/');

/** Tira o prefixo de URL para chegar ao caminho real dentro do `dist/`. */
const semBase = (caminho) =>
  BASE !== '' && temBase(caminho) ? caminho.slice(BASE.length) || '/' : caminho;

const paginas = (dir) =>
  readdirSync(dir).flatMap((nome) => {
    const caminho = join(dir, nome);
    if (statSync(caminho).isDirectory()) return paginas(caminho);
    return nome.endsWith('.html') ? [caminho] : [];
  });

const ESTATICOS = /\.(css|js|png|webp|svg|xml|json|ico|txt|woff2?)$/;

const quebrados = [];
const problemas = [];
const vistos = new Set();
let conferidos = 0;

for (const arquivo of paginas(DIST)) {
  const html = readFileSync(arquivo, 'utf8');

  /*
   * O extrator pega TODO `href`, e não só os absolutos — foi o que esta versão mudou.
   *
   * A forma correta de link interno no conteúdo é a RELATIVA (`../../recursos/x/`), porque ela
   * resolve contra a página corrente e sobrevive a qualquer `base`. Só que a versão anterior
   * casava `href="/…"`: no dia em que os 54 links do markdown passaram de absolutos para
   * relativos, eles sairiam da varredura inteira e o conferidor ficaria verde por não olhar —
   * trocando um falso negativo por um ponto cego maior. Os dois formatos entram.
   */
  for (const casou of html.matchAll(/href="([^"]+)"/g)) {
    const bruto = casou[1];

    // `http:`, `https:`, `mailto:`, `tel:` e `//host/…`: não é rota deste site.
    if (/^(?:[a-z][a-z0-9+.\-]*:|\/\/)/i.test(bruto)) continue;

    const [semQuery] = bruto.split('?');
    const [alvoBruto, ancora] = semQuery.split('#');

    // Âncora pura (`#_top`) aponta para a própria página; não há rota para conferir.
    if (!alvoBruto || ESTATICOS.test(alvoBruto)) continue;

    conferidos++;

    /*
     * O caminho em disco, e é aqui que os dois formatos convergem.
     *
     * Absoluto: precisa carregar o `base`, e o que sobra depois de descontá-lo é o caminho dentro
     * do `dist/`. Relativo: resolve contra o diretório da página que o contém — o mesmo que o
     * navegador faz. Os dois chegam a uma chave única, então a deduplicação passa a ser por
     * DESTINO RESOLVIDO: o mesmo `../x/` em duas páginas diferentes vai para lugares diferentes,
     * e dedupar pelo texto cru esconderia o segundo.
     */
    // No Windows o caminho vem com `\`; o resolvedor de URL trabalha em `/`.
    const origem = arquivo.replace(DIST, '').split(/[\\/]/).join('/');

    let emDisco;

    if (alvoBruto.startsWith('/')) {
      if (!temBase(alvoBruto)) {
        quebrados.push(`${bruto}  — caminho absoluto SEM o prefixo base '${BASE}': em producao isto sai do site do projeto e da 404 (ex.: ${origem})`);
        continue;
      }
      emDisco = semBase(alvoBruto);
    } else {
      emDisco = posix.resolve(posix.dirname(origem), alvoBruto);
    }

    const chave = emDisco + (ancora ? '#' + ancora : '');
    if (vistos.has(chave)) continue;
    vistos.add(chave);

    const achado = [
      join(DIST, emDisco, 'index.html'),
      join(DIST, emDisco),
      join(DIST, emDisco + '.html'),
    ].find(existsSync);

    if (!achado) {
      quebrados.push(`${bruto} -> ${emDisco}  — pagina inexistente (ex.: ${origem})`);
      continue;
    }

    /*
     * A âncora chega percent-encoded no HTML (`#reten%C3%A7%C3%A3o-...`) porque o título tem
     * acento, e o `id` do destino é o texto cru em UTF-8. Comparar as duas formas acusa toda
     * âncora acentuada — foi o que este conferidor fez na primeira rodada, com um falso positivo.
     */
    const procurada = decodeURIComponent(ancora ?? '');

    if (ancora && !readFileSync(achado, 'utf8').includes(`id="${procurada}"`)) {
      quebrados.push(`${bruto} -> ${chave}  — ancora morta (ex.: ${origem})`);
    }
  }
}

/*
 * Piso de população, e ele é o que separa este conferidor de um enfeite.
 *
 * Sem ele, um `dist/` vazio — base do loader quebrada, build que não rodou, diretório renomeado —
 * produz "conferidos: 0 / quebrados: 0" e o guarda diz que está tudo bem sobre o nada. A revisão
 * adversarial apontou exatamente isso: o documento delega a este script as três afirmações mais
 * caras da feature, e ele ficava verde sobre conjunto vazio.
 */
const PISO_DE_PAGINAS = 60;
const PISO_DE_LINKS = 1000;

const paginasLidas = paginas(DIST).length;

if (paginasLidas < PISO_DE_PAGINAS) {
  problemas.push(`o build tem ${paginasLidas} paginas, e o piso e ${PISO_DE_PAGINAS} — a varredura olhou o lugar errado, ou o build saiu vazio`);
}

if (conferidos < PISO_DE_LINKS) {
  problemas.push(`so ${conferidos} links internos foram conferidos, e o piso e ${PISO_DE_LINKS} — o extrator nao extraiu`);
}

console.log(`paginas no build: ${paginasLidas}`);
console.log(`links internos conferidos: ${conferidos} (${vistos.size} distintos)`);
console.log(
  quebrados.length ? `QUEBRADOS (${quebrados.length}):\n` + quebrados.join('\n') : 'quebrados: 0',
);

/*
 * Os redirects das URLs antigas do Jekyll.
 *
 * Dois modos de falha, e os dois são silenciosos: o stub não chegar ao `dist` (e a URL antiga dar
 * 404 como se não houvesse redirect nenhum), e o stub existir apontando para uma página que não
 * existe — que é pior, porque o leitor é levado a um 404 DEPOIS de achar que deu certo.
 */
const mapa = JSON.parse(readFileSync(resolve('redirects.json'), 'utf8'));
if (process.env.PLANTAR_DEFEITO) mapa['/pt/inexistente.html'] = '/pt/nao-existe/';
const redirectsRuins = [];

for (const [antiga, nova] of Object.entries(mapa)) {
  const stub = join(DIST, antiga.replace(/^\//, ''));

  if (!existsSync(stub)) {
    redirectsRuins.push(`${antiga} — stub ausente no dist`);
    continue;
  }

  /*
   * O destino do stub é RELATIVO, e o conferidor precisa saber disso.
   *
   * O stub mora em `…/x.html` e aponta para `x/` — mesmo diretório. É o que faz o redirect
   * funcionar sob qualquer `base`, inclusive o `/filament-starter-kit-easy` do Pages. Uma versão
   * anterior gravava o caminho absoluto e teria dado 404 em produção nos 54.
   */
  const relativo = nova.replace(/\/$/, '').split('/').pop() + '/';
  const conteudoDoStub = readFileSync(stub, 'utf8');

  if (!conteudoDoStub.includes(`url=${relativo}`)) {
    redirectsRuins.push(`${antiga} — stub nao aponta para ${relativo}`);
    continue;
  }

  // O redirect tem de acontecer sem clique: `meta refresh` com atraso zero.
  if (!conteudoDoStub.includes('content="0; url=')) {
    redirectsRuins.push(`${antiga} — stub nao redireciona sozinho (sem meta refresh imediato)`);
    continue;
  }

  if (/href="\//.test(conteudoDoStub)) {
    redirectsRuins.push(`${antiga} — stub tem caminho absoluto, que quebra sob base`);
    continue;
  }

  if (!existsSync(join(DIST, nova, 'index.html'))) {
    redirectsRuins.push(`${antiga} -> ${nova} — o destino nao existe`);
  }
}

console.log(`redirects conferidos: ${Object.keys(mapa).length}`);
console.log(
  redirectsRuins.length
    ? `REDIRECTS RUINS (${redirectsRuins.length}):\n` + redirectsRuins.join('\n')
    : 'redirects ruins: 0',
);

/*
 * O CÓDIGO DE SAÍDA.
 *
 * Sem isto o script imprimia "QUEBRADOS (3)" e saía com 0 — e o passo do workflow ficava verde
 * com o site quebrado indo ao ar. Guarda que não reprova não é guarda: é um `console.log` caro.
 *
 * A revisão adversarial descreveu o modo de falha antes de ele acontecer em produção, e a
 * conferência confirmou: não havia um `process.exit` no arquivo inteiro.
 */
const reprovas = [...problemas, ...quebrados, ...redirectsRuins];

if (reprovas.length > 0) {
  console.error(`\nREPROVADO — ${reprovas.length} problema(s):`);
  for (const r of reprovas) {
    console.error(`  - ${r}`);
  }
  process.exitCode = 1;
} else {
  console.log('\nOK — links, redirects e pisos de populacao conferidos.');
}
