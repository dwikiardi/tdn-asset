<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tridatu Netmon API Integration
    |--------------------------------------------------------------------------
    | Konfigurasi untuk integrasi dengan sistem Tridatu Netmon.
    | Set variabel di .env:
    |   TRIDATU_BASE_URL=https://netmon.tridatu.com
    |   TRIDATU_API_KEY=your-api-key-here
    |   TRIDATU_CACHE_TTL=300
    */

    'base_url'  => env('TRIDATU_BASE_URL', ''),
    'api_key'   => env('TRIDATU_API_KEY', ''),
    'cache_ttl' => env('TRIDATU_CACHE_TTL', 300), // detik

];
