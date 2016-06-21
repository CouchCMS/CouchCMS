<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // German translation courtesy Thomas Klaiber <hallo@thomasklaiber.com>
    // Additional inputs courtsey Manfred Timm <manfred.timm@googlemail.com>

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Hallo';
    $t['view_site'] = 'Seite ansehen';
    $t['logout'] = 'Abmelden';
    $t['javascript_msg'] = 'JavaScript ist deaktiviert oder wird von Ihrem Browser nicht unterst&uuml;tzt.<br/>
                            Bitte aktualisieren Sie Ihren Browser oder <a href="https://support.google.com/answer/23852" target="_blank">aktivieren Sie JavaScript</a> um das Admin Panel zu verwenden.';
    $t['add_new'] = 'Hinzuf&uuml;gen';
    $t['add_new_page'] = 'Neue Seite';
    $t['add_new_user'] = 'Neuer Benutzer';
    $t['view'] = 'Ansehen';
    $t['list'] = 'Liste';
    $t['edit'] = 'Bearbeiten';
    $t['delete'] = 'L&ouml;schen';
    $t['delete_selected'] = 'Ausgew&auml;hlte l&ouml;schen';
    $t['advanced_settings'] = 'Erweiterte Einstellungen';

    // Sidebar
    $t['comment'] = 'Kommentar';
    $t['comments'] = 'Kommentare';
    $t['manage_comments'] = 'Kommentare verwalten';
    $t['users'] = 'Benutzer';
    $t['manage_users'] = 'Benutzer verwalten';

    // List pages
    $t['view_all_folders'] = 'Zeige alle Ordner';
    $t['filter'] = 'Filter';
    $t['showing'] = 'Angezeigt';
    $t['title'] = 'Titel';
    $t['folder'] = 'Ordner';
    $t['date'] = 'Datum';
    $t['actions'] = 'Aktionen';
    $t['no_pages_found'] = 'Keine Seiten gefunden';
    $t['published'] = 'Ver&ouml;ffentlicht';
    $t['unpublished'] = 'Unver&ouml;ffentlicht';
    $t['confirm_delete_page'] = 'Sind Sie sicher, dass Sie diese Seite l&ouml;schen m&ouml;chten'; // No question mark please
    $t['confirm_delete_selected_pages'] = 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Seiten l&ouml;schen m&ouml;chten?';
    $t['remove_template'] = 'Template entfernen';
    $t['template_missing'] = 'Template nicht gefunden';
    $t['prev'] = 'Vorherige'; // Pagination button
    $t['next'] = 'N&auml;chste'; // Pagination button

    // Pages
    $t['welcome'] = 'Willkommen';
    $t['no_regions_defined'] = 'Keine editierbaren Bereiche definiert';
    $t['no_templates_defined'] = 'Es werden keine Templates vom CMS verwaltet';
    $t['access_level'] = 'Berechtigungsstufe';
    $t['superadmin'] = 'Super Admin';
    $t['admin'] = 'Administrator';
    $t['authenticated_user_special'] = 'Registrierter Nutzer (Spezial)';
    $t['authenitcated_user'] = 'Registrierter Nutzer';
    $t['unauthenticated_user'] = 'Jeder';
    $t['allow_comments'] = 'Erlaube Kommentare';
    $t['status'] = 'Status';
    $t['name'] = 'Name';
    $t['title_desc'] = 'Lassen Sie dieses Feld leer um den vom System generierten Titel zu verwenden';
    $t['required'] = 'ben&ouml;tigt'; // Required field
    $t['required_msg'] = 'Ben&ouml;tigtes Feld kann nicht leer gelassen werden';
    $t['browse_server'] = 'Server durchsuchen';
    $t['view_image'] = 'Bild ansehen';
    $t['thumb_created_auto'] = 'Wird automatisch generiert';
    $t['recreate'] = 'Neu generieren';
    $t['thumb_recreated'] = 'Vorschaubild neu generiert';
    $t['crop_from'] = 'Zuschneiden von';
    $t['top_left'] = 'Oben links';
    $t['top_center'] = 'Oben mitte';
    $t['top_right'] = 'Oben rechts';
    $t['middle_left'] = 'Mitte links';
    $t['middle'] = 'Mitte';
    $t['middle_right'] = 'Mitte rechts';
    $t['bottom_left'] = 'Unten links';
    $t['bottom_center'] = 'Unten mitte';
    $t['bottom_right'] = 'Unten rechts';
    $t['view_thumbnail'] = 'Vorschaubild ansehen';
    $t['field_not_found'] = 'Feld nicht gefunden!';
    $t['delete_permanently'] = 'Dauerhaft l&ouml;schen?';
    $t['view_code'] = 'Zeige Code';
    $t['confirm_delete_field'] = 'Sind Sie sicher, dass Sie dieses Feld dauerhaft l&ouml;schen m&ouml;chten?';
    $t['save'] = 'Speichern';

    // Comments
    $t['all'] = 'Alle';
    $t['unapprove'] = 'Zur&uuml;ckweisen';
    $t['unapproved'] = 'Zur&uuml;ckgewiesene';
    $t['approve'] = 'Genehmigen';
    $t['approved'] = 'Genehmigte';
    $t['select-deselect'] = 'Alle aus-/abw&auml;hlen';
    $t['confirm_delete_comment'] = 'Sind Sie sicher, dass Sie diesen Kommentar l&ouml;schen m&ouml;chten?';
    $t['confirm_delete_selected_comments'] = 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Kommentare l&ouml;schen m&ouml;chten?';
    $t['bulk_action'] = 'Massenverarbeitung mit ausgew&auml;hlten';
    $t['apply'] = 'Anwenden';
    $t['submitted_on'] = 'Hinzugef&uuml;gt am';
    $t['email'] = 'E-Mail';
    $t['website'] = 'Website';
    $t['duplicate_content'] = 'Doppelter Inhalt';
    $t['insufficient_interval'] = 'Kein ausreichender zeitlicher Abstand zwischen den Kommentaren';

    // Users
    $t['user_name_restrictions'] = 'Nur Kleinbuchstaben, Zahlen, Bindestriche und Unterstriche erlaubt';
    $t['display_name'] = 'Anzeigename';
    $t['role'] = 'Rolle';
    $t['no_users_found'] = 'Keine Benutzer gefunden';
    $t['confirm_delete_user'] = 'Sind Sie sicher, dass Sie den Benutzer l&ouml;schen m&ouml;chten'; // No question mark please
    $t['confirm_delete_selected_users'] = 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Benutzer l&ouml;schen m&ouml;chten?';
    $t['disabled'] = 'Deaktiviert';
    $t['new_password'] = 'Neues Passwort';
    $t['new_password_msg'] = 'Nur wenn Sie Ihr Passwort &auml;ndern m&ouml;chten, ansonsten lassen Sie dieses Feld bitte leer.';
    $t['repeat_password'] = 'Passwort wiederholen';
    $t['repeat_password_msg'] = 'Geben Sie Ihr neues Passwort erneut ein.';
    $t['user_name_exists'] = 'Benutzername bereits vorhanden';
    $t['email_exists'] = 'E-Mail bereits vorhanden';

    // Login
    $t['user_name'] = 'Benutzername';
    $t['password'] = 'Passwort';
    $t['login'] = 'Anmelden';
    $t['forgot_password'] = 'Passwort vergessen?';
    $t['prompt_cookies'] = 'Cookies müssen aktiviert sein, um dieses CMS nutzen zu können';
    $t['prompt_username'] = 'Bitte geben Sie ihren Benutzername ein';
    $t['prompt_password'] = 'Bitte geben Sie ihr Passwort ein';
    $t['invalid_credentials'] = 'Benutzername oder Passwort falsch';
    $t['account_disabled'] = 'Account deaktiviert';
    $t['access_denied'] = 'Zugriff verweigert';
    $t['insufficient_privileges'] = 'Sie verf&uuml;gen nicht &uuml;ber die notwendigen Berechtigungen um die gew&uuml;schte Seite anzuzeigen.
                                    Um sie zu sehen, loggen Sie sich bitte aus und loggen Sie sich mit den notwendigen Berechtigungen wieder ein.';

    // Password recovery
    $t['recovery_prompt'] = 'Bitte geben Sie Ihren Benutzernamen oder Ihre E-Mail-Adresse ein,
                            Sie werden dann Ihr Passwort per E-Mail erhalten.';
    $t['name_or_email'] = 'Ihr Benutzername oder Ihre E-Mail-Adresse';
    $t['submit'] = 'Senden';
    $t['submit_error'] = 'Bitte geben Sie Ihren Benutzername oder Ihre E-Mail-Adresse ein.';
    $t['no_such_user'] = 'Dieser Benutzer existiert nicht.';
    $t['reset_req_email_subject'] = 'Neues Passwort angefordert';
    $t['reset_req_email_msg_0'] = 'Es wurde eine Anfrage gesendet, Ihr Passwort für folgende Seite und folgenden Benutzer zurückzusetzen';
    $t['reset_req_email_msg_1'] = 'Um zu bestätigen, dass die Anfrage von Ihnen gesendet wurde rufen Sie bitte die folgende Seite auf. Ansonsten ignorieren Sie diese E-Mail einfach.';
    $t['email_failed'] = 'E-Mail konnte nicht gesendet werden.';
    $t['reset_req_email_confirm'] = 'Eine Best&auml;tigungsmail wurde an Sie gesendet,<br/>
                                    bitte kontrollieren Sie Ihr Postfach.';
    $t['invalid_key'] = 'Fehlerhafter Key';
    $t['reset_email_subject'] = 'Ihr neues Passwort';
    $t['reset_email_msg_0'] = 'Ihr Passwort wurde für die folgende Seite und folgenden Benutzer zurückgesetzt';
    $t['reset_email_msg_1'] = 'Nachdem Sie sich angemeldet haben, können Sie Ihr Passwort ändern.';
    $t['reset_email_confirm'] = 'Ihr Passwort wurde zur&uuml;ckgesetzt,<br/>
                                bitte kontrollieren Sie Ihre E-Mails, um das neue Passwort zu erfahren.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>Wartungsmodus</h2>
                        <p>
                            Bitte entschuldigen Sie die St&ouml;rung.<br/>
                            Unsere Website wird gerade planm&auml;&szlig;ig gewartet.<br/>
                            <b>Bitte versuchen Sie es sp&auml;ter erneut.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Admin Panel';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'Keine Ordner definiert';
    $t['select_folder'] = 'Wähle Ordner';
    $t['folders'] = 'Ordner';
    $t['manage_folders'] = 'Ordner verwalten';
    $t['add_new_folder'] = 'Neuer Ordner';
    $t['parent_folder'] = '&Uuml;bergeordneter Ordner';
    $t['weight'] = 'Gewicht';
    $t['weight_desc'] = 'Je h&ouml;her der Wert, desto weiter unten erscheint der Ordner in der Liste. Auch negative Werte sind erlaubt.';
    $t['desc'] = 'Beschreibung';
    $t['image'] = 'Grafik';
    $t['cannot_be_own_parent'] = 'Kann sich nicht selbst &uuml;bergeordnet werden';
    $t['name_already_exists'] = 'Der Name existiert bereits';
    $t['pages'] = 'Seiten';
    $t['none'] = 'Keine';
    $t['confirm_delete_folder'] = 'Sind Sie sicher, dass Sie den Ordner l&ouml;schen m&ouml;chten'; // No question mark please
    $t['confirm_delete_selected_folders'] = 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Ordner l&ouml;schen m&ouml;chten?';

    // Drafts
    $t['draft_caps'] = 'ENTWURF'; // Upper case
    $t['draft'] = 'Entwurf';
    $t['drafts'] = 'Entw&uuml;rfe';
    $t['create_draft'] = 'Neuer Entwurf';
    $t['create_draft_msg'] = 'Erstelle eine Kopie dieser Seite (nachdem die &Auml;nderungen gespeichert wurden)';
    $t['manage_drafts'] = 'Entw&uuml;rfe verwalten'; // Plural
    $t['update_original'] = 'Original aktualisieren';
    $t['update_original_msg'] = 'Kopiere den Inhalt dieses Entwurfs in das Original der Seite (und l&uml;sche den Entwurf)';
    $t['recreate_original'] = 'Original wiederherstellen';
    $t['no_drafts_found'] = 'Keine Entw&uuml;rfe gefunden';
    $t['original_page'] = 'Original Seite';
    $t['template'] = 'Template';
    $t['modified'] = 'Modifiziert'; // Date of last modification
    $t['preview'] = 'Vorschau';
    $t['confirm_delete_draft'] = 'Sind Sie sicher, dass Sie den Entwurf l&ouml;schen m&ouml;chten'; // No question mark please
    $t['confirm_delete_selected_drafts'] = 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Entw&uuml;rfe l&ouml;schen m&ouml;chten?';
    $t['confirm_apply_selected_drafts'] = 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Entw&uuml;rfe anwenden m&ouml;chten?';
    $t['view_all_drafts'] = 'Zeige alle Entw&uuml;rfe';
    $t['original_deleted'] = 'ORIGINAL GEL&Ouml;scht'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = '&Uuml;bergeordnete Seite';
    $t['page_weight_desc'] = 'Je h&ouml;her der Wert, desto weiter unten erscheint die Seite in der Liste. Auch negative Werte sind erlaubt.';
    $t['active'] = 'Aktiv';
    $t['inactive'] = 'Inaktiv';
    $t['menu'] = 'Men&uuml;';
    $t['menu_text'] = 'Men&uuml; Text';
    $t['show_in_menu'] = 'Zeige im Men&uuml;';
    $t['not_shown_in_menu'] = 'Wird nicht im Men&uuml; angezeigt';
    $t['leave_empty'] = 'Leer lassen um Seitentitel zu nutzen';
    $t['menu_link'] = 'Men&uuml; Link';
    $t['link_url'] = 'Diese Seite zeigt zu folgendem Ziel';
    $t['link_url_desc'] = 'Kann leer gelassen werden';
    $t['separate_window'] = '&Ouml;ffne in separatem Fenster';
    $t['pointer_page'] = 'Zielseite';
    $t['points_to_another_page'] = 'Zeigt zu anderer Seite';
    $t['points_to'] = 'Zeigt zu';
    $t['redirects'] = 'Umleiten';
    $t['masquerades'] = 'Maskiert';
    $t['strict_matching'] = 'Auswahl treffen f&uuml;r alle Seiten unter diesem Link';
    $t['up'] = 'Nach oben';
    $t['down'] = 'Nach unten';
    $t['remove_template_completely'] = 'L&ouml;sche alle Seiten und Entw&uuml;rfe dieses Templates vollst&auml;ndig';
    $t['remove_uncloned_template_completely'] = 'L&ouml;sche alle Entw&uuml;rfe dieses Templates vollst&auml;ndig';
