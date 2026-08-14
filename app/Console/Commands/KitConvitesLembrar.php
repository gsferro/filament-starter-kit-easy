<?php

namespace App\Console\Commands;

use App\Models\Convite;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Faz o convite cobrar a si mesmo: um lembrete por convite pendente, nos prazos de
 * `config('kit.convites.lembretes_dias')`.
 *
 * **Ele NÃO chama `Convite::enviar()`, e é a decisão inteira da feature.** `enviar()`
 * rotaciona o token e renova o prazo: o link que a pessoa já tem na caixa de entrada
 * morreria, e um lembrete que caísse no spam teria REVOGADO o único link válido. `lembrar()`
 * manda um SEGUNDO link, paralelo — nada é invalidado, nenhum prazo é renovado. Ver ADR-01
 * em `wikis/specs/main/lembretes-de-convite/02-decisoes-arquiteturais.md`.
 *
 * Sai **um** lembrete por convite por execução, por construção (há uma única chamada de
 * `lembrar()` no laço): cron parado uma semana se recupera nas execuções seguintes em vez de
 * disparar uma rajada. Ver ADR-03.
 *
 * Agendado em `routes/console.php`, e inerte numa instalação nova: sem convite pendente não
 * manda nada, e com `MAIL_MAILER=log` (o default do kit) o e-mail vai para `storage/logs`.
 */
class KitConvitesLembrar extends Command
{
    protected $signature = 'kit:convites-lembrar';

    protected $description = 'Envia lembrete dos convites pendentes nos prazos configurados';

    public function handle(): int
    {
        /** @var list<int> $dias */
        $dias = config('kit.convites.lembretes_dias', []);

        if ($dias === []) {
            $this->components->info('Lembretes de convite desligados (kit.convites.lembretes_dias vazio).');

            return self::SUCCESS;
        }

        $enviados = 0;

        Convite::query()
            ->whereNull('aceito_em')
            // Convite recusado não recebe lembrete: "ela disse não" é diferente de "ela não
            // viu", e insistir com quem recusou é o pior comportamento desta feature.
            ->whereNull('recusado_em')
            // Expirado também não. Não há coluna de status: o estado é derivado.
            ->where('expira_em', '>', now())
            // O teto, do lado do banco: quem já recebeu todos sai do lote.
            ->where('lembretes_enviados', '<', count($dias))
            // Nunca casa com `enviado_em` NULL — é o que exclui convite anterior à migration
            // sem precisar de um `whereNotNull` a mais. Ver ADR-02.
            ->where('enviado_em', '<=', now()->subDays(min($dias)))
            /*
             * `chunkById(100)`, nunca `->get()`: é o defeito literal do
             * `markExpiredInvitations()` do laravel-invite-only. E `chunkById` (não
             * `chunk()`) é o que torna seguro mutar, durante a iteração, a própria coluna
             * que a query filtra — as páginas são faixas disjuntas de `id`.
             */
            ->chunkById(100, function (Collection $convites) use ($dias, &$enviados): void {
                foreach ($convites as $convite) {
                    /*
                     * Quantos lembretes JÁ eram devidos até hoje. Mandou menos que isso?
                     * Manda UM. É toda a lógica: um por convite por execução por construção,
                     * e o dia em que o cron não rodou se recupera nas execuções seguintes,
                     * sem rajada. Ver ADR-03.
                     */
                    $devidos = count(array_filter(
                        $dias,
                        fn (int $prazo): bool => (bool) $convite->enviado_em?->addDays($prazo)->isPast(),
                    ));

                    if ($devidos <= $convite->lembretes_enviados) {
                        continue;
                    }

                    try {
                        $convite->lembrar();
                        $enviados++;
                    } catch (Throwable $e) {
                        /*
                         * Um convite com endereço quebrado não pode derrubar o lote: o
                         * `chunkById` ordena por id, então um id baixo estragado deixaria
                         * todos os outros sem lembrete em TODA execução — starvation
                         * silenciosa. E o comando não vira FAILURE por causa disso: um cron
                         * que sai com erro por um endereço inválido gera alarme falso diário.
                         */
                        Log::channel('autenticacao')->warning(
                            "[KitConvitesLembrar@handle] Falha ao lembrar convite | convite: {$convite->id}",
                            [
                                'convite_id' => $convite->id,
                                'email'      => Str::mask($convite->email, '*', 3),
                                'exception'  => $e,
                            ],
                        );
                    }
                }
            });

        $this->components->info($enviados === 0
            ? 'Nenhum convite pendente para lembrar.'
            : "{$enviados} lembrete(s) de convite enviado(s).");

        Log::channel('autenticacao')->info(
            "[KitConvitesLembrar@handle] Lembretes de convite processados | total: {$enviados}",
            [
                'total' => $enviados,
                'dias'  => $dias,
            ],
        );

        return self::SUCCESS;
    }
}
