<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuário inicial
    |--------------------------------------------------------------------------
    | Criado pelo UsuarioAdminSeeder com o papel master_global. Troque a senha
    | antes de expor o ambiente — ou defina as variáveis abaixo no .env antes
    | de rodar o `kit:install`.
    */

    'admin' => [
        'name'     => env('KIT_ADMIN_NAME', 'Administrador'),
        'email'    => env('KIT_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('KIT_ADMIN_PASSWORD', 'password'),
    ],

];
