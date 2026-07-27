=== Promos Feed ===
Contributors: Santiago Camacho
Author: Santiago Camacho
Author URI: https://santiagocamachomkt.com
Plugin URI: https://github.com/Dsantycam/promos-feed
Tags: promociones, ofertas, feed, carrusel, bloque, gutenberg
Requires at least: 6.1
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestiona promociones de forma simple y bonita, y muéstralas con un bloque de feed totalmente personalizable. Por Santiago Camacho.

== Description ==

Promos Feed es un plugin ligero para gestionar promociones y mostrarlas donde quieras con un bloque de Gutenberg muy personalizable.

**Panel bonito y simple**
* Añade, edita, elimina y reordena (arrastrando) tus promociones sin recargar la página.
* Sube la imagen desde la biblioteca de medios de WordPress.
* Guardado sólido por debajo (usa un tipo de contenido oculto), interfaz a medida por fuera.

**Campos configurables**
* Desde Ajustes eliges qué campos pide una promoción: Imagen, Título, Descripción, Precio/Descuento, Etiqueta (badge), Vigencia (fechas) y Enlace/CTA.
* Cada campo puede marcarse como activo y/o obligatorio.
* Por defecto solo viene activa la Imagen.

**Bloque de feed muy personalizable**
* Tipos de feed: Cuadrícula (Grid), Carrusel, Destacado (Hero), Lista y Mosaico (Masonry).
* Controla: cuántas mostrar, columnas, espacio, redondeo, sombra, colores (acento/fondo/texto), proporción de imagen y orden (manual/recientes/aleatorio).
* Muestra u oculta cada campo activo.
* El carrusel admite reproducción automática, flechas y puntos.
* Las promos con fecha de fin caducada se ocultan solas del feed.

**Sin compilar**
* No necesitas Node ni npm. Copias la carpeta, activas y funciona.

== Installation ==

1. Copia la carpeta `promos-feed` en `wp-content/plugins/`.
2. Activa "Promos Feed" en Plugins.
3. Ve a "Promos Feed" en el menú lateral para añadir promociones.
4. (Opcional) Entra en "Promos Feed → Ajustes" para elegir qué campos pide cada promoción.
5. Edita cualquier página o entrada, añade el bloque "Promos Feed" y personalízalo a tu gusto.

== Frequently Asked Questions ==

= ¿Dónde se guardan las promociones? =
En un tipo de contenido oculto de WordPress, así que son seguras y compatibles con el resto del sistema, pero se gestionan desde un panel propio mucho más simple.

= ¿Puedo añadir más campos en el futuro? =
Sí. El plugin ya incluye varios campos; solo tienes que activarlos en Ajustes y aparecerán tanto al crear promos como en el bloque.

= ¿Cómo se actualiza el plugin? =
Automáticamente. Cada vez que se publica una nueva versión en GitHub, WordPress la detecta y podrás actualizar con un clic desde Plugins o Escritorio → Actualizaciones, sin volver a subir el archivo.

== Changelog ==

= 1.0.0 =
* Versión inicial: panel de gestión a medida, campos configurables y bloque de feed con 5 layouts.
