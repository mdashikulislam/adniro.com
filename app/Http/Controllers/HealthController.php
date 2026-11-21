<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * GET /health (adniro): for uptime monitors. 200 when every check passes, 503 otherwise.
 * Only check names and ok/fail are shown; details go to the log.
 */

class HealthController extends Controller
{
	/** Written by the scheduler every minute (App\Console\Kernel). */
	public const SCHEDULER_HEARTBEAT = 'health:scheduler_heartbeat';
	
	/** The scheduler counts as stopped after this long without a run. */
	public const SCHEDULER_MAX_SILENCE_SECONDS = 600;
	
	private const CACHE_PROBE = 'health:cache_probe';
	
	public function __invoke(): JsonResponse
	{
		$checks = [
			'database'  => $this->check('database', fn () => DB::select('SELECT 1')),
			'cache'     => $this->check('cache', function () {
				$value = (string)microtime(true);
				Cache::put(self::CACHE_PROBE, $value, 60);
				
				return Cache::get(self::CACHE_PROBE) === $value;
			}),
			'storage'   => $this->check('storage', fn () => is_writable(storage_path('framework')) && is_writable(storage_path('logs'))),
			'scheduler' => $this->check('scheduler', function () {
				$lastRun = (int)Cache::get(self::SCHEDULER_HEARTBEAT, 0);
				
				return $lastRun > 0 && (time() - $lastRun) <= self::SCHEDULER_MAX_SILENCE_SECONDS;
			}),
		];
		
		$healthy = !in_array('fail', $checks, true);
		
		return response()
			->json(['status' => $healthy ? 'ok' : 'fail', 'checks' => $checks], $healthy ? 200 : 503)
			->header('Cache-Control', 'no-store, max-age=0');
	}
	
	public static function recordSchedulerHeartbeat(): void
	{
		Cache::put(self::SCHEDULER_HEARTBEAT, time(), 86400);
	}
	
	private function check(string $name, callable $probe): string
	{
		try {
			return $probe() === false ? 'fail' : 'ok';
		} catch (Throwable $e) {
			Log::warning('Health check failed: ' . $name, ['error' => $e->getMessage()]);
			
			return 'fail';
		}
	}
}
