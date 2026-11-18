# Relatório de QA — Roadmap 8.2: `composer-require-checker` + `composer-unused`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão (natureza `nova`, sem UI, domínio comum; o Mínimo vale só para `ajuste`)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `composer.json` (`require-dev` e scripts), job `qualidade` do CI, `KitUpdate::CAMINHOS_DO_KIT` · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 11 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 2 · Cosmético: 0
- Não verificadas: K (o K2 fica fora do perfil padrão). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Q3 (pré-requisito `ext-sodium` e PHP 8.4–8.5) está aberta ao solicitante, com a P-08 vigente até a resposta. É a forma prescrita (premissa e pergunta) e vale como item de teto, não como achado
- Teto do loop: o perfil padrão admite **até 2 ciclos**. Este é o segundo e reprova, então o caso **escala ao usuário** com os achados abertos
- Ambiente: app não servido (a feature é ferramental) · Pest 5 (`vendor/bin/pest`) · MCP: Boost com connect timeout; Playwright não usado (sem UI)

## Ciclo 1 — o que foi feito com cada achado (conferido neste ciclo)

| Achado | Estado no ciclo 2 | Evidência |
|---|---|---|
| QA-01 `livewire/blaze` excluído sem uso | fechado no código e na triagem; restam cópias em `04` (ver QA-08) | `composer-unused.php` com 4 `NamedFilter`; `vendor/bin/composer-unused --no-progress --ignore-exit-code` lista 4 sem uso (inclusive `livewire/blaze`) e 4 ignorados |
| QA-02 pré-requisito sem `P-nn` | parcial: P-08 e Q3 registradas, `01` §Impacto marcado; o "PHP 8.4+" do README não foi alinhado (ver QA-07) | `00` P-08 e Q3; `README.md:331`, `README.en.md:332` |
| QA-03 CT-04 do `04` defasado | parcial: Gherkin e `Exemplos` reescritos; o Setup Global, a taxonomia e o Índice continuam no texto antigo (ver QA-08) | `04:206-219` × `04:103`, `:371`, `:409` |
| QA-04 `[x]` PHPStan sem execução | fechado | `03:33`; reproduzido: `{"tool":"phpstan","result":"passed","errors":0}` (1,6 s, com cache de resultado) |
| QA-05 contagens do `04` | fechado | cabeçalho `9 · 8 · 26 · 0` igual aos quatro `grep -c` do `template-04` |

## Achados

### QA-06 — "171 asserções" no `03` não se reproduz: hoje são 167 · Major · destino 1

- **Dimensão**: L6
- **Relacionado a**: `03-progresso.md:26` (`## Testes`), CT-06
- **Esperado**: todo número de um `[x]` sai do comando que o gera
- **Observado**: o `03` diz "12/12, 171 asserções, ~4,4 s". O CT-06 itera sobre os pacotes excluídos, e a lista caiu de 5 para 4 com o QA-01. A contagem real passou a 167. A linha não foi atualizada depois da correção do ciclo 1
- **Repro**: `vendor/bin/pest tests/Kit/VerificacaoDeDependenciasTest.php` (duas execuções) → `{"tests":12,"passed":12,"assertions":167,...}`
- **Evidência**: `grep -rn "171\|167" wikis/specs/feat/verificacao-de-dependencias/` → só `03-progresso.md:26` (e o `06` do ciclo 1, que será sobrescrito). Na mesma linha, "7 de 10 vermelhos" fala de 10 testes, e hoje são 12 (ver *Não Verificado*)
- **Destino**: 1
- **Ação exigida**: trocar o número pela saída real do comando no `03:26`, que é a única cópia, e datar a medição da falsificabilidade ("7 de 10", anterior ao CT-09 e aos datasets do CT-04) ou refazê-la

### QA-07 — P-08 entregue em parte, e o CT-01 aparece como cobertura dela sem afirmar nada sobre ela · Minor · destino 3, depois 2

- **Dimensão**: A (P-08) e L5
- **Relacionado a**: P-08, Q3, R1/CT-01, `01` §Cobertura do Requisito
- **Esperado**: o texto da P-08 diz "exigir a extensão PHP `sodium` **e PHP 8.4 ou 8.5**, documentado no README, no CHANGELOG, na página de atualização e no roteiro". O roteamento da `feature-wiki` (step 9/11) é `P-nn` → CT com `Origem` = `P-nn` → correção
- **Observado**:
  1. A faixa 8.4–8.5 só aparece no `CHANGELOG.md:29`. O README pt/en ainda diz "PHP 8.4+". `docs/{pt,en}/comecar/atualizando-o-projeto.md` e `wikis/atualizacoes/v0.55.0.md`, com a cópia em `wikis/agentes-e-skills.md:368`, falam só do `sodium`. O lock confirma a faixa: `maglnet/composer-require-checker` 4.24.0 pede `php: ~8.4.0 || ~8.5.0`
  2. O Mapa de Regras do `04` põe a P-08 na origem da R1, e por isso o `rastreabilidade.sh` sai com exit 0. Mas o Gherkin e o teste do CT-01 conferem só a seção `require-dev`/`packages-dev`. Nenhum teste cita `sodium` nem a faixa de PHP
  3. A lista "coberta por" do `04` (`:47-58`) e o §Cobertura do Requisito do `01` não têm linha para P-07 nem para P-08
- **Repro**:
  1. `grep -n "8\.5" docs/pt/comecar/atualizando-o-projeto.md docs/en/comecar/atualizando-o-projeto.md wikis/atualizacoes/v0.55.0.md README.md README.en.md` → nenhuma linha com a faixa
  2. `grep -n "sodium\|8\.5" tests/Kit/*.php` → nenhuma linha da feature
  3. `php -r` sobre o `composer.lock` (`packages-dev`) → `php ~8.4.0 || ~8.5.0`; `php-standard-library` com `ext-sodium`
- **Destino**: 3, depois 2
- **Ação exigida**: pela `feature-test-design`, há duas saídas. A primeira é derivar um CT com `Origem` = P-08, por exemplo "as páginas de requisito/atualização citam `ext-sodium` enquanto o lock a exigir". A segunda é declarar a P-08 como lacuna no `04`, como já foi feito com P-04/P-05, e tirá-la da origem da R1. Depois, alinhar o "PHP 8.4+" do README pt/en, as duas páginas `atualizando-o-projeto` e o roteiro (com a cópia em `agentes-e-skills.md`) à faixa da instalação com dev, e acrescentar as linhas P-07/P-08 no `01` §Cobertura e no `04`

### QA-08 — Sobras do CT-04 antigo e da lista de 5 nas afirmações vivas do `04`; um `Então` só no teste · Minor · destino 3

- **Dimensão**: L1 e L3
- **Relacionado a**: CT-04, CT-06, CT-07, ADV-10; QA-01 e QA-03 do ciclo 1 (o `03:71` diz "em todas as cópias")
- **Observado**. Estas afirmações vivas continuam como antes da correção:
  - `04:103` §Fakes diz "Nenhum. O CT-04 roda processo real…". O CT-04 substitui as duas ferramentas por binários falsos (`VerificacaoDeDependenciasTest.php`, dataset do CT-04)
  - `04:371` (taxonomia) diz "CT-04 — o `Dado` mede que há achado… nunca mundo vazio". Esse é o `Dado` antigo
  - `04:409` (Índice) mantém o título antigo do CT-04 ("com achado, …"), sem "ou ferramenta quebrada"
  - `04:79` (Fronteira) fala em "os cinco pacotes excluídos". Hoje são quatro
  - O ADV-10 manda o CT-06 **e o CT-07** afirmarem "evidência não é só marcador". O teste afirma isso no CT-07 (`VerificacaoDeDependenciasTest.php`, `it('[CT-07]…')`, terceiro `expect`), mas o Gherkin do CT-07 (`04:303-309`) não tem o `Então`. A asserção fica sem cenário

  Ficam de fora, como registros datados: ADV-11 (`04:389`, "3 sem uso"), ADV-14 (`04:392`, "três pacotes sem uso") e o Despacho 1 do `03` ("5… 3 sem uso"), que relatam o que foi medido ou devolvido naquele momento.
- **Repro**: `sed -n '79p;103p;303,309p;371p;409p' wikis/specs/feat/verificacao-de-dependencias/verificacao-de-dependencias/04-casos-de-teste.md` e, para comparar, o `it('[CT-07]…')` do teste
- **Destino**: 3 (o `04` é o oráculo e muda pela `feature-test-design`)
- **Ação exigida**: alinhar `04:79`, `:103`, `:371` e `:409` à fixture de ferramentas falsas e à lista de 4, e acrescentar ao Gherkin do CT-07 o `Então` do ADV-10. Os registros datados ficam como estão

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| P-08 | pré-requisito `ext-sodium` e PHP 8.4–8.5 documentado | — (só §Impacto) | CT-01 (nominal) | — | README pt/en, CHANGELOG, atualizando, roteiro | `sodium` documentado; faixa só no CHANGELOG; CT não afirma | ⚠️ QA-07 |
| P-07 | `deps:check` fora do `composer test` | — (sem linha no §Cobertura) | CT-09 | — | `composer.json` `scripts.test` | ✅ | OK (falta a linha no `01`, QA-07) |

As demais linhas (RQ-01..06, P-01..06) estão OK. `rastreabilidade.sh` exit 0; sem `07-tickets/`. O `require` tem o mesmo md5 que na `main` (`3f9f3204…`), então a P-05 se mantém. `composer-require-checker`: exit 1 com "181 unknown symbols", reproduzido.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-07. `rastreabilidade.sh` exit 0 (a P-08 passa por mapeamento nominal); `indice.sh --check`: sem `07-tickets/`. `git diff` por passo conferido. Ferramentas reais: 4 sem uso, 4 ignorados, 181 símbolos |
| B | Fronteiras e dados | n/a | sem entrada de usuário: o `01` §Rotas/§UI/§Autorização diz "Nenhuma"; o diff não toca rota, request, model nem view |
| C | Matriz de permissão | n/a | nenhuma policy, rota ou permission no diff |
| D | Observabilidade | ✅ | o `01` declara channel "Nenhum"; nenhum `Log::` novo; o resumo do CI traz só nome de pacote e símbolo, sem PII |
| E | Performance | ✅ | `composer-unused` 1,5 s, `composer-require-checker` ~8 s locais |
| F | UX de erro | ✅ | o `} 2>&1 \| tee` leva o erro ao resumo; os 3 datasets do CT-04 passam |
| G | Tema e cor | n/a | sem UI no `01` nem no diff. `dark-mode.sh --mecanismo` exit 1 (o projeto tem dark mode); nível 1 sobre os arquivos AM exit 0 |
| H | Acessibilidade | n/a | sem UI no `01` nem no diff |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9 (`## Revisão do Diff (step 9)` do `03`: 18 linhas, 6 rejeitadas com motivo). Além do step 9: CI em `push`/`pull_request` (sem `pull_request_target`), o passo não usa `secrets`, nenhuma rota nova, nenhum `DB::raw`, dependências públicas e só de dev. O Docker de produção instala com `--no-dev`, então a P-08 não o afeta |
| J | Regressão adjacente | ✅ | desde o ciclo 1 nenhum arquivo de `app/` mudou (`git show --stat c2908360`). Suíte Kit, onde vivem os consumidores da infra (`QualidadeDeCodigoTest`, `CandidatasAPacoteTest`, `RoteiroDeAtualizacaoTest`, `ChecklistDeReleaseTest`): `pest --testsuite=Kit --parallel --processes=3` → 5389 testes, 5386 passaram, 3 pulados, 0 falhas (17 min 52 s). O run completo `--tia` do ciclo 1 (6194/6191/3) não foi repetido |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0. K2 fora do perfil padrão. Revisão adversarial do `04`: feita (Rodada 1, 15 achados). Limite: o CT-01 não afirma a P-08 (QA-07) |
| L | Consistência documental | ❌ | QA-06 (L6), QA-07 (L5), QA-08 (L1/L3). `ids-ct.sh` exit 0, `citacoes.sh` exit 0, `conformidade-rules.sh` exit 0, `checkbox-sem-evidencia.sh` exit 0, `roteiro-manual.sh` exit 0. L8 n/a, sem `08` (`Roteiro manual: não` no `03`). L4: 7 rules casadas, todas com linha, nenhuma violada; `pint --test` nos PHP do diff passou. L6: PHPStan reproduzido; cabeçalho do `04` reproduzido (9/8/26/0); "4 sem uso e 181 símbolos" reproduzido; `RoteiroDeAtualizacaoTest` verde na suíte Kit; "171 asserções" não reproduzido (QA-06). L7: nenhum termo novo em conflito com `wikis/glossario.md` |

## Débitos Aceitos

- nenhum (veredito reprovado)

## Suspeitas Não Confirmadas

- O `setup-php` do CI (passo "Configurar PHP" do job `qualidade` em `.github/workflows/ci.yml`) não lista `sodium` em `extensions`. A suposição é que a build padrão do runner a inclua. A branch não foi publicada (`git ls-remote` vazio), e sem run não há como confirmar
- O `01` §Modelo de Execução estima "~1 min" a mais no job `qualidade`. Localmente as duas ferramentas somam ~10 s
- Fora do escopo do diff: a citação da constante `CAMINHOS_SO_RELATORIO` de `app/Console/Commands/KitUpdate.php` em `tests/Kit/ConstraintDeDependenciaTest.php` e a do método `revisarEAplicar` em `wikis/checklist-de-release.md` já estavam com a linha errada na `main`. Não são achado desta feature

## Não Verificado

- K2 (mutação): fora do perfil padrão. O driver existe (`php -m` lista `pcov` e `xdebug`)
- Execução completa `pest --tia --parallel --processes=3 --exclude-testsuite=Browser` neste ciclo: só a suíte Kit foi repetida, porque o ciclo 1 não mexeu em `app/`. O número "6194/6191/3" do `03:35` vem do run do ciclo 1, não deste
- "Com a entrega revertida, 7 de 10 vermelhos" (`03:26`): reproduzir exigiria alterar a árvore, o que este gate não pode fazer
- Execução real do job `qualidade` no runner do GitHub: a branch não foi publicada
- Boost MCP: connect timeout na sessão; o gate usou `php`/grep e leitura direta
