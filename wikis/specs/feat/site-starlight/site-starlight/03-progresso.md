# Progresso — Migração do site para Astro Starlight

> Espelha os passos de `01-plano-acao.md`.
> Checkbox só fecha com evidência inline: `- [x] {item} — {evidência}, {data}`.

## 1. Trazer o spike para a árvore da feature

- [x] `site/` presente com configuração, tema, conversor e conferidores — commit `7e1ffc0`, 2026-09-19
- [x] `site/src/content/docs/` removido — `[CT-28]` afirma a ausência, 2026-09-19

## 2. Apontar o Starlight para `docs/`

- [x] `content.config.ts` usa `glob({ base: '../docs' })` — `[CT-28]`, 2026-09-19
- [x] Build verde lendo de `docs/` — 67 páginas, sentinela plantada e encontrada no `dist/`, 2026-09-19

## 3. Ajustar o `base` e o `site` do Astro

- [x] `base` lê `DOCS_BASE` — `[CT-15]` e `[CT-16]`, 2026-09-19
- [x] Build de produção confere — com `DOCS_BASE` do repositório, os links saem prefixados, 2026-09-19

## 4. Transformar `docs/` no lugar

- [x] Front-matter convertido — 64 páginas, `[CT-29]` com piso de 60, 2026-09-19
- [x] H1 do corpo removido — `[CT-30]`, 2026-09-19
- [x] `title` recebe o H1 e `sidebar.label` o título antigo — ver Desvios, 2026-09-19
- [x] Links reescritos como caminho absoluto — `[CT-22]` verde nos dois idiomas, 2026-09-19
- [x] Landings de idioma com `hero` no front-matter — `[CT-15]`, 2026-09-19
- [x] Landing compartilhada removida — a raiz redireciona, 2026-09-19

## 5. Redirects das URLs antigas

- [x] 54 stubs em `site/public/`, commitados — `[CT-38]` confere a contagem contra a árvore, 2026-09-19
- [x] Destino RELATIVO, independente do `base` — `[CT-36]`, ver Desvios, 2026-09-19
- [x] Redirecionamento da raiz no Astro — conferido pelo `verifica-links.mjs`, 2026-09-19

## 6. Identidade visual

- [x] Rampa de acento nos dois esquemas — `[CT-32]`, 2026-09-19
- [x] Nenhum cinza do tema no seletor global — `[CT-33]`, 2026-09-19
- [x] Capturas em navegador real conferidas — quatro achados de cor, corrigidos em `832aff6`, 2026-09-19

## 7. Workflow de publicação

- [x] `.github/workflows/pages.yml` criado — `[CT-25]` exige exatamente um publicador, 2026-09-19
- [x] Conferidor roda antes do envio — `[CT-40]`, que também proíbe `continue-on-error`, 2026-09-19

## 8. Trocar a origem do GitHub Pages

- [ ] **BLOQUEADO — passo manual do solicitante.** `Settings → Pages → Source: GitHub Actions`

## 9. A rede de testes acompanha

- [x] `CT-15`, `CT-16`, `CT-17`, `CT-20` e `CT-25` reescritos para o mecanismo novo — 2026-09-19
- [x] `CT-26`..`CT-40` implementados — `SiteDeDocumentacaoTest` 55/55, 2026-09-19
- [x] Testes de outras features reconciliados — `SituacaoDaContaDocumentacaoTest` `[CT-33]`, 2026-09-19

## 10. `.gitattributes`

- [x] `/site` e `/site-vitepress` como `export-ignore` — 2026-09-19

## 11. Ligar o VitePress à ADR

- [x] ADR-05 aponta o README do plano B; `[CT-39]` o protege — 2026-09-19

## 12. Remover o Jekyll

- [x] `docs/_config.yml` removido — **no mesmo commit**, ver Desvios, 2026-09-19
- [x] `[CT-27]` afirma a ausência e varre `remote_theme` — 2026-09-19

## Verificação Final

- [x] `/ponytail:ponytail-review` na wiki — 8 achados, `net: -47 lines`, 2026-09-19
- [x] `vendor/bin/pint --dirty --format agent` — passed, 2026-09-19
- [x] `php artisan test tests/Kit/SiteDeDocumentacaoTest.php` — **55/55**, 2026-09-19
- [x] Três suítes de documentação juntas — **66/66, 269 asserções**, 2026-09-19
- [x] `cd site && npm run build && node verifica-links.mjs` — 67 páginas, **2.296 links / 0 quebrados**, **54 redirects / 0 ruins**, exit 0, 2026-09-19
- [x] Revisão adversarial do `04` — 14 lacunas, 10 fechadas, 4 declaradas, 2026-09-19
- [x] `php artisan test --testsuite=Kit,Tenancy` — **2.601/2.601, 10.121 asserções**, 2026-09-20
- [x] Revisão de código do diff (step 7.5) — 2 achados, ambos fechados; 4 hipóteses rejeitadas com motivo, 2026-09-20
- [x] Citações `arquivo:símbolo:linha` reverificadas — **2/2 ok**, e o gate `CitacoesDeCodigoTest` verde, 2026-09-20
- [x] `git commit` — `7e1ffc0`, `444bea3` e o commit do ciclo 2 do gate, 2026-09-20

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `.ai/rules/specs.md` — citar `{path}:{símbolo}:{linha}` | `wikis/specs/**` | **aplicada** | `tests/Pest.php:documentacaoDoKit():933` e `node_modules/@astrojs/starlight/dist/loaders.d.ts:docsLoader:8`, na ADR-02 |
| `.ai/rules/specs.md` — ler o vendor antes de afirmar | `wikis/specs/**` | **aplicada** | a assinatura do `docsLoader` foi lida no pacote instalado; o fallback de tradução foi medido renomeando uma página de verdade |
| `.ai/rules/testes.md` — helper cruzado vive em `tests/Pest.php` | `tests/**` | **aplicada** | nenhum helper novo foi criado; `titulosDoMarkdown()` e `caminhoResolvido()` foram alterados onde já viviam |
| `.ai/rules/testes-browser.md` | `tests/Browser/**` | **n.a.** | o diff não toca `tests/Browser/`, e o gate de CT-B não se aplica (motivo no `01`) |
| `.ai/rules/general.md` | `composer.json` | **n.a.** | o diff não toca o `composer.json` — as dependências do site vivem em `site/package.json` |

## Quality Gate

- **Ciclo 1** · **Veredito**: `REPROVADO → especificação` · **Data**: 2026-09-20
  - 0 Blocker · 3 Major · 1 Minor · 1 Cosmético
  - Nenhum achado de **comportamento**. O que reprovou foi a dimensão L (consistência documental):
    este `03` declarava quatro propagações que **não tinham sido feitas**, o `## Rollback` do `01`
    descrevia um procedimento que não funciona, e o `[CT-41]` existia como teste sem cenário no `04`.
  - **Degradação declarada**: o ciclo foi executado pelo mesmo agente que implementou, porque o
    sub-agente designado morreu num limite de sessão. A skill nomeia isso como cegueira
    correlacionada.
- **Ciclo 2** · **Veredito**: `APROVADO` · **Data**: 2026-09-20
  - Os cinco achados do ciclo 1 foram fechados e reconferidos por comando. Nenhum achado novo.
  - `QA-04` permanece **aberto por desenho**: é destino 4 (infra), depende de ação do solicitante
    na interface do GitHub, e a skill define que destino 4 não reprova a feature.
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa | O código real diz | Correção aplicada |
|---|---|---|
| *"mover o conteúdo para `site/src/content/docs/`"* | **nove** arquivos apontam para `docs/`, incluindo `tests/Pest.php:documentacaoDoKit():933` | ADR-02 inverteu: o conteúdo fica, o Starlight vai até ele |
| *"o `docsLoader()` aceita uma base"* | aceita **só** `generateId` (`node_modules/@astrojs/starlight/dist/loaders.d.ts:docsLoader:8`) | ADR-02, alternativa 2, registrada como impossível — verificada, não suposta |
| *"o `redirects` do Astro resolve as URLs antigas"* | a chave é tratada como rota, e o formato padrão gera `x.html/index.html` | ADR-06; os stubs foram para `public/` |
| *"traduzir os slugs do inglês é barato agora"* | o i18n casa por caminho idêntico; traduzir gera página-fantasma em português sob `/en/` | ADR-04; a decisão do solicitante foi revertida com a medição na mão |

### Varredura da classe irmã (step 5)

Não há classe nova. A varredura foi feita sobre o **diretório**, que é o artefato que esta feature
cria:

- **Irmã escolhida**: `docs/`, que já passou por esta mesma pergunta na wiki ancestral
- **Onde `docs/` aparece**: `.gitattributes`, nove arquivos de teste, e `KitUpdate::CAMINHOS_DO_KIT`
  **por ausência** — a lista entrega os documentos de topo de `wikis/` e nunca `docs/`
- **`site/` entrou em**: `.gitattributes` (passo 10) e na rede de testes (`CT-26`..`CT-40`).
  **Não** em `CAMINHOS_DO_KIT`, pelo mesmo critério de `docs/`
- **Ocorrência inesperada**: nenhuma além das previstas

### Auditoria Ponytail (step 6)

| # | Sugestão | Aplicada? | Onde |
|---|---|---|---|
| 1 | passo 6 "Identidade visual" não faz nada — o próprio texto dizia "é de revisão, não de escrita" | **sim** — absorvido no passo 9 | `01` |
| 2 | passo 11 tem as duas metades em outro lugar | **sim** — absorvido na ADR-05 e no `CT-39` | `01` |
| 3 | passos 3 e 10 são uma linha cada | **recusada**: passo separado é o que permite ao `03` registrar evidência própria | — |
| 4 | `CT-37` tem mutante de dano cosmético | **recusada**: a revisão adversarial mostrou depois que o mesmo cenário detecta o vácuo de globs quebrados | `04` |
| 5 | tabela do Modelo de Execução longa demais | **recusada**: é exigência da skill, e a resposta curta cabe na tabela | `01` |
| 6 | duas linhas mortas na tabela de Conformidade com Rules | **sim** — viraram `n.a.` com o motivo escrito | `03` |

### Revisão de código do diff (step 7.5)

O sub-agente designado morreu num limite de sessão (HTTP 429) antes de produzir qualquer saída. A
revisão foi feita **por mim**, o que é uma degradação declarada do gate: ele existe para ser feito
por quem não implementou. O que compensa parcialmente é que os eixos foram executados como
**medições**, não como leitura — cada achado abaixo tem um comando que o produziu.

| # | Achado | Gravidade | Estado |
|---|---|---|---|
| 1 | **O conversor não era reexecutável.** Rodá-lo duas vezes mudava 55 arquivos, apagava `sidebar.order` de 32 páginas e embaralhava a barra lateral — sem erro, com o build verde | **alta** | corrigido + `[CT-41]` |
| 2 | **`[CT-37]` afirmava ausência sem piso de população** — verde sobre lista vazia | média | corrigido: exige 10 índices antes de afirmar |

**Achado 1, como foi medido**: `node converter.mjs` uma segunda vez sobre o `docs/` já convertido,
seguido de `git diff --stat`. Causa: o front-matter do just-the-docs era plano (`nav_order: 3`) e
o do Starlight aninha sob `sidebar:`; o leitor só enxergava chave de topo. Correção: aceitar chave
indentada e herdar `nav_order ?? order`. **Provado**: três passadas seguidas, diff vazio.

**Achado 2** saiu de uma varredura dos cenários novos procurando os que não têm âncora de
população. Dos 16, `[CT-37]` era o único.

#### Hipóteses rejeitadas, com o motivo

Relatório sem rejeição parece que só procurou onde achou.

- **"O `npm ci` do runner vai falhar por install script bloqueado."** Esta máquina bloqueia os
  scripts de `sharp` e `esbuild`, e o `pages.yml` depende dos dois. Conferido:
  `npm config list -l` mostra `allow-scripts` como **config do usuário local**, não default do
  npm — `; allow-scripts = [""] ; overridden by user`. O runner roda os scripts normalmente.
- **"Os stubs podem não resolver sob o `base` de produção."** Conferido com o resolvedor de URL do
  próprio Node, que é o algoritmo do navegador: os três casos testados resolvem para a rota nova
  sob `https://gsferro.github.io/filament-starter-kit-easy`.
- **"O conferidor pode ficar verde num modo de falha."** Conferido nos dois: `dist/` vazio → exit
  1; stub apontando para página inexistente → exit 1.
- **Armadilha do `toContain` variádico**: varredura dos 16 cenários novos, nenhuma ocorrência. As
  quatro linhas que o grep acusa são encadeamentos de agulha única, pré-existentes.

### Revisão adversarial do `04` (disparada por Impacto 3 na área D)

Registro completo em `04-casos-de-teste.md` → `## Revisão Adversarial`. Resumo: **5 implementações
erradas plausíveis e 14 lacunas; duas das cinco eram defeitos REAIS já presentes no código.**

## Blockers

- [ ] **O passo 8 depende do solicitante.** Trocar a origem do Pages é ação na interface do GitHub.
      **E passou a ser urgente, não opcional**: a transformação do conteúdo já quebra o Jekyll,
      então a partir do merge o site no ar fica errado até a troca acontecer.

## Desvios do Plano

1. **O `_config.yml` sai no MESMO commit, não num passo 12 separado.** O plano argumentava que os
   dois geradores leriam a mesma árvore por um commit, dando rollback barato. **O argumento é
   falso**: a transformação é para o Starlight (H1 fora do corpo, links absolutos sem o `baseurl`),
   então o Jekyll já está quebrado. O rollback real é reverter o merge e voltar o `Source` do
   Pages. *Propagado em 2026-09-20, depois de o `QA-01` acusar que a propagação não existia: `01` → `## Rollback`, `## Riscos` e passo 12, com marca de data; e o docblock de `[CT-27]`.*
2. **O destino dos stubs passou a ser RELATIVO.** O plano e a ADR-06 previam caminho absoluto com
   `DOCS_BASE`. Como os stubs são commitados e quem os gera localmente não passa a variável, os 54
   entraram apontando para a raiz do domínio — 404 em produção. *Propagado em 2026-09-20 (`QA-01`): ADR-06 → `### Decisão`, com marca de data; e o docblock de
   `escreveStub()`.*
3. **A barra lateral é declarada, não descoberta.** O plano supunha `autogenerate`, que não
   funciona com conteúdo fora da raiz do Astro. *Propagado em 2026-09-20 (`QA-01`): ADR-02 → `### Consequências`, com marca de data; e o docblock
   de `[CT-20]`.*
4. **`title` recebe o H1; `sidebar.label` recebe o título antigo.** O plano dizia apenas "H1 do
   corpo removido". O H1 e o `title` do just-the-docs eram textos **diferentes** em várias páginas.
   *Propagado para o `01` passo 4 e para o docblock de `h1Do()`.*
5. **O `verifica-links.mjs` ganhou piso de população e código de saída.** Não estava no plano
   porque ninguém tinha notado que ele não reprovava. *Achado da revisão adversarial.*

## Notas de Implementação

- **O `documentacaoDoKit()` continua válido sem edição** — o conteúdo não saiu de `docs/`. As
  landings seguem `.md`, então nem a ressalva sobre `.mdx` que o plano previa chegou a valer.
- **MDX não funciona em `docs/`**: um `import` de `@astrojs/starlight/components` não resolve o
  `node_modules` de `site/`. As landings usam `hero` no front-matter e HTML simples.
- **O `[CT-21]` venceu uma decisão minha.** A landing tinha imagem local para o Astro otimizar, e
  a ADR-06 ancestral exige imagem absoluta em `docs/`. Voltou para a URL remota.
- **Caí na armadilha do `toContain` variádico** ao escrever o `[CT-17]` — a mesma que este
  repositório já documentou em `[CT-29]` e `[CT-34]`. A mensagem virou segunda agulha e o teste
  afirmava outra coisa.

## Retrospectiva

**Funcionou bem**: medir antes de decidir. As três decisões estruturais desta feature — o conteúdo
ficar em `docs/`, o slug continuar em português e os stubs irem para `public/` — vieram de medição
que contradisse a intuição, e duas delas inverteram uma escolha **já tomada** (uma minha, uma do
solicitante).

**Faltou no plano**: ele tratou "remover o Jekyll" como passo independente sem perguntar se o
conteúdo transformado ainda servia ao gerador antigo. A pergunta era barata, e a resposta muda a
estratégia de rollback inteira.
