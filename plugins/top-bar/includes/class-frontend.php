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

	/** @var list<array<string, mixed>>|null Cached active and scheduled bars for this request */
	private ?array $cached_bars = null;

	public function __construct() {
		add_action( 'wp_body_open', [ $this, 'maybe_render_bar' ], 5 );
		add_action( 'wp_footer', [ $this, 'maybe_output_bar_fallback' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Get active bars that are also scheduled to show now (cached for request).
	 *
	 * @return list<array<string, mixed>>
	 */
	private function get_scheduled_bars(): array {
		if ( null !== $this->cached_bars ) {
			return $this->cached_bars;
		}

		$bars = Options::get_active_bars();
		$scheduled_bars = [];

		foreach ( $bars as $bar ) {
			if ( $this->is_bar_scheduled_now( $bar ) ) {
				$scheduled_bars[] = $bar;
			}
		}

		$this->cached_bars = $scheduled_bars;
		return $scheduled_bars;
	}

	private function should_show_bar(): bool {
		$has_active = $this->get_scheduled_bars() !== [];
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
			wp_enqueue_script(
				'top-bar-frontend',
				plugins_url( 'assets/dist/js/frontend.js', TOP_BAR_PLUGIN_FILE ),
				[],
				TOP_BAR_VERSION,
				true
			);
		}

		// Add type="module" attribute for ES modules (only on frontend)
		if ( ! is_admin() ) {
			add_filter( 'script_loader_tag', [ $this, 'add_module_type_to_script' ], 10, 3 );
		}

		if ( file_exists( $frontend_css ) ) {
			wp_enqueue_style(
				'top-bar-frontend',
				plugins_url( 'assets/dist/css/frontend.css', TOP_BAR_PLUGIN_FILE ),
				[],
				TOP_BAR_VERSION
			);
		}

		// Fallback to old CSS if Vue not built
		if ( ! file_exists( $frontend_js ) ) {
			wp_enqueue_style(
				'top-bar',
				plugin_dir_url( TOP_BAR_PLUGIN_FILE ) . 'assets/css/top-bar.css',
				[],
				TOP_BAR_VERSION
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

	public function enqueue_admin_assets(): void {
		wp_enqueue_style(
			'jquery-ui-style',
			'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
			[],
			'1.13.2'
		);
		wp_enqueue_style(
			'top-bar-admin',
			plugins_url( 'assets/css/top-bar-admin.css', TOP_BAR_PLUGIN_FILE ),
			[],
			TOP_BAR_VERSION
		);
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script(
			'top-bar-admin',
			plugins_url( 'assets/js/top-bar-admin.js', TOP_BAR_PLUGIN_FILE ),
			[],
			TOP_BAR_VERSION,
			true
		);
	}

	/**
	 * Check if bar is scheduled and currently within the scheduled time range.
	 *
	 * @param array<string, mixed> $bar
	 */
	private function is_bar_scheduled_now( array $bar ): bool {
		// If scheduling is not enabled, bar is always visible
		if ( empty( $bar['scheduled_enabled'] ) ) {
			return true;
		}

		$from = isset( $bar['scheduled_from_datetime'] ) ? trim( (string) $bar['scheduled_from_datetime'] ) : '';
		$to   = isset( $bar['scheduled_to_datetime'] ) ? trim( (string) $bar['scheduled_to_datetime'] ) : '';

		// If dates are not set, treat as always visible
		if ( $from === '' || $to === '' ) {
			return true;
		}

		// Get current time in site timezone
		$now = current_time( 'timestamp' );

		// Parse datetime strings (ISO 8601 format: YYYY-MM-DDTHH:mm)
		$from_timestamp = strtotime( $from );
		$to_timestamp   = strtotime( $to );

		// If parsing failed, treat as always visible
		if ( false === $from_timestamp || false === $to_timestamp ) {
			return true;
		}

		// Check if current time is within range
		return $now >= $from_timestamp && $now <= $to_timestamp;
	}

}
