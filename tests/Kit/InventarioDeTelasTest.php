<?php

/**
 * DT-07 — o inventário de telas dos CT-B não pode divergir da realidade em silêncio.
 *
 * `telasDoKit()` (em `tests/Pest.php`) é uma lista escrita à mão, e o defeito não era ela
 * ser escrita à mão: era ninguém comparar. Tela nova não entrava sozinha, e a suíte de
 * navegador seguia verde dando a impressão de cobertura completa — que é o pior resultado
 * possível para um inventário.
 *
 * Este arquivo é a comparação, nos dois sentidos:
 *
 *   - tela que o painel REGISTRA e o inventário não lista → falha nomeando a URL;
 *   - rota que o inventário lista e o roteador não resolve → falha nomeando a URL.
 *
 * O segundo sentido importa tanto quanto o primeiro, e é menos óbvio: `visit('/rota-que-nao-existe')`
 * abre a página de 404, e `assertNoJavaScriptErrors()` PASSA nela — uma tela renomeada por
 * upgrade de plugin sai da cobertura sem nenhum teste ficar vermelho.
 *
 * Fica em `tests/Kit` e não em `tests/Browser` de propósito: é uma comparação de listas, não
 * precisa de navegador, e assim roda no `composer test:kit` — o comando de resposta rápida
 * depois de um `kit:update`, que é exatamente quando uma tela aparece ou desaparece.
 */

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Telas registradas no painel que o inventário NÃO precisa listar, com o motivo.
 *
 * Lista curta e explícita, no lugar de um filtro esperto: cada linha é uma decisão, e é
 * melhor que ela esteja escrita aqui do que embutida numa condição.
 *
 * Função e não constante global: um `const` no topo de arquivo de teste é visível para toda a
 * suíte e é exatamente o tipo de acoplamento invisível que `.ai/rules/testes.md` proíbe.
 *
 * @return list<string>
 */
function telasForaDoInventario(): array
{
    return [
        /*
         * Os hubs em cartões. Não são tela nova: são um índice das outras, e o kit os entrega
         * DESLIGADOS (`KIT_HUB=false` no `.env.example` e no `phpunit.xml`). Quem liga o hub
         * ganha a tela — e ela já tem cobertura própria, mais forte que smoke, em
         * `tests/Browser/HubDeCardsTest.php` e `tests/Kit/HubDeCardsTest.php`.
         */
        '/app/hub-do-negocio',
        '/admin/hub-de-administracao',
        '/infra/hub-de-infraestrutura',
        /*
         * A Lixeira das exceções nos painéis /app e /admin. O `ExceptionResource` é registrado
         * nos TRÊS painéis por obrigação, não por escolha: o plugin resolve o painel corrente e
         * registrar em um só derruba a aplicação inteira, em todo request e todo comando artisan
         * (ver `.ai/rules/providers-filament.md`). Nos dois onde a tela não deve aparecer ele vem
         * com `registerNavigation(false)`, e a tela coberta é a do /infra, que é a de verdade.
         */
        '/app/exceptions',
        '/admin/exceptions',
        /*
         * O resource de exemplo da demo. A rota EXISTE nesta suíte — o resource é descoberto
         * sempre; o que a demo desliga é o `canAccess()`
         * (`app/Filament/App/Resources/Projetos/ProjetoResource.php:80-88`) —, então visitá-la
         * aqui renderiza um 403, e `assertNoJavaScriptErrors()` passa num 403. A cobertura de
         * verdade está em `tests/BrowserTenancy/AnexosDoProjetoTest.php` e
         * `tests/BrowserTenancy/ImportExportDeProjetosTest.php`, onde existe organização.
         *
         * (O comentário original do inventário dizia que a tela "só existe com a demo ligada".
         * Esta guarda mostrou que não: só o acesso é condicional.)
         */
        '/app/projetos',
    ];
}

/**
 * As URLs de tela que o painel registra e que dão para visitar sem um registro na mão.
 *
 * O filtro de `{record}` e `{tenant}` é feito pelo próprio gerador de rotas: URL que exige
 * parâmetro estoura `UrlGenerationException`, e é isso que a distingue de uma tela de lista.
 * Um regex sobre o padrão da rota faria o mesmo trabalho pior.
 *
 * @return list<string>
 */
function telasRegistradasNoPainel(string $painel): array
{
    $urlOuNulo = static function (callable $gerar): ?string {
        try {
            return parse_url($gerar(), PHP_URL_PATH) ?: null;
        } catch (UrlGenerationException) {
            return null;
        }
    };

    $filament = Filament::getPanel($painel);

    $urls = [];

    foreach ($filament->getPages() as $pagina) {
        $urls[] = $urlOuNulo(fn (): string => $pagina::getUrl(panel: $painel));
    }

    foreach ($filament->getResources() as $resource) {
        foreach (array_keys($resource::getPages()) as $nome) {
            $urls[] = $urlOuNulo(fn (): string => $resource::getUrl($nome, panel: $painel));
        }
    }

    return array_values(array_unique(array_filter($urls)));
}

it('não deixa tela registrada de fora do inventário dos CT-B', function (string $painel): void {
    $ausentes = array_values(array_diff(
        telasRegistradasNoPainel($painel),
        telasDoKit()[$painel],
        telasForaDoInventario(),
    ));

    expect($ausentes)->toBe([], sprintf(
        'Telas registradas no painel /%s e ausentes de telasDoKit(): %s. '
        .'Acrescente cada uma ao inventário em tests/Pest.php, ou a telasForaDoInventario() com o motivo.',
        $painel,
        implode(', ', $ausentes),
    ));
})->with(['app', 'admin', 'infra']);

it('não deixa rota morta no inventário dos CT-B', function (string $painel): void {
    // A resolução do próprio roteador, e não um `Route::has()` de nome: o inventário lista
    // CAMINHOS, e é o caminho que o navegador visita. Foi assim que
    // `/infra/queue-monitors/pending` apareceu — rota que existe com `QUEUE_CONNECTION=database`
    // e não existe nesta suíte.
    $resolve = static function (string $rota): bool {
        try {
            Route::getRoutes()->match(Request::create($rota, 'GET'));

            return true;
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }
    };

    $mortas = array_values(array_filter(
        telasDoKit()[$painel],
        fn (string $rota): bool => ! $resolve($rota),
    ));

    expect($mortas)->toBe([], sprintf(
        'Rotas listadas em telasDoKit() que o roteador não resolve no painel /%s: %s. '
        .'`visit()` numa dessas abre a página de 404 e o assert de console PASSA — a tela sai '
        .'da cobertura sem nada ficar vermelho.',
        $painel,
        implode(', ', $mortas),
    ));
})->with(['app', 'admin', 'infra']);
