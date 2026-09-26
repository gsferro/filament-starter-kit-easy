# Relatório de QA: PHPStan no level 8, e as pendências das últimas rodadas

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo**. Natureza "ajuste", sem UI nova, mas o domínio é sensível (autorização, aceite de convite, papel), então K roda com `--mutate`.
> Natureza da wiki: ajuste · Regressão: sim (ancestral `cobertura-de-testes`, e a reconciliação 6b está no escopo)
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> App servido: **não** (Windows, `php artisan serve` indisponível nesta sessão). As dimensões dinâmicas rodaram por Pest (HTTP e Livewire).

<!-- Gravado pela sessão, verbatim, a partir do retorno do sub-agente fw-qa-gate. Sem edição. -->

## Veredito, ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 5 · Minor: 4 · Cosmético: 0
- Ambiente: app não servido · Pest 5 com PCOV e Xdebug carregados (`php -m`) e `pest-plugin-mutate` presente (`ls vendor/pestphp/`) · Playwright MCP não usado (não há superfície de UI nova)
- Gates reproduzidos por este juiz:
  - `pint --test` → passed
  - `phpstan analyse` → `[OK] No errors`
  - `filacheck` → `All 17 rules passed!`
  - suíte Unit, Feature, Kit e Tenancy em `--parallel` → **3.072 testes, 3.069 passaram, 3 pulados, 13.131 asserções**, 183 s. Contra a baseline de 2.993 / 2.990 / 3: +79 testes, nenhum pulado novo, bate com o CHANGELOG.

## Achados

### QA-01: o RQ-03 perdeu duas das três palavras do pedido, sem pergunta registrada · Major · destino 1
- **Dimensão**: A (auditoria do requisito, passo 2)
- **Relacionado a**: RQ-03 e RQ-01, `00-requisito.md:17` e `:27`
- **Esperado**: *"garanta que as regras de **validação, teste e qualidade** do projeto estejam as melhores possiveis"*. Cada substantivo vira uma cláusula ou uma entrada em `## Ambiguidades`.
- **Observado**: o RQ-03 decompõe só a "qualidade (análise estática)". A pergunta aberta do RQ-03 discute apenas *qual degrau* do PHPStan. "Regras de validação" não aparece no 00, no 01 nem no 04. "Regras de teste" só aparece de forma indireta, nos itens que o `## Fora de Escopo` exclui (type coverage, branch coverage). O RQ-01 também leu "pendente nas últimas rodadas" como "os 48 erros do level 8", que é trabalho novo e não pendência, e isso não foi registrado como interpretação.
- **Repro**: `grep -n "validação" wikis/specs/feat/phpstan-nivel-8/phpstan-nivel-8/0*.md` só casa o trecho literal da linha 17 e a coluna de origem da linha 27. Não há cláusula nem pergunta.
- **Ação exigida**: perguntar ao mantenedor o que ele entende por "regras de validação" e "regras de teste", e registrar a resposta como cláusula ou como Fora de Escopo declarado.

### QA-02: as docs e o CHANGELOG chamam de "0 sobreviventes" mutantes que o plugin conta como sobreviventes · Major · destino 1, e 3 para os mutantes
- **Dimensão**: L5, L6 e K
- **Relacionado a**: CT-27 da wiki `cobertura-de-testes`, passo 6b, `03-progresso.md:174-177`
- **Esperado**: no `pest-plugin-mutate`, `UNTESTED` é mutante cujo processo de teste **passou**, isto é, escapou: `vendor/pestphp/pest-plugin-mutate/src/MutationTest.php:MutationTestResult::Untested:139` fica dentro de `isSuccessful()` e emite `mutationEscaped` (`:141`). Mutante numa linha sem teste nenhum é `UNCOVERED`. O próprio `[CT-27]` (`tests/Kit/CoberturaDeTestesTest.php:273-274`) já trata "não testados" como *"contagem de mutantes sobreviventes"*.
- **Observado**:
  - As docs afirmam *"225, sendo 4 não testados e 0 sobreviventes"* e *"Não testado é mutante numa linha que nenhum teste executa"*. O CHANGELOG repete com *"são zero sobreviventes"*. É a inversão do conceito que a entrega diz ter corrigido.
  - Remedição de `KitCobertura` feita agora: **159 mutantes, 4 untested (sobreviventes) + 1 uncovered, 96,86 %, 33,60 s**. A doc publica 158 / 97,47 % / 38,00 s. A duração é plausível e há sobreviventes nomeados, então o arnês rodou de verdade.
  - Os sobreviventes são `KitCobertura.php:57` (dois), `:59` e `:198`. `CustomizadorDaInstalacao.php:470` (quatro) segue o mesmo padrão.
- **Repro**: `cmd //c "…\pestw.cmd tests/Kit/KitCoberturaTest.php --mutate --path=app/Console/Commands/KitCobertura.php --no-tia"` e depois ler a linha `Mutations:`.
- **Evidência**: o `grep -rn "0 sobreviventes\|zero sobreviventes\|0 survivors\|zero survivors\|Não testado é"` casa estes arquivos: `CHANGELOG.md:97`, `docs/pt/referencia/qualidade-de-codigo.md:276,277,281,282`, `docs/en/referencia/qualidade-de-codigo.md:278,279,284`, `wikis/specs/feat/phpstan-nivel-8/phpstan-nivel-8/03-progresso.md:175`. Os números 97,47 / 158 aparecem nos mesmos arquivos.
- **Ação exigida**:
  - Destino 1: corrigir o texto e os números em **todos** os arquivos acima, com a saída real do comando.
  - Destino 3: levar os sobreviventes (`:57`, `:59`, `:198` e `CustomizadorDaInstalacao:470`) à `feature-test-design`. O CT-12 e o CT-13 da cobertura passam com a saída antecipada removida.

### QA-03: o CT-08 é mais fraco que o próprio Gherkin, e a trava do RQ-04 não vale para código futuro · Major · destino 3
- **Dimensão**: K (oráculo fraco)
- **Relacionado a**: RQ-04, RQ-06, CT-08 e M10
- **Esperado**: o 04 (`:215-218`) diz *"Dado o código de app, config, database, routes e bootstrap/app.php … Então as ocorrências são exatamente 2 em RoleResource e 1 em User"*. Isso é um inventário global.
- **Observado**: `tests/Kit/QualidadeDeCodigoTest.php:572-586` conta só nos dois arquivos pré-existentes e numa lista congelada dos 15 arquivos desta entrega (`arquivosTocadosPelaEntrega()`). Um `@phpstan-ignore`, `@phpstan-assert` ou `assert(` em qualquer outro arquivo de `app/` passa verde. O inventário atual está correto (`grep -rln "@phpstan-ignore" app config database routes bootstrap/app.php` → só os dois); a lacuna está no instrumento.
- **Repro**:
  1. Acrescentar `// @phpstan-ignore-next-line` em `app/Models/Tenant.php`.
  2. Rodar `vendor/bin/pest --filter=CT-08`. O teste fica verde: a leitura do código mostra que o arquivo não é consultado.
- **Ação exigida**: a `feature-test-design` fecha o M10 no escopo analisado inteiro (varrer `paths` da config efetiva) em vez da lista congelada.

### QA-04: a revalidação de `mensagemPendente` e o caminho autenticado de `responder()` não têm teste · Major · destino 3
- **Dimensão**: K, com B e I
- **Relacionado a**: RQ-06, RQ-10, a tabela `## Superfície Livewire` do 02 (*"continua revalidada no topo de `responder()`"*), CT-16
- **Esperado**: a guarda `$pendente === null || mb_strlen($pendente) > 2000` (`app/Livewire/AssistenteChatWidget.php:96`) é a mitigação declarada para uma propriedade pública sem `#[Locked]`, e esta entrega reescreveu essa linha.
- **Observado**: `--mutate` em `AssistenteChatWidget.php` com `AssistenteChatWidgetTest` deu **133 mutantes, 33 mortos, 11 sobreviventes, 89 sem cobertura, 24,81 %, 62,87 s** (duração plausível).
  - Na linha 96 sobreviveram `||`→`&&`, `>`→`>=` e 2000±1.
  - Nas linhas 236-238 sobreviveram 7 mutantes do log de negação de acesso.
  - O corpo autenticado de `responder()`, onde o diff trocou para `$pendente` (stream e catch), está inteiro sem cobertura. A afirmação do 01 de que o widget *"não muda no caminho real"* não tem prova.
- **Repro**: `cmd //c "…\pestw.cmd tests/Kit/AssistenteChatWidgetTest.php --mutate --path=app/Livewire/AssistenteChatWidget.php --no-tia"`.
- **Ação exigida**: a `feature-test-design` com estes mutantes, cobrindo:
  - usuário autenticado + `set('mensagemPendente', str_repeat('a', 2001))` + `responder` → sem chamada ao agente;
  - borda em 2000;
  - uma caracterização do `responder` autenticado com `Assistente::fake`.

### QA-05: o PRD contradiz o código, e os desvios existem só no 03 · Major · destino 1
- **Dimensão**: L3
- **Observado**:
  - `01:24` diz *"47 de 48 corrigidos no código; 1 é anotação … (ADR-03)"*. Com a ADR-05 são 48 de 48 no código.
  - `01:86-87` e `01:149-150` dizem que a guarda do convite fica *"antes do `assignRole()`"*. O código a chama no topo dos dois verbos (`app/Models/Convite.php:620`, `:679`). O desvio está só no `03:165`.
  - `01:142` fixa a mensagem `'Hub de cards fora de um request de painel.'`. O código diz `'Hub de cards exige um painel corrente, e nenhum está definido.'` (`DescobreCardsDoPainel.php:115`).
  - `01:72` fala em `abort_unless(auth()->check(), 403)`. O código usa `abort_unless($user instanceof User, 403)` (`AssistenteChatWidget.php:223`).
  - `01:220-221` diz *"Nenhum helper novo compartilhado entre arquivos … a exceção é `painelCorrente()`"*. Mas `Paineis::correnteOuPadrao()` é usado em 4 arquivos (passo 4b).
  - `01` `## Commits` lista mensagens que não correspondem a `git log main..HEAD`.
- **Repro**: abrir cada `arquivo:linha` acima nos dois lados.
- **Ação exigida**: corrigir o 01 no ponto de origem, com a marca `*(alterado em …)*`. Não basta o registro no 03.

### QA-06: a página de qualidade ainda lista o level 8 como "não adotado" · Minor · destino 1
- **Dimensão**: L5
- **Observado**: `docs/pt/referencia/qualidade-de-codigo.md:311` (e `docs/en/…:315`), na seção "Os níveis que o kit **não** adotou", ainda diz *"PHPStan level 8 | 48 erros | roadmap, prioridade alta"*. A nota `:327` / en `:331` ainda chama o 48 de dado a remedir. O topo da mesma página diz que o kit roda no 8.
- **Ação exigida**: mover a linha para "adotado" ou retirá-la, em pt e en. O passo 7 do 01 listava esses arquivos e esta linha escapou.

### QA-07: número divergente de arquivos com erro no level 8 · Minor · destino 1
- **Dimensão**: L3 e L6
- **Observado**: `00-requisito.md:45` diz *"48 erros em **16** arquivos"*. A tabela do 01 (`:50-66`) e a baseline do 03 (`:9-13`, obtida por `uniq -c`) somam 48 em **14** arquivos. O `grep -rn "16 arquivos"` na wiki casa só o 00, e o 04 (`:32`) já admitia *"eram 16, contagem do plano sem o código"*.
- **Ação exigida**: corrigir o número no 00, fora do Texto Original, com a marca de alteração.

### QA-08: o step 7 não fechou a Verificação Final · Minor · destino 1
- **Dimensão**: L6
- **Observado**: todas as caixas de `## Verificação Final` do 03 (`:59-67`) e do 01 (`:229-234`) estão `[ ]`, e `03:50 [ ] CHANGELOG` também, embora o CHANGELOG esteja no commit `87164c6`. Não há número alegado para reproduzir nessa seção.
- **Números fora dela, reproduzidos**:
  - diff de IDs → só `CT-03`;
  - citações → **8/8 ok** pelo script da `feature-wiki`;
  - 6b: 27 CTs; os 4 arquivos → **107 passaram, 1.461 asserções**; o `diff` de IDs da cobertura volta só com os 7 declarados;
  - `grep -c "alterado em 2026-09-26"` no 04 da cobertura → 5 linhas, que cobrem os 3 cenários alegados e o cabeçalho.
- **Ação exigida**: fechar as caixas com a saída colada. Os valores acima servem.

### QA-09: os scores de mutação de dois arquivos têm duração implausível · Minor · destino 4
- **Dimensão**: K (plausibilidade)
- **Observado**:
  - `DescobreCardsDoPainel`: 52 mutantes testados em 2,42 s (46 ms por mutante), com `--no-cache`.
  - `DefinirSenhaPorEmail`: 25 mutantes em 2,81 s (110 ms por mutante).
  - Um único processo filtrado dos mesmos testes leva ~2,2 s (`pestw.cmd … --filter=CT-09 --bail` → 2,21 s).
  - Todos contam como mortos. É a assinatura de subprocesso que morre cedo, a mesma do arnês quebrado. `KitCobertura` e o widget rodam de verdade, pois produziram sobreviventes.
- **Ação exigida**: investigar o arnês nesses dois alvos. A suspeita é o `--filter` com os nomes desses testes no Windows. Até lá, os dois scores são "Não Verificado". Não reprova a feature.

## Matriz de Rastreabilidade

| RQ | Cláusula | Passo PRD | CT | Código | Resultado | Veredito |
|----|----------|-----------|----|--------|-----------|----------|
| RQ-03 | regras de validação, teste e qualidade | 5 | CT-01…03 | `phpstan.neon` | só "qualidade" foi decomposta | ❌ QA-01 |
| RQ-04 | retrocesso reprova a suíte | 5 | CT-01…08 | `QualidadeDeCodigoTest` | CT-08 não trava código futuro | ⚠️ QA-03 |
| RQ-05 | merge + tag | 8 | — (por desenho) | — | pendente, vem depois do gate | Não Verificado |
| RQ-06/10 | causa, não silêncio / widget recusa | 1–4 | CT-16, CT-22 | `AssistenteChatWidget` | guarda de `mensagemPendente` sem teste | ⚠️ QA-04 |

RQ-01, RQ-02, RQ-07, RQ-08 e RQ-09 fecham sem lacuna. Evidências:
- RQ-01 e RQ-02: `grep -- '- [ ]'` nos dois 03 ancestrais volta só com a recusa declarada do ponytail;
- RQ-07: sentinela `naArvoreDoKit()` conferida em `QualidadeDeCodigoTest` e `CoberturaDeTestesTest`;
- RQ-08: CT-23 verde;
- RQ-09: `git diff main -- phpstan.neon` mostra só a linha do `level`, e o CT-06 exige 3 entradas.

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ❌ | QA-01 |
| B | Fronteiras | ⚠️ | borda de 2000 sem teste (QA-04); o resto estático, sem achado |
| C | Matriz de permissão | ✅ | widget: visitante 403 nas 6 ações, autenticado alheio 404 nas 2 com id; `conversaId` é `#[Locked]` |
| D | Observabilidade | ✅ | nenhum log novo; exceções citam id e role_id, sem PII; log de negação sem teste (dentro do QA-04) |
| E | Performance | ✅ | nenhuma query nova (`historico()` com os mesmos filtros) |
| F | UX de erro | ✅ | convite sem papel vira 500 com mensagem no log, aceito pela premissa P-05 do 00 |
| G | Tema e cor | ⏭️ pulada | sem superfície de UI nova, app não servido |
| H | Acessibilidade | ⏭️ pulada | idem |
| I | Segurança da superfície | ✅ | IDOR coberto (CT-16, CT-22); escopo nulo fecha; destino de erro `/financeiro` → `route('login')` conferido estaticamente (rota existe) |
| J | Regressão adjacente | ✅ | suíte inteira verde; CTs da ancestral rodam nela |
| K | Adequação da suíte | ❌ | QA-02, QA-03, QA-04, QA-09 |
| L | Consistência documental | ❌ | L1 ok (só CT-03, por desenho); L2 8/8; L3 QA-05, QA-07; L4 rules ok; L5 QA-02, QA-06; L6 QA-02, QA-08 |

Nenhuma skill de `qa-skills` foi usada; os fallbacks inline cobriram o que faltou.

## Débitos Aceitos

Nenhum até agora: o veredito reprova. Os achados QA-06 a QA-09 viram débito se sobrarem depois da correção dos Major.

## Suspeitas Não Confirmadas

- O CT-17 não foi medido contra a `main`, embora o 04 (`:612`) exigisse. O docblock do teste justifica: a única diferença é um `continue` inalcançável. O raciocínio é aceitável, mas a medição não existe.
- O script de citações acusa `ERRO KitInstall.php:oferecerEstrela:635` na wiki `cobertura-de-testes`. É efeito do path curto, porque a linha 635 contém o `exec`. É anterior à branch e o arquivo não foi tocado por ela.

## Não Verificado

- Dimensões dinâmicas em navegador (B, F, G, H, I): app não servido. B, C, F e I rodaram por Pest; G e H foram puladas.
- `--mutate` de `DescobreCardsDoPainel` e `DefinirSenhaPorEmail`: duração implausível (QA-09).
- `--mutate` de `Convite::papelOuFalha`: não rodado, por custo (o arquivo é grande).
- 03 `:36` *"3 mutantes no neon derrubam o caso certo"*: exigiria editar o `phpstan.neon`, o que este juiz não faz.
- 03 despacho 8, *"falsificabilidade 9/11"*: sem comando reproduzível.
- RQ-05 (tag): acontece depois do gate. Conferir com `git tag --contains <sha de #107>`.
- Playwright MCP e Boost `browser-logs`: não usados, porque não há superfície de UI nova.
