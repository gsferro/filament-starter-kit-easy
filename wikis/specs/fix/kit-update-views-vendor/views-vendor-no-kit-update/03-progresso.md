# Progresso — Issue #148: overrides autorais de `resources/views/vendor` no `kit:update`

**Estado**: em revisão

> Branch: `fix/kit-update-views-vendor` · Base do PR: `main` (`dcb3083`, v0.45.0)

## 1. As cinco pastas autorais em `CAMINHOS_DO_KIT`
- [x] Comentário de bloco + 5 entradas depois de `'resources/views/svg',`, na forma que `caminhosDeclaradosEm()` lê — `grep -c "^        '" app/Console/Commands/KitUpdate.php` = 84 (79 + 5); comentário de 7 linhas (teto 6 do ponytail estourado em 1: a última linha nomeia o teste que decide); `[CT-10]` verde, 2026-10-06

## 2. A varredura decide autoria em `resources/views/vendor`
- [x] Caso novo em `KitUpdateTest` (autoral → coberta; crua → fora; skip fora da árvore) pelo `fw-executor-ct`, a partir do `04` — CT-02…CT-08, CT-11…CT-14 + CT-01 no dataset e CT-10 renomeado; `pest tests/Kit/KitUpdateTest.php --compact` → 83/83, 133 asserções (despacho 3), 2026-10-06
- [x] Comentário do `continue` de `resources/views/vendor/` reescrito — aponta CT-06/CT-07 deste arquivo (`git diff -- tests/Kit/KitUpdateTest.php`), 2026-10-06
- [x] `media.blade.php` no dataset da fundação — chave `[CT-01] override da lock-screen` no `->with([...])` do caso `cobre os arquivos da fundação`, 2026-10-06

## 3. Recalcular as citações de `KitUpdate.php`
- [x] 20 citações em docs pt/en e 3 testes reancoradas; `CitacoesDeCodigoTest` e CT-66 verdes — 5 pelo `citacoes.py` (símbolo), 7 à mão (chave entre aspas: `'tests/Kit'`, `'tests/Pest.php'`, `'wikis/README.md'` ×2 idiomas, `CAMINHOS_SO_RELATORIO`) e 3 curtas (`483,1158`, `487-489`), todas +12; `pest CitacoesDeCodigoTest ChecklistDeReleaseTest ConstraintDeDependenciaTest DeployDockerLocalTest` → 47/47 (3 pulados); as citações de `wikis/specs/**` antigas **não** foram tocadas (registros datados, fora do `CitacoesDeCodigoTest` por decisão da v0.36.0), 2026-10-06

## 5. Caminho novo na lista é comparado com a árvore do projeto *(passo novo em 2026-10-06)*
- [x] `caminhosNovosNaLista()` + `rotularDiff()` estáticos; segundo diff em `arquivosAlterados()`; frase nas docs pt/en — commit `011333f`; `php -l` ok; `pest tests/Kit/KitUpdateTest.php` 83/83 depois da extração do `rotularDiff` (CT-10 verde); docs pt/en com o parágrafo "Caminho que **entrou** em `CAMINHOS_DO_KIT` depois da sua versão…" citando `caminhosNovosNaLista`, 2026-10-06
- [x] Verificação ponta a ponta (CT-17 e CT-18): `kit:update --repo={kit local} --tag={tag temporária} --dry-run` na extração v0.45.0 lista `media.blade.php` — extração `validacao-v0.45.0/novo-sem-tenant` (git init, `kit.version` 0.45.0) **sem** `resources/views/vendor/filament-auth-designer` e com uma edição só do projeto em `tests/Kit/FundacaoTest.php` (controle do M36); tags locais temporárias `v0.45.1-rc0` (= `806ad36`, só a constante) e `v0.45.1-rc` (= `011333f`); `php artisan kit:update --repo={kit local} --tag=… --dry-run --no-interaction` (logs em `scratchpad/e2e-*.log`): **(1) controle, classe antiga, rc0** → 0 linhas de `resources/views/vendor`, `media.blade.php` ausente — a constante sozinha não entregava (CR-01 reproduzido); **(2) candidata, classe antiga, rc** → 10 linhas de `resources/views/vendor`, `media.blade.php modificado`, `FundacaoTest` ausente (P-07: a classe antiga entrega na primeira rodada); **(3) classe nova copiada + `--from=0.45.0`, rc0** → 1 linha, `media.blade.php novo no kit`, `FundacaoTest` ausente (P-06 funciona sem o toque; M36 morto: o segundo diff não acusa pasta antiga da lista), 2026-10-06

## 6. As dez views autorais mudam nesta release *(passo novo em 2026-10-06)*
- [x] Linha de comentário Blade nas dez views; `git diff --name-only v0.45.0 HEAD -- resources/views/vendor` = 10 arquivos — exatamente os dez das cinco pastas autorais (nenhum das sete cruas); nos cinco com cabeçalho `{{-- … --}}` a linha entrou dentro do bloco; `sticky-panel.blade.php` está CRLF na árvore de trabalho (índice LF, `text=auto eol=lf`) e a linha seguiu o arquivo; `pest BotaoLimparCacheTest LogoDarkModeTest PaginasInfraTest KitUpdateTest` → 146/146 depois do commit (CT-14 compara `git archive HEAD` com a árvore), 2026-10-06

## 4. CHANGELOG
- [x] `[Unreleased]` → `### Corrigido` (#148) — entrada com as 5 pastas, as 7 de fora, o critério e o efeito para quem já editou uma delas; `[CT-11]` verde, 2026-10-06

## Testes
- [x] `tests/Kit/KitUpdateTest.php` (CT-01, CT-02 ×14, CT-03, CT-04 ×3, CT-05, CT-06, CT-07, CT-08, CT-11, CT-12, CT-13 ×3, CT-14, CT-15 ×4, CT-16 ×4, CT-18; CT-10 = caso existente `extrai do fonte desta versão…`, renomeado com o ID) *(alterado em 2026-10-06: revisão adversarial +3 CT, +7 Exemplos; step 9 +CT-15/16 e 6 CTs alterados)* — dois lotes do `fw-executor-ct` (despachos 3 e 7); `pest tests/Kit/KitUpdateTest.php --compact` → 92/92, 148 asserções, 39 casos `[CT-nn]` (CT-18 por `SendMessage` ao mesmo executor), 2026-10-06
- [x] CT-09 — procedimento com `--log-junit` sobre CT-06…CT-08 na extração do `git archive` (evidência aqui, não `it()`) — na extração `validacao-v0.45.0/novo-sem-tenant` com uma pasta `resources/views/vendor/projeto-x` publicada e editada pelo projeto, fora da lista: `php artisan test tests/Kit/KitUpdateTest.php --filter='CT-0[678]' --log-junit` → `{"result":"passed","tests":6,"passed":3,"skipped":3}`, os três `skipped` (o junit do Pest 5 não carrega o motivo; o motivo não vazio é provado pelo CT-13); **M17**: com o `->skip(…)` removido por `sed` na cópia da extração → `"result":"failed"`, mensagem citando `projeto-x/painel.blade.php` e "override AUTORAL … em KitUpdate::CAMINHOS_DO_KIT"; na árvore do kit os três passam (83/83). Cópia restaurada, pasta sonda removida, 2026-10-06

## Tickets
Não fatiado — 2026-10-06: 5 RQ vigentes, 11 CT, compactação: sim (da feature anterior desta sessão, antes do step 0 desta), 3 perguntas de requisito — nenhum sinal de tamanho (18 RQ / 60 CT); a compactação não é desta feature, que cabe numa sessão: sugestão não feita

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff (validar contra over-engineering) — rodado em linha (skill do projeto) sobre `app`, `tests/Kit/KitUpdateTest.php` e as dez views: `net: -4 lines possible` (um `shrink` em `caminhosNovosNaLista`, aplicado; `yagni` do flag `$rastreados` recusado — é a D5; o `comPacote` do helper de teste ficou como o executor escreveu), 2026-10-06
- [x] `vendor/bin/pint --dirty` — `{"tool":"pint","result":"passed"}` a cada lote (último: depois do `shrink` em `KitUpdate.php`), 2026-10-06
- [x] `vendor/bin/pest tests/Kit/KitUpdateTest.php --compact` — OK (83 tests, 133 assertions) depois do `[CT-01]` no dataset; **91/91, 145 asserções** depois do segundo lote (CT-15/16 e os seis alterados), 2026-10-06
- [x] Regressão: `DuasRotasDeEntregaTest`, `LogoDarkModeTest`, `BotaoLimparCacheTest`, `CitacoesDeCodigoTest`, `ChecklistDeReleaseTest`, `ConstraintDeDependenciaTest`, `DeployDockerLocalTest`, `DiagramasDaArquiteturaTest` — `pest DuasRotasDeEntregaTest LogoDarkModeTest BotaoLimparCacheTest DiagramasDaArquiteturaTest --compact` → OK (558 testes, 4.030 asserções, 2 min 48 s); `pest CitacoesDeCodigoTest ChecklistDeReleaseTest ConstraintDeDependenciaTest DeployDockerLocalTest --compact` → 47/47 (3 pulados); `pest tests/Kit/KitUpdateTest.php` → 83/83; **regressão final** sobre `a93172c`: `pest` dos 12 arquivos (`KitUpdate`, `DuasRotasDeEntrega`, `LogoDarkMode`, `BotaoLimparCache`, `PaginasInfra`, `CitacoesDeCodigo`, `ChecklistDeRelease`, `ConstraintDeDependencia`, `DeployDockerLocal`, `DiagramasDaArquitetura`, `QualidadeDeCodigo`, `ArquiteturaDoCodigo`) → OK, 765 testes, 4.539 asserções, 3 pulados, 3 min 29 s, 2026-10-06
- [x] Mutantes manuais do caso novo: entrada removida da lista; pasta crua acrescentada à lista — três mutantes da constante por `sed`, suíte `KitUpdateTest` inteira, `git checkout` depois de cada um: **A** `filament-captcha` fora da lista → `Failures: 1`, CT-06 ("[CT-06] toda pasta autoral está coberta inteira, inclusive por arquivo que ainda não existe") lista os 4 drivers + a sonda; **B** `pulse` na lista → `Failures: 1`, CT-07 ("[CT-07] nenhuma view de pasta publish cru está coberta") acusa `pulse/dashboard.blade.php`; **C** entrada por arquivo (`…/media.blade.php`) no lugar da pasta → `Failures: 1`, CT-06 pela sonda `novo-arquivo-sonda.blade.php` (o CT-14 **não** mata esse: a entrada por arquivo ainda extrai o próprio arquivo — M31 corrigido no `04`). Árvore restaurada (`git status` limpo, 5 entradas); **passo 5** — **E** `array_diff` invertido em `caminhosNovosNaLista` → CT-15 `Failures` (2 linhas); **F** `D` sem origem rotulado "removido do kit" → CT-16 `Failures: 1`; **D (M38)** `resources/views/vendor/pulse/local.blade.php` não rastreado → CT-06/07/08 continuam verdes (6 testes, 15 asserções): a árvore real é lida pelo `git ls-files`; arquivo removido, `git checkout` depois de E e F, 2026-10-06
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados ou rejeitados com motivo
- [ ] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa: `ids-ct.sh {wiki} 'tests/Kit/KitUpdateTest.php'` silencioso
- [ ] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade dos CTs novos: quantos falham sem o fix
- [ ] CHANGELOG reconciliado com o comportamento final; docs pt/en sem frase a mudar além das citações
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| CR-01 = RD-01 | genérico + eixos | `arquivosAlterados()` compara tag→tag dentro da lista: caminho recém-entrado só entrega o que mudou depois da origem; quem já está em ≥ v0.43.0 sem o override (o caso do issue) nunca o receberia | premissa → CT → correção (passo 5 novo) | P-06 / CT-15, CT-16 + verificação ponta a ponta | — |
| CR-02 = RD-02 | genérico + eixos | 11 âncoras curtas `(:símbolo:linha)` no parágrafo do `handle()` das docs pt/en ficaram 12 linhas atrás; `desvincularKit:535` já errada na `main` | fonte (docs), depois do passo 5 (as linhas deslocam de novo) | — | — |
| CR-03/04/05/09 = RD-03 | genérico + eixos | citações curtas pré-existentes erradas na `main` e deslocadas por soma: `ChecklistDeReleaseTest` 483,1158 e 1146; `DeployDockerLocalTest` 487-489 (é `git init`, 497); `wikis/checklist-de-release.md:101` 838 (é 860) | fonte, com a linha certa, depois do passo 5 | — | — |
| CR-06 = RD-04 | genérico + eixos | comentário da constante e CHANGELOG dizem "do pacote instalado"; o código compara com **qualquer** pacote do vendor com o mesmo caminho relativo (`.gitkeep` casa com 17) | texto alinhado ao código (comentário, CHANGELOG, glossário) | — | o mapa namespace→pacote **rejeitado**: é a Q7/D3 (lista à mão que P-03 recusa); o risco é view do kit byte a byte igual à de outro pacote, improvável, e o CT-01 cobre a lock-screen de qualquer jeito |
| CR-07 | genérico | CT-08 tautológico (mesmo `glob` dos dois lados) | teste: contagem por referência independente + a pasta da lock-screen classificada `autoral` (é o objeto da RQ-01, não medição congelada) | CT-08 (alterado) | a parte "`pulse` sai cru" **rejeitada**: congelaria a medição (ADV-14) |
| CR-08 | genérico | CT-14 não exercita o mecanismo (`caminhosUnidos` com a lista velha é comutativo; `git archive` não é o fluxo real) | CT-14 reescopado para "a entrada-pasta extrai o arquivo aninhado"; o mecanismo real vai para CT-15/CT-16 e para a verificação ponta a ponta do passo 5 | CT-14 (alterado), CT-15, CT-16 | — |
| RD-05 | eixos | mensagens com saída errada: pasta sem pacote instalado ("republique" não existe; apagar a pasta órfã não é oferecido); cru coberto por ancestral ("remover a entrada" tiraria as autorais; é estreitar) | teste: mensagens com a saída certa por caso | CT-04 (linha `ancestral`), CT-12 (alterados) | — |
| RD-06 | eixos | CT-06/CT-07 enumeram pelo disco; arquivo não rastreado muda a classe só numa máquina | teste: árvore real pelo `git ls-files` (D5) | CT-06, CT-07 (Dado alterado) | — |
| — | ambos | rejeitados pelos revisores e confirmados pela sessão: symlink (nenhum na árvore, iterador não desce), `afterEach` por arquivo, `git archive` + `tar` com `escapeshellarg`, chaves de dataset únicas, regex de `caminhosDeclaradosEm` não vê o comentário novo (CT-10) | — | — | — |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `views.md` — nunca diretiva do Blade dentro de `{{-- --}}` | `resources/views/**` (as dez views autorais) | aplicada | a linha nova de cada view é só texto: `git diff v0.45.0 HEAD -- resources/views/vendor \| grep '^+' \| grep -v '^+++' \| grep -c '@'` = 0; `PaginasInfraTest` e `LogoDarkModeTest` renderizam as telas que usam as views (146/146) |
| `app.md` — papel por `ContextoDePapeis`, DTO `spatie/laravel-data`, painel por `Paineis`, asset por `asset('storage/…')` | `app/**` (`KitUpdate.php`) | n.a. | o diff no comando é uma constante e dois estáticos sobre `git diff`; nenhum papel, DTO, painel nem URL de arquivo |
| `commands.md` — chave do `.env` só por `SubstituicaoEmArquivo` | `app/Console/Commands/**` | n.a. | o `kit:update` não grava `.env` neste diff (`git diff main...HEAD -- app \| grep -c "definirNoEnv\|\.env"` = 0) |
| `testes.md` — helper de mais de um arquivo em `tests/Pest.php`; caso que lê a árvore do kit pula com motivo; `toContain()` sem mensagem; CHANGELOG inteiro; ID histórico sem colchetes | `tests/**` (`KitUpdateTest.php` + 3 testes de citação) | aplicada | os helpers novos só existem em `KitUpdateTest.php` (`grep -rl classificarPastaDeViews tests` = 1); CT-06/07/08/11/14 com `->skip(fn (): bool => ! naArvoreDoKit(), …)` e CT-13 trava isso; CT-11 lê o arquivo inteiro; o docblock antigo cita CT da outra wiki sem colchetes e os IDs novos entram só nos nomes dos `it` |
| `specs.md` — comportamento de pacote afirmado depois de ler o vendor; citação de vendor por símbolo; citação de teste pelo ID do CT | `wikis/specs/**` (`00`–`04`) | aplicada | a classificação autoral × cru nasceu de diff byte a byte contra `vendor/*/*/resources/views` (tabela do `01`); nenhuma citação `vendor/...:N` sem símbolo no `01`/`02`; as duas citações `arquivo:linha` de teste do `03` foram trocadas pelo ID do CT (`citacoes.sh` reconfere) |

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-06 — sessão autônoma, pelas recomendações (o solicitante confirma ao ler o PR; ele pediu "analise com cuidado o que foi reportado e corrija") — 2 rodadas (step 4; step 7 devolveu Q7); perguntas: 0 fato (resolvidas por leitura e pelo script de auditoria), 4 desenho (Q4–Q7 → D1–D3), 4 requisito (Q1–Q4 — nenhuma bloqueia passo: Q1 e Q2 implementadas pela direção que falha fechado como P-01 e P-02; Q3 é a tag, fora desta entrega)

### Perguntas da entrevista (step 4)

| Qn | Raia | Afeta | Pergunta | Recomendação adotada |
|---|---|---|---|---|
| Q1 | requisito | RQ-02, RQ-04 | as 7 pastas de publish cru saem do repositório? | não nesta entrega; fora de escopo declarado; nenhuma entra na lista (P-01) |
| Q2 | requisito | RQ-02, RQ-04 | `command-center` com 1 view idêntica: pasta inteira? | pasta inteira (P-02) |
| Q3 | requisito | RQ-01, RQ-05 | patch `v0.45.1` depois do merge? | recomendado sim; a sessão não cria a tag sem a palavra do solicitante |
| Q4 | desenho | RQ-03 | varredura em `KitUpdateTest` ou `DuasRotasDeEntregaTest`? | `KitUpdateTest`, onde está a exclusão (D1) |
| Q5 | desenho | RQ-02 | entrada por pasta, arquivo ou raiz `resources/views/vendor`? | pasta (D2) |
| Q6 | desenho | RQ-03, RQ-04 | oráculo de autoria: conteúdo × vendor, git, ou lista à mão? | conteúdo × vendor instalado (D3, P-03, P-05) |
| Q7 | desenho (devolvida pela derivação do `04`) | P-03 | mesmo caminho relativo em dois pacotes / view sem par: compara com o quê? | idêntica a **algum** candidato = cru; sem par = autoral (já era a D3); as duas linhas de CT-02 fechadas |
| Q8 | desenho (devolvida pela re-derivação, step 9) | P-06 | numa linha `R100\told\tnew` do `git diff --name-status`, qual caminho é a chave? | comportamento **pré-existente** mantido (`preg_split` em 2 partes: a chave fica `old\tnew`); renome dentro da lista do kit é raro e não é desta correção; registrado, sem CT |
| Q4 | requisito (step 9) | RQ-01, RQ-02, RQ-05 | linha de comentário nas dez views autorais para a classe antiga entregá-las? | sim (P-07); alternativa documentada (duas rodadas com `--from`) se recusar |

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| caso *cobre todo o código do kit* na linha 158, guarda `.github` na 166 | `tests/Kit/KitUpdateTest.php:'cobre todo o código do kit':164` e `tests/Kit/KitUpdateTest.php:'.github':171` (`citacoes.sh` acusou a segunda) | `01`, Análise — citações reancoradas, 2026-10-06 |
| docs `atualizando-o-projeto` não enumeram `CAMINHOS_DO_KIT` | confirmado: `grep -n "views/\|CAMINHOS_DO_KIT"` só acha a citação `app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT:93` (que não desloca: as entradas entram depois da 93), um diagrama e um exemplo histórico com `resources/views/errors` | nenhuma — passo 4 confirmado |
| nenhum channel de log do `kit:update` | confirmado: `config/logging.php` tem só `ai`, `tenancy`, `autenticacao` e `configuracoes` | nenhuma — D4 confirmada |
| `caminhosDeclaradosEm()` tem caso que compara o fonte desta versão com a constante | confirmado: `tests/Kit/KitUpdateTest.php:caminhosDeclaradosEm:414` (`toBe(caminhosDoKit())`) — a forma `        'caminho',` é obrigatória | nenhuma — passo 1 já exige a forma |

### Varredura da classe irmã (step 5)
Nenhuma classe nova nesta entrega. A "irmã" relevante é a **entrada** `'resources/views/svg'` de `CAMINHOS_DO_KIT`: ela aparece em `app/Console/Commands/KitUpdate.php` (constante e dois comentários), em `tests/Kit/KitUpdateTest.php` (dataset da fundação não a cita; a varredura de `DIRETORIOS_DE_CODIGO` a cobre) e em `tests/Kit/DuasRotasDeEntregaTest.php` (comentário da ocorrência 1). Conferido em 2026-10-06 com `grep -rn "resources/views/svg" app tests config docs README*`: 8 ocorrências, todas em **comentário ou prosa** (`KitUpdate.php` 208/229/248/689, `KitUpdateTest.php` 86/125, `DuasRotasDeEntregaTest.php` 23, docs pt/en 80/81). Nenhum inventário, seeder ou config lista entradas da constante: a entrada nova só precisa existir na constante e no comentário dela.

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `shrink:` comentário do passo 1 "no tom dos vizinhos" (15 linhas) → teto de 6 | sim | `01`, passo 1 |
| 2 | `shrink:` D3 tratava "view sem original" como caso à parte → cai na mesma expressão, zero ramo | sim | `01`, D3 |
| — | `02`: Lean already (nenhuma ADR, Superfície não exigida) | — | — |

`/ponytail:ponytail-review` rodado em linha pela skill do projeto (`.claude/skills/ponytail-review`), 2026-10-06: `net: -10 lines possible.`

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| — | 0 | Sem despacho — captura verbatim do requisito (exceção declarada) | sessão | — | `00` gerado do corpo bruto do issue com marcadores nos caracteres de controle | — | — |
| — | 3 | Sem despacho — auditoria de `resources/views/vendor` por script (tarefa de 1–2 passos) | sessão | — | tabela de 12 pastas: 5 autorais, 7 cruas | — | 3 pastas conferidas por `git log` do arquivo |
| 1 | 7 | `general-purpose` — seguir `feature-test-design`, derivar o `04` | opus (explícito) | `01` inteiro (só paths, stack e "Sem superfície de UI" colados no prompt), `02`, `03`, conversa, código da correção | `04` gravado: 11 CT, 7 regras, 23 mutantes, 4 costuras (unit de regra), 1 pergunta de desenho (Q7), `## Sem CT-B` | 139,3 k tokens · 281 s | `git status --porcelain`: só `?? …/04-casos-de-teste.md`; `grep -o "\[CT-[0-9][0-9]\]" \| sort -u \| wc -l` = 11 = cabeçalho; `rastreabilidade.sh` e `citacoes.sh` exit 0; amostrados CT-02 (7 partições isoladas), CT-09 (procedural com junit — aceito) e CT-11 (arquivo inteiro, regra do CHANGELOG) |
| 2 | 7 | `fw-adversario-ct` — provar que o `04` deixa passar defeito | opus | `01`, `02`, `03`, conversa, código | 21 achados: 6 bloqueantes (pasta amarrada ao pacote pelo nome; iteração a partir do pacote; listagem não recursiva; M7/M5/M6 não morriam), 15 cosméticos; 19 aplicados no `04` (14 CT, 33 mutantes, 1 sem matador), 2 rejeitados com motivo (ADV-09 caixa no Windows, ADV-10 leitor antigo) — tabela em `04` → `## Revisão adversarial` | 55,6 k tokens · 210 s | `git status --porcelain` igual antes/depois (só leu); 3 bloqueantes reproduzidos de cabeça contra o script do step 3 (ele procurava o par por caminho relativo em todos os pacotes — ADV-01 não o atingia, mas atingiria uma implementação ingênua); `git ls-files resources/views/vendor` sem colisão de caixa (ADV-09) |
| 3 | impl. | `fw-executor-ct` — CT-01…CT-14 em `tests/Kit/KitUpdateTest.php` a partir do `04` | opus (sobrepõe o sonnet do agente: fixtures em disco, `git archive` no Windows, meta-caso sobre o próprio fonte) | `01`, `02`, `03`, conversa | 83/83 no arquivo (24 casos do lote com as linhas de dataset), 0 vermelho; 5 ambiguidades do `04` resolvidas e declaradas (`.gitkeep` sempre acha par; "coberta" por direção; lista não vazia nos "fora da lista"; CT-14 compara com fim de linha normalizado; CT-04 "não sugere listá-la" = sem "liste") | 115,7 k tokens · 238 s | `git diff --stat`: só `tests/Kit/KitUpdateTest.php` (+402/−3) além dos arquivos que a sessão já tinha tocado; `ids-ct.sh` acusou CT-01 (dataset sem `[CT-01]`) → a sessão trocou a linha por chave `[CT-01] …` no `->with`; rerodado pela sessão: 83/83, 133 asserções; `pint --dirty` passed |
| 4 | 9 | `general-purpose` — passe genérico sobre `main...HEAD` (fallback: `/code-review` não existe neste host) | opus (explícito) | `01`, `03`, conversa, `wikis/` | 9 achados: 1 major (CR-01), 7 minor, 1 nit; 15 itens considerados e rejeitados com motivo; rodou `KitUpdateTest` (83/83) e conferiu linha a linha as citações tocadas | 107,1 k tokens · 255 s | não editou nada (`git status` limpo depois); CR-01 reproduzido pela sessão lendo `arquivosAlterados()` (`diff --name-status origem destino -- lista`) e `git log -- resources/views/vendor/filament-auth-designer` (último commit `c6d900d`, v0.43.0); CR-02 conferido: `grep -n 'function preVoo'` = 485 contra `:preVoo:473` na doc |
| 5 | 9 | `fw-revisor-diff` — eixos sobre `main...HEAD` sem `wikis/` | opus | `01`, `03`, conversa | 6 achados, 0 bloqueantes (RD-01 major = CR-01; RD-02/03 citações; RD-04 comentário × código; RD-05 mensagens; RD-06 disco × git); 8 rejeitados com motivo; 4 itens não verificados declarados (âncoras em `wikis/*.md`/README — o hook negou o Grep na raiz; tags `kit-v*` ausentes localmente; CT-16 do issue; suíte completa) | 101,1 k tokens · 324 s | `git status --porcelain` vazio antes e depois; RD-04 reproduzido: `ls vendor/*/*/resources/views/.gitkeep` lista 17 pacotes; RD-06 confirmado em `tests/Kit/DuasRotasDeEntregaTest.php:diretoriosRastreados():242` (docblock "Derivar do git corrige a classe"); as âncoras que ele não verificou a sessão varre no passo 5 |
| 6 | 9 | `general-purpose` — `feature-test-design` reinvocada para P-06 e os CTs alterados pelos achados | opus (explícito) | `01` (só as assinaturas novas), `02`, `03`, conversa, código | `04`: 17 CT, 8 regras, 42 mutantes, 1 sem matador; R8/CT-15/CT-16 novos, CT-17 procedural, CT-04/06/07/08/12/14 alterados com marca; 1 pergunta de desenho (Q8) | 148,3 k tokens · 324 s | `git status`: só o `04`; `grep -o '\[CT-[0-9][0-9]\]' \| sort -u \| wc -l` = 17 = cabeçalho; `rastreabilidade.sh` exit 0 depois (P-06 com CT); amostrados CT-15 (4 linhas, mata `array_diff` invertido e origem vazia) e CT-04 (coluna `<saida>`) |
| 7 | impl. | `fw-executor-ct` — CT-15/16 novos, CT-04/06/07/08/12/14 alterados; depois, por `SendMessage`, o `it()` do CT-18 | opus (mesmo motivo do despacho 3) | `01`, `02`, `03`, conversa | 91/91 e depois **92/92** (148 asserções), 0 vermelho; 5 ambiguidades resolvidas e declaradas ("sem pacote" = nenhum par; exata+ancestral → mensagem da ancestral; CT-08 por `scandir` + `git ls-files`; CT-16 `T` sem origem; CT-18 por continência) | 113,9 k + 117,9 k tokens · 207 s + 55 s | `git diff --stat`: só `tests/Kit/KitUpdateTest.php`; `ids-ct.sh` exit 0 depois do CT-18; mutantes E/F/D medidos pela sessão; amostrados CT-15 (`toBe` estrito com ordem) e CT-04 (`<saida>` por célula) |
| — | pré-9 | Sem despacho — re-varredura da `## Superfície Livewire`: não exigida (`git diff main...HEAD --stat -- app/Filament app/Livewire` vazio) | sessão | — | — | — | — |

## Blockers
- nenhum

## Desvios do Plano
- **Passo 5 novo (P-06)**: o plano original só acrescentava a constante; o step 9 (CR-01 = RD-01) mostrou que a entrada nova não entrega arquivo que não mudou desde a origem. `01` ganhou o passo 5, D6 e a linha de P-06 na Cobertura; `00` ganhou P-06; `04` ganhou R8 (CT-15…CT-17).
- **Passo 6 novo (P-07)**: a classe antiga do `kit:update`, que roda na primeira rodada, não tem a P-06 e não avisa quando a lista do destino é lida — as dez views autorais mudam nesta release. `01` passo 6, `00` P-07 e Q4, `04` R9/CT-18.
- **Passo 1, comentário**: 7 linhas, não 6 (teto do ponytail) — a última nomeia o teste que decide; e o texto diz "de toda view de mesmo caminho relativo nos pacotes instalados", não "do pacote" (CR-06/RD-04). `01` passo 1 segue válido (a entrada e a forma não mudaram).
- **Passo 2, enumeração**: árvore real pelo `git ls-files`, não pelo disco (RD-06 → D5); CT-08 por referência independente (CR-07); mensagens por célula (RD-05). `01` passo 2 descreve a varredura sem fixar a enumeração — sem contradição; D5 registra.
- **Passo 3**: além das 20 citações `KitUpdate.php:…:linha`, 11 âncoras curtas `(:símbolo:linha)` do parágrafo do `handle()` nas docs pt/en e 4 citações curtas pré-existentes erradas na `main` (CR-02…05, CR-09) — todas reancoradas pelo símbolo; e um segundo recálculo depois do `shrink` do ponytail no código (−4 linhas após `caminhosNovosNaLista`).
- **Ordem do step 9**: a revisão rodou sobre o diff dos passos 1–4; os passos 5 e 6 nasceram dela. Não houve segunda rodada de revisão do diff sobre eles (teto de 2 rodadas; o quality gate os lê) — a mitigação são os mutantes E/F/D e o `--dry-run` real em três configurações.

## Notas de Implementação
- **`kit:update` roda a classe instalada**: toda correção no comando só vale a partir da rodada seguinte à que a entrega. Correção de entrega que precise valer "já" tem de estar nos **dados** (aqui: o conteúdo das views), não só no código. É o que P-07 faz, e é a razão de a P-06 ser para a próxima pasta, não para esta.
- **O junit do Pest 5 não carrega o motivo do `skip`**: o atributo `message` do `<skipped>` vem vazio. Prova de "pulou com motivo" é o fonte (CT-13), não o junit.
- **`git archive` + `tar` no Windows** funciona por `Process::fromShellCommandline` com `escapeshellarg` nos dois lados (CT-14); não foi preciso o plano B com zip.
- **Extração como projeto**: `git add -A` numa extração grande falhou com "unable to index file" até `core.longpaths=true`; depois disso o `kit:update --repo={caminho local}` aceita o próprio repositório do kit como remote, e tags locais (`v0.45.1-rc*`) servem de destino — é o jeito barato de medir o cenário 3 antes da tag. As duas tags temporárias são locais e serão apagadas antes do release.
- **Script de edição em lote**: `open(p, "w").write(fn(t))` truncou `ChecklistDeReleaseTest.php` quando `fn` lançou (o `open` com `w` avalia antes do argumento). Recuperado por `git checkout`; o padrão passou a `novo = fn(t)` antes de abrir para escrita (memória da sessão).

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-06
- `template-01-plano.md` — step 4 — 2026-10-06
- `template-02-adr.md` — step 4 — 2026-10-06
- `template-03-progresso.md` — step 4 — 2026-10-06
- `entrevista-tres-raias.md`, `pesquisa-step-3.md`, `citacoes-de-codigo.md`, `roteamento-e-despacho.md`, `delegacao-casos-de-teste.md`, `padrao-de-log.md`, `estrutura-criada.md` — lidas nesta mesma sessão para a feature anterior (`deploy-multiambiente-docker`, 2026-10-05); não reabertas: o conteúdo está em contexto

## Retrospectiva
- **Funcionou bem**: a auditoria por conteúdo no step 3 (5 autorais × 7 cruas) virou o oráculo do teste sem retrabalho; a revisão adversarial, despachada além do critério, pegou três implementações plausíveis que passariam (pasta amarrada ao pacote, iteração a partir do pacote, listagem não recursiva); o step 9 pegou o defeito que invalidaria a entrega (CR-01 = RD-01) antes do PR; o `--dry-run` real com `--repo` local provou as três configurações em minutos.
- **Faltou no plano**: ler `arquivosAlterados()` e o aviso de segunda rodada no step 3 — o plano assumiu que "entrar na lista" bastava, e isso é exatamente a premissa que o próprio `KitUpdate.php` documenta como falsa (0.22.3 → 0.24.1). O passo 5 e o passo 6 nasceram do step 9 e custaram uma re-derivação, um segundo lote de testes e dois recálculos de citações.
- **Para a próxima**: toda correção de "arquivo que não chega pelo `kit:update`" começa pela pergunta "o diff tag→tag desse arquivo é vazio para quem já está na versão em que ele nasceu?"; se for, ou o arquivo muda na release, ou o mecanismo muda — e o mecanismo só vale na rodada seguinte.
