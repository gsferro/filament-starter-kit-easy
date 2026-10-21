<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
use App\Support\TutorialGuiado;
use Wallacemartinss\FilamentOnboarding\Pages\OnboardingProgress;

/**
 * A página "Primeiros passos" do tutorial guiado (`/{painel}/onboarding`), nos três painéis —
 * a do pacote, com a permissão da tela consultada (wiki `onboarding-guiado`).
 *
 * Por que subclasse: a Page do vendor tem `canAccess()` permissivo, e a `View:` que o Shield gera
 * para ela seria um checkbox que não governa nada (`.ai/rules/filament.md`, "Page, Widget e Action
 * novos nascem com a permissão consultada"). Aqui o `ExigePermissaoDaTela` decide, com o
 * interruptor do tutorial como regra local: sem permissão ou com o tutorial desligado, a página
 * sai do menu, do hub, do ⌘K e do dock juntos. A URL com o tutorial desligado responde 404 pelo
 * middleware `ExigeTutorialGuiadoLigado` (P-04), antes do 403 da permissão.
 *
 * Registrada nos `->pages()` dos três painéis; o plugin roda com `progressPage(false)` para não
 * registrar a do vendor ao lado. Isso só tira do pacote o link "Começar" da tela de boas-vindas,
 * que o kit mantém desligada (RQ-29).
 */
class PrimeirosPassos extends OnboardingProgress
{
    use ExigePermissaoDaTela;

    protected static function regraLocalDeAcesso(): bool
    {
        return TutorialGuiado::habilitado();
    }
}
