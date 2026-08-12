<?php

namespace App\Filament\Infra\Resources\AiRuns\Tables;

use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Fomvasss\AiTasks\Models\AiRun;
use Gsferro\FilamentOdometerEasy\Tables\Columns\OdometerColumn;

/**
 * Grid do ledger de execuções de IA. Sem Create/Edit/Delete — trilha imutável, gravada pelo
 * listener `RegistrarAiRun` e pelo próprio pacote ai-tasks.
 */
class AiRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('task')->label('Tarefa')->badge()->searchable(),
                TextColumn::make('driver')->label('Driver')->badge()->color('gray')->searchable(),
                // O path JSON é só exibição; a busca vai na coluna `model` — mesmo valor, mas
                // indexável.
                TextColumn::make('request.options.model')->label('Modelo')->placeholder('—')->searchable(['model']),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok'            => 'success',
                        'error', 'dead' => 'danger',
                        'running'       => 'info',
                        'waiting'       => 'warning',
                        default         => 'gray',
                    }),
                OdometerColumn::make('tokens_in')->label('Tokens in')->placeholder('—')->sortable(),
                OdometerColumn::make('tokens_out')->label('Tokens out')->placeholder('—')->sortable(),
                // `maximumFractionDigits: 6` porque execução barata custa frações de centavo e
                // arredondar para 2 casas mostraria 0,00 na maioria das linhas. O custo vem do
                // pacote em USD (config `ai-tasks.drivers.*.price`).
                OdometerColumn::make('cost')->label('Custo (USD)')
                    ->format(['style' => 'currency', 'currency' => 'USD', 'minimumFractionDigits' => 2, 'maximumFractionDigits' => 6])
                    ->placeholder('—')->sortable(),
                OdometerColumn::make('duration_ms')->label('Duração (ms)')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dispatch')->label('Dispatch')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options([
                    'queued'  => 'Na fila',
                    'running' => 'Executando',
                    'ok'      => 'OK',
                    'error'   => 'Erro',
                    'dead'    => 'Morta',
                    'waiting' => 'Aguardando',
                    'skipped' => 'Ignorada',
                ]),
                // Options por distinct no load da página — troque por lista fixa se a tabela
                // crescer muito.
                SelectFilter::make('task')->label('Tarefa')
                    ->options(fn (): array => AiRun::query()->distinct()->orderBy('task')->pluck('task', 'task')->all()),
                SelectFilter::make('driver')->label('Driver')
                    ->options(fn (): array => AiRun::query()->distinct()->orderBy('driver')->pluck('driver', 'driver')->all()),
            ])
            ->recordUrl(fn ($record): string => AiRunResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Nenhuma execução de IA registrada')
            ->emptyStateDescription('As execuções dos agentes aparecem aqui automaticamente.');
    }
}
