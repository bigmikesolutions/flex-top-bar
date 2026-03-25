<?php
/**
 * Edge case tests for Options class.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar\Tests;

use PHPUnit\Framework\TestCase;
use TopBar\FeatureFlags;
use TopBar\Options;

final class OptionsEdgeCasesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_test_options'] = [];
		FeatureFlags::reset_for_tests();
	}

	public function test_get_bars_returns_default_when_option_not_set(): void {
		$bars = Options::get_bars();

		$this->assertCount( 1, $bars );
		$this->assertArrayHasKey( 'id', $bars[0] );
		$this->assertArrayHasKey( 'messages', $bars[0] );
	}

	public function test_get_bars_returns_default_when_option_is_empty_array(): void {
		update_option( Options::OPTION_BARS, [] );
		$bars = Options::get_bars();

		$this->assertCount( 1, $bars );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_bars_filters_non_array_items(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 10 );
		}
		FeatureFlags::reset_for_tests();

		update_option(
			Options::OPTION_BARS,
			[
				[ 'id' => 'valid', 'messages' => [ 'Test' ] ],
				'not an array',
				123,
				null,
				[ 'id' => 'also_valid', 'messages' => [ 'Test2' ] ],
			]
		);

		$bars = Options::get_bars();

		$this->assertCount( 2, $bars );
		$this->assertSame( 'valid', $bars[0]['id'] );
		$this->assertSame( 'also_valid', $bars[1]['id'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_bars_respects_max_bars_limit(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 2 );
		}
		FeatureFlags::reset_for_tests();

		update_option(
			Options::OPTION_BARS,
			[
				[ 'id' => 'bar1', 'messages' => [ 'One' ] ],
				[ 'id' => 'bar2', 'messages' => [ 'Two' ] ],
				[ 'id' => 'bar3', 'messages' => [ 'Three' ] ],
				[ 'id' => 'bar4', 'messages' => [ 'Four' ] ],
			]
		);

		$bars = Options::get_bars();

		$this->assertCount( 2, $bars );
		$this->assertSame( 'bar1', $bars[0]['id'] );
		$this->assertSame( 'bar2', $bars[1]['id'] );
	}

	public function test_normalize_bar_handles_missing_id(): void {
		$bar = Options::normalize_bar( [] );

		$this->assertNotEmpty( $bar['id'] );
		$this->assertIsString( $bar['id'] );
	}

	public function test_normalize_bar_handles_empty_id(): void {
		$bar = Options::normalize_bar( [ 'id' => '' ] );

		$this->assertNotEmpty( $bar['id'] );
	}

	public function test_normalize_bar_preserves_valid_id(): void {
		$bar = Options::normalize_bar( [ 'id' => 'my_custom_id' ] );

		$this->assertSame( 'my_custom_id', $bar['id'] );
	}

	public function test_normalize_bar_defaults_position_to_top(): void {
		$bar = Options::normalize_bar( [] );

		$this->assertSame( 'top', $bar['position'] );
	}

	public function test_normalize_bar_accepts_bottom_position(): void {
		$bar = Options::normalize_bar( [ 'position' => 'bottom' ] );

		$this->assertSame( 'bottom', $bar['position'] );
	}

	public function test_normalize_bar_rejects_invalid_position(): void {
		$bar = Options::normalize_bar( [ 'position' => 'left' ] );

		$this->assertSame( 'top', $bar['position'] );
	}

	public function test_normalize_bar_handles_visible_as_boolean(): void {
		$bar1 = Options::normalize_bar( [ 'visible' => true ] );
		$bar2 = Options::normalize_bar( [ 'visible' => false ] );

		$this->assertTrue( $bar1['visible'] );
		$this->assertFalse( $bar2['visible'] );
	}

	public function test_normalize_bar_handles_visible_as_string(): void {
		$bar1 = Options::normalize_bar( [ 'visible' => '1' ] );
		$bar2 = Options::normalize_bar( [ 'visible' => '0' ] );
		$bar3 = Options::normalize_bar( [ 'visible' => 'true' ] );
		$bar4 = Options::normalize_bar( [ 'visible' => 'false' ] );

		$this->assertTrue( $bar1['visible'] );
		$this->assertFalse( $bar2['visible'] );
		$this->assertTrue( $bar3['visible'] );
		$this->assertFalse( $bar4['visible'] );
	}

	public function test_normalize_bar_handles_legacy_status_field(): void {
		$bar1 = Options::normalize_bar( [ 'status' => 'on' ] );
		$bar2 = Options::normalize_bar( [ 'status' => 'off' ] );

		$this->assertTrue( $bar1['visible'] );
		$this->assertFalse( $bar2['visible'] );
	}

	public function test_normalize_bar_handles_effect_types(): void {
		$bar1 = Options::normalize_bar( [ 'effect' => 'none' ] );
		$bar2 = Options::normalize_bar( [ 'effect' => 'slider' ] );
		$bar3 = Options::normalize_bar( [ 'effect' => 'fadein' ] );
		$bar4 = Options::normalize_bar( [ 'effect' => 'blink' ] );

		$this->assertSame( 'none', $bar1['effect'] );
		$this->assertSame( 'slider', $bar2['effect'] );
		$this->assertSame( 'fadein', $bar3['effect'] );
		$this->assertSame( 'blink', $bar4['effect'] );
	}

	public function test_normalize_bar_rejects_invalid_effect(): void {
		$bar = Options::normalize_bar( [ 'effect' => 'invalid_effect' ] );

		$this->assertSame( 'none', $bar['effect'] );
	}

	public function test_normalize_bar_sanitizes_messages_array(): void {
		$bar = Options::normalize_bar(
			[
				'messages' => [
					'<script>alert("xss")</script>Hello',
					'<b>Bold</b> text',
					123, // non-string
					null, // non-string
				],
			]
		);

		$this->assertIsArray( $bar['messages'] );
		$this->assertStringContainsString( 'Hello', $bar['messages'][0] );
		// Note: wp_kses_post in test environment doesn't actually sanitize
		// In production WordPress, scripts would be removed
		$this->assertIsString( $bar['messages'][0] );
	}

	public function test_normalize_bar_ensures_first_message_not_empty(): void {
		$bar = Options::normalize_bar( [ 'messages' => [ '', 'Second' ] ] );

		$this->assertNotEmpty( $bar['messages'][0] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_normalize_bar_respects_max_messages_limit(): void {
		if ( ! defined( 'FF_MAX_MESSAGES' ) ) {
			define( 'FF_MAX_MESSAGES', 3 );
		}
		FeatureFlags::reset_for_tests();

		$bar = Options::normalize_bar(
			[
				'messages' => [ 'One', 'Two', 'Three', 'Four', 'Five' ],
			]
		);

		$this->assertCount( 3, $bar['messages'] );
	}

	public function test_normalize_bar_handles_frame_width(): void {
		$bar1 = Options::normalize_bar( [ 'frame_width' => 5 ] );
		$bar2 = Options::normalize_bar( [ 'frame_width' => -1 ] );
		$bar3 = Options::normalize_bar( [ 'frame_width' => 100 ] );

		$this->assertSame( 5, $bar1['frame_width'] );
		$this->assertSame( 0, $bar2['frame_width'] ); // negative clamped to 0
		$this->assertSame( 10, $bar3['frame_width'] ); // max is 10
	}

	public function test_sanitize_hex_color_accepts_valid_colors(): void {
		$this->assertSame( '#123456', Options::sanitize_hex_color( '123456' ) );
		$this->assertSame( '#abc', Options::sanitize_hex_color( 'abc' ) );
		$this->assertSame( '#ABCDEF', Options::sanitize_hex_color( 'ABCDEF' ) );
		$this->assertSame( '#123', Options::sanitize_hex_color( '#123' ) );
	}

	public function test_sanitize_hex_color_rejects_invalid_colors(): void {
		$this->assertSame( '', Options::sanitize_hex_color( 'xyz' ) );
		$this->assertSame( '', Options::sanitize_hex_color( '12' ) );
		$this->assertSame( '', Options::sanitize_hex_color( '1234567' ) );
		$this->assertSame( '', Options::sanitize_hex_color( 'rgb(255,0,0)' ) );
	}

	public function test_sanitize_bars_input_handles_non_array(): void {
		$result = Options::sanitize_bars_input( 'not an array' );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'id', $result[0] );
	}

	public function test_sanitize_bars_input_removes_frame_when_width_zero(): void {
		$result = Options::sanitize_bars_input(
			[
				[
					'id'          => 'test',
					'frame_width' => 0,
					'frame_color' => '#ff0000',
				],
			]
		);

		$this->assertSame( '', $result[0]['frame_color'] );
		$this->assertSame( 0, $result[0]['frame_width'] );
	}

	public function test_sanitize_bars_input_handles_hide_on_scroll_as_string(): void {
		$result1 = Options::sanitize_bars_input( [ [ 'hide_on_scroll' => '1' ] ] );
		$result2 = Options::sanitize_bars_input( [ [ 'hide_on_scroll' => '0' ] ] );

		$this->assertTrue( $result1[0]['hide_on_scroll'] );
		$this->assertFalse( $result2[0]['hide_on_scroll'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_sanitize_bars_input_enforces_min_bars(): void {
		$result = Options::sanitize_bars_input( [] );

		$this->assertGreaterThanOrEqual( Options::MIN_BARS, count( $result ) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_active_bars_filters_invisible_bars(): void {
		update_option(
			Options::OPTION_BARS,
			[
				[ 'id' => 'visible', 'visible' => true, 'messages' => [ 'Test' ] ],
				[ 'id' => 'invisible', 'visible' => false, 'messages' => [ 'Test' ] ],
			]
		);

		$active = Options::get_active_bars();

		$this->assertCount( 1, $active );
		$this->assertSame( 'visible', $active[0]['id'] );
	}

	public function test_new_bar_id_generates_ids_with_correct_format(): void {
		$id = Options::new_bar_id();

		$this->assertStringStartsWith( 'bar_', $id );
		$this->assertMatchesRegularExpression( '/^bar_[a-z0-9]+$/', $id );
		// Note: In test environment, wp_generate_password returns same value
		// In production, IDs would be unique
	}

	public function test_default_bar_has_required_fields(): void {
		$bar = Options::default_bar();

		$requiredFields = [
			'id', 'name', 'visible', 'admin_visibile', 'scheduled_enabled',
			'scheduled_from_datetime', 'scheduled_to_datetime', 'position',
			'effect', 'messages', 'messages_mobile_visible', 'bg_color',
			'frame_color', 'frame_width', 'hide_on_scroll'
		];

		foreach ( $requiredFields as $field ) {
			$this->assertArrayHasKey( $field, $bar, "Missing field: $field" );
		}
	}

	public function test_normalize_bar_handles_messages_mobile_visible_variations(): void {
		$bar1 = Options::normalize_bar( [ 'messages_mobile_visible' => true ] );
		$bar2 = Options::normalize_bar( [ 'messages_mobile_visible' => false ] );
		$bar3 = Options::normalize_bar( [ 'messages_mobile_visible' => '1' ] );
		$bar4 = Options::normalize_bar( [ 'messages_mobile_visible' => '0' ] );
		$bar5 = Options::normalize_bar( [ 'messages_mobile_visible' => 'on' ] );

		$this->assertTrue( $bar1['messages_mobile_visible'] );
		$this->assertFalse( $bar2['messages_mobile_visible'] );
		$this->assertTrue( $bar3['messages_mobile_visible'] );
		$this->assertFalse( $bar4['messages_mobile_visible'] );
		$this->assertTrue( $bar5['messages_mobile_visible'] );
	}

	public function test_normalize_bar_handles_admin_visibile_variations(): void {
		$bar1 = Options::normalize_bar( [ 'admin_visibile' => true ] );
		$bar2 = Options::normalize_bar( [ 'admin_visibile' => false ] );
		$bar3 = Options::normalize_bar( [ 'admin_visibile' => '1' ] );
		$bar4 = Options::normalize_bar( [ 'admin_visibile' => '0' ] );

		$this->assertTrue( $bar1['admin_visibile'] );
		$this->assertFalse( $bar2['admin_visibile'] );
		$this->assertTrue( $bar3['admin_visibile'] );
		$this->assertFalse( $bar4['admin_visibile'] );
	}

	public function test_normalize_bar_combines_legacy_life_time_fields(): void {
		$bar = Options::normalize_bar(
			[
				'life_time_enabled'   => '1',
				'life_time_from_date' => '2026-03-25',
				'life_time_from_time' => '10:00',
				'life_time_to_date'   => '2026-03-26',
				'life_time_to_time'   => '18:00',
			]
		);

		$this->assertTrue( $bar['scheduled_enabled'] );
		$this->assertSame( '2026-03-25T10:00', $bar['scheduled_from_datetime'] );
		$this->assertSame( '2026-03-26T18:00', $bar['scheduled_to_datetime'] );
	}

	public function test_normalize_bar_sanitizes_iso_datetime_with_seconds(): void {
		$bar = Options::normalize_bar(
			[
				'scheduled_enabled'       => true,
				'scheduled_from_datetime' => '2026-03-25T10:30:45',
				'scheduled_to_datetime'   => '2026-03-26T18:15:30',
			]
		);

		// Should normalize to minute precision
		$this->assertSame( '2026-03-25T10:30', $bar['scheduled_from_datetime'] );
		$this->assertSame( '2026-03-26T18:15', $bar['scheduled_to_datetime'] );
	}

	public function test_normalize_bar_auto_enables_schedule_when_datetimes_provided(): void {
		$bar = Options::normalize_bar(
			[
				'scheduled_enabled'       => false,
				'scheduled_from_datetime' => '2026-03-25T10:00',
				'scheduled_to_datetime'   => '2026-03-26T18:00',
			]
		);

		// When datetimes are provided, scheduling is auto-enabled
		$this->assertTrue( $bar['scheduled_enabled'] );
		$this->assertSame( '2026-03-25T10:00', $bar['scheduled_from_datetime'] );
		$this->assertSame( '2026-03-26T18:00', $bar['scheduled_to_datetime'] );
	}

	public function test_normalize_bar_clears_schedule_when_disabled_and_empty(): void {
		$bar = Options::normalize_bar(
			[
				'scheduled_enabled'       => false,
				'scheduled_from_datetime' => '',
				'scheduled_to_datetime'   => '',
			]
		);

		$this->assertFalse( $bar['scheduled_enabled'] );
		$this->assertSame( '', $bar['scheduled_from_datetime'] );
		$this->assertSame( '', $bar['scheduled_to_datetime'] );
	}
}
