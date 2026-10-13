> Fonte única — referenciada por: feature-test-design (arquivo 05), feature-wiki (ciclo dos CT-B), agente fw-executor-ctb, feature-quality-gate (README e dimensão G do `SKILL.md`). Exceção declarada: a dimensão G do `SKILL.md` do gate mantém em linha os dois fatos que a motivam (`assertSee` passa com texto invisível; o primeiro `assertScreenshotMatches()` cria o baseline com o bug) e aponta para cá para os demais.
>
> Referência da feature-test-design 1.16.0. Lida em: escrita do `05` e de qualquer CT-B (antes do
> primeiro cenário de browser) e na execução dos CT-B pelo `fw-executor-ctb`. Fonte única de: os
> fatos do `pest-plugin-browser` que mudam o que se escreve, o comando da suíte de browser, os
> seletores e o que o plugin não prova sobre tema e cor.

# `pest-plugin-browser` — fatos que mudam o que se escreve

Estes contradizem crenças comuns e cada um já custou tempo em projeto real — vários contradizem o
que a documentação anterior da coletânea afirmava. Esta é a fonte única: as skills e o agente que
dependem deles apontam para cá.

## Os dez fatos

1. **O plugin sobe o próprio servidor** — HTTP in-process, porta aleatória. **Nada** de Herd,
   `artisan serve`, Sail ou Vite dev server; nada de `APP_URL` a configurar.
2. Como é o **mesmo processo**, valem dentro do navegador: `DB_DATABASE=:memory:`,
   `RefreshDatabase`, **`$this->actingAs($user)` antes do `visit()`** e `assertAuthenticated()`.
   **Use `actingAs()`** — login pela tela custa dezenas de segundos por cenário. Reserve um
   único cenário para o formulário de login, que é o caminho real do usuário.
3. **Nunca `wait($segundos)`.** O plugin reexecuta cada assertion até o teto de
   `pest()->browser()->timeout()`. Espere pelo **estado final visível**. Não existem
   `waitForText`, `waitForSelector`, `waitUntil` — não invente.
4. **`assertPathIs` antes das asserções de conteúdo.** Depois de qualquer ação que navegue
   (`press`, `click`), ela vem primeiro — é ela que espera a navegação. Invertido, o `assertSee`
   é avaliado contra o snapshot da página anterior e falha **com a ação tendo funcionado**.
5. **`npm run build` é pré-requisito duro.** Sem `public/build/manifest.json` toda tela responde
   `ViteException` e todo cenário falha por um motivo que não é o dele.
6. **Série é o padrão conservador da coletânea para browser.** O plugin suporta e a
   [documentação oficial recomenda `--parallel`](https://pestphp.com/docs/browser-testing#running-browser-tests).
   Usá-lo depende de isolamento de banco/recursos por worker e capacidade do ambiente; timeout
   medido numa pilha não é limitação universal. Se o projeto roda TIA/paralelismo no backend,
   selecionar suas suítes sem Browser e rodar os CT-B em comando separado:
   `vendor/bin/pest tests/Browser --filter={Feature}`. Não presumir que o filtro sozinho exclui browser.
7. **`assertNoSmoke()` só em tela de autoria própria.** Em tela de plugin de terceiro use
   `assertNoJavaScriptErrors()`, senão a suíte fica vermelha por `console.log` alheio.
8. **`visit([...])` em lote aborta na primeira falha** — as rotas seguintes não são verificadas
   naquele run. Para colher todos os problemas, um cenário por painel.
9. Upload é **`attach()`**, não `upload()`.
10. **`assertSee` não valida tema**: passa com texto branco em fundo branco. Para propriedade de cor
    específica, `assertScript()` com `getComputedStyle()` pode fornecer um oráculo; screenshot observado
    verifica a aparência mais ampla. A primeira baseline de screenshot não prova que a cor está certa
    (o papel do grep estático do quality gate, que não é prova, está no fim deste arquivo).

## Comando da suíte de browser

`vendor/bin/pest tests/Browser --filter={Feature}` — ou `--testsuite=Browser` **só se** o
`phpunit.xml` define a suíte; **em série por padrão**, com paralelismo somente depois de conferir
isolamento e capacidade (fato 6). É o comando do contrato do
`fw-executor-ctb` e o do template do `05` (`references/template-05.md`). A suíte declarada se confere
com `grep -n '<testsuite name="Browser"' phpunit.xml`; sem saída, vale `tests/Browser`.

## Oráculo

**Assertion de console ou de status nunca é o oráculo único de um CT-B.** Todo cenário precisa de
pelo menos uma assertion sobre o que ele afirma — o elemento, o valor ou o registro.

## Seletores

Preferir `data-testid` / `aria-label` / texto visível a classe de CSS. Se o projeto não tem
`data-testid`, registrar como dívida e usar o que existe — em Filament, o `id` gerado do campo
(`#form\.email`, com o `.` escapado) e o texto **traduzido** do rótulo.

## Tema, cor e acessibilidade — o que o plugin não prova

O `pest-plugin-browser` **tem** `->inDarkMode()`, `assertScreenshotMatches()` e `assertNoAccessibilityIssues()`. Ainda assim, falha em casos graves:

[`inDarkMode()` emula o color scheme escuro](https://pestphp.com/docs/browser-testing#using-dark-mode).
Se a UI usa classe ou preferência própria, ativar o tema pelo mecanismo do projeto e conferir que
ficou ativo antes de avaliar cor; a preferência do navegador sozinha pode deixar a tela no tema claro.

| Cenário | Resultado no pest-browser |
|---|---|
| Texto branco em fundo branco no dark mode | ✅ **`assertSee('Salvar')` PASSA** — está no DOM e na árvore de acessibilidade, apenas **invisível** |
| Feature nova, primeiro `assertScreenshotMatches()` | ✅ passa — ele **cria** o baseline, incluindo o bug |
| `bg-white text-gray-900` sem par `dark:`, com apenas assertion de conteúdo | ✅ passa — o oráculo não confere cor |
| Contraste insuficiente | ⚠️ **talvez** — o axe tem regra de `color-contrast` e a doc fala de "level 1 (serious)"; plausível, mas a confirmar no projeto |

`assertScreenshotMatches` detecta **mudança**, não **erro**. Em requisito novo não existe baseline correto para comparar — é o furo clássico de screenshot regression.

## Cor: achar candidato × provar defeito — dois papéis, sem contradição

Consequência para o fato 10: `assertSee` passa com o texto invisível e `assertScreenshotMatches`
cria o baseline com o defeito. Uma propriedade específica pode ser conferida por
[`assertScript()`](https://pestphp.com/docs/browser-testing#assertscript), por exemplo comparando
`getComputedStyle(elemento).color` com o valor esperado, quando ele vem do requisito ou dos tokens
decididos do projeto. Isso não garante contraste, sobreposição nem a aparência completa. Por fora do
plugin, a dimensão G do `feature-quality-gate` usa um grep estático por classe de cor sem par
`dark:` e o chama de "melhor custo-benefício". As duas frases falam de papéis diferentes:

| Papel | Quem faz | Entrega | Não entrega |
|---|---|---|---|
| **achar candidato**, em lote e barato | dimensão G, nível estático (grep nos arquivos do diff) | `arquivo:linha` com classe de cor sem par `dark:` | prova: a classe sem par pode ser intencional, e a cor ilegível pode vir de CSS que o grep não lê |
| **provar uma propriedade específica** | CT-B com `assertScript()` e `getComputedStyle()` | valor computado comparado ao esperado no estado/tema exercitado | avaliação visual completa; cor certa não garante texto legível |
| **provar** o defeito numa tela | CT-B com screenshot observado, ou o nível visual da dimensão G (Playwright MCP — o agente olha) | o texto ilegível, o ícone que some, na tela | cobertura em lote: custa uma observação por tela e tema |

"Melhor custo-benefício" mede a descoberta de candidatos; a propriedade computada e a observação
visual têm oráculos e alcances próprios. `inDarkMode()->assertNoAccessibilityIssues()` só ajuda
com contraste quando a regra do axe dispara (linha "talvez" da tabela acima). Comandos da
dimensão G: `{skills}/feature-quality-gate/SKILL.md`.
