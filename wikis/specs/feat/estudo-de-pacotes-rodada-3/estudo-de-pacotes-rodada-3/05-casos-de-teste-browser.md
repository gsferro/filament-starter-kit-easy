# Casos de Teste de Browser — feat/estudo-de-pacotes-rodada-3: Estudo e adoção de pacotes Filament, rodada 3 (tabelas e PHPStan)

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor, in-process.
> Costura: `browser` — linha "G11 Rolagem no navegador" de `## Costuras de Teste` do `04`
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`) — ou, isolado, `php artisan test --testsuite=Browser --filter=CarregamentoContinuo` depois do build e do `view:cache`. **Em série; nunca `--parallel`** (`.ai/rules/testes-browser.md` vence a skill — divergência declarada no `04`).
> Teto do perfil padrão: 1 CT-B (happy path). Derivado do requisito (P-10: "o próximo bloco carrega ao chegar ao fim" no modo `rolagem`); nenhum cenário foi escrito olhando implementação.

## Pré-requisitos
- [ ] `npm run build` executado (sem `public/build/manifest.json` toda tela responde `ViteException`)
- [ ] `php artisan view:cache` e aquecimento pelo kernel no `beforeEach` (`$this->get('/admin/users')` com o usuário autenticado) — o primeiro render do painel compila os componentes do Filament fora do cronômetro do Playwright
- [ ] Assets do pacote publicados (`php artisan filament:assets`), se o pacote registrar CSS/JS próprio
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($master)` antes do `visit()` — nunca login pela tela
- [ ] Painel arranjado no próprio cenário, imediatamente antes do `visit()` (o `beforeEach` não arranja painel — `.ai/rules/testes-browser.md`)

## Seletores
| Elemento | Seletor | Já existe? |
|---|---|---|
| Rodapé do carregamento contínuo | `[data-mode="scroll"]` (atributos `data-loaded`, `data-has-more` no mesmo elemento) | sim — Blade do pacote (`resources/views/footer.blade.php`, atributos `data-mode`, `data-loaded`, `data-has-more`) |
| Sentinela de interseção | o último elemento `[data-has-more]` dentro do rodapé (dois elementos têm o atributo — seletor único por `script()`, por causa do modo estrito do Playwright) | sim — Blade do pacote |
| Linhas da tabela | `.fi-ta-row` | sim — Filament (classe; o kit não tem `data-testid`, dívida conhecida) |

---

## CT-B01: rolar até o fim da tabela carrega o próximo bloco

**Por que browser e não Livewire**: o gatilho é a diretiva `x-intersect` do Alpine sobre o sentinela — JavaScript executado no navegador. O teste de componente prova que o método `infiniteScrollLoadMore` soma um bloco (CT-43), mas não que a rolagem o chama: com o plugin `intersect` do Alpine ausente, ou o asset do pacote não publicado, CT-43 continua verde e a tabela nunca cresce.

```gherkin
# language: pt
  Cenário: [CT-B01] chegar ao fim da tabela no modo rolagem carrega exatamente o próximo bloco
    Dado o carregamento "rolagem", Linhas por página igual a 25 e 75 usuários
    E o master_global com /admin/users aberta e 25 usuários exibidos
    Quando o master_global rola a página até o fim da tabela
    Então a tabela exibe 50 usuários
    E o rodapé do carregamento contínuo indica 50 carregados e que há mais
```

Por que estes números discriminam: com 25 linhas a tabela passa da altura da janela, então o sentinela não está à vista antes da rolagem (o `Dado` afirma 25 exibidos); depois do primeiro bloco, as 25 linhas novas empurram o sentinela para muito além da margem de 300 px, então uma rolagem carrega um bloco e só um. 75 usuários garantem que 50 não é "todos". Um bloco de 7 ficaria inteiro na janela e dispararia a interseção sozinho, sem rolagem.

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjar | `ligarCarregamento('rolagem', 25)`; 75 usuários em lote; `$this->actingAs($master)`; aquecer com `$this->get('/admin/users')` | — |
| 2 | abrir a listagem | `$pagina = visit('/admin/users')` | `->assertPathIs('/admin/users')` primeiro; depois `assertAttribute('[data-mode="scroll"]', 'data-loaded', '25')` |
| 3 | rolar ao fim | `$pagina->script("document.querySelectorAll('[data-has-more]')[1]?.scrollIntoView()")` (ou `window.scrollTo(0, document.body.scrollHeight)` se o contêiner de rolagem for a janela) | — |
| 4 | conferir | `->assertAttribute('[data-mode="scroll"]', 'data-loaded', '50')` (retry automático do plugin; nunca `wait()`) | 50 linhas |
| 5 | conferir a contagem de linhas | `->assertScript("document.querySelectorAll('.fi-ta-row').length === 50")` | — |
| 6 | apoio | `->assertNoJavaScriptErrors()` | — |

**Assertions**: `assertPathIs` primeiro · `data-loaded` = 50 e 50 linhas (o oráculo) · `assertNoJavaScriptErrors()` só como apoio (tela de autoria do kit com rodapé de pacote — se o `ColorPicker`/ResizeObserver não está na tela, a asserção vale). Nenhum `assertSee` como oráculo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M67 (do `04`, R14) | a interseção não dispara: plugin `intersect` do Alpine ausente, ou CSS/JS do pacote não publicado | CT-B01 | `data-loaded` passa de 25 para 50; o mutante fica em 25 |
| — (apoio) | a interseção dispara em cadeia (carrega tudo de uma vez) | CT-B01 | `data-loaded` = 50 e 50 linhas; o mutante chega a 75 |

---

## CT-B02: o rodapé do carregamento contínuo é legível no tema escuro

**Por que browser e não Livewire**: o *Gate de CT-B* do `01` nomeia o tema escuro do rodapé como cenário que só o navegador prova — a cor resolvida pelo `dark` do `<html>` e pelo CSS do pacote só existe renderizada. O corte original (abaixo) dizia que `assertSee()` não prova legibilidade; o oráculo aqui é de **cor computada**, não de texto. *(adicionado em 2026-10-15, QA-06 do quality gate ciclo 1)*

```gherkin
# language: pt
  Cenário: [CT-B02] o rodapé do carregamento contínuo tem texto claro sobre fundo escuro no tema escuro
    Dado o carregamento "rolagem", Linhas por página igual a 10 e 30 usuários
    E o master_global com /admin/users aberta no tema escuro
    Então o <html> tem a classe dark e o rodapé do carregamento está visível
    E a luminância do texto do rodapé é maior que 120
    E a luminância do primeiro fundo opaco atrás do rodapé é menor que 80
```

Por que estes números discriminam: o Filament 5 escreve as cores em `oklch`; um canvas de 1 px converte qualquer cor computada para RGB. Texto `gray-400` (≈ 163 de luminância) sobre `gray-900` (≈ 24) passa; no tema claro o fundo é branco (255) e a asserção cai — o cenário é falsificável trocando `inDarkMode()` por `inLightMode()`.

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjar | `ligarCarregamento('rolagem', 10)`; 30 usuários; `$this->actingAs($master)`; aquecer com `$this->get('/admin/users')` | — |
| 2 | abrir no escuro | `$pagina = visit('/admin/users')->inDarkMode()` | `->assertPathIs('/admin/users')` primeiro; `assertScript("document.documentElement.classList.contains('dark')")` |
| 3 | conferir a cor | `assertScript(...)` com `getComputedStyle(rodape).color` e o primeiro `backgroundColor` opaco subindo pelos ancestrais, convertidos em RGB por canvas | luminância texto > 120 e fundo < 80 |
| 4 | apoio | `->assertNoJavaScriptErrors()` | — |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| — | o CSS do pacote não tem variante `dark:` para o rodapé (texto escuro sobre fundo escuro) | CT-B02 | luminância do texto ≤ 120 |
| — | o rodapé pinta fundo claro próprio por cima do painel escuro | CT-B02 | luminância do fundo ≥ 80 |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Tema escuro do rodapé (legibilidade) | ~~o requisito não fixa cor; `inDarkMode()->assertSee()` não prova legibilidade e o primeiro `assertScreenshotMatches()` cria a baseline com o defeito. RQ-07 (capturas nos dois temas) é CT-51 do `04`~~ **reinstaurado como CT-B02** *(alterado em 2026-10-15, QA-06 do gate: o `01` declarava o cenário como só-navegador; o oráculo passou a ser luminância por `getComputedStyle` + canvas, que prova legibilidade sem baseline de screenshot)* |
| Clique no botão "Carregar mais" (modo `botao`) | `wire:click` padrão do Livewire; CT-42 prova o botão ligado ao método e CT-43 o efeito |
| Rodapé "pede busca ou filtro" no teto | texto renderizado no servidor; CT-44 prova pelo componente |
| Reinício do bloco ao buscar | estado do servidor; CT-47 prova pelo componente |

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Rodapé de carregamento contínuo em toda tabela, com `x-intersect` disparando `$wire.infiniteScrollLoadMore()` no modo rolagem | `/admin/users` no modo `rolagem` com 25 linhas por página e 75 usuários: "Mostrando 25 de 75", `data-mode="scroll"`, `data-loaded="25"`, 25 `.fi-ta-row`; depois de rolar o rodapé para a viewport, "Mostrando 50 de 75", `data-loaded="50"`, `data-has-more="1"`, exatamente 50 linhas e nenhum erro de JavaScript | ✅ | CT-B01 verde na primeira execução, Codex sob o contrato do `fw-executor-ctb` (`{"tests":1,"passed":1,"assertions":11,"duration_ms":73366}`); capturas `tabela-rolagem` e `tabela-rolagem-escuro` (`composer art`, 2026-10-14) |
| 2 | Modo botão: botão *Carregar mais* com `wire:click` | não exercitado por CT-B (cortado acima: CT-42 prova o `wire:click`, CT-43 o efeito); na captura, `/admin/users` no modo `botao` com 10 linhas por página e 31 usuários mostra "Mostrando 10 de 31" + botão, e dois cliques levam a 20 e 30 de 31 — os três quadros do clipe | ✅ | `CapturaDeArteTest::capturarQuadrosDoCarregarMais()` nos dois temas (36/38 na rodada do art; os 2 skips são anteriores à feature); `art/carregamento-continuo.gif` e `-escuro.gif` montados com ffmpeg 9.0.2; `art/tabela-carregar-mais(-escuro).png` conferidas pela sessão |
| 3 | Aba *Tabelas* de `ConfiguracoesDoKit`: Linhas por página, os três interruptores existentes, *Carregamento da tabela*, *Visões salvas* e *Compartilhar visões com todos* (este só com visões ligadas) | a aba renderiza os seis controles nessa ordem, o Select com o rótulo do modo e as três ajudas (`art/admin-configuracoes-tabelas.png`, conferida pela sessão) | ✅ | captura `admin-configuracoes-tabelas` (`composer art`) + CT-12…CT-16 e CT-76 em `ConfiguracoesDoKitTelaTest` (55/55) |
| 4 | Botão *Visões* (grupo de ações) no cabeçalho das 10 listagens, com as visões, o `Padrão`/`Compartilhada` e as quatro ações | `/admin/users` com três visões: o grupo abre por cima da barra de ferramentas da tabela (depois da correção de `z-index`), lista "Por nome" (Padrão, ativa), "Cadastros recentes" e "Administradores" (Compartilhada), e as ações *Salvar como nova visão*, *Atualizar visão atual*, *Gerenciar visões* e *Limpar tabela*; modais de salvar e gerenciar nos dois temas | ✅ | clipes `art/visoes-de-tabela.gif` e `-escuro.gif` (4 quadros cada) + `art/visoes-aberta(-escuro).png`, `art/visoes-gerenciar(-escuro).png`, conferidas pela sessão; backend por `VisoesDeTabelaTest` (130/130) e `VisoesDeTabelaTenancyTest` (34/34) |
| 5 | Rodapé do carregamento contínuo legível no tema escuro (*Gate de CT-B* do `01`) *(adicionado em 2026-10-15, QA-06)* | `/admin/users` no modo `rolagem` com 10 por página e 30 usuários, `inDarkMode()`: `<html>` com `dark`, rodapé visível com `data-mode="scroll"`, luminância do texto > 120 e do primeiro fundo opaco < 80 (canvas sobre `getComputedStyle`), sem erro de JavaScript | ✅ | CT-B02 verde na primeira execução pela sessão (`{"tests":1,"passed":1,"assertions":7,"duration_ms":4108}`); nível 3 do gate (ciclo 1, dimensão G) já havia lido `oklch(0.705…)` sobre `oklch(0.21…)` |
