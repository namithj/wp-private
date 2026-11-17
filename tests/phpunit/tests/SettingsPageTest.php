<?php
/**
 * Tests for the settings screen (F021, F004, F007, A-09, S-01, S-02, S-06, S-07) and the 1.x-name rule.
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__ ) . '/includes/class-wpp-test-case.php';

use WpPrivate\Admin\Settings_Page;
use WpPrivate\Admin\Users_Field;
use WpPrivate\Settings;
use WpPrivate\Upgrade;
use WpPrivate\Vendor\PedalCMS\CassetteCMF\Core\Manager;
use WpPrivate\Vendor\PedalCMS\CassetteCMF\Field\Container_Field_Interface;

/**
 * Class SettingsPageTest
 *
 * @group settings
 */
class SettingsPageTest extends WPP_Test_Case {

	/**
	 * An administrator, a clean admin menu and the settings registered (admin_init).
	 *
	 * @return void
	 */
	public function set_up() {
		global $menu, $submenu, $admin_page_hooks, $_registered_pages, $_parent_pages;
		parent::set_up();
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Fresh admin menu state for the test.
		$menu              = [];
		$submenu           = [];
		$admin_page_hooks  = [];
		$_registered_pages = [];
		$_parent_pages     = [];
		// phpcs:enable
		// The library registers a page once per process; make it register again for each test.
		$property = new ReflectionProperty( get_class( Manager::init()->get_new_settings_handler()->get_page( Settings::PAGE_ID ) ), 'registered' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true ); // No effect from PHP 8.1, deprecated in 8.5.
		}
		$property->setValue( Manager::init()->get_new_settings_handler()->get_page( Settings::PAGE_ID ), false );
		$this->login_as( 'administrator' );
		set_current_screen( 'settings_page_wp-private' );
		// These send headers, and the CLI has already printed output.
		remove_action( 'admin_init', 'send_frame_options_header' );
		remove_action( 'admin_init', 'wp_admin_headers' );
		do_action( 'admin_menu' );
		do_action( 'admin_init' );
	}

	/**
	 * Forget the screen.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/**
	 * Draw the page.
	 *
	 * @return string
	 */
	private function page_html() {
		$GLOBALS['title'] = 'WP Private'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- get_admin_page_title() reads it.
		ob_start();
		Settings_Page::render();
		return ob_get_clean();
	}

	/**
	 * The six option names are the C-4.1 names, and none equals a 1.x name.
	 *
	 * @return void
	 */
	public function test_option_names_are_the_contract_names() {
		$names = [];
		foreach ( Manager::init()->get_new_settings_handler()->get_fields( Settings::PAGE_ID ) as $field ) {
			if ( ! $field instanceof Container_Field_Interface ) {
				$names[] = $field->get_option_name( Settings::PAGE_ID );
			}
		}
		sort( $names );
		$this->assertSame(
			[
				'wp_private_settings_after_html',
				'wp_private_settings_authorized_text',
				'wp_private_settings_before_html',
				'wp_private_settings_blocked_users',
				'wp_private_settings_custom_login_page_url',
				'wp_private_settings_type',
			],
			$names
		);
		$legacy = array_merge( array_values( Upgrade::LEGACY_OPTIONS ), [ Upgrade::LEGACY_ARRAY, 'wp_private_linkback_enable' ] );
		$this->assertSame( [], array_intersect( $names, $legacy ) );
		$this->assertSame( array_values( Upgrade::TARGETS ), array_values( array_intersect( array_values( Upgrade::TARGETS ), $names ) ) );
	}

	/**
	 * Every field is a registered setting with a sanitiser (F004).
	 *
	 * @return void
	 */
	public function test_every_option_is_registered_with_a_sanitiser() {
		foreach ( Upgrade::TARGETS as $option ) {
			$this->assertTrue( has_filter( 'sanitize_option_' . $option ), "$option has a sanitiser." );
			$registered = get_registered_settings();
			$this->assertArrayHasKey( $option, $registered );
			$this->assertSame( Settings_Page::MENU_SLUG, $registered[ $option ]['group'], 'Saved through options.php with the page option group.' );
		}
	}

	/**
	 * The page sits under Settings, labelled "WP Private", slug wp-private (D-13).
	 *
	 * @return void
	 */
	public function test_menu_entry() {
		global $submenu;
		$this->assertArrayHasKey( 'options-general.php', $submenu );
		$entries = wp_list_filter( $submenu['options-general.php'], [ 2 => Settings_Page::MENU_SLUG ] );
		$this->assertCount( 1, $entries );
		$entry = reset( $entries );
		$this->assertSame( 'WP Private', $entry[0] );
		$this->assertSame( 'manage_options', $entry[1] );
	}

	/**
	 * HTML fields: raw for a user with unfiltered_html, wp_kses_post for the rest (S-01).
	 *
	 * Single-site only: it assumes a site administrator has unfiltered_html (config 09 and S10 cover the network case).
	 *
	 * @group ms-excluded
	 * @return void
	 */
	public function test_html_sanitising_by_capability() {
		$markup = '<script>alert(1)</script><b onclick="x()">bold</b>';
		$fields = [ Settings::OPTION_BEFORE_HTML, Settings::OPTION_AFTER_HTML, Settings::OPTION_AUTHORIZED_TEXT ];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->assertTrue( current_user_can( 'unfiltered_html' ), 'Single-site administrator.' );
		foreach ( $fields as $option ) {
			update_option( $option, $markup );
			$this->assertSame( $markup, $this->raw_option( $option ), "$option keeps markup for unfiltered_html." );
		}

		$user = new WP_User( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$user->add_cap( 'unfiltered_html', false );
		wp_set_current_user( $user->ID );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );
		foreach ( $fields as $option ) {
			// An unchanged save (options.php trims, browsers post CRLF) keeps the stored bytes (C-4.5, S10).
			update_option( $option, $markup . "\r\n" );
			$this->assertSame( $markup, $this->raw_option( $option ), "$option: an unchanged save keeps the stored value." );

			// New markup is filtered.
			update_option( $option, $markup . '<i>new</i>' );
			$stored = $this->raw_option( $option );
			$this->assertStringNotContainsString( '<script', $stored, "$option is filtered without unfiltered_html." );
			$this->assertStringNotContainsString( 'onclick', $stored );
			$this->assertStringContainsString( '<b>bold</b>', $stored );
		}
	}

	/**
	 * URL, type and users sanitising; a hostile array for a text field does not crash.
	 *
	 * @return void
	 */
	public function test_url_type_and_users_sanitising() {
		update_option( Settings::OPTION_LOGIN_URL, 'javascript:alert(1)' );
		$this->assertSame( '', $this->raw_option( Settings::OPTION_LOGIN_URL ) );
		update_option( Settings::OPTION_LOGIN_URL, 'http://example.org/login/?a=1&b=2' );
		$this->assertSame( 'http://example.org/login/?a=1&b=2', $this->raw_option( Settings::OPTION_LOGIN_URL ) );
		update_option( Settings::OPTION_LOGIN_URL, [ 'x' ] );
		$this->assertSame( '', $this->raw_option( Settings::OPTION_LOGIN_URL ) );

		update_option( Settings::OPTION_TYPE, 'link' );
		$this->assertSame( 'link', $this->raw_option( Settings::OPTION_TYPE ) );
		update_option( Settings::OPTION_TYPE, 'evil' );
		$this->assertSame( '', $this->raw_option( Settings::OPTION_TYPE ) );
		update_option( Settings::OPTION_TYPE, [ 'link' ] );
		$this->assertSame( '', $this->raw_option( Settings::OPTION_TYPE ) );
		$this->assertSame( 'form', Settings::type() );

		update_option( Settings::OPTION_BLOCKED_USERS, [ '3', 'x', '3', 0, '7', -4 ] );
		$this->assertSame( [ 3, 7, 4 ], get_option( Settings::OPTION_BLOCKED_USERS ), 'absint, unique, no zero.' );
		update_option( Settings::OPTION_BLOCKED_USERS, null );
		$this->assertSame( [], get_option( Settings::OPTION_BLOCKED_USERS ), 'Nothing posted means an empty list.' );
	}

	/**
	 * With nothing stored the page shows the defaults; stored markup comes back intact (S-02).
	 *
	 * Single-site only: the test admin has unfiltered_html there, so the stored markup is kept as written; on a network it is filtered.
	 *
	 * @group ms-excluded
	 * @return void
	 */
	public function test_page_shows_defaults_and_escapes_stored_values() {
		$html = $this->page_html();
		$this->assertStringContainsString( 'name="wp_private_settings_type" value="form" checked', $html );
		$this->assertStringContainsString( esc_textarea( Settings::default_before_html() ), $html );
		$this->assertStringContainsString( 'name="wp_private_settings_before_html"', $html );
		$this->assertStringContainsString( 'name="wp_private_settings_blocked_users[]"', $html );
		$this->assertStringContainsString( "name='option_page' value='wp-private'", $html );

		$stored = '</textarea><script>x()</script>&amp; &lt;b&gt;"\'';
		update_option( Settings::OPTION_BEFORE_HTML, $stored );
		update_option( Settings::OPTION_LOGIN_URL, 'http://example.org/?a="b"' );
		$html = $this->page_html();
		$this->assertStringContainsString( '>' . esc_textarea( $stored ) . '</textarea>', $html );
		$this->assertStringNotContainsString( '<script>x()', $html );

		update_option( Settings::OPTION_BEFORE_HTML, '' );
		$this->assertStringContainsString( '></textarea>', $this->page_html(), 'A stored empty string is shown empty, not replaced by the default.' );
	}

	/**
	 * No notice, no promotion, no Classic Editor instruction on the screen (F007), and no notice anywhere.
	 *
	 * @return void
	 */
	public function test_no_notice_and_no_promotion() {
		$html = $this->page_html();
		$this->assertStringNotContainsString( 'notice', $html );
		$this->assertStringNotContainsString( 'settings-error', $html );
		foreach ( [ 'Wp-Insert', 'review', 'Classic Editor', 'donate', 'Namith', 'quote', 'smartlogix' ] as $word ) {
			$this->assertStringNotContainsStringIgnoringCase( $word, $html, "No \"$word\" on the screen." );
		}

		// Core's own "a newer WordPress is available" nags depend on the test core's age, not on this plugin.
		foreach ( [ 'admin_notices', 'network_admin_notices' ] as $hook ) {
			remove_action( $hook, 'update_nag', 3 );
			remove_action( $hook, 'maintenance_nag', 10 );
		}

		foreach ( [ 'settings_page_wp-private', 'dashboard' ] as $screen ) {
			set_current_screen( $screen );
			ob_start();
			do_action( 'admin_notices' );
			do_action( 'network_admin_notices' );
			do_action( 'all_admin_notices' );
			$this->assertSame( '', trim( ob_get_clean() ), "No admin notice on $screen." );
		}
	}

	/**
	 * Split the page into the blocked section and the add section.
	 *
	 * @param string $html Page HTML.
	 * @return array{0: string, 1: string}
	 */
	private function sections( $html ) {
		$start = strpos( $html, 'class="wp-private-users-blocked"' );
		$mid   = strpos( $html, 'class="wp-private-users-add"' );
		$this->assertNotFalse( $start );
		$this->assertNotFalse( $mid );
		return [ substr( $html, $start, $mid - $start ), substr( $html, $mid ) ];
	}

	/**
	 * 250 users with ID 240 stored: a save from a view that shows page 1 only keeps it (A-09).
	 *
	 * @return void
	 */
	public function test_block_list_survives_a_save_from_one_page() {
		$ids = [];
		for ( $i = 1; $i <= 250; $i++ ) {
			$ids[ $i ] = self::factory()->user->create( [ 'user_login' => sprintf( 'wppuser%03d', $i ) ] );
		}
		$blocked = [ $ids[240], $ids[3], 987654 ];
		update_option( Settings::OPTION_BLOCKED_USERS, $blocked );
		$box = '/<input type="checkbox" name="wp_private_settings_blocked_users\[\]" value="(\d+)"( checked=\'checked\')?/';

		// Page 1: both real blocked users are ticked in the blocked section; the ID of no user is a hidden input.
		$_GET                            = [];
		$html                            = $this->page_html();
		list( $blocked_html, $add_html ) = $this->sections( $html );
		preg_match_all( $box, $blocked_html, $in_blocked, PREG_SET_ORDER );
		$this->assertCount( 2, $in_blocked );
		foreach ( $in_blocked as $match ) {
			$this->assertArrayHasKey( 2, $match, 'Ticked.' );
		}
		$this->assertEqualsCanonicalizing( [ (string) $ids[240], (string) $ids[3] ], array_column( $in_blocked, 1 ) );
		$this->assertStringContainsString( 'type="hidden" name="wp_private_settings_blocked_users[]" value="987654"', $blocked_html );

		preg_match_all( $box, $add_html, $results, PREG_SET_ORDER );
		$this->assertCount( 50, $results );
		foreach ( $results as $match ) {
			$this->assertArrayNotHasKey( 2, $match, 'Unticked.' );
			$this->assertNotContains( (int) $match[1], $blocked );
		}
		$this->assertStringContainsString( 'page=wp-private', $add_html );
		$this->assertStringContainsString( Users_Field::PAGE_ARG . '=2', $add_html, 'Paging links.' );

		// The browser posts what the form holds: ticked boxes and every hidden input.
		preg_match_all( '/<input type="checkbox" name="wp_private_settings_blocked_users\[\]" value="(\d+)" checked=\'checked\'/', $html, $checked );
		preg_match_all( '/type="hidden" name="wp_private_settings_blocked_users\[\]" value="(\d+)"/', $html, $hidden );
		update_option( Settings::OPTION_BLOCKED_USERS, array_merge( $checked[1], $hidden[1] ) );
		$this->assertEqualsCanonicalizing( $blocked, get_option( Settings::OPTION_BLOCKED_USERS ), 'No stored ID is dropped.' );

		// A search finds one unticked user.
		$_GET               = [ Users_Field::SEARCH_ARG => 'wppuser120' ];
		list( , $add_html ) = $this->sections( $this->page_html() );
		preg_match_all( $box, $add_html, $results, PREG_SET_ORDER );
		$this->assertCount( 1, $results );
		$this->assertArrayNotHasKey( 2, $results[0] );
		$this->assertStringContainsString( 'wppuser120', $add_html );
		$this->assertStringContainsString( 'Clear search', $add_html );

		// A blocked user is not offered as a result, but stays ticked in the blocked section.
		$_GET                            = [ Users_Field::SEARCH_ARG => 'wppuser240' ];
		list( $blocked_html, $add_html ) = $this->sections( $this->page_html() );
		$this->assertSame( 0, preg_match_all( $box, $add_html ) );
		$this->assertStringContainsString( 'value="' . $ids[240] . '" checked', $blocked_html );

		// Unticking in the blocked section removes the user.
		$_GET = [];
		update_option( Settings::OPTION_BLOCKED_USERS, [ $ids[3], 987654 ] );
		$this->assertNotContains( $ids[240], get_option( Settings::OPTION_BLOCKED_USERS ) );
	}

	/**
	 * Two tabs, the quick-start card and the conditional rules.
	 *
	 * @return void
	 */
	public function test_page_has_tabs_quick_start_and_conditionals() {
		$_GET = [];
		$html = $this->page_html();
		$this->assertStringContainsString( 'data-tab="visitors"', $html );
		$this->assertStringContainsString( 'data-tab="blocked"', $html );
		$this->assertStringContainsString( 'wp-private-quickstart', $html );
		$this->assertStringContainsString( '[protected]Members-only content[/protected]', $html );
		$this->assertStringContainsString( '[loginform]', $html );
		foreach ( [ 'before_html', 'after_html', 'custom_login_page_url' ] as $name ) {
			$this->assertMatchesRegularExpression( '/<div data-field-name="[^"]*' . $name . '"[^>]* data-conditional=/', $html, "$name is conditional." );
		}
		$this->assertDoesNotMatchRegularExpression( '/<div data-field-name="[^"]*type"[^>]* data-conditional=/', $html, 'The choice itself is always shown.' );
	}

	/**
	 * The stylesheet and script load on this screen only.
	 *
	 * @return void
	 */
	public function test_assets_enqueue_only_on_the_settings_screen() {
		wp_dequeue_style( 'wp-private-admin' );
		wp_dequeue_script( 'wp-private-admin' );
		Settings_Page::enqueue_assets( 'index.php' );
		$this->assertFalse( wp_style_is( 'wp-private-admin', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'wp-private-admin', 'enqueued' ) );

		Settings_Page::enqueue_assets( 'settings_page_wp-private' );
		$this->assertTrue( wp_style_is( 'wp-private-admin', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'wp-private-admin', 'enqueued' ) );
		wp_dequeue_style( 'wp-private-admin' );
		wp_dequeue_script( 'wp-private-admin' );
	}

	/**
	 * With nobody blocked the page says so.
	 *
	 * @return void
	 */
	public function test_empty_block_list_message() {
		$_GET = [];
		delete_option( Settings::OPTION_BLOCKED_USERS );
		$html = $this->page_html();
		$this->assertStringContainsString( 'Nobody is blocked. Every logged-in user can see protected content.', $html );
		$this->assertStringContainsString( 'Add users', $html );
	}

	/**
	 * The 1.x option names appear in the code only in the upgrade routine (C-4.3).
	 *
	 * @return void
	 */
	public function test_legacy_option_names_only_in_the_upgrade_routine() {
		$root   = dirname( __DIR__, 3 );
		$files  = glob( $root . '/includes/*.php' );
		$files  = array_merge( $files, glob( $root . '/includes/*/*.php' ), [ $root . '/wp-private.php' ] );
		$names  = array_merge( array_values( Upgrade::LEGACY_OPTIONS ), [ 'wp_private_linkback_enable' ] );
		$found  = [];
		$tested = 0;
		foreach ( $files as $file ) {
			if ( 'class-upgrade.php' === basename( $file ) ) {
				continue;
			}
			++$tested;
			foreach ( file( $file ) as $number => $line ) {
				// The settings page id equals the 1.6 array's option name; it is only ever a page id (see Settings::PAGE_ID).
				if ( "\tconst PAGE_ID = 'wp_private_settings';\n" === $line ) {
					continue;
				}
				foreach ( $names as $name ) {
					if ( false !== strpos( $line, $name ) ) {
						$found[] = basename( $file ) . ':' . ( $number + 1 ) . " $name";
					}
				}
				if ( preg_match( '/[\'"]wp_private_settings[\'"]/', $line ) ) {
					$found[] = basename( $file ) . ':' . ( $number + 1 ) . ' wp_private_settings';
				}
			}
		}
		$this->assertGreaterThan( 8, $tested );
		$this->assertSame( [], $found, '1.x option names outside class-upgrade.php.' );
	}
}
