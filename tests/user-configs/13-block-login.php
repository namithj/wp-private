<?php
/**
 * Config 13.
 *
 * Frozen at the 2.1.0 tag (R02). A 2.x site that gates content with the Protected content block: block theme, stored
 * per-field options, a blocked user, one block nested inside a group, one beside a [protected] shortcode (F022, C-3.1).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '13-block-login',
	'title'       => '2.x site with the Protected content block',
	'version'     => '2.x',
	'site'        => [ 'theme' => 'wpp-test-block' ],
	'users'       => [
		'admin'   => 'administrator',
		'member'  => 'subscriber',
		'blocked' => 'subscriber',
	],
	'options'     => [
		'wp_private_settings_type'            => 'form',
		'wp_private_settings_before_html'     => '<div class="wpp-box">',
		'wp_private_settings_after_html'      => '</div>',
		'wp_private_settings_authorized_text' => '<p>Blocked.</p>',
		'wp_private_settings_blocked_users'   => [ '{user:blocked}' ],
	],
	'posts'       => [
		[
			'key'     => 'gated',
			'title'   => 'Gated post',
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:wp-private/protected -->\n<!-- wp:paragraph -->\n<p>Secret quillfeather13 WPPMARK-13-A</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:group -->\n<div class=\"wp-block-group\"><!-- wp:paragraph -->\n<p>Nested WPPMARK-13-B</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n<!-- /wp:wp-private/protected -->\n\n<!-- wp:shortcode -->\n[protected]Shortcode WPPMARK-13-C[/protected]\n<!-- /wp:shortcode -->\n\n<!-- wp:paragraph -->\n<p>Open outro.</p>\n<!-- /wp:paragraph -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-13.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-13-A', 'WPPMARK-13-B', 'WPPMARK-13-C' ],
	'search_word' => 'quillfeather13',
	'blocked'     => 'blocked',
];
