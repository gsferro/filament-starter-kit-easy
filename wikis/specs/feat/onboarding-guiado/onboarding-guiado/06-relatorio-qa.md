# Relatório de QA — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: checklist Livewire e tour Alpine)
> Natureza da wiki: nova · Toca infra compartilhada: sim → três `PanelProvider`, `KitInstall`, `KitUpdate`, `KitServiceProvider`, settings do kit · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 11 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 3

**REPROVADO → especificação**

- Contagem: Blocker 0 · Major 1 · Minor 3 · Cosmético 0.
- Destino de cada Blocker/Major: o único é o QA-18 (Major), destino 1. O defeito é só de documentação. O comportamento do app passou em todas as checagens deste ciclo.
- Não verificada: K. O mutation score não é certificável no Windows (ver *Não Verificado*).
- `RQ` abertas: nenhuma. As perguntas Q11–Q17 e Q21–Q28 continuam com o solicitante e sustentam as premissas P-04..P-19, forma admitida pela skill.
- Ambiente:
  - app em `http://127.0.0.1:8011`, o kit confirmado, com SQLite descartável;
  - Pest 5.0.5 e PHP com PCOV e Xdebug;
  - Playwright MCP indisponível. Usei o Playwright do `node_modules` direto, com o axe do `pest-plugin-browser`.
- Convergência: este é o ciclo 3 de 3 e trouxe achados novos (QA-15..18). Pelo teto, o caso vai ao usuário.

## Achados

### QA-18 — A wiki ficou com números e uma linha de log anteriores à correção do ciclo 2 · Major · destino 1

- **Dimensão**: L (L6 e L3)
- **Relacionado a**: `03` (`## Testes` e `## Verificação Final`), `02` (abertura), passo 3 do `01` (`**Logs**`), QA-10, QA-14
- **Esperado**: todo número de um `[x]` e de uma tabela derivada por comando é reproduzido no HEAD. O PRD e a ADR dizem o que o código faz.
- **Observado**:
  - `03-progresso.md:46` diz "71 CT + 2 CT-B, igual ao `04`/`05`". A linha 51 dá `OnboardingGuiadoConsumoTest.php` com 19 CT e sem CT-72..75. Real: 75 CT no `04`; 77 IDs nos arquivos (75 CT + 2 CT-B); 23 CT no arquivo de consumo.
  - `03-progresso.md:66` sustenta "nada mais quebrou" com a suíte de antes do código do ciclo 2 (`tests 5321 · passed 5318`). No HEAD dá `tests 5333 · passed 5330 · skipped 3 · risky 2`. A afirmação continua verdadeira; o número, não.
  - `03-progresso.md:42` diz "badge 2.387", mas o `README.md` mostra `casos%20de%20teste-2.391` (mudado no `10b422b1`).
  - `02-decisoes-arquiteturais.md:4` diz "D1 a D13, mais D7b", mas o `01` já tem D14 e D15. É a mesma defasagem do QA-14, reaberta pelo ciclo 2.
  - A linha de log do `updatedFlowKey` no `01` (linha 292) mostra o context `['user_id' => …, 'motivo' => …]`. O código grava também `'flow' => Str::limit(preg_replace(...), 80)` (`app/Livewire/ChecklistDoOnboarding.php:preg_replace:72`).
- **Repro**:
  1. `grep -o "\[CT-B\?[0-9]*\]" tests/Kit/OnboardingGuiado*.php tests/Tenancy/OnboardingGuiadoTenancyTest.php tests/Browser/OnboardingGuiadoTest.php | sed 's/.*://' | sort -u | wc -l` → 77.
  2. `vendor/bin/pest --parallel --processes=4 --exclude-testsuite=Browser` → 5333.
  3. `grep -o "casos%20de%20teste-[0-9.]*" README.md` → 2.391.
  4. `grep -oE "^\| D[0-9]+b? " 01-plano-acao.md` → … D14, D15.
- **Evidência**:
  - `grep -rn "5321\|2\.387\|71 CT\|(19)" wikis/specs/feat/onboarding-guiado/onboarding-guiado/` só acha o `03` (linhas 42, 46, 51, 66) e o `06` do ciclo 2, que este substitui.
  - `grep -rn "D1 a D1"` só acha `02:4`.
- **Ação exigida**:
  - Recontar pelos comandos acima e substituir a saída em `03:42`, `03:46`, `03:51` e `03:66`.
  - Trocar para "D1 a D15, mais D7b" no `02:4`.
  - Acrescentar `'flow'` (sanitizado e truncado) ao context da linha de log do `01:292`, com a marca de data.

### QA-15 — Os botões primários do checklist e da página "Primeiros passos" não atingem o contraste AA · Minor · destino 3

- **Dimensão**: H (e G, nos dois temas)
- **Relacionado a**: RQ-28, CT-B01, CT-B02
- **Esperado**: a UI que a feature traz para os três painéis passa o axe nível *serious*, como as telas do kit sem o tutorial.
- **Observado**:
  - O `color-contrast` (*serious*) dá 3,2:1, com `#ffffff` sobre `#e17100` em 12 px. Atinge "Iniciar tour", o status "A seguir" e o botão da jornada.
  - Contagem de elementos nos dois temas: página do `/admin` com 13 no tema claro e 11 no escuro; página do `/infra` com 17 e 15; página do `/app` com 7 e 6; checklist aberto com 3 em cada painel.
  - Linha de base com o mesmo axe e a mesma sessão: `/admin/users`, `/admin/dashboard-dinamico` e `/admin/configuracoes-da-aplicacao` dão 0 em `color-contrast` nos dois temas. Os dashboards com o checklist fechado dão 0.
  - Causa: o pacote usa `var(--primary-600)` com texto branco (`vendor/wallacemartinss/filament-onboarding/resources/css/onboarding.css:fio-button--primary:413`).
- **Repro**:
  1. Rodar `node scratchpad/g3pw2.cjs` (cookies das sessões já logadas, tema por `localStorage.theme` e `colorScheme`).
  2. Rodar o `axe.run` com `wcag2a/aa/21a/21aa` em `.fi-main` (página) e em `.fio-launcher` (checklist aberto).
- **Ação exigida**: invocar a `feature-test-design` para um CT-B que abre a página e o checklist e chama `assertNoAccessibilityIssues()`. Ele tem de falhar primeiro. Depois, sobrescrever a cor do `.fio-button--primary` no tema do kit.

### QA-16 — O checklist (`role="dialog"`) não fecha com Esc · Minor · destino 3

- **Dimensão**: H
- **Relacionado a**: RQ-28
- **Observado**: o foco chega ao botão do checklist por Tab nos três painéis, e Enter o abre. O foco fica no botão, e Esc não fecha o painel (`resources/views/vendor/filament-onboarding/livewire/launcher.blade.php:dialog:24`, sem handler de `escape` na view). Fechar exige chegar ao "×" por teclado.
- **Repro**: o mesmo script do QA-15. Medi `tabAteLauncher: true`, `painelAberto: true` e `abertoDepoisDeEsc: true` em seis combinações (3 painéis × 2 temas).
- **Ação exigida**: decidir se a feature quer Esc como saída do checklist (`01`, Superfície de UI). Se quiser, um CT-B primeiro e depois o `x-on:keydown.escape` na view publicada.

### QA-17 — O CT-75 deixa passar uma sanitização que só troca `\n` · Minor · destino 3

- **Dimensão**: K (K2)
- **Relacionado a**: P-26, CT-75, M205
- **Esperado**: o `04` diz que o M205 ("só `\n`: sobra o `\r` da linha 2") morre no CT-75 (linha 2).
- **Observado**:
  - O mutante K5 troca `preg_replace('/\s+/', …)` por `preg_replace('/\n/', …)` e sobrevive: 5/5 verdes.
  - Com ele, o `\r` vai escapado no JSON do context, e o Monolog o devolve como `\n` real (`LineFormatter::replaceNewlines`). O arquivo ganha uma linha `" [2026-10-21 23:59:59] local.CRITICAL: linha forjada"`, que a P-26 proíbe.
  - O oráculo só reconhece entrada que começa com `[` na coluna 0 (`tests/Kit/OnboardingGuiadoConsumoTest.php:preg_match:888`). O espaço inserido pela troca faz a linha forjada escapar.
  - O código atual está correto (`\s+`): a lacuna é do teste.
- **Repro**: `bash scratchpad/g3roda.sh 'CT-7[345]' K0 K5`. O K0 (sem mutação) e o K5 dão os dois 5/5 em cerca de 5 s. O K4 (sem sanitizar), pelo mesmo arnês, falha em 2 de 3 linhas, o que prova que a classe mutada carrega.
- **Ação exigida**: passar o mutante pela `feature-test-design`. O CT-75 deve contar as linhas físicas depois da sentinela (exatamente 1), e não só as que começam com `[`. Corrigir também a coluna de matador do M205.

## Ciclos anteriores (histórico)

| ID | Sev. | Situação no ciclo 3 |
|---|---|---|
| QA-01..07 | — | corrigidos no ciclo 1 (ver o `03`) |
| QA-08 | Blocker | Corrigido. `/{admin,infra,app}/onboarding` respondem 200 com passo concluído ("Concluída há 15 horas"), e o CT-72 está verde. A RM-05 rodou inteira. |
| QA-09 | Major | Corrigido. Com 10 passos extras `tela:usuarios`, as consultas de papel ficam em 33, com ou sem os passos (eram 65). O mutante K1 (memória estática) morre no CT-74, e o K2 (sem memória) no CT-73. |
| QA-10 | Major | Corrigido no `hydrate`. A linha do `updatedFlowKey` ficou sem o `flow` (QA-18). |
| QA-11 | Minor | Corrigido. Pelo app, um POST com LF, CRLF, U+2028 e U+0085 gera 1 linha e `flowKey` volta a nulo. O CT-75 mata o K4, mas não o K5 (QA-17). |
| QA-12, QA-13 | Minor | Corrigidos. O CT-16 afirma o valor exato (11/5/15). A preparação 4 do `08` reproduz 11 no `/admin`, 15 no `/infra` e 10→11 na RM-06. |
| QA-14 | Minor | Corrigido no que apontava; a contagem de decisões do `02` defasou de novo (QA-18). |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; `indice.sh --check` n/a (sem `07-tickets/`). P-25 e P-26 com CT. `git diff 5f66c820 10b422b1` faz o que os passos 3/4 dizem. |
| B | Fronteiras e dados | ✅ | O código novo só recebe o `flowKey`. Testei LF, CRLF, U+2028, NEL e 80+ caracteres pelo POST Livewire: 1 linha, truncada, e `flowKey` nulo. |
| C | Matriz de permissão | ✅ | Ligado: página com 403 para `infra` sem `View:PrimeirosPassos` (RM-08); passo do dashboard dinâmico 11→10 sem `Manage:Dashboard` (RM-09). Desligado: 404 para todos, inclusive o `master_global` na autoria (RM-07). |
| D | Observabilidade | ✅ | `onboarding-2026-10-22.log` com 64 debug (2 × 32 do seeder), 2 info e 9 warning, todos com `[Classe@Método]` e context. `grep -ciE "@example\|password\|token"` → 0. |
| E | Performance | ✅ | O mesmo GET dá 51 consultas no total, 33 delas de papel, com ou sem +10 passos `tela:usuarios`. O 1º GET depois da mudança custa +3, para aquecer o cache do pacote. |
| F | UX de erro | ✅ | 404 e 403 sem a página no corpo. Nada mudou neste ciclo. |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0. Nível 3 com o Playwright direto: página e checklist nos três painéis em claro e escuro, sem texto ilegível nem invisível. O contraste é o QA-15 (igual nos dois temas). Não há CT-B `inDarkMode()` (nível 2). |
| H | Acessibilidade | ⚠️ | QA-15 e QA-16. Axe 4.x do `pest-plugin-browser` na página e no checklist aberto. Teclado: Tab alcança o checklist, Enter abre, Esc não fecha. |
| I | Segurança da superfície nova | ✅ | O step 9 cobriu (25 linhas, 5 rejeitadas). Além dele: injeção no log refeita (QA-11 corrigido); `finishTour` no checklist já aberto depois de desligar → 404, com o progresso em 4→4 linhas; memória por request e por pessoa (o K1 morre). |
| J | Regressão adjacente | ✅ | Suíte `--parallel --processes=4` sem browser: 5333 testes, 5330 ok, 3 pulados, 2 risky (921 s). `--filter=Onboarding`: 305/305, 3206 asserções. `tests/Browser/OnboardingGuiadoTest.php` 2/2 em série. RCRCRC (Configuration): a pasta `filament-onboarding` em `CAMINHOS_DO_KIT` chega pela lista do destino (o `KitUpdateTest` está na suíte). |
| K | Adequação da suíte | ⏭️ | Revisão adversarial do `04`: concluída. K1 `k1-oraculo-fraco.sh` exit 1: os mesmos 8 candidatos, todos já presentes no `main` (rejeitados). K2 manual por `auto_prepend_file` com cópia no scratchpad, nada na árvore: K1, K2 e K4 morrem; o K5 sobrevive (QA-17). Cada rodada filtrada (5 testes) leva cerca de 5 s, o que bate com a baseline K0. Não há score (ver *Não Verificado*). |
| L | Consistência documental | ❌ | QA-18. L1 `ids-ct.sh` exit 0 (cabeçalho 75 · 35 · 204 · 0 reproduzido). L2 `citacoes.sh` exit 1: 4 linhas, todas no `06` do ciclo 2, que este substitui. L4 `conformidade-rules.sh` exit 0 (`vendor.md`: view autoral com a pasta na lista; `views.md`: comentário sem `@`). L6 `checkbox-sem-evidencia.sh` exit 0; reproduzidos 305, 29, 3, 6, 17 rules do filacheck e o pint ok; irreproduzíveis em QA-18. L7 sem divergência. L8 `roteiro-manual.sh` exit 0. RM executados, todos de acordo com o esperado: 01 (terminal), 02 (save Livewire da tela), 03, 04 (Playwright: popover "Todos os usuários", sem erro de JS), 05, 06, 07, 08, 09. |

## Débitos Aceitos

- Nenhum: o veredito é REPROVADO. Os Minor QA-15, QA-16 e QA-17 viram débito se o usuário aprovar com o QA-18 corrigido.

## Suspeitas Não Confirmadas

- Rejeitado: o login do `master_global` respondeu 500 uma vez, com corpo vazio e nada no `laravel.log`, enquanto a suíte rodava em paralelo. Na repetição deu 200. É carga do ambiente, não a feature.
- Rejeitado: o 404 do update Livewire traz `exception` e `file` no JSON. É `APP_DEBUG` local (destino 4).
- Rejeitado: os 8 candidatos do K1 (`TelasDoKitTest`, `PermissoesDeResourcesTest`, `QualidadeDeCodigoTest`) já existiam no `main`.

## Não Verificado

- **K2, score**: o `--mutate` do plugin dá morte falsa no Windows (provado no ciclo 2). Por isso só há a amostra manual: 6 mutantes no ciclo 2 e 4 neste, mais a baseline K0. Não há score para comparar ao piso de 70 %.
- **G, nível 2**: não existe CT-B em `inDarkMode()`. O nível 3 rodou por Playwright direto, porque o Playwright MCP está indisponível.
