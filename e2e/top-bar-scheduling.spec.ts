import { expect, test } from '@playwright/test';

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASSWORD ?? 'admin';

function toDatetimeLocalValue(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

test.describe('scheduled', () => {
  test('should set future schedule and make top-bar hidden on frontend', async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.getByLabel('Username or Email Address').fill(ADMIN_USER);
    await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASS);
    await page.getByRole('button', { name: 'Log In' }).click();

    await page.goto('/wp-admin/options-general.php?page=top-bar');

    const firstPanel = page.locator('.top-bar-options').first();
    if (!(await firstPanel.isVisible())) {
      await page.locator('.top-bar-toggle-options').first().click();
    }

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
    await page.goto('/wp-login.php');
    await page.getByLabel('Username or Email Address').fill(ADMIN_USER);
    await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASS);
    await page.getByRole('button', { name: 'Log In' }).click();

    await page.goto('/wp-admin/options-general.php?page=top-bar');

    const firstPanel = page.locator('.top-bar-options').first();
    if (!(await firstPanel.isVisible())) {
      await page.locator('.top-bar-toggle-options').first().click();
    }

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

