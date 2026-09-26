# Progresso — PHPStan no level 8, e as pendências das últimas rodadas

## Baseline (antes do primeiro commit)

- [x] Suíte em `main` @ `acf94fb` — **2.993 testes, 2.990 passaram, 3 pulados, 12.582 asserções,
  0 falhas**, 188,6 s — `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel --compact`,
  2026-09-26
- [x] Pint `passed`, PHPStan L7 `0 erros`, Filacheck `All 17 rules passed!`, 2026-09-26
- [x] Level 8: **48 erros** — `phpstan analyse --level=8 --error-format=raw | grep -c identifier=`,
  2026-09-26. Por arquivo (`cut -d: -f1 | sort | uniq -c`): CreateRole 3, EditRole 3,
  DescobreCardsDoPainel 9, RegistroPorConvite 5, TelaBloqueio 3, TelaLogin 1, LoginSocialController 1,
  ExigirEmailVerificado 1, AssistenteChatWidget 10, DefinirSenhaPorEmail 2, Convite 5,
  PrimeiroAcessoSocial 1, ImportadorDoKit 3, migration harden_onboarding 1

## 1. Classe A — o tipo diz o que o fluxo garante
- [x] `getCurrentOrDefaultPanel()` → `getCurrentPanel() ?? getDefaultPanel()` (TelaLogin, DefinirSenhaPorEmail, TelaBloqueio) — `grep -rn getCurrentOrDefaultPanel app` → só o comentário de `TelaRecuperarSenhaUnificada:55`, 2026-09-26
- [x] CreateRole / EditRole leem do `$papel` — e o `instanceof` subiu para antes do `firstOrCreate`, 2026-09-26
- [x] AssistenteChatWidget: `assertContexto(): User` + leitura única de `mensagemPendente` — `historico()` com guarda local, 2026-09-26
- [x] RegistroPorConvite via `convite()`, 2026-09-26
- [x] Convite: `filter()` + `diff()`, 2026-09-26
- [x] migration: guarda do `$survivor` (`continue`), 2026-09-26

## 2. Classe B — URL de painel nula
- [x] RegistroPorConvite (`urlDeLoginDoApp()`, `urlDaOrganizacao()`), TelaBloqueio, PrimeiroAcessoSocial com `?? url($painel->getPath())`; LoginSocialController com o login do painel padrão (P-03), 2026-09-26

## 3. Classe C — invariante com guarda
- [x] DescobreCardsDoPainel `painelCorrente()` + ternário morto removido, 2026-09-26
- [x] Convite `papelOuFalha()` na primeira linha útil dos dois verbos, antes de qualquer escrita (CT-13), 2026-09-26
- [x] ImportadorDoKit `registro()`, 2026-09-26

## 4. Classe D — anotação do vendor
- [x] `ignoreErrors` do ExigirEmailVerificado, com `path` único e mensagem ancorada, 2026-09-26

## 5. O nível sobe e fica travado
- [x] `phpstan.neon` `level: 8` — `vendor/bin/phpstan analyse` → `{"result":"passed","errors":0}`, 2026-09-26
- [ ] guarda em `QualidadeDeCodigoTest`

## 6. Pendências documentais das wikis anteriores
- [x] `cobertura-de-testes/03` — presets e roadmap fechados com evidência; a caixa do `CoberturaDeTestesTest` depende do 6b, 2026-09-26
- [x] `plumb-e-dividas-tecnicas/03` — as duas caixas fechadas com a baseline de hoje e o #108, 2026-09-26

## 6b. Reconciliação de testes da wiki de cobertura
- [x] Correções de especificação no `04` da cobertura (CT-07, CT-10, CT-23) — `grep -n "alterado em 2026-09-26"` → 3 blocos, 2026-09-26
- [x] Renumeração + cenários faltantes (`fw-executor-ct`) — 27 CTs com teste (`comm -12` dos IDs → 27); `php artisan test` dos 4 arquivos → 107 passaram, 1.461 asserções, 2026-09-26
- [x] Não automatizáveis declarados no `04` da cobertura (CT-01, 02, 03, 25, 28) + CT-49/50 como referência cruzada — `diff` de IDs volta **só** com esses 7, 2026-09-26

## 7. Docs e CHANGELOG
- [x] nível corrente nas docs pt/en, `site-vitepress` pt/en, READMEs, CONTRIBUTING, `wikis/qualidade-de-codigo.md`, `wikis/README.md`, `rector.php`, roadmap §8.1 (e §9 novo) — frases históricas mantidas, 2026-09-26
- [ ] ADR-04 decidida (título h3 congelado)
- [ ] CHANGELOG

## 8. Release
- [ ] `config/kit.php` 0.41.0, PR, merge, tag

## Testes
- [ ] `04-casos-de-teste.md` implementado

## Verificação Final
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` — level 8, 0 erros
- [ ] `vendor/bin/filacheck --fix`
- [ ] suíte Unit, Feature, Kit, Tenancy `--parallel` contra a baseline
- [ ] `/code-review high main...HEAD` + passe de eixos (step 6.5)
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa — saída do `diff` colada
- [ ] Citações `arquivo:símbolo:linha` reverificadas
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit`

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| a anotação errada do `EnsureEmailIsVerified` se corrige por stub | o validador de stub do PHPStan acusa `class.notFound` para `Response` (Symfony e Illuminate), com e sem `use`, dentro e fora do projeto — medido em 4 tentativas | ADR-03 reescrita para `ignoreErrors`; `00` RQ-06 marcado `(alterado em …)` |
| `AssistenteChatWidget` tem 11 erros | **10** (`uniq -c`) | tabela do `01` corrigida; B = 7, C = 13 |
| a caixa de `CoberturaDeTestesTest` no `03` da cobertura só está desatualizada | o `diff` de IDs mostra 22 CTs sem teste e 3 IDs fora do `04` | passo 6b criado no `01` |
| o `--min=0` precisa de correção (reconciliação de 25/09) | já recusado em `app/Console/Commands/KitCobertura.php:pisoPedido:162` | só o executor é avisado; nenhuma correção de app |
| `Filament::getPanel('app')` pode ser nulo | o level 8 **não** acusa — a facade é tipada sem `?` | nenhuma mudança; fora do escopo |

### Varredura da classe irmã

Nenhuma classe nova. A lista paralela relevante é a do **nível do PHPStan**, citado em 10+ arquivos
de prosa (`grep -rn "level 7"` fora de `vendor/`, `wikis/specs/` e `CHANGELOG`) e numa fixture
congelada — passo 7 e ADR-04.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | seis seções "nenhum" no PRD | sim, fundidas numa | `01` |
| 2 | guarda "toda `ignoreErrors` com path" é yagni | **recusada**: é a forma mecânica de RQ-06 | `01` passo 5 |
| 3 | `filter()`+`reject()` avalia o Validator duas vezes | sim, `diff()` | `01` passo 1 |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 4 | `general-purpose` — derivar o `04` seguindo `feature-test-design` (rodou a adversarial rodada 1 por dentro) | opus | `01`, `02`, conversa | 18 CTs, 7 premissas | `git status`: só o `04` criado; `@phpstan-ignore` 2+1 conferidos por grep |
| 2 | 6 | `ponytail-review` sobre `01`/`02` | em linha (skill) | — | 3 sugestões, 2 aplicadas | — |
| 3 | 6b | `fw-executor-ct` — reconciliar o `04` da cobertura × testes | sonnet | `01`/`02`/`03` da cobertura, conversa | 27 CTs com teste, 1 vermelho (b) no CT-27 | `git status` só nos arquivos permitidos; 3 amostras lidas (CT-10, CT-23, helpers sem uso cruzado). **Reprovado em parte**: citações `[CT-nn]` em docblock mascaravam 4 IDs no `diff` — corrigidas pela sessão |
| 4 | 4 | `fw-adversario-ct` — rodada 2 | opus | tudo menos `00` + `04` | **não convergiu**: 12 implementações erradas, 10 oráculos fracos; R6 misturava duas regras | M-I rejeitado com motivo; demais aceitos |
| 5 | 4 | mesmo analista do #1 — aplicar a rodada 2 (R6 → R6a/R6b) | opus | `01`, `02`, código | 22 CTs, 60 mutantes, 9 regras | `grep -c` conferido; só o `04` editado |
| 6 | impl. | sem despacho — edição cirúrgica em 14 arquivos interdependentes (mesma exceção vale para docs) | sessão | — | L8: 48 → 0 | `phpstan analyse` → 0 erros |
| 7 | impl. | `fw-executor-ct` lote A — CT-01…08 (`QualidadeDeCodigoTest`) | sonnet | `01`, `02`, conversa | — | — |
| 8 | impl. | `fw-executor-ct` lote B — CT-09…22 | sonnet | `01`, `02`, conversa | — | — |

## Blockers
- Nenhum.

## Desvios do Plano

- **P-03 contradiz a ADR-02 num ponto só**: o login social que volta de painel sem login vai ao login
  do painel **padrão**, não à raiz do painel de origem. O `04` é o oráculo; a ADR-02 ganhou a exceção
- **A guarda do convite subiu de `atribuirPapel()` para o topo dos dois verbos** (`papelOuFalha()`):
  o CT-13 exige total de usuários igual e convite pendente depois da falha, e `atribuirPapel()` roda
  depois do `User::create()` e do consumo atômico
- **Docs tinham dois erros anteriores a esta entrega**: afirmavam que o level 7 cobra nulo não
  checado (é o `checkNullables` do 8), e contavam **duas** (docs) / **uma** (wiki) exceções no
  `ignoreErrors` quando já eram três

## Notas de Implementação

- **O CT-27 da cobertura acusava uma confusão de conceito, não uma doc incompleta.** A doc dizia
  "225, sendo 4 não testados"; com score 98,22 % isso é 221/225, **zero sobreviventes**. Os 4 são
  mutantes em linha que nenhum teste executa. A correção foi remedir (os números do `KitCobertura`
  tinham envelhecido com a própria reconciliação: 89,24 % → **97,47 %**, 17 → 4 não testados, 38 s)
  e publicar os não testados por `arquivo:linha`, com a distinção escrita — `cmd //c ".\pestw.cmd …"`
- **Citação de ID em docblock é ID para o `diff`.** Quatro docblocks citavam `[CT-51]`…`[CT-53]` e
  `[CT-01]`..`[CT-12]` como história; o `grep` do step 7 não distingue citação de declaração, e o
  `[CT-01]` citado escondia que o CT-01 real não tem teste. Citação histórica vai sem colchete

- **`getCurrentOrDefaultPanel(): ?Panel` nunca devolve nulo.** O corpo é
  `getCurrentPanel() ?? getDefaultPanel()`, e `getDefaultPanel(): Panel`. A assinatura do vendor é
  mais larga do que o comportamento — escrever o corpo inline é o que dá ao analisador o tipo real
- **`--error-format=raw` não funciona sob o `laravel/pao`**: a saída vira JSON truncado de agente.
  `PAO_DISABLE=1` na frente do comando devolve o formato pedido

## Retrospectiva
