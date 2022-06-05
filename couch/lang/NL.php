<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Hallo';
    $t['view_site'] = 'Bekijk Site';
    $t['logout'] = 'Uitloggen';
    $t['javascript_msg'] = 'JavaScript is uitgeschakeld of wordt niet ondersteund door de huidige browser.<br/>
                            Gelieve uw browser te updaten of <a href="https://support.google.com/answer/23852" target="_blank">activeer JavaScript</a> om de Beheer module te gebruiken.';
    $t['add_new'] = 'Nieuw toevoegen';
    $t['add_new_page'] = 'Nieuwe pagina toevoegen';
    $t['add_new_user'] = 'Nieuwe gebruiker toevoegen';
    $t['view'] = 'Pagina bekijken';
    $t['list'] = 'Lijst opbouwen';
    $t['edit'] = 'Editeer';
    $t['delete'] = 'Verwijder';
    $t['delete_selected'] = 'Verwijder selectie';
    $t['advanced_settings'] = 'Geavanceerde eigenschappen';

    // Sidebar
    $t['comment'] = 'Commentaar';
    $t['comments'] = 'Meer Commentaar';
    $t['manage_comments'] = 'Beheer Commentaar';
    $t['users'] = 'Gebruikers';
    $t['manage_users'] = 'Beheer Gebruikers';

    // List pages
    $t['view_all_folders'] = 'Toon alle folders';
    $t['filter'] = 'Filter';
    $t['showing'] = 'Tonen';
    $t['title'] = 'Titel';
    $t['folder'] = 'Folder';
    $t['date'] = 'Datum';
    $t['actions'] = 'Acties';
    $t['no_pages_found'] = 'Geen paginas gevonden';
    $t['published'] = 'Gepubliceerd';
    $t['unpublished'] = 'Niet gepubpliceerd';
    $t['confirm_delete_page'] = 'Bent u zeker deze pagina permanent te verwijderen'; // No question mark please
    $t['confirm_delete_selected_pages'] = 'Bent u zeker de geselecteerde paginas permanent te verwijderen?';
    $t['remove_template'] = 'Verwijder stramien';
    $t['template_missing'] = 'Stramien ontbreekt';
    $t['prev'] = 'Vorige'; // Pagination button
    $t['next'] = 'Volgende'; // Pagination button

    // Pages
    $t['welcome'] = 'Welkom';
    $t['no_regions_defined'] = 'Geen editeerbare regios gedetecteerd';
    $t['no_templates_defined'] = 'Er wordt geen stramien door het CMS beheerd';
    $t['access_level'] = 'Toegangsniveau';
    $t['superadmin'] = 'Super Admin';
    $t['admin'] = 'Administrator';
    $t['authenticated_user_special'] = 'Authentieke Gebruiker (Speciaal)';
    $t['authenitcated_user'] = 'Authentieke Gebruiker';
    $t['unauthenticated_user'] = 'Iedereen';
    $t['allow_comments'] = 'Gebruikerscommentaar toestaan';
    $t['status'] = 'Status';
    $t['name'] = 'Naam';
    $t['title_desc'] = 'Laat dit veld leeg om een automatische naam door het systeem te laten toewijzen op basis van de titel';
    $t['required'] = 'verplicht'; // Required field
    $t['required_msg'] = 'Verplicht veld en mag niet ledig zijn';
    $t['browse_server'] = 'Navigeer server';
    $t['view_image'] = 'Bekijk afbeelding';
    $t['thumb_created_auto'] = 'Wordt automatisch aangemaakt';
    $t['recreate'] = 'Heropbouw';
    $t['thumb_recreated'] = 'Thumbnail aangemaakt';
    $t['crop_from'] = 'Bijsnijden van';
    $t['top_left'] = 'Bovenaan links';
    $t['top_center'] = 'Bovenaan gecentreerd';
    $t['top_right'] = 'Bovenaan rechts';
    $t['middle_left'] = 'Midden links';
    $t['middle'] = 'Midden';
    $t['middle_right'] = 'Midden rechts';
    $t['bottom_left'] = 'Onderaan links';
    $t['bottom_center'] = 'Onderaan gecentreerd';
    $t['bottom_right'] = 'Bovenaan rechts';
    $t['view_thumbnail'] = 'Bekijk Thumbnail';
    $t['field_not_found'] = 'Veld niet gevonden!';
    $t['delete_permanently'] = 'Permanent verwijderen?';
    $t['view_code'] = 'Bekijk code';
    $t['confirm_delete_field'] = 'Bent u zeker dit veld permanent te verwijderen?';
    $t['save'] = 'Opslaan';

    // Comments
    $t['all'] = 'Allen';
    $t['unapprove'] = 'Niet toestaan';
    $t['unapproved'] = 'Niet toegestaan';
    $t['approve'] = 'Goedkeuren';
    $t['approved'] = 'Goedgekeurd';
    $t['select-deselect'] = 'Selecteer/Deselecteer Alles';
    $t['confirm_delete_comment'] = 'Bent u zeker deze commentaar permanent te verwijderen?';
    $t['confirm_delete_selected_comments'] = 'Bent u zeker de geselecteerde commentaar permanent te verwijderen?';
    $t['bulk_action'] = 'Batch actie op basis van selectie';
    $t['apply'] = 'Uitvoeren';
    $t['submitted_on'] = 'Bevestigd op';
    $t['email'] = 'E-Mail';
    $t['website'] = 'Website';
    $t['duplicate_content'] = 'Dupliceer inhoud';
    $t['insufficient_interval'] = 'De actie volgt te kort op de vorige actie (tijdsinterval respecteren)';

    // Users
    $t['user_name_restrictions'] = 'Enkel kleine letters, cijfers, koppeltekens en liggende streepjes zijn toegestaan';
    $t['display_name'] = 'Sitenaam';
    $t['role'] = 'Rol';
    $t['no_users_found'] = 'Geen gebruikers gevonden';
    $t['confirm_delete_user'] = 'Bent u zeker deze gebruiker permanent te verwijderen'; // No question mark please
    $t['confirm_delete_selected_users'] = 'Bent u zeker de geselecteerde gebruikers permanent te verwijderen?';
    $t['disabled'] = 'Deactiveren';
    $t['new_password'] = 'Nieuw paswoord';
    $t['new_password_msg'] = 'Geef een nieuw paswoord in indien u deze wenst te wijzigen. Zoniet laat dit veld ledig.';
    $t['repeat_password'] = 'Herhaal Paswoord';
    $t['repeat_password_msg'] = 'Geef uw paswoord nogmaals in ter verificatie.';
    $t['user_name_exists'] = 'Gebruikersnaam bestaat reeds';
    $t['email_exists'] = 'E-Mail bestaat reeds';

    // Login
    $t['user_name'] = 'Gebruikersnaam';
    $t['password'] = 'Paswoord';
    $t['login'] = 'Login';
    $t['forgot_password'] = 'Paswoord vergeten?';
    $t['prompt_cookies'] = 'Cookies moeten toegestaan zijn om de beheermodule te kunnen gebruiken';
    $t['prompt_username'] = 'Gelieve uw gebruikersnaam in te geven';
    $t['prompt_password'] = 'Gelieve uw paswoord in te geven';
    $t['invalid_credentials'] = 'Ongeldige gebruikersnaam of paswoord';
    $t['account_disabled'] = 'Account niet geactiveerd';
    $t['access_denied'] = 'Geen toegang';
    $t['insufficient_privileges'] = 'U beschikt niet over genoeg machtigingen om de pagina te bekijken.
                                    Gelieve uit te loggen en opnieuw in te loggen met de correcte machtigingen.';

    // Password recovery
    $t['recovery_prompt'] = 'Gelieve uw gebruikersnaam of email adres te bevestigen.<br/>
                            U zult uw paswoord via mail ontvangen.';
    $t['name_or_email'] = 'Uw gebruikersnaam of emailadres';
    $t['submit'] = 'Doorvoeren';
    $t['submit_error'] = 'Gelieve uw gebruikersnaam of email adres in te geven.';
    $t['no_such_user'] = 'Dergelijke gebruiker blijkt niet voor te komen.';
    $t['reset_req_email_subject'] = 'Reset van u paswoord aangevraagd';
    $t['reset_req_email_msg_0'] = 'Een aanvraag om uw paswoord te resetten voor de volgende site en gebruiker werd aangevraagd';
    $t['reset_req_email_msg_1'] = 'Een aanvraag werd ingediend om uw paswoord te resetten. Indien dit effectief door u werd gedaan, dient u de volgende link te bezoeken om de actie te finaliseren, zoniet mag deze mail genegeerd worden.';
    $t['email_failed'] = 'E-Mail kon niet verstuurd worden.';
    $t['reset_req_email_confirm'] = 'Een bevestigingsmail werd verstuurd<br/>
                                    Gelieve uw mail te controleren.';
    $t['invalid_key'] = 'Ongeldige sleutel';
    $t['reset_email_subject'] = 'Uw nieuw paswoord';
    $t['reset_email_msg_0'] = 'Uw paswoord werd gereset voor de volgende site en gebruikersnaam';
    $t['reset_email_msg_1'] = 'Eens ingelogd kunt u uw paswoord aanpassen.';
    $t['reset_email_confirm'] = 'Uw paswoord werd gereset<br/>
                                Gelieve uw mail te controleren voor uw nieuw paswoord.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>Wij zijn dadelijk terug!</h2>
                        <p>
                            Excuses voor het ongemak.<br/>
                            De website ondergaat momenteel een kort onderhoud.<br/>
                            <b>Gelieve binnen enkele ogenblikken de pagina opnieuw te herladen.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Beheer module';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'Geen folders aangemaakt';
    $t['select_folder'] = 'Selecteer folder';
    $t['folders'] = 'folders';
    $t['manage_folders'] = 'Beheer folders';
    $t['add_new_folder'] = 'Toevoegen nieuwe folder';
    $t['parent_folder'] = 'Bovenliggende folder';
    $t['weight'] = 'Folderwaarde';
    $t['weight_desc'] = 'Hoe hoger de waarde, hoe lager de folder in de lijst komt te staan. Negatieve waarden toegestaan.';
    $t['desc'] = 'Omschrijving';
    $t['image'] = 'Afbeelding';
    $t['cannot_be_own_parent'] = 'Kan zijn eigen bovenliggende map niet zijn';
    $t['name_already_exists'] = 'Naam reeds in gebruik';
    $t['pages'] = 'Paginas';
    $t['none'] = 'ledig';
    $t['confirm_delete_folder'] = 'Bent u zeker deze folder te verwijderen'; // No question mark please
    $t['confirm_delete_selected_folders'] = 'Bent u zeker de geselecteerde folders te verwijderen?';

    // Drafts
    $t['draft_caps'] = 'KLAD'; // Upper case
    $t['draft'] = 'Klad';
    $t['drafts'] = 'Kladpaginas';
    $t['create_draft'] = 'Maak klad';
    $t['create_draft_msg'] = 'Maak een kopie van deze pagina (na opgeslaan te hebben)';
    $t['manage_drafts'] = 'Beheer kladpaginas'; // Plural
    $t['update_original'] = 'Update Origineel';
    $t['update_original_msg'] = 'Kopieer de data van het klad naar de originele pagina (en verwijder klad)';
    $t['recreate_original'] = 'Heropbouw origineel';
    $t['no_drafts_found'] = 'Geen klad gevonden';
    $t['original_page'] = 'Originele pagina';
    $t['template'] = 'Stramien';
    $t['modified'] = 'aangepast'; // Date of last modification
    $t['preview'] = 'Voorvertoning';
    $t['confirm_delete_draft'] = 'Bent u zeker deze kladpagina te verwijderen'; // No question mark please
    $t['confirm_delete_selected_drafts'] = 'Bent u zeker de geselecteerde kladpaginas te verwijderen?';
    $t['confirm_apply_selected_drafts'] = 'Bent u zeker de geselecteerde kladpaginas te bevestigen?';
    $t['view_all_drafts'] = 'Bekijk alle kladpaginas';
    $t['original_deleted'] = 'ORIGINEEL VERWIJDERD'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = 'Bovenliggende pagina';
    $t['page_weight_desc'] = 'Hoe hoger de waarde, hoe lager de folder in de lijst komt te staan. Negatieve waarden toegestaan.';
    $t['active'] = 'Actief';
    $t['inactive'] = 'Inactief';
    $t['menu'] = 'Menu';
    $t['menu_text'] = 'Menu tekst';
    $t['show_in_menu'] = 'Toon in menu';
    $t['not_shown_in_menu'] = 'Niet getoond in menu';
    $t['leave_empty'] = 'Laat dit leeg om de titel van de pagina te gebruiken';
    $t['menu_link'] = 'Menu Link';
    $t['link_url'] = 'Deze pagina verwijst naar de volgende locatie';
    $t['link_url_desc'] = 'Mag ledig zijn';
    $t['separate_window'] = 'Open in een apart venster';
    $t['pointer_page'] = 'Verwijspagina';
    $t['points_to_another_page'] = 'Verwijst naar een andere pagina';
    $t['points_to'] = 'Verwijst naar';
    $t['redirects'] = 'Herverwijst';
    $t['masquerades'] = 'Maskeert';
    $t['strict_matching'] = 'Markeer als selectie in het menu voor alle paginas volgend op onderstaande link';
    $t['up'] = 'Omhoog';
    $t['down'] = 'Omlaag';
    $t['remove_template_completely'] = 'Verwijder alle paginas en kladpaginas van dit stramien om het stramien volledig te verwijderen.';
    $t['remove_uncloned_template_completely'] = 'Verwijder alle kladpaginas van dit stramien om het stramien volledig te verwijderen';

    // Addendum to Version 1.2.5 /////////////////////////////////////
    // Gallery
    $t['bulk_upload'] = 'Opladen';
    $t['folder_empty'] = 'Folder leeg. Gelieve de Opladen knop bovenaan te gebruiken om afbeeldingen toe te voegen.';
    $t['root'] = 'Root';
    $t['item'] = 'afbeelding'; // Single
    $t['items'] = 'afbeeldingen'; // Multiple
    $t['container'] = 'folder'; // Single
    $t['containers'] = 'folders'; // Multiple

    //
    $t['columns_missing'] = 'Sommige kolommen ontbreken!';
    $t['confirm_delete_columns'] = 'Bent u zeker de ontbrekende kolommen permanent te verwijderen?';
    $t['add_row'] = 'Rij toevoegen';
