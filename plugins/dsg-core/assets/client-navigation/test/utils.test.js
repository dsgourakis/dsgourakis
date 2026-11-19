import { isCurrentPath, isRoutableLink, shouldHandleClick } from '../utils';

// A fake current page, so the tests don't depend on jsdom's own URL.
const location = new URL( 'https://example.com/portfolio/' );

/**
 * Build a link element.
 *
 * @param {string} href       The href attribute.
 * @param {Object} attributes Extra attributes.
 * @return {HTMLAnchorElement} The link.
 */
function link( href, attributes = {} ) {
	const a = document.createElement( 'a' );
	a.setAttribute( 'href', new URL( href, location.href ).href );
	// Keep the original href for "#" checks, like a real link would.
	if ( href.startsWith( '#' ) ) {
		a.setAttribute( 'href', href );
	}
	Object.entries( attributes ).forEach( ( [ name, value ] ) =>
		a.setAttribute( name, value )
	);
	return a;
}

const click = ( overrides = {} ) => ( {
	defaultPrevented: false,
	button: 0,
	metaKey: false,
	ctrlKey: false,
	shiftKey: false,
	altKey: false,
	...overrides,
} );

describe( 'isCurrentPath', () => {
	it( 'treats "/" as current only on the homepage', () => {
		expect( isCurrentPath( '/', '/' ) ).toBe( true );
		expect( isCurrentPath( '/', '/about/' ) ).toBe( false );
	} );

	it( 'treats a section link as current on its pages too', () => {
		expect( isCurrentPath( '/portfolio/', '/portfolio/' ) ).toBe( true );
		expect( isCurrentPath( '/portfolio', '/portfolio/golden-star/' ) ).toBe(
			true
		);
	} );

	it( 'does not match a page that only starts with the same letters', () => {
		expect( isCurrentPath( '/about/', '/about-me/' ) ).toBe( false );
	} );
} );

describe( 'isRoutableLink', () => {
	it( 'accepts internal page links', () => {
		expect( isRoutableLink( link( '/about/' ), location ) ).toBe( true );
	} );

	it.each( [
		[ 'another site', link( 'https://github.com/' ) ],
		[ 'a new tab', link( '/about/', { target: '_blank' } ) ],
		[ 'a download', link( '/about/', { download: '' } ) ],
		[ 'wp-admin', link( '/wp-admin/edit.php' ) ],
		[ 'the login page', link( '/wp-login.php' ) ],
		[ 'a nonce link', link( '/?action=logout&_wpnonce=abc' ) ],
		[ 'a hash link', link( '#work' ) ],
		[ 'a section of the same page', link( '/portfolio/#work' ) ],
		[ 'a PDF', link( '/wp-content/uploads/resume.pdf' ) ],
		[ 'no link', null ],
	] )( 'leaves %s to the browser', ( _, a ) => {
		expect( isRoutableLink( a, location ) ).toBe( false );
	} );

	it( 'handles a section on another page', () => {
		expect( isRoutableLink( link( '/#work' ), location ) ).toBe( true );
	} );
} );

describe( 'shouldHandleClick', () => {
	it( 'handles a plain left click', () => {
		expect(
			shouldHandleClick( click(), link( '/about/' ), location )
		).toBe( true );
	} );

	it.each( [
		[ 'a middle click', { button: 1 } ],
		[ 'Ctrl+click', { ctrlKey: true } ],
		[ 'Cmd+click', { metaKey: true } ],
		[ 'Shift+click', { shiftKey: true } ],
		[
			'a click another script already handled',
			{ defaultPrevented: true },
		],
	] )( 'ignores %s', ( _, overrides ) => {
		expect(
			shouldHandleClick( click( overrides ), link( '/about/' ), location )
		).toBe( false );
	} );
} );
