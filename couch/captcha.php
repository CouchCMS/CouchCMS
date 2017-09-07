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
    ob_start();
    //define( 'K_ADMIN', 1 );

    //if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR );
    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    //require_once( K_COUCH_DIR.'header.php' );


    require( K_COUCH_DIR. 'includes/securimage/securimage.php' );
    class securimage_ex extends securimage{
        var $captcha_num;
        function __construct(){
            parent::__construct();

            // get the control's name from querystring
            $this->captcha_num = intval($_GET['c']);
        }

        function saveData(){
            $_SESSION['securimage_code_value'.$this->captcha_num] = strtolower($this->code);
        }

        function validate(){
            return false;
        }
    }

    if( isset($_GET['c']) && is_numeric($_GET['c']) && !preg_match("/[^0-9]/", $_GET['c']) ){
        $img = new securimage_ex();
        $img->ttf_file = K_COUCH_DIR. 'includes/securimage/elephant.ttf';
        $img->show();
    }
