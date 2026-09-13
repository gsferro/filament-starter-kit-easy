<?php

/*
|--------------------------------------------------------------------------
| Dynamic Dashboard (mddev31/filament-dynamic-dashboard)
|--------------------------------------------------------------------------
|
| Config do pacote, publicada porque o kit liga `use_spatie_permissions`:
| é o eixo de VISIBILIDADE por papel — `canDisplay()` filtra por roles e o
| "Manage" ganha o seletor de roles. Quem pode MONTAR continua sendo o
| `canEdit()` da página, que delega à permission `Manage:Dashboard` do Shield.
| Dashboard sem roles fica visível a todos. Ver ADR-05 de
| `wikis/specs/main/dashboard-dinamico-nos-paineis/`.
|
| O interruptor da feature NÃO mora aqui: é `kit.dashboard_dinamico.*`, lido
| por `App\Support\DashboardDinamico` por request.
|
*/

return [
    'template_paths' => [
        resource_path('dashboard-templates'),
    ],
    'default_template'   => 'flat-12',
    'disabled_templates' => [
        // e.g. 'flat-24-dense', 'kpi-strip-chart',
    ],
    'use_spatie_permissions' => true,
    'default_personal'       => false,
];
