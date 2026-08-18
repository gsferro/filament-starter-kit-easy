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
 * Lote com `visit([...])`: as 55 telas em 2 cenários (um deles com dataset de 3 painéis).
 * Escrever um cenário por tela custaria 55 boots de navegador para provar a mesma coisa.
 *
 * O inventário das rotas vive AQUI e em mais nenhum lugar. A wiki que especificou estes
 * cenários listava as mesmas 52 rotas numa terceira cópia, e foi essa duplicação que
 * produziu a única divergência de aritmética da rodada — ver D-02 no arquivo 05 da wiki.
 */
beforeEach(function (): void {
    // Mesmo par de seeders de tests/Kit/PaineisTest.php:20-22. Sem helper novo de
    // propósito: papel sem a matriz de permissões do Shield abre painel e não abre tela.
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-B01, CT-B02 e CT-B03 — as telas autenticadas de cada painel.
 *
 * Um dataset e não três cenários: o corpo era idêntico nos três, e o nome do painel
 * continua aparecendo na saída do Pest. O que era específico de cada um virou comentário
 * dentro do array, junto da rota que o motiva.
 *
 * `master_global` porque ele vence pelo `Gate::before` sem depender da matriz de
 * permissões: é o único papel capaz de abrir as 52 telas. O recorte por papel é assunto
 * do CT-B05, não deste.
 */
it('abre as telas autenticadas do painel', function (array $rotas): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit($rotas)->assertNoJavaScriptErrors();
})->with([
    // O painel /app é o único dos três que não tinha nenhuma cobertura de tela: o
    // PaginasInfraTest cobria 15 rotas de /infra e 3 de /admin, e o painel de negócio
    // tinha só o `GET /app` genérico do PaineisTest.
    'app' => [[
        '/app',
        '/app/meu-perfil',
        '/app/two-factor-authentication',
        '/app/convites',
        '/app/convites/create',
        '/app/convites-recebidos',
        '/app/users',
        '/app/users/create',
        /*
         * `/app/projetos` saiu daqui: o resource de exemplo só existe com a demo
         * ligada (`config('kit.demo')` + tenancy), e esta suíte roda single-tenant.
         *
         * ⚠️ As rotas `/app/convites` e `/app/users` acima estão na MESMA situação —
         * `UserResource` e `ConviteResource` do painel de negócio se escondem sem
         * tenancy, então aqui elas respondem 403. Elas continuam na lista porque
         * `assertNoJavaScriptErrors()` passa numa página de 403 (ela não tem erro de
         * JS nenhum), o que significa que estas três linhas nunca provaram nada
         * sobre as telas. Mover para `tests/BrowserTenancy` é o conserto; fica
         * registrado em vez de removido em silêncio.
         */
    ]],
    'admin' => [[
        '/admin',
        '/admin/meu-perfil',
        '/admin/two-factor-authentication',
        '/admin/users',
        '/admin/users/create',
        // Shield e onboarding são Resources de plugin, e incompatibilidade de versão de
        // plugin aparece na primeira visita, não no boot.
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
    ]],
    // No /infra quase toda tela vem de um pacote de terceiro.
    'infra' => [[
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
        // Roda com PULSE_ENABLED=false (phpunit.xml): a tela precisa abrir mesmo assim,
        // porque Pulse desligado não é Pulse quebrado.
        '/infra/pulse',
        '/infra/command-center/commands',
        '/infra/command-center/history',
        '/infra/command-center/definitions',
        '/infra/command-center/definitions/create',
        /*
         * As três telas da 0.17.0.
         *
         * A de exceções é a que mais precisa estar aqui, e não pelo motivo óbvio: o
         * plugin dela resolve o painel CORRENTE, e um registro errado não quebra esta
         * tela — quebra a aplicação inteira, em todo request e em todo comando artisan.
         * Um smoke em navegador é justamente o que pega isso de um jeito que nenhum
         * `$this->get()` isolado pegaria.
         *
         * A Lixeira e a trilha de e-mail abrem VAZIAS numa instalação nova, e é assim
         * mesmo: o que se prova aqui é que a tela renderiza sem erro de JS, não que há
         * dado nela.
         */
        '/infra/exceptions',
        '/infra/mail-logs',
        '/infra/recycle-bin',
    ]],
]);

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
