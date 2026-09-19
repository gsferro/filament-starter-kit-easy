/**
 * Converte as páginas do site Jekyll para o formato do Starlight.
 *
 * Duas transformações, e a segunda é a que tem armadilha:
 *
 * 1. FRONT-MATTER. `parent`/`grand_parent`/`has_children` eram o jeito do just-the-docs montar
 *    a árvore; no Starlight a árvore sai do sistema de arquivos. `nav_order` vira `sidebar.order`.
 *    `description` é derivada do primeiro parágrafo — o Jekyll não tinha, e sem ela o cartão de
 *    compartilhamento e o resultado de busca ficam com o texto que o gerador inventar.
 *
 * 2. LINKS. O Jekyll publica `convites.html`; o Starlight publica `convites/`. A profundidade
 *    relativa MUDA: de `/pt/autenticacao/convites.html`, `../recursos/x.html` chega em
 *    `/pt/recursos/x.html`; de `/pt/autenticacao/convites/`, o mesmo `../recursos/x/` chega em
 *    `/pt/autenticacao/recursos/x/` — página errada, e que em alguns casos existe.
 *
 *    Por isso nada aqui preserva `../`: cada link é resolvido contra o diretório do arquivo de
 *    origem e reescrito como caminho absoluto. É a única forma que não depende de contar níveis.
 */
import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync } from 'node:fs';
import { dirname, join, relative, resolve, sep } from 'node:path';

const ORIGEM = resolve('../docs');
const DESTINO = resolve('src/content/docs');

const emBarras = (caminho) => caminho.split(sep).join('/');

/** @returns {string[]} todo .md sob um diretório */
function paginas(dir) {
  return readdirSync(dir).flatMap((nome) => {
    const caminho = join(dir, nome);
    if (statSync(caminho).isDirectory()) return paginas(caminho);
    return nome.endsWith('.md') ? [caminho] : [];
  });
}

function separaFrontMatter(texto) {
  const casou = /^---\r?\n([\s\S]*?)\r?\n---\r?\n?/.exec(texto);
  if (!casou) return { meta: {}, corpo: texto };
  const meta = {};
  for (const linha of casou[1].split(/\r?\n/)) {
    const par = /^([a-z_]+):\s*(.*)$/.exec(linha);
    if (par) meta[par[1]] = par[2].trim().replace(/^["']|["']$/g, '');
  }
  return { meta, corpo: texto.slice(casou[0].length) };
}

/** O primeiro parágrafo de prosa, limpo de marcação e cortado em fronteira de palavra. */
function descricaoDe(corpo) {
  const blocos = corpo
    .replace(/^```[\s\S]*?```$/gm, '')
    .split(/\r?\n\r?\n/)
    .map((b) => b.trim());

  const prosa = blocos.find(
    (b) => b && !b.startsWith('#') && !b.startsWith('>') && !b.startsWith('|') && !b.startsWith('!['),
  );
  if (!prosa) return null;

  const limpo = prosa
    .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1')
    .replace(/[*_`]/g, '')
    .replace(/\s+/g, ' ')
    .trim();

  if (limpo.length <= 160) return limpo;
  const corte = limpo.slice(0, 157);
  return corte.slice(0, corte.lastIndexOf(' ')) + '…';
}

/** `docs/pt/comecar/index.md` -> `/pt/comecar/`; `docs/pt/a/b.md` -> `/pt/a/b/` */
function urlDe(caminhoAbsoluto) {
  let rel = emBarras(relative(ORIGEM, caminhoAbsoluto)).replace(/\.md$/, '');
  rel = rel.replace(/(^|\/)index$/, '$1');
  return '/' + (rel === '' || rel.endsWith('/') ? rel : rel + '/');
}

/**
 * Remove o H1 do corpo.
 *
 * No Jekyll o `title` do front-matter ia para a aba do navegador e o H1 era escrito à mão no
 * corpo — os dois convivem. O Starlight RENDERIZA o `title` como H1 da página, então manter o do
 * corpo produz o título duas vezes na tela, e um segundo H1 no documento, que é defeito de
 * acessibilidade e não só de estética.
 */
function removeH1(corpo) {
  return corpo.replace(/^\s*#\s+[^\n]*\n+/, '');
}

function reescreveLinks(corpo, arquivo) {
  return corpo.replace(/\]\(([^)\s]+?)(#[^)\s]*)?\)/g, (inteiro, alvo, ancora) => {
    if (/^(https?:|mailto:|#|\/)/.test(alvo)) return inteiro;
    if (!alvo.endsWith('.md')) return inteiro;
    const destino = resolve(dirname(arquivo), alvo);
    return '](' + urlDe(destino) + (ancora || '') + ')';
  });
}

let convertidas = 0;
const semDescricao = [];

/**
 * Os redirects das URLs do Jekyll para as do Starlight.
 *
 * A migração muda a forma de TODA rota de folha: o Jekyll publica
 * `/pt/comecar/instalacao-avancada.html` e o Starlight publica
 * `/pt/comecar/instalacao-avancada/`. Quem salvou um link da documentação, e o que o buscador
 * indexou, apontam para a forma antiga.
 *
 * Índice de seção NÃO entra: `/pt/comecar/` já era assim nos dois, porque o Jekyll serve o
 * `index.html` do diretório na própria URL do diretório. Redirect de rota que não mudou é ruído
 * que esconde os que importam.
 *
 * Gerado daqui, e não escrito à mão, pelo motivo de sempre: lista de 54 rotas mantida à mão
 * envelhece calada na primeira página que alguém renomear.
 *
 * ## Por que em `public/` e não no `redirects` do Astro
 *
 * O `redirects` do Astro trata a chave como ROTA, e o `build.format` padrão é `directory`: a
 * chave `/pt/x.html` vira o diretório `x.html/` com um `index.html` dentro, e o arquivo
 * `/pt/x.html` continua não existindo. Medido — o build falhou gerando
 * `convites.html/index.html`.
 *
 * `public/` é copiado literalmente, então o caminho sai exatamente como escrito aqui. É também o
 * que funciona igual em qualquer host estático, sem depender de configuração de servidor.
 *
 * A raiz (`/`) continua no `redirects` do Astro: ela é rota de verdade, não arquivo `.html`.
 */
const PUBLICO = resolve('public');
const BASE = (process.env.DOCS_BASE || '').replace(/\/$/, '');

/** @type {Record<string, string>} */
const redirects = {};

/** Stub de redirecionamento: `meta refresh` para quem navega, `canonical` para quem indexa. */
function escreveStub(de, para) {
  const destino = BASE + para;
  const saida = join(PUBLICO, de.replace(/^\//, ''));

  mkdirSync(dirname(saida), { recursive: true });
  writeFileSync(
    saida,
    [
      '<!doctype html>',
      '<html lang="pt-BR">',
      '<head>',
      '<meta charset="utf-8">',
      `<meta http-equiv="refresh" content="0; url=${destino}">`,
      `<link rel="canonical" href="${destino}">`,
      '<meta name="robots" content="noindex">',
      '<title>Esta página mudou de endereço</title>',
      '</head>',
      '<body>',
      `<p>Esta página agora fica em <a href="${destino}">${destino}</a>.</p>`,
      '</body>',
      '</html>',
      '',
    ].join('\n'),
    { encoding: 'utf8' },
  );
}

for (const arquivo of paginas(ORIGEM)) {
  const rel = emBarras(relative(ORIGEM, arquivo));

  // A landing compartilhada do Jekyll não tem equivalente: cada locale tem a sua, e `/` redireciona.
  if (rel === 'index.md') continue;

  // As landings de idioma são escritas à mão como `.mdx` (hero + cartões); o `index.md` do Jekyll
  // era só um título com dois parágrafos, e converter por cima delas desfaria o trabalho.
  if (/^(pt|en)\/index\.md$/.test(rel)) continue;

  const { meta, corpo } = separaFrontMatter(readFileSync(arquivo, 'utf8'));
  const descricao = descricaoDe(corpo);
  if (!descricao) semDescricao.push(rel);

  const frente = ['---', 'title: ' + JSON.stringify(meta.title ?? rel)];
  if (descricao) frente.push('description: ' + JSON.stringify(descricao));

  /*
   * O `index.md` de uma seção tem o MESMO título do grupo que o contém — no just-the-docs isso
   * era o normal, porque `has_children` fazia do título o cabeçalho do grupo e a página vinha
   * junto. No Starlight o grupo é nomeado no `astro.config.mjs` e a página é um item dentro
   * dele, então a barra lateral mostrava "Começar > Começar", em quatro das cinco seções.
   *
   * O rótulo na barra passa a ser "Visão geral"; o título da página continua o que era.
   */
  const indiceDeSecao = /^(pt|en)\/[^/]+\/index\.md$/.test(rel);

  if (indiceDeSecao) {
    frente.push('sidebar:', '  label: ' + (rel.startsWith('pt/') ? '"Visão geral"' : '"Overview"'), '  order: 0');
  } else if (meta.nav_order) {
    frente.push('sidebar:', '  order: ' + meta.nav_order);
  }

  frente.push('---', '');

  const saida = join(DESTINO, rel);
  mkdirSync(dirname(saida), { recursive: true });
  if (!indiceDeSecao) {
    const antiga = urlDe(arquivo).replace(/\/$/, '') + '.html';
    redirects[antiga] = urlDe(arquivo);
    escreveStub(antiga, urlDe(arquivo));
  }

  const texto = removeH1(reescreveLinks(corpo, arquivo)).replace(/\r\n/g, '\n');

  writeFileSync(saida, frente.join('\n') + texto, {
    encoding: 'utf8',
  });
  convertidas++;
}

writeFileSync('redirects.json', JSON.stringify(redirects, null, 2) + '\n', { encoding: 'utf8' });

console.log('convertidas: ' + convertidas);
console.log('redirects gerados: ' + Object.keys(redirects).length);
console.log(
  'sem descricao derivada: ' +
    semDescricao.length +
    (semDescricao.length ? ' -> ' + semDescricao.join(', ') : ''),
);
