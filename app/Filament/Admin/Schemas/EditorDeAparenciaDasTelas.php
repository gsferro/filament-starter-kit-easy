<?php

declare(strict_types=1);

namespace App\Filament\Admin\Schemas;

use App\Support\AparenciaDasTelasDeAcesso;
use App\Support\TetoDeUpload;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * O editor da aparência das telas de acesso — os campos e o preview —, usado nos dois lugares que
 * editam uma aparência: a aba "Telas de acesso" das configurações (a da instalação) e a seção
 * "Telas de acesso" do formulário da organização (a dela). Um editor só, para que os dois nunca
 * divirjam no vocabulário (wiki `feat/aparencia-das-telas-de-acesso`).
 *
 * Os campos ficam sob o caminho `aparencia_das_telas` do estado do formulário — o nome da
 * propriedade das configurações e da coluna da organização. O que a tela grava passa por
 * `paraGravar()`, que reduz ao vocabulário (P-04); o que a tela mostra vem de `paraOFormulario()`.
 *
 * ## O rascunho do preview
 *
 * O preview mostra o que AINDA NÃO foi salvo (P-08). Cada render do editor grava o estado atual,
 * já normalizado, no cache, numa chave com o id do usuário e o id do componente Livewire — este
 * último muda a cada carregamento da página, então duas abas não disputam o mesmo rascunho, e
 * ninguém alcança o rascunho de outra pessoa: a página do preview só lê a chave do próprio usuário
 * (ADR-04). A URL leva um `v` com o hash do rascunho: quando ele muda, o `src` do iframe muda e o
 * navegador recarrega o preview sozinho.
 */
final class EditorDeAparenciaDasTelas
{
    /** Minutos de vida do rascunho. Passado isso, o iframe responde 404 até o próximo render. */
    public const VIDA_DO_RASCUNHO = 30;

    /**
     * Os campos, sob o caminho `aparencia_das_telas`.
     *
     * @param  bool  $daOrganizacao  no formulário da organização não há a seção "Esqueci a senha":
     *                               a recuperação e a redefinição de senha nunca sabem a organização
     *                               da pessoa e usam sempre a padrão (P-06, P-31)
     */
    public static function campos(bool $daOrganizacao = false): Group
    {
        /*
         * Uma seção colapsável por tela, e não abas: a tela de configurações tem uma regra para as
         * suas abas (só as de primeiro nível, adiadas) e para os campos (sempre dentro de uma seção
         * colapsável com ícone) — `ConfiguracoesSecoesColapsaveisTest`. Seção recolhida continua
         * montada, então nada se perde ao salvar.
         */
        return Group::make([
            Section::make('Login')
                ->description('Vale também para os dois fatores, o bloqueio de sessão e a confirmação de e-mail.')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->collapsible()
                ->persistCollapsed()
                ->id('aparencia-das-telas-login')
                ->schema(self::camposDaTela(raiz: true, daOrganizacao: $daOrganizacao))
                ->columns(2)
                ->columnSpanFull(),
            self::secaoDeTelaPropria('cadastro', 'Cadastro', 'heroicon-o-user-plus', 'Vale para o cadastro aberto e para o aceite de convite.'),
            ...($daOrganizacao ? [] : [
                self::secaoDeTelaPropria('recuperacao', 'Esqueci a senha', 'heroicon-o-key', 'Vale para o pedido de recuperação e para a redefinição de senha.'),
            ]),
        ])
            ->statePath('aparencia_das_telas')
            ->columnSpanFull();
    }

    /**
     * A seção de uma tela com aparência própria (P-25): "Igual ao login" ligado = a tela segue o login;
     * desligado = a tela tem a sua, que nasce como CÓPIA do login daquele momento.
     */
    private static function secaoDeTelaPropria(string $familia, string $rotulo, string $icone, string $explicacao): Section
    {
        $caminho = "telas.{$familia}";

        return Section::make($rotulo)
            ->description($explicacao)
            ->icon($icone)
            ->collapsible()
            ->collapsed()
            ->persistCollapsed()
            ->id("aparencia-das-telas-{$familia}")
            ->columnSpanFull()
            ->schema([
                Toggle::make("{$caminho}.igual_ao_login")
                    ->label('Igual ao login')
                    ->helperText('Desligado, esta tela passa a ter a sua aparência, começando como cópia da do login.')
                    ->default(true)
                    ->live()
                    ->afterStateUpdated(function (?bool $state, Get $get, Set $set) use ($caminho): void {
                        if ($state === false) {
                            $set($caminho, [...self::estadoDoLogin($get), 'igual_ao_login' => false]);
                        }
                    }),

                Group::make(self::camposDaTela(raiz: false))
                    ->statePath($caminho)
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get("{$caminho}.igual_ao_login") === false),
            ]);
    }

    /**
     * O estado atual dos campos do login, para a cópia de uma tela própria.
     *
     * @return array<string, mixed>
     */
    private static function estadoDoLogin(Get $get): array
    {
        $estado = [];

        foreach (array_keys(AparenciaDasTelasDeAcesso::padraoDoPainel()) as $campo) {
            $estado[$campo] = $get($campo);
        }

        // O `Get` devolve o estado JÁ convertido do upload (o caminho, em string); o campo da cópia
        // precisa do formato interno do FileUpload, `[uuid => caminho]` — sem isto, salvar a cópia
        // derrubava a validação com TypeError (CX-B1 da revisão dos Adendos 2 e 3).
        if (is_string($estado['imagem_do_destaque'] ?? null)) {
            $estado['imagem_do_destaque'] = [(string) Str::uuid() => $estado['imagem_do_destaque']];
        }

        // O auxiliar da cor (não gravado) acompanha a cópia, senão a cor copiada some da tela (CX-B5).
        $estado['origem_da_cor'] = filled($estado['cor'] ?? null) ? 'propria' : 'primaria';

        return $estado;
    }

    /**
     * Os campos de UMA tela, relativos ao caminho do grupo que os contém.
     *
     * @return array<Component>
     */
    private static function camposDaTela(bool $raiz, bool $daOrganizacao = false): array
    {
        $ehPainel = static fn (Get $get): bool => $get('layout') === AparenciaDasTelasDeAcesso::LAYOUT_PAINEL;

        return [
            Select::make('layout')
                ->label('Layout')
                ->options([
                    AparenciaDasTelasDeAcesso::LAYOUT_CLASSICO => 'Clássico — arte ao lado do formulário, como sempre foi',
                    AparenciaDasTelasDeAcesso::LAYOUT_PAINEL   => 'Painel de marca — formulário e painel com a sua marca',
                ])
                ->default(AparenciaDasTelasDeAcesso::LAYOUT_CLASSICO)
                ->selectablePlaceholder(false)
                // Obrigatório só no login (a raiz): um estado forjado sem objeto é recusado em vez de
                // zerar a aparência (CT-20). Nas outras telas o grupo some com "Igual ao login", e a
                // regra disparava mesmo oculta num estado sem `telas` (CT-28); lá, valor ausente cai
                // no clássico pelo normalizador (P-04).
                ->required($raiz)
                ->in(AparenciaDasTelasDeAcesso::LAYOUTS)
                ->live()
                ->helperText('O clássico mantém a tela exatamente como está hoje.')
                ->columnSpanFull(),

            // O formato dos textos de quem passa pelo editor (P-27): Markdown, como o rodapé do login.
            Hidden::make('formato_dos_textos')->default('markdown'),

            Fieldset::make('Disposição')
                ->schema([
                    ToggleButtons::make('lado_do_formulario')
                        ->label('Lado do formulário')
                        ->options(['esquerda' => 'Esquerda', 'direita' => 'Direita'])
                        ->in(AparenciaDasTelasDeAcesso::LADOS)
                        ->default('direita')
                        ->inline()
                        ->required()
                        ->live(),

                    Select::make('tamanho_do_painel')
                        ->label('Largura do painel de marca')
                        ->options(self::opcoesDeTamanho())
                        ->in(AparenciaDasTelasDeAcesso::TAMANHOS_DO_PAINEL)
                        ->default(50)
                        ->selectablePlaceholder(false)
                        ->required()
                        ->live()
                        ->helperText('O formulário fica com o resto da tela (por exemplo, painel em 35 % deixa 65 % para o formulário). No celular o painel vira uma faixa no topo, em qualquer largura.'),
                ])
                ->columns(2)
                ->visible($ehPainel),

            Fieldset::make('Fundo')
                ->schema([
                    ToggleButtons::make('fundo')
                        ->label('Fundo do painel')
                        ->options(['cor' => 'Cor', 'gradiente' => 'Gradiente', 'arte' => 'Arte da identidade'])
                        ->in(AparenciaDasTelasDeAcesso::FUNDOS)
                        ->default('cor')
                        ->inline()
                        ->required()
                        ->live()
                        ->helperText('A arte é a mesma da aba Identidade (ou a gerada com o nome da aplicação). No celular, a faixa usa a cor do painel no lugar da arte.')
                        ->columnSpanFull(),

                    // Só de tela: a escolha é "cor vazia" (a primária) ou uma cor (P-28).
                    ToggleButtons::make('origem_da_cor')
                        ->label('Cor')
                        ->options(['primaria' => 'Cor primária (padrão do Filament)', 'propria' => 'Cor própria'])
                        ->inline()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn (ToggleButtons $component, Get $get) => $component->state(filled($get('cor')) ? 'propria' : 'primaria'))
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === 'primaria') {
                                $set('cor', null);
                            }
                        })
                        ->helperText('A primária é a da organização, quando houver; senão, a da instalação; sem nenhuma, o âmbar do Filament.')
                        ->visible(fn (Get $get): bool => $get('fundo') === 'cor'),

                    self::corHex('cor', 'Cor do painel')
                        ->visible(fn (Get $get): bool => $get('fundo') === 'cor' && $get('origem_da_cor') === 'propria')
                        // Oculta, mas GRAVADA: a aparência é um objeto inteiro (P-07) (CT-32).
                        ->dehydratedWhenHidden(),

                    self::corHex('gradiente_de', 'Gradiente: cor de início')
                        ->helperText('Em branco, o tom 600 da cor primária.')
                        ->visible(fn (Get $get): bool => $get('fundo') === 'gradiente')
                        ->dehydratedWhenHidden(),

                    self::corHex('gradiente_ate', 'Gradiente: cor de fim')
                        ->helperText('Em branco, o tom 900 da cor primária.')
                        ->visible(fn (Get $get): bool => $get('fundo') === 'gradiente')
                        ->dehydratedWhenHidden(),

                    ToggleButtons::make('gradiente_direcao')
                        ->label('Direção do gradiente')
                        ->options(['vertical' => 'Vertical', 'horizontal' => 'Horizontal', 'diagonal' => 'Diagonal'])
                        ->in(array_keys(AparenciaDasTelasDeAcesso::DIRECOES_DO_GRADIENTE))
                        ->default('diagonal')
                        ->inline()
                        ->live()
                        ->helperText('Sobre o gradiente, os textos ficam numa faixa escura, para continuarem legíveis em qualquer ponto.')
                        ->visible(fn (Get $get): bool => $get('fundo') === 'gradiente')
                        ->dehydratedWhenHidden(),
                ])
                ->columns(2)
                ->visible($ehPainel),

            Fieldset::make('Textos')
                ->schema([
                    /*
                     * O mesmo componente e a mesma barra do "Rodapé da tela de login" (RQ-27): Markdown,
                     * e não HTML cru — a tela é pública. A view passa o texto pelo mesmo `Str::markdown`
                     * seguro e só deixa negrito, itálico, tachado e link (P-27).
                     */
                    MarkdownEditor::make('frase')
                        ->label('Frase de destaque')
                        ->toolbarButtons([['bold', 'italic', 'strike', 'link']])
                        ->rule(static fn (): Closure => self::teto(AparenciaDasTelasDeAcesso::LIMITE_DA_FRASE))
                        ->live(onBlur: true)
                        ->placeholder('Ex.: Tudo o que a sua equipe precisa, num lugar só.')
                        ->helperText('Opcional. Aceita negrito, itálico, tachado e link. Até '.AparenciaDasTelasDeAcesso::LIMITE_DA_FRASE.' caracteres visíveis. HTML cru é descartado, porque a tela é pública.')
                        ->columnSpanFull(),

                    MarkdownEditor::make('apoio')
                        ->label('Texto de apoio')
                        ->toolbarButtons([['bold', 'italic', 'strike', 'link']])
                        ->rule(static fn (): Closure => self::teto(AparenciaDasTelasDeAcesso::LIMITE_DO_APOIO))
                        ->live(onBlur: true)
                        ->placeholder('Ex.: Acesse com o e-mail e a senha cadastrados.')
                        ->helperText('Opcional. Aceita negrito, itálico, tachado e link. Até '.AparenciaDasTelasDeAcesso::LIMITE_DO_APOIO.' caracteres visíveis. No celular nenhum texto aparece: só a logo ou o nome da aplicação.')
                        ->columnSpanFull(),
                ])
                ->visible($ehPainel),

            Fieldset::make('Logo e destaque central')
                ->schema([
                    Toggle::make('logo_no_painel')
                        ->label('Logo no topo do painel')
                        ->default(true)
                        ->live()
                        ->helperText('A logo da identidade (ou a da organização), num selo claro. Sem logo, aparece o nome da aplicação.')
                        ->columnSpanFull(),

                    ToggleButtons::make('destaque')
                        ->label('No meio do painel')
                        ->options(['nenhum' => 'Nada', 'logo' => 'Logo', 'imagem' => 'Imagem própria'])
                        ->in(AparenciaDasTelasDeAcesso::DESTAQUES)
                        ->default('nenhum')
                        ->inline()
                        ->required()
                        ->live(),

                    ToggleButtons::make('tamanho_do_destaque')
                        ->label('Tamanho do destaque')
                        ->options(['pequeno' => 'Pequeno', 'medio' => 'Médio', 'grande' => 'Grande'])
                        ->in(AparenciaDasTelasDeAcesso::TAMANHOS_DO_DESTAQUE)
                        ->default('medio')
                        ->inline()
                        ->required()
                        ->live()
                        ->visible(fn (Get $get): bool => $get('destaque') !== 'nenhum')
                        // Oculto sem destaque, mas gravado: a aparência é um objeto inteiro (ADR-02).
                        ->dehydratedWhenHidden(),

                    FileUpload::make('imagem_do_destaque')
                        ->label('Imagem do destaque')
                        // Tipos explícitos, e não `image()`: o `image()` aceita `image/*`, que casa com SVG —
                        // e SVG servido pelo mesmo origin carrega script (mesma razão da logo da organização).
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(TetoDeUpload::emKb())
                        ->disk('public')
                        ->directory(AparenciaDasTelasDeAcesso::DIRETORIO_DAS_IMAGENS)
                        ->visibility('public')
                        ->imagePreviewHeight('120')
                        ->live()
                        ->helperText('PNG, JPEG ou WebP, até '.TetoDeUpload::emMb().' MB. Aparece no meio do painel, sobre o fundo; uma ilustração, um selo, uma foto recortada.')
                        ->visible(fn (Get $get): bool => $get('destaque') === 'imagem')
                        ->dehydratedWhenHidden()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible($ehPainel),

            Fieldset::make('Ilustração')
                ->schema([
                    ToggleButtons::make('ilustracao')
                        ->label('Ilustração do painel')
                        ->options(['nenhuma' => 'Nenhuma', 'rede' => 'Rede de pontos'])
                        ->in(AparenciaDasTelasDeAcesso::ILUSTRACOES)
                        ->default('nenhuma')
                        ->inline()
                        ->required()
                        ->live()
                        ->helperText('Pontos ligados por linhas, decorativos, no alto do painel. Some no celular.'),

                    Toggle::make('movimento')
                        ->label('Em movimento')
                        ->default(true)
                        ->live()
                        ->helperText('Desligado, a rede fica parada. Ligado, a tela ganha um botão de pausar; quem pediu ao sistema para reduzir movimento vê a rede parada.')
                        ->visible(fn (Get $get): bool => $get('ilustracao') === 'rede')
                        ->dehydratedWhenHidden(),

                    self::corHex('cor_da_ilustracao', 'Cor da ilustração')
                        ->helperText('Em branco, a mesma cor do texto do painel.')
                        ->visible(fn (Get $get): bool => $get('ilustracao') === 'rede')
                        ->dehydratedWhenHidden(),
                ])
                ->columns(2)
                ->visible($ehPainel),

            Toggle::make('alternador_de_tema')
                ->label('Alternador de tema claro/escuro')
                ->default(true)
                ->live()
                ->visible($ehPainel),

            // Uma opção só, no login da instalação: o efeito depende das duas telas (P-32), e a
            // organização segue a da instalação (P-35).
            ...($raiz && ! $daOrganizacao ? [
                Toggle::make('transicao_entre_telas')
                    ->label('Transição entre as telas')
                    ->helperText('Ao ir do login para o cadastro ou o "esqueci a senha" (e de volta), o painel desliza para o lado da outra tela e o formulário aparece aos poucos. Vale entre telas no painel de marca, em computador, nos navegadores com suporte; quem pediu ao sistema para reduzir movimento não vê animação. Vale também para as organizações.')
                    ->default(true)
                    ->visible($ehPainel)
                    ->dehydratedWhenHidden(),
            ] : []),
        ];
    }

    /**
     * "50 %", de 30 a 70 (a ajuda do campo diz que o formulário fica com o resto).
     *
     * @return array<int, string>
     */
    private static function opcoesDeTamanho(): array
    {
        $opcoes = [];

        foreach (AparenciaDasTelasDeAcesso::TAMANHOS_DO_PAINEL as $tamanho) {
            $opcoes[$tamanho] = "{$tamanho} %";
        }

        return $opcoes;
    }

    /**
     * O teto de um texto formatado (P-27): o texto VISÍVEL — o Markdown renderizado como a tela o
     * mostra, sem tags, entidades decodificadas e espaços normalizados — e o teto bruto de 4 KiB.
     *
     * Regra própria, e não `maxLength()`: o `maxLength()` mede o valor cru e põe `maxlength` no
     * HTML, que o navegador conta em unidades UTF-16. Valor acima do teto é RECUSADO, nunca cortado
     * (P-10).
     *
     * O chamador passa por `fn (): Closure => self::teto(...)`: o Filament AVALIA todo closure
     * entregue a `rule()` com injeção de dependência, e o `$attribute` da regra do Laravel não se
     * resolve ali (`[$attribute] was unresolvable`). O closure de fora devolve a regra.
     */
    private static function teto(int $limite): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($limite): void {
            if (is_string($value) && strlen(trim($value)) > AparenciaDasTelasDeAcesso::LIMITE_BRUTO_DO_TEXTO) {
                $fail('O texto, com a formatação, passa de 4 KB.');

                return;
            }

            $visivel = is_string($value) ? AparenciaDasTelasDeAcesso::textoVisivel(trim($value), 'markdown') : null;

            if ($visivel !== null && mb_strlen($visivel) > $limite) {
                $fail("Use no máximo {$limite} caracteres visíveis.");
            }
        };
    }

    /**
     * O preview (o botão "Atualizar preview" está na própria view: um `$refresh`, sem Action).
     *
     * @param  Closure(?Model): string  $escopo  `global`, `tenant:{id}` ou `tenant:novo`
     * @return array<Component>
     */
    public static function preview(Closure $escopo): array
    {
        return [
            View::make('filament.admin.preview-das-telas-de-acesso')
                ->viewData(fn (Livewire $livewire, ?Model $record, Get $get): array => [
                    'url' => self::urlDoPreview(
                        $livewire,
                        $escopo($record),
                        $get('usar_aparencia_padrao') === true
                            ? AparenciaDasTelasDeAcesso::global()
                            : $get('aparencia_das_telas'),
                    ),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * O valor gravado, para o formulário: o vocabulário completo, com o clássico explícito.
     *
     * @return array<string, mixed>
     */
    public static function paraOFormulario(mixed $gravado): array
    {
        $normalizado = AparenciaDasTelasDeAcesso::normalizar($gravado, 'formulario');
        $login       = self::telaParaOFormulario($normalizado);
        $telas       = [];

        foreach (AparenciaDasTelasDeAcesso::FAMILIAS_COM_APARENCIA_PROPRIA as $familia) {
            $propria = $normalizado['telas'][$familia] ?? null;

            // Sem aparência própria, a aba já traz a cópia do login, com "Igual ao login" ligado (P-25).
            $telas[$familia] = $propria === null
                ? [...$login, 'igual_ao_login' => true]
                : [...self::telaParaOFormulario($propria), 'igual_ao_login' => false];
        }

        return [...$login, 'telas' => $telas];
    }

    /**
     * Uma tela, como o formulário a mostra: o vocabulário completo, com o clássico explícito, e o
     * texto gravado antes do Adendo 2 convertido para Markdown ESCAPADO — aparece igual, e quem
     * salvar passa a gravar Markdown (P-27).
     *
     * @param  array<string, mixed>|null  $tela
     * @return array<string, mixed>
     */
    private static function telaParaOFormulario(?array $tela): array
    {
        if ($tela === null || ($tela['layout'] ?? null) !== AparenciaDasTelasDeAcesso::LAYOUT_PAINEL) {
            return [
                ...AparenciaDasTelasDeAcesso::padraoDoPainel(),
                'layout'                => AparenciaDasTelasDeAcesso::LAYOUT_CLASSICO,
                // A opção da transição sobrevive ao login no clássico (RD-D1 da revisão do Adendo 4).
                'transicao_entre_telas' => ($tela['transicao_entre_telas'] ?? true) !== false,
            ];
        }

        unset($tela['telas']);

        if (($tela['formato_dos_textos'] ?? 'texto') === 'texto') {
            $tela['frase']              = self::escaparMarkdown($tela['frase'] ?? null);
            $tela['apoio']              = self::escaparMarkdown($tela['apoio'] ?? null);
            $tela['formato_dos_textos'] = 'markdown';
        }

        return $tela;
    }

    /**
     * Texto puro como Markdown que o mostra literalmente: a pontuação ASCII que o Markdown
     * interpreta escapada — inclusive `&` (entidade) e `:` (autolink de URL), que mudavam a exibição
     * de um texto antigo salvo pelo editor (CX-B2, RD-B5).
     */
    private static function escaparMarkdown(?string $texto): ?string
    {
        return $texto === null ? null : (string) preg_replace('/([\\\\`*_{}\[\]()#+\-.!|<>~&:])/', '\\\\$1', $texto);
    }

    /**
     * O estado do formulário, para gravar.
     *
     * Instalação (`$propria = false`): o clássico grava `null` — o default, idêntico a nunca ter
     * mexido. Organização (`$propria = true`): o clássico grava `['layout' => 'classico']`, porque
     * `null` ali significa "usa a padrão", e a organização pode querer o clássico com a padrão em
     * painel (P-07).
     *
     * @return array<string, mixed>|null
     */
    public static function paraGravar(mixed $estado, bool $propria): ?array
    {
        $normalizado = AparenciaDasTelasDeAcesso::normalizar($estado, 'formulario');

        if ($normalizado !== null) {
            return $normalizado;
        }

        return $propria ? ['layout' => AparenciaDasTelasDeAcesso::LAYOUT_CLASSICO] : null;
    }

    /**
     * O formulário da organização, para gravar: o toggle "Usar a aparência padrão" (que não é
     * coluna e não é desidratado) vira `null` na coluna; desligado, grava a aparência própria.
     *
     * O toggle é lido do estado BRUTO do componente porque não chega ao `$data`; e é a única
     * leitura do bruto — o editor, quando visível, chega validado pelo `$data`.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $estado
     * @return array<string, mixed>
     */
    public static function paraGravarNaOrganizacao(array $data, ?array $estado): array
    {
        $usaPadrao = ($estado['usar_aparencia_padrao'] ?? true) !== false;

        unset($data['usar_aparencia_padrao']);

        $data['aparencia_das_telas'] = $usaPadrao
            ? null
            : self::paraGravar($data['aparencia_das_telas'] ?? null, propria: true);

        return $data;
    }

    /**
     * Grava o rascunho e devolve a URL do preview.
     */
    public static function urlDoPreview(Livewire $livewire, string $escopo, mixed $aparencia): string
    {
        $id      = $livewire->getId();
        $valor   = [
            'escopo'             => $escopo,
            'aparencia'          => AparenciaDasTelasDeAcesso::normalizar($aparencia, 'rascunho'),
            'imagem_temporaria'  => self::imagensTemporarias($aparencia),
        ];
        $versao  = hash('xxh128', (string) json_encode($valor));

        Cache::put(self::chave($id), $valor, now()->addMinutes(self::VIDA_DO_RASCUNHO));

        return route('kit.preview-das-telas-de-acesso', ['rascunho' => $id, 'tela' => 'login', 'v' => $versao]);
    }

    /**
     * A imagem ainda não salva de cada família (P-25): a do login na raiz, as outras em `telas`.
     * É o upload que o Livewire guarda no diretório temporário, como URL assinada do próprio
     * Livewire — o preview mostra o que ainda não foi salvo (P-08). Só PNG, JPEG ou WebP: o
     * `FileUpload` só valida o tipo ao salvar.
     *
     * @return array<string, string>
     */
    private static function imagensTemporarias(mixed $aparencia): array
    {
        if (! is_array($aparencia)) {
            return [];
        }

        $estados = [AparenciaDasTelasDeAcesso::FAMILIA_LOGIN => $aparencia['imagem_do_destaque'] ?? null];

        foreach (AparenciaDasTelasDeAcesso::FAMILIAS_COM_APARENCIA_PROPRIA as $familia) {
            $tela = $aparencia['telas'][$familia] ?? null;

            // A tela que segue o login usa a imagem do login, também no preview (CX-B3): o estado
            // escondido dela não conta.
            $estados[$familia] = is_array($tela) && ($tela['igual_ao_login'] ?? true) === false
                ? ($tela['imagem_do_destaque'] ?? null)
                : $estados[AparenciaDasTelasDeAcesso::FAMILIA_LOGIN];
        }

        return array_filter(array_map(self::imagemTemporaria(...), $estados));
    }

    private static function imagemTemporaria(mixed $estado): ?string
    {
        $arquivo = is_array($estado) && count($estado) === 1 ? reset($estado) : $estado;

        if (! $arquivo instanceof TemporaryUploadedFile
            || ! in_array(strtolower((string) $arquivo->guessExtension()), ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return null;
        }

        return $arquivo->temporaryUrl();
    }

    /**
     * O rascunho do PRÓPRIO usuário com esse id, ou `null`.
     *
     * @return array{escopo: string, aparencia: array<string, mixed>|null, imagem_temporaria: array<string, string>}|null
     */
    public static function lerRascunho(string $id): ?array
    {
        if ($id === '' || auth()->id() === null) {
            return null;
        }

        $valor = Cache::get(self::chave($id));

        if (! is_array($valor) || ! is_string($valor['escopo'] ?? null)) {
            return null;
        }

        return [
            'escopo'            => $valor['escopo'],
            'aparencia'         => is_array($valor['aparencia'] ?? null) ? $valor['aparencia'] : null,
            'imagem_temporaria' => is_array($valor['imagem_temporaria'] ?? null) ? array_filter($valor['imagem_temporaria'], 'is_string') : [],
        ];
    }

    private static function chave(string $id): string
    {
        return 'aparencia-preview:'.auth()->id().':'.$id;
    }

    /**
     * Seletor de cor hexadecimal. `hex()` só troca o formato do picker; a regra é que barra o
     * resto (P-05).
     */
    private static function corHex(string $campo, string $rotulo): ColorPicker
    {
        return ColorPicker::make($campo)
            ->label($rotulo)
            ->hex()
            ->regex(AparenciaDasTelasDeAcesso::FORMATO_DA_COR)
            ->validationMessages(['regex' => 'Use uma cor no formato #RRGGBB.'])
            ->live(onBlur: true);
    }

    /**
     * Lista para o seletor do preview — as telas que a página de demonstração sabe montar.
     *
     * @return array<string, string>
     */
    public static function telas(): array
    {
        return [
            'login'           => 'Login',
            'recuperar-senha' => 'Recuperar senha',
            'cadastro'        => 'Cadastro',
        ];
    }
}
