<?php
/**
 * Related jobs template part.
 *
 * Visibility settings (Jobs → Settings → Related Jobs):
 *   - show_related (master switch)
 *   - hide_related_if_empty (suppress when no posts)
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

$apqrinu_related_args = isset( $apqrinu_related_args ) && is_array( $apqrinu_related_args ) ? $apqrinu_related_args : array();

call_user_func(
	static function ( $apqrinu_args ) {
		// Master switch.
		if ( ! APQRINU_Helpers::get_setting( 'show_related', 1 ) ) {
			return;
		}

		$apqrinu_job_id = isset( $apqrinu_args['job_id'] ) ? (int) $apqrinu_args['job_id'] : (int) get_the_ID();
		if ( ! $apqrinu_job_id ) {
			return;
		}
		$apqrinu_query     = APQRINU_Ajax::related_query( $apqrinu_job_id, 1 );
		$apqrinu_max_pages = (int) $apqrinu_query->max_num_pages;
		$apqrinu_has_posts = $apqrinu_query->have_posts();

		// "Hide if empty" — don't render the section at all.
		if ( ! $apqrinu_has_posts && APQRINU_Helpers::get_setting( 'hide_related_if_empty', 0 ) ) {
			return;
		}
		?>
		<section class="apqrinu-jobs-related"
			data-apqrinu-related
			data-job-id="<?php echo esc_attr( (string) $apqrinu_job_id ); ?>"
			data-current="1"
			data-max="<?php echo esc_attr( (string) $apqrinu_max_pages ); ?>">

			<header class="apqrinu-jobs-related__header">
				<h2 class="apqrinu-jobs-related__title"><?php esc_html_e( 'Similar jobs', 'apqrinu-job-board' ); ?></h2>
			</header>

			<div class="apqrinu-jobs-related__grid" id="apqrinu-jobs-related-results">
				<?php
				if ( $apqrinu_has_posts ) :
					while ( $apqrinu_query->have_posts() ) :
						$apqrinu_query->the_post();
						APQRINU_Helpers::render_job_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
				else :
					?>
					<div class="apqrinu-job-empty"><?php esc_html_e( 'No similar jobs found.', 'apqrinu-job-board' ); ?></div>
					<?php
				endif;
				?>
			</div>

			<?php if ( $apqrinu_max_pages > 1 ) : ?>
				<div class="apqrinu-jobs-related__pager">
					<button class="apqrinu-pager-btn" data-apqrinu-related-prev disabled><?php esc_html_e( 'Previous', 'apqrinu-job-board' ); ?></button>
					<span class="apqrinu-pager-info">
						<span data-apqrinu-related-page>1</span> / <?php echo esc_html( (string) $apqrinu_max_pages ); ?>
					</span>
					<button class="apqrinu-pager-btn" data-apqrinu-related-next><?php esc_html_e( 'Next', 'apqrinu-job-board' ); ?></button>
				</div>
			<?php endif; ?>
		</section>
		<?php
	},
	$apqrinu_related_args
);
