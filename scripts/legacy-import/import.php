<?php
/**
 * Step 2 of the legacy import: create the old site's projects as Projects.
 *
 * Reads data/projects.json and data/images/ (made by extract.py) and, for
 * each project, creates or updates a dsg_project post:
 *
 * - title, slug, content, date and order as they were on the old site;
 * - excerpt from the old "home page text", or left empty so WordPress
 *   builds one from the content;
 * - the old banner as the featured image, the homepage screenshot as
 *   dsg_screenshot, and the old link as dsg_live_url.
 *
 * Safe to run again: projects and images are matched by their old IDs
 * (saved as _dsg_legacy_id), so a second run updates instead of duplicating.
 * A Project made by hand with the same slug is updated rather than duplicated.
 * Agency, year, role and technologies are never touched, so anything filled
 * in by hand survives a re-run.
 *
 * Run with WP-CLI, from the repo root:
 *   npx wp-env run cli wp eval-file wp-content/dsg-repo/scripts/legacy-import/import.php
 * On a server, upload scripts/legacy-import/ and run:
 *   wp eval-file path/to/legacy-import/import.php
 *
 * @package DSG\Core
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- one-off CLI script, run by `wp eval-file`.

if ( ! defined( 'WP_CLI' ) ) {
	exit( "Run this with: wp eval-file import.php\n" );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

const DSG_LEGACY_KEY = '_dsg_legacy_id';

$data_dir = __DIR__ . '/data';
$json     = $data_dir . '/projects.json';

if ( ! post_type_exists( 'dsg_project' ) ) {
	WP_CLI::error( 'The dsg_project post type is missing. Is the dsg-core plugin active?' );
}
if ( ! is_readable( $json ) ) {
	WP_CLI::error( "No $json. Run extract.py first." );
}

$projects = json_decode( file_get_contents( $json ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
if ( ! is_array( $projects ) ) {
	WP_CLI::error( 'projects.json is not valid JSON.' );
}

/**
 * The post (of any type) that was imported from this old ID, or 0.
 *
 * @param string $post_type Post type to look in.
 * @param int    $legacy_id ID on the old site.
 * @return int
 */
function dsg_legacy_find( string $post_type, int $legacy_id ): int {
	$ids = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => DSG_LEGACY_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- one-off script.
			'meta_value'     => $legacy_id, // phpcs:ignore WordPress.DB.SlowDBQuery -- one-off script.
		)
	);
	return (int) ( $ids[0] ?? 0 );
}

/**
 * Add an image to the media library (once), attached to a Project.
 *
 * @param array|null $image     { legacy_id, file } from projects.json.
 * @param int        $parent_id The Project it belongs to.
 * @param string     $alt       Alt text ('' for decorative).
 * @param string     $data_dir  Folder holding images/.
 * @return int Attachment ID, or 0.
 */
function dsg_legacy_image( ?array $image, int $parent_id, string $alt, string $data_dir ): int {
	if ( ! $image ) {
		return 0;
	}

	$existing = dsg_legacy_find( 'attachment', (int) $image['legacy_id'] );
	if ( $existing ) {
		return $existing;
	}

	$source = $data_dir . '/images/' . $image['file'];
	if ( ! is_readable( $source ) ) {
		WP_CLI::warning( "Missing image {$image['file']}" );
		return 0;
	}

	// media_handle_sideload() moves the file, so give it a copy.
	$tmp = wp_tempnam( $image['file'] );
	copy( $source, $tmp );

	$attachment_id = media_handle_sideload(
		array(
			'name'     => $image['file'],
			'tmp_name' => $tmp,
		),
		$parent_id
	);

	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $tmp );
		WP_CLI::warning( "Could not import {$image['file']}: " . $attachment_id->get_error_message() );
		return 0;
	}

	update_post_meta( $attachment_id, DSG_LEGACY_KEY, (int) $image['legacy_id'] );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );

	return (int) $attachment_id;
}

$created = 0;
$updated = 0;

foreach ( $projects as $project ) {
	$existing_id = dsg_legacy_find( 'dsg_project', (int) $project['legacy_id'] );

	// Not imported before, but already made by hand with the same slug: take it over.
	if ( ! $existing_id ) {
		$same_slug   = get_page_by_path( $project['slug'], OBJECT, 'dsg_project' );
		$existing_id = $same_slug ? $same_slug->ID : 0;
	}

	$postarr = array(
		'post_type'     => 'dsg_project',
		'post_status'   => 'publish',
		'post_title'    => $project['title'],
		'post_name'     => $project['slug'],
		'post_content'  => $project['content'],
		'post_excerpt'  => $project['excerpt'],
		'post_date'     => $project['date'],
		'post_date_gmt' => $project['date_gmt'],
		'menu_order'    => (int) $project['menu_order'],
	);

	if ( $existing_id ) {
		$postarr['ID'] = $existing_id;
	}

	// wp_slash: wp_insert_post() unslashes its input, which would eat backslashes in the content.
	$project_id = wp_insert_post( wp_slash( $postarr ), true );

	if ( is_wp_error( $project_id ) ) {
		WP_CLI::warning( "{$project['title']}: " . $project_id->get_error_message() );
		continue;
	}

	update_post_meta( $project_id, DSG_LEGACY_KEY, (int) $project['legacy_id'] );
	update_post_meta( $project_id, 'dsg_live_url', $project['live_url'] );

	// The banner is decorative (the card and page already show the title), so its alt is empty.
	$banner_id = dsg_legacy_image( $project['banner'], $project_id, '', $data_dir );
	if ( $banner_id ) {
		set_post_thumbnail( $project_id, $banner_id );
	}

	$screenshot_id = dsg_legacy_image(
		$project['screenshot'],
		$project_id,
		/* translators: %s: project name. */
		sprintf( __( 'The %s homepage', 'dsg-core' ), $project['title'] ),
		$data_dir
	);
	update_post_meta( $project_id, 'dsg_screenshot', $screenshot_id );

	if ( $existing_id ) {
		++$updated;
	} else {
		++$created;
	}
	WP_CLI::log( ( $existing_id ? 'Updated  ' : 'Created  ' ) . $project['title'] );
}

WP_CLI::success( "$created created, $updated updated." );
