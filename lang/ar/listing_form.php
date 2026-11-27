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
	'section_details'         => 'تفاصيل إضافية',
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
	'panel_open'         => 'الجودة',
	'panel_close'        => 'إغلاق',
	'preview_price'      => 'لا يوجد سعر بعد',
	'preview_city'       => 'لا توجد مدينة بعد',
	'preview_category'   => 'لا توجد فئة بعد',
	'preview_negotiable' => 'قابل للتفاوض',
	'live_title'         => 'معاينة مباشرة للمشتري',
	'live_title_empty'   => 'يظهر عنوان إعلانك هنا',
	'live_just_now'      => 'الآن',
	'live_photo_next'    => 'صورة الغلاف: تُضاف في الخطوة التالية',
	'live_photo_below'   => 'أضف الصور أدناه: الأولى هي صورة الغلاف',
	'live_photo_here'    => 'أضف الصور: الأولى هي صورة الغلاف',
	'manage_title'       => 'إدارة إعلانك',
	'manage_view_hint'   => 'شاهده كما يراه المشترون',
	'manage_photos_hint' => 'الصور: :count · إضافة أو حذف أو ترتيب',
	'manage_premium_hint' => 'روّج له بباقة',
	'check_category'     => 'تم اختيار الفئة',
	'check_title'        => 'عنوان واضح ({min}–{max} حرفًا)',
	'check_description'  => 'وصف مفصل ({min}+ حرفًا)',
	'check_price'        => 'تم تحديد السعر',
	'check_city'         => 'تم اختيار المدينة',
	'check_contact'      => 'تمت إضافة بيانات التواصل',
	'check_photos'       => 'تمت إضافة الصور ({min}+)',
	'check_done'         => 'مكتمل',
	'check_todo'         => 'غير مكتمل',
	'strength'           => 'درجة جودة الإعلان',
	'strength_score'     => 'النتيجة',
	'strength_weak'      => 'البداية',
	'strength_fair'      => 'بداية جيدة',
	'strength_strong'    => 'قوي',
	'strength_excellent' => 'ممتاز',
	'strength_weak_text' => 'أضف الأساسيات ليجده المشترون.',
	'strength_fair_text' => 'بعض التفاصيل الإضافية ستجعله مميزًا.',
	'strength_strong_text' => 'أوشكت على الانتهاء: أكمل التفاصيل الأخيرة.',
	'strength_excellent_text' => 'جاهز للنشر.',
	'strength_photos_next' => 'تُضاف الصور في الخطوة التالية.',
	'hint_category_todo' => 'يتصفح المشترون حسب الفئة',
	'hint_title_empty'   => 'العلامة التجارية والطراز وأهم تفصيل',
	'hint_title_short'   => '{count} حرفًا إضافيًا لعنوان واضح',
	'hint_title_long'    => 'طويل: يرى المشترون نحو أول {max} حرفًا',
	'hint_title_good'    => 'طول مناسب',
	'hint_description_empty' => 'الحالة والعمر وما هو مرفق',
	'hint_description_short' => '{count} حرفًا إضافيًا لوصف كامل',
	'hint_description_good' => 'مفصل بما يكفي',
	'hint_price_todo'    => 'يفلتر المشترون حسب السعر',
	'hint_price_negotiable' => 'قابل للتفاوض',
	'hint_city_todo'     => 'يبحث المشترون بالقرب منهم',
	'hint_contact_todo'  => 'بريد إلكتروني أو رقم هاتف',
	'hint_contact_done'  => 'يمكن للمشترين التواصل معك',
	'hint_photos_empty'  => 'الصورة الأولى هي الغلاف',
	'hint_photos_more'   => '{count} صورة إضافية لمعرض كامل',
	'hint_photos_good'   => 'معرض جيد',
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

	// Price hint under Price (statistics from the site's own listings)
	'price_hint' => 'إعلانات مشابهة في :place مسعّرة بين :low – :high (عدد الإعلانات: :count).',
	'price_hint_one' => 'إعلانات مشابهة في :place مسعّرة بنحو :low (عدد الإعلانات: :count).',
	'price_hint_parent' => 'إعلانات :category في :place مسعّرة بين :low – :high (عدد الإعلانات: :count).',

	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers' => 'مساعدات الإعلان',
	'setting_price_hint_label' => 'تلميح السعر',
	'setting_price_hint_hint' => 'أسفل السعر، اعرض الأسعار المطلوبة في الإعلانات المشابهة على الموقع، اعتمادًا على إعلاناتك المنشورة (دون ذكاء اصطناعي). لا يملأ السعر أبدًا.',
	'setting_price_hint_min_label' => 'الإعلانات المطلوبة لعرض التلميح',
	'setting_price_hint_min_hint' => 'لا يظهر التلميح إذا كان عدد الإعلانات المشابهة أقل من هذا الرقم. يبقى التلميح ذا معنى عند 8 أو أكثر.',

	// Multi-step progress on phones
	'step_of'            => 'الخطوة :current من :total',
];
