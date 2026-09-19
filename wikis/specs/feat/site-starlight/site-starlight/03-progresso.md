# Progresso — Migração do site para Astro Starlight

> Espelha os 12 passos de `01-plano-acao.md`.
> Checkbox só fecha com evidência inline: `- [x] {item} — {evidência}, {data}`.

## 1. Trazer o spike para a árvore da feature

- [ ] `site/` presente com configuração, tema, conversor e conferidores
- [ ] `site/src/content/docs/` removido — sob a ADR-02 o conteúdo mora em `docs/`

## 2. Apontar o Starlight para `docs/`

- [ ] `site/src/content.config.ts` usa `glob({ base: '../docs' })` com `docsSchema()`
- [ ] Build verde lendo de `docs/`

## 3. Ajustar o `base` e o `site` do Astro

- [ ] `base` lê `DOCS_BASE`
- [ ] Redirecionamento da raiz sobrevive ao `base`

## 4. Transformar `docs/` no lugar

- [ ] Front-matter convertido nas 66 páginas
- [ ] H1 do corpo removido
- [ ] Links `.md` reescritos como caminho absoluto
- [ ] Landings de idioma em `.mdx`
- [ ] Landing compartilhada removida

## 5. Redirects das URLs antigas

- [ ] 54 stubs em `site/public/`, commitados
- [ ] Redirecionamento da raiz no Astro

## 6. Identidade visual

- [ ] Rampa de acento nos dois esquemas
- [ ] Nenhuma variável de cinza do tema redefinida no seletor global
- [ ] Capturas em navegador real conferidas nos dois temas

## 7. Workflow de publicação

- [ ] `.github/workflows/pages.yml` criado
- [ ] Conferidor de links roda **antes** do envio do artefato

## 8. Trocar a origem do GitHub Pages

- [ ] **Passo manual do solicitante** — `Settings → Pages → Source: GitHub Actions`
- [ ] Feito só depois de o `pages.yml` ter rodado verde uma vez

## 9. A rede de testes acompanha

- [ ] `CT-25` redefinido
- [ ] `CT-26`..`CT-40` implementados
- [ ] Cenários herdados que afirmam sobre o Jekyll reescritos ou aposentados com motivo

## 10. `.gitattributes`

- [ ] `/site export-ignore`

## 11. Ligar o VitePress à ADR

- [ ] `site-vitepress/README.md` referenciado pela ADR-05
- [ ] `CT-39` protege o plano B

## 12. Remover o Jekyll — último, commit separado

- [ ] `docs/_config.yml` removido
- [ ] Feito **depois** do passo 8

## Testes

- [ ] `tests/Kit/SiteDeDocumentacaoTest.php` — CT-25..CT-40
- [ ] `site/verifica-links.mjs` — links e redirects, no workflow

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`
- [ ] `composer test` — suíte completa (regressão obrigatória)
- [ ] `cd site && npm run build && node verifica-links.mjs`
- [ ] Capturas nos dois temas conferidas
- [ ] **`/code-review` no diff (step 7.5)**
- [ ] Citações `arquivo:símbolo:linha` reverificadas — {n}/{n}
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit`

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `.ai/rules/specs.md` | `wikis/specs/**` | | |
| `.ai/rules/testes.md` | `tests/**` | | |
| `.ai/rules/testes-browser.md` | `tests/Browser/**` | | |
| `.ai/rules/general.md` | `composer.json` | | |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: — · **Veredito**: — · **Data**: —
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| *"mover o conteúdo para `site/src/content/docs/`"* (premissa inicial, do spike) | **nove** arquivos apontam para `docs/`, incluindo o helper compartilhado `tests/Pest.php:documentacaoDoKit():933` | ADR-02 inverteu a decisão: o conteúdo fica, o Starlight vai até ele |
| *"`docsLoader()` deve aceitar uma base"* | aceita **só** `generateId`; o caminho é fixo (`node_modules/@astrojs/starlight/dist/loaders.d.ts:docsLoader:8`) | ADR-02, alternativa 2, registrada como impossível — verificada, não suposta |
| *"o `redirects` do Astro resolve as URLs antigas"* | a chave é tratada como rota e o `build.format` padrão gera `x.html/index.html` | ADR-06; os stubs foram para `public/` |
| *"traduzir os slugs do inglês é barato agora"* | o i18n do Starlight casa tradução por **caminho idêntico**; traduzir gera página-fantasma em português sob `/en/` | ADR-04; a decisão do solicitante foi revertida com a medição na mão |

### Varredura da classe irmã (step 5)

**Não há classe nova** — a feature não cria classe PHP. A varredura equivalente foi feita sobre o
**diretório**, que é o artefato que esta feature cria:

- **Irmã escolhida**: `docs/`, o diretório do site atual, que já passou por esta mesma pergunta na
  wiki ancestral.
- **Onde `docs/` é citado**: `.gitattributes` (`export-ignore`), nove arquivos de teste,
  `KitUpdate::CAMINHOS_DO_KIT` (**por ausência** — a lista entrega os documentos de topo de
  `wikis/` e nunca `docs/`).
- **O diretório novo (`site/`) entra em quais?** `.gitattributes` **sim** (passo 10); testes
  **sim** (`CT-26`..`CT-40`); `CAMINHOS_DO_KIT` **não**, pelo mesmo critério de `docs/` — é
  material do kit, não do projeto que nasce dele.
- **Ocorrência inesperada encontrada**: nenhuma além das previstas.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| | | | |

### Revisão adversarial do `04` (disparada por Impacto 3 na área D)

| # | Lacuna apontada | Virou | Onde |
|---|---|---|---|
| | | | |

## Blockers

- [ ] **Passo 8 depende do solicitante**: trocar a origem do Pages para GitHub Actions é ação na
      interface do GitHub, fora do alcance do agente. Até isso acontecer, o site publicado continua
      sendo o do Jekyll.

## Desvios do Plano

<!-- Onde a implementação divergiu do PRD e por quê. Cada item exige a edição correspondente na fonte. -->

## Notas de Implementação

- **O `documentacaoDoKit()` perde as duas landings.** Ele filtra `getExtension() === 'md'`, e as
  landings viram `.mdx`. Perda aceita e declarada no `01`: elas não têm conteúdo normativo.

## Retrospectiva

<!-- Preenchida no fim. -->
