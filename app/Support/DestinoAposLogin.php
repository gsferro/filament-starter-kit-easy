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
     * Restrição de painéis herdada do login social: `kit.login.{provedor}.paineis`. Quem entrou
     * pelo GitHub liberado só no /infra não pode ser entregue no /admin só porque tem papel lá.
     * Gravada por `restringirAosPaineisAutorizados()`, lida por `paineisDe()`, apagada no carimbo.
     *
     * @var string
     */
    public const SESSAO_PAINEIS_PERMITIDOS = 'login_unificado.paineis_permitidos';

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
        $permitidos = session()->get(self::SESSAO_PAINEIS_PERMITIDOS);

        return array_values(array_filter(
            Filament::getPanels(),
            fn (Panel $painel): bool => (! is_array($permitidos) || in_array($painel->getId(), $permitidos, true))
                && $user->canAccessPanel($painel),
        ));
    }

    /**
     * O login social restringe os destinos aos painéis em que o provedor está autorizado
     * (`ConfiguracaoDoLogin::painelAutorizado()`). Lista vazia no config significa todos — aí
     * nada é gravado. Achado Medium da auditoria Blueprint: sem isto, a barreira por painel do
     * login social valia só na ida com `?painel=`, e a página única não envia painel.
     */
    public static function restringirAosPaineisAutorizados(ProvedorSocial $provedor): void
    {
        $autorizados = array_values(array_filter(
            array_keys(Filament::getPanels()),
            fn (string $id): bool => ConfiguracaoDoLogin::painelAutorizado($provedor, $id),
        ));

        if (count($autorizados) === count(Filament::getPanels())) {
            session()->forget(self::SESSAO_PAINEIS_PERMITIDOS);

            return;
        }

        session()->put(self::SESSAO_PAINEIS_PERMITIDOS, $autorizados);
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
            count($paineis) === 1                => Paineis::url($paineis[0]),
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
     * Esquece a URL pretendida quando ela não é de um painel que a pessoa acessa.
     *
     * Serve ao CADASTRO com a página única desligada, onde a escolha de painel não existe e o
     * destino continua sendo o do Filament (`redirect()->intended(Filament::getUrl())`): a única
     * coisa errada ali é a pretendida, então é ela que sai. Ver `RespostaDeCadastro` e
     * `wikis/specs/fix/destino-apos-cadastro/` (ADR-04).
     *
     * Mora aqui, e não na resposta, porque a pergunta "esta URL é de painel acessível?" é de
     * `painelDe()` — privada de propósito: é nela que vivem as duas defesas de URL da auditoria
     * Blueprint. Duplicar a checagem fora daqui espalharia superfície de segurança.
     */
    public static function descartarPretendidaInacessivel(User $user): void
    {
        $pretendida = (string) session()->get('url.intended', '');

        if ($pretendida === '') {
            return;
        }

        $paineis = self::paineisDe($user);

        if (self::painelDe($pretendida, $paineis) instanceof Panel) {
            return;
        }

        session()->forget('url.intended');

        Log::channel('autenticacao')->warning(
            "[DestinoAposLogin@descartarPretendidaInacessivel] URL pretendida descartada — nao e painel acessivel | user: {$user->getKey()}",
            [
                'user_id'    => $user->getKey(),
                'paineis'    => array_map(fn (Panel $painel): string => $painel->getId(), $paineis),
                'pretendida' => parse_url($pretendida, PHP_URL_PATH),
                'motivo'     => 'pretendida_inacessivel',
            ],
        );
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

                return Paineis::url($painel);
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
        session()->forget([self::SESSAO_EM_CURSO, self::SESSAO_PAINEIS_PERMITIDOS]);

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
     * Aceita só duas formas: URL absoluta que começa exatamente por `url('/')` mais barra, ou path
     * relativo que começa por uma barra e não por `//` nem `/\`. Comparar só o host do
     * `parse_url()` aceitava `https://evil.com\@host/admin` e `javascript://host/...` (achado da
     * auditoria Blueprint). E o path precisa ser a raiz do painel ou começar por ela COM a barra:
     * `/administracao/x` não é `/admin`.
     *
     * @param  list<Panel>  $paineis
     */
    private static function painelDe(string $url, array $paineis): ?Panel
    {
        $raizDoApp = rtrim(url('/'), '/').'/';

        if (str_starts_with($url, $raizDoApp)) {
            $url = '/'.substr($url, strlen($raizDoApp));
        }

        if (preg_match('#^/(?![/\\\\])#', $url) !== 1) {
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
