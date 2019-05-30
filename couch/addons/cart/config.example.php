<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // /////////////////////////////////// Core config values ////////////////////////////////////////////
    $pp['tpl_cart'] = 'cart.php';
    $pp['tpl_checkout'] = 'checkout.php';
    $pp['currency'] = 'USD';
    $pp['currency_symbol'] = '$';
    $pp['allow_decimal_qty'] = 0;
    $pp['gateway'] = 'payeezy'; // gateway name, default 'paypal'. Possible values: 'paypal', 'payeezy'

    ////////////////////////////////////// PayPal config values /////////////////////////////////////////
    // used only for PayPal gateway
    $pp['paypal_use_sandbox'] = 0;
    $pp['paypal_email'] = '';

    ////////////////////////////////////// Payeezy config values ////////////////////////////////////////
    // used only for payeezy gateway
    $pp['payeezy_trans_key'] = 'my_trans_key';
    $pp['payeezy_login'] = 'WSP-123456789'; // the payment page id from Payeezy Gateway Payment Pages interface
    $pp['payeezy_gateway_id'] = ''; // If the payment page is set up to use all terminals, this value must be given to specify the desired terminal
/*
 Instructions for Payeezy Gateway
*/
// Set $pp['gateway'] in 'Core config values' above to 'payeezy'
// Set 'payeezy_trans_id' and 'payeezy_login' in the Payeezy config values section above
// Set 'payeezy_gateway_id' to the correct gateway id (only necessary if you have set up your payment page to use all terminals)

// When using payeezy gateway, you must use a 'checkout.php' page. It is not optional, as it is for PayPal gateway
// This is because checkout.php needs to calculate a security hash that is submitted, via POST, to the payeezy gateway. This authenticates
// the transaction AND prevents users from altering the form values (specifcally the total amount to charge).
//
// For Payeezy gateway, the same 'pp_payment_gateway' cart tag is used. This tag will be replaced by the form with the hidden variables
//  needed by Payeezy NOTE THAT THEN FORM TAGS ARE ALSO GENERATED. This differs from the behavior when used for PayPal gateway.
// The generated html includes, or course, the submit button.  If you want to supply your own button html, pass that as a parameter (see below).
/*
 The possible parameters for couch cart tag pp_payment_gateway when using Payeezy gateway are:
*/
// shipping_address: 0= address not required, 1=get address at Payeezy site, 2=get address from session
// relay_response: If TRUE (case-sensitive), Payeezy posts info to merchant's server, then displays html response to customer. You can leave empty for FALSE.
// button_html: html to display submit button. The default is: <div class="check-box"><input name="payeezy-submit" class="button checkout-button"
//     type="submit" value="Continue to Payment" /></div>
// empty_cart: same as when using PayPal gateway

    /////////////////////////////////////// Custom config values ////////////////////////////////////////
    $pp['tpl_ajax_cart'] = 'cart-modal.php';
    $pp['tpl_products'] = 'index.php';
    $pp['tpl_coupons'] = 'coupons.php';
    $pp['msg_add_success'] = 'Product successfully added to your cart.';
    $pp['msg_update_success'] = 'Product quantity successfully updated.';
    $pp['msg_remove_success'] = 'Product successfully removed from your cart.';

    /*
        Shipping calculations
    */
    // Flat rate per order:
    // Set the option below if you want to specify a shipping cost for each order, no matter how many items it contains.
    // For example, if you charge $5 for each order (that is, if John Doe places an order for 5 books, whereas Jane Doe places an order
    // for one book, both orders are charged $5 for shipping) set it to '5'.
    $pp['shipping_flat_rate_per_order'] = '0';

    // Flat rate per item:
    // Set the option below if you want to specify a shipping cost for each item, no matter how many are included in an order.
    // For example, if you charge $1 for each item in the order (that is, if a customer orders ten books, the shipping charge is $10) set it to '1'.
    $pp['shipping_flat_rate_per_item'] = '0';

    // Ship by order total:
    // Set the option below if you want to set up a sliding scale of shipping charges based on the order’s total cost.
    // For example, if you charge $6 for orders between $1 to $50, $3 for orders between $51 to $100, and free shipping for orders worth $101 and more,
    // set it to '[ 0=6 | 50=3 | 100=0 ]'
    // where the string above stands for '6 for more than 0, 3 for more than 50, 0 for more than 100'
    //
    // To set up the shipping charges as a percentage of order's total cost (as opposed to fixed values as we did above),
    // add a '%' after the string e.g. '[ 0=6 | 50=3 | 100=0 ]%'
    // which now makes it 6% of the cart's total for orders over $0, 3% of the cart's total for orders over $50 and 0% (free) for over $100.
    $pp['shipping_by_order_total'] = '';

    // Ship by quantity ordered:
    // Set the option below if you want to set up a sliding scale of shipping charges based on the number of items in cart.
    // For example, if you charge $3 to deliver one to five books, $7 to ship six to 15 books, and $10 to ship more than 15 books,
    // set it to '[ 0=3 | 5=7 | 15=10 ]'
    // where the string above stands for '3 for more than 0, 7 for more than 5, 10 for more than 15'
    $pp['shipping_by_quantity_ordered'] = '';

    /*
        Discounts
    */
    // Discount by order total:
    // Set the option below if you want to set up a sliding scale of discounts based on the order’s total cost.
    // For example, if you offer a discount of $5 for orders over $50, and $15 for orders over $100,
    // set it to '[ 50=5 | 100=15 ]'
    //
    // To set up discounts as a percentage of the order's total cost (as opposed to fixed values as we did above),
    // add a '%' after the string e.g. '[ 50=5 | 100=15 ]%'
    // which now now sets up a discount of 5% of the cart's total for orders over $50, and 10% for orders over $100.
    //
    // To set up a flat discount (fixed value or percentage) off orders above a particular value, create only a single tier in the scale
    // e.g. '[ 100=10 ]' or '[ 100=10 ]%' will provide a flat discount off any order above $100.
    $pp['discount_by_order_total'] = '';

    // Discount by quantity ordered:
    // Set the option below if you want to set up a sliding scale of discounts based on the number of items in cart.
    // For example, for "Buy any 5 products, get $10 off your order" kind of promotion,
    // set it to '[ 4=10 ]'
    // where the string above stands for '10 for more than 4 items'
    $pp['discount_by_quantity_ordered'] = '';

    /*
        Tax
    */
    $pp['tax_percent'] = '0';

    // Set the following to '1' if tax is to be applied before adding shipping charges to cart total (i.e. shipping is not taxed).
    $pp['tax_excludes_shipping'] = '0';
