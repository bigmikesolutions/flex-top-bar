<?php
/**
 * Built-in pro plan settings.
 *
 * @package FlexTopBar
 */
declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( __NAMESPACE__ . '\\FeaturePlan' ) ) {
	require_once __DIR__ . '/interface-feature-plan.php';
}

final class ProPlan implements FeaturePlan {
	public function max_bars(): int {
		return 5;
	}

	public function schedule_enabled(): bool {
		return true;
	}

	public function max_messages(): int {
		return 5;
	}

	public function max_columns(): int {
		return 4;
	}

	public function plan_name(): string {
		return 'pro';
	}
}

