# Plano de Ação — Deploy com Docker: vários ambientes não produtivos no mesmo servidor, atrás do Traefik

> Requisito: `00-requisito.md` · Base do PR: `main`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (não há; `wikis/specs/feat/mysql-no-docker/mysql-no-docker/` e
  `wikis/specs/feature/v1-enriquecimento-kit/cache-de-views-no-docker/` são **vizinhas**: a
  primeira trouxe o `COMPOSE_PROJECT_NAME` que esta feature usa como chave do isolamento, a segunda
  decidiu onde cada cache roda no Docker e deixou um teste que lê os dois arquivos que esta feature toca)
- **Motivo**: —
- **Toca infra compartilhada?**: **sim** — `Dockerfile.laravel` (lido por
  `tests/Kit/CacheDeViewsNoDockerTest.php`, com asserções de ausência filtrando comentário) e
  `bootstrap/app.php` (o stack global de middleware de toda tela). **Regressão obrigatória** contra
  `CacheDeViewsNoDockerTest`, `MysqlNoDockerTest`, `DeployDockerLocalTest`, `UrlSemPrefixoPublicTest`
  e `DiagramasDaArquiteturaTest` (CT-75/CT-84, que contam os 12 serviços do compose base).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Docker, docs e site cobrem o cenário de três ambientes | 3, 4, 5 | a página nova é o centro; README e `.env.docker` apontam para ela |
| RQ-02 | o levantamento é revisado, não transplantado | 0 | as divergências estão em `## Premissas` do `00` (P-01, P-02, P-06) e em `## Auditoria Pré-Implementação` do `03` |
| RQ-03 | branch nova, PR, tag e release | 7 | depois do veredito do step 11; segue `wikis/checklist-de-release.md` |
| RQ-04 | nada do que existe muda de comportamento | 1, 2, 3, 4 | todo passo tem o caminho "sem opt-in" idêntico ao de hoje; CT de ausência no `04` |
| RQ-05 | a nova forma é opt-in | 1, 3, 4 | liga por cópia de um arquivo e por chaves no `.env`; sem elas, nada acontece |
| RQ-06 | isolamento por `COMPOSE_PROJECT_NAME` + `.env` + portas | 4, 5 | o mecanismo já existe desde a `mysql-no-docker`; aqui ele é documentado para o cenário e ganha a matriz |
| RQ-07 | `nginx` na rede externa com os seis labels | 3 | o arquivo de exemplo |
| RQ-08 | `traefik.docker.network` obrigatório; router/service únicos | 3 | o nome vem de `COMPOSE_PROJECT_NAME` (P-05) |
| RQ-09 | TLS no Traefik, backend HTTP 80, `nginx.conf` intocado | 1, 3 | o service aponta para a 80; o passo 1 faz o Laravel honrar o `X-Forwarded-Proto` (P-02) |
| RQ-10 | porta no host opcional / bind em `127.0.0.1` | 4, 5 | pela própria chave `FORWARD_*` (P-06); "opcional" só no sentido de ir para o loopback — as quatro são obrigatórias e distintas (P-09, Q6) |
| RQ-11 | tudo num `docker-compose.override.yml`; base intocado | 3, 4 | o exemplo vive em `docker/traefik/` e a cópia ativa fica fora do git (P-03) |
| RQ-12 | Reverb: as duas rotas preparadas | 3, 5 | bloco do router comentado no exemplo + `FORWARD_REVERB_PORT` já existente |
| RQ-13 | Opção A recomendada; B, C e D com prós e contras | 5 | só texto (P-07) |
| RQ-14 | `VITE_*` por `ARG` no estágio `assets` | 2, 3 | inerte sem Echo (P-01); o override passa os `build.args` |
| RQ-15 | armadilhas documentadas | 5 | cookies, `APP_KEY`, `APP_URL`, `APP_DEBUG` |
| RQ-16 | matriz de portas com as duas notas | 4, 5 | no `.env.docker` (comentada) e na página |
| RQ-17 | hostnames parametrizados, não fixados | 3, 4 | `TRAEFIK_HOST` obrigatória só com o override ativo (P-04) |
| P-01 | o kit não consome `VITE_REVERB_*` | 2 | o `ARG` entra com default vazio; o CT prova que a imagem de hoje é a mesma |
| P-02 | `TRUSTED_PROXIES`, ausente = ninguém | 1 | — |
| P-03 | exemplo em `docker/`, cópia ativa fora do git | 3, 4 | — |
| P-04 | hostname e rota do Reverb por servidor | 3, 4 | — |
| P-05 | router = `COMPOSE_PROJECT_NAME` | 3 | — |
| P-06 | "opcional" via `FORWARD_APP_PORT=127.0.0.1:porta` | 4, 5 | — |
| P-07 | B, C, D só como análise | 5 | — |
| P-09 | as quatro `FORWARD_*` obrigatórias e distintas | 4, 5 | — |
| P-10 | rota do Reverb recorta por `/app/<chave>` e `/apps/<id>` | 3, 5 | — |
| P-11 | `*` só vale sozinho | 1 | — |
| P-12 | `ARG` sem default e sem `ENV` | 2 | — |
| P-14 | só o `nginx` na rede do Traefik; aviso de DNS na página | 3, 5 | — |
| P-15 | `build.args` em lista sem valor | 3 | — |
| P-16 | `REVERB_APP_KEY`/`REVERB_APP_ID` com `:?` | 3 | — |
| P-17 | golden do base a partir da `v0.44.0`, todos os profiles | 6 | — |
| P-08 | `deploy_docker_local.sh` não muda | 5 | o CT do `04` prova que o script continua lendo a porta publicada pelo Docker |

## Objetivo

Hoje o kit sobe **uma** stack por máquina: `docker compose --profile app up -d --build` publica o
`nginx` na porta 8000, o projeto Compose chama `starter-kit` (ou o nome gravado pelo `kit:install`) e
o acesso é direto, por IP e porta. Esta entrega faz o mesmo kit servir **três ambientes do mesmo
projeto num servidor só, atrás de um Traefik que roteia por hostname com TLS** — sem mudar nada
para quem continua subindo uma stack como sempre.

O que entra é uma **opção**: um arquivo de override de exemplo que coloca o `nginx` na rede do
Traefik com os labels certos, as chaves de `.env` que o parametrizam, a leitura de `TRUSTED_PROXIES`
para o Laravel honrar o TLS terminado no proxy, os `ARG` de build para quem tiver Echo no bundle,
e uma página do site que ensina o procedimento inteiro — um checkout por ambiente, um `.env` por
checkout, `COMPOSE_PROJECT_NAME` distinto, portas distintas ou nenhuma porta.

## Contexto

Cinco fatos do código atual sustentam o plano, todos lidos e medidos — não supostos:

1. **O isolamento por nome de projeto já existe.** `docker-compose.yml:name:21` é o piso
   `starter-kit`; `COMPOSE_PROJECT_NAME` no `.env` vence, e é o que o `kit:install` grava
   (`app/Support/CustomizadorDaInstalacao.php:definirNoEnv():273`). Containers, rede e volumes já
   saem prefixados. O que falta é só o encaixe no Traefik e a documentação do cenário.
2. **O Compose carrega o `docker-compose.override.yml` sozinho** e mescla por regras conhecidas —
   medido com `docker compose config` (v5.5.1) numa sonda fora do repositório: `ports` dos dois
   arquivos **concatenam** (o override não remove a porta publicada pelo base), `networks` e
   `build.args` mesclam, `labels` em forma de **lista** interpolam `${COMPOSE_PROJECT_NAME}` (em
   forma de mapa a chave **não** é interpolada), e uma rede externa precisa de chave fixa com
   `name: ${VAR:-default}`. Sem o override presente, o `config` é byte a byte o de hoje.
3. **O kit não consome `VITE_REVERB_*`.** `resources/js/app.js` tem uma linha (`//`), não há
   `laravel-echo` nem `pusher-js` no `package.json`, e o Filament só leria a configuração
   `broadcasting.echo` se ela fosse publicada — no vendor ela está comentada
   (`vendor/filament/support/config/filament.php:'echo':19`). O `VITE_REVERB_HOST: localhost` do
   compose (`docker-compose.yml:VITE_REVERB_HOST:214`) é hoje inerte. A armadilha do documento vale
   para um projeto que acrescente Echo; no kit, o `ARG` é preparação (P-01).
4. **Nenhum proxy é confiável por padrão.** `TrustProxies` nasce com `$proxies` nulo
   (`vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php:$proxies:15`) e só
   passa a confiar quando `bootstrap/app.php` chama `trustProxies(at: …)`
   (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/Middleware.php:trustProxies():698`,
   que é no-op com `at` nulo). O kit não chama. Atrás do Traefik com TLS terminado, `url()` sai
   `http://`, o cookie de sessão não é `secure` e o `RaizDeUrlSemPublic` — que depende de rodar
   **depois** do `TrustProxies` justamente para ver host e esquema reais — lê o esquema errado.
5. **Duas rotas de entrega, e só uma delas inclui a raiz.** `docker/` está em
   `KitUpdate::CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:'docker':196`), como
   `docker-compose.yml` e `Dockerfile.laravel` (`:'docker-compose.yml':334`); `.env.docker` e
   `deploy_docker_local.sh` **não** estão — viajam só pelo `create-project`. Um arquivo novo na raiz
   não chegaria a quem já instalou; dentro de `docker/` chega pelas duas rotas sem mexer na lista.

## Análise dos Arquivos Existentes

### `docker-compose.yml`

- `name: starter-kit` (`docker-compose.yml:name:21`) e o comentário acima dele explicam que
  `COMPOSE_PROJECT_NAME` vence e que nenhum serviço tem `container_name`.
- `nginx` publica `'${FORWARD_APP_PORT:-8000}:80'` (`docker-compose.yml:FORWARD_APP_PORT:252`);
  `reverb` publica `'${FORWARD_REVERB_PORT:-8090}:8090'` (`docker-compose.yml:FORWARD_REVERB_PORT:339`).
- Os seis serviços que fazem `build:` (`app`, `nginx`, `queue`, `scheduler`, `reverb`, `pulse`)
  usam o mesmo `Dockerfile.laravel`; o `nginx` tem `target: web`, os outros `target: app`.
- Os cinco serviços PHP fixam `env_file: .env` (`docker-compose.yml:env_file:201`) e um bloco
  `environment:` que vence o `.env` — é onde `VITE_REVERB_HOST: localhost` e
  `LLAMACPP_URL: 'http://llamacpp:8080/v1'` moram. Um override pode **sobrepor** essas chaves por
  nome (mesclagem por chave), que é a porta de entrada da Opção D (infra compartilhada) sem tocar o base.
- **Não muda nesta entrega.** Nenhuma linha. `DiagramasDaArquiteturaTest` CT-75/CT-84 contam
  exatamente 12 serviços e `MysqlNoDockerTest` CT-14 exige o piso `name: starter-kit`.

### `Dockerfile.laravel`

- Estágio `assets` (`Dockerfile.laravel:assets:26`) roda `RUN npm run build`
  (`Dockerfile.laravel:build:36`) sem `.env` — `.dockerignore` exclui `.env` e `.env.*`.
- O Vite embute `VITE_*` no bundle no build e lê variáveis já presentes no ambiente do processo com
  prioridade sobre os arquivos `.env` (doc oficial do Vite, *Env Variables and Modes*). Um `ARG`
  promovido a `ENV` no estágio `assets` é o caminho para o valor chegar ao `npm run build`.
- `CacheDeViewsNoDockerTest` afirma **ausência** de `view:cache`, `config:cache` e `route:cache` no
  Dockerfile sem comentário: o comentário novo do passo 2 **não cita** esses comandos.

### `bootstrap/app.php`

- `->withMiddleware(…)` (`bootstrap/app.php:withMiddleware():16`) só faz
  `$middleware->append(RaizDeUrlSemPublic::class)`. O comentário ali já diz que a posição importa
  por causa do `TrustProxies`. É onde entra o `trustProxies(at: …)`.
- `bootstrap/` não está em nenhum glob de `.ai/rules/`; `app/Support/**` está (`support.md`).

### `deploy_docker_local.sh`

- Roda no diretório do próprio script, `git pull --ff-only` → `docker compose --profile app up -d
  --build` → migrations → health check lendo a porta publicada pelo Docker
  (`deploy_docker_local.sh:porta:92`: `docker compose --profile app port nginx 80`). Com
  `FORWARD_APP_PORT=127.0.0.1:8090`, `port` responde `127.0.0.1:8090`, o `sed 's/.*://'` deixa
  `8090` e o `curl` em `localhost:8090` continua chegando. Com o override na pasta, o Compose o
  carrega sem flag. **Não muda** (P-08); `DeployDockerLocalTest` segue como regressão.

### `.env.docker`, `.env.example`, `.gitignore`, `.dockerignore`

- `.env.docker` é o "upgrade" de quem usa as stacks: tem o bloco do Reverb
  (`.env.docker:BROADCAST_CONNECTION:63`) e o `# DOCKER_DB_SERVICE=mysql` comentado
  (`.env.docker:DOCKER_DB_SERVICE:56`), que `MysqlNoDockerTest` CT-20 amarra ao compose. É o lugar
  das chaves novas do Traefik, pelo mesmo contrato.
- `.env.example` tem `COMPOSE_PROJECT_NAME=starter-kit` ativo (`.env.example:COMPOSE_PROJECT_NAME:15`)
  e é onde o kit documenta cada chave que passou a entender — `TRUSTED_PROXIES` entra lá,
  **comentada**, porque ausente é o default e ela não é específica do Docker.
- `.gitignore` ignora `.env` (`.gitignore:.env:3`); a cópia ativa do override é por servidor como o
  `.env` e entra ao lado.
- `.dockerignore` já exclui `docker-compose*` da imagem (`.dockerignore:docker-compose:23`) — o
  exemplo em `docker/traefik/` entra no contexto de build (é um arquivo de texto pequeno; inócuo).

### Site e READMEs

- `site/converter.mjs` gera `sidebar.json`, `redirects.json` e os stubs `site/public/{idioma}/…html`
  a partir de `docs/`: página nova = dois `.md` com front-matter (`title`, `description`,
  `sidebar.order`) + rodar o conversor. `SiteDeDocumentacaoTest` CT-20/CT-29/CT-34/CT-38/CT-41
  conferem navegação, front-matter, espelho pt/en, stub e ordem; CT-22/CT-45 conferem links
  internos (relativos, nunca absolutos); CT-14 confere que todo link do README para o site resolve.
- Os READMEs têm a seção `## Docker` com a tabela de portas e a subseção
  `### Atualizando a stack na máquina que a hospeda`. Entra uma subseção irmã, curta, com link
  para a página nova. `DeployDockerLocalTest` CT-04 exige que `./deploy_docker_local.sh` e
  `--recreate` continuem citados.
- `tests/Kit/SiteDeDocumentacaoTest.php` também trava as contagens dos READMEs (features
  especificadas em `wikis/specs/`, arquivos de teste, badge de `it()`): sobem no step 10.

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | A cópia ativa `docker-compose.override.yml` na raiz entra no `.gitignore` do kit; quem quiser versionar o override no próprio projeto apaga a linha | Q3 (desenho) | difícil de reverter (é uma linha) | sessão, pela recomendação — 2026-10-05 |
| D2 | A página nova fica em **Operação** (`operacao/deploy-docker-multiambiente.md`, `order: 6`), não em Começar: deploy em servidor é o dia a dia de quem opera, e `instalacao-avancada` já é a maior página do site | Q4 (desenho) | surpreendente | sessão, pela recomendação — 2026-10-05 |
| D3 | A leitura de `TRUSTED_PROXIES` sai de `bootstrap/app.php` para `App\Support\ProxiesConfiaveis::doEnv()`: lista por vírgula, `*`, vazio e ausente têm teste unitário; o bootstrap fica com uma linha | Q5 (desenho) | trade-off real (uma classe para quatro casos) | sessão — 2026-10-05 |
| D4 | O bloco do Reverb pelo Traefik vai **comentado** no exemplo, no mesmo hostname com `PathPrefix(/app)` e `PathPrefix(/apps)` e service na 8090; descomentar é a adesão. A rota por porta própria é o `FORWARD_REVERB_PORT` que já existe | Q2 (requisito, aberta) | surpreendente | sessão, pela recomendação da Q2 — 2026-10-05 |
| D6 | O bloco comentado do Reverb no exemplo fica entre `# >>> reverb-traefik` e `# <<< reverb-traefik`, para o teste descomentá-lo mecanicamente e rodar `docker compose config` (fecha a lacuna L3 do `04`) e para orientar quem descomenta à mão | Q10 (desenho) | surpreendente | sessão, pela recomendação — 2026-10-05 |
| D8 | A página avisa, na seção do Traefik, que nome de serviço é global na rede compartilhada (outro `app`/`reverb` na `my-network` desvia o `fastcgi_pass`) e manda confirmar com o DevOps; o `nginx.conf` não muda | Q12 (requisito, aberta) | surpreendente | sessão, pela recomendação — 2026-10-05 |
| D9 | O golden `tests/Kit/fixtures/compose-base.json` nasce de `git show v0.44.0:docker-compose.yml`, com `--profile '*'`, comparado inteiro (só o prefixo da pasta temporária é normalizado); regenerar é passo deliberado com linha no CHANGELOG | ADV2-01..03 | trade-off | sessão — 2026-10-05 |
| D7 | A página recomenda `TRUSTED_PROXIES=*` **só** com a porta do nginx em `127.0.0.1`, na mesma seção, e a sub-rede do Traefik como alternativa quando a porta sai para fora | Q11 (requisito, aberta) | surpreendente | sessão, pela recomendação — 2026-10-05 |
| D5 | Nenhum channel de log: o único código PHP novo roda no bootstrap, antes de o container de log existir, e é parsing puro de uma string; o comportamento é provado por teste, não por log | — | — | sessão — 2026-10-05 |

## Autorização

Sem policy, gate ou guard. O `TrustProxies` é middleware **global** já presente no stack do
Laravel; esta entrega só o configura, por chave de ambiente, e a ausência da chave o deixa como está.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** Nada nesta entrega é tela: são arquivos de infra, uma chave de ambiente,
documentação e um script que não muda. Não há `05-casos-de-teste-browser.md`; o motivo fica
registrado no `04`.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `TRUSTED_PROXIES` | ausente → nenhum proxy confiável (hoje) | Lista separada por vírgula de IPs/CIDRs, ou `*`. Lida em `bootstrap/app.php`; vazia ou ausente não chama `trustProxies()`. Documentada comentada no `.env.example` e ativa (`*`) no bloco Traefik do `.env.docker` |
| `TRAEFIK_HOST` | **sem default** — o override falha com mensagem (`${TRAEFIK_HOST:?…}`) | Hostname público do ambiente, usado no `Host(...)` do router. Só é lida com o override ativo (RQ-17, P-04) |
| `TRAEFIK_REDE` | `my-network` | Nome da rede Docker externa onde o Traefik está; vai para `traefik.docker.network` e para o `name:` da rede externa |
| `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`, `VITE_REVERB_APP_KEY` | já existem no `.env.example` | Passam a chegar ao `npm run build` da imagem **quando** o override os repassa em `build.args`; default vazio no `ARG` (RQ-14, P-01) |
| `FORWARD_APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_REVERB_PORT` | já existem | Ganham a matriz sugerida por ambiente e a forma `127.0.0.1:porta` para publicar só no loopback (RQ-10, RQ-16, P-06) |
| `COMPOSE_PROJECT_NAME` | já existe (`starter-kit`) | Passa a ser também o nome do router e do service do Traefik (P-05) — um por ambiente |

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum. Os serviços `queue`, `scheduler`, `reverb` e `pulse` só recebem `build.args` pelo override.

## Modelo de Execução

**Um request, sem trabalho adiado.** `ProxiesConfiaveis::doEnv()` roda uma vez no bootstrap da
aplicação (não por request) e devolve um valor estático para o `TrustProxies`; o Compose e o
Traefik estão fora do processo PHP.

## Impacto em Features Existentes

| Onde | O que muda | Risco |
|---|---|---|
| `tests/Kit/CacheDeViewsNoDockerTest.php` | lê o `Dockerfile.laravel` inteiro, com asserções de ausência filtrando comentário | baixo — o passo 2 acrescenta `ARG`/`ENV` e um comentário que **não cita** os comandos proibidos |
| `tests/Kit/DiagramasDaArquiteturaTest.php` CT-75/CT-84 | contam 12 serviços e os profiles no compose base | nenhum — o base não muda |
| `tests/Kit/MysqlNoDockerTest.php` | piso `name: starter-kit`, chave no `.env.example`, contrato `.env.docker` × compose | nenhum — nada disso muda; o `.env.docker` só ganha um bloco |
| `tests/Kit/DeployDockerLocalTest.php` | ordem pull → build, faixa do `--help`, bit de execução, READMEs | nenhum — o script não muda; os READMEs continuam citando o script |
| `tests/Kit/UrlSemPrefixoPublicTest.php` | o middleware que depende da posição relativa ao `TrustProxies` | nenhum — o `append` continua; só entra uma configuração do `TrustProxies` |
| `tests/Kit/KitUpdateTest.php` | varre `app/` e exige que todo arquivo esteja em `CAMINHOS_DO_KIT` | nenhum — `app/Support` já está na lista; `docker/` também |
| `tests/Kit/SiteDeDocumentacaoTest.php` | navegação, front-matter, stubs, espelho pt/en, contagens dos READMEs | médio — página nova exige rodar o conversor e atualizar as contagens (features especificadas 74 → 75, arquivos de teste, badge) |
| `tests/Kit/RedeDeDocumentacaoTest.php` CT-10/CT-11 | todo teste que lê `docs/`/README leva `naArvoreDoKit()` | nenhum, se os CTs do `04` que leem docs forem guardados |
| Quem roda `docker compose --profile app up -d --build` hoje | nada: sem o override e sem as chaves, `docker compose config` é idêntico | nenhum (CT de ausência) |
| Quem roda `kit:update` | recebe `docker/traefik/docker-compose.override.yml`, o `Dockerfile.laravel`, o `.env.example` e o `.gitignore`? — **`.gitignore` não está em `CAMINHOS_DO_KIT`**; quem já instalou acrescenta a linha à mão (documentado) | baixo |

## Rollback

- Sem migration, sem estado em banco: `git revert` do commit.
- No servidor: apagar `docker-compose.override.yml` da pasta e rodar `docker compose --profile app
  up -d` devolve a stack à de hoje (o `nginx` sai da rede do Traefik e a porta volta ao `.env`);
  remover `TRUSTED_PROXIES` do `.env` desliga a confiança no proxy no próximo `--force-recreate`.

## Dependências

Nenhuma dependência nova de Composer ou NPM. Docker Compose **v2** com suporte a `depends_on.required`
já é exigido pelo compose de hoje; o override usa só interpolação e mesclagem padrão. O Traefik
é do servidor.

## Riscos

| Risco | Mitigação |
|---|---|
| Três `.env` com a mesma `FORWARD_APP_PORT` (default 8000) → o segundo `up` falha com *port is already allocated* | a página e o `.env.docker` trazem a matriz; o erro é duro e nomeia a porta |
| `TRAEFIK_HOST` esquecida → router com `Host()` vazio roteando nada, em silêncio | `${TRAEFIK_HOST:?…}`: o Compose recusa subir e diz o que falta (medido) |
| `traefik.docker.network` esquecida → Traefik escolhe o IP da rede privada e dá 504 | o label está no exemplo com o mesmo valor da rede externa; CT amarra os dois |
| `TRUSTED_PROXIES=*` confia em qualquer `X-Forwarded-*` — aceitável quando **só** o Traefik alcança a porta 80 do container (rede interna) | documentado: com porta publicada no host, preferir o CIDR da rede do Traefik |
| Comentário novo no Dockerfile cita comando de cache proibido e derruba `CacheDeViewsNoDockerTest` | não citar; rodar o arquivo antes de fechar |
| Página nova fora do `sidebar.json`/sem stub → some da navegação sem erro | `node converter.mjs` regenera os três artefatos; CT-20/CT-38 reprovam se faltar |

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem `autenticacao` (`config/logging.php:'autenticacao':132`) e
`configuracoes` (`config/logging.php:'configuracoes':153`), entre outros. Nenhum é desta feature.

### Decisão

**Nenhum log** (D5). O único código PHP novo é `ProxiesConfiaveis::doEnv()`, chamado dentro de
`withMiddleware()` no bootstrap — antes de o container estar resolvido, onde `Log::` não é seguro —,
e é parsing puro: string → `null | '*' | list<string>`. O resultado é observável por teste
(`request()->isSecure()` com e sem a chave) e por `php artisan about`. Infra Docker e Traefik não
logam pelo Laravel.

## Estrutura de Implementação

### 0. Revisão do levantamento (já executada no step 3)

- O documento foi confrontado com o código antes de qualquer arquivo: três afirmações viraram
  premissa (P-01 — `VITE_*` não tem consumidor no kit; P-06 — o override não remove porta; P-02 —
  falta `TRUSTED_PROXIES`, que o documento não menciona) e uma suposição foi medida e confirmada
  (P-05 — router por `COMPOSE_PROJECT_NAME`, com labels em lista).
- **Atende**: RQ-02
- **Logs**: nenhum (passo de análise)

### 1. `TRUSTED_PROXIES` — o Laravel passa a poder confiar no proxy

> Skills: `laravel-best-practices`, `pest-testing`, `ponytail`

- **Path**: `app/Support/ProxiesConfiaveis.php` (novo), `bootstrap/app.php`, `.env.example`
- `final class ProxiesConfiaveis` com um método estático:
  ```php
  /**
   * @return '*'|list<string>|null  null = nenhum proxy confiável (o comportamento de hoje)
   */
  public static function doEnv(mixed $bruto): array|string|null
  ```
  Regras: não-string ou vazio depois de `trim` → `null`; `*` → `'*'`; senão `explode(',')`,
  `trim` em cada item, descarta vazios; lista vazia → `null`. Nenhuma validação de IP/CIDR: o
  Symfony aceita os dois e recusa lixo com exceção clara no primeiro request — melhor que o kit
  reinventar o parser (ponytail: stdlib primeiro).
- `bootstrap/app.php`, dentro de `withMiddleware`, **antes** do `append`:
  ```php
  $middleware->trustProxies(at: ProxiesConfiaveis::doEnv(env('TRUSTED_PROXIES')));
  ```
  Com `at` nulo o método é no-op (fato 4 do Contexto) — o default não muda. `env()` aqui é
  legítimo: o bootstrap roda antes do `config/`, e é o lugar que a doc do Laravel 13 prescreve
  para `trustProxies`. Comentário curto explicando por que a chave é lida aqui e não em `config/kit.php`.
- `.env.example`: logo abaixo de `APP_URL`, um bloco comentado:
  ```
  # Atras de proxy que termina o TLS (Traefik, load balancer): IPs/CIDRs separados por virgula,
  # ou * (so quando a porta do container nao e alcancavel de fora da rede do proxy). Vazio: nenhum.
  # TRUSTED_PROXIES=
  ```
- **Atende**: P-02, RQ-09, RQ-05, RQ-04
- **Logs**: nenhum (D5)

### 2. `ARG` de build para os `VITE_*` no estágio `assets`

> Skills: `ponytail`

- **Path**: `Dockerfile.laravel`
- Entre `WORKDIR /app` e `RUN npm run build` do estágio `assets`:
  ```dockerfile
  # VITE_* e assado no bundle: o Vite le estas variaveis do ambiente do processo no `npm run
  # build`, e o .env nao entra na imagem (.dockerignore). Default vazio = a imagem de hoje. Quem
  # tiver Echo no bundle passa os valores por `build.args` — o override de exemplo em
  # docker/traefik/ ja faz isso. (O kit nao consome VITE_REVERB_* por padrao.)
  ARG VITE_REVERB_HOST
  ARG VITE_REVERB_PORT
  ARG VITE_REVERB_SCHEME
  ARG VITE_REVERB_APP_KEY
  ```
  *(alterado em 2026-10-05: eram `ARG X=` + `ENV`; a derivação do `04` (Q7) mostrou que `ARG X=`
  entra no `RUN` como `''` e o Vite copia `VITE_*` vazia para o bundle — `undefined` viraria `''`
  no projeto com Echo. Medido com `docker build --progress=plain`: `ARG X` sem default e não
  passado fica **ausente**; passado, chega ao `RUN` como variável de ambiente — sem `ENV`. P-12)*
  Posição: depois do `COPY --from=vendor` e antes do `RUN npm run build`, para que uma mudança de
  argumento invalide só a camada do build. O comentário **não cita** `view:cache`, `config:cache`
  nem `route:cache`.
- **Atende**: RQ-14, P-01, RQ-04
- **Logs**: nenhum

### 3. O override de exemplo — `docker/traefik/docker-compose.override.yml`

> Skills: `ponytail`

- **Path**: `docker/traefik/docker-compose.override.yml` (novo)
- Cabeçalho em comentário: o que é, como ligar (`cp docker/traefik/docker-compose.override.yml .`),
  as chaves que lê, o que **não** muda (o base), e que o Compose carrega o arquivo da raiz
  sozinho — sem `-f`, inclusive pelo `deploy_docker_local.sh`.
- Âncora YAML com os quatro `build.args`, em **lista sem valor** *(alterado em 2026-10-05: era mapa
  `${VAR:-}`; a 2ª rodada adversarial (ADV2-07) mostrou que `:-` passa string vazia e reintroduz a
  armadilha de P-12; medido: em lista, o Compose pega do `.env` e omite a ausente — P-15)*:
  ```yaml
  x-vite-args: &vite-args
    - VITE_REVERB_HOST
    - VITE_REVERB_PORT
    - VITE_REVERB_SCHEME
    - VITE_REVERB_APP_KEY
  ```
  aplicada em `app`, `nginx`, `queue`, `scheduler`, `reverb` e `pulse` (`build: { args: *vite-args }`)
  — os seis que fazem `build:`; todos recebem os mesmos args para a imagem ser **uma** (camadas
  compartilhadas) e não seis.
- `nginx`:
  ```yaml
  nginx:
    networks:
      - default
      - traefik
    labels:
      - traefik.enable=true
      - traefik.docker.network=${TRAEFIK_REDE:-my-network}
      - traefik.http.routers.${COMPOSE_PROJECT_NAME:-starter-kit}.rule=Host(`${TRAEFIK_HOST:?defina TRAEFIK_HOST no .env — o hostname publico deste ambiente}`)
      - traefik.http.routers.${COMPOSE_PROJECT_NAME:-starter-kit}.entrypoints=websecure
      - traefik.http.routers.${COMPOSE_PROJECT_NAME:-starter-kit}.tls=true
      - traefik.http.services.${COMPOSE_PROJECT_NAME:-starter-kit}.loadbalancer.server.port=80
  ```
  Labels em **lista**, porque em mapa a chave não interpola (medido). Router e service levam o
  nome do projeto Compose (P-05): único por ambiente, sem chave nova.
- `reverb`: bloco **comentado** (D4), entre os delimitadores `# >>> reverb-traefik` e
  `# <<< reverb-traefik` (D6), com `networks` + labels do router
  `${COMPOSE_PROJECT_NAME:-starter-kit}-reverb`, regra
  ``Host(`${TRAEFIK_HOST}`) && (PathPrefix(`/app/${REVERB_APP_KEY:?…}`) || PathPrefix(`/apps/${REVERB_APP_ID:?…}`))`` *(as duas com `:?` — P-16, ADV2-04: vazia, a regra viraria `/app/`)*,
  mesmo entrypoint, `tls=true`, service na porta 8090 — o WebSocket em `/app/{chave}` e a API em
  `/apps/{id}`, que a doc do Reverb manda servir. *(alterado em 2026-10-05: era `PathPrefix(/app)`,
  que captura o painel `/app` do kit pela prioridade de regra mais longa — Q8/P-10, CT-16)* Comentário
  ao lado: com ele ligado, `REVERB_PORT=443`, `REVERB_SCHEME=https` e `VITE_REVERB_*` idem; sem
  ele, a rota é `FORWARD_REVERB_PORT` com offset por ambiente.
- Rede de topo:
  ```yaml
  networks:
    traefik:
      external: true
      name: ${TRAEFIK_REDE:-my-network}
  ```
- Sem receita da Opção D no arquivo *(cortado no step 6: a receita vive só na página, que é onde
  se lê antes de decidir; o override fica com o que ele aplica)*.
- **Atende**: RQ-07, RQ-08, RQ-09, RQ-11, RQ-12, RQ-14, RQ-17, P-03, P-04, P-05
- **Logs**: nenhum

### 4. `.gitignore` e `.env.docker`

> Skills: `ponytail`

- **Path**: `.gitignore`, `.env.docker`
- `.gitignore`: abaixo de `.env.production`, `/docker-compose.override.yml` com um comentário de
  duas linhas (é por servidor, como o `.env`; o exemplo versionado está em `docker/traefik/`).
- `.env.docker`: bloco novo no fim, comentado, `# --- Varios ambientes no mesmo servidor, atras do
  Traefik ---`, com: `COMPOSE_PROJECT_NAME=projeto-dev` (um por ambiente), `APP_URL=https://…`,
  `TRUSTED_PROXIES=*`, `TRAEFIK_HOST=`, `# TRAEFIK_REDE=my-network`, 
  as quatro `FORWARD_*` comentadas com os valores do **dev** da matriz (`# FORWARD_APP_PORT=127.0.0.1:8090`,
  `# FORWARD_DB_PORT=127.0.0.1:5433`, `# FORWARD_REDIS_PORT=127.0.0.1:6380`, `# FORWARD_REVERB_PORT=8190`)
  e um apontador para a matriz da página *(alterado em 2026-10-05: era só a do app; a derivação do
  `04` (Q6/P-09, CT-25) mostrou que as quatro são obrigatórias — o base publica todas — e que app em
  8090 com o Reverb no default 8090 colide no mesmo ambiente)*. Todas as linhas
  **comentadas**: o `.env.docker` é um arquivo de colar, e as chaves do Traefik só valem com o
  override presente.
- **Atende**: RQ-06, RQ-10, RQ-16, RQ-17, P-03, P-06
- **Logs**: nenhum

### 5. Documentação: página do site, índice da seção, READMEs, CHANGELOG

> Skills: `ponytail`

- **Path**: `docs/pt/operacao/deploy-docker-multiambiente.md` e `docs/en/operacao/deploy-docker-multiambiente.md`
  (novos; front-matter `title`, `description`, `sidebar: order: 6`), `docs/{pt,en}/operacao/index.md`
  (uma frase a mais), `README.md`, `README.en.md`, `CHANGELOG.md` (`[Unreleased]`), e os gerados
  `site/sidebar.json`, `site/redirects.json`, `site/public/{pt,en}/operacao/deploy-docker-multiambiente.html`
  (por `node converter.mjs`, em `site/`).
- Seções da página, nesta ordem: **O mecanismo central** (RQ-06, com `docker compose ps` de dois
  ambientes); **Traefik na frente** (a rede externa, os labels, por que `traefik.docker.network`,
  router único — RQ-07/08/09); **Ligar num servidor, passo a passo** (`cp` do exemplo, as chaves no
  `.env`, `TRUSTED_PROXIES`, `up`, `docker compose config` para conferir — RQ-05/11, P-02);
  **Portas: distintas por ambiente, e no loopback se não forem para fora** (RQ-10, P-06, P-09, a
  matriz — RQ-16, e `*` só com a porta em `127.0.0.1` — D7); **Reverb: duas rotas**
  (RQ-12, D4); **Um checkout por ambiente** (Opção A e o fluxo de atualização com o
  `deploy_docker_local.sh` — RQ-13, P-08); **As outras opções** (B descartada e por quê, C
  descartada, D com a receita por `environment:` — RQ-13, P-07); **Armadilhas** (cookies, `APP_KEY`,
  `APP_URL`, `APP_DEBUG`, e `VITE_*` no build com a nota de que o kit não o consome — RQ-14/15,
  P-01); **O que não muda** (RQ-04). Links internos relativos (`../../comecar/instalacao-avancada/`),
  nunca absolutos (CT-45).
- `operacao/index.md` (pt e en): acrescentar à descrição e ao parágrafo "…e como servir vários
  ambientes do mesmo projeto num servidor só, atrás do Traefik".
- READMEs: subseção `### Vários ambientes no mesmo servidor` / `### Several environments on one
  server` logo depois de "Atualizando a stack…", três frases + link
  `https://gsferro.github.io/filament-starter-kit-easy/{pt,en}/operacao/deploy-docker-multiambiente.html`
  (o formato `.html` que CT-14 resolve).
- `CHANGELOG.md` → `## [Unreleased]` → `### Adicionado` com a entrada da feature; o bump para a
  versão e a seção *Validação antes da tag* entram no passo 7.
- **Atende**: RQ-01, RQ-06, RQ-10, RQ-12, RQ-13, RQ-15, RQ-16, P-06, P-07, P-08
- **Logs**: nenhum

### 6. Testes e verificação

> Skills: `pest-testing`

- Os cenários nascem do `04` (step 7), escritos pelo `fw-executor-ct`. Arquivos previstos:
  `tests/Kit/DeployMultiambienteDockerTest.php` (compose/Dockerfile/override/env/docs) e
  `tests/Kit/ProxiesConfiaveisTest.php` (parsing + bootstrap). Todo caso que leia `docs/`, README
  ou `site/` leva `->skip(fn (): bool => ! naArvoreDoKit(), …)` (`.ai/rules/testes.md`).
- Regressão: `CacheDeViewsNoDockerTest`, `MysqlNoDockerTest`, `DeployDockerLocalTest`,
  `UrlSemPrefixoPublicTest`, `DiagramasDaArquiteturaTest`, `SiteDeDocumentacaoTest`,
  `RedeDeDocumentacaoTest`, `KitUpdateTest`.
- Contagens dos READMEs (features especificadas, arquivos de teste, badge) por comando, no step 10.
- **Atende**: RQ-04, RQ-05 (as provas de ausência), e a suíte dos demais passos
- **Logs**: nenhum

### 7. Release

- Depois do veredito do step 11 e do merge do PR: bump de `config/kit.php`, seção `[x.y.0]` no
  CHANGELOG com *Validação antes da tag* (simulação do cenário 1 por `git archive`, teto de pulados
  por arquivo), tag anotada, `release.yml` verde — a rotina de `wikis/checklist-de-release.md`.
  Minor: feature nova, sem quebra.
- **Atende**: RQ-03
- **Logs**: nenhum

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação: reutilizar (o `COMPOSE_PROJECT_NAME`,
> o `FORWARD_*`, o `TrustProxies` que já está no stack), stdlib antes de código (o parser do Symfony
> valida o CIDR), nativo antes de dependência (o auto-load do override, a mesclagem do Compose),
> uma linha quando possível (o bootstrap), mínimo que funciona. Atalho deliberado leva `ponytail:`.
> Após implementar, `/ponytail:ponytail-review` no diff.
>
> **Caveman `ultra`** na conversa; arquivos wiki, código, commits e PR em prosa normal.
>
> **Baseline antes do primeiro commit de código**: a `main` em `71a7297` teve a suíte Kit+Tenancy
> verde na simulação da `v0.44.0` (3.912 testes, 0 falhas, 841 pulados). A `## Verificação Final`
> compara contra isso.

## Mapeamentos

| Variável do `.env` | Onde cai |
|---|---|
| `COMPOSE_PROJECT_NAME` | `name` do projeto Compose (prefixo de container/rede/volume) **e** nome do router/service do Traefik |
| `TRAEFIK_HOST` | `Host(...)` do router do `nginx` (e do `reverb`, se ligado) |
| `TRAEFIK_REDE` | `traefik.docker.network` + `name:` da rede externa |
| `TRUSTED_PROXIES` | `TrustProxies::at()` via `ProxiesConfiaveis::doEnv()` |
| `VITE_REVERB_*` | `build.args` → `ARG`/`ENV` do estágio `assets` → `npm run build` |
| `FORWARD_*` | `ports` do base, com `127.0.0.1:` opcional no valor |

## Testes

> Ver `04-casos-de-teste.md` para a especificação completa dos cenários de backend.
> Sem `05-casos-de-teste-browser.md` — sem superfície de UI.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest tests/Kit/DeployMultiambienteDockerTest.php tests/Kit/ProxiesConfiaveisTest.php --compact`
- [ ] Regressão nomeada: `CacheDeViewsNoDockerTest`, `MysqlNoDockerTest`, `DeployDockerLocalTest`, `UrlSemPrefixoPublicTest`, `DiagramasDaArquiteturaTest`, `SiteDeDocumentacaoTest`, `RedeDeDocumentacaoTest`, `KitUpdateTest`
- [ ] Suíte completa Kit+Tenancy contra a baseline
- [ ] `pest --mutate --path=app/Support/ProxiesConfiaveis.php` via `pestw.cmd` — score, duração e sobreviventes
- [ ] `docker compose --profile app config` com e sem o override, numa cópia fora da árvore — sem o override, idêntico ao de hoje
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9), antes da reconciliação
- [ ] `node converter.mjs` em `site/` reexecutado sem diff residual
- [ ] Contagens dos READMEs por comando

## Commits
- `:sparkles: feat(docker): deploy opt-in de vários ambientes no mesmo servidor atrás do Traefik`
- `:memo: wiki(deploy-multiambiente-docker): …`
