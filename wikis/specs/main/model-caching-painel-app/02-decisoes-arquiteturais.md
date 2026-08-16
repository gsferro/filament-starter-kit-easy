# Decisões Arquiteturais — Model Caching padrão no painel /app

## ADR-01: Uso de uma trait intermediária `App\Traits\ModeloCacheavel`

**Status**: Proposta
**Data**: 2026-08-16

### Contexto

O pacote `mike-bronner/laravel-model-caching` fornece a trait `Cachable` diretamente. Usá-la em cada model acopla o projeto ao namespace do vendor e dificulta um desligamento global coordenado.

### Decisão

Criar `App\Traits\ModeloCacheavel` que `use` a trait `Cachable` do vendor. Todas as models do kit usam a trait intermediária. Isso permite trocar o mecanismo de cache sem tocar em cada model individualmente.

### Alternativas Consideradas

1. **Usar `Cachable` diretamente em cada model** — rejeitada: acoplamento ao vendor e duplicação do ponto de liga/desliga.
2. **Criar `App\Models\BaseModel` estendendo `CachedModel`** — rejeitada: `User` estende `Authenticatable`; não pode perder essa herança sem reescrever o sistema de autenticação.

### Consequências

- **Positivas**: ponto único de interceptação; fácil rollback; regra de arquitetura pode verificar o uso da trait intermediária.
- **Negativas**: uma camada a mais de indireção.

---

## ADR-02: Models do painel `/app` usam a trait, não o painel `/admin` nem `/infra`

**Status**: Proposta
**Data**: 2026-08-16

### Contexto

O requisito fala em "models de painel de app". O kit tem três painéis (`/admin`, `/app`, `/infra`), cada um com resources e models.

### Decisão

Aplicar o cache apenas nas models que têm Resource no painel `/app` (`User`, `Convite`, `Projeto`). Painéis `/admin` e `/infra` são de administração geral e têm perfil de escrita mais alto; o ganho de cache é menor e o risco de stale data é maior.

### Consequências

- **Positivas**: escopo pequeno, previsível e alinhado ao requisito.
- **Negativas**: modelos administrados em `/admin` (`AgenteIa`, `Tenant`, `Role`) não usam o cache.

---

## ADR-03: Default continua `MODEL_CACHE_ENABLED=false`

**Status**: Proposta
**Data**: 2026-08-16

### Contexto

O `.env.example` e `config/laravel-model-caching.php` já desligam o cache por padrão, pois dependem de Redis.

### Decisão

Manter o default `false`. Os testes do kit validam os dois estados sem forçar o `.env` do desenvolvedor.

### Consequências

- **Positivas**: sem dependência de Redis no desenvolvimento local.
- **Negativas**: em produção ainda é preciso ligar explicitamente.
