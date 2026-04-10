<?php
/**
 * Centralized feature flags/limits derived from Freemius plan,
 * with optional env var overrides for local dev/CI.
 *
 * @package FlexTopBar
 */

declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FeatureFlags {

	private static ?self $instance = null;

	private const PLAN_FREE = 'free';
	private const PLAN_PRO  = 'pro';

	// Freemius "feature slugs/IDs" (stable identifiers; don't rename in Freemius).
	private const FEATURE_MAX_BARS     = 'max_bars';
	private const FEATURE_MAX_MESSAGES = 'max_messages';
	private const FEATURE_MAX_COLUMNS  = 'max_columns';
	private const FEATURE_SCHEDULE     = 'schedule_enabled';

	// Env var overrides (local dev/CI) — evaluated last.
	// Note: in this repo's Docker dev setup these are provided via `.env`.
	private const ENV_MAX_BARS     = 'FF_MAX_BARS';
	private const ENV_MAX_MESSAGES = 'FF_MAX_MESSAGES';
	private const ENV_MAX_COLUMNS  = 'FF_MAX_COLUMNS';
	private const ENV_SCHEDULE     = 'FF_SCHEDULE';

	private string $plan = self::PLAN_FREE;

	private int $max_bars = 1;
	private int $max_messages = 1;
	private int $max_columns = 1;
	private bool $schedule_enabled = false;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->plan = $this->resolve_plan_from_freemius();
		$this->load_from_freemius();
	}

	private function resolve_plan_from_freemius(): string {
		if ( ! function_exists( __NAMESPACE__ . '\\ftb_fs' ) ) {
			return self::PLAN_FREE;
		}

		$fs = ftb_fs();
		if ( ! is_object( $fs ) ) {
			return self::PLAN_FREE;
		}

		// Prefer premium_only variant so logic can be stripped from free builds.
		if ( method_exists( $fs, 'is_plan__premium_only' ) ) {
			if ( (bool) $fs->is_plan__premium_only( self::PLAN_PRO, true ) ) {
				return self::PLAN_PRO;
			}
			return self::PLAN_FREE;
		}

		if ( method_exists( $fs, 'is_plan' ) ) {
			if ( (bool) $fs->is_plan( self::PLAN_PRO, true ) ) {
				return self::PLAN_PRO;
			}
			return self::PLAN_FREE;
		}

		// Fallback for SDKs without is_plan().
		if ( method_exists( $fs, 'is_free_plan' ) && ! (bool) $fs->is_free_plan() ) {
			// If SDK can tell it's NOT free, assume PRO-like.
			return self::PLAN_PRO;
		}

		return self::PLAN_FREE;
	}

	private function load_from_freemius(): void {
		// Safe defaults when Freemius isn't available or has no feature values.
		$this->max_bars         = 1;
		$this->max_messages     = 1;
		$this->max_columns      = 1;
		$this->schedule_enabled = false;

		// 1) Prefer Freemius plan features (so changing values in Freemius doesn't require a plugin release).
		if ( function_exists( __NAMESPACE__ . '\\ftb_fs' ) ) {
			$fs = ftb_fs();
			if ( is_object( $fs ) && method_exists( $fs, 'get_plan' ) ) {
				$plan = $fs->get_plan();
				$this->apply_plan_features( $plan );
			}
		}

		// 2) Env overrides (local dev/CI) last.
		$this->max_bars     = self::override_int_env( self::ENV_MAX_BARS, $this->max_bars, 1, null );
		$this->max_messages = self::override_int_env( self::ENV_MAX_MESSAGES, $this->max_messages, 1, 50 );
		$this->max_columns  = self::override_int_env( self::ENV_MAX_COLUMNS, $this->max_columns, 1, 50 );
		$this->schedule_enabled = self::override_bool_env( self::ENV_SCHEDULE, $this->schedule_enabled );
	}

	private function apply_plan_features( $plan ): void {
		if ( ! is_object( $plan ) || ! isset( $plan->features ) || ! is_array( $plan->features ) ) {
			return;
		}

		$max_bars = self::read_plan_feature_int( $plan->features, self::FEATURE_MAX_BARS );
		if ( $max_bars !== null ) {
			$this->max_bars = self::clamp_int( $max_bars, 1, null );
		}

		$max_messages = self::read_plan_feature_int( $plan->features, self::FEATURE_MAX_MESSAGES );
		if ( $max_messages !== null ) {
			$this->max_messages = self::clamp_int( $max_messages, 1, 50 );
		}

		$max_columns = self::read_plan_feature_int( $plan->features, self::FEATURE_MAX_COLUMNS );
		if ( $max_columns !== null ) {
			$this->max_columns = self::clamp_int( $max_columns, 1, 50 );
		}

		$schedule_enabled = self::read_plan_feature_bool( $plan->features, self::FEATURE_SCHEDULE );
		if ( $schedule_enabled !== null ) {
			$this->schedule_enabled = $schedule_enabled;
		}
	}

	/**
	 * @param array<int, mixed> $features
	 */
	private static function read_plan_feature_int( array $features, string $feature_id ): ?int {
		$feature = self::find_plan_feature( $features, $feature_id );
		if ( ! is_object( $feature ) ) {
			return null;
		}
		$raw = $feature->value ?? null;
		if ( $raw === null || $raw === '' ) {
			return null;
		}
		if ( ! is_numeric( $raw ) ) {
			return null;
		}
		return (int) $raw;
	}

	/**
	 * @param array<int, mixed> $features
	 */
	private static function read_plan_feature_bool( array $features, string $feature_id ): ?bool {
		$feature = self::find_plan_feature( $features, $feature_id );
		if ( ! is_object( $feature ) ) {
			return null;
		}
		$raw = $feature->value ?? null;
		if ( $raw === null || $raw === '' ) {
			// In Freemius UI, boolean-like features often have empty value when enabled.
			return true;
		}
		if ( is_bool( $raw ) ) {
			return $raw;
		}
		$normalized = strtolower( trim( (string) $raw ) );
		if ( in_array( $normalized, [ '1', 'true', 'yes', 'on' ], true ) ) {
			return true;
		}
		if ( in_array( $normalized, [ '0', 'false', 'no', 'off' ], true ) ) {
			return false;
		}
		return null;
	}

	/**
	 * @param array<int, mixed> $features
	 * @return object|null
	 */
	private static function find_plan_feature( array $features, string $feature_id ) {
		foreach ( $features as $feature ) {
			if ( ! is_object( $feature ) ) {
				continue;
			}
			if ( isset( $feature->id ) && (string) $feature->id === $feature_id ) {
				return $feature;
			}
		}
		return null;
	}

	private static function override_int_env( string $env_var, int $fallback, int $min, ?int $max ): int {
		$raw = getenv( $env_var );
		if ( $raw === false || $raw === '' ) {
			// Support test/dev overrides via PHP constants too (e.g. define('FF_MAX_BARS', 5)).
			if ( defined( $env_var ) ) {
				$const = constant( $env_var );
				if ( is_numeric( $const ) ) {
					return self::clamp_int( (int) $const, $min, $max );
				}
			}

			return self::clamp_int( $fallback, $min, $max );
		}

		if ( ! is_numeric( $raw ) ) {
			return self::clamp_int( $fallback, $min, $max );
		}

		return self::clamp_int( (int) $raw, $min, $max );
	}

	private static function override_bool_env( string $env_var, bool $fallback ): bool {
		$raw = getenv( $env_var );
		if ( $raw === false || $raw === '' ) {
			// Support test/dev overrides via PHP constants too.
			if ( defined( $env_var ) ) {
				return (bool) constant( $env_var );
			}

			return $fallback;
		}

		$normalized = strtolower( trim( (string) $raw ) );
		if ( in_array( $normalized, [ '1', 'true', 'yes', 'on' ], true ) ) {
			return true;
		}
		if ( in_array( $normalized, [ '0', 'false', 'no', 'off' ], true ) ) {
			return false;
		}

		return $fallback;
	}

	private static function clamp_int( int $value, int $min, ?int $max ): int {
		if ( $value < $min ) {
			return $min;
		}
		if ( $max !== null && $value > $max ) {
			return $max;
		}
		return $value;
	}

	/**
	 * Plan (for debugging or admin UI).
	 */
	public function plan(): string {
		return $this->plan;
	}

	public function max_bars(): int {
		return $this->max_bars;
	}

	public function max_messages(): int {
		return $this->max_messages;
	}

	public function max_columns(): int {
		return $this->max_columns;
	}

	public function is_schedule_enabled(): bool {
		return $this->schedule_enabled;
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