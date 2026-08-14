<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
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

                /*
                 * A identidade visual da organização.
                 *
                 * ## É AQUI que você acrescenta os campos da SUA organização
                 *
                 * CNPJ, razão social, endereço, contato, responsável — o kit não os cria de
                 * propósito: são dados de negócio, e cada instalação quer os seus, com as suas
                 * validações e o seu formato fiscal. Um kit que crava "CNPJ" não serve fora do
                 * Brasil e obriga migration de remoção em quem não quer o campo.
                 *
                 * Para acrescentar: uma migration com a coluna, o campo em `$fillable` do
                 * `App\Models\Tenant`, e o componente aqui. Ver ADR-05 da wiki
                 * `identidade-visual-da-organizacao`.
                 *
                 * ## Os dois campos abaixo são inertes quando vazios
                 *
                 * Sem cor, o painel `/app` da organização usa o default do Filament; sem logo, a
                 * tela de bloqueio usa a mídia base da aplicação. Nada quebra, nada precisa ser
                 * preenchido.
                 */
                Section::make('Identidade visual')
                    ->description('Aplicadas no painel de negócio desta organização. As demais não são afetadas.')
                    ->columns(2)
                    ->components([
                        ColorPicker::make('cor_primaria')
                            ->label('Cor primária')
                            ->hex()
                            ->helperText('O Filament deriva as 11 tonalidades desta cor e escolhe a legível por contraste. Em branco, usa a cor padrão da aplicação.'),

                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            // `disk('public')` explícito: o default é `local`, que aponta para
                            // storage/app/private e NÃO é servível por URL — a logo nasceria
                            // quebrada. O `storage:link` já roda no `kit:install`.
                            ->disk('public')
                            ->directory('organizacoes/logos')
                            // `visibility()`, e não `visible()`. O Breezy escreve `->visible('public')`
                            // no upload de avatar dele (HasMyProfile.php:64), o que é bug: `visible()`
                            // espera bool|Closure, a string é só truthy, e a visibility nunca é
                            // declarada. Funciona lá por acidente, porque o disk já é público.
                            ->visibility('public')
                            ->maxSize(1024)
                            ->helperText('Exibida na tela de bloqueio de sessão do painel de negócio. Em branco, usa a imagem padrão.'),
                    ]),
            ]);
    }
}
