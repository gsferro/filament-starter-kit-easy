<?php

/**
 * DT-01 — o botão de limpar cache tem de ter nome acessível.
 *
 * O plugin `cms-multi/filament-clear-cache` é registrado SÓ no `InfraPanelProvider`
 * (`:198`), e a blade dele põe o rótulo apenas dentro do `x-tooltip` — que o Alpine monta em
 * hover, onde leitor de tela não chega. O axe-core reprova como `critical` ("Buttons must
 * have discernible text"), e o botão limpa o cache da aplicação inteira.
 *
 * A correção é uma cópia da blade em `resources/views/vendor/`, com uma propriedade a mais.
 * Isso cria dois modos NOVOS de falhar em silêncio, e é deles que este arquivo trata:
 *
 *   1. o pacote muda o namespace/caminho da view e a cópia deixa de ser usada;
 *   2. o pacote muda a blade e a cópia congela a versão antiga.
 *
 * O primeiro caso o smoke pega. O segundo só o diff contra o `vendor/` pega — e é o custo
 * que a nota do `kit.css` cobra de quem publica view de terceiro.
 */

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\View;

/** A blade original, de onde a cópia do kit saiu. */
function bladeDoVendorDoLimparCache(): string
{
    return base_path('vendor/cms-multi/filament-clear-cache/resources/views/livewire/clear-cache-button.blade.php');
}

/** A cópia do kit, que sobrepõe a do vendor. */
function bladeDoKitDoLimparCache(): string
{
    return resource_path('views/vendor/filament-clear-cache/livewire/clear-cache-button.blade.php');
}

/**
 * O atributo no HTML de verdade, servido pelo painel de verdade.
 *
 * `/infra` e não `/admin`: o plugin só está registrado lá. Verificar no `/admin` encontraria
 * o botão por contaminação de processo (DT-08) ou não o encontraria de jeito nenhum — foi
 * assim que a primeira escrita da dívida atribuiu o botão ao painel errado.
 */
it('serve o botão de limpar cache com nome acessível no painel infra', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $master = User::create(['name' => 'Master', 'email' => 'master@example.com', 'password' => 'password']);
    $master->assignRole('master_global');

    $html = $this->actingAs($master)->get('/infra')->assertSuccessful()->getContent();

    // O rótulo vem da mesma chave de tradução do tooltip. Continua em inglês enquanto
    // `lang/vendor/filament-clear-cache/` não for publicado — isso é DT-09.
    $rotulo = __('filament-clear-cache::general.clear_cache');

    // `str_contains` e não `assertSee`/`toContain`: a falha destes despeja a página inteira
    // do painel na saída do Pest, e a mensagem útil se perde em 200 KB de HTML.
    expect(str_contains($html, 'wire:key="clear-cache-button"'))
        ->toBeTrue('O botão de limpar cache não está no HTML de /infra.')
        ->and(str_contains($html, 'aria-label="'.$rotulo.'"'))
        ->toBeTrue("O botão de limpar cache saiu sem `aria-label=\"{$rotulo}\"`.");
});

/**
 * A cópia do kit é quem responde pelo nome da view do pacote.
 *
 * Um upgrade que renomeie a view ou o namespace derruba a sobreposição sem tocar em nada do
 * kit, e o `aria-label` sumiria. O caminho resolvido é a prova direta disso — o
 * `loadViewsFrom()` põe `resources/views/vendor/<namespace>` na frente do diretório do
 * pacote (`vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php:212-226`,
 * chamado em `vendor/spatie/laravel-package-tools/src/Concerns/PackageServiceProvider/ProcessViews.php:18`).
 */
it('resolve a view do pacote para a cópia do kit', function (): void {
    expect(View::getFinder()->find('filament-clear-cache::livewire.clear-cache-button'))
        ->toBe(bladeDoKitDoLimparCache());
});

/**
 * A cópia continua sendo o arquivo do vendor MAIS uma linha, e nada além disso.
 *
 * É o que impede a cópia de virar um fork esquecido: quando o pacote mexer na blade, este
 * caso fica vermelho nomeando a divergência, em vez de o kit servir para sempre a versão do
 * dia em que a dívida foi paga. O conserto é refazer o diff e repor a linha da propriedade.
 */
it('mantém a cópia a uma única linha de distância da blade do vendor', function (): void {
    $normalizar = static fn (string $caminho): string => rtrim(str_replace(
        "\r\n",
        "\n",
        (string) file_get_contents($caminho),
    ));

    $kit = $normalizar(bladeDoKitDoLimparCache());

    // O cabeçalho explicativo do kit não conta: ele é comentário de Blade e não chega ao
    // HTML. O que tem de bater é o corpo.
    $semCabecalho = (string) preg_replace('~^\{\{--.*?--\}\}\s*~s', '', $kit);

    $semRotulo = (string) preg_replace('~^\s*:label="[^"]*"\n~m', '', $semCabecalho);

    expect($semRotulo)->toBe($normalizar(bladeDoVendorDoLimparCache()));
});
