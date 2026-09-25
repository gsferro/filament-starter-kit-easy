# Plano de Ação — Cobertura de testes medida, com meta e badge

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (nenhuma; é infraestrutura de qualidade, não evolução de feature)
- **Motivo**: o kit nunca mediu cobertura, porque nunca teve driver instalado
- **Toca infra compartilhada?**: **sim** → `composer.json` (script novo), `.github/workflows/ci.yml`
  (job novo), `README.md`/`README.en.md` (badge e tabela de qualidade), e o `php.ini` da máquina

> Regressão **obrigatória** mesmo sendo "nova", pela regra da `## Natureza da Wiki`: o workflow e o
> `composer.json` são consumidos por toda feature.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | suíte roda com medição de cobertura | 1, 3 | driver + comando |
| RQ-02 | o número reflete **quanto do kit** está coberto | 2 | `<source>app</source>`, já declarado — ADR-03 |
| RQ-03 | driver instalado no PHP local | 1 | **feito e verificado** antes de escrever este plano |
| RQ-04 | tempo de execução medido e registrado | 3 | serial + PCOV; ADR-02 |
| RQ-05 | "melhor forma" definida: comando, quando, onde | 3, 5 | `composer test:coverage` + job de CI |
| RQ-06 | número mínimo aceitável, justificado | 4 | **nasce da medição do passo 3** |
| RQ-07 | o kit atinge a meta | 6 | fechar lacuna real, não subir número |
| RQ-08 | tudo na documentação, explicitamente | 7 | `docs/pt` + `docs/en` + README |
| RQ-09 | badge no README, visível no GitHub | 5, 7 | shields.io *endpoint* sobre JSON versionado — ADR-04 |

## Objetivo

Dar ao kit uma medida **real e reproduzível** de quanto do seu código de produção (`app/`) é
exercitado pela suíte, com um piso que reprova quem baixar dele, e deixar esse número visível —
no README, na documentação e no histórico do git.

O kit tem 2.904 casos de teste e nunca soube quanto eles cobrem. A crítica externa apontou
exatamente isso: *"não há como validar de fora se esses testes realmente cobrem os caminhos
críticos ou se são majoritariamente triviais que inflam a contagem"*. A desconfiança é legítima
enquanto o número não existe.

## Contexto

A infraestrutura de medição **não existia**: nenhum driver de cobertura instalado, nenhum script,
`coverage: none` nos três jobs do CI. A mesma ausência bloqueava o `--tia` do Pest 5 e o
`pest --mutate` — registrada como achado QA-36 no quality gate da feature `rodape-coerente`.

## Análise dos Arquivos Existentes

### `phpunit.xml`
Já declara `<source><include><directory>app</directory></include></source>`. **Não será tocado** —
a premissa do `00` virou fato verificado (ADR-03).

### `composer.json`
Tem `test`, `test:kit`, `test:kit:serial`, `test:browser`. Ganha **`test:coverage`**. O
`test:kit:serial` já existe e é o precedente de um comando serial declarado.

### `.github/workflows/ci.yml`
Três jobs (`qualidade`, `telas`, `instalacao`), todos com `coverage: none`. Ganha um **quarto**,
`cobertura`, com `coverage: pcov`.

### `README.md` / `README.en.md`
Têm a tabela `## Qualidade` e uma fileira de badges. Os números da tabela são guardados por
`tests/Kit/SiteDeDocumentacaoTest.php` — a linha nova entra no mesmo regime.

## Autorização

Sem superfície de autorização. Nenhuma policy, gate, middleware ou guard.

## Rotas

Nenhuma rota nova.

## Superfície de UI

**Sem superfície de UI.** A entrega é comando, workflow e documentação.

## Variáveis de Ambiente

Nenhuma chave nova no `.env`. O driver liga por `-d pcov.enabled=1` na linha de comando, não por
ambiente — decisão do ADR-01, para que a suíte comum não possa ativá-lo por acidente.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | **não se aplica** — sem superfície de UI |
| O que é adiado? | nada |
| O que é memoizado por request? | nada |
| O que é cacheado entre requests? | **o número**, em `.github/badges/cobertura.json`, versionado. Invalidado por quem roda a medição; o CI reprova quando diverge |
| Custo do caminho principal | **a medição é o custo**: suíte serial + 44 % do PCOV. Medido no passo 3 |

## Impacto em Features Existentes

- **`composer test:kit`**: nenhum. Continua paralelo, e `pcov.enabled=0` faz o driver não
  instrumentar nada.
- **CI**: o job novo roda **em paralelo** com os três existentes, então o relógio de parede só
  muda se ele for o mais lento — o que o passo 5 decide com o número do passo 3 na mão.
- **`SiteDeDocumentacaoTest`**: ganha asserção. É guarda de README, e a linha nova do README
  precisa de guarda como as outras.

## Rollback

- **Driver**: `php.ini.bak-antes-do-pcov` restaura o estado anterior; remover
  `C:\php-8.4.25\ext\php_pcov.dll`.
- **Comando e job**: reverter o commit. Nada no kit passa a depender deles para funcionar.
- **Badge**: remover a linha do README e o JSON.

## Dependências

- **PHP**: extensão **PCOV 1.0.12** (não é pacote Composer — DLL no `ext/`)
- **Composer**: nenhum pacote novo
- **NPM**: nenhum

## Riscos

| Risco | Mitigação |
|---|---|
| A medição é lenta demais para caber no CI | o passo 5 decide **com o número medido**: se não couber por PR, vai para push em `main` e agendado |
| O número commitado envelhece em silêncio | o job de CI **reprova** quando o medido diverge do commitado (ADR-04) |
| Perseguir a meta vira teste escrito para subir número | o `00` já declara: a melhoria persegue **lacuna real** com regra de negócio. Se a meta exigir cobrir getter, a meta está errada e é renegociada |
| Upgrade de PHP quebra o DLL | documentado, com o sintoma (`Unable to load dynamic library 'pcov'`) e o caminho do build |

## Channel de Log da Feature

**Não se aplica.** A entrega não executa lógica de aplicação — é comando de terminal, workflow de
CI e documentação. Não há ponto de execução que justifique channel próprio, e criar um
`storage/logs/cobertura.log` que nunca recebe linha seria código morto.

## Estrutura de Implementação

### 1. Instalar o driver no PHP local (RQ-03) — **FEITO**

> Skills: — (operação de ambiente)

- Baixar `php_pcov-1.0.12-8.4-nts-vs17-x64.zip`
- `php_pcov.dll` → `C:\php-8.4.25\ext\`
- `php.ini`: `extension=pcov` + `pcov.enabled=0`, com o comentário explicando o porquê do desligado
- **Verificação**: `php -m | grep pcov` e `php -i | grep '^pcov'` → `PCOV support => Disabled`,
  `PCOV version => 1.0.12`

### 2. Confirmar o denominador (RQ-02) — **FEITO**

> Skills: —

- `phpunit.xml` já declara `<source>app</source>`; **nada a alterar** (ADR-03)
- **Verificação**: relatório Clover lista **240 arquivos** e **9.848 statements**

### 3. Medir: número e tempo (RQ-01, RQ-04)

> Skills: `pest-testing`

- `php -d pcov.enabled=1 vendor/bin/pest tests/Kit tests/Tenancy --coverage-clover=cobertura.xml`
- Registrar **percentual**, **statements cobertos/total** e **duração**
- Comparar com a mesma suíte sem cobertura, para isolar o custo do PCOV

### 4. Escolher a meta (RQ-06)

> Skills: —

- A meta sai de **três** insumos, nesta ordem:
  1. o percentual medido no passo 3;
  2. o que o passo 6 conseguir subir fechando **lacuna real**;
  3. margem de folga para o número não reprovar por flutuação de uma linha
- Fica registrada com o comando que a produziu e a data

### 5. Comando e job de CI (RQ-05, RQ-09)

> Skills: `laravel-best-practices`

- **`composer test:coverage`**: roda a medição em série, grava o Clover, escreve
  `.github/badges/cobertura.json` e aplica `--min`
- **Job `cobertura`** no `ci.yml`, com `coverage: pcov`: roda a medição, aplica o `--min` e
  **reprova quando o JSON commitado diverge do medido**, com a instrução do comando na mensagem
- A frequência (todo PR × push em `main` × agendado) é decidida **com o tempo do passo 3**

### 6. Fechar lacuna real até a meta (RQ-07)

> Skills: `pest-testing`, `feature-test-design`

- Ordenar os arquivos de `app/` por cobertura **ascendente**, cruzando com "tem regra de negócio"
- Escrever teste para o que estiver em cima da lista e **tiver risco**
- Cada teste novo nasce de cenário, não de linha descoberta — a `feature-test-design` deriva

### 7. Documentação e badge (RQ-08, RQ-09)

> Skills: —

- `docs/pt/` e `docs/en/`: página de cobertura — como medir, quanto custa, qual a meta e por quê
- `README.md` / `README.en.md`: badge + linha na tabela `## Qualidade`
- `tests/Kit/SiteDeDocumentacaoTest.php`: guarda a linha nova, como guarda as outras
- `CHANGELOG.md`

### 8. Xdebug instalado e o custo de carregá-lo medido (RQ-11, RQ-12) — **FEITO**

Xdebug 3.5.3 (`php_xdebug-3.5.3-8.4-nts-vs17-x86_64.dll`) com `xdebug.mode=off` e
`xdebug.start_with_request=no`, backup em `php.ini.bak-antes-do-xdebug`.

A convivência com o PCOV foi verificada **empiricamente**, porque o README do PCOV diz
*"interoperability with Xdebug is not possible"* e a frase é mais forte do que o código: o
impedimento é **coletar com os dois ao mesmo tempo**, não tê-los carregados. Com os dois no
`php.ini` e ambos desligados, `-d pcov.enabled=1` expõe `pcov\collect` e `-d xdebug.mode=coverage`
acende `Coverage ✔ enabled` — cada um responde à sua flag, e o
`phpunit/php-code-coverage` escolhe sozinho em `Driver/Selector.php:31`.

Falta a medição do RQ-12 — o custo de **só carregar** o Xdebug, com a máquina ociosa.

### 9. Levantar os níveis de qualidade e adotar os que já estão pagos (RQ-13, RQ-14)

Cada candidato **rodado contra o kit** antes de entrar na recomendação — a tabela inteira está em
`02-decisoes-arquiteturais.md` → ADR-05. O que esta entrega executa:

- adotar `arch()->preset()->php()` e `arch()->preset()->security()`, este com `ignoring()` na
  única violação (`KitInstall.php:592`), com o motivo escrito;
- verificar o mutation score de verdade, pelo `pestw.cmd`, e o `--tia` (RQ-14);
- registrar no `wikis/roadmap.md` os itens medidos que **não** entram: PHPStan level 8 (48 erros),
  `composer-require-checker` + `composer-unused`, `declare(strict_types=1)` (98 de 240),
  branch coverage e type coverage.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada vale especialmente aqui: o kit **já** tem
> `<source>` no `phpunit.xml`, **já** tem guarda de número de README, e **já** tem o padrão de
> script no `composer.json`. Reutilizar os três vence escrever qualquer coisa nova.
>
> **Baseline antes do primeiro commit**: a suíte em `main` está em **2.904 testes, 2.901
> passaram, 3 pulados, 0 falhas** (medido em 2026-09-24). A Verificação Final compara com isso.

## Testes

> Ver `04-casos-de-teste.md`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --test --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `vendor/bin/filacheck`
- [ ] `composer test:coverage` — percentual, statements e **duração** registrados
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel` — contra a baseline de `main`
- [ ] `.github/badges/cobertura.json` bate com o medido
- [ ] Badge renderiza no GitHub (conferido na aba do PR)
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 6.5)**

## Commits

- `:white_check_mark: feat(cobertura): mede quanto de app/ a suite cobre, com piso e badge`
- `:memo: docs(cobertura): como medir, quanto custa e por que a meta e esta`
