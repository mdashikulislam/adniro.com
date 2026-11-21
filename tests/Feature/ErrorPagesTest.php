<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/*
 * Branded error pages that never show framework text or server error details (adniro).
 */

class ErrorPagesTest extends TestCase
{
	use RefreshDatabase;
	
	public function test_the_not_found_page_offers_search_and_hides_the_framework_message(): void
	{
		// Act
		$html = view('errors.404', ['exception' => new NotFoundHttpException('The route secret-path could not be found.')])->render();
		
		// Assert
		$this->assertStringNotContainsString('could not be found', $html);
		$this->assertStringNotContainsString('secret-path', $html);
		$this->assertStringContainsString('role="search"', $html);
		$this->assertStringContainsString('<label for="errorSearch"', $html);
		$this->assertStringContainsString('<header', $html);
		$this->assertStringContainsString('<footer', $html);
	}
	
	public function test_a_server_error_hides_its_details_from_visitors(): void
	{
		// Arrange
		config(['app.debug' => false]);
		
		// Act
		$html = view('errors.500', ['exception' => new RuntimeException('SQLSTATE[HY000] Access denied for user root')])->render();
		
		// Assert
		$this->assertStringNotContainsString('Access denied', $html);
		$this->assertStringNotContainsString('role="search"', $html, 'no search on a server error');
		$this->assertMatchesRegularExpression('/aria-hidden="true">\s*500\s*</', $html);
	}
	
	public function test_a_message_given_on_purpose_is_shown_escaped(): void
	{
		// Act
		$html = view('errors.403', ['exception' => new HttpException(403, 'Only the <script>owner</script> can do this.')])->render();
		
		// Assert
		$this->assertStringContainsString('Only the &lt;script&gt;owner&lt;/script&gt; can do this.', $html);
		$this->assertMatchesRegularExpression('/aria-hidden="true">\s*403\s*</', $html);
	}
	
	public function test_a_missing_page_answers_404_with_the_branded_page(): void
	{
		// Act
		$response = $this->get('/this-page-does-not-exist-' . uniqid());
		
		// Assert
		$response->assertNotFound()->assertDontSee('could not be found');
	}
}
