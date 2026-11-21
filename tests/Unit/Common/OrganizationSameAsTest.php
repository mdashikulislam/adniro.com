<?php

namespace Tests\Unit\Common;

use App\Services\StructuredDataService;
use Tests\TestCase;

class OrganizationSameAsTest extends TestCase
{
	public function test_the_organization_lists_real_social_profiles_only(): void
	{
		// Arrange: "#" is the default for a profile that was never set
		config([
			'settings.app.name'    => 'Adniro',
			'settings.social_link' => [
				'facebook_page_url' => 'https://www.facebook.com/adniro',
				'twitter_url'       => 'https://x.com/adniro',
				'linkedin_url'      => '#',
				'youtube_url'       => 'javascript:alert(1)',
				'tumblr_url'        => '',
			],
		]);
		
		// Act
		$organization = app(StructuredDataService::class)->organization();
		
		// Assert
		$this->assertSame(['https://www.facebook.com/adniro', 'https://x.com/adniro'], $organization['sameAs']);
	}
	
	public function test_no_same_as_without_profiles(): void
	{
		// Arrange
		config(['settings.app.name' => 'Adniro', 'settings.social_link' => ['twitter_url' => '#']]);
		
		// Act
		$organization = app(StructuredDataService::class)->organization();
		
		// Assert
		$this->assertArrayNotHasKey('sameAs', $organization);
	}
}
