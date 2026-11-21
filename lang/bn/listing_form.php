<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'প্রায় ২ মিনিট লাগবে। * চিহ্নিত ঘরগুলো আবশ্যক।',
	'edit_kicker'        => 'বিজ্ঞাপন সম্পাদনা',
	'view_listing'       => 'বিজ্ঞাপন দেখুন',
	'status_live'        => 'প্রকাশিত',
	'status_pending'     => 'অনুমোদনের অপেক্ষায়',
	'status_review'      => 'যাচাই চলছে',
	'status_rejected'    => 'অনুমোদিত হয়নি',
	'status_archived'    => 'আর্কাইভ করা',

	// Section cards
	'section_photos'          => 'ছবি',
	'section_photos_sub'      => 'পরিষ্কার ছবির বিজ্ঞাপনে অনেক বেশি উত্তর আসে।',
	'section_what'            => 'আপনি কী বিক্রি করছেন?',
	'section_what_sub'        => 'একটি বিভাগ বেছে নিন, তারপর একটি স্পষ্ট শিরোনাম ও বিবরণ দিন।',
	'section_details'         => 'বিস্তারিত',
	'section_details_sub'     => 'এই বিভাগে ক্রেতারা যেসব তথ্য খোঁজেন।',
	'section_price_location'  => 'দাম ও অবস্থান',
	'section_price_location_sub' => 'সঠিক দাম ও সঠিক শহর ক্রেতাদের আপনাকে খুঁজে পেতে সাহায্য করে।',
	'section_more'            => 'আরও অপশন',
	'section_more_sub'        => 'ঐচ্ছিক: ট্যাগ এবং বিজ্ঞাপন কতদিন থাকবে।',
	'section_contact'         => 'ক্রেতারা কীভাবে যোগাযোগ করবেন',
	'section_contact_sub'     => 'ক্রেতারা এই তথ্য দিয়ে আপনার সঙ্গে যোগাযোগ করবেন।',
	'section_finish'          => 'প্রায় শেষ',
	'section_finish_sub'      => 'যাচাই করে নিশ্চিত করুন, তারপর প্রকাশ করুন।',

	// Field hints
	'hint_title'         => 'এটি কী, ব্র্যান্ড ও মডেলসহ লিখুন, যেমন "Samsung Galaxy S21, 128 GB"।',
	'hint_description'   => 'অবস্থা, বয়স, আকার, সঙ্গে কী কী আছে এবং কেন বিক্রি করছেন।',
	'hint_tags'          => 'ক্রেতারা খুঁজতে পারেন এমন সর্বোচ্চ {limit}টি শব্দ।',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'অন্তত {min}টি অক্ষর',

	// Side panel and phone sheet
	'panel_title'        => 'আপনার বিজ্ঞাপন',
	'panel_open'         => 'চেকলিস্ট',
	'panel_close'        => 'বন্ধ করুন',
	'preview'            => 'প্রিভিউ',
	'preview_title'      => 'আপনার শিরোনাম এখানে দেখা যাবে',
	'preview_price'      => 'এখনও দাম নেই',
	'preview_city'       => 'এখনও শহর নেই',
	'preview_category'   => 'এখনও বিভাগ নেই',
	'preview_negotiable' => 'আলোচনা সাপেক্ষে',
	'checklist'          => 'প্রকাশের আগে',
	'checklist_progress' => '{total}টির মধ্যে {done}টি সম্পন্ন',
	'checklist_ready'    => 'প্রকাশের জন্য প্রস্তুত',
	'check_category'     => 'একটি বিভাগ বেছে নিন',
	'check_title'        => 'শিরোনাম যোগ করুন',
	'check_description'  => 'পণ্যের বিবরণ দিন',
	'check_price'        => 'দাম ঠিক করুন',
	'check_city'         => 'একটি শহর বেছে নিন',
	'check_contact'      => 'যোগাযোগের একটি উপায় যোগ করুন',
	'check_done'         => 'সম্পন্ন',
	'check_todo'         => 'বাকি',
	'tips'               => 'দ্রুত বিক্রির টিপস',

	// Checks while filling in
	'error_summary'      => 'এগিয়ে যাওয়ার আগে এগুলো ঠিক করুন:',
	'error_required'     => 'অনুগ্রহ করে এটি পূরণ করুন।',
	'error_category'     => 'অনুগ্রহ করে একটি বিভাগ বেছে নিন।',
	'error_city'         => 'অনুগ্রহ করে একটি শহর বেছে নিন।',
	'error_email'        => 'সঠিক ইমেইল ঠিকানা দিন, যেমন name@example.com।',
	'error_terms'        => 'এগিয়ে যেতে শর্তগুলো মেনে নিন।',
	'error_too_short'    => 'অনুগ্রহ করে অন্তত {min}টি অক্ষর লিখুন।',

	// AI box for guests
	'ai_guest_text'      => 'লগ ইন করুন, AI সংক্ষিপ্ত বিবরণ বা কয়েকটি ছবি থেকে এই ফর্মটি পূরণ করে দিতে পারবে।',
	'ai_guest_login'     => 'লগ ইন করুন',
	'ai_guest_hide'      => 'এই বার্তা লুকান',

	// Multi-step progress on phones
	'step_of'            => 'ধাপ :current / :total',
];
