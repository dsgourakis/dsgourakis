/**
 * Pure helpers for client-side navigation. No DOM changes, no WordPress
 * imports, so they can be unit tested on their own (see test/utils.test.js).
 */

/**
 * Whether a menu link counts as current for a page.
 * Same rule as ClientNavigation::is_current_path() in PHP.
 *
 * "/" is only current on the homepage; any other link is also current on
 * pages below it ("/portfolio/" on "/portfolio/some-project/").
 *
 * @param {string} linkPath Path the link points to.
 * @param {string} pagePath Path of the page being shown.
 * @return {boolean} True when the link is current.
 */
export function isCurrentPath( linkPath, pagePath ) {
	const withSlash = ( path ) =>
		path.endsWith( '/' ) ? path : `${ path }/`;
	const link = withSlash( linkPath );
	const page = withSlash( pagePath );

	if ( link === '/' ) {
		return page === '/';
	}

	return page.startsWith( link );
}

/**
 * Whether a click on a link should be handled by the router instead of the
 * browser. Mirrors the rules of WordPress's own full-page router, plus a few
 * extra cases where a normal page load is the right thing to do.
 *
 * @param {Object}            event      The click event.
 * @param {HTMLAnchorElement} link       The clicked link (or null).
 * @param {Location}          [location] The current location.
 * @return {boolean} True when the router should take over.
 */
export function shouldHandleClick( event, link, location = window.location ) {
	// Only plain left clicks: with a modifier key, the visitor wants a new tab or a download.
	if (
		event.defaultPrevented ||
		event.button !== 0 ||
		event.metaKey ||
		event.ctrlKey ||
		event.shiftKey ||
		event.altKey
	) {
		return false;
	}

	return isRoutableLink( link, location );
}

/**
 * Whether a link points to a page of this site that the router can show.
 *
 * @param {HTMLAnchorElement} link       The link (or null).
 * @param {Location}          [location] The current location.
 * @return {boolean} True for internal page links.
 */
export function isRoutableLink( link, location = window.location ) {
	if ( ! link || ! link.href ) {
		return false;
	}

	const href = link.getAttribute( 'href' ) || '';
	const url = new URL( link.href, location.href );

	return (
		// Opens elsewhere, or is a file to download.
		( ! link.target || link.target === '_self' ) &&
		! link.hasAttribute( 'download' ) &&
		// Another site.
		url.origin === location.origin &&
		// Admin, login and anything with a security token.
		! url.pathname.startsWith( '/wp-admin' ) &&
		! url.pathname.startsWith( '/wp-login.php' ) &&
		! url.searchParams.has( '_wpnonce' ) &&
		// A jump within this page ("#work"): the browser scrolls, no new page needed.
		! href.startsWith( '#' ) &&
		! isSamePageAnchor( url, location ) &&
		// Media and other files: not pages.
		! /\.(pdf|zip|jpe?g|png|gif|webp|avif|svg|mp4|mp3|docx?|xlsx?)$/i.test(
			url.pathname
		)
	);
}

/**
 * Whether a URL is the current page plus a #section, like "/#work" on the homepage.
 *
 * @param {URL}      url      The link's URL.
 * @param {Location} location The current location.
 * @return {boolean} True when only the hash differs.
 */
function isSamePageAnchor( url, location ) {
	return (
		url.hash !== '' &&
		url.pathname === location.pathname &&
		url.search === location.search
	);
}
