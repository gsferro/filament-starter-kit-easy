<?php

use App\Filament\Admin\Resources\AgentesIa\Pages\CreateAgenteIa;
use App\Filament\Admin\Resources\AgentesIa\Pages\ListAgentesIa;
use App\Models\AgenteIa;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * O catálogo de agentes de IA — o resource do `/admin` que não tinha UMA linha de teste.
 *
 * A auditoria de aderência ao Blueprint (N-31) achou `AgenteIaResource` inteiro fora da suíte:
 * nem validação do formulário, nem o filtro `ativo`, nem o par tem/não-tem de `ViewAny:AgenteIa`.
 * O `IaTest` toca o MODEL (seeders e `doSlug()`), nunca a tela. Este arquivo fecha as três lacunas.
 *
 * O que mais importa aqui é o `unique` do slug: é a chave que `AgenteBase` lê para achar o paper,
 * e dois registros com o mesmo slug fariam `doSlug()` devolver um deles ao acaso.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Um agente já cadastrado, para o `unique` do slug ter contra o que bater.
 *
 * `AgenteIa::create()` direto porque o model não tem factory, e os seeders de agente
 * (`AssistenteSeeder`, `GuardaPromptSeeder`) trariam guardrails e instruções que não são o
 * assunto destes casos.
 */
function agente(string $slug, bool $ativo = true): AgenteIa
{
    return AgenteIa::create([
        'slug'       => $slug,
        'nome'       => ucfirst($slug),
        'ativo'      => $ativo,
        'instrucoes' => 'Você é um agente de teste.',
        'versao'     => 1,
    ]);
}

/**
 * Uma linha por regra do `AgenteIaForm`. O payload de base é válido; cada linha estraga UM campo,
 * e a asserção nomeia a REGRA que tem de reprovar — `assertHasFormErrors(['slug'])` sem a regra
 * ficaria verde com o `unique` trocado por `required`.
 *
 * O não-efeito é um só para todas as linhas: o registro que estas linhas tentam criar não nasce.
 *
 * @param  array<string, mixed>  $estragado
 * @param  array<string, string>  $regras
 */
it('recusa o agente com o campo fora da regra e não grava nada', function (array $estragado, array $regras): void {
    agente('ja-existe');

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(CreateAgenteIa::class)
        ->fillForm(array_merge([
            'nome'       => 'Novo agente',
            'slug'       => 'novo-agente',
            'instrucoes' => 'Instruções de teste.',
            'versao'     => 1,
        ], $estragado))
        ->call('create')
        ->assertHasFormErrors($regras);

    expect(AgenteIa::where('slug', '!=', 'ja-existe')->exists())
        ->toBeFalse('o formulário reprovou e mesmo assim um agente foi gravado — a validação virou decorativa');
})->with([
    '`nome` é obrigatório'                   => [['nome' => null], ['nome' => 'required']],
    '`nome` passa de 120 caracteres'         => [['nome' => str_repeat('a', 121)], ['nome' => 'max']],
    '`slug` é obrigatório'                   => [['slug' => null], ['slug' => 'required']],
    '`slug` passa de 120 caracteres'         => [['slug' => str_repeat('a', 121)], ['slug' => 'max']],
    '`slug` já existe no catálogo'           => [['slug' => 'ja-existe'], ['slug' => 'unique']],
    '`instrucoes` é obrigatório'             => [['instrucoes' => null], ['instrucoes' => 'required']],
    '`versao` é obrigatório'                 => [['versao' => null], ['versao' => 'required']],
    '`versao` abaixo de 1'                   => [['versao' => 0], ['versao' => 'min']],
    '`max_tokens` precisa ser inteiro'       => [['max_tokens' => 1.5], ['max_tokens' => 'integer']],
    '`max_tokens` abaixo de 1'               => [['max_tokens' => 0], ['max_tokens' => 'min']],
    '`temperatura` acima de 2'               => [['temperatura' => 2.5], ['temperatura' => 'max']],
    '`temperatura` abaixo de 0'              => [['temperatura' => -0.1], ['temperatura' => 'min']],
]);

/**
 * O filtro `ativo` (`AgentesIaTable.php:33`) — os dois lados, com um registro de cada lado.
 *
 * Só o lado "vê" ficaria verde com o filtro removido: sem filtro a tabela mostra os dois. É o
 * `assertCanNotSeeTableRecords` que prova que o filtro filtra.
 */
it('filtra os agentes por ativo nos dois sentidos', function (bool $valor): void {
    $ativo   = agente('ativo');
    $inativo = agente('inativo', ativo: false);

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    [$visivel, $oculto] = $valor ? [$ativo, $inativo] : [$inativo, $ativo];

    Livewire::test(ListAgentesIa::class)
        ->loadTable()
        ->filterTable('ativo', $valor)
        ->assertCanSeeTableRecords([$visivel])
        ->assertCanNotSeeTableRecords([$oculto]);
})->with([
    'só os ativos'   => [true],
    'só os inativos' => [false],
]);

/**
 * O par tem / não-tem de `ViewAny:AgenteIa` para o papel `admin`.
 *
 * `admin` e não `master_global`: o master vence toda permissão pelo `Gate::before`, então com ele
 * o caso negativo é impossível. E revogar do papel REAL (`semAPermissao()`) é o único arranjo em
 * que a única variável é a permissão — um papel criado à mão sem nada perderia também o
 * `canAccessPanel()`, e o 403 viria da porta do painel, não da tela.
 */
it('abre o catálogo de agentes só para o admin com ViewAny:AgenteIa', function (bool $comPermissao): void {
    if (! $comPermissao) {
        semAPermissao('admin', 'ViewAny:AgenteIa');
    }

    noPainelDoShield('admin');

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'))
        ->get('/admin/agentes-ia')
        ->assertStatus($comPermissao ? 200 : 403);
})->with([
    'com a permissão' => [true],
    'sem a permissão' => [false],
]);
