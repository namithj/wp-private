<?php
/**
 * Shortcodes.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Shortcodes
 *
 * [protected] and [loginform].
 */
class Shortcodes {

	/**
	 * Register both shortcodes.
	 *
	 * @return void
	 */
	public static function register() {
		add_shortcode( 'protected', [ self::class, 'protected_content' ] );
		add_shortcode( 'loginform', [ self::class, 'login_form' ] );
	}

	/**
	 * [protected]...[/protected]
	 *
	 * @param array<string, string>|string $atts    Attributes (none are used).
	 * @param string|null                   $content Enclosed content.
	 * @return string
	 */
	public static function protected_content( $atts, $content = null ) {
		switch ( Access::decision() ) {
			case Access::ALLOW:
				return do_shortcode( (string) $content );
			case Access::BLOCKED:
				return Settings::authorized_text();
			default:
				return Prompt::render();
		}
	}

	/**
	 * [loginform]: a greeting and a logout link for logged-in users, a login form for visitors.
	 *
	 * @return string
	 */
	public static function login_form() {
		$here = Prompt::local_url( Prompt::current_url() );

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return sprintf(
				/* translators: 1: user name, 2: "Log out" link. */
				esc_html__( 'Hello, %1$s. %2$s', 'wp-private' ),
				esc_html( $user->user_login ),
				'<a href="' . esc_url( wp_logout_url( $here ) ) . '">' . esc_html__( 'Log out', 'wp-private' ) . '</a>'
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only: only decides where to go after logging in.
		$redirect = isset( $_GET['redirect'] ) && is_string( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : '';

		Prompt::enqueue_style();
		return Prompt::login_form( '' !== $redirect ? wp_validate_redirect( $redirect, $here ) : $here );
	}
}
