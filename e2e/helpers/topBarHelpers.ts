import { type Page } from '@playwright/test';

declare const process: { env: Record<string, string | undefined>; cwd: () => string };
declare const require: (name: string) => any;

const { execSync } = require('node:child_process');

const ADMIN_USER = process.env.WP_ADMIN_USER ?? 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASSWORD ?? 'admin';
const TOP_BAR_SETTINGS_PATH = '/wp-admin/options-general.php?page=top-bar';

export const MAX_BARS = 5;

export function toDatetimeLocalValue(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export async function loginAndOpenTopBarSettings(page: Page): Promise<void> {
  // Go straight to the admin settings page; WP redirects to login if needed.
  await page.goto(TOP_BAR_SETTINGS_PATH, { waitUntil: 'domcontentloaded', timeout: 20000 });

  const loginInput = page.locator('input[name="log"]');
  const topBarRoot = page.locator('#top-bar');
  const hasLogin = (await loginInput.count()) > 0;
  const hasTopBar = (await topBarRoot.count()) > 0;

  if (!hasLogin && !hasTopBar) {
    // One retry in case WordPress is still finishing startup.
    await page.waitForTimeout(2000);
    await page.goto(TOP_BAR_SETTINGS_PATH, { waitUntil: 'domcontentloaded', timeout: 20000 });
  }

  if (await loginInput.count()) {
    await loginInput.first().fill(ADMIN_USER);
    await page.locator('input[name="pwd"]').first().fill(ADMIN_PASS);
    await page.locator('input[name="wp-submit"]').first().click();
  }

  await page.goto(TOP_BAR_SETTINGS_PATH, { waitUntil: 'domcontentloaded', timeout: 20000 });
  const finalTopBar = page.locator('#top-bar').first();
  await finalTopBar.waitFor({ state: 'visible', timeout: 15000 }).catch(async () => {
    const url = page.url();
    const title = await page.title();
    throw new Error(`Failed to open Top Bar settings. URL: ${url}, title: ${title}`);
  });
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
  const root = process.cwd();
  const composeFile = `${root}/docker-compose.yml`;
  const command = `docker compose -f "${composeFile}" exec -T wordpress php -r 'require_once "/var/www/html/wp-load.php"; $bars = [[ "id" => "bar_single", "name" => "Single bar", "visible" => true, "admin_visibile" => false, "scheduled_enabled" => false, "scheduled_from_datetime" => "", "scheduled_to_datetime" => "", "position" => "top", "effect" => "none", "messages" => ["Single bar for tests.", ""], "bg_color" => "#389339", "frame_color" => "", "frame_width" => 0, "hide_on_scroll" => false ]]; update_option("top_bars", $bars);'`;

  execSync(command, { stdio: 'pipe' });
  await page.goto(TOP_BAR_SETTINGS_PATH);
  await page.waitForLoadState('domcontentloaded');
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

