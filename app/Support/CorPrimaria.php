<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * A cor primária do projeto, vinda de `KIT_COR_PRIMARIA`.
 *
 * Os três painéis chamam `CorPrimaria::paleta()` em `->colors()`. Uma classe, e
 * não a resolução repetida em cada provider, por causa da guarda: `constant()`
 * com um nome inexistente lança `Error: Undefined constant` — e como isto roda
 * no boot de todo painel, o erro derrubaria TODA página do projeto, não uma
 * tela. Um `.env` com `KIT_COR_PRIMARIA=Roxo` volta ao padrão do Filament.
 *
 * Não confundir com a cor de uma ORGANIZAÇÃO (multi-tenancy): aquela é
 * registrada mais tarde no ciclo, no `bootUsing()` do AppPanelProvider, e por
 * isso vence esta dentro de /app/{slug}. O `Panel::boot()` registra as cores do
 * painel (`Panel.php:95`) ANTES de rodar os `bootCallbacks`, e o
 * `ColorManager::getColors()` sobrescreve a chave a cada registro na ordem —
 * quem registra por último vence.
 */
final class CorPrimaria
{
    /**
     * @return array<string, array<int, string>|string> vazio = mantém o padrão do Filament
     */
    public static function paleta(): array
    {
        $nome = config('kit.cor_primaria');

        if (! is_string($nome) || $nome === '') {
            return [];
        }

        $constante = Color::class.'::'.$nome;

        if (! defined($constante)) {
            return [];
        }

        return ['primary' => constant($constante)];
    }
}
