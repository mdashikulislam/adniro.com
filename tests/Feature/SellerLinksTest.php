<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

/*
 * Links that sellers put in a listing (adniro): every link to another website is marked
 * rel="nofollow ugc", whatever rel it already carries, so a listing passes no ranking on.
 */

class SellerLinksTest extends TestCase
{
	private const SITE_HOST = 'www.adniro.test';
	
	public function test_a_link_without_a_rel_is_marked(): void
	{
		// Act
		$html = noFollowLinks('<a href="https://shop.example/pills">Shop</a>', self::SITE_HOST);
		
		// Assert
		$this->assertSame('<a href="https://shop.example/pills" rel="nofollow ugc">Shop</a>', $html);
	}
	
	public function test_a_link_that_already_has_a_rel_is_marked_too(): void
	{
		// Act: what the HTML cleaner makes of a link that opens in a new tab
		$html = noFollowLinks('<a href="https://shop.example" target="_blank" rel="noreferrer noopener">Shop</a>', self::SITE_HOST);
		
		// Assert
		$this->assertSame('<a href="https://shop.example" target="_blank" rel="noreferrer noopener nofollow ugc">Shop</a>', $html);
	}
	
	public function test_a_nofollow_link_is_not_marked_twice(): void
	{
		// Act
		$html = noFollowLinks('<a rel="nofollow" href="https://shop.example">Shop</a>', self::SITE_HOST);
		
		// Assert
		$this->assertSame('<a rel="nofollow ugc" href="https://shop.example">Shop</a>', $html);
	}
	
	public function test_the_sites_own_links_are_left_alone(): void
	{
		// Arrange
		$own = '<a href="https://www.adniro.test/us/sitemap">Sitemap</a> <a href="/us/contact">Contact</a>';
		
		// Act & Assert
		$this->assertSame($own, noFollowLinks($own, self::SITE_HOST));
	}
	
	public function test_naming_the_site_inside_another_address_does_not_make_a_link_the_sites_own(): void
	{
		// Act
		$html = noFollowLinks('<a href="https://shop.example/?from=www.adniro.test">Shop</a>', self::SITE_HOST);
		
		// Assert
		$this->assertStringContainsString('rel="nofollow ugc"', $html);
	}
	
	public function test_an_address_typed_as_text_becomes_a_marked_link(): void
	{
		// Act
		$html = urlsToLinks('Visit https://shop.example/pills today');
		
		// Assert
		$this->assertStringContainsString('<a rel="nofollow ugc" href="https://shop.example/pills"', $html);
	}
	
	public function test_every_link_in_a_listings_description_is_marked(): void
	{
		// Arrange
		config(['settings.listing_form.wysiwyg_editor' => 'tinymce']);
		$post = (new Post())->forceFill([
			'description' => '<p>Buy at <a href="https://shop.example/a" target="_blank">our shop</a>, '
				. '<a href="https://shop.example/b">here</a> or https://shop.example/c</p>',
		]);
		
		// Act
		$html = $post->description;
		
		// Assert
		$this->assertSame(3, preg_match_all('~<a\b[^>]*>~', $html, $links));
		foreach ($links[0] as $link) {
			$this->assertMatchesRegularExpression('~\brel="[^"]*\bnofollow\b[^"]*\bugc\b[^"]*"~', $link, $link);
		}
	}
}
