<?php
/**
 * Settings reader.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * The only reader of the six settings options (C-4.1). It never writes: the settings screen and the upgrade routine do.
 * Defaults live here, in code; nothing is stored until an administrator saves or the upgrade copies a 1.x value.
 */
class Settings {

	/**
	 * Settings page id: cassette-cmf names each option "{page id}_{field name}".
	 */
	const PAGE_ID = 'wp_private_settings';

	const OPTION_TYPE            = 'wp_private_settings_type';
	const OPTION_BEFORE_HTML     = 'wp_private_settings_before_html';
	const OPTION_AFTER_HTML      = 'wp_private_settings_after_html';
	const OPTION_AUTHORIZED_TEXT = 'wp_private_settings_authorized_text';
	const OPTION_BLOCKED_USERS   = 'wp_private_settings_blocked_users';
	const OPTION_LOGIN_URL       = 'wp_private_settings_custom_login_page_url';

	/**
	 * Replacement modes.
	 */
	const MODES = [ 'form', 'link', 'custom' ];

	/**
	 * Value of an option that is not stored, so that a stored empty string stays distinguishable from "never saved".
	 */
	const MISSING = "\0wp-private-missing";

	/**
	 * Stored value of an option, or the default when the option does not exist.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	private static function stored( $option, $default ) {
		$value = get_option( $option, self::MISSING );
		return self::MISSING === $value ? $default : $value;
	}

	/**
	 * Whether an option has been stored.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	public static function is_stored( $option ) {
		return self::MISSING !== get_option( $option, self::MISSING );
	}

	/**
	 * Replacement mode: form, link or custom.
	 *
	 * @return string
	 */
	public static function type() {
		$type = self::stored( self::OPTION_TYPE, 'form' );
		return is_string( $type ) && in_array( $type, self::MODES, true ) ? $type : 'form';
	}

	/**
	 * HTML before the prompt.
	 *
	 * @return string
	 */
	public static function before_html() {
		return self::stored_string( self::OPTION_BEFORE_HTML, self::default_before_html() );
	}

	/**
	 * A stored string option; the default when it is absent or not a string (a corrupt value never prints "Array").
	 *
	 * @param string $option  Option name.
	 * @param string $default Default.
	 * @return string
	 */
	private static function stored_string( $option, $default ) {
		$value = self::stored( $option, $default );
		return is_string( $value ) ? $value : $default;
	}

	/**
	 * HTML after the prompt.
	 *
	 * @return string
	 */
	public static function after_html() {
		return self::stored_string( self::OPTION_AFTER_HTML, self::default_after_html() );
	}

	/**
	 * Message for logged-in users on the block list.
	 *
	 * @return string
	 */
	public static function authorized_text() {
		return self::stored_string( self::OPTION_AUTHORIZED_TEXT, self::default_authorized_text() );
	}

	/**
	 * User IDs on the block list.
	 *
	 * @return int[]
	 */
	public static function blocked_users() {
		return self::normalise_ids( self::stored( self::OPTION_BLOCKED_USERS, [] ) );
	}

	/**
	 * Custom login page URL; empty means wp-login.php.
	 *
	 * @return string
	 */
	public static function custom_login_url() {
		return self::stored_string( self::OPTION_LOGIN_URL, '' );
	}

	/**
	 * Default wrapper start with a one-line prompt.
	 *
	 * @return string
	 */
	public static function default_before_html() {
		return '<div class="wp-private-prompt"><p>' . esc_html__( 'This content is for members. Please log in to read it.', 'wp-private' ) . '</p>';
	}

	/**
	 * Default wrapper end.
	 *
	 * @return string
	 */
	public static function default_after_html() {
		return '</div>';
	}

	/**
	 * Default message for blocked users (plain text).
	 *
	 * @return string
	 */
	public static function default_authorized_text() {
		return esc_html__( 'You do not have permission to view this content.', 'wp-private' );
	}

	/**
	 * Turn an array of IDs (strings or integers), or a comma-separated string, into unique positive integers.
	 *
	 * IDs of users that no longer exist are kept on purpose.
	 *
	 * @param mixed $value Stored or submitted value.
	 * @return int[]
	 */
	public static function normalise_ids( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) ) {
			return [];
		}
		$ids = [];
		foreach ( $value as $id ) {
			if ( is_int( $id ) || is_string( $id ) ) {
				$id = absint( $id );
				if ( $id > 0 ) {
					$ids[ $id ] = $id;
				}
			}
		}
		return array_values( $ids );
	}
}
