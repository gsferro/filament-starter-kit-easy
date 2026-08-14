<?php

namespace App\Support;

use BezhanSalleh\FilamentShield\FilamentShield;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * O mapa painel × Resource × permission.
 *
 * O Shield não sabe a que painel uma permission pertence: o nome dela é
 * `{Ação}:{Model}` e nada mais (`FilamentShield::defaultPermissionKeyBuilder()`), e a
 * tabela `permissions` é a do spatie, sem coluna extra. O único diferenciador que chega
 * ao banco é o `guard_name`, e os três painéis do kit usam o mesmo guard.
 *
 * Quem sabe o painel é o Filament: cada `Panel` conhece os próprios Resources. Esta
 * classe cruza as duas coisas — para o `PapeisSeeder` recortar a matriz de permissões
 * por painel, e para a tela de papéis agrupar as seções.
 *
 * ## Por que perguntar ao Shield em vez de montar o nome
 *
 * `getEntitiesPermissions()` é a MESMA função de onde o `shield:generate` tira o que
 * gravar. Remontar o nome aqui significaria reimplementar `permissions.separator`,
 * `permissions.case`, `resources.subject` e a lista de `policies.methods` — quatro
 * chaves de config que dessincronizam em silêncio.
 *
 * ## Por que descartar a instância a cada painel
 *
 * `FilamentShield` é registrado como `scoped` e memoiza com `once()`, que é por
 * INSTÂNCIA. Trocar o painel corrente não invalida nada: sem instância nova, os três
 * painéis devolvem o resultado do primeiro.
 *
 * E não basta o `forgetInstance()` do container: a **facade** guarda o objeto resolvido
 * em `Facade::$resolvedInstance` e continua entregando o antigo. Foi exatamente o
 * sintoma observado — `Filament::getResources()` respondia 6/1/6 nos três painéis
 * enquanto `FilamentShield::getResources()` respondia 6/6/6, e os três papéis nasciam
 * com a mesma matriz de 79 permissões. Daí o `Facade::clearResolvedInstance()` junto, e
 * o `app('filament-shield')` em vez da facade na varredura.
 */
final class Paineis
{
    /**
     * Chave da memoização.
     *
     * O mapa fica no CONTAINER, não numa propriedade estática: estático sobrevive ao
     * processo inteiro, e numa suíte de testes isso significa o mapa do primeiro caso
     * valendo para todos os outros — inclusive depois de a aplicação ser recriada com
     * outro conjunto de painéis. O container nasce e morre junto com a aplicação, que é
     * exatamente o tempo de vida certo para um mapa derivado dos PanelProviders.
     */
    private const MEMO = 'kit.paineis.mapa';

    /**
     * Rótulo de cada painel, para selects e cabeçalhos.
     *
     * @return array<string, string> ['admin' => '/admin', 'app' => '/app', ...]
     */
    public static function opcoes(): array
    {
        return collect(Filament::getPanels())
            ->map(fn ($painel): string => '/'.$painel->getPath())
            ->all();
    }

    /**
     * Nomes de permission que pertencem a um painel.
     *
     * @return Collection<int, string>
     */
    public static function permissoes(string $painel): Collection
    {
        return collect(self::mapa()['permissoes'][$painel] ?? []);
    }

    /**
     * Entidades de Resource por painel, no formato que a tela de papéis do Shield
     * consome (`resourceFqcn`, `model`, `modelFqcn`, `permissions`).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function resources(): array
    {
        return self::mapa()['resources'];
    }

    /**
     * As permissões destas entidades neste painel — Resource, Page ou Widget.
     *
     * `->only()` casa por FQCN exato. NUNCA por substring: o PHPDoc de
     * `PapeisSeeder::permissoesDeAdministracaoDoApp()` já registra que
     * `str_contains($p, 'User')` foi removido de lá porque um `UserPreferenceResource`
     * futuro cairia nele — e numa SUBTRAÇÃO o erro é o espelhado, tirar permissão de quem
     * deveria tê-la.
     *
     * @param  list<class-string>  $fqcns
     * @return Collection<int, string>
     */
    public static function permissoesDe(string $painel, array $fqcns): Collection
    {
        return collect(self::mapa()['entidades'][$painel] ?? [])
            ->only($fqcns)
            // `flatMap` e não `flatten()`: o segundo devolve `static<int, mixed>` nos stubs
            // do Laravel e a coleção perde o tipo string no caminho.
            ->flatMap(fn (array $chaves): array => $chaves)
            ->unique()
            ->values();
    }

    /**
     * A varredura, uma vez por aplicação.
     *
     * @return array{permissoes: array<string, list<string>>, resources: array<string, list<array<string, mixed>>>, entidades: array<string, array<string, list<string>>>}
     */
    private static function mapa(): array
    {
        if (app()->bound(self::MEMO)) {
            return app(self::MEMO);
        }

        self::exigirDescobertaPorPainel();

        $permissoes = [];
        $resources  = [];
        $entidades  = [];
        $anterior   = Filament::getCurrentPanel();

        try {
            foreach (Filament::getPanels() as $id => $painel) {
                $shield = self::shieldNovo();
                Filament::setCurrentPanel($painel);

                $permissoes[$id] = $shield->getEntitiesPermissions() ?? [];
                $resources[$id]  = array_values($shield->getResources() ?? []);
                $entidades[$id]  = self::entidadesDoPainel($shield);
            }
        } finally {
            self::shieldNovo();

            if ($anterior instanceof Panel) {
                Filament::setCurrentPanel($anterior);
            }
        }

        $mapa = ['permissoes' => $permissoes, 'resources' => $resources, 'entidades' => $entidades];

        app()->instance(self::MEMO, $mapa);

        return $mapa;
    }

    /**
     * FQCN => chaves de permission, para Resource, Page e Widget do painel.
     *
     * As três famílias guardam `permissions` em formatos DIFERENTES, e é a armadilha deste
     * método: Resource guarda `[affix => ['key' => …, 'label' => …]]` e Page/Widget guardam
     * `[chave => rótulo]` (`getDefaultPermissionKeys()` ramifica por `is_array($affixes)`,
     * `FilamentShield.php:91-113`). Aplicar `array_column($…, 'key')` numa Page devolve `[]`
     * — sem erro, sem exception e sem aviso, e a subtração do `panel_user` volta a não
     * subtrair nada. Para Page e Widget o caminho é `array_keys()`, que é o que o próprio
     * Shield usa em `getEntityPermissionKeys()` (`:140-145`).
     *
     * A chave vem do campo `*Fqcn` de DENTRO da entidade, e não da chave externa do array:
     * em `transformWidgets()` (`HasEntityTransformers.php:56-70`) a chave externa pode ser um
     * `WidgetConfiguration`, e só `widgetFqcn` é garantidamente a string.
     *
     * @param  FilamentShield  $shield
     * @return array<string, list<string>>
     */
    private static function entidadesDoPainel(object $shield): array
    {
        return collect($shield->getResources() ?? [])
            ->keyBy('resourceFqcn')
            ->map(fn (array $e): array => array_column($e['permissions'], 'key'))
            ->merge(
                collect($shield->getPages() ?? [])
                    ->keyBy('pageFqcn')
                    ->map(fn (array $e): array => array_keys($e['permissions'])),
            )
            ->merge(
                collect($shield->getWidgets() ?? [])
                    ->keyBy('widgetFqcn')
                    ->map(fn (array $e): array => array_keys($e['permissions'])),
            )
            ->all();
    }

    /**
     * Uma instância limpa do Shield — container e facade.
     *
     * @return FilamentShield
     */
    private static function shieldNovo(): object
    {
        app()->forgetInstance('filament-shield');
        Facade::clearResolvedInstance('filament-shield');

        return app('filament-shield');
    }

    /**
     * Com `discovery.discover_all_*` ligado, o Shield achata os Resources de TODOS os
     * painéis em toda consulta — e este mapa passa a dizer que tudo pertence a todo
     * mundo. O sintoma seria uma matriz de permissões errada, sem erro nenhum: o papel
     * `infra` nasceria com as permissões do `/admin`. Falhar alto é a única saída
     * honesta.
     */
    private static function exigirDescobertaPorPainel(): void
    {
        $ligadas = collect((array) config('filament-shield.discovery', []))
            ->filter()
            ->keys();

        if ($ligadas->isNotEmpty()) {
            throw new RuntimeException(
                'App\Support\Paineis exige descoberta por painel, mas '
                .$ligadas->implode(', ').' está ligado em config/filament-shield.php. '
                .'Com a descoberta global o mapa painel × permissão deixa de separar coisa alguma.'
            );
        }
    }
}
