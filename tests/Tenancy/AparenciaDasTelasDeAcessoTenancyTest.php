<?php

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Pages\Auth\CadastroUnificado;
use App\Filament\Pages\Auth\PreviewDasTelasDeAcesso;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaLogin;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AparenciaDasTelasDeAcesso;
use App\Support\ContextoDePapeis;
use App\Support\TetoDeUpload;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Aparência das telas de acesso — suíte Tenancy (organizações). IDs de CT em
 * `wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/04-casos-de-teste.md`.
 *
 * A autorização de /admin sobre organizações é GLOBAL (`Update:Tenant`/`Create:Tenant`, sem escopo por
 * organização): a persona "só A" do 04 não existe no kit; ver as Ambiguidades do retorno.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    comLogoNaIdentidade();
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    Filament::setCurrentPanel('admin');
});

function editorDaOrganizacao(Tenant $organizacao): Testable
{
    Filament::setCurrentPanel('admin');

    return Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()]);
}

function criadorDeOrganizacao(string $slug = 'nova'): Testable
{
    Filament::setCurrentPanel('admin');

    return Livewire::test(CreateTenant::class)->fillForm(['nome' => ucfirst($slug), 'slug' => $slug, 'ativo' => true]);
}

function comAparenciaPropria(Tenant $organizacao, array $aparencia): Tenant
{
    // Pelo fluxo real da tela, sem atalho no banco: exceção ou erro de validação aqui é defeito (CX-04).
    editorDaOrganizacao($organizacao)
        ->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => $aparencia])
        ->call('save')
        ->assertHasNoFormErrors();

    return $organizacao->fresh();
}

function campoDoEditor(Testable $tela, string $campo): mixed
{
    return $tela->instance()->form->getComponent(fn ($c) => method_exists($c, 'getName') && $c->getName() === $campo, withHidden: true);
}

function organizacoesAB(bool $aAtiva = true): array
{
    $acme = Tenant::factory()->create(['slug' => 'acme', 'nome' => 'Acme', 'ativo' => $aAtiva, 'registro_habilitado' => true]);
    $bela = Tenant::factory()->create(['slug' => 'bela', 'nome' => 'Bela', 'registro_habilitado' => true]);

    return [$acme, $bela];
}

function loginDe(string $url): string
{
    fronteiraDeRequest();
    Filament::auth()->logout();
    test()->flushSession();

    return test()->get($url)->assertOk()->getContent();
}

/*
| R1 — editor
*/

it('[CT-01] o editor está disponível na criação e na edição de organização', function (string $tela): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));

    $editor = $tela === 'criação' ? criadorDeOrganizacao() : editorDaOrganizacao($acme);
    $editor->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => aparenciaDeTeste('A')]);

    foreach (['layout', 'lado_do_formulario', 'fundo', 'cor', 'frase', 'apoio', 'logo_no_painel', 'alternador_de_tema'] as $campo) {
        expect(campoDoEditor($editor, $campo))->not->toBeNull("campo {$campo} ausente");
    }

    $editor->assertSchemaStateSet(['aparencia_das_telas.layout' => 'painel', 'aparencia_das_telas.frase' => 'ORG A', 'aparencia_das_telas.cor' => '#654321']);
})->with(['criação', 'edição'])->group('tenancy');

it('[CT-02] a escolha salva aparece em nova leitura e preserva os campos alheios', function (string $tela): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));

    if ($tela === 'criação') {
        criadorDeOrganizacao('criada')->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => aparenciaDeTeste('A')])->call('create')->assertHasNoFormErrors();
        $alvo = Tenant::where('slug', 'criada')->firstOrFail();
        expect($alvo->nome)->toBe('Criada');
    } else {
        editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => aparenciaDeTeste('A')])->call('save')->assertHasNoFormErrors();
        $alvo = $acme->fresh();
        expect($alvo->nome)->toBe('Acme')->and($alvo->slug)->toBe('acme');
    }

    expect($alvo->aparencia_das_telas)->toEqual(aparenciaDeTeste('A'));
})->with(['criação', 'edição'])->group('tenancy');

it('[CT-03] os textos válidos alcançam os tetos literais', function (string $tela, string $campo, int $n): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));
    $texto = str_repeat('é', $n);
    $dados = ['usar_aparencia_padrao' => false, 'aparencia_das_telas' => [$campo => $texto] + aparenciaDeTeste('P0')];

    if ($tela === 'criação') {
        criadorDeOrganizacao('criada')->fillForm($dados)->call('create')->assertHasNoFormErrors();
        $alvo = Tenant::where('slug', 'criada')->firstOrFail();
    } else {
        editorDaOrganizacao($acme)->fillForm($dados)->call('save')->assertHasNoFormErrors();
        $alvo = $acme->fresh();
    }

    expect($alvo->aparencia_das_telas[$campo])->toBe($texto)->and(mb_strlen($alvo->aparencia_das_telas[$campo]))->toBe($n);
})->with([
    'criação destaque 80' => ['criação', 'frase', 80], 'criação apoio 160' => ['criação', 'apoio', 160],
    'edição destaque 80'  => ['edição', 'frase', 80], 'edição apoio 160' => ['edição', 'apoio', 160],
])->group('tenancy');

it('[CT-28] a ação de salvar da organização recusa o excesso e nada é gravado', function (string $tela, string $campo, int $n, bool $sucesso): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));
    $texto = str_repeat('a', $n);
    $dados = ['usar_aparencia_padrao' => false, 'aparencia_das_telas' => [$campo => $texto] + aparenciaDeTeste('P0')];
    $antes = Tenant::query()->count();

    $resultado = $tela === 'criação'
        ? criadorDeOrganizacao('criada')->fillForm($dados)->call('create')
        : editorDaOrganizacao($acme)->fillForm($dados)->call('save');

    if ($sucesso) {
        $resultado->assertHasNoFormErrors();
    } else {
        $resultado->assertHasFormErrors(["aparencia_das_telas.{$campo}"]);
        expect(Tenant::query()->count())->toBe($antes)->and($acme->fresh()->aparencia_das_telas)->toBeNull();
    }
})->with([
    'criação 81' => ['criação', 'frase', 81, false], 'criação 161' => ['criação', 'apoio', 161, false],
    'edição 81'  => ['edição', 'frase', 81, false], 'edição 161' => ['edição', 'apoio', 161, false],
    'edição 80'  => ['edição', 'frase', 80, true],
])->group('tenancy');

it('[CT-29] valor inválido impede o salvamento inteiro da organização', function (string $tela, string $campo, mixed $valor): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));
    $dados = ['nome' => 'Nome Alterado', 'usar_aparencia_padrao' => false, 'aparencia_das_telas' => [$campo => $valor] + aparenciaDeTeste('A')];
    $antes = Tenant::query()->count();

    $resultado = $tela === 'criação'
        ? criadorDeOrganizacao('criada')->fillForm($dados)->call('create')
        : editorDaOrganizacao($acme)->fillForm($dados)->call('save');

    $resultado->assertHasFormErrors(["aparencia_das_telas.{$campo}"]);
    expect(Tenant::query()->count())->toBe($antes)
        ->and($acme->fresh()->nome)->toBe('Acme')
        ->and($acme->fresh()->aparencia_das_telas)->toBeNull();
})->with([
    'criação cor' => ['criação', 'cor', '#12345G'], 'criação layout' => ['criação', 'layout', 'desconhecido'],
    'edição cor'  => ['edição', 'cor', '#1234567'], 'edição lado' => ['edição', 'lado_do_formulario', 'diagonal'],
])->group('tenancy');

it('[CT-30] a normalização precede a contagem na organização', function (): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));

    editorDaOrganizacao($acme)
        ->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => ['frase' => '  '.str_repeat('a', 80).'  ', 'apoio' => '   '] + aparenciaDeTeste('P0')])
        ->call('save')->assertHasNoFormErrors();

    expect($acme->fresh()->aparencia_das_telas['frase'])->toBe(str_repeat('a', 80))
        ->and($acme->fresh()->aparencia_das_telas['apoio'])->toBeNull();
})->group('tenancy');

// CT-35 (@obsoleto pelo Adendo 1, RQ-20): o editor ganhou o upload da imagem do destaque. O que o
// CT-35 protegia — nenhum arquivo arbitrário — passa ao CT-43.
it('[CT-43] o único upload da aparência da organização aceita só PNG, JPEG ou WebP no diretório das telas de acesso', function (string $tela): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));
    $editor = $tela === 'criação' ? criadorDeOrganizacao() : editorDaOrganizacao($acme);
    $editor->fillForm(['usar_aparencia_padrao' => false]);

    $uploads = collect($editor->instance()->form->getFlatComponents(withHidden: true))
        ->filter(fn ($c): bool => $c instanceof FileUpload && str_contains((string) $c->getStatePath(), 'aparencia_das_telas'))
        ->values();

    // Adendo 2 (P-25): um upload por tela da organização — login e cadastro; "esqueci a senha"
    // não existe na organização (P-31) —, todos restritos.
    expect($uploads)->toHaveCount(2)
        ->and($uploads->map(fn ($u): string => (string) $u->getStatePath())->all())->toBe([
            'data.aparencia_das_telas.imagem_do_destaque',
            'data.aparencia_das_telas.telas.cadastro.imagem_do_destaque',
        ]);

    foreach ($uploads as $upload) {
        expect($upload->getAcceptedFileTypes())->toBe(['image/png', 'image/jpeg', 'image/webp'])
            ->and($upload->getDiskName())->toBe('public')
            ->and($upload->getMaxSize())->toBe(TetoDeUpload::emKb())
            ->and($upload->getDirectory())->toBe('telas-de-acesso');
    }
})->with(['criação', 'edição'])->group('tenancy');

/*
| R2/R3/R15 — leitura da organização
*/

it('[CT-06] conteúdo hostil da organização sai como texto no login', function (string $campo, string $texto): void {
    [$acme] = organizacoesAB();
    // Formato `texto` (gravado antes do Adendo 2); o `markdown` tem o CT-57.
    $acme->update(['aparencia_das_telas' => [$campo => $texto, 'formato_dos_textos' => 'texto'] + aparenciaDeTeste('A')]);

    $html   = loginDe('/app/login?org=acme');
    $arvore = arvoreDoHtml($html);

    expect(textoDoPainelDeMarca($html, $campo))->toBe($texto)
        ->and($arvore->query('//*[@onerror or @onfocus]')->length)->toBe(0)
        ->and($arvore->query("//script[contains(., 'alert(1)')]")->length)->toBe(0)
        ->and($arvore->query('//*[@data-kit-aparencia="painel"]//b')->length)->toBe(0);
})->with([['frase', '<b>Marca</b>'], ['apoio', '<img src=x onerror=alert(1)>']])->group('tenancy');

it('[CT-33] o clássico da organização ignora os valores do painel', function (): void {
    [$acme] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => ['layout' => 'classico', 'frase' => 'SENTINELA', 'cor' => '#fedcba']]);

    $html = loginDe('/app/login?org=acme');

    expect($html)->not->toContain('SENTINELA')->not->toContain('kit-painel-de-marca')->not->toContain('#fedcba');
})->group('tenancy');

/*
| R4 — contexto
*/

it('[CT-07] a decisão considera todas as condições', function (string $caso, string $esperada): void {
    [$acme, $bela] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    Tenant::factory()->create(['slug' => 'inativa', 'ativo' => false, 'aparencia_das_telas' => aparenciaDeTeste('A')]);
    config(['kit.registro.habilitado' => true]);

    $url = match ($caso) {
        'login app'   => '/app/login?org=acme',
        'login único' => (function (): string {
            ligarLoginUnificado();

            return '/login?org=acme';
        })(),
        'cadastro app'   => '/app/register?org=acme',
        'cadastro único' => (function (): string {
            ligarLoginUnificado();

            return '/cadastro?org=acme';
        })(),
        'convite de A'    => '/app/register?token='.ofertaPara('novo@example.com', $acme)->enviar(),
        'convite sem org' => '/app/register?token='.ofertaPara('novo@example.com')->enviar(),
        'org ausente'     => '/app/login',
        'org inexistente' => '/app/login?org=nao-existe',
        'org inativa'     => '/app/login?org=inativa',
        'login admin'     => '/admin/login?org=acme',
        'login infra'     => '/infra/login?org=acme',
        'recuperar senha' => '/app/password-reset/request?org=acme',
    };

    afirmarAparenciaNoHtml(loginDe($url), aparenciaDeTeste($esperada));
})->with([
    'login app'      => ['login app', 'A'], 'login único' => ['login único', 'A'], 'cadastro app' => ['cadastro app', 'A'],
    'cadastro único' => ['cadastro único', 'A'], 'convite de A' => ['convite de A', 'A'], 'convite sem org' => ['convite sem org', 'P'],
    'org ausente'    => ['org ausente', 'P'], 'org inexistente' => ['org inexistente', 'P'], 'org inativa' => ['org inativa', 'P'],
    'login admin'    => ['login admin', 'P'], 'login infra' => ['login infra', 'P'], 'recuperar senha' => ['recuperar senha', 'P'],
])->group('tenancy');

it('[CT-08] erro de login conserva a organização da montagem', function (): void {
    [$acme] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    usuarioComPapel('panel_user', $acme, 'conta@example.com');
    $antes = [$acme->fresh()->aparencia_das_telas, configuracaoGravada('aparencia_das_telas')];

    $this->get('/app/login?org=acme')->assertOk();
    Filament::setCurrentPanel('app');
    $login = Livewire::withQueryParams(['org' => 'acme'])->test(TelaLogin::class);

    // As chamadas seguintes do Livewire::test não levam a query: o contexto vem do estado da montagem.
    $login->fillForm(['email' => 'conta@example.com', 'password' => 'errada'])->call('authenticate')->assertHasFormErrors();

    $this->assertGuest();
    expect($login->instance()->aparenciaDaTela())->toEqual(aparenciaDeTeste('A'))
        ->and([$acme->fresh()->aparencia_das_telas, configuracaoGravada('aparencia_das_telas')])->toEqual($antes);
})->group('tenancy');

it('[CT-09] aparência identificada não concede vínculo', function (): void {
    [$acme, $bela] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    $pessoa = usuarioComPapel('panel_user', $bela, 'so-bela@example.com');
    $pessoa->tenants()->attach($bela->id);

    $this->actingAs($pessoa);
    $this->get('/app/login?org=acme');
    $resposta = $this->get('/app/acme');

    expect($resposta->getStatusCode())->not->toBe(200)
        ->and($pessoa->tenants()->where('tenants.id', $acme->id)->exists())->toBeFalse();
})->group('tenancy');

it('[CT-25] erro de cadastro conserva a organização', function (string $entrada): void {
    [$acme] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    config(['kit.registro.habilitado' => true, 'kit.registro.aprovacao_manual' => false, 'kit.registro.verificar_email' => false]);
    $usuarios = User::query()->count();

    if ($entrada === 'convite') {
        $token    = ofertaPara('novo@example.com', $acme)->enviar();
        $consulta = ['token' => $token];
        $classe   = RegistroPorConvite::class;
        $this->get('/app/register?token='.$token)->assertOk();
    } elseif ($entrada === '/cadastro') {
        ligarLoginUnificado();
        $consulta = ['org' => 'acme'];
        $classe   = CadastroUnificado::class;
        $this->get('/cadastro?org=acme')->assertOk();
    } else {
        $consulta = ['org' => 'acme'];
        $classe   = RegistroPorConvite::class;
        $this->get('/app/register?org=acme')->assertOk();
    }

    Filament::auth()->logout();
    Filament::setCurrentPanel('app');
    $tela = Livewire::withQueryParams($consulta)->test($classe)
        ->fillForm(['name' => 'Fulano', 'email' => 'invalido', 'password' => 'segredo-bem-longo-123', 'passwordConfirmation' => 'segredo-bem-longo-123'])
        ->call('register')->assertHasFormErrors(['email']);

    $this->assertGuest();
    expect($tela->instance()->aparenciaDaTela())->toEqual(aparenciaDeTeste('A'))->and(User::query()->count())->toBe($usuarios);
})->with(['cadastro da organização', '/cadastro', 'convite'])->group('tenancy');

it('[CT-26] erro de desbloqueio conserva aparência e trava', function (): void {
    [$acme] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    $pessoa = usuarioComPapel('panel_user', $acme, 'bloq@example.com');
    $pessoa->tenants()->attach($acme->id);
    $vinculos = DB::table(pivotDePapeis())->count();

    $this->actingAs($pessoa);
    noPainelDa($acme);
    session(['lockscreen' => true, 'tenant_corrente' => $acme->getKey()]);

    $tela = Livewire::test(TelaBloqueio::class)->set('data.password', 'errada')->call('authenticate')->assertHasErrors('data.password');

    expect($tela->instance()->aparenciaDaTela())->toEqual(aparenciaDeTeste('A'))
        ->and(session('lockscreen'))->toBeTrue()
        ->and(DB::table(pivotDePapeis())->count())->toBe($vinculos);
})->group('tenancy');

/*
| R5 — herança, cópia e descarte
*/

it('[CT-10] escolher própria copia a padrão inteira', function (string $tela, string $origem): void {
    [$acme] = organizacoesAB();
    definirAparenciaGlobal(aparenciaDeTeste($origem));
    $this->actingAs(usuarioComPapel('master_global'));

    if ($tela === 'criação') {
        criadorDeOrganizacao('criada')->fillForm(['usar_aparencia_padrao' => false])->call('create')->assertHasNoFormErrors();
        $alvo = Tenant::where('slug', 'criada')->firstOrFail();
    } else {
        editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => false])->call('save')->assertHasNoFormErrors();
        $alvo = $acme->fresh();
    }

    expect($alvo->aparencia_das_telas)->toEqual(aparenciaDeTeste($origem));
})->with([['criação', 'P'], ['edição', 'P'], ['criação', 'P0'], ['edição', 'P0']])->group('tenancy');

it('[CT-11] alterar a padrão afeta somente quem ainda a usa', function (): void {
    [$acme, $bela] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));
    comAparenciaPropria($bela, aparenciaDeTeste('P'));

    // A padrão muda direto na config/settings: o salvamento real da tela de configurações é o CT-02 (Kit).
    definirAparenciaGlobal(aparenciaDeTeste('P2'));

    afirmarAparenciaNoHtml(loginDe('/app/login?org=acme'), aparenciaDeTeste('P2'));
    afirmarAparenciaNoHtml(loginDe('/app/login?org=bela'), aparenciaDeTeste('P'));
})->group('tenancy');

it('[CT-12] repetir salvamento próprio não recopia a padrão', function (): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));
    expect(comAparenciaPropria($acme, aparenciaDeTeste('A'))->aparencia_das_telas)->toEqual(aparenciaDeTeste('A'));

    // Sem tocar no toggle: ele já está desligado, e um `set` do mesmo valor não é a transição do usuário.
    editorDaOrganizacao($acme)->call('save')->assertHasNoFormErrors();

    expect($acme->fresh()->aparencia_das_telas)->toEqual(aparenciaDeTeste('A'))
        ->and(configuracaoGravada('aparencia_das_telas'))->toEqual(aparenciaDeTeste('P'));
})->group('tenancy');

it('[CT-13] escolher padrão restaura a herança e descarta a própria', function (string $modo): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));

    if ($modo === 'própria') {
        comAparenciaPropria($acme, aparenciaDeTeste('A'));
    }

    definirAparenciaGlobal(aparenciaDeTeste('P2'));
    editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => true])->call('save')->assertHasNoFormErrors();

    expect($acme->fresh()->aparencia_das_telas)->toBeNull();
    afirmarAparenciaNoHtml(loginDe('/app/login?org=acme'), aparenciaDeTeste('P2'));
})->with(['própria', 'padrão'])->group('tenancy');

it('[CT-32] sair novamente da padrão cria uma cópia nova', function (): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));
    comAparenciaPropria($acme, aparenciaDeTeste('A'));
    editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => true])->call('save');
    definirAparenciaGlobal(aparenciaDeTeste('P2'));

    editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => false])->call('save')->assertHasNoFormErrors();

    expect($acme->fresh()->aparencia_das_telas)->toEqual(aparenciaDeTeste('P2'));
})->group('tenancy');

it('[CT-34] organização inativa aceita as mesmas operações de aparência', function (string $modo, string $operacao): void {
    [$acme] = organizacoesAB(aAtiva: false);
    $this->actingAs(usuarioComPapel('master_global'));

    if ($modo === 'própria') {
        comAparenciaPropria($acme, aparenciaDeTeste('A'));
    }

    definirAparenciaGlobal(aparenciaDeTeste('P2'));

    $esperado = match ($operacao) {
        'própria sem editar' => (function () use ($acme, $modo): array {
            $editor = editorDaOrganizacao($acme);
            // Em modo própria o toggle já está desligado: só salva (um `set` igual não é transição).
            $modo === 'própria' ? $editor->call('save') : $editor->fillForm(['usar_aparencia_padrao' => false])->call('save');
            $editor->assertHasNoFormErrors();

            return $modo === 'própria' ? aparenciaDeTeste('A') : aparenciaDeTeste('P2');
        })(),
        'própria alterada' => (function () use ($acme): array {
            editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => aparenciaDeTeste('B')])->call('save')->assertHasNoFormErrors();

            return aparenciaDeTeste('B');
        })(),
        'escolher padrão' => (function () use ($acme): ?array {
            editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => true])->call('save')->assertHasNoFormErrors();

            return null;
        })(),
        'padrão global' => $modo === 'própria' ? aparenciaDeTeste('A') : null,
    };

    expect($acme->fresh()->aparencia_das_telas)->toEqual($esperado)->and($acme->fresh()->nome)->toBe('Acme');
    afirmarAparenciaNoHtml(loginDe('/app/login?org=acme'), aparenciaDeTeste('P2'));
})->with([
    ['padrão', 'própria sem editar'], ['própria', 'própria alterada'], ['própria', 'própria sem editar'],
    ['própria', 'escolher padrão'], ['padrão', 'escolher padrão'], ['padrão', 'padrão global'], ['própria', 'padrão global'],
])->group('tenancy');

/*
| R6/R7 — preview e autorização da organização
*/

it('[CT-14] o preview da organização apresenta valores ainda não salvos', function (string $tela): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));

    $editor = $tela === 'criação' ? criadorDeOrganizacao() : editorDaOrganizacao($acme);
    $editor->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => aparenciaDeTeste('A')]);
    $html = $this->get(urlDoPreviewDe(rascunhoDoEditor($editor)))->assertOk()->getContent();

    expect(textoDoPainelDeMarca($html, 'frase'))->toBe('ORG A')
        ->and($acme->fresh()->aparencia_das_telas)->toBeNull()
        ->and(configuracaoGravada('aparencia_das_telas'))->toEqual(aparenciaDeTeste('P'));
})->with(['criação', 'edição'])->group('tenancy');

it('[CT-17] rascunho de organização exige a permissão atual de quem pede', function (): void {
    [$acme, $bela] = organizacoesAB();
    $this->actingAs(usuarioComPapel('panel_user', $acme, 'so-app@example.com'));
    $id = rascunhoDe('tenant:'.$bela->getKey(), aparenciaDeTeste('A'));

    $resposta = $this->get(urlDoPreviewDe($id));

    expect($resposta->getStatusCode())->toBe(403)->and($resposta->getContent())->not->toContain('ORG A');
})->group('tenancy');

it('[CT-19] forçar salvar ou criar sem autorização não escreve o alvo', function (string $alvo): void {
    [$acme, $bela] = organizacoesAB();
    $this->actingAs(usuarioComPapel('panel_user', $acme, 'sem@example.com'));
    $total = Tenant::query()->count();
    Filament::setCurrentPanel('admin');

    try {
        $alvo === 'criação'
            ? Livewire::test(CreateTenant::class)->fillForm(['nome' => 'X', 'slug' => 'x', 'usar_aparencia_padrao' => false, 'aparencia_das_telas' => aparenciaDeTeste('A')])->call('create')
            : Livewire::test(EditTenant::class, ['record' => $bela->getRouteKey()])->fillForm(['usar_aparencia_padrao' => false, 'aparencia_das_telas' => aparenciaDeTeste('A')])->call('save');
    } catch (Throwable) {
        // 403 na montagem é a recusa esperada.
    }

    expect(Tenant::query()->count())->toBe($total)->and($bela->fresh()->aparencia_das_telas)->toBeNull();
})->with(['criação', 'organização B'])->group('tenancy');

it('[CT-20] hidratação hostil preserva dono e escopo', function (string $propriedade, mixed $valor): void {
    [$acme, $bela] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    $bela->update(['aparencia_das_telas' => aparenciaDeTeste('B')]);

    $this->get('/app/login?org=acme')->assertOk();
    Filament::setCurrentPanel('app');
    $login = Livewire::withQueryParams(['org' => 'acme'])->test(TelaLogin::class);

    expect(fn () => $login->set($propriedade, $valor))->toThrow(Exception::class);
    expect($login->instance()->aparenciaDaTela())->toEqual(aparenciaDeTeste('A'))
        ->and($bela->fresh()->aparencia_das_telas)->toEqual(aparenciaDeTeste('B'));
})->with([['orgDaAparencia', 'bela'], ['orgDaAparencia', [['x']]]])->group('tenancy');

/*
| R8 — credenciais mantêm o resultado
*/

it('[CT-22] credenciais mantêm seu resultado com a aparência da organização', function (string $senha): void {
    [$acme] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    $conta = usuarioComPapel('panel_user', $acme, 'conta@example.com');
    $conta->tenants()->attach($acme->id);
    $conta->forceFill(['password' => 'password'])->save();

    $this->get('/app/login?org=acme')->assertOk();
    Filament::setCurrentPanel('app');
    Livewire::withQueryParams(['org' => 'acme'])->test(TelaLogin::class)
        ->fillForm(['email' => 'conta@example.com', 'password' => $senha])->call('authenticate');

    $senha === 'password' ? $this->assertAuthenticatedAs($conta) : $this->assertGuest();
    expect($acme->fresh()->aparencia_das_telas)->toEqual(aparenciaDeTeste('A'));
})->with(['password', 'errada'])->group('tenancy');

/*
| Achados do step 9 (CT-36, CT-37, CT-39, CT-41)
*/

function organizacaoComLogoPropria(string $slug = 'acme'): Tenant
{
    Storage::disk('public')->put('org/logo-a.png', 'png');

    return Tenant::factory()->comIdentidadeVisual('#7c3aed', 'org/logo-a.png')->create(['slug' => $slug, 'nome' => 'Acme', 'registro_habilitado' => true]);
}

it('[CT-36] o bloqueio usa a arte da identidade, e não a logo, no fundo do painel', function (): void {
    Storage::disk('public')->put('arte-identidade.png', 'png');
    config()->set('kit.identidade.arte_do_login', 'arte-identidade.png');
    definirAparenciaGlobal(['fundo' => 'arte'] + aparenciaDeTeste('P'));
    $acme   = organizacaoComLogoPropria();
    $pessoa = usuarioComPapel('panel_user', $acme, 'bloq@example.com');
    $pessoa->tenants()->attach($acme->id);

    $this->actingAs($pessoa);
    noPainelDa($acme);
    session(['lockscreen' => true, 'tenant_corrente' => $acme->getKey()]);

    $tela   = Livewire::test(TelaBloqueio::class)->instance();
    $html   = view('filament.auth.painel-de-marca', ['config' => $tela->getAuthDesignerConfig(), 'aparencia' => $tela->aparenciaDaTela(), 'livewire' => $tela])->render();
    $arvore = arvoreDoHtml($html);

    $fundo = $arvore->query('//img[contains(@class,"kit-painel-de-marca__arte")]');
    $logo  = $arvore->query('//img[contains(@class,"kit-painel-de-marca__logo")]');

    expect($fundo->length)->toBe(1)
        ->and($fundo->item(0)->getAttribute('src'))->toContain('arte-identidade.png')->not->toContain('logo-a.png')
        ->and($logo->length)->toBe(1)
        ->and($logo->item(0)->getAttribute('src'))->toContain('logo-a.png');
})->group('tenancy');

it('[CT-37] chamada direta ao componente não revela cor nem logo que a tela não mostra', function (string $caso): void {
    $acme      = organizacaoComLogoPropria();
    $aparencia = match ($caso) {
        'clássico'       => ['layout' => 'classico'],
        'logo desligada' => ['logo_no_painel' => false] + aparenciaDeTeste('A'),
        'logo ligada'    => ['logo_no_painel' => true] + aparenciaDeTeste('A'),
    };
    $acme->update(['aparencia_das_telas' => $aparencia]);

    $this->get('/app/login?org=acme')->assertOk();
    Filament::setCurrentPanel('app');
    $login = Livewire::withQueryParams(['org' => 'acme'])->test(TelaLogin::class)->instance();

    $cor   = $login->corDoPainelDeMarca();
    $logos = $login->logosDoPainelDeMarca();

    expect(filled($cor))->toBe($caso !== 'clássico');

    if ($caso !== 'clássico') {
        expect($cor)->toBe('#654321');
    }

    if ($caso === 'logo ligada') {
        expect($logos['clara'])->toContain('logo-a.png');
    } else {
        expect(array_filter($logos))->toBe([]);
    }
})->with(['clássico', 'logo desligada', 'logo ligada'])->group('tenancy');

it('[CT-39] permissão revogada com o preview aberto deixa de mostrar o escopo no próximo update', function (): void {
    [$acme] = organizacoesAB();
    // Papel do painel admin, no contexto global: o preview exige entrar no `/admin` (P-17).
    $papel = Role::create(['name' => 'editora-de-organizacoes', 'guard_name' => 'web', 'painel' => 'admin']);
    $papel->givePermissionTo('Update:Tenant');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $editora = usuario('editora@example.com');
    ContextoDePapeis::em(Tenant::CONTEXTO_GLOBAL, $editora, fn () => $editora->assignRole($papel));

    $this->actingAs($editora);
    $id = rascunhoDe('tenant:'.$acme->getKey(), ['frase' => 'SEGREDO A'] + aparenciaDeTeste('A'));
    Filament::setCurrentPanel('admin');
    $preview = Livewire::withQueryParams(['rascunho' => $id, 'tela' => 'login'])->test(PreviewDasTelasDeAcesso::class);

    $papel->revokePermissionTo('Update:Tenant');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $editora->unsetRelation('roles')->unsetRelation('permissions');

    try {
        $preview->call('$refresh');
        $html      = $preview->html();
        $aparencia = $preview->instance()->aparenciaDaTela();
    } catch (HttpException) {
        // A recusa por exceção também não entrega o conteúdo.
        $html      = '';
        $aparencia = null;
    }

    expect($html)->not->toContain('SEGREDO A')->and($aparencia)->toBeNull();
})->group('tenancy');

it('[CT-41] desligar o toggle sem recarregar começa de cópia nova da padrão', function (): void {
    [$acme] = organizacoesAB();
    $acme->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    $this->actingAs(usuarioComPapel('master_global'));

    $editor = editorDaOrganizacao($acme);
    $editor->fillForm(['usar_aparencia_padrao' => true])->call('save')->assertHasNoFormErrors();
    expect($acme->fresh()->aparencia_das_telas)->toBeNull();

    $editor->fillForm(['usar_aparencia_padrao' => false])->call('save')->assertHasNoFormErrors();

    expect($acme->fresh()->aparencia_das_telas)->toEqual(aparenciaDeTeste('P'));
})->group('tenancy');

it('[CT-42] organização com o clássico próprio não herda o painel da padrão', function (): void {
    [$acme] = organizacoesAB();
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    $acme->update(['aparencia_das_telas' => ['layout' => 'classico']]);

    // Controle positivo: sem organização, a padrão (painel) aparece.
    $semOrganizacao = $this->get('/app/login')->assertOk()->getContent();
    $daOrganizacao  = $this->get('/app/login?org=acme')->assertOk()->getContent();

    expect($semOrganizacao)->toContain('data-kit-aparencia="painel"');
    $this->assertStringNotContainsString('data-kit-aparencia="painel"', $daOrganizacao, 'o clássico próprio virou "usa a padrão"');
})->group('tenancy');

/*
| Adendo 1 — campos novos na aparência da organização (ADV-A4, ADV-A11 da revisão do adendo)
*/

it('[CT-53] a aparência própria da organização leva largura, destaque e rede ao login dela', function (): void {
    $acme = organizacaoComLogoPropria();
    comLogoNaIdentidade();
    Storage::disk('public')->put('org/logo-a.png', 'png');
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    $acme->update(['aparencia_das_telas' => [
        'tamanho_do_painel' => 70, 'destaque' => 'logo', 'ilustracao' => 'rede',
    ] + aparenciaDeTeste('A')]);

    $daOrganizacao = loginDe('/app/login?org=acme');
    $arvore        = arvoreDoHtml($daOrganizacao);

    expect($daOrganizacao)->toContain('--media-size: 70%')
        ->and($arvore->query('//svg[@data-kit-ilustracao="rede"]')->length)->toBe(1)
        // A logo do destaque é a da organização, não a da instalação (P-20).
        ->and($arvore->query('//*[@data-kit-destaque="logo"]//img')->item(0)?->getAttribute('src'))->toContain('logo-a.png');

    $semOrganizacao = loginDe('/app/login');

    expect($semOrganizacao)->toContain('--media-size: 60%')
        ->and(arvoreDoHtml($semOrganizacao)->query('//svg[@data-kit-ilustracao="rede"]')->length)->toBe(0);
})->group('tenancy');

/*
| Adendo 2 — uma aparência por tela também na organização (P-25: primeiro o objeto, depois a tela)
*/

it('[CT-73] a organização com aparência própria por tela: login e cadastro dela, cada um com a sua', function (): void {
    [$acme] = organizacoesAB();
    definirAparenciaGlobal(['frase' => 'PADRAO DO LOGIN'] + aparenciaDeTeste('P'));
    $acme->update(['aparencia_das_telas' => [
        'frase' => 'ACME LOGIN', 'lado_do_formulario' => 'direita',
        'telas' => ['cadastro' => ['frase' => 'ACME CADASTRO', 'lado_do_formulario' => 'esquerda'] + aparenciaDeTeste('A')],
    ] + aparenciaDeTeste('A')]);
    config(['kit.registro.habilitado' => true, 'kit.registro.aprovacao_manual' => false, 'kit.registro.verificar_email' => false]);

    $login    = loginDe('/app/login?org=acme');
    $cadastro = loginDe('/app/register?org=acme');
    $semOrg   = loginDe('/app/login');

    expect(textoDoPainelDeMarca($login, 'frase'))->toBe('ACME LOGIN')
        ->and($login)->toContain('media-left')
        ->and(textoDoPainelDeMarca($cadastro, 'frase'))->toBe('ACME CADASTRO')
        ->and($cadastro)->toContain('media-right')
        // Sem organização, nada da Acme: nem a tela própria dela se mistura à padrão.
        ->and(textoDoPainelDeMarca($semOrg, 'frase'))->toBe('PADRAO DO LOGIN');
})->group('tenancy');

it('[CT-79] a organização não tem a tela "esqueci a senha": nem no editor, nem no preview', function (string $tela): void {
    [$acme] = organizacoesAB();
    definirAparenciaGlobal([...aparenciaDeTeste('P'), 'frase' => 'PADRAO DA RECUPERACAO']);
    $this->actingAs(usuarioComPapel('master_global'));

    $editor = $tela === 'criação' ? criadorDeOrganizacao() : editorDaOrganizacao($acme);
    $editor->fillForm(['usar_aparencia_padrao' => false]);

    $campos = collect($editor->instance()->form->getFlatComponents(withHidden: true))
        ->map(fn ($c): string => (string) $c->getStatePath())
        ->filter(fn (string $caminho): bool => str_contains($caminho, 'telas.recuperacao'));

    expect($campos->all())->toBe([]);

    // O preview da organização, na tela de recuperação, mostra a padrão — como a tela real (P-06).
    $id = rascunhoDe('tenant:'.$acme->getKey(), [
        ...aparenciaDeTeste('A'),
        'frase' => 'RASCUNHO DA ACME',
        'telas' => ['recuperacao' => ['frase' => 'RECUPERACAO DA ACME'] + aparenciaDeTeste('A')],
    ]);
    Filament::setCurrentPanel('admin');

    $html = $this->get(urlDoPreviewDe($id, 'recuperar-senha'))->assertOk()->getContent();

    expect(textoDoPainelDeMarca($html, 'frase'))->toBe('PADRAO DA RECUPERACAO')
        ->and($html)->not->toContain('RECUPERACAO DA ACME')
        ->and(textoDoPainelDeMarca($this->get(urlDoPreviewDe($id, 'login'))->getContent(), 'frase'))->toBe('RASCUNHO DA ACME');
})->with(['criação', 'edição'])->group('tenancy');

/**
 * CX-C1 (revisão do Codex, ciclo 2): na recuperação, o preview da organização mostra a padrão
 * SALVA (P-31) — inclusive a imagem do destaque. A imagem recém-enviada no editor da organização
 * vale para o login dela, nunca para a recuperação, que não é dela.
 */
it('[CT-97] na recuperação, o preview da organização usa a imagem da padrão, não a enviada no editor dela', function (): void {
    [$acme] = organizacoesAB();
    Storage::fake('public');
    Storage::disk('public')->put('telas-de-acesso/global.png', 'png');
    definirAparenciaGlobal([...aparenciaDeTeste('P'), 'destaque' => 'imagem', 'imagem_do_destaque' => 'telas-de-acesso/global.png']);
    $this->actingAs(usuarioComPapel('master_global'));

    $id         = rascunhoDe('tenant:'.$acme->getKey(), [...aparenciaDeTeste('A'), 'destaque' => 'imagem']);
    $chave      = 'aparencia-preview:'.auth()->id().':'.$id;
    $temporaria = '/livewire/preview-file/IMAGEM-DA-ACME';
    Cache::put($chave, [...Cache::get($chave), 'imagem_temporaria' => ['login' => $temporaria, 'cadastro' => $temporaria, 'recuperacao' => $temporaria]], now()->addHour());
    Filament::setCurrentPanel('admin');

    $imagemDe = fn (string $tela): ?string => arvoreDoHtml($this->get(urlDoPreviewDe($id, $tela))->assertOk()->getContent())
        ->query('//img[@data-kit-destaque="imagem"]')->item(0)?->getAttribute('src');

    expect($imagemDe('recuperar-senha'))->toContain('telas-de-acesso/global.png')
        // Controle: no login da organização, a imagem enviada e ainda não salva aparece (P-08).
        ->and($imagemDe('login'))->toBe($temporaria);
})->group('tenancy');

/*
| Revisão adversarial dos Adendos 2 e 3 — organização (ADV-C6, C7, C14, C15, C20)
*/

it('[CT-93] primeiro o objeto, depois a tela: sem mistura entre a aparência da organização e a padrão', function (string $caso, string $esperada): void {
    [$acme] = organizacoesAB();
    config(['kit.registro.habilitado' => true, 'kit.registro.aprovacao_manual' => false, 'kit.registro.verificar_email' => false]);
    definirAparenciaGlobal([
        'frase' => 'PADRAO LOGIN', 'lado_do_formulario' => 'direita',
        'telas' => ['cadastro' => ['frase' => 'PADRAO CADASTRO', 'lado_do_formulario' => 'esquerda'] + aparenciaDeTeste('P')],
    ] + aparenciaDeTeste('P'));

    if ($caso === 'própria sem cadastro') {
        $acme->update(['aparencia_das_telas' => ['frase' => 'ACME LOGIN'] + aparenciaDeTeste('A')]);
    }

    expect(textoDoPainelDeMarca(loginDe('/app/register?org=acme'), 'frase'))->toBe($esperada);
})->with([
    // Própria sem tela de cadastro: o cadastro segue o login DA ORGANIZAÇÃO, não o cadastro da padrão.
    'própria sem cadastro' => ['própria sem cadastro', 'ACME LOGIN'],
    // Usando a padrão: vale a padrão inteira, com a tela de cadastro dela.
    'usa a padrão'         => ['usa a padrão', 'PADRAO CADASTRO'],
])->group('tenancy');

it('[CT-94] a recuperação de senha real ignora a "esqueci a senha" gravada na organização', function (): void {
    [$acme] = organizacoesAB();
    definirAparenciaGlobal(['frase' => 'PADRAO'] + aparenciaDeTeste('P'));
    $acme->update(['aparencia_das_telas' => [
        'frase' => 'ACME LOGIN',
        'telas' => ['recuperacao' => ['frase' => 'ACME REC'] + aparenciaDeTeste('A')],
    ] + aparenciaDeTeste('A')]);

    $html = loginDe('/app/password-reset/request?org=acme');

    expect(textoDoPainelDeMarca($html, 'frase'))->toBe('PADRAO')
        ->and($html)->not->toContain('ACME REC');
})->group('tenancy');

it('[CT-95] o editor da organização mantém a seção "Cadastro" com "Igual ao login"', function (): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));

    $editor = editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => false]);

    $caminhos = collect($editor->instance()->form->getFlatComponents(withHidden: true))->map(fn ($c): string => (string) $c->getStatePath());

    expect($caminhos->contains('data.aparencia_das_telas.telas.cadastro.igual_ao_login'))->toBeTrue()
        ->and($caminhos->contains('data.aparencia_das_telas.telas.cadastro.frase'))->toBeTrue();
})->group('tenancy');

it('[CT-96] "cor primária" na aparência da organização usa a primária da organização', function (): void {
    $acme = organizacaoComLogoPropria();
    $acme->update(['aparencia_das_telas' => ['fundo' => 'cor', 'cor' => null] + aparenciaDeTeste('A')]);

    $estilo = arvoreDoHtml(loginDe('/app/login?org=acme'))->query('//*[@data-kit-aparencia="painel"]')->item(0)->getAttribute('style');

    expect($estilo)->toContain('--kit-painel-fundo: #7c3aed');
})->group('tenancy');

/**
 * QA-37 do gate (ciclo 3), P-35: a transição entre as telas é da instalação. A recuperação de senha
 * usa sempre a padrão (P-31), e a transição exige as duas pontas — então o login da organização
 * segue a opção da instalação, não uma própria, e o formulário da organização não oferece o
 * interruptor.
 */
it('[CT-104] na organização, a transição segue a opção da instalação', function (bool $daInstalacao, bool $daOrganizacao): void {
    [$acme] = organizacoesAB();
    definirAparenciaGlobal([...aparenciaDeTeste('P'), 'transicao_entre_telas' => $daInstalacao]);
    $acme->update(['aparencia_das_telas' => [...aparenciaDeTeste('A'), 'transicao_entre_telas' => $daOrganizacao]]);

    $login = arvoreDoHtml(loginDe('/app/login?org=acme'));

    expect(textoDoPainelDeMarca(loginDe('/app/login?org=acme'), 'frase'))->toBe('ORG A')
        ->and($login->query('//head/style[@data-kit-transicao-entre-telas]')->length)->toBe($daInstalacao ? 1 : 0)
        ->and($login->query('//*[@data-kit-aparencia="painel"][@data-kit-transicao]')->length)->toBe($daInstalacao ? 1 : 0);
})->with([
    'instalação ligada, organização desligada' => [true, false],
    'instalação desligada, organização ligada' => [false, true],
])->group('tenancy');

/**
 * Teste manual da v0.53.1: com organizações, o dois fatores do `/app` é `app/{tenant}/...`. A lista
 * de destinos da transição (CT-107) não pode pedir essa rota sem o parâmetro — o login no painel de
 * marca respondia 500. Rota com parâmetro fica fora da lista.
 */
it('[CT-108] com organizações, as telas com transição abrem e levam a lista de destinos', function (string $caminho): void {
    organizacoesAB();
    definirAparenciaGlobal([...aparenciaDeTeste('P'), 'transicao_entre_telas' => true]);

    $html     = loginDe($caminho);
    $destinos = AparenciaDasTelasDeAcesso::caminhosDaTransicao();

    expect(arvoreDoHtml($html)->query('//head/script[@data-kit-transicao-entre-telas-destinos]')->length)->toBe(1)
        ->and($destinos)->toContain('/admin/login')
        ->and($destinos)->toContain('/app/login')
        ->and(collect($destinos)->filter(fn (string $c): bool => str_contains($c, '{'))->all())->toBe([]);
})->with(['/admin/login', '/app/login', '/app/login?org=acme', '/app/password-reset/request'])->group('tenancy');

it('[CT-105] o formulário da organização não oferece a transição entre as telas', function (): void {
    [$acme] = organizacoesAB();
    $this->actingAs(usuarioComPapel('master_global'));

    $editor   = editorDaOrganizacao($acme)->fillForm(['usar_aparencia_padrao' => false]);
    $caminhos = collect($editor->instance()->form->getFlatComponents(withHidden: true))->map(fn ($c): string => (string) $c->getStatePath());

    expect($caminhos->filter(fn (string $c): bool => str_ends_with($c, 'transicao_entre_telas'))->all())->toBe([])
        ->and($caminhos->contains('data.aparencia_das_telas.alternador_de_tema'))->toBeTrue();
})->group('tenancy');
