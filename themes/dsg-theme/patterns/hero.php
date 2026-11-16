<?php
/**
 * Title: Hero
 * Slug: dsg-theme/hero
 * Categories: banner
 * Description: Introduction with name, role and call to action.
 *
 * @package DSG\Theme
 */

?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","justifyContent":"left","contentSize":"760px"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"small"} -->
	<p class="has-accent-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.08em;text-transform:uppercase"><?php esc_html_e( 'Senior WordPress Developer', 'dsg-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading"><?php esc_html_e( 'Accessible, fast WordPress and WooCommerce sites.', 'dsg-theme' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textColor":"muted","fontSize":"large"} -->
	<p class="has-muted-color has-text-color has-large-font-size"><?php esc_html_e( 'I build custom block themes and plugins with a focus on WCAG compliance, performance and clean, tested code.', 'dsg-theme' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/portfolio/"><?php esc_html_e( 'View projects', 'dsg-theme' ); ?></a></div>
		<!-- /wp:button -->
		<!-- wp:button {"className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/contact/"><?php esc_html_e( 'Get in touch', 'dsg-theme' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
