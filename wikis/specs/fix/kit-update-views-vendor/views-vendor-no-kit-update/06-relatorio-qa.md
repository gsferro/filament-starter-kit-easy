# Relatório de QA — Issue #148: `kit:update` não entrega os overrides autorais de `resources/views/vendor`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão
> Natureza da wiki: correção · Toca infra compartilhada: sim → `KitUpdate::CAMINHOS_DO_KIT` e a varredura de `tests/Kit/KitUpdateTest.php` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa (o prompt trouxe um resumo de escopo de uma linha, "comando de console, uma constante, um teste e dez views com uma linha de comentário", e onde estão as tags rc, a extração e os logs e2e; não trouxe raciocínio nem justificativa de decisão)
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 3 · Minor: 2 · Cosmético: 2
- Não verificadas: J (sem o passo `--tia`), K (sem o K2). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma marcada `aberta`, mas Q1, Q2 e Q4 (raia requisito) estão `aberta` e afetam RQ-01, RQ-02, RQ-04 e RQ-05, que estão `fechada` e implementadas (QA-01)
- Ambiente: app em `http://127.0.0.1:8097` (não usado, porque a entrega não tem UI) · Pest 5.0.5 · MCP: não usado (sem superfície de navegador)

## Achados

### QA-01 — Perguntas de requisito abertas, mas a implementação já seguiu a recomendação do dev · Major · destino 1

- **Dimensão**: A (auditoria do requisito, passo 2)
- **Relacionado a**: Q1, Q2, Q4 · RQ-01, RQ-02, RQ-04, RQ-05 · P-01, P-02, P-07 · passos 1, 2 e 6
- **Esperado**: o comentário do próprio `00` em `## Perguntas ao Solicitante` diz: *"Enquanto aberta, a RQ afetada fica `aberta — Qn` e nenhum passo do 01 a implementa."*
- **Observado**: Q1, Q2 e Q4 estão `aberta` (`00-requisito.md` linhas 79, 80 e 82). As RQs que elas afetam estão `fechada` (linhas 65 a 69), e os passos 1, 2 e 6 implementam a recomendação: pasta inteira (P-02), as 7 pastas cruas fora da entrega (P-01) e a linha de comentário nas dez views (P-07). O `03` (linha 86) diz que *"o solicitante confirma ao ler o PR"*. Como as RQs não estão marcadas `aberta`, o `rastreabilidade.sh` não acusa o caso.
- **Repro**:
  1. `grep -n "| aberta |" 00-requisito.md` devolve Q1, Q2, Q3 e Q4.
  2. `grep -n "^| RQ-0" 00-requisito.md` mostra todas as RQs com `fechada`.
  3. `git diff main...HEAD -- resources/views/vendor` mostra a linha da Q4 aplicada nas dez views.
- **Evidência**: as linhas citadas acima. Além disso, o ID Q4 está duplicado: Q4 é pergunta de desenho em `01` D1 e na linha 95 do `03`, e pergunta de requisito na linha 82 do `00` e na linha 100 do `03`.
- **Destino**: 1 especificação
- **Ação exigida**: o solicitante responde Q1, Q2 e Q4 num Adendo com fonte antes do PR. A alternativa é a sessão reclassificar como desenho, com justificativa, a pergunta que não é de requisito (Q2 e Q4 têm cara de desenho de entrega) e levá-la para `## Decisões de Desenho`. Renumerar a Q4 de requisito (por exemplo, para Q9).

### QA-02 — CT-18 falha no CI: o checkout raso não traz a tag `v0.45.0` · Major · destino 3

- **Dimensão**: K (oráculo × ambiente) e J
- **Relacionado a**: P-07, CT-18, passo 6, M43
- **Esperado**: o `[CT-18]` roda verde onde `naArvoreDoKit()` é verdadeiro, e isso inclui o job `qualidade` do CI, que tem `.github` e roda `--testsuite=…,Kit,…`.
- **Observado**: o caso executa `git diff --name-only v0.45.0 HEAD` e afirma `isSuccessful()`. O `actions/checkout` do job `qualidade` (`.github/workflows/ci.yml:17`) roda sem `fetch-depth` e sem tags. O próprio `ci.yml:281` diz que *"um checkout raso (o padrão desta action) não tem a base disponível"*. Nenhum outro teste de `tests/Kit` depende de tag: `grep -rn "rev-parse\|'tag'\|fetch-depth" tests/Kit tests/Pest.php` não devolve nada.
- **Repro**:
  1. `git clone --depth 1 --no-tags --branch fix/kit-update-views-vendor file:///D:/…/starter-kit-easy qa-gate-shallow` (clone feito no scratchpad)
  2. `git tag -l | wc -l` dá `0`.
  3. `git -c core.quotepath=off diff --name-only v0.45.0 HEAD -- resources/views/vendor` dá `fatal: bad revision 'v0.45.0'`, exit 128.
- **Evidência**: a saída do passo 3. Localmente, com as tags presentes, o caso passa (92/92).
- **Destino**: 3 teste. Se a correção escolhida for buscar as tags no CI, também 4 infra.
- **Ação exigida**: invocar a `feature-test-design` com o achado. O `Dado` do CT-18 ganha a partição "tag anterior ausente": ou o caso pula com motivo quando `git rev-parse --verify v0.45.0` falha, e o pulo entra na contagem de pulados, ou o `ci.yml` passa a buscar as tags. Escrever o CT que falha primeiro. A lacuna de derivação é "teste que depende de ref git que o checkout do CI não traz", vizinha da linha "teste que viaja lendo arquivo que não viaja" da taxonomia.

### QA-03 — No projeto sem o override, `media.blade.php` sai como "modificado", e o `--only-new` não o aplica nunca · Major · destino 1

- **Dimensão**: A e L5
- **Relacionado a**: RQ-01, RQ-05, P-06, P-07, D6, passo 5, docs pt/en
- **Esperado**: segundo `KitUpdate.php:36`, `--only-new` *"aplica de uma vez só os arquivos que ainda não existem no projeto"*. O parágrafo novo de `docs/pt/comecar/atualizando-o-projeto.md` (o en diz o mesmo) afirma: *"o arquivo que você não tem aparece como 'novo no kit'"*.
- **Observado**: na extração v0.45.0, que não tem `resources/views/vendor/filament-auth-designer`, o `kit:update --tag=0.45.1-rc --dry-run` lista `…/media.blade.php modificado`. O motivo é o P-07: o diff tag→tag vê o arquivo mudar, e esse rótulo prevalece sobre o da P-06 (D6, `$arquivos += …`). O `revisarEAplicar()` (`KitUpdate.php:911`), com `--only-new`, só aplica `'novo no kit'`. Quem atualiza com `--only-new` fica sem o override, que é justamente o arquivo do issue. A versão não é marcada se nada for aplicado, então a rodada seguinte repete o resultado. O CHANGELOG e as docs não dizem isso. A frase *"Sem isso, quem já estava na v0.43.0 … nunca o receberia"* das docs também não vale para esta release, porque quem entrega aqui é o P-07.
- **Repro**:
  1. Na extração `validacao-v0.45.0/novo-sem-tenant` (sem a pasta), rodar `php artisan kit:update --repo=… --tag=0.45.1-rc --dry-run --no-interaction`.
  2. A saída traz `media.blade.php modificado`. O mesmo aparece em `scratchpad/e2e-antiga.log`, gerado com a classe antiga.
  3. Ler `KitUpdate.php:911`: `$emLote === 'novos' && $rotulo === 'novo no kit'`.
- **Evidência**: a saída reproduzida em 2026-10-06 (dez linhas `modificado`, nenhuma `novo no kit`).
- **Destino**: 1 especificação. A mudança altera o que a feature promete para o `--only-new`.
- **Ação exigida**: registrar uma `P-nn` que decida uma de duas coisas: o rótulo considera se o arquivo existe na árvore do projeto (arquivo ausente vira "novo no kit"), ou a limitação fica documentada no CHANGELOG e nas docs pt/en, com a frase das docs corrigida. Depois o CT no `04` e, por último, a correção.

### QA-04 — `01` e `02` não acompanharam os passos 5 e 6 · Minor · destino 1

- **Dimensão**: L3
- **Relacionado a**: passos 5 e 6, D5, D6
- **Esperado**: afirmação do PRD ou da ADR que o código contradiz leva a marca `*(alterado em …)*`.
- **Observado**:
  - linha 33 do `01`: *"A correção é uma constante e um teste"*.
  - Modelo de Execução (l.117): *"até 5 `git checkout` a mais (um por pasta nova)"*. O `aplicar()` roda por arquivo, então são até 10, e há também um `git show` e um `git diff` a mais por rodada com origem.
  - Rollback (l.130): fala só da constante e do teste.
  - Verificação Final (l.248): *"`pest --mutate`: não se aplica a constante"*. Agora há dois estáticos com lógica (`caminhosNovosNaLista`, `rotularDiff`).
  - Impacto (l.121): *"`LogoDarkModeTest` (22 casos)"*. O arquivo, que não está no diff, tem 24.
  - `02` (l.8): *"As quatro decisões … (D1–D4)"*, mas o `01` tem D1 a D6.
- **Repro**: `grep -rn "22 casos\|até 5\|uma constante e um teste\|D1–D4" wikis/specs/fix/kit-update-views-vendor/views-vendor-no-kit-update/` e `vendor/bin/pest tests/Kit/LogoDarkModeTest.php` (24 passed).
- **Evidência**: a saída do `grep` e o JSON do Pest (`"tests":24`).
- **Destino**: 1
- **Ação exigida**: corrigir as linhas do `01` e do `02`, todas marcadas com a data.

### QA-05 — O `04` diz que o teto de pulados sobe 3, mas são 6 casos novos com `skip` · Minor · destino 1

- **Dimensão**: L3
- **Relacionado a**: R5, CT-06, CT-07, CT-08, CT-11, CT-14, CT-18
- **Esperado**: linha 321 do `04`: *"O teto de pulados … sobe em 3"*.
- **Observado**: o diff acrescenta 6 guardas `->skip(fn (): bool => ! naArvoreDoKit(), …)`, uma em cada caso: CT-06, CT-07, CT-08, CT-11, CT-14 e CT-18.
- **Repro**: `git diff main...HEAD -- tests/Kit/KitUpdateTest.php | grep "^+" | grep -c "skip(fn (): bool => ! naArvoreDoKit()"` dá `6`. O `grep -rn "sobe em 3"` na wiki acha só `04-casos-de-teste.md:321`.
- **Destino**: 1
- **Ação exigida**: corrigir o número no `04` (só ele o repete). Na tag, a contagem medida com `--log-junit` decide.

### QA-06 — Referências quebradas e texto impreciso no `00` e no `04` · Cosmético · destino 1

- **Dimensão**: L1 e L3
- **Observado**:
  - O M3 do `04` (l.169) cita a linha `final` do CT-02, que não existe.
  - A taxonomia (l.461) cita a linha `dois`, que também não existe.
  - Q2 do `00` (l.80) chama o arquivo idêntico de `components/widget.blade.php`-equivalente. O arquivo idêntico é `pages/history.blade.php`: `git ls-files resources/views/vendor/command-center` mais a comparação de conteúdo dão 3 diferentes (`output`, `commands`, `run`).
- **Repro**: `grep -n "final\`\|linha \`dois\`" 04-casos-de-teste.md`
- **Ação exigida**: apontar M3 para as linhas que existem (`byte`, `mista`) e a taxonomia para `painel`/`painel2`. Corrigir o nome do arquivo na Q2.

### QA-07 — Checkboxes do step 9/10 em aberto, mas as checagens já passam · Cosmético · destino 1

- **Dimensão**: L6
- **Observado**: as linhas 41 a 49 do `03` estão `[ ]`. Mesmo assim, `## Revisão do Diff (step 9)` está preenchida e `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh` e `conformidade-rules.sh` saem com exit 0 (reproduzido).
- **Ação exigida**: fechar cada item com a evidência, ou deixar aberto o que de fato falta (por exemplo, "falsificabilidade: quantos falham sem o fix").

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-01 | override da lock-screen chega pelo `kit:update` | 1, 3, 4, 5, 6 | CT-01, 10, 14, 15–18 | — | `CAMINHOS_DO_KIT`, `arquivosAlterados()` | ✅ dry-run reproduzido; ⚠️ com `--only-new`, não chega | ⚠️ QA-01 (Q4), QA-03 |
| RQ-02 | auditoria: toda pasta autoral entregue | 1, 6 | CT-06, 08, 18 | — | 5 entradas | ✅ auditoria independente: 5 autorais, 7 cruas | ⚠️ QA-01 (Q1, Q2, Q4) |
| RQ-04 | publish cru fica fora | 1, 2 | CT-04, 05, 07 | — | — | ✅ nenhum arquivo cru no dry-run | ⚠️ QA-01 (Q1, Q2) |
| RQ-05 | sintoma some no projeto atualizado | 1, 2, 5 | CT-01, 14, 15–17 | — | — | ⏭️ só dry-run; aplicar + `LogoDarkModeTest` CT-16 no projeto não medido | ⚠️ QA-01, QA-03 |
| P-07 | dez views mudam para a classe antiga entregar | 6 | CT-18 | — | 10 views | ✅ local; ❌ CI | ❌ QA-02 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 2 achados (QA-01, QA-03). `rastreabilidade.sh` exit 0, sem `07-tickets/`. O `git diff` por passo confere: passos 1 a 6 batem com os arquivos do diff. Os extras `wikis/checklist-de-release.md` (CR-05) e `wikis/glossario.md` (CR-06) estão registrados no `03` |
| B | Fronteiras e dados | ✅ | Sondagem por `php -r`: `rotularDiff` com `R100` (chave `old\tnew`, Q8 pré-existente), CRLF, caminho com espaço; `caminhosNovosNaLista` com duplicata e com `b/`×`b`; `caminhosDeclaradosEm('')` = `[]`. Histórico de 89 tags: nenhuma entrada nova sob ancestral, nem ancestral sobre filha |
| C | Matriz de permissão | n/a | Sem papel, rota nem policy: `git diff --stat main...HEAD -- routes app/Policies app/Filament app/Livewire app/Models database` vazio |
| D | Observabilidade | ✅ | D4 confirmada: nenhum `Log::`/`logger(` acrescentado (`grep -c` = 0). Saída de console conferida no dry-run, sem PII |
| E | Performance | ✅ | P-06 custa um `git show` e um `git diff` por rodada com origem. `KitUpdateTest` roda em 25,6 s |
| F | UX de erro | ✅ | Mensagens da varredura em pt, nomeando pasta, arquivos e a saída de cada célula (CT-03/04/12). A UX do rótulo vai para QA-03 |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` exit 1 (Filament). Nível 1 `dark-mode.sh` exit 1: uma linha, `clear-cache-button.blade.php:67 text-white`, pré-existente e sobre `bg-danger-500` (falso positivo). O diff só acrescenta comentário Blade, que não chega ao HTML |
| H | Acessibilidade | n/a | Sem UI no `01`. O diff de view é só comentário Blade (`git diff … \| grep '^+'`: 10 linhas, todas comentário) |
| I | Segurança da superfície nova | ✅ | O step 9 (`## Revisão do Diff (step 9)`) cobriu: 9 achados (CR/RD), 8 itens rejeitados. Coberto além dele: os passos 5 e 6, que o step 9 não revisou (desvio declarado no `03`). `git show "{$origem}:…"` usa tag validada por `in_array` (`resolverOrigem`), `Process` com array (sem shell); o `fromShellCommandline` do CT-14 usa `escapeshellarg` e só existe no teste. IDOR, rota, mass assignment, upload e `DB::raw`: n/a |
| J | Regressão adjacente | ⏭️ | Por ID: ancestral `LogoDarkModeTest` (Kit 24/24, Browser 1/1) e os 12 arquivos da regressão do `03` rodados em série, 765 testes e 4.539 asserções somados. `--parallel --tia` não rodou (ver *Não Verificado*). RCRCRC: `KitUpdate.php` é Core, Risk e Repaired; o `--only-new` não foi previsto no Impacto (QA-03) |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1: uma linha, `KitUpdateTest.php:374`, que é caso pré-existente (`c37139d2`, 2026-09-01) e foi rejeitada. Oráculo × ambiente: QA-02. K2 não rodou. Revisão adversarial do `04`: feita (`fw-adversario-ct`, 21 achados) |
| L | Consistência documental | ⚠️ | 4 achados (QA-04 a QA-07). L1 `ids-ct.sh` exit 0, cabeçalho do `04` = `grep -c` (18/9/43/1). L2 `citacoes.sh` exit 0. L4 `conformidade-rules.sh` exit 0, e as 5 rules foram conferidas no código. L5: pt = en, mas há a frase de QA-03. L6 `checkbox-sem-evidencia.sh` exit 0 + números reproduzidos (ver abaixo). L7: os 3 termos novos do glossário batem com o código |

**L6, números reproduzidos**: KitUpdateTest 92/92 com 148 asserções, 39 casos `[CT-` (`--filter`); 558 testes (3+3+24+528); 47 testes com 3 pulados; 765 testes e 4.539 asserções; `grep -c "^        '"` = 84 (79 na `main`); `git diff --name-only v0.45.0 HEAD -- resources/views/vendor` = 10; `'@'` nas linhas novas = 0; `pint --test` passed. O dry-run P-06 (`rc0 --from=0.45.0`) dá `media.blade.php novo no kit`, sem `FundacaoTest`, e bate com `e2e-nova.log`. Nenhuma degradação declarada no `03`. As células de `Custo` estão todas preenchidas.

## Débitos Aceitos

- nenhum (o veredito é reprovação)

## Suspeitas Não Confirmadas

- P-06 com entrada nova **ancestral** de uma entrada da origem (por exemplo, `resources/views` no destino sobre `resources/views/auth` na origem) compararia a pasta inteira com a árvore do projeto e acusaria as edições dele. Nenhuma das 89 tags tem esse caso (medido), então não é achado hoje.

## Não Verificado

- J: o passo 1 (`pest --parallel --tia`) não rodou por restrição do host. O orquestrador proibiu a suíte completa e o `--parallel` por falta de memória. Rodaram por ID a ancestral e os 12 arquivos vizinhos.
- K2 (`--mutate`) está fora do perfil padrão. A ferramenta existe: `php -m` mostra `pcov` e `xdebug`, e há `vendor/pestphp/pest-plugin-mutate`. Mutar o `KitUpdate.php` inteiro com 25 s de suíte por mutante não cabe no host. Os mutantes manuais A–F do `03` foram conferidos por leitura, não reexecutados, porque exigiriam `sed -i`.
- RQ-05 de ponta a ponta (aplicar e rodar `LogoDarkModeTest` CT-16 no projeto atualizado) não foi feito: aplicar alteraria a extração. Só o `--dry-run` rodou.
- L6: as rodadas com a "classe antiga" (controle `rc0` e candidata `rc`) não puderam ser reproduzidas, porque a extração já está no commit "primeira rodada trouxe a classe nova". Foram conferidas pelos logs `e2e-antiga.log` e `e2e-controle.log`.
- O "`net: -4 lines possible`" do ponytail-review é julgamento e não tem comando que o reproduza.
