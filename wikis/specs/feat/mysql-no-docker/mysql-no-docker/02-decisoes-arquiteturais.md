# Decisões Arquiteturais — MySQL no Docker do kit

Todas as medições desta wiki foram feitas nesta sessão, com **Docker 29.7.2 / Docker Compose
v5.5.0** e a imagem **`mysql:8.0`**, num compose de sonda em diretório temporário. Onde há
"medido", há comando executado e saída lida — não doc consultada.

---

## ADR-01: O MySQL entra em profile próprio, e o Postgres **fica** no profile default

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

RQ-01 pede o serviço MySQL; RQ-02 pede que subi-lo não obrigue a subir o Postgres. O compose do
kit hoje tem **um** banco, `pgsql`, sem `profiles:` — ou seja, no profile default, que sobe em
todo `docker compose up -d`. Dois bancos no default subiriam juntos em toda instalação, o que
contraria o motivo do pedido (a escolha do instalador é de opção única).

### Decisão

`mysql` recebe `profiles: [mysql]`. `pgsql` **não** recebe profile — continua no default. Quem
escolheu MySQL sobe assim:

```bash
docker compose up -d mysql redis
```

### Alternativas Consideradas

1. **Os dois bancos em profile, escolha por `COMPOSE_PROFILES` no `.env`** — descartada por
   **duas medições**:

   ```
   .env: COMPOSE_PROFILES=mysql
   $ docker compose up -d              -> mysql, redis          (ok)
   $ docker compose --profile ai up -d -> llamacpp, redis        (o banco DESAPARECEU)
   ```

   O `--profile` da linha de comando **substitui** o `COMPOSE_PROFILES`, não soma. E os comandos
   documentados do kit são todos com `--profile` (`ai`, `mail`, `full`, `app`, `realtime`), então
   cada um deles passaria a subir sem banco. A segunda medição é pior:

   ```
   pgsql e mysql em profile, app com depends_on required:false nos dois
   $ docker compose --profile app up -d  -> app, redis           (aplicação SEM banco, sem erro)
   ```

   Aplicação de pé apontando para um host que não existe é degradação silenciosa — o modo de
   falha que este kit evita por padrão (é a mesma razão do `&&` no `command:` do serviço `app`).

2. **`mysql` no profile `full`, junto com `ai` e `mail`** — descartada: `full` significa "infra
   completa", e incluir os dois bancos ali subiria Postgres e MySQL na mesma stack.

3. **Instalador comenta o `pgsql` no YAML e descomenta o `mysql`**, como no arquivo de referência
   — descartada, ver ADR-02.

### Consequências

- **Positivas**: `docker compose up -d` puro segue com o comportamento documentado no README, sem
  quebra para quem já instalou. Nenhuma variável nova é necessária para subir o banco.
  **Medido**: nomear um serviço que tem profile liga o profile dele e restringe a subida ao que
  foi nomeado — `docker compose up -d mysql redis` cria exatamente dois containers, e o `pgsql`
  do default **não** sobe.
- **Negativas**: o comando de quem usa MySQL é mais comprido que o de quem usa Postgres, e é
  preciso lembrar de nomear o `redis` junto. Fica nos dois READMEs e nas duas páginas de doc.
- **Riscos**: alguém que rode `docker compose up -d` num projeto MySQL sobe um Postgres vazio e
  não entende por quê. Mitigado no texto da doc, que dá o comando completo.

### Referências

- `docker-compose.yml:25-44` (o `pgsql` sem profile)
- `app/Support/CustomizadorDaInstalacao.php:395-418` (o `select()` de opção única)
- ADR-02, ADR-06

---

## ADR-02: O arquivo de referência entra como **insumo**, não como transplante

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

RQ-03 manda ver o `docker-compose.yml` do projeto `universidade-corporativa`, onde o mantenedor
já resolveu o problema. RQ-04 manda **pensar** como adicionar no compose do kit. As duas cláusulas
juntas dizem o que fazer com a referência: ler, e decidir item por item.

### Decisão

Herdado da referência, verbatim ou quase:

| O que | Por que serve ao kit |
|---|---|
| `image: mysql:8.0` | versão fixa, não `latest` — mesma disciplina do `pgvector/pgvector:pg17` e do `redis:7-alpine` do kit |
| `MYSQL_DATABASE: '${DB_DATABASE:-starter_kit}'` | o container lê o `.env` do projeto, exatamente como o `pgsql` do kit já faz |
| `MYSQL_ROOT_PASSWORD` sem `MYSQL_USER` | ver ADR-05 |
| O healthcheck com `mysqladmin ping` e o dólar dobrado | dólar simples seria interpolado pelo Compose; a variável tem de ser lida dentro do container |
| `volumes: mysql-data:/var/lib/mysql` | persistência entre `up`/`down`, como o `pgsql-data` |
| `restart: unless-stopped` | padrão de todos os serviços do kit |

Recusado da referência:

| O que | Por que não |
|---|---|
| **Comentar o serviço `pgsql`** | na referência há **um** projeto com **um** banco escolhido; no kit os três bancos convivem no mesmo arquivo distribuído. Comentar o Postgres tornaria o kit MySQL-only, e a escolha do instalador deixaria de ter efeito |
| **`mysql_test` e `mysql_dusk_test`** | o kit roda a suíte em SQLite `:memory:` e não usa Dusk (o `pest-plugin-browser` sobe o próprio servidor, no mesmo processo). Dois containers que nada consome. Registrado como premissa A4 no `00` |
| **`name: univercidade_corporativa` e os `container_name:` reescritos à mão** | é exatamente o problema que RQ-05 pede para resolver de forma automática. Ver ADR-03 |
| **`MYSQL_ROOT_PASSWORD: 'root'` literal** | o kit já tem a senha no `.env` (`DB_PASSWORD`), e o app precisa da **mesma** senha. Literal no YAML criaria duas fontes que divergem em silêncio |

### Alternativas Consideradas

1. **Copiar o arquivo de referência inteiro e adaptar** — descartada: ele já é o kit **depois** de
   uma customização (pgsql comentado, nome do projeto trocado, três MySQL). Copiar traria as
   escolhas daquele projeto como se fossem do kit.
2. **Ignorar a referência e desenhar do zero** — descartada: RQ-03 é cláusula, e a referência
   resolveu bem o healthcheck e o escape do dólar, que são os dois detalhes que mais custam a
   acertar sozinho.

### Consequências

- **Positivas**: o serviço nasce com o healthcheck certo de primeira, e o kit continua servindo
  os três bancos com o mesmo arquivo.
- **Negativas**: quem comparar os dois arquivos vai achar diferenças, e elas são intencionais.
  Esta ADR é a resposta.

### Referências

- `D:\PROJECTS\GSFERRO\FM2S\UNIVERSIDADE-CORPORATIVA\universidade-corporativa\docker-compose.yml`
  (lido nesta sessão)
- `00-requisito.md` → A4

---

## ADR-03: O nome do projeto chega por `COMPOSE_PROJECT_NAME` no `.env`, não por reescrita do YAML

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

RQ-05 quer o nome da aplicação onde hoje está `starter-kit`. Há dois mecanismos possíveis: o
instalador reescreve o `docker-compose.yml`, ou o instalador escreve uma chave no `.env` e o
Compose resolve o resto.

### Decisão

`COMPOSE_PROJECT_NAME` no `.env`, escrito pelo instalador. **Medido** — a chave no `.env` vence
o `name:` do arquivo:

```
.env: COMPOSE_PROJECT_NAME=minha_app
$ docker compose config | head -1
name: minha_app
```

O `name: starter-kit` do arquivo **fica**, como piso para quem não tem a chave.

Validação do valor, também medida:

```
2minha-app   -> name: 2minha-app                      (dígito inicial: aceito)
minha_app-1  -> name: minha_app-1                     (hífen e underscore: aceitos)
Minha-App    -> invalid project name: "must consist only of lowercase alphanumeric
                characters, hyphens, and underscores as well as start with a letter or number"
minha app    -> invalid project name (idem)
```

Por isso o nome **não** passa por `nomeDeBanco()`: aquele método troca hífen por underscore e
prefixa underscore em dígito inicial, e as duas correções existem por exigência de Postgres e
MySQL, não do Compose. `Str::slug($nome, '-')` já produz o formato aceito; só o resultado vazio
precisa de piso.

### Alternativas Consideradas

1. **Instalador reescreve `name:` e os onze `container_name:` no YAML** — descartada por um
   motivo estrutural: `docker-compose.yml` está em `KitUpdate::CAMINHOS_DO_KIT` (`:231`), e o
   comando aplica com `git checkout <tag> -- <caminho>` (`KitUpdate::aplicar()`, `:750-752`). Na
   primeira atualização o arquivo apareceria como modificado e a customização se perderia se a
   pessoa aceitasse — e aceitar é o caminho normal do comando. O `.env`, ao contrário, **nunca**
   é tocado pelo `kit:update`.
2. **Derivar do nome do diretório** (comportamento default do Compose sem `name:`) — descartada:
   o diretório costuma ser o nome do repositório, não o nome da aplicação, e RQ-05 fala do nome
   da aplicação.
3. **`name: ${COMPOSE_PROJECT_NAME:-starter-kit}` no YAML** — descartada como redundante: a
   variável já vence o `name:` por precedência nativa; interpolá-la ali seria escrever à mão o
   que o Compose faz sozinho.

### Consequências

- **Positivas**: sobrevive ao `kit:update`. Nenhum código novo além de uma linha de escrita e um
  método de três linhas. RQ-06 sai de graça: sem a chave, o nome é o de hoje.
- **Negativas**: num projeto **já instalado** a chave não chega pelo `kit:update`, porque
  `.env.example` e `.env.docker` não estão nas listas de caminhos do comando. O caminho é
  `php artisan kit:install --custom`, que reescreve nome e cor — e por isso o passo 4c leva a
  escrita também para `aplicarSemBanco()`. Fica documentado.
- **Riscos**: trocar o nome do projeto **depois** de já ter subido containers cria volumes novos,
  e os dados antigos ficam no volume do nome anterior. Vai para a doc, na seção de rollback.

### Referências

- `app/Console/Commands/KitUpdate.php:231` (o caminho na lista), `:750-752` (o `git checkout`)
- `app/Support/CustomizadorDaInstalacao.php:523-532` (`nomeDeBanco()`, e por que não serve)
- ADR-04

---

## ADR-04: Os onze `container_name:` saem, e o sufixo de índice é aceito

**Status**: Aceita
**Data**: 2026-09-02
**Refina**: ADR-03

### Contexto

`COMPOSE_PROJECT_NAME` renomeia o projeto e a rede, mas **não** renomeia container que tem
`container_name:` fixo — o valor explícito ganha. Com os onze `container_name: starter-kit-*` no
lugar, a chave da ADR-03 mudaria o nome do projeto e deixaria todos os containers com o prefixo
antigo. Metade da RQ-05 entregue.

### Decisão

Remover as onze linhas. Sem elas o Compose nomeia `<projeto>-<serviço>-<índice>` — recurso
nativo, medido:

```
$ docker compose up --dry-run mysql redis
 Container minha_app-mysql-1 Created
 Container minha_app-redis-1 Created
```

### Alternativas Consideradas

1. **`container_name: ${COMPOSE_PROJECT_NAME:-starter-kit}-pgsql`** nos onze — descartada:
   onze interpolações à mão para reproduzir o que o Compose faz sozinho, e `container_name`
   também impede escalar o serviço. É a rung 4 da escada do Ponytail (recurso nativo) contra
   onze linhas de código.
2. **Manter `container_name` e aceitar o prefixo antigo** — descartada: falha RQ-05.

### Consequências

- **Positivas**: saldo negativo em linhas. Nomes passam a acompanhar o projeto automaticamente,
  sem nada para manter.
- **Negativas**: o nome ganha o sufixo `-1`, **inclusive em quem não customizou nada** —
  `starter-kit-pgsql` vira `starter-kit-pgsql-1`. Isso é um desvio literal de RQ-06 ("só quando
  houver customização"), embora não seja o nome da aplicação entrando onde não devia: é o índice
  de réplica do Compose. Declarado aqui, no `01` (tabela de impacto) e no `CHANGELOG`.
- **Riscos**: quem tem script ou atalho com `docker logs starter-kit-app` precisa ajustar.
  Varredura feita no repositório: os onze literais aparecem **só** no próprio
  `docker-compose.yml` — nenhum teste, doc, README ou código PHP os referencia.

### Referências

- `docker-compose.yml` — onze ocorrências de `container_name:` (contagem conferida)
- ADR-03

---

## ADR-05: Usuário `root` com senha no `.env`, sem `MYSQL_USER`

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O instalador grava, para MySQL, `DB_USERNAME=root` e `DB_PASSWORD` **vazio**
(`app/Support/CustomizadorDaInstalacao.php:505-511`). O docblock justifica pela premissa que esta
entrega remove: *"o kit não sobe container MySQL, então o servidor é o de quem instalou"*
(`:468-474`). Com container, esses valores têm de casar com o que a imagem aceita.

### Decisão

O serviço declara `MYSQL_ROOT_PASSWORD: '${DB_PASSWORD:-secret}'` e **não** declara `MYSQL_USER`.
O instalador passa a gravar `DB_PASSWORD=secret` no ramo MySQL, espelhando o que o ramo `pgsql`
já faz. `DB_USERNAME` continua `root`.

Duas medições na imagem real sustentam cada metade:

```
$ docker run --rm -e MYSQL_DATABASE=teste mysql:8.0
    You need to specify one of the following as an environment variable:
    - MYSQL_ROOT_PASSWORD
    - MYSQL_ALLOW_EMPTY_PASSWORD
    - MYSQL_RANDOM_ROOT_PASSWORD

$ docker run --rm -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_USER=root -e MYSQL_PASSWORD=secret mysql:8.0
    [ERROR] [Entrypoint]: MYSQL_USER="root", MYSQL_USER and MYSQL_PASSWORD are for configuring
    a regular user and cannot be used for the root user
```

A primeira diz que senha vazia não é opção sem uma flag explícita. A segunda diz que não dá para
criar `root` por `MYSQL_USER` — então, mantido `root` como usuário, o único caminho é
`MYSQL_ROOT_PASSWORD`.

### Alternativas Consideradas

1. **Manter `DB_PASSWORD` vazio e usar `MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'`** — descartada: a
   instalação nasceria com um banco de root **sem senha** numa porta publicada em `localhost`.
   O `pgsql` do kit publica `secret`, que é fraco mas não é vazio.
2. **Deixar um fallback com valor no compose (`${DB_PASSWORD:-secret}`)** — descartada, e é a
   armadilha mais perigosa das três: o `:-` substitui quando a variável está **ausente ou
   vazia**, então `DB_PASSWORD=` no `.env` produziria container com senha `secret` e aplicação
   conectando com senha vazia. O sintoma é "Access denied" sem nenhuma pista de que as duas
   pontas discordam.

   **Esta alternativa esteve na primeira implementação**, e a derivação dos casos de teste a
   pegou (pergunta A9): ela não atinge só quem instala do zero — atinge **todo projeto MySQL
   instalado antes desta versão**, que tem `DB_PASSWORD=` vazio no `.env` e recebe o compose
   novo pelo `kit:update`. O `01` classificara o risco como *baixo*; era o mais alto da entrega.

   A correção é `${DB_PASSWORD:-}`, com default **vazio**. Medido: com a variável vazia a imagem
   recusa inicializar e imprime *"Database is uninitialized and password option is not
   specified"*, seguido das três variáveis aceitas. Falha barulhenta na subida vence falha
   silenciosa na conexão. O `:-` fica só para não emitir aviso de variável ausente em quem
   instalou com SQLite e nunca definiu a chave — verificado: `docker compose config -q` sai
   limpo.
3. **Trocar o usuário para `starter_kit` (como no `pgsql`) e usar `MYSQL_USER`** — descartada por
   ser mudança maior sem ganho: `root` já é o que o instalador grava e o que o instalador do
   Laravel grava, e `MYSQL_USER` obrigaria a declarar **também** uma senha de root separada.

### Consequências

- **Positivas**: o container sobe e a aplicação conecta com o que o instalador escreveu, sem
  nenhum ajuste manual. Uma fonte só para a senha: o `.env`.
- **Negativas**: **muda comportamento** para instalações novas com MySQL — o `.env` nasce com
  `DB_PASSWORD=secret` em vez de vazio. Quem traz o próprio servidor MySQL com root sem senha
  precisa apagar o valor, exatamente como já faz hoje quem tem um Postgres externo com outra
  senha. Vai para o `CHANGELOG` em `### Alterado`, não em `### Adicionado`.
- **Riscos**: baixo. `.env` já gravado não é tocado por nada nesta entrega.

### Referências

- `app/Support/CustomizadorDaInstalacao.php:505-511` (o bloco MySQL), `:468-474` (o docblock que
  passa a mentir), `:405` (o rótulo da opção, que também passa a mentir)
- `00-requisito.md` → A5

---

## ADR-06: Uma variável (`DOCKER_DB_SERVICE`) para `DB_CONNECTION` e `DB_HOST` do profile `app`

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

Os cinco serviços do profile `app` fixam `DB_CONNECTION: pgsql` e `DB_HOST: pgsql` no bloco
`environment:`, que vence o `env_file: .env`. O `DB_HOST` **precisa** desse override: o `.env`
diz `127.0.0.1`, e dentro da rede do Compose o endereço é o nome do serviço. Com dois bancos
possíveis, os dois campos passam a depender da escolha.

### Decisão

Uma variável, usada nos dois campos:

```yaml
    environment:
      DB_CONNECTION: ${DOCKER_DB_SERVICE:-pgsql}
      DB_HOST: ${DOCKER_DB_SERVICE:-pgsql}
```

Funciona porque os nomes coincidem **por construção**: as conexões do `config/database.php`
chamam-se `pgsql` (`:87`) e `mysql` (`:47`), e os serviços do compose têm exatamente esses nomes.
A coincidência é deliberada e vai escrita em comentário no arquivo, junto da primeira ocorrência.

### Alternativas Consideradas

1. **Duas variáveis, `DOCKER_DB_CONNECTION` e `DOCKER_DB_HOST`** — descartada: dois botões que
   precisam concordar sempre, e nada garante que concordem. Um botão que pode ser mal ajustado é
   pior que nenhum.
2. **Apagar a linha `DB_CONNECTION` e deixar o `env_file` valer** — descartada por mudar
   comportamento de quem não pediu nada: hoje o profile `app` **força** Postgres, e sem a linha
   um projeto SQLite passaria a rodar containerizado sobre o arquivo SQLite assado na imagem —
   fora do volume, perdido a cada rebuild. Com `DOCKER_DB_SERVICE:-pgsql` o default é
   literalmente o comportamento de hoje.
3. **`DB_HOST: ${DB_CONNECTION:-pgsql}`, reusando a variável que já existe no `.env`** —
   descartada por ser esperta demais: usar o nome de uma conexão do Laravel como hostname
   funciona por acidente de nomenclatura e quebra silenciosamente com `DB_CONNECTION=sqlite`
   (o host viraria `sqlite`). Uma variável com nome próprio diz o que é.

### Consequências

- **Positivas**: o profile `app` passa a servir MySQL com duas linhas por serviço, e o default
  reproduz exatamente o comportamento atual — ninguém que não pediu é afetado.
- **Negativas**: quem usa MySQL **e** liga o profile `app` sobe um Postgres ocioso, porque
  `depends_on` puxa serviço do profile default. Custo aceito explicitamente pelo mantenedor
  (resposta A1 do `00`) e documentado nos dois idiomas. A alternativa — tirar o `pgsql` do
  `depends_on` — foi medida e produz aplicação de pé **sem banco nenhum** (ADR-01).
- **Riscos**: `DOCKER_DB_SERVICE` com um valor que não é nome de serviço do compose faz a
  aplicação não achar o banco. É variável de infra, documentada em `.env.docker`, não campo de
  tela.

### Referências

- `config/database.php:47` (`mysql`), `:87` (`pgsql`)
- ADR-01 (por que o `pgsql` continua no `depends_on`)
