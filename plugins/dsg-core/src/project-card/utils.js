/**
 * Small pure helpers. Pure functions (same input → same output, no side effects)
 * are the easiest code to test, so they live in their own file.
 */

/**
 * Turn "PHP, React ,  , WooCommerce" into [ 'PHP', 'React', 'WooCommerce' ].
 *
 * @param {string} value Comma-separated text from the sidebar input.
 * @return {string[]} Trimmed, non-empty, unique technology names.
 */
export function parseTechnologies( value ) {
	const names = value
		.split( ',' )
		.map( ( name ) => name.trim() )
		.filter( ( name ) => name.length > 0 );

	return [ ...new Set( names ) ];
}

/**
 * Turn [ 'PHP', 'React' ] back into "PHP, React" for the sidebar input.
 *
 * @param {string[]} technologies Technology names.
 * @return {string} Comma-separated text.
 */
export function formatTechnologies( technologies ) {
	return technologies.join( ', ' );
}
