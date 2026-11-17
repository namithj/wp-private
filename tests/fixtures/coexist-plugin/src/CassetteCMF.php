<?php
/**
 * Unprefixed stand-in for the cassette-cmf entry class.
 *
 * @package CoexistPlugin
 */

namespace PedalCMS\CassetteCMF;

/**
 * Registers a real settings page under Settings, the way the library does.
 */
class CassetteCMF {

	const PAGE_ID   = 'coexist_settings';
	const MENU_SLUG = 'coexist';
	const OPTION    = 'coexist_settings_title';

	/**
	 * Boot: hook the library into WordPress.
	 *
	 * @return void
	 */
	public static function boot() {
		self::register_from_array(
			[
				'id'         => self::PAGE_ID,
				'page_title' => 'Coexist Plugin',
				'menu_title' => 'Coexist Plugin',
				'menu_slug'  => self::MENU_SLUG,
				'option'     => self::OPTION,
			]
		);
	}

	/**
	 * Register a settings page from a config array.
	 *
	 * @param array $config Page config.
	 * @return void
	 */
	public static function register_from_array( array $config ) {
		add_action(
			'admin_init',
			static function () use ( $config ) {
				register_setting(
					$config['id'],
					$config['option'],
					[
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					]
				);
			}
		);
		add_action(
			'admin_menu',
			static function () use ( $config ) {
				static $done = [];
				if ( isset( $done[ $config['menu_slug'] ] ) ) {
					return; // Like the real library: one entry per page, however often the menu is built.
				}
				$done[ $config['menu_slug'] ] = true;
				$hook = add_options_page(
					$config['page_title'],
					$config['menu_title'],
					'manage_options',
					$config['menu_slug'],
					static function () use ( $config ) {
						echo '<div class="wrap"><h1>' . esc_html( $config['page_title'] ) . '</h1></div>';
					}
				);
				add_action(
					'admin_enqueue_scripts',
					static function ( $current ) use ( $hook ) {
						if ( $current === $hook ) {
							wp_enqueue_style( 'coexist-admin', plugins_url( 'assets/admin.css', COEXIST_PLUGIN_FILE ), [], COEXIST_PLUGIN_VERSION );
						}
					}
				);
			}
		);
	}
}
