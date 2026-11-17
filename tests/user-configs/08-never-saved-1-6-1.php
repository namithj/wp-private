<?php
/**
 * Config 08.
 *
 * Version 1.6.1 activated, never saved: only the activation seed options exist (A-04, A-07).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '08-never-saved-1-6-1',
	'title'       => '1.6.1 activated, never saved',
	'version'     => '1.6.1',
	'site'        => [ 'theme' => 'wpp-test-block' ],
	'users'       => [
		'admin'  => 'administrator',
		'member' => 'subscriber',
	],
	'options'     => [
		'wp_private_replacement_type' => 'form',
		'wp_private_linkback_enable'  => '1',
		'wp_private_before_html'      => '<br/><div id="wp-private-box"><b>This is protected content. ',
		'wp_private_after_html'       => '</b></div><br/>',
	],
	'posts'       => [
		[
			'key'     => 'gated',
			'title'   => 'Gated post',
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather08 WPPMARK-08-A[/protected]\n<!-- /wp:shortcode -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-08.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-08-A' ],
	'search_word' => 'quillfeather08',
	'skip'        => [ 'S8' ],
];
