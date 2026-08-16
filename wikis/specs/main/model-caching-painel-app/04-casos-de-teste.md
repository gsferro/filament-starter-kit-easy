# Casos de Teste — Model Caching padrão no painel /app

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do requisito, não do plano.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Ativação por config | 2 | 2 | 4 | padrão |
| Comportamento de cache | 2 | 3 | 6 | padrão |
| Arquitetura (trait obrigatória) | 1 | 2 | 2 | mínimo |
| Documentação | 1 | 1 | 1 | mínimo |

- Técnicas: EP, BVA 3-valores para a flag booleana, rastreio de efeito para invalidação
- Cenários: 4 · Regras: 3 · Mutantes previstos: 5

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | `App\Models\User`, `Convite`, `Projeto`; trait `App\Traits\ModeloCacheavel`; `config/laravel-model-caching.php`; testes em `tests/Kit` | CT-01..CT-04 |
| **F**unction | cache de queries Eloquent; invalidação automática; respeito à flag `MODEL_CACHE_ENABLED` | CT-01..CT-04 |
| **D**ata | registros das models; cache keys; store `model-cache` | CT-01, CT-02 |
| **I**nterfaces | sem interface de usuário; configuração por `.env` e `artisan modelCache:clear` | CT-03 |
| **P**latform | Redis/Predis para cache (quando `CACHE_STORE` ou `MODEL_CACHE_STORE` é Redis); SQLite em teste | CT-01 |
| **O**perations | admin gira a flag; testes do kit validam os dois estados | CT-01..CT-04 |
| **T**ime | invalidação imediata após `save`/`delete` | CT-02 |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O cache é usado quando `MODEL_CACHE_ENABLED=true` | ativação (padrão) | RQ-02 | BVA 2-valores + observação de builder | CT-01 |
| R2 — O cache NÃO é usado quando `MODEL_CACHE_ENABLED=false` | ativação (padrão) | RQ-03 | BVA 2-valores + observação de builder | CT-01 |
| R3 — A invalidação acontece ao persistir ou excluir um registro | comportamento (padrão) | RQ-01, RQ-04 | rastreio de efeito | CT-02 |
| R4 — Toda model do painel `/app` usa `ModeloCacheavel` | arquitetura (mínimo) | RQ-01 | inspeção de código | CT-04 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nome `ModeloCacheavel` para a trait | escolha de implementação | detalhe do cenário |
| Models listadas: `User`, `Convite`, `Projeto` | escolha de escopo assumida por premissa | detalhe do teste de arquitetura |

## Setup Global

- **Pest estende `Tests\TestCase` com `RefreshDatabase`** (`tests/Pest.php`)
- **Persona**: `master_global` ou `panel_user` — o cache não depende de papel.
- **Fakes**: `Cache::store($store)` pode ser espiado via `Cache::spy()`; Redis não é obrigatório nos testes.

---

## Regra R1 — O cache é usado quando `MODEL_CACHE_ENABLED=true`

> `RQ-02` · perfil **padrão** · técnica: **BVA 2-valores** (flag `true`)

```gherkin
# language: pt

Funcionalidade: Model caching ativável por configuração

  Regra: O cache é usado quando MODEL_CACHE_ENABLED=true

    Cenário: [CT-01] query de model usa CachedBuilder quando o cache está ativado
      Dado que a configuração `laravel-model-caching.enabled` vale `true`
      E exista um usuário no banco
      Quando eu consultar o usuário pela model `App\Models\User`
      Então a query deve ter sido executada por um `GeneaLabs\LaravelModelCaching\CachedBuilder`
      E o registro deve ter sido armazenado no cache

    Cenário: [CT-01B] a mesma query repetida não toca o banco quando o cache está ativado
      Dado que a configuração `laravel-model-caching.enabled` vale `true`
      E exista um usuário no banco
      Quando eu consultar o usuário duas vezes
      Então a segunda consulta deve ler do cache
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `isCachable()` ignora a config e sempre retorna `false` | CT-01, CT-01B |
| M2 | `newEloquentBuilder` devolve `Builder` em vez de `CachedBuilder` mesmo quando ativado | CT-01 |

---

## Regra R2 — O cache NÃO é usado quando `MODEL_CACHE_ENABLED=false`

> `RQ-03` · perfil **padrão** · técnica: **BVA 2-valores** (flag `false`)

```gherkin
# language: pt

  Regra: O cache NÃO é usado quando MODEL_CACHE_ENABLED=false

    Cenário: [CT-02] query de model usa Eloquent Builder padrão quando o cache está desativado
      Dado que a configuração `laravel-model-caching.enabled` vale `false`
      E exista um usuário no banco
      Quando eu consultar o usuário pela model `App\Models\User`
      Então a query deve ter sido executada por `Illuminate\Database\Eloquent\Builder`
      E o cache não deve ter sido acionado
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M3 | `isCachable()` ignora a config e sempre retorna `true` | CT-02 |
| M4 | `newEloquentBuilder` sempre devolve `CachedBuilder`, ignorando a config | CT-02 |

---

## Regra R3 — Invalidação automática ao persistir ou excluir

> `RQ-01`, `RQ-04` · perfil **padrão** · técnica: **rastreio de efeito**

```gherkin
# language: pt

  Regra: O cache é invalidado quando o registro é alterado

    Cenário: [CT-03] atualizar um registro limpa o cache dessa model
      Dado que a configuração `laravel-model-caching.enabled` vale `true`
      E exista um usuário no banco
      E a consulta tenha sido cacheada
      Quando eu atualizar o nome do usuário
      Então o cache da model `User` deve ter sido invalidado

    Cenário: [CT-04] excluir um registro limpa o cache dessa model
      Dado que a configuração `laravel-model-caching.enabled` vale `true`
      E exista um usuário no banco
      E a consulta tenha sido cacheada
      Quando eu excluir o usuário
      Então o cache da model `User` deve ter sido invalidado
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | eventos de model não invalidam o cache | CT-03, CT-04 |

---

## Regra R4 — Toda model do painel `/app` usa a trait `ModeloCacheavel`

> `RQ-01` · perfil **mínimo** · técnica: **inspeção de código**

```gherkin
# language: pt

Funcionalidade: Padronização do model caching no painel /app

  Regra: Toda model do painel /app deve usar a trait ModeloCacheavel

    Cenário: [CT-05] User, Convite e Projeto usam ModeloCacheavel
      Quando eu inspecionar as classes `App\Models\User`, `App\Models\Convite` e `App\Models\Projeto`
      Então cada uma delas deve usar `App\Traits\ModeloCacheavel`
```

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| Ativação por config (true/false) | CT-01, CT-02 |
| Invalidação em escrita | CT-03, CT-04 |
| Padrão arquitetural nas models | CT-05 |
| Ausente `MODEL_CACHE_ENABLED` no `.env` usa default `false` | não se aplica: default da config já é `false` — testado implicitamente em CT-02 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | query usa CachedBuilder com cache ativado | R1 | BVA | Feature | `tests/Kit/ModelCachingTest.php` | M1, M2 |
| CT-01B | segunda consulta lê do cache | R1 | BVA | Feature | `tests/Kit/ModelCachingTest.php` | M1 |
| CT-02 | query usa Builder padrão com cache desativado | R2 | BVA | Feature | `tests/Kit/ModelCachingTest.php` | M3, M4 |
| CT-03 | atualização invalida cache | R3 | rastreio | Feature | `tests/Kit/ModelCachingTest.php` | M5 |
| CT-04 | exclusão invalida cache | R3 | rastreio | Feature | `tests/Kit/ModelCachingTest.php` | M5 |
| CT-05 | models do painel App usam trait | R4 | inspeção | Architecture | `tests/Kit/ModelCachingTest.php` | — |

## Sem CT-B

A feature não tem superfície de UI. Toda validação pode ser feita em testes de componente e arquitetura.
