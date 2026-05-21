<?php
/**
 * Seeds one bar with a single countdown column (count-down, end date 7 days ahead).
 *
 * @package FlexTopBar
 */

require_once '/var/www/html/wp-load.php';

$countdown_end   = gmdate( 'Y-m-d\TH:i', time() + 7 * DAY_IN_SECONDS );
$countup_from    = gmdate( 'Y-m-d\TH:i', time() - DAY_IN_SECONDS );

$bars = [
	[
		'id'                      => 'bar_single_col',
		'name'                    => 'Single column',
		'visible'                 => true,
		'admin_visibile'          => true,
		'scheduled_enabled'       => false,
		'scheduled_from_datetime' => '',
		'scheduled_to_datetime'   => '',
		'position'                => 'top',
		'effect'                  => 'none',
		'messages'                => [ '', '' ],
		'messages_mobile_visible' => true,
		'columns'                 => [
			[
				'id'                      => 'col_front_countdown',
				'type'                    => 'countdown',
				'counter_style'           => 'boxed',
				'count_direction'         => 'down',
				'countdown_to_datetime'   => $countdown_end,
				'countup_from_datetime'   => $countup_from,
				'countdown_timezone'      => 'UTC',
				'text'                    => 'E2E countdown',
				'text_position'           => 'before',
				'background_color'        => '#1d2327',
				'counter_color'           => '#ffffff',
				'text_color'              => '#ffffff',
				'size_percent'            => 100,
				'content_position'        => 'center',
				'messages_mobile_visible' => true,
			],
		],
		'bg_color'                => '#389339',
		'frame_color'             => '',
		'frame_width'             => 0,
		'hide_on_scroll'          => false,
	],
];

update_option( 'flex_top_bar_bars', $bars );
update_option( 'flex_top_bar_bars_draft', $bars );
