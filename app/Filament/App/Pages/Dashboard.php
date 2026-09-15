<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\Pages\DashboardClassico;
use App\Support\DashboardDinamico;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use MDDev\DynamicDashboard\Pages\DynamicDashboard;

/**
 * Dashboard dinâmico do /app — a raiz do painel quando a feature está ligada.
 *
 * Uma subclasse POR PAINEL, nunca compartilhada: o escopo `available()` do
 * pacote filtra por `dashboards.page = static::class`, então a FQCN desta
 * página É a fronteira entre os dashboards de painéis diferentes.
 *
 * Mora em `/dashboard-dinamico`, NÃO na raiz: a raiz do painel
 * continua sendo do `DashboardClassico`, como sempre foi. É o que faz o update
 * do kit ser inerte (RQ-08) — quem atualiza com a feature desligada não vê a
 * URL canônica dos painéis mudar, e os ~98 testes que abrem `/app`, `/admin` e
 * `/infra` esperando 200 continuam válidos.
 *
 * Com a feature ligada, quem redireciona é o clássico: `/app` devolve para
 * `/app/dashboard-dinamico`. Desligada, o caminho inverso — `mount()` daqui devolve para
 * a raiz. Ver ADR-02.
 */
final class Dashboard extends DynamicDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -2;

    /**
     * `dashboard` pertence ao clássico (rota `/` e o nome histórico). A dinâmica
     * fica em `/dashboard-dinamico` para não colidir com ele — dois slugs iguais
     * gerariam o mesmo nome de rota em painéis diferentes da mesma aplicação.
     */
    protected static ?string $slug = 'dashboard-dinamico';

    /**
     * Qual dashboard está aberto — redeclarada só para ganhar o `#[Locked]`.
     *
     * No vendor a propriedade é pública e sem trava
     * (`DynamicDashboard.php:70-71`), e o `#[Session]` dele só repõe o valor no
     * `mount()` (`livewire/src/Features/SupportSession/BaseSession.php:16-23`):
     * nos requests Livewire seguintes o id vinha do payload, então um
     * `$wire.set('currentDashboardId', <id alheio>)` fazia `persistLayout()` e
     * `createWidget()` escreverem no dashboard de OUTRA organização — o guard
     * `is_locked` do vendor passa justamente porque o dashboard alheio é
     * filtrado pelo scope e `?? false` lê "não travado" (CT-32).
     *
     * O `#[Locked]` barra só a escrita vinda do cliente; o seletor de
     * dashboards do próprio pacote atribui no servidor e segue funcionando.
     * O `#[Session]` é do vendor e precisa ser repetido: atributo de
     * propriedade não é herdado quando a filha redeclara.
     */
    #[Session]
    #[Locked]
    public ?int $currentDashboardId = null;

    public function mount(): void
    {
        if (! DashboardDinamico::atende(self::class)) {
            $this->redirect(DashboardClassico::getUrl(), navigate: true);

            return;
        }

        parent::mount();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return DashboardDinamico::habilitado();
    }

    public function getTitle(): string
    {
        return __('filament-panels::pages/dashboard.title');
    }

    /**
     * Quem pode MONTAR o dashboard — o gancho que o pacote consulta em toda
     * superfície de escrita (botão "Add Widget", ação "Manage", `createWidget()`,
     * `persistLayout()` e o drag do GridStack). Sem override ele é `true` para
     * qualquer um. "Ver" é livre; "gerenciar" é `Manage:Dashboard`.
     */
    public static function canEdit(): bool
    {
        return auth()->user()?->can('Manage:Dashboard') ?? false;
    }
}
