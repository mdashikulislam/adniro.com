<?php

/*
 * adniro: the create/edit listing pages (config/listing_form.php).
 * Placeholders in {braces} are filled by the page scripts; :name ones by Laravel.
 */

return [
	// Page header
	'create_sub'         => 'Cela prend environ 2 minutes. Les champs marqués * sont obligatoires.',
	'edit_kicker'        => 'Modifier l\'annonce',
	'view_listing'       => 'Voir l\'annonce',
	'status_live'        => 'En ligne',
	'status_pending'     => 'En attente de validation',
	'status_review'      => 'En vérification',
	'status_rejected'    => 'Non approuvée',
	'status_archived'    => 'Archivée',

	// Section cards
	'section_photos'          => 'Photos',
	'section_photos_sub'      => 'Les annonces avec de belles photos reçoivent bien plus de réponses.',
	'section_what'            => 'Que vendez-vous ?',
	'section_what_sub'        => 'Choisissez une catégorie, puis donnez un titre et une description clairs.',
	'section_details'         => 'Détails',
	'section_details_sub'     => 'Les infos que les acheteurs recherchent dans cette catégorie.',
	'section_price_location'  => 'Prix et lieu',
	'section_price_location_sub' => 'Un prix juste et la bonne ville aident les acheteurs à vous trouver.',
	'section_more'            => 'Autres options',
	'section_more_sub'        => 'Facultatif : mots-clés et durée de publication de l\'annonce.',
	'section_contact'         => 'Comment les acheteurs vous joignent',
	'section_contact_sub'     => 'Les acheteurs utilisent ces coordonnées pour vous contacter.',
	'section_finish'          => 'Presque terminé',
	'section_finish_sub'      => 'Vérifiez et confirmez, puis publiez.',

	// Field hints
	'hint_title'         => 'Dites ce que c\'est, avec la marque et le modèle, par ex. « Samsung Galaxy S21, 128 GB ».',
	'hint_description'   => 'État, ancienneté, taille, ce qui est inclus et pourquoi vous vendez.',
	'hint_tags'          => 'Jusqu\'à {limit} mots que les acheteurs pourraient rechercher.',
	'counter'            => '{count} / {max}',
	'counter_min'        => 'Au moins {min} caractères',

	// Side panel and phone sheet
	'panel_title'        => 'Votre annonce',
	'panel_open'         => 'Liste de contrôle',
	'panel_close'        => 'Fermer',
	'preview'            => 'Aperçu',
	'preview_title'      => 'Votre titre apparaîtra ici',
	'preview_price'      => 'Pas encore de prix',
	'preview_city'       => 'Pas encore de ville',
	'preview_category'   => 'Pas encore de catégorie',
	'preview_negotiable' => 'Négociable',
	'checklist'          => 'Avant de publier',
	'checklist_progress' => '{done} sur {total} terminés',
	'checklist_ready'    => 'Prête à publier',
	'check_category'     => 'Choisir une catégorie',
	'check_title'        => 'Ajouter un titre',
	'check_description'  => 'Décrire l\'article',
	'check_price'        => 'Fixer un prix',
	'check_city'         => 'Choisir une ville',
	'check_contact'      => 'Ajouter un moyen de vous joindre',
	'check_done'         => 'Fait',
	'check_todo'         => 'À faire',
	'tips'               => 'Conseils pour vendre plus vite',

	// Checks while filling in
	'error_summary'      => 'Veuillez corriger ces points avant de continuer :',
	'error_required'     => 'Veuillez remplir ce champ.',
	'error_category'     => 'Veuillez choisir une catégorie.',
	'error_city'         => 'Veuillez choisir une ville.',
	'error_email'        => 'Saisissez une adresse e-mail valide, comme nom@example.com.',
	'error_terms'        => 'Veuillez accepter les conditions pour continuer.',
	'error_too_short'    => 'Veuillez écrire au moins {min} caractères.',

	// AI box for guests
	'ai_guest_text'      => 'Connectez-vous et l\'IA pourra remplir ce formulaire à partir d\'une courte description ou de quelques photos.',
	'ai_guest_login'     => 'Connectez-vous',
	'ai_guest_hide'      => 'Masquer ce message',

	// Multi-step progress on phones
	'step_of'            => 'Étape :current sur :total',
];
