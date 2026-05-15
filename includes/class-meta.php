<?php
/**
 * Job meta fields and metabox (replaces ACF dependency).
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers post meta and a single metabox for jobs.
 */
class APQRINU_Meta {

	const NONCE_ACTION = 'apqrinu_save_meta';
	const NONCE_NAME   = 'apqrinu_meta_nonce';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metabox' ) );
		add_action( 'save_post_apqrinu_job', array( __CLASS__, 'save_meta' ), 10, 2 );
	}

	/**
	 * The list of meta keys with their args.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'_apqrinu_job_summary'         => array(
				'type'    => 'string',
				'label'   => __( 'Job Summary', 'apqrinu-job-board' ),
				'control' => 'textarea',
			),
			'_apqrinu_company_name'        => array(
				'type'    => 'string',
				'label'   => __( 'Company Name', 'apqrinu-job-board' ),
				'control' => 'text',
			),
			'_apqrinu_company_logo'        => array(
				'type'    => 'integer',
				'label'   => __( 'Company Logo (attachment ID)', 'apqrinu-job-board' ),
				'control' => 'media',
			),
			'_apqrinu_application_deadline' => array(
				'type'    => 'string',
				'label'   => __( 'Application Deadline', 'apqrinu-job-board' ),
				'control' => 'date',
			),
			'_apqrinu_job_status'          => array(
				'type'    => 'string',
				'label'   => __( 'Job Status', 'apqrinu-job-board' ),
				'control' => 'select',
				'choices' => array(
					''         => __( '— Select —', 'apqrinu-job-board' ),
					'open'     => __( 'Open', 'apqrinu-job-board' ),
					'closed'   => __( 'Closed', 'apqrinu-job-board' ),
					'on_hold'  => __( 'On Hold', 'apqrinu-job-board' ),
					'filled'   => __( 'Filled', 'apqrinu-job-board' ),
				),
			),
			'_apqrinu_salary_min'          => array(
				'type'    => 'number',
				'label'   => __( 'Salary Min', 'apqrinu-job-board' ),
				'control' => 'number',
			),
			'_apqrinu_salary_max'          => array(
				'type'    => 'number',
				'label'   => __( 'Salary Max', 'apqrinu-job-board' ),
				'control' => 'number',
			),
			'_apqrinu_salary_visibility'   => array(
				'type'    => 'boolean',
				'label'   => __( 'Show Salary on Front-end', 'apqrinu-job-board' ),
				'control' => 'checkbox',
			),
			'_apqrinu_application_email'   => array(
				'type'    => 'string',
				'label'   => __( 'Application Email (optional)', 'apqrinu-job-board' ),
				'control' => 'email',
			),
			'_apqrinu_application_url'     => array(
				'type'    => 'string',
				'label'   => __( 'External Application URL (optional)', 'apqrinu-job-board' ),
				'control' => 'url',
			),
			'_apqrinu_apply_shortcode'     => array(
				'type'    => 'string',
				'label'   => __( 'Apply Form Shortcode (optional)', 'apqrinu-job-board' ),
				'control' => 'shortcode',
				'description' => __( 'Paste any form-plugin shortcode (e.g. Fluent Forms, WPForms, Contact Form 7, Gravity Forms) to use it as the application form for this job. Leave empty to use the global default or the built-in form.', 'apqrinu-job-board' ),
			),
		);
	}

	/**
	 * Register all meta with the REST API.
	 */
	public static function register_meta() {
		foreach ( self::fields() as $key => $args ) {
			register_post_meta(
				'apqrinu_job',
				$key,
				array(
					'type'              => $args['type'],
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
					'sanitize_callback' => self::sanitizer_for( $args ),
				)
			);
		}
	}

	/**
	 * Build a sanitizer callback for a field definition.
	 *
	 * @param array $args Field args.
	 * @return callable
	 */
	private static function sanitizer_for( $args ) {
		switch ( $args['control'] ) {
			case 'number':
				return static function ( $v ) {
					return is_numeric( $v ) ? (float) $v : 0;
				};
			case 'media':
				return 'absint';
			case 'checkbox':
				return static function ( $v ) {
					return ! empty( $v ) ? 1 : 0;
				};
			case 'textarea':
			case 'shortcode':
				return 'sanitize_textarea_field';
			case 'email':
				return 'sanitize_email';
			case 'url':
				return 'esc_url_raw';
			case 'date':
				return static function ( $v ) {
					$v = sanitize_text_field( (string) $v );
					if ( '' === $v ) {
						return '';
					}
					$ts = strtotime( $v );
					return $ts ? gmdate( 'Y-m-d', $ts ) : '';
				};
			case 'select':
				$choices = isset( $args['choices'] ) ? array_keys( $args['choices'] ) : array();
				return static function ( $v ) use ( $choices ) {
					$v = sanitize_key( $v );
					return in_array( $v, $choices, true ) ? $v : '';
				};
			default:
				return 'sanitize_text_field';
		}
	}

	/**
	 * Add the job details metabox.
	 */
	public static function add_metabox() {
		add_meta_box(
			'apqrinu_job_details',
			__( 'Job Details', 'apqrinu-job-board' ),
			array( __CLASS__, 'render_metabox' ),
			'apqrinu_job',
			'normal',
			'high'
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_metabox( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$fields = self::fields();
		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $key => $args ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<tr>';
			echo '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $args['label'] ) . '</label></th>';
			echo '<td>';
			self::render_control( $key, $args, $value );
			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render a single control.
	 *
	 * @param string $key   Meta key.
	 * @param array  $args  Field args.
	 * @param mixed  $value Current value.
	 */
	private static function render_control( $key, $args, $value ) {
		$id   = esc_attr( $key );
		$name = esc_attr( $key );

		switch ( $args['control'] ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="4" class="large-text">%2$s</textarea>',
					esc_attr( $id ),
					esc_textarea( (string) $value )
				);
				break;
			case 'shortcode':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="2" class="large-text code" placeholder="%2$s">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( '[fluentform id="3"]' ),
					esc_textarea( (string) $value )
				);
				break;
			case 'number':
				printf(
					'<input type="number" step="any" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( (string) $value )
				);
				break;
			case 'email':
				printf(
					'<input type="email" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( (string) $value )
				);
				break;
			case 'url':
				printf(
					'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( (string) $value )
				);
				break;
			case 'date':
				printf(
					'<input type="date" id="%1$s" name="%1$s" value="%2$s" />',
					esc_attr( $id ),
					esc_attr( (string) $value )
				);
				break;
			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s /> %3$s</label>',
					esc_attr( $id ),
					checked( ! empty( $value ), true, false ),
					esc_html__( 'Yes', 'apqrinu-job-board' )
				);
				break;
			case 'select':
				$choices = isset( $args['choices'] ) ? $args['choices'] : array();
				printf( '<select id="%1$s" name="%1$s">', esc_attr( $id ) );
				foreach ( $choices as $val => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $val ),
						selected( (string) $value, (string) $val, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;
			case 'media':
				$attachment_id = (int) $value;
				$preview       = '';
				if ( $attachment_id ) {
					$src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
					if ( $src ) {
						$preview = '<img src="' . esc_url( $src[0] ) . '" alt="" style="max-width:80px;height:auto;display:block;margin-bottom:6px;" />';
					}
				}
				echo '<div class="apqrinu-media-control">';
				echo wp_kses_post( $preview );
				printf(
					'<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text apqrinu-media-id" /> ',
					esc_attr( $id ),
					esc_attr( (string) $attachment_id )
				);
				echo '<button type="button" class="button apqrinu-media-pick">' . esc_html__( 'Choose Image', 'apqrinu-job-board' ) . '</button> ';
				echo '<button type="button" class="button apqrinu-media-clear">' . esc_html__( 'Clear', 'apqrinu-job-board' ) . '</button>';
				echo '<p class="description">' . esc_html__( 'Or use the Featured Image as fallback.', 'apqrinu-job-board' ) . '</p>';
				echo '</div>';
				break;
			case 'text':
			default:
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( (string) $value )
				);
				break;
		}

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}

		unset( $name );
	}

	/**
	 * Save metabox values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( 'apqrinu_job' !== $post->post_type ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		foreach ( self::fields() as $key => $args ) {
			if ( 'checkbox' === $args['control'] ) {
				$value = ! empty( $_POST[ $key ] ) ? 1 : 0;
				update_post_meta( $post_id, $key, $value );
				continue;
			}

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			// $raw is sanitized below via the field-specific callback returned by self::sanitizer_for().
			$raw       = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$sanitizer = self::sanitizer_for( $args );
			$value     = call_user_func( $sanitizer, $raw );
			update_post_meta( $post_id, $key, $value );
		}
	}
}
