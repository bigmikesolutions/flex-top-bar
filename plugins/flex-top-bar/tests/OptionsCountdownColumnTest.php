<?php
/**
 * Countdown column normalization.
 *
 * @package FlexTopBar
 */

use FlexTopBar\FeatureFlags;
use FlexTopBar\Options;
use PHPUnit\Framework\TestCase;

final class OptionsCountdownColumnTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 'pro' );
		}
		FeatureFlags::reset_for_tests();
	}

	public function test_normalize_countdown_column_preserves_payload(): void {
		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'                    => 'countdown',
						'counter_style'           => 'plain',
						'count_direction'         => 'up',
						'countdown_to_datetime'   => '2026-12-31T23:59',
						'countup_from_datetime'   => '2026-01-01T00:00',
						'countdown_timezone'      => 'Europe/Warsaw',
						'text'                    => 'Promo',
						'text_position'           => 'after',
						'background_color'        => '#abcdef',
						'counter_color'           => '#112233',
						'text_color'              => '#445566',
						'size_percent'            => 75,
						'content_position'        => 'right',
						'messages_mobile_visible' => false,
					],
				],
			]
		);

		$col = $bar['columns'][0];
		$this->assertSame( 'countdown', $col['type'] );
		$this->assertSame( 'plain', $col['counter_style'] );
		$this->assertSame( 'up', $col['count_direction'] );
		$this->assertSame( '2026-12-31T23:59', $col['countdown_to_datetime'] );
		$this->assertSame( '2026-01-01T00:00', $col['countup_from_datetime'] );
		$this->assertSame( 'Europe/Warsaw', $col['countdown_timezone'] );
		$this->assertSame( 'Promo', $col['text'] );
		$this->assertSame( 'after', $col['text_position'] );
		$this->assertSame( '#abcdef', $col['background_color'] );
		$this->assertSame( '#112233', $col['counter_color'] );
		$this->assertSame( '#445566', $col['text_color'] );
		$this->assertSame( 75, $col['size_percent'] );
		$this->assertSame( 'right', $col['content_position'] );
		$this->assertFalse( $col['messages_mobile_visible'] );
	}

	public function test_normalize_countdown_column_rejects_invalid_values(): void {
		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'                  => 'countdown',
						'counter_style'         => 'invalid',
						'count_direction'       => 'sideways',
						'countdown_to_datetime' => 'not-a-date',
						'text_position'         => 'middle',
						'background_color'      => 'nope',
					],
				],
			]
		);

		$col = $bar['columns'][0];
		$this->assertSame( 'boxed', $col['counter_style'] );
		$this->assertSame( 'down', $col['count_direction'] );
		$this->assertSame( '', $col['countdown_to_datetime'] );
		$this->assertSame( 'before', $col['text_position'] );
		$this->assertSame( '#1d2327', $col['background_color'] );
		$this->assertSame( '#ffffff', $col['counter_color'] );
		$this->assertSame( '#1d2327', $col['text_color'] );
	}

	public function test_normalize_countdown_column_strips_seconds_from_datetime(): void {
		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'                  => 'countdown',
						'countdown_to_datetime' => '2026-06-15T14:30:45',
						'countup_from_datetime' => '2026-01-10T08:15:00',
					],
				],
			]
		);

		$col = $bar['columns'][0];
		$this->assertSame( '2026-06-15T14:30', $col['countdown_to_datetime'] );
		$this->assertSame( '2026-01-10T08:15', $col['countup_from_datetime'] );
	}

	public function test_normalize_countdown_column_rejects_invalid_timezone(): void {
		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'               => 'countdown',
						'countdown_timezone' => 'Not/A/Timezone',
					],
				],
			]
		);

		$this->assertSame( '', $bar['columns'][0]['countdown_timezone'] );
	}

	public function test_normalize_countdown_column_defaults_countup_from_to_empty(): void {
		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type' => 'countdown',
					],
				],
			]
		);

		$col = $bar['columns'][0];
		$this->assertSame( '', $col['countup_from_datetime'] );
		$this->assertSame( '', $col['countdown_to_datetime'] );
		$this->assertSame( 'boxed', $col['counter_style'] );
		$this->assertSame( 'down', $col['count_direction'] );
	}

	public function test_normalize_bar_preserves_countdown_column_in_multi_column_bar(): void {
		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'     => 'text',
						'messages' => [ 'Hello' ],
					],
					[
						'type'                  => 'countdown',
						'counter_style'         => 'boxed',
						'count_direction'       => 'down',
						'countdown_to_datetime' => '2026-08-01T12:00',
						'text'                  => 'Ends in',
						'text_position'         => 'before',
					],
				],
			]
		);

		$this->assertSame( 'text', $bar['columns'][0]['type'] );
		$this->assertSame( 'countdown', $bar['columns'][1]['type'] );
		$this->assertSame( '2026-08-01T12:00', $bar['columns'][1]['countdown_to_datetime'] );
		$this->assertSame( 'Ends in', $bar['columns'][1]['text'] );
	}
}
