<?php

use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Models\Convite;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

/**
 * Excluir pela tela um papel que tem convite (wiki `excluir-papel-com-convites`, CT-01 a CT-10 e CT-13 a CT-18).
 *
 * Derivado do `04-casos-de-teste.md`. A situação de partida dos convites é gravada pelos três fatos
 * (`aceito_em`, `recusado_em`, `expira_em`): o convite não tem máquina de estados (Setup Global).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    noPainelBootado('admin');

    // Sem isto `audits` fica vazia e todo não-efeito de auditoria é vácuo.
    config(['audit.console' => true]);

    $this->travelTo('2026-11-16 12:00:00');
});

function papelDeSuporteParaExclusao(string $nome = 'suporte'): Role
{
    return Role::create(['name' => $nome, 'guard_name' => 'web', 'painel' => 'admin']);
}

/**
 * Executa a exclusão do papel por um dos três caminhos da tela, sem tratar exceção.
 *
 * @param  list<Role>  $papeis  só a massa usa mais de um
 */
function excluirPapelPeloCaminho(string $caminho, array $papeis): Testable
{
    if ($caminho === 'edicao') {
        return Livewire::test(EditRole::class, ['record' => $papeis[0]->getRouteKey()])
            ->mountAction(TestAction::make(DeleteAction::class))
            ->callMountedAction();
    }

    $lista = Livewire::test(ListRoles::class)->loadTable();

    if ($caminho === 'linha') {
        return $lista
            ->mountAction(TestAction::make(DeleteAction::class)->table($papeis[0]))
            ->callMountedAction();
    }

    return $lista
        ->selectTableRecords(array_map(fn (Role $papel) => $papel->getKey(), $papeis))
        ->mountAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->callMountedAction();
}

/** Título e corpo de cada aviso do Filament, em um texto só (sem ids nem metadados da notificação). */
function textoDosAvisosDeExclusao(): string
{
    return implode("\n", array_map(
        fn (array $aviso): string => strip_tags(($aviso['title'] ?? '').' '.($aviso['body'] ?? '')),
        avisosDoFilament(),
    ));
}

function algumAvisoDeSucessoNaExclusao(): bool
{
    return collect(avisosDoFilament())->contains(fn (array $aviso): bool => ($aviso['status'] ?? null) === 'success');
}

/** Algum aviso leva ação ou link para a tela de Convites do `/admin`. */
function algumAvisoComAtalhoParaConvites(): bool
{
    $caminho = parse_url(ConviteResource::getUrl(panel: 'admin'), PHP_URL_PATH);

    foreach (avisosDoFilament() as $aviso) {
        $bruto = json_encode([$aviso['actions'] ?? [], $aviso['title'] ?? '', $aviso['body'] ?? ''], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (str_contains((string) $bruto, (string) $caminho)) {
            return true;
        }
    }

    return false;
}

/**
 * O trecho do papel no aviso: do rótulo dele até o rótulo do próximo papel bloqueado (ou o fim).
 *
 * @param  list<string>  $rotulos  os rótulos de todos os papéis bloqueados da seleção
 */
function trechoDoPapelNoAviso(string $texto, string $rotulo, array $rotulos): string
{
    $inicio = strpos($texto, $rotulo);

    expect($inicio)->not->toBeFalse("o aviso não nomeia o papel {$rotulo}: {$texto}");

    $fim = strlen($texto);

    foreach ($rotulos as $outro) {
        $posicao = strpos($texto, $outro, $inicio + strlen($rotulo));

        if ($outro !== $rotulo && $posicao !== false) {
            $fim = min($fim, $posicao);
        }
    }

    return substr($texto, $inicio, $fim - $inicio);
}

function numeroIsoladoNoTexto(string $texto, int $numero): bool
{
    return preg_match('/(?<!\d)'.$numero.'(?!\d)/', $texto) === 1;
}

function papelExisteNoBanco(Role $papel): bool
{
    return DB::table('roles')->where('id', $papel->getKey())->exists();
}

function conviteExisteNoBanco(Convite $convite): bool
{
    return DB::table('convites')->where('id', $convite->getKey())->exists();
}

function auditoriasDoConvite(Convite $convite, string $evento): int
{
    return DB::table('audits')
        ->where('auditable_type', Convite::class)
        ->where('auditable_id', $convite->getKey())
        ->where('event', $evento)
        ->count();
}

/**
 * O papel mantém a permissão `View:Role` e o usuário `rui@example.com` (nada foi desligado pelo `deleting` do spatie).
 */
function papelMantemPermissaoEUsuario(Role $papel): bool
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $temPermissao = DB::table('role_has_permissions')
        ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
        ->where('role_has_permissions.role_id', $papel->getKey())
        ->where('permissions.name', 'View:Role')
        ->exists();

    $temUsuario = DB::table(pivotDePapeis())
        ->where('role_id', $papel->getKey())
        ->where('model_id', User::query()->where('email', 'rui@example.com')->value('id'))
        ->exists();

    return $temPermissao && $temUsuario;
}

/** O papel com a permissão e o usuário que a recusa não pode desligar (R2). */
function papelComPermissaoEUsuarioParaExclusao(string $nome = 'suporte'): Role
{
    $papel = papelDeSuporteParaExclusao($nome);
    $papel->givePermissionTo('View:Role');
    usuario('rui@example.com')->assignRole($nome);

    return $papel;
}

/**
 * Os convites do papel: Pendente (opcional), Aceito, Recusado e Expirado.
 *
 * @return array<string, Convite>
 */
function convitesDoPapelParaExclusao(string $papel = 'suporte', bool $comPendente = true, array $atributos = []): array
{
    $convites = [];

    if ($comPendente) {
        $convites['pendente'] = ofertaPara('carla@example.com', papel: $papel, atributos: $atributos);
    }

    return $convites + [
        'aceito'   => ofertaPara('ana@example.com', papel: $papel, atributos: ['aceito_em' => now()] + $atributos),
        'recusado' => ofertaPara('bia@example.com', papel: $papel, atributos: ['recusado_em' => now()] + $atributos),
        'expirado' => ofertaPara('caio@example.com', papel: $papel, atributos: ['expira_em' => now()->subDay()] + $atributos),
    ];
}

/** O operador `admin` (não-master) com a permissão de ver convites concedida ou revogada. */
function operadorAdminParaExclusao(?bool $comVerConvites): User
{
    $operador = usuarioDoKit('admin', 'operador.rui@example.com');

    if ($comVerConvites === true) {
        papelDoKit('admin')->givePermissionTo('ViewAny:Convite');
    } elseif ($comVerConvites === false) {
        semAPermissao('admin', 'ViewAny:Convite');
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $operador->fresh();
}

/*
|--------------------------------------------------------------------------
| R1 — Só o convite Pendente bloqueia
|--------------------------------------------------------------------------
*/

it('[CT-01] a situação do único convite do papel decide se a exclusão é recusada', function (string $situacao, bool $recusa): void {
    $papel = papelDeSuporteParaExclusao();

    $convite = match ($situacao) {
        'sem convite'      => null,
        'pendente borda+1' => ofertaPara('carla@example.com', papel: 'suporte', atributos: ['expira_em' => now()->addSecond()]),
        'borda'            => ofertaPara('carla@example.com', papel: 'suporte', atributos: ['expira_em' => now()]),
        'expirado borda-1' => ofertaPara('carla@example.com', papel: 'suporte', atributos: ['expira_em' => now()->subSecond()]),
        'sem validade'     => ofertaPara('carla@example.com', papel: 'suporte', atributos: ['expira_em' => null]),
        'aceito'           => ofertaPara('carla@example.com', papel: 'suporte', atributos: ['aceito_em' => now(), 'expira_em' => now()->addDay()]),
        'recusado'         => ofertaPara('carla@example.com', papel: 'suporte', atributos: ['recusado_em' => now(), 'expira_em' => now()->addDay()]),
    };

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho('linha', [$papel]);

    expect(papelExisteNoBanco($papel))->toBe($recusa);

    if ($convite !== null) {
        expect(conviteExisteNoBanco($convite))->toBe($recusa);
    }
})->with([
    'sem convite algum'            => ['sem convite', false],
    'Pendente, validade 12:00:01'  => ['pendente borda+1', true],
    'validade 12:00:00 (a borda)'  => ['borda', false],
    'Expirado, validade 11:59:59'  => ['expirado borda-1', false],
    'Expirado, sem validade'       => ['sem validade', false],
    'Aceito com validade futura'   => ['aceito', false],
    'Recusado com validade futura' => ['recusado', false],
]);

/*
|--------------------------------------------------------------------------
| R2 — Recusa pelos quatro caminhos, sem 500, e nada é apagado
|--------------------------------------------------------------------------
*/

it('[CT-02] a exclusão pela tela é recusada com aviso e não apaga nada, em cada caminho', function (string $caminho): void {
    $papel    = papelComPermissaoEUsuarioParaExclusao();
    $convites = convitesDoPapelParaExclusao();

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    // A chamada termina sem exceção: uma `QueryException` não tratada estoura aqui.
    excluirPapelPeloCaminho($caminho, [$papel]);

    $texto = textoDosAvisosDeExclusao();

    expect($texto)->toContain('carla@example.com')
        ->and($texto)->not->toContain('ana@example.com')
        ->and($texto)->not->toContain('bia@example.com')
        ->and($texto)->not->toContain('caio@example.com')
        ->and(algumAvisoDeSucessoNaExclusao())->toBeFalse()
        ->and(papelExisteNoBanco($papel))->toBeTrue()
        ->and(papelMantemPermissaoEUsuario($papel))->toBeTrue();

    foreach ($convites as $convite) {
        expect(conviteExisteNoBanco($convite))->toBeTrue()
            ->and((int) DB::table('convites')->where('id', $convite->getKey())->value('role_id'))->toBe($papel->getKey())
            ->and(auditoriasDoConvite($convite, 'deleted'))->toBe(0);
    }
})->with([
    'ação Excluir da linha'               => ['linha'],
    'ação Excluir do cabeçalho da edição' => ['edicao'],
    'exclusão em massa só com o suporte'  => ['massa'],
]);

it('[CT-03] a exclusão direta do papel, por fora da tela, também não apaga nada', function (): void {
    $papel    = papelComPermissaoEUsuarioParaExclusao();
    $convites = convitesDoPapelParaExclusao();

    // Não afirma se há exceção nem de que tipo (mecanismo, Q5/D4): o oráculo é o banco.
    try {
        $papel->delete();
    } catch (Throwable) {
    }

    expect(papelExisteNoBanco($papel))->toBeTrue()
        ->and(papelMantemPermissaoEUsuario($papel))->toBeTrue();

    foreach ($convites as $convite) {
        expect(conviteExisteNoBanco($convite))->toBeTrue()
            ->and((int) DB::table('convites')->where('id', $convite->getKey())->value('role_id'))->toBe($papel->getKey());
    }
});

/*
|--------------------------------------------------------------------------
| R3 — O aviso lista os e-mails dos Pendentes do papel, no máximo 5 por papel
|--------------------------------------------------------------------------
*/

it('[CT-04] o aviso lista só os e-mails Pendentes do papel excluído', function (): void {
    $papel = papelDeSuporteParaExclusao();
    papelDeSuporteParaExclusao('vendas');

    ofertaPara('carla@example.com', papel: 'suporte');
    ofertaPara('dani@example.com', papel: 'suporte');
    ofertaPara('caio@example.com', papel: 'suporte', atributos: ['expira_em' => now()->subDay()]);
    ofertaPara('eva@example.com', papel: 'vendas');

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho('linha', [$papel]);

    $texto = textoDosAvisosDeExclusao();

    expect($texto)->toContain('carla@example.com', 'dani@example.com')
        ->and($texto)->not->toContain('caio@example.com')
        ->and($texto)->not->toContain('eva@example.com');
});

it('[CT-05] o aviso mostra no máximo 5 e-mails por papel e diz quantos Pendentes faltam', function (array $chaves, int $pendentes, string $caminho, int $listados, string $resto): void {
    $papeis  = [];
    $emails  = [];
    $mortos  = [];
    $rotulos = [];

    foreach ($chaves as $chave) {
        $papeis[$chave]  = papelDeSuporteParaExclusao($chave);
        $rotulos[$chave] = RoleResource::getRecordTitle($papeis[$chave]);

        for ($i = 1; $i <= $pendentes; $i++) {
            $emails[$chave][] = "{$chave}.pend{$i}@example.com";
            ofertaPara("{$chave}.pend{$i}@example.com", papel: $chave);
        }

        $mortos[] = "{$chave}.expirado@example.com";
        $mortos[] = "{$chave}.aceito@example.com";
        ofertaPara("{$chave}.expirado@example.com", papel: $chave, atributos: ['expira_em' => now()->subDay()]);
        ofertaPara("{$chave}.aceito@example.com", papel: $chave, atributos: ['aceito_em' => now(), 'expira_em' => now()->addDay()]);
    }

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho($caminho, array_values($papeis));

    $texto     = textoDosAvisosDeExclusao();
    $presentes = count(array_filter(array_merge(...array_values($emails)), fn (string $email): bool => str_contains($texto, $email)));

    expect($presentes)->toBe($listados);

    foreach ($mortos as $morto) {
        expect($texto)->not->toContain($morto);
    }

    match ($resto) {
        'nenhum' => expect($texto)->not->toContain('e mais'),
        'um'     => expect($texto)->toContain('e mais 1')->and($texto)->not->toContain('e mais 3'),
        'massa'  => (function () use ($texto, $rotulos): void {
            foreach ($rotulos as $rotulo) {
                expect(trechoDoPapelNoAviso($texto, $rotulo, array_values($rotulos)))->toContain('e mais 1');
            }

            expect($texto)->not->toContain('e mais 7');
        })(),
    };
})->with([
    '5 Pendentes de um papel (borda)'        => [['suporte'], 5, 'linha', 5, 'nenhum'],
    '6 Pendentes de um papel (borda+1)'      => [['suporte'], 6, 'linha', 5, 'um'],
    '6 Pendentes em cada um de dois, massa'  => [['suporte', 'gerente_de_contas'], 6, 'massa', 10, 'massa'],
]);

/*
|--------------------------------------------------------------------------
| R4 — Na massa, um papel bloqueado barra a seleção inteira
|--------------------------------------------------------------------------
*/

it('[CT-06] um papel bloqueado na seleção impede a exclusão do papel livre', function (): void {
    $financeiro = papelDeSuporteParaExclusao('financeiro_pj');
    $suporte    = papelDeSuporteParaExclusao('suporte');

    $eva   = ofertaPara('eva@example.com', papel: 'financeiro_pj', atributos: ['expira_em' => now()->subDay()]);
    $carla = ofertaPara('carla@example.com', papel: 'suporte');

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho('massa', [$financeiro, $suporte]);

    $texto = textoDosAvisosDeExclusao();

    expect(papelExisteNoBanco($financeiro))->toBeTrue()
        ->and(papelExisteNoBanco($suporte))->toBeTrue()
        ->and(conviteExisteNoBanco($eva))->toBeTrue()
        ->and(conviteExisteNoBanco($carla))->toBeTrue()
        ->and($texto)->toContain('carla@example.com')
        ->and(algumAvisoDeSucessoNaExclusao())->toBeFalse()
        ->and($texto)->not->toContain('Financeiro Pj')
        ->and($texto)->not->toContain('financeiro_pj');
});

it('[CT-07] o aviso da massa traz cada papel bloqueado com os próprios e-mails', function (): void {
    $suporte = papelDeSuporteParaExclusao('suporte');
    $gerente = papelDeSuporteParaExclusao('gerente_de_contas');

    expect(RoleResource::getRecordTitle($gerente))->toBe('Gerente De Contas');

    $carla = ofertaPara('carla@example.com', papel: 'suporte');
    $dani  = ofertaPara('dani@example.com', papel: 'gerente_de_contas');
    $hel   = ofertaPara('hel@example.com', papel: 'gerente_de_contas', atributos: ['expira_em' => now()->subDay()]);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho('massa', [$suporte, $gerente]);

    $texto   = textoDosAvisosDeExclusao();
    $rotulos = ['Suporte', 'Gerente De Contas'];

    expect(papelExisteNoBanco($suporte))->toBeTrue()
        ->and(papelExisteNoBanco($gerente))->toBeTrue()
        ->and(conviteExisteNoBanco($carla))->toBeTrue()
        ->and(conviteExisteNoBanco($dani))->toBeTrue()
        ->and(conviteExisteNoBanco($hel))->toBeTrue();

    $trechoSuporte = trechoDoPapelNoAviso($texto, 'Suporte', $rotulos);
    $trechoGerente = trechoDoPapelNoAviso($texto, 'Gerente De Contas', $rotulos);

    expect($trechoSuporte)->toContain('carla@example.com')->and($trechoSuporte)->not->toContain('dani@example.com')
        ->and($trechoGerente)->toContain('dani@example.com')->and($trechoGerente)->not->toContain('carla@example.com');
});

/*
|--------------------------------------------------------------------------
| R5 — Os convites mortos somem com o papel, e a remoção fica auditada
|--------------------------------------------------------------------------
*/

it('[CT-08] excluir papel sem Pendente apaga os convites mortos dele, só os dele, auditando cada um uma vez', function (string $caminho): void {
    $papel  = papelDeSuporteParaExclusao();
    $lia    = usuario('lia@example.com');
    papelDeSuporteParaExclusao('vendas');

    $mortos = convitesDoPapelParaExclusao(comPendente: false, atributos: ['convidado_por_id' => $lia->getKey()]);
    $eva    = ofertaPara('eva@example.com', papel: 'vendas', atributos: ['expira_em' => now()->subDay()]);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho($caminho, [$papel]);

    expect(papelExisteNoBanco($papel))->toBeFalse();

    foreach ($mortos as $convite) {
        $antigos = json_decode((string) DB::table('audits')
            ->where('auditable_type', Convite::class)
            ->where('auditable_id', $convite->getKey())
            ->where('event', 'deleted')
            ->value('old_values'), true) ?? [];

        expect(conviteExisteNoBanco($convite))->toBeFalse()
            ->and(auditoriasDoConvite($convite, 'deleted'))->toBe(1)
            ->and($antigos['email'] ?? null)->toBe($convite->email)
            ->and($antigos['role_id'] ?? null)->toBe($papel->getKey())
            ->and($antigos['convidado_por_id'] ?? null)->toBe($lia->getKey())
            ->and(auditoriasDoConvite($convite, 'created'))->toBeGreaterThanOrEqual(1);
    }

    expect(conviteExisteNoBanco($eva))->toBeTrue()
        ->and(auditoriasDoConvite($eva, 'deleted'))->toBe(0);
})->with([
    'ação Excluir da linha'               => ['linha'],
    'ação Excluir do cabeçalho da edição' => ['edicao'],
    'exclusão em massa só com o suporte'  => ['massa'],
]);

it('[CT-14] a exclusão em massa de dois papéis livres apaga e audita os mortos dos dois', function (): void {
    $suporte = papelDeSuporteParaExclusao('suporte');
    $vendas  = papelDeSuporteParaExclusao('vendas');

    $caio = ofertaPara('caio@example.com', papel: 'suporte', atributos: ['expira_em' => now()->subDay()]);
    $bia  = ofertaPara('bia@example.com', papel: 'vendas', atributos: ['recusado_em' => now()]);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho('massa', [$suporte, $vendas]);

    expect(papelExisteNoBanco($suporte))->toBeFalse()
        ->and(papelExisteNoBanco($vendas))->toBeFalse()
        ->and(conviteExisteNoBanco($caio))->toBeFalse()
        ->and(conviteExisteNoBanco($bia))->toBeFalse()
        ->and(auditoriasDoConvite($caio, 'deleted'))->toBe(1)
        ->and(auditoriasDoConvite($bia, 'deleted'))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| R6 — A saída da recusa: aceitar continua possível, revogar libera
|--------------------------------------------------------------------------
*/

it('[CT-09] o convite Pendente continua aceitável com o papel depois da recusa', function (): void {
    $papel   = papelDeSuporteParaExclusao();
    $convite = ofertaPara('carla@example.com', papel: 'suporte');

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho('linha', [$papel]);

    $aceito = $convite->fresh()->aceitar(['name' => 'Carla', 'password' => 'segredo-bem-longo-123']);

    $usuario = User::query()->where('email', 'carla@example.com')->first();

    expect($usuario)->not->toBeNull()
        ->and($aceito->getKey())->toBe($usuario->getKey())
        ->and($usuario->hasRole('suporte'))->toBeTrue()
        ->and($convite->fresh()->aceito_em)->not->toBeNull();
});

it('[CT-10] revogado o convite Pendente na tela de Convites, a nova exclusão passa', function (): void {
    $papel = papelDeSuporteParaExclusao();
    $carla = ofertaPara('carla@example.com', papel: 'suporte');
    $caio  = ofertaPara('caio@example.com', papel: 'suporte', atributos: ['expira_em' => now()->subDay()]);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    excluirPapelPeloCaminho('linha', [$papel]);

    expect(papelExisteNoBanco($papel))->toBeTrue();

    Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('delete')->table($carla));

    expect(conviteExisteNoBanco($carla))->toBeFalse();

    limparAvisosDoFilament();

    excluirPapelPeloCaminho('linha', [$papel]);

    expect(papelExisteNoBanco($papel))->toBeFalse()
        ->and(conviteExisteNoBanco($caio))->toBeFalse()
        ->and(textoDosAvisosDeExclusao())->not->toContain('carla@example.com');
});

/*
|--------------------------------------------------------------------------
| R8 — Os e-mails só aparecem para quem pode ver os convites
|--------------------------------------------------------------------------
*/

it('[CT-13] a permissão de ver convites decide entre e-mails e quantidade, na linha e na edição', function (bool $comPermissao, string $caminho): void {
    $papel = papelDeSuporteParaExclusao();
    papelDeSuporteParaExclusao('vendas');

    $convites = [
        ofertaPara('pend.carla@example.com', papel: 'suporte'),
        ofertaPara('pend.dani@example.com', papel: 'suporte'),
        ofertaPara('pend.fabi@example.com', papel: 'suporte'),
        ofertaPara('caio@example.com', papel: 'suporte', atributos: ['expira_em' => now()->subDay()]),
        ofertaPara('ana@example.com', papel: 'suporte', atributos: ['aceito_em' => now(), 'expira_em' => now()->addDay()]),
        ofertaPara('bia@example.com', papel: 'suporte', atributos: ['recusado_em' => now(), 'expira_em' => now()->addDay()]),
    ];
    ofertaPara('eva@example.com', papel: 'vendas');

    $operador = operadorAdminParaExclusao($comPermissao);

    // Sanidade: a única variável é a permissão de ver convites; a de excluir papéis vale nos dois lados.
    expect($operador->can('ViewAny:Convite'))->toBe($comPermissao)
        ->and($operador->can('Delete:Role'))->toBeTrue();

    $this->actingAs($operador);

    excluirPapelPeloCaminho($caminho, [$papel]);

    expect(papelExisteNoBanco($papel))->toBeTrue();

    foreach ($convites as $convite) {
        expect(conviteExisteNoBanco($convite))->toBeTrue();
    }

    $texto = textoDosAvisosDeExclusao();

    if ($comPermissao) {
        expect($texto)->toContain('pend.carla@example.com', 'pend.dani@example.com', 'pend.fabi@example.com')
            ->and($texto)->not->toContain('caio@example.com')
            ->and($texto)->not->toContain('ana@example.com')
            ->and($texto)->not->toContain('bia@example.com')
            ->and($texto)->not->toContain('eva@example.com');

        return;
    }

    expect(numeroIsoladoNoTexto($texto, 3))->toBeTrue()
        ->and($texto)->not->toContain('@example.com')
        ->and($texto)->not->toContain('pend.carla')
        ->and($texto)->not->toContain('pend.dani')
        ->and($texto)->not->toContain('pend.fabi')
        ->and(algumAvisoComAtalhoParaConvites())->toBeFalse();

    foreach ([4, 5, 6, 7] as $numero) {
        expect(numeroIsoladoNoTexto($texto, $numero))->toBeFalse("o aviso traz o número {$numero}: {$texto}");
    }
})->with([
    'com ViewAny:Convite, linha'   => [true, 'linha'],
    'com ViewAny:Convite, edição'  => [true, 'edicao'],
    'sem ViewAny:Convite, linha'   => [false, 'linha'],
    'sem ViewAny:Convite, edição'  => [false, 'edicao'],
]);

it('[CT-17] na massa, a quantidade sem a permissão é por papel, e os e-mails com ela ficam no trecho de cada papel', function (bool $comPermissao): void {
    $suporte = papelDeSuporteParaExclusao('suporte');
    $gerente = papelDeSuporteParaExclusao('gerente_de_contas');

    $convites = [
        ofertaPara('pend.carla@example.com', papel: 'suporte'),
        ofertaPara('pend.dani@example.com', papel: 'suporte'),
        ofertaPara('pend.fabi@example.com', papel: 'suporte'),
        ofertaPara('caio@example.com', papel: 'suporte', atributos: ['expira_em' => now()->subDay()]),
        ofertaPara('ana@example.com', papel: 'suporte', atributos: ['aceito_em' => now(), 'expira_em' => now()->addDay()]),
        ofertaPara('bia@example.com', papel: 'suporte', atributos: ['recusado_em' => now(), 'expira_em' => now()->addDay()]),
        ofertaPara('pend.gil@example.com', papel: 'gerente_de_contas'),
        ofertaPara('pend.hel@example.com', papel: 'gerente_de_contas'),
    ];

    $operador = operadorAdminParaExclusao($comPermissao);

    expect($operador->can('ViewAny:Convite'))->toBe($comPermissao)
        ->and($operador->can('Delete:Role'))->toBeTrue();

    $this->actingAs($operador);

    excluirPapelPeloCaminho('massa', [$suporte, $gerente]);

    expect(papelExisteNoBanco($suporte))->toBeTrue()
        ->and(papelExisteNoBanco($gerente))->toBeTrue();

    foreach ($convites as $convite) {
        expect(conviteExisteNoBanco($convite))->toBeTrue();
    }

    $texto         = textoDosAvisosDeExclusao();
    $rotulos       = ['Suporte', 'Gerente De Contas'];
    $trechoSuporte = trechoDoPapelNoAviso($texto, 'Suporte', $rotulos);
    $trechoGerente = trechoDoPapelNoAviso($texto, 'Gerente De Contas', $rotulos);

    if ($comPermissao) {
        expect($trechoSuporte)->toContain('pend.carla@example.com', 'pend.dani@example.com', 'pend.fabi@example.com')
            ->and($trechoSuporte)->not->toContain('pend.gil')
            ->and($trechoSuporte)->not->toContain('pend.hel')
            ->and($trechoSuporte)->not->toContain('caio@')
            ->and($trechoSuporte)->not->toContain('ana@')
            ->and($trechoSuporte)->not->toContain('bia@')
            ->and($trechoGerente)->toContain('pend.gil@example.com', 'pend.hel@example.com')
            ->and($trechoGerente)->not->toContain('pend.carla')
            ->and($trechoGerente)->not->toContain('pend.dani')
            ->and($trechoGerente)->not->toContain('pend.fabi');

        return;
    }

    expect(numeroIsoladoNoTexto($trechoSuporte, 3))->toBeTrue()
        ->and(numeroIsoladoNoTexto($trechoGerente, 2))->toBeTrue()
        ->and(numeroIsoladoNoTexto($trechoSuporte, 5))->toBeFalse()
        ->and(numeroIsoladoNoTexto($trechoSuporte, 6))->toBeFalse()
        ->and(numeroIsoladoNoTexto($texto, 5))->toBeFalse()
        ->and($texto)->not->toContain('@example.com')
        ->and($texto)->not->toContain('pend.')
        ->and($texto)->not->toContain('caio')
        ->and($texto)->not->toContain('ana@')
        ->and($texto)->not->toContain('bia@')
        ->and(algumAvisoComAtalhoParaConvites())->toBeFalse();
})->with([
    'com ViewAny:Convite' => [true],
    'sem ViewAny:Convite' => [false],
]);

it('[CT-18] sem a permissão, o aviso esconde os e-mails mesmo de quem convidou ou foi convidado', function (string $envolvimento): void {
    $papel    = papelDeSuporteParaExclusao();
    $operador = operadorAdminParaExclusao(false);

    expect($operador->can('ViewAny:Convite'))->toBeFalse()
        ->and($operador->can('Delete:Role'))->toBeTrue();

    $locais = $envolvimento === 'convidou'
        ? ['pend.carla', 'pend.dani', 'pend.fabi']
        : ['pend.carla', 'pend.dani', 'operador.rui'];

    $convites = [];

    foreach ($locais as $local) {
        $convites[] = ofertaPara("{$local}@example.com", papel: 'suporte', atributos: [
            'convidado_por_id' => $envolvimento === 'convidou' ? $operador->getKey() : null,
        ]);
    }

    $this->actingAs($operador);

    excluirPapelPeloCaminho('linha', [$papel]);

    expect(papelExisteNoBanco($papel))->toBeTrue();

    foreach ($convites as $convite) {
        expect(conviteExisteNoBanco($convite))->toBeTrue();
    }

    $texto = textoDosAvisosDeExclusao();

    expect(numeroIsoladoNoTexto($texto, 3))->toBeTrue()
        ->and($texto)->not->toContain('@example.com')
        ->and(algumAvisoComAtalhoParaConvites())->toBeFalse();

    foreach ($locais as $local) {
        expect($texto)->not->toContain($local);
    }
})->with([
    'todos convidados pelo próprio operador'      => ['convidou'],
    'um deles para o e-mail do próprio operador'  => ['convidado'],
]);

/*
|--------------------------------------------------------------------------
| R9 — Quem pode excluir o papel apaga os convites encerrados
|--------------------------------------------------------------------------
*/

it('[CT-15] o operador sem permissão de convite exclui o papel e os convites encerrados saem auditados', function (): void {
    $papel = papelDeSuporteParaExclusao();

    $operador = usuarioDoKit('admin', 'operador.rui@example.com');

    $permissoesDeConvite = papelDoKit('admin')->permissions
        ->pluck('name')
        ->filter(fn (string $nome): bool => str_ends_with($nome, ':Convite'))
        ->all();

    semAPermissao('admin', ...$permissoesDeConvite);
    $operador = $operador->fresh();

    expect($operador->can('ViewAny:Convite'))->toBeFalse()
        ->and($operador->can('Delete:Convite'))->toBeFalse()
        ->and($operador->can('Delete:Role'))->toBeTrue();

    $mortos = convitesDoPapelParaExclusao(comPendente: false);

    $this->actingAs($operador);

    excluirPapelPeloCaminho('linha', [$papel]);

    expect(papelExisteNoBanco($papel))->toBeFalse();

    foreach ($mortos as $convite) {
        expect(conviteExisteNoBanco($convite))->toBeFalse()
            ->and(auditoriasDoConvite($convite, 'deleted'))->toBe(1)
            ->and(DB::table('audits')
                ->where('auditable_type', Convite::class)
                ->where('auditable_id', $convite->getKey())
                ->where('event', 'deleted')
                ->value('user_id'))->toEqual($operador->getKey());
    }
});

/*
|--------------------------------------------------------------------------
| R10 — O papel bloqueado aparece pelo rótulo
|--------------------------------------------------------------------------
*/

it('[CT-16] o aviso nomeia o papel bloqueado pelo rótulo, em cada caminho e nas duas formas do aviso', function (string $quem, string $caminho): void {
    $papel = papelDeSuporteParaExclusao('gerente_de_contas');

    expect(RoleResource::getRecordTitle($papel))->toBe('Gerente De Contas');

    ofertaPara('carla@example.com', papel: 'gerente_de_contas');

    if ($quem === 'master') {
        $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    } else {
        $operador = operadorAdminParaExclusao(false);

        expect($operador->can('ViewAny:Convite'))->toBeFalse()
            ->and($operador->can('Delete:Role'))->toBeTrue();

        $this->actingAs($operador);
    }

    excluirPapelPeloCaminho($caminho, [$papel]);

    expect(papelExisteNoBanco($papel))->toBeTrue()
        ->and(textoDosAvisosDeExclusao())->toContain('Gerente De Contas')
        ->and(textoDosAvisosDeExclusao())->not->toContain('gerente_de_contas');
})->with([
    'master, linha, com e-mails'                    => ['master', 'linha'],
    'master, edição, com e-mails'                   => ['master', 'edicao'],
    'master, massa, com e-mails'                    => ['master', 'massa'],
    'operador sem ViewAny:Convite, linha'           => ['operador', 'linha'],
    'operador sem ViewAny:Convite, edição'          => ['operador', 'edicao'],
]);

/*
|--------------------------------------------------------------------------
| R11 — A exclusão é atômica, e o Pendente do momento nunca sai como encerrado
|--------------------------------------------------------------------------
*/

it('[CT-20] uma intercorrência no meio da exclusão não deixa nada pela metade', function (string $intercorrencia): void {
    $papel    = papelComPermissaoEUsuarioParaExclusao();
    $convites = convitesDoPapelParaExclusao(comPendente: false);

    // Fake depois da fixture: o reenvio da linha 2 não pode mandar e-mail de verdade.
    Notification::fake();

    $disparou = false;
    $caio     = $convites['expirado'];
    $dani     = null;

    if ($intercorrencia === 'criado') {
        // Dentro do `parent::delete()`, depois da checagem de Pendentes: o `DELETE` do papel bate na FK.
        Role::deleting(function (Role $role) use (&$disparou, &$dani): void {
            if ($disparou || $role->name !== 'suporte') {
                return;
            }

            $disparou = true;
            $dani     = ofertaPara('dani@example.com', null, 'suporte');
        });
    } else {
        // No primeiro convite apagado que não é o do caio, o caio é reenviado (volta a Pendente).
        $caioJaApagadoAntes = false;

        Convite::deleting(function (Convite $convite) use (&$disparou, &$caioJaApagadoAntes, $caio): void {
            if ($disparou) {
                return;
            }

            if ($convite->getKey() === $caio->getKey()) {
                $caioJaApagadoAntes = true;
                $disparou           = true;

                return;
            }

            $disparou = true;
            $caio->fresh()?->enviar();
        });
    }

    // A forma da falha (exceção ou `false`) não é afirmada: o oráculo é o banco.
    try {
        $papel->delete();
    } catch (Throwable) {
    }

    if ($intercorrencia === 'reenviado' && ($caioJaApagadoAntes ?? false)) {
        $this->markTestSkipped('lacuna declarada (04, R11): a limpeza apagou o caio ANTES de apagar qualquer outro encerrado; a ordem da limpeza não é do requisito, o reenvio no meio não pôde ser provocado.');
    }

    expect($disparou)->toBeTrue('a intercorrência não disparou: o arranjo não mediu nada');

    expect(papelExisteNoBanco($papel))->toBeTrue()
        ->and(papelMantemPermissaoEUsuario($papel))->toBeTrue();

    foreach ($convites as $convite) {
        expect(conviteExisteNoBanco($convite))->toBeTrue()
            ->and((int) DB::table('convites')->where('id', $convite->getKey())->value('role_id'))->toBe($papel->getKey())
            ->and(auditoriasDoConvite($convite, 'deleted'))->toBe(0);
    }

    /*
     * O convite "do meio" (dani criado, caio reenviado) não é afirmado: aqui ele nasce NA MESMA
     * conexão, dentro da transação da exclusão, e o rollback o desfaz junto. Num banco de verdade
     * ele é outra transação, que espera a trava e se aplica depois — o que esta pilha não exibe.
     * Não falsificável nesta pilha (04, R11); o caio, que já existia, está no laço acima.
     */
})->with([
    'Pendente criado no meio (a FK recusa o DELETE)'   => ['criado'],
    'Expirado reenviado no meio (Pendente na limpeza)' => ['reenviado'],
]);
