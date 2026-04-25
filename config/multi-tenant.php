<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Mode
    |--------------------------------------------------------------------------
    |
    | When set to false, the application runs in single-tenant mode.
    | All routes work on the main domain without subdomain resolution.
    | When set to true, subdomain-based tenant resolution is active.
    |
    | This is useful for local development where subdomains may not be
    | configured.
    |
    */
    'enabled' => env('MULTI_TENANT', true),
];
