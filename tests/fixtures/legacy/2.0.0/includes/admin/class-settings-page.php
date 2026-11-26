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
 * - The fields sit in one wrapper group holding a tabs field (top-level tabs are rejected on settings pages). The
 *   quick-start card is printed by render(), because a custom_html field would register an extra option.
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
		// Priority 5: cassette-cmf registers its pages before init finishes.
		add_action( 'init', [ self::class, 'register' ], 5 );
		// Priority 20: after cassette-cmf has queued its own script (priority 10), which ours depends on.
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ], 20 );
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
					'name'   => 'screen',
					'type'   => 'group',
					'label'  => '',
					'fields' => [
						[
							'name'        => 'sections',
							'type'        => 'tabs',
							'label'       => '',
							'orientation' => 'horizontal',
							'tabs'        => [ self::tab_visitors(), self::tab_blocked() ],
						],
					],
				],
			],
		];
	}

	/**
	 * Show a field only while the visitors still see a prompt (not with "Nothing").
	 *
	 * @return array<string, mixed>
	 */
	private static function unless_nothing() {
		return [
			'rules' => [
				[
					'field'    => 'type',
					'operator' => '!=',
					'value'    => 'custom',
				],
			],
		];
	}

	/**
	 * The "Logged-out visitors" tab.
	 *
	 * @return array<string, mixed>
	 */
	private static function tab_visitors() {
		return [
			'id'          => 'visitors',
			'label'       => __( 'Logged-out visitors', 'wp-private' ),
			'icon'        => 'dashicons-visibility',
			'description' => __( 'Visitors who aren\'t logged in see this in place of protected content.', 'wp-private' ),
			'fields'      => [
				[
					'name'        => 'type',
					'type'        => 'radio',
					'label'       => __( 'What visitors see instead', 'wp-private' ),
					'default'     => 'form',
					'options'     => [
						'form'   => __( 'A login form (recommended)', 'wp-private' ),
						'link'   => __( 'Links to log in and register', 'wp-private' ),
						'custom' => __( 'Nothing — hide the content silently', 'wp-private' ),
					],
					'description' => __( 'Feeds and apps can\'t show a form, so they always get a Log in link.', 'wp-private' ),
				],
				[
					'name'        => 'custom_login_page_url',
					'type'        => 'url',
					'label'       => __( 'Login page', 'wp-private' ),
					'description' => __( 'Where Log in links take visitors. Create a page with [loginform] and paste its address here. Leave empty for the standard WordPress login page.', 'wp-private' ),
					'placeholder' => home_url( '/login/' ),
					'conditional' => self::unless_nothing(),
				],
				[
					'name'        => 'before_html',
					'type'        => Html_Field::TYPE,
					'label'       => __( 'Message above', 'wp-private' ),
					'description' => __( 'HTML shown before the login form or links. The default opens a box that the closing HTML below ends.', 'wp-private' ),
					'default'     => Settings::default_before_html(),
					'rows'        => 4,
					'conditional' => self::unless_nothing(),
				],
				[
					'name'        => 'after_html',
					'type'        => Html_Field::TYPE,
					'label'       => __( 'Closing HTML', 'wp-private' ),
					'description' => __( 'HTML shown after the login form or links. Keep </div> here if the message above opens a box.', 'wp-private' ),
					'default'     => Settings::default_after_html(),
					'rows'        => 3,
					'conditional' => self::unless_nothing(),
				],
			],
		];
	}

	/**
	 * The "Blocked users" tab.
	 *
	 * @return array<string, mixed>
	 */
	private static function tab_blocked() {
		return [
			'id'          => 'blocked',
			'label'       => __( 'Blocked users', 'wp-private' ),
			'icon'        => 'dashicons-lock',
			'description' => __( 'Logged-in users on this list can\'t see protected content. Everyone else who is logged in can.', 'wp-private' ),
			'fields'      => [
				[
					'name'  => 'blocked_users',
					'type'  => Users_Field::TYPE,
					'label' => __( 'Blocked users', 'wp-private' ),
				],
				[
					'name'        => 'authorized_text',
					'type'        => Html_Field::TYPE,
					'label'       => __( 'Message for blocked users', 'wp-private' ),
					'description' => __( 'Shown to blocked users in place of the protected content.', 'wp-private' ),
					'default'     => Settings::default_authorized_text(),
					'rows'        => 4,
				],
			],
		];
	}

	/**
	 * Load the screen's stylesheet and script (this screen only).
	 *
	 * The script waits for the cassette-cmf script when that is queued, so its tab buttons exist when it runs.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}
		$deps = [ 'jquery', 'wp-a11y' ];
		if ( wp_script_is( Cmf_Adapter::BOOT['asset_handle'], 'registered' ) ) {
			$deps[] = Cmf_Adapter::BOOT['asset_handle'];
		}
		wp_enqueue_style( 'wp-private-admin', plugins_url( 'assets/css/admin.css', WP_PRIVATE_FILE ), [], WP_PRIVATE_VERSION );
		wp_enqueue_script( 'wp-private-admin', plugins_url( 'assets/js/admin.js', WP_PRIVATE_FILE ), $deps, WP_PRIVATE_VERSION, true );
		wp_localize_script(
			'wp-private-admin',
			'wpPrivateAdmin',
			[
				'i18n' => [
					'copy'       => __( 'Copy', 'wp-private' ),
					'copied'     => __( 'Copied to clipboard.', 'wp-private' ),
					'copyFailed' => __( 'Press Ctrl+C to copy.', 'wp-private' ),
				],
			]
		);
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
		<div class="wrap wp-private-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="wp-private-lead"><?php esc_html_e( 'Hide parts of your posts and pages from visitors who aren\'t logged in.', 'wp-private' ); ?></p>
			<div class="wp-private-quickstart">
				<h2><?php esc_html_e( 'How to protect content', 'wp-private' ); ?></h2>
				<ul>
					<li>
						<code class="wp-private-snippet">[protected]<?php esc_html_e( 'Members-only content', 'wp-private' ); ?>[/protected]</code>
						<p class="description"><?php esc_html_e( 'Put this around anything in a post or page that only logged-in users should see.', 'wp-private' ); ?></p>
					</li>
					<li>
						<code class="wp-private-snippet">[loginform]</code>
						<p class="description"><?php esc_html_e( 'Shows a login form anywhere. Put it on a page of its own to make a custom login page.', 'wp-private' ); ?></p>
					</li>
				</ul>
			</div>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::MENU_SLUG );
				self::render_sections();
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Print the settings sections with no global post.
	 *
	 * cassette-cmf's tabs field takes a set global $post as a post-edit context: its fields would then read post meta
	 * and lose the wp_private_settings_ prefix in their names, so a save would empty every option. A plugin that
	 * leaves $post set on this screen must not cause that (WP-CMF-6).
	 *
	 * @return void
	 */
	private static function render_sections() {
		global $post;
		$saved = $post;
		$post  = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored right below.
		do_settings_sections( self::MENU_SLUG );
		$post = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the caller's value.
	}
}
