# Relatório de QA — `/public` nunca aparece na URL antes do painel

> Requisito: `00-requisito.md` (**incluindo o Adendo 1**) · Plano: `01-plano-acao.md`
> Perfil de esforço: **padrão** (correção · sem UI própria · domínio comum, **mas** toca infra
> compartilhada: a raiz de toda URL da aplicação)
> Natureza da wiki: correção · Regressão: sim (parcial — ver "Não Verificado")
> QA independente: não implementou, não corrigiu. Nenhuma linha de código de aplicação ou de
> teste foi alterada.

## Veredito — Ciclo 1

**REPROVADO → especificação** (prioridade de destino: especificação > teste > implementação)

- Blocker: **0** · Major: **6** · Minor: **9** · Cosmético: **1**
- Ambiente: **app NÃO servido** · Pest via `php artisan test` · Playwright MCP: **indisponível**
- Reconferido: `php artisan test tests/Kit/UrlSemPrefixoPublicTest.php` → **18/18, 20 asserções**
- Nenhum Major é de comportamento do produto. Os seis são: 1 lacuna de guarda (teste), 1 fronteira
  de configuração, e 4 de texto que contradiz o código entregue. **O desenho está certo.**

### Resposta direta aos dois pontos vigiados

**1. Arranjos A e B (mesma base `/public`, só um pode ser encurtado) — FECHADO no código.**
`RaizDeUrlSemPublic::handle()` (linha 81) exige `str_ends_with($base, '/public') && deveRemover()`;
`deveRemover()` (96–107) faz a declaração explícita vencer nos dois sentidos e, sem ela, procura
evidência positiva no `.htaccess` da raiz (119–131). Falha fechado. CT-09, CT-10, CT-11 e CT-12
cobrem as seis linhas da tabela de decisão de R5 e passam. O `00` (Adendo 1), a ADR-05 e o
docblock da classe descrevem o achado com honestidade, inclusive admitindo o erro anterior.
**A desonestidade residual está fora do código**: o `01` nunca foi atualizado e ainda lista a
premissa derrubada como risco assumido (QA-05), RQ-06/07/08 não têm linha de cobertura (QA-06), e
as docs de usuário e o CHANGELOG **abrem** afirmando a remoção incondicional e só a qualificam
três parágrafos depois (QA-07).

**2. CT-07 — remoção INCONSISTENTE em um dos lados.** O teste não tem CT-07 ✅, o Índice de
Cenários não tem ✅, R4 traz a nota de remoção com o motivo ✅. Mas a varredura SFDIPOT
(`04-casos-de-teste.md:32`) **ainda credita CT-07** à entrada "ausência de request" (QA-03), e o
cabeçalho ainda conta "R4 usa 2" (QA-04). A rastreabilidade credita, sim, cenário inexistente.

## Achados

### QA-01 — o registro do middleware não tem guarda: apagar o `append` deixa a suíte inteira verde · Major · destino 3
- **Dimensão**: K (adequação da suíte) + A
- **Relacionado a**: RQ-01, RQ-02, RQ-03, ADR-05, `bootstrap/app.php`
- **Esperado**: um mutante que desliga a feature por completo é morto por algum caso.
- **Observado**: os 18 casos chamam `(new RaizDeUrlSemPublic)->handle(...)` direto
  (`tests/Kit/UrlSemPrefixoPublicTest.php:64`). Nenhum teste do repo cita `bootstrap/app.php`,
  `getGlobalMiddleware()` ou faz request pelo kernel. Remover
  `$middleware->append(RaizDeUrlSemPublic::class)` torna a feature **inerte** e os 2.479 casos
  continuam verdes. É o mutante mais barato e mais letal, e não consta de M1–M14.
- **Repro**: `grep -rln "getGlobalMiddleware\|bootstrap/app.php" tests/` → só o próprio arquivo da
  feature. Registro hoje confere: `php artisan tinker --execute '…getGlobalMiddleware()'` → índice
  **9**, logo após `TrustProxies` (índice 3) — a ADR-05 está certa, mas nada a mantém certa.
- **Ação exigida**: CT novo que afirme a presença **e a posição** do middleware no stack global
  (mata também M11, hoje declarado "não coberto"). Invocar a `feature-test-design`.

### QA-02 — `KIT_URL_REMOVER_SUFIXO_PUBLIC=` vazio desliga a correção em vez de cair no padrão · Major · destino 3, depois 2
- **Dimensão**: B (fronteiras) + L4 (rule violada)
- **Relacionado a**: RQ-08, `config/kit.php`, `.ai/rules/config.md`
- **Esperado**: `.ai/rules/config.md` — *"o segundo argumento do `env()` só vale para chave
  ausente; com `CHAVE=` … o default nunca entra"* — e o kit já encapsulou a solução em
  `app/Support/BooleanoDoEnv.php`, cujo docblock diz textualmente que
  `filter_var(..., FILTER_VALIDATE_BOOLEAN)` **sozinho não resolve**.
- **Observado**: `config/kit.php` usa `env(...) === null ? null : filter_var(env(...), FILTER_VALIDATE_BOOLEAN)`.
  Medido: `ausente → null` (detecta) ✅, **`vazio → false`**, **`talvez → false`**. Quem apaga o
  valor e esquece o `=`, ou escreve qualquer coisa fora do vocabulário, troca "detectar" por
  "nunca agir" — e a ADR-04 garante que **não há log** para revelar isso.
- **Repro**: `php -r '$b=""; var_export($b===null?null:filter_var($b,FILTER_VALIDATE_BOOLEAN));'` → `false`.
- **Ação exigida**: CT para as partições vazio/ilegível; depois a coerção cair no `null`
  (tri-estado com a guarda explícita de `BooleanoDoEnv`, não o `filter_var` cru).

### QA-03 — a varredura SFDIPOT ainda credita CT-07 · Major · destino 1
- **Dimensão**: L1 · **Relacionado a**: desvio 4 do `03`
- **Observado**: `04-casos-de-teste.md:32` — `| **I** | … e a ausência de request (console, fila,
  scheduler) | CT-06, CT-07, CT-08 |`. CT-07 não existe em lugar nenhum do teste. A entrada
  "ausência de request" fica, na prática, **sem cenário** — e o `04` a dá por coberta.
- **Repro**: `grep -n "CT-07" 04-casos-de-teste.md` → linhas 20 (nota de remoção) e **32**.
- **Ação exigida**: tirar CT-07 da linha **I** e declarar a entrada como não coberta (ou cobri-la
  junto de QA-01, que é o mesmo assunto: o middleware não roda em console **porque** é middleware).

### QA-04 — três contagens do cabeçalho do `04` estão erradas depois do Adendo 1 · Minor · destino 1
- **Dimensão**: L1
- `"Sem matador: 0"` — **falso**: M11 se declara *"não coberto por teste"*. É 1.
- `"R4 usa 2"` — R4 usa 1 desde a saída de CT-07.
- A frase do teto **não menciona R5**, que usa 4 cenários (CT-09, CT-10, CT-11-esquema, CT-12)
  contra um teto declarado de 3 por regra. O estouro pode ser legítimo, mas não está declarado.

### QA-05 — o `01-plano-acao.md` descreve, inteiro, a implementação abandonada · Major · destino 1
- **Dimensão**: L3 · **Relacionado a**: desvios 2 e 3 do `03`, ADR-05
- **Observado**, tudo sem marca `*(alterado em …)*`: o passo 1 se chama
  *"`configureRaizDeUrl()` no `KitServiceProvider`"* e dá o path do provider; *"Análise dos
  Arquivos Existentes"* analisa só o provider e o ciclo de vida do `boot()` que a ADR-05 derrubou;
  *"Rollback: remover a chamada de `configureRaizDeUrl()` do `boot()`"* descreve um rollback que
  não existe; *"Variáveis de Ambiente: **Nenhuma nova**"* contradiz `KIT_URL_REMOVER_SUFIXO_PUBLIC`;
  e *"Riscos"* ainda diz que a aplicação sob caminho `public` é *"o caso quebrado visto de outro
  ângulo"* — **exatamente a frase que o Adendo 1 declara errada**. Não há passo para
  `bootstrap/app.php` nem para `config/kit.php`.
- **Repro**: ler `01-plano-acao.md` §"Estrutura de Implementação", §"Rollback", §"Riscos",
  §"Variáveis de Ambiente" contra `app/Http/Middleware/RaizDeUrlSemPublic.php`.
- **Ação exigida**: reescrever essas quatro seções e marcar a data; o `03` registra os desvios, mas
  quem lê a feature amanhã lê o `01`.

### QA-06 — RQ-06, RQ-07 e RQ-08 não têm linha na `## Cobertura do Requisito` · Major · destino 1
- **Dimensão**: A (omissão silenciosa na matriz declarada)
- **Observado**: a tabela do `01` para em RQ-05. As três cláusulas do Adendo 1 — as que *revisam*
  RQ-01 — não aparecem, e nenhum passo do plano as endereça. Elas **estão** implementadas
  (ADR-05 + R5 + CT-09…CT-12), então o defeito é da matriz, não do produto — mas é a matriz que a
  próxima pessoa usa para saber o que existe.
- **Ação exigida**: três linhas novas na tabela, apontando para a ADR-05 e para R5.

### QA-07 — docs pt/en e CHANGELOG abrem afirmando a remoção INCONDICIONAL · Major · destino 1
- **Dimensão**: L5 · **Relacionado a**: RQ-06, RQ-07, Adendo 1
- **Esperado**: o ponto que motivou o redesenho (encurtar sem reescrita quebra a instalação) é a
  primeira coisa que o operador precisa saber.
- **Observado**: os três textos dizem, antes de qualquer ressalva, *"Ele recusa honrar uma base de
  endereço terminada em `/public` e reconstrói a raiz sem o sufixo, uma vez por requisição"*,
  seguido de três bullets incondicionais (*"vale para qualquer painel"*, *"vale para asset"*,
  *"em instalação correta nada acontece"*). Só depois vem *"Quando o kit remove o prefixo, e
  quando não remove"*. Quem para no primeiro parágrafo — e é o parágrafo em negrito — conclui
  que a remoção é sempre. É o resíduo textual do desenho derrubado.
- **Repro**: `docs/pt/recursos/configuracao-global-filament.md` §"Onde apontar o `DocumentRoot`",
  parágrafo *"O kit se defende disso sozinho"*; o par em `docs/en`; `CHANGELOG.md` §Corrigido.
- **Ação exigida**: mover a condição para o mesmo parágrafo da afirmação, nos três.

### QA-08 — o doc EN perdeu uma cláusula e duplicou a outra · Minor · destino 1
- **Dimensão**: L5 (pt × en divergentes)
- **Observado**: `docs/en/...` — *"it cannot stop someone typing `/public/...` in the address bar,
  **nor stop someone typing `/public/...` in the address bar**"*. O pt traz, no lugar da segunda,
  *"nem corrige o redirecionamento extra que o `.htaccess` faz a cada clique"*.
- **Ação exigida**: restaurar a cláusula perdida no EN.

### QA-09 — a ADR-05 não declara que revisa a ADR-02, embora diga isso no corpo · Minor · destino 1
- **Dimensão**: L3
- **Observado**: `**Revisa**: ADR-01 (o local) e ADR-03 (o momento de ler o host)`, enquanto o
  Contexto da própria ADR-05 abre com *"O `/code-review` do diff **derrubou a premissa da
  ADR-02**"*. A ADR-02 segue **Status: Aceita**, sem marca, afirmando *"Forçar a raiz somente
  quando `Request::getBaseUrl()` termina em `/public`"* — que é a regra insuficiente.

### QA-10 — a tabela de Superfície Livewire justifica com a implementação que não existe · Minor · destino 1
- **Dimensão**: L3
- **Observado**: `02` — *"**nenhum** — o método é `protected` num provider"*. A entrega é uma
  classe com `handle()` público e `esquecerODetectado()` público estático. A **conclusão**
  (nenhuma superfície Livewire) continua correta; o **motivo escrito** está errado — e é
  literalmente o padrão que `.ai/rules/specs.md` nomeia como o mais perigoso.

### QA-11 — a varredura SFDIPOT nega a config nova · Minor · destino 1
- **Dimensão**: L3
- **Observado**: `04` linha **S**: *"um método `protected` num provider. Sem migration, model,
  job, policy, command ou **config nova**"*. Há config nova (`kit.url.remover_sufixo_public`) e a
  implementação não é um método de provider. A linha **P** também não menciona o `.htaccess`, que
  virou entrada de primeira ordem da feature.

### QA-12 — nenhuma citação `arquivo:linha` de vendor, contra a rule que o `03` marca "aplicada" · Minor · destino 1
- **Dimensão**: L4 · **Relacionado a**: `.ai/rules/specs.md`
- **Esperado**: *"Antes de escrever numa wiki, ADR ou caso de teste POR QUE um pacote se comporta
  de um jeito, abra o arquivo do `vendor/` e cite `file:line`."*
- **Observado**: o `00`, o `02`, o `04` e o docblock afirmam a cadeia de derivação da base do
  Symfony (`SCRIPT_NAME`/`SCRIPT_FILENAME`/`PHP_SELF`/`ORIG_SCRIPT_NAME`) sem uma citação sequer, e
  o `03` marca o item **`[x]`** com a isenção declarada *"a wiki não cita linha de vendor"*. A rule
  não abre essa exceção. As afirmações **conferem** — verifiquei:
  `vendor/symfony/http-foundation/Request.php:1936` (`prepareBaseUrl()`) e `:1944`
  (`ORIG_SCRIPT_NAME`) —, então o achado é de rastro, não de fato.
- **Ação exigida**: acrescentar as duas citações; corrigir a linha do `03`.

### QA-13 — o item "Falsificabilidade por mutante" está fechado com evidência do desenho anterior · Minor · destino 1, depois 3
- **Dimensão**: L3 + K
- **Observado**: `03` — *"5 rodados **no desenho anterior**, 5 mortos"*. O desenho anterior é o que
  a ADR-05 substituiu. Os mutantes do desenho atual (M12–M14 e o de QA-01) não foram medidos, e o
  checkbox não diz isso.

### QA-14 — o Índice de Cenários credita mortes que o cenário não produz · Minor · destino 1
- **Dimensão**: L1
- **Observado**, contra as tabelas por regra (que estão certas): CT-09 aparece matando **M13**
  (*"aceita a mera existência do `.htaccess`"* — mas em CT-09 não há arquivo nenhum, então M13
  passa); CT-11 aparece matando **M14** (*"lê a config só quando a detecção falha"* — em CT-11 a
  detecção falha de propósito, então M14 passa; quem o mata é CT-12); CT-05 aparece matando **M7**
  (CT-05 não encurta, logo a raiz alternativa nunca é exercitada).

### QA-15 — a suíte apaga o `.htaccess` real da raiz do working tree · Minor · destino 3
- **Dimensão**: I (efeito colateral da superfície nova) / infra de teste
- **Observado**: `tests/Kit/UrlSemPrefixoPublicTest.php:84` e `:90` fazem
  `@unlink(base_path('.htaccess'))`, e `:70`/`:216` escrevem no mesmo caminho. O arquivo **não é
  versionado nem gitignorado** (`git ls-files | grep htaccess` → só `public/.htaccess`). Quem
  desenvolve o kit no arranjo A — justamente o público da correção — perde o próprio `.htaccess`
  ao rodar a suíte, sem aviso e sem como restaurar.
- **Ação exigida**: preservar e restaurar o arquivo preexistente, ou apontar a detecção para um
  caminho injetável no teste.

### QA-16 — `KIT_URL_REMOVER_SUFIXO_PUBLIC` não está no `.env.example` · Cosmético · destino 1
- **Dimensão**: L5. As docs mandam o operador de nginx declarar a chave, e ela não aparece onde
  ele procura. **Não é violação de convenção**: outras 12 chaves `KIT_*` do `config/kit.php`
  também faltam lá.

## Matriz de Rastreabilidade (só as linhas com lacuna)

| RQ | Cláusula | Passo PRD | CT | Código | Veredito |
|----|----------|-----------|----|--------|----------|
| RQ-01 | `/public` nunca precede o painel (**sob RQ-06**) | 1, 2 | CT-01, 03, 04 | `RaizDeUrlSemPublic:81` | ⚠️ passo do PRD descreve outra implementação (QA-05) |
| RQ-02 | vale para `/admin`, `/infra`, `/app` | 2 | CT-06 | idem | ✅ |
| RQ-03 | vale para painel novo do usuário | 1 | CT-06 (`/financeiro/lotes`) | idem — nenhum painel nomeado | ✅ |
| RQ-04 | investigação e atribuição da causa | — | — | `00` §Achado | ✅ |
| RQ-05 | kit, branch própria, **PR para a `main`** | 4 | — | `3d8161a` | ⬜ PR ainda não aberto (esperado neste gate) |
| RQ-06 | só remove com evidência positiva | **—** | CT-09, CT-10 | `deveRemover():96` | ❌ sem linha de cobertura (QA-06) |
| RQ-07 | sem evidência, não age | **—** | CT-09, CT-10 | `raizReescreveParaPublic():119` | ❌ idem |
| RQ-08 | o operador declara por configuração | **—** | CT-11, CT-12 | `config/kit.php` | ❌ idem + fronteira do env aberta (QA-02) |
| — | registro do middleware no stack global | — | **nenhum** | `bootstrap/app.php:16` | ❌ sem guarda (QA-01) |
| — | entrada "ausência de request" | — | **nenhum** (CT-07 saiu) | — | ❌ creditada a cenário inexistente (QA-03) |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | 2 achados (QA-01, QA-06); nenhuma cláusula ficou sem código |
| B | Fronteiras e dados | ⚠️ | partições de base cobertas por CT-05; a fronteira do **env** não (QA-02) |
| C | Matriz de permissão | ⏭️ | a feature não lê usuário, papel nem tenant — sem célula a preencher |
| D | Observabilidade real | ✅ | ADR-04 decide "sem log", e o código não tem `Log::` nenhum. Sem PII, porque não há context |
| E | Performance | ✅ | zero query; leitura de arquivo só com o sufixo presente e memoizada (`:74`, `:106`) |
| F | UX de erro | ⏭️ | a feature não produz 4xx, 5xx, mensagem nem redirect |
| G | Tema e cor | ⏭️ | sem superfície de UI |
| H | Acessibilidade | ⏭️ | idem |
| I | Segurança da superfície nova | ✅ | sem rota, sem id, sem escrita. Hipótese do Host header **rejeitada** — ver abaixo |
| J | Regressão adjacente | ⚠️ | parcial: só o arquivo da feature foi reexecutado por mim (18/18) |
| K | Adequação da suíte | ⚠️ | passo estático: oráculos fortes, valor exato, estado de partida declarado em CT-09. 1 lacuna grave (QA-01). Mutação medida: **não** |
| L | Consistência documental | ❌ | 10 achados (QA-03…QA-14, QA-16) — a dimensão de maior rendimento nesta feature |

## Hipóteses Rejeitadas (destino 5 — registradas para não voltarem)

- **`getSchemeAndHttpHost()` usa o header `Host`, logo a raiz forçada seria envenenável.** Rejeitada:
  sem o middleware, o `UrlGenerator` já deriva a raiz do mesmo request. A superfície não é nova, e
  a ADR-03 já registra a dependência de `TrustProxies`.
- **A regex casaria uma `RewriteRule` comentada.** Rejeitada: `~^\s*RewriteRule…~mi` recusa `#`.
- **`--parallel` + escrita em `base_path('.htaccess')` = corrida entre workers.** Rejeitada:
  `grep -rn htaccess tests/` mostra que só este arquivo toca o caminho. (O dano ao working tree do
  desenvolvedor permanece — é QA-15, por outro motivo.)
- **`self::$deveRemover ??=` re-executaria a detecção quando o resultado é `false`.** Rejeitada:
  `false` não é `null`, o memo segura.

## Suspeitas Não Confirmadas

- **Runtime persistente (Octane/RoadRunner)**: `forceRootUrl` nunca é desfeito. Request A chega por
  `/public/app` e fixa a raiz; request B chega limpo, o middleware não entra no ramo, e a raiz de A
  continua valendo — em multi-domínio, com o host errado. Não reproduzido: o kit **não** instala
  Octane (`grep octane composer.json` → vazio). Registrado porque o kit é instalado por terceiros.
- **`RewriteRule ^(.*)$ public$1`** (sem a barra depois de `public`) é forma real e **não** casa a
  regex; o arranjo A escrito assim ficaria sem proteção. Falha fechado, então não quebra nada. Não
  reproduzido num servidor.

## Não Verificado

- **App não servido.** Nenhum request HTTP real, em nenhum dos dois arranjos: não observei o
  `/public` sumir (ou permanecer) numa barra de endereços. Tudo o que afirmo sobre comportamento
  vem do código, do stack de middleware resolvido e dos 18 casos.
- **Playwright MCP indisponível** nesta sessão. Sem `05`/CT-B — justificado no `04`, e o gate do
  `05` de fato não abre (sem superfície de UI).
- **Mutation score** (`--mutate`): não rodado. Exige driver de cobertura e escopo por `--path`. A
  dimensão K rodou só o passo estático; o mutante de QA-01 foi derivado por leitura, não medido.
- **`composer test:kit` completo**: não reexecutado. Aceito como declarado no `03` (2.479/2.479) e
  reconferido só o arquivo da feature — 18/18, 20 asserções, 11s.
- **Apache real**: o `.htaccess` de verdade, o `R=301` e a reescrita interna não foram observados;
  os testes reproduzem a *assinatura* que o servidor entrega, não o servidor.

## Débitos Aceitos

Nenhum ainda — o veredito é REPROVADO. QA-16 (Cosmético) é candidato a débito se o ciclo 2 fechar
os Majors.

---

## Veredito — Ciclo 2

**REPROVADO → especificação** (prioridade: especificação > teste > implementação)

- **Novos** neste ciclo: Blocker **0** · Major **3** · Minor **5** · Cosmético **0**
- **Herdados do ciclo 1 ainda abertos**: QA-05 (Major, ~1/4 fechado), QA-08, QA-09, QA-10, QA-11,
  QA-12, QA-13, QA-14 (Minor), QA-16 (Cosmético)
- Ambiente: **app NÃO servido** · Playwright MCP **indisponível** · commit `c1ba4ad`
- Reexecutado: `php artisan test tests/Kit/UrlSemPrefixoPublicTest.php` → **18/18, 21 asserções**;
  com `tests/Kit/BooleanoDoEnvTest.php` junto → **34/34, 42 asserções**. `composer test:kit` roda
  em paralelo por outra sessão, não reexecutado aqui.
- Nenhum Major é de comportamento do produto — de novo. **O código entregue está certo**; o que
  reprova é (a) uma superfície nova sem guarda e (b) texto que afirma o contrário do que foi medido.

### Fechamento dos 6 Major do ciclo 1 — verificado contra o código

| Achado | Situação | Evidência |
|---|---|---|
| **QA-01** | **FECHADO** | `CT-13` existe (`tests/Kit/UrlSemPrefixoPublicTest.php:260-281`) e afirma presença **e** posição. Medido: `bootstrap/app.php:21` é o **único** ponto de registro do middleware em todo o repo (`grep -rn RaizDeUrlSemPublic app bootstrap config routes tests`); stack global resolvido → índice **9**, `TrustProxies` no **3**. Apagar o `append` torna o `in_array` falso e derruba o caso. Guarda real. Ressalvas: **QA-21** e **QA-22** |
| **QA-02** | **FECHADO no código, ABERTO no teste** | `BooleanoDoEnv::ouNulo()` existe e foi medido: `null→null`, `''→null`, `'talvez'→null`, `'true'→true`, `'false'→false`, `'off'→false`. É exatamente o pedido. Mas o método nasceu **sem um único caso de teste** — ver **QA-17**. O destino era **3 → 2**, e só o 2 foi feito |
| **QA-03** | **FECHADO** | `04-casos-de-teste.md:32`, linha `I`: `CT-06, CT-08, **CT-13**`. CT-07 não é mais creditado em lugar nenhum além da nota de remoção |
| **QA-05** | **PARCIAL — continua Major aberto** | O `## Riscos` está corrigido (premissa tachada, `*(alterado em 2026-09-18)*`, apontando o Adendo 1) ✅. **As outras quatro seções não foram tocadas**: §"Estrutura de Implementação" passo 1 ainda se chama *"`configureRaizDeUrl()` no `KitServiceProvider`"*, com o path do provider; §"Análise dos Arquivos Existentes" só analisa o provider e o `boot()`; §"Rollback" ainda manda *"remover a chamada de `configureRaizDeUrl()` do `boot()`"*; §"Variáveis de Ambiente" ainda diz *"Nenhuma nova"*; §"Modelo de Execução" ainda diz *"roda uma vez por request, **no boot do provider**"*. Continua sem passo para `bootstrap/app.php` e `config/kit.php` |
| **QA-06** | **FECHADO com defeito novo** | RQ-06/07/08 ganharam linha (`01-plano-acao.md:24-26`) ✅ — mas as três apontam **passo 1**, que é o passo do provider abandonado. Ver **QA-18**: é o padrão que a própria skill nomeia — *"PRD que diz RQ-02 → passo 5 mas o passo 5 não trata disso é achado"* |
| **QA-07** | **FECHADO nos três** | pt: *"**quando consegue saber que é seguro** … **mas só quando há sinal**"*; en: *"**when it can tell that doing so is safe** … **but only when there is evidence**"*; CHANGELOG: *"— **so quando ha evidencia de que `/` roteia para dentro de `public/`**"*. A condição está agora no mesmo parágrafo da afirmação |

### Contagens do cabeçalho do `04` — conferidas uma a uma (QA-04 fechado)

| Contagem declarada | Real | ✔ |
|---|---|---|
| Cenários: **12** | CT-01…06, 08…13 = 12 `it()` no teste (18 casos com os datasets de CT-05 e CT-06) | ✅ |
| Regras: **6** | R1…R6 | ✅ |
| Mutantes previstos: **16** | M1–M4 (R1), M5–M7 (R2), M8–M9 (R3), M10–M11 (R4), M12–M14 (R5), M15–M16 (R6) | ✅ |
| Sem matador: **1** (M11) | só M11 se declara sem matador direto | ✅ |
| Teto: R1 3 · R2 2 · R3 1 · R4 1 · R6 1 · R5 **4** (estouro declarado) | soma 12, bate com os cenários | ✅ |

Resíduo: os créditos de morte do **Índice de Cenários** continuam errados — QA-14 **não** foi
mexido (CT-09 ainda mata M13, CT-11 ainda mata M14, CT-05 ainda mata M7) —, apesar de o `03`
declarar o contrário.

## Achados Novos do Ciclo 2

### QA-17 — `BooleanoDoEnv::ouNulo()` nasceu sem teste nenhum: o mutante do QA-01 de novo, na correção do QA-02 · Major · destino 3

- **Dimensão**: K + A · **Relacionado a**: RQ-08, QA-02, `.ai/rules/config.md`
- **Esperado**: a ação exigida do QA-02 era, nesta ordem — *"**CT para as partições vazio/ilegível**;
  **depois** a coerção cair no `null`"*. O destino era **3 → 2**, e o 3 vem primeiro por regra da
  skill: *"corrigir antes destrói a prova"*.
- **Observado**: só o 2 foi feito. `grep -rn "ouNulo" tests/` → **zero ocorrências**.
  `tests/Kit/BooleanoDoEnvTest.php` (16 casos) exercita apenas `comPadrao()`. Os três usos da chave
  no teste da feature são `config()->set(...)` direto (`:89`, `:241`, `:291`), que passa **por cima**
  da linha `config/kit.php:129` — a coerção do env não é exercitada por caso nenhum do kit.
- **O mutante**: apagar o guard `if ($bruto === null || $bruto === '') { return null; }` de
  `ouNulo()` (`app/Support/BooleanoDoEnv.php:72-74`). `KIT_URL_REMOVER_SUFIXO_PUBLIC=` passa a valer
  `false`, a correção desliga em silêncio no arranjo A — e **a suíte inteira segue verde**. É a
  mesma forma do QA-01, no código escrito para fechá-lo.
- **Repro**: `grep -rn "ouNulo\|KIT_URL_REMOVER" tests/` → nada;
  `php -r '... BooleanoDoEnv::ouNulo("")'` → `NULL` hoje, `false` com o guard removido.
- **Agravante**: `04-casos-de-teste.md:368` credita a cobertura à **classe**, não a um cenário —
  *"e o tri-estado (`""` e ilegível → `null`) por `BooleanoDoEnv::ouNulo()`, que é a convenção do
  kit"*. É a forma do QA-03: rastreabilidade creditando o que nenhum caso prova.
- **Ação exigida**: CT em `tests/Kit/BooleanoDoEnvTest.php` para `ouNulo()` nas partições
  ausente/vazio/ilegível/`true`/`false` — as irmãs `NumeroDoEnv` e `TextoDoEnv` já têm o padrão —, e
  a linha do `04` deixa de creditar a classe. Invocar a `feature-test-design`.

### QA-18 — o `03` declara "todos fechados" oito achados que não foram tocados · Major · destino 1

- **Dimensão**: L3/L4 (*"aplicada" sem evidência*)
- **Observado**: `03-progresso.md` → *"### Disposição dos achados do ciclo 1 — **todos fechados em
  2026-09-18**"*, com a linha coletiva *"QA-04, QA-08…QA-14, QA-16 | 1 | contagens do `04` refeitas
  … e o Índice deixou de creditar mortes que o cenário não produz"*. Conferido item a item:
  - **QA-08** ABERTO — `docs/en/...`: *"it cannot stop someone typing `/public/...` in the address
    bar, **nor stop someone typing `/public/...` in the address bar**"*. A cláusula duplicada
    continua lá e a do pt continua perdida.
  - **QA-09** ABERTO — `02:152` ainda `**Revisa**: ADR-01 … e ADR-03`, sem a ADR-02.
  - **QA-10** ABERTO — `02:11` ainda *"**nenhum** — o método é `protected` num provider"*.
  - **QA-11** ABERTO — `04:29`, linha `S`, ainda *"um método `protected` num provider. Sem … **config
    nova**"*, com a config nova entregue.
  - **QA-12** ABERTO — `grep -rn "http-foundation/Request.php\|prepareBaseUrl" wikis/…` → **nada**.
  - **QA-13** ABERTO — o `03` ainda diz *"5 rodados **no desenho anterior**, 5 mortos"*.
  - **QA-14** ABERTO — os três créditos errados do Índice estão intactos.
  - **QA-16** ABERTO — `grep -n KIT_URL .env.example` → ausente.
  - **QA-05** listado como fechado descrevendo só o `## Riscos` — ver o quadro acima.
- **Por que é Major e não Cosmético**: o `03` é o tracking único da feature e o gate de abertura do
  PR (*"PR não abre enquanto houver Major aberto"*). Um quadro que declara tudo fechado **abre o
  gate sozinho**, e é o documento que a próxima pessoa consulta em vez de reabrir o `06`.
- **Ação exigida**: uma linha por achado na tabela de disposição, com o estado real; e o bloco
  `## Quality Gate` recebe a entrada do ciclo 2.

### QA-19 — o `04` e o `03` ainda ensinam as duas lições que ESTE commit mediu como falsas · Major · destino 1

- **Dimensão**: L3 · **Relacionado a**: `c1ba4ad`, docblock de
  `tests/Kit/UrlSemPrefixoPublicTest.php:37-44`
- **Esperado**: o commit `c1ba4ad` reescreveu o topo do teste justamente para desfazê-las —
  *"Este bloco já afirmou outras duas coisas, e as duas eram falsas … comentário de teste que ensina
  o errado é pior que comentário nenhum"* — e o harness perdeu `PHP_SELF`, `REQUEST_URI` e
  `URL::setRequest()` em consequência.
- **Observado**, nos arquivos da mesma wiki, sem marca de alteração:
  - `04-casos-de-teste.md` §Setup Global/Fixtures lista as **quatro** variáveis e afirma: *"**Por que
    as quatro, e não só `SCRIPT_NAME`**: o Symfony percorre `SCRIPT_FILENAME`, `PHP_SELF` e
    `ORIG_SCRIPT_NAME` … **Omitir qualquer uma** produz base **vazia** em todos os casos"*. O teste
    entregue passa **duas** (`SCRIPT_NAME` + `SCRIPT_FILENAME`) e está verde.
  - `03-progresso.md` §Notas de Implementação **N1**: *"O `UrlGenerator` guarda a própria referência
    de request: trocar `app()->instance('request', …)` não o afeta. Sem `setRequest()`, um harness de
    teste mede o request antigo"* — o docblock novo diz o oposto: *"alcança, pelo `rebinding` que o
    `RoutingServiceProvider` registra"*, e `URL::setRequest` saiu do arnês.
  - `03-progresso.md` §**N2** e §Retrospectiva repetem a primeira.
- **Repro**: `04` §Setup Global × `tests/Kit/UrlSemPrefixoPublicTest.php:53-65` e `:37-44`.
- **Ação exigida**: reescrever as duas notas com a medição atual e a data. É literalmente o motivo
  que o commit deu para reescrever o docblock — aplicado a um arquivo só de três.

### QA-20 — ADR-05 e o `03` ainda afirmam que a leitura do `.htaccess` é memoizada; o memo saiu neste commit · Minor · destino 1

- **Dimensão**: L3 + E
- **Observado**: `02-decisoes-arquiteturais.md:188` — *"**Custo**: uma leitura de arquivo,
  **memoizada por processo**"*; `03-progresso.md:32`, checkbox `[x]` **Custo medido** — *"… e é
  **memoizada por processo**, 2026-09-18"*. O ponytail de `c1ba4ad` removeu o memo estático e a API
  pública que ele exigia (`esquecerODetectado()` não existe mais), e o docblock de `deveRemover()`
  agora justifica o contrário: *"**Sem memo**: a leitura só acontece quando a base já veio com o
  sufixo"*.
- **Efeito real**: `is_file()` + `file_get_contents()` + `preg_match()` **por request** enquanto a
  base vier prefixada, em vez de uma vez por processo. O trade-off é defensável e está escrito no
  código; o que está errado é o texto — e é um checkbox fechado *com* a evidência falsa.
- **Ação exigida**: corrigir as duas linhas e marcar a data.

### QA-21 — o comentário do CT-13 afirma "medido" o que a medição nega · Minor · destino 1

- **Dimensão**: L3 + L2
- **Observado**: `tests/Kit/UrlSemPrefixoPublicTest.php:261-265` — *"O CONTRATO, e não
  `Foundation\Http\Kernel` direto: resolver a classe concreta instancia um kernel novo, **com o stack
  de fábrica e sem o que o `bootstrap/app.php` configurou**. O teste ficaria vermelho com o registro
  no lugar — **medido**."*
- **Medido por mim**: `app(Illuminate\Foundation\Http\Kernel::class)` devolve **sim** uma instância
  nova (`$k === app(Kernel::class)` → `false`), mas o stack dela **contém** o registro:
  `total=10, tem_raiz=true, idx=8`. Causa: `ApplicationBuilder::withMiddleware()` registra
  `afterResolving(Illuminate\Contracts\Http\Kernel::class, …)` em
  `vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:289`
  (com `use Illuminate\Contracts\Http\Kernel as HttpKernel` na linha 9), e o container dispara os
  callbacks de `afterResolving` para **todo tipo de que o objeto resolvido é instância**. O teste
  ficaria **verde** com a classe concreta.
- **Por que importa**: usar o contrato continua sendo a escolha certa — a instância singleton é a que
  o `Kernel::handle()` usa e a que carrega o que os pacotes empilham em runtime (12 itens contra 10).
  O **motivo escrito** é falso, e é a **terceira** afirmação "medida" falsa nos comentários deste
  mesmo arquivo; as outras duas o commit acabou de remover.
- **Ação exigida**: trocar o motivo pelo verdadeiro, citando `ApplicationBuilder.php:289` — fecha de
  quebra parte do QA-12, que pede citação de vendor.

### QA-22 — a asserção de posição do CT-13 passa vazia se o `TrustProxies` sair do stack · Minor · destino 3

- **Dimensão**: K (oráculo que degrada em silêncio)
- **Observado**: `:277-280` —
  `expect(array_search(Raiz::class, $global, true))->toBeGreaterThan(array_search(TrustProxies::class, $global, true))`.
  `array_search` devolve **`false`** quando não acha. Com o `TrustProxies` ausente — um
  `$middleware->remove()`, uma troca de classe pelo framework — a comparação vira `9 > false`, que em
  PHP converte os dois para bool → `true > false` → **passa**. A guarda some exatamente no cenário em
  que ela seria necessária.
- **Nota**: hoje os dois estão presentes (`TrustProxies` no índice 3, `Raiz` no 9) e o mutante M16
  (*registrado antes do `TrustProxies`*) **é** morto — `2 > 3` é falso. O defeito é de robustez do
  oráculo, não do produto.
- **Ação exigida**: afirmar antes que o `TrustProxies` está no stack (ou comparar índices inteiros já
  checados), para que a posição não possa passar por ausência.

### QA-23 — o `04` ainda descreve o Exemplo de CT-11 que este commit cortou do teste · Minor · destino 1

- **Dimensão**: L1
- **Observado**: o `04` §R5 mantém `Esquema do Cenário: [CT-11]` com **dois** Exemplos (`true` e
  `false`), e a tabela de decisão de R5 ainda mapeia a linha *"não existe · `false` · não"* para
  **CT-11**. No teste, CT-11 deixou de ser dataset: a linha `false` foi cortada por tautológica
  (`:236-238`). A combinação segue creditada a um Exemplo que não existe mais.
- **Menor que o QA-03 porque** a combinação é indistinguível por construção — sem `.htaccess` a
  detecção já devolve falso —, então não há perda real de cobertura, só de crédito.
- **Também aqui**: o `03` §2 ainda registra *"CT-01…CT-06, CT-08…CT-12 — 18/18, **20 asserções**"* —
  sem CT-13, e medido agora dá **21 asserções**.
- **Ação exigida**: CT-11 vira Cenário simples no `04`; a linha da tabela de decisão aponta CT-09 ou é
  declarada indistinguível; a evidência do `03` é reconferida.

### QA-24 — a "Varredura da classe irmã" continua dizendo que não há classe nova · Minor · destino 1

- **Dimensão**: L3 · **Relacionado a**: QA-17
- **Observado**: `03-progresso.md` §"Varredura da classe irmã (step 5)": *"**nenhuma classe nova** — a
  correção é um método `protected` num provider existente | — | — | não se aplica"*. A entrega tem uma
  classe nova (`App\Http\Middleware\RaizDeUrlSemPublic`) e um método público novo numa classe
  **compartilhada** (`App\Support\BooleanoDoEnv::ouNulo()`).
- **Por que não é só texto**: é exatamente a varredura que pergunta *"a nova entrou nas listas em que
  as irmãs aparecem?"*. Rodada, ela teria perguntado por que `NumeroDoEnv`, `TextoDoEnv` e
  `comPadrao()` têm caso de teste e `ouNulo()` não — que é o **QA-17**.
- **Ação exigida**: refazer as duas linhas da tabela com as classes reais.

## Verificações que NÃO viraram achado (destino 5 — registradas para não voltarem)

- **`BooleanoDoEnv` é classe compartilhada: quem mais a usa foi afetado?** **Não.** O diff de
  `c1ba4ad` sobre `app/Support/BooleanoDoEnv.php` é **puramente aditivo** (+21 linhas, só o `ouNulo()`
  e seu docblock); `comPadrao()` está byte a byte igual. Os seis consumidores
  (`config/kit.php:228,229,230,254,287`) continuam em `comPadrao()`, e `tests/Kit/BooleanoDoEnvTest.php`
  passa (34/34 rodando junto com o da feature). O risco da classe compartilhada é o **método novo sem
  teste** (QA-17), não os antigos.
- **O teste destrói o `.htaccess` do working tree (QA-15)?** **Fechado.** `beforeEach:82-84` guarda o
  conteúdo (ou `null`) e `afterEach:92-97` devolve simetricamente. Resíduo declarado, não achado:
  entre um e outro o arquivo real é substituído pela fixture 18 vezes, e um `kill`/fatal no meio ainda
  perde o original. Para quem desenvolve o kit no arranjo A a fixture escrita é uma `RewriteRule`
  válida, então o efeito de janela é benigno.
- **`config/kit.php` viola a `.ai/rules/config.md`?** Não. A rule exige falha **fechada** em chave que
  abre superfície pública; esta não abre superfície, e o ilegível cai em `null` = *detectar*, que sem
  sinal já não age. A direção do erro é a segura.
- **CT-13 pode passar sem o `append`?** Não. `bootstrap/app.php:21` é o único registro em todo o repo
  (grep em `app bootstrap config routes tests`); sem ele o `in_array` é falso.

## Dimensões — Ciclo 2

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | RQ-06/07/08 agora têm linha, mas apontam passo inexistente (QA-18); a coerção de RQ-08 segue sem cenário (QA-17) |
| B | Fronteiras e dados | ✅ | a fronteira do env (QA-02) foi corrigida e **medida** aqui: `null`/`''`/`'talvez'` → `null` |
| C, F, G, H | permissão · UX de erro · tema · a11y | ⏭️ | sem usuário, sem saída de erro, sem UI — inalterado desde o ciclo 1 |
| D | Observabilidade real | ✅ | continua sem `Log::`, por decisão da ADR-04. Sem PII |
| E | Performance | ⚠️ | o memo saiu: leitura de disco por request prefixado. Aceitável; **o texto é que não acompanhou** (QA-20) |
| I | Segurança da superfície nova | ✅ | `ouNulo()` é aditivo; nenhuma rota, id, escrita ou propriedade pública nova |
| J | Regressão adjacente | ⚠️ | reexecutei os dois arquivos tocados (34/34). `composer test:kit` completo roda em paralelo por outra sessão |
| K | Adequação da suíte | ❌ | CT-13 fecha o buraco maior (QA-01) ✅, mas abrem-se dois: método novo sem teste (QA-17) e oráculo de posição que degrada (QA-22). Mutação **não** medida |
| L | Consistência documental | ❌ | 6 dos 8 achados novos, mais **8 herdados** que o `03` declara fechados sem estarem (QA-18). Segue a dimensão de maior rendimento desta feature |

## Não Verificado — Ciclo 2

- **App não servido**: nenhum request HTTP real, em nenhum dos dois arranjos. Tudo o que afirmo sobre
  comportamento vem do código, do stack de middleware resolvido e dos 18 casos.
- **Playwright MCP indisponível**; sem superfície de UI, o gate do `05` não abre.
- **Mutation score (`--mutate`)**: não rodado. Os mutantes deste ciclo — o guard de `ouNulo()`, o
  `array_search` de CT-13 — foram derivados por leitura e por medição pontual com `php -r` e `tinker`,
  **não** por `pest --mutate`.
- **Mutante do `append` não executado**: apagá-lo exigiria alterar código de aplicação, o que esta
  skill proíbe. A morte foi provada por construção — registro único no repo.
- **`composer test:kit` completo**: rodando em paralelo por outra sessão; não reexecutado aqui.
- **Apache real**: `.htaccess`, `R=301` e reescrita interna continuam não observados.

## Convergência

O ciclo 2 **trouxe achado novo** (8), então o loop não encerra aqui. **Resta 1 ciclo** do teto de 3.
QA-17 e QA-18 são os únicos que exigem trabalho além de texto; os demais são reconciliação documental
e cabem numa passada só. Se o ciclo 3 ainda trouxer Major, escalar ao usuário.

### Nota pós-relatório (mesma sessão)

Depois de eu entregar o veredito, `tests/Kit/BooleanoDoEnvTest.php` apareceu **modificado e não
commitado** no working tree, com três casos novos de `ouNulo()` (ausente/vazio, ilegível,
legível) que citam o QA-17 no comentário. Registro para o ciclo 3 não se confundir:

- **O QA-17 era válido no commit auditado** (`c1ba4ad`): medi `grep -rn ouNulo tests/` → zero.
- Os casos novos **não** foram auditados por mim e **não** estão no commit. O ciclo 3 confere se
  eles matam o mutante do guard e, principalmente, se os cenários nasceram no `04` — hoje são
  `it()` sem ID de CT, o que reabre a sincronia L1 pelo outro lado (teste sem cenário no `04`).
