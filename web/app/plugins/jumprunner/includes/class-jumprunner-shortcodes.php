<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Jumprunner_Shortcodes' ) ) :

class Jumprunner_Shortcodes {

    protected static $_instance = null;

    public static function instance() {
        if ( self::$_instance === null ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->includes();
        $this->init();
    }

    private function includes() {
        include_once JR_ABSPATH . 'includes/shortcodes/class-jumprunner-shortcode-jumprunner.php';
    }

    private function init() {
        $shortcodes = array(
            'jumprunner' => array( $this, 'jumprunner' ),
        );
        foreach ( $shortcodes as $shortcode => $function ) {
            add_shortcode( $shortcode, $function );
        }
    }

    public function jumprunner( $atts ) {
        return Jumprunner_Shortcode_Jumprunner::output( $atts );
    }
}

function jumprunner_shortcodes() {
    return Jumprunner_Shortcodes::instance();
}
jumprunner_shortcodes();

endif;
