import { expect, test } from '@playwright/test';
import {
  addBars,
  getBarIds,
  getBarIdByIndex,
  loginAndOpenTopBarSettings,
  MAX_BARS,
  setBarPosition,
  resetToSingleBar,
  waitForTopBarPut,
} from './helpers/topBarHelpers';

test.describe('multi-bar', () => {
  async function publishBarByIndex(page: any, index: number): Promise<void> {
    const barId = await getBarIdByIndex(page, index);
    page.once('dialog', (d: any) => d.accept());
    const publishBtn = page.locator('.top-bar-row.bg').nth(index).locator('button.top-bar-icons.publish');
    const publishSave = page.waitForResponse((r: any) => {
      if (r.request().method() !== 'POST' || !r.ok()) return false;
      const url = decodeURIComponent(r.url());
      return new RegExp(`/top-bar/v1/bars/${barId}/publish`, 'i').test(url);
    });
    await publishBtn.click();
    await publishSave;
  }

  test('should create 2 top bars and display both on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await addBars(page, 1);

    const ids = await getBarIds(page);
    expect(ids).toHaveLength(2);

    // Set both to top position
    await setBarPosition(page, 0, 'top');
    await setBarPosition(page, 1, 'top');

    // Publish both bars so frontend can see them (frontend reads published bars only).
    await publishBarByIndex(page, 0);
    await publishBarByIndex(page, 1);

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

    // Publish all bars so frontend can see them.
    await publishBarByIndex(page, 0);
    await publishBarByIndex(page, 1);
    await publishBarByIndex(page, 2);

    // Delete first bar
    const removedId = createdIds[0];
    const firstBar = page.locator('.top-bar-row.bg').first();
    const deleteButton = firstBar.locator('button.delete').first();

    // Handle confirmation dialog
    page.on('dialog', dialog => dialog.accept());
    const deleteSave = page.waitForResponse((r) => {
      if (r.request().method() !== 'DELETE') return false;
      if (![200, 204].includes(r.status())) return false;
      const url = decodeURIComponent(r.url());
      return /top-bar\/v1\/bars\/[a-z0-9_]+/i.test(url);
    });
    await deleteButton.click();
    await deleteSave;
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