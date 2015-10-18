<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    ///////////EDIT BELOW THIS////////////////////////////////////////

    // Header
    $t['greeting'] = 'नमस्ते';
    $t['view_site'] = 'साइट देखें';
    $t['logout'] = 'लॉग आउट';
    $t['javascript_msg'] = 'जावास्क्रिप्ट अक्षम है या आपके ब्राउज़र द्वारा समर्थित नहीं है।<br/>
                            अपने ब्राउज़र को अपग्रेड या कृपया व्यवस्थापक पैनल का उपयोग करने के लिए <a title="Enable JavaScript in your browser" href="http://www.google.com/support/bin/answer.py?answer=23852"><b>जावास्क्रिप्ट सक्षम करें</b></a>';
    $t['add_new'] = 'नया जोडो';
    $t['add_new_page'] = 'नया पृष्ठ जोडो';
    $t['add_new_user'] = 'एक नया उपयोगकर्ता जोडो';
    $t['view'] = 'देखो';
    $t['list'] = 'सूची';
    $t['edit'] = 'संपादन करे';
    $t['delete'] = 'हटाए';
    $t['delete_selected'] = 'चुनिंदा हटाए';
    $t['advanced_settings'] = 'उन्नत सेटिंग';

    // Sidebar
    $t['comment'] = 'टिप्पणी';
    $t['comments'] = 'टीका-टिप्पणी';
    $t['manage_comments'] = 'टिप्पणियों का व्यवस्थापन';
    $t['users'] = 'उपयोगकर्ता';
    $t['manage_users'] = 'उपयोगकर्ताओं का व्यवस्थापन';

    // List pages
    $t['view_all_folders'] = 'सभी फ़ोल्डर देखें';
    $t['filter'] = 'साफ़ करे';
    $t['showing'] = 'प्रदर्शन';
    $t['title'] = 'शीर्षक';
    $t['folder'] = 'फ़ोल्डर';
    $t['date'] = 'दिनांक';
    $t['actions'] = 'कार्रवाई';
    $t['no_pages_found'] = 'कोई पृष्ठ नहीं मिला';
    $t['published'] = 'प्रकाशित';
    $t['unpublished'] = 'अप्रकाशित';
    $t['confirm_delete_page'] = 'क्या आप पृष्ठ को हटाना चाहते हैं'; // No question mark please
    $t['confirm_delete_selected_pages'] = 'आप सुनिश्चित हैं कि आप चयनित पृष्ठों को हटाना चाहते हैं?';
    $t['remove_template'] = 'नमूना हटाए';
    $t['template_missing'] = 'नमूना लापता';
    $t['prev'] = 'पिछला'; // Pagination button
    $t['next'] = 'अगला'; // Pagination button

    // Pages
    $t['welcome'] = 'स्वागत';
    $t['no_regions_defined'] = 'कोई संपादन योग्य क्षेत्र परिभाषित नहीं है';
    $t['no_templates_defined'] = 'कोई भी नमूना सामग्री प्रबंधन प्रणाली द्वारा प्रबन्धित नहीं किया जा रहा है।';
    $t['access_level'] = 'प्रवेश दरजा';
    $t['superadmin'] = 'उच्चस्तरीय व्यवस्थापक';
    $t['admin'] = 'व्यवस्थापक';
    $t['authenticated_user_special'] = 'प्रमाणीकृत उपयोगकर्ता (विशेष)';
    $t['authenitcated_user'] = 'प्रमाणीकृत उपयोगकर्ता';
    $t['unauthenticated_user'] = 'अप्रमाणीकृत उपयोगकर्ता';
    $t['allow_comments'] = 'उपयोगकर्ताओं को टिप्पणी करने कि अनुमति दे';
    $t['status'] = 'अवस्था';
    $t['name'] = 'नाम';
    $t['title_desc'] = 'प्रणाली द्वारा उत्पन्न शीर्षक उपयोग करने के लिए इस क्षेत्र को रिक्त छोड़े';
    $t['required'] = 'अनिवार्य क्षेत्र'; // Required field
    $t['required_msg'] = 'अनिवार्य क्षेत्र को रिक्त नहीं छोडा जा सकता';
    $t['browse_server'] = 'परिसेवक विचरण';
    $t['view_image'] = 'छवि देखें';
    $t['thumb_created_auto'] = 'स्वचालित रूप से बनाया जाएगा';
    $t['recreate'] = 'पुनः निर्माण';
    $t['thumb_recreated'] = 'छोटी छवि का पुनः निर्माण';
    $t['crop_from'] = 'छांटना';
    $t['top_left'] = 'ऊपर का बायां हिस्सा';
    $t['top_center'] = 'ऊपर का मध्य हिस्सा';
    $t['top_right'] = 'ऊपर का दाहिना हिस्सा';
    $t['middle_left'] = 'मध्य का बायां हिस्सा';
    $t['middle'] = 'मध्य हिस्सा';
    $t['middle_right'] = 'मध्य का दाहिना हिस्सा';
    $t['bottom_left'] = 'तह का बायां हिस्सा';
    $t['bottom_center'] = 'तह का मध्य हिस्सा';
    $t['bottom_right'] = 'तह का दाहिना हिस्सा';
    $t['view_thumbnail'] = 'छोटी छवि देखें';
    $t['field_not_found'] = 'क्षेत्र नहीं मिला!';
    $t['delete_permanently'] = 'स्थायी रुप से हटाए?';
    $t['view_code'] = 'संकेतावली देखें';
    $t['confirm_delete_field'] = 'क्या आप इस क्षेत्र को स्थायी रूप से नष्ट करना चाहते हैं?';
    $t['save'] = 'सुरक्षित';

    // Comments
    $t['all'] = 'समस्त';
    $t['unapprove'] = 'अस्वीकृत';
    $t['unapproved'] = 'रद्द';
    $t['approve'] = 'मान्य करे';
    $t['approved'] = 'स्वीकृत';
    $t['select-deselect'] = 'सब का चयन/अचयन करे';
    $t['confirm_delete_comment'] = 'क्या आप इस टिप्पणी को स्थायी रूप से नष्ट करना चाहते हैं?';
    $t['confirm_delete_selected_comments'] = 'क्या आप चयनित टिप्पणियों को स्थायी रूप से नष्ट करना चाहते हैं?';
    $t['bulk_action'] = 'चयनित पर थोक कार्रवाई';
    $t['apply'] = 'लागू करना';
    $t['submitted_on'] = 'प्रस्तुत किया जाना';
    $t['email'] = 'ई-मेल';
    $t['website'] = 'संचार प्रौद्योगिकी';
    $t['duplicate_content'] = 'प्रतिरूप सामग्री';
    $t['insufficient_interval'] = 'टिप्पणियों के बीच अपर्याप्त अंतराल';

    // Users
    $t['user_name_restrictions'] = 'केवल छोटे अक्षरों, अंकों, हाइफ़न और अधोयोजक अनुज्ञप्त';
    $t['display_name'] = 'प्रदर्शित होने वाला नाम';
    $t['role'] = 'भूमिका';
    $t['no_users_found'] = 'कोई उपयोगकर्ता नहीं पाया गया';
    $t['confirm_delete_user'] = 'क्या आप उपयोगकर्ता को हटाना चाहते हैं'; // No question mark please
    $t['confirm_delete_selected_users'] = 'क्या आप चयनित उपयोगकर्ता को हटाना चाहते हैं?';
    $t['disabled'] = 'निर्योग्य';
    $t['new_password'] = 'नया सांकेतिक शब्द';
    $t['new_password_msg'] = 'अगर आप अपना सांकेतिक शब्द बदलना चाहते है तो नया सांकेतिक शब्द इस स्थान पर अंकित करे। अन्यथा इस स्थान को रिक्त छोड़े।';
    $t['repeat_password'] = 'सांकेतिक शब्द दोहराए';
    $t['repeat_password_msg'] = 'सांकेतिक शब्द दुबारा दोहराए।';
    $t['user_name_exists'] = 'उपयोगकर्ता नाम पहले से ही मौजूद है';
    $t['email_exists'] = 'ई-मेल पहले से ही मौजूद है';

    // Login
    $t['user_name'] = 'उपयोगकर्ता का नाम';
    $t['password'] = 'सांकेतिक शब्द';
    $t['login'] = 'लॉग इन करें';
    $t['forgot_password'] = 'आप आपका सांकेतिक शब्द भूल गए?';
    $t['prompt_cookies'] = 'इस सामग्री प्रबंधन प्रणाली का उपयोग करने के लिए कुकीज़ सक्षम होने चाहिए।';
    $t['prompt_username'] = 'कृपया उपयोगकर्ता का नाम अंकित करे';
    $t['prompt_password'] = 'कृपया सांकेतिक शब्द अंकित करे';
    $t['invalid_credentials'] = 'अमान्य उपयोगकर्ता का नाम अथवा सांकेतिक शब्द';
    $t['account_disabled'] = 'निर्योग्य खाता';
    $t['access_denied'] = 'प्रवेश निलम्बित';
    $t['insufficient_privileges'] = 'अनुरोधित पृष्ठ को देखने के लिए पर्याप्त विशेषाधिकार अपेक्षित है। इस पृष्ठ को देखने के लिए लॉगआउट करे और पर्याप्त विशेषाधिकार युक्त खाते से लॉग इन करें।';

    // Password recovery
    $t['recovery_prompt'] = 'अपने उपयोगकर्ता नाम या ई-मेल पता दर्ज करें। ई-मेल द्वारा अपका सांकेतिक शब्द आपको मिल जाएगा।';
    $t['name_or_email'] = 'अपका उपयोगकर्ता नाम या ई-मेल';
    $t['submit'] = 'प्रस्तुत करना';
    $t['submit_error'] = 'कृपया अपना उपयोगकर्ता नाम या ई-मेल अंकित करे।';
    $t['no_such_user'] = 'इस तरह का उपयोगकर्ता मौजूद नहीं है।';
    $t['reset_req_email_subject'] = 'सांकेतिक शब्द बदलने का अनुरोध';
    $t['reset_req_email_msg_0'] = 'निम्नलिखित संचार प्रौद्योगिकी और उपयोगकर्ता नाम के सांकेतिक शब्द बदलने का अनुरोध प्राप्त हुआ है';
    $t['reset_req_email_msg_1'] = 'अनुरोध प्राप्त होने की पुष्टि करने के लिये निम्नलिखित पते का मुआयना करे, अन्यथा इस ई-मेल को नज़रअंदाज़ करे।';
    $t['email_failed'] = 'ई-मेल नहीं भेजा जा सका।';
    $t['reset_req_email_confirm'] = 'आपको एक पुष्टिकरण ई-मेल भेज दी गई है। कृपया अपनी ई-मेल देखें।';
    $t['invalid_key'] = 'अमान्य कुंजी';
    $t['reset_email_subject'] = 'आपका नया सांकेतिक शब्द';
    $t['reset_email_msg_0'] = 'निम्नलिखित संचार प्रौद्योगिकी और उपयोगकर्ता नाम का सांकेतिक शब्द बदल दिया गया है';
    $t['reset_email_msg_1'] = 'लॉग इन करने के बाद आप आपना सांकेतिक शब्द बदल सकते है।';
    $t['reset_email_confirm'] = 'आपका सांकेतिक शब्द बदल दिया गया है। कृपया नए सांकेतिक शब्द के लए आपना ई-मेल जांच लें।';

    // Maintenance Mode
    $t['back_soon'] = '<h2>रखरखाव प्रणाली</h2>
                        <p>
                            असुविधा के लिए खेद है।<br/>
                            हमारी वेबसाइट वर्तमान में उपयुक्त रखरखाव के दौर से गुजर रही है।<br/>
                            <b>कुछ समय के बाद पुनः कोशिश करें।</b>
                        </p>';


    // Addendum to Version 1.1 /////////////////////////////////////
    // Admin Panel
    $t['admin_panel'] = 'व्यवस्थापक पैनल';
    $t['login_title'] = 'काउच सामग्री प्रबंधन प्रणाली';

    // Folders
    $t['no_folders'] = 'कोई फ़ोल्डर परिभाषित नहीं है';
    $t['select_folder'] = 'फ़ोल्डर का चयन करें';
    $t['folders'] = 'फ़ोल्डर';
    $t['manage_folders'] = 'फ़ोल्डर प्रबंधन';
    $t['add_new_folder'] = 'नया फ़ोल्डर जोड़ें';
    $t['parent_folder'] = 'मुख्य फ़ोल्डर';
    $t['weight'] = 'मूल्य';
    $t['weight_desc'] = 'जिस पृष्ठ का मूल्य उच्च रहेगा वो विषय सूचि में कम दिखाई देगा,उसे आप नकारात्मक भी स्थापित कर सकते है';
    $t['desc'] = 'विवरण';
    $t['image'] = 'छवि';
    $t['cannot_be_own_parent'] = 'स्वयं, स्वयं का मुख्य नहीं हो सक्ता';
    $t['name_already_exists'] = 'नाम पहले से ही मौजूद है';
    $t['pages'] = 'पृष्ठ';
	$t['none'] = 'कुछ भी नहीं';
	$t['confirm_delete_folder'] = 'क्या आप फोल्डर को हटाना चाहते हैं'; //No question mark please.
	$t['confirm_delete_selected_folders'] = 'क्या आप चयनित फ़ोल्डर को नष्ट करना चाहते हैं?';
	
	// Drafts
	$t['draft_caps'] = 'प्रारूप'; //in Caps
	$t['draft'] = 'प्रारूप';
	$t['drafts'] = 'प्रारूप';
	$t['create_draft'] = 'प्रारूप बनाये ';
	$t['create_draft_msg'] = 'इस पृष्ठ की एक प्रतिलिपि बनाएँ (परिवर्तन के बाद सुरक्षित करे )';
	$t['manage_drafts'] = 'प्रारूप का प्रबंधन'; //plural
	$t['update_original'] = 'अद्यतन मूल';
	$t['update_original_msg'] = 'इस प्रारूप के सामग्री की नक़ल  मूल पृष्ठ पर कर दी है (प्रारूप को हटा दो )';
	$t['recreate_original'] = 'मूल को फिर से बनाना ';
	$t['no_drafts_found'] = 'कोई प्रारूप नहीं मिला';
	$t['original_page'] = 'मूल पृष्ठ';
	$t['template'] = 'नमूना ';
	$t['modified'] = 'अंतिम संशोधन की तारीख'; //date of last modification
	$t['preview'] = 'पूर्वावलोकन'; 
	$t['confirm_delete_draft'] = 'क्या आप इस  प्रारूप  को हटाना चाहते हैं?';
	$t['confirm_delete_selected_drafts'] = 'क्या आप चयनित प्रारूप  को हटाना चाहते हैं?';
	$t['confirm_apply_selected_drafts'] = 'क्या आप चयनित प्रारूप लागु करना चाहते है?';
	$t['view_all_drafts'] = 'सभी प्रारूप देखें';
	$t['original_deleted'] = 'मूल नष्ट'; //in Caps
   
   // Addendum to Version 1.2 /////////////////////////////////////
   // Nested pages
   $t['parent_page'] = 'मुख्य पृष्ठ';
   $t['page_weight_desc'] = ' जिस पृष्ठ का मूल्य उच्च रहेगा वो  विषय सूचि में कम दिखाई देगा,उसे आप नकारात्मक भी स्थापित कर सकते है ';
   $t['active'] = 'सक्रिय';
   $t['inactive'] = 'निष्क्रिय';
   $t['menu'] = 'सूचि ';
   $t['menu_text'] = 'विषयसूचि ';
   $t['show_in_menu'] = 'सूचि में दिखाया गया है';
   $t['not_shown_in_menu'] = 'सूचि में नहीं दिखाया गया है';
   $t['leave_empty'] = 'पृष्ठ शीर्षक का उपयोग करने के लिए खाली छोड़ दो';
   $t['menu_link'] = 'सूचि को जोड़े ';
   $t['link_url'] = 'This page points to the following location';
   $t['link_url_desc'] = 'खाली छोड़ा जा सकता है';
   $t['separate_window'] = 'अलग खिड़की में खोलें';
   $t['pointer_page'] = 'सूचक पृष्ठ';   
   $t['points_to_another_page'] = 'अन्य पृष्ठ पर इशारा करना';
   $t['points_to'] = 'इशारा करना';   
   $t['redi ects'] = 'पुनर्निर्देश';
   $t['masquerades'] = 'स्वांग बनाना';
   $t['strict_matching'] = 'नीचे दिए गए सधि के सभी पृष्ठों को चयनित निशान दे';
   $t['up'] = 'ऊपर ले जाएँ';
   $t['down'] = 'नीचे ले जाएँ';
   $t['remove_template_completely'] = 'यह पूरी तरह से हटाने के लिए इस नमूने के सभी पृष्ठों और प्रारूप को मिटायें';
   $t['remove_uncloned_template_completely'] = 'यह पूरी तरह से हटाने के लिए इस नमूने के सभी प्रारूप को मिटायें';

   // Addendum to Version 1.2.5 /////////////////////////////////////
   // Gallery
   $t['bulk_upload'] = 'भार डालना';
   $t['folder_empty'] = ' खाली फोल्डर । छवि को जोड़ने के लिए ऊपर अपलोड बटन का उपयोग करें.';
   $t['root'] = 'मूल';
   $t['item'] = ' छवि'; //single
   $t['items'] = ' छवि'; //multiple 
   $t['container'] = 'पात्र'; //single 
   $t['containers'] = 'पात्र'; //multiple 

   //
   $t['columns_missing'] = ' कुछ लापता स्तंभ!'; 
   $t['confirm_delete_columns'] = 'क्या आप स्थायी रूप से लापता स्तंभों को हटाना चाहते हैं?';
   $t['add_row'] = 'एक पंक्ति जोड़ें';
