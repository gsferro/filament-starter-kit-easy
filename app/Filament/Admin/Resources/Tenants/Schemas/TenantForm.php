<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Form do tenant. Curto de propósito: o que o define aqui é o nome, o slug
 * (que vira segmento de URL) e se está no ar.
 *
 * `->unique()` simples é o correto NESTE form: o tenant não pertence a tenant
 * nenhum. Em resources escopados por tenant, a regra tem de ser
 * `->scopedUnique()` — a `unique` do Laravel não passa pelo Eloquent e ignora
 * o escopo, deixando um valor de outro tenant bloquear o cadastro.
 */
class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->description('O slug vira o endereço do painel de negócio: /app/{slug}.')
                    ->columns(2)
                    ->components([
                        TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(120)
                            // onBlur, e não a cada tecla: sugerir slug a cada
                            // letra digitada gera um request por caractere.
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Endereço no painel de negócio. Mudar invalida os links já compartilhados.')
                            ->required()
                            ->maxLength(120)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),

                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->helperText('Inativo some do seletor de todos os usuários, sem perder dados.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
