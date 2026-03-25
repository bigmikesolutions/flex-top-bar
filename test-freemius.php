<?php
/**
 * Quick test script to verify Freemius integration
 * Run this from: http://localhost:8080/test-freemius.php
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

header('Content-Type: text/plain');

echo "=== Freemius Integration Test ===\n\n";

// Check if function exists
if ( function_exists( 'top_bar_fs' ) ) {
    echo "✓ top_bar_fs() function exists\n";

    try {
        $fs = top_bar_fs();
        echo "✓ Freemius instance created\n";

        // Check basic properties
        echo "\nFreemius Info:\n";
        echo "  - Plugin ID: " . ( $fs->get_id() ) . "\n";
        echo "  - Plugin Slug: " . ( $fs->get_slug() ) . "\n";
        echo "  - Is Premium: " . ( $fs->is_premium() ? 'Yes' : 'No' ) . "\n";
        echo "  - Has Paid Plans: " . ( $fs->has_paid_plan() ? 'Yes' : 'No' ) . "\n";

        if ( $fs->is_registered() ) {
            echo "  - Status: Registered\n";
            echo "  - User Email: " . $fs->get_user()->email . "\n";
        } else {
            echo "  - Status: Not registered (this is normal for testing)\n";
        }

        echo "\n✅ Freemius is working!\n";
        echo "\nNext steps:\n";
        echo "1. Go to WP Admin > Plugins to see the Top Bar plugin\n";
        echo "2. You should see Freemius options (Account, Pricing, etc.)\n";
        echo "3. Update YOUR_PLUGIN_ID and YOUR_PUBLIC_KEY in top-bar.php\n";

    } catch ( Exception $e ) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ top_bar_fs() function not found\n";
    echo "  Make sure the Top Bar plugin is active\n";
}

echo "\n=== End Test ===\n";
