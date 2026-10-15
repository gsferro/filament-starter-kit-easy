<?php

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\DashboardClassico;
use App\Models\Convite;
use App\Support\TelaDeAutenticacao;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use HoceineEl\QuickActionDock\Support\DockContext;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('[CT-04] a tela entrega somente os componentes permitidos com sessão', function (string $painel, string $tela, bool $positivo, bool $condicional): void {
    $mestre    = usuarioDoKit('master_global');
    $sentinela = 'Sentinela da pilha 7f3c';
    conversaPara($mestre, $sentinela);
    ligarDock(['admin', 'app', 'infra']);
    $this->actingAs($mestre);
    $url = match ($tela) {
        'comum'    => '/'.$painel,
        'bloqueio' => route('lockscreen.'.$painel.'.page'),
        '2fa'      => route('filament.'.$painel.'.auth.two-factor'),
        'convite'  => route('filament.app.auth.register', ['token' => Convite::factory()->create([
            'email'   => 'convidado@example.com',
            'role_id' => papelDoKit('panel_user')->getKey(),
        ])->enviar()]),
        'login-painel'                                 => route('filament.admin.auth.login'),
        'login', 'esqueci-minha-senha', 'login.painel' => route($tela),
        'verificacao'                                  => '/app',
    };
    if ($tela === 'bloqueio') {
        $this->post(route('lockscreen.'.$painel.'.lock-session'))->assertRedirect();
    }
    if (in_array($tela, ['login', 'esqueci-minha-senha', 'login.painel'], true)) {
        ligarLoginUnificado(true);
    }
    if ($tela === 'verificacao') {
        config(['kit.registro.verificar_email' => true]);
        $mestre->forceFill(['email_verified_at' => null])->save();
        $this->followingRedirects();
    }

    $resposta = $this->get($url);

    if ($condicional && $resposta->isRedirect()) {
        $resposta->assertRedirect(match ($tela) {
            'login-painel' => '/admin',
            'convite'      => '/app',
            default        => route('login.painel'),
        });
        fwrite(STDOUT, "\nCT-04 {$tela}: {$resposta->status()} -> {$resposta->headers->get('Location')}\n");

        return;
    }
    if (! $resposta->isOk()) {
        fwrite(STDOUT, "\nCT-04 {$tela}: {$resposta->status()} -> {$resposta->headers->get('Location')}\n");
    }
    $resposta->assertOk();
    if ($condicional) {
        fwrite(STDOUT, "\nCT-04 {$tela}: 200\n");
    }
    $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $resposta->getContent());
    $this->assertStringContainsString('data-voltar-ao-topo', $html, $tela.': render hook executado');
    foreach (['wire:name="quick-action-dock"' => $positivo,
        'data-assistente-chat'                => $positivo && $painel === 'app',
        'wire:name="assistente-chat-widget"'  => $positivo && $painel === 'app',
        $sentinela                            => $positivo && $painel === 'app'] as $agulha => $presente) {
        if ($presente) {
            $this->assertStringContainsString($agulha, $html, $tela.': '.$agulha);
        } else {
            $this->assertStringNotContainsString($agulha, $html, $tela.': '.$agulha);
        }
    }
})->with([
    'bloqueio admin'             => ['admin', 'bloqueio', false, false],
    'bloqueio app'               => ['app', 'bloqueio', false, false],
    'bloqueio infra'             => ['infra', 'bloqueio', false, false],
    '2FA admin'                  => ['admin', '2fa', false, false],
    '2FA app'                    => ['app', '2fa', false, false],
    'convite app'                => ['app', 'convite', false, true],
    'escolha de painel'          => ['', 'login.painel', false, false],
    'login admin com sessão'     => ['admin', 'login-painel', false, true],
    'login unificado com sessão' => ['', 'login', false, true],
    'recuperação com sessão'     => ['', 'esqueci-minha-senha', false, true],
    'verificação com sessão'     => ['app', 'verificacao', false, true],
    'controle app'               => ['app', 'comum', true, false],
    'controle admin'             => ['admin', 'comum', true, false],
]);

it('[CT-09] destravar devolve dock e chat ao app', function (): void {
    $mestre = usuarioDoKit('master_global');
    conversaPara($mestre, 'Sentinela da pilha 7f3c');
    ligarDock(['app']);
    $this->actingAs($mestre)->get('/app')->assertOk();
    $this->post(route('lockscreen.app.lock-session'))->assertRedirect();
    app()->forgetInstance(DockContext::class);
    $bloqueada = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $this->get(route('lockscreen.app.page'))->assertOk()->getContent());
    foreach (['wire:name="quick-action-dock"', 'data-assistente-chat', 'wire:name="assistente-chat-widget"', 'Sentinela da pilha 7f3c'] as $agulha) {
        $this->assertStringNotContainsString($agulha, $bloqueada, $agulha);
    }

    Livewire::test(TelaBloqueio::class)->set('data.password', 'password')->call('authenticate')->assertHasNoErrors();

    app()->forgetInstance(DockContext::class);
    $html = $this->get('/app')->assertOk()->getContent();
    foreach (['wire:name="quick-action-dock"', 'data-assistente-chat', 'Sentinela da pilha 7f3c'] as $agulha) {
        $this->assertStringContainsString($agulha, $html, $agulha);
    }
});

it('[CT-11] round-trip do dock mantém grupo e favorito numa página comum', function (): void {
    config(['kit.hub' => true]);
    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    concederDock('panel_user');
    ligarDock(['app']);
    $chave = chaveDeAtalho('app', DashboardClassico::class);
    gravarAtalhos($ana, [$chave]);
    $this->actingAs($ana)->get('/app')->assertOk();
    Filament::setCurrentPanel('app');
    $dock  = dockNaPagina('app');
    $antes = array_map(static fn (array $grupo): array => [$grupo['label'], array_keys($grupo['actions'])], $dock->instance()->getDockGroups());
    expect(array_merge(...array_column($antes, 1)))->toContain('atalho_0');

    $dock->mountAction('gerenciarAtalhos')->assertHasNoErrors();

    $depois = array_map(static fn (array $grupo): array => [$grupo['label'], array_keys($grupo['actions'])], $dock->instance()->getDockGroups());
    expect($depois)->toBe($antes);
    $dock->assertSee(DashboardClassico::getUrl(panel: 'app'), false);
});

it('[CT-12] rota sem nome não é tela de autenticação', function (?string $rota, bool $esperado): void {
    expect(TelaDeAutenticacao::rota($rota))->toBe($esperado);
})->with([
    'nula'                    => [null, false],
    'vazia'                   => ['', false],
    'página comum'            => ['filament.app.pages.dashboard', false],
    'bloqueio (controle)'     => ['lockscreen.app.page', true],
]);
