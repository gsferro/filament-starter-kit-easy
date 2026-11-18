<?php

use App\Livewire\PreferenciasDeAparencia;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * A seção Aparência do Meu perfil com organizações (suíte Tenancy).
 *
 * IDs de CT em `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/04-casos-de-teste.md`
 * (R12 — CT-30). Com organizações, o Meu perfil do `/app` só existe em `/app/{slug}/meu-perfil`
 * (o `/app/meu-perfil` do 04 responde 404; ver CT-12, linhas D5 e D6).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    aparenciaDaInstalacao(corHex: null);
});

it('[CT-30] com organizações, a Ana grava pelo Meu perfil de qualquer painel e lê o aviso sobre a organização que não liberou', function (string $url, string $painel, bool $organizacao): void {
    ['ana' => $ana, 'acme' => $acme] = anaComTresOrganizacoes();

    papelNaOrganizacao($ana, 'infra');
    $acme->update(['aparencia_por_usuario' => $organizacao]);

    $this->actingAs($ana);

    $html  = $this->get($url)->assertOk()->getContent();
    $texto = preg_replace('/\s+/', ' ', strip_tags($html));

    expect($html)->toContain('Salvar aparência')
        ->and(preg_match('/organiza[çc][ãa]o.{0,80}liber/iu', $texto))->toBe(1, 'a seção não mostra o aviso sobre organizações');

    Filament::setCurrentPanel($painel);

    Livewire::test(PreferenciasDeAparencia::class)
        ->fillForm(['densidade' => 'denso'])
        ->call('salvar')
        ->assertHasNoFormErrors();

    expect($ana->fresh()->aparencia)->toBe(['densidade' => 'denso']);
})->with([
    '/app, organização ligada'     => ['/app/acme/meu-perfil', 'app', true],
    '/app, organização desligada'  => ['/app/acme/meu-perfil', 'app', false],
    '/admin, desligada'            => ['/admin/meu-perfil', 'admin', false],
    '/infra, desligada'            => ['/infra/meu-perfil', 'infra', false],
]);
