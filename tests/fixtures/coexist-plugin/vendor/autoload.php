<?php
/**
 * Minimal imitation of a Composer-generated vendor/autoload.php.
 *
 * Declares ComposerAutoloaderInit<suffix> with a getLoader() method, exactly as the real one does, so
 * two plugins that share a suffix fatal ("Cannot declare class").
 *
 * @package CoexistPlugin
 */

/**
 * Autoloader init class, named like Composer's.
 */
class ComposerAutoloaderInitc0e5157a1b2d4f6089a3b7c1d2e3f405 {

	/**
	 * Loader.
	 *
	 * @var callable|null
	 */
	private static $loader = null;

	/**
	 * Register the autoloader once.
	 *
	 * @return callable
	 */
	public static function getLoader() {
		if ( null !== self::$loader ) {
			return self::$loader;
		}
		self::$loader = static function ( $class_name ) {
			$prefix = 'PedalCMS\\CassetteCMF\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}
			$file = dirname( __DIR__ ) . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_readable( $file ) ) {
				require $file;
			}
		};
		spl_autoload_register( self::$loader, true, true );
		return self::$loader;
	}
}

return ComposerAutoloaderInitc0e5157a1b2d4f6089a3b7c1d2e3f405::getLoader();
