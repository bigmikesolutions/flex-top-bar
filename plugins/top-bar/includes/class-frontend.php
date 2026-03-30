<?php
/**
 * Top Bar frontend: bar output, assets, hide-on-scroll.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	public function __construct() {
		add_action( 'wp_body_open', [ $this, 'maybe_render_bar' ], 5 );
		add_action( 'wp_footer', [ $this, 'maybe_output_bar_fallback' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	private function should_show_bar(): bool {
		$has_active = Options::get_active_bars() !== [];
		return (bool) apply_filters( 'top_bar_show', $has_active );
	}

	public function maybe_render_bar(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		$this->render_mount_point();
	}

	private function render_mount_point(): void {
		// Output Vue mount point
		echo '<div id="top-bar-frontend-mount"></div>';
	}


	/** Fallback when theme does not call wp_body_open: inject mount point at start of body. */
	public function maybe_output_bar_fallback(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		?>
		<script id="top-bar-fallback">
		(function(){
			if(document.getElementById('top-bar-frontend-mount')) return;
			var mount = document.createElement('div');
			mount.id = 'top-bar-frontend-mount';
			document.body.insertBefore(mount, document.body.firstChild);
		})();
		</script>
		<?php
	}

	public function enqueue_assets(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}

		// Enqueue Vue frontend app
		$frontend_js = TOP_BAR_PLUGIN_DIR . 'assets/dist/js/frontend.js';
		$frontend_css = TOP_BAR_PLUGIN_DIR . 'assets/dist/css/frontend.css';

		if ( file_exists( $frontend_js ) ) {
			$ver_js = (string) filemtime( $frontend_js );
			wp_enqueue_script(
				'top-bar-frontend',
				plugins_url( 'assets/dist/js/frontend.js', TOP_BAR_PLUGIN_FILE ),
				[],
				$ver_js,
				true
			);
		}

		// Add type="module" attribute for ES modules (only on frontend)
		if ( ! is_admin() ) {
			add_filter( 'script_loader_tag', [ $this, 'add_module_type_to_script' ], 10, 3 );
		}

		if ( file_exists( $frontend_css ) ) {
			$ver_css = (string) filemtime( $frontend_css );
			wp_enqueue_style(
				'top-bar-frontend',
				plugins_url( 'assets/dist/css/frontend.css', TOP_BAR_PLUGIN_FILE ),
				[],
				$ver_css
			);
		}

	}

	/**
	 * Add type="module" attribute to frontend script tag for ES modules.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 */
	public function add_module_type_to_script( string $tag, string $handle, string $src ): string {
		if ( 'top-bar-frontend' === $handle ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}
		return $tag;
	}
}
