<?php

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Models\Convite;
use App\Models\Projeto;
use App\Models\Role;
use Gsferro\FilamentOdometerEasy\Navigation\OdometerNavigationBadge;

/**
 * O badge do painel do negócio conta a organização corrente — e só ela.
 *
 * Dar badge a todo Resource cria uma superfície que antes não existia: no `/app` os Resources são
 * escopados por organização, e um badge que contasse pela tabela inteira exibiria, no menu de uma
 * organização, quantos registros a outra tem. O requisito não menciona isso, mas ele sai direto de
 * "adicione o count no badge" — o count de um item de menu é o do que aquele item abre.
 *
 * Estes dois casos vivem em `tests/Tenancy` porque `noPainelDa()` e o escopo de organização só
 * existem com `permission.teams` ligado. Em `tests/Kit` eles ficariam VERDES contando zero pelo
 * motivo errado — o ramo fail-closed —, medindo a fronteira em vez do escopo.
 */

/**
 * Um papel qualquer, para a `ConviteFactory` ter `role_id`.
 *
 * Helper de um arquivo só, então fica no arquivo (`.ai/rules/testes.md`). O papel não participa de
 * nenhuma asserção: o que se conta aqui é convite, não permissão.
 *
 * Pelo mesmo motivo, os cenários usam `tenant()` e não `duasOrganizacoes()`: aquele helper monta
 * também uma PESSOA com papéis semeados, e o badge não depende de quem está logado — depende da
 * organização corrente. Exigir o `PapeisSeeder` custaria segundos por caso sem mudar nenhuma
 * asserção.
 */
function papelParaConvite(): Role
{
    return Role::firstOrCreate(['name' => 'papel-do-badge', 'guard_name' => 'web']);
}

/*
|--------------------------------------------------------------------------
| R3 — a contagem é a da organização corrente
|--------------------------------------------------------------------------
*/

/**
 * CT-03 — o badge não soma organizações.
 *
 * Contagens DIFERENTES de propósito. Com 1 e 1, uma implementação que devolvesse "a primeira
 * organização" acertaria por acidente; com 3 e 1, cada defeito produz um número distinto:
 * ignorar o escopo dá 4, fixar a primeira dá 3 nas duas voltas, e cair no fail-closed dá 0.
 */
it('[CT-03] conta no badge apenas os registros da organização corrente', function (string $slug, int $esperado): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    papelParaConvite();

    Convite::factory()->count(3)->create(['tenant_id' => $acme->getKey()]);
    Convite::factory()->count(1)->create(['tenant_id' => $globex->getKey()]);

    noPainelDa($slug === 'acme' ? $acme : $globex);

    expect(ConviteResource::getNavigationBadge())
        ->toBe(OdometerNavigationBadge::make($esperado));
})->with([
    'organização maior' => ['acme', 3],
    'organização menor' => ['globex', 1],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — soft delete não conta
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — registro excluído por soft delete não conta.
 *
 * Três registros com UM excluído, e não dois com um: o esperado (2) precisa diferir tanto do total
 * bruto (3) quanto de qualquer constante plausível. Com 2 e 1, o esperado seria 1 —
 * indistinguível do mutante "devolve 1 quando há registro".
 *
 * `Projeto` e não `Convite`: `Convite` não usa `SoftDeletes` (medido). Os únicos models do kit com
 * soft delete são `Projeto` e `User`, e o `UserResource` do `/app` cai no ramo fail-closed sem
 * tenant, o que mediria outra coisa.
 */
it('[CT-08] não conta no badge registro excluído por soft delete', function (): void {
    noPainelDa(tenant('Acme', 'acme'));

    Projeto::create(['nome' => 'Contrato 2026']);
    Projeto::create(['nome' => 'Reforma da sede']);
    Projeto::create(['nome' => 'Migração encerrada'])->delete();

    expect(ProjetoResource::getNavigationBadge())
        ->toBe(OdometerNavigationBadge::make(2));
})->group('kit');
