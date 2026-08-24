<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Support\Paineis;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use LogicException;
use Override;
use Spatie\Permission\Models\Role as SpatieRole;

class EditRole extends EditRecord
{
    /** @var Collection<int, string> */
    public Collection $permissions;

    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Semeia o state com as permissões de TODOS os painéis — não só do que está visível.
     *
     * ## O defeito que isto conserta, e ele apagava dado
     *
     * `HasShieldFormComponents::setPermissionStateForRecordPermissions()` só hidrata o
     * CheckboxList `if ($component->isVisible() ...)`
     * (`vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:81`). Com o
     * collapse por painel do vendor todas as seções estavam visíveis, então a guarda nunca
     * pesava. Com o **tab vertical** que esta tela passou a usar, só a aba ativa está visível:
     * as seções dos outros dois painéis nasciam com o state VAZIO, e o `syncPermissions()` do
     * `afterSave()` sincroniza com o que está no state — apagando o que não veio.
     *
     * Medido antes da correção: abrir o papel `infra` e clicar em Salvar **sem tocar em nada**
     * levava 140 permissões para 15. O `panel_user` ia de 17 para 3. Sobrevivia só o que
     * calhava de estar numa seção da aba aberta.
     *
     * ## Por que aqui, e não num `afterStateHydrated` próprio
     *
     * O hook do vendor roda DEPOIS deste `mutateFormDataBeforeFill()`, e ele só chama
     * `$component->state()` quando o componente **está** visível — com o valor certo. Ou seja:
     * na aba visível o vendor sobrescreve o que semeamos, com o mesmo conteúdo; nas invisíveis
     * ele não toca, e o que semeamos aqui é o que fica. Sobrescrever o hook seria duplicar a
     * lógica dele e brigar por precedência.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $papel = $this->record;

        if (! $papel instanceof SpatieRole) {
            return $data;
        }

        foreach (Paineis::resources() as $entidades) {
            foreach ($entidades as $entidade) {
                /** @var array<string, string> $permissoes */
                $permissoes = $entidade['permissions'];

                $data[$entidade['resourceFqcn']] = array_values(array_filter(
                    array_keys($permissoes),
                    /*
                     * `rescue` porque `checkPermissionTo()` lança quando a permissão não existe
                     * no banco — estado real de quem mudou a matriz do Shield e não ressemeou.
                     * Ali a resposta certa é "não tem", não derrubar a tela de papéis.
                     */
                    static fn (string $permissao): bool => rescue(
                        static fn (): bool => $papel->checkPermissionTo($permissao),
                        false,
                        report: false,
                    ),
                ));
            }
        }

        return $data;
    }

    #[Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(fn (mixed $permission, string $key): bool => ! in_array($key, ['name', 'guard_name', 'painel', 'select_all', Utils::getTenantModelForeignKey()], true))
            ->values()
            ->flatten()
            ->unique();

        if (Utils::isTenancyEnabled() && Arr::has($data, Utils::getTenantModelForeignKey()) && filled($data[Utils::getTenantModelForeignKey()])) {
            return Arr::only($data, ['name', 'guard_name', 'painel', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name', 'painel']);
    }

    /**
     * O conjunto que este formulário consegue MOSTRAR — e é o único que ele pode sincronizar.
     *
     * Lido da mesma fonte que monta o schema, e não dos componentes: `getOptions()` de um
     * `CheckboxList` cujas opções são Closure não devolve nada fora do ciclo de render, e
     * `getFlatComponents()` ignora o que está oculto por default — as duas tentativas
     * anteriores deram `[]`, o que fazia `(atuais − oferecidas)` preservar tudo e a tela virar
     * somente-adição. Quem pegou foi o caso que prova a remoção.
     *
     * As duas metades, e a assimetria é o defeito de origem:
     *
     * - **Resources**: dos TRÊS painéis, via `App\Support\Paineis`, que varre trocando o painel
     *   corrente. É o que `RoleResource::checkboxListDaEntidade()` usa para montar as seções.
     * - **Pages e Widgets**: só do painel CORRENTE, porque é o que o Shield oferece — o
     *   `FilamentShield` é scoped, e as abas de Páginas e Widgets saem dele. Permissão de Page
     *   ou Widget de `/app` e `/infra` não aparece nesta tela, então ela não entra aqui e é
     *   preservada por construção.
     *
     * O dia em que o kit varrer pages/widgets por painel, esta segunda metade cresce e a
     * primeira não muda — e o caso de teste do par continua sendo o oráculo.
     *
     * @return Collection<int, string>
     */
    private function permissoesQueOFormularioOferece(): Collection
    {
        $deResources = collect(Paineis::resources())
            ->flatten(1)
            ->flatMap(static function (mixed $entidade): array {
                $permissoes = is_array($entidade) ? ($entidade['permissions'] ?? null) : null;

                if (! is_array($permissoes)) {
                    return [];
                }

                return array_values(array_filter(array_map(
                    static fn (mixed $p): ?string => is_array($p) && is_string($p['key'] ?? null)
                        ? $p['key']
                        : null,
                    $permissoes,
                )));
            });

        $doPainelCorrente = collect(array_keys(RoleResource::getPageOptions()))
            ->merge(array_keys(RoleResource::getWidgetOptions()))
            ->filter(static fn (mixed $chave): bool => is_string($chave));

        /** @var Collection<int, string> $tudo */
        $tudo = $deResources->merge($doPainelCorrente)->unique()->values();

        return $tudo;
    }

    /**
     * A regra de conjunto do salvamento: `final = (atuais − oferecidas) ∪ marcadas`.
     *
     * Método puro e estático de propósito — é a única parte desta tela que dá para provar sem
     * subir Livewire, e é a parte onde o erro custa dado. As duas direções importam e uma sem a
     * outra passa em implementação errada:
     *
     * - sem `atuais − oferecidas`, salvar apaga o que o formulário não pôde mostrar (era o
     *   defeito: papel `infra` ia de 140 permissões para 15);
     * - sem `∪ marcadas` sobre o resto, ou preservando tudo, desmarcar deixa de funcionar e a
     *   tela vira somente-adição — igualmente grave, e mais difícil de notar.
     *
     * @param  array<int, mixed>  $atuais  o que o papel tem hoje
     * @param  array<int, mixed>  $oferecidas  o que ESTE formulário conseguiu mostrar
     * @param  array<int, mixed>  $marcadas  o que voltou marcado do formulário
     * @return list<string>
     */
    public static function permissoesFinais(array $atuais, array $oferecidas, array $marcadas): array
    {
        $texto = static fn (array $lista): array => array_values(array_filter($lista, 'is_string'));

        $foraDoAlcanceDoFormulario = array_diff($texto($atuais), $texto($oferecidas));

        return array_values(array_unique([...$foraDoAlcanceDoFormulario, ...$texto($marcadas)]));
    }

    protected function afterSave(): void
    {
        $permissionModels = collect();
        $this->permissions->each(function (string $permission) use ($permissionModels): void {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name'       => $permission,
                'guard_name' => $this->data['guard_name'],
            ]));
        });

        $papel = $this->record;

        /*
         * `CreateRecord::$record` é tipado como `Model`, e `syncPermissions()` vem da
         * trait `HasPermissions`. A checagem narra o tipo para o analisador E fecha o
         * caso impossível: um `permission.models.role` que não seja um papel do spatie
         * faria esta tela gravar o papel e perder as permissões, em silêncio.
         */
        if (! $papel instanceof SpatieRole) {
            throw new LogicException(
                'permission.models.role precisa estender '.SpatieRole::class.' para esta tela gravar permissões.'
            );
        }

        /*
         * Sincroniza SÓ dentro do que o formulário ofereceu — o resto é preservado.
         *
         * ## Por que não `syncPermissions($marcadas)` puro
         *
         * `sync` é "o papel passa a ter exatamente isto". Ele só é correto se o formulário
         * tiver conseguido MOSTRAR tudo o que o papel tem — e esta tela não consegue: as abas
         * de Páginas e Widgets são montadas pelo Shield com `FilamentShield`, que é scoped ao
         * painel corrente (`/admin`). Para um papel do `/infra`, as permissões de Page e Widget
         * daquele painel simplesmente não aparecem como opção.
         *
         * Com `sync` puro, salvar sem tocar em nada apagava o que não apareceu. Medido no papel
         * `infra`: 140 permissões viravam 15.
         *
         * A regra correta é de conjunto: `final = (atuais − oferecidas) ∪ marcadas`. O que o
         * formulário mostrou e a pessoa desmarcou **sai** (senão desmarcar não funcionaria); o
         * que ele nunca teve como mostrar **fica**. Isso vale para as três abas de uma vez, e
         * continua valendo se um upgrade do Shield acrescentar uma quarta.
         *
         * As opções vêm do próprio formulário, não de uma lista escrita à mão: qualquer
         * componente de seleção que ele contenha entra na conta, então a correção não envelhece
         * quando as abas mudarem.
         */
        $oferecidas = $this->permissoesQueOFormularioOferece();

        $papel->syncPermissions(self::permissoesFinais(
            atuais: $papel->permissions->pluck('name')->values()->all(),
            oferecidas: $oferecidas->values()->all(),
            marcadas: $permissionModels->pluck('name')->values()->all(),
        ));

        Log::channel('autenticacao')->info(
            '[EditRole@afterSave] Papel gravado | papel: '.$this->data['name'].' - painel: '.($this->data['painel'] ?? 'nenhum'),
            [
                'role_id'    => $papel->getKey(),
                'papel'      => $this->data['name'],
                'painel'     => $this->data['painel'] ?? null,
                'permissoes' => $this->permissions->count(),
                'executor'   => auth()->id(),
            ],
        );

    }
}
