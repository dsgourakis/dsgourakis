/**
 * What the block looks like inside the editor.
 *
 * React concepts used here:
 * - props: WordPress passes `attributes` (the saved data) and `setAttributes` (to change it).
 * - state: `technologiesText` keeps what the user is typing until they leave the field.
 * - components: RichText, MediaUpload, InspectorControls are React components from WordPress.
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, Flex } from '@wordpress/components';

import { parseTechnologies, formatTechnologies } from './utils';

export default function Edit( { attributes, setAttributes } ) {
	const { title, summary, url, imageId, imageUrl, imageAlt, technologies } =
		attributes;

	// Local state: the raw text in the "Technologies" field while typing.
	const [ technologiesText, setTechnologiesText ] = useState(
		formatTechnologies( technologies )
	);

	const blockProps = useBlockProps( {
		className: 'wp-block-dsg-project-card',
	} );

	const onSelectImage = ( media ) => {
		setAttributes( {
			imageId: media.id,
			imageUrl: media.sizes?.large?.url ?? media.url,
			imageAlt: media.alt ?? '',
		} );
	};

	const onRemoveImage = () => {
		setAttributes( { imageId: undefined, imageUrl: '', imageAlt: '' } );
	};

	return (
		<>
			{ /* Sidebar settings, shown when the block is selected. */ }
			<InspectorControls>
				<PanelBody title={ __( 'Project settings', 'dsg-core' ) }>
					<Flex direction="column" gap={ 4 }>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Project link', 'dsg-core' ) }
							help={ __(
								'Page or external site the card links to.',
								'dsg-core'
							) }
							type="url"
							value={ url }
							onChange={ ( value ) =>
								setAttributes( { url: value } )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Technologies', 'dsg-core' ) }
							help={ __(
								'Comma separated, e.g. PHP, React, WooCommerce',
								'dsg-core'
							) }
							value={ technologiesText }
							onChange={ setTechnologiesText }
							onBlur={ () =>
								setAttributes( {
									technologies:
										parseTechnologies( technologiesText ),
								} )
							}
						/>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Image alt text', 'dsg-core' ) }
							help={ __(
								'Describe the image for screen reader users. Leave empty if it is decorative.',
								'dsg-core'
							) }
							value={ imageAlt }
							onChange={ ( value ) =>
								setAttributes( { imageAlt: value } )
							}
						/>
					</Flex>
				</PanelBody>
			</InspectorControls>

			<article { ...blockProps }>
				<MediaUploadCheck>
					<MediaUpload
						onSelect={ onSelectImage }
						allowedTypes={ [ 'image' ] }
						value={ imageId }
						render={ ( { open } ) => (
							<div className="wp-block-dsg-project-card__media">
								{ imageUrl ? (
									<>
										<img
											src={ imageUrl }
											alt={ imageAlt }
										/>
										<div className="wp-block-dsg-project-card__media-actions">
											<Button
												variant="secondary"
												onClick={ open }
											>
												{ __(
													'Replace image',
													'dsg-core'
												) }
											</Button>
											<Button
												variant="tertiary"
												isDestructive
												onClick={ onRemoveImage }
											>
												{ __( 'Remove', 'dsg-core' ) }
											</Button>
										</div>
									</>
								) : (
									<Button
										variant="secondary"
										onClick={ open }
									>
										{ __(
											'Add project image',
											'dsg-core'
										) }
									</Button>
								) }
							</div>
						) }
					/>
				</MediaUploadCheck>

				<div className="wp-block-dsg-project-card__body">
					<h3 className="wp-block-dsg-project-card__title">
						<RichText
							tagName="span"
							className="wp-block-dsg-project-card__title-text"
							value={ title }
							onChange={ ( value ) =>
								setAttributes( { title: value } )
							}
							placeholder={ __( 'Project name…', 'dsg-core' ) }
							allowedFormats={ [] }
						/>
					</h3>

					<RichText
						tagName="p"
						className="wp-block-dsg-project-card__summary"
						value={ summary }
						onChange={ ( value ) =>
							setAttributes( { summary: value } )
						}
						placeholder={ __(
							'What you built and the result…',
							'dsg-core'
						) }
					/>

					{ technologies.length > 0 && (
						<ul className="wp-block-dsg-project-card__tags">
							{ technologies.map( ( name ) => (
								<li key={ name }>{ name }</li>
							) ) }
						</ul>
					) }
				</div>
			</article>
		</>
	);
}
