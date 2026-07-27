/**
 * Bloque "Promos Feed" — editor (JS nativo, sin JSX ni build).
 *
 * Usa ServerSideRender para que la vista previa sea idéntica al front.
 */
( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;

	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;

	var cmp = wp.components;
	var PanelBody = cmp.PanelBody;
	var RangeControl = cmp.RangeControl;
	var SelectControl = cmp.SelectControl;
	var ToggleControl = cmp.ToggleControl;
	var Notice = cmp.Notice;

	var ServerSideRender = wp.serverSideRender;

	var activeFields = ( window.pfBlock && window.pfBlock.activeFields ) || [];

	function isActive( field ) {
		return activeFields.indexOf( field ) !== -1;
	}

	/**
	 * Devuelve un ToggleControl para un campo, solo si ese campo está activo.
	 */
	function fieldToggle( field, label, attributes, setAttributes ) {
		if ( ! isActive( field ) ) {
			return null;
		}
		var attr = 'show' + field.charAt( 0 ).toUpperCase() + field.slice( 1 );
		return el( ToggleControl, {
			label: label,
			checked: !! attributes[ attr ],
			onChange: function ( v ) {
				var o = {};
				o[ attr ] = v;
				setAttributes( o );
			},
		} );
	}

	registerBlockType( 'promos-feed/feed', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var showColumns = [ 'grid', 'masonry', 'carousel' ].indexOf( attributes.layout ) !== -1;
			var isCarousel = attributes.layout === 'carousel';
			var isFeatured = attributes.layout === 'featured';

			var inspector = el(
				InspectorControls,
				{},

				// --- Panel: Diseño del feed ---
				el(
					PanelBody,
					{ title: __( 'Diseño del feed', 'promos-feed' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Tipo de feed', 'promos-feed' ),
						value: attributes.layout,
						options: [
							{ label: __( 'Cuadrícula (Grid)', 'promos-feed' ), value: 'grid' },
							{ label: __( 'Carrusel', 'promos-feed' ), value: 'carousel' },
							{ label: __( 'Destacado (Hero)', 'promos-feed' ), value: 'featured' },
							{ label: __( 'Lista', 'promos-feed' ), value: 'list' },
							{ label: __( 'Mosaico (Masonry)', 'promos-feed' ), value: 'masonry' },
						],
						onChange: function ( v ) {
							setAttributes( { layout: v } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Cuántas mostrar', 'promos-feed' ),
						value: attributes.count,
						min: 1,
						max: 24,
						onChange: function ( v ) {
							setAttributes( { count: v } );
						},
					} ),
					showColumns
						? el( RangeControl, {
								label: __( 'Columnas', 'promos-feed' ),
								value: attributes.columns,
								min: 1,
								max: 6,
								onChange: function ( v ) {
									setAttributes( { columns: v } );
								},
						  } )
						: null,
					el( SelectControl, {
						label: __( 'Proporción de imagen', 'promos-feed' ),
						value: attributes.imageRatio,
						options: [
							{ label: __( 'Cuadrada (1:1)', 'promos-feed' ), value: '1/1' },
							{ label: __( 'Horizontal (4:3)', 'promos-feed' ), value: '4/3' },
							{ label: __( 'Panorámica (16:9)', 'promos-feed' ), value: '16/9' },
							{ label: __( 'Vertical (3:4)', 'promos-feed' ), value: '3/4' },
							{ label: __( 'Automática', 'promos-feed' ), value: 'auto' },
						],
						onChange: function ( v ) {
							setAttributes( { imageRatio: v } );
						},
					} ),
					isActive( 'image' )
						? el( SelectControl, {
								label: __( 'Calidad de imagen', 'promos-feed' ),
								value: attributes.imageSize,
								help: __( 'Usa "Completa" o "Grande" para que se vean nítidas.', 'promos-feed' ),
								options: [
									{ label: __( 'Completa (máxima calidad)', 'promos-feed' ), value: 'full' },
									{ label: __( 'Grande', 'promos-feed' ), value: 'large' },
									{ label: __( 'Mediana', 'promos-feed' ), value: 'medium' },
									{ label: __( 'Miniatura (más ligera)', 'promos-feed' ), value: 'thumbnail' },
								],
								onChange: function ( v ) {
									setAttributes( { imageSize: v } );
								},
						  } )
						: null,
					el( SelectControl, {
						label: __( 'Orden', 'promos-feed' ),
						value: attributes.order,
						options: [
							{ label: __( 'Manual (mi orden)', 'promos-feed' ), value: 'menu_order' },
							{ label: __( 'Más recientes', 'promos-feed' ), value: 'recent' },
							{ label: __( 'Aleatorio', 'promos-feed' ), value: 'random' },
						],
						onChange: function ( v ) {
							setAttributes( { order: v } );
						},
					} )
				),

				// --- Panel: Estilo ---
				el(
					PanelBody,
					{ title: __( 'Estilo', 'promos-feed' ), initialOpen: false },
					el( RangeControl, {
						label: __( 'Espacio entre tarjetas', 'promos-feed' ),
						value: attributes.gap,
						min: 0,
						max: 64,
						onChange: function ( v ) {
							setAttributes( { gap: v } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Redondeo de esquinas', 'promos-feed' ),
						value: attributes.radius,
						min: 0,
						max: 40,
						onChange: function ( v ) {
							setAttributes( { radius: v } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Sombra', 'promos-feed' ),
						checked: !! attributes.shadow,
						onChange: function ( v ) {
							setAttributes( { shadow: v } );
						},
					} )
				),

				// --- Panel: Colores ---
				el( PanelColorSettings, {
					title: __( 'Colores', 'promos-feed' ),
					initialOpen: false,
					colorSettings: [
						{
							value: attributes.accentColor,
							onChange: function ( v ) {
								setAttributes( { accentColor: v || '#e11d48' } );
							},
							label: __( 'Acento (badges, botones)', 'promos-feed' ),
						},
						{
							value: attributes.bgColor,
							onChange: function ( v ) {
								setAttributes( { bgColor: v || '#ffffff' } );
							},
							label: __( 'Fondo de tarjeta', 'promos-feed' ),
						},
						{
							value: attributes.textColor,
							onChange: function ( v ) {
								setAttributes( { textColor: v || '#111827' } );
							},
							label: __( 'Texto', 'promos-feed' ),
						},
					],
				} ),

				// --- Panel: Contenido visible ---
				el(
					PanelBody,
					{ title: __( 'Contenido visible', 'promos-feed' ), initialOpen: false },
					activeFields.length === 0
						? el(
								Notice,
								{ status: 'warning', isDismissible: false },
								__( 'No hay campos activos. Actívalos en Promos Feed → Ajustes.', 'promos-feed' )
						  )
						: null,
					fieldToggle( 'image', __( 'Imagen', 'promos-feed' ), attributes, setAttributes ),
					fieldToggle( 'title', __( 'Título', 'promos-feed' ), attributes, setAttributes ),
					fieldToggle( 'description', __( 'Descripción', 'promos-feed' ), attributes, setAttributes ),
					fieldToggle( 'price', __( 'Precio / Descuento', 'promos-feed' ), attributes, setAttributes ),
					fieldToggle( 'badge', __( 'Etiqueta (Badge)', 'promos-feed' ), attributes, setAttributes ),
					fieldToggle( 'dates', __( 'Vigencia (fechas)', 'promos-feed' ), attributes, setAttributes ),
					fieldToggle( 'link', __( 'Botón / Enlace', 'promos-feed' ), attributes, setAttributes )
				),

				// --- Panel: Carrusel (condicional) ---
				isCarousel
					? el(
							PanelBody,
							{ title: __( 'Opciones de carrusel', 'promos-feed' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Reproducción automática', 'promos-feed' ),
								checked: !! attributes.autoplay,
								onChange: function ( v ) {
									setAttributes( { autoplay: v } );
								},
							} ),
							attributes.autoplay
								? el( RangeControl, {
										label: __( 'Segundos por diapositiva', 'promos-feed' ),
										value: attributes.autoplaySpeed,
										min: 1,
										max: 15,
										onChange: function ( v ) {
											setAttributes( { autoplaySpeed: v } );
										},
								  } )
								: null,
							el( ToggleControl, {
								label: __( 'Mostrar flechas', 'promos-feed' ),
								checked: !! attributes.showArrows,
								onChange: function ( v ) {
									setAttributes( { showArrows: v } );
								},
							} ),
							el( ToggleControl, {
								label: __( 'Mostrar puntos', 'promos-feed' ),
								checked: !! attributes.showDots,
								onChange: function ( v ) {
									setAttributes( { showDots: v } );
								},
							} )
					  )
					: null,

				// --- Panel: Destacado (condicional) ---
				isFeatured
					? el(
							PanelBody,
							{ title: __( 'Opciones de destacado', 'promos-feed' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Primera promo a lo grande', 'promos-feed' ),
								checked: !! attributes.featuredBig,
								onChange: function ( v ) {
									setAttributes( { featuredBig: v } );
								},
							} )
					  )
					: null
			);

			var preview = el(
				'div',
				blockProps,
				el( ServerSideRender, {
					block: 'promos-feed/feed',
					attributes: attributes,
				} )
			);

			return el( Fragment, {}, inspector, preview );
		},

		save: function () {
			// Bloque dinámico: el render lo hace PHP.
			return null;
		},
	} );
} )( window.wp );
