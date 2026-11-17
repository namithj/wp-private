<?php
/**
 * Plugin Name: WP Private – Gated Content for Posts & Pages
 * Plugin URI: https://www.smartlogix.co.in/
 * Description: Privatize parts of posts from unauthorized users. Begin protected content with [protected] and end hidden content with [/protected].
 * Version: 2.0.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Author: namithjawahar
 * Author URI: https://www.smartlogix.co.in/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-private
 * Domain Path: /languages
 *
 * Surround the text to be privatized with [protected] and [/protected]
 *
 * Copyright 2020 NAMITH JAWAHAR (website: https://www.smartlogix.co.in)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package WpPrivate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version. Also the value stored in the wp_private_version option once the upgrade routine has run.
 */
define( 'WP_PRIVATE_VERSION', '2.0.0' );

/**
 * Main plugin file, for plugin_dir_path() / plugin_dir_url() and the activation hook.
 */
define( 'WP_PRIVATE_FILE', __FILE__ );

/**
 * Floors checked by the guard below; they match the "Requires" headers.
 */
define( 'WP_PRIVATE_MIN_PHP', '7.4' );
define( 'WP_PRIVATE_MIN_WP', '6.8' );

/**
 * Print an admin notice and leave the plugin switched off.
 *
 * Plain PHP on purpose (no namespaces, no short closures) so that this file parses on old PHP and never fatals.
 *
 * @param string $message Already-translated, plain-text message.
 * @return void
 */
function wp_private_bootstrap_notice( $message ) {
	add_action(
		'admin_notices',
		function () use ( $message ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	);
}

if ( version_compare( PHP_VERSION, WP_PRIVATE_MIN_PHP, '<' ) ) {
	wp_private_bootstrap_notice(
		sprintf(
			/* translators: 1: required PHP version, 2: current PHP version. */
			__( 'WP Private needs PHP %1$s or newer. You are running PHP %2$s, so the plugin is switched off.', 'wp-private' ),
			WP_PRIVATE_MIN_PHP,
			PHP_VERSION
		)
	);
	return;
}

if ( version_compare( get_bloginfo( 'version' ), WP_PRIVATE_MIN_WP, '<' ) ) {
	wp_private_bootstrap_notice(
		sprintf(
			/* translators: 1: required WordPress version, 2: current WordPress version. */
			__( 'WP Private needs WordPress %1$s or newer. You are running WordPress %2$s, so the plugin is switched off.', 'wp-private' ),
			WP_PRIVATE_MIN_WP,
			get_bloginfo( 'version' )
		)
	);
	return;
}

// Strauss output (the namespaced frameworks) and Composer's own autoloader (our classes).
if ( ! is_readable( __DIR__ . '/vendor-prefixed/autoload.php' ) || ! is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	wp_private_bootstrap_notice(
		__( 'WP Private is missing its dependencies. Install the plugin from a release ZIP, or run "composer install" in the plugin folder.', 'wp-private' )
	);
	return;
}

require_once __DIR__ . '/vendor-prefixed/autoload.php';
require_once __DIR__ . '/vendor/autoload.php';

// Registered here, not on plugins_loaded: activation runs before that hook fires.
register_activation_hook( WP_PRIVATE_FILE, [ \WpPrivate\Upgrade::class, 'maybe_upgrade' ] );

\WpPrivate\Plugin::init();
