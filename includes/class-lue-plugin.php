<?php
/**
 * Core: SEOPress checks, URL helpers and the llms.txt output filter.
 *
 * @package LLMS_URL_Exclusions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LUE_Plugin
 */
final class LUE_Plugin {

	const OPTION        = 'lue_excluded_urls';
	const TOKEN_OPTION  = 'lue_preview_token';
	const PREVIEW_PARAM = 'lue_preview';

	/**
	 * Register front-end hooks.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_filter_llms' ), 0 );
	}

	/* ------------------------------------------------------------------
	 * SEOPress checks
	 * ---------------------------------------------------------------- */

	/**
	 * Is SEOPress PRO installed and active?
	 *
	 * @return bool
	 */
	public static function seopress_pro_active() {
		if ( defined( 'SEOPRESS_PRO_VERSION' ) ) {
			return true;
		}
		$active = (array) get_option( 'active_plugins', array() );
		foreach ( $active as $plugin ) {
			if ( 0 === strpos( (string) $plugin, 'wp-seopress-pro/' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is the llms.txt feature switched on in SEOPress PRO?
	 *
	 * @return bool
	 */
	public static function llms_enabled() {
		if ( ! self::seopress_pro_active() ) {
			return false;
		}
		if ( function_exists( 'seopress_get_toggle_option' ) ) {
			return '1' === (string) seopress_get_toggle_option( 'llms' );
		}
		$toggles = get_option( 'seopress_toggle' );
		return is_array( $toggles ) && isset( $toggles['toggle-llms'] ) && '1' === (string) $toggles['toggle-llms'];
	}

	/* ------------------------------------------------------------------
	 * URL helpers
	 * ---------------------------------------------------------------- */

	/**
	 * Hosts that belong to this site (without www.).
	 *
	 * @return string[]
	 */
	private static function site_hosts() {
		$hosts = array();
		foreach ( array( home_url(), site_url() ) as $u ) {
			$h = strtolower( (string) wp_parse_url( $u, PHP_URL_HOST ) );
			if ( $h ) {
				$hosts[] = preg_replace( '/^www\./', '', $h );
			}
		}
		return array_unique( $hosts );
	}

	/**
	 * Comparison key for a URL or path.
	 * Internal URLs => "/path" (host independent, survives staging -> live).
	 * External URLs => "host/path".
	 *
	 * @param string $url URL or path.
	 * @return string
	 */
	public static function url_key( $url ) {
		$url = trim( html_entity_decode( (string) $url, ENT_QUOTES ) );
		$url = rtrim( $url, '.,;:!?' );
		if ( '' === $url ) {
			return '';
		}
		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'https:' . $url;
		}

		$host = '';
		if ( preg_match( '#^https?://#i', $url ) ) {
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		} else {
			$path = (string) wp_parse_url( '/' . ltrim( $url, '/' ), PHP_URL_PATH );
		}

		$path = '/' . trim( strtolower( rawurldecode( $path ) ), '/' );
		$bare = preg_replace( '/^www\./', '', $host );

		if ( '' !== $host && ! in_array( $bare, self::site_hosts(), true ) ) {
			return $bare . $path;
		}
		return $path;
	}

	/**
	 * Saved exclusions.
	 *
	 * @return string[]
	 */
	public static function get_excluded() {
		$list = get_option( self::OPTION, array() );
		return is_array( $list ) ? $list : array();
	}

	/**
	 * Secret token for fetching the unfiltered llms.txt from the settings page.
	 *
	 * @return string
	 */
	public static function preview_token() {
		$token = (string) get_option( self::TOKEN_OPTION, '' );
		if ( '' === $token ) {
			$token = wp_generate_password( 32, false, false );
			update_option( self::TOKEN_OPTION, $token, false );
		}
		return $token;
	}

	/* ------------------------------------------------------------------
	 * Front end
	 * ---------------------------------------------------------------- */

	/**
	 * Is the current request for llms.txt?
	 *
	 * @return bool
	 */
	private static function is_llms_request() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = strtolower( untrailingslashit( rawurldecode( (string) wp_parse_url( $uri, PHP_URL_PATH ) ) ) );
		$base = strtolower( untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) );
		return $base . '/llms.txt' === $path;
	}

	/**
	 * Start an output buffer that strips excluded URLs from llms.txt.
	 */
	public static function maybe_filter_llms() {
		if ( is_admin() || ! self::is_llms_request() ) {
			return;
		}

		// Unfiltered preview used by the settings page.
		$token = (string) get_option( self::TOKEN_OPTION, '' );
		if ( '' !== $token && isset( $_GET[ self::PREVIEW_PARAM ] ) && hash_equals( $token, (string) wp_unslash( $_GET[ self::PREVIEW_PARAM ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			return;
		}

		if ( ! self::llms_enabled() ) {
			return;
		}

		$excluded = self::get_excluded();
		if ( empty( $excluded ) ) {
			return;
		}

		ob_start(
			static function ( $output ) use ( $excluded ) {
				return LUE_Plugin::filter_content( $output, $excluded );
			}
		);
	}

	/**
	 * Remove every line that links to an excluded URL.
	 *
	 * @param string $content  llms.txt content.
	 * @param array  $excluded Excluded URLs / paths.
	 * @return string
	 */
	public static function filter_content( $content, array $excluded ) {
		if ( ! is_string( $content ) || '' === $content || empty( $excluded ) ) {
			return $content;
		}

		$keys = array_flip( array_filter( array_map( array( __CLASS__, 'url_key' ), $excluded ) ) );
		if ( empty( $keys ) ) {
			return $content;
		}

		$parts   = preg_split( '/(\r\n|\n|\r)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out     = '';
		$removed = false;
		$count   = count( $parts );

		for ( $i = 0; $i < $count; $i += 2 ) {
			$line = $parts[ $i ];
			$eol  = isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '';
			$drop = false;

			if ( preg_match_all( '#https?://[^\s<>()\[\]"\']+#i', $line, $m ) ) {
				foreach ( $m[0] as $u ) {
					if ( isset( $keys[ self::url_key( $u ) ] ) ) {
						$drop = true;
						break;
					}
				}
			}

			if ( $drop ) {
				$removed = true;
				continue;
			}
			$out .= $line . $eol;
		}

		if ( $removed ) {
			$out = preg_replace( '/(\r?\n){3,}/', "\n\n", $out );
		}

		return $out;
	}
}
