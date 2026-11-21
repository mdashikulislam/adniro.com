<?php

namespace Tests\Feature;

use App\Http\Middleware\TipsMessages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/*
 * The "Get the best deals faster" login nudge shows once per browser session, not on every page (adniro).
 */

class LoginNudgeTest extends TestCase
{
	use RefreshDatabase;
	
	public function test_guests_see_the_login_nudge_once_per_session(): void
	{
		// Arrange
		config(['settings.other.show_tips_messages' => '1', 'settings.localization.geoip_activation' => '0']);
		
		// Act
		$first = $this->runMiddleware('/some-page');
		$second = $this->runMiddleware('/another-page');
		
		// Assert
		$this->assertStringContainsString('Get the best deals faster', $first);
		$this->assertSame('', $second);
	}
	
	/** The flash messages the middleware leaves for the page. */
	private function runMiddleware(string $path): string
	{
		$request = Request::create($path);
		$request->setLaravelSession($this->app['session.store']);
		$this->app->instance('request', $request);
		view()->share('immediateMessages', []);
		
		(new TipsMessages())->handle($request, fn () => new Response('ok'));
		
		return collect(view()->shared('immediateMessages', []))->pluck('message')->implode(' ');
	}
}
