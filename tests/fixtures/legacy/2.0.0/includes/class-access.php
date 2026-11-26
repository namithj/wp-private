<?php
/**
 * Access decision.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Access
 *
 * "May this viewer see it?" for every gate type.
 */
class Access {

	const ALLOW     = 'allow';
	const BLOCKED   = 'blocked';
	const ANONYMOUS = 'anonymous';

	/**
	 * Decide for a user.
	 *
	 * @param \WP_User|null $user User; null means the current user.
	 * @return string One of the class constants.
	 */
	public static function decision( $user = null ) {
		if ( null === $user ) {
			$user = wp_get_current_user();
		}
		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return self::ANONYMOUS;
		}
		return in_array( (int) $user->ID, Settings::blocked_users(), true ) ? self::BLOCKED : self::ALLOW;
	}
}
