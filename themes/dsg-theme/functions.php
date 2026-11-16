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
