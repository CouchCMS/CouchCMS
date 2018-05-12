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

    /*
     * Throttle front-end post submisions by the specified time interval
     */
    class KThrottle extends KUserDefinedFormField{
        static function handle_params( $params, $node ){
            global $FUNCS;

            $attr = $FUNCS->get_named_vars(
                        array(
                               'interval'=>'', /* throttle interval in seconds */
                               'message'=>'',  /* message to be shown when throttling enforced */
                              ),
                        $params);
            $interval = intval( $attr['interval'] );
            $attr['interval'] = $FUNCS->is_non_zero_natural( $interval ) ? $interval : 300; // 5 minutes default
            $message = trim( $attr['message'] );
            $attr['message'] = strlen( $message ) ? $message : 'Insufficient interval between two posts';

            return $attr;

        }

        // Handle Posted data
        function store_posted_changes( $post_val ){
            return; // no data accepted
        }

        // Render input field
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            return; // no visible markup required
        }

        // This is where all the action lies
        function validate(){
            global $FUNCS, $DB, $CTX;
            if( $this->k_inactive ) return true;

            $ip_addr = trim( $FUNCS->cleanXSS(strip_tags($_SERVER['REMOTE_ADDR'])) );
            $ts = strtotime( $FUNCS->get_current_desktop_time() ) - $this->interval;

            $sql = "creation_IP='" .$DB->sanitize( $ip_addr ). "' AND ";
            $sql .= "creation_date>='".$DB->sanitize( date( 'Y-m-d H:i:s', $ts ) )."' ORDER BY creation_date DESC LIMIT 1";
            $rs = $DB->select( K_TBL_PAGES, array('id', 'creation_date'), $sql );
            if( count($rs) ){
                // calculate how many seconds to wait before submission is allowed
                $seconds_remaining = strtotime( $rs[0]['creation_date'] ) - $ts;
                $CTX->set( 'k_error_'.$this->name.'_wait', $seconds_remaining );

                // send back error
                $this->err_msg = $this->message;
                return false;
            }
            return true;
        }

    }// end class KThrottle

    $FUNCS->register_udform_field( 'throttle', 'KThrottle' );
