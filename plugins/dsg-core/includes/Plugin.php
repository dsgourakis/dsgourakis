<?php
/**
 * Main plugin class: connects every feature to WordPress.
 *
 * @package DSG\Core
 */

namespace DSG\Core;

use DSG\Core\Blocks\Blocks;
use DSG\Core\PostTypes\Project;

/**
 * Boots the plugin. Each feature lives in its own class and registers its own hooks.
 */
final class Plugin {

	/**
	 * The single instance of this class.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Whether init() has already run.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Return the single instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all features. Safe to call more than once.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		( new Project() )->register_hooks();
		( new Blocks() )->register_hooks();
	}
}
