# Progresso — Roadmap item 9: excluir um papel que tem convite

**Estado**: em revisão

**Base do PR**: `main`

## 1. Consulta nomeada de convite pendente
- [x] `scopePendentes()` no `Convite` e `pendentesPara()` reutilizando — `app/Models/Convite.php:scopePendentes:510`; `pendentesPara()` e `valido()` usam `->pendentes()`; CT-01 7/7 verdes, 2026-11-17

## 2. A regra no model `Role`
- [x] relação `convites()` sem o escopo do painel `/app` — `app/Models/Role.php:convites`; CT-11/CT-12 (Tenancy) verdes nos três caminhos, 2026-11-17
- [x] `delete()`: recusa com pendente; apaga encerrados um a um — transação com trava e releitura (P-09); CT-02, CT-03, CT-08, CT-14, CT-15, CT-20 verdes, 2026-11-17

## 3. O aviso nas três ações de exclusão
- [x] `recusarSeHaConvitePendente()` com teto de 5 e-mails — CT-04, CT-05, CT-13, CT-16, CT-17, CT-18, CT-21 verdes; log da recusa (QA-03), 2026-11-17
- [x] `before` na ação da linha, na exclusão em massa e na edição — CT-02/CT-06/CT-07 nos três caminhos; Playwright 18/18 sem e com tenancy, 2026-11-17

## Testes
- [x] `tests/Kit/ExclusaoDePapelComConviteTest.php` (CT-01..CT-10, CT-13..CT-18, CT-20, CT-21) — também CT-20 e CT-21 — `vendor/bin/pest tests/Kit/ExclusaoDePapelComConviteTest.php` 41/41 (432 asserções), 2026-11-17
- [x] `tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php` (CT-11, CT-12, CT-19) — `vendor/bin/pest --testsuite=Tenancy tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php` 7/7, 2026-11-17
- [x] Ajuste de testes existentes que simulavam "papel apagado" por `$papel->delete()` com convite pendente — agora pela query (D4): `tests/Kit/ConviteTest.php` (`[CT-12]`, `[CT-13]`, `[CT-18]`), `tests/Kit/CadastroSocialPorConviteTest.php`, `tests/Kit/ConviteUsuarioExistenteTest.php`, `tests/Tenancy/ConviteTenancyTest.php`, `tests/Tenancy/ConviteUsuarioExistenteTest.php` — helper `apagarPapelPelaQuery()`; Kit 64/64 e Tenancy 18/18 nesses arquivos, 2026-11-17

## Tickets
Não fatiado — 2026-11-16: 9 RQ vigentes, 19 CT, compactação: sim (a sessão já vinha compactada de outra feature), 4 perguntas de requisito — sinal de compactação conferido; não fatiado porque a correção toca 3 arquivos de app e cabe numa sessão

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff — `/ponytail-review` no diff: 2 sugestões (-7 linhas), ambas recusadas com motivo em `### Auditoria Ponytail`, 2026-11-17
- [x] `vendor/bin/pint --dirty --format agent` — `{"tool":"pint","result":"passed"}`, 2026-11-17
- [x] `vendor/bin/phpstan analyse --no-progress --memory-limit=4G` — `{"result":"passed","errors":0}` (reproduzido pelo gate no ciclo 2), 2026-11-17
- [x] `vendor/bin/filacheck --fix` — "All 17 rules passed!", 2026-11-17
- [x] testes da feature (Kit e Tenancy) — Kit 41/41, Tenancy 7/7, 2026-11-17
- [x] `vendor/bin/pest --parallel --tia` contra a baseline — `pest --tia --parallel --processes=3 --exclude-testsuite=Browser`: 6058 testes, 6054 passaram, 3 pulados, 1 falha (badge do README 2.624→2.625, corrigido; `SiteDeDocumentacaoTest`+`CitacoesDeCodigoTest`+`RoteiroDeAtualizacaoTest` 283/283 depois); Browser em série com `--tia`: 254, 202 passaram, 52 pulados, 0 falhas; baseline em `main`: 6264, 0 falhas, 2026-11-17
- [x] `/code-review high main...HEAD` + passe de eixos (step 9), antes da reconciliação — 9 achados do `/code-review`, 3 do `fw-revisor-diff`, 2 do `codex`; destino de cada um em `## Revisão do Diff (step 9)`, 2026-11-17
- [x] Desvios propagados ao `01`/`02`/`04` de origem — marcas `*(alterado em …)*` no `01` (D1, D2, passo 2, passo 3, Superfície de UI), no `02` e no `04` (QA-02, QA-05), 2026-11-17
- [x] `rastreabilidade.sh {wiki}` silencioso — exit 0, 2026-11-17
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — exit 0 (rodado depois deste fechamento), 2026-11-17
- [x] `citacoes.sh {wiki}` silencioso — silencioso em `00`–`04`; as 5 linhas restantes são do `06` do ciclo 1, verbatim do gate (não editável), que o ciclo 2 substitui, 2026-11-17
- [x] `ids-ct.sh {wiki}` silencioso — exit 0, 2026-11-17
- [x] `conformidade-rules.sh {wiki} main` silencioso — exit 0, 2026-11-17
- [x] Falsificabilidade dos CT da recusa sem a correção — patch de `app/` revertido (`git apply -R`, reaplicado idêntico): Kit 36 de 37 e Tenancy 7 de 7 falharam sem a correção — o que passa é o controle "sem convite algum" do CT-01; CT-20 vermelho antes da transação (permissões, linha 1; papel, linha 2) e CT-21 linha "sem ViewAny" vermelha antes da P-10; o "convite do meio continua" do CT-20 é não falsificável nesta pilha (mesma conexão, o rollback o desfaz), 2026-11-17
- [x] Docs pt/en, CHANGELOG e roadmap reconciliados — `docs/{pt,en}/autenticacao/convites.md` ("Excluir um papel que tem convites"), `atualizando-o-projeto`, CHANGELOG 0.53.2, roadmap item 9 FEITO, roteiro `wikis/atualizacoes/v0.53.2.md` + cópia em `agentes-e-skills.md`, 2026-11-17
- [x] `git commit` — `079fe61a`, `1c1e291a` e o commit do ciclo 1 do gate, 2026-11-17

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| CR-01 / RD-01 / CX-01 | `/code-review`, eixos e `codex` (convergentes) | `Role::delete()` não é atômico: convite criado ou reenviado entre a contagem e a limpeza é apagado como encerrado, ou o `DELETE` falha depois do `deleting` do spatie e deixa o papel sem permissões, usuários e histórico | premissa → CT → correção | P-09 / CT-20 | — |
| CR-02 | `/code-review` | dupla checagem (`before` e model) sem vínculo | coberto por P-09 | P-09 / CT-20 | — |
| CR-03 | `/code-review` | expirado é reenviável e some com o papel sem aviso | — | — | decisão do solicitante: a resposta a Q2 inclui os expirados no "apagar junto" |
| CR-04 | `/code-review` | N+1 no aviso da massa (uma consulta por papel) | — | — | papéis são poucos por instalação; o custo é linear no tamanho da seleção, sem query por linha da tabela |
| CR-05 | `/code-review` | `valido()` repetia a condição de pendente à mão | correção (reuso) | — | — |
| CR-06 / RD-02 | `/code-review`, eixos | docblock do scope apontava `Role::booted()` | correção de comentário | — | — |
| CR-07 | `/code-review` | `Role` depende do painel `app` para nomear o escopo | — | — | mesmo padrão já usado por `Convite::pendentesPara()`; o painel `app` é parte do kit |
| CR-08 | `/code-review` | filtro `instanceof Role` sem efeito, falha aberta | correção (tipo `Role` no `map`, falha fechada) | — | — |
| CR-09 | `/code-review` | comentário inline repetido em seis testes | correção (helper `apagarPapelPelaQuery()` em `tests/Pest.php`) | — | — |
| RD-03 | eixos | comentário dizia "a mesma regra da `acaoDeUsuarios()`", que aplica outra regra | correção de comentário | — | — |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | aplicada | logs no canal `autenticacao` com `[Role@delete]`; sem pasta-base nova (`app/Exceptions/` evitada) |
| `config.md` | `config/**` | aplicada | só `kit.version` 0.53.2 |
| `css-filament.md` | `app/Providers/**` | n.a. | só uma citação de linha num comentário do `AppPanelProvider` |
| `filament-resources.md` | `app/Filament/**/Resources/**` | aplicada | ações nativas com `before()`; `authorizeIndividualRecords('delete')` mantido na massa |
| `filament.md` | `app/Filament/**` | aplicada | `filacheck` 17/17; notificação nativa do Filament |
| `kit.md` | `tests/Kit/**` | aplicada | testes do kit na suíte Kit, sem tenancy |
| `models.md` | `app/Models/**` | aplicada | nenhuma model nova; `Role` e `Convite` sem trait nova |
| `onboarding.md` | `app/Filament/**` | n.a. | nenhuma página, widget ou dock tocado |
| `providers-filament.md` | `app/Providers/Filament/**` | n.a. | só comentário |
| `providers.md` | `app/Providers/**` | n.a. | só comentário |
| `specs.md` | `wikis/specs/**` | aplicada | wiki em `wikis/specs/feat/excluir-papel-com-convites/` com `00`–`04` |
| `testes.md` | `tests/**` | aplicada | CT com ID no nome; helper com docblock em `tests/Pest.php`; tenancy em `tests/Tenancy` |

## Quality Gate

- **Ciclo**: 1 · **Veredito**: REPROVADO → especificação · **Data**: 2026-11-17
- **Ciclo**: 2 · **Veredito**: REPROVADO → especificação (QA-10 Major; QA-09, QA-11, QA-12 Minor) · **Data**: 2026-11-17 — QA-10: custo real do `delete()` no `01`; QA-09: três citações das docs; QA-11: PHPStan na Verificação Final; QA-12: nomes dos `it()` de CT-08/CT-14 e o comentário da R5 com "encerrados"
- **Relatório**: `06-relatorio-qa.md` (ciclo 2; o do ciclo 1 está no histórico do git, commit `7044378b`)
- **Achados e destino**: QA-01 (Major, implementação: PHPStan level 8 com 2 erros — corrigido com `Role $record` no `before`, `phpstan analyse` 0 erros) · QA-02 (Major, especificação: rota `/admin/shield/roles` e a recusa no `delete()`, corrigidos no `01`, `02`, `04`) · QA-03 (log da recusa pela tela — `RoleResource@recusarSeHaConvitePendente`) · QA-04 (P-10 → CT-21 → correção) · QA-05 (`04` alinhado à P-09) · QA-06 (este `03` com evidência e os despachos do step 9) · QA-07 (helper fora do docblock de `ofertaPara()`) · QA-08 (termo "Encerrado" no glossário)

## Candidatos a Rule

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-11-16 — mantenedor (respostas a Q1 e Q2 no chat; autorização "prossiga ate o fim") — 1 rodada; perguntas: 0 fato, 0 desenho, 2 requisito

Roteiro manual: não — 2026-11-16 — instrução explícita do desenvolvedor ("prossiga ate o fim", sem pedir roteiro) — correção pequena, coberta por teste de componente

### Confronto código × afirmação (step 5)
Nenhuma divergência de comportamento entre o `01` e o código.

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `$action->cancel()` no `before` interrompe a exclusão | `vendor/filament/actions/src/Action.php:cancel:691` existe; `before()` em `vendor/filament/actions/src/Concerns/HasLifecycleHooks.php:before:24` | nenhuma |
| `$records` no `before` da exclusão em massa traz só os autorizados | `vendor/filament/actions/src/Action.php:records:576` injeta `getIndividuallyAuthorizedSelectedRecords()` | nenhuma |
| Auditoria grava a remoção do convite | `config/audit.php:events:65` inclui `deleted`; só vale por instância e com `audit.console` ligado no teste (`tests/Kit/ConviteTest.php:'audit.console':279`) | o passo 2 apaga um a um; CT de auditoria liga `audit.console` |
| Citações do `01`/`02` | `citacoes.sh` acusou 5 símbolos fora da linha | símbolos trocados; `citacoes.sh` exit 0 |

### Varredura da classe irmã (step 5)
Nenhuma classe nova: a feature acrescenta um scope, uma relação e um método estático em classes existentes. Sem lista paralela a conferir.

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | Transação em volta do `delete()` do papel: a recusa vem antes de apagar convite, então RQ-04 não depende dela | sim | `01`, D2 e passo 2 |
| 2 | Inlinar a condição de pendente no `Role` em vez do scope | recusada: o scope é reusado pela `pendentesPara()`, que repetia a mesma condição | `01`, passo 1 |
| 3 | (diff, 2026-11-17) Tirar a contagem prévia de pendentes do `Role::delete()` — a releitura no laço já recusa | recusada: a contagem dá o número exato para o log da recusa e recusa o caso comum sem apagar e auditar encerrados só para desfazer | `app/Models/Role.php` |
| 4 | (diff, 2026-11-17) `$bloqueados` por referência no `map` do aviso → `mapWithKeys` | recusada: estilo, não excesso (-1 linha) | `RoleResource.php` |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 7 | `general-purpose` — `feature-test-design` (derivação do `04`) | opus (fallback: não há agente de derivação em `.claude/agents/`) | `01` além de paths/rotas/Superfície de UI; `02` além da Superfície Livewire; código de `app/` | `04` com 13 CT, depois 18 e 19 (três retomadas: P-06, rodada 1 e rodada 2 do adversário); 3 perguntas devolvidas (Q3, Q4, Q5) | 190k + 252k + 286k tokens · 7,7 + 5,9 + 4,4 min | contagens por `grep -c` conferidas; Q?n renumeradas; costuras confirmadas pela sessão |
| 2 | 7 | `fw-adversario-ct` — rodada 1 | não exposto pelo host | `01`, `02`, código | 34 achados (ADV-01..ADV-34) + ADV-35 não se aplica | 63k tokens · 2,8 min | todos fechados no `04` pela derivação (retomada 2) |
| 3 | 7 | `fw-adversario-ct` — rodada 2 (última) | não exposto pelo host | `01`, `02`, código | 3 achados novos (ADV-36..ADV-38) + ADV-39 de registro | 60k tokens · 1,7 min | fechados no `04` (retomada 3); CT-19 sem nova rodada — limite de 2 rodadas |
| 4 | impl. | `fw-executor-ct` — testes do `04` | não exposto pelo host | `01`, `03`, conversa | 25, depois 41 testes verdes; nenhum vermelho (b) | 158k + 209k tokens · 4,4 + 3,4 min | arquivos conferidos por `git status`; execução refeita pela sessão |
| 5 | 9 | `/code-review high main...HEAD` (skill, sub-agente isolado) | não exposto pelo host | conversa | 9 achados (CR-01..CR-09) | 86k tokens · 2,4 min | CR-01 reproduzido pelo CT-20 vermelho antes da correção |
| 6 | 9 | `fw-revisor-diff` — passe de eixos, sem `wikis/` | não exposto pelo host | `01`, `03`, conversa | 3 achados (RD-01..RD-03), 6 eixos sem achado | 56k tokens · 3,0 min | `git status --porcelain` vazio antes e depois; RD-01 convergente com CR-01 |
| 7 | 9 | `codex exec --yolo` — revisor independente pedido pelo mantenedor | codex-cli 0.163.0 (modelo não exposto) | wiki, conversa | 2 achados (CX-01 = CR-01/RD-01) | — | convergente; nenhum arquivo alterado |
| 8 | 10 | `codex exec --yolo` — roteiro `wikis/atualizacoes/v0.53.2.md` e a cópia em `agentes-e-skills.md` | codex-cli 0.163.0 | conversa | roteiro + cópia; `RoteiroDeAtualizacaoTest` e `ChecklistDeReleaseTest` 238 (235 + 3 pulados) | — | seções e links conferidos pela sessão (só o repositório do kit citado) |
| 9 | 11 | `fw-qa-gate` — ciclo 1 | claude-opus-5-5 (informado no `06`) | conversa | `06` gravado verbatim; REPROVADO → especificação (2 Major, 6 Minor) | 249k tokens · 27 min | `git status --porcelain` vazio antes e depois; banco descartável alterado pelo gate (papel "vendas" excluído) |

## Blockers

## Desvios do Plano
- Passo 2: transação reinstalada (D2) pela P-09 do step 9; a recusa no meio usa `rollBack()` manual em vez de exceção própria, porque `app/Exceptions/` não existe e pasta-base nova pede aprovação
- Passo 2: a checagem de pendente usa o scope SQL, não `situacao()` — os dois discordam na borda `expira_em = agora` (o CT-01 "validade 12:00:00" pegou)
- Prova de falsificabilidade feita revertendo só o patch de `app/` no próprio repositório (`git apply -R` / `git apply`, conferido idêntico), e não numa cópia: na cópia, o `vendor/` por junção carregava o `app/` do repositório principal
- Passo 2: listener `deleting` virou `delete()` sobrescrito — o `deleting` do spatie (`vendor/spatie/laravel-permission/src/Traits/HasPermissions.php:bootHasPermissions:34`) roda antes e desliga permissões e usuários; corrigido no `01` (D1, passo 2, Observers)
- Passo 3: e-mails só para quem tem `ViewAny` de convite — P-06 no `00`, passo 3 do `01`

## Notas de Implementação
- O `deleting` do spatie (`HasPermissions::bootHasPermissions()`) desliga permissões e usuários do papel antes de qualquer listener do kit; por isso a regra é o `delete()` sobrescrito
- Citações de linha que envelheceram com o scope novo, corrigidas: `docs/{pt,en}/autenticacao/convites.md` (`situacao:600`; e `aceitar:619`, `aceitarComoUsuarioExistente:687`, `recusar:752` na forma de continuação, que o `CitacoesDeCodigoTest` não confere — QA-09 do ciclo 2), `app/Providers/Filament/AppPanelProvider.php` e o `04` da wiki `diagramas-da-arquitetura` (`email_verified_at:647`). Já velhas antes desta feature, não mexidas: `app/Models/User.php` e `app/Support/RegistroAberto.php` citam a linha 591 do `Convite.php`, que na `main` já era `'Recusado'`
- Teste manual com Playwright (sem e com tenancy, banco SQLite próprio, drivers padrão de sessão, cache e fila em banco): 18/18 nos dois modos; auditoria com 5 `deleted` de convite em cada. Com `SESSION_DRIVER=file` e `CACHE_STORE=array` o aviso não aparecia na tela — artefato da configuração do teste, não do kit
- No CT-19, `isset()` sobre o proxy do `test()` do Pest é sempre falso: o arnês guarda o escopo original em `$GLOBALS`

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-11-16
- `template-01-plano.md` — step 4 — 2026-11-16
- `template-02-adr.md` — step 4 — 2026-11-16
- `template-03-progresso.md` — step 4 — 2026-11-16

## Retrospectiva
