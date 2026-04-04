<?php
/**
 * Admin page template: Teilnehmerlisten
 *
 * @package rehorik-events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$events_with_dates = Reh_Attendee_List::get_upcoming_events_with_dates();
$selected_date_id  = isset( $_GET['date_id'] ) ? sanitize_text_field( wp_unslash( $_GET['date_id'] ) ) : '';
$attendees         = [];

if ( $selected_date_id ) {
	$variation_id = Reh_Attendee_List::find_variation_id_by_date_id( $selected_date_id );
	if ( $variation_id ) {
		$attendees = Reh_Attendee_List::get_attendees_for_variation( $variation_id );
	}
}
?>
<div class="wrap reh-attendee-list-wrap">
	<h1><?php esc_html_e( 'Teilnehmerlisten', 'rehorik-events' ); ?></h1>

	<!-- Event / Date selector -->
	<form method="get" action="" class="reh-attendee-filter">
		<input type="hidden" name="post_type" value="reh_event" />
		<input type="hidden" name="page" value="reh-attendee-list" />

		<label for="reh-date-select"><strong><?php esc_html_e( 'Termin auswählen:', 'rehorik-events' ); ?></strong></label>
		<select id="reh-date-select" name="date_id">
			<option value=""><?php esc_html_e( '— Termin auswählen —', 'rehorik-events' ); ?></option>
			<?php foreach ( $events_with_dates as $event_group ) : ?>
				<optgroup label="<?php echo esc_attr( $event_group['event_title'] ); ?>">
					<?php foreach ( $event_group['dates'] as $date ) : ?>
						<?php
						$label = sprintf(
							'%s, %s – %s',
							date_i18n( 'd.m.Y', strtotime( $date['date'] ) ),
							$date['time_start'] ?? '',
							$date['time_end'] ?? ''
						);
						?>
						<option value="<?php echo esc_attr( $date['id'] ); ?>" <?php selected( $selected_date_id, $date['id'] ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>

		<?php submit_button( __( 'Anzeigen', 'rehorik-events' ), 'primary', 'submit', false ); ?>
	</form>

	<?php if ( $selected_date_id ) : ?>
		<hr />

		<?php if ( empty( $attendees ) ) : ?>
			<p><?php esc_html_e( 'Keine Teilnehmer für diesen Termin gefunden.', 'rehorik-events' ); ?></p>
		<?php else : ?>
			<h2>
				<?php
				printf(
					/* translators: %d: number of attendees */
					esc_html__( 'Teilnehmer (%d)', 'rehorik-events' ),
					count( $attendees )
				);
				?>
			</h2>
			<table class="wp-list-table widefat fixed striped reh-attendee-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'E-Mail', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Telefon', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Bestellung', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Status', 'rehorik-events' ); ?></th>
						<th><?php esc_html_e( 'Check-in', 'rehorik-events' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $attendees as $attendee ) : ?>
						<tr class="reh-attendee-row <?php echo $attendee['checked_in'] ? 'checked-in' : ''; ?>"
							data-order-id="<?php echo esc_attr( $attendee['order_id'] ); ?>"
							data-variation-id="<?php echo esc_attr( $attendee['variation_id'] ); ?>"
							data-index="<?php echo esc_attr( $attendee['index'] ); ?>">
							<td><?php echo esc_html( $attendee['name'] ); ?></td>
							<td><?php echo esc_html( $attendee['email'] ); ?></td>
							<td><?php echo esc_html( $attendee['phone'] ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $attendee['order_id'] . '&action=edit' ) ); ?>" target="_blank">
									#<?php echo esc_html( $attendee['order_id'] ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $attendee['status'] ); ?></td>
							<td>
								<?php if ( $attendee['checked_in'] ) : ?>
									<span class="reh-checked-in-label">
										✓ <?php echo esc_html( $attendee['checked_in_at'] ); ?>
									</span>
								<?php else : ?>
									<button type="button"
										class="button button-primary reh-checkin-btn"
										data-order-id="<?php echo esc_attr( $attendee['order_id'] ); ?>"
										data-variation-id="<?php echo esc_attr( $attendee['variation_id'] ); ?>"
										data-index="<?php echo esc_attr( $attendee['index'] ); ?>">
										<?php esc_html_e( 'Einchecken', 'rehorik-events' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	<?php endif; ?>
</div>
