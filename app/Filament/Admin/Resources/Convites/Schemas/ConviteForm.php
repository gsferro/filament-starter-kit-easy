<?php

namespace App\Filament\Admin\Resources\Convites\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Support\Config;

/**
 * Form do convite: e-mail, papel e (com tenancy) organização. Três campos, porque são as
 * três decisões que quem convida toma no lugar de quem vai aceitar.
 *
 * O prazo não é campo: sai de `kit.convites.validade_em_dias` e é gravado por
 * `Convite::enviar()`, no `afterCreate()` da página.
 */
class ConviteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quem, e com qual acesso')
                    ->description('O convite vale por um link de uso único, enviado para o e-mail abaixo.')
                    ->columns(2)
                    ->components([
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            /*
                             * SEM `->unique('users', 'email')`, e isto é a feature.
                             *
                             * Até a v0.11.0 o campo recusava endereço que já tinha conta, o
                             * que fazia do convite uma parede no caso mais comum de SaaS
                             * multi-tenant. Agora o endereço com conta vira OFERTA DE
                             * ACESSO: nenhuma conta nova é criada, a pessoa confirma
                             * autenticada e é vinculada à organização com este papel.
                             */
                            ->helperText('Se o endereço já tiver conta, ninguém é cadastrado de novo: a pessoa recebe uma oferta para entrar nesta organização e escolhe aceitar ou recusar. Com MAIL_MAILER=log o e-mail só é escrito em storage/logs.')
                            ->columnSpanFull(),

                        Select::make('role_id')
                            ->label('Papel')
                            ->relationship('papel', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            // `->live()` porque o campo de organização depende do painel
                            // deste papel. O parâmetro TEM de se chamar `$record`: o
                            // Filament injeta closure de opção por NOME, não por tipo.
                            ->live()
                            ->getOptionLabelFromRecordUsing(function (Model $record): string {
                                $painel = $record->getAttribute('painel');

                                return is_string($painel)
                                    ? "{$record->getAttribute('name')} — /{$painel}"
                                    : "{$record->getAttribute('name')} — sem painel";
                            })
                            ->helperText('É o papel que dá acesso ao painel — quem aceitar nasce com ele.'),

                        Select::make('tenant_id')
                            ->label(config('kit.tenancy.label', 'Organização'))
                            ->relationship('tenant', 'nome')
                            ->preload()
                            ->searchable()
                            ->visible(fn (): bool => (bool) config('kit.tenancy.enabled'))
                            /*
                             * Sem isto o convite nasceria com papel do /app e sem
                             * organização, e o aceite atribuiria o papel no contexto
                             * global: alguém que entra no painel de negócio e não
                             * enxerga organização nenhuma.
                             */
                            ->required(fn (Get $get): bool => (bool) config('kit.tenancy.enabled')
                                && self::painelDoPapel($get('role_id')) === 'app')
                            ->helperText('Papel do painel de negócio precisa de uma organização: é nela que o papel será atribuído.'),
                    ]),
            ]);
    }

    /**
     * O painel declarado pelo papel escolhido.
     *
     * Lido por query e não por type hint concreto: a classe do papel sai de
     * `permission.models.role` em runtime.
     */
    private static function painelDoPapel(mixed $roleId): ?string
    {
        if (blank($roleId)) {
            return null;
        }

        $modelo = Config::roleModel();
        $painel = $modelo::query()->whereKey($roleId)->value('painel');

        return is_string($painel) ? $painel : null;
    }
}
