<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'Das dauert etwa 2 Minuten. Mit * markierte Felder sind Pflichtfelder.',
	'edit_kicker'        => 'Anzeige bearbeiten',
	'view_listing'       => 'Anzeige ansehen',
	'status_live'        => 'Online',
	'status_pending'     => 'Wartet auf Freigabe',
	'status_review'      => 'Wird geprüft',
	'status_rejected'    => 'Nicht freigegeben',
	'status_archived'    => 'Archiviert',

	// Section cards
	'section_photos'          => 'Fotos',
	'section_photos_sub'      => 'Anzeigen mit klaren Fotos erhalten viel mehr Antworten.',
	'section_what'            => 'Was verkaufen Sie?',
	'section_what_sub'        => 'Wählen Sie eine Kategorie und geben Sie einen klaren Titel und eine Beschreibung ein.',
	'section_details'         => 'Details',
	'section_details_sub'     => 'Angaben, nach denen Käufer in dieser Kategorie suchen.',
	'section_price_location'  => 'Preis und Ort',
	'section_price_location_sub' => 'Ein fairer Preis und die richtige Stadt helfen Käufern, Sie zu finden.',
	'section_more'            => 'Weitere Optionen',
	'section_more_sub'        => 'Optional: Stichwörter und wie lange die Anzeige online bleibt.',
	'section_contact'         => 'So erreichen Käufer Sie',
	'section_contact_sub'     => 'Käufer nutzen diese Angaben, um Sie zu kontaktieren.',
	'section_finish'          => 'Fast geschafft',
	'section_finish_sub'      => 'Prüfen und bestätigen Sie, dann veröffentlichen Sie.',

	// Field hints
	'hint_title'         => 'Sagen Sie, was es ist, mit Marke und Modell, z. B. „Samsung Galaxy S21, 128 GB“.',
	'hint_description'   => 'Zustand, Alter, Größe, was dabei ist und warum Sie verkaufen.',
	'hint_tags'          => 'Bis zu {limit} Wörter, nach denen Käufer suchen könnten.',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'Mindestens {min} Zeichen',

	// Side panel and phone sheet
	'panel_title'        => 'Ihre Anzeige',
	'panel_open'         => 'Checkliste',
	'panel_close'        => 'Schließen',
	'preview'            => 'Vorschau',
	'preview_title'      => 'Hier erscheint Ihr Titel',
	'preview_price'      => 'Noch kein Preis',
	'preview_city'       => 'Noch keine Stadt',
	'preview_category'   => 'Noch keine Kategorie',
	'preview_negotiable' => 'Verhandelbar',
	'checklist'          => 'Vor dem Veröffentlichen',
	'checklist_progress' => '{done} von {total} erledigt',
	'checklist_ready'    => 'Bereit zum Veröffentlichen',
	'check_category'     => 'Kategorie wählen',
	'check_title'        => 'Titel hinzufügen',
	'check_description'  => 'Artikel beschreiben',
	'check_price'        => 'Preis festlegen',
	'check_city'         => 'Stadt wählen',
	'check_contact'      => 'Kontaktmöglichkeit hinzufügen',
	'check_done'         => 'Erledigt',
	'check_todo'         => 'Offen',
	'tips'               => 'Tipps für schnelleren Verkauf',

	// Checks while filling in
	'error_summary'      => 'Bitte korrigieren Sie Folgendes, bevor Sie fortfahren:',
	'error_required'     => 'Bitte füllen Sie dieses Feld aus.',
	'error_category'     => 'Bitte wählen Sie eine Kategorie.',
	'error_city'         => 'Bitte wählen Sie eine Stadt.',
	'error_email'        => 'Geben Sie eine gültige E-Mail-Adresse ein, z. B. name@example.com.',
	'error_terms'        => 'Bitte akzeptieren Sie die Bedingungen, um fortzufahren.',
	'error_too_short'    => 'Bitte schreiben Sie mindestens {min} Zeichen.',

	// AI box for guests
	'ai_guest_text'      => 'Melden Sie sich an, und die KI kann dieses Formular aus einer kurzen Beschreibung oder ein paar Fotos ausfüllen.',
	'ai_guest_login'     => 'Anmelden',
	'ai_guest_hide'      => 'Diese Meldung ausblenden',

	// AI writing help next to Title and Description
	'ai_help' => 'KI-Hilfe',
	'ai_suggest_title' => 'Titel vorschlagen',
	'ai_suggest_description' => 'Beschreibung schreiben',
	'ai_shorten' => 'Kürzen',
	'ai_clearer' => 'Klarer formulieren',
	'ai_working' => 'Wird geschrieben…',
	'ai_suggestion' => 'KI-Vorschlag',
	'ai_check' => 'Prüfen Sie ihn vor der Verwendung: Die KI kennt nur, was Sie geschrieben haben.',
	'ai_use' => 'Übernehmen',
	'ai_again' => 'Erneut versuchen',
	'ai_dismiss' => 'Verwerfen',
	'ai_undo' => 'Rückgängig',
	'ai_used' => 'Durch den KI-Vorschlag ersetzt.',
	'ai_undone' => 'Ihr eigener Text ist wieder da.',
	'ai_need_text' => 'Schreiben Sie zuerst ein paar Wörter und bitten Sie dann die KI um Hilfe.',
	'ai_error' => 'Die KI-Schreibhilfe ist gerade nicht verfügbar. Bitte versuchen Sie es später erneut.',

	// Price hint under Price (statistics from the site's own listings)
	'price_hint' => 'Ähnliche Anzeigen in :place kosten :low – :high (:count Anzeigen).',
	'price_hint_one' => 'Ähnliche Anzeigen in :place kosten etwa :low (:count Anzeigen).',
	'price_hint_parent' => 'Anzeigen in :category in :place kosten :low – :high (:count Anzeigen).',

	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers' => 'Anzeigenhelfer',
	'setting_price_hint_label' => 'Preishinweis',
	'setting_price_hint_hint' => 'Zeigt unter Preis, was ähnliche Anzeigen auf der Website verlangen, basierend auf Ihren eigenen aktiven Anzeigen (ohne KI). Der Preis wird nie automatisch ausgefüllt.',
	'setting_price_hint_min_label' => 'Anzeigen für einen Hinweis',
	'setting_price_hint_min_hint' => 'Bei weniger ähnlichen Anzeigen als diesem Wert wird kein Hinweis angezeigt. Ab 8 ist der Hinweis aussagekräftig.',

	// Multi-step progress on phones
	'step_of'            => 'Schritt :current von :total',
];
