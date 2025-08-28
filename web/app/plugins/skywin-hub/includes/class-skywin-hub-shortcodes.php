<?php
defined( 'ABSPATH' ) || exit;
if ( !class_exists('Skywin_Hub_Shortcodes') ):
class Skywin_Hub_Shortcodes {
    protected static $_instance = null;
    private $templates_dir = null;
    public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    public function __construct(){
        $this->templates_dir = plugin_dir_path( SW_PLUGIN_FILE ) . 'templates/';
        $this->init();
    }
    private function init(){
        $shortcodes = array(
            'classic_checkout' => array($this, 'classic_checkout'),
            'skywin_hub_deposit_product_fields' => array( $this, 'skywin_hub_deposit_product_fields' ),
            'skywin_hub_deposit_product_form' => array( $this, 'skywin_hub_deposit_product_form' ),
            'skywin_hub_calendar' => array( $this, 'skywin_hub_calendar' ),
            'skywin_hub_wishlist' => array( $this, 'skywin_hub_wishlist' ),

        );
        foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
    }
    public function skywin_hub_deposit_product_fields( $args ){
        return Skywin_Hub_Shortcode_Deposit::output_fields( $args );
    }
    public function skywin_hub_deposit_product_form( $args ){
        return Skywin_Hub_Shortcode_Deposit::output_form( $args );
    }
    public function skywin_hub_calendar( $args ){
        return Skywin_Hub_Shortcode_Calendar::output( $args );
    }
    public function skywin_hub_wishlist( $args ){
        return Skywin_Hub_Shortcode_Wishlist::output( $args );
    }
}
function skywin_hub_shortcodes() {
    return Skywin_Hub_Shortcodes::instance();
}
skywin_hub_shortcodes();
endif;