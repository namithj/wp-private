<?php
/**
 * Config 06.
 *
 * Options as version 1.5 stored them: separate string options, comma-separated block list (A-08; C-4.3).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '06-legacy-1-5',
	'title'       => 'Options as 1.5 stored them',
	'version'     => '1.5',
	'site'        => [ 'theme' => 'wpp-test-classic' ],
	'users'       => [
		'admin'   => 'administrator',
		'member'  => 'subscriber',
		'blocked' => 'subscriber',
		'other'   => 'editor',
	],
	'options'     => [
		'wp_private_replacement_type'      => 'link',
		'wp_private_linkback_enable'       => '1',
		'wp_private_before_html'           => '<p class="wpp-old">Hello &amp; welcome. ',
		'wp_private_after_html'            => '</p>',
		'wp_private_not_authorized_text'   => '<b>You are not permitted &amp; that is final</b>',
		'wp_private_selected_users'        => ',{user:blocked},{user:other}',
		'wp_private_custom_login_page_url' => '',
	],
	'posts'       => [
		[
			'key'     => 'gated',
			'title'   => 'Gated post',
			'content' => "Open intro.\n\n[protected]Secret quillfeather06 WPPMARK-06-A[/protected]\n\nOpen outro.",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => 'Nothing gated here WPPOPEN-06.',
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-06-A' ],
	'search_word' => 'quillfeather06',
	'blocked'     => 'blocked',
];
