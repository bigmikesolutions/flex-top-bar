<?php
/**
 * Top Bar options: ordered array of bars in DB (serialized by WordPress).
 *
 * @package FlexTopBar
 */

declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Options {

	/**
	 * Namespaced option keys to avoid collisions with other plugins.
	 */
	public const OPTION_BARS = 'flex_top_bar_bars';
	public const OPTION_BARS_DRAFT = 'flex_top_bar_bars_draft';

	/** Max upload width/height (px) for custom icon column images. */
	public const ICON_COLUMN_MAX_WIDTH = 64;
	public const ICON_COLUMN_MAX_HEIGHT = 64;
	/** Max file size (bytes) for icon column uploads — 512 KB. */
	public const ICON_COLUMN_MAX_FILE_BYTES = 524288;

	/** Registered attachment size for icon column (soft crop — fits within max box). */
	public const ICON_COLUMN_IMAGE_SIZE = 'flex_top_bar_icon';

	public static function new_bar_id(): string {
		return 'bar_' . wp_generate_password( 8, false, false );
	}

	public static function new_column_id(): string {
		return 'col_' . wp_generate_password( 8, false, false );
	}

	/** @return array<string, mixed> */
	public static function default_bar(): array {
		$welcome = __( 'Welcome!', 'flex-top-bar' );
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
			'name'           => __( 'Top Bar', 'flex-top-bar' ),
			'visible'        => true,
			// Whether the bar's options details are expanded in the admin panel.
			'admin_visibile' => true,
			// Scheduling in admin panel.
			'scheduled_enabled'       => false,
			'scheduled_from_datetime' => '',
			'scheduled_to_datetime'   => '',
			'scheduled_timezone'      => '',
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
		$stored = self::get_option( self::OPTION_BARS, [] );
		$draft  = self::get_option( self::OPTION_BARS_DRAFT, null );
		$stored = $draft ?? $stored;
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
		$stored = self::get_option( self::OPTION_BARS, [] );
		if ( ! is_array( $stored ) || $stored === [] ) {
			return [];
		}
		$out = [];
		foreach ( $stored as $row ) {
			if ( is_array( $row ) ) {
				$out[] = self::normalize_bar( $row );
			}
		}
		if ( $out === [] ) {
			return [];
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
		self::update_option( self::OPTION_BARS, $draft );
		return self::get_published_bars();
	}

	/**
	 * Remove a bar from the published snapshot (e.g. after draft delete).
	 * Keeps the live site in sync without a separate publish step.
	 */
	public static function remove_bar_from_published( string $bar_id ): void {
		$published = self::get_option( self::OPTION_BARS, [] );
		if ( ! is_array( $published ) || $published === [] ) {
			return;
		}
		$filtered = [];
		foreach ( $published as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( (string) ( $row['id'] ?? '' ) === $bar_id ) {
				continue;
			}
			$filtered[] = self::normalize_bar( $row );
		}
		if ( $filtered === [] ) {
			return;
		}
		self::update_option( self::OPTION_BARS, array_values( $filtered ) );
	}

	/**
	 * Publish a single bar from draft into published.
	 *
	 * @return array<string, mixed>|null The published bar, or null when not found in draft.
	 */
	public static function publish_bar( string $bar_id ): ?array {
		self::ensure_draft_initialized();

		$draft = self::get_bars();
		$draft_bar = null;
		foreach ( $draft as $b ) {
			if ( is_array( $b ) && ( $b['id'] ?? '' ) === $bar_id ) {
				$draft_bar = $b;
				break;
			}
		}
		if ( ! is_array( $draft_bar ) ) {
			return null;
		}

		$published = self::get_published_bars();
		$found = false;
		foreach ( $published as $idx => $b ) {
			if ( is_array( $b ) && ( $b['id'] ?? '' ) === $bar_id ) {
				$published[ $idx ] = $draft_bar;
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$published[] = $draft_bar;
		}

		$published = array_values( array_slice( $published, 0, FeatureFlags::instance()->max_bars() ) );
		self::update_option( self::OPTION_BARS, $published );

		return self::normalize_bar( $draft_bar );
	}

	/**
	 * If draft bars don't exist yet, seed them from published bars.
	 */
	private static function ensure_draft_initialized(): void {
		$draft = self::get_option( self::OPTION_BARS_DRAFT, null );
		if ( is_array( $draft ) ) {
			return;
		}
		$published = self::get_option( self::OPTION_BARS, null );
		if ( ! is_array( $published ) || $published === [] ) {
			// On a fresh install we seed only the draft bar so nothing is live until "Publish".
			self::update_option( self::OPTION_BARS_DRAFT, [ self::default_bar() ] );
			return;
		}
		self::update_option( self::OPTION_BARS_DRAFT, $published );
	}

	/**
	 * Read a namespaced option.
	 *
	 * @param mixed $default
	 * @return mixed
	 */
	private static function get_option( string $key, $default ) {
		return get_option( $key, $default );
	}

	/**
	 * Update namespaced option only (no legacy mirroring).
	 *
	 * @param mixed $value
	 */
	private static function update_option( string $new_key, $value ): void {
		update_option( $new_key, $value );
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
		$scheduled_timezone = isset( $bar['scheduled_timezone'] )
			? self::sanitize_timezone( sanitize_text_field( (string) $bar['scheduled_timezone'] ) )
			: '';

		if ( $scheduled_from_datetime !== '' || $scheduled_to_datetime !== '' ) {
			$scheduled_enabled = true;
		}

		if ( ! $scheduled_enabled ) {
			$scheduled_from_datetime = '';
			$scheduled_to_datetime   = '';
			$scheduled_timezone      = '';
		}

		$effect = isset( $bar['effect'] ) ? sanitize_key( (string) $bar['effect'] ) : 'none';
		if ( ! in_array( $effect, [ 'none', 'slider', 'fadein', 'blink' ], true ) ) {
			$effect = 'none';
		}

		$default_message = isset( $defaults['messages'][0] ) && is_string( $defaults['messages'][0] ) ? $defaults['messages'][0] : __( 'Welcome!', 'flex-top-bar' );
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
			'scheduled_timezone'      => $scheduled_timezone,
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
		if ( ! in_array( $type, [ 'text', 'social', 'contact', 'icon' ], true ) ) {
			$type = 'text';
		}

		if ( $type === 'social' ) {
			return self::normalize_social_column( $col, $id, $size_percent, $content_position, $mmv, $max_messages );
		}
		if ( $type === 'contact' ) {
			return self::normalize_contact_column( $col, $id, $size_percent, $content_position, $mmv, $max_messages );
		}
		if ( $type === 'icon' ) {
			return self::normalize_icon_column( $col, $id, $size_percent, $content_position, $mmv );
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
	 * @return list<string>
	 */
	public static function allowed_icon_column_mime_types(): array {
		return [
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
			'image/svg+xml',
		];
	}

	/**
	 * Limits exposed to the Vue admin (keep in sync with src/constants/iconColumn.ts).
	 *
	 * @return array<string, mixed>
	 */
	public static function icon_column_media_limits(): array {
		return [
			'maxWidth'         => self::ICON_COLUMN_MAX_WIDTH,
			'maxHeight'        => self::ICON_COLUMN_MAX_HEIGHT,
			'maxFileBytes'     => self::ICON_COLUMN_MAX_FILE_BYTES,
			'allowedMimeTypes' => self::allowed_icon_column_mime_types(),
			'displaySizePx'    => 24,
		];
	}

	public static function register_icon_image_size(): void {
		add_image_size(
			self::ICON_COLUMN_IMAGE_SIZE,
			self::ICON_COLUMN_MAX_WIDTH,
			self::ICON_COLUMN_MAX_HEIGHT,
			false
		);
	}

	/**
	 * @return array{attachment_id: int, url: string}
	 */
	private static function resolve_icon_attachment( int $attachment_id ): array {
		if ( $attachment_id <= 0 ) {
			return [
				'attachment_id' => 0,
				'url'           => '',
			];
		}

		$post = get_post( $attachment_id );
		if ( ! $post instanceof \WP_Post || $post->post_type !== 'attachment' ) {
			return [
				'attachment_id' => 0,
				'url'           => '',
			];
		}

		$mime = (string) get_post_mime_type( $post );
		if ( ! in_array( $mime, self::allowed_icon_column_mime_types(), true ) ) {
			return [
				'attachment_id' => 0,
				'url'           => '',
			];
		}

		$file = get_attached_file( $attachment_id );
		if ( is_string( $file ) && $file !== '' && file_exists( $file ) ) {
			$bytes = filesize( $file );
			if ( is_int( $bytes ) && $bytes > self::ICON_COLUMN_MAX_FILE_BYTES ) {
				return [
					'attachment_id' => 0,
					'url'           => '',
				];
			}
		}

		if ( $mime !== 'image/svg+xml' ) {
			self::ensure_icon_attachment_scaled( $attachment_id );
		}

		$url = '';
		if ( $mime !== 'image/svg+xml' && function_exists( 'wp_get_attachment_image_url' ) ) {
			$sized = wp_get_attachment_image_url( $attachment_id, self::ICON_COLUMN_IMAGE_SIZE );
			if ( is_string( $sized ) && $sized !== '' ) {
				$url = $sized;
			}
		}
		if ( $url === '' ) {
			$full = wp_get_attachment_url( $attachment_id );
			if ( is_string( $full ) && $full !== '' ) {
				$url = $full;
			}
		}
		if ( $url === '' ) {
			return [
				'attachment_id' => 0,
				'url'           => '',
			];
		}

		return [
			'attachment_id' => $attachment_id,
			'url'           => esc_url_raw( $url ),
		];
	}

	private static function ensure_icon_attachment_scaled( int $attachment_id ): void {
		if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
			return;
		}

		$existing = wp_get_attachment_image_url( $attachment_id, self::ICON_COLUMN_IMAGE_SIZE );
		if ( is_string( $existing ) && $existing !== '' ) {
			return;
		}

		$file = get_attached_file( $attachment_id );
		if ( ! is_string( $file ) || $file === '' || ! file_exists( $file ) ) {
			return;
		}

		if ( defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/image.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			return;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		if ( is_array( $metadata ) && $metadata !== [] && function_exists( 'wp_update_attachment_metadata' ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function normalize_icon_column(
		array $col,
		string $id,
		int $size_percent,
		string $content_position,
		bool $mmv
	): array {
		$icon_position = isset( $col['icon_position'] ) ? sanitize_key( (string) $col['icon_position'] ) : 'before';
		if ( ! in_array( $icon_position, [ 'before', 'after' ], true ) ) {
			$icon_position = 'before';
		}

		$text = isset( $col['text'] ) ? sanitize_text_field( (string) $col['text'] ) : '';

		$attachment_id = isset( $col['icon_attachment_id'] ) ? (int) $col['icon_attachment_id'] : 0;
		$resolved      = self::resolve_icon_attachment( $attachment_id );

		return [
			'id'                      => $id,
			'type'                    => 'icon',
			'icon_attachment_id'      => $resolved['attachment_id'],
			'icon_url'                => $resolved['url'],
			'text'                    => $text,
			'icon_position'           => $icon_position,
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
		// Strip timezone suffix; wall-clock time is stored separately with scheduled_timezone.
		if ( preg_match( '/^(.+?)(?:Z|[+-]\d{2}:\d{2})$/', $value, $tz_match ) === 1 ) {
			$value = $tz_match[1];
		}
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})T((?:[01]\d|2[0-3]):[0-5]\d)$/', $value, $m ) === 1 ) {
			return $m[1] . 'T' . $m[2];
		}
		// Accept seconds and normalize to minute precision.
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})T((?:[01]\d|2[0-3]):[0-5]\d):[0-5]\d$/', $value, $m ) === 1 ) {
			return $m[1] . 'T' . $m[2];
		}
		return '';
	}

	private static function sanitize_timezone( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		try {
			new \DateTimeZone( $value );
			return $value;
		} catch ( \Exception $e ) {
			return '';
		}
	}
}
