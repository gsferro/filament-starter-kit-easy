<?php

namespace App\Support\ImportExport;

use App\Models\Tenant;
use App\Models\User;
use App\Support\ContextoDePapeis;
use App\Traits\BelongsToTenant;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Importer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Import do Filament COM fronteira de organização — a peça que o pacote não entrega.
 *
 * O `Importer` do Filament avisa no próprio código o que não faz
 * (`vendor/filament/actions/src/Imports/Importer.php:157-160`):
 *
 *     // Security: This method runs without policy checks.
 *
 * E não é só policy que falta. O `resolveRecord()` roda **dentro do worker**, e
 * `BelongsToTenant` só aplica `where tenant_id = X` quando `Filament::getTenant()`
 * devolve um `Tenant`. Em fila não há painel nem rota na sessão: devolve `null`, e o
 * escopo global vira **no-op**. `ImportCsv:72` restaura o `auth()->setUser($user)` — o
 * usuário, não o tenant. Nada restaura o tenant.
 *
 * As duas consequências, e as duas são silenciosas:
 *
 * | Linha do CSV | Sem esta classe |
 * |---|---|
 * | com chave de OUTRA organização | UPDATE no registro alheio, sem 403 e sem log |
 * | nova | nasce com `tenant_id` **nulo**, porque o hook `creating` não tem tenant |
 *
 * A correção é passar o `tenant_id` por `->options()` na **Action** — no request, onde o
 * tenant existe — e usá-lo aqui nas duas pontas: filtrar a resolução e preencher a
 * criação.
 *
 * **Fail-closed.** Tenancy ligada, model escopado e nenhum `tenant_id` nas options: a
 * linha é recusada com `RowImportFailedException` (vai para `failed_import_rows`, visível
 * na notificação) e o motivo é logado. O contrário — seguir sem escopo — é exatamente o
 * defeito que esta classe existe para fechar.
 *
 * Formula injection sai LIGADA (`$shouldPreventFormulaInjection`), ao contrário do
 * default do pacote: o CSV de linhas que falharam é devolvido para download, e sem isso
 * ele carrega `=cmd|…` intacto até a planilha de quem abrir.
 *
 * Uso:
 *
 * ```php
 * class ProjetoImporter extends ImportadorDoKit
 * {
 *     protected static ?string $model = Projeto::class;
 *
 *     protected function colunaDeResolucao(): string
 *     {
 *         return 'nome';
 *     }
 *
 *     public static function getColumns(): array { ... }
 * }
 * ```
 */
abstract class ImportadorDoKit extends Importer
{
    protected static bool $shouldPreventFormulaInjection = true;

    /**
     * A coluna que decide se a linha do CSV é um registro que já existe.
     *
     * Não é a chave primária por default de propósito: CSV vindo de planilha raramente
     * traz ID, e resolver por ID sem escopo é o caminho mais curto para escrever no
     * registro de outra organização.
     */
    abstract protected function colunaDeResolucao(): string;

    /**
     * Registro que a linha vai preencher — existente **dentro da organização**, ou novo.
     *
     * `firstOrNew()` do pacote não serve aqui porque ele não recebe o `where` do tenant.
     */
    public function resolveRecord(): Model
    {
        $coluna = $this->colunaDeResolucao();
        $valor  = $this->data[$coluna] ?? null;

        /** @var class-string<Model> $model */
        $model = static::getModel();

        $query = $model::query();

        if ($this->exigeEscopoDeTenant()) {
            $query->where('tenant_id', $this->tenantId());
        }

        return (blank($valor) ? null : $query->where($coluna, $valor)->first())
            ?? new $model;
    }

    /**
     * O `creating` de `BelongsToTenant` não tem tenant dentro do worker, então o
     * preenchimento acontece aqui — antes do `save()`, e só quando o registro é novo.
     */
    protected function beforeSave(): void
    {
        $this->exigirPermissaoDoOperador();

        if (! $this->exigeEscopoDeTenant()) {
            return;
        }

        if ($this->record->exists) {
            return;
        }

        $this->record->setAttribute('tenant_id', $this->tenantId());
    }

    /**
     * A policy que o `Importer` do Filament avisa não consultar ("runs without policy checks").
     *
     * `Import:Projeto` abre a Action; criar ou alterar cada linha exige `Create:`/`Update:` do
     * model — senão quem só pode importar edita por CSV o que a tela não deixa. O operador é
     * o da importação (`imports.user_id`), não o `auth()` do worker, e a consulta roda no
     * contexto da organização: com teams ligado, `can()` só enxerga os papéis do team fixado.
     *
     * @throws RowImportFailedException
     */
    private function exigirPermissaoDoOperador(): void
    {
        $operador = $this->import->user;
        $acao     = $this->record->exists ? 'update' : 'create';
        $contexto = $this->exigeEscopoDeTenant() ? (int) $this->tenantId() : Tenant::CONTEXTO_GLOBAL;

        $autorizado = $operador instanceof User
            && ContextoDePapeis::em($contexto, $operador, fn (): bool => $operador->can($acao, $this->record));

        if ($autorizado) {
            return;
        }

        Log::channel('tenancy')->warning(
            "[ImportadorDoKit@exigirPermissaoDoOperador] Linha recusada: operador sem permissão de {$acao}"
            ." | import_id: {$this->import->getKey()} | importer: ".static::class,
            ['import_id' => $this->import->getKey(), 'importer' => static::class, 'user_id' => $this->import->getAttribute('user_id'), 'acao' => $acao],
        );

        throw new RowImportFailedException(
            $acao === 'update' ? 'Sem permissão para alterar este registro.' : 'Sem permissão para criar este registro.'
        );
    }

    /**
     * `true` quando esta importação precisa de organização para ser segura.
     *
     * Tenancy desligada, ou model que não é de organização (`AgenteIa`, `Tenant`): não há
     * fronteira a aplicar, e exigir uma faria a feature morrer no modo single-tenant.
     *
     * @throws RowImportFailedException quando a fronteira é necessária e o `tenant_id`
     *                                  não chegou nas options
     */
    protected function exigeEscopoDeTenant(): bool
    {
        if (! config('kit.tenancy.enabled')) {
            return false;
        }

        if (! in_array(BelongsToTenant::class, class_uses_recursive(static::getModel()), true)) {
            return false;
        }

        if (blank($this->tenantId())) {
            Log::channel('tenancy')->warning(
                '[ImportadorDoKit@exigeEscopoDeTenant] Importação recusada: tenant_id ausente nas options'
                ." | import_id: {$this->import->getKey()} | importer: ".static::class,
                ['import_id' => $this->import->getKey(), 'importer' => static::class],
            );

            throw new RowImportFailedException(
                'Importação sem organização definida. A action precisa passar o tenant_id em ->options().'
            );
        }

        return true;
    }

    /** O `tenant_id` capturado no request pela Action, não o do contexto — que aqui não existe. */
    protected function tenantId(): int|string|null
    {
        $id = $this->options['tenant_id'] ?? null;

        return is_int($id) || is_string($id) ? $id : null;
    }
}
