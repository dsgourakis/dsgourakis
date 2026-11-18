/**
 * Jest unit tests. Run with: npm run test:js
 */
import { sortProjects, technologyNames } from '../utils';

describe( 'sortProjects', () => {
	const projects = [
		{ id: 1, menu_order: 0, date: '2025-01-01T00:00:00' },
		{ id: 2, menu_order: 0, date: '2026-01-01T00:00:00' },
		{ id: 3, menu_order: -1, date: '2024-01-01T00:00:00' },
		{ id: 4, menu_order: 5, date: '2026-06-01T00:00:00' },
	];

	it( 'sorts by Order first, then newest first', () => {
		expect(
			sortProjects( projects, 10 ).map( ( project ) => project.id )
		).toEqual( [ 3, 2, 1, 4 ] );
	} );

	it( 'keeps only `limit` projects', () => {
		expect( sortProjects( projects, 2 ) ).toHaveLength( 2 );
	} );

	it( 'does not change the original array', () => {
		const copy = [ ...projects ];
		sortProjects( projects, 10 );

		expect( projects ).toEqual( copy );
	} );
} );

describe( 'technologyNames', () => {
	const terms = [
		{ id: 1, name: 'PHP' },
		{ id: 2, name: 'React' },
	];

	it( 'maps IDs to names and skips unknown ones', () => {
		expect( technologyNames( [ 2, 99, 1 ], terms ) ).toEqual( [
			'React',
			'PHP',
		] );
	} );

	it( 'handles missing data', () => {
		expect( technologyNames( undefined, undefined ) ).toEqual( [] );
	} );
} );
