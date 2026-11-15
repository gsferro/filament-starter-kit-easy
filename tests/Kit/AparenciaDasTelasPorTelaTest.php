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
use App\Models\User;
use App\Support\ProvedorAntiRobo;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
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
        ->and($arvore->query("{$frase}//a[starts-with(normalize-space(@href), 'javascript:') or starts-with(normalize-space(@href), '//') or @title or @onclick]")->length)->toBe(0)
        ->and($arvore->query("//script[contains(., 'alert(1)')]")->length)->toBe(0);
})->with([
    'html cru'          => ['<b onclick="alert(1)">Marca</b><script>alert(1)</script>'],
    'imagem markdown'   => ['![x](https://exemplo.test/x.png)'],
    'link javascript'   => ['[clique](javascript:alert(1))'],
    'título'            => ['# Título'],
    'estilo'            => ['<style>body{display:none}</style>'],
    'iframe'            => ['<iframe src="https://exemplo.test"></iframe>'],
    // A limpeza é por DOM, não por regex (SEC-B1): título com `href=` não engole link, nem sobra.
    'título com href'   => ['[a](https://exemplo.test "x href=") [b](javascript:alert(1))'],
    'sem esquema'       => ['[c](//exemplo.test/x)'],
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

    if ($aceita) {
        $tela->assertHasNoFormErrors();

        return;
    }

    // Recusa: erro no campo e nada gravado (P-10).
    $tela->assertHasFormErrors(['aparencia_das_telas.frase']);
    expect(configuracaoGravada('aparencia_das_telas'))->toBeNull();
})->with([
    '80 visíveis com negrito'         => ['**'.str_repeat('a', 80).'**', true],
    '81 visíveis'                     => [str_repeat('a', 81), false],
    'link longo, 5 visíveis'          => ['[ajuda](https://exemplo.test/'.str_repeat('x', 300).')', true],
    'bruto acima de 4 KiB'            => ['[a](https://exemplo.test/'.str_repeat('x', 4100).')', false],
])->group('kit');

it('[CT-67] texto gravado antes do Adendo 2 aparece igual, e continua igual depois de salvo no editor', function (): void {
    // Com entidade e URL: o editor não pode trocar `&copy;` por © nem criar link (CX-B2, RD-B5).
    $antigo = '*Oferta* <empresa> [acesse](x) A &copy; B https://exemplo.test/x';
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

/*
|--------------------------------------------------------------------------
| Achados da revisão do diff dos Adendos 2 e 3
|--------------------------------------------------------------------------
*/

it('[CT-74] desligar "igual ao login" com imagem gravada no login copia a imagem e a cor, e salva', function (): void {
    Storage::disk('public')->put('telas-de-acesso/do-login.png', 'png');
    definirAparenciaGlobal(['destaque' => 'imagem', 'imagem_do_destaque' => 'telas-de-acesso/do-login.png', 'cor' => '#336699'] + aparenciaDeTeste('P'));
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()
        ->set('data.aparencia_das_telas.telas.recuperacao.igual_ao_login', false);

    // O auxiliar da cor acompanha a cópia (CX-B5): a cor própria continua à vista.
    expect($tela->get('data.aparencia_das_telas.telas.recuperacao.origem_da_cor'))->toBe('propria');

    $tela->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada('aparencia_das_telas')['telas']['recuperacao']['imagem_do_destaque'])->toBe('telas-de-acesso/do-login.png')
        ->and(configuracaoGravada('aparencia_das_telas')['telas']['recuperacao']['cor'])->toBe('#336699');
})->group('kit');

it('[CT-75] tela própria com layout inválido cai no clássico, e não no login', function (mixed $layout): void {
    definirAparenciaGlobal([...painelComFrase('direita', 'LOGIN'), 'telas' => ['recuperacao' => ['layout' => $layout] + painelComFrase('esquerda', 'X')]]);

    afirmarAparenciaNoHtml(htmlDe('/admin/password-reset/request'), null);
})->with(['desconhecido' => 'xyz', 'ausente' => null])->group('kit');

it('[CT-76] a aparência devolvida pelo componente não traz as cores do gradiente fora do gradiente', function (): void {
    definirAparenciaGlobal(['fundo' => 'cor', 'gradiente_de' => '#112233', 'gradiente_ate' => '#445566'] + aparenciaDeTeste('P'));
    $this->get('/admin/login')->assertOk();

    $aparencia = Livewire::test(TelaLogin::class)->instance()->aparenciaDaTela();

    expect($aparencia['gradiente_de'])->toBeNull()->and($aparencia['gradiente_ate'])->toBeNull();
})->group('kit');

it('[CT-77] com primária hex, o gradiente vazio vai do tom 600 ao 900 da mesma paleta', function (): void {
    config()->set('kit.cor_primaria_hex', '#336699');
    definirAparenciaGlobal(['fundo' => 'gradiente', 'gradiente_de' => null, 'gradiente_ate' => null] + aparenciaDeTeste('P'));

    $paleta = Color::hex('#336699');
    $estilo = arvoreDoHtml(htmlDe('/admin/login'))->query('//*[@data-kit-aparencia="painel"]')->item(0)->getAttribute('style');

    expect($estilo)->toContain("linear-gradient(135deg, {$paleta[600]}, {$paleta[900]})");
})->group('kit');

it('[CT-78] no preview, a tela que segue o login usa a imagem ainda não salva do login', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()
        ->fillForm(['aparencia_das_telas' => [
            'destaque'           => 'imagem',
            'imagem_do_destaque' => UploadedFile::fake()->image('nova.png', 200, 120),
        ] + aparenciaDeTeste('P')]);

    $id       = rascunhoDoEditor($tela);
    $mapa     = Cache::get('aparencia-preview:'.auth()->id().':'.$id)['imagem_temporaria'];

    expect($mapa)->toHaveKeys(['login', 'cadastro', 'recuperacao'])
        ->and($mapa['cadastro'])->toBe($mapa['login'])
        ->and($mapa['recuperacao'])->toBe($mapa['login']);
})->group('kit');

/*
|--------------------------------------------------------------------------
| Revisão adversarial dos Adendos 2 e 3 (ADV-C1..C32)
|--------------------------------------------------------------------------
*/

it('[CT-80] a frase aplica o Markdown, não só o apoio', function (): void {
    definirAparenciaGlobal(['frase' => 'Uma **forte** frase'] + aparenciaDeTeste('P'));

    $nos = arvoreDoHtml(htmlDe('/admin/login'))->query("//*[contains(@class,'kit-painel-de-marca__frase')]//strong");

    expect($nos->length)->toBe(1)->and($nos->item(0)->textContent)->toBe('forte');
})->group('kit');

it('[CT-81] frase e apoio hostis saem só com a formatação acordada e links de esquema seguro', function (string $campo, string $texto): void {
    definirAparenciaGlobal([$campo => $texto] + aparenciaDeTeste('P'));

    $arvore = arvoreDoHtml(htmlDe('/admin/login'));
    $bloco  = "//*[contains(@class,'kit-painel-de-marca__{$campo}')]";

    $proibidos = $arvore->query("{$bloco}//*[not(self::p or self::br or self::strong or self::em or self::del or self::a)]");
    expect($proibidos->length)->toBe(0, 'elemento fora da lista acordada');

    foreach ($arvore->query("{$bloco}//a[@href]") as $link) {
        expect(preg_match('#^(https?://|mailto:|/(?!/))#i', $link->getAttribute('href')))->toBe(1, 'href: '.$link->getAttribute('href'));
    }

    expect($arvore->query("{$bloco}//*[@onclick or @onerror or @style or @title or @class]")->length)->toBe(0);
})->with(function (): iterable {
    $hostis = [
        'esquema em caixa mista' => '[x](JaVaScRiPt:alert(1))',
        'espaço antes'           => '[x]( javascript:alert(1))',
        'data'                   => '[x](data:text/html,<script>alert(1)</script>)',
        'vbscript'               => '[x](vbscript:msgbox(1))',
        'formulário cru'         => '<form action="https://x.test"><input name=p></form><b>M</b><div>d</div>',
        'link cru'               => '<a href="javascript:alert(1)">clique</a>',
    ];

    foreach (['frase', 'apoio'] as $campo) {
        foreach ($hostis as $nome => $texto) {
            yield "{$campo}: {$nome}" => [$campo, $texto];
        }
    }
})->group('kit');

it('[CT-82] a tela própria usa os SEUS fundo, largura, destaque e ilustração, não os do login', function (): void {
    definirAparenciaGlobal([
        ...painelComFrase('direita', 'LOGIN', ['fundo' => 'cor', 'cor' => '#112233', 'tamanho_do_painel' => 50]),
        'telas' => ['recuperacao' => painelComFrase('esquerda', 'REC', [
            'fundo'    => 'gradiente', 'gradiente_de' => '#445566', 'gradiente_ate' => '#778899', 'tamanho_do_painel' => 30,
            'destaque' => 'logo', 'ilustracao' => 'rede',
        ])],
    ]);

    $login       = htmlDe('/admin/login');
    $recuperacao = htmlDe('/admin/password-reset/request');

    expect($recuperacao)->toContain('kit-painel-de-marca--gradiente')
        ->and($recuperacao)->toContain('--media-size: 30%')
        ->and($recuperacao)->toContain('data-kit-destaque')
        ->and($recuperacao)->toContain('data-kit-ilustracao')
        ->and($login)->not->toContain('kit-painel-de-marca--gradiente')
        ->and($login)->toContain('--media-size: 50%')
        ->and($login)->not->toContain('data-kit-ilustracao');
})->group('kit');

it('[CT-83] tela gravada vazia segue o login; tela própria sem frase não herda a do login', function (): void {
    definirAparenciaGlobal([...painelComFrase('direita', 'FRASE DO LOGIN'), 'telas' => ['recuperacao' => []]]);
    expect(textoDoPainelDeMarca(htmlDe('/admin/password-reset/request'), 'frase'))->toBe('FRASE DO LOGIN');

    definirAparenciaGlobal([...painelComFrase('direita', 'FRASE DO LOGIN'), 'telas' => ['recuperacao' => ['layout' => 'painel', 'lado_do_formulario' => 'esquerda']]]);
    expect(textoDoPainelDeMarca(htmlDe('/admin/password-reset/request'), 'frase'))->toBeNull();
})->group('kit');

it('[CT-84] religar "igual ao login" numa seção não apaga a outra tela própria, e a cópia não leva "telas"', function (): void {
    definirAparenciaGlobal([
        ...painelComFrase('direita', 'LOGIN'),
        'telas' => ['cadastro' => painelComFrase('esquerda', 'CAD'), 'recuperacao' => painelComFrase('esquerda', 'REC')],
    ]);
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->set('data.aparencia_das_telas.telas.recuperacao.igual_ao_login', true)->call('save')->assertHasNoFormErrors();

    $gravada = configuracaoGravada('aparencia_das_telas');
    expect($gravada['telas'])->toHaveKey('cadastro')->not->toHaveKey('recuperacao')
        ->and($gravada['telas']['cadastro']['frase'])->toBe('CAD');

    // A seção "Cadastro": desligar e religar trabalha só nela, e a cópia nasce sem "telas" dentro.
    $tela->set('data.aparencia_das_telas.telas.cadastro.igual_ao_login', true)->call('save');
    $tela->set('data.aparencia_das_telas.telas.cadastro.igual_ao_login', false);

    expect($tela->get('data.aparencia_das_telas.telas.cadastro.frase'))->toBe('LOGIN')
        ->and($tela->get('data.aparencia_das_telas.telas.cadastro'))->not->toHaveKey('telas');

    $tela->call('save')->assertHasNoFormErrors();
    expect(configuracaoGravada('aparencia_das_telas')['telas'])->toHaveKey('cadastro')->not->toHaveKey('recuperacao')
        ->and(configuracaoGravada('aparencia_das_telas')['telas']['cadastro'])->not->toHaveKey('telas');
})->group('kit');

it('[CT-85] cadastro por convite e redefinição de senha renderizam a aparência da sua família', function (): void {
    definirAparenciaGlobal([
        ...painelComFrase('direita', 'FRASE LOGIN'),
        'telas' => ['cadastro' => painelComFrase('esquerda', 'FRASE CAD'), 'recuperacao' => painelComFrase('esquerda', 'FRASE REC')],
    ]);

    expect(textoDoPainelDeMarca(htmlDe('/app/register?token='.conviteDoCadastro()), 'frase'))->toBe('FRASE CAD');

    // A redefinição de verdade: a URL assinada que o e-mail de recuperação leva (divergência 3 do QA-34).
    Filament::setCurrentPanel('admin');
    $pessoa = User::factory()->create();
    $url    = Filament::getResetPasswordUrl(Password::broker()->createToken($pessoa), $pessoa);

    expect(textoDoPainelDeMarca(htmlDe($url), 'frase'))->toBe('FRASE REC');
})->group('kit');

it('[CT-86] cor de gradiente com CSS anexado ou fora de #RRGGBB não chega ao estilo', function (array $campos, string $esperado): void {
    definirAparenciaGlobal(['fundo' => 'gradiente'] + $campos + aparenciaDeTeste('P'));

    $estilo = arvoreDoHtml(htmlDe('/admin/login'))->query('//*[@data-kit-aparencia="painel"]')->item(0)->getAttribute('style');

    expect($estilo)->toContain('--kit-painel-gradiente: '.$esperado)->not->toContain('url(');
})->with([
    'início com CSS anexado' => [['gradiente_de' => '#112233;background:url(x)', 'gradiente_ate' => '#445566'], 'linear-gradient(135deg, '.Color::Amber[600].', #445566)'],
    'fim com CSS'            => [['gradiente_de' => '#112233', 'gradiente_ate' => 'red; x:y'], 'linear-gradient(135deg, #112233, '.Color::Amber[900].')'],
    'oito dígitos'           => [['gradiente_de' => '#11223344', 'gradiente_ate' => '#445566'], 'linear-gradient(135deg, '.Color::Amber[600].', #445566)'],
])->group('kit');

it('[CT-87] o editor recusa cor de gradiente fora de #RRGGBB e não grava nada', function (string $valor): void {
    $this->actingAs(usuarioDoKit('master_global'));

    carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()
        ->fillForm(['aparencia_das_telas' => ['fundo' => 'gradiente', 'gradiente_de' => $valor] + aparenciaDeTeste('P')])
        ->call('save')
        ->assertHasFormErrors(['aparencia_das_telas.gradiente_de']);

    expect(configuracaoGravada('aparencia_das_telas'))->toBeNull();
})->with(['#abc', 'red', '#11223344'])->group('kit');

it('[CT-88] login social e anti-robô só aparecem quando configurados', function (): void {
    // Desligados de forma explícita, sem depender do padrão do ambiente (divergência 4 do QA-34).
    config()->set('kit.login.anti_robo.habilitado', false);
    foreach (ProvedorSocial::cases() as $provedor) {
        config()->set("kit.login.{$provedor->value}.habilitado", false);
    }
    definirAparenciaGlobal(painelComFrase('direita', 'LOGIN'));

    $html = htmlDe('/admin/login');

    // Controle positivo: o painel de marca foi desenhado; a ausência abaixo é dentro dele.
    expect(textoDoPainelDeMarca($html, 'frase'))->toBe('LOGIN')
        ->and($html)->not->toContain('/auth/google/redirect')
        ->and($html)->not->toContain('Entrar com')
        ->and($html)->not->toContain('www.google.com/recaptcha/api.js')
        ->and($html)->not->toContain('data-anti-robo');
})->group('kit');

it('[CT-89] o anti-robô aparece também no cadastro aberto no painel', function (): void {
    config()->set('kit.login.anti_robo', [
        'habilitado'    => true, 'local' => false, 'provedor' => ProvedorAntiRobo::RecaptchaV2->value,
        'chave_do_site' => 'SITE-42', 'chave_secreta' => 'SEGREDO-42', 'pontuacao_minima' => 0.5,
    ]);
    config(['kit.registro.habilitado' => true]);
    definirAparenciaGlobal([...painelComFrase('direita', 'LOGIN'), 'telas' => ['cadastro' => painelComFrase('esquerda', 'CAD')]]);

    $html = htmlDe('/app/register');

    expect($html)->toContain('data-anti-robo="'.ProvedorAntiRobo::RecaptchaV2->value.'"')
        ->and(textoDoPainelDeMarca($html, 'frase'))->toBe('CAD');
})->group('kit');

it('[CT-90] o teto do apoio mede o visível e o bruto em bytes', function (string $apoio, bool $aceita): void {
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()
        ->fillForm(['aparencia_das_telas' => ['apoio' => $apoio] + aparenciaDeTeste('P0')])
        ->call('save');

    if ($aceita) {
        $tela->assertHasNoFormErrors();

        return;
    }

    $tela->assertHasFormErrors(['aparencia_das_telas.apoio']);
    expect(configuracaoGravada('aparencia_das_telas'))->toBeNull();
})->with([
    '160 visíveis com negrito' => ['**'.str_repeat('a', 160).'**', true],
    '161 visíveis'             => [str_repeat('a', 161), false],
    '4096 bytes'               => ['[a](https://exemplo.test/'.str_repeat('x', 4096 - strlen('[a](https://exemplo.test/)')).')', true],
    '4097 bytes'               => ['[a](https://exemplo.test/'.str_repeat('x', 4097 - strlen('[a](https://exemplo.test/)')).')', false],
    'multibyte acima'          => ['[a](https://exemplo.test/'.str_repeat('é', 2100).')', false],
])->group('kit');

it('[CT-91] texto antigo com metacaracteres continua literal depois de salvo, na frase e no apoio', function (): void {
    $frase = '# A ~~b~~ _c_ `d`';
    $apoio = '- item + mais 1. um > citação';
    definirAparenciaGlobal(['frase' => $frase, 'apoio' => $apoio, 'formato_dos_textos' => null] + aparenciaDeTeste('P'));
    $this->actingAs(usuarioDoKit('master_global'));

    carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh()->call('save')->assertHasNoFormErrors();
    auth()->logout();
    definirAparenciaGlobal(configuracaoGravada('aparencia_das_telas'));

    $html = htmlDe('/admin/login');

    expect(textoDoPainelDeMarca($html, 'frase'))->toBe($frase)
        ->and(textoDoPainelDeMarca($html, 'apoio'))->toBe($apoio);
})->group('kit');

it('[CT-92] frase gravada acima do teto visível volta ao padrão na tela', function (int $visiveis, ?string $esperada): void {
    definirAparenciaGlobal(['frase' => '**'.str_repeat('a', $visiveis).'**'] + aparenciaDeTeste('P'));

    expect(textoDoPainelDeMarca(htmlDe('/admin/login'), 'frase'))->toBe($esperada);
})->with([
    // O teto conta o visível: 80 caracteres com a marcação de negrito ainda aparecem.
    'no teto (80)'       => [80, str_repeat('a', 80)],
    'acima do teto (81)' => [81, null],
])->group('kit');

/*
|--------------------------------------------------------------------------
| Adendo 4 — transição entre as telas (RQ-34..RQ-36, P-32..P-34)
|--------------------------------------------------------------------------
*/

/** A página participa da transição: a regra `@view-transition` no <head> e o painel marcado. */
function participaDaTransicao(string $html): bool
{
    // No <head>, uma vez só (CX-D3 da revisão do Adendo 4).
    $regra  = arvoreDoHtml($html)->query('//head/style[@data-kit-transicao-entre-telas]');
    expect(arvoreDoHtml($html)->query('//style[@data-kit-transicao-entre-telas]')->length)->toBe($regra->length);
    $painel = arvoreDoHtml($html)->query('//*[@data-kit-aparencia="painel"][@data-kit-transicao]');

    if ($regra->length === 0) {
        expect($painel->length)->toBe(0, 'o painel está marcado para a transição, mas a página não tem a regra');

        return false;
    }

    // A regra só vale em tela larga e sem "reduzir movimento" (P-33).
    expect($regra->length)->toBe(1);
    expect(preg_replace('/\s+/', ' ', $regra->item(0)->textContent))
        ->toContain('@media (min-width: 1024px) and (prefers-reduced-motion: no-preference) { @view-transition { navigation: auto; } }')
        ->and($painel->length)->toBe(1);

    return true;
}

it('[CT-98] a página no painel participa da transição conforme a opção do login', function (array $aparencia, bool $participa): void {
    definirAparenciaGlobal($aparencia);

    expect(participaDaTransicao(htmlDe('/admin/login')))->toBe($participa)
        ->and(participaDaTransicao(htmlDe('/admin/password-reset/request')))->toBe($participa);
})->with([
    'ligada'                     => [[...aparenciaDeTeste('P'), 'transicao_entre_telas' => true], true],
    'desligada'                  => [[...aparenciaDeTeste('P'), 'transicao_entre_telas' => false], false],
    'gravada antes do Adendo 4'  => [array_diff_key(aparenciaDeTeste('P'), ['transicao_entre_telas' => true]), true],
    'valor inválido'             => [[...aparenciaDeTeste('P'), 'transicao_entre_telas' => 'sim'], true],
    'clássico'                   => [['layout' => 'classico', 'transicao_entre_telas' => true], false],
])->group('kit');

it('[CT-99] a transição é uma opção só, a do login: a tela própria não a liga nem desliga', function (array $aparencia, bool $participa): void {
    definirAparenciaGlobal($aparencia);

    expect(participaDaTransicao(htmlDe('/admin/password-reset/request')))->toBe($participa);
})->with([
    'login desligada, própria gravada ligada' => [[
        ...painelComFrase('direita', 'LOGIN', ['transicao_entre_telas' => false]),
        'telas' => ['recuperacao' => painelComFrase('esquerda', 'REC', ['transicao_entre_telas' => true])],
    ], false],
    'login ligada, própria gravada desligada' => [[
        ...painelComFrase('direita', 'LOGIN', ['transicao_entre_telas' => true]),
        'telas' => ['recuperacao' => painelComFrase('esquerda', 'REC', ['transicao_entre_telas' => false])],
    ], true],
    'login no clássico com a opção desligada na raiz' => [[
        'layout' => 'classico', 'transicao_entre_telas' => false,
        'telas'  => ['recuperacao' => painelComFrase('esquerda', 'REC', ['transicao_entre_telas' => true])],
    ], false],
    'login no clássico sem nada gravado na raiz' => [[
        'layout' => 'classico',
        'telas'  => ['recuperacao' => painelComFrase('esquerda', 'REC', ['transicao_entre_telas' => false])],
    ], true],
])->group('kit');

it('[CT-100] o editor tem a opção só na seção Login e grava o que a pessoa escolheu', function (): void {
    definirAparenciaGlobal(painelComFrase('direita', 'LOGIN'));
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->set('data.aparencia_das_telas.telas.recuperacao.igual_ao_login', false);

    $caminhos = collect($tela->instance()->form->getFlatComponents(withHidden: true))->map(fn ($c): string => (string) $c->getStatePath());

    expect($caminhos->contains('data.aparencia_das_telas.transicao_entre_telas'))->toBeTrue()
        ->and($caminhos->filter(fn (string $c): bool => preg_match('/\.telas\.(cadastro|recuperacao)\./', $c) === 1 && str_ends_with($c, 'transicao_entre_telas'))->all())->toBe([]);

    $tela->set('data.aparencia_das_telas.transicao_entre_telas', false)->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada('aparencia_das_telas')['transicao_entre_telas'])->toBeFalse();

    // As telas de acesso são de quem não entrou: sai, com a configuração como o boot a alinharia.
    auth()->logout();
    alinharConfiguracoesDoKit();

    expect(participaDaTransicao(htmlDe('/admin/login')))->toBeFalse()
        ->and(participaDaTransicao(htmlDe('/admin/password-reset/request')))->toBeFalse();
})->group('kit');

/**
 * RD-D1/RD-D2 da revisão do Adendo 4: pelo caminho real (o editor grava, a tela lê, o editor
 * reabre), a opção desligada sobrevive ao login no clássico com telas próprias no painel.
 */
it('[CT-102] com o login no clássico, a transição desligada continua desligada depois de salvar e reabrir', function (): void {
    definirAparenciaGlobal(painelComFrase('direita', 'LOGIN'));
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->set('data.aparencia_das_telas.telas.recuperacao.igual_ao_login', false)
        ->set('data.aparencia_das_telas.transicao_entre_telas', false)
        ->set('data.aparencia_das_telas.layout', 'classico')
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('aparencia_das_telas')['transicao_entre_telas'] ?? null)->toBeFalse();

    $reaberta = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    expect($reaberta->get('data.aparencia_das_telas.transicao_entre_telas'))->toBeFalse();

    auth()->logout();
    alinharConfiguracoesDoKit();

    expect(participaDaTransicao(htmlDe('/admin/password-reset/request')))->toBeFalse();
})->group('kit');

/** CT-98 no login unificado: as páginas únicas também participam (RD-D3). */
it('[CT-103] as páginas únicas do login unificado participam da transição', function (string $caminho): void {
    ligarLoginUnificado();
    config(['kit.registro.habilitado' => true]);
    definirAparenciaGlobal([...aparenciaDeTeste('P'), 'transicao_entre_telas' => true]);

    expect(participaDaTransicao(htmlDe($caminho)))->toBeTrue();
})->with(['/login', '/esqueci-minha-senha'])->group('kit');

it('[CT-101] o formulário não desliza: só o painel tem a animação de posição, e o formulário entra por opacidade', function (string $arquivo): void {
    $css = preg_replace('/\s+/', ' ', (string) file_get_contents(base_path($arquivo)));

    $bloco = Str::between($css, '@media (min-width: 1024px) and (prefers-reduced-motion: no-preference) { .fi-auth-layout:has(.kit-painel-de-marca[data-kit-transicao])', '@keyframes kit-formulario-aparece');

    expect($bloco)->toContain('.fi-auth-media-wrapper { view-transition-name: kit-painel; }')
        ->toContain('.fi-auth-form-container { view-transition-name: kit-formulario; }')
        ->toContain('::view-transition-group(kit-formulario) { animation: none; }')
        ->toContain('::view-transition-old(kit-formulario) { animation: none; opacity: 0; }')
        ->toContain('::view-transition-new(kit-formulario) { animation: kit-formulario-aparece')
        ->and($css)->toContain('@keyframes kit-formulario-aparece { from { opacity: 0; } to { opacity: 1; } }');
})->with(['resources/css/filament/kit.css', 'public/css/kit/kit-correcoes.css'])->group('kit');
