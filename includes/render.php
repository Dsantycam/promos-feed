<?php
/**
 * Motor de render del feed (compartido por el bloque en editor y en el front).
 *
 * @package PromosFeed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valores por defecto de los atributos del bloque.
 *
 * @return array
 */
function pf_default_attrs() {
	return array(
		'layout'          => 'grid',
		'count'           => 6,
		'columns'         => 3,
		'gap'             => 16,
		'radius'          => 16,
		'shadow'          => true,
		'accentColor'     => '#e11d48',
		'bgColor'         => '#ffffff',
		'textColor'       => '#111827',
		'imageRatio'      => '1/1',
		'imageSize'       => 'large',
		'imageFit'        => 'cover',
		'order'           => 'menu_order',
		'showImage'       => true,
		'showTitle'       => true,
		'showDescription' => true,
		'showPrice'       => true,
		'showBadge'       => true,
		'showDates'       => false,
		'showLink'        => true,
		'autoplay'        => false,
		'autoplaySpeed'   => 4,
		'showArrows'      => true,
		'showDots'        => true,
		'featuredBig'     => true,
	);
}

/**
 * Normaliza atributos entrantes con los valores por defecto.
 *
 * @param array $attrs Atributos.
 * @return array
 */
function pf_normalize_attrs( $attrs ) {
	$attrs = wp_parse_args( (array) $attrs, pf_default_attrs() );

	$attrs['count']         = max( 1, min( 48, (int) $attrs['count'] ) );
	$attrs['columns']       = max( 1, min( 6, (int) $attrs['columns'] ) );
	$attrs['gap']           = max( 0, min( 64, (int) $attrs['gap'] ) );
	$attrs['radius']        = max( 0, min( 40, (int) $attrs['radius'] ) );
	$attrs['autoplaySpeed'] = max( 1, min( 15, (int) $attrs['autoplaySpeed'] ) );

	$allowed_layouts = array( 'grid', 'carousel', 'featured', 'list', 'masonry' );
	if ( ! in_array( $attrs['layout'], $allowed_layouts, true ) ) {
		$attrs['layout'] = 'grid';
	}

	$allowed_sizes = array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' );
	if ( ! in_array( $attrs['imageSize'], $allowed_sizes, true ) ) {
		$attrs['imageSize'] = 'large';
	}

	$allowed_fits = array( 'cover', 'contain', 'fill' );
	if ( ! in_array( $attrs['imageFit'], $allowed_fits, true ) ) {
		$attrs['imageFit'] = 'cover';
	}

	return $attrs;
}

/**
 * Renderiza el feed completo.
 *
 * @param array $attrs Atributos del bloque.
 * @return string HTML.
 */
function pf_render_feed( $attrs ) {
	$attrs = pf_normalize_attrs( $attrs );

	// Orden.
	switch ( $attrs['order'] ) {
		case 'recent':
			$orderby = 'date';
			$order   = 'DESC';
			break;
		case 'random':
			$orderby = 'rand';
			$order   = 'ASC';
			break;
		default:
			$orderby = 'menu_order';
			$order   = 'ASC';
	}

	$query = pf_query_promos(
		array(
			'posts_per_page' => $attrs['count'],
			'orderby'        => $orderby,
			'order'          => $order,
		)
	);

	if ( ! $query->have_posts() ) {
		return '<div class="pf-feed pf-feed--empty"><p>' . esc_html__( 'Todavía no hay promociones que mostrar.', 'promos-feed' ) . '</p></div>';
	}

	// Variables CSS que controlan toda la estética.
	$style = sprintf(
		'--pf-cols:%d;--pf-gap:%dpx;--pf-radius:%dpx;--pf-accent:%s;--pf-bg:%s;--pf-text:%s;--pf-ratio:%s;--pf-fit:%s;--pf-shadow:%s;',
		$attrs['columns'],
		$attrs['gap'],
		$attrs['radius'],
		esc_attr( $attrs['accentColor'] ),
		esc_attr( $attrs['bgColor'] ),
		esc_attr( $attrs['textColor'] ),
		esc_attr( $attrs['imageRatio'] ),
		esc_attr( $attrs['imageFit'] ),
		$attrs['shadow'] ? '0 8px 30px rgba(0,0,0,.10)' : 'none'
	);

	$classes = array( 'pf-feed', 'pf-feed--' . $attrs['layout'] );
	if ( $attrs['shadow'] ) {
		$classes[] = 'has-shadow';
	}

	$uid = 'pf-' . wp_generate_password( 6, false, false );

	$data_attrs = '';
	if ( 'carousel' === $attrs['layout'] ) {
		$data_attrs = sprintf(
			' data-autoplay="%s" data-speed="%d" data-arrows="%s" data-dots="%s"',
			$attrs['autoplay'] ? '1' : '0',
			$attrs['autoplaySpeed'],
			$attrs['showArrows'] ? '1' : '0',
			$attrs['showDots'] ? '1' : '0'
		);
	}

	ob_start();
	echo '<div id="' . esc_attr( $uid ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '"' . $data_attrs . '>'; // phpcs:ignore

	if ( 'carousel' === $attrs['layout'] && $attrs['showArrows'] ) {
		echo '<button type="button" class="pf-arrow pf-arrow--prev" aria-label="' . esc_attr__( 'Anterior', 'promos-feed' ) . '"><span class="dashicons dashicons-arrow-left-alt2"></span></button>';
	}

	echo '<div class="pf-track">';

	$i = 0;
	foreach ( $query->posts as $post ) {
		$data     = pf_get_promo_data( $post );
		$is_first = ( 0 === $i && 'featured' === $attrs['layout'] && $attrs['featuredBig'] );
		echo pf_render_promo_card_front( $data, $attrs, $is_first ); // phpcs:ignore
		$i++;
	}

	echo '</div>'; // .pf-track

	if ( 'carousel' === $attrs['layout'] && $attrs['showArrows'] ) {
		echo '<button type="button" class="pf-arrow pf-arrow--next" aria-label="' . esc_attr__( 'Siguiente', 'promos-feed' ) . '"><span class="dashicons dashicons-arrow-right-alt2"></span></button>';
	}

	if ( 'carousel' === $attrs['layout'] && $attrs['showDots'] ) {
		echo '<div class="pf-dots" aria-hidden="true"></div>';
	}

	echo '</div>'; // .pf-feed

	return ob_get_clean();
}

/**
 * Renderiza una tarjeta de promo para el front.
 *
 * @param array $data     Datos de la promo.
 * @param array $attrs    Atributos del bloque.
 * @param bool  $featured Si es la tarjeta destacada grande.
 * @return string
 */
function pf_render_promo_card_front( $data, $attrs, $featured = false ) {
	$active = pf_active_fields();

	// Helper: un campo se muestra si está activo, el toggle está on y tiene contenido.
	$can = function ( $key, $attr_flag, $value ) use ( $active, $attrs ) {
		return in_array( $key, $active, true ) && ! empty( $attrs[ $attr_flag ] ) && '' !== trim( (string) $value );
	};

	$has_image     = in_array( 'image', $active, true ) && ! empty( $attrs['showImage'] ) && $data['image_url'];
	$has_link_data = in_array( 'link', $active, true ) && '' !== trim( (string) $data['link_url'] );
	$show_button   = $has_link_data && ! empty( $attrs['showLink'] );

	$card_classes = array( 'pf-item' );
	if ( $featured ) {
		$card_classes[] = 'pf-item--featured';
	}

	ob_start();
	echo '<article class="' . esc_attr( implode( ' ', $card_classes ) ) . '">';

	// Media.
	if ( $has_image || $can( 'badge', 'showBadge', $data['badge'] ) || $can( 'price', 'showPrice', $data['discount'] ) ) {
		echo '<div class="pf-item__media">';
		if ( $has_image ) {
			// Servimos la imagen en el tamaño elegido y con srcset (nitidez correcta,
			// igual que el editor de WordPress), no una miniatura comprimida.
			$size = $featured ? 'full' : $attrs['imageSize'];
			echo wp_get_attachment_image(
				$data['image_id'],
				$size,
				false,
				array(
					'alt'      => $data['title'],
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		} else {
			echo '<div class="pf-item__noimg"></div>';
		}
		if ( $can( 'badge', 'showBadge', $data['badge'] ) ) {
			echo '<span class="pf-item__badge">' . esc_html( $data['badge'] ) . '</span>';
		}
		if ( $can( 'price', 'showPrice', $data['discount'] ) ) {
			echo '<span class="pf-item__discount">' . esc_html( $data['discount'] ) . '</span>';
		}
		echo '</div>';
	}

	// Cuerpo (solo si hay algo que mostrar).
	$has_body = $can( 'title', 'showTitle', $data['title'] )
		|| $can( 'description', 'showDescription', $data['description'] )
		|| $can( 'price', 'showPrice', $data['price'] )
		|| $show_button
		|| ( $can( 'dates', 'showDates', $data['date_end'] ) );

	if ( $has_body ) {
		echo '<div class="pf-item__body">';

		if ( $can( 'title', 'showTitle', $data['title'] ) ) {
			echo '<h3 class="pf-item__title">' . esc_html( $data['title'] ) . '</h3>';
		}

		if ( $can( 'description', 'showDescription', $data['description'] ) ) {
			echo '<p class="pf-item__desc">' . esc_html( wp_trim_words( $data['description'], $featured ? 40 : 20 ) ) . '</p>';
		}

		if ( $can( 'price', 'showPrice', $data['price'] ) || $can( 'price', 'showPrice', $data['compare'] ) ) {
			echo '<div class="pf-item__prices">';
			if ( $can( 'price', 'showPrice', $data['price'] ) ) {
				echo '<span class="pf-item__price">' . esc_html( $data['price'] ) . '</span>';
			}
			if ( $can( 'price', 'showPrice', $data['compare'] ) ) {
				echo '<span class="pf-item__compare">' . esc_html( $data['compare'] ) . '</span>';
			}
			echo '</div>';
		}

		if ( $can( 'dates', 'showDates', $data['date_end'] ) ) {
			$end = date_i18n( get_option( 'date_format' ), strtotime( $data['date_end'] ) );
			echo '<span class="pf-item__dates"><span class="dashicons dashicons-clock"></span> ' . esc_html( sprintf( __( 'Hasta el %s', 'promos-feed' ), $end ) ) . '</span>';
		}

		if ( $show_button ) {
			$label = $data['link_label'] ? $data['link_label'] : __( 'Ver oferta', 'promos-feed' );
			echo '<a class="pf-item__btn" href="' . esc_url( $data['link_url'] ) . '">' . esc_html( $label ) . '</a>';
		}

		echo '</div>';
	}

	// Si toda la tarjeta es un enlace y no hay botón visible aparte, la hacemos clicable.
	echo '</article>';

	$html = ob_get_clean();

	// Envolvemos en enlace si hay destino pero sin botón visible (tarjeta clicable).
	if ( $has_link_data && empty( $attrs['showLink'] ) ) {
		$html = '<a class="pf-item-link" href="' . esc_url( $data['link_url'] ) . '">' . $html . '</a>';
	}

	return $html;
}
