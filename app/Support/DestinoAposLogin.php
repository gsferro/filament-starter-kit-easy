<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * Para onde alguém vai depois de entrar pela página única de login (`/login`).
 *
 * A regra é a mesma para o login por senha (`App\Http\Responses\RespostaDeLogin`) e para o login
 * social (`LoginSocialController::urlDoPainel()`): a URL pretendida, se for de um painel que a
 * pessoa acessa; senão o único painel acessível; senão a tela de escolha. Nunca devolve URL de
 * painel que a pessoa não acessa — o 403 do `Authenticate` seria a tela em branco que a feature
 * existe para evitar. Ver `wikis/specs/feat/login-unificado/` (ADR-06).
 */
final class DestinoAposLogin
{
    /**
     * Marca de sessão: "este login começou na página única". `TelaLoginUnificada::mount()` a
     * grava; o `creating` do `authentication_log` (KitServiceProvider) a lê para deixar o painel
     * NULO em vez de carimbar o painel corrente (que na página única é sempre o default); e o
     * carimbo do painel de fato — direto, pretendida ou clique no cartão — a consome.
     */
    public const SESSAO_EM_CURSO = 'login_unificado.em_curso';

    /**
     * Os painéis em que a pessoa pode entrar, na ordem de `Filament::getPanels()`.
     *
     * A pergunta é `User::canAccessPanel()`, a mesma do middleware de cada painel — não uma
     * lista de papéis. `canAccessPanel()` loga `warning` por painel negado; quem só tem um
     * painel gera até dois avisos por login. O Panel Switch da topbar já faz isso a cada
     * request, então o ruído não é novo (ADR-07).
     *
     * @return list<Panel>
     */
    public static function paineisDe(User $user): array
    {
        return array_values(array_filter(
            Filament::getPanels(),
            fn (Panel $painel): bool => $user->canAccessPanel($painel),
        ));
    }

    public static function urlPara(User $user): string
    {
        $paineis    = self::paineisDe($user);
        $pretendida = (string) session()->pull('url.intended', '');

        // Zero painéis com sessão viva (o login social não pergunta por painel) também vai para
        // a escolha: é ela quem encerra a sessão e volta ao login. Mandar ao login direto faria
        // laço — a página única redireciona autenticado para cá.
        $painelDaPretendida = $pretendida !== '' ? self::painelDe($pretendida, $paineis) : null;

        $destino = match (true) {
            $painelDaPretendida instanceof Panel => $pretendida,
            count($paineis) === 1                => $paineis[0]->getUrl() ?? url($paineis[0]->getPath()),
            default                              => route('login.painel'),
        };

        // Painel decidido aqui → carimba agora. Escolha → o cartão carimba (`entrarEm()`).
        $painelDeEntrada = $painelDaPretendida ?? (count($paineis) === 1 ? $paineis[0] : null);

        if ($painelDeEntrada instanceof Panel) {
            self::carimbarAcesso($user, $painelDeEntrada);
        }

        $contexto = [
            'user_id'    => $user->getKey(),
            'paineis'    => array_map(fn (Panel $painel): string => $painel->getId(), $paineis),
            'pretendida' => $pretendida !== '' ? parse_url($pretendida, PHP_URL_PATH) : null,
            'destino'    => parse_url($destino, PHP_URL_PATH),
        ];

        if ($paineis === []) {
            Log::channel('autenticacao')->warning(
                "[DestinoAposLogin@urlPara] Autenticado sem nenhum painel acessível — de volta ao login | user: {$user->getKey()}",
                $contexto,
            );
        } else {
            Log::channel('autenticacao')->info(
                "[DestinoAposLogin@urlPara] Destino após login decidido | user: {$user->getKey()} - paineis: ".count($paineis).' - destino: '.$contexto['destino'],
                $contexto,
            );
        }

        return $destino;
    }

    /**
     * O clique num cartão da escolha: valida que o painel é acessível, carimba o acesso e devolve
     * a URL do painel. Painel que a pessoa não acessa (URL digitada) volta à escolha, sem carimbo.
     */
    public static function entrarEm(User $user, string $painelId): string
    {
        foreach (self::paineisDe($user) as $painel) {
            if ($painel->getId() === $painelId) {
                self::carimbarAcesso($user, $painel);

                Log::channel('autenticacao')->info(
                    "[DestinoAposLogin@entrarEm] Painel escolhido após o login | user: {$user->getKey()} - painel: {$painelId}",
                    ['user_id' => $user->getKey(), 'painel' => $painelId],
                );

                return $painel->getUrl() ?? url($painel->getPath());
            }
        }

        Log::channel('autenticacao')->warning(
            "[DestinoAposLogin@entrarEm] Painel pedido não é acessível — de volta à escolha | user: {$user->getKey()} - painel: {$painelId}",
            ['user_id' => $user->getKey(), 'painel' => $painelId, 'motivo' => 'painel_nao_acessivel'],
        );

        return route('login.painel');
    }

    /**
     * Grava no último acesso deste usuário sem painel o painel em que ele de fato entrou, e
     * encerra a marca de sessão. É o que mantém "acessos por painel" (insights, stat de logins)
     * correto com a página única: sem isto todo login por senha contaria como `app`.
     *
     * Sem a coluna `painel` (instalação antiga sem a migration) não faz nada — a mesma tolerância
     * do `creating` no KitServiceProvider.
     */
    private static function carimbarAcesso(User $user, Panel $painel): void
    {
        session()->forget(self::SESSAO_EM_CURSO);

        $tabela = (string) config('authentication-log.table_name', 'authentication_log');

        if (! rescue(fn (): bool => Schema::hasColumn($tabela, 'painel'), false, report: false)) {
            return;
        }

        AuthenticationLog::query()
            ->where('authenticatable_type', $user->getMorphClass())
            ->where('authenticatable_id', $user->getKey())
            ->whereNull('painel')
            ->latest('login_at')
            ->limit(1)
            ->update(['painel' => $painel->getId()]);
    }

    /**
     * O painel acessível de que a URL pretendida faz parte, ou `null`.
     *
     * Só URL do próprio host (open redirect não passa), e o path precisa ser a raiz do painel
     * ou começar por ela COM a barra: `/administracao/x` não é `/admin`.
     *
     * @param  list<Panel>  $paineis
     */
    private static function painelDe(string $url, array $paineis): ?Panel
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && $host !== parse_url(url('/'), PHP_URL_HOST)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';

        foreach ($paineis as $painel) {
            $raiz = '/'.trim($painel->getPath(), '/');

            if ($path === $raiz || str_starts_with($path, $raiz.'/')) {
                return $painel;
            }
        }

        return null;
    }
}
