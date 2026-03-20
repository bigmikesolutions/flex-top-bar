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

require_once dirname( __DIR__ ) . '/includes/class-options.php';

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
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $name, $value ): bool {
		return true;
	}
}
