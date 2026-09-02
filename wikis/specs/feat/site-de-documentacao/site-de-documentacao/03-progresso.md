# Progresso — Site de documentação em GitHub Pages

> Feature **nova**, mas toca infra compartilhada em três pontos: `.gitattributes`,
> `.github/workflows/` e os dois READMEs (79 asserções em 6 suítes) → regressão obrigatória.

## 1. Esqueleto do Jekyll em `docs/`

- [x] `docs/_config.yml` com `baseurl: /filament-starter-kit-easy` (`1930f83`)
- [x] `remote_theme: just-the-docs/just-the-docs` — o build nativo aceita
- [x] `docs/pt/` e `docs/en/`
- [x] **Sem `Gemfile` na raiz** nem em `docs/` — a prévia local não foi necessária (ADR-02)
- [x] **`package.json` da raiz intocado** — CT-12 congela o conjunto de dependências

## 2. Imagens

- [x] **Nenhum trabalho necessário** — medido: 20 URLs absolutas, 7 badges, **0 relativas**. CT-21
      guarda que continue assim.

## 3. Navegação (manual)

- [x] **Desvio**: não há `docs/_data/nav_*.yml`. O `just-the-docs` monta a árvore pelo **front
      matter** de cada página (`title`, `parent`, `grand_parent`, `nav_order`, `has_children`).
      Menos um arquivo por idioma para divergir; a paridade passou a ser testada sobre o front
      matter (CT-20, CT-23).
- [x] Seletor de idioma: a home `docs/index.md` oferece os dois; cada árvore é um nó de topo do menu
- [x] Home de cada idioma com os 5 grupos
- [x] Caso de teste que falsifica a paridade das duas árvores — CT-23

## 4. Migração do conteúdo (22 páginas × 2 idiomas)

- [x] `comecar/` (2), `autenticacao/` (6), `recursos/` (7), `operacao/` (5), `referencia/` (4) —
      `6a76df3`
- [x] Cada trecho migrado levou **a asserção de teste junto, no mesmo commit** (ADR-05):
      `documentacaoDoKit()` em `tests/Pest.php` reancorou as 6 suítes

## 5. As divergências PT/EN, antes da migração

- [x] As quatro corrigidas em `fa4e7af` (+24 linhas no inglês: 2.533 → 2.557), **antes** da
      migração. CT-06 prova que chegaram ao destino em inglês.

## 6. Workflow de publicação

- [x] **Nenhum workflow** — CT-25 acusa se alguém reintroduzir um
- [x] Pages habilitado. **Evidência** (`gh api repos/gsferro/filament-starter-kit-easy/pages`,
      2026-09-02):

  ```json
  {"status":"built","html_url":"https://gsferro.github.io/filament-starter-kit-easy/",
   "build_type":"legacy","source":{"branch":"feat/site-de-documentacao","path":"/docs"},"https_enforced":true}
  ```

  ⚠️ **A origem hoje é a branch da feature.** Depois do merge, trocar em Settings → Pages para
  **`main` → `/docs`** — senão o site congela no último push desta branch. É o item 0 da
  verificação manual, e o único passo que nenhum teste alcança (M43′).
- [x] `ci.yml` e `seguranca.yml` não afetados

## 7. READMEs viram landing

- [x] `README.md` → 363 linhas; `README.en.md` → 363 (`c37139d`). CT-13: teto de 30% do histórico,
      piso de 100, as três âncoras da landing, paridade de 5%.

## 8. `.gitattributes`

- [x] `/docs export-ignore` — CT-11 mede pelo `git archive` (0 entradas de `docs/`, controles
      negativos presentes)
- [x] `KitUpdate::CAMINHOS_DO_KIT` não lista `docs/` — `KitUpdateTest` já reprova a interseção

## 9. Verificação Final

- [x] `tests/Kit/SiteDeDocumentacaoTest.php` (CT-01 a 06, 11 a 25) e
      `tests/Kit/RedeDeDocumentacaoTest.php` (CT-07 a 10): **47 testes, 154 asserções, verdes**
- [x] Suíte Kit completa por `vendor/bin/pest --no-tia --parallel --testsuite=Kit`: **1.592 testes,
      5.116 asserções, verdes** (18,7 min) — ver
      "Notas de Implementação"
- [x] `git diff --stat package.json` vazio
- [x] `docs/` fora do `git archive` (medido: 0 entradas)
- [x] **Site publicado** conferido nos dois idiomas — ver a verificação manual abaixo

### Verificação manual (a lista do `04`, com evidência)

| # | Item | Evidência (2026-09-02) |
|---|---|---|
| 0 | Pages habilitado, apontando para `/docs` | JSON acima: `status: built`, `path: /docs`. **Branch ainda é a da feature** — trocar para `main` após o merge |
| 1 | Página de terceiro nível renderiza com CSS, URL com o nome do repositório | `GET /pt/operacao/convencoes-do-kit.html` → 200, 29 KB, tema `just-the-docs` no HTML |
| 2 | Imagem carrega de `raw.githubusercontent` | `GET /en/recursos/anexos-e-midia.html` → 200, `raw.githubusercontent` presente na página |
| 3 | Busca por `getTabs` leva a convenções | `assets/js/search-data.json`: 241 entradas; `getTabs` em exatamente 2 — `pt/…/convencoes-do-kit` e `en/…/convencoes-do-kit`. Índice único, como a ADR-01 aceita |

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas verificadas contra o repositório real

| Premissa | O que a verificação mostrou | Efeito na wiki |
|---|---|---|
| O gerador processaria `{{ }}` como template e quebraria o build | **0 ocorrências** de `{{` e `{%` nos dois READMEs — medido primeiro fora de bloco de código (para o Vue), e depois **dentro também** (para o Liquid do Jekyll, que não poupa bloco de código) | risco afastado nas duas hipóteses de gerador |
| HTML inline e pseudo-tags (`<int>`, `<string>`, `<script>`) quebrariam o build | todas as ocorrências estão **dentro de crases**, e são escapadas antes de qualquer motor de template vê-las | risco afastado |
| A dependência do gerador poderia ir para a raiz | `package.json` **não é `export-ignore`**, e os scripts `build`/`dev` são necessários no projeto instalado | ADR-02 nasceu daqui, e sobreviveu à troca de gerador |
| GitHub Pages já estaria configurado | `GET /repos/.../pages` → **404** | passo 6 inclui habilitar |
| O site precisaria de configuração para servir `art/` | **não precisa**: 20 das 27 referências de imagem já são URLs absolutas, **0 relativas** | passo 2 **deletado**; ADR-06 reescrita |
| O build nativo do Pages aceitaria plugin de i18n | roda em `--safe`, lista fechada: **nenhum** plugin de i18n entra, e o `just-the-docs` também não tem | o bilíngue vira manual, e a paridade passa a depender **só de teste** (ADR-01) |
| As divergências PT/EN seriam cosméticas | **três confirmadas por leitura direta**, e são materiais — ver abaixo | passo 5 sobe para **antes** da migração |

### As três divergências, confirmadas linha a linha

| Trecho | Português | Inglês |
|---|---|---|
| Conta existente **não consome convite** — rota GET sem CSRF, SSO silencioso | 4 linhas com a decisão e a wiki de origem (`README.md:708`) | **ausente** (`README.en.md:718`) |
| `wikis/specs` do kit são `export-ignore` e nascem vazias no projeto | nota completa (`README.md:1366`) | trocada por *"The wiki is written in pt-BR"* (`README.en.md:1379`) |
| Convenção de `getTabs()` vs filtro de modal | 8 bullets (`README.md:2057`) | **7 bullets**, sem o de `getTabs()` |

### Auditoria Ponytail (step 6)

| # | Achado | Aplicado? |
|---|---|---|
| 1 | `delete:` o passo de imagens inteiro — as imagens já são absolutas | **sim** |
| 2 | `shrink:` três seções de template dizendo "Nenhum" | **sim** — fundidas numa |

## Blockers

- Nenhum. O gerador foi decidido (Jekyll embutido) depois de três perguntas — ver a retrospectiva.

## Desvios do Plano

| Onde | O plano dizia | O que foi feito | Por quê |
|---|---|---|---|
| Navegação (passo 3) | `docs/_data/nav_pt.yml` + `nav_en.yml` | **front matter** do `just-the-docs` em cada página | é o mecanismo nativo do tema; um arquivo de menu separado seria uma terceira cópia da estrutura para divergir. CT-20/CT-23 testam o front matter |
| Mapa, h2 #18 "Banco de dados" | `landing` | `ambos` — resumo na landing, detalhe de Docker/Postgres em `comecar/instalacao-avancada.md` | o detalhe pesava mais que o resumo; o fixture do baseline registra a reclassificação |
| CT-05 (`04`) | 3 páginas amostradas, tolerância em **linhas** (10% ou 5) | **todas** as páginas, tolerância em **caracteres** (15% ou 300) | a quebra de linha é escolha de quem traduz: o bullet de `getTabs()` tem 1 linha em pt e 12 em en, mesmo conteúdo. Medir caracteres é medir conteúdo. Percorrer as 30 custa zero e mata M9 |
| CT-07 (`04`) | piso de **79 asserções** | piso de **48 sítios de `toContain`** | as 79 contam linhas de dataset; `token_get_all()` enxerga chamadas. O número literal cumpre o mesmo papel: quem reduz tem que editá-lo |
| CT-12 (`04`) | conjunto **congelado** também para o `composer.json` | conjunto congelado para o npm; **lista de recusa** de geradores de documentação para o composer | os ~70 pacotes PHP mudam toda semana neste kit; um fixture ficaria vermelho em toda adição legítima e ensinaria o time a editá-lo sem ler (`ponytail:` no teste) |
| CT-08 (`04`) | âncora "mostra o nome" em `configuracoes-do-kit` | âncora "arte do login" / "login artwork" | a frase do nome da aplicação migrou para `comecar/instalacao-avancada.md`; a página de configurações fala da arte por outro nome. O `public/images/auth/login.svg` proibido não aparece em lugar nenhum do site |
| CT-09 (`04`) | toda seção que nomeia Discord traz o motivo | … **ou aponta por âncora** para a seção que o traz; e ao menos uma seção traz os dois | o preâmbulo de `login-social` diz "Facebook e Discord ficaram de fora" e linka a seção que explica. Exigir o motivo repetido ali seria duplicar texto para satisfazer teste |
| CT-24 (`04`) | sem desvio de execução | `markTestSkipped` quando o commit do baseline não está no clone | o CI faz checkout raso. A guarda é sobre o **histórico git**, não sobre `docs/` — CT-10 continua proibindo guarda sobre a entrega |

## Notas de Implementação

- **O sub-agente do mapa encontrou o que a leitura casual não encontraria.** As três divergências
  PT/EN estão em pontos distantes de arquivos de 2.500 linhas.
- **`php artisan test` travou sem saída** nesta sessão (dois runs de >10 min, zero bytes). O
  mesmo conjunto por `vendor/bin/pest --no-tia` rodou em 16 s. Provável interação do TIA com
  a árvore suja; não investigado — a suíte Kit foi rodada por `pest --no-tia --parallel`.
- **O baseline foi gerado do commit `fa4e7af`**, o imediatamente anterior ao esqueleto do site, e
  não do `00` — o `00` mediu o inglês antes das divergências serem corrigidas (2.533 vs 2.557).
- **Dois helpers mudaram de casa** para `tests/Pest.php` (`readmeSemCitacao`, `secoesDoMarkdown`),
  porque passaram a ser usados por mais de um arquivo — `HelpersDeTesteTest` reprovaria o contrário.
- **A seção "Como o site é publicado"** entrou em `operacao/desenvolvendo-o-kit.md` nos dois idiomas
  (R8): é o único lugar onde alguém descobre que não há workflow e onde a origem do Pages se liga.

## Retrospectiva

- **O que a wiki acertou**: a lista de "fora do alcance". O Pages estava apontado para a **branch
  da feature**, e nenhum dos 47 testes tem como saber — só a chamada à API mostrou.
- **O que a wiki errou**: o `04` derivou testes sobre `docs/_data/nav_*.yml`, um artefato que a
  implementação nunca criou. Derivar do requisito e não do plano é certo; mas a forma da navegação
  era decisão de plataforma, e o `04` a assumiu antes de o tema ser escolhido.
- **Lição da pergunta de gerador**: quando a resposta a uma pergunta de escolha não é nenhuma das
  opções, o problema costuma estar na pergunta. "Pages hospeda, Jekyll gera" desfez três rodadas.
- **Lição de quoting**: um heredoc de shell engoliu uma barra invertida no `tests/Pest.php`, e o
  erro do parser apontou 30 linhas depois. Editar PHP com heredoc de shell não vale o que economiza.
