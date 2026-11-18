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
}
add_action( 'after_setup_theme', 'dsg_theme_setup' );

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
