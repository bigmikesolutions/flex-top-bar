import { expect, test, type Page } from '@playwright/test';
import {
  ensureAtLeastBars,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleBar,
  resetToSingleColumnBar,
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

    test('should not allow adding more than max columns', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const maxColumns = Number(process.env.FF_MAX_COLUMNS ?? '4');
      const addColumnBtn = page.getByRole('button', { name: 'Add column' }).first();

      // Add columns until we reach the cap.
      for (let i = 1; i < maxColumns; i += 1) {
        await expect(layoutColumnGrids(page, 0)).toHaveCount(i);
        await expect(addColumnBtn).toBeEnabled();
        await Promise.all([waitForTopBarPut(page), addColumnBtn.click()]);
      }

      await openPanel(page, 0);
      await expect(layoutColumnGrids(page, 0)).toHaveCount(maxColumns);

      // Once at cap, button should be disabled and count should not increase.
      await expect(addColumnBtn).toBeDisabled();
      await addColumnBtn.click({ force: true }).catch(() => {});
      await expect(layoutColumnGrids(page, 0)).toHaveCount(maxColumns);
    });

    test('should allow adding a Text Editor column', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const addColumnBtn = page.getByRole('button', { name: 'Add column' }).first();
      await Promise.all([waitForTopBarPut(page), addColumnBtn.click()]);

      await openPanel(page, 0);
      await expect(layoutColumnGrids(page, 0)).toHaveCount(2);

      const secondColumn = layoutColumnGrids(page, 0).nth(1);
      // Newly added columns default to "Text Editor" already; no PUT expected here.
      await secondColumn.getByText('Text Editor').click();

      // Text columns show an "Effect" selector.
      await expect(secondColumn.getByText('Effect')).toBeVisible();
    });

    test('should allow adding a Social media column', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const addColumnBtn = page.getByRole('button', { name: 'Add column' }).first();
      await Promise.all([waitForTopBarPut(page), addColumnBtn.click()]);

      await openPanel(page, 0);
      await expect(layoutColumnGrids(page, 0)).toHaveCount(2);

      const secondColumn = layoutColumnGrids(page, 0).nth(1);
      await Promise.all([
        waitForTopBarPut(page),
        secondColumn.getByText('Social media').click(),
      ]);

      // Social editor shows the "Social links" legend.
      await expect(secondColumn.getByText('Social links')).toBeVisible();
    });

    test('should allow adding a Contact data column', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await ensureAtLeastBars(page, 1);
      await openPanel(page, 0);

      const addColumnBtn = page.getByRole('button', { name: 'Add column' }).first();
      await Promise.all([waitForTopBarPut(page), addColumnBtn.click()]);

      await openPanel(page, 0);
      await expect(layoutColumnGrids(page, 0)).toHaveCount(2);

      const secondColumn = layoutColumnGrids(page, 0).nth(1);
      await Promise.all([
        waitForTopBarPut(page),
        secondColumn.getByText('Contact data').click(),
      ]);

      // Contact editor shows the "Add your contact" legend.
      await expect(secondColumn.getByText('Add your contact')).toBeVisible();
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

    test('should display a Text Editor column on the frontend', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleColumnBar(page, 'text');

      await page.goto('/');
      const bar = page.locator('[data-top-bar-id="bar_single_col"]');
      await expect(bar).toHaveCount(1);
      await expect(bar.locator('.top-bar__columns .top-bar__column')).toHaveCount(1);
      await expect(bar).toContainText('Front text');
    });

    test('should display a Social media column on the frontend', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleColumnBar(page, 'social');

      await page.goto('/');
      const bar = page.locator('[data-top-bar-id="bar_single_col"]');
      await expect(bar).toHaveCount(1);
      await expect(bar.locator('.top-bar__columns .top-bar__column')).toHaveCount(1);
      const socialLink = bar.locator('.top-bar-social-column__link').first();
      await expect(socialLink).toHaveCount(1);
      await expect(socialLink).toHaveAttribute('href', /youtube\.com/i);
      await expect(socialLink.locator('.top-bar-icon--social')).toHaveCount(1);
    });

    test('should display a Contact data column on the frontend', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleColumnBar(page, 'contact');

      await page.goto('/');
      const bar = page.locator('[data-top-bar-id="bar_single_col"]');
      await expect(bar).toHaveCount(1);
      await expect(bar.locator('.top-bar__columns .top-bar__column')).toHaveCount(1);
      const contactLink = bar.locator('.top-bar-contact-column__link').first();
      await expect(contactLink).toHaveCount(1);
      await expect(contactLink).toHaveAttribute('href', /mailto:hello%40example\.com/i);
    });
  });
});
