# Relatório de QA — Roadmap item 1: preferências de aparência por usuário

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo. A UI tem JS (o recolher do menu é Alpine) e o domínio inclui autorização por duas chaves
> Natureza da wiki: `nova` · Toca infra compartilhada: sim → render hook de densidade/fonte, largura e modo do menu e cor nos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 3 · Cosmético: 1. Os quatro achados são novos neste ciclo: QA-07..QA-10
- Não verificadas: E (contagem de queries) e K (K2). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Perguntas ao solicitante abertas: Q5, Q6 e Q7, cobertas pelas premissas vigentes P-15, P-16 e P-13. Entram como débito
- Ambiente: app em `http://127.0.0.1:8091` (sem tenancy) e `http://127.0.0.1:8092` (com tenancy) · Pest 5.0.5 · Boost MCP indisponível (falhou ao conectar) · Playwright MCP indisponível. Usei o Playwright de `node_modules` por script headless, só contra o localhost

Os dois Major do ciclo 1 foram corrigidos e conferidos. Não há defeito de comportamento. Os achados novos são de texto: um comentário de código falso, asserções sem `Então` no `04`/`05` e marcas de alteração mal postas.

## Ciclo 1 — situação dos achados

| ID | Sev. | Situação no ciclo 2 |
|---|---|---|
| QA-01 | Major | **Fechado.** Os números da `## Testes` se reproduzem: 35/240, 41/235, 11/46, 3/42, 14/99, 16/120, 4/28 e Browser 7/89. O roteiro versionado `roteiro-playwright/` dá 17/17 (8091) e 23/23 (8092) com o banco limpo. A suíte Browser inteira e o "59 de 81" seguem em *Não Verificado* |
| QA-02 | Major | **Fechado** no `01` e no `02`. Uma cópia da frase falsa sobrevive no código: QA-07 |
| QA-03 | Minor | **Fechado.** Texto do `04` (P-13, `$fillable`, Q5/Q6), CT-41..CT-46 no Índice e no Mapa, e roteiro do `05` preenchido |
| QA-04 | Minor | **Código fechado.** No 8091 o placeholder é "Padrão — Amber, a do Filament"; no 8092 ele leva o sufixo da organização. Falta o `Então` no `04` (QA-08) |
| QA-05 | Minor | **Encaminhado.** Q7 registrada no `00`, P-13 vigente. Fica como débito até a resposta |
| QA-06 | Cosm. | **Fechado.** `grep -c` dá 45 · 16 · 86 · 0, igual ao cabeçalho do `04`. O `01` declara `Tipo: nova` |

## Achados

### QA-07 — Docblock do componente repete a afirmação que o QA-02 corrigiu na wiki · Minor · destino 2

- **Dimensão**: L3. É a cópia do QA-02, item 2
- **Relacionado a**: P-12, CT-05, `## Superfície Livewire` do `02`
- **Esperado**: o `02`, já corrigido, diz que o `Select` aceita uma lista forjada e que a barreira é `salvar()`.
- **Observado**: o docblock de `App\Livewire\PreferenciasDeAparencia`, na seção *O que chega do navegador*, ainda diz "O `Select` recusa valor fora das opções na validação".
- **Repro**:
  1. No 8091, logado como master, abrir `/admin/meu-perfil`.
  2. No console: `Livewire.all().find(c => c.name.includes('aparencia')).$wire.$set('data.densidade', ['compacto'], false)` e depois `salvar()`.
  3. Resultado: notificação "Aparência salva", e o log `[PreferenciasDeAparencia@salvar]` grava sem a chave `densidade`. O valor passou pela validação e foi coagido para o padrão.
- **Evidência**: `grep -n "recusa valor fora das opções" app/Livewire/PreferenciasDeAparencia.php` acha a frase. No `storage/logs/laravel.log`: `"aparencia":{"fonte":"grande","cor":"Purple","menu":"oculto"}`. O comportamento está certo (CT-05, linha `["denso"]`); só o comentário está errado.
- **Destino**: 2
- **Ação exigida**: corrigir o comentário para dizer que o `in()` aceita lista e que a redução por `AparenciaDoUsuario::escolhas()` é a barreira.

### QA-08 — Asserções sem `Então` no `04`/`05`: CT-B01 ampliado e CT-31 fortalecido só no teste · Minor · destino 3

- **Dimensão**: L1
- **Relacionado a**: CT-B01 (BP-37), CT-31 (QA-04 do ciclo 1), P-16, RQ-04
- **Esperado**: toda linha de dataset e toda asserção têm Exemplo e `Então` no Gherkin, e um mutante previsto que ela mata.
- **Observado**:
  - **CT-B01.** O teste tem 4 linhas de dataset: `/admin` nos dois modos, e `/app` e `/infra` em "Esconder tudo". Ele também reabre o menu pelo botão do topo e confere que o item continua visível e clicável. O Gherkin do `05` tem 2 Exemplos, só `/admin`, sem o passo de reabrir. A tabela de mutantes (MB1–MB4) não prevê o "não reabre".
  - **CT-31.** O teste agora afirma que a palavra "organiza" não aparece em nenhum lugar da seção, placeholders inclusive. O `Então` do `04` continua só "não mostra o aviso sobre organizações", e o único mutante é o M59. O ciclo 1 pediu o fortalecimento pela `feature-test-design`, e o `04` não mudou nesse cenário (diff de `cb615687`).
- **Repro**: comparar o `[CT-B01]` de `tests/Browser/PreferenciasDeAparenciaTest.php` com o Gherkin do CT-B01 no `05`. Depois, `tests/Kit/PreferenciasDeAparenciaTest.php`, cenário `[CT-31]`, com o Esquema do CT-31 no `04`.
- **Evidência**: `ids-ct.sh` dá exit 0, porque só confere IDs. O descompasso é do dataset contra os Exemplos (leitura da L1).
- **Destino**: 3
- **Ação exigida**: pela `feature-test-design`, acrescentar ao CT-B01 os Exemplos de `/app` e `/infra` e o `Então` de reabrir, com um mutante "botão do topo não reabre". No CT-31, acrescentar o `Então` "nenhuma menção a organização, placeholders inclusive" e um mutante "sufixo da organização incondicional". Os testes já existem; o vermelho do CT-31 contra o código antigo é alegado pelo `03` e não foi reproduzido por mim (ver *Não Verificado*).

### QA-09 — `## Impacto` do `01` prevê ação nova no `PermissoesDeAcoesTest`; a feature não cria Action · Minor · destino 1

- **Dimensão**: J (previsto × medido) e L3
- **Relacionado a**: `## Impacto em Features Existentes` do `01` (linha "Inventários"); `## Conformidade com Rules` do `03` (linha `filament.md`)
- **Esperado**: o impacto previsto bate com o medido.
- **Observado**: o `01` diz "`PermissoesDeAcoesTest` (ação nova de salvar)". O `03` diz "nenhuma Action nova (o salvar é método do componente)". `tests/Kit/PermissoesDeAcoesTest.php` não está no diff e não menciona aparência.
- **Repro**: `grep -n "PermissoesDeAcoes" wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/*.md` e `git diff --name-only main...HEAD | grep PermissoesDeAcoes` (vazio).
- **Destino**: 1
- **Ação exigida**: corrigir a linha do `01` com a marca `*(alterado em …)*`. Nenhum outro arquivo da wiki repete a afirmação.

### QA-10 — Marcas de alteração mal postas no `01` e célula ambígua em `## Despachos` · Cosmético · destino 1

- **Dimensão**: L3 / L6
- **Observado**:
  1. No passo 5 do `01` ficaram duas marcas. A segunda, "*(alterado em 2026-11-18: era `mutateFormDataBeforeSave()`)*", contradiz o texto atual, que agora nomeia `mutateFormDataBeforeSave()` de propósito.
  2. Na linha de log do passo 6 do `01`, a marca foi inserida dentro da chamada `Log::warning(...)` citada, e a citação deixou de ser código válido.
  3. Na linha 15 de `## Despachos` do `03`, a coluna Contexto diz "conversa". O `06` do ciclo 1 declara "contexto novo sem a conversa".
- **Repro**: `grep -n "era \`mutateFormDataBeforeSave()\`" 01-plano-acao.md`; `grep -n "QA-02 do quality gate, ciclo 1)\*, \['user_id'" 01-plano-acao.md`; `grep -n "^| 15 " 03-progresso.md`.
- **Destino**: 1
- **Ação exigida**: no passo 5, juntar as duas marcas numa só, que conte as duas mudanças. No passo 6, levar a marca para fora da citação do log. Na linha 15 do `03`, trocar "conversa" por "contexto novo, sem a conversa".

## Matriz de Rastreabilidade

Sem lacuna. `rastreabilidade.sh` exit 0. As 14 RQ e as P-01..P-17 têm passo, CT e código. `07-tickets/` não existe: feature não fatiada, e o `03` registra a decisão.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; `indice.sh --check` não se aplica (sem `07-tickets/`). Q5, Q6 e Q7 estão abertas com premissa vigente e entram como débito. Nenhuma `RQ` aberta |
| B | Fronteiras e dados | ✅ | Lista forjada `['compacto']` no `$wire`: passa pela validação, é coagida e só a densidade vira padrão, na própria pessoa. Não é defeito (CT-05); ver QA-07. Os forjados do ciclo 1 (Gray, `</style><script>`, 12345, campo extra) não foram refeitos: o código de gravação não mudou |
| C | Matriz de permissão | ✅ | Desde o ciclo 1, o diff não toca autorização. CT-06, CT-27 e CT-28 seguem verdes, e a regressão inteira também |
| D | Observabilidade | ✅ | `laravel.log`: `[PreferenciasDeAparencia@salvar]` com context `user_id` e valores do vocabulário. Sem PII. Canal padrão (D7) |
| E | Performance | ⏭️ | Não verificada; ver *Não Verificado* |
| F | UX de erro | ✅ | QA-04 corrigido: o placeholder sem tenancy não fala de organização (8091); com tenancy leva o sufixo e o aviso (8092) |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0 nos arquivos AM. Axe com tema escuro forçado (`dark` no `<html>` confirmado) em `/admin/meu-perfil` e nas Configurações: 0 violações |
| H | Acessibilidade | ✅ | axe-core (o `axe.min.js` do `pest-plugin-browser`, injetado pelo Playwright): 0 violações no Meu perfil e nas Configurações, claro e escuro, no 8091. No 8092: perfil com aviso, edição da organização com o interruptor e `/admin` com o menu "Esconder tudo" recolhido (largura 0). Controle positivo: um `<img>` sem `alt` injetado é acusado (`image-alt`, critical). Não há CT-B versionado de acessibilidade |
| I | Segurança da superfície nova | ✅ | O step 9 cobriu 13 achados, 5 deles rejeitados. Além dele: nenhuma rota nova, nenhum `DB::raw`; `users.aparencia` fora do `$fillable` e em `$hidden`; o `salvar()` usa o usuário autenticado; o toggle da organização some (não desidrata) com a chave da instalação desligada |
| J | Regressão adjacente | ⚠️ | `pest --tia --parallel --processes=3 --exclude-testsuite=Browser`: 6182 testes, 6177 passaram, 3 pulados, 2 falhas. As duas são do `QualidadeDeCodigoTest` (CT-01, CT-04): timeout de 60 s do `phpstan dump-parameters` com a máquina carregada. O arquivo isolado dá 31/31 (destino 4). Previsto × medido: ver QA-09 |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, uma linha: o `[CT-16]` de `tests/Kit/AparenciaEfetivaTest.php`. Rejeitada: o teste tem `expect(...)->{$preferencia}->toBe($fabrica)`. K2 não verificado (ver *Não Verificado*). Revisão adversarial do `04` feita em 2 rodadas |
| L | Consistência documental | ⚠️ | QA-07, QA-08, QA-09, QA-10. L1 `ids-ct.sh` exit 0; os `grep -c` do cabeçalho conferem. L2 `citacoes.sh` exit 1, com 3 linhas, todas do `06` do ciclo 1 (citações sem símbolo); este relatório o substitui. L4 `conformidade-rules.sh` exit 0; conferi também contra o código `config.md`, `views.md`, `models.md`, `providers-filament.md`, `specs.md`, `livewire.md` e `settings.md` (cumpridas). L5: docs pt × en, CHANGELOG e glossário coerentes entre si e com P-13. L6 `checkbox-sem-evidencia.sh` exit 0. L8 n/a: sem `08`, e o `03` decidiu `Roteiro manual: não`. `roteiro-manual.sh` exit 0 |

## Débitos Aceitos

- Q5, Q6 e Q7 abertas ao solicitante. P-15, P-16 e P-13 estão implementadas como premissa vigente, e as docs de usuário (pt/en) já descrevem a P-13 (a escolha vale no `/admin` e no `/infra`). Se a resposta de Q7 for outra, mudam o código, os CT-12/CT-09 e as docs.
- QA-07, QA-08, QA-09 e QA-10, se não forem corrigidos neste ciclo. Replicar no `03`.

## Suspeitas Não Confirmadas

- Rodado num banco já usado, o roteiro `manual.cjs` deu 14/17, porque o master tinha escolha gravada. Ele depende do `prepara.sh` (banco novo) a cada execução. Isso é pré-condição documentada no cabeçalho do `prepara.sh`, não defeito.

## Não Verificado

- **K2** — prova indispensável pendente. Rodei `XDEBUG_MODE=coverage cmd //c "$(cygpath -w …/pestw.cmd)" tests/Kit/AparenciaEfetivaTest.php --mutate --path=app/Support/AparenciaDoUsuario.php --covered-only --no-cache --parallel --processes=3 --no-tia`. Resultado: 38 mutantes, 100 %, `Duration` 0,86 s, num arquivo de testes que leva 31 s sozinho. A saída não lista mutante por mutante. No ciclo 1, o mutante equivalente `EmptyStringToNotEmpty` na linha do `paletaDaPessoa()` já tinha sido contado como morto. O arnês continua contando mortes falsas, e nenhum score é certificável.
- **E** — sem `pest-plugin-agent` (`ls vendor/pestphp`: ausente), sem Boost MCP e sem Debugbar/Telescope. Pela leitura, o decisor não tem I/O (CR-03 mediu 23 µs por chamada), mas a contagem de queries não foi medida.
- **L6, suíte Browser inteira** ("261 testes, 209 passaram, 52 pulados"): não rodada, por custo de tempo (em série). O arquivo da feature reproduz 7/7 e 89 asserções.
- **L6, Falsificabilidade** ("Kit 59 de 81", "Tenancy 32 de 34"): reproduzir exige `git apply -R` em `app/`, que é vedado a este gate. O "81" é a contagem da época; hoje os três arquivos somam 87.
- **QA-08, vermelho prévio do CT-31** contra o sufixo incondicional: o `03` o alega. A reprodução por `auto_prepend_file` precisava gravar a versão antiga num arquivo temporário, e o hook do gate negou.
