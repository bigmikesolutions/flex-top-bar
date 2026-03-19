<?php
/**
 * Top Bar options: ordered array of bars in DB (serialized by WordPress).
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Options {

	public const OPTION_BARS = 'top_bars';

	/** At least one bar configuration must exist. */
	public const MIN_BARS = 1;

	/** Maximum number of bars (order preserved in array). */
	public const MAX_BARS = 5;

	public static function new_bar_id(): string {
		return 'bar_' . wp_generate_password( 8, false, false );
	}

	/** @return array<string, mixed> */
	public static function default_bar(): array {
		return [
			'id'             => self::new_bar_id(),
			'name'           => __( 'Top Bar', 'top-bar' ),
			'enabled'        => true,
			'status'         => 'on',
			'position'       => 'top',
			'message'        => __( 'Welcome!', 'top-bar' ),
			'bg_color'       => '#1d2327',
			'frame_color'    => '',
			'frame_width'    => 1,
			'hide_on_scroll' => false,
		];
	}

	/**
	 * Bars from DB (migrates legacy flat options once). Count between MIN_BARS and MAX_BARS.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function get_bars(): array {
		$stored = get_option( self::OPTION_BARS );
		if ( false === $stored ) {
			self::maybe_migrate_legacy();
			$stored = get_option( self::OPTION_BARS );
		}
		if ( ! is_array( $stored ) || $stored === [] ) {
			return [ self::default_bar() ];
		}
		$out = [];
		foreach ( $stored as $row ) {
			if ( is_array( $row ) ) {
				$out[] = self::normalize_bar( $row );
			}
		}
		if ( $out === [] ) {
			return [ self::default_bar() ];
		}
		$out = array_slice( $out, 0, self::MAX_BARS );
		return $out;
	}

	private static function maybe_migrate_legacy(): void {
		if ( get_option( self::OPTION_BARS ) !== false ) {
			return;
		}
		$bar                    = self::default_bar();
		$legacy_pos             = get_option( 'top_bar_position', 'top' );
		$bar['position']        = in_array( $legacy_pos, [ 'top', 'bottom' ], true ) ? $legacy_pos : 'top';
		$bar['message']         = (string) get_option( 'top_bar_message', $bar['message'] );
		$bar['bg_color']        = self::sanitize_hex_color( (string) get_option( 'top_bar_bg_color', '#1d2327' ) ) ?: '#1d2327';
		$bar['frame_color']     = self::sanitize_hex_color( (string) get_option( 'top_bar_frame_color', '' ) ) ?: '';
		$bar['frame_width']     = 1;
		$bar['hide_on_scroll']  = get_option( 'top_bar_hide_on_scroll', '0' ) === '1';
		$legacy_status          = get_option( 'top_bar_status', 'on' );
		$bar['status']          = in_array( $legacy_status, [ 'on', 'off' ], true ) ? $legacy_status : 'on';
		$legacy_name            = get_option( 'top_bar_name', '' );
		$bar['name']            = is_string( $legacy_name ) && $legacy_name !== '' ? $legacy_name : $bar['name'];
		update_option( self::OPTION_BARS, [ $bar ] );
	}

	/**
	 * @param array<string, mixed> $bar
	 * @return array<string, mixed>
	 */
	public static function normalize_bar( array $bar ): array {
		$defaults = self::default_bar();
		$id       = isset( $bar['id'] ) && is_string( $bar['id'] ) && $bar['id'] !== '' ? $bar['id'] : self::new_bar_id();
		$pos      = isset( $bar['position'] ) && in_array( $bar['position'], [ 'top', 'bottom' ], true ) ? $bar['position'] : 'top';
		$status   = isset( $bar['status'] ) && in_array( $bar['status'], [ 'on', 'off' ], true ) ? $bar['status'] : 'on';
		$msg      = isset( $bar['message'] ) && is_string( $bar['message'] ) ? $bar['message'] : $defaults['message'];
		$bg       = isset( $bar['bg_color'] ) ? self::sanitize_hex_color( (string) $bar['bg_color'] ) : '';
		$frame    = isset( $bar['frame_color'] ) ? self::sanitize_hex_color( (string) $bar['frame_color'] ) : '';
		$width    = isset( $bar['frame_width'] ) ? (int) $bar['frame_width'] : 1;
		if ( $width < 0 ) {
			$width = 0;
		}
		if ( $width > 10 ) {
			$width = 10;
		}

		return [
			'id'             => sanitize_key( (string) $id ) ?: (string) $id,
			'name'           => isset( $bar['name'] ) ? sanitize_text_field( (string) $bar['name'] ) : $defaults['name'],
			'enabled'        => ! empty( $bar['enabled'] ),
			'status'         => $status,
			'position'       => $pos,
			'message'        => wp_kses_post( $msg ),
			'bg_color'       => $bg ?: '#1d2327',
			'frame_color'    => $frame,
			'frame_width'    => $width,
			'hide_on_scroll' => ! empty( $bar['hide_on_scroll'] ),
		];
	}

	/**
	 * @param mixed $bars
	 * @return list<array<string, mixed>>
	 */
	public static function sanitize_bars_input( $bars ): array {
		if ( ! is_array( $bars ) ) {
			return [ self::default_bar() ];
		}
		$out = [];
		foreach ( $bars as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$width = isset( $row['frame_width'] ) ? (int) $row['frame_width'] : 0;
			if ( $width <= 0 ) {
				$row['frame_color'] = '';
				$row['frame_width'] = 0;
			}
			$row['enabled']        = ! empty( $row['enabled'] );
			$row['hide_on_scroll'] = ! empty( $row['hide_on_scroll'] );
			$out[]                   = self::normalize_bar( $row );
		}
		if ( $out === [] ) {
			return [ self::default_bar() ];
		}
		$out = array_slice( $out, 0, self::MAX_BARS );
		if ( count( $out ) < self::MIN_BARS ) {
			while ( count( $out ) < self::MIN_BARS ) {
				$out[] = self::default_bar();
			}
		}
		return array_values( $out );
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function get_active_bars(): array {
		return array_values(
			array_filter(
				self::get_bars(),
				static function ( $bar ) {
					return is_array( $bar )
						&& ! empty( $bar['enabled'] )
						&& ( ! isset( $bar['status'] ) || $bar['status'] !== 'off' );
				}
			)
		);
	}

	// --- Back-compat: first bar (admin/legacy UI) ---

	public static function get_position(): string {
		$bars = self::get_bars();
		$pos  = isset( $bars[0]['position'] ) ? (string) $bars[0]['position'] : 'top';
		return in_array( $pos, [ 'top', 'bottom' ], true ) ? $pos : 'top';
	}

	public static function get_message(): string {
		$bars = self::get_bars();
		return isset( $bars[0]['message'] ) ? (string) $bars[0]['message'] : '';
	}

	public static function get_bg_color(): string {
		$bars = self::get_bars();
		$bg   = isset( $bars[0]['bg_color'] ) ? (string) $bars[0]['bg_color'] : '#1d2327';
		return self::sanitize_hex_color( $bg ) ?: '#1d2327';
	}

	public static function get_frame_color(): string {
		$bars = self::get_bars();
		$c    = isset( $bars[0]['frame_color'] ) ? (string) $bars[0]['frame_color'] : '';
		return self::sanitize_hex_color( $c ) ?: 'transparent';
	}

	public static function get_hide_on_scroll(): bool {
		$bars = self::get_bars();
		return ! empty( $bars[0]['hide_on_scroll'] );
	}

	public static function get_status(): string {
		$bars   = self::get_bars();
		$status = isset( $bars[0]['status'] ) ? (string) $bars[0]['status'] : 'on';
		return in_array( $status, [ 'on', 'off' ], true ) ? $status : 'on';
	}

	public static function sanitize_hex_color( string $color ): string {
		$color = ltrim( $color, '#' );
		if ( preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
			return '#' . $color;
		}
		return '';
	}
}
