<?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Test helper: the class name follows the WPP_ test prefix; textContent and nodeName are DOM API names.
/**
 * Scenarios S1 to S10 and S13 of .ai/testing/site-configs.md, run against one applied config.
 *
 * The same engine runs against this build (strict: every expectation is asserted) and against the
 * 1.6.1 files (not strict: output is only captured, because 1.6.1 is known to print its login form
 * and leak into REST and feeds, and the upgrade-path group diffs the two).
 *
 * Real request simulation: go_to() runs the main query, the page is rendered through core's
 * template-loader (block or classic theme), REST goes through rest_do_request(), feeds through
 * do_feed(), search through the front-end search and the REST search routes.
 *
 * @package WpPrivate
 */

require_once dirname( __DIR__, 2 ) . '/user-configs/loader.php';
require_once dirname( __DIR__, 2 ) . '/user-configs/normaliser.php';

/**
 * Scenario engine.
 */
class WPP_Scenarios {

	/**
	 * Anything that names this plugin: its files (not the fixture themes inside its tests folder), its CSS classes or its option names. Core's own "wp-private-apis" handle is not ours.
	 */
	const OURS = '#plugins/wp-private/(?!tests/)|wp-private-(?!apis)|wp_private#i';

	/**
	 * The running test case (for go_to()).
	 *
	 * @var WP_UnitTestCase
	 */
	private $tc;

	/**
	 * The applied config.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Assert (true) or only capture (false).
	 *
	 * @var bool
	 */
	private $strict;

	/**
	 * Snapshot name => text.
	 *
	 * @var array<string, string>
	 */
	private $snaps = [];

	/**
	 * Failed expectations.
	 *
	 * @var string[]
	 */
	private $failures = [];

	/**
	 * Constructor.
	 *
	 * @param WP_UnitTestCase $tc     Test case.
	 * @param array           $config Applied config.
	 * @param bool            $strict Assert expectations.
	 */
	public function __construct( $tc, array $config, $strict = true ) {
		$this->tc     = $tc;
		$this->config = $config;
		$this->strict = $strict;
	}

	/**
	 * Failed expectations so far.
	 *
	 * @return string[]
	 */
	public function failures() {
		return $this->failures;
	}

	/**
	 * Snapshots produced so far.
	 *
	 * @return array<string, string>
	 */
	public function snaps() {
		return $this->snaps;
	}

	/**
	 * Run the request scenarios that apply to the config, S13 first.
	 *
	 * @return void
	 */
	public function run_requests() {
		foreach ( [ 'S13', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9' ] as $id ) {
			if ( in_array( $id, $this->config['skip'], true ) ) {
				continue;
			}
			if ( 'S13' === $id && ! $this->strict ) {
				continue; // Only meaningful for this build.
			}
			if ( 'S9' === $id && ! $this->strict ) {
				continue;
			}
			$this->{ strtolower( $id ) }();
		}
	}

	/**
	 * Record a failed expectation (strict mode only).
	 *
	 * @param bool   $ok      Condition.
	 * @param string $message Message.
	 * @return void
	 */
	private function expect( $ok, $message ) {
		if ( $this->strict && ! $ok ) {
			$this->failures[] = $message;
		}
	}

	/**
	 * Append a section to a scenario's snapshot.
	 *
	 * @param string $scenario Scenario id.
	 * @param string $title    Section title.
	 * @param string $body     Section body.
	 * @return void
	 */
	private function section( $scenario, $title, $body ) {
		$this->snaps[ $scenario ] = ( $this->snaps[ $scenario ] ?? '' ) . '=== ' . $title . " ===\n" . rtrim( (string) $body ) . "\n\n";
	}

	/**
	 * Does any marker of the config appear in the text?
	 *
	 * @param string $text Text.
	 * @return string[] The markers found.
	 */
	private function leaked( $text ) {
		$found = [];
		foreach ( $this->config['markers'] as $marker ) {
			if ( false !== strpos( (string) $text, $marker ) ) {
				$found[] = $marker;
			}
		}
		return $found;
	}

	/**
	 * Assert that no marker is in the text.
	 *
	 * @param string $scenario Scenario id.
	 * @param string $where    Where the text came from.
	 * @param string $text     Text.
	 * @return void
	 */
	private function no_marker( $scenario, $where, $text ) {
		$found = $this->leaked( $text );
		$this->expect( ! $found, $scenario . ': marker visible in ' . $where . ': ' . implode( ', ', $found ) );
	}

	/**
	 * Permalink of a config post.
	 *
	 * @param string $key Post key.
	 * @return string
	 */
	private function permalink( $key ) {
		return get_permalink( WPP_User_Config_Loader::ids()[ $key ] );
	}

	/**
	 * Act as a config user (an empty key means logged out).
	 *
	 * @param string $key User key.
	 * @return void
	 */
	private function act_as( $key ) {
		wp_set_current_user( '' === $key ? 0 : WPP_User_Config_Loader::user_ids()[ $key ] );
	}

	/**
	 * A request starts with no forms counted and no assets queued.
	 *
	 * @return void
	 */
	private function fresh_request() {
		if ( class_exists( 'WpPrivate\\Prompt', false ) ) {
			$property = new ReflectionProperty( 'WpPrivate\\Prompt', 'forms' );
			if ( PHP_VERSION_ID < 80100 ) {
				$property->setAccessible( true ); // No effect from PHP 8.1, deprecated in 8.5.
			}
			$property->setValue( null, 0 );
		}
		$GLOBALS['wp_scripts'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_styles']  = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Run a callable, hiding only the "headers already sent" warning of header() calls in core templates.
	 *
	 * @param callable $callback Callback.
	 * @return string Everything the callback printed.
	 */
	private function capture( $callback ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Scoped to this call.
		set_error_handler(
			static function ( $errno, $errstr ) {
				return false !== strpos( $errstr, 'headers already sent' ) || false !== strpos( $errstr, 'Cannot modify header' );
			}
		);
		$level = ob_get_level();
		ob_start();
		try {
			call_user_func( $callback );
		} finally {
			restore_error_handler();
			// Newer core opens a template output buffer of its own while it renders; a request ends it at shutdown.
			while ( ob_get_level() > $level + 1 ) {
				ob_end_flush();
			}
			$out = ob_get_clean();
		}
		return (string) $out;
	}

	/**
	 * Request a URL and render the whole page through the theme, as a visitor's browser would get it.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function page( $url ) {
		$this->fresh_request();
		$this->tc->go_to( $url );
		add_filter( 'wp_using_themes', '__return_true' );
		remove_all_actions( 'template_redirect' );
		$html = $this->capture(
			static function () {
				require ABSPATH . WPINC . '/template-loader.php';
			}
		);
		remove_filter( 'wp_using_themes', '__return_true' );
		return $html;
	}

	/**
	 * The content region of a page: everything between the first <main and the last </main>.
	 *
	 * @param string $html Page.
	 * @return string
	 */
	private function main_slice( $html ) {
		$start = strpos( $html, '<main' );
		$end   = strrpos( $html, '</main>' );
		if ( false === $start || false === $end ) {
			return '[no <main> in the page]';
		}
		return WPP_Normaliser::html( substr( $html, $start, $end + 7 - $start ) );
	}

	/**
	 * A REST GET as the current user.
	 *
	 * @param string $route  Route.
	 * @param array  $params Query parameters.
	 * @return array{status:int, json:string, data:mixed, stray:string}
	 */
	private function rest( $route, array $params = [] ) {
		$this->fresh_request();
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_query_params( $params );
		$response = null;
		$stray    = $this->capture(
			static function () use ( $request, &$response ) {
				$response = rest_do_request( $request );
			}
		);
		$data     = rest_get_server()->response_to_data( $response, false );
		$json     = wp_json_encode( $data );
		return [
			'status' => $response->get_status(),
			'json'   => (string) $json,
			'data'   => $data,
			'stray'  => $stray,
		];
	}

	/**
	 * S13: unused modules. A plain post and the REST route for it, with the plugin active and no gate in sight.
	 *
	 * @return void
	 */
	private function s13() {
		global $wpdb;
		$this->act_as( '' );
		$queries = [];
		$logger  = static function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return $query;
		};
		add_filter( 'query', $logger );
		$cookies_before = $_COOKIE;

		$html  = $this->page( $this->permalink( $this->config['plain'] ) );
		$rest  = $this->rest( '/wp/v2/posts/' . WPP_User_Config_Loader::ids()[ $this->config['plain'] ] );
		$queue = array_merge( array_keys( wp_scripts()->registered ), array_keys( wp_styles()->registered ) );
		remove_filter( 'query', $logger );

		$ours           = array_values(
			array_filter(
				$queue,
				static function ( $handle ) {
					return 1 === preg_match( '/^(wp-private(?!-apis)|cassette)/i', $handle );
				}
			)
		);
		$sql            = array_values(
			array_filter(
				$queries,
				static function ( $query ) {
					return false !== stripos( $query, 'wp_private' );
				}
			)
		);
		$cookie_headers = array_values(
			array_filter(
				headers_list(),
				static function ( $header ) {
					return 0 === stripos( $header, 'Set-Cookie' );
				}
			)
		);

		$this->expect( false !== strpos( $html, 'WPPOPEN-' ), 'S13: the plain post did not render.' );
		$this->expect( ! $ours, 'S13: plugin assets registered or enqueued with no gate on the page: ' . implode( ', ', $ours ) );
		$this->expect( 0 === preg_match( self::OURS, $html ), 'S13: the page of an ungated post mentions wp-private.' );
		$this->expect( 0 === preg_match( self::OURS, $rest['json'] ), 'S13: the REST response of an ungated post mentions wp-private.' );
		$this->expect( ! $sql, 'S13: a query touched a wp_private option while rendering an ungated post: ' . ( $sql ? $sql[0] : '' ) );
		$this->expect( ! $cookie_headers && $_COOKIE === $cookies_before, 'S13: a cookie was set.' );
		$this->expect( ! class_exists( 'WpPrivate\\Prompt', false ), 'S13: the prompt module was loaded on a page with no gate.' );
		unset( $wpdb );

		$this->section(
			'S13',
			'ungated post and REST',
			"plugin assets: none\nquery on a wp_private option: none\ncookies: none\nprompt module loaded: no\n" . $this->main_slice( $html )
		);
	}

	/**
	 * S1: single post, logged out.
	 *
	 * @return void
	 */
	private function s1() {
		$this->act_as( '' );
		$html = $this->page( $this->permalink( $this->config['main'] ) );
		$this->expect( 0 === stripos( ltrim( $html ), '<!doctype html>' ), 'S1: the response does not start with the doctype: ' . substr( $html, 0, 60 ) );
		$this->no_marker( 'S1', 'the single post', $html );

		$slice = $this->main_slice( $html );
		$type  = $this->effective_type();
		if ( 'form' === $type ) {
			$this->expect( 1 <= substr_count( $slice, 'name="log"' ), 'S1: no login form in the content.' );
		} elseif ( 'link' === $type ) {
			$this->expect( false !== strpos( $slice, 'wp-private-login' ), 'S1: no login link in the content.' );
		} else {
			$this->expect( false === strpos( $slice, 'wp-private' ) && false === strpos( $slice, 'WPPWRAP' ), 'S1: just-remove mode printed something.' );
		}
		$ids = [];
		preg_match_all( '/\sid="([^"]+)"/', $slice, $ids );
		$dups = array_keys(
			array_filter(
				array_count_values( $ids[1] ),
				static function ( $n ) {
					return $n > 1;
				}
			)
		);
		$this->expect( ! $dups, 'S1: duplicate element IDs: ' . implode( ', ', $dups ) );
		if ( ! empty( $this->config['gates'] ) && 'form' === $type ) {
			$this->expect( substr_count( $slice, 'name="log"' ) === (int) $this->config['gates'], 'S1: expected ' . $this->config['gates'] . ' forms, found ' . substr_count( $slice, 'name="log"' ) . '.' );
		}
		$this->section( 'S1', 'single post, logged out', 'doctype first: ' . ( 0 === stripos( ltrim( $html ), '<!doctype html>' ) ? 'yes' : 'no' ) . "\n" . $slice );
		$this->full_page = $html;
	}

	/**
	 * The page S1 rendered (S5 looks at its head).
	 *
	 * @var string
	 */
	private $full_page = '';

	/**
	 * The mode the stored data resolves to (read from the config's raw data, not the plugin).
	 *
	 * @return string
	 */
	private function effective_type() {
		$options = $this->config['options'];
		if ( isset( $options['wp_private_settings'] ) && is_array( $options['wp_private_settings'] ) ) {
			$type = $options['wp_private_settings']['type'] ?? 'form';
		} elseif ( isset( $options['wp_private_replacement_type'] ) ) {
			$type = $options['wp_private_replacement_type'];
		} else {
			$type = 'form';
		}
		return in_array( $type, [ 'form', 'link', 'custom' ], true ) ? $type : 'form';
	}

	/**
	 * S2: home and archives, logged out.
	 *
	 * @return void
	 */
	private function s2() {
		$this->act_as( '' );
		foreach ( [
			'home'    => home_url( '/' ),
			'archive' => home_url( '/?cat=1' ),
			'date'    => home_url( '/?m=202611' ),
		] as $name => $url ) {
			$html = $this->page( $url );
			$this->no_marker( 'S2', $name, $html );
			$this->expect( false !== strpos( $html, 'WPPOPEN-' ) || false !== strpos( $html, 'Gated post' ), 'S2: the ' . $name . ' did not list the posts.' );
			$this->section( 'S2', $name . ', logged out', $this->main_slice( $html ) );
		}
	}

	/**
	 * S3: REST, logged out.
	 *
	 * @return void
	 */
	private function s3() {
		$this->act_as( '' );
		$id   = WPP_User_Config_Loader::ids()[ $this->config['main'] ];
		$one  = $this->rest( '/wp/v2/posts/' . $id );
		$list = $this->rest( '/wp/v2/posts', [ 'per_page' => 20 ] );
		foreach ( [
			'post' => $one,
			'list' => $list,
		] as $name => $result ) {
			$this->expect( 200 === $result['status'], 'S3: REST ' . $name . ' status ' . $result['status'] );
			$this->expect( '' === trim( $result['stray'] ), 'S3: REST ' . $name . ' printed stray output (invalid JSON): ' . substr( trim( $result['stray'] ), 0, 80 ) );
			$this->expect( null !== json_decode( $result['json'] ), 'S3: REST ' . $name . ' is not valid JSON.' );
			$this->no_marker( 'S3', 'REST ' . $name, $result['json'] );
			$this->expect( false === strpos( $result['json'], '<form' ), 'S3: a form in REST ' . $name . '.' );
		}
		$data = (array) $one['data'];
		$this->section(
			'S3',
			'REST post, logged out',
			"status: {$one['status']}\nstray output: " . ( '' === trim( $one['stray'] ) ? 'none' : trim( $one['stray'] ) ) .
			"\n--- content.rendered\n" . WPP_Normaliser::html( $data['content']['rendered'] ?? '' ) .
			"\n--- excerpt.rendered\n" . WPP_Normaliser::html( $data['excerpt']['rendered'] ?? '' )
		);
	}

	/**
	 * S4: feed, logged out.
	 *
	 * @return void
	 */
	private function s4() {
		$this->act_as( '' );
		$this->fresh_request();
		$this->tc->go_to( home_url( '/?feed=rss2' ) );
		$xml = $this->capture(
			static function () {
				do_feed();
			}
		);
		$this->no_marker( 'S4', 'the feed', $xml );
		$this->expect( false === strpos( $xml, '<form' ) && false === strpos( $xml, '&lt;form' ), 'S4: a form in the feed.' );

		$dom  = new DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$ok   = '' !== trim( $xml ) && $dom->loadXML( $xml );
		$errs = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$this->expect( $ok && ! $errs, 'S4: the feed is not well-formed' . ( $errs ? ': ' . trim( $errs[0]->message ) : '.' ) );

		$out = 'well-formed: ' . ( $ok && ! $errs ? 'yes' : 'no' ) . "\n";
		if ( $ok ) {
			foreach ( $dom->getElementsByTagName( 'item' ) as $item ) {
				$title = $item->getElementsByTagName( 'title' )->item( 0 );
				$out  .= '--- item: ' . ( $title ? $title->textContent : '' ) . "\n";
				foreach ( [ 'description', 'encoded' ] as $tag ) {
					$node = $item->getElementsByTagName( $tag )->item( 0 );
					$out .= $tag . ': ' . ( $node ? WPP_Normaliser::html( $node->textContent ) : '(none)' ) . "\n";
				}
			}
		} else {
			$out .= WPP_Normaliser::html( $xml );
		}
		$this->section( 'S4', 'feed, logged out', $out );
	}

	/**
	 * S5: head, excerpts and embed output, logged out.
	 *
	 * @return void
	 */
	private function s5() {
		$this->act_as( '' );
		$html = '' !== $this->full_page ? $this->full_page : $this->page( $this->permalink( $this->config['main'] ) );
		$head = preg_match( '#<head[^>]*>(.*?)</head>#s', $html, $m ) ? $m[1] : '';
		$this->no_marker( 'S5', 'the head', $head );

		$post = get_post( WPP_User_Config_Loader::ids()[ $this->config['main'] ] );
		$this->fresh_request();
		$this->tc->go_to( $this->permalink( $this->config['main'] ) );
		$excerpt = get_the_excerpt( $post );
		$embed   = apply_filters( 'the_excerpt_embed', $excerpt ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$this->no_marker( 'S5', 'get_the_excerpt()', $excerpt );
		$this->no_marker( 'S5', 'the embed excerpt', $embed );

		// The prompt stylesheet is expected output; whether it sits in <head> depends on the WordPress version
		// (6.9+ moves late styles of classic themes into the head), so its own <link> tag is not counted.
		$mentions = preg_match_all( '/wp-private/i', (string) preg_replace( "#<link[^>]+id=['\"]wp-private-prompt-css['\"][^>]*>#i", '', $head ) );
		$this->section( 'S5', 'head and excerpts, logged out', "head mentions of wp-private: {$mentions}\nexcerpt: " . WPP_Normaliser::html( $excerpt ) . "\nembed excerpt: " . WPP_Normaliser::html( $embed ) );
	}

	/**
	 * S6: search, logged out. A gated word is still found until R05; the marker must not show.
	 *
	 * @return void
	 */
	private function s6() {
		$this->act_as( '' );
		$word = $this->config['search_word'];
		$html = $this->page( home_url( '/?s=' . rawurlencode( $word ) ) );
		$this->no_marker( 'S6', 'the search page', $html );
		$posts = $this->rest( '/wp/v2/posts', [ 'search' => $word ] );
		$all   = $this->rest( '/wp/v2/search', [ 'search' => $word ] );
		$this->no_marker( 'S6', 'REST posts search', $posts['json'] );
		$this->no_marker( 'S6', 'REST search', $all['json'] );
		$this->section(
			'S6',
			'search by a word inside the gate, logged out (known limitation until R05: the post is still found)',
			'front end finds the post: ' . ( false !== strpos( $html, 'Gated post' ) ? 'yes' : 'no' ) .
			"\nREST posts finds the post: " . ( count( (array) $posts['data'] ) ? 'yes' : 'no' ) .
			"\nREST search finds the post: " . ( count( (array) $all['data'] ) ? 'yes' : 'no' )
		);
	}

	/**
	 * Expected content of the allowed viewer's render: the post with the gate replaced by a pass-through shortcode.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function ungated_content( $post ) {
		global $shortcode_tags;
		$saved = $shortcode_tags['protected'] ?? null;
		remove_shortcode( 'protected' );
		add_shortcode(
			'protected',
			static function ( $atts, $content = null ) {
				unset( $atts );
				return do_shortcode( (string) $content );
			}
		);
		$out = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		remove_shortcode( 'protected' );
		if ( null !== $saved ) {
			$shortcode_tags['protected'] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		return $out;
	}

	/**
	 * S7: the permitted viewer sees the content the post has without the gate.
	 *
	 * @return void
	 */
	private function s7() {
		$this->act_as( $this->config['viewer'] );
		$post = get_post( WPP_User_Config_Loader::ids()[ $this->config['main'] ] );
		$this->fresh_request();
		$this->tc->go_to( $this->permalink( $this->config['main'] ) );
		$gated    = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$expected = $this->ungated_content( $post );
		foreach ( $this->config['markers'] as $marker ) {
			$this->expect( false !== strpos( $gated, $marker ), 'S7: the permitted viewer does not see ' . $marker );
		}
		$this->expect( $gated === $expected, "S7: the permitted viewer's content differs from the same post without the gate." );
		$html = $this->page( $this->permalink( $this->config['main'] ) );
		foreach ( $this->config['markers'] as $marker ) {
			$this->expect( false !== strpos( $html, $marker ), 'S7: the permitted viewer\'s page lacks ' . $marker );
		}
		$this->expect( false === strpos( $this->main_slice( $html ), 'name="log"' ), 'S7: a login form is shown to a logged-in viewer.' );
		$this->section( 'S7', 'permitted viewer', 'identical to the ungated post: ' . ( $gated === $expected ? 'yes' : 'no' ) . "\n" . WPP_Normaliser::html( $gated ) );
	}

	/**
	 * The text a blocked user should see, from the raw stored data.
	 *
	 * @return string
	 */
	private function expected_authorized_text() {
		$options = $this->config['options'];
		if ( isset( $options['wp_private_settings'] ) && is_array( $options['wp_private_settings'] ) ) {
			if ( array_key_exists( 'authorized_text', $options['wp_private_settings'] ) ) {
				return (string) $options['wp_private_settings']['authorized_text'];
			}
		} elseif ( isset( $options['wp_private_settings_authorized_text'] ) ) {
			return (string) $options['wp_private_settings_authorized_text'];
		} elseif ( isset( $options['wp_private_not_authorized_text'] ) ) {
			return (string) $options['wp_private_not_authorized_text'];
		}
		return $this->strict ? WpPrivate\Settings::default_authorized_text() : '';
	}

	/**
	 * S8: a viewer who fails the gate (blocked user).
	 *
	 * @return void
	 */
	private function s8() {
		$keys = array_merge( [ $this->config['blocked'] ], (array) ( $this->config['extra_blocked'] ?? [] ) );
		foreach ( $keys as $i => $key ) {
			$this->act_as( $key );
			$html = $this->page( $this->permalink( $this->config['main'] ) );
			$this->no_marker( 'S8', 'the page of blocked user ' . $key, $html );
			if ( 0 === $i ) {
				$text = $this->expected_authorized_text();
				$this->expect( '' === $text || false !== strpos( $html, $text ), 'S8: the blocked user does not see the authorised-text message.' );
				$this->expect( false === strpos( $this->main_slice( $html ), 'name="log"' ), 'S8: a login form is shown to a blocked user.' );
				$this->section( 'S8', 'blocked user ' . $key, $this->main_slice( $html ) );
			}
		}
		// A user who is not on the list still gets in.
		$this->act_as( $this->config['viewer'] );
		$html = $this->page( $this->permalink( $this->config['main'] ) );
		$this->expect( false !== strpos( $html, $this->config['markers'][0] ), 'S8: an unblocked user lost access.' );
	}

	/**
	 * Parse the return address out of a prompt.
	 *
	 * @param string $slice Content region.
	 * @param string $mode  form or link.
	 * @return string The address after the one decode a browser and wp-login.php perform; '' if none found.
	 */
	private function return_address( $slice, $mode ) {
		if ( 'form' === $mode && preg_match( '/name="redirect_to"\s+value="([^"]*)"/', $slice, $m ) ) {
			return html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
		}
		if ( 'link' === $mode && preg_match( '/class="wp-private-login"\s+href="([^"]*)"/', $slice, $m ) ) {
			$url  = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			$back = $this->query_value( $url, 'redirect_to' );
			return '' !== $back ? $back : $this->query_value( $url, 'redirect' );
		}
		return '';
	}

	/**
	 * One decoded query argument of a URL.
	 *
	 * @param string $url  URL.
	 * @param string $name Argument.
	 * @return string
	 */
	private function query_value( $url, $name ) {
		$query = [];
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		return isset( $query[ $name ] ) && is_string( $query[ $name ] ) ? $query[ $name ] : '';
	}

	/**
	 * S9: login round trip. The return address is encoded once and local, and lands on the same post.
	 *
	 * @return void
	 */
	private function s9() {
		$this->act_as( '' );
		$post_url = $this->permalink( $this->config['main'] );
		$mode     = $this->effective_type();
		$html     = $this->page( $post_url );
		$slice    = $this->main_slice( $html );
		$address  = $this->return_address( $slice, $mode );
		$report   = 'mode: ' . $mode . "\nreturn address (after one decode): " . $address . "\n";

		$this->expect( $address === $post_url, 'S9: the return address is "' . $address . '", expected "' . $post_url . '" (encoded once, local).' );
		$this->expect( false === strpos( $address, '%25' ) && false === strpos( $address, '%3A' ), 'S9: the return address is encoded twice: ' . $address );
		$this->expect( wp_validate_redirect( $address, '' ) === $address, 'S9: wp-login.php would not accept the return address: ' . $address );

		// wp-login.php after a successful login: login_redirect, then wp_safe_redirect().
		$viewer = get_userdata( WPP_User_Config_Loader::user_ids()[ $this->config['viewer'] ] );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$landing = wp_validate_redirect( apply_filters( 'login_redirect', $address, $address, $viewer ), admin_url() );
		$this->expect( $landing === $post_url, 'S9: after logging in the visitor lands on "' . $landing . '", not the post.' );
		$report .= 'lands on: ' . $landing . "\n";
		$this->act_as( $this->config['viewer'] );
		$again = $this->page( $landing );
		$this->expect( false !== strpos( $again, $this->config['markers'][0] ), 'S9: the post after the round trip does not show the content.' );

		// Context other than HTML (REST, feed): the link variant.
		$this->act_as( '' );
		$this->tc->go_to( $post_url );
		$link = WpPrivate\Prompt::login_url( WpPrivate\Prompt::return_url( WpPrivate\Prompt::CONTEXT_REST ) );
		$name = '' !== WpPrivate\Settings::custom_login_url() ? 'redirect' : 'redirect_to';
		$back = $this->query_value( $link, $name );
		$this->expect( $back === $post_url, 'S9: the REST/feed login link returns to "' . $back . '", expected "' . $post_url . '".' );
		$this->expect( false === strpos( $back, '%' ), 'S9: the REST/feed login link is encoded twice: ' . $back );
		$report .= 'link in REST/feed output: ' . $link . "\n";

		// A custom login page that carries [loginform]: it honours "redirect" and encodes it once.
		if ( ! empty( $this->config['login_page'] ) ) {
			$custom = $this->page( WpPrivate\Prompt::login_url( $post_url ) );
			$form   = $this->return_address( $this->main_slice( $custom ), 'form' );
			$this->expect( $form === $post_url, 'S9: the [loginform] on the custom login page posts "' . $form . '", expected "' . $post_url . '".' );
			$report .= 'custom login page form posts: ' . $form . "\n";
		}
		$this->section( 'S9', 'login round trip', $report );
	}

	/**
	 * S10: load the settings page, save it unchanged through the real save path, and compare the stored bytes.
	 *
	 * The save path is wp-admin/options.php: the browser's form values, trimmed (non-arrays), unslashed,
	 * then update_option() for every allowed option, null for a field that was not posted.
	 *
	 * @return void
	 */
	public function s10() {
		global $wpdb;
		$options = [];
		foreach ( WpPrivate\Upgrade::TARGETS as $option ) {
			$options[] = $option;
		}
		$raw    = static function ( $name ) use ( $wpdb ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ), ARRAY_N ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return $row ? $row[0] : null;
		};
		$before = [];
		foreach ( $options as $option ) {
			$before[ $option ] = $raw( $option );
		}

		$this->act_as( $this->config['admin'] );
		$this->open_settings_screen();
		$html = $this->settings_html();
		$post = $this->form_values( $html, $form_ok );
		$this->expect( $form_ok, 'S10: no settings form posting to options.php on the settings screen.' );
		$this->expect( isset( $post['option_page'] ) && '' !== $post['option_page'], 'S10: the form has no option_page field.' );
		$this->expect( isset( $post['_wpnonce'] ), 'S10: the form has no nonce.' );

		$group   = $post['option_page'] ?? WpPrivate\Admin\Settings_Page::MENU_SLUG;
		$allowed = apply_filters( 'allowed_options', [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$this->expect( ! empty( $allowed[ $group ] ), 'S10: the option group is not registered for options.php.' );
		foreach ( (array) ( $allowed[ $group ] ?? [] ) as $option ) {
			$value = null;
			if ( isset( $post[ $option ] ) ) {
				$value = $post[ $option ];
				if ( ! is_array( $value ) ) {
					$value = trim( $value );
				}
				$value = wp_unslash( wp_slash( $value ) );
			}
			if ( getenv( 'WPP_DEBUG_S10' ) ) {
				fwrite( STDERR, $option . ' posted ' . var_export( $value, true ) . "\n" ); // phpcs:ignore
			}
			update_option( $option, $value );
		}

		$report = '';
		foreach ( $options as $option ) {
			$after = $raw( $option );
			if ( null !== $before[ $option ] ) {
				$this->expect( $before[ $option ] === $after, 'S10: saving the screen unchanged altered ' . $option . ': ' . var_export( $before[ $option ], true ) . ' became ' . var_export( $after, true ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			}
			$report .= $option . ': ' . ( null === $before[ $option ] ? '(absent)' : 'stored' ) . ' -> ' . ( null === $after ? '(absent)' : ( $before[ $option ] === $after ? 'unchanged' : 'CHANGED' ) ) . ' ' . ( null === $before[ $option ] ? ' now ' . var_export( $after, true ) : '' ) . "\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		}
		$this->section( 'S10', 'settings: load, save unchanged, reload', $report );

		// A stored </textarea> and an ampersand come back intact in the form.
		$this->expect( 0 === substr_count( strtolower( $html ), '</textarea></textarea>' ), 'S10: a stored </textarea> broke out of the field.' );
	}

	/**
	 * Put the settings screen in the state a request for it would have.
	 *
	 * @return void
	 */
	private function open_settings_screen() {
		global $menu, $submenu, $admin_page_hooks, $_registered_pages, $_parent_pages;
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- A fresh admin menu for this render.
		$menu              = [];
		$submenu           = [];
		$admin_page_hooks  = [];
		$_registered_pages = [];
		$_parent_pages     = [];
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		$page     = WpPrivate\Vendor\PedalCMS\CassetteCMF\Core\Manager::init()->get_new_settings_handler()->get_page( WpPrivate\Settings::PAGE_ID );
		$property = new ReflectionProperty( get_class( $page ), 'registered' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true ); // No effect from PHP 8.1, deprecated in 8.5.
		}
		$property->setValue( $page, false );
		set_current_screen( 'settings_page_wp-private' );
		remove_action( 'admin_init', 'send_frame_options_header' );
		remove_action( 'admin_init', 'wp_admin_headers' );
		// Update checks call WordPress.org, which the suite blocks.
		foreach ( [ '_maybe_update_core', '_maybe_update_plugins', '_maybe_update_themes' ] as $callback ) {
			remove_action( 'admin_init', $callback );
		}
		do_action( 'admin_menu' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		do_action( 'admin_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
	}

	/**
	 * Draw the settings screen.
	 *
	 * @return string
	 */
	private function settings_html() {
		$GLOBALS['title'] = 'WP Private'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Core reads it when drawing the screen.
		$_GET             = [];
		return $this->capture(
			static function () {
				WpPrivate\Admin\Settings_Page::render();
			}
		);
	}

	/**
	 * What a browser would post for the options.php form in the HTML.
	 *
	 * @param string $html Screen HTML.
	 * @param bool   $ok   Set to whether the form was found.
	 * @return array<string, string|string[]>
	 */
	private function form_values( $html, &$ok = null ) {
		$dom  = new DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$xpath = new DOMXPath( $dom );
		$forms = $xpath->query( '//form[@action="options.php"]' );
		$ok    = $forms->length > 0;
		$post  = [];
		if ( ! $ok ) {
			return $post;
		}
		$add = static function ( $name, $value ) use ( &$post ) {
			if ( '[]' === substr( $name, -2 ) ) {
				$post[ substr( $name, 0, -2 ) ][] = $value;
			} else {
				$post[ $name ] = $value;
			}
		};
		foreach ( $xpath->query( './/input[@name]|.//textarea[@name]|.//select[@name]', $forms->item( 0 ) ) as $field ) {
			$name = $field->getAttribute( 'name' );
			$tag  = $field->nodeName;
			if ( 'textarea' === $tag ) {
				$add( $name, $field->textContent );
			} elseif ( 'select' === $tag ) {
				foreach ( $xpath->query( './/option[@selected]', $field ) as $option ) {
					$add( $name, $option->getAttribute( 'value' ) );
				}
			} else {
				$type = strtolower( $field->getAttribute( 'type' ) );
				if ( in_array( $type, [ 'checkbox', 'radio' ], true ) && ! $field->hasAttribute( 'checked' ) ) {
					continue;
				}
				if ( in_array( $type, [ 'submit', 'button', 'file' ], true ) ) {
					continue;
				}
				$add( $name, $field->getAttribute( 'value' ) );
			}
		}
		return $post;
	}

	/**
	 * Settings screen as a sub-site administrator without unfiltered_html: a saved <script> never reaches the option.
	 *
	 * @return void
	 */
	public function s10_unfiltered_html() {
		$this->act_as( $this->config['admin'] );
		$can = current_user_can( 'unfiltered_html' );
		$this->open_settings_screen();
		update_option( WpPrivate\Settings::OPTION_BEFORE_HTML, '<p>ok</p><script>alert(1)</script>' );
		$stored = get_option( WpPrivate\Settings::OPTION_BEFORE_HTML );
		$this->expect( ! $can, 'S10: this administrator unexpectedly has unfiltered_html.' );
		$this->expect( false === strpos( (string) $stored, '<script' ), 'S10: a <script> was stored by an administrator without unfiltered_html.' );
		// WordPress 7.3 trunk also drops the script's text; older cores leave "alert(1)" behind. Only the markup is the plugin's concern.
		$this->section( 'S10', 'administrator without unfiltered_html saves a <script>', 'unfiltered_html: no' . "\nstored: " . str_replace( 'alert(1)', '', (string) $stored ) );
	}

	/**
	 * S11, data half: the upgrade conversion from the stored 1.x data, as the first request after a file swap ran it.
	 *
	 * Each per-field option equals the matching key of the old array byte for byte; the old option rows are still
	 * present and unchanged; a second run, and a run after a setting changed, change nothing.
	 *
	 * @return void
	 */
	public function s11_data() {
		global $wpdb;
		$raw    = static function ( $name ) use ( $wpdb ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ), ARRAY_N ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return $row ? $row[0] : null;
		};
		$rows   = static function () use ( $wpdb ) {
			return $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wp\\_private\\_%' ORDER BY option_name", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		};
		$stored = $this->resolve_ids( $this->config['options'] );
		$array  = $stored['wp_private_settings'] ?? null;
		$report = '';

		foreach ( WpPrivate\Upgrade::TARGETS as $setting => $option ) {
			$actual = $raw( $option );
			if ( is_array( $array ) ) {
				$expected = array_key_exists( $setting, $array ) ? maybe_serialize( $array[ $setting ] ) : null;
			} elseif ( 'blocked_users' === $setting && isset( $stored[ WpPrivate\Upgrade::LEGACY_OPTIONS[ $setting ] ] ) ) {
				$ids      = array_values( array_filter( array_map( 'absint', explode( ',', $stored[ WpPrivate\Upgrade::LEGACY_OPTIONS[ $setting ] ] ) ) ) );
				$expected = maybe_serialize( $ids );
			} else {
				$expected = $stored[ WpPrivate\Upgrade::LEGACY_OPTIONS[ $setting ] ] ?? null;
			}
			$this->expect( $expected === $actual, 'S11: ' . $option . ' is ' . var_export( $actual, true ) . ', the 1.x data says ' . var_export( $expected, true ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			$report .= $option . ': ' . ( null === $actual ? '(absent)' : $actual ) . "\n";
		}
		foreach ( $stored as $name => $value ) {
			if ( 0 !== strpos( $name, 'wp_private_' ) ) {
				continue;
			}
			$expected = maybe_serialize( $value );
			$this->expect( $raw( $name ) === $expected, 'S11: the 1.x option ' . $name . ' was changed or removed.' );
		}

		$snapshot = $rows();
		WpPrivate\Upgrade::run();
		$this->expect( $snapshot === $rows(), 'S11: a second run of the upgrade routine changed the data.' );
		$this->section( 'S11', 'per-field options after the first request on the new build', $report );
	}

	/**
	 * S11, last steps: running the routine again changes nothing after a setting was changed in 2.x. Leaves the data changed, so it runs last.
	 *
	 * 1. A run after a changed setting (the version row is there) changes nothing.
	 * 2. The version row is gone (lost, or a restored backup): the routine runs again but never overwrites a stored value.
	 * 3. A later version bump never refills a field the administrator emptied or removed.
	 *
	 * @return void
	 */
	public function s11_after_change() {
		global $wpdb;
		$rows = static function ( $with_version ) use ( $wpdb ) {
			$all = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wp\\_private\\_%' ORDER BY option_name", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return array_values(
				array_filter(
					$all,
					static function ( $row ) use ( $with_version ) {
						return $with_version || WpPrivate\Upgrade::VERSION_OPTION !== $row['option_name'];
					}
				)
			);
		};

		update_option( WpPrivate\Settings::OPTION_TYPE, 'link' );
		update_option( WpPrivate\Settings::OPTION_BEFORE_HTML, '<i>changed since the upgrade</i>' );
		$snapshot = $rows( true );
		WpPrivate\Upgrade::run();
		WpPrivate\Upgrade::maybe_upgrade();
		$this->expect( $snapshot === $rows( true ), 'S11: running the upgrade routine after a setting was changed overwrote it.' );

		$snapshot = $rows( false );
		delete_option( WpPrivate\Upgrade::VERSION_OPTION );
		WpPrivate\Upgrade::maybe_upgrade();
		$this->expect( $snapshot === $rows( false ), 'S11: with the version row missing, the upgrade routine overwrote a stored value.' );
		$this->expect( WP_PRIVATE_VERSION === get_option( WpPrivate\Upgrade::VERSION_OPTION ), 'S11: the routine did not store the version.' );

		delete_option( WpPrivate\Settings::OPTION_AFTER_HTML );
		update_option( WpPrivate\Upgrade::VERSION_OPTION, '1.9.0' );
		WpPrivate\Upgrade::maybe_upgrade();
		$this->expect( ! WpPrivate\Settings::is_stored( WpPrivate\Settings::OPTION_AFTER_HTML ), 'S11: a later version bump refilled a field that had been removed.' );
	}

	/**
	 * Resolve "{user:key}" in a stored value the way the loader did.
	 *
	 * @param mixed $value Value from the config.
	 * @return mixed
	 */
	private function resolve_ids( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'resolve_ids' ], $value );
		}
		if ( ! is_string( $value ) ) {
			return $value;
		}
		return preg_replace_callback(
			'/\{user:([a-z0-9_-]+)\}/',
			static function ( $m ) {
				return (string) WPP_User_Config_Loader::user_ids()[ $m[1] ];
			},
			$value
		);
	}

	/**
	 * Does the config apply to S11 and S12 (stored by a 1.x version)?
	 *
	 * @return bool
	 */
	public function has_legacy_data() {
		return '2.x' !== $this->config['version'];
	}

	/**
	 * S12: this build's data under the 1.6.1 files. Which of the listed viewers must not see a marker.
	 *
	 * @param string[] $viewers User keys; '' is a logged-out visitor.
	 * @return void
	 */
	public function hidden_from( array $viewers ) {
		foreach ( $viewers as $key ) {
			$this->act_as( $key );
			$html = $this->page( $this->permalink( $this->config['main'] ) );
			$this->no_marker( 'S12', 'the page for ' . ( '' === $key ? 'a visitor' : 'user ' . $key ), $html );
		}
		$this->act_as( '' );
		$rest = $this->rest( '/wp/v2/posts/' . WPP_User_Config_Loader::ids()[ $this->config['main'] ] );
		$this->no_marker( 'S12', 'REST', $rest['json'] );
	}

	/**
	 * S12: a bare [protected] still gates under the 1.6.1 files: a visitor gets the prompt (or the wrapper in just-remove mode).
	 *
	 * @return void
	 */
	public function gate_still_prompts() {
		$this->act_as( '' );
		// The whole response: 1.6.1 prints its form while the template is built, which on a block theme is before the doctype.
		$slice = $this->page( $this->permalink( $this->config['main'] ) );
		$type  = $this->effective_type();
		$this->expect(
			( 'form' === $type && false !== strpos( $slice, 'name="log"' ) ) || ( 'link' === $type && false !== strpos( $slice, 'Login' ) ) || 'custom' === $type,
			'S12: under the 1.6.1 files the gate shows a visitor no prompt (mode ' . $type . ').'
		);
	}

	/**
	 * The view of the config for one user as plain data, for the old-vs-new comparison.
	 *
	 * @return array<string, string>
	 */
	public function capture_for_diff() {
		$this->run_requests();
		return $this->snaps;
	}
}
