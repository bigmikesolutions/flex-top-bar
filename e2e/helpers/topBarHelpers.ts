import { type Page } from '@playwright/test';

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASSWORD ?? 'admin';

export function toDatetimeLocalValue(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export async function loginAndOpenTopBarSettings(page: Page): Promise<void> {
  await page.goto('/wp-login.php');
  await page.getByLabel('Username or Email Address').fill(ADMIN_USER);
  await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASS);
  await page.getByRole('button', { name: 'Log In' }).click();
  await page.goto('/wp-admin/options-general.php?page=top-bar');
}

export async function ensureAtLeastBars(page: Page, expectedBars: number): Promise<void> {
  for (let i = 0; i < 5; i += 1) {
    const count = await page.locator('.top-bar-row.bg').count();
    if (count >= expectedBars) {
      return;
    }
    await page.getByRole('link', { name: 'Add new Top Bar' }).first().click();
    await page.waitForLoadState('domcontentloaded');
  }
}

export async function openPanel(page: Page, index: number): Promise<void> {
  const panel = page.locator('.top-bar-options').nth(index);
  if (!(await panel.isVisible())) {
    await page.locator('.top-bar-toggle-options').nth(index).click();
  }
}

