<?php

/*
 * O pacote só traz inglês. Tradução do painel de colunas fixas — a mesma
 * convenção usada para os outros pacotes em lang/vendor/.
 */

return [

    'trigger' => [
        'label'   => 'Fixar colunas',
        'tooltip' => 'Fixar colunas',
    ],

    'heading' => 'Fixar colunas',

    /*
     * Estas quatro o pacote escreve direto no Blade, sem __(). A view foi
     * publicada em resources/views/vendor/asmit-resized-column/ só para
     * trocá-las por estas chaves.
     */
    'select_all'   => 'Selecionar todas',
    'deselect_all' => 'Limpar seleção',
    'empty'        => 'Nenhuma coluna',
    'apply'        => 'Aplicar',

];
