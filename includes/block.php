<?php
/**
 * Registro del bloque "Promos Feed" (dinámico, render en PHP, sin build).
 *
 * @package PromosFeed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra scripts/estilos con handles explícitos y luego el bloque.
 */
function pf_register_block() {
	// Script del editor (JS nativo, sin JSX ni compilación).
	wp_register_script(
		'pf-block-editor',
		PF_URL . 'assets/js/block.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
		PF_VERSION,
		true
	);

	// Estilo compartido (editor + front).
	wp_register_style(
		'pf-feed-style',
		PF_URL . 'assets/css/frontend.css',
		array( 'dashicons' ),
		PF_VERSION
	);

	// Estilo extra solo del editor.
	wp_register_style(
		'pf-feed-editor-style',
		PF_URL . 'assets/css/editor.css',
		array( 'pf-feed-style' ),
		PF_VERSION
	);

	// Script del front (carrusel).
	wp_register_script(
		'pf-feed-view',
		PF_URL . 'assets/js/frontend.js',
		array(),
		PF_VERSION,
		true
	);

	// Datos para el editor: qué campos están activos.
	wp_localize_script(
		'pf-block-editor',
		'pfBlock',
		array(
			'activeFields' => pf_active_fields(),
		)
	);

	register_block_type(
		PF_DIR . 'block.json',
		array(
			'render_callback' => 'pf_block_render_callback',
		)
	);
}
add_action( 'init', 'pf_register_block' );

/**
 * Callback de render del bloque en el front.
 *
 * @param array $attributes Atributos.
 * @return string
 */
function pf_block_render_callback( $attributes ) {
	// Aseguramos el JS del carrusel cuando el bloque aparece en la página.
	if ( ! is_admin() ) {
		wp_enqueue_script( 'pf-feed-view' );
	}
	return pf_render_feed( $attributes );
}
