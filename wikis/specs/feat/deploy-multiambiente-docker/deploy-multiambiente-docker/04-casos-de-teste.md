# Casos de Teste — Deploy com Docker: vários ambientes não produtivos no mesmo servidor, atrás do Traefik

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths e artefatos) · Decisões: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`: não exigida)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação, que
> ainda não existe. O código lido foi o **atual**, e só para herdar convenção de teste (helpers de
> `tests/Pest.php`, `MysqlNoDockerTest`, `DeployDockerLocalTest`, `BooleanoDoEnvTest`,
> `UrlSemPrefixoPublicTest`) e os nomes que já existem (`docker-compose.yml`, `Dockerfile.laravel`,
> `.env.docker`, `.env.example`, `deploy_docker_local.sh`, `bootstrap/app.php`,
> `App\Console\Commands\KitUpdate::CAMINHOS_DO_KIT`).
> Derivação feita em sub-agente; perguntas renumeradas pela sessão e levadas ao `00`;
> `## Costuras de Teste` **confirmadas** pela sessão. **Revisão adversarial feita**: 1 rodada
> (`fw-adversario-ct`, cego ao PRD e ao código), **36 achados, todos fechados** — destino de cada um
> em `## Revisão Adversarial`.

## Perfil de Derivação

| Área | O que é | P | I | P×I | Perfil |
|---|---|---|---|---|---|
| **A — opt-in e default intocado** | semântica de carga e merge do Compose (override automático, `ports` concatenados), três vias de entrega (git, `create-project`, `kit:update`) | 3 | 3 | **9** | completo |
| **B — contrato Traefik do exemplo** | interpolação de label em lista, rede externa, nomes globais no Traefik, duas rotas do Reverb | 3 | 2 | **6** | padrão |
| **C — `VITE_REVERB_*` no build** | `ARG` por estágio do Dockerfile, args em seis serviços que compartilham o estágio `assets`; o bundle do kit sem Echo | 2 | 2 | **4** | padrão |
| **D — proxies confiáveis** | parse de chave do `.env` e fronteira de confiança de `X-Forwarded-*` | 2 | 3 | **6** | padrão (R10 escalada a completo) |
| **E — chaves de `.env` e portas entre ambientes** | arquivos de sugestão copiados pelo usuário; colisão de porta no host | 2 | 2 | **4** | padrão |
| **F — documentação, README e site** | prosa pt/en, sidebar e stubs gerados | 2 | 2 | **4** | padrão |

**Impacto 3 em A**: errar aqui muda o deploy de **todo** projeto que já usa o kit, em silêncio
(RQ-04: "como roda outros projetos"). **Impacto 3 em D**: `TRUSTED_PROXIES` decide de quem a
aplicação aceita `X-Forwarded-For/Proto/Host` — confiar por engano é IP forjado no log de
autenticação e no rate limit. **Escalada**: R10 usa EP exaustiva porque a regra é a fronteira de
confiança e cada partição tem modo de falha próprio.

**Revisão adversarial: feita** (obrigatória por Impacto 3 em A e D) — 1 rodada, 36 achados, todos
fechados. Os mutantes trazidos por ela (M83…M127) **não contam para o teto** do perfil
(`SKILL.md` §Passo 6, item 1): onde a tabela de uma regra passa do teto, a linha
`Estouro do teto` abaixo dela registra a origem.

- Técnicas aplicadas: EP (partição exaustiva em R10; por arquivo × chave em R13), tabela de decisão (R11/R12: chamador × valor de `TRUSTED_PROXIES` × saídas), rastreio de efeito como diff profundo de configuração (R3), golden de configuração efetiva (R1, CT-34), contrato entre arquivos (R8, R13), regex sobre regra interpolada (R7), BVA não se aplica (nenhuma faixa ordenável: portas são identidades, não faixas)
- Cenários: 43 · Regras: 20 · Mutantes previstos: 127 · Sem matador: 0
<!-- derivado por grep -c; recalcular a cada cenário novo -->

### Divergências declaradas (skill × rule do projeto)

- `.ai/rules/testes.md` vence: todo caso que lê `docs/`, `README*.md`, `site/` ou roda `git` sobre o
  índice leva `->skip(fn (): bool => ! naArvoreDoKit(), …)`; asserção de ausência roda só sobre
  texto sem comentário; `toContain()` nunca recebe mensagem (ausência por
  `assertStringNotContainsString`).
- A skill não tem costura para "leitura de arquivo de infra com a aplicação de pé" nem para "CLI
  externo": os dois ficam em `Pest feature HTTP` (teste em `tests/Kit` com `TestCase`), que é o
  padrão já usado por `MysqlNoDockerTest` e `DeployDockerLocalTest`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | `docker/traefik/docker-compose.override.yml` (exemplo), `.gitignore`, `Dockerfile.laravel` (estágio `assets`), `.env.example`, `.env.docker`, `bootstrap/app.php`, `app/Support/ProxiesConfiaveis.php`, docs pt/en, README pt/en, `CHANGELOG.md`, `site/sidebar.json`, stubs. Intocados: `docker-compose.yml`, `deploy_docker_local.sh`, `docker/nginx/nginx.conf`, `resources/js`, `package.json` | CT-01…CT-07, CT-17, CT-27, CT-28, CT-34, CT-35, CT-36, CT-43 |
| F | (1) ligar o Traefik por cópia do exemplo; (2) não ligar nada sem a cópia; (3) parse de `TRUSTED_PROXIES`; (4) honrar `X-Forwarded-*` só de proxy confiável; (5) passar `VITE_REVERB_*` ao build; (6) ensinar o procedimento | CT-01, CT-06, CT-08…CT-22, CT-33, CT-38 |
| D | valores de `.env` (ausente, vazio, só espaços, lista, `*`, tokens do Symfony, não-string); três `.env` (dev/teste/homol); nomes de projeto; hostnames; a matriz de portas do requisito, literal | CT-08, CT-11, CT-12, CT-20, CT-21, CT-22, CT-25, CT-26, CT-40 |
| I | `docker compose` (carga automática do override, `--profile app`), `docker build` (args), request HTTP com cabeçalhos `X-Forwarded-*`, `git` (ignore/índice), `kit:update` (`CAMINHOS_DO_KIT`), `create-project` (`.gitattributes`), Traefik (docker provider por labels) | CT-01, CT-03, CT-04, CT-05, CT-21, CT-22, CT-37 |
| P | Docker Compose v5.5.1 local × v2.x do `ubuntu-latest` (comportamento de label em lista medido só na v5.5.1); Windows sem CLI → `skip`; ambiente do processo PHP **vaza** chaves do `.env` do desenvolvedor para o subprocesso `docker compose` (Laravel escreve no `putenv`, a `Process` herda) — tratado no Setup Global; Vite trata `VITE_*` vazia de `process.env` como **definida** (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5705`); `config:cache` fora do Docker congela a configuração e o `.env` deixa de ser lido (P-13) | CT-11, CT-19, CT-34, CT-41, Setup Global |
| O | três checkouts no mesmo servidor, Opção A; quem não usa Traefik (todo projeto que já existe) copiando `.env.docker` como sempre fez; operador que esquece `TRAEFIK_HOST` ou o deixa vazio; Reverb pelo Traefik ou por porta | CT-12, CT-14, CT-23, CT-25, CT-26, CT-39 |
| T | não se aplica a relógio; **estado estático entre casos**: `TrustProxies::$alwaysTrustProxies` é estático do processo — o `tearDown` do framework chama `TrustProxies::flushState()` (`vendor/laravel/framework/src/Illuminate/Foundation/Testing/Concerns/InteractsWithTestCaseLifecycle.php:tearDownTheTestEnvironment:126`), e o env alterado no caso é restaurado pelo helper | CT-21, CT-22 (independência) |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Sem a cópia ativa do override, a stack do kit não muda | A (completo) | RQ-04, RQ-05, RQ-09, RQ-11, P-08 | EP por arquivo-base + execução do Compose + golden da configuração efetiva | CT-01, CT-02, CT-03, CT-33, CT-34, CT-35, CT-36 |
| R2 — O exemplo chega a quem instala; a cópia ativa nunca entra no git nem no `kit:update` | A (completo) | P-03, RQ-11 | EP por via de entrega | CT-04, CT-05, CT-37 |
| R3 — Ligado o override, só muda o que o Traefik e o build precisam | A (completo) | RQ-04, RQ-07, RQ-10, RQ-11, RQ-14, P-06 | rastreio de efeito (diff profundo de configuração) | CT-06, CT-07 |
| R4 — O `nginx` entra na rede externa sem sair da própria, e a rede é coerente | B (padrão) | RQ-07, RQ-08, P-04 | EP (rede ausente × vazia × definida) | CT-08, CT-09 |
| R5 — Labels do docker provider com os valores do requisito | B (padrão) | RQ-07, RQ-09 | EP (valor exato por label) | CT-10 |
| R6 — Router/service únicos por ambiente; hostname nunca fixo | B (padrão) | RQ-08, RQ-17, P-04, P-05 | EP (nome do projeto) + estado de erro com saída | CT-11, CT-12, CT-13 |
| R7 — Reverb: duas rotas, nenhuma imposta; a do Traefik não captura painel | B (padrão) | RQ-12, RQ-04, P-04, P-10 | EP + invariante + regex sobre a regra interpolada | CT-14, CT-15, CT-16 |
| R8 — Os quatro `VITE_REVERB_*` chegam ao `npm run build` de toda imagem | C (padrão) | RQ-14 | contrato estático + contrato entre serviços | CT-17, CT-18 |
| R9 — Sem build-arg, o build é o de hoje | C (padrão) | RQ-02, RQ-04, P-01, P-12 | EP (declaração do `ARG`, origem do valor) | CT-19, CT-38 |
| R10 — Interpretação de `TRUSTED_PROXIES` | D (escalada a completo) | P-02, P-11 | EP exaustiva | CT-20 |
| R11 — Ausente, vazia ou lista sem o chamador: `X-Forwarded-*` ignorados | D (padrão) | P-02, P-11, RQ-05 (invariante, vale com qualquer resposta a Q1) | tabela de decisão | CT-21 |
| R12 — `*` ou lista com o chamador: a aplicação honra `X-Forwarded-*` | D (padrão) | P-02, RQ-09 | tabela de decisão | CT-22 |
| R13 — Chaves novas só como linha comentada; o `.env.docker` oferece o que o exemplo consome; a sugestão não colide | E (padrão) | RQ-02, RQ-05, RQ-06, RQ-10, RQ-16, P-02, P-04, P-09 | EP arquivo × chave + contrato | CT-23, CT-24, CT-25, CT-39, CT-40 |
| R14 — Três ambientes com a matriz do requisito não disputam porta nem nome | E (padrão) | RQ-06, RQ-16, P-09 | valor literal do requisito | CT-26 |
| R15 — A página existe nos dois idiomas e é alcançável; o CHANGELOG registra | F (padrão) | RQ-01, RQ-03 | EP por idioma | CT-27, CT-28 |
| R16 — A página ensina o mecanismo central, o encaixe no Traefik e o passo a passo | F (padrão) | RQ-06, RQ-07, RQ-08, RQ-09, RQ-11, P-13 | EP por âncora | CT-29, CT-41 |
| R17 — A página cobre portas, matriz, o build do Reverb e as duas rotas | F (padrão) | RQ-10, RQ-12, RQ-14, RQ-16, P-06, P-09 | valor literal do requisito | CT-30, CT-42 |
| R18 — A página apresenta as opções A–D e as armadilhas | F (padrão) | RQ-13, RQ-15, P-07 | EP por âncora | CT-31 |
| R19 — pt e en são espelho | F (padrão) | RQ-01 | contrato entre arquivos | CT-32 |
| R20 — O bundle do kit continua sem Echo | C (padrão) | P-01, RQ-04 | EP por arquivo (ausência no texto ativo) | CT-43 |

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
  Q11). Por isso têm regra e cenário (R7, R13, R14, R17) e nenhuma linha "aberta" aqui.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Leitura estática de infra | R1 (CT-02, CT-03, CT-35, CT-36), R2 (CT-05, CT-37), R3 (CT-07), R6 (CT-13), R7 (CT-15, CT-16), R8 (CT-17), R9 (CT-19), R13 (CT-23, CT-24), R20 (CT-43) | Pest feature HTTP | existente — padrão de `tests/Kit/MysqlNoDockerTest.php` (`blocoDoServico()`, filtro de comentário) e `tests/Kit/DeployDockerLocalTest.php`; arquivo novo `tests/Kit/DeployMultiambienteDockerTest.php` | o `Então` afirma texto ativo de arquivo entregue; roda sem Docker (inclusive no Windows sem CLI) | sessão, 2026-10-05 |
| G2 — `docker compose config` em pasta temporária | R1 (CT-01, CT-33, CT-34), R3 (CT-06), R4, R5, R6 (CT-11, CT-12), R7 (CT-14), R8 (CT-18), R9 (CT-38), R13 (CT-25, CT-39, CT-40), R14 | Pest feature HTTP | **nova** — nenhum teste do kit executa o CLI do Compose; interpolação de label, merge de `ports` e carga automática do override só o Compose resolve. Mesmo arquivo de G1. `skip` quando `docker compose version` falha | o observável é a configuração **efetiva** que o Compose montaria; regex sobre YAML não vê interpolação nem merge | sessão, 2026-10-05 — o CLI está no Windows local (v5.5.1) e no `ubuntu-latest` |
| G3 — Índice e ignore do git | R2 (CT-04) | Pest feature HTTP | existente — CT de bit de execução de `DeployDockerLocalTest.php` (`git ls-files` na árvore) | o `Então` é estado do repositório; só existe na árvore do kit → `skip` fora dela | sessão, 2026-10-05 |
| G4 — Documentação, README e site | R15–R19 (CT-27…CT-32, CT-41, CT-42) | Pest feature HTTP | existente — `paginasDoSite()`, `secoesDoMarkdown()`, `naArvoreDoKit()` de `tests/Pest.php`; mesmo arquivo de G1 | texto entregue; `docs/` e `site/` são `export-ignore` → `skip` fora da árvore | sessão, 2026-10-05 |
| G5 — Parse de `TRUSTED_PROXIES` | R10 | unit de regra | existente — forma de `tests/Kit/BooleanoDoEnvTest.php` (dataset sobre função pura de `app/Support`); arquivo novo `tests/Kit/ProxiesConfiaveisTest.php` | o `Então` é valor calculado | sessão, 2026-10-05 |
| G6 — Request pela configuração real de middleware | R11, R12 | Pest feature HTTP | **nova** — nenhum teste re-roda o `withMiddleware` de `bootstrap/app.php` com env diferente. Proposta: fixar o env nas três formas (`putenv`, `$_ENV`, `$_SERVER`), `app()->forgetInstance(\Illuminate\Contracts\Http\Kernel::class)` para o `afterResolving` de `ApplicationBuilder::withMiddleware` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:withMiddleware:287`) rodar de novo no próximo `get()`; alternativa se a primeira não medir: `refreshApplication()` com rota de teste **sem banco**. A costura precisa ser **medida** antes de confirmada. Arquivo `tests/Kit/ProxiesConfiaveisTest.php` | o requisito afirma comportamento HTTP (esquema, host, URL e IP vistos pela aplicação), não a chamada a `trustProxies()` | sessão, 2026-10-05 — confirmada **com a medição delegada ao executor**: tenta (a) `forgetInstance`; se não medir, (b) `refreshApplication()`; se nenhuma, abre lacuna e devolve o fato como texto |

Nenhuma costura `browser` → sem `05`.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome e assinatura `ProxiesConfiaveis::doEnv(mixed): array\|string\|null` | escolha de implementação | detalhe do cenário (CT-20) |
| retorno `null` para não-string (`true`, `1`) | o `00` não decide; aceito como **falha fechado**, coerente com P-02 ("ausente = nenhum proxy") | linha do Esquema de CT-20, marcada |
| `' * '` → `'*'` | aceito: o operador escreveu `*` (P-02); devolver `[' * ']`/`['*']` faz o Laravel confiar em ninguém sem aviso | linha do Esquema de CT-20 e de CT-22 |
| `trustProxies(...)` **antes** do `append(RaizDeUrlSemPublic)` no `bootstrap/app.php` | sem efeito observável: a ordem das chamadas dentro do closure não muda a ordem do stack global, que já é coberta por CT-13 de `UrlSemPrefixoPublicTest` | recusado |
| `# TRUSTED_PROXIES=` "logo abaixo de `APP_URL`" no `.env.example` | posição cosmética | recusado; CT-23 afirma só linha comentada presente e nenhuma linha ativa |
| âncora `x-vite-args` e "os seis serviços" listados | mecanismo; a lista é derivada do **base** (todo serviço com `build:`), não do plano | CT-18 enumera do base |
| `${TRAEFIK_HOST:?…}` — texto da mensagem | o requisito não fixa texto | CT-12 afirma só código ≠ 0 e o nome da chave no erro |
| regra do Reverb `PathPrefix(/app) \|\| PathPrefix(/apps)` | **contraria RQ-04**: o painel do kit vive em `/app` (`app/Providers/Filament/AppPanelProvider.php:path:79`) e a regra mais longa ganha prioridade no Traefik | invariante CT-16 + P-10 |
| `ARG VITE_REVERB_*=` com `ENV` promovendo os quatro | **contraria RQ-04**: com o `ENV` vazio o Vite passa a ver `''` onde hoje vê `undefined` (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5705`) | CT-19 + P-12 |
| `# FORWARD_APP_PORT=127.0.0.1:8090` no bloco do `.env.docker` | valor do plano; o requisito (RQ-16, nota ²) diz que 8090 colide com o Reverb publicado no default | CT-25 afirma a não-colisão, sem fixar o valor |
| `sidebar: order: 6`, texto exato da frase do índice de Operação | cosmético | recusado; CT-27 afirma o link e o slug |
| URL do README `https://gsferro.github.io/filament-starter-kit-easy/{pt,en}/operacao/deploy-docker-multiambiente.html` | o domínio é do site existente; o oráculo é o **sufixo** `/{idioma}/operacao/deploy-docker-multiambiente.html` casar com uma página que existe | CT-27 |

## Setup Global

### Personas
- Não há persona: nenhuma rota, painel ou autorização nova. O "ator" dos cenários é **o operador do servidor** (quem copia arquivos e edita o `.env`) ou **o Traefik** (quem faz o request com `X-Forwarded-*`).

### Fixtures — G2 (`docker compose config`)
- Pasta temporária por caso (`sys_get_temp_dir()` + id único; apagada no `afterEach`), com: cópia do `docker-compose.yml` do kit; `.env` escrito pelo caso (o base declara `env_file: .env`, sem ele o Compose recusa); e, conforme o `Dado`, o exemplo copiado **para a raiz** como `docker-compose.override.yml` ou **para `docker/traefik/`** no mesmo caminho do kit.
- Comando: `docker compose --profile app config --format json`, **sem `-f`** (a carga automática do override é o que está em teste), com a pasta temporária como `cwd`.
- **Armadilha de ambiente (P de SFDIPOT), obrigatória**: o Laravel escreve as chaves do `.env` do desenvolvedor no `putenv`, e a `Symfony\Component\Process\Process` herda o ambiente do PHP. Variável de shell **vence** o `.env` na interpolação do Compose — um `COMPOSE_PROJECT_NAME=starter-kit` ou `COMPOSE_FILE` herdado invalida o caso em silêncio. A `Process` recebe todo nome presente em `getenv()`, `$_ENV` e `$_SERVER` com valor `false` (remove), exceto a lista de sistema: `PATH`/`Path`, `SystemRoot`, `TEMP`, `TMP`, `HOME`, `USERPROFILE`, `APPDATA`, `LOCALAPPDATA`, `ProgramData`, `DOCKER_HOST`, `DOCKER_CONFIG`, `DOCKER_CONTEXT`.
- `skip`: `docker compose version` com código ≠ 0 → `->skip('CLI do Docker Compose ausente')`. CT de G2 **não** leem `docs/` — não levam `naArvoreDoKit()`.
- Leitura do JSON: `services.<s>.labels` (mapa), `services.<s>.networks` (mapa), `services.<s>.ports` (lista com `host_ip`, `published`, `target`), `services.<s>.environment` (mapa), `services.<s>.build.args` (mapa), `networks.<chave>.{name,external}`, `volumes.<chave>.name`, `name` de topo.
- **Golden do base (CT-34)**: fixture versionado `tests/Kit/fixtures/compose-base.json`, gerado **uma vez** pelo executor com `docker compose --profile app config --format json` sobre a pasta temporária de CT-34 (só o `docker-compose.yml` e o `.env` mínimo do caso) e **normalizado**: `build.context`, `volumes[].source` de bind e qualquer path absoluto trocados por `<raiz>`; `name` de topo e `networks/volumes[].name` mantidos. O caso aplica a mesma normalização à saída do momento antes de comparar. Regenerar o fixture é o **ato deliberado** de quem muda o base de propósito — dito no docblock do caso.
- Helpers ficam **locais** a `DeployMultiambienteDockerTest.php` (um consumidor só — `.ai/rules/testes.md`).

### Fixtures — G6 (request)
- Rota **de teste**, declarada no caso, fora do grupo `web` (sem sessão nem banco): devolve `request()->isSecure()`, `request()->getHost()`, `url('/x')` e `request()->ip()`.
- Request: `REMOTE_ADDR` da linha do caso (`withServerVariables`), `Host: interno.local`, `X-Forwarded-For: 203.0.113.9`, `X-Forwarded-Proto: https`, `X-Forwarded-Host: dev.exemplo.test`, `X-Forwarded-Port: 443`; `config(['app.url' => 'https://dev.exemplo.test'])` antes do request.
- Helper local `comTrustedProxies(?string $valor)`: `null` remove a chave das três formas; string grava nas três; restaura o anterior no `afterEach` (forma de `kitConfigCom()` em `tests/Pest.php`). O `Dado` de cada linha **afirma o valor efetivo lido** (`env('TRUSTED_PROXIES')`, que para a string `true` devolve o bool `true`) antes do request — o caso não pode medir o `.env` do desenvolvedor.

### Fakes
- Nenhum: não há fila, e-mail, notificação nem HTTP externo.

### Estratégia de DB
- `RefreshDatabase` global de `tests/Kit` (herdado, irrelevante: nenhum caso toca o banco). Se G6 cair na alternativa `refreshApplication()`, a rota de teste **não** pode tocar banco (`RegistroAbertoTest.php` registra que o `:memory:` morre no refresh).

---

## Regra R1 — Sem a cópia ativa do override, a stack do kit não muda

> `RQ-04`, `RQ-05`, `RQ-09`, `RQ-11`, `P-08` · perfil **completo** · técnica: **EP por arquivo-base** + execução real do Compose + **golden** da configuração efetiva (CT-34)

```gherkin
# language: pt
Funcionalidade: Deploy multiambiente atrás do Traefik é opt-in

  Regra: sem a cópia ativa do override, nada do Traefik entra na configuração

    Cenário: [CT-01] o exemplo em docker/traefik não é carregado pelo Compose
      Dado uma pasta com os arquivos de Compose que o kit entrega na raiz e o exemplo em "docker/traefik/docker-compose.override.yml"
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

    Cenário: [CT-03] o script de deploy chama o Compose sem fixar arquivo, projeto nem env-file
      Dado o "deploy_docker_local.sh", sem as linhas de comentário
      Quando o teste lê cada linha que invoca "docker compose"
      Então nenhuma passa "-f", "--file", "-p", "--project-name" nem "--env-file", e o script não define COMPOSE_FILE nem COMPOSE_PROJECT_NAME
      E ao menos uma linha invoca "docker compose" com "up -d --build"
      E a linha do health check obtém a porta de "docker compose --profile app port nginx 80" e não interpola "${FORWARD_APP_PORT"

    Cenário: [CT-33] sem o override e sem a chave no .env, nenhum serviço recebe TRUSTED_PROXIES
      Dado uma pasta só com o docker-compose.yml do kit e um .env sem TRUSTED_PROXIES
      Quando o operador roda "docker compose --profile app config" na pasta
      Então nenhum serviço tem a chave "TRUSTED_PROXIES" em "environment"

    Cenário: [CT-34] a configuração efetiva do base é a do golden versionado
      Dado uma pasta só com o docker-compose.yml do kit e um .env com COMPOSE_PROJECT_NAME "starter-kit" e as DB_* default das linhas ativas do .env.docker
      Quando o operador roda "docker compose --profile app config --format json" na pasta e o teste normaliza os caminhos absolutos para "<raiz>"
      Então o "name" de topo, o conjunto de serviços e as "networks" e "volumes" de topo são os de "tests/Kit/fixtures/compose-base.json"
      E em todo serviço "ports", "command", "restart", "env_file", "environment", "volumes", "profiles", "depends_on", "healthcheck", "build.target" e "user" são iguais aos do fixture

    Cenário: [CT-35] o nginx.conf não passa a honrar cabeçalho de proxy por conta própria
      Dado o "docker/nginx/nginx.conf" do kit, sem as linhas de comentário
      Quando o teste procura diretivas de proxy
      Então o texto não contém "fastcgi_param HTTPS", "X_FORWARDED", "set_real_ip_from" nem "real_ip_header"

    Cenário: [CT-36] a raiz do kit tem um arquivo de Compose só, que lê o .env literal
      Dado a raiz do kit
      Quando o teste lista "compose*.y*ml" e "docker-compose*.y*ml" e lê o "env_file" do docker-compose.yml
      Então o único arquivo listado é "docker-compose.yml"
      E app, queue, scheduler, reverb e pulse declaram "env_file: .env" literal, sem interpolação
```

`[CT-01]` copia para a pasta **todo arquivo de Compose da raiz do kit** (`docker-compose*.y*ml`,
`compose*.y*ml`) — não só o `docker-compose.yml` — para que um override commitado na raiz por engano
seja carregado e reprove o caso. `[CT-36]` é o par estático (sem CLI) dessa mesma proteção.
`[CT-34]` leva `skip` sem CLI do Compose; o docblock diz que o fixture **expira quando o base mudar
de propósito** e que regenerá-lo (comando e normalização do Setup Global) é o ato deliberado que
acompanha essa mudança. `[CT-03]`: o health check pela porta que o Docker publicou é o que mantém
P-08 verdadeira com `FORWARD_APP_PORT=127.0.0.1:…`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o kit entrega o exemplo **na raiz** como `docker-compose.override.yml` ("para facilitar") | CT-01, CT-36 | a cópia da raiz é carregada sem `-f`: aparece label `traefik.enable` no nginx; o caso exige zero labels `traefik.` / CT-36 lista dois arquivos |
| M2 | `networks:` + `labels:` do Traefik escritos direto no `docker-compose.yml` base | CT-02 (linha compose base) | `^\s*labels:` encontrado no texto ativo; o caso exige ausência |
| M3 | default da porta do nginx muda para loopback (`127.0.0.1:8000:80`) "por segurança" | CT-02 (coluna preservado), CT-34 | a regex `- '${FORWARD_APP_PORT:-8000}:80'` não casa mais / `ports` do nginx com `host_ip` diferente do fixture |
| M4 | `nginx.conf` ganha `listen 443 ssl` para "suportar HTTPS" | CT-02 (linha nginx.conf) | `listen 443` no texto ativo |
| M5 | script passa a usar `docker compose -f docker-compose.yml …` (o override nunca carrega — P-08) | CT-03 | linha ativa com `-f`; o caso exige nenhuma |
| M83 | base ganha `environment: TRUSTED_PROXIES: ${TRUSTED_PROXIES:-*}` no bloco comum dos serviços PHP "para o Traefik funcionar" (ADV-01) | CT-33 | `environment.TRUSTED_PROXIES` = `*` em `app`; o caso exige a chave ausente em todo serviço |
| M84 | base passa a publicar o `pgsql` em loopback (`127.0.0.1:${FORWARD_DB_PORT:-5432}:5432`) "porque atrás do Traefik não precisa" (ADV-02) | CT-34 | `services.pgsql.ports[0].host_ip` = `127.0.0.1`; o fixture não tem `host_ip` |
| M85 | base troca `restart: unless-stopped` por `always` ou mexe no `healthcheck` do `app` junto com a entrega (ADV-02) | CT-34 | `restart`/`healthcheck` do serviço difere do fixture |
| M86 | `nginx.conf` ganha `fastcgi_param HTTPS on;` "porque o TLS termina no Traefik" — todo projeto sem Traefik passa a gerar URL `https` (ADV-06) | CT-35 | `fastcgi_param HTTPS` no texto ativo |
| M87 | o kit entrega `compose.override.yaml` ou `docker-compose.traefik.yml` na raiz (ADV-22) | CT-36 | `glob` lista um segundo arquivo além de `docker-compose.yml` |
| M88 | `env_file: ${ENV_FILE:-.env}` no base, transplantando a parametrização da Opção B (ADV-22) | CT-36 | `env_file` do `app` não é o literal `.env` |
| M89 | script passa `-p "$COMPOSE_PROJECT_NAME"` ou `--env-file .env` "para isolar o ambiente" (ADV-17) | CT-03 | linha ativa com `-p`/`--env-file`; o caso exige nenhuma |
| M90 | health check do script monta a URL com `${FORWARD_APP_PORT:-8000}` — com `127.0.0.1:8090` a URL vira `http://127.0.0.1:127.0.0.1:8090` (ADV-35) | CT-03 | linha do health check interpola `${FORWARD_APP_PORT`; o caso exige a porta vinda de `port nginx 80` |

Estouro do teto (completo: 6): M83…M90 — revisão adversarial (ADV-01, ADV-02, ADV-06, ADV-17, ADV-22, ADV-35).

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
      Então algum caminho do kit:update é prefixo de "docker/traefik/docker-compose.override.yml"
      E nenhuma linha "export-ignore" do .gitattributes cobre esse caminho

    Cenário: [CT-37] o kit:update nunca sobrescreve a cópia ativa do servidor
      Dado a lista de caminhos do kit:update
      Quando o teste procura a cópia ativa "docker-compose.override.yml" da raiz nela
      Então nenhum caminho da lista é igual a "docker-compose.override.yml"
      E nenhum caminho da lista é prefixo dele (nem "", nem ".", nem "docker-compose")
```

`[CT-04]` leva `->skip(fn (): bool => ! naArvoreDoKit(), …)`. O `--no-index` é obrigatório: sem ele o
`git check-ignore` responde "não ignorado" para arquivo já versionado, mesmo casando uma linha do
`.gitignore`. `[CT-05]` e `[CT-37]` usam `caminhosDoKit()` de `tests/Pest.php` (Reflection sobre
`App\Console\Commands\KitUpdate::CAMINHOS_DO_KIT`, privada).

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

Estouro do teto (completo: 6): M91, M92 — revisão adversarial (ADV-10, ADV-25).

---

## Regra R3 — Ligado o override, só muda o que o Traefik e o build precisam

> `RQ-04`, `RQ-07`, `RQ-10`, `RQ-11`, `RQ-14`, `P-06` · perfil **completo** · técnica: **rastreio de efeito** (diff profundo entre a configuração sem e com o override)

```gherkin
  Regra: o override só acrescenta rede e labels ao nginx e args de build

    Cenário: [CT-06] a configuração com o override difere do base só nos caminhos permitidos
      Dado um .env com COMPOSE_PROJECT_NAME "proj-dev" e TRAEFIK_HOST "dev.exemplo.test"
      E a configuração JSON do base sozinho e a do base com o exemplo copiado na raiz, ambas com esse .env
      Quando o teste calcula o diff profundo das duas, folha a folha
      Então o diff, menos os caminhos "services.nginx.networks.<rede do Traefik>", "services.nginx.labels.traefik.*", "services.*.build.args.VITE_REVERB_*" e "networks.<rede do Traefik>", é vazio

    Cenário: [CT-07] todo serviço do exemplo existe no base
      Dado o exemplo e o docker-compose.yml do kit
      Quando o teste lê as chaves de serviço dos dois arquivos (coluna 2 sob "services:")
      Então toda chave de serviço do exemplo é também chave de serviço do base
```

`[CT-06]` compara **toda folha** das duas árvores JSON (adição, remoção e troca de valor), não uma lista
de chaves escolhidas: a chave que ninguém lembrou de listar é onde o defeito passa. `[CT-07]` é
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
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M21 | service na porta 8000 (confundida com a publicada) | CT-10 | `loadbalancer.server.port` = `8000`, esperado `80` |
| M22 | entrypoint `web` | CT-10 | `entrypoints` = `web` |
| M23 | `tls=true` esquecido | CT-10 | label ausente |
| M24 | `traefik.enable=true` esquecido (Traefik com `exposedByDefault=false` ignora o container) | CT-10 | label ausente |

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

> `RQ-12`, `RQ-04`, `P-04`, `P-10` · perfil **padrão** · técnica: **EP** (rota ativa × comentada) + **invariante** (CT-16, origem RQ-04) + **regex** sobre a regra interpolada (recorte de P-10)

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

    Esquema do Cenário: [CT-16] a regra do Reverb pelo Traefik recorta pela chave e não captura caminho de painel
      Dado a regra do router "-reverb" do exemplo, interpolada com TRAEFIK_HOST "dev.exemplo.test", REVERB_APP_KEY "<chave>" e REVERB_APP_ID "<id>"
      Quando o teste confronta a regra interpolada e cada PathPrefix dela com caminhos do kit
      Então a regra casa "^Host\(`[^`]+`\) && \(PathPrefix\(`/app/[^`]+`\) \|\| PathPrefix\(`/apps/[^`]+`\)\)$"
      E nenhum PathPrefix é prefixo de "/app", "/app/login", "/app/acme/users", "/admin" ou "/infra"
      E algum PathPrefix é prefixo de "/app/<chave>" e algum de "/apps/<id>/events"
      E a regra não contém "<ausente>"

      Exemplos:
        | chave            | id           | ausente          | # partição                          |
        | starter-kit-key  | starter-kit  | ${               | valores do .env.docker              |
        | outra-chave      | outro-id     | starter-kit-key  | outra chave: o valor vem do .env    |
```

`[CT-16]` tem **controle positivo** (a linha "algum PathPrefix é prefixo"): uma regra que não casa
nada não passa. O painel `/app` é do kit: `app/Providers/Filament/AppPanelProvider.php:path:79`
(`->path('app')`). A regex exige os **parênteses** em volta do `||`: no Traefik `&&` precede `||`, e
sem eles `PathPrefix(/apps/…)` casa em **qualquer** host — inclusive o dos outros ambientes.

```gherkin
  Regra: o bloco comentado do Reverb, descomentado entre os marcadores, é uma configuração válida

    Cenário: [CT-44] descomentar o bloco entre os marcadores liga a rota do Reverb pelo Traefik
      Dado o exemplo copiado na raiz com as linhas entre "# >>> reverb-traefik" e "# <<< reverb-traefik" descomentadas (o "# " inicial removido)
      E um .env com TRAEFIK_HOST "dev.exemplo.test", COMPOSE_PROJECT_NAME "proj-dev", REVERB_APP_KEY "chave-x" e REVERB_APP_ID "id-y"
      Quando o operador roda "docker compose --profile app config"
      Então o comando sai com código 0
      E o reverb está na rede externa e tem "traefik.docker.network" igual à rede externa
      E o router "proj-dev-reverb" tem regra que casa a regex de R7 com "/app/chave-x" e "/apps/id-y", entrypoint "websecure" e tls "true"
      E o service "proj-dev-reverb" aponta a porta 8090
      E o nginx continua com o router "proj-dev" e sem o sufixo "-reverb"
```

`[CT-44]` fecha a lacuna L3 (Q10 decidida como D6 no `01`: os marcadores existem para isto). Os
marcadores são a única âncora: o teste não adivinha onde o bloco começa.

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

Estouro do teto (padrão: 5): M99, M100 — revisão adversarial (ADV-13, ADV-14).

| M128 | bloco comentado do Reverb com YAML que não valida ao descomentar (indentação errada, label fora da lista) — a adesão falha na hora de ligar | CT-44 | `docker compose config` sai com código ≠ 0 |
---

## Regra R8 — Os quatro `VITE_REVERB_*` chegam ao `npm run build` de toda imagem

> `RQ-14` · perfil **padrão** · técnica: **contrato estático** (escopo de estágio) + **contrato entre serviços**

```gherkin
  Regra: o estágio assets declara os quatro ARG antes do build, e todo serviço que constrói recebe os mesmos valores

    Esquema do Cenário: [CT-17] o ARG está no estágio assets, antes do npm run build
      Dado o Dockerfile.laravel, recortado de "FROM node:22-alpine AS assets" até o próximo FROM
      Quando o teste procura a declaração de "<arg>"
      Então ela casa "^ARG <arg>(=.*)?$" dentro do recorte
      E aparece antes da linha "RUN npm run build" do recorte

      Exemplos:
        | arg                  |
        | VITE_REVERB_HOST     |
        | VITE_REVERB_PORT     |
        | VITE_REVERB_SCHEME   |
        | VITE_REVERB_APP_KEY  |

    Cenário: [CT-18] todo serviço com build recebe os quatro args com os valores do .env
      Dado o exemplo copiado na raiz e um .env com VITE_REVERB_HOST "dev.exemplo.test", VITE_REVERB_PORT "443", VITE_REVERB_SCHEME "https", VITE_REVERB_APP_KEY "chave-dev" e TRAEFIK_HOST "dev.exemplo.test"
      Quando o operador roda "docker compose --profile app config"
      Então todo serviço que tem "build" na configuração do base sozinho tem "build.args" com os quatro
      E os valores são "dev.exemplo.test", "443", "https" e "chave-dev" em todos eles
```

Por que **todos** os serviços com build e não só o nginx: `app` e `web` compartilham o estágio
`assets`; args diferentes por serviço produzem bundles com hash diferente, e o manifest do `app`
passa a apontar arquivos que o nginx não serve. A lista sai do base, não do plano.

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

> `RQ-02`, `RQ-04`, `P-01`, `P-12` · perfil **padrão** · técnica: **EP** (forma da declaração; origem do valor)
> Mecanismo fixado por **P-12** (medido pela sessão com `docker build --progress=plain`: `ARG X` sem default fica **ausente** no `RUN`; `ARG X=` fica `''`).

```gherkin
  Regra: sem --build-arg, nenhuma VITE_REVERB_* existe no ambiente do npm run build

    Cenário: [CT-19] o estágio assets não define VITE_REVERB_* quando ninguém as passa
      Dado o recorte do estágio assets do Dockerfile.laravel, sem as linhas de comentário
      Quando o teste lê as linhas ARG, ENV, COPY, ADD e RUN do recorte
      Então nenhuma linha ENV atribui VITE_REVERB_*
      E nenhuma linha ARG de VITE_REVERB_* tem "=" (sem default, nem vazio)
      E nenhum COPY ou ADD tem origem que case "\.env"
      E nenhum RUN atribui "VITE_REVERB_\w+=" antes do comando

    Cenário: [CT-38] o base sozinho não passa VITE_REVERB_* ao build
      Dado uma pasta só com o docker-compose.yml do kit e um .env com VITE_REVERB_HOST "dev.exemplo.test"
      Quando o operador roda "docker compose --profile app config"
      Então nenhum serviço tem em "build.args" chave que comece com "VITE_REVERB_"
```

O porquê é medido, não suposto: o Vite copia para `import.meta.env` toda `VITE_*` presente em
`process.env`, **inclusive vazia** (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5705`). Hoje o
estágio não tem `.env` e as chaves são `undefined`; com `ENV VITE_REVERB_PORT=` elas viram `''`, e o
`import.meta.env.VITE_REVERB_PORT ?? 80` do projeto que adicionar Echo deixa de cair no default.
`[CT-38]` fecha a outra porta: o `.env` do projeto tem `VITE_REVERB_HOST` (o `.env.example` a traz),
e args no **base** a levariam ao build de quem nunca ligou o override.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | `ENV VITE_REVERB_HOST=$VITE_REVERB_HOST` promovendo ARG vazio | CT-19 | linha `ENV` com `VITE_REVERB_` |
| M40 | `ARG VITE_REVERB_PORT=` (default vazio, entra no ambiente do RUN como `''` — P-12) | CT-19 | `ARG` com `=` |
| M41 | `ARG VITE_REVERB_HOST=localhost` "para dev funcionar" | CT-19 | `ARG` com `=` |
| M101 | `build.args: VITE_REVERB_HOST: ${VITE_REVERB_HOST}` no bloco de build do **base** em vez do override (ADV-03) | CT-38 | `services.app.build.args.VITE_REVERB_HOST` = `dev.exemplo.test` sem override |
| M102 | `COPY .env* ./` no estágio `assets` (a outra saída que o levantamento cita) (ADV-16) | CT-19 | `COPY` com origem `.env*` no recorte |
| M103 | `RUN VITE_REVERB_PORT=${VITE_REVERB_PORT:-} npm run build` (ADV-16) | CT-19 | `RUN` com `VITE_REVERB_PORT=` antes do comando |

Estouro do teto (padrão: 5): M101…M103 — revisão adversarial (ADV-03, ADV-16).

---

## Regra R10 — Interpretação de `TRUSTED_PROXIES`

> `P-02`, `P-11` · perfil **completo** (escalado: fronteira de confiança) · técnica: **EP exaustiva**
> Tokens que o Laravel/Symfony expandem (`*`, `**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`): **decisão da
> sessão (ADV-20), falha fechado** — só `*` sozinho vale "todos"; dentro de lista os quatro são
> descartados; `**` e `PRIVATE_SUBNETS` sozinhos viram `null`.

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
        | '10.0.0.1,*'                       | ['10.0.0.1']                       | `*` dentro de lista (P-11)         |
        | '*,*'                              | null                               | só `*` repetido, em lista          |
        | '**'                               | null                               | `**` sozinho                       |
        | '10.9.9.9,REMOTE_ADDR'             | ['10.9.9.9']                       | `REMOTE_ADDR` dentro de lista      |
        | 'REMOTE_ADDR'                      | null                               | `REMOTE_ADDR` sozinho (falha fechado) |
        | 'PRIVATE_SUBNETS'                  | null                               | `PRIVATE_SUBNETS` sozinho          |
        | '10.9.9.9,PRIVATE_SUBNETS'         | ['10.9.9.9']                       | `PRIVATE_SUBNETS` dentro de lista  |
```

A comparação é `toBe` (estrita): lista com chaves `0, 1` — `[0 => 'a', 2 => 'b']` reprova. Por que
cada token mágico é perigoso: o Laravel trata `'*'` **e** `'**'` como "confiar no chamador"; o
Symfony troca `REMOTE_ADDR` da lista pelo IP do chamador e `PRIVATE_SUBNETS` por todas as faixas
privadas — no Docker, **todo** container da rede. Passar qualquer um adiante dentro de uma lista
transforma um erro de digitação em confiança total.

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

Estouro do teto (completo: 6): M104…M108 — revisão adversarial (ADV-20, ADV-33) e complemento da sessão (`PRIVATE_SUBNETS`).

---

## Regra R11 — Ausente, vazia ou lista sem o chamador: `X-Forwarded-*` ignorados

> `P-02`, `P-11`, `RQ-05` · perfil **padrão** · técnica: **tabela de decisão** (chamador × valor efetivo → `isSecure`, host, URL, IP) · invariante: vale com qualquer resposta a Q1

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
        | 172.18.0.5  | ausente                     | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | chamador na rede do Docker, sem chave     |
        | 10.0.0.7    | ausente                     | falso    | interno.local | http://interno.local/x  | 10.0.0.7    | chamador em 10/8, sem chave               |
        | 172.18.0.5  | ''                          | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | rede do Docker, vazia                     |
        | 10.0.0.7    | '10.9.9.9'                  | falso    | interno.local | http://interno.local/x  | 10.0.0.7    | faixa privada, lista sem o chamador       |
        | 172.18.0.5  | '10.9.9.9,PRIVATE_SUBNETS'  | falso    | interno.local | http://interno.local/x  | 172.18.0.5  | `PRIVATE_SUBNETS` dentro de lista         |
```

Todas as linhas têm a mesma saída **de propósito**: a tabela é a fronteira — nenhuma combinação de
chamador e valor fora de R12 abre a confiança. O `APP_URL` em `https` no `Dado` é o que torna a coluna
`url` discriminante: a URL do request continua a do request (`http://interno.local`), não a da
configuração.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | `trustProxies(at: '*')` incondicional no `bootstrap/app.php` | CT-21 (linha ausente) | `isSecure()` verdadeiro |
| M49 | qualquer valor não vazio vira `'*'` | CT-21 (linha lista sem o chamador) | host visto `dev.exemplo.test` |
| M50 | vazio tratado como "todos" (`?: '*'`) | CT-21 (linha vazia) | `isSecure()` verdadeiro |
| M109 | default de faixas privadas quando a chave falta (`?? ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']`, "porque o Traefik está na rede do Docker") (ADV-04) | CT-21 (linhas `172.18.0.5`/`10.0.0.7` com chave ausente ou vazia) | `isSecure()` verdadeiro, host `dev.exemplo.test`, ip `203.0.113.9` |
| M110 | `bootstrap/app.php` passa o `env('TRUSTED_PROXIES')` cru ao `trustProxies(at:)`, sem a interpretação de R10 (ADV-05) | CT-22 (linha `' * '`), CT-21 (linha `true`) | `' * '` cru vira `['*']` no Laravel: `isSecure()` falso, o CT-22 exige verdadeiro; `true` cru não pode virar 500 nem confiança |
| M111 | `URL::forceRootUrl(config('app.url'))` / `forceScheme('https')` quando `APP_URL` é `https` — contorna a fronteira de confiança (ADV-18) | CT-21 (toda linha) | `url('/x')` = `https://dev.exemplo.test/x`; o caso exige `http://interno.local/x` |

Estouro do teto (padrão: 5): M109…M111 — revisão adversarial (ADV-04, ADV-05, ADV-18).

---

## Regra R12 — `*` ou lista com o chamador: a aplicação honra `X-Forwarded-*`

> `P-02`, `RQ-09` · perfil **padrão** · técnica: **tabela de decisão** · direção de Q1, implementada como P-02

```gherkin
  Regra: com proxy confiável, o esquema, o host e o IP são os que o Traefik informa

    Esquema do Cenário: [CT-22] cabeçalhos de proxy confiável chegam à aplicação
      Dado TRUSTED_PROXIES efetivo <valor efetivo> e o chamador em 127.0.0.1
      E o request com Host "interno.local", X-Forwarded-For "203.0.113.9", X-Forwarded-Proto "https", X-Forwarded-Host "dev.exemplo.test" e X-Forwarded-Port "443"
      Quando o Traefik faz o request à rota de teste
      Então a aplicação vê isSecure() verdadeiro e host "dev.exemplo.test"
      E url('/x') é "https://dev.exemplo.test/x" e ip() é "203.0.113.9"

      Exemplos:
        | valor efetivo             | # partição               |
        | '*'                       | todos                    |
        | ' * '                     | todos, com espaço        |
        | '10.9.9.9,127.0.0.1'      | lista com o chamador     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M51 | chamada a `trustProxies()` ausente do `bootstrap/app.php` | CT-22 (linha todos) | `isSecure()` falso |
| M52 | chave lida com nome errado (`TRUSTED_PROXY`) | CT-22 (linha todos) | `isSecure()` falso |
| M53 | `headers:` restrito a `HEADER_X_FORWARDED_FOR` | CT-22 | `isSecure()` falso e host `interno.local` |
| M54 | só o primeiro item da lista é usado | CT-22 (linha lista com o chamador) | `isSecure()` falso |
| M112 | `headers:` sem `HEADER_X_FORWARDED_FOR` (só Proto/Host/Port) — o IP do log e do rate limit vira o do Traefik (ADV-15) | CT-22 | `ip()` = `127.0.0.1`; o caso exige `203.0.113.9` |

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

    Cenário: [CT-39] o .env.docker copiado como sempre, sem o override, não liga o Traefik
      Dado uma pasta com os arquivos de Compose da raiz do kit, o exemplo em "docker/traefik/" e o .env.docker copiado verbatim como .env
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
distintas por ambiente).

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

Estouro do teto (padrão: 5): M113…M116 — revisão adversarial (ADV-07, ADV-09, ADV-23).

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

> `RQ-06`, `RQ-07`, `RQ-08`, `RQ-09`, `RQ-11`, `P-13` · perfil **padrão** · técnica: **EP por âncora**. `skip` fora da árvore.

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
        | cópia do exemplo             | um bloco de código casa "cp\s+docker/traefik/docker-compose\.override\.yml\s+(\./)?docker-compose\.override\.yml"; o caminho de origem existe no kit | RQ-11, P-03 |
        | proxies confiáveis           | "TRUSTED_PROXIES="                                                                         | P-02   |

    Esquema do Cenário: [CT-41] a página avisa que, com a configuração em cache, TRUSTED_PROXIES vem do ambiente do processo
      Dado a página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste procura o aviso sobre configuração em cache
      Então uma mesma seção contém "<cache>" e "<ambiente>" e "TRUSTED_PROXIES"

      Exemplos:
        | idioma | cache                                     | ambiente              |
        | pt     | config:cache  ·  configuração em cache     | ambiente do processo  |
        | en     | config:cache  ·  cached configuration      | process environment   |
```

`[CT-41]`: na coluna `cache`, basta um dos dois termos (separados por `·`). É a documentação de
P-13; o comportamento em runtime fora do Docker é a lacuna L9.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M67 | a página cita `COMPOSE_PROJECT_NAME` sem exemplo de três ambientes | CT-29 (linha nome de projeto) | menos de 3 valores distintos |
| M68 | a página omite `traefik.docker.network` (o exemplo do DevOps não tinha) | CT-29 | âncora ausente |
| M69 | a página aponta um caminho de exemplo que não existe (renomeado no meio) | CT-29 (linha cópia do exemplo) | `file_exists(base_path(caminho))` falso |
| M70 | a página não diz para copiar para a raiz | CT-29 | regex do `cp` não casa |
| M118 | `cp` invertido no bloco (`cp docker-compose.override.yml docker/traefik/`) ou destino em `docker/` — os dois nomes aparecem, a cópia não liga nada (ADV-26) | CT-29 (linha cópia do exemplo) | a regex `cp\s+docker/traefik/…\s+(\./)?docker-compose\.override\.yml` não casa |
| M119 | página sem o aviso de `config:cache` — quem roda fora do Docker com a configuração em cache perde a chave em silêncio (ADV-19) | CT-41 | nenhuma seção com `config:cache`/"configuração em cache" e "ambiente do processo" |

Estouro do teto (padrão: 5): M118, M119 — revisão adversarial (ADV-19, ADV-26).

---

## Regra R17 — A página cobre portas, matriz, o build do Reverb e as duas rotas

> `RQ-10`, `RQ-12`, `RQ-14`, `RQ-16`, `P-06`, `P-09` · perfil **padrão** · técnica: **valor literal do requisito**. `skip` fora da árvore.

```gherkin
  Regra: a página traz a matriz do requisito com as quatro chaves obrigatórias, o build do Reverb e as duas rotas

    Esquema do Cenário: [CT-30] a matriz de portas, o build e as rotas do Reverb estão na página
      Dado a página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste procura "<item>"
      Então a encontra conforme "<oráculo>"

      Exemplos:
        | item                       | oráculo                                                                        | origem |
        | linha de app da matriz     | linha de tabela "^\|.*FORWARD_APP_PORT.*\|\s*8090\s*\|\s*9090\s*\|\s*8080\s*\|" | RQ-16  |
        | linha de banco             | "^\|.*FORWARD_DB_PORT.*\|\s*5433\s*\|\s*5434\s*\|\s*5435\s*\|"                  | RQ-16  |
        | linha de cache             | "^\|.*FORWARD_REDIS_PORT.*\|\s*6380\s*\|\s*6381\s*\|\s*6382\s*\|"               | RQ-16  |
        | linha do Reverb            | "^\|.*FORWARD_REVERB_PORT.*\|\s*8190\s*\|\s*8191\s*\|\s*8192\s*\|"              | RQ-16  |
        | nota do 8090               | uma linha de prosa (fora de bloco de código e de tabela) com "FORWARD_REVERB_PORT" e "8090" | RQ-16  |
        | bind de administração      | "FORWARD_APP_PORT=127.0.0.1:"                                                   | RQ-10, P-06 |
        | rota do Reverb pelo Traefik | "-reverb" num label de router                                                  | RQ-12  |
        | rota do Reverb por porta   | "FORWARD_REVERB_PORT=" fora da tabela                                           | RQ-12  |
        | VITE do Reverb no build    | um bloco de código com "VITE_REVERB_HOST=", "VITE_REVERB_PORT=443" e "VITE_REVERB_SCHEME=https" | RQ-14  |
        | rebuild ao mudar o VITE    | "--build" na mesma seção desse bloco                                            | RQ-14  |

    Esquema do Cenário: [CT-42] a matriz diz que as quatro portas são obrigatórias e distintas
      Dado a seção da matriz de portas da página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste lê o texto da seção e as células da tabela da matriz
      Então o texto da seção contém "<obrigatória>" e "<distinta>"
      E nenhuma célula da tabela da matriz contém "<opcional>"

      Exemplos:
        | idioma | obrigatória  | distinta   | opcional  |
        | pt     | obrigatóri   | distint    | opcional  |
        | en     | mandatory    | distinct   | optional  |
```

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

Estouro do teto (padrão: 5): M120…M123 — revisão adversarial (ADV-24, ADV-27, ADV-32).

---

## Regra R18 — A página apresenta as opções A–D e as armadilhas

> `RQ-13`, `RQ-15`, `P-07` · perfil **padrão** · técnica: **EP por âncora**. `skip` fora da árvore.

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
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M75 | Opção A apresentada sem a recomendação | CT-31 | "recomendad"/"recommended" fora da seção da A |
| M76 | Opção D omitida "porque o kit não entrega arquivo para ela" | CT-31 | título da D ausente |
| M77 | armadilhas sem `APP_DEBUG=false` ou sem `SESSION_COOKIE` | CT-31 | âncora ausente |
| M78 | sem o fluxo de atualização (só o primeiro `up`) | CT-31 | `git pull` ausente da seção da A |
| M124 | `.env` de exemplo da página com `SESSION_COOKIE=projeto3_dev` "por garantia" — contradiz RQ-15 (ADV-31) | CT-31 (linha sem SESSION_COOKIE customizado) | bloco de código com `SESSION_COOKIE=` |
| M125 | D com llama e mailpit citados **por ambiente** (ou só "pgsql e redis por ambiente", sem dizer o que se compartilha) (ADV-31) | CT-31 (linha equilíbrio da D) | nenhuma frase com `llama`, `mailpit` e `compartilhad`/`shared` |

Estouro do teto (padrão: 5): M124, M125 — revisão adversarial (ADV-31).

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
```

`[CT-43]` lê `resources/js` e `package.json`, que viajam com o kit — sem `skip`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M126 | `resources/js/echo.js` com `new Echo({ wsHost: import.meta.env.VITE_REVERB_HOST, … })` importado pelo `app.js`, "para o build-arg servir para algo" | CT-43 | `import.meta.env.VITE_REVERB_` / `laravel-echo` no texto ativo |
| M127 | `laravel-echo` e `pusher-js` acrescentados ao `package.json` "para o projeto que quiser" | CT-43 | chave `laravel-echo` em `devDependencies` |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota com `{id}` — o plano declara "Rotas: nenhuma"; a rota de G6 é declarada só no teste | — |
| Autorização exercida na ação | não se aplica: nenhuma policy ou permission nova | — |
| Idempotência | não se aplica: nenhuma escrita da aplicação; `docker compose config` é leitura | — |
| Concorrência | não se aplica: nenhum contador ou limite | — |
| **Fronteira no ponto de entrada** (valor do `.env`) | CT-20 (todas as partições), CT-21, CT-22 | G5, G6 |
| Domínio condicionado | CT-21/CT-22 (chamador × valor: a mesma lista confia ou não conforme o `REMOTE_ADDR`; faixa privada sem chave não confia) | G6 |
| **Token mágico do framework** (`*`, `**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`) | CT-20 (sozinho e em lista), CT-21 (em lista, com chamador que o token casaria) | G5, G6 |
| Cardinalidade 0 / 1 / N | proxies: CT-20 (0, 1, 2); serviços com build: CT-18 (todos), CT-38 (nenhum, sem override); ambientes: CT-01 (1, sem override), CT-26 (3) | G5, G2 |
| Ausente ≠ null ≠ vazio | CT-20 (ausente, `''`, `'   '`), CT-21 (ausente, `''`), CT-08 (`TRAEFIK_REDE` ausente e vazia), CT-11 (`COMPOSE_PROJECT_NAME` ausente e vazia), CT-12 (`TRAEFIK_HOST` ausente e vazia) | G5, G6, G2 |
| Texto livre: espaços nas bordas, só espaços | CT-20, CT-22 (`' * '`) | G5, G6 |
| Unicode / limite de varchar | não se aplica: hostnames e IPs; o Compose valida o nome de projeto | — |
| Timezone / DST | não se aplica: nada temporal | — |
| Unicidade + soft delete | não se aplica | — |
| Unicidade de nome global (Traefik e Docker) | CT-11 (todo objeto `routers/services/middlewares` com o prefixo do projeto), CT-15 (sufixo `-reverb`), CT-26 (`name` de topo, rede default e volumes disjuntos) | G2, G1 |
| CRUD combinado | não se aplica | — |
| Mass assignment | não se aplica: nenhum model | — |
| Upload | não se aplica | — |
| Precisão monetária | não se aplica | — |
| Superfície Livewire | não se aplica: `02` declara nenhum componente | — |
| Estado do framework usado sem validar | não se aplica: nenhum `$filters`/`$tableSearch` | — |
| Discriminante nulo (fecha ou abre?) | CT-21 linhas ausente: **fecha** (nenhum proxy), inclusive com chamador em faixa privada | G6 |
| Saída do estado de erro | CT-12 → saída: a mensagem nomeia `TRAEFIK_HOST`; par CT-10 (com a chave, a configuração passa) | G2 |
| Estado estático entre casos | CT-21/CT-22: `TrustProxies::flushState()` no `tearDown` do framework + restauração do env pelo helper | G6 |
| **Teste que viaja lendo arquivo que não viaja** (rule do projeto) | CT-04, CT-27…CT-32, CT-41, CT-42 com `skip` fora da árvore; CT-05 e CT-37 leem `.gitattributes` e `caminhosDoKit()`, que viajam; CT-43 lê `resources/js` e `package.json`, que viajam | G3, G4, G1 |
| **Ambiente do processo vaza para o subprocesso** | Setup Global G2 (env filtrado); sem isso CT-11 linha ausente e CT-33 medem o `.env` do desenvolvedor | G2 |
| Asserção de ausência sobre arquivo comentado | CT-02, CT-03, CT-13, CT-19, CT-23, CT-35, CT-43 rodam a ausência sem comentário; presença no texto cru | G1 |
| **Default intocado medido inteiro** (não por amostra de chaves) | CT-34 (golden), CT-06 (diff profundo) | G2 |
| Prova de ponta a ponta com Traefik real | lacuna declarada L1 | G2 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | exemplo em docker/traefik não é carregado | R1 | EP | G2 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M1 |
| CT-02 | arquivos-base sem Traefik nas linhas ativas | R1 | EP | G1 | Pest feature HTTP | idem | M2, M3, M4 |
| CT-03 | script chama o Compose sem fixar arquivo, projeto nem env-file | R1 | EP | G1 | Pest feature HTTP | idem | M5, M89, M90 |
| CT-04 | git ignora a cópia ativa e versiona o exemplo | R2 | EP | G3 | Pest feature HTTP | idem | M6, M7, M8, M91 |
| CT-05 | exemplo nas duas listas de entrega | R2 | EP | G1 | Pest feature HTTP | idem | M9, M10 |
| CT-06 | diff profundo da config restrito ao Traefik e ao build | R3 | rastreio de efeito | G2 | Pest feature HTTP | idem | M11, M12, M13, M15, M16, M93 |
| CT-07 | serviços do exemplo existem no base | R3 | contrato | G1 | Pest feature HTTP | idem | M12, M14 |
| CT-08 | rede externa do .env, default my-network | R4 | EP | G2 | Pest feature HTTP | idem | M17, M18, M19, M94 |
| CT-09 | só o nginx na rede externa | R4 | EP | G2 | Pest feature HTTP | idem | M20 |
| CT-10 | labels do router e do service | R5 | EP | G2 | Pest feature HTTP | idem | M21, M22, M23, M24 |
| CT-11 | objetos do Traefik com o nome do projeto e valores certos | R6 | EP | G2 | Pest feature HTTP | idem | M25, M26, M96, M97, M98 |
| CT-12 | sem TRAEFIK_HOST (ou vazia) o Compose recusa | R6 | estado de erro | G2 | Pest feature HTTP | idem | M27, M95 |
| CT-13 | exemplo não fixa hostname | R6 | EP | G1 | Pest feature HTTP | idem | M28 |
| CT-14 | Reverb segue na rota por porta | R7 | EP | G2 | Pest feature HTTP | idem | M29 |
| CT-15 | bloco comentado do Reverb completo | R7 | EP + regex | G1 | Pest feature HTTP | idem | M30, M31, M33, M99 |
| CT-16 | regra do Reverb recorta pela chave e não captura painel | R7 | invariante + regex | G1 | Pest feature HTTP | idem | M32, M99, M100 |
| CT-17 | ARG no estágio assets antes do build | R8 | contrato estático | G1 | Pest feature HTTP | idem | M34, M35, M36, M38 |
| CT-18 | todo serviço com build recebe os args | R8 | contrato | G2 | Pest feature HTTP | idem | M37, M38 |
| CT-19 | sem build-arg nada de VITE_REVERB_* | R9 | EP | G1 | Pest feature HTTP | idem | M39, M40, M41, M102, M103 |
| CT-20 | interpretação de TRUSTED_PROXIES | R10 | EP exaustiva | G5 | unit de regra | `tests/Kit/ProxiesConfiaveisTest.php` | M42…M47, M104…M108 |
| CT-21 | proxy não confiável é ignorado | R11 | tabela de decisão | G6 | Pest feature HTTP | `tests/Kit/ProxiesConfiaveisTest.php` | M48, M49, M50, M107, M108, M109, M110, M111 |
| CT-22 | proxy confiável é honrado | R12 | tabela de decisão | G6 | Pest feature HTTP | idem | M51, M52, M53, M54, M110, M112 |
| CT-23 | chave nova só como linha comentada | R13 | EP arquivo × chave | G1 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M55, M56, M58, M113, M114, M115 |
| CT-24 | .env.docker oferece o que o exemplo consome | R13 | contrato | G1 | Pest feature HTTP | idem | M57, M58 |
| CT-25 | bloco do .env.docker não colide porta | R13 | contrato | G2 | Pest feature HTTP | idem | M59 |
| CT-26 | três ambientes não colidem | R14 | valor literal | G2 | Pest feature HTTP | idem | M60, M61, M62, M117 |
| CT-27 | página alcançável nos dois idiomas | R15 | EP por idioma | G4 | Pest feature HTTP | idem | M63, M64, M65 |
| CT-28 | CHANGELOG registra | R15 | EP | G4 | Pest feature HTTP | idem | M66 |
| CT-29 | âncoras do procedimento | R16 | EP por âncora | G4 | Pest feature HTTP | idem | M67…M70, M118 |
| CT-30 | matriz, build e rotas do Reverb | R17 | valor literal | G4 | Pest feature HTTP | idem | M71…M74, M120, M121 |
| CT-31 | opções A–D e armadilhas | R18 | EP por âncora | G4 | Pest feature HTTP | idem | M75…M78, M124, M125 |
| CT-32 | espelho pt/en | R19 | contrato | G4 | Pest feature HTTP | idem | M79…M82 |
| CT-33 | sem override, nenhum serviço recebe TRUSTED_PROXIES | R1 | EP | G2 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M83 |
| CT-34 | config efetiva do base = golden versionado | R1 | golden | G2 | Pest feature HTTP | idem | M3, M84, M85 |
| CT-35 | nginx.conf sem diretiva de proxy | R1 | EP | G1 | Pest feature HTTP | idem | M86 |
| CT-36 | um arquivo de Compose na raiz, `env_file: .env` literal | R1 | EP | G1 | Pest feature HTTP | idem | M1, M87, M88 |
| CT-37 | kit:update não toca a cópia ativa | R2 | EP | G1 | Pest feature HTTP | idem | M92 |
| CT-38 | base sozinho sem build.args VITE_REVERB_* | R9 | EP | G2 | Pest feature HTTP | idem | M101 |
| CT-39 | .env.docker verbatim não liga o Traefik | R13 | EP | G2 | Pest feature HTTP | idem | M113 |
| CT-40 | FORWARD_* em loopback publicam só em 127.0.0.1 | R13 | contrato | G2 | Pest feature HTTP | idem | M116 |
| CT-41 | aviso de config:cache na página | R16 | EP por âncora | G4 | Pest feature HTTP | idem | M119 |
| CT-42 | matriz com as quatro portas obrigatórias e distintas | R17 | EP por âncora | G4 | Pest feature HTTP | idem | M122, M123 |
| CT-43 | bundle do kit sem Echo | R20 | EP por arquivo | G1 | Pest feature HTTP | idem | M126, M127 |
| CT-44 | bloco do Reverb descomentado entre os marcadores produz configuração válida | R7 | EP (rota ativa) + execução real | G2 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M128 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "a chamada a `trustProxies()` vem antes do `append(RaizDeUrlSemPublic)`" (leitura do `bootstrap/app.php`) | não mata mutante observável: a ordem do stack global já é afirmada por CT-13 de `UrlSemPrefixoPublicTest` |
| snapshot `git show <tag>:docker-compose.yml` = arquivo atual | substituído pelo golden de **configuração efetiva** (CT-34): o snapshot de texto expira em toda mudança de comentário; o golden só expira quando o efeito muda, e regenerá-lo é ato deliberado |
| `docker build --target assets` com probe do ambiente do `RUN`, na suíte | exige daemon e uma linha de probe no Dockerfile; o mecanismo foi medido uma vez pela sessão (P-12) e a suíte cobre por leitura (CT-19) — lacuna L2 |
| `config:cache` fora do Docker com `TRUSTED_PROXIES` só no `.env` (runtime) | exige processo PHP separado com configuração em cache; decisão da sessão: documentar (P-13, CT-41) e declarar L9 |
| `TRUSTED_PROXIES=REMOTE_ADDR` sozinho | o `00` e a decisão da sessão (ADV-20) fixam `REMOTE_ADDR` só **dentro de lista**; sozinho fica sem cenário até a sessão decidir (ver retorno) |
| CT-B de qualquer natureza | ver `## Sem CT-B` |

## Costuras — notas para quem confirmar

- G6 precisa de **medição** antes de confirmar: (a) se o `HttpKernel` é resolvido de novo depois de `forgetInstance` e o `afterResolving` de `withMiddleware` roda outra vez no `get()`; (b) se não, `refreshApplication()` com a rota sem banco. Se nenhuma das duas medir, abre-se lacuna e R11/R12 caem para leitura do `bootstrap/app.php` + CT-20 — **piora declarada**, porque a leitura não prova o efeito em `isSecure()`.
- G2 roda no CI (`ubuntu-latest` tem o plugin) e no Windows local (Compose v5.5.1). A interpolação de label em lista foi medida só na v5.5.1; se o CI divergir, CT-11 é o primeiro a acusar. O golden de CT-34 é gerado **numa** versão do Compose: se a do CI serializar o JSON de outro jeito (ex.: `ports[].published` como número × string), o caso normaliza o tipo antes de comparar, e o motivo fica no docblock.

## Fechamento com mutation testing

- Código PHP novo: `app/Support/ProxiesConfiaveis.php` e a linha do `bootstrap/app.php`. Medir com `--path=app/Support/ProxiesConfiaveis.php`, sem `--filter`, com `--no-tia`, pelo `pestw.cmd` (Windows), aceitando só score com `Duration` plausível e lista de sobreviventes (`.ai/rules/testes.md`). `UNTESTED` é sobrevivente.
- Para YAML, Dockerfile, `nginx.conf`, `.env.*`, JavaScript e Markdown o `pest --mutate` não gera mutante — a cobertura de R1–R9 e R13–R20 é a tabela de mutantes de especificação acima.

## Lacunas Declaradas

| # | Lacuna | O que foi tentado / por quê | Regra |
|---|---|---|---|
| L1 | Traefik real roteando `Host` → `nginx:80` com TLS | exige daemon, container do Traefik, DNS e certificado; `docker compose config` prova o contrato, não o roteamento. Fica para a validação manual do quality gate | R4–R7 |
| L2 | prova **recorrente** em runtime de que, sem build-arg, nenhuma `VITE_REVERB_*` existe no ambiente do `npm run build` | medido uma vez pela sessão com `docker build --progress=plain` (P-12); na suíte, `docker run <imagem> env` não vê ARG e provar o `RUN` exige probe no Dockerfile. CT-19 e CT-38 cobrem por leitura e configuração | R9 |
| L4 | P-08 em runtime: health check do script com `FORWARD_APP_PORT=127.0.0.1:…` respondendo | exige daemon e stack de pé; CT-03 prova estaticamente que a porta vem de `docker compose --profile app port nginx 80` e não de `${FORWARD_APP_PORT` | R1 |
| L7 | `TRUSTED_PROXIES=*` ao lado de porta do nginx publicada em `0.0.0.0` | Q11 registrada no `00` e implementada pela direção que falha fechado (a página recomenda `*` só com a porta em `127.0.0.1`); nenhum achado da revisão pediu âncora de página para ela | R12 |
| L9 | `config:cache` fora do Docker: com a configuração em cache, `TRUSTED_PROXIES` só no `.env` não é lida | decisão da sessão (ADV-19): documentar como P-13 e afirmar o aviso na página (CT-41); o runtime exige processo PHP separado com cache e fica sem CT | R11, R16 |

L5 (recorte do Reverb, agora P-10 com CT-15/CT-16), L6 ("opcional" da matriz, agora P-09 com CT-42) e
L8 (revisão adversarial, feita) foram **retiradas** nesta revisão; os números não são reaproveitados.

## Sem CT-B

- Motivo: o `01` declara **Superfície de UI: nenhuma** e o `02`, **Superfície Livewire: nenhuma**; a varredura confirma — nada desta entrega renderiza HTML (artefatos de infra, um `app/Support`, uma linha de bootstrap e documentação). O único request (G6) afirma esquema, host, URL e IP vistos pelo PHP, que `Pest feature HTTP` prova sem navegador. Nenhuma linha de `## Costuras de Teste` tem costura `browser`; o `05` não existe.

## Perguntas para o 00-requisito.md

Todas as perguntas desta derivação já foram levadas pela sessão; nenhuma `Q?n` provisória resta.

| Pergunta | Raia | Destino |
|---|---|---|
| Q6 — "opcional" × obrigatórias e distintas | requisito | `00`, `## Perguntas ao Solicitante`; implementada como P-09 (R13, R14, CT-42) |
| Q7 — `ARG` sem default e sem `ENV` | desenho | medida pela sessão; virou P-12 (R9) |
| Q8 — recorte do Reverb sem capturar `/app` | requisito | `00`; implementada como P-10 (R7, CT-16) |
| Q9 — `*` dentro de lista | requisito | `00`; implementada como P-11 (R10, R11), estendida pela decisão de ADV-20 a `**`, `REMOTE_ADDR` e `PRIVATE_SUBNETS` |
| Q10 — delimitadores no bloco do Reverb | desenho | decidida: D6 do `01` (`# >>> reverb-traefik` / `# <<< reverb-traefik`); CT-44 fecha a L3 |
| Q11 — `TRUSTED_PROXIES=*` com porta aberta | requisito | `00`; a página segue a recomendação (L7) |

## Revisão Adversarial

1 rodada, `fw-adversario-ct` (entrada: só `00` + este `04` + `wikis/glossario.md`), **36 achados,
todos fechados**. Destinos: **CT novo** · **oráculo reescrito** (CT existente com asserção nova ou
reescrita) · **mutante** (linha nova na tabela da regra) · **texto** (rastreabilidade, sem efeito
em cenário) · **lacuna** (declarada com motivo).

| ADV | Sev. | Achado (implementação errada que passava, ou falha do conjunto) | Destino |
|---|---|---|---|
| ADV-01 | blocker | base ganha `environment: TRUSTED_PROXIES` | CT novo CT-33 (R1) · M83 |
| ADV-02 | blocker | defaults do base alterados fora das chaves que CT-01/CT-02 olham | CT novo CT-34 (golden `tests/Kit/fixtures/compose-base.json`) · M84, M85 |
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
