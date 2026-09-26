# Casos de Teste: Varredura e PHPStan no level 8

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só os paths e a superfície, recebidos do orquestrador. **Não lido**.)
> Derivado do **requisito**, não do plano. O `01` e o `02` não foram abertos (cegueira exigida).
> `app/` foi lido só para achar nomes de classe, método e rota. `tests/` foi lido para achar os helpers e os testes que já existem.
> Onde o `Então` é "o comportamento da `main`", o cenário é de **caracterização**. O oráculo é a cláusula "tratar nulidade
> não muda comportamento observável" (RQ-06, *"trata a causa, não cala"*), e o valor é **medido na `main` antes do diff**.
> Se o cenário ficar vermelho na `main`, o errado é o CT, não o código.
>
> **Adendo 1 (2026-09-26, RQ-07…RQ-10)**, achados do step 6.5 derivados só do `00`, sem 01/02/03 e sem código como oráculo. Mudanças: CT-02 (RQ-07), CT-05/CT-06 (RQ-09, que **substitui** a parte "exceção em `ignoreErrors`" do RQ-06), CT-16 (RQ-10) e **CT-23 novo** (RQ-08, regra R9). As decisões da sessão entram como premissas fixas, não como pergunta.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A. Gate de análise estática (`phpstan.neon`, `composer.json`, CI) | 2 | 2 | 4 | padrão |
| B. Nulidade em autenticação e convite (tela de bloqueio, login social, aceite de convite) | 1 | **3** | 3 | mínimo, com **Impacto 3**, o que dispara a revisão adversarial |
| C. Nulidade nos hubs e no widget do assistente | 1 | 2 | 2 | mínimo |
| D. Migration `harden_onboarding_progress_scope` (fusão de dado, irreversível) | 2 | 3 | 6 | padrão |

- A área B tem I=3 porque um defeito ali atribui o papel errado (autorização) ou deixa o usuário sem saída no fluxo de login.
- **Técnica escalada**: na R2 (área A) usei tabela de decisão em vez de só EP. Um único predicado ("tem path") não separa o baseline por arquivo da exceção legítima, e o inventário fechado é o que separa.
- Técnicas aplicadas: EP, tabela de decisão (R2), rastreio de efeito com não-efeito (R6a, R6b, R7, R9), caracterização (R7, R8).
- Cenários: 23 · Regras: 10 (R6 dividida em R6a/R6b; R9 do Adendo 1) · Mutantes previstos: 70 (25 da revisão adversarial e 5 do Adendo 1, fora do teto) · Sem matador: 4 (lacunas declaradas M14, M15, M16 em R3b e M40 em R9)
- Contagens por comando: `grep -c "^ *\(Esquema do \)\?Cenário: \[CT-" 04-casos-de-teste.md` e `grep -cE "^\| M[0-9]+[a-z]? " 04-casos-de-teste.md`.
- **Teto do perfil**: R6b tem 4 cenários contra o teto de 1 do perfil mínimo. O estouro é pelo gate: cada ponto de entrada mata um mutante que nenhum outro mata (M29c…M29g).

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | `phpstan.neon`, script `types:check` do `composer.json`, `.github/workflows/ci.yml`, 14 arquivos com a nulidade tratada (13 alterados em `app/` e `database/`, mais `ExigirEmailVerificado`, tratado por exceção no neon) *(alterado em 2026-09-26: eram "16", contagem do plano sem o código)* | CT-01…CT-08 |
| F | Travar o nível. Não calar erro. Definir o comportamento de cada nulo alcançável: painel sem login, hub fora de painel, convite sem papel, widget sem usuário | CT-09…CT-16 |
| D | O nulo é o dado. `Panel::getLoginUrl()` é null sem `->login()` (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:380-387`). `getCurrentPanel()` é null fora de request de painel. `Convite::papel()` fica null se o papel sumir. `auth()->user()` é null para o visitante. Linhas de progresso duplicadas com escopo nulo | CT-09…CT-17 |
| I | Rota HTTP (`/app/login`, callback social), componente Livewire (widget, tela de bloqueio, hubs), método de model (`Convite::aceitar*`), migration, comando `phpstan` | CT-03, CT-09…CT-17 |
| P | PHPStan 2.2.14 + Larastan. SQLite em memória nos testes: FK ligada por padrão (`config/database.php:40`), e o arranjo do CT-13 a desliga | CT-12, CT-13 |
| O | Mantenedor que rebaixa o nível ou gera baseline para passar no CI. Projeto derivado que registra painel próprio sem `->login()` | CT-01…CT-08, CT-10, CT-11 |
| T | Não se aplica. Nenhuma regra depende de relógio, e os timestamps do CT-17 são dados fixos, não instante de observação | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1: o nível efetivo da análise é ≥ 8 e nenhum ponto de invocação o rebaixa, e a guarda do gate local roda em projeto instalado | A (padrão) | RQ-03, RQ-04, RQ-07 (Adendo 1) | EP | CT-01, CT-02, CT-03 |
| R2: nenhum erro é calado (sem baseline, exceções com escopo e inventário fechado nas 3 anteriores, escopo analisado intacto, `@phpstan-ignore` congelado) | A (padrão) | RQ-06, RQ-09 (Adendo 1) | tabela de decisão | CT-04…CT-08 |
| R3: fora de painel, o hub falha fechado com exceção clara e nunca mostra cartões de outro painel | C (mínimo) | RQ-06 | EP (3 hubs) | CT-09 |
| R3b: nulo impossível pelo desenho (o `/app` sempre tem login) não vira valor inventado | B (mínimo) | RQ-06 | revisão estática | lacuna declarada |
| R4: painel sem login dá ao visitante da tela de bloqueio um destino dentro do painel | B (mínimo) | RQ-06 | EP (com/sem login) | CT-10 |
| R5: painel sem login na volta do provedor social dá um destino de login alcançável | B (mínimo) | RQ-06 | EP (com/sem login) | CT-11 |
| R6a: no model, convite sem papel falha com exceção de invariante e não atribui papel inventado | B (mínimo) | RQ-06 | EP + rastreio de efeito | CT-12, CT-13 |
| R6b: todo ponto de entrada do aceite sobrevive a papel ausente sem efeito colateral nem `Error`/`TypeError`/`AssertionError` | B (mínimo) | RQ-06 | EP por ponto de entrada + rastreio de efeito | CT-18…CT-21 |
| R7: o widget do assistente só mostra e só age sobre conversas do usuário autenticado. Sem usuário, renderiza vazio e recusa | C (mínimo) | RQ-06, RQ-10 (Adendo 1) | EP (persona) + caracterização | CT-14, CT-15, CT-16, CT-22 |
| R8: a fusão de duplicatas da migration do onboarding continua igual à da `main` | D (padrão) | RQ-06 | caracterização | CT-17 |
| R9: nenhum redirect recebe URL de login nula onde o nulo é alcançável | B (mínimo) | RQ-08 (Adendo 1) | EP (com/sem login) | CT-23 (+ lacuna M40) |

**RQ sem cenário, com a justificativa:**
- **RQ-01 / RQ-02** (pendências de código e de teste): são caixas de wiki e evidência de varredura, sem comportamento de aplicação. Quem confere é o `feature-quality-gate` (consistência documental).
- **RQ-05** (merge + tag): é operação de release, não comportamento testável em Pest. Quem confere é o QA gate, com a evidência `git tag --contains <sha de #107>` apontando a tag nova para #107–#109.
- **RQ-01 / RQ-02**, evidência para o QA gate: `grep -c -- '- \[ \]'` nos `03-progresso.md` de `cobertura-de-testes` e `plumb-e-dividas-tecnicas` volta 0, salvo a recusa declarada do ponytail.

## Fronteira com o Plano

| Item recebido do plano | Recusado como oráculo porque | Destino |
|---|---|---|
| Lista dos 14 arquivos com nulidade tratada | é escolha de implementação (onde mexer) | define **onde** procurar nulo alcançável; o CT-08 usa a lista como escopo do "nenhum ignore novo" |
| Nomes `cardsDoPainel`, `atribuirPapel`, `urlDeLoginDoPainel`, `sairPara` | são nomes de método | detalhe; nenhum `Então` cita método privado |
| Mensagem exata das exceções de invariante | é comportamento visível que o 00 não fixa (diz só "exceção de invariante") | premissa P-05; o `Então` afirma o **tipo** e o **assunto** da mensagem, não o texto |
| Fato de vendor: `getUrl()` pode ser null com tenant por domínio | o kit não liga tenant por domínio. `HasRoutes.php:178-179` só devolve null por `getRedirectUrl()` com `hasTenantDomain()` | R3b (lacuna declarada, sem cenário) |
| 403 do widget para o visitante | é o comportamento atual; o 00 não fixa 403 contra 404 | caracterização (CT-16) |

**Perguntas em aberto.** O `00-requisito.md` não foi editado (instrução do orquestrador: só este arquivo). As perguntas estão em [Perguntas para o 00-requisito.md](#perguntas-para-o-00-requisitomd), em bloco pronto para colar.

## Setup Global

### Personas
- `carla`: convidada **sem conta** (`carla@example.com`), destinatária dos convites de cadastro de R6a e R6b.
- `ana`: usuária autenticada do `/app`, com `usuarioDoKit('panel_user', 'ana@example.com')`. É dona de uma conversa do assistente com o título **"Plano de férias"**.
- `bruno`: usuário existente com o papel `infra` (`usuarioDoKit('infra', 'bruno@example.com')`), destinatário da oferta em R6a/R6b (CT-13, CT-19, CT-21) e usuário autenticado que não é dono em R7 (CT-15, CT-22).
- `visitante`: nenhum `actingAs`.

### Fixtures
- Convite pendente: `ofertaPara('carla@example.com', papel: 'admin')` para o fluxo de conta nova, e `ofertaPara('bruno@example.com', papel: 'admin')` para o usuário existente.
- Painel de projeto sem login: `painelRegistradoEmTeste('financeiro')`. O helper já cria `Panel::make()->id()->path()` **sem** `->login()`, e isso é o que torna o nulo alcançável.
- Configuração efetiva do PHPStan: `vendor/bin/phpstan dump-parameters --json`. Ele devolve a config **resolvida**, com os `includes` já mesclados. Ler o `.neon` como texto não enxergaria um baseline incluído. O stdout é JSON, e a nota "Using configuration file" sai no stderr.

### Fakes
- `Notification::fake()` no CT-13 e em R6b (CT-18…CT-21), para afirmar que nenhum aviso sai.
- Sem `Http::fake()`: o CT-11 recusa antes de falar com o provedor, pelo state inválido.

### Estratégia de DB
- `RefreshDatabase` global dos `tests/Kit` (`tests/Pest.php`, via `pest()->extend(TestCase::class)->use(RefreshDatabase::class)->group('kit')`). Tudo roda na suíte **Kit**, **exceto o CT-21**, que vai na **Tenancy** (`Tests\TenancyTestCase`, com `permission.teams` fixado antes das migrations). Nenhum cenário precisa de `admin_app`.

---

## Regra R1: o nível efetivo da análise é ≥ 8 e nenhum ponto de invocação o rebaixa

> `RQ-03`, `RQ-04` · perfil **padrão** · técnica: **EP** (onde o nível é decidido: config e linha de comando)

```gherkin
# language: pt
Funcionalidade: PHPStan travado no level 8

  Regra: o nível efetivo da análise é 8 ou mais, e nenhum ponto de invocação o rebaixa

    Cenário: [CT-01] a configuração resolvida do PHPStan declara nível 8 ou superior
      Dado a configuração efetiva lida por "phpstan dump-parameters --json"
      Quando o guarda de qualidade lê o parâmetro "level"
      Então "phpstan dump-parameters --json" saiu com código 0 e o stdout é JSON válido
      E o nível é um inteiro maior ou igual a 8, ou a string "max"
      E os parâmetros de rigor resolvidos valem "checkNullables" = true, "reportMaybes" = true e "checkUnionTypes" = true

    Esquema do Cenário: [CT-02] nenhum ponto que invoca o gate sobrescreve nível, configuração, escopo ou resultado
      Dado o ponto de invocação "<ponto>"
      Quando o guarda de qualidade lê a linha de comando do phpstan nesse ponto
      Então a linha chama "phpstan analyse" sem argumento posicional de path
      E a linha não contém flag que comece por "-l" ou "--level", nem "-c", "--configuration" ou "--generate-baseline"
      E a linha não termina em "|| true"
      E <regra_do_ponto>
      E num projeto sem ".github/", a linha <sem_github>

      Exemplos:
        | ponto                                                   | regra_do_ponto                                                                                                     | sem_github                                                              | # partição  |
        | script "types:check" do composer.json                   | o script chama o phpstan direto, sem indireção por "@outro-script", e a linha não lê nenhum arquivo de ".github/"   | roda e passa, com ao menos uma asserção executada (não é pulada)        | gate local  |
        | step do phpstan em .github/workflows/ci.yml             | o step não declara "continue-on-error" nem "if:", e o workflow dispara em "push" para "main" e em "pull_request"  | é pulada pela sentinela, e nenhuma asserção dela roda                    | gate de CI  |
        | qualquer outra chamada a phpstan em .github/workflows/* | o step não declara "continue-on-error" nem "if:"                                                                    | é pulada pela sentinela, e nenhuma asserção dela roda                    | varredura   |

    Cenário: [CT-03] o próprio comando do gate termina sem erro no nível travado
      Dado o código de app, bootstrap/app.php, config, database e routes desta branch
      Quando o mantenedor roda "composer types:check", o mesmo comando que o "composer test" chama
      Então o processo sai com código 0
      E a saída contém "[OK] No errors"
```

- CT-01 e CT-02 ficam em `tests/Kit/QualidadeDeCodigoTest.php`, ao lado do guarda "mantém os três gates do composer test".
- **CT-02 × projeto instalado (RQ-07, Adendo 1).** O `create-project` não entrega `.github/`. Por isso as cláusulas de workflow (`on:`, push/pull_request, step sem `if:`/`continue-on-error`) valem **só nas duas linhas de CI**, que rodam sob a sentinela de "arquivo não entregue" (pulam quando `.github/` não existe). A linha do composer **não** é pulada: ela roda e passa num projeto instalado. Arnês: rodar a linha do composer com `base_path` apontando para uma cópia sem `.github/`, ou estruturar o dataset para que ela nunca leia o workflow. Os dois servem, desde que a linha execute asserção.
- CT-01 **nunca é pulado**: sem `skip()` nem `markTestSkipped` condicional (binário ausente, JSON vazio, Windows). Se o `dump-parameters` falhar, o caso fica **vermelho**. Guarda que se pula em silêncio é guarda desligada.
- CT-03 é **comando de gate**, não caso Pest. Rodar o PHPStan dentro da suíte duplicaria o `composer test` (que já roda `@types:check`) e custaria minutos. A evidência é a saída colada no `03` e o job verde do CI.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `level: 7` continua no `phpstan.neon` (o nível subiu só na CLI de quem mediu) | CT-01 |
| M2 | `level: 8` no neon, mas `types:check` vira `phpstan analyse --level=7` para o gate passar | CT-02 (linha composer) |
| M3 | CI aponta `-c phpstan-ci.neon` com nível menor | CT-02 (linha CI) |
| M4 | nível 8 declarado com erros remanescentes (o gate local não foi rodado) | CT-03 |
| M4a (revisão adversarial) | `level: 8` com `checkNullables: false` ao lado: 0 erros sem nada corrigido | CT-01 (parâmetros de rigor) |
| M4b (revisão adversarial) | gate vira `phpstan analyse app/Models`, `-l7`, `\|\| true` ou `continue-on-error` | CT-02 |
| M4c (adversarial, rodada 2) | o guarda se pula quando o `dump-parameters` falha ou devolve JSON inválido, e o nível nunca é lido | CT-01 (exit 0, JSON válido, nunca pulado) |
| M4e (Adendo 1, RQ-07) | a asserção sobre o `on:` do workflow roda também na linha do composer, que reprova em todo projeto instalado (RD-01) | CT-02 (linha composer, `sem_github` = roda e passa) |
| M4f (Adendo 1, RQ-07) | a sentinela de `.github/` é posta no caso inteiro, e a linha do composer também é pulada: em projeto instalado a guarda do gate local some | CT-02 (linha composer, "ao menos uma asserção executada") |
| M4d (adversarial, rodada 2) | step do phpstan com `if:` que o desliga, workflow só em `workflow_dispatch`, ou `types:check` → `@outro` que roda com `--level=7` | CT-02 (sem `if:`, gatilhos push/PR, sem indireção) |

---

## Regra R2: nenhum erro é calado

> `RQ-06` · perfil **padrão** · técnica: **tabela de decisão** (escalada: EP não separa o baseline por arquivo da exceção legítima)
>
> | Condição | Exceção legítima | Baseline por arquivo | Exceção global | Exceção ampla por arquivo |
> |---|---|---|---|---|
> | tem `path`/`paths` | S | S | **N** | S |
> | path ≠ raiz analisada | S | S | — | S |
> | mensagem específica (não casa amostra de nulidade) | S | S | — | **N** |
> | sem chave `count` e dentro do inventário (as 3 da `main`) | S | **N** | — | S |
> | **Aceita** | ✅ | ❌ CT-04/06 | ❌ CT-05 | ❌ CT-05 |

```gherkin
  Regra: nenhum erro do nível 8 é calado por baseline, exceção ampla, corte de escopo ou ignore inline novo

    Cenário: [CT-04] a configuração efetiva não carrega baseline
      Dado a configuração efetiva lida por "phpstan dump-parameters --json"
      E o phpstan.neon da raiz
      Quando o guarda de qualidade procura sinais de baseline
      Então nenhuma entrada de "ignoreErrors" tem a chave "count"
      E a seção "includes" do phpstan.neon não cita arquivo com "baseline" no nome
      E não existe arquivo "phpstan-baseline.*" na raiz do projeto

    Esquema do Cenário: [CT-05] toda exceção de ignoreErrors tem escopo de path e mensagem específica
      Dado a entrada de "ignoreErrors" cuja mensagem cita "<assunto>"
      Quando o guarda de qualidade confere o escopo e a mensagem dela
      Então a entrada declara a chave "message" (regex) e "path" ou "paths" não vazio
      E a entrada não declara "identifier", "identifiers", "rawMessage" nem "messages"
      E nenhum dos paths é igual a uma raiz analisada (app, bootstrap/app.php, config, database, routes)
      E a mensagem não casa "Cannot call method getLoginUrl() on Filament\Panel|null."
      E a mensagem não casa "Parameter #1 $url of class Illuminate\Http\RedirectResponse constructor expects string, string|null given."

      Exemplos:
        | assunto                   | # origem                      |
        | simpleLightbox            | pré-existente                 |
        | WidgetDinamico            | pré-existente                 |
        | customMyProfilePage       | pré-existente                 |

    Cenário: [CT-06] o inventário de exceções volta às 3 anteriores e cada uma registra a tentativa
      Dado a configuração efetiva lida por "phpstan dump-parameters --json"
      E o texto do phpstan.neon
      Quando o guarda de qualidade conta as exceções
      Então "ignoreErrors" tem exatamente 3 entradas
      E as 3 entradas têm "message" e "path(s)" idênticos aos da main
      E nenhuma entrada cita "ExigirEmailVerificado" na mensagem nem no path
      E o bloco de comentário de cada uma das 3 contém o registro "Tentado" seguido de ao menos uma alternativa descartada
      E "reportUnmatchedIgnoredErrors" é true
      E nenhuma entrada de "stubFiles" fica fora de "vendor/" (o kit não tem stub próprio)
      E o phpstan.neon não declara "services" nem "conditionalTags", e todo "includes" aponta para "vendor/"

    Cenário: [CT-07] o escopo analisado não encolheu para esconder erro
      Dado a configuração efetiva lida por "phpstan dump-parameters --json"
      Quando o guarda de qualidade lê "paths" e "excludePaths"
      Então "paths" é exatamente app, bootstrap/app.php, config, database e routes
      E "excludePaths" é exatamente os três globs de migration de vendor (health, breezy_sessions, pulse)
      E nenhum dos 14 arquivos tocados por esta entrega está em "excludePaths"

    @premissa
    Cenário: [CT-08] nenhum @phpstan-ignore novo nasce, e os pré-existentes ficam congelados
      Dado o código de app, config, database, routes e bootstrap/app.php
      Quando o guarda de qualidade conta as ocorrências de "@phpstan-ignore"
      Então as ocorrências são exatamente 2 em "app/Filament/Admin/Resources/Roles/RoleResource.php" e 1 em "app/Models/User.php"
      E nenhum dos 14 arquivos tocados por esta entrega contém "@phpstan-ignore", "@phpstan-assert" nem chamada a "assert("
```

- Premissa **P-04** (CT-08): Assumido: os três ignores anteriores a esta entrega ficam, congelados por inventário. Se negado (o mantenedor quer zero): a primeira asserção do CT-08 inverte para "0 ocorrências". **Invariante das duas leituras**: a contagem nunca cresce, e nenhum arquivo tocado aqui ganha ignore.
- CT-04…CT-08 ficam em `tests/Kit/QualidadeDeCodigoTest.php`. A contagem do CT-08 roda sobre o texto cru: o ignore **é** comentário, então aqui não se filtra comentário (`.ai/rules/testes.md`: o filtro vale para asserção de ausência de *comando citado*, que não é o caso).
- `@var` inline e cast para calar tipo não viram CT, porque não há predicado textual sem falso positivo. Ficam como lacuna declarada, roteada ao `fw-revisor-diff` (eixo "cala o analisador?").

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | `phpstan analyse --generate-baseline` e `includes: - phpstan-baseline.neon` com os 48 erros | CT-04 (`count`, include), CT-06 (inventário ≠ 4) |
| M6 | exceção sem `path` (global) para a mensagem de nulidade do `getLoginUrl()` | CT-05 |
| M7 | exceção com `message: '#.*#'` e escopo de arquivo (o caso histórico era o `ExigirEmailVerificado`) | CT-05 (a amostra casa) e, se for a do `ExigirEmailVerificado`, CT-06 (Adendo 1) |
| M8 | `paths: [app]` numa exceção nova (escopo = raiz) | CT-05 |
| M9 | arquivo com erro movido para `excludePaths`, ou `database` tirado de `paths` | CT-07 |
| M10 | `/** @phpstan-ignore-next-line */` num dos 14 arquivos | CT-08 |
| M10a (revisão adversarial) | exceção pré-existente ganha paths entre os 14 arquivos, ou a regex fica mais larga sem casar as amostras | CT-06 (igualdade com a `main`) |
| M10b (revisão adversarial) | stub próprio declara `getCurrentPanel(): Panel` / `getLoginUrl(): string` e cala os nulos | CT-06 (`stubFiles` só de vendor) |
| M10c (adversarial, rodada 2) | exceção por `identifier: argument.type` (ou `rawMessage`/`messages`) com escopo de arquivo: cala a classe inteira de erro sem regex para as amostras casarem | CT-05 (chaves proibidas) |
| M10d (adversarial, rodada 2) | extensão de tipo própria (`services` com `phpstan.broker.dynamicMethodReturnTypeExtension`) devolvendo `Panel` não nulo | CT-06 (sem `services`/`conditionalTags`, includes só de vendor) |
| M10f (Adendo 1, RQ-09) | a exceção do `ExigirEmailVerificado` volta ao `phpstan.neon` em vez da guarda de invariante no código | CT-06 (contagem 4 ≠ 3, e cita `ExigirEmailVerificado`) |
| M10g (Adendo 1, RQ-09) | a exceção sai do neon e a guarda de invariante no middleware muda o comportamento: o barramento, a resposta 403 JSON ou a trilha no canal de autenticação | regressão `tests/Kit/VerificacaoDeEmailTest.php` (todos os casos), que precisa seguir verde |
| M10e (adversarial, rodada 2) | nulo "tratado" com `assert($x !== null)` ou `@phpstan-assert`: o PHPStan estreita, e a produção (`zend.assertions=-1`) segue com o nulo | CT-08 |

- O "sem stub próprio" do CT-06 segue o 00: o stub do `EnsureEmailIsVerified` **reprovou**. Pelo **RQ-09 (Adendo 1)**, a anotação errada do vendor passa a ser contornada por **guarda de invariante no código do kit**, e não por exceção no neon. O inventário de `ignoreErrors` volta às 3 anteriores.
- **Regressão do RQ-09**: o middleware `ExigirEmailVerificado` continua passando em `tests/Kit/VerificacaoDeEmailTest.php`, sem reescrever, com as mesmas asserções de barramento, 403 JSON, trilha e escopo do painel.

---

## Regra R3: fora de painel, o hub falha fechado

> `RQ-06` · perfil **mínimo** · técnica: **EP** (os 3 hubs como partições; mesmo trait, três consumidores)

```gherkin
  Regra: sem painel corrente, o hub não inventa cartões. Ele falha com exceção clara, e cartão de outro painel nunca aparece

    @premissa
    Esquema do Cenário: [CT-09] o hub sem painel corrente lança exceção de invariante e não lista cartões de outro painel
      Dado nenhum painel corrente no Filament
      E o painel padrão "app" com ao menos um recurso navegável
      Quando o hub "<hub>" monta os seus cartões
      Então é lançada uma exceção que não é Error nem TypeError
      E a mensagem da exceção diz que o hub exige um painel corrente
      E nenhuma URL de cartão do painel "app" é devolvida

      Exemplos:
        | hub                                            | # painel de origem |
        | App\Filament\Admin\Pages\HubDeAdministracao    | admin              |
        | App\Filament\App\Pages\HubDoNegocio            | app                |
        | App\Filament\Infra\Pages\HubDeInfraestrutura   | infra              |
```

- Premissa **P-01**: Assumido (falha fechado): exceção de invariante (`LogicException` ou subclasse), com mensagem que nomeia a falta de painel corrente. Se negado (lista vazia de cartões): as duas primeiras linhas do `Então` invertem para "devolve lista vazia, sem exceção". **Invariante das duas leituras**: nenhum cartão de outro painel, em especial o do painel padrão, e nunca um `Error` "on null".
- Arnês: `Filament::setCurrentPanel(null)` e a leitura dos cartões pela superfície pública da página. Se o único acesso for o método estático protegido do trait, usar reflexão, que é aceitável em teste de invariante. Arquivo: `tests/Kit/HubDeCardsTest.php`.
- **Regressão dentro de painel (não reescrever)**: `tests/Kit/HubDeCardsTest.php` ("aponta o cartão para o destino e o coloca no grupo dele", "abre o hub do painel para quem tem o papel dele", "descreve cada destino do hub de infraestrutura com a frase dele"), `tests/Tenancy/HubDoNegocioTest.php`, `tests/Kit/PermissoesDeTelasTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | `getCurrentPanel()?->getNavigationGroups() ?? []`: lista vazia silenciosa | CT-09 (espera exceção). Se P-01 for negado, este vira o comportamento certo e M11 sai da tabela |
| M12 | troca para `getCurrentOrDefaultPanel()`: o hub do `/admin` mostra cartões do `/app` | CT-09 (invariante) |
| M13 | nulo não tratado: `Error: Call to a member function … on null` | CT-09 (tipo da exceção) |

## Regra R3b: nulo impossível pelo desenho não vira valor inventado

> `RQ-06` · perfil **mínimo** · técnica: revisão estática. Sem cenário executável.

Os três painéis do kit chamam `->login()` (`AdminPanelProvider.php:70`, `AppPanelProvider.php:78`, `InfraPanelProvider.php:91`). Por isso `Filament::getPanel('app')->getLoginUrl()` e `getUrl()`, usados em `RegistroPorConvite`, `PrimeiroAcessoSocial`, `DefinirSenhaPorEmail` e `TelaLogin`, **nunca** são null na configuração do kit. Pelo mesmo critério entra o `ImportadorDoKit`: o nulo é do ciclo de vida do `Importer` do Filament (`$record` antes de `resolveRecord()`), não de dado do CSV (rejeição do M-I, rodada 2). O `getUrl()` só é null com tenant por domínio (`HasRoutes.php:178-179`), que o kit não liga.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | `?? ''` ou `(string)` em `getLoginUrl()`/`getUrl()`: redirect vazio se o nulo um dia acontecer | ⚠️ **lacuna declarada**: na configuração do kit o mutante e o código correto dão o mesmo observável. Tentado: remover `->login()` do `/app` no teste, mas o provider roda antes do teste e reconfigurar muda a feature testada. Roteado ao `fw-revisor-diff` (grep `?? ''`, `?? url('/')`, `(string) ` nos 14 arquivos) |
| M15 | `?? url('/')` novo em arquivo tocado: valor inventado, fora do painel | ⚠️ **lacuna declarada**: mesmo motivo de M14 e mesmo roteamento |
| M16 | `?? url('/')` já existente em `LoginSocialController::urlDoPainel` (vale só com tenant por domínio) mantido como está | ⚠️ **lacuna declarada**: é anterior a esta entrega e fica fora do alcance do kit. É candidata a pergunta P-06 |

**Regressão (não reescrever)**: `tests/Kit/ConviteTest.php`, `tests/Kit/DestinoAposCadastroTest.php`, `tests/Kit/RegistroAbertoTest.php`, `tests/Tenancy/ConviteTenancyTest.php`, `tests/Kit/DefinirSenhaPorEmailTest.php` ("envia o link de redefinicao…", "nao envia nada sem sessao"), `tests/Kit/SituacaoDaContaTest.php` (CT-04, CT-07), `tests/Kit/LoginUnificadoTest.php`, `tests/Kit/VinculoDeProvedorSocialTest.php` (e-mail de `PrimeiroAcessoSocial`).

---

## Regra R4: painel sem login dá ao visitante da tela de bloqueio um destino dentro do painel

> `RQ-06` · perfil **mínimo** · técnica: **EP** (painel com login × painel sem login)

```gherkin
  Regra: o visitante que abre a tela de bloqueio sai para um destino alcançável do mesmo painel, nunca para uma URL vazia ou de fora

    @premissa
    Esquema do Cenário: [CT-10] o visitante da tela de bloqueio é levado ao login do painel, ou à raiz dele quando o painel não tem login
      Dado o painel corrente "<painel>" <login>
      E a sessão marcada como travada
      E nenhum usuário autenticado
      Quando o visitante abre a tela de bloqueio
      Então a resposta é um redirecionamento para "<destino>"
      E o destino não é vazio nem a própria tela de bloqueio

      Exemplos:
        | painel     | login                          | destino              | # partição          |
        | admin      | com login                      | /admin/login         | fato: tem login     |
        | financeiro | sem login (painelRegistradoEmTeste) | /financeiro     | premissa P-02       |
```

- Premissa **P-02**: Assumido (falha fechado, com destino dentro do painel): a raiz do painel (`url($painel->getPath())`), onde o middleware de autenticação do próprio painel decide. Se negado (exceção clara): a linha `financeiro` inverte para "lança exceção de invariante que nomeia o painel sem login". **Invariante das duas leituras**: nunca `TypeError`, nunca redirect para `''`, para `/` ou para a própria tela de bloqueio (laço).
- Arnês: `Livewire::test(TelaBloqueio::class)` com o painel corrente. Se o redirect lançado no `mount()` não atravessar o harness, capturar `HttpResponseException` e afirmar `getResponse()->getTargetUrl()`. Arquivo: `tests/Kit/BloqueioDeSessaoTest.php`.
- A linha `admin` **fortalece** o caso existente "manda visitante não autenticado para o login", que hoje só afirma `assertRedirect()` sem destino. Aquele caso continua como regressão, junto de "redireciona em vez de estourar quando a tela é aberta sem a sessão travada".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | `getLoginUrl() ?? ''`: redirect para a própria URL, em laço | CT-10 (linha financeiro) |
| M18 | `getLoginUrl() ?? url('/')`: valor inventado, fora do painel | CT-10 (linha financeiro, destino exato) |
| M19 | nulo não tratado: `TypeError` no parâmetro `string` do redirect | CT-10 (linha financeiro) |
| M20 | "simplificar" e mandar todo visitante para a raiz do painel | CT-10 (linha admin) |

---

## Regra R5: painel sem login na volta do provedor social dá um destino de login alcançável

> `RQ-06` · perfil **mínimo** · técnica: **EP** (painel da ida com login × sem login)

```gherkin
  Regra: a recusa na volta do provedor leva a um login que existe, mesmo quando o painel de origem não tem login

    @premissa
    Esquema do Cenário: [CT-11] o retorno recusado do Google leva ao login do painel de origem, ou ao login do painel padrão quando a origem não tem login
      Dado o login pelo Google ligado para todos os painéis
      E o painel "financeiro" registrado sem login
      E a ida ao provedor partiu com "?painel=<painel>"
      Quando o retorno chega com um state que não é o da sessão
      Então a resposta é um redirecionamento cujo Location é exatamente url("<destino>")
      E ninguém fica autenticado
      E a contagem de usuários continua a mesma de antes do retorno

      Exemplos:
        | painel     | destino       | # partição      |
        | admin      | /admin/login  | fato: tem login |
        | financeiro | /app/login    | premissa P-03   |
```

- Premissa **P-03**: Assumido (falha fechado, coerente com "ignora painel inexistente na query em vez de estourar"): painel sem login é tratado como painel sem porta, e o destino cai no login do painel padrão. Se negado (raiz do painel de origem): a linha `financeiro` inverte para `/financeiro`. **Invariante das duas leituras**: nunca 500, nunca `Location` vazio, nunca sessão aberta, nenhuma conta criada.
- Um usuário prévio (`usuario('ja.tem@example.com')`) está no `Dado`. Sem ele, a asserção "contagem igual" seria vácuo. Arquivo: `tests/Kit/LoginSocialPorPainelTest.php`.
- **Regressão (não reescrever)**: `tests/Kit/LoginSocialGoogleTest.php` ("não autentica quando o retorno do Google chega sem o state da sessão", "manda quem já tinha conta para o painel, não para o perfil"), `tests/Kit/LoginSocialPorPainelTest.php` ("ignora painel inexistente na query em vez de estourar", "carrega o painel na sessão para a volta do provedor"), `tests/Tenancy/LoginSocialGoogleTenancyTest.php`, `tests/Kit/CadastroSocialPorConviteTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M21 | nulo não tratado: `TypeError` no retorno `string`, 500 no callback OAuth | CT-11 (linha financeiro) |
| M22 | `?? ''`: redirect vazio | CT-11 (linha financeiro, destino exato) |
| M23 | cair sempre no login do painel padrão, perdendo o painel de origem | CT-11 (linha admin) |

---

## Regra R6a: no model, convite sem papel falha com exceção de invariante e não atribui papel inventado

> `RQ-06` (*"onde o nulo é impossível pelo desenho … exceção de invariante, nunca valor inventado"*) · perfil **mínimo**, com Impacto 3 · técnica: **EP + rastreio de efeito** (dois verbos irmãos, porque verbo irmão não herda evidência)
>
> **R6 foi dividida na rodada 2 da revisão adversarial**, que não convergiu. R6a é o invariante do model. R6b é a sobrevivência de **cada ponto de entrada** do aceite.

```gherkin
  Regra: o papel do convite não some pelo caminho normal, e se sumir o aceite pelo model falha sem criar conta, sem mexer em papéis e sem consumir o convite

    Cenário: [CT-12] excluir um papel que tem convite pendente é recusado pelo banco
      Dado um convite pendente para "carla@example.com" com o papel "admin"
      Quando o administrador exclui o papel "admin"
      Então a exclusão falha com violação de chave estrangeira
      E o papel "admin" continua existindo
      E o convite continua apontando para o papel "admin"

    Esquema do Cenário: [CT-13] aceitar convite cujo papel não existe mais lança exceção de invariante sem efeito colateral
      Dado um convite pendente para "<email>" cujo papel foi apagado com as chaves estrangeiras desligadas
      E o usuário "bruno@example.com" existente com o papel "infra"
      Quando o convidado aceita pelo verbo "<verbo>"
      Então é lançada uma exceção que não é Error nem TypeError, e a mensagem cita o papel ausente do convite
      E o total de usuários é o mesmo de antes do aceite
      E "bruno@example.com" tem exatamente o papel "infra"
      E o convite continua pendente
      E nenhuma notificação de aceite foi enviada

      Exemplos:
        | verbo                                  | email               | # partição             |
        | aceitar (conta nova, com nome e senha) | carla@example.com   | fluxo de cadastro      |
        | aceitarComoUsuarioExistente (bruno)    | bruno@example.com   | fluxo de oferta        |
```

- CT-12 prova a afirmação "impossível pelo desenho" em que o 00 se apoia. Ela dispensa um controle, então o cenário é escrito como se ela fosse falsa (seção *Afirmação negativa é hipótese…* da skill). A FK vem ligada no SQLite de teste (`config/database.php:40`).
- CT-13, arnês: `Schema::disableForeignKeyConstraints()`, `Role::whereKey(...)->delete()`, `Schema::enableForeignKeyConstraints()` e `$convite->refresh()`. O `Dado` põe `bruno` (com papel) no mundo para que "papéis inalterados" e "total de usuários igual" discriminem. "Nenhuma notificação" usa `Notification::fake()`, e o destinatário (o convidante do `ofertaPara`) existe. Arquivo: `tests/Kit/ConviteTest.php`.
- Premissa **P-05** (só forma): o tipo exato e o texto da exceção não são fixados. O `Então` afirma "não é `Error`/`TypeError`" e "cita o papel ausente".
- **Regressão (não reescrever)**: `tests/Kit/ConviteTest.php` ("aceita o convite e cria o usuario com o papel", "recusa reuso do convite…"), `tests/Kit/ConviteUsuarioExistenteTest.php`, `tests/Tenancy/ConviteUsuarioExistenteTest.php`, `tests/Tenancy/ConviteTenancyTest.php`. Envio em lote (válidos e tortos): `tests/Kit/ConviteEmMassaTest.php` ("envia os validos mesmo com um endereco torto no meio", "separa e normaliza os enderecos do texto"), `tests/Kit/ConviteEmMassaDtoTest.php`, `tests/Tenancy/ConviteEmMassaTenancyTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | `$this->papel?->…` / `if ($this->papel)`: pula o `assignRole` em silêncio, a conta nasce sem papel e o convite é consumido | CT-13 (exceção, total de usuários, convite pendente) |
| M25 | papel ausente cai no padrão `panel_user`: valor inventado | CT-13 (exceção; na linha bruno, papéis = só `infra`) |
| M26 | exceção lançada depois de criar a conta, fora da transação | CT-13 (linha aceitar: total de usuários) |
| M27 | convite marcado como aceito antes da exceção | CT-13 (convite pendente) |
| M28 | a guarda só no `aceitar` e esquecida no `aceitarComoUsuarioExistente` | CT-13 (linha bruno) |
| M29 | FK trocada para `cascadeOnDelete`/`nullOnDelete` | CT-12 |
| M29a (revisão adversarial) | nulo "tratado" com `assert($papel !== null)`: com `zend.assertions=-1` em produção vira `Error` "on null" | CT-13 (nos testes, `assert` lança `AssertionError`, que **é** `Error`); também CT-08 (M10e) |

- CT-12 afirma a FK como **prova do desenho**, não como UX. Excluir pela tela do `RoleResource` um papel com convite pendente vira `QueryException`. Esse comportamento é anterior a esta entrega e está registrado como pergunta P-07.

---

## Regra R6b: todo ponto de entrada do aceite sobrevive a um convite sem papel, sem efeito colateral

> `RQ-06` (*"não pode virar 500 opaco"*, e o nulo impossível pelo desenho "diz isso com exceção de invariante") · perfil **mínimo**, com Impacto 3 · técnica: **EP por ponto de entrada** (cadastro por convite, oferta ao usuário existente, cadastro social por convite, modo tenancy) + **rastreio de efeito**
>
> **Oráculo comum de R6b.** Toda linha afirma todos estes itens, porque todo ponto de entrada dispara estes efeitos no caminho feliz:
> (a) nenhuma falha é `Error`, `TypeError` ou `AssertionError`; (b) nenhuma conta nasce; (c) "bruno@example.com" mantém exatamente o papel "infra"; (d) o convite continua pendente;
> (e) nenhuma notificação é enviada; (f) quem não estava autenticado continua sem sessão, e quem estava continua autenticado como ele mesmo;
> (g) a resposta **não** é redirect de sucesso para o painel (`/app` ou `/app/...`).
>
> **Destinatários no mundo**: `bruno` com papel (c), o convidante do `ofertaPara` para a notificação (e), e um usuário prévio para a contagem (b). Sem eles, as asserções de ausência seriam vácuo.

```gherkin
  Regra: cadastro por convite, oferta ao usuário existente, cadastro social por convite e aceite com tenancy falham fechado com papel ausente, sem conta, sem papel, sem sessão e sem consumir o convite

    Esquema do Cenário: [CT-18] a tela de cadastro por convite cujo papel não existe mais falha fechado, no momento em que falhar primeiro
      Dado um convite pendente para "carla@example.com" cujo papel foi apagado com as chaves estrangeiras desligadas
      E o usuário "bruno@example.com" existente com o papel "infra"
      E nenhum usuário autenticado
      Quando a convidada <momento>
      Então valem os itens (a) a (g) do oráculo comum de R6b

      Exemplos:
        | momento                                                   | # partição               |
        | abre a tela de cadastro com o token do convite (mount)    | montagem                 |
        | envia nome e senha na tela já montada                     | envio                    |

    Cenário: [CT-19] aceitar pela tela de convites recebidos uma oferta cujo papel não existe mais falha fechado
      Dado uma oferta pendente para "bruno@example.com" cujo papel foi apagado com as chaves estrangeiras desligadas
      E "bruno@example.com" autenticado, com o papel "infra"
      Quando bruno aciona "aceitar" na tela de convites recebidos
      Então valem os itens (a) a (g) do oráculo comum de R6b
      E a oferta continua listada como pendente para bruno

    Cenário: [CT-20] o retorno do Google com convite cujo papel não existe mais falha fechado
      Dado o login pelo Google ligado e um convite pendente para "carla@example.com" cujo papel foi apagado com as chaves estrangeiras desligadas
      E a ida ao provedor partiu da tela de cadastro com o token desse convite
      E o Google devolve "carla@example.com" com o state da sessão
      Quando o retorno do provedor chega
      Então valem os itens (a) a (g) do oráculo comum de R6b

    Esquema do Cenário: [CT-21] com tenancy, aceitar convite cujo papel não existe mais falha fechado nos dois verbos
      Dado a organização "acme" e um convite dela pendente para "<email>" cujo papel foi apagado com as chaves estrangeiras desligadas
      E "bruno@example.com" membro da "acme" com o papel "infra"
      Quando o convidado aceita pelo verbo "<verbo>"
      Então valem os itens (a) a (e) do oráculo comum de R6b
      E bruno não ganha vínculo novo nem papel novo em nenhum contexto de organização, inclusive o global

      Exemplos:
        | verbo                                  | email               | # partição        |
        | aceitar (conta nova, com nome e senha) | carla@example.com   | fluxo de cadastro |
        | aceitarComoUsuarioExistente (bruno)    | bruno@example.com   | fluxo de oferta   |
```

- **CT-18, dois momentos.** Se o `mount` já falhar fechado, a linha "envio" é inalcançável. Nesse caso o executor a registra como **fundida na linha "montagem"**, e o cenário afirma o oráculo sobre o `mount`. O `Então` não fixa **qual** saída o usuário vê (P-05), só o que vale nas duas leituras. Livewire, `RegistroPorConvite`, em `tests/Kit/ConviteTest.php`.
- **CT-19**: Livewire, `App\Filament\App\Pages\ConvitesRecebidos`, ação de tabela `aceitar` via `callAction(TestAction::make('aceitar')->table($convite))`. O item (g) quer dizer que a ação não redireciona para o painel da organização nem notifica sucesso. Kit: `tests/Kit/ConviteUsuarioExistenteTest.php`. Tenancy: coberto pelo CT-21.
- **CT-20**: arnês de `tests/Kit/CadastroSocialPorConviteTest.php` (`conviteSocialPara()`, `usuarioSocialFalso()`, `ligarProvedor()`), no mesmo arquivo.
- **CT-21**: suíte **Tenancy** (`tests/Tenancy/ConviteTenancyTest.php` para `aceitar`, `tests/Tenancy/ConviteUsuarioExistenteTest.php` para a oferta), com `noPainelDa($acme)`. Vínculo e papel são conferidos pelo pivot com `team_id` (`pivotDePapeis()`), inclusive o contexto global `Tenant::CONTEXTO_GLOBAL`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M29b (revisão adversarial) | a tela lê o nome do papel do convite antes de chamar o aceite (o `mount`/cabeçalho estoura com `Error`) e o CT-13 no model passa | CT-18 (linha montagem) |
| M29c (adversarial, rodada 2) | a tela captura a exceção do aceite, notifica sucesso e redireciona para o painel com nada feito, ou já autenticou a convidada antes do aceite | CT-18 (itens f, g) |
| M29d (adversarial, rodada 2) | a ação `aceitar` de convites recebidos engole a exceção e marca a oferta como aceita ou tira da lista | CT-19 (item d, oferta listada) |
| M29e (adversarial, rodada 2) | o cadastro social cria a conta e abre a sessão **antes** de chamar o aceite: a conta fica sem papel e logada | CT-20 (itens b, f) |
| M29f (adversarial, rodada 2) | no ramo com tenancy, o papel ausente cai no contexto global ou num papel padrão da organização | CT-21 (bruno sem papel novo em nenhum contexto) |
| M29g (adversarial, rodada 2) | a guarda existe no Kit e falta no ramo tenancy do `aceitarComoUsuarioExistente` (vínculo à organização gravado antes do papel) | CT-21 (linha bruno) |

---

## Regra R7: sem usuário, o widget do assistente renderiza vazio e recusa toda ação

> `RQ-06` · perfil **mínimo** · técnica: **EP por persona** (visitante × dona) + **caracterização** (o 403 é o comportamento da `main`)
>
> O widget está no `BODY_END` do `/app`, então **também na tela de login**. O visitante é o caminho real, não um caso de laboratório.

```gherkin
  Regra: o widget do assistente só mostra e só age sobre conversas do usuário autenticado. Sem usuário, renderiza vazio e recusa as ações

    Cenário: [CT-14] o visitante abre a tela de login do painel de negócio sem erro e sem conversa de ninguém
      Dado a conversa "Plano de férias" pertencente à Ana
      E nenhum usuário autenticado
      Quando o visitante abre "/app/login"
      Então a resposta é 200
      E o corpo da página contém o componente "assistente-chat-widget" montado
      E o corpo da página não contém "Plano de férias"

    Esquema do Cenário: [CT-15] o widget lista conversas só para a dona delas
      Dado a conversa "Plano de férias" pertencente à Ana
      E a conversa "Orçamento do Bruno" pertencente ao Bruno
      E a conversa "Rascunho órfão" sem participante (participant_type e participant_id nulos)
      E a persona "<persona>"
      Quando o widget do assistente é renderizado
      Então o widget <resultado> "Plano de férias"
      E o widget não mostra "Orçamento do Bruno" nem "Rascunho órfão"

      Exemplos:
        | persona   | resultado     | # partição                          |
        | visitante | não mostra    | sem usuário                         |
        | ana       | mostra        | controle positivo: o oráculo vive   |

    Esquema do Cenário: [CT-16] o visitante que chama uma ação do widget recebe 403 e nada muda
      Dado a conversa "Plano de férias" pertencente à Ana
      E nenhum usuário autenticado
      Quando o visitante chama "<acao>" no widget do assistente
      Então a resposta é 403
      E a conversa da Ana continua com o título "Plano de férias"
      E o total de conversas e de mensagens do assistente é o mesmo de antes

      Exemplos:
        | acao                                          | # superfície $wire.                    |
        | enviar (com mensagem "oi")                    | sem id                                 |
        | renomearConversa (id da Ana, "Invadido")      | id de terceiro: 403, não 404           |
        | retomarConversa (id da Ana)                   | id de terceiro: 403, não 404           |
        | novaConversa                                  | sem id                                 |
        | renomearConversa (id do "Rascunho órfão", "Invadido") | dono nulo: nullsafe viraria whereNull e abriria |
        | responder (depois de definir "mensagemPendente" = "oi" por set) | a ação cuja autorização mais mudou (RQ-10, Adendo 1) |

    Esquema do Cenário: [CT-22] um usuário autenticado que não é dono recebe 404 na conversa alheia e nada muda
      Dado a conversa "Plano de férias" pertencente à Ana
      E "bruno@example.com" autenticado, sem nenhuma conversa própria
      Quando bruno chama "<acao>" no widget do assistente com o id da conversa da Ana
      Então a resposta é 404
      E a conversa da Ana continua com o título "Plano de férias"
      E o "conversaId" do widget continua nulo

      Exemplos:
        | acao                            | # superfície $wire. |
        | renomearConversa ("Invadido")   | escrita             |
        | retomarConversa                 | leitura             |
```

- Na última linha do CT-16, o `Então` sobre título vale para o "Rascunho órfão", que continua com o título original. As colunas de participante de `agent_conversations` são **nullable** (migration `2026_08_12_165028_create_agent_conversations_table.php`), então o dono nulo é alcançável.

- Arquivo novo: `tests/Kit/AssistenteChatWidgetTest.php`. **Não existe hoje nenhum teste do componente**: o `grep` por `AssistenteChatWidget`/`assistente-chat` em `tests/` só acha um comentário em `tests/Browser/BoasVindasTest.php`.
- CT-14 é por HTTP porque a entrada real é o render hook. CT-15 e CT-16 usam `Livewire::test(AssistenteChatWidget::class)`.
- **`responder` entrou no CT-16 pelo RQ-10 (Adendo 1)**, revertendo o corte anterior. O visitante define `mensagemPendente` por `set` e chama `responder`. Espera 403, e o total de conversas e de mensagens não muda. Arnês: `Assistente::fake([...])`, como em `tests/Kit/GuardrailsDtoTest.php`. Sem o fake, um guarda ausente tentaria falar com o provedor em vez de gravar a conversa que o cenário conta.
- **CT-22 é caracterização**: o 404 para o autenticado que não é dono é o comportamento da `main`. Medir na `main` antes do diff. Com o CT-16 ele fecha o par: visitante → 403, autenticado alheio → 404. Um tratamento de nulo que unifique os dois, ou que abra a conversa, muda um dos lados.
- O "403, não 404" das linhas com id é **discriminante**. Um tratamento de nulo por nullsafe na conferência de posse (`$user?->getMorphClass()`) vira `whereNull` e responde 404 ao visitante, e um cenário que só aceitasse "recusado" não o separaria.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | render sem usuário estoura em `auth()->user()->…` (500 na tela de login do `/app`) | CT-14, CT-15 (visitante) |
| M31 | nullsafe na conferência de posse: visitante com id de terceiro recebe 404 | CT-16 (linhas renomear e retomar) |
| M32 | o guarda de autenticação some de uma ação ao tratar o nulo, e `renomearConversa` renomeia | CT-16 (título inalterado) |
| M32a (Adendo 1, RQ-10) | `responder()` lê o usuário com nullsafe e segue sem conferir autenticação: o visitante com `mensagemPendente` forjada dispara o agente e grava conversa e mensagens | CT-16 (linha responder: 403, contagens iguais) |
| M33 | histórico sem filtro de participante quando não há usuário | CT-15 (visitante não vê). O CT-14 **não** o mata: ele só prova que a página renderiza com o widget, e a lista do visitante é afirmada no componente |
| M33a (revisão adversarial) | o widget some do render hook e o CT-14 passa por ausência | CT-14 (componente montado) |
| M33b (revisão adversarial) | nullsafe na posse ou no histórico vira `whereNull` e abre a conversa sem dono ao visitante | CT-15 ("Rascunho órfão"), CT-16 (última linha) |
| M31a (adversarial, rodada 2) | a conferência de posse, reescrita para tratar o nulo, passa a comparar só `participant_type` (ou só `auth()->check()`): o autenticado renomeia ou retoma conversa alheia | CT-22 |
| M31b (adversarial, rodada 2) | o tratamento de nulo unifica a recusa em 403 para todos, e o 404 deixa de esconder a existência da conversa alheia | CT-22 (404 exato) |
| M33c (adversarial, rodada 2) | o histórico filtra só por tipo de participante, e a Ana vê a conversa do Bruno | CT-15 (linha ana, "Orçamento do Bruno") |

---

## Regra R8: a fusão de duplicatas da migration do onboarding continua igual à da `main`

> `RQ-06` · perfil **padrão** · técnica: **caracterização**. O `Então` é o comportamento da `main`, medido rodando este CT contra a `main` **antes** do diff. Se ele ficar vermelho na `main`, o errado é o CT.

```gherkin
  Regra: tratar nulidade na migration não muda o resultado da fusão de progresso duplicado

    Esquema do Cenário: [CT-17] as linhas de progresso duplicadas viram uma, com o que a main produzir, e a linha única não muda
      Dado a migration revertida, com as colunas de escopo de "<tabela>" aceitando nulo
      E as linhas duplicadas do mesmo dono e sujeito, em ordem de id: <duplicatas>
      E uma linha de controle de outro sujeito, sem duplicata, com escopo nulo
      Quando a migration roda de novo
      Então resta uma linha para o par, com o id da mais antiga e <esperado>
      E as colunas de escopo dessa linha e da de controle valem "" (string vazia)
      E a linha de controle mantém os seus timestamps e o seu meta

      Exemplos:
        | # | tabela        | duplicatas                                                                                                                                  | esperado                                                                           | # partição                        |
        | 1 | flow_progress | [escopo nulo, started 08-01 10:00, completed nulo, meta {"a":1}], [escopo nulo, started 08-02 09:00, completed 08-03 08:00, meta {"b":2}]   | started 08-02 09:00, completed 08-03 08:00, meta {"a":1,"b":2}                     | nulo × nulo                       |
        | 2 | step_progress | [escopo nulo, seen 08-01 10:00, completed nulo, meta {"a":1}], [escopo nulo, seen 08-02 09:00, completed 08-03 08:00, meta {"b":2}]         | seen 08-02 09:00, completed 08-03 08:00, meta {"a":1,"b":2}                        | nulo × nulo, outra tabela         |
        | 3 | flow_progress | [escopo nulo, started 08-01 10:00, completed nulo, meta {"a":1}], [escopo "", started 08-02 09:00, completed 08-03 08:00, meta {"b":2}]     | started 08-02 09:00, completed 08-03 08:00, meta {"a":1,"b":2}                     | nulo × vazio: a colisão real      |
        | 4 | flow_progress | [escopo nulo, started 08-05 10:00, completed 08-06 08:00, meta {"a":1}], [escopo nulo, started 08-02 09:00, completed nulo, meta {"b":2}]   | started 08-05 10:00, completed 08-06 08:00, meta {"a":1,"b":2}                     | máximo na linha ANTIGA            |
        | 5 | flow_progress | [escopo nulo, started 08-01 10:00, completed nulo, meta {"a":1}], [escopo nulo, started 08-02 09:00, completed nulo, meta {"a":2}]          | started 08-02 09:00, completed nulo, meta {"a":2}                                  | chave de meta em colisão          |
        | 6 | flow_progress | [escopo "", started 08-01 10:00, completed nulo, meta nulo], [escopo nulo, started 08-03 10:00, completed nulo, meta nulo], [escopo nulo, started 08-02 10:00, completed 08-04 08:00, meta nulo] | started 08-03 10:00, completed 08-04 08:00, meta nulo, e nenhuma outra linha do par | trio, a mais antiga com escopo "" |
```

- **Caracterização: medir na `main` primeiro.** Antes do diff, o executor roda o CT-17 contra a `main` (`git stash` ou `git worktree add ../main main`). Toda célula `esperado` que divergir da `main` está errada **no CT**, e é corrigida para o valor medido antes de rodar na branch. As células mais expostas são a colisão de meta da linha 5 (`{"a":2}` supõe que a mais nova vence) e o meta nulo da linha 6. Registrar no `03` o que foi medido.
- Os valores são **discriminantes**:
  - Linha 1: a mais antiga tem o `started` menor e o `completed` nulo. "Manter os valores da mais antiga" e "o máximo sem descartar nulo" dão resultados diferentes.
  - Linha 4: o máximo está na mais **antiga**. "Ficar com os valores da mais nova" e "último não nulo" erram.
  - Linha 5: a colisão de chave separa "união" de "o primeiro vence".
  - Linha 6: o trio separa "funde o grupo inteiro" de "funde só um par", e o escopo `""` na mais antiga separa agrupar com `coalesce` de agrupar pelo valor cru.
- Arnês: `(require $migration)->down()`, `insert` direto nas tabelas e `->up()`. O nome real da tabela sai da mesma config que a migration lê. Arquivo novo: `tests/Kit/MigracaoDoEscopoDoOnboardingTest.php`. Não há teste existente: o `grep` por `flow_progress|step_progress|scope_type` em `tests/` volta vazio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M34 | ao tipar a linha do `DB::table()->get()`, o `max` passa a ler só a primeira linha, ou o `filter()` de nulos é trocado por `?? ''`, e o timestamp mais avançado se perde | CT-17 (valores de `col_a`/`col_b`) |
| M35 | `json_decode` com nulo tratado como `[]` na linha sem meta, e a união perde a chave | CT-17 (meta com "a" e "b") |
| M35a (revisão adversarial) | ao tipar, o agrupamento deixa de usar `coalesce` e separa nulo de `""`: o `UPDATE` morre no índice único | CT-17 (linhas 3 e 6) |
| M35b (adversarial, rodada 2) | ao tipar as linhas, o merge passa a pegar os timestamps da linha mais nova em vez do máximo | CT-17 (linha 4) |
| M35c (adversarial, rodada 2) | a união de meta vira "o primeiro vence" (`+` em vez de `array_merge`) | CT-17 (linha 5) |
| M35d (adversarial, rodada 2) | o laço apaga só a primeira duplicata do grupo, e o trio deixa duas linhas | CT-17 (linha 6) |

---

## Regra R9: nenhum redirect recebe URL de login nula onde o nulo é alcançável

> `RQ-08` (Adendo 1) · perfil **mínimo** (área B, Impacto 3) · técnica: **EP** (painel com login × painel sem login), o mesmo eixo da R4
>
> A premissa **P-02** vale aqui como fixa (decisão da sessão): no painel sem login, o destino é a raiz do painel.

```gherkin
  Regra: o nulo de URL de login é tratado onde é alcançável. Quem encerra a sessão sai para um destino não nulo dentro do painel

    @premissa
    Esquema do Cenário: [CT-23] definir senha por e-mail num painel sem login encerra a sessão e leva à raiz do painel
      Dado a Ana autenticada, com o painel corrente "<painel>" <login>
      Quando a Ana envia o pedido de "Definir senha por e-mail"
      Então a resposta é um redirecionamento para "<destino>", que não é nulo nem vazio
      E a Ana não está mais autenticada
      E exatamente um link de redefinição de senha foi enviado para "ana@example.com"

      Exemplos:
        | painel     | login                               | destino       | # partição               |
        | admin      | com login                           | /admin/login  | fato: tem login          |
        | financeiro | sem login (painelRegistradoEmTeste) | /financeiro   | premissa P-02 (fixa)     |
```

- Livewire `App\Livewire\DefinirSenhaPorEmail`, ação `enviar`, em `tests/Kit/DefinirSenhaPorEmailTest.php`. O painel corrente vem de `Filament::setCurrentPanel('<painel>')`. "Exatamente um link" usa `Notification::fake()` com a notificação de redefinição que o caso existente "envia o link de redefinicao do filament…" já afirma. É o efeito do caminho feliz, e a Ana é a destinatária.
- **Por que as três asserções juntas**: o RD-02 descreve a tela congelada **com a sessão já invalidada**. Um cenário que só olhasse o redirect aceitaria "redireciona sem encerrar", e um que só olhasse a sessão aceitaria o `redirect(null)`.
- **`RegistroPorConvite::register`, ramo `aprovacao_pendente`** (cadastro recebido e à espera de aprovação: encerra a sessão e redireciona ao login do `/app`). O painel é **sempre** o `app`, e o `/app` sempre tem `->login()` (`AppPanelProvider.php:78`). Por isso o nulo **não é alcançável** na configuração do kit, e esse ramo não tem cenário executável. Fica como **lacuna declarada M40**, no molde de R3b. A regressão do ramo com login está em `tests/Kit/DestinoAposCadastroTest.php` e `tests/Kit/RegistroAbertoTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | `redirect(getLoginUrl())` com o nulo passado adiante: a tela congela com a sessão já encerrada (RD-02) | CT-23 (linha financeiro: redirect exato para `/financeiro`) |
| M37 | `getLoginUrl() ?? ''` ou `?? url('/')`: destino vazio, ou fora do painel | CT-23 (linha financeiro, destino exato) |
| M38 | o tratamento do nulo sai antes do logout e a sessão continua aberta | CT-23 (Ana não está autenticada) |
| M39 | "simplificar" e mandar todo pedido para a raiz do painel, inclusive onde há login | CT-23 (linha admin) |
| M40 | `RegistroPorConvite::register` (ramo `aprovacao_pendente`) com `redirect(null)` se o `/app` perder o login | ⚠️ **lacuna declarada**: o `/app` sempre tem login no kit, então o mutante e o código correto dão o mesmo observável. Tentado: painel sem login como corrente não afeta, porque o ramo usa `getPanel('app')` fixo. Roteado ao `fw-revisor-diff` (grep por `redirect(` recebendo `getLoginUrl()` sem guarda) |

---

## Regressão apontada (testes existentes, não reescritos)

| Arquivo tocado (superfície do plano) | Testes existentes que precisam seguir verdes |
|---|---|
| `app/Filament/Concerns/DescobreCardsDoPainel.php` (3 hubs) | `tests/Kit/HubDeCardsTest.php`, `tests/Tenancy/HubDoNegocioTest.php`, `tests/Kit/PermissoesDeTelasTest.php`, `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` |
| `app/Filament/Admin/Resources/Roles/Pages/{CreateRole,EditRole}.php` | `tests/Kit/TelaDePapeisTest.php`, `tests/Kit/UuidDoPapelTest.php`, `tests/Kit/RenomeacaoDePapelTest.php`, `tests/Kit/PaineisTest.php` |
| `app/Filament/Pages/Auth/RegistroPorConvite.php` | `tests/Kit/ConviteTest.php`, `tests/Kit/DestinoAposCadastroTest.php`, `tests/Kit/RegistroAbertoTest.php`, `tests/Kit/ProtecaoAntiRoboTest.php`, `tests/Kit/TelasDeAutenticacaoTest.php`, `tests/Tenancy/ConviteTenancyTest.php`, `tests/Tenancy/RegistroAbertoTenancyTest.php`, `tests/Tenancy/LoginUnificadoTenancyTest.php` |
| `app/Filament/Pages/Auth/TelaBloqueio.php` | `tests/Kit/BloqueioDeSessaoTest.php`, `tests/Kit/IdentidadeVisualTest.php`, `tests/Kit/PermissoesDeAcoesTest.php` |
| `app/Filament/Pages/Auth/TelaLogin.php` | `tests/Kit/SituacaoDaContaTest.php`, `tests/Kit/LoginUnificadoTest.php`, `tests/Kit/LixeiraTest.php`, `tests/Kit/RodapeCoerenteTest.php` |
| `app/Livewire/DefinirSenhaPorEmail.php` | `tests/Kit/DefinirSenhaPorEmailTest.php` (e o CT-23 novo no mesmo arquivo) |
| `app/Http/Controllers/Auth/LoginSocialController.php` | `tests/Kit/LoginSocialGoogleTest.php`, `tests/Kit/LoginSocialPorPainelTest.php`, `tests/Kit/VinculoDeProvedorSocialTest.php`, `tests/Kit/CadastroSocialPorConviteTest.php`, `tests/Kit/LoginSocialContaIndisponivelTest.php`, `tests/Tenancy/LoginSocialGoogleTenancyTest.php` |
| `app/Notifications/PrimeiroAcessoSocial.php` | `tests/Kit/VinculoDeProvedorSocialTest.php`, `tests/Kit/LoginSocialContaIndisponivelTest.php` |
| `app/Models/Convite.php` (`aceitar`, `aceitarComoUsuarioExistente`, envio em lote) | `tests/Kit/ConviteTest.php`, `tests/Kit/ConviteUsuarioExistenteTest.php`, `tests/Kit/ConviteEmMassaTest.php`, `tests/Kit/ConviteEmMassaDtoTest.php`, `tests/Tenancy/ConviteUsuarioExistenteTest.php`, `tests/Tenancy/ConviteEmMassaTenancyTest.php` |
| `app/Support/ImportExport/ImportadorDoKit.php` | `tests/Kit/ImportExportTest.php`, `tests/Tenancy/ImportExportTenancyTest.php` |
| `app/Http/Middleware/ExigirEmailVerificado.php` (guarda de invariante no lugar da exceção do neon, RQ-09) | `tests/Kit/VerificacaoDeEmailTest.php` (mata o M10g), `tests/Kit/ConfiguracoesDoKitTest.php`, `tests/Kit/RegistroAbertoTest.php`, `tests/Kit/TelasDeAutenticacaoTest.php` |
| `app/Livewire/AssistenteChatWidget.php` | **nenhum**, por isso CT-14…CT-16 são novos |
| `database/migrations/2026_08_12_164953_harden_onboarding_progress_scope.php` | **nenhum**, por isso CT-17 é novo |
| `phpstan.neon` | `tests/Kit/QualidadeDeCodigoTest.php` (os casos atuais continuam; o docblock que diz "level 7" precisa acompanhar) |

Comando mínimo da regressão (suíte Kit + Tenancy, uma por comando):
`php artisan test --testsuite=Kit --parallel --compact` e `php artisan test --testsuite=Tenancy --parallel --compact`.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | CT-16 (visitante com id de conversa da Ana), CT-22 (autenticado alheio) |
| Autorização exercida na ação (não só `can()`) | CT-16 (a ação é chamada, não consultada) |
| Idempotência (ancorada no agregado) | não se aplica: nenhuma operação de escrita nova. O aceite duplo já está em `ConviteTest` ("recusa reuso do convite…") |
| Concorrência | não se aplica: nada novo de contador. A migration existe justamente por concorrência, mas o que ela previne é o índice único, que não muda aqui |
| Fronteira no ponto de entrada (gravação) | não se aplica: nenhum campo novo |
| Domínio condicionado | não se aplica |
| Estado × operação de escrita (o removido ainda funciona?) | CT-13 (model), CT-18…CT-21 (cada ponto de entrada) e CT-12 |
| Ausente ≠ null ≠ vazio | CT-17 (escopo `null` → `""`), CT-09 (painel ausente) |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica: os timestamps do CT-17 são comparados literalmente, sem conversão |
| Unicode / limite de varchar | não se aplica |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: nenhum payload novo |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| Superfície Livewire (método público, prop pública) | CT-16 (5 métodos públicos por `$wire.`, `responder` inclusive, com `mensagemPendente` forjada por `set`). `conversaId` já é `#[Locked]`. `mensagem` fora do domínio já tem `#[Validate]`: lacuna declarada, não é mudança desta entrega |
| Estado do framework usado sem validar | não se aplica: nenhum `$filters`/`$tableFilters` tocado |
| IDOR por entidade | `agent_conversations`: CT-16, CT-22 · `convites`: fora do escopo (nenhuma rota nova) |
| Escopo com discriminante nulo (fecha ou abre?) | CT-15/CT-16 (usuário nulo: o histórico **fecha**, e a posse com nulo **recusa com 403**, não abre nem cai em `whereNull`) |
| Saída do estado de erro (4xx/redirect tem destino) | CT-10, CT-11, CT-23 (destino exato e não vazio). CT-16: o 403 é resposta de ação Livewire, e o visitante continua na tela de login onde já estava. CT-09/CT-13: exceção de invariante em código de desenvolvedor, sem usuário final no caminho (pergunta P-05) |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | nível efetivo ≥ 8 e rigor resolvido, nunca pulado | R1 | EP | Kit (processo) | `tests/Kit/QualidadeDeCodigoTest.php` | M1, M4a, M4c |
| CT-02 | invocações não rebaixam nem se desligam; a linha do composer passa sem `.github/` | R1 | EP | Kit | `tests/Kit/QualidadeDeCodigoTest.php` | M2, M3, M4b, M4d, M4e, M4f |
| CT-03 | análise limpa | R1 | EP | comando de gate | `composer types:check` / CI | M4 |
| CT-04 | sem baseline | R2 | tabela de decisão | Kit | `tests/Kit/QualidadeDeCodigoTest.php` | M5 |
| CT-05 | exceções com escopo e específicas, sem `identifier`/`rawMessage` (3 linhas) | R2 | tabela de decisão | Kit | `tests/Kit/QualidadeDeCodigoTest.php` | M6, M7, M8, M10c |
| CT-06 | inventário fechado nas 3 da `main`, sem `ExigirEmailVerificado`, sem stub nem extensão própria | R2 | tabela de decisão + caracterização | Kit | `tests/Kit/QualidadeDeCodigoTest.php` | M5, M10a, M10b, M10d, M10f |
| CT-07 | escopo analisado intacto | R2 | EP | Kit | `tests/Kit/QualidadeDeCodigoTest.php` | M9 |
| CT-08 | `@phpstan-ignore` congelado, sem `assert(`/`@phpstan-assert` nos tocados `@premissa` | R2 | EP | Kit | `tests/Kit/QualidadeDeCodigoTest.php` | M10, M10e |
| CT-09 | hub sem painel: exceção `@premissa` | R3 | EP | Kit | `tests/Kit/HubDeCardsTest.php` | M11, M12, M13 |
| CT-10 | bloqueio: destino com/sem login `@premissa` | R4 | EP | Livewire | `tests/Kit/BloqueioDeSessaoTest.php` | M17…M20 |
| CT-11 | social: destino com/sem login `@premissa` | R5 | EP | Feature (HTTP) | `tests/Kit/LoginSocialPorPainelTest.php` | M21, M22, M23 |
| CT-12 | FK recusa excluir papel com convite | R6a | EP | Feature | `tests/Kit/ConviteTest.php` | M29 |
| CT-13 | aceite sem papel no model: exceção sem efeito | R6a | EP + rastreio | Feature | `tests/Kit/ConviteTest.php` | M24…M28, M29a |
| CT-14 | `/app/login` do visitante: 200, widget montado, sem conversa | R7 | EP | Feature (HTTP) | `tests/Kit/AssistenteChatWidgetTest.php` | M30, M33a |
| CT-15 | widget lista só para a dona | R7 | EP persona | Livewire | `tests/Kit/AssistenteChatWidgetTest.php` | M30, M33, M33b, M33c |
| CT-16 | ações do visitante, `responder` inclusive: 403 sem efeito | R7 | EP + caracterização | Livewire | `tests/Kit/AssistenteChatWidgetTest.php` | M31, M32, M32a, M33b |
| CT-17 | fusão de duplicatas igual à da `main` (6 linhas) | R8 | caracterização | Feature (migration) | `tests/Kit/MigracaoDoEscopoDoOnboardingTest.php` | M34, M35, M35a…M35d |
| CT-18 | cadastro por convite sem papel (montagem / envio): oráculo comum | R6b | EP por ponto de entrada | Livewire | `tests/Kit/ConviteTest.php` | M29b, M29c |
| CT-19 | convites recebidos, ação aceitar sem papel: oráculo comum | R6b | EP por ponto de entrada | Livewire (ação de tabela) | `tests/Kit/ConviteUsuarioExistenteTest.php` | M29d |
| CT-20 | cadastro social por convite sem papel: oráculo comum | R6b | EP por ponto de entrada | Feature (HTTP) | `tests/Kit/CadastroSocialPorConviteTest.php` | M29e |
| CT-21 | tenancy, dois verbos, convite sem papel | R6b | EP por ponto de entrada | Feature (Tenancy) | `tests/Tenancy/ConviteTenancyTest.php`, `tests/Tenancy/ConviteUsuarioExistenteTest.php` | M29f, M29g |
| CT-22 | autenticado alheio: 404 sem efeito | R7 | caracterização | Livewire | `tests/Kit/AssistenteChatWidgetTest.php` | M31a, M31b |
| CT-23 | definir senha em painel sem login: raiz do painel, sessão encerrada, 1 link `@premissa` | R9 | EP | Livewire | `tests/Kit/DefinirSenhaPorEmailTest.php` | M36…M39 |

Lacunas declaradas (sem matador): M14, M15, M16 (R3b) e M40 (R9), todas de nulo inalcançável na configuração do kit, roteadas ao `fw-revisor-diff`. O M10g é morto pela regressão existente `VerificacaoDeEmailTest`, não por CT novo.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Rodar `phpstan analyse` dentro de um caso Pest | duplica o `@types:check` do `composer test` e custa minutos. CT-03 fica como comando de gate |
| ~~`RegistroPorConvite` pela tela com convite sem papel~~ | **reaberto** pela revisão adversarial (M29a, M29b): agora é o CT-18 |
| ~~`responder` sem usuário no CT-16~~ | **reaberto** pelo RQ-10 (Adendo 1): agora é linha do CT-16 |
| Remover `->login()` do `/app` para exercitar R3b | muda a configuração da feature que se quer testar. Ver lacunas M14…M16 |
| CT de `ImportadorDoKit`, `CreateRole`/`EditRole`, `ExigirEmailVerificado` | não há nulo alcançável novo com comportamento a definir. Cobertos pela regressão apontada. Para o `ImportadorDoKit`, ver a rejeição do M-I na revisão adversarial |
| Casos para RQ-01/02/05 | não são comportamento de aplicação, e o QA gate confere |

## Perguntas para o 00-requisito.md

> **Desvio declarado**: o orquestrador restringiu a escrita a este arquivo. O bloco abaixo está pronto para colar em `## Ambiguidades e Perguntas Abertas` do `00`. As perguntas bloqueiam os cenários `@premissa` citados.

```markdown
- **RQ-06 / hub fora de painel** — o 00 não diz o que um hub faz sem painel corrente.
  - **Assumido**: exceção de invariante com mensagem clara (falha fechado); nunca cartões do painel padrão. (CT-09)
  - **Se negado**: lista vazia sem exceção; CT-09 inverte as duas primeiras linhas do `Então`. O invariante (nenhum cartão de outro painel) fica.
- **RQ-06 / tela de bloqueio em painel sem login** — painel de projeto sem `->login()` que usa o plugin de bloqueio. *(Adendo 1: a sessão adotou esta premissa como fixa também para o CT-23 de `DefinirSenhaPorEmail`.)*
  - **Assumido**: o visitante vai para a raiz do próprio painel (destino dentro do painel). (CT-10, linha `financeiro`)
  - **Se negado**: exceção de invariante que nomeia o painel; CT-10 inverte a linha. Nunca `''`, `/` ou laço.
- **RQ-06 / login social com painel de origem sem login**.
  - **Assumido**: cai no login do painel padrão, como painel inexistente. (CT-11, linha `financeiro`)
  - **Se negado**: raiz do painel de origem (`/financeiro`); CT-11 inverte a linha. Nunca 500, sessão aberta ou conta criada.
- **RQ-06 / `@phpstan-ignore` anteriores a esta entrega** (2 em `RoleResource.php`, 1 em `User.php`).
  - **Assumido**: ficam, congelados por inventário; nenhum novo. (CT-08)
  - **Se negado**: zero; CT-08 inverte para 0 ocorrências e a entrega remove os três.
- **RQ-06 / forma da exceção de invariante** (convite sem papel, hub sem painel).
  - **Assumido**: exceção de domínio (não `Error`/`TypeError`) cuja mensagem cita o que falta; texto livre. Em tela, vira o 500 do handler com mensagem clara no log. *(Adendo 1: pelo RQ-09, o mesmo tipo de guarda de invariante é o que substitui a exceção do `ExigirEmailVerificado` no neon.)*
  - **Se negado** (o mantenedor quer saída amigável na tela de cadastro): nasce um CT de `RegistroPorConvite` com aviso e destino.
- **Excluir pela tela um papel que tem convite pendente** (anterior a esta entrega): a FK recusa, e a ação de excluir do `RoleResource` vira `QueryException` (500).
  - **Assumido**: fora desta entrega. O CT-12 prova só o desenho (a FK existe).
  - **Se negado**: nasce um CT pela ação da tela com aviso de recusa, papel e convite intactos.
- **RQ-06 / `?? url('/')` já existente em `LoginSocialController::urlDoPainel`** — é valor inventado pela letra do 00, mas anterior a esta entrega e inalcançável sem tenant por domínio.
  - **Assumido**: fica fora desta entrega (lacuna M16).
  - **Se negado**: vira exceção de invariante; sem CT executável na configuração do kit.
```

## Revisão adversarial

Disparo obrigatório por Impacto 3 na área B. **Rodada 1**, por sub-agente `fw-adversario-ct`, cego (recebeu só o `00` e o `04`), cobrindo todas as áreas (A–D) e regras (R1–R8). Foram **5 implementações que passavam + 1 bônus + 7 oráculos fracos**. Todos foram fechados:

| Achado | Virou |
|---|---|
| `checkNullables: false` com `level: 8` | CT-01 afirma os parâmetros de rigor resolvidos (M4a) |
| stub próprio estreitando `?T` para `T` | CT-06: `stubFiles` só de vendor (M10b) |
| exceção antiga alargada (paths/regex) | CT-06: as 3 antigas iguais às da `main` (M10a) |
| path posicional, `-l7`, `\|\| true`, `continue-on-error`, outro workflow | CT-02 ampliado + linha de varredura (M4b). CT-03 roda o `composer types:check` real |
| `assert()` como tratamento de nulo; a tela estoura antes do model | CT-13 exclui `AssertionError`; **CT-18 novo** pela tela (M29a, M29b) |
| bônus: conversa sem dono aberta por `whereNull` | CT-15 e CT-16 ganham o "Rascunho órfão" (M33b) |
| CT-11 com "contém" | Location exata |
| CT-14 passa com o widget ausente | afirma o componente montado (M33a) |
| CT-17 só nulo × nulo | linha nulo × `""` (M35a) |
| CT-12 = 500 pela tela do RoleResource | é comportamento anterior; virou pergunta P-07 |
| CT-06 "Tentado" frouxo | exige alternativa descartada depois do "Tentado" |
| RQ-01/02/05 sem predicado | evidência por comando no Mapa de Regras (não Pest) |

**Rodada 2** (re-revisão pedida porque a rodada 1 criou o CT-18). Mesmo contrato cego, disparada pelo orquestrador. **Não convergiu**: veio achado estrutural (R6 cobria o model e só um ponto de entrada). A skill limita a 2 rodadas, então **não há rodada 3**. O fechamento foi **dividir R6** em R6a/R6b e reforçar os oráculos.

| Achado | Decisão | Virou |
|---|---|---|
| M-A: R6 prova o model e uma tela só; os outros pontos de entrada do aceite (oferta, social, tenancy) ficam sem cenário | **aceito**, estrutural | R6 dividida em R6a (CT-12, CT-13) e **R6b** (CT-18 reforçado + **CT-19, CT-20, CT-21** novos), com oráculo comum (a)…(g) |
| M-B: CT-18 sem não-efeito de notificação, sessão e redirect de sucesso | aceito | itens (e), (f), (g) do oráculo comum. O CT-18 vira Esquema montagem/envio (M29c) |
| M-C: exceção por `identifier`/`rawMessage`/`messages` escapa das amostras; selecionar a `ExigirEmailVerificado` pela mensagem é frágil | aceito | CT-05 (chaves proibidas, `message` obrigatório), CT-06 (seleção por path, contagem 1) (M10c) |
| M-D: extensão de tipo própria por `services`/`conditionalTags` | aceito | CT-06 (M10d) |
| M-E / M-J: CT-17 não discrimina máximo na linha antiga, colisão de meta nem trio | aceito | CT-17 com 6 linhas (M35b, M35c, M35d), marcado de novo como caracterização a medir na `main` |
| M-F: falta a persona autenticada que não é dona | aceito | **CT-22 novo** (404, caracterização) e CT-15 com a conversa do Bruno (M31a, M31b, M33c) |
| M-G: step do CI com `if:`, gatilho do workflow, indireção de script | aceito | CT-02 (M4d) |
| M-H: `assert(`/`@phpstan-assert` estreitam o tipo sem tratar o nulo | aceito | CT-08 (M10e) |
| CT-01 pode se pular | aceito | CT-01: exit 0, JSON válido, nunca pulado (M4c) |
| CT-14 declarado matador do M33 sem discriminar | aceito | CT-14 mata só M30/M33a. M33 fica com o CT-15 |
| M-I: o `ImportadorDoKit` sem cenário de nulo | **rejeitado** | A guarda do `ImportadorDoKit` é sobre o ciclo de vida do Filament (`Importer::$record` lido antes de `resolveRecord()`), não sobre dado do arquivo importado, e papel não é coluna desse importador. Não há cenário executável: fica como nulo impossível pelo desenho (R3b), com regressão em `ImportExportTest`/`ImportExportTenancyTest` |

**Estado final**: não convergiu → R6 dividida, sem rodada 3. Os cenários novos da rodada 2 (CT-19…CT-22) **não passaram por revisão cega**. É dívida visível, e o `feature-quality-gate` (dimensão de adequação da suíte + `pest --mutate`) é o próximo olhar sobre eles.

## Sem CT-B

- Motivo: a entrega não tem superfície de UI nova nem rota nova. Toda asserção deste conjunto é provada por processo (config), HTTP ou componente Livewire. Nenhuma depende de JavaScript executado, pixel, tema ou acessibilidade. O `05-casos-de-teste-browser.md` não é criado.
