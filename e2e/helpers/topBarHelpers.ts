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

  // Wait for Vue app to mount and load data
  await page.waitForSelector('#top-bar-app', { state: 'visible', timeout: 10000 });
  await page.waitForTimeout(1000); // Wait for API data to load
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
    await page.waitForTimeout(300); // Wait for animation
  }
}

export async function clickAddNewTopBar(page: Page): Promise<void> {
  // Vue app uses a button, not a link
  const addButton = page.getByRole('button', { name: 'Add new Top Bar' }).first();

  if ((await addButton.count()) === 0) {
    throw new Error('No "Add new Top Bar" control found on page.');
  }

  await addButton.waitFor({ state: 'visible', timeout: 10000 });
  await addButton.click({ timeout: 10000 });
  // Vue updates reactively, no page reload
  await page.waitForTimeout(500); // Wait for Vue to update DOM
}

export async function addBars(page: Page, count: number): Promise<void> {
  for (let i = 0; i < count; i += 1) {
    await clickAddNewTopBar(page);
  }
}

export async function resetToSingleBar(page: Page): Promise<void> {
  const root = process.cwd();
  const composeFile = `${root}/docker-compose.yml`;
  const command = `docker compose -f "${composeFile}" exec -T wordpress php -r 'require_once "/var/www/html/wp-load.php"; $bars = [[ "id" => "bar_single", "name" => "Single bar", "visible" => true, "admin_visibile" => false, "scheduled_enabled" => false, "scheduled_from_datetime" => "", "scheduled_to_datetime" => "", "position" => "top", "effect" => "none", "messages" => ["Single bar for tests.", ""], "messages_mobile_visible" => true, "bg_color" => "#389339", "frame_color" => "", "frame_width" => 0, "hide_on_scroll" => false ]]; update_option("top_bars", $bars);'`;

  execSync(command, { stdio: 'pipe' });
  await page.goto(TOP_BAR_SETTINGS_PATH);
  await page.waitForLoadState('domcontentloaded');

  // Wait for Vue app to load and render
  await page.waitForSelector('#top-bar-app', { state: 'visible', timeout: 10000 });
  await page.waitForTimeout(1000); // Wait for Vue to fetch and render bars
}

export async function getBarIds(page: Page): Promise<string[]> {
  const count = await page.locator('.top-bar-row.bg').count();
  const ids: string[] = [];
  for (let i = 0; i < count; i += 1) {
    const barRow = page.locator('.top-bar-row.bg').nth(i);
    // Extract ID from the name input's id attribute (e.g., "name_bar_abc123")
    const nameInput = barRow.locator('input[type="text"]').first();
    const inputId = await nameInput.getAttribute('id');
    if (inputId && inputId.startsWith('name_')) {
      const barId = inputId.replace('name_', '');
      ids.push(barId);
    }
  }
  return ids;
}

export async function getBarIdByIndex(page: Page, index: number): Promise<string> {
  const barRow = page.locator('.top-bar-row.bg').nth(index);
  const nameInput = barRow.locator('input[type="text"]').first();
  const inputId = await nameInput.getAttribute('id');
  if (inputId && inputId.startsWith('name_')) {
    return inputId.replace('name_', '');
  }
  throw new Error(`Could not find bar ID for index ${index}`);
}

export async function setBarPosition(page: Page, barIndex: number, position: 'top' | 'bottom'): Promise<void> {
  await openPanel(page, barIndex); // Ensure panel is open
  await page.waitForTimeout(300); // Wait for panel animation
  const barId = await getBarIdByIndex(page, barIndex);
  const positionSelect = page.locator(`#position_${barId}`);
  await positionSelect.waitFor({ state: 'visible', timeout: 5000 });
  await positionSelect.selectOption(position);
  await page.waitForTimeout(500); // Wait for API save
}

export async function setBarHideOnScroll(page: Page, barIndex: number, hideOnScroll: boolean): Promise<void> {
  const barId = await getBarIdByIndex(page, barIndex);
  const hideOnScrollSelect = page.locator(`#hide_on_scroll_${barId}`);
  await hideOnScrollSelect.selectOption(hideOnScroll ? 'true' : 'false');
  await page.waitForTimeout(500); // Wait for API save
}

export async function setSchedule(
  page: Page,
  barIndex: number,
  enabled: boolean,
  from?: string,
  to?: string
): Promise<void> {
  await openPanel(page, barIndex);

  const checkbox = page.locator('.top-bar-toggle-life-time').nth(barIndex);
  const isChecked = await checkbox.isChecked();

  if (enabled && !isChecked) {
    await checkbox.check();
    await page.waitForTimeout(500);
  } else if (!enabled && isChecked) {
    await checkbox.uncheck();
    await page.waitForTimeout(500);
  }

  if (enabled && from && to) {
    const barId = await getBarIdByIndex(page, barIndex);
    const fromInput = page.locator(`#scheduled_from_${barId}`);
    const toInput = page.locator(`#scheduled_to_${barId}`);

    await fromInput.fill(from);
    await toInput.fill(to);
    await page.waitForTimeout(500); // Wait for API save
  }
}

