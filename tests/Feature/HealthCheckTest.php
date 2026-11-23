<?php

namespace Tests\Feature;

use App\Http\Controllers\HealthController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
 * GET /health for uptime monitors (adniro, phase 7).
 */

class HealthCheckTest extends TestCase
{
	use RefreshDatabase;
	
	public function test_a_healthy_site_answers_200_with_each_check(): void
	{
		// Arrange: the scheduler ran a minute ago
		Cache::put(HealthController::SCHEDULER_HEARTBEAT, now()->subMinute()->getTimestamp(), 3600);
		
		// Act
		$response = $this->getJson('/health');
		
		// Assert
		$response->assertOk()->assertExactJson([
			'status' => 'ok',
			'checks' => ['database' => 'ok', 'cache' => 'ok', 'storage' => 'ok', 'scheduler' => 'ok'],
		]);
		$this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
	}
	
	public function test_a_stopped_scheduler_answers_503(): void
	{
		// Arrange: last run long ago
		Cache::put(HealthController::SCHEDULER_HEARTBEAT, now()->subHour()->getTimestamp(), 3600);
		
		// Act
		$response = $this->getJson('/health');
		
		// Assert
		$response->assertStatus(503)->assertJson(['status' => 'fail', 'checks' => ['scheduler' => 'fail', 'database' => 'ok']]);
	}
	
	public function test_the_scheduler_writes_its_heartbeat_every_minute(): void
	{
		// Act
		\Illuminate\Support\Facades\Artisan::call('schedule:list');
		$list = \Illuminate\Support\Facades\Artisan::output();
		
		// Assert
		$this->assertMatchesRegularExpression('/\*\s+\*\s+\*\s+\*\s+\*.*health heartbeat/', $list);
	}
	
	public function test_the_live_chat_server_is_watched_only_where_live_chat_is_on(): void
	{
		// Arrange: the scheduler is fine, and something answers where the live chat server should be
		Cache::put(HealthController::SCHEDULER_HEARTBEAT, now()->subMinute()->getTimestamp(), 3600);
		$server = stream_socket_server('tcp://127.0.0.1:0');
		$port = (int)substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
		config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.options.host' => '127.0.0.1', 'broadcasting.connections.reverb.options.port' => $port]);
		
		// Act: up, then stopped
		$up = $this->getJson('/health');
		fclose($server);
		$down = $this->getJson('/health');
		
		// Assert
		$up->assertOk()->assertJsonPath('checks.live_chat', 'ok');
		$down->assertStatus(503)->assertJsonPath('checks.live_chat', 'fail')->assertJsonPath('checks.database', 'ok');
	}
}
