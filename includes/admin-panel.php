<?php
/**
 * Panel de administración a medida: crear, editar, eliminar y reordenar promos.
 *
 * @package PromosFeed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra el menú del plugin (panel + ajustes).
 */
function pf_register_menu() {
	add_menu_page(
		__( 'Promos Feed', 'promos-feed' ),
		__( 'Promos Feed', 'promos-feed' ),
		'edit_posts',
		'promos-feed',
		'pf_render_admin_panel',
		'dashicons-megaphone',
		26
	);

	add_submenu_page(
		'promos-feed',
		__( 'Promociones', 'promos-feed' ),
		__( 'Promociones', 'promos-feed' ),
		'edit_posts',
		'promos-feed',
		'pf_render_admin_panel'
	);

	add_submenu_page(
		'promos-feed',
		__( 'Ajustes', 'promos-feed' ),
		__( 'Ajustes', 'promos-feed' ),
		'manage_options',
		'promos-feed-settings',
		'pf_render_settings_page'
	);
}
add_action( 'admin_menu', 'pf_register_menu' );

/**
 * Carga estilos y scripts solo en nuestras pantallas.
 *
 * @param string $hook Hook de la pantalla actual.
 */
function pf_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'promos-feed' ) ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style( 'pf-admin', PF_URL . 'assets/css/admin.css', array( 'dashicons' ), PF_VERSION );
	wp_enqueue_script( 'pf-admin', PF_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), PF_VERSION, true );

	// Datos de todas las promos para poder editarlas sin recargar.
	$promos = array();
	$query  = pf_query_promos(
		array(
			'posts_per_page'  => -1,
			'post_status'     => 'publish',
			'pf_ignore_dates' => true, // En el panel gestionamos también las expiradas.
		)
	);
	if ( $query->have_posts() ) {
		foreach ( $query->posts as $p ) {
			$promos[ $p->ID ] = pf_get_promo_data( $p );
		}
	}

	// Mapa de campos obligatorios (clave => 0/1).
	$required_map = array();
	foreach ( pf_active_fields() as $field_key ) {
		$required_map[ $field_key ] = pf_field_required( $field_key ) ? 1 : 0;
	}

	wp_localize_script(
		'pf-admin',
		'pfData',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'pf_admin' ),
			'activeFields' => pf_active_fields(),
			'required'     => $required_map,
			'promos'       => $promos,
			'i18n'         => array(
				'confirmDelete' => __( '¿Seguro que quieres eliminar esta promoción?', 'promos-feed' ),
				'chooseImage'   => __( 'Elegir imagen', 'promos-feed' ),
				'changeImage'   => __( 'Cambiar imagen', 'promos-feed' ),
				'useImage'      => __( 'Usar esta imagen', 'promos-feed' ),
				'saving'        => __( 'Guardando…', 'promos-feed' ),
				'errorGeneric'  => __( 'Ha ocurrido un error. Inténtalo de nuevo.', 'promos-feed' ),
				'imageRequired' => __( 'La imagen es obligatoria.', 'promos-feed' ),
				'fieldRequired' => __( 'Este campo es obligatorio.', 'promos-feed' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'pf_admin_assets' );

/**
 * Renderiza los campos del formulario según los campos activos.
 *
 * Se usa como plantilla vacía; el JS la rellena al editar.
 */
function pf_render_promo_form_fields() {
	$active = pf_active_fields();
	$defs   = pf_field_definitions();

	foreach ( $active as $key ) {
		$def      = $defs[ $key ];
		$required = pf_field_required( $key );
		$req_mark = $required ? '<span class="pf-req">*</span>' : '';

		echo '<div class="pf-form-group" data-field="' . esc_attr( $key ) . '">';

		switch ( $def['type'] ) {
			case 'image':
				echo '<label>' . esc_html( $def['label'] ) . wp_kses_post( $req_mark ) . '</label>';
				echo '<div class="pf-image-picker">';
				echo '<div class="pf-image-preview" data-empty="true"><span class="dashicons dashicons-format-image"></span></div>';
				echo '<div class="pf-image-actions">';
				echo '<button type="button" class="pf-btn pf-btn--ghost pf-choose-image">' . esc_html__( 'Elegir imagen', 'promos-feed' ) . '</button>';
				echo '<button type="button" class="pf-btn pf-btn--link pf-remove-image" hidden>' . esc_html__( 'Quitar', 'promos-feed' ) . '</button>';
				echo '</div>';
				echo '<input type="hidden" name="image_id" value="">';
				echo '</div>';
				break;

			case 'text':
				echo '<label for="pf-' . esc_attr( $key ) . '">' . esc_html( $def['label'] ) . wp_kses_post( $req_mark ) . '</label>';
				echo '<input type="text" id="pf-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="pf-input">';
				break;

			case 'textarea':
				echo '<label for="pf-' . esc_attr( $key ) . '">' . esc_html( $def['label'] ) . wp_kses_post( $req_mark ) . '</label>';
				echo '<textarea id="pf-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="pf-input" rows="3"></textarea>';
				break;

			case 'price':
				echo '<label>' . esc_html( $def['label'] ) . wp_kses_post( $req_mark ) . '</label>';
				echo '<div class="pf-price-grid">';
				echo '<input type="text" name="price" class="pf-input" placeholder="' . esc_attr__( 'Precio actual (ej. 19,99 €)', 'promos-feed' ) . '">';
				echo '<input type="text" name="compare" class="pf-input" placeholder="' . esc_attr__( 'Precio anterior', 'promos-feed' ) . '">';
				echo '<input type="text" name="discount" class="pf-input" placeholder="' . esc_attr__( 'Descuento (ej. -30%)', 'promos-feed' ) . '">';
				echo '</div>';
				break;

			case 'daterange':
				echo '<label>' . esc_html( $def['label'] ) . wp_kses_post( $req_mark ) . '</label>';
				echo '<div class="pf-date-grid">';
				echo '<div><span class="pf-mini-label">' . esc_html__( 'Inicio', 'promos-feed' ) . '</span><input type="date" name="date_start" class="pf-input"></div>';
				echo '<div><span class="pf-mini-label">' . esc_html__( 'Fin', 'promos-feed' ) . '</span><input type="date" name="date_end" class="pf-input"></div>';
				echo '</div>';
				break;

			case 'link':
				echo '<label>' . esc_html( $def['label'] ) . wp_kses_post( $req_mark ) . '</label>';
				echo '<div class="pf-link-grid">';
				echo '<input type="url" name="link_url" class="pf-input" placeholder="https://…">';
				echo '<input type="text" name="link_label" class="pf-input" placeholder="' . esc_attr__( 'Texto del botón (ej. Ver oferta)', 'promos-feed' ) . '">';
				echo '</div>';
				break;
		}

		echo '</div>';
	}
}

/**
 * Renderiza el panel principal.
 */
function pf_render_admin_panel() {
	$query  = pf_query_promos(
		array(
			'posts_per_page'  => -1,
			'post_status'     => 'publish',
			'pf_ignore_dates' => true,
		)
	);
	$promos = $query->have_posts() ? $query->posts : array();
	$count  = count( $promos );
	?>
	<div class="pf-wrap">
		<div class="pf-header">
			<div class="pf-header__title">
				<span class="pf-header__emoji">✨</span>
				<div>
					<h1><?php esc_html_e( 'Promociones', 'promos-feed' ); ?></h1>
					<p class="pf-header__sub">
						<?php
						/* translators: %d: número de promociones */
						printf( esc_html( _n( '%d promoción · arrastra para reordenar', '%d promociones · arrastra para reordenar', $count, 'promos-feed' ) ), (int) $count );
						?>
					</p>
				</div>
			</div>
			<div class="pf-header__actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=promos-feed-settings' ) ); ?>" class="pf-btn pf-btn--ghost">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Ajustes', 'promos-feed' ); ?>
				</a>
				<button type="button" class="pf-btn pf-btn--primary pf-open-new">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Nueva promoción', 'promos-feed' ); ?>
				</button>
			</div>
		</div>

		<?php if ( empty( pf_active_fields() ) ) : ?>
			<div class="pf-notice pf-notice--warn">
				<span class="dashicons dashicons-warning"></span>
				<?php
				printf(
					/* translators: %s: enlace a ajustes */
					wp_kses_post( __( 'No hay ningún campo activo. Ve a %s para activar al menos uno.', 'promos-feed' ) ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=promos-feed-settings' ) ) . '">' . esc_html__( 'Ajustes', 'promos-feed' ) . '</a>'
				);
				?>
			</div>
		<?php endif; ?>

		<div class="pf-grid" id="pf-grid" data-count="<?php echo (int) $count; ?>">
			<?php foreach ( $promos as $p ) : ?>
				<?php echo pf_render_admin_card( $p ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>

			<button type="button" class="pf-card pf-card--add pf-open-new">
				<span class="dashicons dashicons-plus-alt"></span>
				<span><?php esc_html_e( 'Añadir promoción', 'promos-feed' ); ?></span>
			</button>
		</div>

		<div class="pf-empty" <?php echo $count > 0 ? 'hidden' : ''; ?>>
			<div class="pf-empty__art">🎉</div>
			<h2><?php esc_html_e( 'Aún no tienes promociones', 'promos-feed' ); ?></h2>
			<p><?php esc_html_e( 'Crea la primera y aparecerá aquí al instante.', 'promos-feed' ); ?></p>
		</div>
	</div>

	<!-- Modal de crear / editar -->
	<div class="pf-modal" id="pf-modal" hidden>
		<div class="pf-modal__backdrop"></div>
		<div class="pf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pf-modal-title">
			<div class="pf-modal__head">
				<h2 id="pf-modal-title"><?php esc_html_e( 'Nueva promoción', 'promos-feed' ); ?></h2>
				<button type="button" class="pf-modal__close" aria-label="<?php esc_attr_e( 'Cerrar', 'promos-feed' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<form id="pf-promo-form" class="pf-modal__body">
				<input type="hidden" name="promo_id" value="0">
				<?php pf_render_promo_form_fields(); ?>
			</form>
			<div class="pf-modal__foot">
				<button type="button" class="pf-btn pf-btn--ghost pf-modal__close"><?php esc_html_e( 'Cancelar', 'promos-feed' ); ?></button>
				<button type="button" class="pf-btn pf-btn--primary pf-save-promo">
					<span class="dashicons dashicons-yes"></span>
					<?php esc_html_e( 'Guardar promoción', 'promos-feed' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Renderiza una tarjeta de promo en el panel.
 *
 * @param WP_Post $post Post de la promo.
 * @return string
 */
function pf_render_admin_card( $post ) {
	$data  = pf_get_promo_data( $post );
	$label = $data['title'];
	if ( '' === $label ) {
		$label = $data['badge'] ? $data['badge'] : sprintf( __( 'Promo #%d', 'promos-feed' ), $data['id'] );
	}

	ob_start();
	?>
	<div class="pf-card" data-id="<?php echo (int) $data['id']; ?>" tabindex="0">
		<div class="pf-card__media">
			<?php if ( $data['thumb_url'] ) : ?>
				<img src="<?php echo esc_url( $data['thumb_url'] ); ?>" alt="" loading="lazy">
			<?php else : ?>
				<div class="pf-card__noimg"><span class="dashicons dashicons-format-image"></span></div>
			<?php endif; ?>
			<?php if ( $data['badge'] ) : ?>
				<span class="pf-card__badge"><?php echo esc_html( $data['badge'] ); ?></span>
			<?php endif; ?>
			<span class="pf-card__drag" title="<?php esc_attr_e( 'Arrastrar', 'promos-feed' ); ?>"><span class="dashicons dashicons-move"></span></span>
		</div>
		<div class="pf-card__body">
			<span class="pf-card__label"><?php echo esc_html( $label ); ?></span>
			<?php if ( $data['price'] ) : ?>
				<span class="pf-card__price"><?php echo esc_html( $data['price'] ); ?></span>
			<?php endif; ?>
		</div>
		<div class="pf-card__actions">
			<button type="button" class="pf-icon-btn pf-edit-promo" title="<?php esc_attr_e( 'Editar', 'promos-feed' ); ?>"><span class="dashicons dashicons-edit"></span></button>
			<button type="button" class="pf-icon-btn pf-icon-btn--danger pf-delete-promo" title="<?php esc_attr_e( 'Eliminar', 'promos-feed' ); ?>"><span class="dashicons dashicons-trash"></span></button>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Sanea y guarda los meta de una promo desde $_POST.
 *
 * @param int $post_id ID de la promo.
 */
function pf_save_promo_meta( $post_id ) {
	$map = array(
		'price'      => '_pf_price',
		'compare'    => '_pf_compare',
		'discount'   => '_pf_discount',
		'badge'      => '_pf_badge',
		'date_start' => '_pf_date_start',
		'date_end'   => '_pf_date_end',
		'link_url'   => '_pf_link_url',
		'link_label' => '_pf_link_label',
	);

	foreach ( $map as $field => $meta_key ) {
		if ( ! isset( $_POST[ $field ] ) ) {
			continue;
		}
		$value = wp_unslash( $_POST[ $field ] );

		if ( 'link_url' === $field ) {
			$value = esc_url_raw( $value );
		} elseif ( in_array( $field, array( 'date_start', 'date_end' ), true ) ) {
			$value = sanitize_text_field( $value );
		} else {
			$value = sanitize_text_field( $value );
		}

		update_post_meta( $post_id, $meta_key, $value );
	}
}

/**
 * AJAX: crea o actualiza una promo.
 */
function pf_ajax_save_promo() {
	check_ajax_referer( 'pf_admin', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Sin permisos.', 'promos-feed' ) ) );
	}

	$promo_id = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
	$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$content  = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
	$image_id = isset( $_POST['image_id'] ) ? absint( $_POST['image_id'] ) : 0;

	// Validación de campos obligatorios activos.
	foreach ( pf_active_fields() as $key ) {
		if ( ! pf_field_required( $key ) ) {
			continue;
		}
		if ( 'image' === $key && ! $image_id ) {
			wp_send_json_error( array( 'message' => __( 'La imagen es obligatoria.', 'promos-feed' ) ) );
		}
		if ( 'title' === $key && '' === $title ) {
			wp_send_json_error( array( 'message' => __( 'El título es obligatorio.', 'promos-feed' ) ) );
		}
		if ( 'description' === $key && '' === $content ) {
			wp_send_json_error( array( 'message' => __( 'La descripción es obligatoria.', 'promos-feed' ) ) );
		}
	}

	$postarr = array(
		'post_type'    => PF_POST_TYPE,
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => $content,
	);

	if ( $promo_id ) {
		$postarr['ID'] = $promo_id;
		$post_id       = wp_update_post( $postarr, true );
	} else {
		// Nuevas promos van al principio (menu_order más bajo).
		$postarr['menu_order'] = pf_lowest_menu_order() - 1;
		$post_id               = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
	}

	if ( $image_id ) {
		set_post_thumbnail( $post_id, $image_id );
	} else {
		delete_post_thumbnail( $post_id );
	}

	pf_save_promo_meta( $post_id );

	wp_send_json_success(
		array(
			'card' => pf_render_admin_card( get_post( $post_id ) ),
			'id'   => $post_id,
		)
	);
}
add_action( 'wp_ajax_pf_save_promo', 'pf_ajax_save_promo' );

/**
 * AJAX: elimina (envía a la papelera) una promo.
 */
function pf_ajax_delete_promo() {
	check_ajax_referer( 'pf_admin', 'nonce' );
	if ( ! current_user_can( 'delete_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Sin permisos.', 'promos-feed' ) ) );
	}

	$promo_id = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
	if ( ! $promo_id || get_post_type( $promo_id ) !== PF_POST_TYPE ) {
		wp_send_json_error( array( 'message' => __( 'Promo no válida.', 'promos-feed' ) ) );
	}

	wp_trash_post( $promo_id );
	wp_send_json_success();
}
add_action( 'wp_ajax_pf_delete_promo', 'pf_ajax_delete_promo' );

/**
 * AJAX: reordena las promos.
 */
function pf_ajax_reorder() {
	check_ajax_referer( 'pf_admin', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}

	$order = isset( $_POST['order'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['order'] ) ) : array();
	foreach ( $order as $index => $id ) {
		if ( get_post_type( $id ) === PF_POST_TYPE ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'menu_order' => $index,
				)
			);
		}
	}
	wp_send_json_success();
}
add_action( 'wp_ajax_pf_reorder', 'pf_ajax_reorder' );

/**
 * Devuelve el menu_order más bajo actual (para poner nuevas promos arriba).
 *
 * @return int
 */
function pf_lowest_menu_order() {
	$q = new WP_Query(
		array(
			'post_type'      => PF_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		)
	);
	if ( empty( $q->posts ) ) {
		return 0;
	}
	$post = get_post( $q->posts[0] );
	return (int) $post->menu_order;
}
