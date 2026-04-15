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
				if( function_exists('skywin_hub_deposit') ){
					skywin_hub_deposit()->remove_product();
				}
				Skywin_Hub_Push::deactivate();
			}
		}
		public function add_plugin_action_links($plugin_actions, $plugin_file)
		{
			$new_actions = array();
			if (SW_PLUGIN === $plugin_file) {
				$new_actions['cl_settings'] = sprintf(__('<a href="%s">Settings</a>', 'skywin-hub'), esc_url(admin_url('admin.php?page=skywin_hub&tab=skywin_hub')));
			}
			return array_merge($new_actions, $plugin_actions);
		}
		public function includes()
		{
			include_once SW_ABSPATH . 'includes/functions.php';

			include_once SW_ABSPATH . 'includes/class-skywin-hub-db.php';
			include_once SW_ABSPATH . 'includes/class-skywin-hub-api.php';

			include_once SW_ABSPATH . 'includes/class-skywin-hub-deposit.php';

			include_once SW_ABSPATH . 'includes/class-skywin-hub-shortcodes.php';
			include_once SW_ABSPATH . 'includes/shortcodes/class-skywin-hub-shortcode-deposit.php';
			include_once SW_ABSPATH . 'includes/shortcodes/class-skywin-hub-shortcode-skyview.php';

			include_once SW_ABSPATH . 'includes/functions-wc.php';
			include_once SW_ABSPATH . 'includes/functions-um.php';
			//include_once SW_ABSPATH . 'includes/functions-tribe.php';
			//include_once SW_ABSPATH . 'includes/functions-mec.php';

			include_once SW_ABSPATH . 'includes/admin/class-skywin-settings.php';

			include_once SW_ABSPATH . 'includes/class-skywin-hub-push.php';
			Skywin_Hub_Push::init();
		}
	}
endif;