<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin orchestrator – registers all hooks via the individual classes.
 */
class Reh_Events_Plugin {

	private static ?Reh_Events_Plugin $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		Reh_Event_Post_Type::register_hooks();
		Reh_Event_Admin::register_hooks();
		Reh_Event_Wc_Sync::register_hooks();
		Reh_Event_Cleanup::register_hooks();
		Reh_Attendee_List::register_hooks();
		Reh_Checkin_Api::register_hooks();
		Reh_Ticket_Pdf::register_hooks();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	public function enqueue_admin_assets( string $hook ): void {
		$screen = get_current_screen();

		$is_event_screen    = $screen && ( 'reh_event' === $screen->post_type );
		$is_attendee_screen = ( false !== strpos( $hook, 'reh-attendee-list' ) );

		if ( ! $is_event_screen && ! $is_attendee_screen ) {
			return;
		}

		wp_enqueue_style(
			'reh-events-admin',
			REH_EVENTS_PLUGIN_URL . 'assets/css/admin.css',
			[],
			REH_EVENTS_VERSION
		);

		wp_enqueue_script(
			'reh-events-admin',
			REH_EVENTS_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			REH_EVENTS_VERSION,
			true
		);

		wp_localize_script(
			'reh-events-admin',
			'rehEvents',
			[
				'nonce'   => wp_create_nonce( 'reh_events_admin' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => rest_url( 'reh/v1/' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}
}
