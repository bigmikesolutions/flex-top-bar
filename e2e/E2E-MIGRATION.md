# E2E Tests Migration Guide

## Status: ⚠️ E2E Tests Need Updates for Vue Admin

The admin panel has been migrated from PHP forms to Vue.js, but E2E tests still expect the old PHP form interface.

## What Changed

### Before (PHP Forms)
- Traditional HTML forms with `<input name="top_bars[0][field]">`
- "Save Changes" button submits form
- Page reload after save
- TinyMCE editors for messages
- URL-based actions (e.g., `?top_bar_add=1`)

### After (Vue Admin)
- Vue reactive components with v-model
- Auto-save via REST API (no submit button)
- No page reloads
- Plain textareas for messages
- Button-based actions

## Required Changes

### 1. Finding Elements

**OLD:**
```typescript
const nameInput = page.locator('input[name="top_bars[0][name]"]');
const positionSelect = page.locator('select[name="top_bars[0][position]"]');
```

**NEW:**
```typescript
// Find by ID attribute (Vue generates these)
const nameInput = page.locator('#name_bar_123');
const positionSelect = page.locator('#position_bar_123');

// Or find within bar container
const barRow = page.locator('.top-bar-row.bg').nth(0);
const nameInput = barRow.locator('input[type="text"]').first();
const positionSelect = barRow.locator('select').filter({ has: page.locator('option', { hasText: 'Top' }) });
```

### 2. Getting Bar IDs

**OLD:**
```typescript
const id = await page.locator('input[name="top_bars[0][id]"]').inputValue();
```

**NEW:**
```typescript
// Extract from input ID attribute
const nameInput = page.locator('.top-bar-row.bg').nth(0).locator('input[type="text"]').first();
const inputId = await nameInput.getAttribute('id'); // "name_bar_abc123"
const barId = inputId.replace('name_', ''); // "bar_abc123"
```

### 3. Saving Changes

**OLD:**
```typescript
await position.setValue('bottom');
await page.getByRole('button', { name: 'Save Changes' }).click();
await expect(page.locator('.notice-success')).toBeVisible();
```

**NEW:**
```typescript
// Vue auto-saves via API on change/blur
await position.selectOption('bottom');
await page.waitForTimeout(500); // Wait for API save
// No success notice - check that value persists on reload
await page.reload();
await expect(position).toHaveValue('bottom');
```

### 4. Adding Bars

**OLD:**
```typescript
await page.locator('a[href*="top_bar_add=1"]').click();
await page.waitForLoadState('domcontentloaded');
```

**NEW:**
```typescript
await page.getByRole('button', { name: 'Add new Top Bar' }).click();
await page.waitForTimeout(500); // Wait for Vue to add bar
```

### 5. Deleting Bars

**OLD:**
```typescript
await page.locator('a.delete[data-bar-index="0"]').click();
await page.waitForLoadState('domcontentloaded');
```

**NEW:**
```typescript
const deleteButton = page.locator('.top-bar-row.bg').nth(0).locator('.delete').first();
await deleteButton.click();
page.on('dialog', dialog => dialog.accept()); // Handle confirm dialog
await page.waitForTimeout(500); // Wait for Vue to remove bar
```

### 6. Editing Messages

**OLD:**
```typescript
// TinyMCE
await page.evaluate((index, value) => {
  const editor = tinymce.get(`top_bar_message_0_${index}`);
  editor.setContent(value);
}, 0, 'Hello');
```

**NEW:**
```typescript
// Plain textarea
const textarea = page.locator('.top-bar-row.bg').nth(0).locator('textarea').nth(0);
await textarea.fill('Hello');
await textarea.blur(); // Trigger auto-save
await page.waitForTimeout(500);
```

### 7. Adding/Removing Messages

**OLD:**
```typescript
await page.getByRole('link', { name: 'Add new text' }).click();
await page.waitForLoadState('domcontentloaded');
```

**NEW:**
```typescript
await page.getByRole('button', { name: 'Add new text' }).click();
await page.waitForTimeout(300); // Vue adds field reactively
```

## Migration Checklist

- [ ] Update `getBarIds()` helper
- [ ] Update `clickAddNewTopBar()` helper
- [ ] Remove all `page.getByRole('button', { name: 'Save Changes' })`
- [ ] Replace form input selectors with Vue-compatible selectors
- [ ] Add `waitForTimeout()` after changes (API saves)
- [ ] Update message editing (no TinyMCE)
- [ ] Update scheduling tests (datetime-local inputs)
- [ ] Update all tests in `top-bar-single.spec.ts`
- [ ] Update all tests in `top-bar-multi.spec.ts`
- [ ] Update all tests in `feature-flags-limits.spec.ts`

## Testing Strategy

1. **Start small**: Fix one test file at a time
2. **Use data attributes**: Consider adding `data-test-id` to Vue components
3. **Wait for API**: Add helpers for waiting for API responses
4. **Verify persistence**: Reload page to confirm saves worked

## Example: Simple Test Update

```typescript
// OLD
test('should change position to bottom', async ({ page }) => {
  await loginAndOpenTopBarSettings(page);
  const position = page.locator('select[name="top_bars[0][position]"]');
  await position.selectOption('bottom');
  await page.getByRole('button', { name: 'Save Changes' }).click();
  await expect(page.locator('.notice-success')).toBeVisible();
});

// NEW
test('should change position to bottom', async ({ page }) => {
  await loginAndOpenTopBarSettings(page);
  await openPanel(page, 0);

  // Find position select within first bar
  const barRow = page.locator('.top-bar-row.bg').nth(0);
  const position = barRow.locator('select').filter({
    has: page.locator('option', { hasText: 'Top' })
  });

  await position.selectOption('bottom');
  await page.waitForTimeout(500); // API save

  // Verify by reloading
  await page.reload();
  await expect(position).toHaveValue('bottom');
});
```

## Current Test Status

- ❌ `top-bar-single.spec.ts` - 0/14 tests passing
- ❌ `top-bar-multi.spec.ts` - 0/2 tests passing
- ❌ `feature-flags-limits.spec.ts` - 3/8 tests passing

**Total: 3/24 tests passing (12.5%)**
