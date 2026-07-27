<?php
/**
 * Plugin Name:       Promos Feed
 * Plugin URI:        https://github.com/Dsantycam/promos-feed
 * Description:        Gestiona promociones de forma simple y bonita, y muéstralas con un bloque de feed totalmente personalizable (grid, carrusel, destacado, lista y mosaico).
 * Version:           1.0.4
 * Requires at least:  6.1
 * Requires PHP:      7.4
 * Author:            Santiago Camacho
 * Author URI:        https://santiagocamachomkt.com
 * Update URI:        https://github.com/Dsantycam/promos-feed
 * Text Domain:       promos-feed
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package PromosFeed
 * @author  Santiago Camacho
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No acceso directo.
}

define( 'PF_VERSION', '1.0.4' );
define( 'PF_FILE', __FILE__ );
define( 'PF_BASENAME', plugin_basename( __FILE__ ) );
define( 'PF_DIR', plugin_dir_path( __FILE__ ) );
define( 'PF_URL', plugin_dir_url( __FILE__ ) );
define( 'PF_POST_TYPE', 'pf_promo' );

// Repositorio de GitHub desde el que se sirven las actualizaciones automáticas.
define( 'PF_GH_USER', 'Dsantycam' );
define( 'PF_GH_REPO', 'promos-feed' );

require_once PF_DIR . 'includes/fields.php';
require_once PF_DIR . 'includes/cpt.php';
require_once PF_DIR . 'includes/settings.php';
require_once PF_DIR . 'includes/admin-panel.php';
require_once PF_DIR . 'includes/render.php';
require_once PF_DIR . 'includes/block.php';
require_once PF_DIR . 'includes/updater.php';

/**
 * Carga las traducciones del plugin.
 */
function pf_load_textdomain() {
	load_plugin_textdomain( 'promos-feed', false, dirname( PF_BASENAME ) . '/languages' );
}
add_action( 'init', 'pf_load_textdomain' );

/**
 * Activación: registra el CPT y refresca las reglas de reescritura.
 */
function pf_activate() {
	pf_register_post_type();
	// Sembramos los ajustes por defecto la primera vez.
	if ( false === get_option( 'pf_fields', false ) ) {
		update_option( 'pf_fields', pf_default_fields_config() );
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pf_activate' );

/**
 * Desactivación: limpia las reglas de reescritura.
 */
function pf_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pf_deactivate' );
