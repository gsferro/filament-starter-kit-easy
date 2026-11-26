/**
 * Gera o mapa interativo do kit: um HTML do archify por diagrama e por idioma, em `dist/mapa/{pt,en}/`.
 *
 * Roda depois do `astro build`, no mesmo `npm run build` (ADR-01 e ADR-04 da wiki
 * `mapa-interativo-do-kit`). Só `render` e `compare`: o `finalize` do archify exige navegador, e o
 * build do site não tem um.
 *
 * Cada arquivo de `mapa/diagramas/` é a entrada do archify com três diferenças, que este gerador
 * desfaz para cada idioma:
 *  - todo texto que muda de idioma é `{pt, en}` (P-02);
 *  - toda prova é âncora `{arquivo, trecho}` (ADR-03). A linha sai do arquivo NA REVISÃO publicada,
 *    e não da árvore de trabalho, para o link "origem verificada" do archify apontar a linha certa
 *    daquele commit (D22);
 *  - os campos do kit — `slug`, `tipo`, `titulo`, `paineis`, `modos` e, no comparativo, `base` e
 *    `cabeca` — saem antes de o arquivo chegar ao archify.
 *
 * Para no primeiro diagrama que o archify recusar, com o diagnóstico dele, e sai com código 1.
 */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, realpathSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { obterArchify } from './archify.mjs';

const RAIZ = fileURLToPath(new URL('../../', import.meta.url));
const SAIDA = fileURLToPath(new URL('../dist/mapa/', import.meta.url));
const DIAGRAMAS = fileURLToPath(new URL('./diagramas/', import.meta.url));
const IDIOMAS = { pt: 'pt-BR', en: 'en' };

/** A coleção que o archify trata como peça, por tipo — a mesma que ele usa para conferir as provas. */
const PECAS = {
  architecture: 'components',
  workflow: 'nodes',
  sequence: 'participants',
  dataflow: 'nodes',
  lifecycle: 'states',
  erd: 'entities',
  tree: 'nodes',
  class: 'types',
  timeline: 'events',
  waterfall: 'spans',
};

/**
 * Onde moram as ligações de cada tipo. O archify não aceita `sources` numa ligação, então a prova dela
 * (`prova: {arquivo, trecho}`, campo do kit) sai do arquivo e vira item de card (P-04, D4). A tree de
 * registro tem as ligações implícitas na hierarquia, e timeline e waterfall não têm ligação.
 */
const LIGACOES = {
  architecture: 'connections',
  workflow: 'edges',
  sequence: 'messages',
  dataflow: 'flows',
  lifecycle: 'transitions',
  erd: 'relationships',
  class: 'relationships',
};

const TITULO_DA_PROVA = { pt: 'Prova das ligações', en: 'Link proof' };

/** Lê um JSON do kit; o quebrado é recusado nomeando o arquivo (P-14). */
function lerJson(caminho) {
  const texto = readFileSync(caminho, 'utf8');
  try {
    return JSON.parse(texto);
  } catch (erro) {
    throw new Error(`[mapa] ERRO ${relative(RAIZ, caminho).replaceAll('\\', '/')} — JSON inválido: ${erro.message}`);
  }
}
const git = (...args) => execFileSync('git', ['-C', RAIZ, ...args], { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 });

const revisao = git('rev-parse', 'HEAD').trim();
const repositorio = {
  url: lerJson(join(RAIZ, 'composer.json')).support.source,
  revision: revisao,
  provider: 'github',
  link_mode: 'web',
};

const interfaceDoKit = lerJson(fileURLToPath(new URL('./interface.json', import.meta.url)));

/** O texto de cada arquivo citado, na revisão publicada; lido uma vez por arquivo. */
const textos = new Map();

/**
 * As faixas de comentário de um PHP, como o tokenizador do PHP as vê (`T_COMMENT` e `T_DOC_COMMENT`, a régua da
 * guarda da R9): `//` e `#` (fora de `#[`) até o fim da linha ou o `?>`, e `/* *\/`. Antes do `<?php` e depois do
 * `?>` é HTML, e string — entre aspas, heredoc e nowdoc — não tem comentário (re-revisão, RR-03).
 */
function comentariosDoPhp(texto) {
  const faixas = [];
  const fimOuTudo = (indice) => (indice < 0 ? texto.length : indice);
  const abertura = /<\?(?:php|=)/g;
  const heredoc = /<<<[ \t]*(['"]?)([A-Za-z_\x80-￿][\w\x80-￿]*)\1\r?\n/y;
  for (let i = 0; ; i += 2) {
    abertura.lastIndex = i;
    const tag = abertura.exec(texto);
    if (!tag) return faixas;
    for (i = tag.index + tag[0].length; i < texto.length && !texto.startsWith('?>', i); i++) {
      const c = texto[i];
      heredoc.lastIndex = i;
      const cabeca = heredoc.exec(texto);
      if (cabeca) {
        const fecho = new RegExp(`^[ \\t]*${cabeca[2]}(?![\\w\\x80-\\uffff])`, 'gm');
        fecho.lastIndex = i + cabeca[0].length;
        const fim = fecho.exec(texto);
        i = fim ? fecho.lastIndex - 1 : texto.length;
      } else if (c === "'" || c === '"' || c === '`') {
        for (i++; i < texto.length && texto[i] !== c; i++) if (texto[i] === '\\') i++;
      } else if (c === '/' && texto[i + 1] === '*') {
        const fim = texto.indexOf('*/', i + 2);
        faixas.push([i, fim < 0 ? texto.length : fim + 2]);
        i = fim < 0 ? texto.length : fim + 1;
      } else if ((c === '/' && texto[i + 1] === '/') || (c === '#' && texto[i + 1] !== '[')) {
        const fim = Math.min(fimOuTudo(texto.indexOf('\n', i)), fimOuTudo(texto.indexOf('?>', i)));
        faixas.push([i, fim]);
        i = fim - 1;
      }
    }
  }
}

/**
 * As faixas de comentário de um arquivo, pela sintaxe do tipo dele, com a mesma régua da guarda da R9
 * (`mapaModeloMascararComentarios()`, D34): PHP pelo tokenizador; Blade, `{{-- --}}` e `<!-- -->`; JS e stub, `/* *\/`
 * e `//` (fora de `://`); YAML e `.env`, `#` no começo da linha ou depois de espaço; Markdown, `<!-- -->`.
 */
function faixasDeComentario(arquivo, texto) {
  const porRegex = (padrao) => [...texto.matchAll(padrao)].map((m) => [m.index, m.index + m[0].length]);
  if (arquivo.endsWith('.blade.php')) return porRegex(/\{\{--[\s\S]*?--\}\}|<!--[\s\S]*?-->/g);
  if (arquivo.endsWith('.php')) return comentariosDoPhp(texto);
  if (/\.(m?js|ts|stub)$/.test(arquivo)) return porRegex(/\/\*[\s\S]*?\*\/|(?<![:\w])\/\/[^\n]*/g);
  if (/\.ya?ml$/.test(arquivo) || arquivo.split('/').pop().startsWith('.env')) return porRegex(/(?<=^|[ \t])#[^\n]*/gm);
  if (arquivo.endsWith('.md')) return porRegex(/<!--[\s\S]*?-->/g);
  return [];
}

/** As faixas de comentário de cada arquivo citado, calculadas uma vez. */
const comentarios = new Map();

function linhaDaAncora(arquivo, trecho) {
  if (!textos.has(arquivo)) textos.set(arquivo, git('show', `${revisao}:${arquivo}`));
  const texto = textos.get(arquivo);
  if (!comentarios.has(arquivo)) comentarios.set(arquivo, faixasDeComentario(arquivo, texto));
  const faixas = comentarios.get(arquivo);
  const tocaComentario = (indice) => faixas.some(([inicio, fim]) => indice < fim && indice + trecho.length > inicio);
  let posicao = texto.indexOf(trecho);
  if (posicao < 0) {
    throw new Error(`o trecho ${JSON.stringify(trecho)} não está em ${arquivo} na revisão ${revisao.slice(0, 7)}`);
  }
  // A linha da prova é a do código: a ocorrência que toca um comentário (um docblock que cita a linha) é pulada (R9, CT-52).
  while (posicao >= 0 && tocaComentario(posicao)) posicao = texto.indexOf(trecho, posicao + 1);
  if (posicao < 0) {
    throw new Error(`o trecho ${JSON.stringify(trecho)} só aparece em comentário em ${arquivo} na revisão ${revisao.slice(0, 7)}`);
  }
  const line = texto.slice(0, posicao).split('\n').length;
  const linhas = trecho.split('\n').length;
  return linhas > 1 ? { path: arquivo, line, end_line: line + linhas - 1 } : { path: arquivo, line };
}

/** Troca, em profundidade, `{pt, en}` pelo texto do idioma e `{arquivo, trecho}` pela fonte do archify. */
function projetar(valor, idioma) {
  if (Array.isArray(valor)) return valor.map((item) => projetar(item, idioma));
  if (valor === null || typeof valor !== 'object') return valor;

  const chaves = Object.keys(valor);
  if (chaves.length === 2 && 'pt' in valor && 'en' in valor) {
    if (valor[idioma] === undefined || valor[idioma] === '') {
      throw new Error(`texto sem a versão ${idioma}: ${JSON.stringify(valor).slice(0, 120)}`);
    }
    return projetar(valor[idioma], idioma);
  }
  if ('arquivo' in valor && 'trecho' in valor) {
    // a linha e o arquivo do link são só os que o gerador acha: `line`, `end_line` e `path` da âncora não passam (RR-04)
    const { arquivo, trecho, line, end_line, path, ...resto } = valor;
    return { ...projetar(resto, idioma), ...linhaDaAncora(arquivo, trecho) };
  }
  try {
    return Object.fromEntries(chaves.map((chave) => [chave, projetar(valor[chave], idioma)]));
  } catch (erro) {
    // a âncora que não resolve nomeia o nó que a cita — a peça, o participante, o estado (P-14)
    if (typeof valor.id !== 'string') throw erro;
    throw new Error(`${valor.id}: ${erro.message}`, { cause: erro });
  }
}

/**
 * Os cards de prova: um por ponta de origem desenhada, com um item por ligação — "origem → destino
 * (rótulo): arquivo:linha — trecho". O item nomeia as duas peças da ligação (mesmo quando a seta sai
 * de um agrupamento) e traz o arquivo e o trecho dela.
 */
function cardsDeProva(provas, ir, tipo, idioma) {
  const rotuloDe = new Map((ir[PECAS[tipo]] ?? []).map((no) => [no.id, no.label ?? no.name ?? no.id]));
  const porOrigem = new Map();
  for (const { de, para, nomeDe, nomePara, rotulo, prova } of provas) {
    const { line, end_line: fim } = linhaDaAncora(prova.arquivo, prova.trecho);
    const verbo = rotulo === undefined ? '' : ` (${projetar(rotulo, idioma)})`;
    const origem = nomeDe ?? rotuloDe.get(de) ?? de;
    const destino = nomePara ?? rotuloDe.get(para) ?? para;
    const item = `${origem} → ${destino}${verbo}: ${prova.arquivo}:${line}${fim ? `-${fim}` : ''} — ${prova.trecho}`;
    porOrigem.set(de, [...(porOrigem.get(de) ?? []), item]);
  }
  return [...porOrigem].map(([de, itens]) => ({
    dot: 'slate',
    title: `${TITULO_DA_PROVA[idioma]} — ${rotuloDe.get(de) ?? de}`,
    items: itens,
  }));
}

/** Diagrama escrito à mão: a prova de cada ligação sai da ligação; ligação sem prova não é publicada (P-04). */
function provasDasLigacoes(tipo, entrada) {
  const colecao = LIGACOES[tipo];
  const provas = [];
  if (colecao && entrada[colecao]) {
    entrada[colecao] = entrada[colecao].map(({ prova, ...ligacao }) => {
      if (!prova) throw new Error(`a ligação ${ligacao.from} → ${ligacao.to} não tem prova (P-04)`);
      provas.push({ de: ligacao.from, para: ligacao.to, rotulo: ligacao.label, prova });
      return ligacao;
    });
  }
  return { entrada, provas };
}

// ---------------------------------------------------------------------------------------------------
// Construtores: a árvore de registro e os mapas de arquitetura saem do modelo (pecas.json e
// ligacoes.json); o arquivo do diagrama diz só o recorte e o layout (ADR-07).
// ---------------------------------------------------------------------------------------------------
let modelo;
/**
 * O modelo, lido uma vez. A condição de cada peça e as pontas de cada ligação são conferidas aqui, antes de qualquer
 * efeito (P-14): o que não se lê é recusado nomeando a peça ou a ligação, e nunca some do desenho calado.
 */
const carregarModelo = () => {
  if (modelo) return modelo;
  const pecas = new Map(lerJson(fileURLToPath(new URL('./pecas.json', import.meta.url))).map((peca) => [peca.id, peca]));
  const ligacoes = lerJson(fileURLToPath(new URL('./ligacoes.json', import.meta.url)));
  for (const peca of pecas.values()) {
    const problemas = problemasDaCondicao(peca.disponibilidade);
    if (problemas.length) {
      throw new Error(`[mapa] ERRO site/mapa/pecas.json — a peça ${peca.id}: a condição de disponibilidade não se lê: ${problemas.join('; ')}`);
    }
  }
  for (const ligacao of ligacoes) {
    const fora = [ligacao.de, ligacao.para].filter((id) => !pecas.has(id));
    if (fora.length) {
      throw new Error(`[mapa] ERRO site/mapa/ligacoes.json — a ligação ${ligacao.de} ${ligacao.verbo} ${ligacao.para}: ${fora.join(' e ')} não está no modelo`);
    }
  }
  return (modelo = { pecas, ligacoes });
};

/** Id do modelo (FQCN, `painel:app`) no formato de id do archify. */
const idArchify = (id) => id.replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/^[^a-zA-Z]+/, 'x-');

/** O tipo de peça do kit no tipo de componente do archify; a legenda e o passaporte traduzem (D12). */
const TIPO_ARCHIFY = {
  painel: 'frontend', recurso: 'frontend', pagina: 'frontend', widget: 'frontend', livewire: 'frontend',
  provider: 'backend', servico: 'backend', comando: 'backend',
  model: 'database', setting: 'database',
  middleware: 'security', policy: 'security',
  plugin: 'cloud',
};

const ROTULO_DO_TIPO = {
  painel: { pt: 'painel', en: 'panel' },
  recurso: { pt: 'recurso', en: 'resource' },
  pagina: { pt: 'página', en: 'page' },
  widget: { pt: 'widget', en: 'widget' },
  plugin: { pt: 'plugin', en: 'plugin' },
  provider: { pt: 'provider', en: 'provider' },
  middleware: { pt: 'middleware', en: 'middleware' },
  servico: { pt: 'serviço', en: 'service' },
  model: { pt: 'model', en: 'model' },
  policy: { pt: 'policy', en: 'policy' },
  setting: { pt: 'configuração', en: 'settings' },
  comando: { pt: 'comando', en: 'command' },
  livewire: { pt: 'Livewire', en: 'Livewire' },
};

/** O verbo da ligação: o rótulo do card e a variante do traço (as setas do mapa saem sem rótulo, ADR-07). */
const VERBO = {
  registra: { pt: 'registra', en: 'registers', variante: 'emphasis', traco: { pt: 'traço forte', en: 'bold stroke' } },
  usa: { pt: 'usa', en: 'uses', variante: 'default', traco: { pt: 'traço comum', en: 'plain stroke' } },
  'lê': { pt: 'lê', en: 'reads', variante: 'dashed', traco: { pt: 'tracejado', en: 'dashed stroke' } },
  grava: { pt: 'grava', en: 'writes', variante: 'security', traco: { pt: 'traço de alerta', en: 'alert stroke' } },
};

/** O tag de F-nn do componente: até três, e "+N" no resto, que o card "F-nn de cada peça" lista inteiro (D29). */
const tagDasFeatures = (features) =>
  features.length > 3 ? `${features.slice(0, 3).join(' · ')} +${features.length - 3}` : features.join(' · ');

/** O card "F-nn de cada peça" das peças desenhadas: as F-nn inteiras, à vista sem zoom — o archify esconde o tag no carregamento (D29). */
function cardDeFeatures(desenhadas) {
  const comFeatures = desenhadas.filter((peca) => peca.features.length);
  return comFeatures.length
    ? [{ dot: 'emerald', title: { pt: 'F-nn de cada peça', en: 'F-nn of each piece' }, items: comFeatures.map((peca) => `${peca.nome}: ${peca.features.join(', ')}`) }]
    : [];
}

/** "recurso · F-29 · F-30" ou "plugin · filament-auth-designer": o tipo, o pacote e as F-nn da peça (D29). */
function sublabelDaPeca(peca) {
  const pacote = peca.origem === 'pacote' && peca.pacote ? ` · ${peca.pacote.split('/').pop()}` : '';
  const base = { pt: `${ROTULO_DO_TIPO[peca.tipo].pt}${pacote}`, en: `${ROTULO_DO_TIPO[peca.tipo].en}${pacote}` };
  const marca = marcaDaCondicao(peca);
  return marca ? { pt: `${base.pt} · ${marca.pt}`, en: `${base.en} · ${marca.en}` } : base;
}

// ---------------------------------------------------------------------------------------------------
// A peça condicional (Adendos 2 a 4): a que o painel registra, mas que só abre num modo ou com uma chave.
// A condição é o campo `disponibilidade` do modelo — {modo?, chaves?: [{chave, padrao, ligada?}], evidencia}.
// ---------------------------------------------------------------------------------------------------
/** A redação decidida do modo (D41): só a do modo com tenant; a condição em outro modo é recusada ao carregar o modelo. */
const MARCA_DO_MODO = { com: { pt: 'só com tenant ativo', en: 'tenancy on only' } };
const eVariavelDoKit = (chave) => typeof chave === 'string' && /^KIT_[A-Z0-9_]+$/.test(chave);
const eCaminhoDeConfiguracao = (chave) => typeof chave === 'string' && /^[a-z][\w-]*(?:\.[\w-]+)+$/.test(chave);
/** O nome curto da classe da fonte, sem namespace e sem `::class`: a validação e a marca comparam por ele. */
const nomeCurto = (valor) => String(valor).replace(/::class$/, '').split('\\').pop();

/**
 * O que impede ler a condição da peça: o modo, texto com redação decidida; cada chave, variável `KIT_*` com o valor de
 * fábrica ou caminho de configuração com uma fonte, pelo nome curto, que a liga além do valor de fábrica; modo ou
 * chave; e a âncora do código que decide. O conferidor do site (`site/verifica-mapa.mjs`, `condicaoDaPeca`) confere
 * por conta própria quase a mesma forma; a diferença é o `chaves: null`, que este gerador recusa e o conferidor lê
 * como lista vazia — o build para aqui antes.
 */
function problemasDaCondicao(condicao) {
  if (condicao === undefined || condicao === null) return [];
  if (typeof condicao !== 'object' || Array.isArray(condicao)) return ['não é um objeto'];
  const { modo = null, chaves = [], evidencia } = condicao;
  const problemas = [];
  if (modo !== null && (typeof modo !== 'string' || !Object.hasOwn(MARCA_DO_MODO, modo))) {
    problemas.push(`o modo ${JSON.stringify(modo)} não tem redação decidida (só "com")`);
  }
  if (!Array.isArray(chaves)) {
    problemas.push('chaves não é lista');
  } else {
    for (const item of chaves) {
      const chave = item?.chave;
      if (eVariavelDoKit(chave)) {
        if (!Object.hasOwn(item, 'padrao')) problemas.push(`a chave ${chave} sem o valor de fábrica (padrao)`);
      } else if (eCaminhoDeConfiguracao(chave)) {
        const deFabrica = [item.padrao ?? []].flat().map(nomeCurto);
        if (![item.ligada ?? []].flat().map(nomeCurto).some((fonte) => !deFabrica.includes(fonte))) {
          problemas.push(`a configuração ${chave} sem fonte que a ligue além do valor de fábrica (ligada)`);
        }
      } else {
        problemas.push(`a chave ${JSON.stringify(chave)} não é variável KIT_* nem caminho de configuração`);
      }
    }
    if (modo === null && chaves.length === 0) problemas.push('nem modo nem chave');
  }
  if (typeof evidencia?.arquivo !== 'string' || typeof evidencia?.trecho !== 'string') {
    problemas.push('sem a âncora da condição (evidencia, com arquivo e trecho)');
  }
  return problemas;
}

/**
 * A marca da condição (RQ-27, D41), ou `null` na peça sem condição: o modo pelo termo da grade; a chave booleana pela
 * variável e pelo valor de fábrica; a lista vazia de fábrica pelo nome; a configuração de pacote pelo caminho e pela
 * fonte que falta de fábrica; e "de fábrica" depois da última chave.
 */
function marcaDaCondicao(peca) {
  const condicao = peca?.disponibilidade;
  if (!condicao) return null;
  const texto = (idioma) => {
    const partes = condicao.modo ? [MARCA_DO_MODO[condicao.modo][idioma]] : [];
    const chaves = (condicao.chaves ?? []).map(({ chave, padrao, ligada }) => {
      if (eVariavelDoKit(chave)) {
        if (typeof padrao === 'boolean') return `${chave}=${padrao}`;
        return [padrao ?? []].flat().length ? chave : `${chave} ${idioma === 'pt' ? 'vazia' : 'empty'}`;
      }
      const deFabrica = [padrao ?? []].flat().map(nomeCurto);
      const falta = [ligada ?? []].flat().map(nomeCurto).filter((nome) => !deFabrica.includes(nome));
      return `${chave} ${idioma === 'pt' ? 'sem' : 'without'} ${falta.join(', ')}`;
    });
    if (chaves.length) partes.push(`${chaves.join(' · ')} ${idioma === 'pt' ? 'de fábrica' : 'by default'}`);
    return partes.join(' · ');
  };
  return { pt: texto('pt'), en: texto('en') };
}

/**
 * A peça entra no diagrama só se, em cada modo dele, o painel a registra e ela fica disponível — com a chave ligada
 * (P-15): a condição só de chave não tira a peça de modo nenhum, e a de modo a tira do outro (Setup Global do `04`).
 */
const entraNosModos = (peca, modos) =>
  Boolean(peca) && modos.every((modo) => (peca.registrada ?? []).includes(modo) && (!peca.disponibilidade?.modo || peca.disponibilidade.modo === modo));

/** O card "Condições de disponibilidade" (R44): uma linha por peça condicional desenhada, com a marca e a prova. */
function cardDeCondicoes(desenhadas) {
  const condicionais = [...new Map(desenhadas.filter((peca) => peca?.disponibilidade).map((peca) => [peca.id, peca])).values()];
  if (!condicionais.length) return [];
  return [{
    dot: 'amber',
    title: { pt: 'Condições de disponibilidade', en: 'Availability conditions' },
    items: condicionais.map((peca) => {
      const { arquivo, trecho } = peca.disponibilidade.evidencia;
      const { line } = linhaDaAncora(arquivo, trecho);
      const marca = marcaDaCondicao(peca);
      return { pt: `${peca.nome}: ${marca.pt} — ${arquivo}:${line} — ${trecho}`, en: `${peca.nome}: ${marca.en} — ${arquivo}:${line} — ${trecho}` };
    }),
  }];
}

/**
 * O termo da grade da página para os modos em que o diagrama vale (P-09, D45): o de um modo, ou "os dois modos" no
 * compartilhado, em qualquer ordem — e nunca o primeiro modo da lista.
 */
const termoDosModos = (modos) =>
  modos.length === 2 ? { pt: 'os dois modos', en: 'both modes' } : modos[0] === 'com' ? { pt: 'com tenant ativo', en: 'tenancy on' } : { pt: 'sem tenant', en: 'tenancy off' };

/** Árvore de registro (tree, para a direita): tudo o que o painel registra no modo do diagrama. */
function arvoreDeRegistro(diagrama) {
  const { pecas, ligacoes } = carregarModelo();
  const [painel] = diagrama.paineis;
  const raiz = `painel:${painel}`;
  // o que o painel registra em cada modo do diagrama e fica disponível nele (a peça condicional, Adendo 2): a árvore
  // compartilhada vale nos dois modos e não desenha o registro de um modo só (Setup Global do `04`)
  const registradas = ligacoes.filter((l) => l.de === raiz && l.verbo === 'registra' && diagrama.modos.every((m) => l.modos.includes(m)) && entraNosModos(pecas.get(l.para), diagrama.modos));
  const GRUPOS = [
    ['recurso', { pt: 'Recursos', en: 'Resources' }],
    ['pagina', { pt: 'Páginas', en: 'Pages' }],
    ['widget', { pt: 'Widgets', en: 'Widgets' }],
    ['plugin', { pt: 'Plugins', en: 'Plugins' }],
    ['middleware', { pt: 'Middlewares', en: 'Middlewares' }],
  ];
  const nodes = [{
    id: idArchify(raiz),
    label: pecas.get(raiz).nome,
    sublabel: termoDosModos(diagrama.modos),
    sources: pecas.get(raiz).evidencia.slice(0, 3),
  }];
  for (const [tipo, rotulo] of GRUPOS) {
    const membros = registradas
      .map((l) => pecas.get(l.para))
      .filter((peca) => peca.tipo === tipo)
      .sort((a, b) => a.nome.localeCompare(b.nome));
    if (!membros.length) continue;
    nodes.push({ id: `grupo-${tipo}`, label: { pt: `${rotulo.pt} (${membros.length})`, en: `${rotulo.en} (${membros.length})` }, parent: idArchify(raiz) });
    for (const peca of membros) {
      const features = peca.features.length ? ` · ${tagDasFeatures(peca.features)}` : '';
      const sub = sublabelDaPeca(peca);
      nodes.push({
        id: idArchify(peca.id),
        label: peca.nome,
        sublabel: { pt: sub.pt + features, en: sub.en + features },
        parent: `grupo-${tipo}`,
        sources: peca.evidencia.slice(0, 3),
      });
    }
  }
  const provas = registradas.map((l) => ({
    de: idArchify(raiz),
    para: idArchify(l.para),
    nomeDe: pecas.get(raiz).nome,
    nomePara: pecas.get(l.para).nome,
    rotulo: { pt: VERBO.registra.pt, en: VERBO.registra.en },
    prova: l.evidencia,
  }));
  return {
    entrada: {
      schema_version: 1,
      diagram_type: 'tree',
      meta: { quality_profile: diagrama.qualidade ?? 'standard' },
      layout: { direction: 'right' },
      nodes,
      cards: [...cardDeFeatures(registradas.map((l) => pecas.get(l.para))), ...cardDeCondicoes(registradas.map((l) => pecas.get(l.para)))],
    },
    provas,
  };
}

/** A peça desenhada e o membro de agrupamento de um mapa construído que não estão no modelo são recusados (P-14). */
function conferirPecasDoMapa(diagrama) {
  const { pecas } = carregarModelo();
  for (const { id } of diagrama.pecas) {
    if (!pecas.has(id)) throw new Error(`a peça ${id} do diagrama não está no modelo`);
  }
  for (const g of diagrama.agrupamentos ?? []) {
    for (const membro of g.membros) {
      if (!pecas.has(membro)) throw new Error(`o membro ${membro} do agrupamento ${g.id} não está no modelo`);
    }
  }
}

/**
 * Mapa de arquitetura (resumo ou domínio): as peças do recorte, os agrupamentos que nomeiam os membros
 * (P-10) e as portas para outros mapas, na grade do arquivo; as ligações são as do modelo entre o que está
 * desenhado, no painel e no modo do diagrama, com o verbo no traço e a prova em card.
 */
function mapaDeArquitetura(diagrama) {
  const { pecas, ligacoes } = carregarModelo();
  conferirPecasDoMapa(diagrama);
  // O diagrama só desenha e agrupa a peça que, em cada modo dele, o painel registra e fica disponível (P-09; Adendo 2).
  const existe = (id) => entraNosModos(pecas.get(id), diagrama.modos);
  diagrama = {
    ...diagrama,
    pecas: diagrama.pecas.filter(({ id }) => existe(id)),
    agrupamentos: (diagrama.agrupamentos ?? []).map((g) => ({ ...g, membros: g.membros.filter(existe) })).filter((g) => g.membros.length),
  };
  const desenhadas = new Set(diagrama.pecas.map(({ id }) => id));
  const grupoDe = new Map(diagrama.agrupamentos.flatMap((g) => g.membros.map((membro) => [membro, g.id])));
  const ponta = (id) => (desenhadas.has(id) ? idArchify(id) : grupoDe.get(id) ?? null);
  const TAMANHO = diagrama.layout?.tamanho ?? [210, 56];
  // A caixa cresce com o rótulo (o archify recusa rótulo mais largo que a caixa, ~6,6 px por caractere) e com o rótulo
  // secundário, que leva a marca da condição (~3,6 px por caractere a 6 px; D41), e a célula da grade acompanha a maior.
  const largura = (peca) =>
    Math.max(TAMANHO[0], Math.ceil(String(peca.nome).length * 6.8) + 24, ...Object.values(sublabelDaPeca(peca)).map((texto) => Math.ceil(texto.length * 3.8) + 12));
  const maior = Math.max(TAMANHO[0], ...diagrama.pecas.map(({ id }) => largura(pecas.get(id))));

  const components = [
    ...diagrama.pecas.map(({ id, row, col }) => {
      const peca = pecas.get(id);
      return {
        id: idArchify(id),
        type: TIPO_ARCHIFY[peca.tipo],
        label: peca.nome,
        sublabel: sublabelDaPeca(peca),
        ...(peca.features.length ? { tag: tagDasFeatures(peca.features) } : {}),
        sources: peca.evidencia.slice(0, 3),
        row,
        col,
        size: [largura(peca), TAMANHO[1]],
      };
    }),
    ...diagrama.agrupamentos.map((g) => ({
      id: g.id,
      type: g.tipo ?? 'backend',
      label: g.rotulo,
      sublabel: g.membros.length === 1 ? { pt: '1 peça', en: '1 piece' } : { pt: `${g.membros.length} peças`, en: `${g.membros.length} pieces` },
      // a procedência da caixa é a dos membros: a prova dos três primeiros
      sources: g.membros.map((membro) => pecas.get(membro)?.evidencia[0]).filter(Boolean).slice(0, 3),
      row: g.row,
      col: g.col,
      size: TAMANHO,
    })),
    ...(diagrama.portas ?? []).map((porta) => ({
      id: `porta-${porta.para}`,
      type: 'external',
      label: porta.rotulo,
      sublabel: { pt: 'outro mapa', en: 'another map' },
      row: porta.row,
      col: porta.col,
      size: TAMANHO,
    })),
  ];

  const connections = new Map();
  const provas = [];
  const verbos = new Set();
  for (const l of ligacoes) {
    // o compartilhado não desenha a ligação de um modo só (P-09): ela vale em cada modo do diagrama
    if (!diagrama.modos.every((m) => l.modos.includes(m))) continue;
    // o diagrama de painel desenha as ligações do painel; o do kit inteiro, as que não são de painel nenhum
    const doEscopo = diagrama.paineis.length ? l.paineis.some((p) => diagrama.paineis.includes(p)) : !l.paineis.length;
    if (!doEscopo) continue;
    const de = ponta(l.de);
    const para = ponta(l.para);
    if (!de || !para || de === para) continue;
    const id = `${de}--${para}--${VERBO[l.verbo].variante}`;
    if (!connections.has(id)) connections.set(id, { id, from: de, to: para, variant: VERBO[l.verbo].variante });
    verbos.add(l.verbo);
    provas.push({
      de,
      para,
      nomeDe: pecas.get(l.de).nome,
      nomePara: pecas.get(l.para).nome,
      rotulo: { pt: VERBO[l.verbo].pt, en: VERBO[l.verbo].en },
      prova: l.evidencia,
    });
  }

  const cards = [];
  if (verbos.size) {
    cards.push({
      dot: 'cyan',
      title: { pt: 'Como ler as setas', en: 'How to read the arrows' },
      items: [...verbos].map((v) => ({ pt: `${VERBO[v].traco.pt}: ${VERBO[v].pt}`, en: `${VERBO[v].traco.en}: ${VERBO[v].en}` })),
    });
  }
  cards.push(...cardDeFeatures(diagrama.pecas.map(({ id }) => pecas.get(id))));
  for (const g of diagrama.agrupamentos) {
    cards.push({
      dot: 'violet',
      title: { pt: `Quem está em ${g.rotulo.pt}`, en: `Who is in ${g.rotulo.en}` },
      // o membro condicional leva a marca na linha dele (D41)
      items: g.membros.map((membro) => {
        const marca = marcaDaCondicao(pecas.get(membro));
        const nome = pecas.get(membro)?.nome ?? membro;
        return marca ? { pt: `${nome} — ${marca.pt}`, en: `${nome} — ${marca.en}` } : nome;
      }),
    });
  }
  cards.push(...cardDeCondicoes([...diagrama.pecas.map(({ id }) => pecas.get(id)), ...diagrama.agrupamentos.flatMap((g) => g.membros.map((membro) => pecas.get(membro)))]));

  return {
    entrada: {
      schema_version: 1,
      diagram_type: 'architecture',
      meta: { quality_profile: diagrama.qualidade ?? 'standard' },
      layout: {
        mode: 'grid',
        ...Object.fromEntries(Object.entries(diagrama.layout ?? {}).filter(([k]) => k !== 'tamanho' && k !== 'teto')),
        cellW: Math.max(diagrama.layout?.cellW ?? 0, maior + 20),
      },
      components,
      connections: [...connections.values()],
      cards,
    },
    provas,
  };
}

const CONSTRUTORES = { registro: arvoreDeRegistro, mapa: mapaDeArquitetura };

/** A entrada do archify de um diagrama, num idioma. */
function entradaDoArchify(diagrama, idioma, catalogoPt) {
  const { slug, tipo, titulo, paineis, modos, construtor, ...resto } = diagrama;
  const { entrada, provas } = construtor ? CONSTRUTORES[construtor](diagrama) : provasDasLigacoes(tipo, resto);

  const ir = projetar(entrada, idioma);
  if (provas.length) ir.cards = [...(ir.cards ?? []), ...cardsDeProva(provas, ir, tipo, idioma)];
  const traducoes = {
    ...(idioma === 'pt' ? { ...catalogoPt, ...interfaceDoKit.pt } : {}),
    ...interfaceDoKit.tipos[tipo]?.[idioma],
    ...ir.meta?.translations,
  };
  ir.meta = {
    ...ir.meta,
    // Alguns tipos exigem `meta.output`; o destino real é o da linha de comando, que prevalece.
    output: `${slug}.html`,
    title: projetar(titulo, idioma),
    locale: IDIOMAS[idioma],
    repository: repositorio,
    ...(Object.keys(traducoes).length ? { translations: traducoes } : {}),
  };
  return ir;
}

/**
 * O nome de tipo de nó que o catálogo do archify não tem: no commit fixado, só os tipos do `architecture` e dos
 * fluxos têm `viewer.kind.*`, e o visualizador escreve o `class` e o `abstract` crus, em inglês, no passaporte e
 * no filtro de tipos (R31). O archify descarta de `meta.translations` toda chave fora do catálogo canônico, então
 * as chaves de `foraDoCatalogo` do `interface.json` entram no catálogo embutido do HTML depois do render.
 */
function completarCatalogoEmbutido(destino, idioma) {
  const chaves = interfaceDoKit.foraDoCatalogo?.[idioma];
  if (!chaves) return;
  const embutido = /(<script id="archify-i18n-data" type="application\/json">)([\s\S]*?)(<\/script>)/;
  const html = readFileSync(destino, 'utf8');
  const achado = html.match(embutido);
  if (!achado) throw new Error('o HTML não tem o catálogo embutido (archify-i18n-data)');
  const dados = JSON.parse(achado[2]);
  // o nome do kit vence: é o que o conferidor espera no passaporte (CT-B04), mesmo se o archify ganhar a chave (R2-5)
  dados.messages = { ...dados.messages, ...chaves };
  const json = JSON.stringify(dados).replace(/</g, '\\u003c');
  writeFileSync(destino, html.replace(embutido, (_, abre, __, fecha) => `${abre}${json}${fecha}`));
}

/**
 * O que, na pasta de saída, o gerador não gerou: tudo o que não é `pt/` ou `en/` com o HTML do mapa dentro — e o
 * recibo do `compare`, que uma geração interrompida deixa ao lado do HTML (R2-3). A pasta que não existe é aceita;
 * o arquivo no lugar da pasta, não.
 */
function arquivosQueOGeradorNaoGerou(saida) {
  if (!existsSync(saida)) return [];
  if (!statSync(saida).isDirectory()) return [saida];
  const estranhos = [];
  for (const nome of readdirSync(saida)) {
    const caminho = join(saida, nome);
    if (!Object.hasOwn(IDIOMAS, nome) || !statSync(caminho).isDirectory()) {
      estranhos.push(nome);
      continue;
    }
    for (const arquivo of readdirSync(caminho)) {
      if (!/\.(html|receipt\.json)$/.test(arquivo) || !statSync(join(caminho, arquivo)).isFile()) estranhos.push(`${nome}/${arquivo}`);
    }
  }
  return estranhos;
}

/**
 * Um mapa na forma que o comparativo recebe: uma seta por par de pontas, com o traço comum. O `compare` do archify
 * falha com seta paralela (a seta por verbo da D36), e a casca dele não tem legenda de verbo nem cards (P-04); o
 * verbo de cada ligação fica nos dois mapas publicados ao lado.
 */
function paraOComparativo(entrada) {
  const pares = new Set();
  const connections = [];
  for (const conexao of entrada.connections ?? []) {
    const par = `${conexao.from}--${conexao.to}`;
    if (pares.has(par)) continue;
    pares.add(par);
    connections.push({ ...conexao, id: par, variant: 'default' });
  }
  return { ...entrada, connections };
}

function archify(cli, args) {
  try {
    execFileSync(process.execPath, [cli, ...args, '--repo-root', RAIZ], {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, ARCHIFY_UPDATE_CHECK_DISABLED: '1' },
    });
  } catch (erro) {
    throw new Error(String(erro.stderr || erro.stdout || erro.message).trim().split('\n').slice(0, 12).join('\n'));
  }
}

/**
 * Sem argumentos, gera todos os diagramas em `dist/mapa/` (o build). Para quem escreve diagramas:
 * `node mapa/gerar.mjs --saida <pasta> [slug …]` gera só os slugs pedidos noutra pasta, sem apagar o
 * `dist/` — vários autores conferem diagramas ao mesmo tempo sem um apagar o do outro.
 */
function gerar(argumentos) {
  // Tudo o que o gerador confere sozinho vem antes de qualquer efeito — apagar, baixar o archify, renderizar (P-14);
  // só o layout fica para o archify, que o confere ao renderizar.
  const posicao = argumentos.indexOf('--saida');
  const pasta = posicao >= 0 ? argumentos[posicao + 1] : undefined;
  if (posicao >= 0 && (pasta === undefined || pasta.startsWith('--'))) {
    throw new Error('[mapa] ERRO --saida: falta a pasta (uso: node mapa/gerar.mjs --saida <pasta> [slug …])');
  }
  const saida = posicao >= 0 ? resolve(pasta) : SAIDA;
  const pedidos = posicao >= 0 ? argumentos.filter((_, indice) => indice !== posicao && indice !== posicao + 1) : argumentos;

  const diagramas = readdirSync(DIAGRAMAS)
    .filter((nome) => nome.endsWith('.json'))
    .sort()
    .map((nome) => lerJson(join(DIAGRAMAS, nome)));
  carregarModelo();
  const porSlug = new Map(diagramas.map((diagrama) => [diagrama.slug, diagrama]));
  const desconhecidos = pedidos.filter((slug) => !porSlug.has(slug));
  if (desconhecidos.length) throw new Error(`[mapa] ERRO — diagrama inexistente: ${desconhecidos.join(', ')}`);
  // Os modos de cada diagrama, de que o termo da raiz e o filtro do desenho dependem: um ou os dois, sem repetir (P-09).
  for (const { slug, modos } of diagramas) {
    if (!Array.isArray(modos) || !modos.length || new Set(modos).size !== modos.length || !modos.every((m) => m === 'sem' || m === 'com')) {
      throw new Error(`[mapa] ERRO ${slug} — modos ${JSON.stringify(modos)}: um ou os dois, sem repetir, só "sem" e "com"`);
    }
  }
  // A peça fora do modelo também, em todo mapa construído, como o JSON quebrado (RR-01).
  for (const diagrama of diagramas.filter((d) => d.construtor === 'mapa')) {
    try {
      conferirPecasDoMapa(diagrama);
    } catch (erro) {
      throw new Error(`[mapa] ERRO ${diagrama.slug} — ${erro.message}`);
    }
  }
  // Sem slug, a pasta de saída é refeita: só se ela não tiver nada que o gerador não gerou (o HTML do mapa em pt/ e en/).
  if (!pedidos.length) {
    const estranhos = arquivosQueOGeradorNaoGerou(saida);
    if (estranhos.length) {
      throw new Error(
        `[mapa] ERRO --saida ${saida}: a pasta tem o que o gerador não gerou (${estranhos.slice(0, 5).join(', ')}${estranhos.length > 5 ? ', …' : ''}), e ele não apaga isso; use uma pasta vazia, nova ou só com o HTML do mapa`,
      );
    }
  }
  // Por último, o que custa ler o código citado: cada diagrama a gerar, nos dois idiomas, montado sem o archify — a
  // prova que não resolve e a ligação sem prova param aqui, com o diagrama nomeado (P-14).
  const aGerar = pedidos.length ? diagramas.filter((diagrama) => pedidos.includes(diagrama.slug)) : diagramas;
  for (const slug of new Set(aGerar.flatMap((d) => (d.tipo === 'compare' ? [d.base, d.cabeca] : [d.slug])))) {
    for (const idioma of Object.keys(IDIOMAS)) {
      try {
        entradaDoArchify(porSlug.get(slug), idioma, {});
      } catch (erro) {
        throw new Error(`[mapa] ERRO ${slug} ${idioma} — ${erro.message}`);
      }
    }
  }

  const cli = obterArchify();
  const catalogoPt = lerJson(fileURLToPath(new URL('../.archify/archify/examples/locales/pt.json', import.meta.url)));
  const temporaria = mkdtempSync(join(tmpdir(), 'mapa-'));

  if (!pedidos.length) {
    for (const idioma of Object.keys(IDIOMAS)) rmSync(join(saida, idioma), { recursive: true, force: true });
  }
  try {
    for (const idioma of Object.keys(IDIOMAS)) {
      mkdirSync(join(saida, idioma), { recursive: true });

      for (const diagrama of aGerar) {
        const { slug, tipo } = diagrama;
        const destino = join(saida, idioma, `${slug}.html`);
        try {
          if (tipo === 'compare') {
            const [base, cabeca] = [diagrama.base, diagrama.cabeca].map((alvo) => {
              const caminho = join(temporaria, `${slug}.${alvo}.${idioma}.json`);
              writeFileSync(caminho, JSON.stringify(paraOComparativo(entradaDoArchify(porSlug.get(alvo), idioma, catalogoPt))));
              return caminho;
            });
            archify(cli, ['compare', 'architecture', base, cabeca, destino]);
            // O `compare` exige gravar o recibo ao lado do HTML; ele sai daqui para que nenhum
            // arquivo do archify além do HTML chegue ao `dist/`.
            rmSync(destino.replace(/\.html$/, '.receipt.json'), { force: true });
            console.log(`[mapa] ${slug} ${idioma} — compare, ${diagrama.base} → ${diagrama.cabeca}, ${statSync(destino).size} B`);
            continue;
          }
          const ir = entradaDoArchify(diagrama, idioma, catalogoPt);
          const entrada = join(temporaria, `${slug}.${idioma}.json`);
          writeFileSync(entrada, JSON.stringify(ir));
          archify(cli, ['render', tipo, entrada, destino]);
          completarCatalogoEmbutido(destino, idioma);
          const pecas = ir[PECAS[tipo]]?.length ?? 0;
          console.log(`[mapa] ${slug} ${idioma} — ${tipo}, ${pecas} peças, ${statSync(destino).size} B`);
        } catch (erro) {
          console.error(`[mapa] ERRO ${slug} ${idioma} — ${erro.message}`);
          process.exitCode = 1;
          return;
        }
      }
    }
  } finally {
    rmSync(temporaria, { recursive: true, force: true });
  }
}

export { carregarModelo, entradaDoArchify, idArchify, paraOComparativo };

// Roda só quando chamado direto (`node mapa/gerar.mjs`); o posicionador importa as funções acima.
const caminhoReal = (caminho) => {
  try {
    return realpathSync(caminho);
  } catch {
    return resolve(caminho);
  }
};
if (process.argv[1] && caminhoReal(process.argv[1]) === caminhoReal(fileURLToPath(import.meta.url))) {
  try {
    gerar(process.argv.slice(2));
  } catch (erro) {
    console.error(erro.message);
    process.exitCode = 1;
  }
}
