<?php
/**
 * HTML textarea field.
 *
 * @package WpPrivate
 */

namespace WpPrivate\Admin;

use WpPrivate\Vendor\PedalCMS\CassetteCMF\Field\Fields\Textarea_Field;
use WpPrivate\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Html_Field
 *
 * A textarea for HTML (fallback for WP-CMF-4). The stock textarea runs wp_kses_post for everybody; this one keeps the
 * raw text for users with the unfiltered_html capability and runs wp_kses_post for everybody else (F004). It shows
 * the code default while the option has never been stored, and escapes the stored value so that a stored
 * "</textarea>" or "&amp;" reaches the browser, and comes back, intact.
 */
class Html_Field extends Textarea_Field {

	/**
	 * Field type the settings page registers this class under.
	 */
	const TYPE = 'wp_private_html';

	/**
	 * Render the textarea.
	 *
	 * @param mixed $value Stored value ('' when the option does not exist).
	 * @return string HTML.
	 */
	public function render( $value = null ): string {
		if ( ! Settings::is_stored( $this->get_option_name( Settings::PAGE_ID ) ) ) {
			$value = $this->config['default'];
		}

		$attributes = [
			'id'    => $this->get_field_id(),
			'name'  => $this->name,
			'class' => 'large-text code',
			'rows'  => $this->config['rows'],
			'cols'  => $this->config['cols'],
		];

		return $this->render_wrapper_start()
			. '<textarea' . $this->build_attributes( $attributes ) . '>' . esc_textarea( is_string( $value ) ? $value : '' ) . '</textarea>'
			. $this->render_description()
			. $this->render_wrapper_end();
	}

	/**
	 * Raw for users who may post unfiltered HTML, wp_kses_post for the rest.
	 *
	 * @param mixed $input Submitted value.
	 * @return string
	 */
	public function sanitize( $input ) {
		if ( ! is_string( $input ) ) {
			return '';
		}

		// An unchanged save keeps the stored bytes (C-4.5, S10). options.php trims the posted value and browsers
		// post CRLF, so the stored value is compared trimmed and with normalised line endings.
		$stored = get_option( $this->get_option_name( Settings::PAGE_ID ), Settings::MISSING );
		if ( is_string( $stored ) && Settings::MISSING !== $stored && self::comparable( $stored ) === self::comparable( $input ) ) {
			return $stored;
		}

		return current_user_can( 'unfiltered_html' ) ? $input : wp_kses_post( $input );
	}

	/**
	 * A value as it comes back from a browser through options.php: CRLF as LF, trimmed.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function comparable( $value ) {
		return trim( str_replace( "\r\n", "\n", $value ) );
	}
}
