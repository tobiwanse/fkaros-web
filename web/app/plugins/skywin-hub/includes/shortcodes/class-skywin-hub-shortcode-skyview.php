<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Skywin_Hub_Shortcode_Skyview' ) ) :

class Skywin_Hub_Shortcode_Skyview {

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Called by the shortcode handler.
	 * Usage: [skywin_hub_skyview date="2025-01-01" refresh="30"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function output( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'title'    => '',
				'date'     => '',
				'refresh'  => '30',
				'aircraft' => '',
			],
			$atts,
			'skywin_hub_skyview'
		);

		self::enqueue_assets();

		$endpoint = rest_url( 'skywin-hub/v1/skyview' );
		$title    = sanitize_text_field( $atts['title'] );
		$date     = sanitize_text_field( $atts['date'] );
		$refresh  = absint( $atts['refresh'] );
		$aircraft = sanitize_text_field( $atts['aircraft'] );
		$logged_in = is_user_logged_in() ? '1' : '0';

		if ( '' !== $aircraft ) {
			$endpoint = add_query_arg( 'aircraft', $aircraft, $endpoint );
		}
		return sprintf(
			'<div class="skyview-page" data-skyview-endpoint="%s" data-skyview-title="%s" data-skyview-date="%s" data-skyview-refresh="%d" data-skyview-logged-in="%s" data-skyview-sw="%s" data-skyview-vapid="%s" data-skyview-push-endpoint="%s" data-skyview-login-url="%s" data-skyview-logout-url="%s" data-skyview-queue-endpoint="%s"></div>',
			esc_attr( $endpoint ),
			esc_attr( $title ),
			esc_attr( $date ),
			$refresh,
			esc_attr( $logged_in ),
			esc_attr( trailingslashit( plugins_url( '', SW_PLUGIN_FILE ) ) . 'assets/js/skyview-sw.js' ),
			esc_attr( class_exists( 'Skywin_Hub_Push' ) ? Skywin_Hub_Push::get_vapid_public_key() : '' ),
			esc_attr( rest_url( 'skywin-hub/v1/push' ) ),
			esc_attr( wp_login_url( get_permalink() ) ),
			esc_attr( wp_logout_url( get_permalink() ) ),
			esc_attr( rest_url( 'skywin-hub/v1/jump-queue' ) )
		);
	}

	// ── REST route ───────────────────────────────────────────────────────────

	public static function register_rest_routes() {
		register_rest_route(
			'skywin-hub/v1',
			'/skyview',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_skyview' ],
				'permission_callback' => [ __CLASS__, 'rest_permission' ],
				'args'                => [
					'date' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					],
					'aircraft' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					],
				],
			]
		);

		register_rest_route(
			'skywin-hub/v1',
			'/jump-queue',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_jump_queue' ],
				'permission_callback' => [ __CLASS__, 'rest_permission' ],
				'args'                => [
					'date' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					],
				],
			]
		);

		register_rest_route(
			'skywin-hub/v1',
			'/skyview/skywish',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_add_skywish' ],
				'permission_callback' => [ __CLASS__, 'rest_mutation_permission' ],
				'args'                => [
					'memberId' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'memberName' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'jumpType' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'altitude' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'comment' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'date' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	public static function rest_permission( WP_REST_Request $request ) {
		unset( $request );
		return true;
	}

	public static function rest_mutation_permission( WP_REST_Request $request ) {
		unset( $request );

		if ( is_user_logged_in() ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden', __( 'Du måste vara inloggad för att ändra skywish.', 'skywin-hub' ), [ 'status' => 403 ] );
	}

	public static function rest_get_skyview( WP_REST_Request $request ) {
		$raw_date          = $request->get_param( 'date' );
		$has_selected_date = is_string( $raw_date ) && '' !== trim( $raw_date );
		$date              = sanitize_text_field( (string) $raw_date );
		$aircraft = sanitize_text_field( $request->get_param( 'aircraft' ) ?: '' );
		if ( empty( $date ) ) {
			$date = wp_date( 'Y-m-d' );
		}
		
		$result = self::build_payload( $date, $aircraft, ! $has_selected_date );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				[ 'error' => $result->get_error_message() ],
				500
			);
		}

		return new WP_REST_Response( $result, 200 );
	}

	public static function rest_get_jump_queue( WP_REST_Request $request ) {
		$db   = skywin_hub_db();
		$rows = $db->get_jump_queue();

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return new WP_REST_Response( [ 'items' => [] ], 200 );
		}

		// Expand rows: if a row has no member name but matches a group, expand to individual members.
		$expanded = [];
		foreach ( $rows as $row ) {
			$has_name = '' !== trim( (string) ( $row['FirstName'] ?? '' ) )
			         || '' !== trim( (string) ( $row['LastName'] ?? '' ) );

			if ( ! $has_name && ! empty( $row['GroupName'] ) ) {
				// InternalNo is actually a GroupNo – fetch group members.
				$group_members = $db->get_group_members( $row['InternalNo'] );
				if ( ! empty( $group_members ) ) {
					foreach ( $group_members as $gm ) {
						$expanded[] = array_merge( $row, [
							'FirstName'  => $gm['FirstName'],
							'LastName'   => $gm['LastName'],
							'NickName'   => $gm['NickName'],
							'MemberNo'   => $gm['MemberNo'],
							'Club'       => $gm['Club'],
							'Captain'    => $gm['Captain'] === 'Y' ? 1 : 0,
							'InternalNo' => $gm['InternalNo'],
							'_groupName' => $row['GroupName'],
						]);
					}
					continue;
				}
			}

			$expanded[] = $row;
		}

		// Group rows by ReqAsGroup.
		$groups = [];
		foreach ( $expanded as $row ) {
			$gid = (string) ( $row['ReqAsGroup'] ?? '0' );
			$groups[ $gid ][] = $row;
		}

		$normalized = [];
		$position   = 0;

		foreach ( $groups as $group_key => $members ) {
			$is_group    = count( $members ) > 1;
			$group_id    = $is_group ? self::build_scoped_group_id( 'queue', $group_key ) : null;
			$group_title = null;

			if ( $is_group ) {
				$first           = $members[0];
				$jumptype_group  = $first['JumptypeGroup'] ?? '';
				// Use stored group name if available, otherwise jumptype.
				if ( ! empty( $first['_groupName'] ) ) {
					$group_title = $first['_groupName'];
				} elseif ( in_array( $jumptype_group, [ 'TY', 'TN' ], true ) ) {
					$group_title = 'Tandem';
				} else {
					$group_title = $first['JumptypeName'] ?? $first['Jumptype'] ?? '';
				}
				$group_title .= ' (' . count( $members ) . ')';
			}

			foreach ( $members as $row ) {
				++$position;

				$label = self::build_queue_label( $row );
				if ( '' === $label ) {
					$label = $row['JumptypeName'] ?? $row['Jumptype'] ?? '?';
				}

				$normalized[] = [
					'id'                   => wp_generate_uuid4(),
					'bookingId'            => sanitize_text_field( (string) ( $row['RequestNo'] ?? '' ) ),
					'position'             => $position,
					'label'                => sanitize_text_field( $label ),
					'internalNo'           => sanitize_text_field( (string) ( $row['InternalNo'] ?? '' ) ),
					'jump_type_name'       => sanitize_text_field( (string) ( $row['JumptypeName'] ?? '' ) ),
					'jump_type'            => sanitize_text_field( (string) ( $row['Jumptype'] ?? '' ) ),
					'altitude'             => sanitize_text_field( (string) ( $row['Altitude'] ?? '' ) ),
					'altitudeUnit'         => sanitize_text_field( (string) ( $row['AltitudeUnit'] ?? '' ) ),
					'student_jump_no'      => ! empty( $row['StudentJumpNo'] ) ? intval( $row['StudentJumpNo'] ) : null,
					'jumper_from_group_no' => null,
					'captain'              => ! empty( $row['Captain'] ),
					'jumptype_group'       => sanitize_text_field( (string) ( $row['JumptypeGroup'] ?? '' ) ),
					'group_id'             => $group_id,
					'group_title'          => $group_title,
				];
			}
		}

		return new WP_REST_Response( [ 'items' => $normalized ], 200 );
	}

	private static function build_queue_label( array $row ): string {
		$first    = trim( (string) ( $row['FirstName'] ?? '' ) );
		$last     = trim( (string) ( $row['LastName'] ?? '' ) );
		$nick     = trim( (string) ( $row['NickName'] ?? '' ) );
		$jumptype = trim( (string) ( $row['JumptypeName'] ?? $row['Jumptype'] ?? '' ) );
		$altitude = trim( (string) ( $row['Altitude'] ?? '' ) );

		$parts = [];

		if ( '' !== $first || '' !== $last ) {
			$name = trim( $first . ' ' . $last );
			if ( '' !== $nick && $nick !== $first && $nick !== $last ) {
				$name .= ' "' . $nick . '"';
			}
			$parts[] = $name;
		}

		if ( '' !== $jumptype ) {
			$parts[] = $jumptype;
		}
		if ( '' !== $altitude ) {
			$parts[] = $altitude;
		}

		return implode( ' ', $parts );
	}

	public static function rest_add_skywish( WP_REST_Request $request ) {
		$member_id   = sanitize_text_field( (string) $request->get_param( 'memberId' ) );
		$member_name = sanitize_text_field( (string) $request->get_param( 'memberName' ) );
		$jump_type   = sanitize_text_field( (string) $request->get_param( 'jumpType' ) );
		$altitude    = sanitize_text_field( (string) $request->get_param( 'altitude' ) );
		$comment     = sanitize_text_field( (string) $request->get_param( 'comment' ) );
		$date        = sanitize_text_field( (string) $request->get_param( 'date' ) );

		if ( '' === $member_id || '' === $jump_type ) {
			return new WP_REST_Response(
				[ 'error' => __( 'memberId och jumpType krävs.', 'skywin-hub' ) ],
				400
			);
		}

		if ( '' === $date ) {
			$date = wp_date( 'Y-m-d' );
		}

		$api_url = get_option( 'skywin_hub_skyview_api_url', '' );
		if ( empty( $api_url ) ) {
			$api_url = self::build_default_skyview_url();
		}

		if ( empty( $api_url ) ) {
			return new WP_REST_Response(
				[ 'error' => __( 'SkyView API är inte konfigurerad.', 'skywin-hub' ) ],
				500
			);
		}

		$auth_header = self::build_auth_header();
		$headers     = [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		];
		if ( '' !== $auth_header ) {
			$headers['Authorization'] = $auth_header;
		}

		$endpoint_url = self::build_load_jump_request_url( $api_url );
		if ( '' === $endpoint_url ) {
			return new WP_REST_Response(
				[ 'error' => __( 'Kunde inte bygga loadjumprequest-url.', 'skywin-hub' ) ],
				500
			);
		}

		$post_response = wp_remote_post(
			$endpoint_url,
			[
				'headers'   => $headers,
				'timeout'   => 15,
				'sslverify' => true,
				'body'      => wp_json_encode(
					[
						'memberId'       => $member_id,
						'internalNo'     => $member_id,
						'searchResultId' => $member_id,
						'memberName'     => $member_name,
						'jumpType'       => $jump_type,
						'jumptype'       => $jump_type,
						'altitude'       => $altitude,
						'comment'        => $comment,
						'jumpDate'       => $date,
						'date'           => $date,
					]
				),
			]
		);

		if ( is_wp_error( $post_response ) ) {
			return new WP_REST_Response(
				[ 'error' => $post_response->get_error_message() ],
				500
			);
		}

		$code = wp_remote_retrieve_response_code( $post_response );

		if ( 405 === $code ) {
			$fallback_response = self::submit_skywish_via_form_page( $api_url, $member_id, $member_name, $jump_type, $altitude, $comment );

			if ( is_wp_error( $fallback_response ) ) {
				return new WP_REST_Response(
					[ 'error' => $fallback_response->get_error_message() ],
					500
				);
			}

			$post_response = $fallback_response;
			$code          = wp_remote_retrieve_response_code( $post_response );
		}

		if ( $code < 200 || $code >= 400 ) {
			return new WP_REST_Response(
				[
					'error'  => __( 'SkyWin returnerade ett fel vid skapandet av skywish-raden.', 'skywin-hub' ),
					'status' => $code,
					'body'   => wp_remote_retrieve_body( $post_response ),
				],
				500
			);
		}

		$refresh_headers = [ 'Accept' => 'application/json' ];
		if ( '' !== $auth_header ) {
			$refresh_headers['Authorization'] = $auth_header;
		}

		return new WP_REST_Response(
			[
				'success'     => true,
				'memberId'    => $member_id,
				'memberName'  => $member_name,
				'skywishList' => self::fetch_skywish_list( $api_url, $date, $refresh_headers ),
			],
			200
		);
	}

	// ── Asset enqueueing ─────────────────────────────────────────────────────

	private static function enqueue_assets() {
		$plugin_url = trailingslashit( plugins_url( '', SW_PLUGIN_FILE ) );
		$plugin_dir = trailingslashit( plugin_dir_path( SW_PLUGIN_FILE ) );

		$css_file = $plugin_dir . 'assets/css/skyview.css';
		$js_file  = $plugin_dir . 'assets/js/skyview.js';

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'skywin-hub-skyview',
				$plugin_url . 'assets/css/skyview.css',
				[],
				filemtime( $css_file )
			);
		}

		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'skywin-hub-skyview',
				$plugin_url . 'assets/js/skyview.js',
				[],
				filemtime( $js_file ),
				true
			);
		}
	}

	// ── Data pipeline ────────────────────────────────────────────────────────

	public static function build_payload( string $date, string $aircraft = '', bool $filter_active_only = false ): array|WP_Error {
		$db = skywin_hub_db();

		$raw_loads  = $db->get_loads( $date );
		$raw_jumps  = $db->get_load_jumpers( $date );
		$raw_roles  = $db->get_load_roles( $date );
		$raw_queue  = $db->get_jump_queue();

		// Index jumpers and roles by PlaneReg::LoadNo.
		$jumpers_by_load = [];
		foreach ( $raw_jumps as $j ) {
			$key = $j['PlaneReg'] . '::' . $j['LoadNo'];
			$jumpers_by_load[ $key ][] = $j;
		}

		$roles_by_load = [];
		foreach ( $raw_roles as $r ) {
			$key = $r['PlaneReg'] . '::' . $r['LoadNo'];
			$roles_by_load[ $key ][] = $r;
		}

		$loads = [];

		foreach ( $raw_loads as $load ) {
			$load_status = intval( $load['LoadStatus'] );

			// Filter by active status (1=Planerad, 2=Bokad, 3=Lyft).
			if ( $filter_active_only && $load_status > 3 ) {
				continue;
			}

			// Filter by aircraft if specified.
			if ( '' !== $aircraft && false === stripos( $load['PlaneReg'], $aircraft ) ) {
				continue;
			}

			$load_no  = intval( $load['LoadNo'] );
			$load_key = $load['PlaneReg'] . '::' . $load_no;
			$load_id  = self::build_scoped_id( 'load', $load['PlaneReg'] . '-' . $load_no );

			// Crew roles.
			$pilot       = '';
			$jump_leader = '';
			$roles       = $roles_by_load[ $load_key ] ?? [];
			foreach ( $roles as $role ) {
				$role_name = self::build_member_display_name( $role );
				if ( 'PILOT' === $role['RoleType'] && '' === $pilot ) {
					$pilot = $role_name;
				} elseif ( 'JUMPLEADER' === $role['RoleType'] && '' === $jump_leader ) {
					$jump_leader = $role_name;
				}
			}

			// Time calculations – DB stores local time; extract HH:MM directly (no tz conversion).
			$lifted_at  = $load['LiftedAt'] ?? '';
			$dropped_at = $load['DroppedAt'] ?? '';
			$landed_at  = $load['LandedAt'] ?? '';
			$time       = strlen( $lifted_at ) >= 16 ? substr( $lifted_at, 11, 5 ) : '';

			$minutes_until = null;
			if ( '' !== $time ) {
				$minutes_until = self::minutes_until( $time, $date );
			}

			// Seats.
			$max_pass    = intval( $load['MaxPass'] );
			$jumper_rows = $jumpers_by_load[ $load_key ] ?? [];
			$booked      = count( $jumper_rows );
			$seats_text  = $booked . '/' . $max_pass;

			// Build jumpers array.
			$jumpers = self::build_jumpers_from_db( $jumper_rows, $load_id, $db );

			$loads[] = [
				'id'             => $load_id,
				'loadNo'         => $load_no,
				'loadStatus'     => $load_status,
				'loadStatusName' => sanitize_text_field( $load['LoadStatusName'] ?? '' ),
				'lift'           => sanitize_text_field( trim( $load['PlaneReg'] ) . ' #' . $load_no ),
				'seats'          => $seats_text,
				'chief'          => '',
				'pilot'          => sanitize_text_field( $pilot ),
				'jumpLeader'     => sanitize_text_field( $jump_leader ),
				'time'           => $time,
				'minutesUntil'   => $minutes_until,
				'timeLeftText'   => '',
				'liftTime'       => strlen( $lifted_at ) >= 16 ? substr( $lifted_at, 11, 5 ) : '',
				'droppedAt'      => strlen( $dropped_at ) >= 16 ? substr( $dropped_at, 11, 5 ) : '',
				'landedAt'       => strlen( $landed_at ) >= 16 ? substr( $landed_at, 11, 5 ) : '',
				'onlyFlying'     => false,
				'comment'        => sanitize_text_field( $load['Comment'] ?? '' ),
				'jumpers'        => $jumpers,
			];
		}

		$loads = self::sort_normalized_loads( $loads );

		// Count individual jumpers (expand group entries).
		$jump_queue_count = 0;
		foreach ( $raw_queue as $qr ) {
			$has_name = '' !== trim( (string) ( $qr['FirstName'] ?? '' ) )
			         || '' !== trim( (string) ( $qr['LastName'] ?? '' ) );
			if ( ! $has_name && ! empty( $qr['GroupName'] ) ) {
				$gm = $db->get_group_members( $qr['InternalNo'] );
				$jump_queue_count += max( 1, count( $gm ) );
			} else {
				++$jump_queue_count;
			}
		}

		return [
			'loads'          => $loads,
			'message'        => '',
			'jumpQueueCount' => $jump_queue_count,
			'altitudeUnit'   => 'm',
			'skywishList'    => [],
		];
	}

	private static function build_member_display_name( array $row ): string {
		$first = trim( (string) ( $row['FirstName'] ?? '' ) );
		$last  = trim( (string) ( $row['LastName'] ?? '' ) );
		$nick  = trim( (string) ( $row['NickName'] ?? '' ) );

		if ( '' === $first && '' === $last ) {
			return '';
		}

		$name = trim( $first . ' ' . $last );
		if ( '' !== $nick && $nick !== $first && $nick !== $last ) {
			$name .= ' "' . $nick . '"';
		}
		return $name;
	}

	private static function build_jumpers_from_db( array $rows, string $load_id, $db ): array {
		// Group jumpers by GroupNo for proper group display.
		$by_group    = [];
		$no_group    = [];

		foreach ( $rows as $row ) {
			$group_no = (string) ( $row['GroupNo'] ?? '0' );
			if ( '0' !== $group_no && '' !== $group_no ) {
				$by_group[ $group_no ][] = $row;
			} else {
				$no_group[] = $row;
			}
		}

		$jumpers = [];

		// Grouped jumpers.
		foreach ( $by_group as $group_no => $members ) {
			$group_id    = self::build_scoped_group_id( $load_id, (string) $group_no );
			$first       = $members[0];
			$group_name  = trim( (string) ( $first['GroupName'] ?? '' ) );
			$jumptype_group = $first['JumptypeGroup'] ?? '';

			if ( '' === $group_name ) {
				if ( in_array( $jumptype_group, [ 'TY', 'TN' ], true ) ) {
					$group_name = 'Tandem';
				} else {
					$group_name = $first['JumptypeName'] ?? $first['Jumptype'] ?? '';
				}
			}
			$group_title = $group_name . ' (' . count( $members ) . ')';

			foreach ( $members as $row ) {
				$has_name = '' !== trim( (string) ( $row['FirstName'] ?? '' ) )
				         || '' !== trim( (string) ( $row['LastName'] ?? '' ) );
				$label = $has_name
					? self::build_member_display_name( $row )
					: ( $row['JumptypeName'] ?? $row['Jumptype'] ?? '?' );

				$jumpers[] = [
					'id'                   => wp_generate_uuid4(),
					'bookingId'            => '',
					'label'                => sanitize_text_field( $label ),
					'internalNo'           => sanitize_text_field( (string) ( $row['InternalNo'] ?? '' ) ),
					'jump_type_name'       => sanitize_text_field( (string) ( $row['JumptypeName'] ?? '' ) ),
					'jump_type'            => sanitize_text_field( (string) ( $row['Jumptype'] ?? '' ) ),
					'altitude'             => sanitize_text_field( (string) ( $row['Altitude'] ?? '' ) ),
					'altitudeUnit'         => sanitize_text_field( (string) ( $row['AltitudeUnit'] ?? '' ) ),
					'student_jump_no'      => ! empty( $row['StudentJumpNo'] ) ? intval( $row['StudentJumpNo'] ) : null,
					'jumper_from_group_no' => ! empty( $row['JumperFromGroupNo'] ) ? (string) $row['JumperFromGroupNo'] : null,
					'captain'              => ( $row['Captain'] ?? '' ) === 'Y',
					'jumptype_group'       => sanitize_text_field( (string) ( $row['JumptypeGroup'] ?? '' ) ),
					'group_id'             => $group_id,
					'group_title'          => $group_title,
				];
			}
		}

		// Ungrouped jumpers.
		foreach ( $no_group as $row ) {
			$has_name = '' !== trim( (string) ( $row['FirstName'] ?? '' ) )
			         || '' !== trim( (string) ( $row['LastName'] ?? '' ) );
			$label = $has_name
				? self::build_member_display_name( $row )
				: ( $row['JumptypeName'] ?? $row['Jumptype'] ?? '?' );

			$jumpers[] = [
				'id'                   => wp_generate_uuid4(),
				'bookingId'            => '',
				'label'                => sanitize_text_field( $label ),
				'internalNo'           => sanitize_text_field( (string) ( $row['InternalNo'] ?? '' ) ),
				'jump_type_name'       => sanitize_text_field( (string) ( $row['JumptypeName'] ?? '' ) ),
				'jump_type'            => sanitize_text_field( (string) ( $row['Jumptype'] ?? '' ) ),
				'altitude'             => sanitize_text_field( (string) ( $row['Altitude'] ?? '' ) ),
				'altitudeUnit'         => sanitize_text_field( (string) ( $row['AltitudeUnit'] ?? '' ) ),
				'student_jump_no'      => ! empty( $row['StudentJumpNo'] ) ? intval( $row['StudentJumpNo'] ) : null,
				'jumper_from_group_no' => ! empty( $row['JumperFromGroupNo'] ) ? (string) $row['JumperFromGroupNo'] : null,
				'captain'              => ( $row['Captain'] ?? '' ) === 'Y',
				'jumptype_group'       => sanitize_text_field( (string) ( $row['JumptypeGroup'] ?? '' ) ),
				'group_id'             => null,
				'group_title'          => null,
			];
		}

		return $jumpers;
	}

	// ── Normalisation ────────────────────────────────────────────────────────

	private static function normalize_loads( array $data, string $date, string $aircraft = '', bool $filter_active_only = false ): array {
		// Accept either a top-level "loads" key or a bare array of loads.
		$raw_loads = $data['loads'] ?? ( $data['data']['loads'] ?? ( isset( $data[0] ) ? $data : [] ) );

		if ( ! is_array( $raw_loads ) ) {
			return [];
		}

		$loads = [];

		foreach ( $raw_loads as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$load_status = $raw['loadStatus'] ?? $raw['load_status'] ?? $raw['status'] ?? null;
			if ( $filter_active_only && ! self::is_active_load_status( $load_status ) ) {
				continue;
			}

			// Filter by aircraft registration if specified.
			if ( '' !== $aircraft ) {
				$raw_aircraft = (string) ( $raw['lift'] ?? $raw['aircraft'] ?? $raw['skyText'] ?? $raw['planeReg'] ?? '' );
				$raw_id_str   = (string) ( $raw['id'] ?? $raw['loadId'] ?? '' );
				if (
					false === stripos( $raw_aircraft, $aircraft ) &&
					false === stripos( $raw_id_str, $aircraft )
				) {
					continue;
				}
			}

			$raw_load_id = sanitize_text_field( $raw['id'] ?? $raw['loadId'] ?? self::build_fallback_load_id( $raw, $date ) );
			$load_id     = self::build_scoped_id( 'load', $raw_load_id );
			$time    = sanitize_text_field( $raw['liftTime'] ?? $raw['departureTime'] ?? $raw['time'] ?? '' );

			$max_pass        = isset( $raw['maxPass'] ) ? intval( $raw['maxPass'] ) : 0;
			$slots_available = isset( $raw['slotsAvailable'] ) ? intval( $raw['slotsAvailable'] ) : 0;
			$booked_seats    = ( $max_pass > 0 ) ? max( 0, $max_pass - $slots_available ) : 0;
			$seats_text      = sanitize_text_field( $raw['seats'] ?? '' );
			if ( '' === $seats_text && $max_pass > 0 ) {
				$seats_text = $booked_seats . '/' . $max_pass;
			}

			$has_time_left = array_key_exists( 'timeLeft', $raw );
			$time_left_raw = $raw['timeLeft'] ?? null;
			if ( $has_time_left ) {
				if ( is_numeric( $time_left_raw ) ) {
					$minutes_until = intval( $time_left_raw );
				} elseif ( null === $time_left_raw || 'null' === strtolower( trim( (string) $time_left_raw ) ) ) {
					// Explicit null from API means "do not display time left".
					$minutes_until = null;
				} else {
					$minutes_until = self::minutes_until( $time, $date );
				}
			} else {
				$minutes_until = self::minutes_until( $time, $date );
			}

			$time_left_text = sanitize_text_field( $raw['timeLeftText'] ?? '' );
			if ( null === $minutes_until ) {
				$time_left_text = '';
			}

			$loads[] = [
				'id'             => $load_id,
				'loadNo'         => intval( $raw['loadNo'] ?? $raw['number'] ?? 0 ),
				'loadStatus'     => is_numeric( $load_status ) ? intval( $load_status ) : sanitize_text_field( (string) $load_status ),
				'loadStatusName' => sanitize_text_field( $raw['loadStatusName'] ?? $raw['statusName'] ?? $raw['loadstatus_name'] ?? '' ),
				'lift'           => sanitize_text_field( $raw['lift'] ?? $raw['aircraft'] ?? $raw['skyText'] ?? $raw['planeReg'] ?? '' ),
				'seats'          => $seats_text,
				'chief'          => sanitize_text_field( $raw['chief'] ?? $raw['loadChief'] ?? self::extract_first_member_name( $raw['fellingLeaders'] ?? [] ) ),
				'pilot'          => sanitize_text_field( self::extract_first_member_name( $raw['pilots'] ?? [] ) ?: self::extract_crew_name_by_childtype( $raw, 'PILOT' ) ),
				'jumpLeader'     => sanitize_text_field( self::extract_first_member_name( $raw['jumpLeaders'] ?? [] ) ?: self::extract_crew_name_by_childtype( $raw, 'JUMP_LEADER' ) ),
				'time'           => $time,
				'minutesUntil'   => $minutes_until,
				'timeLeftText'   => $time_left_text,
				'liftTime'       => sanitize_text_field( $raw['liftTime'] ?? $raw['actualLiftTime'] ?? $raw['actual_lift_time'] ?? '' ),
				'droppedAt'      => sanitize_text_field( $raw['droppedAt'] ?? $raw['dropped_at'] ?? $raw['dropTime'] ?? '' ),
				'landedAt'       => sanitize_text_field( $raw['landedAt'] ?? $raw['landed_at'] ?? $raw['landTime'] ?? '' ),
				'onlyFlying'     => ! empty( $raw['onlyFlying'] ?? $raw['only_flying'] ?? false ),
				'comment'        => sanitize_text_field( $raw['comment'] ?? $raw['loadComment'] ?? $raw['remarks'] ?? $raw['note'] ?? '' ),
				'jumpers'        => self::deduplicate_jumpers( self::extract_jumpers( $raw, $load_id ) ),
			];
		}

		return self::sort_normalized_loads( $loads );
	}

	private static function extract_jump_queue_count( array $data ): ?int {
		$value = $data['jumpQueueCount'] ?? $data['jump_queue_count'] ?? $data['queueCount'] ?? $data['data']['jumpQueueCount'] ?? null;

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return intval( $value );
	}

	private static function fetch_skywish_list( string $skyview_api_url, string $date, array $headers ): array {
		$endpoint = self::build_load_jump_request_url( $skyview_api_url );

		if ( '' === $endpoint ) {
			return [];
		}

		$url = add_query_arg(
			[
				'jumpDate' => $date,
				'date'     => $date,
			],
			$endpoint
		);

		$response = wp_remote_get(
			$url,
			[
				'headers'   => $headers,
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return [];
		}

		return self::normalize_skywish_list( $data );
	}

	private static function build_load_jump_request_url( string $skyview_api_url ): string {
		$base = rtrim( trim( $skyview_api_url ), '/' );
		if ( '' === $base ) {
			return '';
		}

		if ( preg_match( '#/skyview$#i', $base ) ) {
			return preg_replace( '#/skyview$#i', '/loadjumprequest', $base );
		}

		return $base . '/loadjumprequest';
	}

	private static function build_upstream_jump_queue_url( string $skyview_api_url ): string {
		$base = rtrim( trim( $skyview_api_url ), '/' );
		if ( '' === $base ) {
			return '';
		}

		if ( preg_match( '#/skyview$#i', $base ) ) {
			return preg_replace( '#/skyview$#i', '/jump-queue', $base );
		}

		return $base . '/jump-queue';
	}

	private static function normalize_jump_queue( array $data ): array {
		$raw_items = [];

		if ( isset( $data[0] ) ) {
			$raw_items = $data;
		} elseif ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
			$raw_items = $data['items'];
		} elseif ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$raw_items = $data['data'];
		} elseif ( isset( $data['jumpQueue'] ) && is_array( $data['jumpQueue'] ) ) {
			$raw_items = $data['jumpQueue'];
		} elseif ( isset( $data['queue'] ) && is_array( $data['queue'] ) ) {
			$raw_items = $data['queue'];
		}

		if ( ! is_array( $raw_items ) || empty( $raw_items ) ) {
			return [];
		}

		// If items carry childType (same nested format as loads), reuse the children extractor.
		$first = reset( $raw_items );
		if ( is_array( $first ) && isset( $first['childType'] ) ) {
			$jumpers = self::extract_jumpers_from_children( $raw_items, 'queue' );
			// Override label with skyText when available (raw items are flat for JUMP children).
			foreach ( $jumpers as &$j ) {
				// Find original raw item by bookingId to get skyText.
				foreach ( $raw_items as $raw ) {
					if ( ! is_array( $raw ) ) {
						continue;
					}
					$raw_id = (string) ( $raw['id'] ?? $raw['loadJumpRequestId'] ?? '' );
					if ( '' !== $raw_id && $raw_id === ( $j['bookingId'] ?? '' ) ) {
						$sky = isset( $raw['skyText'] ) ? trim( (string) $raw['skyText'] ) : '';
						if ( '' !== $sky ) {
							$j['label'] = sanitize_text_field( $sky );
						}
						break;
					}
					// Also check nested children for groups.
					if ( isset( $raw['children'] ) && is_array( $raw['children'] ) ) {
						foreach ( $raw['children'] as $rc ) {
							if ( ! is_array( $rc ) ) {
								continue;
							}
							$rc_id = (string) ( $rc['id'] ?? $rc['loadJumpRequestId'] ?? '' );
							if ( '' !== $rc_id && $rc_id === ( $j['bookingId'] ?? '' ) ) {
								$sky = isset( $rc['skyText'] ) ? trim( (string) $rc['skyText'] ) : '';
								if ( '' !== $sky ) {
									$j['label'] = sanitize_text_field( $sky );
								}
								break 2;
							}
						}
					}
				}
			}
			unset( $j );
			return $jumpers;
		}

		$normalized = [];
		$position   = 0;

		foreach ( $raw_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sky_text = isset( $item['skyText'] ) ? trim( (string) $item['skyText'] ) : '';

			$name = (string) (
				$item['member']['name']
				?? $item['memberName']
				?? $item['name']
				?? $item['text']
				?? $item['skyText']
				?? ''
			);
			$name = self::strip_quoted_name_parts( $name );

			if ( '' === $name && '' === $sky_text ) {
				continue;
			}

			$display_label = '' !== $sky_text ? $sky_text : $name;

			++$position;

			// Build a scoped group ID from whichever group key is present.
			$raw_group_key = sanitize_text_field( (string) ( $item['groupId'] ?? $item['group_id'] ?? $item['groupNo'] ?? $item['group_no'] ?? '' ) );
			$group_id      = ( '' !== $raw_group_key && '0' !== $raw_group_key )
				? self::build_scoped_group_id( 'queue', $raw_group_key )
				: null;
			$group_title   = null !== $group_id
				? ( sanitize_text_field( self::strip_quoted_name_parts( (string) ( $item['groupTitle'] ?? $item['group_title'] ?? $item['groupName'] ?? '' ) ) ) ?: null )
				: null;

			$normalized[] = [
				'id'                   => wp_generate_uuid4(),
				'bookingId'            => sanitize_text_field( (string) ( $item['id'] ?? $item['queueId'] ?? $item['loadJumpRequestId'] ?? '' ) ),
				'position'             => $position,
				'label'                => sanitize_text_field( $display_label ),
				'internalNo'           => sanitize_text_field( (string) ( $item['internalNo'] ?? '' ) ),
				'jump_type_name'       => sanitize_text_field( (string) ( $item['jumpTypeName'] ?? $item['jumptypeName'] ?? $item['jumpType'] ?? '' ) ),
				'jump_type'            => sanitize_text_field( (string) ( $item['jumpType'] ?? $item['activity'] ?? $item['type'] ?? '' ) ),
				'altitude'             => sanitize_text_field( (string) ( $item['altitude'] ?? $item['alt'] ?? $item['jumpAlt'] ?? '' ) ),
				'altitudeUnit'         => sanitize_text_field( (string) ( $item['altitudeUnit'] ?? $item['altitude_unit'] ?? '' ) ),
				'student_jump_no'      => self::parse_student_jump_no( $item ),
				'jumper_from_group_no' => self::parse_jumper_from_group_no( $item ),
				'captain'              => ! empty( $item['captain'] ),
				'jumptype_group'       => sanitize_text_field( (string) ( $item['jumptypeGroup'] ?? $item['jumptype_group'] ?? '' ) ),
				'group_id'             => $group_id,
				'group_title'          => $group_title,
			];
		}

		return $normalized;
	}

	private static function build_load_jump_request_page_url( string $skyview_api_url ): string {
		$base = rtrim( trim( $skyview_api_url ), '/' );

		if ( '' === $base ) {
			return '';
		}

		if ( preg_match( '#/api(?:/v\d+)?/skyview$#i', $base ) ) {
			return preg_replace( '#/api(?:/v\d+)?/skyview$#i', '/loadjumprequest/index', $base );
		}

		if ( preg_match( '#/skyview$#i', $base ) ) {
			return preg_replace( '#/skyview$#i', '/loadjumprequest/index', $base );
		}

		return rtrim( $base, '/' ) . '/loadjumprequest/index';
	}

	private static function extract_html_input_value( string $html, string $input_name ): string {
		$pattern = '/name="' . preg_quote( $input_name, '/' ) . '"[^>]*value="([^"]*)"/i';

		if ( preg_match( $pattern, $html, $matches ) ) {
			return html_entity_decode( (string) $matches[1], ENT_QUOTES, 'UTF-8' );
		}

		return '';
	}

	private static function submit_skywish_via_form_page( string $api_url, string $member_id, string $member_name, string $jump_type, string $altitude, string $comment ) {
		$page_url = self::build_load_jump_request_page_url( $api_url );

		if ( '' === $page_url ) {
			return new WP_Error( 'skywish_form_url_error', __( 'Kunde inte bygga fallback-url för skywish-formuläret.', 'skywin-hub' ) );
		}

		$auth_header = self::build_auth_header();
		$headers     = [ 'Accept' => 'text/html' ];
		if ( '' !== $auth_header ) {
			$headers['Authorization'] = $auth_header;
		}

		$form_page = wp_remote_get(
			$page_url,
			[
				'headers'   => $headers,
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $form_page ) ) {
			return $form_page;
		}

		$form_html = wp_remote_retrieve_body( $form_page );
		$cookies   = wp_remote_retrieve_cookies( $form_page );
		$token     = self::extract_html_input_value( $form_html, 'SYNCHRONIZER_TOKEN' );
		$token_uri = self::extract_html_input_value( $form_html, 'SYNCHRONIZER_URI' );

		if ( '' === $token || '' === $token_uri ) {
			return new WP_Error( 'skywish_form_token_error', __( 'Kunde inte läsa token från loadjumprequest-formuläret.', 'skywin-hub' ) );
		}

		return wp_remote_post(
			$page_url,
			[
				'headers'   => $headers,
				'cookies'   => $cookies,
				'timeout'   => 15,
				'sslverify' => true,
				'body'      => [
					'SYNCHRONIZER_TOKEN'   => $token,
					'SYNCHRONIZER_URI'     => $token_uri,
					'browserId'            => 'null',
					'requestNo'            => '',
					'searchResultId'       => $member_id,
					'searchResultType'     => 'member',
					'searchResultIsTandem' => 'false',
					'searchResultBalance'  => '',
					'openGroupNumber'      => '',
					'jumptype'             => $jump_type,
					'altitude'             => $altitude,
					'comment'              => $comment,
					'climateAmount'        => '',
					'extraAmount'          => '',
					'discountedPrice'      => '',
					'totalAmount'          => '',
					'typestudentjumpno'    => '',
					'typestudentjumprowno' => '',
					'passengerType'        => 'pax',
					'passenger_id'         => '',
					'passenger_idType'     => '',
					'videoMemberType'      => 'none',
					'videoMember'          => '',
					'videoType'            => '',
					'wingcamType'          => '',
					'_action_save'         => 'Add jumper(s)',
				],
			]
		);
	}

	private static function normalize_skywish_list( array $data ): array {
		$raw_items = [];

		if ( isset( $data[0] ) ) {
			$raw_items = $data;
		} elseif ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$raw_items = $data['data'];
		} elseif ( isset( $data['loadJumpRequests'] ) && is_array( $data['loadJumpRequests'] ) ) {
			$raw_items = $data['loadJumpRequests'];
		} elseif ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
			$raw_items = $data['items'];
		}

		if ( ! is_array( $raw_items ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $raw_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$name = (string) (
				$item['member']['name']
				?? $item['memberName']
				?? $item['name']
				?? $item['text']
				?? $item['skyText']
				?? ''
			);
			$name = self::strip_quoted_name_parts( $name );

			if ( '' === $name ) {
				continue;
			}

			$normalized[] = [
				'id'           => sanitize_text_field( (string) ( $item['id'] ?? $item['loadJumpRequestId'] ?? $item['internalNo'] ?? uniqid( 'wish_', true ) ) ),
				'name'         => sanitize_text_field( $name ),
				'jumpTypeName' => sanitize_text_field( (string) ( $item['jumpTypeName'] ?? $item['jumptypeName'] ?? $item['jumpType'] ?? '' ) ),
				'altitude'     => sanitize_text_field( (string) ( $item['altitude'] ?? $item['alt'] ?? $item['jumpAlt'] ?? '' ) ),
			];
		}

		return $normalized;
	}

	private static function is_active_load_status( $load_status ): bool {
		if ( null === $load_status ) {
			return true;
		}

		$value = trim( (string) $load_status );
		if ( '' === $value ) {
			return true;
		}

		// Hide known inactive numeric statuses.
		// Requested: also hide loadStatus 3 and 4.
		if ( is_numeric( $load_status ) ) {
			$status_code = intval( $load_status );
			if ( in_array( $status_code, [ 3, 4 ], true ) ) {
				return false;
			}

			return true;
		}

		$normalized = strtoupper( $value );

		if ( in_array( $normalized, [ 'ACTIVE', 'OPEN', 'READY', 'PLANNED', 'BOARDING' ], true ) ) {
			return true;
		}

		if ( in_array( $normalized, [ 'CANCELLED', 'CANCELED', 'CLOSED', 'DONE', 'ARCHIVED', 'INACTIVE', 'FINISHED' ], true ) ) {
			return false;
		}

		// Unknown status format: keep the load instead of dropping everything.
		return true;
	}

	private static function sort_normalized_loads( array $loads ): array {
		usort(
			$loads,
			static function ( array $left, array $right ): int {
				$left_no  = intval( $left['loadNo'] ?? 0 );
				$right_no = intval( $right['loadNo'] ?? 0 );

				if ( $left_no !== $right_no ) {
					return $left_no <=> $right_no;
				}

				return strcmp( (string) ( $left['id'] ?? '' ), (string) ( $right['id'] ?? '' ) );
			}
		);

		return $loads;
	}

	/**
	 * Remove group members that also appear as singles (same label).
	 *
	 * The SkyWin API sometimes nests single jumpers inside an unrelated group.
	 * When a jumper appears both as a top-level single and inside a group,
	 * keep the single and remove the duplicate from the group.
	 */
	private static function deduplicate_jumpers( array $jumpers ): array {
		$single_labels = [];

		foreach ( $jumpers as $jumper ) {
			if ( empty( $jumper['group_id'] ) ) {
				$single_labels[ mb_strtolower( trim( $jumper['label'] ?? '' ) ) ] = true;
			}
		}

		if ( empty( $single_labels ) ) {
			return $jumpers;
		}

		return array_values(
			array_filter(
				$jumpers,
				static function ( array $jumper ) use ( $single_labels ): bool {
					// Keep all singles.
					if ( empty( $jumper['group_id'] ) ) {
						return true;
					}

					// Drop grouped jumpers whose label matches a single.
					$label = mb_strtolower( trim( $jumper['label'] ?? '' ) );
					return ! isset( $single_labels[ $label ] );
				}
			)
		);
	}

	private static function parse_student_jump_no( array $data ): ?int {
		$value = $data['studentJumpNo'] ?? $data['student_jump_no'] ?? $data['studentJumpNumber'] ?? null;

		if ( null === $value || '' === (string) $value ) {
			return null;
		}

		$parsed = intval( $value );
		return $parsed > 0 ? $parsed : null;
	}

	private static function parse_jumper_from_group_no( array $data ): ?string {
		$value = $data['jumperFromGroupNo'] ?? $data['jumper_from_group_no'] ?? null;

		if ( null === $value ) {
			return null;
		}

		$text = trim( (string) $value );
		if ( '' === $text || 'null' === strtolower( $text ) ) {
			return null;
		}

		return sanitize_text_field( $text );
	}

	private static function extract_jumpers( array $load, string $load_id ): array {
		if ( ! empty( $load['children'] ) && is_array( $load['children'] ) ) {
			return self::extract_jumpers_from_children( $load['children'], $load_id );
		}

		$slot_arrays = $load['slots'] ?? $load['jumpers'] ?? [];

		if ( ! is_array( $slot_arrays ) ) {
			return [];
		}

		$jumpers = [];

		foreach ( $slot_arrays as $slot ) {
			if ( ! is_array( $slot ) ) {
				continue;
			}

			$names = self::extract_names_from_mixed_value( $slot['name'] ?? $slot['jumperName'] ?? '' );

			foreach ( $names as $name_index => $name ) {
				$name = self::strip_quoted_name_parts( $name );

				if ( '' === trim( $name ) ) {
					continue;
				}

				$raw_group_id  = sanitize_text_field( $slot['groupId'] ?? $slot['group_id'] ?? '' );
				$group_id      = '' !== $raw_group_id ? self::build_scoped_group_id( $load_id, $raw_group_id ) : null;
				$group_title  = sanitize_text_field( $slot['groupTitle'] ?? $slot['group_title'] ?? '' ) ?: null;
				$jumper_from_group_no = self::parse_jumper_from_group_no( $slot );

				$jumpers[] = [
					'id'          => wp_generate_uuid4(),
					'bookingId'   => sanitize_text_field( (string) ( $slot['id'] ?? $slot['loadJumpRequestId'] ?? '' ) ),
					'label'       => sanitize_text_field( $name ),
					'internalNo'  => sanitize_text_field( (string) ( $slot['internalNo'] ?? '' ) ),
					'jump_type_name' => sanitize_text_field( (string) ( $slot['jumpTypeName'] ?? $slot['jumptypeName'] ?? $slot['jump_type_name'] ?? '' ) ),
					'jump_type'   => sanitize_text_field( (string) ( $slot['jumpType'] ?? $slot['activity'] ?? $slot['type'] ?? '' ) ),
					'altitude'    => sanitize_text_field( (string) ( $slot['altitude'] ?? $slot['alt'] ?? $slot['jumpAlt'] ?? $slot['exitAlt'] ?? '' ) ),
					'altitudeUnit' => sanitize_text_field( (string) ( $slot['altitudeUnit'] ?? $slot['altitude_unit'] ?? '' ) ),
					'student_jump_no' => self::parse_student_jump_no( $slot ),
					'jumper_from_group_no' => $jumper_from_group_no,
					'captain'     => ! empty( $slot['captain'] ),
					'jumptype_group' => sanitize_text_field( (string) ( $slot['jumptypeGroup'] ?? $slot['jumptype_group'] ?? '' ) ),
					'group_id'    => $group_id,
					'group_title' => $group_title,
				];
			}
		}

		return $jumpers;
	}

	private static function extract_jumpers_from_children( array $children, string $load_id ): array {
		$jumpers = [];

		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$child_type = strtoupper( (string) ( $child['childType'] ?? '' ) );

			if ( 'GROUP' === $child_type ) {
				$raw_group_id = sanitize_text_field( (string) ( $child['groupNo'] ?? $child['id'] ?? '' ) );
				$group_id     = '' !== $raw_group_id ? self::build_scoped_group_id( $load_id, $raw_group_id ) : '';
				$group_title = sanitize_text_field( self::strip_quoted_name_parts( (string) ( $child['groupName'] ?? $child['text'] ?? '' ) ) );
				$group_jumptype_group = sanitize_text_field( (string) ( $child['jumptypeGroup'] ?? $child['jumptype_group'] ?? '' ) );

				$members = $child['children'] ?? [];
				if ( ! is_array( $members ) ) {
					continue;
				}

				// Empty group with a name → show as a standalone placeholder row.
				if ( empty( $members ) && '' !== $group_title ) {
					$sky_text = isset( $child['skyText'] ) ? trim( (string) $child['skyText'] ) : '';
					$display  = '' !== $sky_text ? sanitize_text_field( $sky_text ) : $group_title;
					$jumpers[] = [
						'id'                   => wp_generate_uuid4(),
						'bookingId'            => sanitize_text_field( (string) ( $child['id'] ?? '' ) ),
						'label'                => $display,
						'internalNo'           => '',
						'jump_type_name'       => '',
						'jump_type'            => '',
						'altitude'             => '',
						'altitudeUnit'         => '',
						'student_jump_no'      => null,
						'jumper_from_group_no' => null,
						'captain'              => false,
						'jumptype_group'       => $group_jumptype_group,
						'group_id'             => null,
						'group_title'          => null,
					];
					continue;
				}

				foreach ( $members as $jump ) {
					if ( ! is_array( $jump ) ) {
						continue;
					}

					$jump_type = strtoupper( (string) ( $jump['childType'] ?? '' ) );
					if ( '' !== $jump_type && 'JUMP' !== $jump_type ) {
						continue;
					}

					$name = self::extract_jump_name( $jump );
					if ( '' === $name ) {
						continue;
					}

					$jumpers[] = [
						'id'          => wp_generate_uuid4(),
						'bookingId'   => sanitize_text_field( (string) ( $jump['id'] ?? $jump['loadJumpRequestId'] ?? '' ) ),
						'label'       => sanitize_text_field( $name ),
						'internalNo'  => sanitize_text_field( (string) ( $jump['internalNo'] ?? '' ) ),
						'jump_type_name' => sanitize_text_field( (string) ( $jump['jumpTypeName'] ?? $jump['jumptypeName'] ?? $jump['jump_type_name'] ?? '' ) ),
						'jump_type'   => sanitize_text_field( (string) ( $jump['jumpType'] ?? $jump['activity'] ?? $jump['type'] ?? '' ) ),
						'altitude'    => sanitize_text_field( (string) ( $jump['altitude'] ?? $jump['alt'] ?? $jump['jumpAlt'] ?? $jump['exitAlt'] ?? '' ) ),
						'altitudeUnit' => sanitize_text_field( (string) ( $jump['altitudeUnit'] ?? $jump['altitude_unit'] ?? '' ) ),
						'student_jump_no' => self::parse_student_jump_no( $jump ),
						'jumper_from_group_no' => self::parse_jumper_from_group_no( $jump ),
						'captain'     => ! empty( $jump['captain'] ),
						'jumptype_group' => $group_jumptype_group,
						'group_id'    => '' !== $group_id ? $group_id : null,
						'group_title' => '' !== $group_title ? $group_title : null,
					];
				}

				continue;
			}

			if ( 'JUMP' === $child_type ) {
				$name = self::extract_jump_name( $child );
				if ( '' === $name ) {
					continue;
				}

				$raw_gno   = sanitize_text_field( (string) ( $child['groupNo'] ?? '' ) );
				$jump_gid  = ( '' !== $raw_gno && '0' !== $raw_gno ) ? self::build_scoped_group_id( $load_id, $raw_gno ) : null;
				$jump_gtit = '' !== $raw_gno
					? sanitize_text_field( self::strip_quoted_name_parts( (string) ( $child['groupName'] ?? $child['text'] ?? '' ) ) )
					: null;

				$jumpers[] = [
					'id'          => wp_generate_uuid4(),
					'bookingId'   => sanitize_text_field( (string) ( $child['id'] ?? $child['loadJumpRequestId'] ?? '' ) ),
					'label'       => sanitize_text_field( $name ),
					'internalNo'  => sanitize_text_field( (string) ( $child['internalNo'] ?? '' ) ),
					'jump_type_name' => sanitize_text_field( (string) ( $child['jumpTypeName'] ?? $child['jumptypeName'] ?? $child['jump_type_name'] ?? '' ) ),
					'jump_type'   => sanitize_text_field( (string) ( $child['jumpType'] ?? $child['activity'] ?? $child['type'] ?? '' ) ),
					'altitude'    => sanitize_text_field( (string) ( $child['altitude'] ?? $child['alt'] ?? $child['jumpAlt'] ?? $child['exitAlt'] ?? '' ) ),
					'altitudeUnit' => sanitize_text_field( (string) ( $child['altitudeUnit'] ?? $child['altitude_unit'] ?? '' ) ),
					'student_jump_no' => self::parse_student_jump_no( $child ),
					'jumper_from_group_no' => self::parse_jumper_from_group_no( $child ),
					'captain'     => ! empty( $child['captain'] ),
					'jumptype_group' => sanitize_text_field( (string) ( $child['jumptypeGroup'] ?? $child['jumptype_group'] ?? '' ) ),
					'group_id'    => $jump_gid,
					'group_title' => '' !== (string) $jump_gtit ? $jump_gtit : null,
				];
			}
		}

		return $jumpers;
	}

	private static function build_scoped_id( string $scope, string $raw_id ): string {
		$clean_scope = sanitize_text_field( $scope );
		$clean_id    = sanitize_text_field( $raw_id );

		if ( '' === $clean_id ) {
			$clean_id = uniqid( $clean_scope . '_', true );
		}

		return $clean_scope . '::' . $clean_id;
	}

	private static function build_fallback_load_id( array $raw, string $date ): string {
		$parts = [
			$date,
			(string) ( $raw['loadNo'] ?? $raw['number'] ?? '' ),
			(string) ( $raw['liftTime'] ?? $raw['departureTime'] ?? $raw['time'] ?? '' ),
			(string) ( $raw['lift'] ?? $raw['aircraft'] ?? $raw['skyText'] ?? $raw['planeReg'] ?? '' ),
			(string) ( $raw['chief'] ?? $raw['loadChief'] ?? '' ),
		];

		$normalized = array_map(
			static function ( $value ): string {
				return sanitize_text_field( trim( (string) $value ) );
			},
			$parts
		);

		$composite = implode( '|', $normalized );

		if ( '' === str_replace( '|', '', $composite ) ) {
			return uniqid( 'load_', true );
		}

		return 'fallback_' . md5( $composite );
	}

	private static function build_scoped_group_id( string $load_id, string $raw_group_id ): string {
		$clean_load_id  = sanitize_text_field( $load_id );
		$clean_group_id = sanitize_text_field( $raw_group_id );

		if ( '' === $clean_group_id ) {
			$clean_group_id = uniqid( 'group_', true );
		}

		return $clean_load_id . '::group::' . $clean_group_id;
	}

	private static function build_scoped_jumper_id( string $load_id, string $raw_jumper_id, int $name_index = 0 ): string {
		$clean_load_id   = sanitize_text_field( $load_id );
		$clean_jumper_id = sanitize_text_field( $raw_jumper_id );

		if ( '' === $clean_jumper_id ) {
			$clean_jumper_id = uniqid( 'j_', true );
		}

		$scoped = $clean_load_id . '::jumper::' . $clean_jumper_id;

		if ( $name_index > 0 ) {
			$scoped .= '::' . strval( $name_index );
		}

		return $scoped;
	}

	private static function extract_jump_name( array $jump ): string {
		$member_name = $jump['member']['name'] ?? '';
		$name        = is_string( $member_name ) && '' !== trim( $member_name )
			? $member_name
			: ( $jump['text'] ?? $jump['skyText'] ?? '' );

		return self::strip_quoted_name_parts( (string) $name );
	}

	// ── Crew helpers ─────────────────────────────────────────────────────────

	/**
	 * Look for a crew member with a specific child-type inside raw load data.
	 *
	 * @param array  $load      Raw load array.
	 * @param string $childtype e.g. 'PILOT' or 'JUMP_LEADER'.
	 * @return string First matching name, or empty string.
	 */
	private static function extract_crew_name_by_childtype( array $load, string $childtype ): string {
		$crew_keys = [ 'crew', 'crewMembers', 'staff' ];

		foreach ( $crew_keys as $key ) {
			$crew = $load[ $key ] ?? null;
			if ( ! is_array( $crew ) ) {
				continue;
			}

			foreach ( $crew as $member ) {
				if ( ! is_array( $member ) ) {
					continue;
				}

				$type = strtoupper( $member['childType'] ?? $member['type'] ?? '' );

				if ( $type === strtoupper( $childtype ) ) {
					$name = $member['name'] ?? $member['displayName'] ?? '';
					$name = self::strip_quoted_name_parts( (string) $name );
					if ( '' !== trim( $name ) ) {
						return $name;
					}
				}
			}
		}

		// Fallback: direct keys for simple schemas.
		$fallback_key = 'PILOT' === $childtype ? 'pilot' : 'jumpLeader';
		return sanitize_text_field( $load[ $fallback_key ] ?? '' );
	}

	// ── Name helpers ──────────────────────────────────────────────────────────

	/**
	 * Accept str or array from API and return an array of name strings.
	 *
	 * @param mixed $value
	 * @return string[]
	 */
	private static function extract_names_from_mixed_value( $value ): array {
		if ( is_string( $value ) ) {
			return [ $value ];
		}

		if ( is_array( $value ) ) {
			$names = [];
			foreach ( $value as $item ) {
				if ( is_string( $item ) ) {
					$names[] = $item;
				} elseif ( is_array( $item ) ) {
					$names[] = $item['name'] ?? $item['displayName'] ?? '';
				}
			}
			return $names;
		}

		return [];
	}

	/**
	 * Remove parenthesised substrings that appear in some APIs.
	 * Quoted name parts ("Ninja", 'Cool') are kept so the frontend
	 * can toggle their visibility.
	 *
	 * e.g. 'Bo (tandem)' → 'Bo'
	 *
	 * @param string $name
	 * @return string
	 */
	private static function strip_quoted_name_parts( string $name ): string {
		// Remove parenthesised segments.
		$name = preg_replace( '/\([^)]*\)/', '', $name );
		// Collapse extra whitespace.
		return trim( (string) preg_replace( '/\s{2,}/', ' ', $name ) );
	}

	private static function extract_first_member_name( $members ): string {
		if ( ! is_array( $members ) ) {
			return '';
		}

		foreach ( $members as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$name = $item['member']['name'] ?? $item['name'] ?? '';
			if ( is_string( $name ) && '' !== trim( $name ) ) {
				return self::strip_quoted_name_parts( $name );
			}
		}

		return '';
	}

	private static function build_default_skyview_url(): string {
		$host = trim( (string) get_option( 'skywin_hub_api_host', '' ) );
		$path = trim( (string) get_option( 'skywin_hub_api_path', '' ) );
		$port = trim( (string) get_option( 'skywin_hub_api_port', '' ) );

		if ( '' === $host ) {
			return '';
		}

		$endpoint = rtrim( $host, '/' );
		if ( '' !== $port ) {
			$endpoint .= ':' . $port;
		}

		$path = trim( $path );
		if ( '' !== $path ) {
			$endpoint .= '/' . trim( $path, '/' );
		}
		return rtrim( $endpoint, '/' ) . '/skyview';
	}

	private static function build_auth_header(): string {
		$username = (string) get_option( 'skywin_hub_api_username', '' );
		$password = (string) get_option( 'skywin_hub_api_password', '' );

		if ( '' === $username || '' === $password ) {
			return '';
		}

		if ( function_exists( 'encrypt_decrypt' ) ) {
			$password = (string) encrypt_decrypt( $password, 'd' );
		}

		if ( '' === $password ) {
			return '';
		}

		return 'Basic ' . base64_encode( $username . ':' . $password );
	}

	// ── Time helpers ─────────────────────────────────────────────────────────

	/**
	 * Minutes until a given HH:MM time on $date.
	 *
	 * @param string $time   e.g. '14:30' or '14:30:00'.
	 * @param string $date   e.g. '2025-06-01'.
	 * @return int|null      null if time cannot be parsed.
	 */
	private static function minutes_until( string $time, string $date ): ?int {
		if ( '' === $time || '' === $date ) {
			return null;
		}

		// Accept HH:MM or HH:MM:SS.
		if ( ! preg_match( '/^\d{2}:\d{2}/', $time ) ) {
			return null;
		}

		$tz      = wp_timezone();
		$now     = new DateTimeImmutable( 'now', $tz );
		$target  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', "{$date} " . substr( $time, 0, 5 ), $tz );

		if ( false === $target ) {
			return null;
		}

		$diff = $target->getTimestamp() - $now->getTimestamp();
		return (int) floor( $diff / 60 );
	}

	// ── API message extraction ────────────────────────────────────────────────

	/**
	 * Extract a human-readable message from the API response, if present.
	 *
	 * @param array $data
	 * @return string
	 */
	private static function extract_api_message( array $data ): string {
		$candidates = [ 'message', 'info', 'notice', 'skyText', 'sky_text' ];

		foreach ( $candidates as $key ) {
			$value = $data[ $key ] ?? null;
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return sanitize_text_field( $value );
			}
		}

		return '';
	}
}

// Register REST routes.
add_action( 'rest_api_init', [ 'Skywin_Hub_Shortcode_Skyview', 'register_rest_routes' ] );

endif;
