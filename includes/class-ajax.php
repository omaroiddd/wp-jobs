<?php
/**
 * AJAX handlers (filter + related jobs).
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * AJAX handlers for filtering and related jobs pagination.
 */
class APQRINU_Ajax {

	const NONCE_ACTION = 'apqrinu_nonce';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_ajax_apqrinu_filter_jobs', array( __CLASS__, 'filter_jobs' ) );
		add_action( 'wp_ajax_nopriv_apqrinu_filter_jobs', array( __CLASS__, 'filter_jobs' ) );

		add_action( 'wp_ajax_apqrinu_related_page', array( __CLASS__, 'related_page' ) );
		add_action( 'wp_ajax_nopriv_apqrinu_related_page', array( __CLASS__, 'related_page' ) );
	}

	/**
	 * Filtered jobs AJAX handler.
	 */
	public static function filter_jobs() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$taxes    = APQRINU_Helpers::taxonomies();
		$per_page = (int) APQRINU_Helpers::get_setting( 'per_page', 5 );
		$paged    = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;

		$tax_query = self::build_tax_query( $taxes, $_POST );

		$args = array(
			'post_type'      => 'apqrinu_job',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( APQRINU_Helpers::get_setting( 'hide_expired', 0 ) ) {
			$args['meta_query'] = self::not_expired_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query = new WP_Query( $args );

		$base_url = '';
		if ( ! empty( $_POST['base_url'] ) ) {
			$base_url = esc_url_raw( wp_unslash( $_POST['base_url'] ) );
		}
		if ( '' === $base_url ) {
			$base_url = (string) get_post_type_archive_link( 'apqrinu_job' );
		}

		$active_filters = array();
		foreach ( $taxes as $tax ) {
			$value = APQRINU_Helpers::sanitize_filter_value( $_POST, $tax );
			if ( '' !== $value ) {
				$active_filters[ $tax ] = $value;
			}
		}

		ob_start();
		self::render_results( $query, $taxes, $paged, $base_url, $active_filters );
		$html = ob_get_clean();

		wp_reset_postdata();
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Related jobs AJAX handler.
	 */
	public static function related_page() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$job_id = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
		$paged  = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;

		if ( ! $job_id || 'apqrinu_job' !== get_post_type( $job_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid job.', 'apqrinu-job-board' ) ) );
		}

		$query = self::related_query( $job_id, $paged );

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				APQRINU_Helpers::render_job_card( get_the_ID() );
			}
		} else {
			echo '<div class="apqrinu-job-empty">' . esc_html__( 'No similar jobs found.', 'apqrinu-job-board' ) . '</div>';
		}
		$html = ob_get_clean();
		wp_reset_postdata();

		wp_send_json_success(
			array(
				'html'      => $html,
				'max_pages' => (int) $query->max_num_pages,
				'current'   => $paged,
				'has_prev'  => $paged > 1,
				'has_next'  => $paged < (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * Build a tax_query from a posted source array.
	 *
	 * @param array $taxes  Taxonomy slugs.
	 * @param array $source Source ($_POST or $_GET).
	 * @return array
	 */
	public static function build_tax_query( $taxes, $source ) {
		$tax_query = array();
		foreach ( $taxes as $tax ) {
			$value = APQRINU_Helpers::sanitize_filter_value( $source, $tax );
			if ( '' === $value ) {
				continue;
			}
			$tax_query[] = array(
				'taxonomy' => $tax,
				'field'    => 'slug',
				'terms'    => $value,
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		return $tax_query;
	}

	/**
	 * Build the meta_query for excluding expired jobs.
	 *
	 * @return array
	 */
	public static function not_expired_meta_query() {
		$today = gmdate( 'Y-m-d' );
		return array(
			'relation' => 'OR',
			array(
				'key'     => '_apqrinu_application_deadline',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_apqrinu_application_deadline',
				'value'   => '',
				'compare' => '=',
			),
			array(
				'key'     => '_apqrinu_application_deadline',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
	}

	/**
	 * Build the related-jobs query.
	 *
	 * @param int $job_id Job ID.
	 * @param int $paged  Page.
	 * @return WP_Query
	 */
	public static function related_query( $job_id, $paged ) {
		$per_page      = (int) APQRINU_Helpers::get_setting( 'related_per_page', 3 );
		$primary_terms = get_the_terms( $job_id, 'apqrinu_job_type' );
		$slugs         = array();
		if ( $primary_terms && ! is_wp_error( $primary_terms ) ) {
			foreach ( $primary_terms as $t ) {
				$slugs[] = $t->slug;
			}
		}

		$args = array(
			'post_type'           => 'apqrinu_job',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => $paged,
			// Single-id exclusion of the current job; perf impact negligible for related-jobs listing.
			'post__not_in'        => array( $job_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $slugs ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'apqrinu_job_type',
					'field'    => 'slug',
					'terms'    => $slugs,
				),
			);
		}

		return new WP_Query( $args );
	}

	/**
	 * Render the results block (cards + pagination or empty state).
	 *
	 * @param WP_Query $query          Query.
	 * @param array    $taxes          Taxonomies.
	 * @param int      $paged          Current page.
	 * @param string   $base_url       Base URL.
	 * @param array    $active_filters Active filters keyed by tax slug.
	 */
	private static function render_results( $query, $taxes, $paged, $base_url, $active_filters ) {
		if ( ! $query->have_posts() ) {
			echo '<div class="apqrinu-job-empty">' . esc_html__( 'No jobs match the current filters.', 'apqrinu-job-board' ) . '</div>';
			return;
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			APQRINU_Helpers::render_job_card( get_the_ID() );
		}

		$total = (int) $query->max_num_pages;
		if ( $total <= 1 ) {
			return;
		}

		$add_args                = $active_filters;
		$add_args['paged']       = '%#%';
		$pagination_base_url     = add_query_arg( $add_args, $base_url );
		echo '<div class="apqrinu-job-pagination">';
		echo wp_kses_post(
			(string) paginate_links(
				array(
					'base'      => $pagination_base_url,
					'format'    => '',
					'total'     => $total,
					'current'   => max( 1, $paged ),
					'type'      => 'plain',
					'prev_text' => '‹',
					'next_text' => '›',
				)
			)
		);
		echo '</div>';
		unset( $taxes );
	}
}
