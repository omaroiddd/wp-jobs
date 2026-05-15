<?php
/**
 * Job card template part.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

$apqrinu_card_args = isset( $apqrinu_card_args ) && is_array( $apqrinu_card_args ) ? $apqrinu_card_args : array();

call_user_func(
	static function ( $apqrinu_args ) {
		$apqrinu_post_id = isset( $apqrinu_args['post_id'] ) ? (int) $apqrinu_args['post_id'] : (int) get_the_ID();
		if ( ! $apqrinu_post_id ) {
			return;
		}

		$apqrinu_summary  = (string) get_post_meta( $apqrinu_post_id, '_apqrinu_job_summary', true );
		$apqrinu_company  = (string) get_post_meta( $apqrinu_post_id, '_apqrinu_company_name', true );
		$apqrinu_logo     = APQRINU_Helpers::company_logo( $apqrinu_post_id );
		$apqrinu_taxes    = APQRINU_Helpers::taxonomies();
		?>
		<article class="apqrinu-job-card" id="apqrinu-job-card-<?php echo esc_attr( (string) $apqrinu_post_id ); ?>">
			<div class="apqrinu-job-card-top">
				<?php if ( $apqrinu_logo ) : ?>
					<a class="apqrinu-job-card-logo" href="<?php echo esc_url( get_permalink( $apqrinu_post_id ) ); ?>">
						<img src="<?php echo esc_url( $apqrinu_logo['url'] ); ?>" alt="<?php echo esc_attr( $apqrinu_logo['alt'] ); ?>" loading="lazy" />
					</a>
				<?php endif; ?>
				<div>
					<h2 class="apqrinu-job-card-title">
						<a href="<?php echo esc_url( get_permalink( $apqrinu_post_id ) ); ?>"><?php echo esc_html( get_the_title( $apqrinu_post_id ) ); ?></a>
					</h2>
					<div class="apqrinu-top-card-chips">
						<?php if ( '' !== $apqrinu_company ) : ?>
							<p class="apqrinu-job-card-company"><?php echo esc_html( $apqrinu_company ); ?></p>
						<?php endif; ?>
						<?php
						$apqrinu_loc_terms = get_the_terms( $apqrinu_post_id, 'apqrinu_job_location' );
						if ( $apqrinu_loc_terms && ! is_wp_error( $apqrinu_loc_terms ) ) :
							$apqrinu_first_loc = reset( $apqrinu_loc_terms );
							?>
							<span class="apqrinu-job-location"><?php echo esc_html( $apqrinu_first_loc->name ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php if ( '' !== $apqrinu_summary ) : ?>
				<p class="apqrinu-job-card-summary"><?php echo esc_html( $apqrinu_summary ); ?></p>
			<?php endif; ?>

			<div class="apqrinu-job-card-chips">
				<?php
				foreach ( $apqrinu_taxes as $apqrinu_tax ) {
					if ( 'apqrinu_job_location' === $apqrinu_tax ) {
						continue;
					}
					$apqrinu_terms = get_the_terms( $apqrinu_post_id, $apqrinu_tax );
					if ( $apqrinu_terms && ! is_wp_error( $apqrinu_terms ) ) {
						foreach ( $apqrinu_terms as $apqrinu_term ) {
							echo '<span class="apqrinu-chip apqrinu-chip--small">' . esc_html( $apqrinu_term->name ) . '</span>';
						}
					}
				}
				?>
			</div>

			<div class="apqrinu-job-card-footer">
				<span class="apqrinu-job-card-date"><?php echo esc_html( APQRINU_Helpers::time_ago( $apqrinu_post_id ) ); ?></span>
				<a class="apqrinu-job-card-link" href="<?php echo esc_url( get_permalink( $apqrinu_post_id ) ); ?>"><?php esc_html_e( 'View details', 'apqrinu-job-board' ); ?></a>
			</div>
		</article>
		<?php
	},
	$apqrinu_card_args
);
