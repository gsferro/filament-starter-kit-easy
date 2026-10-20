<?php

use App\Settings\ConfiguracoesDoKit;

/**
 * Enforcement da rule `.ai/rules/settings.md`, "Lista de painéis no settings declara a semântica do
 * vazio": o kit tem duas semânticas vivas — vazio = TODOS os painéis (login social, dashboard
 * dinâmico) e vazio = NENHUM (dock, agentes de IA) — e a lista nova que copia a vizinha errada
 * grava sem governar o que a tela promete. A declaração tem de estar nos dois lugares que um
 * agente lê antes de copiar: o docblock da propriedade do settings e o `helperText` do `Select`.
 *
 * Origem: `wikis/specs/feat/agentes-ia-no-settings/` (R-cand-2 do step 12).
 */
it('declara no docblock de toda lista de paineis do settings se o vazio e todos ou nenhum', function (): void {
    $classe = new ReflectionClass(ConfiguracoesDoKit::class);
    $listas = array_values(array_filter(
        $classe->getProperties(ReflectionProperty::IS_PUBLIC),
        static fn (ReflectionProperty $p): bool => str_ends_with($p->getName(), '_paineis'),
    ));

    expect($listas)->not->toBeEmpty('o kit deveria ter ao menos uma lista de painéis no settings — o detector está vazio');

    $semSemantica = [];

    foreach ($listas as $propriedade) {
        $docblock = (string) $propriedade->getDocComment();

        if (preg_match('/vazio\s*=\s*\*{0,2}(todos|nenhum)/iu', $docblock) !== 1) {
            $semSemantica[] = '$'.$propriedade->getName();
        }
    }

    expect($semSemantica)->toBe([], 'lista de painéis sem "Vazio = todos" ou "Vazio = nenhum" no docblock');
})->group('kit');

it('declara no helperText de todo select de paineis da tela de configuracoes se em branco e todos ou nenhum', function (): void {
    $fonte        = (string) file_get_contents(app_path('Filament/Admin/Pages/ConfiguracoesDoKit.php'));
    $linhas       = explode("\n", $fonte);
    $selects      = [];
    $semSemantica = [];

    foreach ($linhas as $i => $linha) {
        if (preg_match("/Select::make\\((?:'[a-z_]+_paineis'|\\\$[a-z]+->propriedadeDeSettings\\('paineis'\\))\\)/", $linha) !== 1) {
            continue;
        }

        $selects[] = $i + 1;
        $bloco     = implode("\n", array_slice($linhas, $i, 8));

        if (preg_match('/->helperText\(\'Em branco = (todos|nenhum)/u', $bloco) !== 1) {
            $semSemantica[] = 'linha '.($i + 1).': '.trim($linha);
        }
    }

    expect($selects)->not->toBeEmpty('nenhum Select de painéis achado na tela — o detector está vazio');
    expect($semSemantica)->toBe([], 'Select de painéis sem helperText "Em branco = todos …" ou "Em branco = nenhum …"');
})->group('kit');
