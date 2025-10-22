<?php

/*
 * Spacing: https://getbootstrap.com/docs/5.3/utilities/spacing/
 * Breakpoints: https://getbootstrap.com/docs/5.3/layout/breakpoints/
 */
return [
	'properties' => [
		'm' => [
			'name'  => 'margins', // Fallback CRUD field name
			'label' => 'Margin',
		],
		'p' => [
			'name'  => 'paddings', // Fallback CRUD field name
			'label' => 'Padding',
		],
	],
	
	'sides' => [
		't'     => 'Top',
		'b'     => 'Bottom',
		's'     => 'Start (Left in LTR & Right in LTR)',
		'e'     => 'End (Right in LTR & Left in RTR)',
		'x'     => 'Left & Right',
		'y'     => 'Top & Bottom',
		'blank' => 'All 4 sides',
	],
	
	'breakpoints' => [
		'xs'  => 'Extra small screens',
		'sm'  => 'Small screens',
		'md'  => 'Medium screens',
		'lg'  => 'Large screens',
		'xl'  => 'Extra large screens',
		'xxl' => 'Extra extra large screens',
	],
	
	'sizes' => [
		'0' => 'Eliminate spacing',
		'1' => 'Spacer * .25',
		'2' => 'Spacer * .50',
		'3' => 'Spacer',
		'4' => 'Spacer * 1.5',
		'5' => 'Spacer * 3',
		// 'auto' => 'Margin (only) auto',
	],
];
