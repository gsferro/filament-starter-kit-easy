<?php

namespace Database\Factories;

use App\Models\Convite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Support\Config;

/**
 * Factory de convites — para TESTE apenas.
 *
 * Seeder do kit nunca usa factory nem faker (`fakerphp/faker` é require-dev), e convite é
 * dado de operação, não de fundação: não existe seeder de convite.
 *
 * **Sem `token`**: a coluna nasce nula e quem a preenche é `Convite::enviar()`, que
 * devolve o token em claro. Os testes são o único consumidor legítimo desse retorno.
 *
 * @extends Factory<Convite>
 */
class ConviteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modelo = Config::roleModel();

        return [
            'email'     => fake()->unique()->safeEmail(),
            'role_id'   => $modelo::query()->value('id'),
            'tenant_id' => null,
            'expira_em' => now()->addDays(7),
            'aceito_em' => null,
        ];
    }
}
