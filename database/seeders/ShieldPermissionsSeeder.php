<?php

namespace Database\Seeders;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions do Shield geradas por SEEDER, não por `shield:generate` na mão.
 *
 * O comando do Shield é interativo e falha em modo não-interativo (é o que quebraria o
 * `composer create-project` no Windows). Aqui a geração roda com `--no-interaction` e,
 * se ainda assim falhar num painel, o kit segue instalando: o master_global passa pelo
 * Gate::before e os papéis são criados pelo PapeisSeeder de qualquer forma.
 *
 * ## Um painel de cada vez
 *
 * O `shield:generate` só enxerga o painel corrente (`discovery.discover_all_*` está
 * `false` nas três chaves). Até a versão 0.10.0 este seeder passava `--panel=admin` e
 * mais nada — resultado: as permissions dos Resources de `/app` e `/infra` nunca
 * chegaram a existir no banco, e as telas desses painéis só abriam para o master_global.
 *
 * O `forgetInstance` entre as voltas é obrigatório: `FilamentShield` é `scoped` e memoiza
 * com `once()`, então sem descartá-lo os três painéis geram o conjunto do primeiro.
 *
 * Rode novamente depois de criar seus Resources:
 *   php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
 */
class ShieldPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            $this->command->warn('Tabela `permissions` ausente — rode as migrations antes.');

            return;
        }

        $anterior = Filament::getCurrentPanel();

        foreach (array_keys(Filament::getPanels()) as $painel) {
            $this->gerarPara((string) $painel);
        }

        if ($anterior instanceof Panel) {
            Filament::setCurrentPanel($anterior);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Falhar num painel não pode abortar os outros. */
    private function gerarPara(string $painel): void
    {
        // Instância limpa a cada painel: o Shield é `scoped` e memoiza com `once()`, e a
        // facade ainda guarda o objeto em `Facade::$resolvedInstance`. Sem os dois
        // descartes, as três voltas geram as permissions do primeiro painel.
        app()->forgetInstance('filament-shield');
        Facade::clearResolvedInstance('filament-shield');

        try {
            Artisan::call('shield:generate', [
                '--all'   => true,
                '--panel' => $painel,

                /*
                 * Policy que já existe não é reescrita.
                 *
                 * O `shield:generate` escreve as policies com o estilo dele, não com o do
                 * Pint — e este seeder roda em todo `beforeEach` da suíte. Sem esta flag,
                 * rodar os testes deixa `app/Policies/*.php` reformatado na árvore de
                 * trabalho: `composer test` falha no `lint:check` da execução seguinte, e
                 * o `kit:update` recusa a árvore suja. Também é o que torna o seeder
                 * idempotente de verdade: quem editou uma policy à mão não a perde ao
                 * gerar as permissões de um Resource novo.
                 */
                '--ignore-existing-policies' => true,

                '--no-interaction' => true,
            ]);
        } catch (\Throwable $e) {
            $this->command->warn(
                "shield:generate não pôde rodar no painel {$painel} (".$e->getMessage().'). '
                ."Gere as permissions depois com: php artisan shield:generate --all --panel={$painel}"
            );
        }
    }
}
