<?php

namespace App\Filament\Pages\Auth;

use App\Support\ConfiguracaoDoLogin;
use Filament\Facades\Filament;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

/**
 * O cadastro em `/cadastro`, fora dos painéis, quando o login é unificado.
 *
 * Mesmo molde de `TelaLoginUnificada`: estende a tela do painel, redeclara o `$layout`, responde
 * `ehAPaginaUnica()` e serve na rota sem prefixo registrada pelo `KitServiceProvider` sob
 * `web` + `panel:app`. A regra de negócio do cadastro — convite, registro aberto, organização —
 * é toda de `RegistroPorConvite` e não muda: esta classe só decide ONDE a tela responde.
 *
 * Com a chave desligada devolve para `/app/register`, preservando a query (`token`, `org`), que é
 * o inverso exato da guarda que a mãe aplica com a chave ligada. Ver ADR-02 de
 * `wikis/specs/feat/login-unificado-telas-externas/`.
 */
class CadastroUnificado extends RegistroPorConvite
{
    /** Redeclarado de propósito — `.ai/rules/auth.md`: sem isto o layout de auth vaza para toda página. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function mount(): void
    {
        if (! ConfiguracaoDoLogin::unificado()) {
            throw new HttpResponseException(new RedirectResponse(
                Filament::getPanel('app')->getRegistrationUrl(request()->query()) ?? url('/'),
            ));
        }

        parent::mount();
    }

    protected function ehAPaginaUnica(): bool
    {
        return true;
    }
}
