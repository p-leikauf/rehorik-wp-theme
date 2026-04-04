<?php
/**
 * Plugin Name: Rehorik Events
 * Description: Event- und Ticket-Verwaltung für Rehorik auf WooCommerce-Basis
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 * Text Domain: rehorik-events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REH_EVENTS_VERSION', '1.0.0' );
define( 'REH_EVENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'REH_EVENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

const REH_TICKET_CATEGORY_SLUG     = 'veranstaltungen';
const REH_EVENT_DATE_START_META    = '_event_timestamp_start';
const REH_EVENT_DATE_END_META      = '_event_timestamp_end';
const REH_PERMISSION_ATTENDEE_LIST = 'teilnehmerliste_bei_events_anschauen';

// Autoload via Composer (dompdf etc.)
if ( file_exists( REH_EVENTS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once REH_EVENTS_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-events-plugin.php';
require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-event-post-type.php';
require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-event-admin.php';
require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-event-wc-sync.php';
require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-event-cleanup.php';
require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-attendee-list.php';
require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-checkin-api.php';
require_once REH_EVENTS_PLUGIN_DIR . 'includes/class-reh-ticket-pdf.php';

register_activation_hook( __FILE__, 'reh_events_activate' );
register_deactivation_hook( __FILE__, 'reh_events_deactivate' );

function reh_events_activate(): void {
	if ( ! reh_events_woocommerce_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Rehorik Events benötigt WooCommerce. Bitte WooCommerce zuerst installieren und aktivieren.', 'rehorik-events' ),
			esc_html__( 'Plugin-Aktivierung fehlgeschlagen', 'rehorik-events' ),
			[ 'back_link' => true ]
		);
	}

	// Register post types so rewrite rules are flushed correctly.
	Reh_Event_Post_Type::register();
	flush_rewrite_rules();

	// Schedule daily cron job.
	if ( ! wp_next_scheduled( Reh_Event_Cleanup::CRON_HOOK ) ) {
		wp_schedule_event( time(), 'daily', Reh_Event_Cleanup::CRON_HOOK );
	}
}

function reh_events_deactivate(): void {
	$timestamp = wp_next_scheduled( Reh_Event_Cleanup::CRON_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, Reh_Event_Cleanup::CRON_HOOK );
	}

	flush_rewrite_rules();
}

function reh_events_woocommerce_active(): bool {
	return in_array(
		'woocommerce/woocommerce.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) ),
		true
	);
}

add_action( 'admin_notices', 'reh_events_woocommerce_notice' );
function reh_events_woocommerce_notice(): void {
	if ( reh_events_woocommerce_active() ) {
		return;
	}
	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Rehorik Events benötigt WooCommerce. Bitte WooCommerce installieren und aktivieren.', 'rehorik-events' )
		. '</p></div>';
}

// Bootstrap the plugin only when WooCommerce is active.
add_action( 'plugins_loaded', 'reh_events_init' );
function reh_events_init(): void {
	if ( ! reh_events_woocommerce_active() ) {
		return;
	}
	Reh_Events_Plugin::get_instance()->init();
}
