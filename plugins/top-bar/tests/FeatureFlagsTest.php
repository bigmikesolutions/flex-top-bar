<?php
/**
 * Unit tests for FeatureFlags class.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar\Tests;

use PHPUnit\Framework\TestCase;
use TopBar\FeatureFlags;

final class FeatureFlagsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// Reset singleton before each test
		FeatureFlags::reset_for_tests();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_defaults_when_no_constants_defined(): void {
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 1, $flags->max_bars() );
		$this->assertSame( 1, $flags->max_messages() );
		$this->assertSame( 4, $flags->max_columns() );
		$this->assertFalse( $flags->is_schedule_enabled() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loads_max_bars_from_constant(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 5 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 5, $flags->max_bars() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_enforces_minimum_bars_of_one(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 0 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 1, $flags->max_bars() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_enforces_minimum_bars_with_negative_value(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', -10 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 1, $flags->max_bars() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loads_max_messages_from_constant(): void {
		if ( ! defined( 'FF_MAX_MESSAGES' ) ) {
			define( 'FF_MAX_MESSAGES', 10 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 10, $flags->max_messages() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loads_max_columns_from_constant(): void {
		if ( ! defined( 'FF_MAX_COLUMNS' ) ) {
			define( 'FF_MAX_COLUMNS', 3 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 3, $flags->max_columns() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_enforces_minimum_columns_of_one(): void {
		if ( ! defined( 'FF_MAX_COLUMNS' ) ) {
			define( 'FF_MAX_COLUMNS', 0 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 1, $flags->max_columns() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_enforces_maximum_columns_cap(): void {
		if ( ! defined( 'FF_MAX_COLUMNS' ) ) {
			define( 'FF_MAX_COLUMNS', 999 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 4, $flags->max_columns() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_enforces_minimum_messages_of_one(): void {
		if ( ! defined( 'FF_MAX_MESSAGES' ) ) {
			define( 'FF_MAX_MESSAGES', 0 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 1, $flags->max_messages() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_enforces_maximum_messages_of_fifty(): void {
		if ( ! defined( 'FF_MAX_MESSAGES' ) ) {
			define( 'FF_MAX_MESSAGES', 100 );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 50, $flags->max_messages() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loads_schedule_enabled_true_from_constant(): void {
		if ( ! defined( 'FF_SCHEDULE' ) ) {
			define( 'FF_SCHEDULE', true );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertTrue( $flags->is_schedule_enabled() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loads_schedule_enabled_false_from_constant(): void {
		if ( ! defined( 'FF_SCHEDULE' ) ) {
			define( 'FF_SCHEDULE', false );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertFalse( $flags->is_schedule_enabled() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_handles_non_numeric_max_bars_constant(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 'invalid' );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		// Should fall back to default
		$this->assertSame( 1, $flags->max_bars() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_handles_non_numeric_max_messages_constant(): void {
		if ( ! defined( 'FF_MAX_MESSAGES' ) ) {
			define( 'FF_MAX_MESSAGES', 'invalid' );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		// Should fall back to default
		$this->assertSame( 1, $flags->max_messages() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_handles_non_numeric_max_columns_constant(): void {
		if ( ! defined( 'FF_MAX_COLUMNS' ) ) {
			define( 'FF_MAX_COLUMNS', 'invalid' );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		// Should fall back to default
		$this->assertSame( 4, $flags->max_columns() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loads_all_constants_together(): void {
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 20 );
		}
		if ( ! defined( 'FF_MAX_MESSAGES' ) ) {
			define( 'FF_MAX_MESSAGES', 30 );
		}
		if ( ! defined( 'FF_MAX_COLUMNS' ) ) {
			define( 'FF_MAX_COLUMNS', 2 );
		}
		if ( ! defined( 'FF_SCHEDULE' ) ) {
			define( 'FF_SCHEDULE', true );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 20, $flags->max_bars() );
		$this->assertSame( 30, $flags->max_messages() );
		$this->assertSame( 2, $flags->max_columns() );
		$this->assertTrue( $flags->is_schedule_enabled() );
	}

	public function test_singleton_returns_same_instance(): void {
		$instance1 = FeatureFlags::instance();
		$instance2 = FeatureFlags::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	public function test_reset_for_tests_clears_instance(): void {
		$instance1 = FeatureFlags::instance();
		FeatureFlags::reset_for_tests();
		$instance2 = FeatureFlags::instance();

		$this->assertNotSame( $instance1, $instance2 );
	}
}
