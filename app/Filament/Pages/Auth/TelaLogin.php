<?php

namespace App\Filament\Pages\Auth;

use App\Http\Controllers\Auth\ContaIndisponivelController;
use App\Models\User;
use App\Support\RegistroAberto;
use Caresome\FilamentAuthDesigner\Pages\Auth\Login;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;

/**
 * A tela de login dos TRÊS painéis. Duas diferenças em relação à do Auth Designer:
 *
 * 1. O link "Cadastre-se" é **condicionado ao registro aberto**. O `Login` do Filament o exibe
 *    sempre que o painel tem registro (`vendor/filament/filament/src/Auth/Pages/Login.php:445-456`),
 *    e o /app sempre tem — a rota de registro é a tela de aceite de convite. Enquanto o cadastro
 *    aberto está desligado (o default), o link levaria TODA visita a uma página que recusa. A
 *    leitura passa por `RegistroAberto`, o ponto único (ADR-02 de registro-e-aprovacao).
 *
 * 2. Conta **inativa ou excluída** que digita a senha certa não recebe o erro genérico de
 *    credenciais: é levada à página de aviso, que diz o motivo e a quem recorrer. A negação em si
 *    continua sendo `User::canAccessPanel()` — esta tela só explica. Ver `authenticate()` e as
 *    ADR-01..03 da wiki `status-e-exclusao-logica-de-usuario`.
 */
class TelaLogin extends Login
{
    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function getSubheading(): string|Htmlable|null
    {
        return RegistroAberto::habilitado() ? parent::getSubheading() : null;
    }

    /**
     * Os três campos do Filament mais o desafio anti-robô — que decide sozinho se aparece.
     *
     * Sem `if`: o campo é `->visible()` pela configuração, avaliada no render e na validação, e
     * oculto ele não é renderizado nem validado. Ver o docblock de `CampoAntiRobo`.
     */
    public function form(Schema $schema): Schema
    {
        return CampoAntiRobo::acrescentarA(parent::form($schema));
    }

    /**
     * Intercepta a falha do Filament para explicar quando a conta está indisponível.
     *
     * `try/catch` em volta do pai, e não uma reescrita dele: o `authenticate()` do Filament faz
     * rate limit, `Timebox`, MFA e dispara `Failed` — 90 linhas que continuam intactas. Toda
     * falha dele é `ValidationException` lançada por `throwFailureValidationException()`
     * (`Login.php:231-236`), que é `never` e roda dentro do `Timebox`, por isso não serve de ponto
     * de redirecionamento.
     *
     * Para o **inativo**, o pai já achou o usuário, `canAccessPanel()` negou e o `Failed` já foi
     * disparado com ele (`Login.php:105-110`) — a trilha de acessos ganhou a linha. Para o
     * **excluído**, o `retrieveByCredentials` não o acha (escopo do `SoftDeletes`), o pai disparou
     * `Failed` com usuário nulo, e o listener da trilha ignora — daí o `Failed` à mão só nesse
     * caso. Disparar nos dois duplicaria a linha do inativo.
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $excecao) {
            $conta = $this->contaIndisponivelComSenhaCerta();

            if (! $conta instanceof User) {
                throw $excecao;
            }

            $motivo = (string) $conta->motivoDeIndisponibilidade();
            $painel = Filament::getCurrentOrDefaultPanel()->getId();

            Log::channel('autenticacao')->warning(
                "[TelaLogin@authenticate] Login recusado: {$motivo} | user: {$conta->getKey()} - painel: {$painel} - ip: ".request()->ip(),
                [
                    'user_id'     => $conta->getKey(),
                    'email'       => Str::mask((string) $conta->email, '*', 3),
                    'motivo'      => $motivo,
                    'painel'      => $painel,
                    'ip'          => request()->ip(),
                    'excluida_em' => $conta->deleted_at?->toIso8601String(),
                ],
            );

            if ($conta->trashed()) {
                event(new Failed(Filament::getAuthGuard(), $conta, []));
            }

            $this->redirect(ContaIndisponivelController::redirecionar($conta, Filament::getLoginUrl() ?? url('/')));

            return null;
        }
    }

    /**
     * A conta do e-mail digitado — inclusive excluída — quando está indisponível E a senha confere.
     *
     * A senha é a prova de que quem pergunta é o dono: sem ela, o aviso "sua conta foi excluída em
     * 12/08" confirmaria a existência de uma conta para qualquer e-mail chutado (ADR-03). Senha
     * errada devolve `null`, e o chamador relança o erro genérico — exatamente o que acontece hoje.
     *
     * Dentro do mesmo `Timebox` que o Filament usa para a falha (`Login.php:89-113`): a resposta
     * demora o mesmo com ou sem `Hash::check`, e o tempo não distingue "não existe" de "existe e
     * está excluída".
     */
    private function contaIndisponivelComSenhaCerta(): ?User
    {
        $email = (string) ($this->data['email'] ?? '');
        $senha = (string) ($this->data['password'] ?? '');

        $conta = app(Timebox::class)->call(function () use ($email, $senha): ?User {
            $conta = User::withTrashed()->comEmail($email)->first();

            if (! $conta instanceof User || $conta->motivoDeIndisponibilidade() === null) {
                return null;
            }

            return Hash::check($senha, (string) $conta->password) ? $conta : null;
        }, (int) config('auth.timebox_duration', 200_000));

        return $conta instanceof User ? $conta : null;
    }
}
