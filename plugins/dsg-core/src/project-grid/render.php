<?php
/**
 * Project Grid: the frontend markup.
 *
 * Queries published Projects and prints each one as a Project Card block,
 * so the grid reuses the card's markup, styles, numbering and featured
 * layout instead of duplicating them.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * @package DSG\Core
 */

use DSG\Core\PostTypes\Project;

$number_of_items = max( 1, min( 12, (int) ( $attributes['numberOfItems'] ?? 3 ) ) );
$technology      = (int) ( $attributes['technology'] ?? 0 );

$query_args = array(
	'post_type'           => Project::POST_TYPE,
	'post_status'         => 'publish',
	'posts_per_page'      => $number_of_items,
	// The "Order" field first (lowest first), then newest first.
	'orderby'             => array(
		'menu_order' => 'ASC',
		'date'       => 'DESC',
	),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true, // No pagination, so skip counting all rows.
);

if ( $technology ) {
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Small, filtered list of Projects.
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => Project::TAXONOMY,
			'field'    => 'term_id',
			'terms'    => $technology,
		),
	);
}

$projects = get_posts( $query_args );

// No Projects: print nothing rather than an empty grid.
if ( ! $projects ) {
	return;
}

$cards = '';

foreach ( $projects as $project ) {
	$image_id     = (int) get_post_thumbnail_id( $project );
	$technologies = wp_get_post_terms( $project->ID, Project::TAXONOMY, array( 'fields' => 'names' ) );

	// render_block() runs the Project Card's render.php, which escapes everything.
	$cards .= render_block(
		array(
			'blockName'    => 'dsg/project-card',
			'attrs'        => array(
				'title'        => get_the_title( $project ),
				'summary'      => get_the_excerpt( $project ),
				'url'          => get_permalink( $project ),
				'imageId'      => $image_id,
				'imageAlt'     => $image_id ? (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
				'technologies' => is_wp_error( $technologies ) ? array() : $technologies,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
}

// Adds the block's class, alignment and any margin from the sidebar.
$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php
	// Each card's render.php already escaped its own output.
	echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
