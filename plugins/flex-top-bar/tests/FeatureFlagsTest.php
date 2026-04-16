<?php
/**
 * Unit tests for FeatureFlags class.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace FlexTopBar\Tests;

use PHPUnit\Framework\TestCase;
use FlexTopBar\FeatureFlags;

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
		$this->assertSame( 1, $flags->max_columns() );
		$this->assertFalse( $flags->is_schedule_enabled() );
		$this->assertSame( 'free', $flags->plan_name() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_uses_ff_plan_when_defined_and_not_empty(): void {
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 'pro' );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 5, $flags->max_bars() );
		$this->assertSame( 5, $flags->max_messages() );
		$this->assertSame( 4, $flags->max_columns() );
		$this->assertTrue( $flags->is_schedule_enabled() );
		$this->assertSame( 'pro', $flags->plan_name() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ignores_empty_ff_plan(): void {
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', '   ' );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 'free', $flags->plan_name() );
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
