<?php
defined( 'ABSPATH' ) || exit;
if ( ! class_exists('Skywin_Shortcode_Aff_Signup') ):
class Skywin_Shortcode_Aff_Signup{
    public function __construct() {}
    public function output_fields() {
        $args = array();
        ob_start();
        load_template( SW_TEMPLATE_PATH . '/aff-signup-fields.php', true, $args);
        return ob_get_clean();
    }
}
endif;