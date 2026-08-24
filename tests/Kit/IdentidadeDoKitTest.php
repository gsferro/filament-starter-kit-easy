<?php

use App\Support\IdentidadeDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * Logo, favicon e arte de login: da configuração até o HTML dos painéis.
 *
 * IDs de CT em `wikis/specs/feat/settings-do-kit/settings-do-kit/04-casos-de-teste.md`.
 *
 * CT-26 afirma sobre o RESOLVEDOR; CT-35 e CT-35b afirmam sobre o HTML
 * renderizado, e existem por causa da rodada 2 da revisão adversarial: uma
 * implementação em que `IdentidadeDoKit` está perfeita e **nenhum painel a
 * consome** passava no conjunto inteiro. `brandLogo` ausente e arte literal nos
 * providers, com o resolvedor impecável e testado.
 */
beforeEach(function (): void {
    Storage::fake('public');
});

/**
 * CT-26 — a resolução do arquivo de identidade cobre as três partições.
 *
 * A partição do meio é a que justifica a classe existir: caminho declarado e
 * arquivo AUSENTE no disco. Sem a guarda, o `<link rel="icon">` de toda página
 * pediria um 404 — e acontece de verdade, quando alguém apaga
 * `storage/app/public/kit/` ou clona o repositório sem o `storage/` do colega.
 *
 * **Logo e favicon devolvem `null`; só a arte tem fallback.** A assimetria é
 * deliberada: com `null`, o Filament cai no brand em texto e no ícone dele, o que
 * é um default legítimo. Já `->media()` do Auth Designer recebendo `null` deixaria
 * a tela de autenticação sem imagem, que é regressão visível.
 */
it('resolve a logo para url publica quando o arquivo existe', function (): void {
    Storage::disk('public')->put('kit/logo.png', 'conteúdo');
    config(['kit.identidade.logo' => 'kit/logo.png']);

    expect(IdentidadeDoKit::logo())->toBe(Storage::disk('public')->url('kit/logo.png'));
})->group('kit');

it('devolve nulo para logo e favicon quando o arquivo declarado nao esta no disco', function (string $chave, string $metodo): void {
    config([$chave => 'kit/nao-existe.png']);

    $canal = espiarConfiguracoes();

    expect(IdentidadeDoKit::{$metodo}())->toBeNull();

    $canal->shouldHaveReceived('warning');
})->with([
    'logo'    => ['kit.identidade.logo', 'logo'],
    'favicon' => ['kit.identidade.favicon', 'favicon'],
])->group('kit');

it('devolve nulo para logo e favicon quando nada esta configurado', function (string $metodo): void {
    expect(IdentidadeDoKit::{$metodo}())->toBeNull();
})->with(['logo', 'favicon'])->group('kit');

it('resolve a arte do login para o arquivo enviado quando ele existe', function (): void {
    Storage::disk('public')->put('kit/arte.svg', '<svg/>');
    config(['kit.identidade.arte_do_login' => 'kit/arte.svg']);

    expect(IdentidadeDoKit::arteDoLogin())->toBe(Storage::disk('public')->url('kit/arte.svg'));
})->group('kit');

/**
 * A arte é a única das três com fallback, e nunca devolve `null`.
 *
 * As duas partições de fallback (não configurada e configurada-mas-ausente) caem
 * no mesmo lugar, e é o arquivo que os três painéis usavam literalmente antes
 * desta feature — o que faz uma instalação que nunca abriu a tela se comportar
 * exatamente como antes.
 */
it('cai na arte padrao do kit quando nao ha arquivo utilizavel', function (?string $configurado): void {
    config(['kit.identidade.arte_do_login' => $configurado]);

    expect(IdentidadeDoKit::arteDoLogin())->toBe(asset(IdentidadeDoKit::ARTE_PADRAO));
})->with([
    'não configurada'             => null,
    'configurada e sem o arquivo' => 'kit/apagada.svg',
])->group('kit');

/**
 * CT-35 — o nome, a logo e o favicon gravados aparecem no HTML dos três painéis.
 *
 * O `assertDontSee` do nome do AMBIENTE é a asserção discriminante, e sem ela o
 * caso não vale nada: com a marca literal no provider e o alinhamento funcionando,
 * o HTML conteria as DUAS coisas — a marca velha na topbar e o nome novo no
 * `<title>` —, e um `assertSee` do nome novo passaria com `brandName` congelado.
 * Foi exatamente o que a rodada 2 da revisão apontou.
 */
it('serve o nome, a logo e o favicon gravados nas telas dos tres paineis', function (string $rota): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Storage::disk('public')->put('kit/logo.png', 'conteúdo');
    Storage::disk('public')->put('kit/favicon.png', 'conteúdo');

    $doAmbiente = (string) config('app.name');

    config([
        'app.name'                  => 'Nome Do Banco',
        'kit.identidade.logo'       => 'kit/logo.png',
        'kit.identidade.favicon'    => 'kit/favicon.png',
    ]);

    $this->actingAs(usuarioDoKit('master_global'));

    $this->get($rota)
        ->assertOk()
        ->assertSee('Nome Do Banco')
        ->assertDontSee($doAmbiente)
        ->assertSee(Storage::disk('public')->url('kit/logo.png'), escape: false)
        ->assertSee(Storage::disk('public')->url('kit/favicon.png'), escape: false);
})->with([
    'painel de administração'  => '/admin',
    'painel de negócio'        => '/app',
    'painel de infraestrutura' => '/infra',
])->group('kit');

/**
 * CT-35b — a arte gravada veste as telas de login dos três painéis.
 *
 * O par `assertSee` da arte gravada + `assertDontSee` do caminho padrão é o que
 * distingue "a fiação existe" de "a tela serve as duas". Sem sessão de propósito:
 * a tela de login é anônima, e é justamente por isso que os arquivos de identidade
 * precisam ser públicos.
 */
it('veste as telas de login com a arte gravada', function (string $rota): void {
    Storage::disk('public')->put('kit/arte.svg', '<svg/>');

    config(['kit.identidade.arte_do_login' => 'kit/arte.svg']);

    $this->get($rota)
        ->assertOk()
        ->assertSee(Storage::disk('public')->url('kit/arte.svg'), escape: false)
        ->assertDontSee(IdentidadeDoKit::ARTE_PADRAO);
})->with([
    'login do /admin' => '/admin/login',
    'login do /app'   => '/app/login',
    'login do /infra' => '/infra/login',
])->group('kit');
