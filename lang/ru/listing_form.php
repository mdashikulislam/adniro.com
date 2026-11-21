<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'Это займёт около 2 минут. Поля, отмеченные *, обязательны.',
	'edit_kicker'        => 'Редактирование объявления',
	'view_listing'       => 'Смотреть объявление',
	'status_live'        => 'Опубликовано',
	'status_pending'     => 'Ожидает одобрения',
	'status_review'      => 'На проверке',
	'status_rejected'    => 'Не одобрено',
	'status_archived'    => 'В архиве',

	// Section cards
	'section_photos'          => 'Фото',
	'section_photos_sub'      => 'Объявления с чёткими фото получают гораздо больше откликов.',
	'section_what'            => 'Что вы продаёте?',
	'section_what_sub'        => 'Выберите категорию, затем напишите понятный заголовок и описание.',
	'section_details'         => 'Характеристики',
	'section_details_sub'     => 'Сведения, которые покупатели ищут в этой категории.',
	'section_price_location'  => 'Цена и место',
	'section_price_location_sub' => 'Честная цена и правильный город помогут покупателям вас найти.',
	'section_more'            => 'Дополнительно',
	'section_more_sub'        => 'По желанию: теги и срок, сколько объявление будет на сайте.',
	'section_contact'         => 'Как покупатели свяжутся с вами',
	'section_contact_sub'     => 'Покупатели используют эти данные, чтобы связаться с вами.',
	'section_finish'          => 'Почти готово',
	'section_finish_sub'      => 'Проверьте и подтвердите, затем опубликуйте.',

	// Field hints
	'hint_title'         => 'Напишите, что это, с маркой и моделью, например «Samsung Galaxy S21, 128 GB».',
	'hint_description'   => 'Состояние, возраст, размер, что входит в комплект и почему вы продаёте.',
	'hint_tags'          => 'До {limit} слов, по которым покупатели могут искать.',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'Не менее {min} символов',

	// Side panel and phone sheet
	'panel_title'        => 'Ваше объявление',
	'panel_open'         => 'Качество',
	'panel_close'        => 'Закрыть',
	'preview_price'      => 'Цена не указана',
	'preview_city'       => 'Город не указан',
	'preview_category'   => 'Категория не выбрана',
	'preview_negotiable' => 'Договорная',
	'live_title'         => 'Живой предпросмотр для покупателя',
	'live_view'          => 'Вид предпросмотра',
	'live_feed'          => 'Карточка в ленте',
	'live_detail'        => 'Подробный вид',
	'live_title_empty'   => 'Здесь появится заголовок объявления',
	'live_just_now'      => 'Только что',
	'live_photo_next'    => 'Обложка: добавляется на следующем шаге',
	'live_photo_below'   => 'Добавьте фото ниже: первое станет обложкой',
	'live_photo_here'    => 'Добавьте фото: первое станет обложкой',
	'live_description_empty' => 'Здесь появится ваше описание.',
	'check_category'     => 'Категория выбрана',
	'check_title'        => 'Понятный заголовок ({min}–{max} символов)',
	'check_description'  => 'Подробное описание (от {min} символов)',
	'check_price'        => 'Цена указана',
	'check_city'         => 'Город выбран',
	'check_contact'      => 'Контакты указаны',
	'check_photos'       => 'Фото добавлены ({min}+)',
	'check_done'         => 'Готово',
	'check_todo'         => 'Осталось',
	'strength'           => 'Оценка качества объявления',
	'strength_score'     => 'Баллы',
	'strength_weak'      => 'Начало',
	'strength_fair'      => 'Хорошее начало',
	'strength_strong'    => 'Сильное',
	'strength_excellent' => 'Отлично',
	'strength_weak_text' => 'Добавьте основное, чтобы покупатели могли его найти.',
	'strength_fair_text' => 'Ещё несколько деталей — и оно выделится.',
	'strength_strong_text' => 'Почти готово: заполните последние детали.',
	'strength_excellent_text' => 'Готово к публикации.',
	'strength_photos_next' => 'Фото добавляются на следующем шаге.',
	'hint_category_todo' => 'Покупатели ищут по категориям',
	'hint_title_empty'   => 'Бренд, модель и главная деталь',
	'hint_title_short'   => 'Ещё {count} символов для понятного заголовка',
	'hint_title_long'    => 'Длинный: покупатели видят примерно первые {max} символов',
	'hint_title_good'    => 'Хорошая длина',
	'hint_description_empty' => 'Состояние, возраст и что входит в комплект',
	'hint_description_short' => 'Ещё {count} символов для полного описания',
	'hint_description_good' => 'Достаточно подробно',
	'hint_price_todo'    => 'Покупатели фильтруют по цене',
	'hint_price_negotiable' => 'Торг уместен',
	'hint_city_todo'     => 'Покупатели ищут рядом с собой',
	'hint_contact_todo'  => 'Эл. почта или номер телефона',
	'hint_contact_done'  => 'Покупатели смогут с вами связаться',
	'hint_photos_empty'  => 'Первое фото станет обложкой',
	'hint_photos_more'   => 'Ещё {count} для полной галереи',
	'hint_photos_good'   => 'Хорошая галерея',
	'tips'               => 'Советы, как продать быстрее',

	// Checks while filling in
	'error_summary'      => 'Исправьте это, прежде чем продолжить:',
	'error_required'     => 'Заполните это поле.',
	'error_category'     => 'Выберите категорию.',
	'error_city'         => 'Выберите город.',
	'error_email'        => 'Введите правильный адрес электронной почты, например name@example.com.',
	'error_terms'        => 'Примите условия, чтобы продолжить.',
	'error_too_short'    => 'Напишите не менее {min} символов.',

	// AI box for guests
	'ai_guest_text'      => 'Войдите, и ИИ заполнит эту форму по короткому описанию или нескольким фото.',
	'ai_guest_login'     => 'Войти',
	'ai_guest_hide'      => 'Скрыть это сообщение',

	// AI writing help next to Title and Description
	'ai_help' => 'Помощь ИИ',
	'ai_suggest_title' => 'Предложить заголовок',
	'ai_suggest_description' => 'Написать описание',
	'ai_shorten' => 'Сократить',
	'ai_clearer' => 'Сделать понятнее',
	'ai_working' => 'Пишу…',
	'ai_suggestion' => 'Предложение ИИ',
	'ai_check' => 'Проверьте его перед использованием: ИИ знает только то, что вы написали.',
	'ai_use' => 'Использовать',
	'ai_again' => 'Попробовать снова',
	'ai_dismiss' => 'Закрыть',
	'ai_undo' => 'Отменить',
	'ai_used' => 'Заменено предложением ИИ.',
	'ai_undone' => 'Ваш текст возвращён.',
	'ai_need_text' => 'Сначала напишите несколько слов, затем попросите помощи у ИИ.',
	'ai_error' => 'Помощь ИИ в написании текста сейчас недоступна. Пожалуйста, попробуйте позже.',

	// Price hint under Price (statistics from the site's own listings)
	'price_hint' => 'Цены похожих объявлений (:place): :low – :high. Объявлений: :count.',
	'price_hint_one' => 'Цены похожих объявлений (:place): около :low. Объявлений: :count.',
	'price_hint_parent' => 'Цены объявлений в категории «:category» (:place): :low – :high. Объявлений: :count.',

	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers' => 'Помощники для объявлений',
	'setting_price_hint_label' => 'Подсказка по цене',
	'setting_price_hint_hint' => 'Под полем «Цена» показывает, сколько просят за похожие объявления на сайте, по вашим собственным действующим объявлениям (без ИИ). Цену она никогда не заполняет.',
	'setting_price_hint_min_label' => 'Объявлений для подсказки',
	'setting_price_hint_min_hint' => 'Если похожих объявлений меньше этого числа, подсказка не показывается. При 8 и более подсказка надёжна.',

	// Multi-step progress on phones
	'step_of'            => 'Шаг :current из :total',
];
