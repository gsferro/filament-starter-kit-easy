# Relatório de QA — Issue #148: `kit:update` não entrega os overrides autorais de `resources/views/vendor`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão
> Natureza da wiki: correção · Toca infra compartilhada: sim → `KitUpdate::CAMINHOS_DO_KIT` e a varredura de `tests/Kit/KitUpdateTest.php` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 2 · Cosmético: 1
- Não verificadas: J (falta o passo `--tia`) e K (falta o K2). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Q1 e Q3 continuam `aberta`, mas nenhuma `RQ` desta entrega depende delas (Q1 é escopo novo, Q3 é a tag)
- Ambiente: app em `http://127.0.0.1:8097` (não usado, porque a entrega não tem UI) · Pest 5 · MCP: não usado (não há superfície de navegador)

## Conferência do Ciclo 1

| Achado (ciclo 1) | Sev. | Estado | Evidência no ciclo 2 |
|---|---|---|---|
| QA-01 — perguntas de requisito abertas, mas já implementadas; Q4 duplicada | Major | **fechado** | Q2 e a antiga Q4 (agora Q9) foram reclassificadas como desenho, com motivo, e viraram D2 e D7 do `01`. Q1 e Q3 estão com `Afeta` = nenhuma `RQ`, e nenhum passo depende delas. A reclassificação da Q2 é legítima: o próprio issue raciocina por pasta (*"overrides em ai-tasks, command-center…"*), e a P-02 registra o arquivo idêntico que vai junto. Sobra uma menção a "Q4" no `03` (QA-09) |
| QA-02 — CT-18 falha no checkout raso do CI | Major | **fechado** | `tagAnteriorNoCheckout()` usa `git rev-parse --verify --quiet v0.45.0^{commit}`, e o CT-18 ganhou um segundo `->skip` (`tests/Kit/KitUpdateTest.php:tagAnteriorNoCheckout:1092`). O Pest 5 encadeia os dois `skip` com `addWhen` (lido em `vendor/pestphp/pest/src/PendingCalls/TestCall.php`), então os dois valem. M44 foi medido pela sessão: na extração sem tags, com `.github`, o resultado é `skipped` |
| QA-03 — view ausente sai "modificado", e o `--only-new` nunca a aplica | Major | **fechado** | P-08, D8 e CT-16 ganharam 3 linhas (M45). `rotularDiff` recebe o callable de existência (`app/Console/Commands/KitUpdate.php:existeNoProjeto:737`). **Reproduzido**: na extração, `kit:update --tag=0.45.1-rc --dry-run` com a classe nova deu `media.blade.php novo no kit` e as outras nove `modificado`, igual ao `e2e-p08.log`. Com a classe antiga (primeira rodada), a limitação está declarada no CHANGELOG e nas docs pt/en |
| QA-04 — `01` e `02` sem os passos 5 e 6 | Minor | **em parte** | Todas as linhas citadas foram corrigidas e marcadas. Ficou uma cópia da frase que o gate do ciclo 1 não listou: D4 do `01` (*"a correção é uma constante e um teste, sem caminho de execução novo"*) e `## Sem CT-B` do `04`. Ver QA-04 abaixo |
| QA-05 — teto de pulados "sobe 3" | Minor | **fechado** | O `04` diz "sobe em 6". `grep -c` dá 6 guardas `! naArvoreDoKit()` novas |
| QA-06 — referências `final`, `dois` e nome do arquivo | Cosm. | **fechado** | M3 → `borda`; taxonomia → `painel`/`painel2`; Q2 → `pages/history.blade.php` |
| QA-07 — checkboxes do step 9/10 em aberto | Cosm. | **fechado** | Os itens estão fechados com evidência. `checkbox-sem-evidencia.sh` sai com exit 0 |

O texto integral do ciclo 1 está no histórico do git (`cae9009`, este mesmo arquivo).

## Achados

### QA-04 — "uma constante e um teste" sobrevive no D4 do `01` e no `04` · Minor · destino 1 (resto do ciclo 1)

- **Dimensão**: L3
- **Relacionado a**: D4, passos 5 e 6, P-06, P-08
- **Esperado**: uma afirmação do PRD ou do `04` que o código contradiz leva a marca `*(alterado em …)*`. A regra *"procure a cópia antes de fechar"* vale para todos os arquivos.
- **Observado**: D4 do `01` justifica "nenhum channel de log" com *"a correção é uma constante e um teste, sem caminho de execução novo"*. Os passos 5 e 6 acrescentaram um caminho de execução: um `git show`, um segundo `git diff` e um `is_file` por arquivo rotulado. A mesma frase está em `## Sem CT-B` do `04`. A decisão de não ter channel continua válida; o que ficou velho é o motivo.
- **Repro**: `grep -rn "constante e um teste" wikis/specs/fix/kit-update-views-vendor/views-vendor-no-kit-update/` dá 3 linhas: `01` Objetivo (já marcada), `01` D4 e `04` `## Sem CT-B`.
- **Destino**: 1
- **Ação exigida**: reescrever o motivo do D4 (o comando já imprime o resumo por arquivo e o `aplicado:`) e a frase do `04`, com a marca de data nas duas.

### QA-08 — P-08 muda o rótulo de linha de renome (`R`/`C`) para "novo no kit", e o `--only-new` passa a "aplicar" um caminho que não existe · Minor · destino 1

- **Dimensão**: B (sondagem) e A
- **Relacionado a**: P-08, D8, Q8, CT-16, `rotularDiff`, `revisarEAplicar`
- **Esperado**: segundo a P-08, só *"arquivo que o projeto não tem"* troca de rótulo. Pela decisão da Q8, a linha `R` continua com o comportamento pré-existente: rótulo "modificado" e chave `old\tnew`, sem efeito no `--only-new`.
- **Observado**: o callable roda sobre todo rótulo "modificado", e isso inclui `R`, `C` e `T`. Numa linha de renome a chave é `old\tnew`, que nunca é arquivo, então ela sempre vira "novo no kit". Com `--only-new`, `revisarEAplicar` (`app/Console/Commands/KitUpdate.php:revisarEAplicar:895`) passa a chamar `aplicar()` (`app/Console/Commands/KitUpdate.php:aplicar:1025`). O `git checkout {destino} -- "old\tnew"` falha, mas `git()` (`app/Console/Commands/KitUpdate.php:git:1222`) ignora o exit e devolve só o stdout. O comando imprime `aplicado: old\tnew`, conta o arquivo no `confirmarLote` como novo que "não sobrescreve nada" e marca a versão. Antes da P-08 o `--only-new` pulava a linha. Hoje não há renome entre v0.45.0 e esta versão, mas o histórico tem um dentro da lista (v0.19.8→v0.19.9).
- **Repro**:
  1. `php -r 'require "vendor/autoload.php"; $s="R061\tpublic/images/auth/login.svg\tresources/views/svg/arte-do-login.blade.php\n"; var_export(App\Console\Commands\KitUpdate::rotularDiff($s, true)); var_export(App\Console\Commands\KitUpdate::rotularDiff($s, true, fn(string $c): bool => is_file($c)));'` dá `'modificado'` sem o callable e `'novo no kit'` com ele.
  2. Com a lista lida por `caminhosDeclaradosEm`, `git diff --name-status v0.19.8 v0.19.9 -- {lista}` dá `R057 app/Http/Controllers/Auth/LoginComGoogleController.php app/Http/Controllers/Auth/LoginSocialController.php`.
  3. `git ls-tree --name-only v0.19.9 -- "$(printf 'old\tnew')"` dá 0 linhas: o pathspec não casa com nada.
- **Evidência**: as saídas acima. CT-16 não tem linha `R` + callable.
- **Destino**: 1. A Q8 precisa considerar a interação com a P-08.
- **Ação exigida**: decidir no `01`/`00` entre duas saídas. Uma é restringir o callable ao status `M`, que é o que a P-08 descreve. A outra é tratar a chave de renome, o que reabre a Q8. Depois, a linha no CT-16 (`feature-test-design`), e por último o código.

### QA-09 — Índice do `04` e `## Desvios do Plano` do `03` com referências velhas · Cosmético · destino 1

- **Dimensão**: L1 e L3
- **Observado**:
  - Na coluna `Mata` do `## Índice de Cenários` do `04`, o CT-16 lista só M35, mas o M45 (P-08) também é morto por ele. O CT-18 lista só M43, mas o M44 (guarda da tag) também.
  - `## Desvios do Plano` do `03` ainda diz *"`00` P-07 e Q4"*. A pergunta é Q9 desde o QA-01.
- **Repro**: `grep -n "^| CT-16\|^| CT-18" 04-casos-de-teste.md` e `grep -n "P-07 e Q4" 03-progresso.md`
- **Ação exigida**: acrescentar M45 e M44 às linhas do índice e trocar Q4 por Q9 no `03`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0, sem `07-tickets/`. A auditoria do requisito fecha o QA-01. Pelo `git diff` por passo, os passos 1 a 6 batem com o diff, e P-08 → passo 5 → CT-16 → `rotularDiff`. RQ-05 de ponta a ponta continua só em dry-run (*Não Verificado*). QA-08 mexe numa interação P-08 × Q8 |
| B | Fronteiras e dados | ⚠️ | 1 achado (QA-08). A sondagem de `rotularDiff` por `php -r` com linha `R` e callable mostrou o problema. Também conferido: nenhum caminho da lista tem espaço ou caractere fora de ASCII (`git ls-files -- {lista}`: 0 e 0), então a chave citada pelo `core.quotepath` não ocorre hoje |
| C | Matriz de permissão | n/a | Sem papel, rota nem policy. O diff de `app` é só `KitUpdate.php` |
| D | Observabilidade | ✅ | `git diff main...HEAD -- app \| grep -c "Log::\|logger("` = 0, conforme o D4. A saída de console do dry-run não tem PII |
| E | Performance | ✅ | P-08 acrescenta um `is_file` por arquivo rotulado. O dry-run completo leva 26 s na extração, quase tudo `git fetch` |
| F | UX de erro | ⚠️ | A mensagem `aplicado:` sai falsa para o caminho de renome (QA-08). As mensagens da varredura seguem em pt e acionáveis |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` sai com exit 1 (Filament). No nível 1, `dark-mode.sh` sai com exit 1 numa linha só, `text-white` em `clear-cache-button.blade.php`, que é pré-existente e fica sobre `bg-danger-500` (falso positivo). O diff de view é só um comentário Blade, que não chega ao HTML |
| H | Acessibilidade | n/a | Sem UI no `01`. O diff de view é só comentário (`'@'` nas linhas novas = 0) |
| I | Segurança da superfície nova | ✅ | O step 9 cobriu os passos 1 a 4 (9 + 6 achados, 2 rejeitados). Além dele: o ciclo 1 cobriu os passos 5 e 6, e este ciclo cobriu a P-08. `is_file(base_path(...))` só lê, sobre caminho vindo do `git diff` da tag, filtrado pela lista. `Process` recebe array, sem shell. IDOR, rota, mass assignment, upload e `DB::raw`: n/a |
| J | Regressão adjacente | ⏭️ | Rodei por ID, em série e no HEAD `cae9009`, os 12 arquivos da regressão do `03` mais `HelpersDeTesteTest`: KitUpdate 95, DuasRotas 3, LogoDarkMode 24 (ancestral), BotaoLimparCache 3, PaginasInfra 36, CitacoesDeCodigo 3, ChecklistDeRelease 26 (3 pulados), ConstraintDeDependencia 13, DeployDockerLocal 5, DiagramasDaArquitetura 528, QualidadeDeCodigo 29, ArquiteturaDoCodigo 3, HelpersDeTeste 1. Resultado: **769 verdes, 4.543 asserções, 0 falhas**. `--parallel --tia` não rodou (*Não Verificado*). RCRCRC: `KitUpdate.php` é Core, Risk e Repaired |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` saiu com exit 1 numa linha, `KitUpdateTest.php` no caso `mantém a lista completa…`. É caso pré-existente e foi rejeitado de novo. O oráculo × ambiente do QA-02 está fechado. O K2 não rodou (fora do perfil). A revisão adversarial do `04` foi feita (`fw-adversario-ct`, 21 achados) |
| L | Consistência documental | ⚠️ | 2 achados (QA-04 resto, QA-09). L1: `ids-ct.sh` exit 0, e o cabeçalho do `04` bate com `grep -c` (18/9/45/1). L2: `citacoes.sh` exit 1, mas as 7 linhas estão todas no `06` do ciclo 1, que este arquivo substitui; nenhuma está no `00`–`04`. L4: `conformidade-rules.sh` exit 0; as 5 rules foram conferidas no código, e `HelpersDeTesteTest` está verde com os helpers novos do CT-18. L5: pt e en dizem o mesmo, e a frase da P-08 tem rastro. L6: `checkbox-sem-evidencia.sh` exit 0, mais os números reproduzidos (abaixo). L7: os 3 termos do glossário batem |

**L6, números reproduzidos**: `pest tests/Kit/KitUpdateTest.php` deu 95/95 e 151 asserções; `--filter='CT-'` deu 42. Também batem 47 testes com 3 pulados (Citacoes, Checklist, Constraint e Deploy), 558 (Duas, Logo, Botao e Diagramas), `grep -c "^        '"` = 84, `git diff --name-only v0.45.0 HEAD -- resources/views/vendor` = 10, `'@'` = 0 e `pint --test` passed. Os 765 testes e 4.539 asserções do `03` estão datados em `a93172c`. No HEAD deram 768 e 4.542, a diferença das 3 linhas da P-08, coerente com o registro. O "54 casos" do Impacto do `01` confere (95 − 41 novos). O dry-run da P-08 bate com `e2e-p08.log`. Nenhuma degradação foi declarada no `03`, e todas as células de `Custo` estão preenchidas.

## Débitos Aceitos

- QA-04 (Minor): o motivo do D4 e a frase do `04` ficaram velhos. Vai para o `03`
- QA-08 (Minor): rótulo de renome × P-08 e `aplicado:` falso no `--only-new`. Vai para o `03`
- QA-09 (Cosmético): o índice do `04` não traz M44/M45, e o `03` ainda fala em "Q4". Vai para o `03`

## Suspeitas Não Confirmadas

- A P-08 vale para toda a lista, não só para `resources/views/vendor`. Um arquivo do kit que o projeto **apagou de propósito** e que mudou entre as tags agora sai "novo no kit", e o `--only-new` o recria. Isso é coerente com o texto da opção (*"arquivos que ainda não existem no projeto"*) e com o modo sem origem, e a P-08, o CHANGELOG e as docs dizem isso. Não vejo defeito; registro para não reaparecer.
- No CI, o CT-18 vai pular sempre (checkout raso), então o M43 só morre localmente e o CI passa de 6 para 7 pulados. Isso é consequência da opção escolhida no QA-02 e está declarado no `04` (*"o pulo entra na contagem"*). Não medi no CI.

## Não Verificado

- J: o passo 1 (`pest --parallel --tia`) não rodou por restrição do host (memória; o orquestrador proibiu a suíte completa e o `--parallel`). Rodei por ID os 13 arquivos listados acima.
- K2 (`--mutate`) está fora do perfil padrão. Mutar `KitUpdate.php` inteiro, com cerca de 40 s de `KitUpdateTest` por mutante, não cabe no host. Os mutantes manuais A a G e M44 do `03` foram conferidos por leitura, porque reexecutá-los exigiria editar a árvore.
- RQ-05 de ponta a ponta (aplicar e rodar `LogoDarkModeTest` CT-16 no projeto atualizado) não foi feito, porque aplicar alteraria a extração. Só reproduzi o `--dry-run`.
- As rodadas com a classe antiga (controle `rc0` e candidata `rc`) não puderam ser reproduzidas, porque a extração está no commit da classe nova. Ficaram conferidas pelos logs, como no ciclo 1.
