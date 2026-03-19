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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	private function should_show_bar(): bool {
		$has_active = Options::get_active_bars() !== [];
		return (bool) apply_filters( 'top_bar_show', $has_active );
	}

	public function maybe_render_bar(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		$this->render_bars();
	}

	private function render_bars(): void {
		$bars = Options::get_active_bars();
		if ( $bars === [] ) {
			return;
		}
		$any_hide = false;
		foreach ( $bars as $bar ) {
			$this->render_single_bar( $bar );
			if ( ! empty( $bar['hide_on_scroll'] ) ) {
				$any_hide = true;
			}
		}
		if ( $any_hide ) {
			$this->print_hide_on_scroll_script_inline();
		}
	}

	/**
	 * @param array<string, mixed> $bar
	 */
	private function render_single_bar( array $bar ): void {
		$raw_id         = isset( $bar['id'] ) ? (string) $bar['id'] : 'default';
		$html_id        = 'top-bar-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $raw_id );
		$position       = isset( $bar['position'] ) && $bar['position'] === 'bottom' ? 'bottom' : 'top';
		$message        = isset( $bar['message'] ) ? (string) $bar['message'] : '';
		$classes        = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
		$hide_on_scroll = ! empty( $bar['hide_on_scroll'] );
		if ( $hide_on_scroll ) {
			$classes[] = 'top-bar--hide-on-scroll';
		}
		?>
		<div id="<?php echo esc_attr( $html_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" role="banner" data-top-bar-id="<?php echo esc_attr( $raw_id ); ?>" data-top-bar-position="<?php echo esc_attr( $position ); ?>"<?php echo $hide_on_scroll ? ' data-top-bar-hide-threshold="20"' : ''; ?>>
			<div class="top-bar__inner">
				<?php echo wp_kses_post( $message ); ?>
			</div>
		</div>
		<?php
	}

	/** Fallback when theme does not call wp_body_open: inject bar at start of body and run hide script. */
	public function maybe_output_bar_fallback(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		$bars = Options::get_active_bars();
		if ( $bars === [] ) {
			return;
		}
		$chunks   = [];
		$any_hide = false;
		foreach ( $bars as $bar ) {
			$raw_id         = isset( $bar['id'] ) ? (string) $bar['id'] : 'default';
			$html_id        = 'top-bar-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $raw_id );
			$position       = isset( $bar['position'] ) && $bar['position'] === 'bottom' ? 'bottom' : 'top';
			$message        = isset( $bar['message'] ) ? (string) $bar['message'] : '';
			$classes        = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
			$hide_on_scroll = ! empty( $bar['hide_on_scroll'] );
			if ( $hide_on_scroll ) {
				$classes[] = 'top-bar--hide-on-scroll';
				$any_hide  = true;
			}
			$chunks[] = '<div id="' . esc_attr( $html_id ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" role="banner" data-top-bar-id="' . esc_attr( $raw_id ) . '" data-top-bar-position="' . esc_attr( $position ) . '"' . ( $hide_on_scroll ? ' data-top-bar-hide-threshold="20"' : '' ) . '><div class="top-bar__inner">' . wp_kses_post( $message ) . '</div></div>';
		}
		$bar_html = implode( '', $chunks );
		?>
		<script id="top-bar-fallback">
		(function(){
			if(document.querySelector('.top-bar')) return;
			document.body.insertAdjacentHTML('afterbegin', <?php echo wp_json_encode( $bar_html ); ?>);
		})();
		</script>
		<?php
		if ( $any_hide ) {
			$this->print_hide_on_scroll_script_inline();
		}
	}

	public function enqueue_assets(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		$bars = Options::get_active_bars();
		if ( $bars === [] ) {
			return;
		}
		wp_enqueue_style(
			'top-bar',
			plugin_dir_url( TOP_BAR_PLUGIN_FILE ) . 'assets/css/top-bar.css',
			[],
			TOP_BAR_VERSION
		);
		$rules = [];
		foreach ( $bars as $bar ) {
			$raw_id  = isset( $bar['id'] ) ? (string) $bar['id'] : 'default';
			$html_id = 'top-bar-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $raw_id );
			$sel     = '#' . $html_id;
			$bg      = isset( $bar['bg_color'] ) ? Options::sanitize_hex_color( (string) $bar['bg_color'] ) : '';
			if ( ! $bg ) {
				$bg = '#1d2327';
			}
			$rules[] = $sel . ' { background-color: ' . esc_attr( $bg ) . '; }';
			$frame   = isset( $bar['frame_color'] ) ? Options::sanitize_hex_color( (string) $bar['frame_color'] ) : '';
			if ( $frame !== '' ) {
				$rules[] = $sel . ' { border: 1px solid ' . esc_attr( $frame ) . '; }';
			}
		}
		wp_add_inline_style( 'top-bar', implode( ' ', $rules ) );
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

	private function print_hide_on_scroll_script_inline(): void {
		?>
		<script id="top-bar-hide-on-scroll">(function(){
			var t=30;
			function bars(){ return document.querySelectorAll('.top-bar--hide-on-scroll'); }
			function scrollY(){ return window.pageYOffset||document.documentElement.scrollTop; }
			function update(){
				var list=bars();
				var y=scrollY()>t;
				for(var i=0;i<list.length;i++){
					list[i].style.display=y?'none':'';
				}
			}
			function go(){
				update();
				window.addEventListener('scroll',function(){ update(); },{passive:1});
			}
			if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',go);
			else go();
		})();</script>
		<?php
	}
}
