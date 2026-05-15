<?php
/**
 * Template loader.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides default templates for the 'apqrinu_job' CPT, allowing themes to override.
 */
class APQRINU_Template_Loader {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'archive_template_hierarchy', array( __CLASS__, 'archive_hierarchy' ) );
		add_filter( 'single_template_hierarchy', array( __CLASS__, 'single_hierarchy' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
	}

	/**
	 * Add apqrinu-job-board/archive-apqrinu_job.php candidate.
	 *
	 * @param array $templates Templates.
	 * @return array
	 */
	public static function archive_hierarchy( $templates ) {
		if ( is_post_type_archive( 'apqrinu_job' ) ) {
			array_unshift( $templates, 'apqrinu-job-board/archive-apqrinu_job.php' );
		}
		return $templates;
	}

	/**
	 * Add apqrinu-job-board/single-apqrinu_job.php candidate.
	 *
	 * @param array $templates Templates.
	 * @return array
	 */
	public static function single_hierarchy( $templates ) {
		if ( is_singular( 'apqrinu_job' ) ) {
			array_unshift( $templates, 'apqrinu-job-board/single-apqrinu_job.php' );
		}
		return $templates;
	}

	/**
	 * Fall back to plugin templates if the theme doesn't have one.
	 *
	 * @param string $template Template path picked by WP.
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( is_post_type_archive( 'apqrinu_job' ) ) {
			$theme = locate_template( array( 'apqrinu-job-board/archive-apqrinu_job.php', 'archive-apqrinu_job.php' ) );
			if ( $theme ) {
				return $theme;
			}
			return APQRINU_PATH . 'templates/archive-apqrinu_job.php';
		}

		if ( is_singular( 'apqrinu_job' ) ) {
			$theme = locate_template( array( 'apqrinu-job-board/single-apqrinu_job.php', 'single-apqrinu_job.php' ) );
			if ( $theme ) {
				return $theme;
			}
			return APQRINU_PATH . 'templates/single-apqrinu_job.php';
		}

		return $template;
	}
}
