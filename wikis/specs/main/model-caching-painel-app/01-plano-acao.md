# Plano de Ação — Model Caching padrão no painel /app

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Toca infra compartilhada?**: sim — `tests/Pest.php` ganha helper de config; `.ai/rules/models.md` nova; `README.md` do kit e das skills.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Model caching padrão para models do painel `/app` | 2, 3 | — |
| RQ-02 | Testes com `MODEL_CACHE_ENABLED=true` | 4 | — |
| RQ-03 | Testes com `MODEL_CACHE_ENABLED=false` | 4 | — |
| RQ-04 | Testes de pacotes ativados por env/config | 4 | — |
| RQ-05 | Documentar nos READMEs | 6 | — |
| RQ-06 | Seção de quantidade/cobertura de testes | 7 | opcional, se valer a pena |

## Objetivo

Tornar o `mike-bronner/laravel-model-caching` padrão para as models que servem o painel `/app`, garantindo que o cache seja ativável via `.env` e que existam testes no kit provando os dois estados (`MODEL_CACHE_ENABLED=true` e `false`).

## Contexto

O pacote já está instalado, desabilitado por padrão e listado no `README.md`, mas nenhuma model do kit o utiliza e não há testes cobrindo o comportamento. O risco é que o pacote pare de funcionar após um `kit:update` sem ninguém notar, ou que modelos novos do painel `/app` esqueçam de adotá-lo.

## Análise dos Arquivos Existentes

- `app/Models/User.php`: estende `Authenticatable`; usa `HasRoles`, `TemUuid`, etc. Não usa cache.
- `app/Models/Convite.php`: estende `Model`; usa `HasFactory`, `TemUuid`. Não usa cache.
- `app/Models/Projeto.php`: estende `Model`; demonstração. Não usa cache.
- `config/laravel-model-caching.php`: `enabled` lê `MODEL_CACHE_ENABLED` (default `false`).
- `config/cache.php`: store `model-cache` isolado em Redis.
- `KitServiceProvider::configureClearCacheButton()` adiciona `modelCache:clear` quando `config('laravel-model-caching.enabled')`.
- `README.md:893` já lista o pacote.

## Autorização

Nenhuma. Não altera permissões.

## Rotas

Nenhuma nova.

## Superfície de UI

Sem superfície de UI — é uma alteração de infraestrutura/model.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `MODEL_CACHE_ENABLED` | `false` | Liga/desliga o model caching globalmente. |
| `MODEL_CACHE_STORE` | `model-cache` | Store de cache dedicado (já existe em `config/cache.php`). |

## Eventos / Listeners / Observers

O pacote adiciona listeners de invalidação automaticamente via trait `Cachable`. Nenhum observer custom necessário.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- `User`, `Convite` e `Projeto` passam a usar `CachedBuilder` quando `MODEL_CACHE_ENABLED=true`.
- Testes de multi-tenancy devem confirmar que o escopo de tenant coexiste com o caching.
- O spatie/laravel-permission continua fora do cache (não tocaremos nele).

## Rollback

- Remover o `use ModeloCacheavel;` das models e a trait `app/Traits/ModeloCacheavel.php`.
- Excluir testes e regra.
- A configuração permanece inalterada.

## Dependências

- `mike-bronner/laravel-model-caching` `^13.1` (já instalado).

## Riscos

- **Risco 1**: conflito da trait `Cachable` com `HasRoles` do spatie (ambos interceptam relações). Mitigação: usar `newModelCachingEloquentBuilder()` sem a trait `Cachable` se conflitar — testar primeiro com User.
- **Risco 2**: multi-tenancy vazar cache entre tenants se o prefixo não for isolado. Mitigação: `use-database-keying` já está ativo; testar cenário.

## Channel de Log

Não cria channel novo — o pacote não exige logs de negócio. O teste usará mocks de `Log` se necessário.

## Estrutura de Implementação

### 1. Criar trait `App\Traits\ModeloCacheavel`

> Skills: `laravel-best-practices`

- **Path**: `app/Traits/ModeloCacheavel.php`
- Abstrair a trait `GeneaLabs\LaravelModelCaching\Traits\Cachable` para centralizar o ponto de liga/desliga e facilitar futuras mudanças.
- A trait deve ser `use`ada nas models do painel `/app`.

### 2. Aplicar `ModeloCacheavel` nas models do painel `/app`

> Skills: `laravel-best-practices`

- **Paths**:
  - `app/Models/User.php`
  - `app/Models/Convite.php`
  - `app/Models/Projeto.php`
- Adicionar `use App\Traits\ModeloCacheavel;` após as demais traits.

### 3. Criar regra `.ai/rules/models.md`

> Skills: `laravel-best-practices`, `requirement-to-rule`

- **Path**: `.ai/rules/models.md`
- Regra: "Toda model com Resource no painel `/app` deve usar `App\Traits\ModeloCacheavel` e respeitar `MODEL_CACHE_ENABLED`".
- Atualizar `.ai/rules/index.md` para incluir o glob `app/Models/**`.

### 4. Criar testes em `tests/Kit/ModelCachingTest.php`

> Skills: `pest-testing`

- **Path**: `tests/Kit/ModelCachingTest.php`
- Cenários:
  1. `MODEL_CACHE_ENABLED=true` → queries de `User`, `Convite`, `Projeto` usam `CachedBuilder` e o cache é acionado.
  2. `MODEL_CACHE_ENABLED=false` → queries usam `Illuminate\Database\Eloquent\Builder` e cache não é acionado.
  3. Pacote instalado e config publicada.
  4. `modelCache:clear` é registrado no `KitServiceProvider` quando habilitado.

### 5. Criar teste de arquitetura para garantir que toda model do painel `/app` usa a trait

> Skills: `pest-testing`

- **Path**: `tests/Kit/Arquitetura/ModelosDoPainelApp.php` ou dentro do `ModelCachingTest.php`
- Usar `pest()->arch()` para validar que `app/Models/**` que têm Resource em `app/Filament/App/Resources/**` usam `App\Traits\ModeloCacheavel`.

### 6. Documentar nos READMEs

> Skills: `laravel-best-practices`

- `README.md`: adicionar seção curta explicando que as models do painel `/app` são cacheáveis via `MODEL_CACHE_ENABLED`.
- Skills `feature-test-design`: se necessário, mencionar o novo padrão.

### 7. Avaliar seção de quantidade/cobertura de testes

> Skills: `laravel-best-practices`

- Verificar se `php artisan test` ou `composer test` já expõe contagem.
- Se valer a pena, adicionar ao `README.md` uma sessão "Qualidade — testes" listando suítes e propósito.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest --filter=ModelCaching --compact`
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] `git commit` dos arquivos alterados individualizados

## Commits

- `:package: models: aplica ModeloCacheavel nas models do painel app`
- `:white_check_mark: test(kit): adiciona testes de model caching`
- `:memo: docs: documenta model caching e testes`
- `:wrench: chore: adiciona rule de project para models do painel app`
