# Progresso — Rector no pipeline de qualidade

**Branch**: `feature/v1-enriquecimento-kit`
**Concluído em**: 2026-08-18

## 1. Medição

- [x] Pacote verificado no Packagist: `driftingly/rector-laravel` **2.5.0**, exige `rector/rector ^2.2.7`
- [x] Set de Laravel 13 confirmado: `LaravelSetList::LARAVEL_130` e `LaravelLevelSetList::UP_TO_LARAVEL_130`
- [x] Busca por regras de Filament: **zero ocorrências**
- [x] `filament/upgrade` **v5.7.6** descoberto — ferramenta oficial, também baseada em Rector
- [x] `rector process --dry-run` com sets de qualidade: **103 arquivos**, agrupados por regra
- [x] Defeito do `CarbonToDateFacadeRector` provado contra `helpers.php:623` e `KitServiceProvider:57`

## 2. Decisão registrada

- [x] ADR-01 — adotar como ferramenta de upgrade
- [x] ADR-02 — recusar como gate de lint, com a medição
- [x] ADR-03 — Filament tem ferramenta própria; nada a escrever aqui

## 3. Dependências

- [x] `rector/rector ^2.6` e `driftingly/rector-laravel ^2.5` em `require-dev`

## 4. `rector.php`

- [x] Paths: `app`, `database`, `routes`, `tests`
- [x] **Nenhum set ligado**
- [x] `withSkip()` das três migrations de vendor, espelhando o `phpstan.neon`
- [x] Cache em `storage/framework/cache/rector` (o default sujaria a raiz)
- [x] Bloco de instruções no topo: qual set ligar em cada upgrade, e por que os de qualidade ficam fora

## 5. Scripts

- [x] `composer refactor:preview` e `composer refactor:apply`
- [x] **Nenhum dos dois em `composer test`**
- [x] `rector.php` em `KitUpdate::CAMINHOS_DO_KIT`

## 6. Wiki

- [x] `wikis/qualidade-de-codigo.md` — as quatro ferramentas, os quatro eixos
- [x] `wikis/pacotes.md` — duas linhas novas (Rector e `filament/upgrade`)
- [x] `wikis/README.md` — entrada nº 9 no índice

## 7. READMEs

- [x] `README.md` e `README.en.md`

## 8. Teste do contrato

- [x] `tests/Kit/QualidadeDeCodigoTest.php` — 11 casos, CT-01 a CT-05

## Verificação Final

- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/phpstan analyse` → **0 erros** (level 7)
- [x] `vendor/bin/filacheck` → 17 regras
- [x] `php artisan test tests/Kit/QualidadeDeCodigoTest.php` → 11 casos, 15 asserções
- [x] `composer refactor:preview` → exit 0, nenhuma mudança proposta (sets desligados)
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel`
- [x] `git commit`

---

## Auditoria Pré-Implementação

### Revisão profunda — premissas contra o código real

| Premissa | O que o código/pacote diz | Correção |
|---|---|---|
| "o rector-laravel deve ter regras de Filament" | **zero** ocorrências de "filament" no pacote | ADR-03 reescrita: a lacuna não existe, o Filament tem ferramenta própria |
| "o Rector entra no lint como o FilaCheck entrou" | 103 arquivos, e um deles reverte correção do PHPStan | ADR-02: recusado, com a medição |
| "`storage_path()` funciona no `rector.php`" | o Rector avalia o arquivo **sem bootar a app** — `Call to undefined method Container::storagePath()` | caminho montado com `__DIR__` |

### Auditoria Ponytail

| # | Sugestão | Aplicada? |
|---|---|---|
| 1 | Degrau 1 da escada — "isto precisa existir?" — **recusou metade do pedido** | sim, ADR-02 |
| 2 | Não escrever regras Rector de Filament (competir com ferramenta oficial) | sim, ADR-03 |
| 3 | Não criar config/env para ligar sets — o `rector.php` comentado basta | sim |
| 4 | Não instalar `filament/upgrade` agora (a versão dele acompanha o destino) | sim, ADR-03 |

---

## Blockers

Nenhum.

## Desvios do Plano

- **`storage_path()` no `rector.php` não funciona.** O Rector avalia o arquivo de configuração sem
  bootar a aplicação, então os helpers do Laravel não existem. Primeira execução estourou
  `Call to undefined method Illuminate\Container\Container::storagePath()`. Trocado por
  `__DIR__.'/storage/framework/cache/rector'`, com o motivo no comentário.

- **`rector.php` sem set emite `[WARNING] Register rules or sets`.** É esperado e o exit code é
  **0** — o comando não falha, só avisa. Aceito: o aviso é literalmente a mensagem certa para um
  arquivo cujo estado normal é "desligado".

## Notas de Implementação

- **`now()` é `Date::now()`**, literalmente: `Illuminate/Foundation/helpers.php:623` é
  `return Date::now(enum_value($tz));`. Isso é o que transforma o `CarbonToDateFacadeRector` de
  "mudança de estilo" em "reintrodução de defeito" neste projeto, e foi o fato que decidiu a ADR-02.

- **O Rector puxa `phpstan/phpstan`**, que o kit já tem via larastan. Sem custo de dependência real.

- **A medição dos 103 arquivos usou um `rector.php` temporário** com os sets de qualidade ligados,
  que **não** é o arquivo entregue. O entregue nasce sem set — a medição foi instrumento, não
  configuração.

## Retrospectiva

**Funcionou bem**

- Medir antes de decidir. "O Rector agrega valor?" era pergunta de opinião até o `--dry-run`
  transformá-la em 103 arquivos e um defeito nomeado.
- Separar as duas perguntas do requisito (RQ-02 qualidade × RQ-03 lint) logo no `00`. Elas pareciam
  a mesma, e as respostas são opostas.
- O achado do `filament/upgrade` mudou a resposta de RQ-04 de "não existe, é uma pena" para "não
  existe, e não precisa existir" — que é uma resposta melhor que a pergunta.

**Faltou no plano**

- Não previ que o `rector.php` roda fora do contexto da aplicação. Uma linha de pesquisa teria
  evitado o ciclo do `storage_path()`.
- A skill manda avaliar CT-B pela `## Superfície de UI`. Aqui não há UI nenhuma, e o gate funcionou
  — mas vale registrar que uma feature de **ferramenta** cai fora de quase todo o template, e isso
  não é defeito da entrega.

## Candidatos a Rule de Projeto

**Nenhum proposto.**

A decisão desta feature já tem enforcement automático — `tests/Kit/QualidadeDeCodigoTest.php` falha
se o Rector entrar no gate ou se um set de qualidade for ligado. É exatamente o que a skill pede:
*"preferir enforcement automático à prosa"*. Uma rule aqui seria imposto de contexto em todo arquivo
do glob para repetir o que um teste já garante.
