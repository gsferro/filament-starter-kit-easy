<?php

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Widgets\UltimosUsuariosCadastrados;
use App\Filament\Admin\Widgets\UsuariosPorPapel;
use App\Support\Papeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Livewire\Livewire;

/**
 * Como um papel é exibido — e por que a chave não muda.
 *
 * O nome do papel é identificador: vai em `assignRole()`, `hasRole()`, seeders e
 * policies. Trocá-lo para ficar bonito quebraria tudo isso de uma vez — e quando a
 * troca é mesmo necessária, ela vem com migration (ver
 * `rename_admin_organizacao_role`), porque o papel também vive no banco de quem já
 * instalou.
 *
 * O caminho barato é o outro: a chave fica e só a EXIBIÇÃO muda.
 */
it('exibe o papel em title case sem tocar na chave', function (string $chave, string $rotulo): void {
    expect(Papeis::rotulo($chave))->toBe($rotulo);
})->with([
    'admin' => ['admin', 'Admin'],
    'infra' => ['infra', 'Infra'],
    // Papel de fora do kit: deriva da chave, sem entrada em lugar nenhum.
    'papel do usuário' => ['gerente_de_contas', 'Gerente De Contas'],
])->group('kit');

/**
 * `Str::headline('panel_user')` devolve "Panel User" — inglês, e sem dizer o que o
 * papel faz. Quem o tem opera o painel de negócio.
 */
it('traduz o papel cujo Title Case sairia em inglês', function (): void {
    expect(Papeis::rotulo('panel_user'))->toBe('Painel App');
})->group('kit');

it('não inventa rótulo para papel ausente', function (?string $chave): void {
    expect(Papeis::rotulo($chave))->toBe('—');
})->with(['nulo' => null, 'vazio' => ''])->group('kit');

/**
 * `painel` é a coluna que DÁ o acesso (`User::canAccessPanel()` a lê). Exibi-la
 * como "app" fazia parecer categoria; o rótulo precisa dizer o que ela é.
 *
 * E vazio não é coringa: papel sem painel não abre painel nenhum — o rótulo diz
 * isso com todas as letras, em vez de um traço que o leitor interpreta.
 */
it('diz por extenso qual painel o papel abre', function (?string $painel, string $esperado): void {
    expect(Papeis::rotuloDoPainel($painel))->toBe($esperado);
})->with([
    'app'        => ['app', 'Acesso ao painel /app'],
    'admin'      => ['admin', 'Acesso ao painel /admin'],
    'infra'      => ['infra', 'Acesso ao painel /infra'],
    'sem painel' => [null, 'Não abre painel'],
])->group('kit');

/**
 * Os três papéis do kit cujo Title Case sairia em inglês ou sem acento.
 *
 * `panel_user` viraria "Panel User"; `admin_app`, "Admin App"; `master_global`,
 * "Master Global". Nenhum diz o que o papel faz, e dois deles nem são português.
 */
it('traduz os papéis do kit em vez de derivar da chave', function (string $chave, string $rotulo): void {
    expect(Papeis::rotulo($chave))->toBe($rotulo);
})->with([
    ['panel_user', 'Painel App'],
    ['admin_app', 'Administrador App'],
    ['master_global', 'Administrador Geral'],
])->group('kit');

/**
 * CT-09 — os dois widgets do dashboard /admin exibem o rótulo, não a chave.
 *
 * Este é o caso que fecha o "sempre" do requisito, e ele é sobre o ponto que NÃO parece tela
 * de papel: os widgets do painel de controle. Antes desta feature, o mesmo `panel_user`
 * aparecia como "Painel App" na tabela de papéis e como `panel_user` no badge de últimos
 * usuários e na barra de usuários por papel — duas telas acima e abaixo uma da outra.
 *
 * **Por componente, e não por `GET /admin`.** A primeira versão deste caso visitava o
 * dashboard e falhou: `CanBeLazy::$isLazy` é `true` por default no Filament
 * (`vendor/filament/support/src/Concerns/CanBeLazy.php:9`), então widget de painel renderiza
 * como placeholder Livewire e o conteúdo dele NÃO está no HTML da página. O caso media o
 * esqueleto — a mesma armadilha que `.ai/rules/testes.md` registra para tabela sem
 * `->loadTable()`. E note o modo de falhar: se o `assertDontSee` da chave fosse a única
 * asserção, o caso passaria **verde** medindo uma página sem widget nenhum.
 *
 * `noPainelBootado('admin')` porque `UltimosUsuariosCadastrados::urlDeEdicao()` chama
 * `UserResource::getUrl()`, que precisa do painel corrente.
 */
it('exibe o rotulo do papel nos widgets do dashboard admin', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    noPainelBootado('admin');

    $cliente = usuario('cliente@example.com');
    $cliente->assignRole('panel_user');
    $cliente->forceFill(['origem' => 'google'])->save();

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(UltimosUsuariosCadastrados::class)
        ->assertSee(Papeis::rotulo('panel_user'))
        ->assertDontSee('panel_user')
        // A porta de entrada ao lado do quando: o provedor por extenso, e o interno como "Interno".
        ->assertSee('Google')
        ->assertSee('Interno');

    Livewire::test(UsuariosPorPapel::class)
        ->assertSee(Papeis::rotulo('panel_user'))
        ->assertDontSee('panel_user');
})->group('kit');

/**
 * A lista de usuários do `/admin` mostra por qual porta cada conta entrou.
 *
 * Pedido do solicitante na validação real dos provedores (2026-08-26). O oráculo é o rótulo
 * por extenso — "Google", não `google` — e o "Interno" de quem foi criado pelo admin.
 */
it('mostra a origem da conta na lista de usuarios do admin', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    noPainelBootado('admin');

    usuario('social@example.com')->forceFill(['origem' => 'github'])->save();

    $this->actingAs(usuarioCom('master_global'));

    // `loadTable()`: a tabela do Filament carrega adiada; sem ele o HTML vem sem linhas.
    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertTableColumnExists('origem')
        ->assertSee('GitHub')
        ->assertSee('Interno');
})->group('kit');
