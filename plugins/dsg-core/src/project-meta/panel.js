/**
 * "Project details" panel in the Project editor's sidebar.
 *
 * Saves straight to the post's meta (registered with show_in_rest in
 * Project.php), so the values are stored when the Project is saved.
 * Only shown when editing a Project.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { TextControl } from '@wordpress/components';

import { FIELDS } from './fields';

const POST_TYPE = 'dsg_project';

function ProjectDetailsFields() {
	const [ meta, setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta' );

	return FIELDS.map( ( { key, label, type } ) => (
		<TextControl
			key={ key }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			type={ type ?? 'text' }
			value={ meta?.[ key ] ?? '' }
			placeholder={ type === 'url' ? 'https://' : '' }
			onChange={ ( value ) => setMeta( { ...meta, [ key ]: value } ) }
		/>
	) );
}

export default function ProjectDetailsPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' )?.getCurrentPostType(),
		[]
	);

	if ( postType !== POST_TYPE ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="dsg-project-details"
			title={ __( 'Project details', 'dsg-core' ) }
		>
			<div style={ { display: 'grid', gap: '16px' } }>
				<ProjectDetailsFields />
			</div>
		</PluginDocumentSettingPanel>
	);
}
