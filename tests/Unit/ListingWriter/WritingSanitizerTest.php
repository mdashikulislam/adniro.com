<?php

namespace Tests\Unit\ListingWriter;

use App\Services\ListingWriter\WritingSanitizer;
use App\Services\ListingWriter\WritingTask;
use Tests\TestCase;

class WritingSanitizerTest extends TestCase
{
	private WritingSanitizer $sanitizer;

	protected function setUp(): void
	{
		parent::setUp();
		config([
			'settings.listing_form.title_max_length'       => 40,
			'settings.listing_form.description_max_length' => 120,
		]);
		$this->sanitizer = new WritingSanitizer();
	}

	public function test_a_title_is_one_plain_line_without_quotes(): void
	{
		// Act
		$title = $this->sanitizer->sanitize('title', "  \"<b>Dell XPS 13</b>\n laptop, 16 GB\"  ");

		// Assert
		$this->assertSame('Dell XPS 13 laptop, 16 GB', $title);
	}

	public function test_a_title_is_cut_to_the_site_limit(): void
	{
		// Act
		$title = $this->sanitizer->sanitize('title', str_repeat('Long title ', 10));

		// Assert
		$this->assertSame(40, mb_strlen($title));
	}

	public function test_contact_details_and_links_are_removed(): void
	{
		// Act
		$text = $this->sanitizer->sanitize('description', "Great laptop.\nCall +1 555 123 4567 or mail me@example.test, see https://example.test/x");

		// Assert
		$this->assertStringNotContainsString('555', $text);
		$this->assertStringNotContainsString('@', $text);
		$this->assertStringNotContainsString('https://', $text);
		$this->assertStringStartsWith("Great laptop.\n", $text);
	}

	public function test_a_description_keeps_its_paragraphs_and_the_site_limit(): void
	{
		// Act
		$text = $this->sanitizer->sanitize('description', "First paragraph.\n\n\n\nSecond paragraph. " . str_repeat('x', 200));

		// Assert
		$this->assertStringStartsWith("First paragraph.\n\nSecond paragraph.", $text);
		$this->assertSame(120, mb_strlen($text));
	}

	public function test_an_answer_with_nothing_left_is_empty(): void
	{
		// Act
		$text = $this->sanitizer->sanitize('title', ' <p> </p> https://example.test ');

		// Assert
		$this->assertSame('', $text);
	}

	public function test_a_task_reads_the_description_as_plain_text(): void
	{
		// Act
		$task = WritingTask::make('description', 'clearer', ' Dell XPS ', '<p>Fast &amp; light.</p><p>Comes with<br>charger.</p>', 'Laptops');

		// Assert
		$this->assertSame('Dell XPS', $task->title);
		$this->assertSame("Fast & light.\nComes with\ncharger.", $task->description);
		$this->assertSame($task->description, $task->source());
	}

	public function test_a_suggested_title_works_from_the_description(): void
	{
		// Act
		$task = WritingTask::make('title', 'suggest', '', 'A blue bike in good shape', null);

		// Assert
		$this->assertTrue($task->hasEnoughInput(10));
		$this->assertFalse(WritingTask::make('title', 'shorten', 'Bike', 'A blue bike in good shape', null)->hasEnoughInput(10));
	}

	public function test_an_unknown_field_or_action_is_refused(): void
	{
		// Assert
		$this->expectException(\InvalidArgumentException::class);

		// Act
		WritingTask::make('price', 'suggest', 'x', 'y', null);
	}
}
