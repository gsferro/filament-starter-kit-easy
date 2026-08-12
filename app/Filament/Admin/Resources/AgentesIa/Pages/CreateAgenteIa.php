<?php

namespace App\Filament\Admin\Resources\AgentesIa\Pages;

use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use App\Models\AgenteIa;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateAgenteIa extends CreateRecord
{
    protected static string $resource = AgenteIaResource::class;

    /** Trilha no canal `ai`: quem criou qual paper (o conteúdo fica na auditoria do model). */
    protected function afterCreate(): void
    {
        $record = $this->record;

        if (! $record instanceof AgenteIa) {
            return;
        }

        Log::channel('ai')->info(
            "[CreateAgenteIa@afterCreate] Agente criado | slug: {$record->slug}",
            ['agente_id' => $record->id, 'usuario_id' => Auth::id()],
        );
    }
}
