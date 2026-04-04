<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `reh_event` Custom Post Type and the `reh_event_cat` taxonomy.
 */
class Reh_Event_Post_Type {

	public static function register_hooks(): void {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	public static function register(): void {
		self::register_post_type();
		self::register_taxonomy();
	}

	private static function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Events', 'post type general name', 'rehorik-events' ),
			'singular_name'      => _x( 'Event', 'post type singular name', 'rehorik-events' ),
			'add_new'            => __( 'Neu anlegen', 'rehorik-events' ),
			'add_new_item'       => __( 'Neues Event anlegen', 'rehorik-events' ),
			'edit_item'          => __( 'Event bearbeiten', 'rehorik-events' ),
			'new_item'           => __( 'Neues Event', 'rehorik-events' ),
			'view_item'          => __( 'Event ansehen', 'rehorik-events' ),
			'search_items'       => __( 'Events suchen', 'rehorik-events' ),
			'not_found'          => __( 'Keine Events gefunden', 'rehorik-events' ),
			'not_found_in_trash' => __( 'Keine Events im Papierkorb', 'rehorik-events' ),
			'all_items'          => __( 'Alle Events', 'rehorik-events' ),
			'menu_name'          => __( 'Events', 'rehorik-events' ),
		];

		register_post_type(
			'reh_event',
			[
				'labels'        => $labels,
				'public'        => true,
				'has_archive'   => true,
				'rewrite'       => [ 'slug' => 'events' ],
				'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
				'menu_icon'     => 'dashicons-calendar-alt',
				'show_in_rest'  => true,
				'menu_position' => 5,
			]
		);
	}

	private static function register_taxonomy(): void {
		$labels = [
			'name'              => _x( 'Event-Kategorien', 'taxonomy general name', 'rehorik-events' ),
			'singular_name'     => _x( 'Event-Kategorie', 'taxonomy singular name', 'rehorik-events' ),
			'search_items'      => __( 'Kategorien suchen', 'rehorik-events' ),
			'all_items'         => __( 'Alle Kategorien', 'rehorik-events' ),
			'parent_item'       => __( 'Übergeordnete Kategorie', 'rehorik-events' ),
			'parent_item_colon' => __( 'Übergeordnete Kategorie:', 'rehorik-events' ),
			'edit_item'         => __( 'Kategorie bearbeiten', 'rehorik-events' ),
			'update_item'       => __( 'Kategorie aktualisieren', 'rehorik-events' ),
			'add_new_item'      => __( 'Neue Kategorie anlegen', 'rehorik-events' ),
			'new_item_name'     => __( 'Neuer Kategoriename', 'rehorik-events' ),
			'menu_name'         => __( 'Kategorien', 'rehorik-events' ),
		];

		register_taxonomy(
			'reh_event_cat',
			[ 'reh_event' ],
			[
				'labels'       => $labels,
				'hierarchical' => true,
				'rewrite'      => [ 'slug' => 'event-kategorie' ],
				'show_in_rest' => true,
			]
		);
	}
}
