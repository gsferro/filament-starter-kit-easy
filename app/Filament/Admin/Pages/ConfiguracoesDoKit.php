<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\CustomizadorDaInstalacao;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

/**
 * A tela das configurações da INSTALAÇÃO, em /admin/configuracoes-do-kit.
 *
 * O que era pergunta do `kit:install` gravada no `.env`, mais a arte do login
 * (que era edição de arquivo à mão) e os defaults de tabela (que eram um TODO no
 * `ConfiguraFilamentGlobal`), passam a ser alteráveis aqui. O valor gravado vence
 * o `.env` em tempo de execução — ver o docblock de `App\Settings\ConfiguracoesDoKit`
 * e ADR-01 da wiki `settings-do-kit`.
 *
 * **Isto não é o settings de uma organização.** A identidade visual de um tenant
 * é CRUD em /admin/organizacoes, e ela vence esta dentro de /app/{slug}.
 *
 * ## Autorização: uma permissão só
 *
 * `HasPageShield` liga a Page à permission `View:ConfiguracoesDoKit`, que o
 * `ShieldPermissionsSeeder` gera e o `PapeisSeeder` entrega ao papel `admin`
 * junto com o resto da matriz do painel — nenhuma lista precisou ser editada.
 *
 * `canEdit()` devolve `canAccess()`: uma permissão governa abrir e salvar. Um
 * papel "só leitura" aqui seria um papel que LÊ a senha de SMTP, porque o
 * `canEdit()` do plugin não esconde valor nenhum — o README do vendor diz isso
 * por escrito, e o código confirma (`save()` faz `if (! $this->canEdit()) return;`
 * e `defaultForm()` faz `->disabled(! $this->canEdit())`, nenhum dos dois oculta).
 * Ver ADR-04.
 *
 * **Se esta Page for movida para o painel `app`**, ela PRECISA entrar em
 * `PapeisSeeder::permissoesDeAdministracaoDoApp()`, senão todo `panel_user` herda
 * a configuração da instalação, sem erro nenhum. Ver `.ai/rules/filament.md`.
 *
 * ## Por que este arquivo foi escrito à mão
 *
 * `php artisan make:filament-settings-page ConfiguracoesDoKit ConfiguracoesDoKit`
 * **falha**: o gerador do plugin monta os imports em
 * `SettingsPageClassGenerator::getImports()` e, quando o basename da classe de
 * settings é igual ao da Page, cai num `PhpNamespace::addUse("")` e estoura.
 * Daí o alias `SettingsDoKit` no `use` acima — e o registro deste parágrafo, para
 * a próxima pessoa não perder tempo com o comando.
 */
class ConfiguracoesDoKit extends SettingsPage
{
    use HasPageShield;

    protected static string $settings = SettingsDoKit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Configurações do kit';

    protected static ?string $navigationLabel = 'Configurações do kit';

    protected static ?int $navigationSort = 90;

    /**
     * Uma permissão para abrir e para salvar.
     *
     * Método de **instância**, não estático: é assim que o vendor declara
     * (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:248`),
     * e o exemplo do README dele induz ao erro por mostrar `canAccess()` estático
     * logo acima. Declarar `static` aqui não sobrescreveria nada.
     */
    public function canEdit(): bool
    {
        return static::canAccess();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('configuracoes')
                    ->persistTabInQueryString()
                    // Sem isto o componente de abas ocupa UMA das duas colunas do
                    // `defaultForm()` e o resto do formulário sai ao lado dele.
                    ->columnSpanFull()
                    ->tabs([
                        $this->abaIdentidade(),
                        $this->abaEmail(),
                        $this->abaTabelas(),
                        $this->abaKit(),
                    ]),
            ]);
    }

    /**
     * Registra QUEM salvou. O que mudou fica na trilha de `audits`.
     *
     * Nada é logado na abertura da tela: um `info` por request é o ruído que a
     * nota do canal `autenticacao` em `config/logging.php` mediu em 1,1 MB/dia.
     */
    protected function afterSave(): void
    {
        Log::channel('configuracoes')->info(
            '[ConfiguracoesDoKit@afterSave] Configurações do kit salvas | usuario: '.auth()->id(),
            ['user_id' => auth()->id(), 'campos' => array_keys($this->data ?? [])],
        );
    }

    private function abaIdentidade(): Tab
    {
        return Tab::make('Identidade')
            ->icon('heroicon-o-paint-brush')
            ->schema([
                TextInput::make('nome_da_aplicacao')
                    ->label('Nome da aplicação')
                    ->helperText('Aparece no topo dos três painéis, no título da aba e como remetente padrão.')
                    ->required()
                    ->maxLength(255),

                Select::make('cor_primaria')
                    ->label('Cor primária')
                    ->helperText('A paleta do Filament. Deixe em branco para o padrão (âmbar).')
                    // A lista fechada do kit, e não reflection sobre `Color`: aquela
                    // classe também expõe constantes que não são cor (`WCAG_AA_TEXT`)
                    // e neutros que ninguém escolhe como primária.
                    ->options(array_combine(
                        CustomizadorDaInstalacao::CORES,
                        CustomizadorDaInstalacao::CORES,
                    ))
                    ->placeholder('Padrão do Filament (âmbar)'),

                ColorPicker::make('cor_primaria_hex')
                    ->label('Cor primária livre')
                    ->helperText('Cor de marca em hexadecimal. VENCE a seleção acima quando preenchida. Valor inválido é ignorado.')
                    ->regex('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'),

                $this->arquivo('logo', 'Logo da marca')
                    ->helperText('Substitui o nome no topo dos painéis. Em branco, o nome é usado.'),

                $this->arquivo('favicon', 'Favicon')
                    ->helperText('O ícone da aba do navegador. Em branco, o do Filament.'),

                $this->arquivo('arte_do_login', 'Arte das telas de autenticação')
                    ->helperText('A imagem ao lado do formulário de login, recuperação de senha e confirmação de e-mail. Em branco, a arte que vem no kit.'),
            ]);
    }

    private function abaEmail(): Tab
    {
        $smtp = fn (Get $get): bool => $get('mail_mailer') === 'smtp';

        return Tab::make('E-mail')
            ->icon('heroicon-o-envelope')
            ->schema([
                Select::make('mail_mailer')
                    ->label('Transporte')
                    ->helperText('`log` só escreve em storage/logs — convite e lembrete não chegam a ninguém.')
                    ->options([
                        'log'   => 'Log (não envia — escreve em storage/logs)',
                        'array' => 'Array (descarta — só para teste)',
                        'smtp'  => 'SMTP',
                    ])
                    ->required()
                    ->live(),

                TextInput::make('mail_from_address')
                    ->label('Remetente')
                    ->email()
                    ->maxLength(255),

                TextInput::make('mail_from_name')
                    ->label('Nome do remetente')
                    ->maxLength(255),

                TextInput::make('mail_host')
                    ->label('Servidor')
                    ->maxLength(255)
                    ->visible($smtp),

                TextInput::make('mail_port')
                    ->label('Porta')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(65535)
                    ->visible($smtp),

                Select::make('mail_scheme')
                    ->label('Criptografia')
                    ->options(['tls' => 'TLS', 'ssl' => 'SSL'])
                    ->placeholder('Nenhuma')
                    ->visible($smtp),

                TextInput::make('mail_username')
                    ->label('Usuário')
                    ->maxLength(255)
                    ->visible($smtp),

                TextInput::make('mail_password')
                    ->label('Senha')
                    ->helperText('Guardada cifrada. A trilha de auditoria registra que ela mudou, nunca o valor.')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->visible($smtp),
            ]);
    }

    private function abaTabelas(): Tab
    {
        return Tab::make('Tabelas')
            ->icon('heroicon-o-table-cells')
            ->schema([
                TextInput::make('paginacao_padrao')
                    ->label('Linhas por página')
                    ->helperText('O default de TODA tabela dos três painéis, inclusive as dos pacotes de terceiros.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->required(),

                Toggle::make('tabela_listrada')
                    ->label('Linhas listradas')
                    ->helperText('O único controle de densidade visual que o Filament 5 oferece — não existe API de densidade de tabela nesta versão.'),

                Toggle::make('persistir_filtros')
                    ->label('Lembrar filtro, busca e ordenação')
                    ->helperText('O recorte do usuário sobrevive à navegação, guardado na sessão.'),

                Toggle::make('colunas_redimensionaveis')
                    ->label('Colunas arrastáveis')
                    ->helperText('Arrastar a largura das colunas. Sem efeito se o pacote resized-column for removido.'),
            ]);
    }

    private function abaKit(): Tab
    {
        return Tab::make('Kit')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Toggle::make('hub_de_navegacao')
                    ->label('Hub de navegação em cartões')
                    ->helperText('Uma grade de cartões com os destinos, nos painéis /admin e /app. O /infra tem hub independente desta chave.'),

                TextInput::make('rotulo_da_organizacao')
                    ->label('Como chamar cada organização')
                    ->helperText('Vocabulário da INSTALAÇÃO (Empresa, Cliente, Escola, Unidade). Não é a configuração de uma organização — essa fica em /admin/organizacoes.')
                    ->required()
                    ->maxLength(255),

                TextInput::make('rotulo_das_organizacoes')
                    ->label('E no plural')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    /**
     * Campo de imagem no disco `public`.
     *
     * `->disk('public')->visibility('public')` explícito, e não por default: no
     * Filament o default é `private`, e favicon, logo e arte aparecem ANTES de
     * haver sessão — na tela de login. Arquivo privado existe no disco e responde
     * 403 no `<head>` de toda página. Ver `.ai/rules/models.md` e ADR-03.
     */
    private function arquivo(string $nome, string $rotulo): FileUpload
    {
        return FileUpload::make($nome)
            ->label($rotulo)
            ->image()
            ->disk('public')
            ->directory('kit')
            ->visibility('public');
    }
}
