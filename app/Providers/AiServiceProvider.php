<?php

namespace App\Providers;

use App\Models\Picture;
use App\Models\Post;
use App\Models\PostValue;
use App\Models\Setting;
use App\Observers\Ai\PictureModerationObserver;
use App\Observers\Ai\PostModerationObserver;
use App\Observers\Ai\PostValueModerationObserver;
use App\Services\Ai\AiBudget;
use App\Services\Ai\AiModelResolver;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\LaravelAiClient;
use Illuminate\Support\Facades\Queue;
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
		$this->app->singleton(\App\Services\ListingAssistant\ListingAssistantService::class);
		$this->app->singleton(\App\Services\ListingAssistant\CatalogBuilder::class);
	}
	
	public function boot(): void
	{
		// AI settings are read once per request or job: read them again between jobs and after a save
		Queue::looping(fn () => $this->flushAiSettings());
		Setting::saved(fn (Setting $setting) => $setting->name === 'ai' ? $this->flushAiSettings() : null);
		
		// Listing moderation: added next to the app's own observers, so no core observer changes
		Post::observe(PostModerationObserver::class);
		Picture::observe(PictureModerationObserver::class);
		PostValue::observe(PostValueModerationObserver::class);
		
		// Listing assistant: rebuild the catalog given to the AI when an admin changes it
		foreach ([\App\Models\Category::class, \App\Models\Field::class, \App\Models\FieldOption::class, \App\Models\CategoryField::class] as $model) {
			foreach (['saved', 'deleted'] as $event) {
				$model::$event(fn () => \App\Services\ListingAssistant\CatalogBuilder::invalidate());
			}
		}
	}
	
	private function flushAiSettings(): void
	{
		if ($this->app->resolved(AiSettings::class)) {
			$this->app->make(AiSettings::class)->flush();
		}
	}
}
