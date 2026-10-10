# Relatório de QA — ocultar-seletor-de-organizacao-unica: interruptor que esconde o seletor de quem tem uma só organização

**Data**: 2026-10-08
**Escopo histórico**: diff de trabalho pré-commit; `main...HEAD` não inclui esse diff. Base da feature para revalidação: `47a6a60^`.
**Independência**: mesma sessão que escreveu a wiki — **modo degradado** (host sem sub-agente)
**Perfil**: Padrão (natureza `nova`, UI presente sem JS, domínio comum) — teto: `APROVADO COM DÉBITO` por construção

## Revalidação — 2026-10-09

O ciclo abaixo é histórico. Sua alegação L4 foi invalidada: `conformidade-rules.sh` compara somente commits, e `main...HEAD` pré-commit não verifica o diff de trabalho. A tabela de rules no `03` foi preenchida e o script passou com base `47a6a60^`; esse intervalo também inclui a feature posterior de unificação. Correções não commitadas foram conferidas pelo diff de trabalho separadamente.

O CT-02 passou com `KIT_TENANCY_OCULTAR_SELETOR_UNICO=true` no ambiente externo, após fixar a chave em `env` e `server` no `phpunit.xml`. Uma migration nova invalida cache de settings anterior ao novo campo, preservando dados do banco e cache do negócio. Regressão de cache antigo e 159 testes relacionados passaram. PHPStan: zero erros; Filacheck: 17 regras passaram.

O número histórico de 19 mutantes não foi revalidado para este predicado. O launcher foi corrigido e sua regressão cobre filtros compostos, caminhos especiais e saída não zero. O escopo de logos produziu uma medição real de 88,10%, registrada na wiki de unificação; não substituir esse número por 100% nem aplicá-lo ao seletor.

Custo estático corrigido: até três chamadas no layout normal, pelos boots de sidebar/topbar e pela view da sidebar. Referências: `vendor/filament/filament/src/Livewire/Concerns/HasTenantMenu.php:bootHasTenantMenu:27`, `vendor/filament/filament/src/Livewire/Concerns/HasTenantMenu.php:hasTenantMenu:37` e `vendor/filament/filament/resources/views/livewire/sidebar.blade.php:hasTenantMenu:11`. Sem medição de latência; não foi introduzido cache especulativo.

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0
- Não verificadas: **H** (axe sobre os dois CT-B novos) — causa em *Não Verificado*
- `RQ` abertas: nenhuma (RQ-01..RQ-05 fechadas; Q1–Q3 respondidas pelo solicitante em 2026-10-08)
- Ambiente: suíte Pest verde (`SeletorDeOrganizacao*` 16/16); app exercitado via Livewire/browser tests, não servido separadamente · MCP: indisponível

## Achados

Nenhum achado aberto. O único candidato mecânico do ciclo foi rejeitado como não-defeito:

### QA-01 — CT-04 com assertion só negada · rejeitado (não-defeito) · destino 5

- **Dimensão**: K1 — `k1-oraculo-fraco.sh` linha `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php:'[CT-04]':136`
- **Relacionado a**: CT-04 (`ligada e uma organização: o bloco inteiro some`)
- **Alegação do script**: "só expectativa negada (`not->toContain()`) — nenhum valor esperado"
- **Por que não é defeito**: a ausência do bloco **é** o comportamento sob teste; o par positivo vive no CT-05 (`ligada e duas: o bloco aparece`, mesmo seletor `fi-tenant-menu`). Mutante `visivel() → false` sempre morreria no CT-05 e nos dados do CT-03 — e de fato morreu: `--mutate` reportou **19 mutantes, 0 sobreviventes**, Duration compatível com a base da suíte.
- **Evidência histórica**: `pest --mutate --path=app/Support/SeletorDeOrganizacao.php` (comando 291, `pestw.cmd`); CT-05 em `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php:'[CT-05]':150`. Pontuação depende de revalidação do launcher Windows; não sustenta aceite atual sozinha.

## Matriz de Rastreabilidade

Sem lacuna — `rastreabilidade.sh` silencioso (exit 0): toda `RQ` tem passo, todo passo tem `RQ`, todo `RQ`/`P-nn` tem CT. Não há `07-tickets/` (feature não fatiada) — coluna `Ticket` não se aplica.

## Dimensões

| Dim | Resultado | Instrumento / evidência |
|---|---|---|
| A — Cobertura do requisito | ✅ verificada | `rastreabilidade.sh` exit 0; `git diff` por passo: todos os arquivos citados estão no diff |
| B — Fronteiras e dados | ✅ verificada | contagem 0/1/2/3 organizações coberta pelo dataset do CT-03; campo bool (Toggle) — sem string/numérico para sondar |
| C — Matriz de permissão | ✅ verificada | sem ação nova autorizável: `visivel()` não permite nem nega acesso; toggle vive em tela de settings já protegida |
| D — Observabilidade real | ✅ verificada | `SeletorDeOrganizacao.php:'tenancy':67`: channel dedicado, prefixo `[SeletorDeOrganizacao@visivel]`, context `{user_id, organizacoes, ocultar_seletor_unico}` — sem PII; mutante de remoção do `Log::` morreu (K2) |
| E — Performance | leitura estática corrigida | layout normal pode executar 3× `getUserTenants()`/request; cada chamada hidrata a coleção completa. Sem medição de latência; custo conhecido registrado no R4 do `03` |
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

- Candidato a rule do step 12: **nenhum** — o contrato "fence de código dentro de bullet engole headings no parser da wiki" já é do `rastreabilidade.sh`; o padrão settings→mapa→migration→toggle já está em `.ai/rules`. O silêncio histórico de L4 não comprovou conformidade; ver a revalidação acima.
