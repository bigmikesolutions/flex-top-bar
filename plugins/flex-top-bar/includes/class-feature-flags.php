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

// Keep Freemius parsing isolated in one class, and load it here so no other
// plugin files need to manage include order.
if ( ! class_exists( __NAMESPACE__ . '\\FreemiusFlags' ) ) {
	if ( defined( 'TOP_BAR_PLUGIN_DIR' ) ) {
		require_once TOP_BAR_PLUGIN_DIR . 'includes/class-freemius-flags.php';
	} else {
		require_once __DIR__ . '/class-freemius-flags.php';
	}
}

final class FeatureFlags {

	private static ?self $instance = null;

	// Env var overrides (local dev/CI) — evaluated last.
	// Note: in this repo's Docker dev setup these are provided via `.env`.
	private const ENV_MAX_BARS     = 'FF_MAX_BARS';
	private const ENV_MAX_MESSAGES = 'FF_MAX_MESSAGES';
	private const ENV_MAX_COLUMNS  = 'FF_MAX_COLUMNS';
	private const ENV_SCHEDULE     = 'FF_SCHEDULE';

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
		$this->load_from_freemius();
	}

	private function load_from_freemius(): void {
		// Safe defaults when Freemius isn't available or has no feature values.
		$this->max_bars         = 1;
		$this->max_messages     = 1;
		$this->max_columns      = 1;
		$this->schedule_enabled = false;

		// 1) Prefer Freemius plan features (so changing values in Freemius doesn't require a plugin release).
		$ff = FreemiusFlags::current();

		$max_bars = $ff->max_bars();
		if ( $max_bars !== null ) {
			$this->max_bars = self::clamp_int( $max_bars, 1, null );
		}

		$max_messages = $ff->max_messages();
		if ( $max_messages !== null ) {
			$this->max_messages = self::clamp_int( $max_messages, 1, 50 );
		}

		$max_columns = $ff->max_columns();
		if ( $max_columns !== null ) {
			$this->max_columns = self::clamp_int( $max_columns, 1, 50 );
		}

		$schedule_enabled = $ff->schedule_enabled();
		if ( $schedule_enabled !== null ) {
			$this->schedule_enabled = $schedule_enabled;
		}

		// 2) Env overrides (local dev/CI) last.
		$this->max_bars     = self::override_int_env( self::ENV_MAX_BARS, $this->max_bars, 1, null );
		$this->max_messages = self::override_int_env( self::ENV_MAX_MESSAGES, $this->max_messages, 1, 50 );
		$this->max_columns  = self::override_int_env( self::ENV_MAX_COLUMNS, $this->max_columns, 1, 50 );
		$this->schedule_enabled = self::override_bool_env( self::ENV_SCHEDULE, $this->schedule_enabled );
	}

	// Freemius feature parsing lives in FreemiusFlags.

	private static function override_int_env( string $env_var, int $fallback, int $min, ?int $max ): int {
		// Override only via PHP constants (e.g. define('FF_MAX_BARS', 5)).
		if ( defined( $env_var ) ) {
			$const = constant( $env_var );
			if ( is_numeric( $const ) ) {
				return self::clamp_int( (int) $const, $min, $max );
			}
		}

		return self::clamp_int( $fallback, $min, $max );
	}

	private static function override_bool_env( string $env_var, bool $fallback ): bool {
		// Override only via PHP constants (e.g. define('FF_SCHEDULE', 1)).
		if ( defined( $env_var ) ) {
			return (bool) constant( $env_var );
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