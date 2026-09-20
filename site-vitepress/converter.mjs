/**
 * Converte as páginas do site Jekyll para o VitePress.
 *
 * É bem menos trabalho que a conversão para o Starlight, e vale saber por quê — é a diferença
 * concreta entre os dois na hora de migrar:
 *
 * - **Links**: o VitePress resolve `[x](../recursos/y.md)` sozinho, reescrevendo a extensão e a
 *   profundidade. Nada precisa ser tocado. A conversão para o Starlight teve de reescrever todo
 *   link relativo como caminho absoluto, porque lá `convites.html` virou `convites/` e a
 *   profundidade do `../` mudou junto.
 * - **H1**: o VitePress renderiza o markdown como está, então o H1 do corpo continua sendo o
 *   título da página. No Starlight o `title` do front-matter VIRA o H1, e manter o do corpo dava
 *   título duplicado.
 *
 * O que ele ainda precisa fazer: limpar as chaves do just-the-docs do front-matter e MONTAR A
 * BARRA LATERAL, que no VitePress é declarada à mão no config — não há `autogenerate`. O
 * `sidebar.json` sai daqui para o config não ter uma árvore de 60 itens escrita na mão.
 */
import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync, rmSync } from 'node:fs';
import { dirname, join, relative, resolve, sep } from 'node:path';

const ORIGEM = resolve('../docs');
const DESTINO = resolve('.');

const emBarras = (caminho) => caminho.split(sep).join('/');

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

function descricaoDe(corpo) {
  const prosa = corpo
    .replace(/^```[\s\S]*?```$/gm, '')
    .split(/\r?\n\r?\n/)
    .map((b) => b.trim())
    .find((b) => b && !b.startsWith('#') && !b.startsWith('>') && !b.startsWith('|') && !b.startsWith('!['));
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

const SECOES = {
  comecar: { pt: 'Começar', en: 'Getting started', ordem: 1 },
  autenticacao: { pt: 'Autenticação', en: 'Authentication', ordem: 2 },
  recursos: { pt: 'Recursos', en: 'Features', ordem: 3 },
  operacao: { pt: 'Operação', en: 'Operations', ordem: 4 },
  referencia: { pt: 'Referência', en: 'Reference', ordem: 5 },
};

/** @type {Record<string, Record<string, {text: string, link: string, ordem: number}[]>>} */
const arvore = { pt: {}, en: {} };

let convertidas = 0;

for (const arquivo of paginas(ORIGEM)) {
  const rel = emBarras(relative(ORIGEM, arquivo));

  // A landing da raiz e as dos dois idiomas são escritas à mão (layout `home` do VitePress).
  if (rel === 'index.md' || /^(pt|en)\/index\.md$/.test(rel)) continue;

  const { meta, corpo } = separaFrontMatter(readFileSync(arquivo, 'utf8'));
  const descricao = descricaoDe(corpo);

  const frente = ['---', 'title: ' + JSON.stringify(meta.title ?? rel)];
  if (descricao) frente.push('description: ' + JSON.stringify(descricao));
  frente.push('---', '');

  const saida = join(DESTINO, rel);
  mkdirSync(dirname(saida), { recursive: true });
  writeFileSync(saida, frente.join('\n') + corpo.replace(/\r\n/g, '\n'), { encoding: 'utf8' });
  convertidas++;

  const [idioma, secao, arquivoDaSecao] = rel.split('/');
  if (!SECOES[secao]) continue;

  (arvore[idioma][secao] ??= []).push({
    text: arquivoDaSecao === 'index.md' ? (idioma === 'pt' ? 'Visão geral' : 'Overview') : (meta.title ?? rel),
    link: '/' + rel.replace(/\.md$/, '').replace(/\/index$/, '/'),
    ordem: arquivoDaSecao === 'index.md' ? 0 : Number(meta.nav_order ?? 99),
  });
}

const sidebar = {};

for (const idioma of ['pt', 'en']) {
  sidebar['/' + idioma + '/'] = Object.entries(SECOES)
    .sort((a, b) => a[1].ordem - b[1].ordem)
    .map(([chave, secao]) => ({
      text: secao[idioma],
      collapsed: false,
      items: (arvore[idioma][chave] ?? [])
        .sort((a, b) => a.ordem - b.ordem)
        .map(({ text, link }) => ({ text, link })),
    }));
}

mkdirSync('.vitepress', { recursive: true });
writeFileSync('.vitepress/sidebar.json', JSON.stringify(sidebar, null, 2) + '\n', { encoding: 'utf8' });

console.log('convertidas: ' + convertidas);
console.log(
  'itens no sidebar: pt=' +
    sidebar['/pt/'].reduce((n, g) => n + g.items.length, 0) +
    ' en=' +
    sidebar['/en/'].reduce((n, g) => n + g.items.length, 0),
);
