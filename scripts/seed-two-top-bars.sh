#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ICON_SRC="${ROOT_DIR}/scripts/bms.png"

if [[ ! -f "${ICON_SRC}" ]]; then
	echo "Missing seed icon: ${ICON_SRC}" >&2
	exit 1
fi

docker compose -f "${ROOT_DIR}/docker-compose.yml" cp "${ICON_SRC}" wordpress:/tmp/bms.png

docker compose -f "${ROOT_DIR}/docker-compose.yml" exec -T wordpress php -r '
require_once "/var/www/html/wp-load.php";
require_once ABSPATH . "wp-admin/includes/file.php";
require_once ABSPATH . "wp-admin/includes/media.php";
require_once ABSPATH . "wp-admin/includes/image.php";

if ( class_exists( "FlexTopBar\\Options" ) ) {
	FlexTopBar\Options::register_icon_image_size();
}

/**
 * @return array{attachment_id: int, url: string}
 */
function seed_upload_bms_icon( string $path ): array {
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, "Seed icon not readable in container: " . $path . PHP_EOL );
		exit( 1 );
	}

	$bytes = file_get_contents( $path );
	if ( ! is_string( $bytes ) || $bytes === "" ) {
		fwrite( STDERR, "Seed icon is empty: " . $path . PHP_EOL );
		exit( 1 );
	}

	$upload = wp_upload_bits( "bms.png", null, $bytes );
	if ( ! empty( $upload["error"] ) ) {
		fwrite( STDERR, "Upload failed: " . $upload["error"] . PHP_EOL );
		exit( 1 );
	}

	$attach_id = wp_insert_attachment(
		[
			"post_mime_type" => "image/png",
			"post_title"     => "bms",
			"post_content"   => "",
			"post_status"    => "inherit",
		],
		$upload["file"]
	);
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		fwrite( STDERR, "Failed to create attachment." . PHP_EOL );
		exit( 1 );
	}

	$metadata = wp_generate_attachment_metadata( (int) $attach_id, $upload["file"] );
	if ( is_array( $metadata ) && $metadata !== [] ) {
		wp_update_attachment_metadata( (int) $attach_id, $metadata );
	}

	$url = wp_get_attachment_image_url( (int) $attach_id, "flex_top_bar_icon" );
	if ( ! is_string( $url ) || $url === "" ) {
		$url = (string) wp_get_attachment_url( (int) $attach_id );
	}

	return [
		"attachment_id" => (int) $attach_id,
		"url"           => (string) $url,
	];
}

$seed_icon = seed_upload_bms_icon( "/tmp/bms.png" );

$countdown_end   = gmdate( "Y-m-d\TH:i", time() + 7 * DAY_IN_SECONDS );
$countdown_start = gmdate( "Y-m-d\TH:i", time() - DAY_IN_SECONDS );

// Clean old plugin state so seeding is deterministic.
delete_option("flex_top_bar_bars");
delete_option("flex_top_bar_bars_draft");

$bars = [
	[
		"id" => "bar_top",
		"name" => "1st - top",
		"visible" => true,
		"admin_visibile" => true,
		"scheduled_enabled" => false,
		"scheduled_from_datetime" => "",
		"scheduled_to_datetime" => "",
		"position" => "top",
		"effect" => "fadein",
		"messages" => ["Top bar.", "Top position.", "Scroll is off."],
		"messages_mobile_visible" => true,
		"columns" => [
			[
				"id" => "image_0",
				"type" => "social",
				"icon_style" => "rounded",
				"background_color" => "#ffffff",
				"icon_color" => "#ff0000",
				"icon_border_width" => 0,
				"icon_border_color" => "#1d2327",
				"links" => [
					[
						"platform" => "youtube",
						"url" => "https://www.youtube.com/",
					],
				],
				"size_percent" => 10,
				"content_position" => "right",
				"messages_mobile_visible" => true,
			],
			[
				"id" => "text_1",
				"type" => "text",
				"effect" => "fadein",
				"messages" => ["Top bar.", "Top position.", "Scroll is off."],
				"size_percent" => 25,
				"content_position" => "left",
				"messages_mobile_visible" => true,
			],
			[
				"id" => "countdown_1",
				"type" => "countdown",
				"counter_style" => "boxed",
				"count_direction" => "down",
				"countdown_to_datetime" => $countdown_end,
				"countup_from_datetime" => $countdown_start,
				"countdown_timezone" => "UTC",
				"text" => "Countdown ends in",
				"text_position" => "before",
				"background_color" => "#1d2327",
				"counter_color" => "#ffffff",
				"text_color" => "#ffffff",
				"size_percent" => 25,
				"content_position" => "center",
				"messages_mobile_visible" => true,
			],
			[
				"id" => "icon_0",
				"type" => "icon",
				"icon_attachment_id" => $seed_icon["attachment_id"],
				"icon_url" => $seed_icon["url"],
				"text" => "BMS rulez!",
				"icon_position" => "before",
				"size_percent" => 25,
				"content_position" => "right",
				"messages_mobile_visible" => true,
			],
		],
		"bg_color" => "#389339",
		"frame_color" => "",
		"frame_width" => 0,
		"hide_on_scroll" => false,
	],
	[
		"id" => "bar_bottom",
		"name" => "2nd - bottom (hidden on scroll)",
		"visible" => true,
		"admin_visibile" => false,
		"scheduled_enabled" => false,
		"scheduled_from_datetime" => "",
		"scheduled_to_datetime" => "",
		"position" => "bottom",
		"effect" => "blink",
		"messages" => ["Bottom bar.", "Hidden on scroll."],
		"messages_mobile_visible" => false,
		"columns" => [
			[
				"id" => "image_0",
				"type" => "social",
				"icon_style" => "color",
				"background_color" => "#ffffff",
				"icon_color" => "#ff0000",
				"icon_border_width" => 4,
				"icon_border_color" => "#ff4500",
				"links" => [
					[
						"platform" => "spotify",
						"url" => "https://www.spotify.com/",
					],
				],
				"size_percent" => 10,
				"content_position" => "right",
				"messages_mobile_visible" => true,
			],
			[
				"id" => "image_1",
				"type" => "social",
				"icon_style" => "rounded",
				"background_color" => "#ffffff",
				"icon_color" => "#ff0000",
				"icon_border_width" => 4,
				"icon_border_color" => "#ff4500",
				"links" => [
					[
						"platform" => "facebook",
						"url" => "https://www.facebook.com/",
					],
				],
				"size_percent" => 10,
				"content_position" => "right",
				"messages_mobile_visible" => true,
			],
			[
				"id" => "col_bar_bottom",
				"type" => "text",
				"effect" => "blink",
				"messages" => ["Bottom bar.", "Hidden on scroll."],
				"size_percent" => 50,
				"content_position" => "center",
				"messages_mobile_visible" => false,
			],
				[
				"id" => "image_2",
				"type" => "social",
				"icon_style" => "white",
				"background_color" => "#ffffff",
				"icon_color" => "#ff0000",
				"icon_border_width" => 0,
				"icon_border_color" => "#1d2327",
				"links" => [
					[
						"platform" => "apple",
						"url" => "https://www.apple.com/pl",
					],
				],
				"size_percent" => 10,
				"content_position" => "left",
				"messages_mobile_visible" => true,
			],
		],
		"bg_color" => "#3b3893",
		"frame_color" => "#FFFF00",
		"frame_width" => 4,
		"hide_on_scroll" => true,
	],
	[
		"id" => "bar_bottom_scheduled",
		"name" => "3rd - bottom scheduled",
		"visible" => false,
		"admin_visibile" => false,
		"scheduled_enabled" => true,
		"scheduled_from_datetime" => "2026-01-25T10:00",
		"scheduled_to_datetime" => "2026-12-25T11:00",
		"position" => "bottom",
		"effect" => "none",
		"messages" => ["Bottom bar. scheduled"],
		"messages_mobile_visible" => false,
		"columns" => [
			[
				"id" => "col_bar_scheduled",
				"type" => "text",
				"effect" => "none",
				"messages" => ["Bottom bar. scheduled"],
				"size_percent" => 100,
				"content_position" => "center",
				"messages_mobile_visible" => false,
			],
		],
		"bg_color" => "#ff4500",
		"frame_color" => "#FFFF00",
		"frame_width" => 0,
		"hide_on_scroll" => false,
	],
];

update_option("flex_top_bar_bars", $bars);
// Admin edits drafts; keep draft in sync with published for seeds.
update_option("flex_top_bar_bars_draft", $bars);
echo "Seeded options flex_top_bar_bars + flex_top_bar_bars_draft with 3 bars (top, bottom, scheduled)." . PHP_EOL;
echo "Uploaded bms.png as attachment " . $seed_icon["attachment_id"] . " -> " . $seed_icon["url"] . PHP_EOL;
'

