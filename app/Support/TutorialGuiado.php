<?php

declare(strict_types=1);

namespace App\Support;

use App\Filament\Pages\PrimeirosPassos;
use Database\Seeders\OnboardingSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Wallacemartinss\FilamentOnboarding\FilamentOnboardingPlugin;

/**
 * O decisor do tutorial guiado — a ÚNICA leitura de `kit.onboarding.habilitado`.
 *
 * Uma chave só, editável em /admin/configuracoes-da-aplicacao (aba Aplicação, seção "Tutorial
 * guiado"), sem lista de painéis: cada jornada já declara o painel dela (RQ-22 da wiki
 * `onboarding-guiado`). Chega por `config()`, já sobreposta pelo banco no boot
 * (`ConfiguracoesDoKit::aplicarNaConfig()`): zero consulta para decidir — o mesmo regime de
 * `AgentesDeIa` e `DockDeAtalhos`.
 *
 * Nasce DESLIGADO (RQ-11). Desligado, nada do pacote aparece: o checklist, os tours e a página
 * "Primeiros passos" somem pelo `skipWhen()` do plugin (nenhuma tabela `onboarding_*` é lida), a
 * autoria some do menu pelas policies e responde 404 pelo middleware
 * `App\Http\Middleware\ExigeTutorialGuiadoLigado`, e o checklist já aberto recusa a próxima ação
 * (`App\Livewire\ChecklistDoOnboarding`).
 *
 * Wiki `wikis/specs/feat/onboarding-guiado/`.
 */
final class TutorialGuiado
{
    /** O tutorial guiado está ligado na instalação? */
    public static function habilitado(): bool
    {
        return (bool) config('kit.onboarding.habilitado', false);
    }

    /**
     * Quem vê o tutorial neste request: interruptor ligado E a permissão da página "Primeiros
     * passos" (`View:PrimeirosPassos`) — a mesma pergunta que a página faz no `canAccess()`, para
     * o checklist, os tours e o menu nunca divergirem da página.
     */
    public static function visivel(): bool
    {
        return PrimeirosPassos::canAccess();
    }

    /**
     * O plugin, do mesmo jeito nos três painéis (`.ai/rules/providers-filament.md`: a página de
     * progresso resolve o plugin pelo painel corrente). Tudo o que muda por request é closure. A
     * página "Primeiros passos" é a subclasse do kit (`App\Filament\Pages\PrimeirosPassos`,
     * registrada nos `->pages()` dos painéis), então o `progressPage()` do vendor fica desligado;
     * o `progressPageNavigation()` continua dando rótulo e ícone a ela.
     */
    public static function plugin(): FilamentOnboardingPlugin
    {
        return FilamentOnboardingPlugin::make()
            ->launcher(fn (): bool => self::visivel())
            ->tours(fn (): bool => self::visivel())
            // Desligada com o tutorial ligado ou não (RQ-29): não interrompe o primeiro login.
            ->welcome(false)
            ->progressPage(false)
            ->progressPageNavigation(label: 'Primeiros passos', icon: 'heroicon-o-map')
            ->skipWhen(fn (): bool => ! self::visivel())
            ->conditions(self::condicoes(), self::rotulosDasCondicoes());
    }

    /**
     * As condições em código. `tem_dois_fatores` conclui um passo; as outras decidem a
     * VISIBILIDADE de um passo: some do checklist e não conta no percentual enquanto for falsa, e
     * volta quando ficar verdadeira (P-03, P-11, P-12). Nenhuma reinventa a chave da feature: cada
     * uma pergunta ao decisor que a própria feature já usa (`.ai/rules/config.md`).
     *
     * - `tela:{chave}` — uma por seeder filho com tela (`OnboardingSeeder::TELAS`): o passo só
     *   aparece para quem abre a tela dele no painel corrente. É o `canAccess()` da tela, então
     *   cobre de uma vez a permissão, o interruptor da feature (hub, agentes de IA, central de
     *   comandos, demonstração) e o multi-tenant. A chave é a do passo, que é o nome do filho em
     *   kebab-case (`UsuariosSeeder` → `tela:usuarios`).
     * - as demais, dos passos SEM tela própria: dock, dashboard dinâmico, bloqueio de tela e a troca
     *   de organização.
     *
     * @return array<string, \Closure(Model, ?Model): bool>
     */
    public static function condicoes(): array
    {
        $condicoes = [
            'tem_dois_fatores'             => fn (Model $usuario): bool => method_exists($usuario, 'hasConfirmedTwoFactor') && $usuario->hasConfirmedTwoFactor(),
            'dock_no_painel'               => fn (): bool => DockDeAtalhos::pacoteInstalado() && DockDeAtalhos::habilitadoNoPainel(Filament::getCurrentPanel()),
            'dashboard_dinamico_no_painel' => fn (): bool => DashboardDinamico::habilitado(Filament::getCurrentPanel()),
            'bloqueio_de_tela_ligado'      => fn (): bool => (bool) config('lockscreen.enabled'),
            'multi_tenant_ligado'          => fn (): bool => (bool) config('kit.tenancy.enabled'),
        ];

        foreach (self::filhosComTela() as $chave => $filho) {
            $condicoes["tela:{$chave}"] = fn (): bool => self::abreATelaDo($filho);
        }

        return $condicoes;
    }

    /** @return array<string, string> */
    public static function rotulosDasCondicoes(): array
    {
        $rotulos = [
            'tem_dois_fatores'             => 'Tem a autenticação em dois fatores confirmada',
            'dock_no_painel'               => 'O dock de atalhos está ligado neste painel',
            'dashboard_dinamico_no_painel' => 'O dashboard dinâmico está ligado neste painel',
            'bloqueio_de_tela_ligado'      => 'O bloqueio de tela está ligado',
            'multi_tenant_ligado'          => 'O modo multi-tenant está ligado',
        ];

        foreach (array_keys(self::filhosComTela()) as $chave) {
            $rotulos["tela:{$chave}"] = "Abre a tela do passo \"{$chave}\" neste painel";
        }

        return $rotulos;
    }

    /**
     * Os seeders filhos que ensinam uma tela, pela chave do passo.
     *
     * @return array<string, class-string>
     */
    private static function filhosComTela(): array
    {
        $filhos = array_unique(array_merge(...array_values(array_map('array_values', OnboardingSeeder::TELAS))));

        return collect($filhos)
            ->mapWithKeys(fn (string $filho): array => [Str::kebab(Str::beforeLast(class_basename($filho), 'Seeder')) => $filho])
            ->all();
    }

    /** Alguma tela que o filho ensina, no painel corrente, abre para quem está aqui? Fora de painel, não. */
    private static function abreATelaDo(string $filho): bool
    {
        $telas = array_keys(OnboardingSeeder::TELAS[Filament::getCurrentPanel()?->getId()] ?? [], $filho, true);

        foreach ($telas as $tela) {
            if ($tela::canAccess()) {
                return true;
            }
        }

        return false;
    }
}
