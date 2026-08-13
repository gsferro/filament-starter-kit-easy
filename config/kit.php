<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versão do kit que originou este projeto
    |--------------------------------------------------------------------------
    | Marca de nascença: o `kit:update` usa isto para saber a partir de qual
    | versão comparar e mostrar só o que o KIT mudou — sem confundir com o que
    | você mudou no seu projeto.
    |
    | O `kit:update` grava este número sozinho ao final de cada atualização —
    | você não precisa editar à mão. Sem a chave, ele cai na comparação direta
    | contra a árvore de trabalho, que é mais ruidosa.
    */

    'version' => '0.10.0',

    /*
    |--------------------------------------------------------------------------
    | Repositório do kit
    |--------------------------------------------------------------------------
    | Origem consultada pelo `php artisan kit:update`. O vínculo é temporário:
    | o comando adiciona o remote, compara, aplica o que você aprovar e desfaz
    | tudo ao final — o projeto não fica com remote nem tags de terceiros.
    */

    'repository' => env('KIT_REPOSITORY', 'https://github.com/gsferro/filament-starter-kit-easy.git'),

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy
    |--------------------------------------------------------------------------
    | Desligado por default: o kit nasce single-tenant. Ligue com
    | `php artisan kit:tenancy` — ele escreve `enabled`, liga os papéis por
    | tenant (`permission.teams`) e recria o banco.
    |
    | Com o modo ligado, o painel /app passa a ser /app/{tenant} e o usuário só
    | enxerga os tenants aos quais está vinculado. /admin e /infra seguem globais.
    |
    | Não ligue `enabled` à mão num projeto já migrado: as tabelas de permissão
    | só ganham a coluna de tenant quando `permission.teams` está ativo ANTES do
    | migrate. Use o comando.
    |
    | ## Código em inglês, interface em português
    |
    | O CÓDIGO usa o vocabulário da API do Filament — model `Tenant`, tabela
    | `tenants`, coluna `tenant_id`, métodos `getTenants()`/`canAccessTenant()`.
    | Assim a documentação oficial do Filament se lê sem tradução mental.
    |
    | O que o USUÁRIO vê é o que estiver aqui embaixo. O default é "Organização",
    | mas cada projeto troca para o termo do seu negócio — Empresa, Cliente,
    | Escola, Unidade, Loja — sem tocar numa linha de código.
    */

    'tenancy' => [

        'enabled' => (bool) env('KIT_TENANCY', false),

        // Rótulos exibidos na interface (menu, títulos, formulários, mensagens).
        'label'        => env('KIT_TENANCY_LABEL', 'Organização'),
        'label_plural' => env('KIT_TENANCY_LABEL_PLURAL', 'Organizações'),

        // Segmento do cadastro no painel admin: /admin/organizacoes.
        // Só a URL do CRUD — o endereço do painel de negócio é /app/{slug do
        // próprio registro}, definido em cada tenant.
        'slug' => env('KIT_TENANCY_SLUG', 'organizacoes'),

    ],

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
