<?php
/**
 * PHPUnit bootstrap: loads the WordPress test framework, then this plugin.
 *
 * Runs inside the wp-env "tests-cli" container, where WP_TESTS_DIR points to
 * the WordPress test library and a separate test database is ready.
 *
 * @package DSG\Core
 */

$dsg_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $dsg_tests_dir ) {
	$dsg_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $dsg_tests_dir . '/includes/functions.php' ) ) {
	echo "WordPress test library not found in {$dsg_tests_dir}. Run the tests with: npm run test:php" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output.
	exit( 1 );
}

// The test library needs the PHPUnit Polyfills, installed by Composer in the repo root.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 3 ) . '/vendor/yoast/phpunit-polyfills' );

require_once $dsg_tests_dir . '/includes/functions.php';

// Load the plugin before WordPress finishes loading, like a must-use plugin.
tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/dsg-core.php';
	}
);

require $dsg_tests_dir . '/includes/bootstrap.php';
