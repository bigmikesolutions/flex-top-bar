<?php
/**
 * Schedule-related unit tests for Options.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar\Tests;

use PHPUnit\Framework\TestCase;
use TopBar\FeatureFlags;
use TopBar\Options;

final class OptionsScheduleTest extends TestCase {

	private function enableScheduleFeature(): void {
		if ( ! defined( 'FF_SCHEDULE' ) ) {
			define( 'FF_SCHEDULE', true );
		}
		// Reset FeatureFlags to pick up the constant
		FeatureFlags::reset_for_tests();
	}

	public function test_normalize_bar_keeps_valid_iso_datetime(): void {
		$this->enableScheduleFeature();
		$bar = Options::normalize_bar(
			[
				'id' => 'bar_test',
				'name' => 'Bar',
				'scheduled_enabled' => '1',
				'scheduled_from_datetime' => '2026-03-21T11:00',
				'scheduled_to_datetime' => '2026-03-21T12:30',
			]
		);

		$this->assertTrue( (bool) $bar['scheduled_enabled'] );
		$this->assertSame( '2026-03-21T11:00', $bar['scheduled_from_datetime'] );
		$this->assertSame( '2026-03-21T12:30', $bar['scheduled_to_datetime'] );
	}

	public function test_normalize_bar_combines_legacy_date_time_fields(): void {
		$this->enableScheduleFeature();
		$bar = Options::normalize_bar(
			[
				'scheduled_enabled' => '1',
				'scheduled_from_date' => '2026-03-21',
				'scheduled_from_time' => '11:00',
				'scheduled_to_date' => '2026-03-21',
				'scheduled_to_time' => '12:30',
			]
		);

		$this->assertSame( '2026-03-21T11:00', $bar['scheduled_from_datetime'] );
		$this->assertSame( '2026-03-21T12:30', $bar['scheduled_to_datetime'] );
	}

	public function test_normalize_bar_enables_schedule_when_datetimes_provided(): void {
		$this->enableScheduleFeature();
		$bar = Options::normalize_bar(
			[
				'scheduled_enabled' => '0',
				'scheduled_from_datetime' => '2026-03-21T11:00',
				'scheduled_to_datetime' => '2026-03-21T12:30',
			]
		);

		$this->assertTrue( (bool) $bar['scheduled_enabled'] );
	}

	public function test_normalize_bar_saves_messages_mobile_visible_as_boolean(): void {
		$off_bar = Options::normalize_bar(
			[
				'messages_mobile_visible' => '0',
			]
		);
		$on_bar = Options::normalize_bar(
			[
				'messages_mobile_visible' => '1',
			]
		);

		$this->assertFalse( $off_bar['messages_mobile_visible'] );
		$this->assertTrue( $on_bar['messages_mobile_visible'] );
	}

	public function test_max_messages_uses_feature_flag_when_defined(): void {
		if ( ! defined( 'FF_MAX_MESSAGES' ) ) {
			define( 'FF_MAX_MESSAGES', 2 );
		}
		// Reset FeatureFlags to pick up the constant
		FeatureFlags::reset_for_tests();
		$this->assertSame( 2, FeatureFlags::instance()->max_messages() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_schedule_window_feature_flag_off_always_returns_true(): void {
		if ( ! defined( 'FF_SCHEDULE' ) ) {
			define( 'FF_SCHEDULE', false );
		}
		// Reset FeatureFlags to pick up the constant
		FeatureFlags::reset_for_tests();
		$method = new \ReflectionMethod( Options::class, 'is_bar_in_schedule_window' );

		$result = $method->invoke(
			null,
			[
				'scheduled_enabled' => true,
				'scheduled_from_datetime' => '2026-03-21T11:00',
				'scheduled_to_datetime' => '',
			]
		);

		$this->assertTrue( $result );
	}

	/**
	 * When scheduling is not part of the plan (`FF_SCHEDULE=false`), bars should not be filtered out
	 * by schedule window logic.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_active_bars_ignores_schedule_when_feature_flag_off(): void {
		if ( ! defined( 'FF_SCHEDULE' ) ) {
			define( 'FF_SCHEDULE', false );
		}
		// Reset FeatureFlags to pick up the constant
		FeatureFlags::reset_for_tests();
		$GLOBALS['wp_test_options'] = [];

		update_option(
			Options::OPTION_BARS,
			[
				[
					'id' => 'bar_scheduled',
					'visible' => true,
					'scheduled_enabled' => true,
					'scheduled_from_datetime' => '2099-03-21T11:00',
					'scheduled_to_datetime' => '2099-03-21T12:30',
					'position' => 'top',
					'effect' => 'none',
					'messages' => [ 'Hello', '' ],
				],
			]
		);

		$active = Options::get_active_bars();
		$this->assertCount( 1, $active );
		$this->assertSame( 'bar_scheduled', $active[0]['id'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_active_bars_respects_max_bars_feature_flag(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 1 );
		}
		if ( ! defined( 'FF_SCHEDULE' ) ) {
			define( 'FF_SCHEDULE', false );
		}
		// Reset FeatureFlags to pick up the constant
		FeatureFlags::reset_for_tests();
		$GLOBALS['wp_test_options'] = [];

		update_option(
			Options::OPTION_BARS,
			[
				[
					'id' => 'bar_one',
					'visible' => true,
					'scheduled_enabled' => false,
					'position' => 'top',
					'effect' => 'none',
					'messages' => [ 'One', '' ],
				],
				[
					'id' => 'bar_two',
					'visible' => true,
					'scheduled_enabled' => false,
					'position' => 'top',
					'effect' => 'none',
					'messages' => [ 'Two', '' ],
				],
			]
		);

		$active = Options::get_active_bars();
		$this->assertCount( 1, $active );
		$this->assertSame( 'bar_one', $active[0]['id'] );
	}

	public function test_schedule_window_disabled_returns_true(): void {
		$this->enableScheduleFeature();
		$method = new \ReflectionMethod( Options::class, 'is_bar_in_schedule_window' );

		$result = $method->invoke(
			null,
			[
				'scheduled_enabled' => false,
				'scheduled_from_datetime' => '',
				'scheduled_to_datetime' => '',
			]
		);

		$this->assertTrue( $result );
	}

	public function test_schedule_window_enabled_with_missing_values_returns_false(): void {
		$this->enableScheduleFeature();
		$method = new \ReflectionMethod( Options::class, 'is_bar_in_schedule_window' );

		$result = $method->invoke(
			null,
			[
				'scheduled_enabled' => true,
				'scheduled_from_datetime' => '2026-03-21T11:00',
				'scheduled_to_datetime' => '',
			]
		);

		$this->assertFalse( $result );
	}
}

