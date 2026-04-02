<?php
/**
 * Draft/publish option behavior tests.
 *
 * @package TopBar
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use TopBar\Options;

require_once __DIR__ . '/bootstrap.php';

final class OptionsDraftPublishTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_test_options'] = [];
	}

	public function test_get_bars_initializes_draft_from_published_and_seeds_published_if_missing(): void {
		// No options set.
		$draft = Options::get_bars();

		$this->assertIsArray( $draft );
		$this->assertNotEmpty( $draft );

		$published = Options::get_published_bars();
		$this->assertIsArray( $published );
		$this->assertNotEmpty( $published );

		// Draft should equal published immediately after init.
		$this->assertSame( $published, $draft );
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

	public function test_get_active_bars_uses_published_not_draft(): void {
		$draft = Options::get_bars();
		$draft[0]['visible'] = false;
		update_option( Options::OPTION_BARS_DRAFT, $draft );

		// Published still visible by default.
		$active = Options::get_active_bars();
		$this->assertNotEmpty( $active );
		$this->assertTrue( (bool) ( $active[0]['visible'] ?? false ) );
	}
}

