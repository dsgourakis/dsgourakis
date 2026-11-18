<?php
/**
 * "Project" post type and "Technology" taxonomy.
 *
 * @package DSG\Core
 */

namespace DSG\Core\PostTypes;

/**
 * Portfolio projects, tagged with the technologies used.
 */
class Project {

	public const POST_TYPE = 'dsg_project';
	public const TAXONOMY  = 'dsg_technology';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the Project post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Projects', 'dsg-core' ),
					'singular_name' => __( 'Project', 'dsg-core' ),
					'add_new_item'  => __( 'Add New Project', 'dsg-core' ),
					'edit_item'     => __( 'Edit Project', 'dsg-core' ),
					'all_items'     => __( 'All Projects', 'dsg-core' ),
				),
				'public'       => true,
				'has_archive'  => 'portfolio',
				'rewrite'      => array( 'slug' => 'portfolio' ),
				'menu_icon'    => 'dashicons-portfolio',
				'show_in_rest' => true, // Required for the block editor.
				// page-attributes adds the "Order" field, used to sort the Project Grid.
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
			)
		);
	}

	/**
	 * Register the Technology taxonomy (PHP, React, WooCommerce...).
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Technologies', 'dsg-core' ),
					'singular_name' => __( 'Technology', 'dsg-core' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'technology' ),
			)
		);
	}
}
