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
	'panel_open'         => 'মান',
	'panel_close'        => 'বন্ধ করুন',
	'preview_price'      => 'এখনও দাম নেই',
	'preview_city'       => 'এখনও শহর নেই',
	'preview_category'   => 'এখনও বিভাগ নেই',
	'preview_negotiable' => 'আলোচনা সাপেক্ষে',
	'live_title'         => 'ক্রেতার লাইভ প্রিভিউ',
	'live_view'          => 'প্রিভিউর ধরন',
	'live_feed'          => 'ফিড কার্ড',
	'live_detail'        => 'বিস্তারিত ভিউ',
	'live_title_empty'   => 'আপনার বিজ্ঞাপনের শিরোনাম এখানে দেখাবে',
	'live_just_now'      => 'এইমাত্র',
	'live_photo_next'    => 'কভার ছবি: পরের ধাপে যোগ হবে',
	'live_photo_below'   => 'নিচে ছবি যোগ করুন: প্রথমটি কভার হবে',
	'live_description_empty' => 'আপনার বিবরণ এখানে দেখাবে।',
	'check_category'     => 'বিভাগ বেছে নেওয়া হয়েছে',
	'check_title'        => 'স্পষ্ট শিরোনাম ({min}–{max} অক্ষর)',
	'check_description'  => 'বিস্তারিত বিবরণ ({min}+ অক্ষর)',
	'check_price'        => 'দাম দেওয়া হয়েছে',
	'check_city'         => 'শহর বেছে নেওয়া হয়েছে',
	'check_contact'      => 'যোগাযোগের তথ্য যোগ করা হয়েছে',
	'check_done'         => 'সম্পন্ন',
	'check_todo'         => 'বাকি',
	'strength'           => 'বিজ্ঞাপনের মান স্কোর',
	'strength_score'     => 'স্কোর',
	'strength_weak'      => 'শুরু',
	'strength_fair'      => 'ভালো শুরু',
	'strength_strong'    => 'শক্তিশালী',
	'strength_excellent' => 'চমৎকার',
	'strength_weak_text' => 'ক্রেতারা যেন খুঁজে পান, তাই মূল তথ্যগুলো যোগ করুন।',
	'strength_fair_text' => 'আরও কিছু তথ্য দিলে এটি আলাদা হয়ে উঠবে।',
	'strength_strong_text' => 'প্রায় শেষ: শেষ তথ্যগুলো পূরণ করুন।',
	'strength_excellent_text' => 'প্রকাশের জন্য প্রস্তুত।',
	'strength_photos_next' => 'ছবি পরের ধাপে যোগ করা হবে।',
	'hint_category_todo' => 'ক্রেতারা বিভাগ ধরে খোঁজেন',
	'hint_title_empty'   => 'ব্র্যান্ড, মডেল ও মূল তথ্য',
	'hint_title_short'   => 'স্পষ্ট শিরোনামের জন্য আরও {count}টি অক্ষর',
	'hint_title_long'    => 'দীর্ঘ: ক্রেতারা প্রথম প্রায় {max}টি অক্ষর দেখেন',
	'hint_title_good'    => 'উপযুক্ত দৈর্ঘ্য',
	'hint_description_empty' => 'অবস্থা, বয়স এবং সঙ্গে কী আছে',
	'hint_description_short' => 'পূর্ণ বিবরণের জন্য আরও {count}টি অক্ষর',
	'hint_description_good' => 'যথেষ্ট বিস্তারিত',
	'hint_price_todo'    => 'ক্রেতারা দাম অনুযায়ী ফিল্টার করেন',
	'hint_price_negotiable' => 'আলোচনা সাপেক্ষ',
	'hint_city_todo'     => 'ক্রেতারা কাছাকাছি খোঁজেন',
	'hint_contact_todo'  => 'একটি ইমেইল বা ফোন নম্বর',
	'hint_contact_done'  => 'ক্রেতারা আপনার সাথে যোগাযোগ করতে পারবেন',
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

	// AI writing help next to Title and Description
	'ai_help' => 'AI সহায়তা',
	'ai_suggest_title' => 'একটি শিরোনাম প্রস্তাব করুন',
	'ai_suggest_description' => 'একটি বিবরণ লিখুন',
	'ai_shorten' => 'ছোট করুন',
	'ai_clearer' => 'আরও স্পষ্ট করুন',
	'ai_working' => 'লেখা হচ্ছে…',
	'ai_suggestion' => 'AI-এর প্রস্তাব',
	'ai_check' => 'ব্যবহারের আগে দেখে নিন: AI শুধু আপনার লেখাটুকুই জানে।',
	'ai_use' => 'এটি ব্যবহার করুন',
	'ai_again' => 'আবার চেষ্টা করুন',
	'ai_dismiss' => 'বাদ দিন',
	'ai_undo' => 'পূর্বাবস্থায় ফিরুন',
	'ai_used' => 'AI-এর প্রস্তাব দিয়ে বদলে দেওয়া হয়েছে।',
	'ai_undone' => 'আপনার নিজের লেখা ফিরে এসেছে।',
	'ai_need_text' => 'আগে কয়েকটি শব্দ লিখুন, তারপর AI-এর সাহায্য নিন।',
	'ai_error' => 'AI লেখার সহায়তা এখন পাওয়া যাচ্ছে না। অনুগ্রহ করে পরে আবার চেষ্টা করুন।',

	// Price hint under Price (statistics from the site's own listings)
	'price_hint' => ':place-এ অনুরূপ বিজ্ঞাপনের দাম :low – :high (:count টি বিজ্ঞাপন)।',
	'price_hint_one' => ':place-এ অনুরূপ বিজ্ঞাপনের দাম প্রায় :low (:count টি বিজ্ঞাপন)।',
	'price_hint_parent' => ':place-এ :category বিভাগের বিজ্ঞাপনের দাম :low – :high (:count টি বিজ্ঞাপন)।',

	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers' => 'বিজ্ঞাপন সহায়ক',
	'setting_price_hint_label' => 'দামের ইঙ্গিত',
	'setting_price_hint_hint' => 'দামের নিচে দেখান সাইটে অনুরূপ বিজ্ঞাপনে কত দাম চাওয়া হচ্ছে, আপনার নিজস্ব লাইভ বিজ্ঞাপন থেকে (AI ছাড়া)। এটি কখনো দাম নিজে থেকে পূরণ করে না।',
	'setting_price_hint_min_label' => 'ইঙ্গিতের জন্য প্রয়োজনীয় বিজ্ঞাপন',
	'setting_price_hint_min_hint' => 'অনুরূপ বিজ্ঞাপন এর চেয়ে কম হলে কোনো ইঙ্গিত দেখানো হয় না। ৮ বা তার বেশি হলে ইঙ্গিত অর্থবহ থাকে।',

	// Multi-step progress on phones
	'step_of'            => 'ধাপ :current / :total',
];
