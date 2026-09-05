# Plano de Ação — MySQL no Docker do kit, e o nome do projeto no lugar de `starter-kit`

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (não há; a wiki `feature/v1-enriquecimento-kit/cache-de-views-no-docker/`
  é **vizinha**, não ancestral: ela decidiu onde cada cache do Laravel roda no Docker, e o teste
  que ela deixou (`tests/Kit/CacheDeViewsNoDockerTest.php`) lê o mesmo arquivo que esta feature
  edita)
- **Motivo**: —
- **Toca infra compartilhada?**: **sim** — `docker-compose.yml` (lido por
  `tests/Kit/CacheDeViewsNoDockerTest.php`, 9 casos) e `CustomizadorDaInstalacao` (lido por
  `tests/Kit/CustomizadorDaInstalacaoTest.php` e `tests/Kit/TenancyNaInstalacaoTest.php`).
  **Regressão obrigatória** contra esses três arquivos, mesmo o tipo sendo "nova".

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | serviço MySQL no compose do kit | 1 | — |
| RQ-02 | subir MySQL não obriga a subir Postgres | 1 | `profiles: [mysql]` + serviço nomeado explicitamente. **Medido**, ver ADR-01 |
| RQ-03 | arquivo de referência é insumo do desenho | 1, 2 | o que foi herdado e o que foi recusado está na ADR-02 |
| RQ-04 | desenhado para o compose do kit | 1, 2 | ADR-01 e ADR-02 |
| RQ-05 | o nome da aplicação entra no lugar de `starter-kit` | 3, 4 | `COMPOSE_PROJECT_NAME` + remoção dos onze `container_name:` |
| RQ-06 | só quando houver customização | 3, 4 | default `starter-kit` no `.env.example` e no `name:` do YAML. **Desvio declarado**: o sufixo `-1` aparece mesmo sem customização — ADR-04 |

## Objetivo

O `kit:install` oferece três bancos, e um deles não tem container: quem responde **MySQL** recebe
um `.env` apontando para `127.0.0.1:3306` e nenhuma forma de subir esse servidor pelo kit — a
própria opção diz, hoje, *"traga o seu servidor (o kit não sobe container MySQL)"*. Esta entrega
adiciona o serviço `mysql` ao `docker-compose.yml`, de modo que a terceira opção do instalador
fique tão utilizável quanto as outras duas.

Na mesma passada, o literal `starter-kit` — que hoje nomeia o projeto Compose e prefixa os onze
containers — dá lugar ao nome que a pessoa escolheu na instalação, por `COMPOSE_PROJECT_NAME` no
`.env`. Quem instalou com Enter em tudo continua vendo `starter-kit`.

## Contexto

Três fatos do código atual sustentam o plano, e todos foram lidos, não supostos:

1. **A escolha de banco é exclusiva.** `CustomizadorDaInstalacao::perguntarBanco()`
   (`app/Support/CustomizadorDaInstalacao.php:395-418`) é um `select()` de opção única entre
   `sqlite`, `pgsql` e `mysql`.
2. **O compose do kit não tem MySQL.** O único banco é `pgsql`
   (`docker-compose.yml:25-44`), **sem profile** — sobe em todo `docker compose up -d`.
3. **O instalador grava root sem senha para MySQL.**
   `valoresDoBanco()` (`:505-511`) escreve `DB_USERNAME=root` e `DB_PASSWORD` vazio, e o docblock
   de `aplicarBanco()` (`:468-474`) justifica exatamente pela premissa que esta entrega remove:
   *"o kit não sobe container MySQL, então o servidor é o de quem instalou"*.

## Análise dos Arquivos Existentes

### `docker-compose.yml`

- `name: starter-kit` (linha 17) — nome do **projeto** Compose, não de um serviço.
- Onze `container_name:` — `pgsql`, `redis`, `llamacpp`, `embeddings`, `mailpit`, `app`, `nginx`,
  `queue`, `scheduler`, `reverb`, `pulse`.
- Cinco serviços do profile `app` (`app`, `queue`, `scheduler`, `reverb`, `pulse`) fixam
  `DB_CONNECTION: pgsql` e `DB_HOST: pgsql` no bloco `environment:`, que **vence** o
  `env_file: .env`.
- Os mesmos cinco declaram `depends_on` com `pgsql` em `condition: service_healthy`.
- `volumes:` no fim declara `pgsql-data`, `redis-data`, `llamacpp-cache`, `app-storage`.

### `app/Support/CustomizadorDaInstalacao.php`

- `aplicar()` é o caminho completo; `aplicarSemBanco()` é o `--custom` num projeto já instalado,
  e reescreve **só** nome e cor.
- Os dois passam por `SubstituicaoEmArquivo::definirNoEnv($env, 'APP_NAME', $nome)` — é ao lado
  dessa linha que entra a nova chave, nos dois.
- `nomeDeBanco()` (`:523-532`) **não serve** para o nome de projeto Compose: ele troca hífen por
  underscore e prefixa underscore quando começa com dígito, porque Postgres e MySQL exigem isso.
  O Compose aceita dígito inicial e aceita hífen — ver ADR-03.

### `app/Console/Commands/KitUpdate.php`

- `docker-compose.yml` está em `CAMINHOS_DO_KIT` (`:231`), então a mudança **chega** a quem já
  instalou, com diff mostrado e confirmação.
- `.env.example` e `.env.docker` **não** estão em nenhuma das duas listas. Consequência a
  documentar: num projeto já instalado a chave `COMPOSE_PROJECT_NAME` não chega pelo
  `kit:update` — chega rodando `php artisan kit:install --custom`, que reescreve nome e cor.

## Autorização

Sem policy, gate, middleware ou guard. A feature toca arquivo de infra e o instalador de linha
de comando.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** Nada nesta entrega é tela: o compose é arquivo de infra, e a escrita
da chave acontece dentro do `kit:install`, que é comando. Não há `05-casos-de-teste-browser.md`,
e o motivo fica registrado no `04`.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `COMPOSE_PROJECT_NAME` | `starter-kit` | Nome do projeto Compose. Prefixa todo container e toda rede. Lido **pelo Compose**, não pelo Laravel. Escrito pelo instalador a partir do nome do projeto |
| `DOCKER_DB_SERVICE` | `pgsql` | Qual serviço de banco do compose a aplicação containerizada usa. Vale só para o profile `app`. **Não** é escrita pelo instalador — fica documentada e comentada no `.env.docker`, ver a auditoria Ponytail no `03` |
| `FORWARD_DB_PORT` | `3306` no serviço `mysql` (segue `5432` no `pgsql`) | Já existe; ganha um segundo consumidor |
| `DB_PASSWORD` | passa a ser `secret` quando o banco é MySQL (era vazio) | Consumido pelo `MYSQL_ROOT_PASSWORD` do container — ver ADR-05 |

> `DOCKER_DB_SERVICE` é **uma** variável para dois campos porque os nomes coincidem por
> construção: as conexões do `config/database.php` chamam-se `pgsql` (`:87`) e `mysql` (`:47`),
> e os serviços do compose têm exatamente esses nomes. A coincidência é deliberada e vai escrita
> em comentário no arquivo — ver ADR-06.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum job novo. Os serviços `queue` e `scheduler` do profile `app` são **editados** (passo 2),
não criados.

## Impacto em Features Existentes

| Onde | O que muda | Risco |
|---|---|---|
| `tests/Kit/CacheDeViewsNoDockerTest.php` | lê o `docker-compose.yml` inteiro; 9 casos, alguns com asserção de **ausência** filtrando comentário | alto se os comentários novos citarem os comandos de cache proibidos — **não citar** |
| `docker compose up -d` (sem argumento) | inalterado: sobe `pgsql` + `redis`, exatamente como hoje | nenhum |
| Nome dos containers | passa de `starter-kit-pgsql` para `starter-kit-pgsql-1` mesmo **sem** customização | baixo, cosmético; nada no repo referencia container por nome (varredura feita: os onze literais só aparecem no próprio `docker-compose.yml`) |
| Instalação MySQL existente | `DB_PASSWORD` passa a nascer `secret` em instalações **novas**; `.env` já gravado não é tocado | baixo |
| `kit:update` num projeto instalado | oferece o `docker-compose.yml` novo com diff e confirmação | nenhum; é o fluxo normal do comando |

## Rollback

- Sem migration, sem estado em banco: o rollback é `git revert` do commit.
- No lado do usuário: `docker compose down -v` remove o volume `mysql-data`; remover a linha
  `COMPOSE_PROJECT_NAME` do `.env` devolve o nome do projeto ao `name:` do arquivo.
- **Cuidado com o volume ao renomear**: trocar `COMPOSE_PROJECT_NAME` depois de já ter subido
  containers cria volumes **novos**, e os dados antigos ficam no volume do nome anterior. Vai
  para a documentação.

## Dependências

Nenhuma dependência nova de Composer ou NPM. A imagem `mysql:8.0` é baixada pelo Docker no
primeiro `up`.

## Riscos

| Risco | Mitigação |
|---|---|
| Senha vazia no `.env` com fallback `secret` no compose produz container com `secret` e app com vazio — "Access denied" sem pista | passo 4 muda o instalador para gravar `secret`; caso de teste cobre o par |
| `--profile app` de um usuário MySQL sobe um Postgres ocioso (via `depends_on`) | custo aceito na resposta A1 do `00`; documentado nos dois idiomas |
| Comentário novo no compose derruba asserção de ausência do teste vizinho | não citar os comandos de cache proibidos; rodar aquele arquivo antes de fechar |
| `Str::slug()` de um nome só com símbolos devolve string vazia, e o Compose recusa nome vazio | fallback para `starter-kit` no passo 3 |

## Channel de Log da Feature

### Verificação de Channel Existente

`grep -n "Log::channel" app/Support/CustomizadorDaInstalacao.php` mostra o channel
`configuracoes` em `propagarParaOSettings()`. Os dois métodos que esta feature edita —
`aplicar()` e `aplicarSemBanco()` — logam no channel **default** (`Log::info` e `Log::debug`),
já no formato `[Classe@Método]`.

### Decisão

**Nenhum channel novo.** A feature acrescenta uma chave de `.env` a dois métodos que já logam a
customização inteira; um arquivo de log próprio para "escrevi uma linha a mais no `.env`" é
cerimônia, e fragmentaria o registro da mesma operação em dois lugares. O que muda é o
**contexto** dos logs existentes, que ganha o valor gravado:

```php
Log::info(
    '[CustomizadorDaInstalacao@aplicar] Customização aplicada | banco: '.$banco,
    [
        'banco'           => $banco,
        'cor'             => $cor,
        'tenancy'         => (bool) ($respostas['tenancy'] ?? false),
        'admin_email'     => $email,
        'compose_project' => $projeto,   // NOVO
    ],
);
```

E em `aplicarSemBanco()`, o log de debug existente ganha o mesmo campo no contexto:

```php
Log::debug(
    '[CustomizadorDaInstalacao@aplicarSemBanco] Nome e cor reescritos | cor: '
    .($respostas['cor'] === '' ? 'padrao' : $respostas['cor']),
    ['compose_project' => $projeto],
);
```

## Estrutura de Implementação

### 1. O serviço `mysql` no `docker-compose.yml`

> Skills: `ponytail`

- **Path**: `docker-compose.yml`
- Entra **depois** do `pgsql` e **antes** do `redis`, na seção "Infra base", com comentário
  explicando por que é o único banco com profile.
- `profiles: [mysql]` — **não** entra em `full`: `--profile full` significa "infra completa", e
  incluir os dois bancos ali subiria Postgres e MySQL na mesma stack.
- Bloco:

```yaml
  # O único banco com profile, e o motivo é a exclusividade da escolha: `kit:install`
  # pergunta UM banco. Sem profile, `docker compose up -d` subiria Postgres e MySQL
  # juntos em toda instalação. Com profile, quem escolheu MySQL nomeia os serviços:
  #
  #   docker compose up -d mysql redis
  #
  # Nomear um serviço que tem profile liga o profile dele e restringe a subida ao que
  # foi nomeado — o `pgsql`, que é do profile default, NÃO sobe. Ver ADR-01.
  #
  # `MYSQL_USER` não aparece de propósito: a imagem recusa `MYSQL_USER=root`, e o
  # instalador grava `root` como usuário. Ver ADR-05.
  mysql:
    image: mysql:8.0
    restart: unless-stopped
    profiles: [mysql]
    ports:
      - '${FORWARD_DB_PORT:-3306}:3306'
    environment:
      MYSQL_DATABASE: '${DB_DATABASE:-starter_kit}'
      MYSQL_ROOT_PASSWORD: '${DB_PASSWORD:-secret}'
    volumes:
      - 'mysql-data:/var/lib/mysql'
    healthcheck:
      # O dólar dobrado escapa a interpolação do Compose: a variável é lida DENTRO
      # do container, onde o entrypoint da imagem já a definiu.
      test: ['CMD-SHELL', 'mysqladmin ping -h localhost -u root -p"$$MYSQL_ROOT_PASSWORD" --silent']
      interval: 10s
      timeout: 5s
      retries: 5
```

- `volumes:` no fim do arquivo ganha `mysql-data:`, ao lado de `pgsql-data:`.
- **Logs**: nenhum — arquivo de configuração.

### 2. O profile `app` deixa de fixar o Postgres

> Skills: `ponytail`

- **Path**: `docker-compose.yml`
- Nos **cinco** serviços do profile `app` (`app`, `queue`, `scheduler`, `reverb`, `pulse`):

```yaml
    environment:
      DB_CONNECTION: ${DOCKER_DB_SERVICE:-pgsql}
      DB_HOST: ${DOCKER_DB_SERVICE:-pgsql}
```

- Um comentário **único**, no primeiro deles (`app`), explica a variável e a coincidência de
  nomes; os outros quatro só repetem as duas linhas.
- `depends_on` dos cinco ganha o MySQL como dependência **opcional**, ao lado do Postgres:

```yaml
    depends_on:
      pgsql:
        condition: service_healthy
      mysql:
        condition: service_healthy
        required: false   # fora da subida quando o profile `mysql` não está ligado
      redis:
        condition: service_healthy
```

- **O que NÃO fazer aqui**: tirar o `pgsql` do `depends_on`. Medido: com os dois bancos
  opcionais e nenhum no profile default, `--profile app up -d` sobe a aplicação **sem banco
  nenhum**, sem erro. Ver ADR-01.
- **Consequência aceita e documentada**: quem usa MySQL e liga o profile `app` sobe um Postgres
  ocioso, porque `depends_on` puxa serviço de profile default. É a resposta A1 do `00`.
- **Logs**: nenhum.

### 3. `COMPOSE_PROJECT_NAME` substitui os onze `container_name:`

> Skills: `ponytail`

- **Path**: `docker-compose.yml`
- **Remover as onze linhas `container_name:`.** Sem elas o Compose nomeia
  `<projeto>-<serviço>-<índice>`, e o projeto vem do `COMPOSE_PROJECT_NAME` do `.env` — que
  vence o `name:` do arquivo (medido, ADR-03).
- **Manter `name: starter-kit`** como piso: é o que vale quando ninguém definiu a chave.
- Um comentário no topo, junto do `name:`, diz de onde vem o prefixo e cita o `.env`.
- **Path**: `.env.example` — a chave nasce com o valor de hoje, ao lado de `APP_NAME`:

```
COMPOSE_PROJECT_NAME=starter-kit
```

- **Logs**: nenhum.

### 4. O instalador escreve as duas chaves novas

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/CustomizadorDaInstalacao.php`
- **4a.** Método novo, privado, ao lado de `nomeDeBanco()`:

```php
/**
 * Nome de projeto Compose a partir do nome do projeto.
 *
 * Não reusa `nomeDeBanco()` porque as regras são outras, e as duas foram medidas: o
 * Compose recusa maiúscula e espaço ("must consist only of lowercase alphanumeric
 * characters, hyphens, and underscores as well as start with a letter or number") e
 * ACEITA hífen e dígito inicial — os dois casos que `nomeDeBanco()` existe para
 * consertar. `Str::slug` já entrega o formato aceito; só o vazio precisa de piso,
 * porque nome de projeto vazio é erro duro do Compose.
 */
private function nomeDeProjetoDocker(string $nome): string
{
    return Str::slug($nome, '-') ?: 'starter-kit';
}
```

- **4b.** Em `aplicar()`, ao lado do `APP_NAME`:

```php
$projeto = $this->nomeDeProjetoDocker($nome);

SubstituicaoEmArquivo::definirNoEnv($env, 'APP_NAME', $nome);
SubstituicaoEmArquivo::definirNoEnv($env, 'COMPOSE_PROJECT_NAME', $projeto);
```

  `definirNoEnv()` é o método certo aqui por dois motivos, os dois conferidos: ele trata a chave
  **ausente** (anexa no fim, via o `$fallback` de `aplicar()`), que é o caso de um `.env` antigo;
  e ele grava o valor **entre aspas**, que o Compose remove ao ler — medido, `COMPOSE_PROJECT_NAME="minha-app"`
  produz `name: minha-app`. Se as aspas sobrevivessem, o nome seria inválido e o passo teria de
  montar a linha crua com `aplicar()`.

- **4c.** Em `aplicarSemBanco()`, o mesmo par — senão `kit:install --custom` renomeia a aplicação
  e deixa os containers com o nome antigo, que é a forma de erro que o docblock de
  `propagarParaOSettings()` já documenta para o settings.
- **4d.** `valoresDoBanco()`, ramo `mysql`: `DB_PASSWORD` passa de vazio para `secret`, e
  `DB_USERNAME` continua `root`.
- ~~**4e.** `valoresDoBanco()`, ramo `mysql`: acrescentar `DOCKER_DB_SERVICE`.~~ **CORTADO** pela
  auditoria Ponytail, e o motivo é de correção, não de tamanho: gravar a chave faria quem
  escolheu MySQL e rodasse `--profile app` **sem** `--profile mysql` apontar o `DB_HOST` para um
  container que não subiu — exatamente a degradação silenciosa que a ADR-01 recusou por medição.
  A chave passa a viver comentada no `.env.docker`, onde quem containeriza a aplicação já está
  copiando variáveis.
- **4f.** O docblock de `aplicarBanco()` (`:468-474`) mente depois desta entrega: *"o kit não
  sobe container MySQL"*. Reescrever as duas frases finais.
- **4g.** O rótulo da opção MySQL em `perguntarBanco()` (`:405`) também mente:
  *"traga o seu servidor (o kit não sobe container MySQL)"*. Passa a dizer que há container.
- **Logs**: acrescentar `compose_project` ao contexto dos dois logs existentes, como na seção
  "Channel de Log" acima. Nenhuma mensagem nova.

### 5. Documentação e changelog

> Skills: —

- **`.env.docker`**: bloco novo de MySQL ao lado do de Postgres, com as chaves `DB_*` do driver
  e a linha `DOCKER_DB_SERVICE=mysql`, com o que ela faz. Mais `COMPOSE_PROJECT_NAME` no topo.
- **`README.md` e `README.en.md`**: a tabela de serviços do Docker ganha a linha do MySQL com o
  profile; o bloco de comandos ganha `docker compose up -d mysql redis`.
- **`docs/pt/comecar/instalacao-avancada.md`** e o par em `docs/en/`: o parágrafo do banco passa
  a dizer que MySQL tem container, com o comando; mais a nota do volume ao renomear o projeto e
  a nota de que projeto já instalado recebe a chave por `kit:install --custom`.
- **`CHANGELOG.md`** → `### Adicionado` (o container MySQL; o nome do projeto no Compose) **e**
  `### Alterado` (o profile `app` deixa de fixar Postgres; `DB_PASSWORD` do MySQL nasce `secret`;
  containers ganham o sufixo de índice).
- **Conferir antes** se outra branch editou os mesmos arquivos de doc nesta rodada — as quatro
  branches anteriores desta sequência mexeram em `CHANGELOG.md` e deram conflito.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
>
> A escada, aplicada nominalmente a esta feature:
> 1. **Recurso nativo antes de código**: o prefixo de container derivado do nome do projeto é
>    comportamento padrão do Compose. Nenhum script renomeia nada — onze linhas **saem**.
> 2. **Reuso antes de novo**: `FORWARD_DB_PORT`, `DB_DATABASE` e `DB_PASSWORD` já existem e já
>    são lidos pelo compose; o serviço MySQL os consome sem inventar chave.
> 3. **Uma variável, não duas**: `DOCKER_DB_SERVICE` cobre `DB_CONNECTION` e `DB_HOST` porque os
>    nomes coincidem por construção.
> 4. **Deleção sobre adição**: o saldo do passo 3 é negativo em linhas.
>
> Atalhos deliberados marcados com comentário `ponytail:`.
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> Arquivos wiki (00-06) são boundary do Caveman — prosa normal. Código e commits também.

## Mapeamentos

Resposta do instalador → o que é escrito no `.env`:

| Banco escolhido | `DB_CONNECTION` | `DB_USERNAME` | `DB_PASSWORD` | `DOCKER_DB_SERVICE` | Comando para subir |
|---|---|---|---|---|---|
| `sqlite` | `sqlite` | — | — | — (ausente) | não precisa de container |
| `pgsql` | `pgsql` | `starter_kit` | `secret` | — (ausente; default é `pgsql`) | `docker compose up -d` |
| `mysql` | `mysql` | `root` | `secret` **(era vazio)** | — (não escrita; ver `.env.docker`) | `docker compose up -d mysql redis` |

## Testes

> Ver `04-casos-de-teste.md`, derivado do `00-requisito.md` pela skill `feature-test-design`.
> **Sem `05-casos-de-teste-browser.md`**: a feature não tem superfície de UI.

Insumos que a derivação precisa saber, e que são do plano (paths e stack), não do comportamento:

- Suíte: `tests/Kit` (`TestCase` + `RefreshDatabase`, grupo `kit`).
- O vizinho a imitar para asserção sobre o compose é `tests/Kit/CacheDeViewsNoDockerTest.php`:
  asserção de **presença** sobre o texto cru, asserção de **ausência** sobre o arquivo **sem
  comentário** (`.ai/rules/testes.md`). A ausência importa aqui: os onze `container_name:` saem,
  e o comentário novo vai mencionar a palavra.
- O vizinho a imitar para o instalador é `tests/Kit/CustomizadorDaInstalacaoTest.php`: todo caso
  escreve num diretório **temporário**, nunca no `base_path()`; os helpers `envDoTeste()` e
  `valorNoEnv()` já existem **naquele arquivo** — se um caso novo em outro arquivo precisar
  deles, eles vão para `tests/Pest.php` (`.ai/rules/testes.md`).
- **Não** usar `Symfony\Component\Yaml`: ele está na árvore só por `laravel/roster` (dev), então
  a asserção estrutural dependeria de uma dependência transitiva de ferramenta.
- Verificação com Docker de verdade (`docker compose config -q`, `up -d mysql redis`) é
  **manual**, não entra na suíte: exige daemon, e o CI do kit não tem.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse`
- [ ] `php artisan test --compact tests/Kit/CacheDeViewsNoDockerTest.php` (regressão do vizinho)
- [ ] `php artisan test --compact tests/Kit/CustomizadorDaInstalacaoTest.php tests/Kit/TenancyNaInstalacaoTest.php`
- [ ] `php artisan test --compact tests/Kit/KitInfoTest.php tests/Kit/KitUpdateTest.php`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php` (as páginas de doc editadas)
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] À mão, com Docker: `docker compose config -q`; `docker compose up -d mysql redis` sobe
      **dois** containers e nenhum Postgres; `docker compose ps` mostra o prefixo do
      `COMPOSE_PROJECT_NAME`; `php artisan migrate` conecta no container
- [ ] `git commit`

## Commits

- `✨ feat(docker): container MySQL e o nome do projeto no lugar de starter-kit`
- `📝 docs(wiki): wiki da feature mysql-no-docker`
