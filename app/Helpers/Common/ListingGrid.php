<?php

namespace App\Helpers\Common;

use Illuminate\Support\Number;

/*
 * adniro: how many listings a grid shows per row, and how many the search page shows per page, from the
 * admin panel. The search page uses Settings > Listings List ("Grid View Columns", "Rows per page"); the
 * homepage's Latest and Premium listings sections each have their own "Items per row".
 */

final class ListingGrid
{
	public const MIN_COLUMNS = 2;
	public const MAX_COLUMNS = 6;
	public const HOMEPAGE_DEFAULT_COLUMNS = 4;
	private const SEARCH_DEFAULT_COLUMNS = 3;
	private const GRID_VIEW = 'grid-view';

	/**
	 * Listings per row on wide screens: the given number, or the search page's "Grid View Columns".
	 */
	public static function columns(int|string|null $custom = null): int
	{
		$columns = self::toInt($custom) ?? self::toInt(config('settings.listings_list.grid_view_cols')) ?? self::SEARCH_DEFAULT_COLUMNS;

		return (int)Number::clamp($columns, min: self::MIN_COLUMNS, max: self::MAX_COLUMNS);
	}

	/**
	 * Bootstrap row classes: the chosen columns on extra-large screens, at most 4 on large, 3 on medium, 2 on phones.
	 */
	public static function rowColsClass(int $columns): string
	{
		return sprintf(
			'row-cols-xl-%d row-cols-lg-%d row-cols-md-%d row-cols-2',
			$columns,
			min($columns, 4),
			min($columns, 3)
		);
	}

	/**
	 * Listings per search page from "Rows per page": full rows in the grid view, one listing per row in the
	 * list and compact views. Null when it is not set, so the Pagination setting decides.
	 */
	public static function searchPerPage(): ?int
	{
		$rows = self::toInt(config('settings.listings_list.rows_per_page'));
		if (is_null($rows)) {
			return null;
		}

		$isGrid = config('settings.listings_list.display_mode', self::GRID_VIEW) === self::GRID_VIEW;

		return $isGrid ? $rows * self::columns() : $rows;
	}

	/** Columns offered in the admin panel, value => label */
	public static function columnOptions(): array
	{
		$numbers = range(self::MIN_COLUMNS, self::MAX_COLUMNS);

		return array_combine($numbers, array_map('strval', $numbers));
	}

	/** The "Items per row" field of the homepage's listings sections (admin panel) */
	public static function sectionField(string $tabName): array
	{
		return [
			'name'        => 'items_per_row',
			'label'       => trans('admin.items_per_row_label'),
			'type'        => 'select2_from_array',
			'options'     => self::columnOptions(),
			'allows_null' => true,
			'default'     => self::HOMEPAGE_DEFAULT_COLUMNS,
			'hint'        => trans('admin.items_per_row_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
	}

	private static function toInt(mixed $value): ?int
	{
		$number = filter_var($value, FILTER_VALIDATE_INT);

		return ($number === false || $number < 1) ? null : $number;
	}
}
