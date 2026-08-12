<?php

/*
 * O pacote não traz pt-BR. Sem este arquivo a busca ⌘K fica em inglês no meio
 * de um painel inteiro em português — inclusive o placeholder do campo, que é
 * a primeira coisa que se lê na topbar.
 */

return [
    'placeholder' => 'Buscar...',

    'empty_state' => [
        'prompt'     => 'Digite para buscar.',
        'no_results' => 'Nenhum resultado para ":query".',
    ],

    'keys' => [
        'escape' => 'esc',
    ],

    'groups' => [
        'records'   => 'Registros',
        'resources' => 'Telas',
        'pages'     => 'Páginas',
        'actions'   => 'Ações',
        'recent'    => 'Recentes',
        'pinned'    => 'Fixados',
    ],

    'actions' => [
        'create' => [
            'group' => 'Criar',
            'label' => 'Criar :label',
        ],
    ],
];
