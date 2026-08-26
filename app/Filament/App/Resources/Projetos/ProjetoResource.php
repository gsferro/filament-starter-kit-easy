<?php

namespace App\Filament\App\Resources\Projetos;

use App\Filament\App\Resources\Projetos\Pages\ListProjetos;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\Projeto;
use App\Models\Tenant;
use App\Support\TetoDeUpload;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * DEMONSTRAÇÃO — o primeiro (e único) resource do painel /app, criado por
 * `php artisan kit:tenancy --demo`.
 *
 * O painel /app nasce vazio de propósito; este resource existe só para você
 * VER o isolamento funcionando: entre em `/app/acme` e `/app/globex` com o
 * mesmo usuário e compare as listagens.
 *
 * Repare no que NÃO está aqui: nenhum `where('tenant_id', ...)`. O recorte vem
 * de duas camadas que se sobrepõem — o escopo do Filament (porque a model tem
 * a relação `tenant()`) e o escopo global da trait `BelongsToTenant`, que
 * também cobre as queries fora de resources.
 *
 * O formulário usa `->scopedUnique()`: a regra `unique` do Laravel não passa
 * pelo Eloquent e ignoraria o tenant, deixando o nome usado por OUTRO cliente
 * bloquear o cadastro aqui.
 *
 * Descartável: apague esta pasta junto com o resto da demo.
 */
class ProjetoResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = Projeto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Projeto';

    protected static ?string $pluralModelLabel = 'Projetos';

    protected static ?string $recordTitleAttribute = 'nome';

    /**
     * Só existe quando a demo existe.
     *
     * O painel /app nasce VAZIO de propósito: ninguém sabe o que o seu projeto vai
     * construir, e um resource de exemplo no menu de um projeto de verdade é lixo
     * que alguém vai ter de limpar — provavelmente depois de perguntar de onde
     * veio.
     *
     * As duas condições são uma só ideia: a demo é o cenário de MULTI-ORGANIZAÇÃO,
     * e um Projeto sem tenant não demonstra o isolamento que ele existe para
     * mostrar. Com a tenancy desligada, o resource não teria nem escopo.
     *
     * Espelha `UserResource` e `ConviteResource` do mesmo painel, que já se
     * escondem sem tenancy.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return self::daDemo();
    }

    /**
     * Some do menu E da URL.
     *
     * `shouldRegisterNavigation()` sozinho tiraria só o item do menu: a rota
     * continuaria de pé, e a busca ⌘K continuaria oferecendo "Criar projeto" —
     * affordance para uma tela que não deveria existir naquele projeto.
     */
    public static function canAccess(): bool
    {
        return self::daDemo() && parent::canAccess();
    }

    private static function daDemo(): bool
    {
        return (bool) config('kit.tenancy.enabled') && (bool) config('kit.demo');
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nome'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(120)
                // scopedUnique, não unique: a regra do Laravel ignora o tenant.
                ->scopedUnique(),

            /*
             * A camada de mídia do kit, demonstrada no único lugar em que cabe.
             *
             * O nome do campo é o da COLEÇÃO declarada em `Projeto::registerMediaCollections()`,
             * não o de uma coluna: `spatie/laravel-medialibrary` guarda tudo na tabela
             * polimórfica `media`. Por isso `projetos` não ganhou coluna nenhuma para isto.
             *
             * O isolamento por organização vem de graça: o arquivo pertence a ESTE projeto,
             * e o projeto já é escopado por `BelongsToTenant`. Não há coluna de tenant em
             * `media` nem configuração a lembrar.
             *
             * `->visibility('private')` PARTICIPA da proteção, mas não é ela: o componente
             * só usa a visibilidade para escolher o disco quando o default seria público
             * (`SpatieMediaLibraryFileUpload::getDiskName()`). Quem decide se o arquivo sai
             * sem sessão é o DISCO — e ele está declarado em
             * `Projeto::registerMediaCollections()` com `useDisk('local')`, cujo `serve`
             * obriga URL assinada. Ver o aviso em config/media-library.php.
             */
            SpatieMediaLibraryFileUpload::make('anexos')
                ->label('Anexos')
                ->collection('anexos')
                ->multiple()
                ->reorderable()
                ->openable()
                ->downloadable()
                ->visibility('private')
                /*
                 * O teto vem da config do kit, não de um `10 * 1024` cravado aqui: quem
                 * instala muda `KIT_UPLOAD_MAXIMO_MB` e espera que valha para todo
                 * upload. Em KILOBYTES — `->maxSize()` monta a regra `max:` do Laravel,
                 * que divide o tamanho do arquivo por 1024. Ver `App\Support\TetoDeUpload`.
                 */
                ->maxSize(TetoDeUpload::emKb())
                /*
                 * SVG fora, e por que a forma é diferente aqui.
                 *
                 * Nos campos de IMAGEM do kit a barreira é `->rule('image')`, a regra do
                 * Laravel, que é uma allow-list de nove extensões de imagem. Aqui ela
                 * recusaria PDF e planilha, que é a razão deste campo existir — e
                 * `acceptedFileTypes()` teria o mesmo efeito, porque é allow-list também.
                 * O que se quer é recusar UM formato, então a regra recusa um formato.
                 *
                 * ⚠️ O `Closure` vem DENTRO de outro `Closure`, e não é estilo: o
                 * `getValidationRules()` do Filament faz `$rule = $this->evaluate($rule)`
                 * (vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:872),
                 * então uma regra passada crua é AVALIADA com injeção de utilitários em vez
                 * de entregue ao validador — e a tela morre com "an attempt was made to
                 * evaluate a closure ... but [$atributo] was unresolvable". O wrapper faz o
                 * `evaluate()` devolver a regra. Quem pegou isso foi CT-12; o campo abria
                 * normalmente e só quebrava no envio.
                 *
                 * O `Closure` é validado por ARQUIVO, não pelo array: o
                 * `isArrayValidationRule()` do Filament só classifica como regra de array
                 * as STRINGS de uma lista fechada
                 * (vendor/filament/forms/src/Components/BaseFileUpload.php:101-114,776-785),
                 * e o resto vai para o validador aninhado `["{$name}.*" => ['file', ...]]`
                 * (mesma classe, :752-763). Por isso o campo é `->multiple()` e a regra
                 * ainda vê um `TemporaryUploadedFile` por vez.
                 *
                 * O `getMimeType()` desse objeto lê o DISCO temporário, não o cabeçalho do
                 * cliente (`vendor/livewire/livewire/.../TemporaryUploadedFile.php:63-90`),
                 * então renomear o `.svg` para `.png` não passa. Ver ADR-03 da wiki
                 * `upload-limite-e-tipos` para o que isso significa no teste.
                 */
                ->rule(static fn (): Closure => static function (string $atributo, mixed $arquivo, Closure $falhar): void {
                    if ($arquivo instanceof TemporaryUploadedFile && $arquivo->getMimeType() === 'image/svg+xml') {
                        $falhar('SVG não é aceito: o formato carrega script e o anexo é servido pela própria aplicação.');
                    }
                })
                ->helperText('Até '.TetoDeUpload::emMb().' MB por arquivo, e SVG não é aceito. Os anexos pertencem a este projeto e só são vistos por quem alcança a organização dele.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),

                /*
                 * `simpleLightbox()` é macro do solution-forest/filament-simplelightbox,
                 * registrado por PAINEL — e funciona aqui sem uma linha de cola porque
                 * `SpatieMediaLibraryImageColumn extends ImageColumn`, que é exatamente
                 * a classe onde o macro é declarado. O `Macroable` do Filament resolve
                 * subindo por `class_parents()`.
                 */
                SpatieMediaLibraryImageColumn::make('anexos')
                    ->label('Anexos')
                    ->collection('anexos')
                    ->conversion('miniatura')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->simpleLightbox(),

                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                /*
                 * Apagar aqui é REVERSÍVEL desde que `Projeto` ganhou `SoftDeletes`: o
                 * registro sai da lista e aparece na Lixeira do /infra, restaurável por
                 * quem tem `master_global` ou `infra`.
                 *
                 * Sem `ForceDeleteAction` nem `RestoreAction` na tela: quem restaura é a
                 * Lixeira, num painel de acesso mais estreito. Duas portas para o mesmo
                 * ato dariam ao usuário do /app o poder de desfazer a exclusão de outro.
                 */
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhum projeto aqui')
            ->emptyStateDescription('Cada registro pertence ao tenant selecionado no topo — troque de tenant e a lista muda.');
    }

    /**
     * Sem organização corrente a query FECHA — como nos dois irmãos deste painel.
     *
     * ## Por que a trait não basta
     *
     * `Projeto` usa `BelongsToTenant`, e o escopo global dela recorta por `Filament::getTenant()`.
     * Só que ele **falha aberto por desenho**: sem tenant, o `if` não entra e nenhum `where` é
     * aplicado (`app/Traits/BelongsToTenant.php:64-70`, declarado em `:48-52`). Em request de
     * painel isso nunca acontece — o middleware de tenancy identifica a organização antes. Fora
     * dele — job, comando, busca global sem contexto — este resource devolvia **todos** os projetos
     * de **todas** as organizações. Medido na auditoria de aderência ao Blueprint: 4 de 4, enquanto
     * `UserResource` e `ConviteResource` do mesmo painel devolviam 0.
     *
     * A auditoria do Blueprint (N-04) pegou a assimetria: três resources no mesmo painel, dois
     * fechando e um não, e nada escrito decidindo. Agora os três falam a mesma língua — o
     * `whereRaw('1 = 0')` é o mesmo de `App/Users/UserResource.php`, e a rule
     * `.ai/rules/filament.md` passa a exigir isto de todo resource do /app.
     *
     * Com tenant, delega ao pai: é o escopo global da trait (e o do Filament) quem recorta, e
     * duplicar o `where` aqui seria o "recorte na query da tela" que a rule proíbe.
     */
    public static function getEloquentQuery(): Builder
    {
        if (! Filament::getTenant() instanceof Tenant) {
            Log::channel('autenticacao')->warning(
                '[ProjetoResource@getEloquentQuery] Consulta de projetos sem organização corrente — recorte fechado | painel: app',
                [
                    'painel'      => 'app',
                    'executor_id' => Auth::id(),
                    'motivo'      => 'sem_tenant_corrente',
                ],
            );

            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjetos::route('/'),
        ];
    }
}
