<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Admin\Schemas\EditorDeAparenciaDasTelas;
use App\Filament\Concerns\VesteAparenciaDasTelas;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AparenciaDasTelasDeAcesso;
use Caresome\FilamentAuthDesigner\Concerns\HasAuthDesignerLayout;
use Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;

/**
 * O documento que o iframe do editor de "Telas de acesso" abre: uma tela de autenticação de
 * demonstração, vestida com a aparência AINDA NÃO SALVA (ADR-04 da wiki
 * `feat/aparencia-das-telas-de-acesso`).
 *
 * Não é a tela de login real de propósito: a real redireciona quem já está autenticado, escreve na
 * sessão, conta tentativas e mostra os botões sociais — e não conhece o rascunho. Esta usa o MESMO
 * layout e o MESMO partial do painel de marca, com campos desabilitados e nenhuma ação (P-08).
 *
 * Fronteira: rota do kit sob `panel:admin` + `auth` (`KitServiceProvider`); a página exige acesso ao
 * `/admin` (`canAccessPanel()`), em todo request. O `mount()` só aceita
 * rascunho gravado pelo PRÓPRIO usuário (a chave do cache leva o id dele) e autoriza o escopo
 * gravado no rascunho: configurações para `global`, `update` da organização para `tenant:{id}`,
 * `create` para `tenant:novo`. Sem rascunho: 404; escopo negado: 403.
 *
 * @property-read Schema $form
 */
class PreviewDasTelasDeAcesso extends SimplePage
{
    /*
     * A trait do pacote dá o layout e faz a tela contar como "de autenticação" para o kit (sem dock
     * nem chat). Alias protegido: a classe declara o próprio `getAuthDesignerConfig()`.
     */
    use HasAuthDesignerLayout {
        getAuthDesignerConfig as protected configBaseDoAuthDesigner;
    }
    use VesteAparenciaDasTelas;

    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public const TELAS = ['login', 'recuperar-senha', 'cadastro'];

    #[Locked]
    public string $rascunho = '';

    #[Locked]
    public string $tela = 'login';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $tela       = request()->query('tela');
        $this->tela = is_string($tela) && in_array($tela, self::TELAS, true) ? $tela : 'login';

        $id = request()->query('rascunho');
        $id = is_string($id) ? $id : '';

        $rascunho = EditorDeAparenciaDasTelas::lerRascunho($id);

        if ($rascunho === null) {
            $this->recusar('rascunho_ausente', 404);
        }

        if (! $this->podeVer($rascunho['escopo'])) {
            $this->recusar('escopo_negado', 403, $rascunho['escopo']);
        }

        $this->rascunho = $id;
        $this->form->fill();
    }

    public function getAuthDesignerConfig(): AuthDesignerConfig
    {
        return AparenciaDasTelasDeAcesso::semAlternadorDeTema(
            $this->aplicarAparencia($this->configBaseDoAuthDesigner()),
        );
    }

    public function getTitle(): string|Htmlable
    {
        return 'Pré-visualização';
    }

    public function getHeading(): string|Htmlable|null
    {
        return match ($this->tela) {
            'recuperar-senha' => 'Esqueceu sua senha?',
            'cadastro'        => 'Cadastre-se',
            default           => 'Faça login',
        };
    }

    public function form(Schema $schema): Schema
    {
        $campos = match ($this->tela) {
            'recuperar-senha' => [
                TextInput::make('email')->label('E-mail')->email()->disabled(),
            ],
            'cadastro' => [
                TextInput::make('name')->label('Nome')->disabled(),
                TextInput::make('email')->label('E-mail')->email()->disabled(),
                TextInput::make('password')->label('Senha')->password()->disabled(),
            ],
            default => [
                TextInput::make('email')->label('E-mail')->email()->disabled(),
                TextInput::make('password')->label('Senha')->password()->disabled(),
                Checkbox::make('remember')->label('Lembre de mim')->disabled(),
            ],
        };

        return $schema->components($campos)->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        $rotulo = match ($this->tela) {
            'recuperar-senha' => 'Enviar e-mail',
            'cadastro'        => 'Cadastrar',
            default           => 'Entrar',
        };

        return $schema->components([
            $this->getFormContentComponent(),
            // Botão desabilitado, sem Action: a demonstração não envia nada e não há o que autorizar.
            View::make('filament.pages.auth.botao-de-demonstracao')->viewData(['rotulo' => $rotulo]),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return EmbeddedSchema::make('form');
    }

    protected function getAuthDesignerPageKey(): string
    {
        return $this->tela === 'login' ? 'login' : 'password-reset';
    }

    /** @return array<string, mixed>|null */
    protected function resolverAparencia(): ?array
    {
        return AparenciaDasTelasDeAcesso::normalizar(
            $this->rascunhoAutorizado()['aparencia'] ?? null,
            'preview',
        );
    }

    /**
     * A organização do escopo do rascunho, SEM as condições de P-06: o preview roda no `/admin` e
     * mostra a aparência daquela organização a quem pode editá-la. Só cor e logo dependem dela.
     */
    protected function organizacaoDaAparencia(): ?Tenant
    {
        $escopo = $this->rascunhoAutorizado()['escopo'] ?? null;

        if (! is_string($escopo) || ! preg_match('/^tenant:(\d+)$/', $escopo, $partes)) {
            return null;
        }

        return Tenant::query()->find((int) $partes[1]);
    }

    /**
     * O rascunho, de novo autorizado a cada request — não só no `mount()`: quem perde a permissão
     * com o preview aberto deixa de ver o escopo no próximo update Livewire (SEC-03/CX-01).
     *
     * @return array{escopo: string, aparencia: array<string, mixed>|null}|null
     */
    private function rascunhoAutorizado(): ?array
    {
        $rascunho = EditorDeAparenciaDasTelas::lerRascunho($this->rascunho);

        return $rascunho !== null && $this->podeVer($rascunho['escopo']) ? $rascunho : null;
    }

    private function podeVer(string $escopo): bool
    {
        /*
         * Quem não entra no `/admin` não vê preview nenhum (P-17). Explícito, e não pelo
         * `Authenticate` do Filament na rota: o Laravel ordena os middlewares por prioridade, o
         * `Authenticate` roda ANTES do `SetUpPanel:admin` e decide pelo painel default (`app`) —
         * QA-01 do quality gate, ciclo 1.
         */
        $usuario = auth()->user();
        if (! $usuario instanceof User || ! $usuario->canAccessPanel(Filament::getPanel('admin'))) {
            return false;
        }

        if ($escopo === 'global') {
            return ConfiguracoesDoKit::canAccess();
        }

        if ($escopo === 'tenant:novo') {
            return Gate::allows('create', Tenant::class);
        }

        if (preg_match('/^tenant:(\d+)$/', $escopo, $partes) === 1) {
            $organizacao = Tenant::query()->find((int) $partes[1]);

            return $organizacao instanceof Tenant && Gate::allows('update', $organizacao);
        }

        return false;
    }

    private function recusar(string $motivo, int $status, ?string $escopo = null): never
    {
        Log::channel('autenticacao')->warning(
            '[PreviewDasTelasDeAcesso@mount] Preview recusado | user: '.auth()->id()." - motivo: {$motivo}",
            ['user_id' => auth()->id(), 'motivo' => $motivo, 'escopo' => $escopo],
        );

        abort($status);
    }
}
