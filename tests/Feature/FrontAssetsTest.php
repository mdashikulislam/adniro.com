<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
 * Front-end assets after the phase 6 clean-up (adniro): one notification library, jQuery 3.7,
 * the hand-written stylesheet built and versioned.
 */

class FrontAssetsTest extends TestCase
{
	public function test_flash_messages_use_sweetalert2(): void
	{
		// Arrange
		flash('Your listing is online.')->now()->success();
		
		// Act
		$html = \Illuminate\Support\Facades\Blade::render("@include('helpers.flash.default')\n@stack('after_scripts_stack')");
		
		// Assert
		$this->assertSame('sweetalert2', config('larapen.flash.default'));
		$this->assertStringContainsString('Swal', $html);
		$this->assertStringContainsString('Your listing is online.', $html);
		$this->assertStringNotContainsString('PNotify', $html);
	}
	
	public function test_bundles_carry_jquery_3_7_and_front_pages_no_pnotify(): void
	{
		// Arrange
		$combine = file_get_contents(base_path('webpack.mix.combine.js'));
		
		// Assert
		$this->assertStringNotContainsString('jquery/3.3.1', $combine);
		$this->assertSame(3, substr_count($combine, 'jquery/3.7.1/jquery.min.js'));
		foreach (['front', 'auth'] as $bundle) {
			preg_match('#mix\.combine\(\[(.*?)\],\s*\'public/dist/' . $bundle . '/scripts\.js\'\)#s', $combine, $match);
			$this->assertNotEmpty($match, $bundle);
			$this->assertStringNotContainsString('pnotify', $match[1], $bundle . ' bundle has no PNotify library');
			$this->assertStringContainsString('3.7.1', file_get_contents(public_path("dist/{$bundle}/scripts.js")), $bundle);
		}
		$this->assertStringContainsString('sweetalert2', file_get_contents(public_path('dist/auth/scripts.js')));
	}
	
	public function test_the_custom_stylesheet_is_built_and_versioned(): void
	{
		// Arrange
		$manifest = json_decode(file_get_contents(public_path('mix-manifest.json')), true);
		
		// Assert
		$this->assertArrayHasKey('/dist/front/custom.css', $manifest);
		$this->assertStringContainsString('?id=', $manifest['/dist/front/custom.css']);
		$this->assertFileExists(resource_path('css/front/custom.css'));
		$this->assertLessThan(filesize(resource_path('css/front/custom.css')), filesize(public_path('dist/front/custom.css')), 'minified');
	}
}
