<?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Test class.
/**
 * The upgrade-path group: scenarios S11 (1.x files to this build) and S12 (this build's data under the 1.6.1 files).
 *
 * One config is tested in several PHP processes against ONE database, driven by bin/test-upgrade-path.sh
 * (composer test:upgrade) through WPP_PHASE. Nothing is rolled back between processes.
 *
 *   old      the config's own plugin version (WPP_PLUGIN_FILE) is active; the config is written; outputs captured
 *   new      this build replaces the files; its first request runs the upgrade for real; outputs and data checked
 *            against the frozen snapshots; the difference to the old outputs is compared with the reviewed list
 *   down     the 1.6.1 files are put back over this build's data (unsupported path, C-4.1c)
 *   first2x  a site first installed on 2.x (this build, no 1.x data)
 *   down2x   1.6.1 files over that site: gated content hidden from everyone
 *   prevold  S14: the previous 2.x release (tests/fixtures/legacy/<version>) is active, the config is written and upgraded by it
 *   prevnew  S14: this build over that data: identical to the frozen snapshots and to what the previous release rendered
 *
 * @package WpPrivate
 */

require_once __DIR__ . '/../includes/class-wpp-snapshot-store.php';
require_once __DIR__ . '/../includes/class-wpp-scenarios.php';

/**
 * @group upgrade-path
 */
class UpgradePathTest extends WP_UnitTestCase {

	/**
	 * Refuse to run outside the guarded bootstrap.
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		if ( ! defined( 'WPP_USER_CONFIGS_BOOTSTRAP' ) || false === getenv( 'WPP_PHASE' ) ) {
			self::markTestSkipped( 'Run this group with bin/test-upgrade-path.sh (composer test:upgrade).' );
		}
		// Not parent::set_up_before_class(): core's version empties the database.
	}

	/**
	 * Keep the data for the next process: core's version empties the database.
	 *
	 * @return void
	 */
	public static function tear_down_after_class() {
		// Intentionally empty.
	}

	/**
	 * The phases share one database: do not wrap the test in a rolled-back transaction.
	 *
	 * @return void
	 */
	public function start_transaction() {
		// Intentionally empty.
	}

	/**
	 * Forget the config's filters; the data stays.
	 *
	 * @return void
	 */
	public function tear_down() {
		WPP_User_Config_Loader::reset();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Configs that take part: stored by a 1.x version, single site.
	 *
	 * @return array<string, array{0:string}>
	 */
	public function configs() {
		$out = [];
		foreach ( WPP_User_Config_Loader::slugs() as $slug ) {
			$config = WPP_User_Config_Loader::load( $slug );
			if ( '2.x' === $config['version'] || ! empty( $config['multisite'] ) || in_array( 'S11', $config['skip'], true ) ) {
				continue;
			}
			$out[ $slug ] = [ $slug ];
		}
		$out['01-fresh-default'] = [ '01-fresh-default' ];
		return $out;
	}

	/**
	 * Where the old phase leaves its captured outputs for the new phase.
	 *
	 * @param string $slug Config slug.
	 * @return string
	 */
	private function out_dir( $slug ) {
		$base = getenv( 'WPP_UPGRADE_OUT' );
		return ( false === $base || '' === $base ? sys_get_temp_dir() . '/wpp-upgrade-out' : $base ) . '/' . $slug;
	}

	/**
	 * Run one phase for one config.
	 *
	 * @dataProvider configs
	 * @param string $slug Config slug.
	 * @return void
	 */
	public function test_phase( $slug ) {
		$phase  = (string) getenv( 'WPP_PHASE' );
		$config = WPP_User_Config_Loader::load( $slug );
		$legacy = ( '2.x' !== $config['version'] );
		$method = 'phase_' . $phase;
		if ( ! method_exists( $this, $method ) ) {
			$this->fail( 'Unknown WPP_PHASE ' . $phase );
		}
		if ( ! in_array( $phase, [ 'prevold', 'prevnew' ], true ) && in_array( $phase, [ 'first2x', 'down2x' ], true ) !== ( '01-fresh-default' === $slug ) ) {
			$this->markTestSkipped( 'Phase ' . $phase . ' does not apply to ' . $slug . '.' );
		}
		unset( $legacy );
		$this->$method( $config );
	}

	/**
	 * The old build is active (the plugin file of the config's own version) and the config is written to the database.
	 *
	 * @param array $config Config.
	 * @return void
	 */
	private function phase_old( array $config ) {
		$this->assertFalse( defined( 'WP_PRIVATE_VERSION' ), 'The old phase must not have this build loaded.' );
		$this->assertTrue( shortcode_exists( 'protected' ), 'A 1.x version is loaded.' );
		WPP_User_Config_Loader::apply( $config, true, false );
		if ( '0' === getenv( 'WPP_CAPTURE' ) ) {
			// 1.4 and 1.5 do not run on PHP 8.x (fatal on a WP_User cast), so their output cannot be captured: only the data is written.
			$this->assertTrue( true );
			return;
		}
		$engine = new WPP_Scenarios( $this, $config, false );
		$dir    = $this->out_dir( $config['slug'] );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		}
		foreach ( $engine->capture_for_diff() as $scenario => $text ) {
			file_put_contents( $dir . '/' . $scenario . '.txt', WPP_Normaliser::text( $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		$this->assertTrue( true );
	}

	/**
	 * This build's files over the old data. Its first request has already run the upgrade (plugins_loaded).
	 *
	 * @param array $config Config.
	 * @return void
	 */
	private function phase_new( array $config ) {
		$this->assertTrue( defined( 'WP_PRIVATE_VERSION' ), 'This build is loaded.' );
		WPP_User_Config_Loader::install_environment();
		WPP_User_Config_Loader::restore_posts( $config );
		$this->assertSame( WP_PRIVATE_VERSION, get_option( 'wp_private_version' ), 'The first request on the new files ran the upgrade routine.' );

		$engine = new WPP_Scenarios( $this, $config, true );
		$engine->run_requests();
		$engine->s11_data();
		$failures = $engine->failures();

		// The upgraded site renders exactly what the frozen snapshots say a site with this data renders.
		$store = new WPP_Snapshot_Store( dirname( __DIR__, 2 ) . '/user-configs/snapshots' );
		foreach ( $engine->snaps() as $scenario => $text ) {
			$message = $store->check( $config['slug'] . '/' . $scenario . '.txt', WPP_Normaliser::text( $text ) );
			if ( null !== $message ) {
				$failures[] = 'After the upgrade: ' . $message;
			}
		}

		// The difference to what the old files rendered is the list of intended changes (brief section 8).
		$diff = '';
		if ( ! is_file( $this->out_dir( $config['slug'] ) . '/S1.txt' ) ) {
			$this->assertSame( [], $failures, implode( "\n\n", $failures ) );
			return;
		}
		foreach ( [ 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8' ] as $scenario ) {
			$old_file = $this->out_dir( $config['slug'] ) . '/' . $scenario . '.txt';
			if ( ! is_file( $old_file ) || ! isset( $engine->snaps()[ $scenario ] ) ) {
				continue;
			}
			$old = file_get_contents( $old_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$new = WPP_Normaliser::text( $engine->snaps()[ $scenario ] );
			if ( $old !== $new ) {
				$diff .= '##### ' . $scenario . "\n" . preg_replace( '/^--- .*\n\+\+\+ .*\n/', '', WPP_Snapshot_Store::diff( $old, $new, 0 ) ) . "\n";
			} else {
				$diff .= '##### ' . $scenario . " identical\n";
			}
		}
		$message = $store->check( 'upgrade/' . $config['slug'] . '-from-' . $config['version'] . '.diff', $diff );
		if ( null !== $message ) {
			$failures[] = 'Difference to ' . $config['version'] . ' is not the reviewed list: ' . $message;
		}
		$this->assertSame( [], $failures, implode( "\n\n", $failures ) );
	}

	/**
	 * S14, previous release active: it upgrades the config's data itself (its first request) and its outputs are captured.
	 *
	 * @param array $config Config.
	 * @return void
	 */
	private function phase_prevold( array $config ) {
		$this->assertTrue( defined( 'WP_PRIVATE_VERSION' ), 'The previous release is loaded.' );
		$this->assertSame( getenv( 'WPP_PREV_VERSION' ), WP_PRIVATE_VERSION, 'The previous release, not this build, is loaded.' );
		WPP_User_Config_Loader::apply( $config, true, true );
		$engine = new WPP_Scenarios( $this, $config, false );
		$dir    = $this->out_dir( $config['slug'] );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		}
		foreach ( $engine->capture_for_diff() as $scenario => $text ) {
			file_put_contents( $dir . '/' . $scenario . '.txt', WPP_Normaliser::text( $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		$this->assertTrue( true );
	}

	/**
	 * S14, this build over the previous release's data: no listed change is allowed, everything is identical.
	 *
	 * @param array $config Config.
	 * @return void
	 */
	private function phase_prevnew( array $config ) {
		$this->assertTrue( defined( 'WP_PRIVATE_VERSION' ), 'This build is loaded.' );
		$this->assertNotSame( getenv( 'WPP_PREV_VERSION' ), WP_PRIVATE_VERSION, 'This build, not the previous release, is loaded.' );
		WPP_User_Config_Loader::install_environment();
		WPP_User_Config_Loader::restore_posts( $config );
		$this->assertSame( WP_PRIVATE_VERSION, get_option( 'wp_private_version' ), 'The first request on the new files ran the upgrade routine.' );

		$engine = new WPP_Scenarios( $this, $config, true );
		$engine->run_requests();
		$failures = $engine->failures();

		$store = new WPP_Snapshot_Store( dirname( __DIR__, 2 ) . '/user-configs/snapshots' );
		foreach ( $engine->snaps() as $scenario => $text ) {
			$message = $store->check( $config['slug'] . '/' . $scenario . '.txt', WPP_Normaliser::text( $text ) );
			if ( null !== $message ) {
				$failures[] = 'After the upgrade: ' . $message;
			}
		}

		$compared = 0;
		foreach ( [ 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8' ] as $scenario ) {
			$old_file = $this->out_dir( $config['slug'] ) . '/' . $scenario . '.txt';
			if ( ! is_file( $old_file ) || ! isset( $engine->snaps()[ $scenario ] ) ) {
				continue;
			}
			++$compared;
			$old = file_get_contents( $old_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$new = WPP_Normaliser::text( $engine->snaps()[ $scenario ] );
			if ( $old !== $new ) {
				$failures[] = $scenario . ' differs from ' . getenv( 'WPP_PREV_VERSION' ) . ":\n" . WPP_Snapshot_Store::diff( $old, $new, 0 );
			}
		}
		$this->assertGreaterThan( 0, $compared, 'The previous release left outputs to compare.' );
		$this->assertSame( [], $failures, implode( "\n\n", $failures ) );
	}

	/**
	 * The 1.6.1 files over this build's data: no PHP error (a notice would fail the test), a bare [protected] still gates.
	 *
	 * @param array $config Config.
	 * @return void
	 */
	private function phase_down( array $config ) {
		$this->assertFalse( defined( 'WP_PRIVATE_VERSION' ), 'The down phase must have the 1.6.1 files loaded, not this build.' );
		$this->assertTrue( shortcode_exists( 'protected' ), 'The 1.x files are loaded.' );
		WPP_User_Config_Loader::install_environment();
		WPP_User_Config_Loader::restore_posts( $config );
		$this->assertNotNull( get_option( 'wp_private_settings_type', null ) !== null ? 1 : null, 'The data of this build is in the database.' );

		$engine = new WPP_Scenarios( $this, $config, true );
		$engine->hidden_from( [ '', $config['blocked'] ] );
		if ( is_array( get_option( 'wp_private_settings' ) ) ) {
			$engine->gate_still_prompts();
		}
		$this->assertSame( [], $engine->failures(), implode( "\n", $engine->failures() ) );
	}

	/**
	 * A site first installed on 2.x: this build writes the config (no 1.x data) and runs its first request.
	 *
	 * @param array $config Config.
	 * @return void
	 */
	private function phase_first2x( array $config ) {
		$this->assertTrue( defined( 'WP_PRIVATE_VERSION' ), 'This build is loaded.' );
		WPP_User_Config_Loader::apply( $config, true, true );
		$this->assertSame( WP_PRIVATE_VERSION, get_option( 'wp_private_version' ) );
		$this->assertNull( get_option( 'wp_private_settings', null ), 'A 2.x install has no 1.x array.' );
	}

	/**
	 * 1.6.1 files over that site: gated content is hidden from everyone, logged in or not.
	 *
	 * @param array $config Config.
	 * @return void
	 */
	private function phase_down2x( array $config ) {
		$this->assertFalse( defined( 'WP_PRIVATE_VERSION' ), 'The down phase must have the 1.6.1 files loaded.' );
		WPP_User_Config_Loader::install_environment();
		WPP_User_Config_Loader::restore_posts( $config );
		$engine = new WPP_Scenarios( $this, $config, true );
		$engine->hidden_from( [ '', 'member', 'admin' ] );
		$this->assertSame( [], $engine->failures(), implode( "\n", $engine->failures() ) );
	}
}
