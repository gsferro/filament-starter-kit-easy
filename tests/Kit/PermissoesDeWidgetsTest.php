<?php

use App\Filament\Admin\Widgets\UltimosUsuariosCadastrados;
use App\Filament\Admin\Widgets\UsuariosPorPapel;
use App\Filament\Concerns\ExigePermissaoDoWidget;
use App\Filament\Infra\Widgets\ComposerReleaseOverviewWidget;
use App\Filament\Infra\Widgets\UltimosAcessos;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A permissão `View:{Widget}` passa a decidir a exibição do widget de painel.
 *
 * Os 23 widgets dos painéis `/admin` e `/infra` já tinham permissão gerada, entregue aos papéis e
 * visível como checkbox — e nenhuma delas era consultada:
 * `vendor/filament/widgets/src/Widget.php:34-37` é `canView(): bool { return true; }`.
 *
 * O que 18 deles tinham em `canView()` **não era autorização**: era
 * `rescue(fn () => Schema::hasTable(...), false)`, ou seja "a tabela desta fonte existe?", que é
 * necessário porque widget que estoura derruba o dashboard inteiro. As duas condições agora
 * coexistem, e é isso que a tabela de decisão de CT-07 mede.
 *
 * Ver `.../04-casos-de-teste.md` (R3 e R9).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    // O dado que CT-08 procura no painel. Semeado aqui porque widget vazio não distingue
    // "ocultado pela permissão" de "não tinha nada a mostrar".
    $usuario = User::factory()->create(['email' => 'alvo-do-widget@example.com']);

    DB::table('authentication_log')->insert([
        'authenticatable_type' => $usuario->getMorphClass(),
        'authenticatable_id'   => $usuario->getKey(),
        'ip_address'           => '203.0.113.7',
        'user_agent'           => 'Teste',
        'login_at'             => now(),
        'login_successful'     => true,
    ]);
});

/*
|--------------------------------------------------------------------------
| R3 — a permissão e a fonte de dados decidem juntas
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — a tabela de decisão permissão × fonte de dados.
 *
 * **O oráculo é o predicado `canView()`, não o HTML**, e isso é deliberado. Se o caso afirmasse só
 * "o dado não aparece na página", uma implementação que deixasse `canView()` devolver `true` e
 * movesse o `Schema::hasTable()` para dentro do `getData()` — devolvendo coleção vazia — passaria, e
 * o widget renderizaria uma caixa vazia consultando uma tabela ausente.
 *
 * As três linhas são as três células significativas da tabela: widget SEM checagem própria com tudo
 * presente, widget COM checagem própria com tudo presente, e o mesmo widget com a **fonte ausente e
 * a permissão presente** — a célula que prova que o `&&` não virou `||`. A quarta célula (sem
 * permissão e sem fonte) é implicada pelas outras duas e não gera cenário.
 */
/*
 * `?string $tabela` com `null` = a trilha de acesso, cujo nome vem de config.
 *
 * A resolução fica no CORPO e não no dataset: o dataset é avaliado na coleta dos testes, antes de a
 * aplicação bootar, e um `config()` ali estoura `Target class [config] does not exist`.
 */
it('decide o widget pela permissão E pela fonte de dados', function (string $papel, string $painel, string $widget, string $permissao, ?string $tabela, bool $fonte, bool $esperado): void {
    if (! $fonte) {
        Schema::dropIfExists($tabela ?? (string) config('authentication-log.table_name', 'authentication_log'));
    }

    $usuario = usuarioDoKit($papel, "{$papel}@example.com");

    expect($usuario->can($permissao))->toBeTrue("o papel {$papel} deveria carregar {$permissao}");

    $this->actingAs($usuario);
    noPainelDoShield($painel);

    expect($widget::canView())->toBe($esperado);
})->with([
    'widget sem checagem própria'       => ['admin', 'admin', UsuariosPorPapel::class, 'View:UsuariosPorPapel', null, true, true],
    'widget com checagem própria'       => ['infra', 'infra', UltimosAcessos::class, 'View:UltimosAcessos', null, true, true],
    'fonte ausente, permissão presente' => ['infra', 'infra', UltimosAcessos::class, 'View:UltimosAcessos', null, false, false],

    /*
     * O widget de PACOTE, agora uma subclasse do kit
     * (`App\Filament\Infra\Widgets\ComposerReleaseOverviewWidget`).
     *
     * A célula que só ele cobre é a da fonte ausente: o `canView()` da classe do pacote é
     * `auth()->check()` e não conferia tabela nenhuma (`.../Filament/Widgets/ComposerReleaseOverviewWidget.php:18-21`),
     * então numa instalação sem as migrations do pacote o dashboard do /infra morria inteiro.
     * O guarda entrou em `fonteDeDadosDisponivel()` — e é nesse ponto que o mutante mora:
     * escrevê-lo em `canView()` desligaria a permissão em silêncio, porque método da classe vence
     * método de trait.
     */
    'pacote, fonte presente' => ['infra', 'infra', ComposerReleaseOverviewWidget::class, 'View:ComposerReleaseOverviewWidget', 'composer_release_package_snapshots', true, true],
    'pacote, fonte ausente'  => ['infra', 'infra', ComposerReleaseOverviewWidget::class, 'View:ComposerReleaseOverviewWidget', 'composer_release_package_snapshots', false, false],
]);

/**
 * CT-08 — revogar a permissão tira o widget da grade do painel, e com ele o dado.
 *
 * **As duas metades são o caso.** A metade `com a permissão` é o que distingue "a permissão está
 * sendo consultada" de "o widget nunca aparece" — sem ela, um widget quebrado por qualquer motivo
 * deixaria o caso verde. E é a metade que mata o mutante "o `canView()` está correto mas o painel
 * monta o widget de qualquer forma": o oráculo é a lista de widgets que o painel decidiu exibir, não
 * o predicado.
 *
 * As duas linhas de widget são as duas partições que decidem a feature:
 * `UltimosUsuariosCadastrados` **não tinha** `canView()` (o `use` do concern basta) e
 * `UltimosAcessos` **tinha** (é onde o método precisou ser renomeado para o hook, e onde mora o
 * mutante do `use` inerte).
 *
 * O oráculo é `getVisibleWidgets()` do Dashboard, e não `assertDontSee` no HTML de `GET /admin`:
 * widget do Filament carrega ADIADO, então o dado sensível não está na resposta inicial nem quando a
 * permissão existe — um `assertDontSee` ali seria falso ✅, verde com a feature inteira removida.
 */
it('tira o widget da grade do painel quando a permissão é revogada', function (string $papel, string $painel, string $pagina, string $widget, string $permissao, bool $comPermissao): void {
    if (! $comPermissao) {
        semAPermissao($papel, $permissao);
    }

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"));

    noPainelBootado($painel);

    $exibidos = collect(app($pagina)->getVisibleWidgets())
        ->map(fn (mixed $w): string => is_string($w) ? $w : $w->widget)
        ->all();

    expect(in_array($widget, $exibidos, true))->toBe($comPermissao);
})->with(function (): iterable {
    $linhas = [
        'sem checagem própria' => ['admin', 'admin', Dashboard::class, UltimosUsuariosCadastrados::class, 'View:UltimosUsuariosCadastrados'],
        'com checagem própria' => ['infra', 'infra', Dashboard::class, UltimosAcessos::class, 'View:UltimosAcessos'],
        /*
         * A terceira partição: widget de PACOTE, cuja classe PAI declara `canView()`.
         *
         * É a única linha que falsifica duas coisas de uma vez — o `canView()` da subclasse não
         * sobrescrever o do pai (e o cartão aparecer para quem não tem a permissão), e o widget do
         * pacote continuar registrado ao lado do do kit (`->widget(enabled: true)` mantido no
         * `InfraPanelProvider`), caso em que a grade exibiria a classe do pacote e a permissão
         * seguiria inerte.
         */
        'pacote com subclasse' => ['infra', 'infra', Dashboard::class, ComposerReleaseOverviewWidget::class, 'View:ComposerReleaseOverviewWidget'],
    ];

    foreach ($linhas as $rotulo => $linha) {
        yield "{$rotulo}, com a permissão"  => [...$linha, true];
        yield "{$rotulo}, sem a permissão"  => [...$linha, false];
    }
});

/*
|--------------------------------------------------------------------------
| R9 — nenhum Widget de painel do kit fica sem consultar a permissão dele
|--------------------------------------------------------------------------
*/

/**
 * CT-22 — todo Widget de painel escrito no kit usa o concern.
 *
 * Enforço estrutural. **Fica vermelho quando alguém cria um Widget novo sem o concern**, com o nome
 * da classe na mensagem — que é o comportamento pedido pela cláusula "TODAS as telas [...] precisa
 * ter sua permissão especifica", e não falso positivo.
 *
 * A lista sai dos painéis registrados, não escrita à mão: escrita à mão ela não pega classe nova,
 * que é justamente o que o caso serve para pegar.
 */
it('não deixa nenhum Widget de painel do kit sem o concern de permissão', function (): void {
    $sem = collect(widgetsDePainelDoKit())
        ->keys()
        ->reject(fn (string $classe): bool => in_array(
            ExigePermissaoDoWidget::class,
            class_uses_recursive($classe),
            true,
        ))
        ->values()
        ->all();

    expect($sem)->toBe([], 'Widgets de painel sem ExigePermissaoDoWidget: '.implode(', ', $sem));
});

/**
 * CT-32 — a checagem é observável, e não só declarada.
 *
 * O par comportamental de CT-22, e o que CT-22 **não** pega: `use ExigePermissaoDoWidget;` num
 * widget que sobrescreva `canView()` é no-op silencioso, porque método de classe vence método de
 * trait. Aqui o oráculo é o comportamento — nenhum nome de trait aparece —, então o caso sobrevive a
 * uma renomeação e mata o `use` inerte.
 *
 * Percorrer os 23 NO MESMO PROCESSO também cobre a memoização estática de
 * `HasWidgetShield::$widgetPermissionKey`: uma implementação que a compartilhasse entre classes
 * daria a decisão do primeiro widget a todos os seguintes.
 *
 * O usuário não tem permissão alguma **e** todas as tabelas de fonte existem (o `RefreshDatabase`
 * migrou tudo), então a única razão possível para um `false` é a permissão — e a única razão
 * possível para um `true` é a permissão não ser consultada.
 */
it('faz cada Widget de painel do kit negar exibição a quem não tem permissão nenhuma', function (): void {
    $this->actingAs(usuarioCom(null));

    $visiveis = [];

    foreach (widgetsDePainelDoKit() as $classe => $painel) {
        noPainelDoShield($painel);

        if ($classe::canView()) {
            $visiveis[] = $classe;
        }
    }

    expect($visiveis)->toBe([], 'Widgets visíveis para usuário sem permissão: '.implode(', ', $visiveis));
});

/**
 * Os Widgets de painel ESCRITOS NO KIT, por painel.
 *
 * Sai de `Filament::getPanels()` porque o que interessa é o que está REGISTRADO — widget num
 * diretório que o `discoverWidgets()` não varre não tem permissão gerada. O filtro por namespace
 * `App\` separa o kit do vendor (`ComposerReleaseOverviewWidget`, `AccountWidget`,
 * `FilamentInfoWidget`), que é a fronteira de ADR-05.
 *
 * Helper de um arquivo só, então fica no arquivo (`.ai/rules/testes.md`).
 *
 * @return array<class-string<Widget>, string> FQCN do Widget => id do painel
 */
function widgetsDePainelDoKit(): array
{
    $widgets = [];

    foreach (Filament::getPanels() as $id => $painel) {
        foreach ($painel->getWidgets() as $classe) {
            $fqcn = is_string($classe) ? $classe : $classe->widget;

            if (! str_starts_with($fqcn, 'App\\Filament\\')) {
                continue;
            }

            $widgets[$fqcn] = (string) $id;
        }
    }

    return $widgets;
}
