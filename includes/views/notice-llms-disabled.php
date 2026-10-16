<?php
/**
 * View: llms.txt disabled in SEOPress PRO.
 *
 * @package LLMS_URL_Exclusions
 * @var string $llms_settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap lue-wrap">
	<h1><?php esc_html_e( 'llms.txt URL Exclusions', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></h1>
	<div class="notice notice-warning inline lue-notice">
		<p>
			<strong><?php esc_html_e( 'Please enable llms.txt from SEOPress PRO first.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></strong>
			<?php esc_html_e( 'URL exclusions apply only while the SEOPress PRO llms.txt feature is enabled.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?>
		</p>
		<p><a class="button button-primary" href="<?php echo esc_url( $llms_settings ); ?>"><?php esc_html_e( 'Go to SEOPress llms.txt settings', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></a></p>
	</div>
</div>
