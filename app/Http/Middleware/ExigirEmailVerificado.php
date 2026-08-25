<?php

namespace App\Http\Middleware;

use App\Support\RegistroAberto;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * A exigência de e-mail validado no /app, decidida POR REQUEST.
 *
 * ## O que esta classe existe para consertar
 *
 * O middleware de e-mail verificado é fixado no array da rota no momento do registro:
 *
 *     ...(static::isEmailVerificationRequired($panel) ? [static::getEmailVerifiedMiddleware($panel)] : []),
 *     — vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91
 *
 * O `?:` é avaliado UMA vez, no boot, e o resultado fica gravado. Por isso
 * `registro_verificar_email` não podia virar Settings: o valor do banco chega depois (o painel é
 * montado antes de `ConfiguracoesDoKit::aplicarNaConfig()`), e mudar a config em runtime não
 * reavalia um array de rota já registrado. Nem Closure em `isRequired` resolve — quem chama
 * `isEmailVerificationRequired()` é o REGISTRO da rota, não o request.
 *
 * A saída não é combater o array fixo, é mudar **o que está fixado nele**. O
 * `AppPanelProvider` aplica a exigência SEMPRE e declara esta classe em
 * `->emailVerifiedMiddlewareName()`: o array da rota deixa de guardar uma decisão e passa a
 * guardar um decisor. A pergunta é feita aqui, a cada request, ao ponto único
 * (`RegistroAberto::exigirVerificacaoDeEmail()`), que lê `config('kit.registro.verificar_email')`
 * — já sobreposta pelo banco no boot do `KitServiceProvider`.
 *
 * Consequência que NÃO pode ser desfeita: a rota de destino precisa nascer sempre. Ver a nota do
 * `->emailVerification()` no `AppPanelProvider`.
 *
 * ## Por que herdar em vez de reimplementar
 *
 * O `handle()` do Laravel tem 10 linhas e três sutilezas que uma cópia apressada perde
 * (`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:31-42`):
 * quem NÃO implementa `MustVerifyEmail` passa, quem espera JSON recebe 403 em vez de um HTML de
 * redirecionamento — do que dependem as requisições AJAX do Livewire dentro do painel — e
 * `Redirect::guest()` guarda a URL pretendida, o que devolve a pessoa ao destino depois de
 * validar. Nada disso é reescrito: só a guarda é nossa.
 *
 * Visitante anônimo não chega até aqui: as rotas de página do painel nascem dentro do
 * `Route::middleware($panel->getAuthMiddleware())` (`vendor/filament/filament/routes/web.php:60`),
 * então o `Authenticate` do Filament já o mandou para o login.
 *
 * Ver ADR-01 e ADR-02 em wikis/specs/feat/verificacao-de-email-editavel/.
 */
final class ExigirEmailVerificado extends EnsureEmailIsVerified
{
    /**
     * A guarda, e só ela.
     *
     * `ponytail:` a decisão da feature é este `if`. Não vire isto numa política, numa interface
     * nem num enum de modos de verificação — o que se quer é UM lugar onde a pergunta é feita
     * por request.
     *
     * @param  string|null  $redirectToRoute  o nome da rota de destino, que o Filament concatena
     *                                        em `Panel::getEmailVerifiedMiddleware()`
     */
    public function handle($request, Closure $next, $redirectToRoute = null): Response
    {
        if (! RegistroAberto::exigirVerificacaoDeEmail()) {
            return $next($request);
        }

        $this->registrarBarramento($request);

        return parent::handle($request, $next, $redirectToRoute);
    }

    /**
     * A trilha de quem foi barrado — e só de quem foi barrado.
     *
     * O caminho liberado NÃO loga de propósito: ele é todo request de todo usuário do /app, e
     * log ali é ruído que enche o disco e esconde o sinal. Este é o registro que responde à
     * pergunta que o suporte recebe quando alguém liga a opção sem ler o aviso — "o painel parou
     * de abrir".
     *
     * A condição é a mesma do pai, reafirmada aqui em vez de deduzida do retorno: deduzir
     * exigiria inspecionar a Response, e uma mudança no vendor tornaria o log silenciosamente
     * errado.
     */
    private function registrarBarramento(Request $request): void
    {
        $autenticado = $request->user();

        if (! $autenticado instanceof MustVerifyEmail || $autenticado->hasVerifiedEmail()) {
            return;
        }

        Log::channel('autenticacao')->warning(
            "[ExigirEmailVerificado@handle] Acesso ao /app barrado por e-mail nao validado | user: {$autenticado->getAuthIdentifier()}",
            [
                'user_id' => $autenticado->getAuthIdentifier(),
                'email'   => Str::mask($autenticado->getEmailForVerification(), '*', 3),
                'rota'    => $request->route()?->getName(),
                'ip'      => $request->ip(),
            ],
        );
    }
}
