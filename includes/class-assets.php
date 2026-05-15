<?php
/**
 * Asset enqueuing.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Frontend and admin asset registration.
 */
class APQRINU_Assets {

	const HANDLE_CSS       = 'apqrinu-jobs';
	const HANDLE_JS        = 'apqrinu-jobs';
	const HANDLE_ADMIN_JS  = 'apqrinu-admin';
	const HANDLE_ADMIN_CSS = 'apqrinu-admin';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin' ) );
	}

	/**
	 * Are we on a jobs frontend context?
	 *
	 * @return bool
	 */
	public static function is_jobs_context() {
		if ( is_admin() ) {
			return false;
		}
		if ( is_post_type_archive( 'apqrinu_job' ) || is_singular( 'apqrinu_job' ) || is_tax( APQRINU_Helpers::taxonomies() ) ) {
			return true;
		}
		global $post;
		if ( $post instanceof WP_Post && false !== strpos( (string) $post->post_content, '[apqrinu_' ) ) {
			return true;
		}
		return apply_filters( 'apqrinu_load_assets', false );
	}

	/**
	 * Frontend assets.
	 */
	public static function frontend() {
		if ( ! self::is_jobs_context() ) {
			return;
		}

		wp_register_style(
			self::HANDLE_CSS,
			APQRINU_URL . 'assets/css/jobs.css',
			array(),
			APQRINU_VERSION
		);
		wp_register_script(
			self::HANDLE_JS,
			APQRINU_URL . 'assets/js/jobs.js',
			array(),
			APQRINU_VERSION,
			true
		);

		wp_enqueue_style( self::HANDLE_CSS );
		wp_enqueue_script( self::HANDLE_JS );

		wp_add_inline_style( self::HANDLE_CSS, self::color_vars_css() );

		wp_localize_script(
			self::HANDLE_JS,
			'ApqrinuData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apqrinu_nonce' ),
				'taxes'   => array_values( APQRINU_Helpers::taxonomies() ),
				'i18n'    => array(
					'submitting'    => __( 'Submitting…', 'apqrinu-job-board' ),
					'success'       => __( 'Application sent. Thank you!', 'apqrinu-job-board' ),
					'error'         => __( 'Something went wrong. Please try again.', 'apqrinu-job-board' ),
					'requiredField' => __( 'This field is required.', 'apqrinu-job-board' ),
					'invalidEmail'  => __( 'Please enter a valid email address.', 'apqrinu-job-board' ),
				),
			)
		);
	}

	/**
	 * Build the chosen color palette as CSS custom properties on :root.
	 *
	 * Attached to the frontend stylesheet via wp_add_inline_style().
	 *
	 * @return string
	 */
	private static function color_vars_css() {
		$primary       = APQRINU_Helpers::sanitize_hex_color( APQRINU_Helpers::get_setting( 'color_primary', '#4f46e5' ), '#4f46e5' );
		$primary_hover = APQRINU_Helpers::sanitize_hex_color( APQRINU_Helpers::get_setting( 'color_primary_hover', '#4338ca' ), '#4338ca' );
		$text          = APQRINU_Helpers::sanitize_hex_color( APQRINU_Helpers::get_setting( 'color_text', '#111827' ), '#111827' );
		$card_bg       = APQRINU_Helpers::sanitize_hex_color( APQRINU_Helpers::get_setting( 'color_card_bg', '#ffffff' ), '#ffffff' );
		$card_border   = APQRINU_Helpers::sanitize_hex_color( APQRINU_Helpers::get_setting( 'color_card_border', '#e5e7eb' ), '#e5e7eb' );
		$meta_bg       = APQRINU_Helpers::sanitize_hex_color( APQRINU_Helpers::get_setting( 'color_meta_bg', '#1f2937' ), '#1f2937' );
		$meta_text     = APQRINU_Helpers::sanitize_hex_color( APQRINU_Helpers::get_setting( 'color_meta_text', '#ffffff' ), '#ffffff' );

		return ':root {'
			. '--apqrinu-primary:' . $primary . ';'
			. '--apqrinu-primary-hover:' . $primary_hover . ';'
			. '--apqrinu-text:' . $text . ';'
			. '--apqrinu-card-bg:' . $card_bg . ';'
			. '--apqrinu-card-border:' . $card_border . ';'
			. '--apqrinu-meta-bg:' . $meta_bg . ';'
			. '--apqrinu-meta-text:' . $meta_text . ';'
			. '}';
	}

	/**
	 * Admin assets.
	 *
	 * @param string $hook Current admin hook.
	 */
	public static function admin( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Job edit screen: media picker.
		if ( $screen && 'apqrinu_job' === $screen->post_type ) {
			wp_enqueue_media();
			wp_register_script(
				self::HANDLE_ADMIN_JS,
				APQRINU_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				APQRINU_VERSION,
				true
			);
			wp_register_style(
				self::HANDLE_ADMIN_CSS,
				APQRINU_URL . 'assets/css/admin.css',
				array(),
				APQRINU_VERSION
			);
			wp_enqueue_script( self::HANDLE_ADMIN_JS );
			wp_enqueue_style( self::HANDLE_ADMIN_CSS );
		}

		// Settings page: color picker. The actual hook for a submenu under
		// edit.php?post_type=apqrinu_job is "apqrinu_job_page_apqrinu-settings",
		// but we also match the captured hook in case a future WP version
		// changes the naming convention.
		$settings_hook = class_exists( 'APQRINU_Settings' ) ? APQRINU_Settings::page_hook() : '';
		if ( 'apqrinu_job_page_apqrinu-settings' === $hook || ( '' !== $settings_hook && $settings_hook === $hook ) ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_register_script(
				self::HANDLE_ADMIN_JS,
				APQRINU_URL . 'assets/js/admin.js',
				array( 'jquery', 'wp-color-picker' ),
				APQRINU_VERSION,
				true
			);
			wp_register_style(
				self::HANDLE_ADMIN_CSS,
				APQRINU_URL . 'assets/css/admin.css',
				array( 'wp-color-picker' ),
				APQRINU_VERSION
			);
			wp_enqueue_script( self::HANDLE_ADMIN_JS );
			wp_enqueue_style( self::HANDLE_ADMIN_CSS );
		}
	}
}
