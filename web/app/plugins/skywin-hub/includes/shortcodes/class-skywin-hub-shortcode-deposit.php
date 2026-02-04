<?php
defined('ABSPATH') || exit;

if (!class_exists('Skywin_Hub_Shortcode_Deposit')):
	class Skywin_Hub_Shortcode_Deposit
	{
		public static function output_fields($args)
		{
			global $product;
			$skywin_deposit_product = skywin_hub_deposit()->get_product();
			$product_id = $skywin_deposit_product->get_id();
			$currency = get_woocommerce_currency_symbol();
			$min_amount = get_option('skywin_hub_deposit_min_amount');
            $max_amount = get_option('skywin_hub_deposit_max_amount');
			$quickAmounts = get_option('skywin_hub_deposit_quick_amounts') == '' ? [] : get_option('skywin_hub_deposit_quick_amounts');
			if(!empty($quickAmounts)){
				$quickAmounts = explode(',',$quickAmounts );
			}
			$args = [];
			$args = array(
				'product_id' => $product_id,
				'currency' => $currency,
				'min_amount' => $min_amount,
				'max_amount' => $max_amount,
				'quickAmounts' => $quickAmounts,
				'nonce' => wp_create_nonce('skywin-add-to-cart-nonce')
			);
			ob_start();
			load_template(SW_TEMPLATE_PATH . '/template-skywin-deposit-fields.php', true, $args);
			$html = ob_get_clean();
			return $html;
		}
	}
endif;