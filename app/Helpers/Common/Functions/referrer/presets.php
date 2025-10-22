<?php

$path = 'app/default/presets';

return [
	[
		'image'       => "{$path}/1-default-preset.png",
		'name'        => 'Default Preset',
		'description' => 'Reset homepage sections and the header setting configuration to factory values.',
		'preset'      => [
			'setting' => ['header' => 'defaultPreset'],
			'section' => 'defaultPreset',
		],
	],
	[
		'image'       => "{$path}/2-overlapped-navbar.png",
		'name'        => 'Overlapped Navbar',
		'description' => 'The "Search Form Area" section is reordered as first homepage section. '
			. 'Its "Extended form area" option is enabled to make it as hero section and overlap '
			. 'prevention is disable to allow the navbar to overlaps the section. '
			. 'The navbar background color is set to transparent.',
		'preset'      => [
			'setting' => ['header' => 'overlappedNavbar'],
			'section' => 'overlappedNavbar',
		],
	],
	[
		'image'       => "{$path}/3-boxed-to-full-width-navbar.png",
		'name'        => 'Boxed To Full Width Navbar',
		'description' => 'The "Search Form Area" section is reordered as first homepage section. '
			. 'Its "Extended form area" option is enabled to make it as hero section and overlap '
			. 'prevention is disable to allow the navbar to overlaps the section. '
			. 'Navbar is set to boxed (with dark theme) and the fixed navbar to full width.',
		'preset'      => [
			'setting' => ['header' => 'boxedToFullWidthNavbar'],
			'section' => 'overlappedNavbar',
		],
	],
	[
		'image'       => "{$path}/4-hero-full-height.png",
		'name'        => 'Hero Full Height',
		'description' => 'The "Search Form Area" section is reordered as first homepage section. '
			. 'Its "Extended form area" option is enabled to make it as hero section, '
			. 'overlap prevention is disable to allow the navbar to overlaps the section and the "Full-Height" option is enabled. '
			. 'Animations are applied to the hero section\'s elements and to each the other homepage sections. '
			. 'The navbar background color is set to transparent.',
		'preset'      => [
			'setting' => ['header' => 'overlappedNavbar'],
			'section' => 'fullHeightHero',
		],
	],
	[
		'image'       => "{$path}/5-glass-style-fixed-navbar.png",
		'name'        => 'Glass Style for the Fixed Navbar',
		'description' => 'The "Search Form Area" section is reordered as first homepage section. '
			. 'Its "Extended form area" option is enabled to make it as hero section and overlap '
			. 'prevention is disable to allow the navbar to overlaps the section. '
			. 'The navbar background color is set to transparent. The fixed navbar background color is set to glass style.',
		'preset'      => [
			'setting' => ['header' => 'glassStyleFixedNavbar'],
			'section' => 'overlappedNavbar',
		],
	],
	[
		'image'       => "{$path}/6-no-hero.png",
		'name'        => 'No Hero Section',
		'description' => 'The "Search Form Area" section is reordered as first homepage section. '
			. 'Its "Extended form area" option is disabled to unmake it as hero section and overlap '
			. 'prevention is enabled to prevent the navbar to overlaps the section.',
		'preset'      => [
			'setting' => ['header' => 'borderBottom'],
			'section' => 'noHero',
		],
	],
];
