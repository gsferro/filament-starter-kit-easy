<?php

declare(strict_types=1);

use App\Models\Convite;
use App\Models\Projeto;
use App\Models\Tenant;
use App\Models\User;
use GeneaLabs\LaravelModelCaching\CachedBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
| Model caching do painel /app.
|
| O pacote mike-bronner/laravel-model-caching lê a config
| `laravel-model-caching.enabled` para decidir se realmente cacheia.
| Estes testes cobrem os dois estados e a invalidação automática.
*/

beforeEach(function (): void {
    // Isola o cache em memória para não depender de Redis nos testes.
    config(['cache.stores.model-cache' => ['driver' => 'array']]);
    config(['laravel-model-caching.store' => 'model-cache']);
});

afterEach(function (): void {
    // Limpa o cache entre os cenários para evitar vazamento de estado.
    Cache::store('model-cache')->flush();
});

describe('com MODEL_CACHE_ENABLED=true', function (): void {
    beforeEach(function (): void {
        config(['laravel-model-caching.enabled' => true]);
    });

    it('retorna CachedBuilder nas models do painel app', function (): void {
        expect(User::query())->toBeInstanceOf(CachedBuilder::class)
            ->and(Convite::query())->toBeInstanceOf(CachedBuilder::class)
            ->and(Projeto::query())->toBeInstanceOf(CachedBuilder::class);
    });

    it('não toca o banco na segunda consulta igual', function (): void {
        $usuario = User::factory()->create();

        User::find($usuario->id); // aquece o cache

        DB::enableQueryLog();
        $encontrado = User::find($usuario->id);
        $queries    = DB::getQueryLog();

        expect($encontrado->id)->toBe($usuario->id)
            ->and($queries)->toHaveCount(0);
    });

    it('invalida o cache ao atualizar o registro', function (): void {
        $usuario = User::factory()->create();

        User::find($usuario->id); // aquece
        $usuario->update(['name' => 'Atualizado']);

        DB::enableQueryLog();
        $encontrado = User::find($usuario->id);
        $queries    = DB::getQueryLog();

        expect($encontrado->name)->toBe('Atualizado')
            ->and($queries)->toHaveCount(1);
    });

    it('invalida o cache ao excluir o registro', function (): void {
        $tenant  = Tenant::factory()->create();
        $projeto = Projeto::forceCreate(['nome' => 'Cache Test', 'tenant_id' => $tenant->id]);

        Projeto::find($projeto->id); // aquece
        $id = $projeto->id;
        $projeto->delete();

        DB::enableQueryLog();
        $encontrado = Projeto::find($id);
        $queries    = DB::getQueryLog();

        expect($encontrado)->toBeNull()
            ->and($queries)->toHaveCount(1);
    });
});

describe('com MODEL_CACHE_ENABLED=false', function (): void {
    beforeEach(function (): void {
        config(['laravel-model-caching.enabled' => false]);
    });

    it('retorna Eloquent Builder padrão', function (): void {
        expect(User::query())->toBeInstanceOf(Builder::class)
            ->and(Convite::query())->toBeInstanceOf(Builder::class)
            ->and(Projeto::query())->toBeInstanceOf(Builder::class);
    });

    it('sempre toca o banco', function (): void {
        $usuario = User::factory()->create();

        User::find($usuario->id); // primeira

        DB::enableQueryLog();
        User::find($usuario->id); // segunda
        $queries = DB::getQueryLog();

        expect($queries)->toHaveCount(1);
    });
});

describe('arquitetura', function (): void {
    it('models do painel app usam ModeloCacheavel', function (): void {
        expect(class_uses_recursive(User::class))->toContain('App\\Traits\\ModeloCacheavel')
            ->and(class_uses_recursive(Convite::class))->toContain('App\\Traits\\ModeloCacheavel')
            ->and(class_uses_recursive(Projeto::class))->toContain('App\\Traits\\ModeloCacheavel');
    });

    it('tenant nao usa ModeloCacheavel', function (): void {
        expect(class_uses_recursive(Tenant::class))->not->toContain('App\\Traits\\ModeloCacheavel');
    });
});
