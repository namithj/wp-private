<?php
/**
 * Environment-driven WordPress PHPUnit configuration.
 *
 * @package WpPrivate
 */

/**
 * Read a test environment value.
 *
 * @param string $name Environment variable name.
 * @param string $fallback Default value.
 * @return string
 */
function wpp_test_env( $name, $fallback ) {
	$value = getenv( $name );
	return false === $value || '' === $value ? $fallback : $value;
}

// There is deliberately no default core: bin/phpunit.sh installs a throwaway one and exports WP_TESTS_ABSPATH.
$wpp_test_abspath = wpp_test_env( 'WP_TESTS_ABSPATH', '' );
if ( '' === $wpp_test_abspath || ! file_exists( rtrim( $wpp_test_abspath, '/\\' ) . '/wp-settings.php' ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- WordPress is not loaded yet.
	fwrite( STDERR, 'No WordPress core found. Set WP_TESTS_ABSPATH, or run the tests through bin/phpunit.sh, which installs one into .cache/wp-core/.' . PHP_EOL );
	exit( 1 );
}
define( 'ABSPATH', rtrim( $wpp_test_abspath, '/\\' ) . '/' );

define( 'DB_NAME', wpp_test_env( 'WP_TESTS_DB_NAME', 'wp_private_tests' ) );
define( 'DB_USER', wpp_test_env( 'WP_TESTS_DB_USER', 'root' ) );
define( 'DB_PASSWORD', wpp_test_env( 'WP_TESTS_DB_PASSWORD', 'root' ) );
define( 'DB_HOST', wpp_test_env( 'WP_TESTS_DB_HOST', 'db' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required by WordPress's test bootstrap.
$table_prefix = 'wpptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'WP Private Test Suite' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WP_DEBUG', true );

define( 'AUTH_KEY', 'wp-private-tests' );
define( 'SECURE_AUTH_KEY', 'wp-private-tests' );
define( 'LOGGED_IN_KEY', 'wp-private-tests' );
define( 'NONCE_KEY', 'wp-private-tests' );
define( 'AUTH_SALT', 'wp-private-tests' );
define( 'SECURE_AUTH_SALT', 'wp-private-tests' );
define( 'LOGGED_IN_SALT', 'wp-private-tests' );
define( 'NONCE_SALT', 'wp-private-tests' );
