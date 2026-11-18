<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Pages\MyProfilePage;
use App\Models\User;
use App\Support\AparenciaDoUsuario;
use App\Support\CorPrimaria;
use App\Support\CustomizadorDaInstalacao;
use App\Support\DensidadeDoLayout;
use App\Support\ModoDoMenu;
use App\Support\TamanhoDaFonte;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

/**
 * A seção "Aparência" do Meu perfil: densidade, fonte, cor e menu de cada pessoa (wiki
 * `feat/preferencias-de-aparencia`).
 *
 * Registrada nos três painéis por `->myProfileComponents()` do Breezy, como `DefinirSenhaPorEmail`.
 * Cada campo começa no "Padrão" — usar é opcional (RQ-10) — e a escolha é UMA por pessoa, valendo
 * nos três painéis e em toda organização que libera (P-01).
 *
 * ## Duas guardas da chave da instalação, e só a segunda é barreira
 *
 * `canView()` tira a seção da tela quando a chave está desligada (P-06). Mas a tela pode ter sido
 * aberta ANTES de alguém desligar a chave, e `salvar()` é chamável pelo `$wire` a qualquer momento:
 * por isso ele confere de novo e recusa (falha fechada). A chave da organização não entra aqui: a
 * escolha é uma só e vale em várias organizações, então gravar não depende de onde a pessoa está —
 * quem aplica a chave da organização é `AparenciaDoUsuario`, ao servir cada tela (P-13).
 *
 * ## O que chega do navegador
 *
 * `$data` é público e escrito pelo cliente. O `Select` recusa valor fora das opções na validação,
 * e mesmo assim o que é gravado passa por `AparenciaDoUsuario::escolhas()`, a mesma redução ao
 * vocabulário que a leitura usa (P-12). O log leva só esses valores reduzidos (`.ai/rules/livewire.md`).
 *
 * @property-read Schema $form
 */
class PreferenciasDeAparencia extends MyProfileComponent
{
    // Sem campo de upload no schema: fecha o canal `_startUpload` do Livewire, como em `DefinirSenhaPorEmail`.
    use RestrictsFileUploadsToSchemaComponents;

    /**
     * Depois do 2FA e das passkeys, antes das sessões do navegador. Sem tipo nativo porque o pai
     * declara a propriedade sem tipo, e o PHP proíbe o filho de acrescentar um.
     *
     * @var int
     */
    public static $sort = 45;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected string $view = 'livewire.preferencias-de-aparencia';

    public static function canView(): bool
    {
        return (bool) config('kit.aparencia_por_usuario');
    }

    public function mount(): void
    {
        $pessoa = Filament::auth()->user();

        $this->form->fill($pessoa instanceof User ? AparenciaDoUsuario::escolhas($pessoa->aparencia) : []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('densidade')
                    ->label('Densidade do layout')
                    ->options(DensidadeDoLayout::opcoes())
                    ->placeholder('Padrão — '.self::rotuloCurto(DensidadeDoLayout::deConfig()->getLabel())),

                Select::make('fonte')
                    ->label('Tamanho da fonte')
                    ->options(TamanhoDaFonte::opcoes())
                    ->placeholder('Padrão — '.self::rotuloCurto(TamanhoDaFonte::deConfig()->getLabel())),

                // A lista fechada do kit, a mesma da instalação e da organização (P-07).
                Select::make('cor')
                    ->label('Cor primária')
                    ->options(array_combine(CustomizadorDaInstalacao::CORES, CustomizadorDaInstalacao::CORES))
                    ->placeholder('Padrão — '.self::corPadraoDaAplicacao().(config('kit.tenancy.enabled') ? ' (ou a da organização, dentro dela)' : '')),

                Select::make('menu')
                    ->label('Menu lateral ao recolher')
                    ->options(ModoDoMenu::opcoes())
                    ->placeholder('Padrão — '.ModoDoMenu::deConfig()->getLabel()),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function salvar(): void
    {
        $pessoa = Filament::auth()->user();

        if (! $pessoa instanceof User) {
            return;
        }

        if (! self::canView()) {
            Log::warning(
                "[PreferenciasDeAparencia@salvar] Gravação recusada: aparência pessoal desligada | user: {$pessoa->getKey()}",
                ['user_id' => $pessoa->getKey()],
            );

            Notification::make()
                ->title('A aparência pessoal foi desligada nesta aplicação.')
                ->body('Nada foi gravado. Recarregue a página.')
                ->danger()
                ->send();

            return;
        }

        $escolhas = array_filter(
            AparenciaDoUsuario::escolhas($this->form->getState()),
            static fn (?string $valor): bool => $valor !== null,
        );

        // Tudo no padrão grava `null`, e não `{}`: a coluna volta ao estado de quem nunca escolheu.
        $pessoa->forceFill(['aparencia' => $escolhas === [] ? null : $escolhas])->save();

        Log::info(
            "[PreferenciasDeAparencia@salvar] Aparência pessoal salva | user: {$pessoa->getKey()}",
            ['user_id' => $pessoa->getKey(), 'aparencia' => $escolhas],
        );

        Notification::make()
            ->title('Aparência salva')
            ->success()
            ->send();

        // Densidade, fonte e cor saem do `<head>`: só um carregamento novo da página as aplica.
        $this->redirect(MyProfilePage::getUrl());
    }

    /**
     * A cor da aplicação que vale para quem deixa em "Padrão": o hexadecimal livre, senão o nome da
     * paleta, senão a do Filament — a mesma precedência de `CorPrimaria::paleta()` (BP-19 da revisão
     * contra o Blueprint). A da organização não entra: o Meu perfil serve as organizações todas.
     */
    private static function corPadraoDaAplicacao(): string
    {
        $hex  = config('kit.cor_primaria_hex');
        $nome = config('kit.cor_primaria');

        return match (true) {
            is_string($hex) && CorPrimaria::resolver($hex, null) !== []   => $hex,
            is_string($nome) && CorPrimaria::resolver(null, $nome) !== [] => $nome,
            default                                                       => 'Amber, a do Filament',
        };
    }

    /** "Confortável — o padrão do Filament, nada muda" → "Confortável". */
    private static function rotuloCurto(string $rotulo): string
    {
        return Str::before($rotulo, ' —');
    }
}
