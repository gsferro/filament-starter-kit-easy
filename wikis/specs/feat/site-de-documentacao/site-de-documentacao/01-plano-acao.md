# Plano de Ação — Site de documentação do pacote em GitHub Pages

> Requisito: `00-requisito.md` · Mapa do conteúdo: `90-mapa-do-conteudo.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: nenhuma — não há site hoje
- **Toca infra compartilhada?**: **sim**, em três pontos, e é isso que força regressão apesar do tipo:
  1. `.gitattributes` — o que entra no dist do `create-project`
  2. `.github/workflows/` — o CI do repositório ganha um workflow
  3. **os dois READMEs**, que têm **79 asserções em 6 suítes**

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | Site em GitHub Pages | 1, 6 | Jekyll no build nativo, sem workflow (ADR-01) |
| RQ-02 | Leitura mais fácil que os READMEs | 3, 4 | navegação em 5 grupos, busca local, 22 páginas por idioma |
| RQ-03 | O conteúdo vem dos READMEs | 4 | migração, não reescrita |
| RQ-04 | Kick-off | — | **respondido pelo solicitante: migração COMPLETA nesta entrega** |
| RQ-05 | Mídia é imagem e GIF | — | atendida sem trabalho: as imagens já são URLs absolutas (ADR-06) |
| RQ-06 | O processo de atualização é conhecido | 6, 8 | workflow + a seção "Como atualizar" no `03` |

## Objetivo

Publicar a documentação do kit como site navegável em GitHub Pages, migrando o conteúdo dos dois
READMEs — que somam 5.055 linhas — para 22 páginas por idioma, organizadas em 5 grupos, com busca
local e roteamento `/pt/` e `/en/`.

Os READMEs **encolhem para ~340 linhas cada** e continuam existindo como landing (ADR-04): eles são
a página do pacote no Packagist, que é por onde muita gente descobre o kit.

## Contexto

`README.md` tem 2.522 linhas, 32 seções `h2` e 83 `h3`; o `README.en.md`, 2.533. É documentação de
site vivendo num arquivo que o GitHub renderiza numa coluna só, sem busca e sem navegação.

O mapa do conteúdo (`90-mapa-do-conteudo.md`) já classificou cada seção como `landing`, `site` ou
`ambos`, e propôs a árvore.

## O que a pesquisa encontrou, e que o plano precisa respeitar

| Achado | Consequência no plano |
|---|---|
| **20 das 27 imagens dos READMEs já são URLs absolutas; nenhuma é relativa** | o passo de imagens **deixou de existir** (ADR-06) |
| O build nativo do Pages roda em `--safe`: **nenhum plugin de i18n é permitido** | o bilíngue é manual — duas árvores em `_data`, seletor no layout (ADR-01) |
| `remote_theme` **é** suportado no build nativo | dá para usar um tema de documentação sem publicar gem |
| O `just-the-docs` **não tem i18n** (issue aberta desde 2018) | idem: nada do tema ajuda na paridade |
| Markdown do Jekyll é **Liquid**, e o Liquid processa **até dentro de bloco de código** | **medido: 0 ocorrências** de `{{` e de `{%` nos dois READMEs, dentro ou fora de código. O risco não se materializa hoje; fica como guarda para conteúdo novo |
| `package.json` da raiz **não** é `export-ignore` | nada de dependência na raiz; sem `Gemfile` na raiz (ADR-02) |
| **3 divergências PT/EN** encontradas pelo mapa, além da já corrigida | passo 5 as trata **antes** da migração |

## Autorização

Nenhuma. O site é público e estático; não há usuário, sessão ou policy.

## Rotas

Nenhuma rota Laravel. As rotas do site são arquivos:
`/pt/`, `/en/` e as 22 páginas de cada, conforme `90-mapa-do-conteudo.md`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação | Depende de JS? |
|---|---|---|---|---|
| Site de documentação | Jekyll (estático) | `gsferro.github.io/filament-starter-kit-easy/` | ler, navegar, buscar | Sim (só a busca do tema) |

**Gate de CT-B**: **sem CT-B**, mas *não* pelo motivo que a primeira versão deste plano deu. O
argumento correto é que **o arnês não alcança o artefato**: o `pest-plugin-browser` sobe o app
Laravel, e o site é gerado pelo GitHub e servido por ele — não existe localmente para ser visitado.

Isto é uma **limitação declarada**, não uma prova de que tudo está coberto. Duas coisas ficam fora do
alcance de qualquer teste automatizado desta suíte, e por isso viram roteiro manual obrigatório no
passo 9: o `baseurl` estar correto no site publicado, e a busca do tema funcionar. O `04` registra
isso em `## Fora do alcance` — crédito ao sub-agente que contestou a justificativa original.

**Gate de tela de escrita**: nenhuma tela de escrita.

## Variáveis de Ambiente · Eventos · Jobs

Nenhum dos três. O deploy usa o `GITHUB_TOKEN` que o Actions já fornece, e a feature não tem código
de runtime.

## Impacto em Features Existentes

- **As 6 suítes que leem os READMEs** (79 asserções) — cada trecho migrado leva sua asserção junto,
  no mesmo commit (ADR-05). É o maior risco desta entrega.
- **`create-project`** — `docs/` precisa ser `export-ignore` (ADR-03), e `KitUpdate::CAMINHOS_DO_KIT`
  precisa ser conferido para não entregar de um lado o que o `export-ignore` retém do outro.
- **CI** — um workflow novo, que não deve interferir no `ci.yml` nem no `seguranca.yml`.
- **`npm install` da raiz** — **não muda** (ADR-02). Verificável: o `package.json` da raiz fica
  idêntico.

## Rollback

`git revert` + desabilitar Pages em Settings. Nada de migration, dado ou config de aplicação. Os
READMEs voltam inteiros pelo revert.

## Dependências

**Nenhuma.** O build nativo do Pages resolve as gems no servidor; não há npm, não há Actions, não há
dependência PHP nova. Um `Gemfile` em `docs/` é opcional, só para prévia local (ADR-02).

## Riscos

- **A migração é grande** — ~2.180 linhas por idioma, 44 páginas. O risco não é técnico, é de
  **perda silenciosa**: um trecho que não chega ao destino. Mitigação: o passo 4 migra por seção, e o
  passo 7 confere a contagem de linhas origem × destino.
- **Divergência PT/EN aumenta** — já são 4 conhecidas. Migrar duplica a superfície onde isso acontece.
  Mitigação: passo 5 corrige as 4 antes, e o `04` precisa de um caso de **paridade estrutural**.
- **Link quebrado em massa** — âncoras internas (`#secao`) viram links entre páginas, e **o Jekyll
  não falha em link morto**: ele publica o link quebrado sem reclamar. Diferente do que a primeira
  versão deste plano supunha, quando o gerador era outro. Mitigação: o `04` cobre os links, e a
  conferência do site publicado deixa de ser zelo e vira **obrigatória**.
- **O `baseurl` errado quebra tudo em produção e nada em local.** Mitigação: conferir o publicado.
- **Liquid processa o conteúdo migrado**, inclusive dentro de bloco de código — ao contrário do que
  aconteceria em outros geradores. **Medido: zero ocorrências** de `{{` e `{%` nos dois READMEs, então
  a migração de hoje não é afetada. Fica como guarda para quem escrever exemplo Blade depois: `{% raw %}`.

## Channel de Log da Feature

**Nenhum.** Não há código de aplicação nesta feature: é conteúdo, configuração de build e workflow.
Nada executa em runtime do Laravel, então não há decisão de fluxo a registrar.

## Estrutura de Implementação

### 1. Esqueleto do Jekyll em `docs/` (RQ-01)

- **Paths novos**: `docs/_config.yml`, `docs/index.md`, `docs/pt/index.md`, `docs/en/index.md`
- `remote_theme` apontando para um tema de documentação (o build nativo aceita — ADR-01)
- **Sem `Gemfile` na raiz**; se houver um para prévia local, ele fica em `docs/` (ADR-02)
- `baseurl: /filament-starter-kit-easy` e `url:` — o equivalente Jekyll do problema de subdiretório
- **`package.json` da raiz não é tocado** — verificável por `git diff --stat`

### 2. Imagens (RQ-05) — nada a fazer

As 20 imagens dos READMEs já são URLs absolutas `raw.githubusercontent`; **nenhuma é relativa**. Elas
migram dentro do markdown e funcionam. Sem `publicDir`, sem cópia, sem passo (ADR-06).

### 3. Navegação e as 5 seções de topo (RQ-02) — **manual**

- Duas árvores em `docs/_data/nav_pt.yml` e `docs/_data/nav_en.yml`, espelhando o
  `90-mapa-do-conteudo.md`. **São mantidas à mão**: o Jekyll no build nativo não tem i18n (ADR-01).
- Seletor de idioma escrito no layout.
- Home de cada idioma com os cartões dos 5 grupos.
- **O `04` precisa de um caso que falsifique a paridade das duas árvores** — é o único mecanismo
  que sobra contra o inglês ficar para trás, já que o build não acusa.

### 4. Migração do conteúdo (RQ-03) — o passo grande

- **Uma seção por vez**, na ordem do mapa, e **nos dois idiomas juntos** — separar os idiomas é como
  a divergência nasce.
- Ajustes obrigatórios em cada página migrada:
  - âncoras internas (`#secao`) viram links de página
  - caminhos de imagem passam a resolver pelo `base`
  - o que era `>` de citação continua `>`; **não** converter para callout do tema nesta
    entrega — é reescrita de conteúdo, e o `00` a declarou fora de escopo
- **Cada trecho migrado leva sua asserção de teste junto, no mesmo commit** (ADR-05).

### 5. As 4 divergências PT/EN, antes de migrar (risco)

- Corrigir no README as três omissões que o mapa achou (vínculo de convite com login social; a nota
  sobre onde ficam as ADRs do kit; o bullet de `getTabs()`) e a divergência de F-06.
- **Antes** da migração, de propósito: migrar primeiro é carregar a divergência para 44 arquivos.

### 6. Publicação (RQ-01, RQ-06) — **sem workflow**

- **Nenhum arquivo em `.github/workflows/`.** O build nativo do Pages dispensa Actions (ADR-01).
- Habilitar em Settings → Pages → **Source: Deploy from a branch → `main` → `/docs`**
  (hoje o repositório responde 404 em `/pages`).
- **O ciclo de atualização (RQ-06) passa a ser**: editar o markdown em `docs/`, commitar na `main`,
  e o Pages publica sozinho. Sem build local obrigatório, sem workflow para manter.
- Conferir que o `ci.yml` e o `seguranca.yml` não são afetados.

### 7. Encolher os READMEs (RQ-02, ADR-04)

- De 2.522 → ~340 linhas (PT) e 2.533 → ~345 (EN).
- Ficam: identidade, badges, instalação, capturas, e os links para o site.
- **Conferir contagem origem × destino** antes de commitar: linha que sumiu dos dois lados é conteúdo
  perdido.

### 8. `.gitattributes` (ADR-03)

- `/docs export-ignore`, no bloco existente, com a justificativa no mesmo tom das outras.
- Conferir `KitUpdate::CAMINHOS_DO_KIT`.

### 9. Verificação

- `npm run docs:build` dentro de `docs/` — o build é o portão de link quebrado
- `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — as 79 asserções
- `git diff --stat package.json` — precisa vir **vazio**
- `git archive` de teste, conferindo que `docs/` não entra no dist
- Conferir o **site publicado**, não só o build local

## Filosofia de Implementação

> **Ponytail em `full`.** O que a escada decide aqui:
> 1. **Sem tema customizado.** `remote_theme` com um tema pronto resolve; tema próprio é trabalho que não
>    foi pedido e vira manutenção permanente.
> 2. **Sem plugin de busca.** A busca local é embutida — uma linha de config.
> 3. **Sem duplicar imagem** (ADR-06).
> 4. **Sem converter citações em callouts** do tema. É reescrita, não migração.
> 5. **Sem `docs/` na raiz do `package.json`** (ADR-02).

## Testes

> Ver `04-casos-de-teste.md`. **Sem `05-*-browser.md`** — ver o gate na `## Superfície de UI`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `npm run docs:build` limpo
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
- [ ] `package.json` da raiz intocado
- [ ] `docs/` fora do `git archive`
- [ ] Site publicado conferido no navegador, nos dois idiomas

## Commits

- `✨ feat(docs): esqueleto do site em Jekyll`
- `🐛 fix(readme): corrige as divergências entre português e inglês`
- `📝 docs(site): migra {seção} para o site` — um por grupo
- `📝 docs(readme): READMEs viram landing e apontam para o site`
- `📝 docs(site): habilita o GitHub Pages e aponta para docs/`
