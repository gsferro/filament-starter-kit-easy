<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\ProvedorAntiRobo;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));
    noPainelBootado('admin');
});

function componentesConfiguracoesL1(Testable $componente, string $classe): Collection
{
    return collect($componente->instance()->form->getFlatComponents(withHidden: true))
        ->filter(fn (Component $item): bool => $item instanceof $classe)->values();
}

function campoConfiguracoesL1(Testable $componente, string $nome): Field
{
    $campo = componentesConfiguracoesL1($componente, Field::class)->first(fn (Field $campo): bool => $campo->getName() === $nome);
    expect($campo)->toBeInstanceOf(Field::class);

    return $campo;
}

function idDoCampoL1(Testable $componente, string $nome): string
{
    return 'id="'.campoConfiguracoesL1($componente, $nome)->getId().'"';
}

function htmlDaCargaL1(Testable $componente, string $aba): string
{
    $parciais = $componente->effects['partials'] ?? [];
    expect($parciais)->toHaveKey('schema.form.configuracoes.'.$aba);

    return $parciais['schema.form.configuracoes.'.$aba];
}

function secaoDoCampoL1(Testable $componente, string $nome): Section
{
    $pai = campoConfiguracoesL1($componente, $nome)->getContainer()->getParentComponent();
    while ($pai && ! ($pai instanceof Section) && ! ($pai instanceof Tab)) {
        $pai = $pai->getContainer()->getParentComponent();
    }
    expect($pai)->toBeInstanceOf(Section::class);

    return $pai;
}

function segredosConfiguracoesL1(): array
{
    return [
        'mail_password'                                                => 'senha-smtp-gravada',
        ProvedorSocial::Google->propriedadeDeSettings('client_secret') => 'segredo-google-gravado',
        'login_anti_robo_chave_secreta'                                => 'chave-antirobo-gravada',
    ];
}

function gravarSegredosL1(): void
{
    $settings = app(SettingsDoKit::class);
    foreach (segredosConfiguracoesL1() as $campo => $valor) {
        $settings->{$campo} = $valor;
    }
    $settings->save();
    app()->forgetInstance(SettingsDoKit::class);
}

it('[CT-01] mantém ordem, rótulos e ícone da aba Aplicação', function (): void {
    $abas = componentesConfiguracoesL1(Livewire::test(ConfiguracoesDoKit::class), Tab::class);

    expect($abas->map->getLabel()->all())->toBe(['Identidade', 'E-mail', 'Tabelas', 'Registro', 'Login', 'Aplicação']);
    expect($abas->last()->getIcon())->toBe('heroicon-o-squares-2x2');
})->group('kit');

it('[CT-03] envolve todos os campos, inclusive ocultos, em seção antes da aba', function (): void {
    gravarConfiguracao('mail_mailer', 'log');
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    $campos     = componentesConfiguracoesL1($componente, Field::class);
    $soltos     = [];
    foreach ($campos as $campo) {
        $pai = $campo->getContainer()->getParentComponent();
        while ($pai && ! ($pai instanceof Section) && ! ($pai instanceof Tab)) {
            $pai = $pai->getContainer()->getParentComponent();
        }
        if (! ($pai instanceof Section)) {
            $soltos[] = $campo->getName();
        }
    }

    expect($soltos)->toBe([]);
    foreach (['nome_da_aplicacao', 'mail_host', 'paginacao_padrao', 'registro_habilitado', 'login_rodape', 'hub_de_navegacao'] as $ancora) {
        expect($campos->map->getName()->all())->toContain($ancora);
    }
})->group('kit');

it('[CT-04] torna toda seção colapsável e com ícone, inclusive SMTP e provedores', function (): void {
    gravarConfiguracao('mail_mailer', 'log');
    gravarConfiguracao(ProvedorSocial::Google->propriedadeDeSettings('habilitado'), true);
    gravarConfiguracao(ProvedorSocial::Github->propriedadeDeSettings('habilitado'), false);
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    $secoes     = componentesConfiguracoesL1($componente, Section::class);

    expect($secoes->reject(fn (Section $secao): bool => $secao->isCollapsible() && filled($secao->getIcon()))->map->getHeading()->all())->toBe([]);
    foreach (['Cabeçalho dos painéis', 'Página única de login', 'Login social', 'Proteção anti-robô', 'Rodapé da tela de login', 'Dashboard dinâmico', 'Dock de atalhos'] as $titulo) {
        expect($secoes->map->getHeading()->all())->toContain($titulo);
    }
    foreach (ProvedorSocial::cases() as $provedor) {
        expect($secoes->all())->toContain(secaoDoCampoL1($componente, $provedor->propriedadeDeSettings('client_id')));
    }
    expect($secoes->all())->toContain(secaoDoCampoL1($componente, 'mail_host'));
})->group('kit');

it('[CT-05] persiste o colapso de toda seção com id explícito único', function (): void {
    gravarConfiguracao('mail_mailer', 'log');
    $secoes = componentesConfiguracoesL1(Livewire::test(ConfiguracoesDoKit::class), Section::class);
    expect($secoes)->not->toBeEmpty();
    expect($secoes->reject(fn (Section $secao): bool => filled($secao->getCustomId()) && $secao->shouldPersistCollapsed())->map->getHeading()->all())->toBe([]);
    expect($secoes->map->getCustomId()->unique()->count())->toBe($secoes->count());
})->group('kit');

it('[CT-06] dá chave própria e conteúdo adiado a cada aba', function (): void {
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    $grupos     = componentesConfiguracoesL1($componente, Tabs::class);
    expect($grupos)->toHaveCount(1);
    expect($grupos->first()->getKey())->not->toBeEmpty();
    $abas = componentesConfiguracoesL1($componente, Tab::class);
    expect($abas->map->getLabel()->all())->toBe(['Identidade', 'E-mail', 'Tabelas', 'Registro', 'Login', 'Aplicação']);
    expect($abas->map->getKey()->unique()->count())->toBe($abas->count());
    foreach ($abas as $aba) {
        expect($aba->getKey())->not->toBeEmpty();
        expect($aba->getChildSchemas(true))->not->toBeEmpty();
        foreach ($aba->getChildSchemas(true) as $schema) {
            expect($schema->isLoadingDeferred())->toBeTrue($aba->getLabel());
        }
    }
})->group('kit');

it('[CT-07] renderiza seis marcadores ocupados e nenhum campo inicialmente', function (): void {
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    expect($componente->get('loadedDeferredSchemas'))->toBe([]);
    $html = $componente->html();
    expect(substr_count($html, 'fi-sc-loading'))->toBe(6);
    expect(substr_count($html, 'aria-busy="true"'))->toBe(6);
    foreach (componentesConfiguracoesL1($componente, Field::class) as $campo) {
        $this->assertStringNotContainsString('id="'.$campo->getId().'"', $html);
    }
    foreach (['Identidade', 'E-mail', 'Tabelas', 'Registro', 'Login', 'Aplicação'] as $rotulo) {
        $componente->assertSee($rotulo);
    }
})->group('kit');

it('[CT-08] carrega somente os campos da aba pedida', function (string $aba, string $ancora): void {
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    expect($componente->get('loadedDeferredSchemas'))->toBe([]);

    carregarAba($componente, $aba);

    $html = htmlDaCargaL1($componente, $aba);
    expect($html)->toContain(idDoCampoL1($componente, $ancora));
    foreach (['nome_da_aplicacao', 'mail_from_address', 'paginacao_padrao', 'registro_habilitado', 'login_rodape', 'hub_de_navegacao'] as $outra) {
        if ($outra !== $ancora) {
            $this->assertStringNotContainsString(idDoCampoL1($componente, $outra), $html);
        }
    }
})->with([
    'Identidade' => ['identidade', 'nome_da_aplicacao'],
    'E-mail'     => ['email', 'mail_from_address'],
    'Tabelas'    => ['tabelas', 'paginacao_padrao'],
    'Registro'   => ['registro', 'registro_habilitado'],
    'Login'      => ['login', 'login_rodape'],
    'Aplicação'  => ['aplicacao', 'hub_de_navegacao'],
])->group('kit');

it('[CT-09] carrega a aba com erro de mínimo sem gravar a edição válida', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');
    gravarConfiguracao('paginacao_padrao', 25);
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    expect($componente->get('loadedDeferredSchemas'))->toBe([]);

    $componente->fillForm(['nome_da_aplicacao' => 'Depois', 'paginacao_padrao' => 0])->call('save')->assertHasFormErrors(['paginacao_padrao' => 'min']);

    expect($componente->html())->toContain(idDoCampoL1($componente, 'paginacao_padrao'));
    $componente->assertSee($componente->instance()->getErrorBag()->first('data.paginacao_padrao'));
    foreach (['nome_da_aplicacao', 'mail_from_address', 'registro_habilitado', 'login_rodape', 'hub_de_navegacao'] as $ancora) {
        expect($componente->html())->toContain(idDoCampoL1($componente, $ancora));
    }
    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
    expect(configuracaoGravada('paginacao_padrao'))->toBe(25);
})->group('kit');

it('[CT-10] grava em aba nunca carregada e preserva todas as demais configurações e segredos', function (): void {
    $disco        = Storage::disk('public');
    $logoAnterior = $disco->exists('logos/marca.png') ? $disco->get('logos/marca.png') : null;
    $disco->put('logos/marca.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9xkAAAAASUVORK5CYII='));
    try {
        gravarConfiguracao('versao_do_sistema', 'versao-gravada');
        $defaults = app(SettingsDoKit::class)->toArray();
        $valores  = [
            'nome_da_aplicacao'                                         => 'Projeto Gravado', 'logo' => 'logos/marca.png',
            'mail_from_name'                                            => 'Equipe Gravada', 'paginacao_padrao' => 50,
            'registro_habilitado'                                       => ! $defaults['registro_habilitado'],
            ProvedorSocial::Google->propriedadeDeSettings('habilitado') => true,
            ProvedorSocial::Google->propriedadeDeSettings('client_id')  => 'id-gravado',
            'login_anti_robo_habilitado'                                => true,
            'login_anti_robo_chave_do_site'                             => 'site-gravado',
            'hub_de_navegacao'                                          => ! $defaults['hub_de_navegacao'], 'dock_paineis' => ['admin'],
        ];
        foreach ($valores as $campo => $valor) {
            expect($valor)->not->toBe($defaults[$campo], $campo.' deve diferir do default');
            gravarConfiguracao($campo, $valor);
        }
        gravarSegredosL1();
        $antes      = app(SettingsDoKit::class)->toArray();
        $componente = Livewire::test(ConfiguracoesDoKit::class);
        expect($componente->get('loadedDeferredSchemas'))->toBe([]);

        $componente->fillForm(['login_rodape' => 'Recado novo'])->call('save')->assertHasNoFormErrors();

        app()->forgetInstance(SettingsDoKit::class);
        $esperado = array_replace($antes, ['login_rodape' => 'Recado novo']);
        expect(app(SettingsDoKit::class)->toArray())->toBe($esperado);
        foreach (segredosConfiguracoesL1() as $campo => $segredo) {
            expect(app(SettingsDoKit::class)->{$campo})->toBe($segredo);
        }
        expect($componente->get('loadedDeferredSchemas'))->toBe([]);
        expect($componente->html())->toContain('fi-sc-loading', 'form.configuracoes.login');
        $this->assertStringNotContainsString(idDoCampoL1($componente, 'login_rodape'), $componente->html());
    } finally {
        if ($logoAnterior === null) {
            $disco->delete('logos/marca.png');
        } else {
            $disco->put('logos/marca.png', $logoAnterior);
        }
    }
})->group('kit');

it('[CT-11] grava o servidor SMTP numa aba nunca carregada', function (): void {
    gravarConfiguracao('mail_mailer', 'smtp');
    gravarConfiguracao('mail_host', 'smtp.antigo.test');
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    expect($componente->get('loadedDeferredSchemas'))->toBe([]);

    $componente->fillForm(['mail_host' => 'smtp.novo.test'])->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada('mail_host'))->toBe('smtp.novo.test');
})->group('kit');

it('[CT-12] grava client_id da seção Google que nasce colapsada', function (): void {
    $campo = ProvedorSocial::Google->propriedadeDeSettings('client_id');
    gravarConfiguracao(ProvedorSocial::Google->propriedadeDeSettings('habilitado'), true);
    gravarConfiguracao($campo, 'id-antigo');
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    expect(secaoDoCampoL1($componente, $campo)->isCollapsed())->toBeTrue();

    $componente->fillForm([$campo => 'id-novo'])->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada($campo))->toBe('id-novo');
})->group('kit');

it('[CT-13] aceita somente chave de aba adiada válida no pedido de carga', function (mixed $argumento, bool $valido, bool $erroTipo): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    expect($componente->get('loadedDeferredSchemas'))->toBe([]);

    if ($erroTipo) {
        expect(fn () => $componente->call('loadDeferredSchema', $argumento))->toThrow(TypeError::class);
    } else {
        $componente->call('loadDeferredSchema', $argumento);
    }

    expect($componente->get('loadedDeferredSchemas'))->toBe($valido ? ['form.configuracoes.tabelas' => true] : []);
    if ($valido) {
        expect(htmlDaCargaL1($componente, 'tabelas'))->toContain(idDoCampoL1($componente, 'paginacao_padrao'));
    }
    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
})->with([
    'Tabelas válida' => ['form.configuracoes.tabelas', true, false],
    'inexistente'    => ['form.configuracoes.inexistente', false, false],
    'só o grupo'     => ['form.configuracoes', false, false],
    'lista'          => [['form.configuracoes.tabelas'], false, true],
])->group('kit');

it('[CT-14] recusa escrever nas abas carregadas sem marcar aba, gravar ou avisar', function (mixed $valor): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');
    $componente = Livewire::test(ConfiguracoesDoKit::class)->fillForm(['nome_da_aplicacao' => 'Editado']);
    expect($componente->get('loadedDeferredSchemas'))->toBe([]);

    expect(fn () => $componente->set('loadedDeferredSchemas', $valor))->toThrow(CannotUpdateLockedPropertyException::class);

    expect($componente->get('loadedDeferredSchemas'))->toBe([]);
    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
    Notification::assertNotNotified();
})->with([
    'Login'       => [['form.configuracoes.login' => true]],
    'inexistente' => [['form.configuracoes.inexistente' => true]],
    'texto'       => ['form.configuracoes.login'],
])->group('kit');

it('[CT-15] carregar uma aba mostra o campo sem entregar seu segredo', function (string $aba, string $campo, string $segredo): void {
    gravarConfiguracao('mail_mailer', 'smtp');
    gravarConfiguracao(ProvedorSocial::Google->propriedadeDeSettings('habilitado'), true);
    gravarConfiguracao('login_anti_robo_habilitado', true);
    gravarSegredosL1();
    $componente = Livewire::test(ConfiguracoesDoKit::class);

    carregarAba($componente, $aba);

    $html = htmlDaCargaL1($componente, $aba);
    expect($html)->toContain(idDoCampoL1($componente, $campo));
    $this->assertStringNotContainsString($segredo, $html);
    $this->assertStringNotContainsString($segredo, $componente->html());
})->with([
    'senha SMTP'        => ['email', 'mail_password', 'senha-smtp-gravada'],
    'segredo Google'    => ['login', ProvedorSocial::Google->propriedadeDeSettings('client_secret'), 'segredo-google-gravado'],
    'segredo anti-robô' => ['login', 'login_anti_robo_chave_secreta', 'chave-antirobo-gravada'],
])->group('kit');

it('[CT-16] preserva edição pendente ao carregar outra aba e depois salvar', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');
    $componente = Livewire::test(ConfiguracoesDoKit::class)->fillForm(['nome_da_aplicacao' => 'Editado']);
    carregarAba($componente, 'tabelas');

    $componente->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Editado');
    expect($componente->html())->toContain(idDoCampoL1($componente, 'paginacao_padrao'));
})->group('kit');

it('[CT-17] valida pontuação da seção colapsável anti-robô e traz Login sem gravar', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');
    gravarConfiguracao('login_anti_robo_habilitado', true);
    gravarConfiguracao('login_anti_robo_provedor', ProvedorAntiRobo::RecaptchaV3->value);
    $campo = 'login_anti_robo_pontuacao_minima';
    gravarConfiguracao($campo, 5);
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    expect(secaoDoCampoL1($componente, $campo)->getHeading())->toBe('Proteção anti-robô');
    expect(secaoDoCampoL1($componente, $campo)->isCollapsible())->toBeTrue();
    expect($componente->get('loadedDeferredSchemas'))->toBe([]);

    $componente->fillForm(['nome_da_aplicacao' => 'Depois'])->call('save')->assertHasFormErrors([$campo]);

    expect($componente->html())->toContain(idDoCampoL1($componente, $campo));
    $componente->assertSee($componente->instance()->getErrorBag()->first('data.'.$campo));
    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
    expect(configuracaoGravada($campo))->toBe(5);
})->group('kit');

it('[CT-18] muda a visibilidade da seção SMTP pelo estado sem salvar', function (string $gravado, string $escolhido, bool $visivel): void {
    gravarConfiguracao('mail_mailer', $gravado);
    $componente = Livewire::test(ConfiguracoesDoKit::class);

    $componente->fillForm(['mail_mailer' => $escolhido]);

    expect(secaoDoCampoL1($componente, 'mail_host')->isVisible())->toBe($visivel);
    expect(configuracaoGravada('mail_mailer'))->toBe($gravado);
})->with([
    'log para smtp' => ['log', 'smtp', true],
    'smtp para log' => ['smtp', 'log', false],
])->group('kit');

it('[CT-20] repetir carga mantém só Tabelas carregada e preserva a edição pendente', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');
    $componente = Livewire::test(ConfiguracoesDoKit::class);
    carregarAba($componente, 'tabelas')->fillForm(['paginacao_padrao' => 30]);

    carregarAba($componente, 'tabelas');

    expect($componente->get('loadedDeferredSchemas'))->toBe(['form.configuracoes.tabelas' => true]);
    $componente->assertSchemaStateSet(['paginacao_padrao' => 30]);
    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
})->group('kit');
