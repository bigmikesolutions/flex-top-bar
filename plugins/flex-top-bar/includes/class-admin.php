<?php
/**
 * Top Bar admin settings page and option registration.
 *
 * @package FlexTopBar
 */

declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	/**
	 * Hook suffix returned by add_menu_page().
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_vue_app' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_module_type_to_script' ], 10, 3 );
	}

	public function enqueue_vue_app( string $hook ): void {
		// Use the actual hook suffix returned by add_menu_page() to avoid mismatches.
		if ( $this->hook_suffix === '' || $hook !== $this->hook_suffix ) {
			return;
		}

		// Enqueue Vue app.
		// Prefer dist-dev when present so `vite build --watch` output is reflected in WP immediately.
		$admin_js_dist_dev  = FLEX_TOP_BAR_PLUGIN_DIR . 'assets/dist-dev/js/admin.js';
		$admin_css_dist_dev = FLEX_TOP_BAR_PLUGIN_DIR . 'assets/dist-dev/css/style.css';
		$admin_js_dist      = FLEX_TOP_BAR_PLUGIN_DIR . 'assets/dist/js/admin.js';
		$admin_css_dist     = FLEX_TOP_BAR_PLUGIN_DIR . 'assets/dist/css/style.css';

		$use_dist_dev = file_exists( $admin_js_dist_dev ) && file_exists( $admin_css_dist_dev );
		$admin_js     = $use_dist_dev ? $admin_js_dist_dev : $admin_js_dist;
		$admin_css    = $use_dist_dev ? $admin_css_dist_dev : $admin_css_dist;

		if ( file_exists( $admin_js ) ) {
			$ver_js  = (string) filemtime( $admin_js );
			$ver_css = file_exists( $admin_css ) ? (string) filemtime( $admin_css ) : FLEX_TOP_BAR_VERSION;

			$admin_js_url  = $use_dist_dev
				? plugins_url( 'assets/dist-dev/js/admin.js', FLEX_TOP_BAR_PLUGIN_FILE )
				: plugins_url( 'assets/dist/js/admin.js', FLEX_TOP_BAR_PLUGIN_FILE );
			$admin_css_url = $use_dist_dev
				? plugins_url( 'assets/dist-dev/css/style.css', FLEX_TOP_BAR_PLUGIN_FILE )
				: plugins_url( 'assets/dist/css/style.css', FLEX_TOP_BAR_PLUGIN_FILE );

			wp_enqueue_script(
				'flex-top-bar-admin-vue',
				$admin_js_url,
				[],
				$ver_js,
				true
			);

			wp_enqueue_style(
				'flex-top-bar-admin-vue',
				$admin_css_url,
				[],
				$ver_css
			);

			// Pass configuration to Vue
			wp_localize_script(
				'flex-top-bar-admin-vue',
				'flexTopBarConfig',
				[
					'apiRoot'         => esc_url_raw( rest_url( 'flex-top-bar/v1' ) ),
					'nonce'           => wp_create_nonce( 'wp_rest' ),
					'version'         => (string) FLEX_TOP_BAR_VERSION,
					'siteTimezone'    => Options::site_timezone(),
					'bmsFaviconUrl'   => esc_url_raw( plugins_url( 'assets/img/bms-favicon.png', FLEX_TOP_BAR_PLUGIN_FILE ) ),
					'i18n'    => [
						'welcome'     => __( 'Welcome to Top Bar plugin', 'flex-top-bar' ),
						'addNew'      => __( 'Add new Top Bar', 'flex-top-bar' ),
						'loading'     => __( 'Loading...', 'flex-top-bar' ),
						'saveChanges' => __( 'Save Changes', 'flex-top-bar' ),
					],
				]
			);
		}
	}


	public function add_menu_page(): void {
		$icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">'
			. '<rect x="2" y="3" width="16" height="3" rx="1" fill="currentColor"/>'
			. '<rect x="2" y="8" width="5" height="9" rx="1" fill="currentColor" opacity="0.85"/>'
			. '<rect x="7.5" y="8" width="5" height="9" rx="1" fill="currentColor" opacity="0.65"/>'
			. '<rect x="13" y="8" width="5" height="9" rx="1" fill="currentColor" opacity="0.45"/>'
			. '</svg>';
		$icon_url = 'data:image/svg+xml;base64,' . base64_encode( $icon_svg );

		$this->hook_suffix = (string) add_menu_page(
			__( 'Flex Top Bar', 'flex-top-bar' ),
			__( 'Flex Top Bar', 'flex-top-bar' ),
			'manage_options',
			'flex-top-bar',
			[ $this, 'render_settings_page' ],
			$icon_url
		);
	}


	/**
	 * Add type="module" attribute to admin script tag for ES modules.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 */
	public function add_module_type_to_script( string $tag, string $handle, string $src ): string {
		if ( 'flex-top-bar-admin-vue' === $handle ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}
		return $tag;
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Render Vue mount point
		echo '<div id="top-bar-app"></div>';
	}
}
