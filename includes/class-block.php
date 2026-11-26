<?php
/**
 * The Protected content block.
 *
 * @package WpPrivate
 */

namespace WpPrivate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Block
 *
 * wp-private/protected: a dynamic block whose inner blocks are the gated content (C-3.1). The server decides
 * (C-3.3): nothing is hidden by CSS or script. Same decision and prompt as the [protected] shortcode.
 */
class Block {

	/**
	 * Block name (frozen at the 2.1.0 tag, C-3.1).
	 */
	const NAME = 'wp-private/protected';

	/**
	 * Identifier prefix for the bundled block builder. Letters, digits and hyphens only.
	 */
	const PREFIX = 'wp-private';

	/**
	 * Handle of the editor-only stylesheet for the labelled frame.
	 */
	const EDITOR_STYLE = 'wp-private-block-editor';

	/**
	 * Hook the registration and the front-end stylesheet removal.
	 *
	 * @return void
	 */
	public static function register() {
		// Priority 5: the builder must see its first call before its own init@10 registration.
		add_action( 'init', [ self::class, 'register_block' ], 5 );
		// After the builder's enqueue_block_assets callback (priority 10) has run on the front end.
		add_action( 'wp_enqueue_scripts', [ self::class, 'dequeue_editor_style' ], 100 );
	}

	/**
	 * Register the block with the builder.
	 *
	 * @return void
	 */
	public static function register_block() {
		$builder = '\\WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\CassetteCMFBlocks';
		if ( ! class_exists( $builder ) ) {
			return;
		}

		wp_register_style( self::EDITOR_STYLE, plugins_url( 'assets/css/block-editor.css', WP_PRIVATE_FILE ), [], WP_PRIVATE_VERSION );
		$builder::boot( [ 'prefix' => self::PREFIX ] );
		$builder::register_from_array( [ 'blocks' => [ self::config() ] ] );
	}

	/**
	 * The block declaration.
	 *
	 * @return array<string, mixed>
	 */
	public static function config() {
		return [
			'id'           => self::NAME,
			'args'         => [
				'title'       => __( 'Protected content', 'wp-private' ),
				'description' => __( 'Show the blocks inside only to logged-in users. Visitors see a login prompt instead.', 'wp-private' ),
				'category'    => 'text',
				'icon'        => 'lock',
				'keywords'    => [ 'private', 'protected', 'login', 'members' ],
				// Registered below; loads in the editor only, never on the front end.
				'editorStyle' => [ self::EDITOR_STYLE ],
			],
			'fields'       => [],
			'inner_blocks' => [
				'enabled'  => true,
				'template' => [ [ 'core/paragraph', [] ] ],
			],
			'render'       => [
				'mode'     => 'dynamic',
				'callback' => [ self::class, 'render' ],
				'wrapper'  => [ 'use_block_wrapper_attributes' => false ],
			],
			// Editor only: a labelled frame around the inner blocks. No preview of the prompt.
			'preview'      => [
				'mode'   => 'markup',
				'markup' => [
					'tag'      => 'div',
					'class'    => 'wp-private-protected-frame',
					'children' => [
						[
							'tag'   => 'p',
							'class' => 'wp-private-protected-frame__label',
							'text'  => __( 'Visible to logged-in users', 'wp-private' ),
						],
						[ 'slot' => 'inner_blocks' ],
					],
				],
			],
		];
	}

	/**
	 * Render callback: the inner blocks for an allowed viewer, the same output as [protected] otherwise.
	 *
	 * @param array<string, mixed> $attributes Block attributes (none yet).
	 * @param string               $content    Rendered inner blocks.
	 * @return string
	 */
	public static function render( $attributes = [], $content = '' ) {
		unset( $attributes );
		switch ( Access::decision() ) {
			case Access::ALLOW:
				return (string) $content;
			case Access::BLOCKED:
				return Settings::authorized_text();
			default:
				return Prompt::render();
		}
	}

	/**
	 * The builder's editor stylesheet has no use on the front end.
	 *
	 * @return void
	 */
	public static function dequeue_editor_style() {
		$handle = self::PREFIX . '-blocks-editor-style';
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
