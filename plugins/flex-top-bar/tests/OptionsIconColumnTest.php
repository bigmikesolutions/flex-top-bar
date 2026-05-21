<?php
/**
 * Icon column normalization and media helpers.
 *
 * @package FlexTopBar
 */

use FlexTopBar\FeatureFlags;
use FlexTopBar\Options;
use PHPUnit\Framework\TestCase;

final class OptionsIconColumnTest extends TestCase {

	/** @var list<string> */
	private array $temp_files = [];

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'FF_PLAN' ) ) {
			define( 'FF_PLAN', 'pro' );
		}
		FeatureFlags::reset_for_tests();
		$GLOBALS['wp_test_posts']        = [];
		$GLOBALS['wp_test_attachments'] = [];
		$GLOBALS['wp_test_image_sizes'] = [];
	}

	protected function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( is_string( $path ) && $path !== '' && file_exists( $path ) ) {
				unlink( $path );
			}
		}
		$this->temp_files = [];
		parent::tearDown();
	}

	public function test_icon_column_media_limits_match_constants(): void {
		$limits = Options::icon_column_media_limits();

		$this->assertSame( Options::ICON_COLUMN_MAX_WIDTH, $limits['maxWidth'] );
		$this->assertSame( Options::ICON_COLUMN_MAX_HEIGHT, $limits['maxHeight'] );
		$this->assertSame( Options::ICON_COLUMN_MAX_FILE_BYTES, $limits['maxFileBytes'] );
		$this->assertSame( 24, $limits['displaySizePx'] );
		$this->assertContains( 'image/png', $limits['allowedMimeTypes'] );
	}

	public function test_register_icon_image_size_uses_flex_top_bar_icon_slug(): void {
		Options::register_icon_image_size();

		$this->assertCount( 1, $GLOBALS['wp_test_image_sizes'] );
		$this->assertSame( Options::ICON_COLUMN_IMAGE_SIZE, $GLOBALS['wp_test_image_sizes'][0]['name'] );
		$this->assertSame( 64, $GLOBALS['wp_test_image_sizes'][0]['width'] );
		$this->assertSame( 64, $GLOBALS['wp_test_image_sizes'][0]['height'] );
		$this->assertFalse( $GLOBALS['wp_test_image_sizes'][0]['crop'] );
	}

	public function test_normalize_icon_column_uses_sized_attachment_url(): void {
		$this->stub_attachment(
			42,
			[
				'mime'      => 'image/png',
				'bytes'     => 100,
				'full_url'  => 'http://example.test/uploads/icon.png',
				'sized_url' => 'http://example.test/uploads/icon-64x64.png',
			]
		);

		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'               => 'icon',
						'icon_attachment_id' => 42,
						'text'               => 'Promo',
						'icon_position'      => 'after',
					],
				],
			]
		);

		$col = $bar['columns'][0];
		$this->assertSame( 42, $col['icon_attachment_id'] );
		$this->assertSame( 'http://example.test/uploads/icon-64x64.png', $col['icon_url'] );
		$this->assertSame( 'Promo', $col['text'] );
		$this->assertSame( 'after', $col['icon_position'] );
	}

	public function test_normalize_icon_column_generates_metadata_when_sized_missing(): void {
		$this->stub_attachment(
			7,
			[
				'mime'      => 'image/jpeg',
				'bytes'     => 200,
				'full_url'  => 'http://example.test/uploads/big.jpg',
				'sized_url' => '',
			]
		);

		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'               => 'icon',
						'icon_attachment_id' => 7,
					],
				],
			]
		);

		$this->assertSame( 7, $bar['columns'][0]['icon_attachment_id'] );
		$this->assertSame( 'http://example.test/uploads/icon-64x64.jpg', $bar['columns'][0]['icon_url'] );
	}

	public function test_normalize_icon_column_clears_invalid_attachment(): void {
		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'               => 'icon',
						'icon_attachment_id' => 999,
						'text'               => 'Hello',
					],
				],
			]
		);

		$this->assertSame( 0, $bar['columns'][0]['icon_attachment_id'] );
		$this->assertSame( '', $bar['columns'][0]['icon_url'] );
		$this->assertSame( 'Hello', $bar['columns'][0]['text'] );
	}

	public function test_normalize_icon_column_rejects_oversized_file(): void {
		$this->stub_attachment(
			5,
			[
				'mime'      => 'image/png',
				'bytes'     => Options::ICON_COLUMN_MAX_FILE_BYTES + 1,
				'full_url'  => 'http://example.test/uploads/huge.png',
				'sized_url' => 'http://example.test/uploads/huge-64x64.png',
			]
		);

		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'               => 'icon',
						'icon_attachment_id' => 5,
					],
				],
			]
		);

		$this->assertSame( 0, $bar['columns'][0]['icon_attachment_id'] );
		$this->assertSame( '', $bar['columns'][0]['icon_url'] );
	}

	public function test_normalize_icon_column_rejects_disallowed_mime(): void {
		$this->stub_attachment(
			3,
			[
				'mime'      => 'application/pdf',
				'bytes'     => 100,
				'full_url'  => 'http://example.test/uploads/doc.pdf',
				'sized_url' => '',
			]
		);

		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'               => 'icon',
						'icon_attachment_id' => 3,
					],
				],
			]
		);

		$this->assertSame( 0, $bar['columns'][0]['icon_attachment_id'] );
	}

	public function test_normalize_icon_column_svg_uses_full_url(): void {
		$this->stub_attachment(
			9,
			[
				'mime'      => 'image/svg+xml',
				'bytes'     => 400,
				'full_url'  => 'http://example.test/uploads/icon.svg',
				'sized_url' => '',
			]
		);

		$bar = Options::normalize_bar(
			[
				'columns' => [
					[
						'type'               => 'icon',
						'icon_attachment_id' => 9,
					],
				],
			]
		);

		$this->assertSame( 9, $bar['columns'][0]['icon_attachment_id'] );
		$this->assertSame( 'http://example.test/uploads/icon.svg', $bar['columns'][0]['icon_url'] );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function stub_attachment( int $id, array $data ): void {
		$bytes = isset( $data['bytes'] ) ? (int) $data['bytes'] : 1;
		$file  = sys_get_temp_dir() . '/ftb-icon-' . $id . '-' . uniqid( '', true ) . '.bin';
		file_put_contents( $file, str_repeat( 'x', max( 1, $bytes ) ) );
		$this->temp_files[] = $file;

		$post              = new \WP_Post();
		$post->ID          = $id;
		$post->post_type   = 'attachment';
		$GLOBALS['wp_test_posts'][ $id ] = $post;
		$GLOBALS['wp_test_attachments'][ $id ] = array_merge(
			[
				'file' => $file,
			],
			$data
		);
	}
}
