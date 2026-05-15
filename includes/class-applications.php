<?php
/**
 * Built-in apply form: AJAX submission, application storage, admin email.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles application submissions for the built-in apply form.
 */
class APQRINU_Applications {

	const NONCE_ACTION = 'apqrinu_apply';
	const META_JOB     = '_apqrinu_app_job_id';
	const META_NAME    = '_apqrinu_app_name';
	const META_EMAIL   = '_apqrinu_app_email';
	const META_PHONE   = '_apqrinu_app_phone';
	const META_MESSAGE = '_apqrinu_app_message';
	const META_RESUME  = '_apqrinu_app_resume_id';
	const META_IP      = '_apqrinu_app_ip';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_ajax_apqrinu_apply_submit', array( __CLASS__, 'submit' ) );
		add_action( 'wp_ajax_nopriv_apqrinu_apply_submit', array( __CLASS__, 'submit' ) );

		add_action( 'add_meta_boxes_apqrinu_application', array( __CLASS__, 'add_metabox' ) );
		add_filter( 'manage_apqrinu_application_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_apqrinu_application_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
	}

	/**
	 * Handle an apply form submission.
	 */
	public static function submit() {
		// The form ships its own nonce field named "apqrinu_apply_nonce"
		// (see templates/parts/apply-form.php). Use that — the generic
		// ApqrinuData.nonce is for the listing/related AJAX endpoints.
		check_ajax_referer( self::NONCE_ACTION, 'apqrinu_apply_nonce' );

		if ( ! APQRINU_Helpers::get_setting( 'enable_apply_form', 1 ) ) {
			wp_send_json_error( array( 'message' => __( 'Applications are disabled.', 'apqrinu-job-board' ) ) );
		}

		$job_id = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
		if ( ! $job_id || 'apqrinu_job' !== get_post_type( $job_id ) || 'publish' !== get_post_status( $job_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid job.', 'apqrinu-job-board' ) ) );
		}

		$name    = isset( $_POST['applicant_name'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_name'] ) ) : '';
		$email   = isset( $_POST['applicant_email'] ) ? sanitize_email( wp_unslash( $_POST['applicant_email'] ) ) : '';
		$phone   = isset( $_POST['applicant_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_phone'] ) ) : '';
		$message = isset( $_POST['applicant_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['applicant_message'] ) ) : '';

		if ( '' === $name || '' === $email || ! is_email( $email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please provide a valid name and email.', 'apqrinu-job-board' ),
				)
			);
		}

		// Optional resume upload.
		$resume_id = 0;
		if ( ! empty( $_FILES['applicant_resume']['name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$resume_id = self::handle_resume_upload();
			if ( is_wp_error( $resume_id ) ) {
				wp_send_json_error( array( 'message' => $resume_id->get_error_message() ) );
			}
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'apqrinu_application',
				'post_status' => 'private',
				'post_title'  => sprintf(
					/* translators: 1: applicant name, 2: job title */
					__( '%1$s — %2$s', 'apqrinu-job-board' ),
					$name,
					get_the_title( $job_id )
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not save application.', 'apqrinu-job-board' ) ) );
		}

		update_post_meta( $post_id, self::META_JOB, $job_id );
		update_post_meta( $post_id, self::META_NAME, $name );
		update_post_meta( $post_id, self::META_EMAIL, $email );
		update_post_meta( $post_id, self::META_PHONE, $phone );
		update_post_meta( $post_id, self::META_MESSAGE, $message );
		if ( $resume_id ) {
			update_post_meta( $post_id, self::META_RESUME, $resume_id );
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( $ip ) {
			update_post_meta( $post_id, self::META_IP, $ip );
		}

		self::send_admin_notification( $post_id, $job_id, $name, $email, $phone, $message, $resume_id );

		/**
		 * Fires after a job application is stored.
		 *
		 * @param int $post_id Application post ID.
		 * @param int $job_id  Job ID applied to.
		 */
		do_action( 'apqrinu_application_submitted', $post_id, $job_id );

		wp_send_json_success(
			array(
				'message' => __( 'Application sent. Thank you!', 'apqrinu-job-board' ),
			)
		);
	}

	/**
	 * Handle the resume upload.
	 *
	 * @return int|WP_Error Attachment ID or error.
	 */
	private static function handle_resume_upload() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$allowed = apply_filters(
			'apqrinu_resume_mime_types',
			array(
				'pdf'  => 'application/pdf',
				'doc'  => 'application/msword',
				'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			)
		);

		$overrides = array(
			'test_form' => false,
			'mimes'     => $allowed,
		);

		$id = media_handle_upload( 'applicant_resume', 0, array(), $overrides );

		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return (int) $id;
	}

	/**
	 * Email site admin (or the per-job address) about a new application.
	 *
	 * @param int    $post_id   Application ID.
	 * @param int    $job_id    Job ID.
	 * @param string $name      Applicant name.
	 * @param string $email     Applicant email.
	 * @param string $phone     Applicant phone.
	 * @param string $message   Applicant message.
	 * @param int    $resume_id Resume attachment ID.
	 */
	private static function send_admin_notification( $post_id, $job_id, $name, $email, $phone, $message, $resume_id ) {
		$to = (string) get_post_meta( $job_id, '_apqrinu_application_email', true );
		if ( ! is_email( $to ) ) {
			$to = (string) APQRINU_Helpers::get_setting( 'apply_email', '' );
		}
		if ( ! is_email( $to ) ) {
			$to = get_option( 'admin_email' );
		}

		/* translators: %s: job title */
		$subject = sprintf( __( 'New application: %s', 'apqrinu-job-board' ), get_the_title( $job_id ) );

		$lines   = array();
		/* translators: %s: job title */
		$lines[] = sprintf( __( 'Job: %s', 'apqrinu-job-board' ), get_the_title( $job_id ) );
		/* translators: %s: applicant name */
		$lines[] = sprintf( __( 'Name: %s', 'apqrinu-job-board' ), $name );
		/* translators: %s: applicant email */
		$lines[] = sprintf( __( 'Email: %s', 'apqrinu-job-board' ), $email );
		if ( '' !== $phone ) {
			/* translators: %s: applicant phone */
			$lines[] = sprintf( __( 'Phone: %s', 'apqrinu-job-board' ), $phone );
		}
		if ( '' !== $message ) {
			$lines[] = '';
			$lines[] = __( 'Message:', 'apqrinu-job-board' );
			$lines[] = $message;
		}

		$attachments = array();
		if ( $resume_id ) {
			$path = get_attached_file( $resume_id );
			if ( $path && file_exists( $path ) ) {
				$attachments[] = $path;
			}
		}

		$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

		wp_mail( $to, $subject, implode( "\r\n", $lines ), $headers, $attachments );
		unset( $post_id );
	}

	/**
	 * Add a metabox to the application screen.
	 */
	public static function add_metabox() {
		add_meta_box(
			'apqrinu_application_details',
			__( 'Application Details', 'apqrinu-job-board' ),
			array( __CLASS__, 'render_metabox' ),
			'apqrinu_application',
			'normal',
			'high'
		);
	}

	/**
	 * Render the application metabox.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_metabox( $post ) {
		$job_id    = (int) get_post_meta( $post->ID, self::META_JOB, true );
		$name      = (string) get_post_meta( $post->ID, self::META_NAME, true );
		$email     = (string) get_post_meta( $post->ID, self::META_EMAIL, true );
		$phone     = (string) get_post_meta( $post->ID, self::META_PHONE, true );
		$message   = (string) get_post_meta( $post->ID, self::META_MESSAGE, true );
		$resume_id = (int) get_post_meta( $post->ID, self::META_RESUME, true );

		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th>' . esc_html__( 'Job', 'apqrinu-job-board' ) . '</th><td>';
		if ( $job_id ) {
			$link = get_edit_post_link( $job_id );
			printf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $link ? $link : '#' ),
				esc_html( get_the_title( $job_id ) )
			);
		}
		echo '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Name', 'apqrinu-job-board' ) . '</th><td>' . esc_html( $name ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Email', 'apqrinu-job-board' ) . '</th><td><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td></tr>';
		echo '<tr><th>' . esc_html__( 'Phone', 'apqrinu-job-board' ) . '</th><td>' . esc_html( $phone ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Message', 'apqrinu-job-board' ) . '</th><td>' . nl2br( esc_html( $message ) ) . '</td></tr>';
		if ( $resume_id ) {
			$url = wp_get_attachment_url( $resume_id );
			echo '<tr><th>' . esc_html__( 'Resume', 'apqrinu-job-board' ) . '</th><td>';
			if ( $url ) {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer noopener">' . esc_html__( 'Download', 'apqrinu-job-board' ) . '</a>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Add custom columns to the applications list table.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['apqrinu_job']   = __( 'Job', 'apqrinu-job-board' );
				$new['apqrinu_email'] = __( 'Email', 'apqrinu-job-board' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column data.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'apqrinu_job' === $column ) {
			$job_id = (int) get_post_meta( $post_id, self::META_JOB, true );
			if ( $job_id ) {
				echo esc_html( get_the_title( $job_id ) );
			}
		} elseif ( 'apqrinu_email' === $column ) {
			$email = (string) get_post_meta( $post_id, self::META_EMAIL, true );
			if ( $email ) {
				echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			}
		}
	}
}
