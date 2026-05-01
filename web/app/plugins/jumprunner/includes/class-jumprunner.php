<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Jumprunner' ) ) :

class Jumprunner {

    protected static $_instance = null;
    private $includes_dir;
    private $assets_url;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->includes_dir = JR_ABSPATH . 'includes/';
        $this->assets_url   = trailingslashit( plugins_url( 'assets', JR_PLUGIN_FILE ) );
        $this->includes();
        $this->add_actions();
    }

    private function add_actions() {
        add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 9999 );
        add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_styles' ), 9999 );
    }

    public function wp_enqueue_scripts() {
        $api_key = Jumprunner_Admin::get_google_maps_api_key();
        if ( ! empty( $api_key ) ) {
            wp_enqueue_script(
                'google-maps',
                add_query_arg(
                    array(
                        'key'       => $api_key,
                        'loading'   => 'async',
                        'callback'  => 'initMap',
                        'libraries' => 'marker,geometry,maps3d',
                        'v'         => 'beta',
                    ),
                    'https://maps.googleapis.com/maps/api/js'
                ),
                array(),
                null,
                true
            );
        }
        wp_enqueue_script( 'jumprunner', $this->assets_url . 'js/jumprunner.js', array( 'jquery' ), null, true );
        wp_localize_script( 'jumprunner', 'jumprunnerData', array(
            'mapId' => Jumprunner_Admin::get_google_maps_map_id(),
        ) );
    }

    public function wp_enqueue_styles() {
        wp_enqueue_style( 'jumprunner', $this->assets_url . 'css/jumprunner.css' );
    }

    public function includes() {
        include_once $this->includes_dir . 'class-jumprunner-admin.php';
        Jumprunner_Admin::instance();
        include_once $this->includes_dir . 'class-jumprunner-shortcodes.php';
    }
}

endif;
