<?php

return [
	
	/*
	|--------------------------------------------------------------------------
	| Default Cache TTL
	|--------------------------------------------------------------------------
	|
	| This value is the default time-to-live (in seconds) for cached data.
	| When you cache data without specifying a TTL, this value will be used.
	| Default: 3600 seconds (1 hour)
	|
	*/
	
	'default_ttl' => env('CACHE_MANAGER_DEFAULT_TTL', 3600),
	
	/*
	|--------------------------------------------------------------------------
	| Version Cache TTL
	|--------------------------------------------------------------------------
	|
	| For file-based cache drivers that use versioning strategy, this is the
	| time-to-live (in seconds) for version keys. Version keys should have a
	| longer TTL than regular cache data to avoid frequent regeneration.
	| Default: 2592000 seconds (30 days)
	|
	*/
	
	'version_ttl' => env('CACHE_MANAGER_VERSION_TTL', 2592000),
	
	/*
	|--------------------------------------------------------------------------
	| Version Key Prefix
	|--------------------------------------------------------------------------
	|
	| The prefix used for version keys in file-based cache drivers.
	| This helps avoid conflicts with other cache keys.
	| Default: 'cache_version_'
	|
	*/
	
	'version_prefix' => env('CACHE_MANAGER_VERSION_PREFIX', 'cache_version_'),
	
	/*
	|--------------------------------------------------------------------------
	| Enable Cache Logging
	|--------------------------------------------------------------------------
	|
	| When enabled, cache operations (invalidation, errors) will be logged.
	| Useful for debugging cache issues in development and staging environments.
	| Default: false (enabled only in non-production environments)
	|
	*/
	
	'enable_logging' => env('CACHE_MANAGER_LOGGING', true),
	
	/*
	|--------------------------------------------------------------------------
	| Cache Key Hash Algorithm
	|--------------------------------------------------------------------------
	|
	| The hashing algorithm used to generate cache keys from parameters.
	| Options: 'md5', 'sha1', 'sha256', 'xxh64', 'xxh128'
	| Default: 'md5' (fastest and sufficient for cache keys)
	|
	*/
	
	'hash_algorithm' => env('CACHE_MANAGER_HASH_ALGO', 'md5'),
	
	/*
	|--------------------------------------------------------------------------
	| Auto-Invalidation Settings
	|--------------------------------------------------------------------------
	|
	| Control how automatic cache invalidation behaves across the application.
	|
	*/
	
	'auto_invalidation' => [
		
		/*
		| Enable/disable automatic cache invalidation globally
		| When false, models won't auto-invalidate cache on save/delete
		*/
		'enabled'    => env('CACHE_MANAGER_AUTO_INVALIDATION', true),
		
		/*
		| Invalidate cache on model creation
		*/
		'on_create'  => env('CACHE_MANAGER_INVALIDATE_ON_CREATE', true),
		
		/*
		| Invalidate cache on model update
		*/
		'on_update'  => env('CACHE_MANAGER_INVALIDATE_ON_UPDATE', true),
		
		/*
		| Invalidate cache on model deletion
		*/
		'on_delete'  => env('CACHE_MANAGER_INVALIDATE_ON_DELETE', true),
		
		/*
		| Invalidate cache on model restoration (soft deletes)
		*/
		'on_restore' => env('CACHE_MANAGER_INVALIDATE_ON_RESTORE', true),
	],
	
	/*
	|--------------------------------------------------------------------------
	| Auto-Clean up expired cache files
	|--------------------------------------------------------------------------
	|
	| Run clean up periodically via scheduler
	| Old implementation: env('DISABLE_CACHE_AUTO_CLEAR', false)
	|
	*/
	
	'auto_cleanup_expired_cache' => env('CACHE_MANAGER_AUTO_CLEAN_UP', true),
	
	/*
	|--------------------------------------------------------------------------
	| Performance Settings
	|--------------------------------------------------------------------------
	|
	| Settings to optimize cache performance
	|
	*/
	
	'performance' => [
		/*
		| Cache the driver support check result
		| Reduces overhead of checking if driver supports tags on every operation
		*/
		'cache_driver_check' => true,
		
		/*
        | Serialize method for cache keys
        | Options: 'serialize', 'json' (json is faster but less reliable for complex objects)
        */
		'serialize_method' => 'serialize',
	],
	
	/*
	|--------------------------------------------------------------------------
	| Model-Specific Settings
	|--------------------------------------------------------------------------
	|
	| Override cache settings for specific models
	|
	| Example:
	| 'models' => [
	|     'User' => [
	|         'default_ttl' => 7200,
	|         'enable_auto_invalidation' => true,
	|     ],
	|     'ActivityLog' => [
	|         'enable_auto_invalidation' => false,
	|     ],
	| ],
	|
	*/
	
	'models' => [
		// Add model-specific overrides here
	],
	
	/*
	|--------------------------------------------------------------------------
	| Environment-Specific Defaults
	|--------------------------------------------------------------------------
	|
	| Different defaults for different environments
	| These can be overridden in environment-specific config files
	|
	*/
	
	'environments' => [
		'local' => [
			'default_ttl'    => null, // 600: 10 minutes in development
			'version_ttl'    => null, // 86400: 1 day in development
			'enable_logging' => true,
		],
		
		'testing' => [
			'default_ttl'    => 60, // 60: 1 minute in tests
			'version_ttl'    => 300, // 300: 5 minutes in tests
			'enable_logging' => false,
		],
		
		'staging' => [
			'default_ttl'    => 1800, // 1800: 30 minutes in staging
			'version_ttl'    => 604800, // 604800: 7 days in staging
			'enable_logging' => true,
		],
		
		'production' => [
			'default_ttl'    => null, // 3600: 1 hour in production
			'version_ttl'    => null, // 2592000: 30 days in production
			'enable_logging' => false,
		],
	],

];
