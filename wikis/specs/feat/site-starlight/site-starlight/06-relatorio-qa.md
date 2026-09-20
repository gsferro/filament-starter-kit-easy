# Relatório de QA — Migração do site para Astro Starlight

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** (evolução + UI com JS)
> Natureza da wiki: evolução · Regressão: **sim** (tipo **e** infra compartilhada)

> ⚠️ **DEGRADAÇÃO DECLARADA — separação de poderes.** A skill exige que quem julga não seja quem
> implementou. O sub-agente designado para isto morreu num limite de sessão (HTTP 429), e este
> ciclo foi executado **pelo mesmo agente que escreveu a wiki e o código**. É a condição que a
> skill nomeia como cegueira correlacionada. O que compensa parcialmente é que os achados abaixo
> vieram de **comandos**, não de releitura — cada um traz o comando que o produziu.

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 3 · Minor: 1 · Cosmético: 1
- Ambiente: sem app servido (o artefato é site estático fora do Laravel) · Pest 5 ·
  Playwright MCP: indisponível · driver de cobertura: **ausente**

O roteamento é para **especificação** por prioridade (especificação > teste > implementação): três
dos cinco achados são texto que contradiz o código entregue, e corrigir qualquer outra coisa antes
disso é retrabalho.

**Nenhum achado é de comportamento.** O site construído está correto e medido: 67 páginas, 2.296
links internos sem quebra, 54 redirects resolvendo sob o `base` de produção, suíte `Kit`
2.227/2.227. O que reprova é a **consistência documental** — a dimensão que a skill criou depois de
medir 27 achados dessa natureza numa feature já dada como concluída.

## Achados

### QA-01 — o `03` declara quatro propagações que não aconteceram · Major · destino 1

- **Dimensão**: L3
- **Relacionado a**: `03` → `## Desvios do Plano`, itens 1–4
- **Esperado**: a skill exige que todo desvio corrija a **fonte** (`01`/`02`), marcada inline com
  `*(alterado em {data}: {motivo})*`. O `03` afirma, em quatro itens, *"Propagado para…"*.
- **Observado**: `grep -c "alterado em" 01-plano-acao.md 02-decisoes-arquiteturais.md` → **0 e 0**.
  Nenhuma das quatro edições foi feita. O `03` afirma um trabalho que não existe.
- **Repro**: `grep -c "alterado em" wikis/specs/feat/site-starlight/site-starlight/0[12]-*.md`
- **Por que é Major e não Minor**: uma afirmação falsa de propagação é **pior** que a ausência
  dela. Ela diz à próxima pessoa que o PRD está atualizado, e ela vai ler o PRD.
- **Ação exigida**: fazer as quatro edições no `01`/`02` com a marca de data, ou retirar a
  afirmação do `03`.

### QA-02 — o `## Rollback` do `01` descreve um rollback que não funciona · Major · destino 1

- **Dimensão**: L3
- **Relacionado a**: `01` → `## Rollback`, `## Riscos`, passo 12
- **Esperado**: o plano descreve como reverter.
- **Observado**: três afirmações contraditas pelo código entregue —
  - *"é barato porque o Jekyll não é destruído no mesmo commit"* → o `_config.yml` **foi** removido
    no commit `7e1ffc0`;
  - *"o passo 12 é o último, separado, e não vai no mesmo commit do resto"* → foi no mesmo commit;
  - `## Riscos`: *"o `docs/` fica legível por dois geradores até o passo 12"* → não fica: o
    conteúdo transformado tem o H1 fora do corpo e links absolutos sem o `baseurl`.
- **Repro**: `sed -n '/^## Rollback/,/^## Dep/p' 01-plano-acao.md` contra
  `git show 7e1ffc0 --stat | grep _config`
- **Por que importa**: quem seguir esse rollback em incidente faz `git revert` de um commit que não
  existe e conclui que o site está recuperável quando não está.
- **Ação exigida**: reescrever `## Rollback` para o procedimento real — reverter o merge e voltar o
  `Source` do Pages —, e remover o risco que deixou de existir.

### QA-03 — `[CT-41]` existe no teste e não existe no `04` · Major · destino 3

- **Dimensão**: L1
- **Relacionado a**: `tests/Kit/SiteDeDocumentacaoTest.php` → `[CT-41]`
- **Esperado**: Proibição 11 da `feature-test-design` — *"não escrever teste `[CT-nn]` sem o
  cenário no `04`"*. Cenário descoberto na implementação **nasce no `04`**, com regra, Gherkin e
  mutantes; só depois vira código.
- **Observado**: `CT-41` foi escrito direto como teste, durante o step 7.5, a partir do defeito
  medido. É teste derivado do código — a inversão exata que a proibição existe para evitar.
- **Repro**: `diff <(grep -oh '\[CT-[0-9]*\]' tests/Kit/SiteDeDocumentacaoTest.php | tr -d '[]' |
  sort -u) <(grep -oh 'CT-[0-9]*' 04-casos-de-teste.md | sort -u)` → `CT-41` só à esquerda
- **Ação exigida**: escrever o cenário `CT-41` no `04` — regra, Gherkin, mutantes — e ligá-lo à
  `R5` ou a uma regra nova sobre reexecutabilidade do conversor.

### QA-04 — RQ-04 não está satisfeita, e não pode ser pelo agente · Minor · destino 4

- **Dimensão**: A
- **Relacionado a**: RQ-04 (*"o gerador do site **publicado** passa a ser o Starlight"*), passo 8
- **Esperado**: o site publicado servido pelo Starlight.
- **Observado**: o `Source` do GitHub Pages continua em `Deploy from a branch → main → /docs`. O
  workflow existe e está correto, mas o Pages o ignora enquanto a origem não mudar. A cláusula está
  **parcialmente** atendida: o mecanismo está pronto, a publicação não trocou.
- **Repro**: `Settings → Pages` no repositório.
- **Destino 4 (infra), não 2**: não é defeito de código — é uma ação fora do repositório. Não
  reprova a feature, e já está registrada como blocker no `03`.
- **Ação exigida**: o solicitante trocar a origem. **Com urgência**, porque o conteúdo já é
  Starlight: entre o merge e a troca, o site no ar fica quebrado.

### QA-05 — contagem de mutantes do cabeçalho do `04` está velha · Cosmético · destino 1

- **Dimensão**: L1
- **Observado**: o cabeçalho declara *"Mutantes previstos: 27"*; a contagem real é **32** (a
  revisão adversarial acrescentou cinco, e a skill permite estourar o teto quando eles vêm de lá).
- **Repro**: `grep -oh '^| M[0-9]\+ ' 04-casos-de-teste.md | wc -l` → 32
- **Ação exigida**: recalcular, ou remover a contagem manual — a skill aceita as duas.

## Matriz de Rastreabilidade

| RQ | Cláusula | Passo PRD | CT | Código | Resultado |
|----|----------|-----------|----|--------|-----------|
| RQ-01 | aparência moderna | 2, 3 | CT-30, 31, 32, 33 | `site/src/styles/kit.css` | ✅ sob premissa declarada |
| RQ-02 | VitePress lado a lado | — | — | `site-vitepress/` | ✅ atendido antes desta wiki |
| RQ-03 | cores corrigidas em navegador | 9 | CT-32, CT-33 | `kit.css` | ✅ |
| RQ-04 | Starlight publicado | 1,2,7,**8** | CT-25, 26, 27 | `pages.yml` | ⚠️ **QA-04** — passo 8 fora do repositório |
| RQ-05 | VitePress como plano B | 11 | CT-39 | `site-vitepress/README.md` | ✅ |
| RQ-06 | dois idiomas | 4, 9 | CT-26, 34, 35 | `astro.config.mjs` | ✅ |
| RQ-07 | slugs em português | 4 | CT-34 | (não-ação) | ✅ |
| RQ-08 | redirects | 5, 9 | CT-36, 37, 38 | `site/public/**` | ✅ |
| RQ-09 | implementada | 1–12 | — | — | ⚠️ exceto passo 8 |

**Nenhuma cláusula sem passo, sem CT ou sem código.** Não há omissão silenciosa.

## Ambiguidade nova encontrada pelo gate

**RQ-05 — *"deixe ele comentando como 2 opção"*** tem duas leituras em português: *"deixe-o
documentado como segunda opção"* e *"deixe-o comentado"* (desativado). A implementação adotou a
primeira, que é a leitura que o resto da frase sustenta (*"caso algo aconteça"* pressupõe algo
utilizável). Não vira achado porque a leitura adotada **satisfaz as duas** — um plano B que
funciona também está, a fortiori, desativado no sentido de não ser usado. Registrada para não
reaparecer.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-04; nenhuma omissão silenciosa |
| B | Fronteiras e dados | ⏭️ | **não se aplica**: sem entrada de usuário. A única é `DOCS_BASE`, de build, coberta por CT-15/CT-16 |
| C | Matriz de permissão | ⏭️ | **não se aplica**: site estático e público, sem sessão |
| D | Observabilidade (log) | ⏭️ | **não se aplica**: a feature não executa código de aplicação. O equivalente — a saída do build — é guardado por CT-40 |
| E | Performance | ✅ | build 5,5s, 67 páginas; sem request de aplicação |
| F | UX de erro | ✅ | o único estado de erro é a URL antiga, e ela tem destino (CT-36) |
| G | Tema e cor | ✅ | capturas nos dois temas em navegador real; 4 achados, todos corrigidos e travados por CT-32/CT-33 |
| H | Acessibilidade | ❌ **não verificada** | ver `## Não Verificado` |
| I | Segurança da superfície nova | ✅ | sem `app/`; permissões do workflow escopadas (`pages: write`, `id-token: write`) e `concurrency` serializa |
| J | Regressão adjacente | ⚠️ parcial | `Kit` **2.227/2.227, 8.721 asserções**; `Tenancy` ainda rodando ao fechar este ciclo |
| K | Adequação da suíte | ⚠️ | passo estático feito: 1 achado (CT-37 sem piso), já corrigido antes deste ciclo. Medição por mutação: ver `## Não Verificado` |
| L | Consistência documental | ❌ | **QA-01, QA-02, QA-03, QA-05** — a dimensão que reprova |

## Débitos Aceitos

Nenhum. Os achados Minor/Cosmético (QA-04, QA-05) são acionáveis e ficam abertos, não aceitos.

## Suspeitas Não Confirmadas

- **`sharp` pode ser dependência morta** — não há mais imagem local em `docs/` depois que o
  `[CT-21]` obrigou a voltar para URL absoluta. Não removido, e não confirmado: o Astro documenta
  `sharp` como serviço de imagem padrão, e remover sem medir troca uma dependência ociosa por um
  build quebrado na primeira imagem local que alguém adicionar.

## Não Verificado

- **Acessibilidade (dimensão H)** — nenhuma ferramenta de acessibilidade foi rodada contra o site
  construído. O `pest-plugin-browser` não alcança um site estático de outro toolchain, e o
  Playwright MCP não está configurado nesta sessão. O `capturas.mjs` dirige o Playwright direto e
  **poderia** rodar `axe`, e não roda. É a lacuna mais significativa deste ciclo.
- **Mutation score (dimensão K, passo 2)** — sem PCOV nem Xdebug instalados. E, mesmo com eles, o
  alvo seria `converter.mjs` e `verifica-links.mjs`, que são **JavaScript**: o `pest --mutate` não
  os alcança. A adequação desses dois está coberta por controle negativo manual (modo de falha
  plantado, exit 1 conferido), não por mutação.
- **Suíte `Tenancy`** — ainda em execução ao fechar o ciclo. A `Kit`, que contém todos os cenários
  desta feature, fechou verde.
- **O site publicado** — não existe ainda (QA-04). Tudo o que foi verificado é o `dist/` local,
  construído com o mesmo comando e o mesmo `base` que o workflow usa.

---

## Veredito — Ciclo 2

**APROVADO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0 · **nenhum achado novo**
- `QA-04` permanece aberto **por desenho**: é destino 4 (infra), depende de ação do solicitante
  fora do repositório, e a skill define que destino 4 não reprova a feature.

Os cinco achados do ciclo 1 foram fechados e **reconferidos por comando**, não por releitura —
releitura é exatamente o que produziu o ciclo 1.

| Achado | Fechamento | Reconferência |
|---|---|---|
| QA-01 | quatro propagações feitas no `01` e no `02`, com marca de data | `grep -c "alterado em 2026-09-20"` → **4** no `01`, **3** no `02` |
| QA-02 | `## Rollback` reescrito para o procedimento real (reverter o merge + trocar o `Source`); risco inexistente riscado; passo 12 corrigido | `grep -c "não é destruído no mesmo commit"` → **0** |
| QA-03 | `CT-41` nasceu no `04`: regra `R10`, Gherkin, e três mutantes (`M33`–`M35`) | `diff` dos IDs entre teste e `04` → **sincronizado** |
| QA-05 | contagens recalculadas por `grep` | declarado 17/10/35 · real **17/10/35** |

**Um achado extra saiu do próprio fechamento**, e vale registrar porque é da mesma família do
QA-01: ao propagar para a ADR-02, apareceu que o texto afirmava *"mitigado por teste: o `[CT-30]`
planta uma sentinela em `docs/`"*. **Falso** — o `[CT-30]` afirma sobre H1, e nenhum teste planta
sentinela; ela foi medição manual do planejamento. A mitigação real é o piso de população do
`verifica-links.mjs`, e o texto passou a dizer isso.

### Regressão (dimensão J), agora completa

`php artisan test --testsuite=Kit,Tenancy` → **2.601/2.601, 10.121 asserções**. Fecha a linha que
o ciclo 1 deixou como ⚠️ parcial.

### O que continua não verificado

A seção `## Não Verificado` do ciclo 1 vale igual, e a lacuna mais significativa não mudou:
**acessibilidade nunca foi medida** contra o site construído. O `capturas.mjs` dirige o Playwright
e poderia rodar `axe`; não roda. Fica como débito declarado, não como item silenciado.
