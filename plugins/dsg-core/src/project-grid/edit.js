/**
 * What the block looks like inside the editor.
 *
 * React concepts used here:
 * - useSelect reads from the editor's data store (core-data). The first call
 *   triggers a REST request; when the data arrives the component re-renders.
 * - The preview uses the same class names as the Project Card's render.php,
 *   so the card styles (numbering, featured first card) apply here too.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	Placeholder,
	Spinner,
	Flex,
} from '@wordpress/components';

import { sortProjects, technologyNames } from './utils';

const POST_TYPE = 'dsg_project';
const TAXONOMY = 'dsg_technology';

// Enough for a portfolio. The editor sorts these itself (see utils.js).
const MAX_PROJECTS = 100;

/**
 * Turn the excerpt's HTML into plain text, like the card's summary.
 *
 * @param {string} html Rendered excerpt.
 * @return {string} Text only.
 */
function toText( html ) {
	const doc = new window.DOMParser().parseFromString( html, 'text/html' );
	return ( doc.body.textContent || '' ).trim();
}

/**
 * One card, matching the markup of project-card/render.php.
 *
 * @param {Object}   props
 * @param {Object}   props.project      Project record from the REST API.
 * @param {Object}   props.image        Featured image record, if any.
 * @param {string[]} props.technologies Technology names.
 */
function CardPreview( { project, image, technologies } ) {
	const imageUrl =
		image?.media_details?.sizes?.large?.source_url ?? image?.source_url;

	return (
		<article className="wp-block-dsg-project-card">
			{ imageUrl && (
				<div className="wp-block-dsg-project-card__media">
					<img src={ imageUrl } alt={ image.alt_text ?? '' } />
				</div>
			) }
			<div className="wp-block-dsg-project-card__body">
				<h3 className="wp-block-dsg-project-card__title">
					{ /* A real link, but clicking it in the editor does nothing. */ }
					<a
						href={ project.link }
						onClick={ ( event ) => event.preventDefault() }
					>
						{ decodeEntities( project.title.rendered ) ||
							__( '(no title)', 'dsg-core' ) }
					</a>
				</h3>
				{ project.excerpt?.rendered && (
					<p className="wp-block-dsg-project-card__summary">
						{ toText( project.excerpt.rendered ) }
					</p>
				) }
				{ technologies.length > 0 && (
					<ul className="wp-block-dsg-project-card__tags">
						{ technologies.map( ( name ) => (
							<li key={ name }>{ name }</li>
						) ) }
					</ul>
				) }
				<span
					className="wp-block-dsg-project-card__cta"
					aria-hidden="true"
				>
					{ __( 'Read case study', 'dsg-core' ) } →
				</span>
			</div>
		</article>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { numberOfItems, technology } = attributes;

	const blockProps = useBlockProps();

	// Everything the preview needs, read from the data store in one place.
	const { projects, terms, images, isLoading } = useSelect(
		( select ) => {
			const { getEntityRecords, getMedia, hasFinishedResolution } =
				select( coreStore );

			const query = {
				per_page: MAX_PROJECTS,
				status: 'publish',
				...( technology ? { [ TAXONOMY ]: technology } : {} ),
			};
			const termsQuery = { per_page: -1, hide_empty: false };

			const records = getEntityRecords( 'postType', POST_TYPE, query );
			const sorted = sortProjects( records ?? [], numberOfItems );

			// Featured images, keyed by Project ID. Each getMedia() call
			// fetches one image; undefined until it arrives.
			const media = {};
			sorted.forEach( ( project ) => {
				if ( project.featured_media ) {
					media[ project.id ] = getMedia( project.featured_media );
				}
			} );

			return {
				projects: sorted,
				terms: getEntityRecords( 'taxonomy', TAXONOMY, termsQuery ),
				images: media,
				isLoading: ! hasFinishedResolution( 'getEntityRecords', [
					'postType',
					POST_TYPE,
					query,
				] ),
			};
		},
		[ numberOfItems, technology ]
	);

	const technologyOptions = [
		{ label: __( 'All technologies', 'dsg-core' ), value: '0' },
		...( terms ?? [] ).map( ( term ) => ( {
			label: decodeEntities( term.name ),
			value: String( term.id ),
		} ) ),
	];

	let preview;

	if ( isLoading ) {
		preview = (
			<Placeholder label={ __( 'Project Grid', 'dsg-core' ) }>
				<Spinner />
			</Placeholder>
		);
	} else if ( projects.length === 0 ) {
		preview = (
			<Placeholder
				icon="screenoptions"
				label={ __( 'Project Grid', 'dsg-core' ) }
				instructions={ __(
					'No published Projects match these settings yet. Add some under Projects in the admin menu.',
					'dsg-core'
				) }
			/>
		);
	} else {
		preview = projects.map( ( project ) => (
			<CardPreview
				key={ project.id }
				project={ project }
				image={ images[ project.id ] }
				technologies={ technologyNames( project[ TAXONOMY ], terms ) }
			/>
		) );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Projects', 'dsg-core' ) }>
					<Flex direction="column" gap={ 4 }>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Number of projects', 'dsg-core' ) }
							value={ numberOfItems }
							onChange={ ( value ) =>
								setAttributes( { numberOfItems: value ?? 3 } )
							}
							min={ 1 }
							max={ 12 }
						/>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Technology', 'dsg-core' ) }
							help={ __(
								'Only show Projects tagged with this technology.',
								'dsg-core'
							) }
							value={ String( technology ) }
							options={ technologyOptions }
							onChange={ ( value ) =>
								setAttributes( { technology: Number( value ) } )
							}
						/>
					</Flex>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>{ preview }</div>
		</>
	);
}
