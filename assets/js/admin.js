/**
 * Promos Feed — lógica del panel de administración.
 * Crear / editar / eliminar / reordenar sin recargar la página.
 */
( function ( $ ) {
	'use strict';

	var D = window.pfData || {};
	var i18n = D.i18n || {};
	var required = D.required || {};
	var promos = D.promos || {};

	var $modal = $( '#pf-modal' );
	var $form = $( '#pf-promo-form' );
	var mediaFrame = null;

	/* ---------------------------------------------------------------
	 * Utilidades
	 * ------------------------------------------------------------- */
	function openModal() {
		$modal.removeAttr( 'hidden' );
		$( 'body' ).css( 'overflow', 'hidden' );
	}

	function closeModal() {
		$modal.attr( 'hidden', true );
		$( 'body' ).css( 'overflow', '' );
		clearError();
	}

	function showError( msg ) {
		clearError();
		$( '<div class="pf-form-error"></div>' ).text( msg ).prependTo( $form );
	}

	function clearError() {
		$form.find( '.pf-form-error' ).remove();
	}

	function setImagePreview( url ) {
		var $preview = $form.find( '.pf-image-preview' );
		var $remove = $form.find( '.pf-remove-image' );
		if ( url ) {
			$preview.attr( 'data-empty', 'false' ).html( '<img src="' + url + '" alt="">' );
			$remove.prop( 'hidden', false );
		} else {
			$preview.attr( 'data-empty', 'true' ).html( '<span class="dashicons dashicons-format-image"></span>' );
			$remove.prop( 'hidden', true );
		}
	}

	/* ---------------------------------------------------------------
	 * Rellenar / limpiar el formulario
	 * ------------------------------------------------------------- */
	function resetForm() {
		$form[ 0 ].reset();
		$form.find( 'input[name="promo_id"]' ).val( 0 );
		$form.find( 'input[name="image_id"]' ).val( '' );
		setImagePreview( '' );
		$( '#pf-modal-title' ).text( 'Nueva promoción' );
	}

	function fillForm( data ) {
		resetForm();
		$form.find( 'input[name="promo_id"]' ).val( data.id );
		$( '#pf-modal-title' ).text( 'Editar promoción' );

		$form.find( 'input[name="image_id"]' ).val( data.image_id || '' );
		setImagePreview( data.image_url || data.thumb_url || '' );

		setVal( 'title', data.title );
		setVal( 'description', data.description );
		setVal( 'price', data.price );
		setVal( 'compare', data.compare );
		setVal( 'discount', data.discount );
		setVal( 'badge', data.badge );
		setVal( 'date_start', data.date_start );
		setVal( 'date_end', data.date_end );
		setVal( 'link_url', data.link_url );
		setVal( 'link_label', data.link_label );
	}

	function setVal( name, value ) {
		$form.find( '[name="' + name + '"]' ).val( value || '' );
	}

	/* ---------------------------------------------------------------
	 * Selector de imagen (WP Media)
	 * ------------------------------------------------------------- */
	$( document ).on( 'click', '.pf-choose-image', function ( e ) {
		e.preventDefault();

		if ( mediaFrame ) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media( {
			title: i18n.chooseImage || 'Elegir imagen',
			button: { text: i18n.useImage || 'Usar esta imagen' },
			library: { type: 'image' },
			multiple: false,
		} );

		mediaFrame.on( 'select', function () {
			var att = mediaFrame.state().get( 'selection' ).first().toJSON();
			var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$form.find( 'input[name="image_id"]' ).val( att.id );
			setImagePreview( url );
		} );

		mediaFrame.open();
	} );

	$( document ).on( 'click', '.pf-remove-image', function ( e ) {
		e.preventDefault();
		$form.find( 'input[name="image_id"]' ).val( '' );
		setImagePreview( '' );
	} );

	/* ---------------------------------------------------------------
	 * Abrir modal (nuevo / editar)
	 * ------------------------------------------------------------- */
	$( document ).on( 'click', '.pf-open-new', function ( e ) {
		e.preventDefault();
		resetForm();
		openModal();
	} );

	$( document ).on( 'click', '.pf-edit-promo', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		var id = $( this ).closest( '.pf-card' ).data( 'id' );
		if ( promos[ id ] ) {
			fillForm( promos[ id ] );
			openModal();
		}
	} );

	// Clic en la tarjeta (fuera de los botones) también edita.
	$( document ).on( 'click', '.pf-card[data-id]', function ( e ) {
		if ( $( e.target ).closest( '.pf-card__actions, .pf-card__drag' ).length ) {
			return;
		}
		var id = $( this ).data( 'id' );
		if ( promos[ id ] ) {
			fillForm( promos[ id ] );
			openModal();
		}
	} );

	$( document ).on( 'click', '.pf-modal__close, .pf-modal__backdrop', function ( e ) {
		e.preventDefault();
		closeModal();
	} );

	$( document ).on( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && ! $modal.attr( 'hidden' ) ) {
			closeModal();
		}
	} );

	/* ---------------------------------------------------------------
	 * Guardar promo
	 * ------------------------------------------------------------- */
	$( document ).on( 'click', '.pf-save-promo', function ( e ) {
		e.preventDefault();
		clearError();

		var $btn = $( this );
		var imageId = $form.find( 'input[name="image_id"]' ).val();
		var title = $form.find( '[name="title"]' ).val() || '';
		var desc = $form.find( '[name="description"]' ).val() || '';

		// Validación de obligatorios (espejo de la del servidor).
		if ( required.image && ! imageId ) {
			return showError( i18n.imageRequired || 'La imagen es obligatoria.' );
		}
		if ( required.title && ! title.trim() ) {
			return showError( i18n.fieldRequired || 'Este campo es obligatorio.' );
		}
		if ( required.description && ! desc.trim() ) {
			return showError( i18n.fieldRequired || 'Este campo es obligatorio.' );
		}

		var payload = $form.serializeArray();
		payload.push( { name: 'action', value: 'pf_save_promo' } );
		payload.push( { name: 'nonce', value: D.nonce } );

		$btn.addClass( 'is-loading' );

		$.post( D.ajaxUrl, $.param( payload ) )
			.done( function ( res ) {
				if ( ! res || ! res.success ) {
					showError( ( res && res.data && res.data.message ) || i18n.errorGeneric );
					return;
				}

				var id = res.data.id;
				var $newCard = $( res.data.card );

				var $existing = $( '.pf-card[data-id="' + id + '"]' );
				if ( $existing.length ) {
					$existing.replaceWith( $newCard );
				} else {
					$newCard.insertBefore( '.pf-card--add' );
				}

				// Actualizamos la caché local para poder reeditar.
				promos[ id ] = gatherFormData( id );

				refreshEmptyState();
				closeModal();
			} )
			.fail( function () {
				showError( i18n.errorGeneric || 'Error.' );
			} )
			.always( function () {
				$btn.removeClass( 'is-loading' );
			} );
	} );

	// Reconstruye el objeto de datos desde el formulario (para la caché local).
	function gatherFormData( id ) {
		var url = '';
		var $img = $form.find( '.pf-image-preview img' );
		if ( $img.length ) {
			url = $img.attr( 'src' );
		}
		return {
			id: id,
			title: $form.find( '[name="title"]' ).val() || '',
			description: $form.find( '[name="description"]' ).val() || '',
			image_id: $form.find( 'input[name="image_id"]' ).val() || 0,
			image_url: url,
			thumb_url: url,
			price: $form.find( '[name="price"]' ).val() || '',
			compare: $form.find( '[name="compare"]' ).val() || '',
			discount: $form.find( '[name="discount"]' ).val() || '',
			badge: $form.find( '[name="badge"]' ).val() || '',
			date_start: $form.find( '[name="date_start"]' ).val() || '',
			date_end: $form.find( '[name="date_end"]' ).val() || '',
			link_url: $form.find( '[name="link_url"]' ).val() || '',
			link_label: $form.find( '[name="link_label"]' ).val() || '',
		};
	}

	/* ---------------------------------------------------------------
	 * Eliminar promo
	 * ------------------------------------------------------------- */
	$( document ).on( 'click', '.pf-delete-promo', function ( e ) {
		e.preventDefault();
		e.stopPropagation();

		if ( ! window.confirm( i18n.confirmDelete || '¿Eliminar?' ) ) {
			return;
		}

		var $card = $( this ).closest( '.pf-card' );
		var id = $card.data( 'id' );

		$card.css( { opacity: 0.4, pointerEvents: 'none' } );

		$.post( D.ajaxUrl, {
			action: 'pf_delete_promo',
			nonce: D.nonce,
			promo_id: id,
		} )
			.done( function ( res ) {
				if ( res && res.success ) {
					$card.css( { transform: 'scale(0.9)', opacity: 0 } );
					setTimeout( function () {
						$card.remove();
						delete promos[ id ];
						refreshEmptyState();
					}, 200 );
				} else {
					$card.css( { opacity: 1, pointerEvents: '' } );
				}
			} )
			.fail( function () {
				$card.css( { opacity: 1, pointerEvents: '' } );
			} );
	} );

	/* ---------------------------------------------------------------
	 * Reordenar (drag & drop)
	 * ------------------------------------------------------------- */
	if ( $.fn.sortable ) {
		$( '#pf-grid' ).sortable( {
			items: '.pf-card[data-id]',
			handle: '.pf-card__drag',
			placeholder: 'pf-card pf-card--placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			cursor: 'grabbing',
			start: function ( e, ui ) {
				ui.item.addClass( 'pf-dragging' );
			},
			stop: function ( e, ui ) {
				ui.item.removeClass( 'pf-dragging' );
			},
			update: function () {
				var order = [];
				$( '#pf-grid .pf-card[data-id]' ).each( function () {
					order.push( $( this ).data( 'id' ) );
				} );
				$.post( D.ajaxUrl, {
					action: 'pf_reorder',
					nonce: D.nonce,
					order: order,
				} );
			},
		} );
	}

	/* ---------------------------------------------------------------
	 * Estado vacío
	 * ------------------------------------------------------------- */
	function refreshEmptyState() {
		var count = $( '#pf-grid .pf-card[data-id]' ).length;
		$( '.pf-empty' ).prop( 'hidden', count > 0 );
	}

	/* ---------------------------------------------------------------
	 * Ajustes: activar/desactivar campos
	 * ------------------------------------------------------------- */
	$( document ).on( 'change', '.pf-toggle-active', function () {
		var $row = $( this ).closest( '.pf-field-row' );
		var on = $( this ).is( ':checked' );
		$row.toggleClass( 'is-active', on );
		if ( ! on ) {
			$row.find( '.pf-required-wrap input' ).prop( 'checked', false );
		}
	} );
} )( jQuery );
