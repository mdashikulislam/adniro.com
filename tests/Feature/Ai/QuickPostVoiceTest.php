<?php

namespace Tests\Feature\Ai;

use App\Http\Controllers\Web\Front\Post\QuickPost\QuickPostController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Dictation in the form's AI box when no speech-to-text model is assigned (adniro:ai).
 * The speech recognition itself runs in the browser; these tests cover what the server provides.
 */

class QuickPostVoiceTest extends TestCase
{
	use RefreshDatabase, QuickPostFixtures;
	
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpQuickPost();
		
		DB::table('languages')->insert([
			['code' => 'en', 'name' => 'English', 'native' => 'English', 'locale' => 'en_US', 'active' => 1, 'lft' => 1, 'rgt' => 2],
			['code' => 'bn', 'name' => 'Bengali', 'native' => 'বাংলা', 'locale' => 'bn_BD', 'active' => 1, 'lft' => 3, 'rgt' => 4],
			['code' => 'fr', 'name' => 'French', 'native' => 'Français', 'locale' => 'fr_FR', 'active' => 0, 'lft' => 5, 'rgt' => 6],
		]);
		Cache::flush();
	}
	
	public function test_the_speech_language_follows_the_site_language_and_country(): void
	{
		app()->setLocale('en');
		config(['country.code' => 'BD']);
		
		$voice = QuickPostController::voiceLanguages();
		
		$this->assertSame('en-BD', $voice['default']);
		$this->assertSame(['tag' => 'en-BD', 'label' => 'English (BD)'], $voice['languages'][0]);
		$tags = array_column($voice['languages'], 'tag');
		$this->assertContains('en-US', $tags);
		$this->assertContains('bn-BD', $tags);
		$this->assertNotContains('fr-FR', $tags, 'inactive languages are not offered');
		
		app()->setLocale('bn');
		$voice = QuickPostController::voiceLanguages();
		$this->assertSame('bn-BD', $voice['default']);
		$this->assertSame(1, count(array_filter($voice['languages'], fn ($l) => $l['tag'] === 'bn-BD')), 'no duplicate');
	}
	
}
