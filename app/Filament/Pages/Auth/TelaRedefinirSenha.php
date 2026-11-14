<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Concerns\VesteAparenciaDasTelas;
use Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig;
use Caresome\FilamentAuthDesigner\Pages\Auth\ResetPassword;

/**
 * A tela de redefinição de senha (o link do e-mail), dos três painéis.
 *
 * Até a aparência das telas de acesso a página era a do pacote; ela continua sendo — esta classe
 * só existe para a tela receber a aparência escolhida em "Telas de acesso", como as vizinhas.
 * Registrada por `usingResetPage()` no bloco `passwordReset` dos três providers. Sem organização:
 * a aparência é a da instalação (P-06).
 */
class TelaRedefinirSenha extends ResetPassword
{
    use VesteAparenciaDasTelas;

    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function getAuthDesignerConfig(): AuthDesignerConfig
    {
        return $this->aplicarAparencia(parent::getAuthDesignerConfig());
    }
}
