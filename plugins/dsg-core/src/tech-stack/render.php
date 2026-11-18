<?php
/**
 * Tech Stack: the frontend markup.
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * Accessibility: the chips are a real list, named by the label through
 * aria-labelledby, so screen readers announce "Stack, list, 5 items".
 *
 * @package DSG\Core
 */

$stack_label = $attributes['label'] ?? '';
$items       = array_filter( array_map( 'trim', (array) ( $attributes['items'] ?? array() ) ) );

// No chips: nothing worth showing.
if ( ! $items ) {
	return;
}

// Unique per block, in case the page has more than one Tech Stack.
$label_id = wp_unique_id( 'dsg-tech-stack-' );

// Adds the block's class plus any color/spacing chosen in the sidebar.
$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $stack_label ) : ?>
		<p class="wp-block-dsg-tech-stack__label" id="<?php echo esc_attr( $label_id ); ?>"><?php echo wp_kses_post( $stack_label ); ?></p>
		<ul class="wp-block-dsg-tech-stack__list" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
	<?php else : ?>
		<ul class="wp-block-dsg-tech-stack__list">
	<?php endif; ?>
		<?php foreach ( $items as $item ) : ?>
			<li><?php echo esc_html( $item ); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
