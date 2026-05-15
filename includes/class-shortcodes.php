<?php
/**
 * Shortcodes.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public shortcodes for embedding job content.
 */
class APQRINU_Shortcodes {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_shortcode( 'apqrinu_listings', array( __CLASS__, 'listings' ) );
		add_shortcode( 'apqrinu_related', array( __CLASS__, 'related' ) );
		add_shortcode( 'apqrinu_apply_form', array( __CLASS__, 'apply_form' ) );
	}

	/**
	 * [apqrinu_listings] — full archive listing with filters.
	 *
	 * @param array $atts Shortcode atts.
	 * @return string
	 */
	public static function listings( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page'     => '',
				'show_filters' => 'yes',
			),
			$atts,
			'apqrinu_listings'
		);

		$apqrinu_file = APQRINU_Helpers::locate_template( 'parts/jobs-archive.php' );
		if ( ! $apqrinu_file ) {
			return '';
		}

		ob_start();
		$apqrinu_archive_args = array(
			'per_page'     => '' === $atts['per_page'] ? null : (int) $atts['per_page'],
			'show_filters' => 'yes' === $atts['show_filters'],
		);
		include $apqrinu_file;
		unset( $apqrinu_archive_args );
		return (string) ob_get_clean();
	}

	/**
	 * [apqrinu_related job_id="123" per_page="3"] — related jobs.
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public static function related( $atts ) {
		$atts = shortcode_atts(
			array(
				'job_id'   => 0,
				'per_page' => '',
			),
			$atts,
			'apqrinu_related'
		);

		$apqrinu_job_id = (int) $atts['job_id'];
		if ( ! $apqrinu_job_id && is_singular( 'apqrinu_job' ) ) {
			$apqrinu_job_id = (int) get_queried_object_id();
		}
		if ( ! $apqrinu_job_id ) {
			return '';
		}

		$apqrinu_file = APQRINU_Helpers::locate_template( 'parts/jobs-related.php' );
		if ( ! $apqrinu_file ) {
			return '';
		}

		ob_start();
		$apqrinu_related_args = array(
			'job_id'   => $apqrinu_job_id,
			'per_page' => '' === $atts['per_page'] ? null : (int) $atts['per_page'],
		);
		include $apqrinu_file;
		unset( $apqrinu_related_args );
		return (string) ob_get_clean();
	}

	/**
	 * [apqrinu_apply_form job_id="123"] — built-in apply form.
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public static function apply_form( $atts ) {
		$atts = shortcode_atts(
			array(
				'job_id' => 0,
			),
			$atts,
			'apqrinu_apply_form'
		);

		$apqrinu_job_id = (int) $atts['job_id'];
		if ( ! $apqrinu_job_id && is_singular( 'apqrinu_job' ) ) {
			$apqrinu_job_id = (int) get_queried_object_id();
		}
		if ( ! $apqrinu_job_id ) {
			return '';
		}

		$apqrinu_file = APQRINU_Helpers::locate_template( 'parts/apply-form.php' );
		if ( ! $apqrinu_file ) {
			return '';
		}

		ob_start();
		$apqrinu_apply_args = array( 'job_id' => $apqrinu_job_id );
		include $apqrinu_file;
		unset( $apqrinu_apply_args );
		return (string) ob_get_clean();
	}
}
