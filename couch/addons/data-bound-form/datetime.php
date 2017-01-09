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

    // UDF for DateTime
    class KDateTime extends KUserDefinedField{

        static function handle_params( $params ){
            global $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            return KDateTime::_handle_params( $params );

        }

        static function _handle_params( $params ){
            global $FUNCS;

            // get supplied params
            extract( $FUNCS->get_named_vars(
                        array(
                                'format'=>'', /* dmy, ymd or mdy */
                                'fields_separator'=>'',  /* '/', '.' or '-' */
                                'months'=>'',
                                'allow_time'=>'0',
                                'am_pm'=>'0',
                                'default_time'=>'', /* blank, a valid date( e.g. 2008-05-14 02:08:45) or '@current' */
                                'minute_steps'=>'10',
                                'show_labels'=>'1',
                              ),
                        $params)
                   );

            // sanitize params
            $format = strtolower( trim($format) );
            if( !in_array($format, array('dmy', 'ymd', 'mdy')) ) $format='mdy';

            $fields_separator = strtolower( trim($fields_separator) );
            if( !in_array($fields_separator, array('/', '.', '-')) ) $fields_separator='/';

            $months = trim( $months );
            if( $months ){
                $months = array_map( "trim", explode( ',', $months ) );
                $tmp_months = array();
                foreach( $months as $m ){
                    if( $m ) $tmp_months[]=$m;
                }
                if( count($tmp_months)!=12 ) die("ERROR: Tag \"datetime\" - 'months' attribute requires 12 months.");
                $months = implode( ',', $tmp_months );
            }

            $allow_time = ( $allow_time==1 ) ? 1 : 0;
            $am_pm = ( $am_pm==1 ) ? 1 : 0;

            $default_time = trim( $default_time );
            if( strlen($default_time) ){
                if( strtolower($default_time)=='@current' ){
                    $default_time = '@current';
                }
                else{
                    if( !KDateTime::_checkdate($default_time) ){
                        die("ERROR: Tag \"datetime\" - Date given in 'default_time' attribute is invalid.");
                    }
                }
            }

            $minute_steps = $FUNCS->is_non_zero_natural( $minute_steps ) ? $minute_steps : 10;
            $show_labels = ( $show_labels==0 ) ? 0 : 1;

            // return back params
            $attr = array();
            $attr['format'] = $format;
            $attr['fields_separator'] = $fields_separator;
            $attr['months'] = $months;
            $attr['allow_time'] = $allow_time;
            $attr['am_pm'] = $am_pm;
            $attr['default_time'] = $default_time;
            $attr['minute_steps'] = $minute_steps;
            $attr['show_labels'] = $show_labels;

            return $attr;
        }

        // Output to admin panel
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX;
            $FUNCS->load_css( K_ADMIN_URL . 'addons/data-bound-form/datetime.css' );

            $date = $this->data;
            if( !strlen($date) && !isset($_POST[$input_name.'_year'])){
                if( strlen($this->default_time) ){
                    $date = ($this->default_time=='@current') ? $FUNCS->get_current_desktop_time() : $this->default_time;
                }
            }

            if( $this->months ){
                $arrMonths = explode( ',', $this->months );
            }
            else{
                $arrMonths = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            }

            if( strlen($date) ){
                $yy = substr( $date, 0, 4 );
                $mm = substr( $date, 5, 2 );
                $dd = substr( $date, 8, 2 );
            }

            $year = '<span class="dt_element dt_year"><input type="text" id="'.$input_id.'_year" name="'.$input_name.'[year]" value="' . $yy . '" size="4" maxlength="4" autocomplete="off" />';
            $month = "<span class=\"dt_element dt_month\"><select id=\"".$input_id."_month\" name=\"".$input_name."[month]\" >\n";
            $month .= '<option></option>';
            for( $xx=1; $xx<=12; $xx++ ){
                $month .= "<option value=\"".$xx."\"";
                $month .= ( $xx==$mm ) ? ' selected="selected"' : '';
                $month .= ">".$arrMonths[$xx-1]."</option>";
            }
            $month .= "</select>";
            $day = '<span class="dt_element dt_day"><input type="text" id="'.$input_id.'_day" name="'.$input_name.'[day]" value="' . $dd . '" size="2" maxlength="2" autocomplete="off" />';
            if( $this->show_labels ){
                $year .= '<label class="dt_label" for="'.$input_name.'_year" id="label_'.$input_id.'_year"> YYYY </label>';
                $month .= '<label class="dt_label" for="'.$input_name.'_month" id="label_'.$input_id.'_month"> MM </label>';
                $day .= '<label class="dt_label" for="'.$input_name.'_day" id="label_'.$input_id.'_day"> DD </label>';
            }
            $year .= '</span>';
            $month .= '</span>';
            $day .= '</span>';

            $sep = '<span class="dt_element dt_sep">' . $this->fields_separator . '</span>';
            switch( $this->format ){
                case 'dmy':
                    $html = $day . $sep . $month . $sep . $year;
                    break;
                case 'ymd':
                    $html = $year . $sep . $month . $sep . $day;
                    break;
                case 'mdy':
                    $html = $month . $sep . $day . $sep . $year;
            }

            // Append time?
            if( $this->allow_time ){
                if( strlen($date) ){
                    $h = substr( $date, 11, 2 );
                    $m = substr( $date, 14, 2 );
                    $s = substr( $date, 17, 2 );
                }
                else{
                    $h = $m = $s = -1;
                }

                $hour = "<span class=\"dt_element dt_hour\"><select id=\"".$input_id."_hour\" name=\"".$input_name."[hour]\" >\n";
                $hour .= '<option></option>';
                if( $this->am_pm ){
                    // 24-hour time to 12-hour time
                    $a = 'am';
                    if( $h != -1 ){
                        list( $h, $m, $a ) = explode( ":", @date("h:i:a", strtotime("$h:$m")) );
                    }
                    for( $xx=1; $xx<=12; $xx++ ){
                        $hour .= "<option value=\"".$xx."\"";
                        $hour .= ( $xx==$h ) ? ' selected="selected"' : '';
                        $hour .= ">".sprintf('%02d', $xx)."</option>";
                    }
                }
                else{
                    for( $xx=0; $xx<24; $xx++ ){
                        $hour .= "<option value=\"".$xx."\"";
                        $hour .= ( $xx==$h ) ? ' selected="selected"' : '';
                        $hour .= ">".sprintf('%02d', $xx)."</option>";
                    }
                }
                $hour .= "</select>";

                $min = "<span class=\"dt_element dt_min\"><select id=\"".$input_id."_min\" name=\"".$input_name."[min]\" >\n";
                $min .= '<option></option>';
                $step = $this->minute_steps;
                for( $xx=0; $xx<60; $xx++ ){
                    if( !($xx%$step) ){
                        $min .= "<option value=\"".$xx."\"";
                        $min .= ( $xx==$m ) ? ' selected="selected"' : '';
                        $min .= ">".sprintf('%02d', $xx)."</option>";
                    }
                }
                $min .= "</select>";

                if( $this->show_labels ){
                    $hour .= '<label class="dt_label" for="'.$input_name.'_hour" id="label_'.$input_id.'_hour"> HH </label>';
                    $min .= '<label class="dt_label" for="'.$input_name.'_min" id="label_'.$input_id.'_min"> MM </label>';
                }
                $hour .= '</span>';
                $min .= '</span>';

                $html .= '<span class="dt_element dt_sep">@</span>' . $hour . '<span class="dt_element dt_sep">:</span>' . $min;

                if( $this->am_pm ){
                    $am_pm = "<span class=\"dt_element dt_am_pm\"><select id=\"".$input_id."_am_pm\" name=\"".$input_name."[am_pm]\" >\n";
                    foreach( array('am', 'pm') as $xx ){
                        $am_pm .= "<option value=\"".$xx."\"";
                        $am_pm .= ( $xx==$a ) ? ' selected="selected"' : '';
                        $am_pm .= ">".strtoupper($xx)."</option>";
                    }
                    $am_pm .= "</select>";
                    $am_pm .= "</span>";
                    $html  .= $am_pm;
                }
            }
            $html .= '<span class="dt_break"></span>';
            $html  = '<span class="dt_container">'.$html.'</span>';

            return $html;
        }

        // Output to front-end via $CTX
        function get_data( $for_ctx=0 ){
            return $this->data;
        }

        // Handle posted data
        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->deleted ) return; // no need to store
            if( is_null($this->orig_data) ) $this->orig_data = $this->data;

            if( is_array($post_val) ){
                $year   = $post_val['year'];
                $month  = $post_val['month'];
                $day    = $post_val['day'];
                $hour   = $post_val['hour'];
                $min    = $post_val['min'];
                $sec    = $post_val['sec'];
                $am_pm  = $post_val['am_pm'];
            }
            elseif( strlen(trim($post_val)) ){
                $post_val = trim( $post_val );

                $year   = substr( $post_val, 0, 4 );
                $month  = substr( $post_val, 5, 2 );
                $day    = substr( $post_val, 8, 2 );
                $hour   = substr( $post_val, 11, 2 );
                $min    = substr( $post_val, 14, 2 );
                $sec    = substr( $post_val, 17, 2 );
            }

            // check if empty date submitted
            if( !strlen($year) && !strlen($month) && !strlen($day) && !strlen($hour) && !strlen($min) && !strlen($sec) ){
                $post_val = '';
            }
            else{
                // piece together the date from submitted components
                $year   = sprintf( "%04d", $year );
                $month  = sprintf( "%02d", $month );
                $day    = sprintf( "%02d", $day );
                $hour   = sprintf( "%02d", $hour );
                $min    = sprintf( "%02d", $min );
                $sec    = sprintf( "%02d", $sec );

                if( $this->am_pm && $am_pm ){
                    $am_pm  = ( in_array($am_pm, array('am', 'pm')) ) ? strtoupper($am_pm) : 'AM';
                    list( $hour, $min ) = explode( ":", @date("H:i", strtotime("$hour:$min $am_pm")) );
                    $hour   = sprintf( "%02d", $hour ); // being paranoid :)
                    $min    = sprintf( "%02d", $min );
                }
                $post_val = "$year-$month-$day $hour:$min:$sec";
            }
            $this->data = $post_val;

            // modified?
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 ) ? false : true; // values unchanged
        }

        function validate(){
            global $FUNCS;

            if( $this->is_empty() ){
                if( $this->required ){
                    return parent::validate(); // parent will handle custom error message, if any
                }
                else{
                    return true;
                }
            }

            // Validate date
            if( KDateTime::_checkdate($this->data) ){
                // date is ok. Let parent handle custom validators, if any
                return parent::validate();
            }

            $this->err_msg = 'Invalid Date'; //TODO: localize string
            return false;
        }

        static function _checkdate( $date, $with_time=0 ){
            $date = trim( $date );
            $pattern = ( $with_time ) ? '/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/' : '/^(\d{4})-(\d{2})-(\d{2})( ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]))?$/';
            if( preg_match( $pattern, $date, $matches ) ){
                return checkdate($matches[2], $matches[3], $matches[1]);
            }
            return false;
        }

        function is_empty(){
            if( strlen($this->data) ){
                return false;
            }
            return true;
        }

        // Save to database.
        function get_data_to_save(){
            return trim( $this->data );
        }

    }

    // Form Input field for DateTime (a wrapper around the UDF above)
    class KDateTimeForm extends KUserDefinedFormField{
        var $obj;

        static function handle_params( $params, $node ){

            return KDateTime::_handle_params( $params );

        }

        function __construct( $fields, &$siblings ){
            global $PAGE;

            $this->obj = new KDateTime( $fields, $PAGE /*dummy*/, $siblings );
            parent::__construct( $fields, $siblings );
        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            return call_user_func( array(&$this->obj, '_render'), $input_name, $input_id, $extra, $dynamic_insertion );
        }

        function get_data(){
            return call_user_func( array(&$this->obj, 'get_data') );
        }

        function store_posted_changes( $post_val ){
            return call_user_func( array(&$this->obj, 'store_posted_changes'), $post_val );
        }

        function validate(){
            $res = call_user_func( array(&$this->obj, 'validate') );
            if( $res==false ) $this->err_msg = $this->obj->err_msg;

            return $res;
        }
    }

    $FUNCS->register_udf( 'datetime', 'KDateTime', 1/*repeatable*/ );
    $FUNCS->register_udform_field( 'datetime', 'KDateTimeForm' );
