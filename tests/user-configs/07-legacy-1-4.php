<?php
/**
 * Config 07.
 *
 * Options as version 1.4 stored them (same option set as 1.5; form mode, custom login URL set) (A-08; C-4.3).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '07-legacy-1-4',
	'title'       => 'Options as 1.4 stored them',
	'version'     => '1.4',
	'site'        => [ 'theme' => 'wpp-test-block' ],
	'users'       => [
		'admin'   => 'administrator',
		'member'  => 'subscriber',
		'blocked' => 'subscriber',
	],
	'options'     => [
		'wp_private_replacement_type'      => 'form',
		'wp_private_linkback_enable'       => '1',
		'wp_private_before_html'           => '<br/><div id="wp-private-box"><b>This is protected content. ',
		'wp_private_after_html'            => '</b></div><br/>',
		'wp_private_not_authorized_text'   => '<b>You are not permitted to view Premium Content</b>',
		'wp_private_selected_users'        => '{user:blocked}',
		'wp_private_custom_login_page_url' => 'http://example.org/old-login/',
	],
	'posts'       => [
		[
			'key'     => 'gated',
			'title'   => 'Gated post',
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather07 WPPMARK-07-A[/protected]\n<!-- /wp:shortcode -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-07.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-07-A' ],
	'search_word' => 'quillfeather07',
	'blocked'     => 'blocked',
];
