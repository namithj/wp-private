<?php
/**
 * Tests for the wp-private/protected block (F022).
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__ ) . '/includes/class-wpp-test-case.php';

/**
 * Class BlockTest
 *
 * @group blocks
 */
class BlockTest extends WPP_Test_Case {

	const MARKUP = '<!-- wp:wp-private/protected --><!-- wp:paragraph --><p>SECRET</p><!-- /wp:paragraph --><!-- /wp:wp-private/protected -->';

	/**
	 * The block is registered, dynamic, and has no attributes yet.
	 *
	 * @return void
	 */
	public function test_block_is_registered() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( 'wp-private/protected' );
		$this->assertNotNull( $type );
		$this->assertTrue( $type->is_dynamic() );
	}

	/**
	 * WP-BB-1: the callback gets the rendered inner blocks and visitors never see them.
	 *
	 * @return void
	 */
	public function test_visitor_gets_prompt_and_user_gets_inner_blocks() {
		$out = do_blocks( self::MARKUP );
		$this->assertStringNotContainsString( 'SECRET', $out );
		$this->assertStringContainsString( 'wp-private-prompt', $out );

		$this->login_as();
		$out = do_blocks( self::MARKUP );
		$this->assertStringContainsString( 'SECRET</p>', $out );
	}

	/**
	 * Block and shortcode give identical prompt markup.
	 *
	 * @return void
	 */
	public function test_prompt_matches_shortcode() {
		$normalise = static fn( $html ) => preg_replace( '/wp-private-(loginform|user-login|user-pass|rememberme|submit)-\d+/', 'wp-private-$1-N', $html );
		$this->assertSame( $normalise( do_shortcode( '[protected]SECRET[/protected]' ) ), $normalise( do_blocks( self::MARKUP ) ) );
	}

	/**
	 * A blocked user gets the stored message.
	 *
	 * @return void
	 */
	public function test_blocked_user() {
		$id = $this->login_as();
		$this->store( [ 'blocked_users' => [ (string) $id ] ] );
		$out = do_blocks( self::MARKUP );
		$this->assertStringNotContainsString( 'SECRET', $out );
		$this->assertSame( \WpPrivate\Settings::authorized_text(), $out );
	}

	/**
	 * Anonymous REST (context=view) shows the prompt, not the content; edit context is refused.
	 *
	 * @return void
	 */
	public function test_rest_does_not_leak() {
		$post_id  = self::factory()->post->create(
			[
				'post_status'  => 'publish',
				'post_content' => self::MARKUP,
			]
		);
		$request  = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$response = rest_do_request( $request );
		$this->assertStringNotContainsString( 'SECRET', wp_json_encode( $response->get_data() ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$request->set_param( 'context', 'edit' );
		$this->assertSame( 401, rest_do_request( $request )->get_status() );
	}

	/**
	 * The builder's editor stylesheet is not printed on the front end.
	 *
	 * @return void
	 */
	public function test_editor_style_not_on_front_end() {
		do_action( 'wp_enqueue_scripts' );
		$this->assertFalse( wp_style_is( 'wp-private-blocks-editor-style', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'wp-private-blocks-editor-style', 'registered' ) );
	}
}
