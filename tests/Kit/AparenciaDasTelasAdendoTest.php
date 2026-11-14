<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Pages\Auth\TelaLogin;
use App\Support\AparenciaDasTelasDeAcesso;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Aparência das telas de acesso — Adendo 1 (tamanho do painel, destaque central, ilustração,
 * celular só com a logo). IDs de CT em
 * `wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/04-casos-de-teste.md`,
 * seção "Adendo 1".
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    comLogoNaIdentidade();
    Filament::setCurrentPanel('admin');
});

function loginDoAdendo(?array $aparencia): string
{
    definirAparenciaGlobal($aparencia);

    return test()->get('/admin/login')->assertOk()->getContent();
}

function configDoLoginDoAdendo(): mixed
{
    Filament::setCurrentPanel('admin');

    return Livewire::test(TelaLogin::class)->instance()->getAuthDesignerConfig();
}

/** Uma imagem do destaque gravada no disco público (o `comLogoNaIdentidade()` já faz o fake). */
function imagemDoDestaqueNoDisco(string $nome = 'destaque.png'): string
{
    Storage::disk('public')->put('telas-de-acesso/'.$nome, 'png');

    return 'telas-de-acesso/'.$nome;
}

/*
|--------------------------------------------------------------------------
| R18 — vocabulário dos campos novos (P-19..P-21, P-23, P-24)
|--------------------------------------------------------------------------
*/

it('[CT-44] campo novo fora do vocabulário volta ao padrão do campo', function (string $campo, mixed $valor, mixed $padrao): void {
    $lido = AparenciaDasTelasDeAcesso::normalizar([$campo => $valor] + aparenciaDeTeste('P'), 'teste');

    expect($lido[$campo])->toBe($padrao)
        ->and($lido['frase'])->toBe('PADRAO');
})->with([
    'tamanho abaixo'        => ['tamanho_do_painel', 25, 50],
    'tamanho acima'         => ['tamanho_do_painel', 75, 50],
    'tamanho fora do passo' => ['tamanho_do_painel', 52, 50],
    'tamanho texto'         => ['tamanho_do_painel', 'grande', 50],
    'tamanho negativo'      => ['tamanho_do_painel', -50, 50],
    'destaque'              => ['destaque', 'video', 'nenhum'],
    'tamanho do destaque'   => ['tamanho_do_destaque', 'enorme', 'medio'],
    'ilustração'            => ['ilustracao', 'estrelas', 'nenhuma'],
    'movimento'             => ['movimento', 'sim', true],
    'movimento vazio'       => ['movimento', '', true],
    'cor da ilustração'     => ['cor_da_ilustracao', 'red; background: url(x)', null],
])->group('kit');

it('[CT-44] valores válidos dos campos novos são preservados', function (string $campo, mixed $valor, mixed $lido): void {
    expect(AparenciaDasTelasDeAcesso::normalizar([$campo => $valor] + aparenciaDeTeste('P'), 'teste')[$campo])->toBe($lido);
})->with([
    'tamanho 30'              => ['tamanho_do_painel', 30, 30],
    'tamanho 70'              => ['tamanho_do_painel', 70, 70],
    'tamanho vindo do select' => ['tamanho_do_painel', '45', 45],
    'destaque logo'           => ['destaque', 'logo', 'logo'],
    'ilustração rede'         => ['ilustracao', 'rede', 'rede'],
    'movimento desligado'     => ['movimento', false, false],
    'cor da ilustração'       => ['cor_da_ilustracao', '#ABCDEF', '#abcdef'],
])->group('kit');

it('[CT-45] caminho de imagem fora do diretório das telas de acesso é descartado', function (mixed $caminho, ?string $lido): void {
    expect(AparenciaDasTelasDeAcesso::normalizar(['imagem_do_destaque' => $caminho] + aparenciaDeTeste('P'), 'teste')['imagem_do_destaque'])
        ->toBe($lido);
})->with([
    'válido'                 => ['telas-de-acesso/abc.png', 'telas-de-acesso/abc.png'],
    'estado do FileUpload'   => [['8f1c' => 'telas-de-acesso/abc.webp'], 'telas-de-acesso/abc.webp'],
    'subindo diretório'      => ['telas-de-acesso/../.env', null],
    'outro diretório'        => ['kit/logo.png', null],
    'svg'                    => ['telas-de-acesso/x.svg', null],
    'absoluto'               => ['/etc/passwd', null],
    'URL'                    => ['https://exemplo.test/x.png', null],
    'subpasta'               => ['telas-de-acesso/a/b.png', null],
    'quebra de linha no fim' => ["telas-de-acesso/abc.png\n", null],
    'dois arquivos'          => [['a' => 'telas-de-acesso/a.png', 'b' => 'telas-de-acesso/b.png'], null],
])->group('kit');

it('[CT-46] aparência gravada antes do adendo continua igual', function (): void {
    $antiga = [
        'layout' => 'painel', 'lado_do_formulario' => 'direita', 'fundo' => 'cor', 'cor' => '#123456',
        'frase'  => 'ANTIGA', 'apoio' => 'APOIO', 'logo_no_painel' => true, 'alternador_de_tema' => true,
    ];

    $html   = loginDoAdendo($antiga);
    $arvore = arvoreDoHtml($html);

    expect(configDoLoginDoAdendo()->mediaSize)->toBe('50%')
        ->and($arvore->query('//*[@data-kit-destaque]')->length)->toBe(0)
        ->and($arvore->query('//*[@data-kit-ilustracao]')->length)->toBe(0)
        ->and($arvore->query('//*[@data-kit-pausa]')->length)->toBe(0)
        ->and(textoDoPainelDeMarca($html, 'frase'))->toBe('ANTIGA');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R19 — disposição (P-19)
|--------------------------------------------------------------------------
*/

it('[CT-47] a largura escolhida vira o mediaSize do pacote, dos dois lados', function (int $tamanho, string $lado, string $classe): void {
    definirAparenciaGlobal(['tamanho_do_painel' => $tamanho, 'lado_do_formulario' => $lado] + aparenciaDeTeste('P'));

    $config = configDoLoginDoAdendo();
    $html   = $this->get('/admin/login')->assertOk()->getContent();

    expect($config->mediaSize)->toBe($tamanho.'%')
        ->and($html)->toContain('--media-size: '.$tamanho.'%')
        ->and($html)->toContain($classe);
})->with([
    'mínimo, formulário à direita'  => [30, 'direita', 'media-left'],
    'máximo, formulário à esquerda' => [70, 'esquerda', 'media-right'],
    'meio'                          => [55, 'direita', 'media-left'],
])->group('kit');

it('[CT-54] a largura, o destaque e a rede valem nas outras telas de acesso', function (string $caminho): void {
    config(['kit.registro.habilitado' => true]);
    $caminhoDaImagem = imagemDoDestaqueNoDisco();
    definirAparenciaGlobal([
        'tamanho_do_painel' => 70, 'destaque' => 'imagem', 'imagem_do_destaque' => $caminhoDaImagem, 'ilustracao' => 'rede',
    ] + aparenciaDeTeste('P'));

    $html   = $this->get($caminho)->assertOk()->getContent();
    $arvore = arvoreDoHtml($html);

    expect($html)->toContain('--media-size: 70%')
        ->and($arvore->query('//img[@data-kit-destaque="imagem"]')->length)->toBe(1)
        ->and($arvore->query('//svg[@data-kit-ilustracao="rede"]')->length)->toBe(1);
})->with(['recuperar senha' => '/admin/password-reset/request', 'cadastro' => '/app/register'])->group('kit');

it('[CT-47] o clássico não recebe a largura do painel', function (): void {
    $this->get('/admin/login')->assertOk();
    $antes = configDoLoginDoAdendo();

    definirAparenciaGlobal(['layout' => 'classico', 'tamanho_do_painel' => 30]);

    expect(configDoLoginDoAdendo())->toEqual($antes);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R20 — destaque central (P-20)
|--------------------------------------------------------------------------
*/

it('[CT-48] destaque "imagem" mostra a imagem enviada, no tamanho escolhido', function (string $tamanho): void {
    $caminho = imagemDoDestaqueNoDisco();

    $html  = loginDoAdendo(['destaque' => 'imagem', 'imagem_do_destaque' => $caminho, 'tamanho_do_destaque' => $tamanho] + aparenciaDeTeste('P'));
    $nos   = arvoreDoHtml($html)->query('//img[@data-kit-destaque="imagem"]');

    expect($nos->length)->toBe(1)
        ->and($nos->item(0)->getAttribute('src'))->toEndWith('/storage/'.$caminho)
        ->and($nos->item(0)->getAttribute('class'))->toContain('kit-painel-de-marca__destaque--'.$tamanho);
})->with(['pequeno', 'medio', 'grande'])->group('kit');

it('[CT-48] imagem gravada que sumiu do disco some do painel sem derrubar a tela', function (): void {
    Log::spy();
    Log::shouldReceive('channel')->andReturnSelf();

    $html = loginDoAdendo(['destaque' => 'imagem', 'imagem_do_destaque' => 'telas-de-acesso/sumiu.png'] + aparenciaDeTeste('P'));

    expect(arvoreDoHtml($html)->query('//*[@data-kit-destaque]')->length)->toBe(0)
        ->and(textoDoPainelDeMarca($html, 'frase'))->toBe('PADRAO');
    Log::shouldHaveReceived('warning')->withArgs(fn (string $mensagem): bool => str_contains($mensagem, 'Imagem do destaque ausente'));
})->group('kit');

it('[CT-55] imagem ausente gera um aviso, não um por visita à tela pública', function (): void {
    Log::spy();
    Log::shouldReceive('channel')->andReturnSelf();

    loginDoAdendo(['destaque' => 'imagem', 'imagem_do_destaque' => 'telas-de-acesso/sumiu.png'] + aparenciaDeTeste('P'));
    $this->get('/admin/login')->assertOk();

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $mensagem): bool => str_contains($mensagem, 'Imagem do destaque ausente'));
})->group('kit');

it('[CT-48] destaque "logo" mostra a logo; sem logo, o nome da aplicação', function (bool $comLogo): void {
    if (! $comLogo) {
        config()->set('kit.identidade.logo', null);
    }

    $html   = loginDoAdendo(['destaque' => 'logo', 'logo_no_painel' => false] + aparenciaDeTeste('P'));
    $arvore = arvoreDoHtml($html);

    if ($comLogo) {
        expect($arvore->query('//*[@data-kit-destaque="logo"]//img')->item(0)?->getAttribute('src'))->toContain('logo-teste.png');
    } else {
        expect(trim((string) $arvore->query('//*[@data-kit-destaque="nome"]')->item(0)?->textContent))->toBe(config('app.name'));
    }
})->with(['com logo' => true, 'sem logo' => false])->group('kit');

it('[CT-48] destaque "nenhum" não desenha nada no meio, mesmo com imagem gravada', function (): void {
    $html = loginDoAdendo(['destaque' => 'nenhum', 'imagem_do_destaque' => imagemDoDestaqueNoDisco()] + aparenciaDeTeste('P'));

    expect(arvoreDoHtml($html)->query('//*[@data-kit-destaque]')->length)->toBe(0);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R21 — ilustração (P-21)
|--------------------------------------------------------------------------
*/

it('[CT-49] a rede desenha os pontos e as ligações do desenho fixo', function (): void {
    $rede   = AparenciaDasTelasDeAcesso::rede();
    $arvore = arvoreDoHtml(loginDoAdendo(['ilustracao' => 'rede', 'movimento' => false] + aparenciaDeTeste('P')));
    $svg    = $arvore->query('//svg[@data-kit-ilustracao="rede"]');

    expect($svg->length)->toBe(1)
        ->and($svg->item(0)->getAttribute('aria-hidden'))->toBe('true')
        ->and($arvore->query('//svg[@data-kit-ilustracao="rede"]/circle')->length)->toBe(count($rede['pontos']))
        ->and($arvore->query('//svg[@data-kit-ilustracao="rede"]/line')->length)->toBe(count($rede['ligacoes']))
        ->and(count($rede['ligacoes']))->toBeGreaterThanOrEqual(count($rede['pontos']));
})->group('kit');

it('[CT-49] movimento ligado traz o componente e o botão de pausar; desligado, nenhum dos dois', function (bool $movimento): void {
    $arvore = arvoreDoHtml(loginDoAdendo(['ilustracao' => 'rede', 'movimento' => $movimento] + aparenciaDeTeste('P')));
    $painel = $arvore->query('//*[@data-kit-aparencia="painel"]')->item(0);

    expect($painel->hasAttribute('x-data'))->toBe($movimento)
        ->and($arvore->query('//button[@data-kit-pausa]')->length)->toBe($movimento ? 1 : 0)
        ->and($arvore->query('//svg[@data-kit-movimento]')->length)->toBe($movimento ? 1 : 0);

    if ($movimento) {
        // Nasce escondido: sem JavaScript, ou com "reduzir movimento", o botão não aparece (P-21).
        expect($arvore->query('//button[@data-kit-pausa]')->item(0)->hasAttribute('hidden'))->toBeTrue();
    }
})->with(['ligado' => true, 'desligado' => false])->group('kit');

it('[CT-49] cor própria da ilustração vai para o SVG; em branco, nenhuma cor própria', function (?string $cor): void {
    $arvore = arvoreDoHtml(loginDoAdendo(['ilustracao' => 'rede', 'cor_da_ilustracao' => $cor] + aparenciaDeTeste('P')));
    $estilo = $arvore->query('//svg[@data-kit-ilustracao="rede"]')->item(0)->getAttribute('style');

    expect($estilo)->toBe($cor === null ? '' : 'color: '.$cor);
})->with(['própria' => '#ffcc00', 'em branco' => null])->group('kit');

it('[CT-49] ilustração "nenhuma" não desenha a rede', function (): void {
    $arvore = arvoreDoHtml(loginDoAdendo(['ilustracao' => 'nenhuma', 'movimento' => true] + aparenciaDeTeste('P')));

    expect($arvore->query('//*[@data-kit-ilustracao]')->length)->toBe(0)
        ->and($arvore->query('//*[@data-kit-pausa]')->length)->toBe(0);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R22 — o componente não entrega o que a tela não mostra (P-16 estendida)
|--------------------------------------------------------------------------
*/

it('[CT-50] chamada direta ao destaque devolve só o que a tela mostra', function (string $caso, ?string $tipo): void {
    $caminho = imagemDoDestaqueNoDisco();

    definirAparenciaGlobal(match ($caso) {
        'clássico'        => ['layout' => 'classico', 'destaque' => 'imagem', 'imagem_do_destaque' => $caminho],
        'painel sem nada' => ['destaque' => 'nenhum', 'imagem_do_destaque' => $caminho] + aparenciaDeTeste('P'),
        'painel imagem'   => ['destaque' => 'imagem', 'imagem_do_destaque' => $caminho] + aparenciaDeTeste('P'),
        'painel logo'     => ['destaque' => 'logo'] + aparenciaDeTeste('P'),
    });
    $this->get('/admin/login')->assertOk();

    expect(Livewire::test(TelaLogin::class)->instance()->destaqueDoPainelDeMarca()['tipo'] ?? null)->toBe($tipo);
})->with([
    ['clássico', null],
    ['painel sem nada', null],
    ['painel imagem', 'imagem'],
    ['painel logo', 'logo'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R23 — editor e preview (P-08, P-20)
|--------------------------------------------------------------------------
*/

it('[CT-51] o editor grava os campos novos e o upload no diretório das telas de acesso', function (): void {
    Storage::fake('public');
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->fillForm(['aparencia_das_telas' => [
        'tamanho_do_painel'  => 35, 'destaque' => 'imagem', 'tamanho_do_destaque' => 'grande',
        'ilustracao'         => 'rede', 'movimento' => false, 'cor_da_ilustracao' => '#00ff88',
        'imagem_do_destaque' => UploadedFile::fake()->image('marca.png', 200, 120),
    ] + aparenciaDeTeste('P')])
        ->call('save')
        ->assertHasNoFormErrors();

    $gravada = configuracaoGravada('aparencia_das_telas');

    expect($gravada['tamanho_do_painel'])->toBe(35)
        ->and($gravada['destaque'])->toBe('imagem')
        ->and($gravada['tamanho_do_destaque'])->toBe('grande')
        ->and($gravada['ilustracao'])->toBe('rede')
        ->and($gravada['movimento'])->toBeFalse()
        ->and($gravada['cor_da_ilustracao'])->toBe('#00ff88')
        ->and($gravada['imagem_do_destaque'])->toStartWith('telas-de-acesso/');
    Storage::disk('public')->assertExists($gravada['imagem_do_destaque']);
})->group('kit');

it('[CT-51] o editor recusa SVG no upload do destaque', function (): void {
    Storage::fake('public');
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->fillForm(['aparencia_das_telas' => [
        'destaque'           => 'imagem',
        'imagem_do_destaque' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    ] + aparenciaDeTeste('P')])
        ->call('save')
        ->assertHasFormErrors(['aparencia_das_telas.imagem_do_destaque']);

    expect(configuracaoGravada('aparencia_das_telas'))->toBeNull();
})->group('kit');

it('[CT-52] o preview mostra a imagem enviada e ainda não salva', function (): void {
    Storage::fake('public');
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->fillForm(['aparencia_das_telas' => [
        'destaque'           => 'imagem',
        'imagem_do_destaque' => UploadedFile::fake()->image('ainda-nao-salva.png', 200, 120),
    ] + aparenciaDeTeste('P')]);

    $id   = rascunhoDoEditor($tela);
    $html = $this->get(urlDoPreviewDe($id))->assertOk()->getContent();
    $src  = arvoreDoHtml($html)->query('//img[@data-kit-destaque="imagem"]')->item(0)?->getAttribute('src');

    expect($src)->toContain('livewire')
        ->and(configuracaoGravada('aparencia_das_telas'))->toBeNull()
        ->and(Storage::disk('public')->allFiles('telas-de-acesso'))->toBe([]);
})->group('kit');

it('[CT-52] rascunho com SVG não salvo não vira imagem temporária', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'telas-de-acesso')->refresh();
    $tela->fillForm(['aparencia_das_telas' => [
        'destaque'           => 'imagem',
        'imagem_do_destaque' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
    ] + aparenciaDeTeste('P')]);

    $id = rascunhoDoEditor($tela);

    expect(Cache::get('aparencia-preview:'.auth()->id().':'.$id)['imagem_temporaria'] ?? null)->toBeNull();
})->group('kit');

it('[CT-56] a aparência que o componente devolve não traz imagem nem cor que a tela não usa', function (): void {
    definirAparenciaGlobal([
        'destaque'   => 'nenhum', 'imagem_do_destaque' => imagemDoDestaqueNoDisco(),
        'ilustracao' => 'nenhuma', 'cor_da_ilustracao' => '#00ff88',
    ] + aparenciaDeTeste('P'));
    $this->get('/admin/login')->assertOk();

    $aparencia = Livewire::test(TelaLogin::class)->instance()->aparenciaDaTela();

    expect($aparencia['imagem_do_destaque'])->toBeNull()
        ->and($aparencia['cor_da_ilustracao'])->toBeNull()
        // O gravado continua inteiro (ADR-02): só a saída pública é que é reduzida.
        ->and(configuracaoGravada('aparencia_das_telas')['cor_da_ilustracao'])->toBe('#00ff88');
})->group('kit');
