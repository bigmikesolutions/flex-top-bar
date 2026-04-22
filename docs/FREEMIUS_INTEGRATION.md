# Freemius Integration Guide

- [Guide - doc](https://freemius.com/selling-wordpress-plugins-guide/).
- [Testing credit cards & PayPal accounts](https://freemius.com/help/documentation/checkout/integration/testing/#testing-credit-cards)

## Setup Complete

Freemius SDK has been integrated into the Top Bar plugin. The SDK is initialized in `flex-top-bar.php`.

## Configuration

Update these placeholder values in `flex-top-bar.php` with your actual Freemius credentials:

```php
'id'         => 'YOUR_PLUGIN_ID',      // Replace with your plugin ID from Freemius dashboard
'public_key' => 'YOUR_PUBLIC_KEY',     // Replace with your public key from Freemius dashboard
```

To get these values:
1. Sign up at https://freemius.com
2. Add your plugin to the Freemius dashboard
3. Copy the Plugin ID and Public Key from the dashboard
4. Update the values in `flex-top-bar.php`

## How to Add License Checks

### Example 1: Restrict a Feature to Paid Users

```php
// Check if user has active paid license
if ( top_bar_fs()->is_premium() ) {
    // Premium feature code
} else {
    // Show upgrade message
    echo '<p>This feature requires a premium license. <a href="' . top_bar_fs()->get_upgrade_url() . '">Upgrade now</a></p>';
}
```

### Example 2: Check for Specific Plan

```php
// Check if user has a specific plan
if ( top_bar_fs()->is_plan( 'professional', true ) ) {
    // Professional plan feature
}
```

### Example 3: Trial Support

```php
// Check if user is on trial
if ( top_bar_fs()->is_trial() ) {
    // Trial user message
    echo '<p>You are on a trial. Days remaining: ' . top_bar_fs()->get_trial()->get_days_left() . '</p>';
}
```

### Example 4: Limit Features by License

You could add license checks in `class-admin.php`:

```php
// In the handle_bar_actions method, limit number of bars for free users
if ( isset( $_GET['top_bar_add'] ) ) {
    check_admin_referer( 'top_bar_add' );
    $bars = get_option( Options::OPTION_BARS, [] );

    // Free users limited to 1 bar
    $max_bars = Options::max_bars();

    if ( count( $bars ) >= $max_bars ) {
        if ( ! top_bar_fs()->is_premium() ) {
            // Show upgrade message for free users
            wp_safe_redirect( admin_url( 'options-general.php?page=top-bar&upgrade_required=1' ) );
        } else {
            wp_safe_redirect( admin_url( 'options-general.php?page=top-bar&top_bar_max=1' ) );
        }
        exit;
    }
    // ... rest of code
}
```

## Freemius Features Available

- **Licensing**: Automatic license validation and activation
- **Payments**: Integrated checkout process
- **Trials**: Support for trial periods
- **Updates**: Automatic plugin updates for paid users
- **Analytics**: Usage analytics in Freemius dashboard
- **Support**: Built-in ticketing system
- **Pricing**: Multiple pricing plans and tiers

## Next Steps

1. Create a Freemius account and register your plugin
2. Update the plugin ID and public key in `flex-top-bar.php`
3. Set up your pricing plans in the Freemius dashboard
4. Test the licensing flow in a staging environment
5. Add license checks to premium features as needed

## Freemius Resources

- Dashboard: https://dashboard.freemius.com
- Documentation: https://freemius.com/help/documentation/
- WordPress SDK: https://github.com/Freemius/wordpress-sdk
- Selling Guide: https://freemius.com/selling-wordpress-plugins-guide/



- [Deployment](https://freemius.com/help/documentation/release-management/deployment/#stripping-paid-only-php-logic)