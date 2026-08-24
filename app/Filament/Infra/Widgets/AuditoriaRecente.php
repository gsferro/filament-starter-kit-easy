<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\TimelineEvent;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\TimelineWidget;
use OwenIt\Auditing\Models\Audit;

/**
 * As últimas alterações auditadas, em ordem cronológica.
 *
 * Timeline e não RecentItems: auditoria é NARRATIVA — o que importa é a
 * sequência ("quem mexeu logo antes do sistema quebrar"), e o agrupamento por
 * dia da Timeline dá essa leitura de graça. RecentItems trataria cada linha
 * como um item independente e perderia o encadeamento.
 */
class AuditoriaRecente extends TimelineWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 140;

    protected int|string|array $columnSpan = 'full';

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('audit.drivers.database.table', 'audits')),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Alterações recentes';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Trilha de auditoria — quem mudou o quê, e quando';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhuma alteração auditada';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    /**
     * Agrupar por dia é o que transforma uma lista em linha do tempo.
     */
    protected function shouldGroupByDay(): bool
    {
        return true;
    }

    protected function getLimit(): ?int
    {
        return 8;
    }

    /**
     * Cabeçalho de cada grupo do dia em pt-BR: o pacote só traduz
     * "Today"/"Yesterday" para en/fr, e são os dois rótulos mais visíveis da
     * linha do tempo.
     */
    protected function dayLabel(?Carbon $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        return match (true) {
            $timestamp->isToday()     => 'Hoje',
            $timestamp->isYesterday() => 'Ontem',
            default                   => $timestamp->translatedFormat('d/m/Y'),
        };
    }

    /**
     * @return array<int, TimelineEvent>
     */
    protected function getEvents(): array
    {
        $registros = Audit::query()
            ->latest('created_at')
            ->limit(8)
            ->get();

        $autores = $this->nomesDosAutores($registros);

        return $registros
            ->map(fn (Audit $registro): TimelineEvent => TimelineEvent::make($this->titular($registro))
                ->timestamp($registro->created_at)
                ->actor($this->autor($registro, $autores))
                ->badge($this->rotuloDoEvento($registro))
                ->badgeColor($this->corDoEvento($registro))
                ->icon($this->iconeDoEvento($registro))
                ->color($this->corDoEvento($registro))
                ->description($this->camposAlterados($registro)))
            ->all();
    }

    /**
     * Nomes dos autores numa consulta só.
     *
     * `with('user')` do pacote resolveria o morph, mas dispararia uma consulta
     * por TIPO de autor presente no lote e traria o registro inteiro só para
     * ler um nome. Aqui basta o mapa id => nome dos usuários.
     *
     * @param  Collection<int, Audit>  $registros
     * @return array<int|string, string>
     */
    private function nomesDosAutores(Collection $registros): array
    {
        $ids = $registros
            ->filter(fn (Audit $registro): bool => $registro->getAttribute('user_type') === User::class)
            ->map(fn (Audit $registro): mixed => $registro->getAttribute('user_id'))
            ->filter()
            ->unique()
            ->all();

        if ($ids === []) {
            return [];
        }

        /** @var array<int|string, string> $nomes */
        $nomes = User::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();

        return $nomes;
    }

    /**
     * `auditable_type` guarda o FQCN; o nome curto é o que a pessoa reconhece.
     */
    private function titular(Audit $registro): string
    {
        $modelo = $registro->auditable_type === null
            ? 'Registro'
            : class_basename($registro->auditable_type);

        return $modelo.' #'.($registro->auditable_id ?? '?');
    }

    /**
     * Alteração sem usuário é legítima e importante: veio de comando artisan,
     * job ou seeder. Rotular como "Sistema" é mais honesto que deixar em branco.
     *
     * @param  array<int|string, string>  $autores
     */
    private function autor(Audit $registro, array $autores): string
    {
        $tipo = $registro->getAttribute('user_type');
        $id   = $registro->getAttribute('user_id');

        if ($tipo === null || ! (is_int($id) || is_string($id))) {
            return 'Sistema';
        }

        // Autor de outro tipo (o morph aceita qualquer model) não vira
        // "Sistema": isso apagaria a autoria. Vira o nome curto da classe.
        if ($tipo !== User::class) {
            return class_basename((string) $tipo).' #'.$id;
        }

        return $autores[$id] ?? 'Usuário #'.$id;
    }

    /**
     * Só os NOMES dos campos, nunca os valores: a trilha guarda dado pessoal em
     * `new_values` e o dashboard é a tela mais exposta do painel.
     */
    private function camposAlterados(Audit $registro): ?string
    {
        $campos = array_keys($registro->new_values ?: $registro->old_values ?: []);

        if ($campos === []) {
            return null;
        }

        $amostra = array_slice($campos, 0, 5);
        $resto   = count($campos) - count($amostra);

        return implode(', ', $amostra).($resto > 0 ? " (+{$resto})" : '');
    }

    private function rotuloDoEvento(Audit $registro): string
    {
        return match ($registro->event) {
            'created'  => 'criado',
            'updated'  => 'alterado',
            'deleted'  => 'excluído',
            'restored' => 'restaurado',
            default    => $registro->event,
        };
    }

    private function corDoEvento(Audit $registro): string
    {
        return match ($registro->event) {
            'created'  => 'success',
            'updated'  => 'info',
            'deleted'  => 'danger',
            'restored' => 'warning',
            default    => 'gray',
        };
    }

    private function iconeDoEvento(Audit $registro): string
    {
        return match ($registro->event) {
            'created'  => 'heroicon-o-plus-circle',
            'updated'  => 'heroicon-o-pencil-square',
            'deleted'  => 'heroicon-o-trash',
            'restored' => 'heroicon-o-arrow-uturn-left',
            default    => 'heroicon-o-ellipsis-horizontal-circle',
        };
    }
}
