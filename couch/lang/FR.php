<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // French translation courtesy Jérome Millot <jerome.millot38@gmail.com>

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Bonjour';
    $t['view_site'] = 'Voir le site';
    $t['logout'] = 'Déconnexion';
    $t['javascript_msg'] = 'JavaScript est désactivé ou n\'est pas supporté par votre navigateur.<br/>
                            Veuilez mettre à jour votre navigateur ou <a href="https://support.google.com/answer/23852" target="_blank">activer JavaScript</a> pour utiiser l\'administration.';
    $t['add_new'] = 'Ajouter nouveau';
    $t['add_new_page'] = 'Ajouter une nouvelle page';
    $t['add_new_user'] = 'Ajouter un nouvel utilisateur';
    $t['view'] = 'Voir';
    $t['list'] = 'Liste';
    $t['edit'] = 'Éditer';
    $t['delete'] = 'Supprimer';
    $t['delete_selected'] = 'Supprimer sélection';
    $t['advanced_settings'] = 'Réglages avancés';

    // Sidebar
    $t['comment'] = 'Commentaire';
    $t['comments'] = 'Commentaires';
    $t['manage_comments'] = 'Gérer les commentaires';
    $t['users'] = 'Utilisateurs';
    $t['manage_users'] = 'Gérer les utilisateurs';

    // List pages
    $t['view_all_folders'] = 'Voir tous les dossiers';
    $t['filter'] = 'Filtrer';
    $t['showing'] = 'Affichage';
    $t['title'] = 'Titre';
    $t['folder'] = 'Dossier';
    $t['date'] = 'Date';
    $t['actions'] = 'Actions';
    $t['no_pages_found'] = 'Aucune page trouvée';
    $t['published'] = 'Publiée';
    $t['unpublished'] = 'Non publiée';
    $t['confirm_delete_page'] = 'Êtes-vous sûr de vouloir supprimer la page'; // No question mark please
    $t['confirm_delete_selected_pages'] = 'Êtes-vous sûr de vouloir supprimer les pages sélectionnées ?';
    $t['remove_template'] = 'Supprimer gabarit';
    $t['template_missing'] = 'Gabarit manquant';
    $t['prev'] = 'Préc.'; // Pagination button
    $t['next'] = 'Suiv.'; // Pagination button

    // Pages
    $t['welcome'] = 'Bienvenue';
    $t['no_regions_defined'] = 'Aucune région éditable définie';
    $t['no_templates_defined'] = 'Aucun gabarit géré par le CMS';
    $t['access_level'] = 'Niveau d\'accès';
    $t['superadmin'] = 'Super Admin';
    $t['admin'] = 'Administrateur';
    $t['authenticated_user_special'] = 'Utilisateur authentifié (Spécial)';
    $t['authenitcated_user'] = 'Utilisateur authentifié';
    $t['unauthenticated_user'] = 'Tout le monde';
    $t['allow_comments'] = 'Autoriser l\'utilisateur à commenter';
    $t['status'] = 'Statut';
    $t['name'] = 'Nom';
    $t['title_desc'] = 'Laisser ce champ vide pour utiliser le nom généré par le système à partir du titre';
    $t['required'] = 'requis'; // Required field
    $t['required_msg'] = 'Un champ requis ne peut pas être vide';
    $t['browse_server'] = 'Parcourir le Serveur';
    $t['view_image'] = 'Voir Image';
    $t['thumb_created_auto'] = 'Sera créée automatiquement';
    $t['recreate'] = 'Recréer';
    $t['thumb_recreated'] = 'Vignette recréée';
    $t['crop_from'] = 'rogner depuis';
    $t['top_left'] = 'Haut Gauche';
    $t['top_center'] = 'Haut Centre';
    $t['top_right'] = 'Haut Droit';
    $t['middle_left'] = 'Milieu Gauche';
    $t['middle'] = 'Milieu';
    $t['middle_right'] = 'Milieu Droit';
    $t['bottom_left'] = 'Bas Gauche';
    $t['bottom_center'] = 'Bas Centre';
    $t['bottom_right'] = 'Bas Droit';
    $t['view_thumbnail'] = 'Voir Vignette';
    $t['field_not_found'] = 'Champ non trouvé!';
    $t['delete_permanently'] = 'Supprimer définitivement ?';
    $t['view_code'] = 'Voir Code';
    $t['confirm_delete_field'] = 'Êtes-vous sûr de vouloir supprimer définitivement ce champ ?';
    $t['save'] = 'Enregistrer';

    // Comments
    $t['all'] = 'Tout';
    $t['unapprove'] = 'Désapprouver';
    $t['unapproved'] = 'Désapprouvé';
    $t['approve'] = 'Approuver';
    $t['approved'] = 'Approuvé';
    $t['select-deselect'] = 'Sélectionner/Désélectionner Tous';
    $t['confirm_delete_comment'] = 'Êtes-vous sûr de vouloir supprimer ce commentaire ?';
    $t['confirm_delete_selected_comments'] = 'Êtes-vous sûr de vouloir supprimer les commentaires sélectionnés ?';
    $t['bulk_action'] = 'Action groupée avec la sélection';
    $t['apply'] = 'Appliquer';
    $t['submitted_on'] = 'Soumis le';
    $t['email'] = 'E-Mail';
    $t['website'] = 'Site';
    $t['duplicate_content'] = 'Contenu dupliqué';
    $t['insufficient_interval'] = 'Intervalle insuffisant entre les commentaires';

    // Users
    $t['user_name_restrictions'] = 'Seulement lettres minuscules, chiffres, trait d\'union et souligné permis';
    $t['display_name'] = 'Nom affiché';
    $t['role'] = 'Rôle';
    $t['no_users_found'] = 'Aucun utilisateur trouvé';
    $t['confirm_delete_user'] = 'Êtes-vous sûr de vouloir supprimer cet utilisateur'; // No question mark please
    $t['confirm_delete_selected_users'] = 'Êtes-vous sûr de vouloir supprimer les utiisateurs sélectionnés ?';
    $t['disabled'] = 'Désactivé';
    $t['new_password'] = 'Nouveau mot de passe';
    $t['new_password_msg'] = 'Si vous voulez changer de mot de passe, saisir le nouveau. Sinon laisser vide.';
    $t['repeat_password'] = 'Répéter mot de passe';
    $t['repeat_password_msg'] = 'Re-tapez votre nouveau mot de passe.';
    $t['user_name_exists'] = 'Ce nom d\'utilisateur existe déjà';
    $t['email_exists'] = 'Cet Email existe déjà';

    // Login
    $t['user_name'] = 'Nom d\'utilisateur';
    $t['password'] = 'Mot de passe';
    $t['login'] = 'Connexion';
    $t['forgot_password'] = 'Mot de passe oublié ?';
    $t['prompt_cookies'] = 'Les Cookies doivent être activés pour utiliser ce CMS';
    $t['prompt_username'] = 'Veuillez saisir votre nom d\'utilisateur';
    $t['prompt_password'] = 'Veuillez saisir votre mot de passe';
    $t['invalid_credentials'] = 'Nom d\'utilisateur ou mot de passe invalide';
    $t['account_disabled'] = 'Compte désactivé';
    $t['access_denied'] = 'Accès refusé';
    $t['insufficient_privileges'] = 'Vous n\'avez pas les privilèges suffisants pour voir la page demandée.
                                    Pour voir cette page vous devez vous déconnecter et vous reconnecter avec des privilèges suffisants.';

    // Password recovery
    $t['recovery_prompt'] = 'Veuillez saisir votre nom d\'utilisateur ou votre adresse email.<br/>
                            Vous recevrez votre mot de passe par email.';
    $t['name_or_email'] = 'Votre nom d\'utilisateur ou email';
    $t['submit'] = 'Envoyer';
    $t['submit_error'] = 'Veuillez saisir votre nom d\'utilisateur ou votre adresse email.';
    $t['no_such_user'] = 'Aucun utilisateur correspondant.';
    $t['reset_req_email_subject'] = 'Réinitialisation mot de passe';
    $t['reset_req_email_msg_0'] = 'Une requête a été reçue afin de réinitialiser votre mot de passe pour le site et nom d\'utilisateur suivants';
    $t['reset_req_email_msg_1'] = 'Pour confirmer que cette requête vient bien de vous, veuillez vous rendre à l\'adresse suivante, sinon ignorez simplement cet email.';
    $t['email_failed'] = 'L\'email n\'a pas pû être envoyé.';
    $t['reset_req_email_confirm'] = 'Un mail de confirmation vous a été envoyé<br/>
                                    Veuillez vérifier votre boite email.';
    $t['invalid_key'] = 'Clé invalide';
    $t['reset_email_subject'] = 'Votre nouveau mot de passe';
    $t['reset_email_msg_0'] = 'Votre mot de passe a été réinitialisé pour le site et nom d\'utilisateur suivants';
    $t['reset_email_msg_1'] = 'Une fois connecté vous pourrez changer votre mot de passe.';
    $t['reset_email_confirm'] = 'Votre mot de passe a été réinitialisé<br/>
                                Veuillez vérifier votre boite email pour avoir le nouveau mot de passe.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>Mode Maintenance</h2>
                        <p>
                            Désolé pour le dérangement.<br/>
                            Notre site Internet est en phase de maintenance programmée.<br/>
                            <b>Veuillez réessayer dans quelques instants.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Administration';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'Aucun dossier défini';
    $t['select_folder'] = 'Sélectionner dossier';
    $t['folders'] = 'Dossiers';
    $t['manage_folders'] = 'Gérer les dossiers';
    $t['add_new_folder'] = 'Ajouter un dossier';
    $t['parent_folder'] = 'Dossier parent';
    $t['weight'] = 'Poids';
    $t['weight_desc'] = 'Plus la valeur est haute, plus le dossier apparaitra bas dans la liste. La valeur peut être négative.';
    $t['desc'] = 'Description';
    $t['image'] = 'Image';
    $t['cannot_be_own_parent'] = 'Ne peut pas être son propre parent';
    $t['name_already_exists'] = 'Ce nom existe déjà';
    $t['pages'] = 'Pages';
    $t['none'] = 'Aucun(e)';
    $t['confirm_delete_folder'] = 'Êtes-vous sûr de vouloir supprimer ce dossier'; // No question mark please
    $t['confirm_delete_selected_folders'] = 'Êtes-vous sûr de vouloir supprimer les dossiers sélectionnés ?';

    // Drafts
    $t['draft_caps'] = 'BROUILLON'; // Upper case
    $t['draft'] = 'Brouillon';
    $t['drafts'] = 'Brouillons';
    $t['create_draft'] = 'Créer brouillon';
    $t['create_draft_msg'] = 'Créer une copie de cette page (après enregistrement des modifications)';
    $t['manage_drafts'] = 'Gérer les brouillons'; // Plural
    $t['update_original'] = 'Modifier original';
    $t['update_original_msg'] = 'Copier le contenu de ce brouillon sur la page originale (et supprimer ce brouillon)';
    $t['recreate_original'] = 'Recréer original';
    $t['no_drafts_found'] = 'Aucun brouillon trouvé';
    $t['original_page'] = 'Page originale';
    $t['template'] = 'Modèle';
    $t['modified'] = 'Modifié'; // Date of last modification
    $t['preview'] = 'Prévisualisation';
    $t['confirm_delete_draft'] = 'Êtes-vous sûr de vouloir supprimer ce brouillon'; // No question mark please
    $t['confirm_delete_selected_drafts'] = 'Êtes-vous sûr de vouloir supprimer les brouillons sélectionnés ?';
    $t['confirm_apply_selected_drafts'] = 'Êtes-vous sûr de vouloir appliquer les brouillons sélectionnés?';
    $t['view_all_drafts'] = 'Voir tous les brouillons';
    $t['original_deleted'] = 'ORIGINAL SUPPRIMÉ'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = 'Page parent';
    $t['page_weight_desc'] = 'Plus la valeur est haute, plus la page sera basse dans la liste. Valeur négative possible.';
    $t['active'] = 'Active';
    $t['inactive'] = 'Inactive';
    $t['menu'] = 'Menu';
    $t['menu_text'] = 'Texte Menu';
    $t['show_in_menu'] = 'Voir dans le menu';
    $t['not_shown_in_menu'] = 'Caché dans le menu';
    $t['leave_empty'] = 'Laisser vide pour utiliser le titre de page';
    $t['menu_link'] = 'Menu Lien';
    $t['link_url'] = 'Cette page pointe vers l\'adresse suivante';
    $t['link_url_desc'] = 'Peut être laissé vide';
    $t['separate_window'] = 'Ouvrir dans une nouvelle fenêtre';
    $t['pointer_page'] = 'Pointer Page';
    $t['points_to_another_page'] = 'Pointe vers une autre page';
    $t['points_to'] = 'Pointe vers';
    $t['redirects'] = 'Redirection';
    $t['masquerades'] = 'Affiche le contenu de';
    $t['strict_matching'] = 'Marquer comme sélectionné pour toutes les pages en dessous de ce lien';
    $t['up'] = 'Monter';
    $t['down'] = 'Descendre';
    $t['remove_template_completely'] = 'Supprimer toutes les pages et brouillons de ce template pour le supprimer complètement';
    $t['remove_uncloned_template_completely'] = 'Supprimer tous les brouillons de ce template pour le supprimer complètement';
