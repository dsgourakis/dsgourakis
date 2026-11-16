<?php
/**
 * Registers every custom block found in build/.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Blocks;

/**
 * Each folder in build/ with a block.json is registered automatically,
 * so a new block only needs a new folder in src/.
 */
class Blocks {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register the built blocks.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		foreach ( self::block_folders() as $folder ) {
			register_block_type( $folder );
		}
	}

	/**
	 * Return the absolute paths of all built block folders.
	 *
	 * @param string|null $build_dir Folder to scan. Defaults to the plugin's build/ folder.
	 * @return string[]
	 */
	public static function block_folders( ?string $build_dir = null ): array {
		$build_dir = $build_dir ?? DSG_CORE_DIR . 'build';
		$files     = glob( trailingslashit( $build_dir ) . '*/block.json' );

		if ( false === $files ) {
			return array();
		}

		return array_map( 'dirname', $files );
	}
}
