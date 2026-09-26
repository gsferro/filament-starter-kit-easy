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

---

<!-- Ciclo 2 — gravado pela sessão, verbatim, a partir do retorno do sub-agente fw-qa-gate. Sem edição. -->

## Veredito, ciclo 2

> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Entradas recebidas: path da wiki, projeto, branch e base, e a indicação de que o app não está servido. Não recebi resumo nem justificativa da sessão. Li as mensagens dos commits `501b2cb` e `0b87903` como alegações a conferir, não como verdade.
> Perfil: **completo**, sem mudança desde o ciclo 1 (domínio sensível, K com `--mutate`). Regressão: sim, e a reconciliação 6b está no escopo.

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 7 (QA-08, QA-09, QA-10, QA-11, QA-12, QA-13, QA-14) · Cosmético: 0
- Os 5 Major do ciclo 1 estão fechados. Das 4 Minor do ciclo 1, 2 fecharam e 2 viraram débito. Há 5 Minor novas, nenhuma Major.
- Ambiente: app não servido. Pest 5, com PCOV e Xdebug carregados (`php -m`) e `pest-plugin-mutate` presente (`ls vendor/pestphp/`). Playwright MCP não usado, porque não há superfície de UI nova.
- Gates que reproduzi agora:
  - `pint --test` → `passed`
  - `phpstan analyse` → `[OK] No errors`
  - `filacheck` → `All 17 rules passed!`
  - suíte Unit, Feature, Kit e Tenancy com `--parallel` → **3.078 testes, 3.075 passaram, 3 pulados, 13.109 asserções, 192,6 s**
- Comparação com o ciclo 1: são +6 testes, que são exatamente as 4 linhas do CT-24 mais o CT-25 e o CT-26. As asserções caíram 22 porque o CT-08 deixou de fazer 3 asserções por arquivo e passou a fazer 3 no total. O badge de 1.671 bate: são +3 blocos `it()`, conferidos pelo `[CT-50]` na suíte.

### Disposição dos achados do ciclo 1

| Achado | Disposição | Evidência deste ciclo |
|---|---|---|
| QA-01 (Major, destino 1) | **fechado como especificação registrada** | `00:56-69` traz Assumido e Se negado para "validação" e "teste". A resposta do mantenedor continua pendente, e ela é pré-condição do merge (RQ-05). |
| QA-02 (Major, destinos 1 e 3) | **fechado** | O grep de `0 sobreviventes\|zero sobreviventes\|Não testado é` nas docs e no CHANGELOG só casa a linha nova `158, sendo 0 sobreviventes`, que eu remedi e que é verdadeira. Remedição: `pestw.cmd tests/Kit/KitCoberturaTest.php --mutate --path=app/Console/Commands/KitCobertura.php --covered-only --no-tia --no-cache` → **158 tested, 100 %, 31,45 s** (39 s de relógio). Aceito o número: o mesmo arnês, no ciclo 1, nomeou 4 sobreviventes com duração equivalente (33,6 s), e as asserções novas miram exatamente esses 4. O `Customizador:470` foi declarado equivalente. Conferi `config/kit.php:355`, onde a chave sempre existe, e o docblock de `pluralSugerido` chama o valor do default de defeito. Aceito como destino 5. |
| QA-03 (Major, destino 3) | **fechado** | `QualidadeDeCodigoTest.php` `[CT-08]` agora varre `paths`/`excludePaths` de `configuracaoEfetivaDoPhpstan()` e compara o mapa inteiro com `toBe([...2 arquivos])`. Pela leitura, um `@phpstan-ignore` em `app/Models/Tenant.php` entra no mapa e reprova. |
| QA-04 (Major, destino 3) | **fechado no nível do teste; a medição de mutação é "Não Verificado"** (ver QA-09) | CT-24 cobre nulo, 1999, 2000 e 2001, com `assertPrompted`/`assertNeverPrompted` e contagem de linhas. CT-25 cobre participante, `conversaId` e pendência nula. CT-26 cobre canal `ai`, prefixo, as 3 chaves e `once()`. Pela leitura, os oráculos matam M41–M53. |
| QA-05 (Major, destino 1) | **fechado** | `01:24`, `:71-73`, `:87-89`, `:144`, `:151-153`, `:223-225` e `## Commits` estão corrigidos, com a marca `*(alterado em 2026-09-26: QA-05…)*`, e batem com o código. |
| QA-06 (Minor) | **fechado** | pt `:315` e en `:318` dizem **adotado**. |
| QA-07 (Minor) | **fechado** | `00:45` diz 14, com marca. `grep -rn "16 arquivos"` fora do 06 → vazio. |
| QA-08 (Minor) | **aberto → débito** | Ver QA-08 abaixo. |
| QA-09 (Minor, destino 4) | **aberto e ampliado → débito** | Ver QA-09 abaixo. |

### Achados reabertos e novos

### QA-08: a Verificação Final continua sem as caixas dos gates · Minor · destino 1
- **Dimensão**: L6
- **Observado**:
  - `03:59-62` (pint, phpstan, filacheck, suíte) e `01` `## Verificação Final` continuam `[ ]`.
  - `03` `## Quality Gate` (`:110-112`) está vazio, embora o ciclo 1 tenha rodado. O passo 8 da skill manda registrar ali o veredito e o número do ciclo.
- **Ação exigida**: fechar as caixas com a saída acima (3.078/3.075/3, 13.109 asserções, 192,6 s; `[OK] No errors`; `passed`; `17 rules`) e registrar os ciclos 1 e 2 em `## Quality Gate`.

### QA-09: o arnês de `--mutate` dá "100 %" implausível em três alvos, agora também no widget · Minor · destino 4
- **Dimensão**: K (plausibilidade)
- **Observado** (todos os comandos com `--no-cache --no-tia`):

  | Alvo | Resultado | Ciclo 1 |
  |---|---|---|
  | `AssistenteChatWidget` | 74 tested, 100 %, **7,87 s** (≈106 ms por mutante) | 44 cobertos em 62,87 s, com 11 sobreviventes |
  | `DescobreCardsDoPainel` | 52 em **2,36 s** (≈45 ms por mutante) | igual |
  | `DefinirSenhaPorEmail` | 25 em **2,72 s** (≈109 ms por mutante) | igual |

  - Um único processo filtrado do widget leva 2,75 s (`--filter=CT-24`), com 4 seeders no `beforeEach`. Um mutante morto precisa passar por esse boot. 106 ms não cabem nisso.
  - O processo do Pest sem teste casado sai com código 1 em 0,32 s, e o plugin conta isso como morto. É a mesma assinatura do arnês quebrado.
  - Controle com `--filter=CT-25`: o mutante `Line 96: GreaterToGreaterOrEqual` aparece como morto, e o CT-25 (pergunta de 15 caracteres) não tem como matá-lo. Com `--filter` do usuário o arnês também mente: `KitCobertura` com `--filter=CT-06` deu 73 em 3,19 s. Isso confirma a suspeita do ciclo 1 sobre o `--filter` no Windows.
- **Consequência**: a alegação da mensagem do `501b2cb` (*"100 % dos mutantes da linha 96 e do log mortos"*) não tem medição válida por trás. Ela não está na wiki nem nas docs, então não gera achado L6. Os scores desses três alvos são "Não Verificado".
- **Ação exigida**: diagnosticar o arnês nesses alvos (hipótese: nomes de teste e de dataset com `"`, `−` e `[` no `--filter` que o plugin monta, `MutationTest.php:91-96`) e acrescentar à página de qualidade o aviso de que `--filter` com `--mutate` no Windows invalida o score. Não reprova a feature: os testes do QA-04 se sustentam pela leitura.

### QA-10: o 04 contradiz a si mesmo sobre onde vivem o CT-27 e o CT-28, e cita uma premissa inexistente · Minor · destino 1 · novo
- **Dimensão**: L3
- **Observado**:
  - `04:757` (R11, "Colisão de ID") diz que os casos entram em `KitCoberturaTest` **com os IDs CT-27 e CT-28, que não colidem**.
  - `04:778` diz que `[CT-12]` e `[CT-13]` **não são reescritos**.
  - `04:849-855` e o código fazem o oposto: são asserções a mais dentro de `[CT-12]`/`[CT-13]`, e nenhum `[CT-27]`/`[CT-28]` existe no arquivo (`grep -c` → 0 e 0).
  - `04:791` remete à *"pergunta P-08 abaixo"*, mas não existe P-08 (`grep -n "P-0[0-9]"` só vai até P-07). A pergunta do `pluralSugerido` também não foi espelhada no bloco "Premissas devolvidas" do 00.
- **Ação exigida**:
  - reescrever `04:757` e `:778` conforme a decisão de `:849`;
  - rotular a pergunta como P-08;
  - espelhá-la no 00.

### QA-11: números velhos no 03 depois do próprio ciclo · Minor · destino 1 · novo
- **Dimensão**: L3 e L6
- **Evidência**: `grep -rn "97,47\|1\.668\|8/8"` na wiki casa:
  - `03:181`: *"os números remedidos continuam valendo (158 mutantes, **4 sobreviventes**, 97,47 %, 38 s)"*. As docs e o CHANGELOG dizem 100 % e 31,25 s, e eu medi 100 %.
  - `03:66`: *"casos de teste 1.668"*. O README diz 1.671, travado pelo `[CT-50]`.
  - `03:65` e `03:132`: *"8/8 ok"*. O script de citações da `feature-wiki` sobre `00`–`04` dá hoje **9/9 ok**, porque entrou `MutationTest.php:hasFinished:120`.
- **Ação exigida**: atualizar `03:65`, `:66`, `:132` e `:181` com os valores atuais.

### QA-12: o `[CT-27]` da cobertura aceita uma citação para qualquer quantidade de sobreviventes · Minor · destino 3 · novo
- **Dimensão**: K (oráculo fraco). O teste é novo nesta branch.
- **Esperado**: o Gherkin da cobertura (`04:1047`) diz *"quando há sobreviventes, **cada um** está nomeado por arquivo e linha"*.
- **Observado**: o laço de `tests/Kit/CoberturaDeTestesTest.php` `[CT-27]` refaz o mesmo `assertMatchesRegularExpression('~app/\S+\.php:\d+~', $texto)` para toda contagem diferente de zero. Um único `app/X.php:N` na seção satisfaz qualquer número de linhas.
- **Repro**: pela leitura, basta publicar `158, sendo 3 sobreviventes` mantendo só a citação do `Customizador:470`. O teste fica verde.
- **Ação exigida**: a `feature-test-design` fecha "cada um". Por exemplo, o total de sobreviventes publicado ≤ o número de citações `arquivo:linha` distintas, com os equivalentes declarados contando como citação.

### QA-13: o 04 prometeu caracterizar o CT-24…CT-26 contra a `main` e não há rastro disso · Minor · destino 1 · novo
- **Dimensão**: L6
- **Observado**: `04` R10 diz *"Caracterização: medir CT-24…CT-26 na `main` antes do diff"*, e a R11 diz o mesmo. Não há resultado registrado no 03 nem no 04. É o mesmo padrão da suspeita sobre o CT-17 no ciclo 1.
- **Ação exigida**: colar a medição ou marcar a caracterização como não feita, com o motivo.

### QA-14: a pendência do QA-01 fica dentro do 00, e não no controle do merge · Minor · destino 1 · novo
- **Dimensão**: A
- **Observado**: `00:69` diz *"Levado ao mantenedor no fim da sessão"*. O `03 ## 8. Release` não condiciona o merge à resposta. Se a leitura for negada, o RQ-03 muda de natureza.
- **Ação exigida**: acrescentar ao passo 8 do 03 a caixa *"resposta do mantenedor sobre 'regras de validação/teste' (00:56)"* antes de PR, merge e tag.

## Dimensões, ciclo 2

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ⚠️ | QA-01 fechado como especificação; QA-14 (Minor) |
| B | Fronteiras | ✅ | borda de 2000 coberta (CT-24, 1999/2000/2001/nulo) |
| C | Matriz de permissão | ✅ | sem mudança desde o ciclo 1; CT-26 soma a trilha |
| D | Observabilidade | ✅ | log de negação com canal, prefixo e contexto afirmados; `user_id`/`conversa_id`, sem PII |
| E | Performance | ✅ | nenhuma query nova no ciclo |
| F | UX de erro | ✅ | sem mudança |
| G, H | Tema e acessibilidade | ⏭️ pulada | sem superfície de UI nova; app não servido |
| I | Segurança da superfície | ✅ | sem superfície nova no ciclo |
| J | Regressão adjacente | ✅ | suíte inteira verde; nenhum teste antigo alterado além do `[CT-12]`/`[CT-13]` reforçados e da regex do `[CT-27]` |
| K | Adequação da suíte | ⚠️ | KitCobertura 100 % medido e plausível; widget, hub e DefinirSenha "Não Verificado" (QA-09); QA-12 |
| L | Consistência documental | ⚠️ | L1: 04 × teste, 28 cenários e 88 mutantes batem com o cabeçalho; só `CT-03`/`27`/`28` sem ID, por desenho. L2: 9/9 ok. L3: QA-10, QA-11. L4: rules ok (`testes.md` conferida para os testes novos). L5: pt × en × CHANGELOG coerentes. L6: QA-08, QA-11, QA-13 |

## Débitos Aceitos (ciclo 2)

QA-08, QA-09, QA-10, QA-11, QA-12, QA-13 e QA-14, todos Minor. Devem ser replicados em `03-progresso.md`.

## Convergência

O ciclo 2 trouxe achados novos, mas só Minor. Com 0 Blocker e 0 Major, o veredito não reprova, então **não há ciclo 3 obrigatório**. Os débitos seguem o fluxo normal, e o teto de 3 ciclos não foi atingido.

## Não Verificado (ciclo 2)

- **Scores de `--mutate`** de `AssistenteChatWidget`, `DescobreCardsDoPainel` e `DefinirSenhaPorEmail`: a duração é implausível e o controle mostrou morte falsa (QA-09).
- **`CustomizadorDaInstalacao`, 225 mutantes, 98,22 %**: não remedi. O número não mudou na branch e o alvo está fora do diff.
- **Dimensões de navegador** (G, H e B/F/I dinâmicas): app não servido.
- **Falhar de fato com um `@phpstan-ignore` em `Tenant.php`** (QA-03) e **matar de fato M41–M53** (QA-04): exigiriam editar a árvore, o que este juiz não faz. Fechei esses dois pela leitura do oráculo.
- **RQ-05** (merge e tag): acontece depois do gate.
