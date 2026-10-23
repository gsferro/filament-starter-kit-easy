<?php

/**
 * O alerta do tutorial guiado (RQ-27 da wiki `onboarding-guiado`): tela de painel nova sem seeder
 * de onboarding e sem isenção declarada deixa esta suíte VERMELHA, com o nome da tela.
 *
 * A rule `.ai/rules/onboarding.md` avisa o agente que edita uma tela; este arquivo avisa mesmo
 * quando ninguém leu a rule — é o "teste avisa mesmo quando o agente não lê" do Adendo 2.
 *
 * O inventário é `Database\Seeders\OnboardingSeeder`: `TELAS` (tela → seeder filho, por painel),
 * `ISENTAS` (tela → motivo, em todo painel) e `ISENTAS_NO_PAINEL`. A comparação é nos dois
 * sentidos, como a do `InventarioDeTelasTest`: tela registrada fora do inventário, e linha do
 * inventário que aponta tela que o painel não registra (rename de plugin sai em silêncio).
 */

use Database\Seeders\Onboarding\SeederDeOnboarding;
use Database\Seeders\OnboardingSeeder;
use Filament\Facades\Filament;
use Symfony\Component\Finder\Finder;

/**
 * Pages e Resources que o painel registra — o mesmo critério de `telasRegistradasNoPainel()`.
 *
 * @return list<class-string>
 */
function telasDoPainelParaOnboarding(string $painel): array
{
    $filament = Filament::getPanel($painel);

    return array_values(array_unique([...$filament->getPages(), ...$filament->getResources()]));
}

/** @return list<class-string<SeederDeOnboarding>> */
function filhosDoOnboarding(): array
{
    return [...OnboardingSeeder::FILHOS, ...OnboardingSeeder::FILHOS_DE_TENANCY];
}

it('não deixa tela de painel sem seeder de onboarding nem isenção', function (string $painel): void {
    $semDestino = array_values(array_filter(
        telasDoPainelParaOnboarding($painel),
        fn (string $tela): bool => ! array_key_exists($tela, OnboardingSeeder::TELAS[$painel] ?? [])
            && ! array_key_exists($tela, OnboardingSeeder::ISENTAS)
            && ! array_key_exists($tela, OnboardingSeeder::ISENTAS_NO_PAINEL[$painel] ?? []),
    ));

    expect($semDestino)->toBe([], sprintf(
        'Telas do painel /%s sem tutorial guiado: %s. Crie (ou atualize) o seeder filho em '
        .'database/seeders/Onboarding/ e acrescente a linha em OnboardingSeeder::TELAS — ou declare a '
        .'tela em OnboardingSeeder::ISENTAS com o motivo. Ver .ai/rules/onboarding.md.',
        $painel,
        implode(', ', $semDestino),
    ));
})->with(['admin', 'app', 'infra']);

it('não deixa linha do inventário apontando tela que o painel não registra', function (string $painel): void {
    $mortas = array_values(array_diff(
        array_keys([...OnboardingSeeder::TELAS[$painel], ...OnboardingSeeder::ISENTAS_NO_PAINEL[$painel] ?? []]),
        telasDoPainelParaOnboarding($painel),
    ));

    expect($mortas)->toBe([], "Linhas de OnboardingSeeder para o painel /{$painel} sem tela registrada: ".implode(', ', $mortas));
})->with(['admin', 'app', 'infra']);

it('não deixa isenção apontando tela que nenhum painel registra', function (): void {
    $registradas = [
        ...telasDoPainelParaOnboarding('admin'),
        ...telasDoPainelParaOnboarding('app'),
        ...telasDoPainelParaOnboarding('infra'),
    ];

    expect(array_values(array_diff(array_keys(OnboardingSeeder::ISENTAS), $registradas)))->toBe([]);
});

it('faz todo seeder do inventário ser um filho chamado pelo pai', function (): void {
    $doInventario = OnboardingSeeder::filhosComTela();

    expect(array_values(array_diff($doInventario, filhosDoOnboarding())))->toBe([]);
});

it('faz todo seeder de database/seeders/Onboarding ser chamado pelo pai, e todo filho do pai existir', function (): void {
    $noDisco = collect(Finder::create()->files()->in(database_path('seeders/Onboarding'))->name('*Seeder.php'))
        ->map(fn ($arquivo): string => 'Database\\Seeders\\Onboarding\\'.$arquivo->getBasename('.php'))
        ->reject(fn (string $classe): bool => (new ReflectionClass($classe))->isAbstract())
        ->sort()
        ->values()
        ->all();

    $filhos = collect(filhosDoOnboarding())->sort()->values()->all();

    expect($filhos)->toBe($noDisco)
        ->and(count($filhos))->toBe(count(array_unique($filhos)), 'filho repetido entre FILHOS e FILHOS_DE_TENANCY');
});
