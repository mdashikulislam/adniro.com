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

	// Multi-step progress on phones
	'step_of'            => 'Schritt :current von :total',
];
