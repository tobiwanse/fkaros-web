<?php
/**
 * SkyView Web Push Notifications.
 *
 * Manages VAPID keys, push subscriptions, change detection and sending
 * real push notifications via WP-Cron so users receive alerts even when
 * the browser tab is closed.
 */

defined( 'ABSPATH' ) || exit;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;

class Skywin_Hub_Push {

	const OPTION_VAPID       = 'skyview_push_vapid';
	const OPTION_LAST_STATE  = 'skyview_push_last_state';
	const OPTION_SUBS        = 'skyview_push_subscriptions';
	const CRON_HOOK          = 'skyview_push_check';

	/* ── Bootstrap ─────────────────────────────────────────────────── */

	public static function init() {
		self::ensure_vapid_keys();

		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_action( 'init', [ __CLASS__, 'register_sw_rewrite' ] );
		add_action( 'parse_request', [ __CLASS__, 'serve_sw_file' ] );
		add_action( 'parse_request', [ __CLASS__, 'serve_manifest' ] );
		add_action( 'wp_head', [ __CLASS__, 'pwa_meta_tags' ] );
	}

	public static function register_sw_rewrite(): void {
		add_rewrite_rule( '^skyview-sw\.js$', 'index.php?skyview_sw=1', 'top' );
		add_rewrite_rule( '^skyview-manifest\.json$', 'index.php?skyview_manifest=1', 'top' );
		add_rewrite_tag( '%skyview_sw%', '1' );
		add_rewrite_tag( '%skyview_manifest%', '1' );

		// Flush once so the rewrite rules take effect.
		if ( get_option( 'skyview_sw_rewrite_version' ) !== '2' ) {
			flush_rewrite_rules( false );
			update_option( 'skyview_sw_rewrite_version', '2', true );
		}
	}

	public static function serve_sw_file( WP $wp ): void {
		if ( empty( $wp->query_vars['skyview_sw'] ) ) {
			return;
		}
		$file = plugin_dir_path( SW_PLUGIN_FILE ) . 'assets/js/skyview-sw.js';
		if ( ! file_exists( $file ) ) {
			status_header( 404 );
			exit;
		}
		header( 'Content-Type: application/javascript' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: no-cache' );
		readfile( $file );
		exit;
	}

	public static function get_sw_url(): string {
		return home_url( '/skyview-sw.js' );
	}

	public static function serve_manifest( WP $wp ): void {
		if ( empty( $wp->query_vars['skyview_manifest'] ) ) {
			return;
		}
		$start = isset( $_GET['start'] ) ? esc_url_raw( wp_unslash( $_GET['start'] ) ) : '/skyview-full/';
		$manifest = [
			'name'             => 'SkyView',
			'short_name'       => 'SkyView',
			// 'start_url'        => $start,
			'display'          => 'standalone',
			'background_color' => '#1a1a2e',
			'theme_color'      => '#1a1a2e',
			'icons'            => [
				[
					'src'     => plugins_url( 'assets/img/icon-192.png', SW_PLUGIN_FILE ),
					'sizes'   => '192x192',
					'type'    => 'image/png',
					'purpose' => 'any maskable',
				],
				[
					'src'     => plugins_url( 'assets/img/icon-512.png', SW_PLUGIN_FILE ),
					'sizes'   => '512x512',
					'type'    => 'image/png',
					'purpose' => 'any maskable',
				],
			],
		];
		header( 'Content-Type: application/manifest+json' );
		header( 'Cache-Control: public, max-age=86400' );
		echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		exit;
	}

	public static function pwa_meta_tags(): void {
		echo '<link rel="manifest" href="' . esc_url( home_url( '/skyview-manifest.json?start=' . rawurlencode( $_SERVER['REQUEST_URI'] ) ) ) . '">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
		echo '<meta name="theme-color" content="#1a1a2e">' . "\n";
	}

	/* ── VAPID keys ────────────────────────────────────────────────── */

	private static function ensure_vapid_keys(): void {
		$existing = get_option( self::OPTION_VAPID );
		if ( is_array( $existing ) && isset( $existing['version'] ) && $existing['version'] >= 2 ) {
			return;
		}

		// Use the library's own key generator — returns base64url-encoded raw keys.
		$keys = VAPID::createVapidKeys();

		update_option( self::OPTION_VAPID, [
			'publicKey'  => $keys['publicKey'],
			'privateKey' => $keys['privateKey'],
			'version'    => 2,
		], false );

		// Clear old subscriptions — they were signed with the old key.
		delete_option( self::OPTION_SUBS );
	}

	public static function get_vapid_public_key(): string {
		$vapid = get_option( self::OPTION_VAPID );
		return $vapid['publicKey'] ?? '';
	}

	/* ── REST routes ───────────────────────────────────────────────── */

	public static function register_rest_routes(): void {
		register_rest_route( 'skywin-hub/v1', '/push/vapid', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_vapid' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'skywin-hub/v1', '/push/subscribe', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_subscribe' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'subscription' => [ 'required' => true ],
				'types'        => [ 'required' => true ],
			],
		] );

		register_rest_route( 'skywin-hub/v1', '/push/unsubscribe', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_unsubscribe' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'endpoint' => [ 'required' => true, 'sanitize_callback' => 'esc_url_raw' ],
			],
		] );

		register_rest_route( 'skywin-hub/v1', '/push/test', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_test' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'skywin-hub/v1', '/push/cron', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_cron' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'skywin-hub/v1', '/push/debug', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_debug' ],
			'permission_callback' => '__return_true',
		] );
	}

	public static function rest_cron(): WP_REST_Response {
		$result = self::cron_check();
		return new WP_REST_Response( array_merge( [ 'ok' => true, 'time' => wp_date( 'H:i:s' ) ], $result ) );
	}

	public static function rest_debug(): WP_REST_Response {
		$subs  = get_option( self::OPTION_SUBS, [] );
		$prev  = get_option( self::OPTION_LAST_STATE, false );
		$date  = wp_date( 'Y-m-d' );

		$payload_result = Skywin_Hub_Shortcode_Skyview::build_payload( $date );
		$payload_ok     = ! is_wp_error( $payload_result );
		$load_count     = $payload_ok ? count( $payload_result['loads'] ?? [] ) : 0;
		$payload_error  = is_wp_error( $payload_result ) ? $payload_result->get_error_message() : null;

		$sub_types = array_map( fn( $s ) => $s['types'] ?? [], is_array( $subs ) ? $subs : [] );

		return new WP_REST_Response( [
			'subscribers'    => count( is_array( $subs ) ? $subs : [] ),
			'sub_types'      => $sub_types,
			'now'            => wp_date( 'Y-m-d H:i:s' ),
			'last_state'     => $prev ? [
				'date'       => $prev['date'] ?? null,
				'loads'      => count( $prev['loadIds'] ?? [] ),
				'jumpers'    => count( $prev['jumperKeys'] ?? [] ),
			] : null,
			'current_fetch'  => [
				'ok'         => $payload_ok,
				'error'      => $payload_error,
				'loads'      => $load_count,
			],
		] );
	}

	public static function rest_vapid(): WP_REST_Response {
		return new WP_REST_Response( [ 'publicKey' => self::get_vapid_public_key() ] );
	}

	public static function rest_subscribe( WP_REST_Request $request ): WP_REST_Response {
		$sub   = $request->get_param( 'subscription' );
		$types = $request->get_param( 'types' );

		if ( ! is_array( $sub ) || empty( $sub['endpoint'] ) ) {
			return new WP_REST_Response( [ 'error' => 'Invalid subscription' ], 400 );
		}

		$endpoint = esc_url_raw( $sub['endpoint'] );
		$keys     = $sub['keys'] ?? [];

		if ( empty( $keys['p256dh'] ) || empty( $keys['auth'] ) ) {
			return new WP_REST_Response( [ 'error' => 'Missing keys' ], 400 );
		}

		$entry = [
			'endpoint' => $endpoint,
			'keys'     => [
				'p256dh' => sanitize_text_field( $keys['p256dh'] ),
				'auth'   => sanitize_text_field( $keys['auth'] ),
			],
			'types'    => [
				'newLoad'        => ! empty( $types['newLoad'] ),
				'newJumper'      => ! empty( $types['newJumper'] ),
				'newMessage'     => ! empty( $types['newMessage'] ),
				'newQueueJumper' => ! empty( $types['newQueueJumper'] ),
			],
			'created'  => time(),
		];

		$subs = get_option( self::OPTION_SUBS, [] );
		if ( ! is_array( $subs ) ) {
			$subs = [];
		}

		// Upsert by endpoint.
		$found = false;
		foreach ( $subs as &$s ) {
			if ( $s['endpoint'] === $endpoint ) {
				$s     = $entry;
				$found = true;
				break;
			}
		}
		unset( $s );

		if ( ! $found ) {
			$subs[] = $entry;
		}

		update_option( self::OPTION_SUBS, $subs, false );

		return new WP_REST_Response( [ 'ok' => true ] );
	}

	public static function rest_unsubscribe( WP_REST_Request $request ): WP_REST_Response {
		$endpoint = esc_url_raw( $request->get_param( 'endpoint' ) );
		$subs     = get_option( self::OPTION_SUBS, [] );
		if ( ! is_array( $subs ) ) {
			$subs = [];
		}

		$subs = array_values( array_filter( $subs, fn( $s ) => $s['endpoint'] !== $endpoint ) );
		update_option( self::OPTION_SUBS, $subs, false );

		return new WP_REST_Response( [ 'ok' => true ] );
	}

	public static function rest_test(): WP_REST_Response {
		$subs = get_option( self::OPTION_SUBS, [] );
		if ( ! is_array( $subs ) || empty( $subs ) ) {
			return new WP_REST_Response( [ 'error' => 'Inga prenumeranter hittades', 'count' => 0 ], 404 );
		}

		self::send_push( $subs, [
			'newLoad' => 'Testnotis — push fungerar!',
		] );

		return new WP_REST_Response( [ 'ok' => true, 'count' => count( $subs ) ] );
	}

	/* ── Cron: detect changes & push ───────────────────────────────── */

	public static function cron_check(): array {
		$subs = get_option( self::OPTION_SUBS, [] );
		if ( ! is_array( $subs ) || empty( $subs ) ) {
			return [ 'skipped' => 'no_subs' ];
		}

		$date = wp_date( 'Y-m-d' );

		// Fetch current data using the same method as the REST endpoint.
		$result = Skywin_Hub_Shortcode_Skyview::build_payload( $date );
		if ( is_wp_error( $result ) ) {
			return [ 'skipped' => 'fetch_error', 'error' => $result->get_error_message() ];
		}

		$loads  = $result['loads'] ?? [];
		$current_message     = trim( $result['message'] ?? '' );
		$current_queue_count = $result['jumpQueueCount'] ?? null;
		if ( $current_queue_count !== null ) {
			$current_queue_count = (int) $current_queue_count;
		}

		$prev   = get_option( self::OPTION_LAST_STATE, false );
		$is_first_run = false === $prev;
		if ( ! is_array( $prev ) ) {
			$prev = [];
		}

		$prev_load_ids    = $prev['loadIds']    ?? [];
		$prev_jumper_keys = $prev['jumperKeys'] ?? [];
		$prev_message     = $prev['message']    ?? '';
		$prev_queue_count = $prev['queueCount'] ?? null;

		// Current state.
		$current_load_ids    = [];
		$current_jumper_counts = [];
		$jumper_load_map = []; // key => load position (1-based)

		$load_position = 0;
		foreach ( $loads as $load ) {
			$load_id = $load['id'];
			$current_load_ids[] = $load_id;
			$load_position++;
			foreach ( $load['jumpers'] ?? [] as $j ) {
				$label      = trim( $j['label'] ?? '' );
				$internalNo = trim( $j['internalNo'] ?? '' );

				if ( '' === $internalNo && '' === $label ) {
					continue;
				}

				$key = '' !== $internalNo ? $internalNo : mb_strtolower( $label );
				if ( ! isset( $current_jumper_counts[ $key ] ) ) {
					$current_jumper_counts[ $key ] = [ 'count' => 0, 'label' => $label ];
				}
				$current_jumper_counts[ $key ]['count']++;
				$jumper_load_map[ $key ] = $load_position;
			}
		}

		// Detect new loads.
		$new_loads = array_diff( $current_load_ids, $prev_load_ids );

		// Detect jumpers whose count increased.
		$new_jumper_loads = [];
		if ( ! $is_first_run ) {
			foreach ( $current_jumper_counts as $key => $data ) {
				$prev_count = $prev_jumper_keys[ $key ]['count'] ?? 0;
				if ( $data['count'] > $prev_count ) {
					$new_jumper_loads[] = $jumper_load_map[ $key ] ?? 0;
				}
			}
		}

		// Save current state.
		update_option( self::OPTION_LAST_STATE, [
			'loadIds'    => $current_load_ids,
			'jumperKeys' => $current_jumper_counts,
			'message'    => $current_message,
			'queueCount' => $current_queue_count,
			'date'       => $date,
		], false );

		// Clear previous state if date changed.
		if ( isset( $prev['date'] ) && $prev['date'] !== $date ) {
			return [ 'skipped' => 'date_rollover' ];
		}

		// Skip first run (no previous state at all).
		if ( $is_first_run ) {
			return [ 'skipped' => 'first_run' ];
		}

		// Nothing new? Bail.
		$message_changed = $current_message !== '' && $current_message !== $prev_message;
		$queue_increased = $current_queue_count !== null && $prev_queue_count !== null && $current_queue_count > $prev_queue_count;

		if ( empty( $new_loads ) && empty( $new_jumper_loads ) && ! $message_changed && ! $queue_increased ) {
			return [ 'skipped' => 'no_changes', 'loads' => count( $current_load_ids ), 'prev_loads' => count( $prev_load_ids ) ];
		}

		// Build messages per notification type.
		$messages = [];
		if ( ! empty( $new_loads ) ) {
			$c = count( $new_loads );
			if ( $c === 1 ) {
				$total = count( $current_load_ids );
				$messages['newLoad'] = 'Lift nummer ' . $total . ' tillagd!';
			} else {
				$messages['newLoad'] = $c . ' nya liftar tillagda!';
			}
		}

		if ( ! empty( $new_jumper_loads ) ) {
			$c = count( $new_jumper_loads );
			if ( $c === 1 ) {
				$messages['newJumper'] = 'Ny hoppare lades till i lift nr ' . $new_jumper_loads[0];
			} else {
				$load_nums = array_unique( $new_jumper_loads );
				$messages['newJumper'] = $c . ' nya hoppare lades till i lift nr ' . implode( ', ', $load_nums );
			}
		}

		if ( $message_changed ) {
			$messages['newMessage'] = $current_message;
		}

		if ( $queue_increased ) {
			$messages['newQueueJumper'] = $current_queue_count . ' i kön';
		}

		self::send_push( $subs, $messages );
		return [ 'sent' => $messages, 'subs' => count( $subs ) ];
	}

	/* ── Send push notifications ───────────────────────────────────── */

	private static function send_push( array $subs, array $messages ): void {
		$vapid = get_option( self::OPTION_VAPID );
		if ( ! $vapid ) {
			return;
		}

		$auth = [
			'VAPID' => [
				'subject'    => home_url(),
				'publicKey'  => $vapid['publicKey'],
				'privateKey' => $vapid['privateKey'],
			],
		];

		$webPush = new WebPush( $auth );

		$stale_endpoints = [];

		foreach ( $messages as $type => $body ) {
			$title = match ( $type ) {
				'newMessage'      => 'Nytt meddelande',
				'newQueueJumper'  => 'Ny i önskelistan',
				default           => 'SkyView',
			};
			$payload = wp_json_encode( [
				'title' => $title,
				'body'  => $body,
				'tag'   => 'skyview-' . $type,
				'data'  => [ 'type' => $type ],
			] );

			foreach ( $subs as $sub ) {
				if ( empty( $sub['types'][ $type ] ) ) {
					continue;
				}

				$subscription = Subscription::create( [
					'endpoint' => $sub['endpoint'],
					'keys'     => $sub['keys'],
				] );

				$webPush->queueNotification( $subscription, $payload );
			}
		}

		foreach ( $webPush->flush() as $report ) {
			if ( $report->isSubscriptionExpired() ) {
				$stale_endpoints[] = $report->getEndpoint();
			}
		}

		// Clean up expired subscriptions.
		if ( ! empty( $stale_endpoints ) ) {
			$subs = get_option( self::OPTION_SUBS, [] );
			$subs = array_values( array_filter( $subs, fn( $s ) => ! in_array( $s['endpoint'], $stale_endpoints, true ) ) );
			update_option( self::OPTION_SUBS, $subs, false );
		}
	}

	/* ── Cleanup on plugin deactivation ────────────────────────────── */

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}

		// Clean up legacy Action Scheduler jobs if present.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::CRON_HOOK, [], 'skywin-hub' );
		}
	}
}
