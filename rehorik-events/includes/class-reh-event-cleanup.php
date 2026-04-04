<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily cron job that marks past event dates as "past", removes their
 * WooCommerce variation and hides the parent product when no active
 * variations remain.
 *
 * A date is considered "past" when date + time_end is older than 7 days.
 */
class Reh_Event_Cleanup {

	const CRON_HOOK = 'reh_cleanup_past_event_dates';

	public static function register_hooks(): void {
		add_action( self::CRON_HOOK, [ __CLASS__, 'cleanup' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	public static function cleanup(): void {
		$cutoff = time() - ( 7 * DAY_IN_SECONDS );

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

			$changed = false;
			foreach ( $dates as &$date ) {
				if ( ( $date['status'] ?? 'active' ) !== 'active' ) {
					continue;
				}

				$end_ts = strtotime( ( $date['date'] ?? '' ) . ' ' . ( $date['time_end'] ?? '23:59' ) );
				if ( ! $end_ts || $end_ts >= $cutoff ) {
					continue;
				}

				// Mark as past.
				$date['status'] = 'past';
				$changed        = true;

				// Delete the WC variation.
				$variation_id = isset( $date['wc_variation_id'] ) ? (int) $date['wc_variation_id'] : 0;
				if ( $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation instanceof WC_Product_Variation ) {
						$variation->delete( true );
					}
					$date['wc_variation_id'] = 0;
				}
			}
			unset( $date );

			if ( $changed ) {
				update_post_meta( $event_id, '_reh_event_dates', $dates );

				// If the product has no more active variations, hide it.
				$product_id = get_post_meta( $event_id, '_wc_product_id', true );
				if ( $product_id ) {
					$product = wc_get_product( (int) $product_id );
					if ( $product instanceof WC_Product_Variable ) {
						$active_variation_ids = $product->get_children();
						$active_count         = 0;
						foreach ( $active_variation_ids as $vid ) {
							$v = wc_get_product( $vid );
							if ( $v && 'publish' === $v->get_status() ) {
								++$active_count;
							}
						}
						if ( 0 === $active_count ) {
							$product->set_catalog_visibility( 'hidden' );
							$product->save();
						}
					}
				}
			}
		}
	}
}
