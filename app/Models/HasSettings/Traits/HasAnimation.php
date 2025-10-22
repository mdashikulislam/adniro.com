<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Models\HasSettings\Traits;

trait HasAnimation
{
	/**
	 * @param array $value
	 * @param array $attr
	 * @param array $fieldsMapping
	 * @return array
	 */
	public static function buildAnimationHtmlAttributes(array $value, array $attr = [], array $fieldsMapping = []): array
	{
		// Get the animation fields
		$animationField = $fieldsMapping['animation'] ?? 'animation';
		$easingField = $fieldsMapping['easing'] ?? 'animation_easing';
		$durationField = $fieldsMapping['duration'] ?? 'animation_duration';
		$delayField = $fieldsMapping['delay'] ?? 'animation_delay';
		$offsetField = $fieldsMapping['offset'] ?? 'animation_offset';
		$placementField = $fieldsMapping['placement'] ?? 'animation_placement';
		$onceField = $fieldsMapping['once'] ?? 'animation_once';
		
		$animation = $value[$animationField] ?? null;
		if (!empty($animation)) {
			$attr['data-aos'] = $animation;
			
			$easing = $value[$easingField] ?? null;
			if (!empty($easing)) {
				$attr['data-aos-easing'] = $easing;
			}
			
			$duration = $value[$durationField] ?? null;
			if (!empty($duration)) {
				$attr['data-aos-duration'] = $duration;
			}
			
			$delay = $value[$delayField] ?? null;
			if (!empty($delay)) {
				$attr['data-aos-delay'] = $delay;
			}
			
			$offset = $value[$offsetField] ?? null;
			if (!empty($offset)) {
				$attr['data-aos-offset'] = $offset;
			}
			
			$placement = $value[$placementField] ?? null;
			if (!empty($placement)) {
				$attr['data-aos-anchor-placement'] = $placement;
			}
			
			$playOnce = $value[$onceField] ?? '0';
			$once = ($playOnce == '1') ? 'true' : 'false';
			$attr['data-aos-once'] = $once;
		}
		
		return $attr;
	}
	
	/**
	 * @param array $fields
	 * @param array $fieldsMapping
	 * @param string|false|null $fieldsTitle
	 * @param string|null $tab
	 * @param string|null $filterClass
	 * @param string|null $fieldSeparator
	 * @return array
	 */
	protected static function animationFields(
		array             $fields = [],
		array             $fieldsMapping = [],
		string|null|false $fieldsTitle = null,
		?string           $tab = null,
		?string           $filterClass = null,
		?string           $fieldSeparator = null
	): array
	{
		// Get the animation fields
		$fieldsTitle = is_bool($fieldsTitle)
			? $fieldsTitle
			: (!empty($fieldsTitle) ? $fieldsTitle : trans('admin.animation_title'));
		$animationField = $fieldsMapping['animation'] ?? 'animation';
		$easingField = $fieldsMapping['easing'] ?? 'animation_easing';
		$durationField = $fieldsMapping['duration'] ?? 'animation_duration';
		$delayField = $fieldsMapping['delay'] ?? 'animation_delay';
		$offsetField = $fieldsMapping['offset'] ?? 'animation_offset';
		$placementField = $fieldsMapping['placement'] ?? 'animation_placement';
		$onceField = $fieldsMapping['once'] ?? 'animation_once';
		$uniqId = generateRandomString();
		
		$filterClass = !empty($filterClass) ? " {$filterClass}" : '';
		
		if ($fieldSeparator == 'start' || $fieldSeparator == 'both') {
			$fields[] = self::createAnimationFormSeparatorField('start', $uniqId, $tab);
		}
		
		if (!is_bool($fieldsTitle)) {
			$fields[] = [
				'name'    => "animation_title_{$uniqId}",
				'type'    => 'custom_html',
				'value'   => $fieldsTitle,
				'wrapper' => ['class' => "col-md-12{$filterClass}"],
				'tab'     => !empty($tab) ? $tab : null,
			];
		}
		$fields[] = [
			'name'        => $animationField,
			'label'       => trans('admin.animation_label'),
			'type'        => 'select2_from_array',
			'options'     => self::animations(),
			'allows_null' => true,
			'hint'        => trans('admin.animation_hint'),
			'wrapper'     => ['class' => "col-md-6{$filterClass}"],
			'tab'         => !empty($tab) ? $tab : null,
		];
		$fields[] = [
			'name'        => $easingField,
			'label'       => trans('admin.animation_easing_label'),
			'type'        => 'select2_from_array',
			'options'     => self::animationEasingFunctions(),
			'allows_null' => true,
			'hint'        => trans('admin.animation_easing_hint'),
			'wrapper'     => ['class' => "col-md-6{$filterClass}"],
			'tab'         => !empty($tab) ? $tab : null,
		];
		$fields[] = [
			'name'        => $durationField,
			'label'       => trans('admin.animation_duration_label'),
			'type'        => 'select2_from_array',
			'options'     => self::animationTimeRange(),
			'allows_null' => true,
			'hint'        => trans('admin.animation_duration_hint'),
			'wrapper'     => ['class' => "col-md-6{$filterClass}"],
			'tab'         => !empty($tab) ? $tab : null,
		];
		$fields[] = [
			'name'        => $delayField,
			'label'       => trans('admin.animation_delay_label'),
			'type'        => 'select2_from_array',
			'options'     => self::animationTimeRange(),
			'allows_null' => true,
			'hint'        => trans('admin.animation_delay_hint'),
			'wrapper'     => ['class' => "col-md-6{$filterClass}"],
			'tab'         => !empty($tab) ? $tab : null,
		];
		$fields[] = [
			'name'    => $offsetField,
			'label'   => trans('admin.animation_offset_label'),
			'type'    => 'number',
			'hint'    => trans('admin.animation_offset_hint'),
			'wrapper' => ['class' => "col-md-6{$filterClass}"],
			'tab'     => !empty($tab) ? $tab : null,
		];
		$fields[] = [
			'name'        => $placementField,
			'label'       => trans('admin.animation_placement_label'),
			'type'        => 'select2_from_array',
			'options'     => self::animationAnchorPlacements(),
			'allows_null' => true,
			'hint'        => trans('admin.animation_placement_hint'),
			'wrapper'     => ['class' => "col-md-6{$filterClass}"],
			'tab'         => !empty($tab) ? $tab : null,
		];
		$fields[] = [
			'name'    => $onceField,
			'label'   => trans('admin.animation_once_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.animation_once_hint'),
			'wrapper' => ['class' => "col-md-12{$filterClass}"],
			'tab'     => !empty($tab) ? $tab : null,
		];
		
		if ($fieldSeparator == 'end' || $fieldSeparator == 'both') {
			$fields[] = self::createAnimationFormSeparatorField('end', $uniqId, $tab);
		}
		
		return $fields;
	}
	
	/**
	 * Generate HTML separator field for form sections
	 *
	 * Creates a horizontal rule separator to visually divide form sections.
	 *
	 * @param string $separatorName Unique identifier for the separator field
	 * @param string|null $uniqId
	 * @param string|null $tab
	 * @return array Form field configuration for HTML separator
	 */
	private static function createAnimationFormSeparatorField(string $separatorName, ?string $uniqId = null, ?string $tab = null): array
	{
		$suffix = !empty($uniqId) ? "_{$uniqId}" : '';
		
		return [
			'name'  => "animation_separator_{$separatorName}{$suffix}",
			'type'  => 'custom_html',
			'value' => '<hr>',
			'tab'   => !empty($tab) ? $tab : null,
		];
	}
	
	/**
	 * Get animation list
	 *
	 * @return array
	 */
	protected static function animations(): array
	{
		$aos = getCachedReferrerList('aos');
		$values = $aos['animations'] ?? [];
		
		return collect($values)
			->mapWithKeys(fn ($item) => [$item => $item])
			->toArray();
	}
	
	/**
	 * Get animation time range
	 * (Values from 0 to 3000, with step 50ms)
	 *
	 * @return array
	 */
	protected static function animationTimeRange(): array
	{
		$values = range(0, 3000, 50);
		
		return collect($values)
			->mapWithKeys(fn ($item) => [$item => "{$item}ms"])
			->toArray();
	}
	
	/**
	 * Get animation easing function list
	 *
	 * @return array
	 */
	protected static function animationEasingFunctions(): array
	{
		$aos = getCachedReferrerList('aos');
		$values = $aos['easingFunctions'] ?? [];
		
		return collect($values)
			->mapWithKeys(fn ($item) => [$item => $item])
			->toArray();
	}
	
	/**
	 * Get animation anchor placement list
	 *
	 * @return array
	 */
	protected static function animationAnchorPlacements(): array
	{
		$aos = getCachedReferrerList('aos');
		$values = $aos['anchorPlacements'] ?? [];
		
		return collect($values)
			->mapWithKeys(fn ($item) => [$item => $item])
			->toArray();
	}
}
