<?php
/**
 * Unit tests for Frontend class.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace FlexTopBar\Tests;

use PHPUnit\Framework\TestCase;
use FlexTopBar\Frontend;
use FlexTopBar\Options;

final class FrontendTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_test_options'] = [];
		$GLOBALS['wp_test_actions'] = [];
		$GLOBALS['wp_test_filters'] = [];
		$GLOBALS['wp_test_enqueued_styles'] = [];
		$GLOBALS['wp_test_enqueued_scripts'] = [];
		$GLOBALS['wp_test_inline_styles'] = [];
	}

	public function test_constructor_registers_expected_actions(): void {
		new Frontend();

		$hooks = array_map(
			static fn( array $row ): string => (string) $row['hook'],
			$GLOBALS['wp_test_actions']
		);

		$this->assertContains( 'wp_body_open', $hooks );
		$this->assertContains( 'wp_footer', $hooks );
		$this->assertContains( 'wp_enqueue_scripts', $hooks );
	}

	public function test_maybe_render_bar_outputs_mount_point_when_active_bar_exists(): void {
		update_option(
			Options::OPTION_BARS,
			[
				[
					'id'                    => 'bar_one',
					'name'                  => 'Bar one',
					'visible'               => true,
					'admin_visibile'        => true,
					'scheduled_enabled'     => false,
					'scheduled_from_datetime' => '',
					'scheduled_to_datetime' => '',
					'position'              => 'top',
					'effect'                => 'none',
					'messages'              => [ 'Hello from test', '' ],
					'messages_mobile_visible' => true,
					'bg_color'              => '#123456',
					'frame_color'           => '',
					'frame_width'           => 0,
					'hide_on_scroll'        => false,
				],
			]
		);

		$frontend = new Frontend();
		ob_start();
		$frontend->maybe_render_bar();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'id="flex-top-bar-frontend-mount"', $html );
	}

	public function test_maybe_output_bar_fallback_outputs_script_when_active_bar_exists(): void {
		update_option(
			Options::OPTION_BARS,
			[
				[
					'id'                    => 'bar_fallback',
					'name'                  => 'Bar fallback',
					'visible'               => true,
					'admin_visibile'        => true,
					'scheduled_enabled'     => false,
					'scheduled_from_datetime' => '',
					'scheduled_to_datetime' => '',
					'position'              => 'bottom',
					'effect'                => 'none',
					'messages'              => [ 'Fallback text', '' ],
					'messages_mobile_visible' => false,
					'bg_color'              => '#123456',
					'frame_color'           => '',
					'frame_width'           => 0,
					'hide_on_scroll'        => false,
				],
			]
		);

		$frontend = new Frontend();
		ob_start();
		$frontend->maybe_output_bar_fallback();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'id="flex-top-bar-fallback"', $html );
		$this->assertStringContainsString( 'flex-top-bar-frontend-mount', $html );
	}

	public function test_enqueue_assets_enqueues_vue_frontend(): void {
		update_option(
			Options::OPTION_BARS,
			[
				[
					'id'                    => 'bar_assets',
					'name'                  => 'Bar assets',
					'visible'               => true,
					'admin_visibile'        => true,
					'scheduled_enabled'     => false,
					'scheduled_from_datetime' => '',
					'scheduled_to_datetime' => '',
					'position'              => 'top',
					'effect'                => 'none',
					'messages'              => [ 'Assets text', '' ],
					'bg_color'              => '#123456',
					'frame_color'           => '#abcdef',
					'frame_width'           => 2,
					'hide_on_scroll'        => true,
				],
			]
		);

		$frontend = new Frontend();
		$frontend->enqueue_assets();

		$style_handles  = array_column( $GLOBALS['wp_test_enqueued_styles'], 'handle' );
		$script_handles = array_column( $GLOBALS['wp_test_enqueued_scripts'], 'handle' );

		// Vue frontend assets should be enqueued
		$this->assertContains( 'flex-top-bar-frontend', $style_handles );
		$this->assertContains( 'flex-top-bar-frontend', $script_handles );
	}
}
