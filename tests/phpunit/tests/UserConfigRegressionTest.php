<?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Test class.
/**
 * The user-config regression group: configs 01 to 12 through scenarios S1 to S11 (data half), S10 and S13.
 *
 * Run with bin/test-user-configs.sh (composer test:configs); it needs the guarded bootstrap and a throwaway core.
 * Snapshots live in tests/user-configs/snapshots/<slug>/<scenario>.txt; WPP_UPDATE_SNAPSHOTS=1 rewrites them (never in CI).
 *
 * @package WpPrivate
 */

require_once __DIR__ . '/../includes/class-wpp-snapshot-store.php';
require_once __DIR__ . '/../includes/class-wpp-scenarios.php';

/**
 * @group user-configs
 */
class UserConfigRegressionTest extends WP_UnitTestCase {

	/**
	 * Refuse to run outside the guarded bootstrap.
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		if ( ! defined( 'WPP_USER_CONFIGS_BOOTSTRAP' ) ) {
			self::markTestSkipped( 'Run this group with bin/test-user-configs.sh (composer test:configs).' );
		}
		parent::set_up_before_class();
	}

	/**
	 * Undo the config.
	 *
	 * @return void
	 */
	public function tear_down() {
		WPP_User_Config_Loader::reset();
		wp_set_current_user( 0 );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/**
	 * Config slugs.
	 *
	 * @return array<string, array{0:string}>
	 */
	public function configs() {
		$out = [];
		foreach ( WPP_User_Config_Loader::slugs() as $slug ) {
			$out[ $slug ] = [ $slug ];
		}
		return $out;
	}

	/**
	 * Apply a config and run every scenario that applies; compare with the snapshots.
	 *
	 * @dataProvider configs
	 * @param string $slug Config slug.
	 * @return void
	 */
	public function test_user_config( $slug ) {
		$config = WPP_User_Config_Loader::load( $slug );
		if ( ! empty( $config['multisite'] ) && ! is_multisite() ) {
			$this->fail( $slug . ' is a multisite config: run it with WP_TESTS_MULTISITE=1.' );
		}
		if ( ! empty( $config['coexist'] ) && ! defined( 'COEXIST_PLUGIN_VERSION' ) ) {
			$this->fail( $slug . ' needs the fixture plugin: run it with WPP_LOAD_COEXIST_PLUGIN=1.' );
		}

		WPP_User_Config_Loader::apply( $config );
		$engine = new WPP_Scenarios( $this, $config, true );
		$engine->run_requests();

		if ( $engine->has_legacy_data() && ! in_array( 'S11', $config['skip'], true ) ) {
			$engine->s11_data();
		}
		if ( ! in_array( 'S10', $config['skip'], true ) ) {
			$engine->s10();
			if ( ! empty( $config['multisite'] ) ) {
				$engine->s10_unfiltered_html();
			}
		}
		if ( $engine->has_legacy_data() && ! in_array( 'S11', $config['skip'], true ) ) {
			$engine->s11_after_change();
		}
		if ( ! empty( $config['coexist'] ) ) {
			$this->coexistence( $engine );
		}

		$failures = $engine->failures();
		$store    = new WPP_Snapshot_Store( dirname( __DIR__, 2 ) . '/user-configs/snapshots' );
		foreach ( $engine->snaps() as $scenario => $text ) {
			$message = $store->check( $slug . '/' . $scenario . '.txt', WPP_Normaliser::text( $text ) );
			if ( null !== $message ) {
				$failures[] = $message;
			}
		}
		foreach ( $store->stale( $slug ) as $stale ) {
			$failures[] = 'Stale snapshot ' . $stale . ' (no scenario produced it).';
		}
		$this->assertSame( [], $failures, implode( "\n\n", $failures ) );
	}

	/**
	 * Config 10: WP Private and a second plugin that bundles the same libraries, unprefixed, with its own Composer autoloader (tests/fixtures/coexist-plugin).
	 *
	 * Reaching this point already proves there was no fatal (two autoloaders, one class name) and no notice.
	 *
	 * @param WPP_Scenarios $engine Engine, to record the snapshot through.
	 * @return void
	 */
	private function coexistence( WPP_Scenarios $engine ) {
		unset( $engine );
		$this->assertTrue( defined( 'WP_PRIVATE_VERSION' ), 'WP Private is loaded.' );
		$this->assertTrue( defined( 'COEXIST_PLUGIN_VERSION' ), 'The fixture plugin is loaded.' );
		$this->assertTrue( shortcode_exists( 'protected' ), 'The gate shortcode is registered.' );

		// Two separate copies of the library, in two namespaces: no cross-wiring.
		$ours_class   = 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Manager';
		$theirs_class = 'PedalCMS\\CassetteCMF\\CassetteCMF';
		$this->assertTrue( class_exists( $ours_class ), 'WP Private resolves its cassette classes under WpPrivate\\Vendor.' );
		$this->assertTrue( class_exists( $theirs_class ), 'The fixture resolves its own unprefixed class.' );
		$ours   = new ReflectionClass( $ours_class );
		$theirs = new ReflectionClass( $theirs_class );
		$this->assertNotSame( $ours->getFileName(), $theirs->getFileName(), 'Two copies, two files: nothing is shared.' );
		$this->assertStringContainsString( '/vendor-prefixed/', (string) $ours->getFileName(), 'WP Private loads its own prefixed copy.' );
		$this->assertStringContainsString( '/fixtures/coexist-plugin/', (string) $theirs->getFileName(), 'The fixture class comes from the fixture.' );
		$this->assertFalse( class_exists( 'PedalCMS\\CassetteCMF\\Core\\Manager', false ), 'WP Private did not leak an unprefixed library class.' );
		$this->assertTrue( class_exists( 'ComposerAutoloaderInitWpPrivate', false ), 'WP Private keeps its pinned autoloader class.' );
		$this->assertTrue( class_exists( 'ComposerAutoloaderInitc0e5157a1b2d4f6089a3b7c1d2e3f405', false ), 'The fixture has its own autoloader class.' );

		// Both settings pages are registered, each once.
		global $submenu, $menu;
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		do_action( 'admin_menu' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		do_action( 'admin_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$slugs = [];
		foreach ( (array) $menu as $item ) {
			$slugs[] = $item[2];
		}
		foreach ( (array) $submenu as $items ) {
			foreach ( $items as $item ) {
				$slugs[] = $item[2];
			}
		}
		$private = array_filter(
			$slugs,
			static function ( $slug ) {
				return false !== strpos( (string) $slug, 'wp-private' );
			}
		);
		$this->assertCount( count( array_unique( $private ) ), $private, 'WP Private registered a menu entry twice.' );
		$this->assertCount( 1, $private, 'WP Private has exactly one admin entry.' );
		$this->assertSame( 1, count( array_keys( $slugs, 'coexist', true ) ), 'The fixture registered its settings page once.' );

		// No cross-wired assets, either way: a gated page loads nothing of WP Private's, and the fixture's handle is its own.
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( WPP_User_Config_Loader::ids()['gated'] ) );
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$handles = array_merge( wp_scripts()->queue, wp_styles()->queue );
		$mine    = array_filter(
			$handles,
			static function ( $handle ) {
				return false !== stripos( $handle, 'wp-private' );
			}
		);
		$this->assertSame( [], array_values( $mine ), 'WP Private enqueued a front-end asset.' );
		$this->assertNotContains( 'coexist-admin', $handles, 'The fixture enqueued its admin style on the front end.' );

		$wp_private_page      = get_plugin_page_hookname( 'wp-private', 'options-general.php' );
		$coexist_page         = get_plugin_page_hookname( 'coexist', 'options-general.php' );
		$GLOBALS['wp_styles'] = null;
		set_current_screen( $coexist_page );
		do_action( 'admin_enqueue_scripts', $coexist_page ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$this->assertContains( 'coexist-admin', wp_styles()->queue, 'The fixture style is enqueued on the fixture page.' );
		foreach ( wp_styles()->queue as $handle ) {
			$this->assertFalse( 0 === strpos( $handle, 'wp-private' ), 'A WP Private handle is enqueued on the fixture page.' );
		}
		foreach ( wp_styles()->registered as $handle => $style ) {
			$this->assertFalse( 0 === strpos( $handle, 'wp-private' ) && false !== strpos( (string) $style->src, '/fixtures/coexist-plugin/' ), 'A WP Private handle points at the fixture.' );
			$this->assertFalse( 0 === strpos( $handle, 'coexist' ) && false === strpos( (string) $style->src, '/fixtures/coexist-plugin/' ), 'A fixture handle points at WP Private.' );
		}
		$this->assertNotSame( $wp_private_page, $coexist_page, 'Two distinct admin pages.' );

		// The settings option names do not overlap, and a save by the fixture leaves WP Private's options alone.
		$registered = array_keys( get_registered_settings() );
		$this->assertSame( array_values( array_unique( $registered ) ), array_values( $registered ), 'No setting registered twice.' );
		$this->assertContains( 'coexist_settings_title', $registered, 'The fixture registered its own option.' );
		foreach ( $registered as $name ) {
			$this->assertFalse( 0 === strpos( $name, 'wp_private_' ) && 0 === strpos( $name, 'coexist_' ), 'An option name belongs to both plugins.' );
		}
		$before = $this->wp_private_options();
		$this->assertNotSame( [], $before, 'WP Private options exist to compare.' );
		update_option( 'coexist_settings_title', 'Saved by the fixture' );
		$this->assertSame( $before, $this->wp_private_options(), 'A save by the fixture changed a WP Private option.' );
	}

	/**
	 * All stored options of WP Private.
	 *
	 * @return array<string, mixed>
	 */
	private function wp_private_options() {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wp\\_private\\_%' ORDER BY option_name", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Test.
		return wp_list_pluck( $rows, 'option_value', 'option_name' );
	}
}
