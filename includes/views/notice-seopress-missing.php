<?php
/**
 * View: SEOPress PRO not installed / active.
 *
 * @package LLMS_URL_Exclusions
 * @var string $plugins_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap lue-wrap">
	<h1><?php esc_html_e( 'llms.txt URL Exclusions', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></h1>
	<div class="notice notice-error inline lue-notice">
		<p>
			<strong><?php esc_html_e( 'Please Install/Activate SEOPress PRO plugin.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></strong>
			<?php esc_html_e( 'These settings are available only when SEOPress PRO is active and its llms.txt feature is enabled.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?>
		</p>
		<p><a class="button button-primary" href="<?php echo esc_url( $plugins_url ); ?>"><?php esc_html_e( 'Go to Plugins', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></a></p>
	</div>
</div>
