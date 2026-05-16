<?php
/**
 * Tandem view rendering helpers for the FC / Loadplanner data feed.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Skywin_Hub_FC_Tandem_View' ) ) :

class Skywin_Hub_FC_Tandem_View {

	/** Cache duration in seconds for a fetched payload. */
	const CACHE_TTL = 20;

	/** How long to keep a "last known good" payload as a fallback when the upstream is down. */
	const STALE_TTL = 3600;

	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			'skywin-hub/v1',
			'/tandem',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_tandem' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'date' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					],
				],
			]
		);
	}

	public static function rest_get_tandem( WP_REST_Request $request ) {
		$date = sanitize_text_field( (string) $request->get_param( 'date' ) );
		if ( $date === '' ) {
			$date = wp_date( 'Y-m-d' );
		}

		$payload = self::get_payload( $date );
		if ( is_wp_error( $payload ) ) {
			return new WP_REST_Response(
				[
					'error' => $payload->get_error_message(),
					'date'  => $date,
					'html'  => self::render_error( $payload->get_error_message() ),
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'date'        => $payload['date'] ?? $date,
				'generatedAt' => $payload['generatedAt'] ?? null,
				'html'        => self::render( $payload ),
			],
			200
		);
	}

	/**
	 * Get the FC payload for a given date, with a short transient cache.
	 *
	 * @return array|WP_Error
	 */
	public static function get_payload( string $date ) {
		if ( ! function_exists( 'skywin_hub_fc_api' ) ) {
			return new WP_Error( 'fc_unavailable', __( 'FC API är inte tillgänglig.', 'skywin-hub' ) );
		}

		$cache_key = 'skywin_hub_fc_tandem_' . md5( $date );
		$stale_key = 'skywin_hub_fc_tandem_stale_' . md5( $date );

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$payload = skywin_hub_fc_api()->get_loadplanning( $date );

		if ( is_wp_error( $payload ) || ! is_array( $payload ) ) {
			// Upstream failed — serve last known good payload if we have one.
			$stale = get_transient( $stale_key );
			if ( is_array( $stale ) ) {
				$stale['_stale'] = true;
				return $stale;
			}
			return is_wp_error( $payload )
				? $payload
				: new WP_Error( 'fc_bad_payload', __( 'Oväntat svar från FC-API.', 'skywin-hub' ) );
		}

		set_transient( $cache_key, $payload, self::CACHE_TTL );
		set_transient( $stale_key, $payload, self::STALE_TTL );
		return $payload;
	}

	/**
	 * Render the full tandem view HTML from a decoded FC payload.
	 */
	public static function render( array $payload ): string {
		$sections = isset( $payload['sections'] ) && is_array( $payload['sections'] )
			? $payload['sections']
			: [];

		// Sort by sortOrder ascending (fall back to loadNumber).
		usort(
			$sections,
			static function ( $a, $b ) {
				$ao = isset( $a['sortOrder'] ) ? (int) $a['sortOrder'] : (int) ( $a['loadNumber'] ?? 0 );
				$bo = isset( $b['sortOrder'] ) ? (int) $b['sortOrder'] : (int) ( $b['loadNumber'] ?? 0 );
				return $ao <=> $bo;
			}
		);

		$generated = '';
		if ( ! empty( $payload['generatedAt'] ) ) {
			$ts = strtotime( (string) $payload['generatedAt'] );
			if ( $ts ) {
				$generated = wp_date( 'H:i', $ts );
			}
		}
		$stale = ! empty( $payload['_stale'] );

		ob_start();
		?>
		<div class="tandem-view<?php echo $stale ? ' tandem-view--stale' : ''; ?>">
			<?php
			// Only show loads with status === 'planned'. Drop the rest before rendering.
			$sections = array_values( array_filter(
				$sections,
				static function ( $section ) {
					if ( ! is_array( $section ) ) {
						return false;
					}
					$status = isset( $section['status'] ) ? (string) $section['status'] : 'planned';
					return $status === 'planned';
				}
			) );
			?>

			<?php if ( empty( $sections ) ) : ?>
				<div class="tandem-empty"><?php esc_html_e( 'Inga tandems planerade.', 'skywin-hub' ); ?></div>
			<?php else : ?>
				<div class="tandem-table" role="table" aria-label="<?php esc_attr_e( 'Tandems', 'skywin-hub' ); ?>">
					<div class="tandem-table__head" role="row">
						<div class="tandem-cell tandem-cell--pilot" role="columnheader"><?php esc_html_e( 'Tandem', 'skywin-hub' ); ?></div>
						<div class="tandem-cell tandem-cell--photog" role="columnheader"><?php esc_html_e( 'Kamera', 'skywin-hub' ); ?></div>
						<div class="tandem-cell tandem-cell--media" role="columnheader">V/S</div>
						<div class="tandem-cell tandem-cell--pax" role="columnheader">Pax</div>
						<div class="tandem-cell tandem-cell--comment" role="columnheader"><?php esc_html_e( 'Kommentar', 'skywin-hub' ); ?></div>
						<div class="tandem-cell tandem-cell--color" role="columnheader" aria-label="<?php esc_attr_e( 'Blip Grupp', 'skywin-hub' ); ?>"></div>
					</div>
					<?php foreach ( $sections as $section ) : ?>
						<?php echo self::render_load( is_array( $section ) ? $section : [] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $generated !== '' ) : ?>
				<div class="tandem-view__meta">
					<?php if ( $stale ) : ?>
						<span class="tandem-view__stale" title="<?php esc_attr_e( 'Visar senast lyckade svar', 'skywin-hub' ); ?>"><?php esc_html_e( 'Offline-läge', 'skywin-hub' ); ?></span>
					<?php endif; ?>
					<span class="tandem-view__updated"><?php echo esc_html( sprintf( __( 'Uppdaterad %s', 'skywin-hub' ), $generated ) ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Map a Loadplanner colorGroup name to the Skyview group color index (1–10).
	 * Returns 0 when the color should not be considered a group (e.g. white / empty).
	 */
	private static function color_group_index( string $color ): int {
		static $map = [
			'red'     => 1,
			'rose'    => 1,
			'orange'  => 2,
			'amber'   => 2,
			'green'   => 3,
			'emerald' => 3,
			'blue'    => 4,
			'sky'     => 4,
			'violet'  => 5,
			'purple'  => 5,
			'pink'    => 6,
			'teal'    => 7,
			'cyan'    => 7,
			'yellow'  => 8,
			'lime'    => 8,
			'brown'   => 9,
			'slate'   => 9,
			'black'   => 10,
		];
		$color = strtolower( trim( $color ) );
		if ( $color === '' || $color === 'white' || $color === 'none' ) {
			return 0;
		}
		if ( isset( $map[ $color ] ) ) {
			return $map[ $color ];
		}
		// Unknown but non-empty color — assign a stable slot in 1..10 so it still groups.
		return ( abs( crc32( $color ) ) % 10 ) + 1;
	}

	private static function render_load( array $section ): string {
		$load_number = isset( $section['loadNumber'] ) ? (int) $section['loadNumber'] : 0;
		$aircraft    = isset( $section['aircraftName'] ) ? (string) $section['aircraftName'] : '';
		$status      = isset( $section['status'] ) ? (string) $section['status'] : 'planned';
		$refueling   = ! empty( $section['isRefueling'] );
		$jumps       = ( isset( $section['jumps'] ) && is_array( $section['jumps'] ) ) ? $section['jumps'] : [];
		$empty       = empty( $jumps );

		$title = $aircraft !== ''
			? sprintf( '%s - lift %d', $aircraft, $load_number )
			: sprintf( 'Lift %d', $load_number );

		$classes = [ 'tandem-load' ];
		if ( $refueling ) {
			$classes[] = 'tandem-load--refueling';
		}
		if ( $empty ) {
			$classes[] = 'tandem-load--empty';
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-load-number="<?php echo esc_attr( (string) $load_number ); ?>"
			data-status="<?php echo esc_attr( $status ); ?>"
			data-empty="<?php echo $empty ? 'true' : 'false'; ?>"
			role="rowgroup">
			<div class="tandem-load__head" role="row">
				<span class="tandem-load__title"><?php echo esc_html( $title ); ?></span>
			</div>
			<?php if ( $empty ) : ?>
				<div class="tandem-load__placeholder" role="row">
					<?php echo $refueling
						? esc_html__( 'Tankning – inga tandems tilldelade.', 'skywin-hub' )
						: esc_html__( 'Inga tandems tilldelade.', 'skywin-hub' ); ?>
				</div>
			<?php else : ?>
				<?php
				// Partition jumps: ungrouped (white / unknown / empty) first, then clustered by colorGroup in first-seen order.
				$ungrouped = [];
				$groups    = []; // color => [ 'index' => int, 'jumps' => [] ]
				foreach ( $jumps as $jump ) {
					$jump  = is_array( $jump ) ? $jump : [];
					$color = isset( $jump['colorGroup'] ) ? strtolower( trim( (string) $jump['colorGroup'] ) ) : '';
					$idx   = self::color_group_index( $color );
					if ( $idx === 0 ) {
						$ungrouped[] = $jump;
						continue;
					}
					if ( ! isset( $groups[ $color ] ) ) {
						$groups[ $color ] = [ 'index' => $idx, 'jumps' => [] ];
					}
					$groups[ $color ]['jumps'][] = $jump;
				}
				?>
				<?php foreach ( $ungrouped as $jump ) : ?>
					<?php echo self::render_jump( $jump ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
				<?php foreach ( $groups as $color_name => $group ) : ?>
					<div class="tandem-group tandem-group--color-<?php echo (int) $group['index']; ?>" data-color="<?php echo esc_attr( $color_name ); ?>" role="rowgroup">
						<?php foreach ( $group['jumps'] as $jump ) : ?>
							<?php echo self::render_jump( $jump ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_jump( array $jump ): string {
		$pax     = isset( $jump['passengerName'] ) ? (string) $jump['passengerName'] : '';
		$pilot   = isset( $jump['tandemPilotName'] ) ? (string) $jump['tandemPilotName'] : '';
		$photog  = isset( $jump['photographerName'] ) ? (string) $jump['photographerName'] : '';
		$media   = isset( $jump['mediaType'] ) ? (string) $jump['mediaType'] : 'na';
		$color   = isset( $jump['colorGroup'] ) ? strtolower( trim( (string) $jump['colorGroup'] ) ) : '';
		$comment = isset( $jump['comment'] ) ? (string) $jump['comment'] : '';
		$group_index = self::color_group_index( $color );

		$classes = [ 'tandem-row' ];
		if ( $color !== '' ) {
			$classes[] = 'tandem-row--' . preg_replace( '/[^a-z0-9_-]/', '', $color );
		}
		if ( $group_index > 0 ) {
			$classes[] = 'tandem-row--color-' . $group_index;
		}

		$media_code = '';
		switch ( $media ) {
			case 'photo':
				$media_code = 'S';
				break;
			case 'video':
				$media_code = 'V';
				break;
			case 'photo_and_video':
				$media_code = 'VS';
				break;
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-media="<?php echo esc_attr( $media ); ?>"
			data-color="<?php echo esc_attr( $color ); ?>"
			role="row">
			<div class="tandem-cell tandem-cell--pilot" role="cell"><?php echo esc_html( $pilot ); ?></div>
			<div class="tandem-cell tandem-cell--photog" role="cell"><?php echo esc_html( $photog ); ?></div>
			<div class="tandem-cell tandem-cell--media" role="cell"><?php echo esc_html( $media_code ); ?></div>
			<div class="tandem-cell tandem-cell--pax" role="cell"><?php echo esc_html( $pax ); ?></div>
			<div class="tandem-cell tandem-cell--comment" role="cell" title="<?php echo esc_attr( $comment ); ?>"><?php echo esc_html( $comment ); ?></div>
			<div class="tandem-cell tandem-cell--color" role="cell">
				<?php if ( $group_index > 0 ) : ?>
					<span class="tandem-color-dot" aria-label="<?php echo esc_attr( $color ); ?>" title="<?php echo esc_attr( $color ); ?>"></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_error( string $message ): string {
		return '<div class="tandem-error">' . esc_html( $message ) . '</div>';
	}
}

endif;
