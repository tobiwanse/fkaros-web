<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Skywin_Hub_Shortcode_Tandem' ) ) :

class Skywin_Hub_Shortcode_Tandem {

	/**
	 * Shortcode handler.
	 * Usage: [skywin_hub_tandem date="2025-01-01" refresh="30"]
	 *
	 * Delegates to the SkyView shortcode in tandem-only mode so the user
	 * keeps the full SkyView header (date picker, settings, themes) but
	 * only the tandem column is shown.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function output( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'date'    => '',
				'refresh' => '30',
			],
			$atts,
			'skywin_hub_tandem'
		);

		if ( class_exists( 'Skywin_Hub_FC_Tandem_View' ) && ! Skywin_Hub_FC_Tandem_View::current_user_can_view() ) {
			return '';
		}

		return Skywin_Hub_Shortcode_Skyview::output( [
			'date'        => $atts['date'],
			'refresh'     => $atts['refresh'],
			'tandem_only' => '1',
		] );
	}
}

endif;
