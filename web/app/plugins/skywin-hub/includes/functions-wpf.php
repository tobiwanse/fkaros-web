<?php
if (!defined('ABSPATH')) { exit; }
/**
 * Functions for WPForms integration with WooCommerce.
 *
 * @package SkywinHub
 */

add_action('wpforms_display_field_before', function ($field, $field_atts) {
    if ($field['type'] == 'hidden' && $field['label'] == 'product') {
        $product_id = wc_get_product_id_by_sku($field['default_value']);
        if (!$product_id) {
            return false;
        }
        $product = wc_get_product($product_id);
        $custom_value = get_post_meta( $product_id, 'wpform_id', true );
        error_log($custom_value);
        if (!$product) {
            return false;
        }
        $stock_quantity = $product->get_stock_quantity();
        $price_html = $product->get_price_html();
        echo '<style>
                    .wpforms-field-hidden { display: block !important; }
                    .wpforms-field-hidden input[type="hidden"] { display: none !important; }
              </style>';
        echo 'Price: ' . $price_html . '<br>';

        if ($stock_quantity === 0 || !$product->is_in_stock()) {
            echo 'In stock: Out of stock';
        } elseif ($stock_quantity > 0) {
            echo 'In stock: ' . $stock_quantity;
        }
    }
}, 10, 2);
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
function wpforms_process($fields, $entry, $form_data)
{
    $product_id = null;
    foreach ($fields as $key => $field) {
        if ($field['type'] == 'hidden' && $field['name'] == 'product') {
            $product_id = wc_get_product_id_by_sku($field['value']);
            break 1;
        }
    }

    if (!$product_id) {
        return $fields;
    }

    $product = wc_get_product($product_id);
    WC()->cart->empty_cart();
    if (!$product->is_in_stock()) {
        wpforms()->process->errors[$form_data['id']]['header'] = esc_html__('This product is out of stock.', 'wpforms');
        return $fields;
    }
    $added_to_cart = WC()->cart->add_to_cart($product_id, 1, 0, [], [
        'form_id' => $form_data['id'],
        'fields' => $fields,
    ]);
    if (!$added_to_cart) {
        wpforms()->process->errors[$form_data['id']]['header'] = esc_html__('Could not add this product to cart.', 'wpforms');
    } else {
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
    return $fields;
}
add_action('wpforms_process', 'wpforms_process', 10, 3);