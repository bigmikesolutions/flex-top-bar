<?php
/**
 * Plugin Name: Top Bar
 * Description: Displays a simple info bar at the top or bottom of the menu.
 * Version:     1.0.3
 * Author:      Big Mike Solutions
 * License:     GPL-2.0-or-later
 * Text Domain: top-bar
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TOP_BAR_VERSION', '1.0.3' );
define( 'TOP_BAR_PLUGIN_FILE', __FILE__ );
define( 'TOP_BAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once TOP_BAR_PLUGIN_DIR . 'includes/class-options.php';
require_once TOP_BAR_PLUGIN_DIR . 'includes/class-admin.php';
require_once TOP_BAR_PLUGIN_DIR . 'includes/class-frontend.php';

final class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new Admin();
		new Frontend();
	}
}

add_action( 'plugins_loaded', fn() => Plugin::instance() );
