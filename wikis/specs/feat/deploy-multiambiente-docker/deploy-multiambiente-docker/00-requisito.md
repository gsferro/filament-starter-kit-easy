# Requisito — Deploy com Docker: vários ambientes não produtivos no mesmo servidor, atrás do Traefik

## Fonte

- **Origem**: mensagem do mantenedor no chat, via `/feature-wiki`, com um documento de análise
  colado na íntegra (o levantamento feito no projeto-3 com o DevOps do servidor)
- **Data**: 2026-10-05
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado verbatim abaixo (as cinco linhas de pedido e o
  documento `# Múltiplos ambientes (dev / teste / homol) no mesmo servidor com Docker`)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

~~~~markdown
analise a parte do deploy via docker em um servidor com 3 ambientes não produtivos que compartilham o mesmo server. 
- fiz aqui um levantamento para analise e ajuste no nosso docker e na nossa documentação + site, explicando as melhores formas de fazer esse deploy/uso
- faça uma revisão e implemente essa evolução para esse cenario:
- faça em uma nova branch + PR + tag e release. 
- não modifique o que já existe, como esta é como roda outros projetos, então é o default, a nova opção deve entrar caso o usuário do kit queria, sem afetar como já roda hoje
- segue o prorposto:
# Múltiplos ambientes (dev / teste / homol) no mesmo servidor com Docker

Análise de como rodar 3 ambientes não produtivos do mesmo projeto num único
servidor, cada um com sua própria stack de containers. Inclui as restrições do
ambiente real: **Traefik na frente** exigindo rede Docker compartilhada (input
do DevOps).

> Status: documento de análise, não commitado. Nenhuma mudança foi aplicada.

## O mecanismo central

O Compose isola por **nome de projeto**. O `docker-compose.yml` do projeto já foi
desenhado para este cenário — o comentário da linha 18 diz que
`COMPOSE_PROJECT_NAME` no `.env` "vence", e todas as portas são parametrizadas
via `FORWARD_*`. Se cada ambiente declarar um `COMPOSE_PROJECT_NAME` diferente,
você ganha de graça:

- **Containers** com nomes únicos: `projeto3-dev-app-1`, `projeto3-homol-pgsql-1`...
- **Rede própria** por ambiente (`app:9000` no nginx resolve dentro de cada rede)
- **Volumes nomeados prefixados**: `projeto3-dev_pgsql-data`,
  `projeto3-homol_app-storage` — dados isolados sem esforço

Só falta garantir **portas distintas** no host e um **`.env` por ambiente**.

## Cenário real — Traefik na frente (input do DevOps)

Restrições repassadas pelo DevOps do servidor (**confirmadas**):

- Existe um **Traefik** já rodando que faz o proxy de entrada
- Cada ambiente precisa estar **na mesma rede Docker** que o Traefik — a rede
  chama **`my-network`**
- O Traefik usa **docker provider (labels)** e roteia por **hostname**
  (`Host(...)`) com TLS terminado no entrypoint `websecure` — o backend é a
  porta interna **80** do container
- Dentro de cada ambiente, os serviços se falam por **nome + porta interna** —
  que já é o comportamento padrão da rede privada de cada projeto Compose

### Como o compose se encaixa — configuração confirmada

A rede privada de cada projeto (`projeto3-dev_default` etc.) continua isolando
`app ↔ pgsql ↔ redis ↔ queue ↔ scheduler` — não muda nada. Apenas o **`nginx`**
(e o `reverb`, se o WebSocket também passar pelo Traefik) entra na rede externa
`my-network`, com **labels** no serviço:

```yaml
nginx:
  networks:
    - default       # continua falando com app:9000
    - my-network    # rede do Traefik
  labels:
    - traefik.enable=true
    - traefik.docker.network=my-network
    - traefik.http.routers.projeto3-dev.rule=Host(`dev.projtec.fiocruz.br`)
    - traefik.http.routers.projeto3-dev.entrypoints=websecure
    - traefik.http.routers.projeto3-dev.tls=true
    - traefik.http.services.projeto3-dev.loadbalancer.server.port=80

networks:
  my-network:
    external: true
```

Detalhes importantes:

- **`traefik.docker.network=my-network` é necessária**: o container fica em
  duas redes (`default` + `my-network`) e sem esse label o Traefik pode
  escolher o IP errado. O exemplo do DevOps não tinha porque o container dele
  provavelmente está numa rede só
- **Router/service names são globais no Traefik** — precisam ser únicos por
  ambiente (`projeto3-dev`, `projeto3-teste`, `projeto3-homol`), seguindo o
  padrão `projtec-h` / `projtec-h-service` do exemplo dele
- **TLS termina no Traefik** (`websecure` + `tls=true`) → o backend fala HTTP
  na porta 80 interna; `nginx.conf` não muda (`listen 80` já é o esperado)
- **Portas de app não precisam ser publicadas no host** — o Traefik alcança
  `nginx:80` direto pela `my-network`. `FORWARD_APP_PORT` vira opcional (útil
  só para depuração direta)
- Como tudo pode viver num `docker-compose.override.yml` por checkout, o
  `docker-compose.yml` base continua intocado

### Pendências restantes para o DevOps

- **Hostnames definitivos** dos 3 ambientes (o exemplo usa
  `desenv.projtec.fiocruz.br` — confirmar os de dev/teste/homol)
- **Reverb/WebSocket**: passar pelo Traefik também (outro router no serviço
  `reverb`, ex. `PathPrefix(/app)` ou subdomínio próprio) ou publicar porta
  própria no host?

## Opção A — Um checkout por ambiente (recomendada)

```text
/srv/projeto3-dev     → branch develop,    .env com COMPOSE_PROJECT_NAME=projeto3-dev
/srv/projeto3-teste   → branch de testes,  .env com COMPOSE_PROJECT_NAME=projeto3-teste
/srv/projeto3-homol   → branch release,    .env com COMPOSE_PROJECT_NAME=projeto3-homol
```

Em cada pasta:

```bash
docker compose --profile app up -d --build
```

- **Prós**: branches independentes (dev em feature, homol em release), `.env`
  por pasta, zero alteração no compose, `restart: unless-stopped` já sobrevive
  reboot do servidor
- **Contras**: 3× imagens (disco) e 3× pgsql/redis (RAM) — mitigável, ver Opção D

## Opção B — Um checkout só + `-p` + `--env-file`

```bash
docker compose -p projeto3-dev --env-file .env.dev --profile app up -d
```

- **Prós**: um diretório só, uma imagem compartilhada se o código for o mesmo
- **Contras**: **dois conflitos com o compose atual** — `env_file: .env` e o
  bind `./.env:/var/www/.env` estão hardcoded. `--env-file` muda a interpolação
  de variáveis, mas **não** muda o `env_file:` nem o bind. Exigiria parametrizar
  o compose (`./${ENV_FILE:-.env}:...`). Além disso, um checkout = uma branch por
  vez, então os 3 ambientes rodariam **o mesmo código** — útil só se
  dev/teste/homol divergirem por configuração, não por código

## Opção C — Override files por ambiente

`docker-compose.dev.yml`, `docker-compose.teste.yml`... combinados com `-f`.
Como o compose já parametriza tudo por variáveis, os overrides adicionam
arquivos sem ganhar nada sobre a Opção A. **Descartada.**

## Opção D — Variante híbrida: infra compartilhada

Rodar **um** projeto `projeto3-infra` com pgsql/redis/mailpit/llama
compartilhados, e os apps apontando para ele.

- **pgsql**: 1 container, 3 databases (`projeto3_dev`, `projeto3_homol`...) — o
  init em `docker/pgsql/init/` já cria banco extra de teste, mesmo padrão
- **redis**: 1 container, `REDIS_DB` diferente por ambiente (ou prefixo de
  chave) — cuidado com `cache:clear` cruzado
- **llama.cpp**: se usar o profile `ai`, **compartilhar é quase obrigatório** —
  são ~8 GB de RAM por instância
- **Custo**: exige rede docker externa compartilhada ou portas publicadas +
  apontar `DB_HOST`/`REDIS_HOST` para o IP do host. Mais fios para manter

Sugestão de equilíbrio: **pgsql e redis por ambiente** (baratos, isolamento
total), **llama e mailpit compartilhados** se forem usados.

## Armadilhas concretas encontradas no projeto

- **`VITE_*` é assado no build**: o `Dockerfile.laravel` roda `npm run build`
  no estágio `assets` **sem copiar o `.env`**. O `VITE_REVERB_HOST/PORT` no
  `environment:` do compose é runtime — não chega ao JS compilado. Se cada
  ambiente tiver porta Reverb pública diferente, a imagem precisa receber isso
  via `ARG` de build ou `.env` copiado no estágio de assets. Com o Traefik
  confirmado por hostname + TLS, os valores ficam
  `VITE_REVERB_HOST=<hostname do ambiente>`, `PORT=443`, `SCHEME=https` —
  continuam variando por ambiente, então a correção de build se aplica
- **Cookies**: **resolvido pelo roteamento por hostname** — cada ambiente tem
  domínio próprio (`dev.projtec.fiocruz.br` etc.) e cookies são por host. Não
  precisa de `SESSION_COOKIE` customizado
- **`APP_KEY`**: use chaves diferentes por ambiente — sessions/cookies
  criptografados ficam incompatíveis entre si, o que reforça o isolamento
- **`APP_URL`** por ambiente, `APP_DEBUG=false` fora do dev

## Matriz de portas sugerida (Opção A, por porta)

| Variável | dev | teste | homol |
|---|---|---|---|
| `FORWARD_APP_PORT` *(opcional¹)* | 8090 | 9090 | 8080 |
| `FORWARD_DB_PORT` *(opcional¹)* | 5433 | 5434 | 5435 |
| `FORWARD_REDIS_PORT` *(opcional¹)* | 6380 | 6381 | 6382 |
| `FORWARD_REVERB_PORT` *(só se fora do Traefik²)* | 8190 | 8191 | 8192 |

¹ Com o Traefik roteando por hostname via `my-network`, nenhuma porta precisa
ser publicada no host — manter só para depuração/acesso direto, ou bind em
`127.0.0.1` para administração.

² Se o Reverb **não** passar pelo Traefik: o default `FORWARD_REVERB_PORT=8090`
colidiria com a porta reservada ao dev — offsets aplicados.

Como o Traefik roteia por **hostname** com TLS, os ambientes ganham cookies
isolados, HTTPS e URLs limpas de graça. O `APP_URL` de cada `.env` vira
`https://<hostname-do-ambiente>`.

## Fluxo de atualização por ambiente

```bash
cd /srv/projeto3-dev
git pull
docker compose --profile app up -d --build
```

`docker compose -p projeto3-dev ps` / `logs -f` para operar cada um isoladamente.

## Resumo

**Opção A + Traefik (confirmado)**: 3 pastas, 3 `.env`, `COMPOSE_PROJECT_NAME`
distinto, e o `nginx` de cada stack na rede externa `my-network` com os labels
do Traefik — preferencialmente via `docker-compose.override.yml` por checkout,
sem tocar no YAML base. TLS termina no Traefik (`websecure`), backend segue
HTTP na porta interna 80, `nginx.conf` não muda.

Ajustes estruturais que valem a pena quando for implementar:

- **`VITE_*` no build da imagem** (ARG ou `.env` no estágio `assets`) — cada
  ambiente tem hostname público próprio (`VITE_REVERB_HOST=dev.x`, `PORT=443`,
  `SCHEME=https`)
- **Publicação de portas no host** passa a ser opcional — só para depuração;
  db/redis podem ficar internos ou em `127.0.0.1`
- **`FORWARD_REVERB_PORT` default 8090** colide com a porta reservada ao dev
  se o Reverb for publicado fora do Traefik
- **Pendências**: hostnames definitivos dos 3 ambientes e rota do Reverb
~~~~

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | O Docker do kit, a documentação e o site passam a cobrir o cenário de três ambientes não produtivos no mesmo servidor, explicando como fazer esse deploy | "fiz aqui um levantamento para analise e ajuste no nosso docker e na nossa documentação + site, explicando as melhores formas de fazer esse deploy/uso" | funcional | fechada |
| RQ-02 | O levantamento é **revisado** antes de ser implementado: afirmação do documento que o código do kit contradiz é registrada como divergência, não transplantada | "faça uma revisão e implemente essa evolução para esse cenario" | restrição | fechada |
| RQ-03 | A entrega sai em branch nova, com PR, tag e release | "faça em uma nova branch + PR + tag e release." | restrição | fechada |
| RQ-04 | Nada do que já existe muda de comportamento: o `docker-compose.yml`, o `Dockerfile.laravel` e o script de deploy continuam produzindo, sem opt-in, exatamente o que produzem hoje | "não modifique o que já existe, como esta é como roda outros projetos, então é o default" | restrição | fechada |
| RQ-05 | A nova forma de deploy é **opt-in**: entra só quando quem usa o kit a escolhe, e quem não escolhe nada não é afetado | "a nova opção deve entrar caso o usuário do kit queria, sem afetar como já roda hoje" | funcional | fechada |
| RQ-06 | O isolamento entre ambientes é por `COMPOSE_PROJECT_NAME` distinto, um `.env` por ambiente e portas distintas no host — e isso fica documentado como o mecanismo central | "Se cada ambiente declarar um `COMPOSE_PROJECT_NAME` diferente, você ganha de graça […] Só falta garantir **portas distintas** no host e um **`.env` por ambiente**" | funcional | fechada |
| RQ-07 | O `nginx` de cada ambiente entra na rede externa do Traefik, declarada `external: true`, com os labels do docker provider: `traefik.enable`, `traefik.docker.network`, router por `Host(...)`, entrypoint `websecure`, `tls=true` e service na porta 80 | "Apenas o **`nginx`** […] entra na rede externa `my-network`, com **labels** no serviço" + o bloco YAML | funcional | fechada |
| RQ-08 | O label `traefik.docker.network` é obrigatório, e os nomes de router e service são únicos por ambiente | "**`traefik.docker.network=my-network` é necessária**" / "**Router/service names são globais no Traefik** — precisam ser únicos por ambiente" | funcional | fechada |
| RQ-09 | O TLS termina no Traefik; o backend segue HTTP na porta interna 80 e o `nginx.conf` não muda | "**TLS termina no Traefik** (`websecure` + `tls=true`) → o backend fala HTTP na porta 80 interna; `nginx.conf` não muda" | restrição | fechada |
| RQ-10 | Publicar porta no host passa a ser opcional: só para depuração, ou com bind em `127.0.0.1` para administração | "`FORWARD_APP_PORT` vira opcional (útil só para depuração direta)" / "manter só para depuração/acesso direto, ou bind em `127.0.0.1` para administração" | funcional | fechada |
| RQ-11 | A configuração do Traefik vive num `docker-compose.override.yml` por checkout; o `docker-compose.yml` base fica intocado | "Como tudo pode viver num `docker-compose.override.yml` por checkout, o `docker-compose.yml` base continua intocado" | restrição | fechada |
| RQ-12 | O Reverb tem duas rotas possíveis — pelo Traefik (router próprio no serviço `reverb`) ou porta própria no host — e a decisão é do servidor, não do kit | "**Reverb/WebSocket**: passar pelo Traefik também (outro router no serviço `reverb`, ex. `PathPrefix(/app)` ou subdomínio próprio) ou publicar porta própria no host?" | funcional | fechada |
| RQ-13 | A documentação apresenta a **Opção A** (um checkout por ambiente, com o fluxo de atualização) como recomendada, e as opções B, C e D com prós e contras — a D com a sugestão de equilíbrio (banco e cache por ambiente; llama e mailpit compartilhados) | "## Opção A — Um checkout por ambiente (recomendada)" … "## Opção D — Variante híbrida: infra compartilhada" … "Sugestão de equilíbrio" | funcional | fechada |
| RQ-14 | A imagem recebe os `VITE_*` no build (`ARG` no estágio `assets`), porque hostname, porta e esquema do Reverb variam por ambiente | "**`VITE_*` é assado no build** […] a imagem precisa receber isso via `ARG` de build ou `.env` copiado no estágio de assets" | funcional | fechada |
| RQ-15 | As armadilhas ficam documentadas: cookies isolados pelo hostname (sem `SESSION_COOKIE` customizado), `APP_KEY` diferente por ambiente, `APP_URL` por ambiente e `APP_DEBUG=false` fora do dev | "## Armadilhas concretas encontradas no projeto" | funcional | fechada |
| RQ-16 | A matriz de portas sugerida (dev/teste/homol) é publicada com as duas notas — nenhuma porta precisa ser publicada atrás do Traefik, e o default `FORWARD_REVERB_PORT=8090` colide com a porta do dev quando o Reverb fica fora do Traefik | "## Matriz de portas sugerida (Opção A, por porta)" + notas ¹ e ² | funcional | fechada |
| RQ-17 | Os hostnames dos ambientes não são fixados no kit: ficam parametrizados por ambiente, porque ainda são pendência do DevOps | "**Hostnames definitivos** dos 3 ambientes […] confirmar os de dev/teste/homol" | restrição | fechada |

**Por que RQ-04 e RQ-05 são separadas**: são falsificáveis em separado. Uma entrega pode acertar
RQ-05 (há uma opção nova que se liga) e errar RQ-04 (ao ligá-la para um, o default de todos
mudou — um `networks:` novo no YAML base, uma porta que deixou de ser publicada). Fundidas, a
matriz de rastreabilidade marcaria ✅ com metade entregue.

**Por que RQ-02 é cláusula e não premissa**: "faça uma revisão" é um pedido de processo com
rastro observável — a divergência entre o documento e o código fica escrita (seção
`## Premissas` abaixo e `## Auditoria Pré-Implementação` do `03`). Transplantar o documento sem
revisão atenderia RQ-01 e falharia o pedido.

**Por que RQ-12 fecha sem a resposta do DevOps**: a cláusula é a **pergunta** ("passar pelo
Traefik também … ou publicar porta própria?"), e o kit a atende preparando **as duas** rotas,
cada uma ligada por `.env`. A escolha é por servidor, e fica fora do kit — P-04.

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Qn é a sequência única da feature. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | O documento não menciona **proxies confiáveis**: atrás do Traefik com TLS terminado, o Laravel recebe HTTP na porta 80 e, sem confiar nos cabeçalhos `X-Forwarded-*`, gera URL `http://`, cookie de sessão sem `secure` e `request()->isSecure()` falso. O kit passa a ler uma chave `TRUSTED_PROXIES` (ausente = ninguém, como hoje)? Estende o pedido: é uma chave nova no `.env` | RQ-09, P-02 | **sim** — falha fechado (ausente = comportamento de hoje); sem ela, o cenário documentado não funciona de ponta a ponta no primeiro acesso | aberta — implementada como P-02 (não bloqueia: ausente é o default de hoje) |
| Q2 | Qual rota do Reverb o servidor do projeto-3 vai usar — pelo Traefik no mesmo hostname (`/app` e `/apps`) ou porta própria no host? | RQ-12 | **pelo Traefik, no mesmo hostname** — sem porta extra exposta, TLS de graça, e a pendência de porta por ambiente some; o kit entrega o bloco pronto, comentado, e a outra rota continua valendo com `FORWARD_REVERB_PORT` | aberta — não bloqueia: as duas rotas estão entregues (P-04) |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | O kit **não consome** `VITE_REVERB_*` hoje: o JavaScript empacotado é vazio de Echo e o Filament não publica a configuração `broadcasting.echo`. O `ARG` no estágio `assets` (RQ-14) é portanto **inerte** por padrão e existe para o projeto que adicionar Echo — a armadilha "VITE assado no build" é verdadeira em geral e não tem efeito observável no kit de hoje | step 3 — confronto código × documento (revisão pedida por RQ-02) | 2026-10-05 | RQ-14, RQ-04 | vigente |
| P-02 | Atrás do Traefik o Laravel precisa confiar no proxy para honrar `X-Forwarded-Proto`/`Host`; o kit passa a ler `TRUSTED_PROXIES` (lista separada por vírgula ou `*`), e **ausente ou vazia** mantém o comportamento de hoje (nenhum proxy confiável) | step 3 — lacuna do documento; doc oficial do Laravel 13 (*Configuring Trusted Proxies*) | 2026-10-05 | RQ-09, RQ-05 | vigente |
| P-03 | O arquivo de exemplo do override vive em `docker/`, porque é o único caminho que **viaja pelas duas rotas de entrega** (`create-project` e `kit:update`); a cópia ativa (`docker-compose.override.yml` na raiz) é por servidor, como o `.env`, e fica fora do git | step 3 — leitura das listas de entrega do `kit:update` e do `.gitattributes` | 2026-10-05 | RQ-05, RQ-11 | vigente |
| P-04 | O kit não decide hostname nem rota do Reverb: entrega as chaves (`TRAEFIK_HOST` obrigatória quando o override está ativo, `TRAEFIK_REDE` com default `my-network`; o entrypoint `websecure` fica no arquivo, que é do servidor) e os dois blocos do Reverb; o que é por servidor fica no `.env` daquele servidor | RQ-12, RQ-17 | 2026-10-05 | RQ-12, RQ-17 | vigente |
| P-05 | O nome do router e do service do Traefik é o próprio `COMPOSE_PROJECT_NAME` — único por ambiente por construção, sem uma chave a mais para errar; sem a chave vale o piso `starter-kit`, igual ao nome do projeto Compose | RQ-08, medição com `docker compose config` (Compose v5.5.1) | 2026-10-05 | RQ-08 | vigente |
| P-06 | O override **não consegue** retirar a publicação de porta do YAML base — o Compose concatena `ports` dos dois arquivos. "Opcional" (RQ-10) se realiza pela própria chave: `FORWARD_APP_PORT=127.0.0.1:8090` publica só no loopback, e sem a chave a porta continua a de hoje | RQ-10, medição com `docker compose config` | 2026-10-05 | RQ-10 | vigente |
| P-07 | A Opção A é a documentada como procedimento; B, C e D entram como análise (prós, contras e, na D, a receita de infra compartilhada por `environment:` no override), sem que o kit ganhe arquivo ou comando para elas | RQ-13 | 2026-10-05 | RQ-13 | vigente |
| P-08 | O `deploy_docker_local.sh` não muda: ele roda `docker compose` no diretório do checkout, e o Compose carrega o `docker-compose.override.yml` sozinho; o health check lê a porta publicada pelo próprio Docker, então o bind em `127.0.0.1` continua respondendo | RQ-04, RQ-11 | 2026-10-05 | RQ-04 | vigente |

## Fora de Escopo (declarado)

- **Trocar o default do kit** — porta 8000 publicada, Postgres + Redis sem profile, `name:
  starter-kit`, o `command:` do nginx e do app. Nada disso muda (RQ-04).
- **Compor a stack do Traefik** (o container do Traefik, seus entrypoints e certificados). O
  Traefik já existe no servidor e é do DevOps; o kit só se encaixa nele.
- **Opção B** (`-p` + `--env-file`): documentada como descartada pelos dois conflitos que o próprio
  documento mediu; o compose **não** é parametrizado para ela.
- **Opção D como arquivo do kit**: um `docker-compose.infra.yml` compartilhado não entra; a D fica
  como receita no texto (P-07).
- **Hostnames definitivos** e a **rota do Reverb** do projeto-3: pendências do DevOps, parametrizadas,
  não decididas aqui (P-04).
- **Echo no JavaScript do kit**: o kit continua sem cliente WebSocket no bundle (P-01); ligar o
  sininho em tempo real pelo navegador é outra feature.
- **Produção** — o documento é sobre três ambientes **não produtivos**; a receita serve a produção
  atrás do mesmo Traefik, mas nada aqui é validado como procedimento de produção.
