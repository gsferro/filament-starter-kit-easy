/**
 * Busca uma grade que o archify aceite para um mapa de arquitetura e grava as posições no arquivo do
 * diagrama — ADR-07 da wiki `mapa-interativo-do-kit`. Ferramenta de quem escreve diagramas: NUNCA roda
 * no build (o layout fica versionado no arquivo, e buscar layout no build tornaria o build lento e
 * instável a cada mudança do kit).
 *
 *   node mapa/posicionar.mjs <slug> [--tentativas 200]
 *
 * Começa por camadas no sentido das setas (quem registra e usa à esquerda, quem é usado à direita),
 * ordena cada coluna pela média das vizinhas e então troca e move caixas ao acaso, guardando a grade
 * com menos diagnósticos do `archify validate`, até não sobrar nenhum.
 */
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { obterArchify } from './archify.mjs';
import { entradaDoArchify, idArchify, paraOComparativo } from './gerar.mjs';

const [slug, ...opcoes] = process.argv.slice(2);
if (!slug) {
  console.error('uso: node mapa/posicionar.mjs <slug> [--tentativas 200]');
  process.exit(2);
}
// O argumento é conferido antes de qualquer efeito — ler o diagrama, baixar o archify, gravar (P-14).
const posicao = opcoes.indexOf('--tentativas');
const valor = posicao >= 0 ? opcoes[posicao + 1] : '200';
if (!/^[1-9][0-9]*$/.test(valor ?? '')) {
  console.error(`[mapa] ERRO --tentativas: espera um inteiro positivo, e recebeu ${valor === undefined ? 'nada' : JSON.stringify(valor)}`);
  process.exit(2);
}
const TENTATIVAS = Number(valor);

const RAIZ = fileURLToPath(new URL('../../', import.meta.url));
const arquivo = fileURLToPath(new URL(`./diagramas/${slug}.json`, import.meta.url));
let diagrama;
try {
  diagrama = JSON.parse(readFileSync(arquivo, 'utf8'));
} catch (erro) {
  console.error(`[mapa] ERRO site/mapa/diagramas/${slug}.json — ${erro.code === 'ENOENT' ? 'o diagrama não existe' : `JSON inválido: ${erro.message}`}`);
  process.exit(2);
}
if (diagrama.construtor !== 'mapa') {
  console.error(`[mapa] ${slug} não é um mapa de arquitetura construído do modelo`);
  process.exit(2);
}
// O mapa que é base ou cabeça de um comparativo também tem de valer na forma que o comparativo recebe; os outros
// diagramas são lidos antes de qualquer efeito, e o quebrado é recusado nomeando o arquivo (P-14).
const doComparativo = readdirSync(fileURLToPath(new URL('./diagramas/', import.meta.url)))
  .filter((nome) => nome.endsWith('.json'))
  .map((nome) => {
    try {
      return JSON.parse(readFileSync(fileURLToPath(new URL(`./diagramas/${nome}`, import.meta.url)), 'utf8'));
    } catch (erro) {
      console.error(`[mapa] ERRO site/mapa/diagramas/${nome} — JSON inválido: ${erro.message}`);
      process.exit(2);
    }
  })
  .some((outro) => outro.tipo === 'compare' && [outro.base, outro.cabeca].includes(slug));
const cli = obterArchify();
const catalogoPt = JSON.parse(readFileSync(fileURLToPath(new URL('../.archify/archify/examples/locales/pt.json', import.meta.url)), 'utf8'));
const temporaria = mkdtempSync(join(tmpdir(), 'posicionar-'));

/** As caixas do mapa: peças, agrupamentos e portas, cada uma com a chave do archify. */
const caixas = [
  ...diagrama.pecas.map((c) => ({ origem: c, chave: idArchify(c.id) })),
  ...(diagrama.agrupamentos ?? []).map((c) => ({ origem: c, chave: c.id })),
  ...(diagrama.portas ?? []).map((c) => ({ origem: c, chave: `porta-${c.para}` })),
];

/** Os diagnósticos que o archify dá para uma entrada; zero é aceita. */
function validar(ir) {
  const entrada = join(temporaria, 'mapa.json');
  writeFileSync(entrada, JSON.stringify(ir));
  try {
    execFileSync(process.execPath, [cli, 'validate', 'architecture', entrada, '--json', '--repo-root', RAIZ], {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, ARCHIFY_UPDATE_CHECK_DISABLED: '1' },
    });
    return { nota: 0, erro: '' };
  } catch (falha) {
    let erro = String(falha.stderr || falha.message);
    try {
      erro = JSON.parse(falha.stdout).error ?? erro;
    } catch {
      // saída sem JSON: fica o stderr
    }
    return { nota: (erro.match(/\n- /g) ?? []).length || 1, erro };
  }
}

/** Quantos diagnósticos o archify dá para a grade (e, no mapa de comparativo, também na forma dele); zero é aceita. */
function avaliar(grade) {
  for (const caixa of caixas) Object.assign(caixa.origem, grade.get(caixa.chave));
  const ir = entradaDoArchify(diagrama, 'pt', catalogoPt);
  const mapa = validar(ir);
  if (!doComparativo) return mapa;
  const comparativo = validar(paraOComparativo(ir));
  return { nota: mapa.nota + comparativo.nota, erro: [mapa.erro, comparativo.erro].filter(Boolean).join('\n') };
}

// As ligações desenhadas, para as camadas: as do próprio construtor, sem posição nenhuma.
const ir = entradaDoArchify({ ...diagrama, pecas: diagrama.pecas.map((c) => ({ ...c, row: 0, col: 0 })) }, 'pt', catalogoPt);
const setas = ir.connections.map((c) => [c.from, c.to]);

/** Camadas pelo maior caminho a partir das fontes (ciclos quebrados na ordem da busca). */
function camadas() {
  const saidas = new Map(caixas.map((c) => [c.chave, []]));
  for (const [de, para] of setas) saidas.get(de)?.push(para);
  const camada = new Map();
  const visitando = new Set();
  const visitar = (no) => {
    if (camada.has(no)) return camada.get(no);
    if (visitando.has(no)) return 0;
    visitando.add(no);
    const entradas = setas.filter(([, para]) => para === no).map(([de]) => de);
    const valor = entradas.length ? Math.max(...entradas.map((de) => visitar(de) + 1)) : 0;
    visitando.delete(no);
    camada.set(no, valor);
    return valor;
  };
  for (const caixa of caixas) visitar(caixa.chave);
  return camada;
}

const camada = camadas();
/*
 * Grade equilibrada: o visualizador encolhe o desenho alto até caber na tela, e uma coluna só de 24 caixas
 * derruba o texto abaixo do piso de leitura (ADR-07). As caixas vão, na ordem das camadas, para cerca de
 * √(1,5·n) colunas, até o teto do mapa — quem registra e usa continua à esquerda de quem é usado.
 */
// `layout.teto` (campo do kit) limita as colunas; `layout.cols` é a grade que o archify recebe.
const colunas = Math.max(2, Math.min(diagrama.layout?.teto ?? 4, Math.max(Math.ceil(Math.sqrt(caixas.length * 1.5)), Math.max(...camada.values()) + 1)));
diagrama.layout = { ...diagrama.layout, cols: colunas }; // a grade do archify já com as colunas desta busca
const porColunaInicial = Math.ceil(caixas.length / colunas);
const linhas = porColunaInicial + 1;

/** A grade inicial: em ordem de camada, enchendo uma coluna de cada vez. */
let melhor = new Map();
[...caixas]
  .sort((a, b) => camada.get(a.chave) - camada.get(b.chave))
  .forEach((caixa, i) => melhor.set(caixa.chave, { row: i % porColunaInicial, col: Math.floor(i / porColunaInicial) }));
let { nota: melhorNota, erro: ultimoErro } = avaliar(melhor);
console.log(`[mapa] layout ${slug} — tentativa 0: ${melhorNota} diagnósticos`);

let atual = new Map(melhor);
let notaAtual = melhorNota;
for (let tentativa = 1; tentativa <= TENTATIVAS && melhorNota > 0; tentativa++) {
  const candidata = new Map(atual);
  const chaves = [...candidata.keys()];
  const escolhida = chaves[Math.floor(Math.random() * chaves.length)];
  const destino = { row: Math.floor(Math.random() * linhas), col: Math.floor(Math.random() * colunas) };
  const ocupante = chaves.find((c) => candidata.get(c).row === destino.row && candidata.get(c).col === destino.col);
  if (ocupante) candidata.set(ocupante, candidata.get(escolhida));
  candidata.set(escolhida, destino);
  const { nota, erro } = avaliar(candidata);
  if (nota <= notaAtual) {
    atual = candidata;
    notaAtual = nota;
  }
  if (nota < melhorNota) {
    melhor = candidata;
    melhorNota = nota;
    ultimoErro = erro;
    console.log(`[mapa] layout ${slug} — tentativa ${tentativa}: ${nota} diagnósticos`);
  }
}

rmSync(temporaria, { recursive: true, force: true });
if (melhorNota > 0) {
  console.error(`[mapa] ERRO ${slug} — nenhuma grade aceita em ${TENTATIVAS} tentativas; a melhor tem ${melhorNota}:`);
  console.error(ultimoErro.split('\n').slice(0, 8).join('\n'));
  process.exit(1);
}
for (const caixa of caixas) Object.assign(caixa.origem, melhor.get(caixa.chave));
diagrama.layout = { ...diagrama.layout, cols: colunas };
writeFileSync(arquivo, JSON.stringify(diagrama, null, 2) + '\n');
console.log(`[mapa] layout ${slug} gravado: ${colunas} colunas, ${caixas.length} caixas, ${setas.length} setas`);
