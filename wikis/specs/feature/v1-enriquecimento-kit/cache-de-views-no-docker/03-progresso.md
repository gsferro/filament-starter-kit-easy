# Progresso — Cache de views no boot do container

**Branch**: `feature/v1-enriquecimento-kit`
**Concluído em**: 2026-08-19

## 1. Verificação (RQ-01, RQ-02)

- [x] `kit:install` — **nenhum** comando de otimização
- [x] `Dockerfile.laravel` — só `composer install --optimize-autoloader`
- [x] `docker-compose.yml` — nenhum serviço faz cache
- [x] `view:cache` medido: **38 891 ms**, **417 views**
- [x] `filament:optimize` medido: **14 ms**
- [x] Volume `app-storage` identificado encobrindo `storage/`

## 2. `Dockerfile.laravel`

- [x] `RUN php artisan filament:optimize` no estágio `app`
- [x] Comentário explicando por que este vai no build e o `view:cache` não

## 3. `docker-compose.yml`

- [x] `command: sh -c "php artisan view:cache && php-fpm"` no serviço `app`
- [x] `&&` e não `;`
- [x] Comentário com a armadilha do volume e a proibição de `config:cache`/`route:cache`

## 4. Healthcheck

- [x] `start_period: 90s`

## 5. Teste de contrato

- [x] `tests/Kit/CacheDeViewsNoDockerTest.php` — 9 casos, CT-01 a CT-07

## 6. `kit:install`

- [x] **Não alterado**, por decisão. CT-07 fixa a ausência.

## Verificação Final

- [x] `docker compose config --quiet` — válido
- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/phpstan analyse` → 0 erros (level 7)
- [x] `vendor/bin/filacheck` → 17 regras
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel` → **446 casos, 1197 asserções**
- [x] `git commit`

---

## Auditoria Pré-Implementação

### Revisão profunda — premissas contra o que o projeto realmente faz

| Premissa | O que os arquivos dizem | Correção |
|---|---|---|
| "basta um `RUN view:cache` no Dockerfile" | `storage/` é volume nomeado e encobre o build | correção movida para o `command:` — ADR-01 |
| "`php artisan optimize` resolve tudo" | traz `config:cache` (mata o `.env` em runtime) e `route:cache` (congela tenancy) | só `view:cache` — ADR-02 |
| "o serviço `app` tem um `command:` para editar" | ele **não tem** — usa o `CMD ["php-fpm"]` da imagem | `command:` criado, no padrão do serviço `nginx` |
| "o healthcheck aguenta" | `pgrep php-fpm` com `retries: 5` e sem `start_period` | `start_period: 90s` acrescentado |

### Auditoria Ponytail

| # | Sugestão | Aplicada? |
|---|---|---|
| 1 | Não usar `php artisan optimize` — o atalho traz os dois perigosos | sim, ADR-02 |
| 2 | Não criar entrypoint script — o `nginx` já usa `command: sh -c` | sim |
| 3 | Não fatiar o volume em três montagens para salvar 39s de boot | sim, ADR-01 alternativa 3 |
| 4 | Não mexer no `kit:install` — é dev, não deploy | sim |

---

## Blockers

Nenhum.

## Desvios do Plano

- **O teste reprovou nos próprios comentários.** A primeira versão do
  `CacheDeViewsNoDockerTest` afirmava ausência de `config:cache`, `route:cache` e `view:cache`
  sobre o texto cru dos arquivos — e reprovou em **3 dos 9 casos**, porque os comentários que
  documentam a decisão citam exatamente esses comandos.

  **Corrigido**: o `beforeEach` filtra linhas iniciadas por `#`, e as asserções de ausência
  rodam sobre o resultado. É a terceira vez nesta branch que "citar × executar" aparece — as
  outras duas foram o `rector.php` e o comentário do Blade que virava código.

## Notas de Implementação

- **O serviço `app` não tinha `command:`.** Usava o `CMD ["php-fpm"]` da imagem. O `command:`
  novo substitui o CMD inteiro, então o `php-fpm` precisa aparecer explicitamente no fim — se
  alguém remover só o `view:cache` e deixar o `sh -c`, o container sobe sem servir nada.

- **A origem desta feature foi um erro meu.** Ela nasceu de uma investigação em que eu havia
  atribuído 2,7s de render ao widget de chat. A medição estava errada — eu comparava a primeira
  request (fria, pagando compilação de view) contra requests quentes. Corrigida a medição, o
  widget não custa nada, e o que sobrou foi este achado: **a compilação de view é real, custa
  39s, e ninguém a estava pagando no lugar certo**.

  O erro produziu um achado verdadeiro por acidente. Vale registrar que o achado só ficou de
  pé porque a medição foi refeita — a primeira versão dele teria justificado a feature errada.

## Retrospectiva

**Funcionou bem**

- Medir o `view:cache` antes de decidir onde colocá-lo. Os 38,9s são o que dá tamanho à
  feature; sem o número, "cachear view no deploy" é conselho genérico.
- Ler o `docker-compose.yml` antes de escrever o `RUN`. A armadilha do volume é invisível para
  quem só olha o Dockerfile, e teria produzido uma correção que funciona uma vez.
- Perguntar "por que este comando existe" para cada item do `php artisan optimize`. Foi o que
  separou o seguro dos dois que quebram o kit.

**Faltou no plano**

- Não previ que o teste reprovaria nos próprios comentários, apesar de isso já ter acontecido
  duas vezes nesta mesma branch. Devia ter sido a primeira coisa a considerar ao escrever
  asserção de ausência sobre arquivo documentado.

## Candidatos a Rule de Projeto

**Um candidato**, e é o padrão que já se repetiu três vezes:

> **Asserção de ausência sobre arquivo documentado precisa filtrar comentário.**
> Glob: `tests/**`
> Evidência: `CacheDeViewsNoDockerTest` (3 de 9 casos), `QualidadeDeCodigoTest` (`rector.php`),
> e o `perfil-indicator.blade.php`, onde a menção a uma diretiva dentro de comentário virou
> código compilado.
> Gates: durável ✅ · escopável ✅ · não-inferível ✅ · não-redundante ✅

Apresentado ao usuário; não gravado sem aprovação.
