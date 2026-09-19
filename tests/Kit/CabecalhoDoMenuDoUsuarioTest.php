<?php

use App\Support\Papeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O cabeçalho do menu do usuário: quem sou eu, aqui.
 *
 * O dropdown do canto superior direito respondia só "o que eu posso fazer" — Meu
 * perfil, Bloquear sessão, tema, sair. Numa instalação com três painéis, papéis por
 * painel e impersonação ligada, faltava a outra metade: com que identidade e com que
 * papel a sessão está aberta.
 *
 * São duas camadas, e este arquivo cobre as duas pelo caminho mais barato de cada uma:
 *
 *   1. `User::papelDoPainel()` — a REGRA. Testada direto no model, sem renderizar tela.
 *   2. O render hook nos três painéis — a PRESENÇA. Testada por request de página cheia,
 *      porque render hook só é emitido no layout, e teste de componente Livewire não
 *      passa por ele.
 *
 * O que este arquivo NÃO cobre, de propósito: o dropdown ABRIR ao clique. Isso é Alpine
 * executando, e só o navegador prova — está em `tests/Browser`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| A regra: User::papelDoPainel()
|--------------------------------------------------------------------------
*/

/**
 * O caso base, papel por papel: quem abre o painel é quem aparece no badge.
 *
 * A tabela é a do `PapeisSeeder`, e é ela que dá sentido ao teste — `admin` abre
 * `/admin`, `infra` abre `/infra` e `panel_user` abre `/app`.
 *
 * `admin_app` fica de fora daqui: o `PapeisSeeder` só o cria com tenancy ligada, então
 * nesta suíte ele não existe no banco. O caso dele está em `tests/Tenancy`.
 */
it('devolve o papel que abre o painel', function (string $papel, string $painel): void {
    expect(usuarioCom($papel)->papelDoPainel($painel))->toBe($papel);
})->with([
    'admin no /admin'    => ['admin', 'admin'],
    'infra no /infra'    => ['infra', 'infra'],
    'panel_user no /app' => ['panel_user', 'app'],
])->group('kit');

/**
 * Papel que não abre o painel não vira badge.
 *
 * É o mesmo fato de `canAccessPanel()` visto pelo outro lado: o `infra` não entra no
 * `/admin`, então não há papel dele para mostrar lá. Um badge aqui seria pior que
 * nenhum — afirmaria um acesso que não existe.
 */
it('não devolve papel de outro painel', function (string $papel, string $painel): void {
    expect(usuarioCom($papel)->papelDoPainel($painel))->toBeNull();
})->with([
    'admin no /infra'      => ['admin', 'infra'],
    'infra no /admin'      => ['infra', 'admin'],
    'panel_user no /admin' => ['panel_user', 'admin'],
    'admin no /app'        => ['admin', 'app'],
])->group('kit');

it('não devolve papel para quem não tem papel nenhum', function (string $painel): void {
    expect(usuarioCom(null)->papelDoPainel($painel))->toBeNull();
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * O master_global é resolvido ANTES da consulta, e é a parte que mais fácil se quebra
 * numa refatoração.
 *
 * `roles.painel` dele é NULO — ele não entra pela coluna, entra pelo `Gate::before`.
 * Uma implementação que só consultasse `painel = $painel` devolveria null justamente
 * para quem tem acesso a tudo, e o badge sumiria no caso mais visível do kit.
 */
it('devolve master_global em qualquer painel, mesmo com roles.painel nulo', function (string $painel): void {
    expect(usuarioCom('master_global')->papelDoPainel($painel))->toBe('master_global');
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * Fronteira: o método é EXIBIÇÃO, e não pode virar autorização por acidente.
 *
 * A prova é que ele responde para painel inexistente do mesmo jeito que para painel
 * que o usuário não abre — com null, sem erro. Quem nega acesso é `canAccessPanel()`,
 * que loga a negativa; este aqui apenas não tem o que desenhar.
 */
it('devolve null para painel que não existe, sem estourar', function (): void {
    expect(usuarioCom('admin')->papelDoPainel('painel-que-nao-existe'))->toBeNull();
})->group('kit');

/*
|--------------------------------------------------------------------------
| A presença: o render hook nos três painéis
|--------------------------------------------------------------------------
*/

/**
 * RQ-03 em uma linha: o cabeçalho está nos TRÊS painéis, não só no que foi lembrado.
 *
 * A âncora é `data-user-menu-header` e não o nome do usuário: o nome também aparece no
 * `AccountWidget` do dashboard, na mesma página, então um `assertSee` dele passaria com
 * o hook removido. Ver ADR-06.
 */
it('injeta o cabeçalho no menu do usuário dos três painéis', function (string $painel): void {
    $this->actingAs(usuarioCom('master_global'))
        ->get("/{$painel}")
        ->assertSuccessful()
        ->assertSee('data-user-menu-header', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

it('mostra o nome e o e-mail de quem está autenticado', function (): void {
    $user = usuarioCom('master_global');

    $this->actingAs($user)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee($user->name)
        ->assertSee($user->email);
})->group('kit');

/**
 * O badge sai de `Papeis::rotulo()`, e não de texto escrito na view.
 *
 * `master_global` renderizado como "master_global" seria chave vazando para a tela — o
 * problema que a classe `Papeis` existe para resolver, e que já se repete em sete
 * lugares no kit. Um oitavo, escrito à mão dentro do Blade, seria o que diverge.
 */
it('desenha o badge com o rótulo do papel, não com a chave', function (string $painel): void {
    $this->actingAs(usuarioCom('master_global'))
        ->get("/{$painel}")
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo('master_global'))
        ->assertDontSee('>master_global<', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * O badge é do painel CORRENTE, não "um papel qualquer do usuário".
 *
 * `panel_user` no `/app` mostra "Painel App". Se a view lesse o primeiro papel da
 * relação em vez de filtrar pelo painel, um usuário com papéis em painéis diferentes
 * veria o rótulo errado — e é o tipo de defeito que passa despercebido, porque a tela
 * continua correta para quem só tem um papel.
 */
it('desenha o papel do painel corrente', function (): void {
    $this->actingAs(usuarioCom('panel_user'))
        ->get('/app')
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo('panel_user'));
})->group('kit');

/**
 * O título do badge diz qual painel o papel abre — a mesma frase da tabela de papéis.
 *
 * Não é enfeite: é o que distingue, ao passar o mouse, "sou admin **deste** painel" de
 * "tenho um papel chamado admin em algum lugar".
 */
it('põe no título do badge o painel que o papel abre', function (): void {
    $this->actingAs(usuarioCom('admin'))
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee(Papeis::rotuloDoPainel('admin'));
})->group('kit');

/**
 * O cabeçalho não aparece para quem não está autenticado.
 *
 * A tela de login é servida pelo mesmo painel, e o hook do user menu não é emitido lá —
 * mas a view tem guarda própria para usuário nulo, e é essa guarda que este caso fixa.
 * Sem ela, uma futura tela de painel sem sessão quebraria na renderização, não no
 * acesso.
 */
it('não renderiza o cabeçalho na tela de login', function (string $painel): void {
    $this->get("/{$painel}/login")
        ->assertSuccessful()
        ->assertDontSee('data-user-menu-header', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/*
|--------------------------------------------------------------------------
| A guarda do fato que o comentário dos providers afirma
|--------------------------------------------------------------------------
*/

/**
 * Os dois hooks do menu do usuário renderizam em lugares DIFERENTES, e o kit depende disso.
 *
 * Até a v0.35.0 os três `PanelProvider` e a blade deste cabeçalho afirmavam que
 * `USER_MENU_BEFORE` "renderiza DENTRO do dropdown do usuário". Ele não renderiza: é emitido
 * antes e fora do `<x-filament::dropdown>`. A escolha de `GLOBAL_SEARCH_BEFORE` para o gatilho ⌘K
 * continuava certa — por outro motivo, a posição exata do campo de busca. É o padrão que
 * `.ai/rules/specs.md` nomeia: conclusão certa por motivo errado, e por isso invisível.
 *
 * Este caso é o que impede a afirmação de envelhecer de novo: ele lê a blade do vendor INSTALADO
 * e confere a ordem real. Se um upgrade do Filament mover qualquer um dos dois, ele fica vermelho
 * e o comentário é reescrito junto — em vez de continuar afirmando algo que deixou de valer.
 */
it('[CT-35] mantem USER_MENU_BEFORE fora do dropdown e USER_MENU_PROFILE_BEFORE dentro', function (): void {
    $blade = (string) file_get_contents(
        base_path('vendor/filament/filament/resources/views/components/user-menu.blade.php'),
    );

    $antesDoMenu        = strpos($blade, 'PanelsRenderHook::USER_MENU_BEFORE');
    $aberturaDoDropdown = strpos($blade, '<x-filament::dropdown');
    $dentroDoMenu       = strpos($blade, 'PanelsRenderHook::USER_MENU_PROFILE_BEFORE');

    expect($antesDoMenu)->not->toBeFalse('o vendor não emite mais USER_MENU_BEFORE')
        ->and($aberturaDoDropdown)->not->toBeFalse()
        ->and($dentroDoMenu)->not->toBeFalse()
        ->and($antesDoMenu)->toBeLessThan(
            $aberturaDoDropdown,
            'USER_MENU_BEFORE passou a ser emitido DENTRO do dropdown — o comentário dos três PanelProvider precisa ser reescrito',
        )
        ->and($dentroDoMenu)->toBeGreaterThan(
            $aberturaDoDropdown,
            'USER_MENU_PROFILE_BEFORE saiu de dentro do dropdown — o cabeçalho do menu do usuário mudou de lugar',
        );

    /*
     * A terceira metade do `Então` de CT-35, e ela é asserção de PRESENÇA, não de ausência.
     *
     * A versão anterior do cenário afirmava que "nenhum arquivo do kit diz que o hook renderiza
     * dentro do dropdown" — ausência de texto livre, que a mesma afirmação errada reescrita com
     * outras palavras satisfaz. Exigir a CITAÇÃO da view é o que `.ai/rules/testes.md` recomenda
     * quando o objeto é o texto de um comentário, e é o que mata M51: a correção que troca o texto
     * em três dos quatro arquivos e esquece o quarto.
     *
     * Sem NÚMERO de linha de propósito: num kit obrigado por RQ-14/RQ-15 a aceitar toda
     * atualização da série do Filament, casar `:38` é ruído garantido no próximo upgrade. Quem
     * mede a posição é a metade de cima deste caso, contra o vendor instalado.
     */
    $justificam = [
        base_path('app/Providers/Filament/AdminPanelProvider.php'),
        base_path('app/Providers/Filament/AppPanelProvider.php'),
        base_path('app/Providers/Filament/InfraPanelProvider.php'),
        resource_path('views/filament/user-menu-header.blade.php'),
    ];

    foreach ($justificam as $arquivo) {
        expect((string) file_get_contents($arquivo))->toContain('user-menu.blade.php');
    }
})->group('kit');
