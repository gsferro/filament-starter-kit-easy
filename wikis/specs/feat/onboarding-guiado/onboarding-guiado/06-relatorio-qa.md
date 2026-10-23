# Relatório de QA — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: checklist Livewire e tour Alpine)
> Natureza da wiki: nova · Toca infra compartilhada: sim → três `PanelProvider`, `KitInstall`, `KitUpdate`, `KitServiceProvider`, settings do kit · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 11 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO
> Ciclo 4, além do teto de 3. Foi autorizado explicitamente pelo usuário (gsferro) em 2026-10-22, depois do ciclo 3. O ciclo 3 está na seção `## Quality Gate` do `03`.

## Veredito — Ciclo 4

**APROVADO COM DÉBITO**

- Contagem: Blocker 0 · Major 0 · Minor 3 (QA-19, QA-20, QA-21) · Cosmético 0.
- Não verificada: K. O mutation score não é certificável no Windows, e a revisão adversarial dos CT-B03 e CT-B04 não foi feita (ver *Não Verificado*).
- `RQ` abertas: nenhuma. As perguntas Q11–Q17 e Q21–Q34 seguem com o solicitante e sustentam premissas vigentes, que é a forma admitida.
- Ambiente:
  - app em `http://127.0.0.1:8011`, o kit, com o SQLite descartável do gate;
  - Pest 5.0.5, com PCOV e Xdebug carregados;
  - Playwright MCP indisponível. Usei o `node_modules/playwright` direto, com o axe do `pest-plugin-browser`.
- Convergência: este ciclo trouxe 3 achados novos, todos Minor. O loop não convergiu. Pelo teto, a decisão volta ao usuário: aceitar como débito ou autorizar a correção.
- QA-15..18 do ciclo 3: corrigidos (ver *Ciclos anteriores*). QA-19 é um resíduo da P-27, que a correção do QA-15 não alcançou.

## Achados

### QA-19 — O rótulo "Concluída" da página "Primeiros passos" não atinge o contraste AA no tema claro · Minor · destino 3 → 2

- **Dimensão**: H (e G)
- **Relacionado a**: P-27, RQ-28, R36, CT-B03, MB10
- **Esperado**: a P-27 diz que os botões e **os rótulos de status** do checklist e da página têm contraste AA nos dois temas.
- **Observado**:
  - O axe (`color-contrast`, *serious*) acusa `.fio-tile--done .fio-tile-status` ("CONCLUÍDA"): `#00a63e` sobre `#ffffff`, 10 px em negrito, **3,24:1**.
  - Acontece no `/admin/onboarding` e no `/infra/onboarding`, com as personas que têm passo concluído (`qa-admin`, `qa-infra`). No tema escuro dá 0. No `/app` também dá 0, mas a persona `qa-ana` não tem passo concluído.
  - Causa: o pacote pinta o status concluído com `var(--fio-success)`, que vale `success-600` no claro (`vendor/.../onboarding.css`, linhas 34 e 758). O `kit.css` (linhas 416–420) sobrescreve só o destaque, o texto secundário e o botão primário.
  - O CT-B03 fica verde porque a persona dele não tem passo concluído. É o que o próprio MB10 declara: "persona sem passo concluído".
  - Três textos dizem que o contraste está todo resolvido:
    - `CHANGELOG.md`: "passou ao AA nos dois temas";
    - `05`, linha 236 da tabela Desenhado × Implementado (✅);
    - `01`, Superfície de UI ("leitura (AA, P-27)").
- **Repro**:
  1. Rodar `node scratchpad/g4pw.cjs` (sessão da `qa-admin`, tema por `localStorage.theme` e `colorScheme`, axe com `wcag2a/aa/21a/21aa`).
  2. Abrir `/admin/onboarding` no tema claro. O axe do documento e o de `.fi-main` dão 1 violação, no alvo acima.
  3. Rodar `node scratchpad/g4done.cjs`, que lê a cor computada de cada `.fio-tile-status`. Só o "CONCLUÍDA" usa `oklch(0.627 0.194 149.214)` = `#00a63e`.
- **Evidência**: `scratchpad/g4shots/axe.json`, chaves `admin-light` e `infra-light` (`axePaginaMain`); `scratchpad/g4shots/done-light.png`.
- **Ação exigida**:
  - Invocar a `feature-test-design` com o achado. A lacuna é de derivação: o EP do R36 não cobre o estado do rótulo de status (a seguir × a fazer × concluído). O CT-B03 ganha uma persona com passo concluído e tem de falhar primeiro.
  - Depois, cobrir `--fio-success` no tema claro, no `kit.css` e no `public/css/kit/kit-correcoes.css`, e rodar `php artisan filament:assets`.
  - Corrigir as três alegações citadas.

### QA-20 — Duas teclas Esc seguidas reabrem o checklist · Minor · destino 3 → 2

- **Dimensão**: H (teclado) e B (repetição)
- **Relacionado a**: P-28, R37, CT-B04, linha 25 de `resources/views/vendor/filament-onboarding/livewire/launcher.blade.php`
- **Esperado**: a P-28 diz que o checklist aberto fecha com Esc.
- **Observado**:
  - O tratador chama `$wire.toggle()`, que inverte o `isOpen` (`OnboardingLauncher::toggle`). O painel só sai do DOM quando a resposta Livewire volta, e até lá um segundo Esc dispara outro `toggle`. O checklist reabre.
  - Medi 3 rodadas por intervalo entre as duas teclas:

    | Intervalo | Painel aberto no fim | Requests `toggle` |
    |---|---|---|
    | 20 ms | 3 de 3 | 2 |
    | 150 ms | 3 de 3 | 2 |
    | 400 ms | 3 de 3 | 2 |
    | 1500 ms | 0 de 3 | 1 |

  - O CT-B04 aperta Esc uma vez só. Com uma tecla, inverter (`toggle`) e fechar dão o mesmo resultado, então o defeito sobrevive a ele.
- **Repro**: rodar `node scratchpad/g4esc2.cjs`. Em `/admin/users`, abrir o checklist, apertar Esc, esperar 150 ms, apertar Esc de novo. Depois de 2,5 s, `.fio-panel` está visível.
- **Evidência**: saída `escDuplo_*` do script, que lista os métodos dos POST Livewire por rodada.
- **Ação exigida**:
  - `feature-test-design`: uma linha do CT-B04 com dois Esc seguidos (painel ausente no fim). Ela tem de falhar primeiro.
  - Depois, trocar o tratador por um fechamento idempotente, que nunca abre (por exemplo, gravar `isOpen = false`).

### QA-21 — Um Esc fecha o Spotlight e, junto, o checklist que estava por baixo · Minor · destino 1

- **Dimensão**: H
- **Relacionado a**: P-28, linha 25 da mesma view
- **Esperado**: nada no `00` diz o que o Esc fecha quando há outra camada aberta por cima do checklist. A prática de diálogo é fechar só a de cima.
- **Observado**:
  - Com o checklist aberto e o Spotlight aberto por Ctrl+K, um único Esc fecha os dois. Antes: `spot_abertoAntes: true` e `spot_checklistAntes: true`. Depois: `false` e `false`.
  - Com o menu do usuário (dropdown do Filament) aberto, o Esc fecha só o menu, e o checklist continua aberto (`E_checklistAbertoDepois: true`).
  - Causa: o tratador escuta o `.window` sem olhar se o evento já foi tratado. O Spotlight também escuta o `.window`.
  - O kit já tem o mesmo padrão no assistente de chat (`@keydown.escape.window`), então pode ser uma escolha de produto, e não um defeito.
- **Repro**: rodar `node scratchpad/g4esc2.cjs` (bloco `spot_*`) e `node scratchpad/g4esc.cjs` (bloco `E_*`).
- **Ação exigida**: decidir no `01` (Superfície de UI, P-28) se o Esc fecha só a camada de cima.
  - Se sim: um CT-B que falha primeiro e depois a guarda no tratador.
  - Se não: registrar a decisão para o achado não voltar.

## Ciclos anteriores (histórico)

| ID | Sev. | Situação no ciclo 4 |
|---|---|---|
| QA-01..14 | — | corrigidos nos ciclos 1 e 2 (ver o `03`) |
| QA-15 | Minor | Corrigido no que o achado citou. O botão primário fica em `oklch(0.555 0.163 48.998)` (`primary-700`) com texto branco, e o axe dá 0 no checklist aberto nos 3 painéis × 2 temas. A página do `/app` também dá 0 nos dois temas. Sobra o QA-19. |
| QA-16 | Minor | Corrigido. Com o foco no botão do checklist ou no "×", um Esc fecha o painel, nada é gravado, e o CT-B04 está 2/2. A devolução do foco é lacuna declarada no `04` (taxonomia, Acessibilidade) e não é achado: com o foco dentro do painel, ele vai para o `BODY`, como no "×" do pacote. |
| QA-17 | Minor | Corrigido. O CT-75 (4 linhas) mata o K5 (`/\n/`, 2 de 4 vermelhos) e o K4 (sem sanitizar, 3 de 4). O K0 (sem mutação) dá 4/4 verde, em cerca de 3,7 s cada rodada. |
| QA-18 | Major | Corrigido. 79 IDs (75 CT + 4 CT-B), 23 CT no arquivo de consumo, badge 2.393 nos dois READMEs, "D1 a D15, mais D7b" no `02`, e a linha de log do `01` com `'flow'` igual ao código. |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (não há `07-tickets/`). P-27 → R36/CT-B03 e P-28 → R37/CT-B04. O commit `1c3876b5` faz o que a Superfície de UI do `01` diz, com a ressalva do QA-19. |
| B | Fronteiras e dados | ⚠️ | QA-20 (Esc repetido). Nenhum código de servidor mudou desde o ciclo 3 (`git diff --stat 10b422b1 1c3876b5 -- app config routes database bootstrap` vem vazio). As sondagens de `flowKey` do ciclo 3 continuam valendo. |
| C | Matriz de permissão | ✅ | RM-08: 403 para `infra` sem `View:PrimeirosPassos`. RM-09: 11 → 10 passos sem `Manage:Dashboard`. RM-07: 404 para todos com o tutorial desligado, inclusive o `master_global` na autoria. |
| D | Observabilidade | ✅ | `onboarding-2026-10-22.log`: 75 linhas, todas com `[Classe@Método]`. `grep -ciE "@example\|password\|token"` → 0. A linha da sondagem de injeção do ciclo 3 continua sendo uma linha só, com o `flow` sanitizado e truncado. O Esc não grava log (fechar não é recusa). |
| E | Performance | ✅ | Não houve código de servidor novo. CT-73 e CT-74 verdes no `--filter=Onboarding`. O Esc custa 1 POST `toggle` por tecla. |
| F | UX de erro | ✅ | Os 404 e 403 vêm sem a página no corpo (RM-07, RM-08). Não houve mudança neste ciclo. |
| G | Tema e cor | ⚠️ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 sobre o diff `--diff-filter=AM`: exit 0. Nível 2: o CT-B03 roda nos dois temas pela classe `dark` (`assertScript`). Nível 3, com Playwright direto, nos 3 painéis × 2 temas: nenhum texto invisível. No claro sobra o QA-19. |
| H | Acessibilidade | ⚠️ | QA-19, QA-20 e QA-21. Axe 4.x do `pest-plugin-browser`, no documento inteiro e com escopo `.fi-main` e `.fio-launcher`. Teclado: Esc fecha com o foco no botão e dentro do painel. |
| I | Segurança da superfície nova | ✅ | O step 9 cobriu o eixo 9 (25 linhas, 5 rejeitadas). Além dele: o Esc chama o `toggle` do pacote, que passa pelo `hydrate` (RQ-30), e o `toggle` só inverte `$isOpen`, que é só UI (`02`). Não há rota nem propriedade nova. |
| J | Regressão adjacente | ✅ | Suíte sem browser, com `--parallel --processes=4`, no HEAD `1c3876b5`: 5334 testes, 5331 ok, 3 pulados, 2 risky (844 s). `--filter=Onboarding`: 312/312, 3241 asserções. `KitUpdateTest`: 97/97. `tests/Browser/OnboardingGuiadoTest.php` em série: 8/8. RCRCRC (Configuration): o CSS que o `kit:update` entrega está coberto pelo `KitUpdateTest`, linha `kit.css`. |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1: 8 candidatos, todos presentes no `main` (rejeitados). K2 manual por `auto_prepend_file` sobre cópia no scratchpad: K4 e K5 morrem. O mutante "inverter em vez de fechar" é o código atual e sobrevive ao CT-B04 (QA-20). O "status concluído sem sobrescrita" sobrevive ao CT-B03 (QA-19). Não há score (ver *Não Verificado*). |
| L | Consistência documental | ⚠️ | As alegações de contraste no `CHANGELOG`, no `05` e no `01` entram no QA-19. Scripts e checagens: ver as notas abaixo da tabela. |

Notas da dimensão L:

- L1 `ids-ct.sh` exit 0. O cabeçalho do `04` (75 · 37 · 207 · 0) foi reproduzido pelos 4 `grep -c` do template.
- L2 `citacoes.sh` exit 1: 3 linhas, todas no `06` do ciclo 3, que este substitui (rejeitadas).
- L3: `02` × `01` (D1..D15) conferido; linha de log do `01` igual ao código.
- L4 `conformidade-rules.sh` exit 0. `css-filament.md` aplicada: só variáveis e uma regra escopada em `:root`, sem utilitária solta e sem `@layer`.
- L6 `checkbox-sem-evidencia.sh` exit 0. Reproduzidos: 312/3241, 79, 23, 2.393, "All 17 rules passed!" e pint `passed` (`--test`). O 5333 de `03:66` está datado no HEAD `10b422b1`; no HEAD atual são 5334.
- L7: sem divergência com o glossário.
- L8 `roteiro-manual.sh` exit 0. RM executados, todos de acordo com o esperado:
  - 01: semear de novo deixa 3 jornadas, 32 passos, 0 chaves repetidas e o mesmo hash. O interruptor estava ligado, então a linha `false` do CT-22 não foi refeita.
  - 02: desligar e religar pela tela, com "Salvo".
  - 03: checklist e "Primeiros passos" nos 3 painéis, sem boas-vindas.
  - 04: popover "Todos os usuários", sem erro de JavaScript.
  - 05: 11, 5 e 15 passos.
  - 06: 11 → 10 → 11.
  - 07: sem checklist; 404 na página e nas 2 telas de autoria.
  - 08: 403.
  - 09: 11 → 10.
  - Nos RM-06, RM-08 e RM-09 o preparo foi por `tinker` no banco descartável, não pela tela.

## Débitos Aceitos

- Nenhum ainda. QA-19, QA-20 e QA-21 viram débito se o usuário aceitar este veredito sem um novo ciclo. Cada um precisa ser replicado no `03`.

## Suspeitas Não Confirmadas

- Rejeitado: o commit `1c3876b5` apaga `resources/css/vendor/filament-onboarding/onboarding.css`, que existia no `main` desde o skeleton.
  - Nada carrega esse arquivo. O `config/filament-onboarding.php` só o cita num comentário, e o asset do pacote vem de `css/wallacemartinss/...`.
  - A linha "css publicado por pacote" do `KitUpdateTest` testa um padrão de caminho, não a existência do arquivo.
  - Pelo `03`, a remoção foi a pedido de gsferro.
- Rejeitado: o dashboard do `/infra` falha no axe sem o tutorial. É a linha de base registrada no `03` (Q32), fora do escopo.

## Não Verificado

- **K2, score**: o `--mutate` do plugin dá morte falsa no Windows (provado no ciclo 2). Neste ciclo há só a amostra manual: K0, K4 e K5 no CT-75, mais os dois mutantes de UI provados no app (QA-19, QA-20). Não há score para comparar ao piso de 70 %.
- **K, revisão adversarial dos CT-B03 e CT-B04**: não foi feita. O cabeçalho do `05` diz que o teto de 2 rodadas foi atingido.
- **QA-19 com outras cores primárias**: o axe só rodou com a paleta padrão (âmbar). O tom 700 de outras cores (`cor_primaria`) não foi medido.
