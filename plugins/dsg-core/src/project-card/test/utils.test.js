/**
 * Jest unit tests. Run with: npm run test:js
 */
import { parseTechnologies, formatTechnologies } from '../utils';

describe( 'parseTechnologies', () => {
	it( 'splits on commas and trims spaces', () => {
		expect( parseTechnologies( 'PHP, React ,WooCommerce' ) ).toEqual( [
			'PHP',
			'React',
			'WooCommerce',
		] );
	} );

	it( 'drops empty entries', () => {
		expect( parseTechnologies( 'PHP, , ,React,' ) ).toEqual( [
			'PHP',
			'React',
		] );
	} );

	it( 'removes duplicates', () => {
		expect( parseTechnologies( 'PHP, PHP, React' ) ).toEqual( [
			'PHP',
			'React',
		] );
	} );

	it( 'returns an empty array for empty input', () => {
		expect( parseTechnologies( '' ) ).toEqual( [] );
	} );
} );

describe( 'formatTechnologies', () => {
	it( 'joins names with a comma and space', () => {
		expect( formatTechnologies( [ 'PHP', 'React' ] ) ).toBe( 'PHP, React' );
	} );
} );
