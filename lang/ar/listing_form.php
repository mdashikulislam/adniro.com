<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'يستغرق الأمر نحو دقيقتين. الحقول المعلَّمة بـ * مطلوبة.',
	'edit_kicker'        => 'تعديل الإعلان',
	'view_listing'       => 'عرض الإعلان',
	'status_live'        => 'منشور',
	'status_pending'     => 'بانتظار الموافقة',
	'status_review'      => 'قيد الفحص',
	'status_rejected'    => 'غير مقبول',
	'status_archived'    => 'مؤرشف',

	// Section cards
	'section_photos'          => 'الصور',
	'section_photos_sub'      => 'الإعلانات ذات الصور الواضحة تحصل على ردود أكثر بكثير.',
	'section_what'            => 'ماذا تبيع؟',
	'section_what_sub'        => 'اختر فئة، ثم اكتب عنوانًا ووصفًا واضحين.',
	'section_details'         => 'التفاصيل',
	'section_details_sub'     => 'معلومات يبحث عنها المشترون في هذه الفئة.',
	'section_price_location'  => 'السعر والموقع',
	'section_price_location_sub' => 'سعر عادل ومدينة صحيحة يساعدان المشترين على الوصول إليك.',
	'section_more'            => 'خيارات إضافية',
	'section_more_sub'        => 'اختياري: الوسوم ومدة بقاء الإعلان منشورًا.',
	'section_contact'         => 'كيف يتواصل معك المشترون',
	'section_contact_sub'     => 'يستخدم المشترون هذه البيانات للتواصل معك.',
	'section_finish'          => 'أوشكت على الانتهاء',
	'section_finish_sub'      => 'راجع وأكّد، ثم انشر.',

	// Field hints
	'hint_title'         => 'اذكر ما هو، مع العلامة التجارية والطراز، مثل «Samsung Galaxy S21, 128 GB».',
	'hint_description'   => 'الحالة والعمر والحجم وما هو مرفق وسبب البيع.',
	'hint_tags'          => 'حتى {limit} كلمات قد يبحث عنها المشترون.',
	'counter'            => '{count} / {max}',
	'counter_min'        => '{min} حرفًا على الأقل',

	// Side panel and phone sheet
	'panel_title'        => 'إعلانك',
	'panel_open'         => 'قائمة المراجعة',
	'panel_close'        => 'إغلاق',
	'preview'            => 'معاينة',
	'preview_title'      => 'سيظهر عنوانك هنا',
	'preview_price'      => 'لا يوجد سعر بعد',
	'preview_city'       => 'لا توجد مدينة بعد',
	'preview_category'   => 'لا توجد فئة بعد',
	'preview_negotiable' => 'قابل للتفاوض',
	'checklist'          => 'قبل النشر',
	'checklist_progress' => 'تم {done} من {total}',
	'checklist_ready'    => 'جاهز للنشر',
	'check_category'     => 'اختر فئة',
	'check_title'        => 'أضف عنوانًا',
	'check_description'  => 'صف المنتج',
	'check_price'        => 'حدّد السعر',
	'check_city'         => 'اختر مدينة',
	'check_contact'      => 'أضف طريقة للتواصل معك',
	'check_done'         => 'مكتمل',
	'check_todo'         => 'غير مكتمل',
	'tips'               => 'نصائح للبيع بسرعة أكبر',

	// Checks while filling in
	'error_summary'      => 'يرجى تصحيح ما يلي قبل المتابعة:',
	'error_required'     => 'يرجى تعبئة هذا الحقل.',
	'error_category'     => 'يرجى اختيار فئة.',
	'error_city'         => 'يرجى اختيار مدينة.',
	'error_email'        => 'أدخل بريدًا إلكترونيًا صالحًا، مثل name@example.com.',
	'error_terms'        => 'يرجى قبول الشروط للمتابعة.',
	'error_too_short'    => 'يرجى كتابة {min} حرفًا على الأقل.',

	// AI box for guests
	'ai_guest_text'      => 'سجّل الدخول ويمكن للذكاء الاصطناعي تعبئة هذا النموذج من وصف قصير أو بضع صور.',
	'ai_guest_login'     => 'تسجيل الدخول',
	'ai_guest_hide'      => 'إخفاء هذه الرسالة',

	// AI writing help next to Title and Description
	'ai_help' => 'مساعدة الذكاء الاصطناعي',
	'ai_suggest_title' => 'اقترح عنوانًا',
	'ai_suggest_description' => 'اكتب وصفًا',
	'ai_shorten' => 'اختصر',
	'ai_clearer' => 'وضّح أكثر',
	'ai_working' => 'جارٍ الكتابة…',
	'ai_suggestion' => 'اقتراح الذكاء الاصطناعي',
	'ai_check' => 'راجعه قبل استخدامه: الذكاء الاصطناعي لا يعرف إلا ما كتبته.',
	'ai_use' => 'استخدم هذا',
	'ai_again' => 'حاول مرة أخرى',
	'ai_dismiss' => 'تجاهل',
	'ai_undo' => 'تراجع',
	'ai_used' => 'تم الاستبدال باقتراح الذكاء الاصطناعي.',
	'ai_undone' => 'عاد نصك الأصلي.',
	'ai_need_text' => 'اكتب بضع كلمات أولًا، ثم اطلب المساعدة من الذكاء الاصطناعي.',
	'ai_error' => 'مساعدة الكتابة بالذكاء الاصطناعي غير متاحة حاليًا. يُرجى المحاولة لاحقًا.',

	// Multi-step progress on phones
	'step_of'            => 'الخطوة :current من :total',
];
