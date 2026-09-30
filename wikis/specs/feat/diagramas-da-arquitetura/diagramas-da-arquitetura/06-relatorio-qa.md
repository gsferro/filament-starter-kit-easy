# Relatório de QA — feat/diagramas-da-arquitetura: Diagramas da arquitetura do kit no README e no site

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo. O site renderiza os diagramas por JavaScript no cliente, e o `kit:install` mexe em credencial e no `.env`.
> Natureza da wiki: nova · Toca infra compartilhada: sim → README/`docs/`/`site/`, `kit:arte`, `kit:install` (`CustomizadorDaInstalacao`, `SenhaDoAdministrador`, `SubstituicaoEmArquivo`), `KitTenancy`, `AtivadorDeTenancy`, `tests/Pest.php`, `ci.yml` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis. G, J e K rodaram só em parte · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 3

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 2 · Cosmético: 0 novos. O QA-13 do ciclo 1 segue aberto como débito cosmético.
- Não verificadas: G (nível 3), J (parcial) e K (parcial). As causas estão em *Não Verificado*.
- `RQ` abertas, nenhuma com a direção implementada (só limitam o teto):
  - RQ-28 em parte: Q?10 → P-46 e Q?11 → P-47.
  - RQ-50 em parte: Q?14 → P-48, valor multilinha entre aspas.
  - A RQ-53 foi decidida por omissão ("continue", fidelidade baixa, declarada no Adendo 8).
- Ambiente:
  - App em `http://127.0.0.1:8000` (`/login` → 302). Pest 5 com `pest-plugin-mutate`, PCOV e Xdebug carregados.
  - Site construído (69 páginas) e servido em `astro preview :4399`, já encerrado.
  - Playwright MCP indisponível. Boost MCP não usado.
- Convergência: **ciclo 3 de 3, o teto da skill.** O ciclo trouxe 2 achados novos (QA-25 e QA-26) e reabriu um achado do ciclo 2 que estava dado como fechado (QA-21, agora QA-24). A skill manda parar e escalar ao mantenedor, sem ciclo 4.
  - Os três achados são de texto: wiki, CHANGELOG e um docblock.
  - Código e testes da feature: nenhum defeito novo. O delta de `app/` do ciclo 3 é um docblock.

## Achados

### QA-24 — O QA-21 foi dado como fechado, e o próprio exemplo dele continua errado · Minor · destino 1 (reaberto)
- **Dimensão**: L2/L6 · **Relacionado a**: QA-21 do ciclo 2, `03` `## 28.` (linha do QA-21)
- **Esperado**: citação `arquivo:símbolo:linha` que cai no teste citado. O `03:197` diz "64 citações … recitadas pelo ID … `citacoes.sh` vazio".
- **Observado**:
  - O `[CT-105]` continua citado como `tests/Kit/DiagramasDaArquiteturaTest.php:it:5597` em `01:87`, `01:1663`, `03:215`, `04:514` e `04:4436`. A linha 5597 é o `it('[CT-85]`, e o CT-105 está em `:6223`. É o exemplo que o QA-21 nomeava.
  - No total, 10 citações nomeiam um CT e caem no `it()` de outro. Por exemplo, `04:5702` (CT-129 → `:1462`, que é o CT-08), `04:5947` (CT-89 → CT-104), `04:6015` (CT-146 → CT-132), e `04:3617` e `04:6602` (CT-128 → `tests/Kit/KitArteTest.php`, `it`, linha 682 na data, que é o CT-65).
  - Outras 8 caem em linha sem `it(`. Quatro delas foram deslocadas pelo próprio delta do ciclo 3 (+2 linhas em `elementosOptInDeR58()`):
    - `04:6740`: CT-129 em `:1556`, que é uma linha de dataset (o `it` está em `:1560`).
    - `04:6741`: CT-130 em `:1594`, um `})->with(` (o `it` está em `:1596`).
    - `04:6737`: CT-126 em `:5918` (o `it` está em `:5921`).
    - `04:6753`: CT-142 em `:6017` (o `it` está em `:6020`).
    - As outras quatro: `04:1483` (`:2277`), `04:5320` (`tests/Kit/CustomizadorDaInstalacaoTest.php`, `it`, linha 933 na data), `04:5856` (`:5683`) e `04:6592` (`:948`).
  - O `citacoes.sh` sai com exit 0 porque aceita `it` como substring ("Substituicao", "with"). As 44 citações na forma `arquivo:'[CT-nnn]':linha` estão todas certas.
- **Repro**:
  1. `sed -n 5597p tests/Kit/DiagramasDaArquiteturaTest.php` → `it('[CT-85]…`
  2. O laço sobre `grep -nE "\.php:it:[0-9]+" {wiki}/0[0-5]*.md`: para cada citação, o ID `[CT-nn]` da linha da wiki × o ID do `sed -n "{n}p"` do teste. Mais `grep -qE "^\s*(it|test)\("` na linha citada.
- **Destino**: 1 · **Ação exigida**: recitar as 18 na forma `arquivo:'[CT-nnn]':linha`, a que já funciona nas outras 44. A nota para a `feature-wiki` continua valendo: o `citacoes.sh` deveria recusar `it` como símbolo.

### QA-25 — "Q?9 em aberto" sobrevive no PRD, na ADR e no docblock, depois do Adendo 8 · Minor · destino 1 (+2 no docblock)
- **Dimensão**: L3/L5 · **Relacionado a**: RQ-53, Adendo 8, P-45, passo 27, ADR-10
- **Esperado**: o Adendo 8 (RQ-53) fecha a Q?9. O `01:104` (Cobertura) e o `04` já dizem isso.
- **Observado**: três lugares ainda dizem "(P-45, Q?9 em aberto)":
  - `01:1765` (passo 27) e `02:665` (ADR-10), sem marca de alteração;
  - `app/Support/SubstituicaoEmArquivo.php`, docblock de `definirLinhaNoEnv`, linha 107 na data ("P-45 da mesma wiki, pergunta Q?9 em aberto"). O ciclo 3 editou esse mesmo docblock (`:111`, o `@return`) e deixou a frase.
- **Repro**: `grep -rn "Q?9" app/ {wiki}/01-plano-acao.md {wiki}/02-decisoes-arquiteturais.md`
- **Destino**: 1 (texto do `01` e do `02`, com a marca `*(alterado em …)*`) e 2 (o docblock). **Ação exigida**: trocar "Q?9 em aberto" por "RQ-53, Adendo 8" nos três lugares.

### QA-26 — Números escritos no fechamento que o comando ao lado não reproduz · Major · destino 1
- **Dimensão**: L6/L5 · **Relacionado a**: QA-16 do ciclo 2 (mesma classe), `## Testes` e `## Verificação Final` do `03`, CHANGELOG
- **Esperado**: todo número da wiki e das docs de usuário sai do comando citado (princípio 11, L6).
- **Observado**:
  - `03:234` `[x]`: "`grep -c "alterado em 2026-09-30"` dá … 01: 6". O mesmo comando dá **7** hoje. No `9eba4f2` dava 4, então nenhum commit reproduz o 6. É a linha que o QA-16 mandou refazer.
  - `03:206` `[x]`: "`tests/Kit/DiagramasDaArquiteturaTest.php` (**109 IDs**: CT-01 a CT-11, …)". O comando que o `03:205` declara (`grep -E "^\s*(it|test)\(\x27\[" … | grep -oE "\[CT-[0-9]+\]" | sort -V -u`) dá **106**, e as faixas listadas na própria linha também somam 106.
  - `CHANGELOG.md:42`: "`tests/Kit/DiagramasDaArquiteturaTest.php` (**525 casos**)". Medido agora: **527** (`{"tests":527,"passed":527,"assertions":3841}`). A cópia está em `03:242` ("as guardas com 525, 47 e 50 casos"). `grep -rn "525 casos\|525, 47"` acha só esses dois.
- **Repro**:
  1. `grep -c "alterado em 2026-09-30" {wiki}/01-plano-acao.md` → 7
  2. O comando de `03:205` sobre `tests/Kit/DiagramasDaArquiteturaTest.php` → 106
  3. `XDEBUG_MODE=off vendor/bin/pest tests/Kit/DiagramasDaArquiteturaTest.php --compact --no-tia` → 527
- **Destino**: 1 · **Ação exigida**: substituir pela saída real em `03:206`, `03:234`, `03:242` e `CHANGELOG.md:42`.

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|---|---|---|---|---|---|---|---|
| RQ-28 / P-46, P-47 | senha já definida ou gerada, sem administrador (Q?10, Q?11) | 1, 21, 22 | CT-134, CT-136 (invariante) | — | texto de hoje mantido | direção sem implementação | ⏳ teto |
| RQ-50 / P-48 | valor multilinha entre aspas não suportado (Q?14) | 27 | CT-117 (invariante: o kit nunca produz) | — | sem mudança | DV-12, L-21 | ⏳ teto |
| RQ-53 | sem linha ativa, descomenta a primeira comentada no lugar | 27 | CT-139, CT-151 | — | `definirLinhaNoEnv` (ramo `$comentada`) | ✅ 5/5; a decisão veio de "continue" (fidelidade baixa) | OK, confirmar no PR |

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; sem `07-tickets/`. Dedupe do ciclo 2: QA-15 fechado (RQ-53 no `00`, `01:104`, CT-151). QA-18 fechado: as duas linhas `accDescr` estão em `elementosOptInDeR58()`, e a prova vermelha está no `03` (#86). QA-19 virou P-48 (teto). A decisão por omissão do Adendo 8 foi vista e está declarada no `00`. |
| B | Fronteiras e dados | ✅ | o `app/` do ciclo 3 é só docblock (`git diff 9eba4f2...HEAD -- app`). As 10 sondas do ciclo 2 seguem válidas. As linhas do CT-151 cobrem sem `.env`, sem quebra no fim e duas comentadas. |
| C | Matriz de permissão | ✅ | nenhuma superfície nova de rota, policy ou Livewire |
| D | Observabilidade | ✅ | nenhum `Log::` novo. O md5 do `.env` foi o mesmo antes e depois de cada execução (`90b3e52f…`). |
| E | Performance | ✅ | nenhum request novo; build do site com 69 páginas em 13,8 s |
| F | UX de erro | ✅ | o delta não muda mensagem |
| G | Tema e cor | ⏭️ parcial | `dark-mode.sh --mecanismo` exit 1 (Filament e JS); nível 1 exit 0. No nível 2, pelo conferidor: CT-B02 claro e escuro, e CT-B05 com 14,0 px nas 7 combinações. Nível 3 sem MCP. |
| H | Acessibilidade | ✅ | `node verifica-acessibilidade.mjs 4399` exit 0: 96 páginas, nenhuma violação serious/critical. CT-B01 com 20/20 por idioma e tema; CT-B09 com 0 violações. As quatro `accDescr` novas renderizam. |
| I | Segurança | ✅ | `## Revisão do Diff (step 9)` já cobre o diff até o ciclo 2 (o eixo 9 não foi refeito). O `app/` do ciclo 3 é docblock: eixo 9 não se aplica. Sem mass assignment, upload nem `DB::raw` |
| J | Regressão | ⏭️ parcial | 6 arquivos, um por vez, todos verdes: Customizador 120/120 (519 asserções), Resumo 10/10 (127), Citacoes 3/3, HelpersDeTeste 1/1, SiteDeDocumentacao 68/68 (badge 1.836), Diagramas 527/527 (3.841, 173 s). A suíte completa e o `--tia` não rodaram. |
| K | Adequação da suíte | ⏭️ parcial | K1 `k1-oraculo-fraco.sh` exit 1, com os mesmos 5 candidatos rejeitados no ciclo 2 e nenhum no delta. K2 pelo arnês `auto_prepend_file`, sobre `SubstituicaoEmArquivo` com o Customizador: **82 mutantes, 92,68 %, `Duration` 254,81 s** (cerca de 3,1 s por mutante contra 23,6 s da suíte, plausível); veja o detalhe abaixo da tabela. Revisão adversarial da adição do ciclo 3: não disparada (teto de 2 rodadas; `04:136`). |
| L | Consistência documental | ❌ | QA-24, QA-25, QA-26. Detalhe por checagem abaixo da tabela. |

Detalhe da dimensão K (K2):

- Não sobreviveu nenhum dos 5 mutantes do QA-20.
- Ficam 6 não testados: `:125` e `:133` `RemoveStringCast`, equivalentes e declarados; e `aplicar()`, com `:60` `RemoveStringCast` e `IncrementInteger` e `:62` `TrueToFalse` e `RemoveEarlyReturn`.
- A `TenancyNaInstalacaoTest` não gera mutante em `SubstituicaoEmArquivo`: "0 Mutations".

Detalhe da dimensão L:

- L1: `ids-ct.sh` (8 arquivos) exit 1, só o CT-39 (costura `diff`). O `git diff --name-only` de `composer.*` e `package*.json` vem vazio. A contagem do `04` dá 151 · 65 · 434 · 5 e a do `05` dá 11, que batem com o cabeçalho.
- L2: `citacoes.sh` exit 0, vacuidade descrita no QA-24.
- L4: `conformidade-rules.sh` exit 0. A `testes.md` agora é cumprida: `grep -rn "mb_strtolower(Str::ascii" tests/` só acha `tests/Pest.php:semAcentoESemCaixa:2398`.
- L6: `checkbox-sem-evidencia.sh` exit 0; os números estão no QA-26. Reproduzidos: 120/120, 10/10, 527/527, 68/68, 3/3, 92,68 %, 151 CT, 53 RQ e 48 P-nn, `115.928` ausente, 04 com 181 e 131 marcas, 05 com 24.
- L7: glossário com os três termos do QA-23.

## Débitos Aceitos

- QA-13 (Cosmético, do ciclo 1, parcial): quadros 1 a 3 do `install.gif` com área vazia, e o `WARN` no último. É decisão do mantenedor e já está no `03`.
- DV-01..DV-08, DV-11 e DV-12, como no `03`.

## Suspeitas Não Confirmadas

- `aplicar()` aparece como **UNTESTED** no `--mutate` do Customizador, embora `tests/Kit/CustomizadorDaInstalacaoTest.php:'teams':280` afirme `'teams' => true` no `config/permission.php` temporário. Ou esse caminho não passa por `AtivadorDeTenancy::ligarPapeisPorTenant()` no processo medido, ou a cobertura não o atribui. Não investiguei além disso.
- RQ-53 nasceu de "continue", sem escolha de opção. A sessão declarou isso com fidelidade baixa e com o "Se negado". Não é defeito, mas a confirmação explícita cabe no PR.

## Não Verificado

- G nível 3 (olho nos dois temas). Motivo: Playwright MCP indisponível; só as medidas do conferidor.
- J: a suíte completa e `pest --parallel --tia`. Motivo: memória da máquina e a instrução de não rodar a suíte completa nem usar `--parallel`. A decisão do mantenedor está no `03:228`. O `03` alega 3.796 testes e 0 falhas de antes do ciclo 3; não reproduzi.
- K2 de `aplicar()` (`:60`, `:62`), `CustomizadorDaInstalacao`, `KitInstall`, `KitArte`, `AtivadorDeTenancy` e `KitTenancy`. Motivo: nenhum desses mudou no ciclo 3, e o ciclo 1 mediu parte deles. `aplicar()` sai "UNTESTED" nas duas suítes tentadas. Tempo e memória.
- K: revisão adversarial da adição do ciclo 3 (CT-151 e as linhas do CT-129). Motivo: teto de 2 rodadas da `feature-test-design` atingido (`04:136`).
- L-03 (render no GitHub) e o job `site` num PR. Motivo: não existe PR.
- `--agent`: `pest-plugin-agent` não instalado. `qa-skills`: fallback em linha.
