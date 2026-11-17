<?php
/**
 * Tests for the 1.x to 2.0 settings conversion (F003, C-4.1a, C-4.1b, C-4.3, A-07, A-08, L-06).
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__ ) . '/includes/class-wpp-test-case.php';

use WpPrivate\Settings;
use WpPrivate\Upgrade;

/**
 * Class UpgradeTest
 *
 * @group upgrade
 */
class UpgradeTest extends WPP_Test_Case {

	const HOSTILE = '<script>alert("x")</script><input name="a" value=\'b\'></textarea><b onclick="x()">&amp; &lt;q&gt; é</b>';

	/**
	 * A complete 1.6.x array with hostile markup.
	 *
	 * @return array<string, mixed>
	 */
	private function array_1_6() {
		return [
			'type'                  => 'link',
			'before_html'           => '<br/><div id="wp-private-box"><b>' . self::HOSTILE,
			'after_html'            => '</b></div><br/>' . self::HOSTILE,
			'authorized_text'       => self::HOSTILE,
			'blocked_users'         => [ '2', '240', '9999' ],
			'custom_login_page_url' => 'http://example.org/login/?a=1&b=2',
		];
	}

	/**
	 * Raw rows of the six new options.
	 *
	 * @return array<string, string|null>
	 */
	private function new_rows() {
		$rows = [];
		foreach ( Upgrade::TARGETS as $setting => $option ) {
			$rows[ $setting ] = $this->raw_option( $option );
		}
		return $rows;
	}

	/**
	 * From the 1.6.x array: every key is copied byte for byte, even with a kses filter attached first (D-09).
	 *
	 * @return void
	 */
	public function test_converts_the_1_6_array_byte_for_byte_past_a_sanitize_filter() {
		$array = $this->array_1_6();
		update_option( Upgrade::LEGACY_ARRAY, $array );
		$before = $this->raw_option( Upgrade::LEGACY_ARRAY );

		foreach ( Upgrade::TARGETS as $option ) {
			add_filter( 'sanitize_option_' . $option, 'wp_kses_post' );
			add_filter( 'sanitize_option_' . $option, 'sanitize_text_field', 20 );
		}

		Upgrade::maybe_upgrade();

		$rows = $this->new_rows();
		$this->assertSame( $array['type'], $rows['type'] );
		$this->assertSame( $array['before_html'], $rows['before_html'], 'Raw row identical (hostile markup).' );
		$this->assertSame( $array['after_html'], $rows['after_html'] );
		$this->assertSame( $array['authorized_text'], $rows['authorized_text'] );
		$this->assertSame( $array['custom_login_page_url'], $rows['custom_login_page_url'] );
		$this->assertSame( $array['blocked_users'], maybe_unserialize( $rows['blocked_users'] ) );
		$this->assertSame( maybe_serialize( $array['blocked_users'] ), $rows['blocked_users'], 'Same bytes for the array too.' );

		foreach ( Upgrade::TARGETS as $option ) {
			$this->assertSame( 10, has_filter( 'sanitize_option_' . $option, 'wp_kses_post' ), 'The filter is back after the copy.' );
			$this->assertSame( 20, has_filter( 'sanitize_option_' . $option, 'sanitize_text_field' ), 'Both priorities restored.' );
		}
		$this->assertSame( $before, $this->raw_option( Upgrade::LEGACY_ARRAY ), 'The 1.6 array is untouched.' );
		$this->assertSame( WP_PRIVATE_VERSION, get_option( Upgrade::VERSION_OPTION ) );
	}

	/**
	 * Converting through the Settings reader gives the same values the 1.6.x site had.
	 *
	 * @return void
	 */
	public function test_reader_sees_converted_values() {
		$array = $this->array_1_6();
		update_option( Upgrade::LEGACY_ARRAY, $array );
		Upgrade::maybe_upgrade();

		$this->assertSame( 'link', Settings::type() );
		$this->assertSame( $array['before_html'], Settings::before_html() );
		$this->assertSame( $array['after_html'], Settings::after_html() );
		$this->assertSame( $array['authorized_text'], Settings::authorized_text() );
		$this->assertSame( [ 2, 240, 9999 ], Settings::blocked_users(), 'IDs with no matching user are kept.' );
		$this->assertSame( $array['custom_login_page_url'], Settings::custom_login_url() );
	}

	/**
	 * Keys missing from the array create nothing.
	 *
	 * @return void
	 */
	public function test_missing_keys_create_nothing() {
		update_option( Upgrade::LEGACY_ARRAY, [ 'type' => 'custom' ] );
		Upgrade::maybe_upgrade();
		$rows = $this->new_rows();
		$this->assertSame( 'custom', $rows['type'] );
		unset( $rows['type'] );
		$this->assertSame( [ null ], array_values( array_unique( $rows ) ) );
	}

	/**
	 * From 1.5 (and 1.4, same shape): separate options, block list a comma string becomes an array (A-08).
	 *
	 * @return void
	 */
	public function test_converts_the_1_4_and_1_5_options() {
		update_option( 'wp_private_replacement_type', 'custom' );
		update_option( 'wp_private_before_html', '<br/><div id="wp-private-box"><b>This is protected content. ' );
		update_option( 'wp_private_after_html', '</b></div><br/>' );
		update_option( 'wp_private_not_authorized_text', self::HOSTILE );
		update_option( 'wp_private_selected_users', '3,5,' );
		update_option( 'wp_private_custom_login_page_url', 'http://example.org/login/' );
		update_option( 'wp_private_linkback_enable', '1' );

		Upgrade::maybe_upgrade();

		$rows = $this->new_rows();
		$this->assertSame( 'custom', $rows['type'] );
		$this->assertSame( '<br/><div id="wp-private-box"><b>This is protected content. ', $rows['before_html'] );
		$this->assertSame( self::HOSTILE, $rows['authorized_text'] );
		$this->assertSame( [ 3, 5 ], get_option( Settings::OPTION_BLOCKED_USERS ) );
		$this->assertSame( 'http://example.org/login/', $rows['custom_login_page_url'] );
		$this->assertNull( $this->raw_option( 'wp_private_settings' ), 'Nothing writes the 1.6 array.' );
	}

	/**
	 * Only some of the separate options exist (a 1.4 site that saved little).
	 *
	 * @return void
	 */
	public function test_partial_legacy_options() {
		update_option( 'wp_private_replacement_type', 'link' );
		Upgrade::maybe_upgrade();
		$rows = $this->new_rows();
		$this->assertSame( 'link', $rows['type'] );
		$this->assertNull( $rows['before_html'] );
		$this->assertNull( $rows['blocked_users'] );
	}

	/**
	 * Both present: the array wins as a whole; the old separate options are not mixed in.
	 *
	 * @return void
	 */
	public function test_array_wins_over_separate_options() {
		update_option( Upgrade::LEGACY_ARRAY, [ 'type' => 'form' ] );
		update_option( 'wp_private_replacement_type', 'custom' );
		update_option( 'wp_private_before_html', 'OLD' );
		Upgrade::maybe_upgrade();
		$rows = $this->new_rows();
		$this->assertSame( 'form', $rows['type'] );
		$this->assertNull( $rows['before_html'] );
	}

	/**
	 * The old options are all still there afterwards, with the same bytes.
	 *
	 * @return void
	 */
	public function test_old_options_are_left_in_place() {
		update_option( Upgrade::LEGACY_ARRAY, $this->array_1_6() );
		update_option( 'wp_private_replacement_type', 'custom' );
		update_option( 'wp_private_selected_users', '3,5' );
		update_option( 'wp_private_linkback_enable', '1' );
		$names  = array_merge( array_values( Upgrade::LEGACY_OPTIONS ), [ Upgrade::LEGACY_ARRAY, 'wp_private_linkback_enable' ] );
		$before = [];
		foreach ( $names as $name ) {
			$before[ $name ] = $this->raw_option( $name );
		}
		Upgrade::maybe_upgrade();
		foreach ( $names as $name ) {
			$this->assertSame( $before[ $name ], $this->raw_option( $name ), "$name is untouched." );
		}
	}

	/**
	 * Idempotent: a second run changes nothing, and nothing is overwritten after a 2.x change.
	 *
	 * @return void
	 */
	public function test_idempotent_and_never_overwrites() {
		update_option( Upgrade::LEGACY_ARRAY, $this->array_1_6() );
		Upgrade::maybe_upgrade();
		$first = $this->new_rows();

		Upgrade::run();
		$this->assertSame( $first, $this->new_rows(), 'A second run changes nothing.' );

		update_option( Settings::OPTION_TYPE, 'form' );
		update_option( Settings::OPTION_BLOCKED_USERS, [ 1 ] );
		delete_option( Upgrade::VERSION_OPTION );
		Upgrade::maybe_upgrade();
		$this->assertSame( 'form', get_option( Settings::OPTION_TYPE ), 'A 2.x change survives a re-run.' );
		$this->assertSame( [ 1 ], get_option( Settings::OPTION_BLOCKED_USERS ) );
		$this->assertSame( $first['before_html'], $this->raw_option( Settings::OPTION_BEFORE_HTML ) );
	}

	/**
	 * It does not run again once the stored version is current, even when 1.x data appears.
	 *
	 * @return void
	 */
	public function test_runs_only_when_the_stored_version_is_older() {
		Upgrade::maybe_upgrade();
		$this->assertSame( WP_PRIVATE_VERSION, get_option( Upgrade::VERSION_OPTION ) );
		update_option( 'wp_private_replacement_type', 'custom' );
		Upgrade::maybe_upgrade();
		$this->assertNull( $this->raw_option( Settings::OPTION_TYPE ) );

		update_option( Upgrade::VERSION_OPTION, '1.9.0' );
		Upgrade::maybe_upgrade();
		$this->assertNull( $this->raw_option( Settings::OPTION_TYPE ), 'A recorded older version does not convert again.' );
		$this->assertSame( WP_PRIVATE_VERSION, get_option( Upgrade::VERSION_OPTION ), 'An older stored version is bumped.' );
	}

	/**
	 * A later 2.x upgrade never refills a per-field option from the 1.x data (C-4.1b: once per site).
	 *
	 * @return void
	 */
	public function test_later_upgrade_does_not_refill_a_removed_option() {
		update_option( Upgrade::LEGACY_ARRAY, $this->array_1_6() );
		Upgrade::maybe_upgrade();
		$this->assertNotNull( $this->raw_option( Settings::OPTION_BLOCKED_USERS ) );

		delete_option( Settings::OPTION_BLOCKED_USERS );
		update_option( Upgrade::VERSION_OPTION, '2.0.0-alpha' );
		Upgrade::maybe_upgrade();
		$this->assertNull( $this->raw_option( Settings::OPTION_BLOCKED_USERS ) );
	}

	/**
	 * The routine runs on plugins_loaded at priority 1 and from the activation hook.
	 *
	 * @return void
	 */
	public function test_hooks() {
		$this->assertSame( 1, has_action( 'plugins_loaded', [ Upgrade::class, 'maybe_upgrade' ] ) );

		update_option( 'wp_private_replacement_type', 'link' );
		do_action( 'activate_' . plugin_basename( WP_PRIVATE_FILE ) );
		$this->assertSame( 'link', $this->raw_option( Settings::OPTION_TYPE ) );
	}

	/**
	 * A fresh activation writes the version and nothing else: no seed, no link-back option (A-07, L-06).
	 *
	 * @return void
	 */
	public function test_fresh_activation_writes_only_the_version() {
		global $wpdb;
		do_action( 'activate_' . plugin_basename( WP_PRIVATE_FILE ) );
		$names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wp\\_private%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Test.
		$this->assertSame( [ Upgrade::VERSION_OPTION ], $names );
	}
}
