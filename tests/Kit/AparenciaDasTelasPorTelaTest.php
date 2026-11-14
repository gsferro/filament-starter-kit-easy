<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaDoisFatores;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Pages\Auth\TelaRecuperarSenha;
use App\Filament\Pages\Auth\TelaRedefinirSenha;
use App\Filament\Pages\Auth\TelaVerificacaoDeEmail;
use App\Models\Convite;
use App\Models\Role;
use App\Support\ProvedorAntiRobo;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Aparência das telas de acesso — Adendo 2 (uma aparência por tela, textos formatados, cor
 * primária e gradiente, faixa do celular com arte) e Adendo 3 (login social e anti-robô no painel).
 * IDs de CT em `wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/04-casos-de-teste.md`,
 * seções "Adendo 2" e "Adendo 3".
 */
beforeEach(function (): void {
    Http::preventStrayRequests();
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    comLogoNaIdentidade();
    config(['kit.registro.habilitado' => true, 'kit.login.rodape' => null]);
    Filament::setCurrentPanel('admin');
});

/** Um painel com o formulário do lado pedido e uma frase que identifica a tela. */
function painelComFrase(string $lado, string $frase, array $extra = []): array
{
    return ['lado_do_formulario' => $lado, 'frase' => $frase] + $extra + aparenciaDeTeste('P');
}

function htmlDe(string $caminho): string
{
    return test()->get($caminho)->assertOk()->getContent();
}

function conviteDoCadastro(): string
{
    return Convite::factory()->create([
        'email'   => 'convidado@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ])->enviar();
}

/*
|--------------------------------------------------------------------------
| R26 — uma aparência por família de tela (P-25, P-26)
|--------------------------------------------------------------------------
*/

it('[CT-58] login, cadastro e esqueci a senha usam cada um a sua aparência', function (): void {
    definirAparenciaGlobal([
        ...painelComFrase('direita', 'FRASE DO LOGIN'),
        'telas' => [
            'cadastro'    => painelComFrase('esquerda', 'FRASE DO CADASTRO'),
            'recuperacao' => painelComFrase('esquerda', 'FRASE DA RECUPERACAO'),
        ],
    ]);

    $login       = htmlDe('/admin/login');
    $recuperacao = htmlDe('/admin/password-reset/request');
    $cadastro    = htmlDe('/app/register');

    // Formulário à direita = mídia à esquerda (o lado do painel é o oposto do formulário).
    expect(textoDoPainelDeMarca($login, 'frase'))->toBe('FRASE DO LOGIN')
        ->and($login)->toContain('media-left')
        ->and(textoDoPainelDeMarca($recuperacao, 'frase'))->toBe('FRASE DA RECUPERACAO')
        ->and($recuperacao)->toContain('media-right')
        ->and(textoDoPainelDeMarca($cadastro, 'frase'))->toBe('FRASE DO CADASTRO')
        ->and($cadastro)->toContain('media-right');
})->group('kit');

it('[CT-59] tela sem aparência própria, ou com "igual ao login", segue o login', function (array $telas): void {
    definirAparenciaGlobal([...painelComFrase('direita', 'FRASE DO LOGIN'), 'telas' => $telas]);

    $recuperacao = htmlDe('/admin/password-reset/request');

    expect(textoDoPainelDeMarca($recuperacao, 'frase'))->toBe('FRASE DO LOGIN')
        ->and($recuperacao)->toContain('media-left');
})->with([
    'sem a chave'          => [[]],
    'nula'                 => [['recuperacao' => null]],
    'igual ao login'       => [['recuperacao' => ['igual_ao_login' => true] + painelComFrase('esquerda', 'IGNORADA')]],
    'objeto inválido'      => [['recuperacao' => 'texto']],
])->group('kit');

it('[CT-60] clássico escolhido numa tela vale mesmo com o login em painel, e vice-versa', function (): void {
    definirAparenciaGlobal([...painelComFrase('direita', 'FRASE DO LOGIN'), 'telas' => ['recuperacao' => ['layout' => 'classico']]]);

    afirmarAparenciaNoHtml(htmlDe('/admin/password-reset/request'), null);
    expect(textoDoPainelDeMarca(htmlDe('/admin/login'), 'frase'))->toBe('FRASE DO LOGIN');

    definirAparenciaGlobal(['layout' => 'classico', 'telas' => ['recuperacao' => painelComFrase('esquerda', 'SO A RECUPERACAO')]]);

    afirmarAparenciaNoHtml(htmlDe('/admin/login'), null);
    expect(textoDoPainelDeMarca(htmlDe('/admin/password-reset/request'), 'frase'))->toBe('SO A RECUPERACAO');
})->group('kit');

it('[CT-61] aparência gravada sem "telas" vale igual nas três telas', function (): void {
    definirAparenciaGlobal(painelComFrase('esquerda', 'UMA SO'));

    foreach (['/admin/login', '/admin/password-reset/request', '/app/register'] as $caminho) {
        $html = htmlDe($caminho);
        expect(textoDoPainelDeMarca($html, 'frase'))->toBe('UMA SO', $caminho)
            ->and($html)->toContain('media-right');
    }
})->group('kit');

it('[CT-62] a família de cada página vem da classe da página', function (string $classe, string $familia): void {
    $metodo = new ReflectionMethod($classe, 'familiaDaAparencia');
    $pagina = (new ReflectionClass($classe))->newInstanceWithoutConstructor();

    expect($metodo->invoke($pagina))->toBe($familia);
})->with([
    'login'                 => [TelaLogin::class, 'login'],
    'dois fatores'          => [TelaDoisFatores::class, 'login'],
    'bloqueio'              => [TelaBloqueio::class, 'login'],
    'confirmação de e-mail' => [TelaVerificacaoDeEmail::class, 'login'],
    'cadastro'              => [RegistroPorConvite::class, 'cadastro'],
    'recuperar senha'       => [TelaRecuperarSenha::class, 'recuperacao'],
    'redefinir senha'       => [TelaRedefinirSenha::class, 'recuperacao'],
])->group('kit');

it('[CT-63] o editor grava uma tela própria a partir da cópia do login, e "igual ao login" a descarta', function (): void {
    definirAparenciaGlobal(painelComFrase('direita', 'FRASE DO LOGIN'));
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->set('data.aparencia_das_telas.telas.recuperacao.igual_ao_login', false);

    // A cópia nasce do login daquele momento (P-25).
    expect($tela->get('data.aparencia_das_telas.telas.recuperacao.frase'))->toBe('FRASE DO LOGIN')
        ->and($tela->get('data.aparencia_das_telas.telas.recuperacao.lado_do_formulario'))->toBe('direita');

    $tela->set('data.aparencia_das_telas.telas.recuperacao.lado_do_formulario', 'esquerda')
        ->call('save')
        ->assertHasNoFormErrors();

    $gravada = configuracaoGravada('aparencia_das_telas');
    expect($gravada['lado_do_formulario'])->toBe('direita')
        ->and($gravada['telas']['recuperacao']['lado_do_formulario'])->toBe('esquerda')
        ->and($gravada['telas'])->not->toHaveKey('cadastro');

    $tela->set('data.aparencia_das_telas.telas.recuperacao.igual_ao_login', true)->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada('aparencia_das_telas'))->not->toHaveKey('telas');
})->group('kit');

it('[CT-64] o preview mostra a aparência da tela escolhida', function (string $tela, string $frase): void {
    $this->actingAs(usuarioDoKit('master_global'));
    $id = rascunhoDe('global', [
        ...painelComFrase('direita', 'RASCUNHO LOGIN'),
        'telas' => ['cadastro' => painelComFrase('esquerda', 'RASCUNHO CADASTRO'), 'recuperacao' => painelComFrase('esquerda', 'RASCUNHO RECUPERACAO')],
    ]);

    expect(textoDoPainelDeMarca(htmlDe(urlDoPreviewDe($id, $tela)), 'frase'))->toBe($frase);
})->with([
    ['login', 'RASCUNHO LOGIN'],
    ['cadastro', 'RASCUNHO CADASTRO'],
    ['recuperar-senha', 'RASCUNHO RECUPERACAO'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R27 — textos formatados (P-27)
|--------------------------------------------------------------------------
*/

it('[CT-57] Markdown hostil não deixa elemento executável, estilo nem imagem', function (string $texto): void {
    $html   = (function () use ($texto): string {
        definirAparenciaGlobal(['frase' => $texto] + aparenciaDeTeste('P'));

        return htmlDe('/admin/login');
    })();
    $arvore = arvoreDoHtml($html);
    $frase  = "//*[contains(@class,'kit-painel-de-marca__frase')]";

    expect($arvore->query('//*[@onerror or @onfocus or @onclick]')->length)->toBe(0)
        ->and($arvore->query("{$frase}//script | {$frase}//style | {$frase}//img | {$frase}//iframe | {$frase}//h1")->length)->toBe(0)
        ->and($arvore->query("{$frase}//a[starts-with(normalize-space(@href), 'javascript:')]")->length)->toBe(0)
        ->and($arvore->query("//script[contains(., 'alert(1)')]")->length)->toBe(0);
})->with([
    'html cru'          => ['<b onclick="alert(1)">Marca</b><script>alert(1)</script>'],
    'imagem markdown'   => ['![x](https://exemplo.test/x.png)'],
    'link javascript'   => ['[clique](javascript:alert(1))'],
    'título'            => ['# Título'],
    'estilo'            => ['<style>body{display:none}</style>'],
    'iframe'            => ['<iframe src="https://exemplo.test"></iframe>'],
])->group('kit');

it('[CT-65] a formatação acordada chega à tela', function (string $markdown, string $xpath): void {
    definirAparenciaGlobal(['apoio' => $markdown] + aparenciaDeTeste('P'));

    expect(arvoreDoHtml(htmlDe('/admin/login'))->query("//*[contains(@class,'kit-painel-de-marca__apoio')]{$xpath}")->length)->toBe(1);
})->with([
    'negrito'  => ['**forte**', '//strong'],
    'itálico'  => ['*leve*', '//em'],
    'tachado'  => ['~~velho~~', '//del'],
    'link'     => ['[ajuda](https://exemplo.test/ajuda)', '//a[@href="https://exemplo.test/ajuda" and @rel="noopener noreferrer"]'],
])->group('kit');

it('[CT-66] o teto mede o texto visível e há um teto bruto de 4 KiB', function (string $frase, bool $aceita): void {
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()
        ->fillForm(['aparencia_das_telas' => ['frase' => $frase] + aparenciaDeTeste('P0')])
        ->call('save');

    $aceita ? $tela->assertHasNoFormErrors() : $tela->assertHasFormErrors(['aparencia_das_telas.frase']);
})->with([
    '80 visíveis com negrito'         => ['**'.str_repeat('a', 80).'**', true],
    '81 visíveis'                     => [str_repeat('a', 81), false],
    'link longo, 5 visíveis'          => ['[ajuda](https://exemplo.test/'.str_repeat('x', 300).')', true],
    'bruto acima de 4 KiB'            => ['[a](https://exemplo.test/'.str_repeat('x', 4100).')', false],
])->group('kit');

it('[CT-67] texto gravado antes do Adendo 2 aparece igual, e continua igual depois de salvo no editor', function (): void {
    $antigo = '*Oferta* <empresa> [acesse](x)';
    definirAparenciaGlobal(['frase' => $antigo, 'formato_dos_textos' => null] + aparenciaDeTeste('P'));

    expect(textoDoPainelDeMarca(htmlDe('/admin/login'), 'frase'))->toBe($antigo);

    $this->actingAs(usuarioDoKit('master_global'));
    carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()->call('save')->assertHasNoFormErrors();
    auth()->logout();

    $gravada = configuracaoGravada('aparencia_das_telas');
    definirAparenciaGlobal($gravada);

    expect($gravada['formato_dos_textos'])->toBe('markdown')
        ->and(textoDoPainelDeMarca(htmlDe('/admin/login'), 'frase'))->toBe($antigo);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R28 — cor primária e gradiente (P-28, P-29)
|--------------------------------------------------------------------------
*/

it('[CT-68] o gradiente monta o CSS só com cores validadas e um ângulo do vocabulário', function (array $campos, string $esperado): void {
    definirAparenciaGlobal(['fundo' => 'gradiente'] + $campos + aparenciaDeTeste('P'));

    $html   = htmlDe('/admin/login');
    $painel = arvoreDoHtml($html)->query('//*[@data-kit-aparencia="painel"]')->item(0);

    expect($painel->getAttribute('class'))->toContain('kit-painel-de-marca--gradiente')
        ->and($painel->getAttribute('class'))->not->toContain('kit-painel-de-marca--claro')
        ->and($painel->getAttribute('style'))->toContain('--kit-painel-gradiente: '.$esperado);
})->with([
    'duas cores, vertical'       => [['gradiente_de' => '#112233', 'gradiente_ate' => '#445566', 'gradiente_direcao' => 'vertical'], 'linear-gradient(180deg, #112233, #445566)'],
    'horizontal'                 => [['gradiente_de' => '#112233', 'gradiente_ate' => '#445566', 'gradiente_direcao' => 'horizontal'], 'linear-gradient(90deg, #112233, #445566)'],
    'direção inválida'           => [['gradiente_de' => '#112233', 'gradiente_ate' => '#445566', 'gradiente_direcao' => '45deg); background:url(x'], 'linear-gradient(135deg, #112233, #445566)'],
    'cor com CSS injetado'       => [['gradiente_de' => 'red; x:y', 'gradiente_ate' => '#445566', 'gradiente_direcao' => 'diagonal'], 'linear-gradient(135deg, '.Color::Amber[600].', #445566)'],
    'vazias: primária 600 e 900' => [['gradiente_de' => null, 'gradiente_ate' => null], 'linear-gradient(135deg, '.Color::Amber[600].', '.Color::Amber[900].')'],
])->group('kit');

it('[CT-69] "cor primária" grava a cor vazia, e a tela usa a primária', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()
        ->fillForm(['aparencia_das_telas' => ['cor' => '#123456'] + aparenciaDeTeste('P')])
        ->set('data.aparencia_das_telas.origem_da_cor', 'primaria')
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('aparencia_das_telas')['cor'])->toBeNull();

    auth()->logout();
    definirAparenciaGlobal(configuracaoGravada('aparencia_das_telas'));

    expect(arvoreDoHtml(htmlDe('/admin/login'))->query('//*[@data-kit-aparencia="painel"]')->item(0)->getAttribute('style'))
        ->toContain('--kit-painel-fundo: '.Color::Amber[600]);
})->group('kit');

it('[CT-70] o gradiente não sai pelo componente fora do painel', function (): void {
    definirAparenciaGlobal(['layout' => 'classico', 'fundo' => 'gradiente', 'gradiente_de' => '#112233']);
    $this->get('/admin/login')->assertOk();

    expect(Livewire::test(TelaLogin::class)->instance()->gradienteDoPainelDeMarca())->toBeNull();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R29 — login social e anti-robô no painel (Adendo 3: RQ-31, RQ-32)
|--------------------------------------------------------------------------
*/

it('[CT-71] o login social aparece no layout painel, com a aparência própria do login', function (string $painel): void {
    ligarProvedor(ProvedorSocial::Google);
    definirAparenciaGlobal([...painelComFrase('direita', 'FRASE DO LOGIN'), 'telas' => ['recuperacao' => painelComFrase('esquerda', 'OUTRA')]]);

    $html = $this->get("/{$painel}/login")
        ->assertOk()
        ->assertSeeInOrder(['form.password', 'Entrar com '.ProvedorSocial::Google->rotulo()], escape: false)
        ->assertSee('/auth/google/redirect', escape: false)
        ->getContent();

    expect(arvoreDoHtml($html)->query('//*[@data-kit-aparencia="painel"]')->length)->toBe(1);
})->with(['app', 'admin', 'infra'])->group('kit');

it('[CT-72] o anti-robô aparece no layout painel em login, cadastro e esqueci a senha', function (string $tela): void {
    config()->set('kit.login.anti_robo', [
        'habilitado'    => true, 'local' => false, 'provedor' => ProvedorAntiRobo::RecaptchaV2->value,
        'chave_do_site' => 'SITE-42', 'chave_secreta' => 'SEGREDO-42', 'pontuacao_minima' => 0.5,
    ]);
    definirAparenciaGlobal([
        ...painelComFrase('direita', 'LOGIN'),
        'telas' => ['cadastro' => painelComFrase('esquerda', 'CADASTRO'), 'recuperacao' => painelComFrase('esquerda', 'RECUPERACAO')],
    ]);

    $caminho = $tela === 'cadastro' ? '/app/register?token='.conviteDoCadastro() : $tela;
    $html    = htmlDe($caminho);

    expect($html)->toContain('www.google.com/recaptcha/api.js')
        ->and($html)->toContain('data-anti-robo="'.ProvedorAntiRobo::RecaptchaV2->value.'"')
        ->and($html)->not->toContain('SEGREDO-42')
        ->and(arvoreDoHtml($html)->query('//*[@data-kit-aparencia="painel"]')->length)->toBe(1);
})->with([
    'login do app'         => ['/app/login'],
    'login do admin'       => ['/admin/login'],
    'esqueci a senha'      => ['/admin/password-reset/request'],
    'cadastro por convite' => ['cadastro'],
])->group('kit');
