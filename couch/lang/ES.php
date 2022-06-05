<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // Spanish translation courtesy Jose D. Masa <info@masatrad.com>

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Hola';
    $t['view_site'] = 'Ver p&aacute;gina';
    $t['logout'] = 'Salir';
    $t['javascript_msg'] = 'JavaScript est&aacute; deshabilitado o no es compatible con su navegador.<br/>
                            Actualice su navegador o <a href="https://support.google.com/answer/23852" target="_blank">habilite JavaScript</a> para usar el Panel de administraci&oacute;n.';
    $t['add_new'] = 'A&ntilde;adir';
    $t['add_new_page'] = 'A&ntilde;adir una nueva p&aacute;gina';
    $t['add_new_user'] = 'A&ntilde;adir un nuevo usuario';
    $t['view'] = 'Ver';
    $t['list'] = 'Lista';
    $t['edit'] = 'Editar';
    $t['delete'] = 'Eliminar';
    $t['delete_selected'] = 'Eliminar selecci&oacute;n';
    $t['advanced_settings'] = 'Configuraci&oacute;n avanzada';

    // Sidebar
    $t['comment'] = 'Comentario';
    $t['comments'] = 'Comentarios';
    $t['manage_comments'] = 'Administrar comentarios';
    $t['users'] = 'Usuarios';
    $t['manage_users'] = 'Administrar usuarios';

    // List pages
    $t['view_all_folders'] = 'Ver todas las carpetas';
    $t['filter'] = 'Filtrar';
    $t['showing'] = 'Mostrando';
    $t['title'] = 'T&iacute;tulo';
    $t['folder'] = 'Carpeta';
    $t['date'] = 'Fecha';
    $t['actions'] = 'Acciones';
    $t['no_pages_found'] = 'No se han encontrado ninguna p&aacute;gina';
    $t['published'] = 'Publicada';
    $t['unpublished'] = 'Sin publicar';
    $t['confirm_delete_page'] = '¿Seguro que quieres eliminar la página'; // No question mark please
    $t['confirm_delete_selected_pages'] = '¿Seguro que quiere eliminar las páginas seleccionadas?';
    $t['remove_template'] = 'Eliminar plantilla';
    $t['template_missing'] = 'Falta plantilla';
    $t['prev'] = 'Sig.'; // Pagination button
    $t['next'] = 'Ant.'; // Pagination button

    // Pages
    $t['welcome'] = 'Bienvenido';
    $t['no_regions_defined'] = 'No se ha definido ninguna regi&oacute;n editable';
    $t['no_templates_defined'] = 'No hay plantillas administradas por CMS';
    $t['access_level'] = 'Nivel de acceso';
    $t['superadmin'] = 'Superadministrador';
    $t['admin'] = 'Administrador';
    $t['authenticated_user_special'] = 'Usuario autenticado (especial)';
    $t['authenitcated_user'] = 'Usuario autenticado';
    $t['unauthenticated_user'] = 'P&uacute;blico';
    $t['allow_comments'] = 'Permitir que los usuarios comenten';
    $t['status'] = 'Estado';
    $t['name'] = 'Nombre';
    $t['title_desc'] = 'Deje este campo en blanco para que el sistema genere un nombre a partir del t&iacute;tulo';
    $t['required'] = 'obligatorio'; // Required field
    $t['required_msg'] = 'No se pueden dejar en blanco los campos obligatorios';
    $t['browse_server'] = 'Explorar servidor';
    $t['view_image'] = 'Ver imagen';
    $t['thumb_created_auto'] = 'Se generar&aacute; autom&aacute;ticamente';
    $t['recreate'] = 'Regenerar';
    $t['thumb_recreated'] = 'Miniatura regenerada';
    $t['crop_from'] = 'recorte desde';
    $t['top_left'] = 'Superior izquierda';
    $t['top_center'] = 'Superior centro';
    $t['top_right'] = 'Superior derecha';
    $t['middle_left'] = 'Medio izquierda';
    $t['middle'] = 'Medio';
    $t['middle_right'] = 'Medio derecha';
    $t['bottom_left'] = 'Inferior izquierda';
    $t['bottom_center'] = 'Inferior centro';
    $t['bottom_right'] = 'Inferior derecha';
    $t['view_thumbnail'] = 'Ver miniatura';
    $t['field_not_found'] = '¡No se encuentra el campo!';
    $t['delete_permanently'] = '¿Eliminar definitivamente?';
    $t['view_code'] = 'Ver c&oacute;digo';
    $t['confirm_delete_field'] = '¿Seguro que desea eliminar este campo definitivamente?';
    $t['save'] = 'Guardar';

    // Comments
    $t['all'] = 'Todos';
    $t['unapprove'] = 'Descartar';
    $t['unapproved'] = 'Descartados';
    $t['approve'] = 'Aprobar';
    $t['approved'] = 'Aprobados';
    $t['select-deselect'] = 'Seleccionar/Deseleccionar todos';
    $t['confirm_delete_comment'] = '¿Seguro que desea eliminar este comentario?';
    $t['confirm_delete_selected_comments'] = '¿Seguro que desea eliminar los comentarios seleccionados?';
    $t['bulk_action'] = 'Acci&oacute;n en bloque para los seleccionados';
    $t['apply'] = 'Aplicar';
    $t['submitted_on'] = 'Enviado el';
    $t['email'] = 'Correo electr&oacute;nico';
    $t['website'] = 'P&aacute;gina web';
    $t['duplicate_content'] = 'Contenido duplicado';
    $t['insufficient_interval'] = 'Intervalo insuficiente entre los comentarios';

    // Users
    $t['user_name_restrictions'] = 'Solo se permiten min&uacute;sculas, n&uacute;meros, guiones y barras bajas';
    $t['display_name'] = 'Nombre para mostrar';
    $t['role'] = 'Rol';
    $t['no_users_found'] = 'No se han encontrado usuarios';
    $t['confirm_delete_user'] = '¿Seguro que desea eliminar el usuario'; // No question mark please
    $t['confirm_delete_selected_users'] = '¿Seguro que desea eliminar los usuarios seleccionados?';
    $t['disabled'] = 'Deshabilitado';
    $t['new_password'] = 'Nueva contrase&ntilde;a';
    $t['new_password_msg'] = 'Para cambiar la contrase&ntilde;a escriba una nueva. Si no d&eacute;jelo en blanco.';
    $t['repeat_password'] = 'Repita la contrase&ntilde;a';
    $t['repeat_password_msg'] = 'Escriba su contrase&ntilde;a de nuevo.';
    $t['user_name_exists'] = 'Ya existe ese nombre de usuario';
    $t['email_exists'] = 'Ya existe esa direcci&oacute;n de correo electr&oacute;nico';

    // Login
    $t['user_name'] = 'Nombre de usuario';
    $t['password'] = 'Contrase&ntilde;a';
    $t['login'] = 'Iniciar sesi&oacute;n';
    $t['forgot_password'] = '¿Olvidó su contraseña?';
    $t['prompt_cookies'] = 'Debe habilitar las cookies para utilizar este CMS';
    $t['prompt_username'] = 'Escriba su nombre de usuario';
    $t['prompt_password'] = 'Escriba su contrase&ntilde;a';
    $t['invalid_credentials'] = 'Nombre de usuario o contrase&ntilde;a incorrectos';
    $t['account_disabled'] = 'Cuenta deshabilitada';
    $t['access_denied'] = 'Acceso denegado';
    $t['insufficient_privileges'] = 'No tiene suficientes privilegios para ver la p&aacute;gina solicitada.
                                    Para ver esta p&aacute;gina debe inicar la sesi&oacute;n con los suficientes privilegios.';

    // Password recovery
    $t['recovery_prompt'] = 'Env&iacute;e su nombre de usuario o direcci&oacute;n de correo electr&oacute;nico<br/>
                            Recibir&aacute; su contrase&ntilde; por correo electr&oacute;nico.';
    $t['name_or_email'] = 'Su nombre de usuario o contrase&ntilde;a';
    $t['submit'] = 'Enviar';
    $t['submit_error'] = 'Escriba su nombre de usuario o direcci&oacute;n de correo electr&oacute;nico.';
    $t['no_such_user'] = 'Ese usuario no existe.';
    $t['reset_req_email_subject'] = 'Se ha solicitado el reseteo de la contrase&ntilde;a';
    $t['reset_req_email_msg_0'] = 'Se ha recibido una solicitud para resetear su contrase&ntilde;a para la p&aacute;gina web y el usuario siguientes';
    $t['reset_req_email_msg_1'] = 'Para confirmar la solicitud visite la siguiente direcci&oacute;n, si no simplemente ignore este correo electr&oacute;nico.';
    $t['email_failed'] = 'No se pudo enviar el correo electr&oacute;nico.';
    $t['reset_req_email_confirm'] = 'Se le ha enviado un correo electr&oacute;nico de confirmaci&oacute;n<br/>
                                    Compruebe su bandeja de entrada.';
    $t['invalid_key'] = 'Clave incorrecta';
    $t['reset_email_subject'] = 'Su nueva contrase&ntilde;a';
    $t['reset_email_msg_0'] = 'Se ha reseteado su contrase&ntilde;a para la p&aacute;gina web y el usuario siguientes';
    $t['reset_email_msg_1'] = 'Podr&aacute; cambiar su contrase&ntilde;a cuando inicie una nueva sesi&oacute;n.';
    $t['reset_email_confirm'] = 'Se ha reseteado su contrase&ntilde;a<br/>
                                Compruebe su bandeja de entrada.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>En proceso de mantenimiento</h2>
                        <p>
                            Les pedimos disculpas por cualquier inconveniente.<br/>
                            Se est&aacute; llevando a cabo un proceso de mantenimiento programado.<br/>
                            <b>Vuelva a intentarlo m&aacute;s tarde.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Admin Panel';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'No se ha definido ninguna carpeta';
    $t['select_folder'] = 'Seleccionar carpetas';
    $t['folders'] = 'Carpetas';
    $t['manage_folders'] = 'Administrar carpetas';
    $t['add_new_folder'] = 'A&ntilde;adir una nueva carpeta';
    $t['parent_folder'] = 'Carpeta principal';
    $t['weight'] = 'Peso';
    $t['weight_desc'] = 'Cuanto mayor sea el valor, inferior ser&aacute; la posici&oacute;n de la carpeta dentro de la lista. Podr&aacute; ser un n&uacute;mero negativo.';
    $t['desc'] = 'Descripci&oacute;n';
    $t['image'] = 'Imagen';
    $t['cannot_be_own_parent'] = 'No puede ser su propia carpeta principal';
    $t['name_already_exists'] = 'Ya existe ese nombre';
    $t['pages'] = 'P&aacute;ginas';
    $t['none'] = 'Ninguna';
    $t['confirm_delete_folder'] = '¿Seguro que desea eliminar la carpeta'; // No question mark please
    $t['confirm_delete_selected_folders'] = '¿Seguro que desea eliminar las carpetas seleccionadas?';

    // Drafts
    $t['draft_caps'] = 'BORRADOR'; // Upper case
    $t['draft'] = 'Borrador';
    $t['drafts'] = 'Borradores';
    $t['create_draft'] = 'Crear borrador';
    $t['create_draft_msg'] = 'Crear una copia de esta p&aacute;gina (despu&eacute;s de guardar los cambios)';
    $t['manage_drafts'] = 'Administrar borradores'; // Plural
    $t['update_original'] = 'Actualizar el original';
    $t['update_original_msg'] = 'Copiar el contenido de este borrador a la p&aacute;gina original (y eliminar el borrador)';
    $t['recreate_original'] = 'Regenerar el original';
    $t['no_drafts_found'] = 'No se ha encontrado ning&uacute;n borrador';
    $t['original_page'] = 'P&aacute;gina original';
    $t['template'] = 'Plantilla';
    $t['modified'] = 'Fecha de modificaci&oacute;n'; // Date of last modification
    $t['preview'] = 'Vista previa';
    $t['confirm_delete_draft'] = '¿Seguro que desea eliminar este borrador'; // No question mark please
    $t['confirm_delete_selected_drafts'] = '¿Seguro que desea eliminar los borradores seleccionados?';
    $t['confirm_apply_selected_drafts'] = '¿Seguro que desea aplicar los borradores seleccionados?';
    $t['view_all_drafts'] = 'Ver todos los borradores';
    $t['original_deleted'] = 'ORIGINAL ELIMINADO'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = 'P&aacute;gina principal';
    $t['page_weight_desc'] = 'Cuanto mayor sea el valor, inferior ser&aacute; la posici&oacute;n de la p&aacute;gina dentro de la lista. Podr&aacute; ser un n&uacute;mero negativo.';
    $t['active'] = 'Activo';
    $t['inactive'] = 'Inactivo';
    $t['menu'] = 'Men&uacute;';
    $t['menu_text'] = 'Texto del men&uacute;';
    $t['show_in_menu'] = 'Mostrar en el men&uacute;';
    $t['not_shown_in_menu'] = 'No se muestra en el men&uacute;';
    $t['leave_empty'] = 'D&eacute;jelo en blanco para usar el t&iacute;tulo de la p&aacute;gina';
    $t['menu_link'] = 'Men&uacute; del enlace';
    $t['link_url'] = 'Esta p&aacute;gina enlaza a la siguiente direcci&oacute;n';
    $t['link_url_desc'] = 'No puede dejarse en blanco';
    $t['separate_window'] = 'Abrir en una ventana nueva';
    $t['pointer_page'] = 'Pointer Page';
    $t['points_to_another_page'] = 'Enlazar a otra p&aacute;gina';
    $t['points_to'] = 'Enlaza a';
    $t['redirects'] = 'Redirecci&oacute;n';
    $t['masquerades'] = 'Enmascarar';
    $t['strict_matching'] = 'Marca como seleccionadas en el men&uacute; las p&aacute;ginas bajo este enlace';
    $t['up'] = 'Subir';
    $t['down'] = 'Bajar';
    $t['remove_template_completely'] = 'Elimine todas las p&aacute;ginas y borradores de esta plantilla para eliminarla completamente';
    $t['remove_uncloned_template_completely'] = 'Elimine todos los borradores de esta plantilla para eliminarla completamente';

    // Addendum to Version 1.2.5 /////////////////////////////////////
    // Gallery
    $t['bulk_upload'] = 'Cargar';
    $t['folder_empty'] = 'Carpeta vac&iacute;a. Utilice el bot&oacute;n Cargar para a&ntilde;adir im&aacute;genes.';
    $t['root'] = 'Ra&iacute;z';
    $t['item'] = 'imagen'; // Single
    $t['items'] = 'im&aacute;genes'; // Multiple
    $t['container'] = 'carpeta'; // Single
    $t['containers'] = 'carpetas'; // Multiple

    //
    $t['columns_missing'] = '¡Faltan algunas columnas!';
    $t['confirm_delete_columns'] = '¿Seguro que desea eliminar las columnas que faltan definitivamente?';
    $t['add_row'] = 'A&ntilde;adir una columna';
