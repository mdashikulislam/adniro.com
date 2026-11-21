<?php

namespace App\Models\Section\Home;

use App\Helpers\Common\Arr;
use App\Helpers\Common\ListingGrid;
use App\Http\Controllers\Web\Front\RecentlyViewedController;
use App\Models\Section\BaseSection;

/*
 * adniro: the homepage's "Recently viewed" section (the listings the visitor opened, remembered in the
 * browser). Its options, like the latest listings: how many, how many per row, spacing and animation.
 */

class RecentlyViewedSection extends BaseSection
{
	public static function getFieldValues($value, $disk): array
	{
		$value = is_array($value) ? $value : [];
		$value = array_merge(['max_items' => (string)RecentlyViewedController::MAX_ITEMS, 'items_per_row' => (string)ListingGrid::HOMEPAGE_DEFAULT_COLUMNS], $value);

		$value['css_classes'] = self::buildCssClassesAsString($value);
		$value['html_attributes'] = Arr::toAttributes(self::buildAnimationHtmlAttributes($value));

		return $value;
	}

	public static function setFieldValues($value, $setting)
	{
		return $value;
	}

	public static function getFields($diskName): array
	{
		$fields = [];

		$tabName = trans('admin.content_option_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'content_option_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}

		$fields[] = [
			'name'       => 'max_items',
			'label'      => trans('admin.Max Items'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => RecentlyViewedController::MAX_ITEMS,
				'step' => 1,
			],
			'hint'       => trans('admin.recently_viewed_max_items_hint', ['max' => RecentlyViewedController::MAX_ITEMS]),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];

		$fields[] = ListingGrid::sectionField($tabName);

		$fields[] = [
			'name'     => 'active',
			'label'    => trans('admin.Active'),
			'type'     => 'checkbox_switch',
			'fake'     => false,
			'store_in' => null,
			'tab'      => $tabName,
		];

		$tabName = trans('admin.spacing_option_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'spacing_option_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields = self::appendSpacingFormFields($fields, tab: $tabName, fieldSeparator: 'end');

		$fields[] = [
			'name'  => 'hide_on_mobile',
			'label' => trans('admin.hide_on_mobile_label'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.hide_on_mobile_hint'),
			'tab'   => $tabName,
		];

		$tabName = trans('admin.animation_option_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'animation_option_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}

		return self::animationFields(fields: $fields, fieldsTitle: false, tab: $tabName);
	}
}
