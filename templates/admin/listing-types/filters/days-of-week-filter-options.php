<?php
/**
 * Admin options template for the `days-of-week` filter.
 *
 * Exposes only the shared label field; the choices (7 weekdays) and
 * multi-select behavior are fixed by the filter class.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<?php $this->get_label_field() ?>
