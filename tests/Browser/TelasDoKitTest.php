<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Smoke em navegador real das telas alcançáveis por URL fixa nos três painéis.
 *
 * O que isto pega e o `$this->get()` de tests/Kit não pega: um painel Filament é
 * Livewire + Alpine, então o HTML pode vir íntegro com status 200 e a tela estar
 * inutilizável porque um `x-on:click` estourou, porque um asset do Vite não subiu ou
 * porque um componente de plugin registrou erro no console. Nenhuma dessas três falhas
 * move o status HTTP.
 *
 * Um cenário por painel, com `visit([...])` em lote: 48 telas em 4 cenários. Escrever
 * um cenário por tela custaria 48 boots de navegador para provar a mesma coisa.
 */
beforeEach(function (): void {
    // Mesmo par de seeders de tests/Kit/PaineisTest.php:20-22. Sem helper novo de
    // propósito: papel sem a matriz de permissões do Shield abre painel e não abre tela.
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-B01 — painel `/app`, o único dos três que não tinha nenhuma cobertura de tela.
 *
 * `master_global` porque ele vence pelo `Gate::before` sem depender da matriz de
 * permissões: é o único papel capaz de abrir as 48 telas. O recorte por papel é assunto
 * do CT-B05, não deste.
 */
it('abre as telas do painel app', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit([
        '/app',
        '/app/meu-perfil',
        '/app/two-factor-authentication',
        '/app/convites',
        '/app/convites/create',
        '/app/convites-recebidos',
        '/app/projetos',
        '/app/users',
        '/app/users/create',
    ])->assertNoJavaScriptErrors();
});

/**
 * CT-B02 — painel `/admin`.
 *
 * Cinco dos Resources aqui são de plugin (Shield, onboarding), e incompatibilidade de
 * versão de plugin aparece na primeira visita, não no boot.
 */
it('abre as telas do painel admin', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit([
        '/admin',
        '/admin/meu-perfil',
        '/admin/two-factor-authentication',
        '/admin/users',
        '/admin/users/create',
        '/admin/shield/roles',
        '/admin/shield/roles/create',
        '/admin/convites',
        '/admin/convites/create',
        '/admin/organizacoes',
        '/admin/organizacoes/create',
        '/admin/agentes-ia',
        '/admin/agentes-ia/create',
        '/admin/onboarding-flows',
        '/admin/onboarding-flows/create',
        '/admin/onboarding-conditions',
        '/admin/onboarding-conditions/create',
    ])->assertNoJavaScriptErrors();
});

/**
 * CT-B03 — painel `/infra`, onde quase toda tela vem de um pacote de terceiro.
 *
 * `/infra/pulse` roda com `PULSE_ENABLED=false` (phpunit.xml): a tela precisa abrir
 * mesmo assim, porque Pulse desligado não é Pulse quebrado.
 */
it('abre as telas do painel infra', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit([
        '/infra',
        '/infra/meu-perfil',
        '/infra/two-factor-authentication',
        '/infra/health-check-results',
        '/infra/backup-runs',
        '/infra/queue-monitors',
        '/infra/queue-monitors/failures',
        '/infra/queue-monitors/pending',
        '/infra/audits',
        '/infra/authentication-logs',
        '/infra/logs',
        '/infra/dependency-graph',
        '/infra/composer-release-packages',
        '/infra/execucoes-ia',
        '/infra/pulse',
        '/infra/command-center/commands',
        '/infra/command-center/history',
        '/infra/command-center/definitions',
        '/infra/command-center/definitions/create',
    ])->assertNoJavaScriptErrors();
});

/**
 * CT-B04 — as telas públicas, sem nenhum `actingAs()`.
 *
 * Sem autenticação é o ponto do cenário: visitar `/app/login` autenticado redireciona, e
 * o teste mediria o redirecionamento em vez da tela.
 *
 * Aqui vale `assertNoSmoke()` e não `assertNoJavaScriptErrors()`: estas são as telas de
 * autoria do kit (TelaLogin, RegistroPorConvite, Auth Designer), onde `console.log` é
 * sujeira própria e sai de graça no mesmo cenário. Nas telas de painel, cheias de
 * plugin, seria vermelho por dívida de terceiro.
 *
 * `/*​/screen/lock` fica fora: o Lockscreen exige sessão bloqueada, que é estado, não
 * rota pública.
 */
it('abre as telas publicas dos tres paineis', function (): void {
    visit([
        '/app/login',
        '/app/register',
        '/app/password-reset/request',
        '/admin/login',
        '/admin/password-reset/request',
        '/infra/login',
        '/infra/password-reset/request',
    ])->assertNoSmoke();
});
