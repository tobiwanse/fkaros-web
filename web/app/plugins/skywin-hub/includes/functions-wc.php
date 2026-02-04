<?php
if (!defined('ABSPATH')) { exit; }
function checkout_create_order_line_item($item, $cart_item_key, $values, $order)
{
    $product = $item->get_product();
    $product_id = $product->get_id();
    if ( $product_id == skywin_hub_deposit()->get_id() ) {
        if (isset($values['account']['label']) && isset($values['account']['value'])) {
            $item->add_meta_data($values['account']['label'], $values['account']['value']);
        }
        if (isset($values['accountNo']['label']) && isset($values['accountNo']['value'])) {
            $item->add_meta_data($values['accountNo']['label'], $values['accountNo']['value']);
        }
        if (isset($values['emailAddress']['label']) && isset($values['emailAddress']['value'])) {
            $item->add_meta_data($values['emailAddress']['label'], $values['emailAddress']['value']);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'checkout_create_order_line_item', 10, 4);
function before_calculate_totals($cart)
{
    $deposit_product_id = skywin_hub_deposit()->get_id();
    foreach ($cart->cart_contents as $cart_item_key => $cart_item) {
        if ( $deposit_product_id == $cart_item['product_id'] ) {
            if (isset($cart_item['accountNo']) && !empty($cart_item['accountNo'])) {
                $cart_item['data']->set_price($cart_item['amount']['value']);
                $cart_item['data']->set_regular_price($cart_item['amount']['value']);
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'before_calculate_totals', 10, 1);
function payment_complete($order_id)
{
    $order = wc_get_order($order_id);
    $comment = $order->get_customer_note();

    $deposit_product_id = skywin_hub_deposit()->get_id();
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $product_id = $product->get_id();
        if ( $deposit_product_id == $product_id ) {
            $accountNo = $item['accountNo'];
            $emailAddress = $item['emailAddress'];
            $amount = $item->get_subtotal();
            $transType = "HOPPKONTO";
            $paymentType = "Webpay";
            $paymentReasonCode = "";
            $data = array(
                'order_id'          => $order_id,
                'item_id'           => $item_id,
                'product_id'        => $product_id,
                'accountNo'         => $accountNo,
                'emailAddress'      => $emailAddress,
                'amount'            => $amount,
                'transType'         => $transType,
                'paymentType'       => $paymentType,
                'paymentReasonCode' => $paymentReasonCode,
                'comment'           => "Order id: $order_id $comment"
            );
            do_action('skywin_hub_create_deposit', $data);
        }
    }
}
add_action('woocommerce_payment_complete', 'payment_complete', 10, 1);
function before_add_to_cart_button()
{
    global $product;
    if ( is_a($product, 'WC_Product') && $product->get_id() == skywin_hub_deposit()->get_id()) {
        echo do_shortcode("[skywin_hub_deposit_product_fields]");
    }
}
add_action('woocommerce_before_add_to_cart_button', 'before_add_to_cart_button', 10);
function add_to_cart_validation($true, $product_id, $quantity)
{
    if ( skywin_hub_deposit()->get_id() == $product_id ) {
        error_log('add_to_cart_validation');
        if (!isset($_POST['amount']) || empty($_POST['amount']) || $_POST['amount'] < 1 || !is_numeric($_POST['amount'])) {
            $message = __('Amount is not valid.', 'skywin-hub');
            wc_add_notice($message, 'error');
            $true = false;
        } else {
            $min_amount = get_option('skywin_hub_deposit_min_amount');
            $max_amount = get_option('skywin_hub_deposit_max_amount');
            $currency = get_woocommerce_currency_symbol();
            if (!empty($min_amount) && $_POST['amount'] < $min_amount) {
                $message = __("Amount have to be greater than $min_amount $currency");
                wc_add_notice($message, 'error');
                $true = false;
            }
            if (!empty($max_amount) && $_POST['amount'] > $max_amount) {
                $message = __("Amount have to be less than $max_amount $currency");
                wc_add_notice($message, 'error');
                $true = false;
            }
        }
        if ( !isset($_POST['accountNo']) || empty($_POST['accountNo']) ) {
            $message = __('You have to select an account.', 'skywin-hub');
            wc_add_notice($message, 'error');
            $true = false;
        }
    }
    return $true;
}
add_filter('woocommerce_add_to_cart_validation', 'add_to_cart_validation', 10, 3);
function add_cart_item_data($cart_item_data, $product_id, $variation_id)
{
    if ( skywin_hub_deposit()->get_id() == $product_id ) {
        error_log('add_cart_item_data');
        $cart = WC()->cart;
        if ( isset($_POST['search_account']) && !empty($_POST['search_account']) ) {
            $cart_item_data['account'] = array(
                'label' => 'account',
                'value' => sanitize_text_field($_POST['search_account'])
            );
        }
        if ( isset($_POST['amount']) && !empty($_POST['amount']) ) {
            $cart_item_data['amount'] = array(
                'label' => 'amount',
                'value' => sanitize_text_field($_POST['amount'])
            );
        }
        if ( isset($_POST['accountNo']) && !empty($_POST['accountNo']) ) {
            $cart_item_data['accountNo'] = array(
                'label' => 'accountNo',
                'value' => intval($_POST['accountNo'])
            );
        }
        if ( isset($_POST['emailAddress']) && !empty($_POST['emailAddress']) ) {
            $cart_item_data['emailAddress'] = array(
                'label' => 'emailAddress',
                'value' => sanitize_email($_POST['emailAddress'])
            );
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_cart_item_data', 10, 3);
function get_item_data($cart_item_data, $cart_item)
{
    if ( skywin_hub_deposit()->get_id() == $cart_item['product_id']) {
        if (isset($cart_item['account']['label']) && isset($cart_item['account']['value'])) {
            $cart_item_data[] = array(
                'name' => __('Account', 'skywin-hub'),
                'value' => esc_html($cart_item['account']['value'])
            );
        }
        if (isset($cart_item['accountNo']['label']) && isset($cart_item['accountNo']['value'])) {
            $cart_item_data[] = array(
                'name' => __('Account', 'skywin-hub'),
                'value' => esc_html($cart_item['accountNo']['value'])
            );
        }
        if (isset($cart_item['emailAddress']['label']) && isset($cart_item['emailAddress']['value'])) {
            $cart_item_data[] = array(
                'name' => __('Email', 'skywin-hub'),
                'value' => esc_html($cart_item['emailAddress']['value'])
            );
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_get_item_data', 'get_item_data', 10, 2);
function order_item_display_meta_key($key, $meta, $item)
{
    if ( skywin_hub_deposit()->get_id() == $item['product_id'] ) {
        if ('account' === $meta->key) { $key = 'Account';}
        if ('accountNo' === $meta->key) {$key = 'Account No';}
        if ('emailAddress' === $meta->key) {$key = 'Email';}
        if ('transNo' === $meta->key) {$key = 'Transaction';}
    }
    return $key;
}
add_filter('woocommerce_order_item_display_meta_key', 'order_item_display_meta_key', 10, 3);
function order_item_display_meta_value($value, $meta, $item)
{
    if ( skywin_hub_deposit()->get_id() == $item['product_id'] ) {
        if ('account' === $meta->key) {
            $value = esc_html($meta->value);
        }
        if ('accountNo' === $meta->key) {
            $value = esc_html($meta->value);
        }
        if ('emailAddress' === $meta->key) {
            $value = esc_html($meta->value);
        }
        if ('transNo' === $meta->key) {
            $value = esc_html($meta->value);
        }
        return $value;
    }
    return $value;
}
add_filter('woocommerce_order_item_display_meta_value', 'order_item_display_meta_value', 10, 3);
function hidden_order_itemmeta($array)
{
    return $array;
}
add_filter('woocommerce_hidden_order_itemmeta', 'hidden_order_itemmeta', 10, 1);
function order_item_get_formatted_meta_data($formatted_meta, $instance)
{
    $temp_meta = [];
    foreach ($formatted_meta as $key => $meta) {
        if (isset($meta->key) && !in_array($meta->key, [])) {
            $temp_meta[$key] = $meta;
        }
    }
    return $temp_meta;
}
add_filter('woocommerce_order_item_get_formatted_meta_data', 'order_item_get_formatted_meta_data', 10, 2);
function add_to_cart_redirect($url, $product)
{
    
    if ( is_a($product, 'WC_Product') && skywin_hub_deposit()->get_id() != $product->get_id() ) {
        return $url;
    }
    return wc_get_checkout_url();
}
add_filter('woocommerce_add_to_cart_redirect', 'add_to_cart_redirect', 10, 2);
function add_to_cart_text($text, $product)
{
    if ($product->get_id() != skywin_hub_deposit()->get_id()) {
        return $text;
    }
    $custom_add_to_cart_text = get_option('skywin_hub_deposit_add_to_cart_text', $text);
    if ( $custom_add_to_cart_text && is_singular('product')) {
        return $custom_add_to_cart_text;
    }
    return esc_html($text);
}
add_filter('woocommerce_product_single_add_to_cart_text', 'add_to_cart_text', 9999, 2);
function loop_add_to_cart_args($args, $product)
{
    if ($product->get_id() == skywin_hub_deposit()->get_id()) {
        $args['class'] = str_replace('ajax_add_to_cart', '', $args['class']);
    }
    return $args;
}
add_filter('woocommerce_loop_add_to_cart_args', 'loop_add_to_cart_args', 10, 2);
function remove_price( $price, $product )
{
    if ( is_product() && $product->get_id() == skywin_hub_deposit()->get_id() ) {
        return '';
    }
    return $price;
};
add_filter( 'woocommerce_get_price_html', 'remove_price', 10, 2 );
function add_notice()
{
    global $product;
    if( is_a($product, 'WC_Product') ){
        $is_purchasable = $product->is_purchasable();
        if( !$is_purchasable && $product->get_id() == skywin_hub_deposit()->get_id() ){
            $admin_email = get_bloginfo('admin_email');
            $admin_email_link = "<a href='mailto:". $admin_email ."' >admin</a>";
            wc_add_notice("Service is not available, please contact $admin_email_link", 'error');
            wc_print_notices();
        }
    }
};
add_action('woocommerce_single_product_summary', 'add_notice');

function is_purchasable($is_purchasable, $product)
{
    if ( is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('DOING_AJAX') && DOING_AJAX) ) return $is_purchasable;
    if( is_a($product, 'WC_Product') && $product->get_id() == skywin_hub_deposit()->get_id() ){
        if ( !isset(WC()->session) || is_cart() && WC()->cart->is_empty() || is_shop() || is_product_category() || is_product_tag() ) return false;
        $db_status = skywin_hub_db()->status();
        
        if( is_wp_error($db_status) ){
            $is_purchasable = false;
        }
        $api_status = skywin_hub_api()->status();
        if( is_wp_error($api_status) ){
            $is_purchasable = false;
        }
    }
    return $is_purchasable;
}
add_filter('woocommerce_is_purchasable', 'is_purchasable', 10, 2);

add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false', 9999 );
add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 9999 );
add_filter( 'woocommerce_widget_cart_is_hidden', '__return_false', 9999 );
add_filter( 'woocommerce_checkout_update_order_review_expired', '__return_false' );
function add_content_thankyou() {
   echo wc_print_notices();
}
add_action( 'woocommerce_before_thankyou', 'add_content_thankyou', 10);