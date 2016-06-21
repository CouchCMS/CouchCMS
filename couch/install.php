<?php
    /*
    The contents of this file are subject to the Common Public Attribution License
    Version 1.0 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.couchcms.com/cpal.html. The License is based on the Mozilla
    Public License Version 1.1 but Sections 14 and 15 have been added to cover use
    of software over a computer network and provide for limited attribution for the
    Original Developer. In addition, Exhibit A has been modified to be consistent with
    Exhibit B.

    Software distributed under the License is distributed on an "AS IS" basis, WITHOUT
    WARRANTY OF ANY KIND, either express or implied. See the License for the
    specific language governing rights and limitations under the License.

    The Original Code is the CouchCMS project.

    The Original Developer is the Initial Developer.

    The Initial Developer of the Original Code is Kamran Kashif (kksidd@couchcms.com).
    All portions of the code written by Initial Developer are Copyright (c) 2009, 2010
    the Initial Developer. All Rights Reserved.

    Contributor(s):

    Alternatively, the contents of this file may be used under the terms of the
    CouchCMS Commercial License (the CCCL), in which case the provisions of
    the CCCL are applicable instead of those above.

    If you wish to allow use of your version of this file only under the terms of the
    CCCL and not to allow others to use your version of this file under the CPAL, indicate
    your decision by deleting the provisions above and replace them with the notice
    and other provisions required by the CCCL. If you do not delete the provisions
    above, a recipient may use your version of this file under either the CPAL or the
    CCCL.
    */

    if ( !defined('K_INSTALLATION_IN_PROGRESS') ) die(); // cannot be loaded directly

    header( 'Content-Type: text/html; charset='.K_CHARSET );

    require_once( K_COUCH_DIR.'auth/auth.php' );
    require_once( K_COUCH_DIR.'parser/parser.php' );
    require_once( K_COUCH_DIR.'parser/HTMLParser.php' );
    require_once( K_COUCH_DIR.'page.php' );
    require_once( K_COUCH_DIR.'tags.php' );

    $TAGS = new KTags();
    $CTX = new KContext();

    $k_couch_tables = array(
        K_TBL_TEMPLATES,
        K_TBL_FIELDS,
        K_TBL_PAGES,
        K_TBL_FOLDERS,
        K_TBL_USERS,
        K_TBL_USER_LEVELS,
        K_TBL_SETTINGS,
        K_TBL_DATA_TEXT,
        K_TBL_DATA_NUMERIC,
        K_TBL_FULLTEXT,
        K_TBL_COMMENTS,
        K_TBL_RELATIONS,
    );
    $k_stmts = array();
    $k_stmts[] = "CREATE TABLE ".K_TBL_COMMENTS." (
      id        int AUTO_INCREMENT NOT NULL,
      tpl_id    int NOT NULL,
      page_id   int NOT NULL,
      user_id   int,
      name      tinytext,
      email     varchar(128),
      link      varchar(255),
      ip_addr   varchar(100),
      date      datetime,
      data      text,
      approved  tinyint DEFAULT '0',
      PRIMARY KEY (id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_DATA_NUMERIC." (
      page_id   int NOT NULL,
      field_id  int NOT NULL,
      value     decimal(65,2) DEFAULT '0.00',
      PRIMARY KEY (page_id, field_id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_DATA_TEXT." (
      page_id       int NOT NULL,
      field_id      int NOT NULL,
      value         longtext,
      search_value  text,
      PRIMARY KEY (page_id, field_id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_FIELDS." (
      id                int AUTO_INCREMENT NOT NULL,
      template_id       int NOT NULL,
      name              varchar(255) NOT NULL,
      label             varchar(255),
      k_desc            varchar(255),
      k_type            varchar(128) NOT NULL,
      hidden            int(1),
      search_type       varchar(20) DEFAULT 'text',
      k_order           int,
      data              longtext,
      default_data      longtext,
      required          int(1),
      deleted           int(1),
      validator         varchar(255),
      validator_msg     text,
      k_separator       varchar(20),
      val_separator     varchar(20),
      opt_values        text,
      opt_selected      tinytext,
      toolbar           varchar(20),
      custom_toolbar    text,
      css               text,
      custom_styles     text,
      maxlength         int,
      height            int,
      width             int,
      k_group           varchar(128),
      collapsed         int(1) DEFAULT '-1',
      assoc_field       varchar(128),
      crop              int(1) DEFAULT '0',
      enforce_max       int(1) DEFAULT '1',
      quality           int,
      show_preview      int(1) DEFAULT '0',
      preview_width     int,
      preview_height    int,
      no_xss_check      int(1) DEFAULT '0',
      rtl               int(1) DEFAULT '0',
      body_id           tinytext,
      body_class        tinytext,
      disable_uploader  int(1) DEFAULT '0',
      _html             text COMMENT 'Internal',
      dynamic           text,
      custom_params     text,
      searchable        int(1) DEFAULT '1',
      class             tinytext,
      PRIMARY KEY (id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_FOLDERS." (
      id            int AUTO_INCREMENT NOT NULL,
      pid           int DEFAULT '-1',
      template_id   int NOT NULL,
      name          varchar(255) NOT NULL,
      title         varchar(255),
      k_desc        mediumtext,
      image         text,
      access_level  int DEFAULT '0',
      weight        int DEFAULT '0',
      PRIMARY KEY (id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_FULLTEXT." (
      page_id  int NOT NULL,
      title    varchar(255),
      content  text,
      PRIMARY KEY (page_id)
    ) ENGINE = MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_USER_LEVELS." (
      id        int AUTO_INCREMENT NOT NULL,
      name      varchar(100),
      title     varchar(100),
      k_level   int DEFAULT '0',
      disabled  int DEFAULT '0',
      PRIMARY KEY (id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_PAGES." (
      id                 int AUTO_INCREMENT NOT NULL,
      template_id        int NOT NULL,
      parent_id          int DEFAULT '0',
      page_title         varchar(255),
      page_name          varchar(255),
      creation_date      datetime DEFAULT '0000-00-00 00:00:00',
      modification_date  datetime DEFAULT '0000-00-00 00:00:00',
      publish_date       datetime DEFAULT '0000-00-00 00:00:00',
      status             int,
      is_master          int(1) DEFAULT '0',
      page_folder_id     int DEFAULT '-1',
      access_level       int DEFAULT '0',
      comments_count     int DEFAULT '0',
      comments_open      int(1) DEFAULT '1',
      nested_parent_id   int DEFAULT '-1',
      weight             int DEFAULT '0',
      show_in_menu       int(1) DEFAULT '1',
      menu_text          varchar(255),
      is_pointer         int(1) DEFAULT '0',
      pointer_link       text,
      pointer_link_detail text,
      open_external int(1) DEFAULT '0',
      masquerades          int(1) DEFAULT '0',
      strict_matching      int(1) DEFAULT '0',
      file_name            varchar(260),
      file_ext             varchar(20),
      file_size            int DEFAULT '0',
      file_meta            text,
      creation_IP          varchar(45),
      k_order            int DEFAULT '0',

      PRIMARY KEY (id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_SETTINGS." (
      k_key    varchar(255) NOT NULL,
      k_value  longtext,
      PRIMARY KEY (k_key)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_TEMPLATES." (
      id            int AUTO_INCREMENT NOT NULL,
      name          varchar(255) NOT NULL,
      description   varchar(255),
      clonable      int(1) DEFAULT '0',
      executable    int(1) DEFAULT '1',
      title         varchar(255),
      access_level  int DEFAULT '0',
      commentable   int(1) DEFAULT '0',
      hidden        int(1) DEFAULT '0',
      k_order       int DEFAULT '0',
      dynamic_folders   int(1) DEFAULT '0',
      nested_pages int(1) DEFAULT '0',
      gallery          int(1) DEFAULT '0',
      handler          text,
      custom_params    text,
      type             varchar(255),
      config_list      text,
      config_form      text,
      parent           varchar(255),
      icon             varchar(255),
      PRIMARY KEY (id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE ".K_TBL_USERS." (
      id                 int AUTO_INCREMENT NOT NULL,
      name               varchar(255) NOT NULL,
      title              varchar(255),
      password           varchar(64) NOT NULL,
      email              varchar(128) NOT NULL,
      activation_key     varchar(64),
      password_reset_key varchar(64),
      registration_date  datetime,
      access_level       int DEFAULT '0',
      disabled           int DEFAULT '0',
      system             int DEFAULT '0',
      last_failed        bigint(11) DEFAULT '0',
      failed_logins      int DEFAULT '0',
      PRIMARY KEY (id)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $k_stmts[] = "CREATE TABLE `".K_TBL_RELATIONS."` (
    `pid`     int NOT NULL,
    `fid`     int NOT NULL,
    `cid`     int NOT NULL,
    `weight`  int DEFAULT '0',
    PRIMARY KEY (`pid`, `fid`, `cid`)
    ) ENGINE = InnoDB CHARACTER SET `utf8` COLLATE `utf8_general_ci`;";

    $k_stmts[] = "CREATE TABLE `".K_TBL_ATTACHMENTS."` (
    `attach_id`       bigint(11) UNSIGNED AUTO_INCREMENT NOT NULL,
    `file_real_name`  varchar(255) NOT NULL,
    `file_disk_name`  varchar(255) NOT NULL,
    `file_extension`  varchar(255) NOT NULL,
    `file_size`       int(20) UNSIGNED NOT NULL DEFAULT '0',
    `file_time`       int(10) UNSIGNED NOT NULL DEFAULT '0',
    `is_orphan`       tinyint(1) UNSIGNED DEFAULT '1',
    `hit_count`       int(10) UNSIGNED DEFAULT '0',
    `creation_ip`     varchar(45),
    PRIMARY KEY (`attach_id`)
    ) ENGINE = InnoDB CHARACTER SET `utf8` COLLATE `utf8_general_ci`;";

    $k_stmts[] = "CREATE INDEX ".K_TBL_COMMENTS."_Index01
      ON ".K_TBL_COMMENTS."
      (date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_COMMENTS."_Index02
      ON ".K_TBL_COMMENTS."
      (page_id, approved, date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_COMMENTS."_Index03
      ON ".K_TBL_COMMENTS."
      (tpl_id, approved, date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_COMMENTS."_Index04
      ON ".K_TBL_COMMENTS."
      (approved, date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_COMMENTS."_Index05
      ON ".K_TBL_COMMENTS."
      (tpl_id, page_id, approved, date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_DATA_NUMERIC."_Index01
      ON ".K_TBL_DATA_NUMERIC."
      (value);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_DATA_NUMERIC."_Index02
      ON ".K_TBL_DATA_NUMERIC."
      (field_id, value);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_DATA_TEXT."_Index01
      ON ".K_TBL_DATA_TEXT."
      (search_value(255));";

    $k_stmts[] = "CREATE INDEX ".K_TBL_DATA_TEXT."_Index02
      ON ".K_TBL_DATA_TEXT."
      (field_id, search_value(255));";

    $k_stmts[] = "CREATE INDEX ".K_TBL_FIELDS."_index01
      ON ".K_TBL_FIELDS."
      (k_group, k_order, id);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_FIELDS."_Index02
      ON ".K_TBL_FIELDS."
      (template_id);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_FOLDERS."_Index01
      ON ".K_TBL_FOLDERS."
      (template_id, id);";

    $k_stmts[] = "CREATE UNIQUE INDEX ".K_TBL_FOLDERS."_Index02
      ON ".K_TBL_FOLDERS."
      (template_id, name(255));";

    $k_stmts[] = "CREATE FULLTEXT INDEX ".K_TBL_FULLTEXT."_Index01
      ON ".K_TBL_FULLTEXT."
      (title);";

    $k_stmts[] = "CREATE FULLTEXT INDEX ".K_TBL_FULLTEXT."_Index02
      ON ".K_TBL_FULLTEXT."
      (content);";

    $k_stmts[] = "CREATE UNIQUE INDEX ".K_TBL_USER_LEVELS."_index01
      ON ".K_TBL_USER_LEVELS."
      (k_level);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index01
      ON ".K_TBL_PAGES."
      (template_id, publish_date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index02
      ON ".K_TBL_PAGES."
      (template_id, page_folder_id, publish_date);";

    $k_stmts[] = "CREATE UNIQUE INDEX ".K_TBL_PAGES."_Index03
      ON ".K_TBL_PAGES."
      (template_id, page_name(255));";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index04
      ON ".K_TBL_PAGES."
      (template_id, modification_date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index05
      ON ".K_TBL_PAGES."
      (template_id, page_folder_id, modification_date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index06
      ON ".K_TBL_PAGES."
      (template_id, page_folder_id, page_name(255));";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index07
      ON ".K_TBL_PAGES."
      (template_id, comments_count);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index08
      ON ".K_TBL_PAGES."
      (template_id, page_title(255));";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index09
      ON ".K_TBL_PAGES."
      (template_id, page_folder_id, page_title(255));";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index10
      ON ".K_TBL_PAGES."
      (template_id, page_folder_id, comments_count);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index11
      ON ".K_TBL_PAGES."
      (template_id, parent_id, modification_date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index12
      ON ".K_TBL_PAGES."
      (parent_id, modification_date);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_PAGES."_Index13
      ON ".K_TBL_PAGES."
      (template_id, is_pointer, masquerades, pointer_link_detail(255));";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_Index14` ON `".K_TBL_PAGES."` (`template_id`, `file_name`(255));";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_Index15` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_name`(255));";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_Index16` ON `".K_TBL_PAGES."` (`template_id`, `file_ext`(20), `file_name`(255));";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_Index17` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_ext`(20), `file_name`(255));";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_Index18` ON `".K_TBL_PAGES."` (`template_id`, `file_size`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_Index19` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_size`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_Index20` ON `".K_TBL_PAGES."` (`creation_IP`, `creation_date`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_index21` ON `".K_TBL_PAGES."` (`template_id`, `k_order`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_index22` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `k_order`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_PAGES."_index23` ON `".K_TBL_PAGES."` (`k_order`);";

    $k_stmts[] = "CREATE UNIQUE INDEX ".K_TBL_TEMPLATES."_Index01
      ON ".K_TBL_TEMPLATES."
      (name);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_USERS."_activation_key
      ON ".K_TBL_USERS."
      (activation_key);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_USERS."_password_reset_key
      ON ".K_TBL_USERS."
      (password_reset_key);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_USERS."_index01
      ON ".K_TBL_USERS."
      (access_level);";

    $k_stmts[] = "CREATE INDEX ".K_TBL_USERS."_index02
      ON ".K_TBL_USERS."
      (access_level, name);";

    $k_stmts[] = "CREATE UNIQUE INDEX ".K_TBL_USERS."_email
      ON ".K_TBL_USERS."
      (email);";

    $k_stmts[] = "CREATE UNIQUE INDEX ".K_TBL_USERS."_name
      ON ".K_TBL_USERS."
      (name);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_RELATIONS."_Index01`
    ON `".K_TBL_RELATIONS."`
    (`pid`, `fid`, `weight`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_RELATIONS."_Index02`
    ON `".K_TBL_RELATIONS."`
    (`fid`, `cid`, `weight`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_RELATIONS."_Index03`
    ON `".K_TBL_RELATIONS."`
    (`cid`, `fid`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index01`
    ON `".K_TBL_ATTACHMENTS."`
    (`is_orphan`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index02`
    ON `".K_TBL_ATTACHMENTS."`
    (`file_time`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index03`
    ON `".K_TBL_ATTACHMENTS."`
    (`is_orphan`, `file_time`);";

    $k_stmts[] = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index04`
    ON `".K_TBL_ATTACHMENTS."`
    (`creation_ip`, `file_time`);";

    $k_stmts[] = "INSERT INTO ".K_TBL_USER_LEVELS." (id, name, title, k_level, disabled) VALUES (1, 'superadmin', 'Super Admin', 10, 0);";
    $k_stmts[] = "INSERT INTO ".K_TBL_USER_LEVELS." (id, name, title, k_level, disabled) VALUES (2, 'admin', 'Administrator', 7, 0);";
    $k_stmts[] = "INSERT INTO ".K_TBL_USER_LEVELS." (id, name, title, k_level, disabled) VALUES (3, 'authenticated_user_special', 'Authenticated User (Special)', 4, 0);";
    $k_stmts[] = "INSERT INTO ".K_TBL_USER_LEVELS." (id, name, title, k_level, disabled) VALUES (4, 'authenitcated_user', 'Authenticated User', 2, 0);";
    $k_stmts[] = "INSERT INTO ".K_TBL_USER_LEVELS." (id, name, title, k_level, disabled) VALUES (5, 'unauthenticated_user', 'Everybody', 0, 0);";

    // Load dump file for importing data
    if( file_exists(K_COUCH_DIR . 'install-ex.php') ){
        require_once( K_COUCH_DIR . 'install-ex.php' );
    }

    function k_install( $name, $pwd, $email ){
        global $CTX, $DB, $FUNCS, $k_couch_tables, $k_stmts;
        $err = '';

        // First check if any of the tables to be created do not already exist
        $sql = 'SHOW TABLES FROM `' . $DB->database .'`';
        $result = mysql_query( $sql );
        if( !$result ){
            $err = 'MySQL Error: ' . mysql_error();
            $CTX->set( 'k_install_error', $err );
            return;
        }

        while( $row = mysql_fetch_row($result) ){
            if( in_array($row[0], $k_couch_tables) ){
                $err = 'Table "'.$row[0].'" already exists';
                $CTX->set( 'k_install_error', $err );
                return;
            }
        }
        mysql_free_result( $result );

        // Create tables and records
        @set_time_limit( 0 ); // make server wait
        @mysql_query( "SET autocommit=0" );
        @mysql_query( "BEGIN" );
        $start_time = time();
        foreach( $k_stmts as $sql ){
            @mysql_query( $sql );
            if( mysql_errno() ){
                $err .= mysql_errno() . ": " . mysql_error() . "<br />" . $sql;
                break;
            }
            $cur_time = time();
            if( $cur_time + 25 > $start_time ){
                header( "X-Dummy: wait" ); // make browser wait
                $start_time = $cur_time;
            }
        }

        // Finally create the version and super-admin records
        if( !$err ){
            $k_stmts = array();
            $k_stmts[] = "INSERT INTO ".K_TBL_SETTINGS." (k_key, k_value) VALUES ('k_couch_version', '".K_COUCH_VERSION."');";
            $name = $DB->sanitize( $name );
            $AUTH = new KAuth();
            $pwd = $AUTH->hasher->HashPassword( $pwd );
            $pwd = $DB->sanitize( $pwd );
            $email = $DB->sanitize( $email );
            $creation_time = $FUNCS->get_current_desktop_time();
            $k_stmts[] = "INSERT INTO ".K_TBL_USERS." (id, name, title, password, email, activation_key, password_reset_key, registration_date, access_level, disabled, system, last_failed, failed_logins) VALUES (1, '".$name."', '".$name."', '".$pwd."', '".$email."', '', '', '".$creation_time."', 10, 0, 1, 0, 0);";

            foreach( $k_stmts as $sql ){
                @mysql_query( $sql );
                if( mysql_errno() ){
                    $err .= mysql_errno() . ": " . mysql_error() . "<br />";
                }
            }

        }

        if( !$err ){
            @mysql_query( "COMMIT" );
        }
        else{
            @mysql_query( "ROLLBACK" );
        }

        $CTX->set( 'k_install_error', $err );
    }

    ob_start();
    ////////////////////////////
    ?>
    <?php echo $FUNCS->login_header(); ?>
    <div class="panel-heading simple-heading">CouchCMS</div>
    <cms:form name="frm_login" class="simple-form" action="" method="post" anchor="0" onSubmit="this.k_install.disabled=true; return true;">
        <cms:if k_success >
            <cms:php> global $CTX; k_install( $CTX->get('frm_name'), $CTX->get('frm_password'), $CTX->get('frm_email') ); </cms:php>

            <cms:if k_install_error >
                <div class="alert alert-error alert-icon">
                    <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#circle-x"></use></svg>
                    <h2>Installation failed!</h2>
                    <cms:show k_install_error />
                </div>
            <cms:else />
                <div class="alert alert-success alert-icon">
                    <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#check"></use></svg>
                    <h2>Installation successful!</h2>
                    Please <a href="<cms:php> echo K_ADMIN_URL . K_ADMIN_PAGE; </cms:php>">log in</a> using the information you provided.
                </div>
            </cms:if>
        <cms:else />
            <cms:if k_error >
                <div class="alert alert-error alert-icon">
                    <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#circle-x"></use></svg>
                    <cms:each k_error >
                        <cms:show item /><br/>
                    </cms:each>
                </div>
            <cms:else />
                <div class="alert alert-notice alert-icon">
                    <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#warning"></use></svg>
                    Installation required
                </div>
            </cms:if>

            <div class="field prepend">
                <cms:input type="text" id="k_user_name" name="name" maxlength="40"
                    required="1" validator='title_ready|min_len=4'
                    validator_msg='title_ready=Only lowercase characters, numerals, hyphen and underscore permitted'
                    autofocus="autofocus" class="text" placeholder="Super-Admin Username" 'required="required"' value=""/>
                <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#person"></use></svg>
            </div>

            <div class="field prepend">
                <cms:input type="text" id="k_user_email" name="email" required='1' validator='email'
                    class="text" placeholder="Email Address" 'required="required"' value=""/>
                <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#envelope-closed"></use></svg>
            </div>

            <div class="field prepend">
                <cms:input type="password" id="k_user_pwd" name="password" required="1" validator='min_len=5'
                    autocorrect="off" autocapitalize="off" spellcheck="false" class="password" placeholder="Password" 'required="required"' value="" />
                <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#lock-locked"></use></svg>
            </div>

            <div class="field prepend">
                <cms:input type="password" id="k_user_pwd_repeat" name="repeat_password" required="1" validator='matches_field=password'
                    autocorrect="off" autocapitalize="off" spellcheck="false" class="password" placeholder="Repeat Password" 'required="required"' value="" />
                <svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#lock-locked"></use></svg>
            </div>

            <div class="simple-btns">
                <button class="btn btn-primary" name="k_install" type="submit"><svg class="i"><use xlink:href="<cms:php>echo K_SYSTEM_THEME_URL;</cms:php>assets/open-iconic.svg#check"></use></svg>Install</button>
            </div>
        </cms:if>
    </cms:form>

    <?php echo $FUNCS->login_footer(); ?>
    <?php
    ///////////////////////////
    $html = ob_get_contents();
    ob_end_clean();

    $parser = new KParser( $html );
    echo $parser->get_HTML();
    die();
