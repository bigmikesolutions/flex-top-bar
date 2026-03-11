<?php
/**
 * Plugin Name: Top Bar
 * Description: Displays a simple info bar at the top or bottom of the menu.
 * Version:     1.0.0
 * Author:      Big Mike Solutions
 * License:     GPL-2.0-or-later
 * Text Domain: top-bar
 */

declare(strict_types=1);

namespace TopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TOP_BAR_VERSION', '1.0.0' );
define( 'TOP_BAR_PLUGIN_FILE', __FILE__ );
define( 'TOP_BAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

final class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_body_open', [ $this, 'maybe_render_bar' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function maybe_render_bar(): void {
		if ( ! $this->should_show_bar() ) {
			return;
		}
		$this->render_bar();
	}

	private function should_show_bar(): bool {
		return apply_filters( 'top_bar_show', true );
	}

	private function get_position(): string {
		return get_option( 'top_bar_position', 'top' );
	}

	private function get_message(): string {
		return get_option( 'top_bar_message', __( 'Welcome!', 'top-bar' ) );
	}

	private function render_bar(): void {
		$position = $this->get_position();
		$message  = $this->get_message();
		$classes  = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" role="banner">
			<div class="top-bar__inner">
				<?php echo esc_html( $message ); ?>
			</div>
		</div>
		<?php
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
	}
}

add_action( 'plugins_loaded', fn() => Plugin::instance() );
