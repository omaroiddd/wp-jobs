<?php
/**
 * Settings page.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin settings page using the Settings API.
 */
class APQRINU_Settings {

	const PAGE_SLUG = 'apqrinu-settings';
	const GROUP     = 'apqrinu_settings_group';

	/**
	 * The actual admin page hook returned by add_submenu_page().
	 *
	 * @var string
	 */
	private static $page_hook = '';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . APQRINU_BASENAME, array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Add the settings submenu under Jobs.
	 */
	public static function menu() {
		self::$page_hook = (string) add_submenu_page(
			'edit.php?post_type=apqrinu_job',
			__( 'Jobs Board Settings', 'apqrinu-job-board' ),
			__( 'Settings', 'apqrinu-job-board' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Get the captured admin page hook (e.g. apqrinu_job_page_apqrinu-settings).
	 *
	 * @return string
	 */
	public static function page_hook() {
		return self::$page_hook;
	}

	/**
	 * Register settings + fields.
	 */
	public static function register_settings() {
		register_setting(
			self::GROUP,
			APQRINU_Helpers::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => APQRINU_Helpers::default_settings(),
			)
		);

		add_settings_section(
			'apqrinu_general',
			__( 'General', 'apqrinu-job-board' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'per_page',
			__( 'Jobs per page', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_number' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array( 'key' => 'per_page' )
		);

		add_settings_field(
			'related_per_page',
			__( 'Related jobs per page', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_number' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array( 'key' => 'related_per_page' )
		);

		add_settings_field(
			'currency',
			__( 'Currency code', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_text' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array(
				'key'         => 'currency',
				'description' => __( 'ISO 4217 code, e.g. USD, EUR, SAR.', 'apqrinu-job-board' ),
			)
		);

		add_settings_field(
			'currency_symbol',
			__( 'Currency symbol', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_text' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array( 'key' => 'currency_symbol' )
		);

		add_settings_field(
			'apply_email',
			__( 'Default applications email', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_email' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array(
				'key'         => 'apply_email',
				'description' => __( 'Used when a job has no Application Email of its own. Falls back to site admin.', 'apqrinu-job-board' ),
			)
		);

		add_settings_field(
			'enable_apply_form',
			__( 'Enable built-in apply form', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_checkbox' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array( 'key' => 'enable_apply_form' )
		);

		add_settings_field(
			'hide_expired',
			__( 'Hide jobs past their deadline', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_checkbox' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array( 'key' => 'hide_expired' )
		);

		add_settings_field(
			'apply_shortcode',
			__( 'Default Apply Form Shortcode', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_textarea' ),
			self::PAGE_SLUG,
			'apqrinu_general',
			array(
				'key'         => 'apply_shortcode',
				'description' => __( 'Optional. Paste any form-plugin shortcode (Fluent Forms, WPForms, Contact Form 7, Gravity Forms, Forminator, etc.) to use it as the default application form site-wide. A per-job shortcode (set on the job) takes precedence; if both are empty the built-in form is used.', 'apqrinu-job-board' ),
				'placeholder' => '[fluentform id="3"]',
			)
		);

		// ---- Related Jobs section ----
		add_settings_section(
			'apqrinu_related',
			__( 'Related Jobs', 'apqrinu-job-board' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'show_related',
			__( 'Show related jobs on single', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_checkbox' ),
			self::PAGE_SLUG,
			'apqrinu_related',
			array(
				'key'         => 'show_related',
				'description' => __( 'Master switch for the "Similar jobs" section on single job pages.', 'apqrinu-job-board' ),
			)
		);

		add_settings_field(
			'hide_related_if_empty',
			__( 'Hide section when no similar jobs found', 'apqrinu-job-board' ),
			array( __CLASS__, 'field_checkbox' ),
			self::PAGE_SLUG,
			'apqrinu_related',
			array(
				'key'         => 'hide_related_if_empty',
				'description' => __( 'When checked, the entire "Similar jobs" section is hidden if there are no matching results (instead of showing the empty-state message).', 'apqrinu-job-board' ),
			)
		);

		// ---- Colors section ----
		add_settings_section(
			'apqrinu_colors',
			__( 'Colors', 'apqrinu-job-board' ),
			array( __CLASS__, 'render_colors_intro' ),
			self::PAGE_SLUG
		);

		$color_fields = array(
			'color_primary'       => __( 'Primary (buttons / accents)', 'apqrinu-job-board' ),
			'color_primary_hover' => __( 'Primary hover', 'apqrinu-job-board' ),
			'color_text'          => __( 'Text', 'apqrinu-job-board' ),
			'color_card_bg'       => __( 'Card background', 'apqrinu-job-board' ),
			'color_card_border'   => __( 'Card border', 'apqrinu-job-board' ),
			'color_meta_bg'       => __( 'Meta pill background', 'apqrinu-job-board' ),
			'color_meta_text'     => __( 'Meta pill text', 'apqrinu-job-board' ),
		);
		$defaults = APQRINU_Helpers::default_settings();
		foreach ( $color_fields as $color_key => $color_label ) {
			add_settings_field(
				$color_key,
				$color_label,
				array( __CLASS__, 'field_color' ),
				self::PAGE_SLUG,
				'apqrinu_colors',
				array(
					'key'     => $color_key,
					'default' => $defaults[ $color_key ],
				)
			);
		}
	}

	/**
	 * Intro text for the Colors section.
	 */
	public static function render_colors_intro() {
		echo '<p>' . esc_html__( 'Pick the accent and surface colors used by the listings, single page, chips, and modal. Click a swatch to open the picker. Click "Default" to reset a color.', 'apqrinu-job-board' ) . '</p>';
	}

	/**
	 * Sanitize the settings array.
	 *
	 * @param array $input Posted input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = APQRINU_Helpers::default_settings();
		$out      = $defaults;
		$input    = is_array( $input ) ? $input : array();

		$out['per_page']          = isset( $input['per_page'] ) ? max( 1, (int) $input['per_page'] ) : $defaults['per_page'];
		$out['related_per_page']  = isset( $input['related_per_page'] ) ? max( 1, (int) $input['related_per_page'] ) : $defaults['related_per_page'];
		$out['currency']          = isset( $input['currency'] ) ? strtoupper( substr( sanitize_text_field( $input['currency'] ), 0, 3 ) ) : $defaults['currency'];
		$out['currency_symbol']   = isset( $input['currency_symbol'] ) ? sanitize_text_field( $input['currency_symbol'] ) : $defaults['currency_symbol'];
		$out['apply_email']           = isset( $input['apply_email'] ) ? sanitize_email( $input['apply_email'] ) : '';
		$out['enable_apply_form']     = ! empty( $input['enable_apply_form'] ) ? 1 : 0;
		$out['hide_expired']          = ! empty( $input['hide_expired'] ) ? 1 : 0;
		$out['apply_shortcode']       = isset( $input['apply_shortcode'] ) ? sanitize_textarea_field( $input['apply_shortcode'] ) : '';
		$out['show_related']          = ! empty( $input['show_related'] ) ? 1 : 0;
		$out['hide_related_if_empty'] = ! empty( $input['hide_related_if_empty'] ) ? 1 : 0;

		foreach ( array( 'color_primary', 'color_primary_hover', 'color_text', 'color_card_bg', 'color_card_border', 'color_meta_bg', 'color_meta_text' ) as $color_key ) {
			$out[ $color_key ] = APQRINU_Helpers::sanitize_hex_color(
				isset( $input[ $color_key ] ) ? $input[ $color_key ] : '',
				$defaults[ $color_key ]
			);
		}

		return $out;
	}

	/**
	 * Color field renderer (uses the WordPress color picker).
	 *
	 * @param array $args Field args.
	 */
	public static function field_color( $args ) {
		$key     = $args['key'];
		$value   = (string) APQRINU_Helpers::get_setting( $key, '' );
		$default = isset( $args['default'] ) ? (string) $args['default'] : '';
		printf(
			'<input type="text" class="apqrinu-color-field" name="%1$s[%2$s]" value="%3$s" data-default-color="%4$s" />',
			esc_attr( APQRINU_Helpers::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $value ),
			esc_attr( $default )
		);
	}

	/**
	 * Textarea field renderer.
	 *
	 * @param array $args Field args.
	 */
	public static function field_textarea( $args ) {
		$key         = $args['key'];
		$value       = (string) APQRINU_Helpers::get_setting( $key, '' );
		$placeholder = isset( $args['placeholder'] ) ? (string) $args['placeholder'] : '';
		printf(
			'<textarea name="%1$s[%2$s]" rows="3" class="large-text code" placeholder="%3$s">%4$s</textarea>',
			esc_attr( APQRINU_Helpers::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $placeholder ),
			esc_textarea( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Number field renderer.
	 *
	 * @param array $args Field args.
	 */
	public static function field_number( $args ) {
		$key   = $args['key'];
		$value = (int) APQRINU_Helpers::get_setting( $key, 0 );
		printf(
			'<input type="number" min="1" name="%1$s[%2$s]" value="%3$s" class="small-text" />',
			esc_attr( APQRINU_Helpers::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * Text field renderer.
	 *
	 * @param array $args Field args.
	 */
	public static function field_text( $args ) {
		$key   = $args['key'];
		$value = (string) APQRINU_Helpers::get_setting( $key, '' );
		printf(
			'<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" />',
			esc_attr( APQRINU_Helpers::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Email field renderer.
	 *
	 * @param array $args Field args.
	 */
	public static function field_email( $args ) {
		$key   = $args['key'];
		$value = (string) APQRINU_Helpers::get_setting( $key, '' );
		printf(
			'<input type="email" name="%1$s[%2$s]" value="%3$s" class="regular-text" />',
			esc_attr( APQRINU_Helpers::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Checkbox field renderer.
	 *
	 * @param array $args Field args.
	 */
	public static function field_checkbox( $args ) {
		$key   = $args['key'];
		$value = (int) APQRINU_Helpers::get_setting( $key, 0 );
		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( APQRINU_Helpers::OPTION_KEY ),
			esc_attr( $key ),
			checked( 1, $value, false ),
			esc_html__( 'Enable', 'apqrinu-job-board' )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render the settings page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Jobs Board Settings', 'apqrinu-job-board' ) . '</h1>';
		echo '<form action="options.php" method="post">';
		settings_fields( self::GROUP );
		do_settings_sections( self::PAGE_SLUG );
		submit_button();
		echo '</form>';
		echo '<h2>' . esc_html__( 'Shortcodes', 'apqrinu-job-board' ) . '</h2>';
		echo '<ul>';
		echo '<li><code>[apqrinu_listings]</code> — ' . esc_html__( 'full listing with filters and pagination.', 'apqrinu-job-board' ) . '</li>';
		echo '<li><code>[apqrinu_related job_id="123"]</code> — ' . esc_html__( 'related jobs (defaults to current job on single).', 'apqrinu-job-board' ) . '</li>';
		echo '<li><code>[apqrinu_apply_form job_id="123"]</code> — ' . esc_html__( 'apply form for a specific job.', 'apqrinu-job-board' ) . '</li>';
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Add a Settings link on the plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url   = admin_url( 'edit.php?post_type=apqrinu_job&page=' . self::PAGE_SLUG );
		$links = array_merge(
			array( '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'apqrinu-job-board' ) . '</a>' ),
			$links
		);
		return $links;
	}
}
