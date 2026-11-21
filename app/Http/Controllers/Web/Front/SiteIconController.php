<?php

namespace App\Http\Controllers\Web\Front;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image as ImageProcessor;
use Throwable;

/*
 * favicon.ico, app icons and the web app manifest (adniro), made from the favicon chosen in
 * Admin > Settings > App, so they follow it when it changes. Resized icons are cached.
 */

class SiteIconController extends Controller
{
	public const FAVICON_SIZE = 32;
	public const APPLE_TOUCH_SIZE = 180;
	public const MANIFEST_SIZES = [192, 512];
	
	private const BROWSER_CACHE_SECONDS = 604800; // a week
	private const DEFAULT_THEME_COLOR = '#0d6efd';
	private const APP_BACKGROUND = '#ffffff';
	private const MASKABLE_SAFE_RATIO = 0.7; // the logo's share of a maskable icon: inside Android's 80% safe circle
	
	public function favicon(): Response
	{
		$png = $this->iconPng(self::FAVICON_SIZE);
		abort_if(is_null($png), 404);
		
		return $this->image(self::icoFromPng($png, self::FAVICON_SIZE), 'image/x-icon');
	}
	
	public function appleTouchIcon(): Response
	{
		return $this->png(self::APPLE_TOUCH_SIZE);
	}
	
	public function manifestIcon(int $size): Response
	{
		abort_unless(in_array($size, self::MANIFEST_SIZES, true), 404);
		
		return $this->png($size);
	}
	
	/** A maskable icon: the favicon inside the safe area of a solid square, which Android crops to its own shape */
	public function maskableIcon(int $size): Response
	{
		abort_unless(in_array($size, self::MANIFEST_SIZES, true), 404);

		$png = $this->maskablePng($size);
		abort_if(is_null($png), 404);

		return $this->image($png, 'image/png');
	}

	public function manifest(): JsonResponse
	{
		$name = (string)config('settings.app.name', config('app.name'));
		$hasIcons = !empty(config('settings.app.favicon'));

		return response()
			->json([
				'id'               => '/',
				'name'             => $name,
				'short_name'       => mb_substr($name, 0, 12),
				'description'      => trans('app_install.manifest_text', ['name' => $name]),
				'start_url'        => '/',
				'scope'            => '/',
				'display'          => 'standalone',
				'background_color' => self::APP_BACKGROUND,
				'theme_color'      => self::themeColor(),
				'lang'             => app()->getLocale(),
				'dir'              => (config('lang.direction') == 'rtl') ? 'rtl' : 'ltr',
				'categories'       => ['shopping', 'business'],
				'icons'            => $hasIcons ? $this->manifestIcons() : [],
				'shortcuts'        => $this->manifestShortcuts($hasIcons),
			])
			->header('Content-Type', 'application/manifest+json')
			->header('Cache-Control', 'public, max-age=' . self::BROWSER_CACHE_SECONDS);
	}

	/** The icons for any shape, then the maskable ones */
	private function manifestIcons(): array
	{
		$icon = fn (string $path, int $size, string $purpose) => [
			'src'     => url($path),
			'sizes'   => $size . 'x' . $size,
			'type'    => 'image/png',
			'purpose' => $purpose,
		];

		return array_merge(
			array_map(fn (int $size) => $icon('icon-' . $size . '.png', $size, 'any'), self::MANIFEST_SIZES),
			array_map(fn (int $size) => $icon('icon-maskable-' . $size . '.png', $size, 'maskable'), self::MANIFEST_SIZES)
		);
	}

	/** Long-press menu of the installed app: post an ad, saved ads */
	private function manifestShortcuts(bool $hasIcons): array
	{
		$icons = $hasIcons ? [['src' => url('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png']] : [];
		$shortcut = fn (string $label, string $url) => array_filter([
			'name'       => trans('app_install.' . $label),
			'short_name' => trans('app_install.' . $label),
			'url'        => $url,
			'icons'      => $icons,
		]);

		return [
			$shortcut('shortcut_post', url('posts/create')),
			$shortcut('shortcut_saved', url('account/saved-posts')),
		];
	}
	
	/** The site's brand colour: a custom skin colour, else the chosen skin's colour. */
	public static function themeColor(): string
	{
		$custom = (string)config('settings.style.custom_skin_color');
		if (preg_match('/^#[0-9a-f]{3,8}$/i', $custom)) {
			return $custom;
		}
		
		$skins = (array)getCachedReferrerList('skins');
		$color = (string)data_get($skins, config('settings.style.skin', 'default') . '.color');
		
		return preg_match('/^#[0-9a-f]{3,8}$/i', $color) ? $color : self::DEFAULT_THEME_COLOR;
	}
	
	private function png(int $size): Response
	{
		$png = $this->iconPng($size);
		abort_if(is_null($png), 404);
		
		return $this->image($png, 'image/png');
	}
	
	private function image(string $bytes, string $type): Response
	{
		return response($bytes, 200, [
			'Content-Type'  => $type,
			'Cache-Control' => 'public, max-age=' . self::BROWSER_CACHE_SECONDS,
		]);
	}
	
	/** The favicon as a square PNG of $size pixels, or null when there is no usable favicon. */
	private function iconPng(int $size): ?string
	{
		$path = (string)config('settings.app.favicon');
		if ($path === '') {
			return null;
		}
		
		$key = 'site_icon:' . md5($path) . ':' . $size;
		$cached = Cache::get($key);
		if (is_string($cached)) {
			return $cached;
		}
		
		try {
			$disk = StorageDisk::getDisk();
			if (!$disk->exists($path)) {
				return null;
			}
			$png = (string)ImageProcessor::read($disk->get($path))->cover($size, $size)->toPng();
		} catch (Throwable $e) {
			Log::warning('Site icon could not be made from the favicon.', ['path' => $path, 'error' => $e->getMessage()]);
			
			return null;
		}
		
		Cache::forever($key, $png);
		
		return $png;
	}
	
	/** The favicon centred in the safe area of a white square of $size pixels, or null when there is no usable favicon. */
	private function maskablePng(int $size): ?string
	{
		$path = (string)config('settings.app.favicon');
		if ($path === '') {
			return null;
		}

		$key = 'site_icon_maskable:' . md5($path) . ':' . $size;
		$cached = Cache::get($key);
		if (is_string($cached)) {
			return $cached;
		}

		try {
			$disk = StorageDisk::getDisk();
			if (!$disk->exists($path)) {
				return null;
			}
			$inner = (int)round($size * self::MASKABLE_SAFE_RATIO);
			$logo = ImageProcessor::read($disk->get($path))->contain($inner, $inner);
			$png = (string)ImageProcessor::create($size, $size)
				->fill(self::APP_BACKGROUND)
				->place($logo, 'center')
				->toPng();
		} catch (Throwable $e) {
			Log::warning('Maskable site icon could not be made from the favicon.', ['path' => $path, 'error' => $e->getMessage()]);

			return null;
		}

		Cache::forever($key, $png);

		return $png;
	}

	/** An ICO file holding one PNG image (supported by every current browser). */
	private static function icoFromPng(string $png, int $size): string
	{
		$header = pack('vvv', 0, 1, 1);
		$entry = pack('CCCCvvVV', $size % 256, $size % 256, 0, 0, 1, 32, strlen($png), 6 + 16);
		
		return $header . $entry . $png;
	}
}
