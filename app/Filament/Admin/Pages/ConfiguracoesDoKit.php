<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\CustomizadorDaInstalacao;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
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
 * `ExigePermissaoDaTela` — e não `HasPageShield` direto — porque é a convenção do
 * kit e porque ela é à prova do defeito silencioso: método definido na classe vence
 * método vindo de trait, sem erro nem aviso, então o dia em que esta Page ganhar um
 * `canAccess()` próprio (uma flag de config, por exemplo) a permissão deixaria de
 * ser consultada e o diff pareceria correto. Com o trait do kit, a regra local vai
 * no hook `regraLocalDeAcesso()` e as duas convivem. Esta tela não tem regra local
 * hoje, e por isso não sobrescreve o hook.
 *
 * A permission é `View:ConfiguracoesDoKit`, que o `ShieldPermissionsSeeder` gera e o
 * `PapeisSeeder` entrega ao papel `admin` junto com o resto da matriz do painel —
 * nenhuma lista precisou ser editada.
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
    use ExigePermissaoDaTela;

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
                        $this->abaRegistro(),
                        $this->abaLogin(),
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

    /**
     * O segredo não entra no formulário — e por isso não entra no HTML.
     *
     * Este é o primeiro dos dois pontos que impedem o vazamento descrito no campo
     * `mail_password`. O `fillForm()` do plugin faz `$this->form->fill($data)` com o
     * `toArray()` do settings, e o que vai para `$data` vai para o `wire:snapshot`. Zerar aqui é
     * o que garante que o valor guardado nunca é serializado para o navegador — nem para o
     * administrador que abriu a tela.
     *
     * Zerar aqui NÃO apaga a senha: o segundo ponto (`->dehydrated()`) mantém a chave fora do
     * save quando o campo fica em branco, e o `$settings->fill()` do plugin só aplica as chaves
     * presentes (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:83`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['mail_password']              = null;
        $data['login_google_client_secret'] = null;

        return $data;
    }

    /**
     * As opções do campo, mais o valor que JÁ está configurado, quando ele não está na lista.
     *
     * `Select` acrescenta um `Rule::in()` das próprias opções sozinho
     * (`vendor/filament/forms/src/Components/Select.php:1742-1748`). Isso torna a tela
     * **impossível de salvar** — nem o nome da aplicação grava — sempre que o valor vindo do
     * `.env` é legítimo mas está fora da lista curta que a tela oferece. E isso não é hipótese:
     * `config/mail.php` tem 9 transportes e a tela oferece 3; `Color` tem 26 cores e a lista
     * fechada do kit tem 16. Quem instalou com `MAIL_MAILER=ses` abria a tela e não conseguia
     * mudar nada.
     *
     * As duas saídas óbvias são piores. Ampliar a lista para tudo transforma a tela num
     * formulário de tudo-que-o-Laravel-suporta. Normalizar o valor para o default rebaixa, em
     * silêncio, o transporte de produção de alguém no primeiro salvamento — perda de dado
     * disfarçada de validação.
     *
     * Então o valor configurado entra como opção, marcado. A lista curta continua guiando quem
     * escolhe; quem já tem algo fora dela vê o que tem, mantém se quiser, e consegue salvar o
     * resto da tela.
     *
     * @param  array<string, string>  $opcoes
     * @return array<string, string>
     */
    private function comValorConfigurado(array $opcoes, ?string $atual, string $rotulo): array
    {
        if (blank($atual) || array_key_exists($atual, $opcoes)) {
            return $opcoes;
        }

        return $opcoes + [$atual => $atual.' — '.$rotulo];
    }

    /** Existe segredo do Google guardado? — para o placeholder dizer "em branco mantém". */
    private function segredoDoGoogleGuardado(): ?string
    {
        return app(static::getSettings())->login_google_client_secret;
    }

    /**
     * Existe senha de SMTP guardada? — para o placeholder dizer "em branco mantém".
     *
     * Lê do settings e não do formulário, justamente porque o formulário não a tem. Devolve o
     * valor e não um booleano só para o chamador poder usar `filled()`; o valor não é exibido em
     * lugar nenhum.
     */
    private function senhaDeSmtpGuardada(): ?string
    {
        return app(static::getSettings())->mail_password;
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
                    ->options(fn (): array => $this->comValorConfigurado(
                        array_combine(CustomizadorDaInstalacao::CORES, CustomizadorDaInstalacao::CORES),
                        app(static::getSettings())->cor_primaria,
                        'fora da lista do kit',
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
                    ->options(fn (): array => $this->comValorConfigurado(
                        [
                            'log'   => 'Log (não envia — escreve em storage/logs)',
                            'array' => 'Array (descarta — só para teste)',
                            'smtp'  => 'SMTP',
                        ],
                        app(static::getSettings())->mail_mailer,
                        'configurado no .env',
                    ))
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
                    ->options(fn (): array => $this->comValorConfigurado(
                        ['tls' => 'TLS', 'ssl' => 'SSL'],
                        app(static::getSettings())->mail_scheme,
                        'configurado no .env',
                    ))
                    ->placeholder('Nenhuma')
                    ->visible($smtp),

                TextInput::make('mail_username')
                    ->label('Usuário')
                    ->maxLength(255)
                    ->visible($smtp),

                /*
                 * A senha NUNCA e hidratada — e `->password()` nao resolve isso.
                 *
                 * `->password()` e `->revealable()` mexem no `type` do input, ou seja, na TELA.
                 * O valor continua em `$this->data`, que e propriedade publica da Page
                 * (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:33`),
                 * e o Livewire serializa `$data` inteiro no `wire:snapshot` do HTML. Resultado
                 * medido pelo quality gate: `GET /admin/configuracoes-do-kit` devolvia a senha em
                 * claro no corpo da resposta, com 200 e sem clique em "revelar".
                 *
                 * Por isso a barreira e em DOIS pontos, e nenhum deles e visual:
                 *
                 * 1. `mutateFormDataBeforeFill()` zera a chave antes de o formulario ser
                 *    preenchido — o segredo nao entra em `$data`, logo nao entra no snapshot;
                 * 2. `->dehydrated()` so deixa a chave chegar ao save quando o campo foi
                 *    preenchido. Ausente do `$data` do save, o `$settings->fill()` do plugin nao
                 *    mexe no valor guardado (ele aplica so as chaves presentes), e a senha atual
                 *    sobrevive a um salvamento em que ninguem a tocou.
                 *
                 * `->revealable()` fica: agora o que ele revela e o que a PESSOA acabou de
                 * digitar, que e conferencia de digitacao, nao exposicao do que estava gravado.
                 */
                TextInput::make('mail_password')
                    ->label('Senha')
                    ->helperText('Guardada cifrada. Deixe em branco para manter a senha atual — ela nao e exibida aqui, nem no codigo-fonte da pagina. A trilha de auditoria registra que ela mudou, nunca o valor.')
                    ->placeholder(fn (): string => filled($this->senhaDeSmtpGuardada()) ? 'Ja configurada — em branco mantem' : 'Nenhuma senha configurada')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $estado): bool => filled($estado))
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
                    /*
                     * Teto de 100 para quem ESCOLHE, e o valor já configurado quando ele é maior.
                     * `NumeroDoEnv::positivo()` não tem teto, então `KIT_PAGINACAO=500` é estado
                     * alcançável — e um `maxValue(100)` fixo travaria a tela inteira por causa de
                     * um número que a pessoa nem veio mexer. Mesmo raciocínio de
                     * `comValorConfigurado()`.
                     */
                    ->maxValue(fn (): int => max(100, (int) app(static::getSettings())->paginacao_padrao))
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

    /**
     * A porta de entrada do painel /app — fechada por default.
     *
     * As três chaves governam `App\Support\RegistroAberto`, e chegam lá pelo
     * `mapaDeConfiguracao()`: a classe lê `config('kit.registro.*')` e o
     * `aplicarNaConfig()` sobrepõe essa config com o que está gravado aqui. Nenhuma linha
     * daquela classe precisou mudar.
     *
     * As duas de baixo ficam ocultas com o registro desligado, e isso não é só estética:
     * "exigir aprovação" e "exigir e-mail validado" não significam nada sem porta aberta, e
     * um toggle que não faz efeito é pior que um toggle ausente — a pessoa acha que
     * configurou algo.
     */
    private function abaRegistro(): Tab
    {
        $aberto = fn (Get $get): bool => (bool) $get('registro_habilitado');

        return Tab::make('Registro')
            ->icon('heroicon-o-user-plus')
            ->schema([
                Toggle::make('registro_habilitado')
                    ->label('Permitir cadastro sem convite no /app')
                    ->helperText('Desligado, o /app só aceita quem tem convite — que é o default do kit. Ligado, a tela de cadastro passa a aceitar visitante, e cada organização ainda decide se aceita o seu (em /admin/organizacoes).')
                    ->live(),

                Toggle::make('registro_aprovacao_manual')
                    ->label('Cadastro nasce pendente de aprovação')
                    ->helperText('Quem se cadastra não recebe papel nenhum até alguém aprovar em /admin/usuarios — e sem papel não abre painel nenhum.')
                    ->visible($aberto),

                /*
                 * A verificação de e-mail NÃO é editável aqui, e isso é decisão, não
                 * esquecimento: o `AppPanelProvider` lê a chave no BOOT, e o middleware é
                 * fixado no array da rota no momento do registro
                 * (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`) — não por
                 * request. Um toggle aqui gravaria e não faria efeito até o próximo deploy,
                 * que é pior que campo ausente.
                 */
                TextEntry::make('aviso_verificacao_email')
                    ->hiddenLabel()
                    ->state('A exigência de e-mail validado fica no `.env`, em `KIT_REGISTRO_VERIFICAR_EMAIL` — ela decide o middleware das rotas no boot da aplicação, e por isso não pode mudar em tempo de execução.')
                    ->visible($aberto),
            ]);
    }

    /**
     * Login social e o rodapé da tela de login.
     *
     * Ao contrário de `registro_verificar_email`, estas chaves PODEM viver aqui: as duas que
     * decidem algo são lidas por request — o `abort_unless()` do `LoginComGoogleController` e a
     * closure do render hook do botão. Nada é decidido no boot do painel.
     *
     * O botão só entra no ar com o interruptor ligado E as três credenciais preenchidas, e é
     * `ConfiguracaoDoLogin::googleDisponivel()` que decide — os campos aqui só alimentam a
     * config que ela lê. Com ele desligado, `/auth/google/*` responde 404: esconder o botão não
     * é barreira, porque a URL é pública.
     */
    private function abaLogin(): Tab
    {
        $comGoogle = fn (Get $get): bool => (bool) $get('login_google_habilitado');

        return Tab::make('Login')
            ->icon('heroicon-o-arrow-right-on-rectangle')
            ->schema([
                Toggle::make('login_google_habilitado')
                    ->label('Entrar com Google')
                    ->helperText('Ligar aqui não põe o botão no ar sozinho: as credenciais abaixo também precisam estar preenchidas. O login social AUTENTICA quem já tem conta — criar conta depende do registro aberto, na aba anterior.')
                    ->live(),

                TextInput::make('login_google_client_id')
                    ->label('Client ID')
                    ->helperText('console.cloud.google.com → APIs e serviços → Credenciais. A URI de redirecionamento a cadastrar lá é o seu domínio + /auth/google/callback.')
                    ->maxLength(255)
                    ->visible($comGoogle),

                /*
                 * Mesmo tratamento da senha de SMTP, pelo mesmo motivo: `->password()` esconde
                 * na tela e o valor continua em `$this->data`, que o Livewire serializa no
                 * `wire:snapshot`. O segredo é zerado no fill e só chega ao save quando
                 * preenchido. Ver `mutateFormDataBeforeFill()`.
                 */
                TextInput::make('login_google_client_secret')
                    ->label('Client Secret')
                    ->helperText('Guardado cifrado. Deixe em branco para manter o atual — ele não é exibido aqui, nem no código-fonte da página.')
                    ->placeholder(fn (): string => filled($this->segredoDoGoogleGuardado()) ? 'Já configurado — em branco mantém' : 'Nenhum segredo configurado')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $estado): bool => filled($estado))
                    ->maxLength(255)
                    ->visible($comGoogle),

                Textarea::make('login_rodape')
                    ->label('Rodapé da tela de login')
                    ->helperText('Aparece nas telas de login dos três painéis. É TEXTO e sai escapado: a tela de login é pública e não autenticada, e HTML cru ali seria XSS armazenado.')
                    ->rows(2)
                    ->maxLength(500),
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
