<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Endpoint Check Retention
    |--------------------------------------------------------------------------
    |
    | Endpoint checks accumulate forever otherwise (one row per check, at
    | up to one check per minute per endpoint) — this bounds how long that
    | history is kept before `endpoint-checks:prune` deletes it.
    |
    */

    'check_retention_days' => (int) env('UPTIME_CHECK_RETENTION_DAYS', 90),

];
