<?php
/**
 * Main plugin bootstrap.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

require_once APQRINU_PATH . 'includes/class-helpers.php';
require_once APQRINU_PATH . 'includes/class-post-type.php';
require_once APQRINU_PATH . 'includes/class-taxonomies.php';
require_once APQRINU_PATH . 'includes/class-meta.php';
require_once APQRINU_PATH . 'includes/class-assets.php';
require_once APQRINU_PATH . 'includes/class-template-loader.php';
require_once APQRINU_PATH . 'includes/class-ajax.php';
require_once APQRINU_PATH . 'includes/class-applications.php';
require_once APQRINU_PATH . 'includes/class-shortcodes.php';
require_once APQRINU_PATH . 'includes/class-settings.php';
require_once APQRINU_PATH . 'includes/class-schema.php';

/**
 * Plugin singleton.
 */
final class APQRINU_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var APQRINU_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Boot the plugin and return the instance.
	 *
	 * @return APQRINU_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor wires up the modules.
	 */
	private function __construct() {
		// WordPress.org auto-loads translations for plugin slugs since WP 4.6 — no manual load_plugin_textdomain() needed.

		APQRINU_Post_Type::init();
		APQRINU_Taxonomies::init();
		APQRINU_Meta::init();
		APQRINU_Template_Loader::init();
		APQRINU_Assets::init();
		APQRINU_Ajax::init();
		APQRINU_Applications::init();
		APQRINU_Shortcodes::init();
		APQRINU_Settings::init();
		APQRINU_Schema::init();
	}

	/**
	 * Activation: register CPT/tax then flush rewrites.
	 */
	public static function activate() {
		require_once APQRINU_PATH . 'includes/class-post-type.php';
		require_once APQRINU_PATH . 'includes/class-taxonomies.php';

		APQRINU_Post_Type::register();
		APQRINU_Taxonomies::register();

		flush_rewrite_rules();
	}

	/**
	 * Deactivation: flush rewrites.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
