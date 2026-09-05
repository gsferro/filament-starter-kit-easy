# Progresso — MySQL no Docker do kit, e o nome do projeto no lugar de `starter-kit`

> Wiki criada em 2026-09-02, na branch `feat/mysql-no-docker`.
> **Fidelidade do requisito: alta** — texto escrito, colado verbatim no `00`. As duas
> ambiguidades materiais (A1, o profile `app` com MySQL; A2, o mecanismo do nome) foram
> **respondidas pelo mantenedor** antes de o plano ser escrito. A3, A4 e A5 seguem como premissas
> assumidas, com o "Se negado" registrado.

## 0. Antes de implementar

- [x] Requisito capturado verbatim e decomposto em RQ-01..RQ-06
- [x] Arquivo de referência do mantenedor lido na íntegra (RQ-03), e o que foi herdado/recusado
      está item por item na ADR-02
- [x] Levantamento do código: `docker-compose.yml`, `CustomizadorDaInstalacao`,
      `KitUpdate::CAMINHOS_DO_KIT`, `.env.example`, `.env.docker`, `config/database.php`
- [x] **Oito medições com Docker real** (29.7.2 / Compose v5.5.0 / `mysql:8.0`), todas com saída
      lida — a tabela está na seção "Auditoria Pré-Implementação"
- [x] **A1 respondida**: o profile `app` entra no escopo, parametrizado
- [x] **A2 respondida**: `COMPOSE_PROJECT_NAME` no `.env`, não reescrita do YAML
- [ ] Confirmar **A3** (o "nome do services" é o prefixo dos containers e o nome do projeto),
      **A4** (só um container MySQL, sem os de teste) e **A5** (o instalador passa a gravar senha)
- [x] `04-casos-de-teste.md` recebido da `feature-test-design` — 11 regras, 23 cenários, 61 mutantes (56 com matador, 5 declarados só-com-Docker), revisão adversarial com 21 achados fechados
- [x] Seis perguntas devolvidas pela derivação triadas — ver *Desvios do Plano*

## 1. O serviço `mysql` no `docker-compose.yml`

- [x] Bloco entre o `pgsql` e o `redis`, na seção "Infra base"
- [x] `profiles: [mysql]` — e **não** em `full` (subiria os dois bancos juntos)
- [x] `MYSQL_DATABASE` e `MYSQL_ROOT_PASSWORD` lidos do `.env`; **sem** `MYSQL_USER` (ADR-05)
- [x] Healthcheck com `mysqladmin ping` e o dólar dobrado (a variável é lida dentro do container)
- [x] `mysql-data:` acrescentado ao `volumes:` do fim do arquivo
- [x] Comentário registra por que este é o único banco com profile, e o comando de subida
- [x] **Nenhuma menção a `config:cache` ou `route:cache` nos comentários novos** — o vizinho
      `tests/Kit/CacheDeViewsNoDockerTest.php` faz asserção de ausência sobre esses dois

## 2. O profile `app` deixa de fixar o Postgres

- [x] `DB_CONNECTION` e `DB_HOST` por `${DOCKER_DB_SERVICE:-pgsql}` nos **cinco** serviços
      (`app`, `queue`, `scheduler`, `reverb`, `pulse`)
- [x] Comentário único, no serviço `app`, explicando a variável e a coincidência de nomes
- [x] `depends_on` dos cinco ganha `mysql` com `required: false`
- [x] O `pgsql` **continua** no `depends_on` — tirá-lo produz aplicação sem banco (ADR-01)

## 3. `COMPOSE_PROJECT_NAME` substitui os onze `container_name:`

- [x] As onze linhas `container_name:` removidas
- [x] `name: starter-kit` **mantido** como piso
- [x] Comentário junto do `name:` dizendo de onde vem o prefixo
- [x] `.env.example` ganha `COMPOSE_PROJECT_NAME=starter-kit`

## 4. O instalador escreve as duas chaves novas

- [x] `nomeDeProjetoDocker()` privado, ao lado de `nomeDeBanco()` — e **não** reusando aquele
- [x] `COMPOSE_PROJECT_NAME` escrito em `aplicar()`
- [x] `COMPOSE_PROJECT_NAME` escrito em `aplicarSemBanco()` (o `--custom` também renomeia)
- [x] `DB_PASSWORD` do ramo MySQL passa de vazio para `secret`
- [x] ~~`DOCKER_DB_SERVICE` no ramo MySQL~~ — **cortado** pela auditoria (item 1); a chave fica comentada no `.env.docker`
- [x] Docblock de `aplicarBanco()` reescrito — ele afirma que o kit não sobe container MySQL
- [x] Rótulo da opção MySQL em `perguntarBanco()` reescrito — mesma afirmação
- [x] `compose_project` no contexto dos dois logs existentes; nenhuma mensagem nova, nenhum
      channel novo

## 5. Documentação e changelog

- [x] `.env.docker` — bloco MySQL e `COMPOSE_PROJECT_NAME`
- [x] `README.md` — tabela de serviços e bloco de comandos
- [x] `README.en.md` — o mesmo, em inglês
- [x] `docs/pt/comecar/instalacao-avancada.md` — o container, o comando, a nota do volume ao
      renomear, e como projeto já instalado recebe a chave
- [x] `docs/en/comecar/instalacao-avancada.md` — o mesmo, em inglês
- [x] `CHANGELOG.md` → `### Adicionado` **e** `### Alterado` (são três mudanças de comportamento)
- [x] Conferir se outra branch editou os mesmos arquivos nesta rodada (as quatro anteriores
      deram conflito em `CHANGELOG.md`)

## Testes

- [x] `04-casos-de-teste.md` derivado do `00` pela `feature-test-design`
- [x] `tests/Kit/MysqlNoDockerTest.php` (**novo**) — CT-01…CT-07, CT-09, CT-10, CT-14, CT-15,
      CT-20, CT-21, CT-22, CT-23. **28 casos, 69 asserções, verde**
- [x] `tests/Kit/CustomizadorDaInstalacaoTest.php` (**ampliado**) — CT-08, CT-11…CT-13, CT-16…CT-19.
      **61 casos, 142 asserções, verde** (eram 45)
- [x] Nenhum helper novo em `tests/Pest.php`: o recorte de bloco e o filtro de comentário são *closures* locais do arquivo novo, e a regra é sobre helper usado por mais de um arquivo
- [x] **Sem CT-B** — superfície de UI vazia, e o que sobra exige daemon, não navegador

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — segunda auditoria, tabela própria acima
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse`
- [x] `php artisan test --compact tests/Kit/CacheDeViewsNoDockerTest.php`
- [x] `php artisan test --compact tests/Kit/CustomizadorDaInstalacaoTest.php tests/Kit/TenancyNaInstalacaoTest.php`
- [x] `php artisan test --compact tests/Kit/KitInfoTest.php tests/Kit/KitUpdateTest.php`
- [x] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`
- [ ] ~~`vendor/bin/pest --parallel --tia`~~ — **não rodou**, e o motivo fica registrado: a
      suíte completa leva ~92 min nesta máquina e o limite de tarefa em segundo plano a matou
      duas vezes, sem saída. Trocado por alvo dirigido: **todo** arquivo de teste que lê algum
      artefato tocado pelo diff, achado por varredura (`.env.example`, `docker-compose.yml`,
      `.env.docker`, `DB_PASSWORD`, os READMEs, as páginas de instalação avançada) — 14 arquivos,
      todos verdes. Somando as rodadas: **528 casos**, nenhum vermelho
- [x] À mão, com Docker de verdade — **ciclo fechado**, ver a tabela de verificação abaixo
- [x] `git commit`

## Auditoria Pré-Implementação

### Medições com Docker real (o que substituiu suposição por evidência)

Oito sondas, todas com saída lida. Elas mudaram o desenho em três pontos, e cada uma está citada
na ADR que ela sustenta.

| # | Sonda | Resultado | O que decidiu |
|---|---|---|---|
| 1 | `COMPOSE_PROFILES=mysql` no `.env`, `docker compose up -d` | sobe `mysql` + `redis` | a chave funciona… |
| 2 | o mesmo `.env`, mas `--profile ai up -d` | sobe `llamacpp` + `redis` — **o banco desaparece** | …e é inútil aqui: o flag **substitui** o `COMPOSE_PROFILES`, e todo comando documentado do kit usa flag. Matou a alternativa 1 da ADR-01 |
| 3 | `docker compose up -d mysql redis` com `mysql` em profile e `pgsql` no default | cria **só** os dois containers nomeados | é o comando da entrega. Serviço nomeado liga o próprio profile e restringe a subida |
| 4 | os dois bancos em profile, `app` com `depends_on required:false` nos dois, `--profile app up -d` | sobe `app` + `redis`, **sem banco nenhum**, sem erro | matou a alternativa "simetria total". É por isso que o `pgsql` fica no `depends_on` |
| 5 | `COMPOSE_PROJECT_NAME=minha_app` no `.env` | `docker compose config` responde `name: minha_app` | vence o `name:` do arquivo — base da ADR-03 |
| 6 | quatro nomes de projeto (`2minha-app`, `minha_app-1`, `Minha-App`, `minha app`) | os dois primeiros aceitos; os dois últimos recusados com *"must consist only of lowercase alphanumeric characters, hyphens, and underscores as well as start with a letter or number"* | `Str::slug` basta, e `nomeDeBanco()` **não** serve (ele conserta dígito inicial e hífen, que o Compose aceita) |
| 7 | `docker run mysql:8.0` sem senha, e com `MYSQL_USER=root` | recusa nos dois casos, com o texto citado na ADR-05 | fixou `MYSQL_ROOT_PASSWORD` sem `MYSQL_USER`, e forçou o instalador a gravar senha |
| 8 | `COMPOSE_PROJECT_NAME="minha-app"` **com aspas**, que é como `SubstituicaoEmArquivo::definirNoEnv()` grava toda chave | `name: minha-app` — o Compose remove as aspas | validou o método do passo 4b. Se ele mantivesse as aspas, o nome seria inválido (aspas não estão no conjunto aceito) e o passo teria de usar `aplicar()` com a linha crua |

### Verificação com Docker de verdade (pós-implementação)

Não é sonda em compose de mentira: é o arquivo do kit, containers de verdade, migração de verdade.

| O que | Resultado |
|---|---|
| `docker compose config -q` no arquivo do kit | sem erro de sintaxe nem de interpolação |
| `docker compose up -d` (sem argumento) | `starter-kit-pgsql-1` + `starter-kit-redis-1` — o comportamento de hoje, preservado |
| `docker compose up -d mysql redis` | `starter-kit-mysql-1` + `starter-kit-redis-1`, e **nenhum Postgres**. É RQ-02 provada em container, não em `--dry-run` |
| Porta 3306 ocupada na máquina | o erro veio claro (*"Bind for 0.0.0.0:3306 failed: port is already allocated"*) e `FORWARD_DB_PORT=3399` resolveu — o knob existente cobre o caso |
| Healthcheck | `healthy` em ~30s. A expressão com o dólar dobrado funciona |
| `SHOW DATABASES` com `root`/`secret` | o banco `starter_kit` existe e as credenciais que o instalador grava conectam |
| `php artisan migrate --force` contra o container | **todas as migrations rodaram**, incluindo o ramo `mysql` da de Pulse |
| `docker compose --profile app config` sem variável | `DB_CONNECTION: pgsql`, `DB_HOST: pgsql` — idêntico ao anterior |
| o mesmo com `DOCKER_DB_SERVICE=mysql` | `DB_CONNECTION: mysql`, `DB_HOST: mysql` |
| `COMPOSE_PROJECT_NAME=minha-app` | `name: minha-app`, e os containers herdam o prefixo |

Containers e o volume `mysql-data` foram removidos depois; o `.env` do projeto não foi tocado (as
variáveis da migração foram passadas por ambiente).

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "o instalador reescreve o `docker-compose.yml`" (primeira leitura do requisito) | **não reescreve nada de YAML**: `CustomizadorDaInstalacao` só toca `.env` e dois configs (`grep` por `base_path`/`config_path` na classe) | ADR-03 escolheu o `.env` como veículo, e o plano não inventa reescrita de YAML |
| "posso reusar `nomeDeBanco()` para o nome do projeto" | ele troca hífen por underscore e prefixa underscore em dígito inicial — as duas coisas que o Compose **aceita** (sonda 6). Reusar produziria `minha_app` onde o nome era `minha-app` | passo 4a cria método próprio de três linhas, com o docblock explicando a diferença |
| "`.env.example` chega a quem já instalou pelo `kit:update`" | **falso**: só `docker-compose.yml` está em `CAMINHOS_DO_KIT` (`:231`); `.env.example` e `.env.docker` não estão em nenhuma das duas listas | consequência documentada na ADR-03 e no plano: projeto instalado recebe a chave por `kit:install --custom`, e é por isso que o passo 4c existe |
| "o nome do serviço é `starter-kit`" (a letra do requisito) | nenhum serviço se chama assim; o literal está no `name:` de topo e em onze `container_name:` | premissa A3 no `00`, com "Se negado" — e o alvo do passo 3 é explícito |
| "basta acrescentar o serviço; o profile `app` não muda" | os cinco serviços do profile `app` **fixam** `DB_CONNECTION: pgsql` e `DB_HOST: pgsql` no `environment:`, que vence o `env_file` | virou pergunta A1 ao mantenedor em vez de premissa silenciosa; respondida "parametrizar", e é o passo 2 |
| "`DB_PASSWORD` vazio funciona, o compose tem fallback" | **perigoso**: `${DB_PASSWORD:-secret}` substitui quando a variável está vazia, então o container nasceria com `secret` e a aplicação conectaria com vazio — "Access denied" sem pista | ADR-05, alternativa 2, e o passo 4d muda o instalador |

### Auditoria Ponytail (step 6)

Revisor independente auditou os três arquivos do plano e devolveu `net: -205 lines possible`.

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | **Cortar o passo 4e** (instalador grava `DOCKER_DB_SERVICE=mysql`) | **sim, e é o achado da auditoria** | gravar a chave faria quem escolheu MySQL e rodasse `--profile app` **sem** `--profile mysql` apontar o `DB_HOST` para um container que não subiu — a mesma degradação silenciosa que a ADR-01 recusou por medição, reintroduzida pela porta dos fundos. A chave passa a viver comentada no `.env.docker` |
| 2 | Encolher o comentário do serviço `mysql` (11 → 5 linhas): fica o que é medido e não se lê no código, sai o que é a ADR-01 copiada | sim | `docker-compose.yml`, bloco `mysql` |
| 3 | Docblock de `nomeDeProjetoDocker()` de 10 linhas para uma, com ponteiro para a ADR-03 | sim | `CustomizadorDaInstalacao.php` |
| 4 | Encolher a lista de Verificação Final (cinco `artisan test` nominais que o `--tia` já cobre) | sim | `01` e este arquivo |
| 5 | **Remover `COMPOSE_PROJECT_NAME` do `.env.example`** — "o `name:` do YAML já é o piso e `definirNoEnv` faz append" | **NÃO — e a suíte provou que a sugestão estava errada** | sem a chave no template, `definirNoEnv` **anexa** em vez de substituir, e o `.env` ganha uma linha. Sete casos caíram: `it('substitui a chave já preenchida sem tocar no resto do arquivo')` conta linhas, e os seis de `it('grava nome hostil sem quebrar o arquivo nem criar chave nova')` contam chaves. Com a linha no `.env.example` a chave nasce no lugar certo, ao lado de `APP_NAME`, e **nenhum oráculo precisou afrouxar** |
| 6 | Inline de `nomeDeProjetoDocker()` nos dois chamadores | **não** | são dois chamadores que precisam concordar no piso `'starter-kit'`. Inline duplica a decisão em dois lugares que ninguém vai lembrar de manter juntos. Aceito só o corte do docblock (item 3) |
| 7 | Cortar os cinco `depends_on: mysql / required: false` (20 linhas de YAML) | **não** | sem eles, a aplicação do profile `app` sobe quando o **Postgres** fica saudável, não quando o banco que ela realmente usa fica. Para quem usa MySQL isso é worker morrendo no boot. Health gate no banco correto não é cerimônia |
| 8 | Cortar a duplicação entre os arquivos da wiki (`01` "Contexto", os dois snippets de log, o "Postgres ocioso" escrito quatro vezes, a narração da escada) | **parcial** | os apontamentos são justos e a wiki encolhe na próxima passada; nesta rodada priorizei os itens que mudam código. Registrado como dívida da wiki, não do produto |
| 9 | Documentação: cortar metade das edições por idioma (README resumido, site com a prosa completa) | sim | README ficou com a linha da tabela, o comando e um ponteiro; as três seções longas (MySQL, nome dos containers, profile `app`) foram só para o site, nos dois idiomas |

### Auditoria Ponytail do DIFF (pós-implementação)

Segunda auditoria, agora sobre o código (`+347/-33`, 9 arquivos), com revisor independente.
Devolveu `net: -66` e — mais importante — **um defeito de correção que nenhuma das minhas oito
sondas tinha pegado**.

| # | Achado | Aplicado? |
|---|---|---|
| **1** | **`CORREÇÃO:` `pgsql` e `mysql` publicavam os dois em `${FORWARD_DB_PORT}`.** O risco tinha sido aceito na premissa "os dois bancos nunca sobem juntos" — e **este diff falsificou a premissa**, porque o profile `app` com MySQL sobe o Postgres ocioso junto. **Confirmado**: com `FORWARD_DB_PORT=3306` no `.env`, `docker compose --profile app --profile mysql config` mostra os dois publicando em `3306`, e um morre com "port is already allocated" | **sim** — o MySQL ganhou `FORWARD_MYSQL_PORT` (default 3306). Reconferido: por default agora é `5432` e `3306`, e `FORWARD_MYSQL_PORT=3399` move só o MySQL. Documentado nos dois idiomas, com o texto exato do erro — que é o mesmo que a minha própria verificação tinha encontrado, e que eu tinha lido como acidente da máquina em vez de sintoma |
| 2 | `.env.docker` não devia repetir `COMPOSE_PROJECT_NAME`: a chave já está no `.env.example`, e este arquivo é o que **falta** no `.env` | sim, bloco removido |
| 3 | Comentários repetindo a mesma frase em quatro lugares (compose, `.env.docker`, doc, CHANGELOG) | sim: comentário do `name:` 6→3 linhas, do serviço `mysql` 11→4, do `DOCKER_DB_SERVICE` 12→3, `.env.docker` 8→2 e 10→2 |
| 4 | Docblocks inflados no PHP: `aplicarBanco()` 10→4 linhas, `nomeDeProjetoDocker()` 4→1 | sim |
| 5 | Parágrafo do README duplicando, no mesmo arquivo, a tabela e o bloco de comandos da seção Docker | sim, virou uma linha com ponteiro para a seção |
| 6 | Bullet da senha no CHANGELOG era a terceira cópia integral do comentário do compose | sim, encolhida |
| 7 | Remover o contexto `compose_project` do log de `aplicarSemBanco()` (valor derivado do `APP_NAME`) | **não** — é o valor que foi **efetivamente escrito no disco**. Se o slug produzir algo inesperado, é a única linha que mostra o quê |
| 8 | Inline de `nomeDeProjetoDocker()` | **não**, e o próprio revisor concordou: dois chamadores, e o piso `'starter-kit'` tem de casar com o `name:` do compose |
| — | Pergunta "o que o Compose/Laravel já fazem sozinhos e você escreveu à mão?" | **nada** — `depends_on`/`required: false`, o profile único, a ausência de `container_name` e a interpolação `${DOCKER_DB_SERVICE:-pgsql}` são todos mecanismo nativo |

**A lição do item 1**, que vale além desta feature: a premissa que sustentava um risco aceito era
verdadeira **antes** do diff, e o próprio diff a derrubou. Risco aceito precisa ser relido quando a
feature muda a condição que o tornava aceitável — e eu não reli.

## Blockers

- [x] ~~`04-casos-de-teste.md` pendente~~ — recebido e implementado nos dois arquivos de teste.
- [x] ~~Auditoria Ponytail pendente~~ — as **duas** rodaram: a do plano (step 6) e a do diff.
- [ ] **A3, A4 e A5 seguem como premissa assumida**, com o "Se negado" escrito no `00`. Nenhuma
      bloqueia o que foi entregue: A3 e A4 se confirmaram na prática (o prefixo dos containers é
      o que aparece em `docker compose ps`; nenhum container de teste foi necessário), e A5 foi
      **medida na imagem** antes de virar código.

## Desvios do Plano

| # | Desvio | Por quê |
|---|---|---|
| 1 | **Passo 4e cortado** — o instalador não grava `DOCKER_DB_SERVICE` | auditoria Ponytail do plano, item 1. Gravar a chave criaria a degradação silenciosa que a ADR-01 recusou por medição. CT-16 foi **adaptado** para travar a decisão: ele agora afirma que a chave NÃO é gravada, nos três bancos |
| 2 | **CT-20 mudou de ponta** | o cenário derivado amarrava a variável do compose à chave que o instalador gravava. Sem essa escrita, o contrato passou a ser compose ↔ `.env.docker` — a variável interpolada tem de ser a documentada. A técnica é a mesma (amarrar as duas pontas sem fixar o nome como oráculo); mudou o par |
| 3 | **CT-17 perdeu a segunda asserção** | ela era sobre a chave do passo 4e |
| 4 | **CT-01 espera `FORWARD_MYSQL_PORT`, não `FORWARD_DB_PORT`** | é a correção do defeito que a auditoria do diff achou (A11 da derivação, confirmada). O `04` foi escrito antes da correção |
| 5 | **CT-09 espera `${DB_PASSWORD:-}` e não `${DB_PASSWORD:-secret}`** | é a correção de A9. O default com valor atingiria todo projeto MySQL já instalado, que tem senha vazia no `.env` |
| 6 | **`.env.example` ganhou a chave, contra a auditoria** | sem ela o escritor de `.env` anexa em vez de substituir, e sete oráculos existentes caem. A auditoria não tinha como saber; a suíte tinha |

### As seis perguntas da derivação, e o que foi feito com cada uma

| # | Pergunta | Situação |
|---|---|---|
| **A6** | `DOCKER_DB_SERVICE` gravada só no ramo `mysql` sobrevive a uma troca de banco e aponta para host morto | **resolvida antes de aparecer**: a auditoria do plano já tinha cortado a escrita. CT-16 trava |
| **A7** | `--profile app` sozinho não liga o profile `mysql`: aplicação de pé apontando para host inexistente | **mitigada, não eliminada**. O comando correto (`--profile app --profile mysql`) está no comentário do compose, no `.env.docker` e nas duas páginas de doc. Só entra nesse estado quem define a variável à mão, e os três lugares que ensinam a defini-la ensinam junto o profile |
| **A8** | a medição do nome do projeto não cobria a forma **com aspas** que o escritor de `.env` produz | **medida e fechada**: a chave com aspas produz o nome sem elas. É a sonda 8 |
| **A9** | `kit:update` leva o compose novo a um projeto MySQL com senha vazia — container com `secret`, aplicação com vazio | **corrigida**: o default virou vazio, e a imagem passa a recusar subir com mensagem explícita em vez de subir divergente. Falha barulhenta vence falha silenciosa |
| **A10** | `depends_on.required` exige Compose recente e o kit não declara versão mínima | **aceita como está**: o `required: false` já era usado no kit antes desta feature (`reverb` e `pulse` dependendo do `app`), então a exigência não é nova |
| **A11** | `FORWARD_DB_PORT` com dois consumidores | **corrigida**: o MySQL ganhou `FORWARD_MYSQL_PORT`. Foi também o achado da auditoria do diff, por caminho independente |

## Notas de Implementação

- **Duas auditorias independentes chegaram ao mesmo defeito por caminhos diferentes.** A
  derivação dos casos de teste levantou `FORWARD_DB_PORT` como pergunta A11, e a auditoria do
  diff o levantou como `CORREÇÃO:`. Nenhuma das minhas oito sondas o pegou, porque todas
  testavam um banco de cada vez — e a condição do defeito é os dois juntos, que é o estado que
  esta própria feature criou.
- **O `.env.example` decidiu uma disputa entre duas auditorias.** A do plano mandou tirar a
  chave de lá; a suíte reprovou sete casos. Auditoria de plano raciocina sobre o texto do plano;
  o teste executa o código. Quando discordam, quem executa ganha.
- **Python reescrevendo arquivo em Windows converte LF para CRLF em silêncio**, e o
  `.gitattributes` do repo é `* text=auto eol=lf`. O parser de front matter do
  `SiteDeDocumentacaoTest` exige `---
` e reprovou quatro arquivos. `git diff` **não** mostra o
  problema, porque `core.autocrlf=true` normaliza na comparação.

## Retrospectiva

- **Funcionou bem**: medir em vez de citar documentação. Duas das oito sondas mataram
  alternativas que pareciam melhores no papel — a escolha por `COMPOSE_PROFILES` (sonda 2) e a
  simetria entre os dois bancos (sonda 4) — e a sonda 4 mostrou um modo de falha **silencioso**,
  que é justamente o tipo que passa por revisão de código. Nenhuma das duas teria sido descartada
  por leitura de documentação.
- **Funcionou bem**: levar A1 ao mantenedor em vez de assumir. A leitura estreita do requisito
  ("adicione o container") era defensável e teria entregado uma feature pela metade dentro do
  mesmo arquivo.
- **Faltou no plano**: nada ainda.
