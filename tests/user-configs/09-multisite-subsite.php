<?php
/**
 * Config 09.
 *
 * A multisite sub-site (run with WP_TESTS_MULTISITE=1) whose administrator lacks unfiltered_html (S-01; C-4.5). 1.6.1 saved settings.
 *
 * @package WpPrivate
 */

return [
	'slug'        => '09-multisite-subsite',
	'title'       => 'Multisite sub-site, administrator without unfiltered_html',
	'version'     => '1.6.1',
	'site'        => [
		'theme'      => 'wpp-test-block',
		'subsite'    => true,
		'home'       => 'http://example.org/sub',
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
			'before_html'           => '<div class="wpp-box"><b>Members only</b> ',
			'after_html'            => '</div>',
			'authorized_text'       => '<b>Blocked on this site.</b>',
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
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather09 WPPMARK-09-A[/protected]\n<!-- /wp:shortcode -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-09.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-09-A' ],
	'search_word' => 'quillfeather09',
	'blocked'     => 'blocked',
	'multisite'   => true,
];
