/**
 * Project Grid: the technology filter's scrolling bar.
 *
 * The chips sit on one line and scroll sideways when they don't fit
 * (see style.scss). This script acts only on the filter's <ul> (its
 * data-wp-* directives in render.php), so grids without a filter skip it:
 * - fades the edges that have more chips out of view;
 * - scrolls the current chip into view when the page loads, so a shared
 *   ?tech= link shows which filter is on;
 * - keeps the bar where it was when a chip is clicked. Client-side
 *   navigation replaces the whole bar with the new page's, which would
 *   otherwise jump back to the start.
 *
 * Without it, the bar still scrolls; it just has no fades.
 */
import { store, getElement } from '@wordpress/interactivity';

import { hiddenEdges, scrollToReveal } from './utils.js';

// Where the bar was scrolled to, carried over to the next page's bar.
let lastScrollLeft = 0;

/**
 * Show or hide the fade on each edge.
 *
 * @param {HTMLElement} list The scrolling <ul>.
 */
function updateFades( list ) {
	const edges = hiddenEdges(
		list.scrollLeft,
		list.clientWidth,
		list.scrollWidth
	);
	list.classList.toggle( 'has-more-start', edges.start );
	list.classList.toggle( 'has-more-end', edges.end );
}

store( 'dsg/project-grid', {
	callbacks: {
		/**
		 * When the bar appears (page load or after filtering): restore the
		 * scroll position, bring the current chip into view, then keep the
		 * fades in step with the bar's size.
		 *
		 * @return {Function} Cleanup, run when the bar is replaced.
		 */
		initFilter() {
			const { ref: list } = getElement();
			const fade =
				parseFloat(
					window.getComputedStyle( list ).scrollPaddingLeft
				) || 0;
			const current = list.querySelector( '[aria-current="true"]' );

			list.scrollLeft = lastScrollLeft;
			if ( current ) {
				list.scrollLeft = scrollToReveal(
					list,
					{ left: current.offsetLeft, width: current.offsetWidth },
					fade
				);
			}
			lastScrollLeft = list.scrollLeft;
			updateFades( list );

			// Window resizes and web fonts loading both change what fits.
			const observer = new window.ResizeObserver( () =>
				updateFades( list )
			);
			observer.observe( list );
			[ ...list.children ].forEach( ( chip ) =>
				observer.observe( chip )
			);

			return () => observer.disconnect();
		},

		/**
		 * On scroll: update the fades and remember the position.
		 */
		onScroll() {
			const { ref: list } = getElement();
			lastScrollLeft = list.scrollLeft;
			updateFades( list );
		},
	},
} );
