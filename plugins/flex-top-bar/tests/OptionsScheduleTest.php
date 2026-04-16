<?php
/**
 * Schedule-related unit tests for Options.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace FlexTopBar\Tests;

use PHPUnit\Framework\TestCase;
use FlexTopBar\FeatureFlags;
use FlexTopBar\Options;

final class OptionsScheduleTest extends TestCase {

	private function enableScheduleFeature(): void {
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 'pro' );
		}
		// Reset FeatureFlags to pick up the constant.
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
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 'pro' );
		}
		// Reset FeatureFlags to pick up the constant.
		FeatureFlags::reset_for_tests();
		$this->assertSame( 5, FeatureFlags::instance()->max_messages() );
	}

	/**
	 * Scheduling is now handled client-side by Vue, so get_active_bars() only checks visibility.
	 * Bars with future schedules are returned (Vue will filter them client-side).
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_active_bars_returns_visible_bars_regardless_of_schedule(): void {
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
		$this->assertCount( 1, $active, 'Visible bar should be returned regardless of schedule (Vue handles filtering)' );
		$this->assertSame( 'bar_scheduled', $active[0]['id'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_active_bars_respects_max_bars_feature_flag(): void {
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 'free' );
		}
		// Reset FeatureFlags to pick up the constant.
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

}

