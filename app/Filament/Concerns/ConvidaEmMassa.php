<?php

namespace App\Filament\Concerns;

use App\Models\Convite;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * A ação de header que convida vários endereços de uma vez.
 *
 * Trait, e não classe de Action, porque o campo de PAPEL é legitimamente diferente em cada
 * painel: no /admin ele oferece todos os papéis; no /app só os de `painel = 'app'`, com a
 * trava de servidor. O painel injeta esse campo; o trait cuida de tudo que NÃO pode divergir
 * entre os dois — o parser, o limite, a chamada do lote e o resumo. Duas cópias do resumo é
 * como um painel passa a esconder as falhas que o outro mostra.
 */
trait ConvidaEmMassa
{
    /**
     * @param  Select  $papel  o campo de papel deste painel
     * @param  bool  $escolheOrganizacao  true no /admin (campo no form); false no /app, onde a
     *                                    organização vem do painel e nunca do payload
     */
    protected function acaoDeConvidarEmMassa(Select $papel, bool $escolheOrganizacao = false): Action
    {
        $limite = (int) config('kit.convites.limite_do_lote', 100);
        $rotulo = mb_strtolower((string) config('kit.tenancy.label', 'Organização'));

        return Action::make('convidarEmMassa')
            ->label('Convidar em massa')
            ->icon(Heroicon::OutlinedUserGroup)
            /*
             * Esconde E recusa para quem não tem Create:Convite. Sem esta linha a ação
             * apareceria para quem só tem ViewAny:Convite — affordance sem permissão, que
             * `wikis/convencoes.md` chama de bug. `CreateAction::make()` consulta
             * `canCreate()` sozinho; um `Action::make()` cru não consulta nada.
             */
            ->authorize('create', Convite::class)
            ->modalHeading('Convidar em massa')
            ->modalDescription('Um papel e uma '.$rotulo.' para o lote inteiro. Um endereço com problema não impede os outros.')
            ->modalSubmitActionLabel('Enviar convites')
            ->schema([
                /*
                 * SEM `->email()` e SEM `->nestedRecursiveRules(['email'])`, e não é
                 * esquecimento: validação de formato no formulário reprova a MODAL INTEIRA, e
                 * o lote deixa de ter resultado parcial — que é a feature. O formato é
                 * decidido endereço por endereço dentro de `Convite::convidarEmMassa()`.
                 */
                Textarea::make('emails')
                    ->label('E-mails')
                    ->required()
                    ->rows(8)
                    ->helperText("Um por linha, ou separados por vírgula. Até {$limite} por lote. Endereços repetidos são ignorados.")
                    ->columnSpanFull(),

                $papel,

                // Só no /admin, e só com tenancy — o mesmo par de condições do ConviteForm.
                ...($escolheOrganizacao ? [
                    Select::make('tenant_id')
                        ->label(config('kit.tenancy.label', 'Organização'))
                        ->relationship('tenant', 'nome')
                        ->preload()
                        ->searchable()
                        ->visible(fn (): bool => (bool) config('kit.tenancy.enabled')),
                ] : []),
            ])
            // Sem `->requiresConfirmation()`: a modal de formulário já é a confirmação.
            ->action(function (array $data, Action $action) use ($escolheOrganizacao, $limite, $rotulo): void {
                $emails = Convite::separarEmails($data['emails'] ?? null);

                /*
                 * O limite ABORTA, e é a única coisa nesta feature que aborta: um endereço com
                 * problema é dado ruim dentro de uma entrada válida, mas lote acima do limite é
                 * a ENTRADA inválida, e o certo é não começar. `halt()` mantém a modal ABERTA —
                 * sem isso a pessoa perde as cem linhas que acabou de colar.
                 */
                if ($emails->count() > $limite) {
                    Notification::make()
                        ->title('Lote acima do limite')
                        ->body("Você informou {$emails->count()} endereços e o limite é {$limite}. Nenhum convite foi enviado.")
                        ->danger()
                        ->persistent()
                        ->send();

                    $action->halt();
                }

                $tenantId = $escolheOrganizacao
                    ? ($data['tenant_id'] ?? null)
                    : Filament::getTenant()?->getKey();

                /*
                 * Fail-closed no /app: sem organização corrente o convite nasceria sem
                 * `tenant_id`, e o aceite atribuiria o papel no contexto global — trinta
                 * pessoas dentro do painel de negócio sem organização nenhuma. Mesmo padrão do
                 * `getEloquentQuery()` do ConviteResource deste painel.
                 */
                if (! $escolheOrganizacao && $tenantId === null) {
                    Log::channel('autenticacao')->warning(
                        '[ConvidaEmMassa@acaoDeConvidarEmMassa] Lote recusado, sem organização corrente | painel: app',
                        ['executor_id' => Auth::id(), 'motivo' => 'sem_tenant_corrente', 'recebidos' => $emails->count()],
                    );

                    Notification::make()
                        ->title('Sem '.$rotulo.' corrente')
                        ->body('Nenhum convite foi enviado. Entre por uma '.$rotulo.' e tente de novo.')
                        ->danger()
                        ->persistent()
                        ->send();

                    $action->halt();
                }

                /*
                 * `Auth::user()?->id` e não `Auth::id()`: o segundo é tipado `int|string|null`
                 * (o identificador de um guard pode ser uuid), e `convite.convidado_por_id` é
                 * FK inteira. Ler pelo model é o que amarra o valor à coluna de verdade —
                 * alargar a assinatura de `convidarEmMassa()` deixaria passar um uuid até a FK.
                 */
                $this->notificarResultadoDoLote(Convite::convidarEmMassa(
                    $emails,
                    (int) $data['role_id'],
                    $tenantId === null ? null : (int) $tenantId,
                    Auth::user()?->id,
                ));
            });
    }

    /**
     * O resumo na tela: quantos saíram, e uma linha por endereço que não saiu.
     *
     * `->persistent()` porque um resumo que some em seis segundos é inútil quando lista doze
     * falhas. `success` só quando não houve falha nenhuma.
     *
     * @param  array{enviados: list<string>, falhas: list<array{email: string, motivo: string}>}  $resultado
     */
    private function notificarResultadoDoLote(array $resultado): void
    {
        $enviados = count($resultado['enviados']);
        $falhas   = count($resultado['falhas']);

        $notificacao = Notification::make()
            ->title($falhas === 0
                ? "{$enviados} convite(s) enviado(s)"
                : "{$enviados} convite(s) enviado(s), {$falhas} não enviado(s)")
            ->body(collect($resultado['falhas'])
                ->map(fn (array $falha): string => $falha['email'].' — '.$this->motivoLegivel($falha['motivo']))
                ->implode("\n"))
            ->persistent();

        ($falhas === 0 ? $notificacao->success() : $notificacao->warning())->send();
    }

    /**
     * Os cinco motivos, em pt-BR, num lugar só.
     *
     * Este `match` é o motivo de não existir Enum: a string só é traduzida aqui, e o `default`
     * cobre `erro_no_envio` e qualquer motivo futuro sem quebrar a tela.
     */
    private function motivoLegivel(string $motivo): string
    {
        return match ($motivo) {
            'formato_invalido' => 'endereço inválido',
            'convite_pendente' => 'já tem convite pendente',
            'recusou_antes'    => 'recusou o convite anterior',
            'ja_e_membro'      => 'já faz parte desta '.mb_strtolower((string) config('kit.tenancy.label', 'Organização')),
            default            => 'falha no envio — veja o log de autenticação',
        };
    }
}
