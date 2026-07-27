<?php
/**
 * Auto-actualización del plugin desde GitHub Releases.
 *
 * Sin librerías externas: consulta la última release publicada del repositorio
 * público y, si hay una versión superior, la ofrece en Escritorio → Actualizaciones
 * igual que cualquier plugin del repositorio oficial.
 *
 * @package PromosFeed
 * @author  Santiago Camacho
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obtiene (con caché) la última release publicada en GitHub.
 *
 * @param bool $force Ignora la caché y vuelve a consultar.
 * @return array|false Datos normalizados o false si no se pudo obtener.
 */
function pf_get_latest_release( $force = false ) {
	$cache_key = 'pf_gh_release';

	if ( ! $force ) {
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
	}

	$url = sprintf(
		'https://api.github.com/repos/%s/%s/releases/latest',
		rawurlencode( PF_GH_USER ),
		rawurlencode( PF_GH_REPO )
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'PromosFeed-Updater/' . PF_VERSION,
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		// Guardamos un fallo breve para no martillear la API.
		set_transient( $cache_key, false, HOUR_IN_SECONDS );
		return false;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $data['tag_name'] ) ) {
		set_transient( $cache_key, false, HOUR_IN_SECONDS );
		return false;
	}

	// Buscamos un asset .zip subido a la release (paquete limpio); si no, usamos el zipball.
	$package = isset( $data['zipball_url'] ) ? $data['zipball_url'] : '';
	if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
		foreach ( $data['assets'] as $asset ) {
			if ( isset( $asset['browser_download_url'] ) && '.zip' === strtolower( substr( $asset['name'], -4 ) ) ) {
				$package = $asset['browser_download_url'];
				break;
			}
		}
	}

	$release = array(
		'version'      => ltrim( $data['tag_name'], 'vV' ),
		'package'      => $package,
		'changelog'    => isset( $data['body'] ) ? (string) $data['body'] : '',
		'html_url'     => isset( $data['html_url'] ) ? $data['html_url'] : '',
		'published_at' => isset( $data['published_at'] ) ? $data['published_at'] : '',
	);

	set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );

	return $release;
}

/**
 * Inyecta la actualización disponible en el transient de plugins de WordPress.
 *
 * @param mixed $transient Objeto de actualizaciones.
 * @return mixed
 */
function pf_check_for_update( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	$release = pf_get_latest_release();
	if ( ! $release || empty( $release['package'] ) ) {
		return $transient;
	}

	if ( version_compare( $release['version'], PF_VERSION, '>' ) ) {
		$plugin = array(
			'slug'        => dirname( PF_BASENAME ),
			'plugin'      => PF_BASENAME,
			'new_version' => $release['version'],
			'url'         => 'https://github.com/' . PF_GH_USER . '/' . PF_GH_REPO,
			'package'     => $release['package'],
		);
		$transient->response[ PF_BASENAME ] = (object) $plugin;
	} else {
		// Sin novedades: lo marcamos como "sin actualización" para que WP no consulte a .org.
		$item = array(
			'slug'        => dirname( PF_BASENAME ),
			'plugin'      => PF_BASENAME,
			'new_version' => PF_VERSION,
			'url'         => 'https://github.com/' . PF_GH_USER . '/' . PF_GH_REPO,
			'package'     => '',
		);
		$transient->no_update[ PF_BASENAME ] = (object) $item;
	}

	return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'pf_check_for_update' );

/**
 * Rellena la ventana "Ver detalles / Ver la versión x.x.x" del plugin.
 *
 * @param false|object|array $result Resultado.
 * @param string             $action Acción solicitada.
 * @param object             $args   Argumentos.
 * @return false|object
 */
function pf_plugins_api( $result, $action, $args ) {
	if ( 'plugin_information' !== $action ) {
		return $result;
	}
	if ( empty( $args->slug ) || dirname( PF_BASENAME ) !== $args->slug ) {
		return $result;
	}

	$release = pf_get_latest_release();
	if ( ! $release ) {
		return $result;
	}

	$info = array(
		'name'          => 'Promos Feed',
		'slug'          => dirname( PF_BASENAME ),
		'version'       => $release['version'],
		'author'        => '<a href="https://santiagocamachomkt.com">Santiago Camacho</a>',
		'homepage'      => 'https://github.com/' . PF_GH_USER . '/' . PF_GH_REPO,
		'download_link' => $release['package'],
		'requires'      => '6.1',
		'requires_php'  => '7.4',
		'last_updated'  => $release['published_at'],
		'sections'      => array(
			'description' => __( 'Gestiona promociones de forma simple y bonita, y muéstralas con un bloque de feed totalmente personalizable.', 'promos-feed' ),
			'changelog'   => pf_format_changelog( $release['changelog'] ),
		),
	);

	return (object) $info;
}
add_filter( 'plugins_api', 'pf_plugins_api', 10, 3 );

/**
 * Convierte el cuerpo (Markdown básico) de la release a HTML sencillo.
 *
 * @param string $text Texto de la release.
 * @return string
 */
function pf_format_changelog( $text ) {
	$text = wp_kses_post( $text );
	$text = preg_replace( '/^\s*[-*]\s+(.*)$/m', '<li>$1</li>', $text );
	$text = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text );
	$text = nl2br( $text );
	return $text ? $text : __( 'Consulta las notas de la versión en GitHub.', 'promos-feed' );
}

/**
 * Renombra la carpeta descargada a "promos-feed".
 *
 * GitHub empaqueta los zipball como "usuario-repo-hash", lo que rompería la ruta
 * del plugin. Aquí forzamos el nombre de carpeta correcto tras la descarga.
 *
 * @param string $source        Carpeta de origen ya extraída.
 * @param string $remote_source Carpeta temporal remota.
 * @param object $upgrader      Instancia del actualizador.
 * @param array  $hook_extra    Datos extra del proceso.
 * @return string|WP_Error
 */
function pf_fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
	global $wp_filesystem;

	if ( empty( $hook_extra['plugin'] ) || PF_BASENAME !== $hook_extra['plugin'] ) {
		return $source;
	}

	$desired = trailingslashit( $remote_source ) . dirname( PF_BASENAME );

	if ( trailingslashit( $source ) === trailingslashit( $desired ) ) {
		return $source;
	}

	if ( $wp_filesystem && $wp_filesystem->move( $source, $desired, true ) ) {
		return trailingslashit( $desired );
	}

	return $source;
}
add_filter( 'upgrader_source_selection', 'pf_fix_source_dir', 10, 4 );

/**
 * Limpia la caché de la release tras completar una actualización.
 *
 * @param object $upgrader Instancia.
 * @param array  $options  Datos del proceso.
 */
function pf_purge_update_cache( $upgrader, $options ) {
	if ( isset( $options['action'], $options['type'] ) && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
		delete_transient( 'pf_gh_release' );
	}
}
add_action( 'upgrader_process_complete', 'pf_purge_update_cache', 10, 2 );
