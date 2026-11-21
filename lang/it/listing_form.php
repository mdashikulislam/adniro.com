<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'Ci vogliono circa 2 minuti. I campi contrassegnati con * sono obbligatori.',
	'edit_kicker'        => 'Modifica annuncio',
	'view_listing'       => 'Vedi annuncio',
	'status_live'        => 'Online',
	'status_pending'     => 'In attesa di approvazione',
	'status_review'      => 'In controllo',
	'status_rejected'    => 'Non approvato',
	'status_archived'    => 'Archiviato',

	// Section cards
	'section_photos'          => 'Foto',
	'section_photos_sub'      => 'Gli annunci con foto chiare ricevono molte più risposte.',
	'section_what'            => 'Cosa vendi?',
	'section_what_sub'        => 'Scegli una categoria, poi scrivi un titolo e una descrizione chiari.',
	'section_details'         => 'Dettagli',
	'section_details_sub'     => 'Le informazioni che gli acquirenti cercano in questa categoria.',
	'section_price_location'  => 'Prezzo e luogo',
	'section_price_location_sub' => 'Un prezzo giusto e la città giusta aiutano gli acquirenti a trovarti.',
	'section_more'            => 'Altre opzioni',
	'section_more_sub'        => 'Facoltativo: tag e per quanto tempo l\'annuncio resta online.',
	'section_contact'         => 'Come ti contattano gli acquirenti',
	'section_contact_sub'     => 'Gli acquirenti usano questi dati per contattarti.',
	'section_finish'          => 'Quasi fatto',
	'section_finish_sub'      => 'Controlla e conferma, poi pubblica.',

	// Field hints
	'hint_title'         => 'Dì cos\'è, con marca e modello, ad es. «Samsung Galaxy S21, 128 GB».',
	'hint_description'   => 'Condizioni, età, dimensioni, cosa è incluso e perché vendi.',
	'hint_tags'          => 'Fino a {limit} parole che gli acquirenti potrebbero cercare.',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'Almeno {min} caratteri',

	// Side panel and phone sheet
	'panel_title'        => 'Il tuo annuncio',
	'panel_open'         => 'Checklist',
	'panel_close'        => 'Chiudi',
	'preview'            => 'Anteprima',
	'preview_title'      => 'Qui apparirà il tuo titolo',
	'preview_price'      => 'Ancora nessun prezzo',
	'preview_city'       => 'Ancora nessuna città',
	'preview_category'   => 'Ancora nessuna categoria',
	'preview_negotiable' => 'Negoziabile',
	'checklist'          => 'Prima di pubblicare',
	'checklist_progress' => '{done} di {total} completati',
	'checklist_ready'    => 'Pronto per la pubblicazione',
	'check_category'     => 'Scegli una categoria',
	'check_title'        => 'Aggiungi un titolo',
	'check_description'  => 'Descrivi l\'oggetto',
	'check_price'        => 'Imposta un prezzo',
	'check_city'         => 'Scegli una città',
	'check_contact'      => 'Aggiungi un modo per contattarti',
	'check_done'         => 'Fatto',
	'check_todo'         => 'Da fare',
	'tips'               => 'Consigli per vendere più in fretta',

	// Checks while filling in
	'error_summary'      => 'Correggi questi punti prima di continuare:',
	'error_required'     => 'Compila questo campo.',
	'error_category'     => 'Scegli una categoria.',
	'error_city'         => 'Scegli una città.',
	'error_email'        => 'Inserisci un indirizzo email valido, come nome@example.com.',
	'error_terms'        => 'Accetta i termini per continuare.',
	'error_too_short'    => 'Scrivi almeno {min} caratteri.',

	// AI box for guests
	'ai_guest_text'      => 'Accedi e l\'IA può compilare questo modulo da una breve descrizione o da qualche foto.',
	'ai_guest_login'     => 'Accedi',
	'ai_guest_hide'      => 'Nascondi questo messaggio',

	// AI writing help next to Title and Description
	'ai_help' => 'Aiuto IA',
	'ai_suggest_title' => 'Suggerisci un titolo',
	'ai_suggest_description' => 'Scrivi una descrizione',
	'ai_shorten' => 'Accorcia',
	'ai_clearer' => 'Rendi più chiaro',
	'ai_working' => 'Scrittura in corso…',
	'ai_suggestion' => 'Suggerimento dell\'IA',
	'ai_check' => 'Controllalo prima di usarlo: l\'IA conosce solo ciò che hai scritto.',
	'ai_use' => 'Usa questo',
	'ai_again' => 'Riprova',
	'ai_dismiss' => 'Ignora',
	'ai_undo' => 'Annulla',
	'ai_used' => 'Sostituito con il suggerimento dell\'IA.',
	'ai_undone' => 'Il tuo testo è tornato.',
	'ai_need_text' => 'Scrivi prima qualche parola, poi chiedi aiuto all\'IA.',
	'ai_error' => 'L\'aiuto alla scrittura con IA al momento non è disponibile. Riprova più tardi.',

	// Price hint under Price (statistics from the site's own listings)
	'price_hint' => 'Gli annunci simili a :place hanno un prezzo di :low – :high (:count annunci).',
	'price_hint_one' => 'Gli annunci simili a :place hanno un prezzo di circa :low (:count annunci).',
	'price_hint_parent' => 'Gli annunci di :category a :place hanno un prezzo di :low – :high (:count annunci).',

	// Admin > Settings > Listing form > Listing helpers
	'setting_tab_helpers' => 'Aiuti per gli annunci',
	'setting_price_hint_label' => 'Suggerimento di prezzo',
	'setting_price_hint_hint' => 'Sotto Prezzo, mostra quanto chiedono gli annunci simili sul sito, in base ai tuoi annunci attivi (senza IA). Non compila mai il prezzo.',
	'setting_price_hint_min_label' => 'Annunci necessari per un suggerimento',
	'setting_price_hint_min_hint' => 'Non viene mostrato nessun suggerimento se gli annunci simili sono meno di questo numero. Con 8 o più il suggerimento è affidabile.',

	// Multi-step progress on phones
	'step_of'            => 'Passo :current di :total',
];
