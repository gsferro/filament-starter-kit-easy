<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use OwenIt\Auditing\Models\Audit;

/**
 * Desativar e reativar conta entram na trilha de `/infra/audits`.
 *
 * ## O defeito que este arquivo guarda
 *
 * `App\Traits\AuditsFillables::getAuditInclude()` devolvia `getFillable()`, e `users.ativo`
 * **nunca** é `$fillable` — atribuição em massa com ela destrancaria conta. Quem a escreve é
 * `desativar()`/`reativar()`, com `forceFill(...)->save()` (`app/Models/User.php:307`, `:326`):
 * o evento `updated` dispara, o auditor observa, e o atributo era descartado pelo filtro.
 *
 * Resultado: a trilha registrava a troca do NOME do usuário e não registrava o CORTE DO ACESSO
 * dele — que é o evento que importa numa auditoria.
 *
 * ## Por que os casos chamam o model direto
 *
 * A barreira é do model, não da tela. Um cenário que passasse pela Action mediria o contexto, não
 * o filtro de auditoria — e ficaria verde com `auditaAlemDoFillable()` inteiro removido, porque a
 * Action grava outras colunas junto. Ver `.ai/rules/filament.md`, "asserção de identidade vive no
 * model".
 *
 * Ver ADR-05 de `wikis/specs/feat/estudo-de-pacotes-rodada-2/`.
 */
beforeEach(function (): void {
    /*
     * `audit.console` é false no kit (`config/audit.php:203`) e a suíte roda em console: sem esta
     * linha, `Auditable::isAuditingEnabled()`
     * (`vendor/owen-it/laravel-auditing/src/Auditable.php:552-559`) devolve false e a trilha nunca
     * é escrita. Os casos passariam por a tabela estar VAZIA em vez de por a coluna estar nela —
     * que é o pior resultado possível para um arquivo cuja tese é "a coluna entra na trilha".
     *
     * O mesmo arranjo está em `tests/Kit/ConviteTest.php`, com a mesma justificativa.
     */
    config(['audit.console' => true]);

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** A última linha de auditoria do usuário, ou `null`. */
function ultimaAuditoriaDe(User $user): ?Audit
{
    return Audit::query()
        ->where('auditable_type', $user->getMorphClass())
        ->where('auditable_id', $user->getKey())
        ->latest('id')
        ->first();
}

it('registra a desativacao da conta na trilha', function (): void {
    $alvo = usuarioDoKit('panel_user', 'alvo@example.com');

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $alvo->desativar();

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha)->not->toBeNull('desativar a conta não gerou linha em audits')
        ->and($trilha->event)->toBe('updated')
        ->and($trilha->old_values)->toHaveKey('ativo', true)
        ->and($trilha->new_values)->toHaveKey('ativo', false);
})->group('kit');

it('registra a reativacao da conta na trilha', function (): void {
    $alvo = usuarioDoKit('panel_user', 'alvo@example.com');
    $alvo->desativar();

    $alvo->reativar();

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha->old_values)->toHaveKey('ativo', false)
        ->and($trilha->new_values)->toHaveKey('ativo', true);
})->group('kit');

/**
 * `aprovacao_pendente` é o outro estado de fronteira de acesso do kit, e sai do `$fillable` pelo
 * mesmo motivo. Entrou junto na lista porque metade da correção deixaria o mesmo defeito vivo.
 */
it('registra a baixa da pendencia de aprovacao na trilha', function (): void {
    $alvo = usuarioDoKit('panel_user', 'alvo@example.com');
    $alvo->forceFill(['aprovacao_pendente' => true])->save();

    $alvo->forceFill(['aprovacao_pendente' => false])->save();

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha->old_values)->toHaveKey('aprovacao_pendente', true)
        ->and($trilha->new_values)->toHaveKey('aprovacao_pendente', false);
})->group('kit');

/**
 * A contraprova: a lista não vaza para o que não foi declarado.
 *
 * Um mutante que trocasse o filtro por "auditar tudo" deixaria os três casos acima verdes e este
 * vermelho — `remember_token` é ruído técnico e não pode entrar na trilha.
 */
it('mantem fora da trilha a coluna tecnica que ninguem declarou', function (): void {
    $alvo = usuarioDoKit('panel_user', 'alvo@example.com');

    $alvo->forceFill(['remember_token' => 'token-novo-qualquer'])->save();

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha?->new_values ?? [])->not->toHaveKey('remember_token');
})->group('kit');
