<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Jumprunner_Admin' ) ) :

class Jumprunner_Admin {

    const OPTION_GROUP      = 'jumprunner_settings';
    const OPTION_NAME       = 'jumprunner_google_maps_api_key';
    const OPTION_NAME_MAPID = 'jumprunner_google_maps_map_id';

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu_page() {
        add_options_page(
            __( 'Jumprunner Settings', 'jumprunner' ),
            __( 'Jumprunner', 'jumprunner' ),
            'manage_options',
            'jumprunner',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME_MAPID,
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        add_settings_section(
            'jumprunner_section_api',
            __( 'API Settings', 'jumprunner' ),
            '__return_false',
            'jumprunner'
        );

        add_settings_field(
            self::OPTION_NAME,
            __( 'Google Maps API Key', 'jumprunner' ),
            array( $this, 'render_api_key_field' ),
            'jumprunner',
            'jumprunner_section_api'
        );
        add_settings_field(
            self::OPTION_NAME_MAPID,
            __( 'Google Maps Map ID', 'jumprunner' ),
            array( $this, 'render_map_id_field' ),
            'jumprunner',
            'jumprunner_section_api'
        );
    }

    public function render_api_key_field() {
        $value = get_option( self::OPTION_NAME, '' );
        ?>
        <input
            type="text"
            id="<?php echo esc_attr( self::OPTION_NAME ); ?>"
            name="<?php echo esc_attr( self::OPTION_NAME ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            class="regular-text"
            autocomplete="off"
        />
        <p class="description"><?php esc_html_e( 'Enter your Google Maps JavaScript API key.', 'jumprunner' ); ?></p>
        <?php
    }

    public function render_map_id_field() {
        $value = get_option( self::OPTION_NAME_MAPID, '' );
        ?>
        <input
            type="text"
            id="<?php echo esc_attr( self::OPTION_NAME_MAPID ); ?>"
            name="<?php echo esc_attr( self::OPTION_NAME_MAPID ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            class="regular-text"
            autocomplete="off"
        />
        <p class="description">
            <?php esc_html_e( 'Required for AdvancedMarkerElement. Create a Map ID in Google Cloud Console under Maps → Map Management.', 'jumprunner' ); ?>
        </p>
        <?php
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Jumprunner Settings', 'jumprunner' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( 'jumprunner' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get the stored Google Maps API key.
     *
     * @return string
     */
    public static function get_google_maps_api_key() {
        return (string) get_option( self::OPTION_NAME, '' );
    }

    /**
     * Get the stored Google Maps Map ID.
     *
     * @return string
     */
    public static function get_google_maps_map_id() {
        return (string) get_option( self::OPTION_NAME_MAPID, '' );
    }
}

endif;
