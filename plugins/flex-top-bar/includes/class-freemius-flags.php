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

	private const PLAN_PRO = 'pro';

	private ?string $plan_name = null;
 
	/**
	 * @param object|null $fs Freemius SDK instance.
	 */
	public function __construct( $fs ) {
		if ( is_object( $fs ) && method_exists( $fs, 'get_plan' ) ) {
			$plan = $fs->get_plan();
			if ( is_object( $plan ) ) {
				$title = $plan->title ?? null;
				$name  = $plan->name ?? null;
				$id    = $plan->id ?? null;

				if ( is_string( $title ) && $title !== '' ) {
					$this->plan_name = $title;
				} elseif ( is_string( $name ) && $name !== '' ) {
					$this->plan_name = $name;
				} elseif ( ( is_string( $id ) && $id !== '' ) || is_numeric( $id ) ) {
					$this->plan_name = (string) $id;
				}

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
 
	public function max_bars(): ?int {
		switch ( $this->plan_name_normalized() ) {
			case self::PLAN_PRO:
				return 5;
			default:
				return 1;
		}
	}

	public function schedule_enabled(): ?bool {
		switch ( $this->plan_name_normalized() ) {
			case self::PLAN_PRO:
				return true;
			default:
				return false;
		}
	}

	public function max_messages(): ?int {
		switch ( $this->plan_name_normalized() ) {
			case self::PLAN_PRO:
				return 5;
			default:
				return 1;
		}
	}

	public function max_columns(): ?int {
		switch ( $this->plan_name_normalized() ) {
			case self::PLAN_PRO:
				return 4;
			default:
				return 1;
		}
	}

	public function plan_name(): ?string {
		return $this->plan_name;
	}

	private function plan_name_normalized(): string {
		return strtolower( trim( (string) $this->plan_name ) );
	}
}

