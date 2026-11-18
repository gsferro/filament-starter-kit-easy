// Roteiro Playwright da validação manual (wiki preferencias-de-aparencia). Rodar da raiz do repositório:
// node <esta pasta>/manual.cjs <baseUrl> <tenancy 0|1> <pastaDeSaida>. Banco: ver prepara.sh.
const path = require('path');
const fs = require('fs');
const { chromium } = require(path.resolve('node_modules/playwright'));

const [base, tenancyArg, saida] = process.argv.slice(2);
const tenancy = tenancyArg === '1';
fs.mkdirSync(saida, { recursive: true });
const resultados = [];
const errosJs = [];
let n = 0;

function registra(nome, ok, detalhe = '') {
  resultados.push({ nome, ok, detalhe });
  console.log(`${ok ? 'OK   ' : 'FALHA'} ${nome}${detalhe ? ' — ' + detalhe : ''}`);
}

async function foto(page, nome) {
  n += 1;
  await page.screenshot({ path: path.join(saida, `${String(n).padStart(2, '0')}-${nome}.png`), fullPage: false });
}

async function entrar(page) {
  await page.goto(`${base}/admin/login`);
  await page.locator('input[type=email]').fill('master@manual.test');
  await page.locator('input[type=password]').fill('senha-manual-123');
  await page.locator('button[type=submit]').click();
  await page.waitForURL(/\/admin(?!\/login)/, { timeout: 150000 });
}

// O estilo que o kit emite no <head> (densidade + fonte), ou '' quando não há nenhum.
async function estiloDoKit(page) {
  return page.evaluate(() => {
    const s = [...document.querySelectorAll('style')].map((e) => e.textContent).find((t) => t.startsWith(':root{--spacing') || t.startsWith(':root{--text-'));
    return s || '';
  });
}

async function variavel(page, nome) {
  return page.evaluate((v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim(), nome);
}

async function classesDoBody(page) {
  return page.evaluate(() => document.body.className);
}

async function selecionar(page, campo, valor) {
  const select = page.locator(`select[id$=".${campo}"], select[id$="data.${campo}"]`).first();
  await select.selectOption(valor);
}

async function salvarAparencia(page) {
  await page.getByRole('button', { name: 'Salvar aparência' }).click();
  await page.waitForLoadState('load');
  await page.waitForTimeout(1500);
}

async function abrir(page, url) {
  await page.goto(url);
  await page.waitForLoadState('load');
  await page.waitForTimeout(800);
}

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();
  page.setDefaultTimeout(150000);
  page.setDefaultNavigationTimeout(150000);
  page.on('pageerror', (e) => errosJs.push(String(e)));

  await entrar(page);

  // 1. Sem escolha: nada emitido, menu de ícones, cor padrão.
  await abrir(page, `${base}/admin/meu-perfil`);
  const corPadrao = await variavel(page, '--primary-500');
  registra('perfil sem escolha: nenhum <style> do kit', (await estiloDoKit(page)) === '', await estiloDoKit(page));
  registra('perfil sem escolha: menu recolhe mantendo ícones', (await classesDoBody(page)).includes('fi-body-has-sidebar-collapsible-on-desktop'));
  const secao = page.getByText('Aparência', { exact: true }).first();
  registra('seção Aparência visível no Meu perfil', await secao.isVisible());
  await foto(page, 'perfil-padrao');

  // 2. Escolhe tudo e salva.
  await selecionar(page, 'densidade', 'denso');
  await selecionar(page, 'fonte', 'grande');
  await selecionar(page, 'cor', 'Purple');
  await selecionar(page, 'menu', 'oculto');
  await salvarAparencia(page);
  const estilo = await estiloDoKit(page);
  registra('salvar aplica densidade densa', estilo.includes('--spacing:0.175rem'), estilo.slice(0, 80));
  registra('salvar aplica fonte grande (--text-sm 0.9844rem)', estilo.includes('--text-sm:0.9844rem'));
  registra('largura do menu acompanha a densidade', (await variavel(page, '--sidebar-width')) === '16.5rem', await variavel(page, '--sidebar-width'));
  const corPessoa = await variavel(page, '--primary-500');
  registra('cor primária mudou para a da pessoa', corPessoa !== '' && corPessoa !== corPadrao, `${corPadrao} -> ${corPessoa}`);
  registra('menu passa a esconder tudo', (await classesDoBody(page)).includes('fi-body-has-sidebar-fully-collapsible-on-desktop'));
  registra('aviso "Aparência salva"', await page.getByText('Aparência salva').first().isVisible().catch(() => false));
  await foto(page, 'perfil-escolhido');

  // 3. Esconder tudo: recolhe e reabre pelo botão do topo.
  await abrir(page, `${base}/admin`);
  const fechar = page.locator('.fi-topbar-close-collapse-sidebar-btn, .fi-sidebar-close-collapse-sidebar-btn').first();
  if (await fechar.isVisible().catch(() => false)) {
    await fechar.click();
    await page.waitForTimeout(600);
  }
  const larguraFechado = await page.evaluate(() => { const r = document.querySelector('.fi-sidebar')?.getBoundingClientRect(); return r ? Math.max(0, Math.min(r.width, r.right)) : -1; });
  await foto(page, 'menu-oculto-recolhido');
  const abrirBtn = page.locator('.fi-topbar-open-sidebar-btn:visible, .fi-layout-sidebar-toggle-btn:visible, .fi-topbar-open-collapse-sidebar-btn:visible').first();
  const temAbrir = await abrirBtn.isVisible().catch(() => false);
  registra('menu oculto recolhido some e deixa o botão de reabrir no topo', larguraFechado <= 1 && temAbrir, `largura ${larguraFechado}, botão ${temAbrir}`);
  if (temAbrir) {
    await abrirBtn.click();
    await page.waitForTimeout(600);
  }
  const larguraAberto = await page.evaluate(() => { const r = document.querySelector('.fi-sidebar')?.getBoundingClientRect(); return r ? Math.max(0, Math.min(r.width, r.right)) : -1; });
  registra('o botão do topo reabre o menu', larguraAberto > 200, `largura ${larguraAberto}`);

  // 4. A escolha vale no /infra também (uma por pessoa).
  await abrir(page, `${base}/infra`);
  registra('/infra também com a densidade da pessoa', (await estiloDoKit(page)).includes('--spacing:0.175rem'));
  await foto(page, 'infra-escolhido');

  // 5. Com organizações: Acme liberou, Globex não.
  if (tenancy) {
    await abrir(page, `${base}/app/acme`);
    const corAcme = await variavel(page, '--primary-500');
    registra('Acme (liberou): densidade da pessoa', (await estiloDoKit(page)).includes('--spacing:0.175rem'));
    registra('Acme (liberou): cor da pessoa vence a da organização', corAcme === corPessoa, `${corAcme} vs ${corPessoa}`);
    await foto(page, 'app-acme');
    await abrir(page, `${base}/app/globex`);
    const corGlobex = await variavel(page, '--primary-500');
    registra('Globex (não liberou): nenhum estilo pessoal', (await estiloDoKit(page)) === '');
    registra('Globex (não liberou): cor da organização (Rose), não a da pessoa', corGlobex !== corPessoa && corGlobex !== corPadrao, corGlobex);
    registra('Globex (não liberou): menu de ícones', (await classesDoBody(page)).includes('fi-body-has-sidebar-collapsible-on-desktop'));
    await foto(page, 'app-globex');

    // O interruptor da organização aparece no cadastro dela.
    await abrir(page, `${base}/admin/organizacoes`);
    await page.getByText('Globex').first().click();
    await page.waitForTimeout(1200);
    if (!page.url().includes('/edit')) {
      await abrir(page, page.url().replace(/\/?$/, '/edit'));
    }
    registra('cadastro da organização mostra "Liberar a aparência pessoal"', await page.getByText('Liberar a aparência pessoal').first().isVisible().catch(() => false), page.url());
    await foto(page, 'organizacao-interruptor');
  }

  // 6. Desliga a chave da instalação pela tela: a seção some e todo mundo volta ao padrão.
  await abrir(page, `${base}/admin/configuracoes-da-aplicacao?tab=aplicacao`);
  await page.waitForTimeout(1500);
  const chave = page.locator('[id$="aparencia_por_usuario"]').first();
  await chave.scrollIntoViewIfNeeded().catch(() => {});
  await foto(page, 'configuracoes-secao-aparencia');
  await chave.click();
  await page.getByRole('button', { name: /Salvar/ }).first().click();
  await page.waitForTimeout(2000);
  await abrir(page, `${base}/admin/meu-perfil`);
  registra('chave desligada: seção Aparência some do perfil', !(await page.getByRole('button', { name: 'Salvar aparência' }).isVisible().catch(() => false)));
  registra('chave desligada: nenhum estilo pessoal', (await estiloDoKit(page)) === '');
  registra('chave desligada: cor volta ao padrão', (await variavel(page, '--primary-500')) === corPadrao);
  await foto(page, 'perfil-chave-desligada');

  // 7. Religa: a escolha gravada volta (não foi apagada).
  await abrir(page, `${base}/admin/configuracoes-da-aplicacao?tab=aplicacao`);
  await page.waitForTimeout(1500);
  await page.locator('[id$="aparencia_por_usuario"]').first().click();
  await page.getByRole('button', { name: /Salvar/ }).first().click();
  await page.waitForTimeout(2000);
  await abrir(page, `${base}/admin/meu-perfil`);
  registra('religada: a escolha gravada volta a valer', (await estiloDoKit(page)).includes('--spacing:0.175rem'));

  registra('nenhum erro de JavaScript', errosJs.length === 0, errosJs.join(' | '));
  fs.writeFileSync(path.join(saida, 'resultado.json'), JSON.stringify({ tenancy, resultados, errosJs }, null, 2));
  await browser.close();
  const falhas = resultados.filter((r) => !r.ok).length;
  console.log(`\n${resultados.length - falhas}/${resultados.length} ok`);
  process.exit(falhas ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
