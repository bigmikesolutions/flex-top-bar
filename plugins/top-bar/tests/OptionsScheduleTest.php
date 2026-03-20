<?php
/**
 * Schedule-related unit tests for Options.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar\Tests;

use PHPUnit\Framework\TestCase;
use TopBar\Options;

final class OptionsScheduleTest extends TestCase {

	public function test_normalize_bar_keeps_valid_iso_datetime(): void {
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
		$bar = Options::normalize_bar(
			[
				'scheduled_enabled' => '0',
				'scheduled_from_datetime' => '2026-03-21T11:00',
				'scheduled_to_datetime' => '2026-03-21T12:30',
			]
		);

		$this->assertTrue( (bool) $bar['scheduled_enabled'] );
	}

	public function test_schedule_window_disabled_returns_true(): void {
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

