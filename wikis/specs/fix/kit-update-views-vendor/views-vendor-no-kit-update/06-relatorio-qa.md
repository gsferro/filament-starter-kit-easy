# Relatório de QA — Issue #148: `kit:update` não entrega os overrides autorais de `resources/views/vendor`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão
> Natureza da wiki: correção · Toca infra compartilhada: sim → `KitUpdate::CAMINHOS_DO_KIT` e a varredura de `tests/Kit/KitUpdateTest.php` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 3 (último do teto)

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 1 · Cosmético: 0
- Não verificadas: J (sem o passo `--tia`; a regressão de browser deste ciclo foi interrompida pelo host) e K (sem o K2). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Q1 e Q3 seguem `aberta`, mas nenhuma `RQ` desta entrega depende delas
- Ambiente: app em `http://127.0.0.1:8097` (não usado: a entrega não tem UI) · Pest 5 · MCP: não usado
- **Convergência**: este é o 3º ciclo. Pela regra 3, o loop para aqui e a decisão passa ao usuário. Os três achados são só de texto da wiki (destino 1). Nenhum pede mudança de código ou de teste, e a correção de código do QA-08 está confirmada.

## Conferência dos ciclos anteriores

| Achado | Ciclo | Sev. | Estado no ciclo 3 | Evidência |
|---|---|---|---|---|
| QA-01 a QA-03, QA-05 a QA-07 | 1 | Major/Minor/Cosm. | **fechados** (no ciclo 2) | Não reabertos. O texto está no ciclo 2 (`1db1cef`, este arquivo) |
| QA-04 — "uma constante e um teste" no D4 do `01` e no `04` | 1→2 | Minor | **fechado** | `grep -rn "constante e um teste" {wiki}` traz só as linhas marcadas `*(alterado em … QA-04 …)*`: `01` Objetivo e `04` `## Sem CT-B`. O D4 agora traz o motivo novo, com a marca |
| QA-08 — renome `R`/`C` virava "novo no kit" | 2 | Minor | **fechado** | `rotularDiff()` só aplica o callable a `$status === 'M'` (`app/Console/Commands/KitUpdate.php:rotularDiff:713`, condição na linha 738). Sondagem por `php -r` com o callable `is_file`: `R061 …`, `C100 a.php b.php` e `T p/link.php` saem "modificado", e `M p/nao-existe.php` sai "novo no kit". CT-16 tem a linha `R100` (M46). `pest tests/Kit/KitUpdateTest.php` passou 96/96, com 152 asserções |
| QA-09 — índice do `04` sem M44/M45; "Q4" no `03` | 2 | Cosm. | **fechado** | O índice traz `CT-16 … M35, M45, M46` e o CT-18 traz M43/M44. Em `## Desvios` do `03` está "Q9 (era Q4 até o QA-01)" |

## Achados

### QA-10 — a `## Verificação Final` diz que o `citacoes.sh` "acusa só essas quatro do `06`", mas ele acusa cinco · Major · destino 1

- **Dimensão**: L6 (alegação do `03` com número)
- **Relacionado a**: `03` `## Verificação Final`, item das citações (linha 45)
- **Esperado**: todo número de um `[x]` sai de um comando que o reproduz (L6; proibição 11).
- **Observado**: o item diz que o `06` do ciclo 2 *"cita quatro símbolos de `KitUpdate.php` pelas linhas que valiam antes da correção do QA-08 … e acusa só essas quatro do `06`"*. O script acusa **cinco**. A quinta é `tests/Kit/KitUpdateTest.php:tagAnteriorNoCheckout:1092`, porque o `1db1cef` acrescentou 6 linhas ao CT-16 (a linha `R100`) e a função foi para a linha 1098. A versão anterior do mesmo item, no `1db1cef`, dizia *"rerodado: vazio, exit 0 sobre `00`–`06`"*, e isso também era falso naquele commit.
- **Repro**: `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki}` sai com exit 1 e 5 linhas, todas do `06` (`:1092`, `:737`, `:895`, `:1025`, `:1222`). `grep -n "function tagAnteriorNoCheckout" tests/Kit/KitUpdateTest.php` dá 1098.
- **Evidência**: a saída acima. `grep -rn "essas quatro" {wiki}` acha 1 linha, no `03:45`.
- **Destino**: 1
- **Ação exigida**: trocar "quatro" pelo que o script devolve (cinco: quatro de `KitUpdate.php` e uma de `KitUpdateTest.php`) no `03:45`. Quando a sessão gravar este `06`, as cinco somem, porque este arquivo não cita linha. Nesse caso o item deve dizer o resultado da nova execução.

### QA-11 — o passo 5 do `01` ainda descreve o defeito do QA-08 ("rótulo modificado" em vez de "status `M`") · Major · destino 1

- **Dimensão**: L3 (PRD × código; *"procure a cópia antes de fechar"*)
- **Relacionado a**: passo 5 e D8 do `01`, P-08, QA-08, M46
- **Esperado**: depois do QA-08, o PRD inteiro diz o que o código faz. Só o status `M` com arquivo ausente vira "novo no kit"; `R`, `C` e `T` ficam "modificado".
- **Observado**: o D8 foi corrigido (`01:92`, *"Só `M`"*). O passo 5, porém, ainda diz *"com o `callable`, arquivo com rótulo "modificado" que não existe no projeto vira "novo no kit" (P-08, D8)"* (`01:194`), e a única marca dele é do QA-03. "Rótulo modificado" é o ramo `default` do `match`, e esse ramo inclui `R`, `C` e `T`. Era exatamente a condição do mutante H (`$rotulo === 'modificado'`). Quem executar o `01` pelo passo 5 recoloca o defeito. O CT-16 `R100` o pegaria, mas o PRD e o código se contradizem, e o próprio `01` também.
- **Repro**: `sed -n 194p {wiki}01-plano-acao.md | grep -o 'arquivo com rótulo "modificado"[^(]*'` e `sed -n 738p app/Console/Commands/KitUpdate.php`, que mostra `if ($status === 'M' && …`.
- **Evidência**: `grep -rn 'rótulo "modificado"' {wiki}` dá só o `01:194`.
- **Destino**: 1
- **Ação exigida**: reescrever a frase do passo 5 (`01:194`) para "status `M`", com a marca `*(alterado em … QA-08)*`.

### QA-12 — o `04` (R8, "Cogitado e cortado") e o `03` (Q8) ainda dizem que a linha `R100` não existe no CT-16 · Minor · destino 1

- **Dimensão**: L1 e L3 (`04` e `03` × teste)
- **Relacionado a**: CT-16, M46, Q8
- **Observado**:
  - Em `## Cogitado e cortado` do `04:525`, a *"linha `R100\told\tnew` no CT-16"* aparece como cortada (*"fixar um seria inventar o oráculo"*). O QA-08 a acrescentou, e o dataset tem a linha `QA-08: R100 …`. Falta a marca.
  - O comentário do CT-16 no `04:394` diz *"Duas linhas a mais nos Exemplos"*. São três linhas de P-08 (o M45 do mesmo `04` diz *"as três linhas de P-08"*), mais a `R100`.
  - O cabeçalho da R8 no `04:376` lista as células da tabela de decisão sem a P-08 ("M + ausente") e sem a restrição a `M`.
  - Na Q8, no `03:114`, consta *"registrado, sem CT"*. Hoje o CT-16 `R100` fixa a chave `old\tnew`.
- **Repro**: `grep -n "Duas linhas a mais\|linha \`R100" {wiki}04-casos-de-teste.md`, `grep -n "registrado, sem CT" {wiki}03-progresso.md` e `grep -n "R100" tests/Kit/KitUpdateTest.php`, que acha a linha do dataset.
- **Destino**: 1
- **Ação exigida**: tirar a `R100` de "Cogitado e cortado" (ou marcá-la como revertida pelo QA-08), corrigir "Duas" para "Três" (mais a `R100`), acrescentar as células P-08/`M` ao cabeçalho da R8 e trocar "sem CT" por "CT-16 linha `R100` (QA-08)" na Q8. Tudo com a marca de data.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0, sem `07-tickets/`. No `git diff main...HEAD` por passo, os passos 1 a 6 batem. P-08 → passo 5/D8 → CT-16 → `rotularDiff`, só no `M`. RQ-05 de ponta a ponta: `--dry-run` reproduzido (ver L6). O texto do passo 5 está no QA-11 |
| B | Fronteiras e dados | ✅ | Sondagem de `rotularDiff` com `R061`, `C100`, `T`, `M100`, `M` ausente/presente e linha com espaço inicial: todas coerentes com o D8. No diff tag→tag desta release (`v0.45.0`→`HEAD` e →`v0.45.1-rc`, filtrado pela lista) só há `M` (17 linhas). Nenhum renome em jogo |
| C | Matriz de permissão | n/a | Sem papel, rota nem policy. O diff de `app` é só `KitUpdate.php` |
| D | Observabilidade | ✅ | D4 com o motivo novo. `git diff main...HEAD -- app \| grep -c "Log::\|logger("` = 0. O console do dry-run não tem PII |
| E | Performance | ✅ | Um `is_file` por arquivo rotulado. O dry-run completo leva 26 s na extração, quase tudo `git fetch` |
| F | UX de erro | ✅ | O `aplicado:` falso do QA-08 deixou de ocorrer, porque o `R` não vira mais "novo no kit". Mensagens em pt |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` exit 1 (Filament). Nível 1: `dark-mode.sh` exit 1, uma linha (`text-white` em `clear-cache-button.blade.php:67`), pré-existente, sobre `bg-danger-500`: falso positivo. O diff de view é só comentário Blade |
| H | Acessibilidade | n/a | Sem UI no `01`. As linhas novas das views são comentário (`'@'` = 0) |
| I | Segurança da superfície nova | ✅ | O step 9 cobriu os passos 1 a 4 (9 + 6 achados, 2 rejeitados). Além dele, os ciclos 1 a 3 cobriram os passos 5 e 6, a P-08 e o QA-08. `is_file(base_path(...))` só lê. `Process` recebe array. IDOR, rota, mass assignment, upload e `DB::raw`: n/a |
| J | Regressão adjacente | ⏭️ | Em série no HEAD `4c5060d`: KitUpdate 96, Citacoes 3, Checklist 26 (3 pulados), Constraint 13, Deploy 5, DuasRotas 3, HelpersDeTeste 1, QualidadeDeCodigo 29, ArquiteturaDoCodigo 3, Diagramas 528. Total: **707 verdes, 0 falhas**. `LogoDarkModeTest`, `BotaoLimparCacheTest` e `PaginasInfraTest` foram interrompidos pelo host por falta de memória. As entradas deles (as views) não mudaram desde `cae9009` (`git diff --name-only cae9009 HEAD -- resources/views` = 0), onde o ciclo 2 os rodou verdes. `--parallel --tia` não rodou |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, uma linha (`KitUpdateTest.php:374`, caso pré-existente; rejeitada de novo). O M46 é morto pela linha `R100` (oráculo exato `toBe`). K2 fora do perfil. Revisão adversarial do `04`: feita |
| L | Consistência documental | ❌ | 3 achados (QA-10, QA-11, QA-12). L1: `ids-ct.sh` exit 0; cabeçalho do `04` = `grep -c` (18/9/46/1). L2: `citacoes.sh` exit 1, as 5 linhas só no `06` do ciclo 2 (ver QA-10); nenhuma no `00`–`04`. L4: `conformidade-rules.sh` exit 0; `views.md` conferida (`'@'` = 0). L5: docs pt e CHANGELOG coerentes com o código (não falam de renome). L6: `checkbox-sem-evidencia.sh` exit 0, mais os números (abaixo). L7: os 3 termos novos do glossário batem com o `00`/`01` |

**L6, números reproduzidos**: `pest tests/Kit/KitUpdateTest.php` deu 96/96 e 152 asserções (`03:29`); `--filter='CT-'` deu 43 (`03:29`, "43 casos"); CT-16 tem 8 linhas (`03:29`, "×8"). `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `ids-ct.sh` e `conformidade-rules.sh` saíram com exit 0 (`03:43`, `03:44`, `03:46`, `03:47`). Contra o `03:45`: o `citacoes.sh` dá 5, não 4 (QA-10). O mutante H (`03:40`) foi conferido por leitura e pela sondagem: a linha `R100` com o callable falso devolve "modificado" no código e "novo no kit" na condição mutada. **Dry-run reproduzido**: na extração (classe anterior ao QA-08), `kit:update --repo={kit} --tag=0.45.1-rc --dry-run --no-interaction` (26 s) devolveu `media.blade.php novo no kit` e as outras nove `modificado`, igual ao `e2e-p08.log`. A extração ficou com `git status` limpo depois. Como o diff dessa release só tem `M`, a classe anterior e a atual dão a mesma saída. Nenhuma degradação declarada no `03`. As células de `Custo` estão preenchidas.

## Débitos Aceitos

- Nenhum novo. Os do ciclo 2 (QA-04, QA-08, QA-09) estão fechados.

## Suspeitas Não Confirmadas

- Status `T` (*typechange*) de arquivo ausente no projeto continua "modificado", e o `--only-new` não o aplica. O D8 diz "só `M`", e nenhum CT fixa `T` + ausente. É coerente com a decisão e raro (troca entre symlink e arquivo). Não vejo defeito.
- Herdada do ciclo 2: a P-08 recria pelo `--only-new` um arquivo do kit que o projeto apagou de propósito. É coerente com a opção e está declarado.
- Herdada do ciclo 2: no CI, o CT-18 pula sempre (checkout raso). Declarado no `04`. Não medi no CI.

## Não Verificado

- J: o passo 1 (`pest --parallel --tia`) não rodou por restrição do host (memória; o orquestrador proibiu a suíte completa e o `--parallel`). Na execução por ID, `LogoDarkModeTest`, `BotaoLimparCacheTest` e `PaginasInfraTest` foram interrompidos pelo host por memória baixa e não foram reiniciados. Mitigação: as views que eles leem não mudaram desde o ciclo 2, que os rodou verdes.
- K2 (`--mutate`): fora do perfil padrão. O `pest-plugin-mutate` neste Windows tem duração implausível, segundo o host. O M46 foi conferido por leitura e sondagem, não por mutação medida.
- RQ-05 de ponta a ponta (aplicar e rodar `LogoDarkModeTest` CT-16 no projeto atualizado) não foi feito, porque aplicar alteraria a extração. Só o `--dry-run` foi reproduzido.
- A classe com a correção do QA-08 não foi exercitada pelo comando real na extração, que tem a classe anterior commitada, e copiar a nova alteraria o diretório. O equivalente para esta release está provado acima: o diff só tem `M`.
