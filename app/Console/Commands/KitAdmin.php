<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AdministradorDaInstalacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Troca e-mail e senha do administrador da INSTALAÇÃO, de propósito.
 *
 * **Por que um comando, e não o seeder.** O `UsuarioAdminSeeder` roda em todo `kit:install` e em
 * todo `db:seed`, e por isso ele **não sincroniza** credencial: se sincronizasse, quem trocou a
 * senha pela tela de perfil a veria revertida no próximo `db:seed` de qualquer outro seeder — em
 * silêncio. O seeder garante que EXISTA administrador; trocar credencial é ato deliberado, e ato
 * deliberado merece comando próprio.
 *
 * É a única coisa no kit que reescreve credencial de acesso total pela linha de comando, então:
 * pede confirmação, nunca ecoa a senha, e não grava nem senha nem e-mail em claro no log.
 */
class KitAdmin extends Command
{
    protected $signature = 'kit:admin
        {--email= : Novo e-mail do administrador}
        {--senha= : Nova senha (evite passar em linha de comando: fica no histórico do shell)}
        {--force : Aplica sem pedir confirmação}';

    protected $description = 'Troca e-mail e senha do administrador da instalação';

    public function handle(): int
    {
        $administradores = AdministradorDaInstalacao::todos();

        if ($administradores->isEmpty()) {
            $this->components->error(
                'Nenhum administrador da instalação encontrado. Rode `php artisan db:seed '
                .'--class=UsuarioAdminSeeder` (ou o `kit:install`) antes.'
            );

            return self::FAILURE;
        }

        /*
         * Mais de um `master_global` é estado possível — o papel pode ser concedido na tela de
         * papéis — e aqui é o único lugar do kit que precisa escolher UM. Escolher pelo primeiro
         * seria trocar a credencial de alguém por sorteio de ordenação, então o comando para e
         * mostra quem são. Quem tem dois administradores sabe qual quer; o comando não.
         */
        if ($administradores->count() > 1) {
            $this->components->error('Há mais de um administrador da instalação. Diga qual pela tela de usuários, ou remova o papel do que não deve ter.');
            $this->components->bulletList(
                $administradores->map(fn (User $u): string => "#{$u->getKey()} — ".Str::mask((string) $u->email, '*', 3))->all(),
            );

            return self::FAILURE;
        }

        // Sem guarda de nulo: os dois ramos acima já provaram que a coleção tem exatamente um.
        // O PHPStan confirma — a checagem seria código morto, e código morto num comando que
        // troca credencial é ruído no lugar errado.
        $admin = $administradores->first();

        $interativo = $this->input->isInteractive() && defined('STDIN') && stream_isatty(STDIN);

        [$email, $senha] = $this->coletar($admin, $interativo);

        if ($email === null && $senha === null) {
            $this->components->info('Nada a alterar.');

            return self::SUCCESS;
        }

        if ($email !== null && $this->emailEmUso($email, $admin)) {
            $this->components->error("O e-mail {$email} já pertence a outra conta. Escolha outro, ou renomeie a conta existente antes.");

            return self::FAILURE;
        }

        if (! $this->confirmar($admin, $email, $senha)) {
            $this->components->info('Cancelado. Nada foi alterado.');

            return self::SUCCESS;
        }

        $this->aplicar($admin, $email, $senha);

        return self::SUCCESS;
    }

    /**
     * As respostas — de flag quando vieram, de prompt quando há terminal.
     *
     * Sem terminal e sem flag, devolve o par nulo: um comando que reescreve credencial não
     * inventa valor por default. Diferente do `kit:install`, onde pular as perguntas é o
     * comportamento correto em CI, aqui pular significa não fazer nada.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function coletar(User $admin, bool $interativo): array
    {
        $email = $this->option('email');
        $senha = $this->option('senha');

        if (! $interativo) {
            if ($email === null && $senha === null) {
                $this->components->warn(
                    'Sem terminal: passe --email e/ou --senha. Nada foi perguntado nem alterado.'
                );
            }

            return [$this->limpar($email), $this->limpar($senha)];
        }

        if ($email === null && confirm('Trocar o e-mail?', default: false)) {
            $email = text(
                label: 'Novo e-mail',
                default: (string) $admin->email,
                required: true,
                validate: fn (string $valor): ?string => filter_var($valor, FILTER_VALIDATE_EMAIL)
                    ? null
                    : 'Informe um e-mail válido.',
            );
        }

        if ($senha === null && confirm('Trocar a senha?', default: true)) {
            // `password()` e não `text()`: a senha não aparece na tela nem no scrollback.
            $senha = password(
                label: 'Nova senha',
                required: true,
                validate: fn (string $valor): ?string => mb_strlen($valor) >= 8
                    ? null
                    : 'Use pelo menos 8 caracteres.',
            );
        }

        return [$this->limpar($email), $this->limpar($senha)];
    }

    private function limpar(mixed $valor): ?string
    {
        $texto = is_string($valor) ? trim($valor) : '';

        return $texto === '' ? null : $texto;
    }

    /**
     * O e-mail novo pertence a outra conta?
     *
     * Sem esta guarda o `save()` estoura na constraint de unicidade — erro de banco cru no
     * terminal, em vez de uma frase que diz o que fazer. E o caso é realista: convidar alguém e
     * depois querer o mesmo endereço para o administrador.
     */
    private function emailEmUso(string $email, User $admin): bool
    {
        return User::where('email', $email)
            ->whereKeyNot($admin->getKey())
            ->exists();
    }

    private function confirmar(User $admin, ?string $email, ?string $senha): bool
    {
        $this->components->twoColumnDetail('<fg=gray>Administrador</>', '#'.$admin->getKey().' — '.Str::mask((string) $admin->email, '*', 3));

        if ($email !== null) {
            $this->components->twoColumnDetail('Novo e-mail', Str::mask($email, '*', 3));
        }

        if ($senha !== null) {
            $this->components->twoColumnDetail('Nova senha', 'definida (não exibida)');
        }

        if ((bool) $this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->components->warn('Sem terminal para confirmar: use --force se é isto que você quer.');

            return false;
        }

        return confirm('Aplicar? Isto muda o acesso total à instalação.', default: false);
    }

    private function aplicar(User $admin, ?string $email, ?string $senha): void
    {
        $trocouEmail = $email !== null && $email !== $admin->email;

        if ($email !== null) {
            $admin->email = $email;

            /*
             * E-mail novo entra verificado: quem roda este comando no servidor está afirmando a
             * posse do endereço, e deixar `email_verified_at` nulo trancaria o próprio
             * administrador fora das telas que exigem verificação.
             */
            if ($trocouEmail) {
                $admin->email_verified_at = now();
            }
        }

        if ($senha !== null) {
            // O cast `hashed` do model cuida do hash; atribuir em claro aqui é o caminho normal.
            $admin->password = $senha;
        }

        $admin->save();

        /*
         * Log sem credencial: e-mail mascarado e nenhuma menção à senha além do fato de ter
         * mudado. É a mesma regra dos convites — o channel `autenticacao` é lido pelo Logs
         * Explorer do `/infra`, e o que entra ali sai numa tela.
         */
        Log::channel('autenticacao')->warning(
            "[KitAdmin@aplicar] Credencial de administrador alterada | user: {$admin->getKey()}",
            [
                'user_id'       => $admin->getKey(),
                'trocou_email'  => $trocouEmail,
                'trocou_senha'  => $senha !== null,
                'email_atual'   => Str::mask((string) $admin->email, '*', 3),
            ],
        );

        $this->components->info('Credencial atualizada.');

        if ($trocouEmail) {
            $this->components->warn('O e-mail mudou: entre com o endereço novo na próxima vez.');
        }
    }
}
