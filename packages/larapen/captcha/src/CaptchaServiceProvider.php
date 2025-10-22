<?php

namespace Larapen\Captcha;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Hashing\BcryptHasher as Hasher;
use Illuminate\Routing\Router;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Class CaptchaServiceProvider
 *
 * @package Mews\Captcha
 */
class CaptchaServiceProvider extends ServiceProvider
{
	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		// Publish configuration files
		$this->publishes([
			__DIR__ . '/../config/captcha.php' => config_path('captcha.php'),
		], 'config');
		
		// HTTP routing
		Route::namespace('Larapen\Captcha')
			->group(function (Router $router) {
				$router->middleware(['web'])
					->group(function (Router $router) {
						// admin
						$adminUri = urlGen()->getAdminBasePath();
						$router->get($adminUri . '/captcha/{config?}', [CaptchaController::class, 'getCaptcha']);
						
						// front
						$router->get('captcha/{config?}', [CaptchaController::class, 'getCaptcha']);
					});
				
				$router->middleware(['api'])
					->group(function (Router $router) {
						$router->get('captcha/api/{config?}', [CaptchaController::class, 'getCaptchaApi']);
					});
			});
		
		// Validator extensions
		Validator::extend('captcha', function ($attribute, $value, $parameters) {
			return captcha_check($value);
		});
		
		// Validator extensions
		Validator::extend('captcha_api', function ($attribute, $value, $parameters) {
			return captcha_api_check($value, $parameters[0], $parameters[1] ?? 'default');
		});
	}
	
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void
	{
		// Merge configs
		$this->mergeConfigFrom(__DIR__ . '/../config/captcha.php', 'captcha');
		
		// Bind captcha
		$this->app->bind('captcha', function ($app) {
			return new Captcha(
				$app[Filesystem::class],
				$app[Repository::class],
				$app[Session::class],
				$app[Hasher::class],
				$app[Str::class]
			);
		});
	}
}
