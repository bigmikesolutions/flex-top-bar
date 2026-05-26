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

$warsaw_tz       = new DateTimeZone( "Europe/Warsaw" );
$now_warsaw      = new DateTime( "now", $warsaw_tz );
$countdown_end   = ( clone $now_warsaw )->modify( "+7 days" )->format( "Y-m-d\TH:i" );
$countdown_start = ( clone $now_warsaw )->modify( "-1 day" )->format( "Y-m-d\TH:i" );

// Clean old plugin state so seeding is deterministic.
delete_option("flex_top_bar_bars");
delete_option("flex_top_bar_bars_draft");

$bars = [
	[
		"id" => "bar_top_1",
		"name" => "1st - top",
		"visible" => true,
		"admin_visibile" => true,
		"scheduled_enabled" => false,
		"scheduled_from_datetime" => "",
		"scheduled_to_datetime" => "",
		"position" => "top",
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
				"countdown_timezone" => "Europe/Warsaw",
				"text" => "Countdown ends in",
				"text_position" => "before",
				"background_color" => "#FEDC56",
				"counter_color" => "#ffffff",
				"text_color" => "black",
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
		"id" => "bar_top_2",
		"name" => "2nd - top (hidden on scroll)",
		"visible" => true,
		"admin_visibile" => false,
		"scheduled_enabled" => false,
		"scheduled_from_datetime" => "",
		"scheduled_to_datetime" => "",
		"position" => "top",
		"columns" => [
			[
				"id" => "image_0",
				"type" => "social",
				"icon_style" => "color",
				"background_color" => "#ff9900",
				"icon_color" => "#ff0000",
				"icon_border_width" => 0,
				"icon_border_color" => "#ff4500",
				"links" => [
					[
						"platform" => "twitterx",
						"url" => "https://www.twitter.com/",
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
				"icon_border_width" => 0,
				"icon_border_color" => "#ff4500",
				"links" => [
					[
						"platform" => "snapchat",
						"url" => "https://www.snapchat.com/",
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
				"messages" => ["2nd top bar.", "Hidden on scroll."],
				"size_percent" => 50,
				"content_position" => "center",
				"messages_mobile_visible" => false,
			],
			[
				"id" => "image_2",
				"type" => "contact",
				"icon_style" => "white",
				"background_color" => "#ffffff",
				"icon_color" => "#ff0000",
				"icon_border_width" => 0,
				"icon_border_color" => "#1d2327",
				"contacts" => [
					[
						"kind" => "website",
						"value" => "https://bigmikesolutions.pl",
					],
				],
				"size_percent" => 10,
				"content_position" => "left",
				"messages_mobile_visible" => true,
			],
		],
		"bg_color" => "#ff9900",
		"frame_color" => "#FFFF00",
		"frame_width" => 4,
		"hide_on_scroll" => true,
	],
	[
		"id" => "bar_bottom_1",
		"name" => "2nd - bottom (hidden on scroll)",
		"visible" => true,
		"admin_visibile" => false,
		"scheduled_enabled" => false,
		"scheduled_from_datetime" => "",
		"scheduled_to_datetime" => "",
		"position" => "bottom",
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
		"id" => "bar_bottom_scheduled_1",
		"name" => "3rd - bottom scheduled",
		"visible" => true,
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

