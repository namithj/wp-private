<?php
/**
 * Config 03.
 *
 * Version 1.6.1, links mode, custom login page carrying [loginform] (A-05; C-1.3).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '03-saved-links-custom-login',
	'title'       => '1.6.1, links mode, custom login page',
	'version'     => '1.6.1',
	'site'        => [ 'theme' => 'wpp-test-block' ],
	'users'       => [
		'admin'   => 'administrator',
		'member'  => 'subscriber',
		'blocked' => 'subscriber',
	],
	'options'     => [
		'users_can_register'          => '1',
		'wp_private_settings'         => [
			'type'                  => 'link',
			'before_html'           => '<p class="wpp-note">',
			'after_html'            => '</p>',
			'authorized_text'       => '<p>Your access was removed.</p>',
			'blocked_users'         => [ '{user:blocked}' ],
			'custom_login_page_url' => 'http://example.org/member-login/',
		],
		'wp_private_replacement_type' => 'form',
		'wp_private_linkback_enable'  => '1',
		'wp_private_before_html'      => '<br/><div id="wp-private-box"><b>This is protected content. ',
		'wp_private_after_html'       => '</b></div><br/>',
	],
	'posts'       => [
		[
			'key'     => 'gated',
			'title'   => 'Gated post',
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather03 WPPMARK-03-A[/protected]\n<!-- /wp:shortcode -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-03.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
		[
			'key'     => 'member-login',
			'type'    => 'page',
			'title'   => 'Member login',
			'content' => "<!-- wp:shortcode -->\n[loginform]\n<!-- /wp:shortcode -->",
		],
	],
	'markers'     => [ 'WPPMARK-03-A' ],
	'search_word' => 'quillfeather03',
	'blocked'     => 'blocked',
	'login_page'  => 'member-login',
];
