<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Excluir usuário — e o listener global que ninguém registrou.
 *
 * `mddev31/filament-dynamic-dashboard` é auto-descoberto e, no boot, pendura um
 * `User::deleting` que apaga os dashboards PESSOAIS de quem está saindo. O listener roda
 * mesmo sem o plugin estar registrado em painel nenhum — não há como optar por não tê-lo.
 *
 * As migrations do pacote vêm como `.stub` e precisam de `vendor:publish`. Sem elas a
 * tabela `dashboards` não existe, e o listener derruba a exclusão de QUALQUER usuário com
 * `SQLSTATE[HY000]: no such table: dashboards` — nas três superfícies (a DeleteAction da
 * página de edição, a da tabela e a DeleteBulkAction).
 *
 * O defeito nasceu com o skeleton (`1eded2b`) e sobreviveu 449 casos verdes porque nenhum
 * deles excluía um usuário. Este arquivo fecha essa lacuna.
 */
beforeEach(function (): void {
    $this->dashboardPara = static fn (?int $usuarioId, bool $pessoal): int => (int) DB::table('dashboards')->insertGetId([
        'name'         => $pessoal ? 'Meu dashboard' : 'Dashboard da equipe',
        'is_personal'  => $pessoal,
        'created_by'   => $usuarioId,
        'template_key' => 'flat-12',
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
});

/**
 * O caso que reproduz o 500 relatado.
 *
 * Sem as migrations publicadas ele falha na própria exclusão, antes de qualquer asserção.
 */
it('exclui um usuário sem quebrar', function (): void {
    $user = usuario('a-excluir@example.com');
    $id   = $user->getKey();

    $user->delete();

    expect(User::query()->find($id))->toBeNull();
})->group('kit');

/**
 * A tabela é o que faltava. Sem ela o kit fica meio instalado: o pacote muda comportamento
 * global de model e o schema dele não existe — nem desligado, nem funcional.
 */
it('tem a tabela que o listener do pacote precisa', function (string $tabela): void {
    expect(Schema::hasTable($tabela))->toBeTrue();
})->with(['dashboards', 'dashboard_widgets'])->group('kit');

/**
 * Não basta não quebrar: o listener existe para não deixar dashboard pessoal órfão.
 */
it('leva junto o dashboard pessoal de quem foi excluído', function (): void {
    $user = usuario('dono@example.com');
    $id   = ($this->dashboardPara)($user->getKey(), pessoal: true);

    $user->delete();

    expect(DB::table('dashboards')->find($id))->toBeNull();
})->group('kit');

/**
 * O contorno do caso acima: dashboard COMPARTILHADO sobrevive.
 *
 * Com `SoftDeletes`, a linha do user permanece no banco — `ON DELETE SET NULL` da FK não
 * dispara. `created_by` mantém o ID original, e isso é o correto: o vínculo continua
 * válido (o user está soft-deleted, não removido). Apagar o painel da equipe junto com
 * quem o criou seria perda de dado de outras pessoas.
 */
it('preserva o dashboard compartilhado e mantém o autor', function (): void {
    $user = usuario('autor@example.com');
    $id   = ($this->dashboardPara)($user->getKey(), pessoal: false);

    $user->delete();

    $dashboard = DB::table('dashboards')->find($id);

    expect($dashboard)->not->toBeNull()
        ->and($dashboard->created_by)->toBe($user->getKey());
})->group('kit');

/**
 * O dashboard pessoal de OUTRA pessoa não pode ir junto — o listener filtra por
 * `created_by`, e um filtro errado só aparece quando há mais de um dono na tabela.
 */
it('não toca no dashboard pessoal de outro usuário', function (): void {
    $excluido    = usuario('sai@example.com');
    $preservado  = usuario('fica@example.com');
    $idDoOutro   = ($this->dashboardPara)($preservado->getKey(), pessoal: true);

    $excluido->delete();

    expect(DB::table('dashboards')->find($idDoOutro))->not->toBeNull();
})->group('kit');
