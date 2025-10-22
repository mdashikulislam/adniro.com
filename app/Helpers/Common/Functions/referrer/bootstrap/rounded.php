<?php

return [
	'properties' => [
		'rounded' => [
			'name'  => 'rounded', // Fallback CRUD field name
			'label' => 'Rounded',
		],
	],
	
	'sides' => [
		'blank'  => 'All 4 sides',
		'top'    => 'Top',
		'end'    => 'End',
		'bottom' => 'Bottom',
		'start'  => 'Start',
	],
	
	'sizes' => [
		'0'      => '0',
		'1'      => '.25rem',  // sm: .25rem
		'2'      => '.375rem', // default: .375rem
		'3'      => '.5rem',   // lg: .5rem
		'4'      => '1rem',    // xl: 1rem
		'5'      => '2rem',    // xxl: 2rem
		'circle' => '50%',     // 50%
		'pill'   => '50rem',   // 50rem
	],
];
