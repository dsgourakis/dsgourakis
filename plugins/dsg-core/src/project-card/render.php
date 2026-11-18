<?php
/**
 * Project Card: the frontend markup.
 *
 * Dynamic block: WordPress runs this file every time the block is shown,
 * so changing the markup here updates every card on the site at once.
 * The post only stores the data: <!-- wp:dsg/project-card {"title":"…"} /-->
 *
 * Available variables (WordPress provides them):
 * - $attributes: the block's data, as defined in block.json
 * - $content:    inner blocks' HTML (unused here)
 * - $block:      the WP_Block instance
 *
 * Accessibility:
 * - The whole card is one <article>, and only the title is a link.
 *   CSS stretches that link over the card, so there are no repeated links.
 * - "Read case study" is aria-hidden: the title link already says where it goes.
 *
 * The counter (01, 02…) and the featured layout of the first card come from CSS.
 *
 * @package DSG\Core
 */

$card_title   = $attributes['title'] ?? '';
$summary      = $attributes['summary'] ?? '';
$url          = $attributes['url'] ?? '';
$image_id     = $attributes['imageId'] ?? 0;
$image_url    = $attributes['imageUrl'] ?? '';
$image_alt    = $attributes['imageAlt'] ?? '';
$technologies = $attributes['technologies'] ?? array();

// Adds the block's class plus any color/spacing chosen in the sidebar.
$wrapper_attributes = get_block_wrapper_attributes();
?>
<article <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $image_id || $image_url ) : ?>
		<div class="wp-block-dsg-project-card__media">
			<?php
			if ( $image_id ) {
				// Outputs srcset/sizes too, so browsers download the right size.
				echo wp_get_attachment_image(
					$image_id,
					'large',
					false,
					array(
						'alt'     => $image_alt,
						'loading' => 'lazy',
					)
				);
			} else {
				printf( '<img src="%s" alt="%s" loading="lazy" />', esc_url( $image_url ), esc_attr( $image_alt ) );
			}
			?>
		</div>
	<?php endif; ?>

	<div class="wp-block-dsg-project-card__body">
		<h3 class="wp-block-dsg-project-card__title">
			<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo wp_kses_post( $card_title ); ?></a>
			<?php else : ?>
				<?php echo wp_kses_post( $card_title ); ?>
			<?php endif; ?>
		</h3>

		<?php if ( $summary ) : ?>
			<p class="wp-block-dsg-project-card__summary"><?php echo wp_kses_post( $summary ); ?></p>
		<?php endif; ?>

		<?php if ( $technologies ) : ?>
			<ul class="wp-block-dsg-project-card__tags" aria-label="<?php esc_attr_e( 'Technologies used', 'dsg-core' ); ?>">
				<?php foreach ( $technologies as $technology ) : ?>
					<li><?php echo esc_html( $technology ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $url ) : ?>
			<span class="wp-block-dsg-project-card__cta" aria-hidden="true"><?php esc_html_e( 'Read case study', 'dsg-core' ); ?> →</span>
		<?php endif; ?>
	</div>
</article>
