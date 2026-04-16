<?php
/**
 * Built-in free plan settings.
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

final class FreePlan implements FeaturePlan {
	public function max_bars(): int {
		return 1;
	}

	public function schedule_enabled(): bool {
		return false;
	}

	public function max_messages(): int {
		return 1;
	}

	public function max_columns(): int {
		return 1;
	}

	public function plan_name(): string {
		return 'free';
	}
}

