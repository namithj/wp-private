<?php
/**
 * Config 11.
 *
 * Two gates on one page, a shortcode inside a gate, a gate inside a list (C-5.1; unique form IDs).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '11-many-gates-nested',
	'title'       => 'Two gates, nested shortcode, gate in a list',
	'version'     => '1.6.1',
	'site'        => [ 'theme' => 'wpp-test-block' ],
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
			'content' => "<!-- wp:shortcode -->\n[protected]First secret quillfeather11 WPPMARK-11-A[/protected]\n<!-- /wp:shortcode -->\n\n<!-- wp:paragraph -->\n<p>Between the gates.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Second secret WPPMARK-11-B [caption width=\"100\"]nested WPPMARK-11-C[/caption][/protected]\n<!-- /wp:shortcode -->\n\n<!-- wp:list -->\n<ul><li>Open item</li><li>[protected]Listed secret WPPMARK-11-D[/protected]</li></ul>\n<!-- /wp:list -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-11.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-11-A', 'WPPMARK-11-B', 'WPPMARK-11-C', 'WPPMARK-11-D' ],
	'search_word' => 'quillfeather11',
	'blocked'     => 'blocked',
	'gates'       => 3,
];
