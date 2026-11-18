/**
 * Pure helpers for the editor preview, kept apart so Jest can test them.
 */

/**
 * Sort Projects the same way render.php does: by the Order field
 * (menu_order, lowest first), then newest first.
 *
 * The REST API can only sort by one field, so the editor fetches the
 * Projects and sorts them here to match the frontend exactly.
 *
 * @param {Object[]} projects Project records from the REST API.
 * @param {number}   limit    How many to keep.
 * @return {Object[]} A new, sorted and trimmed array.
 */
export function sortProjects( projects, limit ) {
	return [ ...projects ]
		.sort(
			( a, b ) =>
				( a.menu_order ?? 0 ) - ( b.menu_order ?? 0 ) ||
				new Date( b.date ) - new Date( a.date )
		)
		.slice( 0, limit );
}

/**
 * Look up technology names for a Project's term IDs.
 *
 * @param {number[]} termIds Term IDs stored on the Project.
 * @param {Object[]} terms   All Technology terms ({ id, name }).
 * @return {string[]} Names, in the order of termIds. Unknown IDs are skipped.
 */
export function technologyNames( termIds = [], terms = [] ) {
	return termIds
		.map( ( id ) => terms.find( ( term ) => term.id === id )?.name )
		.filter( Boolean );
}
