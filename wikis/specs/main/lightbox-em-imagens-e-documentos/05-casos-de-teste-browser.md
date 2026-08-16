# Casos de Teste de Browser — Lightbox em imagens e documentos

> Runtime: `pest-plugin-browser` 5 (Playwright). O plugin sobe o próprio servidor HTTP in-process.
> Comando: `composer test:browser` (embute `npm run build`) ou `vendor/bin/pest --testsuite=Browser`.
> **Nunca `--parallel`** — `.ai/rules/testes-browser.md` mediu 4 de 11 cenários caindo.

## Por que existe CT-B nesta feature

A tabela `## Superfície de UI` do PRD tem três linhas, todas marcadas "depende de JS". O gate é
mais estreito que isso, e só **um** cenário o atravessa:

| Afirmação | Só o navegador prova? | Onde vive |
|---|---|---|
| a miniatura aparece com a URL certa | não — HTML renderizado | CT-01 / CT-02 (componente) |
| o gatilho `x-on:click` está na miniatura | não — HTML renderizado | CT-01 / CT-02 (componente) |
| registro sem mídia não tem miniatura | não — HTML renderizado | CT-04 (componente) |
| **o overlay do lightbox aparece depois do clique** | **sim** — `fslightbox` constrói o DOM em runtime | **CT-B01** |

O caso que justifica o navegador é específico: o `x-on:click` pode estar perfeito no HTML e o
clique ser **inerte**, porque `php artisan filament:assets` não publicou o JS do pacote. Não há
erro, não há 500, não há nada no HTML que distinga os dois estados.

**Teto do perfil padrão**: 1 happy path. É o que está aqui.

## Pré-requisitos

- [ ] `npm run build` executado (pré-requisito **duro** — sem `public/build/manifest.json` toda tela responde `ViteException`)
- [ ] `php artisan filament:assets` executado — é justamente o que este cenário mede
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs(usuarioDoKit('master_global'))` antes do `visit()` — **nunca** login pela tela (custa ~20 s por cenário; `.ai/rules/testes-browser.md`)
- [ ] Seeders no `beforeEach`: `ShieldPermissionsSeeder` + `PapeisSeeder`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| miniatura com lightbox | `img.simple-light-box-img-indicator` | **sim** — classe injetada pelo macro (`SimpleLightBoxPlugin.php:52`, `extraImgAttributes`) |
| overlay do lightbox | container do `fslightbox` criado no `document.body` | **não confirmado** — resolver na implementação (ver abaixo) |
| linha da tabela | texto do nome da pessoa | sim |

> **Dívida conhecida do kit**: não há `data-testid` (`.ai/rules/testes-browser.md`). Aqui isso é
> tolerável porque a classe `simple-light-box-img-indicator` é **contrato do pacote**, não CSS de
> layout — se ela mudar, o clique deixou de funcionar e o teste vermelho está certo.
>
> **O seletor do overlay é o único ponto aberto do cenário.** O `fslightbox` injeta o container no
> `body` com marcação própria. Resolver **olhando a página**, não adivinhando: rodar
> `vendor/bin/pest --agent='visit("/admin/users")->screenshot();'` depois de implementar, ou usar
> o Playwright MCP em modo leitura (`browser_find`, `browser_generate_locator`) — nunca inventar o
> nome da classe. Enquanto o seletor não for confirmado, o cenário fica **bloqueado**, não
> "adaptado para passar".

---

## CT-B01: o lightbox abre sobre o avatar clicado

**Por que browser e não componente**: a assertion é sobre um elemento que **não existe no HTML
entregue pelo servidor**. Ele é criado por JavaScript, pelo `fslightbox`, depois do clique. Nenhum
teste de componente Livewire pode observá-lo.

```gherkin

# language: pt

Funcionalidade: Ampliar mídia sem sair da listagem

  Regra: o lightbox abre de fato sobre a imagem clicada

    Cenário: [CT-B01] clicar no avatar abre a imagem ampliada sobre a listagem
      Dado uma pessoa cujo avatar enviado está gravado
      E o administrador na listagem de usuários
      Quando ele clica na miniatura do avatar dessa pessoa
      Então a imagem ampliada aparece sobre a listagem
      E a listagem continua na mesma página
      E o console do navegador não registra erro
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | autenticar sem passar pela tela | `$this->actingAs(usuarioDoKit('master_global'));` | — |
| 2 | abrir a listagem | `$pagina = visit('/admin/users');` | tabela com a pessoa |
| 3 | conferir que a miniatura chegou | `$pagina->assertPresent('img.simple-light-box-img-indicator')` | miniatura visível |
| 4 | clicar na miniatura | `->click('img.simple-light-box-img-indicator')` | overlay abre |
| 5 | conferir o overlay | `->assertPresent('{seletor do fslightbox — confirmar}')` | imagem ampliada |
| 6 | conferir que não navegou | `->assertPathIs('/admin/users')` | mesma página |
| 7 | console | `->assertNoJavaScriptErrors()` | sem erro |

**Assertions**

- **Âncora do cenário**: a presença do overlay do `fslightbox` no DOM. É a única que distingue
  "JS publicado e funcionando" de "gatilho no HTML e script ausente".
- `assertPathIs('/admin/users')` — prova que o clique **não navegou**. Sem ela, uma implementação
  que trocasse o lightbox por um link para a imagem passaria no resto.
- `assertNoJavaScriptErrors()` e **não `assertNoSmoke()`**: a tela tem componentes de plugin de
  terceiro, e `.ai/rules/testes-browser.md` registra que `assertNoSmoke()` deixa a suíte vermelha
  por `console.log` alheio.
- **Assertion de apoio, nunca oráculo único**: `assertNoJavaScriptErrors()` sozinho passa com
  página em branco.

**Fatos do plugin aplicados aqui**

- `actingAs()` antes do `visit()` — mesmo processo, a sessão vale dentro do navegador
- nenhum `wait($segundos)`: o plugin reexecuta as assertions até `pest()->browser()->timeout()`,
  fixado em 20 s no `tests/Pest.php` do projeto
- `assertPathIs` **depois** do clique e **antes** de qualquer assertion de conteúdo que dependa de
  navegação — aqui ele prova o contrário (que **não** houve navegação), e por isso vem depois do
  overlay
- suíte `tests/Browser` (single-tenant): o cenário usa `/admin`, que não tem tenancy

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | `php artisan filament:assets` não executado — o JS do pacote não é publicado, o `x-on:click` existe e o clique é inerte | CT-B01 (passo 5) |
| M12 | a coluna vira um link (`->url()`) em vez de lightbox — o clique navega para a imagem | CT-B01 (passo 6) |
| M13 | `extraImgAttributes` perdido num refactor: a classe `simple-light-box-img-indicator` some, e o ramo "múltiplas imagens" do `SimpleLightBox.open()` não acha `src` nenhum — o lightbox abre **vazio** | CT-B01 (passo 5 falha se a assertion for sobre a imagem dentro do overlay, não só sobre o overlay). **Registrar na implementação**: preferir a assertion sobre a `<img>` de dentro do overlay |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| o mesmo clique na listagem de organizações (logo) | mata os mesmos mutantes que CT-B01, num painel já aquecido pelo cenário anterior. Browser em série é o recurso mais caro da suíte |
| fechar o lightbox com `Esc` | comportamento do `fslightbox`, não do kit — testar biblioteca de terceiro |
| lightbox em tema escuro | `assertSee` não valida tema, e o overlay do `fslightbox` é escuro por construção. Sem defeito plausível a matar |
| navegar entre várias imagens do lightbox | o kit tem uma mídia por linha; o modo "múltiplas imagens" só se manifestaria com várias `<img>` marcadas na mesma célula |
| CT-B do `/app` (suíte `BrowserTenancy`) | mesmo mutante, e o `BrowserTenancy` é a suíte mais cara do projeto (o primeiro cenário paga a compilação dos componentes Livewire — ~25 s medidos com views frias) |

---

## Roteiro de Validação: Desenhado × Implementado

> Preencher no step 7 da `feature-wiki`, depois de rodar os CT-B contra a tela real.
> Divergência aqui vira linha em "Desvios do Plano" no `03-progresso.md`.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `ListUsers` (`/admin/users`) com miniatura de avatar circular na primeira coluna | igual | ✅ | CT-01 (`tests/Kit/LightboxEmTabelaTest.php`) |
| 2 | `ListUsers` (`/app/{tenant}/users`) com a mesma miniatura | igual | ✅ | CT-05 (`tests/Tenancy/LightboxDaOrganizacaoTest.php`) |
| 3 | `ListTenants` (`/admin/tenants`) com miniatura de logo quadrada, 40 px | igual | ✅ | CT-02 (`tests/Tenancy/LightboxDaOrganizacaoTest.php`) |
| 4 | clique na miniatura abre lightbox, sem sair da página | igual — `.fslightbox-container` no DOM após o clique, e `assertPathIs` prova que não navegou | ✅ | CT-B01 (`tests/Browser/LightboxTest.php`) |
| 5 | registro sem mídia: célula vazia, sem miniatura clicável | igual | ✅ | CT-04, por contagem de `simple-light-box-img-indicator` |
| 6 | plugin registrado nos três painéis, inclusive `/infra` | igual | ✅ | CT-03, dataset com os três |
| 7 | *(não desenhado)* mídia cujo arquivo sumiu do disco | ⚠️ **melhor que o previsto**: `ImageColumn` confere a existência por padrão e devolve célula vazia — o ADR-05 previa imagem quebrada | ⚠️ | `ImageColumn.php:208-220`; ADR-05 corrigido |
