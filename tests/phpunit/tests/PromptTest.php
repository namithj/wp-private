<?php
/**
 * Tests for the prompt renderer (F020): every mode in every context, element IDs, return URL, register link.
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__ ) . '/includes/class-wpp-test-case.php';

use WpPrivate\Prompt;
use WpPrivate\Settings;

/**
 * Class PromptTest
 *
 * @group prompt
 */
class PromptTest extends WPP_Test_Case {

	/**
	 * Modes and contexts as a data provider: mode, context, expect a form.
	 *
	 * @return array<string, array{0:string,1:string,2:bool}>
	 */
	public function matrix() {
		return [
			'form in html'   => [ 'form', 'html', true ],
			'form in feed'   => [ 'form', 'feed', false ],
			'form in rest'   => [ 'form', 'rest', false ],
			'link in html'   => [ 'link', 'html', false ],
			'link in feed'   => [ 'link', 'feed', false ],
			'link in rest'   => [ 'link', 'rest', false ],
			'custom in html' => [ 'custom', 'html', false ],
			'custom in feed' => [ 'custom', 'feed', false ],
			'custom in rest' => [ 'custom', 'rest', false ],
		];
	}

	/**
	 * Each cell of the table in the plan (A-01, A-02, A-03, A-06).
	 *
	 * @dataProvider matrix
	 *
	 * @param string $mode      Mode.
	 * @param string $context   Context.
	 * @param bool   $with_form Whether a login form is expected.
	 * @return void
	 */
	public function test_prompt_table( $mode, $context, $with_form ) {
		$this->store( [ 'type' => $mode ] );

		ob_start();
		$html = Prompt::render( $context );
		$this->assertSame( '', ob_get_clean(), 'The prompt is returned, never printed (A-01).' );

		if ( 'custom' === $mode ) {
			$this->assertSame( '', $html, 'Just remove prints nothing, no wrapper (A-06).' );
			return;
		}

		$this->assertStringContainsString( '<div class="wp-private-prompt">', $html, 'Default wrapper.' );
		$this->assertStringEndsWith( '</div>', $html );
		if ( $with_form ) {
			$this->assertStringContainsString( '<form', $html );
			$this->assertStringContainsString( 'wp-login.php', $html );
		} else {
			$this->assertStringNotContainsString( '<form', $html );
			$this->assertStringContainsString( 'wp-login.php', $html, 'A login link.' );
			$this->assertStringContainsString( 'class="wp-private-login"', $html );
		}
	}

	/**
	 * The register link follows the site's registration setting, in link mode only.
	 *
	 * @return void
	 */
	public function test_register_link_is_conditional() {
		$this->store( [ 'type' => 'link' ] );

		update_option( 'users_can_register', 0 );
		$this->assertStringNotContainsString( 'wp-private-register', Prompt::render( 'html' ) );

		update_option( 'users_can_register', 1 );
		$html = Prompt::render( 'html' );
		$this->assertStringContainsString( 'class="wp-private-register"', $html );
		$this->assertStringContainsString( 'wp-login.php?action=register', $html );

		$this->store( [ 'type' => 'form' ] );
		$this->assertStringNotContainsString( 'wp-private-register', Prompt::render( 'feed' ), 'Form mode never shows the register link.' );
	}

	/**
	 * Two prompts on one page have no duplicate element IDs.
	 *
	 * @return void
	 */
	public function test_two_prompts_have_unique_ids() {
		$this->store( [ 'type' => 'form' ] );
		$html = Prompt::render( 'html' ) . Prompt::render( 'html' );
		$ids  = $this->ids_in( $html );
		$this->assertGreaterThanOrEqual( 8, count( $ids ) );
		$this->assertSame( $ids, array_values( array_unique( $ids ) ), 'No ID appears twice.' );

		$both = do_shortcode( '[protected]a[/protected][loginform]' );
		$ids  = $this->ids_in( $both );
		$this->assertSame( $ids, array_values( array_unique( $ids ) ), 'Gate and [loginform] do not share IDs either.' );
	}

	/**
	 * The return URL is the current page, absolute, local, on a subdirectory install, and encoded once (A-05).
	 *
	 * @return void
	 */
	public function test_return_url_in_a_subdirectory_is_single_encoded() {
		update_option( 'home', 'http://example.org/sub' );
		$_SERVER['REQUEST_URI'] = '/sub/hello/?p=5&x=a%20b';
		$expected               = 'http://example.org/sub/hello/?p=5&x=a%20b';

		$this->store( [ 'type' => 'link' ] );
		$html = Prompt::render( 'html' );
		preg_match( '/class="wp-private-login" href="([^"]+)"/', $html, $m );
		$this->assertNotEmpty( $m, 'Login link found.' );
		$this->assertSame( $expected, $this->query_of( $m[1] )['redirect_to'], 'Decoding once gives the page URL.' );

		$this->store( [ 'type' => 'form' ] );
		$html = Prompt::render( 'html' );
		preg_match( '/name="redirect_to" value="([^"]+)"/', $html, $m );
		$this->assertNotEmpty( $m, 'Hidden redirect_to found.' );
		$this->assertSame( $expected, html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * A return URL that is not local is replaced; feed and REST use the permalink.
	 *
	 * @return void
	 */
	public function test_return_url_is_local_and_feed_uses_permalink() {
		$this->assertSame( home_url( '/' ), Prompt::local_url( 'http://evil.example/steal' ) );
		$this->assertSame( home_url( '/' ), Prompt::local_url( '//evil.example/steal' ) );

		$post_id         = self::factory()->post->create();
		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- get_permalink() reads the global post.
		$this->store( [ 'type' => 'link' ] );
		preg_match( '/class="wp-private-login" href="([^"]+)"/', Prompt::render( 'feed' ), $m );
		$this->assertSame( get_permalink( $post_id ), $this->query_of( $m[1] )['redirect_to'] );
	}

	/**
	 * A custom login page gets the return URL once-encoded in "redirect" (and every printed URL is escaped, S-03).
	 *
	 * @return void
	 */
	public function test_custom_login_page() {
		$_SERVER['REQUEST_URI'] = '/hello/?a=1&b=2';
		$this->store(
			[
				'type'                  => 'link',
				'custom_login_page_url' => 'http://example.org/members/?lang=en',
			]
		);
		$html = Prompt::render( 'html' );
		$this->assertStringContainsString( 'href="http://example.org/members/?lang=en&#038;redirect=', $html, 'Escaped with esc_url().' );
		preg_match( '/class="wp-private-login" href="([^"]+)"/', $html, $m );
		$query = $this->query_of( $m[1] );
		$this->assertSame( 'en', $query['lang'] );
		$this->assertSame( 'http://example.org/hello/?a=1&b=2', $query['redirect'] );

		$this->store( [ 'type' => 'form' ] );
		$this->assertStringContainsString( 'members/?lang=en', Prompt::render( 'feed' ), 'Feed links use the custom page too.' );
	}

	/**
	 * Stored before and after HTML is output as stored (C-4.5).
	 *
	 * @return void
	 */
	public function test_stored_wrapper_is_output_as_stored() {
		$this->store(
			[
				'type'        => 'link',
				'before_html' => '<br/><div id="wp-private-box"><b>This is protected content. ',
				'after_html'  => '</b></div><br/>',
			]
		);
		$html = Prompt::render( 'html' );
		$this->assertStringStartsWith( '<br/><div id="wp-private-box"><b>This is protected content. ', $html );
		$this->assertStringEndsWith( '</b></div><br/>', $html );
	}

	/**
	 * Context detection: feed and an internal REST request.
	 *
	 * @return void
	 */
	public function test_context_detection() {
		$this->assertSame( 'html', Prompt::context() );

		$this->go_to( '/?feed=rss2' );
		$this->assertSame( 'feed', Prompt::context() );
		$this->go_to( '/' );

		$seen = '';
		add_filter(
			'rest_pre_dispatch',
			function ( $result ) use ( &$seen ) {
				$seen = Prompt::context();
				return $result;
			}
		);
		rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/types' ) );
		$this->assertSame( 'rest', $seen );
	}

	/**
	 * With nothing stored: the default form mode and wrapper, and nothing is written.
	 *
	 * @return void
	 */
	public function test_defaults_with_nothing_stored() {
		$html = Prompt::render( 'html' );
		$this->assertStringContainsString( '<div class="wp-private-prompt"><p>', $html );
		$this->assertStringContainsString( '<form', $html );
		$this->assertStringNotContainsString( 'id="wp-private-box"', $html, 'No fixed element ID on the default wrapper.' );
		$this->assertSame( 'form', Settings::type() );
		foreach ( $this->all_options() as $option ) {
			$this->assertNull( $this->raw_option( $option ), "$option is not written by reading." );
		}
	}

	/**
	 * A corrupt (non-string) stored HTML or URL option reads as the default, never as "Array".
	 *
	 * @return void
	 */
	public function test_corrupt_string_options_read_as_default() {
		update_option( Settings::OPTION_BEFORE_HTML, [ 'x' ] );
		update_option( Settings::OPTION_AFTER_HTML, [ 'x' ] );
		update_option( Settings::OPTION_AUTHORIZED_TEXT, [ 'x' ] );
		update_option( Settings::OPTION_LOGIN_URL, [ 'x' ] );

		$this->assertSame( Settings::default_before_html(), Settings::before_html() );
		$this->assertSame( Settings::default_after_html(), Settings::after_html() );
		$this->assertSame( Settings::default_authorized_text(), Settings::authorized_text() );
		$this->assertSame( '', Settings::custom_login_url() );
	}
}
