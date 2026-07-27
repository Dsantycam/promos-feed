<?php
/**
 * Custom Post Type "oculto" que sirve de caja fuerte de datos.
 *
 * No mostramos su interfaz clásica: todo se gestiona desde nuestro panel bonito.
 *
 * @package PromosFeed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra el CPT de promociones.
 */
function pf_register_post_type() {
	$labels = array(
		'name'          => __( 'Promociones', 'promos-feed' ),
		'singular_name' => __( 'Promoción', 'promos-feed' ),
	);

	register_post_type(
		PF_POST_TYPE,
		array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false, // Ocultamos la UI clásica del CPT.
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'show_in_rest'        => true, // Necesario para el bloque / editor.
			'rewrite'             => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'menu_icon'           => 'dashicons-megaphone',
		)
	);
}
add_action( 'init', 'pf_register_post_type' );

/**
 * Consulta reutilizable de promociones activas (respeta vigencia por fechas).
 *
 * @param array $args Sobrescribe argumentos de WP_Query.
 * @return WP_Query
 */
function pf_query_promos( $args = array() ) {
	$defaults = array(
		'post_type'      => PF_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);

	// Permite saltarse el filtro de vigencia (útil en el panel de administración).
	$ignore_dates = ! empty( $args['pf_ignore_dates'] );
	unset( $args['pf_ignore_dates'] );

	$args = wp_parse_args( $args, $defaults );

	// Si el campo de fechas está activo, filtramos las promos expiradas o aún no iniciadas.
	if ( ! $ignore_dates && pf_field_active( 'dates' ) ) {
		$today        = current_time( 'Y-m-d' );
		$meta_query   = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
		$meta_query[] = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_pf_date_start',
					'value'   => $today,
					'compare' => '<=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_pf_date_start',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_pf_date_start',
					'value' => '',
				),
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => '_pf_date_end',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_pf_date_end',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_pf_date_end',
					'value' => '',
				),
			),
		);
		$args['meta_query'] = $meta_query;
	}

	return new WP_Query( $args );
}

/**
 * Recupera todos los datos de una promo en un array normalizado.
 *
 * @param int|WP_Post $post Post o ID.
 * @return array
 */
function pf_get_promo_data( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}

	$image_id = get_post_thumbnail_id( $post->ID );

	return array(
		'id'          => $post->ID,
		'title'       => $post->post_title,
		'description' => $post->post_content,
		'image_id'    => $image_id ? (int) $image_id : 0,
		'image_url'   => $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '',
		'thumb_url'   => $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '',
		'price'       => get_post_meta( $post->ID, '_pf_price', true ),
		'compare'     => get_post_meta( $post->ID, '_pf_compare', true ),
		'discount'    => get_post_meta( $post->ID, '_pf_discount', true ),
		'badge'       => get_post_meta( $post->ID, '_pf_badge', true ),
		'date_start'  => get_post_meta( $post->ID, '_pf_date_start', true ),
		'date_end'    => get_post_meta( $post->ID, '_pf_date_end', true ),
		'link_url'    => get_post_meta( $post->ID, '_pf_link_url', true ),
		'link_label'  => get_post_meta( $post->ID, '_pf_link_label', true ),
		'menu_order'  => (int) $post->menu_order,
	);
}
