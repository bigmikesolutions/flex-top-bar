<?php
/**
 * Top Bar admin settings page and option registration.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar;

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

		// Enqueue Vue app
		$admin_js  = TOP_BAR_PLUGIN_DIR . 'assets/dist/js/admin.js';
		$admin_css = TOP_BAR_PLUGIN_DIR . 'assets/dist/css/style.css';
		if ( file_exists( $admin_js ) ) {
			$ver_js  = (string) filemtime( $admin_js );
			$ver_css = file_exists( $admin_css ) ? (string) filemtime( $admin_css ) : TOP_BAR_VERSION;

			wp_enqueue_script(
				'top-bar-admin-vue',
				plugins_url( 'assets/dist/js/admin.js', TOP_BAR_PLUGIN_FILE ),
				[],
				$ver_js,
				true
			);

			wp_enqueue_style(
				'top-bar-admin-vue',
				plugins_url( 'assets/dist/css/style.css', TOP_BAR_PLUGIN_FILE ),
				[],
				$ver_css
			);

			// Pass configuration to Vue
			wp_localize_script(
				'top-bar-admin-vue',
				'topBarConfig',
				[
					'apiRoot' => esc_url_raw( rest_url( 'top-bar/v1' ) ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'i18n'    => [
						'welcome'     => __( 'Welcome to Top Bar plugin', 'top-bar' ),
						'addNew'      => __( 'Add new Top Bar', 'top-bar' ),
						'loading'     => __( 'Loading...', 'top-bar' ),
						'saveChanges' => __( 'Save Changes', 'top-bar' ),
					],
				]
			);
		}
	}


	public function add_menu_page(): void {
		$this->hook_suffix = (string) add_menu_page(
			__( 'Top Bar', 'top-bar' ),
			__( 'Top Bar', 'top-bar' ),
			'manage_options',
			'top-bar',
			[ $this, 'render_settings_page' ],
			'dashicons-megaphone'
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
		if ( 'top-bar-admin-vue' === $handle ) {
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
