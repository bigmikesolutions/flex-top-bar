<?php
/**
 * PHPUnit bootstrap for Top Bar plugin tests.
 *
 * @package TopBar
 */

$autoload = dirname( __DIR__, 3 ) . '/vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	throw new RuntimeException( 'Run composer install from the project root.' );
}
require_once $autoload;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

require_once dirname( __DIR__ ) . '/includes/features/class-feature-flags.php';
require_once dirname( __DIR__ ) . '/includes/class-options.php';
require_once dirname( __DIR__ ) . '/includes/class-frontend.php';

// Minimal WordPress function shims for unit tests.
if ( ! function_exists( '__' ) ) {
	function __( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $text ): string {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( string $text ): string {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		$url = trim( $url );
		if ( $url === '' ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}
		if ( str_starts_with( $url, 'mailto:' ) || str_starts_with( $url, 'tel:' ) ) {
			return $url;
		}
		return '';
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) ) ?? '';
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( int $length = 8 ): string {
		return str_repeat( 'a', max( 1, $length ) );
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone(): DateTimeZone {
		return new DateTimeZone( 'UTC' );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['wp_test_options'] ?? [] ) ? $GLOBALS['wp_test_options'][ $name ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $name, $value ): bool {
		$GLOBALS['wp_test_options'][ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10 ): void {
		$GLOBALS['wp_test_actions'][] = [ 'hook' => $hook, 'callback' => $callback, 'priority' => $priority ];
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$GLOBALS['wp_test_filters'][] = [ 'hook' => $hook, 'callback' => $callback, 'priority' => $priority ];
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value ) {
		if ( isset( $GLOBALS['wp_test_filters'][ $hook ] ) && is_callable( $GLOBALS['wp_test_filters'][ $hook ] ) ) {
			return $GLOBALS['wp_test_filters'][ $hook ]( $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( string $class ): string {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', $class ) ?? '';
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ): string {
		return (string) json_encode( $value );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( string $file ): string {
		return 'http://example.test/plugins/flex-top-bar/';
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( string $path = '', string $plugin = '' ): string {
		return 'http://example.test/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( string $handle, string $src = '', array $deps = [], $ver = false ): void {
		$GLOBALS['wp_test_enqueued_styles'][] = compact( 'handle', 'src', 'deps', 'ver' );
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src = '', array $deps = [], $ver = false, bool $in_footer = false ): void {
		$GLOBALS['wp_test_enqueued_scripts'][] = compact( 'handle', 'src', 'deps', 'ver', 'in_footer' );
	}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style( string $handle, string $data ): void {
		$GLOBALS['wp_test_inline_styles'][] = compact( 'handle', 'data' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $text ): string {
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin(): bool {
		return false;
	}
}

if ( ! function_exists( 'add_image_size' ) ) {
	function add_image_size( string $name, int $width = 0, int $height = 0, $crop = false ): void {
		$GLOBALS['wp_test_image_sizes'][] = compact( 'name', 'width', 'height', 'crop' );
	}
}

if ( ! class_exists( 'WP_Post', false ) ) {
	class WP_Post {
		public int $ID = 0;
		public string $post_type = '';
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * @param int|object $post
	 * @return object|null
	 */
	function get_post( $post = null ) {
		$id = is_object( $post ) ? (int) $post->ID : (int) $post;
		return $GLOBALS['wp_test_posts'][ $id ] ?? null;
	}
}

if ( ! function_exists( 'get_post_mime_type' ) ) {
	function get_post_mime_type( $post ): string {
		$id   = is_object( $post ) ? (int) $post->ID : (int) $post;
		$data = $GLOBALS['wp_test_attachments'][ $id ] ?? [];
		return (string) ( $data['mime'] ?? '' );
	}
}

if ( ! function_exists( 'get_attached_file' ) ) {
	function get_attached_file( int $attachment_id ): string {
		return (string) ( $GLOBALS['wp_test_attachments'][ $attachment_id ]['file'] ?? '' );
	}
}

if ( ! function_exists( 'wp_get_attachment_url' ) ) {
	function wp_get_attachment_url( int $attachment_id ): string {
		return (string) ( $GLOBALS['wp_test_attachments'][ $attachment_id ]['full_url'] ?? '' );
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( int $attachment_id, $size = 'thumbnail' ): string {
		$data = $GLOBALS['wp_test_attachments'][ $attachment_id ] ?? [];
		if ( (string) $size === \FlexTopBar\Options::ICON_COLUMN_IMAGE_SIZE ) {
			return (string) ( $data['sized_url'] ?? '' );
		}
		return '';
	}
}

if ( ! function_exists( 'wp_get_attachment_metadata' ) ) {
	function wp_get_attachment_metadata( int $attachment_id ): array {
		return is_array( $GLOBALS['wp_test_attachments'][ $attachment_id ]['metadata'] ?? null )
			? $GLOBALS['wp_test_attachments'][ $attachment_id ]['metadata']
			: [];
	}
}

if ( ! function_exists( 'wp_update_attachment_metadata' ) ) {
	function wp_update_attachment_metadata( int $attachment_id, array $data ): bool {
		$GLOBALS['wp_test_attachments'][ $attachment_id ]['metadata'] = $data;
		if ( isset( $data['sizes'][ \FlexTopBar\Options::ICON_COLUMN_IMAGE_SIZE ]['file'] ) ) {
			$existing = (string) ( $GLOBALS['wp_test_attachments'][ $attachment_id ]['sized_url'] ?? '' );
			if ( $existing === '' ) {
				$GLOBALS['wp_test_attachments'][ $attachment_id ]['sized_url'] =
					'http://example.test/uploads/icon-64x64.jpg';
			}
		}
		return true;
	}
}

if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
	function wp_generate_attachment_metadata( int $attachment_id, string $file ): array {
		unset( $file );
		$meta = $GLOBALS['wp_test_attachments'][ $attachment_id ]['generated_metadata'] ?? [
			'width'  => 64,
			'height' => 64,
			'sizes'  => [
				\FlexTopBar\Options::ICON_COLUMN_IMAGE_SIZE => [
					'file' => 'icon-64x64.jpg',
				],
			],
		];
		return is_array( $meta ) ? $meta : [];
	}
}

if ( ! defined( 'FLEX_TOP_BAR_PLUGIN_FILE' ) ) {
	define( 'FLEX_TOP_BAR_PLUGIN_FILE', dirname( __DIR__ ) . '/flex-top-bar.php' );
}

if ( ! defined( 'FLEX_TOP_BAR_PLUGIN_DIR' ) ) {
	define( 'FLEX_TOP_BAR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'FLEX_TOP_BAR_VERSION' ) ) {
	define( 'FLEX_TOP_BAR_VERSION', 'test' );
}

// Legacy TOP_BAR_* constants intentionally not defined to avoid collisions.
