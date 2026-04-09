#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

docker compose -f "${ROOT_DIR}/docker-compose.yml" exec -T wordpress php -r '
require_once "/var/www/html/wp-load.php";

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
				"size_percent" => 50,
				"content_position" => "left",
				"messages_mobile_visible" => true,
			],
			[
				"id" => "text_2",
				"type" => "text",
				"effect" => "blink",
				"messages" => ["2nd column.", "2nd column effect is working."],
				"size_percent" => 33,
				"content_position" => "center",
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

update_option("flex_top_bars", $bars);
// Admin edits drafts; keep draft in sync with published for seeds.
update_option("flex_top_bars_draft", $bars);
echo "Seeded options top_bars + top_bars_draft with 3 bars (top, bottom, scheduled)." . PHP_EOL;
'

