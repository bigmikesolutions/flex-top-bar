<?php
/**
 * Centralized feature flag management via Freemius.
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

	private string $plan = 'free';

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
		$this->resolve_plan();
		$this->load_from_freemius();
	}

	/**
	 * Get active Freemius plan.
	 */
	private function resolve_plan(): void {
		if ( function_exists( __NAMESPACE__ . '\\ftb_fs' ) ) {
			$fs = ftb_fs();

			if ( is_object( $fs ) && method_exists( $fs, 'get_plan' ) ) {
				$plan = $fs->get_plan();

				if ( is_object( $plan ) && isset( $plan->id ) ) {
					$this->plan = (string) $plan->id;
					return;
				}
			}
		}
	}

	/**
	 * Load feature flags based on plan constants.
	 */
	private function load_from_freemius(): void {

		// Defaults already set in properties (free plan)

		if ( defined( 'FF_MAX_BARS' ) && $this->plan === 'pro' ) {
			$raw = constant( 'FF_MAX_BARS' );
			if ( is_numeric( $raw ) ) {
				$this->max_bars = max( 1, (int) $raw );
			}
		}

		if ( defined( 'FF_MAX_MESSAGES' ) && $this->plan === 'pro' ) {
			$raw = constant( 'FF_MAX_MESSAGES' );
			if ( is_numeric( $raw ) ) {
				$this->max_messages = max( 1, min( 50, (int) $raw ) );
			}
		}

		if ( defined( 'FF_MAX_COLUMNS' ) && $this->plan === 'pro' ) {
			$raw = constant( 'FF_MAX_COLUMNS' );
			if ( is_numeric( $raw ) ) {
				$this->max_columns = max( 1, min( 50, (int) $raw ) );
			}
		}

		if ( defined( 'FF_SCHEDULE' ) && $this->plan === 'pro' ) {
			$this->schedule_enabled = (bool) constant( 'FF_SCHEDULE' );
		}
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

	public static function reset_for_tests(): void {
		self::$instance = null;
	}
}