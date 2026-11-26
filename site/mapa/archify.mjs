/**
 * Entrega a CLI do archify num commit fixo — ADR-02 da wiki `mapa-interativo-do-kit`.
 *
 * O archify não está no registro npm (o `package.json` dele é `private`) e não tem dependência de
 * runtime: a CLI roda direto no Node que o site já usa. Por isso ele não entra no `package.json` do
 * site — entra aqui, por um download raso do commit declarado em `ARCHIFY_COMMIT` para
 * `site/.archify/`, que o git ignora.
 *
 * Falha FECHADO: se o download não acontece, ou se o commit que ficou na pasta não é o declarado, o
 * build para. Um mapa gerado por outra versão do archify mudaria o HTML que a amostra de
 * acessibilidade (ADR-06) confere, e a tolerância dela é por regra e elemento de uma versão só.
 *
 * Trocar de versão é mudar `ARCHIFY_COMMIT` e regenerar — e rever a tolerância da ADR-06.
 */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

export const ARCHIFY_COMMIT = '44781293f38728baf04dbad33ce1a0cef8c39cca';

const REPOSITORIO = 'https://github.com/tt-a1i/archify.git';
const PASTA = fileURLToPath(new URL('../.archify/', import.meta.url));

const git = (...args) =>
  execFileSync('git', ['-C', PASTA, ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();

/*
 * `rev-parse` só vale quando a pasta tem o PRÓPRIO `.git`: sem ele, o git sobe até o repositório
 * do kit e devolveria o commit do kit, não o do archify.
 */
const commitBaixado = () => {
  if (!existsSync(`${PASTA}.git`)) return null;
  try {
    return git('rev-parse', 'HEAD');
  } catch {
    return null;
  }
};

/** Caminho da CLI do archify no commit declarado; baixa na primeira vez. */
export function obterArchify() {
  try {
    if (commitBaixado() !== ARCHIFY_COMMIT) {
      mkdirSync(PASTA, { recursive: true });
      if (!existsSync(`${PASTA}.git`)) git('init', '--quiet');
      git('fetch', '--quiet', '--depth', '1', REPOSITORIO, ARCHIFY_COMMIT);
      git('checkout', '--quiet', '--force', 'FETCH_HEAD');
    }
    const baixado = commitBaixado();
    if (baixado !== ARCHIFY_COMMIT) {
      throw new Error(`o commit em site/.archify/ é ${baixado}, e o declarado é ${ARCHIFY_COMMIT}`);
    }
  } catch (erro) {
    const motivo = String(erro.stderr || erro.message).trim();
    throw new Error(
      `[mapa] ERRO archify — ${motivo}. O build precisa de git e de acesso a github.com; ` +
        'apague site/.archify/ e rode de novo.',
    );
  }
  console.log(`[mapa] archify ${ARCHIFY_COMMIT.slice(0, 7)} pronto em ${PASTA}`);
  return `${PASTA}archify/bin/archify.mjs`;
}
