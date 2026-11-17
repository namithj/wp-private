<?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Test helper: the class name follows the WPP_ test prefix.
/**
 * Base class for WP Private tests.
 *
 * @package WpPrivate
 */

use WpPrivate\Settings;
use WpPrivate\Upgrade;

/**
 * Class WPP_Test_Case
 */
abstract class WPP_Test_Case extends WP_UnitTestCase {

	/**
	 * REQUEST_URI before the test.
	 *
	 * @var string
	 */
	private $request_uri = '';

	/**
	 * Every option the plugin owns or migrates, by name.
	 *
	 * @return string[]
	 */
	protected function all_options() {
		return array_merge(
			array_values( Upgrade::TARGETS ),
			array_values( Upgrade::LEGACY_OPTIONS ),
			[ Upgrade::LEGACY_ARRAY, Upgrade::VERSION_OPTION, 'wp_private_linkback_enable' ]
		);
	}

	/**
	 * Start from a site that has never stored a WP Private option, logged out, with the front-end context.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		foreach ( $this->all_options() as $option ) {
			delete_option( $option );
		}
		wp_set_current_user( 0 );
		$this->request_uri      = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		$_SERVER['REQUEST_URI'] = '/';
		$_GET                   = [];
	}

	/**
	 * Restore the globals this class touches.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET                   = [];
		$_SERVER['REQUEST_URI'] = $this->request_uri;
		parent::tear_down();
	}

	/**
	 * Store the six new options in one go.
	 *
	 * @param array<string, mixed> $values Setting name => value (type, before_html, ...).
	 * @return void
	 */
	protected function store( array $values ) {
		foreach ( $values as $setting => $value ) {
			update_option( Upgrade::TARGETS[ $setting ], $value );
		}
	}

	/**
	 * Log in as a new user.
	 *
	 * @param string $role Role.
	 * @return int User ID.
	 */
	protected function login_as( $role = 'subscriber' ) {
		$id = self::factory()->user->create( [ 'role' => $role ] );
		wp_set_current_user( $id );
		return $id;
	}

	/**
	 * Raw option row, bypassing every option filter and cache.
	 *
	 * @param string $name Option name.
	 * @return string|null The stored string, or null when there is no row.
	 */
	protected function raw_option( $name ) {
		global $wpdb;
		// get_var() turns an empty string into null, so a row is fetched instead.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ), ARRAY_N ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Test compares raw rows.
		return $row ? $row[0] : null;
	}

	/**
	 * Query arguments of a URL, decoded once by PHP like a request would be.
	 *
	 * @param string $url URL.
	 * @return array<string, string>
	 */
	protected function query_of( $url ) {
		$query = [];
		wp_parse_str( (string) wp_parse_url( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), PHP_URL_QUERY ), $query );
		return $query;
	}

	/**
	 * Every id="..." in a piece of HTML.
	 *
	 * @param string $html HTML.
	 * @return string[]
	 */
	protected function ids_in( $html ) {
		preg_match_all( '/\sid="([^"]+)"/', $html, $matches );
		return $matches[1];
	}
}
