<?php

namespace App\Livewire;

use App\Support\EmailComMais;
use Closure;
use Filament\Forms\Components\TextInput;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;

/**
 * O bloco "Informações pessoais" do perfil do Breezy, com a regra do "+" no e-mail.
 *
 * Sem ela, quem se cadastrou como `fulano@` trocava o e-mail para `fulano+1@` no perfil e liberava
 * o `fulano@` para um segundo cadastro — a mesma porta por outro caminho (Q2 e P-03 da wiki
 * `email-com-mais`). O e-mail atual da conta passa, mesmo com "+" (P-04).
 *
 * Registrado como `personal_info` no `myProfileComponents()` dos três painéis: no merge do Breezy
 * o que o painel registra vence o default que o `boot()` do plugin acrescenta depois.
 */
class InformacoesPessoais extends PersonalInfo
{
    protected function getEmailComponent(): TextInput
    {
        return parent::getEmailComponent()
            ->rule(fn (): Closure => EmailComMais::regra($this->user?->getAttribute('email')));
    }
}
