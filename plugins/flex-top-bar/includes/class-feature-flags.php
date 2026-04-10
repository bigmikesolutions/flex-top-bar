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

	// Env var overrides (local dev/CI).
	private const ENV_MAX_BARS     = 'FF_MAX_BARS';
	private const ENV_MAX_MESSAGES = 'FF_MAX_MESSAGES';
	private const ENV_MAX_COLUMNS  = 'FF_MAX_COLUMNS';
	private const ENV_SCHEDULE     = 'FF_SCHEDULE';

	/**
	 * Plan -> defaults. Freemius decides the active plan.
	 */
	private const PLAN_CONFIG = [
		self::PLAN_FREE => [
			'max_bars'         => 1,
			'max_messages'     => 1,
			'max_columns'      => 1,
			'schedule_enabled' => false,
		],
		self::PLAN_PRO  => [
			'max_bars'         => 5,
			'max_messages'     => 5,
			'max_columns'      => 4,
			'schedule_enabled' => true,
		],
	];

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
		$this->load_config();
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

	private function load_config(): void {
		$config = self::PLAN_CONFIG[ $this->plan ] ?? self::PLAN_CONFIG[ self::PLAN_FREE ];

		$this->max_bars         = (int) ( $config['max_bars'] ?? 1 );
		$this->max_messages     = (int) ( $config['max_messages'] ?? 1 );
		$this->max_columns      = (int) ( $config['max_columns'] ?? 1 );
		$this->schedule_enabled = (bool) ( $config['schedule_enabled'] ?? false );

		$this->apply_env_overrides();
	}

	private function apply_env_overrides(): void {
		$this->max_bars     = self::override_int_env( self::ENV_MAX_BARS, $this->max_bars, 1, null );
		$this->max_messages = self::override_int_env( self::ENV_MAX_MESSAGES, $this->max_messages, 1, 50 );
		$this->max_columns  = self::override_int_env( self::ENV_MAX_COLUMNS, $this->max_columns, 1, 50 );
		$this->schedule_enabled = self::override_bool_env( self::ENV_SCHEDULE, $this->schedule_enabled );
	}

	private static function override_int_env( string $env_var, int $fallback, int $min, ?int $max ): int {
		$raw = getenv( $env_var );
		if ( $raw === false || $raw === '' ) {
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