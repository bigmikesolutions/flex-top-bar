# Freemius Plan Configuration Example

This is how you would configure feature flags in your Freemius plan settings.

## Plan Configuration

Each plan in Freemius dashboard should define these constants:

### Free Plan
```php
// Define in Freemius plan settings
FF_MAX_BARS = 1
FF_MAX_MESSAGES = 1
FF_SCHEDULE = false
```

### Pro Plan
```php
// Define in Freemius plan settings
FF_MAX_BARS = 5
FF_MAX_MESSAGES = 10
FF_SCHEDULE = true
```

### Enterprise Plan
```php
// Define in Freemius plan settings
FF_MAX_BARS = 20
FF_MAX_MESSAGES = 50
FF_SCHEDULE = true
```

## How It Works

1. Freemius will automatically `define()` these constants based on the user's active plan
2. The `FeatureFlags` class loads these constants at initialization
3. All code uses `FeatureFlags::instance()->max_bars()` etc. to get the values
4. No need to check plans or licenses in code - everything is driven by the constants

## Code Usage

```php
// Instead of checking plans directly:
// ❌ if ( $fs->is_plan('pro') ) { ... }

// Just use the feature flags:
// ✅ if ( FeatureFlags::instance()->is_schedule_enabled() ) { ... }
// ✅ $max = FeatureFlags::instance()->max_bars();
```
