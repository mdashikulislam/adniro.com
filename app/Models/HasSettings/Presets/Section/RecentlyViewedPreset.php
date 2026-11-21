<?php

namespace App\Models\HasSettings\Presets\Section;

use App\Helpers\Common\ListingGrid;
use App\Http\Controllers\Web\Front\RecentlyViewedController;
use App\Models\Section\BaseSection;
use Illuminate\Support\Facades\Storage;

/*
 * adniro: the default options of the homepage's "Recently viewed" section: one sideways row of up to
 * 12 listings, 4 per row, with a "Clear" button and the bottom margin the other sections have.
 */

class RecentlyViewedPreset extends BaseSection
{
	public static function defaultPreset(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			'max_items'              => (string)RecentlyViewedController::MAX_ITEMS,
			'items_per_row'          => (string)ListingGrid::HOMEPAGE_DEFAULT_COLUMNS,
			'items_in_carousel'      => '1',
			'show_clear_btn'         => '1',
			'margins'                => self::getDefaultMarginConfiguration(),
			'prevent_header_overlap' => '1',
			'hide_on_mobile'         => '0',
			'animation'              => null,
			'animation_easing'       => null,
			'animation_duration'     => null,
			'animation_delay'        => null,
			'animation_offset'       => null,
			'animation_placement'    => null,
			'animation_once'         => '0',
		];

		return array_merge($value, $defaultValue);
	}
}
