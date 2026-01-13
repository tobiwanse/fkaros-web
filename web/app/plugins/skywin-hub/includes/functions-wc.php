<?php
/**
 * Functions for WooCommerce integration with Skywin Hub.
 *
 * @package SkywinHub
 */
if (!defined('ABSPATH')) { exit; }
function checkout_create_order_line_item($item, $cart_item_key, $values, $order)
{
    $product = $item->get_product();
    $product_id = $product->get_id();
    if( isset($values['fields']) && !empty($values['fields']) ){
       foreach($values['fields'] as $field){
            if( 
                $field['type'] == 'payment-checkbox' ||
                $field['type'] == 'payment-multiple' ||
                $field['type'] == 'payment-select' ||
                $field['type'] == 'payment-single'
                ){
                continue;
            }
            $item->add_meta_data($field['name'], $field['value']);
       }
    }
    if ( isset($values['form_entry']) ) {
        $item->add_meta_data('form_entry', $values['form_entry']);
    }
    if ($product_id == skywin_hub_deposit()->get_id()) {
        if (isset($values['account']['label']) && isset($values['account']['value'])) {
            $item->add_meta_data($values['account']['label'], $values['account']['value']);
        }
        if (isset($values['accountNo']['label']) && isset($values['accountNo']['value'])) {
            $item->add_meta_data($values['accountNo']['label'], $values['accountNo']['value']);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'checkout_create_order_line_item', 10, 4);
function before_calculate_totals($cart)
{
    $deposit_product_id = skywin_hub_deposit()->get_id();
    
    foreach ($cart->cart_contents as $cart_item_key => $cart_item) {
        if( isset($cart_item['fields']) && !empty($cart_item['fields']) ){
             $fields = $cart_item['fields'];
             foreach($fields as $field){
                error_log($field['type']);
                 if(
                    $field['type'] == 'payment-checkbox'
                    || $field['type'] == 'payment-multiple'
                    || $field['type'] == 'payment-select'
                    || $field['type'] == 'payment-single'
                    ){
                    $cart->add_fee($field['value'], $field['amount_raw']);
                 }
             } 
        }
        if ($deposit_product_id == $cart_item['product_id']) {
            if (isset($cart_item['accountNo']) && !empty($cart_item['accountNo'])) {
                $cart_item['data']->set_price($cart_item['amount']['value']);
                $cart_item['data']->set_regular_price($cart_item['amount']['value']);
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'before_calculate_totals', 10, 1);
function order_status_completed($order_id)
{
    error_log('order_status_completed');
}
add_action('woocommerce_order_status_completed', 'order_status_completed', 10, 1);
function payment_complete($order_id)
{
    error_log('payment_complete' );
    $order = wc_get_order($order_id);
    $comment = $order->get_customer_note();

    $deposit_product_id = skywin_hub_deposit()->get_id();
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $product_id = $product->get_id();
        if ( $deposit_product_id == $product_id ) {
            $accountNo = $item['accountNo'];
            $amount = $item->get_subtotal();
            $transType = "HOPPKONTO";
            $paymentType = "Webpay";
            $data = array(
                'order_id'          => $order_id,
                'item_id'           => $item_id,
                'product_id'        => $product_id,
                'accountNo'         => $accountNo,
                'amount'            => $amount,
                'transType'         => $transType,
                'paymentType'       => $paymentType,
                'paymentReasonCode' => '',
                'comment'           => "Order id: $order_id $comment"
            );
            do_action('skywin_hub_create_deposit', $data);
        }
    }

    $form_entry = $item->get_meta('form_entry');
    if ( !empty($form_entry) ) {
        $form_id = $form_entry['id'];
        $_POST['action'] = 'wpforms_submit';
        $form_entry['order_id'] = $order_id;
        $process = wpforms()->process;
        $process->process($form_entry);
        $errors = $process->errors;        
        if( $errors ){
            $admin_note = "Something went wrong while processing form ({$form_id}): " . json_encode($errors);
            $order->add_order_note($admin_note);
            $order->update_status('cancelled');
            wc_add_notice(__('Unable to submit form. Please try again later.', 'skywin-hub'), 'error');
        } else {
            $admin_note = "Form completed: {$process->entry_id}";
            $order->add_order_note($admin_note);
            $order->update_status('completed');
            
            if(isset($process->confirmation_message) && !empty($process->confirmation_message)){
                $confirmation_message = $process->confirmation_message;
                wc_add_notice($confirmation_message, 'notice');
            }
        }
    }
}
add_action('woocommerce_payment_complete', 'payment_complete', 1, 1);
function before_add_to_cart_button()
{
    global $product;
    if (is_a($product, 'WC_Product') && $product->get_id() == skywin_hub_deposit()->get_id()) {
        echo do_shortcode("[skywin_hub_deposit_product_fields]");
    }
}
add_action('woocommerce_before_add_to_cart_button', 'before_add_to_cart_button', 10);
function deposit_is_checkout($is_checkout)
{
    if (is_admin() || defined('REST_REQUEST') && REST_REQUEST || function_exists('wp_doing_ajax') && wp_doing_ajax() || defined('WP_CLI') && WP_CLI) {
        return $is_checkout;
    }
    if (is_page('deposit')) {
        return true;
    }
    if (is_singular('product')) {
        $queried = get_queried_object_id();
        if ($queried && $queried === intval(skywin_hub_deposit()->get_id())) {
            return true;
        }
    }
    return $is_checkout;
}
add_filter('woocommerce_is_checkout', 'deposit_is_checkout');
function add_to_cart_validation($true, $product_id, $quantity)
{
    $product = wc_get_product($product_id);
    $wpform_id = $product->get_meta('_wpforms_id');
    if( $wpform_id ){}
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
    if (skywin_hub_deposit()->get_id() == $product_id) {
        error_log('add_cart_item_data');
        $cart = WC()->cart;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] === $product_id) {
                error_log('Replace cart item deposit.');
                $cart->remove_cart_item($cart_item_key);
            }
        }
        if (isset($_POST['search_account']) && !empty($_POST['search_account'])) {
            $cart_item_data['account'] = array(
                'label' => 'account',
                'value' => sanitize_text_field($_POST['search_account'])
            );
        }
        if (isset($_POST['amount']) && !empty($_POST['amount'])) {
            $cart_item_data['amount'] = array(
                'label' => 'amount',
                'value' => sanitize_text_field($_POST['amount'])
            );
        }
        if (isset($_POST['accountNo']) && !empty($_POST['accountNo'])) {
            $cart_item_data['accountNo'] = array(
                'label' => 'accountNo',
                'value' => intval($_POST['accountNo'])
            );
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'add_cart_item_data', 10, 3);
function get_item_data($cart_item_data, $cart_item)
{
    if( isset($cart_item['fields']) && !empty($cart_item['fields']) ){
       foreach($cart_item['fields'] as $field){
            if(
                $field['type'] == 'payment-checkbox'
                || $field['type'] == 'payment-multiple'
                || $field['type'] == 'payment-select'
                || $field['type'] == 'payment-single'){
                continue;
            }
            $cart_item_data[] = array(
                'name' => $field['name'],
                'value' => $field['value']
            );
       }
    }
    $deposit_product_id = skywin_hub_deposit()->get_id();
    if ($deposit_product_id == $cart_item['product_id']) {
        if (isset($cart_item['account']['label']) && isset($cart_item['account']['value'])) {
            $cart_item_data[] = array(
                'name' => __('To', 'skywin-hub'),
                'value' => esc_html($cart_item['account']['value'])
            );
        }
        if (isset($cart_item['accountNo']['label']) && isset($cart_item['accountNo']['value'])) {
            $cart_item_data[] = array(
                'name' => __('Account #', 'skywin-hub'),
                'value' => esc_html($cart_item['accountNo']['value'])
            );
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_get_item_data', 'get_item_data', 10, 2);
function order_item_display_meta_key($key, $meta, $item)
{
    error_log('order_item_display_meta_key: ' . $meta->key);
    $deposit_product_id = skywin_hub_deposit()->get_id();
    
    if ($deposit_product_id == $item['product_id']) {
        if ('account' === $meta->key) { $key = 'To account';}
        if ('accountNo' === $meta->key) {$key = 'Account #';}
        if ('transNo' === $meta->key) {$key = 'Transaction #';}
    }
    return $key;
}
add_filter('woocommerce_order_item_display_meta_key', 'order_item_display_meta_key', 10, 3);
function order_item_display_meta_value($value, $meta, $item)
{
    if (skywin_hub_deposit()->get_id() == $item['product_id']) {
        if ('account' === $meta->key) {
            $value = esc_html($meta->value);
        }
        if ('accountNo' === $meta->key) {
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
    if (is_a($product, 'WC_Product') && skywin_hub_deposit()->get_id() != $product->get_id()) {
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
    if (is_singular('product')) {
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
function disable_product_in_loops($is_purchasable, $product)
{
    if ( is_cart() || is_shop() || is_product_category() || is_product_tag()) {
        if ($product->get_id() == skywin_hub_deposit()->get_id()) {
            return false;
        }
        if( $product->get_meta('_wpforms_id')){
            return false;
        }
    }
    return $is_purchasable;
}
add_filter('woocommerce_is_purchasable', 'disable_product_in_loops', 10, 2);
function checkout_fields( $fields ) {
    
    if ( WC()->cart && WC()->cart->get_total('edit') == 0 ) {
        $fields['billing'] = [];
    }
    return $fields;
};
add_filter( 'woocommerce_checkout_fields', 'checkout_fields' );
function before_add_to_cart_form(){
    global $product;
    $wpform_id = $product->get_meta('_wpforms_id', true);
    if(!$wpform_id){return;}
    wc_print_notices();
    if (!$product->is_purchasable()) { return; }
    $product_id = $product->get_id();
    $add_to_cart_text = get_option('skywin_hub_deposit_add_to_cart_text');
    if ($product->is_in_stock()):
        echo do_shortcode("[wpforms id='{$wpform_id}']");
    endif;    
}
add_action( 'woocommerce_single_product_summary', 'before_add_to_cart_form' );
function needs_payment( $need_payment, $cart ) {
    if ( $cart->get_total('edit') == 0 ) {
        $need_payment = false;
    }
    return $need_payment;
}
add_filter( 'woocommerce_cart_needs_payment', 'needs_payment', 10, 2 );
function remove_add_to_cart_form( $block_content, $block ) {
    global $product;
    if( !$product || !$product->get_meta('_wpforms_id') || is_shop()){ return $block_content; }
    $remove_blocks = [
        'woocommerce/add-to-cart-with-options',
        'woocommerce/add-to-cart-form'
    ];
    if ( in_array( $block['blockName'], $remove_blocks, true ) ) {
        return '';
    }
    return $block_content;
};
add_filter( 'render_block','remove_add_to_cart_form', 10, 2 );

add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false', 9999 );
add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 9999 );
add_filter( 'woocommerce_widget_cart_is_hidden', '__return_false', 9999 );
//add_action( 'woocommerce_before_checkout_form', 'astra_woocommerce_header_cart', 5 );
add_filter( 'woocommerce_checkout_update_order_review_expired', '__return_false' );
function add_content_thankyou() {
   echo wc_print_notices();
}
add_action( 'woocommerce_before_thankyou', 'add_content_thankyou', 10);
add_filter( 'woocommerce_widget_cart_is_hidden', 'always_show_cart', 10, 0 );
function always_show_cart() {
    return false;
}

/* Woocommerce admin hooks */
function add_wpforms_data_tab($tabs)
{
    $tabs['custom_settings'] = array(
        'label' => __('WPForms Settings', 'woocommerce'),
        'target' => 'wpforms_settings_data',
        'class' => array('show_if_simple', 'show_if_variable', 'show_if_grouped'),
    );
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'add_wpforms_data_tab');
function add_custom_product_tab_content()
{
    echo '<div id="wpforms_settings_data" class="panel woocommerce_options_panel hidden">';
    woocommerce_wp_text_input(array(
        'id' => '_wpforms_id',
        'label' => 'WPForms id',
        'desc_tip' => true,
        'description' => 'Enter a WPForms id.'
    ));
    echo '</div>';
}
add_action('woocommerce_product_data_panels', 'add_custom_product_tab_content');
function save_wpforms_product_meta($product)
{
    if (isset($_POST['_wpforms_id'])) {
        $product->update_meta_data('_wpforms_id', sanitize_text_field($_POST['_wpforms_id']));
    }
}
add_action('woocommerce_admin_process_product_object', 'save_wpforms_product_meta');