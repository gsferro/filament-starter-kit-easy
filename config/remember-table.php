<?php

use App\Support\BooleanoDoEnv;
use Sehsah\FilamentRememberTable\Models\TableView;

/*
 * Config publicada do sehsah/filament-remember-table (visões salvas de tabela).
 *
 * Publicada por UM motivo: `allow_public_views` nasce `true` no pacote, e o kit quer o
 * compartilhamento DESLIGADO por padrão (wiki `estudo-de-pacotes-rodada-3`, P-02). O `.env`
 * semeia a chave; em tempo de execução o banco vence — `ConfiguracoesDoKit::mapaDeConfiguracao()`
 * aponta `visoes_de_tabela_compartilhadas` para `remember-table.allow_public_views`, e
 * `aplicarNaConfig()` sobrepõe este valor no boot. O resto fica no default do pacote.
 *
 * `BooleanoDoEnv` e não `(bool) env()`: o default aqui é `false`, então o defeito do `env()` com
 * valor vazio seria inócuo — mas o padrão do kit para toda chave booleana do `.env` é o mesmo
 * (`config/kit.php`, bloco das tabelas), e não vale a pena ter duas formas.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Database table
    |--------------------------------------------------------------------------
    |
    | The table that stores saved views, and the model used to read it.
    | You may extend the model and point this setting to your own class.
    |
    */

    'table_name' => 'remember_table_views',

    'model' => TableView::class,

    /*
    |--------------------------------------------------------------------------
    | What a view remembers
    |--------------------------------------------------------------------------
    |
    | Turn off any part of the table state you don't want stored in views.
    |
    */

    'remember' => [
        'filters'         => true,
        'search'          => true,
        'column_searches' => true,
        'sort'            => true,
        'grouping'        => true,
        'columns'         => true,
        'per_page'        => false,
        'tab'             => true, // resource list page tabs (getTabs())
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    // Let users share a view with every user of the panel.
    'allow_public_views' => BooleanoDoEnv::comPadrao(env('KIT_TABELA_VISOES_COMPARTILHADAS'), false),

    // Apply the user's default view when they open the table without
    // any table state in the URL.
    'apply_default_view_on_load' => true,

    // Scope views to the current Filament tenant when tenancy is enabled.
    'scope_to_tenant' => true,

    // Maximum number of views one user can save per table (null = no limit).
    'max_views_per_table' => 50,

];
