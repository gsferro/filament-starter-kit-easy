<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

    // Inferência LOCAL por padrão (llama.cpp via `docker compose --profile ai up -d`).
    // Troque por 'openai', 'anthropic' etc. definindo AI_PROVIDER + a API key no .env.
    'default'                   => env('AI_PROVIDER', 'llamacpp'),
    'default_for_images'        => 'gemini',
    'default_for_audio'         => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings'    => env('AI_EMBEDDINGS_PROVIDER', 'llamacpp-embed'),
    'default_for_reranking'     => 'cohere',

    /*
    |--------------------------------------------------------------------------
    | Timeout dos agentes
    |--------------------------------------------------------------------------
    | O default de 60s do SDK não serve para inferência local em CPU: um turno
    | com tools pode levar bem mais. Lido pelo AgenteBase::timeout().
    */

    'agente_timeout' => (int) env('AI_AGENT_TIMEOUT', 180),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. Each
    | represents an AI provider and API key combination which can be used
    | to perform tasks like text, image, and audio creation via agents.
    |
    */

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key'    => env('ANTHROPIC_API_KEY'),
            'url'    => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],

        'azure' => [
            'driver'               => 'azure',
            'key'                  => env('AZURE_OPENAI_API_KEY'),
            'url'                  => env('AZURE_OPENAI_URL'),
            'api_version'          => env('AZURE_OPENAI_API_VERSION', '2025-04-01-preview'),
            'deployment'           => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
            'embedding_deployment' => env('AZURE_OPENAI_EMBEDDING_DEPLOYMENT', 'text-embedding-3-small'),
            'image_deployment'     => env('AZURE_OPENAI_IMAGE_DEPLOYMENT', 'gpt-image-1'),
            'store'                => env('AZURE_OPENAI_STORE', true),
        ],

        'bedrock' => [
            'driver'                          => 'bedrock',
            'region'                          => env('AWS_BEDROCK_REGION', 'us-east-1'),
            'key'                             => env('AWS_BEARER_TOKEN_BEDROCK'),
            'access_key_id'                   => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key'               => env('AWS_SECRET_ACCESS_KEY'),
            'session_token'                   => env('AWS_SESSION_TOKEN'),
            'use_default_credential_provider' => env('AWS_USE_DEFAULT_CREDENTIALS', true),
            'assume_role'                     => [
                'arn'              => env('AWS_BEDROCK_ASSUME_ROLE_ARN'),
                'session_name'     => env('AWS_BEDROCK_ASSUME_ROLE_SESSION_NAME'),
                'duration_seconds' => env('AWS_BEDROCK_ASSUME_ROLE_DURATION_SECONDS'),
                'external_id'      => env('AWS_BEDROCK_ASSUME_ROLE_EXTERNAL_ID'),
            ],
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key'    => env('COHERE_API_KEY'),
        ],

        'deepseek' => [
            'driver' => 'deepseek',
            'key'    => env('DEEPSEEK_API_KEY'),
        ],

        'eleven' => [
            'driver' => 'eleven',
            'key'    => env('ELEVENLABS_API_KEY'),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key'    => env('GEMINI_API_KEY'),
            'url'    => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
        ],

        'groq' => [
            'driver' => 'groq',
            'key'    => env('GROQ_API_KEY'),
        ],

        'jina' => [
            'driver' => 'jina',
            'key'    => env('JINA_API_KEY'),
        ],

        'mistral' => [
            'driver' => 'mistral',
            'key'    => env('MISTRAL_API_KEY'),
        ],

        // llama.cpp (docker profile `ai`) — servidor OpenAI-compatible local.
        'llamacpp' => [
            'driver' => 'openai-compatible',
            'url'    => env('LLAMACPP_URL', 'http://localhost:8080/v1'),
            'key'    => env('LLAMACPP_API_KEY', 'none'),
            'model'  => env('LLAMACPP_MODEL', 'qwen2.5-7b-instruct'),
            'local'  => true,
        ],

        // Embeddings locais (extensão RAG). Driver `openai` de propósito: no
        // laravel/ai 0.10 só ele implementa EmbeddingProvider.
        'llamacpp-embed' => [
            'driver' => 'openai',
            'url'    => env('LLAMACPP_EMBED_URL', 'http://localhost:8081/v1'),
            'key'    => env('LLAMACPP_EMBED_API_KEY', 'none'),
            'model'  => env('LLAMACPP_EMBED_MODEL', 'qwen3-embedding-0.6b'),
            'local'  => true,
        ],

        'ollama' => [
            'driver' => 'ollama',
            'key'    => env('OLLAMA_API_KEY', ''),
            'url'    => env('OLLAMA_URL', 'http://localhost:11434'),
            'local'  => true,
        ],

        'openai' => [
            'driver' => 'openai',
            'key'    => env('OPENAI_API_KEY'),
            'url'    => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'store'  => env('OPENAI_STORE', true),
        ],

        'openai-compatible' => [
            'driver' => 'openai-compatible',
            'url'    => env('OPENAI_COMPATIBLE_URL'),
            'key'    => env('OPENAI_COMPATIBLE_API_KEY'),
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'key'    => env('OPENROUTER_API_KEY'),
        ],

        'voyageai' => [
            'driver' => 'voyageai',
            'key'    => env('VOYAGEAI_API_KEY'),
        ],

        'xai' => [
            'driver' => 'xai',
            'key'    => env('XAI_API_KEY'),
        ],
    ],

];
