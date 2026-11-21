<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'Te llevará unos 2 minutos. Los campos marcados con * son obligatorios.',
	'edit_kicker'        => 'Editar anuncio',
	'view_listing'       => 'Ver anuncio',
	'status_live'        => 'Publicado',
	'status_pending'     => 'Esperando aprobación',
	'status_review'      => 'En revisión',
	'status_rejected'    => 'No aprobado',
	'status_archived'    => 'Archivado',

	// Section cards
	'section_photos'          => 'Fotos',
	'section_photos_sub'      => 'Los anuncios con fotos claras reciben muchas más respuestas.',
	'section_what'            => '¿Qué vendes?',
	'section_what_sub'        => 'Elige una categoría y escribe un título y una descripción claros.',
	'section_details'         => 'Detalles',
	'section_details_sub'     => 'Datos que los compradores buscan en esta categoría.',
	'section_price_location'  => 'Precio y ubicación',
	'section_price_location_sub' => 'Un precio justo y la ciudad correcta ayudan a los compradores a encontrarte.',
	'section_more'            => 'Más opciones',
	'section_more_sub'        => 'Opcional: etiquetas y cuánto tiempo seguirá publicado el anuncio.',
	'section_contact'         => 'Cómo te contactan los compradores',
	'section_contact_sub'     => 'Los compradores usan estos datos para contactarte.',
	'section_finish'          => 'Casi listo',
	'section_finish_sub'      => 'Revisa y confirma, luego publica.',

	// Field hints
	'hint_title'         => 'Di qué es, con marca y modelo, por ejemplo «Samsung Galaxy S21, 128 GB».',
	'hint_description'   => 'Estado, antigüedad, tamaño, qué incluye y por qué lo vendes.',
	'hint_tags'          => 'Hasta {limit} palabras que los compradores podrían buscar.',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'Al menos {min} caracteres',

	// Side panel and phone sheet
	'panel_title'        => 'Tu anuncio',
	'panel_open'         => 'Lista de control',
	'panel_close'        => 'Cerrar',
	'preview'            => 'Vista previa',
	'preview_title'      => 'Tu título aparecerá aquí',
	'preview_price'      => 'Aún sin precio',
	'preview_city'       => 'Aún sin ciudad',
	'preview_category'   => 'Aún sin categoría',
	'preview_negotiable' => 'Negociable',
	'checklist'          => 'Antes de publicar',
	'checklist_progress' => '{done} de {total} completados',
	'checklist_ready'    => 'Listo para publicar',
	'check_category'     => 'Elige una categoría',
	'check_title'        => 'Añade un título',
	'check_description'  => 'Describe el artículo',
	'check_price'        => 'Pon un precio',
	'check_city'         => 'Elige una ciudad',
	'check_contact'      => 'Añade una forma de contactarte',
	'check_done'         => 'Hecho',
	'check_todo'         => 'Pendiente',
	'tips'               => 'Consejos para vender más rápido',

	// Checks while filling in
	'error_summary'      => 'Corrige esto antes de continuar:',
	'error_required'     => 'Rellena este campo.',
	'error_category'     => 'Elige una categoría.',
	'error_city'         => 'Elige una ciudad.',
	'error_email'        => 'Introduce un correo electrónico válido, como nombre@example.com.',
	'error_terms'        => 'Acepta los términos para continuar.',
	'error_too_short'    => 'Escribe al menos {min} caracteres.',

	// AI box for guests
	'ai_guest_text'      => 'Inicia sesión y la IA puede rellenar este formulario a partir de una descripción corta o unas cuantas fotos.',
	'ai_guest_login'     => 'Iniciar sesión',
	'ai_guest_hide'      => 'Ocultar este mensaje',

	// AI writing help next to Title and Description
	'ai_help' => 'Ayuda de IA',
	'ai_suggest_title' => 'Sugerir un título',
	'ai_suggest_description' => 'Escribir una descripción',
	'ai_shorten' => 'Acortar',
	'ai_clearer' => 'Aclarar',
	'ai_working' => 'Escribiendo…',
	'ai_suggestion' => 'Sugerencia de la IA',
	'ai_check' => 'Revísala antes de usarla: la IA solo sabe lo que tú has escrito.',
	'ai_use' => 'Usar esto',
	'ai_again' => 'Intentar de nuevo',
	'ai_dismiss' => 'Descartar',
	'ai_undo' => 'Deshacer',
	'ai_used' => 'Sustituido por la sugerencia de la IA.',
	'ai_undone' => 'Tu propio texto ha vuelto.',
	'ai_need_text' => 'Escribe primero unas palabras y luego pide ayuda a la IA.',
	'ai_error' => 'La ayuda de escritura con IA no está disponible ahora mismo. Inténtalo de nuevo más tarde.',

	// Price hint under Price (statistics from the site's own listings)
	'price_hint' => 'Los anuncios similares en :place tienen precios de :low – :high (:count anuncios).',
	'price_hint_one' => 'Los anuncios similares en :place tienen un precio de unos :low (:count anuncios).',
	'price_hint_parent' => 'Los anuncios de :category en :place tienen precios de :low – :high (:count anuncios).',

	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers' => 'Ayudas para anuncios',
	'setting_price_hint_label' => 'Sugerencia de precio',
	'setting_price_hint_hint' => 'Bajo Precio, muestra lo que piden los anuncios similares del sitio, a partir de tus propios anuncios activos (sin IA). Nunca rellena el precio.',
	'setting_price_hint_min_label' => 'Anuncios necesarios para mostrar la sugerencia',
	'setting_price_hint_min_hint' => 'No se muestra ninguna sugerencia si hay menos anuncios similares que este número. Con 8 o más, la sugerencia es fiable.',

	// Multi-step progress on phones
	'step_of'            => 'Paso :current de :total',
];
