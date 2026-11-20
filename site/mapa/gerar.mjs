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
 *  - os campos do kit — `slug`, `tipo`, `titulo`, `painel`, `modos` e, no comparativo, `base` e
 *    `cabeca` — saem antes de o arquivo chegar ao archify.
 *
 * Para no primeiro diagrama que o archify recusar, com o diagnóstico dele, e sai com código 1.
 */
import { execFileSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
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

/** A entrada do archify de um diagrama, num idioma. */
function entradaDoArchify(diagrama, idioma, catalogoPt) {
  const { slug, tipo, titulo, painel, modos, ...entrada } = diagrama;
  const ir = projetar(entrada, idioma);
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

function gerar() {
  const cli = obterArchify();
  const catalogoPt = lerJson(fileURLToPath(new URL('../.archify/archify/examples/locales/pt.json', import.meta.url)));
  const diagramas = readdirSync(DIAGRAMAS)
    .filter((nome) => nome.endsWith('.json'))
    .sort()
    .map((nome) => lerJson(join(DIAGRAMAS, nome)));
  const porSlug = new Map(diagramas.map((diagrama) => [diagrama.slug, diagrama]));
  const temporaria = mkdtempSync(join(tmpdir(), 'mapa-'));

  rmSync(SAIDA, { recursive: true, force: true });
  try {
    for (const idioma of Object.keys(IDIOMAS)) {
      mkdirSync(join(SAIDA, idioma), { recursive: true });

      for (const diagrama of diagramas) {
        const { slug, tipo } = diagrama;
        const destino = join(SAIDA, idioma, `${slug}.html`);
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

try {
  gerar();
} catch (erro) {
  console.error(erro.message);
  process.exitCode = 1;
}
