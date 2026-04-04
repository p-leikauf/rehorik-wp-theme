<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoint: POST /reh/v1/checkin
 *
 * Body: { order_id, variation_id, index }
 * Saves _checkin_{variation_id}_{index} = current datetime as Order meta.
 */
class Reh_Checkin_Api {

	public static function register_hooks(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function register_rest_routes(): void {
		register_rest_route(
			'reh/v1',
			'/checkin',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'handle_checkin' ],
				'permission_callback' => [ __CLASS__, 'check_permission' ],
				'args'                => [
					'order_id'     => [
						'required'          => true,
						'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
						'sanitize_callback' => 'absint',
					],
					'variation_id' => [
						'required'          => true,
						'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
						'sanitize_callback' => 'absint',
					],
					'index'        => [
						'required'          => true,
						'validate_callback' => fn( $v ) => is_numeric( $v ) && $v >= 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	public static function check_permission(): bool {
		return current_user_can( REH_PERMISSION_ATTENDEE_LIST );
	}

	public static function handle_checkin( WP_REST_Request $request ): WP_REST_Response {
		$order_id     = $request->get_param( 'order_id' );
		$variation_id = $request->get_param( 'variation_id' );
		$index        = $request->get_param( 'index' );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Bestellung nicht gefunden.' ], 404 );
		}

		// Verify the variation belongs to this order.
		$found = false;
		foreach ( $order->get_items() as $item ) {
			/** @var WC_Order_Item_Product $item */
			if ( (int) $item->get_variation_id() === $variation_id ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return new WP_REST_Response( [ 'error' => 'Variation gehört nicht zu dieser Bestellung.' ], 400 );
		}

		$meta_key      = '_checkin_' . $variation_id . '_' . $index;
		$checked_in_at = current_time( 'mysql' );

		update_post_meta( $order_id, $meta_key, $checked_in_at );

		return new WP_REST_Response(
			[
				'success'       => true,
				'checked_in_at' => $checked_in_at,
			],
			200
		);
	}
}
