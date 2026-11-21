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
	'panel_open'         => 'Qualità',
	'panel_close'        => 'Chiudi',
	'preview_price'      => 'Ancora nessun prezzo',
	'preview_city'       => 'Ancora nessuna città',
	'preview_category'   => 'Ancora nessuna categoria',
	'preview_negotiable' => 'Negoziabile',
	'live_title'         => 'Anteprima live per gli acquirenti',
	'live_title_empty'   => 'Qui appare il titolo dell\'annuncio',
	'live_just_now'      => 'Proprio ora',
	'live_photo_next'    => 'Foto di copertina: nel passo successivo',
	'live_photo_below'   => 'Aggiungi le foto qui sotto: la prima è la copertina',
	'live_photo_here'    => 'Aggiungi le foto: la prima è la copertina',
	'manage_title'       => 'Gestisci il tuo annuncio',
	'manage_view_hint'   => 'Vedilo come gli acquirenti',
	'manage_photos_hint' => 'Foto: :count · aggiungi, rimuovi o riordina',
	'manage_premium_hint' => 'Promuovilo con un pacchetto',
	'check_category'     => 'Categoria scelta',
	'check_title'        => 'Titolo chiaro ({min}–{max} caratteri)',
	'check_description'  => 'Descrizione dettagliata ({min}+ caratteri)',
	'check_price'        => 'Prezzo indicato',
	'check_city'         => 'Città scelta',
	'check_contact'      => 'Recapiti aggiunti',
	'check_photos'       => 'Foto aggiunte ({min}+)',
	'check_done'         => 'Fatto',
	'check_todo'         => 'Da fare',
	'strength'           => 'Punteggio di qualità dell\'annuncio',
	'strength_score'     => 'Punti',
	'strength_weak'      => 'Per iniziare',
	'strength_fair'      => 'Buon inizio',
	'strength_strong'    => 'Solido',
	'strength_excellent' => 'Eccellente',
	'strength_weak_text' => 'Aggiungi le informazioni di base perché gli acquirenti lo trovino.',
	'strength_fair_text' => 'Qualche dettaglio in più lo farà risaltare.',
	'strength_strong_text' => 'Ci sei quasi: completa gli ultimi dettagli.',
	'strength_excellent_text' => 'Pronto da pubblicare.',
	'strength_photos_next' => 'Le foto si aggiungono nel passo successivo.',
	'hint_category_todo' => 'Gli acquirenti cercano per categoria',
	'hint_title_empty'   => 'Marca, modello e il dettaglio chiave',
	'hint_title_short'   => 'Altri {count} caratteri per un titolo chiaro',
	'hint_title_long'    => 'Lungo: gli acquirenti vedono circa i primi {max} caratteri',
	'hint_title_good'    => 'Lunghezza giusta',
	'hint_description_empty' => 'Condizioni, età e cosa è incluso',
	'hint_description_short' => 'Altri {count} caratteri per una descrizione completa',
	'hint_description_good' => 'Abbastanza dettagliata',
	'hint_price_todo'    => 'Gli acquirenti filtrano per prezzo',
	'hint_price_negotiable' => 'Trattabile',
	'hint_city_todo'     => 'Gli acquirenti cercano vicino a loro',
	'hint_contact_todo'  => 'Un\'email o un numero di telefono',
	'hint_contact_done'  => 'Gli acquirenti possono contattarti',
	'hint_photos_empty'  => 'La prima foto è la copertina',
	'hint_photos_more'   => 'Altre {count} per una galleria completa',
	'hint_photos_good'   => 'Bella galleria',
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
