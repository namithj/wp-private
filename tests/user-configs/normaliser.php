<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Fixture class name WPP_Normaliser is fixed by the R01 plan.
/**
 * Output normaliser for the user-config snapshots (R01).
 *
 * Snapshots must change only when WP Private's output changes, never because WordPress core or
 * the clock changed. Everything this class rewrites is listed here and has a test in
 * NormaliserTest.php. Nothing else is touched: a snapshot that differs is a real difference.
 *
 * HTML rules, in this order:
 *  1. Line endings: CRLF and CR become LF.
 *  2. Whitespace between tags: a run of whitespace between ">" and "<" becomes one LF.
 *  3. Asset version strings: the "ver" query argument is removed from URLs.
 *  4. Hashed layout classes: "wp-container-core-group-is-layout-9f0d2b3c" and "wp-container-61a3b2c1"
 *     both become "wp-container-{HASH}"; "wp-elements-<32 hex>" becomes "wp-elements-{HASH}".
 *  5. Nonces: ten hex digits after "_wpnonce=" or in a "_wpnonce" input become "{NONCE}".
 *  6. Core-version classes: core 7.0 adds "wp-block-paragraph" to every paragraph block, 6.8 does not.
 *     The class is removed (and an emptied class attribute with it).
 *  7. Generator version: "https://wordpress.org/?v=6.8.12" becomes "https://wordpress.org/?v={WP}".
 *  8. Skip-link target: newer core gives the first <main> of a block theme the id "wp--skip-link--target"; the
 *     attribute is removed.
 *  9. Edges: leading and trailing whitespace is trimmed.
 * The home URL "http://example.org" is deliberately NOT rewritten.
 *
 * Every rule is idempotent: html( html( x ) ) === html( x ).
 */
class WPP_Normaliser {

	/**
	 * Normalise an HTML (or XML) fragment.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function html( $html ) {
		$html = str_replace( [ "\r\n", "\r" ], "\n", (string) $html );

		$html = preg_replace( '/>\s+</', ">\n<", $html );

		$html = preg_replace( '/\?ver=[^&"\'#\s<>]*&(?:amp;|#038;)?/', '?', $html );
		$html = preg_replace( '/(?:\?|&(?:amp;|#038;)?)ver=[^&"\'#\s<>]*/', '', $html );

		$html = preg_replace( '/wp-container-(?:[a-z0-9-]+-)?[0-9a-f]{8}\b/', 'wp-container-{HASH}', $html );
		$html = preg_replace( '/wp-elements-[0-9a-f]{32}\b/', 'wp-elements-{HASH}', $html );

		$html = preg_replace( '/(_wpnonce=)[a-f0-9]{10}\b/', '$1{NONCE}', $html );
		$html = preg_replace( '/(name=["\']_wpnonce["\'][^>]*?value=["\'])[a-f0-9]{10}\b/', '$1{NONCE}', $html );
		$html = preg_replace( '/(value=["\'])[a-f0-9]{10}(["\'][^>]*?name=["\']_wpnonce["\'])/', '$1{NONCE}$2', $html );

		$html = preg_replace_callback(
			'/(\s)class="([^"]*)"/',
			static function ( $m ) {
				$tokens = preg_split( '/\s+/', $m[2], -1, PREG_SPLIT_NO_EMPTY );
				if ( ! in_array( 'wp-block-paragraph', $tokens, true ) ) {
					return $m[0];
				}
				$tokens = array_diff( $tokens, [ 'wp-block-paragraph' ] );
				return $tokens ? $m[1] . 'class="' . implode( ' ', $tokens ) . '"' : '';
			},
			$html
		);

		$html = preg_replace( '#(https://wordpress\.org/\?v=)[0-9][0-9a-zA-Z.-]*#', '$1{WP}', $html );

		$html = preg_replace( '/ id="wp--skip-link--target"/', '', $html );

		return trim( $html );
	}

	/**
	 * Normalise plain text: LF line endings and no trailing whitespace on any line.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function text( $text ) {
		$text = str_replace( [ "\r\n", "\r" ], "\n", (string) $text );
		return preg_replace( '/[ \t]+$/m', '', $text );
	}

	/**
	 * Encode a value as pretty-printed JSON with a trailing newline. Key order is kept.
	 *
	 * @param mixed $value Any JSON-encodable value.
	 * @return string
	 */
	public static function json( $value ) {
		return json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"; // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Stable bytes are required; wp_json_encode may differ between versions.
	}
}
