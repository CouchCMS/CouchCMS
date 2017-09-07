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

    class KComment{
        var $id = '-1';
        var $tpl_id = '';
        var $page_id = '';
        var $user_id = '';
        var $name = '';
        var $email = '';
        var $link = '';
        var $ip_addr = '';
        var $date = '';
        var $data = '';
        var $approved = 0;

        var $tpl_name = '';
        var $clonable = '';

        var $fields = array();

        function __construct( $id ){
            global $DB, $FUNCS;

            $rs = $DB->select( K_TBL_COMMENTS . " cc
                                inner join ".K_TBL_TEMPLATES." ct on ct.id=cc.tpl_id", array('cc.*, ct.name tpl_name, ct.clonable'), "cc.id='" . $DB->sanitize( $id ). "'" );
            if( count($rs) ){
                $row = $rs[0];
                foreach( $row as $k=>$v ){
                    if( isset($this->$k ) ) $this->$k = $v;
                }

                $this->populate_fields();

                // HOOK: comment_loaded
                $FUNCS->dispatch_event( 'comment_loaded', array(&$this) );
            }
        }

        function populate_fields(){
            global $FUNCS;

            if( count($this->fields) ) return;

            $fields = array(
                        'name'=>$FUNCS->t('name'), /*'Name',*/
                        'email'=>$FUNCS->t('email'), /*'Email',*/
                        'link'=>$FUNCS->t('website'), /*'Website',*/
                        'data'=>$FUNCS->t('comment'), /*'Comment',*/
                        'date'=>$FUNCS->t('submitted_on'),
                        'approved'=>$FUNCS->t('status'),
                        'dummy'=>'',
                        );

            foreach( $fields as $k=>$v ){
                $field_info = array(
                    'id' => -1,
                    'name' => '',
                    'label' => '',
                    'k_desc' => '',
                    'k_type' => 'text',
                    'hidden' => '0',
                    'data' => '',
                    'required' => '1',
                    'validator' => '',
                    'system' => '1',
                    'module' => 'comments',
                );

                $field_info['name'] = 'k_'.$k;
                $field_info['label'] = $v;
                $field_info['data'] = $this->$k;

                if( $k=='date' ){
                    $this->fields[] = new KCommentDateField( $field_info, $this, $this->fields );
                }
                elseif( $k=='approved' ){
                    $this->fields[] = new KSingleCheckField( $field_info, $this, $this->fields, $FUNCS->t('approved') );
                }
                else{
                    if( $k=='email' ){
                        $field_info['validator'] = 'email';
                    }
                    elseif( $k=='link' ){
                        $field_info['required'] = '0';
                    }
                    elseif( $k=='data' ){
                        $field_info['k_type'] = 'richtext';
                        $field_info['toolbar'] = 'basic';
                    }
                    elseif( $k=='dummy' ){
                        $field_info['system'] = '0';
                        $field_info['required'] = '0';
                        $field_info['no_render'] = '1';
                    }

                    $this->fields[] = new KField( $field_info, $this, $this->fields );
                }
            }

            // HOOK: alter_comment_fields_info
            $FUNCS->dispatch_event( 'alter_comment_fields_info', array(&$this->fields, &$this) );

        }

        function get_link(){
            global $FUNCS;

            $parent_link = ( K_PRETTY_URLS ) ? $FUNCS->get_pretty_template_link( $this->tpl_name ) : $this->tpl_name;
            return K_SITE_URL . $parent_link . "?comment=" . $this->id;
        }

        function save(){
            global $AUTH, $FUNCS, $DB;

            $DB->begin();

            // Serialize access
            $DB->select( K_TBL_PAGES, array('comments_count'), "id='" . $DB->sanitize( $this->page_id ). "' FOR UPDATE" );

            // HOOK: comment_presave
            // the save process is about to begin.
            // Field values can be adjusted before subjecting them to the save routine.
            $FUNCS->dispatch_event( 'comment_presave', array(&$this) );

            // HOOK: comment_prevalidate
            // all fields are ready for validation. Do any last minute tweaking before validation begins.
            $FUNCS->dispatch_event( 'comment_prevalidate', array(&$this->fields, &$this) );

            // Validate all system  fields before persistng changes
            $errors = 0;
            for( $x=0; $x<count($this->fields); $x++ ){
                $f = &$this->fields[$x];
                if( !$f->validate() ) $errors++;
            }

            // HOOK: comment_validate
            // can add some custom validation here if required.
            $FUNCS->dispatch_event( 'comment_validate', array(&$this->fields, &$errors, &$this) );

            if( $errors ){ $DB->rollback(); return $errors; }

            $arr_update = array();
            unset( $f );
            for( $x=0; $x<count($this->fields); $x++ ){
                $f = &$this->fields[$x];
                if( $f->system ){
                    $name = substr( $f->name, 2 ); // remove the 'k_' prefix from system fields
                    $prev_value = $this->$name;
                    $this->$name = $arr_update[$name] = $f->get_data();
                }
                unset( $f );
            }

            // HOOK: alter_comment_update
            $FUNCS->dispatch_event( 'alter_comment_update', array(&$arr_update, &$this->fields, &$this) );

            // persist changes
            $rs = $DB->update( K_TBL_COMMENTS, $arr_update, "id='" . $DB->sanitize( $this->id ). "'" );
            if( $rs==-1 ) die( "ERROR: Unable to save data in K_TBL_COMMENTS" );

            // adjust comments count for the page
            $rs = $FUNCS->update_comments_count( $this->page_id );
            if( $FUNCS->is_error($rs) ) die( "ERROR: Unable to update comments count in K_TBL_PAGES" );

            // HOOK: comment_updated
            $FUNCS->dispatch_event( 'comment_updated', array(&$this, &$errors) );
            if( $errors ){ $DB->rollback(); return $errors; }

            $DB->commit();

            // Invalidate cache
            //$FUNCS->invalidate_cache();

        }

        function approve( $approve=1 ){
            global $DB, $FUNCS;

            if( $this->id != -1 && ($approve==1 || $approve==0) ){

                $DB->begin();
                // Serialize access
                $DB->select( K_TBL_PAGES, array('comments_count'), "id='" . $DB->sanitize( $this->page_id ). "' FOR UPDATE" );

                $rs = $DB->update( K_TBL_COMMENTS, array('approved'=>$approve), "id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to update data in K_TBL_COMMENTS" );

                // adjust comments count for the page
                $rs = $FUNCS->update_comments_count( $this->page_id );
                if( $FUNCS->is_error($rs) ) die( "ERROR: Unable to update comments count in K_TBL_PAGES" );

                // HOOK: comment_approved
                $FUNCS->dispatch_event( 'comment_approved', array(&$this) );

                $DB->commit();
                $this->approved = $approve;

                // Invalidate cache
                $FUNCS->invalidate_cache();
            }

        }

        function delete(){
            global $DB, $FUNCS;

            // remove comment
            if( $this->id != -1 ){
                $DB->begin();
                // Serialize access
                $DB->select( K_TBL_PAGES, array('comments_count'), "id='" . $DB->sanitize( $this->page_id ). "' FOR UPDATE" );

                // HOOK: comment_predelete
                $FUNCS->dispatch_event( 'comment_predelete', array(&$this) );

                $rs = $DB->delete( K_TBL_COMMENTS, "id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete comment from K_TBL_COMMENTS" );

                // adjust comments count for the page
                $rs = $FUNCS->update_comments_count( $this->page_id );
                if( $FUNCS->is_error($rs) ) die( "ERROR: Unable to update comments count in K_TBL_PAGES" );

                // HOOK: comment_deleted
                $FUNCS->dispatch_event( 'comment_deleted', array(&$this) );

                $DB->commit();
                $this->id = -1;

                // Invalidate cache
                $FUNCS->invalidate_cache();
            }

        }


    }// end class KComments
