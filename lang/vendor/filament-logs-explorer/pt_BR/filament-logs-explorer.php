<?php

declare(strict_types=1);

return [

    'navigation' => [
        'label' => 'Logs',
        'group' => 'Trilhas',
    ],

    'page' => [
        'title'      => 'Logs',
        'heading'    => 'Explorador de logs',
        'subheading' => 'Navegue, leia e pesquise os arquivos de log da aplicação, agrupados por canal.',
    ],

    'actions' => [
        'refresh'         => 'Atualizar',
        'refresh_tooltip' => 'Reler os arquivos de log em disco',
    ],

    'channels' => [
        'untracked'  => 'Outros arquivos',
        'file_count' => '{0} Nenhum arquivo|{1} :count arquivo|[2,*] :count arquivos',
    ],

    'list' => [
        'empty' => [
            'heading'     => 'Nenhum arquivo de log encontrado',
            'description' => 'Nenhum arquivo de log legível foi encontrado para os canais configurados.',
        ],
        'size'       => 'Tamanho',
        'modified'   => 'Modificado :time',
        'view'       => 'Ver',
        'unreadable' => 'Ilegível',
    ],

    'viewer' => [
        'title'              => 'Arquivo de log',
        'channel'            => 'Canal',
        'size'               => 'Tamanho',
        'modified'           => 'Modificado',
        'position'           => 'Arquivo :current de :total',
        'search_placeholder' => 'Pesquisar neste arquivo…',
        'previous_file'      => 'Arquivo anterior',
        'next_file'          => 'Próximo arquivo',
        'previous_match'     => 'Ocorrência anterior',
        'next_match'         => 'Próxima ocorrência',
        'go_to_top'          => 'Ir para o topo',
        'go_to_bottom'       => 'Ir para o fim',
        'matches'            => ':current / :total',
        'no_matches'         => 'Nenhuma ocorrência',
        'lines'              => '{0} vazio|{1} :count linha|[2,*] :count linhas',
        'truncated_tail'     => 'Arquivo grande: exibindo os últimos :size (:lines). Baixe o arquivo para ler tudo.',
        'truncated_head'     => 'Arquivo grande: exibindo os primeiros :size (:lines). Baixe o arquivo para ler tudo.',
        'empty'              => 'Este arquivo está vazio.',
        'unreadable'         => 'Não foi possível ler este arquivo.',
        'copy'               => 'Copiar',
        'copied'             => 'Copiado!',
        'download'           => 'Baixar',
        'delete'             => 'Excluir',
        'close'              => 'Fechar',
        'keyboard_hint'      => 'Atalhos: / pesquisar · n / N ocorrência seguinte / anterior · g / G topo / fim',
    ],

    'delete' => [
        'modal_heading'     => 'Excluir este arquivo de log?',
        'modal_description' => 'Excluir ":name" permanentemente? Não há como desfazer.',
        'success_title'     => 'Arquivo excluído',
        'success_body'      => '":name" foi excluído.',
        'failed_title'      => 'Falha ao excluir',
        'failed_body'       => 'Não foi possível excluir ":name".',
        'denied_title'      => 'Não permitido',
        'denied_body'       => 'Você não tem permissão para excluir arquivos de log.',
        'missing_body'      => 'Este arquivo de log não existe mais.',
    ],

];
