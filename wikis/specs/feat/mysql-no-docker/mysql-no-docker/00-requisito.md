# Requisito — MySQL no Docker do kit, e o nome do projeto no lugar de `starter-kit`

## Fonte

- **Origem**: mensagem do mantenedor no chat, via `/feature-wiki`
- **Data**: 2026-09-02
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado verbatim abaixo. O insumo de referência é
  um arquivo real, lido nesta sessão (path na íntegra na tabela de cláusulas)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> no instalador, temos a opção de escolher o mysql, porem não temos ele no docker, então, acho que é valido adicionar:
> - veja como eu implemente aqui: "D:\PROJECTS\GSFERRO\FM2S\UNIVERSIDADE-CORPORATIVA\universidade-corporativa\docker-compose.yml"
> - pense em como adicionar no docker-composer do kit
> - o nome do services esta como "starter-kit", porem ali deveria entrar o nome da aplicação quando houver uma customização

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O `docker-compose.yml` do kit passa a oferecer um serviço MySQL | "temos a opção de escolher o mysql, porem não temos ele no docker, então, acho que é valido adicionar" | funcional |
| RQ-02 | Subir o MySQL do kit **não** pode obrigar a subir o Postgres | "no instalador, temos a opção de escolher o mysql" — a escolha do instalador é exclusiva: quem responde `mysql` não quer `pgsql` | funcional |
| RQ-03 | O arquivo de referência do mantenedor é insumo obrigatório do desenho | "veja como eu implemente aqui: \"D:\PROJECTS\GSFERRO\FM2S\UNIVERSIDADE-CORPORATIVA\universidade-corporativa\docker-compose.yml\"" | restrição |
| RQ-04 | A adição é **desenhada** para o compose do kit, não transplantada do arquivo de referência | "pense em como adicionar no docker-composer do kit" | restrição |
| RQ-05 | O `starter-kit` fixo dá lugar ao nome da aplicação | "o nome do services esta como \"starter-kit\", porem ali deveria entrar o nome da aplicação" | funcional |
| RQ-06 | O nome da aplicação só entra **quando houver customização**; sem ela o comportamento de hoje continua | "quando houver uma customização" | funcional |

**Por que RQ-05 e RQ-06 são separadas**: são falsificáveis em separado, e a violação de cada uma
é observável. Uma implementação pode acertar RQ-05 (passa a usar o nome da aplicação) e errar
RQ-06 (passa a usar sempre, quebrando quem instalou com Enter em tudo) — ou o contrário. Fundidas,
a matriz de rastreabilidade marcaria ✅ com metade entregue.

**Por que RQ-02 é cláusula e não premissa**: ela não está escrita como frase, mas está escrita
como **contexto** na primeira linha — a origem do pedido é a escolha de banco do instalador, que
é `select()` de opção única (`CustomizadorDaInstalacao::perguntarBanco()`, `app/Support/CustomizadorDaInstalacao.php:395-418`).
Uma entrega que adicione MySQL e obrigue o Postgres a subir junto atende a letra de RQ-01 e falha
o motivo do pedido. É exatamente a omissão silenciosa que a decomposição existe para impedir.

## Ambiguidades e Perguntas Abertas

### Respondidas pelo mantenedor nesta sessão

- **A1 — o profile `app` (aplicação containerizada) precisa funcionar com MySQL?**
  - **Respondido**: sim, parametrizar. `DB_CONNECTION` e `DB_HOST` viram variável nos cinco
    serviços do profile `app` (`app`, `queue`, `scheduler`, `reverb`, `pulse`).
  - **Custo aceito, medido**: o `depends_on: pgsql` continua subindo um Postgres ocioso para
    quem usa MySQL. Fica documentado, não silencioso.
- **A2 — por qual mecanismo o nome da aplicação chega ao Compose?**
  - **Respondido**: `COMPOSE_PROJECT_NAME` no `.env`, escrito pelo instalador, e os onze
    `container_name:` saem do arquivo para o Compose derivar o prefixo (recurso nativo).
  - Sobrevive ao `kit:update`, porque o `.env` nunca é sobrescrito por ele.

### Premissas assumidas (não bloqueiam nenhum passo)

- **A3 — "o nome do services" refere-se a quê, exatamente?**
  - Os serviços do compose do kit chamam-se `pgsql`, `redis`, `app`, `nginx`, `queue`,
    `scheduler`, `reverb`, `pulse`, `llamacpp`, `llamacpp-embeddings`, `mailpit`. **Nenhum**
    se chama `starter-kit`. O literal `starter-kit` aparece em dois lugares: o `name:` de topo
    (`docker-compose.yml:17`), que é o nome do **projeto** Compose, e onze `container_name:`.
  - **Assumido**: a cláusula fala do prefixo visível dos containers e do nome do projeto — é
    o que a pessoa vê em `docker compose ps` e em `docker logs`.
  - **Se negado**: RQ-05 e RQ-06 mudam de alvo, e o passo 3 do plano é refeito. Os passos 1, 2
    e 4 (o MySQL) não são afetados.
- **A4 — os três containers MySQL do arquivo de referência entram?**
  - O arquivo de referência tem `mysql`, `mysql_test` e `mysql_dusk_test`.
  - **Assumido**: só `mysql`. O kit roda a suíte em SQLite `:memory:` (`phpunit.xml`) e não usa
    Dusk — o `pest-plugin-browser` sobe o próprio servidor no mesmo processo. Dois containers de
    banco de teste seriam infra que nada no kit consome.
  - **Se negado**: entram dois serviços a mais, com `FORWARD_DB_TEST_PORT` e
    `FORWARD_DB_DUSK_TEST_PORT`, e o `phpunit.xml` passa a ter conexão de teste — mudança bem
    maior do que RQ-01.
- **A5 — o instalador passa a gravar senha para MySQL?**
  - Hoje ele grava `DB_USERNAME=root` e `DB_PASSWORD=` **vazio**
    (`CustomizadorDaInstalacao::valoresDoBanco()`, `app/Support/CustomizadorDaInstalacao.php:505-511`),
    e o docblock justifica: *"o kit não sobe container MySQL, então o servidor é o de quem
    instalou"* (`:468-474`). Esta entrega remove essa premissa.
  - **Assumido**: sim, passa a gravar `DB_PASSWORD=secret`, espelhando o que o ramo `pgsql` já
    faz. Medido na imagem real: `mysql:8.0` **recusa inicializar** sem senha de root
    (*"You need to specify one of the following as an environment variable: MYSQL_ROOT_PASSWORD,
    MYSQL_ALLOW_EMPTY_PASSWORD, MYSQL_RANDOM_ROOT_PASSWORD"*), e senha vazia no `.env` com
    `${DB_PASSWORD:-secret}` no compose produziria **container com `secret` e app com vazio** —
    "Access denied" sem nenhuma pista.
  - **Se negado**: o serviço passa a usar `MYSQL_ALLOW_EMPTY_PASSWORD`, e a instalação nasce com
    banco de root sem senha numa porta publicada em `localhost`.
  - **Quem traz o próprio servidor** edita o `.env`, exatamente como já faz hoje quem usa um
    Postgres externo.

### Devolvidas pela derivação dos casos de teste (A6..A11)

A `feature-test-design` derivou o `04` a partir **deste** arquivo, sem ler o plano nem o código,
e devolveu seis perguntas. O texto integral delas está em `## Perguntas para o 00-requisito.md`
do `04`; a triagem, com o que foi feito de cada uma, está em `03-progresso.md` → *Desvios do
Plano*. Em resumo:

| # | Assunto | Situação |
|---|---|---|
| A6 | a chave do serviço de banco sobreviveria a uma troca de banco | **resolvida antes de chegar** — a escrita já tinha sido cortada pela auditoria do plano |
| A7 | ligar só o profile `app` deixa a aplicação apontando para host inexistente | **mitigada** — o comando correto está em três lugares; não eliminada |
| A8 | a medição do nome do projeto não cobria a forma **com aspas** | **medida e fechada** (sonda 8) |
| A9 | projeto MySQL já instalado tem senha vazia e receberia container com senha | **virou correção de código** — o default passou a ser vazio, e a imagem recusa subir com mensagem explícita |
| A10 | `depends_on.required` exige Compose recente, sem versão mínima declarada | **aceita** — o recurso já era usado no kit antes desta feature |
| A11 | `FORWARD_DB_PORT` com dois consumidores | **virou correção de código** — o MySQL ganhou chave própria |

Duas delas (A9 e A11) eram **defeitos**, não ambiguidades de redação, e as duas foram
confirmadas por medição antes de virarem código.

## Fora de Escopo (declarado)

- **Trocar o banco default do kit.** SQLite continua o default do `select()`, e Postgres continua
  o recomendado (é o único com `pgvector`, exigido pelas funções de IA local).
- **Containers de banco de teste** (`mysql_test`, `mysql_dusk_test` do arquivo de referência) — ver A4.
- **`MariaDB` como imagem própria.** A opção do instalador é rotulada "MySQL / MariaDB"; o
  serviço entrega `mysql:8.0`. Quem quer MariaDB troca a linha `image:`.
- **Migrar o `pgsql` para fora do profile default.** Medido: os dois bancos em profile fazem
  `docker compose up -d` puro deixar de subir banco (quebra comando documentado no README) e
  `--profile app` subir a aplicação **sem banco nenhum**, sem erro. Recusado por medição.
- **Renomear os serviços** (`pgsql`, `redis`, `app`…). O que ganha o nome da aplicação é o
  projeto e o prefixo dos containers, não os serviços — ver A3.
