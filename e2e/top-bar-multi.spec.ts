import { expect, test } from '@playwright/test';
import {
  addBars,
  getBarIds,
  getBarIdByIndex,
  loginAndOpenTopBarSettings,
  MAX_BARS,
  setBarPosition,
  resetToSingleBar,
} from './helpers/topBarHelpers';

test.describe('multi-bar', () => {
  test('should create 2 top bars and display both on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await addBars(page, 1);

    const ids = await getBarIds(page);
    expect(ids).toHaveLength(2);

    // Set both to top position
    await setBarPosition(page, 0, 'top');
    await setBarPosition(page, 1, 'top');

    // Verify on frontend
    await page.goto('/');
    for (const id of ids) {
      const bar = page.locator(`[data-top-bar-id="${id}"]`);
      await expect(bar).toHaveCount(1);
      await expect(bar).toHaveAttribute('data-top-bar-position', 'top');
    }
  });

  test('should create 3 bars, remove one, and keep 2 displayed', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await addBars(page, 2);

    const createdIds = await getBarIds(page);
    expect(createdIds).toHaveLength(3);

    // Delete first bar
    const removedId = createdIds[0];
    const firstBar = page.locator('.top-bar-row.bg').first();
    const deleteButton = firstBar.locator('button.delete').first();

    // Handle confirmation dialog
    page.on('dialog', dialog => dialog.accept());
    await deleteButton.click();
    await page.waitForTimeout(500); // Wait for Vue to remove bar

    // Verify on frontend
    await page.goto('/');
    await expect(page.locator(`[data-top-bar-id="${removedId}"]`)).toHaveCount(0);
    await expect(page.locator(`[data-top-bar-id="${createdIds[1]}"]`)).toHaveCount(1);
    await expect(page.locator(`[data-top-bar-id="${createdIds[2]}"]`)).toHaveCount(1);
  });

  test('should not allow creating more than max bars from UI', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);

    // Try to add bars until limit
    for (let i = 0; i < MAX_BARS + 2; i += 1) {
      const addButton = page.getByRole('button', { name: 'Add new Top Bar' });
      if ((await addButton.count()) === 0 || (await addButton.isDisabled())) {
        break;
      }
      await addButton.click();
      await page.waitForTimeout(300);
    }

    const barCount = await page.locator('.top-bar-row.bg').count();
    const addButton = page.getByRole('button', { name: 'Add new Top Bar' });

    expect(barCount).toBe(MAX_BARS);
    // Button should be disabled or hidden when max reached
    if (await addButton.count() > 0) {
      await expect(addButton).toBeDisabled();
    }
  });
});