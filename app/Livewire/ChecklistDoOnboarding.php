<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\TutorialGuiado;
use Illuminate\Support\Facades\Log;
use Wallacemartinss\FilamentOnboarding\Livewire\OnboardingLauncher;

/**
 * O checklist flutuante do pacote, com duas guardas a mais. A primeira: a ação seguinte de um checklist já
 * montado é recusada com 404 quando o tutorial foi desligado (ou a permissão da página saiu)
 * depois de a tela abrir — nada é gravado (RQ-30 da wiki `onboarding-guiado`).
 *
 * Por que uma subclasse: o checklist mora nas telas das FEATURES, não nas rotas do onboarding,
 * então o middleware `ExigeTutorialGuiadoLigado` não o alcança; e o trait do vendor resolve o
 * sujeito por `resolveSubject()`, não por `current()`, então o `skipWhen()` do plugin também não
 * o desliga depois de montado. O `hydrate()` roda antes de toda ação de update.
 *
 * Registrada sob o MESMO nome do pacote (`filament-onboarding-launcher`) no `KitServiceProvider`,
 * que boota depois do provider do pacote: o render hook do plugin monta pelo nome, e passa a
 * montar esta classe.
 */
class ChecklistDoOnboarding extends OnboardingLauncher
{
    public function hydrate(): void
    {
        if (TutorialGuiado::visivel()) {
            return;
        }

        Log::channel('onboarding')->warning(
            '[ChecklistDoOnboarding@hydrate] Ação do checklist recusada com o tutorial desligado | user_id: '.auth()->id(),
            ['user_id' => auth()->id(), 'motivo' => 'tutorial_desligado'],
        );

        abort(404);
    }

    /**
     * Troca de aba só para uma jornada que a pessoa vê NESTE painel (CT-18 da wiki
     * `onboarding-guiado`). O do vendor grava qualquer chave: com a de outro painel, o checklist
     * ficava vazio, sem jornada nenhuma — o cliente escreve `flowKey` por `$wire`.
     */
    public function selectFlow(string $flowKey): void
    {
        if ($this->resolveFlowState($flowKey) === null) {
            return;
        }

        parent::selectFlow($flowKey);
    }
}
