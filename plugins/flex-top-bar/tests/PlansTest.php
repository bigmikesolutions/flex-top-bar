<?php
/**
 * Unit tests for built-in FeaturePlan implementations.
 *
 * @package TopBar
 */
declare(strict_types=1);

namespace FlexTopBar\Tests;

use FlexTopBar\FreePlan;
use FlexTopBar\ProPlan;
use PHPUnit\Framework\TestCase;

final class PlansTest extends TestCase {

	public function test_free_plan_values(): void {
		$plan = new FreePlan();

		$this->assertSame( 1, $plan->max_bars() );
		$this->assertSame( 1, $plan->max_messages() );
		$this->assertSame( 1, $plan->max_columns() );
		$this->assertFalse( $plan->schedule_enabled() );
		$this->assertSame( 'free', $plan->plan_name() );
	}

	public function test_pro_plan_values(): void {
		$plan = new ProPlan();

		$this->assertSame( 5, $plan->max_bars() );
		$this->assertSame( 5, $plan->max_messages() );
		$this->assertSame( 4, $plan->max_columns() );
		$this->assertTrue( $plan->schedule_enabled() );
		$this->assertSame( 'pro', $plan->plan_name() );
	}
}

