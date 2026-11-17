<?php
/**
 * Prompt renderer.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Prompt
 *
 * "What does a logged-out visitor see where the gated content was?" by mode and context.
 */
class Prompt {

	const CONTEXT_HTML = 'html';
	const CONTEXT_FEED = 'feed';
	const CONTEXT_REST = 'rest';

	/**
	 * Number of login forms built so far in this request, for unique element IDs.
	 *
	 * @var int
	 */
	private static $forms = 0;

	/**
	 * Where the output goes: html, feed or rest.
	 *
	 * @return string
	 */
	public static function context() {
		// Covers a standalone REST request and an internal one (rest_do_request()).
		if ( wp_is_rest_endpoint() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return self::CONTEXT_REST;
		}
		if ( is_feed() ) {
			return self::CONTEXT_FEED;
		}
		return self::CONTEXT_HTML;
	}

	/**
	 * The prompt for a logged-out visitor, by the stored mode and the current context.
	 *
	 * @param string|null $context Context; null detects it.
	 * @return string HTML.
	 */
	public static function render( $context = null ) {
		$mode = Settings::type();
		if ( 'custom' === $mode ) {
			return '';
		}
		if ( null === $context ) {
			$context = self::context();
		}

		$return = self::return_url( $context );

		if ( 'form' === $mode && self::CONTEXT_HTML === $context ) {
			$body = self::login_form( $return );
		} else {
			$body = self::links( $return, 'link' === $mode );
		}

		return Settings::before_html() . $body . Settings::after_html();
	}

	/**
	 * A login form that is returned, not printed, with element IDs that are unique per call.
	 *
	 * @param string $redirect Where to go after logging in; checked to be local.
	 * @return string HTML.
	 */
	public static function login_form( $redirect ) {
		++self::$forms;
		$n = self::$forms;

		return (string) wp_login_form(
			[
				'echo'        => false,
				'redirect'    => self::local_url( $redirect ),
				'form_id'     => 'wp-private-loginform-' . $n,
				'id_username' => 'wp-private-user-login-' . $n,
				'id_password' => 'wp-private-user-pass-' . $n,
				'id_remember' => 'wp-private-rememberme-' . $n,
				'id_submit'   => 'wp-private-submit-' . $n,
			]
		);
	}

	/**
	 * The login link, and the register link when asked for and registration is open.
	 *
	 * @param string $return        URL to return to after logging in.
	 * @param bool   $with_register Whether to add the register link.
	 * @return string HTML.
	 */
	private static function links( $return, $with_register ) {
		$login = '<a class="wp-private-login" href="' . esc_url( self::login_url( $return ) ) . '">' . esc_html__( 'Log in', 'wp-private' ) . '</a>';

		if ( $with_register && get_option( 'users_can_register' ) ) {
			$register = '<a class="wp-private-register" href="' . esc_url( wp_registration_url() ) . '">' . esc_html__( 'Register', 'wp-private' ) . '</a>';
			return sprintf(
				/* translators: 1: "Log in" link, 2: "Register" link. */
				esc_html__( '%1$s or %2$s to read this content.', 'wp-private' ),
				$login,
				$register
			);
		}

		return $login;
	}

	/**
	 * Login URL: the custom login page when one is set, wp-login.php otherwise. The return URL is encoded once.
	 *
	 * @param string $return URL to return to after logging in.
	 * @return string
	 */
	public static function login_url( $return ) {
		$custom = Settings::custom_login_url();
		if ( '' !== $custom ) {
			return add_query_arg( 'redirect', rawurlencode( $return ), $custom );
		}
		return wp_login_url( $return );
	}

	/**
	 * The URL to return to after logging in: the current page in HTML, the permalink in feeds and REST responses.
	 *
	 * @param string $context Context.
	 * @return string A local URL.
	 */
	public static function return_url( $context ) {
		if ( self::CONTEXT_HTML === $context ) {
			return self::local_url( self::current_url() );
		}
		$permalink = get_permalink();
		return self::local_url( $permalink ? $permalink : home_url( '/' ) );
	}

	/**
	 * The URL of the current request, made absolute on the site's own host.
	 *
	 * @return string
	 */
	public static function current_url() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cleaned by esc_url_raw() below.
		if ( ! is_string( $uri ) || '' === $uri || '/' !== $uri[0] ) {
			return home_url( '/' );
		}

		$home = wp_parse_url( home_url() );
		$base = ( isset( $home['scheme'] ) ? $home['scheme'] : 'http' ) . '://' . ( isset( $home['host'] ) ? $home['host'] : '' );
		if ( ! empty( $home['port'] ) ) {
			$base .= ':' . $home['port'];
		}

		return esc_url_raw( $base . $uri );
	}

	/**
	 * Keep a URL only when it points at this site.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function local_url( $url ) {
		$fallback = get_permalink();
		return wp_validate_redirect( $url, $fallback ? $fallback : home_url( '/' ) );
	}
}
