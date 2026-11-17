<?php
/**
 * Config 10.
 *
 * WordPress in a subdirectory (home and siteurl http://example.org/blog), SwiftForms active with its own bundled cassette libraries (A-05; coexistence).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '10-subdirectory-with-swiftforms',
	'title'       => 'Subdirectory install, SwiftForms active',
	'version'     => '1.6.1',
	'site'        => [
		'theme'      => 'wpp-test-block',
		'home'       => 'http://example.org/blog',
		'permalinks' => '/%postname%/',
	],
	'users'       => [
		'admin'   => 'administrator',
		'member'  => 'subscriber',
		'blocked' => 'subscriber',
	],
	'options'     => [
		'wp_private_settings'         => [
			'type'                  => 'form',
			'before_html'           => '<div class="wpp-box">',
			'after_html'            => '</div>',
			'authorized_text'       => '<p>Blocked.</p>',
			'blocked_users'         => [ '{user:blocked}' ],
			'custom_login_page_url' => '',
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
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather10 WPPMARK-10-A[/protected]\n<!-- /wp:shortcode -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-10.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-10-A' ],
	'search_word' => 'quillfeather10',
	'blocked'     => 'blocked',
	'swiftforms'  => true,
];
