<?php

use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O login social num navegador de verdade: os quatro botões, e o interruptor que ABRE os campos.
 *
 * CT-B01 e CT-B02 de
 * `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/05-casos-de-teste-browser.md`.
 * Este arquivo era `LoginSocialGoogleTest.php` com um cenário; o nome mudou quando o segundo
 * provedor entrou, e o cenário do Google virou a primeira linha de um laço.
 *
 * Dois cenários, e o gate está escrito no `05`. Presença, ordem no DOM, `href`, escape do rodapé,
 * 404 por provedor, callback, gravação e "o segredo não está no HTML" se provam em HTTP ou em
 * componente Livewire, ~40× mais barato, e é o que `tests/Kit/LoginSocialProvedoresTest.php` e
 * `tests/Kit/SegredosDoSettingsTest.php` fazem.
 *
 * Sobram exatamente duas afirmações que só o navegador prova:
 *
 * 1. **`assertSee` fica VERDE com o botão presente no DOM e invisível.** Um `<svg>` de marca com
 *    dimensão zero, um contêiner colapsado pela CSS do Auth Designer ou um erro de JavaScript que
 *    interrompe a montagem antes do bloco dos botões deixam os quatro no HTML e fora da tela. O
 *    botão é a ÚNICA porta do login social: invisível, a feature está entregue e não existe.
 * 2. **"Abrir" os campos é um EVENTO.** RQ-05 diz que ligar a opção *abre* os campos de
 *    configuração. No teste de componente, o `fillForm()` muda o estado e a asserção reavalia o
 *    schema no mesmo ciclo — o cenário fica verde com o `->live()` do interruptor removido, e no
 *    navegador nada aconteceria até um segundo evento. Este é o único lugar que mata esse mutante.
 *
 * **Nenhum `beforeEach` global**, de propósito: os dois cenários têm personas opostas. CT-B01 é o
 * visitante SEM sessão (a tela de login é a única superfície da feature que ele vê; autenticar
 * antes de visitar redirecionaria para o painel) e CT-B02 é o administrador da instalação. Cada um
 * arranja o seu, e cada um aquece a SUA tela.
 */

/**
 * CT-B01 — os quatro botões estão VISÍVEIS, cada um com o ícone dele e o destino dele.
 *
 * O laço sobre `ProvedorSocial::cases()` não é economia de digitação: é o que faz este cenário
 * cobrir o provedor número cinco no dia em que ele entrar no enum, sem ninguém lembrar de voltar
 * aqui. Se o SVG do provedor novo vier com dimensão zero, este caso fica vermelho sozinho.
 *
 * `assertVisible` e não `assertPresent`: presente é o que o arquivo de Kit já prova, e é
 * exatamente o que fica verde com o botão escondido.
 *
 * `assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a tela é de plugin de terceiro
 * (`caresome/filament-auth-designer`), e o `assertNoSmoke()` deixaria a suíte vermelha por
 * `console.log` alheio que ninguém vai corrigir (`.ai/rules/testes-browser.md`). E ela é apoio,
 * nunca oráculo único — quem prova o comportamento são os `assertVisible`.
 *
 * Nenhum `assertPathIs`: nenhuma ação navega aqui. O clique no botão foi deliberadamente cortado —
 * o `redirect()` do provedor falso aponta para `socialite.fake`, domínio que não resolve, então
 * clicar produziria erro de navegação do Playwright em vez de asserção. O `href` prova o destino
 * sem sair da página. Ver "Cogitado e cortado" no `05`.
 *
 * Lacuna declarada (MB4 do `05`): `assertVisible` não prova área clicável nem sobreposição. Se o
 * espaçamento entre botões sumir e os quatro se empilharem, este caso segue verde. Para defeito de
 * layout não há saída barata — é screenshot e olhar.
 */
it('mostra os quatro botoes de provedor visiveis, com icone e destino, na tela de login', function (): void {
    $credenciais = static fn (ProvedorSocial $provedor): array => [
        'client_id'     => 'id-de-teste',
        'client_secret' => 'segredo-de-teste',
        'redirect'      => "/auth/{$provedor->value}/callback",
    ];

    foreach (ProvedorSocial::cases() as $provedor) {
        config()->set("kit.login.{$provedor->value}.habilitado", true);
        config()->set('services.'.$provedor->value, $credenciais($provedor));
    }

    config()->set('kit.login.rodape', 'Kit — todos os direitos reservados');

    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL, fora do
     * cronômetro do Playwright. O `view:cache` do `composer test:browser` cobre as Blade do
     * repositório, mas o primeiro render de um painel ainda paga a compilação dos componentes
     * Livewire do Filament — e rodando ESTE arquivo isolado ninguém pagou essa conta antes, o que
     * estoura os 45 s por um motivo que não é o do cenário. Trocar por um `timeout()` maior não
     * resolve: 40 s e 60 s reproduzem a falha igual.
     *
     * O retorno é descartado de propósito: o que interessa é o efeito colateral em disco, que o
     * servidor do navegador (mesmo processo) reusa.
     */
    $this->get('/app/login');

    $pagina = visit('/app/login')
        // Âncora do formulário primeiro: sem ela, "abaixo do form" não tem referência — e ela é
        // o que acusa MB3, em que um erro de JS no blade novo derruba a tela inteira.
        ->assertVisible('#form\\.password');

    foreach (ProvedorSocial::cases() as $provedor) {
        $botao = '[aria-label="Entrar com '.$provedor->rotulo().'"]';

        $pagina
            ->assertVisible($botao)
            ->assertAttributeContains($botao, 'href', "/auth/{$provedor->value}/redirect")
            /*
             * O ícone COM DIMENSÃO, que é o que o HTML não prova: um `<svg>` sem `width`/`height`
             * está no DOM e ocupa zero. É o mutante MB1, e é o único matador dele.
             *
             * E o `data-provedor` no seletor é o que faz este passo afirmar que é o ícone DAQUELE
             * provedor. Sem ele, GitHub, LinkedIn e X — que são `currentColor` — passariam com o
             * ícone de outra marca, ou com um Heroicon genérico: nada mais no HTML distingue um
             * `<path>` monocromático de outro. Achado da derivação dos casos.
             */
            ->assertVisible($botao.' svg[data-provedor="'.$provedor->value.'"]');
    }

    $pagina
        // O rodapé é regressão: ele sai pelo MESMO render hook dos botões, então um blade novo que
        // estoure derruba os dois.
        ->assertVisible('.fi-login-rodape')
        ->assertSeeIn('.fi-login-rodape', 'todos os direitos reservados')
        ->assertNoJavaScriptErrors();
})->group('browser');

/**
 * CT-B02 — o interruptor do GitHub ABRE os campos de credencial dele, e só os dele.
 *
 * O par dos passos é o oráculo, e nenhum dos dois sozinho serve:
 *
 *   - sem o `assertMissing` ANTES do clique, uma implementação sem `visible()` — campos sempre na
 *     tela — passaria no `assertVisible` depois;
 *   - sem o `assertVisible` DEPOIS, o `->live()` removido passaria no `assertMissing`.
 *
 * O último bloco é o isolamento: ligar um provedor não pode abrir os campos de outro. É o mutante
 * em que o `visible()` de todas as seções lê o interruptor do primeiro provedor — que é
 * exatamente o que um `foreach` escrito com a variável errada produz.
 *
 * **O `Toggle` do Filament não é um `<input type="checkbox">`** — é um botão com `role="switch"`.
 * O `check()`/`assertChecked()` do plugin miram checkbox, então a ação é `click()` e o oráculo é a
 * VISIBILIDADE dos campos, não o estado do controle. O que a pessoa vê é o requisito; o estado
 * interno do controle não é.
 *
 * **Sem `assertNoJavaScriptErrors()`, e a ausência é medida.** O `ColorPicker` do Filament dentro
 * de `Tabs` emite `ResizeObserver loop completed with undelivered notifications` no Chrome
 * headless — duas vezes, na montagem. Não aparece no Chrome do Windows e apareceu no CI, então a
 * asserção deixava a suíte vermelha por ambiente. O plugin não oferece filtro
 * (`assertNoJavaScriptErrors()` compara com array vazio,
 * `vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:78-89`). Custou
 * um CI vermelho na feature `settings-do-kit`, e a nota está no CT-B01 daquela wiki.
 *
 * Nenhum `assertPathIs`: nenhuma ação navega — o clique na aba e no interruptor são
 * Livewire/Alpine na mesma URL. E nenhum `wait()`: o plugin reexecuta cada asserção até o teto de
 * 45 s de `tests/Pest.php`, e `waitForText`/`waitForSelector`/`waitUntil` não existem.
 *
 * Este cenário arranja e visita o MESMO painel (`/admin`), então a armadilha do `visit()` que
 * renderiza a barra lateral do painel do arranjo não se aplica.
 */
it('abre os campos de credencial de um provedor ao ligar o interruptor dele, e so os dele', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    // DT-06 outra vez, agora para a tela do /admin. Ver a nota do CT-B01.
    $this->get('/admin/configuracoes-do-kit');

    $campo = static fn (ProvedorSocial $provedor, string $sufixo): string => '#form\\.'
        .$provedor->propriedadeDeSettings($sufixo);

    $pagina = visit('/admin/configuracoes-do-kit')
        ->click('Login')
        /*
         * Desde 2026-08-26 a seção de cada provedor nasce FECHADA, com o ícone de status no
         * cabeçalho (pedido do solicitante na validação real dos provedores) — então a tela custa
         * UM clique a mais, no cabeçalho da seção, e este cenário o registra. O mutante MB10
         * ("interruptor alcançável") virou: o interruptor aparece depois de abrir a seção.
         */
        ->click('Entrar com GitHub')
        ->assertVisible($campo(ProvedorSocial::Github, 'habilitado'))
        // O estado ANTES: sem esta linha, campos sempre visíveis passariam no oráculo abaixo.
        ->assertMissing($campo(ProvedorSocial::Github, 'client_id'))
        ->assertMissing($campo(ProvedorSocial::Github, 'client_secret'))
        // A ação, e a única.
        ->click($campo(ProvedorSocial::Github, 'habilitado'))
        // O oráculo: os campos APARECERAM, sem nenhum segundo evento.
        ->assertVisible($campo(ProvedorSocial::Github, 'client_id'))
        ->assertVisible($campo(ProvedorSocial::Github, 'client_secret'));

    // E o isolamento: nenhum outro provedor abriu.
    foreach (ProvedorSocial::cases() as $provedor) {
        if ($provedor === ProvedorSocial::Github) {
            continue;
        }

        $pagina
            ->assertMissing($campo($provedor, 'client_id'))
            ->assertMissing($campo($provedor, 'client_secret'));
    }
})->group('browser');
