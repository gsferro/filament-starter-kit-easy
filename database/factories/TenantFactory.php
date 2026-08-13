<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory de tenants — para TESTE apenas.
 *
 * Seeder do kit nunca usa factory nem faker: `fakerphp/faker` é require-dev e a
 * imagem Docker roda `composer install --no-dev` (ver DatabaseSeeder).
 *
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nome = fake()->unique()->company();

        return [
            'nome'  => $nome,
            'slug'  => Str::slug($nome),
            'ativo' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ativo' => false,
        ]);
    }
}
