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
		add_action( 'wp_footer', [ $this, 'maybe_output_bar_fallback' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
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

	private function get_bg_color(): string {
		$color = get_option( 'top_bar_bg_color', '#1d2327' );
		return $this->sanitize_hex_color( $color ) ?: '#1d2327';
	}

	private function get_frame_color(): string {
		$color = get_option( 'top_bar_frame_color', '' );
		return $this->sanitize_hex_color( $color ) ?: 'transparent';
	}

	private function get_hide_on_scroll(): bool {
		return get_option( 'top_bar_hide_on_scroll', '0' ) === '1';
	}

	private function sanitize_hex_color( string $color ): string {
		$color = ltrim( $color, '#' );
		if ( preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
			return '#' . $color;
		}
		return '';
	}

	private function render_bar(): void {
		$position = $this->get_position();
		$message  = $this->get_message();
		$classes  = [ 'top-bar', 'top-bar--' . sanitize_html_class( $position ) ];
		$hide_on_scroll = $this->get_hide_on_scroll();
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
		$hide_on_scroll = $this->get_hide_on_scroll();
		$position       = $this->get_position();
		$message        = $this->get_message();
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
		$bg    = $this->get_bg_color();
		$frame = $this->get_frame_color();
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

	/** Admin settings */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Top Bar', 'top-bar' ),
			__( 'Top Bar', 'top-bar' ),
			'manage_options',
			'top-bar',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings(): void {
		register_setting( 'top_bar_settings', 'top_bar_position', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				return in_array( $v, [ 'top', 'bottom' ], true ) ? $v : 'top';
			},
		] );
		register_setting( 'top_bar_settings', 'top_bar_message', [
			'type'              => 'string',
			'sanitize_callback' => 'wp_kses_post',
		] );
		register_setting( 'top_bar_settings', 'top_bar_bg_color', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				$v = is_string( $v ) ? ltrim( $v, '#' ) : '';
				return preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $v ) ? '#' . $v : '#1d2327';
			},
		] );
		register_setting( 'top_bar_settings', 'top_bar_frame_color', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				if ( ! empty( $_POST['top_bar_frame_disable'] ) ) {
					return '';
				}
				if ( empty( $v ) || ! is_string( $v ) ) {
					return '';
				}
				$v = ltrim( $v, '#' );
				return preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $v ) ? '#' . $v : '';
			},
		] );
		register_setting( 'top_bar_settings', 'top_bar_hide_on_scroll', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				return ( $v === '1' || $v === 1 ) ? '1' : '0';
			},
		] );
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$position        = get_option( 'top_bar_position', 'top' );
		$message         = get_option( 'top_bar_message', __( 'Welcome!', 'top-bar' ) );
		$bg_color        = get_option( 'top_bar_bg_color', '#1d2327' );
		$frame_color     = get_option( 'top_bar_frame_color', '' );
		$hide_on_scroll  = get_option( 'top_bar_hide_on_scroll', '0' ) === '1';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'top_bar_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Position', 'top-bar' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Top', 'top-bar' ); ?></label><br />
								<label><input type="radio" name="top_bar_position" value="bottom" <?php checked( $position, 'bottom' ); ?> /> <?php esc_html_e( 'Bottom', 'top-bar' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="top_bar_message"><?php esc_html_e( 'Message', 'top-bar' ); ?></label></th>
						<td>
							<?php
							wp_editor( $message, 'top_bar_message', [
								'textarea_name' => 'top_bar_message',
								'textarea_rows' => 6,
								'media_buttons' => true,
								'teeny'         => false,
								'quicktags'     => true,
								'tinymce'       => [
									'toolbar1' => 'formatselect,bold,italic,link,unlink,bullist,numlist,blockquote,undo,redo',
								],
								'editor_css'    => '',
								'dfw'           => false,
							] );
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="top_bar_bg_color"><?php esc_html_e( 'Background colour', 'top-bar' ); ?></label></th>
						<td><input type="color" id="top_bar_bg_color" name="top_bar_bg_color" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="top_bar_frame_color"><?php esc_html_e( 'Frame (border) colour', 'top-bar' ); ?></label></th>
						<td>
							<input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" />
							<input type="checkbox" id="top_bar_frame_disable" name="top_bar_frame_disable" value="1" <?php checked( empty( $frame_color ) ); ?> />
							<label for="top_bar_frame_disable"><?php esc_html_e( 'No border', 'top-bar' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Scroll behaviour', 'top-bar' ); ?></th>
						<td>
							<input type="hidden" name="top_bar_hide_on_scroll" value="0" />
							<label>
								<input type="checkbox" name="top_bar_hide_on_scroll" value="1" <?php checked( $hide_on_scroll ); ?> />
								<?php esc_html_e( 'Hide bar when user scrolls down; show again when scrolling up', 'top-bar' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', fn() => Plugin::instance() );
