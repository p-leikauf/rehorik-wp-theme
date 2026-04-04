<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synchronises an reh_event post with a WooCommerce Variable Product.
 *
 * One Variable Product per event, one Variation per active date.
 * Dates are stored as a serialised array in _reh_event_dates.
 */
class Reh_Event_Wc_Sync {

	public static function register_hooks(): void {
		// Sync is triggered by Reh_Event_Admin::save_event_dates after the
		// meta has been written, so no additional hook needed here.
		// We also hook into the REST API save path.
		add_action( 'rest_after_insert_reh_event', [ __CLASS__, 'sync_event_from_rest' ], 10, 1 );
	}

	public static function sync_event_from_rest( WP_Post $post ): void {
		self::sync_event( $post->ID );
	}

	// -------------------------------------------------------------------------
	// Main sync entry point
	// -------------------------------------------------------------------------

	public static function sync_event( int $event_id ): void {
		$post = get_post( $event_id );
		if ( ! $post || 'reh_event' !== $post->post_type ) {
			return;
		}

		$dates = get_post_meta( $event_id, '_reh_event_dates', true );
		if ( ! is_array( $dates ) ) {
			$dates = [];
		}

		$active_dates = array_filter( $dates, fn( $d ) => ( $d['status'] ?? 'active' ) === 'active' );

		$product = self::get_or_create_variable_product( $event_id, $post );
		if ( ! $product ) {
			return;
		}

		// Ensure the pa_termin attribute exists.
		self::ensure_pa_termin_attribute();

		// Sync each active date to a variation.
		$synced_variation_ids = [];
		foreach ( $active_dates as &$date ) {
			$variation_id = self::sync_variation( $product, $event_id, $date );
			if ( $variation_id ) {
				$date['wc_variation_id'] = $variation_id;
				$synced_variation_ids[]  = $variation_id;
			}
		}
		unset( $date );

		// Write back updated dates (with wc_variation_id).
		update_post_meta( $event_id, '_reh_event_dates', $dates );

		// Sync product attributes so WooCommerce knows all variation values.
		self::sync_product_attributes( $product, $active_dates );
		$product->save();

		// Hide product if no active variations.
		if ( empty( $synced_variation_ids ) ) {
			$product->set_catalog_visibility( 'hidden' );
			$product->save();
		} else {
			if ( 'hidden' === $product->get_catalog_visibility() ) {
				$product->set_catalog_visibility( 'visible' );
				$product->save();
			}
		}
	}

	// -------------------------------------------------------------------------
	// Variable Product
	// -------------------------------------------------------------------------

	private static function get_or_create_variable_product( int $event_id, WP_Post $post ): ?WC_Product_Variable {
		$product_id = get_post_meta( $event_id, '_wc_product_id', true );
		$product    = null;

		if ( $product_id ) {
			$product = wc_get_product( (int) $product_id );
			if ( ! ( $product instanceof WC_Product_Variable ) ) {
				$product = null;
			}
		}

		if ( ! $product ) {
			$product = new WC_Product_Variable();
		}

		$product->set_name( $post->post_title );
		$product->set_status( 'publish' );
		$product->set_virtual( true );

		$thumbnail_id = get_post_thumbnail_id( $event_id );
		if ( $thumbnail_id ) {
			$product->set_image_id( $thumbnail_id );
		}

		$cat = get_term_by( 'slug', REH_TICKET_CATEGORY_SLUG, 'product_cat' );
		if ( $cat ) {
			$product->set_category_ids( [ $cat->term_id ] );
		}

		$product->save();

		update_post_meta( $event_id, '_wc_product_id', $product->get_id() );

		return $product;
	}

	// -------------------------------------------------------------------------
	// Product Attribute
	// -------------------------------------------------------------------------

	private static function ensure_pa_termin_attribute(): void {
		if ( ! taxonomy_exists( 'pa_termin' ) ) {
			wc_create_attribute( [
				'name'         => 'Termin',
				'slug'         => 'termin',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			] );
		}
	}

	private static function sync_product_attributes( WC_Product_Variable $product, array $active_dates ): void {
		$values = [];
		foreach ( $active_dates as $date ) {
			$values[] = self::format_date_label( $date );
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( 'pa_termin' ) );
		$attribute->set_name( 'pa_termin' );
		$attribute->set_options( $values );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product->set_attributes( [ 'pa_termin' => $attribute ] );
	}

	// -------------------------------------------------------------------------
	// Variation
	// -------------------------------------------------------------------------

	private static function sync_variation( WC_Product_Variable $product, int $event_id, array &$date ): int {
		$variation_id = isset( $date['wc_variation_id'] ) ? (int) $date['wc_variation_id'] : 0;
		$variation    = null;

		if ( $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! ( $variation instanceof WC_Product_Variation ) ) {
				$variation = null;
			}
		}

		if ( ! $variation ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
		}

		$label = self::format_date_label( $date );

		$variation->set_status( 'publish' );
		$variation->set_virtual( true );
		$variation->set_attributes( [ 'pa_termin' => $label ] );

		// Price: date-level override or event default.
		$price = $date['price'] ?? '';
		if ( '' === $price ) {
			$price = get_post_meta( $event_id, '_reh_price', true );
		}
		if ( '' !== $price ) {
			$variation->set_regular_price( wc_format_decimal( $price ) );
		}

		// Stock.
		$capacity = $date['capacity'] ?? '';
		if ( '' === $capacity || 0 === $capacity ) {
			$capacity = get_post_meta( $event_id, '_reh_max_capacity', true );
		}
		$variation->set_manage_stock( true );
		if ( '' !== $capacity ) {
			$variation->set_stock_quantity( (int) $capacity );
		}

		$variation->save();

		// Store meta for PDF + attendee list.
		$start_ts = self::build_start_timestamp( $date );
		$end_ts   = self::build_end_timestamp( $date );
		update_post_meta( $variation->get_id(), REH_EVENT_DATE_START_META, $start_ts );
		update_post_meta( $variation->get_id(), REH_EVENT_DATE_END_META, $end_ts );
		update_post_meta( $variation->get_id(), '_reh_event_id', $event_id );
		update_post_meta( $variation->get_id(), '_reh_event_date_id', $date['id'] );

		return $variation->get_id();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	public static function format_date_label( array $date ): string {
		$d = isset( $date['date'] ) ? date_i18n( 'd.m.Y', strtotime( $date['date'] ) ) : '';
		$t = $date['time_start'] ?? '';
		return trim( $d . ( $t ? ', ' . $t : '' ) );
	}

	private static function build_start_timestamp( array $date ): int {
		return strtotime( ( $date['date'] ?? '' ) . ' ' . ( $date['time_start'] ?? '00:00' ) ) ?: 0;
	}

	private static function build_end_timestamp( array $date ): int {
		return strtotime( ( $date['date'] ?? '' ) . ' ' . ( $date['time_end'] ?? '00:00' ) ) ?: 0;
	}
}
