<?php
/**
 * Unit tests for Frontend class.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar\Tests;

use PHPUnit\Framework\TestCase;
use TopBar\Frontend;
use TopBar\Options;

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
		$this->assertContains( 'admin_enqueue_scripts', $hooks );
	}

	public function test_message_for_render_none_effect_concatenates_single_line(): void {
		$frontend = new Frontend();
		$method   = new \ReflectionMethod( Frontend::class, 'message_for_render' );

		$result = $method->invoke(
			$frontend,
			[
				'effect'   => 'none',
				'messages' => [ "  Hello\n<b>World</b>  ", "  Next\tline " ],
			]
		);

		$this->assertSame( 'Hello World Next line', $result );
	}

	public function test_message_for_render_non_none_effect_uses_first_message(): void {
		$frontend = new Frontend();
		$method   = new \ReflectionMethod( Frontend::class, 'message_for_render' );

		$result = $method->invoke(
			$frontend,
			[
				'effect'   => 'slider',
				'messages' => [ 'First', 'Second' ],
			]
		);

		$this->assertSame( 'First', $result );
	}

	public function test_message_for_render_returns_empty_when_no_messages(): void {
		$frontend = new Frontend();
		$method   = new \ReflectionMethod( Frontend::class, 'message_for_render' );

		$result = $method->invoke( $frontend, [ 'effect' => 'none', 'messages' => [] ] );

		$this->assertSame( '', $result );
	}

	public function test_messages_for_effect_returns_trimmed_non_empty_strings_only(): void {
		$frontend = new Frontend();
		$method   = new \ReflectionMethod( Frontend::class, 'messages_for_effect' );

		$result = $method->invoke(
			$frontend,
			[
				'messages' => [ '  First  ', '', " \n ", 'Second', 5, null, ' Third ' ],
			]
		);

		$this->assertSame( [ 'First', 'Second', 'Third' ], $result );
	}

	public function test_bar_hides_on_scroll_respects_flag(): void {
		$frontend = new Frontend();
		$method   = new \ReflectionMethod( Frontend::class, 'bar_hides_on_scroll' );

		$this->assertTrue( $method->invoke( $frontend, [ 'hide_on_scroll' => true ] ) );
		$this->assertFalse( $method->invoke( $frontend, [ 'hide_on_scroll' => false ] ) );
	}

	public function test_maybe_render_bar_outputs_markup_when_active_bar_exists(): void {
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

		$this->assertStringContainsString( 'data-top-bar-id="bar_one"', $html );
		$this->assertStringContainsString( 'Hello from test', $html );
		$this->assertStringContainsString( 'data-top-bar-mobile-visible="1"', $html );
	}

	public function test_maybe_render_bar_outputs_mobile_hidden_class_when_messages_mobile_visibility_disabled(): void {
		update_option(
			Options::OPTION_BARS,
			[
				[
					'id'                    => 'bar_mobile_hidden',
					'name'                  => 'Bar mobile hidden',
					'visible'               => true,
					'admin_visibile'        => true,
					'scheduled_enabled'     => false,
					'scheduled_from_datetime' => '',
					'scheduled_to_datetime' => '',
					'position'              => 'top',
					'effect'                => 'none',
					'messages'              => [ 'Hidden on mobile', '' ],
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
		$frontend->maybe_render_bar();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'data-top-bar-mobile-visible="0"', $html );
		$this->assertStringContainsString( 'top-bar--messages-mobile-hidden', $html );
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

		$this->assertStringContainsString( 'id="top-bar-fallback"', $html );
		$this->assertStringContainsString( 'bar_fallback', $html );
		$this->assertStringContainsString( 'data-top-bar-mobile-visible=\"0\"', $html );
		$this->assertStringContainsString( 'top-bar--messages-mobile-hidden', $html );
	}

	public function test_enqueue_assets_enqueues_style_script_and_inline_rules(): void {
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

		$this->assertContains( 'top-bar', $style_handles );
		$this->assertContains( 'top-bar-scroll-hide', $script_handles );
		$this->assertNotEmpty( $GLOBALS['wp_test_inline_styles'] );
		$this->assertStringContainsString( '#top-bar-bar_assets', $GLOBALS['wp_test_inline_styles'][0]['data'] );
		$this->assertStringContainsString( '.top-bar--messages-mobile-hidden', $GLOBALS['wp_test_inline_styles'][0]['data'] );
	}

	public function test_enqueue_admin_assets_enqueues_admin_resources(): void {
		$frontend = new Frontend();
		$frontend->enqueue_admin_assets();

		$style_handles  = array_column( $GLOBALS['wp_test_enqueued_styles'], 'handle' );
		$script_handles = array_column( $GLOBALS['wp_test_enqueued_scripts'], 'handle' );

		$this->assertContains( 'jquery-ui-style', $style_handles );
		$this->assertContains( 'top-bar-admin', $style_handles );
		$this->assertContains( 'jquery-ui-datepicker', $script_handles );
		$this->assertContains( 'top-bar-admin', $script_handles );
	}
}
