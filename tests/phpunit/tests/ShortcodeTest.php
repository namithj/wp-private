<?php
/**
 * Tests for [protected] and [loginform] (F020, A-04, S-03, S-04), the access decision and the settings reader.
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__ ) . '/includes/class-wpp-test-case.php';

use WpPrivate\Access;
use WpPrivate\Settings;

/**
 * Class ShortcodeTest
 *
 * @group shortcodes
 */
class ShortcodeTest extends WPP_Test_Case {

	/**
	 * Both shortcodes exist.
	 *
	 * @return void
	 */
	public function test_shortcodes_are_registered() {
		$this->assertTrue( shortcode_exists( 'protected' ) );
		$this->assertTrue( shortcode_exists( 'loginform' ) );
	}

	/**
	 * A fresh site: logged-in users see the content, visitors see the prompt, nothing is written (A-04).
	 *
	 * @return void
	 */
	public function test_fresh_site_defaults() {
		$this->assertSame( Access::ANONYMOUS, Access::decision() );
		$this->assertStringContainsString( 'wp-private-prompt', do_shortcode( '[protected]SECRET[/protected]' ) );
		$this->assertStringNotContainsString( 'SECRET', do_shortcode( '[protected]SECRET[/protected]' ) );

		$this->login_as();
		$this->assertSame( Access::ALLOW, Access::decision() );
		$this->assertSame( 'SECRET', do_shortcode( '[protected]SECRET[/protected]' ) );
		foreach ( $this->all_options() as $option ) {
			$this->assertNull( $this->raw_option( $option ), "$option is not written." );
		}
	}

	/**
	 * Nested shortcodes run for allowed viewers and nothing is echoed.
	 *
	 * @return void
	 */
	public function test_nested_shortcodes_and_no_output() {
		add_shortcode( 'wpp_inner', static fn() => 'INNER' );
		$this->login_as();
		ob_start();
		$out = do_shortcode( '[protected]a [wpp_inner] b[/protected]' );
		$this->assertSame( '', ob_get_clean() );
		$this->assertSame( 'a INNER b', $out );
		remove_shortcode( 'wpp_inner' );
	}

	/**
	 * A blocked user gets the stored message as stored; a default when none is stored.
	 *
	 * @return void
	 */
	public function test_blocked_user() {
		$id = $this->login_as();
		$this->store( [ 'blocked_users' => [ (string) $id ] ] );
		$this->assertSame( Access::BLOCKED, Access::decision() );
		$this->assertSame( 'You do not have permission to view this content.', do_shortcode( '[protected]SECRET[/protected]' ) );

		$this->store( [ 'authorized_text' => '<b>You are not permitted</b><script>x</script>' ] );
		$this->assertSame( '<b>You are not permitted</b><script>x</script>', do_shortcode( '[protected]SECRET[/protected]' ) );

		$this->store( [ 'authorized_text' => '' ] );
		$this->assertSame( '', do_shortcode( '[protected]SECRET[/protected]' ), 'A stored empty message stays empty.' );
	}

	/**
	 * The block list is compared as integers and accepts an array of strings or ints, or a comma string.
	 *
	 * @return void
	 */
	public function test_block_list_shapes() {
		$this->assertSame( [], Settings::normalise_ids( null ) );
		$this->assertSame( [ 3, 5 ], Settings::normalise_ids( [ '3', 5, '5', 0, 'x', [ 9 ] ] ) );
		$this->assertSame( [ 3, 5 ], Settings::normalise_ids( '3, 5,,3' ) );

		$id = $this->login_as();
		$this->store( [ 'blocked_users' => "7,$id" ] );
		$this->assertSame( Access::BLOCKED, Access::decision() );
		$this->store( [ 'blocked_users' => [ 7, 8 ] ] );
		$this->assertSame( Access::ALLOW, Access::decision() );
	}

	/**
	 * Unknown stored modes fall back to the login form.
	 *
	 * @return void
	 */
	public function test_type_falls_back_to_form() {
		$this->store( [ 'type' => 'bogus' ] );
		$this->assertSame( 'form', Settings::type() );
		$this->store( [ 'type' => '' ] );
		$this->assertSame( 'form', Settings::type() );
	}

	/**
	 * [loginform] returns its form (A-01 in its own shortcode) and the redirect is local (S-04).
	 *
	 * @return void
	 */
	public function test_loginform_returns_form_with_safe_redirect() {
		$_SERVER['REQUEST_URI'] = '/page/';
		ob_start();
		$html = do_shortcode( '[loginform]' );
		$this->assertSame( '', ob_get_clean(), 'Returned, not printed.' );
		$this->assertStringContainsString( '<form', $html );
		$this->assertStringContainsString( 'name="redirect_to" value="' . esc_url( home_url( '/page/' ) ) . '"', $html );

		$_GET['redirect'] = 'http://evil.example/steal';
		$html             = do_shortcode( '[loginform]' );
		$this->assertStringNotContainsString( 'evil.example', $html, 'External redirect is dropped.' );

		$_GET['redirect'] = home_url( '/members/?a=1&b=2' );
		$html             = do_shortcode( '[loginform]' );
		$this->assertStringContainsString( 'members/?a=1&#038;b=2', $html, 'A local redirect is kept.' );
	}

	/**
	 * [loginform] for a logged-in user: escaped greeting and logout link.
	 *
	 * @return void
	 */
	public function test_loginform_logged_in() {
		$id = self::factory()->user->create( [ 'user_login' => 'bob<i>' ] );
		wp_set_current_user( $id );
		$html = do_shortcode( '[loginform]' );
		$this->assertStringContainsString( 'Hello, bob', $html );
		$this->assertStringNotContainsString( '<i>', $html );
		$this->assertStringContainsString( 'wp-login.php?action=logout', $html );
		$this->assertStringNotContainsString( '<form', $html );
	}
}
