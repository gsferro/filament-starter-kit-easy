<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\CustomizadorDaInstalacao;
use App\Support\TetoDeUpload;
use App\Support\ProvedorSocial;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
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
        $data['mail_password'] = null;

        /*
         * Todos os `client_secret`, por laço sobre o enum. Zerar por laço em vez de listar à mão
         * é o que impede o defeito de esquecer UM provedor: o campo esquecido continuaria
         * serializando o segredo gravado no `wire:snapshot`, com 200 e sem clique em "revelar",
         * e nenhum caso de teste dos outros provedores acusaria. Ver `.ai/rules/pages.md`.
         */
        foreach (ProvedorSocial::cases() as $provedor) {
            $data[$provedor->propriedadeDeSettings('client_secret')] = null;
        }

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

    /**
     * Existe segredo guardado deste provedor? — para o placeholder dizer "em branco mantém".
     *
     * Lê do settings e não do formulário, justamente porque o formulário não o tem (ele é zerado
     * em `mutateFormDataBeforeFill()`). Devolve o valor e não um booleano só para o chamador
     * poder usar `filled()`; o valor não é exibido em lugar nenhum.
     */
    private function segredoGuardadoDe(ProvedorSocial $provedor): ?string
    {
        $propriedade = $provedor->propriedadeDeSettings('client_secret');

        return app(static::getSettings())->{$propriedade};
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

                $this->arquivo('logo', 'Logo da marca', 'Substitui o nome no topo dos painéis. Em branco, o nome é usado.'),

                $this->arquivo('favicon', 'Favicon', 'O ícone da aba do navegador. Em branco, o do Filament.'),

                $this->arquivo('arte_do_login', 'Arte das telas de autenticação', 'A imagem ao lado do formulário de login, recuperação de senha e confirmação de e-mail. Em branco, a arte que vem no kit.'),
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
                 * **O parametro da closure PRECISA se chamar `$state`.** O Filament resolve
                 * dependencia de closure por NOME
                 * (`vendor/filament/schemas/src/Components/Component.php:87-98`), e nome
                 * desconhecido com tipo escalar nao resolve para nada
                 * (`vendor/filament/support/src/Concerns/EvaluatesClosures.php:143-160`). Com
                 * `$estado`, a closure recebia `null`, `filled(null)` era sempre `false` e a
                 * chave NUNCA chegava ao save: a senha de SMTP era impossivel de gravar pela
                 * tela, em silencio, desde a v0.19.0.
                 *
                 * O par de casos que a rule `.ai/rules/pages.md` pede — "nao aparece no HTML" e
                 * "sobrevive a um save que nao o tocou" — passa com esse defeito, porque os dois
                 * afirmam o que NAO acontece. Falta o terceiro: campo PREENCHIDO grava. Foi ele
                 * que pegou.
                 *
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
                    ->dehydrated(fn (?string $state): bool => filled($state))
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
     * "Cadastro nasce pendente" fica OCULTO com o registro desligado, e isso não é estética:
     * aprovação de cadastro não significa nada sem porta aberta, e toggle que não faz efeito é
     * pior que toggle ausente — a pessoa acha que configurou algo.
     *
     * "Exigir e-mail validado" NÃO segue a mesma regra, e a diferença é medida: a exigência
     * alcança todo usuário do /app, venha ele de cadastro aberto, de convite ou da tela de
     * usuários. Esconder o campo com o registro fechado produziria o defeito espelhado —
     * exigência LIGADA e invisível, com quem administra sem como desligá-la pela tela. Ver a
     * revisão adversarial na wiki `verificacao-de-email-editavel`.
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
                 * A verificação de e-mail voltou a ser editável, e o que mudou não foi este
                 * arquivo: até a v0.19.3 um toggle aqui gravava e não fazia efeito, porque o
                 * `AppPanelProvider` lia a chave no BOOT e o middleware ficava fixado no array
                 * da rota (`.../Pages/Concerns/HasRoutes.php:91`). Agora a rota guarda um
                 * decisor — `App\Http\Middleware\ExigirEmailVerificado` —, que pergunta a cada
                 * request. Ver a wiki `verificacao-de-email-editavel`.
                 *
                 * O `helperText` avisa o que o README avisa, porque aqui um clique basta: a
                 * exigência alcança TODO usuário do /app, não só quem se cadastrar depois.
                 */
                Toggle::make('registro_verificar_email')
                    ->label('Exigir e-mail validado no /app')
                    ->helperText('Ligado, quem ainda não confirmou o e-mail é levado à tela de confirmação ao entrar no /app — e isso vale para TODO usuário do painel, não só para os novos. Numa base que já tem gente dentro, valide antes quem foi criado pela tela de usuários (o README traz o comando). Quem vem de convite nunca é afetado: o token já provou a posse do endereço.'),
            ]);
    }

    /**
     * Login social e o rodapé da tela de login.
     *
     * Ao contrário de `registro_verificar_email`, estas chaves PODEM viver aqui: as que decidem
     * algo são lidas por request — o `abort_unless()` do `LoginSocialController` e a closure do
     * render hook dos botões. Nada é decidido no boot do painel.
     *
     * Uma SEÇÃO por provedor, e não treze campos soltos: com quatro provedores são doze campos de
     * credencial, nove deles condicionais a um toggle acima. Solto, o campo que aparece empurra
     * os outros para baixo sem indicar de quem ele é. Ver ADR-07 da wiki
     * `mais-provedores-sociais`.
     *
     * As seções vêm de um laço sobre `ProvedorSocial::cases()`: provedor novo aparece na tela sem
     * ninguém tocar nesta tela.
     */
    private function abaLogin(): Tab
    {
        $secoes = array_map(
            fn (ProvedorSocial $provedor): Section => $this->secaoDoProvedor($provedor),
            ProvedorSocial::cases(),
        );

        return Tab::make('Login')
            ->icon('heroicon-o-arrow-right-on-rectangle')
            ->schema([
                ...$secoes,

                Textarea::make('login_rodape')
                    ->label('Rodapé da tela de login')
                    ->helperText('Aparece nas telas de login dos três painéis. É TEXTO e sai escapado: a tela de login é pública e não autenticada, e HTML cru ali seria XSS armazenado.')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * O bloco de um provedor de login social: o interruptor e as duas credenciais.
     *
     * O botão só entra no ar com o interruptor ligado E as três credenciais preenchidas, e é
     * `ConfiguracaoDoLogin::disponivel()` que decide — os campos aqui só alimentam a config que
     * ela lê. Com o interruptor desligado, `/auth/{provedor}/*` responde 404: esconder o botão
     * não é barreira, porque a URL é pública.
     *
     * `->columnSpanFull()` explícito na `Section`: `Grid`, `Section` e `Fieldset` NÃO ocupam todas
     * as colunas por default, e sem isto a seção fica numa das duas colunas do `defaultForm()`.
     *
     * O `client_secret` tem o mesmo tratamento da senha de SMTP, pelo mesmo motivo: `->password()`
     * esconde na TELA e o valor continua em `$this->data`, que o Livewire serializa no
     * `wire:snapshot`. São dois pontos, e nenhum é visual — o segredo é zerado no fill (por laço,
     * em `mutateFormDataBeforeFill()`) e só chega ao save quando preenchido (`->dehydrated()`).
     * Ver `.ai/rules/pages.md`.
     */
    private function secaoDoProvedor(ProvedorSocial $provedor): Section
    {
        $habilitado = $provedor->propriedadeDeSettings('habilitado');
        $ligado     = fn (Get $get): bool => (bool) $get($habilitado);

        return Section::make("Entrar com {$provedor->rotulo()}")
            ->description($this->ondeCriarOApp($provedor))
            ->collapsible()
            ->columnSpanFull()
            ->schema([
                Toggle::make($habilitado)
                    ->label("Oferecer o botão do {$provedor->rotulo()}")
                    ->helperText('Ligar aqui não põe o botão no ar sozinho: as credenciais abaixo também precisam estar preenchidas. O login social AUTENTICA quem já tem conta — criar conta depende do registro aberto, na aba anterior.')
                    ->live(),

                TextInput::make($provedor->propriedadeDeSettings('client_id'))
                    ->label('Client ID')
                    // O caminho é o que vive em `config/services.php`, relativo de propósito —
                    // cadastre-o ABSOLUTO no console do provedor.
                    ->helperText("A URI de redirecionamento a cadastrar no provedor é o seu domínio + /auth/{$provedor->value}/callback")
                    ->maxLength(255)
                    ->visible($ligado),

                TextInput::make($provedor->propriedadeDeSettings('client_secret'))
                    ->label('Client Secret')
                    ->helperText('Guardado cifrado. Deixe em branco para manter o atual — ele não é exibido aqui, nem no código-fonte da página.')
                    ->placeholder(fn (): string => filled($this->segredoGuardadoDe($provedor)) ? 'Já configurado — em branco mantém' : 'Nenhum segredo configurado')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255)
                    ->visible($ligado),
            ]);
    }

    /** Onde criar o app OAuth de cada provedor — o mesmo roteiro que os READMEs detalham. */
    private function ondeCriarOApp(ProvedorSocial $provedor): string
    {
        return match ($provedor) {
            ProvedorSocial::Google   => 'console.cloud.google.com → APIs e serviços → Credenciais → ID do cliente OAuth.',
            ProvedorSocial::Github   => 'github.com/settings/developers → OAuth Apps → New OAuth App. O kit pede o escopo user:email, e é ele que permite confirmar que o e-mail está verificado.',
            ProvedorSocial::LinkedIn => 'linkedin.com/developers → Create app → Products → Sign In with LinkedIn using OpenID Connect. Sem esse produto, o provedor não devolve email_verified.',
            ProvedorSocial::X        => 'developer.x.com → Projects & Apps → User authentication settings, tipo Web App com OAuth 2.0. Pedir o e-mail exige users.email, e o X só devolve endereço que ele já confirmou.',
        };
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
     * Os formatos de imagem aceitos nos campos de arquivo — todos, menos SVG.
     *
     * São os nove da regra `image` do Laravel
     * (`.../Validation/Concerns/ValidatesAttributes.php:1531-1540`) MAIS `ico` e
     * `tiff`, e as duas adições não são capricho: `.ico` é o formato que a maioria
     * dos kits de marca ainda entrega para favicon, o requisito diz que "o
     * restante pode ser qualquer tipo de image", e **este kit serve um
     * `public/favicon.ico`**. Recusar `.ico` na tela de favicon do kit que embarca
     * um `.ico` é a inconsistência que o quality gate achou.
     *
     * A lista é escrita e não herdada da regra `image` por isso mesmo: `image` é
     * mantida pelo framework, o que é bom, mas ela decide por conta própria que
     * `.ico` não é imagem — e aqui essa decisão é do requisito. O preço é revisitar
     * esta linha quando um formato novo virar comum; o teste de formatos aceitos é
     * onde isso aparece.
     *
     * SVG fica fora, e é o único que fica: ele carrega `<script>` e estes arquivos
     * são servidos pelo mesmo origin da aplicação.
     *
     * ⚠️ `tif` E `tiff`, e o primeiro não é redundância. A regra `mimes` compara
     * `guessExtension()`, que devolve a PRIMEIRA extensão que o Symfony associa ao
     * MIME — e para `image/tiff` a primeira é `tif`, não `tiff`
     * (`MimeTypes::getExtensions('image/tiff')` → `['tif', 'tiff', …]`). Com só
     * `tiff` na lista, um TIFF era recusado com a mensagem dizendo que TIFF é
     * aceito. Quem pegou isso foi o caso de partição por formato.
     */
    private const FORMATOS_DE_IMAGEM = 'jpg,jpeg,png,gif,bmp,webp,avif,heic,heif,ico,tif,tiff';

    /**
     * Campo de imagem no disco `public`, com teto de tamanho e sem SVG.
     *
     * `->disk('public')->visibility('public')` explícito, e não por default: no
     * Filament o default é `private`, e favicon, logo e arte aparecem ANTES de
     * haver sessão — na tela de login. Arquivo privado existe no disco e responde
     * 403 no `<head>` de toda página. Ver `.ai/rules/models.md` e ADR-03 da wiki
     * `settings-do-kit`.
     *
     * ## `->image()` E `->rule('image')`, e os dois são necessários
     *
     * São coisas diferentes com o mesmo nome. O `->image()` do Filament é
     * açúcar para `acceptedFileTypes(['image/*'])`
     * (vendor/filament/forms/src/Components/FileUpload.php:130-134): ele vira o
     * `accept` do seletor de arquivo do sistema e a regra `mimetypes:image/*` —
     * e `image/svg+xml` CASA com esse curinga
     * (.../Validation/Concerns/ValidatesAttributes.php:1781-1783). Era por isso
     * que SVG passava aqui.
     *
     * A barreira é a regra `mimes` do LARAVEL, com a lista de `FORMATOS_DE_IMAGEM`.
     * Ela compara `guessExtension()`, que vem do MIME derivado do CONTEÚDO do
     * arquivo (`.../Validation/Concerns/ValidatesAttributes.php:1746-1761`), então
     * renomear um `.svg` para `.png` não passa.
     *
     * SVG carrega `<script>`, e estes três arquivos são servidos pelo MESMO origin
     * da aplicação, com visibilidade pública — abrir a URL executaria o script com
     * acesso ao cookie de sessão. É a razão de a lista ser fechada.
     *
     * ## O teto vem de `TetoDeUpload`, em KILOBYTES
     *
     * `->maxSize()` monta a regra `max:{$size}` do Laravel
     * (vendor/filament/forms/src/Components/BaseFileUpload.php:413-421), e essa
     * regra divide o tamanho do arquivo por 1024
     * (.../Validation/Concerns/ValidatesAttributes.php:2822). Quem sabe disso é
     * `App\Support\TetoDeUpload`, a dona da conversão — aqui e nos outros dois
     * campos de upload do kit.
     *
     * ## O texto de ajuda vem por argumento, e não encadeado
     *
     * As três chamadas encadeavam `->helperText()` DEPOIS de `arquivo()`, o que
     * sobrescreveria qualquer texto definido aqui. Recebendo o texto como
     * argumento, o teto e o aviso de SVG entram nos três de uma vez e
     * acompanham a config.
     */
    private function arquivo(string $nome, string $rotulo, string $ajuda): FileUpload
    {
        $formatos = mb_strtoupper(str_replace(',', ', ', self::FORMATOS_DE_IMAGEM));

        $maximoEmMb = TetoDeUpload::emMb();

        return FileUpload::make($nome)
            ->label($rotulo)
            ->image()
            ->rule('mimes:'.self::FORMATOS_DE_IMAGEM)
            ->maxSize(TetoDeUpload::emKb())
            ->validationMessages([
                'max'   => "O arquivo passa de {$maximoEmMb} MB.",
                'mimes' => "SVG não é aceito. Envie {$formatos}.",
            ])
            ->helperText("{$ajuda} Até {$maximoEmMb} MB, e SVG não é aceito.")
            ->disk('public')
            ->directory('kit')
            ->visibility('public');
    }
}
