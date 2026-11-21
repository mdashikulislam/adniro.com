<?php

namespace Tests\Unit\Common;

use App\Helpers\Common\ErrorPageMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ErrorPageMessageTest extends TestCase
{
	private const DEFAULT = 'The page does not exist. <a href="/">Home</a>';
	
	public function test_a_message_meant_for_visitors_is_shown_escaped(): void
	{
		// Act
		$html = ErrorPageMessage::forVisitor(new HttpException(403, 'Only the <b>owner</b> can do this.'), self::DEFAULT);
		
		// Assert
		$this->assertSame('Only the &lt;b&gt;owner&lt;/b&gt; can do this.', $html);
	}
	
	public function test_framework_messages_give_the_default_text(): void
	{
		// Arrange
		$routeMissing = new NotFoundHttpException('The route this-page/does-not-exist could not be found.');
		$modelMissing = new NotFoundHttpException('No query results for model [App\Models\Post] 12', new ModelNotFoundException());
		
		// Act & Assert
		$this->assertSame(self::DEFAULT, ErrorPageMessage::forVisitor($routeMissing, self::DEFAULT));
		$this->assertSame(self::DEFAULT, ErrorPageMessage::forVisitor($modelMissing, self::DEFAULT));
		$this->assertSame(self::DEFAULT, ErrorPageMessage::forVisitor(new HttpException(404, ''), self::DEFAULT));
		$this->assertSame(self::DEFAULT, ErrorPageMessage::forVisitor(null, self::DEFAULT));
	}
	
	public function test_server_errors_hide_their_message_unless_debugging(): void
	{
		// Arrange
		$error = new RuntimeException('SQLSTATE[HY000] Access denied for user root');
		
		// Act
		$public = ErrorPageMessage::forServerError($error, self::DEFAULT, false);
		$debug = ErrorPageMessage::forServerError($error, self::DEFAULT, true);
		
		// Assert
		$this->assertSame(self::DEFAULT, $public);
		$this->assertStringContainsString('Access denied', $debug);
		$this->assertStringContainsString('<code>', $debug, 'the file and line for developers');
	}
}
