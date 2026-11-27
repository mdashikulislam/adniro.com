<?php

namespace App\Helpers\Common;

/*
 * adniro: the widths of the sitemap page's category cards (front/sitemap/index.blade.php).
 *
 * The cards sit on a grid of a few columns, in the catalog's order, and every row is full. A card's links run
 * in as many columns as the card spans, so a long list gets a wide card instead of a tall one. Of all the ways
 * to cut the cards into full rows, this picks the one that makes the page shortest: the tallest card sets its
 * row's height, so cards of a row end up about as tall as each other. A row taller than a screen costs a
 * little extra, and between two equally good pages the one with less empty room inside its cards wins.
 */

final class SitemapBento
{
	/** A card's head (icon, name, count), counted in lines of links */
	private const HEAD_LINES = 3;
	
	/** The gap under a row, in half lines: of two pages as tall as each other, the one with fewer rows wins */
	private const GAP_HALF_LINES = 1;
	
	/** Lines of links a card shows without being taller than a laptop screen; each line more costs half a line */
	private const COMFORTABLE_LINES = 10;
	
	/**
	 * @param array<int, int> $linkCounts How many links each card lists, in the order the cards are shown
	 * @return array<int, int> How many grid columns each card spans
	 */
	public static function spans(array $linkCounts, int $columns): array
	{
		$counts = array_map(fn ($count) => max(0, (int)$count), array_values($linkCounts));
		$columns = max(1, $columns);
		$total = count($counts);
		
		// $best[$i]: the shortest page made of the first $i cards, as [height, empty room, spans]
		$best = [0 => [0, 0, []]];
		
		for ($end = 1; $end <= $total; $end++) {
			$best[$end] = null;
			
			for ($size = 1; $size <= min($columns, $end); $size++) {
				$start = $end - $size;
				$row = self::bestRow(array_slice($counts, $start, $size), $columns);
				$candidate = [$best[$start][0] + $row[0], $best[$start][1] + $row[1], [...$best[$start][2], ...$row[2]]];
				
				if ($best[$end] === null || [$candidate[0], $candidate[1]] < [$best[$end][0], $best[$end][1]]) {
					$best[$end] = $candidate;
				}
			}
		}
		
		return $best[$total][2];
	}
	
	/**
	 * The way to share a row's columns between its cards that makes the row shortest.
	 *
	 * @param array<int, int> $counts
	 * @return array{0: int, 1: int, 2: array<int, int>} [height in half lines, empty room, spans]
	 */
	private static function bestRow(array $counts, int $columns): array
	{
		$best = null;
		
		foreach (self::shares($columns, count($counts)) as $spans) {
			$lines = array_map(fn ($count, $span) => (int)ceil($count / $span), $counts, $spans);
			$tallest = max($lines);
			$emptyRoom = array_sum(array_map(fn ($cardLines, $span) => ($tallest - $cardLines) * $span, $lines, $spans));
			$height = 2 * ($tallest + self::HEAD_LINES) + self::GAP_HALF_LINES + max(0, $tallest - self::COMFORTABLE_LINES);
			$candidate = [$height, $emptyRoom, $spans];
			
			if ($best === null || [$candidate[0], $candidate[1]] < [$best[0], $best[1]]) {
				$best = $candidate;
			}
		}
		
		return $best;
	}
	
	/**
	 * Every way to give $columns columns to $cards cards, each getting at least one.
	 *
	 * @return array<int, array<int, int>>
	 */
	private static function shares(int $columns, int $cards): array
	{
		if ($cards === 1) {
			return [[$columns]];
		}
		
		$shares = [];
		for ($first = 1; $first <= $columns - ($cards - 1); $first++) {
			foreach (self::shares($columns - $first, $cards - 1) as $rest) {
				$shares[] = [$first, ...$rest];
			}
		}
		
		return $shares;
	}
}
