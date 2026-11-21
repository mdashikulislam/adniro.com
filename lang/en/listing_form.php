<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'It takes about 2 minutes. Fields marked * are required.',
	'edit_kicker'        => 'Edit listing',
	'view_listing'       => 'View listing',
	'status_live'        => 'Live',
	'status_pending'     => 'Waiting for approval',
	'status_review'      => 'Being checked',
	'status_rejected'    => 'Not approved',
	'status_archived'    => 'Archived',

	// Section cards
	'section_photos'          => 'Photos',
	'section_photos_sub'      => 'Listings with clear photos get far more replies.',
	'section_what'            => 'What are you selling?',
	'section_what_sub'        => 'Pick a category, then give it a clear title and description.',
	'section_details'         => 'Details',
	'section_details_sub'     => 'Facts buyers look for in this category.',
	'section_price_location'  => 'Price and location',
	'section_price_location_sub' => 'A fair price and the right city help buyers find you.',
	'section_more'            => 'More options',
	'section_more_sub'        => 'Optional: tags and how long the listing stays up.',
	'section_contact'         => 'How buyers reach you',
	'section_contact_sub'     => 'Buyers use these details to contact you.',
	'section_finish'          => 'Almost done',
	'section_finish_sub'      => 'Check and confirm, then post.',

	// Field hints
	'hint_title'         => 'Say what it is, with brand and model, e.g. "Samsung Galaxy S21, 128 GB".',
	'hint_description'   => 'Condition, age, size, what is included and why you are selling.',
	'hint_tags'          => 'Up to {limit} words buyers might search for.',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'At least {min} characters',

	// Side panel and phone sheet
	'panel_title'        => 'Your listing',
	'panel_open'         => 'Strength',
	'panel_close'        => 'Close',
	'preview'            => 'Preview',
	'preview_title'      => 'Your title appears here',
	'preview_price'      => 'No price yet',
	'preview_city'       => 'No city yet',
	'preview_category'   => 'No category yet',
	'preview_negotiable' => 'Negotiable',
	'check_category'     => 'Category',
	'check_title'        => 'Title',
	'check_description'  => 'Description',
	'check_price'        => 'Price',
	'check_city'         => 'City',
	'check_contact'      => 'Contact details',
	'check_done'         => 'Done',
	'check_todo'         => 'To do',
	'strength'           => 'Listing strength',
	'strength_weak'      => 'Needs work',
	'strength_fair'      => 'Good start',
	'strength_strong'    => 'Strong',
	'strength_excellent' => 'Excellent',
	'strength_weak_text' => 'Add the basics so buyers can find it.',
	'strength_fair_text' => 'A few more details will make it stand out.',
	'strength_strong_text' => 'Almost there: finish the last details.',
	'strength_excellent_text' => 'Ready to post.',
	'strength_next'      => 'Next step',
	'strength_photos_next' => 'Photos are added on the next step.',
	'hint_category_todo' => 'Buyers browse by category',
	'hint_title_empty'   => 'Brand, model and the key detail',
	'hint_title_short'   => '{count} more characters for a clear title',
	'hint_title_long'    => 'Long: buyers see about the first {max} characters',
	'hint_title_good'    => 'Good length',
	'hint_description_empty' => 'Condition, age and what is included',
	'hint_description_short' => '{count} more characters for a full description',
	'hint_description_good' => 'Detailed enough',
	'hint_price_todo'    => 'Buyers filter by price',
	'hint_price_negotiable' => 'Negotiable',
	'hint_city_todo'     => 'Buyers search near them',
	'hint_contact_todo'  => 'An email or phone number',
	'hint_contact_done'  => 'Buyers can reach you',
	'next_category'      => 'Pick a category',
	'next_title'         => 'Write a clear title of {min}+ characters',
	'next_description'   => 'Describe it in {min}+ characters',
	'next_price'         => 'Set a price',
	'next_city'          => 'Choose your city',
	'next_contact'       => 'Add an email or phone number',
	'tips'               => 'Tips to sell faster',

	// Checks while filling in
	'error_summary'      => 'Please fix these before you continue:',
	'error_required'     => 'Please fill this in.',
	'error_category'     => 'Please pick a category.',
	'error_city'         => 'Please choose a city.',
	'error_email'        => 'Enter a valid email address, like name@example.com.',
	'error_terms'        => 'Please accept the terms to continue.',
	'error_too_short'    => 'Please write at least {min} characters.',

	// AI box for guests
	'ai_guest_text'      => 'Log in and AI can fill this form from a short description or a few photos.',
	'ai_guest_login'     => 'Log in',
	'ai_guest_hide'      => 'Hide this message',

	// AI writing help next to Title and Description
	'ai_help'            => 'AI help',
	'ai_suggest_title'   => 'Suggest a title',
	'ai_suggest_description' => 'Write a description',
	'ai_shorten'         => 'Shorten',
	'ai_clearer'         => 'Make clearer',
	'ai_working'         => 'Writing…',
	'ai_suggestion'      => 'AI suggestion',
	'ai_check'           => 'Check it before you use it: the AI only knows what you wrote.',
	'ai_use'             => 'Use this',
	'ai_again'           => 'Try again',
	'ai_dismiss'         => 'Dismiss',
	'ai_undo'            => 'Undo',
	'ai_used'            => 'Replaced with the AI suggestion.',
	'ai_undone'          => 'Your own text is back.',
	'ai_need_text'       => 'Write a few words first, then ask the AI to help.',
	'ai_error'           => 'AI writing help is not available right now. Please try again later.',
	
	// Price hint under Price (statistics from the site's own listings)
	'price_hint'         => 'Similar listings in :place are priced :low – :high (:count listings).',
	'price_hint_one'     => 'Similar listings in :place are priced about :low (:count listings).',
	'price_hint_parent'  => 'Listings in :category in :place are priced :low – :high (:count listings).',
	
	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers'          => 'Listing helpers',
	'setting_price_hint_label'     => 'Price hint',
	'setting_price_hint_hint'      => 'Under Price, show what similar listings on the site ask, from your own live listings (no AI). It never fills in the price.',
	'setting_price_hint_min_label' => 'Listings needed for a hint',
	'setting_price_hint_min_hint'  => 'No hint is shown with fewer similar listings than this. 8 or more keeps it meaningful.',
	
	// Multi-step progress on phones
	'step_of'            => 'Step :current of :total',
];
