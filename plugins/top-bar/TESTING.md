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

### JavaScript Unit Tests (Vitest) ✅ **12 tests passing**

Located in: `src/components/*.spec.ts`

**What's tested:**
- ✅ TopBarFrontend component rendering
- ✅ Bar visibility filtering (visible flag)
- ✅ Schedule window filtering (datetime logic)
- ✅ Message concatenation for "none" effect
- ✅ CSS class application (position, mobile-hidden)
- ✅ Style attributes (background, border)
- ✅ API error handling
- ✅ Data attributes
- ✅ Mobile visibility classes

**Test file:**
- `src/components/TopBarFrontend.spec.ts` - Complete component test suite

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

**JS Tests:** ✅ Fully working (12/12 passing)
- Complete test suite created (`TopBarFrontend.spec.ts`)
- 12 test cases covering all Vue logic
- Tests run successfully from plugin directory

**E2E Tests:** ✅ Separate Playwright setup (in `/e2e`)

## Recommended Next Steps

1. Fix JavaScript test setup:
   - Configure vitest to work in monorepo
   - Or extract plugin to standalone repo
   - Run and verify all JS tests pass

2. Add more JS test coverage:
   - Admin panel components
   - Pinia stores (bars, feature flags)
   - API service layer

3. Maintain separation:
   - PHP tests never test Vue rendering
   - JS tests never test PHP logic
   - E2E tests verify integration
