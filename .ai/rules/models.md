---
paths:
  - 'app/Models/**'
---

# Models

## Models com Resource no painel `/app` usam `App\Traits\ModeloCacheavel`

Toda model que tem um Filament Resource em `app/Filament/App/Resources/` deve usar a trait `App\Traits\ModeloCacheavel`. Isso ativa o `mike-bronner/laravel-model-caching` de forma controlada, respeitando `config('laravel-model-caching.enabled')`.

- Use `use App\Traits\ModeloCacheavel;` junto com as demais traits.
- Nunca use a trait diretamente do vendor (`GeneaLabs\LaravelModelCaching\Traits\Cachable`) — a trait intermediária é o ponto único de liga/desliga.
- O default da configuração é `false`; em produção ligue com `MODEL_CACHE_ENABLED=true` e `MODEL_CACHE_STORE=model-cache`.
