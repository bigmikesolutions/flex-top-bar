import { expect, test, type Page } from '@playwright/test';
import {
  ensureAtLeastBars,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleBar,
  resetToTwoColumnBar,
  waitForTopBarPut,
} from './helpers/topBarHelpers';

/** Layout columns only: direct children of `.top-bar-column-creator` (excludes nested message-row grids). */
function layoutColumnGrids(page: Page, barRowIndex: number) {
  return page
    .locator('.top-bar-row.bg')
    .nth(barRowIndex)
    .locator('.top-bar-column-creator')
    .locator(':scope > .top-bar-column-creator-grid');
}

test.describe('multi-column', () => {
  test.describe('admin — layout columns', () => {
    test('should show two layout column rows when the bar has two columns in the database', async ({
      page,
    }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToTwoColumnBar(page);
      await openPanel(page, 0);

      await expect(layoutColumnGrids(page, 0)).toHaveCount(2);
    });

    test('should add a second layout column via Add column', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      await expect(layoutColumnGrids(page, 0)).toHaveCount(1);

      await Promise.all([
        waitForTopBarPut(page),
        page.getByRole('button', { name: 'Add column' }).first().click(),
      ]);

      await openPanel(page, 0);
      await expect(layoutColumnGrids(page, 0)).toHaveCount(2);
    });

    test('should remove a layout column when two exist', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToTwoColumnBar(page);
      await openPanel(page, 0);

      const barRow = page.locator('.top-bar-row.bg').first();
      await expect(layoutColumnGrids(page, 0)).toHaveCount(2);

      // Layout column remove uses title="Remove column"; plain "X" also matches message-row removes.
      const removeColumnBtn = barRow.getByTitle('Remove column').first();
      await removeColumnBtn.waitFor({ state: 'visible' });
      await Promise.all([waitForTopBarPut(page), removeColumnBtn.click()]);

      await openPanel(page, 0);
      await expect(layoutColumnGrids(page, 0)).toHaveCount(1);
    });
  });

  test.describe('frontend — multiple columns', () => {
    test('should render two .top-bar__column elements for a two-column bar', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToTwoColumnBar(page);

      await page.goto('/');
      const bar = page.locator('[data-top-bar-id="bar_mcol"]');
      await expect(bar).toHaveCount(1);
      const columns = bar.locator('.top-bar__columns .top-bar__column');
      await expect(columns).toHaveCount(2);
      await expect(bar).toContainText('Col A');
      await expect(bar).toContainText('Col B');
    });
  });
});
