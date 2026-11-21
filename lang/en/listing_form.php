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
	'panel_open'         => 'Checklist',
	'panel_close'        => 'Close',
	'preview'            => 'Preview',
	'preview_title'      => 'Your title appears here',
	'preview_price'      => 'No price yet',
	'preview_city'       => 'No city yet',
	'preview_category'   => 'No category yet',
	'preview_negotiable' => 'Negotiable',
	'checklist'          => 'Before you post',
	'checklist_progress' => '{done} of {total} done',
	'checklist_ready'    => 'Ready to post',
	'check_category'     => 'Pick a category',
	'check_title'        => 'Add a title',
	'check_description'  => 'Describe the item',
	'check_price'        => 'Set a price',
	'check_city'         => 'Choose a city',
	'check_contact'      => 'Add a way to reach you',
	'check_done'         => 'Done',
	'check_todo'         => 'To do',
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

	// Multi-step progress on phones
	'step_of'            => 'Step :current of :total',
];
