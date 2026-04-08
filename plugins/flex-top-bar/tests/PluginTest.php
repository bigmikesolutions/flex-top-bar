<?php
/**
 * Unit tests for Top Bar plugin.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace FlexTopBar\Tests;

use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase {

	public function test_plugin_file_exists(): void {
		$plugin_file = dirname( __DIR__ ) . '/top-bar.php';
		$this->assertFileExists( $plugin_file );
	}

	public function test_plugin_file_contains_required_constants(): void {
		$content = (string) file_get_contents( dirname( __DIR__ ) . '/top-bar.php' );
		$this->assertStringContainsString( 'TOP_BAR_VERSION', $content );
		$this->assertStringContainsString( 'TOP_BAR_PLUGIN_DIR', $content );
	}

	public function test_plugin_headers_present(): void {
		$content = (string) file_get_contents( dirname( __DIR__ ) . '/top-bar.php' );
		$this->assertStringContainsString( 'Plugin Name:', $content );
		$this->assertStringContainsString( 'Top Bar', $content );
		$this->assertStringContainsString( 'Version:', $content );
	}

	public function test_plugin_namespace(): void {
		$content = (string) file_get_contents( dirname( __DIR__ ) . '/top-bar.php' );
		$this->assertStringContainsString( 'namespace FlexTopBar;', $content );
	}
}
