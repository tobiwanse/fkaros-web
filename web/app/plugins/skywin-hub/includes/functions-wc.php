<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Functions for WooCommerce integration with Skywin Hub.
 *
 * @package SkywinHub
 */
add_action( 'add_meta_boxes', function() {
    global $post;
    if ( $post && $post->post_type === 'shop_order' ) {
        add_post_meta( $post->ID, 'my_custom_field', '', true );
    }
});
function checkout_create_order_line_item($item, $cart_item_key, $values, $order)
{
    if (isset($values['skywin_accountNo']['label']) && isset($values['skywin_accountNo']['value'])) {
        $item->add_meta_data($values['skywin_accountNo']['label'], $values['skywin_accountNo']['value']);
    }
    if (isset($values['skywin_account']['label']) && isset($values['skywin_account']['value'])) {
        $item->add_meta_data($values['skywin_account']['label'], $values['skywin_account']['value']);
    }
    if( isset($values['form_id']) && isset($values['fields']) ){
        $item->add_meta_data('wpform-id', $values['form_id']);
        $item->add_meta_data('wpform-fields', $values['fields']);
        foreach($values['fields'] as $field) {
            if ( isset($field['name']) && isset($field['value'])) {
                $item->add_meta_data($field['name'], $field['value']);
            }
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'checkout_create_order_line_item', 10, 4);
function before_calculate_totals($cart)
{
    $skywin_deposit_product_id = skywin_hub_deposit_product()->get_id();
    foreach ($cart->cart_contents as $cart_item_key => $cart_item) {
        if (isset($cart_item['deposit_amount']) && !empty($cart_item['deposit_amount']) && $skywin_deposit_product_id == $cart_item['product_id']) {
            $cart_item['data']->set_price($cart_item['deposit_amount']['value']);
            $cart_item['data']->set_regular_price($cart_item['deposit_amount']['value']);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'before_calculate_totals', 10, 1);
function payment_complete( $order_id )
{
    $order = wc_get_order( $order_id );
    $comment = $order->get_customer_note();
    $data = array(
        'order_id' => $order_id
    );
    $skywin_deposit_product_id = skywin_hub_deposit_product()->get_id();
    foreach( $order->get_items() as $key => $item ) {
        $item_id = $item->get_id();
        if( $skywin_deposit_product_id == $item->get_product_id()) {
            $accountNo = $item['accountNo'];
            $amount = $item->get_subtotal();
            $transType = "HOPPKONTO";
            $paymentType = "Webpay";
            $data['item_id'] = $item_id;
            $data['body'] = array(
                'accountNo' => $accountNo,
                'amount' => $amount,
                'transType' => $transType,
                'paymentType' => $paymentType,
                'paymentReasonCode' => 23,
                'comment' => 'Order id: ' . $order_id . '. ' . $comment
            );
            do_action( 'skywin_hub_create_deposit', $data);				
        }

        $wpform_fields = $item->get_meta( 'wpform-fields' ) ?? false;
        $wpform_id = $item->get_meta( 'wpform-id' ) ?? false;
        
        if ( $wpform_fields ) {
            $wpform_fields[] = [
                'name'  => 'Order ID',
                'value' => $order_id,
                'type'  => 'text',
                'id'    => count($wpform_fields) + 1,
            ];
            $wpform_fields[] = [
                'name'  => 'Payment method',
                'value' => $order->get_payment_method_title(),
                'type'  => 'text',
                'id'    => count($wpform_fields) + 1,
            ];
            $wpform_fields[] = [
                'name'  => 'Payment ID',
                'value' => $order->get_transaction_id(),
                'type'  => 'text',
                'id'    => count($wpform_fields) + 1,
            ];
        }
        if ( !empty($wpform_id) && !empty($wpform_fields) ) {
            update_post_meta( $order_id, 'your_custom_field_key', 'your custom value' );
            update_post_meta( $order_id, 'my_custom_field', 'my value' );          
            $entry_id = wpforms()->entry->add([
                'form_id' => $wpform_id,
                'fields'  => json_encode($wpform_fields),
                'date'    => current_time('mysql'),
            ]);

            if ( $entry_id ) {
            }
        }
    }
}
add_action('woocommerce_payment_complete','payment_complete', 10, 1 );
function custom_fields_template()
{
    global $product;
    if ( is_object($product ) && $product->get_id() == skywin_hub_deposit_product()->get_id()) {
        echo do_shortcode("[skywin_hub_deposit_product_fields]");
    }
}
add_action('woocommerce_before_add_to_cart_button','custom_fields_template', 10);
function after_single_product()
{
    //echo do_shortcode('[woocommerce_cart]');
    //echo do_shortcode('[woocommerce_checkout]');
}
add_action('woocommerce_after_single_product','after_single_product', 10);
function skywin_fake_checkout_on_deposit_product() {
    global $product;
    if ( is_product() && !is_checkout() && $product == 'deposit') {
        add_filter('woocommerce_is_checkout', '__return_true', 20);
    }
}
//add_action('template_redirect', 'skywin_fake_checkout_on_deposit_product', 10);

function add_cart_item_data($cart_item_data, $product_id)
{
    if ( skywin_hub_deposit_product()->get_id() == $product_id ) {
        if ( isset($_POST['deposit_amount']) && ! empty($_POST['deposit_amount']) ) {
            $deposit_amount = sanitize_text_field($_POST['deposit_amount']);
            $cart_item_data['deposit_amount'] = array(
                'label' => 'amount',
                'value' => $deposit_amount
            );
        }
                    
        if ( isset($_POST['skywin_accountNo']) && ! empty($_POST['skywin_accountNo']) ) {			
            $cart_item_data['skywin_accountNo'] = array(
                'label' => 'accountNo',
                'value' => intval($_POST['skywin_accountNo'])
            );
        }
                
        if ( isset($_POST['skywin_account']) && ! empty($_POST['skywin_account']) ) {	
            $cart_item_data['skywin_account'] = array(
                'label' => 'account',
                'value' => esc_html($_POST['skywin_account'])
            );
        }
        
    }
    return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data','add_cart_item_data', 10, 2);
function add_to_cart_validation( $true, $product_id, $quantity )
{
    if ( skywin_hub_deposit_product()->get_id() == $product_id ) {
        if ( !isset($_POST['deposit_amount']) || empty($_POST['deposit_amount']) || $_POST['deposit_amount'] < 1 || !is_numeric($_POST['deposit_amount']) ) {
            wc_add_notice( __( 'Amount is not valid.', 'skywin-hub' ), 'error' );
            $true = false;
            return $true;
        }
        if ( !isset($_POST['skywin_accountNo']) || empty($_POST['skywin_accountNo']) ) {
            wc_add_notice( __( 'You have to select an account.', 'skywin-hub' ), 'error' );
            $true = false;
            return $true;
        }
    }
    return $true;
}
add_filter( 'woocommerce_add_to_cart_validation', 'add_to_cart_validation', 10, 3 );	
function get_item_data( $cart_item_data, $cart_item )
{
    if( isset($cart_item['skywin_account']['label']) && isset($cart_item['skywin_account']['value']) ) {
        $cart_item_data[] = array(
            'name' => __('Account', 'skywin-hub'),
            'value' => esc_html($cart_item['skywin_account']['value'])
        );
    }
    if( isset($cart_item['skywin_accountNo']['label']) && isset($cart_item['skywin_accountNo']['value']) ) {
        $cart_item_data[] = array(
            'name' => __('Account no', 'skywin-hub'),
            'value' => esc_html($cart_item['skywin_accountNo']['value'])
        );
    }
    if(isset($cart_item['fields']))
    {
        foreach($cart_item['fields'] as $field) {
            if( isset($field['name']) && isset($field['value']) ) {
                $cart_item_data[] = array(
                    'name' => esc_html($field['name']),
                    'value' => esc_html($field['value'])
                );
            }
        }
    }
    return $cart_item_data;
}
add_filter( 'woocommerce_get_item_data','get_item_data', 10, 2 );
function order_item_display_meta_key( $key, $meta, $item )
{
    if( 'accountNo' === $meta->key ) {
        $key = 'Account No';
    }
    if( 'account' === $meta->key ) {
        $key = 'Skywin account';
    }
    if( 'skywin_trans_no' === $meta->key ){
        $key = 'Skywin trans no';
    }
    if( 'skywin_account_no' === $meta->key ){
        $key = 'Skywin account no';
    }
    
    return $key;
}
add_filter( 'woocommerce_order_item_display_meta_key', 'order_item_display_meta_key', 10, 3 );
function order_item_display_meta_value( $value, $meta, $item )
{
    if('accountNo' === $meta->key){
        $value = esc_html($meta->value);
    }
    if('account' === $meta->key){
        $value = esc_html($meta->value);
    }
    if( 'skywin_trans_no' === $meta->key ){
        $value = esc_html($meta->value);
    }
    if( 'skywin_account_no' === $meta->key ){
        $value = esc_html($meta->value);
    }

    return $value;	
}
add_filter( 'woocommerce_order_item_display_meta_value','order_item_display_meta_value', 10, 3 );
function hidden_order_itemmeta( $array )
{
    $array[] = 'product';
    $array[] = 'accountNo';
    return $array;
}
add_filter( 'woocommerce_hidden_order_itemmeta', 'hidden_order_itemmeta', 10, 1 );
function order_item_get_formatted_meta_data( $formatted_meta, $instance )
{
    $temp_meta = [];
    foreach ( $formatted_meta as $key => $meta) {
        if ( isset( $meta->key ) && ! in_array($meta->key, ['accountNo'] )) {
            $temp_meta[$key] = $meta;
        }
    }
    return $temp_meta;
}
add_filter('woocommerce_order_item_get_formatted_meta_data','order_item_get_formatted_meta_data', 10, 2 );
function add_to_cart_redirect($url, $product)
{
    if( !$product || skywin_hub_deposit_product()->get_id() != $product->get_id()){
        return $url;
    }
    return wc_get_checkout_url();
}
add_filter('woocommerce_add_to_cart_redirect', 'add_to_cart_redirect', 10, 2 );
add_filter('woocommerce_single_product_zoom_enabled', '__return_false');
add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999 );