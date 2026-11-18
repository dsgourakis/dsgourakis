<?php
/**
 * Tests for the Project post type and Technology taxonomy.
 *
 * @package DSG\Core
 */

use DSG\Core\PostTypes\Project;

/**
 * Each method starting with "test_" is one test.
 * WP_UnitTestCase resets the database after every test, so tests never affect each other.
 */
class Test_Project_Post_Type extends WP_UnitTestCase {

	public function test_post_type_is_registered() {
		$this->assertTrue( post_type_exists( Project::POST_TYPE ) );
	}

	public function test_post_type_is_public_and_uses_the_block_editor() {
		$post_type = get_post_type_object( Project::POST_TYPE );

		$this->assertTrue( $post_type->public );
		$this->assertTrue( $post_type->show_in_rest, 'show_in_rest is required for the block editor.' );
	}

	public function test_archive_lives_at_portfolio() {
		$post_type = get_post_type_object( Project::POST_TYPE );

		$this->assertSame( 'portfolio', $post_type->has_archive );
		$this->assertSame( 'portfolio', $post_type->rewrite['slug'] );
	}

	public function test_projects_have_an_order_field() {
		$this->assertTrue( post_type_supports( Project::POST_TYPE, 'page-attributes' ), 'The Project Grid sorts by the "Order" field.' );
	}

	public function test_taxonomy_is_attached_to_projects() {
		$this->assertTrue( taxonomy_exists( Project::TAXONOMY ) );
		$this->assertContains( Project::TAXONOMY, get_object_taxonomies( Project::POST_TYPE ) );
	}

	public function test_project_can_be_tagged_with_technologies() {
		// Factories create test content quickly.
		$project_id = self::factory()->post->create( array( 'post_type' => Project::POST_TYPE ) );

		wp_set_object_terms( $project_id, array( 'PHP', 'React' ), Project::TAXONOMY );

		$names = wp_get_object_terms( $project_id, Project::TAXONOMY, array( 'fields' => 'names' ) );
		sort( $names );

		$this->assertSame( array( 'PHP', 'React' ), $names );
	}
}
