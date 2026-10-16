<?php
/**
 * Admin: settings page, AJAX search, cache purge.
 *
 * @package LLMS_URL_Exclusions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LUE_Admin
 */
final class LUE_Admin {

	const PAGE_SLUG = 'llms-url-exclusions';
	const GROUP     = 'lue_settings';

	/**
	 * Page hook suffix.
	 *
	 * @var string
	 */
	private static $hook = '';

	/**
	 * Register admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 99 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_lue_search', array( __CLASS__, 'ajax_search' ) );
		add_action( 'update_option_' . LUE_Plugin::OPTION, array( __CLASS__, 'purge_caches' ) );
		add_action( 'add_option_' . LUE_Plugin::OPTION, array( __CLASS__, 'purge_caches' ) );
		add_filter( 'plugin_action_links_' . LUE_BASENAME, array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Parent menu: SEOPress menu when available, otherwise Settings.
	 *
	 * @return string
	 */
	private static function parent_slug() {
		global $admin_page_hooks;
		return ( is_array( $admin_page_hooks ) && isset( $admin_page_hooks['seopress-option'] ) ) ? 'seopress-option' : 'options-general.php';
	}

	/**
	 * Settings page URL.
	 *
	 * @return string
	 */
	public static function page_url() {
		$url = function_exists( 'menu_page_url' ) ? menu_page_url( self::PAGE_SLUG, false ) : '';
		return $url ? $url : admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Add the settings page.
	 */
	public static function admin_menu() {
		self::$hook = (string) add_submenu_page(
			self::parent_slug(),
			__( 'llms.txt Exclusions', 'llms-txt-url-exclusions-for-seopress-pro' ),
			__( 'llms.txt Exclusions', 'llms-txt-url-exclusions-for-seopress-pro' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Plugins screen "Settings" link.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::page_url() ) . '">' . esc_html__( 'Settings', 'llms-txt-url-exclusions-for-seopress-pro' ) . '</a>' );
		return $links;
	}

	/**
	 * Register the option.
	 */
	public static function register_settings() {
		register_setting(
			self::GROUP,
			LUE_Plugin::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize textarea input into a list of URLs / paths.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	public static function sanitize( $value ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/\r\n|\r|\n/', $value );
		}
		$clean = array();
		foreach ( (array) $value as $line ) {
			$line = trim( sanitize_text_field( (string) $line ) );
			if ( '' === $line || 0 === strpos( $line, '#' ) ) {
				continue;
			}
			$key = LUE_Plugin::url_key( $line );
			if ( '' === $key ) {
				continue;
			}
			// Internal URLs are stored as paths so they keep working after staging is pushed live.
			$clean[ $key ] = ( 0 === strpos( $key, '/' ) ) ? $key . ( '/' === $key ? '' : '/' ) : esc_url_raw( $line );
		}
		return array_values( $clean );
	}

	/**
	 * Purge cached llms.txt (WP Rocket, SiteGround Optimizer).
	 */
	public static function purge_caches() {
		$url = home_url( '/llms.txt' );
		if ( function_exists( 'rocket_clean_files' ) ) {
			rocket_clean_files( array( $url, trailingslashit( $url ) ) );
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache( $url );
		}
	}

	/**
	 * Enqueue assets on our page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( $hook ) {
		if ( '' === self::$hook || $hook !== self::$hook ) {
			return;
		}
		wp_enqueue_style( 'lue-admin', LUE_URL . 'assets/css/admin.css', array(), LUE_VERSION );

		if ( ! LUE_Plugin::llms_enabled() ) {
			return;
		}
		wp_enqueue_script( 'lue-admin', LUE_URL . 'assets/js/admin.js', array(), LUE_VERSION, true );
		wp_localize_script(
			'lue-admin',
			'LUE',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'lue_search' ),
				'i18n'    => array(
					'exclude' => __( 'Exclude', 'llms-txt-url-exclusions-for-seopress-pro' ),
					'added'   => __( 'Added', 'llms-txt-url-exclusions-for-seopress-pro' ),
					'none'    => __( 'Nothing found.', 'llms-txt-url-exclusions-for-seopress-pro' ),
					'error'   => __( 'Search failed. Please try again.', 'llms-txt-url-exclusions-for-seopress-pro' ),
				),
			)
		);
	}

	/**
	 * Fetch the unfiltered llms.txt and return the internal links it contains.
	 *
	 * @return array|null Null when llms.txt could not be loaded.
	 */
	private static function current_links() {
		$url  = add_query_arg(
			array(
				LUE_Plugin::PREVIEW_PARAM => LUE_Plugin::preview_token(),
				'lue_nc'                  => time(),
			),
			home_url( '/llms.txt' )
		);
		$resp = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 3,
			)
		);
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return null;
		}

		$body  = (string) wp_remote_retrieve_body( $resp );
		$links = array();
		if ( preg_match_all( '#\[([^\]]*)\]\((https?://[^)\s]+)\)#i', $body, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $row ) {
				$key = LUE_Plugin::url_key( $row[2] );
				if ( '' === $key || 0 !== strpos( $key, '/' ) ) {
					continue; // Skip external links.
				}
				$links[ $key ] = array(
					'title' => html_entity_decode( wp_strip_all_tags( $row[1] ), ENT_QUOTES ),
					'url'   => $row[2],
					'path'  => $key . ( '/' === $key ? '' : '/' ),
				);
			}
		}
		return array_values( $links );
	}

	/**
	 * AJAX: search published content of any public post type.
	 */
	public static function ajax_search() {
		check_ajax_referer( 'lue_search', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'any';

		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );
		$post_type = ( 'any' !== $type && isset( $types[ $type ] ) ) ? $type : array_values( $types );

		$query = new WP_Query(
			array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				's'                   => $term,
				'posts_per_page'      => 20,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'orderby'             => '' === $term ? 'date' : 'relevance',
			)
		);

		$out = array();
		foreach ( $query->posts as $p ) {
			$pto   = get_post_type_object( $p->post_type );
			$link  = get_permalink( $p );
			$key   = LUE_Plugin::url_key( $link );
			$out[] = array(
				'title' => html_entity_decode( get_the_title( $p ), ENT_QUOTES ),
				'type'  => $pto ? $pto->labels->singular_name : $p->post_type,
				'url'   => $link,
				'path'  => $key . ( '/' === $key ? '' : '/' ),
			);
		}
		wp_send_json_success( $out );
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$llms_settings = admin_url( 'admin.php?page=seopress-pro-page#tab=tab_seopress_llms' );

		if ( ! LUE_Plugin::seopress_pro_active() ) {
			$plugins_url = admin_url( 'plugins.php' );
			include LUE_DIR . 'includes/views/notice-seopress-missing.php';
			return;
		}

		if ( ! LUE_Plugin::llms_enabled() ) {
			include LUE_DIR . 'includes/views/notice-llms-disabled.php';
			return;
		}

		$excluded = LUE_Plugin::get_excluded();
		$ex_keys  = array_flip( array_map( array( 'LUE_Plugin', 'url_key' ), $excluded ) );
		$links    = self::current_links();
		$types    = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );
		$view_url = add_query_arg( 'nocache', time(), home_url( '/llms.txt' ) );

		include LUE_DIR . 'includes/views/settings-page.php';
	}
}
