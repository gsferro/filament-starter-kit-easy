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
import { mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
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

const lerJson = (caminho) => JSON.parse(readFileSync(caminho, 'utf8'));
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

function linhaDaAncora(arquivo, trecho) {
  if (!textos.has(arquivo)) textos.set(arquivo, git('show', `${revisao}:${arquivo}`));
  const texto = textos.get(arquivo);
  const posicao = texto.indexOf(trecho);
  if (posicao < 0) {
    throw new Error(`o trecho ${JSON.stringify(trecho)} não está em ${arquivo} na revisão ${revisao.slice(0, 7)}`);
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
    const { arquivo, trecho, ...resto } = valor;
    return { ...linhaDaAncora(arquivo, trecho), ...projetar(resto, idioma) };
  }
  return Object.fromEntries(chaves.map((chave) => [chave, projetar(valor[chave], idioma)]));
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
const carregarModelo = () =>
  (modelo ??= {
    pecas: new Map(lerJson(fileURLToPath(new URL('./pecas.json', import.meta.url))).map((peca) => [peca.id, peca])),
    ligacoes: lerJson(fileURLToPath(new URL('./ligacoes.json', import.meta.url))),
  });

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

/** "recurso · F-29 · F-30" ou "plugin · filament-auth-designer": o tipo, o pacote e as F-nn da peça (D29). */
function sublabelDaPeca(peca) {
  const pacote = peca.origem === 'pacote' && peca.pacote ? ` · ${peca.pacote.split('/').pop()}` : '';
  return { pt: `${ROTULO_DO_TIPO[peca.tipo].pt}${pacote}`, en: `${ROTULO_DO_TIPO[peca.tipo].en}${pacote}` };
}

/** Árvore de registro (tree, para a direita): tudo o que o painel registra no modo do diagrama. */
function arvoreDeRegistro(diagrama) {
  const { pecas, ligacoes } = carregarModelo();
  const [painel] = diagrama.paineis;
  const [modo] = diagrama.modos;
  const raiz = `painel:${painel}`;
  const registradas = ligacoes.filter((l) => l.de === raiz && l.verbo === 'registra' && l.modos.includes(modo));
  const GRUPOS = [
    ['recurso', { pt: 'Recursos', en: 'Resources' }],
    ['pagina', { pt: 'Páginas', en: 'Pages' }],
    ['widget', { pt: 'Widgets', en: 'Widgets' }],
    ['plugin', { pt: 'Plugins', en: 'Plugins' }],
    ['middleware', { pt: 'Middlewares', en: 'Middlewares' }],
  ];
  const nodes = [{
    id: idArchify(raiz),
    label: { pt: `Painel /${painel}`, en: `Panel /${painel}` },
    sublabel: modo === 'com' ? { pt: 'com tenant ativo', en: 'tenancy on' } : { pt: 'sem tenant', en: 'tenancy off' },
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
  const provas = registradas.map((l) => ({ de: idArchify(raiz), para: idArchify(l.para), rotulo: { pt: VERBO.registra.pt, en: VERBO.registra.en }, prova: l.evidencia }));
  const comFeatures = registradas.map((l) => pecas.get(l.para)).filter((peca) => peca.features.length);
  const cards = comFeatures.length
    ? [{ dot: 'emerald', title: { pt: 'F-nn de cada peça', en: 'F-nn of each piece' }, items: comFeatures.map((peca) => `${peca.nome}: ${peca.features.join(', ')}`) }]
    : [];
  return {
    entrada: { schema_version: 1, diagram_type: 'tree', meta: { quality_profile: diagrama.qualidade ?? 'standard' }, layout: { direction: 'right' }, nodes, cards },
    provas,
  };
}

/**
 * Mapa de arquitetura (resumo ou domínio): as peças do recorte, os agrupamentos que nomeiam os membros
 * (P-10) e as portas para outros mapas, na grade do arquivo; as ligações são as do modelo entre o que está
 * desenhado, no painel e no modo do diagrama, com o verbo no traço e a prova em card.
 */
function mapaDeArquitetura(diagrama) {
  const { pecas, ligacoes } = carregarModelo();
  const modo = diagrama.modos.length === 1 ? diagrama.modos[0] : null;
  // Diagrama de um modo só não desenha nem agrupa peça que o painel não registra nesse modo (P-09).
  const existe = (id) => !modo || (pecas.get(id)?.registrada ?? []).includes(modo);
  diagrama = {
    ...diagrama,
    pecas: diagrama.pecas.filter(({ id }) => existe(id)),
    agrupamentos: (diagrama.agrupamentos ?? []).map((g) => ({ ...g, membros: g.membros.filter(existe) })),
  };
  const desenhadas = new Set(diagrama.pecas.map(({ id }) => id));
  const grupoDe = new Map(diagrama.agrupamentos.flatMap((g) => g.membros.map((membro) => [membro, g.id])));
  const ponta = (id) => (desenhadas.has(id) ? idArchify(id) : grupoDe.get(id) ?? null);
  const TAMANHO = diagrama.layout?.tamanho ?? [210, 56];

  const components = [
    ...diagrama.pecas.map(({ id, row, col }) => {
      const peca = pecas.get(id);
      if (!peca) throw new Error(`a peça ${id} não está no modelo`);
      return {
        id: idArchify(id),
        type: TIPO_ARCHIFY[peca.tipo],
        label: peca.nome,
        sublabel: sublabelDaPeca(peca),
        ...(peca.features.length ? { tag: tagDasFeatures(peca.features) } : {}),
        sources: peca.evidencia.slice(0, 3),
        row,
        col,
        size: TAMANHO,
      };
    }),
    ...(diagrama.agrupamentos ?? []).map((g) => ({
      id: g.id,
      type: g.tipo ?? 'backend',
      label: g.rotulo,
      sublabel: { pt: `${g.membros.length} peças`, en: `${g.membros.length} pieces` },
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
    if (modo && !l.modos.includes(modo)) continue;
    if (!l.paineis.some((p) => diagrama.paineis.includes(p))) continue;
    const de = ponta(l.de);
    const para = ponta(l.para);
    if (!de || !para || de === para) continue;
    const id = `${de}--${para}`;
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
  // A F-nn de cada peça desenhada, inteira e à vista sem zoom: o archify esconde o tag no carregamento (D29).
  const comFeatures = diagrama.pecas.map(({ id }) => pecas.get(id)).filter((peca) => peca.features.length);
  if (comFeatures.length) {
    cards.push({
      dot: 'emerald',
      title: { pt: 'F-nn de cada peça', en: 'F-nn of each piece' },
      items: comFeatures.map((peca) => `${peca.nome}: ${peca.features.join(', ')}`),
    });
  }
  for (const g of diagrama.agrupamentos ?? []) {
    cards.push({
      dot: 'violet',
      title: { pt: `Quem está em ${g.rotulo.pt}`, en: `Who is in ${g.rotulo.en}` },
      items: g.membros.map((membro) => pecas.get(membro)?.nome ?? membro),
    });
  }

  return {
    entrada: {
      schema_version: 1,
      diagram_type: 'architecture',
      meta: { quality_profile: diagrama.qualidade ?? 'standard' },
      layout: { mode: 'grid', ...Object.fromEntries(Object.entries(diagrama.layout ?? {}).filter(([k]) => k !== 'tamanho')) },
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
  const posicao = argumentos.indexOf('--saida');
  const saida = posicao >= 0 ? resolve(argumentos[posicao + 1]) : SAIDA;
  const pedidos = posicao >= 0 ? argumentos.filter((_, indice) => indice !== posicao && indice !== posicao + 1) : argumentos;

  const cli = obterArchify();
  const catalogoPt = lerJson(fileURLToPath(new URL('../.archify/archify/examples/locales/pt.json', import.meta.url)));
  const diagramas = readdirSync(DIAGRAMAS)
    .filter((nome) => nome.endsWith('.json'))
    .sort()
    .map((nome) => lerJson(join(DIAGRAMAS, nome)));
  const porSlug = new Map(diagramas.map((diagrama) => [diagrama.slug, diagrama]));
  const desconhecidos = pedidos.filter((slug) => !porSlug.has(slug));
  if (desconhecidos.length) throw new Error(`[mapa] ERRO — diagrama inexistente: ${desconhecidos.join(', ')}`);
  const aGerar = pedidos.length ? diagramas.filter((diagrama) => pedidos.includes(diagrama.slug)) : diagramas;
  const temporaria = mkdtempSync(join(tmpdir(), 'mapa-'));

  if (!pedidos.length) rmSync(saida, { recursive: true, force: true });
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
              writeFileSync(caminho, JSON.stringify(entradaDoArchify(porSlug.get(alvo), idioma, catalogoPt)));
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

export { carregarModelo, entradaDoArchify, idArchify };

// Roda só quando chamado direto (`node mapa/gerar.mjs`); o posicionador importa as funções acima.
if (resolve(process.argv[1] ?? '') === fileURLToPath(import.meta.url)) {
  try {
    gerar(process.argv.slice(2));
  } catch (erro) {
    console.error(erro.message);
    process.exitCode = 1;
  }
}
