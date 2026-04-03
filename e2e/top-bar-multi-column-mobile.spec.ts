import { expect, test, type Page } from '@playwright/test';
import {
  getBarIdByIndex,
  loginAndOpenTopBarSettings,
  openPanel,
  resetToSingleColumnBar,
  waitForTopBarPut,
} from './helpers/topBarHelpers';

/** Viewport width must be ≤782px to match `.top-bar__column--mobile-hidden` in TopBarView.vue */
const MOBILE_VIEWPORT = { width: 375, height: 812 } as const;

/**
 * Sets per-column "Visible on the mobile" and publishes so the frontend reflects it.
 */
async function setColumnMobileVisibilityAndPublish(
  page: Page,
  columnType: 'text' | 'social' | 'contact',
  mobileVisible: boolean,
): Promise<string> {
  await loginAndOpenTopBarSettings(page);
  await resetToSingleColumnBar(page, columnType);
  await openPanel(page, 0);

  const barRow = page.locator('.top-bar-row.bg').first();
  const columnGrid = barRow.locator('.top-bar-column-creator-grid').first();
  const mobileSelect = columnGrid
    .locator('fieldset')
    .filter({ has: page.locator('legend', { hasText: 'Visible on the mobile' }) })
    .locator('select');

  const mobileSave = waitForTopBarPut(page);
  await mobileSelect.selectOption({ label: mobileVisible ? 'On' : 'Off' });
  await mobileSave;

  const id0 = await getBarIdByIndex(page, 0);
  page.once('dialog', (d) => d.accept());
  const publishPromise = page.waitForResponse((r) => {
    if (r.request().method() !== 'POST' || !r.ok()) return false;
    const url = decodeURIComponent(r.url());
    return new RegExp(`/top-bar/v1/bars/${id0}/publish`, 'i').test(url);
  });
  await barRow.locator('button.top-bar-icons.publish').click();
  await publishPromise;

  return id0;
}

function barLocator(page: Page, barId: string) {
  return page.locator(`[data-top-bar-id="${barId}"]`);
}

test.describe('multi-column - mobile support', () => {
  test.describe('Text column — visible on mobile', () => {
    test('shows text when On at mobile viewport', async ({ page }) => {
      const id0 = await setColumnMobileVisibilityAndPublish(page, 'text', true);

      await page.setViewportSize(MOBILE_VIEWPORT);
      await page.goto('/');

      const bar = barLocator(page, id0);
      const column = bar.locator('.top-bar__column').first();
      await expect(column).not.toHaveClass(/top-bar__column--mobile-hidden/);
      await expect(column).toBeVisible();
      await expect(bar).toContainText('Front text');
    });

    test('hides column when Off at mobile viewport', async ({ page }) => {
      const id0 = await setColumnMobileVisibilityAndPublish(page, 'text', false);

      await page.setViewportSize(MOBILE_VIEWPORT);
      await page.goto('/');

      const bar = barLocator(page, id0);
      const column = bar.locator('.top-bar__column').first();
      await expect(column).toHaveClass(/top-bar__column--mobile-hidden/);
      await expect(column).toBeHidden();
    });
  });

  test.describe('Social column — visible on mobile', () => {
    test('shows social links when On at mobile viewport', async ({ page }) => {
      const id0 = await setColumnMobileVisibilityAndPublish(page, 'social', true);

      await page.setViewportSize(MOBILE_VIEWPORT);
      await page.goto('/');

      const bar = barLocator(page, id0);
      const column = bar.locator('.top-bar__column').first();
      await expect(column).not.toHaveClass(/top-bar__column--mobile-hidden/);
      await expect(column).toBeVisible();
      const link = bar.locator('.top-bar-social-column__link').first();
      await expect(link).toBeVisible();
      await expect(link).toHaveAttribute('href', /youtube\.com/i);
    });

    test('hides column when Off at mobile viewport', async ({ page }) => {
      const id0 = await setColumnMobileVisibilityAndPublish(page, 'social', false);

      await page.setViewportSize(MOBILE_VIEWPORT);
      await page.goto('/');

      const bar = barLocator(page, id0);
      const column = bar.locator('.top-bar__column').first();
      await expect(column).toHaveClass(/top-bar__column--mobile-hidden/);
      await expect(column).toBeHidden();
    });
  });

  test.describe('Contact column — visible on mobile', () => {
    test('shows contact link when On at mobile viewport', async ({ page }) => {
      const id0 = await setColumnMobileVisibilityAndPublish(page, 'contact', true);

      await page.setViewportSize(MOBILE_VIEWPORT);
      await page.goto('/');

      const bar = barLocator(page, id0);
      const column = bar.locator('.top-bar__column').first();
      await expect(column).not.toHaveClass(/top-bar__column--mobile-hidden/);
      await expect(column).toBeVisible();
      const link = bar.locator('.top-bar-contact-column__link').first();
      await expect(link).toBeVisible();
      await expect(link).toHaveAttribute('href', /mailto:hello%40example\.com/i);
    });

    test('hides column when Off at mobile viewport', async ({ page }) => {
      const id0 = await setColumnMobileVisibilityAndPublish(page, 'contact', false);

      await page.setViewportSize(MOBILE_VIEWPORT);
      await page.goto('/');

      const bar = barLocator(page, id0);
      const column = bar.locator('.top-bar__column').first();
      await expect(column).toHaveClass(/top-bar__column--mobile-hidden/);
      await expect(column).toBeHidden();
    });
  });
});
