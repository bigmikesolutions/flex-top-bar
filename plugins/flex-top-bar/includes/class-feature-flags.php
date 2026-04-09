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

	private int $max_bars = 1;
	private int $max_messages = 1;
	private int $max_columns = 1;
	private bool $schedule_enabled = false;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_from_freemius();
	}

	/**
	 * Load feature flags from constants defined by Freemius plan.
	 */
	private function load_from_freemius(): void {
		// Load from constants defined by Freemius plan
		if ( defined( 'FF_MAX_BARS' ) ) {
			$raw = constant( 'FF_MAX_BARS' );
			if ( is_numeric( $raw ) ) {
				$this->max_bars = max( 1, (int) $raw );
			}
		}

		if ( defined( 'FF_MAX_MESSAGES' ) ) {
			$raw = constant( 'FF_MAX_MESSAGES' );
			if ( is_numeric( $raw ) ) {
				$this->max_messages = max( 1, min( 50, (int) $raw ) );
			}
		}

		if ( defined( 'FF_MAX_COLUMNS' ) ) {
			$raw = constant( 'FF_MAX_COLUMNS' );
			if ( is_numeric( $raw ) ) {
				$this->max_columns = max( 1, min( 50, (int) $raw ) );
			}
		}

		if ( defined( 'FF_SCHEDULE' ) ) {
			$this->schedule_enabled = (bool) constant( 'FF_SCHEDULE' );
		}
	}

	/**
	 * Maximum number of bars allowed in the current plan.
	 */
	public function max_bars(): int {
		return $this->max_bars;
	}

	/**
	 * Maximum number of messages per bar allowed in the current plan.
	 */
	public function max_messages(): int {
		return $this->max_messages;
	}

	/**
	 * Maximum number of columns per bar allowed in the current plan.
	 */
	public function max_columns(): int {
		return $this->max_columns;
	}

	/**
	 * Whether scheduling feature is enabled in the current plan.
	 */
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
