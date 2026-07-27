/**
 * Promos Feed — carrusel del front (vanilla JS, sin dependencias).
 */
( function () {
	'use strict';

	function initCarousel( feed ) {
		var track = feed.querySelector( '.pf-track' );
		if ( ! track ) {
			return;
		}

		var items = Array.prototype.slice.call( track.children );
		if ( ! items.length ) {
			return;
		}

		var prev = feed.querySelector( '.pf-arrow--prev' );
		var next = feed.querySelector( '.pf-arrow--next' );
		var dotsWrap = feed.querySelector( '.pf-dots' );

		var autoplay = feed.getAttribute( 'data-autoplay' ) === '1';
		var speed = ( parseInt( feed.getAttribute( 'data-speed' ), 10 ) || 4 ) * 1000;
		var timer = null;

		function step() {
			var first = items[ 0 ];
			var gap = parseFloat( getComputedStyle( track ).columnGap || getComputedStyle( track ).gap || 0 ) || 0;
			return first.getBoundingClientRect().width + gap;
		}

		function scrollByDir( dir ) {
			track.scrollBy( { left: dir * step(), behavior: 'smooth' } );
		}

		// Puntos: uno por tarjeta.
		if ( dotsWrap ) {
			items.forEach( function ( item, index ) {
				var dot = document.createElement( 'button' );
				dot.type = 'button';
				dot.setAttribute( 'aria-label', 'Ir a la promoción ' + ( index + 1 ) );
				dot.addEventListener( 'click', function () {
					track.scrollTo( { left: index * step(), behavior: 'smooth' } );
				} );
				dotsWrap.appendChild( dot );
			} );
		}

		function currentIndex() {
			return Math.round( track.scrollLeft / step() );
		}

		function update() {
			var idx = currentIndex();
			if ( dotsWrap ) {
				Array.prototype.forEach.call( dotsWrap.children, function ( d, i ) {
					d.classList.toggle( 'is-active', i === idx );
				} );
			}
			var maxScroll = track.scrollWidth - track.clientWidth - 2;
			if ( prev ) {
				prev.disabled = track.scrollLeft <= 2;
			}
			if ( next ) {
				next.disabled = track.scrollLeft >= maxScroll;
			}
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				scrollByDir( -1 );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				scrollByDir( 1 );
			} );
		}

		track.addEventListener( 'scroll', function () {
			window.requestAnimationFrame( update );
		} );

		// Autoplay con pausa al pasar el ratón.
		function play() {
			if ( ! autoplay ) {
				return;
			}
			stop();
			timer = window.setInterval( function () {
				var maxScroll = track.scrollWidth - track.clientWidth - 2;
				if ( track.scrollLeft >= maxScroll ) {
					track.scrollTo( { left: 0, behavior: 'smooth' } );
				} else {
					scrollByDir( 1 );
				}
			}, speed );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		feed.addEventListener( 'mouseenter', stop );
		feed.addEventListener( 'mouseleave', play );

		update();
		play();
		window.addEventListener( 'resize', update );
	}

	function initAll() {
		var feeds = document.querySelectorAll( '.pf-feed--carousel' );
		Array.prototype.forEach.call( feeds, initCarousel );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
