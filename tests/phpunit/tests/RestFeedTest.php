<?php
/**
 * Tests for the REST and feed channels (A-02, A-03): valid output, no form, no gated text.
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__ ) . '/includes/class-wpp-test-case.php';

/**
 * Class RestFeedTest
 *
 * @group channels
 */
class RestFeedTest extends WPP_Test_Case {

	/**
	 * A published post with a gated part.
	 *
	 * @return int
	 */
	private function gated_post() {
		return self::factory()->post->create(
			[
				'post_title'   => 'Gated',
				'post_content' => 'PUBLIC-TEASER [protected]SECRET-PAYLOAD[/protected] PUBLIC-TAIL',
				'post_excerpt' => '',
			]
		);
	}

	/**
	 * Anonymous REST: nothing printed, valid JSON, no form, no secret, a login link (A-02).
	 *
	 * @return void
	 */
	public function test_anonymous_rest_is_clean() {
		global $wp_rest_server;
		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Fresh server for the test.
		$id             = $this->gated_post();

		foreach ( [ 'form', 'link', 'custom' ] as $mode ) {
			$this->store( [ 'type' => $mode ] );

			ob_start();
			$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $id ) );
			$stray    = ob_get_clean();
			$this->assertSame( '', $stray, "Nothing printed outside the response ($mode)." );

			$json = wp_json_encode( rest_get_server()->response_to_data( $response, false ) );
			$this->assertIsString( $json );
			$this->assertNotNull( json_decode( $json, true ), 'Valid JSON.' );
			$this->assertSame( JSON_ERROR_NONE, json_last_error() );

			$this->assertStringNotContainsString( '<form', $json );
			$this->assertStringNotContainsString( 'SECRET-PAYLOAD', $json );
			$this->assertStringContainsString( 'PUBLIC-TEASER', $json );
			if ( 'custom' !== $mode ) {
				$this->assertStringContainsString( 'wp-login.php', $json, "A login link ($mode)." );
			}
		}
	}

	/**
	 * A logged-in user over REST sees the content.
	 *
	 * @return void
	 */
	public function test_logged_in_rest_sees_content() {
		$id = $this->gated_post();
		$this->login_as( 'administrator' );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $id ) );
		$data     = $response->get_data();
		$this->assertStringContainsString( 'SECRET-PAYLOAD', $data['content']['rendered'] );
	}

	/**
	 * The feed is well-formed, has no form and no secret (A-03).
	 *
	 * @return void
	 */
	public function test_feed_is_clean() {
		$this->gated_post();
		foreach ( [ 'form', 'link' ] as $mode ) {
			$this->store( [ 'type' => $mode ] );
			$this->go_to( '/?feed=rss2' );
			ob_start();
			@load_template( ABSPATH . WPINC . '/feed-rss2.php', false ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- The template sends headers, which the CLI already closed.
			$xml = ob_get_clean();

			$this->assertStringContainsString( '<rss', $xml );
			$this->assertStringNotContainsString( '<form', $xml );
			$this->assertStringNotContainsString( 'SECRET-PAYLOAD', $xml );
			$this->assertStringContainsString( 'wp-login.php', $xml );
			$doc = new DOMDocument();
			$this->assertTrue( $doc->loadXML( $xml ), "Well-formed feed ($mode)." );
		}
	}
}
