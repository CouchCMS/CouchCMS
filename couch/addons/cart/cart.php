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

    define( 'PP_CART_VERSION', '1.0' );
    define( 'PP_ACTION_UNDEFINED', 0 );
    define( 'PP_ACTION_ADD_ITEM', 1 );
    define( 'PP_ACTION_UPDATE_ITEMS', 2 );
    define( 'PP_ACTION_REMOVE_ITEM', 3 );
    define( 'PP_ACTION_EMPTY_CART', 4 );
    define( 'PP_ACTION_CHECKOUT', 5 );
    define( 'PP_ACTION_CUSTOM', 6 );

    class KCart{
        var $config = array();
        var $link_cart_template = '';
        var $link_checkout_template = '';
        var $current_action = PP_ACTION_UNDEFINED;
        var $current_action_success = null;
        var $updated_rows = array(); // Will contain the keys & original quantities of the following line-item(s) in cart
                                     // 1. The quantities of whom were changed either by 'add_item' & 'update_items' actions or
                                     // 2. Were added by 'add_item' action. The original quantity in this case will always be 0.

        // Contents (kept in session)
        // These variables (including custom_vars) reflect the current state of the cart
        // which in turn depends on the contained items.
        // So when there are no items (i.e. cart is empty) these are all empty.
        var $items = array();
        var $count_items = 0; // total number of items (taking quantity into consideration)
        var $count_shippable_items = 0; // total number of items that are shippable
        var $sub_total = 0; // total price of all lines
        var $discount = 0;
        var $sub_total_discounted = 0; // total price of all lines - discount
        var $taxes = 0;
        var $shipping = 0;
        var $total = 0; // $sub_total_discounted + $taxes + $shipping
        var $custom_vars = array();


        function __construct(){
            global $FUNCS;

            $this->populate_config();

            // get cart from session
            $this->deserialize();

            // register custom tags
            $FUNCS->register_tag( 'pp_product_form', array('KCart', 'product_form_handler'), 1, 0 ); // Generates the form used to add a product to the cart
            $FUNCS->register_tag( 'pp_product_options', array('KCart', 'product_options_handler'), 1, 1 ); // iterates through product options (e.g. Size, Color etc.)
            $FUNCS->register_tag( 'pp_option_values', array('KCart', 'product_option_values_handler'), 1, 1 ); // creates list/dropdowns of values of an option (e.g. Red, Green etc. for Color)
            $FUNCS->register_tag( 'pp_cart_form', array('KCart', 'cart_form_handler'), 1, 0 ); // Generates the form used to allow updating items in cart
            $FUNCS->register_tag( 'pp_cart_items', array('KCart', 'cart_items_handler'), 1, 1 ); // iterates through line-items in the cart
            $FUNCS->register_tag( 'pp_selected_options', array('KCart', 'selected_options_handler'), 1, 1 ); // creates list of options selected for a line_item
            $FUNCS->register_tag( 'pp_payment_gateway', array('KCart', 'gateway_handler') );

            $FUNCS->register_tag( 'pp_count_items', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_count_unique_items', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_count_shippable_items', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_sub_total', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_discount', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_sub_total_discounted', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_taxes', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_shipping', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_total', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_custom_var', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_currency_symbol', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_refresh_cart', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_config', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_add_item_link', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_update_item_link', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_remove_item_link', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_empty_cart_link', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_checkout_link', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_cart_link', array('KCart', 'cart_vars_handler'), 0, 0 );
            $FUNCS->register_tag( 'pp_empty_cart', array('KCart', 'cart_vars_handler'), 0, 0 );

            // check if invoked with an action to perform on Cart
            $this->check_action();
        }

        function populate_config(){

            $pp = array();
            if( file_exists(K_ADDONS_DIR.'cart/config.php') ){
                require_once( K_ADDONS_DIR.'cart/config.php' );
            }
            $this->config = array_map( "trim", $pp );
            unset( $pp );

            // important default values
            if( !$this->config['tpl_products'] ) $this->config['tpl_products']= 'products.php';
            if( !$this->config['tpl_cart'] ) $this->config['tpl_cart']= 'cart.php';
            if( !$this->config['tpl_checkout'] ) $this->config['tpl_checkout']= 'checkout.php';
            if( !isset($this->config['paypal_use_sandbox']) ) $this->config['paypal_use_sandbox']= K_PAYPAL_USE_SANDBOX;
            if( !$this->config['paypal_email'] ) $this->config['paypal_email']= K_PAYPAL_EMAIL;
            if( !$this->config['currency'] ) $this->config['currency']= K_PAYPAL_CURRENCY;
            if( !$this->config['currency_symbol'] ) $this->config['currency_symbol']= '$';
            if( !$this->config['allow_decimal_qty']==1 ) $this->config['allow_decimal_qty']= '0';
        }

        function get_config( $item ){
            $item = trim( $item );
            if( $item ){
                return $this->config[$item];
            }
        }

        // These custom var functions give a way to the sub-class to set/use vars that get persisted with the cart like
        // the native variables pertaining to calculated values.
        // The 'cms:pp_custom_var' tag can be used to access these vars from the front-end.
        // Like the native variables, these are cart-wide and will be reset when the cart is empty.
        function set_custom_var( $key, $val ){
            $key = trim( $key );
            if( $key ){
                $this->custom_vars[$key]=$val;
            }
        }

        function get_custom_var ($key ){ // Used by 'cms:pp_custom_var' tag
            $key = trim( $key );
            if( $key ){
                return $this->custom_vars[$key];
            }
        }

        function delete_custom_var ($key ){
            $key = trim( $key );
            if( $key ){
                unset( $this->custom_vars[$key] );
            }
        }

        // get cart data from session (sort of an extended constructor)
        function deserialize(){
            if(!session_id()) @session_start();
            $data = $_SESSION['kcart'];

            if( is_array($data) && count($data) ){
                // fill variables
                $this->items = $data['items'];
                $this->count_items = $data['count_items'];
                $this->count_shippable_items = $data['count_shippable_items'];
                $this->sub_total = $data['sub_total'];
                $this->discount = $data['discount'];
                $this->sub_total_discounted = $data['sub_total_discounted'];
                $this->taxes = $data['taxes'];
                $this->shipping = $data['shipping'];
                $this->total = $data['total'];
                $this->custom_vars = $data['custom_vars'];
            }
            else{
                // no data in session. Store default values to begin with.
                $this->serialize();
            }
        }

        // store cart data in session
        function serialize(){

            $this->retotal();

            $data = array(
                'items' => $this->items,
                'count_items' => $this->count_items,
                'count_shippable_items' => $this->count_shippable_items,
                'sub_total' => $this->sub_total,
                'discount' => $this->discount,
                'sub_total_discounted' => $this->sub_total_discounted,
                'taxes' => $this->taxes,
                'shipping' => $this->shipping,
                'total' => $this->total,
                'custom_vars' => $this->custom_vars,
            );

            // why are we not storing the complete KCART object in seesion?
            // because if 'session.auto_start' is on, there is problem in storing objects in session.
            $_SESSION['kcart'] = $data;
        }

        function check_action(){
            global $FUNCS;

            if( isset($_GET['kcart_action']) && $FUNCS->is_non_zero_natural($_GET['kcart_action']) ){

                // Sanity check - actions should be executed only when invoked on the cart template
                $cur_tpl = $FUNCS->get_template_name();
                if( $FUNCS->is_error($cur_tpl) || $cur_tpl!=$this->config['tpl_cart']) return;

                $action = (int)$_GET['kcart_action'];
                if( $action==PP_ACTION_UPDATE_ITEMS && isset($_POST['checkout']) ){
                    // Account for 'checkout' button implemented as a submit button instead of a link
                    $action = PP_ACTION_CHECKOUT;
                }

                $this->current_action = $action;
                switch( $action ){
                    case PP_ACTION_ADD_ITEM: /* add item */
                        $this->add_item();
                        break;
                    case PP_ACTION_UPDATE_ITEMS: /* update items */
                        $this->update_items();
                        break;
                    case PP_ACTION_REMOVE_ITEM: /* remove item */
                        $this->remove_item();
                        break;
                    case PP_ACTION_EMPTY_CART: /* empty cart */
                        $this->empty_cart();
                        break;
                    case PP_ACTION_CHECKOUT: /* checkout */
                        $this->checkout();
                        break;
                    default:
                        $this->current_action = PP_ACTION_CUSTOM;
                        $this->custom_action(); /* some custom action probably implemented by subclass */
                }
            }
        }

        function add_item(){
            global $FUNCS, $DB;

            if( isset($_POST['pp_id']) && $FUNCS->is_non_zero_natural($_POST['pp_id']) ){

                $item_number = (int)$_POST['pp_id'];
                $rs = $DB->select( K_TBL_PAGES, array('id', 'template_id'), "id = '".$DB->sanitize( $item_number )."'" );
                if( count($rs) ){
                    $rec = $rs[0];
                    $pg = new KWebpage( $rec['template_id'], $rec['id'] );
                    if( !$pg->error ){
                        // get all cart related fields from page
                        $arr_pp_fields = array( 'pp_price', 'pp_options', 'pp_requires_shipping' );
                        $arr_custom_fields = array();
                        for( $x=0; $x<count($pg->fields); $x++ ){
                            if( $pg->fields[$x]->system || $pg->fields[$x]->deleted ) continue;

                            $fname = $pg->fields[$x]->name;
                            if( in_array($fname, $arr_pp_fields) ){
                               $$fname = trim( $pg->fields[$x]->get_data() );
                            }
                            else{ // is it a custom field? Check if prefixed with a 'pp_'
                                if( substr($fname, 0, 3)=='pp_' ){
                                    $arr_custom_fields[substr($fname, 3)] = trim( $pg->fields[$x]->get_data() ); // strip off the 'pp_' prefix
                                }
                            }
                        }

                        $all_ok = 1;

                        // valid price
                        if( !isset($pp_price) || !is_numeric($pp_price) ){
                            $all_ok = 0;
                        }

                        // valid quantity
                        $quantity = trim( $_POST['qty'] );
                        if( $this->get_config('allow_decimal_qty') ){
                            if( !is_numeric($quantity) || !preg_match("/^[0-9.]+$/i", $quantity) || !($quantity>0) ){
                                $all_ok = 0;
                            }
                        }
                        else{
                            if( !$FUNCS->is_non_zero_natural($quantity) ){
                                $all_ok = 0;
                            }
                        }

                        if( $all_ok ){
                            $arr_sort_keys = array(); // used to sort items in the cart
                            $arr_display_attrs = array(); // an array of all selected variant options with values
                            $arr_sort_keys[] = $pg->page_name;
                            $arr_sort_keys[] = $pg->id;

                            //get the price modifiers, if any
                            if( isset($pp_options) ){
                                $arr_opts = $this->_parse_options( $pp_options );

                                if( count($arr_opts) ){
                                    for( $x=0; $x<count($arr_opts); $x++ ){
                                        $os = $_POST['os'.$x];
                                        $opt_name = $arr_opts[$x]['name'];
                                        $opt_values = $arr_opts[$x]['values'];

                                        // valid attributes
                                        if( $this->_is_option_text($arr_opts[$x]) ){ // textbox
                                            if( is_string($os) ){
                                                $os = trim( $os );
                                                if( strlen($os) ){
                                                    $arr_sort_keys[] = md5( $os );

                                                    // save selected attribute and value for latter display
                                                    $arr_display_attrs[$opt_name] = $FUNCS->excerpt( $FUNCS->cleanXSS($os, 0, 'none'), 200 );

                                                    // adjust price
                                                    $pp_price = $pp_price + $opt_values[0]['price'];
                                                }
                                            }
                                            else{
                                                $all_ok = 0;
                                                break;
                                            }
                                        }
                                        else{ // select list
                                            if( $FUNCS->is_natural($os) && ($os<count($opt_values)) ){
                                                $arr_sort_keys[] = $os;

                                                // save selected attribute and value for latter display
                                                $arr_display_attrs[$opt_name] = $opt_values[$os]['attr']; // e.g Color=>Black;

                                                // adjust price
                                                $pp_price = $pp_price + $opt_values[$os]['price'];
                                            }
                                            else{
                                                $all_ok = 0;
                                                break;
                                            }
                                        }
                                   }

                                }
                            }
                            // if all ok, add to cart
                            if( $all_ok ){
                                // create the sorting key - page_name + id + attributes
                                $sorting_key = $FUNCS->make_key( $arr_sort_keys );

                                // create a unique id for this item. Will be passed on for future actions on cart.
                                $unique_key = md5( $sorting_key );

                                // if item already exists in cart, update the original else add a new item.
                                if( isset($this->items[$sorting_key]) ){
                                    // update quantity
                                    $this->updated_rows[$sorting_key] = $this->items[$sorting_key]['quantity']; // save original quantity
                                    $this->items[$sorting_key]['quantity'] += $quantity;
                                }
                                else{
                                    // HOOK: cart_get_item_link
                                    $link = K_SITE_URL . $pg->get_page_view_link();
                                    $FUNCS->dispatch_event( 'cart_get_item_link', array($pg->tpl_name, &$link) );

                                    $this->items[$sorting_key] = array(
                                       'line_id' => $unique_key,
                                       'id' => $pg->id,
                                       'name' => $pg->page_name,
                                       'title' => $pg->page_title,
                                       'link' => $link,
                                       'price' => $pp_price,
                                       'quantity' => $quantity,
                                       'line_total' => 0,
                                       'skip_line_total' => 0, /* meant to be set by custom routine that calculates line_total itself */
                                       'options' => $arr_display_attrs,
                                       'requires_shipping' => ( $pp_requires_shipping ) ? 1 : 0,
                                    );

                                    // HOOK: cart_alter_custom_fields
                                    $FUNCS->dispatch_event( 'cart_alter_custom_fields', array(&$arr_custom_fields, &$pg, &$this) );

                                    // Add custom attributes if any
                                    foreach( $arr_custom_fields as $k=>$v ){
                                        $this->items[$sorting_key][$k]=$v;
                                    }
                                    $this->updated_rows[$sorting_key] = 0;

                                    // sort
                                    ksort( $this->items );
                                }
                                // finally persist in session
                                $this->current_action_success = 1;
                                $this->serialize();
                            }
                            else{
                               // report error?
                            }
                        }
                    }
                }
            }

            $this->redirect( 1 );

        }

        function update_items(){
            global $FUNCS;

            // Map the hash keys to the array keys of items
            $arr_modify = array();
            if( is_array($_POST['qty']) && count($_POST['qty']) ){
                foreach( $this->items as $key=>$item ){
                    if( (isset($_POST['qty'][$item['line_id']])) && ($item['quantity'] != $_POST['qty'][$item['line_id']]) ){ // if quantity modified..
                        $arr_modify[$key] = $_POST['qty'][$item['line_id']]; // key=>quantity
                    }
                }
            }
            $refresh = 0;
            foreach( $arr_modify as $key=>$quantity ){
                // first validate quantity
                $quantity = trim( $quantity );
                if( $quantity=='' ) $quantity=0;
                $all_ok = 1;
                if( $this->get_config('allow_decimal_qty') ){
                    if( !is_numeric($quantity) || !preg_match("/^[0-9.]+$/i", $quantity) ){
                       $all_ok = 0;
                    }
                }
                else{
                    if( !$FUNCS->is_natural($quantity) ){
                       $all_ok = 0;
                    }
                }

                if( $all_ok ){
                    if( $quantity==0 ){
                        // remove from cart
                        unset( $this->items[$key] );
                    }
                    else{
                        // update quantity
                        $this->updated_rows[$key] = $this->items[$key]['quantity']; // save original quantity
                        $this->items[$key]['quantity'] = $quantity;
                    }
                    $refresh = 1;
                }
            }

            if( $refresh ){
                $this->current_action_success = 1;
                $this->serialize();
            }

            $this->redirect( 1 );

        }

        function remove_item(){

            if( isset($_GET['line_id']) ){
                $line_id = $_GET['line_id'];
                foreach( $this->items as $key=>$item ){
                    if( $item['line_id']==$line_id ){
                        $key_to_remove = $key;
                        break;
                    }
                }
                if( $key_to_remove ){
                    unset( $this->items[$key_to_remove] );
                    $this->current_action_success = 1;
                    $this->serialize();
                }
            }

            $this->redirect( 1 );
        }

        function empty_cart(){
            $this->items = array();
            $this->current_action_success = 1;
            $this->serialize();
            $this->redirect( 1 );
        }

        function checkout(){
            $this->current_action_success = 1;
            $this->serialize();
            $this->redirect( 2 );
        }

        // Meant to be extended.
        function custom_action(){

        }

        // pre_calc, get_discount, get_taxes, get_shipping, retotal & post_calc can be subclassed to extend.
        function pre_calc(){
            return;
        }

        // Meat of the calculations
        function calc(){
            foreach( $this->items as $key=>$item ){
                if( !$this->items[$key]['skip_line_total'] ){ // if not already calculated in pre_calc
                    $this->items[$key]['line_total'] = $item['price'] * $item['quantity'];
                }
                $this->sub_total += $this->items[$key]['line_total'];
                $this->count_items += $item['quantity']; // will not be useful if decimal quantities allowed and unit of measure varies.
                if( $item['requires_shipping'] ){
                    $this->count_shippable_items += $item['quantity'];
                }
            }
            // get discount
            $this->discount = $this->get_discount();
            if( $this->discount > $this->sub_total ) $this->discount=$this->sub_total; // Discounts cannot be greater than the cart sub-total
            $this->sub_total_discounted = $this->sub_total - $this->discount;

            // get shipping & taxes
            $this->shipping = $this->get_shipping();
            $this->taxes = $this->get_taxes();
            $this->total = $this->sub_total_discounted + $this->taxes + $this->shipping;
        }

        function get_discount(){
            return 0;
        }

        function get_taxes(){
            return 0;
        }

        function get_shipping(){
            return 0;
        }

        function post_calc(){
            return;
        }

        function retotal(){
            // Give the extending class an opportunity to work on the data (usually price and quantity) before we begin calculations based on the data.
            // Why calling pre_calc and post_calc even when items array is empty?
            // Because, the routines might want to do something when the cart is empty.
            $this->pre_calc();

            // Begin calculations
            $this->count_items = 0;
            $this->count_shippable_items = 0;
            $this->sub_total = 0;
            $this->discount = 0;
            $this->sub_total_discounted = 0;
            $this->taxes = 0;
            $this->shipping = 0;
            $this->total = 0;

            if( count($this->items) ){
                $this->calc();
            }
            else{
                // Cart is empty. Remove custom vars too.
                $this->custom_vars = array();
            }

            // Post processing
            $this->post_calc();
        }

        function redirect( $tpl ){
            global $FUNCS, $DB;
            if( !($tpl==1 || $tpl==2) ) return; // if not cart or checkout

            ob_get_contents(); // not neccessary but just in case..
            ob_end_clean();
            $DB->commit( 1 ); // force commit, we are redirecting.

            // AJAX?
            if( $this->is_ajax() ){
                exit;
            }

            $location = '';
            // redirect specified?
            if( isset($_GET['redirect']) ){
                $location = $FUNCS->sanitize_url( trim($_GET['redirect']) ); // $_GET already comes urldecoded
                if( strpos($location, K_SITE_URL)!==0 ){ // we don't allow redirects external to our site
                    $location = '';
                }
            }

            if( !$location ){
                $location = $this->_get_template_link( $tpl );
            }

            // HTTP headers for no cache etc
            header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
            header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
            header( "Cache-Control: no-store, no-cache, must-revalidate" );
            header( "Cache-Control: post-check=0, pre-check=0", false );
            header( "Pragma: no-cache" );
            header( "Location: " . $location );
            exit;
        }

        function is_ajax(){
            if( (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            ||  (isset($_GET['ajax']) && ($_GET['ajax'] == '1')) ){
                return 1;
            }
            return 0;
        }

        function _get_template_link( $tpl ){
            global $FUNCS;

            if( $tpl==1 ){ // cart template
                if( !$this->link_cart_template ){
                    $tpl = $this->get_config('tpl_cart');
                    $link = K_SITE_URL . (( K_PRETTY_URLS ) ? $FUNCS->get_pretty_template_link( $tpl ) : $tpl);

                    // HOOK: cart_get_template_link
                    $FUNCS->dispatch_event( 'cart_get_template_link', array($tpl, &$link) );

                    $this->link_cart_template = $link;
                }
                return $this->link_cart_template;
            }
            elseif( $tpl==2 ){ // checkout template
                if( !$this->link_checkout_template ){
                    $tpl = $this->get_config('tpl_checkout');
                    $link = K_SITE_URL . (( K_PRETTY_URLS ) ? $FUNCS->get_pretty_template_link( $tpl ) : $tpl);

                    // HOOK: cart_get_template_link
                    $FUNCS->dispatch_event( 'cart_get_template_link', array($tpl, &$link) );

                    $this->link_checkout_template = $link;
                }
                return $this->link_checkout_template;
            }
        }

        function _parse_options( $str_po ){
            $str_po = trim( $str_po );
            $arr_options = array();
            if( $str_po ){
                preg_match_all("/(.+)\[(.*)](.*)$/m", $str_po, $matches, PREG_SET_ORDER);
                $limit = min( 7, count($matches) ); // only seven sets of product options supported
                for($y=0; $y<$limit; $y++) { // for each set of options e.g. Color[Red=0.5 | Black=-12.3  | Green=+2]
                    $option = array();
                    $val = $matches[$y];
                    $option['name'] = trim( $val[1] ); // Color
                    $option['values'] = array();
                    $option['modifier'] = trim( $val[3] ); // Extra info that could serve as modifier
                    $arr_attrs = array_map( "trim", explode('|', $val[2]) ); // Red=0.5 | Black=-12.3  | Green=+2
                    if( count($arr_attrs) ){
                        $x=0;
                        foreach( $arr_attrs as $attr ){ // Red=0.5
                            $attr = array_map( "trim", explode('=', $attr) );
                            if( $attr[0] && (is_numeric($attr[1]) || !$attr[1]) ){
                                $option['values'][$x]['attr'] = $attr[0];
                                $option['values'][$x]['price'] = ($attr[1]) ? round($attr[1], 2) : 0;
                                $x++;
                            }
                        }

                        if( count($option['values']) ){
                            $arr_options[] = $option;
                        }
                    }
                }
            }

            return $arr_options;
        }

        function _is_option_text( $opt ){
            return ( count($opt['values'])==1 && $opt['values'][0]['attr']=='*TEXT*' ) ? true : false;
        }

        function payment_gateway( $params ){ // Used by cms:pp_payment_gateway tag
            global $FUNCS, $KSESSION;

            extract( $FUNCS->get_named_vars(
                        array(
                              'shipping_address'=>'0', /* 0=address not required, 1=get address at PayPal site, 2=get address from session */
                              'calc_shipping_at_paypal'=>'0', /* to calculate shipping at PayPal, set this to 1 and do not set $this->shipping */
                              'logo'=>'',
                              'return_url'=>'',
                              'cancel_url'=>'',
                              'empty_cart'=>'1'
                              ),
                        $params)
                   );
            if( $shipping_address!=1 && $shipping_address!=2 ) $shipping_address=0;
            if( $calc_shipping_at_paypal!=1 ) $calc_shipping_at_paypal=0;
            $logo = trim( $logo );
            $return_url = trim( $return_url );
            $cancel_url = trim( $cancel_url );
            if( !$return_url ){ $return_url = K_SITE_URL; }
            if( !$cancel_url ){ $cancel_url = $return_url; }
            if( $empty_cart!=0 ) $empty_cart=1;

            // get to work
            $items = $this->items;
            if( is_array($items) ){

                // general info
                if( $this->get_config('paypal_use_sandbox') ){
                    $paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
                }
                else{
                    $paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
                }
                $qs  = "?cmd=_cart";
                $qs .= "&upload=1";
                $qs .= "&business=" . urlencode( $this->get_config('paypal_email') );
                $qs .= "&currency_code=" . urlencode( $this->get_config('currency') );
                $qs .= "&rm=1";
                $qs .= "&return=" . urlencode( $return_url );
                $qs .= "&cancel_return=" . urlencode( $cancel_url );
                if( $logo ){
                   $qs .= '&image_url='.urlencode( $logo );
                }
                if( $KSESSION->get_var('contact_email') ){
                    $qs .= "&email=" . urlencode( substr($KSESSION->get_var('contact_email'), 0, 127) );
                }

                // add items
                $item_count = 1;
                foreach( $items as $item ){
                    $qs .= "&item_number_" . $item_count . "=" . urlencode( $item['id'] );
                    $qs .= "&item_name_" . $item_count . "=" . urlencode( $item['title'] );
                    $qs .= "&amount_" . $item_count . "=" . urlencode( $item['price'] );
                    $qs .= "&quantity_" . $item_count . "=" . urlencode( $item['quantity'] );
                    if( !$calc_shipping_at_paypal ){
                        $qs .= "&shipping_" . $item_count . "= 0"; // So as to be exempt of merchant rates configured at PayPal (if any).
                    }
                    $options_count = 0;
                    foreach($item['options'] as $k=>$v){
                        $qs .= "&on" . $options_count . "_" . $item_count . "=" . urlencode( $k );
                        $qs .= "&os" . $options_count . "_" . $item_count . "=" . urlencode( $v );
                        $options_count++;
                    }
                    $item_count++;
                }

                // discount
                if( $this->discount ){ // On complete cart. Will override per item discounts, if any
                    $qs .= "&discount_amount_cart=" . urlencode( $this->discount );
                }

                // shipping
                if( $this->shipping ){ //and shipping amount is non-zero
                    $qs .= "&handling_cart=" . urlencode( $this->shipping );
                }

                // shipping address
                if( $shipping_address==2 ){
                    // pass captured address to paypal
                    $qs .= "&first_name=" . urlencode( substr($KSESSION->get_var('shipping_first_name'), 0, 25) );
                    $qs .= "&last_name=" . urlencode( substr($KSESSION->get_var('shipping_last_name'), 0, 25) );
                    $qs .= "&address1=" . urlencode( substr($KSESSION->get_var('shipping_address1'), 0, 100) );
                    $qs .= "&address2=" . urlencode( substr($KSESSION->get_var('shipping_address2'), 0, 100) ); //optional
                    $qs .= "&city=" . urlencode( substr($KSESSION->get_var('shipping_city'), 0, 40) );
                    $qs .= "&state=" . urlencode( $KSESSION->get_var('shipping_state_code') ); // US valid values: https://www.x.com/developers/paypal/documentation-tools/api/stateandprovincecodes
                    $qs .= "&country=" . urlencode( $KSESSION->get_var('shipping_country_code') ); // Valid values: http://www.paypalobjects.com/en_US/ebook/PP_NVPAPI_DeveloperGuide/countrycodes_new.html
                    $qs .= "&zip=" . urlencode( substr($KSESSION->get_var('shipping_zip'), 0, 32) );
                }
                elseif( $shipping_address==1 ){
                    $qs .= "&no_shipping=2"; // make it mandatory to enter shipping address at paypal
                }
                else{
                    $qs .= "&no_shipping=1";
                }

                // taxes
                if( $this->taxes ){ // On complete cart. Will override per item taxes, if any
                    $qs .= "&tax_cart=" . urlencode( $this->taxes );
                }

                if( $empty_cart ){
                    $this->items = array();
                    $this->serialize();
                }

                // go!
                header( 'Location: ' . $paypal_url . $qs );
            }

        }

        ///////////////////////////////////////////// tag handlers//////////////
        static function product_form_handler( $params, $node ){
           global $CTX, $FUNCS, $CART;

            // generates equivalent code of following -
            // for 'pp_product_form':
            //<form action="<cms:pp_add_item_link />&redirect=<cms:show k_page_link />" method="post" accept-charset="utf-8">
            //  <input type="hidden" name="pp_id" value="<cms:show k_page_id />">
            //  ..
            //</form>
            //
            // for 'pp_cart_form':

            //<form action="<cms:pp_update_item_link />" method="post" accept-charset="utf-8">
            //..
            //</form>

            $html = '<form action="' . $CART->get_link( 1, 'kcart_action=' . ($action=($node->name=='pp_product_form') ? '1' : '2') );
            $redirect = '';
            $extra = '';
            for( $x=0; $x<count($params); $x++ ){
                $attr = strtolower(trim($params[$x]['lhs']));
                if( $attr=='action' || $attr=='method' ){
                    continue;
                }
                elseif( $attr=='redirect' ){
                    $redirect = trim($params[$x]['rhs']);
                    continue;
                }
                $extra .= ' '.$params[$x]['lhs'] . '="' . $params[$x]['rhs'] . '"';
            }
            if( $redirect ){
                $html .= '&redirect=' . $redirect;
            }
            $html .= '" method="post" accept-charset="'.K_CHARSET.'"';
            if( $extra ){
                $html .= $extra;
            }
            $html .= ">\r\n"; // end of opening form tag

            // add the hidden inputs
            if( $node->name=='pp_product_form' ){
                $page_id = $CTX->get( 'k_page_id' );
                $html .= '<input type="hidden" name="pp_id" value="' . $page_id . '" style="display:none;">' . "\r\n";
            }

            // get form's contents
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            // close form and return
            $html .= '</form>';
            return $html;
        }

        static function product_options_handler( $params, $node ){
           global $CTX, $FUNCS, $CART;

            extract( $FUNCS->get_named_vars(
                        array(
                                'count_only'=>'0',
                              ),
                        $params)
                   );
            $count_only = ( $count_only==1 ) ? 1 : 0;

            // Convert text from editable region of containg page to array of options
            // e.g. Color[Red=0.5 | Black=-12.3  | Green=+2]
            $arr_opts = $CART->_parse_options( $CTX->get('pp_options') );

            // Return if only count asked for
            if( $count_only ) return count( $arr_opts );

            if( count($arr_opts) ){
                for($y=0; $y<count($arr_opts); $y++){ // create a dropdown for each set of options
                    $opt = $arr_opts[$y];

                    // Check if a drop-down or a textbox is required to handle the variations
                    if( $CART->_is_option_text($opt) ){
                        $CTX->set( 'option_type', 'text' );
                        $price = $opt['values'][0]['price'];
                        $CTX->set( 'option_price', abs( $price ) );
                        $CTX->set( 'option_price_sign', ( $price>=0 ) ? '+' : '-' );
                    }
                    else{
                        $CTX->set( 'option_type', 'list' );
                        $CTX->set( 'option_price', '' );
                        $CTX->set( 'option_price_sign', '' );
                    }

                    // Make the options array available for the child-tag 'cms:pp_option_values'
                    $CTX->set_object( 'selected_options', $opt );
                    $CTX->set( 'k_count', $y );
                    $CTX->set( 'option_name', $opt['name'] ); // e.g. Color
                    $CTX->set( 'option_modifier', $opt['modifier'] ); // Extra info
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
            }

           return $html;

        }

        static function product_option_values_handler( $params, $node ){
            global $CTX, $FUNCS, $CART;

            // get the option object supplied by 'cms:pp_product_options' tag
            $opt = &$CTX->get_object( 'selected_options', 'pp_product_options' );
            $y = $CTX->get( 'k_count' ); // count index of parent 'pp_product_options' tag
            if( is_array($opt) && is_array($opt['values']) ){
                $html = '';
                if( !count($node->children) ){ // used as a self-closing tag - return HTML of options dropdown
                    if( $CART->_is_option_text($opt) ){ // textbox
                        $html .= '<input type="text" name="os'.$y.'" maxlength="200">';
                    }
                    else{ // list
                        $sep = '';
                        for( $x=0; $x<count($opt['values']); $x++ ){
                            $value = $opt['values'][$x];
                            $str_option = $value['attr'];
                            $price = $value['price'];
                            if( ($price!=0) ){
                                $str_option .= ($price>0) ? '  [+'.$CART->get_config('currency_symbol') : ' [-'.$CART->get_config('currency_symbol');
                                $str_option .= abs( $price ) .']';
                            }

                            if( $opt['modifier']=='*' || $opt['modifier']=='**' ){ // radio buttons ('**' specifies all buttons in the same row)
                                if( $opt['modifier']=='*' ) $html .= $sep;
                                $html .= '<label class="radio-label">';
                                $html .= '<input type="radio" name="os'.$y.'" value="'.$x.'"';
                                if( $x==0 ) $html .= ' checked="true"';
                                $html .= '>'.$str_option;
                                $html .= '</label>';
                            }
                            else{ // dropdown
                                $html .= '<option value="'.$x.'">'.$str_option.'</option>';
                            }
                            $sep = '<br>';
                        }

                        if( !($opt['modifier']=='*' || $opt['modifier']=='**') ){
                            $html = '<select name="os'.$y.'">' . $html . '</select>';
                        }
                    }
                    $html = '<input type="hidden" name="on'.$y.'" value="'.$opt['name'].'" style="display:none;">' . $html;

                }
                else{
                    for( $x=0; $x<count($opt['values']); $x++ ){
                        $value = $opt['values'][$x];
                        $str_option = $value['attr'];
                        $price = $value['price'];

                        if( $CART->_is_option_text($opt) ){ // textbox
                            // type textbox really shouldn't be called with 'cms:product_option_values' as it has no options
                            // but in case this is done, let us handle it
                            $CTX->set( 'k_count', $y ); // set k_count of parent (i.e. product_options)
                            $CTX->set( 'option_val', '' ); // don't make the '*TEXT*' available
                        }
                        else{
                            $CTX->set( 'k_count', $x ); // set k_count of its own
                            $CTX->set( 'option_val', $str_option );
                        }
                        $CTX->set( 'option_price', abs( $price ) );
                        $CTX->set( 'option_price_sign', ( $price>=0 ) ? '+' : '-' );
                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                    }
                }
            }

            return $html;
        }

        static function cart_form_handler( $params, $node ){
           global $CART;

           // Delegate to 'cms:pp_product_form'
           return $CART->product_form_handler( $params, $node );
        }

        static function cart_items_handler( $params, $node ){
           global $CTX, $FUNCS, $CART;
           $arr_canonical_attrs = array( 'line_id', 'id', 'name', 'title', 'link', 'price', 'quantity', 'line_total', 'options', 'requires_shipping' );

           $items = $CART->items;
           if( is_array($items) ){
                foreach( $items as $item ){
                    $vars = array();

                    // Canonical attributes of each line-item
                    foreach( $arr_canonical_attrs as $attr ){
                        if( $attr!='options' ){
                            $vars[$attr] = $item[$attr];
                        }
                        else{
                            // Make the selected options array available for the child-tag 'cms:pp_selected_options'
                            $CTX->set_object( 'selected_options', $item['options'] );
                        }
                    }

                    // Custom attributes if any
                    foreach( $item as $k=>$v ){
                        if( !in_array($k, $arr_canonical_attrs) ){
                            $vars[$k] = $v;
                        }
                    }

                    $CTX->set_all( $vars );
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
           }
           return $html;
        }

        // To be used as a child of 'cms:pp_cart_items' tag. Iterates through the options selected for the current line-item
        static function selected_options_handler( $params, $node ){
            global $CTX, $FUNCS;
            extract( $FUNCS->get_named_vars(
                        array(
                                'separator'=>', ',
                                'startcount'=>'0',
                                'count_only'=>'0',
                              ),
                        $params)
                   );

            if( !$separator ) $separator = ', ';
            $startcount = is_numeric( $startcount ) ? intval( $startcount ) : 0;
            $count_only = ( $count_only==1 ) ? 1 : 0;

            // get the selected options array object supplied by 'cms:pp_cart_items' tag
            $arr_options = &$CTX->get_object( 'selected_options', 'pp_cart_items' );
            if( is_array($arr_options) ){

                // Return if only count asked for
                if( $count_only ) return count( $arr_options );

                if( !count($node->children) ){ // used as a self-closing tag - return a concatenated string of the options
                    $str_options = '';
                    $sep = '';
                    foreach( $arr_options as $k=>$v ){
                        $str_options .= $sep . $k .': '. $v;
                        $sep = $separator;
                    }
                    $html = $str_options;
                }
                else{
                    $x = $startcount;
                    foreach( $arr_options as $k=>$v ){
                        $CTX->set( 'k_count', $x );
                        $CTX->set( 'option_name', $k );
                        $CTX->set( 'option_value', $v );
                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                        $x++;
                    }
                }
            }
            return $html;

        }

        // all generic variables
        static function cart_vars_handler( $params, $node ){
            global $CTX, $FUNCS, $CART;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $html = '';
            switch( $node->name ){
                case 'pp_count_items':
                    $html = $CART->count_items;
                    break;
                case 'pp_count_unique_items':
                    $html = count( $CART->items );
                    break;
                case 'pp_count_shippable_items':
                    $html = $CART->count_shippable_items;
                    break;
                case 'pp_sub_total':
                    $html = $CART->sub_total;
                    break;
                case 'pp_discount':
                    $html = $CART->discount;
                    break;
                case 'pp_sub_total_discounted':
                    $html = $CART->sub_total_discounted;
                    break;
                case 'pp_taxes':
                    $html = $CART->taxes;
                    break;
                case 'pp_shipping':
                    $html = $CART->shipping;
                    break;
                case 'pp_total':
                    $html = $CART->total;
                    break;
                case 'pp_custom_var':
                    extract( $FUNCS->get_named_vars(
                            array(
                                'name'=>''
                            ),
                            $params)
                    );
                    $html = $CART->get_custom_var( $name );
                    break;
                case 'pp_currency_symbol':
                    $html = $CART->get_config('currency_symbol');
                    break;
                case 'pp_refresh_cart':
                    $CART->serialize();
                    break;
                case 'pp_config':
                    extract( $FUNCS->get_named_vars(
                            array(
                                'name'=>''
                            ),
                            $params)
                    );
                    $html = $CART->get_config($name);
                    break;
                case 'pp_cart_link':
                    $html = $CART->_get_template_link( 1 );
                    break;
                case 'pp_add_item_link':
                    $html = $CART->get_link( 1, 'kcart_action=1' );
                    break;
                case 'pp_update_item_link':
                    $html = $CART->get_link( 1, 'kcart_action=2' );
                    break;
                case 'pp_remove_item_link':
                    $html = $CART->get_link( 1, 'kcart_action=3&amp;line_id=' . $CTX->get('line_id') );
                    break;
                case 'pp_empty_cart_link':
                    $html = $CART->get_link( 1, 'kcart_action=4' );
                    break;
                case 'pp_checkout_link':
                    $html = $CART->get_link( 1, 'kcart_action=5' );
                    break;
                case 'pp_empty_cart':
                    $CART->items = array();
                    $CART->serialize();
                    break;
            }

            return $html;
        }

        function get_link( $tpl, $querystring ){
            $link = $this->_get_template_link( $tpl );
            if( $link ){
                $querystring = trim( $querystring );
                if( $querystring ){
                    $sep = ( strpos($link, '?')===false ) ? '?' : '&';
                    $link = $link . $sep . $querystring;
                }
            }
            return $link;
        }

        static function gateway_handler( $params, $node ){
            global $CART;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            return $CART->payment_gateway( $params );
        }

    } // end class

    if( file_exists(K_ADDONS_DIR.'cart/cart_ex.php') ){
        require_once( K_ADDONS_DIR.'cart/cart_ex.php' );
        $CART = new KCartEx();
    }
    else{
        $CART = new KCart();
    }
