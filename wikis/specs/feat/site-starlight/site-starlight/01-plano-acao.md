# Plano de Ação — Migrar o site de documentação para Astro Starlight

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/site-de-documentacao/site-de-documentacao/`
- **Motivo**: a ADR-01 da ancestral escolheu o Jekyll embutido do Pages e **nomeou a própria
  saída**: *"Fica nomeada como saída se o bilíngue manual se mostrar caro demais na prática."*
  Esta wiki aciona essa cláusula. Não é reverter a decisão — é executar o plano B que ela
  registrou, com um gerador diferente do nomeado lá (Starlight em vez de Jekyll+Actions), pelo
  motivo explicado na ADR-01 desta wiki.
- **Toca infra compartilhada?**: **sim** → `tests/Pest.php` (o helper `documentacaoDoKit()` é
  consumido por quatro arquivos de teste de outras features), `.gitattributes`, `.github/workflows/`
  e a configuração de publicação do GitHub Pages.

> Tipo `evolução` **e** infra compartilhada: a regressão é obrigatória por dois caminhos
> independentes. Ela roda contra os `CT-01`..`CT-25` da wiki ancestral e contra a suíte inteira.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Aparência moderna / UX melhor | 2, 3, 6 | oráculo é o julgamento já emitido sobre o spike; o que se testa são as propriedades falsificáveis (passo 9) |
| RQ-02 | VitePress testado lado a lado | — | **já atendido no spike**, commits `21c29eb` e `133ac38`. Nada a fazer aqui |
| RQ-03 | Cores escuras corrigidas, conferidas em navegador | 6, 9 | correção já feita no spike (`832aff6`); o passo 9 a **trava por teste**, que o spike não fez |
| RQ-04 | Starlight é o gerador publicado | 1, 2, 7, 8 | |
| RQ-05 | VitePress documentado como plano B | 11 | `site-vitepress/README.md` existe desde `133ac38`; o passo 11 o **liga à ADR** e o protege por teste |
| RQ-06 | Os dois idiomas tratados corretamente | 4, 9 | medido no spike; o passo 9 trava as quatro propriedades |
| RQ-07 | Slugs continuam em português | 4 | é uma **não-ação** com motivo medido; o passo 9 registra o porquê num teste |
| RQ-08 | URLs antigas redirecionam | 5, 9 | stubs já existem (`748a3ed`); falta o guarda virar teste do Pest |
| RQ-09 | Migração implementada | 1–12 | |

**Cláusulas herdadas que continuam valendo** (regressão, não cobertura nova): `RQ-01`..`RQ-06` da
wiki ancestral. A que mais restringe é a ancestral `RQ-03` — *"o conteúdo migrado é o dos
READMEs"*: esta migração **não reescreve página nenhuma**, só transforma o front-matter e os links.

## Objetivo

Trocar o gerador do site de documentação: sai o Jekyll embutido do GitHub Pages com o tema
`just-the-docs`, entra o **Astro Starlight** publicado por **GitHub Actions**. O conteúdo é o
mesmo — 66 páginas em duas árvores de idioma —, transformado no lugar: front-matter do
just-the-docs vira front-matter do Starlight, o H1 do corpo sai (o Starlight renderiza o título) e
os links relativos viram absolutos.

A troca entrega o que o Jekyll embutido não podia dar, por limitação declarada na ADR-01 ancestral:
**i18n de verdade** (com seletor que leva à página equivalente) e **busca separada por idioma**.

## Contexto

O site nasceu na v0.34.0 com uma restrição conhecida e aceita: o build nativo do Pages roda em
`--safe`, com lista fechada de gems, e **nenhum plugin de i18n é permitido**. A consequência,
escrita na ADR-01 ancestral, foi que as duas árvores de navegação são mantidas à mão e *"a busca do
tema tem índice único e mistura os idiomas"*.

O pedido que abre esta wiki é de aparência. A investigação mostrou que o teto de aparência é o
mesmo teto do build: o tema nunca foi estilizado (não existiam `docs/_sass` nem `docs/assets` —
era o `just-the-docs` de fábrica), mas subir dele exigiria plugins que o `--safe` recusa.

Um spike construiu as 66 páginas no Starlight e outro no VitePress, lado a lado, e o solicitante
escolheu o Starlight vendo os dois no navegador.

## Análise dos Arquivos Existentes

### `docs/**` — 66 páginas + 2 landings

Continua sendo a **raiz do conteúdo**, e isso é o eixo do plano — ver ADR-02. O que muda dentro
delas é o front-matter, o H1 e a forma dos links.

### `tests/Pest.php:documentacaoDoKit():933`

Concatena o README do idioma com `docs/{idioma}/**/*.md`. **Não muda**, porque o conteúdo não sai
do lugar. É consumido por `AnexosPrivadosDocumentacaoTest`, `ConfiguracoesDoKitDocumentacaoTest`,
`SituacaoDaContaDocumentacaoTest` e `RedeDeDocumentacaoTest`.

> Ele filtra `getExtension() === 'md'`, então as duas landings, que viram `.mdx`, saem da
> concatenação. É perda aceitável e declarada: as landings não têm conteúdo normativo — a antiga
> `docs/pt/index.md` era um título e dois parágrafos de apresentação.

### `tests/Kit/SiteDeDocumentacaoTest.php` — 930 linhas, 25 CTs

O guarda do site, e o arquivo que mais muda. Seis cenários afirmam sobre o **mecanismo de
publicação** e precisam ser reescritos ou removidos; os demais afirmam sobre **conteúdo** e
sobrevivem. Inventário no passo 9.

### `.github/workflows/` — `ci.yml`, `release.yml`, `seguranca.yml`

Nenhum publica o site hoje — é justamente o que `[CT-25]` afirma. Entra `pages.yml`.

### `.gitattributes:/docs export-ignore`

Continua valendo e **não muda**. Ganha uma linha irmã para `site/`.

## Autorização

Sem autorização de aplicação: o site é estático e público, servido fora do Laravel. As permissões
envolvidas são as do **workflow** — `pages: write` e `id-token: write`, escopadas ao job de deploy.

## Rotas

Nenhuma rota de aplicação muda. As rotas do **site** mudam de forma, e é isso que o passo 5 cobre:

| Antes (Jekyll) | Depois (Starlight) | Tratamento |
|---|---|---|
| `/pt/comecar/instalacao-avancada.html` | `/pt/comecar/instalacao-avancada/` | stub de redirect em `site/public/` |
| `/pt/comecar/` | `/pt/comecar/` | **não muda** — sem redirect, de propósito |
| `/` | `/pt/` | `redirects` do Astro |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Site de documentação | **Astro estático, fora do Laravel** | `https://gsferro.github.io/filament-starter-kit-easy/` | ler, navegar, buscar, trocar idioma e tema | Sim (busca, seletor de idioma e tema) |

**Gate de CT-B: não se aplica, e o motivo é estrutural.** O `pest-plugin-browser` sobe **a
aplicação Laravel** em processo — ele não tem como servir um site estático gerado por outro
toolchain. Um CT-B aqui não seria caro, seria **impossível**. A verificação em navegador real
existe e é feita por Playwright direto (`site/capturas.mjs`), com o agente lendo as imagens; ela é
**observação**, não cobertura, exatamente como a skill define para o MCP. Registrado no `04` como
`## Sem CT-B`.

## Superfície Livewire

**Não se aplica, e não é omissão.** A feature não cria página, widget nem componente Livewire —
não toca `app/`. Não há `public function` chamável por `$wire.`, não há propriedade pública, não há
array de estado do framework consumido. A varredura foi rodada mesmo assim para que a ausência
fique medida, e não deduzida — resultado no `02`.

## Variáveis de Ambiente

Nenhuma chave do Laravel. Uma variável **de build**, lida só pelo toolchain do site:

| Key | Default | Descrição |
|-----|---------|-----------|
| `DOCS_BASE` | vazio (raiz) | O caminho base do site publicado. O workflow passa `/filament-starter-kit-easy`; a prévia local não passa nada e serve na raiz |

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

**Um build estático, sem trabalho adiado em runtime.** O site publicado é HTML pronto; não há
request de aplicação, query, cache nem polling.

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | 1 request estático por página, servido pelo CDN do Pages |
| O que é adiado, e por qual gatilho? | nada em runtime. O índice do Pagefind é carregado sob demanda, ao abrir a busca |
| O que é memoizado por request? | não se aplica — não há request de aplicação |
| O que é cacheado entre requests? | o CDN do Pages, com as políticas dele. Não gerenciamos |
| Custo do caminho principal | o custo é de **build**: ~5s para 67 páginas, ~3,4s do Pagefind |

## Impacto em Features Existentes

- **`documentacaoDoKit()` e os quatro testes que o consomem**: risco **baixo e medido** — o
  conteúdo não sai de `docs/`. O que muda é o front-matter e o H1, e os quatro afirmam sobre
  **tokens no corpo** (`'Discord'`, `'socialiteproviders'`, `'versão destino'`), não sobre títulos.
- **`KitUpdateTest.php:466-467`, `LoginSocialProvedoresTest.php:1635-1638`,
  `MysqlNoDockerTest.php:276-277`, `PageHeaderTest.php:1814-1815`**: citam caminhos
  `docs/{idioma}/...` diretamente. **Continuam válidos** — os caminhos não mudam.
- **`PageHeaderTest.php:1828`** usa `secoesDoMarkdown()` sobre uma página do site. Se o helper
  contar o H1 do corpo, a remoção do H1 o afeta. **Risco a verificar no passo 9**, não a supor.
- **`SiteDeDocumentacaoTest`**: impacto alto e intencional.
- **`CitacoesDeCodigoTest`**: varre `docs/` procurando citações. Continua funcionando; o
  front-matter novo não introduz citação.

## Rollback

*(alterado em 2026-09-20: a versão original descrevia um rollback que não funciona — ver QA-02 do
`06-relatorio-qa.md`.)*

Sem migration e sem dado, mas **o rollback não é barato como este plano supôs**.

A suposição original era que os dois geradores leriam a mesma árvore por um commit, bastando
reverter a remoção do `_config.yml`. **Falso, e a implementação provou**: a transformação do
conteúdo é *para o Starlight* — o H1 sai do corpo (o Jekyll não o repõe, porque para ele `title` é
rótulo de navegação) e os links viram absolutos sem o `baseurl` (que sob
`/filament-starter-kit-easy` dão 404). Com o conteúdo transformado, o Jekyll **já está quebrado**,
com ou sem o `_config.yml`.

O rollback real, em dois movimentos:

1. `git revert` do **merge inteiro** — o conteúdo precisa voltar ao formato do just-the-docs
2. Em `Settings → Pages`, voltar `Source` para `Deploy from a branch → main → /docs`

> Por isso o passo 12 deixou de ser um passo separado: manter o `_config.yml` por um commit não
> guardava rollback nenhum, só dava a impressão de guardar.

## Dependências

**NPM, e nenhuma na raiz do repositório** — ADR-02 da wiki ancestral continua valendo, e o
`[CT-12]` a protege:

| Package | Versão | Onde |
|---|---|---|
| `astro` | ^7.3.3 | `site/package.json` |
| `@astrojs/starlight` | ^0.42.2 | `site/package.json` |
| `sharp` | ^0.34.2 | `site/package.json` |

## Riscos

- **O `glob({ base: '../docs' })` alcança fora da raiz do projeto Astro.** É suportado e foi
  **medido** (sentinela plantada em `docs/` apareceu no build), mas é uso incomum. *Mitigação*: o
  `[CT-30]` do `04` planta a sentinela de novo, em teste, para o dia em que um upgrade do Astro
  mudar isso.
- ~~**O `docs/` fica legível por dois geradores até o passo 12.**~~ *(alterado em 2026-09-20:
  risco retirado — ele nunca existiu. O conteúdo transformado não serve ao Jekyll, então não há
  janela de dois geradores. Ver `## Rollback`.)*
- **O deploy por Actions é a primeira publicação do site fora do build nativo.** *Mitigação*: o
  passo 8 troca a origem do Pages só depois de o workflow ter rodado verde uma vez.
- **`[CT-25]` ancestral proíbe exatamente o workflow que esta feature cria.** *Mitigação*: não é
  contorno, é reescrita com a ADR correspondente — passo 9 e ADR-03.

## Channel de Log da Feature

**Sem channel, e a ausência é decisão, não esquecimento.** A feature não executa código de
aplicação: não há request, job, comando nem listener que possa logar. O que o padrão de log desta
skill cobre — `[Classe@Método]` com context estruturado — pressupõe uma classe PHP em execução, e
esta entrega não tem nenhuma.

O equivalente funcional aqui é a **saída do build**, que o workflow guarda: contagem de páginas,
índice do Pagefind e o resultado do conferidor de links.

## Estrutura de Implementação

### 1. Trazer o spike para a árvore da feature

O spike já está nesta branch (`826816e`, `832aff6`, `21c29eb`, `133ac38`, `748a3ed`). Este passo
é de **reorganização**, não de criação.

- **Path**: `site/`
- Manter: `package.json`, `astro.config.mjs`, `src/content.config.ts`, `src/styles/kit.css`,
  `src/assets/`, `public/`, `converter.mjs`, `verifica-links.mjs`, `capturas.mjs`, `.gitignore`
- **Remover** `site/src/content/docs/` — sob a ADR-02 o conteúdo mora em `docs/`, e manter a cópia
  seria duas fontes da verdade

### 2. Apontar o Starlight para `docs/`

- **Path**: `site/src/content.config.ts`
- Trocar `docsLoader()` por `glob({ base: '../docs', pattern: '**/*.{md,mdx}' })`, mantendo
  `docsSchema()`
- **Verificado**: `docsLoader()` aceita só `generateId` e fixa `src/content/docs/`
  (`node_modules/@astrojs/starlight/dist/loaders.d.ts:docsLoader:8`). O `glob()` com `base` é o
  caminho suportado para conteúdo fora dali

### 3. Ajustar o `base` e o `site` do Astro

- **Path**: `site/astro.config.mjs`
- `base: process.env.DOCS_BASE || undefined` — a prévia local roda na raiz, o workflow passa
  `/filament-starter-kit-easy`
- Conferir que o `redirects: { '/': '/pt/' }` sobrevive ao `base`

### 4. Transformar `docs/` no lugar

- **Path**: `site/converter.mjs` (origem **e** destino passam a ser `../docs`)
- Front-matter: **`title` recebe o H1 do corpo** e `sidebar.label` recebe o `title` antigo quando
  os dois diferem *(alterado em 2026-09-20: o plano dizia "`title` preservado", e o H1 e o `title`
  do just-the-docs eram textos diferentes em várias páginas — o `title` era o rótulo curto da
  navegação. Quem pegou foi o `[CT-01]`, contra o baseline congelado de 115 títulos.)*;
  `description` derivada do primeiro parágrafo de prosa; `nav_order` → `sidebar.order`;
  `parent`/`grand_parent`/`has_children` removidos
- Índice de seção ganha `sidebar.label: "Visão geral"` / `"Overview"` e `order: 0`
- H1 do corpo removido
- Links `.md` reescritos como caminho absoluto
- `docs/pt/index.md` e `docs/en/index.md` viram `.mdx` com hero e cartões
- `docs/index.md` (a landing compartilhada) é removida — `/` passa a redirecionar

**RQ-07 é uma não-ação aqui**: nenhum slug é renomeado. O motivo está na ADR-04.

### 5. Redirects das URLs antigas

- **Path**: `site/public/**` (54 stubs) + `site/astro.config.mjs` (a raiz)
- Stub com `meta refresh`, `link rel=canonical` e `robots noindex`
- Gerados pelo `converter.mjs`, **commitados** — depois que o Jekyll sair, não há de onde
  regenerá-los

### 6. Identidade visual

- **Path**: `site/src/styles/kit.css`
- Já entregue no spike e conferida em navegador; este passo é de **revisão**, não de escrita

### 7. Workflow de publicação

- **Path**: `.github/workflows/pages.yml`
- `on: push: branches: [main], paths: ['docs/**', 'site/**', '.github/workflows/pages.yml']`
- `permissions: { contents: read, pages: write, id-token: write }`
- `concurrency: { group: 'pages', cancel-in-progress: false }`
- Job de build: `actions/checkout` → `actions/setup-node` (com cache npm em `site/package-lock.json`)
  → `npm ci` → `DOCS_BASE=/filament-starter-kit-easy npm run build` → `node verifica-links.mjs`
  → `actions/upload-pages-artifact` com `path: site/dist`
- Job de deploy: `actions/deploy-pages`

> O `verifica-links.mjs` roda **dentro do job de build**, antes do upload: link quebrado não deve
> chegar ao ar só porque o build compilou.

### 8. Trocar a origem do GitHub Pages

- `Settings → Pages → Source: GitHub Actions`
- **Só depois** de o `pages.yml` ter rodado verde uma vez
- Passo manual do solicitante — registrar no `03` como tal

### 9. A rede de testes acompanha, no mesmo commit

ADR-05 da wiki ancestral. Ver `04-casos-de-teste.md` para os cenários.

- **Path**: `tests/Kit/SiteDeDocumentacaoTest.php` (reescrita parcial) e os cenários novos
- Os seis cenários que afirmam sobre o Jekyll são reescritos para afirmar sobre o Starlight
- Entram os cenários novos: sentinela do loader, redirects, i18n, ausência de H1 duplicado

### 10. `.gitattributes`

- **Path**: `.gitattributes`
- `/site export-ignore`, com o comentário explicando o mesmo critério de `/docs`

### 11. Ligar o VitePress à ADR (RQ-05)

- **Path**: `site-vitepress/README.md` e `02-decisoes-arquiteturais.md`
- O README já existe; este passo o referencia da ADR-05 e o protege por teste

### 12. Remover o Jekyll — no MESMO commit *(alterado em 2026-09-20: ver `## Rollback`)*

- **Path**: `docs/_config.yml`
- **Sai junto com a transformação do conteúdo**, não depois. Ver `## Rollback` para o porquê

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A maior parte desta entrega já existe e foi medida no spike —
> a tentação aqui é reescrever o que já funciona. Reutilizar o spike é a primeira escada.
>
> **Caveman ativo em modo `ultra`** na conversa. Os arquivos da wiki são boundary.

## Testes

> Ver `04-casos-de-teste.md`. **Sem CT-B** — motivo em `## Superfície de UI`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`
- [ ] `php artisan test --compact tests/Kit/RedeDeDocumentacaoTest.php tests/Kit/CitacoesDeCodigoTest.php`
- [ ] `composer test` — suíte completa (regressão obrigatória: evolução + infra compartilhada)
- [ ] `cd site && npm run build && node verifica-links.mjs` — 0 links quebrados, 0 redirects ruins
- [ ] Capturas em navegador real nos dois temas, conferidas
- [ ] **`/code-review` no diff (step 7.5)**
- [ ] Citações `arquivo:símbolo:linha` reverificadas

## Commits

- `:sparkles: feat(site): publica o site pelo Astro Starlight`
- `:white_check_mark: test(site): a rede de testes acompanha o gerador novo`
- `:fire: chore(site): remove o Jekyll depois do site novo no ar`
- `:memo: docs(site): wiki da feature site-starlight`
