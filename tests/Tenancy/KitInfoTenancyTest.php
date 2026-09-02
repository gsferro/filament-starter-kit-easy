<?php

use App\Models\Tenant;

/**
 * `kit:info` com a multi-organização LIGADA.
 *
 * Arquivo separado por exigência de bootstrap, não de organização: `TenancyTestCase` fixa
 * `permission.teams` em `createApplication()`, antes das migrations, e o Pest não aceita dois
 * TestCases no mesmo diretório. Ver `.ai/rules/testes.md`.
 *
 * O par com CT-05 (organização desligada, em `tests/Kit`) é o que mata a leitura da chave errada:
 * a suíte `Kit` roda com a tenancy desligada e esta com ela ligada, então um comando que lesse
 * `permission.teams` ou `env('KIT_TENANCY')` em vez de `config('kit.tenancy.enabled')` ficaria
 * vermelho em um dos dois lados.
 */
it('[CT-15] mostra o rotulo plural e quantas organizacoes existem', function (): void {
    tenant('Acme', 'acme');
    tenant('Globex', 'globex');
    tenant('Initech', 'initech');

    expect(Tenant::count())->toBe(3);

    // O oráculo é a LINHA porque as quatro afirmações estão todas nela, e
    // `expectsOutputToContain()` casa no máximo uma substring por linha impressa — ver
    // `linhaDoKitInfo()` em `tests/Pest.php`.
    expect(linhaDoKitInfo(saidaDoKitInfo(), 'Multi-organização'))
        ->toContain('ligada')
        ->toContain((string) config('kit.tenancy.label_plural'))
        ->toContain('3 cadastrada')
        ->not->toContain('desligada');
})->group('kit');
