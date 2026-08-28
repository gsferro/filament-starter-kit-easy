<?php

declare(strict_types=1);
use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Users\UserResource;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Shield Resource
    |--------------------------------------------------------------------------
    |
    | Here you may configure the built-in role management resource. You can
    | customize the URL, choose whether to show model paths, group it under
    | a cluster, and decide which permission tabs to display.
    |
    */

    'shield_resource' => [
        'slug'            => 'shield/roles',
        /*
         * O caminho da classe NÃO aparece embaixo de cada seção de permissão.
         *
         * Com `true`, cada uma das ~20 seções da tela de papéis ganha uma linha como
         * `Wallacemartinss\FilamentOnboarding\Models\OnboardingCondition` sob o título. Numa
         * tela cujo trabalho é escolher checkbox de permissão, isso é ruído técnico: ocupa
         * altura, repete o que o título já diz e não ajuda quem está decidindo quem pode o quê.
         *
         * Medido na inspeção visual da tela (RQ-12 da wiki `tela-de-perfis`) — só o navegador
         * mostra isso, porque nenhuma asserção de conteúdo repara em linha a mais.
         */
        'show_model_path' => false,
        'cluster'         => null,
        'tabs'            => [
            'pages'              => true,
            'widgets'            => true,
            'resources'          => true,

            /*
             * Ligada porque o kit USA `custom_permissions` (bloco no fim deste arquivo).
             * Desligada, `Aceitar:Convite` e `Recusar:Convite` existem no banco e ficam
             * INVISÍVEIS na tela de papéis — ninguém concede nem revoga, sem erro nenhum.
             */
            'custom_permissions' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | When your application supports teams, Shield will automatically detect
    | and configure the tenant model during setup. This enables tenant-scoped
    | roles and permissions throughout your application.
    |
    */

    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This value contains the class name of your user model. This model will
    | be used for role assignments and must implement the HasRoles trait
    | provided by the Spatie\Permission package.
    |
    */

    'auth_provider_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Here you may define a super admin that has unrestricted access to your
    | application. You can choose to implement this via Laravel's gate system
    | or as a traditional role with all permissions explicitly assigned.
    |
    */

    'super_admin' => [
        'enabled'         => true,
        'name'            => 'master_global',
        'define_via_gate' => true,
        'intercept_gate'  => 'before',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel User
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will create a basic panel user role that can be
    | assigned to users who should have access to your Filament panels but
    | don't need any specific permissions beyond basic authentication.
    |
    */

    'panel_user' => [
        'enabled' => true,
        'name'    => 'panel_user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Builder
    |--------------------------------------------------------------------------
    |
    | You can customize how permission keys are generated to match your
    | preferred naming convention and organizational standards. Shield uses
    | these settings when creating permission names from your resources.
    |
    | Supported formats: snake, kebab, pascal, camel, upper_snake, lower_snake
    |
    | Note: The separator must not conflict with the case format's own
    | delimiter. For example, `_` cannot be used with snake/lower_snake/
    | upper_snake, and `-` cannot be used with kebab.
    |
    | When `format_custom_permission_keys` is true (default), custom
    | permissions defined below will have their keys formatted according to
    | the case setting. If your custom permissions come from external sources
    | (e.g. Terraform, Keycloak) and must remain unchanged, set this to false.
    | When using the separator in custom permission definitions, each segment
    | will be formatted independently (e.g. 'view:system_log' with pascal
    | case becomes 'View:SystemLog').
    |
    */

    'permissions' => [
        'separator'                     => ':',
        'case'                          => 'pascal',
        'generate'                      => true,
        'format_custom_permission_keys' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Shield can automatically generate Laravel policies for your resources.
    | Generated policies mirror each model's location: models under
    | app/Models map into the path below (keeping their nesting), models in
    | any other "Models" directory get a sibling "Policies" directory, and
    | vendor models fall back to the path below. When merge is enabled, the
    | methods below will be combined with any resource-specific methods you
    | define in the resources section.
    |
    */

    'policies' => [
        'path'     => app_path('Policies'),
        'merge'    => true,
        'generate' => true,
        /*
         * `import` e `export` são ACRÉSCIMO do kit aos 12 defaults do Shield.
         *
         * Eles geram `Import:{Model}` e `Export:{Model}` para todo resource, e existem
         * porque quem pode LER uma listagem não é necessariamente quem pode levar a
         * listagem inteira embora num arquivo, nem quem pode escrever em massa nela. Sem
         * permissão própria, a Action de export herdaria `ViewAny` e todo usuário de
         * painel exportaria tudo o que enxerga.
         *
         * Depois de mexer aqui, RESSEMEIE — a permission nova não existe no banco até o
         * `shield:generate` rodar de novo, e a Action simplesmente não aparece na tela,
         * sem erro nenhum:
         *
         *   php artisan db:seed --class=Database\Seeders\ShieldPermissionsSeeder
         *   php artisan db:seed --class=Database\Seeders\PapeisSeeder
         */
        'methods'  => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore',
            'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
            'import', 'export',
        ],
        /*
         * `import` e `export` entram aqui porque nenhum dos dois recebe registro: são
         * ações de coleção. Fora desta lista o Shield geraria `import(User $user, Model $record)`
         * na policy, e a Action — que chama `Gate::authorize('import')` sem registro —
         * estouraria `ArgumentCountError`.
         */
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
            'import',
            'export',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Shield supports multiple languages out of the box. When enabled, you
    | can provide translated labels for permissions to create a more
    | localized experience for your international users.
    |
    */

    'localization' => [
        'enabled' => false,
        'key'     => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Here you can fine-tune permissions for specific Filament resources.
    | Use the 'manage' array to override the default policy methods for
    | individual resources, giving you granular control over permissions.
    |
    */

    /*
     * `manage` é onde nasce a permissão de ACTION de Resource.
     *
     * Com `policies.merge => true` (acima), a lista aqui é SOMADA às 14 chaves default daquele
     * Resource — `HasEntityTransformers::getDefaultPolicyMethodsOrFor()` faz `array_merge()`
     * (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:179-181`). Então
     * `'reenviar'` no `ConviteResource` do /admin gera `Reenviar:Convite`, e mais nada muda.
     *
     * ## Por que aqui, e não em `custom_permissions`
     *
     * Porque Resource pertence a um PAINEL, e a permissão nasce escopada de graça:
     * `Paineis::permissoes('admin')` a inclui e `Paineis::permissoes('app')` não. O `PapeisSeeder`
     * colhe a matriz do painel, então a permissão chega ao papel `admin` **sem nenhuma mudança de
     * seeder**. `custom_permissions` não tem noção de painel nenhuma — ver o bloco lá embaixo.
     *
     * Regra de escolha, em uma frase: **Action de Resource vem para cá; Action de Page vai para
     * `custom_permissions` + o mapa de painel do `PapeisSeeder`.**
     *
     * O nome do método entra em camelCase e sai em pascal com o separador (`atribuirPapeis` →
     * `AtribuirPapeis:Tenant`). NENHUM dos três abaixo entra em
     * `policies.single_parameter_methods`: os três agem sobre registro.
     *
     * **Ressemeie depois de mexer aqui**, ou a Action fica oculta para todo mundo sem erro nenhum:
     *
     *   php artisan db:seed --class=Database\Seeders\ShieldPermissionsSeeder
     *   php artisan db:seed --class=Database\Seeders\PapeisSeeder
     */
    'resources' => [
        'subject' => 'model',
        'manage'  => [
            /*
             * Esta entrada é do RoleResource do VENDOR, e o painel usa o publicado do kit
             * (`App\Filament\Admin\Resources\Roles\RoleResource`) — logo ela nunca casa. Mesmo que
             * casasse, `policies.merge => true` faz `array_merge` e as 14 voltariam. Mantida como
             * estava: mexer nela é assunto da tela de papéis, não desta lista.
             */
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],

            // Reenviar dispara e-mail e INVALIDA o token anterior — não é "editar convite".
            ConviteResource::class => [
                'reenviar',
            ],

            /*
             * As três do `UsersRelationManager`. Duas delas são Action NATIVA, e é o ponto:
             * `AttachAction`/`DetachAction` em RelationManager só checam `isReadOnly()`, sem
             * policy — o vendor diz isso em comentário em
             * `vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:348-353`,
             * e o arm do `match` em `:359` confirma. A linha do pivot `tenant_user` que elas criam é
             * exatamente o que `User::canAccessTenant()` consulta para liberar `/app/{slug}`.
             *
             * `atribuirPapeis` grava papéis via `syncRoles()` — é onde nasce o primeiro
             * `admin_app` de uma organização.
             */
            TenantResource::class => [
                'vincularUsuario',
                'desvincularUsuario',
                'atribuirPapeis',
            ],

            /*
             * Desativar e reativar um usuário — as ações de `SituacaoDaConta`, só no /admin.
             * Duas permissões e não uma: reativar é conceder acesso, desativar é retirar. O
             * `panel_user` já perde as duas pela subtração por FQCN do `UserResource` do /app.
             */
            UserResource::class => [
                'desativar',
                'reativar',
            ],
        ],
        'exclude' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Most Filament pages only require view permissions. Pages listed in the
    | exclude array will be skipped during permission generation and won't
    | appear in your role management interface.
    |
    */

    'pages' => [
        'subject' => 'class',
        'prefix'  => 'view',
        'exclude' => [
            Dashboard::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | Like pages, widgets typically only need view permissions. Add widgets
    | to the exclude array if you don't want them to appear in your role
    | management interface.
    |
    */

    'widgets' => [
        'subject' => 'class',
        'prefix'  => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Permissions
    |--------------------------------------------------------------------------
    |
    | Sometimes you need permissions that don't map to resources, pages, or
    | widgets. Define any custom permissions here and they'll be available
    | when editing roles in your application.
    |
    | Keys are formatted per the Permission Builder settings above; set
    | permissions.format_custom_permission_keys to false to use them as-is.
    |
    */

    /*
     * Aqui ficam as permissões de Action de PAGE — as que não pendem de nenhum Resource.
     *
     * `aceitar` e `recusar` são as duas Actions de `App\Filament\App\Pages\ConvitesRecebidos`. Elas
     * NÃO podem nascer no `ConviteResource` do painel `app`, que seria o caminho natural: a lista
     * `PapeisSeeder::permissoesDeAdministracaoDoApp()` subtrai do `panel_user` **todas** as
     * permissões daquele Resource, em bloco, por FQCN — e é justamente o usuário comum que aceita o
     * convite dele. A subtração é assim de propósito (o docblock daquele método explica por quê), e
     * afiná-la reintroduziria casamento por nome numa subtração.
     *
     * As chaves saem em minúsculo e o Shield formata cada segmento com `permissions.case`
     * (`HasEntityTransformers::formatCustomPermissionKey()`, `:155-164`), gerando `Aceitar:Convite`
     * e `Recusar:Convite`.
     *
     * ## ARMADILHA: custom permission não conhece painel
     *
     * `transformCustomPermissions()` lê esta lista sem consultar painel nenhum
     * (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:88-112`), e
     * `getEntitiesPermissions()` faz merge das chaves na matriz de **todo** painel
     * (`FilamentShield.php:119`). Sem recorte, chave nova aqui cai em `admin`, `infra`, `admin_app`
     * **e `panel_user`** — o over-grant silencioso que `.ai/rules/filament.md` §4 chama de a falha
     * mais cara desta parte do kit.
     *
     * Por isso toda chave desta lista PRECISA de entrada em
     * `PapeisSeeder::paineisDasPermissoesCustomizadas()`. Sem entrada ela não vai para papel nenhum
     * (fail-closed) e o caso CT-19 de `tests/Kit/PermissoesDeAcoesTest.php` fica vermelho nomeando
     * a chave.
     *
     * Ver ADR-02 e ADR-03 de
     * `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`.
     */
    'custom_permissions' => [
        'aceitar:convite' => 'Aceitar convite recebido',
        'recusar:convite' => 'Recusar convite recebido',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Discovery
    |--------------------------------------------------------------------------
    |
    | By default, Shield only looks for entities in your default Filament
    | panel. Enable these options if you're using multiple panels and want
    | Shield to discover entities across all of them.
    |
    */

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets'   => false,
        'discover_all_pages'     => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Policy
    |--------------------------------------------------------------------------
    |
    | Shield can automatically register a policy for role management itself.
    | This lets you control who can manage roles using Laravel's built-in
    | authorization system. Requires a RolePolicy class in your app.
    |
    */

    'register_role_policy' => true,

];
