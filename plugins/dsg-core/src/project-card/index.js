/**
 * Project Card block: registers the block with the editor.
 *
 * The block's settings (name, attributes, supports) live in block.json.
 * Here we only connect the React components that draw it.
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import './editor.scss';

import Edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save,
} );
