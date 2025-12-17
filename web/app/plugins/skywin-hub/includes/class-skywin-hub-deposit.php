<?php
defined('ABSPATH') || exit;

if (!class_exists('Skywin_Hub_Deposit')):
	class Skywin_Hub_Deposit
	{
		protected static $_instance = null;
		private $id = null;
		private $slug = null;
		private $name = null;
		private $title = null;
		private $api_status = null;
		private $db_status = null;
		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		public function __construct()
		{
			$this->title = 'Deposit';
			$this->slug = 'deposit';
			$this->name = 'deposit';
			$this->id = get_option($this->name . '_id');
			$this->add_actions();
		}
		private function add_actions()
		{
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 9999);
			add_action('wp_ajax_get_skywin_api_status', array($this, 'ajax_get_skywin_api_status'), 10);
			add_action('wp_ajax_nopriv_get_skywin_api_status', array($this, 'ajax_get_skywin_api_status'), 10);
			add_action('wp_ajax_get_skywin_db_status', array($this, 'ajax_get_skywin_db_status'), 10);
			add_action('wp_ajax_nopriv_get_skywin_db_status', array($this, 'ajax_get_skywin_db_status'), 10);
			add_action('wp_ajax_get_skywin_accounts', array($this, 'ajax_get_skywin_accounts'), 10);
			add_action('wp_ajax_nopriv_get_skywin_accounts', array($this, 'ajax_get_skywin_accounts'), 10);
			add_action('wp_ajax_add_to_cart', array($this, 'ajax_add_to_cart'), 10);
			add_action('wp_ajax_nopriv_add_to_cart', array($this, 'ajax_add_to_cart'), 10);
			add_action('wp_ajax_remove_from_cart', array($this, 'ajax_remove_from_cart'), 10);
			add_action('wp_ajax_nopriv_remove_from_cart', array($this, 'ajax_remove_from_cart'), 10);
			add_action('skywin_hub_create_deposit', array($this, 'skywin_hub_create_deposit'), 10, 1);
			add_action('template_redirect', array($this, 'maybe_remove_product_from_cart'), 9999);
			add_action('template_redirect', array($this, 'check_status'));
		}
		private function get_status(){
			$db_status = skywin_hub_db()->status();
			if( is_wp_error($db_status) ){
				add_filter( 'woocommerce_is_purchasable', '__return_false');
        		if ( function_exists( 'wc_add_notice' ) && function_exists( 'WC' ) && WC() && isset( WC()->session ) ) {
					wc_add_notice('Service is not available, please try again later.', 'error');
				}
				return false;
			}
			$api_status = skywin_hub_api()->status();
			if( is_wp_error($api_status) ){
				add_filter( 'woocommerce_is_purchasable', '__return_false');
        		if ( function_exists( 'wc_add_notice' ) && function_exists( 'WC' ) && WC() && isset( WC()->session ) ) {
					wc_add_notice('Service is not available, please try again later.', 'error');
				}
				return false;
			}
			return true;
		}
		public function check_status(){			
			if ( is_admin() || defined( 'REST_REQUEST' ) && REST_REQUEST || function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() || defined( 'WP_CLI' ) && WP_CLI ) {
				return false;
			}			
			$deposit_id = skywin_hub_deposit()->get_id();
			$is_deposit_product_page = false;

			if ( is_singular( 'product' ) ) {
				$queried = get_queried_object_id();
				if ( $queried && intval( $queried ) === intval( $deposit_id ) ) {
					$is_deposit_product_page = true;
				}
			}
			if ( !is_page( 'deposit' ) && !$is_deposit_product_page ) {
				return false;
			}
			if( !$this->get_status() ){
				return false;
			}
		}
		public function enqueue_scripts()
		{
			global $product;
			$is_product = is_a($product, 'WC_Product') ? true : false;
			
			$is_deposit_product = false;
			if ( !$is_product || is_cart() || is_shop() || is_product_category() || is_product_tag() ) {
				return;
			}
			if( !empty($product) && ( $product->get_id() == $this->get_id() ) ){
				$is_deposit_product = true;
			}
			
			if( is_page('deposit') || $is_deposit_product ){	
				wp_enqueue_script('skywin-deposit', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/js/skywin-deposit.js', array('jquery'), null, true);
				wp_enqueue_style('skywin-deposit', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/css/skywin-deposit.css');

				wp_localize_script('skywin-deposit', 'ajax_deposit_params', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'currency' => get_woocommerce_currency_symbol(),
					'add_to_cart_text' => get_option('skywin_hub_deposit_add_to_cart_text'),
					'min_amount' => get_option('skywin_hub_deposit_min_amount'),
					'max_amount' => get_option('skywin_hub_deposit_max_amount'),
					'action' => 'get_skywin_accounts',
					'_ajax_nonce' => wp_create_nonce('ajax_get_skywin_accounts_nonce'),
				));
				wp_localize_script('skywin-deposit', 'ajax_add_to_cart_params', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'action' => 'add_to_cart',
					'_ajax_nonce' => wp_create_nonce('ajax_add_to_cart_nonce'),
				));
			}
		}
		public function ajax_get_skywin_api_status()
		{
			return SKYWIN_HUB()->ajax_get_api_status();
		}
		public function ajax_get_skywin_db_status()
		{
			return SKYWIN_HUB()->ajax_get_db_status();
		}
		public function ajax_get_skywin_accounts()
		{
			check_ajax_referer('ajax_get_skywin_accounts_nonce', 'nonce');
			if ( !isset($_POST['terms']) || empty($_POST['terms']) ) {
				wp_send_json_success([]);
				die();
			}
			$terms = sanitize_text_field($_POST['terms']);

			$results = [];
			if ( current_user_can('manage_options') ) {
				$results = skywin_hub_db()->accounts($terms);
			} else {
				$is_email = filter_var($terms, FILTER_VALIDATE_EMAIL);
				if ($is_email) {
					$results = skywin_hub_db()->get_account_by_email($terms);					
				}
			}
			if ( is_wp_error($results) || !$results) {
				wp_send_json_success($results);
				die();
			}
			
			$accounts = [];
			foreach ($results as $key => $item) {
				if ( !empty($item['MemberNo']) ) {
					$memberno = $item['MemberNo'];
				}elseif( !empty($item['ExternalMemberNo']) ) {
					$memberno = '<' . $item['ExternalMemberNo'] . '>';
				}else{
					$memberno = 'N/A';
				}

				if ( !empty($item['Club']) ) {
					$club = $item['Club'];
				} else {
					$club = 'N/A';
				}

				$accounts[] = array(
					'value' => $item['AccountNo'],
					'label' => $club . ' ' . $memberno . ' ' . $item['FirstName'] . ' ' . $item['LastName'],
				);
			}
			wp_send_json_success($accounts);
			die();
		}
		public function ajax_add_to_cart()
		{
			error_log('ajax_add_to_cart');
			check_ajax_referer('ajax_add_to_cart_nonce', '_ajax_nonce');
			$result = $this->add_to_cart();
			if (!$result) {
				error_log('ajax_add_to_cart error: ' . json_encode($result));
				wp_send_json_error('Det gick åt skogen någonstans');
				die();
			}
			wp_send_json_success($result);
			die();
		}
		public function ajax_remove_from_cart()
		{
			error_log('ajax_remove_from_cart');
			check_ajax_referer('remove_from_cart_nonce', '_ajax_nonce');
			$this->remove_from_cart();
			wp_send_json_success();
			die();
		}
		private function add_to_cart()
		{
			$deposit_product_id = $this->get_id();
			$add_to_cart_product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
			$add_to_cart_quantity = isset($_POST['quantyty']) ? $_POST['quantity'] : 1;

			if ($deposit_product_id !== $add_to_cart_product_id) { return; }
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $add_to_cart_product_id, $add_to_cart_quantity );
			$result = null;
			if ( $passed_validation ) {
				$cart = WC()->cart;
				$cart->empty_cart();
				$result = $cart->add_to_cart( $add_to_cart_product_id, $add_to_cart_quantity  );
			}
			return $result;
		}
		public function remove_from_cart($cart_item_key = null)
		{
			$this->maybe_remove_product_from_cart();
		}
		public function maybe_remove_product_from_cart()
		{
			global $product;

			$cart = WC()->cart;
			$deposit_product_id = $this->get_id();
			if( is_product() || is_admin() || wp_doing_ajax() ){
				return;
			}
			
			$cart_items = $cart->cart_contents;
			foreach ($cart_items as $cart_item_key => $cart_item) {
				if ( $deposit_product_id == $cart_item['product_id'] ) {
					if ( !isset($cart_item['accountNo']) || empty($cart_item['accountNo']) ) {
						error_log('Remove dummy deposit product...');
						$cart->remove_cart_item($cart_item_key);
					}
				}
			}
		}
		public function get_id()
		{
			$product_id = $this->id ? $this->id : false;
			return $product_id;
		}
		public function get_product()
		{
			return wc_get_product($this->get_id());
		}
		public function skywin_hub_create_deposit($data)
		{
			$order = wc_get_order($data['order_id']);
			$result = skywin_hub_api()->create_transaction($data);
			if (isset($result["transNo"]) && is_numeric($result["transNo"])) {
				$admin_note = "Skywin transfer completed: " . $result["displayName"];
				$order->add_order_note($admin_note);
				wc_update_order_item_meta($data["item_id"], 'transNo', $result["transNo"], true);
				wc_update_order_item_meta($data["item_id"], 'accountNo', $result["accountNo"], true);
				$order->update_status('completed');
			} elseif (is_wp_error($result)) {
				error_log("Something went wrong 1: " . json_encode($result));
				$admin_note = "Something went wrong 1: " . json_encode($result);
				$order->add_order_note($admin_note);
				$order->update_status('cancelled');
				wc_add_notice(__('Unable to create deposit. Please try again later.', 'skywin-hub'), 'error');
			} else {
				error_log("Something went wrong 2: " . json_encode($result));
				$admin_note = "Something went wrong 2: " . json_encode($result);
				$order->add_order_note($admin_note);
				$order->update_status('cancelled');
				wc_add_notice(__('Unable to create deposit. Please try again later.', 'skywin-hub'), 'error');
			}
		}
		public function create_product_if_not_exist()
		{
			if (!class_exists('WC_Product_Simple')) {
				return;
			}
			error_log('create_product_if_not_exist');
			if (!wc_get_product($this->get_id())) {
				$this->create_product();
			}
		}
		private function create_product()
		{
			
			$product = new WC_Product_Simple();
			$product->set_name($this->title);
			$product->set_slug($this->name);
			$product->set_sku($this->name);
			$product->set_status('publish');
			$product->set_sold_individually(true);
			$product->set_regular_price(300);

			$product->set_virtual(true);
			$product->set_downloadable(false);
			$product->set_reviews_allowed(false);
			$product->set_catalog_visibility('hidden');
			$product->update_meta_data('paymentType', 'webpay');

			$product->save();

			update_option($this->name . '_id', $product->get_id());
		}

		private function update_product()
		{
			
			$product = wc_get_product($this->get_id());
			$product->set_name($this->title);
			$product->set_slug($this->name);
			$product->set_sku($this->name);
			$product->set_status('publish');
			$product->set_sold_individually(true);
			$product->set_regular_price(300);

			$product->set_virtual(true);
			$product->set_downloadable(false);
			$product->set_reviews_allowed(false);
			$product->set_catalog_visibility('hidden');
			$product->update_meta_data('paymentType', 'webpay');

			$product->save();
		}




		public function remove_product()
		{
			error_log('remove_product()');
			$product = $this->get_product();
			if ($product) {
				$product->delete(true);
			}
		}
	}
	function skywin_hub_deposit()
	{
		return Skywin_Hub_Deposit::instance();
	}
	skywin_hub_deposit();
endif;