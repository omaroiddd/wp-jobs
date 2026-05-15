<?php
/**
 * Default single-job template.
 *
 * Layout:
 *   - Main column: title, posted date, content, "Apply now" button.
 *   - Sidebar: company logo + name, job summary, taxonomy chips, meta pills.
 *   - Apply form lives only in the modal (opened via "Apply now").
 *
 * Themes may override by placing apqrinu-job-board/single-apqrinu_job.php
 * (or single-apqrinu_job.php) in the theme.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	call_user_func(
		static function () {
			$apqrinu_post_id  = (int) get_the_ID();
			$apqrinu_company  = (string) get_post_meta( $apqrinu_post_id, '_apqrinu_company_name', true );
			$apqrinu_logo     = APQRINU_Helpers::company_logo( $apqrinu_post_id );
			$apqrinu_deadline = (string) get_post_meta( $apqrinu_post_id, '_apqrinu_application_deadline', true );
			$apqrinu_status   = (string) get_post_meta( $apqrinu_post_id, '_apqrinu_job_status', true );
			$apqrinu_summary  = (string) get_post_meta( $apqrinu_post_id, '_apqrinu_job_summary', true );
			$apqrinu_min      = get_post_meta( $apqrinu_post_id, '_apqrinu_salary_min', true );
			$apqrinu_max      = get_post_meta( $apqrinu_post_id, '_apqrinu_salary_max', true );
			$apqrinu_visible  = (bool) get_post_meta( $apqrinu_post_id, '_apqrinu_salary_visibility', true );
			$apqrinu_salary   = $apqrinu_visible ? APQRINU_Helpers::format_salary( $apqrinu_min, $apqrinu_max ) : '';
			$apqrinu_taxes    = APQRINU_Helpers::taxonomies();

			$apqrinu_status_labels = array(
				'open'    => __( 'Open', 'apqrinu-job-board' ),
				'closed'  => __( 'Closed', 'apqrinu-job-board' ),
				'on_hold' => __( 'On Hold', 'apqrinu-job-board' ),
				'filled'  => __( 'Filled', 'apqrinu-job-board' ),
			);
			?>
			<main id="primary" class="apqrinu-job-single">
				<div class="apqrinu-job-container">
					<div class="apqrinu-job-layout">

						<aside class="apqrinu-job-sidebar">
							<?php if ( $apqrinu_logo ) : ?>
								<div class="apqrinu-company-logo">
									<img src="<?php echo esc_url( $apqrinu_logo['url'] ); ?>" alt="<?php echo esc_attr( $apqrinu_logo['alt'] ); ?>" loading="lazy" />
								</div>
							<?php endif; ?>

							<?php if ( '' !== $apqrinu_company ) : ?>
								<div class="apqrinu-job-sidebar__company"><?php echo esc_html( $apqrinu_company ); ?></div>
							<?php endif; ?>

							<?php if ( '' !== $apqrinu_summary ) : ?>
								<div class="apqrinu-job-summary"><?php echo esc_html( $apqrinu_summary ); ?></div>
							<?php endif; ?>

							<div class="apqrinu-job-sidebar__chips">
								<?php
								foreach ( $apqrinu_taxes as $apqrinu_tax ) {
									$apqrinu_terms = get_the_terms( $apqrinu_post_id, $apqrinu_tax );
									if ( $apqrinu_terms && ! is_wp_error( $apqrinu_terms ) ) {
										foreach ( $apqrinu_terms as $apqrinu_term ) {
											echo '<span class="apqrinu-job-chip">' . esc_html( $apqrinu_term->name ) . '</span>';
										}
									}
								}
								?>
							</div>

							<?php if ( '' !== $apqrinu_deadline || '' !== $apqrinu_salary || ( '' !== $apqrinu_status && isset( $apqrinu_status_labels[ $apqrinu_status ] ) ) ) : ?>
								<div class="apqrinu-job-meta">
									<?php if ( '' !== $apqrinu_deadline ) : ?>
										<div class="apqrinu-job-meta-item">
											<span>
												<?php
												printf(
													/* translators: %s: deadline date */
													esc_html__( 'Apply by %s', 'apqrinu-job-board' ),
													esc_html( mysql2date( get_option( 'date_format' ), $apqrinu_deadline ) )
												);
												?>
											</span>
										</div>
									<?php endif; ?>

									<?php if ( '' !== $apqrinu_status && isset( $apqrinu_status_labels[ $apqrinu_status ] ) ) : ?>
										<div class="apqrinu-job-meta-item apqrinu-job-meta-item--status apqrinu-job-meta-item--<?php echo esc_attr( $apqrinu_status ); ?>">
											<span><?php echo esc_html( $apqrinu_status_labels[ $apqrinu_status ] ); ?></span>
										</div>
									<?php endif; ?>

									<?php if ( '' !== $apqrinu_salary ) : ?>
										<div class="apqrinu-job-meta-item apqrinu-job-meta-item--salary">
											<strong><?php esc_html_e( 'Salary', 'apqrinu-job-board' ); ?></strong>
											<span><?php echo esc_html( $apqrinu_salary ); ?></span>
										</div>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<button class="apqrinu-btn-apply apqrinu-btn-apply--block" data-apqrinu-open-apply type="button">
								<?php esc_html_e( 'Apply now', 'apqrinu-job-board' ); ?>
							</button>
						</aside>

						<div class="apqrinu-job-main">
							<header class="apqrinu-job-header">
								<h1 class="apqrinu-job-title"><?php the_title(); ?></h1>

								<div class="apqrinu-job-date">
									<span class="dashicons dashicons-calendar"></span>
									<div>
										<strong><?php esc_html_e( 'Posted:', 'apqrinu-job-board' ); ?></strong>
										<span><?php echo esc_html( get_the_date() ); ?></span>
									</div>
								</div>
							</header>

							<div class="apqrinu-job-content">
								<?php the_content(); ?>
							</div>

							<button class="apqrinu-btn-apply" data-apqrinu-open-apply type="button">
								<?php esc_html_e( 'Apply now', 'apqrinu-job-board' ); ?>
							</button>
						</div>

					</div>

					<?php
					if ( APQRINU_Helpers::get_setting( 'show_related', 1 ) ) {
						$apqrinu_related_file = APQRINU_Helpers::locate_template( 'parts/jobs-related.php' );
						if ( $apqrinu_related_file ) {
							$apqrinu_related_args = array( 'job_id' => $apqrinu_post_id );
							include $apqrinu_related_file;
							unset( $apqrinu_related_args );
						}
					}
					?>
				</div>
			</main>

			<div class="apqrinu-job-modal" id="apqrinu-job-apply-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="apqrinu-job-apply-modal-title">
				<div class="apqrinu-job-modal-overlay" data-apqrinu-close-apply></div>
				<div class="apqrinu-job-modal-content" role="document">
					<button class="apqrinu-job-modal-close" data-apqrinu-close-apply type="button" aria-label="<?php esc_attr_e( 'Close', 'apqrinu-job-board' ); ?>">&times;</button>
					<header class="apqrinu-job-modal-header">
						<h3 id="apqrinu-job-apply-modal-title" class="apqrinu-job-modal-title"><?php esc_html_e( 'Apply for this job', 'apqrinu-job-board' ); ?></h3>
						<p class="apqrinu-job-modal-subtitle"><?php echo esc_html( get_the_title( $apqrinu_post_id ) ); ?></p>
					</header>
					<div class="apqrinu-job-modal-body">
						<?php echo do_shortcode( '[apqrinu_apply_form]' ); ?>
					</div>
				</div>
			</div>
			<?php
		}
	);

endwhile;

get_footer();
