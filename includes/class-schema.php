<?php
/**
 * JobPosting JSON-LD structured data.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outputs schema.org JobPosting JSON-LD on single job pages.
 */
class APQRINU_Schema {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output' ), 99 );
	}

	/**
	 * Output JSON-LD.
	 */
	public static function output() {
		if ( ! is_singular( 'apqrinu_job' ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$company  = (string) get_post_meta( $post_id, '_apqrinu_company_name', true );
		$min      = get_post_meta( $post_id, '_apqrinu_salary_min', true );
		$max      = get_post_meta( $post_id, '_apqrinu_salary_max', true );
		$visible  = (bool) get_post_meta( $post_id, '_apqrinu_salary_visibility', true );
		$deadline = (string) get_post_meta( $post_id, '_apqrinu_application_deadline', true );

		$type_terms = get_the_terms( $post_id, 'apqrinu_job_type' );
		$loc_terms  = get_the_terms( $post_id, 'apqrinu_job_location' );

		$employment_type = '';
		if ( $type_terms && ! is_wp_error( $type_terms ) ) {
			$first           = reset( $type_terms );
			$employment_type = $first->name;
		}

		$location_name = '';
		if ( $loc_terms && ! is_wp_error( $loc_terms ) ) {
			$first         = reset( $loc_terms );
			$location_name = $first->name;
		}

		$data = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'JobPosting',
			'title'       => wp_strip_all_tags( get_the_title( $post_id ) ),
			'description' => wp_strip_all_tags( get_the_content( null, false, $post_id ) ),
			'datePosted'  => get_the_date( 'c', $post_id ),
			'identifier'  => array(
				'@type' => 'PropertyValue',
				'name'  => $company ? $company : (string) get_bloginfo( 'name' ),
				'value' => (string) $post_id,
			),
		);

		if ( '' !== $employment_type ) {
			$data['employmentType'] = $employment_type;
		}

		if ( '' !== $deadline ) {
			$data['validThrough'] = $deadline;
		}

		if ( '' !== $company ) {
			$data['hiringOrganization'] = array(
				'@type' => 'Organization',
				'name'  => $company,
				'sameAs' => home_url( '/' ),
			);
		}

		if ( '' !== $location_name ) {
			$data['jobLocation'] = array(
				'@type'   => 'Place',
				'address' => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => $location_name,
				),
			);
		}

		if ( $visible && ( is_numeric( $min ) || is_numeric( $max ) ) ) {
			$currency             = (string) APQRINU_Helpers::get_setting( 'currency', 'USD' );
			$data['baseSalary']   = array(
				'@type'    => 'MonetaryAmount',
				'currency' => $currency,
				'value'    => array(
					'@type'    => 'QuantitativeValue',
					'minValue' => is_numeric( $min ) ? (float) $min : null,
					'maxValue' => is_numeric( $max ) ? (float) $max : null,
					'unitText' => 'MONTH',
				),
			);
		}

		/**
		 * Filter the JobPosting schema array before output.
		 *
		 * @param array $data    Schema data.
		 * @param int   $post_id Job post ID.
		 */
		$data = apply_filters( 'apqrinu_jobposting_schema', $data, $post_id );

		/*
		 * Escape every HTML-special character inside the JSON so that no
		 * filtered/stored value can break out of the <script> context
		 * (e.g. a literal "</script>" in the post title or a filter callback).
		 *
		 * JSON_HEX_TAG / JSON_HEX_AMP / JSON_HEX_APOS / JSON_HEX_QUOT convert
		 * <, >, &, ', " into their \uXXXX equivalents — keeping the JSON
		 * valid and the surrounding <script> tag safe.
		 */
		$apqrinu_json = wp_json_encode(
			$data,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);

		if ( false === $apqrinu_json ) {
			return;
		}

		echo "\n" . '<script type="application/ld+json">' . $apqrinu_json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of wp_json_encode() with JSON_HEX_TAG/AMP/APOS/QUOT contains only safe ASCII characters; <, >, &, ', " are all \uXXXX-escaped.
	}
}
