<?php

use App\Models\Convite;
use App\Models\Role;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * O carimbo de organização que o Filament põe em todo registro criado com o painel bootado.
 *
 * `Resources\Resource\Concerns\BelongsToTenant::observeTenancyModelCreation()`
 * (`vendor/filament/filament/src/.../BelongsToTenant.php:158-185`) registra, no boot do painel,
 * um `creating` que faz `$relationship->associate($tenant)` **sem verificar se a coluna já veio
 * preenchida**. Vale para todo Resource com `$isScopedToTenant` — no kit, `Convite` e `Projeto`.
 *
 * **Em produção é fail-safe, e é por isso que estes casos afirmam o comportamento em vez de
 * pedirem correção**: dentro do /app da Acme, um payload forjado não cria registro em outra
 * organização. O /admin não é afetado — lá `getCurrentPanel() !== $panel` desliga o hook, e é
 * assim que o convite para qualquer organização continua funcionando a partir da administração.
 * Ver ADR-01 da wiki `wikis/specs/fix/convite-carimba-organizacao-corrente/`.
 *
 * Este arquivo existe para o dia em que o Filament mudar. `ofertaPara()` corrige a divergência
 * para que teste de fronteira não precise saber disto; sem os casos abaixo, essa correção viraria
 * no-op silenciosa e ninguém perceberia que o contrato do vendor mudou.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->acme   = tenant('Acme', 'acme');
    $this->globex = tenant('Globex', 'globex');

    $this->operador = papelNaOrganizacao(usuario('admin.acme@example.com'), 'admin_app', $this->acme);
    $this->operador->tenants()->attach($this->acme->id);
    $this->actingAs($this->operador);
});

/** Convite cru, sem passar pelo `ofertaPara()` — é o helper que está sob medição aqui. */
function conviteCru(string $email, int $tenantId): Convite
{
    return Convite::factory()->create([
        'email'     => $email,
        'role_id'   => Role::query()->where('name', 'panel_user')->value('id'),
        'tenant_id' => $tenantId,
    ]);
}

/** Boota o painel `app` de verdade — só o middleware `SetUpPanel` faz isso num request real. */
function comOPainelAppBootado(string $slug): void
{
    test()->get("/app/{$slug}");
}

it('[CT-01] sem o painel bootado, o tenant_id pedido é o gravado', function (): void {
    $convite = conviteCru('sem.boot@example.com', $this->globex->getKey());

    expect(Event::getListeners('eloquent.creating: App\Models\Convite'))->toBeEmpty()
        ->and(DB::table('convites')->where('id', $convite->getKey())->value('tenant_id'))
        ->toBe($this->globex->getKey());
});

/**
 * O caso que fica VERMELHO no dia em que o Filament passar a respeitar a coluna preenchida.
 *
 * Ele afirma o carimbo, não o deseja: se ficar vermelho, a leitura correta é "o vendor mudou —
 * revise `ofertaPara()` e a rule", e não "apareceu um bug".
 */
it('[CT-03] com o painel bootado, o Filament carimba a organização corrente por cima do pedido', function (): void {
    comOPainelAppBootado($this->acme->slug);
    noPainelDa($this->acme);

    $convite = conviteCru('com.boot@example.com', $this->globex->getKey());

    expect(Event::getListeners('eloquent.creating: App\Models\Convite'))->toHaveCount(1)
        ->and(DB::table('convites')->where('id', $convite->getKey())->value('tenant_id'))
        ->toBe($this->acme->getKey(), 'O Filament deixou de carimbar: revise ofertaPara() e a rule de testes.');
});

/**
 * A garantia que o helper dá, e que é o motivo de ele existir: quem escreve teste de fronteira
 * pede a Globex e recebe a Globex, com ou sem painel bootado.
 */
it('[CT-01] ofertaPara() entrega o convite na organização pedida mesmo com o painel bootado', function (): void {
    comOPainelAppBootado($this->acme->slug);
    noPainelDa($this->acme);

    $daGlobex = ofertaPara('convidado.globex@example.com', $this->globex);

    expect(DB::table('convites')->where('id', $daGlobex->getKey())->value('tenant_id'))
        ->toBe($this->globex->getKey())
        ->and($daGlobex->tenant_id)->toBe($this->globex->getKey());
});

/**
 * A correção do helper é CONDICIONAL — ela não age quando não há divergência.
 *
 * Sem este caso, uma correção incondicional (um `update` sempre, ou um `update` que escrevesse o
 * tenant corrente em vez do pedido) passaria despercebida: os outros casos ficariam verdes.
 */
it('[CT-02] ofertaPara() não mexe no registro quando o carimbo já concorda com o pedido', function (): void {
    comOPainelAppBootado($this->acme->slug);
    noPainelDa($this->acme);

    $daAcme = ofertaPara('convidado.acme@example.com', $this->acme);

    $atualizadoEm = DB::table('convites')->where('id', $daAcme->getKey())->value('updated_at');

    expect(DB::table('convites')->where('id', $daAcme->getKey())->value('tenant_id'))
        ->toBe($this->acme->getKey())
        ->and($atualizadoEm)->toBe($daAcme->created_at?->toDateTimeString());
});

/** Sem organização corrente o hook não carimba — é a segunda guarda do vendor. */
it('[CT-03] sem organização corrente o carimbo não acontece, mesmo com o painel bootado', function (): void {
    comOPainelAppBootado($this->acme->slug);
    noPainelDa($this->acme);

    Filament\Facades\Filament::setTenant(null);

    $convite = conviteCru('sem.tenant@example.com', $this->globex->getKey());

    expect(DB::table('convites')->where('id', $convite->getKey())->value('tenant_id'))
        ->toBe($this->globex->getKey());
});

/**
 * Cardinalidade do efeito, e os demais argumentos do helper.
 *
 * A correção mexe numa linha só, e não toca no que não é `tenant_id`: sem este caso, uma correção
 * escrita com `where('email', ...)` em vez de `where('id', ...)` — ou que reescrevesse a linha
 * inteira em vez da coluna — passaria em todos os outros cenários.
 */
it('[CT-05] duas ofertas para o mesmo e-mail guardam cada uma a sua organização, e os demais argumentos sobrevivem', function (): void {
    comOPainelAppBootado($this->acme->slug);
    noPainelDa($this->acme);

    $aceitoEm = now()->startOfSecond();

    $daAcme   = ofertaPara('mesmo@example.com', $this->acme, 'admin_app', ['aceito_em' => $aceitoEm]);
    $daGlobex = ofertaPara('mesmo@example.com', $this->globex);

    /*
     * Contagem no banco CRU, não por `Convite::query()`: o mesmo trait do vendor que carimba na
     * escrita registra no boot um `addGlobalScope($panel->getTenancyScopeName())`
     * (`BelongsToTenant.php:143-156`), então a leitura por Eloquent devolve só a Acme e o caso
     * mediria o escopo em vez da cardinalidade da correção.
     */
    expect(DB::table('convites')->where('email', 'mesmo@example.com')->count())->toBe(2)
        ->and($daAcme->tenant_id)->toBe($this->acme->getKey())
        ->and($daGlobex->tenant_id)->toBe($this->globex->getKey())
        ->and($daAcme->role_id)->toBe(Role::query()->where('name', 'admin_app')->value('id'))
        ->and($daAcme->aceito_em?->toDateTimeString())->toBe($aceitoEm->toDateTimeString());
});
