<?php
/**
 * Tests for block registration and rendering.
 *
 * @package DSG\Core
 */

use DSG\Core\Blocks\Blocks;
use DSG\Core\PostTypes\Project;

/**
 * Checks that built blocks are found, registered and render correctly.
 * Run `npm run build` first, otherwise build/ is empty.
 *
 * Dynamic blocks are easy to test: give do_blocks() the comment WordPress
 * stores in the post, and check the HTML render.php returns.
 */
class Test_Blocks extends WP_UnitTestCase {

	public function test_block_folders_returns_empty_array_for_missing_folder() {
		$this->assertSame( array(), Blocks::block_folders( '/path/that/does/not/exist' ) );
	}

	public function test_blocks_are_registered() {
		$registry = WP_Block_Type_Registry::get_instance();

		foreach ( array( 'dsg/contact-form', 'dsg/hero', 'dsg/project-card', 'dsg/project-grid', 'dsg/section-header', 'dsg/stat', 'dsg/stats', 'dsg/tech-stack' ) as $name ) {
			$this->assertTrue( $registry->is_registered( $name ), "$name is not registered. Did you run \"npm run build\"?" );
		}
	}

	public function test_project_card_renders_title_link_and_cta() {
		$html = do_blocks( '<!-- wp:dsg/project-card {"title":"Red Cross","summary":"A site.","url":"https://example.com"} /-->' );

		$this->assertStringContainsString( '<a href="https://example.com">Red Cross</a>', $html );
		$this->assertStringContainsString( 'A site.', $html );
		$this->assertStringContainsString( '<a class="wp-block-dsg-project-card__cta" href="https://example.com">', $html );
		$this->assertStringContainsString( 'View the project', $html );
	}

	public function test_project_card_without_link_has_no_link_or_cta() {
		$html = do_blocks( '<!-- wp:dsg/project-card {"title":"Red Cross"} /-->' );

		$this->assertStringNotContainsString( '<a ', $html );
		$this->assertStringNotContainsString( 'View the project', $html );
	}

	public function test_project_card_escapes_unsafe_values() {
		$html = do_blocks( '<!-- wp:dsg/project-card {"title":"X","url":"javascript:alert(1)","technologies":["<script>bad</script>"]} /-->' );

		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_stat_renders_all_fields() {
		$html = do_blocks( '<!-- wp:dsg/stat {"pretitle":"build","title":"WordPress development","description":"Custom themes."} /-->' );

		$this->assertStringContainsString( '<p class="wp-block-dsg-stat__pretitle">build</p>', $html );
		$this->assertStringContainsString( '<h3 class="wp-block-dsg-stat__title">WordPress development</h3>', $html );
		$this->assertStringContainsString( 'Custom themes.', $html );
	}

	public function test_stats_wraps_its_stat_blocks() {
		$html = do_blocks(
			'<!-- wp:dsg/stats -->'
			. '<!-- wp:dsg/stat {"title":"4+"} /-->'
			. '<!-- wp:dsg/stat {"title":"Full-stack"} /-->'
			. '<!-- /wp:dsg/stats -->'
		);

		// Wrapper with the default "wide" alignment, and both Stats inside it.
		$this->assertMatchesRegularExpression( '/<div class="[^"]*\bwp-block-dsg-stats\b/', $html );
		$this->assertMatchesRegularExpression( '/<div class="[^"]*\balignwide\b/', $html );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-stat__title' ) );
	}

	public function test_stat_keeps_empty_pretitle_for_the_counter() {
		$html = do_blocks( '<!-- wp:dsg/stat {"title":"4+"} /-->' );

		$this->assertStringContainsString( '<p class="wp-block-dsg-stat__pretitle"></p>', $html );
	}

	public function test_section_header_renders_heading_and_aside() {
		$html = do_blocks( '<!-- wp:dsg/section-header {"heading":"Selected work","aside":"// 03 projects"} /-->' );

		$this->assertStringContainsString( '<h2 class="wp-block-dsg-section-header__heading">Selected work</h2>', $html );
		$this->assertStringContainsString( '<p class="wp-block-dsg-section-header__aside">// 03 projects</p>', $html );
		$this->assertMatchesRegularExpression( '/<div [^>]*class="[^"]*\balignwide\b/', $html );
	}

	public function test_section_header_has_a_default_bottom_margin() {
		$html = do_blocks( '<!-- wp:dsg/section-header {"heading":"Selected work"} /-->' );

		// The block.json default for the Margin control, printed as an inline style.
		$this->assertStringContainsString( 'style="margin-bottom:var(--wp--preset--spacing--40)"', $html );
	}

	public function test_section_header_skips_empty_aside() {
		$html = do_blocks( '<!-- wp:dsg/section-header {"heading":"Selected work"} /-->' );

		$this->assertStringNotContainsString( '__aside', $html );
	}

	public function test_section_header_renders_nothing_when_empty() {
		$this->assertSame( '', trim( do_blocks( '<!-- wp:dsg/section-header /-->' ) ) );
	}

	/**
	 * The block comment for a Hero. serialize_block_attributes() escapes the
	 * HTML in the attributes the same way the editor does when it saves.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private static function hero_comment( array $attributes ): string {
		return '<!-- wp:dsg/hero ' . serialize_block_attributes( $attributes ) . ' -->';
	}

	public function test_hero_renders_heading_tagline_buttons_and_monogram() {
		$html = do_blocks(
			self::hero_comment(
				array(
					'heading' => 'Hi,<br>I\'m <mark class="has-inline-color has-accent-color">D</mark>imitris',
					'tagline' => 'WordPress developer',
				)
			)
			. '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Contact me</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'
			. '<!-- /wp:dsg/hero -->'
		);

		// Wrapper with the block class, the default "wide" alignment and the code tags on.
		$this->assertMatchesRegularExpression( '/<div class="(?=[^"]*\bwp-block-dsg-hero\b)(?=[^"]*\balignwide\b)(?=[^"]*\bhas-code-tags\b)/', $html );
		$this->assertStringContainsString( '<h1 class="wp-block-dsg-hero__title">Hi,<br>I\'m <mark class="has-inline-color has-accent-color">D</mark>imitris</h1>', $html );
		$this->assertStringContainsString( '<p class="wp-block-dsg-hero__tagline">WordPress developer</p>', $html );
		$this->assertStringContainsString( 'href="/contact/">Contact me</a>', $html );

		// Two copies of the default "D", hidden from screen readers.
		$this->assertStringContainsString( '<div class="wp-block-dsg-hero__monogram" aria-hidden="true">', $html );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-hero__letter' ) );
	}

	public function test_hero_skips_empty_parts() {
		$html = do_blocks( '<!-- wp:dsg/hero {"heading":"Hi","monogram":"","showCodeTags":false} /-->' );

		$this->assertStringNotContainsString( '__tagline', $html );
		$this->assertStringNotContainsString( '__actions', $html );
		$this->assertStringNotContainsString( '__monogram', $html );
		$this->assertStringNotContainsString( 'has-code-tags', $html );
	}

	public function test_hero_escapes_unsafe_values() {
		$html = do_blocks(
			self::hero_comment(
				array(
					'heading'  => '<script>bad</script>Hi',
					'monogram' => '<b>',
				)
			) . '<!-- /wp:dsg/hero -->'
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringContainsString( '&lt;b&gt;', $html );
	}

	public function test_tech_stack_renders_a_labelled_list() {
		$html = do_blocks( '<!-- wp:dsg/tech-stack {"label":"Stack","items":["PHP","JavaScript"]} /-->' );

		$this->assertMatchesRegularExpression( '/<p class="wp-block-dsg-tech-stack__label" id="([^"]+)">Stack<\/p>\s*<ul class="wp-block-dsg-tech-stack__list" aria-labelledby="\1">/', $html );
		$this->assertStringContainsString( '<li>PHP</li>', $html );
		$this->assertStringContainsString( '<li>JavaScript</li>', $html );
	}

	public function test_tech_stack_escapes_items_and_skips_empty_ones() {
		$html = do_blocks( '<!-- wp:dsg/tech-stack {"items":["<script>bad</script>","  "]} /-->' );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertSame( 1, substr_count( $html, '<li>' ) );
	}

	public function test_tech_stack_without_items_renders_nothing() {
		$this->assertSame( '', trim( do_blocks( '<!-- wp:dsg/tech-stack {"label":"Stack"} /-->' ) ) );
	}

	public function test_project_grid_renders_projects_as_cards_in_order() {
		$first  = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Golden Star Ferries',
				'menu_order' => 0,
				'post_date'  => '2024-01-01 00:00:00',
			)
		);
		$second = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Hellenic Red Cross',
				'menu_order' => 1,
				'post_date'  => '2026-01-01 00:00:00',
			)
		);
		wp_set_object_terms( $first, array( 'PHP' ), Project::TAXONOMY );

		$html = do_blocks( '<!-- wp:dsg/project-grid /-->' );

		$this->assertMatchesRegularExpression( '/<div class="[^"]*\bwp-block-dsg-project-grid\b/', $html );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-project-card__title' ) );
		$this->assertStringContainsString( get_permalink( $second ), $html );
		$this->assertStringContainsString( '<li>PHP</li>', $html );

		// Lower "Order" comes first, even though it's the older Project.
		$this->assertLessThan( strpos( $html, 'Hellenic Red Cross' ), strpos( $html, 'Golden Star Ferries' ) );
	}

	public function test_project_grid_respects_number_and_technology() {
		$php   = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'PHP project',
			)
		);
		$react = self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'React project',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'  => Project::POST_TYPE,
				'post_title' => 'Other project',
			)
		);

		$php_term = wp_set_object_terms( $php, array( 'PHP' ), Project::TAXONOMY );
		wp_set_object_terms( $react, array( 'React' ), Project::TAXONOMY );

		$html = do_blocks( '<!-- wp:dsg/project-grid {"technology":' . (int) $php_term[0] . '} /-->' );
		$this->assertStringContainsString( 'PHP project', $html );
		$this->assertStringNotContainsString( 'React project', $html );

		$html = do_blocks( '<!-- wp:dsg/project-grid {"numberOfItems":2} /-->' );
		$this->assertSame( 2, substr_count( $html, 'wp-block-dsg-project-card__title' ) );
	}

	public function test_project_grid_without_projects_renders_nothing() {
		$this->assertSame( '', trim( do_blocks( '<!-- wp:dsg/project-grid /-->' ) ) );
	}
}
