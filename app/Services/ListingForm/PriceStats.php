<?php

namespace App\Services\ListingForm;

/*
 * The usual price range of a set of listing prices (adniro), statistics only.
 * Far-off prices (placeholders like "1", typos with extra zeros) are left out with the
 * 1.5 × IQR rule; the usual range is the middle half (25th to 75th percentile), rounded.
 */

final class PriceStats
{
	private const IQR_FACTOR = 1.5;

	/** Below this share of prices left after trimming, the set is too odd to summarise */
	private const MIN_KEPT_SHARE = 0.75;

	/**
	 * @param array<int|float|string> $prices
	 * @return array{low: float, high: float, median: float, count: int}|null null when fewer than $minCount prices remain
	 */
	public static function summarize(array $prices, int $minCount): ?array
	{
		$prices = array_values(array_filter(array_map('floatval', $prices), fn (float $price) => $price > 0));
		if (count($prices) < max(1, $minCount)) {
			return null;
		}
		sort($prices);

		$q1 = self::percentile($prices, 0.25);
		$q3 = self::percentile($prices, 0.75);
		// The middle half is one value while others differ (placeholders such as "1"): no honest range
		if ($q1 == $q3 && $prices[0] != $prices[count($prices) - 1]) {
			return null;
		}
		$spread = ($q3 - $q1) * self::IQR_FACTOR;
		$kept = array_values(array_filter($prices, fn (float $price) => $price >= $q1 - $spread && $price <= $q3 + $spread));
		// Many identical placeholders ("1") leave no spread and push every real price out
		if (count($kept) < max(1, $minCount) || count($kept) < count($prices) * self::MIN_KEPT_SHARE) {
			return null;
		}

		return [
			'low'    => self::roundNice(self::percentile($kept, 0.25)),
			'high'   => self::roundNice(self::percentile($kept, 0.75)),
			'median' => self::roundNice(self::percentile($kept, 0.5)),
			'count'  => count($kept),
		];
	}

	/**
	 * Linear interpolation between the closest ranks.
	 *
	 * @param float[] $sorted ascending
	 */
	public static function percentile(array $sorted, float $fraction): float
	{
		$position = (count($sorted) - 1) * $fraction;
		$lower = (int)floor($position);
		$upper = (int)ceil($position);

		return $sorted[$lower] + ($sorted[$upper] - $sorted[$lower]) * ($position - $lower);
	}

	/**
	 * Two significant digits, so a range reads like people talk about prices: 12,000 – 18,000,
	 * 47, 7.4, 0.25.
	 */
	public static function roundNice(float $value): float
	{
		if ($value <= 0) {
			return 0.0;
		}
		$step = 10 ** ((int)floor(log10($value)) - 1);

		return round(round($value / $step) * $step, 10);
	}
}
