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
		foreach ( $bars as $bar ) {
			$this->render_single_bar( $bar );
		}
	}

	/**
	 * @param array<string, mixed> $bar
	 */
	private function render_single_bar( array $bar ): void {
		$raw_id         = isset( $bar['id'] ) ? (string) $bar['id'] : 'default';
		$html_id        = 'top-bar-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $raw_id );
		$position       = isset( $bar['position'] ) && $bar['position'] === 'bottom' ? 'bottom' : 'top';
		$message        = $this->message_for_render( $bar );
		$effect         = isset( $bar['effect'] ) ? sanitize_key( (string) $bar['effect'] ) : 'none';
		$effect_messages = $this->messages_for_effect( $bar );
		$classes        = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
		$hide_on_scroll = $this->bar_hides_on_scroll( $bar );
		?>
		<div id="<?php echo esc_attr( $html_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" role="banner" data-top-bar-id="<?php echo esc_attr( $raw_id ); ?>" data-top-bar-position="<?php echo esc_attr( $position ); ?>" data-top-bar-effect="<?php echo esc_attr( $effect ); ?>" data-top-bar-effect-messages="<?php echo esc_attr( wp_json_encode( $effect_messages ) ); ?>"<?php echo $hide_on_scroll ? ' data-top-bar-scroll-hide="1" data-top-bar-hide-threshold="30"' : ''; ?>>
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
		$chunks = [];
		foreach ( $bars as $bar ) {
			$raw_id         = isset( $bar['id'] ) ? (string) $bar['id'] : 'default';
			$html_id        = 'top-bar-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $raw_id );
			$position       = isset( $bar['position'] ) && $bar['position'] === 'bottom' ? 'bottom' : 'top';
			$message        = $this->message_for_render( $bar );
			$effect         = isset( $bar['effect'] ) ? sanitize_key( (string) $bar['effect'] ) : 'none';
			$effect_messages = $this->messages_for_effect( $bar );
			$classes        = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
			$hide_on_scroll = $this->bar_hides_on_scroll( $bar );
			$chunks[]       = '<div id="' . esc_attr( $html_id ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" role="banner" data-top-bar-id="' . esc_attr( $raw_id ) . '" data-top-bar-position="' . esc_attr( $position ) . '" data-top-bar-effect="' . esc_attr( $effect ) . '" data-top-bar-effect-messages="' . esc_attr( wp_json_encode( $effect_messages ) ) . '"' . ( $hide_on_scroll ? ' data-top-bar-scroll-hide="1" data-top-bar-hide-threshold="30"' : '' ) . '><div class="top-bar__inner">' . wp_kses_post( $message ) . '</div></div>';
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
		$needs_scroll_hide = false;
		foreach ( $bars as $bar ) {
			if ( $this->bar_hides_on_scroll( $bar ) ) {
				$needs_scroll_hide = true;
				break;
			}
		}
		if ( $needs_scroll_hide ) {
			// Distinct handle from stylesheet `top-bar` (avoids conflicts with optimizers / dequeues).
			wp_enqueue_script(
				'top-bar-scroll-hide',
				plugin_dir_url( TOP_BAR_PLUGIN_FILE ) . 'assets/js/top-bar.js',
				[],
				TOP_BAR_VERSION,
				true
			);
		}
		$needs_effect_rotation = false;
		foreach ( $bars as $bar ) {
			$effect = isset( $bar['effect'] ) ? sanitize_key( (string) $bar['effect'] ) : 'none';
			$messages = $this->messages_for_effect( $bar );
			if ( in_array( $effect, [ 'slider', 'fadein', 'blink' ], true ) && count( $messages ) > 1 ) {
				$needs_effect_rotation = true;
				break;
			}
		}
		if ( $needs_effect_rotation ) {
			wp_enqueue_script(
				'top-bar-effects',
				plugin_dir_url( TOP_BAR_PLUGIN_FILE ) . 'assets/js/top-bar-effects.js',
				[],
				TOP_BAR_VERSION,
				true
			);
		}
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
			$width   = isset( $bar['frame_width'] ) ? (int) $bar['frame_width'] : 1;
			if ( $width < 0 ) {
				$width = 0;
			}
			if ( $width > 10 ) {
				$width = 10;
			}
			if ( $frame !== '' && $width > 0 ) {
				$rules[] = $sel . ' { border: ' . $width . 'px solid ' . esc_attr( $frame ) . '; }';
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

	/**
	 * @param array<string, mixed> $bar
	 */
	private function bar_hides_on_scroll( array $bar ): bool {
		return ! empty( $bar['hide_on_scroll'] );
	}

	/**
	 * @param array<string, mixed> $bar
	 */
	private function message_for_render( array $bar ): string {
		$effect = isset( $bar['effect'] ) ? sanitize_key( (string) $bar['effect'] ) : 'none';
		$messages = [];
		if ( isset( $bar['messages'] ) && is_array( $bar['messages'] ) ) {
			foreach ( $bar['messages'] as $item ) {
				if ( is_string( $item ) ) {
					$item = trim( $item );
					if ( $item !== '' ) {
						$messages[] = $item;
					}
				}
			}
		}
		if ( $messages === [] ) {
			return '';
		}
		if ( $effect === 'none' ) {
			$single_line_messages = [];
			foreach ( $messages as $item ) {
				$plain = wp_strip_all_tags( $item );
				$plain = preg_replace( '/\s+/', ' ', (string) $plain );
				$plain = trim( (string) $plain );
				if ( $plain !== '' ) {
					$single_line_messages[] = $plain;
				}
			}
			return implode( ' ', $single_line_messages );
		}
		return $messages[0];
	}

	/**
	 * @param array<string, mixed> $bar
	 * @return list<string>
	 */
	private function messages_for_effect( array $bar ): array {
		$messages = [];
		if ( isset( $bar['messages'] ) && is_array( $bar['messages'] ) ) {
			foreach ( $bar['messages'] as $item ) {
				if ( is_string( $item ) ) {
					$item = trim( $item );
					if ( $item !== '' ) {
						$messages[] = $item;
					}
				}
			}
		}
		return array_values( $messages );
	}
}
