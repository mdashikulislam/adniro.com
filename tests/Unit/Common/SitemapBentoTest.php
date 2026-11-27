<?php

namespace Tests\Unit\Common;

use App\Helpers\Common\SitemapBento;
use PHPUnit\Framework\TestCase;

/*
 * The sitemap page's category cards (adniro): a long list gets a wide card instead of a tall one, every row
 * is full, and the cards of a row end up about as tall as each other.
 */

class SitemapBentoTest extends TestCase
{
	/** Sub-categories per category on the live site, in the catalog's order */
	private const LIVE_CATALOG = [7, 4, 15, 6, 8, 7, 7, 10, 23, 16, 7, 12];

	public function test_a_long_list_gets_the_wide_card_of_its_row(): void
	{
		$this->assertSame([1, 1, 2], SitemapBento::spans([7, 4, 15], 4));
		$this->assertSame([3, 1], SitemapBento::spans([23, 4], 4));
	}

	public function test_a_card_alone_takes_the_whole_row(): void
	{
		$this->assertSame([4], SitemapBento::spans([3], 4));
	}

	public function test_every_row_is_full_at_each_width(): void
	{
		foreach ([4, 3, 2, 1] as $columns) {
			$spans = SitemapBento::spans(self::LIVE_CATALOG, $columns);

			$this->assertCount(count(self::LIVE_CATALOG), $spans);
			foreach ($this->rows($spans, $columns) as $row) {
				$this->assertSame($columns, array_sum(array_column($row, 'span')), "{$columns} columns");
			}
		}
	}

	public function test_no_card_of_the_live_catalog_is_taller_than_twelve_lines_on_a_wide_screen(): void
	{
		// Act
		$spans = SitemapBento::spans(self::LIVE_CATALOG, 4);

		// Assert: 23 sub-categories in one column would be 23 lines
		foreach ($spans as $index => $span) {
			$this->assertLessThanOrEqual(12, (int)ceil(self::LIVE_CATALOG[$index] / $span));
		}
	}

	public function test_the_cards_keep_the_catalogs_order(): void
	{
		// One span per card, in the order given: the same cards in another order give another layout
		$this->assertSame([1, 3], SitemapBento::spans([4, 23], 4));
	}

	public function test_no_cards_no_spans(): void
	{
		$this->assertSame([], SitemapBento::spans([], 4));
	}

	/**
	 * @return array<int, array<int, array{span: int}>>
	 */
	private function rows(array $spans, int $columns): array
	{
		$rows = [];
		$row = [];
		foreach ($spans as $span) {
			$row[] = ['span' => $span];
			if (array_sum(array_column($row, 'span')) >= $columns) {
				$rows[] = $row;
				$row = [];
			}
		}
		if ($row !== []) {
			$rows[] = $row;
		}

		return $rows;
	}
}
