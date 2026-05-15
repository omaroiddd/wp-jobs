<?php
/**
 * Apply form template part.
 *
 * Renders, in priority order:
 *   1. External Application URL (Apply now button → URL)
 *   2. Per-job custom form shortcode (e.g. Fluent Forms / WPForms / CF7 / Gravity Forms)
 *   3. Global default Apply Form Shortcode setting
 *   4. Built-in apply form (AJAX)
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

$apqrinu_apply_args = isset( $apqrinu_apply_args ) && is_array( $apqrinu_apply_args ) ? $apqrinu_apply_args : array();

call_user_func(
	static function ( $apqrinu_args ) {
		$apqrinu_job_id = isset( $apqrinu_args['job_id'] ) ? (int) $apqrinu_args['job_id'] : (int) get_the_ID();
		if ( ! $apqrinu_job_id || 'apqrinu_job' !== get_post_type( $apqrinu_job_id ) ) {
			return;
		}
		if ( ! APQRINU_Helpers::get_setting( 'enable_apply_form', 1 ) ) {
			return;
		}

		$apqrinu_external_url     = (string) get_post_meta( $apqrinu_job_id, '_apqrinu_application_url', true );
		$apqrinu_job_shortcode    = (string) get_post_meta( $apqrinu_job_id, '_apqrinu_apply_shortcode', true );
		$apqrinu_global_shortcode = (string) APQRINU_Helpers::get_setting( 'apply_shortcode', '' );
		$apqrinu_shortcode        = '' !== trim( $apqrinu_job_shortcode ) ? $apqrinu_job_shortcode : $apqrinu_global_shortcode;
		?>
		<div class="apqrinu-apply" data-apqrinu-apply>
			<?php if ( '' !== $apqrinu_external_url ) : ?>
				<a class="apqrinu-btn-apply" href="<?php echo esc_url( $apqrinu_external_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Apply now', 'apqrinu-job-board' ); ?>
				</a>

			<?php elseif ( '' !== trim( (string) $apqrinu_shortcode ) ) : ?>
				<div class="apqrinu-apply-shortcode">
					<?php
					/*
					 * Run any registered form-plugin shortcode (Fluent Forms,
					 * WPForms, CF7, Gravity Forms, Forminator, etc.).
					 *
					 * The stored value is already sanitized on save with
					 * sanitize_textarea_field() (see APQRINU_Meta::sanitizer_for()
					 * and APQRINU_Settings::sanitize()), which strips all HTML
					 * tags — so the value can only contain shortcode syntax
					 * and plain text. Passing it through wp_kses_post() here
					 * additionally normalises entities, which corrupts
					 * shortcode attributes (e.g. id="3" → id=&quot;3&quot;)
					 * for some form plugins (Fluent Forms in particular) and
					 * makes them render empty.
					 *
					 * do_shortcode() itself is the canonical safe-output
					 * function for shortcode content and is recognised as
					 * an escaping function by WordPress Coding Standards.
					 */
					echo do_shortcode( $apqrinu_shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $apqrinu_shortcode is sanitized on save by sanitize_textarea_field (HTML stripped); do_shortcode() returns intentionally HTML-formatted form markup from a registered plugin.
					?>
				</div>

			<?php else : ?>
				<form class="apqrinu-apply-form" data-apqrinu-apply-form enctype="multipart/form-data" novalidate>
					<?php wp_nonce_field( 'apqrinu_apply', 'apqrinu_apply_nonce' ); ?>
					<input type="hidden" name="action" value="apqrinu_apply_submit" />
					<input type="hidden" name="job_id" value="<?php echo esc_attr( (string) $apqrinu_job_id ); ?>" />

					<p class="apqrinu-field">
						<label for="apqrinu-name-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>">
							<?php esc_html_e( 'Full name', 'apqrinu-job-board' ); ?>
							<span class="apqrinu-req" aria-hidden="true">*</span>
						</label>
						<input type="text" id="apqrinu-name-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>" name="applicant_name" required />
					</p>

					<p class="apqrinu-field">
						<label for="apqrinu-email-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>">
							<?php esc_html_e( 'Email', 'apqrinu-job-board' ); ?>
							<span class="apqrinu-req" aria-hidden="true">*</span>
						</label>
						<input type="email" id="apqrinu-email-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>" name="applicant_email" required />
					</p>

					<p class="apqrinu-field">
						<label for="apqrinu-phone-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>">
							<?php esc_html_e( 'Phone', 'apqrinu-job-board' ); ?>
						</label>
						<input type="tel" id="apqrinu-phone-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>" name="applicant_phone" />
					</p>

					<p class="apqrinu-field">
						<label for="apqrinu-message-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>">
							<?php esc_html_e( 'Cover letter', 'apqrinu-job-board' ); ?>
						</label>
						<textarea id="apqrinu-message-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>" name="applicant_message" rows="5"></textarea>
					</p>

					<p class="apqrinu-field">
						<label for="apqrinu-resume-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>">
							<?php esc_html_e( 'Resume (PDF/DOC/DOCX)', 'apqrinu-job-board' ); ?>
						</label>
						<input type="file" id="apqrinu-resume-<?php echo esc_attr( (string) $apqrinu_job_id ); ?>" name="applicant_resume" accept=".pdf,.doc,.docx" />
					</p>

					<p class="apqrinu-field apqrinu-honeypot" aria-hidden="true">
						<label>
							<?php esc_html_e( 'Leave this field empty', 'apqrinu-job-board' ); ?>
							<input type="text" name="apqrinu_hp" tabindex="-1" autocomplete="off" />
						</label>
					</p>

					<div class="apqrinu-actions">
						<button type="submit" class="apqrinu-btn-apply"><?php esc_html_e( 'Apply now', 'apqrinu-job-board' ); ?></button>
					</div>

					<div class="apqrinu-form-status" role="status" aria-live="polite"></div>
				</form>
			<?php endif; ?>
		</div>
		<?php
	},
	$apqrinu_apply_args
);
