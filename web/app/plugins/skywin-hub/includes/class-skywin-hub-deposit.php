<?php
defined('ABSPATH') || exit;

if (!class_exists('Skywin_Hub_Deposit_Product')):
	class Skywin_Hub_Deposit_Product
	{
		protected static $_instance = null;
		public $id = null;
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
			$this->slug = 'skywin-deposit-product';
			$this->name = 'skywin-deposit-product';
			$this->id = get_option($this->name, false);

			$this->includes();
			$this->add_actions();
		}
		private function includes()
		{
			include_once SW_ABSPATH . 'includes/shortcodes/class-skywin-hub-shortcode-deposit.php';
		}
		private function add_actions()
		{
			add_action('activated_plugin', array($this, 'activated_plugin'), 10);
			add_action('deactivated_plugin', array($this, 'deactivated_plugin'), 10);
			add_action('wp_ajax_get_skywin_api_status', array($this, 'ajax_get_skywin_api_status'), 10);
			add_action('wp_ajax_nopriv_get_skywin_api_status', array($this, 'ajax_get_skywin_api_status'), 10);
			add_action('wp_ajax_get_skywin_db_status', array($this, 'ajax_get_skywin_db_status'), 10);
			add_action('wp_ajax_nopriv_get_skywin_db_status', array($this, 'ajax_get_skywin_db_status'), 10);
			add_action('wp_ajax_get_skywin_accounts', array($this, 'ajax_get_skywin_accounts'), 10);
			add_action('wp_ajax_nopriv_get_skywin_accounts', array($this, 'ajax_get_skywin_accounts'), 10);
			add_action('skywin_hub_create_deposit', array($this, 'skywin_hub_create_deposit'), 10, 1);
		}
		public function activated_plugin($base_name)
		{
			if ($base_name === SW_PLUGIN) {
				$this->create_product_if_not_exist();
			}
		}
		public function deactivated_plugin($base_name)
		{
			if ($base_name === SW_PLUGIN) {
				$this->remove_product();
				delete_option('skywin-deposit-product');
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
			return SKYWIN_HUB()->ajax_get_skywin_accounts();
		}
		public function get_id()
		{
			return $this->id;
		}
		public function get_product()
		{
			return wc_get_product($this->get_id());
		}
		public function skywin_hub_create_deposit($data)
		{
			$order = wc_get_order($data['order_id']);
			$result = skywin_hub_api()->create_transaction($data['body']);

			if (!is_wp_error($result) && isset($result["transNo"]) && is_numeric($result["transNo"])) {
				$admin_note = "Skywin transfer completed: " . $result["displayName"];
				$order->add_order_note($admin_note);
				wc_update_order_item_meta($data["item_id"], 'skywin_trans_no', $result["transNo"], true);
				wc_update_order_item_meta($data["item_id"], 'skywin_account_no', $result["accountNo"], true);
				$order->update_status('completed');
			} elseif (is_wp_error($result)) {
				error_log("Something went wrong: " . json_encode($result));
				$admin_note = "Something went wrong: " . json_encode($result);
				$order->add_order_note($admin_note);
				$order->update_status('cancelled');
				wc_add_notice(__('Unable to create deposit. Please try again later.', 'skywin-hub'), 'error');
			} else {
				error_log("Something went wrong: " . json_encode($result));
				$admin_note = "Something went wrong: " . json_encode($result);
				$order->add_order_note($admin_note);
				$order->update_status('cancelled');
				wc_add_notice(__('Unable to create deposit. Please try again later.', 'skywin-hub'), 'error');
			}
		}
		private function create_product_if_not_exist()
		{
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

			$product->set_virtual(true);
			$product->set_downloadable(false);
			$product->set_reviews_allowed(false);
			$product->set_catalog_visibility('hidden');
			$product->update_meta_data('paymentType', 'webpay');

			$product->save();

			update_option($this->name, $product->get_id());
		}
		private function remove_product()
		{
			$product = $this->get_product();
			if ($product) {
				$product->delete(true);
			}
		}
	}
	function skywin_hub_deposit_product()
	{
		return Skywin_Hub_Deposit_Product::instance();
	}
	skywin_hub_deposit_product();
endif;