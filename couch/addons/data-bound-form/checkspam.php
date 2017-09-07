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

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly


    class KCheckSpam{

        // tag handler
        static function check_spam_handler( $params, $node ){
            global $CTX, $FUNCS, $AUTH, $SFS;
            if( $AUTH->user->access_level >= K_ACCESS_LEVEL_ADMIN ){ return; } // exempt admins from check

            extract( $FUNCS->get_named_vars(
                        array(
                              'email'=>'',
                              'username'=>'',
                              'ip'=>'',
                              'display_message'=>'<h2>Flagged as spam!</h2>',
                              'notify_email'=>'',
                              ),
                        $params)
                   );

            $email = trim( $email );
            if( $email=='' ) $email = $CTX->get('frm_k_email'); // get from submitted comment form
            if( !$email ) return; // email is mandatory - no email, nothing to check

            $username = trim( $username );
            if( $username=='' ) $username = $CTX->get('frm_k_author'); // get from submitted comment form

            $ip = trim( $ip );
            if( $ip=='' ) $ip = $_SERVER["REMOTE_ADDR"];
            $ip = preg_replace( '/[^0-9A-F:., ]/i', '', $ip );

            $display_message = trim( $display_message );
            $notify_email = trim( $notify_email );

            // contact stopforumspam.com to get spam score
            $spam_score = KCheckSpam::check_stopforumspam( $username, $email, $ip );

            // Spammer?
            $spam_threshold = ( $username ) ? 3: 2;
            if( $spam_score >= $spam_threshold ){

                if( $notify_email ){
                   $from = $to = $notify_email;
                   $subject = 'Spam Stopped!';
                   $message = "Stopped a spam posting...\n\nUsername: ".$username
                   ."\nEmail: ".$email.
                   "\nIP: ".$IP.
                   "\nScore: ".$spam_score;

                   $FUNCS->send_mail( $from, $to, $subject, $message );
                }

                // Kill the posting process
                ob_end_clean();
                die( $display_message );
            }
        }

        static function check_stopforumspam($username, $email, $ip){
            global $FUNCS;

            $score = 0;

            // query stopforumspam.com
            $username = trim( $username );
            $email = trim( $email );
            $url = 'http://www.stopforumspam.com/api?ip='.urlencode( $ip );
            if( strlen($username) ) $url .= '&username=' . urlencode( $username );
            $url .= '&email=' . urlencode( $email );
            $url .= '&f=serial';

            $res = @unserialize( $FUNCS->file_get_contents($url) );

            if( is_array($res) && $res['success'] ){

                $freq_email = $res['email']['frequency'];
                $freq_ip = $res['ip']['frequency'];

                if( strlen($username) ){
                    $freq_username = $res['username']['frequency'];
                    if( $freq_email + $freq_ip == 0 ) return 0;
                    if( $freq_username + $freq_email == 0 ) return 0;
                }
                else{
                    $freq_username = 0;
                }

                // Return the total score
                $score = ( $freq_username + $freq_email + $freq_ip );
            }

            return $score;
        }

    } //end class KCheckSpam

    // register custom tag
    $FUNCS->register_tag( 'check_spam', array('KCheckSpam', 'check_spam_handler') );
