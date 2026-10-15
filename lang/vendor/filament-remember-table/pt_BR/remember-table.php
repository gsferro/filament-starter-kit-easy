<?php

/*
 * Traduções do sehsah/filament-remember-table (visões salvas de tabela).
 *
 * O pacote só traz `en` e `ar` em `resources/lang/` — a página dele no diretório de plugins
 * lista pt-BR, e não confere. Todas as chaves do `en` do pacote estão aqui, e o caso de teste
 * da feature (wiki `estudo-de-pacotes-rodada-3`) reprova quando o pacote ganhar uma chave nova
 * sem a tradução correspondente.
 */

return [

    'button' => [
        'label'    => 'Visões',
        'modified' => ':name (modificada)',
    ],

    'actions' => [
        'save'          => 'Salvar como nova visão',
        'save_submit'   => 'Salvar visão',
        'update'        => 'Atualizar visão atual',
        'manage'        => 'Gerenciar visões',
        'manage_submit' => 'Salvar alterações',
        'reset'         => 'Limpar tabela',
    ],

    'modals' => [
        'save' => [
            'heading'     => 'Salvar visão',
            'description' => 'Guarda os filtros, a busca, a ordenação, o agrupamento e as colunas atuais.',
        ],
        'update' => [
            'heading'     => 'Atualizar ":name"?',
            'description' => 'A visão será substituída pela configuração atual da tabela.',
        ],
        'manage' => [
            'heading' => 'Gerenciar visões',
        ],
    ],

    'fields' => [
        'name'           => 'Nome',
        'is_default'     => 'Visão padrão',
        'is_public'      => 'Compartilhar com todos',
        'is_public_help' => 'Outros usuários podem aplicar esta visão, mas só você pode alterá-la.',
    ],

    'badges' => [
        'default' => 'Padrão',
        'shared'  => 'Compartilhada',
        'preset'  => 'Pré-definida',
    ],

    'notifications' => [
        'saved'         => 'Visão ":name" salva',
        'updated'       => 'Visão ":name" atualizada',
        'applied'       => 'Mostrando ":name"',
        'managed'       => 'Visões atualizadas',
        'limit_reached' => 'Você pode salvar até :limit visões para esta tabela.',
    ],

];
