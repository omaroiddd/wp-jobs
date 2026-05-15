<?php
/**
 * Shared helpers.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static helper utilities.
 */
class APQRINU_Helpers {

	/**
	 * Plugin option key.
	 */
	const OPTION_KEY = 'apqrinu_settings';

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'per_page'              => 5,
			'related_per_page'      => 3,
			'currency'              => 'USD',
			'currency_symbol'       => '$',
			'apply_email'           => '',
			'enable_apply_form'     => 1,
			'hide_expired'          => 0,
			'apply_shortcode'       => '',
			'show_related'          => 1,
			'hide_related_if_empty' => 0,
			'color_primary'         => '#4f46e5',
			'color_primary_hover'   => '#4338ca',
			'color_text'            => '#111827',
			'color_card_bg'         => '#ffffff',
			'color_card_border'     => '#e5e7eb',
			'color_meta_bg'         => '#1f2937',
			'color_meta_text'       => '#ffffff',
		);
	}

	/**
	 * Sanitize a hex color value.
	 *
	 * @param string $color    Raw color.
	 * @param string $fallback Fallback when invalid.
	 * @return string
	 */
	public static function sanitize_hex_color( $color, $fallback = '' ) {
		$color = trim( (string) $color );
		if ( '' === $color ) {
			return $fallback;
		}
		if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
			return $color;
		}
		return $fallback;
	}

	/**
	 * Read a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = wp_parse_args( get_option( self::OPTION_KEY, array() ), self::default_settings() );
		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}
		return $default;
	}

	/**
	 * Active taxonomy slugs for jobs.
	 *
	 * @return string[]
	 */
	public static function taxonomies() {
		return array( 'apqrinu_job_type', 'apqrinu_work_mode', 'apqrinu_experience_level', 'apqrinu_job_location' );
	}

	/**
	 * Sanitize a posted taxonomy filter value.
	 *
	 * @param array  $source Source array (typically $_POST or $_GET).
	 * @param string $tax    Taxonomy slug.
	 * @return string
	 */
	public static function sanitize_filter_value( $source, $tax ) {
		if ( empty( $source[ $tax ] ) ) {
			return '';
		}
		// Decode then sanitize as a slug-like value.
		$raw = wp_unslash( $source[ $tax ] );
		if ( is_array( $raw ) ) {
			$raw = reset( $raw );
		}
		$raw = urldecode( (string) $raw );
		return sanitize_title( $raw );
	}

	/**
	 * Format salary range.
	 *
	 * @param float|int|string $min      Minimum salary.
	 * @param float|int|string $max      Maximum salary.
	 * @param string           $currency Currency symbol.
	 * @return string
	 */
	public static function format_salary( $min, $max, $currency = '' ) {
		$currency = '' === $currency ? self::get_setting( 'currency_symbol', '$' ) : $currency;
		$min      = is_numeric( $min ) ? (float) $min : 0;
		$max      = is_numeric( $max ) ? (float) $max : 0;

		if ( $min > 0 && $max > 0 ) {
			return sprintf(
				/* translators: 1: min, 2: max, 3: currency */
				__( '%1$s – %2$s %3$s', 'apqrinu-job-board' ),
				number_format_i18n( $min ),
				number_format_i18n( $max ),
				$currency
			);
		}
		if ( $min > 0 ) {
			return sprintf(
				/* translators: 1: min, 2: currency */
				__( 'From %1$s %2$s', 'apqrinu-job-board' ),
				number_format_i18n( $min ),
				$currency
			);
		}
		if ( $max > 0 ) {
			return sprintf(
				/* translators: 1: max, 2: currency */
				__( 'Up to %1$s %2$s', 'apqrinu-job-board' ),
				number_format_i18n( $max ),
				$currency
			);
		}
		return '';
	}

	/**
	 * Render a job card.
	 *
	 * @param int $post_id Job post ID.
	 * @return void
	 */
	public static function render_job_card( $post_id ) {
		$apqrinu_file = self::locate_template( 'parts/job-card.php' );
		if ( $apqrinu_file ) {
			$apqrinu_card_args = array( 'post_id' => (int) $post_id );
			include $apqrinu_file;
			unset( $apqrinu_card_args );
		}
	}

	/**
	 * Locate a template, allowing theme overrides under {theme}/apqrinu-job-board/.
	 *
	 * @param string $relative Relative template path.
	 * @return string Absolute path or empty string.
	 */
	public static function locate_template( $relative ) {
		$relative = ltrim( $relative, '/\\' );

		$theme = locate_template( array( 'apqrinu-job-board/' . $relative ) );
		if ( $theme ) {
			return $theme;
		}

		$plugin = APQRINU_PATH . 'templates/' . $relative;
		if ( file_exists( $plugin ) ) {
			return $plugin;
		}

		return '';
	}

	/**
	 * Render a numeric date diff like "3 days ago" using human_time_diff.
	 *
	 * @param int $post_id Optional post ID.
	 * @return string
	 */
	public static function time_ago( $post_id = 0 ) {
		$ts = (int) get_post_time( 'U', true, $post_id ? $post_id : get_the_ID() );
		if ( ! $ts ) {
			return '';
		}
		return sprintf(
			/* translators: %s: human-readable time difference */
			__( '%s ago', 'apqrinu-job-board' ),
			human_time_diff( $ts )
		);
	}

	/**
	 * Get the company logo image URL for a job.
	 *
	 * @param int $post_id Job ID.
	 * @return array{url:string,alt:string}|null
	 */
	public static function company_logo( $post_id ) {
		$attachment_id = (int) get_post_meta( $post_id, '_apqrinu_company_logo', true );
		if ( ! $attachment_id ) {
			$thumb = get_post_thumbnail_id( $post_id );
			if ( ! $thumb ) {
				return null;
			}
			$attachment_id = $thumb;
		}
		$src = wp_get_attachment_image_src( $attachment_id, 'medium' );
		if ( ! $src ) {
			return null;
		}
		$alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( '' === $alt ) {
			$alt = get_the_title( $attachment_id );
		}
		return array(
			'url' => $src[0],
			'alt' => $alt,
		);
	}
}
