<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use App\Support\CustomizadorDaInstalacao;
use App\Support\RegistroAberto;
use App\Support\TetoDeUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
                            ->unique(),

                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->helperText('Inativo some do seletor de todos os usuários, sem perder dados.')
                            ->default(true)
                            ->columnSpanFull(),

                        /*
                         * Registro aberto DESTA organização — aqui, ao lado do `ativo`, e não
                         * numa `Section` própria: são dois booleanos da mesma natureza ("está
                         * no ar" / "aceita cadastro"), e uma seção inteira para um campo é
                         * cerimônia.
                         *
                         * Só aparece quando a instalação liberou o registro, porque o requisito
                         * amarra as duas condições ("se tiver um tenancy, **e** o register
                         * estiver liberado"). Toggle que não pode ter efeito é pior que toggle
                         * ausente: ele promete um controle que não existe.
                         */
                        Toggle::make('registro_habilitado')
                            ->label('Aceita cadastro público')
                            ->helperText('Libera /app/register?org={slug} para quem tiver o link. A pessoa nasce nesta organização, com o perfil básico do painel de negócio — e, se a instalação exigir aprovação, fica pendente até alguém liberar.')
                            ->default(false)
                            ->visible(fn (): bool => RegistroAberto::habilitado())
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
                        /*
                         * A mesma escolha do settings do kit: uma cor da paleta do Filament, da
                         * lista fechada `CustomizadorDaInstalacao::CORES` — a lista tem um dono só.
                         * O `in()` que o Filament aplica a todo Select é a barreira de dado: nome
                         * fora da lista não grava. A precedência com a cor livre ao lado é a do
                         * kit (`CorPrimaria::resolver()`): o hexadecimal vence quando preenchido.
                         */
                        Select::make('cor_primaria_nome')
                            ->label('Cor primária (paleta do Filament)')
                            ->options(array_combine(CustomizadorDaInstalacao::CORES, CustomizadorDaInstalacao::CORES))
                            ->placeholder('Cor da aplicação (padrão)')
                            ->native(false)
                            ->searchable()
                            ->helperText('A mesma lista do settings do kit. Em branco, a organização usa a cor da aplicação. A cor livre ao lado VENCE quando preenchida.'),

                        ColorPicker::make('cor_primaria')
                            ->label('Cor primária livre')
                            ->hex()
                            // `hex()` NÃO valida: ele só troca o formato do picker
                            // (vendor/filament/forms/src/Components/ColorPicker.php:31-36). Sem a
                            // regra abaixo, `roxo` e `rgb(124,58,237)` entram sem erro — e
                            // `Color::generatePalette()` não estoura com lixo: o `sscanf` falha, o
                            // chroma cai abaixo de 0.03 e a paleta inteira sai ACROMÁTICA. O painel
                            // do cliente fica cinza, sem erro em lugar nenhum.
                            // O regex é âncorado, então ele já garante os 7 caracteres exatos que a
                            // coluna `string(7)` aceita — em sqlite o excesso passaria calado, em
                            // MySQL/Postgres seria erro no save. (`ColorPicker` não tem
                            // `maxLength()`; o regex cobre os dois problemas de uma vez.)
                            ->regex('/^#[0-9A-Fa-f]{6}$/')
                            ->validationMessages([
                                'regex' => 'Informe uma cor no formato #RRGGBB.',
                            ])
                            ->helperText('Cor de marca em hexadecimal. VENCE a paleta escolhida ao lado quando preenchida. O Filament deriva as 11 tonalidades e escolhe a legível por contraste.'),

                        FileUpload::make('logo')
                            ->label('Logo')
                            // `acceptedFileTypes()` explícito, e NÃO `->image()`: o `image()` do
                            // Filament gera `acceptedFileTypes(['image/*'])`
                            // (FileUpload.php:130-134), que vira a regra `mimetypes:image/*` —
                            // e `image/svg+xml` casa com ela. (A regra `image` do Laravel, que é
                            // outra coisa, recusa SVG por padrão.)
                            //
                            // SVG aceita `<script>` embutido. Com disk público e visibility
                            // pública, o arquivo é servido pelo MESMO origin da aplicação: abrir a
                            // URL dele direto executa o script com acesso ao cookie de sessão.
                            // Exige quem já administra organizações, então é escalada de insider e
                            // não porta anônima — mas é superfície nova, e superfície nova não
                            // nasce com XSS armazenado.
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
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
                            // O teto vem da config do kit, e não de um número cravado aqui (era
                            // `1024`, 1 MB, sem nada explicando o valor). Um teto por campo são
                            // vários donos da mesma pergunta: quem instala o kit muda
                            // `KIT_UPLOAD_MAXIMO_MB` e espera que valha para todo upload. Em
                            // KILOBYTES — `->maxSize()` monta a regra `max:` do Laravel, que
                            // divide o tamanho do arquivo por 1024.
                            ->maxSize(TetoDeUpload::emKb())
                            ->validationMessages([
                                'max' => 'O arquivo passa de '.TetoDeUpload::emMb().' MB.',
                            ])
                            ->helperText('Exibida na tela de bloqueio de sessão do painel de negócio. Em branco, usa a imagem padrão. Até '.TetoDeUpload::emMb().' MB, e SVG não é aceito.')
                            // Linha inteira: com os dois campos de cor na primeira linha, a logo
                            // espremida ao lado de um vazio ficava feia.
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
