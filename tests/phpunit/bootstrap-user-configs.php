<?php
/**
 * Bootstrap for the user-config regression group and the upgrade-path group.
 *
 * Same as bootstrap.php, plus the safeguard that keeps these groups away from the developer's real
 * site: WP_TESTS_ABSPATH must be set explicitly and must not be a WordPress root that contains this
 * plugin. bin/test-user-configs.sh and bin/test-upgrade-path.sh point it at a throwaway core in
 * .cache/wp-core/. Two switches select what is loaded (the upgrade-path script sets them):
 *
 * - WPP_PLUGIN_FILE: the main plugin file to load. Default: this build's wp-private.php.
 *   The upgrade-path group sets it to tests/fixtures/legacy/1.6.1/wp-private.php for the "old" phases.
 * - WPP_LOAD_SWIFTFORMS=1: also load the sibling SwiftForms plugin (config 10, coexistence).
 *
 * @package WpPrivate
 */

define( 'WPP_USER_CONFIGS_BOOTSTRAP', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WPP_ is this plugin's test prefix.

$wpp_test_abspath = getenv( 'WP_TESTS_ABSPATH' );
if ( false === $wpp_test_abspath || '' === $wpp_test_abspath ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- WordPress is not loaded yet.
	fwrite( STDERR, "Refusing to run: WP_TESTS_ABSPATH is not set.\nThese groups run against a throwaway WordPress core, never the real site.\nUse composer test:configs or composer test:upgrade.\n" );
	exit( 1 );
}

$wpp_test_real_abspath = realpath( $wpp_test_abspath );
$wpp_test_plugin_dir   = realpath( dirname( __DIR__, 2 ) );
if ( false === $wpp_test_real_abspath || 0 === strpos( $wpp_test_plugin_dir . '/', $wpp_test_real_abspath . '/' ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- WordPress is not loaded yet.
	fwrite( STDERR, "Refusing to run: WP_TESTS_ABSPATH contains this plugin, so it looks like a real site.\n" );
	exit( 1 );
}

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( false === getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Required by wp-phpunit.
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

if ( getenv( 'WP_TESTS_MULTISITE' ) && ! defined( 'WP_TESTS_MULTISITE' ) ) {
	define( 'WP_TESTS_MULTISITE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Constant read by wp-phpunit.
}

$wpp_test_lib_dir = getenv( 'WP_PHPUNIT__DIR' );
if ( false === $wpp_test_lib_dir ) {
	$wpp_test_lib_dir = dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit';
}

require_once $wpp_test_lib_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		$file = getenv( 'WPP_PLUGIN_FILE' );
		require false === $file || '' === $file ? dirname( __DIR__, 2 ) . '/wp-private.php' : $file;
		// The fixture themes must be known before WordPress picks the active theme: a later process continues a site whose theme is one of them.
		register_theme_directory( dirname( __DIR__ ) . '/user-configs/themes' );
		if ( '1' === getenv( 'WPP_LOAD_SWIFTFORMS' ) ) {
			require dirname( __DIR__, 3 ) . '/swiftforms/swiftforms.php';
		}
	}
);

require $wpp_test_lib_dir . '/includes/bootstrap.php';
