<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Linha de números do painel admin: quantas pessoas existem, quantas se
 * protegeram com 2FA, quanto o cadastro cresceu no último mês e qual o tamanho
 * do vocabulário de autorização (papéis e permissões).
 *
 * StatsOverview e não gráfico: são cinco grandezas sem relação entre si — não
 * há composição nem série temporal a desenhar, só o valor corrente de cada uma.
 * Todos os valores são inteiros, então todos usam StatPlus (o odômetro só anima
 * número; qualquer texto viraria zero na tela).
 */
class UsuariosVisaoGeralStats extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Usuários e acesso';

    /**
     * @return array<int, StatPlus>
     */
    protected function getStats(): array
    {
        $total          = User::query()->count();
        $novos          = User::query()->where('created_at', '>=', now()->subDays(30))->count();
        $comDoisFatores = $this->contarUsuariosComDoisFatores();

        return [
            StatPlus::make('Usuários', $total)
                ->icon('heroicon-o-users')
                ->iconColor('primary')
                ->accentColor('primary')
                ->description('Contas cadastradas no sistema'),

            StatPlus::make('Com 2FA ativo', $comDoisFatores)
                ->icon('heroicon-o-shield-check')
                // Verde só quando TODO mundo está protegido; caso contrário é
                // uma pendência de segurança, não uma conquista.
                ->iconColor($total > 0 && $comDoisFatores === $total ? 'success' : 'warning')
                ->accentColor($total > 0 && $comDoisFatores === $total ? 'success' : 'warning')
                ->description($this->descreverCobertura($comDoisFatores, $total)),

            StatPlus::make('Novos em 30 dias', $novos)
                ->icon('heroicon-o-user-plus')
                ->iconColor('info')
                ->accentColor('info')
                ->description('Cadastros desde '.now()->subDays(30)->translatedFormat('d/m/Y')),

            StatPlus::make('Papéis', Role::query()->count())
                ->icon('heroicon-o-identification')
                ->iconColor('gray')
                ->accentColor('gray')
                ->description('Perfis de acesso (Shield)'),

            StatPlus::make('Permissões', Permission::query()->count())
                ->icon('heroicon-o-key')
                ->iconColor('gray')
                ->accentColor('gray')
                ->description('Ações protegidas por gate'),
        ];
    }

    /**
     * O 2FA do Breezy vive em `breezy_sessions` (morph `authenticatable`), não
     * numa coluna de `users`. Um usuário pode ter uma linha por painel, daí o
     * DISTINCT: sem ele, quem ativou 2FA no admin e no infra contaria duas vezes.
     *
     * A tabela é guardada por `hasTable()` porque o 2FA é opcional — desligar o
     * plugin não pode derrubar o dashboard inteiro.
     */
    private function contarUsuariosComDoisFatores(): int
    {
        if (! Schema::hasTable('breezy_sessions')) {
            return 0;
        }

        return DB::table('breezy_sessions')
            ->where('authenticatable_type', User::class)
            ->whereNotNull('two_factor_confirmed_at')
            ->distinct()
            ->count('authenticatable_id');
    }

    /**
     * Base zero não tem percentual — num kit recém-instalado a divisão seria
     * por zero e "0%" mentiria sobre uma amostra inexistente.
     */
    private function descreverCobertura(int $parte, int $total): string
    {
        if ($total === 0) {
            return 'Nenhum usuário cadastrado ainda';
        }

        return round(($parte / $total) * 100).'% dos usuários';
    }
}
