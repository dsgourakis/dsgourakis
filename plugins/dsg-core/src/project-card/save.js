/**
 * The HTML saved into the post content and shown on the frontend.
 *
 * Accessibility notes:
 * - The whole card is one <article>, and only the title is a link
 *   (no nested or repeated links for screen readers).
 * - The technology list is a real <ul> so it is announced as a list.
 * - The image uses the alt text set in the sidebar ("" means decorative).
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { title, summary, url, imageUrl, imageAlt, technologies } =
		attributes;

	const blockProps = useBlockProps.save( {
		className: 'wp-block-dsg-project-card',
	} );

	const titleText = (
		<RichText.Content
			tagName="span"
			className="wp-block-dsg-project-card__title-text"
			value={ title }
		/>
	);

	return (
		<article { ...blockProps }>
			{ imageUrl && (
				<div className="wp-block-dsg-project-card__media">
					<img src={ imageUrl } alt={ imageAlt } loading="lazy" />
				</div>
			) }

			<div className="wp-block-dsg-project-card__body">
				<h3 className="wp-block-dsg-project-card__title">
					{ url ? <a href={ url }>{ titleText }</a> : titleText }
				</h3>

				<RichText.Content
					tagName="p"
					className="wp-block-dsg-project-card__summary"
					value={ summary }
				/>

				{ technologies.length > 0 && (
					<ul
						className="wp-block-dsg-project-card__tags"
						aria-label={ __( 'Technologies used', 'dsg-core' ) }
					>
						{ technologies.map( ( name ) => (
							<li key={ name }>{ name }</li>
						) ) }
					</ul>
				) }
			</div>
		</article>
	);
}
