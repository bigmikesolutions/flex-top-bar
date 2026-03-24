#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

docker compose -f "${ROOT_DIR}/docker-compose.yml" exec -T wordpress php -r '
require_once "/var/www/html/wp-load.php";

$bars = [
	[
		"id" => "bar_top",
		"name" => "1st - top",
		"enabled" => true,
		"visible" => true,
		"admin_visibile" => false,
		"scheduled_enabled" => false,
		"scheduled_from_datetime" => "",
		"scheduled_to_datetime" => "",
		"position" => "top",
		"effect" => "fadein",
		"messages" => ["Top bar.", "Top position.", "Scroll is off."],
		"bg_color" => "#389339",
		"frame_color" => "",
		"frame_width" => 0,
		"hide_on_scroll" => false,
	],
	[
		"id" => "bar_bottom",
		"name" => "2nd - bottom (hidden on scroll)",
		"enabled" => true,
		"visible" => true,
		"admin_visibile" => false,
		"scheduled_enabled" => false,
		"scheduled_from_datetime" => "",
		"scheduled_to_datetime" => "",
		"position" => "bottom",
		"effect" => "blink",
		"messages" => ["Bottom bar.", "Hidden on scroll."],
		"bg_color" => "#3b3893",
		"frame_color" => "#FFFF00",
		"frame_width" => 4,
		"hide_on_scroll" => true,
	],
];

update_option("top_bars", $bars);
echo "Seeded option top_bars with 2 bars (top + bottom)." . PHP_EOL;
'

