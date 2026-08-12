<?php

use App\Models\User;

return [

    'enabled' => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_ENABLED', true),

    'composer_json_path' => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_COMPOSER_JSON', base_path('composer.json')),

    'composer_lock_path' => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_COMPOSER_LOCK', base_path('composer.lock')),

    /*
    |--------------------------------------------------------------------------
    | Version source
    |--------------------------------------------------------------------------
    |
    | packagist — Uses repo.packagist.org (p2) JSON, no token. Latest version from
    | published stable tags. Optional GitHub **website** compare URL when lock
    | source points at github.com (no GitHub API for compare unless you opt in).
    |
    | github — Uses GitHub Releases + Compare APIs (token recommended for rate limits).
    |
    */
    'version_source' => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_VERSION_SOURCE', 'packagist'),

    'packagist' => [
        'base_url'     => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_URL', 'https://repo.packagist.org'),
        'http_timeout' => (int) env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_TIMEOUT', 15),
        'user_agent'   => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_USER_AGENT', 'filament-composer-release-notifier'),
    ],

    'github' => [
        'token'        => env('GITHUB_TOKEN'),
        'http_timeout' => (int) env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_HTTP_TIMEOUT', 15),
        'user_agent'   => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_USER_AGENT', 'filament-composer-release-notifier'),
    ],

    'features' => [
        'resource'    => true,
        'widget'      => true,
        'mail_report' => false,
    ],

    'sync' => [
        'queue'      => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_QUEUE'),
        'connection' => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_QUEUE_CONNECTION'),
    ],

    'compare' => [
        'max_commits_stored' => 50,
        /*
         * When version_source is packagist, set true to still call GitHub’s compare
         * API for commit summaries (subject to anonymous rate limits without a token).
         */
        'fetch_github_commits_with_packagist' => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_FETCH_GITHUB_COMMITS', false),
    ],

    'excluded_packages' => [
        // 'php',
    ],

    'mail' => [
        'recipient_mode'   => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_MAIL_MODE', 'none'), // none | all_panel_users | specific_emails
        'specific_emails'  => array_filter(array_map('trim', explode(',', (string) env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_MAIL_TO', '')))),
        'user_model'       => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_USER_MODEL', User::class),
        'send_when'        => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_MAIL_SEND_WHEN', 'when_outdated'), // always | when_outdated
        'throttle_hours'   => (int) env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_MAIL_THROTTLE_HOURS', 24),
        'allow_log_driver' => env('FILAMENT_COMPOSER_RELEASE_NOTIFIER_MAIL_ALLOW_LOG', false),
    ],

];
