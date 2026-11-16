<?php
/**
 * Tests for block registration.
 *
 * @package DSG\Core
 */

use DSG\Core\Blocks\Blocks;

/**
 * Checks that built blocks are found and registered.
 * Run `npm run build` first, otherwise build/ is empty.
 */
class Test_Blocks extends WP_UnitTestCase {

	public function test_block_folders_returns_empty_array_for_missing_folder() {
		$this->assertSame( array(), Blocks::block_folders( '/path/that/does/not/exist' ) );
	}

	public function test_project_card_block_is_registered() {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'dsg/project-card' ),
			'dsg/project-card is not registered. Did you run "npm run build"?'
		);
	}

	public function test_project_card_renders_title_and_link() {
		$markup = '<!-- wp:dsg/project-card {"url":"https://example.com","technologies":["PHP"]} -->'
			. '<article class="wp-block-dsg-project-card"><div class="wp-block-dsg-project-card__body">'
			. '<h3 class="wp-block-dsg-project-card__title"><a href="https://example.com">'
			. '<span class="wp-block-dsg-project-card__title-text">Red Cross</span></a></h3>'
			. '</div></article>'
			. '<!-- /wp:dsg/project-card -->';

		$html = do_blocks( $markup );

		$this->assertStringContainsString( 'Red Cross', $html );
		$this->assertStringContainsString( 'href="https://example.com"', $html );
	}
}
