<?php

use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Arr;
use Livewire\Livewire;

/**
 * A aparência efetiva dentro e fora de organização (suíte Tenancy).
 *
 * IDs de CT em `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/04-casos-de-teste.md`
 * (R5 — CT-12, CT-13, CT-14; R7 — CT-18). Instalação de referência SEM cor livre (cor Teal).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    aparenciaDaInstalacao(corHex: null);
});

/** Os quatro observáveis, na ordem do 04. */
function observaveisDaPagina(array $efetiva): array
{
    return Arr::only($efetiva, ['densidade', 'fonte', 'cor', 'menu']);
}

$referencia = ['densidade' => 'Denso', 'fonte' => 'Muito grande', 'cor' => 'Red', 'menu' => 'Manter os ícones'];

it('[CT-12] a tabela das duas chaves decide o que cada pessoa vê', function (string $pessoa, bool $instalacao, bool $organizacao, string $pagina, bool $escolhaVale, string $corDoPadrao) use ($referencia): void {
    ['ana' => $ana, 'acme' => $acme, 'initech' => $initech] = anaComTresOrganizacoes();

    $acme->update(['aparencia_por_usuario' => $organizacao]);
    $initech->update(['aparencia_por_usuario' => $organizacao]);

    [$quem, $painelDoPerfil] = match ($pessoa) {
        'ana'   => [$ana, 'admin'],
        'carla' => [usuarioComPapel('admin_app', $acme, 'carla@example.com'), 'app'],
        'adm'   => [usuarioComPapel('admin', null, 'adm@example.com'), 'admin'],
    };

    if ($pessoa === 'carla') {
        $quem->tenants()->attach($acme->getKey());
    }

    // A escolha é gravada pela seção, com a chave da instalação ligada, e só depois a chave vira o que a linha pede.
    $this->actingAs($quem);
    gravarAparenciaPelaSecao(null, $painelDoPerfil, $painelDoPerfil === 'app' ? '/app/acme/meu-perfil' : null);

    if (! $instalacao) {
        gravarConfiguracao('aparencia_por_usuario', false);
        alinharConfiguracoesDoKit();
    }

    $esperado = $escolhaVale
        ? $referencia
        : ['densidade' => 'Compacto', 'fonte' => 'Grande', 'cor' => $corDoPadrao, 'menu' => 'Esconder tudo'];

    expect(observaveisDaPagina(aparenciaEfetiva($pagina)))->toBe($esperado);
})->with([
    'D1 duas chaves'                      => ['ana', true, true, '/app/acme', true, ''],
    'D2 organização não liberou'          => ['ana', true, false, '/app/acme', false, '#7c3aed'],
    'D3 instalação desligada'             => ['ana', false, true, '/app/acme', false, '#7c3aed'],
    'D4 as duas desligadas'               => ['ana', false, false, '/app/acme', false, '#7c3aed'],
    'D7 /admin não consulta organização'  => ['ana', true, false, '/admin', true, ''],
    'D8 organização sem cor não liberou'  => ['ana', true, false, '/app/initech', false, 'Teal'],
    'D9 admin da organização'             => ['carla', true, false, '/app/acme', false, '#7c3aed'],
    'D10 administradora da instalação'    => ['adm', false, true, '/admin', false, 'Teal'],
]);

it('[CT-13] com as duas chaves e a cor em "Padrão", vale a cor da organização e, sem ela, a da instalação', function (string $slug, string $cor): void {
    ['ana' => $ana, 'acme' => $acme, 'initech' => $initech] = anaComTresOrganizacoes();

    $organizacao = $slug === 'acme' ? $acme : $initech;
    $organizacao->update(['aparencia_por_usuario' => true]);

    $this->actingAs($ana);
    gravarAparenciaPelaSecao(['densidade' => 'denso'], 'admin');

    expect(observaveisDaPagina(aparenciaEfetiva("/app/{$slug}")))
        ->toBe(['densidade' => 'Denso', 'fonte' => 'Grande', 'cor' => $cor, 'menu' => 'Esconder tudo']);
})->with([
    'Acme com cor'    => ['acme', '#7c3aed'],
    'Initech sem cor' => ['initech', 'Teal'],
]);

it('[CT-14] a aparência de uma organização não vaza para a seguinte no mesmo processo', function (string $primeira, string $segunda, string $densidade, string $cor): void {
    ['ana' => $ana, 'acme' => $acme] = anaComTresOrganizacoes();

    $acme->update(['aparencia_por_usuario' => true]);

    $this->actingAs($ana);
    gravarAparenciaPelaSecao(null, 'admin');

    aparenciaEfetiva("/app/{$primeira}");

    $efetiva = aparenciaEfetiva("/app/{$segunda}", fronteira: false);

    expect($efetiva)->densidade->toBe($densidade)->cor->toBe($cor);
})->with([
    'acme → globex'   => ['acme', 'globex', 'Compacto', '#059669'],
    'globex → acme'   => ['globex', 'acme', 'Denso', 'Red'],
    'acme → initech'  => ['acme', 'initech', 'Compacto', 'Teal'],
]);

it('[CT-18] a chave da organização desligada e religada pelo cadastro devolve a escolha da Ana', function (): void {
    ['ana' => $ana, 'acme' => $acme] = anaComTresOrganizacoes();

    $acme->update(['aparencia_por_usuario' => true]);

    $this->actingAs($ana);
    gravarAparenciaPelaSecao(null, 'admin');

    $administradora = usuarioComPapel('master_global', null, 'adm@example.com');

    $salvarEdicao = function (bool $ligada) use ($acme, $administradora): void {
        $this->flushSession();
        $this->actingAs($administradora);

        Filament::setCurrentPanel('admin');

        Livewire::test(EditTenant::class, ['record' => $acme->getRouteKey()])
            ->fillForm(['aparencia_por_usuario' => $ligada])
            ->call('save')
            ->assertHasNoFormErrors();
    };

    $salvarEdicao(false);
    expect($acme->fresh()->aparencia_por_usuario)->toBeFalse();

    $salvarEdicao(true);

    $this->flushSession();
    $this->actingAs($ana);

    expect(aparenciaEfetiva('/app/acme'))->densidade->toBe('Denso')->cor->toBe('Red')
        ->and($ana->fresh()->aparencia)->toBe(escolhaDeReferencia());
});
