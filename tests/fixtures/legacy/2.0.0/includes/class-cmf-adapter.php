<?php
/**
 * The only door to cassette-cmf.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cmf_Adapter
 *
 * The only class that names the Strauss-prefixed library entry point (the two admin field classes extend its field
 * base class and cannot avoid it). A late framework change is absorbed here without touching stored data.
 *
 * State delivered with cassette-cmf 0.2.1 (see .ai/r01/phase-1-report.md):
 * - WP-CMF-2 (user picker), WP-CMF-3 (no library notices) and WP-CMF-4 (capability-aware HTML sanitising) are not
 *   delivered. Fallbacks: the two custom field types in Admin\, the classic renderer with the page's own callback.
 * - cassette-cmf names every option "{page id}_{field name}", which is what C-4.1 freezes.
 */
class Cmf_Adapter {

	/**
	 * Boot options that keep the library's identifiers apart from any other copy on the site.
	 */
	const BOOT = [
		'asset_handle'   => 'wp-private-cmf',
		'js_global'      => 'WpPrivateCMFFields',
		'rest_namespace' => 'wp-private-cmf/v1',
	];

	/**
	 * Library entry point.
	 */
	const CMF = '\\WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\CassetteCMF';

	/**
	 * Whether the library is installed.
	 *
	 * @return bool
	 */
	public static function available() {
		return class_exists( self::CMF );
	}

	/**
	 * Boot the library, register custom field types, then register a settings page.
	 *
	 * @param array<string, string> $field_types Field type => class name.
	 * @param array<string, mixed>  $page        Settings page configuration.
	 * @return bool False when the library is not available.
	 */
	public static function register_settings_page( array $field_types, array $page ) {
		if ( ! self::available() ) {
			return false;
		}

		$cmf = self::CMF;

		// Before any other call, so that the prefixed identifiers win.
		$cmf::boot( self::BOOT );
		foreach ( $field_types as $type => $class_name ) {
			$cmf::register_field_type( $type, $class_name );
		}
		$cmf::register_from_array( [ 'settings_pages' => [ $page ] ] );

		return true;
	}
}
