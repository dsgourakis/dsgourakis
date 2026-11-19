<?php
/**
 * The Résumé link: one site-wide setting that any button can use.
 *
 * @package DSG\Core
 */

namespace DSG\Core\Settings;

use WP_Block;

/**
 * Stores which PDF is the résumé, and feeds its URL to blocks.
 *
 * - Setting: Settings > General > Résumé. It stores the PDF's attachment ID,
 *   so uploading a new résumé is just picking another file.
 * - Block binding: a Button bound to the "dsg-core/resume" source gets the
 *   PDF's URL as its link. The header does this (parts/header.html).
 * - With no résumé set, bound buttons are not shown at all, rather than
 *   linking nowhere.
 */
class ResumeLink {

	/**
	 * Option name. Holds an attachment ID, 0 when none is set.
	 */
	public const OPTION = 'dsg_resume_id';

	/**
	 * Block bindings source name.
	 */
	public const SOURCE = 'dsg-core/resume';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_setting' ) );
		add_action( 'init', array( $this, 'register_binding_source' ) );
		add_action( 'admin_init', array( $this, 'add_settings_field' ) );
		add_filter( 'render_block_core/button', array( $this, 'hide_when_empty' ), 10, 2 );
		add_filter( 'render_block_core/buttons', array( $this, 'remove_empty_buttons_group' ) );
	}

	/**
	 * Register the option. On "init" (not "admin_init") so the REST API knows it too.
	 *
	 * @return void
	 */
	public function register_setting(): void {
		register_setting(
			'general',
			self::OPTION,
			array(
				'type'              => 'integer',
				'description'       => __( 'Attachment ID of the résumé PDF.', 'dsg-core' ),
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);
	}

	/**
	 * Register the "dsg-core/resume" source for block bindings.
	 *
	 * @return void
	 */
	public function register_binding_source(): void {
		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Résumé PDF', 'dsg-core' ),
				'get_value_callback' => array( self::class, 'url' ),
			)
		);
	}

	/**
	 * Add the field to Settings > General.
	 *
	 * @return void
	 */
	public function add_settings_field(): void {
		add_settings_field(
			self::OPTION,
			__( 'Résumé', 'dsg-core' ),
			array( $this, 'render_field' ),
			'general',
			'default',
			array( 'label_for' => self::OPTION )
		);
	}

	/**
	 * A dropdown of the PDFs in the Media Library.
	 *
	 * @return void
	 */
	public function render_field(): void {
		$pdfs    = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'application/pdf',
				'post_status'    => 'inherit',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$current = (int) get_option( self::OPTION, 0 );

		printf( '<select id="%1$s" name="%1$s">', esc_attr( self::OPTION ) );
		printf( '<option value="0">%s</option>', esc_html__( '— None (hide the Résumé button) —', 'dsg-core' ) );
		foreach ( $pdfs as $pdf ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $pdf->ID,
				selected( $current, $pdf->ID, false ),
				esc_html( sprintf( '%s (%s)', get_the_title( $pdf ), get_the_date( '', $pdf ) ) )
			);
		}
		echo '</select>';

		printf(
			'<p class="description">%s <a href="%s">%s</a></p>',
			esc_html__( 'The PDF the header’s Résumé button opens. To use a new version,', 'dsg-core' ),
			esc_url( admin_url( 'media-new.php' ) ),
			esc_html__( 'upload it to the Media Library first.', 'dsg-core' )
		);
	}

	/**
	 * Keep the option only when it points at a PDF attachment.
	 *
	 * @param mixed $value Submitted value.
	 * @return int
	 */
	public static function sanitize( $value ): int {
		$id = absint( $value );

		return ( $id && 'application/pdf' === get_post_mime_type( $id ) ) ? $id : 0;
	}

	/**
	 * The résumé's URL, or null when none is set or the file is gone.
	 *
	 * Also the bindings callback, which is why it returns null rather than "":
	 * null tells WordPress to leave the block's own value alone.
	 *
	 * @return string|null
	 */
	public static function url(): ?string {
		$id = (int) get_option( self::OPTION, 0 );
		if ( ! $id ) {
			return null;
		}

		$url = wp_get_attachment_url( $id );

		return $url ? $url : null;
	}

	/**
	 * Don't show a button bound to the résumé when there is no résumé.
	 *
	 * Runs after WordPress has applied the binding, so a set résumé is
	 * already in the button's href by now.
	 *
	 * @param string $block_content The Button block's HTML.
	 * @param array  $block         The block's data.
	 * @return string
	 */
	public function hide_when_empty( string $block_content, array $block ): string {
		$source = $block['attrs']['metadata']['bindings']['url']['source'] ?? '';

		if ( self::SOURCE === $source && null === self::url() ) {
			return '';
		}

		return $block_content;
	}

	/**
	 * Drop a Buttons group left with no buttons, so it doesn't add a gap.
	 *
	 * @param string $block_content The Buttons block's HTML.
	 * @return string
	 */
	public function remove_empty_buttons_group( string $block_content ): string {
		return str_contains( $block_content, 'wp-block-button__link' ) ? $block_content : '';
	}
}
