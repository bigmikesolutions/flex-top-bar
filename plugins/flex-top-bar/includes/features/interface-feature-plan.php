<?php
/**
 * Plan capabilities/limits contract.
 *
 * @package FlexTopBar
 */
declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface FeaturePlan {
	public function max_bars(): int;

	public function schedule_enabled(): bool;

	public function max_messages(): int;

	public function max_columns(): int;

	public function plan_name(): string;
}

