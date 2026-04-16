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
use FlexTopBar\FreePlan;
use FlexTopBar\PlanNameProvider;
use FlexTopBar\ProPlan;

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
		$free  = new FreePlan();

		$this->assertSame( $free->max_bars(), $flags->max_bars() );
		$this->assertSame( $free->max_messages(), $flags->max_messages() );
		$this->assertSame( $free->max_columns(), $flags->max_columns() );
		$this->assertSame( $free->schedule_enabled(), $flags->schedule_enabled() );
		$this->assertSame( $free->plan_name(), $flags->plan_name() );
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
		$pro   = new ProPlan();

		$this->assertSame( $pro->max_bars(), $flags->max_bars() );
		$this->assertSame( $pro->max_messages(), $flags->max_messages() );
		$this->assertSame( $pro->max_columns(), $flags->max_columns() );
		$this->assertSame( $pro->schedule_enabled(), $flags->schedule_enabled() );
		$this->assertSame( $pro->plan_name(), $flags->plan_name() );
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

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_ff_plan_is_case_insensitive_and_trimmed(): void {
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', '  PRO  ' );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 'pro', $flags->plan_name() );
		$this->assertTrue( $flags->schedule_enabled() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_unknown_ff_plan_defaults_to_free(): void {
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 'enterprise' );
		}
		FeatureFlags::reset_for_tests();
		$flags = FeatureFlags::instance();

		$this->assertSame( 'free', $flags->plan_name() );
		$this->assertFalse( $flags->schedule_enabled() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_non_string_ff_plan_is_ignored_and_provider_is_used(): void {
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 123 );
		}

		$provider = new class() implements PlanNameProvider {
			public function get_plan_name(): string {
				return 'pro';
			}
		};

		$flags = new FeatureFlags( $provider );

		$this->assertSame( 'pro', $flags->plan_name() );
		$this->assertTrue( $flags->schedule_enabled() );
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
