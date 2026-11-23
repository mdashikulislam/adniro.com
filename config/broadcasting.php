<?php

return [
	
    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */
	
    'default' => env('BROADCAST_DRIVER', 'null'),
	
    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */
	
    'connections' => [
		
        /*
         * adniro: live chat. Laravel Reverb runs on this machine (php artisan reverb:start, kept alive by systemd).
         * PHP hands it messages on REVERB_HOST:REVERB_PORT (127.0.0.1:8080); the short timeouts mean that sending
         * a message is never held up when it is down. Browsers connect to the site's own address, see
         * App\Services\Chat\ChatLive ("client" is only for a different public address).
         * To turn live chat on: BROADCAST_DRIVER=reverb and the three REVERB_APP_* values in .env.
         */
        'reverb' => [
            'driver'  => 'reverb',
            'key'     => env('REVERB_APP_KEY'),
            'secret'  => env('REVERB_APP_SECRET'),
            'app_id'  => env('REVERB_APP_ID'),
            'options' => [
                'host'   => env('REVERB_HOST', '127.0.0.1'),
                'port'   => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'client_options' => [
                'connect_timeout' => 1,
                'timeout'         => 2,
            ],
            'client' => [
                'host'   => env('REVERB_CLIENT_HOST'),
                'port'   => env('REVERB_CLIENT_PORT'),
                'scheme' => env('REVERB_CLIENT_SCHEME'),
            ],
        ],

        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('PUSHER_APP_KEY'),
            'secret'  => env('PUSHER_APP_SECRET'),
            'app_id'  => env('PUSHER_APP_ID'),
			'options' => [
				'cluster' => env('PUSHER_APP_CLUSTER'),
				'useTLS'  => true,
			],
			'client_options' => [
				// Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
			],
        ],
		
		'ably' => [
			'driver' => 'ably',
			'key'    => env('ABLY_KEY'),
		],
		
        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],
		
        'log' => [
            'driver' => 'log',
        ],
		
        'null' => [
            'driver' => 'null',
        ],
		
    ],
	
];
