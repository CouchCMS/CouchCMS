<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Hallo';
    $t['view_site'] = 'Bekijk Site';
    $t['logout'] = 'Afmelden';
    $t['javascript_msg'] = 'JavaScript is uitgeschakeld of wordt niet ondersteund door de huidige browser.<br/>
                            Update uw browser of <a href="https://support.google.com/answer/23852" target="_blank">activeer JavaScript</a> om de Beheer module te gebruiken.';
    $t['add_new'] = 'Toevoegen';
    $t['add_new_page'] = 'Pagina toevoegen';
    $t['add_new_user'] = 'Gebruiker toevoegen';
    $t['view'] = 'Weergeven';
    $t['list'] = 'Lijst';
    $t['edit'] = 'Aanpassen';
    $t['delete'] = 'Verwijderen';
    $t['delete_selected'] = 'Verwijder selectie';
    $t['advanced_settings'] = 'Geavanceerde eigenschappen';

    // Sidebar
    $t['comment'] = 'Commentaar';
    $t['comments'] = 'Commentaren';
    $t['manage_comments'] = 'Commentaar beheren';
    $t['users'] = 'Gebruikers';
    $t['manage_users'] = 'Gebruikers beheren';

    // List pages
    $t['view_all_folders'] = 'Toon alle folders';
    $t['filter'] = 'Filter';
    $t['showing'] = 'Tonen';
    $t['title'] = 'Titel';
    $t['folder'] = 'Map';
    $t['date'] = 'Datum';
    $t['actions'] = 'Acties';
    $t['no_pages_found'] = "Geen pagina's gevonden";
    $t['published'] = 'Gepubliceerd';
    $t['unpublished'] = 'Niet gepubliceerd';
    $t['confirm_delete_page'] = 'Weet u zeker dat u deze pagina wilt verwijderen'; // No question mark please
    $t['confirm_delete_selected_pages'] = "Weet u zeker dat u de geselecteerde pagina's wilt verwijderen?";
    $t['remove_template'] = 'Verwijder sjabloon';
    $t['template_missing'] = 'Sjabloon ontbreekt';
    $t['prev'] = 'Vorige'; // Pagination button
    $t['next'] = 'Volgende'; // Pagination button

    // Pages
    $t['welcome'] = 'Welkom';
    $t['no_regions_defined'] = "Geen aanpasbare regio's gedefinieerd";
    $t['no_templates_defined'] = 'Er worden geen sjablonen door het CMS beheerd';
    $t['access_level'] = 'Toegangsniveau';
    $t['superadmin'] = 'Super Admin';
    $t['admin'] = 'Administrator';
    $t['authenticated_user_special'] = 'Geverifieerde Gebruiker (Speciaal)';
    $t['authenitcated_user'] = 'Geverifieerde Gebruiker';
    $t['unauthenticated_user'] = 'Iedereen';
    $t['allow_comments'] = 'Gebruikerscommentaar toegestaan';
    $t['status'] = 'Status';
    $t['name'] = 'Naam';
    $t['title_desc'] = 'Laat dit veld leeg om het systeem een naam op basis van de titel te laten genereren';
    $t['required'] = 'verplicht'; // Required field
    $t['required_msg'] = 'Een verplicht veld kan niet leeg zijn';
    $t['browse_server'] = 'Bladeren';
    $t['view_image'] = 'Bekijk afbeelding';
    $t['thumb_created_auto'] = 'Wordt automatisch aangemaakt';
    $t['recreate'] = 'Opnieuw aanmaken';
    $t['thumb_recreated'] = 'Miniatuur opnieuw aangemaakt';
    $t['crop_from'] = 'Afknippen van';
    $t['top_left'] = 'Bovenaan links';
    $t['top_center'] = 'Bovenaan gecentreerd';
    $t['top_right'] = 'Bovenaan rechts';
    $t['middle_left'] = 'Midden links';
    $t['middle'] = 'Midden';
    $t['middle_right'] = 'Midden rechts';
    $t['bottom_left'] = 'Onderaan links';
    $t['bottom_center'] = 'Onderaan gecentreerd';
    $t['bottom_right'] = 'Bovenaan rechts';
    $t['view_thumbnail'] = 'Bekijk miniatuur';
    $t['field_not_found'] = 'Veld niet gevonden!';
    $t['delete_permanently'] = 'Definitief verwijderen?';
    $t['view_code'] = 'Bekijk code';
    $t['confirm_delete_field'] = 'Weet u zeker dat u dit veld wilt verwijderen?';
    $t['save'] = 'Opslaan';

    // Comments
    $t['all'] = 'Alles';
    $t['unapprove'] = 'Niet goedkeuren';
    $t['unapproved'] = 'Niet goedgekeurd';
    $t['approve'] = 'Goedkeuren';
    $t['approved'] = 'Goedgekeurd';
    $t['select-deselect'] = 'Selecteer/Deselecteer Alles';
    $t['confirm_delete_comment'] = 'Weet u zeker dat u deze commentaar wilt verwijderen';
    $t['confirm_delete_selected_comments'] = 'Weet u zeker dat u de geselecteerde commentaren wilt verwijderen?';
    $t['bulk_action'] = 'Batch actie op basis van selectie';
    $t['apply'] = 'Uitvoeren';
    $t['submitted_on'] = 'Ingezonden op';
    $t['email'] = 'Emailadres';
    $t['website'] = 'Website';
    $t['duplicate_content'] = 'Dupliceer inhoud';
    $t['insufficient_interval'] = 'Te korte tijdsinterval tussen de commentaren';

    // Users
    $t['user_name_restrictions'] = 'Enkel kleine letters, cijfers, koppeltekens en liggende streepjes zijn toegestaan';
    $t['display_name'] = 'Weergave naam';
    $t['role'] = 'Rol';
    $t['no_users_found'] = 'Geen gebruikers gevonden';
    $t['confirm_delete_user'] = 'Weet u zeker dat u deze gebruiker wilt verwijderen'; // No question mark please
    $t['confirm_delete_selected_users'] = 'Weet u zeker dat u de geselecteerde gebruikers wilt verwijderen?';
    $t['disabled'] = 'Gedeactiveerd';
    $t['new_password'] = 'Nieuw wachtwooord';
    $t['new_password_msg'] = 'Voer een nieuw wachtwoord in of laat leeg om het huidige wachtwoord te behouden';
    $t['repeat_password'] = 'Herhaal het wachtwoord';
    $t['repeat_password_msg'] = 'Voer ter verificatie nogmaals uw wachtwoord in.';
    $t['user_name_exists'] = 'Gebruikersnaam bestaat al';
    $t['email_exists'] = 'Emailadres bestaat al';

    // Login
    $t['user_name'] = 'Gebruikersnaam';
    $t['password'] = 'Wachtwoord';
    $t['login'] = 'Aanmelden';
    $t['forgot_password'] = 'Wachtwoord vergeten?';
    $t['prompt_cookies'] = 'U moet Cookies toestaan om deze CMS te kunnen gebruiken';
    $t['prompt_username'] = 'Voer a.u.b. uw gebruikersnaam in';
    $t['prompt_password'] = 'Voer a.u.b. uw wachtwoord in';
    $t['invalid_credentials'] = 'Ongeldige gebruikersnaam of wachtwoord';
    $t['account_disabled'] = 'Account is niet geactiveerd';
    $t['access_denied'] = 'Geen toegang';
    $t['insufficient_privileges'] = 'U heeft niet genoeg rechten om gevraagde pagina te bekijken.
                                    Om de pagina te bekijken moet u zich opnieuw aanmelden met de juste rechten.';

    // Password recovery
    $t['recovery_prompt'] = 'Voer uw gebruikersnaam of emailadres in.<br/>
                            U ontvangt uw nieuwe wachtwoord per email.';
    $t['name_or_email'] = 'Uw gebruikersnaam of emailadres';
    $t['submit'] = 'Verzenden';
    $t['submit_error'] = 'Voer a.u.b. uw gebruikersnaam of emailadres in.';
    $t['no_such_user'] = 'Opgegeven gebruiker bestaat niet.';
    $t['reset_req_email_subject'] = 'Aanvraag om uw wachtwoord te resetten';
    $t['reset_req_email_msg_0'] = 'Er is een verzoek om uw wachtwoord te resetten gedaan voor de volgende site en gebruiker';
    $t['reset_req_email_msg_1'] = 'Bezoek het volgende web-adres om te bevestigen dat u dit verzoek heeft ingediend. Was u dit niet zelf, negeer dan dit emailbericht.';
    $t['email_failed'] = 'Email kon niet worden verstuurd.';
    $t['reset_req_email_confirm'] = 'Er werd een  bevestigingsmail verstuurd.<br/>
                                    Controleer a.u.b. uw email.';
    $t['invalid_key'] = 'Ongeldige sleutel';
    $t['reset_email_subject'] = 'Uw nieuwe wachtwoord';
    $t['reset_email_msg_0'] = 'Uw wachtwoord werd gereset voor de volgende site en gebruikersnaam';
    $t['reset_email_msg_1'] = 'U kan uw wachtwoord aanpassen zodra u bent aangemeld.';
    $t['reset_email_confirm'] = 'Uw wachtwoord werd gereset<br/>
                                Controleer a.u.b. uw email voor uw nieuwe wachtwoord.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>In Onderhoud Modus!</h2>
                        <p>
                            Excuses voor het ongemak.<br/>
                            De website ondergaat momenteel geplande onderhoud.<br/>
                            <b>Probeert u het a.u.b. kortstondig opnieuw.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Beheer paneel';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'Geen mappen aangemaakt';
    $t['select_folder'] = 'Selecteer een map';
    $t['folders'] = 'Mappen';
    $t['manage_folders'] = 'Mappen beheren';
    $t['add_new_folder'] = 'Map toevoegen';
    $t['parent_folder'] = 'Bovenliggende map';
    $t['weight'] = 'Gewicht';
    $t['weight_desc'] = 'Hoe hoger de waarde, hoe lager de map in de lijst komt te staan. Negatieve waarden zijn toegestaan.';
    $t['desc'] = 'Omschrijving';
    $t['image'] = 'Afbeelding';
    $t['cannot_be_own_parent'] = 'Kan niet zijn eigen bovenliggende map zijn';
    $t['name_already_exists'] = 'Naam bestaat al';
    $t['pages'] = "Pagina's";
    $t['none'] = 'Geen';
    $t['confirm_delete_folder'] = 'Weet u zeker dat u deze map wilt verwijderen'; // No question mark please
    $t['confirm_delete_selected_folders'] = 'Weet u zeker dat u de geselecteerde mappen wilt verwijderen?';

    // Drafts
    $t['draft_caps'] = 'CONCEPT'; // Upper case
    $t['draft'] = 'Concept';
    $t['drafts'] = 'Concepten';
    $t['create_draft'] = 'Concept aanmaken';
    $t['create_draft_msg'] = 'Maak een kopie van deze pagina (nadat de aanpassingen zijn opgeslagen)';
    $t['manage_drafts'] = 'Concepten beheren'; // Plural
    $t['update_original'] = 'Origineel updaten';
    $t['update_original_msg'] = 'Kopieer de inhoud van dit concept naar de originele pagina (en verwijder het concept)';
    $t['recreate_original'] = 'Origineel opnieuw aanmaken';
    $t['no_drafts_found'] = 'Geen concepten gevonden';
    $t['original_page'] = 'Originele pagina';
    $t['template'] = 'Sjabloon';
    $t['modified'] = 'Aangepast'; // Date of last modification
    $t['preview'] = 'Voorvertoning';
    $t['confirm_delete_draft'] = 'Weet u zeker dat u dit concept wilt verwijderen'; // No question mark please
    $t['confirm_delete_selected_drafts'] = 'Weet u zeker dat u de geselecteerde concepten wilt verwijderen?';
    $t['confirm_apply_selected_drafts'] = 'Weet u zeker dat  u de geselecteerde concepten wilt doorvoeren?';
    $t['view_all_drafts'] = 'Bekijk alle concepten';
    $t['original_deleted'] = 'ORIGINEEL VERWIJDERD'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = 'Bovenliggende pagina';
    $t['page_weight_desc'] = 'Hoe hoger de waarde, hoe lager de pagina in de lijst komt te staan. Negatieve waarden zijn toegestaan.';
    $t['active'] = 'Actief';
    $t['inactive'] = 'Niet actief';
    $t['menu'] = 'Menu';
    $t['menu_text'] = 'Menutekst';
    $t['show_in_menu'] = 'In menu tonen';
    $t['not_shown_in_menu'] = 'Niet in menu getoond';
    $t['leave_empty'] = 'Laat leeg om paginatitel te gebruiken';
    $t['menu_link'] = 'Menukoppeling';
    $t['link_url'] = 'Deze pagina verwijst naar de volgende locatie';
    $t['link_url_desc'] = 'Mag leeg zijn';
    $t['separate_window'] = 'Open in een apart venster';
    $t['pointer_page'] = 'Verwijspagina';
    $t['points_to_another_page'] = 'Verwijst naar een andere pagina';
    $t['points_to'] = 'Verwijst naar';
    $t['redirects'] = 'Omleiding naar';
    $t['masquerades'] = 'Maskeert';
    $t['strict_matching'] = "Markeer als geselecteerd in het menu voor alle pagina's onder deze koppeling";
    $t['up'] = 'Verplaats omhoog';
    $t['down'] = 'Verplaats omlaag';
    $t['remove_template_completely'] = "Verwijder alle pagina's en concepten van dit sjabloon om het volledig te verwijderen.";
    $t['remove_uncloned_template_completely'] = 'Verwijder alle concepten van dit sjabloon om het volledig te verwijderen';

    // Addendum to Version 1.2.5 /////////////////////////////////////
    // Gallery
    $t['bulk_upload'] = 'Bulk Uploaden';
    $t['folder_empty'] = 'Deze map is leeg. Gebruik de Upload-knop bovenaan om afbeeldingen toe te voegen.';
    $t['root'] = 'Hoofd map';
    $t['item'] = 'afbeelding'; // Single
    $t['items'] = 'afbeeldingen'; // Multiple
    $t['container'] = 'Map'; // Single
    $t['containers'] = 'Mappen'; // Multiple

    //
    $t['columns_missing'] = 'Sommige kolommen ontbreken!';
    $t['confirm_delete_columns'] = 'Weet u zeker dat u de ontbrekende kolommen wilt verwijderen?';
    $t['add_row'] = 'Rij toevoegen';
	
    // 2.0
    $t['left'] = 'Verplaats naar links';
    $t['right'] = 'Verplaats naar rechts';
    $t['crop'] = 'Afknippen';
    $t['menu_templates'] = 'Sjablonen';
    $t['menu_modules'] = 'Beheer';
    $t['cancel'] = 'Annuleer';
    $t['selected'] = 'Geselecteerd';
    $t['add'] = 'Toevoegen';
    $t['remove'] = 'Verwijderen';
    // 2.1
    $t['tiles_missing'] = 'Beepaalde tegels ontbreken!';
    $t['confirm_delete_tiles'] = 'Weet u zeker dat u de ontbrekende tegels wilt verwijderen?';
    $t['add_above'] = 'Hierboven toevoegen';
    $t['confirm_delete_row'] = 'Deze regel verwijderen?';
    $t['no_data_message'] = '- Geen Gegevens -';
    $t['ok'] = 'OKE';
    $t['globals'] = 'Globale velden';
    $t['manage_globals'] = 'Globale velden beheren';
    $t['bulk_action_with_selected'] = 'Bulk actie met selectie';
    $t['month01'] = 'Januari';
    $t['month02'] = 'Februari';
    $t['month03'] = 'Maart';
    $t['month04'] = 'April';
    $t['month05'] = 'Mei';
    $t['month06'] = 'Juni';
    $t['month07'] = 'Juli';
    $t['month08'] = 'Augustus';
    $t['month09'] = 'September';
    $t['month10'] = 'Oktober';
    $t['month11'] = 'November';
    $t['month12'] = 'December';
	
	// New additions
	// Short month names
    $t['s_month01'] = 'Jan';
    $t['s_month02'] = 'Feb';
    $t['s_month03'] = 'Mrt';
    $t['s_month04'] = 'Apr';
    $t['s_month05'] = 'Mei';
    $t['s_month06'] = 'Jun';
    $t['s_month07'] = 'Jul';
    $t['s_month08'] = 'Aug';
    $t['s_month09'] = 'Sep';
    $t['s_month10'] = 'Okt';
    $t['s_month11'] = 'Nov';
    $t['s_month12'] = 'Dec';
	// Days of the week names
	$t['day0'] = 'Zondag'; // When using w parameter for date
	$t['day1'] = 'Maandag';
	$t['day2'] = 'Dinsdag';
	$t['day3'] = 'Woensdag';
	$t['day4'] = 'Donderdag';
	$t['day5'] = 'Vrijdag';
	$t['day6'] = 'Zaterdag';
	$t['day7'] = 'Zondag'; // When using N parameter for date
	// Short day of the week names
	$t['s_day0'] = 'Zo'; // When using w parameter for date
	$t['s_day1'] = 'Ma';
	$t['s_day2'] = 'Di';
	$t['s_day3'] = 'Wo';
	$t['s_day4'] = 'Do';
	$t['s_day5'] = 'Vr';
	$t['s_day6'] = 'Za';
	$t['s_day7'] = 'Zo'; // When using N parameter for date
	
