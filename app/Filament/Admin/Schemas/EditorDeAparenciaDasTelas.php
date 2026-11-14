<?php

declare(strict_types=1);

namespace App\Filament\Admin\Schemas;

use App\Support\AparenciaDasTelasDeAcesso;
use App\Support\TetoDeUpload;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
     */
    public static function campos(): Group
    {
        $ehPainel = static fn (Get $get): bool => $get('layout') === AparenciaDasTelasDeAcesso::LAYOUT_PAINEL;

        return Group::make([
            Select::make('layout')
                ->label('Layout')
                ->options([
                    AparenciaDasTelasDeAcesso::LAYOUT_CLASSICO => 'Clássico — arte ao lado do formulário, como sempre foi',
                    AparenciaDasTelasDeAcesso::LAYOUT_PAINEL   => 'Painel de marca — formulário e painel com a sua marca',
                ])
                ->default(AparenciaDasTelasDeAcesso::LAYOUT_CLASSICO)
                ->selectablePlaceholder(false)
                ->required()
                ->live()
                ->helperText('O clássico mantém as telas exatamente como estão hoje. O painel de marca vale para login, cadastro, recuperação e redefinição de senha, confirmação de e-mail, dois fatores e bloqueio de sessão.')
                ->columnSpanFull(),

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
                        ->helperText('O formulário fica com o resto da tela (painel em 35 %, formulário em 65 %). No celular o painel vira uma faixa no topo, em qualquer largura.'),
                ])
                ->columns(2)
                ->visible($ehPainel),

            Fieldset::make('Fundo')
                ->schema([
                    ToggleButtons::make('fundo')
                        ->label('Fundo do painel')
                        ->options(['cor' => 'Cor', 'arte' => 'Arte da identidade'])
                        ->in(AparenciaDasTelasDeAcesso::FUNDOS)
                        ->default('cor')
                        ->inline()
                        ->required()
                        ->live()
                        ->helperText('A arte é a mesma da aba Identidade (ou a gerada com o nome da aplicação).'),

                    ColorPicker::make('cor')
                        ->label('Cor do painel')
                        ->hex()
                        // `hex()` só troca o formato do picker; a regra é que barra o resto (P-05).
                        ->regex(AparenciaDasTelasDeAcesso::FORMATO_DA_COR)
                        ->validationMessages(['regex' => 'Use uma cor no formato #RRGGBB.'])
                        ->live(onBlur: true)
                        ->helperText('Em branco, a cor primária (a da organização, quando houver).')
                        ->visible(fn (Get $get): bool => $get('fundo') === 'cor')
                        // Oculta com fundo de arte, mas GRAVADA: a aparência é um objeto inteiro (P-07), e a
                        // cor escolhida volta se o fundo voltar a ser cor. Sem isto a cópia da padrão perdia
                        // a cor (CT-32).
                        ->dehydratedWhenHidden(),
                ])
                ->columns(2)
                ->visible($ehPainel),

            Fieldset::make('Textos')
                ->schema([
                    TextInput::make('frase')
                        ->label('Frase de destaque')
                        ->rule(static fn (): Closure => self::teto(AparenciaDasTelasDeAcesso::LIMITE_DA_FRASE))
                        ->live(onBlur: true)
                        ->placeholder('Ex.: Tudo o que a sua equipe precisa, num lugar só.')
                        ->helperText('Opcional. Aparece no painel, menor que o título do formulário. Até '.AparenciaDasTelasDeAcesso::LIMITE_DA_FRASE.' caracteres, texto puro.')
                        ->columnSpanFull(),

                    Textarea::make('apoio')
                        ->label('Texto de apoio')
                        ->rule(static fn (): Closure => self::teto(AparenciaDasTelasDeAcesso::LIMITE_DO_APOIO))
                        ->rows(2)
                        ->live(onBlur: true)
                        ->placeholder('Ex.: Acesse com o e-mail e a senha cadastrados.')
                        ->helperText('Opcional. Até '.AparenciaDasTelasDeAcesso::LIMITE_DO_APOIO.' caracteres, texto puro. No celular nenhum texto aparece: só a logo ou o nome da aplicação.')
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

                    ColorPicker::make('cor_da_ilustracao')
                        ->label('Cor da ilustração')
                        ->hex()
                        ->regex(AparenciaDasTelasDeAcesso::FORMATO_DA_COR)
                        ->validationMessages(['regex' => 'Use uma cor no formato #RRGGBB.'])
                        ->live(onBlur: true)
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
        ])
            ->statePath('aparencia_das_telas')
            ->columns(2)
            ->columnSpanFull();
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
     * O teto de um texto, medido como ele é gravado (P-11): pontos de código, depois de tirar os
     * espaços das bordas e os repetidos.
     *
     * Regra própria, e não `maxLength()`: o `maxLength()` mede o valor cru — "  " + 80 + "  "
     * seria recusado embora grave 80 — e ainda põe `maxlength` no HTML, que o navegador conta em
     * unidades UTF-16 (um emoji vale dois). Valor acima do teto é RECUSADO, nunca cortado (P-10).
     *
     * O chamador passa por `fn (): Closure => self::teto(...)`: o Filament AVALIA todo closure
     * entregue a `rule()` com injeção de dependência, e o `$attribute` da regra do Laravel não se
     * resolve ali (`[$attribute] was unresolvable`). O closure de fora devolve a regra.
     */
    private static function teto(int $limite): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($limite): void {
            $limpo = AparenciaDasTelasDeAcesso::textoLimpo($value);

            if ($limpo !== null && mb_strlen($limpo) > $limite) {
                $fail("Use no máximo {$limite} caracteres.");
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

        return $normalizado ?? [
            ...AparenciaDasTelasDeAcesso::padraoDoPainel(),
            'layout' => AparenciaDasTelasDeAcesso::LAYOUT_CLASSICO,
        ];
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
            'imagem_temporaria'  => self::imagemTemporaria(is_array($aparencia) ? ($aparencia['imagem_do_destaque'] ?? null) : null),
        ];
        $versao  = hash('xxh128', (string) json_encode($valor));

        Cache::put(self::chave($id), $valor, now()->addMinutes(self::VIDA_DO_RASCUNHO));

        return route('kit.preview-das-telas-de-acesso', ['rascunho' => $id, 'tela' => 'login', 'v' => $versao]);
    }

    /**
     * A imagem do destaque ainda NÃO salva (o upload que o Livewire guarda no diretório
     * temporário), como URL assinada do próprio Livewire — o preview mostra o que ainda não foi
     * salvo (P-08). Só PNG, JPEG ou WebP: o `FileUpload` só valida o tipo ao salvar.
     */
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
     * @return array{escopo: string, aparencia: array<string, mixed>|null, imagem_temporaria: ?string}|null
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
            'imagem_temporaria' => is_string($valor['imagem_temporaria'] ?? null) ? $valor['imagem_temporaria'] : null,
        ];
    }

    private static function chave(string $id): string
    {
        return 'aparencia-preview:'.auth()->id().':'.$id;
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
