<?php
defined('ABSPATH') || exit;

if (!class_exists('Skywin_Admin_Settings')):
	class Skywin_Admin_Settings
	{
		protected static $_instance = null;
		protected $name = '';
		private $args = [];
		private $page_title;
		private $menu_title;
		private $menu_slug;
		private $option_page;
		private $current_tab;
		private $user_capability;
		private $settings;
		private $tabs;

		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		public function __construct()
		{
			$this->tabs = $this->tabs();
			$this->args = $this->args();
			$this->menu_title = $this->args['menu_title'];
			$this->page_title = $this->args['page_title'];
			$this->menu_slug = $this->args['menu_slug'];
			$this->user_capability = $this->args['user_capability'];
			$this->option_page = $this->option_page();
			$this->current_tab = $this->current_tab();
			$this->settings = $this->settings();
			$this->add_actions();
			$this->add_filters();
		}
		private function args()
		{
			return [
				'menu_title' => __('Skywin Hub', 'skywin-hub'),
				'page_title' => __('Skywin Hub', 'skywin-hub'),
				'menu_slug' => 'skywin_hub',
				'user_capability' => 'manage_options',
			];
		}
		private function option_page()
		{
			return "{$this->menu_slug}_{$this->current_tab()}";
		}
		private function tabs()
		{
			$tabs["api"] = esc_html__('SkywinOne', 'skywin-hub');
			$tabs["db"] = esc_html__('Skywin Database', 'skywin-hub');
			$tabs["google_api"] = esc_html__('Google Api', 'skywin-hub');
			return $tabs;
		}
		private function current_tab()
		{
			$current_tab = isset($_GET['tab']) && isset($this->tabs[$_GET['tab']]) ? $_GET['tab'] : array_key_first($this->tabs);
			return $current_tab;
		}
		private function add_actions()
		{
			add_action('admin_init', array($this, 'admin_init'));

			add_action('admin_menu', [$this, 'register_menu_page'], 10, 1);
			add_action('admin_init', [$this, 'register_settings'], 10, 1);

			add_action('admin_enqueue_scripts', array($this, 'admin_register_scripts'), 10, 1);
			add_action('admin_enqueue_scripts', array($this, 'admin_register_styles'), 10, 1);
		}
		private function add_filters()
		{
		}
		public function admin_register_scripts($hook)
		{
			if ("toplevel_page_{$this->menu_slug}" !== $hook) {
				return;
			}
		}
		public function admin_register_styles($hook)
		{
			if ("toplevel_page_{$this->menu_slug}" !== $hook) {
				return;
			}
			wp_enqueue_style('admin-skywin-hub-css', plugin_dir_url(SW_PLUGIN_FILE) . 'admin/assets/css/style.css');
		}
		private function is_skywin_hub_page()
		{
			$true = false;
			if (isset($_REQUEST['page']) && $_REQUEST['page'] === $this->menu_slug) {
				$true = true;
			}
			return $true;
		}
		public function admin_init()
		{
			$this->maybe_remove_connection();
		}
		private function maybe_remove_connection()
		{
			if (!isset($_GET['settings-updated']) && isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "{$this->option_page}_remove_nonce")) {
				if (!current_user_can($this->user_capability)) {
					return;
				}
				$this->remove_connection();
				$url = add_query_arg(array(
					'page' => $this->menu_slug,
					'tab' => $this->current_tab(),
				), admin_url('admin.php'));
				wp_safe_redirect($url);
				exit;
			}
		}
		private function remove_connection()
		{

			delete_option("{$this->option_page}_password");
			delete_option("{$this->option_page}_is_authorized");
			//delete_option( "{$this->option_page}_client_id" );
			//delete_option( "{$this->option_page}_client_secret" );
			//delete_option( "{$this->option_page}_redirect_uri" );
			//delete_option( "{$this->option_page}_access_token" );
		}
		/**
		 * Register the admin menu page.
		 */
		public function register_menu_page()
		{
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				$this->user_capability,
				$this->menu_slug,
				[$this, 'render_options_page']
			);
		}
		/**
		 * Register the settings.
		 */
		public function register_settings()
		{
			if (isset($_GET['code']) || isset($_GET['settings-updated'])) {
				$this->validate_connection();
			}
			$settings = $this->settings();
			$section_id = 0;
			foreach ($settings as $setting) {
				$custom_attributes = '';

				if ($setting['type'] == 'title') {
					$this->render_title_field($setting);
					continue;
				}

				if ($setting['type'] == 'sectionend') {
					add_settings_section(
						"{$this->option_page}_{$section_id}",
						$setting['name'] ?? '',
						[$this, 'section_callback'],
						$this->option_page,
						$setting
					);
					$section_id++;
				} else {
					$sanitize_callback = $setting['sanitize_callback'] ?? null;

					register_setting(
						$this->option_page,
						$setting['id'] ?? '',
						array(
							'type' => 'array',
							'sanitize_callback' => $sanitize_callback,
						)
					);

					if (isset($setting['custom_attributes']) && !empty($setting['custom_attributes'])) {
						foreach ($setting['custom_attributes'] as $key => $value) {
							$custom_attributes .= "{$key}={$value} ";
						}
					}
					$default_value = $setting['default'] ?? null;

					$value = get_option($setting['id'] ?? null);

					if (!isset($value) || empty($value)) {
						$value = $default_value;
					}

					add_settings_field(
						$setting['id'] ?? '',
						$setting['name'] ?? '',
						array($this, 'render_' . $setting['type'] . '_field'),
						$this->option_page,
						"{$this->option_page}_{$section_id}",
						[
							'label_for' => esc_attr($setting['id'] ?? ''),
							'name' => esc_html($setting['name'] ?? ''),
							'value' => esc_attr($value),
							'attr' => esc_attr($custom_attributes),
							'class' => esc_attr($setting['class'] ?? ''),
							'desc' => esc_html($setting['desc'] ?? ''),
							'desc_tip' => esc_html($setting['desc_tip'] ?? ''),
							'options' => $setting['options'] ?? array(),
						]
					);
				}
			}
		}
		public function section_callback($args)
		{
			if (isset($args['text']) && !empty($args['text'])) {
				echo $args['text'];
			}
		}

		public function render_options_tabs()
		{
			$tabs = $this->tabs;
			if (empty($tabs)) {
				return;
			} ?>
			<h2 class="nav-tab-wrapper">
				<?php
				foreach ($tabs as $tab => $label) {
					$active = $this->current_tab() === $tab ? ' nav-tab-active' : '';
					$args = array(
						'page' => $this->menu_slug,
						'tab' => $tab
					);
					$url = add_query_arg($args, admin_url('admin.php'));
					?>
					<a href="<?php echo esc_url($url); ?>" data-tab="<?php echo esc_attr($tab); ?>"
						class="nav-tab <?php echo $active ?>"><?php echo ucfirst($label); ?></a>
					<?php
				} ?>
			</h2>
			<?php
		}
		public function render_options_page()
		{
			if (!current_user_can($this->user_capability)) {
				return;
			}

			if (isset($_GET['code']) || isset($_GET['settings-updated'])) {

				if (get_option("{$this->option_page}_is_authorized")) {
					add_settings_error($this->option_page . '_mesages', $this->option_page . '_message', esc_html__('Connection success.', 'skywin-hub'), 'updated');
				} else {
					add_settings_error($this->option_page . '_mesages', $this->option_page . '_message', esc_html__('Connection failed.', 'skywin-hub'), 'error');
				}
			}

			wp_enqueue_script('admin-skywin-hub-js', plugin_dir_url(SW_PLUGIN_FILE) . 'admin/assets/js/index.js', array('jquery'), null, true);
			wp_localize_script('admin-skywin-hub-js', 'ajax_get_skywin_db_status_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'action' => 'get_db_status',
				'_ajax_nonce' => wp_create_nonce('ajax_get_skywin_db_status_nonce'),
			));
			wp_localize_script('admin-skywin-hub-js', 'ajax_get_skywin_api_status_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'action' => 'get_api_status',
				'_ajax_nonce' => wp_create_nonce('ajax_get_skywin_api_status_nonce'),
			));
			wp_localize_script('admin-skywin-hub-js', 'ajax_get_google_api_status_params', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'action' => 'get_google_api_status',
				'_ajax_nonce' => wp_create_nonce('ajax_get_google_api_status_nonce'),
			));
			$url = add_query_arg(array(
				'tab' => $this->current_tab()
			), admin_url('options.php'));
			?>
			<div class="wrap">
				<form action="<?php echo $url ?>" method="post" enctype="multipart/form-data" class="skywin-options-form">
					<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
					<?php

					$this->render_options_tabs();
					settings_errors($this->option_page . '_mesages');
					?>
					<?php
					settings_fields($this->option_page);
					do_settings_sections($this->option_page);

					if (!get_option("{$this->option_page}_is_authorized")) {
						submit_button(__('Save Settings', 'skywin-hub'));
					}

					?>
				</form>
			</div>
			<?php
		}
		public function render_title_field($args)
		{
		}
		public function render_text_field($args)
		{
			?>
			<input type="text" id="<?php echo $args['label_for'] ?>" name="<?php echo $args['label_for'] ?>"
				value="<?php echo $args['value'] ?>" class="<?php echo $args['class'] ?>" <?php echo $args['attr'] ?>>
			<?php if ($args['desc']) { ?>
				<p class="description">
					<?php echo esc_html($args['desc']); ?>
				</p>
			<?php }
		}
		public function render_password_field($args)
		{
			?>
			<input type="password" id="<?php echo $args['label_for'] ?>" name="<?php echo $args['label_for'] ?>"
				value="<?php echo $args['value'] ?>" <?php echo $args['attr'] ?>>
			<?php if ($args['desc']): ?>
				<p class="description"><?php echo $args['desc'] ?></p>
				<?php
			endif;
		}
		public function render_skywin_hub_api_password_field($args)
		{
			$this->render_password_field($args);
		}
		public function render_skywin_hub_api_remove_connection_field($args)
		{
			$user = get_option("{$this->option_page}_username");
			$host = get_option("{$this->option_page}_host");
			$connected_as = $user . '@' . $host;
			$nonce = wp_create_nonce("{$this->option_page}_remove_nonce");
			$url = add_query_arg(array(
				'page' => $this->menu_slug,
				'tab' => $this->current_tab,
				'_wpnonce' => $nonce,
			), admin_url('admin.php'));
			?>
			<div class="sw-admin-setting-field">
				<a href="<?php echo $url; ?>" class="sw-btn-danger">Remove Connection</a>
				<span class="connected-as">Connected as <?php echo $connected_as ?></span>
			</div>
			<?php
		}

		public function render_skywin_hub_db_password_field($args)
		{
			$this->render_password_field($args);
		}
		public function render_skywin_hub_db_remove_connection_field($args)
		{
			$user = get_option("{$this->option_page}_username");
			$host = get_option("{$this->option_page}_host");
			$connected_as = $user . '@' . $host;
			$nonce = wp_create_nonce("{$this->option_page}_remove_nonce");
			$url = add_query_arg(array(
				'page' => $this->menu_slug,
				'tab' => $this->current_tab,
				'_wpnonce' => $nonce,
			), admin_url('admin.php'));
			?>
			<div class="sw-admin-setting-field">
				<a href="<?php echo $url; ?>" class="sw-btn-danger">Remove Connection</a>
				<span class="connected-as">Connected as <?php echo $connected_as ?></span>
			</div>
			<?php
		}
		public function render_skywin_hub_google_api_remove_connection_field($args)
		{
			$user = get_option("{$this->option_page}_username");
			$host = get_option("{$this->option_page}_host");
			$connected_as = $user . '@' . $host;
			$nonce = wp_create_nonce("{$this->option_page}_remove_nonce");
			$url = add_query_arg(array(
				'page' => $this->menu_slug,
				'tab' => $this->current_tab,
				'_wpnonce' => $nonce,
			), admin_url('admin.php'));
			?>
			<div class="sw-admin-setting-field">
				<a href="<?php echo $url; ?>" class="sw-btn-danger">Remove Connection</a>
			</div>
			<?php
		}

		public function sanitize_skywin_hub_api_host_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_api_port_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_api_path_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_api_username_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_api_password_field($data)
		{
			if (isset($_POST["{$this->option_page}_password"]) && !empty($_POST["{$this->option_page}_password"])) {
				$data = SKYWIN_HUB()->encrypt($_POST["{$this->option_page}_password"]);
			}
			return $data;
		}

		public function sanitize_skywin_hub_db_host_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_db_port_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_db_name_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_db_username_field($data)
		{
			return $data;
		}
		public function sanitize_skywin_hub_db_password_field($data)
		{
			if (isset($_POST["{$this->option_page}_password"]) && !empty($_POST["{$this->option_page}_password"])) {
				$data = SKYWIN_HUB()->encrypt($_POST["{$this->option_page}_password"]);
			}
			return $data;
		}

		public function sanitize_text_field($data)
		{
			return $data;
		}
		public function sanitize_checkbox_field($value = '', $field_args = [])
		{
			return ('on' === $value) ? 1 : 0;
		}
		public function sanitize_select_field($value = '', $field_args = [])
		{
			$choices = $field_args['choices'] ?? [];
			if (array_key_exists($value, $choices)) {
				return $value;
			}
		}

		public function ajax_get_api_status()
		{
			global $swapi;
			check_ajax_referer('ajax_get_skywin_api_status_nonce', '_ajax_nonce');
			$status = $swapi->status();
			$connected = false;
			if (!is_wp_error($status)) {
				$connected = true;
			} else {
				$msg = $status->get_error_message();
			}
			wp_send_json($connected);
		}
		public function ajax_get_db_status()
		{
			global $swdb;
			check_ajax_referer('ajax_get_skywin_db_status_nonce', '_ajax_nonce');
			$status = $swdb->status();
			$connected = false;
			if (!is_wp_error($status)) {
				$connected = true;
			} else {
				$msg = $status->get_error_message();
			}
			wp_send_json($connected);
		}
		private function validate_connection()
		{
			$connected = false;

			if ($this->option_page === 'skywin_hub_api') {
				if (!is_wp_error(skywin_hub_api()->status())) {
					$connected = true;
					update_option("{$this->option_page}_is_authorized", true);
				}
			}
			if ($this->option_page === 'skywin_hub_db') {
				if (!is_wp_error(skywin_hub_db()->status())) {
					$connected = true;
					update_option("{$this->option_page}_is_authorized", true);
				}
			}
			if ($this->option_page === 'skywin_hub_google_api') {
				skywin_hub_google_api()->authenticate();
				if (get_option('skywin_hub_google_api_access_token')) {
					$connected = true;
					update_option("{$this->option_page}_is_authorized", true);
				}
			}
		}
		public function settings()
		{
			$current_tab = $this->current_tab();
			$settings = [];

			if ("api" === $current_tab):
				$settings[] = [
					'name' => __('Skywin API settings', 'skywin-hub'),
					'type' => 'title',
					'tab' => 'skywinone_api',
				];
				if (!get_option("{$this->option_page}_is_authorized")):
					$settings[] = [
						'id' => "{$this->option_page}_host",
						'name' => __('Host', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('SkywinOne host ex. localhost or 127.0.0.1', 'skywin-hub'),
						'desc_tip' => __('SkywinOne host ex. localhost or 127.0.0.1', 'skywin-hub'),
						'default' => 'localhost',
						'sanitize_callback' => null,
					];
					$settings[] = [
						'id' => "{$this->option_page}_port",
						'name' => __('Port', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('SkywinOne port ex. 8080', 'skywin-hub'),
						'desc_tip' => __('SkywinOne port ex. 8080', 'skywin-hub'),
						'default' => esc_attr('8080'),
						'sanitize_callback' => null,
					];
					$settings[] = [
						'id' => "{$this->option_page}_path",
						'name' => __('Path', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('SkywinOne path ex. /skywinone/api/v1/'),
						'desc_tip' => __('SkywinOne path ex. /skywinone/api/v1/'),
						'default' => esc_attr('/skywinone/api/v1/'),
						'sanitize_callback' => null,
					];
					$settings[] = [
						'id' => "{$this->option_page}_username",
						'name' => __('Username', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('SkywinOne username', 'skywin-hub'),
						'desc_tip' => __('SkywinOne username', 'skywin-hub'),
					];
					$settings[] = [
						'id' => "{$this->option_page}_password",
						'name' => __('Password', 'skywin-hub'),
						'type' => "skywin_hub_api_password",
						'desc' => __('SkywinOne password', 'skywin-hub'),
						'desc_tip' => __('SkywinOne password', 'skywin-hub'),
						'sanitize_callback' => [$this, "sanitize_{$this->option_page}_password_field"],
						'custom_attributes' => array(
							"autocomplete" => "off"
						),
					];
				elseif (get_option("{$this->option_page}_is_authorized")):
					$settings[] = [
						'name' => __('Authorization', 'skywin-hub'),
						'type' => "{$this->option_page}_remove_connection",
						'desc' => __('Authorization', 'skywin-hub'),
					];
				endif;

				$settings[] = [
					'name' => __('SkywinOne api Settings', 'skywin-hub'),
					'type' => 'sectionend',
					'desc' => __('SkywinOne api Settings', 'skywin-hub'),
					'desc_tip' => __('SkywinOne api Settings', 'skywin-hub'),
				];
			elseif ("db" === $current_tab):

				$settings[] = [
					'name' => __('Skywin Database Settings', 'skywin-hub'),
					'type' => 'title',
				];
				if (!get_option("{$this->option_page}_is_authorized")):
					$settings[] = [
						'id' => "{$this->option_page}_host",
						'name' => __('Host', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('Skywin database host ex. localhost or 127.0.0.1', 'skywin-hub'),
						'desc_tip' => __('Skywin database host ex. localhost or 127.0.0.1', 'skywin-hub'),
						'default' => esc_attr('localhost'),
						'sanitize_callback' => null,
					];
					$settings[] = [
						'id' => "{$this->option_page}_port",
						'name' => __('Port', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('Skywin database port ex. 3306', 'skywin-hub'),
						'desc_tip' => __('Skywin database port ex. 3306', 'skywin-hub'),
						'default' => esc_attr('3306'),
						'sanitize_callback' => null,
					];
					$settings[] = [
						'id' => "{$this->option_page}_name",
						'name' => __('Database name', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('Skywin database name', 'skywin-hub'),
						'desc_tip' => __('Skywin database name', 'skywin-hub'),
						'default' => esc_attr('skywin'),
						'sanitize_callback' => null,
					];
					$settings[] = [
						'id' => "{$this->option_page}_username",
						'name' => __('Username', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('Skywin database username', 'skywin-hub'),
						'desc_tip' => __('Skywin database username', 'skywin-hub'),
						'sanitize_callback' => null,
					];
					$settings[] = [
						'id' => "{$this->option_page}_password",
						'name' => __('Password', 'skywin-hub'),
						'type' => 'skywin_hub_db_password',
						'desc' => __('Skywin database password', 'skywin-hub'),
						'desc_tip' => __('Skywin database password', 'skywin-hub'),
						'custom_attributes' => array('autocomplete' => 'off'),
						'sanitize_callback' => [$this, "sanitize_{$this->option_page}_password_field"],
					];
				elseif (get_option("{$this->option_page}_is_authorized")):
					$settings[] = [
						'name' => __('Authorization', 'skywin-hub'),
						'type' => "{$this->option_page}_remove_connection",
						'desc' => __('Authorization', 'skywin-hub'),
					];
				endif;
				$settings[] = [
					'name' => __('Skywin Database Settings', 'skywin-hub'),
					'type' => 'sectionend',
					'desc' => __('Skywin Database Section', 'skywin-hub'),
					'desc_tip' => __('Skywin Database Section', 'skywin-hub'),
				];
			elseif ("skywin_hub_google_api" === $this->option_page):
				$settings[] = [
					'name' => __('Google Api Settings', 'skywin-hub'),
					'type' => 'title',
				];
				if (!get_option("{$this->option_page}_is_authorized")):
					$settings[] = [
						'id' => "{$this->option_page}_client_id",
						'name' => __('Client Id', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('Google client id', 'skywin-hub'),
						'desc_tip' => __('Google client id', 'skywin-hub'),
					];
					$settings[] = [
						'id' => "{$this->option_page}_client_secret",
						'name' => __('Client Secret', 'skywin-hub'),
						'type' => 'text',
						'desc' => __('Google client secret', 'skywin-hub'),
						'desc_tip' => __('Google client secret', 'skywin-hub'),
					];

					$redirect_url = add_query_arg(array(
						'page' => $this->menu_slug,
						'tab' => $current_tab
					), admin_url('admin.php'));

					$settings[] = [
						'id' => "{$this->option_page}_redirect_uri",
						'name' => __('Redirect uri', 'skywin-hub'),
						'value' => $redirect_url,
						'type' => 'text',
						'desc' => __('Google redirect uri', 'skywin-hub'),
						'desc_tip' => __('Google redirect uri', 'skywin-hub'),
						'default' => $redirect_url,
						'custom_attributes' => array('readonly' => true),
					];
				elseif (get_option("{$this->option_page}_is_authorized")):
					$settings[] = [
						'name' => __('Authorization', 'skywin-hub'),
						'type' => "{$this->option_page}_remove_connection",
						'desc' => __('Authorization', 'skywin-hub'),
					];
				endif;

				$settings[] = [
					'name' => __('Google Api Settings', 'skywin-hub'),
					'type' => 'sectionend',
					'desc' => __('Google Api Section', 'skywin-hub'),
					'desc_tip' => __('Google Api Section', 'skywin-hub'),
				];
			endif;

			return $settings;
		}
	}
	function skywin_admin_settings()
	{
		return Skywin_Admin_Settings::instance();
	}
	skywin_admin_settings();
endif;