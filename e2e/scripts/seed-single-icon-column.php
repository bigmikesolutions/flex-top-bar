<?php
/**
 * Seeds one bar with a single icon column (uploads scripts/bms.png from /tmp/bms-e2e.png).
 *
 * @package FlexTopBar
 */

require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( class_exists( 'FlexTopBar\\Options' ) ) {
	FlexTopBar\Options::register_icon_image_size();
}

$path = '/tmp/bms-e2e.png';
if ( ! is_readable( $path ) ) {
	fwrite( STDERR, "E2E icon not readable: {$path}\n" );
	exit( 1 );
}

$bytes = file_get_contents( $path );
if ( ! is_string( $bytes ) || $bytes === '' ) {
	fwrite( STDERR, "E2E icon is empty: {$path}\n" );
	exit( 1 );
}

$upload = wp_upload_bits( 'bms-e2e.png', null, $bytes );
if ( ! empty( $upload['error'] ) ) {
	fwrite( STDERR, 'Upload failed: ' . $upload['error'] . PHP_EOL );
	exit( 1 );
}

$attach_id = wp_insert_attachment(
	[
		'post_mime_type' => 'image/png',
		'post_title'     => 'bms-e2e',
		'post_content'   => '',
		'post_status'    => 'inherit',
	],
	$upload['file']
);
if ( is_wp_error( $attach_id ) || ! $attach_id ) {
	fwrite( STDERR, "Failed to create attachment.\n" );
	exit( 1 );
}

$metadata = wp_generate_attachment_metadata( (int) $attach_id, $upload['file'] );
if ( is_array( $metadata ) && $metadata !== [] ) {
	wp_update_attachment_metadata( (int) $attach_id, $metadata );
}

$icon_url = wp_get_attachment_image_url( (int) $attach_id, 'flex_top_bar_icon' );
if ( ! is_string( $icon_url ) || $icon_url === '' ) {
	$icon_url = (string) wp_get_attachment_url( (int) $attach_id );
}

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
				'id'                      => 'col_front_icon',
				'type'                    => 'icon',
				'icon_attachment_id'      => (int) $attach_id,
				'icon_url'                => (string) $icon_url,
				'text'                    => 'E2E icon column',
				'icon_position'           => 'before',
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
