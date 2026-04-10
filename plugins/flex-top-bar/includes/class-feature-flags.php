<?php

declare(strict_types=1);

final class FeatureLimits
{
	private const PLAN_FREE = 'free';
	private const PLAN_PRO  = 'pro';

	// Local dev overrides (defined only in dev env)
	private const FF_MAX_BARS     = 'FF_MAX_BARS';
	private const FF_MAX_MESSAGES = 'FF_MAX_MESSAGES';
	private const FF_MAX_COLUMNS  = 'FF_MAX_COLUMNS';
	private const FF_SCHEDULE     = 'FF_SCHEDULE';

	/**
	 * Plan configuration (single source of truth)
	 */
	private const PLAN_CONFIG = [
		self::PLAN_FREE => [
			'max_bars'         => 1,
			'max_messages'     => 1,
			'max_columns'      => 1,
			'schedule_enabled' => false,
		],
		self::PLAN_PRO => [
			'max_bars'         => 5,
			'max_messages'     => 5,
			'max_columns'      => 4,
			'schedule_enabled' => true,
		],
	];

	private string $plan;

	private int $max_bars;
	private int $max_messages;
	private int $max_columns;
	private bool $schedule_enabled;

	public function __construct(string $plan)
	{
		$this->plan = $plan;

		// defaults (safe fallback)
		$this->max_bars         = 1;
		$this->max_messages     = 1;
		$this->max_columns      = 1;
		$this->schedule_enabled = false;

		$this->load_from_freemius();
	}

	private function load_from_freemius(): void
	{
		// 1. Apply plan-based config
		$config = self::PLAN_CONFIG[$this->plan]
			?? self::PLAN_CONFIG[self::PLAN_FREE];

		$this->apply_config($config);

		// 2. Apply local dev overrides (if defined)
		$this->apply_overrides();
	}

	private function apply_config(array $config): void
	{
		$this->max_bars         = (int) $config['max_bars'];
		$this->max_messages     = (int) $config['max_messages'];
		$this->max_columns      = (int) $config['max_columns'];
		$this->schedule_enabled = (bool) $config['schedule_enabled'];
	}

	private function apply_overrides(): void
	{
		if (defined(self::FF_MAX_BARS)) {
			$this->max_bars = $this->clamp_int(constant(self::FF_MAX_BARS), 1, null, $this->max_bars);
		}

		if (defined(self::FF_MAX_MESSAGES)) {
			$this->max_messages = $this->clamp_int(constant(self::FF_MAX_MESSAGES), 1, 50, $this->max_messages);
		}

		if (defined(self::FF_MAX_COLUMNS)) {
			$this->max_columns = $this->clamp_int(constant(self::FF_MAX_COLUMNS), 1, 50, $this->max_columns);
		}

		if (defined(self::FF_SCHEDULE)) {
			$this->schedule_enabled = (bool) constant(self::FF_SCHEDULE);
		}
	}

	private function clamp_int(mixed $value, int $min, ?int $max, int $fallback): int
	{
		if (!is_numeric($value)) {
			return $fallback;
		}

		$val = (int) $value;

		if ($val < $min) {
			return $min;
		}

		if ($max !== null && $val > $max) {
			return $max;
		}

		return $val;
	}

	// getters

	public function maxBars(): int
	{
		return $this->max_bars;
	}

	public function maxMessages(): int
	{
		return $this->max_messages;
	}

	public function maxColumns(): int
	{
		return $this->max_columns;
	}

	public function isScheduleEnabled(): bool
	{
		return $this->schedule_enabled;
	}
}