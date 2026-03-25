#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

docker compose -f "${ROOT_DIR}/docker-compose.yml" exec -T wordpress php -r '
require_once "/var/www/html/wp-load.php";

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
				"id" => "text_1",
				"type" => "text",
				"effect" => "fadein",
				"messages" => ["Top bar.", "Top position.", "Scroll is off."],
				"size_percent" => 50,
				"messages_mobile_visible" => true,
			],
			[
				"id" => "text_2",
				"type" => "text",
				"effect" => "blink",
				"messages" => ["2nd column.", "2nd column effect is working."],
				"size_percent" => 50,
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
				"id" => "col_bar_bottom",
				"type" => "text",
				"effect" => "blink",
				"messages" => ["Bottom bar.", "Hidden on scroll."],
				"size_percent" => 100,
				"messages_mobile_visible" => false,
			],
		],
		"bg_color" => "#3b3893",
		"frame_color" => "#FFFF00",
		"frame_width" => 4,
		"hide_on_scroll" => true,
	],
	[
		"id" => "bar_bottom - scheduled",
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
				"messages_mobile_visible" => false,
			],
		],
		"bg_color" => "#ff4500",
		"frame_color" => "#FFFF00",
		"frame_width" => 0,
		"hide_on_scroll" => false,
	],
];

update_option("top_bars", $bars);
echo "Seeded option top_bars with 3 bars (top, bottom, scheduled)." . PHP_EOL;
'

