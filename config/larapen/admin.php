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

use App\Models\User;

return [
	
    /*
     |--------------------------------------------------------------------------
     | ADMIN - Preferences
     |--------------------------------------------------------------------------
     */
    // Project name. Shown in the breadcrumbs and a few other places.
    'project_name' => 'LaraClassifier',
	
	// Logo
	'logo' => [
		'dark'  => 'app/default/backend/logo_dark.png',
		'light' => 'app/default/backend/logo_light.png',
	],
	
    // Logos Text
    'logo_lg'   => '<b>Lara</b>Classified',
    'logo_mini' => '<b>LRC</b>',
	
    // Developer or company name. Shown in footer.
    'developer_name' => 'BeDigit',
	
    // Developer website. Link in footer.
    'developer_link' => 'https://bedigit.com',
	
    // Show powered by Laravel in the footer?
    'show_powered_by' => true,
	
    // The AdminLTE skin. Affects menu color and primary/secondary colors used throughout the application.
	// Options: skin-black, skin-blue, skin-purple, skin-red, skin-yellow, skin-green, skin-blue-light,
	//          skin-black-light, skin-purple-light, skin-green-light, skin-red-light, skin-yellow-light
    'skin' => 'skin-blue',
	
	/*
    |--------------------------------------------------------------------------
    | CREATE & UPDATE
    |--------------------------------------------------------------------------
    */
	// Where do you want to redirect the user by default, after a CRUD entry is saved in the Add or Edit forms?
	// options: save_and_back, save_and_edit, save_and_new
	'default_save_action' => 'save_and_back',
	
	// When using tabbed forms (create & update), what kind of tabs would you like?
	// options: horizontal, vertical
    'tabs_type' => 'horizontal',
	
	// How would you like the validation errors to be shown?
    'show_grouped_errors' => true,
    'show_inline_errors'  => true,
	
	/*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */
	// LIST VIEW (table view)
	
	// enable the datatables-responsive plugin, which hides columns if they don't fit?
	// if not, a horizontal scrollbar will be shown instead
    'responsive_table' => true,
	
	// How many items should be shown by default by the Datatable?
	// This value can be overwritten on a specific CRUD by calling
	// $this->xPanel->setDefaultPageLength(50);
    'default_page_length' => 25,
	
	// A 1D array of options which will be used for both the displayed option and the value, or
	// A 2D array in which the first array is used to define the value options and the second array the displayed options
	// If a 2D array is used, strings in the right hand array will be automatically run through trans()
    'page_length_menu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'admin.all']],
	
	// PREVIEW
	
	/*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */
	
	/*
	|--------------------------------------------------------------------------
	| REORDER
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| DETAILS ROW
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| TRANSLATABLE CRUD
	|--------------------------------------------------------------------------
	*/
	'show_translatable_field_icon'     => true,
	'translatable_field_icon_position' => 'right', // left or right
	
	// Don't allow edits on these language files
	// i.e. Language files to NOT show in the LangFile Manager
    'language_ignore' => ['.DS_Store', 'routes', 'messages'],
	
	/*
    |--------------------------------------------------------------------------
    | Disallow the user interface for creating/updating permissions or roles.
    |--------------------------------------------------------------------------
    | Roles and permissions are used in code by their name
    | - ex: $user->hasPermissionTo('edit articles');
    |
    | So after the developer has entered all permissions and roles, the administrator should either:
    | - not have access to the panels
    | or
    | - creating and updating should be disabled
    */
	'allow_permission_create' => true,
	'allow_permission_update' => true,
	'allow_permission_delete' => true,
	'allow_role_create'       => true,
	'allow_role_update'       => true,
	'allow_role_delete'       => true,
	
    /*
    |--------------------------------------------------------------------------
    | Registration Open
    |--------------------------------------------------------------------------
    |
    | Choose weather new users are allowed to register.
    | This will show up the Register button in the menu and allow access to the
    | Register functions in AuthController.
    */
    'registration_open' => false,
	
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */
    // Fully qualified namespace of the User model
    'user_model_fqn' => User::class,
	
];
