<?php
/**
 * Block-list user picker field.
 *
 * @package WpPrivate
 */

namespace WpPrivate\Admin;

use WpPrivate\Vendor\PedalCMS\CassetteCMF\Field\Abstract_Field;
use WpPrivate\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Users_Field
 *
 * A user picker (fallback for WP-CMF-2): users in pages of 50 with a search box and a checkbox each. IDs on the
 * block list that are not on the current page (or no longer match a user) are posted back as hidden inputs, so that
 * saving never drops a stored ID (A-09). The stored value is an array of integers.
 */
class Users_Field extends Abstract_Field {

	/**
	 * Field type the settings page registers this class under.
	 */
	const TYPE = 'wp_private_users';

	/**
	 * Users per page.
	 */
	const PER_PAGE = 50;

	/**
	 * Query argument of the page number.
	 */
	const PAGE_ARG = 'wp_private_users_page';

	/**
	 * Query argument of the search term.
	 */
	const SEARCH_ARG = 'wp_private_users_s';

	/**
	 * Id of the standalone GET form the search box belongs to (a form cannot be nested in the settings form).
	 */
	const SEARCH_FORM = 'wp-private-users-search';

	/**
	 * A group of controls: the settings title is not a label for one of them.
	 *
	 * @var bool
	 */
	protected bool $uses_label_wrapper = false;

	/**
	 * Render the picker.
	 *
	 * @param mixed $value Stored block list.
	 * @return string HTML.
	 */
	public function render( $value = null ): string {
		$blocked = Settings::normalise_ids( $value );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view state (page number, search term).
		$paged  = isset( $_GET[ self::PAGE_ARG ] ) ? max( 1, absint( wp_unslash( $_GET[ self::PAGE_ARG ] ) ) ) : 1;
		$search = isset( $_GET[ self::SEARCH_ARG ] ) && is_string( $_GET[ self::SEARCH_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::SEARCH_ARG ] ) ) : '';
		// phpcs:enable

		$args = [
			'number'      => self::PER_PAGE,
			'paged'       => $paged,
			'orderby'     => 'login',
			'order'       => 'ASC',
			'count_total' => true,
			'fields'      => [ 'ID', 'user_login', 'display_name' ],
		];
		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'user_nicename', 'display_name' ];
		}
		$query = new \WP_User_Query( $args );
		$users = $query->get_results();
		$pages = (int) ceil( $query->get_total() / self::PER_PAGE );

		$shown  = [];
		$output = $this->render_wrapper_start();

		$output .= '<p class="description">' . esc_html(
			sprintf(
				/* translators: %d: number of users on the block list. */
				_n( '%d user is on the block list.', '%d users are on the block list.', count( $blocked ), 'wp-private' ),
				count( $blocked )
			)
		) . '</p>';

		$output .= '<p class="search-box">'
			. '<label class="screen-reader-text" for="' . esc_attr( self::SEARCH_ARG ) . '">' . esc_html__( 'Search users', 'wp-private' ) . '</label>'
			. '<input type="search" id="' . esc_attr( self::SEARCH_ARG ) . '" name="' . esc_attr( self::SEARCH_ARG ) . '" form="' . esc_attr( self::SEARCH_FORM ) . '" value="' . esc_attr( $search ) . '" /> '
			. '<button type="submit" class="button" form="' . esc_attr( self::SEARCH_FORM ) . '">' . esc_html__( 'Search users', 'wp-private' ) . '</button>'
			. '</p>';

		$output .= '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Users who cannot see protected content', 'wp-private' ) . '</legend>';
		foreach ( $users as $user ) {
			$id      = (int) $user->ID;
			$shown[] = $id;
			$output .= '<label style="display:block;margin:2px 0;"><input type="checkbox" name="' . esc_attr( $this->name ) . '[]" value="' . esc_attr( (string) $id ) . '"' . checked( in_array( $id, $blocked, true ), true, false ) . ' /> '
				. esc_html( $user->user_login );
			if ( '' !== $user->display_name && $user->display_name !== $user->user_login ) {
				$output .= ' <span class="description">' . esc_html( $user->display_name ) . '</span>';
			}
			$output .= '</label>';
		}
		if ( empty( $users ) ) {
			$output .= '<p>' . esc_html__( 'No users found.', 'wp-private' ) . '</p>';
		}
		$output .= '</fieldset>';

		// Every stored ID that is not shown on this page is sent back unchanged.
		foreach ( array_diff( $blocked, $shown ) as $id ) {
			$output .= '<input type="hidden" name="' . esc_attr( $this->name ) . '[]" value="' . esc_attr( (string) $id ) . '" />';
		}

		if ( $pages > 1 ) {
			$base    = add_query_arg( self::SEARCH_ARG, rawurlencode( $search ), admin_url( 'options-general.php?page=' . Settings_Page::MENU_SLUG ) );
			$output .= '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(
				(string) paginate_links(
					[
						'base'      => add_query_arg( self::PAGE_ARG, '%#%', $base ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $pages,
						'prev_text' => '&lsaquo;',
						'next_text' => '&rsaquo;',
					]
				)
			) . '</div></div>';
			$output .= '<p class="description">' . esc_html__( 'Changes made on this page are saved with the Save button; changes on other pages are lost when you switch pages without saving.', 'wp-private' ) . '</p>';
		}

		$output .= $this->render_description() . $this->render_wrapper_end();

		add_action( 'admin_footer', [ self::class, 'print_search_form' ] );

		return $output;
	}

	/**
	 * Print the standalone search form that the search box belongs to.
	 *
	 * @return void
	 */
	public static function print_search_form() {
		echo '<form id="' . esc_attr( self::SEARCH_FORM ) . '" method="get" action="' . esc_url( admin_url( 'options-general.php' ) ) . '"><input type="hidden" name="page" value="' . esc_attr( Settings_Page::MENU_SLUG ) . '" /></form>';
	}

	/**
	 * Unique positive integers; IDs with no matching user are kept.
	 *
	 * An unchanged save keeps the stored value as it is (S10): a list converted from 1.x holds ID strings, and
	 * rewriting them as integers would change the stored bytes without changing the setting.
	 *
	 * @param mixed $input Submitted value.
	 * @return array<int|string>
	 */
	public function sanitize( $input ) {
		$ids    = Settings::normalise_ids( $input );
		$stored = get_option( Settings::OPTION_BLOCKED_USERS, Settings::MISSING );

		if ( is_array( $stored ) ) {
			$stored_ids = Settings::normalise_ids( $stored );
			$sorted     = $ids;
			sort( $stored_ids );
			sort( $sorted );
			if ( $stored_ids === $sorted ) {
				return $stored;
			}
		}

		return $ids;
	}
}
