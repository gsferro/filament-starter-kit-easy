# Plano de Ação — Roadmap item 8.2: `composer-require-checker` + `composer-unused`

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: `wikis/specs/feat/cobertura-de-testes/` (ADR-05, o levantamento dos níveis de qualidade que gerou o item 8)
- **Motivo**: responder mecanicamente "das dependências, quais são de fato usadas?"
- **Toca infra compartilhada?**: sim — `composer.json` (`require-dev` e scripts), o job `qualidade` do CI e a lista de arquivos do `kit:update`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | `composer-unused` no kit | 1, 2, 3 | — |
| RQ-02 | `composer-require-checker` no kit | 1, 3 | — |
| RQ-03 | Como `require-dev` | 1 | — |
| RQ-04 | O CI roda pelo `vendor/bin` | 4 | — |
| RQ-05 | Relatório, sem reprovar | 4 | — |
| RQ-06 | A pergunta tem resposta registrada | 2, 6 | — |
| P-01 | `composer deps:check` | 3 | — |
| P-02 | Resumo do job, job verde | 4 | — |
| P-03 | Exclusão só do que é usado por outro caminho, com motivo | 2 | — |
| P-04 | Triagem registrada | 6 | — |
| P-05 | Nenhuma dependência de produção muda | — | recomendação na triagem |
| P-06 | Configuração viaja com o kit | 5 | — |
| P-07 | `deps:check` fora do `composer test` | 3 | CT-09 *(alterado em 2026-11-18: QA-07 do quality gate, ciclo 2)* |
| P-08 | `ext-sodium` e PHP 8.4–8.5 na instalação com dev, documentados | 1, 6 | CT-10 *(alterado em 2026-11-18: QA-07 do quality gate, ciclo 2)* |

## Objetivo

Instalar as duas ferramentas como dependência de desenvolvimento, rodá-las por um comando do
composer e no CI como relatório que não reprova, e registrar a triagem do que elas acusam hoje.

## Análise dos Arquivos Existentes

- `composer.json`: scripts de qualidade `lint`, `lint:check`, `types:check`, `filament:check` (o molde de P-01); `require-dev` sem nenhuma das duas ferramentas
- `.github/workflows/ci.yml`, job `qualidade`: Pint, PHPStan e Pest, cada um um passo
- `app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT`: lista com `rector.php`, `phpstan.neon`, `phpunit.xml`, `pint.json` (P-06); o `composer.json` não é sobrescrito pelo `kit:update`, só relatado
- Medição de 2026-11-18 (antes de qualquer configuração): `composer-unused` acusa 8 de 62 pacotes (4 usados por outro caminho e 4 sem uso, depois da triagem e do QA-01); `composer-require-checker` acusa 181 símbolos

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Configuração do `composer-unused` em `composer-unused.php` na raiz (o nome que a ferramenta procura sozinha), só com os 4 pacotes usados por outro caminho *(alterado em 2026-11-18: QA-01 do quality gate, ciclo 1)*, cada um com o motivo | P-03 | — | sessão, 2026-11-18 |
| D2 | Sem arquivo de configuração para o `composer-require-checker`: a triagem não achou falso positivo, e um arquivo vazio seria cerimônia | P-03 | — | sessão, 2026-11-18 (Ponytail) |
| D3 | Três scripts: `deps:unused`, `deps:require` e `deps:check` (os dois). O `deps:unused` usa `--ignore-exit-code` para o `deps:check` chegar ao segundo; o `deps:require` devolve o código da ferramenta | P-01 | — | sessão, 2026-11-18 |
| D4 | No CI, um passo no job `qualidade` depois do PHPStan, com `continue-on-error`, `--ignore-exit-code`/`|| true` e a saída no `GITHUB_STEP_SUMMARY` | RQ-04, RQ-05, P-02 | — | sessão, 2026-11-18 |

## Autorização

Não se aplica (ferramental de desenvolvimento).

## Rotas

Nenhuma.

## Superfície de UI

Nenhuma.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | não se aplica |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado por request? | nada |
| O que é cacheado entre requests? | nada |
| Custo do caminho principal | o passo do CI: ~1 min a mais no job `qualidade` (as duas ferramentas leem o `vendor/` inteiro) |

## Impacto em Features Existentes

- `composer install` de quem instala o kit traz as duas ferramentas e as dependências delas (só `require-dev`) — e, por elas, passa a exigir a extensão PHP `sodium` e PHP 8.4–8.5 na instalação com dev (P-08) *(alterado em 2026-11-18: QA-02 do quality gate, ciclo 1)*
- Nenhum código de `app/` muda além da lista do `kit:update`

## Rollback

- Remover as duas linhas do `require-dev`, os três scripts, o passo do CI e o `composer-unused.php`

## Dependências

- `maglnet/composer-require-checker` `^4.16` e `icanhazstring/composer-unused` `^0.9` em `require-dev` (aprovadas pelo solicitante, Q1)

## Riscos

- Relatório ignorado por não reprovar: a triagem registrada (passo 6) é a linha de base, e o próximo relatório se compara a ela

## Channel de Log da Feature

Nenhum.

## Estrutura de Implementação

### 1. As duas ferramentas no `require-dev`

- **Path**: `composer.json`, `composer.lock`
- **Atende**: RQ-01, RQ-02, RQ-03
- **Logs**: nenhum

### 2. A configuração do `composer-unused`

- **Path**: `composer-unused.php`
- Exclui `anselmokossa/filament-sentinel`, `laravel/pulse`, `laravel/reverb`, `spatie/laravel-backup`, cada um com o motivo *(alterado em 2026-11-18: QA-01: o `livewire/blaze` saiu — sem `@blaze` nem `Blaze::optimize()` ele não transforma template nenhum do quality gate, ciclo 1)*
- **Atende**: RQ-01, RQ-06, P-03
- **Logs**: nenhum

### 3. Os scripts do composer

- **Path**: `composer.json`
- `deps:unused`, `deps:require`, `deps:check` (D3)
- **Atende**: RQ-01, RQ-02, P-01
- **Logs**: nenhum

### 4. O relatório no CI

- **Path**: `.github/workflows/ci.yml`
- Passo "Dependências declaradas × usadas (relatório)" no job `qualidade` (D4)
- **Atende**: RQ-04, RQ-05, P-02
- **Logs**: nenhum

### 5. A configuração viaja com o kit

- **Path**: `app/Console/Commands/KitUpdate.php`
- `composer-unused.php` em `CAMINHOS_DO_KIT`, ao lado de `pint.json`
- **Atende**: P-06
- **Logs**: nenhum

### 6. A triagem registrada

- **Path**: `wikis/qualidade-de-codigo.md`, `docs/{pt,en}/referencia/qualidade-de-codigo.md`, `wikis/roadmap.md`
- Tabela de cada pacote acusado (usado por outro caminho / sem uso), o agrupamento dos 181 símbolos (transitivos usados direto e extensões PHP não declaradas) e as recomendações (P-05); roadmap 8.2 marcado FEITO
- **Atende**: RQ-06, P-04, P-05
- **Logs**: nenhum

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**.
> **Baseline**: `main` da v0.54.0 (`61948f87`), suíte verde (registrada na wiki `preferencias-de-aparencia`).

## Testes

> Ver `04-casos-de-teste.md`.

## Verificação Final
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` (level 8)
- [ ] testes da feature
- [ ] `vendor/bin/pest --parallel --tia` contra a baseline
- [ ] step 9 (revisão do diff) e step 11 (quality gate)

## Commits
- `:wrench: chore(qualidade): composer-unused e composer-require-checker como relatório`
