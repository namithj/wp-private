<?php
/**
 * Hook wiring.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Hook wiring only: every behaviour lives in the class the hook points at.
 */
class Plugin {

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public static function init() {
		// Before any gate can render: priority 1 on plugins_loaded (updates do not fire the activation hook).
		add_action( 'plugins_loaded', [ Upgrade::class, 'maybe_upgrade' ], 1 );
		Shortcodes::register();
		Block::register();
		Admin\Settings_Page::init();
	}
}
