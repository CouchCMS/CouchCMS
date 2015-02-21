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

    class KUser{
        var $id = -1;
        var $name = '';
        var $title = '';
        var $password = '';
        var $email = '';
        var $activation_key = '';
        var $registration_date = '';
        var $access_level = 0;
        var $disabled = '0';
        var $system = '0';
        var $last_failed = '0';

        var $fields = array();

        function KUser( $id='' ){
            global $DB;

            if( $id ){
                if( is_numeric($id) ){
                    $rs = $DB->select( K_TBL_USERS, array('*'), "id='" . $DB->sanitize( $id ). "'" );
                }
                else{
                    if( strpos( $id, '@')!==false ){
                        $rs = $DB->select( K_TBL_USERS, array('*'), "email='" . $DB->sanitize( $id ). "'" );
                    }
                    else{
                        $rs = $DB->select( K_TBL_USERS, array('*'), "name='" . $DB->sanitize( $id ). "'" );
                    }
                }
                if( count($rs) ){
                    $row = $rs[0];
                    foreach( $row as $k=>$v ){
                        if( isset($this->$k ) ) $this->$k = $v;
                    }
                }
            }

        }

        function populate_fields(){
            global $FUNCS;

            $fields = array(
                        'name'=>$FUNCS->t('user_name'),
                        'title'=>$FUNCS->t('display_name'),
                        'email'=>$FUNCS->t('email'),
                        'access_level'=>'Role',
                        'disabled'=>'Disabled'
                        );

            foreach( $fields as $k=>$v ){
                $field_info = array(
                    'id' => -1,
                    'name' => 'k_'.$k,
                    'label' => $v,
                    'k_desc' => '',
                    'k_type' => 'text',
                    'hidden' => '0',
                    'data' => $this->$k,
                    'required' => '1',
                    'validator' => '',
                    'system' => '1'
                );

                if( $k=='name' ){
                    $field_info['k_desc'] = $FUNCS->t('user_name_restrictions');
                    $field_info['validator'] = 'title_ready|min_len=4';
                }
                elseif( $k=='email' ){
                    $field_info['validator'] = 'email';
                }
                elseif( $k=='access_level' || $k=='disabled' ){
                    $field_info['hidden'] = '1';
                }

                $this->fields[] = new KFieldUser( $field_info, $this->fields );
            }

            $field_info = array(
                    'id' => -1,
                    'name' => 'k_password',
                    'label' => $FUNCS->t('new_password'),
                    'k_desc' => $FUNCS->t('new_password_msg'),
                    'k_type' => 'password',
                    'hidden' => '0',
                    'data' => '',
                    'required' => ($this->id == -1) ? '1' : '0',
                    'validator' => 'min_len='.K_MIN_PASSWORD_LEN,
                    'system' => '0'
                );
            $this->fields[] = new KFieldUser( $field_info, $this->fields );

            $field_info = array(
                    'id' => -1,
                    'name' => 'k_password2',
                    'label' => $FUNCS->t('repeat_password'),
                    'k_desc' => $FUNCS->t('repeat_password_msg'),
                    'k_type' => 'password',
                    'hidden' => '0',
                    'data' => '',
                    'required' => ($this->id == -1) ? '1' : '0',
                    'validator' => 'matches_field=k_password',
                    'system' => '0'
                );
            $this->fields[] = new KFieldUser( $field_info, $this->fields );
        }

        function save(){
            global $AUTH, $FUNCS, $DB;

            // Verify that the level of the account being set is lower than the level of the user setting it
            if( ($this->id != $AUTH->user->id) && ($this->access_level >= $AUTH->user->access_level) ){
                die( "Cheating?!" );
            }


            // Validate all system  fields before persistng changes
            $errors = 0;
            for( $x=0; $x<count($this->fields); $x++ ){
                $f = &$this->fields[$x];
                if( !$f->validate() ) $errors++;
            }
            if( $errors ) return $errors;

            $DB->begin();
            // Serialize access
            //$DB->select( K_TBL_USERS, array('id'), "access_level='10' FOR UPDATE" ); // results in deadlock
            $DB->update( K_TBL_SETTINGS, array('k_key'=>'secret_key'), "k_key='secret_key'" );

            // Verify that name & email are unique before proceeding
            $f = &$this->fields[0];
            $name = $f->get_data();
            if( $f->modified ){
                $rs = $DB->select( K_TBL_USERS, array('id'), "name='" . $DB->sanitize( $name ). "' and id != " . $DB->sanitize( $this->id ));
                if( count($rs) ){
                    $f->err_msg = $FUNCS->t('user_name_exists');
                    $errors++;
                }
            }
            $f = &$this->fields[2];
            $email = $f->get_data();
            if( $f->modified ){
                $rs = $DB->select( K_TBL_USERS, array('id'), "email='" . $DB->sanitize( $email ). "' and id != " . $DB->sanitize( $this->id ));
                if( count($rs) ){
                    $f->err_msg = $FUNCS->t('email_exists');
                    $errors++;
                }
            }

            // if user changing password, verify that the 'repeat password' field matches
            $f = &$this->fields[5];
            $pwd = $f->get_data();
            $f = &$this->fields[6];
            $pwd2 = $f->get_data();
            if( trim($pwd)!=trim($pwd2) ){
                $errors++;
                $f->err_msg = 'Does not match New Password';
            }

            if( $errors ){ $DB->rollback(); return $errors; }

            // verify that the changes to the account's level are permitted to the logged-in user.
            $f = &$this->fields[3];
            $level = $f->get_data();
            if( $level > $AUTH->user->access_level ){
                die( "Cheating?!" );
            }
            if( ($level == $AUTH->user->access_level) && ($this->id != $AUTH->user->id) ){
                die( "Cheating?!" );
            }
            if( ($level < $AUTH->user->access_level) && ($this->id == $AUTH->user->id) ){
                $f->data = $AUTH->user->access_level;
                $f->modified = true;
            }

            // get hash of the password
            if( $this->fields[5]->get_data() ){
                $hash = $AUTH->hasher->HashPassword( trim($this->fields[5]->get_data()) );
            }

            // If new user, create a record for it first.
            if( $this->id == -1 ){
                $rs = $DB->insert( K_TBL_USERS, array(
                                              'name'=>$name,
                                              'password'=>$hash,
                                              'registration_date'=>$FUNCS->get_current_desktop_time(),
                                              'email'=>$email
                                              )
                         );
                if( $rs!=1 ) die( "Failed to insert record for new page in K_TBL_USERS" );

                $this->id = $DB->last_insert_id;
                $rs = $DB->select( K_TBL_USERS, array('*'), "id='" . $DB->sanitize( $this->id ). "'" );
                if( !count($rs) ) die( "Failed to insert record for new page in K_TBL_USERS" );
                $rec = $rs[0];
                foreach( $rec as $k=>$v ){
                    $this->$k = $v;
                }
            }

            $arr_update = array();
            unset( $f );
            for( $x=0; $x<count($this->fields); $x++ ){
                $f = &$this->fields[$x];
                if( $f->system ){
                    $name = substr( $f->name, 2 ); // remove the 'k_' prefix from system fields
                    if( ($name=='access_level' || $name=='disabled') && ($this->id == $AUTH->user->id) ){
                        // cannot change one's own level or disable oneself
                        continue;
                    }
                    $prev_value = $this->$name;
                    $this->$name = $f->get_data();
                    $arr_update[$name] = $f->get_data();

                    if( $name=='name' && $f->modified  ){
                        if( $AUTH->user->name == $prev_value ){
                            $AUTH->set_cookie( $f->get_data() );
                        }
                    }
                }
                unset( $f );
            }
            // add password
            if( $hash ){
                $arr_update['password'] = $hash;
            }

            // persist changes
            $rs = $DB->update( K_TBL_USERS, $arr_update, "id='" . $DB->sanitize( $this->id ). "'" );
            if( $rs==-1 ) die( "ERROR: Unable to save data in K_TBL_USERS" );

            $DB->commit();

            if( !($this->id == $AUTH->user->id) ){
                $this->access_level = (int)$this->fields[3]->get_data();
                $this->disabled = (int)$this->fields[4]->get_data();
            }
        }

        function delete(){
            global $DB, $AUTH;

            // remove user
            if( $this->id != -1 ){
                if( $this->access_level >= $AUTH->user->access_level ){
                    die( "Cheating?!" );
                }

                $DB->begin();
                // Serialize access
                //$DB->select( K_TBL_USERS, array('id'), "access_level='10' FOR UPDATE" );
                $DB->update( K_TBL_SETTINGS, array('k_key'=>'secret_key'), "k_key='secret_key'" );

                $rs = $DB->delete( K_TBL_USERS, "id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete user from K_TBL_USERS" );

                $DB->commit();
            }
        }


    }// end class KUser
