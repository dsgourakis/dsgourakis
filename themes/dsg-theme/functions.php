<?php
/**
 * Theme setup. A block theme needs very little PHP:
 * design lives in theme.json, layout in templates/ and parts/.
 *
 * @package DSG\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load style.css on the frontend and in the editor.
 *
 * @return void
 */
function dsg_theme_enqueue_styles() {
	wp_enqueue_style(
		'dsg-theme',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dsg_theme_enqueue_styles' );

/**
 * Theme supports.
 *
 * @return void
 */
function dsg_theme_setup() {
	add_editor_style( 'style.css' );

	// Pages use their excerpt as the intro text under the title (see templates/page.html).
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'after_setup_theme', 'dsg_theme_setup' );

/**
 * On pages, show the Excerpt block only when an excerpt was written.
 *
 * Without this, WordPress makes one up from the first words of the page
 * content, and the intro would repeat the text right below it.
 *
 * @param string   $block_content The block's HTML.
 * @param array    $block         The block's data.
 * @param WP_Block $instance      The block instance, which knows which post it's showing.
 * @return string
 */
function dsg_theme_page_excerpt_only_if_written( $block_content, $block, $instance ) {
	$post_id = $instance->context['postId'] ?? 0;

	if ( $post_id && 'page' === get_post_type( $post_id ) && ! has_excerpt( $post_id ) ) {
		return '';
	}

	return $block_content;
}
add_filter( 'render_block_core/post-excerpt', 'dsg_theme_page_excerpt_only_if_written', 10, 3 );

/**
 * Preload the main font, so the hero heading doesn't flash in the fallback.
 *
 * Only Schibsted Grotesk: it's the big text above the fold. JetBrains Mono
 * is used for small labels, and preloading it too would compete for bandwidth.
 * The @font-face rules themselves come from theme.json.
 *
 * @param array $resources Resources WordPress will preload.
 * @return array
 */
function dsg_theme_preload_fonts( $resources ) {
	$resources[] = array(
		'href'        => get_theme_file_uri( 'assets/fonts/schibsted-grotesk-latin-wght-normal.woff2' ),
		'as'          => 'font',
		'type'        => 'font/woff2',
		'crossorigin' => 'anonymous', // Fonts are always fetched in CORS mode; without this the preload is wasted.
	);

	return $resources;
}
add_filter( 'wp_preload_resources', 'dsg_theme_preload_fonts' );

/**
 * Extra block styles. Their CSS lives in style.css.
 *
 * @return void
 */
function dsg_theme_register_block_styles() {
	// A button that looks like a plain link, e.g. "See work ↓" next to a real button.
	register_block_style(
		'core/button',
		array(
			'name'  => 'text',
			'label' => __( 'Text link', 'dsg-theme' ),
		)
	);
}
add_action( 'init', 'dsg_theme_register_block_styles' );
