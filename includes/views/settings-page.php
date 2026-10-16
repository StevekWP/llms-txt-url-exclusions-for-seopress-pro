<?php
/**
 * View: settings page.
 *
 * @package LLMS_URL_Exclusions
 * @var string[]   $excluded
 * @var array      $ex_keys
 * @var array|null $links
 * @var array      $types
 * @var string     $view_url
 * @var string     $llms_settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap lue-wrap">
	<h1><?php esc_html_e( 'llms.txt URL Exclusions', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></h1>

	<p class="lue-intro">
		<?php esc_html_e( 'URLs listed here are removed from llms.txt only. They stay indexable by Google and other search engines: no noindex is added and XML sitemaps / robots.txt are not changed.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?>
	</p>
	<p>
		<?php /* <a class="button" href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View llms.txt', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></a> */ ?>
		<a class="button" href="<?php echo esc_url( $llms_settings ); ?>"><?php esc_html_e( 'SEOPress llms.txt settings', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></a>
	</p>

	<?php settings_errors(); ?>

	<form method="post" action="options.php" id="lue-form">
		<?php settings_fields( LUE_Admin::GROUP ); ?>

		<div class="lue-card">
			<h2><?php esc_html_e( 'Excluded URLs', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></h2>
			<p class="description"><?php esc_html_e( 'One URL per line. Full URLs or paths (e.g. /blog/my-post/) work for posts, pages and any custom post type. Lines starting with # are ignored.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></p>
			<textarea name="<?php echo esc_attr( LUE_Plugin::OPTION ); ?>" id="lue-urls" rows="10" class="large-text code"><?php echo esc_textarea( implode( "\n", $excluded ) ); ?></textarea>
		</div>

		<?php /*<div class="lue-card">
			<h2><?php esc_html_e( 'Add content to exclude', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></h2>
			<p class="lue-search-row">
				<label class="screen-reader-text" for="lue-type"><?php esc_html_e( 'Content type', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></label>
				<select id="lue-type">
					<option value="any"><?php esc_html_e( 'All content types', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></option>
					<?php foreach ( $types as $type_obj ) : ?>
						<option value="<?php echo esc_attr( $type_obj->name ); ?>"><?php echo esc_html( $type_obj->labels->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="screen-reader-text" for="lue-term"><?php esc_html_e( 'Search', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></label>
				<input type="search" id="lue-term" class="regular-text" placeholder="<?php esc_attr_e( 'Search by title…', 'llms-txt-url-exclusions-for-seopress-pro' ); ?>">
				<button type="button" class="button" id="lue-search"><?php esc_html_e( 'Search', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></button>
			</p>
			<table class="widefat striped lue-table" id="lue-results" hidden><tbody></tbody></table>
		</div>

		<div class="lue-card">
			<h2><?php esc_html_e( 'Links currently in llms.txt', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></h2>
			<?php if ( null === $links ) : ?>
				<p class="description"><?php esc_html_e( 'Could not load llms.txt from the server. You can still add URLs above.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></p>
			<?php elseif ( empty( $links ) ) : ?>
				<p class="description"><?php esc_html_e( 'No internal links were found in llms.txt.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Tick a link to exclude it (this updates the list above). Click "Save Changes" to apply.', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></p>
				<table class="widefat striped lue-table">
					<thead>
						<tr>
							<th class="lue-col-check"><?php esc_html_e( 'Exclude', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></th>
							<th><?php esc_html_e( 'Title', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></th>
							<th><?php esc_html_e( 'URL', 'llms-txt-url-exclusions-for-seopress-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $links as $link ) : ?>
						<tr>
							<td class="lue-col-check"><input type="checkbox" class="lue-pick" value="<?php echo esc_attr( $link['path'] ); ?>" aria-label="<?php echo esc_attr( $link['title'] ); ?>" <?php checked( isset( $ex_keys[ LUE_Plugin::url_key( $link['path'] ) ] ) ); ?>></td>
							<td><?php echo esc_html( $link['title'] ); ?></td>
							<td><a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( $link['path'] ); ?></code></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div> */ ?>

		<?php submit_button(); ?>
	</form>
</div>
