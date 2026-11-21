<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'इसमें लगभग 2 मिनट लगेंगे। * वाले खाने भरना ज़रूरी है।',
	'edit_kicker'        => 'विज्ञापन संपादित करें',
	'view_listing'       => 'विज्ञापन देखें',
	'status_live'        => 'प्रकाशित',
	'status_pending'     => 'मंज़ूरी का इंतज़ार',
	'status_review'      => 'जाँच जारी',
	'status_rejected'    => 'मंज़ूर नहीं हुआ',
	'status_archived'    => 'संग्रहीत',

	// Section cards
	'section_photos'          => 'फ़ोटो',
	'section_photos_sub'      => 'साफ़ फ़ोटो वाले विज्ञापनों को कहीं ज़्यादा जवाब मिलते हैं।',
	'section_what'            => 'आप क्या बेच रहे हैं?',
	'section_what_sub'        => 'एक श्रेणी चुनें, फिर साफ़ शीर्षक और विवरण लिखें।',
	'section_details'         => 'विवरण',
	'section_details_sub'     => 'इस श्रेणी में खरीदार जो जानकारी ढूँढते हैं।',
	'section_price_location'  => 'कीमत और जगह',
	'section_price_location_sub' => 'सही कीमत और सही शहर से खरीदारों को आप आसानी से मिलते हैं।',
	'section_more'            => 'और विकल्प',
	'section_more_sub'        => 'वैकल्पिक: टैग और विज्ञापन कितने समय तक लगा रहेगा।',
	'section_contact'         => 'खरीदार आपसे कैसे संपर्क करें',
	'section_contact_sub'     => 'खरीदार इन जानकारियों से आपसे संपर्क करेंगे।',
	'section_finish'          => 'लगभग पूरा',
	'section_finish_sub'      => 'जाँचें और पुष्टि करें, फिर प्रकाशित करें।',

	// Field hints
	'hint_title'         => 'बताएँ कि यह क्या है, ब्रांड और मॉडल के साथ, जैसे "Samsung Galaxy S21, 128 GB"।',
	'hint_description'   => 'हालत, उम्र, आकार, साथ में क्या मिलेगा और आप क्यों बेच रहे हैं।',
	'hint_tags'          => 'ज़्यादा से ज़्यादा {limit} शब्द, जो खरीदार खोज सकते हैं।',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'कम से कम {min} अक्षर',

	// Side panel and phone sheet
	'panel_title'        => 'आपका विज्ञापन',
	'panel_open'         => 'गुणवत्ता',
	'panel_close'        => 'बंद करें',
	'preview_price'      => 'अभी कीमत नहीं',
	'preview_city'       => 'अभी शहर नहीं',
	'preview_category'   => 'अभी श्रेणी नहीं',
	'preview_negotiable' => 'बातचीत योग्य',
	'live_title'         => 'खरीदार के लिए लाइव प्रीव्यू',
	'live_title_empty'   => 'आपके विज्ञापन का शीर्षक यहाँ दिखेगा',
	'live_just_now'      => 'अभी',
	'live_photo_next'    => 'कवर फ़ोटो: अगले चरण में जोड़ी जाएगी',
	'live_photo_below'   => 'नीचे फ़ोटो जोड़ें: पहली फ़ोटो कवर होगी',
	'live_photo_here'    => 'फ़ोटो जोड़ें: पहली फ़ोटो कवर होगी',
	'check_category'     => 'श्रेणी चुनी गई',
	'check_title'        => 'साफ़ शीर्षक ({min}–{max} अक्षर)',
	'check_description'  => 'विस्तृत विवरण ({min}+ अक्षर)',
	'check_price'        => 'कीमत तय',
	'check_city'         => 'शहर चुना गया',
	'check_contact'      => 'संपर्क विवरण जोड़ा गया',
	'check_photos'       => 'फ़ोटो जोड़ी गईं ({min}+)',
	'check_done'         => 'पूरा',
	'check_todo'         => 'बाकी',
	'strength'           => 'विज्ञापन गुणवत्ता स्कोर',
	'strength_score'     => 'स्कोर',
	'strength_weak'      => 'शुरुआत',
	'strength_fair'      => 'अच्छी शुरुआत',
	'strength_strong'    => 'मज़बूत',
	'strength_excellent' => 'बेहतरीन',
	'strength_weak_text' => 'मुख्य जानकारी जोड़ें ताकि खरीदार इसे ढूंढ सकें।',
	'strength_fair_text' => 'कुछ और जानकारी इसे अलग बनाएगी।',
	'strength_strong_text' => 'लगभग पूरा: आख़िरी जानकारी भरें।',
	'strength_excellent_text' => 'पोस्ट करने के लिए तैयार।',
	'strength_photos_next' => 'फ़ोटो अगले चरण में जोड़ी जाती हैं।',
	'hint_category_todo' => 'खरीदार श्रेणी के अनुसार देखते हैं',
	'hint_title_empty'   => 'ब्रांड, मॉडल और मुख्य बात',
	'hint_title_short'   => 'साफ़ शीर्षक के लिए {count} और अक्षर',
	'hint_title_long'    => 'लंबा: खरीदार लगभग पहले {max} अक्षर देखते हैं',
	'hint_title_good'    => 'सही लंबाई',
	'hint_description_empty' => 'हालत, उम्र और साथ में क्या है',
	'hint_description_short' => 'पूरे विवरण के लिए {count} और अक्षर',
	'hint_description_good' => 'पर्याप्त विस्तृत',
	'hint_price_todo'    => 'खरीदार कीमत से फ़िल्टर करते हैं',
	'hint_price_negotiable' => 'मोलभाव संभव',
	'hint_city_todo'     => 'खरीदार अपने आस-पास खोजते हैं',
	'hint_contact_todo'  => 'ईमेल या फ़ोन नंबर',
	'hint_contact_done'  => 'खरीदार आपसे संपर्क कर सकते हैं',
	'hint_photos_empty'  => 'पहली फ़ोटो कवर होगी',
	'hint_photos_more'   => 'पूरी गैलरी के लिए {count} और',
	'hint_photos_good'   => 'अच्छी गैलरी',
	'tips'               => 'जल्दी बेचने के सुझाव',

	// Checks while filling in
	'error_summary'      => 'आगे बढ़ने से पहले इन्हें ठीक करें:',
	'error_required'     => 'कृपया इसे भरें।',
	'error_category'     => 'कृपया एक श्रेणी चुनें।',
	'error_city'         => 'कृपया एक शहर चुनें।',
	'error_email'        => 'सही ईमेल पता लिखें, जैसे name@example.com।',
	'error_terms'        => 'आगे बढ़ने के लिए शर्तें स्वीकार करें।',
	'error_too_short'    => 'कृपया कम से कम {min} अक्षर लिखें।',

	// AI box for guests
	'ai_guest_text'      => 'लॉग इन करें, फिर AI छोटे विवरण या कुछ फ़ोटो से यह फ़ॉर्म भर सकता है।',
	'ai_guest_login'     => 'लॉग इन करें',
	'ai_guest_hide'      => 'यह संदेश छिपाएँ',

	// AI writing help next to Title and Description
	'ai_help' => 'AI सहायता',
	'ai_suggest_title' => 'शीर्षक सुझाएँ',
	'ai_suggest_description' => 'विवरण लिखें',
	'ai_shorten' => 'छोटा करें',
	'ai_clearer' => 'और साफ़ करें',
	'ai_working' => 'लिखा जा रहा है…',
	'ai_suggestion' => 'AI का सुझाव',
	'ai_check' => 'इस्तेमाल से पहले जाँच लें: AI सिर्फ़ वही जानता है जो आपने लिखा है।',
	'ai_use' => 'इसे इस्तेमाल करें',
	'ai_again' => 'फिर कोशिश करें',
	'ai_dismiss' => 'हटाएँ',
	'ai_undo' => 'पूर्ववत करें',
	'ai_used' => 'AI के सुझाव से बदल दिया गया।',
	'ai_undone' => 'आपका अपना टेक्स्ट वापस आ गया है।',
	'ai_need_text' => 'पहले कुछ शब्द लिखें, फिर AI से मदद माँगें।',
	'ai_error' => 'AI लेखन सहायता अभी उपलब्ध नहीं है। कृपया बाद में फिर कोशिश करें।',

	// Price hint under Price (statistics from the site's own listings)
	'price_hint' => ':place में मिलते-जुलते विज्ञापनों की कीमत :low – :high है (:count विज्ञापन)।',
	'price_hint_one' => ':place में मिलते-जुलते विज्ञापनों की कीमत करीब :low है (:count विज्ञापन)।',
	'price_hint_parent' => ':place में :category के विज्ञापनों की कीमत :low – :high है (:count विज्ञापन)।',

	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers' => 'विज्ञापन सहायक',
	'setting_price_hint_label' => 'कीमत संकेत',
	'setting_price_hint_hint' => 'कीमत के नीचे दिखाएँ कि साइट पर मिलते-जुलते विज्ञापनों में कितनी कीमत माँगी गई है, आपके अपने लाइव विज्ञापनों से (AI के बिना)। यह कीमत अपने-आप कभी नहीं भरता।',
	'setting_price_hint_min_label' => 'संकेत के लिए ज़रूरी विज्ञापन',
	'setting_price_hint_min_hint' => 'मिलते-जुलते विज्ञापन इससे कम हों तो कोई संकेत नहीं दिखता। 8 या ज़्यादा होने पर संकेत भरोसेमंद रहता है।',

	// Multi-step progress on phones
	'step_of'            => 'चरण :current / :total',
];
