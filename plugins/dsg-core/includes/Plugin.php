<?php
/**
 * Main plugin class: connects every feature to WordPress.
 *
 * @package DSG\Core
 */

namespace DSG\Core;

use DSG\Core\Blocks\Blocks;
use DSG\Core\Blocks\Copyright;
use DSG\Core\Contact\ContactEndpoint;
use DSG\Core\Contact\FallbackHandler;
use DSG\Core\Contact\Mail;
use DSG\Core\Contact\Messages;
use DSG\Core\Navigation\ClientNavigation;
use DSG\Core\PostTypes\Project;
use DSG\Core\Settings\ResumeLink;

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
		( new Copyright() )->register_hooks();
		( new ClientNavigation() )->register_hooks();
		( new ResumeLink() )->register_hooks();
		( new Messages() )->register_hooks();
		( new Mail() )->register_hooks();
		( new ContactEndpoint() )->register_hooks();
		( new FallbackHandler() )->register_hooks();
	}
}
