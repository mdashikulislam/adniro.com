<?php

namespace Tests\Unit\Ai;

use App\Services\ListingAssistant\DraftForForm;
use PHPUnit\Framework\TestCase;

/*
 * The AI description is plain text; the listing form's editor gets it as HTML (adniro:ai).
 */

class DraftDescriptionHtmlTest extends TestCase
{
	public function test_blank_lines_make_paragraphs_and_single_breaks_stay_in_them(): void
	{
		// Act
		$html = DraftForForm::paragraphs("Apple iPhone 17 Pro.\nCosmic Orange.\n\nAppears to be in very good condition.");

		// Assert
		$this->assertSame('<p>Apple iPhone 17 Pro.<br>Cosmic Orange.</p><p>Appears to be in very good condition.</p>', $html);
	}

	public function test_dash_lines_become_a_bulleted_list(): void
	{
		// Arrange
		$text = "Apple iPhone 17 Pro in Cosmic Orange.\n\nKey features:\n- 6.3-inch display\n- Triple rear camera\n\nAppears to be in very good condition.";

		// Act
		$html = DraftForForm::paragraphs($text);

		// Assert
		$this->assertSame(
			'<p>Apple iPhone 17 Pro in Cosmic Orange.</p>'
			. '<p>Key features:</p><ul><li>6.3-inch display</li><li>Triple rear camera</li></ul>'
			. '<p>Appears to be in very good condition.</p>',
			$html
		);
	}

	public function test_bullets_written_with_other_marks_are_lists_too(): void
	{
		// Act
		$html = DraftForForm::paragraphs("• Unlocked\n* Original box");

		// Assert
		$this->assertSame('<ul><li>Unlocked</li><li>Original box</li></ul>', $html);
	}

	public function test_list_items_are_escaped(): void
	{
		// Act
		$html = DraftForForm::paragraphs("- <script>alert(1)</script>");

		// Assert
		$this->assertSame('<ul><li>&lt;script&gt;alert(1)&lt;/script&gt;</li></ul>', $html);
	}

	public function test_a_dash_inside_a_sentence_is_not_a_list(): void
	{
		// Act
		$html = DraftForForm::paragraphs('Fits models 1998-2004 - like new.');

		// Assert
		$this->assertSame('<p>Fits models 1998-2004 - like new.</p>', $html);
	}
}
