<?php
defined('ABSPATH') || exit;

if (!class_exists('Skywin_Hub_Shortcode_Nexi_Checkout')):
	class Skywin_Hub_Shortcode_Nexi_Checkout
	{
		public static function output($args)
		{
			//add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'), 10);

			ob_start();
			load_template(SW_TEMPLATE_PATH . '/template-skywin-nexi-checkout.php', true, $args);
			$html = ob_get_clean();
			return $html;
		}
		public static function enqueue_scripts()
		{
			wp_enqueue_script('jquery-ui-autocomplete');
			wp_enqueue_script('skywin-hub-deposit-js', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/js/skywin-deposit.js', array('jquery'), null, true);
			wp_enqueue_style('skywin-hub-deposit-css', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/css/skywin-deposit.css');
		
			wp_localize_script('skywin-hub-deposit-js', 'ajax_deposit_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'currency' => get_woocommerce_currency(),
				'action' => 'get_skywin_accounts',
				'_ajax_nonce' => wp_create_nonce('ajax_get_skywin_accounts_nonce'),
			));
			wp_localize_script('skywin-hub-deposit-js', 'ajax_add_to_cart_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'action' => 'add_to_cart',
				'_ajax_nonce' => wp_create_nonce('ajax_add_to_cart_nonce'),
			));
			wp_localize_script('skywin-hub-deposit-js', 'ajax_get_skywin_db_status_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'action' => 'get_db_status',
				'_ajax_nonce' => wp_create_nonce('ajax_get_skywin_db_status_nonce'),
			));
			wp_localize_script('skywin-hub-deposit-js', 'ajax_get_skywin_api_status_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'action' => 'get_api_status',
				'_ajax_nonce' => wp_create_nonce('ajax_get_skywin_api_status_nonce'),
			));
		}
	}
endif;