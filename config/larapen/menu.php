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
	
	// Menu Locations
	'locations' => [
		'header' => 'Header Navigation',
		'footer' => 'Footer Navigation',
	],
	
	'views'         => [
		'default' => 'components.menus.default',
		'header'  => 'components.menus.header',
		'footer'  => 'components.menus.footer',
		'sidebar' => 'components.menus.sidebar',
	],
	
	// Menu Item Types
	'menuItemTypes' => [
		'link'    => 'Link',
		'button'  => 'Button',
		'divider' => 'Divider',
		'title'   => 'Title/Text',
	],
	
	// Link/URL Types
	'linkTypes'     => [
		'route'    => 'Route',
		'internal' => 'Internal',
		'external' => 'External',
	],
	
	// Menus Allowed Routes
	'allowedRoutes' => [
		'homepage' => 'Homepage',
		
		'auth.login.showForm'           => 'Login Page',
		'auth.register.showForm'        => 'Register Page',
		'auth.forgot.password.showForm' => 'Forgot Password',
		'auth.logout'                   => 'Logout',
		'impersonate.leave'             => 'Leave Impersonating',
		
		'country.list'               => 'Country List Page',
		'listing.create.ss.showForm' => 'Create Listing Page (Single-Step Form)',
		'listing.create.ms.showForm' => 'Create Listing Page (Multi-Steps Form)',
		'pricing'                    => 'Pricing Page',
		'contact.showForm'           => 'Contact Page',
		'sitemap'                    => 'Sitemap Page',
		'browse.listings'            => 'Listings Page',
		
		'account.overview'                  => 'Account: User Overview Page',
		'account.profile'                   => 'Account: User Profile Page',
		'account.security'                  => 'Account: Security Page',
		'account.preferences'               => 'Account: Preferences Page',
		'account.linkedAccounts'            => 'Account: Linked Accounts Page',
		'account.closing.showForm'          => 'Account: Closing Account Page',
		'account.subscription.showForm'     => 'Account: User Subscription Form',
		'account.transactions.promotion'    => 'Account: Promotion Transactions Page',
		'account.transactions.subscription' => 'Account: Subscription Transactions Page',
		'account.listings.online'           => 'Account: Online Listings Page',
		'account.listings.archived'         => 'Account: Archived Listings Page',
		'account.listings.pendingApproval'  => 'Account: Pending Listings Page',
		'account.savedListings'             => 'Account: Saved Listings Page',
		'account.savedSearches'             => 'Account: Saved Searches Page',
		'account.messages'                  => 'Account: Messages Page',
		
		'xml.sitemaps.all' => 'XML Sitemap Page',
		
		'admin.panel'     => 'Admin Panel: / (Redirect to Dashboard)',
		'admin.dashboard' => 'Admin Panel: Dashboard',
	],
];
