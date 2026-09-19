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
  const texto = removeH1(reescreveLinks(corpo, arquivo)).replace(/\r\n/g, '\n');

  writeFileSync(saida, frente.join('\n') + texto, {
    encoding: 'utf8',
  });
  convertidas++;
}

console.log('convertidas: ' + convertidas);
console.log(
  'sem descricao derivada: ' +
    semDescricao.length +
    (semDescricao.length ? ' -> ' + semDescricao.join(', ') : ''),
);
