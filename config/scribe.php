<?php

use Knuckles\Scribe\Extracting\Strategies;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Config\AuthIn;
use function Knuckles\Scribe\Config\{removeStrategies, configureStrategy};

return [
	
	// The HTML <title> for the generated documentation.
    'title' => 'LaraClassifier API Documentation',
	
	// A short description of your API. Will be included in the docs webpage, Postman collection and OpenAPI spec.
    'description' => 'LaraClassifier API specification and documentation.',
	
	// The base URL displayed in the docs.
	// If you're using `laravel` type, you can set this to a dynamic string, like '{{ config("app.tenant_url") }}' to get a dynamic base URL.
    'base_url' => null,
	
	// Routes to include in the docs
    'routes' => [
        [
            /*
             * Specify conditions to determine what routes will be a part of this group.
             * A route must fulfill ALL conditions to be included.
             */
            'match' => [
	            // Match only routes whose paths match this pattern (use * as a wildcard to match any characters). Example: 'users/*'.
                'prefixes' => ['api/*'],
				
	            // Match only routes whose domains match this pattern (use * as a wildcard to match any characters). Example: 'api.*'.
                'domains' => ['*'],
            ],
			
	        // Include these routes even if they did not match the rules above.
            'include' => [
                // 'users.index', 'healthcheck*',
            ],
			
	        // Exclude these routes even if they matched the rules above.
            'exclude' => [
                // '/health', 'admin.*',
	            'auth.register', 'posts.payments', 'any.other',
            ],
        ],
    ],
	
	// The type of documentation output to generate.
	// - "static" will generate a static HTMl page in the /public/docs folder,
	// - "laravel" will generate the documentation as a Blade view, so you can add routing and authentication.
	// - "external_static" and "external_laravel" do the same as above, but pass the OpenAPI spec as a URL to an external UI template
    'type' => 'laravel',
	
	// See https://scribe.knuckles.wtf/laravel/reference/config#theme for supported options
    'theme' => 'default',
	
    /*
     * Settings for `static` type output.
     */
    'static' => [
	    // HTML documentation, assets and Postman collection will be generated to this folder.
	    // Source Markdown will still be in resources/docs.
	    'output_path' => 'documentation/api',
    ],

    /*
     * Settings for `laravel` type output.
     */
    'laravel' => [
	    // Whether to automatically create a docs route for you to view your generated docs. You can still set up routing manually.
        'add_routes' => true,
	
	    // URL path to use for the docs endpoint (if `add_routes` is true).
	    // By default, `/docs` opens the HTML page, `/docs.postman` opens the Postman collection, and `/docs.openapi` the OpenAPI spec.
        'docs_url' => '/docs/api',
	
	    // Directory within `public` in which to store CSS and JS assets.
	    // By default, assets are stored in `public/vendor/scribe`.
	    // If set, assets will be stored in `public/{{assets_directory}}`
        'assets_directory' => null,
	
	    // Middleware to attach to the docs endpoint (if `add_routes` is true).
        'middleware' => [
	        'admin',
        ],
    ],

    'external' => [
	    'html_attributes' => []
    ],

    'try_it_out' => [
	    // Add a Try It Out button to your endpoints so consumers can test endpoints right from their browser.
	    // Don't forget to enable CORS headers for your endpoints.
        'enabled' => true,
	
	    // The base URL to use in the API tester. Leave as null to be the same as the displayed URL (`scribe.base_url`).
        'base_url' => null,
	
	    // [Laravel Sanctum] Fetch a CSRF token before each request, and add it as an X-XSRF-TOKEN header.
        'use_csrf' => false,
	
	    // The URL to fetch the CSRF token from (if `use_csrf` is true).
        'csrf_url' => '/sanctum/csrf-cookie',
    ],
	
	// How is your API authenticated? This information will be used in the displayed docs, generated examples and response calls.
    'auth' => [
	    // Set this to true if ANY endpoints in your API use authentication.
        'enabled' => true,
	
	    // Set this to true if your API should be authenticated by default. If so, you must also set `enabled` (above) to true.
	    // You can then use @unauthenticated or @authenticated on individual endpoints to change their status from the default.
        'default' => false,
		
	    // Where is the auth value meant to be sent in a request?
        'in' => AuthIn::BEARER->value,
		
	    // The name of the auth parameter (e.g. token, key, apiKey) or header (e.g. Authorization, Api-Key).
        'name' => 'Authorization',
		
	    // The value of the parameter to be used by Scribe to authenticate response calls.
	    // This will NOT be included in the generated documentation. If empty, Scribe will use a random value.
        'use_value' => env('DOCS_API_AUTH_TOKEN'),
		
	    // Placeholder your users will see for the auth parameter in the example requests.
	    // Set this to null if you want Scribe to use a random value as placeholder instead.
        'placeholder' => '{YOUR_AUTH_KEY}',
		
	    // Any extra authentication-related info for your users. Markdown and HTML are supported.
        'extra_info' => 'You can retrieve your token by visiting your dashboard and clicking <b>Generate API token</b>.',
    ],
	
	// Text to place in the "Introduction" section, right after the `description`. Markdown and HTML are supported.
    'intro_text' => <<<INTRO
This documentation aims to provide all the information you need to work with our API.

<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>
<p><strong>Important:</strong> By default the API uses an access token set in the <strong><code>/.env</code></strong> file with the variable <code>APP_API_TOKEN</code>, whose its value
need to be added in the header of all the API requests with <code>X-AppApiToken</code> as key. On the other hand, the key <code>X-AppType</code> must not be added to the header... This key is only useful for the included web client and for API documentation.</p>
<p>Also, by default the default app's country will be selected if the <strong><code>countryCode</code></strong> query parameter is not filled during API calls. If a default country is not set for the app, the most populated country will be selected. Same for the language, which the default app language will be selected if the <strong><code>languageCode</code></strong> query parameter is not filled.</p>
INTRO
    ,
	
	// Example requests for each endpoint will be shown in each of these languages.
	// Supported options are: bash, javascript, php, python
	// To add a language of your own, see https://scribe.knuckles.wtf/laravel/advanced/example-requests
	// Note: does not work for `external` docs types
    'example_languages' => [
        'bash',
        'javascript',
	    'php',
	    'python',
    ],
	
	// Generate a Postman collection (v2.1.0) in addition to HTML docs.
	// For 'static' docs, the collection will be generated to public/docs/collection.json.
	// For 'laravel' docs, it will be generated to storage/app/scribe/collection.json.
	// Setting `laravel.add_routes` to true (above) will also add a route for the collection.
    'postman' => [
        'enabled' => true,
		
        /*
         * Manually override some generated content in the spec. Dot notation is supported.
         */
        'overrides' => [
            // 'info.version' => '2.0.0',
        ],
    ],
	
	// Generate an OpenAPI spec (v3.0.1) in addition to docs webpage.
	// For 'static' docs, the collection will be generated to public/docs/openapi.yaml.
	// For 'laravel' docs, it will be generated to storage/app/scribe/openapi.yaml.
	// Setting `laravel.add_routes` to true (above) will also add a route for the spec.
    'openapi' => [
        'enabled' => false,
		
        /*
         * Manually override some generated content in the spec. Dot notation is supported.
         */
        'overrides' => [
            // 'info.version' => '2.0.0',
        ],
		
	    // Additional generators to use when generating the OpenAPI spec.
	    // Should extend `Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator`.
        'generators' => [],
    ],
	
    'groups' => [
	    // Endpoints which don't have a @group will be placed in this default group.
        'default' => 'Endpoints',
	
	    // By default, Scribe will sort groups alphabetically, and endpoints in the order their routes are defined.
	    // You can override this by listing the groups, subgroups and endpoints here in the order you want them.
	    // See https://scribe.knuckles.wtf/blog/laravel-v4#easier-sorting and https://scribe.knuckles.wtf/laravel/reference/config#order for details
	    // Note: does not work for `external` docs types
        'order' => [
            // 'This group will come first',
            // 'This group will come next' => [
            //     'POST /this-endpoint-will-comes-first',
            //     'GET /this-endpoint-will-comes-next',
            // ],
            // 'This group will come third' => [
            //     'This subgroup will come first' => [
            //         'GET /this-other-endpoint-will-comes-first',
            //         'GET /this-other-endpoint-will-comes-next',
            //     ]
            // ]
        ],
    ],
	
	// Custom logo path. This will be used as the value of the src attribute for the <img> tag,
	// so make sure it points to an accessible URL or path. Set to "false" to not use a logo.
	// For example, if your logo is in public/img:
	// - 'logo' => '../img/logo.png' // for `static` type (output folder is public/docs)
	// - 'logo' => 'img/logo.png' // for `laravel` type
    'logo' => '../storage/app/default/logo-api.png',
	
	// Customize the "Last updated" value displayed in the docs by specifying tokens and formats.
	// Examples:
	// - {date:F j Y} => March 28, 2022
	// - {git:short} => Short hash of the last Git commit
	// Available tokens are `{date:<format>}` and `{git:<format>}`.
	// The format you pass to `date` will be passed to PHP's `date()` function.
	// The format you pass to `git` can be either "short" or "long".
	// Note: does not work for `external` docs types
    'last_updated' => 'Last updated: {date:F j, Y}',
	
    'examples' => [
	    // Set this to any number to generate the same example values for parameters on each run,
        'faker_seed' => null,
		
	    // With API resources and transformers, Scribe tries to generate example models to use in your API responses.
	    // By default, Scribe will try the model's factory, and if that fails, try fetching the first from the database.
	    // You can reorder or remove strategies here.
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],
	
	// The strategies Scribe will use to extract information about your routes at each stage.
	// Use configureStrategy() to specify settings for a strategy in the list.
	// Use removeStrategies() to remove an included strategy.
    'strategies' => [
	    'metadata' => [
		    ...Defaults::METADATA_STRATEGIES,
	    ],
	    'headers' => [
		    ...Defaults::HEADERS_STRATEGIES,
		    Strategies\StaticData::withSettings(data: [
			    'Content-Type'     => 'application/json',
			    'Accept'           => 'application/json',
			    'Content-Language' => env('APP_LOCALE'),
			    'X-AppApiToken'    => env('APP_API_TOKEN'),
			    'X-AppType'        => 'docs',
		    ]),
	    ],
	    'urlParameters' => [
		    ...Defaults::URL_PARAMETERS_STRATEGIES,
	    ],
	    'queryParameters' => [
		    ...Defaults::QUERY_PARAMETERS_STRATEGIES,
	    ],
	    'bodyParameters' => [
		    ...Defaults::BODY_PARAMETERS_STRATEGIES,
	    ],
	    'responses' => configureStrategy(
		    Defaults::RESPONSES_STRATEGIES,
		    Strategies\Responses\ResponseCalls::withSettings(
		        /*
				 * API calls will be made only for routes in this group matching these HTTP methods (GET, POST, etc).
				 * List the methods here or use '*' to mean all methods. Leave empty to disable API calls.
				 */
			    only: ['GET *'],
			    /*
				 * Laravel config variables which should be set for the API call.
				 * This is a good place to ensure that notifications, emails and other external services
				 * are not triggered during the documentation API calls.
				 * You can also create a `.env.docs` file and run the generate command with `--env docs`.
				 */
			    // Recommended: disable debug mode in response calls to avoid error stack traces in responses
			    config: [
				    'app.env' => 'local',
				    'app.debug' => false,
			    ]
		    )
	    ),
	    'responseFields' => [
		    ...Defaults::RESPONSE_FIELDS_STRATEGIES,
	    ]
    ],
	
	// For response calls, API resource responses and transformer responses,
	// Scribe will try to start database transactions, so no changes are persisted to your database.
	// Tell Scribe which connections should be transacted here. If you only use one db connection, you can leave this as is.
    'database_connections_to_transact' => [config('database.default')],
	
    'fractal' => [
	    // If you are using a custom serializer with league/fractal, you can specify it here.
	    // Leave as null to use no serializer or return simple JSON.
        'serializer' => null,
    ],
];
