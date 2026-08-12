<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the branded error pages
    |--------------------------------------------------------------------------
    |
    | When true, Sentinel registers its views under the `errors` namespace so
    | Laravel resolves them for aborted requests. Your app can still override
    | any page by adding its own resources/views/errors/{code}.blade.php.
    |
    */

    'enabled' => env('SENTINEL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Handled status codes
    |--------------------------------------------------------------------------
    |
    | Only these HTTP status codes are served by Sentinel. Any other code falls
    | back to the framework (or your app's) default error view.
    |
    */

    'codes' => [500, 403, 404, 419, 503],

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | Wordmark shown above each page. Defaults to the application name.
    |
    */

    'brand' => env('SENTINEL_BRAND', null),

    /*
    |--------------------------------------------------------------------------
    | Message number
    |--------------------------------------------------------------------------
    |
    | A stable, SAP-style reference for each error (e.g. SNT-500-087). The suffix
    | is derived deterministically from the error signature, so the same failure
    | always produces the same number — easy to quote to support.
    |
    */

    'message_number' => [
        'enabled' => true,
        'prefix'  => env('SENTINEL_PREFIX', 'SNT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request id
    |--------------------------------------------------------------------------
    |
    | Show a per-request identifier so a user can quote it to support. Sentinel
    | reuses an incoming X-Request-Id header when present, otherwise generates
    | one via its middleware.
    |
    */

    'request_id' => [
        'enabled' => true,
        'header'  => 'X-Request-Id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception details (500)
    |--------------------------------------------------------------------------
    |
    | Reveal the real exception on the 500 page's "view more" panel — the class,
    | file and line, its message and a short stack trace, i.e. the actual Laravel
    | error. Values:
    |
    |   null  (default) — follow APP_DEBUG: shown locally, hidden in production.
    |   true            — always show, even in production. Use only when the 500
    |                     is reachable strictly by trusted panel admins.
    |   false           — never show, even in debug.
    |
    */

    'show_exception_details' => env('SENTINEL_SHOW_EXCEPTION_DETAILS', null),

    /*
    |--------------------------------------------------------------------------
    | Livewire overlay (500)
    |--------------------------------------------------------------------------
    |
    | When a panel (Livewire) action fails with a 500, show the branded 500 as a
    | small window over the real page — dimmed behind, dismissible by clicking
    | outside or pressing Escape — instead of Livewire's default dark modal.
    |
    */

    'livewire_overlay' => true,

    /*
    |--------------------------------------------------------------------------
    | 500 presentation
    |--------------------------------------------------------------------------
    |
    | How the 500 is presented:
    |
    |   style    'window' — a small docked message window (over the real page on
    |                       a Livewire error). 'page' — a full-page card, exactly
    |                       like the other error codes; a Livewire 500 then takes
    |                       over the document like landing on a real error page.
    |
    |   position where the docked window sits (style 'window' only). One of:
    |            bottom-left, bottom-right, top-left, top-right, top-center,
    |            bottom-center, center.
    |
    |   width    the docked window width (style 'window' only). A number is taken
    |            as pixels (400 => 400px); a string is used verbatim (e.g. "28rem").
    |
    */

    'window' => [
        'style'    => env('SENTINEL_500_STYLE', 'window'),
        'position' => env('SENTINEL_500_POSITION', 'bottom-left'),
        'width'    => env('SENTINEL_500_WIDTH', 400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Support link
    |--------------------------------------------------------------------------
    |
    | Optional URL or mailto: shown as a "Contact support" action.
    |
    */

    'support_url' => env('SENTINEL_SUPPORT_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Home url
    |--------------------------------------------------------------------------
    |
    | Destination of the primary "back to safety" button. Defaults to "/".
    |
    */

    'home_url' => env('SENTINEL_HOME_URL', '/'),

];
