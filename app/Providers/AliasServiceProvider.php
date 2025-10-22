<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Js;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Larapen\Captcha\Facades\Captcha;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Larapen\TextToImage\Facades\TextToImage;

class AliasServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Get the AliasLoader instance
		$loader = AliasLoader::getInstance();
		
		// Add your aliases
		$loader->alias('App', App::class);
		$loader->alias('Arr', Arr::class);
		$loader->alias('Artisan', Artisan::class);
		$loader->alias('Auth', Auth::class);
		$loader->alias('Blade', Blade::class);
		$loader->alias('Broadcast', Broadcast::class);
		$loader->alias('Bus', Bus::class);
		$loader->alias('Cache', Cache::class);
		$loader->alias('Config', Config::class);
		$loader->alias('Cookie', Cookie::class);
		$loader->alias('Crypt', Crypt::class);
		$loader->alias('Date', Date::class);
		$loader->alias('DB', DB::class);
		$loader->alias('Eloquent', Model::class);
		$loader->alias('Event', Event::class);
		$loader->alias('File', File::class);
		$loader->alias('Gate', Gate::class);
		$loader->alias('Hash', Hash::class);
		$loader->alias('Http', Http::class);
		$loader->alias('Js', Js::class);
		$loader->alias('Lang', Lang::class);
		$loader->alias('Log', Log::class);
		$loader->alias('Mail', Mail::class);
		$loader->alias('Notification', Notification::class);
		$loader->alias('Password', Password::class);
		$loader->alias('Queue', Queue::class);
		$loader->alias('RateLimiter', RateLimiter::class);
		$loader->alias('Redirect', Redirect::class);
		$loader->alias('Request', Request::class);
		$loader->alias('Response', Response::class);
		$loader->alias('Route', Route::class);
		$loader->alias('Schema', Schema::class);
		$loader->alias('Session', Session::class);
		$loader->alias('Storage', Storage::class);
		$loader->alias('Str', Str::class);
		$loader->alias('URL', URL::class);
		$loader->alias('Validator', Validator::class);
		$loader->alias('View', View::class);
		
		$loader->alias('TextToImage', TextToImage::class);
		$loader->alias('MetaTag', MetaTag::class);
		$loader->alias('Captcha', Captcha::class);
	}
	
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}
}
