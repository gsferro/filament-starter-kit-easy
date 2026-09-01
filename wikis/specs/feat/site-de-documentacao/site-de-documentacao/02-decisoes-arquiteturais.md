# Decisões Arquiteturais — Site de documentação em GitHub Pages

## ADR-01: Jekyll embutido do GitHub Pages, sem workflow

**Status**: Aceita
**Data**: 2026-09-01
**Substitui**: a primeira versão desta ADR, que escolhia VitePress

### Contexto

A pergunta "qual gerador?" foi respondida duas vezes com *"github pages"*, e a segunda veio com o
link da documentação oficial e a pergunta **"esse jekyll é o github pages?"**. A confusão era real e
é comum: o Pages **hospeda** (a própria doc o define como "serviço de hospedagem de sites estáticos
que obtém arquivos HTML, CSS e JavaScript diretamente de um repositório"), e o Jekyll **gera**. O
Pages traz o Jekyll embutido, o que faz os dois parecerem a mesma coisa.

Esclarecida a distinção — e apresentado o custo do bilíngue no Jekyll —, o solicitante escolheu
**"Jekyll, o embutido do Pages"**.

### Decisão

**Jekyll rodando no build nativo do GitHub Pages**, com `Source: Deploy from a branch → main → /docs`.
**Nenhum workflow de Actions.** Tema por `remote_theme`, que o build nativo suporta.

### Alternativas Consideradas

1. **VitePress** — foi a recomendação anterior e continha i18n e busca por idioma nativos.
   Descartada **pelo solicitante**, com o custo do bilíngue manual apresentado antes da escolha.
2. **Jekyll via GitHub Actions** (com Polyglot) — permitiria i18n de verdade, mas devolve o workflow
   que a escolha do build nativo elimina. Fica nomeada como saída se o bilíngue manual se mostrar
   caro demais na prática.

### Consequências

- **Positivas**: publicação sem workflow, sem Node, sem passo de build para manter. O ciclo de
  atualização vira *editar markdown → commit → o Pages publica*. É o processo mais simples possível,
  e RQ-06 fica atendida quase de graça.
- **Negativas, e elas são reais**: o build nativo roda em `--safe`, com lista fechada de plugins.
  **Nenhum plugin de i18n é permitido** — nem Polyglot, nem `jekyll-multiple-languages-plugin`. E o
  `just-the-docs`, tema de documentação padrão do ecossistema, **não tem i18n** (issue aberta desde
  2018). Portanto:
  - as duas árvores de navegação são mantidas **à mão**, em `_data`;
  - o seletor de idioma é escrito no layout;
  - a busca do tema tem índice único e **mistura os idiomas**.
- **Riscos**: nada no build acusa uma página que existe em `/pt/` e não em `/en/`. Como já existem
  **quatro divergências PT/EN** nos READMEs, este é o risco central da entrega — e ele passa a ser
  coberto **só por teste** (ADR-05 e os cenários de paridade do `04`).

### Referências

- [O que é o GitHub Pages](https://docs.github.com/pt/pages) — a definição que separa hospedagem de geração
- [Temas no Pages / `remote_theme`](https://docs.github.com/en/pages/setting-up-a-github-pages-site-with-jekyll/adding-a-theme-to-your-github-pages-site-using-jekyll)
- [just-the-docs — i18n em aberto](https://github.com/just-the-docs/just-the-docs/issues/59)

---

## ADR-02: Nenhuma dependência de build entra na raiz do repositório

**Status**: Aceita
**Data**: 2026-09-01
**Nota**: reescrita depois da ADR-01 trocar VitePress por Jekyll — o princípio sobreviveu, o arquivo mudou

### Contexto

O princípio: **o projeto que nasce do `create-project` não pode pagar pela documentação do kit.**
`package.json` **não** é `export-ignore` e não pode ser (os scripts `build` e `dev` são necessários
no projeto instalado), então qualquer dependência declarada ali seria baixada por todo mundo.

Com o Jekyll no build nativo, o problema muda de forma: **não há dependência a instalar**. O Pages
resolve as gems no servidor dele; o repositório não precisa de `Gemfile` para publicar.

### Decisão

- **`package.json` da raiz não é tocado** — verificável, e o `04` tem cenário para isso.
- **Nenhum `Gemfile` na raiz.** Se um for necessário para build local (`bundle exec jekyll serve`),
  ele vive em `docs/`, junto do que documenta.

### Alternativas Consideradas

1. **`Gemfile` na raiz** — descartada: colocaria Ruby na raiz de um projeto PHP+Node, e todo projeto
   instalado nasceria com ele.
2. **Nenhum `Gemfile`, nem em `docs/`** — é o mínimo absoluto e funciona para publicar. Descartada
   como padrão porque impede prévia local: quem edita a documentação publica às cegas.

### Consequências

- **Positivas**: `npm install` do projeto instalado continua idêntico; nenhuma toolchain nova na raiz.
- **Negativas**: quem quiser prévia local instala Ruby. O `docs/README` precisa dizer isso.
- **Riscos**: publicar sem prévia é o caminho normal aqui, e erro de layout só aparece no ar.

### Referências

- `.gitattributes` — o bloco que explica por que `phpunit.xml` e `pint.json` **não** são `export-ignore`
- `package.json` — as seis `devDependencies` que não devem mudar

---

## ADR-03: `docs/` é `export-ignore`

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

Tudo que está no repositório vai para a instalação de quem roda o `create-project`, salvo
`export-ignore`. O kit já tomou essa decisão duas vezes — para `/.github` e para `/wikis/specs` —,
e o argumento registrado no `.gitattributes` é o mesmo: *é material do kit, não do projeto que nasce
dele*.

Um `docs/` com o site inteiro é dezenas de arquivos markdown sobre o **kit**, mais uma toolchain de
documentação. Nada disso é do projeto instalado.

### Decisão

`/docs export-ignore` no `.gitattributes`, no mesmo bloco e com o mesmo tipo de justificativa.

### Alternativas Consideradas

1. **Deixar `docs/` no dist** — descartada: o projeto instalado nasceria com a documentação do kit
   dentro, e o agente de IA do projeto varreria dezenas de páginas alheias antes de escrever a
   primeira dele. É literalmente o argumento que o `.gitattributes` já registra para `wikis/specs`.

### Consequências

- **Positivas**: o dist do `create-project` não cresce.
- **Negativas**: quem instalou o kit não tem a documentação offline — mas tem o README, que continua
  no dist, e o link para o site.
- **Riscos**: `KitUpdate::CAMINHOS_DO_KIT` precisa ser conferido; se ele entregar `docs/` de um lado
  enquanto o `export-ignore` o retém do outro, as duas pontas divergem — foi o defeito que a wiki do
  `wikis/specs` documentou.

### Referências

- `.gitattributes` — o bloco de `/wikis/specs`
- `app/Console/Commands/KitUpdate.php` — `CAMINHOS_DO_KIT`

---

## ADR-04: O README vira landing e continua existindo

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

O requisito manda descarregar a documentação dos READMEs para o site. A leitura fácil seria esvaziar
os dois arquivos e apontar para a URL.

Mas o README é a **página do pacote no Packagist** e a primeira tela do repositório no GitHub. Quem
descobre o kit por uma busca no Packagist nunca vê o site — vê o README, e decide ali se instala.

### Decisão

O README **encolhe e permanece**: o que é o kit, instalação em poucos comandos, três ou quatro
capturas, e links para o site. O conteúdo profundo migra.

### Alternativas Consideradas

1. **Esvaziar e redirecionar** — descartada pelo argumento acima: piora a descoberta do pacote para
   ganhar consistência de fonte única.
2. **Duplicar o conteúdo nos dois** — descartada: duas fontes do mesmo texto divergem, e este
   repositório **já tem a prova disso** — o `README.en.md` afirmava um provedor anti-robô padrão
   diferente do `README.md`, e o erro sobreviveu até uma leitura casual.

### Consequências

- **Positivas**: o Packagist continua vendendo o kit; o site ganha o conteúdo profundo.
- **Negativas**: existem duas superfícies de documentação, e a fronteira entre elas precisa ser
  mantida por alguém.
- **Riscos**: o README voltar a crescer. É como ele chegou a 2.522 linhas.

### Referências

- `00-requisito.md` → a ambiguidade "o README esvazia?"

---

## ADR-05: A rede de testes acompanha o conteúdo, no mesmo commit

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

São **79 asserções** sobre `README.md`/`README.en.md`, em 6 suítes. Não são superficiais: há
asserção de **ausência** com filtro de citação (`readmeSemCitacao()`), que existe para permitir
afirmar "isto não é mais instruído" sem falso positivo quando o texto **cita** o antigo.

Essa rede é o que impede a documentação de mentir sobre comportamento — e ela **já pegou erro real
nesta mesma sessão**: três instruções "troque a arte em `public/images/auth/login.svg`" viraram
mentira quando o arquivo deixou de existir, e o teste as apanhou.

Mover conteúdo para `docs/` sem mover a asserção junto **apaga a garantia em silêncio**: o teste
continua verde porque continua encontrando o texto no README — até o texto sair de lá, e aí ele fica
vermelho por motivo errado, ou é "consertado" removendo a asserção.

### Decisão

Toda asserção que se refere a um trecho migrado **muda de alvo no mesmo commit que move o trecho**.
Nenhum commit intermediário fica com a documentação num lugar e o teste apontando para outro.

Onde o helper de leitura precisar ser compartilhado por mais de um arquivo de teste, ele vai para
`tests/Pest.php` — `.ai/rules/testes.md` é explícita, e `tests/Kit/HelpersDeTesteTest.php` reprova
quem clonar helper com outro nome.

### Alternativas Consideradas

1. **Migrar tudo e ajustar os testes depois** — descartada. É o caminho pelo qual a garantia se
   perde: entre um passo e outro existe um estado em que a suíte está verde e a documentação está
   errada, e é nesse estado que alguém decide que "o teste está atrapalhando".
2. **Testar só o site, abandonando os READMEs** — descartada: o README continua existindo (ADR-04) e
   continua fazendo afirmações sobre comportamento.

### Consequências

- **Positivas**: a documentação continua verificável depois da mudança de meio.
- **Negativas**: cada passo de migração fica maior, porque carrega o teste junto.
- **Riscos**: um trecho migrado sem asserção correspondente passa despercebido. Mitigação: o
  `04-casos-de-teste.md` precisa de um caso que afirme **onde** cada garantia mora depois da
  migração.

### Referências

- `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` — `readmeSemCitacao()`
- `.ai/rules/testes.md` — helper compartilhado vai para `tests/Pest.php`

---

## ADR-06: As imagens não exigem nada — elas já são absolutas

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

`art/` tem 8,5 MB versionados, e o site precisa das mesmas imagens. O caminho ingênuo seria copiar
`art/` para dentro de `docs/public/`, dobrando o peso do repositório.

### Decisão

**Nada a fazer.** Medição: das 27 referências de imagem do `README.md`, **20 já são URLs absolutas**
`raw.githubusercontent` e 7 são badges externos — **nenhuma é caminho relativo**. O markdown migrado
carrega essas URLs consigo e funciona no site sem configuração.

### Alternativas Consideradas

1. **Copiar `art/` para `docs/public/`** — descartada: duplica 8,5 MB e cria duas cópias que
   divergem no primeiro `composer art` rodado sem a cópia.
2. **`publicDir` do Vite apontando para `art/`** — descartada por **desnecessária**. Seria a solução
   correta se as imagens fossem relativas; não são. E `publicDir` fora de `docs/` não é feature
   documentada do VitePress — só passthrough do Vite, com issue aberta no repositório deles.

### Consequências

- **Positivas**: um passo a menos no plano, uma configuração a menos, nenhum peso novo no repositório.
- **Negativas**: as imagens não passam pelo pipeline do Vite (sem hash, sem otimização) e o site
  depende do GitHub servi-las. Para um repositório público cujas imagens já são servidas assim hoje,
  é o mesmo risco de sempre.
- **Riscos**: se o repositório virasse privado, as imagens quebrariam no site. Aí a alternativa 1
  volta — e continua sendo uma cópia, não uma arquitetura.

### Referências

- Medição: 20 `raw.githubusercontent` + 7 badges + **0 relativas**, nos dois READMEs
