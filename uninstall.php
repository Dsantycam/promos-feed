<?php
/**
 * Limpieza al desinstalar el plugin.
 *
 * No borramos las promociones (son contenido del usuario); solo la configuración.
 *
 * @package PromosFeed
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'pf_fields' );
