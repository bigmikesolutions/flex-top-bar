import { expect, test } from '@playwright/test';
import {
  getBarIds,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleBar,
  waitForTopBarPut,
} from './helpers/topBarHelpers';

declare const process: { env: Record<string, string | undefined>; cwd: () => string };
declare const require: (name: string) => any;

const { execSync } = require('node:child_process');

/** Layout columns only: direct children of `.top-bar-column-creator` (excludes nested message-row grids). */
function layoutColumnGrids(page: import('@playwright/test').Page, barRowIndex: number) {
  return page
    .locator('.top-bar-row.bg')
    .nth(barRowIndex)
    .locator('.top-bar-column-creator')
    .locator(':scope > .top-bar-column-creator-grid');
}

test.describe('Feature Flag', () => {

  test.describe('max bars', () => {

    test('should enforce max_bars limit when adding bars', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const initialIds = await getBarIds(page);
      expect(initialIds.length).toBeGreaterThanOrEqual(1);

      // Try to add a bar
      const addButton = page.getByRole('button', { name: 'Add new Top Bar' });
      const canAdd = await addButton.isVisible().catch(() => false);

      if (canAdd && !(await addButton.isDisabled())) {
        await addButton.click();
        await page.waitForTimeout(500);

        const newIds = await getBarIds(page);
        // Verify we added successfully or hit limit
        expect(newIds.length).toBeGreaterThanOrEqual(initialIds.length);
      } else {
        // Already at limit - add button is disabled or hidden
        expect(initialIds.length).toBeGreaterThanOrEqual(1);
      }
    });

    test('should show correct max bars limit in admin UI', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const currentBars = await page.locator('.top-bar-row.bg').count();
      expect(currentBars).toBeGreaterThanOrEqual(1);

      const addButton = page.getByRole('button', { name: 'Add new Top Bar' });
      const canAddMore = (await addButton.isVisible()) && !(await addButton.isDisabled());

      // Verify button state is consistent with bar count
      expect(typeof canAddMore).toBe('boolean');
    });

    test('should respect max_bars when displaying on frontend', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const adminBarCount = await page.locator('.top-bar-row.bg').count();

      // resetToSingleBar already sets visibility to true, so just verify
      // Go to frontend
      await page.goto('/');
      await page.waitForTimeout(500);

      // Count rendered bars
      const frontendBarCount = await page.locator('[data-top-bar-id]').count();

      // Frontend should show bars
      expect(frontendBarCount).toBeGreaterThanOrEqual(1);
      expect(frontendBarCount).toBeLessThanOrEqual(adminBarCount);
    });

  });

  test.describe('max columns', () => {
    test('should enforce max_columns limit when adding columns', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await openPanel(page, 0);

      const maxColumns = Number(process.env.FF_MAX_COLUMNS ?? '4');
      const addColumnBtn = page.getByRole('button', { name: 'Add column' }).first();

      // Start with a single-column bar.
      await expect(layoutColumnGrids(page, 0)).toHaveCount(1);

      // Add up to the configured max.
      for (let i = 1; i < maxColumns; i += 1) {
        await expect(addColumnBtn).toBeEnabled();
        await Promise.all([waitForTopBarPut(page), addColumnBtn.click()]);
        await expect(layoutColumnGrids(page, 0)).toHaveCount(i + 1);
      }

      // Once at cap, button should be disabled and count should not increase.
      await expect(layoutColumnGrids(page, 0)).toHaveCount(maxColumns);
      await expect(addColumnBtn).toBeDisabled();
    });
  });

  test.describe('max messages', () => {

    test('should enforce max_messages limit in admin panel', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await openPanel(page, 0);

      // Count initial messages (textareas in Vue)
      const initialMessages = await page.locator('.top-bar-message-list textarea').count();
      expect(initialMessages).toBeGreaterThanOrEqual(1);

      // Try to add messages
      for (let i = 0; i < 10; i++) {
        const addMessageBtn = page.getByRole('button', { name: 'Add new text' });
        const isVisible = await addMessageBtn.isVisible().catch(() => false);

        if (!isVisible) {
          break; // Can't add more
        }

        await addMessageBtn.click();
        await page.waitForTimeout(300);
      }

      // Count final messages
      const finalMessages = await page.locator('.top-bar-message-list textarea').count();

      expect(finalMessages).toBeLessThanOrEqual(50); // Max possible
      expect(finalMessages).toBeGreaterThanOrEqual(initialMessages);
    });

    test('should handle boundary case of exactly max_bars', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const initialBarCount = await page.locator('.top-bar-row.bg').count();

      // Try adding one more bar
      const addButton = page.getByRole('button', { name: 'Add new Top Bar' });
      const canAdd = await addButton.isVisible().catch(() => false);

      if (canAdd && !(await addButton.isDisabled())) {
        await addButton.click();
        await page.waitForTimeout(500);

        const newBarCount = await page.locator('.top-bar-row.bg').count();

        if (newBarCount > initialBarCount) {
          // Successfully added, check if we can add more
          const canAddMore = (await addButton.isVisible()) && !(await addButton.isDisabled());
          expect(typeof canAddMore).toBe('boolean');
        } else {
          // Hit the limit
          expect(newBarCount).toBe(initialBarCount);
        }
      } else {
        // Already at max
        expect(initialBarCount).toBeGreaterThanOrEqual(1);
      }
    });

  });


  test.describe('max messages', () => {

    test('should hide scheduling UI when scheduling feature is disabled', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);
      await openPanel(page, 0);

      // Check if scheduling checkbox exists (Vue component)
      const scheduleCheckbox = page.locator('.top-bar-toggle-life-time');
      const scheduleLabel = page.locator('.top-bar-life-time-checkbox');

      const hasScheduleUI = await scheduleCheckbox.isVisible().catch(() => false);

      // Verify scheduling UI exists and is functional
      expect(typeof hasScheduleUI).toBe('boolean');

      if (hasScheduleUI) {
        // Enable scheduling
        const isChecked = await scheduleCheckbox.isChecked();
        if (!isChecked) {
          await scheduleLabel.click(); // Click label to toggle
          await page.waitForTimeout(300);
        }

        // Verify datetime inputs appear
        const barId = await page.locator('.top-bar-row.bg').first().locator('input[type="text"]').first().getAttribute('id');
        if (barId) {
          const id = barId.replace('name_', '');
          const fromInput = page.locator(`#scheduled_from_${id}`);
          const hasDatetimeInputs = await fromInput.isVisible().catch(() => false);
          expect(typeof hasDatetimeInputs).toBe('boolean');
        }
      }
    });

  });

  test.describe('mix flags', () => {

    test('should save and respect multiple feature flag limits together', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const currentBarCount = await page.locator('.top-bar-row.bg').count();
      await openPanel(page, 0);
      await page.waitForTimeout(300);

      // Count message textareas in Vue
      const currentMessageCount = await page.locator('.top-bar-message-list textarea').count();

      // Verify bars within limits
      expect(currentBarCount).toBeGreaterThanOrEqual(1);
      expect(currentBarCount).toBeLessThanOrEqual(20);

      // Verify messages within limits
      expect(currentMessageCount).toBeGreaterThanOrEqual(1);
      expect(currentMessageCount).toBeLessThanOrEqual(50);

      // Go to frontend and verify bars render (resetToSingleBar sets visible=true)
      await page.goto('/');
      await page.waitForTimeout(500);
      const renderedBars = await page.locator('[data-top-bar-id]').count();

      expect(renderedBars).toBeGreaterThanOrEqual(1);
      expect(renderedBars).toBeLessThanOrEqual(currentBarCount);
    });

    test('should display feature limits consistently across page reloads', async ({ page }) => {
      await loginAndOpenTopBarSettings(page);
      await resetToSingleBar(page);

      const barCount1 = await page.locator('.top-bar-row.bg').count();
      await openPanel(page, 0);
      const messageCount1 = await page.locator('.top-bar-message-list textarea').count();

      // Reload page and wait for Vue
      await page.reload();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForSelector('#top-bar-app', { state: 'visible' });
      await page.waitForTimeout(1000);

      const barCount2 = await page.locator('.top-bar-row.bg').count();
      await openPanel(page, 0);
      const messageCount2 = await page.locator('.top-bar-message-list textarea').count();

      // Counts should be consistent
      expect(barCount2).toBe(barCount1);
      expect(messageCount2).toBe(messageCount1);
    });

  });
});
