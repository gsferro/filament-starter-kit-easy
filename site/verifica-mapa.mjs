/**
 * Confere o mapa interativo do kit no site construído, num navegador real — ADR-06 e D17 da wiki
 * `mapa-interativo-do-kit`.
 *
 * Mora fora do `verifica-acessibilidade.mjs` (D17): aquele conferidor já carrega os `[CT-B01]` e
 * `[CT-B02]` da wiki `diagramas-da-arquitetura`, e os CT-B desta wiki também começam em 01 — o
 * mesmo ID no mesmo arquivo nomearia dois cenários. O `pest-plugin-browser` não serve site estático.
 *
 * O que ele prova (`05-casos-de-teste-browser.md`), com o ID na mensagem de cada reprovação:
 *  - CT-B01  cada diagrama abre com as peças e os rótulos da fonte, a busca montada, a F-nn no desenho,
 *            sem erro, sem exceção e sem recurso de fora;
 *  - CT-B02  cada ligação desenhada tem o item de prova, inteiro e à vista, e as arestas são as da fonte;
 *  - CT-B03  a busca acha a peça da amostra, e o passaporte tem o link para a linha, no commit do build;
 *  - CT-B04  a interface do visualizador fala o idioma da página;
 *  - CT-B05  AMOSTRA de acessibilidade (axe): um HTML do mapa por tipo, idioma e tema, mais o
 *            comparativo no escuro — a ADR-06, com as duas tolerâncias abaixo;
 *  - CT-B06  controles sintéticos da tolerância e o controle de processo (código de saída);
 *  - CT-B07  o catálogo pt entregue ao visualizador não copia o texto en, fora a lista fechada.
 *
 * O ESPERADO vem da FONTE (`mapa/diagramas/`, `mapa/pecas.json`, `mapa/ligacoes.json`), nunca do
 * `dist/`: o oráculo não pode sair do artefato que ele confere. As rotas vêm dos arquivos de diagrama,
 * e não de uma varredura do `dist/` por `index.html`: um diagrama que o build deixou de gerar reprova
 * aqui, em vez de sumir.
 *
 * Reprova qualquer violação `serious`/`critical` de WCAG 2.1 AA, qualquer `console.error` e
 * qualquer erro de página, EXCETO exatamente dois defeitos do archify, reconhecidos pela regra E
 * pelo elemento — nunca pelo texto do seletor que o axe devolve, que muda com o tipo:
 *  1. `nested-interactive` no `<svg>` raiz do diagrama (`role="img"` com filhos focáveis);
 *  2. `color-contrast` na contagem de grupo do índice de nós (`.node-outline-heading > small`),
 *     2,5:1 nos dois temas.
 * Uma versão nova do archify que mude o elemento de um deles reprova aqui — e é o desejado: a
 * troca de versão (ADR-02) passa a exigir rever esta tolerância.
 *
 * Variáveis de ambiente (só o controle de processo do CT-B06 as usa, para rodar este mesmo arquivo
 * como processo filho sobre uma pasta de diagramas e uma prévia de controle):
 *  VERIFICA_MAPA_DIAGRAMAS, VERIFICA_MAPA_DIST, VERIFICA_MAPA_TIMEOUT_MS e VERIFICA_MAPA_CONTROLE.
 */
import { chromium } from 'playwright';
import { execFileSync, spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';

const PORTA = process.argv[2] || '4321';
const BASE = (process.env.DOCS_BASE || '').replace(/\/+$/, '');
const DIST = resolve(process.env.VERIFICA_MAPA_DIST || 'dist');
const DIAGRAMAS = resolve(process.env.VERIFICA_MAPA_DIAGRAMAS || 'mapa/diagramas');
const MODELO = resolve('mapa');
const ARCHIFY = resolve('.archify/archify');
const AXE = readFileSync(resolve('node_modules/axe-core/axe.min.js'), 'utf8');
const RENDER_TIMEOUT_MS = Number(process.env.VERIFICA_MAPA_TIMEOUT_MS || 15_000);
const MODO_CONTROLE = process.env.VERIFICA_MAPA_CONTROLE === '1';
const ORIGEM = `http://localhost:${PORTA}`;
const IDIOMAS = ['pt', 'en'];

const lerJson = (caminho) => JSON.parse(readFileSync(caminho, 'utf8'));
const norm = (texto) => String(texto).replace(/\s+/g, ' ').trim();
const texto = (valor, idioma) => (valor !== null && typeof valor === 'object' ? valor[idioma] : valor);

const violacoes = [];
const reprovar = (ct, mensagem) => violacoes.push(`[${ct}] ${mensagem}`);
const conferencias = {};
const contar = (ct, quantidade = 1) => {
  conferencias[ct] = (conferencias[ct] ?? 0) + quantidade;
};

// ---------------------------------------------------------------------------------------------------
// A fonte
// ---------------------------------------------------------------------------------------------------
const diagramas = readdirSync(DIAGRAMAS)
  .filter((nome) => nome.endsWith('.json'))
  .sort()
  .map((nome) => lerJson(join(DIAGRAMAS, nome)));

const porSlug = new Map(diagramas.map((d) => [d.slug, d]));
const pecasDoModelo = new Map(lerJson(join(MODELO, 'pecas.json')).map((peca) => [peca.id, peca]));
const ligacoesDoModelo = lerJson(join(MODELO, 'ligacoes.json'));

/** O primeiro diagrama de cada tipo, em ordem de nome de arquivo. */
const representantes = new Map();
for (const { tipo, slug } of diagramas) {
  if (!representantes.has(tipo)) {
    representantes.set(tipo, slug);
  }
}

const amostra = [...representantes].flatMap(([tipo, slug]) =>
  IDIOMAS.flatMap((idioma) =>
    (tipo === 'compare' ? ['dark'] : ['light', 'dark']).map((tema) => ({ tipo, slug, idioma, tema })),
  ),
);

/** Id do modelo (FQCN, `painel:app`) no id do desenho: o que o `data-node-id` leva. */
const idNoDesenho = (id) => id.replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/^[^a-zA-Z]+/, 'x-');

/** A coleção de peças de cada tipo, e o campo do rótulo nela. */
const COLECAO_DE_PECAS = {
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
const CAMPO_DO_ROTULO = { timeline: 'title', waterfall: 'name' };

/** A coleção de ligações de cada tipo escrito à mão. A timeline e o waterfall não têm ligação. */
const COLECAO_DE_LIGACOES = {
  architecture: 'connections',
  workflow: 'edges',
  sequence: 'messages',
  dataflow: 'flows',
  lifecycle: 'transitions',
  erd: 'relationships',
  class: 'relationships',
};

/** O nome de cada tipo de peça do modelo na legenda, em cada idioma (RQ-24 e glossário). */
const NOME_DO_TIPO_NA_LEGENDA = {
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
  comando: { pt: 'comando', en: 'command' },
  livewire: { pt: 'Livewire', en: 'Livewire' },
  setting: { pt: 'settings', en: 'setting' },
};

/**
 * O que a fonte diz que o diagrama desenha, num idioma:
 *  pecas      — [{ id, rotulo }] pelo id do desenho;
 *  caixas     — [{ id?, rotulo }] agrupamento (pelo id) e porta (só pelo rótulo);
 *  ligacoes   — [{ origem, destino, arquivo, trecho }] uma por ligação desenhada;
 *  pares      — Set de "de→para" das arestas (só `architecture`), ou null;
 *  comFeature — [{ id, nome, features }] as peças com nó próprio que entregam feature (só os construídos);
 *  tipos      — os tipos de peça do modelo que o mapa de arquitetura desenha.
 */
function esperadoDoDiagrama(d, idioma) {
  if (d.construtor === 'registro') {
    return esperadoDaArvore(d);
  }
  if (d.construtor === 'mapa') {
    return esperadoDoMapa(d, idioma);
  }

  const colecao = d[COLECAO_DE_PECAS[d.tipo]] ?? [];
  const campo = CAMPO_DO_ROTULO[d.tipo] ?? 'label';
  const pecas = colecao.map((peca) => ({ id: peca.id, rotulo: texto(peca[campo], idioma) }));
  const rotuloDe = new Map(pecas.map(({ id, rotulo }) => [id, rotulo]));
  const lista = COLECAO_DE_LIGACOES[d.tipo] ? (d[COLECAO_DE_LIGACOES[d.tipo]] ?? []) : [];
  const ligacoes = lista.map((ligacao) => ({
    origem: rotuloDe.get(ligacao.from),
    destino: rotuloDe.get(ligacao.to),
    arquivo: ligacao.prova?.arquivo,
    trecho: ligacao.prova?.trecho,
    de: ligacao.from,
    para: ligacao.to,
  }));

  return {
    pecas,
    caixas: [],
    ligacoes,
    pares: d.tipo === 'architecture' ? new Set(lista.map((l) => `${l.from}→${l.to}`)) : null,
    comFeature: [],
    tipos: new Set(),
  };
}

/** O mapa de arquitetura construído: o recorte do arquivo, as ligações do modelo no painel e no modo. */
function esperadoDoMapa(d, idioma) {
  const modos = d.modos;
  const existe = (id) => (pecasDoModelo.get(id)?.registrada ?? []).some((modo) => modos.includes(modo));
  const desenhadas = d.pecas.filter(({ id }) => existe(id)).map(({ id }) => pecasDoModelo.get(id));
  const agrupamentos = (d.agrupamentos ?? []).map((g) => ({ ...g, membros: g.membros.filter(existe) }));
  const idsDesenhados = new Set(desenhadas.map((p) => p.id));
  const grupoDe = new Map(agrupamentos.flatMap((g) => g.membros.map((membro) => [membro, g.id])));
  const ponta = (id) => (idsDesenhados.has(id) ? idNoDesenho(id) : (grupoDe.get(id) ?? null));
  const doEscopo = (l) => (d.paineis.length ? l.paineis.some((p) => d.paineis.includes(p)) : l.paineis.length === 0);

  const ligacoes = [];
  const pares = new Set();
  for (const l of ligacoesDoModelo) {
    if (!l.modos.some((modo) => modos.includes(modo)) || !doEscopo(l)) {
      continue;
    }
    const de = ponta(l.de);
    const para = ponta(l.para);
    if (!de || !para || de === para) {
      continue;
    }
    pares.add(`${de}→${para}`);
    ligacoes.push({
      origem: pecasDoModelo.get(l.de).nome,
      destino: pecasDoModelo.get(l.para).nome,
      arquivo: l.evidencia.arquivo,
      trecho: l.evidencia.trecho,
    });
  }

  return {
    pecas: desenhadas.map((p) => ({ id: idNoDesenho(p.id), rotulo: p.nome })),
    caixas: [
      ...agrupamentos.map((g) => ({ id: g.id, rotulo: texto(g.rotulo, idioma) })),
      ...(d.portas ?? []).map((porta) => ({ id: null, rotulo: texto(porta.rotulo, idioma) })),
    ],
    ligacoes,
    pares,
    comFeature: desenhadas.filter((p) => p.features.length).map((p) => ({ id: idNoDesenho(p.id), nome: p.nome, features: p.features })),
    tipos: new Set(desenhadas.map((p) => p.tipo)),
  };
}
/** A árvore de registro: o painel, mais tudo o que ele registra no modo do diagrama. Cada registro é uma ligação. */
function esperadoDaArvore(d) {
  const [painel] = d.paineis;
  const [modo] = d.modos;
  const raiz = `painel:${painel}`;
  const registros = ligacoesDoModelo.filter((l) => l.de === raiz && l.verbo === 'registra' && l.modos.includes(modo));
  const registradas = [...new Map(registros.map((l) => [l.para, pecasDoModelo.get(l.para)])).values()];

  return {
    pecas: [{ id: idNoDesenho(raiz), rotulo: null, raiz: true }, ...registradas.map((p) => ({ id: idNoDesenho(p.id), rotulo: p.nome }))],
    caixas: [],
    ligacoes: registros.map((l) => ({
      origem: null,
      destino: pecasDoModelo.get(l.para).nome,
      arquivo: l.evidencia.arquivo,
      trecho: l.evidencia.trecho,
    })),
    pares: null,
    comFeature: registradas.filter((p) => p.features.length).map((p) => ({ id: idNoDesenho(p.id), nome: p.nome, features: p.features })),
    tipos: new Set(),
  };
}

// ---------------------------------------------------------------------------------------------------
// Git, repositório e commit do build (P-07)
// ---------------------------------------------------------------------------------------------------
const git = (...args) => execFileSync('git', args, { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024, stdio: ['ignore', 'pipe', 'pipe'] });
const COMMIT = MODO_CONTROLE ? '' : (process.env.GITHUB_SHA || git('rev-parse', 'HEAD').trim());
const FONTE_DO_REPOSITORIO = MODO_CONTROLE ? '' : lerJson(resolve('..', 'composer.json')).support.source;
const [DONO, REPOSITORIO] = FONTE_DO_REPOSITORIO.replace(/^https:\/\/github\.com\//, '').split('/');
const arquivosNoCommit = new Map();
const arquivoNoCommit = (caminho) => {
  if (!arquivosNoCommit.has(caminho)) {
    let conteudo = null;
    try {
      conteudo = git('show', `${COMMIT}:${caminho}`);
    } catch {
      conteudo = null;
    }
    arquivosNoCommit.set(caminho, conteudo);
  }
  return arquivosNoCommit.get(caminho);
};

// ---------------------------------------------------------------------------------------------------
// O catálogo de interface (P-11): en do archify, pt embutido no HTML e pt da fonte do kit
// ---------------------------------------------------------------------------------------------------
const CATALOGO_EN = MODO_CONTROLE ? {} : lerJson(join(ARCHIFY, 'locales/en.json'));
const CATALOGO_PT_DO_ARCHIFY = MODO_CONTROLE ? {} : lerJson(join(ARCHIFY, 'examples/locales/pt.json'));
const INTERFACE_DO_KIT = MODO_CONTROLE ? { pt: {}, tipos: {} } : lerJson(join(MODELO, 'interface.json'));

/** O pt que o kit entrega, pela fonte, para um tipo de diagrama. */
const ptDaFonte = (tipo) => ({ ...CATALOGO_PT_DO_ARCHIFY, ...INTERFACE_DO_KIT.pt, ...INTERFACE_DO_KIT.tipos?.[tipo]?.pt });

/** As mensagens embutidas no HTML (`archify-i18n-data`), lidas do arquivo. */
function mensagensEmbutidas(idioma, slug) {
  const caminho = join(DIST, 'mapa', idioma, `${slug}.html`);
  if (!existsSync(caminho)) {
    return null;
  }
  const achado = readFileSync(caminho, 'utf8').match(/<script id="archify-i18n-data"[^>]*>([\s\S]*?)<\/script>/);
  return achado ? JSON.parse(achado[1]).messages : null;
}

/** O catálogo pt de um HTML pt: o embutido, e a fonte do kit nas chaves que o archify aplica no render. */
const catalogoPtDoHtml = (slug, tipo) => ({ ...ptDaFonte(tipo), ...mensagensEmbutidas('pt', slug) });

/** As 14 chaves cujo texto pt é igual ao en no commit fixado do archify (fato da base, CT-B07), e as de só marcadores. */
const CHAVES_MEDIDAS = new Set([
  'legend.architecture.frontend',
  'legend.architecture.backend',
  'viewer.kind.frontend',
  'viewer.kind.backend',
  'viewer.preset.editorial',
  'viewer.export.direction.upstream',
  'viewer.export.direction.downstream',
  'viewer.guide.facts',
  'viewer.finder.status.all',
  'viewer.passport.upstream',
  'viewer.passport.downstream',
  'viewer.passport.relationship.row',
  'viewer.nav.level.auto',
  'node.context.class.interface',
]);

/** A lista fechada de textos iguais nos dois idiomas, cada chave com o motivo. */
const LISTA_FECHADA = {
  'legend.architecture.frontend': 'Frontend é o termo técnico, usado em pt do mesmo jeito',
  'legend.architecture.backend': 'Backend é o termo técnico, usado em pt do mesmo jeito',
  'viewer.kind.frontend': 'Frontend é o termo técnico, usado em pt do mesmo jeito',
  'viewer.kind.backend': 'Backend é o termo técnico, usado em pt do mesmo jeito',
  'viewer.preset.editorial': 'Editorial se escreve igual em pt e en',
  'viewer.export.direction.upstream': 'Upstream é o termo técnico de fluxo, usado em pt sem tradução',
  'viewer.export.direction.downstream': 'Downstream é o termo técnico de fluxo, usado em pt sem tradução',
  'viewer.guide.facts': 'só marcadores {nodes} e {relationships}, sem palavra a traduzir',
  'viewer.finder.status.all': 'só marcadores {available} e {noun}, sem palavra a traduzir',
  'viewer.passport.upstream': 'Upstream é o termo técnico de fluxo, usado em pt sem tradução',
  'viewer.passport.downstream': 'Downstream é o termo técnico de fluxo, usado em pt sem tradução',
  'viewer.passport.relationship.row': 'só marcadores {group}, {relationship} e {neighbor}, sem palavra a traduzir',
  'viewer.nav.level.auto': 'AUTO é a sigla do modo automático, igual nos dois idiomas',
  'node.context.class.interface': 'Interface se escreve igual em pt e en',
};

/** Texto de marcador: sem nenhuma letra fora dos `{marcadores}`. */
const soMarcadores = (valor) => !/\p{L}/u.test(String(valor).replace(/\{[^}]*\}/gu, ''));

/** Motivo de enfeite (Setup Global do `04`): vazio, só travessão ou pontuação, ou "não se aplica" sem dizer por quê. */
const motivoDeEnfeite = (motivo) => {
  const limpo = String(motivo ?? '').replace(/[\s\p{P}\p{S}]+/gu, ' ').trim();
  return limpo === '' || /^n[aã]o se aplica$/iu.test(limpo);
};

/**
 * A decisão do CT-B07, uma só para o catálogo real e para as cópias: devolve as chaves que reprovam, com o motivo.
 * Chave do en ausente no pt reprova; texto igual ao en reprova, salvo chave da lista com motivo que não é de enfeite;
 * e chave da lista fora das 14 medidas e que não é só de marcadores reprova (o teto da lista).
 */
function decidirCatalogoPt(en, pt, lista) {
  const reprovadas = [];
  for (const [chave, textoEn] of Object.entries(en)) {
    if (!Object.hasOwn(pt, chave)) {
      reprovadas.push(`${chave} (ausente no pt)`);
    } else if (pt[chave] === textoEn && !(chave in lista)) {
      reprovadas.push(`${chave} (texto pt igual ao en, fora da lista fechada)`);
    }
  }
  for (const [chave, motivo] of Object.entries(lista)) {
    if (motivoDeEnfeite(motivo)) {
      reprovadas.push(`${chave} (na lista sem motivo, ou com motivo de enfeite)`);
    }
    if (!CHAVES_MEDIDAS.has(chave) && !soMarcadores(en[chave] ?? '')) {
      reprovadas.push(`${chave} (na lista, fora das 14 medidas e com palavras)`);
    }
  }
  return reprovadas;
}

// ---------------------------------------------------------------------------------------------------
// A decisão da tolerância do axe, uma só para a amostra real e para os controles sintéticos
// ---------------------------------------------------------------------------------------------------
/**
 * Roda no navegador. Decide ALVO POR ALVO, resolvendo cada alvo do axe para o ELEMENTO do DOM: o texto
 * do seletor muda com o tipo de diagrama e nunca decide.
 */
async function decidirNaPagina() {
  const raiz = document.getElementById('archify-diagram-title')?.closest('svg') ?? null;
  const tolerada = (regra, elemento) =>
    (regra === 'nested-interactive' && raiz !== null && elemento === raiz) ||
    (regra === 'color-contrast' && elemento?.matches?.('.node-outline-heading > small') === true);

  const r = await window.axe.run(document, {
    resultTypes: ['violations'],
    elementRef: true,
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
  });

  return r.violations
    .filter((v) => v.impact === 'serious' || v.impact === 'critical')
    .map((v) => {
      const restantes = v.nodes.filter((no) => !tolerada(v.id, no.element));
      return {
        regra: v.id,
        impacto: v.impact,
        toleradas: v.nodes.length - restantes.length,
        restantes: restantes.map((no) => (no.html || '').slice(0, 120)),
      };
    });
}

// ---------------------------------------------------------------------------------------------------
// A leitura da página aberta (roda no navegador)
// ---------------------------------------------------------------------------------------------------
function lerPagina() {
  const visivel = (el) => el.checkVisibility({ checkVisibilityCSS: true }) && el.offsetHeight > 0;
  const fora = (el) => el.closest('[data-node-id], .card, script, style, noscript, template, .node-finder-result') !== null;

  const textos = [];
  const andador = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  for (let no = andador.nextNode(); no; no = andador.nextNode()) {
    const pai = no.parentElement;
    const t = no.textContent.replace(/\s+/g, ' ').trim();
    if (t && pai && !fora(pai) && visivel(pai)) {
      textos.push(t);
    }
  }
  const atributos = [];
  for (const el of document.querySelectorAll('[aria-label], [title], [placeholder], [data-legend-label]')) {
    if (fora(el)) {
      continue;
    }
    for (const nome of ['aria-label', 'title', 'placeholder', 'data-legend-label']) {
      const v = el.getAttribute(nome);
      if (v) {
        atributos.push(v.replace(/\s+/g, ' ').trim());
      }
    }
  }
  atributos.push(document.title);

  const embutido = document.getElementById('archify-i18n-data');
  const itens = [...document.querySelectorAll('.reader-rail .card li')].map((li) => ({
    t: li.textContent.replace(/\s+/g, ' ').trim(),
    aVista: visivel(li),
    cortado: li.scrollWidth > li.clientWidth + 1,
  }));

  return {
    lang: document.documentElement.lang,
    h1: document.querySelector('h1')?.textContent ?? null,
    subtitulo: document.querySelector('p.subtitle')?.textContent ?? null,
    nos: [...document.querySelectorAll('svg [data-node-id]')].map((n) => ({ id: n.dataset.nodeId, rotulo: n.dataset.nodeLabel ?? null })),
    resultados: [...document.querySelectorAll('.node-finder-result')].map((n) => n.dataset.nodeId),
    arestas: [...document.querySelectorAll('svg [data-edge-from][data-edge-to]')].map((a) => `${a.dataset.edgeFrom}→${a.dataset.edgeTo}`),
    itens,
    legenda: [...document.querySelectorAll('svg g[data-legend-kind]')].map((g) => g.dataset.legendLabel ?? ''),
    recursos: document.querySelectorAll('script[src], link[rel="stylesheet"]').length,
    textos,
    atributos,
    embutido: embutido ? JSON.parse(embutido.textContent) : null,
    avisoDeIdiomaReserva:
      document.querySelector('[data-locale-fallback], [data-i18n-fallback], .locale-fallback, .i18n-fallback, .i18n-warning') !== null ||
      /fall(s)? back to English|idioma reserva|locale fallback/i.test(document.body.innerText),
  };
}

/** Roda no navegador: o texto de interface visível e os atributos de um momento (busca e passaporte abertos). */
function lerPaginaDaInterface() {
  const visivel = (el) => el.checkVisibility({ checkVisibilityCSS: true }) && el.offsetHeight > 0;
  const fora = (el) => el.closest('[data-node-id], .card, script, style, noscript, template, .node-finder-result') !== null;
  const saida = [];
  const andador = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  for (let no = andador.nextNode(); no; no = andador.nextNode()) {
    const pai = no.parentElement;
    const t = no.textContent.replace(/\s+/g, ' ').trim();
    if (t && pai && !fora(pai) && visivel(pai)) {
      saida.push(t);
    }
  }
  for (const el of document.querySelectorAll('[aria-label], [title], [placeholder], [data-legend-label]')) {
    if (fora(el)) {
      continue;
    }
    for (const nome of ['aria-label', 'title', 'placeholder', 'data-legend-label']) {
      const v = el.getAttribute(nome);
      if (v) {
        saida.push(v.replace(/\s+/g, ' ').trim());
      }
    }
  }
  return saida;
}

// ---------------------------------------------------------------------------------------------------
// Conferências por página
// ---------------------------------------------------------------------------------------------------
const duasPinturas = (pagina) => pagina.evaluate(() => new Promise((pronto) => requestAnimationFrame(() => requestAnimationFrame(pronto))));
const urlDoDiagrama = (idioma, slug) => `${ORIGEM}${BASE}/mapa/${idioma}/${slug}.html`;
const seletorDoNo = (id) => `[data-node-id="${id.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"]`;

function conferirRecursos(ct, rotulo, requisicoes, leitura) {
  const forasDaOrigem = requisicoes.filter((url) => !url.startsWith(ORIGEM) && !url.startsWith('data:') && !url.startsWith('blob:'));
  if (forasDaOrigem.length > 0) {
    reprovar(ct, `${rotulo} — requisição fora da origem da prévia: ${forasDaOrigem[0].slice(0, 160)}`);
  }
  if (leitura.recursos > 0) {
    reprovar(ct, `${rotulo} — o documento tem ${leitura.recursos} <script src> ou <link rel="stylesheet">`);
  }
}

/** O conjunto de peças da fonte está desenhado, cada uma com o rótulo da fonte. */
function conferirPecas(rotulo, esperado, leitura, idioma) {
  const desenhadas = new Map(leitura.nos.map((no) => [no.id, no.rotulo]));
  for (const peca of esperado.pecas) {
    if (!desenhadas.has(peca.id)) {
      reprovar('CT-B01', `${rotulo} — a peça ${peca.id} não está desenhada`);
    } else if (peca.rotulo !== null && desenhadas.get(peca.id) !== peca.rotulo) {
      reprovar('CT-B01', `${rotulo} — a peça ${peca.id} tem o rótulo ${JSON.stringify(desenhadas.get(peca.id))}, e a fonte dá ${JSON.stringify(peca.rotulo)}`);
    }
  }
  for (const caixa of esperado.caixas) {
    const achada = caixa.id ? desenhadas.get(caixa.id) : [...desenhadas.values()].find((r) => r === caixa.rotulo);
    if (achada === undefined || achada !== caixa.rotulo) {
      reprovar('CT-B01', `${rotulo} — a caixa ${JSON.stringify(caixa.rotulo)} (${idioma}) não está desenhada com o rótulo do arquivo`);
    }
  }
  if (esperado.pecas.length === 0 && esperado.caixas.length === 0) {
    reprovar('CT-B01', `${rotulo} — a fonte não dá peça nenhuma a este diagrama`);
  }
  contar('CT-B01', esperado.pecas.length);
}

function conferirBusca(rotulo, leitura) {
  const desenhadas = new Set(leitura.nos.map((no) => no.id));
  const achadas = new Set(leitura.resultados);
  const semBusca = [...desenhadas].filter((id) => !achadas.has(id));
  const sobrando = [...achadas].filter((id) => !desenhadas.has(id));
  if (achadas.size === 0 || semBusca.length > 0 || sobrando.length > 0) {
    reprovar(
      'CT-B01',
      `${rotulo} — a busca tem ${achadas.size} resultados para ${desenhadas.size} peças desenhadas` +
        `${semBusca.length ? ` (sem resultado: ${semBusca[0]})` : ''}${sobrando.length ? ` (resultado sem peça: ${sobrando[0]})` : ''}`,
    );
  }
}

/** CT-B02: o item de prova de cada ligação desenhada, à vista e inteiro, e os pares de arestas. */
function conferirProvas(rotulo, d, esperado, leitura) {
  const origemDaRaiz = leitura.nos.find((no) => esperado.pecas.some((p) => p.raiz && p.id === no.id))?.rotulo ?? null;
  const itens = leitura.itens.filter((item) => /^.+ → .+: \S+:\d+(?:-\d+)? — /s.test(item.t));
  const usados = new Set();

  for (const ligacao of esperado.ligacoes) {
    const origem = ligacao.origem ?? origemDaRaiz;
    const prefixo = `${origem} → ${ligacao.destino}`;
    const indice = itens.findIndex(
      (item, i) =>
        !usados.has(i) &&
        (item.t.startsWith(`${prefixo} (`) || item.t.startsWith(`${prefixo}: `)) &&
        item.t.includes(`: ${ligacao.arquivo}:`) &&
        item.t.endsWith(norm(ligacao.trecho)),
    );
    if (indice < 0) {
      reprovar('CT-B02', `${rotulo} — nenhum item de prova para ${prefixo} com ${ligacao.arquivo} e o trecho ${JSON.stringify(ligacao.trecho.slice(0, 60))}`);
      continue;
    }
    usados.add(indice);
    if (!itens[indice].aVista) {
      reprovar('CT-B02', `${rotulo} — o item de prova de ${prefixo} não está à vista sem foco`);
    }
    if (itens[indice].cortado) {
      reprovar('CT-B02', `${rotulo} — o item de prova de ${prefixo} está cortado (scrollWidth > clientWidth + 1)`);
    }
  }

  if (itens.length !== esperado.ligacoes.length) {
    reprovar('CT-B02', `${rotulo} — ${itens.length} itens de prova no DOM, e ${esperado.ligacoes.length} ligações desenhadas`);
  }
  contar('CT-B02', esperado.ligacoes.length);

  if (esperado.pares) {
    const doDom = new Set(leitura.arestas);
    const faltando = [...esperado.pares].filter((par) => !doDom.has(par));
    const sobrando = [...doDom].filter((par) => !esperado.pares.has(par));
    if (faltando.length > 0 || sobrando.length > 0) {
      reprovar(
        'CT-B02',
        `${rotulo} — as arestas desenhadas não são as da fonte: ${doDom.size} no DOM e ${esperado.pares.size} na fonte` +
          `${faltando.length ? `; falta ${faltando[0]}` : ''}${sobrando.length ? `; sobra ${sobrando[0]}` : ''}`,
      );
    }
    contar('CT-B02', esperado.pares.size);
  }
  void d;
}

/** CT-B04: o texto de interface lido não é o en de uma chave cujo pt é outro (ou o contrário, na versão en). */
function conferirIdioma(rotulo, idioma, d, leitura, conjuntoProibido, ctExtra = 'CT-B04') {
  if (idioma === 'pt' && !leitura.lang.startsWith('pt')) {
    reprovar('CT-B04', `${rotulo} — o lang do documento é ${JSON.stringify(leitura.lang)}, e devia começar por pt`);
  }
  if (idioma === 'en' && !leitura.lang.startsWith('en')) {
    reprovar('CT-B04', `${rotulo} — o lang do documento é ${JSON.stringify(leitura.lang)}, e devia começar por en`);
  }
  const proibidos = [...new Set([...leitura.textos, ...leitura.atributos])].filter((t) => conjuntoProibido.has(t));
  if (proibidos.length > 0) {
    reprovar(ctExtra, `${rotulo} — texto de interface no idioma errado: ${JSON.stringify(proibidos[0])}`);
  }
  void d;
}

/** O conjunto de textos que NÃO podem aparecer na interface de um idioma: o texto do outro, nas chaves em que os dois diferem. */
function textosProibidos(idioma, slug, tipo) {
  if (idioma === 'pt') {
    const pt = catalogoPtDoHtml(slug, tipo);
    return new Set(Object.entries(CATALOGO_EN).filter(([chave, textoEn]) => pt[chave] !== undefined && pt[chave] !== textoEn).map(([, textoEn]) => textoEn));
  }
  const pt = mensagensEmbutidas('pt', slug) ?? {};
  const en = mensagensEmbutidas('en', slug) ?? {};
  return new Set(Object.entries(pt).filter(([chave, textoPt]) => en[chave] !== undefined && en[chave] !== textoPt).map(([, textoPt]) => textoPt));
}

/** CT-B04: a legenda do mapa de arquitetura traz o nome de cada tipo de peça desenhado, como pedaço do rótulo. */
function conferirLegenda(rotulo, idioma, esperado, leitura) {
  for (const tipo of esperado.tipos) {
    const nome = NOME_DO_TIPO_NA_LEGENDA[tipo][idioma];
    if (!leitura.legenda.some((r) => r.includes(nome))) {
      reprovar('CT-B04', `${rotulo} — a legenda (${idioma}) não traz o nome ${JSON.stringify(nome)} do tipo ${tipo}: ${JSON.stringify(leitura.legenda)}`);
    }
  }
}

/**
 * O que o desenho da peça mostra das F-nn dela (D29 emendada): todas, até três; acima de três, as três primeiras na
 * ordem do modelo e "+N", com N o resto. A lista inteira fica no card "F-nn de cada peça" do mesmo diagrama.
 */
const fnnNoDesenho = (features) => (features.length > 3 ? `${features.slice(0, 3).join(' · ')} +${features.length - 3}` : features.join(' · '));

/** CT-B01: a F-nn de cada peça com nó próprio que entrega feature está no desenho dela e, inteira, no card da peça. */
async function conferirFeatures(pagina, rotulo, d, esperado, leitura) {
  if (esperado.comFeature.length === 0) {
    return;
  }

  if (d.construtor === 'mapa') {
    const botao = pagina.locator('[data-view="in"]').first();
    for (let i = 0; i < 40; i++) {
      if ((await pagina.evaluate(() => document.querySelector('.diagram-container')?.dataset.detailLevel)) === 'full') {
        break;
      }
      await botao.click();
      await duasPinturas(pagina);
    }
    if ((await pagina.evaluate(() => document.querySelector('.diagram-container')?.dataset.detailLevel)) !== 'full') {
      reprovar('CT-B01', `${rotulo} — o visualizador não chegou ao nível de leitura cheio`);
      return;
    }
  }

  const ler = (pecas, modo) =>
    pagina.evaluate(
      ({ pecas: lista, modo: como }) => {
        const efetiva = (el) => {
          let o = 1;
          for (let e = el; e && e.nodeType === 1; e = e.parentElement) {
            o *= parseFloat(getComputedStyle(e).opacity);
          }
          return o;
        };
        return lista.map(({ id, abreviada }) => {
          const no = [...document.querySelectorAll('svg [data-node-id]')].find((n) => n.dataset.nodeId === id);
          if (!no) {
            return { id, achada: false };
          }
          const alvos =
            como === 'tag'
              ? [...no.querySelectorAll('[data-detail="fine"]')].filter((el) => /F-[0-9]/.test(el.textContent))
              : [...no.querySelectorAll('[data-detail="context"]')];
          const t = como === 'tag' ? (alvos[0]?.textContent ?? '') : (no.dataset.nodeSublabel ?? '');
          return {
            id,
            achada: true,
            todas: t.includes(abreviada),
            opacidade: alvos.length ? Math.min(...alvos.map(efetiva)) : null,
            texto: t,
          };
        });
      },
      { pecas, modo },
    );

  const modo = d.construtor === 'mapa' ? 'tag' : 'sublabel';
  const comAbreviada = esperado.comFeature.map((p) => ({ id: p.id, abreviada: fnnNoDesenho(p.features) }));
  let medidas = await ler(comAbreviada, modo);
  const pronto = (m) => m.every((x) => x.achada && x.opacidade === 1);
  for (let i = 0; i < 20 && !pronto(medidas); i++) {
    await duasPinturas(pagina);
    medidas = await ler(comAbreviada, modo);
  }
  const incompletas = medidas.filter((m) => m.achada && !m.todas);
  if (incompletas.length > 0) {
    reprovar(
      'CT-B01',
      `${rotulo} — ${incompletas.length} de ${medidas.length} peças não mostram no ${modo === 'tag' ? 'tag' : 'sublabel'} as F-nn delas (todas, até três; as três primeiras e "+N", acima disso); ex.: ${incompletas[0].id} mostra ${JSON.stringify(incompletas[0].texto)}`,
    );
  }
  for (const m of medidas) {
    if (!m.achada || !m.todas) {
      continue; // a falta do nó já reprovou em conferirPecas
    }
    if (m.opacidade !== 1) {
      reprovar('CT-B01', `${rotulo} — a F-nn da peça ${m.id} está com opacidade ${m.opacidade}, e devia estar à vista (1)`);
    }
  }
  for (const { nome, features } of esperado.comFeature) {
    const linha = `${nome}: ${features.join(', ')}`;
    const item = leitura.itens.find((i) => i.t === linha);
    if (!item) {
      reprovar('CT-B01', `${rotulo} — o card "F-nn de cada peça" não traz a linha ${JSON.stringify(linha)}`);
    } else if (!item.aVista || item.cortado) {
      reprovar('CT-B01', `${rotulo} — a linha ${JSON.stringify(linha)} do card "F-nn de cada peça" não está à vista e inteira`);
    }
  }
  contar('CT-B01:F-nn', esperado.comFeature.length);
}

/** Busca a peça pelo nome, foca, e devolve o que o passaporte mostra. */
async function buscarEFocar(pagina, id, rotulo) {
  await pagina.keyboard.press('/');
  try {
    await pagina.waitForFunction(() => document.activeElement?.matches?.('input.node-finder-input') === true, null, { timeout: RENDER_TIMEOUT_MS });
  } catch {
    return { erro: 'o atalho / não focou o campo de busca' };
  }
  await pagina.locator('input.node-finder-input').fill(rotulo);
  const resultado = pagina.locator(`.node-finder-result${seletorDoNo(id)}`);
  try {
    await resultado.first().waitFor({ state: 'visible', timeout: RENDER_TIMEOUT_MS });
    await resultado.first().click();
    await pagina.waitForFunction(
      ({ alvo, nome }) => document.getElementById('focus-id')?.textContent === alvo && document.getElementById('focus-label')?.textContent === nome,
      { alvo: id, nome: rotulo },
      { timeout: RENDER_TIMEOUT_MS },
    );
  } catch {
    return { erro: 'a peça não ficou em foco pela busca (resultado ou passaporte não apareceu)' };
  }
  return pagina.evaluate(() => {
    const evidencia = document.querySelector('.semantic-passport-evidence');
    const repo = document.getElementById('focus-repository');
    return {
      rotulo: document.getElementById('focus-label')?.textContent ?? null,
      evidencia: evidencia ? evidencia.textContent.replace(/\s+/g, ' ').trim() : null,
      repositorio: repo ? repo.href : null,
      links: [...document.querySelectorAll('.semantic-passport-evidence a[href*="/blob/"], .relationship-lens-copy a[href*="/blob/"]')].map((a) => a.href),
    };
  });
}

/** CT-B03: o passaporte da peça da amostra, com os links para a linha do trecho, no commit do build. */
function conferirPassaporte(rotulo, peca, passaporte) {
  if (passaporte.erro) {
    reprovar('CT-B03', `${rotulo} — ${peca.nome}: ${passaporte.erro}`);
    return;
  }
  if (passaporte.rotulo !== peca.nome) {
    reprovar('CT-B03', `${rotulo} — o passaporte mostra ${JSON.stringify(passaporte.rotulo)}, e a peça é ${peca.nome}`);
  }
  const origemEsperada = `${DONO}/${REPOSITORIO} @ ${COMMIT.slice(0, 7)}`;
  if (!passaporte.evidencia?.includes(origemEsperada)) {
    reprovar('CT-B03', `${rotulo} — ${peca.nome}: a origem verificada não diz ${origemEsperada}: ${JSON.stringify(passaporte.evidencia)}`);
  }
  if (passaporte.repositorio !== `https://github.com/${DONO}/${REPOSITORIO}/tree/${COMMIT}`) {
    reprovar('CT-B03', `${rotulo} — ${peca.nome}: o repositório do passaporte é ${passaporte.repositorio}`);
  }
  const unicos = [...new Set(passaporte.links)];
  if (unicos.length < 1 || unicos.length > 3) {
    reprovar('CT-B03', `${rotulo} — ${peca.nome}: ${unicos.length} links de prova no passaporte, e o esperado é de 1 a 3`);
  }
  for (const href of unicos) {
    const partes = href.match(/^https:\/\/github\.com\/([^/]+)\/([^/]+)\/blob\/([0-9a-f]{40})\/(.+?)#L(\d+)(?:-L(\d+))?$/);
    if (!partes) {
      reprovar('CT-B03', `${rotulo} — ${peca.nome}: link de prova fora do formato blob/{commit}/{arquivo}#L{linha}: ${href}`);
      continue;
    }
    const [, dono, repo, commit, caminho, inicio, fim] = partes;
    if (dono !== DONO || repo !== REPOSITORIO) {
      reprovar('CT-B03', `${rotulo} — ${peca.nome}: o link leva a ${dono}/${repo}, e o repositório do kit é ${DONO}/${REPOSITORIO}`);
    }
    if (commit !== COMMIT) {
      reprovar('CT-B03', `${rotulo} — ${peca.nome}: o link leva ao commit ${commit.slice(0, 7)}, e o do build é ${COMMIT.slice(0, 7)}`);
    }
    const conteudo = arquivoNoCommit(decodeURIComponent(caminho));
    if (conteudo === null) {
      reprovar('CT-B03', `${rotulo} — ${peca.nome}: ${caminho} não existe no commit ${COMMIT.slice(0, 7)}`);
      continue;
    }
    const linhas = conteudo.split('\n').slice(Number(inicio) - 1, Number(fim ?? inicio));
    const trechoNaLinha = peca.evidencia.some((ancora) => ancora.arquivo === decodeURIComponent(caminho) && linhas.join('\n').includes(ancora.trecho));
    if (!trechoNaLinha) {
      reprovar('CT-B03', `${rotulo} — ${peca.nome}: a linha ${inicio} de ${caminho} não contém o trecho da âncora da prova`);
    }
  }
  contar('CT-B03', unicos.length);
}

/** A amostra do CT-B03 num diagrama `architecture`: a primeira peça de pacote e a primeira do kit, pelo modelo. */
function amostraDePassaporte(d) {
  const pecas = d.pecas.map(({ id }) => pecasDoModelo.get(id)).filter((p) => p && (p.registrada ?? []).some((modo) => d.modos.includes(modo)));
  const pacote = pecas.find((p) => p.origem === 'pacote');
  const kit = pecas.find((p) => p.origem.includes('proprio'));
  return { pacote, kit };
}

/** Abre um diagrama, num idioma, e roda as conferências das costuras CT-B01 a CT-B04 que se leem na página. */
async function conferirDiagrama(navegador, d, idioma, resumo) {
  const rotulo = `${d.tipo} ${idioma}/${d.slug}`;
  if (!existsSync(join(DIST, 'mapa', idioma, `${d.slug}.html`))) {
    reprovar('CT-B01', `${rotulo} — o build não gerou dist/mapa/${idioma}/${d.slug}.html`);
    return;
  }

  const contexto = await navegador.newContext();
  const pagina = await contexto.newPage();
  const erros = [];
  const requisicoes = [];
  pagina.on('console', (mensagem) => {
    if (mensagem.type() === 'error') {
      erros.push(`console error: ${mensagem.text().slice(0, 200)}`);
    }
  });
  pagina.on('pageerror', (erro) => erros.push(`exceção não capturada: ${erro.message.slice(0, 200)}`));
  pagina.on('request', (requisicao) => requisicoes.push(requisicao.url()));

  const comparativo = d.tipo === 'compare';
  try {
    await pagina.goto(urlDoDiagrama(idioma, d.slug), { waitUntil: 'load' });
    await pagina.waitForSelector('svg [data-node-id]', { state: 'attached', timeout: RENDER_TIMEOUT_MS });
    if (!comparativo) {
      await pagina.waitForSelector('.node-finder-result', { state: 'attached', timeout: RENDER_TIMEOUT_MS });
      await pagina
        .waitForFunction(() => document.querySelectorAll('.node-finder-result').length === document.querySelectorAll('svg [data-node-id]').length, null, {
          timeout: RENDER_TIMEOUT_MS,
        })
        .catch(() => {});
    }
  } catch {
    reprovar('CT-B01', `${rotulo} — o diagrama não desenhou peça nenhuma em ${RENDER_TIMEOUT_MS} ms (svg [data-node-id]${comparativo ? '' : ' e a busca'})`);
    await contexto.close();
    return;
  }
  await duasPinturas(pagina);

  const leitura = await pagina.evaluate(lerPagina);
  resumo.abertos[idioma]++;
  let antesDaInteracao = erros.length;

  conferirRecursos('CT-B01', rotulo, requisicoes, leitura);

  if (comparativo) {
    if (idioma === 'pt') {
      const titulos = [d.base, d.cabeca].map((slug) => porSlug.get(slug).titulo.pt);
      if (!titulos.every((titulo) => (leitura.subtitulo ?? '').includes(titulo))) {
        reprovar('CT-B04', `${rotulo} — o subtítulo ${JSON.stringify(leitura.subtitulo)} não traz os títulos pt da base e da cabeça`);
      }
    }
  } else {
    const esperado = esperadoDoDiagrama(d, idioma);
    conferirPecas(rotulo, esperado, leitura, idioma);
    conferirBusca(rotulo, leitura);
    if (leitura.h1 !== texto(d.titulo, idioma)) {
      reprovar('CT-B01', `${rotulo} — o <h1> é ${JSON.stringify(leitura.h1)}, e o título do arquivo é ${JSON.stringify(texto(d.titulo, idioma))}`);
    }
    conferirProvas(rotulo, d, esperado, leitura);
    conferirIdioma(rotulo, idioma, d, leitura, textosProibidos(idioma, d.slug, d.tipo));
    if (idioma === 'pt' && leitura.avisoDeIdiomaReserva) {
      reprovar('CT-B04', `${rotulo} — o aviso de idioma reserva apareceu`);
    }
    if (d.tipo === 'architecture') {
      conferirLegenda(rotulo, idioma, esperado, leitura);
    }
    contar('CT-B04');

    // a F-nn no desenho, antes de focar peça: o foco esmaece as outras peças, e o zoom não atrapalha a busca
    await conferirFeatures(pagina, rotulo, d, esperado, leitura);

    // interação: a busca e o passaporte abertos (CT-B03 na amostra de `architecture`; CT-B04 em todo pt)
    const antes = erros.length;
    antesDaInteracao = antes;
    const amostraDoDiagrama = d.tipo === 'architecture' ? amostraDePassaporte(d) : {};
    const alvos = [];
    if (d.tipo === 'architecture') {
      for (const peca of [amostraDoDiagrama.pacote, amostraDoDiagrama.kit]) {
        if (peca) {
          alvos.push({ peca, id: idNoDesenho(peca.id), nome: peca.nome });
        }
      }
      resumo.pacote[idioma] += amostraDoDiagrama.pacote ? 1 : 0;
      resumo.kit[idioma] += amostraDoDiagrama.kit ? 1 : 0;
    } else if (idioma === 'pt') {
      const primeira = esperado.pecas.find((p) => !p.raiz && p.rotulo) ?? esperado.pecas.find((p) => p.rotulo);
      const desenhada = leitura.nos.find((no) => no.id === primeira?.id);
      if (desenhada) {
        alvos.push({ peca: null, id: desenhada.id, nome: desenhada.rotulo });
      }
    }
    if (idioma === 'pt' && d.tipo === 'architecture' && alvos.length === 0) {
      const desenhada = leitura.nos[0];
      alvos.push({ peca: null, id: desenhada.id, nome: desenhada.rotulo });
    }

    let primeiraInteracao = true;
    for (const alvo of alvos) {
      const passaporte = await buscarEFocar(pagina, alvo.id, alvo.nome);
      if (alvo.peca) {
        conferirPassaporte(rotulo, alvo.peca, passaporte);
      } else if (passaporte.erro) {
        reprovar('CT-B04', `${rotulo} — ${alvo.nome}: ${passaporte.erro}`);
      }
      if (idioma === 'pt' && primeiraInteracao && !passaporte.erro) {
        primeiraInteracao = false;
        const textosDaInteracao = await pagina.evaluate(lerPaginaDaInterface);
        const proibidos = textosProibidos('pt', d.slug, d.tipo);
        const achados = [...new Set(textosDaInteracao)].filter((t) => proibidos.has(t));
        if (achados.length > 0) {
          reprovar('CT-B04', `${rotulo} — com a busca e o passaporte abertos, texto de interface no idioma errado: ${JSON.stringify(achados[0])}`);
        }
      }
    }

    for (const erro of erros.slice(antes)) {
      if (d.tipo === 'architecture') {
        reprovar('CT-B03', `${rotulo} — com a busca e o passaporte abertos: ${erro}`);
      }
      if (idioma === 'pt') {
        reprovar('CT-B04', `${rotulo} — com a busca e o passaporte abertos: ${erro}`);
      }
    }

  }

  // erros e exceções do carregamento valem para todo diagrama, inclusive o comparativo
  for (const erro of erros.slice(0, antesDaInteracao)) {
    reprovar('CT-B01', `${rotulo} — ${erro}`);
  }

  await contexto.close();
}

// ---------------------------------------------------------------------------------------------------
// CT-B05 — a amostra de acessibilidade
// ---------------------------------------------------------------------------------------------------
const toleradas = { 'nested-interactive': 0, 'color-contrast': 0 };
const auditadas = [];

async function conferirAmostra(navegador) {
  for (const { tipo, slug, idioma, tema } of amostra) {
    const rotulo = `${tipo} ${idioma}/${slug} ${tema}`;
    if (!existsSync(join(DIST, 'mapa', idioma, `${slug}.html`))) {
      reprovar('CT-B05', `${rotulo} — o build não gerou dist/mapa/${idioma}/${slug}.html`);
      continue;
    }

    const contexto = await navegador.newContext({ colorScheme: tema });
    const pagina = await contexto.newPage();
    pagina.on('console', (mensagem) => {
      if (mensagem.type() === 'error') {
        reprovar('CT-B05', `${rotulo} — console error: ${mensagem.text().slice(0, 200)}`);
      }
    });
    pagina.on('pageerror', (erro) => reprovar('CT-B05', `${rotulo} — erro de página: ${erro.message.slice(0, 200)}`));

    await pagina.goto(urlDoDiagrama(idioma, slug), { waitUntil: 'load' });
    try {
      await pagina.waitForFunction(() => document.querySelector('svg[role="img"]'), null, { timeout: RENDER_TIMEOUT_MS });
    } catch {
      reprovar('CT-B05', `${rotulo} — nenhum <svg role="img"> em ${RENDER_TIMEOUT_MS} ms`);
      await contexto.close();
      continue;
    }
    // Duas pinturas: o visualizador termina de montar o painel lateral depois do primeiro quadro.
    await duasPinturas(pagina);

    // O comparativo abre no escuro, de propósito, e ignora o esquema de cor (D8).
    const temaAplicado = await pagina.evaluate(() => document.documentElement.dataset.theme);
    if (tipo !== 'compare' && temaAplicado !== tema) {
      reprovar('CT-B05', `${rotulo} — o visualizador abriu no tema ${temaAplicado}, e o pedido foi ${tema}`);
    }
    const desenhou = await pagina.evaluate(() => document.querySelectorAll('svg [data-node-id], svg [data-edge-from]').length);
    if (desenhou === 0) {
      reprovar('CT-B05', `${rotulo} — a entrada da amostra está vazia: nenhuma peça nem aresta desenhada`);
    }

    await pagina.addScriptTag({ content: AXE });
    const resultado = await pagina.evaluate(decidirNaPagina);
    for (const v of resultado) {
      if (v.toleradas > 0) {
        toleradas[v.regra] += v.toleradas;
      }
      if (v.restantes.length > 0) {
        reprovar('CT-B05', `${rotulo} — ${v.regra} (${v.impacto}, ${v.restantes.length}×): ${v.restantes[0]}`);
      }
    }
    auditadas.push(`${tipo}|${idioma}|${tema}`);
    await contexto.close();
  }
}

/** A composição da amostra contra a fonte, montada por um caminho independente do `amostra` acima. */
function conferirComposicaoDaAmostra() {
  const tipos = [...new Set(diagramas.map((d) => d.tipo))];
  const esperadas = [];
  for (const tipo of tipos) {
    for (const idioma of IDIOMAS) {
      for (const tema of tipo === 'compare' ? ['dark'] : ['light', 'dark']) {
        esperadas.push(`${tipo}|${idioma}|${tema}`);
      }
    }
  }
  if (esperadas.length === 0) {
    reprovar('CT-B05', 'a amostra está vazia: mapa/diagramas/ não tem diagrama nenhum');
  }
  for (const chave of esperadas) {
    const vezes = auditadas.filter((a) => a === chave).length;
    if (vezes !== 1) {
      reprovar('CT-B05', `a amostra tem ${vezes} entradas auditadas para ${chave}, e devia ter exatamente uma`);
    }
  }
  if (auditadas.length !== esperadas.length) {
    reprovar('CT-B05', `a amostra auditou ${auditadas.length} entradas, e a fonte pede ${esperadas.length}`);
  }
  if (tipos.includes('compare') === false) {
    reprovar('CT-B05', 'a fonte não tem comparativo, e o comparativo no tema escuro é parte da amostra');
  }
  contar('CT-B05', esperadas.length);
}

// ---------------------------------------------------------------------------------------------------
// CT-B06 — os controles da tolerância (páginas sintéticas, decididas pela mesma função)
// ---------------------------------------------------------------------------------------------------
const pagina_ = (corpo) => `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>controle</title></head><body><main>${corpo}</main></body></html>`;
const SVG_RAIZ_COM_FOCAVEL =
  '<svg role="img" aria-labelledby="archify-diagram-title" width="120" height="40"><title id="archify-diagram-title">Diagrama</title><g tabindex="0" role="button" aria-label="peça"><rect width="20" height="20"/></g></svg>';
const SVG_RAIZ_LIMPO =
  '<svg role="img" aria-labelledby="archify-diagram-title" width="120" height="40"><title id="archify-diagram-title">Diagrama</title><rect width="20" height="20"/></svg>';
const SVG_OUTRO_COM_FOCAVEL =
  '<svg role="img" aria-label="outro desenho" width="120" height="40"><g tabindex="0" role="button" aria-label="peça"><rect width="20" height="20"/></g></svg>';
const POUCO_CONTRASTE = 'style="color:#aaaaaa;background:#ffffff"';
const SMALL_DO_INDICE = `<div class="node-outline-heading"><small ${POUCO_CONTRASTE}>3 peças</small></div>`;

const CONTROLES = [
  { controle: 'nested-interactive no <svg> raiz do diagrama', html: SVG_RAIZ_COM_FOCAVEL, resultado: 'tolera', regras: ['nested-interactive'] },
  { controle: 'color-contrast em .node-outline-heading > small', html: `${SVG_RAIZ_LIMPO}${SMALL_DO_INDICE}`, resultado: 'tolera', regras: ['color-contrast'] },
  { controle: 'os dois defeitos tolerados juntos', html: `${SVG_RAIZ_COM_FOCAVEL}${SMALL_DO_INDICE}`, resultado: 'tolera', regras: ['nested-interactive', 'color-contrast'] },
  { controle: 'nested-interactive num <svg> que não é o raiz', html: `${SVG_RAIZ_LIMPO}${SVG_OUTRO_COM_FOCAVEL}`, resultado: 'reprova', regras: ['nested-interactive'] },
  { controle: 'color-contrast num <small> fora de .node-outline-heading', html: `${SVG_RAIZ_LIMPO}<div class="outro"><small ${POUCO_CONTRASTE}>3 peças</small></div>`, resultado: 'reprova', regras: ['color-contrast'] },
  { controle: 'color-contrast num parágrafo do corpo', html: `${SVG_RAIZ_LIMPO}<p ${POUCO_CONTRASTE}>texto do corpo</p>`, resultado: 'reprova', regras: ['color-contrast'] },
  { controle: 'button-name (serious) num botão sem nome', html: `${SVG_RAIZ_LIMPO}<button type="button"></button>`, resultado: 'reprova', regras: ['button-name'] },
  {
    controle: 'color-contrast de dois alvos: .node-outline-heading > small e um parágrafo do corpo',
    html: `${SVG_RAIZ_LIMPO}${SMALL_DO_INDICE}<p ${POUCO_CONTRASTE}>texto do corpo</p>`,
    resultado: 'reprova pelo parágrafo',
    regras: ['color-contrast'],
  },
];

/** A decisão de um controle: o que a mesma função da amostra decide sobre a página sintética. */
function decisaoDoControle(violacoesDoAxe, regras) {
  const daRegra = violacoesDoAxe.filter((v) => regras.includes(v.regra));
  const restantes = violacoesDoAxe.flatMap((v) => v.restantes);
  return {
    produziu: regras.every((regra) => daRegra.some((v) => v.regra === regra)),
    toleradas: violacoesDoAxe.reduce((soma, v) => soma + v.toleradas, 0),
    restantes,
    resultado: restantes.length > 0 ? 'reprova' : 'tolera',
  };
}

async function conferirControlesDaTolerancia(navegador) {
  const pagina = await navegador.newPage();
  for (const { controle, html, resultado, regras } of CONTROLES) {
    await pagina.setContent(pagina_(html));
    await pagina.addScriptTag({ content: AXE });
    const decisao = decisaoDoControle(await pagina.evaluate(decidirNaPagina), regras);
    const esperado = resultado.startsWith('reprova') ? 'reprova' : 'tolera';
    if (!decisao.produziu) {
      reprovar('CT-B06', `controle "${controle}" — a página sintética não produziu a violação ${regras.join(' + ')}`);
    } else if (decisao.resultado !== esperado) {
      reprovar('CT-B06', `controle "${controle}" — esperava ${esperado}, e a função decidiu ${decisao.resultado}`);
    } else if (esperado === 'tolera' && decisao.toleradas === 0) {
      reprovar('CT-B06', `controle "${controle}" — tolerou sem nenhum alvo tolerado`);
    } else if (resultado === 'reprova pelo parágrafo' && !(decisao.restantes.length === 1 && decisao.restantes[0].startsWith('<p') && decisao.toleradas === 1)) {
      reprovar('CT-B06', `controle "${controle}" — devia reprovar só pelo parágrafo, e restou ${JSON.stringify(decisao.restantes)} (toleradas: ${decisao.toleradas})`);
    }
    contar('CT-B06');
  }
  await pagina.close();
}

/** O controle de processo: este mesmo arquivo, rodado como processo, sobre uma prévia em que o HTML do diagrama não desenha nada. */
async function conferirControleDeProcesso() {
  const pasta = mkdtempSync(join(tmpdir(), 'controle-mapa-'));
  const slug = 'tempos-ci';
  let servidor;
  try {
    mkdirSync(join(pasta, 'diagramas'), { recursive: true });
    cpSync(join(DIAGRAMAS, `${slug}.json`), join(pasta, 'diagramas', `${slug}.json`));
    for (const idioma of IDIOMAS) {
      mkdirSync(join(pasta, 'dist', 'mapa', idioma), { recursive: true });
      writeFileSync(join(pasta, 'dist', 'mapa', idioma, `${slug}.html`), '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>vazio</title></head><body><h1>vazio</h1></body></html>');
    }
    servidor = createServer((requisicao, resposta) => {
      resposta.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
      resposta.end(readFileSync(join(pasta, 'dist', 'mapa', requisicao.url.includes('/en/') ? 'en' : 'pt', `${slug}.html`)));
    });
    await new Promise((pronto) => servidor.listen(0, '127.0.0.1', pronto));
    const porta = String(servidor.address().port);

    const filho = spawn(process.execPath, [new URL(import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1'), porta], {
      env: {
        ...process.env,
        DOCS_BASE: '',
        VERIFICA_MAPA_DIAGRAMAS: join(pasta, 'diagramas'),
        VERIFICA_MAPA_DIST: join(pasta, 'dist'),
        VERIFICA_MAPA_TIMEOUT_MS: '3000',
        VERIFICA_MAPA_CONTROLE: '1',
      },
      stdio: ['ignore', 'pipe', 'pipe'],
    });
    let saida = '';
    filho.stdout.on('data', (pedaco) => (saida += pedaco));
    filho.stderr.on('data', (pedaco) => (saida += pedaco));
    const codigo = await new Promise((pronto) => filho.on('close', pronto));

    if (codigo === 0 || codigo === null) {
      reprovar('CT-B06', `controle de processo — o conferidor terminou com código ${codigo} sobre um HTML que não desenha nada, e devia terminar com código diferente de zero`);
    } else if (!saida.includes(slug)) {
      reprovar('CT-B06', `controle de processo — o código de saída foi ${codigo}, mas o log não nomeia o diagrama ${slug}`);
    }
    contar('CT-B06');
    return { codigo, saida };
  } finally {
    servidor?.close();
    rmSync(pasta, { recursive: true, force: true });
  }
}

// ---------------------------------------------------------------------------------------------------
// CT-B07 — o catálogo pt que o kit entrega
// ---------------------------------------------------------------------------------------------------
function conferirCatalogoPt() {
  // as cópias, em memória, decididas pela mesma função que decide o catálogo real
  const ptBase = { ...CATALOGO_EN, ...Object.fromEntries(Object.keys(LISTA_FECHADA).map((chave) => [chave, CATALOGO_EN[chave]])) };
  const ptTraduzido = Object.fromEntries(Object.entries(CATALOGO_EN).map(([chave, valor]) => [chave, LISTA_FECHADA[chave] ? valor : `${valor} (pt)`]));
  void ptBase;
  const copias = [
    { nome: 'texto en copiado em viewer.finder.placeholder', pt: { ...ptTraduzido, 'viewer.finder.placeholder': CATALOGO_EN['viewer.finder.placeholder'] }, lista: LISTA_FECHADA, chave: 'viewer.finder.placeholder' },
    { nome: 'chave viewer.passport.evidence ausente', pt: Object.fromEntries(Object.entries(ptTraduzido).filter(([chave]) => chave !== 'viewer.passport.evidence')), lista: LISTA_FECHADA, chave: 'viewer.passport.evidence' },
    { nome: 'viewer.guide.facts sem motivo', pt: ptTraduzido, lista: { ...LISTA_FECHADA, 'viewer.guide.facts': '' }, chave: 'viewer.guide.facts' },
    { nome: 'viewer.guide.facts com o motivo "—"', pt: ptTraduzido, lista: { ...LISTA_FECHADA, 'viewer.guide.facts': '—' }, chave: 'viewer.guide.facts' },
    {
      nome: 'texto en em viewer.finder.placeholder e a chave na lista, com motivo',
      pt: { ...ptTraduzido, 'viewer.finder.placeholder': CATALOGO_EN['viewer.finder.placeholder'] },
      lista: { ...LISTA_FECHADA, 'viewer.finder.placeholder': 'o texto de exemplo da busca se escreve igual' },
      chave: 'viewer.finder.placeholder',
    },
  ];

  // a cópia "válida" do mundo de controle: o pt traduzido com a lista fechada passa
  if (decidirCatalogoPt(CATALOGO_EN, ptTraduzido, LISTA_FECHADA).length > 0) {
    reprovar('CT-B07', `controle (catálogo traduzido com a lista fechada) — devia aceitar, e reprovou: ${decidirCatalogoPt(CATALOGO_EN, ptTraduzido, LISTA_FECHADA)[0]}`);
  }
  for (const { nome, pt, lista, chave } of copias) {
    const reprovadas = decidirCatalogoPt(CATALOGO_EN, pt, lista);
    if (!reprovadas.some((linha) => linha.startsWith(`${chave} `))) {
      reprovar('CT-B07', `controle "${nome}" — devia reprovar nomeando ${chave}, e decidiu ${JSON.stringify(reprovadas)}`);
    }
    contar('CT-B07');
  }

  // o catálogo real: o de cada HTML pt publicado, fora o comparativo
  if (Object.keys(CATALOGO_EN).length !== 486) {
    reprovar('CT-B07', `o catálogo en do archify tem ${Object.keys(CATALOGO_EN).length} chaves, e o fixado tem 486`);
  }
  for (const d of diagramas.filter((x) => x.tipo !== 'compare')) {
    const embutidas = mensagensEmbutidas('pt', d.slug);
    if (embutidas === null) {
      reprovar('CT-B07', `pt/${d.slug} — o HTML não tem o catálogo embutido (archify-i18n-data)`);
      continue;
    }
    const reprovadas = decidirCatalogoPt(CATALOGO_EN, catalogoPtDoHtml(d.slug, d.tipo), LISTA_FECHADA);
    if (reprovadas.length > 0) {
      reprovar('CT-B07', `pt/${d.slug} — catálogo pt reprovado: ${reprovadas.slice(0, 3).join('; ')}`);
    }
    contar('CT-B07');
  }
}

// ---------------------------------------------------------------------------------------------------
// Execução
// ---------------------------------------------------------------------------------------------------
const resumo = { abertos: { pt: 0, en: 0 }, pacote: { pt: 0, en: 0 }, kit: { pt: 0, en: 0 } };
const navegador = await chromium.launch();

for (const idioma of IDIOMAS) {
  for (const d of diagramas) {
    await conferirDiagrama(navegador, d, idioma, resumo);
  }
}
await conferirAmostra(navegador);
conferirComposicaoDaAmostra();
if (!MODO_CONTROLE) {
  await conferirControlesDaTolerancia(navegador);
}
await navegador.close();

if (!MODO_CONTROLE) {
  conferirCatalogoPt();
  await conferirControleDeProcesso();
}

// totais do CT-B01 e do CT-B03
for (const idioma of IDIOMAS) {
  if (resumo.abertos[idioma] !== diagramas.length || diagramas.length === 0) {
    reprovar('CT-B01', `${resumo.abertos[idioma]} diagramas abertos em ${idioma}, e a fonte tem ${diagramas.length} arquivos`);
  }
  if (representantes.has('architecture') && resumo.pacote[idioma] === 0) {
    reprovar('CT-B03', `a amostra em ${idioma} não tem peça de pacote em diagrama nenhum`);
  }
  if (representantes.has('architecture') && resumo.kit[idioma] === 0) {
    reprovar('CT-B03', `a amostra em ${idioma} não tem peça do kit em diagrama nenhum`);
  }
}
if (conferencias['CT-B01:F-nn'] === undefined && diagramas.some((x) => x.construtor)) {
  reprovar('CT-B01', 'nenhuma peça com F-nn foi conferida no desenho (o total devia ser maior que zero)');
}
if ((conferencias['CT-B02'] ?? 0) === 0) {
  reprovar('CT-B02', 'nenhuma ligação desenhada foi conferida (o total devia ser maior que zero)');
}

console.log(`mapa: ${resumo.abertos.pt} diagramas abertos em pt e ${resumo.abertos.en} em en, de ${diagramas.length} arquivos`);
console.log(`mapa: amostra de acessibilidade, ${auditadas.length} HTML conferidos de ${amostra.length} (${representantes.size} tipos)`);
console.log(
  `tolerados (ADR-06): nested-interactive no <svg> raiz ${toleradas['nested-interactive']}×, ` +
    `color-contrast na contagem do índice ${toleradas['color-contrast']}×`,
);
for (const ct of ['CT-B01', 'CT-B01:F-nn', 'CT-B02', 'CT-B03', 'CT-B04', 'CT-B05', 'CT-B06', 'CT-B07']) {
  const base = ct.split(':')[0];
  const reprovacoes = violacoes.filter((v) => v.startsWith(`[${base}]`)).length;
  console.log(`  ${ct}: ${conferencias[ct] ?? 0} conferências, ${ct.includes(':') ? 'incluído no CT-B01' : `${reprovacoes} reprovação(ões)`}`);
}

/*
 * Piso de população, como nos outros conferidores do site: sem diagrama nenhum, a amostra seria
 * vazia e o conferidor diria que está tudo bem sobre o nada.
 */
let reprovado = false;
if (diagramas.length === 0) {
  console.error('REPROVADO — mapa/diagramas/ não tem diagrama nenhum');
  reprovado = true;
}
if (violacoes.length > 0) {
  console.error(`REPROVADO — ${violacoes.length} problema(s) no mapa:`);
  for (const v of [...new Set(violacoes)]) {
    console.error(`  - ${v}`);
  }
  reprovado = true;
}
if (!reprovado) {
  console.log('OK — mapa conferido (CT-B01 a CT-B07), sem violação serious/critical fora das duas toleradas, e sem erro no console.');
}

process.exitCode = reprovado ? 1 : 0;
