# Relatório de QA — Roadmap 8.2: `composer-require-checker` + `composer-unused`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão (natureza `nova`, sem UI, domínio comum; o Mínimo vale só para `ajuste`)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `composer.json` (`require-dev` e scripts), job `qualidade` do CI, `KitUpdate::CAMINHOS_DO_KIT` · Regressão: sim
> Independência: comprometida — recebeu do orquestrador um resumo do que foi entregue ("composer require-dev, scripts `composer deps:*`, um passo de relatório no CI que não reprova, `composer-unused.php`, triagem na documentação de qualidade") e o método de verificação sugerido; sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 11 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 3 · Minor: 1 · Cosmético: 1
- Não verificadas: K (K2 fica fora do perfil padrão). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma
- Ambiente: app não servido (não se aplica, a feature é ferramental) · Pest 5 (`vendor/bin/pest`) · MCP: Boost com connect timeout, Playwright não usado (sem UI)

## Achados

### QA-01 — Pacote sem uso efetivo (`livewire/blaze`) excluído como "usado por outro caminho" · Major · destino 2

- **Dimensão**: A (P-03, RQ-06)
- **Relacionado a**: P-03, P-04, RQ-06, CT-06, passos 2 e 6 do PRD
- **Esperado**: P-03 diz que o pacote realmente sem uso NÃO entra na lista de exclusão. O `composer-unused.php` repete isso no próprio comentário
- **Observado**: o `livewire/blaze` está excluído (`composer-unused.php`, `NamedFilter::fromString('livewire/blaze')`), e a triagem (`wikis/qualidade-de-codigo.md`) o classifica como "usado por outro caminho". O motivo dado é "auto-discovery do provider intercepta a compilação do Blade". A mesma célula de evidência admite "nenhum `@blaze` próprio do kit". O provider de fato engancha a compilação (`vendor/livewire/blaze/src/BlazeServiceProvider.php:interceptBladeCompilation`), mas só transforma o template com a diretiva `@blaze` ou com um caminho registrado em `Blaze::optimize()->in(...)`. O código está em `vendor/livewire/blaze/src/BlazeManager.php:compile` (`$directives->blaze() || $this->config->shouldCompile($path)`), e `Config::isEnabled` devolve `false` com a configuração vazia. Nenhuma das duas condições existe no kit nem em nenhum pacote do vendor
- **Repro**:
  1. `grep -rn "@blaze\|Blaze::\|blaze" resources app bootstrap/app.php bootstrap/providers.php config routes` → nenhuma linha
  2. `grep -rln "@blaze" vendor --include=*.php | grep -v livewire/blaze/` → 0 arquivos. `grep -rln "Livewire\\\\Blaze\|Blaze::" vendor --include=*.php | grep -v livewire/blaze/` → 0 arquivos
  3. Ler `Config::shouldCompile`/`isEnabled`: sem `add()`/`in()`, nenhum arquivo é compilado, dobrado nem memoizado
- **Evidência**: as saídas acima. O CT-06 passa porque cruza a lista de exclusão com a triagem, e os dois artefatos foram escritos pelo mesmo autor. O CT não confere se o veredito é verdadeiro
- **Destino**: 2 implementação (a triagem e a configuração divergem de P-03)
- **Ação exigida**: reclassificar o `livewire/blaze` como "sem uso", com recomendação ("remover ou usar de verdade com `@blaze`/`Blaze::optimize()`"), e tirá-lo do `composer-unused.php`. Atualizar todas as cópias dos números: `grep -rn "5 usados\|5 são usados\|3 sem uso\|3 não têm uso\|3 are not used\|5 are used\|5 pacotes\|os 5\|cinco pacotes" wikis/qualidade-de-codigo.md wikis/roadmap.md docs/pt/referencia/qualidade-de-codigo.md docs/en/referencia/qualidade-de-codigo.md CHANGELOG.md wikis/agentes-e-skills.md wikis/atualizacoes/v0.55.0.md wikis/specs/feat/verificacao-de-dependencias/` (o `01` passo 2 e D1, o `03` passo 2 e o Despacho 1 repetem "5"/"3")

### QA-02 — Novo pré-requisito de instalação (`ext-sodium`, PHP ≤ 8.5 nas dependências de dev) sem `RQ`/`P-nn` e fora do Impacto do PRD · Major · destino 1

- **Dimensão**: L5 (e A, impacto não previsto)
- **Relacionado a**: RQ-03 (Q1), CR-01 do step 9, `01` §Impacto em Features Existentes e §Dependências
- **Esperado**: comportamento que muda o que a feature promete vira `P-nn` em `## Premissas`. Se estende o pedido, vira também pergunta ao solicitante. A opção de Q1 dizia só que as ferramentas "viajam com todo `create-project`"; não dizia que a instalação passaria a exigir uma extensão
- **Observado**: o README pt/en (§Requisitos) passou a exigir a extensão `sodium` em todo `composer create-project`. O CHANGELOG 0.55.0 diz ainda "limita o PHP a 8.4 e 8.5": o lock exige `php-standard-library` com `ext-sodium`, e `maglnet/composer-require-checker` 4.24.0 exige `php: ~8.4.0 || ~8.5.0`. Nenhuma `P-nn` no `00` cobre isso, e nenhuma decisão no `01`/`02`. O `01` §Impacto diz só "traz as duas ferramentas e as dependências delas". O README ainda anuncia "PHP 8.4+", que deixa de valer para instalação com dev a partir do PHP 8.6. A decisão está só na tabela do step 9 do `03` (CR-01: "documentado")
- **Repro**:
  1. `php -r '$l=json_decode(file_get_contents("composer.lock"),true); foreach($l["packages-dev"] as $p) if(in_array($p["name"],["php-standard-library/php-standard-library","maglnet/composer-require-checker"])) echo $p["name"]," ",json_encode($p["require"]),"\n";'` → `ext-sodium` e `php ~8.4.0 || ~8.5.0`
  2. `git diff main...HEAD -- README.md README.en.md` → a frase nova do `sodium` em Requisitos
  3. `grep -n "P-0" wikis/specs/feat/verificacao-de-dependencias/verificacao-de-dependencias/00-requisito.md` → nenhuma premissa sobre o pré-requisito
- **Evidência**: as saídas acima; `03-progresso.md` §Revisão do Diff, linha CR-01
- **Destino**: 1 especificação
- **Ação exigida**: registrar `P-nn` ("a instalação com dependências de dev exige `ext-sodium` e PHP 8.4–8.5"). Como estende o custo aceito em Q1, registrar também a pergunta ao solicitante: aceita o pré-requisito, ou prefere outra via, como ferramenta só no CI ou phar? Depois atualizar o `01` §Impacto/§Dependências e alinhar o "PHP 8.4+" do README pt/en à faixa real

### QA-03 — CT-04 do `04` desatualizado em relação ao teste: título, precondição, fixture e 3 datasets sem `Exemplos` · Major · destino 3

- **Dimensão**: L1 (linha de dataset sem Exemplo no Gherkin; teste derivado do código)
- **Relacionado a**: RQ-05, P-02, CT-04, CX-01/CX-02/CX-03 do step 9
- **Esperado**: o Gherkin do CT-04 é o oráculo do teste. Em `04-casos-de-teste.md` §R4, o CT-04 começa com "Dado que ao menos uma das duas ferramentas, rodada sozinha no kit, sai com código diferente de 0", e o §Setup Global diz "Fakes: Nenhum… sem `Process::fake()`, que esvaziaria o oráculo". Não há `Exemplos`
- **Observado**: `tests/Kit/VerificacaoDeDependenciasTest.php` ("[CT-04] com achado ou ferramenta quebrada…") roda binários falsos em diretório temporário e tem 3 linhas de dataset: "as duas com achado", "a primeira ausente e a segunda ainda roda" e "a segunda escreve o erro só em stderr". Nenhuma está no `04`. A precondição medida no kit real deixou de existir, e as notas sobre M11/M12 latentes ficaram obsoletas. As cláusulas acrescentadas pela revisão adversarial (ADV-01…ADV-10: `--no-dev`, job `qualidade` sem `if:`, `--excludePackage`, `--config-file`, marcadores de evidência) existem só na tabela "ADV → Destino". O corpo Gherkin de CT-03/05/06/08 não as tem, embora o teste as afirme
- **Repro**:
  1. Comparar `04-casos-de-teste.md` §Regra R4 e §Setup Global com o `it('[CT-04]…')->with([...])` do teste
  2. `bash .ai/skills/feature-wiki/scripts/ids-ct.sh {wiki} 'tests/Kit/VerificacaoDeDependenciasTest.php'` sai com exit 0, porque só confere o ID; a divergência é de leitura
- **Evidência**: as linhas `->with([...])` do CT-04; o §Setup Global/Fakes do `04`. O passo real do CI foi executado à parte pelo gate (dimensão F/J) e saiu com exit 0, com as duas saídas no resumo. Isso não substitui o CT
- **Destino**: 3 teste
- **Ação exigida**: pela `feature-test-design`, reescrever o CT-04 do `04` com a fixture de ferramentas falsas, o título e `Exemplos` para os 3 datasets (os casos de CX-01/CX-02), e decidir se fica algum CT com a ferramenta real. Levar os "ganha:" da revisão adversarial para os `Então` dos cenários

### QA-04 — `[x]` do PHPStan na Verificação Final sem execução · Minor · destino 1

- **Dimensão**: L6
- **Esperado**: checkbox fechado com evidência de execução
- **Observado**: o `03` §Verificação Final traz "`vendor/bin/phpstan analyse` (level 8) — sem código de `app/` novo…; o job `qualidade` do PR roda o gate". Isso é justificativa, não saída. A branch nunca foi publicada: `git ls-remote origin feat/verificacao-de-dependencias` e `gh run list --branch …` voltaram vazios, então não há job de PR
- **Repro**: `vendor/bin/phpstan analyse --no-progress --memory-limit=2G` → `{"tool":"phpstan","result":"passed","errors":0}` (42 s)
- **Destino**: 1
- **Ação exigida**: substituir a justificativa pela saída real do comando

### QA-05 — Contagens e textos defasados no `04` e no docblock do teste · Cosmético · destino 1

- **Dimensão**: L1
- **Observado**: o cabeçalho do `04` diz "Cenários: 8", e o `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md` devolve **9**. Outras defasagens:
  - O M26 é citado no Índice, mas não tem linha `| M26` em tabela de mutantes; "Mutantes previstos: 25" não o conta.
  - `04:86` diz "Q?2: … sem cenário próprio", mas o CT-09 existe.
  - `04:5` diz "Revisão adversarial: não exigida", mas o §Revisão Adversarial registra a Rodada 1.
  - O docblock do teste diz "Casos CT-01..CT-08".
- **Evidência**: `grep -rn "Cenários: 8\|Mutantes previstos: 25\|M26" wikis/specs/feat/verificacao-de-dependencias/` → só `04-casos-de-teste.md:19` e `:398`
- **Ação exigida**: recalcular o cabeçalho pelos `grep -c` de `template-04.md`, §*Contagem do cabeçalho*, e corrigir as três frases e o docblock

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| P-03 | exclusão só do que é usado por outro caminho | 2 | CT-06 | — | `composer-unused.php` | `livewire/blaze` sem uso efetivo excluído | ❌ QA-01 |
| RQ-06 / P-04 | resposta registrada (triagem) | 6 | CT-07, CT-08 | — | `wikis/qualidade-de-codigo.md` | 1 veredito errado; contagens 137/44 e 8 ext conferidas | ⚠️ QA-01 |
| RQ-05 / P-02 | relatório sem reprovar, no resumo | 4 | CT-04 | — | `.github/workflows/ci.yml` | passo real: exit 0, as duas saídas; o CT diverge do `04` | ⚠️ QA-03 |
| (sem RQ/P) | pré-requisito `ext-sodium` / PHP ≤ 8.5 | — | — | — | README pt/en, CHANGELOG | crescimento sem rastro | ❌ QA-02 |

As demais linhas (RQ-01..04, P-01, P-05, P-06, P-07) estão OK. O `rastreabilidade.sh` saiu com exit 0. O `deps:check` real roda as duas ferramentas. O `require` está idêntico ao da `main` (md5 do JSON igual), e o lock só acrescenta 12 pacotes `packages-dev`. Não há `07-tickets/`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | QA-01, e QA-02 pelo lado do impacto. `rastreabilidade.sh` exit 0; `indice.sh --check`: sem `07-tickets/`. `git diff` por passo conferido. As duas conferências que o `04` delegou ao gate: completude da triagem (181 = 137 sem dependência adivinhada + 44 de 8 `ext-*`; Filament 82; os grupos somam 137) e P-05 (`require` inalterado) — feitas |
| B | Fronteiras e dados | n/a | sem entrada de usuário: o `01` §Rotas/§UI/§Autorização diz "Nenhuma", e o diff não toca rota, request, model nem view |
| C | Matriz de permissão | n/a | sem papel nem ação: nenhuma policy, rota ou permission no diff (`git diff --name-only main...HEAD`) |
| D | Observabilidade | ✅ | o `01` declara channel "Nenhum", e o diff não acrescenta `Log::`. O resumo do CI traz só nomes de pacote e símbolo, sem PII |
| E | Performance | ✅ | medido localmente: `composer-unused` 1,3 s, `composer-require-checker` 7,8 s. Fora do runner (ver Suspeitas) |
| F | UX de erro | ✅ | o erro da ferramenta chega ao resumo (`} 2>&1 \| tee`). O `run` real do passo foi executado com `bash -eo pipefail`: exit 0, as duas seções e as saídas presentes |
| G | Tema e cor | n/a | sem UI no `01` e no diff. `dark-mode.sh --mecanismo` exit 1 (o projeto tem dark mode); nível 1 sobre os arquivos AM do diff exit 0 |
| H | Acessibilidade | n/a | sem UI no `01` e no diff |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9, `## Revisão do Diff (step 9)` do `03` (18 linhas, 6 rejeitadas com motivo). Além disso, conferido pelo gate: CI em `pull_request` (não `pull_request_target`), o passo não usa `secrets`, não há rota nova, não há `DB::raw`, as dependências novas são públicas e só de dev |
| J | Regressão adjacente | ✅ | `pest --parallel --tia --processes=3 --exclude-testsuite=Browser`: 6194 testes, 6191 passaram, 3 pulados, 0 falhas. O TIA avisou "does not apply to partial runs", então a execução foi integral. Consumidores da infra por arquivo: `QualidadeDeCodigoTest` 31/31, `CandidatasAPacoteTest` 3/3, `RoteiroDeAtualizacaoTest` 212/212, `ChecklistDeReleaseTest` 23 + 3 pulados. As citações `KitUpdate.php:símbolo:linha` deslocadas pela linha nova foram todas corrigidas pelo diff; nenhuma ficou quebrada por ele |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0. K2 fora do perfil padrão. Revisão adversarial do `04`: feita (Rodada 1, 15 achados). Limitação de oráculo: o CT-06 não pega veredito falso (QA-01) |
| L | Consistência documental | ❌ | QA-02 (L5), QA-03 (L1), QA-04 (L6), QA-05 (L1). Scripts: `ids-ct.sh` exit 0, `citacoes.sh` exit 0, `conformidade-rules.sh` exit 0, `checkbox-sem-evidencia.sh` exit 0, `roteiro-manual.sh` exit 0. L8 n/a — sem `08` (`Roteiro manual: não` no `03`). L3: D1–D4 conferidos contra o código. L4: 7 rules casadas, todas com linha no `03`, nenhuma violada. L5: docs pt × en coerentes. L6: "12/12, 171 asserções" reproduzido (12/171/4,2 s); "6194/6191/3" reproduzido; os cinco scripts com exit 0 reproduzidos; "3 sem uso e 181 símbolos" reproduzido; `RoteiroDeAtualizacaoTest` 212/212 reproduzido. L7: os vereditos da triagem são rótulos do relatório, sem conflito com `wikis/glossario.md` ("rota de entrega" foi usada no sentido do glossário) |

## Débitos Aceitos

- nenhum (veredito reprovado)

## Suspeitas Não Confirmadas

- O `01` §Modelo de Execução estima "~1 min a mais no job `qualidade`". Localmente as duas ferramentas somam ~9 s. Sem execução no runner não há como confirmar o número
- No `ubuntu-latest`, o `setup-php` do CI não lista `sodium` em `extensions`. A suposição é que a build padrão a inclui. Sem execução do CI não há como confirmar; se faltar, o `composer install` de todos os jobs falha
- O `.ai/rules/testes.md` exige decompor no CHANGELOG (`Validação antes da tag`) o aumento do teto de pulados. Este arquivo acrescenta 8 casos pulados fora da árvore do kit. A seção 0.55.0 ainda não tem a validação, o que é esperado só no momento da tag

## Não Verificado

- K2 (mutação) — motivo: fora do perfil padrão. O driver existe (`php -m` lista `pcov` e `xdebug`), e o diff quase não tem código de `app/` (uma linha de lista)
- Alegação do `03` "com a entrega revertida 7 de 10 vermelhos" — motivo: reproduzir exige alterar a árvore (proibido a este gate). Os testes hoje são 12, não 10
- Execução real do job `qualidade` no runner do GitHub — motivo: a branch não foi publicada e não há run (`gh run list` vazio). O `run` do passo foi executado localmente, no Git Bash do Windows
- Boost MCP — motivo: connect timeout na sessão; usados `php artisan`/grep e leitura direta
