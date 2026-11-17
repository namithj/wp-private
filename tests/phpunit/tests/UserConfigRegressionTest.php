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
		if ( ! empty( $config['swiftforms'] ) && ! defined( 'SWIFTFORMS_VERSION' ) ) {
			$this->fail( $slug . ' needs SwiftForms: run it with WPP_LOAD_SWIFTFORMS=1.' );
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
		if ( ! empty( $config['swiftforms'] ) ) {
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
	 * Config 10: WP Private and SwiftForms (with its own bundled, prefixed cassette libraries) side by side.
	 *
	 * @param WPP_Scenarios $engine Engine, to record the snapshot through.
	 * @return void
	 */
	private function coexistence( WPP_Scenarios $engine ) {
		unset( $engine );
		$this->assertTrue( defined( 'WP_PRIVATE_VERSION' ), 'WP Private is loaded.' );
		$this->assertTrue( defined( 'SWIFTFORMS_VERSION' ), 'SwiftForms is loaded.' );
		$this->assertTrue( shortcode_exists( 'protected' ), 'The gate shortcode is registered.' );

		// Two separate copies of the library, in two namespaces.
		$ours   = new ReflectionClass( 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Manager' );
		$theirs = class_exists( 'SwiftForms\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Manager' ) ? new ReflectionClass( 'SwiftForms\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Manager' ) : null;
		$this->assertNotNull( $theirs, 'SwiftForms ships its own prefixed copy of the library.' );
		$this->assertNotSame( $ours->getFileName(), $theirs->getFileName(), 'Two copies, two files: nothing is shared.' );

		// No duplicate registration: one top-level or settings entry per slug, one option group each.
		global $submenu, $menu;
		do_action( 'admin_menu' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
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

		// No cross-wired assets: a gated page with SwiftForms active loads nothing of either plugin's front end.
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
		$swift = array_filter(
			$handles,
			static function ( $handle ) {
				return 0 === stripos( $handle, 'swiftforms' );
			}
		);
		$this->assertSame( [], array_values( $swift ), 'SwiftForms enqueued an asset on a page with no form.' );

		// The settings option names do not overlap.
		$registered = array_keys( get_registered_settings() );
		$this->assertSame( array_values( array_unique( $registered ) ), array_values( $registered ), 'No setting registered twice.' );
		foreach ( $registered as $name ) {
			$this->assertFalse( 0 === strpos( $name, 'wp_private_' ) && 0 === strpos( $name, 'swiftforms_' ), 'An option name belongs to both plugins.' );
		}
	}
}
