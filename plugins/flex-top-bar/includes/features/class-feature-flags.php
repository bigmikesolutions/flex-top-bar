<?php
/**
 * Centralized feature flags/limits derived from Freemius plan,
 * with optional plan override for local dev/CI.
 *
 * @package FlexTopBar
 */
declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Keep Freemius parsing isolated in one class, and load it here so no other
// plugin files need to manage include order.
if ( ! class_exists( __NAMESPACE__ . '\\FreemiusFlags' ) ) {
	if ( defined( 'TOP_BAR_PLUGIN_DIR' ) ) {
		require_once TOP_BAR_PLUGIN_DIR . 'includes/features/class-freemius-flags.php';
	} else {
		require_once __DIR__ . '/class-freemius-flags.php';
	}
}

if ( ! interface_exists( __NAMESPACE__ . '\\PlanNameProvider' ) ) {
	require_once __DIR__ . '/interface-plan-name-provider.php';
}

if ( ! interface_exists( __NAMESPACE__ . '\\FeaturePlan' ) ) {
	require_once __DIR__ . '/interface-feature-plan.php';
}

if ( ! class_exists( __NAMESPACE__ . '\\FreePlan' ) ) {
	require_once __DIR__ . '/class-free-plan.php';
}

if ( ! class_exists( __NAMESPACE__ . '\\ProPlan' ) ) {
	require_once __DIR__ . '/class-pro-plan.php';
}

final class FeatureFlags implements FeaturePlan {

	private static ?self $instance = null;

	// Plan override (local dev/CI).
	// Note: in this repo's Docker dev setup this is provided via `.env`.
	private const ENV_PLAN = 'FF_PLAN';

	private FeaturePlan $plan;

	public static function instance(): FeaturePlan {
		if ( self::$instance === null ) {
			self::$instance = new self( FreemiusFlags::current() );
		}
		return self::$instance;
	}

	public function __construct( PlanNameProvider $provider ) {
		// 1) FF_PLAN override (if set and non-empty).
		$override = self::plan_override_from_env();
		if ( $override !== null ) {
			$this->plan = self::plan_from_name( $override );
			return;
		}

		// 2) Plan name provider (Freemius by default).
		$this->plan = self::plan_from_name( $provider->get_plan_name() );
	}

	private static function plan_override_from_env(): ?string {
		if ( ! defined( self::ENV_PLAN ) ) {
			return null;
		}

		$value = constant( self::ENV_PLAN );
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );
		if ( $value === '' ) {
			return null;
		}

		return $value;
	}

	private static function plan_from_name( string $plan_name ): FeaturePlan {
		switch ( strtolower( trim( $plan_name ) ) ) {
			case 'pro':
				return new ProPlan();
			default:
				return new FreePlan();
		}
	}

	public function max_bars(): int {
		return $this->plan->max_bars();
	}

	public function max_messages(): int {
		return $this->plan->max_messages();
	}

	public function max_columns(): int {
		return $this->plan->max_columns();
	}

	public function schedule_enabled(): bool {
		return $this->plan->schedule_enabled();
	}

	public function plan_name(): string {
		return $this->plan->plan_name();
	}

	/**
	 * Reset instance (useful for tests).
	 *
	 * @internal
	 */
	public static function reset_for_tests(): void {
		self::$instance = null;
	}
}

