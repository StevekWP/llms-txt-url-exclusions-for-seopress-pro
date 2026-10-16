<?php
/**
 * Uninstall: remove plugin options.
 *
 * @package LLMS_URL_Exclusions
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'lue_excluded_urls' );
delete_option( 'lue_preview_token' );
