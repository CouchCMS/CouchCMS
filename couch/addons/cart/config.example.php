<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // /////////////////////////////////// Core config values ////////////////////////////////////////////
    $pp['tpl_cart'] = 'cart.php';
    $pp['tpl_checkout'] = 'checkout.php';
    $pp['paypal_use_sandbox'] = 0;
    $pp['paypal_email'] = '';
    $pp['currency'] = 'USD';
    $pp['currency_symbol'] = '$';
    $pp['allow_decimal_qty'] = 0;


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
