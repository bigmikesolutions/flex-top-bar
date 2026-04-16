<?php
/**
 * Freemius plan feature reader.
 *
 * Reads the active plan name from Freemius.
 *
 * @package FlexTopBar
 */
 
declare(strict_types=1);
 
namespace FlexTopBar;
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
if ( ! interface_exists( __NAMESPACE__ . '\\PlanNameProvider' ) ) {
	require_once __DIR__ . '/interface-plan-name-provider.php';
}

final class FreemiusFlags implements PlanNameProvider {

	private string $plan_name = 'free';
 
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
 
	public function get_plan_name(): string {
		return $this->plan_name;
	}
}

