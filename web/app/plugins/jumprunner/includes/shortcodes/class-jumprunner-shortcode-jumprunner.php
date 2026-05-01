<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Jumprunner_Shortcode_Jumprunner' ) ) :

class Jumprunner_Shortcode_Jumprunner {

    /**
     * Called by the shortcode handler.
     * Usage: [jumprunner]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function output( $atts = [] ) {
        $atts = shortcode_atts(
            [
                'title' => '',
                'lat'   => '0',
                'lng'   => '0',
                'zoom'  => '12',
            ],
            $atts,
            'jumprunner'
        );

        $template_args = [
            'title' => sanitize_text_field( $atts['title'] ),
            'lat'   => sanitize_text_field( $atts['lat'] ),
            'lng'   => sanitize_text_field( $atts['lng'] ),
            'zoom'  => absint( $atts['zoom'] ),
        ];

        ob_start();
        load_template(
            JR_ABSPATH . 'templates/template-jumprunner.php',
            false,
            $template_args
        );
        return ob_get_clean();
    }
}

endif;
