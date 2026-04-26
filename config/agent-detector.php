<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CSRF Bypass
    |--------------------------------------------------------------------------
    | When true, CSRF verification is skipped for detected agent requests.
    | Default is true — installs and immediately fixes the top pain point (419s).
    | Set to false to manage CSRF yourself.
    */
    'disable_csrf' => true,

    /*
    |--------------------------------------------------------------------------
    | Agent Log Channel
    |--------------------------------------------------------------------------
    | Name of the auto-registered log channel for agent activity.
    | Logs are written to storage/logs/{log_channel}.log.
    | Set to null to disable.
    */
    'log_channel' => 'agent',

    /*
    |--------------------------------------------------------------------------
    | Auto-register Middleware
    |--------------------------------------------------------------------------
    | Middleware must be added manually via bootstrap/app.php.
    | See README for instructions.
    */
    'auto_register_middleware' => false,
];
