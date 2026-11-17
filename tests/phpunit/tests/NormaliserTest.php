<?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Test class.
/**
 * Unit test of the snapshot normaliser. Every rule has a case, plus idempotence.
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__, 2 ) . '/user-configs/normaliser.php';

/**
 * @group normaliser
 */
class NormaliserTest extends PHPUnit\Framework\TestCase {

	/**
	 * Rule 1 and 2.
	 *
	 * @return void
	 */
	public function test_line_endings_and_inter_tag_whitespace() {
		$this->assertSame( "<p>a</p>\n<p>b  c</p>", WPP_Normaliser::html( "<p>a</p>\r\n\t  <p>b  c</p>\r\n" ) );
	}

	/**
	 * Rule 3.
	 *
	 * @return void
	 */
	public function test_version_query_is_removed() {
		$this->assertSame( '<link href="a.css">', WPP_Normaliser::html( '<link href="a.css?ver=6.8.1">' ) );
		$this->assertSame( '<link href="a.css?x=1">', WPP_Normaliser::html( '<link href="a.css?x=1&#038;ver=7.1.4">' ) );
		$this->assertSame( '<link href="a.css?x=1">', WPP_Normaliser::html( '<link href="a.css?ver=1&amp;x=1">' ) );
	}

	/**
	 * Rule 4.
	 *
	 * @return void
	 */
	public function test_hashed_classes() {
		$this->assertSame(
			'<div class="wp-container-{HASH}"></div>',
			WPP_Normaliser::html( '<div class="wp-container-core-group-is-layout-9f0d2b3c"></div>' )
		);
		$this->assertSame( 'wp-container-{HASH}', WPP_Normaliser::html( 'wp-container-61a3b2c1' ) );
		$this->assertSame( 'wp-elements-{HASH}', WPP_Normaliser::html( 'wp-elements-' . str_repeat( 'ab', 16 ) ) );
		$this->assertSame( 'wp-block-group-is-layout-flow', WPP_Normaliser::html( 'wp-block-group-is-layout-flow' ), 'Stable classes stay.' );
	}

	/**
	 * Rule 5.
	 *
	 * @return void
	 */
	public function test_nonces() {
		$this->assertSame( 'x?_wpnonce={NONCE}', WPP_Normaliser::html( 'x?_wpnonce=0123456789' ) );
		$this->assertSame( '<input name="_wpnonce" value="{NONCE}">', WPP_Normaliser::html( '<input name="_wpnonce" value="abcdef0123">' ) );
		$this->assertSame( '<input value="{NONCE}" name="_wpnonce">', WPP_Normaliser::html( '<input value="abcdef0123" name="_wpnonce">' ) );
		$this->assertSame( 'value="abcdef0123456"', WPP_Normaliser::html( 'value="abcdef0123456"' ), 'Only ten digits.' );
	}

	/**
	 * Rule 6.
	 *
	 * @return void
	 */
	public function test_core_version_paragraph_class() {
		$this->assertSame( '<p>x</p>', WPP_Normaliser::html( '<p class="wp-block-paragraph">x</p>' ) );
		$this->assertSame( '<p class="has-text-align-center">x</p>', WPP_Normaliser::html( '<p class="has-text-align-center wp-block-paragraph">x</p>' ) );
		$this->assertSame( '<p class="wp-private-x">x</p>', WPP_Normaliser::html( '<p class="wp-private-x">x</p>' ) );
	}

	/**
	 * Rule 7.
	 *
	 * @return void
	 */
	public function test_generator_version() {
		$this->assertSame( '<generator>https://wordpress.org/?v={WP}</generator>', WPP_Normaliser::html( '<generator>https://wordpress.org/?v=6.8.12</generator>' ) );
	}

	/**
	 * Rule 8.
	 *
	 * @return void
	 */
	public function test_skip_link_target() {
		$this->assertSame( '<main class="a">', WPP_Normaliser::html( '<main id="wp--skip-link--target" class="a">' ) );
		$this->assertSame( '<main id="main">', WPP_Normaliser::html( '<main id="main">' ) );
	}

	/**
	 * Rule 9, and the home URL is kept.
	 *
	 * @return void
	 */
	public function test_edges_and_home_url() {
		$this->assertSame( '<a href="http://example.org/">x</a>', WPP_Normaliser::html( "  \n<a href=\"http://example.org/\">x</a>\n " ) );
	}

	/**
	 * Every rule is idempotent.
	 *
	 * @return void
	 */
	public function test_idempotent() {
		$sample = "<p class=\"wp-block-paragraph a\">x</p>\r\n <div class=\"wp-container-core-group-is-layout-9f0d2b3c\"><a href=\"a.css?ver=1&#038;x=2\">y</a>\n<input name=\"_wpnonce\" value=\"abcdef0123\"></div> https://wordpress.org/?v=7.1.4 ";
		$once   = WPP_Normaliser::html( $sample );
		$this->assertSame( $once, WPP_Normaliser::html( $once ) );
	}

	/**
	 * Text and JSON helpers.
	 *
	 * @return void
	 */
	public function test_text_and_json() {
		$this->assertSame( "a\nb\n", WPP_Normaliser::text( "a  \r\nb\t\r\n" ) );
		$this->assertSame(
			"{\n    \"b\": \"/é\",\n    \"a\": 1\n}\n",
			WPP_Normaliser::json(
				[
					'b' => '/é',
					'a' => 1,
				]
			)
		);
	}
}
