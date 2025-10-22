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

return [
	/*
	|--------------------------------------------------------------------------
	| Flash Messages Presenter
	|--------------------------------------------------------------------------
	| Supported: 'bsmodal', 'bstoast', 'pnotify' or 'sweetalert2'
	*/
	'default'    => env('FLASH_MESSAGE_PRESENTER', 'pnotify'),
	
	/*
    |--------------------------------------------------------------------------
    | Presenters
    |--------------------------------------------------------------------------
    */
	'presenters' => [
		/*
		 * Bootstrap Modal Component
		 * --------------------------
		 * https://getbootstrap.com/docs/5.3/components/modal/
		 * --------------------------
		 * fade: Enable/disable fade animation
		 * size: Possible values: sm, lg, xl or empty string (for default)
		 * scrollable: Enable/disable scrollable
		 * showFooter: Enable/disable footer
		 * options.backdrop: Includes a modal-backdrop element. boolean or 'static'
		 * options.focus: Puts the focus on the modal when initialized
		 * options.keyboard: Closes the modal when escape key is pressed
		 */
		'bsmodal'     => [
			'fade'            => env('BS_MODAl_ANIMATION', true),
			'size'            => env('FLASH_MESSAGE_BODY_SIZE', 'lg'),
			'scrollable'      => env('BS_MODAl_SCROLLABLE', true),
			'showFooter'      => env('BS_MODAl_SHOW_FOOTER', true),
			'autohide'        => env('BS_MODAl_AUTOHIDE', true),
			'delay'           => env('BS_MODAl_DELAY', 5000),
			'showProgressBar' => env('BS_MODAl_SHOW_PROGRESS_BAR', false),
			'options'         => [
				'backdrop' => env('BS_MODAl_OPTIONS_BACKDROP', true),
				'focus'    => env('BS_MODAl_OPTIONS_FOCUS', true),
				'keyboard' => env('BS_MODAl_OPTIONS_KEYBOARD', true),
			],
		],
		
		/*
		 * Bootstrap Toasts Component
		 * --------------------------
		 * https://getbootstrap.com/docs/5.3/components/toasts/
		 * --------------------------
		 * position: Possible values: topRight, bottomRight, topLeft, bottomLeft, etc.
		 * options.animation: Enable/disable fade animation
		 * options.autohide: Enable/disable auto-hide
		 * options.delay: Auto-hide after 10 seconds
		 */
		'bstoast'     => [
			'position' => env('BS_TOAST_POSITION', 'topRight'),
			'options'  => [
				'animation' => env('BS_TOAST_OPTIONS_ANIMATION', true),
				'autohide'  => env('BS_TOAST_OPTIONS_AUTOHIDE', true),
				'delay'     => env('BS_TOAST_OPTIONS_DELAY', 10000),
			],
		],
		
		/*
		 * PNotify
		 * -------
		 * https://sciactive.com/pnotify/
		 * https://github.com/sciactive/pnotify/blob/master/README.md#options
		 * https://sciactive.com/pnotify/#stacks
		 * -------
		 * styling: 'brighttheme', 'material', or 'custom'
		 * mode: 'no-preference', 'light', or 'dark'
		 * icons: 'brighttheme', 'material', or ...
		 * icon: Set icon to true to use the default icon for the selected style/type, false for no icon, or a string for your own icon class
		 * animation: 'fade' or 'none' (or Animate.css classes)
		 * animateSpeed: 'slow', 'normal', or 'fast'. Respectively, 400ms, 250ms, 100ms
		 * hide: After a delay, close the notice.
		 */
		'pnotify'     => [
			'defaultOptions' => [
				'styling'      => env('PNOTIFY_STYLING', 'custom'),
				'mode'         => env('PNOTIFY_MODE', 'no-preference'),
				'icons'        => env('PNOTIFY_ICONS', 'material'),
				'icon'         => env('PNOTIFY_ICON', false),
				'width'        => env('PNOTIFY_WIDTH', '400px'),
				'animation'    => env('PNOTIFY_ANIMATION', 'fade'),
				'animateSpeed' => env('PNOTIFY_ANIMATE_SPEED', 'normal'),
				'shadow'       => env('PNOTIFY_SHADOW', true),
				'hide'         => env('PNOTIFY_AUTOHIDE', true),
				'delay'        => env('PNOTIFY_DELAY', 10000),
			],
			// topRightStack: for the env() default values
			'defaultStack'   => [
				'dir1'      => env('PNOTIFY_STACK_DIR1', 'down'),
				'dir2'      => env('PNOTIFY_STACK_DIR2', 'left'),
				'firstpos1' => env('PNOTIFY_STACK_FIRSTPOS1', 25),
				'firstpos2' => env('PNOTIFY_STACK_FIRSTPOS2', 25),
				'spacing1'  => env('PNOTIFY_STACK_SPACING1', 10),
				'spacing2'  => env('PNOTIFY_STACK_SPACING2', 25),
				'modal'     => env('PNOTIFY_STACK_MODAL', false),
				'maxOpen'   => env('PNOTIFY_STACK_MAX_OPEN', 'Infinity'),
			],
		],
		
		/*
		 * sweetalert2
		 * -----------
		 * https://sweetalert2.github.io
		 * -----------
		 * toast: Whether or not an alert should be treated as a toast notification... Toasts are NEVER autofocused
		 * position: 'top', 'top-start', 'top-end', 'center', 'center-start', 'center-end', 'bottom', 'bottom-start', or 'bottom-end'
		 * timer: Auto close timer of the popup. Set in ms (milliseconds)
		 * timerProgressBar: If set to true, the timer will have a progress bar at the bottom of a popup
		 * backdrop: Whether SweetAlert2 should show a full screen click-to-dismiss backdrop
		 * showCloseButton: Set to true to show close button in top right corner of the popup
		 * showConfirmButton: If set to false, a "Confirm"-button will not be shown
		 */
		'sweetalert2' => [
			'toast'             => env('SWEET_ALERT_TOAST', true),
			'position'          => env('SWEET_ALERT_POSITION', 'top-end'),
			'timer'             => env('SWEET_ALERT_TIMER', 10000),
			'timerProgressBar'  => env('SWEET_ALERT_TIMER_PROGRESS_BAR', true),
			'width'             => env('SWEET_ALERT_WIDTH', '400px'),
			'padding'           => env('SWEET_ALERT_PADDING', '1rem'),
			'backdrop'          => env('SWEET_ALERT_BACKDROP', true),
			'showCloseButton'   => env('SWEET_ALERT_CLOSE', true),
			'showConfirmButton' => env('SWEET_ALERT_CONFIRM', false),
			'focusConfirm'      => env('SWEET_ALERT_FOCUS_CONFIRM', true),
		],
	],
];
