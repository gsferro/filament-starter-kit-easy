# Casos de Teste — MySQL no Docker do kit, e o nome do projeto no lugar de `starter-kit`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação —
> ela não existe ainda. O código lido foi o **atual**, e só para herdar convenção de teste
> (helpers do `tests/Pest.php`, os dois vizinhos, a forma das asserções sobre o compose) e para
> saber os nomes que já existem no arquivo.

## Perfil de Derivação

| Área | O que é | P | I | P×I | Perfil |
|---|---|---|---|---|---|
| **A — o serviço MySQL no compose** | serviço novo numa infra que já existe, imagem externa, healthcheck, volume nomeado | 3 | 3 | **9** | completo |
| **B — exclusividade de profile e o profile `app`** | semântica de `profiles:`/`depends_on` do Compose, cinco serviços editados, degradação silenciosa possível | 3 | 3 | **9** | completo |
| **C — o nome do projeto Compose** | um método novo de três linhas, texto livre digitado por humano, escrito num arquivo de configuração | 3 | 2 | **6** | padrão |
| **D — a escrita das chaves pelo instalador** | dois caminhos de escrita (`aplicar()` e `aplicarSemBanco()`), chave nova assimétrica entre os ramos de banco | 3 | 3 | **9** | completo |
| **E — documentação (README, `docs/`, `CHANGELOG`)** | prosa, reversível | 1 | 1 | **1** | mínimo |

**Justificativa do impacto 3 em A, B e D**: as três podem produzir *degradação silenciosa*, que
é o modo de falha que este kit recusa por padrão (é a razão do `&&` no `command:` do serviço
`app`, e a razão pela qual a ADR-01 recusou duas alternativas medidas). Em A, esquecer a
declaração de topo do volume `mysql-data` faz **todo** comando `docker compose` falhar,
inclusive o de quem usa Postgres. Em B, a aplicação containerizada pode subir apontando para um
host que não existe. Em D, a senha vazia com fallback no compose produz "Access denied" sem
nenhuma pista — está descrito na alternativa 2 da ADR-05.

**Justificativa do impacto 2 em C**: nome de projeto inválido é erro **duro** do Compose, não
silencioso, e o conserto é editar uma linha do `.env`. Retrabalho manual, não perda.

- **Técnicas aplicadas**: EP (partição por atributo obrigatório e por classe de nome),
  normalização (caixa, espaço, acento, unicode, dígito inicial, vazio), tabela de decisão
  (qual banco está atrás de profile), rastreio de efeito (a chave gravada no `.env`),
  criação × edição × uso (instalação nova × `--custom` × reinstalação com troca de banco).
- **Técnica escalada acima do perfil da área**: a área **C** é `padrão`, e a regra **R7**
  (normalização do nome) recebeu partição **exaustiva por classe de caractere** com valores
  discriminantes, que é rigor de perfil `completo`. Motivo: é o único ponto da entrega em que
  texto digitado por uma pessoa vira identificador consumido por um programa externo que
  **recusa** o formato errado, e a implementação errada mais provável (reusar o `nomeDeBanco()`
  que já existe ao lado) acerta a maioria dos nomes e erra exatamente os que começam com dígito.
- **Cenários**: 23 · **Regras**: 11 · **Mutantes previstos**: 61 · **Sem matador**: 5 (todos
  declarados, com verificação manual e/ou pergunta vinculada)
- **Revisão adversarial**: 1 rodada, 21 achados, todos fechados — 4 cenários novos, 9 oráculos
  reescritos, 12 mutantes acrescentados. Ver `## Revisão Adversarial`.
- **Sem `05-casos-de-teste-browser.md`** — ver `## Sem CT-B`.

> **Teto de mutantes por regra**: o perfil `completo` prevê de 3 a 6. R1, R2, R3, R4 e R5
> estouram o teto porque os mutantes `M49`–`M61` vieram da **revisão adversarial** — achado
> medido, não enchimento, e a skill os isenta do teto explicitamente. Cada um está marcado com a
> origem na coluna do `#`.

### Divergências declaradas

| Instrução | O que venceu | Por quê |
|---|---|---|
| A skill sugere `pest --parallel --tia` como comando padrão de fechamento | **`.ai/rules/testes-browser.md`** (`vendor/bin/pest --parallel --group=kit`) | a rule mediu: sem **PCOV** no ambiente o `--tia` não termina (abortado após 35 min, em série, com Xdebug). Rule é medição local; skill é generalização |
| A skill manda asserção de **presença** sobre o texto cru do arquivo | **reforçada** para regex ancorada em coluna (`/^  mysql:$/m`) nesta feature | a palavra `mysql` vai aparecer em vários comentários do compose, e `toContain('profiles: [mysql]')` pode ficar verde por causa de um comentário. Linha começando por `#` nunca casa uma âncora de indentação YAML. Não é divergência da rule de **ausência** — essa continua filtrando comentário |
| A skill manda filtrar comentário em toda asserção de ausência | **exceção pontual em CT-19** | ali o comentário **é** o artefato sob teste (o docblock de `aplicarBanco()` afirma um fato que deixa de ser verdadeiro). A saída é não filtrar o arquivo inteiro, e sim recortar aquele docblock por `ReflectionMethod::getDocComment()`. A rule continua valendo para o resto do arquivo |

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | `docker-compose.yml` (serviço novo, onze linhas removidas, dez linhas de `environment:` reescritas, um volume de topo), `.env.example` (chave nova), `app/Support/CustomizadorDaInstalacao.php` (um método privado novo, quatro pontos de escrita, dois textos que passam a mentir), `README.md` / `README.en.md` / `docs/{pt,en}/comecar/instalacao-avancada.md`, `CHANGELOG.md`. **Sem migration, sem model, sem policy, sem rota, sem job** | CT-01 … CT-23 |
| **F**unction | declarar um serviço de banco utilizável; manter a escolha de banco exclusiva; parametrizar o banco da aplicação containerizada; derivar um nome de projeto Compose a partir de texto livre; gravar duas chaves novas no `.env` nos dois caminhos do instalador; parar de afirmar na tela que o kit não sobe MySQL | CT-01 … CT-23 |
| **D**ata | **entra**: o nome do projeto (texto livre digitado por uma pessoa) e a escolha de banco (uma de três). **sai**: linhas do `.env` (`COMPOSE_PROJECT_NAME`, a chave do serviço de banco, `DB_PASSWORD`) e o conteúdo do `docker-compose.yml`. **já existe**: um `.env` de projeto já instalado, cuja linha pode estar preenchida, comentada ou ausente — os três estados convivem no arquivo recém-copiado do `.env.example`. Cardinalidade relevante: **cinco** serviços no profile `app`, **onze** `container_name:`, **um** banco no profile default | CT-01, CT-03, CT-06, CT-08, CT-11, CT-12, CT-16, CT-17 |
| **I**nterfaces | `php artisan kit:install` (e `--force`), `php artisan kit:install --custom`, `php artisan kit:update`, e os comandos `docker compose` que uma pessoa digita. **Nenhuma rota HTTP, nenhuma tela, nenhum job, nenhum webhook** | CT-05, CT-08, CT-11 … CT-23 |
| **P**latform | Docker Engine e Docker Compose (a semântica de `profiles:`, a precedência de `COMPOSE_PROJECT_NAME` sobre `name:`, o campo `required:` de `depends_on`, o parser de `.env` do Compose); a imagem `mysql:8.0` (recusa subir sem senha de root, recusa `MYSQL_USER=root`); `Str::slug()` do Laravel. **O CI do kit não tem daemon Docker** — toda asserção sobre o comportamento real do Compose é manual, e está em `## Lacunas Declaradas` | CT-01 … CT-07 (como proxy), VM-01 … VM-04 |
| **O**perations | três usos reais e distintos: **instalação nova** (`kit:install`), **projeto já instalado** (`kit:install --custom`, que é o único caminho pelo qual a chave nova chega — `.env.example` não está nas listas do `kit:update`) e **reinstalação com troca de banco** (`kit:install --force`). Perfis: só quem instala o kit; não há papel de usuário envolvido | CT-11, CT-16, CT-17, CT-18 |
| **T**ime | **não se aplica** ao comportamento: nada nesta entrega depende de relógio, fuso, agendamento, expiração ou concorrência entre requisições. A única ordem que importa é a de **execução do instalador** (aplicar duas vezes, e aplicar depois de uma escolha anterior), e ela está coberta por R9 e R10 como *edição*, não como tempo. O `depends_on … service_healthy` é ordem de **boot de container**, verificável só com daemon (VM-01) | CT-16, CT-17 (ordem de execução) · VM-01 (ordem de boot) |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem no `00` | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — o kit passa a declarar um serviço MySQL utilizável e persistente | A (completo) | RQ-01, RQ-03, RQ-04 | EP por atributo obrigatório | CT-01 |
| **R2** — o MySQL nasce saudável, e a aplicação containerizada só arranca depois | A + B (completo) | RQ-01, RQ-03, A1 | EP + rastreio de dependência (contagem sobre os cinco) | CT-02, CT-03 |
| **R3** — subir o MySQL não sobe o Postgres | B (completo) | RQ-02, A4 | tabela de decisão + invariante de cardinalidade | CT-04, CT-21, CT-05 |
| **R4** — o profile `app` deixa de fixar o Postgres, e o default é o comportamento de hoje | B (completo) | A1, RQ-02, *Fora de Escopo* ("trocar o banco default") | EP + contagem sobre os cinco + contrato entre artefatos | CT-06, CT-07, CT-20 |
| **R5** — uma fonte só para a senha e para o banco do MySQL, e nenhuma nasce vazia | A + D (completo) | A5, RQ-01 | EP (ausente ≠ vazio ≠ preenchido) + partição inválida isolada | CT-08, CT-09 |
| **R6** — o prefixo dos containers passa a vir do nome da aplicação | C + D (padrão) | RQ-05, A2, A3 | rastreio de efeito (a chave no `.env`) + asserção de ausência | CT-10, CT-11, CT-23 |
| **R7** — o nome gravado é sempre um nome de projeto que o Compose aceita | C (padrão, técnica escalada) | RQ-05, A2 | normalização + EP exaustiva por classe de caractere | CT-12 |
| **R8** — sem customização, o nome de hoje continua | C (padrão) | RQ-06 | valor literal do requisito + piso + caminho negativo | CT-13, CT-14, CT-15, CT-22 |
| **R9** — a escolha de banco continua exclusiva quando é **refeita** | D (completo) | RQ-02 no ponto de **edição** | criação × edição × uso + idempotência ancorada no `.env` | CT-16 `@premissa`, CT-17 |
| **R10** — o `--custom` também reescreve o nome do projeto Compose | D (completo) | RQ-05 no ponto de **edição**, A2 ("escrito pelo instalador") | criação × edição | CT-18 |
| **R11** — o instalador anuncia o container em vez de negá-lo | D (completo) | RQ-01 | presença do fato novo + ausência com recorte por Reflection | CT-19 |

### Rastreabilidade `RQ` → regra

| RQ | Regras que a atendem | Observação |
|---|---|---|
| RQ-01 | R1, R2, R5, R11 | "oferecer" inclui **ser utilizável**: sem volume declarado no topo o arquivo nem é válido; sem healthcheck saudável nada que dependa dele sobe; com senha divergente o app não conecta; e o rótulo que nega o container é a própria promessa desmentida na tela |
| RQ-02 | R3, R4, R9 | R3 é a exclusividade no arquivo; R4 é a exclusividade dentro do profile `app`; **R9 é a exclusividade na segunda escolha** — o ponto que o `00` não determina (pergunta A6) |
| RQ-03 | R1, R2 — **parcialmente** | a referência entra como insumo pelos itens que ela resolveu bem: a tag fixa, o volume e o **dólar dobrado** do healthcheck. Ver a ressalva abaixo |
| RQ-04 | R1 | "desenhado para o compose do kit" é falsificável de uma forma só e barata: o serviço tem de estar **no** `docker-compose.yml` do kit, não num arquivo de override ao lado — e tem de seguir a convenção dos serviços vizinhos (tag fixa, `restart: unless-stopped`, volume nomeado, healthcheck) |
| RQ-05 | R6, R7 | R6 é o mecanismo chegando ao `.env` e os onze nomes fixos saindo; R7 é o valor gravado ser aceitável |
| RQ-06 | R8 | o desvio do sufixo `-1` está declarado na ADR-04 e **não** é recusado aqui — nenhum cenário afirma sobre ele |

Nenhuma cláusula `RQ` ficou sem regra. Nenhuma regra ficou sem cenário.

> **Ressalva de falsificabilidade em RQ-03 e RQ-04**, trazida pela revisão adversarial e
> registrada aqui em vez de escondida: as duas são cláusulas **de processo** ("veja como eu
> implementei ali", "pense em como adicionar aqui"), e processo não deixa rastro observável.
> As asserções de R1 e R2 seriam **idênticas** com ou sem a leitura do arquivo de referência, e
> um transplante fiel que evitasse os atributos afirmados — trazendo `command:`, rede própria e
> o `MYSQL_DATABASE` da outra aplicação — passaria em CT-01 inteiro.
>
> O que esta derivação faz é o máximo que dá para fazer: converter RQ-03 nos **itens concretos**
> que a referência resolveu bem (tag fixa, volume, `$$` do healthcheck) e RQ-04 na **convenção
> dos vizinhos** (tag fixa, `restart`, volume nomeado, healthcheck, e estar no arquivo do kit).
> Cobertas só por esses itens, e **não** pela cláusula inteira. A parte de "foi pensado, não
> copiado" é auditável por revisão humana da ADR-02, não por teste.

---

## Fronteira com o Plano

O `01` e o `02` foram lidos para **paths, nomes e superfície**. O que segue é o que veio deles e
foi **recusado como oráculo**, para nenhum cenário virar teste do PRD.

| Item do plano | Recusado como oráculo porque | Destino |
|---|---|---|
| O nome do profile ser literalmente `mysql` | escolha de implementação (ADR-01). O que o requisito determina é que **exista** um gate de profile no MySQL e **não** no Postgres | detalhe do cenário (CT-04 afirma sobre a existência e o conteúdo do gate, não sobre a string escolhida) |
| O nome da variável ser `DOCKER_DB_SERVICE` | escolha de implementação (ADR-06). A resposta A1 do `00` diz "viram variável", sem nomear | detalhe. CT-06 afirma que **nenhum** dos cinco fixa o banco literalmente e que os dez campos vêm de interpolação — sem depender do nome |
| O nome do serviço ser `mysql` | escolha de implementação, *mas forçada*: a ADR-06 depende da coincidência entre nome de serviço e nome de conexão do `config/database.php` | detalhe adotado. CT-05 e CT-11 fecham o par (o valor gravado pelo instalador e o comando divulgado têm de casar o nome declarado no compose) |
| O nome do método `nomeDeProjetoDocker()` | escolha de implementação | detalhe. Nenhum cenário chama o método privado por nome; todos passam por `aplicar()` / `aplicarSemBanco()` |
| O texto novo do rótulo da opção MySQL e do docblock de `aplicarBanco()` | comportamento **visível** que o requisito não determina na redação | CT-19 afirma a **negação da negativa** (o texto não pode continuar dizendo que o kit não sobe container, porque RQ-01 torna a frase falsa) e, como âncora positiva, que o rótulo cita o **serviço do compose** — que é dado pelo próprio arquivo, não pelo plano. A escolha das palavras não é oráculo |
| O campo `compose_project` no contexto dos dois logs existentes | o `00` não pede log nenhum, e o formato é convenção da wiki | fora dos cenários. Regressão: `CustomizadorDaInstalacaoTest` já garante que a senha não vaza para o log, e acrescentar um campo ao contexto não move aquela asserção |
| Os textos de `README`, `docs/` e `CHANGELOG` | prosa; o `00` não determina | fora dos cenários, **com uma exceção**: CT-05, porque ali o comando documentado é a **única** forma de RQ-02 chegar a uma pessoa, e um comando errado (`--profile mysql up -d`) sobe os dois bancos |
| `.env.example` declarar `COMPOSE_PROJECT_NAME` | só o plano determina, e é *funcionalmente inócuo* (o `definirNoEnv()` anexa a chave se ela não existir) | CT-15 fica, com o oráculo declarado como **convenção enforçada do projeto** (chave nova de `.env` é documentada — o padrão está em `LoginSocialGoogleTest`), não como requisito. É o cenário de menor peso do conjunto |
| O default da interpolação ser `pgsql` | vem da ADR-06, **mas** é sustentado pelo `00`: a seção *Fora de Escopo* diz que trocar o banco default do kit está fora | **aceito** como oráculo (CT-07), com a fonte sendo o `00`, não a ADR |
| `COMPOSE_PROJECT_NAME` como mecanismo, e a saída dos onze `container_name:` | **não é do plano**: está no `00`, na resposta **A2** do mantenedor | aceito como oráculo (R6) |
| `DB_PASSWORD=secret` no ramo MySQL | **não é do plano**: está no `00`, na premissa **A5** respondida | aceito como oráculo (R5) |
| `DB_CONNECTION`/`DB_HOST` virarem variável nos cinco serviços do profile `app` | **não é do plano**: está no `00`, na resposta **A1** | aceito como oráculo (R4) |

### O que é *proxy*, e precisa ser dito

Sete cenários (CT-01 a CT-07) afirmam sobre o **texto** do `docker-compose.yml`, e o que o
requisito realmente pede é sobre o **comportamento do Compose** — quantos containers sobem, se
o container fica saudável, se a aplicação acha o banco. Nada disso é observável sem daemon, e o
CI do kit não tem.

A cadeia de oráculo que legitima cada proxy é sempre a mesma forma:
**cláusula do `00` + fato de plataforma medido** → invariante no arquivo. Exemplo, para RQ-02:

> RQ-02 (subir MySQL não obriga a subir Postgres) + fato medido *"serviço com `profiles:` não
> sobe em `docker compose up -d`, e nomear um serviço com profile liga o profile dele e
> restringe a subida ao que foi nomeado"* → **invariante**: exatamente um banco fora de profile.

O fato de plataforma é medição (registrada na ADR-01 e reproduzida nesta sessão com Docker
real), não escolha de plano — por isso ele pode entrar na cadeia. O que **não** entra é a
string `mysql` do nome do profile.

O resíduo que nenhum proxy cobre está em `## Lacunas Declaradas`, com o comando manual que o
prova e o mutante que só ele mata.

---

## Perguntas para o 00-requisito.md

> **Desvio declarado**: a skill manda replicar as perguntas em `00-requisito.md` →
> `## Ambiguidades`. O mantenedor pediu para revisar antes de editar o `00`, então elas ficam
> aqui, em bloco pronto para colagem no formato daquela seção. A pergunta **continua
> bloqueando** o que depende dela: **A6 bloqueia R9** (CT-16 está marcado `@premissa`) e
> **A7 bloqueia a lacuna VM-04**.

```markdown
### Perguntas abertas pela derivação dos casos de teste (2026-09-02)

- **A6 — a chave que aponta o serviço de banco do Compose acompanha a escolha quando ela é REFEITA?**
  (bloqueia o caso de teste da exclusividade na edição)
  O plano grava a chave só no ramo `mysql` de `valoresDoBanco()`. Os ramos `pgsql` e `sqlite`
  não a mencionam, e o `foreach` de `aplicarBanco()` só escreve as chaves que o array traz —
  então, num `kit:install --force` que troque MySQL por Postgres (ou por SQLite), a linha
  `DOCKER_DB_SERVICE=mysql` **fica no `.env`**. Consequência: `--profile app` passa a rodar a
  aplicação com `DB_CONNECTION=mysql` e `DB_HOST=mysql` sobre um `.env` de Postgres, ou sobre um
  projeto SQLite — que é exatamente o dano que a alternativa 2 da ADR-06 foi escrita para evitar
  ("um projeto SQLite passaria a rodar containerizado sobre o arquivo SQLite assado na imagem").
  A tabela de mapeamentos do plano registra `— (ausente)` para `pgsql` e `sqlite`, o que é
  verdade num `.env` recém-copiado e falso numa reinstalação.
  **Determinar**: os ramos `pgsql` e `sqlite` gravam `DOCKER_DB_SERVICE=pgsql` (a escolha passa
  a ser exclusiva também na segunda vez), ou a chave é removida do `.env` quando o banco
  escolhido não é MySQL, ou o desvio é aceito e documentado?

- **A7 — qual é o comando de quem usa MySQL E liga o profile `app`?**
  `docker compose --profile app up -d` **não** liga o profile `mysql`: o Postgres sobe (é
  profile default, e o `depends_on` o puxa), o MySQL **não** sobe, e a aplicação arranca
  apontando `DB_HOST=mysql` para um host que não existe. É a "aplicação de pé sem banco" que a
  ADR-01 mediu e usou como motivo para recusar a alternativa 1 — só que chegando pelo caminho
  que a ADR-06 aceitou. A resposta A1 diz "parametrizar", e nenhum lugar da wiki dá o comando.
  **Determinar**: o comando documentado passa a ser
  `docker compose --profile app --profile mysql up -d --build`? Ou `COMPOSE_PROFILES=app,mysql`
  no `.env` escrito pelo instalador (lembrando que o `--profile` da linha de comando
  **substitui** o `COMPOSE_PROFILES`, medição da ADR-01)? Ou o profile `app` com MySQL fica
  declarado como não suportado nesta versão?

- **A8 — o Compose remove as aspas do valor gravado pelo instalador?**
  As medições de A2/ADR-03 usaram `COMPOSE_PROJECT_NAME=minha_app`, **sem aspas**. O instalador
  grava por `SubstituicaoEmArquivo::definirNoEnv()`, que **sempre cita e escapa** o valor —
  o `.env` real vai conter `COMPOSE_PROJECT_NAME="loja-do-ferro"`. Se o parser de `.env` do
  Compose não remover as aspas, o nome do projeto contém `"` e cai na mesma recusa medida para
  maiúscula e espaço ("must consist only of lowercase alphanumeric characters, hyphens, and
  underscores…") — e aí **todo** comando `docker compose` do projeto falha, não só os de MySQL.
  **Determinar**: medir a forma citada com Docker real. Se as aspas não forem removidas, a
  escrita da chave não pode passar por `definirNoEnv()`.

- **A9 — o `kit:update` num projeto MySQL já instalado leva o container e deixa a senha para trás.**
  A tabela de impacto do plano diz que `DB_PASSWORD` nasce `secret` só em instalações **novas** e
  que o `.env` já gravado não é tocado — o que é verdade e, combinado com o compose novo, produz
  o par que a alternativa 2 da ADR-05 descreve como a armadilha mais perigosa das três:
  `.env` com `DB_PASSWORD=` vazio + `MYSQL_ROOT_PASSWORD: '${DB_PASSWORD:-secret}'` no compose
  = container com senha `secret`, aplicação conectando com senha vazia, "Access denied" sem
  pista. O risco está classificado como **baixo** no `01`; pela própria ADR-05 ele é o mais
  perigoso do conjunto.
  **Determinar**: o `kit:update` avisa quem tem `DB_CONNECTION=mysql` e `DB_PASSWORD` vazio? A
  nota entra nas duas páginas de doc e no `CHANGELOG`? Ou o fallback do compose deixa de ter
  default (`${DB_PASSWORD:?defina DB_PASSWORD}`), trocando o silêncio por erro na subida?

- **A10 — qual é a versão mínima de Docker Compose suportada pelo kit?**
  O `depends_on` com `required: false` é campo do compose-spec relativamente recente. Nenhum
  README ou página de doc do kit declara versão mínima, e as medições da wiki foram feitas em
  Compose v5.5.0. Numa versão que não conheça o campo, o arquivo é recusado na validação e
  **todo** comando `docker compose` do projeto para — inclusive o de quem usa Postgres.
  **Determinar**: declarar a versão mínima na doc, ou trocar `required: false` por um mecanismo
  que não dependa do campo.

- **A11 — `FORWARD_DB_PORT` passa a ter dois consumidores com defaults diferentes.**
  `pgsql` publica `${FORWARD_DB_PORT:-5432}:5432` e o `mysql` novo publica
  `${FORWARD_DB_PORT:-3306}:3306`. Quem define a variável para desviar o Postgres de uma porta
  ocupada muda, sem saber, também a porta publicada do MySQL. Pelo desenho da ADR-01 os dois
  bancos nunca sobem juntos, então o impacto é baixo — mas a chave deixa de ter significado
  único.
  **Determinar**: aceitar (e documentar em `.env.docker`), ou dar ao MySQL uma chave própria
  (`FORWARD_MYSQL_PORT`)?
```

---

## Setup Global

### Personas

**Não se aplica**: a feature não tem usuário autenticado, papel, painel nem autorização. O
`01` registra isso explicitamente ("Sem policy, gate, middleware ou guard"). O único ator é
**quem instala o kit**, e ele age por linha de comando.

Consequência para o checklist de taxonomia: as linhas de IDOR, autorização horizontal, matriz
papel × ação e mass assignment ficam dispensadas — com o motivo escrito, não em silêncio.

### Fixtures

- **Nenhuma factory.** Nenhum model é tocado.
- O arranjo do instalador é o do vizinho `tests/Kit/CustomizadorDaInstalacaoTest.php`: um
  diretório **temporário** (`sys_get_temp_dir()`), com uma cópia do `.env.example` do kit e
  cópias de `config/permission.php` e `config/filament-shield.php`. Nunca o `base_path()` — o
  customizador reescreve o `.env`, e apontá-lo para o projeto faria a suíte destruir o ambiente
  de quem roda os testes.
- O arranjo do compose é o do vizinho `tests/Kit/CacheDeViewsNoDockerTest.php`:
  `file_get_contents` do `docker-compose.yml`, mais uma versão **sem as linhas de comentário**
  para as asserções de ausência.

### Fakes

- **Nenhum.** Sem fila, sem e-mail, sem HTTP, sem evento. `Log::spy()` só no caso **existente**
  que garante que a senha não vaza — não é preciso em nenhum cenário novo.

### Estratégia de DB

`tests/Pest.php` liga `TestCase` + `RefreshDatabase` em `tests/Kit`, com o grupo `kit`. A base é
SQLite `:memory:` (`phpunit.xml`). Nenhum cenário novo precisa de banco; herdam o arnês porque é
o que a pasta oferece.

**O `afterEach` do vizinho é obrigatório para quem mexe em banco escolhido**: ele devolve
`database.default` para `sqlite` e faz `DB::purge('pgsql')` / `DB::purge('mysql')`. Sem isso, um
caso que aplica `banco => 'pgsql'` deixa o rollback do `RefreshDatabase` procurando a transação
na conexão errada, e **todos** os casos seguintes falham com "cannot start a transaction within
a transaction". Os cenários novos que trocam de banco (CT-08, CT-16) entram **no arquivo que já
tem esse `afterEach`**.

### Onde cada cenário mora, e por que não há helper novo compartilhado

| Arquivo | Cenários | Por quê |
|---|---|---|
| `tests/Kit/MysqlNoDockerTest.php` (**novo**) | CT-01 … CT-07, CT-09, CT-10, CT-14, CT-15, CT-21, CT-22, CT-23 | lê `docker-compose.yml`, `.env.example`, os dois READMEs, as duas páginas de instalação avançada e a lista de caminhos do `kit:update`. `beforeEach` próprio, com o **recorte de bloco** e o filtro de comentário como *closures* locais |
| `tests/Kit/CustomizadorDaInstalacaoTest.php` (**ampliado**) | CT-08, CT-11 … CT-13, CT-16 … CT-20 | os quatro helpers de que esses casos precisam (`envDoTeste()`, `valorNoEnv()`, `respostasDeCustomizacao()`, `customizadorNoTemp()`) **já existem naquele arquivo**, e o `afterEach` que salva o `RefreshDatabase` também. CT-20 lê o compose direto, sem helper — é o cenário que atravessa os dois artefatos |

**Duas ferramentas de leitura que os cenários pressupõem**, e que valem por si:

- **recorte de bloco de serviço** — do `/^  <serviço>:$/m` até a próxima chave de coluna 2. É o
  que torna CT-01, CT-02, CT-03 e CT-09 discriminantes: sem ele, `restart: unless-stopped` e
  `mysqladmin ping` ficam verdes por causa dos dez serviços vizinhos e dos comentários.
- **filtro de linhas de comentário** — o mesmo do vizinho, só para as asserções de ausência.

As duas ficam **locais** ao arquivo novo. Se um terceiro arquivo precisar do recorte, aí ele vai
para `tests/Pest.php` como função, conforme `.ai/rules/testes.md` — não antes.

**Nenhum helper novo vai para `tests/Pest.php`, de propósito.** A regra de `.ai/rules/testes.md`
é sobre helper usado por **mais de um arquivo**; aqui cada arquivo se serve sozinho. O filtro de
comentário aparece nos dois arquivos de compose (o vizinho e o novo) como *closure* dentro do
`beforeEach`, não como função global — a guarda `tests/Kit/HelpersDeTesteTest.php` só enxerga
`function nome(`, e mover o filtro para `tests/Pest.php` exigiria editar o vizinho, que hoje
tem 9 casos verdes e é a rede de regressão desta feature. **Se um terceiro arquivo precisar do
filtro, é aí que ele vira função em `tests/Pest.php`** — não antes.

---

## Regra R1 — o kit passa a declarar um serviço MySQL utilizável e persistente

> `RQ-01`, `RQ-03`, `RQ-04` · área **A**, perfil **completo** · técnica: **EP por atributo
> obrigatório** — cada linha do `Exemplos:` é uma partição do conjunto "atributos sem os quais
> o serviço não cumpre RQ-01", e cada uma tem um modo de falha próprio.

Por que EP e não uma asserção única: os atributos falham de formas diferentes e independentes.
Sem o volume declarado no topo, o arquivo é **inválido** e nenhum comando roda; sem o volume
montado, o arquivo é válido e os dados morrem no `down`; sem o nome do banco, o container fica
saudável e a aplicação morre com "Unknown database"; com tag flutuante, tudo funciona hoje e
quebra num dia qualquer. Uma asserção só não distingue esses casos.

**O `Dado` é o BLOCO do serviço, não o arquivo.** A revisão adversarial mostrou que varrer o
arquivo inteiro torna metade das linhas inútil: `restart: unless-stopped` já aparece **dez
vezes** no compose de hoje, nos serviços vizinhos, então uma asserção sobre o arquivo é
**verdadeira antes da entrega** e não mata mutante nenhum. O recorte é do `^  mysql:$` até a
próxima chave de coluna 2, e só sobre esse pedaço as linhas são afirmadas.

```gherkin
# language: pt

Funcionalidade: MySQL no Docker do kit

  Regra: o compose do kit declara um serviço MySQL utilizável e persistente

    Esquema do Cenário: [CT-01] o serviço MySQL do kit traz os atributos sem os quais não serve
      Dado o bloco do serviço `mysql`, recortado do `docker-compose.yml` do kit
      Quando o mantenedor lê os atributos declarados nesse bloco
      Então o bloco contém a linha "<forma exigida>", ancorada na indentação do YAML

      Exemplos:
        | atributo (partição)               | forma exigida                             | como falha sem ele                                        |
        | o serviço existe sob `services:`  | `  mysql:` com corpo indentado            | RQ-04: o serviço nasce num arquivo de override ao lado     |
        | imagem fixada em major.minor      | `    image: mysql:8.0`                    | tag flutuante quebra numa atualização silenciosa           |
        | política de restart do kit        | `    restart: unless-stopped`             | divergir da convenção dos dez serviços vizinhos            |
        | porta publicada, com override     | `      - '${FORWARD_DB_PORT:-3306}:3306'` | porta fixa colide com um MySQL já instalado na máquina     |
        | o banco da aplicação é criado     | `      MYSQL_DATABASE: '${DB_DATABASE...` | container saudável e `migrate` morre em "Unknown database" |
        | volume nomeado montado            | `      - 'mysql-data:/var/lib/mysql'`     | os dados morrem em `docker compose down`                   |
        | volume declarado no bloco de topo | `  mysql-data:` sob `volumes:`            | o arquivo fica INVÁLIDO e todo comando compose falha       |
```

**Por que os valores são discriminantes**: a forma exigida é sempre a linha **inteira e
ancorada** (`/^    image: mysql:8\.0$/m`), não `toContain('mysql')`. A palavra `mysql` vai
aparecer no comentário do serviço, no comentário de topo do arquivo e no comando documentado —
`toContain` ficaria verde com o serviço inexistente. Linha começando por `#` não casa
`/^    image:/`.

Duas linhas exigem cuidado extra, as duas trazidas pela revisão adversarial:

- **`  mysql:` sozinho não prova que existe um serviço.** A indentação de dois espaços é a mesma
  de uma entrada do bloco `volumes:` de topo. A asserção é sobre a chave **sob `services:`** e
  com corpo — na prática, o mesmo recorte de bloco que o `Dado` já faz: se o recorte vier vazio,
  o cenário reprova.
- **`mysql-data` tem de aparecer duas vezes no arquivo** (o mount e a declaração de topo).
  Contar as ocorrências, ou casar `/^volumes:$.*^  mysql-data:$/ms`, distingue "esqueci a
  declaração" — o defeito que derruba o `docker compose up -d` de **todo** usuário do kit,
  inclusive quem usa Postgres.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M01 | `image: mysql:latest` (ou `image: mysql`, sem tag) | CT-01, linha "imagem fixada" |
| M02 | volume montado no serviço e `mysql-data:` esquecido no bloco `volumes:` de topo — o erro de digitação mais comum em compose, e o de maior alcance | CT-01, linha "volume declarado no bloco de topo" |
| M03 | sem volume nenhum, "porque é banco de desenvolvimento" | CT-01, linha "volume nomeado montado" |
| M04 | `ports: - '3306:3306'` fixo, sem `${FORWARD_DB_PORT:-3306}` | CT-01, linha "porta publicada, com override" |
| M05 | o serviço entra num arquivo novo (`docker-compose.mysql.yml`, `compose.override.yml`) em vez do compose do kit — leitura literal de "adicionar" sem RQ-04 | CT-01, linha "o serviço existe sob `services:`" (o recorte é feito no `docker-compose.yml`) |
| M49 — *revisão adversarial* | o serviço sobe, fica saudável, root conecta — e `MYSQL_DATABASE` nunca foi declarado, então o banco da aplicação não existe. RQ-01 pede serviço **utilizável** | CT-01, linha "o banco da aplicação é criado" (e CT-08, que fixa o `DB_DATABASE` do outro lado) |
| M50 — *revisão adversarial* | o serviço nasce sem `restart: unless-stopped` — e a asserção varrendo o arquivo inteiro fica verde por causa dos dez vizinhos que já a satisfazem | CT-01 com o `Dado` recortado no bloco (sem o recorte, este mutante **não** tem matador) |

---

## Regra R2 — o MySQL nasce saudável, e a aplicação containerizada só arranca depois

> `RQ-01`, `RQ-03`, `A1` · áreas **A** e **B**, perfil **completo** · técnica: **EP** (a forma do
> healthcheck) + **rastreio de dependência por contagem** (os cinco serviços do profile `app`)

A resposta `A1` do `00` diz que o profile `app` precisa funcionar com MySQL. Os cinco serviços
daquele profile esperam o banco por `condition: service_healthy` — logo, um MySQL sem
healthcheck, ou com healthcheck que nunca fica verde, faz o profile `app` **nunca subir**. É
por isso que o healthcheck é regra de RQ-01 e não detalhe estético.

```gherkin
# language: pt

  Regra: o MySQL nasce saudável, e a aplicação containerizada só arranca depois dele

    Cenário: [CT-02] o healthcheck do MySQL lê a senha dentro do container
      Dado o bloco `healthcheck:` recortado do serviço `mysql`
      Quando o mantenedor lê o comando de teste de saúde
      Então o comando invoca `mysqladmin ping`
      E a senha é passada como `$$MYSQL_ROOT_PASSWORD`, com o dólar dobrado
      E nenhum `$` solitário precede `MYSQL_ROOT_PASSWORD` nesse comando

    Esquema do Cenário: [CT-03] cada serviço da aplicação containerizada espera o banco escolhido
      Dado o bloco do serviço `<serviço>`, recortado do `docker-compose.yml` do kit
      Quando o mantenedor lê o `depends_on` desse serviço
      Então ele declara `mysql` com `condition: service_healthy` e `required: false`
      E ele continua declarando `pgsql` com `condition: service_healthy`

      Exemplos:
        | serviço   | por que está na lista                         |
        | app       | o php-fpm da aplicação                        |
        | queue     | o worker de fila                              |
        | scheduler | o agendador                                   |
        | reverb    | o WebSocket, que também é do profile realtime |
        | pulse     | o Pulse, idem                                 |
```

**Por que o valor de CT-02 é discriminante**: o dólar simples é a forma que *parece* certa e
falha em silêncio — o Compose interpola `$MYSQL_ROOT_PASSWORD` em tempo de leitura do arquivo,
onde a variável não existe, e o `mysqladmin` roda com senha vazia. O container fica
`unhealthy` para sempre, e quem depende dele por `service_healthy` nunca sobe.

**A negativa precisa de olhar-para-trás, senão ela é autocontraditória.** A revisão adversarial
pegou isto: `$$MYSQL_ROOT_PASSWORD` **contém** `$MYSQL_ROOT_PASSWORD`, então "não contém a forma
de um dólar" reprovaria a implementação **correta**. A asserção executável é
`/(?<!\$)\$MYSQL_ROOT_PASSWORD/` **não** casando — um `$` que não é precedido por outro `$`. E o
`Dado` recorta o `healthcheck:` do bloco do serviço, porque `mysqladmin ping` como substring do
arquivo inteiro ficaria verde por causa de um comentário que citasse o comando.

**Por que CT-03 virou Esquema com os cinco nomes**: a versão anterior contava ocorrências no
arquivo inteiro, e a revisão adversarial mostrou que a contagem `= 5` é satisfeita por **cinco
serviços errados** — editar `nginx` (que está logo acima e "também é do app") e esquecer o
`pulse` fecha a conta. O `00` **nomeia** os cinco serviços na resposta A1; converter uma lista
fechada num número joga fora a informação que o requisito deu de graça. Uma linha por serviço
custa o mesmo (um `Esquema do Cenário` conta como um cenário) e ancora a asserção onde ela
pertence.

A segunda asserção (o `pgsql` continua) é a que impede a "limpeza" que a ADR-01 mediu e
recusou: tirar o Postgres do `depends_on` deixa `--profile app up -d` subir a aplicação **sem
banco nenhum**, sem erro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M06 | healthcheck com a senha lida por um dólar só — o Compose interpola e o teste roda com senha vazia | CT-02 |
| M07 | serviço sem `healthcheck:` nenhum, "porque o Postgres já tem e ninguém reclamou" | CT-02 (o recorte do bloco vem vazio) |
| M08 | `depends_on` do MySQL sem `required: false` — o `--profile app up -d` de quem usa Postgres passa a exigir um serviço que não está no profile | CT-03 (todas as cinco linhas) |
| M09 | `depends_on` editado só no serviço `app`, e `queue`/`scheduler`/`reverb`/`pulse` esquecidos | CT-03 (quatro linhas vermelhas) |
| M10 | `pgsql` retirado do `depends_on` dos cinco, para não subir um Postgres ocioso — a "limpeza" medida e recusada na ADR-01 | CT-03 (segunda asserção) |
| M51 — *revisão adversarial* | os cinco `depends_on` são editados, mas num conjunto errado de cinco serviços: entra o `nginx`, sai o `pulse` — a contagem global fecha e o Pulse containerizado nunca acha o banco | CT-03 (linha `pulse`) |
| M11 ⚠️ | o healthcheck está sintaticamente correto e o container **nunca** fica `healthy` (flag do `mysqladmin` mudou na imagem, socket ainda não existe no intervalo configurado) | ⚠️ **sem matador na suíte** — só daemon prova. Verificação manual **VM-01** |

---

## Regra R3 — subir o MySQL não sobe o Postgres

> `RQ-02` · área **B**, perfil **completo** · técnica: **tabela de decisão** sobre duas
> condições, com todas as quatro combinações enumeradas

O `00` explica por que RQ-02 é cláusula: a escolha do instalador é `select()` de opção única, e
uma entrega que suba os dois bancos atende a letra de RQ-01 e falha o motivo do pedido.

| # | `mysql` atrás de profile? | `pgsql` atrás de profile? | O que acontece (medido, ADR-01) | Veredito |
|---|---|---|---|---|
| 1 | **sim** | **não** | `up -d` sobe só o Postgres; `up -d mysql redis` sobe exatamente dois containers e o Postgres **não** sobe | **única sobrevivente** → CT-04 |
| 2 | não | não | `docker compose up -d` sobe os **dois** bancos em toda instalação — contraria RQ-02 e o motivo do pedido | mutante M12 |
| 3 | sim | sim | `docker compose up -d` puro deixa de subir banco (quebra o comando do README) e `--profile app up -d` sobe a aplicação **sem banco nenhum**; e o `--profile` da linha de comando **substitui** o `COMPOSE_PROFILES` do `.env`, então cada comando documentado do kit passaria a subir sem banco | mutante M13 |
| 4 | não | sim | inverte o default do kit, que a seção *Fora de Escopo* do `00` proíbe | mutante M14 |

```gherkin
# language: pt

  Regra: subir o MySQL não obriga a subir o Postgres

    Cenário: [CT-04] o MySQL é o único banco atrás de profile, e o profile é só dele
      Dado o `docker-compose.yml` do kit
      Quando o mantenedor compara os dois serviços de banco
      Então o serviço `mysql` declara uma lista de profiles com exatamente um elemento
      E esse elemento não aparece na lista de profiles de nenhum outro serviço do arquivo
      E o serviço `pgsql` não declara profile nenhum

    Cenário: [CT-21] nenhum banco novo entra no profile default
      Dado o `docker-compose.yml` do kit
      Quando o mantenedor lista os serviços cuja imagem é de banco de dados e que não declaram profile
      Então a lista tem exatamente um serviço, e ele é o `pgsql`

    Cenário: [CT-05] o comando divulgado nomeia os serviços, e não liga um profile
      Dado o comentário de topo do compose, os dois READMEs e as duas páginas de instalação avançada
      Quando o mantenedor procura como subir o MySQL em cada um dos cinco textos
      Então todos trazem `docker compose up -d mysql redis`
      E em nenhum deles a palavra `--profile` aparece na mesma linha que o serviço MySQL
      E em nenhum deles aparece `COMPOSE_PROFILES`
      E o serviço nomeado nesse comando é o mesmo declarado no compose
```

**Por que CT-05 não é teste de documentação**: `docker compose --profile mysql up -d` **liga** o
profile `mysql` e o **soma** ao default — sobe MySQL, Redis **e Postgres**. É a forma que
qualquer pessoa escreveria por analogia com `--profile ai` e `--profile mail`, os cinco
comandos que o kit já documenta. Um arquivo perfeito com o comando errado divulgado entrega uma
violação de RQ-02 a cada pessoa que segue o texto.

Duas correções vindas da revisão adversarial:

- **O universo eram três textos e a varredura SFDIPOT lista quatro arquivos de documentação.**
  As duas páginas de `docs/{pt,en}/comecar/instalacao-avancada.md` estavam fora da asserção, e
  são elas que o site de documentação publica. Um `--profile mysql up -d` ali violaria RQ-02
  para toda pessoa que lesse o site, com os READMEs verdes.
- **A negativa era um literal exato.** `--profile mysql up`, `--profile mysql up -d --build` e
  `COMPOSE_PROFILES=mysql` passavam todos. A forma que falsifica é proibir `--profile` na
  vizinhança do serviço MySQL e proibir `COMPOSE_PROFILES` nesses textos, não proibir uma frase.

**Por que CT-21 existe além de CT-04**: CT-04 fala de **dois serviços nominados**, e o
invariante que RQ-02 exige é sobre o **conjunto** — *exatamente um banco fora de profile*. A
diferença não é retórica: a premissa **A4** do `00` (só o `mysql` entra, e não os
`mysql_test`/`mysql_dusk_test` do arquivo de referência) tem consequência declarada e, sem
CT-21, **nenhum cenário**. Um transplante que trouxesse os dois containers de teste sem profile
("banco de teste tem que estar sempre de pé") passa em CT-04 inteiro e faz
`docker compose up -d` subir três bancos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | `mysql` sem `profiles:`, junto do Postgres no default (linha 2 da tabela) | CT-04, CT-21 |
| M13 | `pgsql` ganha `profiles: [pgsql]` pela simetria tentadora — os dois bancos opt-in (linha 3) | CT-04 |
| M14 | os profiles trocados de lado: `pgsql` gated, `mysql` no default (linha 4) | CT-04, CT-21 |
| M15 | `profiles: [mysql, full]` — o MySQL entra no "infra completa", e `--profile full up -d` sobe dois bancos | CT-04 (a lista tem de ter um elemento só, e exclusivo) |
| M16 | o serviço é declarado com outro nome (`mysql-db`, `db`), e o valor gravado pelo instalador aponta para um host que não existe | CT-05 (última asserção) + CT-01 + CT-20 |
| M52 — *revisão adversarial* | entram também `mysql_test` e `mysql_dusk_test` do arquivo de referência, sem profile — nega a premissa A4, e `docker compose up -d` sobe três bancos | CT-21 |
| M53 — *revisão adversarial* | o comando certo entra nos READMEs e o errado (`--profile mysql up -d`) nas duas páginas de `docs/`, que são as que o site publica | CT-05 (universo de cinco textos) |
| M17 ⚠️ | `docker compose up -d mysql redis` sobe um **terceiro** container por uma dependência não prevista | ⚠️ **sem matador na suíte** — só daemon prova. Verificação manual **VM-02** |
| M61 ⚠️ — *revisão adversarial* | a resposta à pergunta **A7** é `COMPOSE_PROFILES=app,mysql` no `.env`, e aí `docker compose up -d` puro passa a somar o profile ao default: **os dois bancos sobem** em toda instalação MySQL. Nenhum cenário lê `COMPOSE_PROFILES` do `.env` | ⚠️ **sem matador**, e é consequência direta de **A7**. Se a resposta for essa, R3 ganha um cenário sobre o `.env` |

---

## Regra R4 — o profile `app` deixa de fixar o Postgres, e o default é o comportamento de hoje

> `A1`, `RQ-02`, *Fora de Escopo* do `00` · área **B**, perfil **completo** · técnica: **EP** +
> **contagem sobre os cinco serviços**

```gherkin
# language: pt

  Regra: nenhum serviço da aplicação containerizada fixa o banco, e o default é o de hoje

    Cenário: [CT-06] os cinco serviços do profile `app` deixam de fixar o Postgres
      Dado o `docker-compose.yml` do kit sem as linhas de comentário
      Quando o mantenedor conta os campos de banco do bloco `environment:` desses serviços
      Então `DB_CONNECTION: pgsql` e `DB_HOST: pgsql` não aparecem nenhuma vez
      E `DB_CONNECTION` vem de interpolação nas cinco ocorrências
      E `DB_HOST` vem de interpolação nas cinco ocorrências

    Cenário: [CT-07] quem não escolheu nada continua no Postgres
      Dado o `docker-compose.yml` do kit
      Quando o mantenedor lê as dez linhas de banco do profile `app`
      Então as dez trazem a mesma string interpolada, e ela termina com o default `:-pgsql}`

    Cenário: [CT-20] a variável que o compose lê é a chave que o instalador grava
      Dado o `.env` produzido por uma instalação com o banco `mysql`
      Quando o mantenedor compara a chave nova desse `.env` com a variável interpolada no compose
      Então as dez linhas de banco do profile `app` interpolam exatamente essa chave
      E nenhuma outra chave aparece interpolada nessas dez linhas
```

**Por que a asserção de ausência de CT-06 precisa da forma exata**: depois da mudança a linha é
`DB_CONNECTION: ${DOCKER_DB_SERVICE:-pgsql}` — que **contém** a palavra `pgsql`. Um
`not->toContain('pgsql')` reprovaria a implementação correta. A ausência é da forma **literal**
`DB_CONNECTION: pgsql` (dois pontos, espaço, e nenhum `${`), e roda sobre o arquivo sem
comentário, porque o comentário único que o plano põe no serviço `app` explica a variável e vai
citar `pgsql`.

**Por que CT-07 existe separado**: é o cenário que carrega o **valor literal do requisito**. A
seção *Fora de Escopo* do `00` diz que trocar o banco default do kit está fora do escopo, e o
default da interpolação é o único lugar onde esse "fora de escopo" é falsificável. Sem CT-07,
um `:-mysql}` distraído passa e muda o banco de toda aplicação containerizada que não definiu a
variável. A revisão adversarial reforçou o `Então`: **a mesma** string nas dez, e não dez
strings diferentes que por acaso terminam igual.

**Por que CT-20 existe, e por que ele é o achado mais caro da revisão**: a seção *Fronteira com
o Plano* recusa — corretamente — o nome `DOCKER_DB_SERVICE` como oráculo, porque a resposta A1
do `00` diz "viram variável" sem nomear nada. Só que, ao recusar o nome, o conjunto ficou sem
**ninguém amarrando as duas pontas**: o compose podia interpolar `DOCKER_DB_CONNECTION` e o
instalador gravar `DOCKER_DB_SERVICE`, e todos os cenários ficavam verdes com a aplicação
containerizada rodando **sempre** em Postgres — A1 entregue no papel e falha inteira na prática.
CT-20 fecha o par sem fixar o nome: ele lê a chave que o instalador realmente escreveu e exige
que seja essa a interpolada. É a mesma técnica que CT-05 aplica ao **nome do serviço**; faltava
aplicá-la ao **nome da variável**, que é a outra metade do mesmo contrato entre artefatos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | só o serviço `app` parametrizado; `queue`, `scheduler`, `reverb` e `pulse` continuam com `pgsql` fixo | CT-06 (contagem 1 ≠ 5) |
| M19 | as linhas `DB_CONNECTION` apagadas para deixar o `env_file: .env` valer — alternativa 2 da ADR-06, que faz um projeto SQLite rodar containerizado sobre o arquivo assado na imagem | CT-06 (contagem 0 ≠ 5) |
| M20 | `DB_HOST` parametrizado e `DB_CONNECTION` esquecido: host `mysql`, driver `pgsql` | CT-06 (as duas contagens são afirmadas) |
| M21 | default trocado para `:-mysql}` | CT-07 |
| M54 — *revisão adversarial* | o compose interpola uma chave e o instalador grava outra (`DOCKER_DB_CONNECTION` × `DOCKER_DB_SERVICE`, ou um typo) — a aplicação containerizada nunca sai do Postgres, ou aponta para host morto | CT-20 |
| M22 ⚠️ | com a variável apontando `mysql` no `.env`, `docker compose --profile app up -d` sobe a aplicação apontando para um host que não existe (o profile `mysql` não é ligado por esse comando) e um Postgres ocioso saudável ao lado | ⚠️ **sem matador na suíte**, e **bloqueado pela pergunta A7** — verificação manual **VM-04** |

---

## Regra R5 — uma fonte só para a senha do MySQL, e ela não é vazia

> `A5`, `RQ-01` · áreas **A** e **D**, perfil **completo** · técnica: **EP** sobre
> *ausente ≠ vazio ≠ preenchido*, com cada partição inválida isolada num cenário

`A5` é premissa **respondida** pelo mantenedor no `00`, com medição na imagem real — por isso é
oráculo, e não escolha de plano. Ela determina duas coisas: o `.env` nasce com senha não vazia,
e a senha do container é a **mesma** que a aplicação usa.

```gherkin
# language: pt

  Regra: a senha do MySQL tem uma fonte só, o `.env`, e ela nunca nasce vazia

    Cenário: [CT-08] a instalação com MySQL grava um bloco de banco utilizável
      Dado um `.env` recém-copiado do `.env.example` num diretório temporário
      Quando o instalador aplica as respostas com o banco `mysql` e o nome "Loja do Ferro"
      Então `DB_PASSWORD` vale `secret`
      E `DB_USERNAME` vale `root`
      E `DB_PORT` vale `3306`
      E `DB_DATABASE` vale `loja_do_ferro`
      E `DB_HOST` vale `127.0.0.1`

    Cenário: [CT-09] o container cria o banco, lê a senha do `.env` e não abre sem senha
      Dado o bloco `environment:` recortado do serviço `mysql`
      Quando o mantenedor lê as variáveis passadas à imagem
      Então `MYSQL_ROOT_PASSWORD` vem de `${DB_PASSWORD:-secret}`
      E `MYSQL_DATABASE` vem de `${DB_DATABASE...}`
      E, no bloco sem comentário, não aparecem `MYSQL_USER`, `MYSQL_ALLOW_EMPTY_PASSWORD` nem `MYSQL_RANDOM_ROOT_PASSWORD`
```

**Por que `secret` literal, e não `config()`**: é o valor que o `00` fixa na resposta A5, e o
único jeito de um default errado ficar vermelho é alguém escrever o valor do requisito.

**Por que `DB_DATABASE` e `MYSQL_DATABASE` entraram nos dois lados**: a revisão adversarial
mostrou que a regra "uma fonte só" estava aplicada à senha e a mais nada. Um container que sobe,
fica saudável e aceita `root` — mas sem o banco criado — reprova RQ-01 pelo critério que a
própria rastreabilidade adotou ("oferecer inclui ser utilizável"), e `php artisan migrate` morre
com *"Unknown database"*. Os dois lados da mesma fonte precisam ser afirmados juntos, senão a
divergência entre eles é invisível.

**Por que `MYSQL_RANDOM_ROOT_PASSWORD` entrou na lista de ausências**: é a **terceira** saída que
a própria mensagem de erro da imagem oferece (`MYSQL_ROOT_PASSWORD`, `MYSQL_ALLOW_EMPTY_PASSWORD`,
`MYSQL_RANDOM_ROOT_PASSWORD`), e a medição registrada em A5 cita a mensagem inteira. Ela produz
uma senha aleatória que **não** está no `.env` — a divergência silenciosa que A5 existe para
impedir, por um caminho que a versão anterior deste cenário não proibia.

**Por que as duas ausências de CT-09 precisam do filtro de comentário**: o comentário que o
plano escreve no serviço **cita** `MYSQL_USER` para explicar por que ele não está lá ("a imagem
recusa `MYSQL_USER=root`"), e a ADR-05 cita `MYSQL_ALLOW_EMPTY_PASSWORD` como alternativa
recusada. Sem o filtro, a asserção reprova pela própria documentação que torna a decisão
utilizável — o padrão que já custou três vezes nesta base (`.ai/rules/testes.md`).

**Por que as duas partições inválidas estão em cenários separados**: `DB_PASSWORD` vazio (CT-08)
e `MYSQL_ALLOW_EMPTY_PASSWORD` presente (CT-09) são as duas metades da mesma armadilha, mas
falham em pontos diferentes — uma no `.env` escrito, outra no arquivo distribuído. Juntá-las
num cenário faria a primeira asserção mascarar a segunda.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | `DB_PASSWORD` continua vazio no ramo `mysql`, e o fallback do compose "resolve" — container com `secret`, aplicação com vazio, "Access denied" sem pista (alternativa 2 da ADR-05) | CT-08 |
| M24 | `MYSQL_ROOT_PASSWORD: 'root'` literal no YAML, como no arquivo de referência — duas fontes que divergem em silêncio | CT-09 |
| M25 | `MYSQL_USER: '${DB_USERNAME:-root}'` por simetria com o `POSTGRES_USER` do serviço vizinho — a imagem **recusa inicializar** | CT-09 |
| M26 | `MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'` para preservar o `.env` de hoje — root sem senha numa porta publicada em `localhost` | CT-09 |
| M27 | `DB_USERNAME` trocado para `starter_kit` pela simetria com o Postgres, sem `MYSQL_USER` que o crie — o container sobe e a aplicação não conecta | CT-08 |
| M55 — *revisão adversarial* | `MYSQL_RANDOM_ROOT_PASSWORD: 'yes'`, a terceira saída que a mensagem da imagem sugere — o container sobe com senha aleatória, e o `.env` diz outra | CT-09 |
| M56 — *revisão adversarial* | o ramo `mysql` deixa de gravar `DB_DATABASE`, ou grava um nome que não é o que o container cria — banco de pé, aplicação com "Unknown database" | CT-08 (a quarta asserção) + CT-01 e CT-09 (o outro lado) |

---

## Regra R6 — o prefixo dos containers passa a vir do nome da aplicação

> `RQ-05`, `A2`, `A3` · áreas **C** e **D**, perfil **padrão** · técnica: **rastreio de efeito**
> (a chave gravada no `.env`) + **asserção de ausência** (os onze nomes fixos)

`A2` é resposta do mantenedor, e nomeia as duas metades: a chave no `.env` **e** a saída dos
onze `container_name:`. As duas são necessárias: `COMPOSE_PROJECT_NAME` renomeia o projeto e a
rede, mas **não** renomeia container com nome explícito — o valor fixo ganha. Metade entregue é
o nome do projeto novo com todos os containers no prefixo antigo.

```gherkin
# language: pt

  Regra: o prefixo dos containers vem do nome da aplicação, e nenhum nome é fixo no arquivo

    Cenário: [CT-10] nenhum container do kit tem nome fixo no compose
      Dado o `docker-compose.yml` do kit sem as linhas de comentário
      Quando o mantenedor procura nomes de container escritos à mão
      Então `container_name` não aparece nenhuma vez

    Cenário: [CT-11] a instalação grava o nome do projeto Compose no `.env`
      Dado um `.env` recém-copiado do `.env.example` num diretório temporário
      Quando o instalador aplica as respostas com o nome "Loja do Ferro"
      Então o arquivo contém a linha crua `COMPOSE_PROJECT_NAME="loja-do-ferro"`
      E `APP_NAME` continua valendo `Loja do Ferro`

    Cenário: [CT-23] a chave do nome do projeto sobrevive ao `kit:update`
      Dado as listas de caminhos que o `kit:update` atualiza
      Quando o mantenedor procura o `.env` nelas
      Então nenhuma das listas contém o `.env`
```

**Por que CT-10 filtra comentário**: o plano põe um comentário junto do `name:` explicando de
onde vem o prefixo, e o assunto daquele comentário é justamente a nomeação dos containers — a
palavra tende a aparecer ali, e a asserção reprovaria a explicação.

**Por que CT-11 afirma as duas chaves**: `APP_NAME` continuar com o nome cru é o que separa
"derivei um nome de projeto" de "estraguei o nome da aplicação". Uma implementação que gravasse
o slug nas duas passaria numa asserção só.

**Por que CT-11 afirma a LINHA CRUA, com as aspas**: a revisão adversarial pegou uma
incoerência de oráculo entre CT-11 e CT-18 — um afirmava o valor **depois** do parser de `.env`
(que remove as aspas) e o outro a linha crua **com** aspas. Convivendo, o par ficava cego
justamente ao defeito da pergunta **A8**: se a forma citada não for aceita pelo Compose, o
cenário que mede o valor pós-parser continua verde. Os dois passam a afirmar a linha crua, que é
o que o Compose vai ler. **Enquanto A8 não for respondida, a forma citada é premissa, não
verdade medida** — está registrado como M36 em R7.

**Por que CT-23 é barato e obrigatório**: a resposta **A2** do `00` sustenta o mecanismo inteiro
numa frase — *"sobrevive ao `kit:update`, porque o `.env` nunca é sobrescrito por ele"*. É a
premissa da qual depende toda a RQ-05, e ela é um fato sobre uma lista de caminhos que alguém
pode editar amanhã sem perceber o que derrubou. Um cenário de três linhas trava a premissa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | os onze `container_name:` reescritos como `${COMPOSE_PROJECT_NAME:-starter-kit}-pgsql` — onze interpolações à mão para reproduzir o que o Compose faz sozinho, e nome fixo impede escalar o serviço | CT-10 |
| M29 | remoção **parcial**: saem os da infra base e ficam os cinco do profile `app` | CT-10 (ausência total) |
| M30 | a chave gravada recebe o nome cru (`COMPOSE_PROJECT_NAME="Loja do Ferro"`) — o Compose recusa maiúscula e espaço | CT-11, CT-12 |
| M31 | a chave nunca é gravada; só o `name:` do YAML é trocado à mão (e o `kit:update` o devolve na primeira atualização) | CT-11 |
| M57 — *revisão adversarial* | o `.env` entra numa das listas do `kit:update` para "manter o exemplo em dia", e a primeira atualização apaga a customização de quem aceitar o diff | CT-23 |

---

## Regra R7 — o nome gravado é sempre um nome de projeto que o Compose aceita

> `RQ-05`, `A2` · área **C**, perfil **padrão** com **técnica escalada** · técnica:
> **normalização + EP exaustiva por classe de caractere**

A regra medida do Compose (ADR-03, e reproduzida nesta sessão) é: *minúscula, alfanumérico,
hífen e underscore, começando por letra ou número*. Maiúscula e espaço são recusados; hífen e
dígito inicial são aceitos. Nome vazio é erro duro. O oráculo de cada linha é o **valor exato**,
mais o invariante do formato.

```gherkin
# language: pt

  Regra: o nome digitado por uma pessoa vira sempre um nome de projeto que o Compose aceita

    Esquema do Cenário: [CT-12] toda classe de nome digitado produz um nome de projeto válido
      Dado um `.env` recém-copiado do `.env.example` num diretório temporário
      Quando o instalador aplica as respostas com o nome "<nome digitado>"
      Então `COMPOSE_PROJECT_NAME` vale "<gravado>"
      E o valor gravado casa `^[a-z0-9][a-z0-9_-]*$`
      E a quantidade de chaves do `.env` é a mesma de antes

      Exemplos:
        | nome digitado          | gravado             | partição / por que é discriminante                                              |
        | Loja do Ferro          | loja-do-ferro       | espaço vira HÍFEN; `nomeDeBanco()` daria `loja_do_ferro`                         |
        | 2026 Kit               | 2026-kit            | dígito inicial é aceito; `nomeDeBanco()` daria `_2026_kit`, que o Compose RECUSA |
        | Ação & Cia             | acao-cia            | acento e símbolo, que só saem por transliteração                                 |
        | Loja "do" Ferro        | loja-do-ferro       | aspas: o que o escritor de `.env` cita, e o eixo da pergunta A8                  |
        | Kit $APP_ENV           | kit-app-env         | cifrão: o escritor de `.env` escapa, o slug remove — as duas defesas na mesma linha |
        | ###                    | starter-kit         | slug vazio: nome de projeto vazio é erro duro, precisa de piso                   |
        | Loja\nAPP_DEBUG=false  | loja-app-debugfalse | injeção de linha no `.env` (a terceira asserção é o oráculo)                     |
```

**Por que a linha `2026 Kit` é a mais importante do conjunto**: a implementação errada mais
provável é reusar o `nomeDeBanco()` que já existe ao lado — ele acerta a maioria dos nomes
(apenas troca hífen por underscore, e o Compose aceita underscore) e erra **exatamente** os que
começam com dígito, porque prefixa `_`, e o Compose exige começar por letra ou número. Sem esta
linha, o reuso passa e a instalação de quem chamou o projeto "2026 Kit" fica com **todo** comando
`docker compose` recusado. A linha `Loja do Ferro` sozinha não discrimina o reuso quanto ao
formato válido — só quanto ao separador.

**Por que o invariante não substitui os valores exatos**: `^[a-z0-9][a-z0-9_-]*$` é satisfeito
por `loja_do_ferro`. O invariante pega o que ninguém previu; os valores exatos pegam o reuso.

**Duas linhas saíram da tabela por não discriminarem nada** — achado da revisão adversarial, e
vale registrar por que, para ninguém as repor:

- **`MEU KIT` → `meu-kit`**: toda implementação que passe na linha `Loja do Ferro` já
  minusculiza, inclusive o `nomeDeBanco()` que é o mutante principal. A linha matava só um
  mutante que ninguém escreveria ("troquei espaço por hífen e esqueci a caixa").
- **`Kit 🚀` → `kit`**: o emoji cai em `Str::slug`, em `iconv`, num `preg_replace` de
  `[^a-z0-9]` e no `nomeDeBanco()`. Nenhuma implementação plausível diverge ali, e o rótulo
  "quatro bytes" prometia um limite de `varchar` que esta feature não tem — não há coluna de
  banco envolvida.

No lugar delas entraram **aspas** e **cifrão**, que são os caracteres que o escritor de `.env`
trata de forma especial: são eles que atravessam duas transformações (o slug e o escape) e é
neles que as duas podem discordar. A linha das aspas é também a que aponta para a pergunta
**A8** — ela prova que o *valor* fica limpo, não que a *linha gravada* seja aceita pelo Compose.

**Os valores exatos foram conferidos contra `Str::slug` do Laravel instalado**, e um deles
corrige o que a primeira redação supunha: `"Loja\nAPP_DEBUG=false"` produz `loja-app-debugfalse`,
e não `loja-app-debug-false` — o `=` é removido sem virar separador. Valor suposto num
`Exemplos:` é a forma mais silenciosa de um cenário nascer vermelho por culpa do teste.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | reusou `nomeDeBanco()`, que já estava ali e "faz a mesma coisa" | CT-12, linhas "espaço" e "dígito inicial" |
| M33 | `Str::slug($nome, '-')` sem piso para o vazio — `COMPOSE_PROJECT_NAME=""`, e o Compose recusa nome vazio | CT-12, linha `###` |
| M34 | só `strtolower($nome)`, "porque o problema é a maiúscula" — o espaço sobrevive e o Compose recusa | CT-12, linhas "espaço" e "acento" |
| M35 | a quebra de linha não é neutralizada e injeta uma chave nova no `.env` | CT-12, terceira asserção (contagem de chaves) |
| M36 ⚠️ | o Compose **não** remove as aspas de `COMPOSE_PROJECT_NAME="loja-do-ferro"`, forma que o `definirNoEnv()` sempre produz e que **nenhuma** medição da wiki cobriu | ⚠️ **sem matador na suíte**, e é a **pergunta A8** — verificação manual **VM-03** |

---

## Regra R8 — sem customização, o nome de hoje continua

> `RQ-06` · área **C**, perfil **padrão** · técnica: **valor literal do requisito** + piso

```gherkin
# language: pt

  Regra: quem não customizou nada continua com o nome de hoje

    Cenário: [CT-13] o nome sugerido produz exatamente o prefixo de hoje
      Dado um `.env` recém-copiado do `.env.example` num diretório temporário
      Quando o instalador aplica as respostas com o nome sugerido "Starter Kit"
      Então `COMPOSE_PROJECT_NAME` vale `starter-kit`

    Cenário: [CT-14] o piso do arquivo continua valendo para quem não tem a chave
      Dado o `docker-compose.yml` do kit
      Quando o mantenedor lê o nome do projeto declarado no arquivo
      Então existe uma linha, não comentada, que declara `name: starter-kit`

    Cenário: [CT-15] a chave nova nasce ativa no `.env.example`
      Dado o `.env.example` do kit
      Quando o mantenedor procura o nome do projeto Compose
      Então existe uma linha, não comentada, `COMPOSE_PROJECT_NAME=starter-kit`

    Cenário: [CT-22] a instalação que não customiza nada não muda o prefixo de hoje
      Dado um projeto instalado sem passar pelas perguntas do customizador
      Quando o mantenedor lê o nome de projeto que o Compose vai usar
      Então ele é `starter-kit`, vindo do `.env.example` ou do piso do arquivo
      E nenhuma das duas fontes está vazia
```

**Nota de oráculo de CT-15**: este era, na primeira redação, o único cenário do conjunto cujo
oráculo **não** era o requisito — convenção enforçada do projeto (chave nova de `.env` é
declarada no `.env.example` e nos READMEs, padrão que `LoginSocialGoogleTest` e
`LoginSocialProvedoresTest` já cobram para nove chaves). Com CT-22 ele **passa a ter peso de
requisito**: se a chave nascer comentada no `.env.example`, ela é inerte, e o caminho de RQ-06
depende de qual das duas fontes sobra.

**"Não comentada" não é preciosismo em CT-14 e CT-15.** A dimensão **D** desta própria varredura
diz que os três estados (preenchida, comentada, ausente) convivem no `.env.example` — e o
`.env.example` do kit realmente traz chaves comentadas de propósito. `# COMPOSE_PROJECT_NAME=…`
satisfaria uma asserção de substring e entregaria uma chave que não faz nada. Vale o mesmo para
`# name: starter-kit` no compose.

**Por que CT-22 é o cenário que faltava para RQ-06**: a revisão adversarial apontou que CT-13
exercita o caminho *"aceitei o nome sugerido"*, que ainda **roda** o customizador. O caminho
literal da cláusula — *"quando **não** houver customização"* — é aquele em que o instalador não
pergunta nada (`devePerguntar()` falso: sem terminal, `--no-custom`, projeto já instalado) e
**nada é escrito**. Nesse caminho o prefixo depende inteiramente das duas fontes estáticas, e
nenhum cenário anterior afirmava que pelo menos uma delas continua entregando `starter-kit`. Uma
implementação que removesse o `name:` do YAML **e** deixasse a chave comentada no `.env.example`
passava em tudo e mudava o nome de todo mundo que não customizou — para o nome do diretório.

**Nota sobre o desvio já declarado**: a ADR-04 registra que o sufixo `-1` passa a aparecer
mesmo sem customização (`starter-kit-pgsql` → `starter-kit-pgsql-1`). **Nenhum cenário afirma
sobre isso**, de propósito — o desvio foi decidido e declarado no `01` e no `02`, e escrever um
cenário que o proibisse seria a derivação recusando decisão tomada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M37 | `name: starter-kit` removido do YAML, por parecer redundante depois da chave — sem a chave, o Compose passa a usar o nome do **diretório**, que costuma ser o nome do repositório | CT-14 |
| M38 | o piso do slug vazio é outro valor (`app`, `laravel`, `kit`) | CT-12 (linha `###`) e CT-13 |
| M39 | a chave não nasce no `.env.example`, e nada na árvore diz de onde vem o prefixo | CT-15 |
| M58 — *revisão adversarial* | a chave nasce **comentada** no `.env.example` (que é como o kit documenta chave opcional) **e** o `name:` sai do YAML por parecer redundante — quem não customizou passa a ver o nome do diretório como prefixo | CT-22 (e CT-14/CT-15 com a exigência de linha não comentada) |

---

## Regra R9 — a escolha de banco continua exclusiva quando é refeita

> `RQ-02` no ponto de **edição** · área **D**, perfil **completo** · técnica:
> **criação × edição × uso** + **idempotência ancorada no `.env`**
>
> `@premissa` — **bloqueada pela pergunta A6**. Premissa adotada: *a chave que diz qual serviço
> de banco a aplicação containerizada usa acompanha a escolha do instalador em toda execução,
> não só na primeira*. Se o mantenedor decidir aceitar o desvio, CT-16 muda de oráculo (passa a
> afirmar que a documentação avisa) e M40 vira desvio declarado.

O `00` descreve a escolha de banco no ponto de **uso** (o `select()` de opção única). Não diz
nada sobre o que acontece na **segunda** execução — e é ali que a chave nova, gravada só num dos
três ramos, deixa de acompanhar a escolha.

```gherkin
# language: pt

  Regra: a escolha de banco continua exclusiva quando o instalador roda de novo

    Esquema do Cenário: [CT-16] @premissa a chave do banco acompanha a escolha, em toda execução
      Dado um `.env` já customizado com o banco "<antes>"
      Quando o instalador aplica de novo com o banco "<depois>"
      Então `DB_CONNECTION` vale "<depois>"
      E a chave que aponta o serviço de banco do Compose fica no estado "<estado esperado>"

      Exemplos:
        | antes | depois | estado esperado    | por que é discriminante                                                  |
        | mysql | pgsql  | vale `pgsql`       | a chave sobra apontando `mysql`, e o profile `app` procura um host morto |
        | mysql | sqlite | ausente do arquivo | pior variante: é o dano que a alternativa 2 da ADR-06 quis evitar        |
        | pgsql | mysql  | vale `mysql`       | direção positiva — a chave tem de PASSAR a existir com o valor certo     |

    Cenário: [CT-17] reaplicar a customização não duplica nenhuma das chaves novas
      Dado um `.env` do qual as duas chaves novas foram removidas
      Quando o instalador aplica duas vezes as respostas com o banco `mysql`
      Então `COMPOSE_PROJECT_NAME` aparece em exatamente uma linha, com o valor da segunda execução
      E a chave do serviço de banco do Compose aparece em exatamente uma linha, valendo `mysql`
```

**Por que CT-16 deixou de ser puramente negativo**: a revisão adversarial mostrou que
*"a chave não vale `<antes>`"* é satisfeito pela **ausência** da chave — inclusive na linha
`pgsql → mysql`, rotulada "a chave tem de passar a existir" e que não afirmava existência
nenhuma. Uma implementação que **nunca grava a chave** passava nas três linhas. A coluna
`estado esperado` torna cada linha uma afirmação positiva, e a linha `mysql → sqlite` afirma
uma ausência **deliberada** (que é uma das duas saídas que a pergunta **A6** oferece — se o
mantenedor escolher a outra, a célula passa a ser `vale pgsql`).

**Por que CT-17 parte de um `.env` sem as chaves**: também da revisão. Se o `.env.example` já
traz `COMPOSE_PROJECT_NAME` (e CT-15 exige que traga), então "aparece em exatamente uma linha"
é satisfeito por uma linha **herdada**, com o instalador não tendo escrito nada. Removendo as
duas chaves no `Dado`, a contagem de 1 só é atingível se a escrita aconteceu; e a asserção sobre
o **valor da segunda execução** distingue "escreveu uma vez e ignorou a segunda" de "substituiu".

**Por que CT-17 ancora no arquivo, e não no retorno**: idempotência se afirma sobre o agregado
**persistido**. O `.env` é o agregado desta feature, e o defeito plausível
(`SubstituicaoEmArquivo::aplicar()` usado com um padrão que não casa a linha já escrita, caindo
sempre no fallback de append) faz o arquivo crescer a cada `--custom` — a última linha vence, o
comportamento parece certo, e o arquivo apodrece. Afirmar só o **valor** da chave passaria nos
dois desenhos; a **contagem de linhas** não. É a mesma forma do caso existente "acrescenta a
chave ausente uma única vez".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M40 | a chave é gravada só no ramo `mysql` de `valoresDoBanco()`, e sobra no `.env` quando a escolha muda — **é o desenho descrito no plano** | CT-16, linhas `mysql → pgsql` e `mysql → sqlite` |
| M41 | as chaves são anexadas em vez de substituídas, e o `.env` ganha uma linha por execução | CT-17 |
| M42 | o ramo `sqlite` (que devolve array vazio) é tratado como "nada a fazer", e a chave do banco anterior fica | CT-16, linha `mysql → sqlite` |
| M59 — *revisão adversarial* | a chave **nunca** é gravada, em ramo nenhum — e a versão puramente negativa de CT-16 ficava verde nas três linhas | CT-16 (coluna `estado esperado`) e CT-17 (partindo do `.env` sem as chaves) |

---

## Regra R10 — o `--custom` também reescreve o nome do projeto Compose

> `RQ-05` no ponto de **edição**, `A2` ("escrito pelo instalador") · área **D**, perfil
> **completo** · técnica: **criação × edição**

`A2` diz que a chave é escrita pelo instalador e que sobrevive ao `kit:update` porque o `.env`
nunca é sobrescrito por ele. A consequência que a ADR-03 registra é que, num projeto **já
instalado**, o único caminho pelo qual a chave chega é o `kit:install --custom` — que passa por
`aplicarSemBanco()`, um método diferente. Regra escrita na criação e esquecida na edição é
invisível para qualquer cenário que só instale de zero.

```gherkin
# language: pt

  Regra: o caminho não destrutivo também reescreve o nome do projeto Compose

    Cenário: [CT-18] renomear a aplicação num projeto já instalado renomeia os containers
      Dado um `.env` com `APP_NAME=Antigo` e sem o nome do projeto Compose
      Quando o instalador reaplica apenas nome e cor, com o nome "Meu Projeto"
      Então o arquivo contém a linha crua `COMPOSE_PROJECT_NAME="meu-projeto"`
      E o arquivo contém `APP_NAME="Meu Projeto"`
      E `DB_CONNECTION=sqlite` continua intacto no arquivo
```

**Por que a terceira asserção**: é a promessa do nome do método. O caso existente "aplica nome e
cor sem tocar em mais nada" já a faz, e repeti-la aqui é o que impede o conserto errado — copiar
o bloco de `aplicar()` para `aplicarSemBanco()` e trazer o bloco de banco junto, o que faria o
`--custom` reescrever o banco de um projeto em produção.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M43 | a escrita entra só em `aplicar()` — o `--custom` renomeia a aplicação e deixa todos os containers com o prefixo antigo | CT-18 |
| M44 | `aplicarSemBanco()` passa a mexer no bloco de banco, por reuso do trecho errado | CT-18 (terceira asserção) |
| M45 | `aplicarSemBanco()` grava o nome cru, sem passar pela derivação | CT-18 (primeira asserção) |

---

## Regra R11 — o instalador deixa de afirmar que o kit não sobe container MySQL

> `RQ-01` · área **D**, perfil **completo** · técnica: **asserção de ausência com recorte por
> Reflection**

RQ-01 torna falsa uma frase que hoje o kit diz **na tela** (o rótulo da terceira opção de banco:
*"traga o seu servidor (o kit não sobe container MySQL)"*) e outra que ele diz **no código** (o
docblock de `aplicarBanco()`, que justifica a senha vazia pela mesma premissa). Entregar o
container e deixar as duas frases é entregar um produto que se desmente.

```gherkin
# language: pt

  Regra: nada no instalador continua afirmando que o kit não sobe container MySQL

    Cenário: [CT-19] o instalador passa a anunciar o container em vez de negá-lo
      Dado o código-fonte de `CustomizadorDaInstalacao`
      Quando o mantenedor lê o rótulo da opção MySQL e o docblock que aplica o bloco de banco
      Então o rótulo cita o serviço `mysql` do compose
      E nem o rótulo nem o docblock contêm a palavra "não" a menos de cinco palavras de "container"
      E a frase "o kit não sobe container MySQL" não aparece em nenhum dos dois
```

**Por que este é o único lugar do conjunto em que a ausência NÃO filtra o comentário do arquivo
inteiro**: aqui o comentário **é** o artefato sob teste. O docblock afirma um fato sobre o
produto, e o fato deixa de ser verdadeiro. Filtrar comentários deixaria M46 vivo; não filtrar
proibiria qualquer comentário futuro que citasse a frase antiga para explicar a mudança —
exatamente o que a rule do projeto existe para permitir. A saída que preserva as duas coisas é
**recortar os dois trechos** — a linha do rótulo, e o docblock daquele método por
`ReflectionMethod::getDocComment()` — em vez de varrer o arquivo.

**Por que a asserção deixou de ser só uma ausência**: a revisão adversarial mostrou que proibir
uma **frase literal em português** é contornável por sinônimo — *"você traz o seu servidor; não
há container no kit"*, *"o kit não provê MySQL"* — e o cenário ficaria verde com a mesma mentira
na tela. Ausência de literal é asserção sobre uma string; o que RQ-01 exige é uma asserção sobre
a **afirmação**. A forma falsificável combina três coisas: a **presença** do fato novo (o rótulo
cita o serviço do compose, que é o que a pessoa vai digitar), a proibição da negação por
proximidade, e só então a ausência da frase de hoje. Presença é o que fecha o buraco do
sinônimo; ausência sozinha nunca fecha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M46 | só o rótulo da opção é corrigido; o docblock continua justificando a senha vazia pela premissa removida — e o próximo agente lê o docblock, não o `CHANGELOG` | CT-19 (segunda e terceira asserções) |
| M47 | só o docblock é corrigido; a tela do instalador continua dizendo que o kit não sobe container MySQL | CT-19 (primeira asserção) |
| M48 | os dois textos ficam como estão, porque "é comentário, não quebra nada" | CT-19 (as três asserções) |
| M60 — *revisão adversarial* | a frase é reescrita com outras palavras e **continua negando o container** ("o kit não provê MySQL", "não há container aqui") | CT-19 (primeira e segunda asserções) |

---

## Lacunas Declaradas — o que só o Docker prova

Quatro mutantes não têm matador na suíte, e a razão é a mesma: o CI do kit não tem daemon
Docker. Cada um recebe um comando manual, e o roteiro abaixo é a **evidência exigida** antes de
fechar a feature. Não é "verificação opcional": são os quatro mutantes que a suíte não mata.

Antes de declarar cada um como irremediável, foi tentado o caminho de arnês:
`Symfony\Component\Yaml` para asserção estrutural (**recusado**: está na árvore só por
`laravel/roster`, dependência de ferramenta em `require-dev`, então o teste passaria a depender
de uma transitiva que um `composer update` remove); `Process::run('docker compose config -q')`
(**recusado**: exige daemon, e o teste ficaria vermelho na máquina de quem não usa Docker, que
é a maioria de quem instala o kit — o `00` diz que ele roda 100% sem Docker).

| ID | Comando | O que observar | Mata | Pergunta vinculada |
|---|---|---|---|---|
| **VM-01** | `docker compose up -d mysql redis` e depois `docker compose ps` | o container MySQL chega a `healthy`, e não fica `starting`/`unhealthy` | M11 | — |
| **VM-02** | `docker compose up -d mysql redis` | **exatamente dois** containers criados, e nenhum Postgres | M17 | — |
| **VM-03** | `php artisan kit:install --custom` com o nome "Loja do Ferro", depois `docker compose config \| head -1` | a primeira linha é `name: loja-do-ferro`, sem aspas no nome | M36 | **A8** |
| **VM-04** | com a variável de serviço apontando `mysql` no `.env`: `docker compose --profile app up -d --build`, depois `docker compose exec app php artisan migrate --pretend` | o comando conecta; se falhar por host inexistente, o desenho precisa do comando que a pergunta A7 pede | M22 | **A7** |
| **VM-05** | se a resposta a **A7** for `COMPOSE_PROFILES` no `.env`: `docker compose up -d` puro, depois `docker compose ps` | **não** sobem dois bancos — o profile no `.env` **soma** ao default | M61 | **A7** |

Somam-se dois itens de sanidade que não são mutante de comportamento, e que o `01` já lista:
`docker compose config -q` (o arquivo é válido) e `docker compose ps` mostrando o prefixo do
`COMPOSE_PROJECT_NAME`.

**VM-05 é condicional**: ele só existe se a pergunta **A7** for respondida pelo caminho do
`COMPOSE_PROFILES`. Se o comando documentado for `--profile app --profile mysql`, M61 deixa de
ser mutante plausível e a linha sai.

---

## Checklist de Taxonomia

Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou
`lacuna declarada: {o que foi tentado}`. Nunca "sim".

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma rota, nenhum `{id}`, nenhum recurso por usuário. O único ator é quem executa o comando no próprio terminal |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: sem policy, gate, middleware ou guard nesta entrega |
| Idempotência (ancorada no agregado) | **CT-17** — agregado é o `.env`; a asserção é a contagem de linhas **mais** o valor, partindo de um arquivo sem as chaves |
| Contrato entre dois artefatos (um escreve, o outro lê) | **CT-20** (chave do `.env` × variável do compose) e **CT-05** (nome do serviço × comando divulgado) |
| Premissa do requisito travada por cenário | **CT-21** (A4: só o `mysql` entra) e **CT-23** (A2: o `.env` sobrevive ao `kit:update`) |
| Caminho negativo da cláusula (o que acontece quando NÃO se faz nada) | **CT-22** — RQ-06 diz "só quando houver customização", e sem este cenário só o caminho positivo era exercido |
| Concorrência | **não se aplica**: o instalador é comando de uma pessoa numa máquina, sem contador, saldo nem limite. Duas execuções simultâneas do `kit:install` no mesmo diretório não é cenário de uso do kit |
| Fronteira no ponto de entrada (gravação) | **CT-08, CT-11, CT-12** — o nome e o banco são validados/normalizados no ponto em que são **gravados**, não onde são lidos |
| Criação ≠ edição ≠ uso | **CT-11** (criação), **CT-18** (edição por `--custom`), **CT-16** (edição por reinstalação com troca), **CT-01…CT-07** (uso: o arquivo lido pelo Compose) |
| Domínio condicionado (um campo depende de outro) | **CT-16** — o valor válido da chave do serviço de banco depende do banco escolhido; é a combinação, não o campo isolado |
| Estado × operação de escrita | **não se aplica**: nenhuma entidade com ciclo de vida ou `status`. O análogo mais próximo — o estado da linha no `.env` (preenchida, comentada, ausente) × a operação (`aplicar`, `aplicarSemBanco`) — está coberto por **CT-11, CT-17, CT-18** e pelos casos já existentes de R3 do vizinho |
| Ausente ≠ `null` ≠ vazio | **CT-08** e **CT-09** — é o eixo central de R5: `DB_PASSWORD` **ausente** e **vazio** caem os dois no fallback `${DB_PASSWORD:-secret}`, e é por isso que vazio é defeito e não "quase certo" |
| Paginação / ordenação | **não se aplica**: sem listagem |
| Timezone / DST | **não se aplica**: nada depende de relógio, fuso ou expiração (ver dimensão **T** da varredura) |
| Unicode / limite de varchar | **CT-12**, linhas "quatro bytes" e "acento e símbolo" |
| Texto livre em fronteira de confiança | **CT-12** — o nome digitado vai para dentro de um arquivo de configuração; a linha "injeção de linha" é o oráculo |
| Unicidade + soft delete | **não se aplica**: sem banco de dados nesta entrega |
| CRUD combinado | **não se aplica**: sem CRUD |
| Mass assignment | **não se aplica**: `aplicar()` lê chaves fixas de um array montado pelos Prompts; não há payload de usuário |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: nenhum valor numérico calculado |
| Efeito colateral entregue pelo canal certo | **CT-11, CT-16, CT-18** — o "canal" aqui é o arquivo (`.env`) e o método (`aplicar` × `aplicarSemBanco`); CT-18 existe porque o efeito certo no método errado é metade da entrega |
| Regressão em vizinho que lê o mesmo arquivo | **`tests/Kit/CacheDeViewsNoDockerTest.php`** (9 casos, já verdes): os comentários novos do compose não podem citar `config:cache`, `route:cache`, `artisan optimize` nem `filament:optimize`. É o matador do mutante "escrevi um comentário explicando os caches na seção nova" |
| Regressão no instalador | **`tests/Kit/CustomizadorDaInstalacaoTest.php`** e **`tests/Kit/TenancyNaInstalacaoTest.php`**: as chaves novas não podem mudar o resumo impresso (o caso "aplica nome e cor sem tocar em mais nada" afirma `toHaveCount(2)` no resumo) |
| Plataforma com versão mínima não declarada | **lacuna declarada**: `depends_on … required: false` exige compose-spec recente e o kit não declara mínimo. Tentado inferir do README e das páginas de doc — nenhum dos dois traz versão. Virou pergunta **A10** |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | os atributos sem os quais o serviço MySQL não serve | R1 | EP por atributo | Feature (`tests/Kit`) | `tests/Kit/MysqlNoDockerTest.php` | M01–M05, M49, M50 |
| CT-02 | o healthcheck lê a senha dentro do container | R2 | EP | Feature | `tests/Kit/MysqlNoDockerTest.php` | M06, M07 |
| CT-03 | cada um dos cinco serviços do profile `app` espera o banco | R2 | EP nominal (Esquema) | Feature | `tests/Kit/MysqlNoDockerTest.php` | M08, M09, M10, M51 |
| CT-04 | o MySQL é o único banco atrás de profile, e o profile é só dele | R3 | tabela de decisão | Feature | `tests/Kit/MysqlNoDockerTest.php` | M12–M15 |
| CT-21 | nenhum banco novo entra no profile default | R3 | invariante de cardinalidade | Feature | `tests/Kit/MysqlNoDockerTest.php` | M12, M14, M52 |
| CT-05 | o comando divulgado nomeia serviços, não liga profile | R3 | tabela de decisão | Feature | `tests/Kit/MysqlNoDockerTest.php` | M16, M53 |
| CT-06 | os cinco deixam de fixar o Postgres | R4 | EP + contagem | Feature | `tests/Kit/MysqlNoDockerTest.php` | M18, M19, M20 |
| CT-07 | quem não escolheu nada continua no Postgres | R4 | valor do requisito | Feature | `tests/Kit/MysqlNoDockerTest.php` | M21 |
| CT-20 | a variável do compose é a chave que o instalador grava | R4 | contrato entre artefatos | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M54, M16 |
| CT-08 | a instalação com MySQL grava bloco de banco utilizável | R5 | EP (vazio ≠ preenchido) | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M23, M27, M56 |
| CT-09 | o container cria o banco, lê a senha e não abre sem senha | R5 | partição inválida isolada | Feature | `tests/Kit/MysqlNoDockerTest.php` | M24, M25, M26, M55 |
| CT-10 | nenhum container tem nome fixo no compose | R6 | ausência (com filtro) | Feature | `tests/Kit/MysqlNoDockerTest.php` | M28, M29 |
| CT-11 | a instalação grava o nome do projeto no `.env` | R6 | rastreio de efeito | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M30, M31 |
| CT-23 | a chave sobrevive ao `kit:update` | R6 | premissa travada (A2) | Feature | `tests/Kit/MysqlNoDockerTest.php` | M57 |
| CT-12 | toda classe de nome digitado produz nome válido | R7 | normalização + EP | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M30, M32–M35, M38 |
| CT-13 | o nome sugerido produz o prefixo de hoje | R8 | valor do requisito | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M38 |
| CT-14 | o piso do arquivo continua valendo | R8 | valor do requisito | Feature | `tests/Kit/MysqlNoDockerTest.php` | M37, M58 |
| CT-15 | a chave nova nasce ativa no `.env.example` | R8 | convenção + RQ-06 | Feature | `tests/Kit/MysqlNoDockerTest.php` | M39, M58 |
| CT-22 | a instalação sem customização não muda o prefixo | R8 | caminho negativo de RQ-06 | Feature | `tests/Kit/MysqlNoDockerTest.php` | M58 |
| CT-16 `@premissa` | a chave do banco acompanha a escolha em toda execução | R9 | criação × edição × uso | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M40, M42, M59 |
| CT-17 | reaplicar não duplica nenhuma das chaves novas | R9 | idempotência no agregado | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M41, M59 |
| CT-18 | renomear projeto já instalado renomeia os containers | R10 | criação × edição | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M43, M44, M45 |
| CT-19 | o instalador anuncia o container em vez de negá-lo | R11 | ausência com Reflection + presença | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M46, M47, M48, M60 |
| **VM-01…VM-04** | verificação manual com Docker | R2, R3, R4, R7 | — | **manual** | `03-progresso.md` (evidência colada) | M11, M17, M22, M36 |

**Camada, e por que nenhum cenário é `Unit`**: `tests/Pest.php` liga `TestCase` a `Feature`,
`Kit`, `Tenancy` e `Browser` — **não** a `tests/Unit`. Um caso "unitário" ali roda sem
container: sem `base_path()` para ler o compose, sem `config()` para o alinhamento em memória
que o `aplicar()` faz. A escada real do projeto começa em `tests/Kit`, e é por isso que
CT-01…CT-07 — que só leem texto de arquivo e pareceriam candidatos naturais a `Unit` — moram
onde moram. A escolha também segue o observável, não a estrutura provável do código: o `Então`
de CT-01…CT-07 é sobre o **conteúdo de um arquivo do projeto**, que é `base_path()`.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "o serviço não usa tag flutuante" como cenário próprio | o oráculo ancorado de CT-01 (`^    image: mysql:8\.0$`) já exclui `latest` e a ausência de tag; um cenário só para isso mata o mesmo mutante |
| "o profile do MySQL não é `full`" como cenário próprio | virou a segunda asserção de CT-04; separar custaria um cenário e mataria o mesmo M15 |
| "a senha do compose não é literal" como cenário próprio | a asserção exata de CT-09 (`MYSQL_ROOT_PASSWORD` vem de `${DB_PASSWORD:-secret}`) já reprova qualquer literal |
| "o `.env.docker` ganha o bloco MySQL" | prosa de documentação, oráculo só no plano, e nenhum mutante de comportamento morre com ele. Fica na `## Verificação Final` do `01` como item de revisão, não como CT |
| "o contexto dos dois logs ganha `compose_project`" | oráculo só no plano; e o caso existente que importa (a senha não vaza para o log) continua verde com ou sem o campo |
| "o `CHANGELOG` cita o container MySQL nas duas seções" | mesma razão; o fluxo de release já cobre a forma do arquivo |
| "duas variáveis (`DOCKER_DB_CONNECTION` e `DOCKER_DB_HOST`) em vez de uma" como mutante | não é defeito: a resposta A1 do `00` diz "viram variável", sem número. É escolha de plano (ADR-06 alt 1), e um mutante que não é defeito é enchimento de gate |
| "`name: ${COMPOSE_PROJECT_NAME:-starter-kit}` no YAML" como mutante | funciona; a ADR-03 o recusou por redundância, não por defeito |
| "o sufixo `-1` não aparece sem customização" | desvio de RQ-06 **já declarado** na ADR-04, no `01` e no `CHANGELOG`. Cenário que o proibisse seria a derivação recusando decisão tomada |
| linha `MEU KIT → meu-kit` no `Exemplos:` de CT-12 | **removida na revisão adversarial**: toda implementação que passe na linha `Loja do Ferro` já minusculiza, inclusive o `nomeDeBanco()` que é o mutante principal. Não discriminava nada |
| linha `Kit 🚀 → kit` no `Exemplos:` de CT-12 | **removida na revisão adversarial**: o emoji cai em `Str::slug`, em `iconv`, num `preg_replace` e no `nomeDeBanco()`. Nenhuma implementação plausível diverge, e o rótulo "quatro bytes" prometia um limite de `varchar` que esta feature não tem |
| um cenário só para `MYSQL_RANDOM_ROOT_PASSWORD` | virou a terceira ausência de CT-09; separar mataria o mesmo M55 |
| um cenário para o sufixo `-1` dos containers | ver a nota de R8: desvio já declarado na ADR-04 |
| CT-B de qualquer natureza | ver `## Sem CT-B` |

---

## Sem CT-B

**Motivo**: o `01-plano-acao.md` declara `## Superfície de UI` **vazia**, e a declaração se
sustenta na varredura SFDIPOT feita aqui de forma independente:

1. **Nada nesta entrega renderiza HTML.** Não há rota, painel, Resource, Page, widget,
   formulário nem componente Livewire novo ou alterado. As dimensões **S** e **I** da varredura
   confirmam: os artefatos são um YAML de infra, um `.env.example`, uma classe de suporte e
   arquivos de documentação.
2. **A única interface humana é TTY.** O `kit:install` é comando; as perguntas rodam nos Prompts
   do Laravel dentro do `composer create-project`, e o docblock do vizinho
   `tests/Kit/CustomizadorDaInstalacaoTest.php` já registra que **nenhum** teste automatizado
   alcança essa camada — o oráculo ali é a decisão, não o efeito de terminal. Navegador não
   melhora isso; não há navegador envolvido.
3. **O que sobra de "só o ambiente prova" não é browser, é Docker.** Os quatro mutantes sem
   matador precisam de daemon, não de Playwright, e estão em `## Lacunas Declaradas` com o
   comando manual de cada um.

Portanto **não existe** `05-casos-de-teste-browser.md`, e o gate do `05` não foi reprovado por
escolha de escopo — ele não se aplica.

---

## Fechamento do Ciclo com Mutation Testing

Comando (escopado, como a skill exige, e com o filtro que funciona de forma confiável):

```bash
vendor/bin/pest tests/Kit/CustomizadorDaInstalacaoTest.php --mutate --path=app/Support/CustomizadorDaInstalacao.php
```

`pestphp/pest-plugin-mutate` está declarado **diretamente** em `require-dev` do
`composer.json` (`^5.0`) — não é transitiva, e o comando não funciona por acidente da árvore.

**Duas advertências específicas desta feature, e a segunda é grande:**

1. O comando exige driver de cobertura. A rule `.ai/rules/testes-browser.md` mediu que, sem
   **PCOV**, análise com cobertura neste ambiente é inviável (`--tia` abortado após 35 min).
   Enquanto o ambiente não tiver PCOV, o `--mutate` roda escopado a **um** arquivo ou não roda.
2. **O mutation score é cego a 14 dos 23 cenários desta feature.** CT-01 a CT-07, CT-09, CT-10,
   CT-14, CT-15 e CT-21 a CT-23 afirmam sobre **YAML e Markdown**, e mutation testing só muta
   **PHP**. Nenhum mutante é gerado para o `docker-compose.yml`, e portanto o score não cai se o
   serviço MySQL for removido inteiro do arquivo. O que responde por essa metade é a
   rastreabilidade `RQ` → regra → cenário e o gate de mutantes **de especificação** deste
   documento — não a ferramenta. Um `MSI` alto aqui não é evidência de nada sobre a metade infra
   da entrega, que é justamente a metade que RQ-01 a RQ-04 pedem.

Mutante sobrevivente na parte PHP se traduz de volta assim:

| Sobreviveu | Lacuna de derivação | O que escrever |
|---|---|---|
| literal `'starter-kit'` → `''` no piso do slug | falta a partição do vazio | já coberto por CT-12 linha `###`; se sobreviver, a asserção está no invariante e não no valor |
| `Str::slug($nome, '-')` → `Str::slug($nome)` | separador não verificado | CT-12 linha "espaço" afirma o hífen; se sobreviver, o exemplo deixou de ser discriminante |
| chamada de `definirNoEnv` para a chave nova removida | efeito colateral não verificado | CT-11 e CT-18 são os rastreios; se sobreviver, um dos dois não afirma sobre a chave |
| `match` do ramo `mysql` → ramo default | partição de banco não coberta na gravação | CT-08 e CT-16 |

---

## Revisão Adversarial

**Rodada 1** — executada por sub-agente independente, que recebeu **apenas** `00-requisito.md` e
a primeira versão deste arquivo. Sem o `01`, sem o `02`, sem o código, sem os arquivos de teste
e sem o raciocínio de quem derivou. O contrato foi o da skill: *provar que este conjunto deixa
passar defeito*, com proibição explícita de elogiar ou de dizer que está bom.

**21 achados. Todos fechados.** Saldo: **4 cenários novos**, **9 oráculos reescritos**, **12
mutantes acrescentados**, **2 linhas de `Exemplos:` removidas** por não discriminarem nada.

### Os cinco achados que mais doeram

| # | Achado | O que era | O que virou |
|---|---|---|---|
| 1 | **O par chave-do-`.env` × variável-do-compose nunca era amarrado.** Ao recusar o nome `DOCKER_DB_SERVICE` como oráculo (decisão correta), o conjunto ficou sem ninguém ligando as duas pontas: o compose podia interpolar uma chave e o instalador gravar outra, com a aplicação containerizada rodando **sempre** em Postgres e todos os cenários verdes | — | **CT-20** (novo) + M54 |
| 2 | **O invariante "exatamente um banco fora de profile" estava na prosa e não em nenhum `Então`.** CT-04 falava de dois serviços nominados, então trazer também os `mysql_test`/`mysql_dusk_test` da referência sem profile passava — e isso é a **premissa A4** do `00`, que tinha consequência declarada e zero cenários | — | **CT-21** (novo) + M52 |
| 3 | **Contagens globais em vez de nominais.** CT-03 contava cinco ocorrências no arquivo inteiro; cinco serviços **errados** (com `nginx` no lugar de `pulse`) fechavam a conta. E o `00` **nomeia** os cinco na resposta A1 — a derivação tinha convertido uma lista fechada num número | contagem `= 5` | **CT-03** vira `Esquema` com uma linha por serviço + M51 |
| 4 | **CT-16 era inteiramente negativo**, e "a chave não vale `<antes>`" é satisfeito pela **ausência** da chave. Uma implementação que nunca a gravasse passava nas três linhas — inclusive na rotulada "a chave tem de passar a existir" | só negativas | coluna `estado esperado`, com valor positivo por linha + M59 |
| 5 | **Asserção verde antes da entrega.** `restart: unless-stopped` já aparece dez vezes no compose de hoje; varrendo o arquivo, aquela linha do `Exemplos:` de CT-01 é verdadeira com ou sem o serviço MySQL. E faltava o atributo que torna o banco **utilizável** (`MYSQL_DATABASE`), sem o qual o container sobe saudável e o `migrate` morre com "Unknown database" | asserção sobre o arquivo | **recorte de bloco** no `Dado` + linha nova + M49, M50, M56 |

### Os demais, em uma linha cada

| Achado | Fechamento |
|---|---|
| CT-02 era **autocontraditório**: `$$MYSQL_ROOT_PASSWORD` contém `$MYSQL_ROOT_PASSWORD`, então a negativa reprovaria a implementação correta | negativa com olhar-para-trás (`(?<!\$)`) e `Dado` recortado no `healthcheck:` |
| CT-05 cobria três textos e a varredura **S** lista quatro arquivos de documentação — as duas páginas de `docs/` (as que o site publica) estavam fora | universo de cinco textos + M53 |
| CT-05 proibia um **literal**; `--profile mysql up`, `… --build` e `COMPOSE_PROFILES` passavam | proibição por proximidade, não por frase |
| CT-09 não proibia `MYSQL_RANDOM_ROOT_PASSWORD`, a **terceira** saída que a mensagem da imagem sugere — senha aleatória divergente do `.env` | ausência acrescentada + M55 |
| CT-11 afirmava o valor **pós-parser** e CT-18 a linha **crua**: convivendo, o par ficava cego ao defeito da pergunta A8 | os dois passam a afirmar a linha crua; a forma citada fica declarada como premissa |
| CT-14 e CT-15 eram presença de substring — `# name: starter-kit` e `# COMPOSE_PROJECT_NAME=…` satisfaziam, e a chave nasceria inerte | "linha não comentada" nos dois + M58 |
| CT-17 contava 1 linha, que podia ser **herdada** do `.env.example` sem escrita nenhuma | `Dado` parte de um `.env` sem as chaves, e o valor da segunda execução é afirmado |
| CT-19 proibia uma frase em português, contornável por sinônimo que continuasse negando o container | asserção de **presença** do fato novo + proibição da negação por proximidade + M60 |
| RQ-06 só tinha o caminho positivo ("aceitei o nome sugerido"); o caminho literal da cláusula — não customizar nada — não era exercido | **CT-22** (novo) |
| A2 ("sobrevive ao `kit:update`") sustentava o mecanismo inteiro e não tinha cenário | **CT-23** (novo), três linhas |
| `COMPOSE_PROFILES` no `.env` — uma das saídas que a própria pergunta A7 propõe — faria `up -d` puro subir dois bancos, e nenhum cenário lia essa chave | M61 ⚠️ declarado + **VM-05** condicional |
| RQ-03 não é falsificável por teste nenhum, e isso não estava declarado | ressalva escrita na rastreabilidade, com o que foi coberto no lugar |
| `MEU KIT` e `Kit 🚀` não discriminavam nada (toda implementação plausível acerta as duas) | trocadas por **aspas** e **cifrão**, que atravessam duas transformações |
| CT-01: `  mysql:` casa também uma entrada do bloco `volumes:` de topo | asserção sobre a chave sob `services:`, com corpo |

### Segunda rodada: recusada, com motivo

A skill permite uma segunda rodada quando o fechamento **cria cenário novo** — e criou quatro.
Ela está **dispensada** aqui, e a razão é a natureza dos quatro:

- **CT-20, CT-21 e CT-23 são asserções de invariante sobre artefatos que os cenários existentes
  já liam** (o compose, o `.env`, a lista de caminhos do `kit:update`). Eles não introduzem
  superfície nova; fecham buracos em superfície já visitada, que é onde a lacuna de segunda
  ordem não costuma morar.
- **CT-22 é o caminho negativo de uma regra existente**, e sua asserção é a conjunção das duas
  fontes que CT-14 e CT-15 já afirmam separadamente.

O que **de fato** ficou aberto depois da revisão não é lacuna de derivação: são as **cinco
perguntas** para o `00` (A6 a A11), e nenhuma segunda rodada as fecha — só o mantenedor fecha.
Rodar mais uma passada antes de A6 e A7 serem respondidas produziria cenários sobre um
comportamento que ainda não foi decidido. **O teto de duas rodadas fica com uma em reserva**,
para depois das respostas.
