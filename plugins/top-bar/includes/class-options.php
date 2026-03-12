<?php
/**
 * Top Bar options and sanitization.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Options {

	public static function get_position(): string {
		return get_option( 'top_bar_position', 'top' );
	}

	public static function get_message(): string {
		return get_option( 'top_bar_message', __( 'Welcome!', 'top-bar' ) );
	}

	public static function get_bg_color(): string {
		$color = get_option( 'top_bar_bg_color', '#1d2327' );
		return self::sanitize_hex_color( $color ) ?: '#1d2327';
	}

	public static function get_frame_color(): string {
		$color = get_option( 'top_bar_frame_color', '' );
		return self::sanitize_hex_color( $color ) ?: 'transparent';
	}

	public static function get_hide_on_scroll(): bool {
		return get_option( 'top_bar_hide_on_scroll', '0' ) === '1';
	}

	public static function sanitize_hex_color( string $color ): string {
		$color = ltrim( $color, '#' );
		if ( preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
			return '#' . $color;
		}
		return '';
	}
}
