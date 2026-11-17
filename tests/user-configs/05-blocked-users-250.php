<?php
/**
 * Config 05.
 *
 * 250 users, five blocked, one of them (u240) beyond the first 100 (A-09; C-4.1). 1.6.1 stored the block list as an array of ID strings.
 *
 * @package WpPrivate
 */

$wpp_test_users = [
	'admin'  => 'administrator',
	'member' => 'subscriber',
];
for ( $wpp_test_i = 1; $wpp_test_i <= 250; $wpp_test_i++ ) {
	$wpp_test_users[ sprintf( 'u%03d', $wpp_test_i ) ] = 'subscriber';
}

return [
	'slug'          => '05-blocked-users-250',
	'title'         => '250 users, 5 blocked, one beyond the first 100',
	'version'       => '1.6.1',
	'site'          => [ 'theme' => 'wpp-test-block' ],
	'users'         => $wpp_test_users,
	'options'       => [
		'wp_private_settings'         => [
			'type'                  => 'form',
			'before_html'           => '<div class="wpp-box">',
			'after_html'            => '</div>',
			'authorized_text'       => '<p>Blocked.</p>',
			'blocked_users'         => [ '{user:u003}', '{user:u050}', '{user:u099}', '{user:u101}', '{user:u240}' ],
			'custom_login_page_url' => '',
		],
		'wp_private_replacement_type' => 'form',
		'wp_private_linkback_enable'  => '1',
		'wp_private_before_html'      => '<br/><div id="wp-private-box"><b>This is protected content. ',
		'wp_private_after_html'       => '</b></div><br/>',
	],
	'posts'         => [
		[
			'key'     => 'gated',
			'title'   => 'Gated post',
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather05 WPPMARK-05-A[/protected]\n<!-- /wp:shortcode -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-05.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'       => [ 'WPPMARK-05-A' ],
	'search_word'   => 'quillfeather05',
	'blocked'       => 'u240',
	'extra_blocked' => [ 'u003', 'u050', 'u099', 'u101' ],
];
