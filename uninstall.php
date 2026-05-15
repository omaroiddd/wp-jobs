<?php
/**
 * Uninstall handler.
 *
 * @package Apqrinu_Job_Board
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove plugin options.
delete_option( 'apqrinu_settings' );
delete_site_option( 'apqrinu_settings' );

// Remove all 'apqrinu_application' posts and their meta.
$apqrinu_application_ids = get_posts(
	array(
		'post_type'   => 'apqrinu_application',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);
foreach ( (array) $apqrinu_application_ids as $apqrinu_aid ) {
	wp_delete_post( (int) $apqrinu_aid, true );
}

// Remove plugin meta keys from job posts.
$apqrinu_meta_keys = array(
	'_apqrinu_job_summary',
	'_apqrinu_company_name',
	'_apqrinu_company_logo',
	'_apqrinu_application_deadline',
	'_apqrinu_job_status',
	'_apqrinu_salary_min',
	'_apqrinu_salary_max',
	'_apqrinu_salary_visibility',
	'_apqrinu_application_email',
	'_apqrinu_application_url',
	'_apqrinu_apply_shortcode',
);
foreach ( $apqrinu_meta_keys as $apqrinu_key ) {
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $apqrinu_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
}
