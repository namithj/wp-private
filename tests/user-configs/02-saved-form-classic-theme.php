<?php
/**
 * Config 02.
 *
 * Version 1.6.1 with saved settings in login-form mode, classic theme (A-01; C-4.1, C-5.3).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '02-saved-form-classic-theme',
	'title'       => '1.6.1, login form mode, classic theme',
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
			'before_html'           => '<div class="wpp-box"><b>Members only</b> &amp; friends. ',
			'after_html'            => '</div>',
			'authorized_text'       => '<b>Sorry &amp; goodbye</b></textarea>',
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
			'content' => "Open intro.\n\n[protected]Secret quillfeather02 WPPMARK-02-A[/protected]\n\nOpen outro.",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => 'Nothing gated here WPPOPEN-02.',
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-02-A' ],
	'search_word' => 'quillfeather02',
	'blocked'     => 'blocked',
];
