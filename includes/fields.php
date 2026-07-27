<?php
/**
 * Registro central de campos de una promoción.
 *
 * Toda la lógica de "qué campos existen y cuáles están activos" vive aquí,
 * para que el panel de admin, los ajustes y el bloque siempre estén sincronizados.
 *
 * @package PromosFeed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catálogo completo de campos disponibles para una promoción.
 *
 * @return array
 */
function pf_field_definitions() {
	return array(
		'image'       => array(
			'label' => __( 'Imagen', 'promos-feed' ),
			'help'  => __( 'La imagen destacada de la promoción.', 'promos-feed' ),
			'type'  => 'image',
			'icon'  => 'dashicons-format-image',
		),
		'title'       => array(
			'label' => __( 'Título', 'promos-feed' ),
			'help'  => __( 'Un título corto para la promoción.', 'promos-feed' ),
			'type'  => 'text',
			'icon'  => 'dashicons-editor-textcolor',
		),
		'description' => array(
			'label' => __( 'Descripción', 'promos-feed' ),
			'help'  => __( 'Un texto breve que acompaña a la promoción.', 'promos-feed' ),
			'type'  => 'textarea',
			'icon'  => 'dashicons-text',
		),
		'price'       => array(
			'label' => __( 'Precio / Descuento', 'promos-feed' ),
			'help'  => __( 'Precio actual, precio anterior y % de descuento.', 'promos-feed' ),
			'type'  => 'price',
			'icon'  => 'dashicons-tag',
		),
		'badge'       => array(
			'label' => __( 'Etiqueta (Badge)', 'promos-feed' ),
			'help'  => __( 'Una etiqueta corta, ej. "Nuevo" o "Últimas unidades".', 'promos-feed' ),
			'type'  => 'text',
			'icon'  => 'dashicons-awards',
		),
		'dates'       => array(
			'label' => __( 'Vigencia (fechas)', 'promos-feed' ),
			'help'  => __( 'Fecha de inicio y fin. La promo se oculta al expirar.', 'promos-feed' ),
			'type'  => 'daterange',
			'icon'  => 'dashicons-calendar-alt',
		),
		'link'        => array(
			'label' => __( 'Enlace / Botón (CTA)', 'promos-feed' ),
			'help'  => __( 'Un enlace de destino y el texto del botón.', 'promos-feed' ),
			'type'  => 'link',
			'icon'  => 'dashicons-admin-links',
		),
	);
}

/**
 * Configuración por defecto de campos (primera activación).
 *
 * Solo la imagen viene activa por defecto, tal y como se acordó.
 *
 * @return array
 */
function pf_default_fields_config() {
	$config = array();
	foreach ( pf_field_definitions() as $key => $def ) {
		$config[ $key ] = array(
			'active'   => ( 'image' === $key ) ? 1 : 0,
			'required' => ( 'image' === $key ) ? 1 : 0,
		);
	}
	return $config;
}

/**
 * Devuelve la configuración de campos guardada, fusionada con los valores por defecto.
 *
 * @return array
 */
function pf_get_fields_config() {
	$saved   = get_option( 'pf_fields', array() );
	$default = pf_default_fields_config();
	$config  = array();

	foreach ( pf_field_definitions() as $key => $def ) {
		$config[ $key ] = array(
			'active'   => isset( $saved[ $key ]['active'] ) ? (int) $saved[ $key ]['active'] : $default[ $key ]['active'],
			'required' => isset( $saved[ $key ]['required'] ) ? (int) $saved[ $key ]['required'] : $default[ $key ]['required'],
		);
	}
	return $config;
}

/**
 * ¿Está activo un campo?
 *
 * @param string $key Clave del campo.
 * @return bool
 */
function pf_field_active( $key ) {
	$config = pf_get_fields_config();
	return ! empty( $config[ $key ]['active'] );
}

/**
 * ¿Es obligatorio un campo?
 *
 * @param string $key Clave del campo.
 * @return bool
 */
function pf_field_required( $key ) {
	$config = pf_get_fields_config();
	return ! empty( $config[ $key ]['required'] );
}

/**
 * Lista de claves de campos activos, respetando el orden del catálogo.
 *
 * @return string[]
 */
function pf_active_fields() {
	$active = array();
	foreach ( pf_field_definitions() as $key => $def ) {
		if ( pf_field_active( $key ) ) {
			$active[] = $key;
		}
	}
	return $active;
}
