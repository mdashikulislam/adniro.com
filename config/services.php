<?php

return [
	
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */
	
	/*
	 * Mail providers
	 */
    'mailgun' => [
        'domain'   => null,
        'secret'   => null,
		'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'), // 'api.eu.mailgun.net' - If you are not using the United States Mailgun region
	    'scheme'   => 'https',
    ],
	
	'postmark' => [
		'token' => env('POSTMARK_TOKEN', ''),
	],
	
    'ses' => [
        'key'    => null,
        'secret' => null,
        'region' => null,
		'token'  => null, // To utilize AWS temporary credentials via a session token
    ],
	
    'sparkpost' => [
        'secret' => null,
        'guzzle' => [
            'verify' => false,
        ],
    ],
	
    'resend' => [
	    'key' => env('RESEND_API_KEY'),
    ],
	
    'mailersend' => [
	    'api_key' => env('MAILERSEND_API_KEY'),
    ],
	
    'brevo' => [
	    'key' => env('BREVO_API_KEY'),
    ],
	
	/*
	 * Social Auth Providers
	 */
    'facebook' => [
        'client_id'     => null,
        'client_secret' => null,
        'redirect'      => env('APP_URL') . '/auth/connect/facebook/callback',
    ],
	
	'linkedin-openid' => [
		'client_id'     => null,
		'client_secret' => null,
		'redirect'      => env('APP_URL') . '/auth/connect/linkedin/callback',
	],
	
	// OAuth 2.0
    'twitter-oauth-2' => [
	    'client_id'       => null,
	    'client_secret'   => null,
	    'redirect'        => env('APP_URL') . '/auth/connect/twitter-oauth-2/callback',
    ],
	
	// OAuth 1.0
	'twitter' => [
		'client_id'       => null,
		'client_secret'   => null,
		'redirect'        => env('APP_URL') . '/auth/connect/twitter/callback',
	],
	
    'google' => [
        'client_id'     => null,
        'client_secret' => null,
        'redirect'      => env('APP_URL') . '/auth/connect/google/callback',
    ],
	
	/*
	 * Payment gateways
	 * See payment plugins config files
	 */
	
	/*
	 * Other
	 */
    'slack' => [
	    'notifications' => [
		    'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
		    'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
	    ],
    ],
	
	'google_maps_platform' => [
		'maps_javascript_api_key' => null, //-> for Google 'Maps JavaScript API' & 'Maps Embed API'
		'maps_embed_api_key'      => null,
		'geocoding_api_key'       => null,
	],
	
];
