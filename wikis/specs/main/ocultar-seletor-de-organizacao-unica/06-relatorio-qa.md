# Relatório de QA — ocultar-seletor-de-organizacao-unica: interruptor que esconde o seletor de quem tem uma só organização

**Data**: 2026-10-08
**Escopo**: `main...HEAD` (diff de trabalho, pré-commit)
**Independência**: mesma sessão que escreveu a wiki — **modo degradado** (host sem sub-agente)
**Perfil**: Padrão (natureza `nova`, UI presente sem JS, domínio comum) — teto: `APROVADO COM DÉBITO` por construção

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0
- Não verificadas: **H** (axe sobre os dois CT-B novos) — causa em *Não Verificado*
- `RQ` abertas: nenhuma (RQ-01..RQ-05 fechadas; Q1–Q3 respondidas pelo solicitante em 2026-10-08)
- Ambiente: suíte Pest verde (`SeletorDeOrganizacao*` 16/16); app exercitado via Livewire/browser tests, não servido separadamente · MCP: indisponível

## Achados

Nenhum achado aberto. O único candidato mecânico do ciclo foi rejeitado como não-defeito:

### QA-01 — CT-04 com assertion só negada · rejeitado (não-defeito) · destino 5

- **Dimensão**: K1 — `k1-oraculo-fraco.sh` linha `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php:136`
- **Relacionado a**: CT-04 (`ligada e uma organização: o bloco inteiro some`)
- **Alegação do script**: "só expectativa negada (`not->toContain()`) — nenhum valor esperado"
- **Por que não é defeito**: a ausência do bloco **é** o comportamento sob teste; o par positivo vive no CT-05 (`ligada e duas: o bloco aparece`, mesmo seletor `fi-tenant-menu`). Mutante `visivel() → false` sempre morreria no CT-05 e nos dados do CT-03 — e de fato morreu: `--mutate` reportou **19 mutantes, 0 sobreviventes**, Duration compatível com a base da suíte.
- **Evidência**: `pest --mutate --path=app/Support/SeletorDeOrganizacao.php` (comando 291, `pestw.cmd`); CT-05 em `SeletorDeOrganizacaoTenancyTest.php:150`

## Matriz de Rastreabilidade

Sem lacuna — `rastreabilidade.sh` silencioso (exit 0): toda `RQ` tem passo, todo passo tem `RQ`, todo `RQ`/`P-nn` tem CT. Não há `07-tickets/` (feature não fatiada) — coluna `Ticket` não se aplica.

## Dimensões

| Dim | Resultado | Instrumento / evidência |
|---|---|---|
| A — Cobertura do requisito | ✅ verificada | `rastreabilidade.sh` exit 0; `git diff` por passo: todos os arquivos citados estão no diff |
| B — Fronteiras e dados | ✅ verificada | contagem 0/1/2/3 organizações coberta pelo dataset do CT-03; campo bool (Toggle) — sem string/numérico para sondar |
| C — Matriz de permissão | ✅ verificada | sem ação nova autorizável: `visivel()` não permite nem nega acesso; toggle vive em tela de settings já protegida |
| D — Observabilidade real | ✅ verificada | `SeletorDeOrganizacao.php:'tenancy':67`: channel dedicado, prefixo `[SeletorDeOrganizacao@visivel]`, context `{user_id, organizacoes, ocultar_seletor_unico}` — sem PII; mutante de remoção do `Log::` morreu (K2) |
| E — Performance | ✅ verificada | leitura estática: ligado, `hasTenantMenu()` avalia no render de sidebar **e** topbar → até 2× `getUserTenants()`/request — registrado como não-defeito R4 no `03` (YAGNI, D4 do `01`) |
| F — UX de erro | ✅ verificada | sem caminho de erro novo: o toggle salva pelo fluxo existente da página de settings |
| G — Tema/cor | n.a. com prova | `dark-mode.sh --mecanismo` exit 1 (mecanismo existe), mas `git diff --name-only --diff-filter=AM -- '*.blade.php' '*.css'` = vazio — sem superfície de cor nova |
| H — Acessibilidade | ⚠️ não verificada | componente `Toggle` padrão Filament e remoção de UI não pedem markup; sem `assertNoAccessibilityIssues()` nos CT-B novos |
| I — Segurança da superfície | ✅ verificada | coberto pelo step 9 (5 linhas, R4 rejeitado) + nenhuma rota nova no diff; `visivel()` só lê dados do próprio usuário — sem IDOR possível |
| J — Regressão | ✅ verificada | infra compartilhada = sim → `pest --parallel` completo (comando 316): as 3 falhas novas eram guardas de contagem que o próprio diff invalidou (CT-56, READMEs/badge) — corrigidas e reverificadas verdes; `KitArteTest` (consumidor de IMAGENS/CLIPES) verde |
| K — Adequação da suíte | ✅ verificada | K1: 1 candidata → QA-01 rejeitada; K2: 19 mutantes, 100% mortos, Duration compatível |
| L — Consistência documental | ✅ verificada | L1 `ids-ct.sh` exit 0; L2 `citacoes.sh` exit 0; L3 conferida; L4 `conformidade-rules.sh` exit 0; L5 docs pt/en + CHANGELOG; L6 `checkbox-sem-evidencia.sh` exit 0 + números reproduzidos (16/16, 19 mutantes, contagens de README); L7 `wikis/glossario.md` — termos "Organização"/"Organização aberta" usados certos, nenhum termo novo decidido |

## Débitos Aceitos

Nenhum achado aberto. O teto `APROVADO COM DÉBITO` vem do **perfil Padrão** (G–J parciais por construção) somado à H não verificada — não de defeito.

## Suspeitas Não Confirmadas

- QA-01 (CT-04 negada) — rejeitada, ver acima; registrada para não reaparecer no próximo ciclo.

## Não Verificado

- **H** — axe (`assertNoAccessibilityIssues()`) não rodado nos CT-B novos; risco nulo por construção (componente padrão do framework, remoção de bloco), mas não medido.
- App servido isolado — validação dinâmica feita via suíte Pest (Livewire + browser tests verdes), não via `APP_URL` separada.

## Para o orquestrador

- Candidato a rule do step 12: **nenhum** — o contrato "fence de código dentro de bullet engole headings no parser da wiki" já é do `rastreabilidade.sh`; o padrão settings→mapa→migration→toggle já está em `.ai/rules` (a L4 passou silenciosa porque as rules casadas estão cumpridas).
