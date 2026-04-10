<?php
/**
 * Freemius plan feature reader.
 *
 * Reads numeric/boolean limits from the active plan's features so that changing
 * values in Freemius doesn't require a plugin release.
 *
 * @package FlexTopBar
 */
 
declare(strict_types=1);
 
namespace FlexTopBar;
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
final class FreemiusFlags {
	/**
	 * Freemius feature identifiers as configured in Freemius.
	 *
	 * Note: In Freemius UI, these are the "Feature ID"/slug values (not the title).
	 */
	private const FEATURE_NOTIFICATION_BARS   = 'notification bars';
	private const FEATURE_SCHEDULING          = 'scheduling';
	private const FEATURE_MAX_MESSAGES        = 'max messages';
	private const FEATURE_MAX_COLUMNS_PER_BAR = 'max columns per bar';

	/**
	 * @var array<int, mixed>
	 */
	private array $features = [];
 
	/**
	 * @param object|null $fs Freemius SDK instance.
	 */
	public function __construct( $fs ) {
		if ( is_object( $fs ) && method_exists( $fs, 'get_plan' ) ) {
			$plan = $fs->get_plan();
			if ( is_object( $plan ) && isset( $plan->features ) && is_array( $plan->features ) ) {
				$this->features = $plan->features;
			}
		}
	}

	/**
	 * Create instance from current Freemius context (if available).
	 */
	public static function current(): self {
		$fs = null;
		if ( function_exists( __NAMESPACE__ . '\\ftb_fs' ) ) {
			$maybe_fs = ftb_fs();
			if ( is_object( $maybe_fs ) ) {
				$fs = $maybe_fs;
			}
		}

		return new self( $fs );
	}
 
	public function int( string $feature_id ): ?int {
		$feature = $this->find_feature( $feature_id );
		if ( ! is_object( $feature ) ) {
			return null;
		}
		$raw = $feature->value ?? null;
		if ( $raw === null || $raw === '' || ! is_numeric( $raw ) ) {
			return null;
		}
		return (int) $raw;
	}
 
	public function bool( string $feature_id ): ?bool {
		$feature = $this->find_feature( $feature_id );
		if ( ! is_object( $feature ) ) {
			return null;
		}
		$raw = $feature->value ?? null;
		if ( $raw === null || $raw === '' ) {
			// Freemius often uses empty value for enabled boolean-ish features.
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

	public function max_bars(): ?int {
		return $this->int( self::FEATURE_NOTIFICATION_BARS );
	}

	public function schedule_enabled(): ?bool {
		return $this->bool( self::FEATURE_SCHEDULING );
	}

	public function max_messages(): ?int {
		return $this->int( self::FEATURE_MAX_MESSAGES );
	}

	public function max_columns(): ?int {
		return $this->int( self::FEATURE_MAX_COLUMNS_PER_BAR );
	}
 
	/**
	 * @return object|null
	 */
	private function find_feature( string $feature_id ) {
		foreach ( $this->features as $feature ) {
			if ( ! is_object( $feature ) ) {
				continue;
			}
			if ( isset( $feature->id ) && (string) $feature->id === $feature_id ) {
				return $feature;
			}
		}
		return null;
	}
}

