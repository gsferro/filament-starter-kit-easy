<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Log;

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
        $destino = match (true) {
            $pretendida !== '' && self::pertenceAAlgum($pretendida, $paineis) => $pretendida,
            count($paineis) === 1                                             => $paineis[0]->getUrl() ?? url($paineis[0]->getPath()),
            default                                                           => route('login.painel'),
        };

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
     * A URL pretendida é de um dos painéis acessíveis?
     *
     * Só URL do próprio host (open redirect não passa), e o path precisa ser a raiz do painel
     * ou começar por ela COM a barra: `/administracao/x` não é `/admin`.
     *
     * @param  list<Panel>  $paineis
     */
    private static function pertenceAAlgum(string $url, array $paineis): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && $host !== parse_url(url('/'), PHP_URL_HOST)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';

        foreach ($paineis as $painel) {
            $raiz = '/'.trim($painel->getPath(), '/');

            if ($path === $raiz || str_starts_with($path, $raiz.'/')) {
                return true;
            }
        }

        return false;
    }
}
