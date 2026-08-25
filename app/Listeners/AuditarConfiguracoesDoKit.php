<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Settings\ConfiguracoesDoKit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Models\Audit;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;

/**
 * A trilha de alteração das configurações do kit, em `audits`.
 *
 * ## Por que um listener, e não a trait do kit
 *
 * O padrão do kit é `App\Traits\AuditsFillables` (`use Auditable` +
 * `getAuditInclude()`), aplicada em `User`, `Tenant`, `Convite`, `Projeto` e
 * `AgenteIa`. Ela **não serve aqui**, e a saída óbvia é pior que não fazer nada:
 *
 * `Spatie\LaravelSettings\Settings` não é model Eloquent — é uma classe abstrata
 * de propriedades tipadas (`vendor/spatie/laravel-settings/src/Settings.php:19`),
 * persistida como linhas chave-valor. Apontar
 * `settings.repositories.database.model` para uma model do kit com a trait
 * auditaria a **criação** e perderia **toda alteração**:
 *
 *   - `createProperty()` usa `->create()` — dispara evento
 *     (`.../SettingsRepositories/DatabaseSettingsRepository.php:56`)
 *   - `updatePropertiesPayload()` usa `->upsert()` — **não** dispara
 *     (`.../DatabaseSettingsRepository.php:74-77`)
 *
 * Resultado: trilha verde e vazia, que é o pior resultado possível para uma
 * trilha. `SavingSettings` (`.../Settings.php:191`) é o único ponto do pacote que
 * carrega valor novo **e** valor antigo juntos. Ver ADR-07.
 *
 * ## As três escolhas que vêm de ler o pacote de UI
 *
 * 1. **`auditable_type` é `SettingsProperty`**, model Eloquent real na tabela
 *    `settings` (`vendor/spatie/laravel-settings/src/Models/SettingsProperty.php:7`),
 *    e `auditable_id` é o id da linha de verdade. A listagem de /infra/audits faz
 *    `->with(['user', 'auditable'])`
 *    (`vendor/tapp/filament-auditing/src/Filament/Resources/Audits/Tables/AuditsTable.php:38`)
 *    — um `auditable_type` que não seja model quebra o eager load do morph e
 *    derruba a tela inteira.
 *
 * 2. **`event` é `settings-updated`, não `updated`.** `RestoreAuditAction` só
 *    fica visível com `event === 'updated'`
 *    (`vendor/tapp/filament-auditing/src/Filament/Actions/RestoreAuditAction.php:46`),
 *    e o restore faz `$record->fill($audit->old_values)` + `save()`
 *    (`vendor/tapp/filament-auditing/src/Concerns/CanRestoreAudit.php:53-54`).
 *    Nossos `old_values` são `['nome_da_aplicacao' => …]` e a linha tem colunas
 *    `group`/`name`/`payload` — o restore produziria SQL inválido. Um nome de
 *    evento diferente esconde o botão sem precisar de policy, e a coluna é
 *    `string` livre na migration de `audits`.
 *
 * 3. **`old_values`/`new_values` usam o NOME da propriedade como chave**, não
 *    `payload`. É o que faz a listagem dizer *o que* mudou em vez de mostrar um
 *    blob anônimo.
 *
 * ## O que esta trilha não é
 *
 * Não é a `Auditable` do owen-it: não existe `$settings->audits()` e não há
 * restauração. É gravação direta na mesma tabela, para aparecer no mesmo lugar
 * que o resto. Declarado, não escondido.
 *
 * ## O registro é automático, e NÃO deve ser repetido no provider
 *
 * O Laravel descobre listeners em `app/Listeners` pela assinatura do `handle()`.
 * Acrescentar `Event::listen(SavingSettings::class, self::class)` num provider
 * registra o listener **duas vezes** e grava **duas linhas idênticas de
 * auditoria** por alteração — foi o que aconteceu na primeira versão desta
 * feature, e só apareceu porque um caso contava os registros. Medido:
 * `app('events')->getListeners(SavingSettings::class)` devolvia 2.
 *
 * `App\Ai\Listeners\RegistrarAiRun` é registrado à mão porque vive FORA de
 * `app/Listeners` e a descoberta não o alcança — os dois padrões convivem, e o
 * que decide qual usar é o diretório.
 *
 * A guarda disso é o caso "registra o listener da trilha uma única vez" em
 * `tests/Kit/ConfiguracoesDoKitTest.php`.
 */
final class AuditarConfiguracoesDoKit
{
    /** O que aparece no lugar do valor de uma propriedade cifrada. */
    public const SEGREDO_MASCARADO = '••••••';

    public function handle(SavingSettings $evento): void
    {
        if (! $evento->settings instanceof ConfiguracoesDoKit) {
            return;
        }

        if (! config('audit.enabled', true) || ! Schema::hasTable(config('audit.drivers.database.table', 'audits'))) {
            return;
        }

        $anteriores = $evento->originalValues?->all() ?? [];
        $cifradas   = ConfiguracoesDoKit::encrypted();
        $gravadas   = [];

        foreach ($evento->properties->all() as $propriedade => $novo) {
            $antigo = $anteriores[$propriedade] ?? null;

            /*
             * `==` e não `===`: o formulário devolve o inteiro da paginação como
             * string ("25"), e o valor lido do banco é inteiro. Uma comparação
             * estrita registraria alteração em toda gravação, o que é o mesmo
             * defeito de não diffar nada.
             */
            if ($antigo == $novo) {
                continue;
            }

            $linha = $this->linhaDaPropriedade((string) $propriedade);

            if ($linha === null) {
                Log::channel('configuracoes')->warning(
                    '[AuditarConfiguracoesDoKit@handle] Propriedade sem linha na tabela, trilha não gravada | propriedade: '.$propriedade,
                    ['propriedade' => $propriedade, 'grupo' => ConfiguracoesDoKit::group()],
                );

                continue;
            }

            $ehSegredo = in_array($propriedade, $cifradas, true);

            Audit::query()->create([
                'user_type'      => auth()->user() === null ? null : auth()->user()->getMorphClass(),
                'user_id'        => auth()->id(),
                'event'          => 'settings-updated',
                'auditable_type' => SettingsProperty::class,
                'auditable_id'   => $linha,
                'old_values'     => [$propriedade => $ehSegredo ? self::SEGREDO_MASCARADO : $antigo],
                'new_values'     => [$propriedade => $ehSegredo ? self::SEGREDO_MASCARADO : $novo],
                'url'            => request()->fullUrl(),
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'tags'           => 'configuracoes-do-kit',
            ]);

            $gravadas[] = $propriedade;
        }

        if ($gravadas === []) {
            return;
        }

        Log::channel('configuracoes')->info(
            '[AuditarConfiguracoesDoKit@handle] Trilha de alteração gravada | propriedades: '.count($gravadas),
            ['alteradas' => $gravadas, 'user_id' => auth()->id(), 'grupo' => ConfiguracoesDoKit::group()],
        );
    }

    /**
     * O id da linha de `settings` daquela propriedade.
     *
     * `null` quando a propriedade não tem linha — acontece quando alguém
     * acrescenta propriedade à classe e esquece a migration de settings. O
     * `SavingSettings` dispara ANTES da gravação, então a linha ainda não existe
     * nesse caso, e é isso que o `warning` do chamador registra.
     */
    private function linhaDaPropriedade(string $propriedade): ?int
    {
        $id = SettingsProperty::query()
            ->where('group', ConfiguracoesDoKit::group())
            ->where('name', $propriedade)
            ->value('id');

        return is_numeric($id) ? (int) $id : null;
    }
}
