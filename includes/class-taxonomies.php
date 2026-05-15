<?php
/**
 * Job taxonomies.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers job-related taxonomies.
 *
 * Note: every taxonomy name is prefixed with 'apqrinu_' to avoid collisions
 * with other plugins using generic taxonomy names.
 */
class APQRINU_Taxonomies {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 11 );
	}

	/**
	 * Register all taxonomies.
	 */
	public static function register() {
		$taxes = array(
			'apqrinu_job_type'         => array(
				'singular' => __( 'Job Type', 'apqrinu-job-board' ),
				'plural'   => __( 'Job Types', 'apqrinu-job-board' ),
				'slug'     => 'job-type',
			),
			'apqrinu_work_mode'        => array(
				'singular' => __( 'Work Mode', 'apqrinu-job-board' ),
				'plural'   => __( 'Work Modes', 'apqrinu-job-board' ),
				'slug'     => 'work-mode',
			),
			'apqrinu_experience_level' => array(
				'singular' => __( 'Experience Level', 'apqrinu-job-board' ),
				'plural'   => __( 'Experience Levels', 'apqrinu-job-board' ),
				'slug'     => 'experience-level',
			),
			'apqrinu_job_location'     => array(
				'singular' => __( 'Location', 'apqrinu-job-board' ),
				'plural'   => __( 'Locations', 'apqrinu-job-board' ),
				'slug'     => 'job-location',
			),
		);

		foreach ( $taxes as $tax => $t ) {
			$labels = array(
				'name'          => $t['plural'],
				'singular_name' => $t['singular'],
				/* translators: %s: taxonomy name */
				'search_items'  => sprintf( __( 'Search %s', 'apqrinu-job-board' ), $t['plural'] ),
				/* translators: %s: taxonomy name */
				'all_items'     => sprintf( __( 'All %s', 'apqrinu-job-board' ), $t['plural'] ),
				/* translators: %s: taxonomy name */
				'edit_item'     => sprintf( __( 'Edit %s', 'apqrinu-job-board' ), $t['singular'] ),
				/* translators: %s: taxonomy name */
				'update_item'   => sprintf( __( 'Update %s', 'apqrinu-job-board' ), $t['singular'] ),
				/* translators: %s: taxonomy name */
				'add_new_item'  => sprintf( __( 'Add New %s', 'apqrinu-job-board' ), $t['singular'] ),
				/* translators: %s: taxonomy name */
				'new_item_name' => sprintf( __( 'New %s Name', 'apqrinu-job-board' ), $t['singular'] ),
				'menu_name'     => $t['plural'],
			);

			register_taxonomy(
				$tax,
				array( 'apqrinu_job' ),
				array(
					'labels'            => $labels,
					'public'            => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
					'hierarchical'      => false,
					'rewrite'           => array(
						'slug'         => $t['slug'],
						'with_front'   => false,
						'hierarchical' => false,
					),
					'query_var'         => $tax,
				)
			);
		}
	}
}
