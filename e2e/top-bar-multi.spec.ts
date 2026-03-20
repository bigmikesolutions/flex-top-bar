import { expect, test } from '@playwright/test';
import {
  addBars,
  getBarIds,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleBar,
} from './helpers/topBarHelpers';

declare const process: { env: Record<string, string | undefined> };

test.describe('multi-bar', () => {
  test('should create 2 top bars and display both on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await resetToSingleBar(page);
    await addBars(page, 1);

    const ids = await getBarIds(page);
    expect(ids).toHaveLength(2);

    for (let i = 0; i < 2; i += 1) {
      await openPanel(page, i);

      const position = page.locator(`select[name="top_bars[${i}][position]"]`);
      await position.evaluate((el: HTMLSelectElement) => {
        el.value = 'top';
        el.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }

    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

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

    for (let i = 0; i < 3; i += 1) {
      await openPanel(page, i);
    }

    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

    const removedId = createdIds[0];
    const rowToRemove = page
      .locator('.top-bar-row.bg')
      .filter({ has: page.locator(`input[name^="top_bars["][name$="[id]"][value="${removedId}"]`) })
      .first();
    await rowToRemove.locator('a.top-bar-icons.delete').click();
    await page.waitForLoadState('domcontentloaded');

    await page.goto('/');
    await expect(page.locator(`[data-top-bar-id="${removedId}"]`)).toHaveCount(0);
    await expect(page.locator(`[data-top-bar-id="${createdIds[1]}"]`)).toHaveCount(1);
    await expect(page.locator(`[data-top-bar-id="${createdIds[2]}"]`)).toHaveCount(1);
  });
});