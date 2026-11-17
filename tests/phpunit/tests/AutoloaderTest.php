<?php
/**
 * The Composer autoloader keeps WP Private's own class name.
 *
 * @package WpPrivate
 */

/**
 * Two plugins that bundle Composer's autoloader with the default suffix both declare the same
 * ComposerAutoloaderInit<hash> class and the second one to load is a fatal error. composer.json pins
 * "autoloader-suffix": "WpPrivate" to prevent it.
 *
 * @group autoloader
 */
class AutoloaderTest extends WP_UnitTestCase {

	/**
	 * The loader class is the pinned one.
	 *
	 * @return void
	 */
	public function test_autoloader_uses_the_pinned_suffix() {
		$loader = require dirname( __DIR__, 3 ) . '/vendor/autoload.php';
		$this->assertInstanceOf( 'Composer\\Autoload\\ClassLoader', $loader );
		$this->assertTrue( class_exists( 'ComposerAutoloaderInitWpPrivate', false ), 'vendor/autoload.php declares ComposerAutoloaderInitWpPrivate.' );
		$this->assertSame( $loader, ComposerAutoloaderInitWpPrivate::getLoader() );
	}

	/**
	 * The setting itself is in composer.json.
	 *
	 * @return void
	 */
	public function test_composer_json_pins_the_suffix() {
		$json = json_decode(
			(string) file_get_contents( dirname( __DIR__, 3 ) . '/composer.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
			true
		);
		$this->assertSame( 'WpPrivate', $json['config']['autoloader-suffix'] );
	}
}
