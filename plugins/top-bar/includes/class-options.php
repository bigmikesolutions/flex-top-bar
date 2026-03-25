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

	public static function new_bar_id(): string {
		return 'bar_' . wp_generate_password( 8, false, false );
	}

	/** @return array<string, mixed> */
	public static function default_bar(): array {
		return [
			'id'             => self::new_bar_id(),
			'name'           => __( 'Top Bar', 'top-bar' ),
			'visible'        => true,
			// Whether the bar's options details are expanded in the admin panel.
			'admin_visibile' => true,
			// Scheduling in admin panel.
			'scheduled_enabled'   => false,
			'scheduled_from_datetime' => '',
			'scheduled_to_datetime'   => '',
			'position'       => 'top',
			'effect'         => 'none',
			'messages'       => [ __( 'Welcome!', 'top-bar' ), '' ],
			'messages_mobile_visible' => true,
			'bg_color'       => '#1d2327',
			'frame_color'    => '',
			'frame_width'    => 0,
			'hide_on_scroll' => false,
		];
	}

	/**
	 * Bars from DB.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function get_bars(): array {
		$stored = get_option( self::OPTION_BARS );
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
		$out = array_slice( $out, 0, FeatureFlags::instance()->max_bars() );
		return $out;
	}

	/**
	 * @param array<string, mixed> $bar
	 * @return array<string, mixed>
	 */
	public static function normalize_bar( array $bar ): array {
		$defaults = self::default_bar();
		$id       = isset( $bar['id'] ) && is_string( $bar['id'] ) && $bar['id'] !== '' ? $bar['id'] : self::new_bar_id();
		$pos      = isset( $bar['position'] ) && in_array( $bar['position'], [ 'top', 'bottom' ], true ) ? $bar['position'] : 'top';

		$visible            = self::parse_bool( $bar['visible'] ?? true, true );
		$admin_visibile     = self::parse_bool( $bar['admin_visibile'] ?? $defaults['admin_visibile'], $defaults['admin_visibile'] );
		$scheduled_enabled  = self::parse_bool( $bar['scheduled_enabled'] ?? $defaults['scheduled_enabled'], $defaults['scheduled_enabled'] );
		$hide_on_scroll     = self::parse_bool( $bar['hide_on_scroll'] ?? false, false );
		$messages_mobile_visible = self::parse_bool( $bar['messages_mobile_visible'] ?? true, true );

		$scheduled_from_datetime = isset( $bar['scheduled_from_datetime'] )
			? self::sanitize_iso_datetime( sanitize_text_field( (string) $bar['scheduled_from_datetime'] ) )
			: '';
		$scheduled_to_datetime = isset( $bar['scheduled_to_datetime'] )
			? self::sanitize_iso_datetime( sanitize_text_field( (string) $bar['scheduled_to_datetime'] ) )
			: '';

		if ( $scheduled_from_datetime !== '' || $scheduled_to_datetime !== '' ) {
			$scheduled_enabled = true;
		}

		if ( ! $scheduled_enabled ) {
			$scheduled_from_datetime = '';
			$scheduled_to_datetime   = '';
		}

		$effect = isset( $bar['effect'] ) ? sanitize_key( (string) $bar['effect'] ) : 'none';
		if ( ! in_array( $effect, [ 'none', 'slider', 'fadein', 'blink' ], true ) ) {
			$effect = 'none';
		}

		$default_message = isset( $defaults['messages'][0] ) && is_string( $defaults['messages'][0] ) ? $defaults['messages'][0] : __( 'Welcome!', 'top-bar' );
		$messages = [];
		if ( isset( $bar['messages'] ) && is_array( $bar['messages'] ) ) {
			foreach ( $bar['messages'] as $item ) {
				if ( is_string( $item ) ) {
					$messages[] = wp_kses_post( $item );
				}
			}
		}
		if ( $messages === [] ) {
			$messages = [ wp_kses_post( $default_message ), '' ];
		}
		if ( ! isset( $messages[0] ) || $messages[0] === '' ) {
			$messages[0] = wp_kses_post( $default_message );
		}
		$messages = array_values( array_slice( $messages, 0, FeatureFlags::instance()->max_messages() ) );

		$bg    = isset( $bar['bg_color'] ) ? self::sanitize_hex_color( (string) $bar['bg_color'] ) : '';
		$width = isset( $bar['frame_width'] ) ? (int) $bar['frame_width'] : 0;
		$width = max( 0, min( 10, $width ) );
		$frame = $width > 0 && isset( $bar['frame_color'] ) ? self::sanitize_hex_color( (string) $bar['frame_color'] ) : '';

		// Clear frame if width is 0
		if ( $width === 0 ) {
			$frame = '';
		}

		return [
			'id'             => sanitize_key( (string) $id ) ?: (string) $id,
			'name'           => isset( $bar['name'] ) ? sanitize_text_field( (string) $bar['name'] ) : $defaults['name'],
			'visible'        => $visible,
			'admin_visibile' => $admin_visibile,
			'scheduled_enabled'   => $scheduled_enabled,
			'scheduled_from_datetime' => $scheduled_from_datetime,
			'scheduled_to_datetime'   => $scheduled_to_datetime,
			'position'       => $pos,
			'effect'         => $effect,
			'messages'       => $messages,
			'messages_mobile_visible' => $messages_mobile_visible,
			'bg_color'       => $bg ?: '#1d2327',
			'frame_color'    => $frame,
			'frame_width'    => $width,
			'hide_on_scroll' => $hide_on_scroll,
		];
	}

	/**
	 * @param mixed $value
	 * @param bool  $default
	 * @return bool
	 */
	private static function parse_bool( $value, bool $default ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			$raw = strtolower( trim( $value ) );
			return $raw === 'true' || $raw === '1';
		}
		if ( is_int( $value ) ) {
			return $value === 1;
		}
		return $default;
	}

	/**
	 * Get visible bars (scheduling is handled client-side by Vue).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function get_active_bars(): array {
		return array_values(
			array_filter(
				self::get_bars(),
				static fn( $bar ) => is_array( $bar ) && ( $bar['visible'] ?? false )
			)
		);
	}

	public static function sanitize_hex_color( string $color ): string {
		$color = ltrim( $color, '#' );
		if ( preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
			return '#' . $color;
		}
		return '';
	}

	/**
	 * @return string ISO datetime `YYYY-MM-DDTHH:MM` or empty string.
	 */
	private static function sanitize_iso_datetime( string $value ): string {
		$value = trim( $value );
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})T((?:[01]\d|2[0-3]):[0-5]\d)$/', $value, $m ) === 1 ) {
			return $m[1] . 'T' . $m[2];
		}
		// Accept seconds and normalize to minute precision.
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})T((?:[01]\d|2[0-3]):[0-5]\d):[0-5]\d$/', $value, $m ) === 1 ) {
			return $m[1] . 'T' . $m[2];
		}
		return '';
	}
}
