<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'Hello';
    $t['view_site'] = 'View Site';
    $t['logout'] = 'Log Out';
    $t['javascript_msg'] = 'JavaScript is disabled or not supported by your browser.
                            Please upgrade your browser or <a href="https://support.google.com/answer/23852" target="_blank">enable JavaScript</a> to use the Admin Panel.';
    $t['add_new'] = 'Add New';
    $t['add_new_page'] = 'Add a new page';
    $t['add_new_user'] = 'Add a new user';
    $t['view'] = 'View';
    $t['list'] = 'List';
    $t['edit'] = 'Edit';
    $t['delete'] = 'Delete';
    $t['delete_selected'] = 'Delete Selected';
    $t['advanced_settings'] = 'Advanced Settings';

    // Sidebar
    $t['comment'] = 'Comment';
    $t['comments'] = 'Comments';
    $t['manage_comments'] = 'Manage Comments';
    $t['users'] = 'Users';
    $t['manage_users'] = 'Manage Users';

    // List pages
    $t['view_all_folders'] = 'View all folders';
    $t['filter'] = 'Filter';
    $t['showing'] = 'Showing';
    $t['title'] = 'Title';
    $t['folder'] = 'Folder';
    $t['date'] = 'Date';
    $t['actions'] = 'Actions';
    $t['no_pages_found'] = 'No pages found';
    $t['published'] = 'Published';
    $t['unpublished'] = 'Unpublished';
    $t['confirm_delete_page'] = 'Are you sure you want to delete page'; // No question mark please
    $t['confirm_delete_selected_pages'] = 'Are you sure you want to delete the selected pages?';
    $t['remove_template'] = 'Remove Template';
    $t['template_missing'] = 'Template missing';
    $t['prev'] = 'Prev'; // Pagination button
    $t['next'] = 'Next'; // Pagination button

    // Pages
    $t['welcome'] = 'Welcome';
    $t['no_regions_defined'] = 'No Editable Regions defined';
    $t['no_templates_defined'] = 'No templates are being managed by the CMS';
    $t['access_level'] = 'Access Level';
    $t['superadmin'] = 'Super Admin';
    $t['admin'] = 'Administrator';
    $t['authenticated_user_special'] = 'Authenticated User (Special)';
    $t['authenitcated_user'] = 'Authenticated User';
    $t['unauthenticated_user'] = 'Everybody';
    $t['allow_comments'] = 'Allow users to comment';
    $t['status'] = 'Status';
    $t['name'] = 'Name';
    $t['title_desc'] = 'leave this field empty to use the system generated name from the title';
    $t['required'] = 'required'; // Required field
    $t['required_msg'] = 'Required field cannot be left empty';
    $t['browse_server'] = 'Browse Server';
    $t['view_image'] = 'View Image';
    $t['thumb_created_auto'] = 'Will be created automatically';
    $t['recreate'] = 'Recreate';
    $t['thumb_recreated'] = 'Thumbnail recreated';
    $t['crop_from'] = 'cropping from';
    $t['top_left'] = 'Top Left';
    $t['top_center'] = 'Top Center';
    $t['top_right'] = 'Top Right';
    $t['middle_left'] = 'Middle Left';
    $t['middle'] = 'Middle';
    $t['middle_right'] = 'Middle Right';
    $t['bottom_left'] = 'Bottom Left';
    $t['bottom_center'] = 'Bottom Center';
    $t['bottom_right'] = 'Bottom Right';
    $t['view_thumbnail'] = 'View Thumbnail';
    $t['field_not_found'] = 'Field not found!';
    $t['delete_permanently'] = 'Delete Permanently?';
    $t['view_code'] = 'View Code';
    $t['confirm_delete_field'] = 'Are you sure you want to permanently delete this field?';
    $t['save'] = 'Save';

    // Comments
    $t['all'] = 'All';
    $t['unapprove'] = 'Un-approve';
    $t['unapproved'] = 'Un-approved';
    $t['approve'] = 'Approve';
    $t['approved'] = 'Approved';
    $t['select-deselect'] = 'Select/Deselect All';
    $t['confirm_delete_comment'] = 'Are you sure you want to delete this comment?';
    $t['confirm_delete_selected_comments'] = 'Are you sure you want to delete the selected comments?';
    $t['bulk_action'] = 'Bulk action with selected';
    $t['apply'] = 'Apply';
    $t['submitted_on'] = 'Submitted on';
    $t['email'] = 'Email Address';
    $t['website'] = 'Website';
    $t['duplicate_content'] = 'Duplicate content';
    $t['insufficient_interval'] = 'Not sufficient interval between comments';

    // Users
    $t['user_name_restrictions'] = 'Only Lowercase characters, numerals, hyphen and underscore permitted';
    $t['display_name'] = 'Display Name';
    $t['role'] = 'Role';
    $t['no_users_found'] = 'No users found';
    $t['confirm_delete_user'] = 'Are you sure you want to delete user'; // No question mark please
    $t['confirm_delete_selected_users'] = 'Are you sure you want to delete the selected users?';
    $t['disabled'] = 'Disabled';
    $t['new_password'] = 'New Password';
    $t['new_password_msg'] = 'If you would like to change the password type a new one. Otherwise leave this blank.';
    $t['repeat_password'] = 'Repeat Password';
    $t['repeat_password_msg'] = 'Type your new password again.';
    $t['user_name_exists'] = 'Username already exists';
    $t['email_exists'] = 'Email address already exists';

    // Login
    $t['user_name'] = 'Username';
    $t['password'] = 'Password';
    $t['login'] = 'Log In';
    $t['forgot_password'] = 'Forgot your password?';
    $t['prompt_cookies'] = 'Cookies must be enabled to use this CMS';
    $t['prompt_username'] = 'Please enter your username';
    $t['prompt_password'] = 'Please enter your password';
    $t['invalid_credentials'] = 'Invalid username or password';
    $t['account_disabled'] = 'Account disabled';
    $t['access_denied'] = 'Access Denied';
    $t['insufficient_privileges'] = 'You do not have sufficient privileges to view the page requested.
                                    To see this page you must log out and log in with sufficient privileges.';

    // Password recovery
    $t['recovery_prompt'] = 'Please submit your username or email address.<br/>
                            You will receive your password by email.';
    $t['name_or_email'] = 'Your Username or Email Address';
    $t['submit'] = 'Submit';
    $t['submit_error'] = 'Please enter your username or email address';
    $t['no_such_user'] = 'No such user exists';
    $t['reset_req_email_subject'] = 'Password reset requested';
    $t['reset_req_email_msg_0'] = 'A request was received to reset your password for the following site and username';
    $t['reset_req_email_msg_1'] = 'To confirm that the request was made by you, please visit the following address, otherwise just ignore this email.';
    $t['email_failed'] = 'Email could not be sent';
    $t['reset_req_email_confirm'] = 'A confirmation email has been sent to you.<br/>
                                    Please check your email inbox.';
    $t['invalid_key'] = 'Invalid key';
    $t['reset_email_subject'] = 'Your new password';
    $t['reset_email_msg_0'] = 'Your password has been reset for the following site and username';
    $t['reset_email_msg_1'] = 'You can change your password once logged in.';
    $t['reset_email_confirm'] = 'Your password has been reset.<br/>
                                Please check your email for the new password.';

    // Maintenance Mode
    $t['back_soon'] = '<h2>Maintenance Mode</h2>
                        <p>
                            Sorry for the inconvenience.<br/>
                            Our website is currently undergoing scheduled maintenance.<br/>
                            <b>Please try back after some time.</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'Admin Panel';
    $t['login_title'] = 'CouchCMS';

    // Folders
    $t['no_folders'] = 'No folders defined';
    $t['select_folder'] = 'Select Folder';
    $t['folders'] = 'Folders';
    $t['manage_folders'] = 'Manage Folders';
    $t['add_new_folder'] = 'Add a new folder';
    $t['parent_folder'] = 'Parent Folder';
    $t['weight'] = 'Weight';
    $t['weight_desc'] = 'Higher the value, lower the folder will appear in list. Can be set to negative.';
    $t['desc'] = 'Description';
    $t['image'] = 'Image';
    $t['cannot_be_own_parent'] = 'Cannot be its own parent';
    $t['name_already_exists'] = 'Name already exists';
    $t['pages'] = 'Pages';
    $t['none'] = 'None';
    $t['confirm_delete_folder'] = 'Are you sure you want to delete folder'; // No question mark please
    $t['confirm_delete_selected_folders'] = 'Are you sure you want to delete the selected folders?';

    // Drafts
    $t['draft_caps'] = 'DRAFT'; // Upper case
    $t['draft'] = 'Draft';
    $t['drafts'] = 'Drafts';
    $t['create_draft'] = 'Create Draft';
    $t['create_draft_msg'] = 'Create a copy of this page (after saving changes)';
    $t['manage_drafts'] = 'Manage Drafts'; // Plural
    $t['update_original'] = 'Update Original';
    $t['update_original_msg'] = 'Copy the contents of this draft to the original page (and delete draft)';
    $t['recreate_original'] = 'Recreate Original';
    $t['no_drafts_found'] = 'No drafts found';
    $t['original_page'] = 'Original Page';
    $t['template'] = 'Template';
    $t['modified'] = 'Modified'; // Date of last modification
    $t['preview'] = 'Preview';
    $t['confirm_delete_draft'] = 'Are you sure you want to delete this draft'; // No question mark please
    $t['confirm_delete_selected_drafts'] = 'Are you sure you want to delete the selected drafts?';
    $t['confirm_apply_selected_drafts'] = 'Are you sure you want to apply the selected drafts?';
    $t['view_all_drafts'] = 'View all drafts';
    $t['original_deleted'] = 'ORIGINAL DELETED'; // Upper case

    // Addendum to Version 1.2 /////////////////////////////////////
    // Nested Pages
    $t['parent_page'] = 'Parent Page';
    $t['page_weight_desc'] = 'Higher the value, lower the page will appear in list. Can be set to negative.';
    $t['active'] = 'Active';
    $t['inactive'] = 'Inactive';
    $t['menu'] = 'Menu';
    $t['menu_text'] = 'Menu Text';
    $t['show_in_menu'] = 'Show in menu';
    $t['not_shown_in_menu'] = 'Not shown in menu';
    $t['leave_empty'] = 'Leave empty to use page title';
    $t['menu_link'] = 'Menu Link';
    $t['link_url'] = 'This page points to the following location';
    $t['link_url_desc'] = 'Can be left empty';
    $t['separate_window'] = 'Open in separate window';
    $t['pointer_page'] = 'Pointer Page';
    $t['points_to_another_page'] = 'Points to another page';
    $t['points_to'] = 'Points to';
    $t['redirects'] = 'Redirects';
    $t['masquerades'] = 'Masquerades';
    $t['strict_matching'] = 'Mark as selected in menu for all pages below this link';
    $t['up'] = 'Move Up';
    $t['down'] = 'Move Down';
    $t['remove_template_completely'] = 'Delete all pages and drafts of this template to remove it completely';
    $t['remove_uncloned_template_completely'] = 'Delete all drafts of this template to remove it completely';

    // Addendum to Version 1.2.5 /////////////////////////////////////
    // Gallery
    $t['bulk_upload'] = 'Upload';
    $t['folder_empty'] = 'This folder is empty. Please use the upload button above to add images.';
    $t['root'] = 'Root';
    $t['item'] = 'image'; // Single
    $t['items'] = 'images'; // Multiple
    $t['container'] = 'folder'; // Single
    $t['containers'] = 'folders'; // Multiple

    //
    $t['columns_missing'] = 'Some columns missing!';
    $t['confirm_delete_columns'] = 'Are you sure you want to permanently delete the missing columns?';
    $t['add_row'] = 'Add a Row';

    // 2.0
    $t['left'] = 'Move Left';
    $t['right'] = 'Move Right';
    $t['crop'] = 'Crop';
    $t['menu_templates'] = 'Templates';
    $t['menu_modules'] = 'Administration';
