# Relatório de QA — dock-de-acoes-rapidas: Dock de ações rápidas (favoritos do usuário)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: o dock é Alpine e assets do pacote; domínio sensível: autorização entre painéis e organizações)
> Natureza da wiki: nova · Toca infra compartilhada: sim → matriz de papéis, settings da instalação, tabela `users`, os três `PanelProvider`, inventário de permissões · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 2 · Cosmético: 1
- Não verificadas: D (parcial), G, H, K (sem o K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. A Q28 continua aberta sobre a P-02, que foi implementada pela recomendação dela, como manda o roteamento de premissa da `feature-wiki`. Não é `RQ` aberta.
- Ambiente: app em `http://localhost:8000` (respondeu 302 em `/admin` e 200 em `/admin/login`) · Pest 5.1.1 · Playwright MCP indisponível · Boost MCP usado (`database-query`, `browser-logs`, `last-error`)

Os dois Major são de texto: o `01` afirma coisas que o código não faz. O código e os testes não têm defeito confirmado.

## Achados

### QA-01 — O `01` afirma um cast de enum em `dock_layout` que o código não tem · Major · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: RQ-17, passos 6 e 7 do PRD, ticket 04
- **Esperado**: o PRD descreve o código entregue. O passo 6 (`01-plano-acao.md:266`) diz "casts `dock_atalhos` → `array`, `dock_layout` → `DockLayout::class` (nullable)". O passo 7 (`01-plano-acao.md:276`) diz `layoutPara` = `$usuario?->dock_layout ?? DockLayout::tryFrom(...)`, e isso pressupõe o cast.
- **Observado**: `app/Models/User.php:147` só tem o cast `'dock_atalhos' => 'array'`. `dock_layout` é texto cru, e `DockDeAtalhos::layoutPara()` (`app/Support/DockDeAtalhos.php:100`) faz `DockLayout::tryFrom((string) ...)`. A linha 266 tem a marca *(alterado em 2026-10-14: ticket 01)*, mas a mudança marcada é a do `$hidden`, não a do cast.
- **Repro**:
  1. `grep -rn "DockLayout::class" wikis/specs/main/dock-de-acoes-rapidas/ app/`
  2. `grep -n "dock_" app/Models/User.php`
- **Evidência**: o passo 1 devolve só `01-plano-acao.md:266`. O passo 2 devolve `101`, `102` (`$hidden`) e `147: 'dock_atalhos' => 'array'`, sem cast de `dock_layout`.
- **Destino**: 1 especificação
- **Ação exigida**: corrigir os passos 6 e 7 do `01` (cast só em `dock_atalhos`, `layoutPara` por `tryFrom` de string) com a marca `*(alterado em …)*`. Não há cópia do texto no `02` (o ADR-02 diz "texto curto").

### QA-02 — O `01` ainda diz que todo favorito passa por painel, organização e troca de contexto · Major · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: RQ-04, RQ-15, RQ-16, P-04, passo 7 do PRD, `## Autorização` do PRD, ticket 08
- **Esperado**: `01-plano-acao.md:138` (`## Autorização`) diz "Cada favorito: `DockDeAtalhos::podeAbrir()` — … `canAccessPanel()`, no `/app` `canAccessTenant()` … avaliado no painel e no contexto de papéis do favorito". `01-plano-acao.md:279` (passo 7, `podeAbrir`) repete as mesmas barreiras e o `noContexto(...)`.
- **Observado**: o favorito do painel e da organização correntes pula `acessaPainel()`, `canAccessTenant()` e `noContexto()` (`app/Support/DockDeAtalhos.php:avaliar:544-548`, `organizacaoDoRequest:612`). Isso é a P-04, já registrada no ADR-01 do `02` (marca de 2026-10-14) e em `## Desvios do Plano` do `03`, mas o `01` continua afirmando o comportamento antigo, sem marca. No mesmo caso, a ADR troca `canAccessPanel()` por `User::motivoDaNegativaDoPainel()` (RD-05), e o `01` também não registra isso.
- **Repro**:
  1. `sed -n '138p;279p' wikis/specs/main/dock-de-acoes-rapidas/01-plano-acao.md`
  2. `sed -n '533,560p;612,625p' app/Support/DockDeAtalhos.php`
- **Evidência**: o `01` lista as barreiras sem exceção. O código retorna `montarAtalho()` direto quando `organizacaoDoRequest()` não é `false`.
- **Destino**: 1 especificação
- **Ação exigida**: alinhar `## Autorização` e o passo 7 do `01` à P-04, à RD-01 (descarte do Shield em `noContexto`) e à RD-05, com marca de data. Isso fecha o desvio que hoje está só no `03` e no `02`.

### QA-03 — A feature usa "organização corrente" e não o termo do glossário, e não registrou os termos que decidiu · Minor · destino 1

- **Dimensão**: L (L7)
- **Relacionado a**: RQ-25, RQ-27, RQ-35
- **Esperado**: o glossário define **Organização aberta** ("a organização da rota `/app/{slug}`, fixada por `Filament::getTenant()`"). Termo que a feature decide entra no glossário.
- **Observado**: o `00` (RQ-25), o `01`, o `04` e as docs pt/en dizem "organização corrente / current organisation" para o mesmo conceito. O código usa `$aberta`. Os termos "favorito oculto", "chave que nunca foi destino", "destino" e "catálogo" foram decididos na Q15 e na Q2 e não estão em `wikis/glossario.md`.
- **Repro**: `grep -n -i "dock\|atalho\|favorit\|corrente" wikis/glossario.md` (nenhuma linha) × `grep -rn "organização corrente" wikis/specs/main/dock-de-acoes-rapidas/ docs/pt/recursos/hub-de-navegacao.md`
- **Evidência**: o glossário não traz nenhum termo do dock. "corrente" aparece em RQ-25 e no `01`, passo 7.
- **Destino**: 1
- **Ação exigida**: alinhar o texto do `01`, do `04` e das docs a "organização aberta", ou registrar o sinônimo. Depois, incluir "favorito oculto", "destino" e "catálogo" em `wikis/glossario.md`. O `00` não é editado.

### QA-04 — A `## Verificação Final` declara o passe Ponytail como "manual" com a skill disponível · Minor · destino 1

- **Dimensão**: L (L6)
- **Relacionado a**: `## Verificação Final` do `03`, linha 82
- **Esperado**: uma degradação declarada precisa ter prova de que a ferramenta falta.
- **Observado**: o `03` diz "manual (skill `ponytail-review` sem o namespace `ponytail:` neste host)". O namespace realmente não existe, mas a skill sem namespace está instalada e foi usada no step 6 (`03-progresso.md:190`).
- **Repro**: `ls -d .claude/skills/ponytail-review .ai/skills/ponytail-review`
- **Evidência**: os dois diretórios existem.
- **Destino**: 1
- **Ação exigida**: rodar a skill `ponytail-review` sobre o diff e colar o resultado, ou reescrever a linha sem atribuir o passe manual à falta da ferramenta.

### QA-05 — Os checklists de passos do `03` estão todos abertos numa feature com tudo concluído · Cosmético · destino 1

- **Dimensão**: L (L3)
- **Esperado**: o `03` é o tracking único da feature.
- **Observado**: os itens de `## 1. Dependência` a `## 10. Documentação` e de `## Testes` (`03-progresso.md:7-65`) estão todos `[ ]`. Ao mesmo tempo, os 8 tickets estão `[x] concluído` e o código está no diff.
- **Repro**: `sed -n 5,66p wikis/specs/main/dock-de-acoes-rapidas/03-progresso.md | grep -c "\- \[ \]"`
- **Destino**: 1
- **Ação exigida**: fechar os itens com evidência, ou declarar que o acompanhamento passou para `## Tickets`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | Sem lacuna. `rastreabilidade.sh` exit 0; `indice.sh --check` exit 0. Conferi o diff por passo (passos 1–10 contra `git diff main...HEAD`): todo arquivo citado está no diff e trata do que o passo diz |
| B | Fronteiras e dados | ✅ | Pela validação lida e pelos CT verdes: chave forjada, lista, não-string e de organização sem vínculo (CT-17, CT-28, CT-54); layout fora do enum e como lista (CT-09, CT-12); 0/1/10/11 favoritos (CT-14, CT-33, CT-34); duplicata (CT-40). A sonda `--agent` não rodou: o hook negou o arquivo efêmero de sonda |
| C | Matriz de permissão | ✅ | Papéis `panel_user`, `admin`, `infra`, `master_global` e `admin_app` × render, adicionar, gerenciar, chamada direta e semeadura: CT-01–05, 20, 22, 37, 41, 42, 59. Nenhuma célula destrutiva sem CT |
| D | Observabilidade | ⏭️ parcial | Estático limpo: canal `dock-de-acoes-rapidas`, prefixo `[DockDeAtalhos@…]`, context com `user_id`, `painel`, classe do erro e contagens, sem PII nem chave crua. Log real não lido: nos testes `LOG_KIT_DRIVER=monolog` com `NullHandler`, e o ambiente local não tem `storage/logs/dock-de-acoes-rapidas*.log` |
| E | Performance | ✅ | Contagem medida pelos CT-35, 36, 56, 65 (ligado × desligado × sem plugin) e CT-71, 72 (P-04), todos verdes no meu run. O custo de favorito de outro painel é limitado a 10 trocas de contexto (ADR-01), não cresce com N |
| F | UX de erro | ✅ | Mensagens em pt-BR dizem o quê e o efeito ("Limite de 10 atalhos… Nada foi gravado.", "Há atalhos fora das telas disponíveis. Nada foi gravado.", P-03). O slideover continua aberto na recusa (`halt()`). O pacote tem `lang/pt_BR`. Avaliado na resposta do componente, não na tela |
| G | Tema e cor | ⏭️ | `dark-mode.sh --mecanismo` exit 1 (Filament com classe `dark`); nível 1 `dark-mode.sh` exit 0 nos 12 arquivos AM; nível 2 sem CT-B `inDarkMode()`; nível 3 sem Playwright MCP |
| H | Acessibilidade | ⏭️ | O CT-B01 não usa `assertNoAccessibilityIssues()`; teclado e foco não observados (sem MCP) |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (14 achados, 12 confirmados, 2 rejeitados). Além do step 9: nenhuma rota nova; `$fillable` intacto e `dock_*` em `$hidden` (CT-19); sem `DB::raw` nem upload; IDOR por usuário (gravação só no autenticado, CT-13) e por organização (CT-28, CT-61) |
| J | Regressão adjacente | ✅ | `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel`: 4443 testes, 4439 passed, 3 skipped, 1 falha (`AgentesDaEsteiraTest`, da baseline). As suítes das quatro features ancestrais estão dentro desse run. Não usei `--tia` (grava cache; a suíte completa é um superconjunto). RCRCRC: o refactor de `User::canAccessPanel()` (Core/Risk) está verde na suíte |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, 1 candidato rejeitado: CT-10 afirma em `afirmarLayoutEEstiloDoDock()`. K2 não verificada: o `--mutate` da sessão está em andamento e não há score, `Duration` nem sobreviventes disponíveis (`pest-mutate.out`, 01:29, só o aviso de TIA). Revisão adversarial do `04`: feita (2 rodadas) |
| L | Consistência documental | ❌ | 5 achados (QA-01–05). L1 `ids-ct.sh` exit 0; cabeçalho do `04` confere (75/28/149/2 pelos `grep -c`). L2 `citacoes.sh` exit 0. L4 `conformidade-rules.sh main` exit 0; 15 linhas conferidas, nenhuma rule violada. L5: docs pt × en × CHANGELOG coerentes. L6 `checkbox-sem-evidencia.sh` exit 0; números reproduzidos (124/124, 44/44, 38/38, 61/61, 4443); PCOV e Xdebug presentes. L7: QA-03 |

## Débitos Aceitos

- nenhum (o ciclo reprova)

## Suspeitas Não Confirmadas

- `last-error` do Boost: `Class "App\Filament\Dock\PluginDoDock" not found` em 2026-10-14 03:12:20, no `AdminPanelProvider.php:289`. Ocorreu uma vez, 5 min antes do commit 1424e41, provavelmente durante a restauração temporária de arquivos da prova de falsificabilidade. O app responde normalmente agora.
- O CT-B01 não terminou em mais de 10 min no meu run, em paralelo com o `--mutate` da sessão, e foi interrompido. O `03` registra 1/1 verde (2º run). Não reproduzido, sem indício de defeito.

## Não Verificado

- D — log real não lido: o canal de teste é `NullHandler`, e exercitar o fluxo no ambiente local gravaria no banco de desenvolvimento.
- G — nível 2 (sem CT-B em tema escuro) e nível 3 (Playwright MCP indisponível).
- H — sem `assertNoAccessibilityIssues()` no CT-B01 e Playwright MCP indisponível.
- K2 — prova indispensável pendente: o `pest --mutate --path=app/Support/DockDeAtalhos.php` da sessão está em andamento, sem score, `Duration` nem sobreviventes. PCOV e Xdebug presentes (`php -m`), `pest-plugin-mutate` instalado.
- L6 — "6 de 9 falham sem a correção" (`03`, `## Revisão do Diff`): reproduzir exige restaurar arquivos de `app/`, fora do contrato do gate.
- B/E — sonda `--agent` própria não rodada: o hook negou criar o arquivo efêmero; as dimensões foram verificadas pelos CT de fronteira e de contagem de consultas.
