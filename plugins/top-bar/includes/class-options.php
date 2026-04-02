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
	public const OPTION_BARS_DRAFT = 'top_bars_draft';

	/** At least one bar configuration must exist. */
	public const MIN_BARS = 1;

	/** Maximum layout columns per bar (admin + frontend). */
	public const MAX_COLUMNS = 4;

	public static function new_bar_id(): string {
		return 'bar_' . wp_generate_password( 8, false, false );
	}

	public static function new_column_id(): string {
		return 'col_' . wp_generate_password( 8, false, false );
	}

	/** @return array<string, mixed> */
	public static function default_bar(): array {
		$welcome = __( 'Welcome!', 'top-bar' );
		$column  = [
			'id'                      => self::new_column_id(),
			'type'                    => 'text',
			'effect'                  => 'none',
			'messages'                => [ $welcome, '' ],
			'size_percent'            => 100,
			'content_position'        => 'center',
			'messages_mobile_visible' => true,
		];

		return [
			'id'             => self::new_bar_id(),
			'name'           => __( 'Top Bar', 'top-bar' ),
			'visible'        => true,
			// Whether the bar's options details are expanded in the admin panel.
			'admin_visibile' => true,
			// Scheduling in admin panel.
			'scheduled_enabled'       => false,
			'scheduled_from_datetime' => '',
			'scheduled_to_datetime'   => '',
			'position'                => 'top',
			'effect'                  => $column['effect'],
			'messages'                => $column['messages'],
			'messages_mobile_visible' => $column['messages_mobile_visible'],
			'columns'                 => [ $column ],
			'bg_color'                => '#1d2327',
			'frame_color'             => '',
			'frame_width'             => 0,
			'hide_on_scroll'          => false,
		];
	}

	/**
	 * Bars from DB.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function get_bars(): array {
		// Admin edits drafts; published bars are served to the frontend separately.
		self::ensure_draft_initialized();
		$stored = get_option( self::OPTION_BARS_DRAFT );
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
	 * Published bars from DB.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function get_published_bars(): array {
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
	 * Copy draft bars into published bars.
	 *
	 * @return list<array<string, mixed>> Published bars.
	 */
	public static function publish_draft_to_published(): array {
		self::ensure_draft_initialized();
		$draft = self::get_bars();
		update_option( self::OPTION_BARS, $draft );
		return self::get_published_bars();
	}

	/**
	 * If draft bars don't exist yet, seed them from published bars.
	 */
	private static function ensure_draft_initialized(): void {
		$draft = get_option( self::OPTION_BARS_DRAFT, null );
		if ( is_array( $draft ) ) {
			return;
		}
		$published = get_option( self::OPTION_BARS, [] );
		if ( ! is_array( $published ) ) {
			$published = [];
		}
		update_option( self::OPTION_BARS_DRAFT, $published );
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
		// Preserve frame color even when width is 0 so the user's color choice isn't lost.
		// Rendering still depends on width > 0 in the frontend/admin preview styles.
		$frame = isset( $bar['frame_color'] ) ? self::sanitize_hex_color( (string) $bar['frame_color'] ) : '';

		$max_messages = FeatureFlags::instance()->max_messages();

		$columns = [];
		if ( isset( $bar['columns'] ) && is_array( $bar['columns'] ) && $bar['columns'] !== [] ) {
			foreach ( $bar['columns'] as $col ) {
				if ( is_array( $col ) ) {
					$columns[] = self::normalize_column_row( $col, $default_message, $max_messages );
				}
			}
			$columns = array_values( array_slice( $columns, 0, FeatureFlags::instance()->max_columns() ) );
		}

		if ( $columns === [] ) {
			$columns = [
				self::normalize_column_row(
					[
						'effect'                  => $effect,
						'messages'                => $messages,
						'messages_mobile_visible' => $messages_mobile_visible,
						'size_percent'            => 100,
						'content_position'        => 'center',
					],
					$default_message,
					$max_messages
				),
			];
		}

		return [
			'id'                      => sanitize_key( (string) $id ) ?: (string) $id,
			'name'                    => isset( $bar['name'] ) ? sanitize_text_field( (string) $bar['name'] ) : $defaults['name'],
			'visible'                 => $visible,
			'admin_visibile'          => $admin_visibile,
			'scheduled_enabled'       => $scheduled_enabled,
			'scheduled_from_datetime' => $scheduled_from_datetime,
			'scheduled_to_datetime'   => $scheduled_to_datetime,
			'position'                => $pos,
			'effect'                  => $effect,
			'messages'                => $messages,
			'messages_mobile_visible' => $messages_mobile_visible,
			'columns'                 => $columns,
			'bg_color'                => $bg ?: '#1d2327',
			'frame_color'             => $frame,
			'frame_width'             => $width,
			'hide_on_scroll'          => $hide_on_scroll,
		];
	}

	/**
	 * @param array<string, mixed> $col
	 * @return array<string, mixed>
	 */
	private static function normalize_column_row( array $col, string $default_message, int $max_messages ): array {
		$id = isset( $col['id'] ) && is_string( $col['id'] ) && $col['id'] !== ''
			? sanitize_key( (string) $col['id'] )
			: self::new_column_id();

		$size_percent = isset( $col['size_percent'] ) ? (int) $col['size_percent'] : 100;
		if ( ! in_array( $size_percent, [ 10, 25, 33, 50, 75, 100 ], true ) ) {
			$size_percent = 100;
		}

		$mmv = self::parse_bool( $col['messages_mobile_visible'] ?? true, true );

		$content_position = isset( $col['content_position'] ) ? sanitize_key( (string) $col['content_position'] ) : 'center';
		if ( ! in_array( $content_position, [ 'left', 'center', 'right' ], true ) ) {
			$content_position = 'center';
		}

		$type = isset( $col['type'] ) ? sanitize_key( (string) $col['type'] ) : 'text';
		if ( ! in_array( $type, [ 'text', 'social', 'contact' ], true ) ) {
			$type = 'text';
		}

		if ( $type === 'social' ) {
			return self::normalize_social_column( $col, $id, $size_percent, $content_position, $mmv, $max_messages );
		}
		if ( $type === 'contact' ) {
			return self::normalize_contact_column( $col, $id, $size_percent, $content_position, $mmv, $max_messages );
		}

		return self::normalize_text_column( $col, $id, $default_message, $max_messages, $size_percent, $content_position, $mmv );
	}

	/**
	 * @return list<string>
	 */
	private static function allowed_social_platforms(): array {
		return [
			'facebook',
			'twitterX',
			'instagram',
			'linkedin',
			'google',
			'youtube',
			'apple',
			'snapchat',
			'pinterest',
			'medium',
			'github',
			'threads',
			'whatsapp',
			'figma',
			'dribbble',
			'reddit',
			'discord',
			'tiktok',
			'tumblr',
			'telegram',
			'bluesky',
			'signal',
			'vk',
			'spotify',
			'twitch',
			'messenger'
		];
	}

	/**
	 * @return list<string>
	 */
	private static function allowed_contact_kinds(): array {
		return [
			'email',
			'phone',
			'mobile',
			'location',
			'chat',
			'website',
			'support',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function normalize_text_column(
		array $col,
		string $id,
		string $default_message,
		int $max_messages,
		int $size_percent,
		string $content_position,
		bool $mmv
	): array {
		$effect = isset( $col['effect'] ) ? sanitize_key( (string) $col['effect'] ) : 'none';
		if ( ! in_array( $effect, [ 'none', 'slider', 'fadein', 'blink' ], true ) ) {
			$effect = 'none';
		}

		$messages = [];
		if ( isset( $col['messages'] ) && is_array( $col['messages'] ) ) {
			foreach ( $col['messages'] as $item ) {
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
		$messages = array_values( array_slice( $messages, 0, $max_messages ) );

		return [
			'id'                      => $id,
			'type'                    => 'text',
			'effect'                  => $effect,
			'messages'                => $messages,
			'size_percent'            => $size_percent,
			'content_position'        => $content_position,
			'messages_mobile_visible' => $mmv,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function normalize_social_column(
		array $col,
		string $id,
		int $size_percent,
		string $content_position,
		bool $mmv,
		int $max_links
	): array {
		$icon_style = isset( $col['icon_style'] ) ? sanitize_key( (string) $col['icon_style'] ) : 'rounded';
		if ( ! in_array( $icon_style, [ 'rounded', 'square', 'color', 'black', 'white' ], true ) ) {
			$icon_style = 'rounded';
		}

		$bg = isset( $col['background_color'] ) ? self::sanitize_hex_color( (string) $col['background_color'] ) : '';
		if ( $bg === '' ) {
			$bg = '#ffffff';
		}

		$icon_color = isset( $col['icon_color'] ) ? self::sanitize_hex_color( (string) $col['icon_color'] ) : '';
		if ( $icon_color === '' ) {
			$icon_color = '#1d2327';
		}

		$icon_border_width = isset( $col['icon_border_width'] ) ? (int) $col['icon_border_width'] : 0;
		if ( $icon_border_width < 0 ) {
			$icon_border_width = 0;
		}
		if ( $icon_border_width > 10 ) {
			$icon_border_width = 10;
		}

		$icon_border_color = isset( $col['icon_border_color'] ) ? self::sanitize_hex_color( (string) $col['icon_border_color'] ) : '';
		if ( $icon_border_color === '' ) {
			$icon_border_color = '#1d2327';
		}

		$allowed = self::allowed_social_platforms();
		$links   = [];
		if ( isset( $col['links'] ) && is_array( $col['links'] ) ) {
			foreach ( $col['links'] as $link ) {
				if ( count( $links ) >= $max_links ) {
					break;
				}
				if ( ! is_array( $link ) ) {
					continue;
				}
				$platform_raw = isset( $link['platform'] ) ? (string) $link['platform'] : '';
				// sanitize_key() lowercases, but our canonical UI value is "twitterX" (legacy).
				if ( $platform_raw === 'twitterX' ) {
					$platform = 'twitterX';
				} else {
					$platform = sanitize_key( $platform_raw );
					if ( $platform === 'twitterx' ) {
						$platform = 'twitterX';
					}
				}
				if ( $platform !== '' && ! in_array( $platform, $allowed, true ) ) {
					$platform = '';
				}
				$url = isset( $link['url'] ) ? esc_url_raw( (string) $link['url'] ) : '';
				$links[] = [
					'platform' => $platform,
					'url'      => $url,
				];
			}
		}

		if ( $links === [] ) {
			$links[] = [
				'platform' => '',
				'url'      => '',
			];
		}

		return [
			'id'                      => $id,
			'type'                    => 'social',
			'icon_style'              => $icon_style,
			'background_color'        => $bg,
			'icon_color'              => $icon_color,
			'icon_border_width'       => $icon_border_width,
			'icon_border_color'       => $icon_border_color,
			'links'                   => $links,
			'size_percent'            => $size_percent,
			'content_position'        => $content_position,
			'messages_mobile_visible' => $mmv,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function normalize_contact_column(
		array $col,
		string $id,
		int $size_percent,
		string $content_position,
		bool $mmv,
		int $max_entries
	): array {
		$icon_style = isset( $col['icon_style'] ) ? sanitize_key( (string) $col['icon_style'] ) : 'rounded';
		// Keep this allowlist in sync with the admin UI options (src/constants/iconStyleOptions.ts).
		if ( ! in_array( $icon_style, [ 'rounded', 'square', 'black', 'white', 'color' ], true ) ) {
			$icon_style = 'rounded';
		}

		$bg = isset( $col['background_color'] ) ? self::sanitize_hex_color( (string) $col['background_color'] ) : '';
		if ( $bg === '' ) {
			$bg = '#ffffff';
		}

		$icon_color = isset( $col['icon_color'] ) ? self::sanitize_hex_color( (string) $col['icon_color'] ) : '';
		if ( $icon_color === '' ) {
			$icon_color = '#1d2327';
		}

		$icon_border_width = isset( $col['icon_border_width'] ) ? (int) $col['icon_border_width'] : 0;
		if ( $icon_border_width < 0 ) {
			$icon_border_width = 0;
		}
		if ( $icon_border_width > 10 ) {
			$icon_border_width = 10;
		}

		$icon_border_color = isset( $col['icon_border_color'] ) ? self::sanitize_hex_color( (string) $col['icon_border_color'] ) : '';
		if ( $icon_border_color === '' ) {
			$icon_border_color = '#1d2327';
		}

		$allowed  = self::allowed_contact_kinds();
		$contacts = [];
		if ( isset( $col['contacts'] ) && is_array( $col['contacts'] ) ) {
			foreach ( $col['contacts'] as $entry ) {
				if ( count( $contacts ) >= $max_entries ) {
					break;
				}
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$kind = isset( $entry['kind'] ) ? sanitize_key( (string) $entry['kind'] ) : '';
				if ( $kind !== '' && ! in_array( $kind, $allowed, true ) ) {
					$kind = '';
				}
				$raw   = isset( $entry['value'] ) ? (string) $entry['value'] : '';
				$value = $kind === 'address' ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
				$contacts[] = [
					'kind'  => $kind,
					'value' => $value,
				];
			}
		}

		if ( $contacts === [] ) {
			$contacts[] = [
				'kind'  => '',
				'value' => '',
			];
		}

		return [
			'id'                      => $id,
			'type'                    => 'contact',
			'icon_style'              => $icon_style,
			'background_color'        => $bg,
			'icon_color'              => $icon_color,
			'icon_border_width'       => $icon_border_width,
			'icon_border_color'       => $icon_border_color,
			'contacts'                => $contacts,
			'size_percent'            => $size_percent,
			'content_position'        => $content_position,
			'messages_mobile_visible' => $mmv,
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
				self::get_published_bars(),
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
