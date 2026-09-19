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
import { join, resolve } from 'node:path';

const DIST = resolve('dist');

const paginas = (dir) =>
  readdirSync(dir).flatMap((nome) => {
    const caminho = join(dir, nome);
    if (statSync(caminho).isDirectory()) return paginas(caminho);
    return nome.endsWith('.html') ? [caminho] : [];
  });

const ESTATICOS = /\.(css|js|png|webp|svg|xml|json|ico|txt|woff2?)$/;

const quebrados = [];
const vistos = new Set();
let conferidos = 0;

for (const arquivo of paginas(DIST)) {
  const html = readFileSync(arquivo, 'utf8');

  for (const casou of html.matchAll(/href="(\/[^"?]*)"/g)) {
    const [alvo, ancora] = casou[1].split('#');
    if (!alvo || ESTATICOS.test(alvo)) continue;

    conferidos++;

    const chave = alvo + (ancora ? '#' + ancora : '');
    if (vistos.has(chave)) continue;
    vistos.add(chave);

    const achado = [join(DIST, alvo, 'index.html'), join(DIST, alvo), join(DIST, alvo + '.html')].find(
      existsSync,
    );

    const origem = arquivo.replace(DIST, '');

    if (!achado) {
      quebrados.push(`${chave}  — pagina inexistente (ex.: ${origem})`);
      continue;
    }

    /*
     * A âncora chega percent-encoded no HTML (`#reten%C3%A7%C3%A3o-...`) porque o título tem
     * acento, e o `id` do destino é o texto cru em UTF-8. Comparar as duas formas acusa toda
     * âncora acentuada — foi o que este conferidor fez na primeira rodada, com um falso positivo.
     */
    const procurada = decodeURIComponent(ancora ?? '');

    if (ancora && !readFileSync(achado, 'utf8').includes(`id="${procurada}"`)) {
      quebrados.push(`${chave}  — ancora morta (ex.: ${origem})`);
    }
  }
}

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

  if (!readFileSync(stub, 'utf8').includes(`url=${nova}`)) {
    redirectsRuins.push(`${antiga} — stub nao aponta para ${nova}`);
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
