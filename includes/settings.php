<?php
/**
 * Página de Ajustes: elige qué campos pide una promoción.
 *
 * @package PromosFeed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guarda la configuración de campos cuando se envía el formulario de ajustes.
 */
function pf_maybe_save_settings() {
	if ( ! isset( $_POST['pf_settings_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pf_settings_nonce'] ) ), 'pf_save_settings' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$incoming = isset( $_POST['pf_fields'] ) && is_array( $_POST['pf_fields'] ) ? wp_unslash( $_POST['pf_fields'] ) : array();
	$config   = array();

	foreach ( pf_field_definitions() as $key => $def ) {
		$active   = ! empty( $incoming[ $key ]['active'] ) ? 1 : 0;
		$required = ! empty( $incoming[ $key ]['required'] ) ? 1 : 0;
		// Un campo no puede ser obligatorio si no está activo.
		if ( ! $active ) {
			$required = 0;
		}
		$config[ $key ] = array(
			'active'   => $active,
			'required' => $required,
		);
	}

	update_option( 'pf_fields', $config );

	add_settings_error( 'pf_settings', 'pf_saved', __( 'Ajustes guardados. ¡Todo listo! 💛', 'promos-feed' ), 'success' );
}
add_action( 'admin_init', 'pf_maybe_save_settings' );

/**
 * Renderiza la página de Ajustes.
 */
function pf_render_settings_page() {
	$config = pf_get_fields_config();
	settings_errors( 'pf_settings' );
	?>
	<div class="pf-wrap">
		<div class="pf-header">
			<div class="pf-header__title">
				<span class="pf-header__emoji">⚙️</span>
				<div>
					<h1><?php esc_html_e( 'Ajustes de Promos Feed', 'promos-feed' ); ?></h1>
					<p class="pf-header__sub"><?php esc_html_e( 'Elige qué información pide cada promoción. Solo verás los campos activos al crearlas.', 'promos-feed' ); ?></p>
				</div>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=promos-feed' ) ); ?>" class="pf-btn pf-btn--ghost">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
				<?php esc_html_e( 'Volver a promociones', 'promos-feed' ); ?>
			</a>
		</div>

		<form method="post" action="" class="pf-settings">
			<?php wp_nonce_field( 'pf_save_settings', 'pf_settings_nonce' ); ?>

			<div class="pf-fields-list">
				<?php foreach ( pf_field_definitions() as $key => $def ) : ?>
					<div class="pf-field-row <?php echo ! empty( $config[ $key ]['active'] ) ? 'is-active' : ''; ?>" data-field="<?php echo esc_attr( $key ); ?>">
						<div class="pf-field-row__icon">
							<span class="dashicons <?php echo esc_attr( $def['icon'] ); ?>"></span>
						</div>
						<div class="pf-field-row__info">
							<strong><?php echo esc_html( $def['label'] ); ?></strong>
							<span><?php echo esc_html( $def['help'] ); ?></span>
						</div>
						<div class="pf-field-row__controls">
							<label class="pf-switch" title="<?php esc_attr_e( 'Activar campo', 'promos-feed' ); ?>">
								<input type="checkbox" class="pf-switch__input pf-toggle-active" name="pf_fields[<?php echo esc_attr( $key ); ?>][active]" value="1" <?php checked( ! empty( $config[ $key ]['active'] ) ); ?>>
								<span class="pf-switch__slider"></span>
								<span class="pf-switch__label"><?php esc_html_e( 'Activo', 'promos-feed' ); ?></span>
							</label>
							<label class="pf-check pf-required-wrap">
								<input type="checkbox" name="pf_fields[<?php echo esc_attr( $key ); ?>][required]" value="1" <?php checked( ! empty( $config[ $key ]['required'] ) ); ?>>
								<span><?php esc_html_e( 'Obligatorio', 'promos-feed' ); ?></span>
							</label>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="pf-settings__footer">
				<button type="submit" class="pf-btn pf-btn--primary">
					<span class="dashicons dashicons-yes"></span>
					<?php esc_html_e( 'Guardar ajustes', 'promos-feed' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}
