<?php

use App\Data\Convite\FalhaDoConviteData;
use App\Data\Convite\ResultadoDoConviteEmMassaData;
use App\Models\Convite;
use App\Models\Role;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * O resultado do convite em massa como Data — o shape que estava redocumentado em duas classes.
 *
 * Os IDs de CT são os de
 * `wikis/specs/feat/laravel-data-como-padrao-de-dto/laravel-data-como-padrao-de-dto/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function papelDoLoteDto(): int
{
    return (int) Role::query()->where('name', 'panel_user')->value('id');
}

/*
 * CT-24 — o domínio devolve o resultado TIPADO, e cada falha é um Data.
 *
 * O `instanceof` é o oráculo que mata "o domínio voltou a devolver array e a tela lê por chave":
 * a tela recebe o tipo, e o PHP recusa qualquer outra coisa na assinatura de
 * `notificarResultadoDoLote()`.
 */
it('[CT-24] devolve o resultado do lote tipado', function (): void {
    Notification::fake();

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("uma@example.com\noutra@example.com"),
        papelDoLoteDto(),
        null,
        null,
    );

    expect($resultado)->toBeInstanceOf(ResultadoDoConviteEmMassaData::class)
        ->and($resultado->enviados)->toBe(['uma@example.com', 'outra@example.com'])
        ->and($resultado->totalDeEnviados())->toBe(2)
        ->and($resultado->totalDeFalhas())->toBe(0);
});

/*
 * CT-25 — o resumo do lote sai com os mesmos números e o mesmo motivo legível de hoje.
 *
 * Regressão: trocar array por Data não pode mudar o que o usuário lê.
 */
it('[CT-25] mantem o resumo do lote com enviados e falhas', function (): void {
    Notification::fake();

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("valida@example.com\nnao-e-email\noutra@example.com"),
        papelDoLoteDto(),
        null,
        null,
    );

    expect($resultado->totalDeEnviados())->toBe(2)
        ->and($resultado->totalDeFalhas())->toBe(1)
        ->and($resultado->falhas[0])->toBeInstanceOf(FalhaDoConviteData::class)
        ->and($resultado->falhas[0]->email)->toBe('nao-e-email')
        ->and($resultado->falhas[0]->motivo)->toBe('formato_invalido');
});

/*
 * CT-26 — reenviar o mesmo lote não duplica convite.
 *
 * A asserção é no AGREGADO persistido (o convite), não no contador do resultado: ancorar no
 * recurso consumido provaria contabilidade, não idempotência.
 */
it('[CT-26] nao duplica convite ao reenviar o lote', function (): void {
    Notification::fake();

    Convite::convidarEmMassa(
        Convite::separarEmails('contato@exemplo.com'),
        papelDoLoteDto(),
        null,
        null,
    );

    $segundo = Convite::convidarEmMassa(
        Convite::separarEmails('contato@exemplo.com'),
        papelDoLoteDto(),
        null,
        null,
    );

    expect(Convite::query()->where('email', 'contato@exemplo.com')->count())->toBe(1)
        ->and($segundo->totalDeEnviados())->toBe(0)
        ->and($segundo->falhas[0]->email)->toBe('contato@exemplo.com')
        ->and($segundo->falhas[0]->motivo)->toBe('convite_pendente');
});
