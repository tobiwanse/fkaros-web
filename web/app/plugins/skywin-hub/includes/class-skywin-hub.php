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
			$this->title = __('Skywin Hub', 'skywin-hub');
			$this->plugin_basename = plugin_basename(SW_PLUGIN_FILE);
			$this->includes_dir = plugin_dir_path(SW_PLUGIN_FILE) . 'includes/';
			$this->admin_url = trailingslashit(plugins_url('admin', SW_PLUGIN_FILE));
			$this->assets_url = trailingslashit(plugins_url('assets', SW_PLUGIN_FILE));
			$this->skywin_deposit_product_id = get_option('skywin-deposit-product');
			$this->define_constants();
			$this->includes();
			$this->add_actions();
			$this->add_filters();
		}
		private function define_constants()
		{
			define('SW_ABSPATH', dirname(SW_PLUGIN_FILE) . '/');
			define('SW_TEMPLATE_PATH', dirname(SW_PLUGIN_FILE) . '/templates');
			define('SW_PLUGIN', plugin_basename(SW_PLUGIN_FILE));
		}
		private function add_actions()
		{
			add_action('activated_plugin', array($this, 'activated_plugin'));
			add_action('deactivated_plugin', array($this, 'deactivated_plugin'));
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'), 10, 1);
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_styles'), 9999, 1);
			add_action('wp_ajax_get_db_status', array($this, 'ajax_get_db_status'), 10);
			add_action('wp_ajax_get_api_status', array($this, 'ajax_get_api_status'), 10);
			add_action('wp_footer',array($this, 'footer'));
		}
		public function footer(){
			if ( $this->is_front_end() && is_home() ) {
				ob_start();
				include_once SW_ABSPATH . 'includes/footer.php';
				$html = ob_get_clean();
				echo $html;
			}
		}
		private function add_filters()
		{
			add_filter('plugin_action_links', [$this, 'add_plugin_action_links'], 10, 2);
		}
		public function wp_enqueue_scripts()
		{
			wp_enqueue_script('skywin-hub-js', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/js/skywin-hub.js', array('jquery'), null, true);
		}
		public function wp_enqueue_styles()
		{
			
			wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
			wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
			wp_enqueue_style('style-css', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/css/style.css');
		}
		public function activated_plugin($base_name)
		{
		}
		public function deactivated_plugin($base_name)
		{
			if ($base_name === $this->plugin_basename) {
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
			check_ajax_referer('ajax_get_skywin_accounts_nonce', 'nonce');
			$terms = $_REQUEST['terms'];
			$terms = explode(' ', $terms);
			$items = $this->get_skywin_accounts();
			$results = array();

			if (is_wp_error($items)) {
				wp_send_json([]);
				wp_die();
			}

			foreach ($items as $key => $item) {
				$fields = array(
					$item['Club'],
					$item['AccountNo'],
					$item['MemberNo'],
					$item['ExternalMemberNo'],
					$item['FirstName'],
					$item['LastName'],
					$item['Emailaddress'],
					$item['NickName'],
					$item['PhoneNo'],
				);

				$regex = [];

				foreach ($terms as $key => $term) {
					$regex[] = '\b(?=\w)' . $term . '.*';
				}

				$pattern = '/' . implode('', $regex) . '/i';
				$subject = implode(' ', $fields);

				preg_match($pattern, $subject, $matches);

				if ($matches) {
					if (isset($item['MemberNo']) && !empty($item['MemberNo'])) {
						$memberno = $item['MemberNo'];
					} else {
						$memberno = '<' . $item['ExternalMemberNo'] . '>';
					}

					if (isset($item['Club']) && !empty($item['Club'])) {
						$club = $item['Club'];
					} else {
						$club = 'N/A';
					}

					$results[] = array(
						'value' => $club . ' ' . $memberno . ' ' . $item['FirstName'] . ' ' . $item['LastName'],
						'label' => $club . ' ' . $memberno . ' ' . $item['FirstName'] . ' ' . $item['LastName'],
						'data' => array(
							'Club' => $item['Club'],
							'AccountNo' => $item['AccountNo'],
							'MemberNo' => $item['MemberNo'],
							'ExternalMemberNo' => $item['ExternalMemberNo'],
							'FirstName' => $item['FirstName'],
							'LastName' => $item['LastName'],
							'PhoneNo' => $item['PhoneNo']
						)
					);
				}
			}
			$results = array_slice($results, 0, 5);
			wp_send_json($results);
			wp_die();
		}
		public function ajax_get_api_status()
		{
			check_ajax_referer('ajax_get_skywin_api_status_nonce', '_ajax_nonce');
			$status = $this->get_api_status();
			wp_send_json($status);
			wp_die();
		}
		public function ajax_get_db_status()
		{
			check_ajax_referer('ajax_get_skywin_db_status_nonce', '_ajax_nonce');
			$status = $this->get_db_status();
			wp_send_json($status);
			wp_die();
		}
		public function add_plugin_action_links($plugin_actions, $plugin_file)
		{
			$new_actions = array();
			if (SW_PLUGIN === $plugin_file) {
				$new_actions['cl_settings'] = sprintf(__('<a href="%s">Settings</a>', 'skywin-hub'), esc_url(admin_url('admin.php?page=wc-settings&tab=skywin_hub')));
			}
			return array_merge($new_actions, $plugin_actions);
		}
		private function includes()
		{
			include_once SW_ABSPATH . 'includes/admin/class-skywin-settings.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-db.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-api.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-gapi.php';
			
			include_once SW_ABSPATH . 'includes/class-skywin-hub-calendar.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-wishlist.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-shortcodes.php';

			include_once SW_ABSPATH . 'includes/class-skywin-hub-deposit.php';
			include_once SW_ABSPATH . 'includes/functions-wc.php';
			include_once SW_ABSPATH . 'includes/functions-um.php';
			include_once SW_ABSPATH . 'includes/functions-tribe.php';
			include_once SW_ABSPATH . 'includes/functions-wpf.php';
		}
		private function is_skywin_deposit_product_in_cart()
		{
			global $order;

			if (!is_object($order) || !isset($order->id)) {
				//return false;
			}
			$product_id = $this->skywin_deposit_product_id;
			foreach (WC()->cart->get_cart() as $cart_item) {
				$product_in_cart = $cart_item['product_id'];
				if ($product_in_cart == $product_id)
					$in_cart = true;
			}
			return $in_cart;
		}
		private function encrypt_decrypt($stringToHandle = "", $encryptDecrypt = 'e')
		{
			$output = null;
			$secret_key = 'hgfdr3ys%h';
			$secret_iv = 'e*rt"dh46Gv';
			if (defined('AUTH_KEY')) {
				$secret_key = AUTH_KEY;
			}
			if (defined('AUTH_SALT')) {
				$secret_iv = AUTH_SALT;
			}
			$key = hash('sha256', $secret_key);
			$iv = substr(hash('sha256', $secret_iv), 0, 16); // using salt technique
			if ($encryptDecrypt == 'e') {
				$output = base64_encode(openssl_encrypt($stringToHandle, "AES-256-CBC", $key, 0, $iv));
			} else if ($encryptDecrypt == 'd') {
				$output = openssl_decrypt(base64_decode($stringToHandle), "AES-256-CBC", $key, 0, $iv);
			}
			return $output;
		}
		public function encrypt($string)
		{
			if (!isset($string) || empty($string)) {
				return '';
			}
			$encryptedString = $this->encrypt_decrypt($string, 'e');
			return $encryptedString;
		}
		public function decrypt($string)
		{
			$decryptedString = $this->encrypt_decrypt($string, 'd');
			return $decryptedString;
		}
		public function is_front_end()
		{
			return !is_admin() || defined('DOING_AJAX');
		}
	}
endif;