<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * A cor primária do projeto, em duas fontes com precedência declarada.
 *
 * Os três painéis chamam `CorPrimaria::paleta()` em `->colors()`. Uma classe, e
 * não a resolução repetida em cada provider, por causa das guardas: `constant()`
 * com um nome inexistente lança `Error: Undefined constant`, e
 * `Color::generatePalette()` (`vendor/filament/support/src/Colors/Color.php:663`)
 * chama `convertToOklch()` sem validar nada antes. Como isto roda no boot de
 * TODO painel, qualquer um dos dois derrubaria toda página do projeto, não uma
 * tela. Um valor inválido volta ao padrão do Filament.
 *
 * ## As duas fontes, e por que o hexadecimal vence
 *
 * 1. `kit.cor_primaria_hex` — cor de marca livre (`#rgb` ou `#rrggbb`),
 *    gravada na tela /admin/configuracoes-do-kit;
 * 2. `kit.cor_primaria` — nome de uma constante da paleta do Filament, da lista
 *    fechada de `CustomizadorDaInstalacao::CORES`.
 *
 * O hexadecimal vence porque é o campo **mais específico**: quem o digita
 * escolheu aquela cor, enquanto o seletor da lista tem valor padrão e pode nunca
 * ter sido tocado. A precedência inversa tornaria a cor livre inalcançável em
 * toda instalação que escolheu cor no `kit:install` — que é o caminho comum.
 *
 * Hexadecimal fora do formato **cai para o nome**, não zera a paleta: recusar o
 * valor mais específico não é motivo para descartar o menos específico, que pode
 * estar correto.
 *
 * Uma string é devolvida no caso do hexadecimal, e não uma paleta: o
 * `ColorManager` chama `Color::generatePalette()` sozinho quando recebe string
 * (`ColorManager.php:84-85`) — é o mesmo caminho que a cor de uma organização já
 * usa.
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
     * `#rgb` ou `#rrggbb`, e nada mais.
     *
     * A alternância explícita entre 3 e 6 dígitos, e não `{3,6}`: o intervalo
     * aceitaria 4 e 5 dígitos, que não são cor em nenhuma notação e chegariam ao
     * `convertToOklch()`. `A-F` junto de `a-f` porque hexadecimal em maiúsculas é
     * o que a maioria dos manuais de marca entrega.
     */
    private const FORMATO_HEX = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

    /**
     * @return array<string, array<int, string>|string> vazio = mantém o padrão do Filament
     */
    public static function paleta(): array
    {
        $hex = config('kit.cor_primaria_hex');

        if (is_string($hex) && preg_match(self::FORMATO_HEX, $hex) === 1) {
            return ['primary' => $hex];
        }

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
