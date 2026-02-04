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
		public $page_slug = null;
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
			$this->page_slug = 'insattning';
			$this->id = get_option($this->name . '_id');
			$this->add_actions();
		}
		private function add_actions()
		{
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 9999);
			add_action('wp_ajax_get_skywin_accounts', array($this, 'ajax_get_skywin_accounts'), 10);
			add_action('wp_ajax_nopriv_get_skywin_accounts', array($this, 'ajax_get_skywin_accounts'), 10);
			add_action('skywin_hub_create_deposit', array($this, 'skywin_hub_create_deposit'), 10, 1);
		}
		public function enqueue_scripts()
		{
			global $product;
			$is_product = is_a($product, 'WC_Product') ? true : false;
			$is_deposit_product = false;
			if ( is_cart() || is_shop() || is_product_category() || is_product_tag() ) {
				return;
			}
			if( $is_product && !empty($product) && ( $product->get_id() == $this->get_id() ) ){
				$is_deposit_product = true;
			}
			if( is_page($this->page_slug) || $is_deposit_product){
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
			}
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
					'emailAddress' => $item['Emailaddress'],
				);
			}
			wp_send_json_success($accounts);
			die();
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
				$admin_note = "Something went wrong 1: " . json_encode($result);
				$order->add_order_note($admin_note);
				$order->update_status('cancelled');
				wc_add_notice(__('Unable to create deposit. Please try again later.', 'skywin-hub'), 'error');
			} else {
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
			$product->set_catalog_visibility('hidden');
			$product->update_meta_data('paymentType', 'webpay');

			$product->save();

			update_option($this->name . '_id', $product->get_id());
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