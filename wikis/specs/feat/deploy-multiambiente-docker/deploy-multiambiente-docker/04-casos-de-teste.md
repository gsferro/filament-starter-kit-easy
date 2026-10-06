# Casos de Teste — Deploy com Docker: vários ambientes não produtivos no mesmo servidor, atrás do Traefik

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths e artefatos) · Decisões: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`: não exigida)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação, que
> ainda não existe. O código lido foi o **atual**, e só para herdar convenção de teste (helpers de
> `tests/Pest.php`, `MysqlNoDockerTest`, `DeployDockerLocalTest`, `BooleanoDoEnvTest`,
> `UrlSemPrefixoPublicTest`) e os nomes que já existem (`docker-compose.yml`, `Dockerfile.laravel`,
> `.env.docker`, `.env.example`, `deploy_docker_local.sh`, `bootstrap/app.php`,
> `App\Console\Commands\KitUpdate::CAMINHOS_DO_KIT`).
> Derivação feita em sub-agente; perguntas renumeradas pela sessão e levadas ao `00`;
> `## Costuras de Teste` **confirmadas** pela sessão. **Revisão adversarial feita**: 2 rodadas
> (`fw-adversario-ct`, cego ao PRD e ao código), **36 + 33 achados, todos fechados** (a 2ª trouxe
> também a Q12, implementada como P-14) — destino de cada um em `## Revisão Adversarial`. **Teto de
> 2 rodadas atingido**: este v3 fecha a 2ª sem uma 3ª.
> **v4 — premissas do step 9** (revisão do diff: RD-01…RD-08 do `fw-revisor-diff` e CR-01…CR-09 do
> passe genérico): P-18…P-25 novas, P-02 alterada, P-13 reescrita, P-17 substituída por P-24. Os CT
> que elas pedem nascem aqui, **antes** da correção (`SKILL.md` §Quando Invocar), com origem `P-nn`;
> destino de cada achado em `## Revisão Adversarial`, seção *Step 9*. A costura G6 passa a
> `refreshApplication()` (sessão, 2026-10-05); G7 é nova e volta à confirmação.

## Perfil de Derivação

| Área | O que é | P | I | P×I | Perfil |
|---|---|---|---|---|---|
| **A — opt-in e default intocado** | semântica de carga e merge do Compose (override automático, `ports` concatenados), três vias de entrega (git, `create-project`, `kit:update`) | 3 | 3 | **9** | completo |
| **B — contrato Traefik do exemplo** | interpolação de label em lista, rede externa, nomes globais no Traefik, duas rotas do Reverb | 3 | 2 | **6** | padrão |
| **C — `VITE_REVERB_*` no build** | `ARG` por estágio do Dockerfile, args em seis serviços que compartilham o estágio `assets`; o bundle do kit sem Echo | 2 | 2 | **4** | padrão |
| **D — proxies confiáveis** | parse de chave do `.env`, validação de IP/CIDR, aviso no log do item descartado, caminho `config` → boot e fronteira de confiança de `X-Forwarded-*` | 2 | 3 | **6** | padrão (R10 escalada a completo) |
| **E — chaves de `.env` e portas entre ambientes** | arquivos de sugestão copiados pelo usuário; colisão de porta no host | 2 | 2 | **4** | padrão |
| **F — documentação, README e site** | prosa pt/en, sidebar e stubs gerados | 2 | 2 | **4** | padrão |

**Impacto 3 em A**: errar aqui muda o deploy de **todo** projeto que já usa o kit, em silêncio
(RQ-04: "como roda outros projetos"). **Impacto 3 em D**: `TRUSTED_PROXIES` decide de quem a
aplicação aceita `X-Forwarded-For/Proto/Host` — confiar por engano é IP forjado no log de
autenticação e no rate limit. **Escalada**: R10 usa EP exaustiva porque a regra é a fronteira de
confiança e cada partição tem modo de falha próprio.

**Revisão adversarial: feita** (obrigatória por Impacto 3 em A e D) — 2 rodadas, 36 + 33 achados,
todos fechados. Os mutantes trazidos por elas (M83…M127 da 1ª, M129…M164 da 2ª) **não contam para o
teto** do perfil (`SKILL.md` §Passo 6, item 1); M128 nasceu de decisão da sessão (Q10, D6 do `01`) e
também fica fora. Os mutantes do step 9 (M165…M200) ficam fora pelo mesmo motivo: são achado medido da
revisão do diff, não enchimento. Onde a tabela de uma regra passa do teto, a linha `Estouro do teto`
abaixo dela registra a origem.

- Técnicas aplicadas: EP (partição exaustiva em R10; por arquivo × chave em R13), tabela de decisão (R11/R12: chamador × valor de `TRUSTED_PROXIES` × saídas), rastreio de efeito como diff profundo de configuração (R3) e no canal de log (R21: aconteceu / não aconteceu com o canal de pé / uma vez por item), golden de configuração efetiva com procedência fixa (R1, CT-34: cópia textual do `docker-compose.yml` da tag `v0.44.0`, os dois lados gerados pelo mesmo CLI no teste, todos os profiles, comparado inteiro — P-24), canário de CI contra `skip` silencioso (R1, CT-46), contrato entre arquivos (R8, R13), regex sobre a regra **interpolada pelo próprio Compose** (R7), âncora na mesma frase sem negação (R16, R17), BVA não se aplica (nenhuma faixa ordenável: portas são identidades, não faixas)
- Cenários: 50 · Regras: 21 · Mutantes previstos: 200 · Sem matador: 2 (M131 → L11, M183 → L9)
<!-- derivado por grep -c; recalcular a cada cenário novo -->

### Divergências declaradas (skill × rule do projeto)

- `.ai/rules/testes.md` vence: todo caso que lê `docs/`, `README*.md`, `site/` ou roda `git` sobre o
  índice leva `->skip(fn (): bool => ! naArvoreDoKit(), …)`; asserção de ausência roda só sobre
  texto sem comentário; `toContain()` nunca recebe mensagem (ausência por
  `assertStringNotContainsString`).
- A mensagem de todo `skip` fora da árvore **não contém a palavra `docs`** (decisão da sessão,
  2026-10-05) — vale para os casos novos do v4 (CT-34, CT-46 e a linha textual de CT-48) e para os
  que já pulavam.
- A skill não tem costura para "leitura de arquivo de infra com a aplicação de pé" nem para "CLI
  externo": os dois ficam em `Pest feature HTTP` (teste em `tests/Kit` com `TestCase`), que é o
  padrão já usado por `MysqlNoDockerTest` e `DeployDockerLocalTest`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | `docker/traefik/docker-compose.override.yml` (exemplo), `.gitignore`, `Dockerfile.laravel` (estágio `assets`), `.env.example`, `.env.docker`, `config/kit.php` (chave `kit.proxies_confiaveis` — P-02), o boot de `app/Providers/KitServiceProvider.php` (aplica e avisa — P-02, P-18), `app/Support/ProxiesConfiaveis.php`, `App\Console\Commands\KitUpdate::CAMINHOS_DO_KIT` (ganha `.env.docker` — P-23), docs pt/en, README pt/en, `CHANGELOG.md`, `site/sidebar.json`, stubs, o golden textual `tests/Kit/fixtures/docker-compose.v0.44.0.yml` (cópia do base da `v0.44.0` — P-24). Intocados: `docker-compose.yml`, `deploy_docker_local.sh`, `docker/nginx/nginx.conf`, `bootstrap/app.php` (P-02: a chave não é lida lá), `resources/js`, `package.json`, `config/filament.php` (se existir) | CT-01…CT-07, CT-17, CT-27, CT-28, CT-34, CT-35, CT-36, CT-37, CT-43, CT-47, CT-48 |
| F | (1) ligar o Traefik por cópia do exemplo; (2) não ligar nada sem a cópia; (3) parse de `TRUSTED_PROXIES`; (4) honrar `X-Forwarded-*` só de proxy confiável; (5) passar `VITE_REVERB_*` ao build, só as definidas; (6) ligar a rota do Reverb pelo Traefik descomentando o bloco; (7) ensinar o procedimento; (8) descartar item que não é IP/CIDR ou é coringa, avisando no log (P-18); (9) aplicar a chave pelo `config` no boot (P-02) | CT-01, CT-06, CT-08…CT-22, CT-33, CT-38, CT-44, CT-45, CT-48, CT-49, CT-50 |
| D | valores de `.env` (ausente, vazio, só espaços, lista, `*`, tokens do Symfony — inclusive `private_ranges` —, não-string, item que não é IP/CIDR: `172.18.0.0/16x`, `17x.18.0.1`, `/33`, `/129`; IPv6; CIDR com bits de host); três `.env` (dev/teste/homol); nomes de projeto; hostnames; `REVERB_APP_KEY`/`REVERB_APP_ID` ausente × vazia; `VITE_REVERB_*` ausente × definida; a matriz de portas do requisito, literal | CT-08, CT-11, CT-12, CT-20, CT-21, CT-22, CT-25, CT-26, CT-40, CT-44, CT-45, CT-49 |
| I | `docker compose` (carga automática do override, `--profile app`), `docker build` (args), request HTTP com cabeçalhos `X-Forwarded-*`, `git` (ignore/índice; `git ls-files` dos arquivos de Compose **rastreados** — CR-08), `kit:update` (`CAMINHOS_DO_KIT`), `create-project` (`.gitattributes`), Traefik (docker provider por labels), o runner do CI (variável `CI`), o canal de log `configuracoes` (P-18), `config()` (P-02) | CT-01, CT-03, CT-04, CT-05, CT-21, CT-22, CT-36, CT-37, CT-46, CT-48, CT-50 |
| P | Docker Compose v5.5.1 local × v2.x do `ubuntu-latest` (comportamento de label em lista medido só na v5.5.1); Windows sem CLI → `skip`; ambiente do processo PHP **vaza** chaves do `.env` do desenvolvedor para o subprocesso `docker compose` (Laravel escreve no `putenv`, a `Process` herda) — tratado no Setup Global; Vite trata `VITE_*` vazia de `process.env` como **definida** (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5705`); `config:cache` congela `kit.proxies_confiaveis` no valor da hora do cache, como toda chave (P-13 reescrita); o closure de `withMiddleware()` roda **antes** de o `.env` ser carregado (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:withMiddleware:287`, RD-01) — por isso a chave saiu do `bootstrap/app.php` (P-02); o Symfony **não valida** IP/CIDR (`vendor/symfony/http-foundation/IpUtils.php:checkIp4:87`) e lê `private_ranges` como `PRIVATE_SUBNETS` (`vendor/symfony/http-foundation/Request.php:private_ranges:658`); `refreshApplication()` limpa as fachadas **antes** do boot dos providers (`vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/RegisterFacades.php:bootstrap:18`) — spy de `Log` posto antes do refresh não vê o boot (G7); o JSON do `docker compose config` muda de forma entre versões do Compose (CR-07) — os dois lados do golden passam pelo mesmo CLI (P-24); o `docker compose config` devolve no JSON os extension fields de topo (`x-*`); `build.args` em lista sem valor **omite** a chave ausente do `.env` (P-15, medido); runner do CI sem o CLI faria toda a G2 virar `skip` verde | CT-06, CT-11, CT-19, CT-20, CT-21, CT-34, CT-41, CT-45, CT-46, CT-48, CT-50, Setup Global |
| O | três checkouts no mesmo servidor, Opção A; quem não usa Traefik (todo projeto que já existe) copiando `.env.docker` como sempre fez; operador que esquece `TRAEFIK_HOST` ou o deixa vazio; Reverb pelo Traefik ou por porta; operador que descomenta o bloco do Reverb sem `REVERB_APP_KEY`/`REVERB_APP_ID`; outro projeto do servidor com container `app`/`reverb` na rede compartilhada (P-14, P-25) e confiado pelo `*` (P-21, L7); checkout que seguiu a página e tem a cópia ativa na raiz, ignorada pelo git (CR-08); projeto instalado que editou o base de propósito (P-24 — CT-34 e CT-46 pulam fora da árvore); servidor que liga o profile `ai`/`mail` com a matriz do requisito (8080 do llama — P-22) | CT-01, CT-12, CT-14, CT-23, CT-25, CT-26, CT-30, CT-34, CT-36, CT-39, CT-41, CT-44, CT-46, CT-47 |
| T | não se aplica a relógio; **estado estático entre casos**: `TrustProxies::$alwaysTrustProxies` é estático do processo — o `TrustProxies::at()` do boot o grava a cada `refreshApplication()`, o `tearDown` do framework chama `TrustProxies::flushState()` (`vendor/laravel/framework/src/Illuminate/Foundation/Testing/Concerns/InteractsWithTestCaseLifecycle.php:tearDownTheTestEnvironment:126`), e o env alterado no caso é restaurado pelo helper; **ordem do caso em G6**: env → refresh → rota e `config()` → request (o refresh descarta rotas e configuração do app anterior) | CT-21, CT-22, CT-48, CT-50 (independência) |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Sem a cópia ativa do override, a stack do kit não muda | A (completo) | RQ-04, RQ-05, RQ-09, RQ-11, P-08, P-24 | EP por arquivo-base (rastreado — CR-08) + execução do Compose + golden da configuração efetiva, os dois lados pelo mesmo CLI + canário de CI | CT-01, CT-02, CT-03, CT-33, CT-34, CT-35, CT-36, CT-46 |
| R2 — O exemplo chega a quem instala; a cópia ativa nunca entra no git nem no `kit:update` | A (completo) | P-03, RQ-11, P-23 | EP por via de entrega | CT-04, CT-05, CT-37 |
| R3 — Ligado o override, só muda o que o Traefik e o build precisam | A (completo) | RQ-04, RQ-07, RQ-10, RQ-11, RQ-14, P-06 | rastreio de efeito (diff profundo de configuração) | CT-06, CT-07 |
| R4 — O `nginx` entra na rede externa sem sair da própria, e a rede é coerente | B (padrão) | RQ-07, RQ-08, P-04 | EP (rede ausente × vazia × definida) | CT-08, CT-09 |
| R5 — Labels do docker provider com os valores do requisito | B (padrão) | RQ-07, RQ-09 | EP (valor exato por label) | CT-10 |
| R6 — Router/service únicos por ambiente; hostname nunca fixo | B (padrão) | RQ-08, RQ-17, P-04, P-05 | EP (nome do projeto) + estado de erro com saída | CT-11, CT-12, CT-13 |
| R7 — Reverb: duas rotas, nenhuma imposta; a do Traefik não captura painel | B (padrão) | RQ-12, RQ-04, P-04, P-10, P-16; P-21 (só a linha do cabeçalho de CT-15 — RD-08) | EP + invariante + regex sobre a regra interpolada pelo Compose + estado de erro com saída | CT-14, CT-15, CT-16, CT-44 |
| R8 — Os quatro `VITE_REVERB_*` chegam ao `npm run build` de toda imagem | C (padrão) | RQ-14, P-15 | contrato estático + contrato entre serviços | CT-17, CT-18 |
| R9 — Sem build-arg, o build é o de hoje | C (padrão) | RQ-02, RQ-04, P-01, P-12, P-15 | EP (declaração do `ARG`, origem do valor, chave ausente × definida no `.env`) | CT-19, CT-38, CT-45 |
| R10 — Interpretação de `TRUSTED_PROXIES` | D (escalada a completo) | P-02, P-11, P-18, P-19 | EP exaustiva | CT-20, CT-49 |
| R11 — Ausente, vazia ou lista sem o chamador: `X-Forwarded-*` ignorados | D (padrão) | P-02, P-11, P-18, P-19, RQ-05 (invariante, vale com qualquer resposta a Q1) | tabela de decisão | CT-21 |
| R12 — `*` ou lista com o chamador: a aplicação honra `X-Forwarded-*` | D (padrão) | P-02 (alterada: lida pelo `config`, aplicada no boot), P-13, P-18, RQ-09 | tabela de decisão + leitura do caminho `config` → boot | CT-22, CT-48 |
| R13 — Chaves novas só como linha comentada; o `.env.docker` oferece o que o exemplo consome; a sugestão não colide | E (padrão) | RQ-02, RQ-05, RQ-06, RQ-10, RQ-16, P-02, P-04, P-09 | EP arquivo × chave + contrato | CT-23, CT-24, CT-25, CT-39, CT-40 |
| R14 — Três ambientes com a matriz do requisito não disputam porta nem nome | E (padrão) | RQ-06, RQ-16, P-09 | valor literal do requisito | CT-26 |
| R15 — A página existe nos dois idiomas e é alcançável; o CHANGELOG registra | F (padrão) | RQ-01, RQ-03 | EP por idioma | CT-27, CT-28 |
| R16 — A página ensina o mecanismo central, o encaixe no Traefik e o passo a passo | F (padrão) | RQ-06, RQ-07, RQ-08, RQ-09, RQ-11, P-13, P-14, P-21, P-25 | EP por âncora (na mesma frase, sem negação) + ausência da instrução que P-13 reescrita desmente | CT-29, CT-41, CT-47 |
| R17 — A página cobre portas, matriz, o build do Reverb e as duas rotas | F (padrão) | RQ-10, RQ-12, RQ-14, RQ-16, P-06, P-09, P-20, P-22 | valor literal do requisito + âncora com negação exigida (P-20) | CT-30, CT-42 |
| R18 — A página apresenta as opções A–D e as armadilhas | F (padrão) | RQ-13, RQ-15, P-07, P-22 | EP por âncora | CT-31 |
| R19 — pt e en são espelho | F (padrão) | RQ-01 | contrato entre arquivos | CT-32 |
| R20 — O bundle do kit continua sem Echo | C (padrão) | P-01, RQ-04 | EP por arquivo (ausência no texto ativo) | CT-43 |
| R21 — O item descartado vai para o log, nunca para o Symfony | D (padrão) | P-18 | rastreio de efeito (canal `configuracoes`, nível `warning`, um por item) | CT-50 (o "nunca para o Symfony" no HTTP: linhas P-18 de CT-21 e CT-22) |

- **RQ-02** (revisar antes de transplantar) — cláusula de **processo**, cuja evidência primária é
  `## Premissas` do `00` e `## Auditoria Pré-Implementação` do `03`. Os achados da revisão viraram
  regras com cenário: R9 (P-12, contra o `ENV` vazio que o documento de origem sugeriria) e R13
  (P-09, contra o "opcional" da matriz); também CT-16 (P-10) e CT-26.
- **RQ-03** (branch, PR, tag, release) — processo; branch, PR e tag são conferidos pelo quality gate
  e pelo checklist de release. O registro da entrega no `CHANGELOG.md` tem cenário (CT-28, R15).
- **RQ-09 / P-02 — Q1 aberta, não bloqueante no `00`**: R11 é o invariante (ausente = hoje) e vale com
  qualquer resposta; R12 é a direção de Q1, implementada como P-02 pela mesma regra de Q6, Q8 e Q11 (falha fechado: ausente = hoje) — não é `@premissa` *(decisão da sessão, 2026-10-05)*.
- **RQ-10, RQ-12 e RQ-16 são fechadas no `00`**: Q6, Q8 e Q11 estão registradas como perguntas ao
  solicitante **já implementadas pela direção que falha fechado** (P-09, P-10 e a recomendação de
  Q11, esta última com âncora de configuração em CT-25). Por isso têm regra e cenário (R7, R13, R14,
  R17) e nenhuma linha "aberta" aqui.
- **Q12 — aberta no `00`, não bloqueante**: implementada como P-14 (documentar e confirmar com o
  DevOps; só o `nginx` entra na rede compartilhada). Cenário: CT-47 (R16, a página) e CT-09 (R4, só o
  nginx na rede externa); o runtime com outro `app` na mesma rede é a lacuna L10.
- **Step 9 (v4)** — toda `P-nn` vigente nova gerou regra ou linha de cenário (tabela P-nn → CT em
  `## Revisão Adversarial`, seção *Step 9*). **P-17 está substituída por P-24**: nenhum cenário a cita
  como origem. P-02 alterada e P-13 reescrita mudaram o **oráculo** de CT-21/CT-22 (costura) e de
  CT-41 (o aviso de cache passa a ser o oposto do de v3).

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Leitura estática de infra | R1 (CT-02, CT-03, CT-35, CT-36 — lista dos arquivos rastreados por `git ls-files` na árvore, CR-08), R2 (CT-05, CT-37), R3 (CT-07), R6 (CT-13), R7 (CT-15), R8 (CT-17), R9 (CT-19), R13 (CT-23, CT-24), R20 (CT-43) | Pest feature HTTP | existente — padrão de `tests/Kit/MysqlNoDockerTest.php` (`blocoDoServico()`, filtro de comentário) e `tests/Kit/DeployDockerLocalTest.php`; arquivo novo `tests/Kit/DeployMultiambienteDockerTest.php` | o `Então` afirma texto ativo de arquivo entregue; roda sem Docker (inclusive no Windows sem CLI) | sessão, 2026-10-05 |
| G2 — `docker compose config` em pasta temporária | R1 (CT-01, CT-33, CT-34, CT-46), R3 (CT-06), R4, R5, R6 (CT-11, CT-12), R7 (CT-14, CT-16, CT-44), R8 (CT-18), R9 (CT-38, CT-45), R13 (CT-25, CT-39, CT-40), R14 | Pest feature HTTP | **nova** — nenhum teste do kit executa o CLI do Compose; interpolação de label, merge de `ports` e carga automática do override só o Compose resolve. Mesmo arquivo de G1. `skip` quando `docker compose version` falha — **menos o canário CT-46**, que com `CI=true` falha em vez de pular | o observável é a configuração **efetiva** que o Compose montaria; regex sobre YAML não vê interpolação nem merge | sessão, 2026-10-05 — o CLI está no Windows local (v5.5.1) e no `ubuntu-latest` |
| G3 — Índice e ignore do git | R2 (CT-04) | Pest feature HTTP | existente — CT de bit de execução de `DeployDockerLocalTest.php` (`git ls-files` na árvore) | o `Então` é estado do repositório; só existe na árvore do kit → `skip` fora dela | sessão, 2026-10-05 |
| G4 — Documentação, README e site | R15–R19 (CT-27…CT-32, CT-41, CT-42, CT-47) | Pest feature HTTP | existente — `paginasDoSite()`, `secoesDoMarkdown()`, `naArvoreDoKit()` de `tests/Pest.php`; mesmo arquivo de G1 | texto entregue; `docs/` e `site/` são `export-ignore` → `skip` fora da árvore | sessão, 2026-10-05 |
| G5 — Parse de `TRUSTED_PROXIES` | R10 (CT-20, CT-49) | unit de regra | existente — forma de `tests/Kit/BooleanoDoEnvTest.php` (dataset sobre função pura de `app/Support`); arquivo novo `tests/Kit/ProxiesConfiaveisTest.php` | o `Então` é valor calculado | sessão, 2026-10-05 |
| G6 — Request pela configuração real de proxies | R11 (CT-21), R12 (CT-22, CT-48) | Pest feature HTTP | **nova** — fixar o env nas três formas (`putenv`, `$_ENV`, `$_SERVER`) e **`refreshApplication()`**: o `config/kit.php` relê a chave e o boot do `KitServiceProvider` a aplica (`TrustProxies::at()` — P-02 alterada). Rota de teste **sem banco** e `config(['app.url'])` declaradas **depois** do refresh. O `app()->forgetInstance(Kernel)` de v3 refaz só o `afterResolving` de `withMiddleware` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:withMiddleware:287`), não o `boot()` do provider — deixa de servir. Arquivo `tests/Kit/ProxiesConfiaveisTest.php` | o requisito afirma comportamento HTTP (esquema, host, URL e IP vistos pela aplicação), não a chamada a `TrustProxies::at()` | sessão, 2026-10-05 — **`refreshApplication()`** (substitui a confirmação de v3, que era `forgetInstance`) |
| G7 — Aviso de descarte no boot | R21 (CT-50) | Pest feature HTTP | **nova** — spy do canal `configuracoes` na forma de `espiarConfiguracoes()` (`tests/Pest.php`). Armadilha: `refreshApplication()` limpa as fachadas antes de bootar os providers (`vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/RegisterFacades.php:bootstrap:18`), então um `Log::spy()` posto **antes** do refresh não vê o aviso. Proposta: env fixado → `refreshApplication()` → spy → re-executar o passo de boot que aplica a chave, pela forma de `alinharConfiguracoesDoKit()` (`tests/Pest.php`: `Closure::call` sobre o provider); o que se conta é só a re-execução. Mesmo arquivo de G6 | o `Então` é efeito colateral em canal de log nomeado pela premissa (P-18) | sessão, 2026-10-05 — confirmada **com** a armadilha: env fixado → `refreshApplication()` → spy do canal `configuracoes` → `(fn () => $this->confiarNosProxiesDoEnv())->call($provider)` sobre o provider registrado (re-execução contada) — o `boot()` inteiro não serve: re-registra os health checks do spatie/laravel-health e estoura `DuplicateCheckNamesFound` (medido pelo executor); é a forma de `alinharConfiguracoesDoKit()` |

Nenhuma costura `browser` → sem `05`.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome e assinatura `ProxiesConfiaveis::doEnv(mixed): array\|string\|null` | escolha de implementação | detalhe do cenário (CT-20) |
| nome `ProxiesConfiaveis::descartados(mixed): array` | escolha de implementação (decidida pela sessão no step 9) | detalhe do cenário (CT-49) |
| `config('kit.proxies_confiaveis')` **cru**, sem interpretar no `config/kit.php` | mecanismo, decidido pela sessão (RD-01): a interpretação (R10) e o aviso (R21) vivem no boot; aceito porque com `config:cache` o valor cacheado é o do `.env`, como toda chave (P-13) | linha `' * '` → `' * '` de CT-48 |
| prefixo `[KitServiceProvider@confiarNosProxiesDoEnv]` da mensagem | o `00` não fixa texto; é a convenção de log do projeto, fixada pela sessão | CT-50 afirma o prefixo e o item no contexto, não o resto da frase |
| rede `ia-compartilhada` da receita da Opção D | nome do plano; P-22 exige só uma rede própria que não é a do Traefik | CT-31 afirma "rede que não é a do Traefik" |
| retorno `null` para não-string (`true`, `1`) | o `00` não decide; aceito como **falha fechado**, coerente com P-02 ("ausente = nenhum proxy") | linha do Esquema de CT-20, marcada |
| `' * '` → `'*'` | aceito: o operador escreveu `*` (P-02); devolver `[' * ']`/`['*']` faz o Laravel confiar em ninguém sem aviso | linha do Esquema de CT-20 e de CT-22 |
| `trustProxies(...)` **antes** do `append(RaizDeUrlSemPublic)` no `bootstrap/app.php` | obsoleto desde P-02 alterada (step 9): o `bootstrap/app.php` não lê mais a chave | recusado; CT-48 afirma que o texto do `bootstrap/app.php` não contém `trustProxies` |
| `# TRUSTED_PROXIES=` "logo abaixo de `APP_URL`" no `.env.example` | posição cosmética | recusado; CT-23 afirma só linha comentada presente e nenhuma linha ativa |
| âncora `x-vite-args` e "os seis serviços" listados | mecanismo; a lista é derivada do **base** (todo serviço com `build:`), não do plano | CT-18 enumera do base |
| `${TRAEFIK_HOST:?…}` — texto da mensagem | o requisito não fixa texto | CT-12 afirma só código ≠ 0 e o nome da chave no erro |
| `${REVERB_APP_KEY:?…}` / `${REVERB_APP_ID:?…}` — texto da mensagem | o requisito não fixa texto; P-16 fixa só a obrigatoriedade | CT-44 afirma só código ≠ 0 e o nome da chave no erro |
| `x-vite-args` devolvido no JSON do `docker compose config` | extension field de topo é mecanismo, não efeito: nenhum serviço o lê pelo nome | CT-06 ignora as chaves de topo que começam por `x-` |
| regra do Reverb `PathPrefix(/app) \|\| PathPrefix(/apps)` | **contraria RQ-04**: o painel do kit vive em `/app` (`app/Providers/Filament/AppPanelProvider.php:path:79`) e a regra mais longa ganha prioridade no Traefik | invariante CT-16 + P-10 |
| `ARG VITE_REVERB_*=` com `ENV` promovendo os quatro | **contraria RQ-04**: com o `ENV` vazio o Vite passa a ver `''` onde hoje vê `undefined` (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5705`) | CT-19 + P-12 |
| `# FORWARD_APP_PORT=127.0.0.1:8090` no bloco do `.env.docker` | valor do plano; o requisito (RQ-16, nota ²) diz que 8090 colide com o Reverb publicado no default | CT-25 afirma a não-colisão, sem fixar o valor |
| `sidebar: order: 6`, texto exato da frase do índice de Operação | cosmético | recusado; CT-27 afirma o link e o slug |
| URL do README `https://gsferro.github.io/filament-starter-kit-easy/{pt,en}/operacao/deploy-docker-multiambiente.html` | o domínio é do site existente; o oráculo é o **sufixo** `/{idioma}/operacao/deploy-docker-multiambiente.html` casar com uma página que existe | CT-27 |

## Setup Global

### Personas
- Não há persona: nenhuma rota, painel ou autorização nova. O "ator" dos cenários é **o operador do servidor** (quem copia arquivos e edita o `.env`) ou **o Traefik** (quem faz o request com `X-Forwarded-*`).

### Fixtures — G2 (`docker compose config`)
- Pasta temporária por caso (`sys_get_temp_dir()` + id único; apagada no `afterEach`), com: cópia do `docker-compose.yml` do kit; `.env` escrito pelo caso (o base declara `env_file: .env`, sem ele o Compose recusa); e, conforme o `Dado`, o exemplo copiado **para a raiz** como `docker-compose.override.yml` ou **para `docker/traefik/`** no mesmo caminho do kit. Em CT-16 e CT-44, a cópia da raiz tem as linhas entre `# >>> reverb-traefik` e `# <<< reverb-traefik` **descomentadas** (o `# ` inicial removido) — os marcadores são a única âncora; o teste não adivinha onde o bloco começa.
- **Arquivos de Compose da raiz (CR-08)**: na árvore do kit, os que `git ls-files 'docker-compose*' 'compose*'` lista (os **rastreados**); fora dela (sem git), os que casam `docker-compose*.y*ml` e `compose*.y*ml`, **menos** `docker-compose.override.yml`. Um `glob` cru pegava a cópia ativa ignorada pelo git de um checkout que seguiu a página e reprovava CT-01, CT-36 e CT-39 sem defeito. Helper único para os três casos.
- Comando: `docker compose --profile app config --format json`, **sem `-f`** (a carga automática do override é o que está em teste), com a pasta temporária como `cwd`. CT-34 usa `--profile '*'` (todos os profiles — P-24).
- **Armadilha de ambiente (P de SFDIPOT), obrigatória**: o Laravel escreve as chaves do `.env` do desenvolvedor no `putenv`, e a `Symfony\Component\Process\Process` herda o ambiente do PHP. Variável de shell **vence** o `.env` na interpolação do Compose — um `COMPOSE_PROJECT_NAME=starter-kit` ou `COMPOSE_FILE` herdado invalida o caso em silêncio. A `Process` recebe todo nome presente em `getenv()`, `$_ENV` e `$_SERVER` com valor `false` (remove), exceto a lista de sistema: `PATH`/`Path`, `SystemRoot`, `TEMP`, `TMP`, `HOME`, `USERPROFILE`, `APPDATA`, `LOCALAPPDATA`, `ProgramData`, `DOCKER_HOST`, `DOCKER_CONFIG`, `DOCKER_CONTEXT`.
- `skip`: `docker compose version` com código ≠ 0 → `->skip('CLI do Docker Compose ausente')`, **em todo caso de G2 menos CT-46**: o canário é um `it()` só que, com `CI=true` no ambiente, **falha** quando o CLI falta, para a G2 inteira não virar verde por `skip` num runner sem o Compose; fora do CI ele pula. CT de G2 **não** leem `docs/` — mas **CT-34 e CT-46 levam `->skip(fn (): bool => ! naArvoreDoKit(), …)`** (P-24/RD-07: no projeto instalado o base pode ter sido editado de propósito, e o caso não tem saída lá), com mensagem **sem a palavra `docs`** (ex.: `'o base do kit só é conferido na árvore do kit'`).
- Leitura do JSON: `services.<s>.labels` (mapa), `services.<s>.networks` (mapa), `services.<s>.ports` (lista com `host_ip`, `published`, `target`), `services.<s>.environment` (mapa), `services.<s>.build.args` (mapa; com a lista sem valor do exemplo, a `VITE_REVERB_*` ausente do `.env` **não aparece** — P-15), `networks.<chave>.{name,external}`, `volumes.<chave>.name`, `name` de topo. O JSON traz também as chaves de topo `x-*` do arquivo (extension fields), que nenhum caso lê.
- **Golden do base (CT-34, P-24)**: fixture versionado `tests/Kit/fixtures/docker-compose.v0.44.0.yml`, **cópia textual** do `docker-compose.yml` da tag `v0.44.0` (a anterior à branch) — nunca um JSON gerado. O caso monta **duas** pastas temporárias, cada uma com o mesmo `.env` mínimo `COMPOSE_PROJECT_NAME=starter-kit`: uma com o `docker-compose.yml` da árvore, outra com o fixture copiado como `docker-compose.yml`; roda `docker compose --profile '*' config --format json` nas duas, **com o mesmo CLI** (todos os profiles: 12 serviços). A **única** normalização é trocar o prefixo de **cada** pasta pelo mesmo `<raiz>`, em todas as grafias de separador em que ele aparece no JSON (`/`, `\` e `\\` escapado); nada mais é normalizado nem excluído; o JSON é comparado **inteiro** (diff profundo). A versão do Compose deixa de importar (CR-07): os dois lados a usam. **Sem interruptor de regeneração** no teste: regenerar é copiar o base para o fixture, ato deliberado com linha no `CHANGELOG.md`; o docblock registra a procedência (tag `v0.44.0`). O fixture JSON de v3 sai da árvore.
- Helpers ficam **locais** a `DeployMultiambienteDockerTest.php` (um consumidor só — `.ai/rules/testes.md`).

### Fixtures — G6 (request)
- Ordem do caso: (1) `comTrustedProxies()` fixa a chave nas três formas; (2) `refreshApplication()` — o `config/kit.php` relê a chave e o boot do provider a aplica; (3) rota de teste e `config(['app.url' => …])`, **depois** do refresh; (4) o request.
- Rota **de teste**, declarada no caso, fora do grupo `web` (sem sessão nem banco): devolve `request()->isSecure()`, `request()->getHost()`, `url('/x')` e `request()->ip()`.
- Request: `REMOTE_ADDR` da linha do caso (`withServerVariables`), `Host: interno.local`, `X-Forwarded-For: 203.0.113.9`, `X-Forwarded-Proto: https`, `X-Forwarded-Host: dev.exemplo.test`, `X-Forwarded-Port` da linha (443, salvo a linha 8443 de CT-22); `config(['app.url' => 'https://dev.exemplo.test'])` antes do request.
- A linha de CT-21 com `TRAEFIK_HOST` fixa também essa chave nas três formas, pelo mesmo mecanismo do helper, e a restaura no `afterEach`.
- Helper local `comTrustedProxies(?string $valor)`: `null` remove a chave das três formas; string grava nas três; restaura o anterior no `afterEach` (forma de `kitConfigCom()` em `tests/Pest.php`). O `Dado` de cada linha **afirma o valor efetivo lido** (`env('TRUSTED_PROXIES')`, que para a string `true` devolve o bool `true`) antes do request — o caso não pode medir o `.env` do desenvolvedor.

### Fixtures — G7 (log do boot)
- Env fixado como em G6 → `refreshApplication()` → `espiarConfiguracoes()` (spy do canal `configuracoes`, `tests/Pest.php`) → re-execução do passo de boot que aplica a chave (forma de `alinharConfiguracoesDoKit()`). Conta-se só o que a re-execução emite. O spy **antes** do refresh é perdido (`RegisterFacades.php:bootstrap:18`). Se a sessão preferir outro arnês (ex.: listener de `Illuminate\Log\Events\MessageLogged` registrado depois do refresh + re-execução), o oráculo de CT-50 não muda.

### Fakes
- Nenhum de fila, e-mail, notificação ou HTTP externo. Log: spy do canal `configuracoes` só em G7.

### Estratégia de DB
- `RefreshDatabase` global de `tests/Kit` (herdado, irrelevante: nenhum caso toca o banco). Com G6/G7 em `refreshApplication()`, a rota de teste **não** pode tocar banco (`RegistroAbertoTest.php` registra que o `:memory:` morre no refresh).

---

## Regra R1 — Sem a cópia ativa do override, a stack do kit não muda

> `RQ-04`, `RQ-05`, `RQ-09`, `RQ-11`, `P-08`, `P-24` · perfil **completo** · técnica: **EP por arquivo-base** (os rastreados — CR-08) + execução real do Compose + **golden** da configuração efetiva, com procedência na tag `v0.44.0` e os dois lados gerados pelo mesmo CLI (CT-34) + **canário de CI** (CT-46)

```gherkin
# language: pt
Funcionalidade: Deploy multiambiente atrás do Traefik é opt-in

  Regra: sem a cópia ativa do override, nada do Traefik entra na configuração

    Cenário: [CT-01] o exemplo em docker/traefik não é carregado pelo Compose
      Dado uma pasta com os arquivos de Compose rastreados da raiz do kit e o exemplo em "docker/traefik/docker-compose.override.yml"
      E um .env sem COMPOSE_PROJECT_NAME, TRAEFIK_HOST, TRAEFIK_REDE e FORWARD_APP_PORT
      Quando o operador roda "docker compose --profile app config" na pasta
      Então nenhum serviço tem label que comece com "traefik."
      E a configuração não declara rede de topo além da "default" do projeto
      E o nginx publica a porta 8000 do host para a 80 do container

    Esquema do Cenário: [CT-02] os arquivos-base continuam sem nada do Traefik nas linhas ativas
      Dado o arquivo "<arquivo>" do kit, sem as linhas de comentário
      Quando o teste procura "<proibido>"
      Então não encontra
      E o texto cru ainda tem a linha que casa "<preservado>"

      Exemplos:
        | arquivo                  | proibido                                         | preservado                                     | # partição            |
        | docker-compose.yml       | ^\s*networks:  ·  ^\s*labels:  ·  traefik         | ^\s*- '\$\{FORWARD_APP_PORT:-8000\}:80'$       | compose base          |
        | docker/nginx/nginx.conf  | listen 443  ·  ssl_certificate                    | ^\s*listen 80;$                                | TLS termina no Traefik |

    Cenário: [CT-03] o script de deploy chama o Compose sem fixar arquivo, projeto, diretório nem env-file
      Dado o "deploy_docker_local.sh", sem as linhas de comentário
      Quando o teste lê cada linha que casa "docker[ -]compose"
      Então nenhuma passa "-f", "--file", "-p", "--project-name", "--project-directory" nem "--env-file"
      E o script não define COMPOSE_FILE, COMPOSE_PROJECT_NAME nem COMPOSE_ENV_FILES
      E ao menos uma linha invoca "docker compose" com "up -d --build"
      E a linha do health check obtém a porta de "docker compose --profile app port nginx 80" e não interpola "${FORWARD_APP_PORT"

    Cenário: [CT-33] sem o override e sem a chave no .env, nenhum serviço recebe TRUSTED_PROXIES
      Dado uma pasta só com o docker-compose.yml do kit e um .env sem TRUSTED_PROXIES
      Quando o operador roda "docker compose --profile app config" na pasta
      Então nenhum serviço tem a chave "TRUSTED_PROXIES" em "environment"

    Cenário: [CT-34] a configuração efetiva do base é, inteira, a do base da tag anterior
      Dado a árvore do kit
      E uma pasta com o docker-compose.yml do kit e outra com "tests/Kit/fixtures/docker-compose.v0.44.0.yml" copiado como docker-compose.yml, as duas com um .env só com COMPOSE_PROJECT_NAME "starter-kit"
      Quando o operador roda "docker compose --profile '*' config --format json" nas duas pastas, com o mesmo CLI
      Então as duas saídas, cada uma com o prefixo da própria pasta trocado por "<raiz>" em todas as grafias de separador, são iguais em profundidade, folha a folha, sem chave excluída
      E a saída do fixture tem os 12 serviços de todos os profiles, inclusive mysql, llamacpp, llamacpp-embeddings e mailpit

    Cenário: [CT-35] o nginx.conf não passa a honrar cabeçalho de proxy por conta própria
      Dado o "docker/nginx/nginx.conf" do kit, sem as linhas de comentário
      Quando o teste procura diretivas de proxy
      Então nenhuma linha casa "fastcgi_param\s+HTTPS\b"
      E nenhuma linha casa "(?i)x[_-]forwarded"
      E o texto não contém "set_real_ip_from" nem "real_ip_header"

    Cenário: [CT-36] a raiz do kit entrega um arquivo de Compose só, que lê o .env literal, e nenhum Compose de infra
      Dado os arquivos de Compose da raiz: na árvore do kit, os que "git ls-files 'docker-compose*' 'compose*'" lista; fora dela, os que casam "compose*.y*ml" e "docker-compose*.y*ml", menos "docker-compose.override.yml"
      Quando o teste lê essa lista, o "env_file" do docker-compose.yml e procura "*infra*.y*ml" na árvore
      Então o único arquivo da lista é "docker-compose.yml"
      E app, queue, scheduler, reverb e pulse declaram "env_file: .env" literal, sem interpolação
      E nenhum arquivo do kit fora de "node_modules", "vendor" e "site/" casa "*infra*.y*ml"

    Cenário: [CT-46] no CI, a falta do CLI do Compose reprova em vez de pular
      Dado a árvore do kit e a variável de ambiente CI igual a "true"
      Quando o teste roda "docker compose version"
      Então o comando sai com código 0
      E com o CLI ausente o caso falha com a mensagem "CLI do Docker Compose ausente no CI" — nunca chama skip
```

`[CT-01]` copia para a pasta **os arquivos de Compose rastreados da raiz do kit** (Setup Global, CR-08:
`git ls-files 'docker-compose*' 'compose*'` na árvore; fora dela, o `glob` menos
`docker-compose.override.yml`) — não só o `docker-compose.yml` — para que um override **commitado** na
raiz por engano seja carregado e reprove o caso. A cópia ativa **ignorada** de um checkout que seguiu a
página não entra: o `glob` cru de v3 a pegava e reprovava o caso sem defeito. `[CT-36]` é o par
estático (sem CLI) dessa mesma proteção, com a mesma lista, e `[CT-39]` também a usa.
`[CT-34]` leva `skip` sem CLI do Compose **e fora da árvore do kit** (P-24/RD-07: no projeto instalado
o base pode ter sido editado de propósito, e o caso não teria saída), com mensagem sem a palavra
`docs`. O fixture é **cópia textual** do `docker-compose.yml` da `v0.44.0` (P-24), não JSON gerado: os
dois lados passam pelo mesmo CLI no mesmo momento, e a versão do Compose do CI deixa de importar
(CR-07 — forma de `null`, `required`, durações). Regenerar é copiar o base para o fixture, ato
deliberado com linha no `CHANGELOG.md`; **não há** interruptor de regeneração no teste — um interruptor
deixaria regenerar a partir da árvore da branch, e o golden se certificaria sozinho (ADV2-01). A
procedência (o arquivo da tag, não o da branch) não tem prova na suíte: lacuna L11. O `Quando` é só a
execução; a normalização é parte do `Então` (ADV2-30). `[CT-46]` é o **único** caso de G2 que não pula
sem o CLI: com `CI` diferente de `"true"` ele pula (o Windows local sem Docker continua verde), e no CI
a ausência do CLI é falha — sem ele, uma troca de imagem do runner deixaria R1, R3–R7, R9, R13 e R14
verdes por `skip`. Fora da árvore do kit ele também pula (P-24/RD-07): o projeto instalado não tem
como agir sobre o runner do kit. `[CT-03]`: o health check pela porta que o Docker publicou é o que mantém
P-08 verdadeira com `FORWARD_APP_PORT=127.0.0.1:…`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o kit entrega o exemplo **na raiz** como `docker-compose.override.yml` ("para facilitar") | CT-01, CT-36 | a cópia da raiz é carregada sem `-f`: aparece label `traefik.enable` no nginx; o caso exige zero labels `traefik.` / CT-36 lista dois arquivos |
| M2 | `networks:` + `labels:` do Traefik escritos direto no `docker-compose.yml` base | CT-02 (linha compose base) | `^\s*labels:` encontrado no texto ativo; o caso exige ausência |
| M3 | default da porta do nginx muda para loopback (`127.0.0.1:8000:80`) "por segurança" | CT-02 (coluna preservado), CT-34 | a regex `- '${FORWARD_APP_PORT:-8000}:80'` não casa mais / `ports` do nginx com `host_ip` diferente do lado do fixture |
| M4 | `nginx.conf` ganha `listen 443 ssl` para "suportar HTTPS" | CT-02 (linha nginx.conf) | `listen 443` no texto ativo |
| M5 | script passa a usar `docker compose -f docker-compose.yml …` (o override nunca carrega — P-08) | CT-03 | linha ativa com `-f`; o caso exige nenhuma |
| M83 | base ganha `environment: TRUSTED_PROXIES: ${TRUSTED_PROXIES:-*}` no bloco comum dos serviços PHP "para o Traefik funcionar" (ADV-01) | CT-33 | `environment.TRUSTED_PROXIES` = `*` em `app`; o caso exige a chave ausente em todo serviço |
| M84 | base passa a publicar o `pgsql` em loopback (`127.0.0.1:${FORWARD_DB_PORT:-5432}:5432`) "porque atrás do Traefik não precisa" (ADV-02) | CT-34 | `services.pgsql.ports[0].host_ip` = `127.0.0.1`; a configuração do fixture não tem `host_ip` |
| M85 | base troca `restart: unless-stopped` por `always` ou mexe no `healthcheck` do `app` junto com a entrega (ADV-02) | CT-34 | `restart`/`healthcheck` do serviço difere do lado do fixture |
| M86 | `nginx.conf` ganha `fastcgi_param HTTPS on;` "porque o TLS termina no Traefik" — todo projeto sem Traefik passa a gerar URL `https` (ADV-06) | CT-35 | `fastcgi_param HTTPS` no texto ativo |
| M87 | o kit entrega `compose.override.yaml` ou `docker-compose.traefik.yml` na raiz (ADV-22) | CT-36 | `glob` lista um segundo arquivo além de `docker-compose.yml` |
| M88 | `env_file: ${ENV_FILE:-.env}` no base, transplantando a parametrização da Opção B (ADV-22) | CT-36 | `env_file` do `app` não é o literal `.env` |
| M89 | script passa `-p "$COMPOSE_PROJECT_NAME"` ou `--env-file .env` "para isolar o ambiente" (ADV-17) | CT-03 | linha ativa com `-p`/`--env-file`; o caso exige nenhuma |
| M90 | health check do script monta a URL com `${FORWARD_APP_PORT:-8000}` — com `127.0.0.1:8090` a URL vira `http://127.0.0.1:127.0.0.1:8090` (ADV-35) | CT-03 | linha do health check interpola `${FORWARD_APP_PORT`; o caso exige a porta vinda de `port nginx 80` |
| M129 | base muda serviço fora do profile `app` (`mailpit`, `llamacpp`, `mysql`) — o golden antigo, só com `--profile app`, não o via (ADV2-03) | CT-34 | `services.mailpit` (ou o alterado) difere do lado do fixture, gerado com `--profile '*'` |
| M130 | base muda chave que a lista antiga do CT-34 não comparava (`stop_grace_period`, `ulimits`, `logging`, `extra_hosts`, `build.args`) (ADV2-02) | CT-34 | a folha nova aparece no diff profundo do JSON inteiro |
| M131 | fixture copiado da árvore da branch, já com o base alterado — o caso compara o defeito com ele mesmo (ADV2-01) | sem matador — — (lacuna declarada L11) | — : P-24 tirou do caso a dependência da tag (CR-07); a procedência fica no docblock e no `CHANGELOG.md` |
| M132 | bind do base trocado (`./.env:/var/www/.env` → `./${ENV_FILE:-.env}:…`) e escondido pela normalização ampla de "todo path absoluto" (ADV2-01) | CT-34 | só o prefixo de cada pasta temporária é normalizado: `volumes[].source` difere do lado do fixture |
| M133 | script usa `docker-compose -f …` (hífen), `--project-directory` ou exporta `COMPOSE_ENV_FILES` — a leitura antiga só via `docker compose` com espaço (ADV2-23) | CT-03 | linha que casa `docker[ -]compose` com flag proibida / `COMPOSE_ENV_FILES` definido |
| M134 | `nginx.conf` ganha `fastcgi_param  HTTPS $https_from_proxy;` (dois espaços) ou `fastcgi_param HTTP_X_Forwarded_Proto …` — as âncoras literais antigas não casavam (ADV2-17) | CT-35 | `fastcgi_param\s+HTTPS\b` / `(?i)x[_-]forwarded` casa |
| M135 | o kit entrega `docker-compose.infra.yml` (ou `docker/infra/compose.yml`) para a Opção D — fora de escopo (ADV2-32) | CT-36 | arquivo que casa `*infra*.y*ml` fora de `node_modules`, `vendor` e `site/` |
| M136 | runner do CI sem o plugin do Compose (troca de imagem): toda a G2 pula e a suíte fica verde (ADV2-14) | CT-46 | com `CI=true`, `docker compose version` ≠ 0 reprova o caso |

Estouro do teto (completo: 6): M83…M90 — revisão adversarial (ADV-01, ADV-02, ADV-06, ADV-17, ADV-22, ADV-35); M129…M136 — 2ª rodada (ADV2-01, ADV2-02, ADV2-03, ADV2-14, ADV2-17, ADV2-23, ADV2-32).

---

## Regra R2 — O exemplo chega a quem instala; a cópia ativa nunca entra no git nem no `kit:update`

> `P-03`, `RQ-11` · perfil **completo** · técnica: **EP por via de entrega** (git, `create-project`, `kit:update`)

```gherkin
  Regra: o exemplo viaja pelas duas rotas de entrega e a cópia ativa fica fora do git e do kit:update

    Esquema do Cenário: [CT-04] o git ignora a cópia ativa e versiona o exemplo
      Dado a árvore do kit
      Quando o teste consulta o git sobre "<caminho>"
      Então "git check-ignore --no-index" responde "<ignorado>"
      E "git ls-files" lista o caminho: "<versionado>"

      Exemplos:
        | caminho                                     | ignorado | versionado | # partição      |
        | docker-compose.override.yml                 | sim      | não        | cópia ativa     |
        | docker/traefik/docker-compose.override.yml  | não      | sim        | exemplo         |

    Cenário: [CT-05] o exemplo está nas duas listas de entrega
      Dado a lista de caminhos do kit:update e o .gitattributes
      Quando o teste procura o exemplo nelas
      Então algum caminho do kit:update cobre "docker/traefik/docker-compose.override.yml" — é igual a ele ou é diretório-pai por segmento
      E nenhuma linha "export-ignore" do .gitattributes cobre esse caminho

    Cenário: [CT-37] o kit:update nunca sobrescreve a cópia ativa do servidor e leva o .env.docker
      Dado a lista de caminhos do kit:update
      Quando o teste procura nela a cópia ativa "docker-compose.override.yml" da raiz e o ".env.docker"
      Então nenhum caminho da lista cobre "docker-compose.override.yml" — igual a ele ou diretório-pai por segmento
      E a lista não tem "", "." nem "./"
      E algum caminho da lista cobre ".env.docker" — igual a ele ou diretório-pai por segmento
```

"Cobre", em CT-05 e CT-37, é por **segmento**, nunca por prefixo de string:
`$c === $alvo || str_starts_with($alvo, rtrim($c, '/').'/')`. Prefixo de string aceitaria
`docker/tra` (o `kit:update` não copia nada com ele) em CT-05 e acusaria `docker-compose` em CT-37
(ADV2-15). `[CT-04]` leva `->skip(fn (): bool => ! naArvoreDoKit(), …)`. O `--no-index` é obrigatório: sem ele o
`git check-ignore` responde "não ignorado" para arquivo já versionado, mesmo casando uma linha do
`.gitignore`. `[CT-05]` e `[CT-37]` usam `caminhosDoKit()` de `tests/Pest.php` (Reflection sobre
`App\Console\Commands\KitUpdate::CAMINHOS_DO_KIT`, privada). A última linha de `[CT-37]` é P-23 (RD-05):
CT-23, CT-24 e CT-39 leem o `.env.docker` e viajam pelo `kit:update`; o arquivo tem de viajar junto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | `.gitignore` com `docker-compose.override.yml` **sem barra** — ignora também o exemplo em `docker/traefik/` | CT-04 (linha exemplo) | `git check-ignore --no-index` responde ignorado; o caso exige "não" |
| M7 | linha do `.gitignore` esquecida: a cópia ativa de um servidor vai para o commit | CT-04 (linha cópia ativa) | `git check-ignore --no-index` responde não ignorado; o caso exige "sim" |
| M8 | `.gitignore` ignora `docker/traefik/` inteiro (a pasta "é do servidor") | CT-04 (linha exemplo) | exemplo ignorado e fora do `ls-files` |
| M9 | exemplo entregue fora de `docker/` (ex.: raiz, `docker-compose.traefik.yml`) — não viaja no `kit:update` | CT-05 | nenhum caminho de `caminhosDoKit()` é prefixo do caminho do exemplo |
| M10 | `/docker/traefik export-ignore` no `.gitattributes` | CT-05 | linha `export-ignore` cobrindo o caminho encontrada |
| M91 | `docker-compose.override.yml` sem barra no `.gitignore` **depois** de o exemplo já estar versionado — o `check-ignore` sem `--no-index` diz "não ignorado" e o defeito passa (ADV-25) | CT-04 (linha exemplo) | com `--no-index` a resposta é "ignorado"; o caso exige "não" |
| M92 | `docker-compose.override.yml` acrescentado a `CAMINHOS_DO_KIT` "para o exemplo viajar" — o `kit:update` sobrescreve o override de cada servidor (ADV-10) | CT-37 | o caminho aparece na lista; o caso exige ausência |

| M137 | `CAMINHOS_DO_KIT` com `docker/traefik/docker-compose` ou `docker/tra` — prefixo de string do exemplo, que o `kit:update` não resolve para o arquivo (ADV2-15) | CT-05 | nenhum caminho cobre o exemplo por segmento |
| M165 | `.env.docker` fora de `CAMINHOS_DO_KIT` — os casos que o leem chegam ao projeto pelo `kit:update` e o arquivo não (RD-05, P-23) | CT-37 | nenhum caminho cobre `.env.docker` |

Estouro do teto (completo: 6): M91, M92 — revisão adversarial (ADV-10, ADV-25); M137 — 2ª rodada (ADV2-15); M165 — step 9 (RD-05).

---

## Regra R3 — Ligado o override, só muda o que o Traefik e o build precisam

> `RQ-04`, `RQ-07`, `RQ-10`, `RQ-11`, `RQ-14`, `P-06` · perfil **completo** · técnica: **rastreio de efeito** (diff profundo entre a configuração sem e com o override)

```gherkin
  Regra: o override só acrescenta rede e labels ao nginx e args de build

    Cenário: [CT-06] a configuração com o override difere do base só nos caminhos permitidos
      Dado um .env com COMPOSE_PROJECT_NAME "proj-dev" e TRAEFIK_HOST "dev.exemplo.test"
      E a configuração JSON do base sozinho e a do base com o exemplo copiado na raiz, ambas com esse .env
      Quando o teste calcula o diff profundo das duas, folha a folha
      Então o diff, menos as chaves de topo que começam por "x-" e os caminhos "services.nginx.networks.<rede do Traefik>", "services.nginx.labels.traefik.*", "services.*.build.args.VITE_REVERB_*" e "networks.<rede do Traefik>", é vazio

    Cenário: [CT-07] todo serviço do exemplo existe no base
      Dado o exemplo e o docker-compose.yml do kit
      Quando o teste lê as chaves de serviço dos dois arquivos (coluna 2 sob "services:")
      Então toda chave de serviço do exemplo é também chave de serviço do base
```

`[CT-06]` compara **toda folha** das duas árvores JSON (adição, remoção e troca de valor), não uma lista
de chaves escolhidas: a chave que ninguém lembrou de listar é onde o defeito passa. As chaves de topo
`x-*` ficam fora porque o Compose (v5.5.1) devolve os extension fields do arquivo no JSON (o
`x-vite-args` do exemplo): não são serviço, rede nem volume, e o efeito delas já aparece onde são
usadas (`services.*.build.args`). `[CT-07]` é
estático (G1) e roda sem CLI — é o par barato de `[CT-06]` para o Windows sem Docker.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | override publica `80:80`/`443:443` no nginx "para o Traefik alcançar" | CT-06 | `services.nginx.ports[1]` só existe com o override (o Compose concatena — P-06); fora dos caminhos permitidos |
| M12 | override acrescenta o serviço `traefik` (fora de escopo: a stack do Traefik é do DevOps) | CT-06, CT-07 | `services.traefik` só existe de um lado; `traefik` não é chave do base |
| M13 | override fixa `container_name` no nginx | CT-06 | `services.nginx.container_name` só existe com o override |
| M14 | erro de digitação `ngnix:` no exemplo (vira serviço novo e o nginx real fica sem labels) | CT-07 | `ngnix` não é chave do base |
| M15 | override troca `command`/`environment` do nginx (ex.: `APP_URL` forçado) | CT-06 | `services.nginx.command`/`environment.APP_URL` difere |
| M16 | override declara `name:` de topo fixo | CT-06 | `name` de topo difere (`proj-dev` × fixo) |
| M93 | override acrescenta `restart: always`, `healthcheck` ou `extra_hosts` ao nginx, ou `labels` a outro serviço — chaves fora da lista que o CT-06 antigo comparava (ADV-28) | CT-06 | folha `services.nginx.restart` (ou `extra_hosts`, `services.app.labels.*`) no diff, fora dos caminhos permitidos |

Estouro do teto (completo: 6): M93 — revisão adversarial (ADV-28).

---

## Regra R4 — O `nginx` entra na rede externa sem sair da própria, e a rede é coerente

> `RQ-07`, `RQ-08`, `P-04` · perfil **padrão** · técnica: **EP** (rede ausente × vazia × definida no `.env`)

```gherkin
  Regra: o nginx fica na rede default e na rede externa do Traefik, e o label aponta para ela

    Esquema do Cenário: [CT-08] a rede externa vem do .env, com default my-network
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST "dev.exemplo.test" e TRAEFIK_REDE <valor no .env>
      Quando o operador roda "docker compose --profile app config"
      Então existe uma rede de topo com "external" verdadeiro e "name" igual a "<rede>"
      E o nginx está nas redes "default" e nessa rede externa
      E o label "traefik.docker.network" do nginx vale "<rede>"

      Exemplos:
        | valor no .env      | rede              | # partição  |
        | ausente            | my-network        | default     |
        | vazia (TRAEFIK_REDE=) | my-network     | vazia ≠ ausente |
        | rede-do-traefik    | rede-do-traefik   | definida    |

    Cenário: [CT-09] só o nginx entra na rede externa
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST "dev.exemplo.test"
      Quando o operador roda "docker compose --profile app config"
      Então nenhum serviço além do nginx está na rede externa
      E app, queue, scheduler, reverb, pulse, pgsql e redis estão só na rede "default"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M17 | label `traefik.docker.network=my-network` fixo enquanto a rede usa `TRAEFIK_REDE` | CT-08 (linha definida) | label vale `my-network`, rede vale `rede-do-traefik` |
| M18 | nginx só na rede do Traefik (perde `default` e não resolve `app:9000`) | CT-08 | `default` ausente das redes do nginx |
| M19 | rede sem `external: true` (o Compose cria `proj-dev_traefik`, isolada do Traefik) | CT-08 | `external` falso / `name` ≠ valor esperado |
| M20 | `x-traefik-net` aplicada também ao `app` ou ao `reverb` | CT-09 | outro serviço na rede externa |
| M94 | `${TRAEFIK_REDE-my-network}` (sem `:`) — a linha `TRAEFIK_REDE=` vazia copiada do `.env.docker` vira rede de nome vazio (ADV-08) | CT-08 (linha vazia) | `name` da rede externa = `''` e o label vale `''`; o caso exige `my-network` |

---

## Regra R5 — Labels do docker provider com os valores do requisito

> `RQ-07`, `RQ-09` · perfil **padrão** · técnica: **EP** (valor exato por label)

```gherkin
  Regra: os labels do nginx são os que o Traefik do servidor espera

    Cenário: [CT-10] o nginx carrega router por Host, websecure, TLS e service na porta 80
      Dado o exemplo copiado na raiz e um .env com COMPOSE_PROJECT_NAME "proj-dev" e TRAEFIK_HOST "dev.exemplo.test"
      Quando o operador roda "docker compose --profile app config"
      Então o nginx tem "traefik.enable" = "true"
      E "traefik.http.routers.proj-dev.rule" = "Host(`dev.exemplo.test`)"
      E "traefik.http.routers.proj-dev.entrypoints" = "websecure" e "traefik.http.routers.proj-dev.tls" = "true"
      E "traefik.http.services.proj-dev.loadbalancer.server.port" = "80"
      E as chaves de label do nginx que começam por "traefik." são exatamente "traefik.enable", "traefik.docker.network", "traefik.http.routers.proj-dev.rule", "traefik.http.routers.proj-dev.entrypoints", "traefik.http.routers.proj-dev.tls" e "traefik.http.services.proj-dev.loadbalancer.server.port"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M21 | service na porta 8000 (confundida com a publicada) | CT-10 | `loadbalancer.server.port` = `8000`, esperado `80` |
| M22 | entrypoint `web` | CT-10 | `entrypoints` = `web` |
| M23 | `tls=true` esquecido | CT-10 | label ausente |
| M24 | `traefik.enable=true` esquecido (Traefik com `exposedByDefault=false` ignora o container) | CT-10 | label ausente |
| M138 | label a mais no nginx com nome que começa pelo projeto — `traefik.http.routers.proj-dev-http.entrypoints=web` (redirect sem TLS) ou `traefik.http.routers.proj-dev.middlewares=…` — passa pelo prefixo de CT-11 (ADV2-06) | CT-10 | o conjunto de chaves `traefik.` do nginx tem uma a mais que as seis |

---

## Regra R6 — Router/service únicos por ambiente; hostname nunca fixo

> `RQ-08`, `RQ-17`, `P-04`, `P-05` · perfil **padrão** · técnica: **EP** (nome do projeto) + **estado de erro com saída**

```gherkin
  Regra: o nome de todo objeto do Traefik é o do projeto Compose, e o hostname vem do .env

    Esquema do Cenário: [CT-11] o router leva o nome do projeto de cada ambiente
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST "h.exemplo.test" e COMPOSE_PROJECT_NAME <valor>
      Quando o operador roda "docker compose --profile app config"
      Então o nginx tem "traefik.http.routers.<router>.rule" = "Host(`h.exemplo.test`)"
      E "traefik.http.services.<router>.loadbalancer.server.port" = "80"
      E todo nome de objeto em label "traefik.http.(routers|services|middlewares).<nome>." do nginx começa por "<router>"
      E nenhuma chave de label contém "${"

      Exemplos:
        | valor                       | router       | # partição           |
        | proj-dev                    | proj-dev     | ambiente 1           |
        | proj-homol                  | proj-homol   | ambiente 2           |
        | ausente                     | starter-kit  | piso (P-05)          |
        | vazia (COMPOSE_PROJECT_NAME=) | starter-kit | vazia ≠ ausente     |

    Esquema do Cenário: [CT-12] sem TRAEFIK_HOST o Compose recusa e diz qual chave falta
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST <valor>
      Quando o operador roda "docker compose --profile app config"
      Então o comando sai com código diferente de zero
      E a saída de erro nomeia "TRAEFIK_HOST"

      Exemplos:
        | valor                  | # partição       |
        | ausente                | ausente          |
        | vazia (TRAEFIK_HOST=)  | vazia ≠ ausente  |

    Cenário: [CT-13] o exemplo não fixa hostname
      Dado o exemplo, sem as linhas de comentário
      Quando o teste lê todo argumento de "Host(" nas linhas ativas
      Então cada argumento é uma interpolação que começa por "${TRAEFIK_HOST"
      E o texto ativo não contém "fiocruz.br" nem "projtec"
```

Saída do estado de erro de `[CT-12]`: o par é `[CT-10]` — com `TRAEFIK_HOST` definido, a mesma
configuração é aceita e o router existe. A linha vazia importa porque o bloco do `.env.docker` traz
`# TRAEFIK_HOST=` e quem descomenta sem preencher deixa a chave **vazia**, não ausente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | router fixo `starter-kit` (dois ambientes colidem no Traefik) | CT-11 (linhas proj-dev, proj-homol) | label `traefik.http.routers.proj-dev.rule` ausente |
| M26 | labels em **mapa** (a chave não interpola — medido) | CT-11 | chave de label com `${COMPOSE_PROJECT_NAME` literal |
| M27 | `${TRAEFIK_HOST:-localhost}` no lugar de `:?` (sobe roteando host errado, em silêncio) | CT-12 (linha ausente) | o comando sai 0; o caso exige ≠ 0 |
| M28 | hostname do documento de origem colado no exemplo (`desenv.projtec.fiocruz.br`) | CT-13 | argumento de `Host(` sem `${TRAEFIK_HOST` / `projtec` no texto ativo |
| M95 | `${TRAEFIK_HOST?…}` (sem `:`) — a chave vazia passa e o router vira `Host(``)` (ADV-08) | CT-12 (linha vazia) | o comando sai 0; o caso exige ≠ 0 e `TRAEFIK_HOST` no erro |
| M96 | `${COMPOSE_PROJECT_NAME-starter-kit}` (sem `:`) — a chave vazia gera `traefik.http.routers..rule` (ADV-08) | CT-11 (linha vazia) | label `traefik.http.routers.starter-kit.rule` ausente |
| M97 | middleware de nome fixo (`traefik.http.middlewares.redirect-https.…`) ou service `starter-kit-svc` — nome global colide entre ambientes (ADV-11) | CT-11 (linha proj-dev) | nome `redirect-https` não começa por `proj-dev` |
| M98 | `rule=Host(`${COMPOSE_PROJECT_NAME}.${TRAEFIK_HOST}`)` ou porta do service vinda de `FORWARD_APP_PORT` — a chave existe com valor errado (ADV-30) | CT-11 (toda linha) | `rule` ≠ `Host(`h.exemplo.test`)` / `server.port` ≠ `80` |

Estouro do teto (padrão: 5): M95…M98 — revisão adversarial (ADV-08, ADV-11, ADV-30).

---

## Regra R7 — Reverb: duas rotas, nenhuma imposta; a do Traefik não captura painel

> `RQ-12`, `RQ-04`, `P-04`, `P-10`, `P-16` · perfil **padrão** · técnica: **EP** (rota ativa × comentada) + **invariante** (CT-16, origem RQ-04) + **regex** sobre a regra interpolada pelo próprio Compose (recorte de P-10) + **estado de erro com saída** (CT-44, P-16)

```gherkin
  Regra: o exemplo oferece a rota do Reverb pelo Traefik sem ligá-la, e a porta própria continua valendo

    Cenário: [CT-14] com o override ativo, o Reverb continua na rota por porta
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST "dev.exemplo.test" e FORWARD_REVERB_PORT "8190"
      Quando o operador roda "docker compose --profile app config"
      Então o reverb não tem label que comece com "traefik."
      E o reverb não está na rede externa
      E o reverb publica a porta 8190 do host para a 8090 do container

    Cenário: [CT-15] o bloco comentado do Reverb traz a rota completa pelo Traefik
      Dado o texto cru do exemplo
      Quando o teste lê as linhas comentadas do bloco do reverb, sem o prefixo de comentário
      Então há o router "${COMPOSE_PROJECT_NAME:-starter-kit}-reverb" cuja regra casa "^Host\(`[^`]+`\) && \(PathPrefix\(`/app/[^`]+`\) \|\| PathPrefix\(`/apps/[^`]+`\)\)$"
      E o argumento de Host( começa por "${TRAEFIK_HOST"
      E o service desse router aponta a porta 8090
      E o bloco põe o reverb na rede externa e declara "traefik.docker.network"
      E o cabeçalho (os comentários antes da primeira linha ativa) tem linha com "TRUSTED_PROXIES=*", e toda linha dele que a contém cita também "127.0.0.1"

    Esquema do Cenário: [CT-16] a regra do Reverb, interpolada pelo Compose, recorta pela chave e não captura caminho de painel
      Dado o exemplo copiado na raiz com as linhas entre "# >>> reverb-traefik" e "# <<< reverb-traefik" descomentadas
      E um .env com TRAEFIK_HOST "dev.exemplo.test", COMPOSE_PROJECT_NAME "proj-dev", REVERB_APP_KEY "<chave>" e REVERB_APP_ID "<id>"
      Quando o operador roda "docker compose --profile app config"
      Então o label "traefik.http.routers.proj-dev-reverb.rule" do reverb, lido da configuração, casa "^Host\(`[^`]+`\) && \(PathPrefix\(`/app/[^`]+`\) \|\| PathPrefix\(`/apps/[^`]+`\)\)$"
      E nenhum PathPrefix dela é prefixo de "/app", "/app/login", "/app/acme/users", "/admin" ou "/infra"
      E algum PathPrefix é prefixo de "/app/<chave>" e algum de "/apps/<id>/events"
      E a regra não contém "<ausente>"

      Exemplos:
        | chave            | id           | ausente          | # partição                          |
        | starter-kit-key  | starter-kit  | ${               | valores do .env.docker              |
        | outra-chave      | outro-id     | starter-kit-key  | outra chave: o valor vem do .env    |
```

`[CT-16]` lê a regra **que o Compose interpolou** (mecanismo de CT-44), uma execução do Compose por
linha do Esquema — não a regra do texto interpolada pelo próprio teste, que aceitaria um `$${…}`
escapado ou uma interpolação que o Compose não resolve (ADV2-27). Tem **controle positivo** (a linha
"algum PathPrefix é prefixo"): uma regra que não casa nada não passa. O painel `/app` é do kit: `app/Providers/Filament/AppPanelProvider.php:path:79`
(`->path('app')`). A regex exige os **parênteses** em volta do `||`: no Traefik `&&` precede `||`, e
sem eles `PathPrefix(/apps/…)` casa em **qualquer** host — inclusive o dos outros ambientes. A última
linha de `[CT-15]` é RD-08 (cabeçalho do exemplo oferecia `TRUSTED_PROXIES=*` sem a ressalva do
loopback; P-21/D7): está em CT-15 por decisão da sessão — é o caso que já lê o texto cru do exemplo.

```gherkin
    Esquema do Cenário: [CT-44] descomentar o bloco entre os marcadores liga a rota do Reverb pelo Traefik, e só com chave e id
      Dado o exemplo copiado na raiz com as linhas entre "# >>> reverb-traefik" e "# <<< reverb-traefik" descomentadas (o "# " inicial removido)
      E um .env com TRAEFIK_HOST "dev.exemplo.test", COMPOSE_PROJECT_NAME "proj-dev", REVERB_APP_KEY <chave> e REVERB_APP_ID <id>
      Quando o operador roda "docker compose --profile app config"
      Então o comando sai com código <código>
      E a saída de erro nomeia "<nomeada>"
      E, na partição feliz, o reverb está nas redes "default" e externa e tem "traefik.docker.network" igual ao nome da rede externa
      E, na partição feliz, o router "proj-dev-reverb" tem regra que casa a regex de R7 com "/app/chave-x" e "/apps/id-y", entrypoint "websecure" e tls "true"
      E, na partição feliz, o service "proj-dev-reverb" aponta a porta 8090
      E, na partição feliz, o nginx continua com o router "proj-dev" e sem o sufixo "-reverb"

      Exemplos:
        | chave                     | id                       | código | nomeada         | # partição            |
        | "chave-x"                 | "id-y"                   | 0      | — (sem erro)    | feliz                 |
        | ausente                   | "id-y"                   | ≠ 0    | REVERB_APP_KEY  | chave ausente         |
        | vazia (REVERB_APP_KEY=)   | "id-y"                   | ≠ 0    | REVERB_APP_KEY  | chave vazia ≠ ausente |
        | "chave-x"                 | ausente                  | ≠ 0    | REVERB_APP_ID   | id ausente            |
        | "chave-x"                 | vazia (REVERB_APP_ID=)   | ≠ 0    | REVERB_APP_ID   | id vazio ≠ ausente    |
```

`[CT-44]` é cenário da **mesma** regra de R7 (o bloco é a rota pelo Traefik que a regra oferece);
nasceu da Q10, decidida como D6 no `01` — os marcadores existem para isto —, e retirou a antiga
lacuna L3. As linhas de erro são P-16: com `REVERB_APP_KEY` ou `REVERB_APP_ID` vazia, a regra viraria
`PathPrefix(/app/)` e roubaria o painel; a vazia importa porque o `.env.docker` traz as duas e quem
apaga o valor deixa a chave **vazia**, não ausente. Saída do estado de erro: a linha feliz. As
asserções "na partição feliz" só rodam na linha de código 0 — nas de erro não há configuração para
ler.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M29 | bloco do Reverb **ativo** no exemplo (impõe a rota pelo Traefik a todo servidor) | CT-14 | label `traefik.` no reverb |
| M30 | router do Reverb com o mesmo nome do nginx (sem `-reverb`) — colide no Traefik | CT-15 | router `…-reverb` ausente |
| M31 | bloco do Reverb sem `traefik.docker.network` (container em duas redes, IP errado — RQ-08) | CT-15 | label ausente do bloco |
| M32 | `PathPrefix(`/app`)` na regra (rouba o painel `/app` pela prioridade de regra mais longa) | CT-16 | `/app` é prefixo de `/app/login`; a regex de `/app/[^`]+` não casa |
| M33 | bloco do Reverb sem a rede externa | CT-15 | rede externa ausente do bloco |
| M99 | regra sem parênteses: `Host(…) && PathPrefix(/app/…) \|\| PathPrefix(/apps/…)` (ADV-13) | CT-15, CT-16 | a regex com `&& \(… \|\| …\)$` não casa |
| M100 | chave do `.env.docker` colada literal na regra (`PathPrefix(/app/starter-kit-key)`) em vez de `${REVERB_APP_KEY}` (ADV-14) | CT-16 (linha outra chave) | nenhum PathPrefix é prefixo de `/app/outra-chave`; a regra contém `starter-kit-key` |
| M128 | bloco comentado do Reverb com YAML que não valida ao descomentar (indentação errada, label fora da lista) — a adesão falha na hora de ligar | CT-44 (linha feliz) | `docker compose config` sai com código ≠ 0 |
| M139 | `PathPrefix(/app/${REVERB_APP_KEY})` ou `${REVERB_APP_KEY:-}` sem `:?` — com a chave vazia a regra vira `PathPrefix(/app/)` e rouba o painel (ADV2-04) | CT-44 (linhas chave ausente e vazia) | o comando sai 0; o caso exige ≠ 0 e `REVERB_APP_KEY` no erro |
| M140 | `${REVERB_APP_ID?…}` (sem `:`) — a chave vazia passa e a regra vira `PathPrefix(/apps/)` (ADV2-04) | CT-44 (linha id vazio) | o comando sai 0 |
| M141 | bloco põe o reverb **só** na rede externa (`networks: [traefik]`) — perde a `default` e não fala com o `redis` do próprio ambiente (ADV2-13) | CT-44 (linha feliz) | `default` ausente das redes do reverb |
| M142 | regra escrita com `$${REVERB_APP_KEY}` (escape do Compose) — o texto parece certo, o Compose entrega `${REVERB_APP_KEY}` literal ao Traefik (ADV2-27) | CT-16 | a regra lida da configuração contém `${`; a linha "valores do .env.docker" exige ausência |
| M166 | cabeçalho do exemplo sugere `TRUSTED_PROXIES=*` sem a ressalva do loopback — o operador o copia com a porta do nginx em `0.0.0.0` (RD-08) | CT-15 | linha do cabeçalho com `TRUSTED_PROXIES=*` sem `127.0.0.1` |

Estouro do teto (padrão: 5): M99, M100 — revisão adversarial (ADV-13, ADV-14); M128 — decisão da sessão (Q10, D6 do `01`: os marcadores existem para que descomentar o bloco seja testável); M139…M142 — 2ª rodada (ADV2-04, ADV2-13, ADV2-27); M166 — step 9 (RD-08).

---

## Regra R8 — Os quatro `VITE_REVERB_*` chegam ao `npm run build` de toda imagem

> `RQ-14`, `P-15` · perfil **padrão** · técnica: **contrato estático** (escopo de estágio) + **contrato entre serviços**

```gherkin
  Regra: o estágio assets declara os quatro ARG antes do build, e todo serviço que constrói recebe os mesmos valores

    Esquema do Cenário: [CT-17] o ARG está no estágio assets, antes do npm run build
      Dado o Dockerfile.laravel, recortado da linha que casa "^FROM\s+\S+\s+AS\s+assets$" até o próximo FROM
      Quando o teste procura a declaração de "<arg>"
      Então ela casa "^ARG <arg>(=.*)?$" dentro do recorte
      E aparece antes da linha "RUN npm run build" do recorte

      Exemplos:
        | arg                  |
        | VITE_REVERB_HOST     |
        | VITE_REVERB_PORT     |
        | VITE_REVERB_SCHEME   |
        | VITE_REVERB_APP_KEY  |

    Cenário: [CT-18] com os quatro no .env, todo serviço com build recebe os quatro args com os valores do .env
      Dado o exemplo copiado na raiz e um .env que define os quatro — VITE_REVERB_HOST "dev.exemplo.test", VITE_REVERB_PORT "443", VITE_REVERB_SCHEME "https", VITE_REVERB_APP_KEY "chave-dev" e TRAEFIK_HOST "dev.exemplo.test"
      Quando o operador roda "docker compose --profile app config"
      Então todo serviço que tem "build" na configuração do base sozinho tem "build.args" com os quatro
      E os valores são "dev.exemplo.test", "443", "https" e "chave-dev" em todos eles
```

Por que **todos** os serviços com build e não só o nginx: `app` e `web` compartilham o estágio
`assets`; args diferentes por serviço produzem bundles com hash diferente, e o manifest do `app`
passa a apontar arquivos que o nginx não serve. A lista sai do base, não do plano. O `.env` de CT-18
define **os quatro** porque o exemplo os passa em lista sem valor (P-15): o Compose pega cada um do
`.env` e omite o ausente — o caso com chave ausente é CT-45 (R9). O recorte do estágio é pelo nome
`assets`, não pela imagem: trocar `node:22-alpine` não muda o contrato (ADV2-29).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M34 | `ARG` antes do primeiro `FROM` (escopo global, invisível dentro do estágio sem redeclarar) | CT-17 | declaração fora do recorte |
| M35 | `ARG` depois do `RUN npm run build` | CT-17 | posição ≥ a do `RUN npm run build` |
| M36 | `ARG` no estágio `app` (o build dos assets não o vê) | CT-17 | declaração fora do recorte |
| M37 | args só no `nginx` | CT-18 | `build.args` ausente em `app`, `queue`, `scheduler`, `reverb`, `pulse` |
| M38 | `VITE_REVERB_APP_KEY` esquecido | CT-17 (linha APP_KEY), CT-18 | regex não casa / chave ausente de `build.args` |

---

## Regra R9 — Sem build-arg, o build é o de hoje

> `RQ-02`, `RQ-04`, `P-01`, `P-12`, `P-15` · perfil **padrão** · técnica: **EP** (forma da declaração; origem do valor; chave ausente × definida no `.env`)
> Mecanismo fixado por **P-12** (medido pela sessão com `docker build --progress=plain`: `ARG X` sem default fica **ausente** no `RUN`; `ARG X=` fica `''`).

```gherkin
  Regra: sem --build-arg, nenhuma VITE_REVERB_* existe no ambiente do npm run build

    Cenário: [CT-19] o estágio assets não define VITE_REVERB_* quando ninguém as passa
      Dado o recorte do estágio assets do Dockerfile.laravel, sem as linhas de comentário
      Quando o teste lê as linhas ARG, ENV, COPY, ADD e RUN do recorte
      Então nenhuma linha ENV atribui VITE_REVERB_*
      E nenhuma linha ARG de VITE_REVERB_* tem "=" (sem default, nem vazio)
      E nenhum COPY ou ADD tem origem que case "\.env"
      E nenhum COPY ou ADD tem origem "." (ou "./") ou com "*", salvo se o .dockerignore tiver a linha ativa ".env"
      E nenhum RUN atribui "VITE_REVERB_\w+=" antes do comando
      E nenhum "RUN --mount" tem "source" (ou "src") que case "\.env"

    Cenário: [CT-38] o base sozinho não passa VITE_REVERB_* ao build
      Dado uma pasta só com o docker-compose.yml do kit e um .env com VITE_REVERB_HOST "dev.exemplo.test"
      Quando o operador roda "docker compose --profile app config"
      Então nenhum serviço tem em "build.args" chave que comece com "VITE_REVERB_"

    Esquema do Cenário: [CT-45] com o override, build.args leva só as VITE_REVERB_* que o .env define
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST "dev.exemplo.test" e <vite no .env>
      Quando o operador roda "docker compose --profile app config"
      Então em todo serviço que tem "build" na configuração do base sozinho, "build.args" tem <presentes>
      E não tem as chaves <ausentes> — nem com valor vazio

      Exemplos:
        | vite no .env                              | presentes                                | ausentes                                                                  | # partição                    |
        | nenhuma VITE_REVERB_*                     | nenhuma VITE_REVERB_*                    | VITE_REVERB_HOST, VITE_REVERB_PORT, VITE_REVERB_SCHEME, VITE_REVERB_APP_KEY | nenhuma: o build é o de hoje  |
        | só VITE_REVERB_HOST "dev.exemplo.test"    | VITE_REVERB_HOST = "dev.exemplo.test"    | VITE_REVERB_PORT, VITE_REVERB_SCHEME, VITE_REVERB_APP_KEY                 | uma definida, três ausentes   |
```

O porquê é medido, não suposto: o Vite copia para `import.meta.env` toda `VITE_*` presente em
`process.env`, **inclusive vazia** (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5705`). Hoje o
estágio não tem `.env` e as chaves são `undefined`; com `ENV VITE_REVERB_PORT=` elas viram `''`, e o
`import.meta.env.VITE_REVERB_PORT ?? 80` do projeto que adicionar Echo deixa de cair no default.
`[CT-38]` fecha a outra porta: o `.env` do projeto tem `VITE_REVERB_HOST` (o `.env.example` a traz),
e args no **base** a levariam ao build de quem nunca ligou o override. `[CT-45]` fecha a terceira porta,
**pelo override** (P-15, medido): `${VITE_REVERB_PORT:-}` passaria `""` ao build e reintroduziria a
armadilha de P-12; a lista sem valor omite a chave ausente. O caso depende do filtro de ambiente do
Setup Global — o `.env` do desenvolvedor traz `VITE_REVERB_*` e, herdado pelo subprocesso, faria a
linha "nenhuma" medir o ambiente dele. `[CT-19]`, linha do `COPY`: o estágio de hoje copia
`package*.json`, e o que o torna seguro é o `.dockerignore` excluir `.env`; um `COPY . .` ou um
`RUN --mount=type=bind,source=.env` traria o `.env` para o `npm run build` por outro caminho (ADV2-20).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | `ENV VITE_REVERB_HOST=$VITE_REVERB_HOST` promovendo ARG vazio | CT-19 | linha `ENV` com `VITE_REVERB_` |
| M40 | `ARG VITE_REVERB_PORT=` (default vazio, entra no ambiente do RUN como `''` — P-12) | CT-19 | `ARG` com `=` |
| M41 | `ARG VITE_REVERB_HOST=localhost` "para dev funcionar" | CT-19 | `ARG` com `=` |
| M101 | `build.args: VITE_REVERB_HOST: ${VITE_REVERB_HOST}` no bloco de build do **base** em vez do override (ADV-03) | CT-38 | `services.app.build.args.VITE_REVERB_HOST` = `dev.exemplo.test` sem override |
| M102 | `COPY .env* ./` no estágio `assets` (a outra saída que o levantamento cita) (ADV-16) | CT-19 | `COPY` com origem `.env*` no recorte |
| M103 | `RUN VITE_REVERB_PORT=${VITE_REVERB_PORT:-} npm run build` (ADV-16) | CT-19 | `RUN` com `VITE_REVERB_PORT=` antes do comando |

| M143 | `build.args` do exemplo em mapa com `${VITE_REVERB_PORT:-}` — a chave ausente do `.env` vai ao build como `""` e o Vite a copia para o bundle (ADV2-07, P-15) | CT-45 (linha nenhuma) | `build.args.VITE_REVERB_PORT` = `""` presente; o caso exige a chave ausente |
| M144 | lista com default (`- VITE_REVERB_PORT=443`, `- VITE_REVERB_SCHEME=https`) "porque atrás do Traefik é sempre 443" (ADV2-07) | CT-45 (as duas linhas) | `VITE_REVERB_PORT` presente sem estar no `.env` |
| M145 | `COPY . .` no estágio `assets` com o `.dockerignore` sem `.env` (ADV2-20) | CT-19 | `COPY` com origem `.` sem a linha ativa `.env` no `.dockerignore` |
| M146 | `RUN --mount=type=bind,source=.env,target=/app/.env npm run build` (ADV2-20) | CT-19 | `RUN --mount` com `source` que casa `\.env` |

Estouro do teto (padrão: 5): M101…M103 — revisão adversarial (ADV-03, ADV-16); M143…M146 — 2ª rodada (ADV2-07, ADV2-20).

---

## Regra R10 — Interpretação de `TRUSTED_PROXIES`

> `P-02`, `P-11`, `P-18`, `P-19` · perfil **completo** (escalado: fronteira de confiança) · técnica: **EP exaustiva**
> Tokens que o Laravel/Symfony expandem (`*`, `**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`): **decisão da
> sessão (ADV-20), falha fechado** — só `*` sozinho vale "todos"; dentro de lista os quatro são
> descartados; `**`, `REMOTE_ADDR` e `PRIVATE_SUBNETS` sozinhos viram `null` (falha fechado), e o
> filtro compara o item **depois** do `trim`. Lista que, filtrada, fica vazia vira `null` — inclusive
> `'*,'`, em que o `*` está numa lista (decisão da sessão, ADV2-19).
> **Step 9 (P-18, P-19)**: item que não é IP nem CIDR — IPv4 com prefixo 0–32, IPv6 com prefixo
> 0–128 — é descartado como os coringas; `private_ranges` é coringa. O Symfony não valida
> (`vendor/symfony/http-foundation/IpUtils.php:checkIp4:87` faz `explode('/')` e compara o prefixo sem
> checar o tipo) e reconhece `private_ranges` como `PRIVATE_SUBNETS`
> (`vendor/symfony/http-foundation/Request.php:private_ranges:658`). CIDR com bits de host
> (`10.0.0.1/8`) é aceito, como o Symfony aceita. O que foi descartado é exposto à parte (CT-49) para o
> aviso de R21.

```gherkin
  Regra: o valor do .env vira nenhum proxy, todos, ou uma lista limpa sem token mágico

    Esquema do Cenário: [CT-20] cada forma do valor tem uma interpretação só
      Dado o valor bruto <bruto> lido de TRUSTED_PROXIES
      Quando o kit o interpreta
      Então o resultado é <resultado>

      Exemplos:
        | bruto                              | resultado                          | # partição                         |
        | null                               | null                               | ausente                            |
        | ''                                 | null                               | vazia                              |
        | '   '                              | null                               | só espaços                         |
        | ','                                | null                               | só separador                       |
        | ' , , '                            | null                               | separadores e espaços              |
        | '*'                                | '*'                                | todos                              |
        | ' * '                              | '*'                                | todos, com espaço                  |
        | '10.0.0.1'                         | ['10.0.0.1']                       | um                                 |
        | '10.0.0.1,172.18.0.0/16'           | ['10.0.0.1', '172.18.0.0/16']      | dois, CIDR preservado              |
        | ' 10.0.0.1 , ,172.18.0.0/16 '      | ['10.0.0.1', '172.18.0.0/16']      | espaço e item vazio no meio        |
        | true                               | null                               | não-string (`TRUSTED_PROXIES=true`) |
        | 1                                  | null                               | não-string inteiro                 |
        | false                              | null                               | não-string falso                   |
        | '10.0.0.1,*'                       | ['10.0.0.1']                       | `*` dentro de lista (P-11)         |
        | '*,*'                              | null                               | só `*` repetido, em lista          |
        | '*,'                               | null                               | `*` com separador: lista, não "todos" |
        | '**'                               | null                               | `**` sozinho                       |
        | '10.0.0.1,**'                      | ['10.0.0.1']                       | `**` dentro de lista               |
        | '10.9.9.9,REMOTE_ADDR'             | ['10.9.9.9']                       | `REMOTE_ADDR` dentro de lista      |
        | '10.9.9.9, REMOTE_ADDR '           | ['10.9.9.9']                       | token com espaço nas bordas        |
        | 'REMOTE_ADDR'                      | null                               | `REMOTE_ADDR` sozinho (falha fechado) |
        | 'PRIVATE_SUBNETS'                  | null                               | `PRIVATE_SUBNETS` sozinho          |
        | '10.9.9.9,PRIVATE_SUBNETS'         | ['10.9.9.9']                       | `PRIVATE_SUBNETS` dentro de lista  |
        | '10.9.9.9 , PRIVATE_SUBNETS'       | ['10.9.9.9']                       | token e item com espaço            |
        | 'private_ranges'                   | null                               | `private_ranges` sozinho (P-19)    |
        | '10.9.9.9,private_ranges'          | ['10.9.9.9']                       | `private_ranges` dentro de lista (P-19) |
        | '172.18.0.0/16x'                   | null                               | CIDR com prefixo malformado (P-18) |
        | '17x.18.0.1'                       | null                               | IP com octeto malformado (P-18)    |
        | '10.0.0.1,172.18.0.0/16x'          | ['10.0.0.1']                       | inválido dentro de lista: só ele sai |
        | '10.0.0.0/33'                      | null                               | prefixo IPv4 acima de 32           |
        | '2001:db8::1'                      | ['2001:db8::1']                    | IPv6 válido                        |
        | '2001:db8::/129'                   | null                               | prefixo IPv6 acima de 128          |
        | '10.0.0.1/8'                       | ['10.0.0.1/8']                     | CIDR com bits de host: aceito      |
        | '10.0.0.1/32'                      | ['10.0.0.1/32']                    | prefixo no limite IPv4 (/32): aceito (QA-02, mutante `<=`→`<`) |
        | '2001:db8::1/128'                  | ['2001:db8::1/128']                    | prefixo no limite IPv6 (/128): aceito |

    Esquema do Cenário: [CT-49] o kit diz quais itens descartou
      Dado o valor bruto <bruto> lido de TRUSTED_PROXIES
      Quando o kit lista os itens que descartou
      Então a lista é <descartados>

      Exemplos:
        | bruto                                  | descartados                     | # partição                              |
        | '10.0.0.1,*'                           | ['*']                           | coringa em lista                        |
        | '10.9.9.9,private_ranges'              | ['private_ranges']              | coringa minúsculo em lista (P-19)       |
        | '172.18.0.0/16x'                       | ['172.18.0.0/16x']              | inválido sozinho                        |
        | '10.0.0.1,REMOTE_ADDR,17x.18.0.1'      | ['REMOTE_ADDR', '17x.18.0.1']   | misto: coringa e inválido, na ordem     |
        | ' 10.0.0.1 , 17x.18.0.1 '              | ['17x.18.0.1']                  | o descartado vem aparado                |
        | null                                   | []                              | ausente                                 |
        | ''                                     | []                              | vazio                                   |
        | ' , , '                                | []                              | só separadores: item vazio não é descarte |
        | '10.0.0.1,172.18.0.0/16'               | []                              | lista limpa                             |
        | '*'                                    | []                              | `*` sozinho vale "todos": não é descarte |
        | '**'                                   | ['**']                          | coringa sozinho (Q13: descartado e avisado) |
        | 'REMOTE_ADDR'                          | ['REMOTE_ADDR']                 | coringa sozinho                         |
        | 'private_ranges'                       | ['private_ranges']              | coringa minúsculo sozinho (P-19)        |
        | true                                   | ['true']                        | não-string (`TRUSTED_PROXIES=true` vira bool no `env()`): avisado |
```

A comparação é `toBe` (estrita): lista com chaves `0, 1` — `[0 => 'a', 2 => 'b']` reprova. Por que
cada token mágico é perigoso: o Laravel trata `'*'` **e** `'**'` como "confiar no chamador"; o
Symfony troca `REMOTE_ADDR` da lista pelo IP do chamador e `PRIVATE_SUBNETS` por todas as faixas
privadas — no Docker, **todo** container da rede. Passar qualquer um adiante dentro de uma lista
transforma um erro de digitação em confiança total. As linhas de P-18 existem porque o Symfony não
valida: `172.18.0.0/16x` chega ao `checkIp4` e quebra **todo** request; `17x.18.0.1` e `/33` não casam
nada, em silêncio — o operador acha que confiou no Traefik e não confiou. `[CT-49]` compara com `toBe`
(ordem e chaves `0…n`); as linhas de coringa sozinho (`REMOTE_ADDR`, `private_ranges`, `**`) e de
não-string entram (Q13 decidida pela sessão em 2026-10-05: sim para os dois — coringa sozinho e não-string são descartados e avisados).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M42 | itens sem `trim` | CT-20 (linha espaço e item vazio) | `[' 10.0.0.1 ', …]` ≠ esperado |
| M43 | item vazio não descartado | CT-20 (linha espaço e item vazio) | `['10.0.0.1', '', '172.18.0.0/16']` |
| M44 | `array_filter` sem reindexar | CT-20 (linha espaço e item vazio) | chaves `0, 2` reprovam o `toBe` |
| M45 | lista vazia devolvida como `[]` | CT-20 (linhas só separador / separadores e espaços) | `[]` ≠ `null` |
| M46 | `*` comparado sem `trim` (vira `['*']`, que o Symfony não reconhece: confia em ninguém) | CT-20 (linha todos, com espaço) | `['*']` ≠ `'*'` |
| M47 | não-string coagido por `(string)` (`true` → `['1']`) | CT-20 (linhas não-string) | `['1']` ≠ `null` |
| M104 | `*` dentro de lista mantido como item (ADV-20) | CT-20 (linha `*` dentro de lista) | `['10.0.0.1', '*']` ≠ `['10.0.0.1']` |
| M105 | `**` sozinho devolvido como `'**'` — o Laravel o trata como "todos" (ADV-20) | CT-20 (linha `**` sozinho) | `'**'` ≠ `null` |
| M106 | lista deduplicada e, sobrando só `*`, promovida a `'*'` (ADV-20) | CT-20 (linha só `*` repetido) | `'*'` ≠ `null` |
| M107 | `REMOTE_ADDR` passado adiante dentro da lista — o Symfony o troca pelo IP do chamador e qualquer chamador vira proxy (ADV-20, ADV-33) | CT-20 (linha `REMOTE_ADDR`), CT-21 (linha `10.9.9.9,REMOTE_ADDR`) | `['10.9.9.9', 'REMOTE_ADDR']` ≠ `['10.9.9.9']` / `isSecure()` verdadeiro |
| M108 | lista passa `PRIVATE_SUBNETS` adiante e o container da rede do Docker vira proxy confiável (decisão complementar da sessão) | CT-20 (linhas `PRIVATE_SUBNETS`), CT-21 (linha `10.9.9.9,PRIVATE_SUBNETS`, chamador `172.18.0.5`) | `['10.9.9.9', 'PRIVATE_SUBNETS']` ≠ `['10.9.9.9']` / `isSecure()` verdadeiro |

| M147 | filtro de token aplicado **antes** do `trim` — `' REMOTE_ADDR '` não é reconhecido, é aparado depois e vai adiante (ADV2-10) | CT-20 (linhas token com espaço) | `['10.9.9.9', 'REMOTE_ADDR']` ≠ `['10.9.9.9']` |
| M148 | dentro de lista só `*` é descartado; `**` vai adiante — o Laravel o trata como "todos" (ADV2-19) | CT-20 (linha `**` dentro de lista) | `['10.0.0.1', '**']` ≠ `['10.0.0.1']` |
| M149 | lista que sobra com um item só é colapsada para ele, ou o valor é aparado de `,` antes do teste de `*` — `'*,'` vira `'*'` (ADV2-19) | CT-20 (linha `*` com separador) | `'*'` ≠ `null` |
| M167 | validação só de IPv4 (`ip2long`/`FILTER_FLAG_IPV4`) — o IPv6 do operador é descartado (P-18) | CT-20 (linha IPv6 válido) | `null` ≠ `['2001:db8::1']` |
| M168 | prefixo do CIDR conferido só como dígitos (`\d{1,3}`), sem a faixa — `/33` e `/129` passam ao Symfony, que não casa nada em silêncio (P-18) | CT-20 (linhas `/33`, `/129`) | `['10.0.0.0/33']` ≠ `null` |
| M169 | prefixo coagido por `(int)` antes de conferir a faixa — `/16x` vira `16` e passa (RD-03) | CT-20 (linhas `/16x`) | `['172.18.0.0/16x']` ≠ `null` |
| M170 | validação por `FILTER_VALIDATE_IP` sem tratar o `/` — todo CIDR é descartado (P-18) | CT-20 (linhas dois/CIDR preservado, `10.0.0.1/8`) | `['10.0.0.1']` ≠ `['10.0.0.1', '172.18.0.0/16']` |
| M171 | CIDR com bits de host recusado "porque não é endereço de rede" (`10.0.0.1/8`) — o Symfony o aceita (P-18) | CT-20 (linha `10.0.0.1/8`) | `null` ≠ `['10.0.0.1/8']` |
| M172 | um item inválido anula a lista inteira (P-18 fala do item, não da lista) | CT-20 (linha inválido dentro de lista) | `null` ≠ `['10.0.0.1']` |
| M173 | o código de v3: coringas só os quatro de P-11 e nenhuma validação — `private_ranges` vai adiante e o Symfony confia em toda faixa privada (RD-02) | CT-20 (linhas `private_ranges`) | `['10.9.9.9', 'private_ranges']` ≠ `['10.9.9.9']` |
| M174 | `descartados()` devolve só os inválidos (os coringas "já eram descartados antes") (P-18) | CT-49 (linhas coringa em lista, misto) | `[]` ≠ `['*']` / `['17x.18.0.1']` ≠ `['REMOTE_ADDR', '17x.18.0.1']` |
| M175 | `descartados()` inclui o `*` sozinho — toda instalação com `*` ganha aviso falso no log (P-18) | CT-49 (linha `*` sozinho) | `['*']` ≠ `[]` |
| M176 | `descartados()` inclui o item vazio entre separadores (P-18) | CT-49 (linha só separadores) | `['', '', '']` ≠ `[]` |
| M177 | `descartados()` devolve o item cru, sem `trim` (P-18) | CT-49 (linha o descartado vem aparado) | `[' 17x.18.0.1 ']` ≠ `['17x.18.0.1']` |

Estouro do teto (completo: 6): M104…M108 — revisão adversarial (ADV-20, ADV-33) e complemento da sessão (`PRIVATE_SUBNETS`); M147…M149 — 2ª rodada (ADV2-10, ADV2-19); M167…M177 — step 9 (RD-02, RD-03/CR-04). Onze mutantes numa regra só sugerem duas regras (interpretar × listar o descartado); fica uma, porque CT-49 é o mesmo domínio de entrada de CT-20 e o desdobramento renumeraria a rastreabilidade (`SKILL.md` §Passo 6, item 1).

---

## Regra R11 — Ausente, vazia ou lista sem o chamador: `X-Forwarded-*` ignorados

> `P-02`, `P-11`, `P-18`, `P-19`, `RQ-05` · perfil **padrão** · técnica: **tabela de decisão** (chamador × valor efetivo → `isSecure`, host, URL, IP) · invariante: vale com qualquer resposta a Q1 · costura G6: `refreshApplication()` depois de fixar o env

```gherkin
  Regra: sem proxy confiável, a aplicação vê o request como hoje

    Esquema do Cenário: [CT-21] cabeçalhos de proxy não confiável não mudam esquema, host, URL nem IP
      Dado TRUSTED_PROXIES efetivo <valor efetivo>, APP_URL "https://dev.exemplo.test" e o chamador em <chamador>
      E o request com Host "interno.local", X-Forwarded-For "203.0.113.9", X-Forwarded-Proto "https", X-Forwarded-Host "dev.exemplo.test" e X-Forwarded-Port "443"
      Quando o Traefik faz o request à rota de teste
      Então a resposta é 200 e a aplicação vê isSecure() <isSecure>
      E o host visto é "<host>", url('/x') é "<url>" e ip() é "<ip>"

      Exemplos:
        | chamador    | valor efetivo               | isSecure | host          | url                     | ip          | # partição                                |
        | 127.0.0.1   | ausente                     | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | default de hoje                           |
        | 127.0.0.1   | ''                          | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | vazia                                     |
        | 127.0.0.1   | '10.9.9.9'                  | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | lista sem o chamador                      |
        | 127.0.0.1   | true (bool, de `=true`)     | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | não-string: falha fechado, sem TypeError  |
        | 127.0.0.1   | '10.9.9.9,*'                | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | `*` dentro de lista (P-11)                |
        | 127.0.0.1   | '10.9.9.9,REMOTE_ADDR'      | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | `REMOTE_ADDR` dentro de lista             |
        | 127.0.0.1   | '**'                        | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | `**` sozinho                              |
        | 127.0.0.1   | 'REMOTE_ADDR'               | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | `REMOTE_ADDR` sozinho                     |
        | 127.0.0.1   | '*,*'                       | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | `*` repetido, em lista                    |
        | 127.0.0.1   | ausente, com TRAEFIK_HOST "dev.exemplo.test" no env | falso | interno.local | http://interno.local/x | 127.0.0.1 | override ligado não liga a confiança |
        | 172.18.0.5  | ausente                     | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | chamador na rede do Docker, sem chave     |
        | 10.0.0.7    | ausente                     | falso    | interno.local | http://interno.local/x  | 10.0.0.7    | chamador em 10/8, sem chave               |
        | 172.18.0.5  | ''                          | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | rede do Docker, vazia                     |
        | 10.0.0.7    | '10.9.9.9'                  | falso    | interno.local | http://interno.local/x  | 10.0.0.7    | faixa privada, lista sem o chamador       |
        | 172.18.0.5  | '10.9.9.9,PRIVATE_SUBNETS'  | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | `PRIVATE_SUBNETS` dentro de lista         |
        | 172.18.0.5  | 'PRIVATE_SUBNETS'           | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | `PRIVATE_SUBNETS` sozinho, chamador privado |
        | 172.18.0.5  | '10.9.9.9,private_ranges'   | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | `private_ranges` dentro de lista, chamador privado (P-19) |
        | 127.0.0.1   | '172.18.0.0/16x'            | falso    | interno.local | http://interno.local/x  | 127.0.0.1   | item malformado: 200, sem erro do Symfony (P-18) |
```

Todas as linhas têm a mesma saída **de propósito**: a tabela é a fronteira — nenhuma combinação de
chamador e valor fora de R12 abre a confiança. O `APP_URL` em `https` no `Dado` é o que torna a coluna
`url` discriminante: a URL do request continua a do request (`http://interno.local`), não a da
configuração. Hoje nada no kit força esquema nem raiz que mascare essa coluna:
`grep -rn "forceScheme\|forceRootUrl" app/` devolve só `URL::forceRootUrl` em
`app/Http/Middleware/RaizDeUrlSemPublic.php:forceRootUrl:73` (dentro de `handle()`), condicionado a
base de URL terminada em `/public` — a rota de teste não tem essa base. As linhas de token sozinho e
`*,*` provam no HTTP o que CT-20 prova na função (ADV2-05): um boot que repassasse o token cru ao
`TrustProxies::at()` passaria em CT-20. A linha com `TRAEFIK_HOST` prova que ligar o override não liga
a confiança — só `TRUSTED_PROXIES` liga (ADV2-09). As duas linhas do step 9: `private_ranges` com
chamador `172.18.0.5` (RD-02 — o Symfony o expandiria para toda faixa privada) e `172.18.0.0/16x`, cujo
discriminante é o **200**: passado ao Symfony, o item quebra todo request (RD-03) — é o "nunca para o
Symfony" de R21 observado no HTTP.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | `TrustProxies::at('*')` incondicional (no boot do provider ou no `bootstrap/app.php`) | CT-21 (linha ausente) | `isSecure()` verdadeiro |
| M49 | qualquer valor não vazio vira `'*'` | CT-21 (linha lista sem o chamador) | host visto `dev.exemplo.test` |
| M50 | vazio tratado como "todos" (`?: '*'`) | CT-21 (linha vazia) | `isSecure()` verdadeiro |
| M109 | default de faixas privadas quando a chave falta (`?? ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']`, "porque o Traefik está na rede do Docker") (ADV-04) | CT-21 (linhas `172.18.0.5`/`10.0.0.7` com chave ausente ou vazia) | `isSecure()` verdadeiro, host `dev.exemplo.test`, ip `203.0.113.9` |
| M110 | o boot passa o `config('kit.proxies_confiaveis')` cru ao `TrustProxies::at()`, sem a interpretação de R10 (ADV-05) | CT-22 (linha `' * '`) | `' * '` cru vira `['*']` no Laravel: `isSecure()` falso, o CT-22 exige verdadeiro. A linha `true` de CT-21 **não** conta como matador: sem `strict_types` no arquivo do boot o `true` é coagido a `'1'` e não confia em ninguém; com ele, `TypeError` (ADV2-25) |
| M111 | `URL::forceRootUrl(config('app.url'))` / `forceScheme('https')` quando `APP_URL` é `https` — contorna a fronteira de confiança (ADV-18) | CT-21 (toda linha) | `url('/x')` = `https://dev.exemplo.test/x`; o caso exige `http://interno.local/x` |

| M150 | o boot repassa ao `TrustProxies::at()` o valor cru quando ele é um token do framework sozinho (`**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`) ou `*,*`, e só usa a interpretação de R10 para listas (ADV2-05) | CT-21 (linhas `**`, `REMOTE_ADDR`, `*,*`, `PRIVATE_SUBNETS` sozinhos) | `isSecure()` verdadeiro, host `dev.exemplo.test`, ip `203.0.113.9` |
| M151 | `TrustProxies::at('*')` quando `TRAEFIK_HOST` está definida, "porque o override está ligado" (ADV2-09) | CT-21 (linha com `TRAEFIK_HOST`) | `isSecure()` verdadeiro |
| M152 | lista que contém `*` promovida a `'*'` ("o operador quis todos") (ADV2-26) | CT-21 (linha `10.9.9.9,*`), CT-20 (linha `10.0.0.1,*`) | `isSecure()` verdadeiro / `'*'` ≠ `['10.0.0.1']` |
| M178 | o boot filtra os coringas mas não valida IP/CIDR — `172.18.0.0/16x` chega ao Symfony e todo request quebra (RD-03) | CT-21 (linha item malformado) | resposta 500; o caso exige 200 e `isSecure()` falso |
| M179 | `private_ranges` passado adiante ao `TrustProxies::at()` (RD-02) | CT-21 (linha `10.9.9.9,private_ranges`) | `isSecure()` verdadeiro, host `dev.exemplo.test`, ip `203.0.113.9` |

Estouro do teto (padrão: 5): M109…M111 — revisão adversarial (ADV-04, ADV-05, ADV-18); M150…M152 — 2ª rodada (ADV2-05, ADV2-09, ADV2-26); M178, M179 — step 9 (RD-02, RD-03).

---

## Regra R12 — `*` ou lista com o chamador: a aplicação honra `X-Forwarded-*`

> `P-02` (alterada no step 9), `P-13`, `P-18`, `RQ-09` · perfil **padrão** · técnica: **tabela de decisão** + **leitura do caminho `config` → boot** (CT-48) · direção de Q1, implementada como P-02 · costura G6: `refreshApplication()` depois de fixar o env

```gherkin
  Regra: com proxy confiável, o esquema, o host e o IP são os que o Traefik informa

    Esquema do Cenário: [CT-22] cabeçalhos de proxy confiável chegam à aplicação
      Dado TRUSTED_PROXIES efetivo <valor efetivo> e o chamador em 127.0.0.1
      E o request com Host "interno.local", X-Forwarded-For "203.0.113.9", X-Forwarded-Proto "https", X-Forwarded-Host "dev.exemplo.test" e X-Forwarded-Port "<porta>"
      Quando o Traefik faz o request à rota de teste
      Então a aplicação vê isSecure() verdadeiro e host "dev.exemplo.test"
      E url('/x') é "<url>" e ip() é "203.0.113.9"

      Exemplos:
        | valor efetivo             | porta | url                               | # partição                 |
        | '*'                       | 443   | https://dev.exemplo.test/x        | todos                      |
        | ' * '                     | 443   | https://dev.exemplo.test/x        | todos, com espaço          |
        | '10.9.9.9,127.0.0.1'      | 443   | https://dev.exemplo.test/x        | lista com o chamador       |
        | '*'                       | 8443  | https://dev.exemplo.test:8443/x   | porta não padrão do proxy  |
        | '17x.18.0.1,127.0.0.1'    | 443   | https://dev.exemplo.test/x        | item malformado descartado, o válido continua (P-18) |

    Esquema do Cenário: [CT-48] a chave é lida pelo config e aplicada no boot, não no bootstrap
      Dado TRUSTED_PROXIES <valor no ambiente> fixado nas três formas e a aplicação recriada
      E o request com Host "interno.local", X-Forwarded-Proto "https" e X-Forwarded-Host "dev.exemplo.test", vindo de 127.0.0.1
      Quando o Traefik faz o request à rota de teste
      Então config('kit.proxies_confiaveis') é <no config>
      E a aplicação vê isSecure() <isSecure>
      E, na árvore do kit, o texto de "bootstrap/app.php" não contém "trustProxies" nem "TRUSTED_PROXIES"

      Exemplos:
        | valor no ambiente | no config | isSecure   | # partição                                      |
        | ausente           | null      | falso      | ausente: config nulo, ninguém confiável         |
        | '*'               | '*'       | verdadeiro | o caminho config → boot liga a confiança        |
        | ' * '             | ' * '     | verdadeiro | o config é cru; a interpretação é a do boot (R10) |
```

`[CT-48]` nasce de P-02 alterada (RD-01/CR-01): o closure de `withMiddleware()` roda **antes** de o
`.env` ser carregado, então `env('TRUSTED_PROXIES')` no `bootstrap/app.php` só via o ambiente do
processo — a chave escrita no arquivo `.env`, que é o que a página ensina, era ignorada. O arnês fixa
a chave no ambiente do processo, onde uma leitura no bootstrap **também** a veria; por isso a linha que
discrimina RD-01 é a **textual** (o `bootstrap/app.php` não lê a chave), e as linhas de config e request
provam que o caminho novo liga. A prova de runtime com a chave **só no arquivo `.env`** é a lacuna L12.
A linha textual só roda na árvore do kit: o `bootstrap/app.php` não está em `CAMINHOS_DO_KIT`, e o
projeto instalado pode ter o seu próprio `trustProxies()` — fora da árvore a linha é pulada, com
mensagem sem a palavra `docs`, e as outras duas continuam valendo. A linha `' * '` → `' * '` é a
decisão da sessão de expor a chave crua no config (`## Fronteira com o Plano`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M51 | `TrustProxies::at()` ausente do boot — a chave é lida e nunca aplicada | CT-22 (linha todos), CT-48 (linha `'*'`) | `isSecure()` falso |
| M52 | chave lida com nome errado (`TRUSTED_PROXY`) | CT-22 (linha todos) | `isSecure()` falso |
| M53 | `headers:` restrito a `HEADER_X_FORWARDED_FOR` | CT-22 | `isSecure()` falso e host `interno.local` |
| M54 | só o primeiro item da lista é usado | CT-22 (linha lista com o chamador) | `isSecure()` falso |
| M112 | `headers:` sem `HEADER_X_FORWARDED_FOR` (só Proto/Host/Port) — o IP do log e do rate limit vira o do Traefik (ADV-15) | CT-22 | `ip()` = `127.0.0.1`; o caso exige `203.0.113.9` |
| M153 | `headers:` sem `HEADER_X_FORWARDED_PORT` — atrás de um entrypoint em porta não padrão a URL perde a porta (ADV2-18) | CT-22 (linha 8443) | `url('/x')` = `https://dev.exemplo.test/x`; o caso exige `:8443` |
| M180 | havendo descarte, o boot não chama `TrustProxies::at()` ("valor suspeito, melhor não confiar em nada") (P-18) | CT-22 (linha item malformado descartado) | `isSecure()` falso |
| M181 | `bootstrap/app.php` mantém `trustProxies(at: …env('TRUSTED_PROXIES'))` ao lado do provider — com a chave só no `.env`, o bootstrap aplica `null` e o resultado depende da ordem (RD-01) | CT-48 (linha textual) | o texto contém `trustProxies` |
| M182 | `config/kit.php` sem a chave; o provider lê `env('TRUSTED_PROXIES')` no boot — com `config:cache` a chave vira `null` em silêncio (P-13) | CT-48 (linhas `'*'` e `' * '`) | `config('kit.proxies_confiaveis')` = `null` com a chave fixada |
| M183 | `config/kit.php` com a chave, mas o provider lê `env()` direto em vez do config — sem cache é igual; com `config:cache`, o valor cacheado é ignorado (P-13) | sem matador — — (lacuna declarada L9) | — : só a configuração em cache com a chave fora do ambiente diverge |
| M184 | o provider lê `kit.trusted_proxies` e o config expõe `kit.proxies_confiaveis` (nomes divergentes) | CT-48 (linha `'*'`), CT-22 | `isSecure()` falso |

Estouro do teto (padrão: 5): M112, M153 — revisão adversarial (ADV-15, ADV2-18); M180…M184 — step 9 (RD-01/CR-01, RD-03).

---

## Regra R13 — Chaves novas só como linha comentada; o `.env.docker` oferece o que o exemplo consome; a sugestão não colide

> `RQ-02`, `RQ-05`, `RQ-06`, `RQ-10`, `RQ-16`, `P-02`, `P-04`, `P-09` · perfil **padrão** · técnica: **EP arquivo × chave** + **contrato**
> Vocabulário: *linha ativa* e *linha comentada* (do `.env`) conforme `wikis/glossario.md`.

```gherkin
  Regra: copiar os arquivos de sugestão como sempre não liga nada

    Esquema do Cenário: [CT-23] a chave nova existe só como linha comentada
      Dado o arquivo "<arquivo>" do kit
      Quando o teste procura "<chave>"
      Então não há linha ativa de "<chave>"
      E há linha comentada "^#\s*<chave>="
      E o .env.docker não tem linha ativa de COMPOSE_FILE nem de COMPOSE_PROFILES

      Exemplos:
        | arquivo       | chave                 |
        | .env.example  | TRUSTED_PROXIES       |
        | .env.docker   | TRUSTED_PROXIES       |
        | .env.docker   | TRAEFIK_HOST          |
        | .env.docker   | TRAEFIK_REDE          |
        | .env.docker   | COMPOSE_PROJECT_NAME  |
        | .env.docker   | FORWARD_APP_PORT      |
        | .env.docker   | FORWARD_DB_PORT       |
        | .env.docker   | FORWARD_REDIS_PORT    |
        | .env.docker   | FORWARD_REVERB_PORT   |

    Cenário: [CT-24] toda variável que o exemplo interpola é oferecida no .env.docker
      Dado o texto cru do exemplo e o .env.docker
      Quando o teste extrai todo nome em "${NOME" do exemplo, exceto os já resolvidos pelo próprio Compose
      Então cada nome tem linha ativa ou linha comentada no .env.docker
      E a última linha ativa de APP_URL no .env.docker continua "http://localhost:8000"

    Cenário: [CT-25] descomentar o bloco do .env.docker não publica duas vezes a mesma porta
      Dado um .env feito do .env.docker com as linhas comentadas de COMPOSE_PROJECT_NAME, APP_URL, TRUSTED_PROXIES, TRAEFIK_HOST, TRAEFIK_REDE e FORWARD_* descomentadas
      E TRAEFIK_HOST "dev.exemplo.test" quando a linha descomentada vier vazia
      Quando o operador roda "docker compose --profile app config" com o exemplo copiado na raiz
      Então nenhum par de publicações tem o mesmo "published" com "host_ip" sobreposto (vazio e 0.0.0.0 sobrepõem qualquer IP)
      E, como o bloco traz TRUSTED_PROXIES "*", toda publicação do nginx tem "host_ip" "127.0.0.1"

    Cenário: [CT-39] o .env.docker copiado como sempre, sem o override, não liga o Traefik
      Dado uma pasta com os arquivos de Compose rastreados da raiz do kit, o exemplo em "docker/traefik/" e o .env.docker copiado verbatim como .env
      Quando o operador roda "docker compose --profile app config" na pasta
      Então nenhum serviço tem label que comece com "traefik."
      E a configuração não declara rede de topo além da "default" do projeto

    Cenário: [CT-40] com as quatro FORWARD_* em loopback, toda publicação fica em 127.0.0.1
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST "dev.exemplo.test", FORWARD_APP_PORT "127.0.0.1:8090", FORWARD_DB_PORT "127.0.0.1:5433", FORWARD_REDIS_PORT "127.0.0.1:6380" e FORWARD_REVERB_PORT "127.0.0.1:8190"
      Quando o operador roda "docker compose --profile app config"
      Então toda publicação de nginx, pgsql, redis e reverb tem "host_ip" "127.0.0.1"
      E os "published" são 8090, 5433, 6380 e 8190, respectivamente
```

`"exceto os já resolvidos pelo próprio Compose"` em CT-24 é a lista vazia hoje — se algum nome
precisar dela, a exceção é escrita no caso com o motivo. `[CT-39]` copia o `.env.docker` **sem
editar** (as `DB_*` ativas que ele já traz bastam): é o caminho de todo projeto que já usa o kit, e
um `COMPOSE_FILE` ativo nele ligaria o exemplo sem cópia nenhuma. As três `FORWARD_*` novas de CT-23
ficam no bloco do multiambiente, ao lado de `FORWARD_APP_PORT` (P-09: as quatro são obrigatórias e
distintas por ambiente). A última linha de `[CT-25]` é a recomendação de Q11 em configuração: o bloco
que sugere `TRUSTED_PROXIES=*` tem de sugerir também a porta do nginx em loopback — com ela em
`0.0.0.0`, quem alcança a porta sem passar pelo Traefik forja `X-Forwarded-*`. Retira a antiga lacuna
L7 (ADV2-08).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M55 | `TRUSTED_PROXIES=*` como linha ativa no `.env.docker` (todo usuário de Docker passa a confiar em qualquer chamador) | CT-23 | linha ativa encontrada |
| M56 | `COMPOSE_PROJECT_NAME=projeto3-dev` ativo no `.env.docker` (renomeia a stack de quem copia) | CT-23 | linha ativa encontrada |
| M57 | `APP_URL=https://…` como linha ativa no fim do `.env.docker` (vence o `http://localhost:8000` — o leitor fica com a última) | CT-24 | última linha ativa de `APP_URL` ≠ `http://localhost:8000` |
| M58 | `TRAEFIK_REDE` consumida no exemplo e não oferecida | CT-24, CT-23 | nome sem linha no `.env.docker` |
| M59 | sugestão `FORWARD_APP_PORT=127.0.0.1:8090` com o Reverb no default 8090 (RQ-16, nota ²) | CT-25 | `127.0.0.1:8090` (nginx) e `:8090` (reverb, host_ip vazio) sobrepostos |
| M113 | `COMPOSE_FILE=docker-compose.yml:docker/traefik/docker-compose.override.yml` ativo no `.env.docker` "para dispensar a cópia" (ADV-07) | CT-39, CT-23 | label `traefik.enable` no nginx sem override na raiz / linha ativa de `COMPOSE_FILE` |
| M114 | `COMPOSE_PROFILES=app` ativo no `.env.docker` (muda o `docker compose up` de quem copia) (ADV-07) | CT-23 | linha ativa de `COMPOSE_PROFILES` |
| M115 | bloco do multiambiente sugere só `FORWARD_APP_PORT` (o levantamento diz "opcional") — o segundo ambiente não sobe (ADV-09) | CT-23 (linhas `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_REVERB_PORT`) | linha comentada ausente |
| M116 | publicação com IP fixo no override ou no base (`'0.0.0.0:${FORWARD_DB_PORT:-5432}:5432'`, ou um `ports` a mais no reverb) — o bind de administração em loopback deixa de valer (ADV-23) | CT-40 | publicação de `pgsql` (ou a extra) com `host_ip` `0.0.0.0`/vazio |

| M154 | bloco do `.env.docker` sugere `TRUSTED_PROXIES=*` com `FORWARD_APP_PORT=8090` (sem loopback) — a porta aberta aceita `X-Forwarded-*` de qualquer um (ADV2-08, Q11) | CT-25 | publicação do nginx com `host_ip` vazio/`0.0.0.0` |

Estouro do teto (padrão: 5): M113…M116 — revisão adversarial (ADV-07, ADV-09, ADV-23); M154 — 2ª rodada (ADV2-08).

---

## Regra R14 — Três ambientes com a matriz do requisito não disputam porta nem nome

> `RQ-06`, `RQ-16`, `P-09` · perfil **padrão** · técnica: **valor literal do requisito**

```gherkin
  Regra: com COMPOSE_PROJECT_NAME e portas distintas, os três ambientes convivem no host

    Cenário: [CT-26] dev, teste e homol com a matriz sugerida não colidem
      Dado três .env com COMPOSE_PROJECT_NAME "projeto3-dev", "projeto3-teste" e "projeto3-homol", TRAEFIK_HOST distinto em cada um
      E FORWARD_APP_PORT 8090/9090/8080, FORWARD_DB_PORT 5433/5434/5435, FORWARD_REDIS_PORT 6380/6381/6382 e FORWARD_REVERB_PORT 8190/8191/8192
      Quando o operador roda "docker compose --profile app config" com o exemplo copiado na raiz, uma vez por ambiente
      Então nenhuma porta do host é publicada por dois serviços, no mesmo ambiente ou entre ambientes
      E os três "name" de topo são distintos
      E "networks.default.name" e todo "volumes.*.name" são disjuntos entre os três ambientes
      E nenhum serviço declara "container_name"
```

Os números são os **literais** da matriz do requisito (RQ-16), não os da página.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M60 | porta fixa sem `FORWARD_*` num serviço do profile `app` do base (ex.: `'8091:8090'`) | CT-26 | a mesma porta publicada nos três ambientes |
| M61 | override fixa `name:` de topo | CT-26 (e CT-06) | três `name` iguais |
| M62 | dois serviços usando a mesma chave `FORWARD_*` (ex.: reverb lendo `FORWARD_APP_PORT`) | CT-26 | colisão dentro do mesmo ambiente |
| M117 | override declara `volumes.app-storage.name: app-storage` ou `networks.default.name` fixo — os três ambientes compartilham dados (ADV-12) | CT-26 | o mesmo `name` de volume/rede nos três |

---

## Regra R15 — A página existe nos dois idiomas e é alcançável; o CHANGELOG registra

> `RQ-01`, `RQ-03` · perfil **padrão** · técnica: **EP por idioma**. Todos os casos: `->skip(fn (): bool => ! naArvoreDoKit(), …)`.

```gherkin
  Regra: a página nova é encontrada pelo README, pelo índice de Operação e pelo site

    Esquema do Cenário: [CT-27] a página do idioma existe e todos os caminhos levam a ela
      Dado a árvore do kit
      Quando o teste procura a página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Então paginasDoSite("<idioma>") tem a página, com "title" e "description" no front-matter
      E o <readme> tem link que termina em "/<idioma>/operacao/deploy-docker-multiambiente.html", dentro da seção "## Docker"
      E "docs/<idioma>/operacao/index.md" tem link para "deploy-docker-multiambiente"
      E "site/sidebar.json" tem o slug "operacao/deploy-docker-multiambiente" e existe o stub "site/public/<idioma>/operacao/deploy-docker-multiambiente.html"

      Exemplos:
        | idioma | readme        |
        | pt     | README.md     |
        | en     | README.en.md  |

    Cenário: [CT-28] o CHANGELOG registra a entrega
      Dado o CHANGELOG.md inteiro
      Quando o teste procura a entrega
      Então ele cita "docker/traefik/docker-compose.override.yml" e "TRUSTED_PROXIES"
```

`[CT-28]` assere sobre o **arquivo inteiro**, nunca sobre a seção do topo (`.ai/rules/testes.md`:
a asserção sobre o topo expira na próxima entrega). É a parte observável de RQ-03 (a release registra
a entrega); branch, PR e tag ficam com o quality gate.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M63 | só a página pt escrita | CT-27 (linha en) | `paginasDoSite('en')` sem a página |
| M64 | `README.en.md` sem a subseção | CT-27 (linha en) | link ausente na seção `## Docker` |
| M65 | `node converter.mjs` não rodado (sidebar e stubs desatualizados) | CT-27 | slug ausente / stub inexistente |
| M66 | CHANGELOG sem a entrada | CT-28 | `docker/traefik/docker-compose.override.yml` ausente |

---

## Regra R16 — A página ensina o mecanismo central, o encaixe no Traefik e o passo a passo

> `RQ-06`, `RQ-07`, `RQ-08`, `RQ-09`, `RQ-11`, `P-13` (reescrita), `P-14`, `P-21`, `P-25` · perfil **padrão** · técnica: **EP por âncora** (na mesma frase, sem negação, onde o sentido importa) + **ausência** da instrução que P-13 reescrita desmente. `skip` fora da árvore.

```gherkin
  Regra: o operador consegue montar um ambiente lendo só a página

    Esquema do Cenário: [CT-29] a página do idioma carrega cada âncora do procedimento
      Dado a página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste procura a âncora "<âncora>"
      Então a encontra conforme "<oráculo>"

      Exemplos:
        | âncora                       | oráculo                                                                                  | origem |
        | nome de projeto por ambiente | ao menos 3 valores distintos em "^COMPOSE_PROJECT_NAME=([a-z0-9_-]+)$" (multilinha)       | RQ-06  |
        | um .env por ambiente         | os 3 valores aparecem em blocos de .env distintos                                         | RQ-06  |
        | rede e label obrigatórios    | "traefik.docker.network=" e "external: true"                                              | RQ-07, RQ-08 |
        | labels do router             | "entrypoints=websecure", "tls=true", "loadbalancer.server.port=80"                        | RQ-07, RQ-09 |
        | cópia do exemplo             | uma linha de bloco de código casa "cp\s+docker/traefik/docker-compose\.override\.yml\s+(\./)?docker-compose\.override\.yml(\s*$\|\s*&&\|\s*;)"; o caminho de origem existe no kit | RQ-11, P-03 |
        | proxies confiáveis           | "TRUSTED_PROXIES="                                                                         | P-02   |

    Esquema do Cenário: [CT-41] a seção de TRUSTED_PROXIES diz como o cache e o "*" se comportam
      Dado a seção de TRUSTED_PROXIES da página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste lê as frases da seção e as da página
      Então uma mesma frase da seção contém "<cache>", "TRUSTED_PROXIES" e "<refazer>", sem "não", "nem", "not" ou "never" nas 3 palavras antes de cada âncora
      E nenhuma frase da página contém "<ambiente>" junto com "TRUSTED_PROXIES"
      E a seção contém "ipv4_address" e "<ip fixo>"
      E uma mesma frase da seção contém "`*`", "<rede>" e "container"

      Exemplos:
        | idioma | cache                                 | refazer                              | ambiente             | ip fixo  | rede    |
        | pt     | config:cache  ·  configuração em cache | refa  ·  de novo  ·  novamente       | ambiente do processo | IP fixo  | rede    |
        | en     | config:cache  ·  cached configuration  | again  ·  re-run  ·  rerun  ·  rebuild | process environment | fixed IP | network |

    Esquema do Cenário: [CT-47] a seção do Traefik avisa que nome de serviço é global na rede compartilhada
      Dado a página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste lê a seção (secoesDoMarkdown) que contém "traefik.docker.network"
      Então essa seção contém "`app`", "`reverb`", "my-network" e "DevOps"
      E uma mesma frase da seção contém "nginx" e "app"
      E uma mesma frase da seção contém "reverb", "pgsql" e "redis"

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

`[CT-29]`, linha "cópia do exemplo": na célula, os `\|` do grupo final são a alternância da regex
escapada para a tabela — a regex é `(\s*$|\s*&&|\s*;)`, e ancora o fim do destino (ADV2-22).
`[CT-41]` foi **reescrito no v4**: o aviso de v3 ("com cache, a chave vem do ambiente do processo")
nasceu da leitura no bootstrap, que RD-01 mostrou nunca ler o `.env`; P-13 reescrita diz o contrário —
com cache vale o valor cacheado, como toda chave, e mudar a chave pede refazer o cache. A segunda
linha do `Então` impede a página de manter a instrução antiga. **Seção de TRUSTED_PROXIES**: a seção
(`secoesDoMarkdown`, com as subseções) cujo título casa `(?i)TRUSTED_PROXIES|proxies confiáveis|trusted
proxies` — exatamente uma por página. As duas últimas linhas são P-21 (CR-03): `*` confia em qualquer
container da rede compartilhada do Traefik, o loopback não isola dela, e a página recomenda o IP fixo
do Traefik (`ipv4_address`) quando o DevOps o fornecer; o risco aceito é a lacuna L7, reaberta. A sessão
apontou CT-42 para estas âncoras; ficaram em CT-41 porque CT-42 é recortado à seção da matriz, e o
aviso vive na seção da chave. Nas colunas `cache` e `refazer`, basta um dos termos (separados por `·`);
`refa` casa "refazer"/"refaça". **Frase** (CT-41, CT-42): trecho de
prosa — fora de bloco de código e de linha de tabela — entre terminadores `.`, `!` ou `?` seguidos de
espaço ou fim de linha, ou entre linhas em branco; o ponto de `.env` e o dois-pontos de
`config:cache` não terminam frase. A janela de negação são as 3 palavras imediatamente antes da
âncora, sem diferenciar caixa (ADV2-28): "seção" basta para presença, não para sentido — "não precisa
de `TRUSTED_PROXIES` no ambiente do processo" passava. `[CT-47]` é a documentação de P-14 (Q12): o
DNS do Docker resolve nome de serviço em **todas** as redes do container, e outro `app` na rede do
Traefik pode receber o `fastcgi_pass app:9000`; a página manda confirmar com o DevOps. O runtime é a
lacuna L10. As duas últimas linhas são P-25 (CR-09): o que resolve pela rede compartilhada é o `nginx`
resolvendo `app` e, se o `reverb` entrar na rede, o `reverb` resolvendo `pgsql` e `redis` — o aviso
que só nomeia `app` e `reverb` como "nomes em risco" erra o lado da resolução.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M67 | a página cita `COMPOSE_PROJECT_NAME` sem exemplo de três ambientes | CT-29 (linha nome de projeto) | menos de 3 valores distintos |
| M68 | a página omite `traefik.docker.network` (o exemplo do DevOps não tinha) | CT-29 | âncora ausente |
| M69 | a página aponta um caminho de exemplo que não existe (renomeado no meio) | CT-29 (linha cópia do exemplo) | `file_exists(base_path(caminho))` falso |
| M70 | a página não diz para copiar para a raiz | CT-29 | regex do `cp` não casa |
| M118 | `cp` invertido no bloco (`cp docker-compose.override.yml docker/traefik/`) ou destino em `docker/` — os dois nomes aparecem, a cópia não liga nada (ADV-26) | CT-29 (linha cópia do exemplo) | a regex `cp\s+docker/traefik/…\s+(\./)?docker-compose\.override\.yml` não casa |
| M119 | página sem o aviso de `config:cache` — quem muda a chave com a configuração em cache não vê efeito e não sabe por quê (ADV-19, P-13 reescrita) | CT-41 | nenhuma frase com `config:cache`/"configuração em cache", `TRUSTED_PROXIES` e `refa…`/`again` |

| M155 | `cp docker/traefik/docker-compose.override.yml docker-compose.override.yml.bak` (ou `.example`) — o destino casa como prefixo e a cópia não liga nada (ADV2-22) | CT-29 (linha cópia do exemplo) | a regex ancorada no fim do comando não casa |
| M156 | aviso invertido: "com `config:cache`, mudar `TRUSTED_PROXIES` **não** pede refazer o cache" (ADV2-28, reescrito com P-13) | CT-41 | negação nas 3 palavras antes de uma âncora |
| M157 | página sem o aviso de nome global na rede compartilhada — o operador põe o `app` na `my-network` ou não pergunta ao DevOps (ADV2-33, Q12) | CT-47 | seção do Traefik sem `` `app` ``, `` `reverb` `` ou `DevOps` |
| M185 | a página mantém a instrução de v3: "com `config:cache`, `TRUSTED_PROXIES` precisa estar no ambiente do processo" — que P-13 reescrita desmente (RD-01) | CT-41 | frase com "ambiente do processo"/"process environment" e `TRUSTED_PROXIES` |
| M186 | a página diz que a chave é lida "no bootstrap" e que o cache não a afeta — o operador muda a chave e não refaz o cache (P-13) | CT-41 | nenhuma frase com o cache, `TRUSTED_PROXIES` e o verbo de refazer |
| M187 | a seção recomenda `*` "com o nginx em `127.0.0.1`" como suficiente, sem o IP fixo do Traefik (CR-03) | CT-41 | `ipv4_address` ou "IP fixo"/"fixed IP" ausente da seção |
| M188 | a seção não diz que `*` confia nos outros containers da rede compartilhada — o risco aceito fica invisível (CR-03, P-21) | CT-41 | nenhuma frase com `` `*` ``, "rede"/"network" e "container" |
| M189 | o aviso de DNS nomeia `app` e `reverb` como os nomes em risco, sem dizer que o `reverb` na rede resolve `pgsql`/`redis` (CR-09) | CT-47 | nenhuma frase com `reverb`, `pgsql` e `redis` |

Estouro do teto (padrão: 5): M118, M119 — revisão adversarial (ADV-19, ADV-26); M155…M157 — 2ª rodada (ADV2-22, ADV2-28, ADV2-33); M185…M189 — step 9 (RD-01, CR-03, CR-09).

---

## Regra R17 — A página cobre portas, matriz, o build do Reverb e as duas rotas

> `RQ-10`, `RQ-12`, `RQ-14`, `RQ-16`, `P-06`, `P-09`, `P-20`, `P-22` · perfil **padrão** · técnica: **valor literal do requisito** + âncora com negação **exigida** (P-20). `skip` fora da árvore.

```gherkin
  Regra: a página traz a matriz do requisito com as quatro chaves obrigatórias, o build do Reverb e as duas rotas

    Esquema do Cenário: [CT-30] a matriz de portas, o build e as rotas do Reverb estão na página
      Dado a página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste procura "<item>"
      Então a encontra conforme "<oráculo>"

      Exemplos:
        | item                       | oráculo                                                                        | origem |
        | linha de app da matriz     | linha de tabela "^\|.*FORWARD_APP_PORT.*\|C(8090)\|C(9090)\|C(8080)\|"          | RQ-16  |
        | linha de banco             | "^\|.*FORWARD_DB_PORT.*\|C(5433)\|C(5434)\|C(5435)\|"                           | RQ-16  |
        | linha de cache             | "^\|.*FORWARD_REDIS_PORT.*\|C(6380)\|C(6381)\|C(6382)\|"                        | RQ-16  |
        | linha do Reverb            | "^\|.*FORWARD_REVERB_PORT.*\|C(8190)\|C(8191)\|C(8192)\|"                       | RQ-16  |
        | nota do 8090               | uma linha de prosa (fora de bloco de código e de tabela) com "FORWARD_REVERB_PORT" e "8090" | RQ-16  |
        | bind de administração      | "FORWARD_APP_PORT=127.0.0.1:"                                                   | RQ-10, P-06 |
        | rota do Reverb pelo Traefik | "-reverb" num label de router                                                  | RQ-12  |
        | rota do Reverb por porta   | "FORWARD_REVERB_PORT=" fora da tabela                                           | RQ-12  |
        | VITE do Reverb no build    | um bloco de código com "VITE_REVERB_HOST=", "VITE_REVERB_PORT=443" e "VITE_REVERB_SCHEME=https" | RQ-14  |
        | rebuild ao mudar o VITE    | "--build" na mesma seção desse bloco                                            | RQ-14  |
        | host do Reverb atrás do Traefik | no bloco com "VITE_REVERB_PORT=443", o valor de "VITE_REVERB_HOST=" não é "localhost" nem "127.0.0.1" e é igual a um valor de "TRAEFIK_HOST=" ou ao host de um "APP_URL=https://" da página | RQ-14, RQ-17 |
        | REVERB_* não mudam         | na seção desse bloco: uma mesma frase com "REVERB_HOST", "REVERB_PORT" e "REVERB_SCHEME" (cada uma sem "VITE_" colado antes) e "não" (pt) / "not" (en) | P-20   |
        | REVERB_* sem TLS no .env   | nenhuma linha de bloco de código da página casa "(?<![A-Z_])REVERB_SCHEME=https" nem "(?<![A-Z_])REVERB_PORT=443" | P-20   |
        | portas dos profiles ai/mail | "FORWARD_LLAMA_PORT" na seção da matriz (a de CT-42)                          | P-22, RQ-16 |

    Esquema do Cenário: [CT-42] a matriz diz que as quatro portas são obrigatórias e distintas, e que vão para o loopback
      Dado a seção da matriz — a seção (secoesDoMarkdown) que contém a linha com "FORWARD_APP_PORT", "8090", "9090" e "8080" — da página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste lê as frases da seção e as células da tabela da matriz
      Então uma mesma frase contém "<obrigatória>" e "<distinta>", sem "não", "nem", "not" ou "never" nas 3 palavras antes de cada uma
      E uma mesma frase contém "127.0.0.1" e "Traefik"
      E nenhuma célula da tabela da matriz contém "<opcional>"

      Exemplos:
        | idioma | obrigatória  | distinta   | opcional  |
        | pt     | obrigatóri   | distint    | opcional  |
        | en     | mandatory    | distinct   | optional  |
```

`C(N)`, na coluna oráculo de CT-30, é a célula numérica `\s*(127\.0\.0\.1:)?N[¹²]?\s*`: a célula pode
trazer o loopback (P-06/P-09: a página ensina `127.0.0.1:8090`) e o marcador de nota do requisito
(`8190¹`). O número continua o **literal** do requisito. As linhas de P-20 (RD-04/CR-02): na rota do
Reverb pelo Traefik só os `VITE_REVERB_*` (o que o navegador usa) mudam; `REVERB_HOST`/`REVERB_PORT`/
`REVERB_SCHEME` são do `app` falando com `reverb:8090` em HTTP, e `REVERB_SCHEME=https` quebra o
broadcast. O lookbehind `(?<![A-Z_])` é obrigatório: `VITE_REVERB_PORT=443` **deve** estar na página
(linha "VITE do Reverb no build") e contém `REVERB_PORT=443`. A linha de P-22 (CR-06): com o profile
`ai`, o `llamacpp` publica 8080, que é a porta do app da homol na matriz do requisito.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M71 | matriz transcrita com valor trocado (ex.: homol 8081) | CT-30 | regex da linha não casa |
| M72 | nota do 8090 omitida | CT-30 | nenhuma linha de prosa com `FORWARD_REVERB_PORT` e `8090` |
| M73 | "porta opcional" sem o como (sem bind em loopback) | CT-30 | `FORWARD_APP_PORT=127.0.0.1:` ausente |
| M74 | só a rota pelo Traefik documentada | CT-30 (linha rota por porta) | `FORWARD_REVERB_PORT=` ausente fora da tabela |
| M120 | página explica o `ARG` sem os valores atrás do Traefik (ou com `VITE_REVERB_PORT=8090`), ou sem dizer que mudá-los pede `--build` (ADV-24) | CT-30 (linhas VITE do Reverb, rebuild) | bloco sem `VITE_REVERB_PORT=443` / `--build` ausente da seção |
| M121 | nota do 8090 só como comentário dentro de bloco ou célula da tabela (ADV-27) | CT-30 (linha nota do 8090) | nenhuma linha de **prosa** com as duas âncoras |
| M122 | matriz transcrita com "*(opcional¹)*" do levantamento (ADV-32) | CT-42 | célula com `opcional`/`optional` |
| M123 | seção da matriz sem dizer que as quatro são obrigatórias e distintas (ADV-32) | CT-42 | `obrigatóri`/`mandatory` ausente da seção |

| M158 | bloco do build com `VITE_REVERB_HOST=localhost` e `VITE_REVERB_PORT=443` — copiado do `.env` local, o Echo do navegador abre o socket no `localhost` de quem acessa (ADV2-21) | CT-30 (linha host do Reverb) | `VITE_REVERB_HOST` = `localhost` / diferente de todo `TRAEFIK_HOST` da página |
| M159 | seção da matriz diz "não é obrigatório que sejam distintas" ou separa as âncoras em frases que se contradizem (ADV2-28) | CT-42 | negação nas 3 palavras antes de `obrigatóri`/`distint`, ou as duas em frases diferentes |
| M160 | seção da matriz repete a nota ¹ do levantamento ("atrás do Traefik nenhuma porta precisa ser publicada") sem dizer que o base publica sempre e que ela vai para `127.0.0.1` (ADV2-31, P-09) | CT-42 | nenhuma frase com `127.0.0.1` e `Traefik` |
| M190 | a página manda `REVERB_SCHEME=https` e `REVERB_PORT=443` no `.env` para a rota pelo Traefik — o `app` fala TLS com `reverb:8090` (RD-04/CR-02) | CT-30 (linha REVERB_* sem TLS no .env) | linha de bloco com `REVERB_SCHEME=https` |
| M191 | a página troca só os `VITE_REVERB_*`, mas não diz que os `REVERB_*` ficam — o operador "completa o par" (P-20) | CT-30 (linha REVERB_* não mudam) | nenhuma frase com as três chaves e "não"/"not" |
| M192 | a matriz sem as portas dos profiles `ai`/`mail` — a homol com `--profile ai` não sobe (8080 do llama) (CR-06) | CT-30 (linha portas dos profiles) | `FORWARD_LLAMA_PORT` ausente da seção da matriz |

Estouro do teto (padrão: 5): M120…M123 — revisão adversarial (ADV-24, ADV-27, ADV-32); M158…M160 — 2ª rodada (ADV2-21, ADV2-28, ADV2-31); M190…M192 — step 9 (RD-04/CR-02, CR-06).

---

## Regra R18 — A página apresenta as opções A–D e as armadilhas

> `RQ-13`, `RQ-15`, `P-07`, `P-22` · perfil **padrão** · técnica: **EP por âncora**. `skip` fora da árvore.

```gherkin
  Regra: a página recomenda a Opção A e explica B, C, D e as armadilhas

    Esquema do Cenário: [CT-31] cada opção e cada armadilha tem seu lugar na página
      Dado as seções de "docs/<idioma>/operacao/deploy-docker-multiambiente.md" (secoesDoMarkdown)
      Quando o teste procura "<item>"
      Então o encontra conforme "<oráculo>"

      Exemplos:
        | item                   | oráculo                                                                                          | origem |
        | Opção A recomendada    | um título com "A" de opção e, na mesma seção, "recomendad" (pt) / "recommended" (en)               | RQ-13  |
        | fluxo de atualização   | na seção da Opção A: "git pull" antes de "--profile app up -d --build" (ou "./deploy_docker_local.sh") | RQ-13  |
        | opções B, C e D        | um título para cada uma                                                                           | RQ-13, P-07 |
        | equilíbrio da D        | na seção da D: "pgsql"/"redis", e uma mesma frase com "llama", "mailpit" e "compartilhad" (pt) / "shared" (en) | RQ-13  |
        | armadilhas             | "SESSION_COOKIE", "APP_KEY", "APP_URL=https://" e "APP_DEBUG=false"                               | RQ-15  |
        | sem SESSION_COOKIE customizado | nenhum bloco de código da página contém "SESSION_COOKIE="                                 | RQ-15  |
        | APP_URL do ambiente    | no bloco de código de .env da página (o que tem "TRAEFIK_HOST="), "APP_URL=" é "https://" seguido do valor de "TRAEFIK_HOST=" do mesmo bloco | RQ-15, RQ-17 |
        | sem debug ligado       | nenhum bloco de código da página contém "APP_DEBUG=true"                                         | RQ-15  |
        | receita da D           | na seção da D: um bloco de código com "networks:" e "LLAMACPP_EMBED_URL"; algum bloco com "environment:" e "LLAMACPP_URL"; e algum bloco declara uma rede `external: true` cujo nome não é "my-network" nem interpola "TRAEFIK_REDE" (hoje `ia-compartilhada`) | RQ-13, P-07, P-22 |
```

A página tem **um** bloco de `.env` (o do dev); as linhas "APP_URL do ambiente" e "sem debug ligado"
valem para ele e para qualquer outro que vier (ADV2-16). A linha "receita da D" foi reescrita no v4
(P-22, CR-05): a receita de v3 punha `LLAMACPP_URL` no `environment:` do consumidor, mas o `app` não
estava em rede nenhuma com o llama de outro projeto, e as embeddings continuavam locais. O nome da rede
é do plano; o oráculo exige só que não seja a do Traefik.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M75 | Opção A apresentada sem a recomendação | CT-31 | "recomendad"/"recommended" fora da seção da A |
| M76 | Opção D omitida "porque o kit não entrega arquivo para ela" | CT-31 | título da D ausente |
| M77 | armadilhas sem `APP_DEBUG=false` ou sem `SESSION_COOKIE` | CT-31 | âncora ausente |
| M78 | sem o fluxo de atualização (só o primeiro `up`) | CT-31 | `git pull` ausente da seção da A |
| M124 | `.env` de exemplo da página com `SESSION_COOKIE=projeto3_dev` "por garantia" — contradiz RQ-15 (ADV-31) | CT-31 (linha sem SESSION_COOKIE customizado) | bloco de código com `SESSION_COOKIE=` |
| M125 | D com llama e mailpit citados **por ambiente** (ou só "pgsql e redis por ambiente", sem dizer o que se compartilha) (ADV-31) | CT-31 (linha equilíbrio da D) | nenhuma frase com `llama`, `mailpit` e `compartilhad`/`shared` |

| M161 | bloco de `.env` da página com `APP_URL=http://localhost:8000` (copiado do `.env.docker`) ou com host diferente do `TRAEFIK_HOST` do bloco (ADV2-16) | CT-31 (linha APP_URL do ambiente) | `APP_URL` ≠ `https://` + `TRAEFIK_HOST` |
| M162 | bloco de `.env` da página com `APP_DEBUG=true` "porque é o dev" — o exemplo é copiado para teste e homol (ADV2-16) | CT-31 (linha sem debug ligado) | bloco com `APP_DEBUG=true` |
| M163 | Opção D só em prosa, sem a receita de `environment:` no override (P-07) (ADV2-32) | CT-31 (linha receita da D) | nenhum bloco com `environment:` e `LLAMACPP_URL` na seção da D |
| M193 | receita da D só com `environment:` apontando `LLAMACPP_URL` para o llama de outro projeto, sem rede comum — o nome não resolve (CR-05) | CT-31 (linha receita da D) | nenhum bloco com `networks:` e `LLAMACPP_EMBED_URL` |
| M194 | receita sem `LLAMACPP_EMBED_URL` — as embeddings continuam no llama local, que o profile `ai` sobe de novo (CR-05) | CT-31 (linha receita da D) | `LLAMACPP_EMBED_URL` ausente da seção |
| M195 | receita compartilha o llama pela rede do Traefik (`my-network`) — mistura a IA com o roteamento de todos os projetos (P-22) | CT-31 (linha receita da D) | nenhuma rede `external: true` além de `my-network`/`TRAEFIK_REDE` |

Estouro do teto (padrão: 5): M124, M125 — revisão adversarial (ADV-31); M161…M163 — 2ª rodada (ADV2-16, ADV2-32); M193…M195 — step 9 (CR-05).

---

## Regra R19 — pt e en são espelho

> `RQ-01` · perfil **padrão** · técnica: **contrato entre arquivos**. `skip` fora da árvore.

```gherkin
  Regra: a página en não fica para trás da pt

    Cenário: [CT-32] as duas páginas têm a mesma estrutura e os mesmos identificadores
      Dado as páginas pt e en de "operacao/deploy-docker-multiambiente.md"
      Quando o teste compara as duas
      Então o número de títulos "## " e "### " é o mesmo
      E o conjunto de tokens "[A-Z][A-Z0-9_]{2,}=" e "traefik\.[a-z0-9.${}:_-]+" é o mesmo nas duas
      E o número de blocos de código é o mesmo
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M79 | en com uma seção a menos (a das armadilhas não traduzida) | CT-32 | contagem de títulos difere |
| M80 | en com chave "traduzida" (`TRAEFIK_NETWORK=`) | CT-32 | conjuntos de tokens diferem |
| M81 | en sem o bloco YAML do override | CT-32 | contagem de blocos de código difere |
| M82 | en com label antigo (`traefik.docker.network` ausente, só na pt) | CT-32 | conjunto de tokens `traefik.` difere |

---

## Regra R20 — O bundle do kit continua sem Echo

> `P-01`, `RQ-04` · perfil **padrão** · técnica: **EP por arquivo** (ausência no texto ativo). Nasce da revisão adversarial (ADV-21): o `ARG` de R8 é inerte **porque** o kit não tem Echo (P-01); se a entrega ligar Echo "para o arg ter efeito", o bundle de todo projeto muda.

```gherkin
  Regra: a entrega não acrescenta cliente WebSocket ao JavaScript do kit

    Cenário: [CT-43] nenhum arquivo do bundle importa Echo nem lê VITE_REVERB_*
      Dado os arquivos de "resources/js", sem as linhas de comentário, e o package.json do kit
      Quando o teste procura Echo e as chaves do Reverb
      Então nenhum arquivo importa "laravel-echo" nem lê "import.meta.env.VITE_REVERB_"
      E nem "dependencies" nem "devDependencies" do package.json têm "laravel-echo" ou "pusher-js"
      E config('filament.broadcasting.echo') é vazio ou nulo
      E, se "config/filament.php" existir, o texto ativo dele não contém "'echo'" junto com "VITE_"
```

`[CT-43]` lê `resources/js`, `package.json` e `config/`, que viajam com o kit — sem `skip`. As duas
últimas linhas fecham a outra porta de P-01: o Filament liga Echo pela configuração
`broadcasting.echo`, sem nenhum `import` no bundle (ADV2-12).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M126 | `resources/js/echo.js` com `new Echo({ wsHost: import.meta.env.VITE_REVERB_HOST, … })` importado pelo `app.js`, "para o build-arg servir para algo" | CT-43 | `import.meta.env.VITE_REVERB_` / `laravel-echo` no texto ativo |
| M127 | `laravel-echo` e `pusher-js` acrescentados ao `package.json` "para o projeto que quiser" | CT-43 | chave `laravel-echo` em `devDependencies` |
| M164 | `config/filament.php` publicado com `'broadcasting' => ['echo' => ['key' => env('VITE_REVERB_APP_KEY'), …]]` — o Filament liga Echo sem tocar o bundle (ADV2-12) | CT-43 | `config('filament.broadcasting.echo')` não vazio / `'echo'` com `VITE_` no texto ativo |

---

## Regra R21 — O item descartado vai para o log, nunca para o Symfony

> `P-18` · perfil **padrão** (área D) · técnica: **rastreio de efeito** — o QUE: canal `configuracoes`, nível `warning`, mensagem com o prefixo `[KitServiceProvider@confiarNosProxiesDoEnv]` e o item no contexto; as direções: aconteceu (um por item) / **não** aconteceu com o canal de pé e a chave definida / uma vez só por item. O "nunca para o Symfony" é observado no HTTP pelas linhas P-18 de CT-21 (`/16x` → 200) e CT-22 (`17x…,127.0.0.1` → honrado). Costura G7. Nasce do step 9 (RD-03/CR-04).

```gherkin
  Regra: cada item de TRUSTED_PROXIES que o kit descarta vira um aviso no log de configurações

    Esquema do Cenário: [CT-50] cada item descartado gera um aviso no canal configuracoes, e só ele
      Dado TRUSTED_PROXIES efetivo <valor efetivo> fixado no ambiente e a aplicação recriada
      E o canal de log "configuracoes" espiado
      Quando o kit aplica os proxies confiáveis no boot
      Então o canal recebe exatamente <n> chamadas "warning"
      E cada uma tem a mensagem começando por "[KitServiceProvider@confiarNosProxiesDoEnv]" e, no contexto, um item de <descartados> — cada item em uma chamada só

      Exemplos:
        | valor efetivo                       | n | descartados              | # partição                                 |
        | '10.0.0.1,REMOTE_ADDR,17x.18.0.1'   | 2 | REMOTE_ADDR, 17x.18.0.1  | coringa e malformado em lista: aconteceu, uma vez cada |
        | '10.0.0.1,172.18.0.0/16'            | 0 | —                        | lista limpa: não aconteceu, com o canal de pé |
        | '*'                                 | 0 | —                        | `*` sozinho vale "todos": não aconteceu    |
```

O `Dado` declara um alvo que existe (o canal `configuracoes`, espiado) e uma chave **definida** nas
linhas de zero avisos — chave ausente não provaria não-efeito (`SKILL.md` §Passo 3, *Não-efeito*). O
"exatamente `n`" é a direção "uma vez só": um aviso duplicado (no `doEnv()` e de novo no boot) dá 4. O
arnês de G7 conta só a re-execução do passo de boot, depois do spy (o refresh limpa as fachadas antes
do boot). Regra de rastreio de efeito consome o teto (`SKILL.md` §Passo 7): um Esquema, três direções.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M196 | descarte silencioso — o operador nunca sabe que o item malformado foi ignorado (P-18) | CT-50 (linha coringa e malformado) | 0 chamadas; o caso exige 2 |
| M197 | um aviso só, com a lista inteira dos descartados | CT-50 (linha coringa e malformado) | 1 chamada; o caso exige 2, cada item na sua |
| M198 | aviso no canal default (`Log::warning`) em vez de `configuracoes` | CT-50 (linha coringa e malformado) | o canal `configuracoes` recebe 0 |
| M199 | aviso também quando nada é descartado ("TRUSTED_PROXIES aplicado: …") em nível `warning` | CT-50 (linhas lista limpa e `*` sozinho) | ≥ 1 chamada; o caso exige 0 |
| M200 | aviso emitido duas vezes por item (no `doEnv()` e no boot, ou no `register()` e no `boot()`) | CT-50 (linha coringa e malformado) | 4 chamadas; o caso exige 2 |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota com `{id}` — o plano declara "Rotas: nenhuma"; a rota de G6 é declarada só no teste | — |
| Autorização exercida na ação | não se aplica: nenhuma policy ou permission nova | — |
| Idempotência | não se aplica: nenhuma escrita da aplicação; `docker compose config` é leitura | — |
| Concorrência | não se aplica: nenhum contador ou limite | — |
| **Fronteira no ponto de entrada** (valor do `.env`) | CT-20 (todas as partições), CT-49, CT-21, CT-22, CT-48 | G5, G6 |
| **Valor malformado passado a biblioteca que não valida** (Symfony `IpUtils`, P-18) | CT-20 (`/16x`, `17x…`, `/33`, `/129`), CT-21 (`/16x` → 200), CT-22 (`17x…,127.0.0.1` → honrado) | G5, G6 |
| **Leitura de env fora do ciclo de carga** (closure que roda antes do `.env` — RD-01) | CT-48 (texto do `bootstrap/app.php`; `config` → boot); runtime com a chave só no arquivo `.env`: lacuna L12 | G6 |
| Domínio condicionado | CT-21/CT-22 (chamador × valor: a mesma lista confia ou não conforme o `REMOTE_ADDR`; faixa privada sem chave não confia) | G6 |
| **Token mágico do framework** (`*`, `**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`, `private_ranges`) | CT-20 (sozinho, em lista e com espaço nas bordas), CT-49, CT-21 (sozinho e em lista, com chamador que o token casaria) | G5, G6 |
| Cardinalidade 0 / 1 / N | proxies: CT-20 (0, 1, 2); serviços com build: CT-18 (todos), CT-38 (nenhum, sem override); ambientes: CT-01 (1, sem override), CT-26 (3) | G5, G2 |
| Ausente ≠ null ≠ vazio | CT-20 (ausente, `''`, `'   '`, `false`), CT-21 (ausente, `''`), CT-08 (`TRAEFIK_REDE` ausente e vazia), CT-11 (`COMPOSE_PROJECT_NAME` ausente e vazia), CT-12 (`TRAEFIK_HOST` ausente e vazia), CT-44 (`REVERB_APP_KEY`/`REVERB_APP_ID` ausente e vazia), CT-45 (`VITE_REVERB_*` ausente ≠ `""` no build) | G5, G6, G2 |
| Texto livre: espaços nas bordas, só espaços | CT-20, CT-22 (`' * '`) | G5, G6 |
| Unicode / limite de varchar | não se aplica: hostnames e IPs; o Compose valida o nome de projeto | — |
| Timezone / DST | não se aplica: nada temporal | — |
| Unicidade + soft delete | não se aplica | — |
| Unicidade de nome global (Traefik e Docker) | CT-10 (conjunto fechado de labels do nginx), CT-11 (todo objeto `routers/services/middlewares` com o prefixo do projeto), CT-15 (sufixo `-reverb`), CT-26 (`name` de topo, rede default e volumes disjuntos); nome de **serviço** global na rede compartilhada (P-14): CT-47 documenta, L10 | G2, G1, G4 |
| CRUD combinado | não se aplica | — |
| Mass assignment | não se aplica: nenhum model | — |
| Upload | não se aplica | — |
| Precisão monetária | não se aplica | — |
| Superfície Livewire | não se aplica: `02` declara nenhum componente | — |
| **Efeito colateral em log** (aviso do descarte) | CT-50 (aconteceu / não aconteceu / uma vez por item) | G7 |
| Estado do framework usado sem validar | não se aplica: nenhum `$filters`/`$tableSearch` | — |
| Discriminante nulo (fecha ou abre?) | CT-21 linhas ausente: **fecha** (nenhum proxy), inclusive com chamador em faixa privada | G6 |
| Saída do estado de erro | CT-12 → saída: a mensagem nomeia `TRAEFIK_HOST`; par CT-10 (com a chave, a configuração passa). CT-44 → a mensagem nomeia `REVERB_APP_KEY`/`REVERB_APP_ID`; par: a linha feliz do mesmo Esquema | G2 |
| Estado estático entre casos | CT-21/CT-22/CT-48/CT-50: `refreshApplication()` regrava o estático no boot; `TrustProxies::flushState()` no `tearDown` do framework + restauração do env pelo helper | G6, G7 |
| **Teste que viaja lendo arquivo que não viaja** (rule do projeto) | CT-04, CT-27…CT-32, CT-41, CT-42, CT-47 com `skip` fora da árvore; CT-05 e CT-37 leem `.gitattributes` e `caminhosDoKit()`, que viajam — e CT-37 exige o `.env.docker` na lista (P-23); CT-43 lê `resources/js` e `package.json`, que viajam | G3, G4, G1 |
| **Teste que viaja sem saída no projeto** (o projeto editou o arquivo de propósito) | CT-34 e CT-46 pulam fora da árvore (P-24/RD-07); a linha textual de CT-48 (`bootstrap/app.php`, fora de `CAMINHOS_DO_KIT`) também | G2, G6 |
| **Arquivo ignorado pelo git lido como entregue** (CR-08) | CT-01, CT-36, CT-39: lista dos arquivos de Compose **rastreados** | G1, G2 |
| **Ambiente do processo vaza para o subprocesso** | Setup Global G2 (env filtrado); sem isso CT-11 linha ausente e CT-33 medem o `.env` do desenvolvedor | G2 |
| Asserção de ausência sobre arquivo comentado | CT-02, CT-03, CT-13, CT-19, CT-23, CT-35, CT-43 rodam a ausência sem comentário; presença no texto cru | G1 |
| **Default intocado medido inteiro** (não por amostra de chaves) | CT-34 (golden do JSON inteiro, todos os profiles), CT-06 (diff profundo) | G2 |
| **Golden que se certifica sozinho** | CT-34: o fixture é cópia textual do base da `v0.44.0` e não há interruptor de regeneração (P-24); a procedência não tem prova na suíte — lacuna L11 | G2 |
| **Golden frágil à versão da ferramenta** (CR-07) | CT-34: os dois lados gerados pelo mesmo CLI no mesmo momento | G2 |
| **`skip` silencioso no CI** (suíte verde porque pulou) | CT-46 (canário: com `CI=true`, CLI ausente reprova) | G2 |
| **Âncora presente com sentido invertido** (negação na prosa) | CT-41, CT-42 (mesma frase, sem negação nas 3 palavras antes); CT-30 linha REVERB_* (negação **exigida**) | G4 |
| **Documentação que sobrevive à correção** (instrução antiga que a premissa nova desmente) | CT-41 (nenhuma frase com "ambiente do processo" e `TRUSTED_PROXIES` — P-13 reescrita); CT-30 (nenhum `REVERB_SCHEME=https` em bloco — P-20) | G4 |
| **Oráculo que o próprio teste interpola** | CT-16 lê a regra interpolada pelo Compose, não pelo teste | G2 |
| Prova de ponta a ponta com Traefik real | lacuna declarada L1 | G2 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | exemplo em docker/traefik não é carregado | R1 | EP | G2 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M1 |
| CT-02 | arquivos-base sem Traefik nas linhas ativas | R1 | EP | G1 | Pest feature HTTP | idem | M2, M3, M4 |
| CT-03 | script chama o Compose sem fixar arquivo, projeto, diretório nem env-file | R1 | EP | G1 | Pest feature HTTP | idem | M5, M89, M90, M133 |
| CT-04 | git ignora a cópia ativa e versiona o exemplo | R2 | EP | G3 | Pest feature HTTP | idem | M6, M7, M8, M91 |
| CT-05 | exemplo nas duas listas de entrega | R2 | EP | G1 | Pest feature HTTP | idem | M9, M10, M137 |
| CT-06 | diff profundo da config restrito ao Traefik e ao build | R3 | rastreio de efeito | G2 | Pest feature HTTP | idem | M11, M12, M13, M15, M16, M93 |
| CT-07 | serviços do exemplo existem no base | R3 | contrato | G1 | Pest feature HTTP | idem | M12, M14 |
| CT-08 | rede externa do .env, default my-network | R4 | EP | G2 | Pest feature HTTP | idem | M17, M18, M19, M94 |
| CT-09 | só o nginx na rede externa | R4 | EP | G2 | Pest feature HTTP | idem | M20 |
| CT-10 | labels do router e do service, conjunto fechado | R5 | EP | G2 | Pest feature HTTP | idem | M21, M22, M23, M24, M138 |
| CT-11 | objetos do Traefik com o nome do projeto e valores certos | R6 | EP | G2 | Pest feature HTTP | idem | M25, M26, M96, M97, M98 |
| CT-12 | sem TRAEFIK_HOST (ou vazia) o Compose recusa | R6 | estado de erro | G2 | Pest feature HTTP | idem | M27, M95 |
| CT-13 | exemplo não fixa hostname | R6 | EP | G1 | Pest feature HTTP | idem | M28 |
| CT-14 | Reverb segue na rota por porta | R7 | EP | G2 | Pest feature HTTP | idem | M29 |
| CT-15 | bloco comentado do Reverb completo; cabeçalho com a ressalva do loopback | R7 | EP + regex | G1 | Pest feature HTTP | idem | M30, M31, M33, M99, M166 |
| CT-16 | regra do Reverb, interpolada pelo Compose, recorta pela chave e não captura painel | R7 | invariante + regex | G2 | Pest feature HTTP | idem | M32, M99, M100, M142 |
| CT-17 | ARG no estágio assets antes do build | R8 | contrato estático | G1 | Pest feature HTTP | idem | M34, M35, M36, M38 |
| CT-18 | com os quatro no .env, todo serviço com build recebe os args | R8 | contrato | G2 | Pest feature HTTP | idem | M37, M38 |
| CT-19 | sem build-arg nada de VITE_REVERB_* | R9 | EP | G1 | Pest feature HTTP | idem | M39, M40, M41, M102, M103, M145, M146 |
| CT-20 | interpretação de TRUSTED_PROXIES | R10 | EP exaustiva | G5 | unit de regra | `tests/Kit/ProxiesConfiaveisTest.php` | M42…M47, M104…M108, M147…M149, M152, M167…M173 |
| CT-21 | proxy não confiável é ignorado | R11 | tabela de decisão | G6 | Pest feature HTTP | `tests/Kit/ProxiesConfiaveisTest.php` | M48, M49, M50, M107, M108, M109, M111, M150, M151, M152, M178, M179 |
| CT-22 | proxy confiável é honrado | R12 | tabela de decisão | G6 | Pest feature HTTP | idem | M51, M52, M53, M54, M110, M112, M153, M180, M184 |
| CT-23 | chave nova só como linha comentada | R13 | EP arquivo × chave | G1 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M55, M56, M58, M113, M114, M115 |
| CT-24 | .env.docker oferece o que o exemplo consome | R13 | contrato | G1 | Pest feature HTTP | idem | M57, M58 |
| CT-25 | bloco do .env.docker não colide porta | R13 | contrato | G2 | Pest feature HTTP | idem | M59, M154 |
| CT-26 | três ambientes não colidem | R14 | valor literal | G2 | Pest feature HTTP | idem | M60, M61, M62, M117 |
| CT-27 | página alcançável nos dois idiomas | R15 | EP por idioma | G4 | Pest feature HTTP | idem | M63, M64, M65 |
| CT-28 | CHANGELOG registra | R15 | EP | G4 | Pest feature HTTP | idem | M66 |
| CT-29 | âncoras do procedimento | R16 | EP por âncora | G4 | Pest feature HTTP | idem | M67…M70, M118, M155 |
| CT-30 | matriz, build e rotas do Reverb; `REVERB_*` intocados; portas dos profiles | R17 | valor literal | G4 | Pest feature HTTP | idem | M71…M74, M120, M121, M158, M190…M192 |
| CT-31 | opções A–D e armadilhas; receita da D com rede própria | R18 | EP por âncora | G4 | Pest feature HTTP | idem | M75…M78, M124, M125, M161…M163, M193…M195 |
| CT-32 | espelho pt/en | R19 | contrato | G4 | Pest feature HTTP | idem | M79…M82 |
| CT-33 | sem override, nenhum serviço recebe TRUSTED_PROXIES | R1 | EP | G2 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M83 |
| CT-34 | config efetiva do base, inteira, = a do base de `v0.44.0` (fixture textual, mesmo CLI) | R1 | golden | G2 | Pest feature HTTP | idem | M3, M84, M85, M129, M130, M132 (M131 → L11) |
| CT-35 | nginx.conf sem diretiva de proxy | R1 | EP | G1 | Pest feature HTTP | idem | M86, M134 |
| CT-36 | um arquivo de Compose **rastreado** na raiz, `env_file: .env` literal, nenhum `*infra*.y*ml` | R1 | EP | G1 | Pest feature HTTP | idem | M1, M87, M88, M135 |
| CT-37 | kit:update não toca a cópia ativa e leva o `.env.docker` | R2 | EP | G1 | Pest feature HTTP | idem | M92, M137, M165 |
| CT-38 | base sozinho sem build.args VITE_REVERB_* | R9 | EP | G2 | Pest feature HTTP | idem | M101 |
| CT-39 | .env.docker verbatim não liga o Traefik | R13 | EP | G2 | Pest feature HTTP | idem | M113 |
| CT-40 | FORWARD_* em loopback publicam só em 127.0.0.1 | R13 | contrato | G2 | Pest feature HTTP | idem | M116 |
| CT-41 | seção de TRUSTED_PROXIES: cache refeito, sem "ambiente do processo", IP fixo e vizinhos do `*` | R16 | EP por âncora + ausência | G4 | Pest feature HTTP | idem | M119, M156, M185…M188 |
| CT-42 | matriz com as quatro portas obrigatórias, distintas e em loopback | R17 | EP por âncora | G4 | Pest feature HTTP | idem | M122, M123, M159, M160 |
| CT-43 | bundle do kit sem Echo | R20 | EP por arquivo | G1 | Pest feature HTTP | idem | M126, M127, M164 |
| CT-44 | bloco do Reverb descomentado liga a rota, e só com chave e id | R7 | EP (rota ativa) + estado de erro + execução real | G2 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M128, M139, M140, M141 |
| CT-45 | com o override, build.args só com as VITE_REVERB_* definidas | R9 | EP (ausente × definida) | G2 | Pest feature HTTP | idem | M143, M144 |
| CT-46 | canário: no CI, CLI do Compose ausente reprova | R1 | canário | G2 | Pest feature HTTP | idem | M136 |
| CT-47 | seção do Traefik avisa nome de serviço global e o que resolve | R16 | EP por âncora | G4 | Pest feature HTTP | idem | M157, M189 |
| CT-48 | chave lida pelo config e aplicada no boot, não no bootstrap | R12 | leitura do caminho `config` → boot | G6 | Pest feature HTTP | `tests/Kit/ProxiesConfiaveisTest.php` | M51, M181, M182, M184 (M183 → L9) |
| CT-49 | o kit diz quais itens descartou | R10 | EP | G5 | unit de regra | idem | M174…M177 |
| CT-50 | aviso no canal configuracoes, um por item descartado | R21 | rastreio de efeito | G7 | Pest feature HTTP | idem | M196…M200 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "a chamada a `trustProxies()` vem antes do `append(RaizDeUrlSemPublic)`" (leitura do `bootstrap/app.php`) | obsoleto: P-02 alterada tirou a chave do `bootstrap/app.php` (CT-48 afirma a ausência) |
| snapshot `git show <tag>:docker-compose.yml` = arquivo atual | substituído pelo golden de **configuração efetiva** (CT-34): o snapshot de texto expira em toda mudança de comentário; o golden só expira quando o efeito muda, e regenerá-lo é ato deliberado. A tag continua sendo a **procedência** do fixture (P-24) |
| re-gerar no teste a configuração de `git show v0.44.0:docker-compose.yml` e compará-la à do momento | adotado em parte por P-24 (CR-07): o caso gera os dois lados no momento, mas a partir da **cópia textual** versionada, sem depender de `git` nem da tag no checkout |
| CT-34 contra o JSON gerado numa versão do Compose (o fixture JSON de v3) | frágil à versão do Compose do CI (forma de `null`, `required`, durações — CR-07); substituído pela cópia textual de P-24 |
| interruptor de regeneração do golden no teste (`REGENERAR_GOLDEN=1`) | P-24: regenerar é copiar o base; o interruptor deixaria regenerar a partir da árvore da branch e o golden se certificaria sozinho (ADV2-01) |
| CT-34 conferindo o fixture contra `git show v0.44.0:docker-compose.yml` quando a tag existe | P-24 tira do caso a dependência da tag (decisão da sessão); a procedência é a lacuna L11 |
| `Host: *.on-forge.com`/`*.on-vapor.com` com a chave ausente: o Laravel confia em todos (`vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php:setTrustedProxyIpAddresses:67`) (RD-06) | comportamento do framework **anterior ao diff**; o requisito não pede mudá-lo — a sessão corrigiu só o texto (docblock da classe). CT-21 usa `Host: interno.local`, fora desses sufixos, então "ausente = nenhum proxy" continua verdadeiro no cenário |
| CT-26 com `--profile ai` (as portas do llama na matriz) | a matriz do requisito colide com o llama por construção (8080 — P-22); o remédio é a página (CT-30, linha portas dos profiles), não o compose |
| `private_ranges` como coringa a mais no filtro, com mutante próprio | depois de P-18 o item também cai na validação de IP/CIDR: os dois caminhos dão o mesmo resultado em CT-20 e CT-49 (mutante equivalente); as linhas de P-19 ficam, o mutante não |
| alias único para o `app` (ou `fastcgi_pass` pelo nome do container) contra a colisão de DNS na rede compartilhada | muda o `nginx.conf`, que RQ-09 manda não mudar; Q12 recomenda documentar e perguntar ao DevOps (P-14, CT-47) |
| `docker build --target assets` com probe do ambiente do `RUN`, na suíte | exige daemon e uma linha de probe no Dockerfile; o mecanismo foi medido uma vez pela sessão (P-12) e a suíte cobre por leitura (CT-19) — lacuna L2 |
| `config:cache` com `TRUSTED_PROXIES` diferente entre o cache e o ambiente (runtime) | decisão da sessão (step 9): documentar (P-13 reescrita, CT-41) e declarar L9. Arnês candidato não tentado: `APP_CONFIG_CACHE` apontando um cache temporário (`vendor/laravel/framework/src/Illuminate/Foundation/Application.php:getCachedConfigPath:1325`) + `refreshApplication()` — mataria M183 |
| CT-B de qualquer natureza | ver `## Sem CT-B` |

## Costuras — notas para quem confirmar

- G6 **confirmada** pela sessão em `refreshApplication()` (2026-10-05). A medição de v3 (`forgetInstance(Kernel)` refaz o `afterResolving` de `withMiddleware`) deixou de servir com P-02 alterada: a chave agora vive no `config` e é aplicada no `boot()` do provider, que só um refresh refaz. Ordem: env → refresh → rota e `config()` → request.
- G7 **volta à confirmação**: a sessão pediu "spy + `refreshApplication()`", mas o refresh limpa as fachadas antes do boot (`RegisterFacades.php:bootstrap:18`) e o spy posto antes não vê o aviso. Proposta: spy **depois** do refresh e re-execução do passo de boot (forma de `alinharConfiguracoesDoKit()`). Se a sessão confirmar outro arnês, o oráculo de CT-50 não muda.
- G2 roda no CI (`ubuntu-latest` tem o plugin) e no Windows local (Compose v5.5.1). A interpolação de label em lista foi medida só na v5.5.1; se o CI divergir, CT-11 é o primeiro a acusar. O golden de CT-34 deixou de depender da versão do Compose (P-24, CR-07): os dois lados são gerados pelo mesmo CLI no mesmo momento.

## Fechamento com mutation testing

- Código PHP novo: `app/Support/ProxiesConfiaveis.php` (`doEnv()` e `descartados()`), o passo de boot de `app/Providers/KitServiceProvider.php` que lê `config('kit.proxies_confiaveis')`, avisa e chama `TrustProxies::at()`, e a chave de `config/kit.php`. Medir com `--path=app/Support/ProxiesConfiaveis.php` e `--path=app/Providers/KitServiceProvider.php`, sem `--filter`, com `--no-tia`, pelo `pestw.cmd` (Windows), aceitando só score com `Duration` plausível e lista de sobreviventes (`.ai/rules/testes.md`). `UNTESTED` é sobrevivente. No provider, contar só os mutantes do passo de proxies; os demais pertencem a outras features.
- Para YAML, Dockerfile, `nginx.conf`, `.env.*`, JavaScript e Markdown o `pest --mutate` não gera mutante — a cobertura de R1–R9 e R13–R20 é a tabela de mutantes de especificação acima.

## Lacunas Declaradas

| # | Lacuna | O que foi tentado / por quê | Regra |
|---|---|---|---|
| L1 | Traefik real roteando `Host` → `nginx:80` com TLS | exige daemon, container do Traefik, DNS e certificado; `docker compose config` prova o contrato, não o roteamento. Fica para a validação manual do quality gate | R4–R7 |
| L2 | prova **recorrente** em runtime de que, sem build-arg, nenhuma `VITE_REVERB_*` existe no ambiente do `npm run build` | medido uma vez pela sessão com `docker build --progress=plain` (P-12); na suíte, `docker run <imagem> env` não vê ARG e provar o `RUN` exige probe no Dockerfile. CT-19 e CT-38 cobrem por leitura e configuração | R9 |
| L4 | P-08 em runtime: health check do script com `FORWARD_APP_PORT=127.0.0.1:…` respondendo | exige daemon e stack de pé; CT-03 prova estaticamente que a porta vem de `docker compose --profile app port nginx 80` e não de `${FORWARD_APP_PORT` | R1 |
| L7 | **reaberta (P-21, CR-03) como risco aceito documentado**: `*` em `TRUSTED_PROXIES` confia em qualquer container da rede compartilhada do Traefik, não só no Traefik — o loopback de CT-25 fecha a porta do host, não a rede | a página declara o risco e recomenda o IP fixo do Traefik (`ipv4_address`) quando o DevOps o fornecer (CT-41); o runtime com outro container forjando `X-Forwarded-*` exige daemon e dois projetos na mesma rede externa | R11, R16 |
| L9 | **reescrita (P-13)**: com a configuração em cache vale o valor cacheado de `kit.proxies_confiaveis`, como toda chave; mudar a chave pede refazer o cache — sem prova de runtime; M183 (provider lendo `env()` direto) fica sem matador | decisão da sessão (step 9): documentar e afirmar o aviso na página (CT-41). Arnês candidato, não tentado: `APP_CONFIG_CACHE` para um cache temporário com a chave diferente do ambiente + `refreshApplication()` (`Application.php:getCachedConfigPath:1325`) | R12, R16 |
| L10 | resolução de `app:9000` quando outro projeto do servidor tem container `app` (ou `reverb`) na rede compartilhada do Traefik (P-14, P-25, Q12) | exige daemon, dois projetos na mesma rede externa e o `nginx` resolvendo o nome; a entrega documenta e manda confirmar com o DevOps (CT-47), e só o `nginx` entra na rede (CT-09) | R4, R16 |
| L11 | procedência do fixture de CT-34: que `docker-compose.v0.44.0.yml` é o base da tag, e não o da branch — M131 sem matador | P-24 tirou do caso a dependência da tag no git (CR-07); a cópia é ato deliberado, registrada no docblock e no `CHANGELOG.md`. Conferir contra `git show v0.44.0:…` quando a tag existir foi cortado (`## Cogitado e cortado`) | R1 |
| L12 | a chave **só no arquivo `.env`** (fora do ambiente do processo) chega ao boot em runtime — a metade de runtime de RD-01 | o arnês de G6 fixa a chave no ambiente do processo, onde uma leitura no `bootstrap/app.php` também a veria; o que discrimina RD-01 em CT-48 é a linha textual. Não tentado: `.env` temporário carregado por `Application::loadEnvironmentFrom()` antes do bootstrap, que o `createApplication()` padrão não expõe | R12 |

**Retiradas** (os números não são reaproveitados): L3 (bloco do Reverb descomentado, agora CT-44 —
Q10/D6), L5 (recorte do Reverb, agora P-10 com CT-15/CT-16), L6 ("opcional" da matriz, agora P-09
com CT-42) e L8 (revisão adversarial, feita). L7 foi retirada no v3 (ADV2-08: a última linha de CT-25)
e **reaberta** no v4 com outro conteúdo (P-21: o loopback não basta contra a rede compartilhada).

## Sem CT-B

- Motivo: o `01` declara **Superfície de UI: nenhuma** e o `02`, **Superfície Livewire: nenhuma**; a varredura confirma — nada desta entrega renderiza HTML (artefatos de infra, um `app/Support`, um passo de boot de provider, uma chave de config e documentação). O único request (G6) afirma esquema, host, URL e IP vistos pelo PHP, que `Pest feature HTTP` prova sem navegador. Nenhuma linha de `## Costuras de Teste` tem costura `browser`; o `05` não existe.

## Perguntas para o 00-requisito.md

As perguntas de v1–v3 já foram levadas pela sessão. O v4 abre **uma** `Q?n` provisória, de raia
desenho (a sessão decide; não vai ao `00`):

- **Q13** — `ProxiesConfiaveis::descartados()` (CT-49) e o aviso de R21 (CT-50) valem também para o
  **coringa sozinho** (`'REMOTE_ADDR'`, `'private_ranges'`, `'**'`, que P-11 manda virar `null`) e para
  o **não-string** (`TRUSTED_PROXIES=true`)? Recomendação: **sim para o coringa sozinho** (o operador
  escreveu algo que o kit ignorou — o aviso é o único rastro) e **sim para o não-string** pelo mesmo
  motivo; falha fechado continua (o resultado de `doEnv()` não muda). Bloqueia só as linhas que CT-49
  e CT-50 deixaram de fora.

| Pergunta | Raia | Destino |
|---|---|---|
| Q6 — "opcional" × obrigatórias e distintas | requisito | `00`, `## Perguntas ao Solicitante`; implementada como P-09 (R13, R14, CT-42) |
| Q7 — `ARG` sem default e sem `ENV` | desenho | medida pela sessão; virou P-12 (R9) |
| Q8 — recorte do Reverb sem capturar `/app` | requisito | `00`; implementada como P-10 (R7, CT-16) |
| Q9 — `*` dentro de lista | requisito | `00`; implementada como P-11 (R10, R11), estendida pela decisão de ADV-20 a `**`, `REMOTE_ADDR` e `PRIVATE_SUBNETS` |
| Q10 — delimitadores no bloco do Reverb | desenho | decidida: D6 do `01` (`# >>> reverb-traefik` / `# <<< reverb-traefik`); CT-44 fecha a L3 |
| Q11 — `TRUSTED_PROXIES=*` com porta aberta | requisito | `00`; a página segue a recomendação, e CT-25 a afirma na configuração do bloco do `.env.docker` (ADV2-08; a antiga L7 foi retirada) |
| Q12 — nome de serviço global na rede compartilhada do Traefik (Q?2 da 2ª rodada adversarial) | requisito | `00`, aberta e não bloqueante; implementada como P-14 (documentar e confirmar com o DevOps; só o `nginx` entra na rede) — CT-47 (R16), CT-09 (R4), lacuna L10; refinada por P-25 no step 9 |
| Q13 (v4) — descarte e aviso do coringa sozinho e do não-string | desenho | decidida pela sessão, 2026-10-05: **sim** para os dois; linhas em CT-49 (e o CT-50 cobre o coringa sozinho pela mesma regra) |

## Revisão Adversarial

2 rodadas, `fw-adversario-ct` (entrada: só `00` + este `04` + `wikis/glossario.md`), **36 + 33
achados, todos fechados**. **Teto de 2 rodadas atingido**: o fechamento da 2ª (este v3) não passa por
uma 3ª. Destinos: **CT novo** · **oráculo reescrito** (CT existente com asserção nova ou reescrita) ·
**mutante** (linha nova na tabela da regra) · **texto** (rastreabilidade, sem efeito em cenário) ·
**lacuna** (declarada com motivo).

### Rodada 1 (`04` v1 → v2)

| ADV | Sev. | Achado (implementação errada que passava, ou falha do conjunto) | Destino |
|---|---|---|---|
| ADV-01 | blocker | base ganha `environment: TRUSTED_PROXIES` | CT novo CT-33 (R1) · M83 |
| ADV-02 | blocker | defaults do base alterados fora das chaves que CT-01/CT-02 olham | CT novo CT-34 (golden do base; desde o v4, o fixture textual de P-24) · M84, M85 |
| ADV-03 | alto | `build.args` com `VITE_REVERB_*` no base | CT novo CT-38 (R9) · M101 |
| ADV-04 | blocker | default de faixas privadas quando a chave falta | oráculo reescrito CT-21 (chamadores `172.18.0.5`, `10.0.0.7`; ausente, vazia, `10.9.9.9`) · M109 |
| ADV-05 | alto | `bootstrap/app.php` passa o env cru | oráculo reescrito CT-22 (`' * '`) e CT-21 (`true` → 200, falha fechado) · M110 |
| ADV-06 | alto | `nginx.conf` passa a honrar cabeçalho de proxy | CT novo CT-35 (R1) · M86 |
| ADV-07 | alto | `.env.docker` com `COMPOSE_FILE`/`COMPOSE_PROFILES` ativo | CT novo CT-39 (R13) + oráculo reescrito CT-23 · M113, M114 |
| ADV-08 | alto | ausente ≠ vazio em `TRAEFIK_HOST`, `TRAEFIK_REDE`, `COMPOSE_PROJECT_NAME` | oráculo reescrito CT-12 (Esquema com linha vazia), CT-08 e CT-11 (linhas vazias) · M94, M95, M96 |
| ADV-09 | alto | `.env.docker` sem as outras três `FORWARD_*` | oráculo reescrito CT-23 (linhas `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_REVERB_PORT`) · M115 |
| ADV-10 | médio | `kit:update` com a cópia ativa da raiz na lista | CT novo CT-37 (R2) · M92 |
| ADV-11 | médio | middleware/service de nome fixo colide entre ambientes | oráculo reescrito CT-11 (todo objeto começa pelo projeto) · M97 |
| ADV-12 | médio | rede default ou volume de nome fixo compartilhado entre ambientes | oráculo reescrito CT-26 · M117 |
| ADV-13 | médio | regra do Reverb sem parênteses (`&&` precede `\|\|`) | oráculo reescrito CT-15/CT-16 (regex sobre a regra interpolada) · M99 |
| ADV-14 | médio | chave do Reverb colada literal na regra | oráculo reescrito CT-16 (segunda interpolação `outra-chave`/`outro-id`) · M100 |
| ADV-15 | médio | `X-Forwarded-For` fora dos `headers` | oráculo reescrito CT-22 (`ip()` = `203.0.113.9`) e CT-21 (`ip()` = chamador) · M112 |
| ADV-16 | médio | `COPY .env` ou `RUN VITE_REVERB_X=` no estágio `assets` | oráculo reescrito CT-19 · M102, M103 |
| ADV-17 | médio | script com `-p`/`--project-name`/`--env-file` ou `COMPOSE_PROJECT_NAME` | oráculo reescrito CT-03 · M89 |
| ADV-18 | médio | `forceRootUrl`/`forceScheme` pelo `APP_URL` contorna a confiança | oráculo reescrito CT-21 (`url('/x')` com `APP_URL` https) · M111 |
| ADV-19 | médio | `config:cache` fora do Docker ignora o `.env` | lacuna L9 + texto (P-13 nova no `00`) + CT novo CT-41 (R16, documentação) · M119 |
| ADV-20 | médio | tokens mágicos (`*`, `**`, `REMOTE_ADDR`) passam em lista | oráculo reescrito CT-20 (4 linhas) e CT-21 (`10.9.9.9,REMOTE_ADDR`, `10.9.9.9,*`) · M104…M107; complemento da sessão: `PRIVATE_SUBNETS` (CT-20, CT-21) · M108 |
| ADV-21 | baixo | entrega liga Echo no bundle | regra nova R20 + CT novo CT-43 · M126, M127 |
| ADV-22 | baixo | segundo arquivo de Compose na raiz / `env_file` parametrizado | CT novo CT-36 (R1) · M87, M88 |
| ADV-23 | médio | publicação com IP fixo anula o bind em loopback | CT novo CT-40 (R13) · M116 |
| ADV-24 | médio | página sem os valores `VITE_REVERB_*` atrás do Traefik nem o rebuild | oráculo reescrito CT-30 (duas linhas) · M120 |
| ADV-25 | alto | `git check-ignore` sem `--no-index` não vê arquivo versionado | oráculo reescrito CT-04 · M91 |
| ADV-26 | — | âncora da cópia aceitava os dois nomes em qualquer ordem | oráculo reescrito CT-29 (regex do `cp`) · M118 |
| ADV-27 | — | nota do 8090 aceita dentro de bloco/tabela | oráculo reescrito CT-30 (linha de prosa) · M121 |
| ADV-28 | — | CT-06 comparava lista fechada de chaves | oráculo reescrito CT-06 (diff profundo menos caminhos permitidos) · M93 |
| ADV-29 | — | CT-21 sem a tabela de decisão explícita | oráculo reescrito CT-21 (chamador × chave × `isSecure`/host/url/ip) |
| ADV-30 | — | CT-11 afirmava só a existência da chave do label | oráculo reescrito CT-11 (`rule` e `server.port` com valor em toda linha) · M98 |
| ADV-31 | — | `SESSION_COOKIE=` em bloco e D sem dizer o que se compartilha | oráculo reescrito CT-31 (duas linhas) · M124, M125 |
| ADV-32 | alto | matriz transcrita com "opcional" | CT novo CT-42 (R17) · M122, M123 |
| ADV-33 | — | P-11 sem cenário que a discrimine no HTTP | oráculo reescrito CT-20/CT-21 (ver ADV-20) · M107 |
| ADV-34 | — | `04` dessincronizado do `00` (P-09…P-12, Q6/Q8/Q11, `@premissa` de R9, L5/L6, cabeçalho) | texto: Mapa de Regras, R7, R9, Lacunas, cabeçalho e esta tabela |
| ADV-35 | — | health check do script pela `FORWARD_APP_PORT` | oráculo reescrito CT-03 · M90; L4 reescrita |
| ADV-36 | — | RQ-02 e RQ-03 sem origem em regra | texto: RQ-02 na Origem de R9 e R13; RQ-03 na Origem de R15 |

### Rodada 2 (`04` v2 + CT-44 → v3)

Foco nos CT novos e reescritos da rodada 1. **33 achados** (1 blocker, 5 altos, 10 médios, 16 baixos,
1 malformado — classificação do despacho no `03`, `## Despachos`) e uma pergunta, Q?2, levada ao `00`
como **Q12** e implementada como **P-14**. Reabriu quatro fechamentos da rodada 1 (ADV-02, ADV-05,
ADV-20, ADV-28) e a sincronia do `04` (ADV-34). A sessão reproduziu ADV2-03 (`--profile '*'`),
ADV2-04 e ADV2-07 (medido: `${VAR:-}` passa `""`, a lista sem valor omite a ausente) e aceitou
**todos**, com as decisões em P-14…P-17 do `00` e D8/D9 do `01`. A severidade individual abaixo só
consta onde o despacho a fixou no texto da decisão.

| ADV | Sev. | Achado (implementação errada que passava, ou falha do conjunto) | Destino |
|---|---|---|---|
| ADV2-01 | blocker | golden gerado pelo executor a partir da árvore da branch se certifica sozinho; a normalização de "todo path absoluto" escondia bind trocado | oráculo reescrito CT-34 + Setup Global (fixture da sessão, de `v0.44.0`; só o prefixo da pasta temporária normalizado) + P-17 no `00` (substituída por P-24 no step 9) · M131 (desde o v4, sem matador — L11), M132 |
| ADV2-02 | alto | CT-34 comparava lista fechada de chaves por serviço (reabre ADV-02) | oráculo reescrito CT-34 (JSON inteiro, diff profundo, nada excluído) · M130 |
| ADV2-03 | alto | golden só com `--profile app`: serviço de outro profile mudava sem ser visto | oráculo reescrito CT-34 (`--profile '*'`, 12 serviços) · M129 |
| ADV2-04 | — | `REVERB_APP_KEY`/`REVERB_APP_ID` vazias viram `PathPrefix(/app/)` e roubam o painel | CT-44 vira Esquema (ausente × vazia, por chave, e a linha feliz) + P-16 no `00` · M139, M140 |
| ADV2-05 | — | tokens sozinhos (`**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`) e `*,*` sem prova no HTTP (reabre ADV-05 e ADV-20) | oráculo reescrito CT-21 (4 linhas) · M150 |
| ADV2-06 | — | label a mais com o prefixo do projeto passava pelo CT-11 | oráculo reescrito CT-10 (conjunto fechado das seis chaves `traefik.`) · M138 |
| ADV2-07 | — | `build.args` com `${VAR:-}` passa `""` ao build e reintroduz a armadilha de P-12 pelo override | CT novo CT-45 (R9) + CT-18 ajustado + P-15 no `00` · M143, M144 |
| ADV2-08 | — | `TRUSTED_PROXIES=*` sugerido com a porta do nginx em `0.0.0.0` (era a L7) | oráculo reescrito CT-25 · M154; L7 retirada |
| ADV2-09 | — | confiança ligada por `TRAEFIK_HOST` definida | oráculo reescrito CT-21 (linha com `TRAEFIK_HOST`) · M151 |
| ADV2-10 | — | token com espaço nas bordas escapa do filtro | oráculo reescrito CT-20 (2 linhas) · M147 |
| ADV2-11 | — | CT-21 afirmava, sem citar, que nada no kit força esquema ou raiz | texto: nota de CT-21 com `app/Http/Middleware/RaizDeUrlSemPublic.php:forceRootUrl:73` e a condição `/public` |
| ADV2-12 | — | Echo ligado pela configuração `broadcasting.echo` do Filament, sem `import` no bundle | oráculo reescrito CT-43 (2 linhas) · M164 |
| ADV2-13 | — | bloco do Reverb põe o reverb só na rede externa | oráculo reescrito CT-44 (redes `default` **e** externa) · M141 |
| ADV2-14 | — | runner do CI sem o CLI: toda a G2 vira `skip` verde | CT novo CT-46 (canário, R1) + Setup Global · M136 |
| ADV2-15 | — | "prefixo" por string em CT-05/CT-37 | oráculo reescrito CT-05 e CT-37 (por segmento) · M137 |
| ADV2-16 | — | bloco de `.env` da página com `APP_URL` local ou `APP_DEBUG=true` | oráculo reescrito CT-31 (2 linhas) · M161, M162 |
| ADV2-17 | — | âncoras literais do `nginx.conf` (espaço duplo, caixa, hífen) | oráculo reescrito CT-35 (regex) · M134 |
| ADV2-18 | — | `X-Forwarded-Port` fora dos `headers` | oráculo reescrito CT-22 (coluna porta, linha 8443) · M153 |
| ADV2-19 | — | `false`, `'10.0.0.1,**'` e `'*,'` sem linha | oráculo reescrito CT-20 (3 linhas; `'*,'` → `null` por decisão da sessão) + blockquote do R10 · M148, M149 |
| ADV2-20 | — | `COPY . .` ou `RUN --mount` trazem o `.env` ao estágio `assets` | oráculo reescrito CT-19 (2 linhas) · M145, M146 |
| ADV2-21 | — | bloco do build da página com `VITE_REVERB_HOST=localhost` | oráculo reescrito CT-30 (linha host do Reverb) · M158 |
| ADV2-22 | — | regex do `cp` sem âncora no fim (`….yml.bak` passava) | oráculo reescrito CT-29 · M155 |
| ADV2-23 | — | `docker-compose` com hífen, `--project-directory` e `COMPOSE_ENV_FILES` fora da leitura | oráculo reescrito CT-03 · M133 |
| ADV2-24 | — | achado registrado | sem ação — decisão da sessão |
| ADV2-25 | — | M110 com CT-21 (linha `true`) como matador: sem `strict_types` no `bootstrap/app.php`, não diverge | texto: M110 corrigido (só CT-22) |
| ADV2-26 | — | "lista que contém `*` promovida a `'*'`" sem mutante | mutante M152 (CT-21 linha `10.9.9.9,*`; CT-20 linha `10.0.0.1,*`) |
| ADV2-27 | — | CT-16 interpolava a regra no próprio teste (reabre ADV-28 no espírito: o oráculo não era o efeito) | oráculo reescrito CT-16 (regra interpolada pelo Compose, G2, uma execução por linha) · M142 |
| ADV2-28 | — | âncoras de seção aceitavam frase negada; "seção da matriz" indefinida | oráculo reescrito CT-41 e CT-42 (mesma frase, sem negação nas 3 palavras antes; seção da matriz definida) · M156, M159 |
| ADV2-29 | — | recorte do estágio pelo literal da imagem | oráculo reescrito CT-17 (`^FROM\s+\S+\s+AS\s+assets$`) |
| ADV2-30 | — | `Quando` do CT-34 com duas ações (executar e normalizar) | texto: `Quando` só com a execução; normalização no `Então` |
| ADV2-31 | — | seção da matriz podia repetir a nota ¹ sem o loopback | oráculo reescrito CT-42 (`127.0.0.1` e `Traefik` na mesma frase) · M160; RQ-16 no `00` fechada com a releitura por P-09 |
| ADV2-32 | — | Opção D sem receita; arquivo de infra entregue pelo kit | oráculo reescrito CT-31 (linha receita da D) e CT-36 (`*infra*.y*ml`) · M163, M135 |
| ADV2-33 / Q?2 | — | DNS do Docker resolve nome de serviço em todas as redes: outro `app` na rede do Traefik recebe o `fastcgi_pass` | Q12 no `00`, implementada como P-14 + CT novo CT-47 (R16) + lacuna L10 · M157 |
| ADV-34 (reaberto) | — | `04` dessincronizado de novo: contagens do cabeçalho, M128 fora da tabela do R7, segunda linha de regra Gherkin no R7, L3 sem registro | texto: cabeçalho por `grep -c`; M128 na tabela do R7 com o estouro justificado; CT-44 na mesma regra do R7; L3 e L7 nas retiradas; `## Cogitado e cortado` sem a linha de `REMOTE_ADDR` sozinho (agora no blockquote do R10 e em CT-20/CT-21); `## Perguntas` com Q12 |

Ajustes de oráculo vindos do executor depois do v2 (classificados pela sessão como oráculo
prescritivo demais, não defeito): CT-06 ignora as chaves de topo `x-*` que o `docker compose config`
(v5.5.1) devolve no JSON; CT-30 aceita, em cada célula da matriz, o prefixo `127.0.0.1:` (P-06/P-09)
e o marcador de nota `¹`/`²`.

### Step 9 — revisão do diff (`04` v3 → v4)

Não é uma 3ª rodada adversarial (o teto de 2 continua atingido): são os achados da **revisão do
diff** — `fw-revisor-diff` (RD-01…RD-08) e o passe genérico (CR-01…CR-09) —, todos aceitos pela sessão,
que os levou ao `00` como premissa (P-18…P-25, P-02 alterada, P-13 reescrita, P-17 substituída) ou os
roteou direto a teste (CR-08). Os CT abaixo nascem **antes** da correção. Severidade só onde o `03` a
fixou (os três altos do revisor de eixos e os dois altos do genérico, coincidentes com RD-01/RD-04).

| Achado | Sev. | O que passava | Premissa | Destino |
|---|---|---|---|---|
| RD-01 / CR-01 | alto | `env('TRUSTED_PROXIES')` no `withMiddleware()` roda antes de o `.env` carregar: a chave só no arquivo era ignorada | P-02 alterada, P-13 reescrita | CT novo CT-48 (R12) · costura G6 → `refreshApplication()` · CT-41 reescrito (o aviso de cache se inverte) · M181…M184, M185, M186 · L9 reescrita, L12 nova · M110, M48, M51, M150, M151 reescritos (o boot no lugar do bootstrap) |
| RD-02 | alto | `private_ranges` passava pela lista e confiava em toda faixa privada | P-19 | CT-20 (2 linhas), CT-21 (1 linha), CT-49 (1 linha) · M173, M179 |
| RD-03 / CR-04 | alto | item que não é IP/CIDR chega ao Symfony: `TypeError` em todo request (`/16x`) ou silêncio (`17x…`) | P-18 | CT-20 (7 linhas), CT-21 (`/16x` → 200), CT-22 (`17x…,127.0.0.1` → honrado) · CT novo CT-49 (R10) · regra nova R21 + CT novo CT-50 · costura G7 · M167…M172, M174…M178, M180, M196…M200 |
| RD-04 / CR-02 | alto | página mandava `REVERB_SCHEME=https`/`REVERB_PORT=443` no `.env` — quebra o broadcast servidor-para-servidor | P-20 | CT-30 (2 linhas: frase com as três `REVERB_*` e "não"; nenhum bloco com `REVERB_SCHEME=https`/`REVERB_PORT=443`) · M190, M191 |
| RD-05 | — | `.env.docker` fora de `CAMINHOS_DO_KIT`: CT-23/CT-24/CT-39 viajariam sem o arquivo | P-23 | CT-37 (1 linha) · M165 |
| RD-06 | — | chave ausente + `Host: *.on-forge.com`/`*.on-vapor.com` vira `'*'` (vendor, anterior ao diff) | — | sem CT — `## Cogitado e cortado` (só o texto do docblock muda) |
| RD-07 | — | CT-34 e CT-46 viajam para o projeto instalado sem saída lá | P-24 | CT-34 e CT-46 com `skip` fora da árvore (oráculo reescrito; mensagem sem `docs`) |
| RD-08 | — | cabeçalho do exemplo oferecia `TRUSTED_PROXIES=*` sem a ressalva do loopback | — (D7/P-21) | CT-15 (1 linha) · M166 |
| CR-03 | — | `*` confia em qualquer container da `my-network`; o loopback não isola da rede compartilhada | P-21 | CT-41 (2 linhas: `ipv4_address` + "IP fixo"; `` `*` `` + "rede" + "container") — a sessão apontou CT-42; ver nota de CT-41 · M187, M188 · L7 reaberta |
| CR-05 | — | receita da Opção D não funcionava: `app` fora da rede do llama; faltava `LLAMACPP_EMBED_URL` | P-22 | CT-31 (linha receita da D reescrita) · M193…M195 |
| CR-06 | — | homol `8080` colide com o `llamacpp` (8080) com `--profile ai`; portas dos profiles sem cobertura | P-22 | CT-30 (linha portas dos profiles: `FORWARD_LLAMA_PORT` na seção da matriz) · M192 |
| CR-07 | — | golden em JSON gerado pelo Compose 5.5.1 frágil à versão do CI | P-24 (substitui P-17) | CT-34 reescrito (fixture textual, dois lados pelo mesmo CLI), Setup Global, `## Costuras — notas` · M131 → L11 |
| CR-08 | — | CT-01/CT-36/CT-39 faziam `glob` na raiz e pegavam a cópia ativa ignorada pelo git | — (roteado a teste) | oráculo reescrito CT-01, CT-36, CT-39 + Setup Global (lista dos **rastreados**; fora da árvore, o `glob` menos `docker-compose.override.yml`) |
| CR-09 | — | aviso de DNS nomeava `app` e `reverb` como nomes em risco; o risco é o `nginx` resolver `app` e o `reverb` resolver `pgsql`/`redis` | P-25 | CT-47 (2 linhas) · M189 |

#### P-nn → CT (v4)

| Premissa | Estado no `00` | CT de destino |
|---|---|---|
| P-02 | alterada (lida pelo `config`, aplicada no boot) | CT-48 (novo), CT-21/CT-22 (costura G6) |
| P-13 | reescrita (com cache vale o valor cacheado) | CT-41 (reescrito), CT-48 (linha de config); runtime: L9 |
| P-17 | substituída por P-24 | — (nenhum cenário a cita) |
| P-18 | vigente | CT-20 (7 linhas), CT-49 (novo), CT-50 (novo, R21), CT-21 (1 linha), CT-22 (1 linha) |
| P-19 | vigente | CT-20 (2 linhas), CT-21 (1 linha), CT-49 (1 linha) |
| P-20 | vigente | CT-30 (2 linhas) |
| P-21 | vigente | CT-41 (2 linhas), CT-15 (1 linha — RD-08); risco aceito: L7 |
| P-22 | vigente | CT-31 (linha reescrita), CT-30 (1 linha) |
| P-23 | vigente | CT-37 (1 linha) |
| P-24 | vigente | CT-34 (reescrito), CT-46 (`skip` fora da árvore); procedência: L11 |
| P-25 | vigente | CT-47 (2 linhas) |
