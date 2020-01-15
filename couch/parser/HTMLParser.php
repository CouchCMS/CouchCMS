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

    class KHTMLNode extends KNode{
        var $is_end_tag = 0;
        var $is_self_closing = 0;
        var $is_ignored = 0;
        var $self_closing_tags = array('br', 'hr', 'img', 'input', 'meta', 'link', 'spacer', 'frame', 'base', 'area');

        var $cleanXSS;
        var $for_comments;
        var $escape_tag;
        var $safe_tags = array(
                                'img', 'div', 'span', 'a', 'p', 'blockquote', 'code', 'address',  'cite',
                                'ul', 'ol', 'li', 'dd', 'dl',  'dt',
                                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                                'table', 'caption', 'col', 'colgroup', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr',
                                'br', 'hr', 'pre', 'b', 'u', 'i', 'strong', 'big', 'small', 'em', 'sub', 'sup',
                                'center', 'font', 'strike', 'del', 'abbr', 'dfn', 'samp', 'var', 'kbd', 'ins', 's', 'bdo',

                                'article', 'aside', 'bdi', 'details', 'figcaption', 'figure', 'footer', 'header', 'hgroup',
                                'mark', 'nav', 'rp', 'rt', 'ruby', 'section', 'summary', 'time', 'wbr'
                                );

        function __construct( $type, $name='', $attr='', $text='', $cleanXSS=0, $for_comments=0, $safe_tags=null ){
            global $FUNCS;

            if( $name[0]=='/' ){
                $this->is_end_tag = 1;
                $name = trim( substr($name, 1) );
            }
            elseif( substr($attr[0], -1) == '/' ){
                $this->is_self_closing = 1;
                $attr[0] = substr( $attr[0], 0, -1 );
            }
            elseif( in_array($name, $this->self_closing_tags) ){
                $this->is_self_closing = 1;
            }
            $this->cleanXSS = $cleanXSS;
            $this->for_comments = $for_comments;
            if( is_array($safe_tags) ) $this->safe_tags = $safe_tags;

            if( $this->cleanXSS ){
                if( $type == K_NODE_TYPE_TEXT ){
                    $text = $FUNCS->escape_HTML( $text );
                    if( $this->for_comments==1 ){
                        $text = $this->nl2br( $text );
                    }
                }
                elseif( $type == K_NODE_TYPE_CODE ){
                    if( !in_array($name, $this->safe_tags) ){
                        $this->escape_tag = 1;
                    }

                    if( strlen($attr[0]) ){
                        $val = $attr[0];

                        // normalize (decode) all entities before hunting for XSS elements
                        $val = $this->normalize_entities( $val );

                        // sanitize
                        $val = $this->sanitize( $val );

                        if( $this->escape_tag ){ $val = $FUNCS->escape_HTML( $val ); }
                        $attr[0] = $val;
                    }

                    // if tag being used within comments, strip off attributes (except href of 'a' tag)
                    if( $this->for_comments ){
                        if( $name=='a' ){
                            $link = preg_match( '@\bhref\s*=\s*["\']([^"\']*)["\']@is', $attr[0], $matches ) ? $matches[1] : '';
                            $attr[0] = 'rel="external nofollow" href="'.$link.'"';

                        }
                        else{
                            $attr[0] = '';
                        }
                    }
                }
            }

            parent::__construct($type, $name, $attr, $text );
        }

        function normalize_entities( $str ){
            $found = true;
            while ( $found == true ){
                $str_prev = $str;

                // replace literal entities
                $str = html_entity_decode( $str, ENT_QUOTES, K_CHARSET );

                // replace dangerous HTML5 entities
                $str = str_ireplace( array('&colon;', '&lpar;', '&rpar;', '&newline;', '&tab;'), array(':', '(', ')', "\n", "\t"), $str );

                // replace numeric entities
                $str = preg_replace_callback( '~&#x0{0,8}([0-9A-F]+);?~i',
                                                function($matches){
                                                    $val = hexdec($matches[1]);
                                                    return ( $val < 128 ) ? chr($val) : $matches[0];
                                                },
                                                $str );

                $str = preg_replace_callback( '~&#0{0,8}([0-9]+);?~i',
                                                function($matches){
                                                    $val = $matches[1];
                                                    return ( $val < 128 ) ? chr($val) : $matches[0];
                                                },
                                                $str );
                $str = stripcslashes( $str );

                if( $str_prev == $str ){  $found = false; }
            }
            return $str;
        }

        function sanitize( $val ){

            $ra = array( 'javascript', 'vbscript', 'expression', 'script' );
            $found = true;
            while( $found == true ){
                $val_before = $val;

                // remove C style coments
                $pattern = '~/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/~';
                $val = preg_replace( $pattern, '', $val );

                // these words can have white spaces between them
                for( $i = 0; $i < count($ra); $i++ ){
                    $pattern = '/';
                    for( $j = 0; $j < strlen($ra[$i]); $j++ ){
                        if( $j > 0 ){
                            $pattern .= '\s*';
                        }
                        $pattern .= $ra[$i][$j];
                    }
                    $pattern .= '/i';
                    $replacement = substr( $ra[$i], 0, 2 ).'xxx'.substr( $ra[$i], 2 );
                    $val = preg_replace( $pattern, $replacement, $val );
                }

                if( $val_before == $val ){ $found = false; }

            }

            // invalidate other dangerous words
            // https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
            // https://gist.github.com/kurobeats/9a613c9ab68914312cbb415134795b45
            $ra2 = array(
                 'fscommand', 'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate',
                 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus',
                 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbegin', 'onbeforeupdate', 'onblur',
                 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu',
                 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged',
                 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend',
                 'ondragenter', 'ondragleave', 'ondragover', 'ondragdrop', 'ondragstart', 'ondrop', 'onend',
                 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus',
                 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup',
                 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmediacomplete', 'onmediaerror', 'onmousedown', 'onmouseenter',
                 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup',
                 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onoutofsync', 'onpaste', 'onpause', 'onprogress',
                 'onpropertychange', 'onreadystatechange', 'onrepeat', 'onreset', 'onresize',
                 'onresizeend', 'onresizestart', 'onresume', 'onreverse', 'onrowenter', 'onrowexit', 'onrowsdelete',
                 'onrowsinserted', 'onscroll', 'onseek', 'onselect', 'onselectionchange', 'onselectstart',
                 'onstart', 'onstop', 'onsyncrestored', 'onsubmit', 'ontimeerror', 'ontrackchange', 'onunload',
                 'onurlflip', 'seeksegmenttime',
                 'moz-binding', 'expression', 'mocha',
                 'document.cookie', 'document.write', 'window.location', 'document.location',
                 'datafld', 'dataformatas', 'datasrc', 'binding', 'behavior',
                 'onformchange', 'onforminput', 'formaction', 'oninput', 'dirname', 'pattern', 'mhtml:',
                 'onhashchange', 'onmessage', 'onoffline', 'ononline', 'onpagehide', 'onpageshow', 'onpopstate', 'onstorage', 'onundo', 'onredo',
                 'oninvalid', 'onsearch', 'onwheel', 'oncanplay', 'oncuechange', 'ondurationchange', 'onemptied', 'onplay', 'onratechange',
                 'onstalled', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting', 'onshow', 'ontoggle',
                 'onanimation', 'onauxclick', 'onfullscreen', 'ongotpointercapture', 'onlostpointercapture', 'onpointer', 'onorientationchange',
                 'ontouch', 'ontransition', 'onvisibilitychange', 'onwebkit', 'onmoz',
                 );

            for( $i = 0; $i < count($ra2); $i++ ){
                $replacement = substr( $ra2[$i], 0, 2 ).'xxx'.substr( $ra2[$i], 2 );
                $val = preg_replace("#".$ra2[$i]."#i", $replacement, $val );
            }

            return $val;
        }

        function get_HTML( $level=0 ){
            if( $this->is_ignored ) return;

            switch( $this->type ){
                case K_NODE_TYPE_ROOT:
                    foreach( $this->children as $node ){
                        $html .= $node->get_HTML( $level++ );
                    }
                    break;
                case K_NODE_TYPE_TEXT:
                    $html = $this->text;
                    break;
                case K_NODE_TYPE_CODE:
                    $opening_tag = ($this->escape_tag) ? '&lt;' : '<';
                    $closing_tag = ($this->escape_tag) ? '&gt;' : '>';

                    $html = $opening_tag . $this->name;
                    if( strlen($this->attributes[0]) ){
                        $html .= ' ' . $this->attributes[0];
                    }
                    if( $this->is_self_closing && !K_HTML4_SELFCLOSING_TAGS ){
                        $html .= strlen($this->attributes[0]) ? '/'.$closing_tag : ' /'.$closing_tag;
                    }
                    else{
                        $html .= $closing_tag;
                    }

                    foreach( $this->children as $node ){
                        //if( $this->escape_tag ){ $node->escape_tag = 1; }
                        $html .= $node->get_HTML( $level++ );
                    }
                    $html .= $this->is_self_closing ? '' : $opening_tag.'/' . $this->name . $closing_tag;
                    break;
            }

            return $html;
        }

        function get_info( $level=0 ){
            return;
        }

        // converts newlines to <br>
        function nl2br( $text ){
            $text = str_replace( "\r\n", "\n", $text );
            $text = str_replace( "\r", "\n", $text );

            //coalesce multiple newlines to max two
            while( preg_match("/(\n\s+\n)/", $text, $matches) ){
                $text = preg_replace( "/".$matches[1]."/", "\n\n", $text );
            }
            $text = preg_replace( "/\n\n+/", "\n\n", $text );

            //$text = nl2br( $text );
            while( preg_match("/[^\^\>](\n)[^\$]/", $text, $matches) ){ //skip those at the very ends
                $text = preg_replace( "/".$matches[1]."/", "<br />\n", $text );
            }

            return $text;
        }
    }

    class KHTMLParser{
        var $str;
        var $pos;
        var $state;
        var $stack;
        var $curr_node;
        var $DOM;
        var $parsed;
        var $ignore_tags = array();
        var $cleanXSS;
        var $for_comments;
        var $allowed_tags = null;

        var $HTML4_tags = array(
                        'a','abbr','acronym','address','applet','area','b','base','basefont','bdo','big','blockquote','body',
                        'br','button','caption','center','cite','code','col','colgroup','dd','del','dfn','dir','div','dl','dt',
                        'em','fieldset','font','form','frame','frameset','h1','h2','h3','h4','h5','h6','head','hr','html','i',
                        'iframe','img','input','ins','isindex','kbd','label','legend','li','link','map','menu','meta','noframes',
                        'noscript','object','ol','optgroup','option','p','param','pre','q','s','samp','script','select','small',
                        'span','strike','strong','style','sub','sup','table','tbody','td','textarea','tfoot','th','thead','title',
                        'tr','tt','u','ul','var'
                        );

        function __construct( $str, $ignore_tags=null, $cleanXSS=0, $for_comments=0, $allowed_html_tags='' ){
            $this->str = $str;
            $this->state = K_STATE_TEXT;
            $this->stack = array();
            $this->curr_node = new KHTMLNode( K_NODE_TYPE_ROOT );
            $this->DOM = &$this->curr_node;
            if( is_array($ignore_tags) ){
                $this->ignore_tags = array_map( "strtolower", $ignore_tags );
            }
            $this->cleanXSS = $cleanXSS;
            $this->for_comments = $for_comments;

            if( $this->cleanXSS ){

                // remove all non-printable characters except CR(0D) and LF(0A) and TAB(09).
                $this->str = preg_replace( '/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $this->str );

                // remove HTML comments
                $str = $this->str;
                $found = true;
                while ( $found == true ){
                    $str_prev = $str;

                    $pattern = '~\<![ \r\n\t]*(--([^\-]|[\r\n]|-[^\-])*--[ \r\n\t]*)\>~';
                    $str = preg_replace( $pattern, '', $str );

                    if( $str_prev == $str ){  $found = false; }
                }
                $this->str = $str;

                // allowed tags ..(currently only used with KFieldForm fields i.e. front-end forms)
                $allowed_html_tags = strtolower( trim($allowed_html_tags) );
                if( $allowed_html_tags!='' ){
                    if( $allowed_html_tags=='none' ){
                        $this->allowed_tags = array();
                    }
                    else{
                        $allowed_html_tags = array_map( "trim", explode(",", $allowed_html_tags) );

                        if( is_array($allowed_html_tags) ){
                            $class_vars = get_class_vars( 'KHTMLNode' );
                            $allowed_html_tags = array_intersect( $allowed_html_tags, $class_vars['safe_tags'] );
                            if( count($allowed_html_tags) ){
                                $this->allowed_tags = $allowed_html_tags;
                            }
                        }
                    }
                }

            }
        }

        function &get_DOM(){
            if( !$this->parsed ){
                $this->pos = 0;
                $tag = null;

                while( 1==1 ){
                    switch( $this->state ){
                        case K_STATE_TEXT:
                            $start = $this->pos;
                            $tag = &$this->get_next_tag();
                            if( !$tag ){
                                $text = substr( $this->str, $start, strlen($this->str)-$start );

                                // Remove any truncated tag in the remaining text
                                $pattern = '/<(\/?(?(?<=\/)\s*[A-Z]|[A-Z])[A-Z0-9]*)\s*((?:[A-Z]+\s*=\s*["\'][^"\']*["\']|[^<>])*)/is';
                                $res = preg_match( $pattern, $text, $matches, PREG_OFFSET_CAPTURE );
                                if( $res ){
                                    $text = substr( $text, 0, -(strlen($text)-$matches[0][1]) );
                                }

                                $this->add_child( new KHTMLNode( K_NODE_TYPE_TEXT, '', '', $text, $this->cleanXSS, $this->for_comments ) );
                                break 2;
                            }
                            $text = substr( $this->str, $start, $tag->char_num-$start );
                            $this->add_child( new KHTMLNode( K_NODE_TYPE_TEXT, '', '', $text, $this->cleanXSS, $this->for_comments ) );

                            if( $tag->is_end_tag ){
                                $this->state = K_STATE_TAG_CLOSE;
                            }
                            else{
                                $this->state = K_STATE_TAG_OPEN;
                            }
                            break;

                        case K_STATE_TAG_OPEN:
                            if( $tag->name ){
                                if( $tag->is_self_closing ){
                                    $this->add_child( $tag );
                                }
                                else{
                                    if( $tag->name==$this->curr_node->name &&
                                       !in_array($this->curr_node->name, array('div', 'span', 'blockquote'))
                                    ){
                                        $this->pop();
                                    }
                                    $this->add_child( $tag );
                                    $this->push( $tag );
                                }
                            }
                            $this->state = K_STATE_TEXT;
                            break;

                        case K_STATE_TAG_CLOSE:
                            if( $tag->name!=$this->curr_node->name ){
                                $pos = $this->find( $tag->name );
                                if( $pos!== false ){
                                    $cnt_pop = count($this->stack)-$pos;
                                    for( $x=0; $x<$cnt_pop; $x++ ){
                                        $this->pop();
                                    }
                                    $this->pop();
                                }
                            }
                            else{
                                $this->pop();
                            }
                            $this->state = K_STATE_TEXT;
                            break;
                    }

                }
                if( $this->state != K_STATE_TEXT ){
                    echo "Parsing ended in an invalid state";
                }
                if( count($this->stack) ){
                    // Unclosed tags
                }
                $this->parsed = true;
            }
            return $this->DOM;
        }

        function &get_next_tag(){
            $pattern = '/<(\/?(?(?<=\/)\s*[A-Z]|[A-Z])[A-Z0-9]*)\s*((?:[A-Z]+\s*=\s*["\'][^"\']*["\']|[^<>])*)>/is';

            while( 1==1 ){
                $res = preg_match( $pattern, $this->str, $matches, PREG_OFFSET_CAPTURE, $this->pos ); //We'll now require php 4.3.3
                if( !$res ) return false;

                $match = $matches[0];
                $tag = $matches[1];
                $attr = $matches[2];

                $tag_name = strtolower( trim($tag[0]) );
                $attr_str = $attr[0];
                $starts = $match[1];
                $len = strlen( $match[0] );
                $this->pos = $starts + $len;

                $node = new KHTMLNode( K_NODE_TYPE_CODE, $tag_name, array($attr_str), '', $this->cleanXSS, $this->for_comments, $this->allowed_tags );
                $node->char_num = $starts;
                if( in_array($tag_name, $this->ignore_tags) ){
                    $node->is_ignored = 1;
                }

                // return tag only if it is a valid HTML4 tag
                //if( in_array($node->name, $this->HTML4_tags) ){
                    return $node;
                //}
            }
        }

        function get_HTML(){
            $DOM = &$this->get_DOM();
            return $DOM->get_HTML();
        }

        function add_child( &$child ){
            $this->curr_node->children[] = &$child;
        }

        function push( &$node ){
            $this->stack[count($this->stack)] = &$this->curr_node;
            $this->curr_node = &$node;
        }

        function pop(){
            if( !count($this->stack) ) return;

            unset( $this->curr_node );
            $this->curr_node = &$this->stack[count($this->stack)-1];
            unset( $this->stack[count($this->stack)-1] );
        }

        function find( $tag_name ){
            for( $x=count($this->stack)-1; $x>=0; $x-- ){
                if( $this->stack[$x]->name == $tag_name ) return $x;
            }
            return false;
        }

    }// end class KHTMLParser

    require_once( K_COUCH_DIR.'parser/BBParser.php' );
