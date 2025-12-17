<?php
defined('ABSPATH') || exit;
if (!class_exists('Skywin_Hub')):
	class Skywin_Hub
	{
		protected static $_instance = null;
		private $plugin_basename;
		private $includes_dir;
		private $assets_url;
		private $admin_url;
		private $name;
		private $title;
		private $skywin_deposit_product_id;
		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		public function __construct()
		{
			$this->name = 'skywin-hub';
			$this->title = 'Skywin Hub';
			$this->plugin_basename = plugin_basename(SW_PLUGIN_FILE);
			$this->includes_dir = plugin_dir_path(SW_PLUGIN_FILE) . 'includes/';
			$this->admin_url = trailingslashit(plugins_url('admin', SW_PLUGIN_FILE));
			$this->assets_url = trailingslashit(plugins_url('assets', SW_PLUGIN_FILE));
			$this->skywin_deposit_product_id = get_option('skywin-deposit-product');
			$this->includes();
			$this->add_actions();
			$this->add_filters();
		}
		private function add_actions()
		{
			add_action('activated_plugin', array($this, 'activated_plugin'), 10);
			add_action('deactivated_plugin', array($this, 'deactivated_plugin'), 10);
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'), 9999);
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_styles'), 9999);
		}
		private function add_filters()
		{
			add_filter('plugin_action_links', [$this, 'add_plugin_action_links'], 10, 2);
		}
		public function wp_enqueue_scripts()
		{
			wp_enqueue_script('jquery-ui-autocomplete');
			wp_enqueue_script('skywin-hub', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/js/skywin-hub.js', array('jquery'), null, true);
		}
		public function wp_enqueue_styles()
		{
			wp_enqueue_style('style-css', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/css/style.css');
		}
		public function activated_plugin($base_name)
		{
			if ($base_name === SW_PLUGIN && function_exists('skywin_hub_deposit')) {
				skywin_hub_deposit()->create_product_if_not_exist();
			}
		}
		public function deactivated_plugin($base_name)
		{
			if ($base_name === $this->plugin_basename) {
				delete_option('skywin_hub_deposit_min_amount');
				delete_option('skywin_hub_deposit_max_amount');
				delete_option('skywin_hub_deposit_quick_amounts');
				delete_option('skywin_hub_deposit_quick_amount');
				delete_option('skywin_hub_deposit_amount_selected');
				
				delete_option('skywin_hub_db_host');
				delete_option('skywin_hub_db_name');
				delete_option('skywin_hub_db_port');
				delete_option('skywin_hub_db_username');
				delete_option('skywin_hub_db_password');
				delete_option('skywin_hub_db_is_authorized');

				delete_option('skywin_hub_api_host');
				delete_option('skywin_hub_api_port');
				delete_option('skywin_hub_api_path');
				delete_option('skywin_hub_api_status');
				delete_option('skywin_hub_api_username');
				delete_option('skywin_hub_api_password');
				delete_option('skywin_hub_api_is_authorized');

				delete_option('skywin_hub_google_api_client_id');
				delete_option('skywin_hub_google_api_client_secret');
				delete_option('skywin_hub_google_api_redirect_uri');
				delete_option('skywin_hub_google_api_access_token');
				delete_option('skywin_hub_google_api_is_authorized');
				if( function_exists('skywin_hub_deposit') ){
					skywin_hub_deposit()->remove_product();
				}
			}
		}
		private function get_skywin_accounts()
		{
			$accounts = skywin_hub_db()->accounts();
			return $accounts;
		}
		public function get_db_status()
		{
			$status = skywin_hub_db()->status();
			$connected = false;
			if (!is_wp_error($status)) {
				$connected = true;
			} else {
				$msg = $status->get_error_message();
			}
			return $connected;
		}
		public function get_api_status()
		{
			$status = skywin_hub_api()->status();
			$connected = false;
			if (!is_wp_error($status)) {
				$connected = true;
			} else {
				$msg = $status->get_error_message();
			}
			return $connected;
		}
		public function ajax_get_skywin_accounts()
		{
			// check_ajax_referer('ajax_get_skywin_accounts_nonce', 'nonce');
			
			// $items = [];
			// if( isset($_POST['terms']) && !empty($_POST['terms']) ){
			// 	$terms = esc_sql($_POST['terms']);
			// } else {
			// 	wp_send_json([]);
			// };

			
			
			// if ( is_user_logged_in() ) {
			// 	$items = skywin_hub_db()->accounts($terms);
			// } else {
			// 	if( isset($terms) && !empty($terms) ){
			// 		$is_email = filter_var($terms, FILTER_VALIDATE_EMAIL);
			// 		if ( $is_email ) {
			// 			$items = skywin_hub_db()->get_account_by_email($terms);
						
			// 		} else {
			// 			$items = skywin_hub_db()->get_account_by_memberno($terms);
			// 		}
					
			// 	}
			// }
			// $results = array();
			// if ( is_wp_error($items) || !$items) {
			// 	wp_send_json([]);
			// 	die();
			// }
			// $terms = explode(' ', $terms);
			// foreach ($items as $key => $item) {
			// 	$fields = array(
			// 		$item['Club'],
			// 		$item['InternalNo'],
			// 		$item['AccountNo'],
			// 		$item['MemberNo'],
			// 		$item['ExternalMemberNo'],
			// 		$item['FirstName'],
			// 		$item['LastName'],
			// 		$item['Emailaddress'],
			// 		$item['NickName'],
			// 		$item['PhoneNo'],
			// 	);

			// 	$regex = [];

			// 	foreach ($terms as $key => $term) {
			// 		$regex[] = "\b(?=\w)$term.*";
			// 	}
				
			// 	$pattern = '/' . implode('', $regex) . '/i';
			// 	$subject = implode(' ', $fields);

			// 	preg_match($pattern, $subject, $matches);

			// 	if ($matches) {
			// 		if (isset($item['MemberNo']) && !empty($item['MemberNo'])) {
			// 			$memberno = $item['MemberNo'];
			// 		} else {
			// 			$memberno = '<' . $item['ExternalMemberNo'] . '>';
			// 		}

			// 		if (isset($item['Club']) && !empty($item['Club'])) {
			// 			$club = $item['Club'];
			// 		} else {
			// 			$club = 'N/A';
			// 		}

			// 		$results[] = array(
			// 			'value' => $club . ' ' . $memberno . ' ' . $item['FirstName'] . ' ' . $item['LastName'],
			// 			'label' => $club . ' ' . $memberno . ' ' . $item['FirstName'] . ' ' . $item['LastName'],
			// 			'data' => array(
			// 				'Club' => $item['Club'],
			// 				'InternalNo' => $item['InternalNo'],
			// 				'AccountNo' => $item['AccountNo'],
			// 				'MemberNo' => $item['MemberNo'],
			// 				'ExternalMemberNo' => $item['ExternalMemberNo'],
			// 				'FirstName' => $item['FirstName'],
			// 				'LastName' => $item['LastName'],
			// 				'PhoneNo' => $item['PhoneNo'],
			// 				'Emailaddress' => $item['Emailaddress'],
			// 			)
			// 		);
			// 	}
			// }
			// $results = array_slice($results, 0, 5);
			// wp_send_json($items);
			// die();
		}
		public function ajax_get_api_status()
		{
			check_ajax_referer('ajax_get_skywin_api_status_nonce', '_ajax_nonce');
			$result = $this->get_api_status();
			$status = false;
			if (!is_wp_error($result) && is_array($result)) {
				$status = true;
			}
			wp_send_json($status);
			die();
		}
		public function ajax_get_db_status()
		{
			check_ajax_referer('ajax_get_skywin_db_status_nonce', '_ajax_nonce');
			$status = $this->get_db_status();
			wp_send_json($status);
			die();
		}
		public function add_plugin_action_links($plugin_actions, $plugin_file)
		{
			$new_actions = array();
			if (SW_PLUGIN === $plugin_file) {
				$new_actions['cl_settings'] = sprintf(__('<a href="%s">Settings</a>', 'skywin-hub'), esc_url(admin_url('admin.php?page=wc-settings&tab=skywin_hub')));
			}
			return array_merge($new_actions, $plugin_actions);
		}
		public function includes()
		{
			
			include_once SW_ABSPATH . 'includes/class-skywin-hub-db.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-api.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-gapi.php';

			include_once SW_ABSPATH . 'includes/class-skywin-hub-deposit.php';

			include_once SW_ABSPATH . 'includes/class-skywin-hub-shortcodes.php';
			include_once SW_ABSPATH . 'includes/shortcodes/class-skywin-hub-shortcode-deposit.php';

			include_once SW_ABSPATH . 'includes/functions.php';
			include_once SW_ABSPATH . 'includes/functions-wc.php';
			include_once SW_ABSPATH . 'includes/functions-um.php';
			include_once SW_ABSPATH . 'includes/functions-tribe.php';
			include_once SW_ABSPATH . 'includes/functions-wpf.php';
			include_once SW_ABSPATH . 'includes/functions-mec.php';

			include_once SW_ABSPATH . 'includes/admin/class-skywin-settings.php';
		}
		private function is_skywin_deposit_product_in_cart()
		{
			global $order;

			$product_id = $this->skywin_deposit_product_id;
			foreach (WC()->cart->get_cart() as $cart_item) {
				$product_in_cart = $cart_item['product_id'];
				if ($product_in_cart == $product_id)
					$in_cart = true;
			}
			return $in_cart;
		}
		public function is_front_end()
		{
			return !is_admin() || defined('DOING_AJAX');
		}
	}
endif;