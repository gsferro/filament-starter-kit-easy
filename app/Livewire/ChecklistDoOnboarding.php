<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\TutorialGuiado;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Wallacemartinss\FilamentOnboarding\Livewire\OnboardingLauncher;

/**
 * O checklist flutuante do pacote, com duas guardas a mais. A primeira: a ação seguinte de um checklist já
 * montado é recusada com 404 quando o tutorial foi desligado (ou a permissão da página saiu)
 * depois de a tela abrir — nada é gravado (RQ-30 da wiki `onboarding-guiado`).
 *
 * Por que uma subclasse: o checklist mora nas telas das FEATURES, não nas rotas do onboarding,
 * então o middleware `ExigeTutorialGuiadoLigado` não o alcança. O vendor já transforma a ação em
 * no-op silencioso quando o `skipWhen()` diz sim (`InteractsWithOnboarding::onboarding()` consulta
 * `shouldSkip`), mas o requisito pede RECUSA (404), com trilha no canal `onboarding` — não um clique
 * que parece funcionar e não faz nada. O `hydrate()` roda antes de toda ação de update.
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

        $motivo = TutorialGuiado::habilitado() ? 'sem_permissao_da_pagina' : 'tutorial_desligado';

        Log::channel('onboarding')->warning(
            '[ChecklistDoOnboarding@hydrate] Ação do checklist recusada | motivo: '.$motivo,
            ['user_id' => auth()->id(), 'motivo' => $motivo],
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

    /**
     * A mesma guarda para `flowKey` escrito DIRETO pelo cliente (`$wire.set('flowKey', …)`), que não
     * passa pelo `selectFlow()` — a propriedade é pública e sem `#[Locked]` no trait do vendor
     * (P-20 da wiki `onboarding-guiado`). Chave de jornada que a pessoa não vê neste painel volta
     * para nulo, e o checklist segue na jornada visível.
     */
    public function updatedFlowKey(?string $flowKey): void
    {
        if ($flowKey !== null && $this->resolveFlowState($flowKey) === null) {
            Log::channel('onboarding')->warning(
                // O valor do cliente sai da mensagem e entra no context sem quebra de linha: o formatter do Laravel
                // devolve a quebra do JSON como linha real, e o valor forjava registros no log (P-26).
                '[ChecklistDoOnboarding@updatedFlowKey] Jornada fora do painel recusada no checklist',
                ['user_id' => auth()->id(), 'flow' => Str::limit((string) preg_replace('/\s+/', ' ', $flowKey), 80), 'motivo' => 'jornada_invisivel'],
            );

            $this->flowKey = null;
        }
    }
}
