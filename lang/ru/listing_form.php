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
	'panel_open'         => 'Чек-лист',
	'panel_close'        => 'Закрыть',
	'preview'            => 'Предпросмотр',
	'preview_title'      => 'Здесь появится ваш заголовок',
	'preview_price'      => 'Цена не указана',
	'preview_city'       => 'Город не указан',
	'preview_category'   => 'Категория не выбрана',
	'preview_negotiable' => 'Договорная',
	'checklist'          => 'Перед публикацией',
	'checklist_progress' => 'Выполнено {done} из {total}',
	'checklist_ready'    => 'Готово к публикации',
	'check_category'     => 'Выберите категорию',
	'check_title'        => 'Добавьте заголовок',
	'check_description'  => 'Опишите товар',
	'check_price'        => 'Укажите цену',
	'check_city'         => 'Выберите город',
	'check_contact'      => 'Добавьте способ связи',
	'check_done'         => 'Готово',
	'check_todo'         => 'Осталось',
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

	// Multi-step progress on phones
	'step_of'            => 'Шаг :current из :total',
];
