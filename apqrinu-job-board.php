<?php
/**
 * Plugin Name:       Apqrinu Job Board
 * Plugin URI:        https://github.com/omaroiddd/wp-jobs
 * Description:       Lightweight job board with CPT, taxonomy filters, AJAX listing, apply form, JobPosting schema, and template overrides.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            apqrinu
 * Author URI:        https://profiles.wordpress.org/apqrinu/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       apqrinu-job-board
 * Domain Path:       /languages
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

define( 'APQRINU_VERSION', '1.0.0' );
define( 'APQRINU_FILE', __FILE__ );
define( 'APQRINU_PATH', plugin_dir_path( __FILE__ ) );
define( 'APQRINU_URL', plugin_dir_url( __FILE__ ) );
define( 'APQRINU_BASENAME', plugin_basename( __FILE__ ) );

require_once APQRINU_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'APQRINU_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'APQRINU_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'APQRINU_Plugin', 'instance' ) );
