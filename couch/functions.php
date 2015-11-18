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

    require_once( K_COUCH_DIR.'parser/parser.php' );
    require_once( K_COUCH_DIR.'parser/HTMLParser.php' );
    require_once( K_COUCH_DIR.'search.php' );
    require_once( K_COUCH_DIR.'event.php' );

    class KFuncs{
        var $latin_charset;
        var $nonce_secret_key;
        var $trans_tbl;
        var $cached_folder_trees = array();
        var $cached_templates = array();
        var $cached_fields = array();
        var $cached_files = array(); // for use of smart_embed tag
        var $cached_nested_pages = array();
        var $cached_pretty_tplnames = array();

        var $_t = array(); // translated strings
        var $shortcodes = array();
        var $admin_list_views = array();
        var $admin_page_views = array();
        var $tags = array();
        var $udfs = array();
        var $udform_fields = array();
        var $scripts = array();
        var $styles = array();
        var $repeatable = array();

        var $_ed;

        function KFuncs(){
            define( '_64e3',  (64.0 * 64.0 * 64.0) );
            define( '_64e4',  (64.0 * 64.0 * 64.0 * 64.0) );
            define( '_64e15', (_64e3 * _64e4 * _64e4 * _64e4) );
            define( '_64e16', (_64e4 * _64e4 * _64e4 * _64e4) );
            define( '_64e63', (_64e15 * _64e16 * _64e16 * _64e16) );
            define( '_64e64', (_64e16 * _64e16 * _64e16 * _64e16) );

            $this->_ed = new EventDispatcher();
        }

        function raise_error( $err_msg ){
            return new KError( $err_msg );
        }

        function is_error( $e ){
            return is_a( $e, 'KError');
        }

        function get_named_vars( $into, $from ){
            // First fill in unnamed variables
            $i=0;
            foreach( $from as $param ){
                if( $i >= count($into) ) break;
                if( !$param['lhs'] ){
                    $x=0;
                    foreach( $into as $k=>$v ){
                        if($x==$i){
                            $into[$k] = $param['rhs'];
                            break;
                        }
                        $x++;
                    }
                    $i++;
                }
            }

            // Next the named variables
            foreach( $from as $param ){
                if( isset($into[$param['lhs']]) ){
                    $into[$param['lhs']] = $param['rhs'];
                }
            }
            return $into;
        }

        function resolve_parameters( $attributes ){
            // Resolve the attribute values.
            global $CTX;

            $params = array();
            foreach( $attributes as $attr ){
                $param = array();
                $param['lhs'] = $attr['name'];
                $param['op'] = $attr['op'];
                switch( $attr['value_type'] ){
                    case K_VAL_TYPE_LITERAL:
                        $param['rhs'] = $attr['value'];
                        break;
                    case K_VAL_TYPE_VARIABLE:
                        $param['rhs'] = trim( $CTX->get($attr['value']) );
                        break;
                    case K_VAL_TYPE_SPECIAL:
                        $param['rhs'] = trim( $attr['value']->get_HTML() );
                        break;
                }
                $params[] = $param;
            }

            return $params;
        }

        function resolve_condition( $attributes ){
            global $CTX;
            $equivalent_ops = array("lt"=>"<", "gt"=>">", "le"=>"<=", "ge"=>">=", "="=>"==", "eq"=>"==", "ne"=>"!=");
            $known_ops = array( '<', '>', '<=', '>=', '==', '!=', '(', ')', '&&', '||');

            $s="";
            foreach( $attributes as $attr ){
                if( isset($attr['name']) ){
                    $s .= "'" . addslashes($CTX->get( $attr['name'] )) . "'";
                }
                if( isset($attr['op']) ){
                    $op = $attr['op'];
                    //if( $op == '=' ){ die( "Did you mean to use '==' instead of '=' in IF tag?"); }

                    if( isset($equivalent_ops[$op]) ) $op = $equivalent_ops[$op];
                    if( !in_array($op, $known_ops) ) { die( "Unknown operator: " . $op); }
                    $s .= ' ' . $op . ' ';
                }
                if( isset($attr['value']) ){
                    switch( $attr['value_type'] ){
                        case K_VAL_TYPE_LITERAL:
                            $s .= "'" . addslashes( $attr['value'] ) . "'";
                            break;
                        case K_VAL_TYPE_VARIABLE:
                            $s .= "'" . addslashes( $CTX->get($attr['value']) ) . "'";
                            break;
                        case K_VAL_TYPE_SPECIAL:
                            $s .= "'" . addslashes( $attr['value']->get_HTML() ) . "'";
                            break;
                    }
                }
            }
            return $s;
        }

        function post_process_page(){
            global $DB, $PAGE, $AUTH;

            if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                $DB->begin();

                // HOOK: post_process_page_start
                $this->dispatch_event( 'post_process_page_start' );

                // Process deleted fields
                $dirty = array();
                foreach( $PAGE->fields as $field ){
                    if( $field->system ) continue;

                    // if a template is clonable, an unprocessed field will only be deleted in 'page view'
                    if( !$field->processed && !$field->deleted && ((!$PAGE->tpl_is_clonable) || ($PAGE->tpl_is_clonable && !$PAGE->is_master)) ){
                        if( $field->k_type=='group' || $field->k_type=='message' || $field->k_type=='hidden' ){
                            // These types do not contain data hence can be deleted immediately
                            // note: if a 'group' is removed, all child fields will be moved into the last existing group
                            // unless their group parameters is adjusted in code.

                            if( $field->search_type == 'text' ){
                                // remove all instances of this text field
                                $rs = $DB->delete( K_TBL_DATA_TEXT, "field_id='" . $DB->sanitize( $field->id ). "'" );
                                if( $rs==-1 ) die( "ERROR: Unable to delete field data from K_TBL_DATA_TEXT" );
                            }
                            else{
                                // remove all instances of this numeric field
                                $rs = $DB->delete( K_TBL_DATA_NUMERIC, "field_id='" . $DB->sanitize( $field->id ). "'" );
                                if( $rs==-1 ) die( "ERROR: Unable to delete field data from K_TBL_DATA_NUMERIC" );
                            }

                            // finally remove this field
                            $rs = $DB->delete( K_TBL_FIELDS, "id='" . $DB->sanitize( $field->id ). "'" );
                            if( $rs==-1 ) die( "ERROR: Unable to delete field K_TBL_FIELDS" );

                        }
                        else{
                            $rs = $DB->update( K_TBL_FIELDS, array('deleted'=>'1'), "id='" . $DB->sanitize( $field->id ). "'" );
                            if( $rs==-1 ) die( "ERROR: Unable to mark field as deleted" );
                        }
                        continue;
                    }
                }

                // Process deleted folders (if non-dynamic)
                if( !$PAGE->tpl_dynamic_folders ){
                    $PAGE->folders->process_delete();
                }

                // HOOK: post_process_page_end
                $this->dispatch_event( 'post_process_page_end' );

                $DB->commit();
            }
        }

        function is_title_clean( $title ){
            if( !preg_match('/^[0-9a-z-_]+$/', $title ) ){
                return false;
            }
            return true;
        }

        function is_variable_clean( $title ){
            if( !preg_match('/^[a-z_][0-9a-z_]+$/i', $title ) ){
                return false;
            }
            return true;
        }

        function get_clean_url( $title ){
            global $FUNCS;

            $title = strip_tags( $title ); // remove html tags
            $title = preg_replace('/%([0-9a-fA-F][0-9a-fA-F])/', '', $title); // remove encoded octets
            $title = preg_replace('/&.+?;/', '', $title); // remove html_entities

            // HOOK: transliterate_clean_url
            // give plugins the first shot at transliterating non-latin characters
            $FUNCS->dispatch_event( 'transliterate_clean_url', array(&$title) );

            // next try transliterator_transliterate(), if available
            if( function_exists('transliterator_transliterate') ){
                $trans_title = @transliterator_transliterate( 'Any-Latin; Latin-ASCII; Lower()', $title );
                if( $trans_title!==FALSE ){ $title = $trans_title; }
            }

            // finally the original pruning
            $title = $this->remove_accents( $title );
            $title = str_replace( '/', '-', $title );
            $title = preg_replace( '/[^0-9a-zA-Z-_ \.]/', '', $title );
            $title = strtolower( $title );
            $title = str_replace( ' ', '-', $title );
            $title = str_replace( '.', '-', $title );
            $title = preg_replace( '/-+/', '-', $title);
            $title = trim( $title, '-' );

            return $title;
        }

        function remove_accents( $title ){
            if ( !preg_match('/[\x80-\xff]/', $title) ) return $title; // All chars within ASCII range.
            if( !$this->utf8_check($title) ) $title = utf8_encode($title); // If not utf8, perhaps ISO-8859-1
            $title = preg_replace_callback( '/([\xC3\xC4\xC5])([\x80-\xEF])/', array($this, '_remove_accents'), $title );
            return $title;
        }

        function _remove_accents( $matches ){

            if( !is_array($this->latin_charset) ){
                $this->latin_charset = array(
                    /* Latin-1 Supplement U+0080 - U+00FF (128-255) */
                    192 => 'A', // LATIN CAPITAL LETTER A WITH GRAVE (&Agrave;)
                    193 => 'A', // LATIN CAPITAL LETTER A WITH ACUTE (&Aacute;)
                    194 => 'A', // LATIN CAPITAL LETTER A WITH CIRCUMFLEX (&Acirc;)
                    195 => 'A', // LATIN CAPITAL LETTER A WITH TILDE (&Atilde;)
                    196 => 'A', // LATIN CAPITAL LETTER A WITH DIAERESIS (&Auml;)
                    197 => 'A', // LATIN CAPITAL LETTER A WITH RING ABOVE (&Aring;)
                    198 => 'A', // LATIN CAPITAL LETTER AE (&AElig;)
                    199 => 'C', // LATIN CAPITAL LETTER C WITH CEDILLA (&Ccedil;)
                    200 => 'E', // LATIN CAPITAL LETTER E WITH GRAVE (&Egrave;)
                    201 => 'E', // LATIN CAPITAL LETTER E WITH ACUTE (&Eacute;)
                    202 => 'E', // LATIN CAPITAL LETTER E WITH CIRCUMFLEX (&Ecirc;)
                    203 => 'E', // LATIN CAPITAL LETTER E WITH DIAERESIS (&Euml;)
                    204 => 'I', // LATIN CAPITAL LETTER I WITH GRAVE (&Igrave;)
                    205 => 'I', // LATIN CAPITAL LETTER I WITH ACUTE (&Iacute;)
                    206 => 'I', // LATIN CAPITAL LETTER I WITH CIRCUMFLEX (&Icirc;)
                    207 => 'I', // LATIN CAPITAL LETTER I WITH DIAERESIS (&Iuml;)
                    209 => 'N', // LATIN CAPITAL LETTER N WITH TILDE (&Ntilde;)
                    210 => 'O', // LATIN CAPITAL LETTER O WITH GRAVE (&Ograve;)
                    211 => 'O', // LATIN CAPITAL LETTER O WITH ACUTE (&Oacute;)
                    212 => 'O', // LATIN CAPITAL LETTER O WITH CIRCUMFLEX (&Ocirc;)
                    213 => 'O', // LATIN CAPITAL LETTER O WITH TILDE (&Otilde;)
                    214 => 'O', // LATIN CAPITAL LETTER O WITH DIAERESIS (&Ouml;)
                    216 => 'O', // LATIN CAPITAL LETTER O WITH STROKE (&Oslash;)
                    217 => 'U', // LATIN CAPITAL LETTER U WITH GRAVE (&Ugrave;)
                    218 => 'U', // LATIN CAPITAL LETTER U WITH ACUTE (&Uacute;)
                    219 => 'U', // LATIN CAPITAL LETTER U WITH CIRCUMFLEX (&Ucirc;)
                    220 => 'U', // LATIN CAPITAL LETTER U WITH DIAERESIS (&Uuml;)
                    221 => 'Y', // LATIN CAPITAL LETTER Y WITH ACUTE (&Yacute;)
                    223 => 's', // LATIN SMALL LETTER SHARP S (&szlig;)
                    224 => 'a', // LATIN SMALL LETTER A WITH GRAVE (&agrave;)
                    225 => 'a', // LATIN SMALL LETTER A WITH ACUTE (&aacute;)
                    226 => 'a', // LATIN SMALL LETTER A WITH CIRCUMFLEX (&acirc;)
                    227 => 'a', // LATIN SMALL LETTER A WITH TILDE (&atilde;)
                    228 => 'a', // LATIN SMALL LETTER A WITH DIAERESIS (&auml;)
                    229 => 'a', // LATIN SMALL LETTER A WITH RING ABOVE (&aring;)
                    230 => 'a', // LATIN SMALL LETTER AE (&aelig;)
                    231 => 'c', // LATIN SMALL LETTER C WITH CEDILLA (&ccedil;)
                    232 => 'e', // LATIN SMALL LETTER E WITH GRAVE (&egrave;)
                    233 => 'e', // LATIN SMALL LETTER E WITH ACUTE (&eacute;)
                    234 => 'e', // LATIN SMALL LETTER E WITH CIRCUMFLEX (&ecirc;)
                    235 => 'e', // LATIN SMALL LETTER E WITH DIAERESIS (&euml;)
                    236 => 'i', // LATIN SMALL LETTER I WITH GRAVE (&igrave;)
                    237 => 'i', // LATIN SMALL LETTER I WITH ACUTE (&iacute;)
                    238 => 'i', // LATIN SMALL LETTER I WITH CIRCUMFLEX (&icirc;)
                    239 => 'i', // LATIN SMALL LETTER I WITH DIAERESIS (&iuml;)
                    241 => 'n', // LATIN SMALL LETTER N WITH TILDE (&ntilde;)
                    242 => 'o', // LATIN SMALL LETTER O WITH GRAVE (&ograve;)
                    243 => 'o', // LATIN SMALL LETTER O WITH ACUTE (&oacute;)
                    244 => 'o', // LATIN SMALL LETTER O WITH CIRCUMFLEX (&ocirc;)
                    245 => 'o', // LATIN SMALL LETTER O WITH TILDE (&otilde;)
                    246 => 'o', // LATIN SMALL LETTER O WITH DIAERESIS (&ouml;)
                    248 => 'o', // LATIN SMALL LETTER O WITH STROKE (&oslash;)
                    249 => 'u', // LATIN SMALL LETTER U WITH GRAVE (&ugrave;)
                    250 => 'u', // LATIN SMALL LETTER U WITH ACUTE (&uacute;)
                    251 => 'u', // LATIN SMALL LETTER U WITH CIRCUMFLEX (&ucirc;)
                    252 => 'u', // LATIN SMALL LETTER U WITH DIAERESIS (&uuml;)
                    253 => 'y', // LATIN SMALL LETTER Y WITH ACUTE (&yacute;)
                    255 => 'y', // LATIN SMALL LETTER Y WITH DIAERESIS (&yuml;)

                    /* Latin Extended-A U+0100 - U+017F (256-383) */
                    256 => 'A', // LATIN CAPITAL LETTER A WITH MACRON
                    257 => 'a', // LATIN SMALL LETTER A WITH MACRON
                    258 => 'A', // LATIN CAPITAL LETTER A WITH BREVE
                    259 => 'a', // LATIN SMALL LETTER A WITH BREVE
                    260 => 'A', // LATIN CAPITAL LETTER A WITH OGONEK
                    261 => 'a', // LATIN SMALL LETTER A WITH OGONEK
                    262 => 'C', // LATIN CAPITAL LETTER C WITH ACUTE
                    263 => 'c', // LATIN SMALL LETTER C WITH ACUTE
                    264 => 'C', // LATIN CAPITAL LETTER C WITH CIRCUMFLEX
                    265 => 'c', // LATIN SMALL LETTER C WITH CIRCUMFLEX
                    272 => 'D', // LATIN CAPITAL LETTER D WITH STROKE
                    273 => 'd', // LATIN SMALL LETTER D WITH STROKE
                    274 => 'E', // LATIN CAPITAL LETTER E WITH MACRON
                    275 => 'e', // LATIN SMALL LETTER E WITH MACRON
                    276 => 'E', // LATIN CAPITAL LETTER E WITH BREVE
                    277 => 'e', // LATIN SMALL LETTER E WITH BREVE
                    278 => 'E', // LATIN CAPITAL LETTER E WITH DOT ABOVE
                    279 => 'e', // LATIN SMALL LETTER E WITH DOT ABOVE
                    280 => 'E', // LATIN CAPITAL LETTER E WITH OGONEK
                    281 => 'e', // LATIN SMALL LETTER E WITH OGONEK
                    288 => 'G', // LATIN CAPITAL LETTER G WITH DOT ABOVE
                    289 => 'g', // LATIN SMALL LETTER G WITH DOT ABOVE
                    290 => 'G', // LATIN CAPITAL LETTER G WITH CEDILLA
                    291 => 'g', // LATIN SMALL LETTER G WITH CEDILLA
                    292 => 'H', // LATIN CAPITAL LETTER H WITH CIRCUMFLEX
                    293 => 'h', // LATIN SMALL LETTER H WITH CIRCUMFLEX
                    294 => 'H', // LATIN CAPITAL LETTER H WITH STROKE
                    295 => 'h', // LATIN SMALL LETTER H WITH STROKE
                    296 => 'I', // LATIN CAPITAL LETTER I WITH TILDE
                    297 => 'i', // LATIN SMALL LETTER I WITH TILDE
                    304 => 'I', // LATIN CAPITAL LETTER I WITH DOT ABOVE
                    305 => 'i', // LATIN SMALL LETTER DOTLESS I
                    306 => 'I', // LATIN CAPITAL LIGATURE IJ
                    307 => 'i', // LATIN SMALL LIGATURE IJ
                    308 => 'J', // LATIN CAPITAL LETTER J WITH CIRCUMFLEX
                    309 => 'j', // LATIN SMALL LETTER J WITH CIRCUMFLEX
                    310 => 'K', // LATIN CAPITAL LETTER K WITH CEDILLA
                    311 => 'k', // LATIN SMALL LETTER K WITH CEDILLA
                    313 => 'L', // LATIN CAPITAL LETTER L WITH ACUTE
                    320 => 'l', // LATIN SMALL LETTER L WITH MIDDLE DOT
                    321 => 'L', // LATIN CAPITAL LETTER L WITH STROKE
                    322 => 'l', // LATIN SMALL LETTER L WITH STROKE
                    323 => 'N', // LATIN CAPITAL LETTER N WITH ACUTE
                    324 => 'n', // LATIN SMALL LETTER N WITH ACUTE
                    325 => 'N', // LATIN CAPITAL LETTER N WITH CEDILLA
                    326 => 'n', // LATIN SMALL LETTER N WITH CEDILLA
                    327 => 'N', // LATIN CAPITAL LETTER N WITH CARON
                    328 => 'n', // LATIN SMALL LETTER N WITH CARON
                    329 => 'n', // LATIN SMALL LETTER N PRECEDED BY APOSTROPHE
                    336 => 'O', // LATIN CAPITAL LETTER O WITH DOUBLE ACUTE
                    337 => 'o', // LATIN SMALL LETTER O WITH DOUBLE ACUTE
                    338 => 'O', // LATIN CAPITAL LIGATURE OE (&OElig;)
                    339 => 'o', // LATIN SMALL LIGATURE OE (&oelig;)
                    340 => 'R', // LATIN CAPITAL LETTER R WITH ACUTE
                    341 => 'r', // LATIN SMALL LETTER R WITH ACUTE
                    342 => 'R', // LATIN CAPITAL LETTER R WITH CEDILLA
                    343 => 'r', // LATIN SMALL LETTER R WITH CEDILLA
                    344 => 'R', // LATIN CAPITAL LETTER R WITH CARON
                    345 => 'r', // LATIN SMALL LETTER R WITH CARON
                    352 => 'S', // LATIN CAPITAL LETTER S WITH CARON (&Scaron;)
                    353 => 's', // LATIN SMALL LETTER S WITH CARON (&scaron;)
                    354 => 'T', // LATIN CAPITAL LETTER T WITH CEDILLA
                    355 => 't', // LATIN SMALL LETTER T WITH CEDILLA
                    356 => 'T', // LATIN CAPITAL LETTER T WITH CARON
                    357 => 't', // LATIN SMALL LETTER T WITH CARON
                    358 => 'T', // LATIN CAPITAL LETTER T WITH STROKE
                    359 => 't', // LATIN SMALL LETTER T WITH STROKE
                    360 => 'U', // LATIN CAPITAL LETTER U WITH TILDE
                    361 => 'u', // LATIN SMALL LETTER U WITH TILDE
                    368 => 'U', // LATIN CAPITAL LETTER U WITH DOUBLE ACUTE
                    369 => 'u', // LATIN SMALL LETTER U WITH DOUBLE ACUTE
                    370 => 'U', // LATIN CAPITAL LETTER U WITH OGONEK
                    371 => 'u', // LATIN SMALL LETTER U WITH OGONEK
                    372 => 'W', // LATIN CAPITAL LETTER W WITH CIRCUMFLEX
                    373 => 'w', // LATIN SMALL LETTER W WITH CIRCUMFLEX
                    374 => 'Y', // LATIN CAPITAL LETTER Y WITH CIRCUMFLEX
                    375 => 'y', // LATIN SMALL LETTER Y WITH CIRCUMFLEX
                    376 => 'Y', // LATIN CAPITAL LETTER Y WITH DIAERESIS (&Yuml;)
                    377 => 'Z'  // LATIN CAPITAL LETTER Z WITH ACUTE
                );
            } // end array $latin_charset

            $key = ((ord($matches[1]) & 0x1F) << 6) | ( ord($matches[2]) & 0x3F );
            if( isset($this->latin_charset[$key]) ){
                return $this->latin_charset[$key];
            }
            else{
                return $matches[0];
            }
        }

        // Determines if the string is a valid utf8 encoded one.
        // author: <bmorel@ssi.fr>
        // code from:   http://www.php.net/manual/en/function.utf8-encode.php
        function utf8_check($Str) {
            $len = strlen($Str);
            for ($i=0; $i<$len; $i++) {
                $b = ord($Str[$i]);
                if ($b < 0x80) continue; # 0bbbbbbb
                elseif (($b & 0xE0) == 0xC0) $n=1; # 110bbbbb
                elseif (($b & 0xF0) == 0xE0) $n=2; # 1110bbbb
                elseif (($b & 0xF8) == 0xF0) $n=3; # 11110bbb
                elseif (($b & 0xFC) == 0xF8) $n=4; # 111110bb
                elseif (($b & 0xFE) == 0xFC) $n=5; # 1111110b
                else return false; # Does not match any model

                for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                    if ((++$i == $len) || ((ord($Str[$i]) & 0xC0) != 0x80))
                        return false;
                }
            }
            return true;
        }

        function unhtmlspecialchars( $string ){
            if( strpos( $string, '&' ) === false ) return $string;

            $string = str_replace ( '&lt;', '<', $string );
            $string = str_replace ( '&gt;', '>', $string );
            $string = str_replace ( '&amp;', '&', $string );
            $string = str_replace ( '&#039;', '\'', $string );
            $string = str_replace ( '&quot;', '"', $string );

            return $string;
        }

        function unhtmlentities( $string, $charset ){

            if( version_compare(phpversion(), '5.0.0', '>=') || (strtoupper($charset)!='UTF-8') ){
                return html_entity_decode( $string, ENT_QUOTES, $charset );
            }

            return $this->html_entity_decode_utf8( $string );
        }

        // Workaround to html_entity_decode not working with UTF-8 in PHP4.
        // code from php.net, author: laurynas dot butkus at gmail dot com
        function html_entity_decode_utf8( $string ) {

            // replace numeric entities
            $string = @preg_replace( '~&#x0*([0-9a-f]+);~ei', 'KFuncs::code2utf(hexdec("\\1"))', $string );
            $string = @preg_replace( '~&#0*([0-9]+);~e', 'KFuncs::code2utf(\\1)', $string );

            // replace literal entities
            if( !isset($this->trans_tbl) ){
                $this->trans_tbl = array();

            $arr_entities = get_html_translation_table(HTML_ENTITIES);
            foreach( $arr_entities as $val=>$key ){
                $this->trans_tbl[$key] = utf8_encode( $val );
            }

            // Augment the incomplete set returned by PHP4 with the left out HTML4 entities.
            // http://en.wikipedia.org/wiki/List_of_XML_and_HTML_character_entity_references
            $arr_HTML4_entities = array(
                                $this->code2utf(338) => "&OElig;",
                                $this->code2utf(339) => "&oelig;",
                                $this->code2utf(352) => "&Scaron;",
                                $this->code2utf(353) => "&scaron;",
                                $this->code2utf(376) => "&Yuml;",
                                $this->code2utf(402) => "&fnof;",
                                $this->code2utf(710) => "&circ;",
                                $this->code2utf(732) => "&tilde;",
                                $this->code2utf(913) => "&Alpha;",
                                $this->code2utf(914) => "&Beta;",
                                $this->code2utf(915) => "&Gamma;",
                                $this->code2utf(916) => "&Delta;",
                                $this->code2utf(917) => "&Epsilon;",
                                $this->code2utf(918) => "&Zeta;",
                                $this->code2utf(919) => "&Eta;",
                                $this->code2utf(920) => "&Theta;",
                                $this->code2utf(921) => "&Iota;",
                                $this->code2utf(922) => "&Kappa;",
                                $this->code2utf(923) => "&Lambda;",
                                $this->code2utf(924) => "&Mu;",
                                $this->code2utf(925) => "&Nu;",
                                $this->code2utf(926) => "&Xi;",
                                $this->code2utf(927) => "&Omicron;",
                                $this->code2utf(928) => "&Pi;",
                                $this->code2utf(929) => "&Rho;",
                                $this->code2utf(931) => "&Sigma;",
                                $this->code2utf(932) => "&Tau;",
                                $this->code2utf(933) => "&Upsilon;",
                                $this->code2utf(934) => "&Phi;",
                                $this->code2utf(935) => "&Chi;",
                                $this->code2utf(936) => "&Psi;",
                                $this->code2utf(937) => "&Omega;",
                                $this->code2utf(945) => "&alpha;",
                                $this->code2utf(946) => "&beta;",
                                $this->code2utf(947) => "&gamma;",
                                $this->code2utf(948) => "&delta;",
                                $this->code2utf(949) => "&epsilon;",
                                $this->code2utf(950) => "&zeta;",
                                $this->code2utf(951) => "&eta;",
                                $this->code2utf(952) => "&theta;",
                                $this->code2utf(953) => "&iota;",
                                $this->code2utf(954) => "&kappa;",
                                $this->code2utf(955) => "&lambda;",
                                $this->code2utf(956) => "&mu;",
                                $this->code2utf(957) => "&nu;",
                                $this->code2utf(958) => "&xi;",
                                $this->code2utf(959) => "&omicron;",
                                $this->code2utf(960) => "&pi;",
                                $this->code2utf(961) => "&rho;",
                                $this->code2utf(962) => "&sigmaf;",
                                $this->code2utf(963) => "&sigma;",
                                $this->code2utf(964) => "&tau;",
                                $this->code2utf(965) => "&upsilon;",
                                $this->code2utf(966) => "&phi;",
                                $this->code2utf(967) => "&chi;",
                                $this->code2utf(968) => "&psi;",
                                $this->code2utf(969) => "&omega;",
                                $this->code2utf(977) => "&thetasym;",
                                $this->code2utf(978) => "&upsih;",
                                $this->code2utf(982) => "&piv;",
                                $this->code2utf(8194) => "&ensp;",
                                $this->code2utf(8195) => "&emsp;",
                                $this->code2utf(8201) => "&thinsp;",
                                $this->code2utf(8204) => "&zwnj;",
                                $this->code2utf(8205) => "&zwj;",
                                $this->code2utf(8206) => "&lrm;",
                                $this->code2utf(8207) => "&rlm;",
                                $this->code2utf(8211) => "&ndash;",
                                $this->code2utf(8212) => "&mdash;",
                                $this->code2utf(8216) => "&lsquo;",
                                $this->code2utf(8217) => "&rsquo;",
                                $this->code2utf(8218) => "&sbquo;",
                                $this->code2utf(8220) => "&ldquo;",
                                $this->code2utf(8221) => "&rdquo;",
                                $this->code2utf(8222) => "&bdquo;",
                                $this->code2utf(8224) => "&dagger;",
                                $this->code2utf(8225) => "&Dagger;",
                                $this->code2utf(8226) => "&bull;",
                                $this->code2utf(8230) => "&hellip;",
                                $this->code2utf(8240) => "&permil;",
                                $this->code2utf(8242) => "&prime;",
                                $this->code2utf(8243) => "&Prime;",
                                $this->code2utf(8249) => "&lsaquo;",
                                $this->code2utf(8250) => "&rsaquo;",
                                $this->code2utf(8254) => "&oline;",
                                $this->code2utf(8260) => "&frasl;",
                                $this->code2utf(8364) => "&euro;",
                                $this->code2utf(8465) => "&image;",
                                $this->code2utf(8472) => "&weierp;",
                                $this->code2utf(8476) => "&real;",
                                $this->code2utf(8482) => "&trade;",
                                $this->code2utf(8501) => "&alefsym;",
                                $this->code2utf(8592) => "&larr;",
                                $this->code2utf(8593) => "&uarr;",
                                $this->code2utf(8594) => "&rarr;",
                                $this->code2utf(8595) => "&darr;",
                                $this->code2utf(8596) => "&harr;",
                                $this->code2utf(8629) => "&crarr;",
                                $this->code2utf(8656) => "&lArr;",
                                $this->code2utf(8657) => "&uArr;",
                                $this->code2utf(8658) => "&rArr;",
                                $this->code2utf(8659) => "&dArr;",
                                $this->code2utf(8660) => "&hArr;",
                                $this->code2utf(8704) => "&forall;",
                                $this->code2utf(8706) => "&part;",
                                $this->code2utf(8707) => "&exist;",
                                $this->code2utf(8709) => "&empty;",
                                $this->code2utf(8711) => "&nabla;",
                                $this->code2utf(8712) => "&isin;",
                                $this->code2utf(8713) => "&notin;",
                                $this->code2utf(8715) => "&ni;",
                                $this->code2utf(8719) => "&prod;",
                                $this->code2utf(8721) => "&sum;",
                                $this->code2utf(8722) => "&minus;",
                                $this->code2utf(8727) => "&lowast;",
                                $this->code2utf(8730) => "&radic;",
                                $this->code2utf(8733) => "&prop;",
                                $this->code2utf(8734) => "&infin;",
                                $this->code2utf(8736) => "&ang;",
                                $this->code2utf(8743) => "&and;",
                                $this->code2utf(8744) => "&or;",
                                $this->code2utf(8745) => "&cap;",
                                $this->code2utf(8746) => "&cup;",
                                $this->code2utf(8747) => "&int;",
                                $this->code2utf(8756) => "&there4;",
                                $this->code2utf(8764) => "&sim;",
                                $this->code2utf(8773) => "&cong;",
                                $this->code2utf(8776) => "&asymp;",
                                $this->code2utf(8800) => "&ne;",
                                $this->code2utf(8801) => "&equiv;",
                                $this->code2utf(8804) => "&le;",
                                $this->code2utf(8805) => "&ge;",
                                $this->code2utf(8834) => "&sub;",
                                $this->code2utf(8835) => "&sup;",
                                $this->code2utf(8836) => "&nsub;",
                                $this->code2utf(8838) => "&sube;",
                                $this->code2utf(8839) => "&supe;",
                                $this->code2utf(8853) => "&oplus;",
                                $this->code2utf(8855) => "&otimes;",
                                $this->code2utf(8869) => "&perp;",
                                $this->code2utf(8901) => "&sdot;",
                                $this->code2utf(8968) => "&lceil;",
                                $this->code2utf(8969) => "&rceil;",
                                $this->code2utf(8970) => "&lfloor;",
                                $this->code2utf(8971) => "&rfloor;",
                                $this->code2utf(9001) => "&lang;",
                                $this->code2utf(9002) => "&rang;",
                                $this->code2utf(9674) => "&loz;",
                                $this->code2utf(9824) => "&spades;",
                                $this->code2utf(9827) => "&clubs;",
                                $this->code2utf(9829) => "&hearts;",
                                $this->code2utf(9830) => "&diams;"
                            );
                foreach( $arr_HTML4_entities as $val=>$key ){
                    $this->trans_tbl[$key] = $val;
                }
            }

            $str = strtr( $string, $this->trans_tbl );
            return strtr( $string, $this->trans_tbl );
        }

        // Returns the utf string corresponding to the unicode value (from php.net, courtesy - romans@void.lv)
        function code2utf( $num ){
            if ($num < 128) return chr($num);
            if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
            if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
            if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
            return '';
        }

        // Returns an array of integers (unicode values) corresponding to the utf string (from - http://randomchaos.com/documents/?source=php_and_unicode)
        function utf2code( $str ){
            $unicode = array();
            $values = array();
            $lookingFor = 1;

            for( $i = 0; $i < strlen( $str ); $i++ ){
                $thisValue = ord( $str[ $i ] );

                if( $thisValue < 128 ){
                    $unicode[] = $thisValue;
                }
                else{
                    if( count( $values ) == 0 ) $lookingFor = ( $thisValue < 224 ) ? 2 : 3;

                    $values[] = $thisValue;

                    if( count( $values ) == $lookingFor ){
                        $number = ( $lookingFor == 3 ) ?
                            ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
                            ( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );

                        $unicode[] = $number;
                        $values = array();
                        $lookingFor = 1;
                    }
                }
            }
            return $unicode;
        }

        /*
        * This routine converts real numbers into a stringified representation that can
        * be used as keys in indices because they contain no nulls and
        * sort in numeric order inspite of being strings.
        */
        function real_to_str( $r ){
            // This array maps integers between 0 and 63 into base-64 digits.
            // The digits must be chosen such at their ASCII codes are increasing.
            // This means we can not use the traditional base-64 digit set. */
            $zDigit = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz|~";

            if( $r<0.0 ){
                $neg = 1;
                $r = -$r;
                $z .= '-';
            } else {
                $neg = 0;
                $z .= '0';
            }
            $exp = 0;

            if( $r==0.0 ){
                $exp = -1024;
            }else if( $r<(0.5/64.0) ){
                while( $r < 0.5/_64e64 && $exp > -961  ){ $r *= _64e64;  $exp -= 64; }
                while( $r < 0.5/_64e16 && $exp > -1009 ){ $r *= _64e16;  $exp -= 16; }
                while( $r < 0.5/_64e4  && $exp > -1021 ){ $r *= _64e4;   $exp -= 4; }
                while( $r < 0.5/64.0   && $exp > -1024 ){ $r *= 64.0;    $exp -= 1; }
            }else if( $r>=0.5 ){
                while( $r >= 0.5*_64e63 && $exp < 960  ){ $r *= 1.0/_64e64; $exp += 64; }
                while( $r >= 0.5*_64e15 && $exp < 1008 ){ $r *= 1.0/_64e16; $exp += 16; }
                while( $r >= 0.5*_64e3  && $exp < 1020 ){ $r *= 1.0/_64e4;  $exp += 4; }
                while( $r >= 0.5        && $exp < 1023 ){ $r *= 1.0/64.0;   $exp += 1; }
            }
            if( $neg ){
                $exp = -$exp;
                $r = -$r;
            }
            $exp += 1024;
            $r += 0.5;
            if( $exp<0 ) return;
            if( $exp>=2048 || $r>=1.0 ){
                return "~~~~~~~~~~~~";
            }
            $z .= $zDigit[($exp>>6)&0x3f];
            $z .= $zDigit[$exp & 0x3f];
            while( $r>0.0 && $cnt<10 ){
                $digit;
                $r *= 64.0;
                $digit = (int)$r;
                $z .= $zDigit[$digit & 0x3f];
                $r -= $digit;
                $cnt++;
            }
            return $z;
        }

        /*
        * The sort_order makes a difference in that text-type fields may not be
        * introduced by 'b' (as described in the next paragraph).  The
        * first character of a text-type field must be either 'a' (if it is NULL)
        * or 'c'.  Numeric fields will be introduced by 'b' if their content
        * looks like a well-formed number.  Otherwise the 'a' or 'c' will be
        * used.
        *
        * The key is a concatenation of fields.  Each field is terminated by
        * a single 0x00 character.  A NULL field is introduced by an 'a' and
        * is followed immediately by its 0x00 terminator.  A numeric field is
        * introduced by a single character 'b' and is followed by a sequence
        * of characters that represent the number such that a comparison of
        * the character string using strcmp() sorts the numbers in numerical
        * order.  The character strings for numbers are generated using the
        * _real_to_str() function.  A text field is introduced by a
        * 'c' character and is followed by the exact text of the field.  The
        * use of an 'a', 'b', or 'c' character at the beginning of each field
        * guarantees that NULLs sort before numbers and that numbers sort
        * before text.  0x00 characters do not occur except as separators
        * between fields.
        */
        function make_key( $keys ){
          if( !is_array( $keys ) ){
            $tmp = $keys;
            $keys = array();
            $keys[] = $tmp;
          }

          $k = "";
          foreach( $keys as $key ){
            if( is_null( $key ) ){
              $k .= "a" . chr( 0x00 );
            }
            elseif( is_numeric( $key ) ){
              $k .= "b" . $this->real_to_str( (float)$key ) . chr( 0x00 );
            }
            else{
              $k .= sprintf( "%s%s%s", "c", $key, chr( 0x00 ) );
            }
          }
          return $k;
        }

        function strip_tags( $val ){ // Used for saving searchable version of inputted text

            // ignore the 'script', 'style' tags
            $parser = new KHTMLParser( $val, array('script', 'style'), 1 );
            $val = $parser->get_HTML();

            $val = $this->unhtmlentities( $val, K_CHARSET ); //html_entity_decode( $val, ENT_NOQUOTES );
            $val = trim( strip_tags($val) );

            $val = htmlspecialchars( $val, ENT_QUOTES, K_CHARSET ); //to match the xss cleaned $_GET search terms
            $val = preg_replace( "/[\r\n\t]+/", " ", $val );
            $val = preg_replace( "/[ ]+/", " ", $val );
            return $val;
        }

        // Differs from 'htmlspecialchars' in that it does not encode '&' of already encoded entities like &nbsp;
        function escape_HTML( $str ){
            $str = preg_replace( "/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", htmlspecialchars($str, ENT_QUOTES, K_CHARSET) );

            return $str;
        }

        // Truncates a string to the given length
        function excerpt( $str_utf, $count, $trail='' ){
            if( @preg_match('/^.{1}/us', $str_utf, $matches) == 1 ){ // quick check for UTF-8 well-formedness
                if( function_exists('mb_strlen') && function_exists('mb_substr') ){
                    $strlen = mb_strlen( $str_utf, 'UTF-8' );
                    if( $count < $strlen ){
                        $substr = mb_substr( $str_utf, 0, $count, 'UTF-8' ) . $trail;
                    }
                    else{
                        $substr = $str_utf;
                    }
                }
                else{
                    $strlen = strlen( utf8_decode($str_utf) );
                    if( $count < $strlen ){
                        $pattern = '#^(.{'.$count.'})#us';
                        @preg_match( $pattern, $str_utf, $matches );
                        $substr = $matches[1] . $trail;
                    }
                    else{
                        $substr = $str_utf;
                    }
                }
            }
            else{
                $strlen = strlen( $str_utf );
                if( $count < $strlen ){
                    $substr = substr( $str_utf, 0, $count ) . $trail;
                }
                else{
                    $substr = $str_utf;
                }
            }

            return $substr;

        }

        function get_url(){
            $url = 'http';
            if( K_HTTPS ){
                $url .= 's';
            }
            $url .= '://';
            $url .= $_SERVER['HTTP_HOST'];
            $port = '';
            if( $_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443' ){
                $port = ':' . $_SERVER['SERVER_PORT'];
            }
            if( strpos($_SERVER['HTTP_HOST'], ':')===false ) $url .= $port;

            $chopped_url = @parse_url( $_SERVER['REQUEST_URI'] );
            if( $chopped_url===false || !$chopped_url['path'] ) return false;
            $chopped_url['path'] = trim( preg_replace( '|/index\.php/*?$|', '/', $chopped_url['path']) );
            if( !$chopped_url['path'] ) return false;

            $url .= $chopped_url['path'];
            return $url;
        }

        function pathinfo( $path ){
            if( version_compare(phpversion(), "5.2.0", "<") ) {
                $temp = pathinfo( $path );
                if( $temp['extension'] ){
                    $temp['filename'] = substr( $temp['basename'], 0 ,strlen($temp['basename'])-strlen($temp['extension'])-1 );
                }
                return $temp;
            }
            else{
                return pathinfo($path);
            }
        }

        //function &make_tree( $rows, $tpl_name, $orderbyfield='name', $order='asc' ){
        function &get_folders_tree( $tpl_id, $tpl_name, $orderbyfield='name', $order='asc' ){
            global $DB;

            if( array_key_exists($tpl_id, $this->cached_folder_trees) ){
                $tree = &$this->cached_folder_trees[$tpl_id];
                if( $tree->cmp_field!=$orderbyfield || $tree->cmp_order!=$order ){
                    $tree->set_sort( $orderbyfield, $order );
                    $tree->sort(1);
                }
            }
            else{
                $tree = new KFolder( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError()/*dummy*/ );
                $tree->set_sort( $orderbyfield, $order );

                $cols = array( 'cf.*, count(cp.id) as count' );
                $tables = K_TBL_FOLDERS . " cf left outer join " . K_TBL_PAGES . " cp on (cf.template_id = cp.template_id and cf.id = cp.page_folder_id and cp.publish_date <> '0000-00-00 00:00:00')";
                $sql = 'cf.template_id=' . $DB->sanitize( $tpl_id );
                $sql .= ' group by cf.id';

                $rows = $DB->select( $tables, $cols, $sql );

                $folders = array();
                foreach( $rows as $r ){
                    $f = new KFolder( $r, $tpl_name, $tree );
                    //$f->set_sort( $orderbyfield, $order );
                    $folders[ $f->id ] = &$f;
                    unset( $f );
                }

                foreach( $folders as  $f ){
                    $folder = &$folders[$f->id];
                    if( isset($folders[$f->pid]) ){
                        $folders[$f->pid]->add_child( $folder );
                    }
                    else{
                        $tree->add_child( $folder );
                    }
                }
                $tree->sort(1);
                $tree->set_count(); // consolidated count of pages within folders
                $tree->set_children_count();
                $this->cached_folder_trees[$tpl_id] = &$tree;
            }

            return $tree;
        }

        // a variation of the get_folders_tree function above.
        function &get_nested_pages( $tpl_id, $tpl_name, $tpl_access_level, $orderbyfield='weightx', $order='asc', $force=0 ){
            global $DB;

            if( array_key_exists($tpl_id, $this->cached_nested_pages) && !$force ){
                $tree = &$this->cached_nested_pages[$tpl_id];
                if( $tree->cmp_field!=$orderbyfield || $tree->cmp_order!=$order ){
                    $tree->set_sort( $orderbyfield, $order );
                    $tree->sort(1);
                }
            }
            else{

                $tree = new KNestedPage( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError()/*dummy*/ );
                $tree->set_sort( $orderbyfield, $order );
                $tree->crumbs = null; // will be filled, if required, by calling tags e.g. 'nested_pages'.

                $cols = array( 'p.id, p.page_title as title, p.page_name as name, p.creation_date, p.publish_date, p.access_level, p.comments_count,
                              p.nested_parent_id as pid, p.weight, p.show_in_menu, p.menu_text, p.is_pointer, p.pointer_link, p.pointer_link_detail, p.open_external, p.masquerades, p.strict_matching,
                              count(p2.id) as drafts_count' );
                $tables = K_TBL_PAGES . " p left outer join " . K_TBL_PAGES . " p2 on p.id = p2.parent_id";
                $sql = 'p.template_id=' . $DB->sanitize( $tpl_id );
                $sql .= " AND p.parent_id=0"; //skip drafts
                $sql .= ' group by p.id';
                $rows = $DB->select( $tables, $cols, $sql );

                $folders = array();
                foreach( $rows as $r ){
                    $r['template_id'] = $tpl_id;
                    $r['template_name'] = $tpl_name;
                    $r['template_access_level'] = $tpl_access_level;

                    $f = new KNestedPage( $r, $tpl_name, $tree );
                    $folders[ $f->id ] = &$f;
                    unset( $f );
                }

                foreach( $folders as  $f ){
                    $folder = &$folders[$f->id];
                    if( isset($folders[$f->pid]) ){
                        $folders[$f->pid]->add_child( $folder );
                    }
                    else{
                        $tree->add_child( $folder );
                    }
                }
                $tree->sort(1);
                $tree->set_children_count();
                $this->cached_nested_pages[$tpl_id] = &$tree;
            }

            return $tree;
        }

        function set_userinfo_in_context(){
            global $AUTH, $CTX;

            if( !$AUTH->user->disabled ){
                $CTX->set( 'k_user_id', $AUTH->user->id );
                $CTX->set( 'k_user_name', $AUTH->user->name );
                $CTX->set( 'k_user_title', $AUTH->user->title );
                $CTX->set( 'k_user_email', $AUTH->user->email );
                $CTX->set( 'k_user_access_level', $AUTH->user->access_level );
                $CTX->set( 'k_user_disabled', '0' );

                if( $AUTH->user->id != -1 ){
                    $CTX->set( 'k_logged_in', 1 );
                    $CTX->set( 'k_logout_link', $this->get_logout_link() );
                }
                else{
                    $CTX->set( 'k_logged_out', 1 );
                    $CTX->set( 'k_login_link', $this->get_login_link() );
                }
            }
            else{
                $CTX->set( 'k_user_disabled', '1' );
                $CTX->set( 'k_user_access_level', 0 );
            }

            // HOOK: alter_user_set_context
            $this->dispatch_event( 'alter_user_set_context' );
        }

        function access_levels_dropdown( $selected_level, $max_level, $min_level=0, $inherited=0 ){
            global $DB;

            $html = "<select name=\"f_k_levels_list\"";
            if( $inherited ) $html .= " disabled=1";
            $html .= ">\n";
            $rs = $DB->select( K_TBL_USER_LEVELS, array('name', 'title', 'k_level'), "k_level <= ".$DB->sanitize( $max_level )." AND k_level >= ".$DB->sanitize( $min_level )." ORDER BY k_level DESC" );
            if( count($rs) ){
                foreach( $rs as $rec ){
                    $html .= "<option value=\"".$rec['k_level']."\"";
                    $html .= ( $rec['k_level']==$selected_level ) ? ' selected="selected"' : '';
                    $html .= ">".$this->t($rec['name'])."</option>";
                }
            }
            $html .= "</select>";

            return $html;
        }

        // Adjusts current server time using the provided offset
        // to return the current desktop time (of the user).
        function get_current_desktop_time(){ //+5.5 India
            return gmdate( 'Y-m-d H:i:s', (time() + (K_GMT_OFFSET * 60 * 60)) );
        }

        function date_dropdowns( $date='' ){
            global $PAGE;
            //TODO: allow localization
            $arrMonths = array('01'=>'Jan', '02'=>'Feb', '03'=>'Mar', '04'=>'Apr',
                               '05'=>'May', '06'=>'Jun', '07'=>'Jul', '08'=>'Aug',
                               '09'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dec');

            if( !$date ) $date = $PAGE->publish_date;
            if( !$date || $date=='0000-00-00 00:00:00' ) $date = $this->get_current_desktop_time();
            $yy = substr( $date, 0, 4 );
            $mm = substr( $date, 5, 2 );
            $dd = substr( $date, 8, 2 );
            $h = substr( $date, 11, 2 );
            $m = substr( $date, 14, 2 );
            $s = substr( $date, 17, 2 );

            $year = '<input type="text" id="f_k_year" name="f_k_year" value="' . $yy . '" size="4" maxlength="4" autocomplete="off" />';
            $month = "<select id=\"f_k_month\" name=\"f_k_month\" >\n";
            foreach( $arrMonths as $k=>$v ){
                $month .= "<option value=\"".$k."\"";
                $month .= ( $k==$mm ) ? ' selected="selected"' : '';
                $month .= ">".$v."</option>";
            }
            $month .= "</select>";
            $day = '<input type="text" id="f_k_day" name="f_k_day" value="' . $dd . '" size="2" maxlength="2" autocomplete="off" />';
            $hour = '<input type="text" id="f_k_hour" name="f_k_hour" value="' . $h . '" size="2" maxlength="2" autocomplete="off" />';
            $min = '<input type="text" id="f_k_min" name="f_k_min" value="' . $m . '" size="2" maxlength="2" autocomplete="off" />';
            $sec = '<input type="hidden" id="f_k_sec" name="f_k_sec" value="' . $s . '" size="2" maxlength="2" autocomplete="off" />';

            $date = $month . $day . ', ' . $year . ' @ ' . $hour . ' : ' . $min . $sec;
            return $date;
        }

        function sanitize_posted_date(){
            $year = intval( $_POST['f_k_year'] );
            if( $year <= 0 ) $year = date('Y');
            if( $year < 1970 ) $year = '1970';

            $month = intval( $_POST['f_k_month'] );
            if( $month <= 0 ) $month = date('n');
            if( $month > 12 ) $month = $month % 12;

            $day = intval( $_POST['f_k_day'] );
            $days_in_month = date( "t", mktime(0, 0, 0, $month, 1, $year) );
            if( $day <= 0 ) $day = date('j');
            if( $day > $days_in_month ) $day = $days_in_month;

            $hour = intval( $_POST['f_k_hour'] );
            if( $hour < 0 ) $hour = date('H');
            if( $hour > 23 ) $hour = $hour % 24;

            $min = intval( $_POST['f_k_min'] );
            if( $min < 0 ) $min = date('i');
            if( $min > 59 ) $min = $min % 60;

            $sec = intval( $_POST['f_k_sec'] );
            if( $sec < 0 ) $sec = date('s');
            if( $sec > 59 ) $sec = $sec % 60;

            $iso_format = "%04d-%02d-%02d %02d:%02d:%02d";
            return sprintf( $iso_format, $year, $month, $day, $hour, $min, $sec );

        }

        function make_date( $str ){
            /* valid strings
            2008-05-14 02:08:45
            2008-05-14 02:08
            2008-05-14 02
            2008-05-14
            2008-05
            2008
            */
            $pattern = '/([0-9]{4})(?:-([0-9]{1,2}))?(?:-([0-9]{1,2}))?(?:\s+([0-9]{1,2}))?(?:\:([0-9]{1,2}))?(?:\:([0-9]{1,2}))?/';
            preg_match( $pattern, $str, $matches );
            $year = ($matches[1]) ? $matches[1] : '1970';
            if( $year=='0000' ) $year = '1970';
            $month = ($matches[2]) ? $matches[2] : '01';
            $day = ($matches[3]) ? $matches[3] : '01';
            $hour = ($matches[4]) ? $matches[4] : '00';
            $min = ($matches[5]) ? $matches[5] : '00';
            $sec = ($matches[6]) ? $matches[6] : '00';

            //return @date('Y-m-d H:i:s', @mktime($hour, $min, $sec, $month, $day, $year) ); // mktime can only handle a range of 1970 to 2038
            $iso_format = "%04d-%02d-%02d %02d:%02d:%02d";
            return sprintf( $iso_format, $year, $month, $day, $hour, $min, $sec );

        }

        function cmp_date( $str_date1, $str_date2, $v ){
            if( !$str_date1 ) return $str_date2;
            if( !$str_date2 ) return $str_date1;

            $timestamp1 = @strtotime( $str_date1 );
            $timestamp2 = @strtotime( $str_date2 );
            if( ($timestamp1 === false) || ($timestamp1 == -1) ){
                return $str_date2; // we'll assume the second date is valid
            }
            if( ($timestamp2 === false) || ($timestamp2 == -1) ){
                return $str_date1;
            }

            if( $v ){
                return ( $timestamp1 < $timestamp2 ) ? $str_date2 : $str_date1;
            }
            else{
                return ( $timestamp1 >= $timestamp2 ) ? $str_date2 : $str_date1;
            }
        }

        function smaller_date( $str_date1, $str_date2 ){
            return $this->cmp_date( $str_date1, $str_date2, 0 );
        }

        function greater_date( $str_date1, $str_date2 ){
            return $this->cmp_date( $str_date1, $str_date2, 1 );
        }

        function get_link( $masterpage ){
            if( K_PRETTY_URLS ){
                return K_SITE_URL . $this->get_pretty_template_link( $masterpage );
            }
            else{
                return K_SITE_URL . $masterpage;
            }
        }

        function get_login_link( $redirect='' ){
            global $AUTH;

            $link = '';
            $redirect = trim( $redirect );


            // HOOK: get_login_link
            $this->dispatch_event( 'get_login_link', array(&$link, &$redirect) );

            if( trim($link)==false ){
                if( $AUTH->user->id == -1 ){
                    if( !strlen($redirect) ){ $redirect = $_SERVER["REQUEST_URI"]; }
                    $link = K_ADMIN_URL.'login.php?redirect='.urlencode( $redirect );
                }
                else{
                    $link = 'javascript:void(0)';
                }
            }

            return $link;
        }

        function get_logout_link( $redirect='' ){
            global $AUTH;

            $link = '';
            $redirect = trim( $redirect );

            // HOOK: get_logout_link
            $this->dispatch_event( 'get_logout_link', array(&$link, &$redirect) );

            if( trim($link)==false ){
                if( $AUTH->user->id != -1 ){
                    $nonce = $this->create_nonce( 'logout'.$AUTH->user->id, $AUTH->user->name );
                    if( !strlen($redirect) ){ $redirect = $_SERVER["REQUEST_URI"]; }
                    $link = K_ADMIN_URL.'login.php?act=logout&nonce='.$nonce. '&redirect='.urlencode( $redirect );
                }
                else{
                    $link = 'javascript:void(0)';
                }
            }

            return $link;
        }

        function get_archive_link( $template_name, $year, $month, $day ){
            global $PAGE;

            if( $month ) $month = sprintf( "%02d", $month );
            if( $day ) $day = sprintf( "%02d", $day );
            if( K_PRETTY_URLS ){
                $link = $this->get_pretty_template_link( $template_name ) . $year. '/';
                if( $month ) $link .= $month . '/';
                if( $day ) $link .= $day . '/';
            }
            else{
                $link = $template_name . '?d=' . $year;
                if( $month ) $link .= $month;
                if( $day ) $link .= $day;
            }
            return $link;
        }

        function get_pretty_template_link( $template_name ){
            return $this->get_pretty_template_link_ex( $template_name, $dummy );
        }

        function get_pretty_template_link_ex( $template_name, &$is_index, $consider_masquerading=1 ){
            global $DB;

            // if $consider_masquerading set, if the template is infact being masqueraded by a nested-page (of index.php),
            // the template's name gets substituted by nested-page's path. Requires K_PRETTY_URLS to be on and Curl to be present.
            if( $consider_masquerading && K_MASQUERADE_ON ){
                if( array_key_exists($template_name, $this->cached_pretty_tplnames) ){
                    return $this->cached_pretty_tplnames[$template_name];
                }

                $rs = $DB->select( K_TBL_PAGES. " p INNER JOIN " .K_TBL_TEMPLATES." t on p.template_id = t.id", array('p.id', 't.id as tid', 't.name', 't.access_level'), "t.name='index.php' AND is_pointer='1' AND masquerades='1' AND pointer_link_detail LIKE 'masterpage=" . $DB->sanitize( $template_name ) . "&%' AND t.nested_pages='1' AND t.clonable='1'" );
                if( count($rs) ){
                    $tree = &$this->get_nested_pages( $rs[0]['tid'], $rs[0]['name'], $rs[0]['access_level'] );
                    if( count($tree->children) ) {
                        $arr = $tree->children[0]->root->get_parents_by_id( $rs[0]['id'] );
                        if( is_array($arr) ){
                            for( $x=count($arr)-1; $x>=0; $x-- ){
                                $link_masquering_page .= $arr[$x]->name . '/';
                            }
                            $link_masquering_page = $this->get_pretty_template_link_ex( $rs[0]['name'], $dummy ) . $link_masquering_page;
                            $this->cached_pretty_tplnames[$template_name] = $link_masquering_page;
                            return $link_masquering_page;
                        }
                    }
                }
            }

            $is_index = 0;
            $arr = explode( "/", $template_name );
            $last_elem = array_pop( $arr );
            $pos = strrpos( $last_elem, '.' );
            if( $pos !== false ){
                $last_elem = substr( $last_elem, 0, $pos );
                if( strtolower($last_elem) == 'index' ){
                    $last_elem = '';
                    $is_index = 1;
                }
                else{
                    $last_elem .= '/';
                }
            }
            if( count($arr) ){
                $last_elem = implode( "/", $arr ) . '/' . $last_elem;
            }

            if( $consider_masquerading ) $this->cached_pretty_tplnames[$template_name] = $last_elem;
            return $last_elem;
        }

        function generate_rewrite_rules(){
            global $DB, $FUNCS;

            //$rs = $DB->select( K_TBL_TEMPLATES, array('name'), 'clonable=1 AND executable=1' );
            $rs = $DB->select( K_TBL_TEMPLATES, array('name', 'custom_params'), '1=1' );
            if( count($rs) ){
                foreach( $rs as $key=>$val ){
                    $is_index = 0;
                    $pretty_tpl_names[$key] = $this->get_pretty_template_link_ex( $val['name'], $is_index, 0 );
                    $depth[$key] = count( explode("/", $val['name']) );
                    $rs[$key]['pretty_name'] = $pretty_tpl_names[$key];
                    $rs[$key]['is_index'] = $is_index;
                }

                // Sort templates according to nesting levels and names
                array_multisort( $depth, SORT_DESC, SORT_NUMERIC, $pretty_tpl_names, SORT_DESC, SORT_STRING, $rs );

                // Loop once again through the templates, generating rewrite rules for each.
                $header = '';
                $for_index = '';
                $body = '';
                $sep = "\n";
                foreach( $rs as $key=>$val ){
                    $body .= $sep . '#'. $val['name'] . $sep;
                    if( $val['is_index'] ){
                        //RewriteRule ^news/index.php$ "news/" [R=301,L,QSA]
                        $for_index .= 'RewriteRule ^'.$val['name'].'$ "'.$val['pretty_name'].'" [R=301,L,QSA]' . $sep;
                    }
                    else{
                        // Redirect if not trailing slash
                        //RewriteRule ^news/test$ "$0/" [R=301,L,QSA]
                        $body .= 'RewriteRule ^'.substr( $val['pretty_name'], 0, strlen($val['pretty_name'])-1 )  .'$ "$0/" [R=301,L,QSA]' . $sep;

                        //RewriteRule ^news/test/$ news/test.php [L,QSA]
                        $body .= 'RewriteRule ^'.$val['pretty_name'].'$ '.$val['name'].' [L,QSA]' . $sep;
                    }

                    $name = ( $val['is_index'] ) ? $val['pretty_name'] : $val['name'];

                    // is routable? (i.e. has custom routes)
                    $has_custom_routes = 0;
                    $custom_params = array();
                    if( strlen($val['custom_params']) ){
                        $custom_params = $FUNCS->unserialize($val['custom_params']);
                        if( is_array($custom_params) && $custom_params['routable'] ){
                            $has_custom_routes = 1;
                        }
                    }

                    if( !$has_custom_routes ){
                        // Page
                        //RewriteRule ^news/test/.*?([^\.\/]*)\.html$ news/test.php?pname=$1 [L,QSA]
                        $body .= 'RewriteRule ^'. $val['pretty_name'].'.*?([^\.\/]*)\.html$ '.$name.'?pname=$1 [L,QSA]' . $sep;

                        // Archives
                        //RewriteRule ^news/([1-2]\d{3})/(?:(0[1-9]|1[0-2])/(?:(0[1-9]|1[0-9]|2[0-9]|3[0-1])/)?)?$  [L,QSA]
                        $body .= 'RewriteRule ^'.$val['pretty_name'].'([1-2]\d{3})/(?:(0[1-9]|1[0-2])/(?:(0[1-9]|1[0-9]|2[0-9]|3[0-1])/)?)?$ '.$name.'?d=$1$2$3 [L,QSA]' . $sep;

                        // Folder
                        //RewriteRule ^news/test/[^\.]*?([^/\.]*)/$ news/test.php?fname=$1 [L,QSA]
                        $body .= 'RewriteRule ^'.$val['pretty_name'].'[^\.]*?([^/\.]*)/$ '.$name.'?fname=$1 [L,QSA]' . $sep;

                        // Folder redirect if not trailing slash
                        //RewriteRule ^news/test/[^\.]*?([^/\.]*)$ "$0/" [R=301,L,QSA]
                        //RewriteRule ^\w[^\.]*?([^/\.]*)$ "$0/" [R=301,L,QSA]
                        $n = (strlen($val['pretty_name'])) ? $val['pretty_name'] : '\w';
                        $body .= 'RewriteRule ^'.$n.'[^\.]*?([^/\.]*)$ "$0/" [R=301,L,QSA]' . $sep;
                    }
                    else{
                        //RewriteRule ^news/test/(+*?)$ news/test.php?q=$1 [L,QSA]
                        $body .= 'RewriteRule ^'. $val['pretty_name'].'(.+?)$ '.$name.'?q=$1 [L,QSA]' . $sep;
                    }
                }

                // Send back the consolidated rules
                $header .= 'Options +SymLinksIfOwnerMatch -MultiViews' . $sep;
                $header .= '<IfModule mod_rewrite.c>' . $sep;
                $header .= 'RewriteEngine On' . $sep;
                $header .= $sep;
                $header .= '#If your website is installed in a subfolder, change the line below to reflect the path to the subfolder.' . $sep;
                $header .= '#e.g. for http://www.example.com/subdomain1/subdomain2/ make it RewriteBase /subdomain1/subdomain2' . $sep;
                $header .= 'RewriteBase /' . $sep;
                $header .= $sep;
                $header .= '#If you wish to use a custom 404 page, place a file named 404.php in your website\'s root and uncomment the line below.' . $sep;
                $header .= '#If your website is installed in a subfolder, change the line below to reflect the path to the subfolder.' . $sep;
                $header .= '#e.g. for http://www.example.com/subdomain1/subdomain2/ make it ErrorDocument 404 /subdomain1/subdomain2/404.php' . $sep;
                $header .= '#ErrorDocument 404 /404.php' . $sep;
                $header .= $sep;
                $header .= '#If your site begins with \'www\', uncomment the following two lines' . $sep;
                $header .= '#RewriteCond %{HTTP_HOST} !^www\.' . $sep;
                $header .= '#RewriteRule ^(.*)$ http://www.%{HTTP_HOST}/$1 [R=301,L]' . $sep;
                $header .= $sep;
                $header .= $sep;
                $header .= '#DO NOT EDIT BELOW THIS' . $sep;
                $header .= $sep;
                $header .= $for_index;
                $header .= $sep;
                $header .= 'RewriteCond %{REQUEST_FILENAME} -d [OR]' . $sep;
                $header .= 'RewriteCond %{REQUEST_FILENAME} -f' . $sep;
                $header .= 'RewriteRule . - [L]' . $sep;

                $footer = '</IfModule>';

                return $header . $body . $footer;
            }
        }

        // Given an internal link, analyzes it and returns details about it (i.e. view, masterpage, page_id etc)
        function analyze_link( $link ){
            global $DB;

            // works on local links only..
            $link = trim( $link );
            if( strpos($link, K_SITE_URL)!==0 ){ return; }

            $link = substr( $link, strlen(K_SITE_URL) );
            $link2 = explode( '#', $link ); // strip off querystring etc. for prettyURLs check
            $link2 = explode( '?', $link2[0] );
            $link2 = $link2[0];

            $rs = $DB->select( K_TBL_TEMPLATES, array('name'), '1=1' );
            if( count($rs) ){
                foreach( $rs as $key=>$val ){
                    $is_index = 0;
                    $pretty_tpl_names[$key] = $this->get_pretty_template_link_ex( $val['name'], $is_index, 0 );
                    $depth[$key] = count( explode("/", $val['name']) );
                    $rs[$key]['pretty_name'] = $pretty_tpl_names[$key];
                    $rs[$key]['is_index'] = $is_index;
                }

                // Sort templates according to nesting levels and names
                array_multisort( $depth, SORT_DESC, SORT_NUMERIC, $pretty_tpl_names, SORT_DESC, SORT_STRING, $rs );

                //TODO cache results here

                // Loop once again through the templates, testing the link against each.
                foreach( $rs as $key=>$val ){
                    $name = ( $val['is_index'] ) ? $val['pretty_name'].'(?:index.php)?' : $val['name'];

                    // First try the non-pretty variations
                    // 1. page-view
                    $pattern = '#^'.$name.'\?p=(\d+).*#i';
                    $replacement = 'masterpage='.$val['name'].'&is_page=1&p=$1';
                    $ret = @preg_replace( $pattern, $replacement, $link );
                    if( $ret!=$link ){ return $ret; }

                    // 2. archive-view
                    $pattern = '#^'.$name.'\?d=([1-2]\d{3})(?:(0[1-9]|1[0-2])(?:(0[1-9]|1[0-9]|2[0-9]|3[0-1]))?)?.*#i';
                    $replacement = 'masterpage='.$val['name'].'&is_archive=1&yy=$1&mm=$2&dd=$3';
                    $ret = @preg_replace( $pattern, $replacement, $link );
                    if( $ret!=$link ){ return $ret; }

                    // 3. folder-view
                    $pattern = '#^'.$name.'\?f=(\d+).*#i';
                    $replacement = 'masterpage='.$val['name'].'&is_folder=1&f=$1';
                    $ret = @preg_replace( $pattern, $replacement, $link );
                    if( $ret!=$link ){ return $ret; }

                    // 4. home-view
                    $pattern = '#^'.$val['name'].'.*#i';
                    $replacement = 'masterpage='.$val['name'].'&is_home=1';
                    $ret = @preg_replace( $pattern, $replacement, $link );
                    if( $ret!=$link ){ return $ret; }

                    // Next prettyURLs using QS stripped off link
                    // 5. home-view
                    if( $val['pretty_name'] ){
                        $pattern = '#^'.$val['pretty_name'].'?$#i';
                        $replacement = 'masterpage='.$val['name'].'&is_home=1';
                        $ret = @preg_replace( $pattern, $replacement, $link2 );
                        if( $ret!=$link2 ){ return $ret; }
                    }

                    // 6. page-view
                    $pattern = '#^'.$val['pretty_name'].'.*?([0-9a-z-_]*)\.html$#i';
                    $replacement = 'masterpage='.$val['name'].'&is_page=1&pname=$1';
                    $ret = @preg_replace( $pattern, $replacement, $link2 );
                    if( $ret!=$link2 ){ return $ret; }

                    // 7. archive-view
                    $pattern = '#^'.$val['pretty_name'].'([1-2]\d{3})(?:/(0[1-9]|1[0-2])(?:/(0[1-9]|1[0-9]|2[0-9]|3[0-1]))?)?/?$#i';
                    $replacement = 'masterpage='.$val['name'].'&is_archive=1&yy=$1&mm=$2&dd=$3';
                    $ret = @preg_replace( $pattern, $replacement, $link2 );
                    if( $ret!=$link2 ){ return $ret; }

                    // 8. folder-view
                    $pattern = '#^'.$val['pretty_name'].'[^\.]*?([0-9a-z-_]+)/?$#i';
                    $replacement = 'masterpage='.$val['name'].'&is_folder=1&fname=$1';
                    $ret = @preg_replace( $pattern, $replacement, $link2 );
                    if( $ret!=$link2 ){ return $ret; }
                }
            }
        }

        function hash_hmac( $key, $data ){
            if ( !function_exists('hash_hmac') ){
                // http://www.php.net/manual/
                // RFC 2104 HMAC implementation for php.
                // Creates an md5 HMAC.
                // Eliminates the need to install mhash to compute a HMAC
                // Hacked by Lance Rushing
                $b = 64; // byte length for md5
                if (strlen($key) > $b) {
                    $key = pack("H*",md5($key));
                }
                $key  = str_pad($key, $b, chr(0x00));
                $ipad = str_pad('', $b, chr(0x36));
                $opad = str_pad('', $b, chr(0x5c));
                $k_ipad = $key ^ $ipad;
                $k_opad = $key ^ $opad;
                return md5($k_opad  . pack("H*",md5($k_ipad . $data)));

            }
            else{
                return hash_hmac( 'md5', $key, $data );
            }
        }

        function generate_key( $len ){
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
            $key = '';
            for( $i = 0; $i < $len; $i++ ){
                $pos = rand(0, strlen($chars)-1);
                $key .= $chars{$pos};
            }
            return $key;
        }

        function create_nonce( $action, $username='', $last_period=0 ){
            global $AUTH;

            // Three things go into creating a nonce-
            // Current user, the action this nonce is created for and a time period
            // for which the nonce remains valid
            $valid_for = ceil( (time() + (K_GMT_OFFSET * 60 * 60))/(3600 * 12) ); // valid for 12 to 24 hrs server local time
            if( $last_period ) $valid_for--;

            if( empty($username) ){
                if( $AUTH->user->id != -1 ){ // User logged-in
                    $username = $AUTH->user->name;
                }
                else{ // when no user logged-in, use session_id as unique id
                    if( !session_id() ) @session_start();
                    $username = session_id();
                }
            }
            $data = $username . ':' .$action . ':' . $valid_for;
            $key = $this->hash_hmac( $data, $this->_get_nonce_secret_key() );
            $hash = $this->hash_hmac( $data, $key );

            return $hash;
        }

        function validate_nonce( $action, $nonce='' ){
            global $AUTH;
            if( empty($nonce) ){ $nonce = $_REQUEST['nonce']; }

            $nonce_orig = $this->create_nonce( $action );
            if( $nonce != $nonce_orig ){
                // try moving back one period
                $nonce_orig = $this->create_nonce( $action, '', 1 );
                if( $nonce != $nonce_orig ){
                    ob_end_clean();
                    echo 'Security tokens do not tally for executing this action. Please try again.';
                    if( $AUTH->user->access_level >= K_ACCESS_LEVEL_ADMIN ){
                        echo '<br /><a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'">Admin Panel</a>';
                    }
                    die;
                }
            }
        }

        function check_nonce( $action, $nonce='' ){
            global $AUTH;
            if( empty($nonce) ){ $nonce = $_REQUEST['nonce']; }
            $nonce_orig = $this->create_nonce( $action );
            if( $nonce != $nonce_orig ){
                // try moving back one period
                $nonce_orig = $this->create_nonce( $action, '', 1 );
                if( $nonce != $nonce_orig ){
                    return false;
                }
            }
            return true;
        }

        function _get_nonce_secret_key(){
            if( empty($this->nonce_secret_key) ){

                $secret_key = $this->get_setting( 'nonce_secret_key' );
                if( empty($secret_key) ){
                    $secret_key = $this->generate_key( 64 );
                    $this->set_setting( 'nonce_secret_key', $secret_key );
                }
                $this->nonce_secret_key = $secret_key;
            }
            return $this->nonce_secret_key;
        }

        function get_secret_key(){
            $secret_key = $this->get_setting( 'secret_key' );
            if( empty($secret_key) ){
                $secret_key = $this->generate_key( 64 );
                $this->set_setting( 'secret_key', $secret_key );
            }
            return $secret_key;

        }

        function encrypt( $text, $hash_key='' ){

            $key = $this->get_secret_key();
            if( $hash_key ){
                $key = $this->hash_hmac( $hash_key, $key );
            }

            // implementation of RC4 encryption algorithm.
            // http://pt.wikipedia.org/wiki/RC4
            // 1. key-scheduling algorithm (KSA)
            $S = array();
            for( $i = 0; $i < 256; $i++ ){
                $S[$i] = $i;
            }
            $keylen = strlen( $key );
            $j = 0;
            for( $i = 0; $i < 256; $i++ ){
                $j = ( $j + $S[$i] + $key{$i % $keylen} ) % 256;
                // swap
                $tmp = $S[$i];
                $S[$i] = $S[$j];
                $S[$j] = $tmp;
            }

            // 2. pseudo-random generation algorithm (PRGA)
            $textlen = strlen( $text );
            $i = $j = 0;
            for( $k = 0; $k < $textlen; $k++ ){
                $i = ($i + 1) % 256;
                $j = ($j + $S[$i]) % 256;
                // swap
                $tmp = $S[$i];
                $S[$i] = $S[$j];
                $S[$j] = $tmp;

                $tmp = $S[(($S[$a] + $S[$j]) % 256)];
                $tmp = ord(substr($text, $k, 1)) ^ $tmp;
                $result .= chr($tmp);
            }

            return $result;

        }

        function decrypt( $text, $hash_key='' ){
            return $this->encrypt( $text, $hash_key );
        }

        function get_setting( $key, $default=null ){
            global $DB;

            $rs = $DB->select( K_TBL_SETTINGS, array('*'), "k_key='" . $DB->sanitize( $key ). "'" );
            if( count($rs) ){
                return $rs[0]['k_value'];
            }
            return $default;
        }

        function set_setting( $key, $value ){
            global $DB;

            $rs = $DB->select( K_TBL_SETTINGS, array('k_key'), "k_key='" . $DB->sanitize( $key ). "'" );
            if( !count($rs) ){
                $rs = $DB->insert( K_TBL_SETTINGS, array('k_key'=>$key, 'k_value'=>$value) );
                if( $rs!=1 ) return KFuncs::raise_error( "Failed to insert record for setting in K_TBL_SETTINGS" );
                return;
            }
            else{
                $rs = $DB->update( K_TBL_SETTINGS, array('k_value'=>$value), "k_key='" . $DB->sanitize( $key ). "'" );
                if( $rs==-1 ) return KFuncs::raise_error( "Unable to update setting in K_TBL_SETTINGS" );
            }
        }

        function delete_setting( $key ){
            global $DB;

            $rs = $DB->delete( K_TBL_SETTINGS, "k_key='" . $DB->sanitize( $key ). "'" );
            if( $rs==-1 ) return KFuncs::raise_error( "Unable to remove setting from K_TBL_SETTINGS" );
            return;
        }

        // helper function of send_mail
        function _rsc( $s ){
            $injections = array('/(\n+)/i',
            '/(\r+)/i',
            '/(\t+)/i',
            '/(%0A+)/i',
            '/(%0D+)/i',
            '/(%08+)/i',
            '/(%09+)/i'
            );
            $s = preg_replace( $injections, '', $s );

            return $s;
        }

        function send_mail( $from, $to, $subject, $text, $headers="" ){
            // Source: http://www.anyexample.com/
            if( strtolower(substr(PHP_OS, 0, 3)) === 'win' ){
                $mail_sep = "\r\n";
            }
            else{
                $mail_sep = "\n";
            }

            $h = '';
            if( is_array($headers) ){
                foreach( $headers as $k=>$v ){
                    $h .= $this->_rsc($k).': '.$this->_rsc($v).$mail_sep;
                }
                if( $h != '' ) {
                    $h = substr($h, 0, strlen($h) - strlen($mail_sep));
                    $h = $mail_sep.$h;
                }
            }

            $from = $this->_rsc( $from );
            $to = $this->_rsc( $to );
            $subject = $this->_rsc( $subject );

            if ( defined('K_USE_ALTERNATIVE_MTA') && K_USE_ALTERNATIVE_MTA ){
                return @email( $to, $subject, $text, 'From: '.$from.$h );
            }
            else{
                return @mail( $to, $subject, $text, 'From: '.$from.$h );
            }
        }

        function is_alpha( $str ){
            if( is_null($str) ) return 0;
            return (preg_match("/[^a-zA-Z]/", $str)) ? 0 : 1;
        }

        function is_alphanumeric( $str ){
            if( is_null($str) ) return 0;
            return (preg_match("/[^a-zA-Z0-9]/", $str)) ? 0 : 1;
        }

        // -2, -1, 0, 1, 2 etc.
        function is_int( $str ){
            $str = trim( $str );
            if( !strlen($str) ) return 0;
            return (preg_match("/^[\+\-]?[0-9]+$/", $str)) ? 1 : 0;
        }

        // 0, 1, 2 etc.
        function is_natural( $str ){
            $str = trim( $str );
            if( !strlen($str) ) return 0;
            return (preg_match("/[^0-9]/", $str)) ? 0 : 1;
        }

        // 1, 2, 3 etc.
        function is_non_zero_natural( $str ){
            if( !$this->is_natural($str) ) return 0;
            return ( $str == 0 ) ? 0 : 1;
        }

        // static helper validation routines for fields
        function validate_alpha( $field ){
            $val = trim( $field->get_data() );
            if( !KFuncs::is_alpha($val) ){
                return KFuncs::raise_error( "Contains invalid characters (only alpha allowed)" );
            }
        }

        function validate_alpha_num( $field ){
            $val = trim( $field->get_data() );
            if( !KFuncs::is_alphanumeric($val) ){
                return KFuncs::raise_error( "Invalid characters (only alphanumeric allowed)" );
            }
        }

        function validate_int( $field ){
            $val = trim( $field->get_data() );
            if( !KFuncs::is_int($val) ){
                return KFuncs::raise_error( "Invalid characters (only integers allowed)" );
            }
        }

        function validate_natural( $field ){
            $val = trim( $field->get_data() );
            if( !KFuncs::is_natural($val) ){
                return KFuncs::raise_error( "Invalid characters (only natural numbers [0-9] allowed)" );
            }
        }

        function validate_non_zero_natural( $field ){
            $val = trim( $field->get_data() );
            if( !KFuncs::is_natural($val) ){
                return KFuncs::raise_error( "Invalid characters (only integers allowed)" );
            }
            if( $val==0 ){
                return KFuncs::raise_error( "Invalid characters (value cannot be zero)" );
            }
        }

        function validate_numeric( $field ){
            $val = trim( $field->get_data() );
            if( !is_numeric($val) ){
                return KFuncs::raise_error( "Invalid characters (only numeric values allowed)" );
            }
        }

        function validate_non_negative_numeric( $field ){
            $val = trim( $field->get_data() );
            if( !is_numeric($val) ){
                return KFuncs::raise_error( "Invalid characters (only numeric values allowed)" );
            }
            if( $val < 0 ){
                return KFuncs::raise_error( "Value cannot be negative" );
            }
        }

        function validate_non_zero_numeric( $field ){
            $val = trim( $field->get_data() );
            if( !is_numeric($val) ){
                return KFuncs::raise_error( "Invalid characters (only numeric values allowed)" );
            }
            if( !($val > 0) ){
                return KFuncs::raise_error( "Value cannot be negative or zero" );
            }
        }

        function validate_title( $field ){
            if( !KFuncs::is_title_clean($field->get_data()) ){
                return KFuncs::raise_error( "Contains invalid characters" );
            }
        }

        function validate_min_len( $field, $args ){
            $min = trim( $args );
            $val = trim( $field->get_data() );
            //if( !$field->required && !strlen($val) ) return;
            if( KFuncs::is_natural($min) ){
                $func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
                if( $func($val) < $min ){
                    return KFuncs::raise_error( "Cannot be less than ".$min." characters" );
                }
            }
        }

        function validate_max_len( $field, $args ){
            $min = trim( $args );
            $val = trim( $field->get_data() );
            //if( !$field->required && !strlen($val) ) return;
            if( KFuncs::is_natural($min) ){
                $func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
                if( $func($val) > $min ){
                    return KFuncs::raise_error( "Cannot be more than ".$min." characters" );
                }
            }
        }

        function validate_exact_len( $field, $args ){
            $min = trim( $args );
            $val = trim( $field->get_data() );
            //if( !$field->required && !strlen($val) ) return;
            if( KFuncs::is_natural($min) ){
                $func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
                if( $func($val) != $min ){
                    return KFuncs::raise_error( "Not equal to ".$min." characters" );
                }
            }
        }

        function validate_matches( $field, $args ){
            $val1 = trim( $field->get_data() );
            $args = trim( $args );

            // find the field to match with
            $found = 0;
            foreach( $field->siblings as $f ){
                if( $f->name == $args ){
                    $found = 1;
                    break;
                }
            }
            if( $found ){
                $val2 = trim( $f->get_data() );
                $label = $f->label;
            }

            // Reset internal pointer because we are working on reference.
            // Else results in an infinite loop in the calling routine in php4.
            foreach( $field->siblings as $f ){
                if( $f->name == $field->name ) break;
            }

            if( $found ){
                if( $val1 !== $val2 ){
                    return KFuncs::raise_error( "Does not match " . $label );
                }
            }
            else{
                return KFuncs::raise_error( "Field ".$args." to match not found" );
            }
        }

        function validate_email( $field ){
            if( !preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", trim($field->get_data())) ){
                return KFuncs::raise_error( "Invalid E-mail" );
            }
        }

        function validate_url( $field ){
            // Pattern from http://mathiasbynens.be/demo/url-regex
            $pattern = "/^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/iuS";
            if( !preg_match($pattern, trim($field->get_data())) ){
                return KFuncs::raise_error( "Invalid URL" );
            }
        }

        function validate_regex( $field, $args ){
            if( !preg_match(trim($args), trim($field->get_data())) ){
                return KFuncs::raise_error( "Does not match pattern" );
            }
        }

        // Used only internally
        function validate_unique_page( $field ){
            global $DB;

            $page_id = ( $field->page_id ) ? $field->page_id : $field->page->id;

            // Also make sure that another page by this name does not exist
            $rs = $DB->select( K_TBL_PAGES, array('id'), "NOT id='". $DB->sanitize( $page_id )."' AND page_name='" . $DB->sanitize( $field->get_data() ). "' AND template_id='" . $DB->sanitize( $field->template_id ). "'" );
            if( count($rs) ){
                return KFuncs::raise_error( "Page already exists by this name" );
            }
        }

        function cleanXSS( $val, $nl2br=0, $allowed_html_tags='' ){

            if( is_array($val) ) $val = $val[0]; //?? do what? take only the first value

            $parser = new KHTMLParser( $val, '', 1, $nl2br, $allowed_html_tags );
            $val = $parser->get_HTML();

            return $val;
        }

        function stripslashes_deep( $value ){
            $value = is_array( $value ) ?
                        array_map( array($this, 'stripslashes_deep'), $value ) :
                        stripslashes( $value );

            return $value;
        }

        function sanitize_deep( $arr ){
            $tmp = array();

            foreach( $arr as $k=>$v ){
                $k = $this->cleanXSS( $k );
                if( is_array($v) ){
                    $tmp[$k] = $this->sanitize_deep( $v );
                }
                else{
                    $tmp[$k] = $this->cleanXSS( $v );
                }
            }

            return $tmp;
        }

        function sanitize_url( $url, $default='', $only_local=0 ){
            $url = trim( $url );
            $default = trim( $default );

            if( strlen($url) ){
                // Only chars permitted to remain unencoded in urls remain
                $url = preg_replace( array('/</', '/>/', '/"/', '/\x00+/'), array('', '', '', ''), $url );
                $url = preg_replace( '|[^a-z0-9:#@%/;,\'$()~_?\+-=\\\.&!]|i', '', $url );

                // remove newlines
                $newlines = array('%0d', '%0D', '%0a', '%0A');
                $found = true;
                while( $found == true ){
                    $val_before = $url;
                    for( $i = 0; $i < count($newlines); $i++ ){
                        $url = str_replace( $newlines[$i], '', $url );
                    }
                    if( $val_before == $url ){ $found = false; }
                }

                if( strlen($url) ){
                    if( $only_local ){ // don't allow redirects external to our site
                        if( !strlen($default) ) $default=K_SITE_URL;

                        if( strpos($url, '//')!==false ){
                            if( strpos($url, K_SITE_URL)!==0 ){
                                $url = $default;
                            }
                        }
                        elseif( strpos($url, '/\\')===0 ){
                            $url = $default;
                        }
                    }
                }
                else{
                    $url = $default;
                }
            }
            else{
                $url = $default;
            }

            return $url;
        }

        function invalidate_cache(){
            // Invalidate cache
            $file = K_COUCH_DIR . 'cache/' . 'cache_invalidate.dat';
            if( file_exists($file) ) @unlink( $file );
            @fclose( @fopen($file, 'a') );
        }

        // Changes data types of custom-fields form text to numeric and vice-versa
        function change_field_type( $old_type, $new_type, $field_id ){
            global $DB;

            if( $old_type == $new_type ) return;
            if( $old_type!='text' && $new_type!='text' ) return; //at least one type should be text

            $from_table = ( $old_type=='text' ) ? K_TBL_DATA_TEXT : K_TBL_DATA_NUMERIC;
            $to_table = ( $new_type=='text' ) ? K_TBL_DATA_TEXT : K_TBL_DATA_NUMERIC;

            $arr_from_fields = array( 'page_id', 'field_id', 'value' );
            if( $old_type == 'integer' ){
                $arr_from_fields[] = 'TRUNCATE(value,0) as value';
            }
            else{
                $arr_from_fields[] = 'value';
            }

            // Get records from old table and insert into new after conversion from old_type to new_type
            $rs = $DB->select( $from_table, $arr_from_fields, "field_id='" . $DB->sanitize( $field_id ). "'" );
            if( count($rs) ){
                foreach( $rs as $rec ){
                    $arr_to_fields = array('page_id'=>$rec['page_id'],
                                        'field_id'=>$rec['field_id']
                                        );
                    if( $new_type=='integer' ){
                        $arr_to_fields['value'] = $this->strip_decimal( $rec['value'] );
                    }
                    else{
                        $arr_to_fields['value'] = $rec['value'];
                    }
                    if( $new_type=='text' ){
                        $arr_to_fields['search_value'] = $rec['value'];
                    }

                    $rs2 = $DB->insert( $to_table, $arr_to_fields );
                    if( $rs2==-1 ) die( "ERROR: Unable to convert field type" );
                }
            }
            // remove records from old table
            $rs = $DB->delete( $from_table, "field_id='" . $DB->sanitize( $field_id ). "'" );
            if( $rs==-1 ) die( "ERROR: Unable to convert field type" );
        }

        function strip_decimal( $str_num ){
            $pos = strpos( $str_num, ".");
            if( $pos!==false ){
                $str_num = substr( $str_num, 0, $pos );
            }
            return $str_num;
        }

        // Interpolates width and height attributes within provided style string
        function set_style( $style, $width, $height ){
            if( $style ){
                // parse to see if width or height specified
                $arr_style = array_map( "trim", explode( ';', $style ) );
                foreach( $arr_style as $attr ){
                    if( $attr ){
                        $arr_attr = array_map( "trim", explode( ':', $attr ) );
                        if( strtolower($arr_attr[0])=='width' ) unset( $width );
                        if( strtolower($arr_attr[0])=='height' ) unset( $height );
                        $arr_style2[] = $attr;
                    }
                }
                if( $width ) $arr_style2[]='width:'.intval($width).'px';
                if( $height ) $arr_style2[]='height:'.intval($height).'px';

                // join the attributes back
                $style = implode( ";", $arr_style2 );

            }
            else{
                if( $width ) $style .= 'width:'.$width.'px; ';
                if( $height ) $style .= 'height:'.$height.'px; ';
            }
            if( $style ) $style = 'style="'.$style.'"';
            return $style;
        }

        function login_header(){
            $link = K_ADMIN_URL . 'theme/styles.css?ver='.K_COUCH_BUILD;
            $login_title = 'CouchCMS';
            if( K_PAID_LICENSE ){
                if( defined('K_LOGO_LIGHT') ){
                    $logo_src = K_ADMIN_URL.'theme/images/'.K_LOGO_LIGHT;
                }
                else{
                    $logo_src = K_ADMIN_URL.'theme/images/couch.gif';
                }
                // box title
                $login_title = $this->t('login_title');
            }
            else{
                $logo_src = K_ADMIN_URL.'logo.php';
            }

            $html = <<<OUT
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
            <head>
                <title>$login_title</title>
                <script type="text/javascript">try { document.execCommand('BackgroundImageCache', false, true); } catch(e) {}</script>
                <link rel="stylesheet" href="$link" type="text/css" media="screen" />
            </head>
            <body class="login">
                <div id="login" >
                    <img src="$logo_src" />
OUT;
            return $html;
        }

        function login_footer(){
            $html = <<<OUT
            </div>
            </body>
            </html>
OUT;
            return $html;
        }

        function log( $msg, $file='' ){
            // File name if provided needs to be relative to the site directory
            if( !$file ){
                $file = 'log.txt';
            }
            $file = K_SITE_DIR . $file;

            $ts = '['.$this->get_current_desktop_time().']';
            $str = "\r\n=======================" . $ts . "=======================\r\n" . $msg . "\r\n";

            $fp = @fopen( $file,'a' );
            if( $fp ){
                @flock( $fp, LOCK_EX );
                @fwrite( $fp, $str );
                @flock( $fp, LOCK_UN );
                @fclose( $fp );
            }
        }

        // Called while processing paypal IPN to validate transaction
        function validate_transaction( $item_name, $item_number, $quantity, $payment_amount, $payment_currency, $receiver_email, &$pg ){
            global $DB;

            // $item_number is actually our page_id. Get the page.
            if( KFuncs::is_natural($item_number) ){
                $rs = $DB->select( K_TBL_PAGES, array('id', 'template_id'), "id = '".$DB->sanitize( $item_number )."' AND page_title = '".$DB->sanitize( trim($item_name) )."'" );
                if( count($rs) ){
                    $rec = $rs[0];
                    $pg = new KWebpage( $rec['template_id'], $rec['id'] );
                    if( !$pg->error ){
                        for( $x=0; $x<count($pg->fields); $x++ ){
                            if( $pg->fields[$x]->name == 'pp_price' ){
                                $pp_price = trim( $pg->fields[$x]->get_data() );
                            }
                        }

                        if( isset($pp_price) ){

                            // Validate payment made is not less than price * quantity (allow a little margin to take rounding into consideration)
                            if( $payment_amount < ($pp_price*$quantity*0.995) ){
                                return KFuncs::raise_error( 'Payment made('.$payment_amount.') less than price('.$pp_price.') x quantity('.$quantity.')' );
                            }

                            // Validate currency of payment matches currency of price
                            if( trim($payment_currency) != trim(K_PAYPAL_CURRENCY) ){
                                return KFuncs::raise_error( 'Payment currency('.$payment_currency.') does not match price currency('.K_PAYPAL_CURRENCY.')' );
                            }

                            // Finally validate that the payment has been made to the right account
                            if( trim($receiver_email) != trim(K_PAYPAL_EMAIL) ){
                                return KFuncs::raise_error( 'Receiver email('.$receiver_email.') does not match seller\'s email('.K_PAYPAL_EMAIL.')' );
                            }

                            // if we are here, everything is ok
                            return;

                        }
                        else{
                            return KFuncs::raise_error( 'Item number('.$item_number.') has no price field associated' );
                        }
                    }
                    else{
                        return KFuncs::raise_error( 'Error occured while creating Page object for item number('.$item_number.'). Error:'.$pg->err_msg.'' );
                    }
                }
                else{
                    return KFuncs::raise_error( 'No item number('.$item_number.') with the item_name('.$item_name.') found' );
                }
            }
            else{
                return KFuncs::raise_error( 'Invalid item number('.$item_number.')' );
            }

        }

        // Original Pagination code from:
        // http://www.strangerstudios.com/sandbox/pagination/diggstyle.php
        // strangerstudios.com
        function getPaginationString( $page = 1, $totalitems, $limit = 15, $adjacents = 1, $targetpage = "/", $pagestring = "?page=", $prev_text, $next_text, $simple ){
            //defaults
            if( !$adjacents ) $adjacents = 1;
            if( !$limit ) $limit = 15;
            if( !$page ) $page = 1;
            if( !$targetpage ) $targetpage = "/";

            //other vars
            $prev = $page - 1; //previous page is page - 1
            $next = $page + 1; //next page is page + 1
            $lastpage = ceil($totalitems / $limit); //lastpage is = total items / items per page, rounded up.
            $lpm1 = $lastpage - 1; //last page minus 1

            /*
                Now we apply our rules and draw the pagination object.
                We're actually saving the code to a variable in case we want to draw it more than once.
            */
            $pagination = "";
            if( $lastpage > 1 ){

                $pagination .= "<div class=\"pagination\"";
                $pagination .= ">";

                //previous button
                if( $page > 1 ){
                    $pagination .= "<a href=\"$targetpage$pagestring$prev\" class=\"prev\">".$prev_text."</a>";
                }
                else{
                    $pagination .= "<span class=\"page_disabled prev\">".$prev_text."</span>";
                }

                //pages
                if( !$simple ){
                    if( $lastpage < 7 + ($adjacents * 2) ){ //not enough pages to bother breaking it up
                        for( $counter = 1; $counter <= $lastpage; $counter++ ){
                            if( $counter == $page ){
                                $pagination .= "<span class=\"page_current\">$counter</span>";
                            }
                            else{
                                $pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
                            }
                        }
                    }
                    elseif( $lastpage >= 7 + ($adjacents * 2) ){ //enough pages to hide some
                        //close to beginning; only hide later pages
                        if($page < 1 + ($adjacents * 3)){
                            for( $counter = 1; $counter < 4 + ($adjacents * 2); $counter++ ){
                                if( $counter == $page ){
                                    $pagination .= "<span class=\"page_current\">$counter</span>";
                                }
                                else{
                                    $pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
                                }
                            }
                            $pagination .= "<span class=\"ellipsis\">&hellip;</span>";
                            $pagination .= "<a href=\"" . $targetpage . $pagestring . $lpm1 . "\">$lpm1</a>";
                            $pagination .= "<a href=\"" . $targetpage . $pagestring . $lastpage . "\">$lastpage</a>";
                        }
                        //in middle; hide some front and some back
                        elseif( $lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2) ){
                                $pagination .= "<a href=\"" . $targetpage . $pagestring . "1\">1</a>";
                                $pagination .= "<a href=\"" . $targetpage . $pagestring . "2\">2</a>";
                                $pagination .= "<span class=\"ellipsis\">&hellip;</span>";
                                for( $counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++ ){
                                    if( $counter == $page ){
                                        $pagination .= "<span class=\"page_current\">$counter</span>";
                                    }
                                    else{
                                        $pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
                                    }
                                }
                                $pagination .= "<span class=\"ellipsis\">&hellip;</span>";
                                $pagination .= "<a href=\"" . $targetpage . $pagestring . $lpm1 . "\">$lpm1</a>";
                                $pagination .= "<a href=\"" . $targetpage . $pagestring . $lastpage . "\">$lastpage</a>";
                        }
                        //close to end; only hide early pages
                        else{
                                $pagination .= "<a href=\"" . $targetpage . $pagestring . "1\">1</a>";
                                $pagination .= "<a href=\"" . $targetpage . $pagestring . "2\">2</a>";
                                $pagination .= "<span class=\"ellipsis\">&hellip;</span>";
                                for( $counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++ ){
                                    if( $counter == $page ){
                                        $pagination .= "<span class=\"page_current\">$counter</span>";
                                    }
                                    else{
                                        $pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
                                    }
                                }
                        }
                    }
                }
                else{
                    $counter = $lastpage + 1;
                }

                //next button
                if( $page < $counter - 1 ){
                    $pagination .= "<a href=\"" . $targetpage . $pagestring . $next . "\" class=\"next\">".$next_text."</a>";
                }
                else{
                    $pagination .= "<span class=\"page_disabled next\">".$next_text."</span>";
                }
                $pagination .= "</div>\n";
            }
            return $pagination;
        }

        function getPaginationArray( $page = 1, $totalitems, $limit = 15, $adjacents = 1, $targetpage = "/", $pagestring = "?page=", $prev_text, $next_text, $simple ){
            //defaults
            if( !$adjacents ) $adjacents = 1;
            if( !$limit ) $limit = 15;
            if( !$page ) $page = 1;
            if( !$targetpage ) $targetpage = "/";

            //other vars
            $prev = $page - 1; //previous page is page - 1
            $next = $page + 1; //next page is page + 1
            $lastpage = ceil($totalitems / $limit); //lastpage is = total items / items per page, rounded up.
            $lpm1 = $lastpage - 1; //last page minus 1

            $pagination = array();
            if( $lastpage > 1 ){

                //previous button
                if( $page > 1 ){
                    $pagination[] = array( 'crumb_type'=>'prev', 'link'=>$targetpage . $pagestring . $prev, 'text'=>$prev_text, 'disabled'=>'0', 'current'=>'0' );
                }
                else{
                    $pagination[] = array( 'crumb_type'=>'prev', 'link'=>'', 'text'=>$prev_text, 'disabled'=>'1', 'current'=>'0' );
                }

                //pages
                if( !$simple ){
                    if( $lastpage < 7 + ($adjacents * 2) ){ //not enough pages to bother breaking it up
                        for( $counter = 1; $counter <= $lastpage; $counter++ ){
                            if( $counter == $page ){
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'1' );
                            }
                            else{
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'0' );
                            }
                        }
                    }
                    elseif( $lastpage >= 7 + ($adjacents * 2) ){ //enough pages to hide some
                        //close to beginning; only hide later pages
                        if($page < 1 + ($adjacents * 3)){
                            for( $counter = 1; $counter < 4 + ($adjacents * 2); $counter++ ){
                                if( $counter == $page ){
                                    $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'1' );
                                }
                                else{
                                    $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'0' );
                                }
                            }
                            $pagination[] = array( 'crumb_type'=>'ellipsis', 'link'=>'', 'text'=>'&hellip;', 'disabled'=>'0', 'current'=>'0' );
                            $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $lpm1, 'text'=>$lpm1, 'disabled'=>'0', 'current'=>'0' );
                            $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $lastpage, 'text'=>$lastpage, 'disabled'=>'0', 'current'=>'0' );
                        }
                        //in middle; hide some front and some back
                        elseif( $lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2) ){
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . '1', 'text'=>'1', 'disabled'=>'0', 'current'=>'0' );
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . '2', 'text'=>'2', 'disabled'=>'0', 'current'=>'0' );
                                $pagination[] = array( 'crumb_type'=>'ellipsis', 'link'=>'', 'text'=>'&hellip;', 'disabled'=>'0', 'current'=>'0' );
                                for( $counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++ ){
                                    if( $counter == $page ){
                                        $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'1' );
                                    }
                                    else{
                                        $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'0' );
                                    }
                                }
                                $pagination[] = array( 'crumb_type'=>'ellipsis', 'link'=>'', 'text'=>'&hellip;', 'disabled'=>'0', 'current'=>'0' );
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $lpm1, 'text'=>$lpm1, 'disabled'=>'0', 'current'=>'0' );
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $lastpage, 'text'=>$lastpage, 'disabled'=>'0', 'current'=>'0' );
                        }
                        //close to end; only hide early pages
                        else{
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . '1', 'text'=>'1', 'disabled'=>'0', 'current'=>'0' );
                                $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . '2', 'text'=>'2', 'disabled'=>'0', 'current'=>'0' );
                                $pagination[] = array( 'crumb_type'=>'ellipsis', 'link'=>'', 'text'=>'&hellip;', 'disabled'=>'0', 'current'=>'0' );
                                for( $counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++ ){
                                    if( $counter == $page ){
                                        $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'1' );
                                    }
                                    else{
                                        $pagination[] = array( 'crumb_type'=>'page', 'link'=>$targetpage . $pagestring . $counter, 'text'=>$counter, 'disabled'=>'0', 'current'=>'0' );
                                    }
                                }
                        }
                    }
                }
                else{
                    $counter = $lastpage + 1;
                }

                //next button
                if( $page < $counter - 1 ){
                    $pagination[] = array( 'crumb_type'=>'next', 'link'=>$targetpage . $pagestring . $next, 'text'=>$next_text, 'disabled'=>'0', 'current'=>'0' );
                }
                else{
                    $pagination[] = array( 'crumb_type'=>'next', 'link'=>'', 'text'=>$next_text, 'disabled'=>'1', 'current'=>'0' );
                }
            }
            return $pagination;
        }

        function hilite_search_terms( $keywords, $content, $in_title=0 ){
            $arr_ranges = array();
            $arr_selected_ranges = array();
            $arr_keywords = array();

            if( $in_title ){
                //foreach( explode( ' ', $keywords ) as $kw ){
                foreach( $keywords as $kw ){
                    while( @preg_match("/[^\>](".$kw.")[^\<]/i", " ".$content." ", $matches) ){
                        $content = @preg_replace( "/".$matches[1]."/i", "<b>".$matches[1]."</b>", $content );
                    }
                }
                return $content;
            }
            else{
                //foreach( explode( ' ', $keywords ) as $kw ){
                foreach( $keywords as $kw ){
                    $arr_keywords[] = new KKeyword( $kw, $content, $arr_ranges );
                }

                foreach( $arr_keywords as $kw ){
                    $kw->get_selected_range( $arr_selected_ranges );
                }

                $out = array();
                if( count($arr_selected_ranges) ){
                    // hilight and sort ranges according to start positions
                    foreach( $arr_selected_ranges as $r ){
                        foreach( $r->keywords as $kw ){
                            while( @preg_match("/[^\>](".$kw.")[^\<]/i", " ".$r->text." ", $matches) ){
                                $r->text = @preg_replace( "/".$matches[1]."/i", "<b>".$matches[1]."</b>", $r->text );
                            }
                        }
                        $out[$r->start] = $r->text;
                    }
                    ksort( $out, SORT_NUMERIC );
                }
                else{
                    // if no ranges selected, output the first few chars
                    $out[] = substr( $content, 0, K_RANGE_LEN*2 );
                }

                // output ranges
                $sep = '<b>&hellip;</b>';
                foreach( $out as $k=>$v ){
                    if( $k==0 ) $sep = '';
                    $str .= $sep. $v;
                    $sep = '<b>&hellip;</b>';
                }
                $str .= $sep;

                return $str;
            }
        }

        function insert_comment( $params, $node ){
            global $DB, $PAGE, $AUTH;
            $tpl_id = '';
            $page_id = '';
            $name = '';
            $email = '';
            $link = '';
            $ip_addr = '';
            $user_id = '';
            $data = '';
            $date = '';
            $approved = '';

            // insert comment only if containing page is a true page (i:e not is_master, is_folder or is_archive)
            // or is a non-clonable page (where the above mentioned views do not apply)..
            if( !$PAGE->tpl_is_clonable || ($PAGE->tpl_is_clonable && !$PAGE->is_master) ){
                //.. and commenting has been allowed
                if( $PAGE->tpl_is_commentable && $PAGE->comments_open ){
                    $tpl_id = $PAGE->tpl_id;
                    $page_id = $PAGE->id;

                    // if user logged in, get data from his profile..
                    if( $AUTH->user->id != -1 ){
                        $name = $AUTH->user->title;
                        $email = $AUTH->user->email;
                        $link = $AUTH->user->website; // not yet implemented
                        $user_id = $AUTH->user->id;
                    }
                    else{
                        $name = trim( $this->cleanXSS(strip_tags($_POST['k_author'])) );
                        $email = trim( $this->cleanXSS(strip_tags($_POST['k_email'])) );

                        $link = trim( strip_tags($_POST['k_link']) );
                        $link = preg_match( '@^(?:https?:\/\/|www\.)[^/]+@i', $link ) ? $link : '';
                        $link = substr( $link, 0, 3 ) == 'www' ? 'http://'.$link : $link;
                        if( $link ){
                            $link = $this->cleanXSS( '<a href="'.$link.'">dummy</a>' );
                            $link = preg_match( '@\<a href="([^"]*)"@', $link, $matches ) ? $matches[1] : '';
                        }
                    }

                    // IP address
                    $ip_addr = trim( $this->cleanXSS(strip_tags($_SERVER['REMOTE_ADDR'])) );

                    // comment
                    if( isset($_POST['k_comment']) ){
                        $allowed_tags = '<a><br><strong><b><em><i><u><blockquote><pre><code><ul><ol><li><del>';
                        $data = strip_tags( $_POST['k_comment'], $allowed_tags );
                        $data = trim( $this->cleanXSS($data, 1) );
                    }
                    if( !strlen($data) ){
                        return $this->raise_error( "Cannot insert empty comment" );
                    }

                    // moderated or not?
                    if( $AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ){
                        $approved = !( K_COMMENTS_REQUIRE_APPROVAL );
                    }
                    else{
                        $approved = 1;
                    }

                    $DB->begin();
                    // Serialize access
                    $DB->select( K_TBL_PAGES, array('comments_count'), "id='" . $DB->sanitize( $page_id ). "' FOR UPDATE" );

                    // make sure it is not double posting..
                    $sql = "page_id='" .$DB->sanitize( $page_id ). "' AND data='" .$DB->sanitize( $data ). "' AND email='" .$DB->sanitize( $email ). "' LIMIT 1";
                    $rs = $DB->select( K_TBL_COMMENTS, array('id'), $sql );
                    if( count($rs) ){
                        $DB->rollback();
                        return $this->raise_error( $this->t('duplicate_content') );
                    }

                    $date = $this->get_current_desktop_time();

                    // make sure that there is sufficient time between two comments
                    if( $AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ){
                        $ts = date( 'Y-m-d H:i:s', strtotime($date) - K_COMMENTS_INTERVAL );
                        $sql = "(ip_addr='" .$DB->sanitize( $ip_addr ). "' OR email='" .$DB->sanitize( $email ). "') AND ";
                        $sql .= "date>='".$DB->sanitize( $ts )."' LIMIT 1";
                        $rs = $DB->select( K_TBL_COMMENTS, array('id'), $sql );
                        if( count($rs) ){
                            $DB->rollback();
                            return $this->raise_error( $this->t('insufficient_interval') );
                        }
                    }

                    $arr_insert = array(
                        'tpl_id'=>$tpl_id,
                        'page_id'=>$page_id,
                        'user_id'=>$user_id,
                        'name'=>$name,
                        'email'=>$email,
                        'link'=>$link,
                        'ip_addr'=>$ip_addr,
                        'date'=>$date,
                        'data'=>$data,
                        'approved'=>$approved
                    );

                    // HOOK: alter_comment_insert
                    $this->dispatch_event( 'alter_comment_insert', array(&$arr_insert, &$approved, $params, $node) );

                    // if everything ok, go for it
                    $rs = $DB->insert( K_TBL_COMMENTS, $arr_insert );
                    if( $rs!=1 ){ $DB->rollback();  return $this->raise_error( "Failed to insert record in K_TBL_COMMENTS" );}
                    $comment_id = $DB->last_insert_id;

                    // HOOK: comment_inserted
                    $err_msg = $this->dispatch_event( 'comment_inserted', array($comment_id, $arr_insert, &$approved, $params, $node) );
                    if( $err_msg ){
                        $DB->rollback();
                        return $this->raise_error( $err_msg );
                    }

                    if( $approved ){
                        // increase comments count for the page
                        $rs = $this->update_comments_count( $page_id );
                        if( $this->is_error($rs) ){ $DB->rollback();  return $rs; }

                        // invalidate cache
                        $this->invalidate_cache();

                        // redirect to the inserted comment
                        $DB->commit( 1 ); // force commit, we are redirecting.
                        $parent_link = ( K_PRETTY_URLS ) ? $this->get_pretty_template_link( $PAGE->tpl_name ) : $PAGE->tpl_name;
                        $comment_link = K_SITE_URL . $parent_link . "?comment=" . $comment_id;
                        header( "Location: " . $comment_link );
                        exit;
                    }

                    $DB->commit();
                    return $comment_id;
                }
                else{
                    return $this->raise_error( "Comments not allowed" );
                }
            }
            else{
                return $this->raise_error( "Page not commentable" );
            }
        }

        function update_comments_count( $page_id ){
            global $DB;

            // get current count of approved comments
            $sql = "page_id='".$DB->sanitize($page_id)."' AND approved='1'";
            $rs = $DB->select( K_TBL_COMMENTS, array('count(id) as cnt'), $sql );
            $count = intval( $rs[0]['cnt'] );

            // save it with the page data
            $rs = $DB->update( K_TBL_PAGES, array('comments_count'=>$count), "id='" . $DB->sanitize( $page_id ). "'" );
            if( $rs==-1 ) return $this->raise_error( "Unable to increase comments count in K_TBL_PAGES" );
        }

        // Utility func used by 'pages' tag.
        // Given a comma separated string like "blog.php, testimonial.php"
        // or "NOT blog.php, testimonial.php"
        // returns the sql statement required to query.
        function gen_sql( $elem, $field_name, $validate_natural=0 ){
            global $DB;
            $sql = '';

            // Negation?
            $neg = 0;
            $pos = strpos( strtoupper($elem), 'NOT ' );
            if( $pos!==false && $pos==0 ){
                $neg = 1;
                $elem = trim( substr($elem, strpos($elem, ' ')) ); // remove NOT
            }

            if( $validate_natural ){
                $sql = ' AND ' . $field_name;
                $arr_elems = array_filter( explode(',', $elem), array($this, '_validate_natural') );
                $count = count( $arr_elems );
                if( !$count ) return;

                if( $count>1 ){
                    $sql .= ( $neg ) ? ' NOT IN' : ' IN';
                    $sql .= '(' . implode( ",", $arr_elems ) . ')';
                }
                else{
                    $sql .= ( $neg ) ? '!=' : '=';
                    $arr_elems = array_values( $arr_elems );
                    $sql .= $arr_elems[0];
                }
            }
            else{
                $arr_elems = array_map( "trim", explode( ',', $elem ) );
                $sep = " AND ";
                if( $neg ) $sep .= "NOT";
                $sep .= "(";
                foreach( $arr_elems as $elem ){
                    if( $elem ){
                        $sql .= $sep . $field_name."='" . $DB->sanitize( $elem )."'";
                        $sep = " OR ";
                    }
                }
                if( $sep == " OR " ) $sql .= ")";
            }

            return $sql;
        }

        function _validate_natural( $str ){
            return (bool)$this->is_natural( $str );
        }

        function get_gravatar( $email='', $size=48, $default='', $link_only=0 ){
            $size = $this->is_natural( $size ) ? intval( $size ) : 48;
            $url = K_HTTPS ? 'https://secure.' : 'http://www.';

            if( !$default ){
                $default = $url . "gravatar.com/avatar/" . md5( 'unknown@gravatar.com' ) . "?size=" . $size;
            }

            if( $email ){
                $grav_url = $url . "gravatar.com/avatar/" . md5( strtolower($email) ) .
                "?default=" . urlencode( $default ) .
                "&size=" . $size;
            }
            else{
                $grav_url = $default;
            }
            $html = '<img class="gravatar' . ($email ? '' : ' gravatar-default') . '" height="' . $size . '" src="' . $grav_url . '" width="' . $size . '"/>';

            return $link_only ? $grav_url : $html;
        }

        function t( $key ){

            return $this->_t[$key];
        }

        function register_shortcode( $tagname, $handler ){
            $tagname = strtolower(trim($tagname));
            if( strlen($tagname) ){
                if( is_callable($handler) ){
                    $this->shortcodes[$tagname] = $handler;
                }
                else{
                    ob_end_clean();
                    die("ERROR function register_shortcode(): handler function of Shortcode \"".$tagname."\" not callable");
                }
            }
        }

        // for now only for internal use (listing non-nested pages in admin-panel)
        function register_admin_listview( $masterpage, $filename ){
            $masterpage = trim( $masterpage );
            if( strlen($masterpage) ){
                $this->admin_list_views[$masterpage] = trim( $filename );
            }
        }

        // for now only for internal use (showing single page for editing in admin-panel)
        function register_admin_pageview( $masterpage, $filename, $show_advanced_settings=1 ){
            $masterpage = trim( $masterpage );
            if( strlen($masterpage) ){
                $this->admin_page_views[$masterpage] = array( trim($filename), $show_advanced_settings );
            }
        }

        function register_tag( $tagname, $handler, $supports_scope=0, $supports_zebra=0 ){
            global $TAGS, $CTX;

            $tagname = strtolower(trim($tagname));
            if( strlen($tagname) ){
                if( array_key_exists( $tagname, $this->tags) ){
                    ob_end_clean();
                    die("ERROR function register_tag(): Tag \"".$tagname."\" already registered");
                }
                if( ($tagname=='if' || $tagname=='else' || $tagname=='while') || method_exists($TAGS, $tagname) ){
                    ob_end_clean();
                    die("ERROR function register_tag(): Tag \"".$tagname."\" is a native tag");
                }

                $supports_scope = ( $supports_scope==1 ) ? 1 : 0;
                $supports_zebra = ( $supports_zebra==1 ) ? 1 : 0;
                if( is_callable($handler) ){
                    $this->tags[$tagname] = array( 'handler'=>$handler );
                    if( $supports_scope ){
                        $CTX->support_scope[]=$tagname;
                        if( $supports_zebra ) $CTX->support_zebra[]=$tagname;
                    }
                }
                else{
                    ob_end_clean();
                    die("ERROR function register_tag(): handler function of Tag \"".$tagname."\" not callable");
                }
            }
        }

        function register_udf( $fieldtype, $handler_class, $repeatable=0, $searchable=1 ){ //'searchable' or not applies only to text types (numerics are always non_searchable)
            if( !is_string( $handler_class ) || !($handler_class=trim($handler_class)) ){
                ob_end_clean();
                die("ERROR function register_field(): Please provide the name of a valid class");
            }
            $fieldtype = strtolower(trim($fieldtype));
            if( !$fieldtype ){
                ob_end_clean();
                die("ERROR function register_field(): Please provide a field type");
            }

            if( !$this->is_subclass($handler_class, 'KUserDefinedField') ){
                ob_end_clean();
                die("ERROR function register_field(): ".$fieldtype." - handler not a subclass of KUserDefinedField");
            }

            if( $this->is_core_type($fieldtype) ){
                ob_end_clean();
                die("ERROR function register_field(): Field \"".$fieldtype."\" is a system field");
            }

            if( array_key_exists( $fieldtype, $this->udfs) ){
                ob_end_clean();
                die("ERROR function register_field(): Field \"".$fieldtype."\" already registered");
            }

            $searchable = ( $searchable==0 ) ? 0 : 1;
            $repeatable = ( $repeatable==1 ) ? 1 : 0;
            $this->udfs[$fieldtype] = array( 'handler'=>$handler_class, 'searchable'=>$searchable, 'repeatable'=>$repeatable );
        }

        function register_udform_field( $fieldtype, $handler_class ){
            if( !is_string( $handler_class ) || !($handler_class=trim($handler_class)) ){
                ob_end_clean();
                die("ERROR function register_udform_field(): Please provide the name of a valid class");
            }
            $fieldtype = strtolower(trim($fieldtype));
            if( !$fieldtype ){
                ob_end_clean();
                die("ERROR function register_udform_field(): Please provide a field type");
            }

            if( !$this->is_subclass($handler_class, 'KUserDefinedFormField') ){
                ob_end_clean();
                die("ERROR function register_udform_field(): ".$fieldtype." - handler not a subclass of KUserDefinedFormField");
            }

            if( $this->is_core_formfield_type($fieldtype) ){
                ob_end_clean();
                die("ERROR function register_udform_field(): Field \"".$fieldtype."\" is a core field");
            }

            if( array_key_exists( $fieldtype, $this->udform_fields) ){
                ob_end_clean();
                die("ERROR function register_udform_field(): Field \"".$fieldtype."\" already registered");
            }

            $this->udform_fields[$fieldtype] = array( 'handler'=>$handler_class );
        }

        // wrapper functions for event dispatcher
        function dispatch_event( $event_name, $args=array() ){
            return $this->_ed->dispatch( $event_name, $args );
        }

        function add_event_listener( $event_name, $listener, $priority = 0 ){
            $this->_ed->add_listener( $event_name, $listener, $priority );
        }

        function remove_event_listener( $event_name, $listener ){
            $this->_ed->remove_listener( $event_name, $listener );
        }

        function has_event_listeners( $event_name = null ){
            return $this->_ed->has_listeners( $event_name );
        }

        function get_event_listeners( $event_name = null ){
            return $this->_ed->get_listeners( $event_name );
        }

        // Store the passed script to output it in admin. (called from udfs)
        function load_js( $src='' ){
            $src = trim( $src );
            if( $src ){
                $sep = ( strpos($src, '?')===false ) ? '?' : '&';
                $this->scripts[MD5($src)] = $src . $sep . 'kver=' . K_COUCH_BUILD;
            }
        }

        function load_css( $src='' ){
            $src = trim( $src );
            if( $src ){
                $sep = ( strpos($src, '?')===false ) ? '?' : '&';
                $this->styles[MD5($src)] = $src . $sep . 'kver=' . K_COUCH_BUILD;
            }
        }

        function is_core_type( $fieldtype ){
            $known_types = array( 'text', 'password', 'textarea', 'richtext', 'image', 'thumbnail', 'file',
                                 'radio', 'checkbox', 'dropdown', 'hidden', 'message', 'group');
            return in_array( $fieldtype, $known_types );
        }

        function is_core_formfield_type( $fieldtype ){
            $known_types = array( 'text', 'password', 'textarea', 'radio', 'checkbox', 'dropdown', 'hidden', 'submit', 'captcha', 'bound' );
            return in_array( $fieldtype, $known_types );
        }

        function is_subclass( $childname, $parentname ){
            if( version_compare(phpversion(), '5.0.3', '>=') ){
                return is_subclass_of( $childname, $parentname );
            }
            else{
                $parentname = strtolower( $parentname );
                do{
                    if( $parentname === strtolower($childname) ) return true;
                }
                while( false != ($childname = get_parent_class($childname)) );
                return false;
            }
        }

        // Makes the 'do_shortcode' object mimic 'cms:embed' tag.
        // By default expects a filename as parameter. If code passed, set $is_code to 1.
        function embed( $html='', $is_code=0 ){
            global $CTX, $TAGS;

            if( !$is_code ){
                $filename = trim( $html );
                if( !strlen($filename) ) return;
            }
            else{
                if( !strlen( trim($html) ) ) return;
                $code = $html;
            }
            // get the 'obj_sc' object placed by the calling 'do_shortcode' tag on context stack
            $node = &$CTX->get_object( 'obj_sc', 'do_shortcodes' );
            if( is_null($node) ){
                // Not called from a shortcode handler.
                //.. handle using a new instance of parser
                if( !$is_code ){
                    if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                        $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
                    }
                    else{
                        $base_snippets_dir = K_COUCH_DIR . 'snippets/';
                    }

                    $filepath = $base_snippets_dir . ltrim( trim($filename), '/\\' );
                    $html = @file_get_contents( $filepath );
                    if( $html===FALSE ) return;
                }

                $parser = new KParser( $html );
                return $parser->get_HTML();
            }

            // prepare parameters for the surrogate 'embed' tag
            $params = array();
            $param = array();
            if( $is_code ){
                $param['lhs'] = 'code';
            }
            $param['op'] = '=';
            $param['rhs'] = ( $filename ) ? $filename : $code;
            $params[] = $param;

            // invoke 'embed'
            $html = $TAGS->embed( $params, $node );

            return $html;

        }

        // Expects a urlencoded and sanitized link
        function masquerade( $url ){
            global $DB;

            session_write_close();
            ob_end_clean();
            $DB->commit( 1 ); // force commit, we exit at the end of this function.
            $url = trim( $url );
            if( !strlen($url) ) die( 'Error in func masquerade: link empty' );
            $url = str_replace( ' ', '%20', $url );

            if( extension_loaded('curl') ){

                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_URL, $url );

                // pass on headers (include cookies only if masquerading internal link)
                $internal_link = ( strpos($url, K_SITE_URL)===0 ) ? 1 : 0;
                $headers = array();
                $headers[] = 'Expect:';
                $headers[] = 'Cache-Control:';
                $headers[] = 'Last-Modified:';
                foreach($_SERVER as $name => $value){
                    if( substr($name, 0, 5) == 'HTTP_' ){
                        $headername = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        if( $headername=='Host' || ($headername=='Cookie' && !$internal_link) ) continue;
                        if( defined('K_IS_MY_TEST_MACHINE') ){
                            if( $headername=='Cookie' ) $value = str_replace( 'XDEBUG_SESSION=kksidd', '' , $value );
                        }
                        $headers[] = $headername . ': ' . $value;

                    }
                }
                curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

                if( $_SERVER['REQUEST_METHOD'] != 'GET' ){
                    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD'] );
                    if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
                        $post_data = file_get_contents( 'php://input' );
                        if( !$post_data ){
                            // happens in multipart/form-data..
                            // got to recreate using the $_POST and $_FILES variables
                            $post_data = $this->_flattenpost( $_POST );

                            if( !empty($_FILES) ){
                                $arr_files = array();
                                foreach( $_FILES as $k=>$v ){
                                    $k = rawurldecode( $k );
                                    if( !is_array($v['tmp_name']) ){
                                        if( !empty($v['tmp_name']) && !$v['error'] ){
                                            $name = dirname($v['tmp_name']) . '/' . $v['name'];
                                            @unlink( $name );
                                            $arr_files[$k] = '@' . ((!@rename($v['tmp_name'], $name)) ? $v['tmp_name'] : $name);
                                        }
                                        else{
                                            $arr_files[$k] = '';
                                        }
                                    }
                                    else{
                                        $arr_files = array_merge( $arr_files, $this->_flattenpost($v['tmp_name'], $k, $v['name'], $v['error']) );
                                    }
                                }
                                $post_data = array_merge( $post_data, $arr_files );
                            }

                        }
                        curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
                    }
                }
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                //curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 ); //problematic
                curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
                curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
                curl_setopt( $ch, CURLOPT_FRESH_CONNECT, 1 );
                curl_setopt( $ch, CURLOPT_TIMEOUT, 0 );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
                curl_setopt( $ch, CURLOPT_HEADERFUNCTION, array($this, '_processhttpheader') );
                curl_setopt( $ch, CURLOPT_WRITEFUNCTION, array($this, '_processhttpbody') );

                @set_time_limit( 0 );
                curl_exec( $ch );
                $info = curl_getinfo($ch);
                if( curl_errno($ch) ){
                    echo "cURL Error: <br> Error Code: {". curl_errno($ch) . "}<br> Error Message: {". curl_error($ch) ."}<br>";
                }
                else{

                    // clean up temp uploaded files
                    if( is_array($post_data) ){
                        foreach( $post_data as $v ){
                            if( $v[0]=='@' ){ @unlink( substr( $v, 1 ) ); }
                        }
                    }


                }

                curl_close( $ch );
            }
            else{
                // fall back to redirect if curl not available
                header("Location: " . $url, TRUE, 301 );
            }

            exit;

        }

        function file_get_contents( $url ){
            $html = @file_get_contents( $url );
            if( $html==FALSE ){
                // try curl
                if( extension_loaded('curl') ){
                    $ch = curl_init();
                    curl_setopt( $ch, CURLOPT_URL, $url );
                    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                    $html = curl_exec( $ch );
                    curl_close( $ch );
                }
            }
            return $html;
        }

        // Callback func of masquerade()
        function _processhttpheader( $ch, $header ){
            /*$forwardable = array( 'set-cookie', 'content-encoding', 'content-disposition', 'content-type', 'content-range', 'content-language',
                                  'cache-control', 'pragma', 'expires' );*/

            if( strpos($header, ':') ){
                $arr = explode( ':', $header, 2 );
                /*if( in_array(strtolower($arr[0]), $forwardable) ){
                    header( $header ); // forward
                }*/
                $headername = trim( strtolower($arr[0]) );
                if( $headername != 'transfer-encoding' ) header( $header ); // forward
            }
            else{
                if( trim($header) ) header( $header ); // HTTP status code
            }

            return strlen( $header );
        }

        // Callback func of masquerade()
        function _processhttpbody( $ch, $data ){
            echo $data;
            flush();
            return strlen( $data );
        }

        // used by masquerade()
        function _flattenpost( $arr, $prefix='', $arr_name=null, $arr_error=null ){
            $ret = array();

            foreach( $arr as $k=>$v ){
                $orig_k = $k;
                $k = rawurldecode( $k );
                if( $prefix ) $k = $prefix . '[' . $k . ']';
                if( !is_array($v) ){
                    if( is_null($arr_name) ){
                        $ret[$k] = $v;
                    }
                    else{
                        // it is a file
                        if( !empty($v) && !$arr_error[$k] ){
                            $name = dirname($v) . '/' . $arr_name[$orig_k];
                            @unlink( $name );
                            $ret[$k] = '@' . ((!@rename($v, $name)) ? $v : $name);
                        }
                        else{
                            $ret[$k] = '';
                        }
                    }
                }
                else{
                    // recursively flatten the array
                    $arr_name2 = ( is_null($arr_name) ) ? null : $arr_name[$orig_k];
                    $arr_error2 = ( is_null($arr_error) ) ? null : $arr_error[$orig_k];
                    $child_ret = $this->_flattenpost( $v, $k, $arr_name2, $arr_error2 );
                    $ret = array_merge( $ret, $child_ret );
                }
            }
            return $ret;
        }

        // Expects an array
        function serialize( $var = array(), $inner = FALSE ){
            if( $inner ){
                foreach( $var as $k => $v ){
                    if( is_array($v) ){
                        $var[$k] = $this->serialize($v, 1);
                    }
                    else {
                        $var[$k] = base64_encode($v);
                    }
                }
                return $var;
            }
            else{
                return serialize( $this->serialize($var, 1) );
            }
        }

        function unserialize( $var = FALSE, $inner = FALSE ){
            if( $inner ){
                if( $var ){
                    foreach( $var as $k => $v ){
                        if( is_array($v) ){
                            $var[$k] = $this->unserialize( $v, 1 );
                        }else{
                            $var[$k] = base64_decode($v);
                        }
                    }
                }
                return $var;
            }
            else{
                return $this->unserialize( @unserialize($var), 1 );
            }
        }

        function filterExif( $exifdata ){
            $accepted = array(
                'aperture',
                'color',
                'componentConfig',
                'jpegQuality',
                'exifComment',
                'contrast',
                'copyright',
                'customRendered',
                'DateTime',
                'dateTimeDigitized',
                'zoomRatio',
                'distanceRange',
                'Height',
                'Width',
                'exifVersion',
                'exposureBias',
                'exposureMode',
                'exposure',
                'exposureTime',
                'fnumber',
                'flashUsed',
                'flashpixVersion',
                'focalLength',
                'focusDistance',
                'gainControl',
                'isoEquiv',
                'make',
                'meteringMode',
                'model',
                'orientation',
                'jpegQuality',
                'resolution',
                'resolutionUnit',
                'saturation',
                'screenCaptureType',
                'sharpness',
                'software',
                'whiteBalance',
                'YCbCrPositioning',
                'xResolution',
                'yResolution',
            );
            $arr = array();
            if( is_array($exifdata) && count($exifdata) ){
                $count = count( $accepted );
                for( $x=0; $x<$count; $x++ ){
                    if( isset($exifdata[$accepted[$x]]) ){
                        $arr[$accepted[$x]] = $exifdata[$accepted[$x]];
                    }
                }
            }
            return $arr;
        }

        function render_admin_page_ex( $_p ){
            global $AUTH, $DB;

            if( !K_PAID_LICENSE ){ $html_title = 'CouchCMS - Simple Open-Source Content Management : '; }
            $html_title .= $this->t( 'admin_panel' );
            ?>
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
            <head>
                <title><?php echo $html_title ?></title>
                <link rel="shortcut icon" href="<?php echo K_ADMIN_URL . 'favicon.ico'; ?>" type="image/x-icon" />
                <script type="text/javascript">try { document.execCommand('BackgroundImageCache', false, true); } catch(e) {}</script>
                <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/mootools-core-1.4.5.js'; ?>"></script>
                <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/mootools-more-1.4.0.1.js'; ?>"></script>
                <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/slimbox/slimbox.js'; ?>"></script>
                <script type="text/javascript" src="<?php echo K_ADMIN_URL . 'includes/smoothbox/smoothbox.js?v=1.3.5'; ?>"></script>
                <?php
                foreach( $this->scripts as $k=>$v ){
                    echo '<script type="text/javascript" src="'.$v.'"></script>'."\n";
                }
                ?>

                <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'includes/slimbox/slimbox.css'; ?>" type="text/css" media="screen" />
                <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'includes/smoothbox/smoothbox.css'; ?>" type="text/css" media="screen" />
                <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'theme/styles.css?ver='.K_COUCH_BUILD.''; ?>" type="text/css" media="screen" />
                <!--[if IE]>
                <link rel="stylesheet" href="<?php echo K_ADMIN_URL . 'theme/ie.css?ver='.K_COUCH_BUILD.''; ?>" type="text/css" media="screen, projection">
                <![endif]-->
                <?php
                foreach( $this->styles as $k=>$v ){
                    echo '<link rel="stylesheet" href="'.$v.'" type="text/css" media="screen" />'."\n";
                }
                ?>
            </head>
            <body>
            <div id="container" ><div id="container2" >

            <?php
            // header
            echo '<div id="header" >';
            if( K_PAID_LICENSE ){
                if( defined('K_LOGO_DARK') ){
                    $logo_src = K_ADMIN_URL.'theme/images/'.K_LOGO_DARK;
                }
                else{
                    $logo_src = K_ADMIN_URL.'theme/images/couch_dark.gif';
                }
            }
            else{
                $logo_src = K_ADMIN_URL.'logo.php?d=1';
            }
            echo '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'"><img id="couch-logo" src="'.$logo_src.'" /></a>';

            echo '<ul id="admin-subnav">';
            $nonce = $this->create_nonce( 'update_user_'.$AUTH->user->id );
            echo '<li>'.$this->t('greeting').', <a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=users&act=edit&id='.$AUTH->user->id.'&nonce='.$nonce.'"><b>' . ucwords( strtolower($AUTH->user->title) ) . '</b></a></li>';
            echo '<li>|</li>';
            echo '<li><a href="'.K_SITE_URL.'" target="_blank">'.$this->t('view_site').'</a></li>';
            echo '<li>|</li>';
            echo '<li><a href="'.$this->get_logout_link(K_ADMIN_URL . K_ADMIN_PAGE).'">'.$this->t('logout').'</a></li>';
            echo '</ul>';
            ?>
            <noscript>
                <div class="error">
                    <?php echo $this->t('javascript_msg'); ?>
                </div>
            </noscript>
            <?php
            if( $_p['link'] ){
                echo '<h2><a id="listing-header" href="'.$_p['link'].'">' . $_p['title'] .'</a></h2>';
            }
            else{
                echo '<h2>' . $_p['title'] .'</h2>';
            }
            echo $_p['buttons'];
            echo '</div>'; // end header

            // body
            ?>
            <div id="sidebar">
                <ul class="templates">
                    <?php
                    $show_comments_link = 0;
                    $rs = $DB->select( K_TBL_TEMPLATES, array('*'), '1=1 ORDER BY k_order, id ASC' );
                    if( count($rs) ){
                        foreach( $rs as $tpl ){

                            $class = '';
                            if( $tpl['hidden'] ){
                                if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){
                                    continue;
                                }
                                else{
                                    $class = "hidden-template ";
                                }
                            }

                            $class .= ( $tpl['name']==$_p['tpl_name'] ) ? "active-template" : "template";
                            echo '<li class="'.$class.'">';
                            if( $tpl['clonable'] ){
                                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $tpl['id'];
                            }
                            else{
                                $nonce = $this->create_nonce( 'edit_page_'.$tpl['id'] );
                                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=edit&tpl=' . $tpl['id'] .'&nonce='.$nonce;
                            }
                            if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                                echo '<a title="'.$tpl['name'].'" href="'.$link.'">';
                            }
                            else{
                                echo '<a href="'.$link.'">';
                            }
                            if( $tpl['clonable'] ){
                                echo '<img src="'.K_ADMIN_URL.'theme/images/copy.gif"/> ';
                            }
                            echo ( $tpl['title'] ) ? $tpl['title'] : $tpl['name'];
                            echo '</a>';
                            echo '</li>';

                            if( $tpl['commentable'] ) $show_comments_link=1;
                        }
                    }
                    ?>
                    <li class="template-separator">
                        <a href="#">&nbsp;</a>
                    </li>
                    <?php
                        // Show link to comments section only if any template is commentable or if any comment exists
                        if( !$show_comments_link ){
                            $rs = $DB->select( K_TBL_COMMENTS, array('id'), '1=1 LIMIT 1' );
                            if( count($rs) ) $show_comments_link=1;
                        }
                    ?>
                    <?php
                        if( $show_comments_link ){
                            $class = ( $_p['module']=='comments' ) ? "active-template" : "template";
                    ?>
                            <li class="<?php echo $class; ?>">
                                <a title="<?php echo $this->t('manage_comments'); ?>" href="<?php echo K_ADMIN_URL . K_ADMIN_PAGE.'?o=comments'; ?>">
                                    <img src="<?php echo K_ADMIN_URL.'theme/images/comment.gif'; ?>">
                                    <?php echo $this->t('comments'); ?>
                                </a>
                            </li>
                    <?php } ?>

                    <?php $class = ( $_p['module']=='users' ) ? "active-template" : "template"; ?>
                    <li class="<?php echo $class; ?>">
                        <a title="<?php echo $this->t('manage_users'); ?>" href="<?php echo K_ADMIN_URL . K_ADMIN_PAGE.'?o=users'; ?>">
                            <img src="<?php echo K_ADMIN_URL.'theme/images/user.gif'; ?>">
                            <?php echo $this->t('users'); ?>
                        </a>
                    </li>

                    <?php
                        if( $_p['module']=='drafts' ){
                            $class = 'active-template';
                            $draft_img = 'drafts-open.gif';
                            $show_drafts_link = 1;
                        }
                        else{
                            $class = 'template';
                            $draft_img = 'drafts-closed.gif';
                        }
                        if( !$show_drafts_link ){
                            $rs = $DB->select( K_TBL_PAGES, array('id'), 'parent_id>0 LIMIT 1' );
                            if( count($rs) ) $show_drafts_link=1;
                        }
                    ?>
                    <?php if( $show_drafts_link ){ ?>
                    <li class="<?php echo $class; ?>">
                        <a title="<?php echo $this->t('manage_drafts'); ?>" href="<?php echo K_ADMIN_URL . K_ADMIN_PAGE.'?o=drafts'; ?>">
                            <img src="<?php echo K_ADMIN_URL.'theme/images/'.$draft_img; ?>">
                            <?php echo $this->t('drafts'); ?>
                        </a>
                    </li>
                    <?php } ?>
                </ul>
            </div>

            <div id="admin-wrapper">
                <div id="admin-wrapper-header">
                    <?php if( $_p['show_advanced'] ){ ?>
                        <div id="advanced-settings">
                            <a id="toggle" class="collapsed" href="#"><?php echo $this->t('advanced_settings'); ?></a>
                        </div>
                    <?php } ?>
                    <?php if( $_p['subtitle'] ) echo '<h3>'.$_p['subtitle'].'</h3>';  ?>

                </div>
                <div id="admin-wrapper-body">
                    <?php echo $_p['content'] ?>
                </div>
            </div>

            <div id="footer" style="z-index:99999 !important; display:block !important; visibility:visible !important;">
                <?php
                    $admin_footer = '<a href="http://www.couchcms.com/" style="display:inline !important; visibility:visible !important;">CouchCMS - Simple Open-Source Content Management ';
                    $admin_footer .= 'v' . K_COUCH_VERSION . ' (build ' . K_COUCH_BUILD . ')</a>';
                    if( K_PAID_LICENSE ){
                        if( defined('K_ADMIN_FOOTER') ) $admin_footer = K_ADMIN_FOOTER;
                    }
                    echo $admin_footer;
                    if( defined('K_IS_MY_TEST_MACHINE') ){
                        echo '&nbsp;['.k_timer_stop().']';
                    }
                ?>
            </div>
            </div></div>
            </body>
            </html>
            <?php
            die;
        }


    }// end class KFuncs


    class KError{
        var $err_msg = '';

        function KError( $err_msg='' ){
            $this->err_msg = $err_msg;
        }
    }
