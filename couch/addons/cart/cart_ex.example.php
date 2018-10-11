<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KCartEx extends KCart{

        // Can modify item price and quantity here to affect the calculations that follow.
        // We'll manipulate price here to code up 'Quantity based pricing'
        function pre_calc(){

            // Quantity based pricing (also known as "tiered pricing"):
            // This is when the product's base price varies based on the quantity purchased (useful for bulk purchases).
            // In Couch, it can be done by defining an editable region named 'pp_discount_scale'.
            // Individual products can then be set with a sliding scale for price-reductions (discounts) in the format '[ 5=10 | 10=15 ]'
            // where the scale above stands for 'reduce product's base-price by $10 if customer buys more than 5 units of it, by $15 if buys more than 10'.
            //
            // To setup price-reductions (discounts) as a percentage of the product's base price (instead of fixed values as in the example above),
            // a '%' can be added to the scale e.g. '[ 5=10 | 10=15 ]%'
            // where the scale now becomes 'reduce product's base-price by 10% if customer buys more than 5 units of it, by 15% if buys more than 10'
            //
            // The following routine looks for items in cart that have the above-mentioned scale set.
            // For all such items, the applicable discount is calculated, the base-price reduced by the calculated discount and
            // the original base-price and the discount saved as line variables (can be accessed on the front-end e.g. '<cms:show line_discount />').
            //
            // We begin by checking if a new item has been added to the cart or the the quantity of any existing item updated
            if( count($this->updated_rows) ){
                foreach($this->updated_rows as $key=>$orig_qty){
                    // A newly added item, as opposed to one being updated, will have its original quantity as 0. Is it so?
                    if( !$orig_qty ){
                        // .. if yes, good time to add custom variables (i.e. those not found by default) to the new cart item being added.
                        // We'll create two: 'orig_price' and 'line_discount'
                        $this->items[$key]['orig_price'] = $this->items[$key]['price']; // Save original price because we'll be modifying it
                        $this->items[$key]['line_discount'] = 0; // This will end up holding the price-reduction applied further down by this routine
                    }

                    $item_count = $this->items[$key]['quantity'];
                    $item_price = $this->items[$key]['orig_price'];
                    $discount_scale = $this->items[$key]['discount_scale']; // e.g. [ 5=10 | 10=15 ]
                    if( strlen($discount_scale) ){
                        $rates = $this->_calc_line_discount( $item_price, $item_count, $discount_scale );
                        $this->items[$key]['price'] = $rates['item_discounted_price']; // set the discounted price as the item's base-price

                        // If a graduated pricing scale used, the item price set above can have rounding discrepencies.
                        // We therefore set the line_total ourselves..
                        $this->items[$key]['line_total'] = $rates['line_total'];
                        $this->items[$key]['skip_line_total'] = 1; // ..and signal to cart not to recalulate it again.

                        $this->items[$key]['line_discount'] = $rates['line_discount']; // Finally save the discount value
                    }
                }
            }
        }

        function get_discount(){
            global $KSESSION;

            $discount = 0;
            $items = $this->count_items;
            if( !$items ) return 0;

            // First check if a coupon needs to be applied
            if( $KSESSION->get_var('coupon_found') && $this->sub_total > $KSESSION->get_var('coupon_min_amount') ){
                // apply discount converting to absolute value if in percentage
                $discount += ( $KSESSION->get_var('coupon_type')=='0' ) ? round( ($KSESSION->get_var('coupon_discount')/100)*$this->sub_total, 2 ) : $KSESSION->get_var('coupon_discount');
            }

            // Next calculate cart level discounts set in config file.
            $scale_order_total = $this->get_config( 'discount_by_order_total' );
            if( strlen($scale_order_total) ){
                $discount += $this->_calc_charge( 0 /*cost*/, $this->sub_total, $scale_order_total );
            }

            $scale_quantity_ordered = $this->get_config( 'discount_by_quantity_ordered' );
            if( strlen($scale_quantity_ordered) ){
                $discount += $this->_calc_charge( 1 /*count*/, $items, $scale_quantity_ordered );
            }

            return round( $discount, 2);
        }

        function get_shipping(){
            global $KSESSION;

            $cost = 0;
            $items = $this->count_shippable_items;
            if( !$items ) return 0; // No item in the cart that needs shipping

            // First check if a discount coupon provides free shipping
            if( $KSESSION->get_var('coupon_found') &&
                $this->sub_total > $KSESSION->get_var('coupon_min_amount') &&
                $KSESSION->get_var('coupon_free_shipping') ){

                    $KSESSION->set_var( 'free_shipping_applied', 1 );
                    return 0;
            }
            else{
                $KSESSION->set_var( 'free_shipping_applied', 0 );
            }

            // Next calculate cart level shipping charges set in config file.
            $flat_rate_per_order = $this->get_config( 'shipping_flat_rate_per_order' );
            if( is_numeric($flat_rate_per_order) && preg_match("/^[0-9.]+$/i", $flat_rate_per_order) && $flat_rate_per_order>0 ){
                $cost += $flat_rate_per_order;
            }

            $flat_rate_per_item = $this->get_config( 'shipping_flat_rate_per_item' );
            if( is_numeric($flat_rate_per_item) && preg_match("/^[0-9.]+$/i", $flat_rate_per_item) && $flat_rate_per_item>0 ){
                $cost += ( $items * $flat_rate_per_item );
            }

            $scale_order_total = $this->get_config( 'shipping_by_order_total' );
            if( strlen($scale_order_total) ){
                $cost += $this->_calc_charge( 0 /*cost*/, $this->sub_total_discounted, $scale_order_total );
            }

            $scale_quantity_ordered = $this->get_config( 'shipping_by_quantity_ordered' );
            if( strlen($scale_quantity_ordered) ){
                $cost += $this->_calc_charge( 1 /*count*/, $items, $scale_quantity_ordered );
            }

            // Finally, loop through items in cart to see if any shippable item has individual shipping info attached to it
            // (which can be done by defining an editable region named 'pp_shipping_scale')
            foreach( $this->items as $key=>$item ){
                $scale_line_quantity = trim( $item['shipping_scale'] );
                if( $item['requires_shipping'] && strlen($scale_line_quantity) ){
                    $cost += $this->_calc_charge( 1 /*count*/, $item['quantity'], $scale_line_quantity );
                }
            }

            return round( $cost, 2);

        }

        function get_taxes(){
            $tax = 0;

            // Get values from config
            $tax_percent = $this->get_config( 'tax_percent' );
            $tax_excludes_shipping = $this->get_config( 'tax_excludes_shipping' );
            if( !($tax_excludes_shipping==0 || $tax_excludes_shipping==1) ) $tax_excludes_shipping = 0;

            $taxable_amount = $this->sub_total_discounted;
            if( !$tax_excludes_shipping ) $taxable_amount += $this->shipping;
            if( is_numeric($tax_percent) && preg_match("/^[0-9.]+$/i", $tax_percent) && $tax_percent>0 ){
                $tax = round( ($tax_percent/100)*$taxable_amount, 2 );
            }
            return $tax;
        }

        // Can set cart wide custom variables here
        function post_calc(){

            // We set flash message (i.e. that lasts for only one page-view) if action on cart successful
            global $KSESSION;

            $flash_message = '';
            if( $this->current_action_success ){
                switch( $this->current_action ){
                    case PP_ACTION_ADD_ITEM:
                        $flash_message = '<p class="success">' . $this->get_config( 'msg_add_success' ) . '</p>';
                        break;
                    case PP_ACTION_UPDATE_ITEMS:
                        $flash_message = '<p class="notice">' . $this->get_config( 'msg_update_success' ) . '</p>';
                        break;
                    case PP_ACTION_REMOVE_ITEM:
                        $flash_message = '<p class="error">' . $this->get_config( 'msg_remove_success' ) . '</p>';
                        break;
                }
            }

            $KSESSION->set_flash( 'cart_flash_msg', $flash_message );

        }


        /////// Helper Functions (do not edit below this!) //////////////////////////////////////////////

        // A note about pricing scale.
        // Pricing scale is a string of the following format:
        // [ 0=2.50 | 5=2.75 | 10=3.00 ]
        //
        // It is a 'from greater than' scale so in the example above
        // '0=2.50' means any key greater than '0' will have a value of '2.50' and
        // '5=2.75' means any key greater than '5' will have a value of '2.75' and
        // '10=3.00' means any key greater than '10' will have a value of '3.00'
        //
        // The key could represent anything - e.g. item count, item value, item weight, cart total, cart weight etc.
        // The value could represent anything e.g. price, discount, shipping charge, tax etc.
        //
        // For countable items the example scale above would mean
        // 1 units to 5 units = 2.50
        // 6 units to 10 units = 2.75 and
        // 11 units onwards = 3.00
        //
        // ..while for weights or amounts it would mean
        // 0.1 units to 5 units = 2.50
        // 5.1 units to 10 units = 2.75 and
        // 10.1 units onwards = 3.00
        //
        // If instead of single brackets, double brackets are used the scale becomes a 'graduated' scale.
        // Using the example above, if the scale was a simple one (single bracket) where the key represented item count and value represented price per item,
        // a key of '9' will get a value of '2.75' per item.
        //
        // If the same scale is converted to a 'graduated' scale by using double-quotes
        // a key of '9' will get a value of '2.50' for the first 5 items and
        // a value of '2.75' for the remaining 4 items.
        //
        // If the string is followed by an optional '%' sign, it would signify that the values are percentages and not absolute.
        //
        // The following two utility functions '_parse_scale' and '_get_brackets' can be used to work with the pricing scales.


        // Function _parse_scale:
        // Given a string representing a pricing scale e.g.
        // [ 2=2.50 | 4=2.75 | 8=3.00 ]
        // [ 2=2.50 | 4=2.75 | 8=3.00 ]%
        // [[ 2=2.50 | 4=2.75 | 8=3.00 ]]
        // [[ 2=2.50 | 4=2.75 | 8=3.00 ]]%
        // (where the double square brackets suggest 'graduated' scale and the % means the scale is percentage based)
        // the following function returns an array like -
        //
        //    array
        //        'is_graduated' => 1
        //        'is_percent' => 1
        //        'brackets' =>
        //          array
        //            2 => 2.5
        //            4 => 2.75
        //            8 => 3
        //
        // - that can be used by other pricing functions in cart_ex.
        // An empty array is returned if the string is empty or malformed.
        //
        function _parse_scale( $str_pricing_scale ){

            $scale = array();

            $pattern = '/\[\[?([^\]]*)\](\]?)\s*(%?)/';
            if( preg_match($pattern, $str_pricing_scale, $matches) ){ //e.g. [[ 2=2.50 | 4=2.75 | 8=3.00 ]]%
                $str_pricing_scale = $matches[1];
                $is_graduated = ( $matches[2] ) ? 1 : 0;
                $is_percent = ( $matches[3] ) ? 1 : 0;

                // Process the scale further
                $arr_brackets = array_map( "trim", explode('|', $str_pricing_scale) ); // 2=2.50 | 4=2.75 | 8=3.00
                if( count($arr_brackets) ){
                    $tmp = array();
                    foreach( $arr_brackets as $bracket ){ // 2=2.50
                        $bracket = array_map( "trim", explode('=', $bracket) );
                        if( count($bracket)==2 &&
                           (is_numeric($bracket[0]) && preg_match("/^[0-9.]+$/i", $bracket[0])) &&
                           (is_numeric($bracket[1]) && preg_match("/^[0-9.]+$/i", $bracket[1])) ){
                                $tmp[$bracket[0]] = round($bracket[1], 2); // Commercial rounding
                        }
                    }

                    if( count($tmp) ){
                        ksort( $tmp, SORT_NUMERIC );
                        $scale['is_graduated'] = $is_graduated;
                        $scale['is_percent'] = $is_percent;
                        $scale['brackets'] = $tmp;
                    }
                }
            }

            return $scale;
        }

        // Function _get_brackets:
        // Given a pricing scale (in the string format accepted by _parse_scale above or a pre-parsed array)
        // and a target number (e.g. number of items, value, weight etc.)
        // this function returns an array of all brackets from the scale that apply to the target.
        // If the scale is non-graduated the array will atmost have only one bracket.
        // For graduated scale, potentially more than one brackets can be returned.
        // If no suitable bracketes found, an empty array is returned.
        //
        // e.g. a target of '2.6' and a scale of '[ 0=10 | 0.5=20 | 1.5=30 | 4=40 ]' returns -
        //array
        //  0 =>
        //    array
        //      'range' => 2.6
        //      'value' => 30
        //
        // - which signifies that a value of '30' (from the third bracket of the scale) applies to the entire range of target i.e. '2.6'.
        //
        // With the same scale converted to a 'graduated' scale
        // a target of '2.6' and a scale of '[[ 0=10 | 0.5=20 | 1.5=30 | 4=40 ]]' returns -
        //array
        //  0 =>
        //    array
        //      'range' => float 0.5
        //      'value' => float 10
        //  1 =>
        //    array
        //      'range' => float 1
        //      'value' => float 20
        //  2 =>
        //    array
        //      'range' => float 1.1
        //      'value' => float 30
        //
        // which signifies that
        // the first '0.5' (0 to 0.5) of '2.6' gets the first bracket's value of '10'
        // the next '1' (0.5 to 1.5) of '2.6' gets the second bracket's value of '20'
        // while the remaining '1.1' of '2.6' gets the third bracket's value of '30'
        //
        function _get_brackets( $target, $scale ){

            $arr_brackets = array();

            // Parse if the scale is still in string form
            if( !is_array($scale) ){
                $scale = $this->_parse_scale( $scale );
            }

            if( count($scale) ){
                if( !$scale['is_graduated'] ){ // find the single bracket that applies
                    $last_bracket_value = 0;
                    foreach( $scale['brackets'] as $bracket_from => $bracket_value ){
                        if( $target > $bracket_from ){
                            $last_bracket_value = $bracket_value;
                        }
                    }
                    $arr_brackets[] = array( 'range'=>$target, 'value'=>$last_bracket_value );
                }
                else{
                    // If scale is graduated, all the brackets leading to (and including) the last one will apply
                    $last_bracket_from = 0;
                    $last_bracket_value = 0;
                    foreach( $scale['brackets'] as $bracket_from => $bracket_value ){
                        if( $target > $bracket_from ){
                            if( $bracket_from > $last_bracket_from ){
                                $range = $bracket_from - $last_bracket_from;
                                $arr_brackets[] = array( 'range'=>$range, 'value'=>$last_bracket_value );
                            }
                            $last_bracket_from = $bracket_from;
                            $last_bracket_value = $bracket_value;
                        }
                    }
                    if( $target > $last_bracket_from ){
                        $range = $target - $last_bracket_from;
                        $arr_brackets[] = array( 'range'=>$range, 'value'=>$last_bracket_value );
                    }
                }
            }

            // return all applicable brackets
            return $arr_brackets;
        }

        function _calc_charge( $basis, $value, $scale ){
            if( !($basis=='0' /*cost*/ || $basis=='1' /*count*/) ) return 0;
            $charge = 0;

            // Parse the string representation of the shipping scale
            $scale = $this->_parse_scale( $scale );

            // Get the applicable bracket(s) from the scale for $value
            // ($value is usually cart's 'sub_total_discounted' when the basis is 'cost'
            // and 'count_shippable_items' when the basis is 'count')
            $arr_brackets = $this->_get_brackets( $value, $scale );
            if( count($arr_brackets) ){
                foreach( $arr_brackets as $bracket ){
                    if( $basis=='0' ){ // basis is cost
                        // convert to absolute value if in percentage
                        $charge += ( $scale['is_percent'] ) ? round( ($bracket['value']/100)*$bracket['range'], 2 ) : $bracket['value'];
                    }
                    else{ //basis is count so percentage does not make sense
                        $charge += $bracket['value'];
                    }
                }
            }

            return $charge;
        }

        // Given a pricing scale (in the format accepted by _parse_scale above),
        // an item's unit price and the number of items,
        // this function returns an array of calculated discounts.
        // e.g. $item_price=10, $item_count=12, $pricing_scale='[ 5=1 | 10=2 ]' returns
        //    array
        //        'item_discount' => 2 /* item price reduced by this value */
        //        'item_discounted_price' => 8 /* the new (reduced) item price */
        //        'line_discount' => 24 /* total discount given to the line i.e. 'item_count' (number of items in the line) multiplied by 'item_discount'*/
        //        'line_total' => 96 /* new total of the line i.e. 'item_count' (number of items in the line) multiplied by 'item_discounted_price' */
        //
        function _calc_line_discount( $item_price, $item_count, $pricing_scale ){

            $item_discount = 0;
            $item_discounted_price = $item_price;

            // Parse the string representation of the pricing scale
            $scale = $this->_parse_scale( $pricing_scale );

            // Get the applicable bracket(s) from the scale for item_count
            $arr_brackets = $this->_get_brackets( $item_count, $scale );

            if( count($arr_brackets) ){
                if( !$scale['is_graduated'] ){
                    $bracket = $arr_brackets[0];  // only a single discount bracket applies
                    $discount = ( $scale['is_percent'] ) ? round( ($bracket['value']/100)*$item_price, 2 ) : $bracket['value']; //convert to absolute value if in percentage

                    // calculate the required figures
                    $item_discount = $discount;
                    $item_discounted_price = $item_price - $item_discount;
                    $total_price = $item_discounted_price * $item_count;
                    $total_discount = $item_discount * $item_count;
                }
                else{
                    // multiple brackets can apply with graduated scale
                    $total_price = 0;
                    $total_discount = 0;
                    foreach( $arr_brackets as $bracket ){
                        $discount = ( $scale['is_percent'] ) ? round( ($bracket['value']/100)*$item_price, 2 ) : $bracket['value']; //convert to absolute value if in percentage
                        $total_price += $bracket['range'] * ($item_price - $discount);
                        $total_discount += $bracket['range'] * $discount;
                    }

                    // Finding item_discounted_price and item_discount using graduated scale is not straightforward.
                    // We'll have to find the total of all the items and then divide that with the number of items to find a unit value.
                    // This can result in some rounding discrepencies.
                    if( $total_price ) $item_discounted_price = round( $total_price / $item_count, 2 );
                    if( $total_discount ) $item_discount = round( $total_discount / $item_count, 2);
                }
            }
            else{
                $total_price = $item_price * $item_count;
                $total_discount = 0;
            }

            // return calulated values
            return array(
                'item_discount' => $item_discount,
                'item_discounted_price' => $item_discounted_price,
                'line_discount' => $total_discount,
                'line_total' => $total_price
            );

        }

    }
