<?php
defined('ABSPATH') || exit;

if (!class_exists('Skywin_Hub_Shortcode_Deposit')):
	class Skywin_Hub_Shortcode_Deposit
	{
		public static function checkout_popup(){
			
			ob_start();
			?>
			<div class="skywin_hub-checkout-modal">
				<div class="skywin_hub-close-checkout-modal">
					<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M10.0303 8.96965C9.73741 8.67676 9.26253 8.67676 8.96964 8.96965C8.67675 9.26255 8.67675 9.73742 8.96964 10.0303L10.9393 12L8.96966 13.9697C8.67677 14.2625 8.67677 14.7374 8.96966 15.0303C9.26255 15.3232 9.73743 15.3232 10.0303 15.0303L12 13.0607L13.9696 15.0303C14.2625 15.3232 14.7374 15.3232 15.0303 15.0303C15.3232 14.7374 15.3232 14.2625 15.0303 13.9696L13.0606 12L15.0303 10.0303C15.3232 9.73744 15.3232 9.26257 15.0303 8.96968C14.7374 8.67678 14.2625 8.67678 13.9696 8.96968L12 10.9393L10.0303 8.96965Z" fill="#ffffffff"/>
						<path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C6.06294 1.25 1.25 6.06294 1.25 12C1.25 17.9371 6.06294 22.75 12 22.75C17.9371 22.75 22.75 17.9371 22.75 12C22.75 6.06294 17.9371 1.25 12 1.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75C17.1086 2.75 21.25 6.89137 21.25 12C21.25 17.1086 17.1086 21.25 12 21.25C6.89137 21.25 2.75 17.1086 2.75 12Z" fill="#ffffffff"/>
					</svg>
				</div>
				<div class="skywin_hub-checkout-modal-box">
					<?php echo do_shortcode('[woocommerce_checkout]'); ?>
				</div>
			</div>
			<?php
			$html = ob_get_clean();
			echo $html;
		}
		public static function output_fields($args)
		{
			if ( is_admin() || defined( 'REST_REQUEST' ) && REST_REQUEST || function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() || defined( 'WP_CLI' ) && WP_CLI ) {
				return '';
			}
			if ( function_exists( 'is_preview' ) && is_preview() ) {
				return '';
			}
			$skywin_deposit_product = skywin_hub_deposit()->get_product();
			$product_id = $skywin_deposit_product->get_id();
			$currency = get_woocommerce_currency_symbol();
			$min_amount = get_option('skywin_hub_deposit_min_amount');
            $max_amount = get_option('skywin_hub_deposit_max_amount');
			$accountNo = '';
			if( is_user_logged_in() ){
				$user_id = get_current_user_id();
				$user_meta = get_user_meta($user_id);
				$accountNo = $user_meta['accountNo'] ?? '';
			}
			if( isset($_POST['accountNo']) && !empty($_POST['accountNo']) ){
				$accountNo = esc_attr($_POST['accountNo']);
			}
			$amount = isset($_POST['amount']) ? esc_attr($_POST['amount']) : $skywin_deposit_product->get_price();
			$search_account = isset($_POST['search_account']) ? esc_attr($_POST['search_account']) : '';
			$quickAmounts = get_option('skywin_hub_deposit_quick_amounts') == '' ? [] : get_option('skywin_hub_deposit_quick_amounts');
			if(!empty($quickAmounts)){
				$quickAmounts = explode(',',$quickAmounts );
			}
			$args = [];
			$args = array(
				'product_id' => $product_id,
				'currency' => $currency,
				'amount' => $amount,
				'min_amount' => $min_amount,
				'max_amount' => $max_amount,
				'search_account' => $search_account,
				'accountNo' => $accountNo,
				'quickAmounts' => $quickAmounts,
				'nonce' => wp_create_nonce('skywin-add-to-cart-nonce')
			);
			add_action('wp_footer', array(__CLASS__, 'checkout_popup'), 10);
			$cart = WC()->cart;
			$cart->add_to_cart($product_id);
			ob_start();
			load_template(SW_TEMPLATE_PATH . '/template-skywin-deposit-fields.php', true, $args);
			$html = ob_get_clean();
			return $html;
		}
		public static function output_form($args)
		{
			if ( is_admin() || defined( 'REST_REQUEST' ) && REST_REQUEST || function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() || defined( 'WP_CLI' ) && WP_CLI ) {
				return;
			}
			
			$product = skywin_hub_deposit()->get_product();
			$GLOBALS['product'] = $product;
			$args = array(
				'product_id' => $product->get_id(),
				'product' => $product
			);
			ob_start();
			load_template(SW_TEMPLATE_PATH . '/template-skywin-deposit-form.php', true, $args);
			$html = ob_get_clean();
			return $html;
		}
	}
endif;