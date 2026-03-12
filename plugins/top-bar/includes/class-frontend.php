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
		return apply_filters( 'top_bar_show', true );
	}

	public function maybe_render_bar(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		$this->render_bar();
	}

	private function render_bar(): void {
		$position       = Options::get_position();
		$message        = Options::get_message();
		$classes        = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
		$hide_on_scroll = Options::get_hide_on_scroll();
		if ( $hide_on_scroll ) {
			$classes[] = 'top-bar--hide-on-scroll';
		}
		?>
		<div id="top-bar" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" role="banner"<?php echo $hide_on_scroll ? ' data-top-bar-hide-threshold="20"' : ''; ?>>
			<div class="top-bar__inner">
				<?php echo wp_kses_post( $message ); ?>
			</div>
		</div>
		<?php
		if ( $hide_on_scroll ) {
			$this->print_hide_on_scroll_script_inline();
		}
	}

	/** Fallback when theme does not call wp_body_open: inject bar at start of body and run hide script. */
	public function maybe_output_bar_fallback(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		$hide_on_scroll = Options::get_hide_on_scroll();
		$position       = Options::get_position();
		$message        = Options::get_message();
		$classes        = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
		if ( $hide_on_scroll ) {
			$classes[] = 'top-bar--hide-on-scroll';
		}
		$bar_html = '<div id="top-bar" class="' . esc_attr( implode( ' ', $classes ) ) . '" role="banner"' . ( $hide_on_scroll ? ' data-top-bar-hide-threshold="20"' : '' ) . '><div class="top-bar__inner">' . wp_kses_post( $message ) . '</div></div>';
		?>
		<script id="top-bar-fallback">
		(function(){
			if(document.getElementById('top-bar')) return;
			document.body.insertAdjacentHTML('afterbegin', <?php echo wp_json_encode( $bar_html ); ?>);
		})();
		</script>
		<?php
		if ( $hide_on_scroll ) {
			$this->print_hide_on_scroll_script_inline();
		}
	}

	public function enqueue_assets(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		wp_enqueue_style(
			'top-bar',
			plugin_dir_url( TOP_BAR_PLUGIN_FILE ) . 'assets/css/top-bar.css',
			[],
			TOP_BAR_VERSION
		);
		$bg     = Options::get_bg_color();
		$frame  = Options::get_frame_color();
		$inline = '.top-bar { background-color: ' . esc_attr( $bg ) . '; }';
		if ( $frame && $frame !== 'transparent' ) {
			$inline .= ' .top-bar { border: 1px solid ' . esc_attr( $frame ) . '; }';
		}
		wp_add_inline_style( 'top-bar', $inline );
	}

	private function print_hide_on_scroll_script_inline(): void {
		?>
		<script id="top-bar-hide-on-scroll">(function(){
			var t=30;
			function find(){ return document.getElementById('top-bar'); }
			function scrollY(){ return window.pageYOffset||document.documentElement.scrollTop; }
			function update(){
				var bar=find();
				if(!bar) return;
				bar.style.display=scrollY()>t?'none':'';
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
