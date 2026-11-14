<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Pages\Auth\PreviewDasTelasDeAcesso;
use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaDoisFatores;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Pages\Auth\TelaLoginUnificada;
use App\Filament\Pages\Auth\TelaRecuperarSenha;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

/**
 * Aparência das telas de acesso — suíte Kit (tenancy desligada). IDs de CT em
 * `wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/04-casos-de-teste.md`.
 * CT-21 e CT-27 são execução pela sessão e CT-B* são de navegador: não existem aqui.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    comLogoNaIdentidade();
    Filament::setCurrentPanel('admin');
});

/** Quem opera as configurações: o master (controle) ou um papel só com as permissões dadas. */
function pessoaCom(array $permissoes, string $email = 'pessoa@example.com'): User
{
    $papel = Role::create(['name' => 'papel-'.md5($email.implode(',', $permissoes)), 'guard_name' => 'web', 'painel' => 'admin']);
    $papel->givePermissionTo($permissoes);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $pessoa = usuario($email);
    $pessoa->assignRole($papel);

    return $pessoa;
}

/** A tela de configurações com a aba das telas de acesso carregada. */
function telaDeAparencia(): Testable
{
    $tela = Livewire::test(ConfiguracoesDoKit::class);

    return carregarAba($tela, 'telas-de-acesso')->refresh();
}

/** Um texto de $n pontos de código, misturando ASCII, acento e emoji de um ponto de código. */
function textoDePontos(int $n): string
{
    $partes = ['a', 'é', '😀'];
    $texto  = '';

    for ($i = 0; $i < $n; $i++) {
        $texto .= $partes[$i % 3];
    }

    return $texto;
}

/** Fotografia das configurações sem a aparência (depois de um salvamento neutro que as normaliza). */
function fotoDasConfiguracoes(): array
{
    app()->forgetInstance(SettingsDoKit::class);
    $foto = app(SettingsDoKit::class)->toArray();
    unset($foto['aparencia_das_telas']);

    return $foto;
}

/** A configuração que a tela de login entrega ao pacote, no painel dado. */
function configDoPacoteNoLogin(string $painel = 'admin'): mixed
{
    Filament::setCurrentPanel($painel);

    return Livewire::test(TelaLogin::class)->instance()->getAuthDesignerConfig();
}

/*
|--------------------------------------------------------------------------
| R1 — edição, recusa e normalização
|--------------------------------------------------------------------------
*/

it('[CT-01] o editor está disponível no escopo autorizado: configurações', function (): void {
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = telaDeAparencia();

    foreach (['layout', 'lado_do_formulario', 'fundo', 'cor', 'frase', 'apoio', 'logo_no_painel', 'alternador_de_tema'] as $campo) {
        expect(chaveDoCampo($tela, $campo))->toContain($campo);
    }

    $tela->assertSchemaStateSet([
        'aparencia_das_telas.layout'             => 'painel',
        'aparencia_das_telas.lado_do_formulario' => 'esquerda',
        'aparencia_das_telas.fundo'              => 'cor',
        'aparencia_das_telas.cor'                => '#123456',
        'aparencia_das_telas.frase'              => 'PADRAO',
        'aparencia_das_telas.apoio'              => 'APOIO P',
        'aparencia_das_telas.logo_no_painel'     => true,
        'aparencia_das_telas.alternador_de_tema' => true,
    ]);
})->group('kit');

it('[CT-02] a escolha salva aparece em nova leitura e preserva os campos alheios', function (string $qual): void {
    $this->actingAs(usuarioDoKit('master_global'));
    Livewire::test(ConfiguracoesDoKit::class)->call('save')->assertHasNoFormErrors();
    $antes = fotoDasConfiguracoes();

    $tela = telaDeAparencia();
    $tela->fillForm(['aparencia_das_telas' => aparenciaDeTeste($qual)])->call('save')->assertHasNoFormErrors();

    // O clássico é gravado como `null` (o default de toda instalação).
    expect(configuracaoGravada('aparencia_das_telas'))->toEqual($qual === 'classico' ? null : aparenciaDeTeste($qual))
        ->and(fotoDasConfiguracoes())->toEqual($antes);
})->with(['painel P' => ['P'], 'clássico' => ['classico']])->group('kit');

it('[CT-03] os textos válidos alcançam os tetos literais sem truncamento', function (string $campo, int $n): void {
    $this->actingAs(usuarioDoKit('master_global'));
    $texto = textoDePontos($n);

    telaDeAparencia()
        ->fillForm(['aparencia_das_telas' => [$campo => $texto] + aparenciaDeTeste('P0')])
        ->call('save')
        ->assertHasNoFormErrors();

    $gravado = configuracaoGravada('aparencia_das_telas')[$campo];

    expect($gravado)->toBe($texto)->and(mb_strlen($gravado))->toBe($n);
})->with([
    'destaque 79'  => ['frase', 79],
    'destaque 80'  => ['frase', 80],
    'apoio 159'    => ['apoio', 159],
    'apoio 160'    => ['apoio', 160],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — valores gravados inválidos têm fallback seguro
|--------------------------------------------------------------------------
*/

it('[CT-04] layout gravado inválido volta ao clássico anterior sem erro', function (mixed $valor): void {
    // O GET real boota o painel (e registra a configuração do pacote) antes do componente.
    $this->get('/admin/login')->assertOk();
    $anterior = configDoPacoteNoLogin();

    definirAparenciaGlobal(['layout' => $valor] + aparenciaDeTeste('P'));
    $html = $this->get('/admin/login')->assertOk()->getContent();

    expect(configDoPacoteNoLogin())->toEqual($anterior);
    afirmarAparenciaNoHtml($html, null);
    expect($html)->toContain('fi-auth-layout')->toContain('type="password"');
})->with(['desconhecido' => 'desconhecido', 'array' => [['x' => 1]]])->group('kit');

it('[CT-05] o fallback é local e usa o padrão literal do campo', function (string $campo, mixed $valor, mixed $padrao): void {
    $sentinelas = aparenciaDeTeste('P') + [];
    $gravado    = [$campo => $valor] + $sentinelas;
    definirAparenciaGlobal($gravado);

    $html = $this->get('/admin/login')->assertOk()->getContent();

    $esperado = array_merge($sentinelas, [$campo => $padrao]);

    // O padrão da cor é a primária: a que a instalação resolve (aqui nenhuma hex configurada).
    if ($campo === 'cor') {
        $esperado['cor'] = null;
    }

    afirmarAparenciaNoHtml($html, $esperado);

    if ($campo === 'cor') {
        // Nenhuma declaração CSS nem atributo originado do valor inválido.
        expect($html)->not->toContain('--kit-painel-fundo: #123456')
            ->not->toContain('display:none')
            ->not->toContain('onload=alert');
    }
})->with([
    'lado enum'          => ['lado_do_formulario', 'diagonal', 'direita'],
    'lado tipo'          => ['lado_do_formulario', [['x']], 'direita'],
    'fundo enum'         => ['fundo', 'vídeo', 'cor'],
    'fundo tipo'         => ['fundo', [['x']], 'cor'],
    'cor caractere'      => ['cor', '#12345G', null],
    'cor curta'          => ['cor', '#12345', null],
    'cor sufixo num'     => ['cor', '#1234567', null],
    'cor sufixo css'     => ['cor', '#123456;}body{display:none}', null],
    'cor sufixo attr'    => ['cor', '#123456" onload=alert(1)', null],
    'cor quebra final'   => ['cor', "#123456\n", null],
    'cor tipo'           => ['cor', [['x']], null],
    'destaque excesso'   => ['frase', str_repeat('a', 81), null],
    'destaque tipo'      => ['frase', [['x']], null],
    'apoio excesso'      => ['apoio', str_repeat('a', 161), null],
    'apoio tipo'         => ['apoio', [['x']], null],
    'logo não booleano'  => ['logo_no_painel', 'talvez', true],
    'logo tipo'          => ['logo_no_painel', [['x']], true],
    'alternador valor'   => ['alternador_de_tema', 'talvez', true],
    'alternador tipo'    => ['alternador_de_tema', [['x']], true],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — textos não viram conteúdo ativo
|--------------------------------------------------------------------------
*/

it('[CT-06] conteúdo hostil sai como texto, no login e no preview', function (string $campo, string $texto, string $saida): void {
    $aparencia         = [$campo => $texto] + aparenciaDeTeste('P');
    $esperadoNaPosicao = $campo === 'frase' ? 'frase' : 'apoio';

    if ($saida === 'login') {
        definirAparenciaGlobal($aparencia);
        $html = $this->get('/admin/login')->assertOk()->getContent();
    } elseif ($saida === 'recuperar senha') {
        definirAparenciaGlobal($aparencia);
        $html = $this->get('/admin/password-reset/request')->assertOk()->getContent();
    } else {
        $this->actingAs(usuarioDoKit('master_global'));
        $id   = rascunhoDe('global', $aparencia);
        $html = $this->get(urlDoPreviewDe($id))->assertOk()->getContent();
    }

    $arvore = arvoreDoHtml($html);

    expect(textoDoPainelDeMarca($html, $esperadoNaPosicao))->toBe($texto)
        ->and($arvore->query("//*[contains(@class,'kit-painel-de-marca__{$esperadoNaPosicao}')]/*")->length)->toBe(0)
        ->and($arvore->query('//*[@onerror or @onfocus]')->length)->toBe(0)
        ->and($arvore->query('//*[@data-kit-aparencia="painel"]//*[@autofocus]')->length)->toBe(0)
        ->and($arvore->query("//script[contains(., 'alert(1)')]")->length)->toBe(0)
        ->and($arvore->query("//style[contains(., 'display:none')]")->length)->toBe(0)
        ->and($arvore->query('//*[@data-kit-aparencia="painel"]//b | //*[@data-kit-aparencia="painel"]//img[@onerror]')->length)->toBe(0);
})->with([
    'html destaque login'      => ['frase', '<b>Marca</b>', 'login'],
    'evento apoio login'       => ['apoio', '<img src=x onerror=alert(1)>', 'login'],
    'script destaque preview'  => ['frase', '<script>alert(1)</script>', 'preview'],
    'css apoio preview'        => ['apoio', '<style>body{display:none}</style>', 'preview'],
    'js destaque login'        => ['frase', "';alert(1);//", 'login'],
    'atributo apoio preview'   => ['apoio', '" autofocus onfocus="alert(1)', 'preview'],
    'html destaque recuperar'  => ['frase', '<b>Marca</b>', 'recuperar senha'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — contexto visual (partição sem tenancy)
|--------------------------------------------------------------------------
*/

it('[CT-07] com a tenancy desligada o org do pedido e as telas excluídas usam a padrão', function (string $url): void {
    $organizacao = Tenant::factory()->create(['slug' => 'acme']);
    $organizacao->update(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    definirAparenciaGlobal(aparenciaDeTeste('P'));

    $html = $this->get($url)->assertOk()->getContent();

    afirmarAparenciaNoHtml($html, aparenciaDeTeste('P'));
    expect($html)->toContain('fi-auth-form-container');
})->with([
    'login com org'          => ['/app/login?org=acme'],
    'login admin'            => ['/admin/login?org=acme'],
    'login infra'            => ['/infra/login?org=acme'],
    'recuperar senha'        => ['/admin/password-reset/request?org=acme'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — preview representa o rascunho e é inerte
|--------------------------------------------------------------------------
*/

it('[CT-14] o preview apresenta valores ainda não salvos', function (string $tela): void {
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    $this->actingAs(usuarioDoKit('master_global'));

    $editor = telaDeAparencia()->fillForm(['aparencia_das_telas' => aparenciaDeTeste('A')]);
    $id     = rascunhoDoEditor($editor);

    $html = $this->get(urlDoPreviewDe($id, $tela))->assertOk()->getContent();

    expect(textoDoPainelDeMarca($html, 'frase'))->toBe('ORG A')
        ->and(textoDoPainelDeMarca($html, 'apoio'))->toBe('APOIO A')
        ->and($html)->toContain(match ($tela) {
            'login'           => 'Faça login',
            'recuperar-senha' => 'Esqueceu sua senha?',
            'cadastro'        => 'Cadastre-se',
        })
        ->and(configuracaoGravada('aparencia_das_telas'))->toEqual(aparenciaDeTeste('P'));
})->with(['login', 'recuperar-senha', 'cadastro'])->group('kit');

it('[CT-15] tela desconhecida mostra o login com o rascunho e nada é gravado', function (string $tela): void {
    $this->actingAs(usuarioDoKit('master_global'));
    $id    = rascunhoDe('global', aparenciaDeTeste('A'));
    $antes = configuracaoGravada('aparencia_das_telas');

    $html = $this->get(urlDoPreviewDe($id, $tela))->assertOk()->getContent();

    expect($html)->toContain('Faça login')
        ->and(textoDoPainelDeMarca($html, 'frase'))->toBe('ORG A')
        ->and(configuracaoGravada('aparencia_das_telas'))->toEqual($antes);
})->with(['desconhecida' => 'desconhecida', 'caminho' => '../../admin'])->group('kit');

it('[CT-16] ações demonstrativas não produzem efeitos reais', function (string $acao, string $tela): void {
    Notification::fake();
    Mail::fake();
    $this->actingAs($admin = usuarioDoKit('master_global'));
    $destinataria = usuario('destinataria@example.com');
    DB::table('password_reset_tokens')->delete();
    $id = rascunhoDe('global', aparenciaDeTeste('A'));

    Socialite::shouldReceive('driver')->never();

    $usuariosAntes = User::query()->count();
    $vinculosAntes = DB::table(pivotDePapeis())->count();
    $tokensAntes   = DB::table('password_reset_tokens')->count();
    $global        = configuracaoGravada('aparencia_das_telas');

    $this->get(urlDoPreviewDe($id, $tela))->assertOk();

    // Tentativa forjada à fronteira demonstrativa, repetida: a página não tem a ação real.
    $pagina = Livewire::withQueryParams(['rascunho' => $id, 'tela' => $tela])->test(PreviewDasTelasDeAcesso::class);

    for ($i = 0; $i < 2; $i++) {
        match ($acao) {
            'autenticar' => expect(fn () => $pagina->set('data.email', $destinataria->email)->set('data.password', 'password')->call('authenticate'))->toThrow(MethodNotFoundException::class),
            'recuperar'  => expect(fn () => $pagina->set('data.email', $destinataria->email)->call('request'))->toThrow(MethodNotFoundException::class),
            'cadastrar'  => expect(fn () => $pagina->set('data.email', 'novo@example.com')->call('register'))->toThrow(MethodNotFoundException::class),
            'social'     => expect(fn () => $pagina->call('redirectToProvider', 'google'))->toThrow(MethodNotFoundException::class),
        };
    }

    $this->assertAuthenticatedAs($admin);
    expect(User::query()->count())->toBe($usuariosAntes)
        ->and(DB::table(pivotDePapeis())->count())->toBe($vinculosAntes)
        ->and(DB::table('password_reset_tokens')->count())->toBe($tokensAntes)
        ->and(configuracaoGravada('aparencia_das_telas'))->toEqual($global);
    Notification::assertNothingSent();
    Mail::assertNothingSent();
})->with([
    'autenticar' => ['autenticar', 'login'],
    'recuperar'  => ['recuperar', 'recuperar-senha'],
    'cadastrar'  => ['cadastrar', 'cadastro'],
    'social'     => ['social', 'login'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — autorização, dono e escopo
|--------------------------------------------------------------------------
*/

it('[CT-17] a rota verifica a autorização atual do escopo', function (string $quem, string $escopo, ?int $status): void {
    $acme = Tenant::factory()->create(['slug' => 'acme']);
    $bela = Tenant::factory()->create(['slug' => 'bela']);

    $permissoes = [
        'padrao'    => ['View:ConfiguracoesDoKit'],
        'editar'    => ['Update:Tenant'],
        'criar'     => ['Create:Tenant'],
        'tres'      => ['View:ConfiguracoesDoKit', 'Update:Tenant', 'Create:Tenant'],
        'nenhuma'   => [],
        'visitante' => [],
    ][$quem];

    $alvo = match ($escopo) {
        'global' => 'global',
        'A'      => 'tenant:'.$acme->getKey(),
        'B'      => 'tenant:'.$bela->getKey(),
        'novo'   => 'tenant:novo',
    };

    $pessoa = pessoaCom($permissoes);
    $this->actingAs($pessoa);
    $id    = rascunhoDe($alvo, aparenciaDeTeste('A'));
    $antes = configuracaoGravada('aparencia_das_telas');

    if ($quem === 'visitante') {
        auth()->logout();
        $this->flushSession();
    }

    $resposta = $this->get(urlDoPreviewDe($id));

    if ($status === 200) {
        $resposta->assertOk();
        expect(textoDoPainelDeMarca($resposta->getContent(), 'frase'))->toBe('ORG A');
    } else {
        expect($resposta->getStatusCode())->not->toBe(200)
            ->and($resposta->getContent())->not->toContain('ORG A');

        if ($status !== null) {
            $resposta->assertStatus($status);
        }
    }

    expect(configuracaoGravada('aparencia_das_telas'))->toEqual($antes);
})->with([
    'visitante padrão'                => ['visitante', 'global', null],
    'sem permissão padrão'            => ['nenhuma', 'global', 403],
    'editar A só pode A'              => ['editar', 'A', 200],
    'criar só pode criação'           => ['criar', 'novo', 200],
    'padrão pode padrão'              => ['padrao', 'global', 200],
    'criar não pode padrão'           => ['criar', 'global', 403],
    'padrão não pode criação'         => ['padrao', 'novo', 403],
    'editar não pode padrão'          => ['editar', 'global', 403],
    'editar não pode criação'         => ['editar', 'novo', 403],
    'três: padrão'                    => ['tres', 'global', 200],
    'três: A'                         => ['tres', 'A', 200],
    'três: criação'                   => ['tres', 'novo', 200],
])->group('kit');

it('[CT-17] permissão revogada depois do rascunho responde 403 sem o conteúdo', function (): void {
    $papel  = Role::create(['name' => 'papel-revogavel', 'guard_name' => 'web', 'painel' => 'admin']);
    $papel->givePermissionTo('View:ConfiguracoesDoKit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $pessoa = usuario('revogada@example.com');
    $pessoa->assignRole($papel);

    $this->actingAs($pessoa);
    $id = rascunhoDe('global', aparenciaDeTeste('A'));
    $this->get(urlDoPreviewDe($id))->assertOk();

    $papel->revokePermissionTo('View:ConfiguracoesDoKit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $pessoa->unsetRelation('roles')->unsetRelation('permissions');

    $resposta = $this->get(urlDoPreviewDe($id))->assertForbidden();
    expect($resposta->getContent())->not->toContain('ORG A');
})->group('kit');

it('[CT-18] rascunho inválido ou alheio recebe a resposta determinada', function (string $entrada, int $status): void {
    $permissoes = ['View:ConfiguracoesDoKit'];
    $ana        = pessoaCom($permissoes, 'ana@example.com');
    $bia        = pessoaCom($permissoes, 'bia@example.com');

    $this->actingAs($bia);
    $idDeBia = rascunhoDe('global', ['frase' => 'TEXTO DA BIA'] + aparenciaDeTeste('B'));

    $this->flushSession();
    $this->actingAs($ana);
    $idDeAna = rascunhoDe('global', ['frase' => 'TEXTO DA ANA'] + aparenciaDeTeste('A'));

    $url = match ($entrada) {
        'alheio'         => urlDoPreviewDe($idDeBia),
        'dono forjado'   => urlDoPreviewDe($idDeBia, 'login', ['dono' => $bia->getKey(), 'owner_id' => $bia->getKey(), 'user' => $bia->getKey()]),
        'comum'          => (function () use ($idDeBia): string {
            // O mesmo id nos dois namespaces: cada pessoa tem o seu.
            rascunhoDe('global', ['frase' => 'TEXTO DA ANA'] + aparenciaDeTeste('A'), $idDeBia);

            return urlDoPreviewDe($idDeBia);
        })(),
        'ausente'        => urlDoPreviewDe(null),
        'nulo'           => route('kit.preview-das-telas-de-acesso', ['rascunho' => '', 'tela' => 'login']),
        'inexistente'    => urlDoPreviewDe('00000000-0000-0000-0000-000000000000'),
        'expirado'       => (function () use ($idDeAna): string {
            $this->travel(1)->days();

            return urlDoPreviewDe($idDeAna);
        })(),
    };

    $resposta = $this->get($url);
    $resposta->assertStatus($status);
    expect($resposta->getContent())->not->toContain('TEXTO DA BIA');

    if ($status === 200) {
        expect(textoDoPainelDeMarca($resposta->getContent(), 'frase'))->toBe('TEXTO DA ANA');
    }
})->with([
    'alheio'       => ['alheio', 404],
    'dono forjado' => ['dono forjado', 404],
    'comum'        => ['comum', 200],
    'ausente'      => ['ausente', 404],
    'nulo'         => ['nulo', 404],
    'inexistente'  => ['inexistente', 404],
    'expirado'     => ['expirado', 404],
])->group('kit');

it('[CT-19] forçar a ação sem autorização não escreve o alvo', function (string $acao): void {
    $papel = Role::create(['name' => 'papel-forcado', 'guard_name' => 'web', 'painel' => 'admin']);
    $papel->givePermissionTo('View:ConfiguracoesDoKit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $pessoa = usuario('forcada@example.com');
    $pessoa->assignRole($papel);

    definirAparenciaGlobal(aparenciaDeTeste('P'));
    $this->actingAs($pessoa);
    $tela = telaDeAparencia();
    $tela->call('save')->assertHasNoFormErrors();
    $tela->fillForm(['aparencia_das_telas' => aparenciaDeTeste('A'), 'login_rodape' => 'Recado novo']);
    $antes        = configuracaoGravada('aparencia_das_telas');
    $rodape       = configuracaoGravada('login_rodape');
    $idDoRascunho = $tela->instance()->getId();

    // A autorização é revogada depois da montagem: a ação forçada vê o estado atual.
    $papel->revokePermissionTo('View:ConfiguracoesDoKit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $pessoa->unsetRelation('roles')->unsetRelation('permissions');

    try {
        $acao === 'salvar' ? $tela->call('save') : $tela->call('$refresh');
    } catch (Throwable) {
        // A recusa pode vir como exceção; o que importa é o que ficou gravado.
    }

    expect(configuracaoGravada('aparencia_das_telas'))->toEqual($antes)
        ->and(configuracaoGravada('login_rodape'))->toEqual($rodape);

    $this->get(urlDoPreviewDe($idDoRascunho))->assertForbidden();
})->with(['salvar configuração' => 'salvar', 'atualizar preview' => 'preview'])->group('kit');

it('[CT-20] hidratação hostil preserva dono e escopo', function (string $superficie, string $propriedade, mixed $valor): void {
    $this->actingAs(usuarioDoKit('master_global'));
    $bela = Tenant::factory()->create(['slug' => 'bela']);
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    $id   = rascunhoDe('global', aparenciaDeTeste('A'));
    $foto = ['global' => configuracaoGravada('aparencia_das_telas'), 'tenant' => $bela->fresh()->aparencia_das_telas, 'users' => User::query()->count(), 'papeis' => DB::table(pivotDePapeis())->count()];

    if ($superficie === 'preview') {
        $pagina = Livewire::withQueryParams(['rascunho' => $id, 'tela' => 'login'])->test(PreviewDasTelasDeAcesso::class);

        try {
            $pagina->set($propriedade, $valor);
        } catch (Throwable) {
            // Propriedade trancada ou de tipo errado: a recusa é o resultado esperado.
        }

        expect($pagina->instance()->rascunho)->toBe($id)->and($pagina->instance()->tela)->toBe('login');
    } else {
        $tela = telaDeAparencia();

        try {
            $tela->set($propriedade, $valor)->call('save');
        } catch (Throwable) {
            // Tipo errado derruba o salvamento: nada pode ter sido gravado.
        }
    }

    expect(configuracaoGravada('aparencia_das_telas'))->toEqual($foto['global'])
        ->and($bela->fresh()->aparencia_das_telas)->toEqual($foto['tenant'])
        ->and(User::query()->count())->toBe($foto['users'])
        ->and(DB::table(pivotDePapeis())->count())->toBe($foto['papeis']);
})->with([
    'preview rascunho alheio'     => ['preview', 'rascunho', 'outro-id'],
    'preview rascunho array'      => ['preview', 'rascunho', [['x']]],
    'preview tela admin'          => ['preview', 'tela', 'admin'],
    'preview tela array'          => ['preview', 'tela', [['x']]],
    'preview data acao forjada'   => ['preview', 'data.acao', 'authenticate'],
    'preview data dono'           => ['preview', 'data.owner_id', 1],
    'preview data string'         => ['preview', 'data', 'texto'],
    'config chave de dono'        => ['config', 'data.owner_id', 99],
    'config chave de escopo'      => ['config', 'data.escopo', 'tenant:1'],
    'config string na estrutura'  => ['config', 'data.aparencia_das_telas', 'texto'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — credenciais mantêm o resultado nos dois layouts
|--------------------------------------------------------------------------
*/

it('[CT-22] credenciais mantêm seu resultado nos dois layouts', function (string $layout, string $senha, string $entrada): void {
    definirAparenciaGlobal(aparenciaDeTeste($layout));
    $conta = usuarioDoKit('master_global', 'conta@example.com');
    $conta->forceFill(['password' => 'password'])->save();

    if ($entrada === 'único') {
        ligarLoginUnificado();
        Filament::setCurrentPanel('app');
        $this->get('/login')->assertOk();
        $componente = Livewire::test(TelaLoginUnificada::class);
    } else {
        $this->get('/admin/login')->assertOk();
        $componente = Livewire::test(TelaLogin::class);
    }

    $componente->fillForm(['email' => 'conta@example.com', 'password' => $senha])->call('authenticate');

    if ($senha === 'password') {
        $this->assertAuthenticatedAs($conta);
        $this->get('/admin')->assertOk();
    } else {
        $this->assertGuest();
        $componente->assertHasFormErrors();
    }

    expect(configuracaoGravada('aparencia_das_telas'))->toEqual($layout === 'classico' ? ['layout' => 'classico'] : aparenciaDeTeste($layout));
})->with([
    'sucesso clássico' => ['classico', 'password', 'painel'],
    'sucesso painel'   => ['P', 'password', 'painel'],
    'erro clássico'    => ['classico', 'errada', 'painel'],
    'erro painel'      => ['P', 'errada', 'painel'],
    'unificado'        => ['P', 'password', 'único'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R1 — fronteira de escrita
|--------------------------------------------------------------------------
*/

it('[CT-28] a ação de salvar recusa o excesso mesmo sem montar o editor', function (string $campo, int $n, bool $sucesso): void {
    $this->actingAs(usuarioDoKit('master_global'));
    Livewire::test(ConfiguracoesDoKit::class)->call('save');
    $antes = fotoDasConfiguracoes();
    $texto = textoDePontos($n);

    // A aba adiada NÃO é carregada: a ação é chamada com o estado forjado, sem montar a UI do editor.
    $componente = Livewire::test(ConfiguracoesDoKit::class)
        ->set('data.aparencia_das_telas', [$campo => $texto] + aparenciaDeTeste('P0'))
        ->call('save');

    if ($sucesso) {
        expect(configuracaoGravada('aparencia_das_telas')[$campo])->toBe($texto);
    } else {
        $componente->assertHasErrors(["data.aparencia_das_telas.{$campo}"]);
        expect(configuracaoGravada('aparencia_das_telas'))->toBeNull();
    }

    expect(fotoDasConfiguracoes())->toEqual($antes);
})->with([
    'destaque 80'  => ['frase', 80, true],
    'destaque 81'  => ['frase', 81, false],
    'apoio 160'    => ['apoio', 160, true],
    'apoio 161'    => ['apoio', 161, false],
])->group('kit');

it('[CT-29] valor inválido impede o salvamento inteiro', function (string $campo, mixed $valor): void {
    $this->actingAs(usuarioDoKit('master_global'));
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    Livewire::test(ConfiguracoesDoKit::class)->call('save');
    $antes  = fotoDasConfiguracoes();
    $global = configuracaoGravada('aparencia_das_telas');

    $tela = telaDeAparencia();
    $tela->fillForm(['aparencia_das_telas' => [$campo => $valor] + aparenciaDeTeste('A'), 'login_rodape' => 'Recado alterado'])
        ->call('save')
        ->assertHasFormErrors(["aparencia_das_telas.{$campo}"]);

    expect(configuracaoGravada('aparencia_das_telas'))->toEqual($global)
        ->and(fotoDasConfiguracoes())->toEqual($antes)
        ->and(configuracaoGravada('login_rodape'))->not->toBe('Recado alterado');
})->with([
    'destaque 81'      => ['frase', str_repeat('a', 81)],
    'apoio 161'        => ['apoio', str_repeat('a', 161)],
    'layout enum'      => ['layout', 'desconhecido'],
    'lado enum'        => ['lado_do_formulario', 'diagonal'],
    'fundo enum'       => ['fundo', 'vídeo'],
    'cor caractere'    => ['cor', '#12345G'],
    'cor sufixo num'   => ['cor', '#1234567'],
    'cor sufixo css'   => ['cor', '#123456;}body{display:none}'],
    'cor sufixo attr'  => ['cor', '#123456" onload=alert(1)'],
    'cor quebra final' => ['cor', "#123456\n"],
    'lado array'       => ['lado_do_formulario', [['x']]],
    'logo fora'        => ['logo_no_painel', 'talvez'],
])->group('kit');

it('[CT-30] a normalização precede a contagem em pontos de código', function (string $campo, string $entrada, ?string $gravado): void {
    $this->actingAs(usuarioDoKit('master_global'));
    $familia = '👩‍👩‍👧‍👦';

    $entrada = strtr($entrada, [
        '{80a}'      => str_repeat('a', 80),
        '{160a}'     => str_repeat('a', 160),
        '{39a}'      => str_repeat('a', 39),
        '{40b}'      => str_repeat('b', 40),
        '{79a}'      => str_repeat('a', 79),
        '{80b}'      => str_repeat('b', 80),
        '{80emojis}' => str_repeat('😀', 80),
        '{11f}'      => str_repeat($familia, 11),
        '{23f}'      => str_repeat($familia, 23),
    ]);
    $esperado = $gravado === null ? null : strtr($gravado, ['{80a}' => str_repeat('a', 80), '{160a}' => str_repeat('a', 160), '{39a}' => str_repeat('a', 39), '{40b}' => str_repeat('b', 40), '{79a}' => str_repeat('a', 79), '{80b}' => str_repeat('b', 80), '{80emojis}' => str_repeat('😀', 80)]);

    $resultado = telaDeAparencia()
        ->fillForm(['aparencia_das_telas' => [$campo => $entrada] + aparenciaDeTeste('P0')])
        ->call('save');

    if ($esperado === 'RECUSA') {
        $resultado->assertHasFormErrors(["aparencia_das_telas.{$campo}"]);
        expect(configuracaoGravada('aparencia_das_telas'))->toBeNull();

        return;
    }

    $resultado->assertHasNoFormErrors();
    expect(configuracaoGravada('aparencia_das_telas')[$campo])->toBe($esperado);

    if ($esperado === null) {
        definirAparenciaGlobal(configuracaoGravada('aparencia_das_telas'));
        // A tela de login é de visitante: autenticado, o Filament redireciona ao painel.
        auth()->logout();
        $html = $this->get('/admin/login')->assertOk()->getContent();
        expect(textoDoPainelDeMarca($html, $campo))->toBeNull();
    }
})->with([
    'trim destaque'     => ['frase', '  {80a}  ', '{80a}'],
    'colapso destaque'  => ['frase', '{39a}   {40b}', '{39a} {40b}'],
    'trim apoio'        => ['apoio', '  {160a}  ', '{160a}'],
    'colapso apoio'     => ['apoio', '{79a}   {80b}', '{79a} {80b}'],
    'vazio destaque'    => ['frase', '', null],
    'espaços apoio'     => ['apoio', '   ', null],
    'emojis'            => ['frase', '{80emojis}', '{80emojis}'],
    'famílias destaque' => ['frase', '{11f}abcd', 'RECUSA'],
    'famílias apoio'    => ['apoio', '{23f}', 'RECUSA'],
])->group('kit');

it('[CT-31] campos omitidos do painel usam os padrões determinados', function (): void {
    config()->set('kit.cor_primaria_hex', '#654321');
    definirAparenciaGlobal(['layout' => 'painel']);

    $html = $this->get('/admin/login')->assertOk()->getContent();

    afirmarAparenciaNoHtml($html, [
        'layout' => 'painel', 'lado_do_formulario' => 'direita', 'fundo' => 'cor', 'cor' => '#654321',
        'frase'  => null, 'apoio' => null, 'logo_no_painel' => true, 'alternador_de_tema' => true,
    ]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R15 — o clássico entrega a configuração anterior exata
|--------------------------------------------------------------------------
*/

it('[CT-33] o clássico ignora os valores exclusivos do painel', function (string $tela): void {
    $sentinela = ['layout' => 'classico', 'lado_do_formulario' => 'esquerda', 'cor' => '#fedcba', 'frase' => 'SENTINELA-FRASE', 'apoio' => 'SENTINELA-APOIO'];
    $usuario   = usuarioDoKit('master_global', 'classica@example.com');

    $abrir = function () use ($tela, $usuario): array {
        Filament::setCurrentPanel('admin');
        $this->flushSession();

        return match ($tela) {
            'login'            => (function (): array {
                $pagina = Livewire::test(TelaLogin::class);

                return [$pagina->instance()->getAuthDesignerConfig(), $this->get('/admin/login')->getContent()];
            })(),
            'recuperar senha'  => (function (): array {
                $pagina = Livewire::test(TelaRecuperarSenha::class);

                return [$pagina->instance()->getAuthDesignerConfig(), $this->get('/admin/password-reset/request')->getContent()];
            })(),
            'redefinir senha'  => (function (): array {
                $url = URL::signedRoute('filament.admin.auth.password-reset.reset', ['email' => 'a@example.com', 'token' => 't']);

                return [null, $this->get($url)->getContent()];
            })(),
            'dois fatores'     => (function () use ($usuario): array {
                $this->actingAs($usuario);
                $pagina = Livewire::test(TelaDoisFatores::class);

                return [$pagina->instance()->getAuthDesignerConfig(), $this->get('/admin/two-factor-authentication')->getContent()];
            })(),
            'bloqueio'         => (function () use ($usuario): array {
                $this->actingAs($usuario);
                session(['lockscreen' => true]);
                $pagina = Livewire::test(TelaBloqueio::class);

                return [$pagina->instance()->getAuthDesignerConfig(), ''];
            })(),
            'cadastro aberto'  => (function (): array {
                config(['kit.registro.habilitado' => true]);

                return [null, $this->get('/admin/register')->getContent()];
            })(),
            'cadastro convite' => (function (): array {
                $token = ofertaPara('novo@example.com')->enviar();

                return [null, $this->get("/app/register?token={$token}")->getContent()];
            })(),
            'confirmação'      => (function () use ($usuario): array {
                $this->actingAs($usuario);

                return [null, $this->get('/admin/email-verification/prompt')->getContent()];
            })(),
        };
    };

    config()->set('kit.identidade.aparencia_das_telas', null);
    $this->get('/admin/login')->assertOk();
    [$anterior, $htmlAnterior] = $abrir();

    definirAparenciaGlobal($sentinela);
    [$atual, $htmlAtual] = $abrir();

    expect($atual)->toEqual($anterior);

    foreach (['SENTINELA-FRASE', 'SENTINELA-APOIO', '#fedcba', 'kit-painel-de-marca', 'data-kit-aparencia'] as $marca) {
        expect($htmlAtual)->not->toContain($marca);
    }

    expect(preg_match_all('/media-(left|right)/', $htmlAtual))->toBe(preg_match_all('/media-(left|right)/', $htmlAnterior));
})->with([
    'login', 'recuperar senha', 'redefinir senha', 'dois fatores', 'bloqueio', 'cadastro aberto', 'cadastro convite', 'confirmação',
])->group('kit');

/*
|--------------------------------------------------------------------------
| R1 — sem upload novo
|--------------------------------------------------------------------------
*/

it('[CT-35] o editor reutiliza a identidade sem upload próprio', function (): void {
    definirAparenciaGlobal(aparenciaDeTeste('P'));
    $this->actingAs(usuarioDoKit('master_global'));

    $tela    = telaDeAparencia();
    $uploads = collect($tela->instance()->form->getFlatComponents(withHidden: true))
        ->filter(fn ($c): bool => $c instanceof Filament\Forms\Components\FileUpload && str_contains((string) $c->getStatePath(), 'aparencia_das_telas'))
        ->count();

    expect($uploads)->toBe(0)
        ->and($tela->html())->not->toContain('type="file"');

    // As opções de arte e logo são as da identidade existente: o fundo "arte" e o toggle da logo.
    $tela->assertFormFieldExists(chaveDoCampo($tela, 'fundo'))->assertFormFieldExists(chaveDoCampo($tela, 'logo_no_painel'));
})->group('kit');

/*
|--------------------------------------------------------------------------
| Achados do step 9 (CT-38, CT-40)
|--------------------------------------------------------------------------
*/

it('[CT-38] conta sem acesso ao admin não abre o preview', function (): void {
    // A permissão do escopo existe: a única variável é o acesso ao painel admin.
    papelDoKit('panel_user')->givePermissionTo('View:ConfiguracoesDoKit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $conta = usuarioDoKit('panel_user', 'so-app@example.com');

    $this->actingAs($conta);
    $id = rascunhoDe('global', ['frase' => 'SEGREDO DO RASCUNHO'] + aparenciaDeTeste('A'));

    // O `beforeEach` fixa o painel admin no processo; um request real chega sem painel corrente
    // (QA-01 do gate: com o painel vazado, o caso passava pelo motivo errado).
    Filament::setCurrentPanel(null);

    $resposta = $this->get(urlDoPreviewDe($id));

    expect($resposta->getStatusCode())->toBeIn([302, 403])
        ->and($resposta->getContent())->not->toContain('SEGREDO DO RASCUNHO');
})->group('kit');

it('[CT-40] cor clara deixa o texto do painel escuro', function (?string $cor, bool $tem): void {
    definirAparenciaGlobal(['cor' => $cor, 'fundo' => 'cor'] + aparenciaDeTeste('P'));

    $html   = $this->get('/admin/login')->assertOk()->getContent();
    $painel = arvoreDoHtml($html)->query('//*[@data-kit-aparencia="painel"]');

    expect($painel->length)->toBe(1)
        ->and(str_contains($painel->item(0)->getAttribute('class'), 'kit-painel-de-marca--claro'))->toBe($tem);
})->with([
    'branco' => ['#ffffff', true],
    'bege'   => ['#f5f5dc', true],
    'azul'   => ['#123456', false],
    'preto'  => ['#000000', false],
    // Fronteira (QA-04 do gate): cores médias em que o branco fica abaixo de 3:1.
    'ciano médio'    => ['#00aaff', true],
    'âmbar 600'      => ['#d97706', true],
    'azul 700'       => ['#1d4ed8', false],
    // Sem cor: a primária default (âmbar 600 da paleta, em oklch) também pede texto escuro.
    'primária default' => [null, true],
])->group('kit');
