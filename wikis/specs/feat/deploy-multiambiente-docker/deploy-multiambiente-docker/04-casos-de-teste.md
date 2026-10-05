# Casos de Teste — Deploy com Docker: vários ambientes não produtivos no mesmo servidor, atrás do Traefik

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths e artefatos) · Decisões: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`: não exigida)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação, que
> ainda não existe. O código lido foi o **atual**, e só para herdar convenção de teste (helpers de
> `tests/Pest.php`, `MysqlNoDockerTest`, `DeployDockerLocalTest`, `BooleanoDoEnvTest`,
> `UrlSemPrefixoPublicTest`) e os nomes que já existem (`docker-compose.yml`, `Dockerfile.laravel`,
> `.env.docker`, `.env.example`, `deploy_docker_local.sh`, `bootstrap/app.php`).
> Derivação feita em sub-agente: perguntas com numeração provisória `Q?n`; `## Costuras de Teste`
> com `Confirmada` vazia; **revisão adversarial pendente** (a sessão despacha o `fw-adversario-ct`).

## Perfil de Derivação

| Área | O que é | P | I | P×I | Perfil |
|---|---|---|---|---|---|
| **A — opt-in e default intocado** | semântica de carga e merge do Compose (override automático, `ports` concatenados), três vias de entrega (git, `create-project`, `kit:update`) | 3 | 3 | **9** | completo |
| **B — contrato Traefik do exemplo** | interpolação de label em lista, rede externa, nomes globais no Traefik, duas rotas do Reverb | 3 | 2 | **6** | padrão |
| **C — `VITE_REVERB_*` no build** | `ARG` por estágio do Dockerfile, args em seis serviços que compartilham o estágio `assets` | 2 | 2 | **4** | padrão |
| **D — proxies confiáveis** | parse de chave do `.env` e fronteira de confiança de `X-Forwarded-*` | 2 | 3 | **6** | padrão (R10 escalada a completo) |
| **E — chaves de `.env` e portas entre ambientes** | arquivos de sugestão copiados pelo usuário; colisão de porta no host | 2 | 2 | **4** | padrão |
| **F — documentação, README e site** | prosa pt/en, sidebar e stubs gerados | 2 | 2 | **4** | padrão |

**Impacto 3 em A**: errar aqui muda o deploy de **todo** projeto que já usa o kit, em silêncio
(RQ-04: "como roda outros projetos"). **Impacto 3 em D**: `TRUSTED_PROXIES` decide de quem a
aplicação aceita `X-Forwarded-For/Proto/Host` — confiar por engano é IP forjado no log de
autenticação e no rate limit. **Escalada**: R10 usa EP exaustiva com 6 mutantes (teto do completo)
porque a regra é a fronteira de confiança e cada partição tem modo de falha próprio.

**Revisão adversarial: obrigatória** (Impacto 3 em A e D) — **pendente**, a sessão despacha.

- Técnicas aplicadas: EP (partição exaustiva em R10; por arquivo × chave em R13), tabela de decisão (R11/R12: valor de `TRUSTED_PROXIES` × chamador), rastreio de efeito como diff de configuração (R3), contrato entre arquivos (R8, R13), BVA não se aplica (nenhuma faixa ordenável: portas são identidades, não faixas)
- Cenários: 32 · Regras: 19 · Mutantes previstos: 82 · Sem matador: 0
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
| S | `docker/traefik/docker-compose.override.yml` (exemplo), `.gitignore`, `Dockerfile.laravel` (estágio `assets`), `.env.example`, `.env.docker`, `bootstrap/app.php`, `app/Support/ProxiesConfiaveis.php`, docs pt/en, README pt/en, `CHANGELOG.md`, `site/sidebar.json`, stubs. Intocados: `docker-compose.yml`, `deploy_docker_local.sh`, `docker/nginx/nginx.conf` | CT-01…CT-07, CT-17, CT-27, CT-28 |
| F | (1) ligar o Traefik por cópia do exemplo; (2) não ligar nada sem a cópia; (3) parse de `TRUSTED_PROXIES`; (4) honrar `X-Forwarded-*` só de proxy confiável; (5) passar `VITE_REVERB_*` ao build; (6) ensinar o procedimento | CT-01, CT-06, CT-08…CT-22 |
| D | valores de `.env` (ausente, vazio, só espaços, lista, `*`, não-string); três `.env` (dev/teste/homol); nomes de projeto; hostnames; a matriz de portas do requisito, literal | CT-11, CT-20, CT-21, CT-22, CT-25, CT-26 |
| I | `docker compose` (carga automática do override, `--profile app`), `docker build` (args), request HTTP com cabeçalhos `X-Forwarded-*`, `git` (ignore/índice), `kit:update` (`CAMINHOS_DO_KIT`), `create-project` (`.gitattributes`), Traefik (docker provider por labels) | CT-01, CT-03, CT-04, CT-05, CT-21, CT-22 |
| P | Docker Compose v5.5.1 local × v2.x do `ubuntu-latest` (comportamento de label em lista medido só na v5.5.1); Windows sem CLI → `skip`; ambiente do processo PHP **vaza** chaves do `.env` do desenvolvedor para o subprocesso `docker compose` (Laravel escreve no `putenv`, a `Process` herda) — tratado no Setup Global; Vite trata `VITE_*` vazia de `process.env` como **definida** (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5730`) | CT-11, CT-19, Setup Global |
| O | três checkouts no mesmo servidor, Opção A; quem não usa Traefik (todo projeto que já existe) copiando `.env.docker` como sempre fez; operador que esquece `TRAEFIK_HOST`; Reverb pelo Traefik ou por porta | CT-12, CT-14, CT-23, CT-25, CT-26 |
| T | não se aplica a relógio; **estado estático entre casos**: `TrustProxies::$alwaysTrustProxies` é estático do processo — o `tearDown` do framework chama `TrustProxies::flushState()` (`vendor/laravel/framework/src/Illuminate/Foundation/Testing/Concerns/InteractsWithTestCaseLifecycle.php:tearDownTheTestEnvironment:218`), e o env alterado no caso é restaurado pelo helper | CT-21, CT-22 (independência) |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Sem a cópia ativa do override, a stack do kit não muda | A (completo) | RQ-04, RQ-05, RQ-09, RQ-11, P-08 | EP por arquivo-base + execução do Compose | CT-01, CT-02, CT-03 |
| R2 — O exemplo chega a quem instala; a cópia ativa nunca entra no git | A (completo) | P-03, RQ-11 | EP por via de entrega | CT-04, CT-05 |
| R3 — Ligado o override, só muda o que o Traefik e o build precisam | A (completo) | RQ-04, RQ-07, RQ-10, RQ-11, RQ-14, P-06 | rastreio de efeito (diff de configuração) | CT-06, CT-07 |
| R4 — O `nginx` entra na rede externa sem sair da própria, e a rede é coerente | B (padrão) | RQ-07, RQ-08, P-04 | EP (rede ausente × definida) | CT-08, CT-09 |
| R5 — Labels do docker provider com os valores do requisito | B (padrão) | RQ-07, RQ-09 | EP (valor exato por label) | CT-10 |
| R6 — Router/service únicos por ambiente; hostname nunca fixo | B (padrão) | RQ-08, RQ-17, P-04, P-05 | EP (nome do projeto) + estado de erro com saída | CT-11, CT-12, CT-13 |
| R7 — Reverb: duas rotas, nenhuma imposta; a do Traefik não captura painel | B (padrão) | RQ-12, RQ-04, P-04 | EP + invariante | CT-14, CT-15, CT-16 |
| R8 — Os quatro `VITE_REVERB_*` chegam ao `npm run build` de toda imagem | C (padrão) | RQ-14 | contrato estático + contrato entre serviços | CT-17, CT-18 |
| R9 — Sem build-arg, o build é o de hoje | C (padrão) | RQ-04, P-01 · `@premissa` Q7 | EP (declaração do `ARG`) | CT-19 |
| R10 — Interpretação de `TRUSTED_PROXIES` | D (escalada a completo) | P-02 | EP exaustiva | CT-20 |
| R11 — Ausente, vazia ou lista sem o chamador: `X-Forwarded-*` ignorados | D (padrão) | P-02, RQ-05 (invariante, vale com qualquer resposta a Q1) | tabela de decisão | CT-21 |
| R12 — `*` ou lista com o chamador: a aplicação honra `X-Forwarded-*` | D (padrão) | P-02, RQ-09 · `@premissa` (direção de Q1) | tabela de decisão | CT-22 |
| R13 — Chaves novas só como linha comentada; o `.env.docker` oferece o que o exemplo consome; a sugestão não colide | E (padrão) | RQ-05, RQ-06, RQ-16, P-02, P-04 | EP arquivo × chave + contrato | CT-23, CT-24, CT-25 |
| R14 — Três ambientes com a matriz do requisito não disputam porta nem nome | E (padrão) | RQ-06, RQ-16 | valor literal do requisito | CT-26 |
| R15 — A página existe nos dois idiomas e é alcançável; o CHANGELOG registra | F (padrão) | RQ-01 | EP por idioma | CT-27, CT-28 |
| R16 — A página ensina o mecanismo central, o encaixe no Traefik e o passo a passo | F (padrão) | RQ-06, RQ-07, RQ-08, RQ-09, RQ-11 | EP por âncora | CT-29 |
| R17 — A página cobre portas, matriz e as duas rotas do Reverb | F (padrão) | RQ-10, RQ-12, RQ-16, P-06 | valor literal do requisito | CT-30 |
| R18 — A página apresenta as opções A–D e as armadilhas | F (padrão) | RQ-13, RQ-15, P-07 | EP por âncora | CT-31 |
| R19 — pt e en são espelho | F (padrão) | RQ-01 | contrato entre arquivos | CT-32 |

- **RQ-02** (revisar antes de transplantar) — cláusula de **processo**; a evidência é `## Premissas` do `00` e `## Auditoria Pré-Implementação` do `03`, não comportamento do produto. Sem cenário; o efeito dela aparece como os cenários que **contrariam** o documento de origem (CT-16, CT-19, CT-25, CT-26).
- **RQ-03** (branch, PR, tag, release) — processo; conferido pelo quality gate e pelo checklist de release. Sem cenário.
- **RQ-09 / P-02 — Q1 aberta, não bloqueante no `00`**: R11 é o invariante (ausente = hoje) e vale com qualquer resposta; R12 é a direção, marcada `@premissa`, e sai se Q1 for respondida "não".
- **RQ-12 — Q2 aberta, não bloqueante**: R7 cobre as duas rotas sem escolher. O **recorte de caminho** da rota pelo Traefik fica `aberta (Q8), sem cenário até a resposta`; o invariante (não capturar o painel) é de RQ-04 e tem cenário já (CT-16).
- **RQ-10 / RQ-16 — redação "opcional" da matriz**: `aberta (Q6), sem cenário até a resposta`; os valores da matriz e a nota do 8090 têm cenário (CT-26, CT-30).

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Leitura estática de infra | R1 (CT-02, CT-03), R2 (CT-05), R3 (CT-07), R6 (CT-13), R7 (CT-15, CT-16), R8 (CT-17), R9, R13 (CT-23, CT-24) | Pest feature HTTP | existente — padrão de `tests/Kit/MysqlNoDockerTest.php` (`blocoDoServico()`, filtro de comentário) e `tests/Kit/DeployDockerLocalTest.php`; arquivo novo `tests/Kit/DeployMultiambienteDockerTest.php` | o `Então` afirma texto ativo de arquivo entregue; roda sem Docker (inclusive no Windows sem CLI) | sessão, 2026-10-05 |
| G2 — `docker compose config` em pasta temporária | R1 (CT-01), R3 (CT-06), R4, R5, R6 (CT-11, CT-12), R7 (CT-14), R8 (CT-18), R13 (CT-25), R14 | Pest feature HTTP | **nova** — nenhum teste do kit executa o CLI do Compose; interpolação de label, merge de `ports` e carga automática do override só o Compose resolve. Mesmo arquivo de G1. `skip` quando `docker compose version` falha | o observável é a configuração **efetiva** que o Compose montaria; regex sobre YAML não vê interpolação nem merge | sessão, 2026-10-05 — o CLI está no Windows local (v5.5.1) e no `ubuntu-latest` |
| G3 — Índice e ignore do git | R2 (CT-04) | Pest feature HTTP | existente — CT de bit de execução de `DeployDockerLocalTest.php` (`git ls-files` na árvore) | o `Então` é estado do repositório; só existe na árvore do kit → `skip` fora dela | sessão, 2026-10-05 |
| G4 — Documentação, README e site | R15–R19 | Pest feature HTTP | existente — `paginasDoSite()`, `secoesDoMarkdown()`, `naArvoreDoKit()` de `tests/Pest.php`; mesmo arquivo de G1 | texto entregue; `docs/` e `site/` são `export-ignore` → `skip` fora da árvore | sessão, 2026-10-05 |
| G5 — Parse de `TRUSTED_PROXIES` | R10 | unit de regra | existente — forma de `tests/Kit/BooleanoDoEnvTest.php` (dataset sobre função pura de `app/Support`); arquivo novo `tests/Kit/ProxiesConfiaveisTest.php` | o `Então` é valor calculado | sessão, 2026-10-05 |
| G6 — Request pela configuração real de middleware | R11, R12 | Pest feature HTTP | **nova** — nenhum teste re-roda o `withMiddleware` de `bootstrap/app.php` com env diferente. Proposta: fixar o env nas três formas (`putenv`, `$_ENV`, `$_SERVER`), `app()->forgetInstance(\Illuminate\Contracts\Http\Kernel::class)` para o `afterResolving` de `ApplicationBuilder::withMiddleware` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:withMiddleware:289`) rodar de novo no próximo `get()`; alternativa se a primeira não medir: `refreshApplication()` com rota de teste **sem banco**. A costura precisa ser **medida** antes de confirmada. Arquivo `tests/Kit/ProxiesConfiaveisTest.php` | o requisito afirma comportamento HTTP (esquema e host vistos pela aplicação), não a chamada a `trustProxies()` | sessão, 2026-10-05 — confirmada **com a medição delegada ao executor**: tenta (a) `forgetInstance`; se não medir, (b) `refreshApplication()`; se nenhuma, abre L3 e devolve o fato como texto |

Nenhuma costura `browser` → sem `05`.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome e assinatura `ProxiesConfiaveis::doEnv(mixed): array\|string\|null` | escolha de implementação | detalhe do cenário (CT-20) |
| retorno `null` para não-string (`true`, `1`) | o `00` não decide; aceito como **falha fechado**, coerente com P-02 ("ausente = nenhum proxy") | linha do Esquema de CT-20, marcada |
| `' * '` → `'*'` | aceito: o operador escreveu `*` (P-02); devolver `[' * ']`/`['*']` faz o Laravel confiar em ninguém sem aviso | linha do Esquema de CT-20 |
| `trustProxies(...)` **antes** do `append(RaizDeUrlSemPublic)` no `bootstrap/app.php` | sem efeito observável: a ordem das chamadas dentro do closure não muda a ordem do stack global, que já é coberta por CT-13 de `UrlSemPrefixoPublicTest` | recusado |
| `# TRUSTED_PROXIES=` "logo abaixo de `APP_URL`" no `.env.example` | posição cosmética | recusado; CT-23 afirma só linha comentada presente e nenhuma linha ativa |
| âncora `x-vite-args` e "os seis serviços" listados | mecanismo; a lista é derivada do **base** (todo serviço com `build:`), não do plano | CT-18 enumera do base |
| `${TRAEFIK_HOST:?…}` — texto da mensagem | o requisito não fixa texto | CT-12 afirma só código ≠ 0 e o nome da chave no erro |
| regra do Reverb `PathPrefix(/app) \|\| PathPrefix(/apps)` | **contraria RQ-04**: o painel do kit vive em `/app` (`app/Providers/Filament/AppPanelProvider.php:panel:79`) e a regra mais longa ganha prioridade no Traefik | invariante CT-16 + pergunta Q8 |
| `ARG VITE_REVERB_*=` com `ENV` promovendo os quatro | **contraria RQ-04**: com o `ENV` vazio o Vite passa a ver `''` onde hoje vê `undefined` (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5730`) | CT-19 `@premissa` + pergunta Q7 |
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
- Leitura do JSON: `services.<s>.labels` (mapa), `services.<s>.networks` (mapa), `services.<s>.ports` (lista com `host_ip`, `published`, `target`), `services.<s>.build.args` (mapa), `networks.<chave>.{name,external}`, `name` de topo.
- Helpers ficam **locais** a `DeployMultiambienteDockerTest.php` (um consumidor só — `.ai/rules/testes.md`).

### Fixtures — G6 (request)
- Rota **de teste**, declarada no caso, fora do grupo `web` (sem sessão nem banco): devolve `request()->isSecure()`, `request()->getHost()` e `url('/x')`.
- Request: `REMOTE_ADDR=127.0.0.1` declarado (`withServerVariables`), `Host: interno.local`, `X-Forwarded-Proto: https`, `X-Forwarded-Host: dev.exemplo.test`, `X-Forwarded-Port: 443`.
- Helper local `comTrustedProxies(?string $valor)`: `null` remove a chave das três formas; string grava nas três; restaura o anterior no `afterEach` (forma de `kitConfigCom()` em `tests/Pest.php`). O `Dado` de cada linha **afirma o valor efetivo lido** (`env('TRUSTED_PROXIES')`) antes do request — o caso não pode medir o `.env` do desenvolvedor.

### Fakes
- Nenhum: não há fila, e-mail, notificação nem HTTP externo.

### Estratégia de DB
- `RefreshDatabase` global de `tests/Kit` (herdado, irrelevante: nenhum caso toca o banco). Se G6 cair na alternativa `refreshApplication()`, a rota de teste **não** pode tocar banco (`RegistroAbertoTest.php` registra que o `:memory:` morre no refresh).

---

## Regra R1 — Sem a cópia ativa do override, a stack do kit não muda

> `RQ-04`, `RQ-05`, `RQ-09`, `RQ-11`, `P-08` · perfil **completo** · técnica: **EP por arquivo-base** + execução real do Compose

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

    Cenário: [CT-03] o script de deploy chama o Compose sem fixar arquivo
      Dado o "deploy_docker_local.sh", sem as linhas de comentário
      Quando o teste lê cada linha que invoca "docker compose"
      Então nenhuma passa "-f", "--file" nem define COMPOSE_FILE
      E ao menos uma linha invoca "docker compose" com "up -d --build"
```

`[CT-01]` copia para a pasta **todo arquivo de Compose da raiz do kit** (`docker-compose*.y*ml`,
`compose*.y*ml`) — não só o `docker-compose.yml` — para que um override commitado na raiz por engano
seja carregado e reprove o caso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o kit entrega o exemplo **na raiz** como `docker-compose.override.yml` ("para facilitar") | CT-01 | a cópia da raiz é carregada sem `-f`: aparece label `traefik.enable` no nginx; o caso exige zero labels `traefik.` |
| M2 | `networks:` + `labels:` do Traefik escritos direto no `docker-compose.yml` base | CT-02 (linha compose base) | `^\s*labels:` encontrado no texto ativo; o caso exige ausência |
| M3 | default da porta do nginx muda para loopback (`127.0.0.1:8000:80`) "por segurança" | CT-02 (coluna preservado) | a regex `- '${FORWARD_APP_PORT:-8000}:80'` não casa mais |
| M4 | `nginx.conf` ganha `listen 443 ssl` para "suportar HTTPS" | CT-02 (linha nginx.conf) | `listen 443` no texto ativo |
| M5 | script passa a usar `docker compose -f docker-compose.yml …` (o override nunca carrega — P-08) | CT-03 | linha ativa com `-f`; o caso exige nenhuma |

---

## Regra R2 — O exemplo chega a quem instala; a cópia ativa nunca entra no git

> `P-03`, `RQ-11` · perfil **completo** · técnica: **EP por via de entrega** (git, `create-project`, `kit:update`)

```gherkin
  Regra: o exemplo viaja pelas duas rotas de entrega e a cópia ativa fica fora do git

    Esquema do Cenário: [CT-04] o git ignora a cópia ativa e versiona o exemplo
      Dado a árvore do kit
      Quando o teste consulta o git sobre "<caminho>"
      Então "git check-ignore" responde "<ignorado>"
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
```

`[CT-04]` leva `->skip(fn (): bool => ! naArvoreDoKit(), …)`. `[CT-05]` usa `caminhosDoKit()` de
`tests/Pest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | `.gitignore` com `docker-compose.override.yml` **sem barra** — ignora também o exemplo em `docker/traefik/` | CT-04 (linha exemplo) | `git check-ignore` responde ignorado; o caso exige "não" |
| M7 | linha do `.gitignore` esquecida: a cópia ativa de um servidor vai para o commit | CT-04 (linha cópia ativa) | `git check-ignore` responde não ignorado; o caso exige "sim" |
| M8 | `.gitignore` ignora `docker/traefik/` inteiro (a pasta "é do servidor") | CT-04 (linha exemplo) | exemplo ignorado e fora do `ls-files` |
| M9 | exemplo entregue fora de `docker/` (ex.: raiz, `docker-compose.traefik.yml`) — não viaja no `kit:update` | CT-05 | nenhum caminho de `caminhosDoKit()` é prefixo do caminho do exemplo |
| M10 | `/docker/traefik export-ignore` no `.gitattributes` | CT-05 | linha `export-ignore` cobrindo o caminho encontrada |

---

## Regra R3 — Ligado o override, só muda o que o Traefik e o build precisam

> `RQ-04`, `RQ-07`, `RQ-10`, `RQ-11`, `RQ-14`, `P-06` · perfil **completo** · técnica: **rastreio de efeito** (diff entre a configuração sem e com o override)

```gherkin
  Regra: o override só acrescenta rede e labels ao nginx e args de build

    Cenário: [CT-06] a configuração com o override difere do base só nos pontos do Traefik e do build
      Dado um .env com COMPOSE_PROJECT_NAME "proj-dev" e TRAEFIK_HOST "dev.exemplo.test"
      E a configuração do base sozinho e a do base com o exemplo copiado na raiz, ambas com esse .env
      Quando o teste compara as duas, serviço a serviço
      Então o conjunto de serviços e o "name" de topo são os mesmos
      E "ports", "command", "environment", "volumes", "depends_on", "image" e "container_name" são iguais em todo serviço
      E as únicas diferenças são "networks" e "labels" do nginx, "build.args" e a rede de topo do Traefik

    Cenário: [CT-07] todo serviço do exemplo existe no base
      Dado o exemplo e o docker-compose.yml do kit
      Quando o teste lê as chaves de serviço dos dois arquivos (coluna 2 sob "services:")
      Então toda chave de serviço do exemplo é também chave de serviço do base
```

`[CT-07]` é estático (G1) e roda sem CLI — é o par barato de `[CT-06]` para o Windows sem Docker.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | override publica `80:80`/`443:443` no nginx "para o Traefik alcançar" | CT-06 | `ports` do nginx difere entre as duas configurações (o Compose concatena — P-06) |
| M12 | override acrescenta o serviço `traefik` (fora de escopo: a stack do Traefik é do DevOps) | CT-06, CT-07 | conjunto de serviços difere; `traefik` não é chave do base |
| M13 | override fixa `container_name` no nginx | CT-06 | `container_name` difere |
| M14 | erro de digitação `ngnix:` no exemplo (vira serviço novo e o nginx real fica sem labels) | CT-07 | `ngnix` não é chave do base |
| M15 | override troca `command`/`environment` do nginx (ex.: `APP_URL` forçado) | CT-06 | `command` ou `environment` difere |
| M16 | override declara `name:` de topo fixo | CT-06 | `name` de topo difere (`proj-dev` × fixo) |

---

## Regra R4 — O `nginx` entra na rede externa sem sair da própria, e a rede é coerente

> `RQ-07`, `RQ-08`, `P-04` · perfil **padrão** · técnica: **EP** (rede ausente × definida no `.env`)

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
  Regra: o nome do router e do service é o do projeto Compose, e o hostname vem do .env

    Esquema do Cenário: [CT-11] o router leva o nome do projeto de cada ambiente
      Dado o exemplo copiado na raiz e um .env com TRAEFIK_HOST "h.exemplo.test" e COMPOSE_PROJECT_NAME <valor>
      Quando o operador roda "docker compose --profile app config"
      Então o nginx tem o label "traefik.http.routers.<router>.rule"
      E o label "traefik.http.services.<router>.loadbalancer.server.port"
      E nenhuma chave de label contém "${"

      Exemplos:
        | valor       | router       | # partição           |
        | proj-dev    | proj-dev     | ambiente 1           |
        | proj-homol  | proj-homol   | ambiente 2           |
        | ausente     | starter-kit  | piso (P-05)          |

    Cenário: [CT-12] sem TRAEFIK_HOST o Compose recusa e diz qual chave falta
      Dado o exemplo copiado na raiz e um .env sem TRAEFIK_HOST
      Quando o operador roda "docker compose --profile app config"
      Então o comando sai com código diferente de zero
      E a saída de erro nomeia "TRAEFIK_HOST"

    Cenário: [CT-13] o exemplo não fixa hostname
      Dado o exemplo, sem as linhas de comentário
      Quando o teste lê todo argumento de "Host(" nas linhas ativas
      Então cada argumento é uma interpolação que começa por "${TRAEFIK_HOST"
      E o texto ativo não contém "fiocruz.br" nem "projtec"
```

Saída do estado de erro de `[CT-12]`: o par é `[CT-10]` — com `TRAEFIK_HOST` definido, a mesma
configuração é aceita e o router existe.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | router fixo `starter-kit` (dois ambientes colidem no Traefik) | CT-11 (linhas proj-dev, proj-homol) | label `traefik.http.routers.proj-dev.rule` ausente |
| M26 | labels em **mapa** (a chave não interpola — medido) | CT-11 | chave de label com `${COMPOSE_PROJECT_NAME` literal |
| M27 | `${TRAEFIK_HOST:-localhost}` no lugar de `:?` (sobe roteando host errado, em silêncio) | CT-12 | o comando sai 0; o caso exige ≠ 0 |
| M28 | hostname do documento de origem colado no exemplo (`desenv.projtec.fiocruz.br`) | CT-13 | argumento de `Host(` sem `${TRAEFIK_HOST` / `projtec` no texto ativo |

---

## Regra R7 — Reverb: duas rotas, nenhuma imposta; a do Traefik não captura painel

> `RQ-12`, `RQ-04`, `P-04` · perfil **padrão** · técnica: **EP** (rota ativa × comentada) + **invariante** (CT-16, origem RQ-04)
> Recorte de caminho da rota pelo Traefik: **aberto (Q8)**, sem cenário da direção.

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
      Quando o teste lê as linhas comentadas do bloco do reverb
      Então há o router "${COMPOSE_PROJECT_NAME:-starter-kit}-reverb" com regra que começa por "Host(`${TRAEFIK_HOST"
      E o service desse router aponta a porta 8090
      E o bloco põe o reverb na rede externa e declara "traefik.docker.network"

    Cenário: [CT-16] a regra do Reverb pelo Traefik não captura caminho de painel
      Dado a regra do router "-reverb" do exemplo, interpolada com REVERB_APP_KEY "starter-kit-key" e REVERB_APP_ID "starter-kit" do .env.docker
      Quando o teste confronta cada PathPrefix da regra com caminhos do kit
      Então nenhum PathPrefix é prefixo de "/app", "/app/login", "/app/acme/users", "/admin" ou "/infra"
      E algum PathPrefix é prefixo de "/app/starter-kit-key" e algum de "/apps/starter-kit/events"
```

`[CT-16]` tem **controle positivo** (a última linha): uma regra que não casa nada não passa.
O painel `/app` é do kit: `app/Providers/Filament/AppPanelProvider.php:panel:79` (`->path('app')`).
Será **vermelho contra o plano atual** (`PathPrefix(/app)`) — é o achado de Q8.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M29 | bloco do Reverb **ativo** no exemplo (impõe a rota pelo Traefik a todo servidor) | CT-14 | label `traefik.` no reverb |
| M30 | router do Reverb com o mesmo nome do nginx (sem `-reverb`) — colide no Traefik | CT-15 | regex do router `…-reverb.rule=` não casa |
| M31 | bloco do Reverb sem `traefik.docker.network` (container em duas redes, IP errado — RQ-08) | CT-15 | label ausente do bloco |
| M32 | `PathPrefix(`/app`)` na regra (rouba o painel `/app` pela prioridade de regra mais longa) | CT-16 | `/app` é prefixo de `/app/login` |
| M33 | bloco do Reverb sem a rede externa | CT-15 | rede externa ausente do bloco |

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

> `RQ-04`, `P-01` · perfil **padrão** · técnica: **EP** (forma da declaração) · `@premissa` mecanismo de Q7 (raia desenho)

```gherkin
  @premissa
  Regra: sem --build-arg, nenhuma VITE_REVERB_* existe no ambiente do npm run build

    Cenário: [CT-19] o estágio assets não define VITE_REVERB_* quando ninguém as passa
      Dado o recorte do estágio assets do Dockerfile.laravel, sem as linhas de comentário
      Quando o teste lê as linhas ARG e ENV que nomeiam VITE_REVERB_*
      Então nenhuma linha ENV atribui VITE_REVERB_*
      E nenhuma linha ARG de VITE_REVERB_* tem "=" (sem default, nem vazio)
```

O porquê é medido, não suposto: o Vite copia para `import.meta.env` toda `VITE_*` presente em
`process.env`, **inclusive vazia** (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5730`). Hoje o
estágio não tem `.env` e as chaves são `undefined`; com `ENV VITE_REVERB_PORT=` elas viram `''`, e o
`import.meta.env.VITE_REVERB_PORT ?? 80` do projeto que adicionar Echo deixa de cair no default.
O mecanismo "ARG sem default não entra no ambiente do RUN" é o que Q7 pede para medir.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | `ENV VITE_REVERB_HOST=$VITE_REVERB_HOST` promovendo ARG vazio | CT-19 | linha `ENV` com `VITE_REVERB_` |
| M40 | `ARG VITE_REVERB_PORT=` (default vazio, entra no ambiente do RUN como `''`) | CT-19 | `ARG` com `=` |
| M41 | `ARG VITE_REVERB_HOST=localhost` "para dev funcionar" | CT-19 | `ARG` com `=` |

---

## Regra R10 — Interpretação de `TRUSTED_PROXIES`

> `P-02` · perfil **completo** (escalado: fronteira de confiança) · técnica: **EP exaustiva**

```gherkin
  Regra: o valor do .env vira nenhum proxy, todos, ou uma lista limpa

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
```

A comparação é `toBe` (estrita): lista com chaves `0, 1` — `[0 => 'a', 2 => 'b']` reprova.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M42 | itens sem `trim` | CT-20 (linha espaço e item vazio) | `[' 10.0.0.1 ', …]` ≠ esperado |
| M43 | item vazio não descartado | CT-20 (linha espaço e item vazio) | `['10.0.0.1', '', '172.18.0.0/16']` |
| M44 | `array_filter` sem reindexar | CT-20 (linha espaço e item vazio) | chaves `0, 2` reprovam o `toBe` |
| M45 | lista vazia devolvida como `[]` | CT-20 (linhas só separador / separadores e espaços) | `[]` ≠ `null` |
| M46 | `*` comparado sem `trim` (vira `['*']`, que o Symfony não reconhece: confia em ninguém) | CT-20 (linha todos, com espaço) | `['*']` ≠ `'*'` |
| M47 | não-string coagido por `(string)` (`true` → `['1']`) | CT-20 (linhas não-string) | `['1']` ≠ `null` |

---

## Regra R11 — Ausente, vazia ou lista sem o chamador: `X-Forwarded-*` ignorados

> `P-02`, `RQ-05` · perfil **padrão** · técnica: **tabela de decisão** (valor efetivo × chamador) · invariante: vale com qualquer resposta a Q1

```gherkin
  Regra: sem proxy confiável, a aplicação vê o request como hoje

    Esquema do Cenário: [CT-21] cabeçalhos de proxy não confiável não mudam esquema nem host
      Dado TRUSTED_PROXIES efetivo <valor efetivo> e o chamador em 127.0.0.1
      E o request com Host "interno.local", X-Forwarded-Proto "https" e X-Forwarded-Host "dev.exemplo.test"
      Quando o Traefik faz o request à rota de teste
      Então a aplicação vê isSecure() falso
      E o host visto é "interno.local"

      Exemplos:
        | valor efetivo   | # partição                 |
        | ausente         | default de hoje            |
        | ''              | vazia                      |
        | '10.9.9.9'      | lista sem o chamador       |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | `trustProxies(at: '*')` incondicional no `bootstrap/app.php` | CT-21 (linha ausente) | `isSecure()` verdadeiro |
| M49 | qualquer valor não vazio vira `'*'` | CT-21 (linha lista sem o chamador) | host visto `dev.exemplo.test` |
| M50 | vazio tratado como "todos" (`?: '*'`) | CT-21 (linha vazia) | `isSecure()` verdadeiro |

---

## Regra R12 — `*` ou lista com o chamador: a aplicação honra `X-Forwarded-*`

> `P-02`, `RQ-09` · perfil **padrão** · técnica: **tabela de decisão** · `@premissa` direção de Q1 (sai se Q1 = não)

```gherkin
  @premissa
  Regra: com proxy confiável, o esquema e o host são os que o Traefik informa

    Esquema do Cenário: [CT-22] cabeçalhos de proxy confiável chegam à aplicação
      Dado TRUSTED_PROXIES efetivo <valor efetivo> e o chamador em 127.0.0.1
      E o request com Host "interno.local", X-Forwarded-Proto "https", X-Forwarded-Host "dev.exemplo.test" e X-Forwarded-Port "443"
      Quando o Traefik faz o request à rota de teste
      Então a aplicação vê isSecure() verdadeiro e host "dev.exemplo.test"
      E url('/x') é "https://dev.exemplo.test/x"

      Exemplos:
        | valor efetivo             | # partição               |
        | '*'                       | todos                    |
        | '10.9.9.9,127.0.0.1'      | lista com o chamador     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M51 | chamada a `trustProxies()` ausente do `bootstrap/app.php` | CT-22 (linha todos) | `isSecure()` falso |
| M52 | chave lida com nome errado (`TRUSTED_PROXY`) | CT-22 (linha todos) | `isSecure()` falso |
| M53 | `headers:` restrito a `HEADER_X_FORWARDED_FOR` | CT-22 | `isSecure()` falso e host `interno.local` |
| M54 | só o primeiro item da lista é usado | CT-22 (linha lista com o chamador) | `isSecure()` falso |

---

## Regra R13 — Chaves novas só como linha comentada; o `.env.docker` oferece o que o exemplo consome; a sugestão não colide

> `RQ-05`, `RQ-06`, `RQ-16`, `P-02`, `P-04` · perfil **padrão** · técnica: **EP arquivo × chave** + **contrato**
> Vocabulário: *linha ativa* e *linha comentada* (do `.env`) conforme `wikis/glossario.md`.

```gherkin
  Regra: copiar os arquivos de sugestão como sempre não liga nada

    Esquema do Cenário: [CT-23] a chave nova existe só como linha comentada
      Dado o arquivo "<arquivo>" do kit
      Quando o teste procura "<chave>"
      Então não há linha ativa de "<chave>"
      E há linha comentada "^#\s*<chave>="

      Exemplos:
        | arquivo       | chave                 |
        | .env.example  | TRUSTED_PROXIES       |
        | .env.docker   | TRUSTED_PROXIES       |
        | .env.docker   | TRAEFIK_HOST          |
        | .env.docker   | TRAEFIK_REDE          |
        | .env.docker   | COMPOSE_PROJECT_NAME  |
        | .env.docker   | FORWARD_APP_PORT      |

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
```

`"exceto os já resolvidos pelo próprio Compose"` em CT-24 é a lista vazia hoje — se algum nome
precisar dela, a exceção é escrita no caso com o motivo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M55 | `TRUSTED_PROXIES=*` como linha ativa no `.env.docker` (todo usuário de Docker passa a confiar em qualquer chamador) | CT-23 | linha ativa encontrada |
| M56 | `COMPOSE_PROJECT_NAME=projeto3-dev` ativo no `.env.docker` (renomeia a stack de quem copia) | CT-23 | linha ativa encontrada |
| M57 | `APP_URL=https://…` como linha ativa no fim do `.env.docker` (vence o `http://localhost:8000` — o leitor fica com a última) | CT-24 | última linha ativa de `APP_URL` ≠ `http://localhost:8000` |
| M58 | `TRAEFIK_REDE` consumida no exemplo e não oferecida | CT-24, CT-23 | nome sem linha no `.env.docker` |
| M59 | sugestão `FORWARD_APP_PORT=127.0.0.1:8090` com o Reverb no default 8090 (RQ-16, nota ²) | CT-25 | `127.0.0.1:8090` (nginx) e `:8090` (reverb, host_ip vazio) sobrepostos |

---

## Regra R14 — Três ambientes com a matriz do requisito não disputam porta nem nome

> `RQ-06`, `RQ-16` · perfil **padrão** · técnica: **valor literal do requisito**

```gherkin
  Regra: com COMPOSE_PROJECT_NAME e portas distintas, os três ambientes convivem no host

    Cenário: [CT-26] dev, teste e homol com a matriz sugerida não colidem
      Dado três .env com COMPOSE_PROJECT_NAME "projeto3-dev", "projeto3-teste" e "projeto3-homol", TRAEFIK_HOST distinto em cada um
      E FORWARD_APP_PORT 8090/9090/8080, FORWARD_DB_PORT 5433/5434/5435, FORWARD_REDIS_PORT 6380/6381/6382 e FORWARD_REVERB_PORT 8190/8191/8192
      Quando o operador roda "docker compose --profile app config" com o exemplo copiado na raiz, uma vez por ambiente
      Então nenhuma porta do host é publicada por dois serviços, no mesmo ambiente ou entre ambientes
      E os três "name" de topo são distintos
      E nenhum serviço declara "container_name"
```

Os números são os **literais** da matriz do requisito (RQ-16), não os da página.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M60 | porta fixa sem `FORWARD_*` num serviço do profile `app` do base (ex.: `'8091:8090'`) | CT-26 | a mesma porta publicada nos três ambientes |
| M61 | override fixa `name:` de topo | CT-26 (e CT-06) | três `name` iguais |
| M62 | dois serviços usando a mesma chave `FORWARD_*` (ex.: reverb lendo `FORWARD_APP_PORT`) | CT-26 | colisão dentro do mesmo ambiente |

---

## Regra R15 — A página existe nos dois idiomas e é alcançável; o CHANGELOG registra

> `RQ-01` · perfil **padrão** · técnica: **EP por idioma**. Todos os casos: `->skip(fn (): bool => ! naArvoreDoKit(), …)`.

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
a asserção sobre o topo expira na próxima entrega).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M63 | só a página pt escrita | CT-27 (linha en) | `paginasDoSite('en')` sem a página |
| M64 | `README.en.md` sem a subseção | CT-27 (linha en) | link ausente na seção `## Docker` |
| M65 | `node converter.mjs` não rodado (sidebar e stubs desatualizados) | CT-27 | slug ausente / stub inexistente |
| M66 | CHANGELOG sem a entrada | CT-28 | `docker/traefik/docker-compose.override.yml` ausente |

---

## Regra R16 — A página ensina o mecanismo central, o encaixe no Traefik e o passo a passo

> `RQ-06`, `RQ-07`, `RQ-08`, `RQ-09`, `RQ-11` · perfil **padrão** · técnica: **EP por âncora**. `skip` fora da árvore.

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
        | cópia do exemplo             | "docker/traefik/docker-compose.override.yml" e "docker-compose.override.yml" como destino; o caminho de origem existe no kit | RQ-11, P-03 |
        | proxies confiáveis           | "TRUSTED_PROXIES="                                                                         | P-02   |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M67 | a página cita `COMPOSE_PROJECT_NAME` sem exemplo de três ambientes | CT-29 (linha nome de projeto) | menos de 3 valores distintos |
| M68 | a página omite `traefik.docker.network` (o exemplo do DevOps não tinha) | CT-29 | âncora ausente |
| M69 | a página aponta um caminho de exemplo que não existe (renomeado no meio) | CT-29 (linha cópia do exemplo) | `file_exists(base_path(caminho))` falso |
| M70 | a página não diz para copiar para a raiz | CT-29 | destino `docker-compose.override.yml` ausente |

---

## Regra R17 — A página cobre portas, matriz e as duas rotas do Reverb

> `RQ-10`, `RQ-12`, `RQ-16`, `P-06` · perfil **padrão** · técnica: **valor literal do requisito**. `skip` fora da árvore.

```gherkin
  Regra: a página traz a matriz do requisito e as duas rotas do Reverb

    Esquema do Cenário: [CT-30] a matriz de portas e as rotas do Reverb estão na página
      Dado a página "operacao/deploy-docker-multiambiente.md" em <idioma>
      Quando o teste procura "<item>"
      Então a encontra conforme "<oráculo>"

      Exemplos:
        | item                       | oráculo                                                                        | origem |
        | linha de app da matriz     | linha de tabela "^\|.*FORWARD_APP_PORT.*\|\s*8090\s*\|\s*9090\s*\|\s*8080\s*\|" | RQ-16  |
        | linha de banco             | "^\|.*FORWARD_DB_PORT.*\|\s*5433\s*\|\s*5434\s*\|\s*5435\s*\|"                  | RQ-16  |
        | linha de cache             | "^\|.*FORWARD_REDIS_PORT.*\|\s*6380\s*\|\s*6381\s*\|\s*6382\s*\|"               | RQ-16  |
        | linha do Reverb            | "^\|.*FORWARD_REVERB_PORT.*\|\s*8190\s*\|\s*8191\s*\|\s*8192\s*\|"              | RQ-16  |
        | nota do 8090               | "FORWARD_REVERB_PORT" e "8090" na mesma seção, fora da tabela                   | RQ-16  |
        | bind de administração      | "FORWARD_APP_PORT=127.0.0.1:"                                                   | RQ-10, P-06 |
        | rota do Reverb pelo Traefik | "-reverb" num label de router                                                  | RQ-12  |
        | rota do Reverb por porta   | "FORWARD_REVERB_PORT=" fora da tabela                                           | RQ-12  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M71 | matriz transcrita com valor trocado (ex.: homol 8081) | CT-30 | regex da linha não casa |
| M72 | nota do 8090 omitida | CT-30 | sem `8090` fora da tabela na seção |
| M73 | "porta opcional" sem o como (sem bind em loopback) | CT-30 | `FORWARD_APP_PORT=127.0.0.1:` ausente |
| M74 | só a rota pelo Traefik documentada | CT-30 (linha rota por porta) | `FORWARD_REVERB_PORT=` ausente fora da tabela |

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
        | equilíbrio da D        | na seção da D: "llama" e "mailpit" e "pgsql"/"redis"                                               | RQ-13  |
        | armadilhas             | "SESSION_COOKIE", "APP_KEY", "APP_URL=https://" e "APP_DEBUG=false"                               | RQ-15  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M75 | Opção A apresentada sem a recomendação | CT-31 | "recomendad"/"recommended" fora da seção da A |
| M76 | Opção D omitida "porque o kit não entrega arquivo para ela" | CT-31 | título da D ausente |
| M77 | armadilhas sem `APP_DEBUG=false` ou sem `SESSION_COOKIE` | CT-31 | âncora ausente |
| M78 | sem o fluxo de atualização (só o primeiro `up`) | CT-31 | `git pull` ausente da seção da A |

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

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota com `{id}` — o plano declara "Rotas: nenhuma"; a rota de G6 é declarada só no teste | — |
| Autorização exercida na ação | não se aplica: nenhuma policy ou permission nova | — |
| Idempotência | não se aplica: nenhuma escrita da aplicação; `docker compose config` é leitura | — |
| Concorrência | não se aplica: nenhum contador ou limite | — |
| **Fronteira no ponto de entrada** (valor do `.env`) | CT-20 (todas as partições), CT-21, CT-22 | G5, G6 |
| Domínio condicionado | CT-21/CT-22 (valor × chamador: a mesma lista confia ou não conforme o `REMOTE_ADDR`) | G6 |
| Cardinalidade 0 / 1 / N | proxies: CT-20 (0, 1, 2); serviços com build: CT-18 (todos); ambientes: CT-01 (1, sem override), CT-26 (3) | G5, G2 |
| Ausente ≠ null ≠ vazio | CT-20 (ausente, `''`, `'   '`), CT-21 (ausente, `''`), CT-08 (`TRAEFIK_REDE` ausente), CT-11 (`COMPOSE_PROJECT_NAME` ausente), CT-12 (`TRAEFIK_HOST` ausente) | G5, G6, G2 |
| Texto livre: espaços nas bordas, só espaços | CT-20 | G5 |
| Unicode / limite de varchar | não se aplica: hostnames e IPs; o Compose valida o nome de projeto | — |
| Timezone / DST | não se aplica: nada temporal | — |
| Unicidade + soft delete | não se aplica | — |
| Unicidade de nome global (Traefik) | CT-11, CT-15 (sufixo `-reverb`), CT-26 | G2, G1 |
| CRUD combinado | não se aplica | — |
| Mass assignment | não se aplica: nenhum model | — |
| Upload | não se aplica | — |
| Precisão monetária | não se aplica | — |
| Superfície Livewire | não se aplica: `02` declara nenhum componente | — |
| Estado do framework usado sem validar | não se aplica: nenhum `$filters`/`$tableSearch` | — |
| Discriminante nulo (fecha ou abre?) | CT-21 linha ausente: **fecha** (nenhum proxy) | G6 |
| Saída do estado de erro | CT-12 → saída: a mensagem nomeia `TRAEFIK_HOST`; par CT-10 (com a chave, a configuração passa) | G2 |
| Estado estático entre casos | CT-21/CT-22: `TrustProxies::flushState()` no `tearDown` do framework + restauração do env pelo helper | G6 |
| **Teste que viaja lendo arquivo que não viaja** (rule do projeto) | CT-04, CT-27…CT-32 com `skip` fora da árvore; CT-05 lê `.gitattributes` e `caminhosDoKit()`, que viajam | G3, G4 |
| **Ambiente do processo vaza para o subprocesso** | Setup Global G2 (env filtrado); sem isso CT-11 linha ausente mede o `.env` do desenvolvedor | G2 |
| Asserção de ausência sobre arquivo comentado | CT-02, CT-03, CT-13, CT-19, CT-23 rodam a ausência sem comentário; presença no texto cru | G1 |
| Prova de ponta a ponta com Traefik real | lacuna declarada L1 | G2 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | exemplo em docker/traefik não é carregado | R1 | EP | G2 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M1 |
| CT-02 | arquivos-base sem Traefik nas linhas ativas | R1 | EP | G1 | Pest feature HTTP | idem | M2, M3, M4 |
| CT-03 | script chama o Compose sem fixar arquivo | R1 | EP | G1 | Pest feature HTTP | idem | M5 |
| CT-04 | git ignora a cópia ativa e versiona o exemplo | R2 | EP | G3 | Pest feature HTTP | idem | M6, M7, M8 |
| CT-05 | exemplo nas duas listas de entrega | R2 | EP | G1 | Pest feature HTTP | idem | M9, M10 |
| CT-06 | diff da config restrito ao Traefik e ao build | R3 | rastreio de efeito | G2 | Pest feature HTTP | idem | M11, M12, M13, M15, M16 |
| CT-07 | serviços do exemplo existem no base | R3 | contrato | G1 | Pest feature HTTP | idem | M12, M14 |
| CT-08 | rede externa do .env, default my-network | R4 | EP | G2 | Pest feature HTTP | idem | M17, M18, M19 |
| CT-09 | só o nginx na rede externa | R4 | EP | G2 | Pest feature HTTP | idem | M20 |
| CT-10 | labels do router e do service | R5 | EP | G2 | Pest feature HTTP | idem | M21, M22, M23, M24 |
| CT-11 | router com o nome do projeto | R6 | EP | G2 | Pest feature HTTP | idem | M25, M26 |
| CT-12 | sem TRAEFIK_HOST o Compose recusa | R6 | estado de erro | G2 | Pest feature HTTP | idem | M27 |
| CT-13 | exemplo não fixa hostname | R6 | EP | G1 | Pest feature HTTP | idem | M28 |
| CT-14 | Reverb segue na rota por porta | R7 | EP | G2 | Pest feature HTTP | idem | M29 |
| CT-15 | bloco comentado do Reverb completo | R7 | EP | G1 | Pest feature HTTP | idem | M30, M31, M33 |
| CT-16 | regra do Reverb não captura painel | R7 | invariante | G1 | Pest feature HTTP | idem | M32 |
| CT-17 | ARG no estágio assets antes do build | R8 | contrato estático | G1 | Pest feature HTTP | idem | M34, M35, M36, M38 |
| CT-18 | todo serviço com build recebe os args | R8 | contrato | G2 | Pest feature HTTP | idem | M37, M38 |
| CT-19 | sem build-arg nada de VITE_REVERB_* | R9 | EP | G1 | Pest feature HTTP | idem | M39, M40, M41 |
| CT-20 | interpretação de TRUSTED_PROXIES | R10 | EP exaustiva | G5 | unit de regra | `tests/Kit/ProxiesConfiaveisTest.php` | M42…M47 |
| CT-21 | proxy não confiável é ignorado | R11 | tabela de decisão | G6 | Pest feature HTTP | `tests/Kit/ProxiesConfiaveisTest.php` | M48, M49, M50 |
| CT-22 | proxy confiável é honrado | R12 | tabela de decisão | G6 | Pest feature HTTP | idem | M51, M52, M53, M54 |
| CT-23 | chave nova só como linha comentada | R13 | EP arquivo × chave | G1 | Pest feature HTTP | `tests/Kit/DeployMultiambienteDockerTest.php` | M55, M56, M58 |
| CT-24 | .env.docker oferece o que o exemplo consome | R13 | contrato | G1 | Pest feature HTTP | idem | M57, M58 |
| CT-25 | bloco do .env.docker não colide porta | R13 | contrato | G2 | Pest feature HTTP | idem | M59 |
| CT-26 | três ambientes não colidem | R14 | valor literal | G2 | Pest feature HTTP | idem | M60, M61, M62 |
| CT-27 | página alcançável nos dois idiomas | R15 | EP por idioma | G4 | Pest feature HTTP | idem | M63, M64, M65 |
| CT-28 | CHANGELOG registra | R15 | EP | G4 | Pest feature HTTP | idem | M66 |
| CT-29 | âncoras do procedimento | R16 | EP por âncora | G4 | Pest feature HTTP | idem | M67…M70 |
| CT-30 | matriz e rotas do Reverb | R17 | valor literal | G4 | Pest feature HTTP | idem | M71…M74 |
| CT-31 | opções A–D e armadilhas | R18 | EP por âncora | G4 | Pest feature HTTP | idem | M75…M78 |
| CT-32 | espelho pt/en | R19 | contrato | G4 | Pest feature HTTP | idem | M79…M82 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "a chamada a `trustProxies()` vem antes do `append(RaizDeUrlSemPublic)`" (leitura do `bootstrap/app.php`) | não mata mutante observável: a ordem do stack global já é afirmada por CT-13 de `UrlSemPrefixoPublicTest` |
| snapshot `git show <tag>:docker-compose.yml` = arquivo atual | expira na primeira mudança legítima do base; CT-02 + CT-01 provam o que importa |
| `docker build --target assets` com probe do ambiente do `RUN` | exige daemon e uma linha de probe no Dockerfile; virou lacuna L2 + CT-19 `@premissa` |
| CT-B de qualquer natureza | ver `## Sem CT-B` |
| `TRUSTED_PROXIES=10.0.0.1,*` | comportamento não decidido (Q9); sem cenário até a resposta |

## Costuras — notas para quem confirmar

- G6 precisa de **medição** antes de confirmar: (a) se o `HttpKernel` é resolvido de novo depois de `forgetInstance` e o `afterResolving` de `withMiddleware` roda outra vez no `get()`; (b) se não, `refreshApplication()` com a rota sem banco. Se nenhuma das duas medir, a lacuna L3 é aberta e R11/R12 caem para leitura do `bootstrap/app.php` + CT-20 — **piora declarada**, porque a leitura não prova o efeito em `isSecure()`.
- G2 roda no CI (`ubuntu-latest` tem o plugin) e no Windows local (Compose v5.5.1). A interpolação de label em lista foi medida só na v5.5.1; se o CI divergir, CT-11 é o primeiro a acusar.

## Fechamento com mutation testing

- Código PHP novo: `app/Support/ProxiesConfiaveis.php` e a linha do `bootstrap/app.php`. Medir com `--path=app/Support/ProxiesConfiaveis.php`, sem `--filter`, com `--no-tia`, pelo `pestw.cmd` (Windows), aceitando só score com `Duration` plausível e lista de sobreviventes (`.ai/rules/testes.md`). `UNTESTED` é sobrevivente.
- Para YAML, Dockerfile e Markdown o `pest --mutate` não gera mutante — a cobertura de R1–R9 e R13–R19 é a tabela de mutantes de especificação acima.

## Lacunas Declaradas

| # | Lacuna | O que foi tentado / por quê | Regra |
|---|---|---|---|
| L1 | Traefik real roteando `Host` → `nginx:80` com TLS | exige daemon, container do Traefik, DNS e certificado; `docker compose config` prova o contrato, não o roteamento. Fica para a validação manual do quality gate | R4–R7 |
| L2 | prova em runtime de que, sem build-arg, nenhuma `VITE_REVERB_*` existe no ambiente do `npm run build` | `docker run <imagem> env` não vê ARG (só ENV); provar o RUN exige uma linha de probe no Dockerfile. CT-19 cobre por leitura, `@premissa` Q7 | R9 |
| L3 | bloco do Reverb **descomentado** produz configuração válida | descomentar mecanicamente depende de um delimitador no exemplo (Q10); CT-15/CT-16 cobrem o texto cru | R7 |
| L4 | P-08: health check do script com `FORWARD_APP_PORT=127.0.0.1:…` | exige daemon e stack de pé; CT-03 cobre a carga automática do override pelo script | R1 |
| L5 | recorte de caminho da rota do Reverb pelo Traefik | `aberta (Q8)`; o invariante tem CT-16 | R7 |
| L6 | redação "opcional" das portas na página | `aberta (Q6)`; os valores da matriz têm CT-30 | R17 |
| L7 | `TRUSTED_PROXIES=*` com porta publicada em `0.0.0.0` | `aberta (Q11)`; sem cenário até a resposta | R12 |
| L8 | revisão adversarial | obrigatória (Impacto 3 em A e D); a sessão despacha o `fw-adversario-ct` com só o `00` e este `04` | todas |

## Sem CT-B

- Motivo: o `01` declara **Superfície de UI: nenhuma** e o `02`, **Superfície Livewire: nenhuma**; a varredura confirma — nada desta entrega renderiza HTML (artefatos de infra, um `app/Support`, uma linha de bootstrap e documentação). O único request (G6) afirma esquema e host vistos pelo PHP, que `Pest feature HTTP` prova sem navegador. Nenhuma linha de `## Costuras de Teste` tem costura `browser`; o `05` não existe.

## Perguntas para o 00-requisito.md

❓ Q6 · raia: requisito · afeta: RQ-10, RQ-16, P-06 · depende de: —
A matriz do requisito marca as portas de app, banco e cache como "opcional¹" ("nenhuma porta precisa ser publicada" atrás do Traefik). No kit isso não é verdade: o `docker-compose.yml` base publica pgsql, redis, nginx e reverb **sempre**, em `0.0.0.0`, e o override não retira porta (P-06). No mesmo servidor, o segundo ambiente não sobe sem `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_APP_PORT` e `FORWARD_REVERB_PORT` distintas. A página diz que essas quatro chaves são **obrigatórias e distintas** por ambiente (podendo ir para `127.0.0.1`), em vez de "opcionais"?
➡️ Recomendação: **sim, obrigatórias e distintas** — falha fechado; "opcional" ensina uma configuração que não sobe. O invariante já tem cenário (CT-26 com a matriz literal).

❓ Q7 · raia: desenho · afeta: RQ-04, RQ-14, P-01 · depende de: —
Promover os `ARG` vazios com `ENV` (ou declarar `ARG X=`) faz o Vite ver `''` onde hoje vê `undefined` (`node_modules/vite/dist/node/chunks/node.js:loadEnv:5730`), e o `?? 80` do projeto que adicionar Echo deixa de valer — muda o build de quem não ligou nada. Declarar os quatro `ARG` sem default e sem `ENV`?
➡️ Recomendação: **`ARG VITE_REVERB_X` sem `=` e sem `ENV`**, e medir uma vez com `docker build --target assets` que, sem `--build-arg`, nenhuma `VITE_REVERB_*` entra no ambiente do `RUN npm run build`. CT-19 está escrito nesse mecanismo (`@premissa`).

❓ Q8 · raia: requisito · afeta: RQ-12, RQ-04 · depende de: Q2
A rota do Reverb pelo Traefik no mesmo hostname com `PathPrefix(/app)` (exemplo do documento de origem) **captura o painel `/app` do kit**: a regra mais longa ganha prioridade no Traefik e todo `/app/...` vai para o Reverb. Qual recorte vale: (a) `PathPrefix(/app/<REVERB_APP_KEY>)` + `PathPrefix(/apps/<REVERB_APP_ID>)`; (b) subdomínio próprio para o Reverb?
➡️ Recomendação: **(a)** — mantém um hostname só, sem porta extra, e não colide com nenhum painel; (b) fica documentada como alternativa. O invariante "não captura painel" já tem cenário (CT-16), e é vermelho contra o plano atual.

❓ Q9 · raia: requisito · afeta: P-02 · depende de: Q1
`TRUSTED_PROXIES` com `*` dentro de lista (`10.0.0.1,*`) significa "todos" ou é uma lista com um item inválido?
➡️ Recomendação: **lista** (o `*` só vale sozinho) — falha fechado: um erro de digitação não abre confiança total.

❓ Q10 · raia: desenho · afeta: RQ-12 · depende de: Q8
O bloco comentado do Reverb no exemplo pode ter delimitadores de linha (ex.: `# >>> reverb pelo Traefik` / `# <<< reverb pelo Traefik`) para o teste descomentá-lo e rodar `docker compose config` sobre ele?
➡️ Recomendação: **sim** — fecha a lacuna L3 sem regex frágil sobre comentário, e o delimitador também orienta quem descomenta à mão.

❓ Q11 · raia: requisito · afeta: P-02, RQ-10 · depende de: Q1, Q6
O bloco do `.env.docker` sugere `TRUSTED_PROXIES=*`. Com a porta do nginx publicada em `0.0.0.0` (default e P-06), qualquer um que alcance essa porta do servidor sem passar pelo Traefik forja `X-Forwarded-For/Proto/Host` e a aplicação aceita. A página recomenda `*` só junto de `FORWARD_APP_PORT=127.0.0.1:…`, ou recomenda a sub-rede da rede do Traefik no lugar de `*`?
➡️ Recomendação: **`*` só com a porta do nginx em `127.0.0.1`, dito na mesma seção**; a sub-rede como alternativa — falha fechado: confiança total nunca fica sugerida ao lado de uma porta aberta.
