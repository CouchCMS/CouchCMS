<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    $DB->begin();

    // will move the queries to a separate file later
    // upgrade to 1.0.2 (unreleased .. merged with 1.1)
    if( version_compare("1.0.2", $_ver, ">") ){
        // dynamic folders
        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `dynamic_folders` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` MODIFY `name` varchar(255) NOT NULL;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_FOLDERS."` ADD `image` text;";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX ".K_TBL_FOLDERS."_Index01 ON ".K_TBL_FOLDERS." (template_id, id);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX ".K_TBL_FOLDERS."_Index02 ON ".K_TBL_FOLDERS." (template_id, name(255));";
        $DB->_query( $_sql );

    }

    // upgrade to 1.1
    if( version_compare("1.1.0", $_ver, ">") ){
        // drafts
        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `parent_id` int DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index11` ON `".K_TBL_PAGES."` (`template_id`, `parent_id`, `modification_date`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index12` ON `".K_TBL_PAGES."` (`parent_id`, `modification_date`);";
        $DB->_query( $_sql );
    }

    // upgrade to 1.2 //actually RC1 (will be considered < 1.2.0)
    if( version_compare("1.2", $_ver, ">") ){
        // nested pages
        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `nested_pages` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `nested_parent_id` int DEFAULT '-1';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `weight` int DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `show_in_menu` int(1) DEFAULT '1';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `menu_text` varchar(255);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `is_pointer` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `pointer_link` text;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `pointer_link_detail` text;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `open_external` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."`  ADD `masquerades` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."`  ADD `strict_matching` int(1) DEFAULT '0';";
        $DB->_query( $_sql );
    }
    // upgrade to 1.2.0RC2
    if( version_compare("1.2.0RC2", $_ver, ">") ){
        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index13` ON `".K_TBL_PAGES."` (`template_id`, `is_pointer`, `masquerades`, `pointer_link_detail`(255));";
        $DB->_query( $_sql );
    }
    // upgrade to 1.2.0 //release
    if( version_compare("1.2.0", $_ver, ">") ){

    }
    // upgrade to 1.2.5RC1
    if( version_compare("1.2.5RC1", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `gallery` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_name` varchar(260);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_ext` varchar(20);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_size` int DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_meta` text;";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index14` ON `".K_TBL_PAGES."` (`template_id`, `file_name`(255));";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index15` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_name`(255));";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index16` ON `".K_TBL_PAGES."` (`template_id`, `file_ext`(20), `file_name`(255));";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index17` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_ext`(20), `file_name`(255));";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index18` ON `".K_TBL_PAGES."` (`template_id`, `file_size`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index19` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_size`);";
        $DB->_query( $_sql );
    }
    // upgrade to 1.3RC1
    if( version_compare("1.3RC1", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_FIELDS."` ADD `custom_params` text;";
        $DB->_query( $_sql );

        $_sql = "CREATE TABLE `".K_TBL_RELATIONS."` (
            `pid`     int NOT NULL,
            `fid`     int NOT NULL,
            `cid`     int NOT NULL,
            `weight`  int DEFAULT '0',
            PRIMARY KEY (`pid`, `fid`, `cid`)
        ) ENGINE = InnoDB
        CHARACTER SET `utf8` COLLATE `utf8_general_ci`;";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_RELATIONS."_Index01` ON `".K_TBL_RELATIONS."` (`pid`, `fid`, `weight`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_RELATIONS."_Index02` ON `".K_TBL_RELATIONS."` (`fid`, `cid`, `weight`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_RELATIONS."_Index03` ON `".K_TBL_RELATIONS."` (`cid`);";
        $DB->_query( $_sql );
    }
    // upgrade to 1.4RC1
    if( version_compare("1.4RC1", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `creation_IP` varchar(45);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index20` ON `".K_TBL_PAGES."` (`creation_IP`, `creation_date`);";
        $DB->_query( $_sql );

        $_sql = "CREATE TABLE `".K_TBL_ATTACHMENTS."` (
            `attach_id`       bigint(11) UNSIGNED AUTO_INCREMENT NOT NULL,
            `file_real_name`  varchar(255) NOT NULL,
            `file_disk_name`  varchar(255) NOT NULL,
            `file_extension`  varchar(255) NOT NULL,
            `file_size`       int(20) UNSIGNED NOT NULL DEFAULT '0',
            `file_time`       int(10) UNSIGNED NOT NULL DEFAULT '0',
            `is_orphan`       tinyint(1) UNSIGNED DEFAULT '1',
            `hit_count`       int(10) UNSIGNED DEFAULT '0',
            PRIMARY KEY (`attach_id`)
            ) ENGINE = InnoDB CHARACTER SET `utf8` COLLATE `utf8_general_ci`;";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index01` ON `".K_TBL_ATTACHMENTS."` (`is_orphan`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index02` ON `".K_TBL_ATTACHMENTS."` (`file_time`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index03` ON `".K_TBL_ATTACHMENTS."` (`is_orphan`, `file_time`);";
        $DB->_query( $_sql );
    }
    // upgrade to 1.4.5RC1
    if( version_compare("1.4.5RC1", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_ATTACHMENTS."` ADD `creation_ip` varchar(45);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_index04` ON `".K_TBL_ATTACHMENTS."` (`creation_ip`, `file_time`);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `handler` text;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `custom_params` text;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_USERS."` ADD `password_reset_key` varchar(64);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_USERS."` ADD `failed_logins` int DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_USERS."_password_reset_key` ON `".K_TBL_USERS."` (`password_reset_key`);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_USERS."` MODIFY `name` varchar(255) NOT NULL;";
        $DB->_query( $_sql );
    }
    // upgrade to 1.4.5
    if( version_compare("1.4.5", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_FIELDS."` ADD `searchable` int(1) DEFAULT '1';";
        $DB->_query( $_sql );
    }
    // upgrade to 2.0.beta
    if( version_compare("2.0.beta", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `type` varchar(255);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `config_list` text;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `config_form` text;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `parent` varchar(255);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `icon` varchar(255);";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_FIELDS."`    ADD `class` tinytext;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `k_order` int DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "UPDATE `".K_TBL_PAGES."` set k_order=id where 1=1;";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_FIELDS."` MODIFY `collapsed` int(1) DEFAULT '-1';";
        $DB->_query( $_sql );

        $_sql = "UPDATE `".K_TBL_FIELDS."` set collapsed='-1' where 1=1;";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_index21` ON `".K_TBL_PAGES."` (`template_id`, `k_order`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_index22` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `k_order`);";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_index23` ON `".K_TBL_PAGES."` (`k_order`);";
        $DB->_query( $_sql );
    }
    // upgrade to 2.1.beta
    if( version_compare("2.1.beta", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `ref_count` int DEFAULT '1';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `deleted` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `has_globals` int(1) DEFAULT '0';";
        $DB->_query( $_sql );

        $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index24` ON `".K_TBL_PAGES."` (`status`, `ref_count`, `modification_date`);";
        $DB->_query( $_sql );
    }
    // upgrade to 2.2.beta
    if( version_compare("2.2.beta", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_FIELDS."` ADD `not_active` text;";
        $DB->_query( $_sql );
    }
    // upgrade to 2.2RC1
    if( version_compare("2.2RC1", $_ver, ">") ){
        $__fix_globals = function(){
            global $FUNCS, $DB;

            $rs = $DB->select( K_TBL_TEMPLATES, array('name'), 'has_globals=1' );
            if( count($rs) ){
                foreach( $rs as $rec ){
                    $pi = $FUNCS->pathinfo( $rec['name'] );
                    $old_name = $pi['filename'] . '__globals';
                    $new_name = $rec['name'] . '__globals';

                    $rs2 = $DB->update( K_TBL_TEMPLATES, array('name'=>$new_name), "name='" . $DB->sanitize( $old_name ). "'" );
                }
            }
        };
        $__fix_globals();
    }
    // upgrade to 2.2.1
    if( version_compare("2.2.1", $_ver, ">") ){
        $_sql = "ALTER TABLE `".K_TBL_FIELDS."` MODIFY `custom_params` mediumtext;";
        $DB->_query( $_sql );
    }

    // Finally update version number
    $_rs = $DB->update( K_TBL_SETTINGS, array('k_value'=>K_COUCH_VERSION), "k_key='k_couch_version'" );
    if( $_rs==-1 ) die( "ERROR: Unable to update version number" );
    $DB->commit( 1 );
