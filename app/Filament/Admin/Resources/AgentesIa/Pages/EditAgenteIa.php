<?php

namespace App\Filament\Admin\Resources\AgentesIa\Pages;

use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use App\Models\AgenteIa;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditAgenteIa extends EditRecord
{
    protected static string $resource = AgenteIaResource::class;

    /** Sem DeleteAction — a exclusão é lógica (flag `ativo`). */
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        if (! $record instanceof AgenteIa) {
            return;
        }

        Log::channel('ai')->info(
            "[EditAgenteIa@afterSave] Agente atualizado | slug: {$record->slug}",
            // Só as CHAVES alteradas: o valor novo das instruções não vai para o log.
            ['agente_id' => $record->id, 'campos' => array_keys($record->getChanges()), 'usuario_id' => Auth::id()],
        );
    }
}
