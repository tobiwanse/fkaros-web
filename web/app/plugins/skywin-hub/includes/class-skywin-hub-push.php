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
		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedule' ] );

		self::ensure_vapid_keys();

		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'cron_check' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'skyview_push_interval', self::CRON_HOOK );
		}
	}

	public static function add_cron_schedule( array $schedules ): array {
		$schedules['skyview_push_interval'] = [
			'interval' => 15,
			'display'  => __( 'Every 15 seconds (SkyView push)', 'skywin-hub' ),
		];
		return $schedules;
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
				'newLoad'   => ! empty( $types['newLoad'] ),
				'newJumper' => ! empty( $types['newJumper'] ),
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

	public static function cron_check(): void {
		$subs = get_option( self::OPTION_SUBS, [] );
		if ( ! is_array( $subs ) || empty( $subs ) ) {
			return;
		}

		$date = wp_date( 'Y-m-d' );

		// Fetch current data using the same method as the REST endpoint.
		$result = Skywin_Hub_Shortcode_Skyview::build_payload( $date );
		if ( is_wp_error( $result ) ) {
			return;
		}

		$loads  = $result['loads'] ?? [];
		$prev   = get_option( self::OPTION_LAST_STATE, false );
		$is_first_run = false === $prev;
		if ( ! is_array( $prev ) ) {
			$prev = [];
		}

		$prev_load_ids    = $prev['loadIds']    ?? [];
		$prev_jumper_keys = $prev['jumperKeys'] ?? [];

		// Current state.
		$current_load_ids    = [];
		$current_jumper_counts = [];

		foreach ( $loads as $load ) {
			$load_id = $load['id'];
			$current_load_ids[] = $load_id;
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
			}
		}

		// Detect new loads.
		$new_loads = array_diff( $current_load_ids, $prev_load_ids );

		// Detect jumpers whose count increased.
		$new_jumper_labels = [];
		if ( ! $is_first_run ) {
			foreach ( $current_jumper_counts as $key => $data ) {
				$prev_count = $prev_jumper_keys[ $key ]['count'] ?? 0;
				if ( $data['count'] > $prev_count ) {
					$new_jumper_labels[] = $data['label'];
				}
			}
			$new_jumper_labels = array_unique( $new_jumper_labels );
		}

		// Save current state.
		update_option( self::OPTION_LAST_STATE, [
			'loadIds'    => $current_load_ids,
			'jumperKeys' => $current_jumper_counts,
			'date'       => $date,
		], false );

		// Clear previous state if date changed.
		if ( isset( $prev['date'] ) && $prev['date'] !== $date ) {
			return; // Don't notify on date rollover.
		}

		// Skip first run (no previous state at all).
		if ( $is_first_run ) {
			return;
		}

		// Nothing new? Bail.
		if ( empty( $new_loads ) && empty( $new_jumper_labels ) ) {
			return;
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

		if ( ! empty( $new_jumper_labels ) ) {
			if ( count( $new_jumper_labels ) === 1 ) {
				$messages['newJumper'] = $new_jumper_labels[0] . ' har lagts till!';
			} else {
				$messages['newJumper'] = count( $new_jumper_labels ) . ' nya hoppare tillagda!';
			}
		}

		self::send_push( $subs, $messages );
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
			$payload = wp_json_encode( [
				'title' => 'SkyView',
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
	}
}
