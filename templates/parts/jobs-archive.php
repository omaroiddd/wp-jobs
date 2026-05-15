<?php
/**
 * Jobs archive template part: filters + grid.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

$apqrinu_archive_args = isset( $apqrinu_archive_args ) && is_array( $apqrinu_archive_args ) ? $apqrinu_archive_args : array();

call_user_func(
	static function ( $apqrinu_args ) {
		$apqrinu_taxes = APQRINU_Helpers::taxonomies();

		$apqrinu_label_map = array(
			'apqrinu_job_type'         => __( 'Job Type', 'apqrinu-job-board' ),
			'apqrinu_work_mode'        => __( 'Work Mode', 'apqrinu-job-board' ),
			'apqrinu_experience_level' => __( 'Experience', 'apqrinu-job-board' ),
			'apqrinu_job_location'     => __( 'Location', 'apqrinu-job-board' ),
		);

		$apqrinu_active_filters = array();
		foreach ( $apqrinu_taxes as $apqrinu_tax ) {
			$apqrinu_value = APQRINU_Helpers::sanitize_filter_value( $_GET, $apqrinu_tax ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $apqrinu_value ) {
				$apqrinu_active_filters[ $apqrinu_tax ] = $apqrinu_value;
			}
		}

		$apqrinu_tax_query = APQRINU_Ajax::build_tax_query( $apqrinu_taxes, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$apqrinu_paged_get = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$apqrinu_paged     = (int) get_query_var( 'paged' );
		if ( ! $apqrinu_paged ) {
			$apqrinu_paged = $apqrinu_paged_get > 0 ? $apqrinu_paged_get : 1;
		}
		$apqrinu_paged = max( 1, $apqrinu_paged );

		$apqrinu_per_page = isset( $apqrinu_args['per_page'] ) && $apqrinu_args['per_page']
			? (int) $apqrinu_args['per_page']
			: (int) APQRINU_Helpers::get_setting( 'per_page', 5 );

		$apqrinu_query_args = array(
			'post_type'      => 'apqrinu_job',
			'post_status'    => 'publish',
			'posts_per_page' => $apqrinu_per_page,
			'paged'          => $apqrinu_paged,
		);
		if ( ! empty( $apqrinu_tax_query ) ) {
			$apqrinu_query_args['tax_query'] = $apqrinu_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( APQRINU_Helpers::get_setting( 'hide_expired', 0 ) ) {
			$apqrinu_query_args['meta_query'] = APQRINU_Ajax::not_expired_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$apqrinu_query = new WP_Query( $apqrinu_query_args );

		$apqrinu_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$apqrinu_base_url    = strtok( home_url( $apqrinu_request_uri ), '#' );

		$apqrinu_show_filters = ! isset( $apqrinu_args['show_filters'] ) || $apqrinu_args['show_filters'];
		?>
		<section class="apqrinu-job-archive" aria-label="<?php esc_attr_e( 'Jobs archive', 'apqrinu-job-board' ); ?>">
			<div class="apqrinu-job-container">

				<?php if ( $apqrinu_show_filters ) : ?>
					<section class="apqrinu-job-filters apqrinu-job-filters--top">
						<div class="apqrinu-job-filters-bar">
							<div class="apqrinu-job-filters-tabs">
								<?php foreach ( $apqrinu_taxes as $apqrinu_tax ) : ?>
									<?php
									$apqrinu_terms = get_terms(
										array(
											'taxonomy'   => $apqrinu_tax,
											'hide_empty' => true,
										)
									);
									if ( is_wp_error( $apqrinu_terms ) || empty( $apqrinu_terms ) ) {
										continue;
									}
									$apqrinu_active = isset( $apqrinu_active_filters[ $apqrinu_tax ] ) ? $apqrinu_active_filters[ $apqrinu_tax ] : '';
									$apqrinu_label  = isset( $apqrinu_label_map[ $apqrinu_tax ] ) ? $apqrinu_label_map[ $apqrinu_tax ] : ucwords( str_replace( '_', ' ', $apqrinu_tax ) );
									?>
									<div class="apqrinu-filter-select-wrapper">
										<select class="apqrinu-filter-select" data-tax="<?php echo esc_attr( $apqrinu_tax ); ?>" name="<?php echo esc_attr( $apqrinu_tax ); ?>">
											<option value=""><?php echo esc_html( $apqrinu_label ); ?></option>
											<?php foreach ( $apqrinu_terms as $apqrinu_term ) : ?>
												<option value="<?php echo esc_attr( $apqrinu_term->slug ); ?>" <?php selected( $apqrinu_active, $apqrinu_term->slug ); ?>>
													<?php echo esc_html( $apqrinu_term->name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>
								<?php endforeach; ?>
								<a class="apqrinu-clear-filters" href="<?php echo esc_url( $apqrinu_base_url ); ?>"><?php esc_html_e( 'Clear filters', 'apqrinu-job-board' ); ?></a>
							</div>
						</div>
					</section>
				<?php endif; ?>

				<section class="apqrinu-job-list">
					<div id="apqrinu-jobs-results">
						<?php
						if ( $apqrinu_query->have_posts() ) :
							while ( $apqrinu_query->have_posts() ) :
								$apqrinu_query->the_post();
								APQRINU_Helpers::render_job_card( get_the_ID() );
							endwhile;

							$apqrinu_total = (int) $apqrinu_query->max_num_pages;
							if ( $apqrinu_total > 1 ) :
								$apqrinu_add_args          = $apqrinu_active_filters;
								$apqrinu_add_args['paged'] = '%#%';
								$apqrinu_pagination_base   = add_query_arg( $apqrinu_add_args, $apqrinu_base_url );

								echo '<div class="apqrinu-job-pagination">';
								echo wp_kses_post(
									(string) paginate_links(
										array(
											'base'      => $apqrinu_pagination_base,
											'format'    => '',
											'total'     => $apqrinu_total,
											'current'   => max( 1, $apqrinu_paged ),
											'type'      => 'plain',
											'prev_text' => '‹',
											'next_text' => '›',
										)
									)
								);
								echo '</div>';
							endif;
						else :
							echo '<div class="apqrinu-job-empty">' . esc_html__( 'No jobs match the current filters.', 'apqrinu-job-board' ) . '</div>';
						endif;
						wp_reset_postdata();
						?>
					</div>
				</section>
			</div>
		</section>
		<?php
	},
	$apqrinu_archive_args
);
