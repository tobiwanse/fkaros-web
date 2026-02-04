<?php
defined( 'ABSPATH' ) || exit;
if ( !class_exists('Skywin_Hub_Shortcodes') ):
class Skywin_Hub_Shortcodes {
    protected static $_instance = null;
    public static function instance() {
		if ( self::$_instance === null ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    public function __construct(){
        $this->init();
    }
    private function init(){
        $shortcodes = array(
            'skywin_hub_deposit_product_fields' => array( $this, 'skywin_hub_deposit_product_fields' ),
        );
        foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
    }
    public function skywin_hub_deposit_product_fields( $args ){
        return Skywin_Hub_Shortcode_Deposit::output_fields( $args );
    }
}
function skywin_hub_shortcodes() {
    return Skywin_Hub_Shortcodes::instance();
}
skywin_hub_shortcodes();
endif;