import { expect, test } from '@playwright/test';
import {
  ensureAtLeastBars,
  loginAndOpenTopBarSettings,
  openPanel,
  toDatetimeLocalValue,
} from './helpers/topBarHelpers';

declare const process: { env: Record<string, string | undefined> };

test.describe('basic settings - position', () => {
  test('should save bar as top and render it at top', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await ensureAtLeastBars(page, 1);
    await openPanel(page, 0);

    const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
    const position0 = page.locator('select[name="top_bars[0][position]"]');

    await position0.evaluate((el: HTMLSelectElement) => {
      el.value = 'top';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

    await page.goto('/');

    const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
    await expect(topBar).toHaveCount(1);
    await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
    await expect(topBar).toHaveClass(/top-bar--top/);
  });

  test('should save bar as bottom and render it at bottom', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await ensureAtLeastBars(page, 1);
    await openPanel(page, 0);

    const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
    const position0 = page.locator('select[name="top_bars[0][position]"]');

    await position0.evaluate((el: HTMLSelectElement) => {
      el.value = 'bottom';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

    await page.goto('/');

    const bottomBar = page.locator(`[data-top-bar-id="${id0}"]`);
    await expect(bottomBar).toHaveCount(1);
    await expect(bottomBar).toHaveAttribute('data-top-bar-position', 'bottom');
    await expect(bottomBar).toHaveClass(/top-bar--bottom/);
  });
});

test.describe('basic settings - hide on scroll', () => {
  test('should save top position with hide-on-scroll and hide after window scroll', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await ensureAtLeastBars(page, 1);
    await openPanel(page, 0);

    const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
    const position0 = page.locator('select[name="top_bars[0][position]"]');
    const hideOnScroll0 = page.locator('select[name="top_bars[0][hide_on_scroll]"]');

    await position0.evaluate((el: HTMLSelectElement) => {
      el.value = 'top';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await hideOnScroll0.evaluate((el: HTMLSelectElement) => {
      el.value = '1';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });

    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

    await page.goto('/');

    const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
    await expect(topBar).toHaveCount(1);
    await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
    await expect(topBar).toHaveClass(/top-bar--top/);
    await expect(topBar).toHaveAttribute('data-top-bar-scroll-hide', '1');

    // Initial state at top of page: visible.
    await expect(topBar).toBeVisible();
    await expect(topBar).not.toHaveCSS('display', 'none');

    // Scroll past threshold (30px) and verify the bar is hidden by script.
    await page.evaluate(() => window.scrollTo(0, 200));
    await expect(topBar).toHaveCSS('display', 'none');
  });

  test('should save top position with hide-on-scroll disabled and stay visible after window scroll', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await ensureAtLeastBars(page, 1);
    await openPanel(page, 0);

    const id0 = await page.locator('input[name="top_bars[0][id]"]').inputValue();
    const position0 = page.locator('select[name="top_bars[0][position]"]');
    const hideOnScroll0 = page.locator('select[name="top_bars[0][hide_on_scroll]"]');

    await position0.evaluate((el: HTMLSelectElement) => {
      el.value = 'top';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await hideOnScroll0.evaluate((el: HTMLSelectElement) => {
      el.value = '0';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });

    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

    await page.goto('/');

    const topBar = page.locator(`[data-top-bar-id="${id0}"]`);
    await expect(topBar).toHaveCount(1);
    await expect(topBar).toHaveAttribute('data-top-bar-position', 'top');
    await expect(topBar).toHaveClass(/top-bar--top/);
    // Disabled mode omits the attribute entirely in frontend markup.
    await expect(topBar).not.toHaveAttribute('data-top-bar-scroll-hide', '1');

    await expect(topBar).toBeVisible();
    await expect(topBar).not.toHaveCSS('display', 'none');

    await page.evaluate(() => window.scrollTo(0, 200));
    await expect(topBar).toBeVisible();
    await expect(topBar).not.toHaveCSS('display', 'none');
  });
});

test.describe('scheduled', () => {
  test('should set future schedule and make top-bar hidden on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await ensureAtLeastBars(page, 2);
    await openPanel(page, 0);

    const scheduled = page.locator('input[name="top_bars[0][scheduled_enabled]"][type="checkbox"]');
    const barIdInput = page.locator('input[name="top_bars[0][id]"]');
    const fromInput = page.locator('input[name="top_bars[0][scheduled_from_datetime]"]');
    const toInput = page.locator('input[name="top_bars[0][scheduled_to_datetime]"]');
    const barId = (await barIdInput.inputValue()).trim();

    // Hidden checkbox: toggle via label first, then force JS state as fallback.
    await page.locator('label.top-bar-life-time-checkbox').first().click();
    await scheduled.evaluate((el: HTMLInputElement) => {
      if (!el.checked) {
        el.checked = true;
        el.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });

    await expect(scheduled).toBeChecked();
    // Use a far-future window so this bar should be hidden on frontend now.
    await fromInput.fill('2099-03-21T11:00');
    await toInput.fill('2099-03-21T12:30');

    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

    await page.reload();
    await expect(scheduled).toBeChecked();
    await expect(fromInput).toHaveValue('2099-03-21T11:00');
    await expect(toInput).toHaveValue('2099-03-21T12:30');

    await page.goto('/');
    await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(0);
  });

  test('should set schedule covering now and make top-bar visible on frontend', async ({ page }) => {
    await loginAndOpenTopBarSettings(page);
    await ensureAtLeastBars(page, 2);
    await openPanel(page, 0);

    const scheduled = page.locator('input[name="top_bars[0][scheduled_enabled]"][type="checkbox"]');
    const barIdInput = page.locator('input[name="top_bars[0][id]"]');
    const fromInput = page.locator('input[name="top_bars[0][scheduled_from_datetime]"]');
    const toInput = page.locator('input[name="top_bars[0][scheduled_to_datetime]"]');
    const barId = (await barIdInput.inputValue()).trim();

    await page.locator('label.top-bar-life-time-checkbox').first().click();
    await scheduled.evaluate((el: HTMLInputElement) => {
      if (!el.checked) {
        el.checked = true;
        el.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
    await expect(scheduled).toBeChecked();

    // Wide range around now to avoid timezone edge cases.
    const now = new Date();
    const from = new Date(now.getTime() - 24 * 60 * 60 * 1000);
    const to = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const fromValue = toDatetimeLocalValue(from);
    const toValue = toDatetimeLocalValue(to);

    await fromInput.fill(fromValue);
    await toInput.fill(toValue);

    await page.getByRole('button', { name: 'Save Changes' }).click();
    await expect(page.locator('#setting-error-settings_updated, .notice-success')).toBeVisible();

    await page.reload();
    await expect(scheduled).toBeChecked();
    await expect(fromInput).toHaveValue(fromValue);
    await expect(toInput).toHaveValue(toValue);

    await page.goto('/');
    await expect(page.locator(`[data-top-bar-id="${barId}"]`)).toHaveCount(1);
  });
});

