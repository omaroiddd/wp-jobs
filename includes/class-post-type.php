<?php
/**
 * Custom post types.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the 'apqrinu_job' and 'apqrinu_application' post types.
 */
class APQRINU_Post_Type {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the post types.
	 */
	public static function register() {
		self::register_job();
		self::register_application();
	}

	/**
	 * Register the public 'apqrinu_job' CPT.
	 */
	private static function register_job() {
		$labels = array(
			'name'               => _x( 'Jobs', 'post type general name', 'apqrinu-job-board' ),
			'singular_name'      => _x( 'Job', 'post type singular name', 'apqrinu-job-board' ),
			'menu_name'          => _x( 'Jobs', 'admin menu', 'apqrinu-job-board' ),
			'name_admin_bar'     => _x( 'Job', 'add new on admin bar', 'apqrinu-job-board' ),
			'add_new'            => _x( 'Add New', 'job', 'apqrinu-job-board' ),
			'add_new_item'       => __( 'Add New Job', 'apqrinu-job-board' ),
			'new_item'           => __( 'New Job', 'apqrinu-job-board' ),
			'edit_item'          => __( 'Edit Job', 'apqrinu-job-board' ),
			'view_item'          => __( 'View Job', 'apqrinu-job-board' ),
			'all_items'          => __( 'All Jobs', 'apqrinu-job-board' ),
			'search_items'       => __( 'Search Jobs', 'apqrinu-job-board' ),
			'not_found'          => __( 'No jobs found.', 'apqrinu-job-board' ),
			'not_found_in_trash' => __( 'No jobs found in Trash.', 'apqrinu-job-board' ),
		);

		$args = array(
			'labels'        => $labels,
			'public'        => true,
			'menu_icon'     => 'dashicons-id-alt',
			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => apply_filters( 'apqrinu_job_slug', 'jobs' ),
				'with_front' => false,
			),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'show_in_rest'  => true,
			'hierarchical'  => false,
			'menu_position' => 20,
		);

		register_post_type( 'apqrinu_job', $args );
	}

	/**
	 * Register the private 'apqrinu_application' CPT for storing applications.
	 */
	private static function register_application() {
		$labels = array(
			'name'          => __( 'Applications', 'apqrinu-job-board' ),
			'singular_name' => __( 'Application', 'apqrinu-job-board' ),
			'menu_name'     => __( 'Applications', 'apqrinu-job-board' ),
			'all_items'     => __( 'All Applications', 'apqrinu-job-board' ),
			'edit_item'     => __( 'View Application', 'apqrinu-job-board' ),
			'search_items'  => __( 'Search Applications', 'apqrinu-job-board' ),
			'not_found'     => __( 'No applications found.', 'apqrinu-job-board' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=apqrinu_job',
			'capability_type'     => 'post',
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
		);

		register_post_type( 'apqrinu_application', $args );
	}
}
