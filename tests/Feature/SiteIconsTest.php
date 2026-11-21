<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * The favicon, app icons and web app manifest, made from the favicon set in Admin > Settings (adniro).
 */

class SiteIconsTest extends TestCase
{
	use RefreshDatabase;

	private const FAVICON_PATH = 'app/ico/test-favicon.png';

	protected function setUp(): void
	{
		parent::setUp();

		// The test database has no countries or languages; the localization middleware would exit
		$this->withoutMiddleware([
			\App\Http\Middleware\GetLocalization::class,
			\App\Http\Middleware\SetBrowserLocale::class,
			\App\Http\Middleware\SetCountryLocale::class,
			\App\Http\Middleware\SetDefaultLocale::class,
			\App\Http\Middleware\RequirementsChecker::class,
		]);

		$disk = Storage::fake(config('filesystems.default', 'public'));
		$image = imagecreatetruecolor(600, 600);
		imagefill($image, 0, 0, imagecolorallocate($image, 13, 148, 136));
		ob_start();
		imagepng($image);
		$disk->put(self::FAVICON_PATH, ob_get_clean());

		config([
			'settings.app.name'    => 'Adniro',
			'settings.app.favicon' => self::FAVICON_PATH,
			'settings.style.skin'  => 'tealGreen',
		]);
	}

	public function test_favicon_ico_is_a_small_icon_made_from_the_favicon(): void
	{
		// Act
		$response = $this->get('/favicon.ico');

		// Assert
		$response->assertOk()->assertHeader('Content-Type', 'image/x-icon');
		$body = $response->getContent();
		$this->assertSame("\x00\x00\x01\x00\x01\x00", substr($body, 0, 6), 'an ICO file with one image');
		$this->assertSame(32, ord($body[6]), '32 pixels wide');
		$this->assertStringContainsString('max-age=', (string)$response->headers->get('Cache-Control'));
	}

	public function test_app_icons_have_the_size_their_name_gives(): void
	{
		foreach (['/apple-touch-icon.png' => 180, '/icon-192.png' => 192, '/icon-512.png' => 512] as $url => $size) {
			// Act
			$response = $this->get($url);

			// Assert
			$response->assertOk()->assertHeader('Content-Type', 'image/png');
			[$width, $height] = getimagesizefromstring($response->getContent());
			$this->assertSame([$size, $size], [$width, $height], $url);
		}
	}

	public function test_the_manifest_names_the_site_and_its_icons(): void
	{
		// Act
		$response = $this->get('/manifest.json');

		// Assert
		$response->assertOk()->assertJson([
			'name'        => 'Adniro',
			'short_name'  => 'Adniro',
			'start_url'   => '/',
			'display'     => 'standalone',
			'theme_color' => '#0d9488',
		]);
		$this->assertSame(['192x192', '512x512'], array_column($response->json('icons'), 'sizes'));
	}

	public function test_without_a_favicon_the_icons_are_not_found(): void
	{
		// Arrange
		config(['settings.app.favicon' => null]);

		// Act & Assert
		$this->get('/favicon.ico')->assertNotFound();
		$this->get('/icon-192.png')->assertNotFound();
	}
}
