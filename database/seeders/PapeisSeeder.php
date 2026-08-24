<?php

namespace Database\Seeders;

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\App\Resources\Users\UserResource;
use App\Support\Paineis;
use BezhanSalleh\FilamentExceptions\Resources\ExceptionResource;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Papéis do kit — a fronteira de acesso aos painéis (User::canAccessPanel).
 *
 * Cada papel declara em QUAL PAINEL vale, na coluna `roles.painel`:
 *
 *   master_global      → painel nulo; entra em tudo pelo Gate::before, não pela coluna
 *   admin              → /admin: usuários, papéis, agentes de IA
 *   infra              → /infra: health, filas, logs, auditoria, comandos
 *   admin_app  → /app: administra a PRÓPRIA organização (só com tenancy)
 *   panel_user         → /app: o perfil básico da operação de negócio
 *
 * A matriz de permissões de cada papel é EXATAMENTE a do painel dele, colhida por
 * `App\Support\Paineis` na mesma fonte que o `shield:generate` usa. Antes isso era
 * casamento por substring (`str_contains($p, 'User')`), que colocava um Resource futuro
 * chamado `UserPreference` no papel `admin` sem ninguém decidir.
 *
 * Idempotente: pode rodar de novo depois de criar Resources novos — e DEVE, junto com o
 * ShieldPermissionsSeeder. Ver `.ai/rules/filament.md`.
 *
 * ## Com multi-tenancy ligada (`permission.teams`)
 *
 * Os papéis continuam sendo criados SEM team (`roles.team_id` nulo) — no spatie isso
 * significa "papel global, disponível em qualquer tenant". O que passa a ser por tenant
 * é a ATRIBUIÇÃO: `$user->assignRole('admin')` grava em `model_has_roles` o team
 * corrente, fixado a cada request pelo middleware `DefinirTenantDePermissoes`.
 *
 * Efeito prático: o mesmo usuário pode ser `admin` num tenant e usuário comum em outro,
 * sem duplicar a definição do papel.
 */
class PapeisSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // master_global fica sem permissions de propósito: o acesso vem do Gate::before
        // (KitServiceProvider). Sincronizar tudo aqui só criaria uma lista que apodrece a
        // cada Resource novo. O painel é nulo pelo mesmo motivo — ele não entra pela
        // coluna, e nulo NÃO é coringa (ADR-03 da wiki perfil-e-acesso-ao-painel).
        $this->papel(config('filament-shield.super_admin.name', 'master_global'), $guard, null)
            ->syncPermissions([]);

        $this->papel('admin', $guard, 'admin')
            ->syncPermissions($this->permissoesDoPainel('admin', $guard));

        $this->papel('infra', $guard, 'infra')
            ->syncPermissions($this->permissoesDoPainel('infra', $guard));

        // admin_app só existe no modo multi-tenant: sem organização não há o que
        // administrar dentro do /app, e um papel com permissão de criar usuário sem
        // recorte de organização seria um segundo `admin` com outro nome. Ver ADR-09 da
        // wiki admin-da-organizacao.
        //
        // A matriz é a do painel inteira MENOS o que não é tela do /app: quem administra a
        // organização administra tudo que o /app oferece, e o recorte dele é de DADO (só a
        // organização corrente), feito no `getEloquentQuery()` dos Resources.
        //
        // A subtração de `permissoesForaDoApp()` é o que impede a matriz "inteira" de
        // incluir Resource de vendor que só está no painel por obrigação técnica. Ver o
        // docblock daquele método, e QA-01 do 06-relatorio-qa.md da wiki
        // admin-da-organizacao.
        $foraDoApp = $this->permissoesForaDoApp();

        if (config('kit.tenancy.enabled')) {
            $this->papel('admin_app', $guard, 'app')
                ->syncPermissions(
                    $this->permissoesDoPainel('app', $guard)
                        ->reject(fn (string $permissao): bool => in_array($permissao, $foraDoApp, true))
                );
        }

        // panel_user é o perfil básico do /app: usa o NEGÓCIO, não administra a
        // organização. Por isso ele recebe a matriz do painel MENOS as permissões das
        // entidades de administração (usuários e convites) — sem a subtração, registrar
        // esses Resources no painel `app` promoveria todo usuário comum a administrador
        // da organização, sem migration e sem erro nenhum. Ver ADR-06.
        //
        // A subtração roda nos DOIS modos: os Resources existem no painel mesmo com
        // `canAccess()` falso em single-tenant, então o Shield gera as permissões deles
        // de qualquer forma.
        //
        // No seu projeto, este seeder é o lugar da matriz de autorização: recorte mais o
        // que o usuário comum pode fazer, ou crie papéis mais finos.
        $administracao = $this->permissoesDeAdministracaoDoApp();

        $this->papel(config('filament-shield.panel_user.name', 'panel_user'), $guard, 'app')
            ->syncPermissions(
                $this->permissoesDoPainel('app', $guard)
                    ->reject(fn (string $permissao): bool => in_array($permissao, $administracao, true))
                    ->reject(fn (string $permissao): bool => in_array($permissao, $foraDoApp, true))
                    ->reject(fn (string $permissao): bool => $this->ehPermissaoDeImportOuExport($permissao))
            );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * `Import:{Model}` e `Export:{Model}` — subtraídas do `panel_user` por default.
     *
     * As duas não são "ver a tela em massa": **import é escrita em massa** e **export tira
     * o dado da aplicação** num arquivo que segue por e-mail. O usuário comum do /app usa
     * o negócio um registro por vez; quem move planilha é quem opera a organização.
     *
     * Fica com o `admin_app`, que recebe a matriz do painel inteira, e pode ser concedida
     * ao `panel_user` na tela de Papéis por quem decidir que faz sentido — que é
     * exatamente o cenário separado que o requisito pediu ("pode ter cenarios diferentes
     * caso envolve quem pode exportar e quem pode importar").
     *
     * **Prefixo, e não FQCN, e a diferença importa.** A subtração de administração acima é
     * por FQCN de propósito, porque `str_contains($p, 'User')` pegaria um
     * `UserPreferenceResource` futuro por acidente. Aqui o casamento é no segmento de AÇÃO
     * do nome, que o Shield monta deterministicamente de `policies.methods` +
     * `permissions.separator` — `Import:` só aparece em permissão de import, para qualquer
     * model presente ou futuro. É o comportamento desejado: resource novo nasce com as
     * duas fora do usuário comum, sem ninguém precisar lembrar de acrescentá-lo a lista
     * nenhuma.
     */
    private function ehPermissaoDeImportOuExport(string $permissao): bool
    {
        $separador = (string) config('filament-shield.permissions.separator', ':');

        return str_starts_with($permissao, 'Import'.$separador)
            || str_starts_with($permissao, 'Export'.$separador);
    }

    /**
     * Permissões das entidades de ADMINISTRAÇÃO do painel `app`.
     *
     * Resource, Page OU Widget: as três entram na matriz do painel por
     * `FilamentShield::getEntitiesPermissions()`, então as três precisam poder ser
     * subtraídas. Até a 0.11.0 esta lista varria só Resources, e uma Page de administração
     * registrada no `app` era herdada pelo `panel_user` sem que nada pudesse removê-la. Ver
     * ADR-06 da wiki convite-em-massa.
     *
     * Recortadas por FQCN, nunca por substring do nome da permission: o casamento por
     * `str_contains($p, 'User')` foi removido daqui justamente porque um
     * `UserPreferenceResource` futuro cairia nele por acidente. Numa SUBTRAÇÃO o erro seria
     * o espelhado — tirar permissão de quem deveria tê-la.
     *
     * Entidade de administração nova no painel `app` precisa entrar nesta lista, senão o
     * `panel_user` a herda.
     *
     * @return list<string>
     */
    private function permissoesDeAdministracaoDoApp(): array
    {
        // `array_values()` no fim: `Paineis::permissoesDe()` devolve `Collection<int, string>`
        // e o `all()` dela é `array<int, string>` para o analisador — o contrato aqui é lista.
        return array_values(Paineis::permissoesDe('app', [
            UserResource::class,
            ConviteResource::class,
        ])->all());
    }

    /**
     * O que existe na matriz do painel `app` e **não é tela do /app**.
     *
     * Lista diferente da de cima porque o MOTIVO é diferente, e o motivo decide de QUEM se
     * subtrai. `UserResource` e `ConviteResource` são telas legítimas do /app: quem
     * administra a organização deve tê-las, e só o `panel_user` as perde. O que entra aqui
     * ninguém do painel deve ter — nem `admin_app`, nem `panel_user`.
     *
     * Hoje há um caso: o `ExceptionResource`. Ele está na matriz deste painel só porque o
     * `FilamentExceptionsPlugin` precisa estar registrado nos TRÊS painéis para o pacote não
     * estourar `LogicException` no boot (ver o comentário no `AppPanelProvider`). Registrado
     * sem navegação, mas registrado — e `registerNavigation(false)` mexe apenas em
     * `shouldRegisterNavigation()`, nunca em `canAccess()`.
     *
     * As rotas existem no painel (`route:list --path=app` devolve
     * `app/{tenant:slug}/exceptions` e `.../{record}`), então a permission basta para
     * alcançar a tela. Até a 0.18.2 a subtração pegava só o `panel_user` e o `admin_app`
     * recebia as 14 permissions de `Exception`, `DeleteAny` inclusive.
     *
     * O estrago era menor do que parece, e vale registrar para ninguém "melhorar" a
     * correção pelo motivo errado: com a tenancy ligada a tela não chega a renderizar.
     * O global scope de tenancy chama `getTenantOwnershipRelationship()`, que lança
     * `LogicException` em
     * `vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:98` —
     * o model `Exception` do vendor não tem relação `tenant`. Logo era 500, não vazamento de
     * stack trace. Mas a permission existir já é defeito: é `DeleteAny` num papel de
     * cliente, e a rota responde no painel dele.
     *
     * Resource de vendor registrado por obrigação técnica em painel onde não é tela entra
     * aqui, não na lista de administração.
     *
     * @return list<string>
     */
    private function permissoesForaDoApp(): array
    {
        return array_values(Paineis::permissoesDe('app', [
            ExceptionResource::class,
        ])->all());
    }

    /**
     * Painel a que cada permissão de `config('filament-shield.custom_permissions')` pertence.
     *
     * Existe porque o Shield **não** escopa custom permission por painel: o
     * `transformCustomPermissions()` lê a config sem consultar painel algum
     * (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:88-112`) e o
     * `getEntitiesPermissions()` faz merge das chaves na matriz de TODOS os painéis
     * (`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:119`). Sem este mapa, uma chave
     * custom nova cai em `admin`, `infra`, `admin_app` **e `panel_user`** — o over-grant silencioso
     * que `.ai/rules/filament.md` §4 chama de a falha mais cara desta parte do kit.
     *
     * Hoje as duas chaves são as Actions de `App\Filament\App\Pages\ConvitesRecebidos`, e elas ficam
     * no painel `app`: quem aceita ou recusa o convite é quem opera o negócio.
     *
     * **Fail-closed de propósito**: chave sem entrada aqui não vai para papel nenhum. Quem
     * acrescenta uma custom permission e esquece o mapa vê o botão sumir para todos — e é a falha
     * segura, não a que promove usuário comum a administrador. O caso CT-19 de
     * `tests/Kit/PermissoesDeAcoesTest.php` fica vermelho nomeando a chave faltante.
     *
     * Action de RESOURCE não passa por aqui: ela nasce em `config('filament-shield.resources.manage')`,
     * que é escopado por painel de graça. Ver ADR-02 e ADR-03 de
     * `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`.
     *
     * @return array<string, list<string>>
     */
    private function paineisDasPermissoesCustomizadas(): array
    {
        return [
            'Aceitar:Convite' => ['app'],
            'Recusar:Convite' => ['app'],
        ];
    }

    /**
     * As permissões do painel que EXISTEM no banco.
     *
     * A interseção não é preciosismo: `syncPermissions()` recebendo um nome que não está
     * na tabela lança `PermissionDoesNotExist` e derruba o seeder inteiro. Isso acontece
     * sempre que este seeder roda sem o `ShieldPermissionsSeeder` antes — cenário comum
     * em teste, que semeia só o que o caso precisa.
     *
     * Tolerar o banco incompleto também é o comportamento antigo: antes a lista vinha de
     * `Permission::pluck('name')` e um banco sem permissions simplesmente dava papel
     * vazio, em vez de erro.
     *
     * O `reject` final é o recorte de painel das custom permissions — ver
     * `paineisDasPermissoesCustomizadas()`. Ele fica AQUI, e não nas quatro chamadas de
     * `syncPermissions()`, porque este é o ponto único por onde toda permissão passa antes de chegar
     * a um papel: um recorte por papel seria quatro cópias, e a próxima copiaria mal.
     *
     * @return Collection<int, string>
     */
    private function permissoesDoPainel(string $painel, string $guard): Collection
    {
        $declarado = $this->paineisDasPermissoesCustomizadas();

        /*
         * O mapa é montado sobre as chaves que o Shield REALMENTE gera a partir da config, e não
         * sobre as chaves declaradas acima. É o que torna o recorte fail-closed: custom permission
         * nova sem entrada no mapa cai aqui com lista de painéis VAZIA e é rejeitada em todos os
         * painéis. Se o mapa fosse a única fonte, a chave sem entrada escaparia do `reject` e iria
         * para os quatro papéis — fail-open, exatamente o defeito que este método fecha.
         */
        $paineisPorCustom = collect(array_keys((array) FilamentShield::getCustomPermissions()))
            ->mapWithKeys(fn (string $chave): array => [$chave => $declarado[$chave] ?? []])
            ->all();

        return Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', Paineis::permissoes($painel))
            ->pluck('name')
            ->reject(fn (string $permissao): bool => array_key_exists($permissao, $paineisPorCustom)
                && ! in_array($painel, $paineisPorCustom[$permissao], true))
            ->values();
    }

    /**
     * Papel com DEFINIÇÃO global (`roles.team_id` nulo) e painel declarado.
     *
     * Com `permission.teams` ligado, o `Role::findOrCreate` do spatie carimba o team
     * corrente na definição do papel, e um papel carimbado no tenant A fica invisível no
     * tenant B — não haveria como atribuir `admin` em dois tenants sem duplicar a
     * definição.
     *
     * `roles.team_id` é nullable justamente para isso: nulo = papel disponível em
     * qualquer contexto. O que varia por tenant é a ATRIBUIÇÃO
     * (`model_has_roles.team_id`, essa sim NOT NULL).
     *
     * `updateOrCreate` e não `firstOrCreate`: papel que já existe precisa receber o
     * painel, senão quem atualiza o kit fica com papéis sem painel — ou seja, sem acesso.
     */
    private function papel(string $nome, string $guard, ?string $painel): Role
    {
        $chave = ['name' => $nome, 'guard_name' => $guard];

        if (config('permission.teams')) {
            $chave[config('permission.column_names.team_foreign_key', 'team_id')] = null;
        }

        return Role::query()->updateOrCreate($chave, ['painel' => $painel]);
    }
}
