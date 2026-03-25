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
	public const MAX_BARS = 1;

	/**
	 * Maximum number of bars allowed.
	 *
	 * If Freemius plan exposes the `FF_MAX_BARS` constant, use it. Otherwise fall back to MAX_BARS.
	 */
	public static function max_bars(): int {
		$max = self::MAX_BARS;
		if ( defined( 'FF_MAX_BARS' ) ) {
			$raw = constant( 'FF_MAX_BARS' );
			if ( is_numeric( $raw ) ) {
				$max = (int) $raw;
			}
		}
		if ( $max < self::MIN_BARS ) {
			$max = self::MIN_BARS;
		}
		return $max;
	}

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
		$out = array_slice( $out, 0, self::max_bars() );
		return $out;
	}

	private static function maybe_migrate_legacy(): void {
		if ( get_option( self::OPTION_BARS ) !== false ) {
			return;
		}
		update_option( self::OPTION_BARS, [ self::default_bar() ] );
	}

	/**
	 * @param array<string, mixed> $bar
	 * @return array<string, mixed>
	 */
	public static function normalize_bar( array $bar ): array {
		$defaults = self::default_bar();
		$id       = isset( $bar['id'] ) && is_string( $bar['id'] ) && $bar['id'] !== '' ? $bar['id'] : self::new_bar_id();
		$pos      = isset( $bar['position'] ) && in_array( $bar['position'], [ 'top', 'bottom' ], true ) ? $bar['position'] : 'top';
		// Visibility on the site: controlled by `visible` (true/false).
		$visible = true;
		if ( array_key_exists( 'visible', $bar ) ) {
			$v = $bar['visible'];
			if ( is_bool( $v ) ) {
				$visible = $v;
			} else {
				$raw = is_string( $v ) ? strtolower( trim( (string) $v ) ) : '';
				$visible = $raw === 'true' || $raw === '1' || $v === 1;
			}
		} elseif ( array_key_exists( 'status', $bar ) ) {
			// Legacy: `status` used `on/off` strings.
			$raw_status = is_string( $bar['status'] ) ? strtolower( trim( (string) $bar['status'] ) ) : '';
			$visible    = $raw_status === 'on';
		}

		$admin_visibile = $defaults['admin_visibile'];
		if ( array_key_exists( 'admin_visibile', $bar ) ) {
			$av = $bar['admin_visibile'];
			if ( is_bool( $av ) ) {
				$admin_visibile = $av;
			} else {
				$raw = is_string( $av ) ? strtolower( trim( (string) $av ) ) : '';
				$admin_visibile = $raw === 'true' || $raw === '1' || $av === 1;
			}
		}

		// Scheduling: read admin inputs (may be date+time or already combined datetime).
		$scheduled_enabled = $defaults['scheduled_enabled'];
		if ( array_key_exists( 'scheduled_enabled', $bar ) ) {
			$se = $bar['scheduled_enabled'];
			if ( is_bool( $se ) ) {
				$scheduled_enabled = $se;
			} else {
				$raw = is_string( $se ) ? strtolower( trim( (string) $se ) ) : '';
				$scheduled_enabled = $raw === 'true' || $raw === '1' || $se === 1;
			}
		} elseif ( array_key_exists( 'life_time_enabled', $bar ) ) {
			// Back-compat for the earlier key name.
			$se = $bar['life_time_enabled'];
			if ( is_bool( $se ) ) {
				$scheduled_enabled = $se;
			} else {
				$raw = is_string( $se ) ? strtolower( trim( (string) $se ) ) : '';
				$scheduled_enabled = $raw === 'true' || $raw === '1' || $se === 1;
			}
		}

		$scheduled_from_datetime = isset( $bar['scheduled_from_datetime'] )
			? sanitize_text_field( (string) $bar['scheduled_from_datetime'] )
			: '';
		$scheduled_to_datetime = isset( $bar['scheduled_to_datetime'] )
			? sanitize_text_field( (string) $bar['scheduled_to_datetime'] )
			: '';

		// Back-compat: earlier saved keys.
		$scheduled_from_date = isset( $bar['scheduled_from_date'] )
			? sanitize_text_field( (string) $bar['scheduled_from_date'] )
			: '';
		$scheduled_from_time = isset( $bar['scheduled_from_time'] )
			? sanitize_text_field( (string) $bar['scheduled_from_time'] )
			: '';
		$scheduled_to_date = isset( $bar['scheduled_to_date'] )
			? sanitize_text_field( (string) $bar['scheduled_to_date'] )
			: '';
		$scheduled_to_time = isset( $bar['scheduled_to_time'] )
			? sanitize_text_field( (string) $bar['scheduled_to_time'] )
			: '';

		// Back-compat: map earlier keys if new ones are missing.
		if ( $scheduled_from_datetime === '' && $scheduled_from_date === '' && isset( $bar['life_time_from_date'] ) ) {
			$scheduled_from_date = sanitize_text_field( (string) $bar['life_time_from_date'] );
		}
		if ( $scheduled_from_datetime === '' && $scheduled_from_time === '' && isset( $bar['life_time_from_time'] ) ) {
			$scheduled_from_time = sanitize_text_field( (string) $bar['life_time_from_time'] );
		}
		if ( $scheduled_to_datetime === '' && $scheduled_to_date === '' && isset( $bar['life_time_to_date'] ) ) {
			$scheduled_to_date = sanitize_text_field( (string) $bar['life_time_to_date'] );
		}
		if ( $scheduled_to_datetime === '' && $scheduled_to_time === '' && isset( $bar['life_time_to_time'] ) ) {
			$scheduled_to_time = sanitize_text_field( (string) $bar['life_time_to_time'] );
		}

		// Normalize into ISO datetime strings.
		// If date+time were provided, combine them; otherwise use provided datetime.
		$scheduled_from_datetime = $scheduled_from_datetime !== ''
			? self::sanitize_iso_datetime( $scheduled_from_datetime )
			: '';
		$scheduled_to_datetime = $scheduled_to_datetime !== ''
			? self::sanitize_iso_datetime( $scheduled_to_datetime )
			: '';

		$scheduled_from_date = self::sanitize_iso_date( $scheduled_from_date );
		$scheduled_to_date   = self::sanitize_iso_date( $scheduled_to_date );
		$scheduled_from_time = self::sanitize_iso_time( $scheduled_from_time );
		$scheduled_to_time   = self::sanitize_iso_time( $scheduled_to_time );

		if ( $scheduled_from_datetime === '' && $scheduled_from_date !== '' && $scheduled_from_time !== '' ) {
			$scheduled_from_datetime = $scheduled_from_date . 'T' . $scheduled_from_time;
		}
		if ( $scheduled_to_datetime === '' && $scheduled_to_date !== '' && $scheduled_to_time !== '' ) {
			$scheduled_to_datetime = $scheduled_to_date . 'T' . $scheduled_to_time;
		}

		// If user provided schedule values, keep scheduling enabled.
		if ( $scheduled_from_datetime !== '' || $scheduled_to_datetime !== '' ) {
			$scheduled_enabled = true;
		}

		// Clear values only when schedule is explicitly disabled and empty.
		if ( ! $scheduled_enabled ) {
			$scheduled_from_datetime = '';
			$scheduled_to_datetime   = '';
		}

		// Hide on scroll behavior: controlled by `hide_on_scroll` (bool).
		$hide_on_scroll = false;
		if ( array_key_exists( 'hide_on_scroll', $bar ) ) {
			$hide_on_scroll = ! empty( $bar['hide_on_scroll'] );
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
		$messages = array_values( array_slice( $messages, 0, 10 ) );
		$messages_mobile_visible = true;
		if ( array_key_exists( 'messages_mobile_visible', $bar ) ) {
			$mmv = $bar['messages_mobile_visible'];
			if ( is_bool( $mmv ) ) {
				$messages_mobile_visible = $mmv;
			} else {
				$raw = is_string( $mmv ) ? strtolower( trim( $mmv ) ) : '';
				$messages_mobile_visible = $raw === 'true' || $raw === '1' || $raw === 'on' || $mmv === 1;
			}
		}
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
			$hos = $row['hide_on_scroll'] ?? '0';
			if ( is_bool( $hos ) ) {
				$row['hide_on_scroll'] = $hos;
			} else {
				$row['hide_on_scroll'] = (string) $hos === '1' || $hos === 1;
			}
			$out[] = self::normalize_bar( $row );
		}
		if ( $out === [] ) {
			return [ self::default_bar() ];
		}
		$out = array_slice( $out, 0, self::max_bars() );
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
		$active = array_values(
			array_filter(
				self::get_bars(),
				static function ( $bar ) {
					if ( ! is_array( $bar ) ) {
						return false;
					}
					$v = $bar['visible'] ?? null;
					$is_visible = true;
					if ( is_bool( $v ) ) {
						$is_visible = $v;
					} elseif ( is_string( $v ) ) {
						$raw = strtolower( trim( $v ) );
						if ( in_array( $raw, [ 'true', 'false', '0', '1' ], true ) ) {
							$is_visible = $raw === 'true' || $raw === '1';
						}
					}
					if ( ! $is_visible ) {
						return false;
					}

					return self::is_bar_in_schedule_window( $bar );
				}
			)
		);

		// Enforce plan limit on active bars as well.
		return array_slice( $active, 0, self::max_bars() );
	}

	/**
	 * A bar is in schedule window when scheduling is disabled, or now is within from..to.
	 *
	 * @param array<string, mixed> $bar
	 */
	private static function is_bar_in_schedule_window( array $bar ): bool {
		$enabled_raw = $bar['scheduled_enabled'] ?? false;
		$enabled = false;
		if ( is_bool( $enabled_raw ) ) {
			$enabled = $enabled_raw;
		} elseif ( is_string( $enabled_raw ) ) {
			$raw = strtolower( trim( $enabled_raw ) );
			$enabled = $raw === 'true' || $raw === '1';
		} elseif ( is_numeric( $enabled_raw ) ) {
			$enabled = (int) $enabled_raw === 1;
		}
		if ( ! $enabled ) {
			return true;
		}

		$from_raw = isset( $bar['scheduled_from_datetime'] ) ? (string) $bar['scheduled_from_datetime'] : '';
		$to_raw   = isset( $bar['scheduled_to_datetime'] ) ? (string) $bar['scheduled_to_datetime'] : '';
		$from = self::sanitize_iso_datetime( $from_raw );
		$to   = self::sanitize_iso_datetime( $to_raw );
		if ( $from === '' || $to === '' ) {
			return false;
		}

		try {
			$tz = wp_timezone();
			$now = new \DateTimeImmutable( 'now', $tz );
			$from_dt = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $from, $tz );
			$to_dt = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $to, $tz );
			if ( false === $from_dt || false === $to_dt ) {
				return false;
			}
			return $now >= $from_dt && $now <= $to_dt;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	// --- Back-compat: first bar (admin/legacy UI) ---

	public static function get_position(): string {
		$bars = self::get_bars();
		$pos  = isset( $bars[0]['position'] ) ? (string) $bars[0]['position'] : 'top';
		return in_array( $pos, [ 'top', 'bottom' ], true ) ? $pos : 'top';
	}

	public static function get_message(): string {
		$bars = self::get_bars();
		return isset( $bars[0]['messages'][0] ) ? (string) $bars[0]['messages'][0] : '';
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
		$v = $bars[0]['visible'] ?? true;
		if ( is_string( $v ) ) {
			$raw = strtolower( trim( $v ) );
			if ( in_array( $raw, [ 'true', 'false', '0', '1' ], true ) ) {
				$v = $raw === 'true' || $raw === '1';
			}
		}
		return ! empty( $v ) ? 'on' : 'off';
	}

	public static function sanitize_hex_color( string $color ): string {
		$color = ltrim( $color, '#' );
		if ( preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
			return '#' . $color;
		}
		return '';
	}

	/**
	 * @return string ISO date `YYYY-MM-DD` or empty string.
	 */
	private static function sanitize_iso_date( string $value ): string {
		$value = trim( $value );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) === 1 ) {
			return $value;
		}

		// Back-compat with datepicker defaults like `MM/DD/YYYY`.
		if ( preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m ) === 1 ) {
			return $m[3] . '-' . $m[1] . '-' . $m[2];
		}
		// Back-compat with `DD/MM/YYYY`.
		if ( preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m ) === 1 ) {
			$left = (int) $m[1];
			$right = (int) $m[2];
			if ( $left > 12 ) {
				return $m[3] . '-' . sprintf( '%02d', $right ) . '-' . sprintf( '%02d', $left );
			}
		}
		// Back-compat with dotted format `DD.MM.YYYY`.
		if ( preg_match( '/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $m ) === 1 ) {
			return $m[3] . '-' . $m[2] . '-' . $m[1];
		}

		return '';
	}

	/**
	 * @return string ISO time `HH:MM` or empty string.
	 */
	private static function sanitize_iso_time( string $value ): string {
		$value = trim( $value );
		// Basic `HH:MM` validation.
		if ( preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value ) !== 1 ) {
			// Accept `HH:MM:SS` and normalize to `HH:MM`.
			if ( preg_match( '/^((?:[01]\d|2[0-3]):[0-5]\d):[0-5]\d$/', $value, $m ) === 1 ) {
				return $m[1];
			}
			return '';
		}
		return $value;
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
