<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Assistente de IA (Prism)
    |--------------------------------------------------------------------------
    | Provedor e modelo usados pelo assistente dos painéis.
    | Provedores suportados pelo Prism: openai, anthropic, gemini, ollama, etc.
    | Não esqueça de definir a API key do provedor (ex.: OPENAI_API_KEY,
    | ANTHROPIC_API_KEY) no .env — veja config/prism.php após publicar.
    */

    'ai' => [
        'provider' => env('PRISM_AI_PROVIDER', 'openai'),
        'model' => env('PRISM_AI_MODEL', 'gpt-4o-mini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy (opcional — desligado por padrão)
    |--------------------------------------------------------------------------
    | Quando habilitado, o painel APP passa a operar por tenant (equipe):
    | registro/troca de equipe, perfil da equipe e escopo automático dos
    | recursos pelo relacionamento de posse ("team").
    |
    | A maioria dos projetos não usa: basta manter KIT_TENANCY=false.
    | Para usar: KIT_TENANCY=true no .env e adicione team_id (ou o pivot)
    | aos modelos de negócio. Detalhes no README, seção "Multi-tenancy".
    */

    'tenancy' => [
        'enabled' => env('KIT_TENANCY', false),
        'model' => \App\Models\Team::class,
        'slug_attribute' => 'slug',
        'ownership_relationship' => 'team',
    ],

];
