<?php

use App\Filament\App\Resources\Projetos\Pages\ListProjetos;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\Exports\ProjetoExporter;
use App\Filament\Imports\ProjetoImporter;
use App\Models\Projeto;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * A fronteira de organização no import de CSV.
 *
 * O defeito que estes casos fecham vive no worker, e é por isso que quase todos chamam o
 * **importador direto** em vez de passar pela tela: `resolveRecord()` roda dentro do job,
 * onde `Filament::getTenant()` é `null` e o escopo global de `BelongsToTenant` vira
 * no-op. Um cenário que passasse pela tela com o tenant no contexto mediria o contexto,
 * não a correção — ficaria verde com o `ImportadorDoKit` inteiro removido.
 *
 * Por isso os cenários **limpam o contexto de tenant** antes de importar: é a reprodução
 * fiel do que o worker vê.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->acme   = tenant('Acme', 'acme');
    $this->globex = tenant('Globex', 'globex');
});

/**
 * Uma linha de CSV importada como o worker a importa: sem tenant no contexto.
 *
 * `$options` é o que a Action captura no request; passar `[]` reproduz a Action escrita
 * errado, e é o cenário do fail-closed.
 *
 * O operador default é `admin_app` da Acme: o importador consulta a policy do model por
 * linha, no contexto da organização das options.
 *
 * @param  array<string, mixed>  $linha
 * @param  array<string, mixed>  $options
 */
function importarLinha(array $linha, array $options, ?User $operador = null): void
{
    Filament::setTenant(null, isQuiet: true);

    $operador ??= usuarioComPapel('admin_app', Tenant::where('slug', 'acme')->firstOrFail(), 'operador@example.com');

    $import = Import::create([
        'importer'   => ProjetoImporter::class,
        'file_name'  => 'projetos.csv',
        'file_path'  => 'projetos.csv',
        'total_rows' => 1,
        // `imports.user_id` é NOT NULL com FK: a Action sempre associa quem clicou.
        'user_id'    => $operador->getKey(),
    ]);

    $importador = new ProjetoImporter($import, ['nome' => 'nome'], $options);

    $importador($linha);
}

/*
|--------------------------------------------------------------------------
| Isolamento no import — o núcleo da entrega
|--------------------------------------------------------------------------
*/

/**
 * O cenário que a feature existe para fechar.
 *
 * Sem o escopo, `resolveRecord()` acha o projeto da Globex pelo nome e o UPDATE cai nele:
 * sem 403, sem log, sem nada na tela. Com o escopo, o nome que colide é um nome livre
 * dentro da Acme e nasce um registro NOVO.
 */
it('não altera registro de outra organização quando a chave colide', function (): void {
    noPainelDa($this->globex);
    $daGlobex = Projeto::create(['nome' => 'Contrato 2026']);

    importarLinha(['nome' => 'Contrato 2026'], ['tenant_id' => $this->acme->getKey()]);

    expect($daGlobex->refresh()->tenant_id)->toBe($this->globex->getKey())
        ->and(Projeto::withoutGlobalScope('tenant')->where('nome', 'Contrato 2026')->count())->toBe(2)
        ->and(Projeto::withoutGlobalScope('tenant')->where('tenant_id', $this->acme->getKey())->count())->toBe(1);
});

/**
 * `tenant_id` nulo é o outro lado do mesmo defeito, e o mais fácil de não notar: o
 * registro nasce, a importação diz "sucesso", e a linha simplesmente não aparece em
 * organização nenhuma — invisível na tela de todo mundo.
 */
it('preenche a organização no registro criado pelo import', function (): void {
    importarLinha(['nome' => 'Novo pela planilha'], ['tenant_id' => $this->acme->getKey()]);

    $criado = Projeto::withoutGlobalScope('tenant')->firstOrFail();

    expect($criado->tenant_id)->toBe($this->acme->getKey());
});

/**
 * Fail-closed: Action escrita sem `->options(['tenant_id' => ...])` recusa a linha em vez
 * de escrever sem fronteira. É a diferença entre uma importação que falha visivelmente e
 * uma que grava em lugar nenhum.
 */
it('recusa a linha quando a organização não chega nas options', function (): void {
    expect(fn () => importarLinha(['nome' => 'Sem organização'], []))
        ->toThrow(RowImportFailedException::class);

    expect(Projeto::withoutGlobalScope('tenant')->count())->toBe(0);
});

/**
 * O `Importer` do Filament avisa: "runs without policy checks". `Import:Projeto` abre a
 * Action; criar ou alterar CADA linha exige a policy do model — senão quem só pode importar
 * edita por CSV o que a tela não deixa. Achado da auditoria Blueprint.
 */
it('recusa a linha quando o operador não pode criar nem alterar o registro', function (): void {
    $semPapel = usuario('sem-papel@example.com');
    $semPapel->tenants()->attach($this->acme);

    expect(fn () => importarLinha(['nome' => 'Pela planilha'], ['tenant_id' => $this->acme->getKey()], $semPapel))
        ->toThrow(RowImportFailedException::class);

    expect(Projeto::withoutGlobalScope('tenant')->count())->toBe(0);
});

/**
 * A resolução por chave também é escopada na LEITURA: o projeto da própria organização é
 * atualizado, não duplicado. Sem este caso, um escopo que recusasse tudo passaria os dois
 * cenários acima.
 */
it('atualiza o registro da própria organização em vez de duplicar', function (): void {
    noPainelDa($this->acme);
    $daAcme = Projeto::create(['nome' => 'Contrato 2026']);

    importarLinha(['nome' => 'Contrato 2026'], ['tenant_id' => $this->acme->getKey()]);

    expect(Projeto::withoutGlobalScope('tenant')->count())->toBe(1)
        ->and(Projeto::withoutGlobalScope('tenant')->firstOrFail()->getKey())->toBe($daAcme->getKey());
});

/*
|--------------------------------------------------------------------------
| Isolamento no export — herdado, e é preciso provar que é
|--------------------------------------------------------------------------
*/

/**
 * O export não tem código de escopo, e por isso mesmo precisa de caso: a query vem da
 * tabela da tela, e se algum dia ela deixar de vir, nada no `ExportadorDoKit` acusaria.
 */
it('a query de export da tela só alcança a própria organização', function (): void {
    noPainelDa($this->globex);
    Projeto::create(['nome' => 'Da Globex']);

    noPainelDa($this->acme);
    Projeto::create(['nome' => 'Da Acme']);

    $usuario = usuarioComPapel('admin_app', $this->acme);
    $usuario->tenants()->attach($this->acme);
    $this->actingAs($usuario);
    config(['kit.demo' => true]);

    $this->get(ProjetoResource::getUrl('index', tenant: $this->acme))->assertOk();

    $nomes = Livewire::test(ListProjetos::class)
        ->instance()
        ->getTableQueryForExport()
        ->pluck('nome');

    expect($nomes->all())->toBe(['Da Acme']);
});

/*
|--------------------------------------------------------------------------
| Permissão separada para importar e para exportar
|--------------------------------------------------------------------------
*/

it('esconde import e export de quem não tem a permissão', function (): void {
    noPainelDa($this->acme);

    $usuario = usuarioComPapel('panel_user', $this->acme);
    $usuario->tenants()->attach($this->acme);
    $this->actingAs($usuario);
    config(['kit.demo' => true]);

    $this->get(ProjetoResource::getUrl('index', tenant: $this->acme))->assertOk();

    Livewire::test(ListProjetos::class)
        ->assertActionHidden('import')
        ->assertActionHidden('export');
});

/**
 * As duas permissões são independentes — é a cláusula que o requisito separou de
 * propósito: "pode ter cenarios diferentes caso envolve quem pode exportar e quem pode
 * importar".
 */
it('libera export sem liberar import', function (): void {
    noPainelDa($this->acme);

    $usuario = usuarioComPapel('panel_user', $this->acme);
    $usuario->tenants()->attach($this->acme);
    $usuario->givePermissionTo('Export:Projeto');
    $this->actingAs($usuario);
    config(['kit.demo' => true]);

    $this->get(ProjetoResource::getUrl('index', tenant: $this->acme))->assertOk();

    Livewire::test(ListProjetos::class)
        ->assertActionVisible('export')
        ->assertActionHidden('import');
});

/*
|--------------------------------------------------------------------------
| Formula injection no arquivo exportado
|--------------------------------------------------------------------------
*/

/**
 * O CSV exportado é aberto no Excel, e célula começando em `=` é fórmula. O dado veio de
 * formulário de usuário: `=cmd|' /C calc'!A1` num campo de nome é um payload esperando
 * planilha.
 *
 * O `preventFormulaInjection()` do Filament nasce **desligado** por coluna. O
 * `ExportadorDoKit` o liga em todas — este caso é o que reprova se alguém "simplificar" o
 * wrapper de `getColumns()`.
 */
it('neutraliza fórmula em toda coluna do export', function (): void {
    $colunas = ProjetoExporter::getColumns();

    expect($colunas)->not->toBeEmpty();

    foreach ($colunas as $coluna) {
        expect($coluna)->toBeInstanceOf(ExportColumn::class)
            ->and($coluna->shouldPreventFormulaInjection())->toBeTrue();
    }
});

/*
|--------------------------------------------------------------------------
| Job assíncrono e notificação com botão de download
|--------------------------------------------------------------------------
*/

/**
 * O caminho completo do export pela tela: modal, job e arquivo.
 *
 * A fila é `sync` na suíte, então o `Bus::chain` do Filament roda inline e a linha de
 * `exports` chega completa na mesma requisição. Em produção é worker de verdade — o que
 * muda é o tempo, não o caminho.
 *
 * O oráculo é o ARQUIVO em disco, não a notificação: a notificação anexa a ação de
 * download apontando para a rota assinada, e essa rota devolve o arquivo. Sem arquivo, o
 * botão existe e não baixa nada — que é precisamente a falha que "assertNotified" sozinho
 * não vê.
 */
it('exporta pela tela, processa em job e deixa o arquivo pronto para download', function (): void {
    noPainelDa($this->acme);

    $usuario = usuarioComPapel('admin_app', $this->acme);
    $usuario->tenants()->attach($this->acme);
    $this->actingAs($usuario);
    config(['kit.demo' => true]);

    Projeto::create(['nome' => 'Contrato 2026']);

    $this->get(ProjetoResource::getUrl('index', tenant: $this->acme))->assertOk();

    Livewire::test(ListProjetos::class)
        ->callAction('export', [
            'columnMap' => [
                'nome' => ['isEnabled' => true, 'label' => 'Nome'],
            ],
        ])
        ->assertNotified();

    $export = Export::query()->latest('id')->firstOrFail();

    expect($export->completed_at)->not->toBeNull()
        ->and($export->successful_rows)->toBe(1)
        // O CSV sai em pedaços numerados dentro do diretório do export
        // (`ExportCsv:101`), e o downloader os concatena no stream. O oráculo é haver
        // arquivo no diretório — o nome de cada pedaço é detalhe do pacote.
        ->and($export->getFileDisk()->files($export->getFileDirectory()))->not->toBeEmpty();
});
