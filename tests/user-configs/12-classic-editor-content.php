<?php
/**
 * Config 12.
 *
 * Content written in the Classic Editor: raw text, wpautop quirks around the shortcode (C-5.1).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '12-classic-editor-content',
	'title'       => 'Classic Editor content, wpautop quirks',
	'version'     => '1.6.1',
	'site'        => [ 'theme' => 'wpp-test-classic' ],
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
			'content' => "First paragraph.\n\n[protected]\nSecret paragraph one quillfeather12 WPPMARK-12-A.\n\nSecret paragraph two.\nA line break inside WPPMARK-12-B.\n[/protected]\n\nInline: before [protected]inline secret WPPMARK-12-C[/protected] after.\n\nLast paragraph.",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "Nothing gated here WPPOPEN-12.\n\nSecond paragraph.",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-12-A', 'WPPMARK-12-B', 'WPPMARK-12-C' ],
	'search_word' => 'quillfeather12',
	'blocked'     => 'blocked',
];
