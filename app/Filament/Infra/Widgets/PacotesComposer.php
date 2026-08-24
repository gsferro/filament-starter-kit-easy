<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\Bullet;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BulletWidget;
use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;

/**
 * Quantos pacotes monitorados estão na última versão publicada.
 *
 * Bullet e não Metric: existem duas grandezas na mesma escala — o que está em
 * dia (valor) e o total monitorado (alvo). O bullet põe as duas na mesma régua
 * e ainda pinta sozinho de verde quando o alvo é alcançado; um Metric só
 * mostraria "37" sem dizer 37 de quantos.
 */
class PacotesComposer extends BulletWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 60;

    protected int|string|array $columnSpan = 1;

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable('composer_release_package_snapshots'),
            false,
            report: false,
        );
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum pacote monitorado';
    }

    public function getEmptyStateDescription(): ?string
    {
        return 'Configure os pacotes em config/filament-composer-release-notifier.php e sincronize.';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-cube';
    }

    protected function getBullet(): Bullet
    {
        $total     = ComposerReleasePackageSnapshot::query()->count();
        $atrasados = ComposerReleasePackageSnapshot::query()->where('is_outdated', true)->count();
        $emDia     = $total - $atrasados;

        return Bullet::make('Pacotes na última versão', $emDia)
            ->icon('heroicon-o-cube')
            // Alvo = todos: qualquer pacote atrasado é dívida técnica visível.
            ->target($total)
            ->max($total)
            // Faixas em terços dão contexto qualitativo ao fundo da régua sem
            // inventar meta nova — a meta continua sendo 100%.
            ->ranges([(int) floor($total / 3), (int) floor($total * 2 / 3)])
            ->description($this->descrever($atrasados));
    }

    private function descrever(int $atrasados): string
    {
        $sincronizacao = $this->ultimaSincronizacao();
        $sufixo        = $sincronizacao === null
            ? ' • nunca sincronizado'
            : ' • sincronizado '.$sincronizacao->diffForHumans();

        if ($atrasados === 0) {
            return 'Tudo atualizado'.$sufixo;
        }

        return ($atrasados === 1 ? '1 pacote atrás da última release' : "{$atrasados} pacotes atrás da última release").$sufixo;
    }

    private function ultimaSincronizacao(): ?Carbon
    {
        $valor = ComposerReleasePackageSnapshot::query()->max('synced_at');

        return is_string($valor) ? Carbon::parse($valor) : null;
    }
}
