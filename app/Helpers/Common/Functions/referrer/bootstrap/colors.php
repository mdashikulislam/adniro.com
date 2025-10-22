<?php

return [
	// https://getbootstrap.com/docs/5.3/components/buttons/#variants
	'button' => [
		'primary'   => [
			'class' => 'btn-primary',
			'value' => 'var(--bs-primary)!important',
		],
		'secondary' => [
			'class' => 'btn-secondary',
			'value' => 'var(--bs-secondary)!important',
		],
		'success'   => [
			'class' => 'btn-success',
			'value' => 'var(--bs-success)!important',
		],
		'danger'    => [
			'class' => 'btn-danger',
			'value' => 'var(--bs-danger)!important',
		],
		'warning'   => [
			'class' => 'btn-warning',
			'value' => 'var(--bs-warning)!important',
		],
		'info'      => [
			'class' => 'btn-info',
			'value' => 'var(--bs-info)!important',
		],
		'light'     => [
			'class' => 'btn-light',
			'value' => 'var(--bs-light)!important',
		],
		'dark'      => [
			'class' => 'btn-dark',
			'value' => 'var(--bs-dark)!important',
		],
		'indigo'    => [
			'class' => 'btn-indigo',
			'value' => '#6610f2!important',
		],
		'purple'    => [
			'class' => 'btn-purple',
			'value' => '#6f42c1!important',
		],
		'pink'      => [
			'class' => 'btn-pink',
			'value' => '#d63384!important',
		],
		'highlight' => [
			'class' => 'btn-highlight',
			'value' => '#f6d80f!important',
		],
		/*
		'link'      => [
			'class' => 'btn-link',
			'value' => 'var(--bs-link-color)!important',
		], // transparent
		*/
	],
	
	// https://getbootstrap.com/docs/5.3/components/badge/#background-colors
	'badge'  => [
		'primary'   => [
			'class' => 'text-bg-primary',
			'value' => 'var(--bs-primary)!important',
		],
		'secondary' => [
			'class' => 'text-bg-secondary',
			'value' => 'var(--bs-secondary)!important',
		],
		'success'   => [
			'class' => 'text-bg-success',
			'value' => 'var(--bs-success)!important',
		],
		'danger'    => [
			'class' => 'text-bg-danger',
			'value' => 'var(--bs-danger)!important',
		],
		'warning'   => [
			'class' => 'text-bg-warning',
			'value' => 'var(--bs-warning)!important',
		],
		'info'      => [
			'class' => 'text-bg-info',
			'value' => 'var(--bs-info)!important',
		],
		'light'     => [
			'class' => 'text-bg-light',
			'value' => 'var(--bs-light)!important',
		],
		'dark'      => [
			'class' => 'text-bg-dark',
			'value' => 'var(--bs-dark)!important',
		],
	],
	
	// https://getbootstrap.com/docs/5.3/components/alerts/
	// https://getbootstrap.com/docs/5.3/components/alerts/#link-color
	'alert'  => [
		'primary'   => [
			'class' => 'alert-primary',
			'value' => 'var(--bs-primary-bg-subtle)!important',
		],
		'secondary' => [
			'class' => 'alert-secondary',
			'value' => 'var(--bs-secondary-bg-subtle)!important',
		],
		'success'   => [
			'class' => 'alert-success',
			'value' => 'var(--bs-success-bg-subtle)!important',
		],
		'danger'    => [
			'class' => 'alert-danger',
			'value' => 'var(--bs-danger-bg-subtle)!important',
		],
		'warning'   => [
			'class' => 'alert-warning',
			'value' => 'var(--bs-warning-bg-subtle)!important',
		],
		'info'      => [
			'class' => 'alert-info',
			'value' => 'var(--bs-info-bg-subtle)!important',
		],
		'light'     => [
			'class' => 'alert-light',
			'value' => 'var(--bs-light-bg-subtle)!important',
		],
		'dark'      => [
			'class' => 'alert-dark',
			'value' => 'var(--bs-dark-bg-subtle)!important',
		],
	],
	
	// https://getbootstrap.com/docs/5.3/utilities/background/#background-color
	'bg'     => [
		'primary'          => [
			'class' => 'bg-primary text-white',
			'value' => 'var(--bs-primary)!important',
		],
		'primary-subtle'   => [
			'class' => 'bg-primary-subtle text-primary-emphasis',
			'value' => 'var(--bs-primary-bg-subtle)!important',
		],
		'secondary'        => [
			'class' => 'bg-secondary text-white',
			'value' => 'var(--bs-secondary)!important',
		],
		'secondary-subtle' => [
			'class' => 'bg-secondary-subtle text-secondary-emphasis',
			'value' => 'var(--bs-secondary-bg-subtle)!important',
		],
		'success'          => [
			'class' => 'bg-success text-white',
			'value' => 'var(--bs-success)!important',
		],
		'success-subtle'   => [
			'class' => 'bg-success-subtle text-success-emphasis',
			'value' => 'var(--bs-success-bg-subtle)!important',
		],
		'danger'           => [
			'class' => 'bg-danger text-white',
			'value' => 'var(--bs-danger)!important',
		],
		'danger-subtle'    => [
			'class' => 'bg-danger-subtle text-danger-emphasis',
			'value' => 'var(--bs-danger-bg-subtle)!important',
		],
		'warning'          => [
			'class' => 'bg-warning text-dark',
			'value' => 'var(--bs-warning)!important',
		],
		'warning-subtle'   => [
			'class' => 'bg-warning-subtle text-warning-emphasis',
			'value' => 'var(--bs-warning-bg-subtle)!important',
		],
		'info'             => [
			'class' => 'bg-info text-dark',
			'value' => 'var(--bs-info)!important',
		],
		'info-subtle'      => [
			'class' => 'bg-info-subtle text-dark-emphasis',
			'value' => 'var(--bs-info-bg-subtle)!important',
		],
		'light'            => [
			'class' => 'bg-light text-dark',
			'value' => 'var(--bs-light)!important',
		],
		'light-subtle'     => [
			'class' => 'bg-light-subtle text-light-emphasis',
			'value' => 'var(--bs-light-bg-subtle)!important',
		],
		'dark'             => [
			'class' => 'bg-dark text-white',
			'value' => 'var(--bs-dark)!important',
		],
		'dark-subtle'      => [
			'class' => 'bg-dark-subtle text-dark-emphasis',
			'value' => 'var(--bs-dark-bg-subtle)!important',
		],
		'black'            => [
			'class' => 'bg-black text-white',
			'value' => 'var(--bs-black)!important',
		],
		'white'            => [
			'class' => 'bg-white text-dark',
			'value' => 'var(--bs-white)!important',
		],
		'body'             => [
			'class' => 'bg-body text-body',
			'value' => 'var(--bs-body-bg)!important',
		],
		'body-secondary'   => [
			'class' => 'bg-body-secondary',
			'value' => 'var(--bs-secondary-bg)!important',
		],
		'body-tertiary'    => [
			'class' => 'bg-body-tertiary',
			'value' => 'var(--bs-tertiary-bg)!important',
		],
		'transparent'      => [
			'class' => 'bg-transparent text-body',
			'value' => 'transparent!important',
		],
	],
	
	// https://getbootstrap.com/docs/5.3/utilities/colors/#colors
	'text'   => [
		'primary'            => [
			'class' => 'text-primary',
			'value' => 'var(--bs-primary)!important',
		],
		'primary-emphasis'   => [
			'class' => 'text-primary-emphasis',
			'value' => 'var(--bs-primary-text-emphasis)!important',
		],
		'secondary'          => [
			'class' => 'text-secondary',
			'value' => 'var(--bs-secondary)!important',
		],
		'secondary-emphasis' => [
			'class' => 'text-secondary-emphasis',
			'value' => 'var(--bs-secondary-text-emphasis)!important',
		],
		'success'            => [
			'class' => 'text-success',
			'value' => 'var(--bs-success)!important',
		],
		'success-emphasis'   => [
			'class' => 'text-success-emphasis',
			'value' => 'var(--bs-success-text-emphasis)!important',
		],
		'danger'             => [
			'class' => 'text-danger',
			'value' => 'var(--bs-danger)!important',
		],
		'danger-emphasis'    => [
			'class' => 'text-danger-emphasis',
			'value' => 'var(--bs-danger-text-emphasis)!important',
		],
		'warning'            => [
			'class' => 'text-warning',
			'value' => 'var(--bs-warning)!important',
		],
		'warning-emphasis'   => [
			'class' => 'text-warning-emphasis',
			'value' => 'var(--bs-warning-text-emphasis)!important',
		],
		'info'               => [
			'class' => 'text-info',
			'value' => 'var(--bs-info)!important',
		],
		'info-emphasis'      => [
			'class' => 'text-info-emphasis',
			'value' => 'var(--bs-info-text-emphasis)!important',
		],
		'light'              => [
			'class' => 'text-light',
			'value' => 'var(--bs-light)!important',
		],
		'light-emphasis'     => [
			'class' => 'text-light-emphasis',
			'value' => 'var(--bs-light-text-emphasis)!important',
		],
		'dark'               => [
			'class' => 'text-dark',
			'value' => 'var(--bs-dark)!important',
		],
		'dark-emphasis'      => [
			'class' => 'text-dark-emphasis',
			'value' => 'var(--bs-dark-text-emphasis)!important',
		],
		'black'              => [
			'class' => 'text-black',
			'value' => 'var(--bs-black)!important',
		],
		'white'              => [
			'class' => 'text-white',
			'value' => 'var(--bs-white)!important',
		],
		'black-50'           => [
			'class' => 'text-black-50',
			'value' => 'rgba(var(--bs-black-rgb),.5)!important',
		],
		'white-50'           => [
			'class' => 'text-white-50',
			'value' => 'rgba(var(--bs-white-rgb),.5)!important',
		],
		'body'               => [
			'class' => 'text-body',
			'value' => 'var(--bs-body-color)!important',
		],
		'body-emphasis'      => [
			'class' => 'text-body-emphasis',
			'value' => 'var(--bs-emphasis-color)!important',
		],
		'body-secondary'     => [
			'class' => 'text-body-secondary',
			'value' => 'var(--bs-secondary-color)!important',
		],
		'body-tertiary'      => [
			'class' => 'text-body-tertiary',
			'value' => 'var(--bs-tertiary-color)!important',
		],
	],
	
	// https://getbootstrap.com/docs/5.3/utilities/link/#colored-links
	'link'   => [
		'primary'       => [
			'class' => 'link-primary',
			'value' => 'RGBA(var(--bs-primary-rgb),var(--bs-link-opacity,1))!important',
		],
		'secondary'     => [
			'class' => 'link-secondary',
			'value' => 'RGBA(var(--bs-secondary-rgb),var(--bs-link-opacity,1))!important',
		],
		'success'       => [
			'class' => 'link-success',
			'value' => 'RGBA(var(--bs-success-rgb),var(--bs-link-opacity,1))!important',
		],
		'danger'        => [
			'class' => 'link-danger',
			'value' => 'RGBA(var(--bs-danger-rgb),var(--bs-link-opacity,1))!important',
		],
		'warning'       => [
			'class' => 'link-warning',
			'value' => 'RGBA(var(--bs-warning-rgb),var(--bs-link-opacity,1))!important',
		],
		'info'          => [
			'class' => 'link-info',
			'value' => 'RGBA(var(--bs-info-rgb),var(--bs-link-opacity,1))!important',
		],
		'light'         => [
			'class' => 'link-light',
			'value' => 'RGBA(var(--bs-light-rgb),var(--bs-link-opacity,1))!important',
		],
		'dark'          => [
			'class' => 'link-dark',
			'value' => 'RGBA(var(--bs-dark-rgb),var(--bs-link-opacity,1))!important',
		],
		'body-emphasis' => [
			'class' => 'link-body-emphasis',
			'value' => 'RGBA(var(--bs-emphasis-color-rgb),var(--bs-link-opacity,1))!important',
		],
	],
	
	// https://getbootstrap.com/docs/5.3/utilities/borders/#color
	'border' => [
		'primary'          => [
			'class' => 'border-primary',
			'value' => 'var(--bs-primary)!important',
		],
		'primary-subtle'   => [
			'class' => 'border-primary-subtle',
			'value' => 'var(--bs-primary-border-subtle)!important',
		],
		'secondary'        => [
			'class' => 'border-secondary',
			'value' => 'var(--bs-secondary)!important',
		],
		'secondary-subtle' => [
			'class' => 'border-secondary-subtle',
			'value' => 'var(--bs-secondary-border-subtle)!important',
		],
		'success'          => [
			'class' => 'border-success',
			'value' => 'var(--bs-success)!important',
		],
		'success-subtle'   => [
			'class' => 'border-success-subtle',
			'value' => 'var(--bs-success-border-subtle)!important',
		],
		'danger'           => [
			'class' => 'border-danger',
			'value' => 'var(--bs-danger)!important',
		],
		'danger-subtle'    => [
			'class' => 'border-danger-subtle',
			'value' => 'var(--bs-danger-border-subtle)!important',
		],
		'warning'          => [
			'class' => 'border-warning',
			'value' => 'var(--bs-warning)!important',
		],
		'warning-subtle'   => [
			'class' => 'border-warning-subtle',
			'value' => 'var(--bs-warning-border-subtle)!important',
		],
		'info'             => [
			'class' => 'border-info',
			'value' => 'var(--bs-info)!important',
		],
		'info-subtle'      => [
			'class' => 'border-info-subtle',
			'value' => 'var(--bs-info-border-subtle)!important',
		],
		'light'            => [
			'class' => 'border-light',
			'value' => 'var(--bs-light)!important',
		],
		'light-subtle'     => [
			'class' => 'border-light-subtle',
			'value' => 'var(--bs-light-border-subtle)!important',
		],
		'dark'             => [
			'class' => 'border-dark',
			'value' => 'var(--bs-dark)!important',
		],
		'dark-subtle'      => [
			'class' => 'border-dark-subtle',
			'value' => 'var(--bs-dark-border-subtle)!important',
		],
		'black'            => [
			'class' => 'border-black',
			'value' => 'var(--bs-black)!important',
		],
		'white'            => [
			'class' => 'border-white',
			'value' => 'var(--bs-white)!important',
		],
	],
];
