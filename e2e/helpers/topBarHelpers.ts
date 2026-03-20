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
    await clickAddNewTopBar(page);
  }
}

export async function openPanel(page: Page, index: number): Promise<void> {
  const panel = page.locator('.top-bar-options').nth(index);
  if (!(await panel.isVisible())) {
    await page.locator('.top-bar-toggle-options').nth(index).click();
  }
}

export async function clickAddNewTopBar(page: Page): Promise<void> {
  // Prefer explicit add-action URL, independent of button size/style classes.
  let addLink = page.locator('a[href*="top_bar_add=1"]:not([aria-disabled="true"])').first();
  if ((await addLink.count()) === 0) {
    // Fallback when URL shape changes but label remains stable.
    addLink = page.getByRole('link', { name: 'Add new Top Bar' }).first();
  }

  if ((await addLink.count()) === 0) {
    throw new Error('No "Add new Top Bar" control found on page.');
  }

  await addLink.waitFor({ state: 'visible', timeout: 10000 });
  try {
    await addLink.click({ timeout: 10000 });
  } catch {
    await addLink.click({ force: true, timeout: 10000 });
  }
  await page.waitForLoadState('domcontentloaded');
}

export async function addBars(page: Page, count: number): Promise<void> {
  for (let i = 0; i < count; i += 1) {
    await clickAddNewTopBar(page);
  }
}

export async function resetToSingleBar(page: Page): Promise<void> {
  for (let i = 0; i < 10; i += 1) {
    const count = await page.locator('.top-bar-row.bg').count();
    if (count <= 1) {
      return;
    }

    const deleteLink = page.locator('.top-bar-row.bg a.top-bar-icons.delete').first();
    await deleteLink.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

export async function getBarIds(page: Page): Promise<string[]> {
  const count = await page.locator('.top-bar-row.bg').count();
  const ids: string[] = [];
  for (let i = 0; i < count; i += 1) {
    const id = (await page.locator(`input[name="top_bars[${i}][id]"]`).inputValue()).trim();
    ids.push(id);
  }
  return ids;
}

