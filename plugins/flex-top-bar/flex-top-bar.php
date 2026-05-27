<?php
/**
 * Plugin Name: Flex Top Bar
 * Description: Build and publish customizable notification bars (top or bottom) with scheduling, icons, message effects/animations, multi-bar support, modern admin UI and more.
 * Version:     0.2.0
 * Author:      Big Mike Solutions
 * Plugin URI:  https://github.com/bigmikesolutions/flex-top-bar
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flex-top-bar
 *
 * @package FlexTopBar
 *
 * --------------------------------------------------------------------------------
 * Flex Top Bar – End-User License Agreement (EULA) – Summary
 *
 * - Licensed under GNU General Public License v2 or later (GPL-2.0-or-later)
 *   You may use, modify, and redistribute the plugin in accordance with the GPL.
 *
 * - Use at your own risk. Plugin is provided "AS IS" and "AS AVAILABLE".
 *   No warranties expressed or implied, including merchantability, fitness for
 *   a particular purpose, or non-infringement.
 *
 * - Author is not responsible for:
 *   • data loss, downtime, or system issues,
 *   • direct, indirect, or consequential damages,
 *   • compatibility with WordPress versions, themes, plugins, or third-party services.
 *
 * - User is responsible for:
 *   • verifying plugin in a staging environment,
 *   • maintaining backups,
 *   • security and compliance of their website.
 *
 * - User content (messages, links, graphics) remains user's responsibility.
 *
 * Full EULA is included in 'EULA.MD' inside this plugin folder and on the plugin page.
 */

declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Namespaced constants to avoid collisions with other plugins.
define( 'FLEX_TOP_BAR_VERSION', '0.2.0' );
define( 'FLEX_TOP_BAR_PLUGIN_FILE', __FILE__ );
define( 'FLEX_TOP_BAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Backwards-compat aliases (keep older internal references working).
if ( ! defined( 'TOP_BAR_VERSION' ) ) {
	define( 'TOP_BAR_VERSION', FLEX_TOP_BAR_VERSION );
}
if ( ! defined( 'TOP_BAR_PLUGIN_FILE' ) ) {
	define( 'TOP_BAR_PLUGIN_FILE', FLEX_TOP_BAR_PLUGIN_FILE );
}
if ( ! defined( 'TOP_BAR_PLUGIN_DIR' ) ) {
	define( 'TOP_BAR_PLUGIN_DIR', FLEX_TOP_BAR_PLUGIN_DIR );
}

// Freemius integration
if ( ! function_exists( 'ftb_fs' ) ) {
    // Create a helper function for easy SDK access.
    function ftb_fs() {
        static $ftb_fs_instance = null;

        if ( $ftb_fs_instance === null ) {
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

            $ftb_fs_instance = fs_dynamic_init( array(
                'id'                  => '26477',
                'slug'                => 'flex-top-bar',
                'type'                => 'plugin',
                'public_key'          => 'pk_f374ba95bc57af51c49e958c2717e',
                'is_premium'          => false, // free version is default
                'premium_suffix'      => 'Premium',
                'has_premium_version' => true,  // upgrade to premium version
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'is_org_compliant'    => true,
                'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                'trial'               => array(
                    'days'               => 14,
                    'is_require_payment' => false,
                ),
                'menu'                => array(
                    'slug'           => 'flex-top-bar',
                    'support'        => false,
                ),
            ) );
        }

        return $ftb_fs_instance;
    }

    // Init Freemius.
    ftb_fs();
    // Signal that SDK was initiated.
    do_action( 'flex_top_bar_fs_loaded' );
}

require_once TOP_BAR_PLUGIN_DIR . 'includes/features/class-feature-flags.php';
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
		add_action( 'after_setup_theme', [ Options::class, 'register_icon_image_size' ] );
		new API();
		new Admin();
		new Frontend();
	}
}

add_action( 'plugins_loaded', fn() => Plugin::instance() );
