# Decisões Arquiteturais — Migração do site para Astro Starlight

## Superfície Livewire

**Não se aplica — e a ausência é medida, não deduzida.**

A skill exige a tabela sempre que a feature cria página, widget ou componente. Esta não cria: o
diff da feature contra a `main` toca **134 arquivos em `site/` e 79 em `site-vitepress/`, e zero em
`app/`, `resources/`, `routes/` ou `database/`**.

```
git diff --name-only main...HEAD | sed 's|/.*||' | sort | uniq -c
    134 site
     79 site-vitepress
```

Não há `public function` chamável por `$wire.`, não há propriedade pública sem `#[Locked]`, não há
array de estado do framework consumido. O site publicado é HTML estático servido pelo CDN do
GitHub Pages, **fora do processo do Laravel** — o navegador nunca fala com a aplicação.

> A skill registra que esta tabela já foi pulada por leitura literal da condição, com dois 500 como
> resultado. Por isso a varredura foi rodada e o resultado colado, em vez de a seção dizer
> "não se aplica" e seguir.

---

## ADR-01: Astro Starlight, publicado por GitHub Actions

**Status**: Aceita
**Data**: 2026-09-19
**Relaciona**: aciona a saída declarada na ADR-01 da wiki `site-de-documentacao`

### Contexto

O pedido que abre a feature é de aparência: *"eu acho o site com o Jekyll bem simples e feio"*. A
investigação mostrou duas coisas que mudam o enquadramento.

A primeira: **o tema nunca foi estilizado.** Não existiam `docs/_sass` nem `docs/assets` — era o
`just-the-docs` de fábrica. Havia folga real sem trocar nada.

A segunda: **o teto não é o tema, é o build.** O Pages nativo roda em `--safe`, com lista fechada
de gems, e a ADR-01 ancestral já havia registrado a consequência — *"nenhum plugin de i18n é
permitido"*, as árvores de navegação mantidas à mão e *"a busca do tema tem índice único e mistura
os idiomas"*.

E aquela mesma ADR **nomeou a saída**: *"Fica nomeada como saída se o bilíngue manual se mostrar
caro demais na prática."* Esta decisão aciona essa cláusula.

### Decisão

**Astro Starlight**, publicado por **GitHub Actions** (`Settings → Pages → Source: GitHub Actions`).

### Alternativas Consideradas

1. **Repaginar o `just-the-docs`** (color scheme próprio, tipografia, landing). Custo de horas,
   zero mudança de arquitetura, nenhum guarda tocado — e resolve **só** o pedido literal. Descartada
   porque não toca as duas limitações declaradas: i18n manual e busca de índice único.
2. **Jekyll + Actions com Polyglot** — a saída que a ADR ancestral nomeou textualmente. Devolveria o
   i18n, mas mantém Ruby, e o ecossistema de tema de documentação do Jekyll é o mesmo que já
   produziu o resultado que motivou o pedido.
3. **VitePress** — construído lado a lado, 68 páginas, 2.573 links conferidos, 0 quebrados. O
   solicitante viu os dois no navegador e escolheu o Starlight: *"gostei mais do Starlight"*.
   **Não foi descartado** — ver ADR-05.

### Consequências

- **Positivas**: i18n nativo com seletor que leva à página equivalente; busca **separada por
  idioma** (medido: `en` 33 páginas, `pt-br` 33, índices e hashes distintos); tema escuro/claro;
  navegação em celular; `description` em todas as 66 páginas, que o Jekyll não tinha em nenhuma.
- **Negativas**: entra um toolchain Node e um workflow de Actions — exatamente as duas coisas que a
  ADR-01 ancestral eliminava. O ciclo de atualização deixa de ser *editar markdown → commit* e
  passa a ter um build entre os dois.
- **Riscos**: quatro regras de CSS descem a nome de classe do tema (`sl-link-button`, `card`,
  `hero`, `table`), e nome de classe não é contrato. Mitigado pela ADR-05.

### Referências

- `site/astro.config.mjs` — a configuração resultante
- ADR-01 de `wikis/specs/feat/site-de-documentacao/site-de-documentacao/02-decisoes-arquiteturais.md`

---

## ADR-02: O conteúdo continua em `docs/`; o Starlight é que vai até ele

**Status**: Aceita
**Data**: 2026-09-19

### Contexto

O layout convencional do Starlight é `src/content/docs/**`. Seguir a convenção significaria mover
as 66 páginas de `docs/` para `site/src/content/docs/`.

Antes de aceitar a mudança, o acoplamento foi **medido**. Nove arquivos apontam para `docs/`:

| Arquivo | O que aponta |
|---|---|
| `tests/Pest.php:documentacaoDoKit():933` | `base_path("docs/{$idioma}")` — consumido por **quatro** testes de outras features |
| `tests/Kit/KitUpdateTest.php:466-467` | caminhos literais das duas páginas de atualização |
| `tests/Kit/LoginSocialProvedoresTest.php:1635-1638` | quatro caminhos literais |
| `tests/Kit/MysqlNoDockerTest.php:276-277` | dois caminhos literais |
| `tests/Kit/PageHeaderTest.php:1814-1815` | dois caminhos literais |
| `tests/Kit/CitacoesDeCodigoTest.php`, `RedeDeDocumentacaoTest.php`, `SiteDeDocumentacaoTest.php`, `SituacaoDaContaDocumentacaoTest.php` | varredura do diretório |

Mais o `.gitattributes` (`/docs export-ignore`) e a decisão inteira da ADR-03 ancestral.

Mover o conteúdo custaria editar os nove, e cada caminho literal é uma chance de errar em silêncio.

### Decisão

**O conteúdo fica em `docs/`.** O Starlight vai até ele, por um loader `glob` com `base` apontando
para fora da raiz do projeto Astro:

```js
loader: glob({ base: '../docs', pattern: '**/*.{md,mdx}' })
```

### Alternativas Consideradas

1. **Mover para `site/src/content/docs/`** — convencional, e é o que um contribuidor espera
   encontrar. Descartada pelo custo medido acima.
2. **`docsLoader()` com base customizada** — **impossível, e isto foi verificado, não suposto**:
   a assinatura aceita só `generateId`
   (`node_modules/@astrojs/starlight/dist/loaders.d.ts:docsLoader:8`), e o caminho
   `src/content/docs/` é fixo.
3. **Symlink de `site/src/content/docs` para `../../docs`** — funciona em Unix, e o repositório é
   desenvolvido em Windows. Descartada.

### Consequências

- **Positivas**: os nove arquivos continuam válidos sem edição; `.gitattributes` intacto;
  `KitUpdate::CAMINHOS_DO_KIT` intacto; o diff da migração encolhe para `site/`, a rede de testes
  do site e o workflow.
- **Negativas**: layout não-convencional. Quem conhece Starlight procura `src/content/docs/` e não
  acha — mitigado por comentário no `content.config.ts`.
- **Riscos**: `glob` com `base` fora da raiz do Astro é suportado mas incomum; um upgrade pode
  apertar isso. **Mitigado por teste**: o `[CT-30]` do `04` planta uma sentinela em `docs/` e
  exige que ela apareça no build.

### Falsificação executada

A premissa foi testada antes de virar decisão, porque a contagem de páginas **não** a distingue —
as duas árvores tinham ~67 páginas, e o build daria 67 lendo de qualquer uma:

1. `echo "SENTINELA-…" >> docs/pt/comecar/dominio-local.md`
2. `npm run build`
3. `grep -rl "SENTINELA-…" dist/` → `dist/pt/comecar/dominio-local/index.html`

A sentinela apareceu. O loader lê de `docs/`.

---

## ADR-03: A publicação passa por Actions, e o `[CT-25]` ancestral é reescrito

**Status**: Aceita
**Data**: 2026-09-19
**Substitui**: a afirmação de `[CT-25]` da wiki ancestral

### Contexto

O `[CT-25]` ancestral afirma: *"nenhum fluxo de Actions publica o site por fora do build nativo"*,
com um detector que casa `deploy-pages|actions-gh-pages|github-pages-deploy|gh-pages`. O workflow
desta feature usa `actions/deploy-pages` — **o teste reprova a feature por desenho**.

Um teste que proíbe o que a feature entrega é uma de duas coisas: a feature está errada, ou o teste
codificou uma decisão que mudou. Aqui é a segunda.

### Decisão

O `[CT-25]` é **reescrito, não removido**, e passa a afirmar a decisão nova: **existe exatamente um
workflow que publica o site, e é o `pages.yml`**.

A inversão é deliberada. O cenário antigo protegia contra *"alguém adicionou um publicador"*; o
novo protege contra *"alguém adicionou um segundo publicador"* e contra *"o publicador sumiu"* —
os dois modos de falha que a arquitetura nova tem.

### Alternativas Consideradas

1. **Remover o `[CT-25]`** — descartada. A rede de testes do site perde a afirmação sobre o
   mecanismo de publicação, que é justamente o que mudou. Apagar teste que incomoda é o
   anti-padrão que a `.ai/rules/specs.md` descreve para citações e vale igual aqui.
2. **Manter e marcar `skip`** — descartada. Teste pulado é dívida silenciosa.

### Consequências

- **Positivas**: a rede continua afirmando sobre publicação, e o controle positivo do cenário
  (que o detector casa uma string plantada) é preservado.
- **Negativas**: a wiki ancestral passa a ter um CT cuja afirmação foi invertida. Registrado aqui
  e apontado de lá.

---

## ADR-04: Os slugs do inglês continuam em português

**Status**: Aceita
**Data**: 2026-09-19

### Contexto

As 33 páginas em inglês têm slug em português: `/en/autenticacao/protecao-anti-robo/` serve
*"Anti-robot protection"*. Vem do Jekyll e não foi introduzido aqui.

A migração já muda **toda** rota de folha (`.html` → `/`), então o argumento *"não mexer para não
quebrar links"* não se aplica — este seria o momento mais barato de traduzir.

O solicitante foi consultado com esse enquadramento e escolheu **traduzir**.

### Decisão

**Não traduzir.** A decisão inverteu a escolha inicial do solicitante, depois de uma medição que
nem o agente nem ele tinham quando a pergunta foi feita.

### O que foi medido

Renomeando uma página real (`en/autenticacao/protecao-anti-robo.md` →
`en/authentication/anti-robot-protection.md`) e construindo, o Starlight gerou **duas** páginas:

```
/en/authentication/anti-robot-protection/   ← o conteúdo inglês, no slug novo
/en/autenticacao/protecao-anti-robo/        ← lang="en", título "Proteção anti-robô",
                                              corpo inteiro em português
```

A segunda é o **fallback de tradução ausente**: o Starlight gera, para cada rota do idioma padrão,
uma página em todos os locales, e quando não acha a tradução **naquele mesmo caminho**, serve o
conteúdo do idioma padrão.

Pior: o seletor de idioma da página portuguesa continuou apontando para o caminho antigo — para o
fallback em português, não para a página inglesa real:

```
<option value="/pt/autenticacao/protecao-anti-robo/" selected>
<option value="/en/autenticacao/protecao-anti-robo/">
```

**O caminho é o contrato do i18n do Starlight.** Traduzir o slug joga fora as duas coisas pelas
quais o Starlight foi escolhido: o sidebar por locale e o seletor que leva à página equivalente.

### Alternativas Consideradas

1. **Traduzir e manter o Starlight** — produz 33 páginas-fantasma em português sob `/en/` e um
   seletor de idioma que leva para elas. Descartada pela medição.
2. **Traduzir e abandonar o i18n do Starlight** (sidebar manual por locale, sem seletor) —
   descartada: joga fora o motivo da escolha do gerador.
3. **Slug inglês redirecionando para o canônico português** — ganho parcial (a barra de endereço e
   a indexação continuam em português) a custo de mais 33 stubs. Descartada por não valer o custo;
   fica registrada aqui como retomável.

### Consequências

- **Positivas**: i18n funciona como projetado; o guarda de paridade continua comparando caminho
  com caminho, sem mapa bilíngue para manter.
- **Negativas**: a URL inglesa continua em português. É dívida declarada, não resolvida.
- **Riscos**: nenhum novo — é o estado atual.

---

## ADR-05: O VitePress fica no repositório construindo, não em prosa

**Status**: Aceita
**Data**: 2026-09-19

### Contexto

O solicitante pediu: *"deixe ele comentando como 2 opção caso algo aconteça"*. A leitura barata
seria um parágrafo na ADR-01 dizendo "se der errado, dá para usar VitePress".

### Decisão

O `site-vitepress/` **permanece no repositório, construindo**, com um README que declara os
gatilhos de troca. Não é código morto: é uma alternativa executada, medida e reexecutável.

### Alternativas Consideradas

1. **Um parágrafo na ADR** — descartada. Alternativa em prosa nunca foi executada, e quem tentar
   usá-la vai descobrir os problemas sob pressão, que é o pior momento.
2. **Apagar e confiar no histórico do git** — descartada pelo mesmo motivo: recuperar de um commit
   antigo é reexecutar a migração inteira, sem saber se ainda funciona.

### Consequências

- **Positivas**: o plano B tem evidência (68 páginas, 2.573 links, 0 quebrados) e um procedimento
  de quatro comandos.
- **Negativas**: 79 arquivos e um `package.json` a mais no repositório, que ninguém usa no dia a
  dia. Custo aceito conscientemente.
- **Riscos**: ele **envelhece** — o `converter.mjs` dele lê de `../docs`, e o front-matter de lá
  passa a ser do Starlight. Registrado no README com o ajuste necessário.

### Referências

- `site-vitepress/README.md` — gatilhos de troca e o que cada um faz melhor/pior

---

## ADR-06: Os redirects são stubs commitados em `public/`

**Status**: Aceita
**Data**: 2026-09-19

### Contexto

A migração muda `/pt/comecar/instalacao-avancada.html` para
`/pt/comecar/instalacao-avancada/` — 54 rotas de folha.

### Decisão

Stubs HTML com `meta refresh` + `canonical` + `noindex`, gerados pelo `converter.mjs`, gravados em
`site/public/` e **commitados**.

### Alternativas Consideradas

1. **O `redirects` do Astro** — **tentada e falhou**, e o modo de falha vale registrar: a chave é
   tratada como **rota**, e o `build.format` padrão é `directory`, então `/pt/x.html` vira o
   diretório `x.html/` com um `index.html` dentro — e o arquivo `/pt/x.html` continua não
   existindo. O build quebrou gerando `convites.html/index.html`.
2. **Regra de servidor** — o GitHub Pages não oferece.
3. **Não redirecionar** — descartada pelo solicitante.

### Consequências

- **Positivas**: funciona em qualquer host estático, sem configuração de servidor.
- **Negativas**: 54 arquivos quase idênticos no repositório.
- **Riscos**: envelhecem se uma página for renomeada. Mitigado pelo guarda `[CT-32]`.

### Por que commitados, e não gerados no build

Eles foram para o `.gitignore` por reflexo, e estava errado. Enquanto o Jekyll existir, o
`converter.mjs` consegue regenerá-los; **depois que o `_config.yml` sair e o conteúdo for
transformado no lugar, não há mais "URL antiga" de onde derivá-los**. Deixar ignorado era
transformar fonte em derivado.
