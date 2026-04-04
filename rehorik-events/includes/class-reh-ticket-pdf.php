<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Dompdf\Dompdf;

/**
 * Generates a ticket PDF for each event line item in a completed order
 * and attaches it to the customer_completed_order e-mail.
 */
class Reh_Ticket_Pdf {

	const DIR_NAME = 'reh-event-tickets';

	public static function register_hooks(): void {
		add_filter( 'woocommerce_email_attachments', [ __CLASS__, 'attach_ticket_pdf' ], 10, 3 );
	}

	// -------------------------------------------------------------------------
	// E-mail hook
	// -------------------------------------------------------------------------

	public static function attach_ticket_pdf( array $attachments, string $email_id, WC_Order $order ): array {
		if ( 'customer_completed_order' !== $email_id ) {
			return $attachments;
		}

		foreach ( $order->get_items() as $item ) {
			/** @var WC_Order_Item_Product $item */
			$variation_id = (int) $item->get_variation_id();
			if ( ! $variation_id ) {
				continue;
			}

			$event_id      = (int) get_post_meta( $variation_id, '_reh_event_id', true );
			$event_date_id = get_post_meta( $variation_id, '_reh_event_date_id', true );

			if ( ! $event_id ) {
				continue;
			}

			$pdf_path = self::generate_pdf( $order, $item, $event_id, $event_date_id, $variation_id );
			if ( $pdf_path ) {
				$attachments[] = $pdf_path;
			}
		}

		return $attachments;
	}

	// -------------------------------------------------------------------------
	// PDF generation
	// -------------------------------------------------------------------------

	private static function generate_pdf(
		WC_Order $order,
		WC_Order_Item_Product $item,
		int $event_id,
		string $event_date_id,
		int $variation_id
	): ?string {
		$event_dates = get_post_meta( $event_id, '_reh_event_dates', true );
		$date_data   = [];

		if ( is_array( $event_dates ) ) {
			foreach ( $event_dates as $d ) {
				if ( ( $d['id'] ?? '' ) === $event_date_id ) {
					$date_data = $d;
					break;
				}
			}
		}

		$organizer = get_post_meta( $event_id, '_reh_organizer', true ) ?: 'Rehorik';
		$venue     = get_post_meta( $event_id, '_reh_venue', true );
		$address   = get_post_meta( $event_id, '_reh_address', true );

		$start_ts    = (int) get_post_meta( $variation_id, REH_EVENT_DATE_START_META, true );
		$date_string = $start_ts ? date_i18n( 'd.m.Y, H:i', $start_ts ) : '';

		$ticket_number = $order->get_id() . '-' . $variation_id;

		$template_data = [
			'ticket_name'   => get_the_title( $event_id ),
			'ticket_id'     => $ticket_number,
			'organizer'     => $organizer,
			'date'          => $date_string,
			'location'      => trim( $venue . ( $address ? ', ' . $address : '' ) ),
			'holder_name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'assets_dir'    => REH_EVENTS_PLUGIN_DIR . 'assets',
			'assets_url'    => REH_EVENTS_PLUGIN_URL . 'assets',
		];

		try {
			return self::render_pdf( $ticket_number, $template_data );
		} catch ( Exception $e ) {
			error_log( '[Reh_Ticket_Pdf] ' . $e->getMessage() );
			return null;
		}
	}

	private static function render_pdf( string $filename_base, array $template_data ): ?string {
		if ( ! class_exists( '\\Dompdf\\Dompdf' ) ) {
			error_log( '[Reh_Ticket_Pdf] dompdf not available. Run composer install in the plugin directory.' );
			return null;
		}

		// Use local file paths only – no remote resources needed.
		$dompdf = new Dompdf( [
			'enable_remote' => false,
			'dpi'           => 300,
		] );

		// Register custom fonts.
		// Place cond.ttf and cond-bold.ttf in assets/fonts/ of the plugin.
		$fonts_dir = REH_EVENTS_PLUGIN_DIR . 'assets/fonts/';
		if ( is_dir( $fonts_dir ) ) {
			$font_metrics = $dompdf->getFontMetrics();
			$cond         = $fonts_dir . 'cond.ttf';
			$cond_bold    = $fonts_dir . 'cond-bold.ttf';
			if ( file_exists( $cond ) ) {
				$font_metrics->registerFont(
					[ 'family' => 'Cond', 'style' => 'normal', 'weight' => 'normal' ],
					$cond
				);
			}
			if ( file_exists( $cond_bold ) ) {
				$font_metrics->registerFont(
					[ 'family' => 'Cond Bold', 'style' => 'normal', 'weight' => 'bold' ],
					$cond_bold
				);
			}
		}

		ob_start();
		$reh_tpl = $template_data;
		include REH_EVENTS_PLUGIN_DIR . 'templates/pdf/ticket-pdf.php';
		$html = ob_get_clean();

		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'A4' );
		$dompdf->render();

		$file_path = self::get_file_path() . sanitize_file_name( $filename_base ) . '.pdf';
		if ( file_put_contents( $file_path, $dompdf->output() ) ) {
			return $file_path;
		}

		return null;
	}

	private static function get_file_path(): string {
		$upload_dir = wp_upload_dir();
		$path       = trailingslashit( $upload_dir['basedir'] ) . self::DIR_NAME . '/';

		if ( ! is_dir( $path ) ) {
			wp_mkdir_p( $path );
		}

		return $path;
	}
}
