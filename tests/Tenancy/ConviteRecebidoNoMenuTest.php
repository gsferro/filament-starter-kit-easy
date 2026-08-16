<?php

use App\Filament\App\Pages\ConvitesRecebidos;
use App\Models\Convite;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;

/**
 * O caminho de quem JÁ TEM CONTA e é convidado para outra organização.
 *
 * A tela de aceite manda essa pessoa entrar e promete, com todas as letras: "o
 * convite aparece no menu do seu usuário". Se o item não aparecer, a promessa
 * vira beco sem saída — a pessoa autentica e não tem o que fazer.
 *
 * Reproduz o cenário exato relatado em teste manual da v0.16.2: usuário na
 * organização A, convite pendente para a organização B.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->acme   = tenant('Acme', 'acme');
    $this->globex = tenant('Globex', 'globex');

    $this->convidado = usuarioComPapel('panel_user', $this->acme, 'ja-tem-conta@example.com');
    $this->convidado->tenants()->attach($this->acme->getKey());
});

it('conta o convite pendente de outra organização para quem já tem conta', function (): void {
    Convite::factory()->create([
        'email'     => $this->convidado->email,
        'tenant_id' => $this->globex->getKey(),
    ]);

    expect(Convite::pendentesPara($this->convidado)->count())->toBe(1);
})->group('kit');

it('mostra o item de menu quando há convite pendente', function (): void {
    Convite::factory()->create([
        'email'     => $this->convidado->email,
        'tenant_id' => $this->globex->getKey(),
    ]);

    $this->actingAs($this->convidado);
    noPainelDa($this->acme);

    $item = ConvitesRecebidos::itemDeMenu();

    // O badge chega como string: o `Action::badge()` do Filament normaliza para
    // texto na hora de renderizar. O que importa é o número que ele mostra.
    expect($item->isVisible())->toBeTrue()
        ->and((int) $item->getBadge())->toBe(1);
})->group('kit');

it('esconde o item quando não há nada a decidir', function (): void {
    $this->actingAs($this->convidado);
    noPainelDa($this->acme);

    expect(ConvitesRecebidos::itemDeMenu()->isVisible())->toBeFalse();
})->group('kit');

/**
 * O item precisa chegar ao HTML do painel, não só ser construível.
 *
 * Ele é acrescentado num `bootUsing()`, e quem registra por último vence — a
 * mesma armadilha que o item "Bloquear sessão" já documenta. Por isso a
 * asserção é sobre a PÁGINA renderizada, num request de verdade: montar o painel
 * fora do HTTP não serve aqui (o BreezyCore resolve rota no boot e estoura
 * `Call to a member function parameter() on null`).
 */
it('entrega o item no HTML do painel de negócio', function (): void {
    Convite::factory()->create([
        'email'     => $this->convidado->email,
        'tenant_id' => $this->globex->getKey(),
    ]);

    $this->actingAs($this->convidado)
        ->get("/app/{$this->acme->slug}")
        ->assertOk()
        ->assertSee('Convites recebidos');
})->group('kit');
