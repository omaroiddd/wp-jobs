<?php
/**
 * Default archive template for the 'apqrinu_job' CPT.
 *
 * Themes may override by placing apqrinu-job-board/archive-apqrinu_job.php
 * (or archive-apqrinu_job.php) in the theme.
 *
 * @package Apqrinu_Job_Board
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="apqrinu-archive-main">
	<?php
	$apqrinu_archive_file = APQRINU_Helpers::locate_template( 'parts/jobs-archive.php' );
	if ( $apqrinu_archive_file ) {
		include $apqrinu_archive_file;
	}
	?>
</main>
<?php
get_footer();
