<?php
/**
 * Config 04.
 *
 * Version 1.6.1, 'just remove' mode with wrapper HTML set (A-06).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '04-saved-remove',
	'title'       => '1.6.1, just-remove mode with wrapper HTML',
	'version'     => '1.6.1',
	'site'        => [ 'theme' => 'wpp-test-block' ],
	'users'       => [
		'admin'   => 'administrator',
		'member'  => 'subscriber',
		'blocked' => 'subscriber',
	],
	'options'     => [
		'wp_private_settings'         => [
			'type'                  => 'custom',
			'before_html'           => '<div class="wpp-removed">WPPWRAP-04-BEFORE',
			'after_html'            => 'WPPWRAP-04-AFTER</div>',
			'authorized_text'       => '<p>Not for you.</p>',
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
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather04 WPPMARK-04-A[/protected]\n<!-- /wp:shortcode -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-04.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-04-A' ],
	'search_word' => 'quillfeather04',
	'blocked'     => 'blocked',
	'skip'        => [ 'S9' ],
];
