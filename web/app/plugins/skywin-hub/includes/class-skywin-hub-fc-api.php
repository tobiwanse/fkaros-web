<?php
/**
 * FC / Loadplanner API client.
 *
 * Wraps the upstream JSON endpoint at
 *   https://loadplannerjs.vercel.app/api/tv/loadplanning
 *
 * Auth value is stored as the WP option `skywin_hub_fc_authorization`
 * (managed under Skywin Hub → FC-Settings).
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Skywin_Hub_FC_API' ) ) :

class Skywin_Hub_FC_API {

	const DEFAULT_BASE_URL = 'https://localhost';
	const DEFAULT_ENDPOINT = '/api/endpoint';

	protected static $_instance = null;

	public static function instance(): self {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function get_base_url(): string {
		$url = (string) get_option( 'skywin_hub_fc_url', self::DEFAULT_BASE_URL );
		return rtrim( $url, '/' );
	}

	public function get_endpoint(): string {
		$endpoint = (string) get_option( 'skywin_hub_fc_endpoint', self::DEFAULT_ENDPOINT );
		if ( $endpoint === '' ) {
			$endpoint = self::DEFAULT_ENDPOINT;
		}
		if ( $endpoint[0] !== '/' ) {
			$endpoint = '/' . $endpoint;
		}
		return $endpoint;
	}

	/**
	 * Stored Authorization header value.
	 */
	public function get_authorization(): string {
		return (string) get_option( 'skywin_hub_fc_authorization', '' );
	}

	/**
	 * Fetch loadplanning for a given jump date.
	 *
	 * @param string $jump_date Date in Y-m-d format. Defaults to today (site timezone).
	 * @return array|WP_Error Decoded JSON on success, WP_Error on failure.
	 */
	public function get_loadplanning( ) {
		$auth = $this->get_authorization();
		if ( $auth === '' ) {
			return new WP_Error( 'fc_missing_auth', __( 'FC Authorization is not configured.', 'skywin-hub' ) );
		}

		if ( $jump_date === '' ) {
			$jump_date = wp_date( 'Y-m-d' );
		}

		$base = $this->get_base_url();

		$args = [
			'method'      => 'GET',
			'timeout'     => 15,
			'redirection' => 5,
			'headers'     => [
				'Accept'        => 'application/json',
				'Authorization' => $auth,
			],
		];

        error_log(json_encode($args));
        error_log(json_encode($base . $this->get_endpoint()));

		$url      = $base . $this->get_endpoint();
		$attempts = 0;
		$max      = 3;
		$response = null;
		while ( $attempts < $max ) {
			$attempts++;
			$response = wp_remote_request( $url, $args );
			if ( ! is_wp_error( $response ) ) {
				break;
			}
			// Retry only on transient connectivity errors.
			$code = $response->get_error_code();
			$msg  = $response->get_error_message();
			$transient_error = $code === 'http_request_failed'
				&& ( stripos( $msg, 'cURL error 7' ) !== false
					|| stripos( $msg, 'cURL error 28' ) !== false
					|| stripos( $msg, 'cURL error 6' ) !== false
					|| stripos( $msg, 'cURL error 52' ) !== false
					|| stripos( $msg, 'cURL error 56' ) !== false );
			if ( ! $transient_error || $attempts >= $max ) {
				break;
			}
			error_log( sprintf( 'Skywin FC API retry %d/%d after error: %s', $attempts, $max - 1, $msg ) );
			usleep( 300000 * $attempts ); // 300ms, 600ms backoff
		}

		if ( is_wp_error( $response ) ) {
			error_log( 'Skywin FC API error: ' . $response->get_error_message() );
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code >= 400 ) {
			error_log( 'Skywin FC API HTTP ' . $code . ': ' . $body );
			return new WP_Error(
				'fc_http_error',
				sprintf( __( 'FC API returned HTTP %d.', 'skywin-hub' ), $code ),
				[ 'status' => $code, 'body' => $body ]
			);
		}

		$decoded = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'fc_json_error', __( 'FC API returned invalid JSON.', 'skywin-hub' ) );
		}

		return $decoded;
	}
}

endif;

if ( ! function_exists( 'skywin_hub_fc_api' ) ) {
	function skywin_hub_fc_api(): Skywin_Hub_FC_API {
		return Skywin_Hub_FC_API::instance();
	}
}
