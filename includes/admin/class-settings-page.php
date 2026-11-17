<?php
/**
 * The settings screen: a cassette-cmf settings page.
 *
 * @package WpPrivate
 */

namespace WpPrivate\Admin;

use WpPrivate\Cmf_Adapter;
use WpPrivate\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings_Page
 *
 * Settings, WP Private: a cassette-cmf settings page in classic (PHP) rendering, saved through options.php. Classic
 * rendering with the page's own callback is how the screen shows no admin notice and no library string on a
 * supported site (fallback for WP-CMF-3).
 *
 * - Page id Settings::PAGE_ID, so cassette-cmf stores one option per field: wp_private_settings_{field} (C-4.1).
 *   The page declares no schema_version, so the library writes nothing besides those options.
 * - Every other part of the plugin reads the values through Settings.
 */
class Settings_Page {

	/**
	 * Menu slug (options-general.php?page=wp-private).
	 */
	const MENU_SLUG = 'wp-private';

	/**
	 * Hook the registration.
	 *
	 * @return void
	 */
	public static function init() {
		// Priority 5, like SwiftForms: cassette-cmf wants its pages before init finishes.
		add_action( 'init', [ self::class, 'register' ], 5 );
	}

	/**
	 * Boot cassette-cmf and register the page.
	 *
	 * @return void
	 */
	public static function register() {
		Cmf_Adapter::register_settings_page(
			[
				Html_Field::TYPE  => Html_Field::class,
				Users_Field::TYPE => Users_Field::class,
			],
			self::page()
		);
		add_filter( 'sanitize_option_' . Settings::OPTION_TYPE, [ self::class, 'string_only' ], 5 );
		add_filter( 'sanitize_option_' . Settings::OPTION_LOGIN_URL, [ self::class, 'string_only' ], 5 );
	}

	/**
	 * Turn a submitted array into an empty string before the field's own sanitiser sees it.
	 *
	 * @param mixed $value Submitted value.
	 * @return mixed
	 */
	public static function string_only( $value ) {
		return is_string( $value ) ? $value : '';
	}

	/**
	 * The page configuration.
	 *
	 * @return array<string, mixed>
	 */
	public static function page() {
		return [
			'id'          => Settings::PAGE_ID,
			'page_title'  => __( 'WP Private', 'wp-private' ),
			'menu_title'  => __( 'WP Private', 'wp-private' ),
			'capability'  => 'manage_options',
			'menu_slug'   => self::MENU_SLUG,
			'parent_slug' => 'options-general.php',
			'callback'    => [ self::class, 'render' ],
			'fields'      => [
				[
					'name'        => 'how_to_block',
					'type'        => 'group',
					'label'       => __( 'How to block content', 'wp-private' ),
					'description' => __( 'Surround the text to hide from visitors with [protected] and [/protected] in a post or page.', 'wp-private' ),
					'fields'      => [],
				],
				[
					'name'        => 'replacement',
					'type'        => 'group',
					'label'       => __( 'What visitors see instead', 'wp-private' ),
					'description' => '',
					'fields'      => [
						[
							'name'    => 'type',
							'type'    => 'radio',
							'label'   => __( 'Replace blocked content with', 'wp-private' ),
							'default' => 'form',
							'options' => [
								'form'   => __( 'A login form', 'wp-private' ),
								'link'   => __( 'Login and register links', 'wp-private' ),
								'custom' => __( 'Nothing (just remove it)', 'wp-private' ),
							],
						],
						[
							'name'        => 'before_html',
							'type'        => Html_Field::TYPE,
							'label'       => __( 'HTML before the prompt', 'wp-private' ),
							'description' => __( 'Shown to logged-out visitors, before the login form or links.', 'wp-private' ),
							'default'     => Settings::default_before_html(),
							'rows'        => 4,
						],
						[
							'name'        => 'after_html',
							'type'        => Html_Field::TYPE,
							'label'       => __( 'HTML after the prompt', 'wp-private' ),
							'description' => __( 'Shown to logged-out visitors, after the login form or links.', 'wp-private' ),
							'default'     => Settings::default_after_html(),
							'rows'        => 4,
						],
					],
				],
				[
					'name'   => 'blocked',
					'type'   => 'group',
					'label'  => __( 'Logged-in users who cannot see protected content', 'wp-private' ),
					'fields' => [
						[
							'name'        => 'authorized_text',
							'type'        => Html_Field::TYPE,
							'label'       => __( 'Message for blocked users', 'wp-private' ),
							'description' => __( 'Shown instead of the content to logged-in users on the block list.', 'wp-private' ),
							'default'     => Settings::default_authorized_text(),
							'rows'        => 4,
						],
						[
							'name'  => 'blocked_users',
							'type'  => Users_Field::TYPE,
							'label' => __( 'Block list', 'wp-private' ),
						],
					],
				],
				[
					'name'        => 'login',
					'type'        => 'group',
					'label'       => __( 'Custom login page', 'wp-private' ),
					'description' => __( 'You can show a login form anywhere in your posts and pages with the shortcode [loginform].', 'wp-private' ),
					'fields'      => [
						[
							'name'        => 'custom_login_page_url',
							'type'        => 'url',
							'label'       => __( 'Custom login page URL', 'wp-private' ),
							'description' => __( 'The page where you have used the shortcode [loginform]. Leave empty to use the standard login page.', 'wp-private' ),
						],
					],
				],
			],
		];
	}

	/**
	 * Draw the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::MENU_SLUG );
				do_settings_sections( self::MENU_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
