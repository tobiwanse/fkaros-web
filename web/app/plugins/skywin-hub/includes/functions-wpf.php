<?php
if ( !defined('ABSPATH') || !defined( 'SW_ABSPATH' )) { exit; }
/**
 * Functions for WPForms integration with WooCommerce.
 *
 * @package SkywinHub
 */
function field_properties_name($properties, $field, $form_data)
{
    $properties['inputs']['first']['sublabel']['value'] = __('Förnamn', 'skywin-hub');
    $properties['inputs']['middle']['sublabel']['value'] = __('Middle Initial', 'skywin-hub');
    $properties['inputs']['last']['sublabel']['value'] = __('Efternamn', 'skywin-hub');
    return $properties;
}
add_filter('wpforms_field_properties_name', 'field_properties_name', 10, 3);
function field_properties_address($properties, $field, $form_data)
{
    $properties['inputs']['address1']['sublabel']['value'] = __('Adress', 'skywin-hub');
    $properties['inputs']['city']['sublabel']['value'] = __('Stad', 'skywin-hub');
    $properties['inputs']['state']['sublabel']['value'] = __('Län', 'skywin-hub');
    $properties['inputs']['postal']['sublabel']['value'] = __('Postnummer', 'skywin-hub');
    $properties['inputs']['country']['sublabel']['value'] = __('Land', 'skywin-hub');
    return $properties;
}
add_filter('wpforms_field_properties_address', 'field_properties_address', 10, 3);
function get_next_field_id($fields){
    $existing_ids = array_map('intval', array_keys((array) ($fields ?? [])));
    $next_id = $existing_ids ? max($existing_ids) + 1 : 999;
    return $next_id;
}
function process_filter($fields, $entry, $form_data)
{
    if ( !isset($entry['order_id']) || empty($entry['order_id']) ) { return $fields; }
    $order = wc_get_order($entry['order_id']);
    $payment_method = $order->get_payment_method();
    $payment_id = $order->get_transaction_id();
    $order_total = $order->get_total();
    $fields[] = [
        'name'  => 'Order ID',
        'value' => $entry['order_id'],
        'type'  => 'text',
        'id'    => get_next_field_id($fields)
    ];
    $fields[] = [
        'name'  => 'Payment method',
        'value' => $payment_method,
        'type'  => 'text',
        'id'    => get_next_field_id($fields)
    ];
    $fields[] = [
        'name'  => 'Payment ID',
        'value' => $payment_id,
        'type'  => 'text',
        'id'    => get_next_field_id($fields)
    ];
    $fields[] = [
        'name'  => 'Order Total',
        'value' => $order_total,
        'type'  => 'text',
        'id'    => get_next_field_id($fields)
    ];
    return $fields;
}
add_filter('wpforms_process_filter', 'process_filter', 10, 3);
function wpforms_process($fields, $entry, $form_data)
{
    if ( !isset($_POST['wpforms']) || empty($_POST['wpforms'])) {
        return $fields;
    }
    $products = wc_get_products(
        array(
            'status' => 'publish',
            'meta_key' => '_wpforms_id',
            'meta_value' => $form_data['id']
        )
    );
    if ( empty($products) ) { return $fields; }

    $cart = WC()->cart;
    $cart->empty_cart();
    foreach($products as $product){
        if ( !$product->is_in_stock() ) {
            wpforms()->process->errors[$form_data['id']]['header'] = esc_html__('This product is out of stock.', 'skywin-hub');
            return $fields;
        }
        $product_id = $product->get_id();
        $quantity = 1;
        if(isset($_POST['quantity']) && !empty( $_POST['quantity']) > 1){
            $quantity = sanitize_text_field($_POST['quantity']);
        }
        $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
        $added_to_cart = false;
        if( $passed_validation ){
            $added_to_cart = $cart->add_to_cart(
                    $product_id,
                    $quantity,
                    0,
                    [],
                    ['form_entry' => $entry, 'fields' => $fields]
                );
        } else {
            $msg = esc_html__('Product did not pass the validation.', 'skywin-hub');
            wpforms()->process->errors[$form_data['id']]['header'] = $msg;
            error_log('WPForms Error: ' . json_encode(wpforms()->process->errors) );
            return $fields;
        }
    }
    if ( !$added_to_cart || isset(wpforms()->process->errors[$form_data['id']]) ) {
        $msg = esc_html__('Could not add this product to cart.', 'skywin-hub');
        wpforms()->process->errors[$form_data['id']]['header'] = $msg;
        error_log('WPForms Error: ' . json_encode(wpforms()->process->errors) );
        return $fields;
    } else {
        wp_safe_redirect(wc_get_checkout_url());
        die();
    }
    return $fields;
}
add_action('wpforms_process', 'wpforms_process', 10, 3);
function field_submit( $submit_text, $form_data ){
    global $product;
    if( !$product ){ return $submit_text; }
    $wpforms_id = $product->get_meta('_wpforms_id');
    if( empty($wpforms_id) || $wpforms_id !== $form_data['id']){ return $submit_text; }
    $add_to_cart_text = get_option('skywin_hub_deposit_add_to_cart_text');
    if( $add_to_cart_text ){
        return $add_to_cart_text;
    }
    return $submit_text;
}
add_filter('wpforms_field_submit', 'field_submit', 10, 2);