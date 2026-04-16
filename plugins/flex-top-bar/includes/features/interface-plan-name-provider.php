<?php
/**
 * Plan name provider contract.
 *
 * @package FlexTopBar
 */
declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface PlanNameProvider {
	public function get_plan_name(): string;
}

