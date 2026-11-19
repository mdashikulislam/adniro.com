<?php

namespace App\Providers;

use App\Models\Picture;
use App\Models\Post;
use App\Models\PostValue;
use App\Observers\Ai\PictureModerationObserver;
use App\Observers\Ai\PostModerationObserver;
use App\Observers\Ai\PostValueModerationObserver;
use App\Services\Ai\AiBudget;
use App\Services\Ai\AiModelResolver;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\LaravelAiClient;
use Illuminate\Support\ServiceProvider;

/*
 * AI features (adniro:ai).
 */

class AiServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(config_path('ai_features.php'), 'ai_features');
		
		$this->app->singleton(AiSettings::class);
		$this->app->singleton(AiModelResolver::class);
		$this->app->singleton(AiBudget::class);
		$this->app->singleton(AiClientContract::class, LaravelAiClient::class);
	}
	
	public function boot(): void
	{
		// Listing moderation: added next to the app's own observers, so no core observer changes
		Post::observe(PostModerationObserver::class);
		Picture::observe(PictureModerationObserver::class);
		PostValue::observe(PostValueModerationObserver::class);
	}
}
