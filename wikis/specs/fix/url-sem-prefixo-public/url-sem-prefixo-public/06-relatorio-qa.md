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
