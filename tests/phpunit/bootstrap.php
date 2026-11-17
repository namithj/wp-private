<?php
/**
 * Load the WordPress core test library with WP Private active.
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( false === getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Required by wp-phpunit.
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

// wp-phpunit's multisite switch is read from this constant by its own bootstrap.
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
		require dirname( __DIR__, 2 ) . '/wp-private.php';
	}
);

// Deterministic and offline: the core, plugin and theme update checks see a fresh result and never call WordPress.org.
foreach ( [ 'update_core', 'update_plugins', 'update_themes' ] as $wpp_test_transient ) {
	tests_add_filter(
		'pre_site_transient_' . $wpp_test_transient,
		static function () {
			return (object) [
				'last_checked'    => time(),
				'version_checked' => $GLOBALS['wp_version'] ?? '',
				'updates'         => [],
				'response'        => [],
				'translations'    => [],
				'checked'         => [],
			];
		}
	);
}

require $wpp_test_lib_dir . '/includes/bootstrap.php';
