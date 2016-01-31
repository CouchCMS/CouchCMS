<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Witaj';
    $t['view_site'] = 'Zobacz Stronę';
    $t['logout'] = 'Wyloguj';
    $t['javascript_msg'] = 'JavaScript jest wyłączony albo Twoja przeglądarka go nie wspiera.<br/>
                            Zaktualizuj przeglądarke albo <a title="Włącz JavaScript w przeglądarce" href="http://www.google.com/support/bin/answer.py?answer=23852"><b>włącz JavaScript</b></a> aby używać panelu admińskiego.';
    $t['add_new'] = 'Dodaj';
    $t['add_new_page'] = 'Dodaj nową stronę';
    $t['add_new_user'] = 'Dodaj nowego użytkownika';
    $t['view'] = 'Zobacz';
    $t['list'] = 'Lista';
    $t['edit'] = 'Edytuj';
    $t['delete'] = 'Usuń';
    $t['delete_selected'] = 'Usuń zaznaczone';
    $t['advanced_settings'] = 'Zaawansowane ustawienia';

    // Sidebar
    $t['comment'] = 'Komentarz';
    $t['comments'] = 'Komentarze';
    $t['manage_comments'] = 'Zarządzaj komentarzami';
    $t['users'] = 'Użytkownicy';
    $t['manage_users'] = 'Zarządzaj Użytkownikami';

    // List pages
    $t['view_all_folders'] = 'Zobacz wszystkie katalogi';
    $t['filter'] = 'Filtruj';
    $t['showing'] = 'Oglądane';
    $t['title'] = 'Tytuł';
    $t['folder'] = 'Katalog';
    $t['date'] = 'Data';
    $t['actions'] = 'Akcje';
    $t['no_pages_found'] = 'Nie znaleziono stron';
    $t['published'] = 'Opublikowane';
    $t['unpublished'] = 'Nieopublikowane';
    $t['confirm_delete_page'] = 'Czy na pewno chcesz usunąć stronę?'; // No question mark please
    $t['confirm_delete_selected_pages'] = 'Czy na pewno chcesz usunąć zaznaczone strony?';
    $t['remove_template'] = 'Usuń szablon';
    $t['template_missing'] = 'Szablon nie istnieje';
    $t['prev'] = 'Poprzedni'; // Pagination button
    $t['next'] = 'Następny'; // Pagination button

    // Pages
    $t['welcome'] = 'Witaj';
    $t['no_regions_defined'] = 'Brak zdefiniowanych pól do edycji';
    $t['no_templates_defined'] = 'Brak szablonów zarządzanych przez CMS';
    $t['access_level'] = 'Poziom dostępu';
    $t['superadmin'] = 'Super Admin';
    $t['admin'] = 'Administrator';
    $t['authenticated_user_special'] = 'Zalogowany użytkonik (specjalny)';
    $t['authenitcated_user'] = 'Zalogowany użytkownik';
    $t['unauthenticated_user'] = 'Ktokolwiek';
    $t['allow_comments'] = 'Zezwól użytkownikom na komentowanie';
    $t['status'] = 'Status';
    $t['name'] = 'Nazwa';
    $t['title_desc'] = 'zostaw to pole puste aby system wygenerował nazwę na podstawie tytułu';
    $t['required'] = 'wymagane'; // Required field
    $t['required_msg'] = 'Wymagane pole nie może być puste';
    $t['browse_server'] = 'Przeglądaj serwer';
    $t['view_image'] = 'Zobacz zdjęcie';
    $t['thumb_created_auto'] = 'Będą utworzone automatycznie';
    $t['recreate'] = 'Utwórz ponownie';
    $t['thumb_recreated'] = 'Miniaturka utworzona ponownie';
    $t['crop_from'] = 'format przycięcia';
    $t['top_left'] = 'Góra Lewo';
    $t['top_center'] = 'Góra Środek';
    $t['top_right'] = 'Góra Prawo';
    $t['middle_left'] = 'Środek Lewo';
    $t['middle'] = 'Środek';
    $t['middle_right'] = 'Środek Prawo';
    $t['bottom_left'] = 'Dół Lewo';
    $t['bottom_center'] = 'Dół Środek';
    $t['bottom_right'] = 'Dół Prawo';
    $t['view_thumbnail'] = 'Zobacz miniaturkę';
    $t['field_not_found'] = 'Pole nieznalezione!';
    $t['delete_permanently'] = 'Usunąć permamentnie?';
    $t['view_code'] = 'Zobacz kod';
    $t['confirm_delete_field'] = 'Czy jesteś pewien że chcesz usunąć ten pole permamentnie?';
    $t['save'] = 'Zapisz';

    // Comments
    $t['all'] = 'Wszystkie';
    $t['unapprove'] = 'Odrzuć';
    $t['unapproved'] = 'Odrzucone';
    $t['approve'] = 'Zaakceptuj';
    $t['approved'] = 'Zaakceptowane';
    $t['select-deselect'] = 'Zaznacz/Odznacz wszystkie';
    $t['confirm_delete_comment'] = 'Czy na pewno chcesz usunąć ten komentarz?';
    $t['confirm_delete_selected_comments'] = 'Czy na pewno chcesz usunąć zaznaczone komentarze?';
    $t['bulk_action'] = 'Grupowa akcja z zaznaczonych';
    $t['apply'] = 'Nanieś';
    $t['submitted_on'] = 'Zapisane';
    $t['email'] = 'E-Mail';
    $t['website'] = 'Strona';
    $t['duplicate_content'] = 'Zduplikowana zawartość';
    $t['insufficient_interval'] = 'Niewystarczający odstęp czasu między komentarzami';

    // Users
    $t['user_name_restrictions'] = 'Tylko małe litery, cyfry, myślnik i podkreślenie są dozwolone';
    $t['display_name'] = 'Wyświetl nazwę';
    $t['role'] = 'Rola';
    $t['no_users_found'] = 'Nie znaleziono użytkowników';
    $t['confirm_delete_user'] = 'Czy jesteś pewien że chcesz usunąć użytkownika?'; // No question mark please
    $t['confirm_delete_selected_users'] = 'Czy jesteś pewien że chcesz usunąć zaznaczonych użytkowników?';
    $t['disabled'] = 'Wyłączony';
    $t['new_password'] = 'Nowe hasło';
    $t['new_password_msg'] = 'Jeśli chcesz zmienić swoje hasło to wpisz tutaj nowe. W przeciwnym wypadku zostaw to pole puste.';
    $t['repeat_password'] = 'Powtórz hasło';
    $t['repeat_password_msg'] = 'Wpisz ponownie nowe hasło.';
    $t['user_name_exists'] = 'Użytkownik już istnieje';
    $t['email_exists'] = 'E-Mail już jest zarejestrowany.';

    // Login
    $t['user_name'] = 'Nazwa użytkonika';
    $t['password'] = 'Hasło';
    $t['login'] = 'Login';
    $t['forgot_password'] = 'Zapomniałeś hasła?';
    $t['prompt_cookies'] = 'Ciasteczka muszą być włączone do poprawnego działania CMS';
    $t['prompt_username'] = 'Proszę wprowadź nazwę użytkownika';
    $t['prompt_password'] = 'Proszę wprowadź hasło';
    $t['invalid_credentials'] = 'Niepoprawny użytkownik albo hasło';
    $t['account_disabled'] = 'Konto wyłączone';
    $t['access_denied'] = 'Dostęp zablokowany';
    $t['insufficient_privileges'] = 'Nie masz wystarczających uprawnień aby przeglądać tą stronę.
                                    Aby ją zobaczyć musisz się zalogować na konto z odpowiednimi uprawnieniami.';

    // Password recovery
    $t['recovery_prompt'] = 'Proszę wpisz nazwę użytkownika albo adres e-mail.<br/>
                            Dostaniesz Swoje hasło w wiadomości e-mail.';
    $t['name_or_email'] = 'Nazwa użytkownika albo adres e-mail';
    $t['submit'] = 'Zapisz';
    $t['submit_error'] = 'Proszę wprowadź nazwę użytkownika albo adres e-mail.';
    $t['no_such_user'] = 'Użytkownik nie istnieje.';
    $t['reset_req_email_subject'] = 'Zmiana hasła';
    $t['reset_req_email_msg_0'] = 'Otrzymaliśmy rządanie zmiany hasła dla następującej strony i użytkownika';
    $t['reset_req_email_msg_1'] = 'Aby potwierdzić, że to Ty wysłałeś rządanie prosimy o odwiedzenie następującego adresu, w innym przypadku zignoruj tą wiadomość.';
    $t['email_failed'] = 'E-Mail nie mógł zostać wysłany.';
    $t['reset_req_email_confirm'] = 'Wiadomość e-mail z potwierdzeniem została wysłana.<br/>
                                    Sprawdź Swoją skrzynkę pocztową.';
    $t['invalid_key'] = 'Niepoprawny klucz';
    $t['reset_email_subject'] = 'Twoje nowe hasło';
    $t['reset_email_msg_0'] = 'Twoje hasło zostało zmienione dla następującej strony i użytkownika';
    $t['reset_email_msg_1'] = 'Możesz zmienić hasło po zalogowaniu się.';
    $t['reset_email_confirm'] = 'Twoje hasło zostało zresetowane.<br/>
                                Sprawdź Swoją skrzynkę pocztową.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>Strona w przebudowie</h2>
                        <p>
                            Przepraszamy za niedogodności.<br/>
                            Nasza strona jest aktualnie w przebudowie.<br/>
                            <b>Prosimy spróbować ponownie później.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Panel admiński';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'Brak zdefiniowanych katalogów';
    $t['select_folder'] = 'Zaznacz katalog';
    $t['folders'] = 'Katalogi';
    $t['manage_folders'] = 'Zarządzaj katalogami';
    $t['add_new_folder'] = 'Dodaj nowy katalog';
    $t['parent_folder'] = 'Nadrzędny katalog';
    $t['weight'] = 'Priorytet';
    $t['weight_desc'] = 'Im większa wartość tym katalog znajdzie się niżej na liście. Wartość może być ujemna.';
    $t['desc'] = 'Opis';
    $t['image'] = 'Zdjęcie';
    $t['cannot_be_own_parent'] = 'Nie może być swoim rodzicem';
    $t['name_already_exists'] = 'Nazwa już istnieje';
    $t['pages'] = 'Strony';
    $t['none'] = 'Pusto';
    $t['confirm_delete_folder'] = 'Czy jesteś pewien że chcesz usunąć katalog'; // No question mark please
    $t['confirm_delete_selected_folders'] = 'Czy jesteś pewien że chcesz usunąć zaznaczone katalogi?';

    // Drafts
    $t['draft_caps'] = 'WERSJA ROBOCZA'; // Upper case
    $t['draft'] = 'Wersja robocza';
    $t['drafts'] = 'Wersje robocze';
    $t['create_draft'] = 'Utwórz wersję roboczą';
    $t['create_draft_msg'] = 'Utwórz kopię tej strony (po zapisie zmian)';
    $t['manage_drafts'] = 'Zarządzaj wersjami roboczymi'; // Plural
    $t['update_original'] = 'Zaktualizuj oryginał';
    $t['update_original_msg'] = 'Copy the contents of this draft to the original page (and delete draft)';
    $t['recreate_original'] = 'Utwórz ponownie oryginał';
    $t['no_drafts_found'] = 'Nie znaleziono wersji roboczych';
    $t['original_page'] = 'Oryginalna strona';
    $t['template'] = 'Szablon';
    $t['modified'] = 'Zmodyfikowana'; // Date of last modification
    $t['preview'] = 'Podgląd';
    $t['confirm_delete_draft'] = 'Czy jesteś pewien że chcesz usunąć tą wersję roboczą?';
    $t['confirm_delete_selected_drafts'] = 'Czy jesteś pewien że chcesz usunąć zaznaczone wersje robocze?';
    $t['confirm_apply_selected_drafts'] = 'Czy jesteś pewien że chcesz zmienić zaznaczone wersje robocze?';
    $t['view_all_drafts'] = 'Zobacz wszystkie wersje robocze';
    $t['original_deleted'] = 'ORYGINAŁ USUNIĘTY'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = 'Strona nadrzędna';
    $t['page_weight_desc'] = 'Im większa wartość tym strona znajdzie się niżej na liście. Wartość może być ujemna.';
    $t['active'] = 'Aktywna';
    $t['inactive'] = 'Wyłączona';
    $t['menu'] = 'Menu';
    $t['menu_text'] = 'Treść menu';
    $t['show_in_menu'] = 'Pokaż w menu';
    $t['not_shown_in_menu'] = 'Ukryte w menu';
    $t['leave_empty'] = 'Zostaw puste aby użyć tytułu strony';
    $t['menu_link'] = 'odnośnik menu';
    $t['link_url'] = 'Ten odnośnik wskazuje na następującą stronę';
    $t['link_url_desc'] = 'Może pozostać pusty';
    $t['separate_window'] = 'Otwórz w nowym oknie';
    $t['pointer_page'] = 'Strona docelowa';
    $t['points_to_another_page'] = 'Wskazuje na inną stronę';
    $t['points_to'] = 'Wskazuje na';
    $t['redirects'] = 'Przekierowania';
    $t['masquerades'] = 'Masquerades';
    $t['strict_matching'] = 'Oznacz jako aktywny w menu dla wszystkich stron pod tym odnośnikiem';
    $t['up'] = 'Do góry';
    $t['down'] = 'Na dół';
    $t['remove_template_completely'] = 'Czy chcesz usunąć wszystkie strony i wersje robocze tego szablonu aby go całkowicie usunąć';
    $t['remove_uncloned_template_completely'] = 'Usuń wszystkie wersje robocze tego szablonu aby usunąć go całkowicie';

    // Addendum to Version 1.2.5 /////////////////////////////////////
    // Gallery
    $t['bulk_upload'] = 'Załaduj';
    $t['folder_empty'] = 'Ten katalog jest pusty. Prosimy o użycie przycisku powyżej do wgrania nowych zdjęć.';
    $t['root'] = 'główny katalog';
    $t['item'] = 'zdjęcie'; // Single
    $t['items'] = 'zdjęcia'; // Multiple
    $t['container'] = 'katalog'; // Single
    $t['containers'] = 'katalogi'; // Multiple

    //
    $t['columns_missing'] = 'Brakuje niektórych kolumn!';
    $t['confirm_delete_columns'] = 'Czy jesteś pewien że chcesz permamentnie usunąć brakujące kolumny?';
    $t['add_row'] = 'Dodaj wiersz';
