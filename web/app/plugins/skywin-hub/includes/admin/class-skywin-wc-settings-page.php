<?php

if (!defined('ABSPATH')) { exit; }

if( !class_exists('Skywin_Hub_WC_Settings_Page') ):
class Skywin_Hub_WC_Settings_Page extends WC_Settings_Page{
	public static $_instance = null;
	protected $id = null;
	protected $title = null;
	protected $label = null;
    public static function instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	public function __construct()
	{
		$this->id = 'skywin-hub-settings';
		$this->title = __('Skywin Hub settings', 'skywin-hub');
		$this->label = __( 'Skywin Hub', 'skywin-hub' );
		$this->get_sections();
		parent::__construct();
	
	}
	public function filter_setting_by_tab( $settings )
	{
		global $current_section;
		$filter_setting = [];
		foreach( $settings as $setting){
			if( $current_section == $setting['tab']){
				$filter_setting[] = $setting;
			}
		}
		return $filter_setting;
	}
	public function get_sections()
	{
		global $current_section;
		$sections = skywin_admin_settings()->tabs();
		$current_section = isset( $_GET[ 'section' ] ) && isset( $sections[ $_GET[ 'section' ] ] ) ? $_GET[ 'section' ] : array_key_first( $sections );
		$filter_sections = apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		return $filter_sections;
	}
    public function output()
	{	
		$settings = $this->filter_setting_by_tab( skywin_admin_settings()->settings() ) ;
		WC_Admin_Settings::output_fields( $settings );
    }

    public function save()
	{
		global $current_section;
		$this->get_sections();
		$this->validate_settings();
		$settings = $this->filter_setting_by_tab( skywin_admin_settings()->settings() ) ;
		WC_Admin_Settings::save_fields( $settings );
		
		$connected = $this->validate_connection();
		
		if( $connected ){
			update_option( "skywin_".$current_section."_is_authorized", true );
		} else {
			$this->remove_connection();
		}
    }
}
function skywin_hub_wc_settings_page()
{
	return Skywin_Hub_WC_Settings_Page::instance();
}
endif;