<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Fixture class name WPP_User_Config_Loader is fixed by the R01 plan.
/**
 * Loads and applies the numbered site configurations (R01).
 *
 * A config is a PHP file returning an array. It describes RAW stored data: wp_options rows,
 * users and post_content exactly as the named plugin version stored them. apply() writes them
 * with plain SQL and wp_insert_post() with every filter detached, never through WP Private's code.
 * The only plugin code that runs afterwards is Upgrade::maybe_upgrade(), which is what the first
 * request after a file swap runs on a real site (plugins_loaded priority 1).
 *
 * Config keys: slug, title, version (1.4, 1.5, 1.6.1 or 2.x), site (theme, permalinks, home,
 * subsite, subdirectory), users (key => role), options (name => raw value; "{user:key}" in a
 * string becomes that user's ID), posts (key, title, content, type, status, slug, date), main (the
 * gated post key), plain (a post without a gate), markers, search_word, viewer, blocked, admin,
 * skip (scenario ids), swiftforms (true: config 10 loads SwiftForms too).
 *
 * @package WpPrivate
 */

/**
 * Config loader and applier.
 */
class WPP_User_Config_Loader {

	/**
	 * The UTC moment every config is "now": 2026-11-19T10:00:00Z.
	 */
	const FROZEN_TIME = 1795082400;

	/**
	 * First post and user ID; IDs count up in the order the config lists them.
	 */
	const POST_ID_BASE = 900000;
	const USER_ID_BASE = 800000;

	/**
	 * Every scenario id a config may skip.
	 */
	const SCENARIOS = [ 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10', 'S11', 'S12', 'S13' ];

	/**
	 * Post key => ID, for the config applied last.
	 *
	 * @var array<string, int>
	 */
	private static $post_ids = [];

	/**
	 * User key => ID, for the config applied last.
	 *
	 * @var array<string, int>
	 */
	private static $user_ids = [];

	/**
	 * Filters installed by install_environment().
	 *
	 * @var array<int, array{0:string, 1:callable, 2:int}>
	 */
	private static $filters = [];

	/**
	 * Mails captured instead of sent.
	 *
	 * @var array<int, array>
	 */
	private static $mails = [];

	/**
	 * Whether a sub-site was created and switched to.
	 *
	 * @var bool
	 */
	private static $switched = false;

	/**
	 * Theme directories before the fixture directory was registered.
	 *
	 * @var array|null
	 */
	private static $theme_dirs_before = null;

	/**
	 * Directory of the config files.
	 *
	 * @return string
	 */
	public static function dir() {
		return __DIR__;
	}

	/**
	 * Slugs of every config, in order.
	 *
	 * @return string[]
	 */
	public static function slugs() {
		$slugs = [];
		foreach ( glob( __DIR__ . '/[0-9][0-9]-*.php' ) as $file ) {
			$slugs[] = basename( $file, '.php' );
		}
		sort( $slugs );
		return $slugs;
	}

	/**
	 * Load and validate one config.
	 *
	 * @param string $slug Config slug, for example "02-saved-form-classic-theme".
	 * @return array
	 */
	public static function load( $slug ) {
		$file = __DIR__ . '/' . $slug . '.php';
		if ( ! is_file( $file ) ) {
			throw new RuntimeException( 'Unknown config: ' . $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$config = require $file;
		if ( ! is_array( $config ) || ( $config['slug'] ?? '' ) !== $slug ) {
			throw new RuntimeException( 'Config ' . $slug . ' must return an array whose slug equals its file name.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$config         += [
			'title'       => $slug,
			'version'     => '1.6.1',
			'site'        => [],
			'users'       => [],
			'options'     => [],
			'posts'       => [],
			'main'        => 'gated',
			'plain'       => 'plain',
			'markers'     => [],
			'search_word' => '',
			'viewer'      => 'member',
			'blocked'     => '',
			'admin'       => 'admin',
			'skip'        => [],
			'swiftforms'  => false,
		];
		$config['site'] += [
			'theme'      => 'wpp-test-block',
			'permalinks' => '/%postname%/',
			'home'       => 'http://example.org',
		];
		foreach ( $config['skip'] as $id ) {
			if ( ! in_array( $id, self::SCENARIOS, true ) ) {
				throw new RuntimeException( $slug . ': unknown scenario ' . $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
		}
		if ( empty( $config['markers'] ) ) {
			throw new RuntimeException( $slug . ': every config needs at least one marker.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		return $config;
	}

	/**
	 * Post key => ID.
	 *
	 * @return array<string, int>
	 */
	public static function ids() {
		return self::$post_ids;
	}

	/**
	 * User key => ID.
	 *
	 * @return array<string, int>
	 */
	public static function user_ids() {
		return self::$user_ids;
	}

	/**
	 * Captured mails.
	 *
	 * @return array
	 */
	public static function mails() {
		return self::$mails;
	}

	/**
	 * Write a config as stored data.
	 *
	 * @param array $config      Loaded config.
	 * @param bool  $environment Install the test environment (frozen time, blocked HTTP, captured mail).
	 * @param bool  $first_request Run the plugin's own first-request upgrade (what plugins_loaded does after a file swap).
	 */
	public static function apply( array $config, $environment = true, $first_request = true ) {
		self::reset();
		if ( $environment ) {
			self::install_environment();
		}
		$site = $config['site'];

		if ( ! empty( $site['subsite'] ) ) {
			if ( ! is_multisite() ) {
				throw new RuntimeException( 'This config is a sub-site: run it with WP_TESTS_MULTISITE=1.' );
			}
			$blog = wpmu_create_blog( 'example.org', '/sub/', 'WPP sub-site', 1, [ 'public' => 1 ], get_current_network_id() );
			if ( is_wp_error( $blog ) ) {
				throw new RuntimeException( 'Could not create the sub-site: ' . $blog->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
			switch_to_blog( (int) $blog );
			self::$switched = true;
		}

		self::clear_stored_state();
		update_option( 'home', $site['home'] );
		update_option( 'siteurl', ! empty( $site['siteurl'] ) ? $site['siteurl'] : $site['home'] );
		self::apply_theme( $site['theme'] );
		self::apply_users( $config['users'] );
		self::apply_posts( $config['posts'] );
		foreach ( $config['options'] as $name => $value ) {
			self::write_option( $name, self::resolve( $value ) );
		}

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( $site['permalinks'] );
		$wp_rewrite->flush_rules( false );
		wp_cache_flush();

		if ( $first_request && class_exists( 'WpPrivate\\Upgrade' ) ) {
			WpPrivate\Upgrade::maybe_upgrade();
		}
	}

	/**
	 * Learn the post and user IDs of a config that an earlier process already wrote (the upgrade-path group applies a
	 * config in one process and tests it in the next). IDs are pinned, so they are computed, not read.
	 *
	 * @param array $config Loaded config.
	 */
	public static function adopt( array $config ) {
		self::$user_ids = [];
		self::$post_ids = [];
		foreach ( array_keys( $config['users'] ) as $key ) {
			self::$user_ids[ $key ] = self::USER_ID_BASE + count( self::$user_ids ) + 1;
		}
		foreach ( $config['posts'] as $post ) {
			self::$post_ids[ $post['key'] ] = self::POST_ID_BASE + count( self::$post_ids ) + 1;
		}
	}

	/**
	 * Write the config's posts again. The core test bootstrap deletes every post at the start of each PHP process, and
	 * the upgrade-path group continues one database across processes. The posts are fixtures that no plugin version
	 * touches, so inserting the same rows again continues the same site.
	 *
	 * @param array $config Loaded config.
	 */
	public static function restore_posts( array $config ) {
		self::adopt( $config );
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID >= %d", self::POST_ID_BASE ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::$post_ids = [];
		self::apply_posts( $config['posts'] );
		wp_cache_flush();
	}

	/**
	 * Undo apply(): filters, the sub-site switch, the fixture theme directory.
	 */
	public static function reset() {
		foreach ( self::$filters as $entry ) {
			remove_filter( $entry[0], $entry[1], $entry[2] );
		}
		self::$filters = [];
		if ( self::$switched ) {
			restore_current_blog();
			self::$switched = false;
		}
		if ( null !== self::$theme_dirs_before ) {
			$GLOBALS['wp_theme_directories'] = self::$theme_dirs_before; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			self::$theme_dirs_before         = null;
			wp_clean_themes_cache();
		}
		self::$post_ids = [];
		self::$user_ids = [];
		self::$mails    = [];
	}

	/**
	 * Fixed time, blocked external HTTP, captured mail.
	 */
	public static function install_environment() {
		self::$mails = [];
		self::add_filter(
			'pre_wp_mail',
			static function ( $short_circuit, $atts ) {
				unset( $short_circuit );
				self::$mails[] = $atts;
				return true;
			},
			10,
			2
		);
		self::add_filter(
			'pre_http_request',
			static function ( $short_circuit, $args, $url ) {
				unset( $short_circuit, $args );
				return new WP_Error( 'wpp_blocked_http', 'External HTTP is blocked in the user-config suite: ' . $url );
			},
			10,
			3
		);
		self::add_filter(
			'current_time',
			static function ( $time, $type ) {
				unset( $time );
				if ( 'mysql' === $type ) {
					return gmdate( 'Y-m-d H:i:s', self::FROZEN_TIME );
				}
				if ( 'timestamp' === $type || 'U' === $type ) {
					return self::FROZEN_TIME;
				}
				return gmdate( (string) $type, self::FROZEN_TIME );
			},
			10,
			2
		);
		mt_srand( 20261119 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand -- Deterministic fixtures.
	}

	/**
	 * Add a filter and remember it for reset().
	 *
	 * @param string   $hook          Hook.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted arguments.
	 */
	private static function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_filter( $hook, $callback, $priority, $accepted_args );
		self::$filters[] = [ $hook, $callback, $priority ];
	}

	/**
	 * Remove everything a config could have written: this plugin's options, config users and posts.
	 */
	private static function clear_stored_state() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Raw fixtures on purpose; table names come from $wpdb.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wp\\_private\\_%'" );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID >= %d", self::POST_ID_BASE ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id >= %d", self::POST_ID_BASE ) );
		// Content a new sub-site comes with (Hello world, the sample page, the first comment).
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID < %d AND post_type IN ( 'post', 'page' )", self::POST_ID_BASE ) );
		$wpdb->query( "DELETE FROM {$wpdb->comments}" );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->users} WHERE ID >= %d", self::USER_ID_BASE ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE user_id >= %d", self::USER_ID_BASE ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		wp_cache_flush();
		// A config describes the whole stored state: core options a config may set go back to their defaults first.
		self::write_option( 'users_can_register', '0' );
	}

	/**
	 * Activate a fixture theme.
	 *
	 * @param string $theme Theme directory name.
	 */
	private static function apply_theme( $theme ) {
		if ( null === self::$theme_dirs_before ) {
			self::$theme_dirs_before = isset( $GLOBALS['wp_theme_directories'] ) ? (array) $GLOBALS['wp_theme_directories'] : [];
		}
		register_theme_directory( __DIR__ . '/themes' );
		wp_clean_themes_cache();
		if ( ! wp_get_theme( $theme, __DIR__ . '/themes' )->exists() ) {
			throw new RuntimeException( 'Fixture theme not found: ' . $theme ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		switch_theme( $theme );
		// A real request boots with the theme already active: redo the theme features core derives from it (block-templates, post-thumbnails and so on).
		$GLOBALS['_wp_theme_features'] = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $GLOBALS['wp_stylesheet_path'], $GLOBALS['wp_template_path'] ); // locate_template() caches the paths of the theme the process booted with.
		_add_default_theme_supports();
		wp_enable_block_templates();
	}

	/**
	 * Insert users as raw rows (no password hashing, no hooks): 250 users stay fast.
	 *
	 * @param array $users Key => role.
	 */
	private static function apply_users( array $users ) {
		global $wpdb;
		$meta_key = $wpdb->get_blog_prefix() . 'capabilities';
		foreach ( $users as $key => $role ) {
			$id = self::USER_ID_BASE + count( self::$user_ids ) + 1;
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			$wpdb->insert(
				$wpdb->users,
				[
					'ID'              => $id,
					'user_login'      => 'wpp-' . $key,
					'user_pass'       => 'not-a-hash',
					'user_nicename'   => 'wpp-' . $key,
					'user_email'      => 'wpp-' . $key . '@example.org',
					'user_registered' => '2026-01-01 00:00:00',
					'display_name'    => 'WPP ' . $key,
				]
			);
			$wpdb->insert(
				$wpdb->usermeta,
				[
					'user_id'    => $id,
					'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value' => serialize( [ $role => true ] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				]
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			self::$user_ids[ $key ] = $id;
		}
	}

	/**
	 * Insert posts with kses and every save filter detached, then compare the stored bytes.
	 *
	 * @param array $posts Post definitions.
	 */
	private static function apply_posts( array $posts ) {
		global $wpdb;
		kses_remove_filters();
		try {
			foreach ( $posts as $post ) {
				$content = self::resolve( (string) $post['content'] );
				$id      = wp_insert_post(
					wp_slash(
						[
							'import_id'     => self::POST_ID_BASE + count( self::$post_ids ) + 1,
							'post_type'     => $post['type'] ?? 'post',
							'post_status'   => $post['status'] ?? 'publish',
							'post_title'    => $post['title'] ?? $post['key'],
							'post_name'     => $post['slug'] ?? $post['key'],
							'post_content'  => $content,
							'post_author'   => 1,
							'post_date'     => $post['date'] ?? '2026-11-10 10:00:00',
							'post_date_gmt' => $post['date'] ?? '2026-11-10 10:00:00',
						]
					),
					true
				);
				if ( is_wp_error( $id ) ) {
					throw new RuntimeException( 'Could not insert post ' . $post['key'] . ': ' . $id->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				}
				$stored = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				if ( (string) $stored !== $content ) {
					throw new RuntimeException( 'Post ' . $post['key'] . ' was not stored byte-identically.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				}
				self::$post_ids[ $post['key'] ] = (int) $id;
			}
		} finally {
			kses_init();
		}
	}

	/**
	 * Write one option row with plain SQL, the way an old plugin version left it.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Value; arrays are serialised.
	 */
	public static function write_option( $name, $value ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->options,
			[
				'option_name'  => $name,
				'option_value' => maybe_serialize( $value ),
				'autoload'     => 'yes',
			]
		);
		wp_cache_delete( $name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Replace "{user:key}" in strings, recursively.
	 *
	 * @param mixed $value String or array.
	 * @return mixed
	 */
	private static function resolve( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ self::class, 'resolve' ], $value );
		}
		if ( ! is_string( $value ) ) {
			return $value;
		}
		return preg_replace_callback(
			'/\{user:([a-z0-9_-]+)\}/',
			static function ( $m ) {
				if ( ! isset( self::$user_ids[ $m[1] ] ) ) {
					throw new RuntimeException( 'Unknown user key ' . $m[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				}
				return (string) self::$user_ids[ $m[1] ];
			},
			$value
		);
	}
}
