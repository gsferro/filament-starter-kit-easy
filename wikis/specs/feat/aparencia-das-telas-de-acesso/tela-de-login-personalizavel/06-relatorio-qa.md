# Relatório de QA — feat/aparencia-das-telas-de-acesso: Tela de login personalizável

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: iframe de preview e campos `live`; domínio sensível: autorização do preview e escopo por organização)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → todas as telas de autenticação dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 3

**REPROVADO → especificação (QA-16), com implementação (QA-15)**

- Blocker: 0 · Major: 2 · Minor: 0 · Cosmético: 1
- Não verificadas: G, H, K. As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Q1 continua `aberta`, mas o código implementa só a premissa estreita P-01, então não gera achado. A confirmação de Q2 pelo solicitante também segue pendente. As duas estão em Débitos do `03`
- Ambiente: app em `http://127.0.0.1:8123` (HEAD `d343fc56`, aparência global `painel`) · Pest 5.0.5 · Playwright MCP: indisponível · Boost MCP: indisponível (`laravel-boost` CONNECT_TIMEOUT)
- **Ciclo 3 é o teto do loop e trouxe achados novos (QA-15..QA-17).** Pela convergência, o caso sobe ao usuário com os achados abertos e vira blocker no `03`. Não há ciclo 4

## Situação dos achados do ciclo 2

| QA | Situação no ciclo 3 | Evidência |
|---|---|---|
| QA-02 | **fechado** | Os logs dos cenários estão em `scratchpad/cenarios/c{1..4}c.test.log` da sessão. Os quatro rodaram 5795 testes com `skipped` 1161 e `failed` ausente. Erros: c1c 5, c3c 2, c4c 2, todos "`phpstan dump-parameters` exceeded the timeout of 60 seconds"; c2c `passed`. `c3c.update.log`: "46 arquivo(s) atualizado(s)", com as duas migrations novas (tenants e settings) aplicadas. Os quatro diretórios contêm `luminanciaDoOklch`, o que confirma o código de `d343fc56`. Rodei de novo o `QualidadeDeCodigoTest` isolado em c3c: 31 testes, 29 passaram e 2 foram pulados, como o `03` declara. Ressalva: a suíte dos cenários não exercita navegador (ver QA-15) |
| QA-10 | **fechado** | `corDoPainel()` expande `#rgb`. Sonda: `#ff0`→`#ffff00`→escuro; `#FFF`→escuro; `#000`→branco. `CorPrimaria::FORMATO_HEX` aceita só `#rgb`/`#rrggbb`, então nenhum outro formato chega |
| QA-11 | **fechado** | A conversão OKLab→sRGB linear é real. Varri as 286 cores de `Color::*` e comparei com `Color::calculateContrastRatio()` contra branco e `#111827`. Houve 1 divergência, Olive[500] (4,20 × 4,49), que não é alcançável porque `corDoPainel()` usa só o tom 600 |
| QA-12 | **fechado** | `grep -rn "Authenticate"` na wiki sem o `06`: restam só a nota datada do `01:124` e a linha SEC-02 do `03`, que marca a substituição. `providers.md` e `auth.md` estão corrigidas |
| QA-13 | **fechado** | P-02 diz "Pela leitura do texto original (Q2)". A confirmação pendente está em `03` › Débitos |
| QA-14 | **fechado** | Os quatro `grep -cE` do template dão 42 · 17 · 57 · 0, iguais ao cabeçalho. A seção `## Regra R17` existe |

As quatro linhas novas do CT-40 discriminam o código anterior. Rodei a classe de `1b6d3a09` lado a lado com a atual: `#ff0` dá antigo=false e novo=true; Fuchsia antigo=true e novo=false; Pink antigo=true e novo=false; Sky antigo=false e novo=true.

## Achados

### QA-15 — O preview quebra um teste de regressão existente: erro de JS na edição da organização · Major · destino 2

- **Dimensão**: J (F e H correlatas)
- **Relacionado a**: RQ-07, passo 7 do PRD, P-08, `tests/BrowserTenancy/IdentidadeVisualTest.php`
- **Esperado**: RQ-07, "não deve quebrar os sistemas que já usam o kit". O teste existente `abre a tela cheia de edicao da organizacao` termina em `assertNoJavaScriptErrors()`.
- **Observado**: o teste falha de forma reproduzível, também isolado, com `ResizeObserver loop completed with undelivered notifications`. O único `ResizeObserver` de `app/` e `resources/` é o da feature (`resources/views/filament/admin/preview-das-telas-de-acesso.blade.php:42`). Ele observa `$refs.moldura` (`:67`) e, no callback, `medir()` muda `escala`, que redimensiona o `__palco` dentro da moldura observada. O `EditorDeAparenciaDasTelas::preview()` é renderizado sempre no `TenantForm`, inclusive com "Usar a aparência padrão" ligado. Na suíte completa, o mesmo erro também derrubou o CT-B08 no dataset "preview, quebra de JavaScript". Isolado, esse caso passou (4/4), então ali o efeito é intermitente.
- **Repro**:
  1. `vendor/bin/pest tests/BrowserTenancy/IdentidadeVisualTest.php --compact` → `failed: 1`, `abre_a_tela_cheia_de_edicao_da_organizacao`, "found 1: ResizeObserver loop completed…"
  2. `vendor/bin/pest --testsuite=Browser --compact` → 220 testes, 181 passaram, 37 pulados, 2 falharam (o caso acima e o CT-B08 do preview)
- **Evidência**: `grep -rln "ResizeObserver" resources app` → só a view do preview. `git diff main...HEAD -- app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php` → a seção "Telas de acesso" com `...EditorDeAparenciaDasTelas::preview(...)` sem `visible()`.
- **Destino**: 2. Um teste versionado já pega o defeito, então não é lacuna de teste.
- **Ação exigida**: tirar o laço de layout do preview, por exemplo observando um elemento cujo tamanho não depende da `escala` ou adiando a medição com `requestAnimationFrame`. Depois, rodar a suíte `Browser` inteira (inclui `tests/BrowserTenancy`) verde e registrar o resultado no `03`.

### QA-16 — "suíte Browser 171/171" não se reproduz no HEAD · Major · destino 1

- **Dimensão**: L6 / L4
- **Relacionado a**: `03` › Verificação Final e Conformidade (`testes-browser.md`), RQ-07
- **Esperado**: todo número da Verificação Final é reproduzido pelo comando que o gera, sobre o commit que vai para o PR.
- **Observado**: o `phpunit.xml` define a suíte `Browser` com `tests/Browser` e `tests/BrowserTenancy`. No HEAD `d343fc56`, `vendor/bin/pest --testsuite=Browser` dá 220 testes e 2 falhas, não 171/171. A alegação é de 2026-11-13, antes dos commits dos ciclos 1 e 2, e o `03` não registra nenhuma execução posterior da suíte de navegador. Os cenários 1–4 não suprem a falta, porque não rodam navegador. A linha `testes-browser.md` da Conformidade repete o número.
- **Repro**: `vendor/bin/pest --testsuite=Browser --compact` → `"tests":220,"passed":181,"failed":2,"skipped":37`
- **Evidência**: `grep -rn "171" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/` → `03-progresso.md:117` (Conformidade, `testes-browser.md`) e `03-progresso.md:131` (Verificação Final)
- **Destino**: 1
- **Ação exigida**: depois da correção do QA-15, trocar as **duas** linhas (`03:117` e `03:131`) pela saída real da suíte `Browser` sobre o commit final, com data.

### QA-17 — `03` › Quality Gate registra o ciclo 2 sem o destino do veredito · Cosmético · destino 1

- **Dimensão**: L
- **Observado**: a linha do ciclo 2 diz "REPROVADO (QA-02 aberto…)". O `06` do ciclo 2 emitiu "REPROVADO → especificação".
- **Repro**: `grep -n "^| 2 | REPROVADO" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/03-progresso.md`
- **Destino**: 1
- **Ação exigida**: registrar o veredito com o destino, como na linha do ciclo 1.

## Matriz de Rastreabilidade

`rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica: não há `07-tickets/`. A única lacuna:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-07 | não quebrar quem já usa o kit | 1, 7, 9 | CT-21, CT-27 (cenários) | regressão `tests/BrowserTenancy` existente | preview no `TenantForm` | `IdentidadeVisualTest` vermelho | ❌ QA-15, QA-16 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-02 fechado. `rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (sem `07-tickets/`). Diff de `1b6d3a09..d343fc56` conferido contra as ações do ciclo 2. RQ-07 com lacuna na regressão de navegador (QA-15) |
| B | Fronteiras e dados | ✅ | `#rgb` maiúsculo e minúsculo, `#000`/`#FFF`, primária nula e as 286 cores da paleta sondadas. Sem achado novo |
| C | Matriz de permissão | ✅ | Visitante → `302 /login` ao vivo. CT-38/39/17/19 verdes (211/211). Sem mudança de autorização no ciclo |
| D | Observabilidade | ✅ | O ciclo não acrescenta ponto de log. As evidências dos ciclos 1 e 2 continuam valendo, sem PII |
| E | Performance | ✅ | O código novo é aritmética O(1), sem query. A medição do ciclo 2 (3 queries em `/admin/login`) continua valendo |
| F | UX de erro | ✅ | Sem mudança. Erro de console no editor da organização: QA-15 |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 `dark-mode.sh` sobre views/css/Filament do diff exit 0. Nível 2: CT-B com `inDarkMode()` verdes (40/40 isolado). Nível 3 sem MCP |
| H | Acessibilidade | ⏭️ não verificada | `grep -c assertNoAccessibilityIssues tests/Browser/AparenciaDasTelasDeAcessoTest.php` = 0, e o MCP está indisponível. O contraste do texto (P-18) foi conferido pela sonda da B |
| I | Segurança da superfície nova | ✅ | O step 9 cobriu: tabela de 19 linhas, BP-20 rejeitado. Além dele, `route:list --path=preview-das-telas -v` mostra a pilha `web` → `SetUpPanel:admin` → `Authenticate`, sem a pilha do painel. Isso está registrado como débito no `03` (rascunho próprio, sem dado alheio). `#[Locked]` e a reautorização por request continuam como no ciclo 2 |
| J | Regressão adjacente | ❌ | QA-15. Testes da feature: Kit, Entrega e Tenancy 211/211. Kit adjacentes (TelasDeAutenticacao, BloqueioDeSessao, CorPrimaria, CorrecaoDeCorPrimaria, CitacoesDeCodigo): 139/139. Browser da feature isolado: 40/40. Suíte `Browser` completa: 2 falhas. Cenários 1–4 reproduzidos pelos logs |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1, com as mesmas 4 candidatas do ciclo 2 (CT-31, CT-33, CT-07, CT-11), rejeitadas pelo mesmo motivo. As linhas novas do CT-40 discriminam o código anterior (sonda lado a lado). O K2 não foi rodado: o `04:1199` declara pendente a re-revisão adversarial dos cenários acrescentados, e o `--mutate` no Windows só se certifica aplicando o mutante, o que exige editar arquivo, vedado ao gate. Não há score declarado na wiki |
| L | Consistência documental | ❌ | QA-16, QA-17. L1 `ids-ct.sh` exit 0; contagem do cabeçalho reproduzida (42 · 17 · 57 · 0). L2 `citacoes.sh` exit 0. L3: `01` Autorização e passo 1 batem com o código. L4 `conformidade-rules.sh` exit 0, com `testes-browser.md` sustentada pelo número velho (QA-16). L5: docs pt/en coerentes sobre o texto escuro em cor clara. L6 `checkbox-sem-evidencia.sh` exit 0; reproduzidos 5795, 1161, 46 arquivos e 31/29/2; não reproduzido Browser 171/171 (QA-16). L7: glossário sem mudança. L8 `roteiro-manual.sh` exit 0; n/a, sem `08` e com decisão `não` |

## Débitos Aceitos

- Nenhum neste ciclo: há Major aberto. Os débitos que a sessão declarou no `03` (Q1, Q2, alternador de tema fora do teclado, organização fora do navegador, positivo do painel em redefinir/verificar/2FA, preview sem a pilha do painel) continuam lá e não foram contestados.

## Suspeitas Não Confirmadas

- Ligação causal do QA-15: a cadeia da evidência é forte (único `ResizeObserver` do app, renderizado sempre no formulário da organização, mesmo erro no CT-B08 do preview). Mas confirmar que retirar o observer zera o erro exige editar a view, o que é vedado ao gate. O teste vermelho, em si, é reproduzível.

## Não Verificado

- G nível 3 (olhar nos dois temas): Playwright MCP indisponível.
- H, axe (`assertNoAccessibilityIssues`): nenhum CT-B da feature o chama, e o MCP está indisponível.
- K2: a re-revisão adversarial dos cenários acrescentados está declarada pendente em `04:1199` e `05:456`. O `--mutate` não é certificável no Windows sem aplicar o mutante, e aplicar exige editar arquivo, o que é vedado.
- Sessão autenticada no app servido (editor, preview 200/403 ao vivo): sem MCP. Coberta por CT/CT-B.
- Busca do RQ-12 pelos nomes do projeto de referência: os nomes estão mascarados no `00`. Só o oráculo genérico (CT-24) foi reproduzido.
