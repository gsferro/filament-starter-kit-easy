<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Linha de números do painel admin: quantas pessoas existem, quantas se
 * protegeram com 2FA, quanto o cadastro cresceu no último mês, qual o tamanho
 * do vocabulário de autorização (papéis e permissões) — e quantos logins houve
 * hoje.
 *
 * Cinco dos seis stats medem **cadastro**; o sexto mede **uso**, e é a única
 * grandeza aqui cuja leitura depende do ritmo dos dias anteriores — "12 logins
 * hoje" não diz nada sozinho, diz tudo ao lado de uma semana de 40. Por isso ele
 * é o único com `chart()`: um sparkline de 7 dias dentro da própria caixa.
 * Um cadastro grande com zero login era indistinguível de um cadastro grande com
 * uso diário, e nenhum dos cinco primeiros resolvia isso.
 *
 * Todos os valores são inteiros, então todos usam StatPlus (o odômetro só anima
 * número; qualquer texto viraria zero na tela).
 *
 * Ver `wikis/specs/main/stat-de-logins-do-dia/` — ADR-01 explica por que o
 * sparkline é `Stat::chart()` nativo e não ApexCharts, apesar da rule "gráfico é
 * filament-apex-charts": ApexCharts é widget, e o requisito pede o gráfico DENTRO
 * do stat.
 */
class UsuariosVisaoGeralStats extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    private const DIAS_DO_HISTORICO = 7;

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

        $stats = [
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

        /*
         * O sexto stat é CONDICIONAL, e a guarda é local a ele — nunca
         * `fonteDeDadosDisponivel()`. Aquele nome é do trait `ExigePermissaoDoWidget`
         * e decide o widget INTEIRO: declará-lo aqui esconderia os cinco stats
         * acima, que não têm nada a ver com log de acesso, numa instalação sem o
         * plugin. Ver ADR-03.
         *
         * Sem a tabela, o widget volta às cinco caixas de antes. Exibir o stat com
         * zero seria pior: `0` afirma "ninguém entrou hoje", e a verdade é "este
         * dado não é coletado aqui".
         */
        if ($this->logDeAcessoDisponivel()) {
            $serie = $this->loginsPorDia();

            $stats[] = StatPlus::make('Logins hoje', (int) end($serie))
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->iconColor('success')
                ->accentColor('success')
                ->chart($serie)
                // Explícito: sem isto o gráfico herda `getColor()`, que no StatPlus
                // não é necessariamente o mesmo do `accentColor()`.
                ->chartColor('success')
                ->description('Entradas confirmadas · série dos últimos '.self::DIAS_DO_HISTORICO.' dias');
        }

        return $stats;
    }

    /**
     * Logins bem-sucedidos por dia, do mais antigo até hoje, chaveados pelo rótulo do eixo.
     *
     * Usa `flowframe/laravel-trend`, que é o pacote que a **doc do Filament 5 recomenda** para
     * gerar série a partir de model Eloquent (Widgets > Charts > *Generating chart data from an
     * Eloquent model*). Ele resolve, no banco, os dois problemas que um laço à mão resolve no PHP:
     *
     * 1. **Dia vazio vira `0`.** `Trend::mapValuesToDates()` gera um placeholder com
     *    `aggregate: 0` para cada data do `CarbonPeriod` e faz `merge()->unique('date')` — o valor
     *    real vence o placeholder porque vem primeiro. Sem isso a curva "pula" o buraco e um fim
     *    de semana sem ninguém vira um trecho reto, mentindo sobre o uso.
     * 2. **Portabilidade do `GROUP BY` por data.** `Trend::getSqlDate()` escolhe o adapter pelo
     *    driver da conexão — MySQL/MariaDB, SQLite e PostgreSQL têm funções de data com nomes
     *    diferentes, e o kit roda nos três. Era exatamente por isso que
     *    `App\Filament\Infra\Widgets\IaExecucoesPorDia::contarPorDia()` agrupa em PHP; aquele
     *    widget é anterior ao uso do pacote aqui e continua como está.
     *
     * `Trend::query()` e não `Trend::model()`: o filtro `login_successful` precisa entrar antes
     * da agregação. E `dateColumn('login_at')` é **obrigatório** — o default do pacote é
     * `created_at`, e `AuthenticationLog` tem `$timestamps = false` e não possui essa coluna.
     *
     * O valor do stat é a ÚLTIMA posição desta série, nunca uma segunda consulta: duas consultas
     * para o mesmo número abrem a porta para o número e o gráfico discordarem, e o sparkline não
     * tem eixo Y rotulado para ninguém perceber. Ver ADR-04.
     *
     * @return array<string, int>
     */
    private function loginsPorDia(): array
    {
        $serie = Trend::query(
            AuthenticationLog::query()->where('login_successful', true)
        )
            ->dateColumn('login_at')
            ->between(
                start: Carbon::today()->subDays(self::DIAS_DO_HISTORICO - 1),
                end: Carbon::today()->endOfDay(),
            )
            ->perDay()
            ->count();

        return $serie
            /*
             * O rótulo do eixo é reformatado de `Y-m-d` (o que o pacote devolve em `perDay()`)
             * para `d/m`, que é o que cabe embaixo de um sparkline de 40 px. Sem o `Y-m-d`
             * original a chave não é única entre anos, mas a janela é de 7 dias — não há colisão
             * possível.
             */
            ->mapWithKeys(fn (TrendValue $valor): array => [
                Carbon::parse($valor->date)->format('d/m') => (int) $valor->aggregate,
            ])
            ->all();
    }

    /**
     * A tabela do log de acesso existe nesta instalação?
     *
     * `logDeAcessoDisponivel()` e **não** `fonteDeDadosDisponivel()`: o segundo nome pertence ao
     * trait `ExigePermissaoDoWidget` e guarda o widget inteiro. Numa classe com seis fontes
     * diferentes ele também não diria QUAL fonte. Ver ADR-03.
     */
    private function logDeAcessoDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('authentication-log.table_name', 'authentication_log')),
            false,
            report: false,
        );
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
