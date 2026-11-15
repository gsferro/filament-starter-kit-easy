<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Concerns\VesteAparenciaDasTelas;
use Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig;
use Caresome\FilamentAuthDesigner\Pages\Auth\EmailVerification;

/**
 * O aviso de confirmação de e-mail do `/app` — a página do pacote, vestida com a aparência das
 * telas de acesso. Registrada no `->emailVerification(...)` do `AppPanelProvider`, o único painel
 * em que a rota existe. Sem organização: a aparência é a da instalação (P-06).
 */
class TelaVerificacaoDeEmail extends EmailVerification
{
    use VesteAparenciaDasTelas;

    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function getAuthDesignerConfig(): AuthDesignerConfig
    {
        return $this->aplicarAparencia(parent::getAuthDesignerConfig());
    }
}
