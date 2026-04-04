<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides:
 *  - REST endpoint GET /reh/v1/attendees/{event_date_id}
 *  - Admin sub-page "Teilnehmerlisten" under the Events menu
 */
class Reh_Attendee_List {

	public static function register_hooks(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		add_action( 'admin_menu', [ __CLASS__, 'register_admin_menu' ] );
	}

	// -------------------------------------------------------------------------
	// REST routes
	// -------------------------------------------------------------------------

	public static function register_rest_routes(): void {
		register_rest_route(
			'reh/v1',
			'/attendees/(?P<event_date_id>[a-zA-Z0-9_-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get_attendees' ],
				'permission_callback' => [ __CLASS__, 'check_permission' ],
				'args'                => [
					'event_date_id' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	public static function check_permission(): bool {
		return current_user_can( REH_PERMISSION_ATTENDEE_LIST );
	}

	public static function get_attendees( WP_REST_Request $request ): WP_REST_Response {
		$event_date_id = $request->get_param( 'event_date_id' );

		$variation_id = self::find_variation_id_by_date_id( $event_date_id );
		if ( ! $variation_id ) {
			return new WP_REST_Response( [ 'error' => 'Event-Termin nicht gefunden.' ], 404 );
		}

		$attendees = self::get_attendees_for_variation( $variation_id );

		return new WP_REST_Response( $attendees, 200 );
	}

	// -------------------------------------------------------------------------
	// Admin page
	// -------------------------------------------------------------------------

	public static function register_admin_menu(): void {
		add_submenu_page(
			'edit.php?post_type=reh_event',
			__( 'Teilnehmerlisten', 'rehorik-events' ),
			__( 'Teilnehmerlisten', 'rehorik-events' ),
			REH_PERMISSION_ATTENDEE_LIST,
			'reh-attendee-list',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function render_admin_page(): void {
		if ( ! current_user_can( REH_PERMISSION_ATTENDEE_LIST ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'rehorik-events' ) );
		}
		require_once REH_EVENTS_PLUGIN_DIR . 'templates/admin-attendee-list.php';
	}

	// -------------------------------------------------------------------------
	// Data helpers (public so the template can call them)
	// -------------------------------------------------------------------------

	public static function get_upcoming_events_with_dates(): array {
		$events = get_posts( [
			'post_type'      => 'reh_event',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		] );

		$result = [];
		$now    = time();

		foreach ( $events as $event_id ) {
			$dates = get_post_meta( $event_id, '_reh_event_dates', true );
			if ( ! is_array( $dates ) ) {
				continue;
			}

			$upcoming = [];
			foreach ( $dates as $date ) {
				if ( ( $date['status'] ?? 'active' ) !== 'active' ) {
					continue;
				}
				$start_ts = strtotime( ( $date['date'] ?? '' ) . ' ' . ( $date['time_start'] ?? '00:00' ) );
				if ( $start_ts && $start_ts >= $now ) {
					$upcoming[] = $date;
				}
			}

			if ( ! empty( $upcoming ) ) {
				$result[] = [
					'event_id'    => $event_id,
					'event_title' => get_the_title( $event_id ),
					'dates'       => $upcoming,
				];
			}
		}

		usort( $result, fn( $a, $b ) => strcmp( $a['event_title'], $b['event_title'] ) );

		return $result;
	}

	public static function find_variation_id_by_date_id( string $date_id ): int {
		$events = get_posts( [
			'post_type'      => 'reh_event',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		foreach ( $events as $event_id ) {
			$dates = get_post_meta( $event_id, '_reh_event_dates', true );
			if ( ! is_array( $dates ) ) {
				continue;
			}
			foreach ( $dates as $date ) {
				if ( ( $date['id'] ?? '' ) === $date_id ) {
					return (int) ( $date['wc_variation_id'] ?? 0 );
				}
			}
		}

		return 0;
	}

	public static function get_attendees_for_variation( int $variation_id ): array {
		$attendees = [];

		$orders = wc_get_orders( [
			'limit'  => -1,
			'status' => [ 'completed', 'processing' ],
		] );

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				/** @var WC_Order_Item_Product $item */
				if ( (int) $item->get_variation_id() !== $variation_id ) {
					continue;
				}

				$qty  = (int) $item->get_quantity();
				$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

				for ( $i = 0; $i < $qty; $i++ ) {
					$meta_key   = '_checkin_' . $variation_id . '_' . $i;
					$checked_in_at = get_post_meta( $order->get_id(), $meta_key, true );

					$attendees[] = [
						'order_id'      => $order->get_id(),
						'variation_id'  => $variation_id,
						'index'         => $i,
						'name'          => $name,
						'email'         => $order->get_billing_email(),
						'phone'         => $order->get_billing_phone(),
						'status'        => $order->get_status(),
						'checked_in'    => ! empty( $checked_in_at ),
						'checked_in_at' => $checked_in_at ?: null,
					];
				}
			}
		}

		return $attendees;
	}
}
