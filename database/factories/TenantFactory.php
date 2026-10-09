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

    /**
     * Organização com identidade visual definida.
     *
     * O default de `definition()` deixa os dois campos nulos de propósito: o estado neutro é o
     * mais importante de cobrir, porque a feature tem de ser inerte sem eles.
     *
     * `$logoEscura` grava `logo_dark`, a variante escura da logo (só vale com a marca separada
     * da organização também — `unifica_logo` desligado). `$unifica` mexe nesse flag; `null`
     * segue a regra do backfill da coluna: escura enviada significa marca separada
     * (`unifica_logo = false`), senão o default do banco vale.
     */
    public function comIdentidadeVisual(?string $cor = '#7c3aed', ?string $logo = null, ?string $paleta = null, ?string $logoEscura = null, ?bool $unifica = null): static
    {
        return $this->state(function (array $attributes) use ($cor, $logo, $paleta, $logoEscura, $unifica): array {
            $unifica ??= $logoEscura !== null ? false : null;
            $estado = [
                'cor_primaria'      => $cor,
                'cor_primaria_nome' => $paleta,
                'logo'              => $logo,
                'logo_dark'         => $logoEscura,
            ];

            if ($unifica !== null) {
                $estado['unifica_logo'] = $unifica;
            }

            return $estado;
        });
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ativo' => false,
        ]);
    }
}
