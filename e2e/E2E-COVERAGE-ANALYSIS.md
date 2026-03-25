# E2E Test Coverage Analysis

## Current E2E Test Coverage

### Existing Test Files

#### 1. `e2e/top-bar-single.spec.ts` (17 tests)
**Covers:**
- ✅ Position (top/bottom) - save and render
- ✅ Hide on scroll (top position) - enabled/disabled
- ✅ Hide on scroll (bottom position) - enabled/disabled
- ✅ Scheduling - future dates (hidden)
- ✅ Scheduling - current dates (visible)
- ✅ Multi-message admin - add message field
- ✅ Multi-message admin - remove message field
- ✅ Multi-message admin - cannot remove first message
- ✅ Multi-message effects - slider
- ✅ Multi-message effects - fadein
- ✅ Multi-message effects - blink
- ✅ Multi-message effects - none (concatenated)
- ✅ Mobile visibility - enabled
- ✅ Mobile visibility - disabled

#### 2. `e2e/top-bar-multi.spec.ts` (3 tests)
**Covers:**
- ✅ Create 2 bars and display both
- ✅ Create 3 bars, remove one, keep 2
- ✅ UI prevents creating more than max bars

### Helper Functions
`e2e/helpers/topBarHelpers.ts` provides:
- Login and navigation
- Bar manipulation (add/remove)
- Panel toggling
- Database reset
- Date/time utilities

## Coverage Gaps & E2E Test Recommendations

### HIGH PRIORITY - Feature Flag Related Tests

#### 1. **Feature Flag Limits Testing** 🔴 CRITICAL
Since we just refactored feature flags, these are ESSENTIAL:

```typescript
// e2e/feature-flags-limits.spec.ts
test.describe('Feature Flag Limits', () => {
  test('should enforce FF_MAX_BARS limit when adding bars', async ({ page }) => {
    // Mock/set FF_MAX_BARS=2 via environment or Freemius
    // Try to add 3rd bar
    // Verify UI blocks it
    // Verify only 2 bars saved
  });

  test('should enforce FF_MAX_MESSAGES limit in admin', async ({ page }) => {
    // Mock/set FF_MAX_MESSAGES=3
    // Try to add 4th message
    // Verify UI blocks it
    // Verify only 3 messages saved
  });

  test('should hide scheduling UI when FF_SCHEDULE is false', async ({ page }) => {
    // Mock FF_SCHEDULE=false
    // Open admin
    // Verify scheduling controls are hidden/disabled
  });

  test('should show scheduling UI when FF_SCHEDULE is true', async ({ page }) => {
    // Mock FF_SCHEDULE=true
    // Open admin
    // Verify scheduling controls are visible
  });

  test('should respect max_bars when displaying on frontend', async ({ page }) => {
    // Create 5 bars in DB
    // Set FF_MAX_BARS=2
    // Verify only 2 render on frontend
  });

  test('should respect max_messages when displaying on frontend', async ({ page }) => {
    // Create bar with 10 messages
    // Set FF_MAX_MESSAGES=3
    // Verify only 3 messages display
  });
});
```

#### 2. **Feature Flag Changes at Runtime** 🟡 MEDIUM
Test what happens when limits change:

```typescript
test('should handle max_bars decrease gracefully', async ({ page }) => {
  // Create 5 bars with FF_MAX_BARS=5
  // Change to FF_MAX_BARS=2
  // Reload admin
  // Verify UI shows only 2 bars
  // Verify warning/message about limit
});

test('should handle max_messages decrease in existing bars', async ({ page }) => {
  // Create bar with 10 messages
  // Change FF_MAX_MESSAGES=3
  // Reload admin
  // Verify only 3 messages shown
  // Verify save works correctly
});
```

### MEDIUM PRIORITY - Missing Functional Tests

#### 3. **Color Customization** 🟡
```typescript
test.describe('Color Customization', () => {
  test('should save and render custom background color', async ({ page }) => {
    // Set custom bg color #ff0000
    // Verify frontend has correct background-color CSS
  });

  test('should save and render custom frame color and width', async ({ page }) => {
    // Set frame color #00ff00 and width 5px
    // Verify frontend has border CSS
  });

  test('should remove frame when width set to 0', async ({ page }) => {
    // Set frame width to 0
    // Verify no border on frontend
  });
});
```

#### 4. **Visibility Toggle** 🟡
```typescript
test.describe('Visibility Toggle', () => {
  test('should hide bar on frontend when visible=false', async ({ page }) => {
    // Set bar visible=false
    // Verify bar does NOT render on frontend
  });

  test('should show bar on frontend when visible=true', async ({ page }) => {
    // Set bar visible=true
    // Verify bar DOES render on frontend
  });

  test('should hide invisible bars from multiple bars', async ({ page }) => {
    // Create 3 bars: visible, invisible, visible
    // Verify only 2 render on frontend
  });
});
```

#### 5. **Schedule Edge Cases** 🟡
```typescript
test.describe('Schedule Edge Cases', () => {
  test('should auto-enable schedule when dates provided', async ({ page }) => {
    // Set scheduled_enabled=false
    // Provide from/to datetimes
    // Save
    // Verify scheduled_enabled=true in DB
  });

  test('should clear dates when schedule disabled', async ({ page }) => {
    // Set scheduled_enabled=true with dates
    // Disable scheduling
    // Save
    // Verify dates cleared in DB
  });

  test('should validate schedule date ranges', async ({ page }) => {
    // Try to set "from" date AFTER "to" date
    // Verify validation error or auto-fix
  });

  test('should handle timezone correctly in schedule', async ({ page }) => {
    // Set schedule in specific timezone
    // Verify frontend respects timezone
  });
});
```

#### 6. **Admin Panel Interactions** 🟡
```typescript
test.describe('Admin Panel Interactions', () => {
  test('should expand/collapse panel correctly', async ({ page }) => {
    // Click panel header
    // Verify expanded/collapsed state
  });

  test('should persist expanded state after save', async ({ page }) => {
    // Expand panel 1, collapse panel 2
    // Save
    // Reload page
    // Verify state persisted
  });

  test('should show validation errors inline', async ({ page }) => {
    // Submit invalid data
    // Verify error messages shown
  });
});
```

#### 7. **Multiple Bars Positioning** 🟡
```typescript
test.describe('Multiple Bars Positioning', () => {
  test('should render multiple bars in correct order', async ({ page }) => {
    // Create 3 bars: top, bottom, top
    // Verify order on frontend matches admin order
  });

  test('should stack multiple top bars vertically', async ({ page }) => {
    // Create 3 top-position bars
    // Verify they stack without overlap
  });

  test('should stack multiple bottom bars vertically', async ({ page }) => {
    // Create 3 bottom-position bars
    // Verify they stack without overlap
  });
});
```

### LOW PRIORITY - Nice to Have

#### 8. **Performance & Load Tests** 🟢
```typescript
test('should handle many messages efficiently', async ({ page }) => {
  // Create bar with 50 messages (max)
  // Verify page loads in reasonable time
  // Verify effect animations smooth
});

test('should handle max bars efficiently', async ({ page }) => {
  // Create 20 bars (if allowed)
  // Verify frontend renders without lag
});
```

#### 9. **Cross-Browser Testing** 🟢
Already configured for Chromium. Consider adding:
- Firefox
- Safari (WebKit)
- Mobile browsers

#### 10. **Accessibility Testing** 🟢
```typescript
test('should have proper ARIA labels', async ({ page }) => {
  // Check accessibility tree
  // Verify screen reader compatibility
});

test('should be keyboard navigable', async ({ page }) => {
  // Tab through admin interface
  // Verify all controls accessible
});
```

#### 11. **Error Handling** 🟢
```typescript
test('should handle database errors gracefully', async ({ page }) => {
  // Simulate DB failure
  // Verify user sees helpful error message
});

test('should handle concurrent edits', async ({ page, context }) => {
  // Open settings in 2 tabs
  // Edit in both
  // Verify conflict resolution
});
```

## Implementation Priority

### Phase 1: Feature Flags (MUST HAVE) 🔴
Focus on testing the newly refactored feature flag system:
1. Max bars limit enforcement
2. Max messages limit enforcement
3. Schedule feature flag (show/hide UI)
4. Frontend respects limits

**Estimate:** 6-8 tests, ~4-6 hours

### Phase 2: Core Functionality Gaps 🟡
1. Color customization (3 tests)
2. Visibility toggle (3 tests)
3. Schedule edge cases (4 tests)
4. Admin panel interactions (3 tests)

**Estimate:** 13 tests, ~6-8 hours

### Phase 3: Polish & Edge Cases 🟢
1. Multiple bars positioning (3 tests)
2. Performance tests (2 tests)
3. Accessibility (2 tests)
4. Error handling (2 tests)

**Estimate:** 9 tests, ~4-6 hours

## Current Test Count
- **E2E Tests:** 20 tests (17 single + 3 multi)
- **Unit Tests:** 73 tests

## Recommended Test Count After Additions
- **E2E Tests:** ~48 tests (current 20 + Phase 1: 8 + Phase 2: 13 + Phase 3: 9)
- **Unit Tests:** 73 tests (already comprehensive)

## Running E2E Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run specific test file
npx playwright test e2e/feature-flags-limits.spec.ts

# Run with UI (debug mode)
npx playwright test --ui

# Run and generate report
npx playwright test --reporter=html
```

## Notes for Implementation

### Setting Feature Flags in Tests
You'll need to mock Freemius constants. Options:

1. **Environment variables** (read in PHP):
   ```typescript
   process.env.FF_MAX_BARS = '2';
   ```

2. **Direct DB update before test**:
   ```typescript
   execSync('docker compose exec wordpress wp option update ...');
   ```

3. **WordPress filter/constant definition**:
   Create a test helper plugin that defines constants based on env vars.

### Test Data Isolation
- Use `resetToSingleBar()` before each test
- Consider adding `resetFeatureFlags()` helper
- Clean up test data after runs

### CI/CD Integration
- Add feature flag tests to required checks
- Run E2E tests on every PR
- Generate coverage reports
- Screenshot failures automatically
