<?php
/**
 * Draft/publish option behavior tests.
 *
 * @package TopBar
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use FlexTopBar\FeatureFlags;
use FlexTopBar\Options;

require_once __DIR__ . '/bootstrap.php';

final class OptionsDraftPublishTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_test_options'] = [];
		if ( ! defined( 'FF_MAX_BARS' ) ) {
			define( 'FF_MAX_BARS', 5 );
		}
		FeatureFlags::reset_for_tests();
	}

	public function test_get_bars_initializes_draft_without_seeding_published_on_fresh_install(): void {
		// No options set.
		$draft = Options::get_bars();

		$this->assertIsArray( $draft );
		$this->assertNotEmpty( $draft );

		$published = Options::get_published_bars();
		$this->assertIsArray( $published );
		$this->assertEmpty( $published );
	}

	public function test_publish_draft_to_published_copies_values(): void {
		$draft = Options::get_bars();
		$draft[0]['bg_color'] = '#ff0000';
		update_option( Options::OPTION_BARS_DRAFT, $draft );

		$published = Options::publish_draft_to_published();
		$this->assertSame( '#ff0000', $published[0]['bg_color'] );

		$published2 = Options::get_published_bars();
		$this->assertSame( '#ff0000', $published2[0]['bg_color'] );
	}

	public function test_publish_bar_only_updates_that_bar(): void {
		$draft = Options::get_bars();
		$bar0_id = (string) ( $draft[0]['id'] ?? '' );

		// Create a second bar in both draft + published.
		$bar2 = Options::default_bar();
		$bar2['id'] = 'bar_two';
		$bar2['bg_color'] = '#00ff00';
		update_option( Options::OPTION_BARS, [ $draft[0], $bar2 ] );
		update_option( Options::OPTION_BARS_DRAFT, [ $draft[0], $bar2 ] );

		// Change draft for bar0 only.
		$draft2 = Options::get_bars();
		$draft2[0]['bg_color'] = '#abcdef';
		$draft2[1] = $bar2;
		update_option( Options::OPTION_BARS_DRAFT, $draft2 );

		$published_before = Options::get_published_bars();
		$this->assertSame( '#00ff00', $published_before[1]['bg_color'] );

		$published_bar = Options::publish_bar( $bar0_id );
		$this->assertNotNull( $published_bar );
		$this->assertSame( '#abcdef', $published_bar['bg_color'] );

		$published_after = Options::get_published_bars();
		$this->assertSame( '#abcdef', $published_after[0]['bg_color'] );
		// Second bar should remain unchanged.
		$this->assertSame( '#00ff00', $published_after[1]['bg_color'] );
	}

	public function test_get_active_bars_uses_published_not_draft(): void {
		$draft = Options::get_bars();
		$draft[0]['visible'] = false;
		update_option( Options::OPTION_BARS_DRAFT, $draft );

		// Nothing should be live until explicitly published.
		$active = Options::get_active_bars();
		$this->assertEmpty( $active );
	}
}

