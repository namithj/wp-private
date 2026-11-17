<?php
/**
 * Plugin Name: Coexist Plugin (test fixture)
 * Description: Test-only stand-in for another plugin that bundles its own Composer autoloader and an unprefixed copy of the cassette-cmf libraries. Loaded only by config 10 of the user-config regression group.
 * Version: 1.0.0
 * Text Domain: coexist-plugin
 *
 * @package CoexistPlugin
 */

use PedalCMS\CassetteCMF\CassetteCMF;

define( 'COEXIST_PLUGIN_VERSION', '1.0.0' );
define( 'COEXIST_PLUGIN_FILE', __FILE__ );

// Like a real plugin: its own Composer autoloader, its own unprefixed copy of the library.
require_once __DIR__ . '/vendor/autoload.php';

add_action( 'init', [ CassetteCMF::class, 'boot' ], 5 );
