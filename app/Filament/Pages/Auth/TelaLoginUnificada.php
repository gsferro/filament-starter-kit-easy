<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\ConfiguracaoDoLogin;
use App\Support\DestinoAposLogin;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * A página única de login, em `/login`, para os três painéis.
 *
 * É a mesma `TelaLogin` dos painéis (arte, anti-robô, botões sociais, recusa explicada de conta
 * indisponível), servida fora de rota de painel pelo middleware `panel:app` — o painel default
 * empresta tema, cores e o layout do Auth Designer, como a rota `boas-vindas` faz. A rota é
 * registrada SEMPRE em `KitServiceProvider::configureLoginUnificado()`; com a chave desligada,
 * esta página só redireciona para o login do painel default.
 *
 * O que muda em relação à tela de um painel é a pergunta "pode entrar?": aqui é "pode entrar em
 * ALGUM painel" (ADR-07), e o destino depois é decidido por `DestinoAposLogin` via a
 * `RespostaDeLogin` do container. Ver `wikis/specs/feat/login-unificado/`.
 */
class TelaLoginUnificada extends TelaLogin
{
    /** Redeclarado de propósito — `.ai/rules/auth.md`: sem isto o layout de auth vaza para toda página. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function mount(): void
    {
        if (! ConfiguracaoDoLogin::unificado()) {
            throw new HttpResponseException(new RedirectResponse(
                Filament::getDefaultPanel()->getLoginUrl() ?? url('/'),
            ));
        }

        // Quem já está autenticado não vê o formulário: vai para onde iria depois de entrar.
        // O pai mandaria para `Filament::getUrl()` (o /app), que um `admin` sem papel lá não acessa.
        if (($user = Filament::auth()->user()) instanceof User) {
            throw new HttpResponseException(new RedirectResponse(DestinoAposLogin::urlPara($user)));
        }

        parent::mount();
    }

    protected function ehAPaginaUnica(): bool
    {
        return true;
    }

    /**
     * Na página única, "pode entrar" é "pode entrar em ALGUM painel".
     *
     * O Filament pergunta só pelo painel corrente (`Login.php:172-179`) — aqui o `app`, por causa
     * do `panel:app` —, o que recusaria um `admin` sem papel no `/app` com "credenciais
     * inválidas". O método é chamado dentro do `Timebox` e no `attemptWhen()` do pai, então
     * rate limit, MFA e a padronização de tempo continuam os do Filament.
     */
    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return true;
        }

        if (DestinoAposLogin::paineisDe($user) !== []) {
            return true;
        }

        Log::channel('autenticacao')->warning(
            "[TelaLoginUnificada@isUserAllowedToAccessPanel] Login recusado na página única: nenhum painel acessível | user: {$user->getKey()} - ip: ".request()->ip(),
            [
                'user_id' => $user->getKey(),
                'email'   => Str::mask((string) $user->email, '*', 3),
                'ip'      => request()->ip(),
                'motivo'  => 'nenhum_painel_acessivel',
            ],
        );

        return false;
    }
}
