<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Brimham\BackupMonitor\Models\BackupRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use LaBoiteACode\FilamentDashboardWidgets\Data\Metric;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\MetricWidget;

/**
 * Quando foi o último backup e se ele deu certo.
 *
 * Metric e não Card: o dado é um valor único com contexto ("há 3 horas", disco,
 * tamanho) — exatamente a forma do Metric. O Card serviria se houvesse ação a
 * disparar dali, e disparar backup pelo dashboard não é operação de painel.
 *
 * Backup é a métrica de infra em que a AUSÊNCIA de dado é a notícia: um backup
 * velho é indistinguível de nenhum backup se a tela só mostrar "sucesso".
 */
class UltimoBackup extends MetricWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 1;

    /**
     * A tabela vem do brimham/backup-monitor via `loadMigrationsFrom()`. Se o
     * pacote sair do composer.json, o widget some junto em vez de estourar.
     */
    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable('backup_runs'),
            false,
            report: false,
        );
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum backup registrado';
    }

    public function getEmptyStateDescription(): ?string
    {
        return 'Rode `php artisan backup:run` ou verifique o agendamento.';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-circle-stack';
    }

    protected function getMetric(): Metric
    {
        // `type = backup` filtra as linhas de health check e de limpeza que o
        // pacote grava na MESMA tabela — sem isso o "último backup" poderia ser
        // na verdade a última faxina de arquivos antigos.
        $ultimo = BackupRun::query()
            ->where('type', 'backup')
            ->latest('created_at')
            ->first();

        if ($ultimo === null) {
            // value null => o widget cai no empty state.
            return Metric::make('Último backup')->icon('heroicon-o-circle-stack');
        }

        $sucesso = $ultimo->getAttribute('status') === 'success';
        $momento = $this->momentoDe($ultimo);

        return Metric::make('Último backup', $momento?->diffForHumans() ?? 'data desconhecida')
            ->icon($sucesso ? 'heroicon-o-shield-check' : 'heroicon-o-shield-exclamation')
            ->color($sucesso ? 'success' : 'danger')
            ->description($this->descrever($ultimo, $sucesso))
            ->tooltip($momento?->format('d/m/Y H:i:s'));
    }

    private function momentoDe(BackupRun $execucao): ?Carbon
    {
        $criadoEm = $execucao->getAttribute('created_at');

        return $criadoEm instanceof \DateTimeInterface ? Carbon::instance($criadoEm) : null;
    }

    /**
     * Em caso de falha a mensagem do erro vale mais que o tamanho do arquivo —
     * é ela que diz se foi credencial, disco cheio ou timeout.
     */
    private function descrever(BackupRun $execucao, bool $sucesso): string
    {
        $disco = (string) ($execucao->getAttribute('disk') ?? 'disco não informado');

        if (! $sucesso) {
            $mensagem = (string) ($execucao->getAttribute('message') ?? 'sem detalhe do erro');

            return "Falhou em {$disco}: ".str($mensagem)->limit(80)->toString();
        }

        $bytes = $execucao->getAttribute('size_in_bytes');

        return $bytes === null
            ? "Concluído em {$disco}"
            : "Concluído em {$disco} • ".Number::fileSize((int) $bytes, precision: 1);
    }
}
