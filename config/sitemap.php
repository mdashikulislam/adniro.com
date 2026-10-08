<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Here you may enable or disable caching of sitemaps for each time they
    | are generated. You can also specify the length of time (in seconds:
    | Laravel's cache TTLs are in seconds) they will remain cached.
    |
    */
    
    'cache_enabled' => true,
    
    // 6 hours: new listings show up in the sitemaps the same day
    'cache_length' => (int)env('SITEMAP_CACHE_LENGTH', 21600),
];
