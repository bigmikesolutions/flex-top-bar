<?php
/**
 * Plugin Name: Flex Top Bar
 * Description: Displays a simple info bar at the top or bottom of the menu.
 * Version:     1.0.5
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

define( 'TOP_BAR_VERSION', '1.0.5' );
define( 'TOP_BAR_PLUGIN_FILE', __FILE__ );
define( 'TOP_BAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Freemius integration
if ( ! function_exists( 'ftb_fs' ) ) {
    // Create a helper function for easy SDK access.
    function ftb_fs() {
        global $ftb_fs;

        if ( ! isset( $ftb_fs ) ) {
            // Include Freemius SDK.
            $sdk_paths = [
                dirname( __FILE__ ) . '/freemius/start.php',
                dirname( __FILE__ ) . '/vendor/freemius/start.php',
            ];
            $loaded = false;
            foreach ( $sdk_paths as $sdk_path ) {
                if ( file_exists( $sdk_path ) ) {
                    require_once $sdk_path;
                    $loaded = true;
                    break;
                }
            }
            if ( ! $loaded ) {
                // Fail gracefully: plugin can still run without Freemius.
                return null;
            }

            $ftb_fs = fs_dynamic_init( array(
                'id'                  => '26477',
                'slug'                => 'flex-top-bar',
                'type'                => 'plugin',
                'public_key'          => 'pk_f374ba95bc57af51c49e958c2717e',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'is_org_compliant'    => true,
                'menu'                => array(
                    'account'        => false,
                    'support'        => false,
                ),
            ) );
        }

        return $ftb_fs;
    }

    // Init Freemius.
    ftb_fs();
    // Signal that SDK was initiated.
    do_action( 'ftb_fs_loaded' );
}

require_once TOP_BAR_PLUGIN_DIR . 'includes/class-feature-flags.php';
require_once TOP_BAR_PLUGIN_DIR . 'includes/class-options.php';
require_once TOP_BAR_PLUGIN_DIR . 'includes/class-api.php';
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
		new API();
		new Admin();
		new Frontend();
	}
}

add_action( 'plugins_loaded', fn() => Plugin::instance() );
