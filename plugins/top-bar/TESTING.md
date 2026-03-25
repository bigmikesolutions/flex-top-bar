# Testing Strategy

## Test Coverage

### PHP Unit Tests (PHPUnit) ✅ **63 tests passing**

Located in: `tests/`

**What's tested:**
- `FeatureFlagsTest.php` - Feature flag loading and validation
- `FrontendTest.php` - Mount point rendering, asset enqueuing
- `OptionsEdgeCasesTest.php` - Data normalization, sanitization, validation
- `OptionsScheduleTest.php` - Schedule field normalization (scheduling logic is client-side)
- `PluginTest.php` - Plugin file structure and constants

**What's NOT tested** (correctly delegated to JS tests):
- ❌ Bar rendering HTML/CSS
- ❌ Message rotation logic
- ❌ Schedule filtering (client-side)
- ❌ Hide on scroll behavior
- ❌ Vue component lifecycle
- ❌ Effect transitions

**Run tests:**
```bash
cd /Users/m-wrona/github/bigmikesolutions/wordpress
php vendor/bin/phpunit --testdox plugins/top-bar/tests/
```

### JavaScript Unit Tests (Vitest) ✅ **79 tests passing**

Located in: `src/**/*.spec.ts`

**What's tested:**

**Components:**
- ✅ TopBarFrontend - Rendering, visibility, scheduling, styles, API errors (12 tests)
- ✅ BarItem - Admin component for editing bars (34 tests)
  - Rendering (name, expanded/collapsed states)
  - Visibility toggle functionality
  - Delete button (enabled/disabled states, confirmation)
  - Expand/collapse with aria-expanded
  - Form fields (name, position, colors, effects, hide on scroll, mobile visibility)
  - Messages CRUD (add, remove, update, max limit)
  - Scheduling (enable/disable, datetime inputs)
  - Reactivity on prop changes

**API Client:**
- ✅ GET /bars - Fetch bars
- ✅ POST /bars - Create bar
- ✅ PUT /bars/:id - Update bar
- ✅ DELETE /bars/:id - Delete bar
- ✅ GET /feature-flags - Fetch flags
- ✅ Error handling (network errors, HTTP errors)
- ✅ 204 No Content handling (8 tests)

**Pinia Stores:**
- ✅ useBarsStore - State management, CRUD operations, error handling (17 tests)
- ✅ useFeatureFlagsStore - Feature flag loading, error handling (8 tests)

**Test files:**
- `src/components/TopBarFrontend.spec.ts` - Frontend component
- `src/components/BarItem.spec.ts` - Admin bar item component
- `src/api/client.spec.ts` - API client layer
- `src/stores/bars.spec.ts` - Bars state management
- `src/stores/featureFlags.spec.ts` - Feature flags state

**Run tests:**
```bash
# From plugin directory
npm test

# Or with watch mode
npm test -- --watch

# With UI
npm run test:ui
```

**Note:** Tests run from plugin directory but use node_modules from WordPress root (monorepo setup). Vue was installed at root level to resolve dependencies.

## Test Philosophy

### PHP Tests = Backend Logic Only
- Data validation and sanitization
- Database operations
- WordPress hook registration
- Asset enqueuing (not rendering)
- Configuration loading

### JavaScript Tests = Frontend Logic Only
- Vue component rendering
- User interactions
- DOM manipulation
- Client-side scheduling/filtering
- Effects and animations
- API calls and responses

### E2E Tests = Full Integration
- User workflows
- Admin → Frontend flow
- Real browser testing
- Cross-browser compatibility

## Current Status

**PHP Tests:** ✅ Fully migrated and passing (63/63)
- Removed tests for deleted PHP rendering methods
- Updated to reflect Vue-based architecture
- Tests only check mount point output and asset enqueuing

**JS Tests:** ✅ Fully working (79/79 passing)
- Complete test suites for components, API, and stores
- 79 test cases covering all Vue/JS logic
- Tests run successfully from plugin directory

**E2E Tests:** ✅ Separate Playwright setup (in `/e2e`)

## Recommended Next Steps

1. Maintain separation:
   - PHP tests never test Vue rendering
   - JS tests never test PHP logic
   - E2E tests verify integration

2. Consider additional test coverage:
   - Admin panel container component (if/when created)
   - Additional edge cases for scheduling
   - Integration tests for complete user workflows
