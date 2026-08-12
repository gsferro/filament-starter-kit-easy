<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bloqueio de sessão (marjose123/filament-lockscreen)
    |--------------------------------------------------------------------------
    | O plugin fica registrado nos 3 painéis (obrigatório — ver nota no
    | AdminPanelProvider); estas chaves são o kill-switch e o tempo ocioso.
    */

    'enabled' => env('LOCKSCREEN_ENABLED', true),

    // Segundos sem navegação até a tela de bloqueio (30 min).
    'idle_timeout' => env('LOCKSCREEN_IDLE_TIMEOUT', 1800),

];
