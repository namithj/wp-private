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
 * A user picker (fallback for WP-CMF-2): the blocked users as ticked checkboxes (pages of 50), then a searchable list
 * (pages of 50) of the users not yet blocked. Blocked IDs not shown on the current page (other pages, IDs that no longer
 * match a user) are posted back as one hidden comma-separated input, so that saving never drops a stored ID (A-09) and
 * the form posts at most about 100 variables however long the list is (PHP's max_input_vars cuts the rest silently).
 * The stored value is an array of integers.
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
	 * Query argument of the block-list page number.
	 */
	const BLOCKED_PAGE_ARG = 'wp_private_blocked_page';

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

		$list_url = admin_url( 'options-general.php?page=' . Settings_Page::MENU_SLUG );

		// Section A: one page of the blocked users, ticked; unticking removes the user. The other IDs ride in one hidden input.
		$sorted = $blocked;
		sort( $sorted );
		$blocked_pages = max( 1, (int) ceil( count( $sorted ) / self::PER_PAGE ) );
		$blocked_paged = isset( $_GET[ self::BLOCKED_PAGE_ARG ] ) ? min( $blocked_pages, max( 1, absint( wp_unslash( $_GET[ self::BLOCKED_PAGE_ARG ] ) ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view state.
		$blocked_users = [];
		if ( ! empty( $sorted ) ) {
			$blocked_users = get_users(
				[
					'include' => array_slice( $sorted, ( $blocked_paged - 1 ) * self::PER_PAGE, self::PER_PAGE ),
					'orderby' => 'login',
					'order'   => 'ASC',
					'fields'  => [ 'ID', 'user_login', 'display_name' ],
				]
			);
		}
		$known  = [];
		$output = $this->render_wrapper_start();

		$output .= '<div class="wp-private-users-blocked"><h3>' . esc_html(
			sprintf(
				/* translators: %d: number of users on the block list. */
				_n( '%d user is on the block list.', '%d users are on the block list.', count( $blocked ), 'wp-private' ),
				count( $blocked )
			)
		) . '</h3>';
		if ( empty( $blocked ) ) {
			$output .= '<p>' . esc_html__( 'Nobody is blocked. Every logged-in user can see protected content.', 'wp-private' ) . '</p>';
		} else {
			$output .= '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Users on the block list', 'wp-private' ) . '</legend>'
				. $this->render_user_list( $blocked_users, true )
				. '</fieldset>'
				. '<p class="description">' . esc_html__( 'Untick a user to remove them from the list.', 'wp-private' ) . '</p>';
			foreach ( $blocked_users as $user ) {
				$known[] = (int) $user->ID;
			}
			$rest = array_values( array_diff( $blocked, $known ) );
			if ( ! empty( $rest ) ) {
				$output .= '<input type="hidden" name="' . esc_attr( $this->name ) . '[]" value="' . esc_attr( implode( ',', $rest ) ) . '" />';
			}
			if ( $blocked_pages > 1 ) {
				$blocked_base = add_query_arg( self::SEARCH_ARG, rawurlencode( $search ), add_query_arg( self::PAGE_ARG, $paged, $list_url ) );
				$output      .= '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(
					(string) paginate_links(
						[
							'base'      => add_query_arg( self::BLOCKED_PAGE_ARG, '%#%', $blocked_base ),
							'format'    => '',
							'current'   => $blocked_paged,
							'total'     => $blocked_pages,
							'prev_text' => '&lsaquo;',
							'next_text' => '&rsaquo;',
						]
					)
				) . '</div></div>';
				$output      .= '<p class="description">' . esc_html__( 'Save before moving to another page, or the ticks on this page are lost.', 'wp-private' ) . '</p>';
			}
		}
		$output .= '</div>';

		// Section B: users who are not blocked yet, searchable and paged.
		$args = [
			'number'      => self::PER_PAGE,
			'paged'       => $paged,
			'orderby'     => 'login',
			'order'       => 'ASC',
			'count_total' => true,
			'fields'      => [ 'ID', 'user_login', 'display_name' ],
		];
		if ( ! empty( $blocked ) ) {
			$args['exclude'] = $blocked;
		}
		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'user_nicename', 'display_name' ];
		}
		$query = new \WP_User_Query( $args );
		$users = $query->get_results();
		$pages = (int) ceil( $query->get_total() / self::PER_PAGE );

		$output .= '<div class="wp-private-users-add"><h3>' . esc_html__( 'Add users', 'wp-private' ) . '</h3>';
		$output .= '<p class="search-box">'
			. '<label class="screen-reader-text" for="' . esc_attr( self::SEARCH_ARG ) . '">' . esc_html__( 'Search users', 'wp-private' ) . '</label>'
			. '<input type="search" id="' . esc_attr( self::SEARCH_ARG ) . '" name="' . esc_attr( self::SEARCH_ARG ) . '" form="' . esc_attr( self::SEARCH_FORM ) . '" value="' . esc_attr( $search ) . '" /> '
			. '<button type="submit" class="button" form="' . esc_attr( self::SEARCH_FORM ) . '">' . esc_html__( 'Search users', 'wp-private' ) . '</button>'
			. '</p>';
		if ( '' !== $search ) {
			$output .= '<p>' . esc_html(
				sprintf(
					/* translators: %s: the search term. */
					__( 'Results for “%s”.', 'wp-private' ),
					$search
				)
			) . ' <a href="' . esc_url( $list_url ) . '">' . esc_html__( 'Clear search', 'wp-private' ) . '</a></p>';
		}

		if ( empty( $users ) ) {
			$none    = '' === $search && ! empty( $blocked ) ? __( 'Every user is already on the block list.', 'wp-private' ) : __( 'No users found.', 'wp-private' );
			$output .= '<p>' . esc_html( $none ) . '</p>';
		} else {
			$output .= '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Users you can add to the block list', 'wp-private' ) . '</legend>'
				. $this->render_user_list( $users, false )
				. '</fieldset>';
		}

		if ( $pages > 1 ) {
			$base    = add_query_arg( self::SEARCH_ARG, rawurlencode( $search ), add_query_arg( self::BLOCKED_PAGE_ARG, $blocked_paged, $list_url ) );
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
			$output .= '<p class="description">' . esc_html__( 'Save before moving to another page, or the ticks on this page are lost.', 'wp-private' ) . '</p>';
		}
		$output .= '</div>';

		$output .= $this->render_description() . $this->render_wrapper_end();

		add_action( 'admin_footer', [ self::class, 'print_search_form' ] );

		return $output;
	}

	/**
	 * A list of user checkboxes.
	 *
	 * @param array<int, object> $users   Users (ID, user_login, display_name).
	 * @param bool               $checked Whether the boxes start ticked.
	 * @return string HTML.
	 */
	private function render_user_list( array $users, $checked ) {
		$output = '<ul class="wp-private-user-list">';
		foreach ( $users as $user ) {
			$output .= '<li><label><input type="checkbox" name="' . esc_attr( $this->name ) . '[]" value="' . esc_attr( (string) (int) $user->ID ) . '"' . checked( $checked, true, false ) . ' /> '
				. esc_html( $user->user_login );
			if ( '' !== $user->display_name && $user->display_name !== $user->user_login ) {
				$output .= ' <span class="description">' . esc_html( $user->display_name ) . '</span>';
			}
			$output .= '</label></li>';
		}
		return $output . '</ul>';
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
		if ( is_array( $input ) ) {
			$flat = [];
			foreach ( $input as $item ) {
				if ( is_string( $item ) && false !== strpos( $item, ',' ) ) {
					$flat = array_merge( $flat, explode( ',', $item ) );
				} else {
					$flat[] = $item;
				}
			}
			$input = $flat;
		}
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
