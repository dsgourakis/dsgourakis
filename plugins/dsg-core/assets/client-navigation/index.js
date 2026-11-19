/**
 * Client-side navigation + the sliding menu pill.
 *
 * Clicking an internal link no longer reloads the page. WordPress's router
 * (@wordpress/interactivity-router) fetches the next page and swaps only
 * <main> (marked as a router region in ClientNavigation.php). The header
 * stays on screen, so the menu pill can slide to the new link.
 *
 * The router does the hard parts: it loads the new page's CSS and script
 * modules, updates the title, keeps history working and tells screen
 * readers the page loaded. This file adds what it leaves out:
 * - scroll to the top, and move focus to the new page's heading;
 * - mark the current menu link and move the pill;
 * - close the mobile menu after a link in it is used.
 *
 * Anything unexpected falls back to a normal page load.
 */
import { isCurrentPath, isRoutableLink, shouldHandleClick } from './utils.js';

// "nav." matters: the Navigation block also copies its custom class onto
// the inner <ul>, and the pill lives in the <nav>, outside that list.
const PILL_NAV = 'nav.dsg-pill-nav';
const REGION = 'main[data-wp-router-region]';

// The latest navigation started; an older one that finishes later is ignored.
let latestHref = '';

/**
 * Load the router only when it's first needed (hover or click).
 *
 * @return {Promise<Object>} The router's actions.
 */
async function router() {
	const { actions } = await import( '@wordpress/interactivity-router' );
	return actions;
}

/* Menu: current link and pill ------------------------------------------ */

/**
 * Mark the menu links that point to the given page as current.
 *
 * @param {string} pagePath Path of the page being shown.
 */
function markCurrentLinks( pagePath ) {
	document
		.querySelectorAll( '.wp-block-navigation-item > a[href]' )
		.forEach( ( link ) => {
			const url = new URL( link.href, window.location.href );
			const current =
				url.origin === window.location.origin &&
				isCurrentPath( url.pathname, pagePath );

			link.parentElement.classList.toggle( 'current-menu-item', current );
			if ( current ) {
				link.setAttribute( 'aria-current', 'page' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
}

/**
 * Move a menu's pill under a link (or hide it when no link is current).
 *
 * The pill's position is passed to CSS as custom properties; the theme's
 * style.css does the drawing and the sliding transition.
 *
 * @param {HTMLElement}      nav     A pill menu.
 * @param {HTMLElement|null} link    The link to move under.
 * @param {boolean}          animate False to jump without sliding (first paint, resizes).
 */
function movePill( nav, link, animate = true ) {
	const pill = nav.querySelector( '.dsg-nav-pill' );
	if ( ! pill ) {
		return;
	}

	if ( ! link ) {
		pill.classList.remove( 'is-visible' );
		return;
	}

	// The pill is positioned inside the bar's border, so measure from there.
	const bar = pill.parentElement;
	const box = bar.getBoundingClientRect();
	const target = link.getBoundingClientRect();
	const x = target.left - box.left - bar.clientLeft;
	const y = target.top - box.top - bar.clientTop;

	pill.classList.toggle( 'is-animated', animate );
	pill.style.setProperty( '--pill-x', `${ x }px` );
	pill.style.setProperty( '--pill-y', `${ y }px` );
	pill.style.setProperty( '--pill-w', `${ target.width }px` );
	pill.style.setProperty( '--pill-h', `${ target.height }px` );
	pill.classList.add( 'is-visible' );
}

/**
 * Put every pill under its menu's current link.
 *
 * @param {boolean} animate False to jump without sliding.
 */
function placePills( animate ) {
	document.querySelectorAll( PILL_NAV ).forEach( ( nav ) => {
		movePill( nav, nav.querySelector( 'a[aria-current="page"]' ), animate );
	} );
}

/* Navigation ----------------------------------------------------------- */

/**
 * Close the mobile menu overlay if it's open.
 */
function closeMobileMenu() {
	document
		.querySelector(
			'.wp-block-navigation__responsive-container.is-menu-open .wp-block-navigation__responsive-container-close'
		)
		?.click();
}

/**
 * Move keyboard and screen reader focus to the new page's heading, so the
 * next Tab starts at the new content instead of somewhere in the old one.
 *
 * @param {HTMLElement} main The page's <main>.
 */
function focusNewContent( main ) {
	const target = main.querySelector( 'h1' ) || main;

	if ( ! target.hasAttribute( 'tabindex' ) ) {
		// Focusable by script only, not by Tab; removed again once focus leaves.
		target.setAttribute( 'tabindex', '-1' );
		target.addEventListener(
			'blur',
			() => target.removeAttribute( 'tabindex' ),
			{ once: true }
		);
	}

	target.focus( { preventScroll: true } );
}

/**
 * Replay the content's entrance animation (see style.css).
 *
 * @param {HTMLElement} main The page's <main>.
 */
function playEntrance( main ) {
	main.classList.remove( 'is-entering' );
	// Reading a layout value restarts the CSS animation.
	void main.offsetWidth; // eslint-disable-line no-void
	main.classList.add( 'is-entering' );
}

/**
 * Show another page of the site without a full reload.
 *
 * @param {string} href The page URL.
 */
async function navigate( href ) {
	const main = document.querySelector( REGION );
	if ( ! main ) {
		window.location.assign( href );
		return;
	}

	latestHref = href;
	main.setAttribute( 'aria-busy', 'true' );

	try {
		await ( await router() ).navigate( href );
	} catch {
		window.location.assign( href );
		return;
	} finally {
		main.removeAttribute( 'aria-busy' );
	}

	// Another link was clicked while this page was loading.
	if ( latestHref !== href ) {
		return;
	}

	markCurrentLinks( window.location.pathname );
	placePills( true );
	closeMobileMenu();

	// The router scrolls to a #section by itself; otherwise start at the top.
	if ( ! new URL( href, window.location.href ).hash ) {
		window.scrollTo( { top: 0, behavior: 'instant' } );
	}

	focusNewContent( main );
	playEntrance( main );
}

/* Events --------------------------------------------------------------- */

document.addEventListener( 'click', ( event ) => {
	const link = event.target.closest?.( 'a' );
	if ( ! shouldHandleClick( event, link ) ) {
		return;
	}

	event.preventDefault();

	// Slide the pill right away, while the page loads.
	const nav = link.closest( PILL_NAV );
	if ( nav ) {
		movePill( nav, link, true );
	}

	navigate( link.href );
} );

// Start downloading a page when the pointer or keyboard focus reaches its link.
const prefetch = async ( event ) => {
	const link = event.target.closest?.( 'a' );
	if ( isRoutableLink( link ) ) {
		( await router() ).prefetch( link.href );
	}
};
document.addEventListener( 'pointerover', prefetch );
document.addEventListener( 'focusin', prefetch );

// Back/forward: the router swaps the content; keep the menu in step.
window.addEventListener( 'popstate', () => {
	markCurrentLinks( window.location.pathname );
	placePills( true );
} );

// Place the pills without sliding: on load, once fonts change the link
// widths, and whenever the menu changes size.
placePills( false );
document.fonts?.ready.then( () => placePills( false ) );
const resizeObserver = new window.ResizeObserver( () => placePills( false ) );
document
	.querySelectorAll( `${ PILL_NAV } .wp-block-navigation__container` )
	.forEach( ( list ) => resizeObserver.observe( list ) );
