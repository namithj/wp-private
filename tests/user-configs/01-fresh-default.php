<?php
/**
 * Config 01.
 *
 * New install, never opened the settings screen, block theme. No wp_private_* rows at all (A-04, C-1.2).
 *
 * @package WpPrivate
 */

return [
	'slug'        => '01-fresh-default',
	'title'       => 'New install on a block theme, settings never saved',
	'version'     => '2.x',
	'site'        => [ 'theme' => 'wpp-test-block' ],
	'users'       => [
		'admin'  => 'administrator',
		'member' => 'subscriber',
	],
	'options'     => [],
	'posts'       => [
		[
			'key'     => 'gated',
			'title'   => 'Gated post',
			'content' => "<!-- wp:paragraph -->\n<p>Open intro.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[protected]Secret quillfeather01 WPPMARK-01-A[/protected]\n<!-- /wp:shortcode -->\n\n<!-- wp:paragraph -->\n<p>Open outro.</p>\n<!-- /wp:paragraph -->",
		],
		[
			'key'     => 'plain',
			'title'   => 'Plain post',
			'content' => "<!-- wp:paragraph -->\n<p>Nothing gated here WPPOPEN-01.</p>\n<!-- /wp:paragraph -->",
			'date'    => '2026-11-09 10:00:00',
		],
	],
	'markers'     => [ 'WPPMARK-01-A' ],
	'search_word' => 'quillfeather01',
	'skip'        => [ 'S8' ],
];
