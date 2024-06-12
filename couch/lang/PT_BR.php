<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Olá';
    $t['view_site'] = 'Visitar Site';
    $t['logout'] = 'Sair';
    $t['javascript_msg'] = 'JavaScript está desabilitado ou não é suportado pelo seu 
                                navegador.
                            Por favor, atualize seu navegador ou <a href="https://support.google.com/answer/23852" target="_blank">habilite JavaScript</a> para usar o Painel Administrativo.';
    $t['add_new'] = 'Adicionar Novo';
    $t['add_new_page'] = 'Adicionar Nova Página';
    $t['add_new_user'] = 'Adicionar Novo Usuário';
    $t['view'] = 'Visualizar';
    $t['list'] = 'Listar';
    $t['edit'] = 'Editar';
    $t['delete'] = 'Deletar';
    $t['delete_selected'] = 'Deletar Selecionados';
    $t['advanced_settings'] = 'Configurações Avançadas';

    // Sidebar
    $t['comment'] = 'Comentário';
    $t['comments'] = 'Comentários';
    $t['manage_comments'] = 'Gerenciar Comentários';
    $t['users'] = 'Usuários';
    $t['manage_users'] = 'Gerenciar Usuários';

    // List pages
    $t['view_all_folders'] = 'Visualizar todas as pastas';
    $t['filter'] = 'Filtar';
    $t['showing'] = 'Mostrando';
    $t['title'] = 'Título';
    $t['folder'] = 'Pastas';
    $t['date'] = 'Data';
    $t['actions'] = 'Ações';
    $t['no_pages_found'] = 'Nenhuma página encontrada';
    $t['published'] = 'Publicado';
    $t['unpublished'] = 'Despublicado';
    $t['confirm_delete_page'] = 'Você tem certeza que deseja deletar a página'; // No question mark please
    $t['confirm_delete_selected_pages'] = 'Você tem certeza que deseja deletar as páginas selecionadas?';
    $t['remove_template'] = 'Remover Template';
    $t['template_missing'] = 'Template faltando';
    $t['prev'] = 'Anterior'; // Pagination button
    $t['next'] = 'Próximo'; // Pagination button

    // Pages
    $t['welcome'] = 'Bem-vindo';
    $t['no_regions_defined'] = 'Nenhuma região editável definida';
    $t['no_templates_defined'] = 'Nenhum template está sendo gerenciado pelo CMS';
    $t['access_level'] = 'Nível de acesso';
    $t['superadmin'] = 'Super Admin';
    $t['admin'] = 'Administrador';
    $t['authenticated_user_special'] = 'Usuário Autenticado (Especial)';
    $t['authenitcated_user'] = 'Usuário Autenticado';
    $t['unauthenticated_user'] = 'Todos';
    $t['allow_comments'] = 'Permitir comentários de usuários';
    $t['status'] = 'Status';
    $t['name'] = 'Nome';
    $t['title_desc'] = 'deixe este campo vazio para usar o nome gerado pelo sistema pelo título';
    $t['required'] = 'obrigatório'; // Required field
    $t['required_msg'] = 'Campo obrigatório não pode ficar vazio.';
    $t['browse_server'] = 'Procurar Servidor';
    $t['view_image'] = 'Ver imagem';
    $t['thumb_created_auto'] = 'Será criado automáticamente';
    $t['recreate'] = 'Recriar';
    $t['thumb_recreated'] = 'Miniatura recriado';
    $t['crop_from'] = 'recortar de';
    $t['top_left'] = 'Superior Esquerda';
    $t['top_center'] = 'Superior Central';
    $t['top_right'] = 'Superior Direta';
    $t['middle_left'] = 'Meio Esquerda';
    $t['middle'] = 'Meio';
    $t['middle_right'] = 'Meio Direita';
    $t['bottom_left'] = 'Inferior Esquerda';
    $t['bottom_center'] = 'Inferior Central';
    $t['bottom_right'] = 'Inferior Direita';
    $t['view_thumbnail'] = 'Visualizar Miniatura';
    $t['field_not_found'] = 'Campo não encontrado!';
    $t['delete_permanently'] = 'Deletar Permanentemente?';
    $t['view_code'] = 'Ver Código';
    $t['confirm_delete_field'] = ' Você tem certeza que deseja deletar este campo pernamentemente?';
    $t['save'] = 'Salvar';

    // Comments
    $t['all'] = 'Todos';
    $t['unapprove'] = 'Não aprovar';
    $t['unapproved'] = 'Não aprovado';
    $t['approve'] = 'Aprovar';
    $t['approved'] = 'Aprovado';
    $t['select-deselect'] = 'Selecionar/Desmarcar Todos';
    $t['confirm_delete_comment'] = 'Você tem certeza que deseja deletar este comentário?';
    $t['confirm_delete_selected_comments'] = 'Você tem certeza que deseja deletar os comentários selecionados?';
    $t['bulk_action'] = 'Ação em Massa com selecionados';
    $t['apply'] = 'Aplicar';
    $t['submitted_on'] = 'Enviado em';
    $t['email'] = 'Endereço de E-mail';
    $t['website'] = 'Website';
    $t['duplicate_content'] = 'Duplicar conteúdo';
    $t['insufficient_interval'] = 'Não há intervalos suficientes entre os comentários';

    // Users
    $t['user_name_restrictions'] = 'São permitidos apenas caracteres minúsculos, numerais, hífen e sublinhado';
    $t['display_name'] = 'Mostrar Nome';
    $t['role'] = 'Função';
    $t['no_users_found'] = 'Nenhum usuário encontrado';
    $t['confirm_delete_user'] = 'Você tem certeza que deseja deletar o usuário'; // No question mark please
    $t['confirm_delete_selected_users'] = 'Você tem certeza que deseja deletar os usuários selecionados?';
    $t['disabled'] = 'Desabilitar';
    $t['new_password'] = 'Nova Senha';
    $t['new_password_msg'] = 'Se quiser alterar a senha, digite uma nova. Senão, deixe este campo em branco.';
    $t['repeat_password'] = 'Repita a Senha';
    $t['repeat_password_msg'] = 'Digite sua nova senha novamente.';
    $t['user_name_exists'] = 'Este nome de usuário já existe';
    $t['email_exists'] = 'Este endereço de e-mail já existe';

    // Login
    $t['user_name'] = 'Nome de Usuário';
    $t['password'] = 'Senha';
    $t['login'] = 'Entrar';
    $t['forgot_password'] = 'Esqueceu a senha?';
    $t['prompt_cookies'] = 'Cookies devem estar habilitados para usar este CMS';
    $t['prompt_username'] = 'Por favor, insira seu nome de usuário';
    $t['prompt_password'] = 'Por favor, insira sua senha';
    $t['invalid_credentials'] = 'Senha e/ou nome de usuário inválidos';
    $t['account_disabled'] = 'Conta Desativada';
    $t['access_denied'] = 'Acesso Negado';
    $t['insufficient_privileges'] = 'Você não tem permissões suficientes para visualizar a página solicitada.
                                    Para ver esta página você deve sair e entrar novamente com privilégios suficientes.';

    // Password recovery
    $t['recovery_prompt'] = 'Por favor, insira seu nome de usuário ou endereço de e-mail.<br/>
                            Você receberá sua senha por e-mail.';
    $t['name_or_email'] = 'Seu nome de usuário ou Endereço de E-mail';
    $t['submit'] = 'Enviar';
    $t['submit_error'] = 'Por favor, insira seu nome de usuário ou endereço de e-mail';
    $t['no_such_user'] = 'Este usuário não existe';
    $t['reset_req_email_subject'] = 'Solicitação de Redefinição de Senha';
    $t['reset_req_email_msg_0'] = 'Uma solicitação de redefinição de senha foi recebida para o seguinte site e nome de usuário';
    $t['reset_req_email_msg_1'] = 'Para confirmar que a solicitação foi feita por você, por favor, acesse o seguinte endereço. Caso não tenha feito a solicitação, apenas ignore este e-mail.';
    $t['email_failed'] = 'O e-mail não pode ser eviado';
    $t['reset_req_email_confirm'] = 'Um e-mail de confirmação foi enviado para você.<br/>
                                    Por favor, confira sua caixa de entrada.';
    $t['invalid_key'] = 'Chave inválida';
    $t['reset_email_subject'] = 'Sua nova senha';
    $t['reset_email_msg_0'] = 'Sua senha foi redefinida para o seguinte site e nome de usuário';
    $t['reset_email_msg_1'] = 'Acesse sua conta para alterar a senha.';
    $t['reset_email_confirm'] = 'Sua senha foi redefinida.<br/>
                                Por favor, verifique seu e-mail para obter a nova senha.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>Modo Manutenção</h2>
                        <p>
                            Desculpe-nos pelo incômodo.
                            Nosso site está em uma manutenção programada.<br/>
                            <b>Por favor, tente novamente mais tarde.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Painel Administrativo';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'Nenhuma pasta definida';
    $t['select_folder'] = 'Selecionar Pasta';
    $t['folders'] = 'Pastas';
    $t['manage_folders'] = 'Gerenciar Pastas';
    $t['add_new_folder'] = 'Adicionar uma nova pasta';
    $t['parent_folder'] = 'Pasta Pai';
    $t['weight'] = 'Peso';
    $t['weight_desc'] = 'Quanto maior o valor, mais abaixo a pasta irá aparecer na lista. Pode ser definido como valor negativo.';
    $t['desc'] = 'Descrição';
    $t['image'] = 'Imagem';
    $t['cannot_be_own_parent'] = 'Não pode ser seu próprio pai';
    $t['name_already_exists'] = 'Este nome já existe';
    $t['pages'] = 'Páginas';
    $t['none'] = 'Nada';
    $t['confirm_delete_folder'] = 'Você tem certeza que deseja deletar esta pasta'; // No question mark please
    $t['confirm_delete_selected_folders'] = 'Você tem certeza que deseja deletar as pastas selecionadas?';

    // Drafts
    $t['draft_caps'] = 'RASCUNHO'; // Upper case
    $t['draft'] = 'Rascunho';
    $t['drafts'] = 'Rascunhos';
    $t['create_draft'] = 'Criar Rascunho';
    $t['create_draft_msg'] = 'Criar uma cópia desta página (depois de salvar as alterações)';
    $t['manage_drafts'] = 'Gerenciar Rascunhos'; // Plural
    $t['update_original'] = 'Atualizar Original';
    $t['update_original_msg'] = 'Copiar o conteúdo deste rascunho para a página original ( e excluir rascunho)';
    $t['recreate_original'] = 'Recriar Original';
    $t['no_drafts_found'] = 'Nenhum rascunho encontrado';
    $t['original_page'] = 'Página Original';
    $t['template'] = 'Template';
    $t['modified'] = 'Modificado em'; // Date of last modification
    $t['preview'] = 'Visualização';
    $t['confirm_delete_draft'] = 'Você tem certeza que deseja deletar este rascunho'; // No question mark please
    $t['confirm_delete_selected_drafts'] = 'Você tem certeza que deseja deletar os rascunhos selecionados?';
    $t['confirm_apply_selected_drafts'] = 'Você tem certeza que deseja aplicar os rascunhos selecionados?';
    $t['view_all_drafts'] = 'Visualizar todos os rascunhos';
    $t['original_deleted'] = 'ORIGINAL EXCLUÍDO'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = 'Página Pai';
    $t['page_weight_desc'] = 'Quanto maior o valor, mais abaixo a página irá aparecer na lista. Pode ser definido como valor negativo.';
    $t['active'] = 'Ativo';
    $t['inactive'] = 'Inativo';
    $t['menu'] = 'Menu';
    $t['menu_text'] = 'Texto do Menu';
    $t['show_in_menu'] = 'Mostrar no menu';
    $t['not_shown_in_menu'] = 'Não mostrar no menu';
    $t['leave_empty'] = 'Deixe vazio para usar o título da página';
    $t['menu_link'] = 'Link do Menu';
    $t['link_url'] = 'Esta página aponta para o seguinte local';
    $t['link_url_desc'] = 'Não pode deixar vazio';
    $t['separate_window'] = 'Abrir em uma nova janela';
    $t['pointer_page'] = 'Página do apontamento';
    $t['points_to_another_page'] = 'Apontar para outra página';
    $t['points_to'] = 'Apontar para';
    $t['redirects'] = 'Redirecionar';
    $t['masquerades'] = 'Marcaradas';
    $t['strict_matching'] = 'Marcar como selecionadas no menu todas as páginas abaixo deste link';
    $t['up'] = 'Mover para Cima';
    $t['down'] = 'Mover para Baixo';
    $t['remove_template_completely'] = 'Excluir todas as páginas e rascunhos deste template para removê-lo completamente';
    $t['remove_uncloned_template_completely'] = 'Excluir todos os rascunhos deste template para removê-lo completamente';

    // Addendum to Version 1.2.5 /////////////////////////////////////
    // Gallery
    $t['bulk_upload'] = 'Envio';
    $t['folder_empty'] = 'Esta pasta está vazia. Por favor, utilize o botão de envio acima para adicionar imagens.';
    $t['root'] = 'Raiz';
    $t['item'] = 'imagem'; // Single
    $t['items'] = 'imagens'; // Multiple
    $t['container'] = 'pasta'; // Single
    $t['containers'] = 'pastas'; // Multiple

    //
    $t['columns_missing'] = 'Algumas colunas estão faltando!';
    $t['confirm_delete_columns'] = 'Você tem certeza que deseja excluir permanentemente as colunas que estão faltando?';
    $t['add_row'] = 'Adiciona Linha';

    // 2.0
    $t['left'] = 'Mover para a Esquerda';
    $t['right'] = 'Mover para a Direita';
    $t['crop'] = 'Cortar';
    $t['menu_templates'] = 'Templates';
    $t['menu_modules'] = 'Administração';
