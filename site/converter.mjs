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
import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync, rmSync } from 'node:fs';
import { dirname, join, relative, resolve, sep } from 'node:path';

const ORIGEM = resolve('../docs');

/*
 * Origem e destino são O MESMO diretório: a transformação é NO LUGAR.
 *
 * O spike copiava `docs/` para dentro do projeto Astro, e isso criaria duas fontes da verdade.
 * A ADR-02 decidiu o contrário, depois de medir que NOVE arquivos do repositório apontam para
 * `docs/` — entre eles o helper `tests/Pest.php:documentacaoDoKit():933`, consumido por quatro
 * testes de outras features. Mover o conteúdo custaria editar os nove; ir até ele custa uma
 * linha de configuração.
 */
const DESTINO = ORIGEM;

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

  /*
   * O primeiro bloco de PROSA — e "prosa" exclui mais coisa do que parece.
   *
   * Cabeçalho, citação, tabela e imagem já estavam fora. Faltava a **imagem com link**
   * (`[![alt](thumb)](full)`), que abre várias páginas deste site e começa com `[`, não com `!`.
   * Sem ela na lista, a `description` de 20 páginas nascia com o texto alternativo de um print —
   * que é o que o buscador e o cartão de compartilhamento mostrariam.
   *
   * O teste do bloco é: sobra texto depois de tirar links e imagens?
   */
  const ehProsa = (b) => {
    if (!b || b.startsWith('#') || b.startsWith('>') || b.startsWith('|') || b.startsWith('```')) {
      return false;
    }

    const semMidia = b
      .replace(/!\[[^\]]*\]\([^)]*\)/g, '')
      .replace(/\[[^\]]*\]\([^)]*\)/g, '')
      .trim();

    return semMidia.length > 40;
  };

  const prosa = blocos.find(ehProsa);
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

/**
 * O texto do H1 do corpo, ou `null`.
 *
 * Existe porque **o H1 e o `title` do just-the-docs nem sempre são o mesmo texto**, e a diferença
 * não é cosmética: no Jekyll o `title` era o rótulo da NAVEGAÇÃO (curto) e o H1 era o título da
 * PÁGINA (completo). `A rota / é pública` contra
 * ``A rota `/` é pública e não mostra segredo`` é um dos casos reais.
 *
 * No Starlight o `title` é os dois ao mesmo tempo — vira o H1 renderizado e o rótulo da barra —,
 * e `sidebar.label` é quem guarda a forma curta. A conversão certa não é "manter o `title` e
 * jogar o H1 fora": é `title` ← H1, `sidebar.label` ← `title` antigo, quando diferem.
 *
 * Quem descobriu foi o `[CT-01]`, que compara os títulos do site contra o baseline congelado de
 * 115 medido ANTES da migração original. Jogar o H1 fora perdia títulos, e o baseline acusou —
 * um oráculo escrito para outra feature pegando o defeito desta.
 */
function h1Do(corpo) {
  const casou = /^\s*#\s+([^\n]+)/.exec(corpo);

  return casou ? casou[1].trim() : null;
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
 * A barra lateral, gerada.
 *
 * O `autogenerate` do Starlight **não funciona** com o conteúdo fora da raiz do projeto Astro:
 * os grupos renderizam vazios. Medido — a barra saiu com os cinco rótulos e nenhum item, e a
 * página construída tinha 9 links onde deveria ter ~40.
 *
 * O que funciona é declarar os itens por `slug`, e o slug é **sem o prefixo de idioma**: o
 * Starlight rejeita `pt/comecar/dominio-local` com "does not exist" e aceita
 * `comecar/instalacao-avancada`, localizando-o para cada locale. Também medido, nos dois sentidos.
 *
 * A lista é gerada daqui e não escrita à mão pelo motivo de sempre — e aqui com um agravante: uma
 * página nova que não entrasse na lista simplesmente não apareceria na navegação, sem nada ficar
 * vermelho. É o que o `[CT-41]` passa a cobrir.
 */
const SECOES = [
  { dir: 'comecar', pt: 'Começar', en: 'Getting started' },
  { dir: 'autenticacao', pt: 'Autenticação', en: 'Authentication' },
  { dir: 'recursos', pt: 'Recursos', en: 'Features' },
  { dir: 'operacao', pt: 'Operação', en: 'Operations' },
  { dir: 'referencia', pt: 'Referência', en: 'Reference' },
];

/** @type {Record<string, {slug: string, ordem: number}[]>} */
const itensDaSecao = {};

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

/** @type {Record<string, string>} */
const redirects = {};

/**
 * Stub de redirecionamento: `meta refresh` para quem navega, `canonical` para quem indexa.
 *
 * ## O destino é RELATIVO, e isso não é estilo
 *
 * A primeira versão gravava o caminho absoluto (`/pt/comecar/instalacao-avancada/`), com um
 * prefixo opcional vindo de `DOCS_BASE`. Os stubs são **commitados** (ADR-06), e quem os gera
 * localmente não passa `DOCS_BASE` — então os 54 arquivos entraram no repositório apontando para
 * a raiz do domínio. O site publica em `https://gsferro.github.io/filament-starter-kit-easy/`:
 * **todos os 54 redirects dariam 404 em produção**, e nada no repositório ficaria vermelho.
 *
 * O stub mora em `…/comecar/instalacao-avancada.html` e o destino é `…/comecar/instalacao-avancada/`
 * — mesmo diretório. Um caminho relativo (`instalacao-avancada/`) resolve contra o diretório do
 * próprio documento e chega no lugar certo **em qualquer base**, inclusive na raiz. A dependência
 * do `DOCS_BASE` deixa de existir para os stubs.
 */
function escreveStub(de, para) {
  // Mesmo diretório sempre: `X.html` -> `X/`. O relativo é só o último segmento.
  const destino = para.replace(/\/$/, '').split('/').pop() + '/';
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

  /*
   * A landing compartilhada do Jekyll não tem equivalente: cada locale tem a sua, e `/`
   * redireciona. Como a transformação é no lugar, ela precisa ser REMOVIDA, não só ignorada —
   * deixada para trás, o Starlight a publicaria como uma página solta na raiz, sem locale.
   */
  if (rel === 'index.md') {
    rmSync(arquivo);
    continue;
  }

  /*
   * As landings de idioma são ESCRITAS À MÃO (hero no front-matter + cartões em HTML) e o
   * conversor não encosta nelas.
   *
   * Uma versão anterior as apagava, por herança do spike, onde elas viviam dentro do projeto
   * Astro e o `index.md` do Jekyll era descartável. Com a transformação no lugar, apagar aqui
   * destrói a landing na primeira reexecução do conversor — e o conversor é feito para ser
   * reexecutável.
   */
  if (/^(pt|en)\/index\.md$/.test(rel)) continue;

  const { meta, corpo } = separaFrontMatter(readFileSync(arquivo, 'utf8'));
  const descricao = descricaoDe(corpo);
  if (!descricao) semDescricao.push(rel);

  const h1 = h1Do(corpo);
  const tituloAntigo = meta.title ?? rel;
  const titulo = h1 ?? tituloAntigo;

  /*
   * O rótulo curto da navegação só nasce quando o H1 e o `title` antigo diferem de verdade.
   *
   * O `meta.label` do final é o que torna o conversor REEXECUTÁVEL: numa segunda passada o H1 já
   * foi removido, `h1` é nulo, e sem essa herança o `sidebar.label` conquistado na primeira
   * passada seria apagado — um conversor que degrada o conteúdo a cada execução.
   */
  const rotuloCurto = h1 !== null && h1 !== tituloAntigo ? tituloAntigo : (meta.label ?? null);

  const frente = ['---', 'title: ' + JSON.stringify(titulo)];
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
  } else if (rotuloCurto !== null || meta.nav_order) {
    frente.push('sidebar:');
    if (rotuloCurto !== null) frente.push('  label: ' + JSON.stringify(rotuloCurto));
    if (meta.nav_order) frente.push('  order: ' + meta.nav_order);
  }

  frente.push('---', '');

  const saida = join(DESTINO, rel);
  mkdirSync(dirname(saida), { recursive: true });
  const [idioma, secao] = rel.split('/');

  // A barra lateral é declarada uma vez e localizada pelo Starlight: só o idioma padrão a alimenta.
  if (idioma === 'pt' && SECOES.some((s) => s.dir === secao)) {
    (itensDaSecao[secao] ??= []).push({
      slug: emBarras(rel).replace(/^pt\//, '').replace(/\.mdx?$/, '').replace(/\/index$/, ''),
      ordem: indiceDeSecao ? 0 : Number(meta.nav_order ?? 99),
    });
  }

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

writeFileSync(
  'sidebar.json',
  JSON.stringify(
    SECOES.map(({ dir, pt, en }) => ({
      label: pt,
      translations: { en },
      items: (itensDaSecao[dir] ?? [])
        .sort((a, b) => a.ordem - b.ordem)
        .map(({ slug }) => ({ slug })),
    })),
    null,
    2,
  ) + String.fromCharCode(10),
  { encoding: 'utf8' },
);

writeFileSync('redirects.json', JSON.stringify(redirects, null, 2) + '\n', { encoding: 'utf8' });

console.log('convertidas: ' + convertidas);
console.log('redirects gerados: ' + Object.keys(redirects).length);
console.log('itens no sidebar: ' + Object.values(itensDaSecao).reduce((n, i) => n + i.length, 0));
console.log(
  'sem descricao derivada: ' +
    semDescricao.length +
    (semDescricao.length ? ' -> ' + semDescricao.join(', ') : ''),
);
