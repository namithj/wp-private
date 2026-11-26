<?php
/**
 * One-time conversion of 1.x settings.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Upgrade
 *
 * The ONLY file that may name a 1.x option (C-4.3). It reads the 1.x data once, copies each value into the matching
 * per-field option, and records the version. It never deletes or writes a 1.x option, and never overwrites an option
 * that already exists.
 */
class Upgrade {

	/**
	 * Option that records the last version that ran the routine (C-1.5).
	 */
	const VERSION_OPTION = 'wp_private_version';

	/**
	 * The 1.6.x array option; its keys are the setting names.
	 */
	const LEGACY_ARRAY = 'wp_private_settings';

	/**
	 * 1.4 and 1.5 stored one option per setting: setting name => 1.x option name.
	 */
	const LEGACY_OPTIONS = [
		'type'                  => 'wp_private_replacement_type',
		'before_html'           => 'wp_private_before_html',
		'after_html'            => 'wp_private_after_html',
		'authorized_text'       => 'wp_private_not_authorized_text',
		'blocked_users'         => 'wp_private_selected_users',
		'custom_login_page_url' => 'wp_private_custom_login_page_url',
	];

	/**
	 * Setting name => new option name.
	 */
	const TARGETS = [
		'type'                  => Settings::OPTION_TYPE,
		'before_html'           => Settings::OPTION_BEFORE_HTML,
		'after_html'            => Settings::OPTION_AFTER_HTML,
		'authorized_text'       => Settings::OPTION_AUTHORIZED_TEXT,
		'blocked_users'         => Settings::OPTION_BLOCKED_USERS,
		'custom_login_page_url' => Settings::OPTION_LOGIN_URL,
	];

	/**
	 * Run the routine when the stored version is older than the code.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( version_compare( (string) get_option( self::VERSION_OPTION, '0' ), WP_PRIVATE_VERSION, '<' ) ) {
			self::run();
		}
	}

	/**
	 * Convert the 1.x settings, then record the version.
	 *
	 * The conversion runs once per site (C-4.1b): only while no version has ever been recorded, which is the state of
	 * every 1.x site and every fresh install. Later 2.x upgrades skip it, so a per-field option that 2.x removed is
	 * never refilled from the 1.x data.
	 *
	 * @return void
	 */
	public static function run() {
		if ( Settings::MISSING === get_option( self::VERSION_OPTION, Settings::MISSING ) ) {
			foreach ( self::source() as $setting => $value ) {
				self::copy( self::TARGETS[ $setting ], $value );
			}
		}
		update_option( self::VERSION_OPTION, WP_PRIVATE_VERSION );
	}

	/**
	 * The 1.x values to convert: the 1.6.x array when there is one, else the separate 1.4/1.5 options.
	 *
	 * @return array<string, mixed> Setting name => value, only for settings that were stored.
	 */
	private static function source() {
		$array = get_option( self::LEGACY_ARRAY, false );
		if ( is_array( $array ) ) {
			$values = [];
			foreach ( array_keys( self::TARGETS ) as $setting ) {
				if ( array_key_exists( $setting, $array ) ) {
					$values[ $setting ] = $array[ $setting ];
				}
			}
			return self::fix_block_list( $values );
		}

		$values = [];
		foreach ( self::LEGACY_OPTIONS as $setting => $option ) {
			$value = get_option( $option, Settings::MISSING );
			if ( Settings::MISSING !== $value ) {
				$values[ $setting ] = $value;
			}
		}
		return self::fix_block_list( $values );
	}

	/**
	 * 1.4 and 1.5 stored the block list as a comma-separated string; the new option holds an array (A-08).
	 * An array is copied as it is.
	 *
	 * @param array<string, mixed> $values Source values.
	 * @return array<string, mixed>
	 */
	private static function fix_block_list( array $values ) {
		if ( isset( $values['blocked_users'] ) && ! is_array( $values['blocked_users'] ) ) {
			$values['blocked_users'] = Settings::normalise_ids( $values['blocked_users'] );
		}
		return $values;
	}

	/**
	 * Add an option byte for byte, unless it already exists.
	 *
	 * WordPress applies a registered option's sanitize callback on every add_option(); that callback exists only
	 * after admin_init, but another plugin may attach one earlier. The callbacks are detached for the copy and put
	 * back afterwards, so that stored markup is never rewritten (C-4.5).
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Value.
	 * @return void
	 */
	private static function copy( $option, $value ) {
		if ( Settings::is_stored( $option ) ) {
			return;
		}

		$hook  = 'sanitize_option_' . $option;
		$saved = isset( $GLOBALS['wp_filter'][ $hook ] ) ? clone $GLOBALS['wp_filter'][ $hook ] : null;
		remove_all_filters( $hook );

		add_option( $option, $value );

		if ( null !== $saved ) {
			foreach ( $saved->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					add_filter( $hook, $callback['function'], $priority, $callback['accepted_args'] );
				}
			}
		}
	}
}
