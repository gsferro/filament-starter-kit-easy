<?php

use Illuminate\Support\Facades\File;

/**
 * Varredura de fonte pelas normas do Filament Blueprint que se verificam por texto.
 *
 * Cada linha da tabela é uma norma extraída das referências de planejamento do `filament/blueprint`
 * (`05-norma-blueprint.md` da wiki `aderencia-ao-blueprint`), confirmada no vendor antes de virar
 * regra:
 *
 * - **`ignoreRecord: true` é redundante** — `CanBeValidated.php:34` fixa
 *   `$shouldUniqueValidationIgnoreRecordByDefault = true`, consumido em `:566` e `:598`. A auditoria
 *   achou 6 ocorrências, todas ruído. Ruído em validação é o que esconde a chamada que importa.
 * - **`->reactive()` não existe no v5** — é `->live()`.
 * - **`Filament\Forms\Get`/`Set` são o namespace antigo** — o certo é
 *   `Filament\Schemas\Components\Utilities\{Get,Set}`.
 * - **Helpers de teste depreciados** — `vendor/filament/{pacote}/.stubs.php` marca `assertFormSet`,
 *   `callTableAction`, `assertTableActionExists` e `assertTableBulkActionExists` como `@deprecated`.
 *   Funcionam hoje e somem no próximo major; o teste escrito com eles quebra no upgrade.
 *
 * A varredura ignora comentários e docblocks, senão ela acusaria a própria explicação do erro — e
 * acusaria este arquivo. Explicar não é cometer.
 *
 * Ver ADR-04 da wiki: enforço automático antes de prosa.
 */

/**
 * @return list<array{padrao: string, motivo: string}>
 */
function normasTextuaisDoBlueprint(): array
{
    return [
        ['padrao' => '/->(scopedUnique|unique)\([^)]*ignoreRecord:\s*true/', 'motivo' => 'ignoreRecord: true é redundante no v4+ (CanBeValidated.php:34)'],
        ['padrao' => '/->reactive\(/', 'motivo' => '->reactive() não existe no v5; use ->live()'],
        ['padrao' => '/^use Filament\\\\Forms\\\\(Get|Set);/m', 'motivo' => 'namespace antigo; use Filament\Schemas\Components\Utilities\{Get,Set}'],
        ['padrao' => '/\b(BelongsToSelect|MultiSelect|BadgeColumn|BooleanColumn|DateColumn)::make\(/', 'motivo' => 'componente que não existe no v5 (checklist.md do Blueprint)'],
    ];
}

/**
 * @return list<array{padrao: string, motivo: string}>
 */
function helpersDepreciadosDeTeste(): array
{
    return [
        ['padrao' => '/->assertFormSet\(/', 'motivo' => '@deprecated — use assertSchemaStateSet() (vendor/filament/forms/.stubs.php)'],
        ['padrao' => '/->callTableAction\(/', 'motivo' => '@deprecated — use callAction() (vendor/filament/tables/.stubs.php)'],
        ['padrao' => '/->assertTableActionExists\(/', 'motivo' => '@deprecated — use assertActionExists()'],
        ['padrao' => '/->assertTableBulkActionExists\(/', 'motivo' => '@deprecated — use assertActionExists()'],
    ];
}

/** Remove comentários de linha e de bloco, para a varredura não acusar quem explica o erro. */
function semComentarios(string $php): string
{
    $semBlocos = preg_replace('#/\*.*?\*/#s', '', $php) ?? $php;

    return preg_replace('#^\s*//.*$#m', '', $semBlocos) ?? $semBlocos;
}

/**
 * @param  list<array{padrao: string, motivo: string}>  $normas
 * @return list<string>
 */
function violacoesEm(string $raiz, array $normas): array
{
    $violacoes = [];
    $arquivos  = File::allFiles(base_path($raiz));

    expect($arquivos)->not->toBeEmpty("Âncora de população: a varredura de {$raiz} não leu arquivo nenhum.");

    foreach ($arquivos as $arquivo) {
        if ($arquivo->getExtension() !== 'php') {
            continue;
        }

        $fonte = semComentarios($arquivo->getContents());

        foreach ($normas as ['padrao' => $padrao, 'motivo' => $motivo]) {
            if (preg_match($padrao, $fonte)) {
                $violacoes[] = $arquivo->getRelativePathname().' — '.$motivo;
            }
        }
    }

    return $violacoes;
}

it('nao tem em app/ nenhuma construcao que o Blueprint marca como errada no v5', function (): void {
    $violacoes = violacoesEm('app', normasTextuaisDoBlueprint());

    expect($violacoes)->toBe([], "Violações:\n".implode("\n", $violacoes));
})->group('kit');

it('nao usa em tests/ nenhum helper que o Filament marca como @deprecated', function (): void {
    /*
     * Este arquivo cita os helpers pelo nome dentro de strings de regex, não em chamadas — e a
     * varredura remove comentários, mas não strings. Ele fica de fora da própria varredura pelo
     * motivo óbvio, e é o único.
     */
    $violacoes = array_values(array_filter(
        violacoesEm('tests', helpersDepreciadosDeTeste()),
        static fn (string $v): bool => ! str_starts_with($v, 'Kit'.DIRECTORY_SEPARATOR.'AderenciaAoBlueprintTest.php'),
    ));

    expect($violacoes)->toBe([], "Helpers depreciados:\n".implode("\n", $violacoes));
})->group('kit');

/**
 * BulkAction de escrita autoriza registro a registro, ou não autoriza nada.
 *
 * `BulkAction` pergunta só o verbo `*Any` da policy (`deleteAny`, `forceDeleteAny`, …) e **nunca**
 * consulta a policy de cada registro selecionado sem `authorizeIndividualRecords()`
 * (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:252-266`, onde a string vira
 * `Gate::inspect($ability, $record)`).
 *
 * Medido: com `RolePolicy::delete()` negando o papel `master_global` para quem não é
 * administrador da instalação, a exclusão em massa apagava esse papel assim mesmo — a guarda por
 * registro simplesmente não era consultada. Um teste de exclusão em MASSA achou o que o teste de
 * exclusão individual dava por fechado.
 *
 * Hoje a única policy do kit que decide por registro é a de papéis. A varredura existe para o dia
 * em que a próxima decidir: o buraco reabre em silêncio, com o diff parecendo correto.
 *
 * @return list<string>
 */
function bulkActionsSemAutorizacaoPorRegistro(): array
{
    $violacoes = [];
    $arquivos  = File::allFiles(base_path('app/Filament'));

    expect($arquivos)->not->toBeEmpty('Âncora de população: a varredura de app/Filament não leu arquivo nenhum.');

    foreach ($arquivos as $arquivo) {
        if ($arquivo->getExtension() !== 'php') {
            continue;
        }

        $fonte = semComentarios($arquivo->getContents());

        // O encadeamento pode quebrar linha, então a busca é sobre a chamada e o que a segue
        // até o fim da expressão — `,` ou `;` no mesmo nível de indentação já bastam.
        if (preg_match_all('/\b(\w*BulkAction)::make\((.*?)\)((?:\s*->\w+\([^;]*?\))*)/s', $fonte, $achados, PREG_SET_ORDER) === 0) {
            continue;
        }

        foreach ($achados as $achado) {
            if (str_contains($achado[3], 'authorizeIndividualRecords')) {
                continue;
            }

            $violacoes[] = $arquivo->getRelativePathname().' — '.$achado[1]
                .'::make() sem ->authorizeIndividualRecords(): a policy do registro não é consultada';
        }
    }

    return $violacoes;
}

it('nao tem BulkAction de escrita sem autorizacao por registro', function (): void {
    $violacoes = bulkActionsSemAutorizacaoPorRegistro();

    expect($violacoes)->toBe([], "BulkAction sem guarda por registro:\n".implode("\n", $violacoes));
})->group('kit');
