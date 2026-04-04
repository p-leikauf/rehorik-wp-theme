<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers meta-boxes for Event details and Event dates on the reh_event post type.
 * Handles saving of all event meta and the dates serialised array.
 */
class Reh_Event_Admin {

	public static function register_hooks(): void {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
		add_action( 'save_post_reh_event', [ __CLASS__, 'save_event_details' ], 10, 2 );
		add_action( 'save_post_reh_event', [ __CLASS__, 'save_event_dates' ], 10, 2 );
	}

	// -------------------------------------------------------------------------
	// Meta-box registration
	// -------------------------------------------------------------------------

	public static function add_meta_boxes(): void {
		add_meta_box(
			'reh_event_details',
			__( 'Event-Details', 'rehorik-events' ),
			[ __CLASS__, 'render_details_meta_box' ],
			'reh_event',
			'normal',
			'high'
		);

		add_meta_box(
			'reh_event_dates',
			__( 'Termine', 'rehorik-events' ),
			[ __CLASS__, 'render_dates_meta_box' ],
			'reh_event',
			'normal',
			'default'
		);
	}

	// -------------------------------------------------------------------------
	// Render: Event-Details
	// -------------------------------------------------------------------------

	public static function render_details_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'reh_event_details_nonce', 'reh_event_details_nonce_field' );

		$venue       = get_post_meta( $post->ID, '_reh_venue', true );
		$address     = get_post_meta( $post->ID, '_reh_address', true );
		$organizer   = get_post_meta( $post->ID, '_reh_organizer', true );
		$duration    = get_post_meta( $post->ID, '_reh_duration', true );
		$capacity    = get_post_meta( $post->ID, '_reh_max_capacity', true );
		$price       = get_post_meta( $post->ID, '_reh_price', true );
		$is_online   = get_post_meta( $post->ID, '_reh_is_online', true );

		if ( '' === $organizer ) {
			$organizer = 'Rehorik';
		}
		?>
		<table class="form-table reh-event-details-table">
			<tr>
				<th><label for="reh_venue"><?php esc_html_e( 'Ort', 'rehorik-events' ); ?></label></th>
				<td><input type="text" id="reh_venue" name="reh_venue" value="<?php echo esc_attr( $venue ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="reh_address"><?php esc_html_e( 'Adresse', 'rehorik-events' ); ?></label></th>
				<td><input type="text" id="reh_address" name="reh_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="reh_organizer"><?php esc_html_e( 'Veranstalter', 'rehorik-events' ); ?></label></th>
				<td><input type="text" id="reh_organizer" name="reh_organizer" value="<?php echo esc_attr( $organizer ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="reh_duration"><?php esc_html_e( 'Dauer (Minuten)', 'rehorik-events' ); ?></label></th>
				<td><input type="number" id="reh_duration" name="reh_duration" value="<?php echo esc_attr( $duration ); ?>" min="0" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="reh_max_capacity"><?php esc_html_e( 'Max. Teilnehmer', 'rehorik-events' ); ?></label></th>
				<td><input type="number" id="reh_max_capacity" name="reh_max_capacity" value="<?php echo esc_attr( $capacity ); ?>" min="0" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="reh_price"><?php esc_html_e( 'Preis in €', 'rehorik-events' ); ?></label></th>
				<td><input type="text" id="reh_price" name="reh_price" value="<?php echo esc_attr( $price ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Online-Event', 'rehorik-events' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="reh_is_online" value="1" <?php checked( $is_online, '1' ); ?> />
						<?php esc_html_e( 'Dieses Event findet online statt', 'rehorik-events' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	// -------------------------------------------------------------------------
	// Render: Termine
	// -------------------------------------------------------------------------

	public static function render_dates_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'reh_event_dates_nonce', 'reh_event_dates_nonce_field' );

		$dates = get_post_meta( $post->ID, '_reh_event_dates', true );
		if ( ! is_array( $dates ) ) {
			$dates = [];
		}
		?>
		<div id="reh-dates-wrap">
			<table id="reh-dates-table" class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Datum', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Von', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Bis', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Plätze', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Preis', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Status', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'WC-Variation', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Aktionen', 'rehorik-events' ); ?></th>
					</tr>
				</thead>
				<tbody id="reh-dates-tbody">
				<?php if ( empty( $dates ) ) : ?>
					<tr id="reh-no-dates-row">
						<td colspan="8"><?php esc_html_e( 'Noch keine Termine angelegt.', 'rehorik-events' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $dates as $date ) : ?>
					<tr data-date-id="<?php echo esc_attr( $date['id'] ); ?>">
						<td><?php echo esc_html( isset( $date['date'] ) ? date_i18n( 'd.m.Y', strtotime( $date['date'] ) ) : '' ); ?></td>
						<td><?php echo esc_html( $date['time_start'] ?? '' ); ?></td>
						<td><?php echo esc_html( $date['time_end'] ?? '' ); ?></td>
						<td><?php echo esc_html( $date['capacity'] ?? '' ); ?></td>
						<td><?php echo esc_html( isset( $date['price'] ) && '' !== $date['price'] ? number_format_i18n( (float) $date['price'], 2 ) . ' €' : '' ); ?></td>
						<td><?php echo esc_html( $date['status'] ?? 'active' ); ?></td>
						<td><?php echo esc_html( $date['wc_variation_id'] ?? '' ); ?></td>
						<td>
							<button type="button" class="button button-small reh-delete-date" data-id="<?php echo esc_attr( $date['id'] ); ?>">
								<?php esc_html_e( 'Löschen', 'rehorik-events' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<!-- Hidden inputs carrying the current dates state -->
			<input type="hidden" id="reh_event_dates_json" name="reh_event_dates_json" value="<?php echo esc_attr( wp_json_encode( $dates ) ); ?>" />

			<h3 style="margin-top:20px;"><?php esc_html_e( 'Neuer Termin', 'rehorik-events' ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="reh_new_date"><?php esc_html_e( 'Datum', 'rehorik-events' ); ?></label></th>
					<td><input type="date" id="reh_new_date" name="reh_new_date" /></td>
				</tr>
				<tr>
					<th><label for="reh_new_time_start"><?php esc_html_e( 'Von (Uhrzeit)', 'rehorik-events' ); ?></label></th>
					<td><input type="time" id="reh_new_time_start" name="reh_new_time_start" /></td>
				</tr>
				<tr>
					<th><label for="reh_new_time_end"><?php esc_html_e( 'Bis (Uhrzeit)', 'rehorik-events' ); ?></label></th>
					<td><input type="time" id="reh_new_time_end" name="reh_new_time_end" /></td>
				</tr>
				<tr>
					<th><label for="reh_new_capacity"><?php esc_html_e( 'Plätze (optional)', 'rehorik-events' ); ?></label></th>
					<td><input type="number" id="reh_new_capacity" name="reh_new_capacity" min="0" class="small-text" /></td>
				</tr>
				<tr>
					<th><label for="reh_new_price"><?php esc_html_e( 'Preis in € (optional)', 'rehorik-events' ); ?></label></th>
					<td><input type="text" id="reh_new_price" name="reh_new_price" class="small-text" /></td>
				</tr>
			</table>
			<p>
				<button type="button" id="reh-add-date" class="button button-primary">
					<?php esc_html_e( '+ Termin hinzufügen', 'rehorik-events' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Save: Event-Details
	// -------------------------------------------------------------------------

	public static function save_event_details( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['reh_event_details_nonce_field'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['reh_event_details_nonce_field'] ), 'reh_event_details_nonce' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = [
			'reh_venue'        => '_reh_venue',
			'reh_address'      => '_reh_address',
			'reh_organizer'    => '_reh_organizer',
			'reh_duration'     => '_reh_duration',
			'reh_max_capacity' => '_reh_max_capacity',
			'reh_price'        => '_reh_price',
		];

		foreach ( $fields as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
			}
		}

		$is_online = isset( $_POST['reh_is_online'] ) ? '1' : '0';
		update_post_meta( $post_id, '_reh_is_online', $is_online );
	}

	// -------------------------------------------------------------------------
	// Save: Termine
	// -------------------------------------------------------------------------

	public static function save_event_dates( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['reh_event_dates_nonce_field'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['reh_event_dates_nonce_field'] ), 'reh_event_dates_nonce' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['reh_event_dates_json'] ) ) {
			return;
		}

		$raw   = wp_unslash( $_POST['reh_event_dates_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$dates = json_decode( $raw, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $dates ) ) {
			$dates = [];
		}

		$sanitized = [];
		foreach ( $dates as $date ) {
			if ( empty( $date['id'] ) ) {
				continue;
			}
			$sanitized[] = [
				'id'             => sanitize_text_field( $date['id'] ),
				'date'           => sanitize_text_field( $date['date'] ?? '' ),
				'time_start'     => sanitize_text_field( $date['time_start'] ?? '' ),
				'time_end'       => sanitize_text_field( $date['time_end'] ?? '' ),
				'capacity'       => isset( $date['capacity'] ) && '' !== $date['capacity'] ? absint( $date['capacity'] ) : '',
				'price'          => isset( $date['price'] ) && '' !== $date['price'] ? sanitize_text_field( $date['price'] ) : '',
				'wc_variation_id'=> isset( $date['wc_variation_id'] ) ? absint( $date['wc_variation_id'] ) : 0,
				'status'         => in_array( $date['status'] ?? 'active', [ 'active', 'past' ], true ) ? $date['status'] : 'active',
			];
		}

		update_post_meta( $post_id, '_reh_event_dates', $sanitized );

		// Trigger WC sync after dates are saved.
		Reh_Event_Wc_Sync::sync_event( $post_id );
	}
}
